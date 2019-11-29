<?php

class fedexwebservices {
	public $code;
	public $title;
	public $description;
	public $icon;
	public $sort_order;
	public $enabled;
	public $tax_class;
	public $fedex_key;
	public $fedex_pwd;
	public $fedex_act_num;
	public $fedex_meter_num;
	public $country;

	//Class Constructor
	public function __construct() {
		$order = @$GLOBALS['order'];

		@define('MODULE_SHIPPING_FEDEX_WEB_SERVICES_INSURE', 0);
		$this->code = "fedexwebservices";
		$this->title = MODULE_SHIPPING_FEDEX_WEB_SERVICES_TEXT_TITLE;
		$this->description = MODULE_SHIPPING_FEDEX_WEB_SERVICES_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_SHIPPING_FEDEX_WEB_SERVICES_SORT_ORDER;
		$this->handling_fee = MODULE_SHIPPING_FEDEX_WEB_SERVICES_HANDLING_FEE;
		$this->icon = DIR_WS_ICONS.'shipping_fedex_ground.jpg';
		$this->enabled = MODULE_SHIPPING_FEDEX_WEB_SERVICES_STATUS=='true'?TRUE:FALSE;

		$this->tax_class = MODULE_SHIPPING_FEDEX_WEB_SERVICES_TAX_CLASS;
		$this->fedex_key = MODULE_SHIPPING_FEDEX_WEB_SERVICES_KEY;
		$this->fedex_pwd = MODULE_SHIPPING_FEDEX_WEB_SERVICES_PWD;
		$this->fedex_act_num = MODULE_SHIPPING_FEDEX_WEB_SERVICES_ACT_NUM;
		$this->fedex_meter_num = MODULE_SHIPPING_FEDEX_WEB_SERVICES_METER_NUM;
		if (defined("SHIPPING_ORIGIN_COUNTRY")) {
			if ((int) SHIPPING_ORIGIN_COUNTRY > 0) {
				$countries_array = $this->get_countries(SHIPPING_ORIGIN_COUNTRY, true);
				$this->country = $countries_array['countries_iso_code_2'];
			}
			else {
				$this->country = SHIPPING_ORIGIN_COUNTRY;
			}
		}
		else {
			$this->country = STORE_ORIGIN_COUNTRY;
		}

		$this->enabled = FALSE;

		// flip the force disable since we're moving from Fedex to UPS
		// only show this for customers who are allowed to ship on their own Fedex account
		// $pre_enabled = $this->enabled;
		//  $this->enabled = true;
		/*
		 * we are turning fedex back on because we want to allow customers to use their own fedex shipping account if they choose
		 * if (!empty($_SESSION['customer_id'])) {
			$customer = service_locator::getDbService()->fetchRow('SELECT customers_fedex, dealer_shipping_module FROM customers WHERE customers_id = ?', $_SESSION['customer_id']);
			// per Martin, enable Fedex if they are allowed to ship on their own account even if they are international
			if (!empty(trim($customer['customers_fedex'])) && $customer['dealer_shipping_module'] == '1') { // && $order->delivery['country_id'] == '223') {
				$this->enabled = $pre_enabled;
			}
		}*/

		//explicitly disallow fedex ground to canada
		// We're enabling fedex for all shipment types to international customers if they are allowed ot ship on their own account
		/*if (!empty($order) && $order->delivery['country']['id'] == '38') {
			$check_flag = false;
			$this->enabled = false;
		}*/

		if ($this->enabled == true && (int) MODULE_SHIPPING_FEDEX_WEB_SERVICES_ZONE > 0) {
			$check_flag = false;
			$checks = prepared_query::fetch('SELECT zone_id FROM zones_to_geo_zones WHERE geo_zone_id = ? AND zone_country_id = ? ORDER BY zone_id', cardinality::SET, array(MODULE_SHIPPING_FEDEX_WEB_SERVICES_ZONE, @$order->delivery['country']['id']));

			foreach ($checks as $check) {
				if (($check['zone_id'] <= 1) || (empty($check['zone_id']))) {
					$check_flag = true;
					break;
				}
				elseif ($check['zone_id'] == $order->delivery['zone_id']) {
					$check_flag = true;
					break;
				}
				// $check->MoveNext();
			}

			//explicitly allow fedex ground to canada
			/*if ($order->delivery['country']['id'] == '38') {
				$check_flag = true;
			}*/

			if ($check_flag == false) {
				$this->enabled = false;
			}
		}
	}

	//Class Methods

	public function quote($method='') {
		//$GLOBALS['SHIPPING_FAILOVER'][] = 'upsxmlfedexground';
		//	return;
		/* FedEx integration starts */
		$shipping_weight = $GLOBALS['shipping_weight'];
		$shipping_num_boxes = $GLOBALS['shipping_num_boxes'];
		$order = $GLOBALS['order'];

		require_once(DIR_FS_CATALOG.DIR_WS_INCLUDES.'lib-apis/fedex-common.php5');
		//if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_SERVER == 'test') {
			//$request['Version'] = array('ServiceId' => 'crs', 'Major' => '7', 'Intermediate' => '0', 'Minor' => '0');
			//$path_to_wsdl = DIR_WS_INCLUDES."wsdl/RateService_v7_test.wsdl";
		//} else {
			$path_to_wsdl = DIR_FS_CATALOG.DIR_WS_INCLUDES."wsdl/RateService_v9.wsdl";
		//}
		ini_set("soap.wsdl_cache_enabled", "0");
		$client = new SoapClient($path_to_wsdl, array('trace' => 1));
		$this->types = array();
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_INTERNATIONAL_PRIORITY == 'true') {
			$this->types[] = 'INTERNATIONAL_PRIORITY';
			$this->types[] = 'EUROPE_FIRST_INTERNATIONAL_PRIORITY';
		}
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_INTERNATIONAL_ECONOMY == 'true') {
			$this->types[] = 'INTERNATIONAL_ECONOMY';
		}
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_STANDARD_OVERNIGHT == 'true') {
			$this->types[] = 'STANDARD_OVERNIGHT';
		}
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_FIRST_OVERNIGHT == 'true') {
			$this->types[] = 'FIRST_OVERNIGHT';
		}
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_PRIORITY_OVERNIGHT == 'true') {
			$this->types[] = 'PRIORITY_OVERNIGHT';
		}
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_2DAY == 'true') {
			$this->types[] = 'FEDEX_2_DAY';
		}
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_GROUND == 'true') {
			$this->types[] = 'FEDEX_GROUND';
			$this->types[] = 'GROUND_HOME_DELIVERY';
		}
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_INTERNATIONAL_GROUND == 'true') {
			$this->types[] = 'INTERNATIONAL_GROUND';
		}
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_EXPRESS_SAVER == 'true') {
			$this->types[] = 'FEDEX_EXPRESS_SAVER';
		}
		if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_FREIGHT == 'true') {
			$this->types[] = 'FEDEX_FREIGHT';
			$this->types[] = 'FEDEX_NATIONAL_FREIGHT';
			$this->types[] = 'FEDEX_1_DAY_FREIGHT';
			$this->types[] = 'FEDEX_2_DAY_FREIGHT';
			$this->types[] = 'FEDEX_3_DAY_FREIGHT';
			$this->types[] = 'INTERNATIONAL_ECONOMY_FREIGHT';
			$this->types[] = 'INTERNATIONAL_PRIORITY_FREIGHT';
		}

		$this->types[] = 'SMART_POST';

		// customer details
		$street_address = !empty($order->delivery['street_address'])?$order->delivery['street_address']:NULL;
		$street_address2 = !empty($order->delivery['suburb'])?$order->delivery['suburb']:NULL;
		$city = !empty($order->delivery['city'])?$order->delivery['city']:NULL;
		$state = ck_address2::legacy_get_zone_field($order->delivery['country']['id'], @$order->delivery['zone_id'], '', 'zone_code');
		if ($state == "QC") $state = "PQ";
		$postcode = str_replace(array(' ', '-'), '', $order->delivery['postcode']);
		$country_id = $order->delivery['country']['iso_code_2'];

		// $totals = $order->info['subtotal'] = $_SESSION['cart']->get_total();
		$this->_setInsuranceValue(@$totals);

		$request['WebAuthenticationDetail'] = array(
			'UserCredential' => array('Key' => $this->fedex_key, 'Password' => $this->fedex_pwd)
		);
		$request['ClientDetail'] = array('AccountNumber' => $this->fedex_act_num, 'MeterNumber' => $this->fedex_meter_num);
		$request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request v9 using PHP ***');
		//$request['RequestedShipment']['SmartPostDetail'] = array(
			//'Indicia' => 'MEDIA_MAIL',
			//'AncillaryEndorsement' => 'CARRIER_LEAVE_IF_NO_RESPONSE',
			//'SpecialServices' => 'USPS_DELIVERY_CONFIRMATION',
			//'HubId' => '5254',
			//'CustomerManifestId' => 1101
		//);
		//$request['RequestedShipment']['ServiceType'] = 'SMART_POST';

		$request['Version'] = array('ServiceId' => 'crs', 'Major' => '9', 'Intermediate' => '0', 'Minor' => '0');
		$request['ReturnTransitAndCommit'] = true;
		$request['RequestedShipment']['DropoffType'] = $this->_setDropOff(); // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
		$request['RequestedShipment']['ShipTimestamp'] = date('c');
		//if (tep_not_null($method) && in_array($method, $this->types)) {
			//$request['RequestedShipment']['ServiceType'] = $method; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
		//}
		$request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
		$request['RequestedShipment']['TotalInsuredValue'] = array('Ammount'=> $this->insurance, 'Currency' => 'USD');
		$request['WebAuthenticationDetail'] = array('UserCredential' => array('Key' => $this->fedex_key, 'Password' => $this->fedex_pwd));
		$request['ClientDetail'] = array('AccountNumber' => $this->fedex_act_num, 'MeterNumber' => $this->fedex_meter_num);
		//print_r($request['WebAuthenticationDetail']);
		//print_r($request['ClientDetail']);
		//exit;
		$request['RequestedShipment']['Shipper'] = array(
			'Address' => array(
				'StreetLines' => array(MODULE_SHIPPING_FEDEX_WEB_SERVICES_ADDRESS_1, MODULE_SHIPPING_FEDEX_WEB_SERVICES_ADDRESS_2), // Origin details
				'City' => MODULE_SHIPPING_FEDEX_WEB_SERVICES_CITY,
				'StateOrProvinceCode' => MODULE_SHIPPING_FEDEX_WEB_SERVICES_STATE,
				'PostalCode' => MODULE_SHIPPING_FEDEX_WEB_SERVICES_POSTAL,
				'CountryCode' => $this->country
			)
		);
		$request['RequestedShipment']['Recipient'] = array(
			'Address' => array ( // customer info
				'StreetLines' => array($street_address, $street_address2),
				'City' => $city,
				'StateOrProvinceCode' => $state,
				'PostalCode' => $postcode,
				'CountryCode' => $country_id,
				'Residential' => !empty($order->delivery['company'])?FALSE:FALSE // this one is clearly a little wonky
			)
		);
		//print_r($request['RequestedShipment']['Recipient']) ;
		//exit;
		$request['RequestedShipment']['ShippingChargesPayment'] = array(
			'PaymentType' => 'SENDER',
			'Payor' => array(
				'AccountNumber' => $this->fedex_act_num, // Replace 'XXX' with payor's account number
				'CountryCode' => $this->country
			)
		);

		$request['RequestedShipment']['RateRequestTypes'] = 'LIST';
		$request['RequestedShipment']['PackageCount'] = $shipping_num_boxes;
		$request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
		$request['RequestedShipment']['RequestedPackageLineItems'] = array();
		if ($shipping_weight == 0) $shipping_weight = 0.1;
		for ($i=0; $i<$shipping_num_boxes; $i++) {
			$request['RequestedShipment']['RequestedPackageLineItems'][] = array('Weight' => array('Value' => $shipping_weight, 'Units' => MODULE_SHIPPING_FEDEX_WEB_SERVICES_WEIGHT));
		}
		//echo '<!-- shippingWeight: '.$shipping_weight.' '.$shipping_num_boxes.' -->';
		//echo '<!-- ';
		//echo '<pre>';
		//print_r($request);
		//echo '</pre>';
		//echo ' -->';
        $response = $client->getRates($request);
        //echo '<!-- ';
		//echo '<pre>';
		//print_r($response);
		//echo '</pre>';
		//echo ' -->';

		if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR' && !empty($response->RateReplyDetails) && (is_array($response->RateReplyDetails) || is_object($response->RateReplyDetails))) {
			if (is_object($response->RateReplyDetails)) {
				$response->RateReplyDetails = get_object_vars($response->RateReplyDetails);
			}
			//echo '<pre>';
			//print_r($response->RateReplyDetails);
			//echo '</pre>';
			switch (@SHIPPING_BOX_WEIGHT_DISPLAY) {
				case 0:
					$show_box_weight = '';
					break;
				case 1:
					$show_box_weight = ' ('.$shipping_num_boxes.' '.TEXT_SHIPPING_BOXES.')';
					break;
				case (2):
					$show_box_weight = ' ('.number_format($shipping_weight*$shipping_num_boxes,2).TEXT_SHIPPING_WEIGHT.')';
					break;
				default:
					$show_box_weight = ' ('.$shipping_num_boxes.' x '.number_format($shipping_weight,2).TEXT_SHIPPING_WEIGHT.')';
					break;
			}
			$this->quotes = array('id' => $this->code, 'module' => $this->title.$show_box_weight);

			//echo '<pre>';
			//print_r($response->RateReplyDetails);
			//echo '</pre>';

			//EXIT();

			$methods = array();
			foreach ($response->RateReplyDetails as $rateReply) {
		//echo '<pre>';
		//var_dump($rateReply);
		//echo '</pre>';
				if (in_array($rateReply->ServiceType, $this->types) && ($method == '' || $rateReply->ServiceType == $method)) {
					if (MODULE_SHIPPING_FEDEX_WEB_SERVICES_RATES == 'LIST') {
						foreach ($rateReply->RatedShipmentDetails as $ShipmentRateDetail) {
							if ($ShipmentRateDetail->ShipmentRateDetail->RateType == 'PAYOR_LIST_PACKAGE') {
								$cost = $ShipmentRateDetail->ShipmentRateDetail->TotalNetCharge->Amount;
								$cost = (float) round(preg_replace('/[^0-9.]/', '', $cost), 2);
							}
						}
					}
					else {
						$cost = $rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount;
						$cost = (float) round(preg_replace('/[^0-9.]/', '', $cost), 2);
					}

					if (strlen($this->handling_fee) > 0) {
						if (strstr($this->handling_fee,'%')) {
							$cost += $cost * (str_replace('%','',$this->handling_fee)/100);
						}
						else {
							$cost += $this->handling_fee;
						}
					}
					//Need to change this data to use the same data as the old module

					$methods[] = array(
						'id' => str_replace('_', '', $rateReply->ServiceType),
						'title' => ucwords(strtolower(str_replace('_', ' ', $rateReply->ServiceType))),
						'shipping_method_id' => '9',
						'cost' => $cost
					);
					/*
					//print $rateReply->ServiceType;

					switch (str_replace('_', '', $rateReply->ServiceType)) {
						case "FEDEXGROUND":
							$id = '92';
							$title = '9';
							$shipping_method_id = $title;
							break;
						case "FEDEXEXPRESSSAVER":
							$id = '20';
							$title = '5';
							$shipping_method_id = $title;
							break;
						case "FEDEX2DAY":
							$id = '03';
							$title = '4';
							$shipping_method_id = $title;
							break;
						case "STANDARDOVERNIGHT":
							$id = '05';
							$title = '3';
							$shipping_method_id = $title;
							break;
						case "PRIORITYOVERNIGHT":
							$id = '01';
							$title = '2';
							$shipping_method_id = $title;
							break;
						default:
							$id = '92';
							$title = '9';
							$shipping_method_id = $title;
					}

					$methods[] = array(
						'id' => $id,
						'title' => $title,
						'shipping_method_id' => $shipping_method_id,
						'cost' => $cost
					);
					*/
				}
			}

			function cmp($a, $b) {
				if ($a['cost'] == $b['cost']) {
					return 0;
				}
				return ($a['cost'] < $b['cost']) ? -1 : 1;
			}

			usort($methods, 'cmp');
			$this->quotes['methods'] = $methods;
		}
		else {
			$GLOBALS['SHIPPING_FAILOVER'][] = 'upsxmlfedexground';
			return array();
			/*
			$message = 'Error in processing transaction.<br /><br />';
			foreach ($response -> Notifications as $notification) {
				if (is_array($response->Notifications)) {
					$message .= $notification->Severity;
					$message .= ': ';
					$message .= $notification->Message.'<br />';
				}
				else {
					$message .= $notification.'<br />';
				}
			}
			$this->quotes = array('module' => $this->title, 'error' => $message);*/
		}
		if (tep_not_null($this->icon)) $this->quotes['icon'] = tep_image($this->icon, $this->title);
		//echo '<!-- Quotes: ';
		//print_r($this->quotes);
		//print_r($_SESSION['shipping']);
		//echo ' -->';
		return $this->quotes;
	}

	function _setInsuranceValue($order_amount) {
		if ($order_amount > (float) MODULE_SHIPPING_FEDEX_WEB_SERVICES_INSURE) {
			$this->insurance = sprintf("%01.2f", $order_amount);
		}
		else {
			$this->insurance = 0;
		}
	}

	function objectToArray($object) {
		if (!is_object($object) && !is_array($object)) {
			return $object;
		}
		if (is_object($object)) {
			$object = get_object_vars($object);
		}
		return array_map('objectToArray', $object);
	}

	function _setDropOff() {
		switch (MODULE_SHIPPING_FEDEX_WEB_SERVICES_DROPOFF) {
			case '1':
				return 'REGULAR_PICKUP';
				break;
			case '2':
				return 'REQUEST_COURIER';
				break;
			case '3':
				return 'DROP_BOX';
				break;
			case '4':
				return 'BUSINESS_SERVICE_CENTER';
				break;
			case '5':
				return 'STATION';
				break;
		}
	}

	function check() {
		if (!isset($this->_check)) {
			$check_query = prepared_query::fetch("SELECT configuration_value FROM configuration WHERE configuration_key = 'MODULE_SHIPPING_FEDEX_WEB_SERVICES_STATUS'");
			$this->_check = count($check_query);
		}
		return $this->_check;
	}

	function install() {
	}

	function remove() {
		prepared_query::execute("DELETE FROM configuration WHERE configuration_key in ('".implode("','", $this->keys())."')");
	}

	function keys() {
		return array(
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_STATUS',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_KEY',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_PWD',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_ACT_NUM',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_METER_NUM',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_WEIGHT',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_ADDRESS_1',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_ADDRESS_2',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_CITY',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_STATE',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_POSTAL',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_PHONE',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_DROPOFF',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_EXPRESS_SAVER',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_STANDARD_OVERNIGHT',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_FIRST_OVERNIGHT',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_PRIORITY_OVERNIGHT',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_2DAY',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_INTERNATIONAL_PRIORITY',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_INTERNATIONAL_ECONOMY',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_GROUND',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_INTERNATIONAL_GROUND',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_FREIGHT',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_TAX_CLASS',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_HANDLING_FEE',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_RATES',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_ZONE',
			'MODULE_SHIPPING_FEDEX_WEB_SERVICES_SORT_ORDER'
		);
	}

	function get_countries($countries_id = '', $with_iso_codes = false) {
		$countries_array = array();
		if (tep_not_null($countries_id)) {
			if ($with_iso_codes == true) {
				$countries_values = prepared_query::fetch("SELECT countries_name, countries_iso_code_2, countries_iso_code_3 FROM countries WHERE countries_id = :countries_id ORDER BY countries_name", cardinality::ROW, [':countries_id' => $countries_id]);
				$countries_array = array(
					'countries_name' => $countries_values['countries_name'],
					'countries_iso_code_2' => $countries_values['countries_iso_code_2'],
					'countries_iso_code_3' => $countries_values['countries_iso_code_3']
				);
			}
			else {
				$countries_values = prepared_query::fetch("SELECT countries_name FROM countries WHERE countries_id = :countries_id", cardinality::ROW, [':countries_id' => $countries_id]);
				$countries_array = array('countries_name' => $countries_values['countries_name']);
			}
		}
		else {
			$countries = prepared_query::fetch("SELECT countries_id, countries_name FROM countries ORDER BY countries_name", cardinality::SET);
			foreach ($countries as $countries_values) {
				$countries_array[] = array('countries_id' => $countries_values['countries_id'], 'countries_name' => $countries_values['countries_name']);
			}
		}

		return $countries_array;
	}
}
