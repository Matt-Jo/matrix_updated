<?php

require_once('includes/application_top.php');

$stock_id = $_GET['stock_id'];
$month = $_GET['month'];
$year = $_GET['year'];

//now we will retrieve the sales for the selected period
$sales_query = "select ai.inv_order_id as orders_id, ai.customer_id as customers_id, o.customers_name, ai.inv_date, ";
$sales_query .= "aii.invoice_item_price as final_price, aii.invoice_item_qty as products_quantity ";
$sales_query .= "from acc_invoices ai left join acc_invoice_items aii on ai.invoice_id = aii.invoice_id left join customers c on ai.customer_id = c.customers_id left join orders o on o.orders_id = ai.inv_order_id ";
$sales_query .= "where MONTH(ai.inv_date) = ".$month." ";
$sales_query .= "and YEAR(ai.inv_date) = ".$year." ";
$sales_query .= "and aii.ipn_id = '$stock_id' ";
$sales_query .= "order by ai.inv_date desc";

$sales_query = prepared_query::fetch($sales_query);

$response = [
	'month' => $month,
	'year' => $year,
	'sales' => []
];

foreach ($sales_query as $sale) {
	$response['sales'][] = [
		'orders_id' => $sale['orders_id'],
		'customers_id' => $sale['customers_id'],
		'customers_name' => addslashes($sale['customers_name']),
		'inv_date' => date('m/d/y', strtotime($sale['inv_date'])),
		'products_quantity' => $sale['products_quantity'],
		'final_price' => number_format($sale['final_price'], 2),
	];
}

echo json_encode($response);
exit();
?>
