<?php
require('includes/application_top.php');

if (empty($_SESSION['customer_id'])) {
	$_SESSION['navigation']->set_snapshot();
	CK\fn::redirect_and_exit('/login.php');
}

$paymentSvcApi = new PaymentSvcApi();
$c = new ck_customer2($_SESSION['customer_id']);

$_SESSION['braintree_customer_id'] = $c->get_header('braintree_customer_id');
if (!empty($_SESSION['braintree_customer_id'])) {
	$btc = json_decode($paymentSvcApi->getCustomer($_SESSION['braintree_customer_id']), TRUE);

	if ($btc['result']['status'] != 'success') $_SESSION['braintree_customer_id'] = NULL;
}
if (empty($_SESSION['braintree_customer_id'])) {
	$custData = [
		'owner' => $c->id(),
		'firstname' => $c->get_header('first_name'),
		'lastname' => $c->get_header('last_name'),
		'email' => $c->get_header('email_address'),
		'token' => NULL
	];

	$result = json_decode($paymentSvcApi->createCustomer($custData), true);

	if ($result['result']['status'] == 'success') {
		$braintree_customer_id = $result['result']['CustomerId'];
		prepared_query::execute('UPDATE customers SET braintree_customer_id = :braintree_customer_id WHERE customers_id = :customers_id', [':braintree_customer_id' => $braintree_customer_id, ':customers_id' => $c->id()]);

		//and set $_SESSION['braintree_customer_id']
		$_SESSION['braintree_customer_id'] = $braintree_customer_id;
	}
}

//customer id to use while adding card
if (isset($_SESSION['customer_extra_login_id']) && ($_SESSION['customer_extra_login_id'] > 0)) $_SESSION['matrix_cust_id'] = $_SESSION['customer_extra_login_id'];
else $_SESSION['matrix_cust_id'] = $_SESSION['customer_id'];

//get brintree client token
if (empty($_SESSION['braintree_client_token'])) {
	$token =  json_decode($paymentSvcApi->getToken(), true);
	$_SESSION['braintree_client_token'] = $token['braintree_client_token'];
}

//get customer cards
if (!empty($_SESSION['braintree_customer_id'])) {
	$customerData = $paymentSvcApi->getCustomerCards($_SESSION['braintree_customer_id']);
	//echo $customerData;
	$customerData = json_decode($customerData, TRUE);
	//var_dump($customerData);

	//add all cards to
	$customer_cards = [];

	if (!empty($customerData['result']['cards'])) {
		foreach ($customerData['result']['cards'] as $card) {
			if ($card['hide_card'] && $card['owner'] != $_SESSION['cart']->get_cart_key()) continue;
			$customer_cards[] = [
				'cardType' => $card['cardType'],
				'lastFour' => $card['lastFour'],
				'expired' => $card['expired'],
				'token' => $card['token'],
				'expirationDate' => $card['expirationDate'],
				'cardholderName' => $card['cardholderName']!==null?$card['cardholderName']:'',
				'imageUrl' => $card['cardimgUrl'],
				'privateCard' => FALSE,
				'editCard' => FALSE
			];
		}
	}

	/*//check if any card is private
	$privateCardData = json_decode($paymentSvcApi->getPrivateCards($_SESSION['braintree_customer_id']), TRUE);

	if ($privateCardData['result']['status'] == 'success') {
		$privateCards = json_decode($privateCardData['result']['privateCards'], true);

		if ($_SESSION['customer_extra_login_id'] > 0) $custId = $_SESSION['customer_extra_login_id'];
		else $custId = intval($_SESSION['customer_id']);

		foreach ($customer_cards as &$card) {
			$token = $card['token'];

			foreach ($privateCards as $pcard) {
				if (empty($pcard['owner'])) continue;

				$owner = intval($pcard['owner']);

				//we have a matching token
				if ($pcard['token'] == $token) {
					if ($owner != $custId) $card['privateCard'] = true;
					else $card['editCard'] = true;
				}
			}
		}
	}*/

	$_SESSION['customer_cards'] = $customer_cards;
}

if (!empty($_POST['action']) && $_POST['action'] == 'process') {
	$cc_id = $_POST['cc_id'];
	prepared_query::execute('UPDATE credit_card SET disabled = 1, customer_visible = 0 WHERE id = :cc_id', [':cc_id' => $cc_id]);

	$messageStack->add_session('account', 'The credit card has been removed.', 'success');

	CK\fn::redirect_and_exit('/account_manage_ccs.php');
}

if (!empty($_GET['action']) && $_GET['action'] == 'delete') {
	$result = json_decode($paymentSvcApi->deleteCard($_GET['token']), TRUE);

	if (isset($result['result'])) $messageStack->add_session('account', 'The credit card has been removed.', 'success');
	else $messageStack->add_session('account', 'Removing the credit card has failed.', 'error');

	CK\fn::redirect_and_exit('/account_manage_ccs.php');
}

$breadcrumb->add('Customer Service', '/custserv.php');
$breadcrumb->add('My Account', '/account.php');
$breadcrumb->add('Manage Credit Cards', '/account_manage_ccs.php');

$content = 'account_manage_ccs';

require('templates/Pixame_v1/main_page.tpl.php');
?>
