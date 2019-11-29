<?php 

require_once(DIR_FS_CATALOG . DIR_WS_INCLUDES . 'lib-apis/fedex-common.php5');

global $fws_client_options;
$fws_client_options = array();   

$config = service_locator::get_config_service();
if($config->get_env() != Ck_Config::ENV_PRODUCTION){ //DEVELOPMENT
	/*define('FEDEX_WS_ACCOUNT_NUMBER', '510087585');
	define('FEDEX_WS_METER_NUMBER', '118558553');
	define('FEDEX_WS_KEY', 'XtWEnfJ9zJtXcVqh');
	define('FEDEX_WS_PASSWORD', 'fAAYhgoHqnx1qBO7x6A70UHML'); //MMD - these were dev server credentials */
	define('FEDEX_WS_ACCOUNT_NUMBER', '241484688');
	define('FEDEX_WS_METER_NUMBER', '103249688');
	define('FEDEX_WS_KEY', 'QHzfbwfYAEdH6Ncd');
	define('FEDEX_WS_PASSWORD', 'hT7caRuSDbaSUpHE4zM0cEqRa');
	$fws_client_options['trace'] = true;
}
else{ //PRODUCTION
	define('FEDEX_WS_ACCOUNT_NUMBER', '241484688');
	define('FEDEX_WS_METER_NUMBER', '103249688');
	define('FEDEX_WS_KEY', 'QHzfbwfYAEdH6Ncd');
	define('FEDEX_WS_PASSWORD', 'hT7caRuSDbaSUpHE4zM0cEqRa');
	$fws_client_options['trace'] = true;
}

/*******
 * Functions below here are only utility functions for the
 * functions above
 *******/
function _fws_get_client($wsdl){
	global $fws_client_options;
	$wsdl_array = array('RATE' => DIR_FS_CATALOG . DIR_WS_INCLUDES . "wsdl/RateService_v10.wsdl",
				'SHIP' => DIR_FS_CATALOG . DIR_WS_INCLUDES . "wsdl/ShipService_v10.wsdl");
	ini_set("soap.wsdl_cache_enabled", "0");
	return new SoapClient($wsdl_array[$wsdl], $fws_client_options);
}
function _fws_get_request() {
	$request = array();
	$request['WebAuthenticationDetail'] = array(
		'UserCredential' =>array(
			'Key' => FEDEX_WS_KEY, 
			'Password' => FEDEX_WS_PASSWORD
		)
	); 
	$request['ClientDetail'] = array(
		'AccountNumber' => FEDEX_WS_ACCOUNT_NUMBER, 
		'MeterNumber' => FEDEX_WS_METER_NUMBER
	);
	return $request;
}
function _fws_get_office_address(){
	$address = array(
		'Contact' => array(
			'PersonName' => 'Shipping Department',
			'CompanyName' => 'CablesAndKits.com',
			'PhoneNumber' => '8666220223'),
		'Address' => array(
			'StreetLines' => array('4555 Atwater Ct', 'Suite A'),
			'City' => 'Buford',
			'StateOrProvinceCode' => 'GA',
			'PostalCode' => '30518',
			'CountryCode' => 'US')
	);

	return $address;//Add Origin Address
}	
function _fws_build_address($address){ // this param should be an instance of Ck_Address
	$address_arr = array(
		'Contact' => array(
			'PersonName' => $address->name,
			'CompanyName' => $address->company,
			'PhoneNumber' => $address->telephone),
		'Address' => array(
			'StreetLines' => array($address->street_address, $address->suburb),
			'City' => $address->city,
			'PostalCode' => $address->postcode,
			'CountryCode' => !empty($address->country)?@ck_address2::get_country($address->country)['countries_iso_code_2']:'', //$address->getCountryCode(), //
		)
	);
	//MMD - do this because we might not be able to get a state code
	try{
		$state_code = $address->getStateCode();
		if (strlen(trim($state_code)) <= 2) {
			$address_arr['Address']['StateOrProvinceCode'] = $state_code;
		}
	}catch(Exception $e) {
		//if we have an exception, we don't supply a state - the API limits the size of the state code to 2 characters
	}

	return $address_arr;//Add Origin Address
}	
