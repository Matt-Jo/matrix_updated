<?php
require_once(__DIR__.'/../../includes/application_top.php');

prepared_query::execute('DELETE FROM ck_carts WHERE cart_key IS NULL AND TO_DAYS(date_updated) < TO_DAYS(NOW()) - 2');
prepared_query::execute('DELETE FROM ck_carts WHERE customers_id IS NULL AND TO_DAYS(date_updated) < TO_DAYS(NOW()) - 2');
prepared_query::execute('DELETE FROM ck_carts WHERE date_updated IS NULL AND TO_DAYS(date_created) < TO_DAYS(NOW()) - 2');
prepared_query::execute('DELETE c FROM ck_carts c LEFT JOIN ck_cart_products cp ON c.cart_id = cp.cart_id LEFT JOIN ck_cart_quotes cq ON c.cart_id = cq.cart_id WHERE cp.cart_product_id IS NULL AND cq.cart_quote_id IS NULL AND TO_DAYS(c.date_created) < TO_DAYS(NOW()) - 2');

$duplicated_carts = prepared_query::fetch('SELECT c.* FROM ck_carts c JOIN ck_carts c0 ON c.cart_key = c0.cart_key AND c.cart_id != c0.cart_id ORDER BY c.cart_key ASC, c.cart_id DESC');

$last_cart = '';

foreach ($duplicated_carts as $cart) {
	if ($cart['cart_key'] != $last_cart['cart_key']) {
		$last_cart = $cart;
		continue;
	}

	prepared_query::execute('DELETE FROM ck_carts WHERE cart_id = :cart_id', [':cart_id' => $cart['cart_id']]); // just delete earlier carts - we may someday choose to merge them, but not this day
}

prepared_query::execute('DELETE cp FROM ck_cart_payments cp LEFT JOIN ck_carts c ON cp.cart_id = c.cart_id WHERE c.cart_id IS NULL');
prepared_query::execute('DELETE cp FROM ck_cart_products cp LEFT JOIN ck_carts c ON cp.cart_id = c.cart_id WHERE c.cart_id IS NULL');
prepared_query::execute('DELETE cq FROM ck_cart_quotes cq LEFT JOIN ck_carts c ON cq.cart_id = c.cart_id WHERE c.cart_id IS NULL');
prepared_query::execute('DELETE cs FROM ck_cart_shipments cs LEFT JOIN ck_carts c ON cs.cart_id = c.cart_id WHERE c.cart_id IS NULL');
?>