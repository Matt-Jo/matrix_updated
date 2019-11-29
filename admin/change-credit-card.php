<?php
require_once('includes/application_top.php');

$paymentSvcApi = new PaymentSvcApi();
$order = new ck_sales_order($_REQUEST['order_id']);

$customer_cards = [];
$selected_card = NULL;

$data = json_decode($paymentSvcApi->findToken($order->get_header('paymentsvc_id')), TRUE);
$selectedCardToken = $data['result']['cardType'];

$order_owner = $order->get_header('customers_id');
if (!empty($order->get_header('customers_extra_logins_id'))) $order_owner .= '-'.$order->get_header('customers_extra_logins_id');

if ($braintree_customer_id = $order->get_customer()->get_header('braintree_customer_id')) {
	$customerData = json_decode($paymentSvcApi->getCustomerCards($braintree_customer_id), TRUE);

	//add all cards to
	$cards = $customerData['result']['cards'];

	foreach ($cards as $card) {
		$cc = [
			'cardType' => $card['cardType'],
			'lastFour' => $card['lastFour'],
			'expired' => $card['expired'],
			'token' => $card['token'],
			'expirationDate' => $card['expirationDate'],
			'cardholderName' => $card['cardholderName']!==null?$card['cardholderName']:'',
			'imageUrl' => $card['cardimgUrl'],
			'privateCard' => FALSE, // this appears to be incompletely implemented
			'editCard' => FALSE, // this appears to be incompletely implemented
			'hide_card' => $card['hide_card']
		];

		if ($card['hide_card'] && $card['owner'] != $order_owner) $cc['privateCard'] = TRUE;

		if ($card['token'] == $selectedCardToken) $selected_card = $cc;
		$customer_cards[] = $cc; // allow re-charging the existing card
	}
}

if (!empty($_POST['action'])) {
	$action = !empty($_POST['action'])?$_POST['action']:NULL;

	if ($action == 'save_existing') {
		// auth new card
		// assign new card to order
		// void previous auth
		if ($_POST['cc_id'] == '0') {
			echo json_encode(['error' => 'Please select a valid option from the dropdown.']);
			die();
		}

		$order = new ck_sales_order($_POST['order_id']);
		$cc_token = $_POST['cc_id'];

		try {
			if (!empty($order->get_header('paymentsvc_id'))) $paymentSvcApi->voidTransaction($order->get_header('paymentsvc_id'));

			$data = [
				'amount' => $order->get_simple_totals('total'),
				'customerId' => $order->get_customer()->get_header('braintree_customer_id'),
				'token' => $cc_token,
				'authorization' => FALSE,
				'orderId' => $order->id()
			];

			$auth_result = json_decode($paymentSvcApi->authorizeCCTransaction($data), TRUE);

			if ($auth_result['result']['status'] == 'failed') {
				$messageStack->add_session($auth_result['result']['message'], 'error');
				echo json_encode(['error' => $auth_result['result']['message']]);
			}
			elseif (empty($auth_result['result']['transactionId'])) {
				ob_start();
				var_dump($auth_result);
				$msg = ob_get_clean();

                $mailer = service_locator::get_mail_service();
                $mail = $mailer->create_mail()
                    ->set_subject('CC Processing Error')
                    ->add_to('jason.shinn@cablesandkits.com')
                    ->set_from('jason.shinn@cablesandkits.com')
                    ->set_body($msg);                
                $mailer->send($mail);

				$messageStack->add_session($auth_result['result']['message'], 'error');
				echo json_encode(['error' => $auth_result['result']['message']]);
			}
			else {
				prepared_query::execute('UPDATE orders SET paymentsvc_id = :paymentsvc_id, legacy_order = :legacy_order WHERE orders_id = :orders_id', [':paymentsvc_id' => $auth_result['result']['transactionId'], ':orders_id' => $order->id(), ':legacy_order' => 0]);
				$messageStack->add_session('Card updated successfully', 'success');
				echo json_encode(['id' => 'success']);
			}

			die();
		}
		catch (Exception $e) {
			echo json_encode(['error' => $e->getMessage()]);
			die();
		}
	}
} ?>
<div style="width: 525px; margin: 15px;">
	<form id="existing-credit-card-change">
		<fieldset>
			<legend>Choose Existing Card</legend>
			<select name="cc_id" id="cc_id">
				<option value="0">Choose One</option>
				<?php foreach ($customer_cards as $card) {
					if ($card['privateCard']) continue; ?>
				<option value="<?= $card['token']; ?>">
					<?= $card['cardType']; ?> -
					xxxxxxxxxxxx<?= $card['lastFour']; ?> -
					Exp. <?= $card['expirationDate']; ?>
				</option>
				<?php } ?>
			</select>
			<input id="existing-change-credit-card-button" type="button" value="Submit" style="float: right;">
		</fieldset>

		<?php if (!empty($selected_card)) { ?>
		<fieldset>
			<legend>Currently Selected Card:</legend>
			<p>
				<?= $selected_card['cardType']; ?> -
				xxxxxxxxxxxx<?= $selected_card['lastFour']; ?> -
				Exp. <?= $selected_card['expirationDate']; ?>
			</p>
		</fieldset>
		<?php } ?>
	</form>
	<p id="change-cc-info-message" style="text-align: center; display: none;">Please Wait</p>
</div>
