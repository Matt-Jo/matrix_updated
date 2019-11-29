<?php
class ck_view_shopping_cart extends ck_view {

	protected $url = '/shopping_cart.php';

	protected $page_templates = [
		'legacy-header' => 'tpl-legacy-content.head.mustache.html',
		'legacy-footer' => 'tpl-legacy-content.foot.mustache.html',
		'shopping_cart' => 'page-shopping_cart.mustache.html',
		'address' => 'partial-address-format.mustache.html',
		'shipping_estimator' => 'partial-shipping_estimator.mustache.html'
	];

	public function process_response() {
		$__FLAG = request_flags::instance();

		if ($__FLAG['checkout_now']) CK\fn::redirect_and_exit('/checkout_shipping.php');

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		// there's nothing to do here
		echo json_encode(['errors' => []]);
		exit();
	}

	private function http_response() {
		$GLOBALS['breadcrumb']->add('Cart Contents', $this->url);

		$data = $this->data();

		$data['breadcrumbs?'] = $GLOBALS['breadcrumb']->trail();
		$this->render($this->page_templates['legacy-header'], $data);
		$this->flush();

		$data = $this->data();

		if (CK\fn::check_flag(@$_SESSION['admin'])) $data['admin?'] = 1;

		// if we're an admin, always show stock status
		// for customers, show stock status if it's Monday-Friday between 6 PM - 8 PM
		$data['show_stock_status?'] = NULL;
		if (CK\fn::check_flag(@$_SESSION['admin']) || (date('N') >= 1 && date('N') <= 5 && date('G') >= 18 && date('G') <= 20)) {
			$data['show_stock_status?'] = 1;
		}

		$any_out_of_stock_loc = 0;
		$any_freight_items_in_cart = FALSE;
		$something_can_ship_today = FALSE;

		$cart = $_SESSION['cart'];

		$data['contact_phone'] = $cart->get_contact_phone();
		$data['contact_email'] = $cart->get_contact_email();

		if ($cart->has_customer()) {
			$data['customers_id'] = $cart->get_header('customers_id');
			$data['customers_extra_logins_id'] = $cart->get_header('customers_extra_logins_id');
			$customer = $cart->get_customer();
			if (!empty($data['customers_extra_logins_id'])) $data['new_quote_customer_email_address'] = $customer->get_extra_logins($data['customers_extra_logins_id'])['email_address'];
			else $data['new_quote_customer_email_address'] = $customer->get_header('email_address');
			$exp = new DateTime();
			$exp->add(new DateInterval('P7D'));
			$data['new_quote_expiration_date'] = $exp->format('Y-m-d');
		}

		if ($quotes = ck_quote::get_quotes_by_customer($cart->get_header('customers_id'), $cart->get_header('customers_extra_logins_id'), NULL, TRUE)) {
			foreach ($quotes as $quote) {
				if (!$quote->is('released') && !CK\fn::check_flag(@$_SESSION['admin'])) continue;
				if (empty($data['available_quotes'])) $data['available_quotes'] = [];
				$data['available_quotes'][] = ['quote_id' => $quote->id(), 'quote_label' => $quote->id().' - Expires: '.$quote->get_header('expiration_date')->format('m/d/Y')];
			}
		}

		// most of the rest here is needed for shipping estimator, but we need the address figuring for free ship eligibility

		if ($cart->has_customer()) {
			$customer = $cart->get_customer();
			try {
				if (!empty($_REQUEST['address_id'])) $cart->update_active_shipment(['shipping_address_book_id' => $_REQUEST['address_id']]);

				$addresses = $customer->get_addresses();

				// this automatically will fall through session, default, first-on-customer, or NULL
				$address = $cart->get_shipping_address();
			}
			catch (CKCartException $e) {
				// don't care, we just won't have set the address
			}
		}

		// if we're not logged in, or the customer has no address record, just do the normal country drop down
		if (empty($address)) {
			$address_data = array(
				'address1' => NULL,
				'address2' => NULL,
				'postcode' => NULL,
				'city' => NULL,
				'state' => NULL,
				'countries_id' => NULL,
				'country' => NULL,
				'countries_iso_code_2' => NULL,
				'countries_iso_code_3' => NULL,
				'country_address_format_id' => NULL,
			);

			if (isset($_REQUEST['country_id'])) {
				// user changed country for this call
				$country = ck_address2::get_country($_REQUEST['country_id']);

				$address_data['countries_id'] = $_REQUEST['country_id'];
				$address_data['country'] = $country['countries_name'];
				$address_data['countries_iso_code_2'] = $country['countries_iso_code_2'];
				$address_data['countries_iso_code_3'] = $country['countries_iso_code_3'];
				$address_data['country_address_format_id'] = $country['address_format_id'];

				$address_data['postcode'] = !empty($_REQUEST['zip_code'])?$_REQUEST['zip_code']:$country['default_postcode'];
				$address_data['zone_id'] = 19; // default to our zone

				$_SESSION['cart_country_id'] = $address_data['countries_id'];
				$_SESSION['cart_zip_code'] = $address_data['postcode'];
			}
			elseif (!empty($_SESSION['cart_country_id'])) {
				// user has changed country during this session
				$country_info = ck_address2::get_country($_SESSION['cart_country_id']);

				$address_data['countries_id'] = $_SESSION['cart_country_id'];
				$address_data['country'] = $country_info['countries_name'];
				$address_data['countries_iso_code_2'] = $country_info['countries_iso_code_2'];
				$address_data['countries_iso_code_3'] = $country_info['countries_iso_code_3'];
				$address_data['country_address_format_id'] = $country_info['address_format_id'];

				$address_data['postcode'] = $_SESSION['cart_zip_code'];
				$address_data['zone_id'] = 19; // default to our zone
			}
			else {
				// user has not explicitly change the country during this session
				$_SESSION['cart_country_id'] = $GLOBALS['ck_keys']->cart['default_country'];
				$_SESSION['cart_zip_code'] = $GLOBALS['ck_keys']->cart['shipping_origin_postcode'];

				$country_info = ck_address2::get_country($_SESSION['cart_country_id']);

				$address_data['countries_id'] = $_SESSION['cart_country_id'];
				$address_data['country'] = $country_info['countries_name'];
				$address_data['countries_iso_code_2'] = $country_info['countries_iso_code_2'];
				$address_data['countries_iso_code_3'] = $country_info['countries_iso_code_3'];
				$address_data['country_address_format_id'] = $country_info['address_format_id'];

				$address_data['postcode'] = $_SESSION['cart_zip_code'];
				$address_data['zone_id'] = 19; // default to our zone
			}

			$data['estimator_postcode'] = @$_REQUEST['zip_code'];

			$address_type = new ck_address_type();
			$address_type->load('header', $address_data);

			$address = new ck_address2(NULL, $address_type);
		}

		if (empty($addresses)) {
			$countries = prepared_query::fetch('SELECT countries_id, countries_name FROM countries ORDER BY countries_name ASC', cardinality::SET);
			foreach ($countries as $idx => $country) {
				if (!empty($_SESSION['cart_country_id']) && $country['countries_id'] == $_SESSION['cart_country_id']) $countries[$idx]['selected?'] = 1;
				elseif ($country['countries_id'] == $GLOBALS['ck_keys']->cart['default_country']) $countries[$idx]['selected?'] = 1;
			}
			$data['countries'] = $countries;
		}
		else {
			$data['address_list?'] = 1;
			$address_list = [];

			foreach ($addresses as $address_entry) {
				$option = $address_entry->get_address_line_template(['city', 'postcode', 'state', 'country'], ' ');
				$option['address_book_id'] = $address_entry->get_header('address_book_id');

				if ($address_entry->get_header('address_book_id') == $address->get_header('address_book_id')) $option['selected?'] = 1;

				$address_list[] = $option;
			}

			$data['addresses'] = $address_list;
		}

		if ($cart->is_freeship_eligible($address, FALSE)) {
			$data['eligible_for_free_shipping?'] = 1;

			if ($_SESSION['cart']->get_total() >= $GLOBALS['ck_keys']->product['freeship_threshold']) {
				$data['free_shipping_message'] = 'This order qualifies for <u>FREE Standard Shipping</u>!';
			}
			else {
				$diff = $GLOBALS['ck_keys']->product['freeship_threshold'] - $cart->get_total();
				$data['free_shipping_message'] = 'Only <span style="color: #000000;">$'.number_format($diff, 2).'</span> more to qualify for <u>FREE Standard Shipping</u>!';
			}
		}


		if (!$cart->has_any_products()) {
			$data['empty?'] = 1;

			$this->render($this->page_templates['shopping_cart'], $data);
			$this->flush();
		}
		else {
			$quotes = $cart->get_quotes();
			$products = $cart->get_products();

			$any_products = FALSE;

			$data['listrak_products'] = [];

			if (!empty($products)) {
				$data['products'] = [];

				foreach ($products as $product) {
					if ($product['option_type'] != ck_cart::$option_types['INCLUDED']) {
						$data['listrak_products'][] = [
							'sku' => $product['products_id'],
							'qty' => $product['quantity'],
							'unit_price' => $product['unit_price'],
							'name' => $product['listing']->get_header('products_name'),
							'image' => $product['listing']->get_image('products_image'),
							'url' => $product['listing']->get_url()
						];
					}

					if ($product['option_type'] != ck_cart::$option_types['NONE']) continue;

					$any_products = TRUE;

					$row = [
						'cart_product_id' => $product['cart_product_id'],
						'products_id' => $product['products_id'],
						'quantity' => $product['quantity'],
						'name' => $product['listing']->get_header('products_name'),
						'name_attr' => htmlspecialchars($product['listing']->get_header('products_name')),
						'model_num' => $product['listing']->get_header('products_model'),
						'weight' => $product['listing']->get_total_weight(),
						'meta_condition' => $product['listing']->get_condition('meta'),
						'condition' => $product['listing']->get_condition(),
						'url' => $product['listing']->get_url(),
						'image' => $product['listing']->get_image('products_image'),
						'available' => $product['listing']->get_inventory('available'),
						'price' => '$'.number_format($product['unit_price'], 2),
						'total_price' => '$'.number_format($product['unit_price']*$product['quantity'], 2),
						'discontinued' => $product['listing']->is('discontinued')?1:0
					];

					if ($product['listing']->is('discontinued')) $row['discontinued?'] = 1;

					$can_ship_today = TRUE;
					$qty_ship_today = $product['quantity'];
					$qty_back_order = 0;

					$product_total = 0;

					if ($product['listing']->is('freight')) $any_freight_items_in_cart = TRUE;

					if ($product['quantity'] > $row['available']) {
						$can_ship_today = FALSE;
						$any_out_of_stock_loc = 1;
						if ($row['available'] > 0) {
							$qty_ship_today = $row['available'];
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

					$inc_idx = NULL;
					$opt_idx = NULL;

					$row['included_options'] = [];
					if ($product['listing']->has_options('included')) {
						//$row['has_options?'] = 1;
						$options = $product['listing']->get_options('included');
						foreach ($options as $inc_idx => $option) {
							$row['included_options'][] = ['name' => $option['name']];
						}
					}

					$product_total += $product['unit_price'] * $product['quantity'];

					$optional_addons = [];

					foreach ($products as $optional_addon) {
						if ($optional_addon['option_type'] == ck_cart::$option_types['NONE']) continue; // this is not an addon at all
						if ($optional_addon['option_type'] == ck_cart::$option_types['INCLUDED']) continue; // this is an included addon

						if ($optional_addon['parent_products_id'] != $product['products_id']) continue; // this is an optional addon for a different parent

						$row['has_options?'] = 1;

						// if we get here, this item is an optional addon on our current parent

						$addon_name = $optional_addon['listing']->get_header('products_name');
						foreach ($product['listing']->get_options('extra') as $option) {
							if ($option['products_id'] != $optional_addon['products_id']) continue;
							$addon_name = $option['name'];
							break;
						}

						if (is_null($opt_idx)) $opt_idx = 0;
						else $opt_idx++;

						$option_row = [
							'cart_product_id' => $optional_addon['cart_product_id'],
							'parent_cart_product_id' => $product['cart_product_id'],
							'parent_products_id' => $product['products_id'],
							'products_id' => $optional_addon['products_id'],
							'option_type' => $optional_addon['option_type'],
							'quantity' => $optional_addon['quantity'],
							'name' => $addon_name,
							'name_attr' => htmlspecialchars($optional_addon['listing']->get_header('products_name')),
							'model_num' => $optional_addon['listing']->get_header('products_model'),
							'weight' => $optional_addon['listing']->get_total_weight(),
							'meta_condition' => $optional_addon['listing']->get_condition('meta'),
							'condition' => $optional_addon['listing']->get_condition(),
							'url' => $optional_addon['listing']->get_url(),
							'image' => $optional_addon['listing']->get_image('products_image'),
							'available' => $optional_addon['listing']->get_inventory('available'),
							'price' => '$'.number_format($optional_addon['unit_price'], 2),
							'total_price' => '$'.number_format($optional_addon['unit_price']*$optional_addon['quantity'], 2),
							'discontinued' => $optional_addon['listing']->is('discontinued')?1:0
						];

						if ($optional_addon['listing']->is('discontinued')) $option_row['discontinued?'] = 1;

						$product_total += $optional_addon['unit_price']*$optional_addon['quantity'];

						$optional_addons[] = $option_row;
					}

					if (!is_null($opt_idx)) $optional_addons[$opt_idx]['last?'] = 1;

					//if (!empty($optional_addons))
					$row['optional_addons'] = $optional_addons;

					$row['whole_total_price'] = '$'.number_format($product_total, 2);

					if (!empty($data['show_stock_status?'])) {
						if (!empty($can_ship_today)) {

							$row['stock_status_message_2?'] = NULL;
							$row['stock_status_color_2?'] = NULL;

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
					}

					$data['products'][] = $row;
				}
			}

			$data['product_total'] = CK\text::monetize($cart->get_product_total());

			if (!empty($quotes)) {
				unset($data['available_quotes']);
				$data['quotes'] = [];

				foreach ($quotes as $quote) {
					if (!$quote['quote']->found()) {
						$cart->remove_cart_quote($quote['quote']->id());
						continue;
					}
					// this is a bit brute force to reassociate the quote each time, but at least at first I think it's needed
					// it's built so that if it's already associated correctly, it won't do anything
					$quote['quote']->associate_to_account($cart, TRUE);

					$qproducts = $quote['quote']->get_products();
					$qprodlist = ['quote_id' => $quote['quote']->id(), 'products' => []];
					if ($quote['quote']->is('released')) $qprodlist['released?'] = 1;
					// if this quote isn't yet released to the customer and we're not an admin, don't show the items to the customer even if they've added it to their cart
					if (!$quote['quote']->is('released') && !CK\fn::check_flag(@$_SESSION['admin'])) {
						$qprodlist['hidden?'] = 1;
						$data['quotes'][] = $qprodlist;
						continue;
					}

					foreach ($qproducts as $product) {
						if ($product['option_type'] != ck_cart::$option_types['NONE']) continue;

						$any_products = TRUE;

						$row = [
							'customer_quote_product_id' => $product['customer_quote_product_id'],
							//'products_id' => $product['listing']->id().'_quoted',
							'quantity' => $product['quantity'],
							'name' => $product['listing']->get_header('products_name'),
							'name_attr' => htmlspecialchars($product['listing']->get_header('products_name')),
							'model_num' => $product['listing']->get_header('products_model'),
							'weight' => $product['listing']->get_total_weight(),
							'meta_condition' => $product['listing']->get_condition('meta'),
							'condition' => $product['listing']->get_condition(),
							'url' => $product['listing']->get_url(),
							'image' => $product['listing']->get_image('products_image'),
							'available' => $product['listing']->get_inventory('available'),
							'raw_price' => number_format($product['price'], 2),
							'price' => '$'.number_format($product['price'], 2),
							'total_price' => '$'.number_format($product['price']*$product['quantity'], 2),
							'discontinued' => $product['listing']->is('discontinued')?1:0
						];

						if ($product['listing']->is('discontinued')) $row['discontinued?'] = 1;

						$can_ship_today = TRUE;
						$qty_ship_today = $product['quantity'];
						$qty_back_order = 0;

						$product_total = 0;

						if ($product['listing']->is('freight')) $any_freight_items_in_cart = TRUE;

						if ($product['quantity'] > $row['available']) {
							$can_ship_today = FALSE;
							$any_out_of_stock_loc = 1;
							if ($row['available'] > 0) {
								$qty_ship_today = $row['available'];
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
							$row['included_options'] = [];
							foreach ($options as $option) {
								$row['included_options'][] = ['name' => $option['name']];
							}
						}

						$product_total += $product['price'] * $product['quantity'];

						$optional_addons = [];

						foreach ($qproducts as $optional_addon) {
							if ($optional_addon['option_type'] == ck_cart::$option_types['NONE']) continue; // this is not an addon at all
							if ($optional_addon['option_type'] == ck_cart::$option_types['INCLUDED']) continue; // this is an included addon

							if ($optional_addon['parent_products_id'] != $product['products_id']) continue; // this is an optional addon for a different parent

							// if we get here, this item is an optional addon on our current parent

							$option_row = [
								'customer_quote_product_id' => $optional_addon['customer_quote_product_id'],
								//'parent_cart_products_id' => $product['id'],
								//'cart_products_id' => $optional_addon['products_id'].'_'.$product['products_id'],
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
								'raw_price' => number_format($optional_addon['price'], 2),
								'price' => '$'.number_format($optional_addon['price'], 2),
								'total_price' => '$'.number_format($optional_addon['price']*$optional_addon['quantity'], 2),
								'discontinued' => $optional_addon['listing']->is('discontinued')?1:0
							];

							if ($optional_addon['listing']->is('discontinued')) $option_row['discontinued?'] = 1;

							$product_total += $optional_addon['price'] * $optional_addon['quantity'];

							$optional_addons[] = $option_row;
						}

						if (!empty($optional_addons)) $row['optional_addons'] = $optional_addons;

						$row['whole_total_price'] = CK\text::monetize($product_total);

						if (!empty($data['show_stock_status?'])) {

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
						}

						$qprodlist['products'][] = $row;
					}

					$data['quotes'][] = $qprodlist;
				}
			}

			if (!$any_products) $data['empty?'] = 1;

			$data['quote_total'] = CK\text::monetize($cart->get_quote_total());

			$data['cart_total'] = CK\text::monetize($cart->get_total());

			$_SESSION['any_out_of_stock'] = FALSE;
			if ($any_out_of_stock_loc == 1 && !$any_freight_items_in_cart && $something_can_ship_today) {
				$_SESSION['any_out_of_stock'] = TRUE;
				$data['out_of_stock_items?'] = 1;
				if (date('N') < 6 && date('G') > 9 && date('G') < 18) $data['immediate_assistance?'] = 1;
				if (!empty($_SESSION['split_order'])) $data['split_order?'] = 1;
			}

			// the cart itself is done, go ahead and get the display sent to the user
			$this->render($this->page_templates['shopping_cart'], $data);
			$this->flush();
		}

		if (CK\fn::check_flag(@$_SESSION['admin']) && !$cart->has_any_products() || $cart->has_products()) {
			// below here is shipping estimator
			$data['total_weight'] = $cart->get_weight()+shipit::$box_tare_weight;
			$data['total_count'] = $cart->get_units();

			$__FLAG = request_flags::instance();

			$weight = NULL;
			$box_width = NULL;
			$box_length = NULL;
			$box_height = NULL;
			$from_address = NULL;

			if (CK\fn::check_flag(@$_SESSION['admin'])) {
				if (!empty($_REQUEST['to_zip']) && !empty($_REQUEST['from_zip'])) {

					/*This is using the geo location google api and assigning those values to use in the view -- I'll leave this commented out for now, just incase it's ever beneficial to us
					$to_address_information = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$_REQUEST['to_zip'].'&sensor=true');
					$to_address_raw = json_decode($to_address_information);
					foreach ($to_address_raw->results[0]->address_components as $to) {
						if ($to->types[0] == 'postal_code') $to_address_data['postcode'] = $to->short_name;
						else if ($to->types[0] == 'locality') $to_address_data['city'] = $to->long_name;
						else if ($to->types[0] == 'administrative_area_level_1') $to_address_data['state'] = $to->short_name;
						else if ($to->types[0] == 'country') {
							$to_address_data['country'] = $to->long_name;
							$to_address_data['countries_iso_code_2'] = $to->short_name;
						}
					}

					$from_address_information = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$_REQUEST['from_zip'].'&sensor=true');
					$from_address_raw = json_decode($from_address_information);
					foreach ($from_address_raw->results[0]->address_components as $from) {
						if ($from->types[0] == 'postal_code') $data['shipment_information']['from_postcode'] = $from_address_data['postcode'] = $from->short_name;
						else if ($from->types[0] == 'locality') $data['shipment_information']['from_city'] = $from_address_data['city'] = $from->long_name;
						else if ($from->types[0] == 'administrative_area_level_1') $data['shipment_information']['from_state'] = $from_address_data['state'] = $from->short_name;
						else if ($from->types[0] == 'country') {
							$data['shipment_information']['from_country'] = $from_address_data['country'] = $from->long_name;
							$from_address_data['countries_iso_code_2'] = $from->short_name;
						}
					}*/

					$to_address_data = [
						'address1' => NULL,
						'address2' => NULL,
						'postcode' => $_REQUEST['to_zip'],
						'city' => 'Saturn2000',
						'state' => 'Saturn2000',
						'countries_id' => '223',
						'country' => 'United States',
						'countries_iso_code_2' => 'US',
						'countries_iso_code_3' => 'USA',
						'country_address_format_id' => '2'
					];

					$from_address_data = [
						'address1' => NULL,
						'address2' => NULL,
						'postcode' => $_REQUEST['from_zip'],
						'city' => 'Saturn2000',
						'state' => 'Saturn2000',
						'countries_id' => '223',
						'country' => NULL,
						'countries_iso_code_2' => 'US',
						'countries_iso_code_3' => 'USA',
						'country_address_format_id' => '2'
					];

					$data['shipment_information']['to_postcode'] = $to_address_data['postcode'];
					$data['shipment_information']['from_postcode'] = $from_address_data['postcode'];

					$from_address_type = new ck_address_type();
					$from_address_type->load('header', $from_address_data);
					$from_address = new ck_address2(NULL, $from_address_type);

					$to_address_type = new ck_address_type();
					$to_address_type->load('header', $to_address_data);
					$address = $to_address = new ck_address2(NULL, $to_address_type);

				}

				if (!empty($_REQUEST['override_weight'])) $data['total_weight'] = $weight = $_REQUEST['override_weight'];
				if (!empty($_REQUEST['box_length']) && !empty($_REQUEST['box_width']) && !empty($_REQUEST['box_height'])) {
					$data['shipment_information']['box_width'] = $box_width = $_REQUEST['box_width'];
					$data['shipment_information']['box_height'] = $box_height = $_REQUEST['box_height'];
					$data['shipment_information']['box_length'] = $box_length = $_REQUEST['box_length'];
				}

			}

			if ($__FLAG['show_options']) {
				$data['show_estimates?'] = 1;

				if ($cart->has_customer() && !empty($address)) { // just a final confirmation that one of the above selections was successful
					$data['formatted_address'] = $address->get_address_line_template(NULL, '<br>');

					if ($address->get_header('countries_iso_code_2') != 'US') $data['intl?'] = 1;
				}

				if (!empty($address->get_header('postcode'))) $data['rate_groups'] = $cart->get_ship_rate_quotes($address, $weight, $box_length, $box_height, $box_width, $from_address);
				else {
					$data['rate_groups'] = ['group_img?' => '<strong style="color:#f00;font-size:16px;">Please enter a zip code to get accurate shipping estimates</strong>'];
				}
			}

			// finish the page
			if (!empty($_GET)) unset($_GET);
			$this->render($this->page_templates['shipping_estimator'], $data);
			$this->flush();
		}
		$data = $this->data();


		$this->render($this->page_templates['legacy-footer'], $data);
		$this->flush();
	}
}
?>
