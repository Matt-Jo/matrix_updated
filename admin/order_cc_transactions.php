<?php
require('includes/application_top.php');

$oID = $_POST['order_id'];
$order = new ck_sales_order($oID);

$response = ['result' => TRUE, 'message' => ''];

if ($order->is('legacy_order')) {
	$response['result'] = FALSE;
	$response['message'] = 'This is an old order - see dev to complete your action.';
}
else {
	$paymentSvcApi = new PaymentSvcApi();

	try {
		switch ($_POST['action']) {
			case 'auth':
				if (!empty($order->get_header('paymentsvc_id'))) $paymentSvcApi->voidTransaction($order->get_header('paymentsvc_id'));

				$cardToken = json_decode($paymentSvcApi->findToken($order->get_header('paymentsvc_id')), TRUE);

				if ($cardToken['result']['status'] === 'success') {
					//success ...now create a new authorization

					$data = [
						'amount' => $order->get_simple_totals('total'),
						'customerId' => $order->get_customer()->get_header('braintree_customer_id'),
						'token' => $cardToken['result']['cardType'],
						'authorization' => FALSE,
						'orderId' => $order->id()
					];

					$auth_result = json_decode($paymentSvcApi->authorizeCCTransaction($data), TRUE);

					if ($auth_result['result']['status'] == 'failed') {
						$response['result'] = FALSE;
						$response['message'] = $auth_result['result']['message'];
					}
					elseif (empty($auth_result['result']['transactionId'])) {
						ob_start();
						var_dump($auth_result);
						$msg = ob_get_clean();
                        $mailer = service_locator::get_mail_service();
                        $mail = $mailer->create_mail()
                        ->set_subject('CC Processing Error')
                        ->set_from('jason.shinn@cablesandkits.com')
                        ->add_to('jason.shinn@cablesandkits.com')
                        ->set_body($msg);
                        $mailer->send($mail);

						$response['result'] = FALSE;
						$response['message'] = $auth_result['result']['message'];
					}
					else {
						prepared_query::execute('UPDATE orders SET paymentsvc_id = :paymentsvc_id WHERE orders_id = :orders_id', [':paymentsvc_id' => $auth_result['result']['transactionId'], ':orders_id' => $order->id()]);
					}
				}
				else {
					$response['result'] = FALSE;
					$response['message'] = 'Card could not be found to re-auth';
				}
				break;
			case 'capture':
				$amount = floatval($_POST['amount']);
				$paymentsvc_id = $order->get_header('paymentsvc_id');

				/*if (empty($paymentsvc_id) && $order->has_parent_orders()) {
					$parent_order = array_pop($order->get_parent_orders());
					$paymentsvc_id = $parent_order->get_header('paymentsvc_id');
				}*/

				if ($amount <= 0) {
					$response['result'] = FALSE;
					$response['message'] = 'Cannot charge $0';
					break;
				}

				if (empty($paymentsvc_id)) {
					$response['result'] = FALSE;
					$response['message'] = 'No active payment record was found - please select a credit card';
					break;
				}

				$data = [
					'transactionId' => $paymentsvc_id,
					'amount' => $amount,
					'orderId' => $order->id()
				];

				$settlement_result = json_decode($paymentSvcApi->settleTransaction($data), TRUE);

				$response['result_status'] = $settlement_result['result']['status'];

				//var_dump([$data, $settlement_result]);

				if (!empty($settlement_result['authorization_expired']) || !in_array($settlement_result['result']['status'], ['submitted_for_settlement', 'settlement_pending'])) {
					//get the card token used for the transaction
					$cardToken = json_decode($paymentSvcApi->findToken($paymentsvc_id), TRUE);

					if ($cardToken['result']['status'] !== 'success') {
						$response['result'] = FALSE;
						$response['message'] = 'Auth expired, card could not be found to re-auth';
						break;
					}

					//success ...now create a new authorization
					$data = [
						'amount' => $amount,
						'customerId' => $order->get_customer()->get_header('braintree_customer_id'),
						'token' => $cardToken['result']['cardType'],
						'authorization' => TRUE,
						'orderId' => $order->id()
					];

					$auth_result = json_decode($paymentSvcApi->authorizeCCTransaction($data), TRUE);

					if ($auth_result['result']['status'] === 'submitted_for_settlement') {
						prepared_query::execute('UPDATE orders SET paymentsvc_id = :paymentsvc_id WHERE orders_id = :orders_id', [':paymentsvc_id' => $auth_result['result']['transactionId'], ':orders_id' => $order->id()]);
						ck_payment::legacy_insert_credit($order->get_customer()->id(), $order->id(), 'creditcard_pp', $auth_result['result']['transactionId'], $amount);
					}
					else {
						$response['result'] = FALSE;
						$response['message'] = 'Auth expired, new auth failed.';
					}
				}
				else ck_payment::legacy_insert_credit($order->get_customer()->id(), $order->id(), 'creditcard_pp', $paymentsvc_id, $amount);

				break;
		}
	}
	catch (Exception $e) {
		$response['result'] = FALSE;
		$response['message'] = $e->getMessage();
	}
}

echo json_encode($response);
