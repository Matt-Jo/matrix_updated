<?php
/*
$Id: order.php,v 1.1.1.1 2004/03/04 23:40:45 ccwjr Exp $

osCommerce, Open Source E-Commerce Solutions
http://www.oscommerce.com

Copyright (c) 2003 osCommerce

Released under the GNU General Public License
*/

class order {
	public $info;
	public $totals;
	public $products;
	public $customer;
	public $delivery;
	public $content_type;

	public function __construct($order_id=NULL) {
		$this->info = [];
		$this->totals = [];
		$this->products = [];
		$this->customer = [];
		$this->delivery = [];

		if (!empty($order_id)) $this->build_order($order_id);
		else $this->build_cart();
	}

	private function build_order($order_id) {
		/*Tracking contribution begin*/
		$order = prepared_query::fetch('SELECT legacy_order, net10_po, net15_po, net30_po, net45_po, amazon_order_number, packing_slip, purchase_order_number, dropship, o.customers_id, o.customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_telephone, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method_id, currency, currency_value, date_purchased, orders_status, orders_sub_status, last_modified, customers_referer_url, payment_id, orders_weight, all_items_in_stock, ce.customers_extra_logins_id, ce.customers_emailaddress as extra_email_address, CONCAT_WS(\' \', ce.customers_firstname, ce.customers_lastname) as extra_name, ce.copy_account, ca_order_id FROM orders o LEFT JOIN customers_extra_logins ce ON o.customers_extra_logins_id = ce.customers_extra_logins_id where orders_id = ?', cardinality::ROW, $order_id);

		/*Tracking contribution end*/

		$all_totals = prepared_query::fetch('SELECT title, text, class, external_id FROM orders_total WHERE orders_id = ? order by sort_order', cardinality::SET, $order_id);

		foreach ($all_totals as $totals) {
			$this->totals[] = array(
				'title' => $totals['title'],
				'text' => $totals['text'],
				'class' => $totals['class'],
				'external_id' => $totals['external_id']
			); # 334
		}

		// begin PayPal_Shopping_Cart_IPN V2.8 DMG
		$order_total = prepared_query::fetch('SELECT text, value, external_id FROM orders_total WHERE orders_id = ? AND class = ?', cardinality::ROW, array($order_id, 'ot_total'));
		// end PayPal_Shopping_Cart_IPN

		//begin PayPal_Shopping_Cart_IPN V2.8 DMG
		$shipping_method = prepared_query::fetch('SELECT title, value, external_id FROM orders_total WHERE orders_id = ? and class = ? ORDER BY external_id DESC', cardinality::ROW, array($order_id, 'ot_shipping'));
		//end PayPal_Shopping_Cart_IPN

		$order_status = prepared_query::fetch('SELECT orders_status_name FROM orders_status WHERE orders_status_id = ?', cardinality::ROW, $order['orders_status']);
		$payment_method = prepared_query::fetch('SELECT label FROM payment_method WHERE id = ?', cardinality::ROW, $order['payment_method_id']);

		$this->info = array(
			'id' => $order_id,
			'currency' => $order['currency'],
			'currency_value' => $order['currency_value'],
			'legacy_order' => $order['legacy_order'],
			'payment_method' => $payment_method['label'],
			'payment_method_id' => $order['payment_method_id'],
			'date_purchased' => $order['date_purchased'],
			'last_modified' => $order['last_modified'],
			'customers_referer_url' => $order['customers_referer_url'],
			//begin PayPal_Shopping_Cart_IPN
			'payment_id' => $order['payment_id'],
			'purchase_order_number' => $order['purchase_order_number'],
			'dropship' => $order['dropship'],
			'packing_slip' => $order['packing_slip'],
			'net10_po' => $order['net10_po'],
			'net15_po' => $order['net15_po'],
			'net30_po' => $order['net30_po'],
			'net45_po' => $order['net45_po'],
			'amazon_order_number' => $order['amazon_order_number'],
			'orders_status' => $order['orders_status'],
			'orders_sub_status' => $order['orders_sub_status'],
			'shipping_cost' => $shipping_method['value'],
			'total_value' => $order_total['value'],
			//end PayPal_Shopping_Cart_IPN
			'last_modified' => $order['last_modified'],
			'ca_order_id' => $order['ca_order_id'],
			'comments' => (isset($_SESSION['comments'])?$_SESSION['comments']:NULL),
			'total' => strip_tags($order_total['text']),
			'shipping_method' => $shipping_method['external_id'], # 334
			'orders_weight'=> $order['orders_weight'],
			'all_items_in_stock' => $order['all_items_in_stock']
		);

		$this->customer = array(
			'id' => $order['customers_id'],
			'name' => $order['customers_name'],
			'extra_name'=> $order['extra_name'],
			//begin PayPal_Shopping_Cart_IPN
			'customer_extra_login_id' => $order['customers_extra_logins_id'],
			'extra_email_address' => $order['extra_email_address'],
			'copy_account' => $order['copy_account'],
			'company' => $order['customers_company'],
			'street_address' => $order['customers_street_address'],
			'suburb' => $order['customers_suburb'],
			'city' => $order['customers_city'],
			'postcode' => $order['customers_postcode'],
			'state' => $order['customers_state'],
			'country' => $order['customers_country'],
			'format_id' => $order['customers_address_format_id'],
			'telephone' => $order['customers_telephone'],
			'email_address' => $order['customers_email_address']
		);

		$this->delivery = array(
			'name' => $order['delivery_name'],
			'company' => $order['delivery_company'],
			'street_address' => $order['delivery_street_address'],
			'suburb' => $order['delivery_suburb'],
			'city' => $order['delivery_city'],
			'postcode' => $order['delivery_postcode'],
			'state' => $order['delivery_state'],
			'country' => $order['delivery_country'],
			'telephone' => $order['delivery_telephone'],
			'format_id' => $order['delivery_address_format_id']
		);

		if (empty($this->delivery['name']) && empty($this->delivery['street_address'])) $this->delivery = false;

		$this->billing = array(
			'name' => $order['billing_name'],
			'company' => $order['billing_company'],
			'street_address' => $order['billing_street_address'],
			'suburb' => $order['billing_suburb'],
			'city' => $order['billing_city'],
			'postcode' => $order['billing_postcode'],
			'state' => $order['billing_state'],
			'country' => $order['billing_country'],
			'format_id' => $order['billing_address_format_id']
		);

		$all_orders_products = prepared_query::fetch('SELECT orders_products_id, products_id, products_name, products_model, products_tax, products_quantity, final_price, is_quote, expected_ship_date FROM orders_products WHERE orders_id = ?', cardinality::SET, $order_id);
		foreach ($all_orders_products as $index => $orders_products) {
			$this->products[$index] = array(
				'qty' => $orders_products['products_quantity'],
				'id' => $orders_products['products_id'],
				//begin PayPal_Shopping_Cart_IPN
				'orders_products_id' => $orders_products['orders_products_id'],
				//end PayPal_Shopping_Cart_IPN
				'name' => $orders_products['products_name'],
				'model' => $orders_products['products_model'],
				'tax' => $orders_products['products_tax'],
				'price' => $orders_products['final_price'],
				'final_price' => $orders_products['final_price'],
				'is_quote' => $orders_products['is_quote'],
				'expected_ship_date' => $orders_products['expected_ship_date']
			);

			$all_attributes = prepared_query::fetch('SELECT products_options_id, products_options, products_options_values_id, products_options_values, options_values_price, price_prefix FROM orders_products_attributes WHERE orders_id = ? AND orders_products_id = ?', cardinality::SET, array($order_id, $orders_products['orders_products_id']));
			//end PayPal_Shopping_Cart_IPN
			if (!empty($all_attributes)) {
				foreach ($all_attributes as $subindex => $attributes) {
					$this->products[$index]['attributes'][$subindex] = array(
						//begin PayPal_Shopping_Cart_IPN
						'option_id' => $attributes['products_options_id'],
						'value_id' => $attributes['products_options_values_id'],
						//end PayPal_Shopping_Cart_IPN
						'option' => $attributes['products_options'],
						'value' => $attributes['products_options_values'],
						'prefix' => $attributes['price_prefix'],
						'price' => $attributes['options_values_price']
					);
				}
			}

			$this->info['tax_groups'][$this->products[$index]['tax']] = '1';
		}
	}

	private function build_cart() {
		$cart = $_SESSION['cart'];

		$this->content_type = $cart->get_legacy_content_type();

		$customer = $cart->get_customer();
		$customer_address = $customer->get_default_address();
		$shipping_address = $cart->get_shipping_address();
		$billing_address = $cart->get_billing_address();

		$shipment = $cart->get_shipments('active');

		$payments = $cart->get_payments($cart->select_cart_shipment());
		if (!empty($payments)) $payment = $payments[0];
		else $payment = NULL;
		if ($customer->has('own_shipping_account') && !is_null($shipment['shipment_account_choice']) && $shipment['shipment_account_choice'] != 4) $ship_cost = 0;
		elseif (!is_null($shipment['shipment_account_choice']) && $shipment['shipment_account_choice'] == 0) $ship_cost = 0;
		elseif (!empty($shipment['shipping_method_id'])) {
			$rate_quote = $cart->get_selected_ship_rate_quote();
			if (!empty($rate_quote)) {
				if (!empty($rate_quote['rate_quotes'])) $ship_cost = CK\text::demonetize($rate_quote['rate_quotes'][0]['price']);
				elseif (!empty($rate_quote['freight_quote'])) $ship_cost = CK\text::demonetize($rate_quote['freight_quote']['price']);
				else $ship_cost = 0;
			}
			else $ship_cost = 0;
		}
		else $ship_cost = 0;

		$this->info = [
			'orders_status' => ck_sales_order::STATUS_CST,
			'orders_sub_status' => ck_sales_order::$sub_status_map['CST']['Uncat'],
			'currency' => 'USD',
			'currency_value' => 1,
			'payment_method' => !empty($payment['payment_method_id'])?ck_payment_method_lookup::instance()->lookup_by_id($payment['payment_method_id'], 'method_label'):'',
			'payment_method_id' => $payment['payment_method_id'],
			/* these are no longer used, so I won't bother to populate them
			'net10_po' => @$_SESSION['paymentMethod']=='Net10'?$_SESSION['net_po_number']:'',
			'net15_po' => @$_SESSION['paymentMethod']=='Net15'?$_SESSION['net_po_number']:'',
			'net30_po' => @$_SESSION['paymentMethod']=='Net30'?$_SESSION['net_po_number']:'',
			'net45_po' => @$_SESSION['paymentMethod']=='Net45'?$_SESSION['net_po_number']:'',
			*/
			'amazon_order_number' => (isset($GLOBALS['amazon_order_number'])?$GLOBALS['amazon_order_number']:''),
			'dropship' => $shipment['blind']?1:0,
			'po_marker' => !empty($shipment['order_po_number'])?1:0,
			'purchase_order_number' => $shipment['order_po_number'],
			'packing_slip' => '',
			'shipping_method' => $shipment['shipping_method_id'],
			'shipping_cost' => $ship_cost,
			'subtotal' => 0,
			'tax' => 0,
			'tax_groups' => [],
			'comments' => $cart->get_header('customer_comments'),
			'admin_comments' => $cart->get_header('admin_comments')
		];

		$this->customer = $customer_address->get_legacy_array();
		$this->delivery = $shipping_address->get_legacy_array();
		$this->billing = $billing_address->get_legacy_array();

		if ($products = $cart->get_products()) {
			foreach ($products as $product) {
				$prod = [
					'qty' => $product['quantity'],
					'display_price' => $product['price_options_snapshot'],
					'name' => $product['listing']->get_header('products_name'),
					'model' => $product['listing']->get_header('products_model'),
					'tax' => 6,
					'tax_description' => 'GA State Tax',
					'price' => $product['unit_price'],
					'final_price' => $product['unit_price'],
					'weight' => $product['listing']->get_total_weight(),
					'id' => $product['products_id'],
					'always_available' => $product['listing']->get_header('always_available'),
					'lead_time' => $product['listing']->get_header('lead_time'),
					'is_quote' => NULL,
				];

				$this->products[] = $prod;

				$shown_price = $prod['final_price'] * $prod['qty'];
				$this->info['subtotal'] += $shown_price;
			}
		}

		if ($quotes = $_SESSION['cart']->get_quotes()) {
			foreach ($quotes as $quote) {
				foreach ($quote['quote']->get_products() as $product) {
					$prod = [
						'qty' => $product['quantity'],
						'display_price' => 'quote',
						'name' => $product['listing']->get_header('products_name'),
						'model' => $product['listing']->get_header('products_model'),
						'tax' => 6,
						'tax_description' => 'GA State Tax',
						'price' => $product['price'],
						'final_price' => $product['price'],
						'weight' => $product['listing']->get_total_weight(),
						'id' => $product['products_id'],
						'always_available' => $product['listing']->get_header('always_available'),
						'lead_time' => $product['listing']->get_header('lead_time'),
						'is_quote' => 1,
					];

					$this->products[] = $prod;

					$shown_price = $prod['final_price'] * $prod['qty'];
					$this->info['subtotal'] += $shown_price;
				}
			}
		}

		$tax = 0;
		require_once(dirname(__FILE__).'/../functions/avatax.php');
		$tax = avatax_get_tax($this->products, $this->info['shipping_cost']);
		$this->info['tax'] = $tax;

		$this->info['total'] = $this->info['subtotal'] + $this->info['tax'] + $this->info['shipping_cost'] + (@$this->info['liftgate_cost']) + (@$this->info['inside_cost']) + (@$this->info['limitaccess_cost']);
	}
}
