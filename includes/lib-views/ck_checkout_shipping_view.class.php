<?php
class ck_checkout_shipping_view extends ck_view {

	protected $url = 'checkout_shipping.php';

	protected $page_templates = [
	];

	protected $errors = [];

	public function process_response() {
		if (!$_SESSION['cart']->is_logged_in()) {
			$_SESSION['navigation']->set_snapshot(['page' => $this->url, 'get' => [], 'post' => $_POST]);
			CK\fn::redirect_and_exit('/login.php');
		}

		if ($_SESSION['cart']->get_units() < 1) CK\fn::redirect_and_exit('/shopping_cart.php');

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	public function view_context() {
		return 'checkout_shipping';
	}

	private function psuedo_controller() {
		$page = NULL;

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action']) {
			case 'select-shipping':
				$cart_header = [];
				$shipment = [];

				$cart = $_SESSION['cart'];
				$customer = $cart->get_customer();

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

						$_POST['shipping_address_id'] = $address_id;
					}
					catch (CKCustomerException $e) {
						$response['error'] = $e->getMessage();
					}
					catch (Exception $e) {
						$this->errors[] = 'There was a problem saving your address.';
					}
				}

				if (!empty($_POST['shipping_address_id'])) $shipment['shipping_address_book_id'] = $_POST['shipping_address_id'];
				else $this->errors[] = 'You must select a shipping address.';

				$shipment['blind'] = $__FLAG['dropship']?1:0;
				if ($__FLAG['po_marker']) {
					if (!empty($_POST['purchase_order_number'])) $shipment['order_po_number'] = $_POST['purchase_order_number'];
					else $this->errors[] = 'If you wish to use a PO/Reference number, you must enter it on this page.';
				}
				else $shipment['order_po_number'] = NULL;
				if (CK\fn::check_flag($customer->get_header('use_reclaimed_packaging')) != CK\fn::check_flag($__FLAG['use_reclaimed_packaging'])) {
					// if the customer makes a new selection, we'll save it for the future
					$customer->update(['use_reclaimed_packaging' => $__FLAG['use_reclaimed_packaging']?1:0]);
				}
				$shipment['reclaimed_materials'] = $__FLAG['use_reclaimed_packaging']?1:0;
				if (CK\fn::check_flag($shipment['blind'])) $shipment['reclaimed_materials'] = 0; // if the order is blind, we are going to make sure we don't use reclaimed packaging

				if (isset($_POST['shipping_account'])) {
					switch ($_POST['shipping_account']) {
						case 'customer-on-file':
							$shipment['shipment_account_choice'] = 2;
							$shipment['ups_account_number'] = NULL;
							$shipment['fedex_account_number'] = NULL;

							if (isset($_POST['on_file_shipping_account_selection']['ups'])) $shipment['ups_account_number'] = $_POST['on_file_shipping_acount_selection']['ups'];
							elseif (isset($_POST['on_file_shipping_account_selection']['fedex'])) $shipment['fedex_account_number'] = $_POST['on_file_shipping_acount_selection']['fedex'];

							break;
						case 'customer-alternate':
							$shipment['shipment_account_choice'] = 0;
							$shipment['ups_account_number'] = NULL;
							$shipment['fedex_account_number'] = NULL;
							$shipping_account_alternate = NULL;
							if (isset($_POST['shipping_account_alternate'])) $shipping_account_alternate = str_replace(' ', '', $_POST['shipping_account_alternate']);
							if ($_POST['shipping_account'] == 'customer-alternate' && !empty($shipping_account_alternate)) {
								if ($_POST['alternate_account_selection'] == 'ups') {
									$shipment['ups_account_number'] = $shipping_account_alternate;
								}
								elseif ($_POST['alternate_account_selection'] == 'fedex') {
									$shipment['fedex_account_number'] = $shipping_account_alternate;
								}
							}
							else $this->errors[] = 'You must enter a valid shipping account number';
							break;
						case 'shipper':
						default:
							$shipment['shipment_account_choice'] = 4;
							$shipment['ups_account_number'] = NULL;
							$shipment['fedex_account_number'] = NULL;
							break;
					}
				}
				else {
					$shipment['shipment_account_choice'] = NULL;
					$shipment['ups_account_number'] = NULL;
					$shipment['fedex_account_number'] = NULL;
				}

				$shipment['residential'] = 0;

				if (!empty($_POST['ship-method'])) {
					$shipment['shipping_method_id'] = $_POST['ship-method'];
					if ($shipment['shipping_method_id'] == 50) {
						if (!empty($_POST['freight_opts'])) {
							$freight_opts = $_POST['freight_opts'];

							if ($__FLAG['freight-residential']) $shipment['residential'] = 1;
							else $shipment['residential'] = 0;

							if (isset($freight_opts['liftgate'])) $shipment['freight_needs_liftgate'] = CK\fn::check_flag($freight_opts['liftgate'])?1:0;
							else $this->errors[] = 'You must indicate whether or not you have a loading dock or forklift.';

							if (isset($freight_opts['inside'])) $shipment['freight_needs_inside_delivery'] = CK\fn::check_flag($freight_opts['inside'])?1:0;
							else $this->errors[] = 'You must indicate whether or not you need inside delivery.';

							if (isset($freight_opts['limitaccess'])) $shipment['freight_needs_limited_access'] = CK\fn::check_flag($freight_opts['limitaccess'])?1:0;
							else $this->errors[] = 'You must indicate whether or not your address is a limited access location.';
						}
						else $this->errors[] = 'You must indicate whether or not you need additional delivery options for freight.';
					}
					else {
						$shipment['freight_needs_liftgate'] = NULL;
						$shipment['freight_needs_inside_delivery'] = NULL;
						$shipment['freight_needs_limited_access'] = NULL;
					}

					if ($shipment['shipping_method_id'] == 48) {
						// we're not shipping on a customer account if it's free shipping
						$shipment['shipment_account_choice'] = NULL;
						$shipment['ups_account_number'] = NULL;
						$shipment['fedex_account_number'] = NULL;
					}
				}
				else $this->errors[] = 'You must select a shipping method.';

				if (!empty($_POST['comments'])) $cart_header['customer_comments'] = $_POST['comments'];
				if (!empty($_POST['admin_comments'])) $cart_header['admin_comments'] = $_POST['admin_comments'];

				if (!empty($cart_header)) $cart->update($cart_header);
				$cart->update_active_shipment($shipment);

				if (empty($this->errors)) $page = 'checkout_payment.php';

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
			case 'get-shipping-quotes':
				$cart = $_SESSION['cart'];

				$data['contact_phone'] = $cart->get_contact_phone();
				$data['contact_email'] = $cart->get_contact_email();

				$customer = $cart->get_customer();
				$shipment = $cart->get_shipments('active');

				$address = new ck_address2($_GET['shipping_address_id']);

				$customer_carrier = NULL;
				if (!empty($_REQUEST['customer_carrier'])) {
					$carrier_options = ['fedex', 'ups'];
					if (in_array($_REQUEST['customer_carrier'], $carrier_options)) $customer_carrier = $_REQUEST['customer_carrier'];
				}

				if ($address->found()) {
					$data['rate_groups'] = $cart->get_ship_rate_quotes($address, $weight=NULL, $box_length=NULL, $box_height=NULL, $box_width=NULL, $from_address=NULL, $shipping_method_id=NULL, $carrier=$customer_carrier);

					foreach ($data['rate_groups'] as $rg => $group) {
						if (in_array($group['carrier_id'], ['fedexnonsaturday', 'fedexwebservices'])) {
							$data['rate_groups'][$rg]['fedex_group'] = 1;
							if (date('G') >= 16 && date('G') <= 20) $data['rate_groups'][$rg]['fedex_group_warning'] = 1;
						}
						/*elseif (in_array($group['carrier_id'], ['iux'])) {
							$data['rate_groups'][$rg]['ups_group_warning'] = 1;
						}*/

						if (!empty($group['freight_quote'])) {
							if ($group['freight_quote']['shipping_method_id'] == $shipment['shipping_method_id']) $data['rate_groups'][$rg]['freight_quote']['selected?'] = 1;
							if (!is_null($shipment['freight_needs_liftgate'])) {
								if ($shipment['freight_needs_liftgate'] == 0) $data['rate_groups'][$rg]['freight_quote']['freight_liftgate_no?'] = 1;
								else $data['rate_groups'][$rg]['freight_quote']['freight_liftgate_yes?'] = 1;
							}
							if (!is_null($shipment['freight_needs_inside_delivery'])) {
								if ($shipment['freight_needs_inside_delivery'] == 0) $data['rate_groups'][$rg]['freight_quote']['freight_inside_no?'] = 1;
								else $data['rate_groups'][$rg]['freight_quote']['freight_inside_yes?'] = 1;
							}
							if (!is_null($shipment['freight_needs_limited_access'])) {
								if ($shipment['freight_needs_limited_access'] == 0) $data['rate_groups'][$rg]['freight_quote']['freight_limitaccess_no?'] = 1;
								else $data['rate_groups'][$rg]['freight_quote']['freight_limitaccess_yes?'] = 1;
							}
						}
						elseif (!empty($group['rate_quotes'])) {
							foreach ($group['rate_quotes'] as $rq => $quote) {
								if ($quote['shipping_method_id'] == $shipment['shipping_method_id']) $data['rate_groups'][$rg]['rate_quotes'][$rq]['selected?'] = 1;
							}
						}
					}
				}

				if ($customer->has('own_shipping_account') && !$cart->has_freight_products()) $data['shipping_account'] = 1;
				if (!empty($_REQUEST['shipping_account']) && $_REQUEST['shipping_account'] != 'shipper') $data['customer_account'] = 1;

				$this->set_open();

				$this->render('partial-shipping-methods.mustache.html', $data);
				$this->flush();

				exit();
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
			case 'confirm-po-number':
				$cart = $_SESSION['cart'];
				$customer = $cart->get_customer();
				$response = $customer->has_used_po_number_before($_REQUEST['purchase_order_number']);
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

		$GLOBALS['breadcrumb']->add('Cart Contents', '/shopping_cart.php');
		$GLOBALS['breadcrumb']->add('Shipping Method', '/checkout_shipping.php');

		$data['breadcrumbs'] = $GLOBALS['breadcrumb']->trail();

		$data['contact_phone'] = $cart->get_contact_phone();
		$data['contact_email'] = $cart->get_contact_email();

		$customer = $cart->get_customer();
		$shipment = $cart->get_shipments('active');

		if ($address = $cart->get_shipping_address()) {
			$data['default_address'] = $address->get_address_line_template(NULL, ',<span class="address-spacer">  <br></span>');
			$data['default_address']['address_id'] = $address->id();
			if ($address->is('default_address')) $data['default_address']['is_default'] = 1;
			else $data['blind-allowed?'] = 1;

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

		if ($customer->has('own_shipping_account')) {
			$data['shipping_account'] = [];
			if ($cart->has_freight_products()) $data['shipping_account']['shipping_account_disabled?'] = 1;
			if ($customer->has('ups_account_number')) $data['shipping_account']['ups_account'] = $customer->get_header('ups_account_number');
			if ($customer->has('fedex_account_number')) $data['shipping_account']['fedex_account'] = $customer->get_header('fedex_account_number');

			$data['shipping_account']['shipper'] = 1;

			if (is_numeric($shipment['shipment_account_choice'])) {
				switch ($shipment['shipment_account_choice']) {
					case 4:
						$data['shipping_account']['shipper'] = 1;
						break;
					case 2:
						$data['shipping_account']['customer_on_file'] = 1;
						break;
					case 0:
						$data['shipping_account']['customer_alternate'] = 1;
						$data['shipping_account']['shipping_account_alternate_ups'] = $shipment['ups_account_number'];
						$data['shipping_account']['shipping_account_alternate_fedex'] = $shipment['fedex_account_number'];
						break;
				}
			}
			elseif (!empty($shipment['ups_account_number']) || !empty($shipment['fedex_account_number'])) {
				$data['shipping_account']['customer_alternate'] = 1;
				$data['shipping_account']['shipping_account_alternate_ups'] = $shipment['ups_account_number'];
				$data['shipping_account']['shipping_account_alternate_fedex'] = $shipment['fedex_account_number'];
			}
			elseif ($customer->has('ups_account_number') || $customer->has('fedex_account_number')) $data['shipping_account']['customer_on_file'] = 1;
			else $data['shipping_account']['shipper'] = 1;
		}

		if ($shipment['blind']) $data['blind'] = 1;
		if (!empty($shipment['order_po_number'])) $data['ref-number'] = $shipment['order_po_number'];

		if (CK\fn::check_flag($customer->get_header('use_reclaimed_packaging')) || CK\fn::check_flag($shipment['reclaimed_materials'])) $data['use_reclaimed_packaging'] = 1;

		$data['comments'] = $cart->get_header('customer_comments');
		$data['admin_comments'] = $cart->get_header('admin_comments');

		$this->render('page-checkout-shipping.mustache.html', $data);
		$this->flush();
	}
}
?>
