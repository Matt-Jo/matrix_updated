<?php
require_once(DIR_FS_CATALOG.DIR_WS_INCLUDES.'lib-apis/fedex-common.php5');

global $fws_client_options;
$fws_client_options = [];

define('FEDEX_WS_ACCOUNT_NUMBER', '241484688');
define('FEDEX_WS_METER_NUMBER', '103249688');
define('FEDEX_WS_KEY', 'QHzfbwfYAEdH6Ncd');
define('FEDEX_WS_PASSWORD', 'hT7caRuSDbaSUpHE4zM0cEqRa');
$fws_client_options['trace'] = TRUE;

/*******
 * Functions below here are only utility functions for the
 * functions above
 *******/
function _fws_get_client($wsdl) {
	global $fws_client_options;
	$wsdl_array = [
		'RATE' => DIR_FS_CATALOG.DIR_WS_INCLUDES."wsdl/RateService_v10.wsdl",
		'SHIP' => DIR_FS_CATALOG.DIR_WS_INCLUDES."wsdl/ShipService_v10.wsdl"
	];
	ini_set("soap.wsdl_cache_enabled", "0");

	return new SoapClient($wsdl_array[$wsdl], $fws_client_options);
}

function _fws_get_request() {
	$request = [];
	$request['WebAuthenticationDetail'] = [
		'UserCredential' => [
			'Key' => FEDEX_WS_KEY,
			'Password' => FEDEX_WS_PASSWORD
		]
	]
		;
	$request['ClientDetail'] = [
		'AccountNumber' => FEDEX_WS_ACCOUNT_NUMBER,
		'MeterNumber' => FEDEX_WS_METER_NUMBER
	];

	return $request;
}

function _fws_get_office_address() {
	$address = [
		'Contact' => [
			'PersonName' => 'Shipping Department',
			'CompanyName' => 'CablesAndKits.com',
			'PhoneNumber' => '8666220223'
		],
		'Address' => [
			'StreetLines' => ['4555 Atwater Ct', 'Suite A'],
			'City' => 'Buford',
			'StateOrProvinceCode' => 'GA',
			'PostalCode' => '30518',
			'CountryCode' => 'US'
		]
	];

	return $address; //Add Origin Address
}

function _fws_build_address(ck_address2 $address) {
	$addr = $address->get_header();

	$address_arr = [
		'Contact' => [
			'PersonName' => $address->get_name(),
			'CompanyName' => $address->get_company_name(),
			'PhoneNumber' => $addr['telephone'],
		],
		'Address' => [
			'StreetLines' => [$addr['address1'], $addr['address2']],
			'City' => $addr['city'],
			'PostalCode' => $addr['postcode'],
			'CountryCode' => $addr['countries_iso_code_2'],
		]
	];

	$state_code = $address->get_state();
	if (strlen(trim($state_code)) <= 2) {
		$address_arr['Address']['StateOrProvinceCode'] = $state_code;
	}

	return $address_arr;
}

//total weight = total weight of each package
function fws_get_rates($destination_address, $total_weight, $number_of_boxes=1, $saturday_delivery=FALSE) {
	$client = _fws_get_client('RATE');
	$request = _fws_get_request();

	$request['TransactionDetail'] = ['CustomerTransactionId' => '*** CablesAndKits Shipping Request v10 using PHP ***'];
	$request['Version'] = [
		'ServiceId' => 'crs',
		'Major' => '10',
		'Intermediate' => '0',
		'Minor' => '0'
	];

	$request['ReturnTransitAndCommit'] = TRUE;

	$ltime = localtime(time(), TRUE);
	$ship_timestamp = NULL;

	if ($ltime['tm_wday'] >= 1 && $ltime['tm_wday'] <= 4) { //handle monday through friday cases first, these are easiest
		if ($ltime['tm_hour'] < 20) { //shipping today
			$ship_timestamp = date('c');
		}
		else { // otherwise, shipping tomorrow
			$ship_timestamp = date('c', time() + (60 * 60 * 12));
		}
	}
	elseif ($ltime['tm_wday'] == 5) { //friday
		if ($ltime['tm_hour'] < 20) { //shipping today
			$ship_timestamp = date('c');
		}
		else { //shipping monday
			$ship_timestamp = date('c', time() + (60 * 60 *60));
		}
	}
	elseif ($ltime['tm_wday'] == 6) { //saturday - handling similar to friday so we don't have any ship times past 8:00 on monday
		if ($ltime['tm_hour'] < 20) { //shipping monday
			$ship_timestamp = date('c', time() + (60 * 60 * 48));
		}
		else { //shipping monday before 8:00
			$ship_timestamp = date('c', time() + (60 * 60 * 36));
		}
	}
	elseif ($ltime['tm_wday'] == 7) { //sunday - handling similar to friday so we don't have any ship times past 8:00 on monday
		if ($ltime['tm_hour'] < 20) { //shipping monday
			$ship_timestamp = date('c', time() + (60 * 60 * 24));
		}
		else { //shipping monday before 8:00
			$ship_timestamp = date('c', time() + (60 * 60 * 12));
		}
	}

	$request['RequestedShipment'] = [
		'ShipTimestamp' => $ship_timestamp,
		'DropoffType' => 'REGULAR_PICKUP',
		'Shipper' => _fws_get_office_address(),
		'Recipient' => _fws_build_address($destination_address),
		'ShippingChargesPayment' => [
			'PaymentType' => 'SENDER',
			'Payor' => [
				'AccountNumber' => FEDEX_WS_ACCOUNT_NUMBER,
				'CountryCode' => 'US'
			]
		],
		'RateRequestTypes' => 'LIST', //'ACCOUNT',
		'PackageCount' => $number_of_boxes,
		'PackageDetail' => 'INDIVIDUAL_PACKAGES',
		'RequestedPackageLineItems' => []
	];

	if ($saturday_delivery) {
		$request['RequestedShipment']['SpecialServicesRequested'] = ['SpecialServiceTypes' => 'SATURDAY_DELIVERY'];
	}
	for ($i = 0; $i < $number_of_boxes; $i++) {
		$request['RequestedShipment']['RequestedPackageLineItems'][] = [
			'GroupPackageCount' => '1',
			'Weight' => [
				'Value' => round(($total_weight), 1),
				//'Value' => round(($total_weight / $number_of_boxes), 1),
				'Units' => 'LB'
			]
		];
	}

	$response = NULL;
	try {
		$response = $client->getRates($request);
	}
	catch (Exception $e) {
		return [];
		//var_dump($e);
	}

	if ($response -> HighestSeverity == 'FAILURE' || $response -> HighestSeverity == 'ERROR') {
		return FALSE;
	}

	$key_suffix = '';
	if ($saturday_delivery) {
		$key_suffix = '_SAT';
	}

	$result = [];
	if (!empty($response->RateReplyDetails)) {
		if (!is_array($response->RateReplyDetails)) $rate_reply_details = [$response->RateReplyDetails];
		else $rate_reply_details = $response->RateReplyDetails;

		foreach ($rate_reply_details as $rateReply) {
			$method = $rateReply->ServiceType.$key_suffix;
			if (empty($result[$method])) $result[$method] = [];

			foreach ($rateReply->RatedShipmentDetails as &$details) {
				$rate_type = $details->ShipmentRateDetail->RateType;

				if (!preg_match('/^PAYOR_/', $rate_type)) continue;

				if (preg_match('/_LIST_/', $rate_type)) $type = 'list_rate';
				elseif (preg_match('/_ACCOUNT_/', $rate_type)) $type = 'negotiated_rate';
				else continue;

				$dtl = json_decode(json_encode($details->ShipmentRateDetail), TRUE);

				unset($details->ShipmentRateDetail);

				$result[$method][$type] = number_format($dtl['TotalNetCharge']['Amount'], 2, '.', '');
			}
		}
	}

	return $result;
}

// payment should be an array containing 'type', 'account_number', and 'country'
// writes label to file system as <TRACKING_NUM>.png
// returns array with 'tracking_number' - others to be added later
function fws_generate_shipping_labels($shipping_method, $origin_address=NULL, $destination_address=NULL, $package, $payment, $master_tracking_number=NULL, $package_number=1, $package_count=1, $saturday_shipping=FALSE, $signature_option='NO_SIGNATURE_REQUIRED', $addtional_path='', $po_number='') {

	$client = _fws_get_client('SHIP');
	$request = _fws_get_request();

	$request['TransactionDetail'] = ['CustomerTransactionId' => '*** CablesAndKits Shipping Request v10 using PHP ***'];
	$request['Version'] = [
		'ServiceId' => 'ship',
		'Major' => '10',
		'Intermediate' => '0',
		'Minor' => '0'
	];

	$request['RequestedShipment'] = [
		'ShipTimestamp' => date('c'),
		'DropoffType' => 'REGULAR_PICKUP', // valid values REGULAR_PICKUP, REQUEST_COURIER, DROP_BOX, BUSINESS_SERVICE_CENTER and STATION
		'ServiceType' => $shipping_method, // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
		'PackagingType' => 'YOUR_PACKAGING', // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
		'ShippingChargesPayment' => [
			'PaymentType' => $payment['type'],
			'Payor' => [
				'AccountNumber' => $payment['account_number'],
				'CountryCode' => $payment['country']
			]
		],
		//MMD - need to sort out the image type and stock type below - this matches closest to what we are currently doing but i'm not sure how we print
		'LabelSpecification' => [
			'LabelFormatType' => 'COMMON2D', // valid values COMMON2D, LABEL_DATA_ONLY
			'ImageType' => 'PNG', // valid values DPL, EPL2, PDF, ZPLII and PNG
			'LabelStockType' => 'PAPER_4X6'// 'PAPER_7X4.75'
		],
		'RateRequestTypes' => ['ACCOUNT'], // valid values ACCOUNT and LIST
		'PackageCount' => @$number_of_packages,
		'PackageDetail' => 'INDIVIDUAL_PACKAGES',
		'RequestedPackageLineItems' => []
	];

	if (NULL == $origin_address) {
		$request['RequestedShipment']['Shipper'] = _fws_get_office_address();
	}
	else {
		$request['RequestedShipment']['Shipper'] = _fws_build_address($origin_address);
	}

	if (NULL == $destination_address) {
		$request['RequestedShipment']['Recipient'] = _fws_get_office_address();
	}
	else {
		$request['RequestedShipment']['Recipient'] = _fws_build_address($destination_address);
	}

	if (NULL != $master_tracking_number) {
		$request['RequestedShipment']['MasterTrackingId'] = $master_tracking_number;
	}

	$request['RequestedShipment']['SpecialServicesRequested'] = ['SpecialServiceTypes' => []];

	if ($saturday_shipping) {
		$request['RequestedShipment']['SpecialServicesRequested']['SpecialServiceTypes'][] = 'SATURDAY_DELIVERY';
	}

	if ($package_count > 0) {
		$request['RequestedShipment']['PackageCount'] = $package_count;
	}

	$request['RequestedShipment']['RequestedPackageLineItems'][] = [
		'SequenceNumber' => $package_number,
		'GroupPackageCount' => 1,
		'Weight' => [
			'Value' => round($package['weight'], 1),
			'Units' => 'LB'
		],
		'Dimensions' => [
			'Height' => $package['dimensions']['height'],
			'Width' => $package['dimensions']['width'],
			'Length' => $package['dimensions']['length'],
			'Units' => 'IN'
		],
		'SpecialServicesRequested' => [
			'SpecialServiceTypes' => ['SIGNATURE_OPTION'],
			'SignatureOptionDetail' => [
				'OptionType' => $signature_option
			]
		]
	];

	if (trim($po_number) != '') {
		$request['RequestedShipment']['RequestedPackageLineItems'][0]['CustomerReferences'] = [['CustomerReferenceType' => 'P_O_NUMBER', 'Value' => $po_number]];
	}

	try {
		try {
			prepared_query::execute('INSERT INTO ck_debug_labels (request, response) VALUES (:request, :response)', [':request' => json_encode($request), ':response' => '']);
		}
		catch (Exception $e) {
			// do nothing
		}

		$response = $client->processShipment($request); // FedEx web service invocation
		if (!empty($response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber) && !empty($response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image)) {
			$fh = fopen(DIR_FS_ADMIN.DIR_WS_FEDEX_LABELS.$addtional_path.$response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber.'.png', 'w+b');
			fwrite($fh, $response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);
			fclose($fh);
		}
	}
	catch (Exception $e) {
		var_dump($e);
		throw $e;
	}

	$result = [];
	$result['tracking_number'] = !empty($response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber)?$response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber:NULL;

	if (!empty($response->CompletedShipmentDetail->MasterTrackingId)) {
		$result['master_tracking'] = get_object_vars($response->CompletedShipmentDetail->MasterTrackingId);
	}

	$result['tracking_cost'] = 0;
	if (!empty($response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating)) {
		// we were charged for this shipment, rather than putting it on the customer fedex account
		$actual_rate_type = $response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating->ActualRateType; // cache the rate type

		// create the array even if there was only one response, so we can handle in the same way
		if (!is_array($response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating->PackageRateDetails)) $prds = [$response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating->PackageRateDetails];
		else $prds = $response->CompletedShipmentDetail->CompletedPackageDetails->PackageRating->PackageRateDetails;

		foreach ($prds as $prd) {
			if ($prd->RateType != $actual_rate_type) continue;
			$result['tracking_cost'] = $prd->NetCharge->Amount;
		}
	}

	if ($response -> HighestSeverity == 'FAILURE' || $response -> HighestSeverity == 'ERROR') {
		$exception_string = '';

		ob_start();
		printError($client, $response);
		$exception_string = ob_get_contents();
		ob_end_clean();

		throw new Exception ($exception_string);
	}

	return $result;
}

function fws_cancel_shipping_label($tracking_num, $tracking_type) {
	$client = _fws_get_client('SHIP');
	$request = _fws_get_request();

	$request['TransactionDetail'] = ['CustomerTransactionId' => '*** CablesAndKits Shipping Request v10 using PHP ***'];
	$request['Version'] = [
		'ServiceId' => 'ship',
		'Major' => '10',
		'Intermediate' => '0',
		'Minor' => '0'
	];

	$request['DeleteShipmentRequest'] = [
		'ShipTimestamp' => date('c'),
		'TrackingId' => [
			'TrackingIdType' => $tracking_type,
			'TrackingNumber' => $tracking_num
		],
		'DeletionControl' => 'DELETE_ALL_PACKAGES'
	];

	try {
		$client->deleteShipment($request);
	}
	catch (Exception $e) {
	}
}
?>
