<?php
class ck_cart extends ck_singleton {
	use ck_legacy_api;

	protected static $skeleton_type = 'ck_cart_type';

	protected static $queries = [
		'cart_header' => [
			'qry' => 'SELECT cart_id, cart_key, customers_id, customers_extra_logins_id, customer_comments, admin_comments, date_created, date_updated FROM ck_carts WHERE cart_id = :cart_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'cart_header_by_key' => [
			'qry' => 'SELECT cart_id, cart_key, customers_id, customers_extra_logins_id, customer_comments, admin_comments, date_created, date_updated FROM ck_carts WHERE cart_key = :cart_key',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'shipments' => [
			'qry' => 'SELECT cart_shipment_id, shipping_address_book_id, residential, blind, order_po_number, reclaimed_materials, shipping_method_id, freight_needs_liftgate, freight_needs_inside_delivery, freight_needs_limited_access, shipment_account_choice, ups_account_number, fedex_account_number, date_created, date_updated FROM ck_cart_shipments WHERE cart_id = :cart_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'payments' => [
			'qry' => 'SELECT cart_payment_id, cart_shipment_id, billing_address_book_id, payment_method_id, pp_nonce, payment_card_id, payment_po_number, payment_coupon_id, payment_coupon_code, date_created, date_updated FROM ck_cart_payments WHERE cart_id = :cart_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'quotes' => [
			'qry' => 'SELECT cart_quote_id, cart_shipment_id, quote_id, date_created FROM ck_cart_quotes WHERE cart_id = :cart_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'products' => [
			'qry' => 'SELECT cart_product_id, cart_shipment_id, products_id, quantity, unit_price as display_price, price_options_snapshot, quoted_price, quoted_reason, option_type, parent_products_id, date_created, date_updated FROM ck_cart_products WHERE cart_id = :cart_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'products_for_undo_delete' => [
			'qry' => 'SELECT * FROM ck_cart_products WHERE cart_id = :cart_id AND (products_id = :products_id OR parent_products_id = :products_id)',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'addon_products_for_undo_delete' => [
			'qry' => 'SELECT * FROM ck_cart_products WHERE cart_id = :cart_id AND products_id = :products_id AND parent_products_id = :parent_products_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public static $option_types = [
		'NONE' => 0,
		'OPTIONAL' => 1,
		'RECOMMENDED' => 2,
		'INCLUDED' => 3,
		'UNKNOWN' => 4
	];

	public static $price_reasons_key = [
		'base_price' => 1,
		'original' => 1,
		'specials_price' => 2,
		'special' => 2,
		'customer_price' => 3,
		'customer' => 3,
		'dealer_price' => 4,
		'dealer' => 4,
		'addon_price' => 5,
		'option' => 5,
		'quote' => 6,
		'wholesale_high' => 7,
		'wholesale_low' => 8,
		'promo' => 9
	];

	// we need to interact with the session for our instance
	public static function instance($parameters=[]) {
		if (!empty(self::$instance[static::class])) return self::$instance[static::class];
		// we set it in the session even though we aren't going to use it from the session, since there are many places around the code that use it this way
		else return $_SESSION['cart'] = self::$instance[static::class] = new static($parameters);
	}

	protected function init($parameters=[]) {
		$this->skeleton = new self::$skeleton_type();
		$this->load_legacy_mappings();

		$this->rebuild_cart();
	}

	private function set_keys() {
		// there may or may not be a customer logged in, and that customer may or may not be using an "extra login" credential
		$this->skeleton->load('customers_id', !empty($_SESSION['customer_id'])?$_SESSION['customer_id']:NULL);
		$this->skeleton->load('customers_extra_logins_id', !empty($_SESSION['customer_extra_login_id'])?$_SESSION['customer_extra_login_id']:NULL);

		// this will set the ID as the session value, if one is found, otherwise if there is an existing cart for this user, pull it out
		if (empty($this->id()) && $header = self::fetch('cart_header_by_key', [':cart_key' => $this->get_cart_key()])) {
			$this->id($header['cart_id']);
		}

		//var_dump([$this->skeleton->get('customers_id'), $_SESSION['customer_id']]);
	}

	public function load_legacy_mappings() {
		$this->map_legacy_method('add_cart', 'update_product', function($args) { $args[0] = new ck_product_listing($args[0]); return $args; });
		$this->map_legacy_method('remove', 'update_product', function($args) { return [new ck_product_listing($args[0]), 0]; });
		$this->map_legacy_method('show_total', 'get_total');
		$this->map_legacy_method('show_weight', 'get_weight');
		$this->map_legacy_method('count_contents', 'get_units');
		$this->map_legacy_method('get_content_type', 'get_legacy_content_type');
		$this->map_legacy_method('get_quantity', 'get_total_product_quantity');
		$this->map_legacy_method('reset', 'reset_cart');
		$this->map_legacy_method('resetShoppingCart', 'reset_cart', function($args) { return [TRUE]; });
		$this->map_legacy_method('restore_contents', 'rebuild_cart');
		$this->map_legacy_method('eligible_for_free_shipping', 'is_freeship_eligible');
	}

	public function id($cart_id=NULL) {
		if (!empty($cart_id)) {
			$this->skeleton->rebuild('cart_id');
			$this->skeleton->load('cart_id', $cart_id);
			$_SESSION['cart_id'] = $cart_id;
		}
		elseif (!$this->skeleton->built('cart_id') && !empty($_SESSION['cart_id'])) {
			$this->skeleton->load('cart_id', $_SESSION['cart_id']);
		}

		if ($this->skeleton->built('cart_id')) return $this->skeleton->get('cart_id');
		return NULL;
	}

	public function debug() {
		foreach ($this->skeleton->get('products', 'consolidated') as $p) {
			//var_dump($p);
			$p['listing']->debug();
		}
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_header() {
		// if we've already got the cart ID, use it
		$header = self::fetch('cart_header', [':cart_id' => $this->id()]);

		$header['date_created'] = ck_datetime::datify(@$header['date_created']);
		if (!empty($header['date_updated'])) $header['date_updated'] = ck_datetime::datify($header['date_updated']);

		$this->skeleton->load('header', $header);
	}

	private function build_totals() {
		$totals = [];

		$customer = $this->get_customer();

		$shipment = $this->get_shipments('active');
		$payments = $this->get_payments($shipment['cart_shipment_id']);
		$payment = $payments[0];

		$subtotal = $this->get_total();

		$shipping_method_id = $shipment['shipping_method_id'];

		if (($customer->has('own_shipping_account') && !is_null($shipment['shipment_account_choice']) && $shipment['shipment_account_choice'] != 4) || $shipment['shipment_account_choice'] == 0) {
			$shipping_cost = 0;
			$shipping_cost_inclusive = 0;
		}
		else {
			$shipping_quote = $this->get_selected_ship_rate_quote();
			if (!empty($shipping_quote['rate_quotes'])) $shipping_cost = CK\text::demonetize($shipping_quote['rate_quotes'][0]['price']);
			elseif (!empty($shipping_quote['freight_quote'])) $shipping_cost = CK\text::demonetize($shipping_quote['freight_quote']['price']);
			$shipping_cost_inclusive = $shipping_cost;
		}

		$freight_costs = [
			'liftgate_cost' => empty($shipment['residential'])?70:0, // if it's residential, the cost is baked in
			'inside_cost' => 100,
			'limitaccess_cost' => 100
		];

		$shipping_ttl = [
			'title' => $shipping_method_id,
			'class' => 'ot_shipping',
			'sort_order' => 200,
			'external_id' => $shipping_method_id,

			'value' => $shipping_cost,
			'actual_shipping_cost' => $shipping_cost,
			'text' => CK\text::monetize($shipping_cost)
		];

		// manage freight
		if ($shipping_method_id == 50) {
			$this->skeleton->load('freight', TRUE);

			$shipping_ttl['title'] = 'Oversize/Best Fit Shipping:';

			if ($shipment['freight_needs_liftgate']) {
				$totals[] = [
					'title' => 'Liftgate:',
					'class' => 'ot_shipping',
					'sort_order' => 201,

					'value' => $freight_costs['liftgate_cost'],
					'actual_shipping_cost' => $freight_costs['liftgate_cost'],
					'text' => $freight_costs['liftgate_cost']>0?CK\text::monetize($freight_costs['liftgate_cost']):'Included'
				];
				$shipping_cost_inclusive += $freight_costs['liftgate_cost'];
			}

			if ($shipment['freight_needs_inside_delivery']) {
				$totals[] = [
					'title' => 'Inside Delivery:',
					'class' => 'ot_shipping',
					'sort_order' => 202,

					'value' => $freight_costs['inside_cost'],
					'actual_shipping_cost' => $freight_costs['inside_cost'],
					'text' => CK\text::monetize($freight_costs['inside_cost'])
				];
				$shipping_cost_inclusive += $freight_costs['inside_cost'];
			}

			if ($shipment['freight_needs_limited_access']) {
				$totals[] = [
					'title' => 'Limited Access:',
					'class' => 'ot_shipping',
					'sort_order' => 203,

					'value' => $freight_costs['limitaccess_cost'],
					'actual_shipping_cost' => $freight_costs['limitaccess_cost'],
					'text' => CK\text::monetize($freight_costs['limitaccess_cost'])
				];
				$shipping_cost_inclusive += $freight_costs['limitaccess_cost'];
			}
		}

		$totals[] = $shipping_ttl;

		// manage coupons
		if (!empty($payment['payment_coupon_id']) && $coupon = self::query_fetch('SELECT * FROM coupons WHERE coupon_id = :coupon_id', cardinality::ROW, [':coupon_id' => $payment['payment_coupon_id']])) {
			$coupon_amount = 0;

			// if this is a free shipping coupon, *and* we're using UPS or Fedex Ground, coupon amount is the shipping amount
			if ($coupon['coupon_type'] == 'S' && in_array($shipping_method_id, [9, 23])) $coupon_amount = $shipping_ttl['value'];

			// otherwise, if this is a straight $ discount, or a % discount, we'll run it
			elseif (in_array($coupon['coupon_type'], ['F', 'P'])) {
				$product_total = 0;

				if (!empty($coupon['restrict_to_products'])) {
					$products_ids = preg_split('/\s*,\s*/', $coupon['restrict_to_products']);

					if ($products = $this->get_products()) {
						foreach ($products as $product) {
							if (!in_array($product['products_id'], $products_ids)) continue;
							$product_total += $product['quantity'] * $product['unit_price'];
						}
					}

					if ($quotes = $this->get_quotes()) {
						foreach ($quotes as $quote) {
							if ($products = $quote['quote']->get_products()) {
								foreach ($products as $product) {
									if (!in_array($product['products_id'], $products_ids)) continue;
									$product_total += $product['quantity'] * $product['price'];
								}
							}
						}
					}
				}
				elseif (!empty($coupon['restrict_to_categories'])) {
					$categories_ids = preg_split('/\s*,\s*/', $coupon['restrict_to_categories']);

					if ($products = $this->get_products()) {
						foreach ($products as $product) {
							$product_match = FALSE;
							if ($categories = $product['listing']->get_categories()) {
								foreach ($categories as $category) {
									if (in_array($category->id(), $categories_ids)) {
										$product_match = TRUE;
										break;
									}
									elseif ($category->has_ancestors()) {
										foreach ($category->get_ancestors() as $ancestor) {
											if (in_array($ancestor->id(), $categories_ids)) {
												$product_match = TRUE;
												break;
											}
										}
									}
								}
							}
							if ($product_match) $product_total += $product['quantity'] * $product['unit_price'];
						}
					}

					if ($quotes = $this->get_quotes()) {
						foreach ($quotes as $quote) {
							if ($products = $quote['quote']->get_products()) {
								foreach ($products as $product) {
									$product_match = FALSE;
									if ($categories = $product['listing']->get_categories()) {
										foreach ($categories as $category) {
											if (in_array($category->id(), $categories_ids)) {
												$product_match = TRUE;
												break;
											}
											elseif ($category->has_ancestors()) {
												foreach ($category->get_ancestors() as $ancestor) {
													if (in_array($ancestor->id(), $categories_ids)) {
														$product_match = TRUE;
														break;
													}
												}
											}
										}
									}
									if ($product_match) $product_total += $product['quantity'] * $product['unit_price'];
								}
							}
						}
					}
				}
				else $product_total = $subtotal;

				if ($coupon['coupon_type'] == 'F') $coupon_amount = min($product_total, $coupon['coupon_amount']);
				elseif ($coupon['coupon_type'] == 'P') $coupon_amount = ($coupon['coupon_amount'] / 100) * $product_total;
			}

			if (abs($coupon_amount) > 0) {
				$totals[] = [
					'title' => 'Discount Coupons:'.$coupon['coupon_code'].':',
					'class' => 'ot_coupon',
					'sort_order' => 720,

					'value' => -1 * abs($coupon_amount),
					'actual_shipping_cost' => -1 * abs($coupon_amount),
					'text' => '<strong>-'.CK\text::monetize(abs($coupon_amount)).'</strong>'
				];
			}
		}

		// manage tax
		$shipping_address = $this->get_shipping_address()->get_header();
		// we'll redo avatax at some point, but for now it requires the legacy order class
		require_once(__DIR__.'/../functions/avatax.php');

		$order = (object) [
			'info' => [
				'shipping_method' => $shipping_method_id,
				'shipping_cost' => $shipping_cost_inclusive
			],
			'customer' => [
				'id' => $this->get_customer()->id(),
				'email_address' => $this->get_customer()->get_header('email_address')
			],
			'delivery' => [
				'street_address' => $shipping_address['address1'],
				'suburb' => $shipping_address['address2'],
				'city' => $shipping_address['city'],
				'zone_id' => $shipping_address['zone_id'],
				'country' => ['iso_code_2' => $shipping_address['countries_iso_code_2']],
				'postcode' => $shipping_address['postcode']
			],
			'products' => []
		];

		if ($this->has_products()) {
			foreach ($this->get_products() as $product) {
				$order->products[] = [
					'id' => $product['products_id'],
					'model' => $product['listing']->get_header('products_model'),
					'name' => $product['listing']->get_header('products_name'),
					'qty' => $product['quantity'],
					'final_price' => $product['unit_price']
				];
			}
		}

		if ($this->has_quotes()) {
			foreach ($this->get_quotes() as $quote) {
				foreach ($quote['quote']->get_products() as $product) {
					$order->products[] = [
						'id' => $product['products_id'],
						'model' => $product['listing']->get_header('products_model'),
						'name' => $product['listing']->get_header('products_name'),
						'qty' => $product['quantity'],
						'final_price' => $product['price']
					];
				}
			}
		}

		$tax = avatax_get_tax($order->products, $order->info['shipping_cost']);
		// die(var_dump($tax));
		if ($tax > 0) {
			$totals[] = [
				'title' => 'Tax:',
				'class' => 'ot_tax',
				'sort_order' => 780,

				'value' => $tax,
				'actual_shipping_cost' => $tax,
				'text' => CK\text::monetize($tax)
			];
		}

		// manage overall total
		$total = array_reduce($totals, function($running_total, $ttl) {
			$running_total += $ttl['value'];
			return $running_total;
		}, $subtotal);

		$totals[] = [
			'title' => 'Total:',
			'class' => 'ot_total',
			'sort_order' => 800,

			'value' => $total,
			'actual_shipping_cost' => $total,
			'text' => '<strong>'.CK\text::monetize($total).'</strong>'
		];

		// sort by sort order
		usort($totals, function($a, $b) {
			if ($a['sort_order'] < $b['sort_order']) return -1;
			elseif ($a['sort_order'] > $b['sort_order']) return 1;
			else return 0;
		});

		$this->skeleton->load('totals', $totals);
	}

	private function build_customer() {
		if (!empty($this->skeleton->get('customers_id'))) $this->skeleton->load('customer', new ck_customer2($this->skeleton->get('customers_id')));
		else $this->skeleton->load('customer', NULL);
	}

	private function build_shipments() {
		if (!$this->skeleton->built('header')) $this->build_header();
		$shipments = self::fetch('shipments', [':cart_id' => $this->id()]);

		foreach ($shipments as &$shipment) {
			$shipment['date_created'] = self::DateTime($shipment['date_created']);
			if (!empty($shipment['date_updated'])) $shipment['date_updated'] = self::DateTime($shipment['date_updated']);

			$bool_fields = ['residential', 'blind', 'reclaimed_materials', 'freight_needs_liftgate', 'freight_needs_inside_delivery', 'freight_needs_limited_access'];
			foreach ($bool_fields as $field) {
				$shipment[$field] = CK\fn::check_flag($shipment[$field]);
			}
		}

		$this->skeleton->load('shipments', $shipments);
	}

	private function build_shipping_address() {
		$ship_address = NULL;

		// if the customer is not logged in, don't even try
		if ($this->is_logged_in()) {
			$customer = $this->get_customer(); // deref the customer

			// if we've got a selected shipping address, use it
			if ($this->has_shipments()) {
				$shipment = $this->get_shipments('active');
				if (!empty($shipment['shipping_address_book_id'])) $ship_address = $customer->get_addresses($shipment['shipping_address_book_id']);
			}

			// if we *haven't* selected an address (or the selected address doesn't belong to this customer), attempt to use one from the customer
			if (empty($ship_address) && $customer->has_addresses()) {
				// if the customer has a default address; if they don't, this will set the first one as default
				$ship_address = $customer->get_default_address();
			}
		}

		$this->skeleton->load('shipping_address', $ship_address);
	}

	private function build_billing_address() {
		$bill_address = NULL;

		// if the customer is not logged in, don't even try
		if ($this->is_logged_in()) {
			$customer = $this->get_customer(); // deref the customer

			// if we've got a selected billing address on the active shipment, use it
			if ($this->has_payments($this->select_cart_shipment())) {
				$payments = $this->get_payments($this->select_cart_shipment());
				foreach ($payments as $payment) {
					if (!empty($payment['billing_address_book_id'])) {
						$bill_address = $customer->get_addresses($payment['billing_address_book_id']);
						break;
					}
				}
			}

			// if we *haven't* selected an address (or the selected address doesn't belong to this customer), attempt to use one from the customer
			if (empty($bill_address) && !empty($customer->has_addresses())) {
				// if the customer has a default address; if they don't, this will set the first one as default
				$bill_address = $customer->get_default_address();
			}
		}

		$this->skeleton->load('billing_address', $bill_address);
	}

	private function build_payments() {
		if (!$this->skeleton->built('header')) $this->build_header();
		$payments_raw = self::fetch('payments', [':cart_id' => $this->id()]);

		$payments = ['consolidated' => []];

		foreach ($payments_raw as $payment) {
			$payment['date_created'] = self::DateTime($payment['date_created']);
			if (!empty($payment['date_updated'])) $payment['date_updated'] = self::DateTime($payment['date_updated']);

			if (empty($payments[$payment['cart_shipment_id']])) $payments[$payment['cart_shipment_id']] = [];
			$payments[$payment['cart_shipment_id']][] =& $payment;
			$payments['consolidated'][] =& $payment;
		}

		$this->skeleton->load('payments', $payments);
	}

	private function build_quotes() {
		if (!$this->skeleton->built('header')) $this->build_header();
		$quotes_raw = self::fetch('quotes', [':cart_id' => $this->id()]);

		$quotes = ['consolidated' => []];

		foreach ($quotes_raw as $quote) {
			$quote['date_created'] = self::DateTime($quote['date_created']);

			$quote['quote'] = new ck_quote($quote['quote_id']);

			if (empty($quotes[$quote['cart_shipment_id']])) $quotes[$quote['cart_shipment_id']] = [];
			$quotes[$quote['cart_shipment_id']][] = $quote;
			$quotes['consolidated'][] = $quote;
		}

		$this->skeleton->load('quotes', $quotes);
	}

	private function build_products() {
		if (!$this->skeleton->built('header')) $this->build_header();
		$products_raw = self::fetch('products', [':cart_id' => $this->id()]);

		$products = ['consolidated' => []];

		foreach ($products_raw as $product) {
			$product['listing'] = new ck_product_listing($product['products_id']);

			if ($product['quoted_price'] > 0) {
				$product['unit_price'] = $product['quoted_price'];
			}
			else {
				// we always want up-to-date pricing
				if (in_array($product['option_type'], [self::$option_types['NONE'], self::$option_types['UNKNOWN']])) $product['unit_price'] = $product['listing']->get_price('display');
				elseif (in_array($product['option_type'], [self::$option_types['OPTIONAL'], self::$option_types['RECOMMENDED']])) {
					if ($parents = $product['listing']->get_parent_listings('extra')) {
						foreach ($parents as $parent) {
							if ($parent['products_id'] != $product['parent_products_id']) continue;
							$product['unit_price'] = $parent['addon_price'];
						}
					}
					else $product['unit_price'] = $product['listing']->get_price('display');
				}
				elseif ($product['option_type'] == self::$option_types['INCLUDED']) $product['unit_price'] = 0;
			}

			$product['date_created'] = self::DateTime($product['date_created']);
			if (!empty($product['date_updated'])) $product['date_updated'] = self::DateTime($product['date_updated']);

			if (empty($products[$product['cart_shipment_id']])) $products[$product['cart_shipment_id']] = [];
			$products[$product['cart_shipment_id']][] = $product;
			$products['consolidated'][] = $product;
		}

		$this->skeleton->load('products', $products);
	}

	private function build_universal_products() {
		$universal_products = [];

		if ($products = $this->get_products()) {
			foreach ($products as $product) {
				if (empty($universal_products[$product['products_id']])) $universal_products[$product['products_id']] = ['products_id' => $product['products_id'], 'listing' => $product['listing'], 'quantity' => 0, 'option_type' => self::$option_types['NONE']];
				$universal_products[$product['products_id']]['quantity'] += $product['quantity'];
				$universal_products[$product['products_id']]['option_type'] = min($universal_products[$product['products_id']]['option_type'], $product['option_type']);
			}
		}

		if ($quotes = $this->get_quotes()) {
			foreach ($quotes as $quote) {
				if ($products = $quote['quote']->get_products()) {
					foreach ($products as $product) {
						if (empty($universal_products[$product['products_id']])) $universal_products[$product['products_id']] = ['products_id' => $product['products_id'], 'listing' => $product['listing'], 'quantity' => 0, 'option_type' => self::$option_types['NONE']];
						$universal_products[$product['products_id']]['quantity'] += $product['quantity'];
						$universal_products[$product['products_id']]['option_type'] = min($universal_products[$product['products_id']]['option_type'], $product['option_type']);
					}
				}
			}
		}

		$this->skeleton->load('universal_products', $universal_products);
	}

	private function build_selected_ship_rate_quote() {
		$shipment = $this->get_shipments('active');
		$carrier = self::query_fetch('SELECT carrier FROM shipping_methods WHERE shipping_code = :shipping_code', cardinality::SINGLE, [':shipping_code' => $shipment['shipping_method_id']]);

		if ($this->has_cached_rate_quote($this->get_shipping_address(), $shipment['shipping_method_id'])) $quote = [['rate_quotes' => [$this->get_cached_rate_quote($this->get_shipping_address(), $shipment['shipping_method_id'])]]];
		else $quote = $this->get_ship_rate_quotes($this->get_shipping_address(), NULL, NULL, NULL, NULL, NULL, $shipment['shipping_method_id'], strtolower($carrier));

		$this->skeleton->load('selected_ship_rate_quote', $quote[0]);
	}

	/*private function build_products() {
		$cart = $this->get_legacy_cart();

		$products = [];

		$legacy_products = $cart->get_products();
		foreach ($legacy_products as $product) {
			if (ck_product_listing::is_addon($product['id'])) {
				$products_id = ck_product_listing::get_child_from_addon_id($product['id']);
				$parent_products_id = ck_product_listing::get_parent_from_addon_id($product['id']);
				// we can instantiate this just to throw it away, the class uses a static registry to not re-run queries
				$parent = new ck_product_listing($parent_products_id);
				$options = $parent->get_options();
				$option_type = NULL;
				if (!empty($options['extra'])) {
					foreach ($options['extra'] as $option) {
						if ($option['products_id'] == $products_id) $option_type = !empty($option['recommended?'])?1:0; // recommended:not recommended
					}
				}
				if (!empty($options['included'])) {
					foreach ($options['included'] as $option) {
						if ($option['products_id'] == $products_id) $option_type = 2; // included
					}
				}
				// if the parent is a bundle, and the child isn't a direct option, then we know it's included from one of the child products
				if ($parent->is('is_bundle') && is_null($option_type)) $option_type = 2; // included
				// if we haven't otherwise found the relationship, it's not recommended
				elseif (is_null($option_type)) $option_type = 0; // not recommended

				if ($option_type < 2 && $option['allow_mult_opts'] > 0) $option_qty_tied_to_parent = TRUE;
				elseif ($option_type < 2 && $option['allow_mult_opts'] > 0) $option_qty_tied_to_parent = FALSE;
				else $option_qty_tied_to_parent = NULL;
			}
			else {
				$products_id = $product['id'];
				$parent_products_id = NULL;
				$option_type = NULL;
				$option_qty_tied_to_parent = NULL;
			}
			$listing = new ck_product_listing($products_id);
			$products[$product['id']] = [ // we key it on the legacy cart product id, for now
				'products_id' => $products_id,
				'listing' => $listing,
				'quantity' => $product['quantity'],
				'price' => $product['final_price'],
				'parent_products_id' => $parent_products_id,
				'option_type' => $option_type,
				'legacy_products_id' => $product['id'],
				'option_qty_tied_to_parent' => $option_qty_tied_to_parent,
				'legacy_product' => $product
			];
		}

		$this->skeleton->load('products', $products);
	}*/

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_cart_key() {
		// init cart key as the session key
		$cart_key = $this->id();

		// if we've got a customer, create our key out of the customer/extra login
		if ($this->skeleton->has('customers_id')) {
			$cart_key = $this->skeleton->get('customers_id');
			if ($this->skeleton->has('customers_extra_logins_id')) $cart_key .= '-'.$this->skeleton->get('customers_extra_logins_id');
		}

		return $cart_key;
	}

	public function is_logged_in() {
		return $this->has_customer();
	}

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function get_email_address() {
		if ($this->skeleton->has('customers_extra_logins_id')) {
			$cel = $this->get_customer()->get_extra_logins($this->skeleton->get('customers_extra_logins_id'));
			return $cel['email_address'];
		}
		else return $this->get_customer()->get_header('email_address');
	}

	public function is_freight() {
		if (!$this->skeleton->built('totals')) $this->build_totals();
		return !empty($this->skeleton->get('freight'));
	}

	public function get_totals($key=NULL) {
		if (!$this->skeleton->built('totals')) $this->build_totals();
		if (empty($key)) return $this->skeleton->get('totals');
		else {
			foreach ($this->skeleton->get('totals') as $total) {
				if (in_array($total['class'], [$key, 'ot_'.$key])) return $total;
			}
			return NULL;
		}
	}

	public function get_simple_totals($key=NULL) {
		$totals = $this->get_totals();

		$totals = array_reduce($totals, function($simple, $ttl) use ($key) {
			$class = preg_replace('/ot_/', '', $ttl['class']);
			if (!empty($key) && $class != $key) return $simple;
			if (empty($simple[$class])) $simple[$class] = 0;
			$simple[$class] += $ttl['value'];
			return $simple;
		}, []);

		if (!empty($key)) return $totals[$key];
		else return $totals;
	}

	public function has_customer() {
		if (!$this->skeleton->built('customer')) $this->build_customer();
		return $this->skeleton->has('customer');
	}

	public function get_customer() {
		if (!$this->has_customer()) return NULL;
		return $this->skeleton->get('customer');
	}

	public function get_contact_phone($separator='.') {
		$default_number = ['888', '622', '0223'];
		if (!$this->has_customer() || !$this->get_customer()->has_sales_team() || !$this->get_customer()->get_sales_team()->has('phone_number')) return implode($separator, $default_number);
		else {
			$team_number = array_filter(preg_split('/[^0-9]+/', $this->get_customer()->get_sales_team()->get_header('phone_number')));
			return implode($separator, $team_number);
		}
	}

	public function get_contact_local_phone($separator='.') {
		$default_number = ['678', '597', '5000'];
		if (!$this->has_customer() || !$this->get_customer()->has_sales_team() || !$this->get_customer()->get_sales_team()->has('local_phone_number')) return implode($separator, $default_number);
		else {
			$team_number = array_filter(preg_split('/[^0-9]+/', $this->get_customer()->get_sales_team()->get_header('local_phone_number')));
			return implode($separator, $team_number);
		}
	}

	public function get_contact_email() {
		$default_email = 'sales@cablesandkits.com';
		if (!$this->has_customer() || !$this->get_customer()->has_sales_team() || !$this->get_customer()->get_sales_team()->has('email_address')) return $default_email;
		else {
			$team_email = $this->get_customer()->get_sales_team()->get_header('email_address');
			return $team_email;
		}
	}

	public function has_shipments() {
		if (!$this->skeleton->built('shipments')) $this->build_shipments();
		return $this->skeleton->has('shipments');
	}

	public function get_shipments($key=NULL) {
		if (!$this->has_shipments()) return NULL;
		if ($key != 'active') return $this->skeleton->get('shipments');
		else {
			foreach ($this->skeleton->get('shipments') as $shipment) {
				if ($shipment['cart_shipment_id'] == $this->select_cart_shipment()) return $shipment;
			}
		}
	}

	public function get_ups_account_number($customers_number=TRUE) {
		$customer = $this->get_customer();

		$shipment = $this->get_shipments('active');
		$shipping_method = self::query_fetch('SELECT * FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::ROW, [':shipping_method_id' => $shipment['shipping_method_id']]);

		$ck_account_number = $shipping_method['carrier']=='UPS'?'724TT9':'';

		if (($customer->has('own_shipping_account') && ($customers_number || $shipping_method['carrier'] == 'UPS')) || $shipment['shipment_account_choice'] == 0) {
			if (is_numeric($shipment['shipment_account_choice'])) {
				switch ($shipment['shipment_account_choice']) {
					case 4:
						$account_number = $customers_number?'':$ck_account_number;
						break;
					case 2:
						$account_number = $customer->get_header('ups_account_number');
						break;
					case 0:
						$account_number = $shipment['ups_account_number'];
						break;
				}
			}
			else $account_number = $ck_account_number;
		}
		else $account_number = $customers_number?'N/A':$ck_account_number;

		if ($customers_number) $account_number = preg_replace('/-/', '', $account_number);

		return $account_number;
	}

	public function get_fedex_account_number($customers_number=TRUE) {
		$customer = $this->get_customer();

		$shipment = $this->get_shipments('active');
		$shipping_method = self::query_fetch('SELECT * FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::ROW, [':shipping_method_id' => $shipment['shipping_method_id']]);

		$ck_account_number = $shipping_method['carrier']=='FedEx'?'285019516':'';

		if (($customer->has('own_shipping_account') && ($customers_number || $shipping_method['carrier'] == 'FedEx')) || $shipment['shipment_account_choice'] == 0) {
			if (is_numeric($shipment['shipment_account_choice'])) {
				switch ($shipment['shipment_account_choice']) {
					case 4:
						$account_number = $customers_number?'':$ck_account_number;
						break;
					case 2:
						$account_number = $customer->get_header('fedex_account_number');
						break;
					case 0:
						$account_number = $shipment['fedex_account_number'];
						break;
				}
			}
			else $account_number = $ck_account_number;
		}
		else $account_number = $customers_number?'N/A':$ck_account_number;

		if ($customers_number) $account_number = preg_replace('/-/', '', $account_number);

		return $account_number;
	}

	public function get_shipping_bill_type() {
		$bill_type = 1;
		$shipment = $this->get_shipments('active');
		if ($this->get_customer()->has('own_shipping_account') || $shipment['shipment_account_choice'] == 0) {
			$shipping_method = self::query_fetch('SELECT * FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::ROW, [':shipping_method_id' => $shipment['shipping_method_id']]);

			if (in_array($shipment['shipping_method_id'], [23, 29, 9, 15])) $bill_type = 5;
			elseif (in_array($shipping_method['carrier'], ['UPS', 'FedEx'])) $bill_type = 2;
		}

		return $bill_type;
	}

	public function has_shipping_address() {
		if (!$this->skeleton->built('shipping_address')) $this->build_shipping_address();
		return $this->skeleton->has('shipping_address');
	}

	public function get_shipping_address() {
		if (!$this->has_shipping_address()) return NULL;
		return $this->skeleton->get('shipping_address');
	}

	public function has_billing_address() {
		if (!$this->skeleton->built('billing_address')) $this->build_billing_address();
		return $this->skeleton->has('billing_address');
	}

	public function get_billing_address() {
		if (!$this->has_billing_address()) return NULL;
		return $this->skeleton->get('billing_address');
	}

	public function has_payments($cart_shipment_id='consolidated') {
		if (!$this->skeleton->built('payments')) $this->build_payments();
		if (empty($cart_shipment_id)) return $this->skeleton->has('payments');
		else return !empty($this->skeleton->get('payments', $cart_shipment_id));
	}

	public function get_payments($cart_shipment_id='consolidated') {
		if (!$this->has_payments()) return NULL;
		if (empty($cart_shipment_id)) return $this->skeleton->get('payments');
		else return $this->skeleton->get('payments', $cart_shipment_id);
	}

	public function has_quotes($cart_shipment_id='consolidated') {
		if (!$this->skeleton->built('quotes')) $this->build_quotes();
		if (empty($cart_shipment_id)) return $this->skeleton->has('quotes');
		else return !empty($this->skeleton->get('quotes', $cart_shipment_id));
	}

	public function get_quotes($cart_shipment_id='consolidated') {
		if (!$this->has_quotes($cart_shipment_id)) return NULL;
		if (empty($cart_shipment_id)) return $this->skeleton->get('quotes');
		else return $this->skeleton->get('quotes', $cart_shipment_id);
	}

	public function has_products($cart_shipment_id='consolidated') {
		if (!$this->skeleton->built('products')) $this->build_products();
		if (empty($cart_shipment_id)) return $this->skeleton->has('products');
		else return !empty($this->skeleton->get('products', $cart_shipment_id));
	}

	public function get_products($cart_shipment_id='consolidated', $products_id=NULL, $parent_products_id=NULL) {
		if (!$this->has_products($cart_shipment_id)) return NULL;
		$products = $this->skeleton->get('products');
		if (empty($cart_shipment_id)) return $products;
		elseif (empty($products[$cart_shipment_id])) return NULL;
		elseif (empty($products_id)) return $products[$cart_shipment_id];
		else {
			foreach ($products[$cart_shipment_id] as $product) {
				if ($product['products_id'] != $products_id) continue;
				if ($product['parent_products_id'] != $parent_products_id) continue;
				return $product;
			}
		}
	}

	public function has_any_products() {
		if (!$this->skeleton->built('universal_products')) $this->build_universal_products();
		return $this->skeleton->has('universal_products');
	}

	public function get_universal_products($key=NULL) {
		if (!$this->has_any_products()) return [];
		if (empty($key)) return $this->skeleton->get('universal_products');
		else return $this->skeleton->get('universal_products', $key);
	}

	public function get_total($products_id=NULL, $quote_id=NULL) {
		$total = 0;

		$total += $this->get_product_total($products_id);
		$total += $this->get_quote_total($quote_id, $products_id);

		return $total;
	}

	public function get_product_total($products_id=NULL) {
		$total = 0;
		if ($products = $this->get_products()) {
			foreach ($products as $product) {
				if (!empty($products_id) && !in_array($products_id, [$product['products_id'], $product['parent_products_id']])) continue;
				$total += $product['quantity'] * $product['unit_price'];
			}
		}
		return $total;
	}

	public function get_quote_total($quote_id=NULL, $products_id=NULL) {
		$total = 0;
		if ($quotes = $this->get_quotes()) {
			foreach ($quotes as $quote) {
				if (!empty($quote_id) && $quote_id != $quote['quote']->id()) continue;
				if ($products = $quote['quote']->get_products()) {
					foreach ($products as $product) {
						if (!empty($products_id) && !in_array($products_id, [$product['products_id'], $product['parent_products_id']])) continue;
						$total += $product['quantity'] * $product['price'];
					}
				}
			}
		}
		return $total;
	}

	public function get_weight() {
		$weight = 0;

		if ($products = $this->get_products()) {
			foreach ($products as $product) {
				if ($product['option_type'] == self::$option_types['INCLUDED']) continue;
				$weight += $product['quantity'] * $product['listing']->get_total_weight();
			}
		}

		if ($quotes = $this->get_quotes()) {
			foreach ($quotes as $quote) {
				if ($products = $quote['quote']->get_products()) {
					foreach ($products as $product) {
						if ($product['option_type'] == self::$option_types['INCLUDED']) continue;
						$weight += $product['quantity'] * $product['listing']->get_total_weight();
					}
				}
			}
		}

		return $weight;
	}

	public function get_estimated_shipped_weight() {
		$shipped_weight = $this->get_weight();

		// we add the greater between the default tare weight or the tare factor applied to the product weight
		$shipped_weight += max(shipit::$box_tare_weight, $shipped_weight * shipit::$box_tare_factor);

		return round($shipped_weight, 1);
	}

	public function get_units() {
		$units = 0;

		if ($products = $this->get_products()) {
			foreach ($products as $product) {
				if ($product['option_type'] == self::$option_types['INCLUDED']) continue;
				$units += $product['quantity'];
			}
		}

		if ($quotes = $this->get_quotes()) {
			foreach ($quotes as $quote) {
				if ($products = $quote['quote']->get_products()) {
					foreach ($products as $product) {
						if ($product['option_type'] == self::$option_types['INCLUDED']) continue;
						// this is new for us, it never used to count quoted products
						$units += $product['quantity'];
					}
				}
			}
		}

		return $units;
	}

	public function get_total_product_quantity($products_id=NULL) {
		$quantities = [];

		if ($products = $this->get_products()) {
			foreach ($products as $product) {
				if (!empty($products_id) && $product['products_id'] != $products_id) continue;
				if (empty($quantities[$product['products_id']])) $quantities[$product['products_id']] = 0;
				$quantities[$product['products_id']] += $product['quantity'];
			}
		}

		if ($quotes = $this->get_quotes()) {
			foreach ($quotes as $quote) {
				if ($products = $quote['quote']->get_products()) {
					foreach ($products as $product) {
						if (!empty($products_id) && $product['products_id'] != $products_id) continue;
						if (empty($quantities[$product['products_id']])) $quantities[$product['products_id']] = 0;
						$quantities[$product['products_id']] += $product['quantity'];
					}
				}
			}
		}

		if (!empty($products_id)) return $quantities[$products_id];
		else return $quantities;
	}

	public function has_cached_rate_quote(ck_address2 $address, $shipping_method_id) {
		$status = TRUE;

		$timeout = new DateInterval('PT2H'); // we cache for 2 hours

		if (empty($_SESSION['checkout_shipping_quote_cache'])) $status = FALSE;
		else {
			$_SESSION['checkout_shipping_quote_cache']['cache_time']->add($timeout);

			if (empty($_SESSION['checkout_shipping_quote_cache']['quotes'][$shipping_method_id])) $status = FALSE;
			elseif ($this->get_weight() != $_SESSION['checkout_shipping_quote_cache']['weight']) $status = FALSE;
			elseif ($address->get_unique_id() != $_SESSION['checkout_shipping_quote_cache']['shipping_address']) $status = FALSE;
			elseif (self::NOW() >= $_SESSION['checkout_shipping_quote_cache']['cache_time']) $status = FALSE;
		}

		if (!$status) unset($_SESSION['checkout_shipping_quote_cache']);

		return $status;
	}

	public function get_cached_rate_quote(ck_address2 $address, $shipping_method_id) {
		if (!$this->has_cached_rate_quote($address, $shipping_method_id)) return NULL; //$this->get_ship_rate_quotes($address, NULL, NULL, NULL, NULL, NULL, $shipping_method_id);
		else return $_SESSION['checkout_shipping_quote_cache']['quotes'][$shipping_method_id];
	}

	public function get_ship_rate_quotes(ck_address2 $address, $weight=NULL, $box_length=NULL, $box_height=NULL, $box_width=NULL, ck_address2 $from_address=NULL, $shipping_method_id=NULL, $carrier='ups') {
		$header = $address->get_header();
		$GLOBALS['order'] = (object) [
			'delivery' => [
				'postcode' => $header['postcode'],
				'state' => $header['state'],
				'city' => $header['city'],
				'street_address' => $header['address1'],
				'suburb' => $header['address2'],
				'country' => [
					'id' => $header['countries_id'],
					'title' => $header['country'],
					'iso_code_2' => $header['countries_iso_code_2'],
					'iso_code_3' => $header['countries_iso_code_3']
				],
				'country_id' => $header['countries_id'],
				'format_id' => $header['country_address_format_id']
			]
		]; // needed to interact with the oldschool shipping modules

		if (!empty($from_address)) {
			$from_header = $from_address->get_header();
			$GLOBALS['origin'] = (object) [
				'delivery' => [
					'postcode' => $from_header['postcode'],
					'state' => $from_header['state'],
					'city' => $from_header['city'],
					'country' => [
						'id' => $from_header['countries_id'],
						'title' => $from_header['country'],
						'iso_code_2' => $from_header['countries_iso_code_2'],
						'iso_code_3' => $from_header['countries_iso_code_3']
					],
					'country_id' => $from_header['countries_id'],
					'format_id' => $from_header['country_address_format_id']
				]
			];
		}

		if (!empty($box_length) && !empty($box_height) && !empty($box_width)) {
			$GLOBALS['box_length'] = $box_length;
			$GLOBALS['box_height'] = $box_height;
			$GLOBALS['box_width'] = $box_width;
		}

		if ($this->has_customer()) {
			$GLOBALS['order']->delivery['street_address'] = $header['address1'];
			$GLOBALS['order']->delivery['suburb'] = $header['address2'];
			$GLOBALS['order']->delivery['city'] = $header['city'];
			$GLOBALS['order']->delivery['telephone'] = $header['telephone'];
			$GLOBALS['order']->delivery['state'] = $address->get_state();
			$GLOBALS['order']->delivery['zone_id'] = $header['zone_id'];
		}

		if (!empty($weight)) $GLOBALS['total_weight'] = $weight;
		else $GLOBALS['total_weight'] = $this->get_weight();

		require_once(DIR_WS_CLASSES . 'shipping.php');
		$shipping_modules = new shipping;
		//$GLOBALS['fedexnonsaturday']->enabled = FALSE;
		//$GLOBALS['fedexwebservices']->enabled = FALSE;
		if ($carrier == 'ups') {
			$GLOBALS['iux']->enabled = TRUE;
			$GLOBALS['fedexnonsaturday']->enabled = FALSE;
			//$GLOBALS['fedexwebservices']->enabled = FALSE;
		}
		elseif ($carrier == 'fedex') {
			$GLOBALS['fedexnonsaturday']->enabled = TRUE;
			//$GLOBALS['fedexwebservices']->enabled = TRUE;
			$GLOBALS['iux']->enabled = FALSE;
		}

		if (!empty($shipping_method_id)) $quote_list = $shipping_modules->modified_quote($shipping_method_id);
		else $quote_list = $shipping_modules->quote();
		$rate_groups = [];

		foreach ($quote_list as $module_idx => $module_raw) {
			$rate_group = ['module' => $module_raw['module'], 'carrier_id' => $module_raw['id']];
			if (!empty($module_raw['icon'])) {
				$rate_group['group_img?'] = $module_raw['icon'];
			}

			if (!empty($module_raw['error'])) {
				$rate_group['error_raw'] = $module_raw['error'];
				if (empty($rate_group['rate_quotes'])) $rate_group['rate_quotes'] = [];
				$rate_group['rate_quotes'][] = ['name' => $module_raw['module'], 'error?' => '('.$module_raw['error'].')'];
			}
			else {
				if (empty($module_raw['methods'])) continue;
				foreach ($module_raw['methods'] as $method_idx => $method_raw) {
					$quote = [];
					$quote['shipping_method_id'] = $method_raw['shipping_method_id'];
					$quote['price'] = CK\text::monetize($method_raw['cost']);
					$quote['price_raw'] = $method_raw['cost'];
					$quote['title_raw'] = $method_raw['title'];
					$quote['negotiated_rate'] = !empty($method_raw['negotiated_rate'])?CK\text::monetize($method_raw['negotiated_rate']):NULL;

					if ($method_raw['shipping_method_id'] == 50) {
						// freight quote
						$quote['name'] = 'Oversize/Best Fit Shipping';

						if (!empty($module_raw['residential'])) {
							if (empty($module_raw['verified_address'])) {
								$quote['possible_residential?'] = 'The cost shown is for residential delivery, if you are shipping to a business location, please log in to verify your address.';
							}
							else {
								$quote['possible_residential?'] = 'Your address is a residential address. A residential delivery surcharge is included in your shipping cost.';
							}

							$quote['quote_residential?'] = 1;
						}
						elseif (empty($module_raw['confirmed'])) {
							$quote['possible_residential?'] = 'We cannot confirm that your location is a business. If the carrier determines that your address is a residential address, then an additional residential delivery surcharge will be included.';
						}

						$rate_group['freight_quote'] = $quote;
					}
					else {
						if (empty($rate_group['rate_quotes'])) $rate_group['rate_quotes'] = [];

						require_once(__DIR__.'/../classes/shipping_methods.php');
						$shipmethod = new shipping_methods;
						$quote['name'] = $shipmethod->sm_short_description($method_raw['shipping_method_id']);

						if (!empty($_SESSION['sm_cmnt'][$method_raw['shipping_method_id']])) $quote['estimated_delivery'] = $_SESSION['sm_cmmt'][$method_raw['shipping_method_id']];
						else $quote['estimated_delivery'] = $shipmethod->sm_description($method_raw['shipping_method_id']);

						$rate_group['rate_quotes'][] = $quote;
					}
				}
			}
			$rate_groups[] = $rate_group;
		}

		$this->cache_rate_quotes($address, $rate_groups);

		return $rate_groups;
	}

	public function get_selected_ship_rate_quote() {
		if (!$this->skeleton->built('selected_ship_rate_quote')) $this->build_selected_ship_rate_quote();
		return $this->skeleton->get('selected_ship_rate_quote');
	}

	public function has_freight_products() {
		$freighted = NULL;

		if ($products = $this->get_products()) {
			$freighted = FALSE;
			foreach ($products as $product) {
				if ($product['listing']->is('freight')) {
					$freighted = TRUE;
					break;
				}
			}
		}

		if (!$freighted && $quotes = $this->get_quotes()) {
			foreach ($quotes as $quote) {
				if ($products = $quote['quote']->get_products()) {
					$freighted = FALSE;
					foreach ($products as $product) {
						if ($product['listing']->is('freight')) {
							$freighted = TRUE;
							break;
						}
					}
				}
			}
		}

		return $freighted;
	}

	public function is_freeship_eligible(ck_address2 $address=NULL, $sufficient_check=TRUE) {
		$eligible = TRUE;

		if ($eligible && $this->has_customer()) {
			$cust = $this->get_customer();
			if ($cust->is('disable_standard_shipping')) $eligible = FALSE;
		}

		// null means there's no products to check, true means there's at least one freight item, false means we have products and none are freight
		if ($eligible) $eligible = ($this->has_freight_products() == FALSE);

		if ($eligible && $sufficient_check) $eligible = ($this->get_total() >= $GLOBALS['ck_keys']->product['freeship_threshold']);

		if (empty($address) && $this->has_shipping_address()) $address = $this->get_shipping_address();

		if ($eligible && !empty($address) && (int) MODULE_FREE_SHIPPING_25_ZONE > 0) {
			$check_flag = FALSE;
			$countries_id = $address->get_header('countries_id');
			$checks = self::query_fetch('SELECT zone_id FROM zones_to_geo_zones WHERE geo_zone_id = :free_shipping_zone AND zone_country_id = :delivery_zone ORDER BY zone_id', cardinality::COLUMN, [':free_shipping_zone' => MODULE_FREE_SHIPPING_25_ZONE, ':delivery_zone' => $countries_id]);

			foreach ($checks as $check) {
				if ($check < 1) $check_flag = TRUE;
				elseif ($check == $address->get_header('zone_id')) $check_flag = TRUE;
				elseif (@$_REQUEST['country_id'] == '223') $check_flag = TRUE;
				if ($check_flag) break;
			}

			$eligible = $check_flag;
		}

		return $eligible;
	}

	public function get_split_requested() {
		$split_order = 0;
		if (!empty($_SESSION['any_out_of_stock'])) $split_order = @$_SESSION['split_order']=='split'?1:2;

		return $split_order;
	}

	public function get_legacy_content_type() {
		return 'physical';
	}

	public function user_admin() {
		$admin_id = NULL;

		if (isset($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id'])) $admin_id = $_SESSION['admin_id'];
		elseif (isset($_SESSION['admin_login_id']) && is_numeric($_SESSION['admin_login_id'])) $admin_id = $_SESSION['admin_login_id'];
		elseif (!empty($_SESSION['admin']) && ($_SESSION['admin'] === 'true')) $admin_id = $_COOKIE['admin_login_id'];
		elseif (!empty($_SESSION['login_id'])) $admin_id = $_SESSION['login_id'];

		return $admin_id;
	}

	private $legacy_session = [];

	public function get_legacy_session_val($key) {
		if (!isset($this->legacy_session[$key])) {
			switch ($key) {
				case 'payment_method_id':
					$payment_method_map = [
						'CreditCard' => 1,
						'Paypal' => 2,
						'Net10' => 5,
						'Net15' => 6,
						'Net30' => 7,
						'Net45' => 15,
						'AccountCredit' => 8,
						'CustService' => 16,
						'CheckMO' => 3,
					];

					$this->legacy_session[$key] = !empty($payment_method_map[$_SESSION['paymentMethod']])?$payment_method_map[$_SESSION['paymentMethod']]:$payment_method_map['CheckMO'];

					break;
				case 'admin_id':
					$admin_id = NULL;

					if (isset($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id'])) $admin_id = $_SESSION['admin_id'];
					elseif (isset($_SESSION['admin_login_id']) && is_numeric($_SESSION['admin_login_id'])) $admin_id = $_SESSION['admin_login_id'];
					elseif (!empty($_SESSION['admin']) && ($_SESSION['admin'] === 'true')) $admin_id = $_COOKIE['admin_login_id'];
					elseif (!empty($_SESSION['login_id'])) $admin_id = $_SESSION['login_id'];

					$this->legacy_session[$key] = $admin_id;
					break;
				case 'channel':
					$this->legacy_session[$key] = !empty($this->get_legacy_session_val('admin_id'))?'phone':'web';
					break;
				case 'split_order':
					$split_order = 0;
					if (!empty($_SESSION['any_out_of_stock'])) $split_order = @$_SESSION['split_order']=='split'?1:2;

					$this->legacy_session[$key] = $split_order;
					break;
				case 'customers_referer_url':
					$this->legacy_session[$key] = @$_SESSION['ref_url'];
					break;
				case 'currency':
					$this->legacy_session[$key] = 'USD';
					break;
				case 'currency_value':
					$this->legacy_session[$key] = 1;
					break;
				case 'purchase_order_number':
					$this->legacy_session[$key] = !empty($_SESSION['po_marker'])&&!empty($_SESSION['purchase_order_number'])?$_SESSION['purchase_order_number']:'';
					break;
				case 'dealer_payment_module':
					if (!$this->get_customer()->has_terms()) $this->legacy_session[$key] = 0;
					else $this->legacy_session[$key] = $this->get_customer()->get_header('legacy_dealer_pay_module');
					break;
				case 'dealer_shipping_module':
					if (($this->get_customer()->has_own_shipping_account() && $this->get_legacy_session_val('shipping_account_choice') != 4 && $this->get_legacy_session_val('shipping_method_id') != 48) || $this->get_legacy_session_val('shipping_account_choice') == 0) {
						$this->legacy_session[$key] = 1;
					}
					else $this->legacy_session[$key] = 0;

					break;
				case 'shipping_method_id':
					$this->legacy_session[$key] = !empty($_SESSION['shipping']['shipping_method_id'])?$_SESSION['shipping']['shipping_method_id']:NULL;
					break;
				case 'shipping_carrier':
					$this->legacy_session[$key] = self::query_fetch('SELECT carrier FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::SINGLE, [':shipping_method_id' => $this->get_legacy_session_val('shipping_method_id')]);
					break;
				case 'shipping_account_choice':
					$this->legacy_session[$key] = isset($_SESSION['shipping_account_choice'])?$_SESSION['shipping_account_choice']:NULL;
					break;
				case 'customers_fedex':
					if ($this->get_legacy_session_val('dealer_shipping_module') == 0 && $this->get_legacy_session_val('shipping_account_choice') != 0) $this->legacy_session[$key] = 'N/A';
					else {
						switch ($this->get_legacy_session_val('shipping_account_choice')) {
							case 0:
								$this->legacy_session[$key] = @$_SESSION['customers_fedex'];
								break;
							case 2:
								$this->legacy_session[$key] = $this->get_customer()->get_header('fedex_account_number');
								break;
							default:
								$this->legacy_session[$key] = '';
								break;
						}
					}
					break;
				case 'customers_ups':
					if ($this->get_legacy_session_val('dealer_shipping_module') == 0 && $this->get_legacy_session_val('shipping_account_choice') != 0) $this->legacy_session[$key] = 'N/A';
					else {
						switch ($this->get_legacy_session_val('shipping_account_choice')) {
							case 0:
								$this->legacy_session[$key] = @$_SESSION['customers_ups'];
								break;
							case 2:
								$this->legacy_session[$key] = $this->get_customer()->get_header('ups_account_number');
								break;
							default:
								$this->legacy_session[$key] = '';
								break;
						}
					}
					break;
				case 'ups_account_number':
					if ($this->get_legacy_session_val('shipping_carrier') != 'UPS') $this->legacy_session[$key] = '';
					elseif ($this->get_legacy_session_val('dealer_shipping_module') == 0 && $this->get_legacy_session_val('shipping_account_choice') != 0) $this->legacy_session[$key] = '724TT9'; // CK account #
					else $this->legacy_session[$key] = $this->get_legacy_session_val('customers_ups');
					break;
				case 'fedex_account_number':
					if ($this->get_legacy_session_val('shipping_carrier') != 'FedEx') $this->legacy_session[$key] = '';
					elseif ($this->get_legacy_session_val('dealer_shipping_module') == 0 && $this->get_legacy_session_val('shipping_account_choice') != 0) $this->legacy_session[$key] = '285019516'; // CK account #
					else $this->legacy_session[$key] = $this->get_legacy_session_val('customers_fedex');
					break;
				case 'fedex_bill_type':
					if ($this->get_legacy_session_val('dealer_shipping_module') == 0 && $this->get_legacy_session_val('shipping_account_choice') != 0) $this->legacy_session[$key] = 1;
					elseif (in_array($this->get_legacy_session_val('shipping_method_id'), [23, 29, 9, 15])) $this->legacy_session[$key] = 5;
					elseif (in_array($this->get_legacy_session_val('shipping_carrier'), ['UPS', 'FedEx'])) $this->legacy_session[$key] = 2;
					else $this->legacy_session[$key] = 1;

					break;
				case 'freight_residential':
					$this->legacy_session[$key] = !empty($_SESSION['residential'])?1:0;
					break;
				case 'freight_liftgate':
					$this->legacy_session[$key] = !empty($_SESSION['freight_opts_liftgate'])?1:0;
					break;
				case 'freight_inside':
					$this->legacy_session[$key] = !empty($_SESSION['freight_opts_inside'])?1:0;
					break;
				case 'freight_limitaccess':
					$this->legacy_session[$key] = !empty($_SESSION['freight_opts_limitaccess'])?1:0;
					break;
				case 'coupon':
					$coupon = NULL;
					if (!empty($_SESSION['cc_id'])) $coupon = self::query_fetch('SELECT * FROM coupons WHERE coupon_id = :coupon_id', cardinality::ROW, [':coupon_id' => $_SESSION['cc_id']]);
					$this->legacy_session[$key] = $coupon;
					break;
				case 'dropship':
				case 'use_reclaimed_packaging':
					$this->legacy_session[$key] = CK\fn::check_flag(@$_SESSION[$key])?1:0;
					break;
				case 'packing_slip':
					$this->legacy_session[$key] = !empty($_SESSION[$key])?$_SESSION[$key]:'';
					break;
				default:
					throw new CKCartException('Could not understand request for session variable ['.$key.']');
					break;
			}
		}

		return $this->legacy_session[$key];
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public function cache_rate_quotes(ck_address2 $address, $rate_groups) {
		$_SESSION['checkout_shipping_quote_cache'] = [
			'weight' => $this->get_weight(),
			'shipping_address' => $address->get_unique_id(),
			'cache_time' => new DateTime,
			'quotes' => [],
		];

		foreach ($rate_groups as $rg => $group) {
			if (!empty($group['freight_quote'])) {
				$_SESSION['checkout_shipping_quote_cache']['quotes'][$group['freight_quote']['shipping_method_id']] = $group['freight_quote'];
			}
			elseif (!empty($group['rate_quotes'])) {
				foreach ($group['rate_quotes'] as $rq => $quote) {
					$_SESSION['checkout_shipping_quote_cache']['quotes'][$quote['shipping_method_id']] = $quote;
				}
			}
		}
	}

	public function rebuild_cart() {
		$this->set_keys();
		$create = TRUE;
		if ($this->id()) {
			if (!$this->skeleton->has('customers_id')) { // we're not currently logged on
				// ... and the cart we have an ID for is not currently logged on - we can use this cart
				if (empty($this->get_header('customers_id'))) $create = FALSE;
				// otherwise, if we're not logged on but the cart is, we'll create a new cart below
			}
			else { // we *are* logged on
				// ... and the cart is logged on to the same customer - we can use this cart
				if ($this->get_header('customers_id') == $this->skeleton->get('customers_id') && $this->get_header('customers_extra_logins_id') == $this->skeleton->get('customers_extra_logins_id')) $create = FALSE;
				// ... or the cart is *not* logged on - we can use it, but we'll need to do some work
				elseif (empty($this->get_header('customers_id'))) {
					// if we find another *different* cart for this customer, merge our current cart with that one
					if ($header = self::fetch('cart_header_by_key' , [':cart_key' => $this->get_cart_key()])) {
						// we only need to worry about merging products, we can't have quotes, payments, shipments, etc unless we're already logged in
						$products = $this->get_products();
						$this->reset_cart(TRUE);
						self::query_execute('UPDATE ck_carts SET date_updated = NOW() WHERE cart_id = :cart_id', cardinality::NONE, [':cart_id' => $this->id($header['cart_id'])]);

						if (!empty($products)) {
							foreach ($products as $product) {
								$this->update_product($product['listing'], $product['quantity'], FALSE, $product['parent_products_id'], $product['option_type']);
							}
						}
					}
					// otherwise, we'll just use this cart but log it in
					else {
						self::query_execute('UPDATE ck_carts SET cart_key = :cart_key, customers_id = :customers_id, customers_extra_logins_id = :customers_extra_logins_id, date_updated = NOW() WHERE cart_id = :cart_id', cardinality::NONE, [':cart_key' => $this->get_cart_key(), ':customers_id' => $this->skeleton->get('customers_id'), ':customers_extra_logins_id' => $this->skeleton->get('customers_extra_logins_id'), ':cart_id' => $this->id()]);
					}

					$create = FALSE;
				}
				// ... or we're taking over a currently logged in cart with a new login, and there's a saved cart
				elseif ($header = self::fetch('cart_header_by_key', [':cart_key' => $this->get_cart_key()])) {
					$this->id($header['cart_id']);
					$create = FALSE;
				}
				/// otherwise, if we're logged on to a different customer than the cart, we'll create a new cart below
			}
		}

		$this->skeleton->rebuild('active_cart_shipment_id');
		$this->skeleton->rebuild('header');
		$this->skeleton->rebuild('customer');
		$this->skeleton->rebuild('shipments');
		$this->skeleton->rebuild('shipping_address');
		$this->skeleton->rebuild('payments');
		$this->skeleton->rebuild('quotes');
		$this->skeleton->rebuild('products');
		$this->skeleton->rebuild('universal_products');

		if ($create) {
			self::query_execute('INSERT INTO ck_carts (cart_key, customers_id, customers_extra_logins_id, date_updated) VALUES (:cart_key, :customers_id, :customers_extra_logins_id, NOW())', cardinality::NONE, [':cart_key' => $this->get_cart_key(), ':customers_id' => $this->skeleton->get('customers_id'), ':customers_extra_logins_id' => $this->skeleton->get('customers_extra_logins_id')]);
			$this->id(self::fetch_insert_id());
			$this->create_first_shipment();
		}
	}

	public function update(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$cart_header = new prepared_fields($data, prepared_fields::UPDATE_QUERY);
			$cart_header->filter(['customer_comments', 'admin_comments']); // whitelist

			$id = new prepared_fields(['cart_id' => $this->id()]);

			self::query_execute('UPDATE ck_carts SET '.$cart_header->update_sets().', date_updated = NOW() WHERE cart_id = :cart_id', cardinality::NONE, prepared_fields::consolidate_parameters($cart_header, $id));

			$this->skeleton->rebuild('header');

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCartException('Failed to update cart: '.$e->getMessage());
		}
	}

	private function create_first_shipment() {
		$this->create_shipment([]);
		$this->skeleton->rebuild('active_cart_shipment_id');
	}

	public function create_shipment(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			if (!empty($data['shipping_address_book_id'])) {
				if (!$this->is_logged_in()) throw new CKCartException('Shipping Address cannot be assigned to a cart without a logged in customer.');
				elseif (!($address = $this->get_customer()->get_addresses($data['shipping_address_book_id']))) throw new CKCartException('The selected shipping address does not belong to the currently logged in customer');
			}

			$data['cart_id'] = $this->id();
			$shipment = new prepared_fields($data, prepared_fields::INSERT_QUERY);
			self::query_execute('INSERT INTO ck_cart_shipments ('.$shipment->insert_fields().', date_updated) VALUES ('.$shipment->insert_values().', NOW())', cardinality::NONE, $shipment->insert_parameters());

			$this->skeleton->rebuild('shipments');
			$this->skeleton->rebuild('shipping_address');

			self::transaction_commit($savepoint);
		}
		catch (CKCartException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCartException('Failed to create cart shipment: '.$e->getMessage());
		}
	}

	public function update_shipment($cart_shipment_id, Array $data) {
		$savepoint = self::transaction_begin();

		try {
			if (!empty($data['shipping_address_book_id'])) {
				if (!$this->is_logged_in()) throw new CKCartException('Shipping Address cannot be assigned to a cart without a logged in customer.');
				elseif (!($address = $this->get_customer()->get_addresses($data['shipping_address_book_id']))) throw new CKCartException('The selected shipping address does not belong to the currently logged in customer');
			}

			$shipment = new prepared_fields($data, prepared_fields::UPDATE_QUERY);
			$id = new prepared_fields(['cart_id' => $this->id(), 'cart_shipment_id' => $cart_shipment_id]);
			self::query_execute('UPDATE ck_cart_shipments SET '.$shipment->update_sets().' WHERE '.$id->where_clause(), cardinality::NONE, prepared_fields::consolidate_parameters($shipment, $id));

			$this->skeleton->rebuild('shipments');
			$this->skeleton->rebuild('shipping_address');

			self::transaction_commit($savepoint);
		}
		catch (CKCartException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCartException('Failed to update cart shipment: '.$e->getMessage());
		}
	}

	public function update_active_shipment(Array $data) {
		return $this->update_shipment($this->select_cart_shipment(), $data);
	}

	public function remove_shipment($cart_shipment_id) {
		$savepoint = self::transaction_begin();

		try {
			//delete main product
			self::query_execute('DELETE FROM ck_cart_shipments WHERE cart_shipment_id = :cart_shipment_id', cardinality::NONE, [':cart_shipment_id' => $cart_shipment_id]);

			$this->skeleton->rebuild('shipments');
			$this->skeleton->rebuild('shipping_address');
			if ($this->select_cart_shipment() == $cart_shipment_id) $this->skeleton->rebuild('active_cart_shipment_id');

			if (!$this->has_shipments()) $this->create_first_shipment();

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCartException('Failed to remove cart shipment: '.$e->getMessage());
		}
	}

	public function create_payment($cart_shipment_id, Array $data) {
		$savepoint = self::transaction_begin();

		try {
			if (!empty($data['billing_address_book_id'])) {
				if (!$this->is_logged_in()) throw new CKCartException('Billing Address cannot be assigned to a cart without a logged in customer.');
				elseif (!($address = $this->get_customer()->get_addresses($data['billing_address_book_id']))) throw new CKCartException('The selected billing address does not belong to the currently logged in customer');
			}

			$data['cart_id'] = $this->id();
			$data['cart_shipment_id'] = $cart_shipment_id;
			$payment = new prepared_fields($data, prepared_fields::INSERT_QUERY);
			self::query_execute('INSERT INTO ck_cart_payments ('.$payment->insert_fields().', date_updated) VALUES ('.$payment->insert_values().', NOW())', cardinality::NONE, $payment->insert_parameters());

			$this->skeleton->rebuild('payments');
			$this->skeleton->rebuild('billing_address');

			self::transaction_commit($savepoint);
		}
		catch (CKCartException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCartException('Failed to create cart payment: '.$e->getMessage());
		}
	}

	public function update_payment($cart_payment_id, Array $data) {
		$savepoint = self::transaction_begin();

		try {
			if (!empty($data['billing_address_book_id'])) {
				if (!$this->is_logged_in()) throw new CKCartException('Billing Address cannot be assigned to a cart without a logged in customer.');
				elseif (!($address = $this->get_customer()->get_addresses($data['billing_address_book_id']))) throw new CKCartException('The selected billing address does not belong to the currently logged in customer');
			}

			$payment = new prepared_fields($data, prepared_fields::UPDATE_QUERY);
			$id = new prepared_fields(['cart_id' => $this->id(), 'cart_payment_id' => $cart_payment_id]);
			self::query_execute('UPDATE ck_cart_payments SET '.$payment->update_sets().' WHERE '.$id->where_clause(), cardinality::NONE, prepared_fields::consolidate_parameters($payment, $id));

			$this->skeleton->rebuild('payments');
			$this->skeleton->rebuild('billing_address');

			self::transaction_commit($savepoint);
		}
		catch (CKCartException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCartException('Failed to update cart payment: '.$e->getMessage());
		}
	}

	public function remove_payment($cart_payment_id) {
		$savepoint = self::transaction_begin();

		try {
			//delete main product
			self::query_execute('DELETE FROM ck_cart_payments WHERE cart_payment_id = :cart_payment_id', cardinality::NONE, [':cart_payment_id' => $cart_payment_id]);

			$this->skeleton->rebuild('payments');
			$this->skeleton->rebuild('billing_address');

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCartException('Failed to remove cart payment: '.$e->getMessage());
		}
	}

	/* --- these more normalized methods will eventually replace the early forms currently in place, but for now I'm leaving them commented out to not confuse things
	public static function create(Array $data) {
	}

	public function remove() {
	}

	public function add_products(Array $data) {
	}

	public function update_product($cart_product_id, Array $data) {
	}

	public function remove_product($cart_product_id) {
	}

	public function add_quotes($quote_id) {
	}

	public function remove_quote($quote_id) {
	}
	*/

	/* --- these functions were never in use and now probably never will be
	public function set_billing_address($address_book_id) {
		$this->skeleton->rebuild('billing_address');
		unset($_SESSION['billto']);
		if ($this->has_customer()) {
			if ($address = $this->get_customer()->get_addresses($address_book_id)) {
				$this->skeleton->load('billing_address', $address);
				$_SESSION['billto'] = $address_book_id;
			}
			// if we tried to set the address to an ID that is not owned by this customer, puke
			elseif (is_numeric($address_book_id)) throw new CKCartException('The selected billing address does not belong to the currently logged in customer');
		}
		else throw new CKCartException('Billing Address cannot be assigned to a cart without a logged in customer');
	}

	public function set_option($field, $data) {
		$this->skeleton->rebuld('options');

		$mapped_field = $field;
		if (isset($this->field_map['options'][$field])) $mapped_field = $this->field_map['options'][$field];

		if (!isset($this->skeleton->format('options')[$field])) return FALSE;

		if (!is_null($data)) {
			if (is_bool($data)) $_SESSION[$mapped_field] = $data?1:0;
			else $_SESSION[$mapped_field] = $data;
		}
		else unset($_SESSION[$mapped_field]);
	}

	public function set_shipping_method($key) {
		$this->skeleton->rebuild('shipping_method');
		unset($_SESSION['shipping']);

		$shipping_method = explode('_', $key, 2);

		if (count($shipping_method) == 1) return FALSE;

		if (!($rate_groups = $this->get_ship_quotes($this->get_shipping_address(), $shipping_method[1], $shipping_method[0]))) return FALSE;

		// we perform this check now rather than before getting quotes because only now have the shipping modules been instantiated - it's a bit messed up, but we'll fix it when we fix shipping
		// I don't realistically know how often this check fails, maybe never, but I'm carrying it forward from legacy
		if (empty($GLOBALS[$shipping_method[0]]) || !is_object($GLOBALS[$shipping_method[0]])) return FALSE;

		if (!empty($rate_groups[0]['error_raw'])) return FALSE;

		if (!empty($rate_groups[0]['freight_quote'])) $quote = $rate_groups[0]['freight_quote'];
		else $quote = $rate_groups[0]['rate_quotes'][0];

		$_SESSION['shipping'] = [
			'id' => $key,
			'title' => $rate_groups[0]['module'].' ('.$quote['title_raw'].')',
			'shipping_method_id' => $quote['shipping_method_id'],
			'cost' => $quote['price_raw']
		];

		return TRUE;
	}

	public function set_comment($field, $comment) {
		$this->skeleton->rebuld('comments');

		$mapped_field = $field;
		if (isset($this->field_map['comments'][$field])) $mapped_field = $this->field_map['comments'][$field];

		if (!isset($this->skeleton->format('comments')[$field])) return FALSE;

		if (!empty($comment)) $_SESSION[$mapped_field] = $comment;
		else unset($_SESSION[$mapped_field]);
	}*/

	public function process_page($action) {
		// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled
		if (empty($_COOKIE) && !service_locator::get_session_service()->session_exists()) CK\fn::redirect_and_exit('/cookie_usage.php');

		$__FLAG = request_flags::instance();

		$ajax_result = [];
		$redirect_url = NULL;

		switch ($action) {
			case 'buy_now':
				$_REQUEST['quantity'] = 1;
			case 'add_product':
				// add product to cart

				$ajax_result['success'] = 1;
				$ajax_result['add_qty'] = $_REQUEST['quantity'];
				$ajax_result['cart_qty'] = $this->get_units();
				$ajax_result['cart_contents'] = $this->get_products();

				$redirect_url = '/shopping_cart.php';

				if (empty($_REQUEST['products_id'])) break;
				if (!is_numeric($_REQUEST['products_id'])) break;

				$ajax_result['success'] = 0;

				$this->update_product(new ck_product_listing($_REQUEST['products_id']), $_REQUEST['quantity'], FALSE);

				if (!empty($_REQUEST['addon_select'])) {
					foreach ($_REQUEST['addon_select'] as $products_id => $on) {
						if (!CK\fn::check_flag($on)) continue; // just a double check

						$quantity = $_REQUEST['addon_quantity'][$products_id];

						$opt = new ck_product_listing($products_id);
						foreach ($opt->get_parent_listings('extra') as $parent) {
							if ($parent['products_id'] != $_REQUEST['products_id']) continue;
							$option_type = !empty($parent['recommended?'])?self::$option_types['RECOMMENDED']:self::$option_types['OPTIONAL'];
						}

						$this->update_product($opt, $quantity, FALSE, $_REQUEST['products_id'], $option_type);
					}
				}

				$ajax_result['success'] = 1;
				$ajax_result['cart_qty'] = $this->get_units();
				$ajax_result['cart_contents'] = $this->get_products();
				break;
			case 'update_product':
				// update product from cart
				$ajax_result['success'] = 0;
				$products_id = $_REQUEST['products_id'];
				$parent_products_id = !empty($_REQUEST['parent_products_id'])?$_REQUEST['parent_products_id']:NULL;
				$quantity = max(0, (int) $_REQUEST['quantity']); // gets the same result as the parseInt we're running in javascript

				$option_type = !empty($_REQUEST['option_type'])?$_REQUEST['option_type']:0;

				if (!in_array($option_type, self::$option_types)) $option_type = self::$option_types['UNKNOWN'];

				$prod = new ck_product_listing($products_id);

				$cart_product_id = $this->update_product($prod, $quantity, TRUE, $parent_products_id, $option_type);

				if ($quantity > 0) {
					foreach ($this->get_products() as $product) {
						if ($product['cart_product_id'] == $cart_product_id) break;
					}
					$ajax_result['line_total'] = '$'.number_format($quantity * $product['unit_price'], 2);
				}
				else $ajax_result['line_total'] = '$0.00';

				$ajax_result['success'] = 1;
				$ajax_result['quantity'] = $quantity;

				if ($option_type == self::$option_types['NONE']) $ajax_result['total_line_total'] = '$'.number_format($this->get_product_total($products_id), 2);
				else $ajax_result['total_line_total'] = '$'.number_format($this->get_total($parent_products_id), 2);
				$ajax_result['product_total'] = '$'.number_format($this->get_product_total(), 2);
				$ajax_result['cart_total'] = '$'.number_format($this->get_total(), 2);
				$ajax_result['cart_qty'] = $this->get_units();

				$ajax_result['show_stock_status'] = 0;

				if ($quantity > 0 && CK\fn::check_flag(@$_SESSION['admin']) || (date('N') >= 1 && date('N') <= 5 && date('G') >= 18 && date('G') <= 20)) {
					$ajax_result['show_stock_status'] = 1;

					if ($quantity <= $prod->get_inventory('available')) {
						$ajax_result['stock_status_color'] = '009900';

						// it's after 7 PM Monday through Thursday, or Sunday
						if ((date('w') < 5 && date('G') > 19) || date('w') == 0) $ajax_result['stock_status_message'] = 'Available to ship tomorrow';
						// it's after 7 PM Friday, or Saturday
						elseif ((date('w') == 5 && date('G') > 19) || date('2') == 6) $ajax_result['stock_status_message'] = 'Available to ship Monday';
						else $ajax_result['stock_status_message'] = 'Available to ship today';
					}
					elseif ($prod->get_inventory('available') <= 0) {
						$ajax_result['stock_status_message'] = 'Not available to ship immediately.';
						$ajax_result['stock_status_color'] = 'd22842';
					}
					else {
						$ajax_result['stock_status_color'] = '009900';

						// it's after 7 PM Monday through Thursday, or Sunday
						if ((date('w') < 5 && date('G') > 19) || date('w') == 0) $ajax_result['stock_status_message'] = $prod->get_inventory('available').' available to ship tomorrow';
						// it's after 7 PM Friday, or Saturday
						elseif ((date('w') == 5 && date('G') > 19) || date('2') == 6) $ajax_result['stock_status_message'] = $prod->get_inventory('available').' available to ship Monday';
						else $ajax_result['stock_status_message'] = $prod->get_inventory('available').' available to ship today';

						$ajax_result['stock_status_color_2?'] = 'd22842';
						$ajax_result['stock_status_message_2?'] = ($quantity-$prod->get_inventory('available')).' not available to ship immediately';
					}
				}

				$redirect_url = '/shopping_cart.php';

				break;
			case 'delete_cart_item':
				foreach ($this->get_products() as $product) {
					if ($product['cart_product_id'] != $_REQUEST['cart_product_id']) continue;

					$ajax_result['undo_cart_key'] = 'undo_cart_key_'.$product['cart_product_id'];

					if ($product['option_type'] == self::$option_types['NONE']) {
						$_SESSION[$ajax_result['undo_cart_key']] = self::fetch('products_for_undo_delete', [':cart_id' => $this->id(), ':products_id' => $product['products_id']]);

						$this->update_product($product['listing'], 0);

						$ajax_result['product_name'] = $product['listing']->get_header('products_name');
					}
					else {
						$_SESSION[$ajax_result['undo_cart_key']] = self::fetch('addon_products_for_undo_delete', [':cart_id' => $this->id(), ':products_id' => $product['products_id'], ':parent_products_id' => $product['parent_products_id']]);

						$this->update_product($product['listing'], 0, TRUE, $product['parent_products_id'], $product['option_type'], $product['cart_shipment_id']);

						$ajax_result['product_name'] = $product['listing']->get_header('products_name');
						foreach ($product['listing']->get_parent_listings('extra') as $parent) {
							if ($parent['products_id'] != $product['parent_products_id']) continue;
							$ajax_result['product_name'] = $parent['addon_name'];
							break;
						}

						$ajax_result['line_total'] = '$'.number_format($this->get_total($product['parent_products_id']), 2);
					}

					$ajax_result['product_total'] = '$'.number_format($this->get_product_total(), 2);
					$ajax_result['cart_total'] = '$'.number_format($this->get_total(), 2);
					$ajax_result['cart_qty'] = $this->get_units();
					$ajax_result['success'] = 1;
					break;
				}
				$redirect_url = '/shopping_cart.php';
				break;
			case 'undo_delete_cart_item':
				if (!empty($_SESSION[$_REQUEST['undo_cart_key']])) {
					$cart_id_maps = [];
					$parent_products_id = NULL;
					foreach ($_SESSION[$_REQUEST['undo_cart_key']] as $product) {
						if ($product['option_type'] == self::$option_types['INCLUDED']) continue;
						if (empty($product['parent_products_id'])) $parent_products_id = $product['products_id'];
						else $parent_products_id = $product['parent_products_id'];
						$cart_id_maps[$product['cart_product_id']] = $this->update_product(new ck_product_listing($product['products_id']), $product['quantity'], FALSE, $product['parent_products_id'], $product['option_type'], $product['cart_shipment_id']);
					}

					unset($_SESSION[$_REQUEST['undo_cart_key']]);

					$ajax_result['line_total'] = '$'.number_format($this->get_total($parent_products_id), 2);

					$ajax_result['product_total'] = '$'.number_format($this->get_product_total(), 2);
					$ajax_result['cart_total'] = '$'.number_format($this->get_total(), 2);
					$ajax_result['cart_qty'] = $this->get_units();
					$ajax_result['cart_id_maps'] = $cart_id_maps;
				}
				$redirect_url = '/shopping_cart.php';
				break;
			case 'create_new_quote':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create a new quote; please contact the CK Sales Team.';
					break;
				}
				// somehow it's duplicating the quote creation process creating a 2nd empty quote, so this will halt if if there are no more products to add
				if ($this->has_products()) {
					$details = [':admin_id' => $_SESSION['admin_login_id']];

					$customers_id = $_REQUEST['customers_id'];
					$customers_extra_logins_id = $_REQUEST['customers_extra_logins_id'];
					$email_address = $_REQUEST['customer_email_address'];

					if (!empty($email_address) && ($customer = ck_customer2::get_customer_by_email($email_address))) {
						$customers_id = $customer->id();
						if ($customer->get_email_address_id($email_address) == $customers_id) $customers_extra_logins_id = NULL;
						else $customers_extra_logins_id = $customer->get_email_address_id($email_address);
					}

					$details[':customers_id'] = $customers_id;
					$details[':customers_extra_logins_id'] = $customers_extra_logins_id;
					$details[':customer_email'] = $email_address;

					if (!empty($_REQUEST['expiration_date'])) $details[':expiration_date'] = $_REQUEST['expiration_date'];
					$quote = ck_quote::create_quote($details);
					$this->send_products_to_quote($quote);
					$quote->release();

					if ($this->has_customer() && ($customers_id != $this->get_customer()->id() || $this->has_quotes())) $ajax_result['redirect_to'] = '/admin/customer-quote.php?customer_quote_id='.$quote->id();
					else $this->add_cart_quote($quote->id());
				}

				$ajax_result['success'] = 1;

				$redirect_url = '/shopping_cart.php';
				break;
			/*case 'create_advance_replacement':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create an advance replacement; please contact the CK Sales Team.';
					break;
				}
				$details = [':admin_id' => $_SESSION['admin_login_id']];
				if (!empty($_REQUEST['customers_id'])) $details[':customers_id'] = $_REQUEST['customers_id'];
				if (!empty($_REQUEST['customers_extra_logins_id'])) $details[':customers_extra_logins_id'] = $_REQUEST['customers_extra_logins_id'];
				if (!empty($_REQUEST['customer_email_address'])) $details[':customer_email'] = $_REQUEST['customer_email_address'];
				$expiration = clone self::NOW();
				$expiration->add(new DateInterval('P1D'));
				$details[':expiration_date'] = $expiration->format('Y-m-d');
				$quote = ck_quote::create_quote($details);
				$this->send_products_to_quote($quote);
				foreach ($quote->get_products() as $product) {
					$quote->update_quote_line($product['customer_quote_product_id'], NULL, 0);
				}
				$quote->release();

				if (!$this->has_quotes()) $this->add_cart_quote($quote->id());

				$ajax_result['success'] = 1;

				$redirect_url = '/shopping_cart.php';
				break;*/
			case 'add_new_items_to_quote':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create a new quote; please contact the CK Sales Team.';
					break;
				}
				if ($quote = $this->get_quotes()[0]['quote']) $this->send_products_to_quote($quote);

				$ajax_result['success'] = 1;

				$redirect_url = '/shopping_cart.php';
				break;
			case 'add_quote':
				$quote = new ck_quote($_REQUEST['quote_id']);

				if (empty($this->get_header('customers_id'))) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = "You are not currently logged in - please log in to verify\nthis quote belongs to your account.";
					break;
				}
				elseif ($this->get_header('customers_id') != $quote->get_header('customers_id') || $this->get_header('customers_extra_logins_id') != $quote->get_header('customers_extra_logins_id')) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'It appears this quote does not belong to your account.';
					break;
				}

				$this->add_cart_quote($quote->id());

				$ajax_result['success'] = 1;

				$redirect_url = '/shopping_cart.php';
				break;
			case 'remove_quote':
				$this->remove_cart_quote($_REQUEST['quote_id']);

				$ajax_result['success'] = 1;

				$redirect_url = '/shopping_cart.php';
				break;
			case 'update_quote':
				// update quote product from cart

				$ajax_result['success'] = 0;

				$quote = $this->get_quotes()[0]['quote'];

				$ajax_result['line_total'] = '$0.00';

				if (!empty($_REQUEST['quote_quantity'])) {
					foreach ($_REQUEST['quote_quantity'] as $customer_quote_product_id => $quantity) {
						$quantity = (int) $quantity;
						$quote->update_quote_line($customer_quote_product_id, $quantity);

						foreach ($quote->get_products() as $prod) {
							if ($prod['customer_quote_product_id'] != $customer_quote_product_id) continue;
							$ajax_result['line_total'] = '$'.number_format($prod['quantity'] * $prod['price'], 2);
						}
					}
				}

				if (!empty($_REQUEST['quote_price'])) {
					foreach ($_REQUEST['quote_price'] as $customer_quote_product_id => $price) {
						$price = preg_replace('/[^\d.]/', '', $price);
						$quote->update_quote_line($customer_quote_product_id, NULL, $price);

						foreach ($quote->get_products() as $prod) {
							if ($prod['customer_quote_product_id'] != $customer_quote_product_id) continue;
							$ajax_result['line_total'] = '$'.number_format($prod['quantity'] * $prod['price'], 2);
						}
					}
				}

				$ajax_result['success'] = 1;

				$ajax_result['quote_total'] = '$'.number_format($this->get_quote_total(), 2);
				$ajax_result['cart_total'] = '$'.number_format($this->get_total(), 2);
				$ajax_result['cart_qty'] = $this->get_units();

				$redirect_url = '/shopping_cart.php';
				break;
			/*case 'add_product_to_quote':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create a new quote; please contact the CK Sales Team.';
					break;
				}
				break;
			case 'update_product_on_quote':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create a new quote; please contact the CK Sales Team.';
					break;
				}
				break;
			case 'remove_product_from_quote':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create a new quote; please contact the CK Sales Team.';
					break;
				}
				break;
			case 'undo_remove_product_from_quote':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create a new quote; please contact the CK Sales Team.';
					break;
				}
				break;
			case 'update_quote':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create a new quote; please contact the CK Sales Team.';
					break;
				}

				// email
				// date
				break;
			case 'lock_quote_for_work':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create a new quote; please contact the CK Sales Team.';
					break;
				}

				$quote = new ck_quote($_REQUEST['quote_id']);
				$quote['quote']->lock_for_work();

				$ajax_result['success'] = 1;

				$redirect_url = '/shopping_cart.php';
				break;
			case 'release_quote':
				if (!CK\fn::check_flag(@$_SESSION['admin'])) {
					$ajax_result['success'] = 0;
					$ajax_result['message'] = 'You do not have the appropriate permission to create a new quote; please contact the CK Sales Team.';
					break;
				}

				$quote = new ck_quote($_REQUEST['quote_id']);

				// email
				// date

				$quote['quote']->release();

				$ajax_result['success'] = 1;

				$redirect_url = '/shopping_cart.php';
				break;*/
			default:
				// if it's an action we don't recognize, it's not a cart action, don't try to handle it
				return;
		}
		$this->rebuild_cart();
		// only actually take over and respond if we got some direction in the switch cases above
		if (!empty($ajax_result) && $__FLAG['ajax']) {
			if (isset($__FLAG['shopping_cart'])) { // if the direction came from the shopping cart then handle it this way
				echo json_encode($ajax_result);
				exit();
			}
			return $ajax_result; // else we are assuming the action is for the cart flyout and we return instead of echo
		}
		elseif (!empty($redirect_url)) CK\fn::redirect_and_exit($redirect_url);
	}

	public function select_cart_shipment() {
		if (!$this->skeleton->built('active_cart_shipment_id')) {
			if (!$this->has_shipments()) $this->create_first_shipment();
			$this->skeleton->load('active_cart_shipment_id', $this->get_shipments()[0]['cart_shipment_id']);
		}
		return $this->skeleton->get('active_cart_shipment_id');
	}

	// recursive add to ensure all levels of included items get updated
	public function update_product(ck_product_listing $product, $qty, $is_total=TRUE, $parent_products_id=NULL, $option_type=NULL, $cart_shipment_id=NULL, $allow_discontinued=FALSE, $quote=NULL) {
		if (is_numeric($product)) $product = new ck_product_listing($product);

		if (!($product instanceof ck_product_listing) || !$product->found()) throw new CKCartException('Failed updating cart product; invalid product.');

		if (!$product->is_cartable() && (!$is_total || $qty != 0)) return;

		if (empty($cart_shipment_id)) $cart_shipment_id = $this->select_cart_shipment();

		// force this, and any subsequent recursive calls, to to be the total #

		$cart_product = $this->get_products($cart_shipment_id, $product->id(), $parent_products_id);

		if (!$is_total) {
			$qty += $cart_product['quantity'];
			$is_total = TRUE; // just make it explicit what we're doing
		}

		$inventory = $product->get_inventory();
		$prices = $product->get_price();

		// if the product is discontinued, never add more than the available qty
		if (!$allow_discontinued && $product->is('discontinued')) $qty = min($qty, $inventory['available']);

		if ($qty <= 0) {
			// we're removing this item from the cart
			// because of how we've set this up, we don't need to do a recursive remove, we can just do it all right here
			if (empty($option_type) || $option_type == self::$option_types['NONE']) {
				self::query_execute('DELETE FROM ck_cart_products WHERE cart_id = :cart_id AND cart_shipment_id = :cart_shipment_id AND (products_id = :products_id OR parent_products_id = :products_id)', cardinality::NONE, [':cart_id' => $this->id(), ':cart_shipment_id' => $cart_shipment_id, ':products_id' => $product->id()]);
			}
			else {
				self::query_execute('DELETE FROM ck_cart_products WHERE cart_id = :cart_id AND cart_shipment_id = :cart_shipment_id AND products_id = :products_id AND option_type = :option_type AND parent_products_id = :parent_products_id', cardinality::NONE, [':cart_id' => $this->id(), ':cart_shipment_id' => $cart_shipment_id, ':products_id' => $product->id(), ':option_type' => $option_type, ':parent_products_id' => $parent_products_id]);
			}
			$this->skeleton->rebuild('products');
			return;
		}

		if (empty($option_type)) $option_type = self::$option_types['NONE'];

		$unit_price = $prices['display'];
		if ($option_type == self::$option_types['INCLUDED']) $unit_price = 0;
		elseif (in_array($option_type, [self::$option_types['OPTIONAL'], self::$option_types['RECOMMENDED']])) {
			if (($parent = $this->get_products($cart_shipment_id, $parent_products_id)) && ($options = $parent['listing']->get_options('extra'))) {
				foreach ($options as $option) {
					if ($option['products_id'] != $product->id()) continue;
					$unit_price = $option['price'];
				}
			}
		}

		$quoted_price = $quoted_reason = NULL;
		if (!empty($quote)) {
			$quoted_price = $quote['price'];
			$quoted_reason = $quote['reason'];
		}

		// just insert, or take over, the qty
		if (!empty($cart_product)) {
			self::query_execute('UPDATE ck_cart_products SET quantity = :quantity, unit_price = :unit_price, price_options_snapshot = :price_options_snapshot, quoted_price = IFNULL(:quoted_price, quoted_price), quoted_reason = IFNULL(:quoted_reason, quoted_reason), option_type = :option_type, date_updated = NOW() WHERE cart_shipment_id = :cart_shipment_id AND cart_id = :cart_id AND products_id = :products_id AND ((:parent_products_id IS NULL AND parent_products_id IS NULL) OR parent_products_id = :parent_products_id)', cardinality::NONE, [':cart_shipment_id' => $cart_shipment_id, ':cart_id' => $this->id(), ':products_id' => $product->id(), ':quantity' => $qty, ':unit_price' => $unit_price, ':price_options_snapshot' => json_encode($prices), ':quoted_price' => $quoted_price, ':quoted_reason' => $quoted_reason, ':option_type' => $option_type, ':parent_products_id' => $parent_products_id]);

			$return_cart_product_id = $cart_product['cart_product_id'];
		}
		else {
			self::query_execute('INSERT INTO ck_cart_products (cart_shipment_id, cart_id, products_id, quantity, unit_price, price_options_snapshot, quoted_price, quoted_reason, option_type, parent_products_id, date_updated) VALUES (:cart_shipment_id, :cart_id, :products_id, :quantity, :unit_price, :price_options_snapshot, :quoted_price, :quoted_reason, :option_type, :parent_products_id, NOW()) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity), unit_price=VALUES(unit_price), price_options_snapshot=VALUES(price_options_snapshot), quoted_price=VALUES(quoted_price), quoted_reason=VALUES(quoted_reason), option_type=VALUES(option_type), parent_products_id=VALUES(parent_products_id)', cardinality::NONE, [':cart_shipment_id' => $cart_shipment_id, ':cart_id' => $this->id(), ':products_id' => $product->id(), ':quantity' => $qty, ':unit_price' => $unit_price, ':price_options_snapshot' => json_encode($prices), ':quoted_price' => $quoted_price, ':quoted_reason' => $quoted_reason, ':option_type' => $option_type, ':parent_products_id' => $parent_products_id]);

			$return_cart_product_id = self::fetch_insert_id();
		}

		// we manage included options along with parent items
		if ($options = $product->get_options('included')) {
			if (empty($parent_products_id)) $parent_products_id = $product->id();
			foreach ($options as $option) {
				// we need to deal with a potentially different qty if this is a bundle
				$option_qty = $qty;
				if ($product->is('is_bundle')) $option_qty *= $option['bundle_quantity'];

				// at this point, $qty is the total qty, $is_total is TRUE, $parent_products_id is either the current product or the top level product
				$this->update_product($option['listing'], $option_qty, $is_total, $parent_products_id, self::$option_types['INCLUDED'], $cart_shipment_id);
			}
		}

		$this->skeleton->rebuild('products');

		return $return_cart_product_id;
	}

	private function send_products_to_quote(ck_quote $quote) {
		// we probably should use the methods to add the products to the quote and remove them from the cart, but this will be much more efficient and unlikely to cause problems until we fully implement the cart shipments
		self::query_execute('INSERT INTO customer_quote_products (customer_quote_id, product_id, parent_products_id, option_type, price, quantity) SELECT :customer_quote_id, products_id, parent_products_id, option_type, IFNULL(quoted_price, unit_price), quantity FROM ck_cart_products WHERE cart_id = :cart_id', cardinality::NONE, [':customer_quote_id' => $quote->id(), ':cart_id' => $this->id()]);
		self::query_execute('DELETE FROM ck_cart_products WHERE cart_id = :cart_id', cardinality::NONE, [':cart_id' => $this->id()]);
	}

	public function add_cart_quote($quote_id, $cart_shipment_id=NULL) {
		if (empty($cart_shipment_id)) $cart_shipment_id = $this->select_cart_shipment();
		self::query_execute('INSERT IGNORE INTO ck_cart_quotes (cart_shipment_id, cart_id, quote_id) VALUES (:cart_shipment_id, :cart_id, :quote_id)', cardinality::NONE, [':cart_shipment_id' => $cart_shipment_id, ':cart_id' => $this->id(), ':quote_id' => $quote_id]);
		$this->skeleton->rebuild('quotes');
	}

	public function remove_cart_quote($quote_id, $cart_shipment_id=NULL) {
		if (empty($cart_shipment_id)) $cart_shipment_id = $this->select_cart_shipment();
		self::query_execute('DELETE FROM ck_cart_quotes WHERE cart_shipment_id = :cart_shipment_id AND cart_id = :cart_id AND quote_id = :quote_id', cardinality::NONE, [':cart_shipment_id' => $cart_shipment_id, ':cart_id' => $this->id(), ':quote_id' => $quote_id]);
		$this->skeleton->rebuild('quotes');
	}

	public function reset_cart($clear=FALSE) {
		if ($clear) $this->clear_cart();
		unset($_SESSION['cart_id']);
		$this->skeleton->rebuild();
		$this->set_keys();
	}

	private function clear_cart() {
		self::query_execute('DELETE FROM ck_cart_products WHERE cart_id = :cart_id', cardinality::NONE, [':cart_id' => $this->id()]);
		self::query_execute('DELETE FROM ck_cart_quotes WHERE cart_id = :cart_id', cardinality::NONE, [':cart_id' => $this->id()]);
		self::query_execute('DELETE FROM ck_cart_payments WHERE cart_id = :cart_id', cardinality::NONE, [':cart_id' => $this->id()]);
		self::query_execute('DELETE FROM ck_cart_shipments WHERE cart_id = :cart_id', cardinality::NONE, [':cart_id' => $this->id()]);
		self::query_execute('DELETE FROM ck_carts WHERE cart_id = :cart_id', cardinality::NONE, [':cart_id' => $this->id()]);
	}

	/*public function process_shipping($action) {
		// if somehow the selected shipping address does not belong to this customer, reset shipping session
		if (!empty($_SESSION['sendto']) && !$this->get_customer()->get_addresses($_SESSION['sendto'])) {
			unset($_SESSION['sendto']); // this will be reset when we preload the shipping address below
			unset($_SESSION['shipping']);
		}

		// preload the shipping address, which falls through session, default, first-on-customer, null
		$this->get_shipping_address();

		// there was a really misguided attempt to thwart session hijacking by setting a random ID in the
		// session and checking the value set in the session cart against it.  I've removed it
		// we handle session security through many of the methods described here:
		// http://stackoverflow.com/questions/5081025/php-session-fixation-hijacking
		// ... and always using HTTPS

		if ($action == 'process') {
			$this->set_comment('customer', $_POST['comments']);
			$this->set_comment('admin', @$_POST['admin_comments']);

			$this->set_option('blind_ship', CK\fn::check_flag(@$_POST['dropship']));
			$this->set_option('use_po_number', CK\fn::check_flag(@$_POST['po_marker']));
			$this->set_option('po_number', @$_POST['purchase_order_number']);
			$this->set_option('customer_fedex_account_number', @$_POST['customers_fedex']);
			$this->set_option('customer_ups_account_number', @$_POST['customers_ups']);
			$this->set_option('shipment_billing_option', @$_POST['shipping_account_choice']);

			// this one is always 0
			$this->set_option('include_customer_packing_slip', CK\fn::check_flag(@$_POST['packing_slip']));

			// freight options
			$this->set_option('ship_to_residential', CK\fn::check_flag(@$_POST['residential']));
			// 'off' means they don't have a liftgate, so we turn this 'on' to say they need one
			$this->set_option('freight_opts_liftgate', !CK\fn::check_flag(@$_POST['freight_opts']['liftgate']));
			$this->set_option('freight_opts_inside', CK\fn::check_flag(@$_POST['freight_opts']['inside']));
			$this->set_option('freight_opts_limitaccess', CK\fn::check_flag(@$_POST['freight_opts']['limitaccess']));

			// if we've set our shipping method, we're done, go on to payment
			if (!empty($_POST['shipping']) && $this->set_shipping_method($_POST['shipping'])) CK\fn::redirect_and_exit('/checkout_payment.php');
		}
	}*/

	public function record_error_message($msg) {
		if (empty($_SESSION['cart_errors'])) $_SESSION['cart_errors'] = [];
		$_SESSION['cart_errors'][] = $msg;
	}

	public function has_error_messages() {
		return !empty($_SESSION['cart_errors']);
	}

	public function pull_error_messages() {
		$msgs = $_SESSION['cart_errors'];
		$_SESSION['cart_errors'] = NULL;
		unset($_SESSION['cart_errors']);
		return $msgs;
	}
}

class CKCartException extends Exception {
}
?>
