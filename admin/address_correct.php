<?php
$oid = intval(strip_tags($_GET['oid']));
$act = strip_tags($_GET['action']);
$actionArr = array('correct_ship','save_ship','reset_states','display_error','display_ship');

if (empty($oid)) die("Invalid Order Number");

if (!$act || !in_array($act, $actionArr, true)) die("Invalid Action");

require_once('includes/application_top.php');
require_once("includes/classes/address_check.php");
require_once("includes/classes/order.php");

$ad = new address_check();
$order = new order($oid);
$order->delivery['orders_id'] = $oid;

$countryArr = $ad->getCountries();
$country = @$countryArr[intval(strip_tags($_GET['country']))]['countries_name'];
$statesArr = $ad->getStates(intval(strip_tags(@$_GET['country'])));
$state = (!intval(strip_tags(@$_GET['state'])) && strip_tags(@$_GET['state_name'])) ? strip_tags(@$_GET['state_name']) : @$statesArr[intval(strip_tags(@$_GET['state']))]['zone_name'];

switch ($act) {
	case 'display_error':
		$errArr = array('company' =>'The company name may not be over 35 characters long!',
						'usrname' => 'A name is required and it may not be over 35 characters long!',
						'address1' => 'An address is required. It may not be over 35 characters long and it cannot be a PO Box!', # 410
						'address2' => 'The 2nd address line may not be over 35 characters long!',
						'state' => 'A valid state code is required!',
						'city' => 'City is required and it may not be over 20 characters long!',
						'postcode' => 'A valid postal code is required! US Zip codes must either be 5 or 9 digits long',
						'country' => 'A valid country is required!',
						'telephone' => 'The phone number must contain 10 digits!');
		if ($errArr[strip_tags($_GET['fldid'])]) {
			echo $errArr[strip_tags($_GET['fldid'])];
		} else {
			echo "This error needs to be corrected!";
		}
		break;

	case 'reset_states':
		$addrArr = array();
		$addrArr['country'] = $country;
		$addrArr['state'] = $state;
		$addrArr['orders_id'] = $oid;
		echo $ad->mkStateDiv($statesArr, $addrArr);;
		break;

	case 'correct_ship':
		echo $ad->edit_ship_address($order->delivery);
		break;

	case 'save_ship';
		$sales_order = new ck_sales_order($oid);

		$telephone = $ad->cleanPhone($_GET['telephone']);

		$address_data = [
			'delivery_company' => myTrim($_GET['company']),
			'delivery_name' => myTrim($_GET['name']),
			'delivery_street_address' => myTrim($_GET['street_address']),
			'delivery_suburb' => myTrim($_GET['suburb']),
			'delivery_city' => myTrim($_GET['city']),
			'delivery_postcode' => myTrim($_GET['postcode']),
			'delivery_state' => myTrim($state),
			'delivery_country' => myTrim($country),
			'delivery_telephone' => myTrim($telephone),
		];

		$sales_order->update($address_data);
		$sales_order->refigure_totals();

		# Do not break to disp div content from default below
		#break;

	default:
		echo $ad->disp_ship_address($order->delivery);
		break;
}

function myTrim($str) {
	$str = stripslashes($str);
	$str = htmlentities($str, ENT_QUOTES);
	$str = strip_tags($str);
	$str = trim($str);
	return $str;
}
?>
