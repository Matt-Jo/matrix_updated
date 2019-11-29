<?php
require('includes/application_top.php');

$breadcrumb->add('Log Off');

unset($_SESSION['previous_page']);

unset($_SESSION['customer_id']);
unset($_SESSION['customer_default_address_id']);
unset($_SESSION['customer_first_name']);
unset($_SESSION['customer_country_id']);
unset($_SESSION['customer_zone_id']);
unset($_SESSION['admin']);
unset($_SESSION['comments']);
//ICW - logout -> unregister GIFT VOUCHER sessions - Thanks Fredrik
unset($_SESSION['gv_id']);
unset($_SESSION['cc_id']);
//ICW - logout -> unregister GIFT VOUCHER sessions - Thanks Fredrik
 
//customer cards
unset($_SESSION['customer_cards']);
 
unset($_SESSION['customer_extra_login_id']);

if (isset($_COOKIE['osCAdminID2'])) {
	try {
		if ($admin_session_string = prepared_query::fetch('SELECT value FROM sessions WHERE sesskey = :sesskey AND (modified + expiry) > :exp', cardinality::SINGLE, [':sesskey' => $_COOKIE['osCAdminID2'], ':exp' => time()])) {
			ck_session::assign_admin_user_login($admin_session_string);
		}
	}
	catch (Exception $e) {
		// don't need to do anything
	}
}

$_SESSION['cart']->reset_cart();

service_locator::get_session_service()->destroy();

$content = 'logoff';

require('templates/Pixame_v1/main_page.tpl.php');
?>
