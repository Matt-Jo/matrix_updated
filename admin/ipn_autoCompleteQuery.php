<?php
require_once('includes/application_top.php');

$query = $_GET['query'];

$stock_name_qry = [':query' => '^'.preg_replace('/([^a-zA-Z0-9.+])/', '$1?', $query)];

$stock_names = prepared_query::fetch('SELECT stock_name FROM products_stock_control WHERE stock_name RLIKE :query', cardinality::SET, $stock_name_qry);

$response = ['results' => []];
foreach ($stock_names as $stock_name) {
	$response['results'][] = ['name' => $stock_name['stock_name']];
}

echo json_encode($response);
