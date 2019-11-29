<?php
require_once('includes/application_top.php');

$query = $_GET['q'];

$stock_names = prepared_query::fetch("select stock_name from products_stock_control where stock_name like :match", cardinality::COLUMN, [':match' => $query."%"]);

foreach ($stock_names as $stock_name) {
	echo $stock_name."\n";
}
