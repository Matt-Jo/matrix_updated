<?php
class ck_checkout_payment_view extends ck_view {

	protected $url = 'checkout_payment.php';

	protected $page_templates = [
	];

	protected $errors = [];

	public function process_response() {
		if (!$_SESSION['cart']->is_logged_in()) {
			$_SESSION['navigation']->set_snapshot(['page' => $this->url, 'get' => $_GET, 'post' => $_POST]);
			CK\fn::redirect_and_exit('/login.php');
		}

		if ($_SESSION['cart']->get_units() < 1) CK\fn::redirect_and_exit('/shopping_cart.php');

		$shipment = $_SESSION['cart']->get_shipments('active');

		if (empty($shipment['shipping_method_id']) || empty($shipment['shipping_address_book_id'])) {
			$_SESSION['cart']->record_error_message('Your cart is missing shipping selections.');
			CK\fn::redirect_and_exit('/checkout_shipping.php');
		}

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	public function view_context() {
		return 'checkout_payment';
	}

	private function psuedo_controller() {
		$page = NULL;
		$page_exact = FALSE;

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action']) {
			case 'select-payment':
				$cart_header = [];
				$payment = [];

				$cart = $_SESSION['cart'];
				$customer = $cart->get_customer();
				$payments = $cart->get_payments($cart->select_cart_shipment());

				if ($__FLAG['use-this-address']) {
					$address = [
						'entry_firstname' => $_POST['address-firstname'],
						'entry_lastname' => $_POST['address-lastname'],
						'entry_company' => $_POST['address-company'],
						'entry_street_address' => $_POST['address-street1'],
						'entry_suburb' => $_POST['address-street2'],
						'entry_postcode' => $_POST['address-postcode'],
						'entry_city' => $_POST['address-city'],
						'entry_country_id' => $_POST['address-country'],
						'entry_telephone' => $_POST['address-phone'],
					];

					try {
						if ($zone = ck_address2::get_zone($_POST['address-state'], $_POST['address-country'], TRUE)) {
							$address['entry_zone_id'] = $zone['zone_id'];
							$address['entry_state'] = '';
						}
						else {
							$address['entry_zone_id'] = 0;
							$address['entry_state'] = $_POST['address-state'];
						}
					}
					catch (Exception $e) {
					}

					try {
						if (!empty($_POST['edit_address_id'])) {
							$address_id = $_POST['edit_address_id'];
							$customer->edit_address($address_id, $address);
							$response['edit'] = 1;
						}
						else $address_id = $customer->create_address($address);

						$_POST['billing_address_id'] = $address_id;
					}
					catch (CKCustomerException $e) {
						$response['error'] = $e->getMessage();
					}
					catch (Exception $e) {
						$this->errors[] = 'There was a problem saving your address.';
					}
				}

				if ($__FLAG['billing_same_as_shipping']) $payment['billing_address_book_id'] = $cart->get_shipping_address()->id();
				elseif (!empty($_POST['billing_address_id'])) $payment['billing_address_book_id'] = $_POST['billing_address_id'];
				else $this->errors[] = 'You must select a billing address.';

				if (empty($_POST['payment_method'])) $this->errors[] = 'You must select a payment method.';
				elseif (preg_match('/^cc-(.+)$/', $_POST['payment_method'], $card_token)) {
					$payment['payment_method_id'] = ck_payment_method_lookup::instance()->lookup_by_code('creditcard_pp', 'payment_method_id');
					$payment['payment_card_id'] = $card_token[1];
				}
				else {
					switch ($_POST['payment_method']) {
						case 'paypal':
							$payment['payment_method_id'] = ck_payment_method_lookup::instance()->lookup_by_code('paypal', 'payment_method_id');
							if (!empty($_POST['paypal-nonce'])) $payment['pp_nonce'] = $_POST['paypal-nonce'];
							else $this->errors[] = 'Please complete the paypal payment process.';
							break;
						case 'terms':
							if (!$customer->has_credit() || !$customer->has_terms()) $this->errors[] = 'You do not have terms available for your account.';
							else {
								$payment['payment_method_id'] = $customer->get_terms('payment_method_id');
								if (!empty($_POST['net_po_number'])) $payment['payment_po_number'] = $_POST['net_po_number'];
								else $this->errors[] = 'You must enter a PO number.';
							}
							break;
						case 'checkmo':
							$payment['payment_method_id'] = ck_payment_method_lookup::instance()->lookup_by_code('checkmo', 'payment_method_id');
							break;
						case 'account-credit':
							$payment['payment_method_id'] = ck_payment_method_lookup::instance()->lookup_by_code('accountcredit', 'payment_method_id');
							break;
						/*case 'expected-cc':
							$payment['payment_method_id'] = ck_payment_method_lookup::instance()->lookup_by_code('customer_service', 'payment_method_id');
							break;*/
						default:
							$this->errors[] = 'Your selected payment method was not recognized.';
							break;
					}
				}

				if (!empty($payments[0])) $cart->update_payment($payments[0]['cart_payment_id'], $payment);
				else $cart->create_payment($cart->select_cart_shipment(), $payment);

				if (!empty($_POST['comments'])) $cart_header['customer_comments'] = $_POST['comments'];
				if (!empty($_POST['admin_comments'])) $cart_header['admin_comments'] = $_POST['admin_comments'];

				if (!empty($cart_header)) $cart->update($cart_header);

				if (!empty($_POST['coupon_code'])) {
					$payments = $cart->get_payments($cart->select_cart_shipment());

					$payment = [];

					// ugh.  Still on the old module system for this one, including order class etc etc etc.
					$_POST['gv_redeem_code'] = $_POST['coupon_code'];

					require(DIR_WS_CLASSES.'order.php');
					$GLOBALS['order'] = new order;

					include(DIR_WS_CLASSES.'order_total.php');
					$order_total_modules = new order_total;
					$order_total_modules->collect_posts();
					$order_total_modules->pre_confirmation_check();

					if (!empty($_SESSION['cc_id'])) {
						$payment['payment_coupon_id'] = $_SESSION['cc_id'];
						$payment['payment_coupon_code'] = $_POST['coupon_code'];
						$cart->update_payment($payments[0]['cart_payment_id'], $payment);
					}
					else $this->errors[] = 'The coupon code you entered ['.$_POST['coupon_code'].'] was not recognized.';
				}

				if (empty($this->errors) && empty($page)) $page = 'checkout_confirmation.php';

				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page, $page_exact);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		$__FLAG = request_flags::instance();

		$response = [];

		switch ($_REQUEST['action']) {
			case 'record-credit-card':
				$paymentSvcApi = new PaymentSvcApi();

				$cart = $_SESSION['cart'];
				$customer = $cart->get_customer();
				$payments = $cart->get_payments($cart->select_cart_shipment());

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

				if ($customer->has('braintree_customer_id')) {
					$card = [
						'owner' => $cart->get_cart_key(),
						'token' => $_POST['card_nonce'],
						'customerId' => $customer->get_header('braintree_customer_id'),
						'firstName' => $_POST['first_name'],
						'lastName' => $_POST['last_name'],
						'email' => $_POST['email'],
						'privateCard' => $__FLAG['private_card']?'T':'F'
					];

					if ($address = $customer->get_addresses($_POST['billing_address_id'])) {
						if ($address->has_company_name()) $card['company'] = $address->get_company_name();
						/*if ($address->has('first_name')) $card['firstName'] = $address->get_header('first_name');
						if ($address->has('last_name')) $card['lastName'] = $address->get_header('last_name');*/
						if ($address->has('address1')) $card['street1'] = $address->get_header('address1');
						if ($address->has('address2')) $card['street2'] = $address->get_header('address2');
						if ($address->has('city')) $card['city'] = $address->get_header('city');
						if ($state = $address->get_state()) $card['state'] = $state;
						if ($address->has('postcode')) $card['postalCode'] = $address->get_header('postcode');
						if ($address->has('countries_iso_code_2')) $card['countryCode'] = $address->get_header('countries_iso_code_2');
					}

					$card_result = json_decode($paymentSvcApi->addNewCardToCustomer($card), TRUE);

					if ($card_result['result']['status'] == 'error') {
						$response['error'] = $card_result['result']['message'];
						if (!empty($card_result['result']['cvv']) && !in_array($card_result['result']['cvv']['response_code'], ['M', 'I'])) $response['error'] .= ' [CVV: Code '.$card_result['result']['cvv']['response'].']';
						if (!empty($card_result['result']['avs']) && !in_array($card_result['result']['avs']['postal_response_code'], ['M', 'I'])) $response['error'] .= ' [AVS: Zip '.$card_result['result']['avs']['postal_response'].']';
						if (!empty($card_result['result']['avs']) && !in_array($card_result['result']['avs']['street_response_code'], ['M', 'I'])) $response['error'] .= ' [AVS: Street '.$card_result['result']['avs']['street_response'].']';
					}
					else {
						$response['card_id'] = $card_result['result']['card_token'];

						$new_card = json_decode($paymentSvcApi->findCard($response['card_id']), TRUE);

						$response['card_img'] = $new_card['result']['cardimgUrl'];
						$response['card_type'] = $new_card['result']['cardType'];
						$response['ending_in'] = $new_card['result']['lastFour'];
						$response['name_on_card'] = $new_card['result']['cardholderName'];
						$response['expiration'] = $new_card['result']['expirationDate'];
					}
				}
				else {
					$response['error'] = 'We could not create this credit card for your account; please contact your sales team.';
				}

				if (empty($response['error'])) {
					if (!empty($payments[0])) {
						$cart->update_payment($payments[0]['cart_payment_id'], ['billing_address_book_id' => $_POST['billing_address_id'], 'payment_method_id' => ck_payment_method_lookup::instance()->lookup_by_code('creditcard_pp', 'payment_method_id'), 'payment_card_id' => $response['card_id']]);
					}
					else {
						$cart->create_payment($cart->select_cart_shipment(), ['billing_address_book_id' => $_POST['billing_address_id'], 'payment_method_id' => ck_payment_method_lookup::instance()->lookup_by_code('creditcard_pp', 'payment_method_id'), 'payment_card_id' => $response['card_id']]);
					}
				}
				break;
			case 'record-paypal-nonce':
				$cart = $_SESSION['cart'];
				$customer = $cart->get_customer();
				$payments = $cart->get_payments($cart->select_cart_shipment());

				if (!empty($payments[0])) {
					$cart->update_payment($payments[0]['cart_payment_id'], ['payment_method_id' => ck_payment_method_lookup::instance()->lookup_by_code('paypal', 'payment_method_id'), 'pp_nonce' => $_POST['paypal_nonce']]);
				}
				else {
					$cart->create_payment($cart->select_cart_shipment(), ['payment_method_id' => ck_payment_method_lookup::instance()->lookup_by_code('paypal', 'payment_method_id'), 'pp_nonce' => $_POST['paypal_nonce']]);
				}

				break;
			case 'save-address':
				$cart = $_SESSION['cart'];
				$customer = $cart->get_customer();

				$address = [
					'entry_firstname' => $_POST['first_name'],
					'entry_lastname' => $_POST['last_name'],
					'entry_company' => $_POST['company'],
					'entry_street_address' => $_POST['address1'],
					'entry_suburb' => $_POST['address2'],
					'entry_postcode' => $_POST['postcode'],
					'entry_city' => $_POST['city'],
					'entry_country_id' => $_POST['country_id'],
					'entry_telephone' => $_POST['phone'],
				];

				try {
					if ($zone = ck_address2::get_zone($_POST['state'], $_POST['country_id'], TRUE)) {
						$address['entry_zone_id'] = $zone['zone_id'];
						$address['entry_state'] = '';
					}
					else {
						$address['entry_zone_id'] = 0;
						$address['entry_state'] = $_POST['state'];
					}
				}
				catch (Exception $e) {
				}

				try {
					if (!empty($_POST['edit_address_id'])) {
						$address_id = $_POST['edit_address_id'];
						$customer->edit_address($address_id, $address);
						$response['edit'] = 1;
					}
					else $address_id = $customer->create_address($address);

					$address = $customer->get_addresses($address_id);

					require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
					require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
					require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

					$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
					$cktpl->buffer = TRUE;

					$content = $address->get_address_line_template(NULL, ',<span class="address-spacer">  <br></span>');

					$response['address'] = $cktpl->content(DIR_FS_CATALOG.'includes/templates/partial-address-format.mustache.html', $content);
					$response['address_id'] = $address_id;
					$response['is_international'] = $address->is_international()?1:0;
					$response['country_code'] = $address->get_header('countries_iso_code_2');
				}
				catch (CKCustomerException $e) {
					$response['error'] = $e->getMessage();
				}
				catch (Exception $e) {
					$response['error'] = 'There was a problem saving this address.';
				}

				break;
			case 'get-address-data':
				$cart = $_SESSION['cart'];
				$customer = $cart->get_customer();

				$address = $customer->get_addresses($_GET['address_id']);

				$response['address'] = $address->get_header();
				$response['address_id'] = $address->id();
				$response['is_international'] = $address->is_international()?1:0;
				$response['country_code'] = $address->get_header('countries_iso_code_2');
				break;
			case 'reload-zones':
				$response['states'] = ck_address2::get_regions($_GET['countries_id']);
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$__FLAG = request_flags::instance();

		$data = $this->data();

		$cart = $_SESSION['cart'];

		if ($GLOBALS['messageStack']->size('payment') > 0) $data['message_stack'] = $GLOBALS['messageStack']->output('payment');

		if (!empty($this->errors) || $cart->has_error_messages()) {
			$errors = $this->errors;
			if ($cart->has_error_messages()) $errors = array_merge($cart->pull_error_messages(), $errors);
			$data['error'] = implode('<br>', $errors);
			$this->errors = [];
		}

		if (CK\fn::check_flag(@$_SESSION['admin_as_user'])) $data['admin?'] = 1;

		$GLOBALS['breadcrumb']->add('Cart Contents', '/shopping_cart.php');
		$GLOBALS['breadcrumb']->add('Shipping Method', '/checkout_shipping.php');
		$GLOBALS['breadcrumb']->add('Payment Method', '/checkout_payment.php');

		$data['breadcrumbs'] = $GLOBALS['breadcrumb']->trail();

		$data['contact_phone'] = $cart->get_contact_phone();
		$data['contact_email'] = $cart->get_contact_email();

		$paymentSvcApi = new PaymentSvcApi();
		$token = json_decode($paymentSvcApi->getToken(), true);

		$data['braintree_client_token'] = $token['braintree_client_token'];

		$data['order_total'] = number_format($cart->get_simple_totals('total'), 2);

		$customer = $cart->get_customer();
		$shipment = $_SESSION['cart']->get_shipments('active');
		$payments = $cart->get_payments($shipment['cart_shipment_id']);

		$data['shipping_address_id'] = $cart->get_shipping_address()->id();

		if ($address = $cart->get_billing_address()) {
			$data['default_address'] = $address->get_address_line_template(NULL, ',<span class="address-spacer">  <br></span>');
			$data['default_address']['header'] = $address->get_header();
			$data['default_address']['safe_header'] = array_map(function($field) { return htmlspecialchars($field, ENT_QUOTES); }, $address->get_header());
			$data['default_address']['address_id'] = $address->id();
			if ($address->is('default_address')) $data['default_address']['is_default'] = 1;

			if ($address->is_international()) {
				$data['default_address']['is_international'] = 1;
				$data['international?'] = 1;
			}
		}

		if ($addresses = $customer->get_other_addresses(!empty($address)?$address->id():NULL)) {
			$data['more_addresses?'] = 1;
			$data['addresses'] = [];
			foreach ($addresses as $idx => $addy) {
				if ($idx >= 5) break;

				$a = $addy->get_address_line_template(NULL, ',<span class="address-spacer">  <br></span>');
				$a['header'] = $addy->get_header();
				$a['safe_header'] = array_map(function($field) { return htmlspecialchars($field, ENT_QUOTES); }, $addy->get_header());
				$a['address_id'] = $addy->id();
				if ($addy->is('default_address')) $a['is_default'] = 1;
				if ($addy->is_international()) $a['is_international'] = 1;

				$data['addresses'][] = $a;
			}

			if (count($addresses) > 5) $data['more?'] = 1;
		}

		if ($__FLAG['use-this-address']) {
			$data['new_address'] = [
				'edit-address-id' => $_REQUEST['edit-address-id'],
				'address-firstname' => $_REQUEST['address-firstname'],
				'address-lastname' => $_REQUEST['address-lastname'],
				'address-company' => $_REQUEST['address-company'],
				'address-street1' => $_REQUEST['address-street1'],
				'address-street2' => $_REQUEST['address-street2'],
				'address-city' => $_REQUEST['address-city'],
				'address-state' => $_REQUEST['address-state'],
				'address-postcode' => $_REQUEST['address-postcode'],
				'address-country' => $_REQUEST['address-country'],
				'address-phone' => $_REQUEST['address-phone'],
			];
		}

		$data['countries'] = ck_address2::get_countries();
		$data['default_country'] = !empty($address)?$address->get_header('countries_id'):ck_address2::DEFAULT_COUNTRY_ID; // 223/USA

		$data['states'] = ck_address2::get_regions($data['default_country']);

		$data['email_address'] = $customer->get_header('email_address');

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
					if ($card['hide_card'] && $card['owner'] != $cart->get_cart_key()) continue;
					$customer_cards[] = [
						'card_id' => $card['token'],
						'card_img' => $card['cardimgUrl'],
						'card_type' => $card['cardType'],
						'ending_in' => $card['lastFour'],
						'name_on_card' => isset($card['cardholderName'])?$card['cardholderName']:'',
						'expiration' => $card['expirationDate'],
					];
				}

				if (!empty($customer_cards)) $data['credit_cards'] = $customer_cards;
			}
		}

		if ($customer->has_credit() && $customer->has_terms()) {
			$terms = $customer->get_terms();

			$data['terms'] = $terms;

			if ($customer->cannot_place_any_order()) $data['terms']['cannot_place_any_order'] = 1;
			elseif ($customer->get_credit('credit_status_id') == ck_customer2::CREDIT_PREPAID) $data['terms']['prepaid_only'] = 1;
			elseif (!$customer->can_place_credit_order($cart->get_totals('total')['value'])) {
				$data['terms']['over_limit'] = 1;
				$data['terms']['remaining_credit'] = CK\text::monetize($customer->get_remaining_credit());
			}
		}

		if (!empty($payments)) {
			$payment = $payments[0]; // for the moment, we're only dealing with a single payment record allowed on the cart - the structure supports multiple payment records.

			if (empty($payment['billing_address_book_id']) || $cart->get_shipping_address()->id() == $address->id()) $data['billing_same'] = 1;

			if (!empty($payment['pp_nonce'])) $data['paypal_nonce'] = $payment['pp_nonce'];

			if (!empty($payment['payment_method_id'])) {
				switch (ck_payment_method_lookup::instance()->lookup_by_id($payment['payment_method_id'], 'method_code')) {
					case 'creditcard_pp':
						if (!empty($data['credit_cards'])) {
							foreach ($data['credit_cards'] as &$card) {
								if ($card['card_id'] == $payment['payment_card_id']) $card['cc-selected?'] = 1;
							}
						}
						break;
					case 'paypal':
						$data['payment_paypal?'] = 1;
						break;
					case 'net10':
					case 'net15':
					case 'net30':
					case 'net45':
						$data['terms']['payment_terms?'] = 1;
						if (!empty($payment['payment_po_number'])) $data['terms']['net_po_number'] = $payment['payment_po_number'];
						break;
					case 'checkmo':
						$data['payment_checkmo?'] = 1;
						break;
					case 'accountcredit':
						$data['payment_account_credit?'] = 1;
						break;
					/*case 'customer_service':
						$data['payment_expected_cc?'] = 1;
						break;*/
				}
			}

			$data['coupon_code'] = $payment['payment_coupon_code'];
		}
		elseif (!$shipment['blind'] && $shipment['shipping_address_book_id'] == $address->id()) $data['billing_same'] = 1;

		$data['comments'] = $cart->get_header('customer_comments');
		$data['admin_comments'] = $cart->get_header('admin_comments');

		$this->render('page-checkout-payment.mustache.html', $data);
		$this->flush();
	}
}
?>
