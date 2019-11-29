<?php
require_once ('includes/classes/xmldocument.php');
require_once (DIR_FS_CATALOG.'includes/languages/english/modules/shipping/upsxml.php');
require_once (DIR_FS_CATALOG.'includes/languages/english/modules/shipping/iux.php');
// if using the optional dimensional support, set to 1, otherwise leave as 0
if (!defined('DIMENSIONS_SUPPORTED')) define('DIMENSIONS_SUPPORTED', 0);

class ups_rates {
	var $types, $boxcount;

	//***************
	function __construct() {
		$this->access_key = MODULE_SHIPPING_UPSXML_RATES_ACCESS_KEY;
		$this->access_username = MODULE_SHIPPING_UPSXML_RATES_USERNAME;
		$this->access_password = MODULE_SHIPPING_UPSXML_RATES_PASSWORD;
		$this->origin = MODULE_SHIPPING_UPSXML_RATES_ORIGIN;
		$this->origin_city = MODULE_SHIPPING_UPSXML_RATES_CITY;
		$this->origin_stateprov = MODULE_SHIPPING_UPSXML_RATES_STATEPROV;
		$this->origin_country = MODULE_SHIPPING_UPSXML_RATES_COUNTRY;
		$this->origin_postalcode = MODULE_SHIPPING_UPSXML_RATES_POSTALCODE;
		$this->pickup_method = MODULE_SHIPPING_UPSXML_RATES_PICKUP_METHOD;
		$this->package_type = MODULE_SHIPPING_UPSXML_RATES_PACKAGE_TYPE;
		$this->unit_weight = MODULE_SHIPPING_UPSXML_RATES_UNIT_WEIGHT;
		$this->unit_length = MODULE_SHIPPING_UPSXML_RATES_UNIT_LENGTH;
		$this->handling_fee = MODULE_SHIPPING_UPSXML_RATES_HANDLING;
		$this->quote_type = MODULE_SHIPPING_UPSXML_RATES_QUOTE_TYPE;
		$this->customer_classification = MODULE_SHIPPING_UPSXML_RATES_CUSTOMER_CLASSIFICATION_CODE;
		$this->protocol = 'https';
		$this->host = ((MODULE_SHIPPING_UPSXML_RATES_TEST_MODE == 'Test') ? 'wwwcie.ups.com' : 'wwwcie.ups.com');
		$this->port = '443';
		$this->path = '/ups.app/xml/Rate';
		$this->transitpath = '/ups.app/xml/TimeInTransit';
		$this->version = 'UPSXML Rate 1.0001';
		$this->transitversion = 'UPSXML Time In Transit 1.0001';
		$this->timeout = '60';
		$this->xpci_version = '1.0001';
		$this->transitxpci_version = '1.0001';
		$this->items_qty = 0;
		$this->timeintransit = '0';
		$this->today = date("Ymd");
		# 334
		$this->sm_map = [
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
			'UPS Standard' => 29
		];

		// insurace addition
		if (MODULE_SHIPPING_UPSXML_INSURE == 'False') {$this->pkgvalue = 100;}
		if (MODULE_SHIPPING_UPSXML_INSURE == 'True') {$this->pkgvalue = ceil($order->info['subtotal']);}
		// end insurance addition
		// to enable logging, create an empty "upsxml.log" file at the location you set below, give it write permissions (777) and uncomment the next line
		///	$this->logfile = '/tmp/upsxml.log';

		// when cURL is not compiled into PHP (Windows users, some Linux users)
		// you can set the next variable to "1" and then exec(curl -d $xmlRequest, $xmlResponse)
		// will be used
		$this->use_exec = '0';

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
				'01' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_01,
				'02' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_02,
				'03' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_03,
				'07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_07,
				'08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_08,
				'11' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_11,
				'12' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_12,
				'13' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_13,
				'14' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_14,
				'54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_54,
				'59' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_59,
				'65' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_US_ORIGIN_65
			],
			// Canada Origin
			'Canada Origin' => [
				'01' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_01,
				'07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_07,
				'08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_08,
				'11' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_11,
				'12' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_12,
				'13' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_13,
				'14' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_14,
				'54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_CANADA_ORIGIN_54
			],
			// European Union Origin
			'European Union Origin' => [
				'07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_07,
				'08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_08,
				'11' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_11,
				'54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_54,
				'64' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_64,
				'65' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_EU_ORIGIN_65
			],
			// Puerto Rico Origin
			'Puerto Rico Origin' => [
				'01' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_01,
				'02' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_02,
				'03' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_03,
				'07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_07,
				'08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_08,
				'14' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_14,
				'54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_PR_ORIGIN_54
			],
			// Mexico Origin
			'Mexico Origin' => [
				'07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_07,
				'08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_08,
				'54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_MEXICO_ORIGIN_54
			],
			// All other origins
			'All other origins' => [
				'07' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_07,
				'08' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_08,
				'54' => MODULE_SHIPPING_UPSXML_SERVICE_CODE_OTHER_ORIGIN_54
			]
		];
	}

	// class methods
	//delivery_address - ck_address2 object
	//shipping_weight - weight of 1 package in the shipment
	//shipping_num_boxes - total number of packages in the shipment
	function get_quotes(ck_address2 $delivery_address, $shipping_weight, $shipping_num_boxes) {
		$state = strtoupper($delivery_address->get_state());

		$this->_upsOrigin(MODULE_SHIPPING_UPSXML_RATES_CITY, MODULE_SHIPPING_UPSXML_RATES_STATEPROV, MODULE_SHIPPING_UPSXML_RATES_COUNTRY, MODULE_SHIPPING_UPSXML_RATES_POSTALCODE);
		$this->_upsDest($delivery_address->get_header('city'), $state, $delivery_address->get_header('countries_iso_code_2'), $delivery_address->get_header('postcode'));

		$this->items_qty = 0; //reset quantities
		for ($i = 0; $i < $shipping_num_boxes; $i++) {
			$this->_addItem (0, 0, 0, $shipping_weight);
		}

		// BOF Time In Transit: comment out this section if you don't want/need to have
		// expected delivery dates

		$this->servicesTimeintransit = $this->_upsGetTimeServices();
		if ($this->logfile) {
			error_log("------------------------------------------\n", 3, $this->logfile);
			error_log("Time in Transit: ".$this->timeintransit."\n", 3, $this->logfile);
		}

		// EOF Time In Transit

		$upsQuote = $this->_upsGetQuote();
		$methods = [];
		if ((is_array($upsQuote)) && (sizeof($upsQuote) > 0)) {
			for ($i=0; $i < sizeof($upsQuote); $i++) {
				$type = key($upsQuote[$i]);
                $cost = current(array_values($upsQuote[$i]));
				//MMD - we are only showing UPS options to customers shipping on their own account - so we want to show everything here
				// - thus we are commenting the line below out.
				//if (!exclude_u_choices($type)) continue;
				if ( $method == '' || $method == $type ) {
					$_type = $type;
					//		if (isset($this->servicesTimeintransit[$type])) {
					//				$_type = $_type.", ".$this->servicesTimeintransit[$type]["date"];
					//		}

					// instead of just adding the expected delivery date as ", yyyy-mm-dd"
					// you might like to change this to your own liking for example by commenting the
					// three lines above this and uncommenting/changing the next:
					// START doing things differently
					//

					if (isset($this->servicesTimeintransit[$type])) {
						$eta_array = explode("-", $this->servicesTimeintransit[$type]["date"]);
						$months = array (" ", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
						$eta_arrival_date = $months[(int)$eta_array[1]]." ".$eta_array[2].", ".$eta_array[0];
						$type_description = "Estimated Delivery: ".$eta_arrival_date;
					}
					// END of doing things differently:

					$methods[] = [
						'id' => $type,
						'title' => $this->sm_map[$_type],
						'shipping_method_id' => $this->sm_map[$_type], # 334
						'cost' => ($this->handling_fee + $cost)
					];
				}
			}

			//Kirk added for changes per chris email on 6-30-09
			//$methods = array_reverse($methods);
		}
		else {
			if ( $upsQuote != false ) {
				$errmsg = $upsQuote;
			}
			else {
				$errmsg = MODULE_SHIPPING_UPSXML_RATES_TEXT_UNKNOWN_ERROR;
			}
			$errmsg .= '<br>'.MODULE_SHIPPING_UPSXML_RATES_TEXT_IF_YOU_PREFER.' '.STORE_NAME.' via <a href="mailto:sales@cablesandkits.com"><u>Email</U></a>.';
			$methods[] = $errmsg;
		}
		return $methods;
	}

	//***********************
	function _upsProduct($prod) {
		$this->_upsProductCode = $prod;
	}

	//**********************************************
	function _upsOrigin($city, $stateprov, $country, $postal) {
		$this->_upsOriginCity = $city;
		$this->_upsOriginStateProv = $stateprov;
		$this->_upsOriginCountryCode = $country;
		$postal = str_replace(' ', '', $postal);
		if ($country == 'US') {
			$this->_upsOriginPostalCode = substr($postal, 0, 5);
		}
		else {
			$this->_upsOriginPostalCode = $postal;
		}
	}

	//**********************************************
	function _upsDest($city, $stateprov, $country, $postal) {
		$this->_upsDestCity = $city;
		$this->_upsDestStateProv = $stateprov;
		$this->_upsDestCountryCode = $country;
		$postal = str_replace(' ', '', $postal);
		if ($country == 'US') {
			$this->_upsDestPostalCode = substr($postal, 0, 5);
		}
		else {
			$this->_upsDestPostalCode = $postal;
		}
	}

	//************************
	function _upsAction($action) {
		// rate - Single Quote; shop - All Available Quotes
		$this->_upsActionCode = $action;
	}

	//********************************************
	function _addItem($length, $width, $height, $weight) {
		// Add box or item to shipment list. Round weights to 1 decimal places.
		if ((float)$weight < 1.0) {
			$weight = 1;
		}
		else {
			$weight = round($weight, 1);
		}
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
			"	</PickupType>\n".
			"	<Shipment>\n".
			"		<Shipper>\n".
			"			<Address>\n".
			"				<City>". $this->_upsOriginCity ."</City>\n".
			"				<StateProvinceCode>". $this->_upsOriginStateProv ."</StateProvinceCode>\n".
			"				<CountryCode>". $this->_upsOriginCountryCode ."</CountryCode>\n".
			"				<PostalCode>". $this->_upsOriginPostalCode ."</PostalCode>\n".
			"			</Address>\n".
			"		</Shipper>\n".
			"		<ShipTo>\n".
			"			<Address>\n".
			"				<City>". $this->_upsDestCity ."</City>\n".
			"				<StateProvinceCode>". $this->_upsDestStateProv ."</StateProvinceCode>\n".
			"				<CountryCode>". $this->_upsDestCountryCode ."</CountryCode>\n".
			"				<PostalCode>". $this->_upsDestPostalCode ."</PostalCode>\n".
			($this->quote_type == "Residential" ? "<ResidentialAddressIndicator/>\n" : "") .
			"			</Address>\n".
			"		</ShipTo>\n";

		for ($i = 0; $i < $this->items_qty; $i++) {
			$ratingServiceSelectionRequestPackageContent .=
				"		<Package>\n".
				"			<PackagingType>\n".
				"				<Code>". $this->package_types[$this->package_type] ."</Code>\n".
				"			</PackagingType>\n";

			if (DIMENSIONS_SUPPORTED) {
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
				"				<UnitOfMeasurement>\n".
				"					<Code>". $this->unit_weight ."</Code>\n".
				"				</UnitOfMeasurement>\n".
				"				<Weight>". $this->item_weight[$i] ."</Weight>\n".
				"			</PackageWeight>\n".
				"			<PackageServiceOptions>\n".
				//"				<COD>\n".
				//"					<CODFundsCode>0</CODFundsCode>\n".
				//"					<CODCode>3</CODCode>\n".
				//"					<CODAmount>\n".
				//"						<CurrencyCode>USD</CurrencyCode>\n".
				//"						<MonetaryValue>1000</MonetaryValue>\n".
				//"					</CODAmount>\n".
				//"				</COD>\n".
				"				<InsuredValue>\n".
				"					<CurrencyCode>".MODULE_SHIPPING_UPSXML_CURRENCY_CODE."</CurrencyCode>\n".
				"					<MonetaryValue>".$this->pkgvalue."</MonetaryValue>\n".
				"				</InsuredValue>\n".
				"			</PackageServiceOptions>\n".
				"		</Package>\n";
		}

		$ratingServiceSelectionRequestFooter =
			//"	<ShipmentServiceOptions/>\n".
			"	</Shipment>\n".
			"	<CustomerClassification>\n".
			"		<Code>". $this->customer_classification ."</Code>\n".
			"	</CustomerClassification>\n".
			"</RatingServiceSelectionRequest>\n";

		$xmlRequest = $accessRequestHeader.$ratingServiceSelectionRequestHeader.$ratingServiceSelectionRequestPackageContent.$ratingServiceSelectionRequestFooter;

		//post request $strXML;
		$xmlResult = $this->_post($this->protocol, $this->host, $this->port, $this->path, $this->version, $this->timeout, $xmlRequest);
		return $this->_parseResult($xmlResult);
	}

	//******************************************************************
	function _post($protocol, $host, $port, $path, $version, $timeout, $xmlRequest) {
		$url = $protocol."://".$host.":".$port.$path;
		if ($this->logfile) {
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
			if ($this->logfile) {
				error_log("UPS REQUEST using exec(): ".$xmlRequest."\n", 3, $this->logfile);
			}
			// add option -k to the statement: $command = "".$curl_path." -k -d \"". etcetera if you get
			// curl error 60: error setting certificate verify locations
			// using addslashes was the only way to avoid UPS returning the 1001 error: The XML document is not well formed
			$command = "".$curl_path." -d \"".addslashes($xmlRequest)."\" ".$url."";
			exec($command, $xmlResponse);
			if ( empty($xmlResponse) && $this->logfile) { // using exec no curl errors can be retrieved
				error_log("Error from cURL using exec() since there is no \$xmlResponse\n", 3, $this->logfile);
			}
			if ($this->logfile) {
				error_log("UPS RESPONSE using exec(): ".$xmlResponse[0]."\n", 3, $this->logfile);
			}
		}
		elseif ($this->use_exec == '1') { // if NOT (function_exists('exec') && $this->use_exec == '1'
			if ($this->logfile) {
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

			if ($this->logfile) {
				error_log("UPS REQUEST: ".$xmlRequest."\n", 3, $this->logfile);
			}
			$xmlResponse = curl_exec ($ch);
			if (curl_errno($ch) && $this->logfile) {
				$error_from_curl = sprintf('Error [%d]: %s', curl_errno($ch), curl_error($ch));
				error_log("Error from cURL: ".$error_from_curl."\n", 3, $this->logfile);
			}
			if ($this->logfile) {
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
				"		<ResponseStatusDescription>". MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_UNKNOWN_ERROR ."</ResponseStatusDescription>\n".
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
			$message = MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_VERSION_ERROR;
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

			$aryProducts[$i] = array($title => $totalCharge);
		}
		return $aryProducts;
	}

	// BOF Time In Transit

	// GM 11-15-2004: renamed from _upsGetTime()

	//********************
	function _upsGetTimeServices() {
		if (defined('UPSXML_SHIPPING_DAYS_DELAY')) {
			$shipdate = date("Ymd", time()+(86400*UPSXML_SHIPPING_DAYS_DELAY));
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

	// GM 11-15-2004: modified to return array with time for each service, as
	//				opposed to single transit time for hardcoded "GND" code

	function _transitparseResult($xmlTransitResult) {
		$transitTime = [];

		// Parse XML message returned by the UPS post server.
		$doc = new XMLDocument();
		$xp = new XMLParser();
		$xp->setDocument($doc);
		$xp->parse($xmlTransitResult);
		$doc = $xp->getDocument();
		// Get version. Must be xpci version 1.0001 or this might not work.
		$responseVersion = $doc->getValueByPath('TimeInTransitResponse/Response/TransactionReference/XpciVersion');
		if ($this->transitxpci_version != $responseVersion) {
			$message = MODULE_SHIPPING_UPSXML_RATES_TEXT_COMM_VERSION_ERROR;
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
		if ($this->logfile) {
			error_log("------------------------------------------\n", 3, $this->logfile);
			foreach ($transitTime as $desc => $time) {
				error_log("Business Transit: ".$desc ." = ". $time["date"]."\n", 3, $this->logfile);
			}
		}
		return $transitTime;
	}

	//EOF Time In Transit
}

//***************************
function exclude_u_choices($type) {
	// used for exclusion of UPS shipping options, read from db
	$allowed_types = explode(",", MODULE_SHIPPING_UPSXML_TYPES);
	if (strstr($type, "UPS")) {
		// this will chop off "UPS" from the beginning of the line - typically something like UPS Next Day Air (1 Business Days)
		$type_minus_ups = explode("UPS", $type );
		if (strstr($type, "(")) {
			// this will chop off (x Business Days)
			$type_minus_bd = explode("(", $type_minus_ups[1] );
			// get rid of white space with trim
			$type_root = trim($type_minus_bd[0]);
		}
		else { // end if (strstr($type, "("))
			// if service description contains UPS but not (x Business days):
			$type_root = trim($type_minus_ups[1]);
		} // end if (strstr($type, "UPS"):
	}
	elseif (strstr($type, "(")) {
		// if service description doesn't contain UPS, but does (x Business Days):
		$type_minus_ups_bd = explode("(", $type );
		$type_root = trim($type_minus_ups_bd[0]);
	}
	else { // service description neither contain UPS nor (x Business Days)
		$type_root = trim($type);
	}
	for ($za = 0; $za < count ($allowed_types); $za++ ) {
		if ($type_root == trim($allowed_types[$za])) {
			return true;
			exit;
		} // end if ($type_root == $allowed_types[$za] ...
	}
	// if the type is not allowed:
	return false;
}

//******************************
function ready_to_u_shipCmp( $a, $b) {
	if ( $a['ready_to_ship'] == $b['ready_to_ship'] ) return 0;
	if ( $a['ready_to_ship'] > $b['ready_to_ship'] ) return -1;
	return 1;
} ?>
