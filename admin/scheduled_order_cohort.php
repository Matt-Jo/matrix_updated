<?php
require_once(__DIR__.'/../includes/application_top.php');

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

@ini_set("memory_limit","768M");
set_time_limit(0);

try {
	// get our counts
	$count_raw = prepared_query::fetch('SELECT o.customers_id, (IFNULL(MAX(o.customer_order_count), 0) + 1) as customer_order_count FROM orders o JOIN (SELECT DISTINCT customers_id FROM orders WHERE customer_order_count IS NULL) o2 ON o.customers_id = o2.customers_id GROUP BY o.customers_id', cardinality::SET);
	$counts = array();
	foreach ($count_raw as $count) {
		$counts[$count['customers_id']] = $count['customer_order_count'];
	}
	unset($count_raw);
	// set up the table, or empty it if it exists

	$orders = prepared_query::fetch('SELECT customers_id, orders_id FROM orders o WHERE customer_order_count IS NULL ORDER BY customers_id ASC, date_purchased ASC, orders_id ASC', cardinality::SET);
	foreach ($orders as $idx => $order) {
		if (empty($counts[$order['customers_id']])) $counts[$order['customers_id']] = 1;

		prepared_query::execute('UPDATE orders SET customer_order_count = ? WHERE orders_id = ?', array($counts[$order['customers_id']], $order['orders_id']));

		$counts[$order['customers_id']]++;
	}
}
catch (Exception $e) {
	echo $e->getMessage();
	// we should make some sort of notification to someone who cares here
}
?>
