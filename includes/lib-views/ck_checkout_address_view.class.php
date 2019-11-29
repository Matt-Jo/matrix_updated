<?php
class ck_checkout_address_view extends ck_view {

	protected $url = 'checkout_address.php';

	protected $page_templates = [
	];

	protected $errors = [];

	public function process_response() {
		if (!$_SESSION['cart']->is_logged_in()) {
			$_SESSION['navigation']->set_snapshot(['page' => $this->url, 'get' => $_GET, 'post' => $_POST]);
			CK\fn::redirect_and_exit('/login.php');
		}

		if ($_SESSION['cart']->get_units() < 1) CK\fn::redirect_and_exit('/shopping_cart.php');

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	public function view_context() {
		return 'checkout_address';
	}

	private function psuedo_controller() {
		$page = NULL;

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action'].'-'.$_REQUEST['target']) {
			case 'select-address-shipping':
				$shipment = [];

				if (!empty($_POST['address_id'])) $shipment['shipping_address_book_id'] = $_POST['address_id'];
				else $this->errors[] = 'You must select a shipping address.';

				$_SESSION['cart']->update_active_shipment($shipment);

				if (empty($this->errors)) $page = '/checkout_shipping.php';

				break;
			case 'select-address-payment':
				$payment = [];

				if (!empty($_POST['address_id'])) $payment['billing_address_book_id'] = $_POST['address_id'];
				else $this->errors[] = 'You must select a billing address.';

				$payments = $_SESSION['cart']->get_payments($_SESSION['cart']->select_cart_shipment());

				if (!empty($payments[0])) $_SESSION['cart']->update_payment($payments[0]['cart_payment_id'], $payment);
				else $_SESSION['cart']->create_payment($_SESSION['cart']->select_cart_shipment(), $payment);

				if (empty($this->errors)) $page = '/checkout_payment.php';

				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		// there's nothing to do here

		$response = [];

		switch ($_REQUEST['action']) {
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

		if (!empty($this->errors) || $cart->has_error_messages()) {
			$errors = $this->errors;
			if ($cart->has_error_messages()) $errors = array_merge($cart->pull_error_messages(), $errors);
			$data['error'] = implode('<br>', $errors);
			$this->errors = [];
		}

		if (CK\fn::check_flag(@$_SESSION['admin_as_user'])) $data['admin?'] = 1;

		$target = $_GET['target'];

		$GLOBALS['breadcrumb']->add('Cart Contents', '/shopping_cart.php');
		$GLOBALS['breadcrumb']->add('Shipping Method', '/checkout_shipping.php');
		if ($target == 'shipping') {
			$GLOBALS['breadcrumb']->add('Change Shipping Address', '/checkout_address.php?target=shipping');
		}
		elseif ($target == 'payment') {
			$GLOBALS['breadcrumb']->add('Payment Method', '/checkout_payment.php');
			$GLOBALS['breadcrumb']->add('Change Billing Address', '/checkout_address.php?target=billing');
		}

		$data['breadcrumbs'] = $GLOBALS['breadcrumb']->trail();

		$data['target'] = $target;

		$data['contact_phone'] = $cart->get_contact_phone();
		$data['contact_email'] = $cart->get_contact_email();

		$customer = $cart->get_customer();

		if ($target == 'shipping') $selected_address = $cart->get_shipping_address();
		elseif ($target == 'payment') $selected_address = $cart->get_billing_address();

		if ($addresses = $customer->get_addresses()) {
			$data['addresses'] = [];
			foreach ($addresses as $addy) {
				$a = $addy->get_address_line_template(NULL, ',<span class="address-spacer">  <br></span>');
				$a['address_id'] = $addy->id();

				if ($addy->id() == $selected_address->id()) $a['selected?'] = 1;
				if ($addy->is('default_address')) $a['is_default'] = 1;
				if ($addy->is_international()) $a['is_international'] = 1;

				$data['addresses'][] = $a;
			}
		}

		$data['countries'] = ck_address2::get_countries();
		$data['default_country'] = !empty($address)?$address->get_header('countries_id'):ck_address2::DEFAULT_COUNTRY_ID; // 223/USA

		$data['states'] = ck_address2::get_regions($data['default_country']);

		$this->render('page-checkout-address.mustache.html', $data);
		$this->flush();
	}
}
?>
