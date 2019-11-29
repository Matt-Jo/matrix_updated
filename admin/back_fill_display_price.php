<?php
require_once(__DIR__.'/../includes/application_top.php');

error_reporting(E_ALL);

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

prepared_query::execute("UPDATE orders_products op, (SELECT
	op.orders_products_id,
	p.products_price,
	p.products_dealer_price,
	psc.dealer_price,
	itc.price as customer_price,
	s.specials_new_products_price as specials_price,
	CASE
		WHEN op.products_price = p.products_price THEN 1
		WHEN op.products_price = psc.dealer_price THEN 4
		WHEN NOT psc.dealer_price > 0 AND op.products_price = p.products_dealer_price THEN 4
		WHEN op.products_price = itc.price THEN 3
		WHEN op.products_price = s.specials_new_products_price THEN 2
		ELSE -1
	END as price_reason
FROM
	orders_products op
		JOIN orders o ON o.orders_id = op.orders_id
		LEFT JOIN products p ON op.products_id = p.products_id
		LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id
		LEFT JOIN ipn_to_customers itc ON psc.stock_id = itc.stock_id AND itc.customers_id = o.customers_id
		LEFT JOIN specials s ON p.products_id = s.products_id
WHERE
	o.date_purchased >= '2012-09-21' AND op.price_reason IS NULL) p
SET
	op.display_price = CASE WHEN p.price_reason != -1 THEN op.products_price ELSE NULL END,
	op.price_reason = p.price_reason
WHERE op.orders_products_id = p.orders_products_id AND op.price_reason IS NULL");
?>
