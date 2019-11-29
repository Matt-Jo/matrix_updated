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
class fedexnonsaturday {
	var $code, $title, $description, $sort_order, $icon, $tax_class, $enabled, $meter, $intl;

	// class constructor
	function __construct() {
		global $admin_auth, $order;
		$this->code = 'fedexnonsaturday';
		$this->title = MODULE_SHIPPING_FEDEXNONSATURDAY_TEXT_TITLE;
		$this->description = MODULE_SHIPPING_FEDEXNONSATURDAY_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_SHIPPING_FEDEXNONSATURDAY_SORT_ORDER;
		$this->icon = DIR_WS_ICONS.'shipping_fedex_express.jpg';
		$this->tax_class = MODULE_SHIPPING_FEDEXNONSATURDAY_TAX_CLASS;
		$this->enabled = ((MODULE_SHIPPING_FEDEXNONSATURDAY_STATUS == 'True') ? true : false);
		$this->meter = MODULE_SHIPPING_FEDEXNONSATURDAY_METER;

		$this->domestic_mappings = array(
			'PRIORITY_OVERNIGHT' => '2',
			'STANDARD_OVERNIGHT' => '3',
			'FEDEX_2_DAY' => '4',
			'FEDEX_EXPRESS_SAVER' => '5',
			'PRIORITY_OVERNIGHT_SAT' => '7',
			'FEDEX_2_DAY_SAT' => '8',
			'FEDEX_GROUND' => '9',
		);

		$this->international_mappings = array(
			'INTERNATIONAL_PRIORITY' => '13',
			'INTERNATIONAL_ECONOMY' => '14'/*, MMD - don't show international ground - 042414
			'FEDEX_GROUND' => '15'*/
		);

		// flip the force disable since we're moving from Fedex to UPS
		// only show this for customers who are allowed to ship on their own Fedex account
		// $pre_enabled = $this->enabled;
		// $this->enabled = true;
		/*
		 * we are turning fedex back on because we want to allow customers to use their own fedex shipping account if they choose
		 * if (!empty($_SESSION['customer_id'])) {
			$customer = service_locator::getDbService()->fetchRow('SELECT customers_fedex, dealer_shipping_module FROM customers WHERE customers_id = ?', $_SESSION['customer_id']);
			// per Martin, enable Fedex if they are allowed to ship on their own account even if they are international
			if (!empty(trim($customer['customers_fedex'])) && $customer['dealer_shipping_module'] == '1') { // && $order->delivery['country_id'] == '223') {
				$this->enabled = $pre_enabled;
			}
		}*/
	}

	// class methods
	function quote($method = '') {
		global $shipping_weight, $shipping_num_boxes, $order, $format_shipping;
		//$GLOBALS['SHIPPING_FAILOVER'][] = 'upsxmlfedexexpress';
		//return;
		$ltime = localtime(time(), true);
		$saturday_shipping = false;
		if (($ltime['tm_wday'] == 3 && $ltime['tm_hour'] >= 20) || //Wednesday after 8
			$ltime['tm_wday'] == 4 || //Anytime Thursday
			($ltime['tm_wday'] == 5 && $ltime['tm_hour'] < 20)) { //Friday before 8
			$saturday_shipping = true;
		}

		$address_data = [
			'first_name' => @$order->delivery['firstname'],
			'last_name' => @$order->delivery['lastname'],
			'company_name' => @$order->delivery['company'],
			'address1' => @$order->delivery['street_address'],
			'address2' => @$order->delivery['suburb'],
			'city' => @$order->delivery['city'],
			'postcode' => $order->delivery['postcode'],
			'state' => @$order->delivery['state'],
			'country' => $order->delivery['country']['iso_code_2'],
			'telephone' => @$order->delivery['telephone'],
			'country_address_format_id' => '1'
		];

		$address_type = new ck_address_type();
		$address_type->load('header', $address_data);
		$address = new ck_address2(NULL, $address_type);

		require_once(DIR_FS_CATALOG.DIR_WS_FUNCTIONS.'fedex_webservices.php');

		$this->quotes = array('id' => $this->code, 'module' => '<hr><font color="#F47820" size="2"><b>'.$this->title.'</b></font>', 'methods' => array());
		if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title,'','','align="absmiddle"');

		$saturday_rates = array();
		if ($saturday_shipping) {
			$saturday_rates = fws_get_rates($address, $shipping_weight, $shipping_num_boxes, true);
		}
		$rates = fws_get_rates($address, $shipping_weight, $shipping_num_boxes, false);
		$rates = array_merge($saturday_rates, $rates);

		foreach ($rates as $code => $costs) {
			$mappings = $this->domestic_mappings;
			$cost_multiplier = 1;
			$cost = $costs['list_rate'];
			if ($address->is_international()) {
				$mappings = $this->international_mappings;
				if (!empty($mappings[$code]) && $mappings[$code] != '15') {
					$cost_multiplier = 1.1;
					$cost = $costs['negotiated_rate'];
				}
			}

			$cost *= $cost_multiplier;

			if ((!empty($mappings[$code]) && $method == '') || $method == $code) {
				$this->quotes['methods'][] = array(
					'id' => $code,
					'title' => $mappings[$code],
					'shipping_method_id' => $mappings[$code],
					'cost' => $cost,
					'negotiated_rate' => $costs['negotiated_rate'],
				);
			}
		}

		if (empty($this->quotes['methods'])) $GLOBALS['SHIPPING_FAILOVER'][] = 'upsxmlfedexexpress';

		return $this->quotes;
	}

	function check() {
		if (!isset($this->_check)) {
			$check_query = prepared_query::fetch("select configuration_value from configuration where configuration_key = 'MODULE_SHIPPING_FEDEXNONSATURDAY_STATUS'");
			$this->_check = count($check_query);
		}
		return $this->_check;
	}

	function install() {
	}

	function remove() {
		prepared_query::execute("delete from configuration where configuration_key in ('".implode("', '", $this->keys())."')");
	}

	function keys() {
		return array('MODULE_SHIPPING_FEDEXNONSATURDAY_STATUS', 'MODULE_SHIPPING_FEDEXNONSATURDAY_ACCOUNT', 'MODULE_SHIPPING_FEDEXNONSATURDAY_METER', 'MODULE_SHIPPING_FEDEXNONSATURDAY_CURL', 'MODULE_SHIPPING_FEDEXNONSATURDAY_DEBUG', 'MODULE_SHIPPING_FEDEXNONSATURDAY_WEIGHT', 'MODULE_SHIPPING_FEDEXNONSATURDAY_SERVER', 'MODULE_SHIPPING_FEDEXNONSATURDAY_ADDRESS_1', 'MODULE_SHIPPING_FEDEXNONSATURDAY_ADDRESS_2', 'MODULE_SHIPPING_FEDEXNONSATURDAY_CITY', 'MODULE_SHIPPING_FEDEXNONSATURDAY_STATE', 'MODULE_SHIPPING_FEDEXNONSATURDAY_POSTAL', 'MODULE_SHIPPING_FEDEXNONSATURDAY_PHONE', 'MODULE_SHIPPING_FEDEXNONSATURDAY_DROPOFF', 'MODULE_SHIPPING_FEDEXNONSATURDAY_TRANSIT', 'MODULE_SHIPPING_FEDEXNONSATURDAY_SURCHARGE', 'MODULE_SHIPPING_FEDEXNONSATURDAY_LIST_RATES', 'MODULE_SHIPPING_FEDEXNONSATURDAY_INSURE', 'MODULE_SHIPPING_FEDEXNONSATURDAY_RESIDENTIAL', 'MODULE_SHIPPING_FEDEXNONSATURDAY_ENVELOPE', 'MODULE_SHIPPING_FEDEXNONSATURDAY_WEIGHT_SORT', 'MODULE_SHIPPING_FEDEXNONSATURDAY_TIMEOUT', 'MODULE_SHIPPING_FEDEXNONSATURDAY_TAX_CLASS','MODULE_SHIPPING_FEDEXNONSATURDAY_SORT_ORDER','MODULE_SHIPPING_FEDEXNONSATURDAY_INTERNATIONAL_PERCENTAGE');
	}

}
?>
