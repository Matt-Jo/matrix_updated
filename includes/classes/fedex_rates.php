<?php
/*
Version 2.04 for MS2 and earlier

osCommerce, Open Source E-Commerce Solutions
http://www.oscommerce.com

Copyright (c) 2002, 2003 Steve Fatula of Fatula Consulting
compconsultant@yahoo.com

Released under the GNU General Public License
*/

if (!defined('DEBUG_NOW')) define('DEBUG_NOW', false);
class fedex_rates{
	var $meter, $intl;

	// class constructor
	function __construct() {
		$this->meter = MODULE_SHIPPING_FEDEXNONSATURDAY_METER;

		$this->domestic_mappings = array(
			'PRIORITY_OVERNIGHT' => '2',
			'STANDARD_OVERNIGHT' => '3',
			'FEDEX_2_DAY' => '4',
			'FEDEX_EXPRESS_SAVER' => '5',
			'PRIORITY_OVERNIGHT_SAT' => '7',
			'FEDEX_2_DAY_SAT' => '8',
			'FEDEX_GROUND' => '9'
		);

		$this->international_mappings = array(
			'INTERNATIONAL_PRIORITY' => '13',
			'INTERNATIONAL_ECONOMY' => '14'
		);

	}

	// class methods
	function get_quotes(ck_address2 $delivery_address, $shipping_weight, $shipping_num_boxes = 1) {

		$ltime = localtime(time(), true);
		$saturday_shipping = false;
		if (($ltime['tm_wday'] == 3 && $ltime['tm_hour'] >= 20) || //Wednesday after 8
			$ltime['tm_wday'] == 4 || //Anytime Thursday
			($ltime['tm_wday'] == 5 && $ltime['tm_hour'] < 20)) { //Friday before 8
			$saturday_shipping = true;
		}

		require_once(DIR_FS_CATALOG.DIR_WS_FUNCTIONS.'fedex_webservices.php');

		$saturday_rates = array();
		if ($saturday_shipping) {
			$saturday_rates = fws_get_rates($delivery_address, $shipping_weight, $shipping_num_boxes, true);
		}
		$rates = fws_get_rates($delivery_address, $shipping_weight, $shipping_num_boxes, false);
		$rates = array_merge($saturday_rates, $rates);

		$methods = array();
		foreach ($rates as $code => $cost) {
			$mappings = $this->domestic_mappings;
			if ($delivery_address->get_header('countries_iso_code_2') != 'US') {
				$mappings = $this->international_mappings;
			}
			if (!empty($mappings[$code])) {
				$methods[] = array(
					'id' => $code,
					'title' => $mappings[$code],
					'shipping_method_id' => $mappings[$code],
					'cost' => $cost
				);
			}
		}
		return $methods;
	}
}
?>
