<?php
require_once(__DIR__.'/../includes/application_top.php');

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

set_time_limit(0);

prepared_query::execute('UPDATE products p, products_stock_control psc SET p.products_weight = 0, psc.stock_weight = 0 WHERE p.stock_id = psc.stock_id AND psc.is_bundle = 1 AND (p.products_weight != 0 OR psc.stock_weight != 0)');
