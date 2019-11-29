<?php
include('includes/application_top.php');

$ipn = ck_ipn2::get_ipn_by_ipn($_GET['stock_name']);

$output = ['results' => []];

foreach ($ipn->get_listings() as $product) {
	if (!$product->is('products_status')) continue;
	$output['results'][] = ['name' => addslashes($product->get_header('products_name')), 'id' => $product->id()];
}

echo '('.json_encode($output).')';
?>
