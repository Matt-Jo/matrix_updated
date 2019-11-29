<?php
class feed_listrakorders extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8');
		$feed_namespace = 'listrakorders__';
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

	public function build() {

		$start = time();

		$this->header = [
			'email', //email address of customer
			'order_number', //unique order number
			'date_entered', //timestamp of order date (EST)
			'order_total', //total value of the order (remove currency symbols)
			'item_total', //total cost of items ordered (subtotal)
			'tax_total', //total sales tax charged
			'shipping_total', //total shipping costs
			'handling_total', //total handling costs
			'status', //status indicator (see Status Codes)
			'ship_date', //timestamp when entire order shipped (EST)
			'tracking_number', //shipment tracking number
			'shipping_method', //shipping method (e.g. UPS Ground)
			'coupon_code', //coupon code used with order
			'discount_total', //total value of order discount
			'source', //describes the srouce at which the order was placed
			'meta1', //addtional information
			'meta2', 
			'meta3', 
			'meta4', 
			'meta5' 
		];

		//Not in use, but for future reference
		$listrak_order_status_code = [
			'Not Set' => 0,
			'Misc.' => 1, 
			'Pre-Order' => 2,
			'Backorder' => 3,
			'Pending' => 4,
			'Hold' => 5,
			'Processing' => 6,
			'Shipped' => 7,
			'Completed' => 8,
			'Returned' => 9,
			'Canceled' => 10,
			'Unknown' => 11
		];

		//Mapping CKs Order Status' with Listrak Order Status'
		$order_status_mapping = [
			5 => 3, //Backorder
			2 => 6, //Pending
			3 => 7, //Shipped
			11 => 5, //Hold
			7 => 6, //Processing
			6 => 10, //Canceled
			9 => 9, //Returned
			8 => 5, //Hold
			10 => 4 //Pending
		];

		//***begin bulk (historical) data pull
		//set date selection for queries
		$date_selection = '2015-01-11 00:00:00';

		$start_time = !empty($_GET['start'])?$_GET['start']:NULL;
		$increment = new DateTime($start_time);
		if (empty($start_time)) $increment->sub(new DateInterval('PT31M'));

		//query *most* order details
		$order_details_results = prepared_query::fetch('SELECT customers_email_address, orders_id, date_purchased, source FROM orders WHERE date_purchased >= :date_selection ORDER BY orders_id ASC', cardinality::SET, [':date_selection' => $increment->format('Y-m-d H:i:s')]);
		
		//loop through orders and populate an array with order ids to prepare to select the oldest one
		foreach($order_details_results as $key => $orders) {
			$orders_id_list[] = $orders['orders_id'];
		}
		
		if (!empty($orders_id_list)) $order_id_selection = $oldest_order = min($orders_id_list);

		echo 'Start feed at order #: '.$oldest_order.'<br>';

		//query order sub total
		$orders_sub_total_results = prepared_query::fetch('SELECT orders_id, value AS sub_total FROM orders_total WHERE class = \'ot_total\' AND orders_id >= :order_id_selection', cardinality::SET, [':order_id_selection' => $order_id_selection]);
		foreach($orders_sub_total_results as $ostr) {
			$order_total[$ostr['orders_id']] = $ostr['sub_total'];
		}
		unset($orders_sub_total_results);

		//query order tax value
		$orders_tax_value_results = prepared_query::fetch('SELECT orders_id, value AS tax_total FROM orders_total WHERE class = \'ot_tax\' AND orders_id >= :order_id_selection', cardinality::SET, [':order_id_selection' => $order_id_selection]);
		foreach($orders_tax_value_results as $otvr) {
			$tax_total[$otvr['orders_id']] = $otvr['tax_total'];
		}
		unset($orders_tax_value_resutls);

		//query shipping cost
		$shipping_cost_results = prepared_query::fetch('SELECT orders_id, value AS shipping_cost FROM orders_total WHERE class = \'ot_shipping\' AND orders_id >= :order_id_selection', cardinality::SET, [':order_id_selection' => $order_id_selection]);
		foreach($shipping_cost_results as $scr) {
			$shipping_total[$scr['orders_id']] = $scr['shipping_cost'];
		}
		unset($shipping_cost_results);

		//query discount total data, loop through and then unset data results variable
		$discounts_total_results = prepared_query::fetch('SELECT orders_id, value AS discount_total FROM orders_total WHERE class = \'ot_coupon\' AND orders_id >= :order_id_selection', cardinality::SET, [':order_id_selection' => $order_id_selection]);
		foreach($discounts_total_results as $dtr) {
			$discount_total[$dtr['orders_id']] = $dtr['discount_total'];
		}
		unset($discount_total_results);

		//product total query, loop through, then unset data results variable
		$orders_products_total_results = prepared_query::fetch('SELECT orders_id, SUM(final_price) AS item_total FROM orders_products WHERE orders_id >= :order_id_selection GROUP BY orders_id', cardinality::SET, [':order_id_selection' => $order_id_selection]);
		foreach($orders_products_total_results as $optr) {
			$item_total[$optr['orders_id']] = $optr['item_total'];
		}

		//query coupon code data, loop through and then unset data results variable
		$coupon_codes_results = prepared_query::fetch('SELECT crt.order_id, c.coupon_code FROM coupon_redeem_track crt LEFT JOIN coupons c ON crt.coupon_id = c.coupon_id WHERE crt.order_id >= :order_id_selection', cardinality::SET, [':order_id_selection' => $order_id_selection]);
		foreach($coupon_codes_results as $ccr) {
		$coupon_code[$ccr['order_id']] = $ccr['coupon_code'];
		}
		unset($coupon_codes_results);

		//query for order ship date
		$order_ship_dates_results = prepared_query::fetch('SELECT osh.orders_id, osh.date_added FROM orders_status_history osh WHERE osh.orders_status_id = 3 ORDER BY osh.date_added', cardinality::SET);
		//loop through order ship dates set key equal to order id
		foreach($order_ship_dates_results as $osdr) {
			$ship_date[$osdr['orders_id']] = $osdr['date_added'];
		}
		unset($order_ship_dates_results);

		//query for tracking number, loop through and then unset results variable
		$extra_details_results = prepared_query::fetch('SELECT op.orders_id, ot.tracking_num, sm.name AS shipping_method FROM orders_packages op JOIN orders_tracking ot ON op.orders_packages_id = ot.orders_packages_id LEFT JOIN shipping_methods sm ON sm.shipping_code = ot.shipping_method_id WHERE op.orders_id >= :order_id_selection AND ot.void = 0', cardinality::SET, [':order_id_selection' => $order_id_selection]);
		foreach($extra_details_results as $edr) {
			if (!empty($tracking_numbers[$edr['orders_id']])) $tracking_numbers[$edr['orders_id']] .= ', '.trim($edr['tracking_num']);
			else $tracking_numbers[$edr['orders_id']] = trim($edr['tracking_num']);
			$shipping_method[$edr['orders_id']] = $edr['shipping_method'];
			$tracking_number[$edr['orders_id']] = rtrim($tracking_numbers[$edr['orders_id']], ',');
		}
		unset($tracking_numbers);
		unset($extra_details_results);

		//query order status, loop through set the most recent one and unset results variable
		$orders_status_results = prepared_query::fetch('SELECT orders_id, orders_status_id FROM orders_status_history WHERE date_added >= :date_selection ORDER BY orders_id ASC, orders_status_history_id ASC', cardinality::SET, [':date_selection' => $date_selection]);
		foreach($orders_status_results as $osr) {
			$order_status[$osr['orders_id']] = $osr['orders_status_id'];
		}
		unset($orders_status_results);

		echo 'total orders queried: '.count($order_details_results).'<br>';
		
		foreach ($order_details_results as $idx => $odr) {
			$this->data[] = [
				'email' => $odr['customers_email_address'],
				'order_number' => $odr['orders_id'],
				'date_entered' => $odr['date_purchased'],
				'order_total' => !empty($order_total[$odr['orders_id']])?$order_total[$odr['orders_id']]:NULL,
				'item_total' => !empty($item_total[$odr['orders_id']])?$item_total[$odr['orders_id']]:NULL,
				'tax_total' => !empty($tax_total[$odr['orders_id']])?$tax_total[$odr['orders_id']]:NULL,
				'shipping_total' => !empty($shipping_total[$odr['orders_id']])?$shipping_total[$odr['orders_id']]:NULL,
				'handling_total' => NULL,
				'status' => $order_status_mapping[$order_status[$odr['orders_id']]],
				'ship_date' => !empty($ship_date[$odr['orders_id']])?$ship_date[$odr['orders_id']]:NULL,
				'tracking_number' => !empty($tracking_number[$odr['orders_id']])?$tracking_number[$odr['orders_id']]:NULL,
				'shipping_method' => !empty($shipping_method[$odr['orders_id']])?$shipping_method[$odr['orders_id']]:NULL,
				'coupon_code' => !empty($coupon_code[$odr['orders_id']])?$coupon_code[$odr['orders_id']]:NULL,
				'discount_total' => !empty($discount_total[$odr['orders_id']])?$discount_total[$odr['orders_id']]:NULL,
				'source' => !empty($odr['source'])?$odr['source']:NULL,
				'meta1' => NULL, //$order->get_header('customer_type'),
				'meta2' => NULL, // - sales rep full name
				'meta3' => NULL, //$order->get_header('channel'),
				'meta4' => NULL, //$order->get_po_number(),
				'meta5' => NULL //$order->get_header('campaign')
			];
			
			if ($idx % 10000 == 0) echo 'checkpoint ('.$idx.')<br> Time elapsed: '.(time() - $start).' seconds<br>';
		}
		echo '<br><span style=\'color:red\'>Completed in: '.(time() - $start).' seconds<br></span>'; 

		unset($start);
		
		/*$orders_id = prepared_query::fetch('SELECT orders_id FROM orders WHERE orders_status = 3 AND date_purchased >= :start_time ORDER BY orders_id ASC', cardinality::SET, [':start_time' => $increment->format('Y-m-d H:i:s')]);

		$this->header = [
			'email', //email address of customer
			'order_number', //unique order number
			'date_entered', //timestamp of order date (EST)
			'order_total', //total value of the order (remove currency symbols)
			'item_total', //total cost of items ordered (subtotal)
			'tax_total', //total sales tax charged
			'shipping_total', //total shipping costs
			'handling_total', //total handling costs
			'status', //status indicator (see Status Codes)
			'ship_date', //timestamp when entire order shipped (EST)
			'tracking_number', //shipment tracking number
			'shipping_method', //shipping method (e.g. UPS Ground)
			'coupon_code', //coupon code used with order
			'discount_total', //total value of order discount
			'source', //describes the srouce at which the order was plac ed
			'meta1', //addtional information
			'meta2', 
			'meta3', 
			'meta4', 
			'meta5' 
		];

		//Not in use, but for future reference
		$listrak_order_status_code = [
			'Not Set' => 0,
			'Misc.' => 1, 
			'Pre-Order' => 2,
			'Backorder' => 3,
			'Pending' => 4,
			'Hold' => 5,
			'Processing' => 6,
			'Shipped' => 7,
			'Completed' => 8,
			'Returned' => 9,
			'Canceled' => 10,
			'Unknown' => 11
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

		echo count($orders_id);

		$start = time();

		foreach ($orders_id as $idx => $order_id) {
			$order = new ck_sales_order($order_id['orders_id']);

			$order_ship_date = service_locator::getDbService()->fetchOne('SELECT date_added FROM orders_status_history WHERE orders_status_id = :orders_status_id AND orders_id = :orders_id ORDER BY date_added DESC LIMIT 1', [':orders_status_id' => 3, ':orders_id' => $order_id['orders_id']]);

			$tracking_number = service_locator::getDbService()->fetchOne('SELECT ot.tracking_num FROM orders_packages op JOIN orders_tracking ot ON op.orders_packages_id = ot.orders_packages_id WHERE op.orders_id = :orders_id LIMIT 1', [':orders_id' => $order_id['orders_id']]);

			//get products and loop through them for item total
			$item_total = NULL;
			foreach ($order->get_products() as $products) {
				$item_total += $products['final_price'];
			}
			unset($products);

			//populate ship date with invoice date
			$ship_date = NULL;
			//if ($order->has_invoices()) $ship_date = $order->get_invoices('invoice_date');

			$row = [
				'email' => $order->get_prime_contact(),
				'order_number' => $order->id(),
				'date_entered' => $order->get_header('date_purchased'),
				'order_total' => $order->get_simple_totals('total'),
				'item_total' => $item_total,
				'tax_total' => $order->get_simple_totals('tax'),
				'shipping_total' => $order->get_simple_totals('shipping'),
				'handling_total' => NULL,
				'status' => $order_status_mapping[$order->get_header('orders_status_name')],
				'ship_date' => $order_ship_date,
				'tracking_number' => $tracking_number,
				'shipping_method' => $order->get_shipping_method(),
				'coupon_code' => NULL,
				'discount_total' => $order->get_simple_totals('coupon'),
				'source' => $order->get_header('source'),
				'meta1' => NULL, //$order->get_header('customer_type'),
				'meta2' => NULL, //
				'meta3' => NULL, //$order->get_header('channel'),
				'meta4' => NULL, //$order->get_po_number(),
				'meta5' => NULL //$order->get_header('campaign')
			];

			$this->data[] = $row;
			//manage memory
			unset($order);

			if ($idx%50 == 0) gc_collect_cycles();
		}
		echo "\n".(time() - $start).' seconds'; 
		*/
	}
} ?>