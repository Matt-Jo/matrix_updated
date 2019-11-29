<?php
class ck_view_admin_customer_quote extends ck_view {

	protected $url = '/customer-quote.php';
	protected $customers_id;
	protected $ups_shipping_method_map = [
		'03' => '23', // UPS Ground
		'12' => '22', // UPS 3 Day Select
		'59' => '20', // UPS Second Day Air AM
		'02' => '21', // UPS Second Day Air
		'13' => '19', // UPS Next Day Air Saver
		'14' => '17', // UPS Next Day Air Early AM
		'01' => '18' // UPS Next Day Air
	];

	protected $page_templates = [
		'customer_quote' => 'page-customer-quote.mustache.html',
		'quick_order' => 'page-quick-order.mustache.html'
	];

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_POST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'update-quote':
				$quote = new ck_quote($_REQUEST['customer_quote_id']);

				switch (@$_POST['quote-update-action']) {
					case 'Update':
						$page = '/admin/customer-quote.php?customer_quote_id='.$_REQUEST['customer_quote_id'];

						$expiration_date = new DateTime($_REQUEST['expiration_date']);

						$customers_id = $_REQUEST['customers_id'];
						$customers_extra_logins_id = $_REQUEST['customers_extra_logins_id'];

						$email_address = $_REQUEST['customer_email_address'];

						if (!empty($email_address) && ($customer = ck_customer2::get_customer_by_email($email_address))) {
							$customers_id = $customer->id();
							if ($customer->get_email_address_id($email_address) == $customers_id) $customers_extra_logins_id = NULL;
							else $customers_extra_logins_id = $customer->get_email_address_id($email_address);
						}

						$fields = [
							':customer_email' => $email_address,
							':expiration_date' => $expiration_date->format('Y-m-d'),
							':customers_id' => $customers_id,
							':customers_extra_logins_id' => $customers_extra_logins_id
						];

						if (CK\fn::check_flag(@$_REQUEST['remove_customers_id'])) {
							$fields[':customers_id'] = NULL;
							$fields[':customers_extra_logins_id'] = NULL;
						}

						if ($quote->expired() && $expiration_date > self::NOW()) $fields[':active'] = 1;

						$quote->update_quote($fields);

						if (!empty($_REQUEST['delete_quoted'])) {
							foreach ($_REQUEST['delete_quoted'] as $quote_product_id => $delete) {
								if (!CK\fn::check_flag($delete)) continue;

								$quote->update_quote_line($quote_product_id, 0);
							}
						}

						if (!empty($_REQUEST['quote_quantity'])) {
							foreach ($_REQUEST['quote_quantity'] as $quote_product_id => $qty) {
								if (CK\fn::check_flag(@$_REQUEST['delete_quoted'][$quote_product_id])) continue;

								$price = preg_replace('/[^0-9.]/', '', $_REQUEST['quote_price'][$quote_product_id]);

								$quote->update_quote_line($quote_product_id, $qty, $price);
							}
						}
						break;
					case 'Release':
						$page = '/admin/customer-quote.php?customer_quote_id='.$_REQUEST['customer_quote_id'];
						$quote->release();
						break;
					case 'Release & Email':
						$page = '/quote.php?customer_quote_id='.$_REQUEST['customer_quote_id'];
						$quote->release();
						break;
					case 'Lock for Editing':
						$page = '/admin/customer-quote.php?customer_quote_id='.$_REQUEST['customer_quote_id'];
						$quote->lock_for_work();
						break;
					default:
						break;
				}
				break;
			case 'create-order':
				$savepoint = self::transaction_begin();
				try {
					$customer_quote = new ck_quote($_REQUEST['customer_quote_id']);
					$customer = new ck_customer2($customer_quote->get_header('customers_id'));

					$created_date = new DateTime('NOW');
					$created_date->setTimezone(new DateTimeZone('America/New_York'));

					$dropship = 0;

					if (!empty($_REQUEST['blind_shipment'])) $dropship = 1;

					$shipping_address = $customer_address = new ck_address2($customer->get_header('default_address_id'));

					$order = [
						'header' => [
							'customers_id' => $customer->id(),
							'customers_extra_logins_id' => NULL,
							'customers_name' => $customer->get_header('first_name').' '.$customer->get_header('last_name'),
							'customers_company' => $customer_address->get_header('company_name'),
							'customers_street_address' => $customer_address->get_header('address1'),
							'customers_suburb' => $customer_address->get_header('address2'),
							'customers_city' => $customer_address->get_header('city'),
							'customers_postcode' => $customer_address->get_header('postcode'),
							'customers_state' => $customer_address->get_header('state'),
							'customers_country' => $customer_address->get_header('country'),
							'customers_telephone' => $customer_address->get_header('telephone'),
							'customers_email_address' => $customer->get_header('email_address'),
							'customers_address_format_id' => 2,
							'delivery_name' => $_REQUEST['first_name'].' '.$_REQUEST['last_name'],
							'delivery_company' => $_REQUEST['company_name'],
							'delivery_street_address' => $_REQUEST['address1'],
							'delivery_suburb' => $_REQUEST['address2'],
							'delivery_city' => $_REQUEST['city'],
							'delivery_postcode' => $_REQUEST['postcode'],
							'delivery_state' => $_REQUEST['state'],
							'delivery_country' => $_REQUEST['country'],
							'delivery_telephone' => $_REQUEST['telephone'],
							'delivery_address_format_id' => 2,
							'billing_name' => $shipping_address->get_header('first_name').' '.$shipping_address->get_header('last_name'),
							'billing_company' => $shipping_address->get_header('company_name'),
							'billing_street_address' => $shipping_address->get_header('address1'),
							'billing_suburb' => $shipping_address->get_header('address2'),
							'billing_city' => $shipping_address->get_header('city'),
							'billing_postcode' => $shipping_address->get_header('postcode'),
							'billing_state' => $shipping_address->get_header('state'),
							'billing_country' => $shipping_address->get_header('country'),
							'billing_address_format_id' => 2,
							'payment_method_id' => $_REQUEST['payment_type'],
							'dropship' => $dropship,
							'date_purchased' => $created_date->format('Y-m-d H:i:s'),
							'last_modified' => date('Y-m-d H:i:s'),
							'orders_status' => 11,
							'orders_sub_status' => 1,
							'legacy_order' => FALSE,
							'channel' => 'phone',
							'currency' => 'USD',
							'currency_value' => 1,
							'ca_order_id' => NULL,
							'marketplace_order_id' => NULL,
							'amazon_order_number' => NULL,
							'ebay_order_id' => NULL,
							'paymentsvc_id' => NULL,
							'net10_po' => !empty($_REQUEST['net10_po'])?$_REQUEST['net10_po']:'',
							'net15_po' => !empty($_REQUEST['net15_po'])?$_REQUEST['net15_po']:'',
							'net30_po' => !empty($_REQUEST['net30_po'])?$_REQUEST['net30_po']:'',
							'net45_po' => !empty($_REQUEST['net45_po'])?$_REQUEST['net45_po']:'',
							'purchase_order_number' => !empty($_REQUEST['purchase_order_number'])?$_REQUEST['purchase_order_number']:''
						],
						'payment' => [
							'payment_method_id' => $_REQUEST['payment_type'],
							'payment_method' => '',
							'payment_transaction_id' => '',
							'amount' => $_REQUEST['order_total'],
							'marketplace' => FALSE
						],
						'products' => [],
						'totals' => [],
						'customer_notes' => NULL,
						'admin_notes' => [],
						'check_fraud' => FALSE,
						'send_to_crm' => TRUE,
						'notify' => FALSE
					];

					if ($customer_quote->has_direct_account_manager()) $order['header']['orders_sales_rep_id'] = $customer_quote->get_account_manager()->id();

					if (!empty($_REQUEST['customers_ups'])) $order['header']['customers_ups'] = $_REQUEST['customers_ups'];
					if (!empty($_REQUEST['customers_fedex'])) $order['header']['customers_fedex'] = $_REQUEST['customers_fedex'];

					foreach ($customer_quote->get_products() as $product) {
						$order['products'][$product['products_id']] = [
							'products_id' => $product['products_id'],
							'products_model' => $product['listing']->get_header('products_model'),
							'products_name' => $product['listing']->get_header('products_name'),
							'products_quantity' => $product['quantity'],
							'final_price' => $product['price'],
							'display_price' => $product['price'],
							'products_tax' => 0,
							'price_reason' => '',
							'option_type' => ck_cart::$option_types['NONE']
						];
					}

					$order['admin_notes'][] = ['orders_note_user' => ck_sales_order::$solutionsteam_id, 'orders_note_text' => 'Order Created By Admin'];

					if (!empty($_REQUEST['admin_notes'])) $order['admin_notes'][] = ['orders_note_user' => ck_sales_order::$solutionsteam_id, 'orders_note_text' => $_REQUEST['admin_notes']];

					$order['customer_notes'] = $_REQUEST['customer_comments'];

					$tax = 0;

					// we'll redo avatax at some point, but for now it requires the legacy order class
					require_once(__DIR__.'/../functions/avatax.php');

					$legacy_order_object = (object) [
						'info' => [
							'shipping_method' => '',
							'shipping_cost' => ''
						],
						'customer' => [
							'id' => $customer->id(),
							'email_address' => $customer->get_header('email_address')
						],
						'delivery' => [
							'street_address' => $shipping_address->get_header('address1'),
							'suburb' => $shipping_address->get_header('address2'),
							'city' => $shipping_address->get_header('city'),
							'zone_id' => $shipping_address->get_header('zone_id'),
							'country' => ['iso_code_2' => $shipping_address->get_header('countries_iso_code_2')],
							'postcode' => $shipping_address->get_header('postcode')
						],
						'products' => []
					];

					foreach ($customer_quote->get_products() as $product) {
						$legacy_order_object->products[] = [
							'id' => $product['products_id'],
							'model' => $product['listing']->get_header('products_model'),
							'name' => $product['listing']->get_header('products_name'),
							'qty' => $product['quantity'],
							'final_price' => $product['price']
						];
					}

					$tax = avatax_get_tax($legacy_order_object->products, $legacy_order_object->info['shipping_cost']);

					$order['totals'][] = ['value' => $_REQUEST['order_total'], 'class' => 'ot_total'];
					$order['totals'][] = ['value' => $tax, 'class' => 'ot_tax'];

					//we aren't using coupons for quick order right now
					//$order['totals'][] = ['value' => $_REQUEST['order_coupon'], 'class' => 'ot_coupon'];

					$shipping_method = self::query_fetch('SELECT shipping_code, name FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::ROW, [':shipping_method_id' => $_REQUEST['shipping_method']]);

					$order['totals'][] = ['value' => $_REQUEST['shipping_total_cost'], 'title' => $shipping_method['name'], 'external_id' => $shipping_method['shipping_code'], 'class' => 'ot_shipping'];

					$sales_order = ck_sales_order::create($order);
					$orders_id = $sales_order->id();

					$page = '/admin/orders_new.php?selected_box=orders&oID='.$orders_id.'&action=edit';

					if ($_REQUEST['redirect_page'] == 'quote-page') $page = '/admin/customer_quote_dashboard.php';
				}
				catch (Exception $e) {
					self::transaction_rollback($savepoint);
					throw $e;
				}
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
		$response = [];

		switch ($_REQUEST['action']) {
			case 'customer-lookup':
				$response['results'] = [];
				$email = preg_replace('/\s/', '', $_REQUEST['email']);

				if (strlen($email) <= 2) return FALSE;

				$emails = self::query_fetch('(SELECT customers_id, NULL as customers_extra_logins_id, customers_email_address FROM customers WHERE customers_email_address LIKE :email) UNION (SELECT customers_id, customers_extra_logins_id, customers_emailaddress as customers_email_address FROM customers_extra_logins WHERE customers_emailaddress LIKE :email) ORDER BY customers_email_address ASC', cardinality::SET, [':email' => '%'.$email.'%']);

				foreach ($emails as $address) {
					$response['results'][] = [
						'result_id' => 'c-'.$address['customers_id'].'-'.$address['customers_extra_logins_id'],
						'customers_id' => $address['customers_id'],
						'customers_extra_logins_id' => $address['customers_extra_logins_id'],
						'email_address' => $address['customers_email_address'],
						'field_value' => $address['customers_email_address'],
						'result_label' => preg_replace('/('.$email.')/i', '<strong>$1</strong>', $address['customers_email_address'])
					];
				}

				break;
			case 'product-lookup':
				$response['results'] = [];
				$model = preg_replace('/\s/', '', $_REQUEST['product']);

				if (strlen($model) <= 2) return FALSE;

				$products = self::query_fetch('SELECT DISTINCT psc.stock_id, psc.stock_name as ipn, p.products_id, p.products_model as model, p.products_status as status FROM products_stock_control psc JOIN products p ON psc.stock_id = p.stock_id WHERE psc.stock_name LIKE :model OR p.products_model LIKE :model ORDER BY psc.stock_name ASC', cardinality::SET, [':model' => '%'.$model.'%']);

				foreach ($products as $product) {
					$prod = new ck_product_listing($product['products_id']);
					$label_ipn = preg_replace('/('.$model.')/i', '<strong>$1</strong>', $product['ipn']);
					$label_model = preg_replace('/('.$model.')/i', '<strong>$1</strong>', $product['model']);
					$row = [
						'result_id' => 'p-'.$product['products_id'],
						'field_value' => $product['model'],
						'result_label' => $label_model,
						'stock_id' => $product['stock_id'],
						'products_id' => $product['products_id'],
						'ipn' => $label_ipn,
						'model' => $label_model,
						'price' => number_format($prod->get_price('original'), 2),
						'dealer_price' => number_format($prod->get_price('dealer'), 2),
						'wholesale_high_price' => number_format($prod->get_price('wholesale_high'), 2),
						'wholesale_low_price' => number_format($prod->get_price('wholesale_low'), 2),
						'special_price' => !empty($prod->get_price('special'))?number_format($prod->get_price('special'), 2):'',
						'available' => $prod->get_inventory('available'),
						'drop_ship' => $prod->get_ipn()->is('drop_ship')?'Y':'N',
						'non_stock' => $prod->get_ipn()->is('non_stock')?'Y':'N',
						'freight' => $prod->is('freight')?'Y':'N',
						'discontinued' => $prod->is('discontinued')?'Y':'N',
						'status' => $product['status']==1?'Y':'N',
					];

					if ($row['drop_ship'] == 'Y') $row['ds?'] = 1;
					if ($row['non_stock'] == 'Y') $row['ns?'] = 1;
					if ($row['freight'] == 'Y') $row['f?'] = 1;
					if ($row['discontinued'] == 'Y') $row['disc?'] = 1;
					if ($row['status'] == 'Y') $row['on?'] = 1;

					$response['results'][] = $row;
				}

				break;
			case 'add-product':
				$quote = new ck_quote($_REQUEST['customer_quote_id']);
				$product = new ck_product_listing($_REQUEST['products_id']);

				if (empty($_REQUEST['parent_products_id'])) {
					$price = NULL;

					if ($customers_id = $quote->get_header('customers_id')) {
						$customer = new ck_customer2($customers_id);
						$price = $customer->get_prices($product->get_header('stock_id'));
					}

					$quote->update_product($product, 1, $price, $is_total=TRUE, $parent_products_id=NULL, $option_type=NULL, $allow_discontinued=TRUE);
				}
				else {
					$parent_products_id = $_REQUEST['parent_products_id'];
					$option_type = !empty($_REQUEST['recommended'])?ck_cart::$option_types['RECOMMENDED']:ck_cart::$option_types['OPTIONAL'];

					$price = NULL;
					if ($parents = $product->get_parent_listings('extra')) {
						foreach ($parents as $parent) {
							if ($parent['products_id'] != $parent_products_id) continue;
							$price = $parent['addon_price'];
						}
					}

					$quote->update_product($product, 1, $price, TRUE, $parent_products_id, $option_type, $allow_discontinued=TRUE);
				}

				break;
			case 'get-shipping-methods':
				$package_detail[] = [
					'weight' => $_REQUEST['total_product_weight']
					/*'dim' => [
						'length' => $_REQUEST['package_length'],
						'width' => $_REQUEST['package_width'],
						'height' => $_REQUEST['package_height']
					]*/
				];

				$ck_address = api_ups::$local_origin;
				$address_type = new ck_address_type();
				$address_type->load('header', $ck_address);
				$ck_address = new ck_address2(NULL, $address_type);

				$delivery_address = [
					'company_name' => '',
					'address1' => '',
					'address2' => '',
					'postcode' => $_REQUEST['postcode'],
					'city' => '',
					'state' => $_REQUEST['state'],
					'zone_id' => '',
					'countries_id' => '',
					'country' => $_REQUEST['country'],
					'countries_iso_code_2' => '',
					'countries_iso_code_3' => '',
					'country_address_format_id' => '',
					'telephone' => '',
				];

				$address_type = new ck_address_type();
				$address_type->load('header', $delivery_address);
				$delivery_address = new ck_address2(NULL, $address_type);

				// we need to reindex the array that we get back from the api for our mustache template
				$ups_rates = array_values(api_ups::quote_rates($package_detail, $delivery_address, $ck_address));

				foreach ($ups_rates as $ups_rate) {
					$ups_shipping_methods[] = ['code' => $ups_rate['code'], 'service' => $ups_rate['service'], 'list' => $ups_rate['list'], 'negotiated' => $ups_rate['negotiated'], 'ck_shipping_method_id' => $this->ups_shipping_method_map[$ups_rate['code']]];
				}

				$response['shipping_quotes'] = $ups_shipping_methods;

				break;
			case 'add-address-to-customer':
				$zone_code = self::query_fetch('SELECT zone_code FROM zones WHERE zone_id = :zone_id', cardinality::SINGLE, [':zone_id' => $_REQUEST['state']]);

				$success = self::query_execute('INSERT INTO address_book (customers_id, entry_company, entry_firstname, entry_lastname, entry_street_address, entry_suburb, entry_postcode, entry_city, entry_state, entry_country_id, entry_zone_id, entry_telephone) VALUES (:customers_id, :company_name, :first_name, :last_name, :address1, :address2, :postcode, :city, :state, :country_id, :zone_id, :telephone)', cardinality::NONE, [':customers_id' => $_REQUEST['customers_id'], ':company_name' => $_REQUEST['company_name'], ':first_name' => $_REQUEST['first_name'], ':last_name' => $_REQUEST['last_name'], ':address1' => $_REQUEST['address1'], ':address2' => $_REQUEST['address2'], ':postcode' => $_REQUEST['postcode'], ':city' => $_REQUEST['city'], ':state' => $zone_code, ':country_id' => $_REQUEST['country'], ':zone_id' => $_REQUEST['state'], ':telephone' => $_REQUEST['telephone']]);

				$response['add_address'] = FALSE;
				if ($success) $response['add_address'] = TRUE;
				break;
			case 'assign-account-manager':
				$quote = new ck_quote($_REQUEST['customer_quote_id']);
				$quote->change_account_manager($_REQUEST['admin_id']);
				$response['success'] = 1;
				break;
			case 'assign-sales-team':
				$quote = new ck_quote($_REQUEST['customer_quote_id']);
				$quote->change_sales_team($_REQUEST['sales_team_id']);
				$response['success'] = 1;
				break;
			case 'update-prepared-by':
				$quote = new ck_quote($_REQUEST['customer_quote_id']);
				$quote->update_prepared_by(new ck_admin($_REQUEST['admin_id']));
				$response['success'] = 1;
				break;
			default:
				$response['errors'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {

		$data = $this->data();
		$template = 'customer_quote';

		if (!empty($_REQUEST['customer_quote_id']) && ($quote = new ck_quote($_REQUEST['customer_quote_id'])) && $quote->found()) {
			$data['customer_quote_id'] = $quote->id();
			$data['email_address'] = $quote->get_header('customer_email');

			if ($quote->expired()) $data['inactive?'] = 1;

			if ($quote->has_customer()) {
				$customer = $quote->get_customer();
				$data['customers_id'] = $customer->id();

				if (!$customer->owns_email_address($data['email_address'])) $data['remove_customers_id?'] = 1;
			}
			if ($quote->has('customers_extra_logins_id')) {
				$data['customers_extra_logins_id'] = $quote->get_header('customers_extra_logins_id');
				$data['cel?'] = 1;
			}

			$data['account_manager'] = $quote->has_account_manager()?$quote->get_account_manager()->get_name():'None';
			$data['sales_team'] = $quote->has_sales_team()?$quote->get_sales_team()->get_header('label'):'None';

			$data['change_account_manager'] = 1;
			$account_managers = ck_admin::get_account_managers(['ck_admin', 'sort_by_name']);

			$data['account_managers'] = [];
			$sales_admin = new ck_admin(ck_admin::$cksales_id);
			$data['prepared_by'][] = [
				'admin_id' => $sales_admin->id(),
				'name' => $sales_admin->get('first_name').' '. $sales_admin->get_header('last_name'),
				'email' => $sales_admin->get_header('email_address'),
				'phone_number' => $sales_admin->get_header('phone_number')
			];

			foreach ($account_managers as $account_manager) {
				$am = ['admin_id' => $account_manager->id(), 'name' => $account_manager->get_normalized_name()];
				if ($quote->has_account_manager() && $quote->get_account_manager()->id() == $account_manager->id()) {
					$am['selected?'] = 1;
				}
				$data['account_managers'][] = $am;

				// prepared by information
				$phone_number = $sales_admin->get_header('phone_number');
				if (!empty($account_manager->get_header('phone_number'))) $phone_number = $account_manager->get_header('phone_number');
				$prepared_by_data = [
					'admin_id' => $account_manager->id(),
					'name' => $account_manager->get_normalized_name(),
					'email' => $account_manager->get_header('email_address'),
					'phone_number' => $phone_number
				];
				if ($quote->get_header('prepared_by') == $account_manager->id()) $prepared_by_data['selected?'] = 1;
				$data['prepared_by'][] = $prepared_by_data;
			}

			$data['expiration_date'] = $quote->get_header('expiration_date')->format('Y-m-d');
			$data['expiration_date_formatted'] = $quote->get_header('expiration_date')->format('m/d/Y');
			$data['url_hash'] = $quote->get_key();
			if (!$quote->is('released')) $data['editable?'] = 1;

			$has_any_discontinued = FALSE;

			if ($products = $quote->get_products()) {
				$data['products'] = [];
				foreach ($products as $product) {
					if ($product['option_type'] != ck_cart::$option_types['NONE']) continue;

					$product_inventory = $product['listing']->get_inventory();
					$product_prices = $product['listing']->get_price();

					$row = [
						'quote_product_id' => $product['customer_quote_product_id'],
						'ipn' => $product['listing']->get_ipn()->get_header('ipn'),
						'products_id' => $product['listing']->id(),
						'quantity' => $product['quantity'],
						'name' => $product['listing']->get_header('products_name'),
						'name_attr' => htmlspecialchars($product['listing']->get_header('products_name')),
						'model_num' => $product['listing']->get_header('products_model'),
						'weight' => $product['listing']->get_total_weight(),
						'meta_condition' => $product['listing']->get_condition('meta'),
						'condition' => $product['listing']->get_condition(),
						'url' => $product['listing']->get_url(),
						'image' => $product['listing']->get_image('products_image'),
						'price' => number_format($product['price'], 2),
						'total_price' => '$'.number_format($product['price']*$product['quantity'], 2),
						'product_available' => $product_inventory['available'],
						'product_on_hand' => $product_inventory['on_hand'],
						'product_allocated' => $product_inventory['allocated'],
						'product_conditioning' => $product['listing']->get_ipn()->get_inventory('in_conditioning'),
						'product_on_order' => $product['listing']->get_header('on_order'),
						'product_stock_price' => number_format($product_prices['original'], 2),
						'product_dealer_price' => number_format($product_prices['dealer'], 2),
						'wholesale_high_price' => number_format($product_prices['wholesale_high'], 2),
						'wholesale_low_price' => number_format($product_prices['wholesale_low'], 2),
						'product_special_price' => !empty($product_prices['special'])?number_format($product_prices['special'], 2):''
					];

					if (empty($row['product_conditioning'])) $row['product_conditioning'] = 0;

					if (!empty($customer) && ($customer_price = $customer->get_prices($product['listing']->get_header('stock_id')))) {
						$row['product_customer_price'] = number_format($customer_price, 2);
					}

					if ($product['listing']->has_options('extra')) {
						$options = $product['listing']->get_options('extra');
						$row['product_optional_addons'] = [];
						foreach ($options as $option) {
							$opt = [
								'ipn' => $option['listing']->get_ipn()->get_header('ipn'),
								'products_id' => $option['products_id'],
								'parent_products_id' => $product['listing']->id(),
								'name' => $option['name'],
								'price' => '$'.number_format($option['price'], 2),
								'available' => 0, // preemptively set to zero, we will set the actual quantity below
							];
							
							if ($option['listing']->get_inventory('available') > 0) {
								$opt['available'] = $option['listing']->get_inventory('display_available');
							}

							if (!empty($option['recommended?'])) $opt['recommended?'] = 1;

							$row['product_optional_addons'][] = $opt;
						}
					}

					if ($product['listing']->is('discontinued')) {
						$row['discontinued?'] = 1;
						$has_any_discontinued = TRUE;
					}
					if ($product['listing']->is('freight')) $row['freight?'] = 1;
					if ($product['listing']->get_ipn()->is('drop_ship')) $row['drop_ship?'] = 1;
					if ($product['listing']->get_ipn()->is('non_stock')) $row['non_stock?'] = 1;

					$can_ship_today = TRUE;
					$qty_ship_today = $product['quantity'];
					$qty_back_order = 0;

					$product_total = 0;
					$product_total_weight = 0;

					if ($product['quantity'] > $row['product_available']) {
						$can_ship_today = FALSE;
						$any_out_of_stock_loc = 1;
						if ($row['product_available'] > 0) {
							$qty_ship_today = $row['product_available'];
							$qty_back_order = $product['quantity'] - $qty_ship_today;
							$something_can_ship_today = TRUE;
						}
						else {
							$qty_ship_today = 0;
							$qty_back_order = $product['quantity'];
						}
					}
					else {
						$something_can_ship_today = TRUE;
					}

					if ($product['listing']->has_options('included')) {
						$options = $product['listing']->get_options('included');
						$row['included_options?'] = [];
						foreach ($options as $option) {
							$row['included_options?'][] = ['name' => $option['name']];
						}
					}

					$product_total += $product['price'] * $product['quantity'];
					$product_total_weight += $product['listing']->get_total_weight() * $product['quantity'];

					$optional_addons = [];

					foreach ($products as $optional_addon) {
						if ($optional_addon['option_type'] == ck_cart::$option_types['NONE']) continue; // this is not an addon at all
						if ($optional_addon['option_type'] == ck_cart::$option_types['INCLUDED']) continue; // this is an included addon

						if ($optional_addon['parent_products_id'] != $product['products_id']) continue; // this is an optional addon for a different parent

						// if we get here, this item is an optional addon on our current parent

						$option_row = [
							'parent_quote_product_id' => $product['customer_quote_product_id'],
							'quote_product_id' => $optional_addon['customer_quote_product_id'],
							'products_id' => $optional_addon['listing']->id(),
							'parent_products_id' => $product['products_id'],
							'quantity' => $optional_addon['quantity'],
							'name' => $optional_addon['listing']->get_header('products_name'),
							'name_attr' => htmlspecialchars($optional_addon['listing']->get_header('products_name')),
							'model_num' => $optional_addon['listing']->get_header('products_model'),
							'weight' => $optional_addon['listing']->get_total_weight(),
							'meta_condition' => $optional_addon['listing']->get_condition('meta'),
							'condition' => $optional_addon['listing']->get_condition(),
							'url' => $optional_addon['listing']->get_url(),
							'image' => $optional_addon['listing']->get_image('products_image'),
							'available' => $optional_addon['listing']->get_inventory('available'),
							'price' => number_format($optional_addon['price'], 2),
							'total_price' => '$'.number_format($optional_addon['price']*$optional_addon['quantity'], 2)
						];

						if ($optional_addon['listing']->is('discontinued')) $option_row['discontinued?'] = 1;

						$product_total += $optional_addon['price'] * $optional_addon['quantity'];
						$product_total_weight += $optional_addon['listing']->get_total_weight() * $optional_addon['quantity'];

						$optional_addons[] = $option_row;
					}

					if (!empty($optional_addons)) $row['optional_addons'] = $optional_addons;

					$row['whole_total_price'] = CK\text::monetize($product_total);

					if (!empty($can_ship_today)) {
						$row['stock_status_color'] = '009900';

						// it's after 7 PM Monday through Thursday, or Sunday
						if ((date('w') < 5 && date('G') > 19) || date('w') == 0) $row['stock_status_message'] = 'Available to ship tomorrow';
						// it's after 7 PM Friday, or Saturday
						elseif ((date('w') == 5 && date('G') > 19) || date('2') == 6) $row['stock_status_message'] = 'Available to ship Monday';
						else $row['stock_status_message'] = 'Available to ship today';
					}
					elseif ($qty_ship_today <= 0) {
						$row['stock_status_message'] = 'Not available to ship immediately.';
						$row['stock_status_color'] = 'd22842';
					}
					else {
						$row['stock_status_color'] = '009900';

						// it's after 7 PM Monday through Thursday, or Sunday
						if ((date('w') < 5 && date('G') > 19) || date('w') == 0) $row['stock_status_message'] = $qty_ship_today.' available to ship tomorrow';
						// it's after 7 PM Friday, or Saturday
						elseif ((date('w') == 5 && date('G') > 19) || date('2') == 6) $row['stock_status_message'] = $qty_ship_today.' available to ship Monday';
						else $row['stock_status_message'] = $qty_ship_today.' available to ship today';

						$row['stock_status_color_2?'] = 'd22842';
						$row['stock_status_message_2?'] = $qty_back_order.' not available to ship immediately';
					}

					$data['products'][] = $row;
				}
			}

			if ($has_any_discontinued) $data['has_any_discontinued'] = 1;

			$data['quote_total'] = '$'.number_format($quote->get_total(), 2);
			$data['raw_quote_total'] = $quote->get_total();

		}
		elseif (!empty($quote)) {
			$data['bad_quote?'] = 1;
		}

		if (!empty($_REQUEST['create_order_action'])) {
			$customer = new ck_customer2($_REQUEST['customers_id']);

			$paymentSvcApi = new PaymentSvcApi();
			$token = json_decode($paymentSvcApi->getToken(), true);

			$data['customer_id'] = $customer->id();
			$data['braintree_token'] = $token['braintree_client_token'];

			$braintree_customer_id = $customer->get_header('braintree_customer_id');
			if (empty($braintree_customer_id)) {
				$custData = [
					'firstname' => $customer->get_header('first_name'),
					'lastname' => $customer->get_header('last_name'),
					'email' => $customer->get_header('email_address'),
					'token' => NULL,
					'owner' => $customer->id()
				];

				$result = json_decode($paymentSvcApi->createCustomer($custData), true);

				if ($result['result']['status'] == 'success') {
					$braintree_customer_id = $result['result']['CustomerId'];
					self::query_execute('UPDATE customers SET braintree_customer_id = :braintree_customer_id WHERE customers_id = :customers_id', cardinality::NONE, [':braintree_customer_id' => $braintree_customer_id, ':customers_id' => $customer->id()]);
				}
			}

			$data['braintree_customer_id'] = $braintree_customer_id;
			$customer_data = json_decode($paymentSvcApi->getCustomerCards($braintree_customer_id), true);

			if (!empty($customer_data['result']['cards'])) {
				foreach ($customer_data['result']['cards'] as $card) {
					$data['customer_cards'][] = [
						'cardType' => $card['cardType'],
						'lastFour' => $card['lastFour'],
						'expired' => $card['expired'],
						'token' => $card['token'],
						'expirationDate' => $card['expirationDate'],
						'cardholderName' => isset($card['cardholderName'])?$card['cardholderName']:'',
						'imageUrl' => $card['cardimgUrl'],
						'privateCard' => FALSE
					];
				}
			}
			
			if ($customer->has_credit() && $customer->has_terms()) $data['payment_terms'] = $customer->get_terms();

			$data['customer_email'] = $customer->get_header('email_address');
			$data['is_dealer'] = $customer->is('dealer');
			$data['has_own_shipping_account'] = $customer->has_own_shipping_account();
			$data['ups_account'] = $customer->get_header('ups_account_number');
			$data['fedex_account'] = $customer->get_header('fedex_account_number');

			$data['customer_quote_id'] = $_REQUEST['customer_quote_id'];

			$data['shipping_addresses'] = [];

			$data['has_shipping_addresses'] = false;
			if ($customer->has_addresses()) {
				$data['has_shipping_addresses'] = true;
				foreach ($customer->get_addresses() as $address) {
					if ($address->get_header('default_address')) {
						$data['default_address'][] = [
						'address_book_id' => $address->get_header('address_book_id'),
						'company_name' => $address->get_header('company_name'),
						'first_name' => $address->get_header('first_name'),
						'last_name' => $address->get_header('last_name'),
						'address1' => $address->get_header('address1'),
						'address2' => $address->get_header('address2'),
						'postcode' => $address->get_header('postcode'),
						'city' => $address->get_header('city'),
						'full_state' => $address->get_header('state'),
						'state' => $address->get_header('state_region_code'),
						'zone_id' => $address->get_header('zone_id'),
						'country' => $address->get_header('country'),
						'countries_id' => $address->get_header('countries_id'),
						'countries_iso_code_2' => $address->get_header('countries_iso_code_2'),
						'countries_iso_code_3' => $address->get_header('countries_iso_code_3'),
						'country_address_format_id' => $address->get_header('country_address_format_id'),
						'telephone' => $address->get_header('telephone'),
						'default_address' => $address->get_header('default_address')==1?true:false
						];
					}

					$data['shipping_addresses'][] = [
						'address_book_id' => $address->get_header('address_book_id'),
						'company_name' => $address->get_header('company_name'),
						'first_name' => $address->get_header('first_name'),
						'last_name' => $address->get_header('last_name'),
						'address1' => $address->get_header('address1'),
						'address2' => $address->get_header('address2'),
						'postcode' => $address->get_header('postcode'),
						'city' => $address->get_header('city'),
						'full_state' => $address->get_header('state'),
						'state' => $address->get_header('state_region_code'),
						'zone_id' => $address->get_header('zone_id'),
						'country' => $address->get_header('country'),
						'countries_id' => $address->get_header('countries_id'),
						'countries_iso_code_2' => $address->get_header('countries_iso_code_2'),
						'countries_iso_code_3' => $address->get_header('countries_iso_code_3'),
						'country_address_format_id' => $address->get_header('country_address_format_id'),
						'telephone' => $address->get_header('telephone'),
						'default_address' => $address->get_header('default_address')==1?true:false
					];
				}
			}

			$data['states'] = self::query_fetch('SELECT zone_country_id, zone_code, zone_name FROM zones', cardinality::SET, []);
			$data['countries'] = self::query_fetch('SELECT countries_id, countries_name, countries_iso_code_2 FROM countries', cardinality::SET, []);

			$data['total_product_weight'] = $product_total_weight;

			if ($product_total_weight >= 50) {
				$data['number_of_packages'] = $number_of_packages = ceil($product_total_weight/50);
				$data['package_weight'] = $package_weight = $product_total_weight/$number_of_packages;

				for ($i = 0; $i < $number_of_packages; $i++) {
					$package_detail[] = [
						'weight' => $package_weight
						/*'dim' => [
							'length' => $_REQUEST['package_length'],
							'width' => $_REQUEST['package_width'],
							'height' => $_REQUEST['package_height']
						]*/
					];
				}
			}
			else {
				$package_detail[] = [
					'weight' => $product_total_weight
					/*'dim' => [
						'length' => $_REQUEST['package_length'],
						'width' => $_REQUEST['package_width'],
						'height' => $_REQUEST['package_height']
					]*/
				];
			}

			if (!empty($data['default_address'])) {
				$ck_address = api_ups::$local_origin;
				$address_type = new ck_address_type();
				$address_type->load('header', $ck_address);
				$ck_address = new ck_address2(NULL, $address_type);

				$delivery_address = [
					'company_name' => $data['default_address'][0]['company_name'],
					'address1' => $data['default_address'][0]['address1'],
					'address2' => $data['default_address'][0]['address2'],
					'postcode' => $data['default_address'][0]['postcode'],
					'city' => $data['default_address'][0]['city'],
					'state' => $data['default_address'][0]['state'],
					'zone_id' => $data['default_address'][0]['zone_id'],
					'countries_id' => $data['default_address'][0]['countries_id'],
					'country' => $data['default_address'][0]['country'],
					'countries_iso_code_2' => $data['default_address'][0]['countries_iso_code_2'],
					'countries_iso_code_3' => $data['default_address'][0]['countries_iso_code_3'],
					'country_address_format_id' => $data['default_address'][0]['country_address_format_id'],
					'telephone' => $data['default_address'][0]['telephone'],
				];

				$address_type = new ck_address_type();
				$address_type->load('header', $delivery_address);
				$delivery_address = new ck_address2(NULL, $address_type);

				$data['free_shipping'] = [];

				if ($product_total >= 99) $data['free_shipping'] = ['cost' => '0.00', 'title' => 'Free Shipping'];
				if ($product_total < 99 && $product_total_weight < .9) $data['free_shipping'] = ['cost' => '3.99', 'title' => 'Standard Shipping'];

				$data['ups_shipping_methods'] = [];

				// we need to reindex the array that we get back from the api for our mustache template
				$ups_rates = array_values(api_ups::quote_rates($package_detail, $delivery_address, $ck_address));

				foreach ($ups_rates as $ups_rate) {
					$data['ups_shipping_methods'][] = ['code' => $ups_rate['code'], 'service' => $ups_rate['service'], 'list' => $ups_rate['list'], 'negotiated' => $ups_rate['negotiated'], 'ck_shipping_method_id' => $this->ups_shipping_method_map[$ups_rate['code']]];
				}
			}

			$template = 'quick_order';
		}

		$this->render($this->page_templates[$template], $data);
		$this->flush();
	}
}
?>
