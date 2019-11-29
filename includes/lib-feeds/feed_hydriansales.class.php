<?php
class feed_hydriansales extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$feed_namespace = 'hydrian_sales__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2); // remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__H-i-s').'.txt';

		$this->ftp_server = 'files.hydrian.com';
		$this->ftp_user = 'cablesandkits';
		$this->ftp_pass = 'qBT!ZmmVC!7kc%e7FMjz';
		$this->ftp_path = 'Daily_Data_Files/Cables_and_Kits/To_Hydrian';
		$this->destination_filename = 'hydrian-sales.txt';
	
		parent::__construct(self::OUTPUT_SFTP, self::DELIM_TAB, self::FILE_NONE);

		$this->category_depth = 0;
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	private static $failed_loop = FALSE;

	private static function track_failure($stock_id, $reason) {
		self::loop_has_failed(TRUE);
		$insert = [':feed' => 'hydrian_sales', ':stock_id' => $stock_id, ':reason' => $reason];
		prepared_query::execute('INSERT INTO ck_feed_failure_tracking (feed, stock_id, reason) VALUES (:feed, :stock_id, :reason)', $insert);
	}

	private static function loop_has_failed($status=NULL) {
		if (!empty($status)) return self::$failed_loop = TRUE;
		elseif (!empty(self::$failed_loop)) {
			self::$failed_loop = FALSE;
			return TRUE;
		}
		return FALSE;
	}

	public function build() {
		debug_tools::mark('Load sales');
		$sales_lines = prepared_query::fetch('SELECT op.orders_products_id, psc.stock_id, psc.stock_name as ipn, op.orders_id, op.products_quantity as qty, op.final_price as unit_price, op.price_reason, op.exclude_forecast, o.customers_id, c.customers_email_address, IF(c.customer_type, \'DEALER\', \'RETAIL\') as price_type, c.customer_segment_id, cs.segment, os.orders_status_name, oss.orders_sub_status_name, op.qty_available_when_booked, op.expected_ship_date, o.date_purchased, o.promised_ship_date, MAX(ship.date_added) as shipped_date, MAX(cancel.date_added) as canceled_date, o.delivery_postcode, o.delivery_country, o.parent_orders_id FROM orders_products op JOIN orders o ON op.orders_id = o.orders_id AND o.date_purchased >= DATE(NOW()) - INTERVAL 1 YEAR JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id AND psc.is_bundle = 0 LEFT JOIN orders_status_history ship ON o.orders_id = ship.orders_id AND o.orders_status = ship.orders_status_id AND o.orders_status = :shipped LEFT JOIN orders_status_history cancel ON o.orders_id = cancel.orders_id AND o.orders_status = cancel.orders_status_id AND o.orders_status = :canceled LEFT JOIN customers c ON o.customers_id = c.customers_id LEFT JOIN customer_segments cs ON c.customer_segment_id = cs.customer_segment_id LEFT JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id GROUP BY op.orders_products_id ORDER BY o.date_purchased ASC, op.orders_products_id ASC', cardinality::SET, [':shipped' => ck_sales_order::STATUS_SHIPPED, ':canceled' => ck_sales_order::STATUS_CANCELED]);

		debug_tools::mark('Order Line Count: '.count($sales_lines));

		$feed = @fopen($this->file_path, 'w');
		
		$this->header = [
			'export_date', // NOW()
			'stock_id',
			'ipn', // NOT REQUESTED - but I think it's useful
			'order_id',
			'order_date', // booked date
			'line_expected_ship_date', // expected ship date for this line alone
			'ship_date', // the date the order was shipped, if it has shipped
			'cancel_date', // the date the order was canceled, if it has been canceled
			'order_qty', // qty on this order
			'unit_price', // the unit price we sold this item for
			'unit_price_reason', // why did we select this price?
			'distribution_center', // BUFORD
			'drop_shipment', // whether this order was fulfilled via dropshipment - we don't currently store this data, but we should
			'order_status', // our status description - all order statuses are relevant, including canceled
			'order_sub_status', // this isn't requested, but probably useful
			'promised_ship_date', // our promise date on the order, not the individual line
			'destination_zip', // the shipping address zip code
			'destination_country_code', // the 2 character country code
			'customer_id',
			'customer_price_type',
			'customer_segment',
			//'customer_email', // not requested, but useful for reporting
			'stock_status', // was this line fully in stock at the time the order was placed - e.g. is the promised date on the line the same as the booked date (or qty_available_when_booked is greater than qty)
			'preferred_warehouse', // BUFORD
			'unit_of_measure', // always 1 for us, for now
			'uom_conversion_to_each', // always 1 for us, for now
			'flag_excluded', // whether or not we excluded this line
			'flag_split_order', // whether or not this order was split from another
			'context', // production or dev
		];

		fwrite($feed, implode($this->delimeter, $this->header));

		$today = date('Y-m-d H:i:s');

		function get_price_reason($price_reason_code) {
			switch ($price_reason_code) {
				case 1:
					return 'RETAIL';
					break;
				case 2:
					return 'ON-SALE';
					break;
				case 3:
					return 'CUSTOMER-SPECIFIC';
					break;
				case 4:
					return 'WHOLESALE';
					break;
				case 5:
					return 'ADD-ON-OPTION';
					break;
				case 6:
					return 'QUOTED';
					break;
				default:
					return 'UNKNOWN';
					break;
			}
		}

		$config = service_locator::get_config_service();
		$context = service_locator::get_config_service()->is_production()?'PRODUCTION':'DEVELOPMENT';

		$countries = [];

		foreach ($sales_lines as $index => $sales_line) {
			if ($index%5000 == 0) debug_tools::mark('Iteration '.$index);

			$esd = $bd = $psd = $sd = $cd = NULL;

			$esd_empty = new DateTime('2099-01-01');

			if (!ck_master_archetype::date_is_empty($sales_line['expected_ship_date'])) $esd = ck_master_archetype::DateTime($sales_line['expected_ship_date']);
			if (!empty($esd) && $esd->format('Y-m-d') == $esd_empty->format('Y-m-d')) $esd = NULL;
			if (!ck_master_archetype::date_is_empty($sales_line['date_purchased'])) $bd = ck_master_archetype::DateTime($sales_line['date_purchased']);
			if (!ck_master_archetype::date_is_empty($sales_line['promised_ship_date'])) $psd = ck_master_archetype::DateTime($sales_line['promised_ship_date']);
			if (!ck_master_archetype::date_is_empty($sales_line['shipped_date'])) $sd = ck_master_archetype::DateTime($sales_line['shipped_date']);
			if (!ck_master_archetype::date_is_empty($sales_line['canceled_date'])) $cd = ck_master_archetype::DateTime($sales_line['canceled_date']);

			$line_in_stock = 0;

			if (is_numeric($sales_line['qty_available_when_booked']) && $sales_line['qty_available_when_booked'] > $sales_line['qty']) $line_in_stock = 1;
			elseif (!is_numeric($sales_line['qty_available_when_booked']) && !empty($esd) && $esd->format('Y-m-d') == $bd->format('Y-m-d')) $line_in_stock = 1;

			if (empty($countries[$sales_line['delivery_country']])) $countries[$sales_line['delivery_country']] = ck_address2::get_country($sales_line['delivery_country']);
			$country = @$countries[$sales_line['delivery_country']];

			$row = [
				'export_date' => $today, // NOW()
				'stock_id' => $sales_line['stock_id'],
				'ipn' => $sales_line['ipn'], // NOT REQUESTED - but I think it's useful
				'order_id' => $sales_line['orders_id'],
				'order_date' => $bd->format('Y-m-d H:i:s'), // booked date
				'line_expected_ship_date' => !empty($esd)?$esd->format('Y-m-d H:i:s'):NULL, // expected ship date for this line alone
				'ship_date' => !empty($sd)?$sd->format('Y-m-d H:i:s'):NULL, // the date the order was shipped, if it has shipped
				'cancel_date' => !empty($cd)?$cd->format('Y-m-d H:i:s'):NULL, // the date the order was canceled, if it has been canceled
				'order_qty' => $sales_line['qty'], // qty on this order
				'unit_price' => $sales_line['unit_price'], // the unit price we sold this item for
				'unit_price_reason' => get_price_reason($sales_line['price_reason']), // why did we select this price?
				'distribution_center' => 'BUFORD', // BUFORD
				'drop_shipment' => 'UNKNOWN', // whether this order was fulfilled via dropshipment - we don't currently store this data, but we should
				'order_status' => $sales_line['orders_status_name'], // our status description - all order statuses are relevant, including canceled
				'order_sub_status' => $sales_line['orders_sub_status_name'], // this isn't requested, but probably useful
				'promised_ship_date' => !empty($psd)?$psd->format('Y-m-d H:i:s'):NULL, // our promise date on the order, not the individual line
				'destination_zip' => trim($sales_line['delivery_postcode']), // the shipping address zip code
				'destination_country_code' => @$country['countries_iso_code_2'], // the 2 character country code
				'customer_id' => $sales_line['customers_id'],
				'customer_price_type' => $sales_line['price_type'],
				'customer_segment' => $sales_line['segment'],
				//'customer_email' => $sales_line['customers_email_address'], // not requested, but useful for reporting
				'stock_status' => $line_in_stock, // was this line fully in stock at the time the order was placed - e.g. is the promised date on the line the same as the booked date
				'preferred_warehouse' => 'BUFORD', // BUFORD
				'unit_of_measure' => 1, // always 1 for us, for now
				'uom_conversion_to_each' => 1, // always 1 for us, for now
				'flag_excluded' => $sales_line['exclude_forecast'], // whether or not we excluded this line
				'flag_split_order' => !empty($sales_line['parent_orders_id'])?1:0, // whether or not this order was split from another
				'context' => $context, // production or dev
			];

			fwrite($feed, "\n".implode($this->delimeter, $row));

			//$this->data[] = $row;
		}

		fclose($feed);

		debug_tools::mark('Finished building data.');
	}
} ?>
