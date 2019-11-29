<?php 
require('includes/application_top.php');

$stock_id = $_GET['stock_id'];
$month = $_GET['month'];
$year = $_GET['year'];

//now we will retrieve the sales for the selected period
$sales_query = prepared_query::fetch('SELECT o.orders_id, o.customers_id, o.customers_name, o.date_purchased, o.orders_status, op.products_id, op.products_name, op.final_price, op.products_quantity, os.orders_status_name, op.exclude_forecast, op.orders_products_id, op.cost AS products_cost, psc.average_cost FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id LEFT JOIN customers c ON o.customers_id = c.customers_id LEFT JOIN orders_status os ON o.orders_status = os.orders_status_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE MONTH(o.date_purchased) = :month AND YEAR(o.date_purchased) = :year AND YEAR(o.date_purchased) = :year AND o.orders_status IN (1, 2, 3, 5, 7, 8, 10, 11, 12) AND p.stock_id = :stock_id ORDER BY o.date_purchased DESC', cardinality::SET, [':stock_id' => $stock_id, ':month' => $month, ':year' => $year]); 

$response = [
	'month' => $month,
	'year' => $year,
	'sales' => []
];

foreach ($sales_query as $sale) {
	empty($sale['products_cost'])?$products_cost = $sale['products_cost']:$products_cost = $sale['average_cost'];
	$products_margin = $sale['final_price']-$products_cost;

	$response['sales'][] = [
		'orders_id' => $sale['orders_id'],
		'customers_id' => $sale['customers_id'],
		'customers_name' => addslashes($sale['customers_name']),
		'date_purchased' => date('m/d/y', strtotime($sale['date_purchased'])),
		'products_id' => $sale['products_id'],
		'products_name' => addslashes($sale['products_name']),
		'products_quantity' => $sale['products_quantity'],
		'final_price' => number_format($sale['final_price'], 2),
		'orders_status' => addslashes($sale['orders_status_name']),
		'exclude_forecast' => $sale['exclude_forecast'],
		'orders_products_id' => $sale['orders_products_id'],
		'products_cost'  => number_format($products_cost, 2),
		'products_margin' => number_format($products_margin * $sale['products_quantity'], 2),
		'products_margin_percentage' => $sale['final_price']==0?'<i>Incalculable</i>':number_format(($products_margin/$sale['final_price'])*100, 2).'%'
	];
}

echo json_encode($response);
exit();
?>
