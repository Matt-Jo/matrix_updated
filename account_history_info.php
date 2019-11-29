<?php
require('includes/application_top.php');

if (empty($_SESSION['customer_id'])) {
	$_SESSION['navigation']->set_snapshot();
	CK\fn::redirect_and_exit('/login.php');
}
if (empty($_GET['order_id'])) CK\fn::redirect_and_exit('/account_history.php');
$ckorder = new ck_sales_order($_GET['order_id']);
if (!$ckorder->found()) CK\fn::redirect_and_exit('/account_history.php');
if ($ckorder->get_header('customers_id') != $_SESSION['customer_id']) CK\fn::redirect_and_exit('/account_history.php');

$_SESSION['order_id'] = $ckorder->id();

$paymentSvcApi = new PaymentSvcApi();

if (!empty($_SESSION['customer_extra_login_id'])) {
	$_SESSION['braintree_customer_id'] = prepared_query::fetch('SELECT braintree_customer_id from customers WHERE customers_id = :customers_id', cardinality::SINGLE, [':customers_id' => $_SESSION['customer_id']]);
}

if (empty($_SESSION['braintree_client_token'])) {
	$token = json_decode($paymentSvcApi->getToken(), true);
	$_SESSION['braintree_client_token'] = $token['braintree_client_token'];
}

$orderTotal = prepared_query::fetch("SELECT value as order_total_amt FROM orders_total WHERE class = 'ot_total' AND orders_id = :order_id", cardinality::SINGLE, [':order_id' => $ckorder->id()]);
$paymentMade = prepared_query::fetch('SELECT SUM(amount) as amt_paid FROM acc_payments_to_orders WHERE order_id = :order_id', cardinality::SINGLE, [':order_id' => $ckorder->id()]);

$_SESSION['amountDue'] = $amtDue = max($orderTotal - $paymentMade, 0);

if ($amtDue > 0) $_SESSION['balancePending'] = true;
else $_SESSION['balancePending'] = false;

if (isset($_POST['ajax'])) {
	$data = [
		'orderId' => $ckorder->id(),
		'amount' => $_SESSION['amountDue'],
		'customerId' => $_SESSION['braintree_customer_id'],
		'token' => $_POST['paymentToken'],
		'authorization' => true
	];

	$result = json_decode($paymentSvcApi->authorizeCCTransaction($data), true);

	if ($result['result']['status'] === 'submitted_for_settlement') {
		$transactionId = $result['result']['transactionId'];

		$payment_id = prepared_query::insert('INSERT INTO acc_payments (customer_id, payment_amount, payment_method_id, payment_ref, payment_date) VALUES (:customer_id, :amount, 1, :payment_ref, NOW())', [':customer_id' => $_SESSION['customer_id'], ':amount' => $amtDue, ':payment_ref' => $transactionId]);

		//insert into acc_payments_to_orders
		prepared_query::execute('INSERT INTO acc_payments_to_orders(payment_id, order_id, amount) values(:payment_id, :order_id, :amount)', [':payment_id' => $payment_id, ':order_id' => $ckorder->id(), ':amount' => $amtDue]);

		//update transaction id only if it is a child order.
		prepared_query::execute('UPDATE orders SET paymentsvc_id = :transaction_id WHERE orders_id = :orders_id', [':transaction_id' => $transactionId, ':orders_id' => $ckorder->id()]);
	}
	exit();
}

$breadcrumb->add('Customer Service', '/custserv.php');
$breadcrumb->add('My Account', '/account.php');
$breadcrumb->add('History', '/account_history.php');
$breadcrumb->add('Order #'.$_GET['order_id'], '/account_history_info?order_id='.$_GET['order_id']);

if (!empty($_SESSION['braintree_customer_id'])) {
	$customerData = json_decode($paymentSvcApi->getCustomerCards($_SESSION['braintree_customer_id']), true);

	//add all cards to
	$customer_cards = [];

	if ($cards = $customerData['result']['cards']) {
		foreach($cards as $card ) {
			$customer_cards[] = [
				'cardType' => $card['cardType'],
				'lastFour' => $card['lastFour'],
				'expired' => $card['expired'],
				'token' => $card['token'],
				'expirationDate' => $card['expirationDate'],
				'cardholderName' => $card['cardholderName']!==null?$card['cardholderName']:'',
				'imageUrl' => $card['cardimgUrl'],
				'privateCard' => false,
				'editCard' => false
			];
		}
	}

	//check if any card is private
	$privateCardData = json_decode($paymentSvcApi->getPrivateCards($_SESSION['braintree_customer_id']), true);

	if ($privateCardData['result']['status'] == 'success') {
		$privateCards = json_decode($privateCardData['result']['privateCards'], true);

		if ($_SESSION['customer_extra_login_id'] > 0) $custId = $_SESSION['customer_extra_login_id'];
		else $custId = intval($_SESSION['customer_id']);

		foreach ($customer_cards as &$customer_card) {
			$token = $customer_card['token'];
			foreach ($privateCards as $privateCard) {
				if (empty($privateCard['owner'])) continue;

				$owner = intval($privateCard['owner']);
				//we have a matching token
				if (@$privateCard['token'] == $token) {
					if ($owner != $custId) $customer_card['privateCard'] = true;
					if ($owner == $custId) $customer_card['editCard'] = true;
				}
			}
		}
	}
	$_SESSION['customer_cards'] = $customer_cards;
}

$content = 'account_history_info';
$javascript = 'popup_window_print.js';

require('templates/Pixame_v1/main_page.tpl.php');
?>
