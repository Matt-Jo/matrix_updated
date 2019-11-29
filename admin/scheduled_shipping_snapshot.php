<?php
require_once(__DIR__.'/../includes/application_top.php');

$wh_packing_queue_orders = 0;
$wh_packing_queue_lines = 0;
$wh_uncat_orders = 0;
$wh_uncat_lines = 0;
$rtp_orders = 0;
$rtp_lines = 0;
$wh_packing_queue_list = [];
$wh_uncat_list = [];
$rtp_orders_list = [];
$wh_picking_lines = 0;
$wh_picking_list = [];
$wh_picking_orders = 0;

try {

	$shipping_orders = prepared_query::fetch('SELECT orders_id, orders_status, orders_sub_status FROM orders WHERE orders_status IN (2, 7)', cardinality::SET);
	
	foreach ($shipping_orders as $orders) {
		if($orders['orders_status'] == '7') {
			if($orders['orders_sub_status'] == 24) {
				$wh_packing_queue_orders ++;
				$wh_packing_queue_list[] = $orders['orders_id'];
			}
			elseif ($orders['orders_sub_status'] == 25 || $orders['orders_sub_status'] == 0) {
				$wh_uncat_orders ++;
				$wh_uncat_list[] = $orders['orders_id'];
			}
			elseif ($orders['orders_sub_status'] == 26) {
				$wh_picking_orders ++;
				$wh_picking_list[] = $orders['orders_id'];
			}
		}
		elseif($orders['orders_status'] == '2') {
			$rtp_orders ++;
			$rtp_orders_list[] = $orders['orders_id'];
		}
		$orders_array[] = $orders['orders_id'];
	}

	$ttl_lines = prepared_query::fetch('SELECT orders_products_id, orders_id FROM orders_products WHERE orders_id IN ('.implode(',', $orders_array).') GROUP BY products_id', cardinality::SET);
	
	foreach ($ttl_lines as $tl) {
		if (in_array($tl['orders_id'], $wh_packing_queue_list)) {
			$wh_packing_queue_lines ++;
		}
		elseif (in_array($tl['orders_id'], $wh_uncat_list)) {
			$wh_uncat_lines ++;
		}
		elseif (in_array($tl['orders_id'], $rtp_orders_list)) {
			$rtp_lines ++;
		}
		elseif (in_array($tl['orders_id'], $wh_picking_list)) {
			$wh_picking_lines ++;
		}
	}

	prepared_query::execute('INSERT INTO ck_shipping_status (timestamp, wh_packing_queue_lines, wh_packing_queue_orders, wh_uncat_lines, wh_uncat_orders, rtp_lines, rtp_orders, wh_picking_lines, wh_picking_orders) VALUES (now(), :wh_packing_queue_lines, :wh_packing_queue_orders, :wh_uncat_lines, :wh_uncat_orders, :rtp_ttl_lines, :rtp_order_qty, :wh_picking_lines, :wh_picking_orders)', [':wh_packing_queue_lines' => $wh_packing_queue_lines, ':wh_packing_queue_orders' => $wh_packing_queue_orders, ':wh_uncat_lines' => $wh_uncat_lines, ':wh_uncat_orders' => $wh_uncat_orders, ':rtp_ttl_lines' => $rtp_lines, ':rtp_order_qty' => $rtp_orders, ':wh_picking_lines' => $wh_picking_lines, ':wh_picking_orders' => $wh_picking_orders]);

}
catch (Exception $e) {
	echo $e->getMessage();
} ?>