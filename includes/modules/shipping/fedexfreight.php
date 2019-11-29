<?php

class fedexfreight {
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

	private $freight_cost_factor = 2; // 1.0 = full cost

	public $sources = array();
	public $exclusive = FALSE;

	//Class Constructor
	public function __construct() {
		@define('MODULE_SHIPPING_FEDEX_WEB_SERVICES_INSURE', 0);
		$this->code = "fedexfreight";
		$this->title = 'FedEx Freight';
		$this->description = MODULE_SHIPPING_FEDEX_WEB_SERVICES_TEXT_DESCRIPTION;
		$this->sort_order = NULL; // remove the sort order because we don't really need it, and otherwise it'll conflict with fedex web services
		$this->handling_fee = defined('MODULE_SHIPPING_FEDEX_FREIGHT_HANDLING_FEE')?MODULE_SHIPPING_FEDEX_FREIGHT_HANDLING_FEE:0;
		$this->icon = DIR_WS_ICONS.'shipping_fedex_ground.jpg';
		$this->enabled = MODULE_SHIPPING_FEDEX_WEB_SERVICES_STATUS=='true'?TRUE:FALSE;

		$this->tax_class = defined('MODULE_SHIPPING_FEDEX_FREIGHT_TAX_CLASS')?MODULE_SHIPPING_FEDEX_FREIGHT_TAX_CLASS:0;
		$this->fedex_key = MODULE_SHIPPING_FEDEX_WEB_SERVICES_KEY;
		$this->fedex_pwd = MODULE_SHIPPING_FEDEX_WEB_SERVICES_PWD;
		$this->fedex_act_num = MODULE_SHIPPING_FEDEX_WEB_SERVICES_ACT_NUM;
		$this->fedex_meter_num = MODULE_SHIPPING_FEDEX_WEB_SERVICES_METER_NUM;

		$this->country = 'US';
		
		// if the country isn't US, we can't freight it (right?!?!?)
		if (!empty($GLOBALS['order']) && $GLOBALS['order']->delivery['country']['id'] != '223') {
			$check_flag = FALSE;
			$this->enabled = FALSE;
			return;
		}

		// if the state/zone is in the lower 48, then we can freight it. Otherwise, nope.
		if ($this->enabled == TRUE && (int)MODULE_SHIPPING_FEDEX_WEB_SERVICES_ZONE > 0) {
			$check_flag = FALSE;
			$checks = prepared_query::fetch('SELECT zone_id FROM zones_to_geo_zones WHERE geo_zone_id = ? AND zone_country_id = ? ORDER BY zone_id', cardinality::SET, array(MODULE_SHIPPING_FEDEX_WEB_SERVICES_ZONE, @$GLOBALS['order']->delivery['country']['id']));

			foreach ($checks as $check) {
				if (($check['zone_id'] <= 1) || (empty($check['zone_id']))) {
					$check_flag = TRUE;
					break;
				}
				elseif ($check['zone_id'] == $GLOBALS['order']->delivery['zone_id']) {
					$check_flag = TRUE;
					break;
				}
				// $check->MoveNext();
			}

			if ($check_flag == FALSE) {
				$this->enabled = FALSE;
			}
		}

		// check to see that this is actually an order that needs to be shipped freight
		if ($this->enabled && !empty($_SESSION['cart']) && $_SESSION['cart']->has_freight_products()) {
			$sources = [];
			if ($products = $_SESSION['cart']->get_universal_products()) {
				foreach ($products as $product) {
					if ($product['listing']->is('freight')) {
						if ($src = prepared_query::fetch('SELECT psc.drop_ship, psc.freight, v.vendors_id, abv.entry_street_address as street, abv.entry_city as city, z.zone_code as state, abv.entry_postcode as zip, c.countries_iso_code_2 as country FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id LEFT JOIN vendors v ON psce.preferred_vendor_id = v.vendors_id LEFT JOIN address_book_vendors abv ON v.vendors_default_address_id = abv.address_book_id LEFT JOIN zones z ON abv.entry_zone_id = z.zone_id LEFT JOIN countries c ON abv.entry_country_id = c.countries_id WHERE p.products_id = :products_id', cardinality::ROW, [':products_id' => $product['products_id']])) {
							$src['weight'] = $product['listing']->get_total_weight() * $product['quantity'];
							$src['base_product'] = $product['option_type']==ck_cart::$option_types['NONE']?1:0;
							$sources[$product['products_id']] = $src;
						}
					}
				}
			}
		}
		// force freight just means if there aren't any freight items on the order, still run the freight module
		elseif (empty($GLOBALS['force_freight'])) $this->enabled = FALSE;

		// if freight rules apply, then make this an exclusive query
		if ($this->enabled) {
			$this->exclusive = TRUE;

			foreach ($sources as $source) {
				if (!empty($source['drop_ship'])) {
					if (!isset($this->sources[$source['vendors_id']])) $this->sources[$source['vendors_id']] = $source;
					else {
						$this->sources[$source['vendors_id']]['freight'] += $source['freight'];
						$this->sources[$source['vendors_id']]['count']++;
						$this->sources[$source['vendors_id']]['weight'] += $source['weight'];
						$this->sources[$source['vendors_id']]['base_product'] += $source['base_product'];
					}
				}
				else {
					// not drop ship, source from CK
					// we're recording the vendor address details from the first item, but they'll be ignored since it's not drop_ship
					if (!isset($this->sources[0])) $this->sources[0] = $source;
					else {
						$this->sources[0]['freight'] += $source['freight'];
						$this->sources[0]['count']++;
						$this->sources[0]['weight'] += $source['weight'];
						$this->sources[0]['base_product'] += $source['base_product'];
					}
				}
			}
		}
	}

	//Class Methods

	public function quote($method='') {
		/* FedEx integration starts */
		$shipping_weight = $GLOBALS['shipping_weight'];
		$shipping_num_boxes = $GLOBALS['shipping_num_boxes'];
		$order = $GLOBALS['order'];

		require_once(DIR_FS_CATALOG.DIR_WS_INCLUDES.'lib-apis/fedex-common.php5');
		$path_to_wsdl = DIR_FS_CATALOG.DIR_WS_INCLUDES."wsdl/RateService_v10.wsdl";
		ini_set("soap.wsdl_cache_enabled", "0");
		$freight_client = new SoapClient($path_to_wsdl, array('trace' => 1));
		$ground_client = new SoapClient(DIR_FS_CATALOG.DIR_WS_INCLUDES.'wsdl/RateService_v9.wsdl', array('trace' => 1));

		$this->types = array();
		//$this->types[] = 'FEDEX_FREIGHT_ECONOMY';
		$this->types[] = 'FEDEX_FREIGHT_PRIORITY';

		// customer details
		$street_address = $order->delivery['street_address'];
		$street_address2 = $order->delivery['suburb'];
		$city = $order->delivery['city']?$order->delivery['city']:'Saturn2000';
		$state = ck_address2::legacy_get_zone_field($order->delivery['country']['id'], $order->delivery['zone_id'], MODULE_SHIPPING_FEDEX_WEB_SERVICES_STATE, 'zone_code');
		if ($state == "QC") $state = "PQ";
		$postcode = str_replace(array(' ', '-'), '', $order->delivery['postcode']);
		$country_id = $order->delivery['country']['iso_code_2'];

		if ($shipping_weight == 0) $shipping_weight = 0.1;
		switch (@SHIPPING_BOX_WEIGHT_DISPLAY) {
			case 0:
				$show_box_weight = '';
				break;
			case 1:
				$show_box_weight = ' ('.$shipping_num_boxes.' '.TEXT_SHIPPING_BOXES.')';
				break;
			case 2:
				$show_box_weight = ' ('.number_format($shipping_weight*$shipping_num_boxes,2).TEXT_SHIPPING_WEIGHT.')';
				break;
			default:
				$show_box_weight = ' ('.$shipping_num_boxes.' x '.number_format($shipping_weight,2).TEXT_SHIPPING_WEIGHT.')';
				break;
		}
		$this->quotes = array('id' => $this->code, 'module' => $this->title.$show_box_weight);

		$residential = FALSE; // default to commercial, since that's the most likely type of customer we're dealing with
		$confirmed = FALSE;
		if (!empty($street_address)) {
			$address_client = new SoapClient(DIR_FS_CATALOG.DIR_WS_INCLUDES.'wsdl/AddressValidationService_v2.wsdl', array('trace' => 1));

			$address_request = array(
				'WebAuthenticationDetail' => array(
					'UserCredential' => array(
						'Key' => $this->fedex_key,
						'Password' => $this->fedex_pwd
					)
				),
				'ClientDetail' => array(
					'AccountNumber' => $this->fedex_act_num,
					'MeterNumber' => $this->fedex_meter_num
				),
				'TransactionDetail' => array('CustomerTransactionId' => ' *** Address Validation Request v2 using PHP ***'),
				'Version' => array(
					'ServiceId' => 'aval',
					'Major' => '2',
					'Intermediate' => '0',
					'Minor' => '0'
				),
				'RequestTimestamp' => date('c'),
				'Options' => array(
					'CheckResidentialStatus' => 1,
					'StreetAccuracy' => 'LOOSE',
					'DirectionalAccuracy' => 'LOOSE',
					'CompanyNameAccuracy' => 'LOOSE',
					'RecognizeAlternateCityNames' => 1,
					'ReturnParsedElements' => 1
				),
				'AddressesToValidate' => array(array(
					'Address' => array(
						'StreetLines' => array($street_address),
						'City' => $city,
						'StateorProvinceCode' => $state,
						'PostalCode' => $postcode,
						'CountryCode' => $country_id
					)
				))
			);

            $address_response = $address_client->addressValidation($address_request);

            /*echo htmlentities($address_client->__getLastRequest());
            echo '<pre>';
            print_r($address_response);
            echo '</pre>';*/

			if ($address_response->HighestSeverity != 'FAILURE' && $address_response->HighestSeverity != 'ERROR') {
				$address_results = is_array($address_response->AddressResults)?$address_response->AddressResults:[$address_response->AddressResults];
				foreach ($address_results as $addressResult) {
					$residential = TRUE;

					if (!empty($addressResult->ResidentialStatus)) {
						$residential = strtolower($addressResult->ResidentialStatus) == 'residential';
					}
					elseif (!empty($addressResult->ProposedAddressDetails) && !empty($addressResult->ProposedAddressDetails->ResidentialStatus)) {
						$residential = strtolower($addressResult->ProposedAddressDetails->ResidentialStatus) == 'residential';
					}

					$confirmed = TRUE;
				}
			}
			else {
				$message = 'Error in processing transaction.<br /><br />';
				foreach ($address_response->Notifications as $notification) {
					if (is_array($address_response->Notifications)) {
						$message .= $notification->Severity;
						$message .= ': ';
						$message .= $notification->Message.'<br />';
					}
					else {
						$message .= $notification.'<br />';
					}
				}
				$this->quotes['error'] = $message;
				$this->quotes['icon'] = '';
				return $this->quotes;
			}
		}

		if ($residential) $this->types[] = 'GROUND_HOME_DELIVERY';
		else $this->types[] = 'FEDEX_GROUND';

		$this->_setInsuranceValue(NULL); //$totals); // $totals is undefined at this point

		/*---------------------------------------------
		// start request
		---------------------------------------------*/

		$request['WebAuthenticationDetail'] = array('UserCredential' => array('Key' => $this->fedex_key, 'Password' => $this->fedex_pwd));
		$request['ClientDetail'] = array('AccountNumber' => $this->fedex_act_num, 'MeterNumber' => $this->fedex_meter_num);

		$request['ReturnTransitAndCommit'] = TRUE;
		$request['RequestedShipment']['DropoffType'] = $this->_setDropOff(); // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
		$request['RequestedShipment']['ShipTimestamp'] = date('c');

		$request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
		$request['RequestedShipment']['TotalInsuredValue'] = array('Ammount'=> $this->insurance, 'Currency' => 'USD');

		$request['RequestedShipment']['Shipper'] = array(
			/*'AccountNumber' => $this->fedex_act_num,*/
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
				'Residential' => $residential
			)
		);

		$request['RequestedShipment']['RateRequestTypes'] = 'LIST';
		$request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';

		$context = ''; // freight or ground
		$method = array();
		$total_cost = 0;

		foreach ($this->sources as $source) {
			if (!empty($source['drop_ship'])) {
				$request['RequestedShipment']['Origin'] = array(
					/*'AccountNumber' => $this->fedex_act_num,*/
					'Address' => array(
						'StreetLines' => array($source['street'], ''), // Origin details
						'City' => $source['city'],
						'StateOrProvinceCode' => $source['state'],
						'PostalCode' => $source['zip'],
						'CountryCode' => $source['country']
					)
				);
			}
			else {
				unset($request['RequestedShipment']['Origin']);
			}

			if ($source['freight'] || $source['weight'] > 300) {
				$context = 'freight';

				if (!$order->delivery['city']) {
					$request['RequestedShipment']['Recipient']['Address']['City'] = $city;
					$request['RequestedShipment']['Recipient']['Address']['StateOrProvinceCode'] = $state;
				}

				$request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request v10 using PHP ***');
				$request['Version'] = array('ServiceId' => 'crs', 'Major' => '10', 'Intermediate' => '0', 'Minor' => '0');

				$request['RequestedShipment']['PackageCount'] = $source['freight'];
				unset($request['RequestedShipment']['PackageDetail']);
				unset($request['RequestedShipment']['RequestedPackageLineItems']);

				$request['RequestedShipment']['FreightShipmentDetail'] = array(
					'FedExFreightAccountNumber' => 241484688, //285019516,
					'FedExFreightBillingContactAndAddress' => array(
						'Address' => array(
							'StreetLines' => array(MODULE_SHIPPING_FEDEX_WEB_SERVICES_ADDRESS_1, MODULE_SHIPPING_FEDEX_WEB_SERVICES_ADDRESS_2),
							'City' => MODULE_SHIPPING_FEDEX_WEB_SERVICES_CITY,
							'StateOrProvinceCode' => MODULE_SHIPPING_FEDEX_WEB_SERVICES_STATE,
							'PostalCode' => MODULE_SHIPPING_FEDEX_WEB_SERVICES_POSTAL,
							'CountryCode' => $this->country
						)
					),
					'Role' => 'SHIPPER',
					'LineItems' => array(
						'FreightClass' => 'CLASS_070',
						'Packaging' => 'BOX',
						'Weight' => array('Value' => $source['weight']/*$shipping_weight*/, 'Units' => MODULE_SHIPPING_FEDEX_WEB_SERVICES_WEIGHT)
					)
				);

				$request['RequestedShipment']['ShippingChargesPayment'] = array(
					'PaymentType' => 'SENDER',
					'Payor' => array(
						'AccountNumber' => 241484688, //285019516,// $this->fedex_act_num, // Replace 'XXX' with payor's account number
						'CountryCode' => $this->country
					)
				);
                $response = $freight_client->getRates($request);

                /*if (!empty($_GET['debug'])) {
                    echo htmlentities($freight_client->__getLastRequest());
                    echo '<pre>';
                    print_r($response);
                    echo '</pre>';
                }*/
			}
			else {
				$context = 'ground';

				if (!$order->delivery['city']) {
					unset($request['RequestedShipment']['Recipient']['Address']['City']);
					$request['RequestedShipment']['Recipient']['Address']['StateOrProvinceCode'] = '';
				}

				$request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request v9 using PHP ***');
				$request['Version'] = array('ServiceId' => 'crs', 'Major' => '9', 'Intermediate' => '0', 'Minor' => '0');

				unset($request['RequestedShipment']['FreightShipmentDetail']);
				$shipping_num_boxes = 1;

				if (SHIPPING_BOX_WEIGHT >= $source['weight']*SHIPPING_BOX_PADDING/100) {
					$source['weight'] = $source['weight']+SHIPPING_BOX_WEIGHT;
				}
				else {
					$source['weight'] = $source['weight'] + ($source['weight']*SHIPPING_BOX_PADDING/100);
				}
				if ($source['weight'] > SHIPPING_MAX_WEIGHT) { // Split into many boxes
					$shipping_num_boxes = ceil($source['weight']/SHIPPING_MAX_WEIGHT);
					$source['weight'] = $source['weight']/$shipping_num_boxes;
				}

				$request['RequestedShipment']['PackageCount'] = $shipping_num_boxes;
				$request['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
				$request['RequestedShipment']['RequestedPackageLineItems'] = array();
				if ($source['weight'] == 0) $source['weight'] = 0.1;
				for ($i=0; $i<$shipping_num_boxes; $i++) {
					$request['RequestedShipment']['RequestedPackageLineItems'][] = array('Weight' => array('Value' => $source['weight'], 'Units' => MODULE_SHIPPING_FEDEX_WEB_SERVICES_WEIGHT));
				}

				$request['RequestedShipment']['ShippingChargesPayment'] = array(
					'PaymentType' => 'SENDER',
					'Payor' => array(
						'AccountNumber' => $this->fedex_act_num, // Replace 'XXX' with payor's account number
						'CountryCode' => $this->country
					)
				);
                $response = $ground_client->getRates($request);

                /*if (!empty($_GET['debug'])) {
                    echo htmlentities($ground_client->__getLastRequest());
                    echo '<pre>';
                    print_r($response);
                    echo '</pre>';
                }*/
			}

			if (!in_array($response->HighestSeverity, array('FAILURE', 'ERROR')) && (is_array($response->RateReplyDetails) || is_object($response->RateReplyDetails))) {
				if (is_object($response->RateReplyDetails)) $response->RateReplyDetails = array($response->RateReplyDetails);

				foreach ($response->RateReplyDetails as $rateReply) {
					if (in_array($rateReply->ServiceType, $this->types)) {
						foreach ($rateReply->RatedShipmentDetails as $ShipmentRateDetail) {
							// PAYOR_LIST_SHIPMENT is the list freight cost, PAYOR_ACCOUNT_SHIPMENT is our freight cost
							if (in_array($ShipmentRateDetail->ShipmentRateDetail->RateType, array('PAYOR_ACCOUNT_SHIPMENT', 'PAYOR_LIST_PACKAGE'))) {
								$cost = $ShipmentRateDetail->ShipmentRateDetail->TotalNetCharge->Amount;
								$context=='freight'?$cost *= $this->freight_cost_factor:NULL; // if this is a freight quote, which is our cost, upcharge it.
								$cost = (float) round(preg_replace('/[^0-9.]/', '', $cost), 2);
							}
						}

						if ($this->handling_fee) {
							if (strstr($this->handling_fee, '%')) {
								$cost += $cost * (str_replace('%', '', $this->handling_fee)/100);
							}
							else {
								$cost += $this->handling_fee;
							}
						}
						$total_cost += $cost;

						if ($context == 'freight') {
							// at least one of the requests will be a freight request, and we're writing most of the details here (cost will come at the end)
							$method = array(
								'id' => str_replace('_', '', $rateReply->ServiceType),
								'title' => ucwords(strtolower(str_replace('_', ' ', $rateReply->ServiceType))),
								'shipping_method_id' => '50'
							);
						}
					}
					else {
						/*if (!empty($_GET['debug'])) {
							echo $rateReply->ServiceType.'<br>';
						}*/
						// skip it
					}
				}

				// the whole point is that we're only showing one method
				//usort($methods, function ($a, $b) { if ($a['cost'] == $b['cost']) { return 0; } return ($a['cost']<$b['cost'])?-1:1; });
				//$this->quotes['methods'] = $methods;
			}
			else {
				$message = 'Error in processing transaction.<br /><br />';
				if (is_array($response->Notifications)) {
					foreach ($response->Notifications as $notification) {
						$message .= $notification->Severity;
						$message .= ': ';
						$message .= $notification->Message.'<br />';
					}
				}
				elseif (is_object($response->Notifications)) {
					$message .= $response->Notifications->Severity;
					$message .= ': ';
					$message .= $response->Notifications->Message.'<br />';
				}
				else {
					$message .= $notification.'<br />';
				}
				$this->quotes['error'] = $message;
				break;
			}
		}
		$method['cost'] = $total_cost;
		$this->quotes['methods'] = array($method);
		$this->quotes['verified_address'] = $street_address?TRUE:FALSE;
		$this->quotes['residential'] = $residential;
		$this->quotes['confirmed'] = $confirmed;

		if (tep_not_null($this->icon)) $this->quotes['icon'] = '';//tep_image($this->icon, $this->title);
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
		prepared_query::exeucte("DELETE FROM configuration WHERE configuration_key in ('".implode("','", $this->keys())."')");
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
}
