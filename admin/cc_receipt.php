<?php
require('includes/application_top.php');

$order = new ck_sales_order($_GET['oID']);

$billing = $order->get_billing_address();
$shipping = $order->get_shipping_address();

if ($order->is('legacy_order')) {
	$transaction = prepared_query::fetch("SELECT ccl.transaction_id, ccl.datetime as transaction_date, ccl.amount, cc.credit_card_number_masked, cct.name as payment_method, cca.description as transaction_type FROM credit_card_log ccl LEFT JOIN credit_card cc ON cc.id = ccl.credit_card_id LEFT JOIN credit_card_type cct ON cct.id = cc.credit_card_type_id LEFT JOIN credit_card_action cca ON cca.code = ccl.action WHERE ccl.order_id = :orders_id AND ccl.action IN ('C', 'R') ORDER BY ccl.id DESC LIMIT 1", cardinality::ROW, [':orders_id' => $order->id()]);

	$transaction['transaction_date'] = new DateTime($transaction['transaction_date']);

	if ($transaction['transaction_type'] == 'Refund') {
		$amount = '($'.$transaction['amount'].')';
		$trans_type = 'Refund';
	}
	else {
		$amount = '$'.$transaction['amount'];
		$trans_type = 'Charge';
	}
}
else {
	$paymentSvcApi = new PaymentSvcApi();

	$transactionData = json_decode($paymentSvcApi->getTransactionDetails($order->get_header('paymentsvc_id')), TRUE);
	$tokenData = json_decode($paymentSvcApi->findToken($order->get_header('paymentsvc_id')), TRUE);
	if ($tokenData['result']['status'] === 'success') $cardData = json_decode($paymentSvcApi->findCard($tokenData['result']['cardType']), TRUE);

	$transaction_date = new DateTime($transactionData['result']['updated_at']['date'], new DateTimeZone($transactionData['result']['updated_at']['timezone']));
	$transaction_date->setTimeZone(new DateTimeZone('America/New_York'));

	$transaction = [
		'transaction_id' => $order->get_header('paymentsvc_id'),
		'transaction_date' => $transaction_date,
		'credit_card_number_masked' => '************'.$cardData['result']['lastFour'],
		'payment_method' => $cardData['result']['cardType'],
	];

	$amount = '$'.number_format($transactionData['result']['amount'], 2);
	if ($transactionData['status'] == 'refunded') {
		$trans_type = 'Refund';
		$amount = '('.$amount.')';
	}
	else {
		$trans_type = 'Charge';
	}
}
?>
<script>
	window.resizeTo(800, 900);
</script>

<table width="700" style="border:1px solid #666;">
	<thead>
		<tr>
			<td colspan="2" style="padding:10px; font-weight:bold;">Credit Card Merchant Receipt</td>
		</tr>
		<tr>
			<td colspan="2"><hr></td>
		</tr>
		<tr>
			<td width="350">
				<strong>Merchant</strong><br>
				Cablesandkits.com<br>
				4555 Atwater Ct. Suite A<br>
				Buford, GA 30518<br>
			</td>
			<td>
				<strong>Contact Number:</strong><br>
				<?= $order->get_contact_phone(); ?>
			</td>
		</tr>
		<tr>
			<td colspan="2"><hr></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Order Date:</td>
			<td><?= $order->get_header('date_purchased')->format('m/d/Y'); ?></td>
		</tr>
		<tr>
			<td><strong>Order ID:</strong></td>
			<td><strong><?= $order->id(); ?></strong></td>
		</tr>
		<tr>
			<td>Charge/Ship Date:</td>
			<td><?= $transaction['transaction_date']->format('m/d/Y'); ?></td>
		</tr>
		<tr>
			<td>Transaction ID:</td>
			<td><?= $transaction['transaction_id']; ?></td>
		</tr>
		<tr>
			<td>Transaction Type:</td>
			<td><?= $trans_type; ?></td>
		</tr>
		<tr>
			<td>Card Number:</td>
			<td><?= $transaction['credit_card_number_masked']; ?></td>
		</tr>
		<tr>
			<td>Payment Method:</td>
			<td><?= $transaction['payment_method']; ?></td>
		</tr>
		<tr>
			<td><strong>Amount:</strong></td>
			<td><strong><?= $amount; ?></strong></td>
		</tr>
		<tr>
			<td colspan="2"><hr></td>
		</tr>
		<tr>
			<td><strong>Billing Information:</strong></td>
			<td><strong>Shipping Information:</strong></td>
		</tr>
		<tr>
			<td><?= $billing['name']; ?></td>
			<td><?= $shipping['name']; ?></td>
		</tr>
		<tr>
			<td><?= $billing['street_address_1']; ?></td>
			<td><?= $shipping['street_address_1']; ?></td>
		</tr>
		<tr>
			<td><?= $billing['city'].', '.$billing['state'].' '.$billing['zip']; ?></td>
			<td><?= $shipping['city'].', '.$shipping['state'].' '.$shipping['zip']; ?></td>
		</tr>
		<tr>
			<td><?= $billing['country']; ?></td>
			<td><?= $shipping['country']; ?></td>
		</tr>
	</tbody>
</table>