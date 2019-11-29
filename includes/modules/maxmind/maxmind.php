<?php
require("CreditCardFraudDetection.php");

// Create a new CreditCardFraudDetection object
$ccfs = new CreditCardFraudDetection;

// Set inputs and store them in a hash
// See http://www.maxmind.com/app/ccv for more details on the input fields

// Enter your license key here (Required)
$h["license_key"] = "s2xINNwkdwMi";

// build data
/* -------------------------------------------------------------------------------------------
Modify this section only, unless you know what you're doing
-----------------------------------------------------------
------------------------------------------------------------------------------------------- */
$mxmnd_order_id = $insert_id;

$mxmnd_data_array = prepared_query::fetch('SELECT * FROM orders WHERE orders_id = ?', cardinality::ROW, array($mxmnd_order_id));
//billing lookup
!($country_code = prepared_query::fetch('SELECT countries_iso_code_2 FROM countries WHERE countries_name = ?', cardinality::SINGLE, array($mxmnd_data_array['billing_country'])))?$country_code=$mxmnd_data_array['billing_country']:NULL;
!($zone_code = prepared_query::fetch('SELECT zone_code FROM zones WHERE zone_name = ?', cardinality::SINGLE, array($mxmnd_data_array['billing_state'])))?$zone_code=$mxmnd_data_array['billing_state']:NULL;

//shipping lookup
!($shp_country_code = prepared_query::fetch('SELECT countries_iso_code_2 FROM countries WHERE countries_name = ?', cardinality::SINGLE, array($mxmnd_data_array['delivery_country'])))?$shp_country_code=$mxmnd_data_array['delivery_country']:NULL;
!($shp_zone_code = prepared_query::fetch('SELECT zone_code FROM zones WHERE zone_name = ?', cardinality::SINGLE, array($mxmnd_data_array['delivery_state'])))?$shp_zone_code=$mxmnd_data_array['delivery_state']:NULL;

$bank_id_num = substr($mxmnd_data_array['cc_number'], 0, 6);
$email = explode('@', $mxmnd_data_array['customers_email_address']);
$email_name = !empty($email[0])?$email[0]:NULL;
$email_domain = !empty($email[1])?$email[1]:NULL;
$phone = substr(preg_replace( '/\D/', '', $mxmnd_data_array['customers_telephone']), 0, 6);

/* ------------------------------------------------------------------------
NOTE - JASON SHINN 12/11/2012
-----------------------------
It looks like the addition of the Clearwire device is causing problems with
the fraud score. Our Clearwire IP is registering a high spam score, which
we're evaluating to see if that is a problem in and of itself. In the
meantime, we'll just find and replace the Clearwire IP with the DSL IP and
hopefully that will take care of the issue
-----------------------------
UPDATE
-----------------------------
This may have been a temporary issue, we're commenting this out and keeping an eye on it
------------------------------------------------------------------------ */
$client_ip = !empty($REMOTE_ADDR)?trim($REMOTE_ADDR):trim($_SERVER['REMOTE_ADDR']);
//$client_ip=='50.13.250.204'?$client_ip = '216.27.164.6':NULL;

// this is just a little convoluted cascade to try and find the forwarded IP if the client is using a transparent proxy.
$forwarded_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR'])&&trim($_SERVER['HTTP_X_FORWARDED_FOR'])?
		trim($_SERVER['HTTP_X_FORWARDED_FOR']):
		(isset($_SERVER['HTTP_CLIENT_IP'])&&trim($_SERVER['HTTP_CLIENT_IP'])?trim($_SERVER['HTTP_CLIENT_IP']):'');

// AVS and CVV codes to acceptible maxmind values
$codes = prepared_query::fetch('SELECT avs_code, cvv2_code FROM credit_card_log WHERE order_id = ?', cardinality::ROW, array($mxmnd_order_id));
$avs_result = '';
$cvv_result = '';
if (!empty($codes)) {
	$avs_result = in_array($codes['avs_code'], array('N', 'Y', 'A', 'P'))?$codes['avs_code']:'';
	$cvv_result = in_array($codes['cvv2_code'], array('M'))?'Y':'N';
}

// session
$session_id = session_id();

// transaction
$order_amount = '';
if (isset($order_totals)) {
	foreach ($order_totals as $line) {
		if ($line['code'] == 'ot_total') {
			$order_amount = $line['value'];
			break;
		}
	}
}
elseif (isset($_SESSION['cart'])) {
	foreach ($_SESSION['cart']->get_totals() as $total) {
		if ($total['class'] == 'ot_total') {
			$order_amount = $total['value'];
			break;
		}
	}
}
$txn_type = in_array($mxmnd_data_array['payment_method_id'], array(1,11))?'creditcard':(in_array($mxmnd_data_array['payment_method_id'], array(2))?'paypal':'other');

/* -------------------------------------------------------------------------------------------
END MODIFICATIONS
------------------------------------------------------------------------------------------- */

// Required fields
$h["i"] = $client_ip;											// set the client ip address
$h["city"] = $mxmnd_data_array['billing_city'];				// set the billing city
$h["region"] = $zone_code;									// set the billing state
$h["postal"] = $mxmnd_data_array['billing_postcode'];			// set the billing zip code
$h["country"] = $country_code;								// set the billing country

// Recommended fields
$h["domain"] = $email_domain;									// Email domain
$h["bin"] = $bank_id_num;										// bank identification number
$h["forwardedIP"] = $forwarded_ip;								// X-Forwarded-For or Client-IP HTTP Header
// CreditCardFraudDetection.php will take
// MD5 hash of e-mail address passed to emailMD5 if it detects '@' in the string
$h["emailMD5"] = $mxmnd_data_array['customers_email_address']; // full email address
// CreditCardFraudDetection.php will take the MD5 hash of the username/password if the length of the string is not 32
//$h["usernameMD5"] = "test_carder_username";					// user login username - looks like it's deprecated
//$h["passwordMD5"] = "test_carder_password";					// user login password - looks like it's deprecated

// Optional fields
//$h["binName"] = "MBNA America Bank";							// bank name
//$h["binPhone"] = "800-421-2110";								// bank customer service phone number on back of credit card
$h["custPhone"] = $phone;										// Area-code and local prefix of customer phone number
$h["requested_type"] = "premium";								// Which level (free, city, premium) of CCFD to use

$h["shipAddr"] = $mxmnd_data_array['delivery_street_address']; // Shipping Address
$h["shipCity"] = $mxmnd_data_array['delivery_city'];			// the City to Ship to
$h["shipRegion"] = $shp_zone_code;							// the Region to Ship to
$h["shipPostal"] = $mxmnd_data_array['delivery_postcode'];	// the Postal Code to Ship to
$h["shipCountry"] = $shp_country_code;						// the country to Ship to

$h["txnID"] = $mxmnd_order_id;									// Transaction ID (Order #)
$h['order_amount'] = $order_amount;							// the total transaction $ amount
$h['order_currency'] = $mxmnd_data_array['currency'];			// the currency denomination that we are being paid in (should always be USD at the moment)
//$h['shopID'] =												// our internal shop ID. Not relevant currently, we only have one shop
$h['txn_type'] = $txn_type;									// transaction processor... will either be creditcard or paypal

$h["sessionID"] = $session_id;								// Client Session ID
$h["accept_language"] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];		// Client Accept Language
$h["user_agent"] = $_SERVER['HTTP_USER_AGENT'];				// Client User Agent

$h['avs_result'] = $avs_result;								// the result of the CC AVS check
$h['cvv_result'] = $cvv_result;								// the result of the CC CVV check

// If you want to disable Secure HTTPS or don't have Curl and OpenSSL installed
// uncomment the next line
// $ccfs->isSecure = 0;

// set the timeout to be five seconds
$ccfs->timeout = 10;

// uncomment to turn on debugging
// $ccfs->debug = 1;

// how many seconds to cache the ip addresses
// $ccfs->wsIpaddrRefreshTimeout = 3600*5;

// file to store the ip address for minfraud3.maxmind.com, minfraud1.maxmind.com and minfraud2.maxmind.com
// $ccfs->wsIpaddrCacheFile = "/tmp/maxmind.ws.cache";

// if useDNS is 1 then use DNS, otherwise use ip addresses directly
$ccfs->useDNS = 1;

$ccfs->isSecure = 1;

$log = array();
$log['order_id'] = $insert_id;
$log['query'] = http_build_query($h);

// next we set up the input hash
$ccfs->input($h);
// then we query the server
$ccfs->query();
// then we get the result from the server
$h = $ccfs->output();

$log['response'] = http_build_query($h);

// these fields map to the same fields in the old API version
$log['score'] = $h['riskScore'];
$log['country_match'] = $h['countryMatch'];
$log['hi_risk'] = $h['highRiskCountry'];
$log['distance'] = $h['distance'];
$log['ip_city'] = $h['ip_city'];
$log['ip_region'] = $h['ip_region'];
$log['country_code'] = $h['countryCode'];
$log['ip_latitude'] = $h['ip_latitude'];
$log['ip_longitude'] = $h['ip_longitude'];
$log['ip_isp'] = $h['ip_isp'];
$log['ip_org'] = $h['ip_org'];
$log['anonymous_proxy'] = $h['anonymousProxy'];
$log['proxy_score'] = $h['proxyScore'];
$log['free_mail'] = $h['freeMail'];
$log['bin_match'] = $h['binMatch'];
$log['bin_country'] = $h['binCountry'];
$log['bin_name'] = $h['binName'];
$log['cust_phone'] = $h['custPhoneInBillingLoc'];
$log['err'] = $h['err'];
$log['spam_score'] = @$h['score']; // we're flipping this and riskScore, so that we can maintain a common point of reference... probably won't matter if score isn't even being returned in the latest API

// these fields are new
$log['ip_accuracy_radius'] = $h['ip_accuracyRadius'] ?: NULL;
$log['ip_region_name'] = $h['ip_regionName'] ?: NULL;
$log['ip_postal_code'] = $h['ip_postalCode'] ?: NULL;
$log['ip_metro_code'] = $h['ip_metroCode'] ?: NULL;
//$log['ip_area_code'] = $h['ip_areaCode']; // deprecated
$log['ip_country_name'] = $h['ip_countryName'] ?: NULL;
$log['ip_continent_code'] = $h['ip_continentCode'] ?: NULL;
$log['ip_time_zone'] = $h['ip_timeZone'] ?: NULL;
$log['ip_asnum'] = $h['ip_asnum'] ?: NULL;
$log['ip_user_type'] = $h['ip_userType'] ?: NULL;
$log['ip_net_speed_cell'] = $h['ip_netSpeedCell'] ?: NULL;
$log['ip_domain'] = $h['ip_domain'] ?: NULL;
$log['ip_city_conf'] = $h['ip_cityConf'] ?: NULL;
$log['ip_region_conf'] = $h['ip_regionConf'] ?: NULL;
$log['ip_postal_conf'] = $h['ip_postalConf'] ?: NULL;
$log['ip_country_conf'] = $h['ip_countryConf'] ?: NULL;
$log['is_trans_proxy'] = @$h['isTransProxy'] ?: NULL;
$log['ip_corporate_proxy'] = $h['ip_corporateProxy'] ?: NULL;
$log['carder_email'] = @$h['carderEmail'] ?: NULL;
//$log['high_risk_username'] = $h['highRiskUsername']; // deprecated
//$log['high_risk_password'] = $h['highRiskPassword']; // deprecated
$log['bin_name_match'] = $h['binNameMatch'] ?: NULL; // we're not sending the binName, since we're not collecting it from our customers, so this will always be 'NA'
$log['bin_phone_match'] = $h['binPhoneMatch'] ?: NULL; // we're not sending the binPhone, since we're not collecting it from our customers, so this will always be 'NA'
$log['bin_phone'] = $h['binPhone'] ?: NULL;
$log['prepaid'] = $h['prepaid'] ?: NULL;
$log['ship_forward'] = @$h['shipForward'] ?: NULL;
$log['city_postal_match'] = $h['cityPostalMatch'] ?: NULL;
$log['ship_city_postal_match'] = $h['shipCityPostalMatch'] ?: NULL;
$log['queries_remaining'] = $h['queriesRemaining'] ?: NULL;
$log['maxmind_id'] = $h['maxmindID'] ?: NULL;
$log['minfraud_version'] = $h['minfraud_version'] ?: NULL;
$log['service_level'] = $h['service_level'] ?: NULL;

try {
	$insert = new prepared_fields($log, prepared_fields::INSERT_QUERY);
	prepared_query::execute('INSERT INTO orders_maxmind ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
}
catch (Exception $e) {
	echo "DATABASE INSERT ERROR: ".$e->getMessage()."\n";
}
?>
