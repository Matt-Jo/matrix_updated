<?php
require ('includes/classes/xmldocument.php');

class iux {
	var $code, $title, $description, $icon, $enabled, $types, $boxcount;

	//***************
	function __construct() {
		global $order;
		$this->code = 'iux';
		$this->title = 'International UPS';
		$this->description = 'International UPS';
		$this->sort_order = 8;
		$this->icon = DIR_WS_ICONS.'shipping_ups.jpg';
		$this->tax_class = 0;
		$this->enabled = TRUE;
		$this->access_key = 'BBD3FF207D79AB5C';
		$this->access_username = 'zboyblue';
		$this->access_password = 'DanielisAwesome2016';
		$this->origin = 'US Origin';
		$this->origin_city = 'Buford';
		$this->origin_stateprov = 'GA';
		$this->origin_country = 'US';
		$this->origin_postalcode = '30518';
		$this->pickup_method = 'Daily Pickup';
		$this->package_type = 'Customer Package';
		$this->unit_weight = 'LBS';
		$this->unit_length = 'IN';
		$this->handling_fee = 0;
		$this->quote_type = 'Commercial';
		$this->customer_classification = '01';
		$this->protocol = 'https';
		$this->host = defined('MODULE_SHIPPING_IUX_RATES_TEST_MODE')&&MODULE_SHIPPING_IUX_RATES_TEST_MODE=='Test'?'wwwcie.ups.com':'onlinetools.ups.com';
		$this->port = '443';
		$this->path = '/ups.app/xml/Rate';
		$this->transitpath = '/ups.app/xml/TimeInTransit';
		$this->version = 'IUX Rate 1.0001';
		$this->transitversion = 'IUX Time In Transit 1.0001';
		$this->timeout = '60';
		$this->xpci_version = '1.0001';
		$this->transitxpci_version = '1.0001';
		$this->items_qty = 0;
		$this->timeintransit = '0';
		$this->today = date("Ymd");

		$this->sm_map = array(
			'UPS Next Day Air Early A.M.' => 17,
			'UPS Next Day Air' => 18,
			'UPS Next Day Air Saver' => 19,
			'UPS 2nd Day Air AM' => 20,
			'UPS 2nd Day Air' => 21,
			'UPS 3 Day Select' => 22,
			'UPS Ground' => 23,
			'UPS Worldwide Express Plus' => 25,
			'UPS Worldwide Express' => 26,
			'UPS Express Saver' => 27,
			'UPS Worldwide Expedited' => 28,
			'UPS Standard' => 29,
			'UPS Saturday Delivery (Next Day Air)' => 64,
			'UPS Saturday Delivery (2nd Day Air)' => 65
		);

		// insurace addition
		//if (MODULE_SHIPPING_IUX_INSURE == 'False')
		$this->pkgvalue = 100;
		//if (MODULE_SHIPPING_IUX_INSURE == 'True') $this->pkgvalue = ceil($order->info['subtotal']);
		// end insurance addition

		// to enable logging, create an empty "iux.log" file at the location you set below, give it write permissions (777) and uncomment the next line
		///	$this->logfile = '/tmp/iux.log';

		// when cURL is not compiled into PHP (Windows users, some Linux users)
		// you can set the next variable to "1" and then exec(curl -d $xmlRequest, $xmlResponse)
		// will be used
		$this->use_exec = '0';

		if (!$this->enabled && !empty($_GET['oid']) && strpos($_SERVER['REQUEST_URI'], 'admin')) {
			$myOrder = new ck_sales_order($_GET['oid']);
			$address = $myOrder->get_ship_address();
			$myCust = $myOrder->get_customer();
			if (strlen(trim($myCust->get_header('ups_account_number'))) > 0 && $myCust->is('own_shipping_account') && $address->is_international()) {
				$this->enabled = true;
			}
		}

		// Available pickup types - set in admin
		$this->pickup_methods = [
			'Daily Pickup' => '01',
			'Customer Counter' => '03',
			'One Time Pickup' => '06',
			'On Call Air Pickup' => '07',
			'Letter Center' => '09',
			'Air Service Center' => '10'
		];

		// Available package types
		$this->package_types = [
			'Unknown' => '00',
			'UPS Letter' => '01',
			'Customer Package' => '02',
			'UPS Tube' => '03',
			'UPS Pak' => '04',
			'UPS Express Box' => '21',
			'UPS 25kg Box' => '24',
			'UPS 10kg Box' => '25'
		];

		// Human-readable Service Code lookup table. The values returned by the Rates and Service "shop" method are numeric.
		// Using these codes, and the admininstratively defined Origin, the proper human-readable service name is returned.
		// Note: The origin specified in the admin configuration affects only the product name as displayed to the user.
		$this->service_codes = [
			// US Origin
			'US Origin' => [
				'01' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_01,
				'02' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_02,
				'03' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_03,
				'07' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_07,
				'08' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_08,
				'11' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_11,
				'12' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_12,
				'13' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_13,
				'14' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_14,
				'54' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_54,
				'59' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_59,
				'65' => MODULE_SHIPPING_IUX_SERVICE_CODE_US_ORIGIN_65
			],
			// Canada Origin
			'Canada Origin' => [
				'01' => MODULE_SHIPPING_IUX_SERVICE_CODE_CANADA_ORIGIN_01,
				'07' => MODULE_SHIPPING_IUX_SERVICE_CODE_CANADA_ORIGIN_07,
				'08' => MODULE_SHIPPING_IUX_SERVICE_CODE_CANADA_ORIGIN_08,
				'11' => MODULE_SHIPPING_IUX_SERVICE_CODE_CANADA_ORIGIN_11,
				'12' => MODULE_SHIPPING_IUX_SERVICE_CODE_CANADA_ORIGIN_12,
				'13' => MODULE_SHIPPING_IUX_SERVICE_CODE_CANADA_ORIGIN_13,
				'14' => MODULE_SHIPPING_IUX_SERVICE_CODE_CANADA_ORIGIN_14,
				'54' => MODULE_SHIPPING_IUX_SERVICE_CODE_CANADA_ORIGIN_54
			],
			// European Union Origin
			'European Union Origin' => [
				'07' => MODULE_SHIPPING_IUX_SERVICE_CODE_EU_ORIGIN_07,
				'08' => MODULE_SHIPPING_IUX_SERVICE_CODE_EU_ORIGIN_08,
				'11' => MODULE_SHIPPING_IUX_SERVICE_CODE_EU_ORIGIN_11,
				'54' => MODULE_SHIPPING_IUX_SERVICE_CODE_EU_ORIGIN_54,
				'64' => @MODULE_SHIPPING_IUX_SERVICE_CODE_EU_ORIGIN_64, // missing, don't know what it is
				'65' => MODULE_SHIPPING_IUX_SERVICE_CODE_EU_ORIGIN_65
			],
			// Puerto Rico Origin
			'Puerto Rico Origin' => [
				'01' => MODULE_SHIPPING_IUX_SERVICE_CODE_PR_ORIGIN_01,
				'02' => MODULE_SHIPPING_IUX_SERVICE_CODE_PR_ORIGIN_02,
				'03' => MODULE_SHIPPING_IUX_SERVICE_CODE_PR_ORIGIN_03,
				'07' => MODULE_SHIPPING_IUX_SERVICE_CODE_PR_ORIGIN_07,
				'08' => MODULE_SHIPPING_IUX_SERVICE_CODE_PR_ORIGIN_08,
				'14' => MODULE_SHIPPING_IUX_SERVICE_CODE_PR_ORIGIN_14,
				'54' => MODULE_SHIPPING_IUX_SERVICE_CODE_PR_ORIGIN_54
			],
			// Mexico Origin
			'Mexico Origin' => [
				'07' => MODULE_SHIPPING_IUX_SERVICE_CODE_MEXICO_ORIGIN_07,
				'08' => MODULE_SHIPPING_IUX_SERVICE_CODE_MEXICO_ORIGIN_08,
				'54' => MODULE_SHIPPING_IUX_SERVICE_CODE_MEXICO_ORIGIN_54
			],
			// All other origins
			'All other origins' => [
				'07' => MODULE_SHIPPING_IUX_SERVICE_CODE_OTHER_ORIGIN_07,
				'08' => MODULE_SHIPPING_IUX_SERVICE_CODE_OTHER_ORIGIN_08,
				'54' => MODULE_SHIPPING_IUX_SERVICE_CODE_OTHER_ORIGIN_54
			]
		];
	}

	// class methods
	function quote($method = '') {
		global $format_shipping, $order, $shipping_weight, $shipping_num_boxes, $total_weight, $boxcount, $origin, $box_length, $box_width, $box_height;
		// UPS purports that if the origin is left out, it defaults to the account's location. Yeah, right.
		$state = prepared_query::fetch('SELECT zone_code FROM zones WHERE zone_name = :state', cardinality::SINGLE, [':state' => $order->delivery['state']]);
		$state = strtoupper($state);

		if (!empty($origin)) {
			$from_state = prepared_query::fetch('SELECT zone_code FROM zones WHERE zone_name = :state', cardinality::SINGLE, [':state' => $origin->delivery['state']]);
			$this->_upsOrigin($origin->delivery['city'], $from_state, $origin->delivery['country']['iso_code_2'], $origin->delivery['postcode']);
		}
		else {
			$this->_upsOrigin('Buford', 'GA', 'US', '30518');
		}

		$this->_upsDest($order->delivery['street_address'], $order->delivery['suburb'], $order->delivery['city'], $state, $order->delivery['country']['iso_code_2'], $order->delivery['postcode']);

		$se_cart = !empty($_SESSION['cart'])?$_SESSION['cart']:(!empty($GLOBALS['cart'])?$GLOBALS['cart']:NULL);
		if (!empty($se_cart)) $productsArray = $se_cart->get_universal_products();
		else {
			$productsArray = [];
			foreach ($GLOBALS['se_order']->get_products() as $product) {
				if (empty($productsArray[$product['products_id']])) $productsArray[$product['products_id']] = ['products_id' => $product['products_id'], 'listing' => $product['listing'], 'quantity' => 0, 'option_type' => ck_cart::$option_types['NONE']];
				$productsArray[$product['products_id']]['quantity'] += $product['quantity'];
				$productsArray[$product['products_id']]['option_type'] = min($productsArray[$product['products_id']]['option_type'], $product['option_type']);
			}
		}

		// The old method. Let osCommerce tell us how many boxes, plus the weight of each (or total? - might be sw/num boxes)
		$this->items_qty = 0; //reset quantities
		for ($i = 0; $i < $shipping_num_boxes; $i++) {
			if (!empty($box_width) && !empty($box_length) && !empty($box_height)) $this->_addItem($box_length, $box_width, $box_height, $shipping_weight);
			else $this->_addItem(0, 0, 0, $shipping_weight);
		}
	
		// BOF Time In Transit: comment out this section if you don't want/need to have
		// expected delivery dates
		$this->servicesTimeintransit = $this->_upsGetTimeServices();
		if (!empty($this->logfile)) {
			error_log("------------------------------------------\n", 3, $this->logfile);
			error_log("Time in Transit: ".$this->timeintransit."\n", 3, $this->logfile);
		}
		// EOF Time In Transit

		$upsQuote = $this->_upsGetQuote();
		if ((is_array($upsQuote)) && (sizeof($upsQuote) > 0)) {
			$this->quotes = array('id' => $this->code, 'module' => '<hr>'.$this->title.' ('.$shipping_num_boxes.($shipping_num_boxes > 1 ? ' pkgs, ' : ' pkg, ').$shipping_weight.' '.strtolower($this->unit_weight).')');

			$methods = array();
			foreach ($upsQuote as $quote) {
				// BOF limit choices
				if (!exclude_choices($quote['service']['name'])) continue;
				// EOF limit choices
				if (empty($method) || $method == $quote['service']['name']) {
					unset($_SESSION['sm_cmmt']);

					$cost_multiplier = 1;

					$cost = $this->handling_fee + ($cost_multiplier * $quote['price']);

					if (@$order->delivery['country']['iso_code_2'] != 'US') {

						$cost = $this->handling_fee + ($quote['cost'] + (.1 * $quote['cost']));
					}

					$methods[] = [
						'id' => $quote['service']['name'],
						'title' => '',
						'shipping_method_id' => $this->sm_map[$quote['service']['name']],
						'cost' => $cost,
						'negotiated_rate' => $quote['cost']
					];
				}
			}
			$this->quotes['methods'] = $methods;
		}
		else {
			if ( $upsQuote != false ) {
				$errmsg = $upsQuote;
			}
			else {
				$errmsg = MODULE_SHIPPING_IUX_RATES_TEXT_UNKNOWN_ERROR;
			}
			$errmsg .= '<br>If you need help to complete this order, please contact <a href="mailto:sales@cablesandkits.com"><u>sales@cablesandkits.com</u></a> and we will be glad to help you out.';
			$this->quotes = array('module' => $this->title, 'error' => $errmsg);
		}
		if (tep_not_null($this->icon)) {
			$this->quotes['icon'] = tep_image($this->icon, $this->title,'','','align="absmiddle"');
		}
		return $this->quotes;
	}

	//**************
	function check() {
		if (!isset($this->_check)) {
			$check_query = prepared_query::fetch("select configuration_value from configuration where configuration_key = 'MODULE_SHIPPING_IUX_RATES_STATUS'");
			$this->_check = count($check_query);
		}
		return $this->_check;
	}

	//**************
	function install() {
	}

	//****************
	function remove() {
		prepared_query::execute("delete from configuration where configuration_key in ('".implode("', '", $this->keys())."')");
	}

	//*************
	function keys() {
		// add MODULE_SHIPPING_IUX_TYPES to end of array for selectable shipping methods
		return array('MODULE_SHIPPING_IUX_RATES_STATUS', 'MODULE_SHIPPING_IUX_RATES_ACCESS_KEY', 'MODULE_SHIPPING_IUX_RATES_USERNAME', 'MODULE_SHIPPING_IUX_RATES_PASSWORD', 'MODULE_SHIPPING_IUX_RATES_PICKUP_METHOD', 'MODULE_SHIPPING_IUX_RATES_PACKAGE_TYPE', 'MODULE_SHIPPING_IUX_RATES_CUSTOMER_CLASSIFICATION_CODE', 'MODULE_SHIPPING_IUX_RATES_ORIGIN', 'MODULE_SHIPPING_IUX_RATES_CITY', 'MODULE_SHIPPING_IUX_RATES_STATEPROV', 'MODULE_SHIPPING_IUX_RATES_COUNTRY', 'MODULE_SHIPPING_IUX_RATES_POSTALCODE', 'MODULE_SHIPPING_IUX_RATES_MODE', 'MODULE_SHIPPING_IUX_RATES_UNIT_WEIGHT', 'MODULE_SHIPPING_IUX_RATES_UNIT_LENGTH', 'MODULE_SHIPPING_IUX_RATES_QUOTE_TYPE', 'MODULE_SHIPPING_IUX_RATES_HANDLING', 'MODULE_SHIPPING_IUX_INSURE', 'MODULE_SHIPPING_IUX_CURRENCY_CODE','MODULE_SHIPPING_IUX_RATES_TAX_CLASS', 'MODULE_SHIPPING_IUX_RATES_ZONE', 'MODULE_SHIPPING_IUX_RATES_SORT_ORDER', 'MODULE_SHIPPING_IUX_TYPES', 'IUX_SHIPPING_DAYS_DELAY');
	}

	//**********************************************
	function _upsOrigin($city, $stateprov, $country, $postal) {
		$this->_upsOriginCity = $city;
		$this->_upsOriginStateProv = $stateprov;
		$this->_upsOriginCountryCode = $country;
		$postal = str_replace(' ', '', $postal);
		if ($country == 'US') {
			$this->_upsOriginPostalCode = substr($postal, 0, 5);
		} else {
			$this->_upsOriginPostalCode = $postal;
		}
	}

	//**********************************************
	function _upsDest($street1, $street2, $city, $stateprov, $country, $postal) {
		$this->_upsDestAddressLine1 = $street1;
		!empty($street2)?$this->_upsDestAddressLine2=$street2:NULL;
		$this->_upsDestCity = $city;
		$this->_upsDestStateProv = $stateprov;
		$this->_upsDestCountryCode = $country;
		$postal = str_replace(' ', '', $postal);
		if ($country == 'US') {
			$this->_upsDestPostalCode = substr($postal, 0, 5);
		} else {
			$this->_upsDestPostalCode = $postal;
		}
	}

	//********************************************
	function _addItem($length, $width, $height, $weight) {
		// Add box or item to shipment list. Round weights to 1 decimal places.
		if ((float)$weight < 1.0) $weight = 1;
		else $weight = round($weight, 1);

		$index = $this->items_qty;
		$this->item_length[$index] = ($length ? (string)$length : '0' );
		$this->item_width[$index] = ($width ? (string)$width : '0' );
		$this->item_height[$index] = ($height ? (string)$height : '0' );
		$this->item_weight[$index] = ($weight ? (string)$weight : '0' );
		$this->items_qty++;
	}

	//*********************
	function _upsGetQuote() {

		// Create the access request
		$accessRequestHeader =
		"<?xml version=\"1.0\"?>\n".
		"<AccessRequest xml:lang=\"en-US\">\n".
		"	<AccessLicenseNumber>". $this->access_key ."</AccessLicenseNumber>\n".
		"	<UserId>". $this->access_username ."</UserId>\n".
		"	<Password>". $this->access_password ."</Password>\n".
		"</AccessRequest>\n";

		$ratingServiceSelectionRequestHeader =
		"<?xml version=\"1.0\"?>\n".
		"<RatingServiceSelectionRequest xml:lang=\"en-US\">\n".
		"	<Request>\n".
		"		<TransactionReference>\n".
		"			<CustomerContext>Rating and Service</CustomerContext>\n".
		"			<XpciVersion>". $this->xpci_version ."</XpciVersion>\n".
		"		</TransactionReference>\n".
		"		<RequestAction>Rate</RequestAction>\n".
		"		<RequestOption>shop</RequestOption>\n".
		"	</Request>\n".
		"	<PickupType>\n".
		"		<Code>". $this->pickup_methods[$this->pickup_method] ."</Code>\n".
		"		<Description/>\n".
		"	</PickupType>\n".
		"	<CustomerClassification>\n".
		"		<Code>". $this->customer_classification ."</Code>\n".
		"	</CustomerClassification>\n".
		"	<Shipment>\n".
		//"		<Service>\n".
		//"			<Code>03</Code>\n".
		//"			<Description/>\n".
		//"		</Service>\n".
		"		<Shipper>\n".
		"			<ShipperNumber>y2e712</ShipperNumber>\n". //724tt9
		"			<Address>\n".
		"				<City>". $this->_upsOriginCity ."</City>\n".
		"				<StateProvinceCode>". $this->_upsOriginStateProv ."</StateProvinceCode>\n".
		"				<PostalCode>". $this->_upsOriginPostalCode ."</PostalCode>\n".
		"				<CountryCode>". $this->_upsOriginCountryCode ."</CountryCode>\n".
		"			</Address>\n".
		"		</Shipper>\n".
		"		<ShipFrom>\n".
		"			<CompanyName>CablesAndKits</CompanyName>\n".
		"			<Address>\n".
		"				<AddressLine1>4555 Atwater Ct</AddressLine1>\n".
		"				<City>Buford</City>\n".
		"				<StateProvinceCode>GA</StateProvinceCode>\n".
		"				<PostalCode>30518</PostalCode>\n".
		"				<CountryCode>US</CountryCode>\n".
		"			</Address>\n".
		"		</ShipFrom>\n".
		"		<ShipTo>\n".
		"			<CompanyName/>\n".
		"			<AttentionName/>\n".
		"			<Address>\n".
		"				<AddressLine1>".$this->_upsDestAddressLine1."</AddressLine1>\n".
		(!empty($this->_upsDestAddressLine2)?"<AddressLine>".$this->_upsDestAddressLine2."</AddressLine>\n":'').
		"				<City>". $this->_upsDestCity ."</City>\n".
		(!empty($this->_upsDestStateProv)?"				<StateProvinceCode>". $this->_upsDestStateProv ."</StateProvinceCode>\n":'').
		"				<PostalCode>". $this->_upsDestPostalCode ."</PostalCode>\n".
		"				<CountryCode>". $this->_upsDestCountryCode ."</CountryCode>\n".
		/*($this->quote_type == "Residential" ? "				<ResidentialAddressIndicator/>\n" : "") .*/
		"			</Address>\n".
		"		</ShipTo>\n".
		"		<RateInformation>\n".
		"			<NegotiatedRatesIndicator/>\n".
		"		</RateInformation>\n";

		$ratingServiceSelectionRequestPackageContent = '';
		for ($i = 0; $i < $this->items_qty; $i++) {

			$ratingServiceSelectionRequestPackageContent .=
			"		<Package>\n".
			"			<PackagingType>\n".
			"				<Code>". $this->package_types[$this->package_type] ."</Code>\n".
			"				<Description/>\n".
			"			</PackagingType>\n";

			if ($this->item_length[$i] > 0 && $this->item_width[$i] > 0 && $this->item_height[$i] > 0) {
				$ratingServiceSelectionRequestPackageContent .=
				"			<Dimensions>\n".
				"				<UnitOfMeasurement>\n".
				"					<Code>". $this->unit_length ."</Code>\n".
				"				</UnitOfMeasurement>\n".
				"				<Length>". $this->item_length[$i] ."</Length>\n".
				"				<Width>". $this->item_width[$i] ."</Width>\n".
				"				<Height>". $this->item_height[$i] ."</Height>\n".
				"			</Dimensions>\n";
			}
			$ratingServiceSelectionRequestPackageContent .=
			"			<PackageWeight>\n".
			"				<Weight>". $this->item_weight[$i] ."</Weight>\n".
			"				<UnitOfMeasurement>\n".
			"					<Code>". $this->unit_weight ."</Code>\n".
			"					<Description/>\n".
			"				</UnitOfMeasurement>\n".
			"			</PackageWeight>\n".
			//"			<PackageServiceOptions>\n".
			//"				<COD>\n".
			//"					<CODFundsCode>0</CODFundsCode>\n".
			//"					<CODCode>3</CODCode>\n".
			//"					<CODAmount>\n".
			//"						<CurrencyCode>USD</CurrencyCode>\n".
			//"						<MonetaryValue>1000</MonetaryValue>\n".
			//"					</CODAmount>\n".
			//"				</COD>\n".
			//"				<InsuredValue>\n".
			//"					<CurrencyCode>".MODULE_SHIPPING_IUX_CURRENCY_CODE."</CurrencyCode>\n".
			//"					<MonetaryValue>".$this->pkgvalue."</MonetaryValue>\n".
			//"				</InsuredValue>\n".
			//"			</PackageServiceOptions>\n".
			"		</Package>\n";
		}

		$ratingServiceSelectionRequestFooter =
		//"	<ShipmentServiceOptions/>\n".
		"	</Shipment>\n".
		"</RatingServiceSelectionRequest>\n";

		$xmlRequest1 = $accessRequestHeader .
		$ratingServiceSelectionRequestHeader .
		$ratingServiceSelectionRequestPackageContent .
		$ratingServiceSelectionRequestFooter;

		//post request $strXML;
		$xmlResult = $this->_post($this->protocol, $this->host, $this->port, $this->path, $this->version, $this->timeout, $xmlRequest1);
		$xr = $this->_simpleParseResult($xmlResult);

		//var_dump($xr);

		if (is_thursday() || is_friday()) {
			$ratingServiceSelectionRequestFooter = "<ShipmentServiceOptions>\n<SaturdayDeliveryIndicator/>\n</ShipmentServiceOptions>\n".$ratingServiceSelectionRequestFooter;

			$xmlRequest2 = $accessRequestHeader .
			$ratingServiceSelectionRequestHeader .
			$ratingServiceSelectionRequestPackageContent .
			$ratingServiceSelectionRequestFooter;

			$xmlResult = $this->_post($this->protocol, $this->host, $this->port, $this->path, $this->version, $this->timeout, $xmlRequest2);
			$xr2 = $this->_simpleParseResult($xmlResult);

			//var_dump($xr2);

			if (is_array($xr2)) {
				foreach ($xr2 as &$rate) {
					$rate['service']['name'] = 'UPS Saturday Delivery ('.preg_replace('/^UPS\s*/', '', $rate['service']['name']).')';
				}

				$xr = array_merge($xr, $xr2);
			}
		}

		if (is_array($xr)) usort($xr, [$this, 'sortRates']);
		return $xr;
		//return $this->_parseResult($xmlResult);
	}

	//******************************************************************
	function _post($protocol, $host, $port, $path, $version, $timeout, $xmlRequest) {
		$url = $protocol."://".$host.":".$port.$path;
		if (!empty($this->logfile)) {
			error_log("------------------------------------------\n", 3, $this->logfile);
			error_log("DATE AND TIME: ".date('Y-m-d H:i:s')."\n", 3, $this->logfile);
			error_log("UPS URL: ".$url."\n", 3, $this->logfile);
		}
		if (function_exists('exec') && $this->use_exec == '1' ) {
			exec('which curl', $curl_output);
			if (!empty($curl_output)) {
				$curl_path = $curl_output[0];
			}
			else {
				$curl_path = 'curl'; // change this if necessary
			}
			if (!empty($this->logfile)) {
				error_log("UPS REQUEST using exec(): ".$xmlRequest."\n", 3, $this->logfile);
			}

			// add option -k to the statement: $command = "".$curl_path." -k -d \"". etcetera if you get
			// curl error 60: error setting certificate verify locations
			// using addslashes was the only way to avoid UPS returning the 1001 error: The XML document is not well formed
			$command = "".$curl_path." -d \"".addslashes($xmlRequest)."\" ".$url."";
			exec($command, $xmlResponse);
			if ( empty($xmlResponse) && !empty($this->logfile)) { // using exec no curl errors can be retrieved
				error_log("Error from cURL using exec() since there is no \$xmlResponse\n", 3, $this->logfile);
			}
			if (!empty($this->logfile)) {
				error_log("UPS RESPONSE using exec(): ".$xmlResponse[0]."\n", 3, $this->logfile);
			}
		}
		elseif ($this->use_exec == '1') { // if NOT (function_exists('exec') && $this->use_exec == '1'
			if (!empty($this->logfile)) {
				error_log("Sorry, exec() cannot be called\n", 3, $this->logfile);
			}
		}
		else { // default behavior: cURL is assumed to be compiled in PHP
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			// uncomment the next line if you get curl error 60: error setting certificate verify locations
			// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			// uncommenting the next line is most likely not necessary in case of error 60
			// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
			curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);

			if (!empty($this->logfile)) {
				error_log("UPS REQUEST: ".$xmlRequest."\n", 3, $this->logfile);
			}
			$xmlResponse = curl_exec ($ch);
			if (curl_errno($ch) && !empty($this->logfile)) {
				$error_from_curl = sprintf('Error [%d]: %s', curl_errno($ch), curl_error($ch));
				error_log("Error from cURL: ".$error_from_curl."\n", 3, $this->logfile);
			}
			if (!empty($this->logfile)) {
				error_log("UPS RESPONSE: ".$xmlResponse."\n", 3, $this->logfile);
			}
			curl_close ($ch);
		}

		if (!$xmlResponse) {
			$xmlResponse = "<?xml version=\"1.0\"?>\n".
			"<RatingServiceSelectionResponse>\n".
			"	<Response>\n".
			"		<TransactionReference>\n".
			"			<CustomerContext>Rating and Service</CustomerContext>\n".
			"			<XpciVersion>1.0001</XpciVersion>\n".
			"		</TransactionReference>\n".
			"		<ResponseStatusCode>0</ResponseStatusCode>\n".
			"		<ResponseStatusDescription>". MODULE_SHIPPING_IUX_RATES_TEXT_COMM_UNKNOWN_ERROR ."</ResponseStatusDescription>\n".
			"	</Response>\n".
			"</RatingServiceSelectionResponse>\n";
			return $xmlResponse;
		}
		if ($this->use_exec == '1') {
			return $xmlResponse[0]; // $xmlResponse is an array in this case
		}
		else {
			return $xmlResponse;
		}
	}

	function _simpleParseResult($xmlResult) {
		try {
			$result = new SimpleXMLElement($xmlResult);
		}
		catch (Exception $e) {
			return 'Error connecting to UPS; please try again.';
		}

		$rates = [];

		if ($result->Response->TransactionReference->XpciVersion != $this->xpci_version) {
			return MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_VERSION_ERROR;
		}

		if ($result->Response->ResponseStatusCode != 1) {
			return $result->Response->Error->ErrorCode.': '.$result->Response->Error->ErrorDescription;
		}

		foreach ($result->RatedShipment as $rate) {
			if (empty($rate->Service->Code)) continue;
			if (empty($rate->TotalCharges->MonetaryValue)) continue;

			if (empty($rate->NegotiatedRates)) {
				return 'There was a problem retrieving a correct rate quote for shipping to your address; please double check your address details.';
			}

			$rt = [
				'service' => [
					'code' => $rate->Service->Code->__toString(),
					'name' => $this->service_codes[$this->origin][$rate->Service->Code->__toString()]
				],
				'price' => $rate->TotalCharges->MonetaryValue->__toString(), //
				'cost' => !empty($rate->NegotiatedRates)?$rate->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue->__toString():$rate->TotalCharges->MonetaryValue->__toString(), //
				'rated_weight' => $rate->BillingWeight->Weight->__toString(),
				'schedule' => [
					'guaranteed_days_to_delivery' => $rate->GuaranteedDaysToDelivery->__toString(),
					'scheduled_delivery_time' => $rate->ScheduledDeliveryTime->__toString()
				]
			];

			$rates[] = $rt;
		}

		return $rates;
	}

	function sortRates($a, $b) {
		$a_gdtv = empty($a['schedule']['guaranteed_days_to_delivery'])?9999:$a['schedule']['guaranteed_days_to_delivery'];
		$b_gdtv = empty($b['schedule']['guaranteed_days_to_delivery'])?9999:$b['schedule']['guaranteed_days_to_delivery'];
		if ($a_gdtv == $b_gdtv) {
			if ($a['price']+0 > $b['price']+0) {
				//var_dump([$a['price']+0, $b['price']+0, -1]);
				return -1;
			}
			elseif ($a['price']+0 < $b['price']+0) {
				//var_dump([$a['price']+0, $b['price']+0, 1]);
				return 1;
			}
			else {
				//var_dump([$a['price']+0, $b['price']+0, 0]);
				return 0;
			}
		}
		elseif ($a_gdtv < $b_gdtv) {
			return -1;
		}
		else {
			return 1;
		}
	}

	//*****************************
	function _parseResult($xmlResult) {
		// Parse XML message returned by the UPS post server.
		$doc = new XMLDocument();
		$xp = new XMLParser();
		$xp->setDocument($doc);
		$xp->parse($xmlResult);
		$doc = $xp->getDocument();
		// Get version. Must be xpci version 1.0001 or this might not work.
		$responseVersion = $doc->getValueByPath('RatingServiceSelectionResponse/Response/TransactionReference/XpciVersion');
		if ($this->xpci_version != $responseVersion) {
			$message = MODULE_SHIPPING_IUX_RATES_TEXT_COMM_VERSION_ERROR;
			return $message;
		}
		// Get response code. 1 = SUCCESS, 0 = FAIL
		$responseStatusCode = $doc->getValueByPath('RatingServiceSelectionResponse/Response/ResponseStatusCode');
		if ($responseStatusCode != '1') {
			$errorMsg = $doc->getValueByPath('RatingServiceSelectionResponse/Response/Error/ErrorCode');
			$errorMsg .= ": ";
			$errorMsg .= $doc->getValueByPath('RatingServiceSelectionResponse/Response/Error/ErrorDescription');
			return $errorMsg;
		}
		$root = $doc->getRoot();
		$ratedShipments = $root->getElementsByName("RatedShipment");
		$aryProducts = false;
		for ($i = 0; $i < count($ratedShipments); $i++) {
			$serviceCode = $ratedShipments[$i]->getValueByPath("/Service/Code");
			$totalCharge = $ratedShipments[$i]->getValueByPath("/TotalCharges/MonetaryValue");
			if (!($serviceCode && $totalCharge)) {
				continue;
			}
			$ratedPackages = $ratedShipments[$i]->getElementsByName("RatedPackage");
			$this->boxCount = count($ratedPackages);
			$gdaysToDelivery = $ratedShipments[$i]->getValueByPath("/GuaranteedDaysToDelivery");
			$scheduledTime = $ratedShipments[$i]->getValueByPath("/ScheduledDeliveryTime");
			$title = '';
			$title = $this->service_codes[$this->origin][$serviceCode];

				/* we don't want to use this, it may conflict with time estimation
			if (!empty($gdaysToDelivery)) {
				$title .= ' (';
				$title .= $gdaysToDelivery." Business Days";
				if (!empty($scheduledTime)) {
					$title .= ' @ '.$scheduledTime;
				}
				$title .= ')';
			} elseif ($this->timeintransit > 0) {
				$title .= ' (';
				$title .= $this->timeintransit." Business Days";
				$title .= ')';
			}
				*/
			$aryProducts[$i] = array($title => $totalCharge);
		}
		return $aryProducts;
	}

	//********************
	function _upsGetTimeServices() {

		if (defined('IUX_SHIPPING_DAYS_DELAY')) {
			$shipdate = date("Ymd", time()+(86400*IUX_SHIPPING_DAYS_DELAY));
		}
		else {
			$shipdate = $this->today;
		}

		// Create the access request
		$accessRequestHeader =
		"<?xml version=\"1.0\"?>\n".
		"<AccessRequest xml:lang=\"en-US\">\n".
		"	<AccessLicenseNumber>". $this->access_key ."</AccessLicenseNumber>\n".
		"	<UserId>". $this->access_username ."</UserId>\n".
		"	<Password>". $this->access_password ."</Password>\n".
		"</AccessRequest>\n";

		$timeintransitSelectionRequestHeader =
		"<?xml version=\"1.0\"?>\n".
		"<TimeInTransitRequest xml:lang=\"en-US\">\n".
		"	<Request>\n".
		"		<TransactionReference>\n".
		"			<CustomerContext>Time in Transit</CustomerContext>\n".
		"			<XpciVersion>". $this->transitxpci_version ."</XpciVersion>\n".
		"		</TransactionReference>\n".
		"		<RequestAction>TimeInTransit</RequestAction>\n".
		"	</Request>\n".
		"	<TransitFrom>\n".
		"		<AddressArtifactFormat>\n".
		"			<PoliticalDivision2>". $this->origin_city ."</PoliticalDivision2>\n".
		"			<PoliticalDivision1>". $this->origin_stateprov ."</PoliticalDivision1>\n".
		"			<CountryCode>". $this->_upsOriginCountryCode ."</CountryCode>\n".
		"			<PostcodePrimaryLow>". $this->origin_postalcode ."</PostcodePrimaryLow>\n".
		"		</AddressArtifactFormat>\n".
		"	</TransitFrom>\n".
		"	<TransitTo>\n".
		"		<AddressArtifactFormat>\n".
		"			<PoliticalDivision2>". $this->_upsDestCity ."</PoliticalDivision2>\n".
		"			<PoliticalDivision1>". $this->_upsDestStateProv ."</PoliticalDivision1>\n".
		"			<CountryCode>". $this->_upsDestCountryCode ."</CountryCode>\n".
		"			<PostcodePrimaryLow>". $this->_upsDestPostalCode ."</PostcodePrimaryLow>\n".
		"			<PostcodePrimaryHigh>". $this->_upsDestPostalCode ."</PostcodePrimaryHigh>\n".
		"		</AddressArtifactFormat>\n".
		"	</TransitTo>\n".
		"	<PickupDate>".$shipdate."</PickupDate>\n".
		"	<ShipmentWeight>\n".
		"		<UnitOfMeasurement>\n".
		"			<Code>".$this->unit_weight."</Code>\n".
		"		</UnitOfMeasurement>\n".
		"		<Weight>10</Weight>\n".
		"	</ShipmentWeight>\n".
		"	<InvoiceLineTotal>\n".
		"		<CurrencyCode>USD</CurrencyCode>\n".
		"		<MonetaryValue>100</MonetaryValue>\n".
		"	</InvoiceLineTotal>\n".
		"</TimeInTransitRequest>\n";

		$xmlTransitRequest = $accessRequestHeader.$timeintransitSelectionRequestHeader;

		//post request $strXML;
		$xmlTransitResult = $this->_post($this->protocol, $this->host, $this->port, $this->transitpath, $this->transitversion, $this->timeout, $xmlTransitRequest);
		return $this->_transitparseResult($xmlTransitResult);
	}

	//***************************************
	function _transitparseResult($xmlTransitResult) {
		$transitTime = array();

		// Parse XML message returned by the UPS post server.
		$doc = new XMLDocument();
		$xp = new XMLParser();
		$xp->setDocument($doc);
		$xp->parse($xmlTransitResult);
		$doc = $xp->getDocument();
		// Get version. Must be xpci version 1.0001 or this might not work.
		$responseVersion = $doc->getValueByPath('TimeInTransitResponse/Response/TransactionReference/XpciVersion');
		if ($this->transitxpci_version != $responseVersion) {
			$message = MODULE_SHIPPING_IUX_RATES_TEXT_COMM_VERSION_ERROR;
			return $message;
		}
		// Get response code. 1 = SUCCESS, 0 = FAIL
		$responseStatusCode = $doc->getValueByPath('TimeInTransitResponse/Response/ResponseStatusCode');
		if ($responseStatusCode != '1') {
			$errorMsg = $doc->getValueByPath('TimeInTransitResponse/Response/Error/ErrorCode');
			$errorMsg .= ": ";
			$errorMsg .= $doc->getValueByPath('TimeInTransitResponse/Response/Error/ErrorDescription');
			return $errorMsg;
		}
		$root = $doc->getRoot();
		$rootChildren = $root->getChildren();
		for ($r = 0; $r < count($rootChildren); $r++) {
			$elementName = $rootChildren[$r]->getName();
			if ($elementName == "TransitResponse") {
				$transitResponse = $root->getElementsByName("TransitResponse");
				$serviceSummary = $transitResponse['0']->getElementsByName("ServiceSummary");
				$this->numberServices = count($serviceSummary);
				for ($s = 0; $s < $this->numberServices ; $s++) {
					// index by Desc because that's all we can relate back to the service with
					// (though it can probably return the code as well..)
					$serviceDesc = $serviceSummary[$s]->getValueByPath("Service/Description");

					$transitTime[$serviceDesc]["days"] = $serviceSummary[$s]->getValueByPath("EstimatedArrival/BusinessTransitDays");
					$transitTime[$serviceDesc]["date"] = $serviceSummary[$s]->getValueByPath("EstimatedArrival/Date");
					$transitTime[$serviceDesc]["guaranteed"] = $serviceSummary[$s]->getValueByPath("Guaranteed/Code");
				}
			}
		}
		if (!empty($this->logfile)) {
			error_log("------------------------------------------\n", 3, $this->logfile);
			foreach ($transitTime as $desc => $time) {
				error_log("Business Transit: ".$desc ." = ". $time["date"]."\n", 3, $this->logfile);
			}
		}
		return $transitTime;
	}
}

//***************************
function exclude_choices($service) {
	// used for exclusion of UPS shipping options, read from db
	$allowed_services = preg_split('/\s*,\s*/', strtolower(MODULE_SHIPPING_IUX_TYPES));
	foreach ($allowed_services as $idx => $allowed) {
		if ($allowed == 'saturday delivery (2nd day air)' && !is_thursday()) unset($allowed_services[$idx]);
		if ($allowed == 'saturday delivery (next day air)' && !is_friday()) unset($allowed_services[$idx]);
	}

	$service = trim(strtolower($service));

	$service_attempts = [$service];

	// this will chop off "UPS" from the beginning of the line - typically something like UPS Next Day Air (1 Business Days)
	if (preg_match('/ups\s*/', $service)) $service_attempts[] = $service = trim(preg_replace('/ups\s*/', '', $service));

	// this will chop off (x Business Days)
	if (preg_match('/\s*\(.+\)/', $service)) $service_attempts[] = $service = trim(preg_replace('/\s*\(.+\)/', '', $service));

	$service_attempts = array_reverse($service_attempts);

	foreach ($service_attempts as $attempt) {
		if (in_array($attempt, $allowed_services)) return TRUE;
	}
	return FALSE;
}

function is_thursday($time=NULL) {
	if (empty($time)) $time = new DateTime();
	// wed after 8, or thurs before 8
	return ($time->format('w') == 3 && $time->format('H') >= 20) || ($time->format('w') == 4 && $time->format('H') < 20);
}

function is_friday($time=NULL) {
	if (empty($time)) $time = new DateTime();
	// thurs after 8, or fri before 8
	return ($time->format('w') == 4 && $time->format('H') >= 20) || ($time->format('w') == 5 && $time->format('H') < 20);
}
?>
