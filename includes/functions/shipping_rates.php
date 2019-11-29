<?php

/*
	@param delivery_address - ck_address2
	@param total_weight - total weight, in pounds, of each package
	@param number_of_packages - total number of packages
*/
function get_ups_rates(ck_address2 $delivery_address, $shipping_weight, $number_of_boxes = 1) {
	require_once(DIR_FS_CATALOG.'includes/classes/ups_rates.php');
	$ups = new ups_rates();
	$rates = $ups->get_quotes($delivery_address, $shipping_weight, $number_of_boxes);
	return $rates;
}

/*
	@param delivery_address - ck_address2
	@param total_weight - total weight, in pounds, of each package
	@param number_of_packages - total number of packages
*/
function get_usps_rates(ck_address2 $delivery_address, $total_weight, $number_of_packages = 1) {
	require_once(DIR_FS_CATALOG.'includes/classes/usps_rates.php');
	$usps = new usps_rates();
	$rates = $usps->get_quotes($delivery_address, $shipping_weight, $number_of_packages);
	return $rates;

}
function get_fedex_rates(ck_address2 $delivery_address, $shipping_weight, $number_of_boxes = '1') {
	require_once(DIR_FS_CATALOG.'includes/classes/fedex_rates.php');
	$fedex = new fedex_rates();
	$rates = $fedex->get_quotes($delivery_address, $shipping_weight, $number_of_boxes);
	return $rates;
}

