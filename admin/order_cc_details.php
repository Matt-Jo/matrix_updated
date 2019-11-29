<?php
require_once('includes/application_top.php');
$paymentSvcApi = new PaymentSvcApi();

$oID = $_GET['orderId'];
$order = new ck_sales_order($oID);

if ($order->is('legacy_order')) {
	$order_authorized = false;
	$order_captured = false;

	$auth_amount = 0.00;
	$capture_amount = 0.00;

	$find_auth_trans_result = prepared_query::fetch("SELECT ccl.transaction_id, cc.credit_card_owner, cc.credit_card_number_masked, cct.name as card_type_name, avs.description as avs, cvv2.description as cvv2 FROM credit_card_log ccl JOIN credit_card cc ON ccl.credit_card_id = cc.id JOIN credit_card_type cct ON cc.credit_card_type_id = cct.id LEFT JOIN credit_card_cvv2_code cvv2 ON cvv2.code = ccl.cvv2_code LEFT JOIN credit_card_avs_code avs ON avs.code = ccl.avs_code WHERE action = 'A' AND result = 'A' AND order_id = :order_id ORDER BY ccl.datetime DESC LIMIT 1", cardinality::ROW, [':order_id' => $oID]);
	$find_last_payment_status_result = prepared_query::fetch('SELECT action from credit_card_log where order_id = :order_id ORDER BY id DESC LIMIT 1', cardinality::ROW, [':order_id' => $oID]);
}
else {
	$paymentsvc_id = $order->get_header('paymentsvc_id');

	if (empty($paymentsvc_id)) {
		echo 'No CC Payment Found';
		die();
	}

	$order_authorized = false;
	$order_captured = false;

	$auth_amount = 0.00;
	$capture_amount = 0.00;

	// get transaction status
	$transactionData = json_decode($paymentSvcApi->getTransactionDetails($paymentsvc_id), true);
	//given paymentsvc_id get the token id.....
	$data = json_decode($paymentSvcApi->findToken($paymentsvc_id), true);
	$cardToken = $data['result']['cardType'];
	$status = $data['result']['status'];

	if ($status === 'success') $data = json_decode($paymentSvcApi->findCard($cardToken), true);

	$order_authorized = false;
	if (in_array($transactionData['status'], ['authorized', 'settled', 'settlement_pending', 'submitted_for_settlement'])) $order_authorized = true;
	if (in_array($transactionData['status'], ['settled', 'settlement_pending', 'submitted_for_settlement'])) $order_captured = true;
	if ($order_authorized) $auth_amount = $transactionData['result']['amount'];
	if ($order_captured) $capture_amount = $transactionData['result']['amount'];
} ?>
<table>
	<tr>
		<td class="main"><b>Card #:</b></td>
		<td class="main">
			<?php if ($order->is('legacy_order')) echo $find_auth_trans_result['credit_card_number_masked'];
			else echo '***********'.$data['result']['lastFour']; ?>
		</td>
	</tr>
	<tr>
		<td class="main"><b>Name on Card:</b></td>
		<td class="main">
			<?php if ($order->is('legacy_order')) echo $find_auth_trans_result['credit_card_owner'];
			else echo $data['result']['cardholderName']; ?>
		</td>
	</tr>
	<tr>
		<td class="main"><b>Card Type:</b></td>
		<td class="main">
			<?php if ($order->is('legacy_order')) echo $find_auth_trans_result['card_type_name'];
			else echo $data['result']['cardType']; ?>
		</td>
	</tr>
	<?php if ($order->is('legacy_order')) { ?>
	<tr>
		<td class="main"><b>AVS:</b></td>
		<td class="main"><?= $find_auth_trans_result['avs']; ?></td>
	</tr>
	<tr>
		<td class="main"><b>CVV2:</b></td>
		<td class="main"><?= $find_auth_trans_result['cvv2']; ?></td>
	</tr>
	<?php }
	else { ?>
	<tr>
		<td class="main"><b>AVS:</b></td>
		<td class="main">
			Postal Code: <?= PaymentSvcApi::$avs_postal_response_codes[$transactionData['result']['avs']['postal_code_response_code']]; ?><br>
			Street Addr: <?= PaymentSvcApi::$avs_street_response_codes[$transactionData['result']['avs']['street_address_response_code']]; ?>
		</td>
	</tr>
	<tr>
		<td class="main"><b>CVV2:</b></td>
		<td class="main"><?= PaymentSvcApi::$cvv_response_codes[$transactionData['result']['cvv_response_code']]; ?></td>
	</tr>
	<?php }

	if(!empty($paymentsvc_id)) { ?>
	<input type="hidden" id="paymentsvcId" value="<?= $paymentsvc_id; ?>">
	<?php }

	if (in_array($_SESSION['perms']['admin_groups_id'], [1, 5, 7, 8, 9, 10, 11, 17, 19, 20, 21, 24, 29, 31])) { ?>
	<tr>
		<td class="main"><b>Authorized:</b></td>
		<td class="main">
			<img id="indicator-authorized" style="vertical-align:middle;" src="images/icons/<?= $order_authorized?'tick.gif':'cross.gif'; ?>" alt="<?= $order_authorized?'Yes':'No'; ?>">
			<?php if (empty($order_authorized)) { ?>
			<a id="linkAuth" href="#">Authorize Card</a>
			<?php } ?>

			<div id="authModal" class="jqmWindow">
				Authorize new amount:
				<input id="auth_amt" type="text" name="auth_amt" style="width: 75px;" value="<?= $auth_amount<$order->get_total()?sprintf('%.2F', $order->get_total()-$auth_amount):''; ?>">
				<input id="auth_amt_process" type="button" name="auth_amt_process" value="Authorize">
				<img id="auth-throbber" style="display:none;" src="images/icons/throbber.gif" alt="Working"><br>
			</div>
		</td>
	</tr>
	<?php }
	
	if (in_array($_SESSION['perms']['admin_groups_id'], [1, 5, 7, 8, 9, 10, 11, 17, 19, 20, 21, 24, 29, 31])) { ?>
	<tr>
		<td class="main"><b>Charged:</b></td>
		<td class="main">
			<img id="indicator-authorized" style="vertical-align:middle;" src="images/icons/<?= $order_captured?'tick.gif':'cross.gif'; ?>" alt="<?= $order_captured?'Yes':'No'; ?>">
			<?php if (!empty($order_authorized) && empty($order_captured)) { ?>
			<a id="linkCapture" href="#">Charge Card</a>
			<?php } ?>

			<div id="captureModal" class="jqmWindow">
				Charged for: <span style="margin-left: 65px;"><?= money_format('%n', $capture_amount); ?></span><br>
				Charge new amount:
				<input id="capture_amt" type="text" name="capture_amt" style="width: 75px;" value="<?= $capture_amount<$order->get_total()?sprintf('%.2F', $order->get_total()-$capture_amount):''; ?>">
				<input id="capture_amt_process" type="button" name="capture_amt_process" value="Charge">
				<img id="capture-throbber" style="display: none;" src="images/icons/throbber.gif" alt="Working"><br>
			</div>
		</td>
	</tr>
	<?php }
	
	if ($order_captured) { ?>
	<tr>
		<td class="main"><strong>Receipt:</strong></td>
		<td class="main"><img src="images/receipt.jpg"> <a target="_blank" href="cc_receipt.php?oID=<?= $oID; ?>">Receipt</a></td>
	</tr>
	<?php } ?>
</table>
<script type="text/javascript">
	/*jQuery(document).ready(function($) {
		charge = function() {
			id = $('#paymentsvcId').val();

			jQuery.ajax({
				url: 'orders_new.php',
				type: 'POST',
				data: { chargeCard:true, paymentscvId:id },
				timeout: 10000,
				success: function(data) {
					//debugger;
					location.reload();
				},
				error: function(obj) {
					alert('There was a communication error. Please wait at least 1 minute and reload the screen to see if it went through, and try again if necessary.');
				}
			});
		}
	});*/
</script>
