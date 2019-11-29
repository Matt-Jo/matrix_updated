<?php
require_once(__DIR__.'/../../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

@ini_set("memory_limit","2048M");
set_time_limit(0);

$start = time();

$orders = prepared_query::fetch('SELECT orders_id, customers_id, date_purchased FROM orders ORDER BY customers_id, date_purchased DESC', cardinality::SET);

$customers = [];

foreach ($orders as $idx => $order) {
	if (isset($customers[$order['customers_id']])) {
		$params = [':date_purchased' => $customers[$order['customers_id']], ':orders_id' => $order['orders_id']];

		prepared_query::execute('UPDATE orders SET following_order_date = :date_purchased WHERE orders_id = :orders_id', $params);
	}
	$customers[$order['customers_id']] = $order['date_purchased'];

	if ($idx % 1000 == 0) echo 'IDX: '.$idx.'<br>';
}

echo 'Update took '.(time()-$start).' seconds; updated '.count($orders).' orders';
?>
