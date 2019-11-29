<?php
chdir('../..');
require_once('includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

@ini_set("memory_limit","2048M");
set_time_limit(0);

$start = time();

include('admin/includes/data/pp2bt.php');

$paymentSvcApi = new PaymentSvcApi;

$cards = prepared_query::fetch("SELECT c.customers_id, CONCAT('CK', LPAD(c.customers_id, 9, '0')) as braintree_customer_id, c.customers_firstname as firstname, c.customers_lastname as lastname, c.customers_email_address as email, cc.reference_transaction_id as pnref_payment_method_token, cc.credit_card_number_masked, cc.credit_card_expiration_date, cct.name as cc_type FROM credit_card cc JOIN customers c ON cc.customer_id = c.customers_id LEFT JOIN credit_card_type cct ON cc.credit_card_type_id = cct.id WHERE cc.customer_visible = 1 AND DATE(cc.last_used) >= '2016-01-01'", cardinality::SET);

$customers = [];

$count = 0;

foreach ($cards as $idx => $card) {
	if (!in_array($card['pnref_payment_method_token'], $found_tokens)) continue;
	if (in_array($card['pnref_payment_method_token'], $failed_tokens)) continue;

	$count++;

	//if ($card['braintree_customer_id'] != 'CK000141011') continue;

	if (empty($customers[$card['braintree_customer_id']])) {
		$customers[$card['braintree_customer_id']] = [
			'braintree_customer_id' => $card['braintree_customer_id'],
			'customer_id' => $card['customers_id'],
			'firstname' => $card['firstname'],
			'lastname' => $card['lastname'],
			'email' => $card['email'],
			'cards' => []
		];

		prepared_query::execute('UPDATE customers SET braintree_customer_id = :braintree_customer_id WHERE customers_id = :customers_id', [':braintree_customer_id' => $card['braintree_customer_id'], ':customers_id' => $card['customers_id']]);
	}

	if ($card['cc_type'] == 'Amex') $card['cc_type'] = 'American Express';
	$expiration_date = new DateTime($card['credit_card_expiration_date']);

	$customers[$card['braintree_customer_id']]['cards'][] = [
		'card_type' => $card['cc_type'],
		'card_last4' => substr($card['credit_card_number_masked'], -4),
		'card_token' => $card['pnref_payment_method_token'],
		'card_expirationdate' => $expiration_date->format('m/Y')
	];

}
$customer_count = count($customers);
$customer_batches = array_chunk($customers, 500, TRUE);
$imp_failed = 0;
foreach ($customer_batches as $customers) {
	$result = json_decode($paymentSvcApi->migrateData($customers), TRUE);
	$imp_failed += count($result['failure']);
	foreach ($result['failure'] as $bci) {
		var_dump($customers[$bci]);
	}
}

var_dump(['count' => $count, 'imp_count' => $customer_count, 'found' => count($found_tokens), 'failed' => count($failed_tokens), 'imp_failed' => $imp_failed]);

/*exit();*/
?>
