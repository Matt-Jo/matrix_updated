<?php
$router = ck_router::instance();

$routes = [];
$routes['team-list'] = 'ck_view_admin_team_list';
$routes['customer-quote-dashboard.php'] = 'ck_view_admin_customer_quote_dashboard';
$routes['customer-quote.php'] = 'ck_view_admin_customer_quote';
$routes['custom-page-manager'] = 'ck_view_admin_custom_page_manager';
$routes['inventory-disposition.php'] = 'ck_view_admin_inventory_disposition';
$routes['site-constants'] = 'ck_admin_site_constants_view';
$routes['lookup-manager'] = 'ck_admin_lookup_manager_view';
$routes['dynamic-lookup-manager'] = 'ck_admin_dynamic_lookup_manager_view';
$routes['bundle-included-product-import'] = 'ck_view_admin_bundle_included_product_import';
$routes['category-redirects'] = 'ck_admin_category_redirects_view';
$routes['upload-suggested-buys'] = 'ck_admin_suggested_buy_view';
$routes['promos'] = 'ck_admin_promos_view';
$routes['wholesale-price-worksheet'] = 'ck_admin_wholesale_price_worksheet_view';
$routes['ipn-creation-review-dashboard'] = 'ck_admin_ipn_creation_review_dashboard_view';
$routes['manage-tax-liabilities'] = 'ck_admin_manage_tax_liabilities_view';
$routes['admin-account-details'] = 'ck_view_admin_account_details';
$routes['my-orders'] = 'ck_admin_my_orders_view';
$routes['ghost-invoices'] = 'ck_admin_ghost_invoices_view';
$routes['orders'] = 'ck_admin_orders_view';
$routes['holidays'] = 'ck_admin_holidays_view';

$router->create_route('/front_controller.php', $routes, function() {
	return $_GET['request'];
});
?>
