<?php
$router = ck_router::instance();

$map = [
	'shopping-cart' => 'ck_view_shopping_cart',
	'page-includer' => 'ck_view_page_includer',
	'login' => 'ck_view_login',
	'password_forgotten' => 'ck_view_forgot_password',
	'merchandising-container' => 'ck_merchandising_container_view',
	'inventory-report' => 'ck_view_inventory_report',
	'checkout_shipping.php' => 'ck_checkout_shipping_view',
	'checkout_payment.php' => 'ck_checkout_payment_view',
	'checkout_address.php' => 'ck_checkout_address_view',
	'checkout_confirmation.php' => 'ck_checkout_confirmation_view',
	'checkout_success.php' => 'ck_checkout_success_view',
	'account-setup' => 'ck_view_account_setup',
	'cart-flyout' => 'ck_cart_flyout_view',
	'my-account' => 'ck_customer_account_view',
	'my-account/orders' => 'ck_customer_account_orders_view',
	'my-account/addresses' => 'ck_customer_account_addresses_view',
	'my-account/info' => 'ck_customer_account_info_view',
	'my-account/payment' => 'ck_customer_account_payment_view',
	'erp/ipn-lookup' => 'ck_erp_ipn_lookup_view',
	'maintenance' => 'ck_maintenance_view',
];

if (!ck::area_in_maintenance('db')) {
	// setup custom page routes
	$custom_pages = ck_custom_page::get_all('active');
	foreach ($custom_pages as $page) $map += [$page['url'] => 'ck_custom_page_view'];
}

// front controller routes - this isn't really set up yet, but it's useful to get a head start on it
$router->create_route('/front_controller.php', $map, function() {
	return $_GET['final_target'];
});

$router->create_simple_route('/shopping_cart.php', 'ck_view_shopping_cart');
//$router->create_simple_route('/page_includer.php', 'ck_view_page_includer');
$router->create_simple_route('/login.php', 'ck_view_login');
$router->create_simple_route('/password_forgotten.php', 'ck_view_forgot_password');
$router->create_simple_route('/inventory-report', 'ck_view_inventory_report');
?>
