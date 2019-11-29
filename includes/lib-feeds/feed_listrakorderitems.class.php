<?php
ini_set('memory_limit', '2048M');

class feed_listrakorderitems extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8');
		$feed_namespace = 'listrakorderitems__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2);

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';

		$this->ftp_server = "ftp.listrakbi.com";
		$this->ftp_user = "FAUser_CableandKits";
		$this->ftp_pass = "rlo075Vt8hL00ks";
		$this->ftp_path = "";
		$this->destination_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';

		parent::__construct(self::OUTPUT_FTP, self::DELIM_TAB, self::FILE_CSV);

		$this->category_depth = 0;
		$this->category_hierarchy = TRUE;
		$this->needs_attributes = TRUE;
	}

	public function __destruct() {
		parent::__destruct(); //write the file
	}

	private static $failure_stmt = NULL;
	private static $failed_loop = FALSE;
	
	private static function loop_has_failed($status=NULL) {
		if (!empty($status)) return self::$failed_loop = TRUE;
		elseif (!empty(self::$failed_loop)) {
			self::$failed_loop = FALSE;
			return TRUE;
		}
		return FALSE;
	}

	public function build() {
		/** Listrak required data: order_number, sku, quantity, price **/
		
		$start = time();

		$this->header = [
			'order_number',
			'sku',
			'quantity',
			'price',
			'status',
			'ship_date',
			'tracking_number',
			'shipping_method',
			'discounted_price',
			'item_total',
			'meta1',
			'meta2',
			'meta3',
			'meta4',
			'meta5'
		];
		
		//Mapping CKs Order Status' with Listrak Order Status'
		$order_status_mapping = [
			'Backorder' => 3, //Backorder
			'Ready To Pick' => 6, //Pending
			'Shipped' => 7, //Shipped
			'Customer service' => 5, //Hold
			'Warehouse' => 6, //Processing
			'Canceled' => 10, //Canceled
			'Refunded' => 9, //Returned
			'On Hold (PayPal)' => 5, //Hold
			'Preparing [PayPal IPN]' => 4 //Pending
		];

		$start_time = !empty($_GET['start'])?$_GET['start']:NULL;
		$increment = new DateTime($start_time);
		if (empty($start_time)) $increment->sub(new DateInterval('PT31M'));

		/*Begin bulk data pull*/
		//query *most* order item details
		$orders_items_details = prepared_query::fetch('SELECT op.orders_id, op.products_id, op.products_model, op.final_price, op.products_quantity FROM orders_products op JOIN orders o ON o.orders_id = op.orders_id WHERE o.date_purchased >= :date_selection', cardinality::SET, [':date_selection' => $increment->format('Y-m-d H:i:s')]);
		
		//pull order ship date
		$order_ship_date = prepared_query::fetch('SELECT orders_id, date_added FROM orders_status_history WHERE orders_status_id = :orders_status_id ORDER BY date_added', cardinality::SET, [':orders_status_id' => 3]);

		//loop through ship date query and build array with order id as key
		foreach($order_ship_date as $osd) $ship_date[$osd['orders_id']] = $osd['date_added'];
		
		unset($order_ship_date);

		//pulling tracking number and shipping method
		$orders_extra_details = prepared_query::fetch('SELECT op.orders_id, ot.tracking_num, sm.name AS shipping_method FROM orders_packages op JOIN orders_tracking ot ON op.orders_packages_id = ot.orders_packages_id LEFT JOIN shipping_methods sm ON sm.shipping_code = ot.shipping_method_id', cardinality::SET);
		
		//loop through orders extra details query and build array with order id as key
		foreach($orders_extra_details as $tn) {
			$tracking_number[$tn['orders_id']] = $tn['tracking_num'];
			$shipping_method[$tn['orders_id']] = $tn['shipping_method'];
		}
		unset($orders_extra_details);

		//query order status
		$orders_status = prepared_query::fetch('SELECT osh.orders_id, os.orders_status_name FROM orders_status_history osh JOIN orders_status os ON osh.orders_status_id = os.orders_status_id WHERE osh.date_added >= :date_selection ORDER BY osh.orders_id ASC, osh.orders_status_history_id ASC', cardinality::SET, [':date_selection' => '2014-01-12 00:00:00']);

		//loop through order status results and set order id as key
		foreach($orders_status as $os) {
			$order_status[$os['orders_id']] = $os['orders_status_name'];
		}
		unset($orders_status);
		
		echo count($orders_items_details).'<br>';

		foreach($orders_items_details as $idx => $oid) {

			$this->data[] = [
				'order_number' => $oid['orders_id'],
				'sku' => $oid['products_id'],
				'quantity' => $oid['products_quantity'],
				'price' => $oid['final_price'],
				'status' => $order_status_mapping[$order_status[$oid['orders_id']]],
				'ship_date' => empty($ship_date[$oid['orders_id']])?NULL:$ship_date[$oid['orders_id']],
				'tracking_number' => empty($tracking_number[$oid['orders_id']])?NULL:$tracking_number[$oid['orders_id']],
				'shipping_method' => empty($shipping_method[$oid['orders_id']])?NULL:$shipping_method[$oid['orders_id']],
				'discounted_price' => NULL,
				'item_total' => $oid['products_quantity'] * $oid['final_price'],
				'meta1' => NULL,
				'meta2' => NULL,
				'meta3' => NULL,
				'meta4' => NULL,
				'meta5' => NULL	
			];

			if ($idx % 10000 == 0) echo 'checkpoint ('.$idx.')<br> Time elapsed: '.(time() - $start).' seconds<br>';
			flush();
		}
		echo '<br><span style=\'color:red\'>Completed in: '.(time() - $start).' seconds<br></span>'; 
		/*end bulk data pull*/

		/*
		$orders_ids = prepared_query::fetch('SELECT orders_id FROM orders WHERE orders_status = 3 AND date_purchased >= :start_time ORDER BY orders_id ASC', cardinality::SET, [':start_time' => $increment->format('Y-m-d H:i:s')]);
		
		foreach ($orders_ids as $orders_id) {

			$order = new ck_sales_order($orders_id['orders_id']);

			$order_ship_date = service_locator::getDbService()->fetchOne('SELECT date_added FROM orders_status_history WHERE orders_status_id = :orders_status_id AND orders_id = :orders_id ORDER BY date_added DESC LIMIT 1', [':orders_status_id' => 3, ':orders_id' => $order->id()]);

			$tracking_number = service_locator::getDbService()->fetchOne('SELECT ot.tracking_num FROM orders_packages op JOIN orders_tracking ot ON op.orders_packages_id = ot.orders_packages_id WHERE op.orders_id = :orders_id LIMIT 1', [':orders_id' => $order_id['orders_id']]);

			foreach ($order->get_products() as $product) {
				$row = [
					'order_number' => $order->id(),
					'sku' => $product['products_id'],
					'quantity' => $product['quantity'],
					'price' => $product['final_price'],
					'status' => $order_status_mapping[$order->get_header('orders_status_name')],
					'ship_date' => $order_ship_date,
					'tracking_number' => $tracking_number,
					'shipping_method' => $order->get_shipping_method(),
					'discounted_price' => NULL,
					'item_total' => $product['quantity'] * $product['final_price'],
					'meta1' => NULL,
					'meta2' => NULL,
					'meta3' => NULL,
					'meta4' => NULL,
					'meta5' => NULL
				];

				$this->data[] = $row;
			}
		}
		echo "\n".(time() - $start).' seconds';
		*/
	}
}?>