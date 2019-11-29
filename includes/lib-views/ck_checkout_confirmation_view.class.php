<?php
class ck_checkout_confirmation_view extends ck_view {

	protected $url = 'checkout_confirmation.php';

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

		$payments = $_SESSION['cart']->get_payments($shipment['cart_shipment_id']);
		if (empty($payments[0]) || empty($payments[0]['billing_address_book_id']) || empty($payments[0]['payment_method_id'])) {
			$_SESSION['cart']->record_error_message('Your cart is missing payment selections.');
			CK\fn::redirect_and_exit('/checkout_payment.php');
		}
		elseif (ck_payment_method_lookup::instance()->lookup_by_id($payments[0]['payment_method_id'], 'method_code') == 'creditcard_pp' && empty($payments[0]['payment_card_id'])) {
			$_SESSION['cart']->record_error_message('Please select your credit card.');
			CK\fn::redirect_and_exit('/checkout_payment.php');
		}
		elseif (ck_payment_method_lookup::instance()->lookup_by_id($payments[0]['payment_method_id'], 'method_code') == 'paypal' && empty($payments[0]['pp_nonce'])) {
			$_SESSION['cart']->record_error_message('Please complete the paypal checkout process.');
			CK\fn::redirect_and_exit('/checkout_payment.php');
		}
		elseif (in_array(ck_payment_method_lookup::instance()->lookup_by_id($payments[0]['payment_method_id'], 'method_code'), ck_payment_method_lookup::instance()->get_list('terms', 'method_code')) && empty($payments[0]['payment_po_number'])) {
			$_SESSION['cart']->record_error_message('Please enter a PO number.');
			CK\fn::redirect_and_exit('/checkout_payment.php');
		}

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	public function view_context() {
		return 'checkout_confirmation';
	}

	private function psuedo_controller() {
		$page = NULL;

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action']) {
			case 'process-order':
				try {
					//ck_master_archetype::begin_debugging();
					$sales_order = ck_sales_order::create_from_cart($_SESSION['cart']);
				}
				catch (CKSalesOrderException $e) {
					if (ck_master_archetype::debugging()) {
						throw $e;
					}
					else {
						$_SESSION['cart']->record_error_message($e->getMessage());
						CK\fn::redirect_and_exit('/checkout_confirmation.php');
					}
				}
				catch (Exception $e) {
					$msg = 'There was an error entering your order, please try again or contact <a href="mailto:'.$_SESSION['cart']->get_contact_email().'">'.$_SESSION['cart']->get_contact_email().'</a>.';
					if (isset($_SESSION['admin_as_user'])) $msg .= ' Admin Details: '.$e->getMessage();
					if (ck_master_archetype::debugging()) {
						echo $msg;
						throw $e;
					}
					else {
						$_SESSION['cart']->record_error_message($msg);
						CK\fn::redirect_and_exit('/checkout_confirmation.php');
					}
				}

				if (isset($_SESSION['admin_as_user'])) {
					$send_payment_request = $__FLAG['send_payment_request'];

					if ($__FLAG['view_order_in_admin']) $_SESSION['view_order_in_admin'] = 1;
					else unset($_SESSION['view_order_in_admin']);

					if ($__FLAG['log_out_as_user']) $_SESSION['log_out_as_user'] = 1;
					else unset($_SESSION['log_out_as_user']);
				}

				if (!empty($send_payment_request)) $sales_order->send_payment_request();

				//if (!empty($view_order_in_admin)) CK\fn::redirect_and_exit('/admin/orders_new.php?action=edit&oID='.$sales_order->id());
				//else
				CK\fn::redirect_and_exit('/checkout_success.php?order_id='.$sales_order->id());
				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		$__FLAG = request_flags::instance();

		$response = [];

		switch ($_REQUEST['action']) {
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
		$GLOBALS['breadcrumb']->add('Order Confirmation', '/checkout_confirmation.php');

		$data['breadcrumbs'] = $GLOBALS['breadcrumb']->trail();

		$data['contact_phone'] = $cart->get_contact_phone();
		$data['contact_email'] = $cart->get_contact_email();

		$data['order_email_address'] = $cart->get_email_address();

		$data['order_totals'] = [];

		if ($cart->has_products()) {
			$data['has_products?'] = 1;
			$data['product_total'] = CK\text::monetize($cart->get_product_total());

			$optional_addons = [];
			$included_addons = [];

			foreach ($cart->get_products() as $product) {
				if (empty($product['parent_products_id'])) continue; // we're just looping through child products

				if (!in_array($product['option_type'], [ck_cart::$option_types['NONE'], ck_cart::$option_types['INCLUDED']])) {
					if (empty($optional_addons[$product['parent_products_id']])) $optional_addons[$product['parent_products_id']] = [];
					$optional_addons[$product['parent_products_id']][] = $product;
				}

				if (!in_array($product['option_type'], [ck_cart::$option_types['INCLUDED']])) continue;

				if (empty($included_addons[$product['parent_products_id']])) $included_addons[$product['parent_products_id']] = [];
				$included_addons[$product['parent_products_id']][] = $product;
			}

			$data['products'] = [];

			foreach ($cart->get_products() as $product) {
				if (!empty($product['parent_products_id'])) continue; // we're only dealing with parent products

				$prod = [
					'quantity' => $product['quantity'],
					'products_name' => $product['listing']->get_header('products_name'),
					'products_model' => $product['listing']->get_header('products_model'),
					'unit_price' => CK\text::monetize($product['unit_price']),
					'line_price' => CK\text::monetize($product['unit_price'] * $product['quantity'])
				];

				if (!empty($included_addons[$product['products_id']])) {
					$prod['has_addons?'] = 1;
					$prod['included'] = [];

					foreach ($included_addons[$product['products_id']] as $idx => $included) {
						$addon_name = $included['listing']->get_header('products_name');
						foreach ($product['listing']->get_options('included') as $opt) {
							if ($opt['products_id'] != $included['products_id']) continue;
							$addon_name = $opt['name'];
							break;
						}

						$inc = [
							'products_name' => $addon_name,
							'products_model' => $included['listing']->get_header('products_model'),
						];

						if (empty($optional_addons[$product['products_id']]) && $idx + 1 == count($included_addons[$product['products_id']])) $inc['last?'] = 1;

						$prod['included'][] = $inc;
					}
				}

				if (!empty($optional_addons[$product['products_id']])) {
					$prod['has_addons?'] = 1;
					$prod['options'] = [];

					foreach ($optional_addons[$product['products_id']] as $idx => $option) {
						$addon_name = $option['listing']->get_header('products_name');
						foreach ($product['listing']->get_options('extra') as $opt) {
							if ($opt['products_id'] != $option['products_id']) continue;
							$addon_name = $opt['name'];
							break;
						}

						$opt = [
							'quantity' => $option['quantity'],
							'products_name' => $addon_name,
							'products_model' => $option['listing']->get_header('products_model'),
							'unit_price' => CK\text::monetize($option['unit_price']),
							'line_price' => CK\text::monetize($option['unit_price'] * $option['quantity'])
						];

						if ($idx + 1 == count($optional_addons[$product['products_id']])) $opt['last?'] = 1;

						$prod['options'][] = $opt;
					}
				}

				$data['products'][] = $prod;
			}
		}

		if ($cart->has_quotes()) {
			$data['has_quotes?'] = 1;

			$data['order_totals'][] = [
				'description' => 'Subtotal',
				'class' => 'sub-total',
				'total' => CK\text::monetize($cart->get_total())
			];

			$data['quotes'] = [];

			foreach ($cart->get_quotes() as $quote) {
				$optional_addons = [];
				$included_addons = [];

				$qt = ['quote_total' => CK\text::monetize($cart->get_quote_total()), 'products' => []];

				foreach ($quote['quote']->get_products() as $product) {
					if (empty($product['parent_products_id'])) continue; // we're just looping through child products

					if (!in_array($product['option_type'], [ck_cart::$option_types['NONE'], ck_cart::$option_types['INCLUDED']])) {
						if (empty($optional_addons[$product['parent_products_id']])) $optional_addons[$product['parent_products_id']] = [];
						$optional_addons[$product['parent_products_id']][] = $product;
					}

					if (!in_array($product['option_type'], [ck_cart::$option_types['INCLUDED']])) continue;

					if (empty($included_addons[$product['parent_products_id']])) $included_addons[$product['parent_products_id']] = [];
					$included_addons[$product['parent_products_id']][] = $product;
				}

				foreach ($quote['quote']->get_products() as $product) {
					if (!empty($product['parent_products_id'])) continue; // we're only dealing with parent products

					$prod = [
						'quantity' => $product['quantity'],
						'products_name' => $product['listing']->get_header('products_name'),
						'products_model' => $product['listing']->get_header('products_model'),
						'unit_price' => CK\text::monetize($product['price']),
						'line_price' => CK\text::monetize($product['price'] * $product['quantity'])
					];

					if (!empty($included_addons[$product['products_id']])) {
						$prod['has_addons?'] = 1;
						$prod['included'] = [];

						foreach ($included_addons[$product['products_id']] as $idx => $included) {
							$addon_name = $included['listing']->get_header('products_name');
							foreach ($product['listing']->get_options('included') as $opt) {
								if ($opt['products_id'] != $included['products_id']) continue;
								$addon_name = $opt['name'];
								break;
							}

							$inc = [
								'products_name' => $addon_name,
								'products_model' => $included['listing']->get_header('products_model'),
							];

							if (empty($optional_addons[$product['products_id']]) && $idx + 1 == count($included_addons[$product['products_id']])) $inc['last?'] = 1;

							$prod['included'][] = $inc;
						}
					}

					if (!empty($optional_addons[$product['products_id']])) {
						$prod['has_addons?'] = 1;
						$prod['options'] = [];

						foreach ($optional_addons[$product['products_id']] as $idx => $option) {
							$addon_name = $option['listing']->get_header('products_name');
							foreach ($product['listing']->get_options('extra') as $opt) {
								if ($option['products_id'] != $opt['products_id']) continue;
								$addon_name = $opt['name'];
								break;
							}

							$opt = [
								'quantity' => $option['quantity'],
								'products_name' => $addon_name,
								'products_model' => $option['listing']->get_header('products_model'),
								'unit_price' => CK\text::monetize($option['price']),
								'line_price' => CK\text::monetize($option['price'] * $option['quantity'])
							];

							if ($idx + 1 == count($optional_addons[$product['products_id']])) $opt['last?'] = 1;

							$prod['options'][] = $opt;
						}
					}

					$qt['products'][] = $prod;
				}

				$data['quotes'][] = $qt;
			}
		}

		$customer = $cart->get_customer();
		$shipment = $cart->get_shipments('active');
		$payments = $cart->get_payments($cart->select_cart_shipment());
		$payment = $payments[0];

		if ($shipping_address = $cart->get_shipping_address()) {
			$data['shipping_address'] = $shipping_address->get_address_format_template(NULL, ',  ');
		}
		if ($billing_address = $cart->get_billing_address()) {
			$data['billing_address'] = $billing_address->get_address_format_template(NULL, ',  ');
		}

		$shipping_details_full = $cart->get_selected_ship_rate_quote();
		if (!empty($shipping_details_full['rate_quotes'])) $shipping_details = $shipping_details_full['rate_quotes'][0];
		elseif (!empty($shipping_details_full['freight_quote'])) $shipping_details = $shipping_details_full['freight_quote'];
		else $shipping_details = ['name' => '', 'estimated_delivery' => ''];

		$data['shipping_method'] = $shipping_details['name'].($shipment['shipping_method_id']!=50?' - '.$shipping_details['estimated_delivery']:'');
		if ($shipment['blind']) $data['blind?'] = 1;
		if (!empty($shipment['order_po_number'])) $data['reference_number?'] = $shipment['order_po_number'];
		if ($shipment['reclaimed_materials']) $data['reclaimed_materials?'] = 1;

		if (!is_null($shipment['shipment_account_choice'])) {
			switch ($shipment['shipment_account_choice']) {
				case 2:
					$data['shipping_account?'] = [];
					if (in_array(@$shipping_details_full['carrier_id'], ['fedexnonsaturday', 'fedexwebservices'])) {
						$data['shipping_account?']['carrier'] = 'FedEx';
						$data['shipping_account?']['account_number'] = $customer->get_header('fedex_account_number');
					}
					elseif (in_array(@$shipping_details_full['carrier_id'], ['iux'])) {
						$data['shipping_account?']['carrier'] = 'UPS';
						$data['shipping_account?']['account_number'] = $customer->get_header('ups_account_number');
					}
					break;
				case 0:
					$data['shipping_account?'] = [];
					if (in_array(@$shipping_details_full['carrier_id'], ['fedexnonsaturday', 'fedexwebservices'])) {
						$data['shipping_account?']['carrier'] = 'FedEx';
						$data['shipping_account?']['account_number'] = $shipment['fedex_account_number'];
					}
					elseif (in_array(@$shipping_details_full['carrier_id'], ['iux'])) {
						$data['shipping_account?']['carrier'] = 'UPS';
						$data['shipping_account?']['account_number'] = $shipment['ups_account_number'];
					}
					break;
			}
		}

		$payment_method = ck_payment_method_lookup::instance()->lookup_by_id($payment['payment_method_id']);
		$data['payment_method'] = $payment_method['method_label'];

		if ($customer->has_credit() && $customer->cannot_place_any_order()) $data['cannot_place_any_order'] = 1; // this needs to show whether they're using terms or not

		switch ($payment_method['method_code']) {
			case 'creditcard_pp':
				$paymentSvcApi = new PaymentSvcApi();
				$card = json_decode($paymentSvcApi->findCard($payment['payment_card_id']), TRUE);
				$card = $card['result'];

				$data['additional_billing_info'] = ['description' => 'Card Info'];
				$data['additional_billing_info']['info'] = '<img class="cart-type" src="'.$card['cardimgUrl'].'" title="'.$card['cardType'].'"> card ending in '.$card['lastFour'].', Expires '.$card['expirationDate'];

				//['cardimgUrl']['cardType']['lastFour']['cardholderName']['expirationDate']
				break;
			case 'paypal':
				break;
			case 'net10':
			case 'net15':
			case 'net30':
			case 'net45':
				$data['send_checks?'] = 1;
				$data['additional_billing_info'] = ['description' => 'PO Number', 'info' => $payment['payment_po_number']];

				if ($customer->cannot_place_any_order()) $data['cannot_place_any_order'] = 1; // this is double set just because it's easier that way, since it should short circuit the next two conditions
				elseif ($customer->get_credit('credit_status_id') == ck_customer2::CREDIT_PREPAID) $data['prepaid_only'] = 1;
				elseif (!$customer->can_place_credit_order($cart->get_totals('total')['value'])) {
					$data['over_limit'] = 1;
					$data['remaining_credit'] = CK\text::monetize($customer->get_remaining_credit());
				}
				break;
			case 'checkmo':
				$data['send_checks?'] = 1;
				$data['checkmo?'] = 1;
				break;
			case 'accountcredit':
				break;
			/*case 'customer_service':
				break;*/
		}

		$data['order_totals'][] = [
			'is_shipping' => TRUE,
			'description' => $shipping_details['name'].($shipment['shipping_method_id']!=50?' ('.$shipping_details['estimated_delivery'].')':''),
			'class' => 'shipping-total',
			'total' => !is_null($shipment['shipment_account_choice'])&&in_array($shipment['shipment_account_choice'], [2, 0])?'Bill Customer Account':CK\text::monetize($shipping_details['price'])
		];

		if ($shipment['freight_needs_liftgate']) $data['order_totals'][] = ['description' => 'Liftgate Needed for Freight Shipment', 'class' => 'liftgate-total', 'total' => $shipment['residential']?'Included':CK\text::monetize(70)];
		if ($shipment['freight_needs_inside_delivery']) $data['order_totals'][] = ['description' => 'Inside Delivery Needed for Freight Shipment', 'class' => 'inside-total', 'total' => CK\text::monetize(100)];
		if ($shipment['freight_needs_limited_access']) $data['order_totals'][] = ['description' => 'Limited Access Accommodation Needed for Freight Shipment', 'class' => 'limitaccess-total', 'total' => CK\text::monetize(100)];

		// ugh.  Still on the old module system for coupons & tax, including order class etc etc etc.
		if (!empty($payment['payment_coupon_code'])) $_POST['gv_redeem_code'] = $payment['payment_coupon_code'];

		require(DIR_WS_CLASSES.'order.php');
		$GLOBALS['order'] = new order;

		include(DIR_WS_CLASSES.'order_total.php');
		$order_total_modules = new order_total;
		$order_total_modules->collect_posts();
		$order_total_modules->pre_confirmation_check();
		$order_total_modules->process();

		$coupon = 0;

		foreach ($order_total_modules->modules as $module) {
			$class = substr($module, 0, strrpos($module, '.'));

			switch ($class) {
				case 'ot_coupon':
					foreach ($GLOBALS[$class]->output as $cpn) {
						$data['order_totals'][] = [
							'description' => preg_replace('/:\s*$/', '', $cpn['title']),
							'class' => 'coupon-total',
							'total' => '-'.CK\text::monetize($cpn['value'])
						];
					}
					break;
				case 'ot_tax':
					foreach ($GLOBALS[$class]->output as $tax) {
						$data['order_totals'][] = [
							'description' => preg_replace('/:\s*$/', '', $tax['title']),
							'class' => 'tax-total',
							'total' => CK\text::monetize($tax['value'])
						];
					}
					break;
				case 'ot_total':
					foreach ($GLOBALS[$class]->output as $ttl) {
						$data['order_totals'][] = [
							'description' => preg_replace('/:\s*$/', '', $ttl['title']),
							'class' => 'overall-total',
							'total' => CK\text::monetize($ttl['value'])
						];
					}
					break;
			}
		}

		if ($cart->has('customer_comments')) $data['comments'] = $cart->get_header('customer_comments');
		if ($cart->has('admin_comments')) $data['admin_comments'] = $cart->get_header('admin_comments');

		//var_dump($data);

		$this->render('page-checkout-confirmation.mustache.html', $data);
		$this->flush();
	}
}
?>
