<?php
require_once('includes/application_top.php');

if (empty($ipn)) {
	if (!empty($_REQUEST['stock_id'])) $ipn = new ck_ipn2($_REQUEST['stock_id']);
	elseif (!empty($_REQUEST['ipnId'])) $ipn = ck_ipn2::get_ipn_by_ipn($_REQUEST['ipnId']);
	else $ipn = NULL;
}

/*if ($ipn) {
	$tabs = [];
	$tabs[] = 'general';
	$tabs[] = 'products';
	$tabs[] = 'sales_history';
	$tabs[] = 'traffic';
	$tabs[] = 'quotes';
	$tabs[] = 'change_history';
	$tabs[] = 'purchase_history';
	$tabs[] = 'receiving_history';
	if ($ipn->is('serialized')) $tabs[] = 'serials';
	$tabs[] = 'vendors';
	$tabs[] = 'special_pricing';
	//$tabs[] = 'tier_pricing';
	$tabs[] = 'invoicing_history';
	$tabs[] = 'rfq_history';
	$tabs = array_flip($tabs);
}*/
?>