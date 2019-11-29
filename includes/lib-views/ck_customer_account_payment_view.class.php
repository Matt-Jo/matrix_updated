<?php
class ck_customer_account_payment_view extends ck_view {

	protected $url = '/my-account/payment';
	protected $errors = [];

	public $secondary_partials = TRUE;

	public function page_title() {
		return 'Account Payment';
	}

	public function process_response() {
		if (!$_SESSION['cart']->is_logged_in()) CK\fn::redirect_and_exit('/login.php');
		$this->template_set = self::TPL_FULLWIDTH_FRONTEND_CUSTOMER_ACCOUNT;

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'add-card':
				break;
			case 'delete-card':
				if (isset($_POST['card_id'])) {
					$paymentSvcApi = new PaymentSvcApi();
					$paymentSvcApi->deleteCard($_POST['card_id']);
				}
				$page = '/my-account/payment';
				break;
			default:
				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		if (!empty($results)) echo json_encode($results);
		else echo json_encode(['success' => false]);
		exit();
	}

	private function http_response() {
		$data = $this->data();
		$customer = new ck_customer2($_SESSION['customer_id']);

		$paymentSvcApi = new PaymentSvcApi();
		$token = json_decode($paymentSvcApi->getToken(), true);

		$data['braintree_client_token'] = $token['braintree_client_token'];

		// setting up the braintree CC customer is complicated, basically triple checking that we have an ID, it's legit, or attempting to set up a new one
		if ($customer->has('braintree_customer_id')) {
			$btc = json_decode($paymentSvcApi->getCustomer($customer->get_header('braintree_customer_id')), TRUE);

			if ($btc['result']['status'] != 'success') $btc = NULL;
		}

		// $btc is empty if we don't have a braintree_customer_id or we failed the lookup
		if (empty($btc)) {
			$braintree_customer = [
				'firstname' => $customer->get_header('first_name'),
				'lastname' => $customer->get_header('last_name'),
				'email' => $customer->get_header('email_address'),
				'token' => NULL,
			];

			$result = json_decode($paymentSvcApi->createCustomer($braintree_customer), TRUE);

			if ($result['result']['status'] == 'success') {
				$braintree_customer_id = $result['result']['CustomerId'];
				prepared_query::execute('UPDATE customers SET braintree_customer_id = :braintree_customer_id WHERE customers_id = :customers_id', [':braintree_customer_id' => $braintree_customer_id, ':customers_id' => $customer->id()]);
				$customer->rebuild_header();
			}
		}

		// at this point we should have a legit braintree_customer_id unless we attempted to create one and it failed
		if ($customer->has('braintree_customer_id')) {
			$customerData = json_decode($paymentSvcApi->getCustomerCards($customer->get_header('braintree_customer_id')), TRUE);

			if (!empty($customerData['result']['cards'])) {
				//add all cards to
				$cards = $customerData['result']['cards'];
				$customer_cards = [];

				foreach ($cards as $card) {
					if ($card['hide_card'] && $card['owner'] != $_SESSION['cart']->get_cart_key()) continue;
					$customer_cards[] = [
						'card_id' => $card['token'],
						'card_img' => $card['cardimgUrl'],
						'card_type' => $card['cardType'],
						'ending_in' => $card['lastFour'],
						'name_on_card' => isset($card['cardholderName'])?$card['cardholderName']:'',
						'expiration' => $card['expirationDate'],
					];
				}

				if (!empty($customer_cards)) {
					$data['has_cards'] = 1;
					$data['credit_cards'] = $customer_cards;
				}
			}
		}



		$this->render('page-customer-account-payment.mustache.html', $data);
		$this->flush();
	}
}
?>