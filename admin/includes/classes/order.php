<?php

class order
{
	public $info	= array();
	public $totals	= array();
	public $products = array();
	public $customer = array();
	public $delivery = array();

	public function __construct($orderId) {
		$this->_query($orderId);
	}

	protected function _query($orderId) {
		$order = prepared_query::fetch("SELECT all_items_in_stock, orders_weight, dsm, dpm, customers_ups, customers_fedex, net10_po, net15_po, net30_po, net45_po, amazon_order_number, packing_slip, purchase_order_number, dropship, o.customers_id, o.customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_telephone, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method_id, currency, date_purchased, orders_status, orders_sub_status, last_modified, customers_referer_url, payment_id, cel.customers_extra_logins_id, cel.customers_emailaddress AS extra_email_address, CONCAT_WS(' ',cel.customers_firstname, cel.customers_lastname) AS extra_name, cel.copy_account FROM orders o LEFT JOIN customers_extra_logins cel ON o.customers_extra_logins_id = cel.customers_extra_logins_id WHERE orders_id = :orders_id", cardinality::ROW, [':orders_id' => $orderId]);

		$totals = prepared_query::fetch('SELECT ot.title, ot.text, ot.class, ot.external_id, ot.value, sm.name, sm.carrier, sm.description FROM orders_total ot left join shipping_methods sm ON sm.shipping_code = ot.external_id WHERE orders_id = :orders_id ORDER BY sort_order', cardinality::SET, [':orders_id' => $orderId]);

		$order_shipping_method = NULL;
		foreach ($totals as $total) {
			$this->totals[] = array(
				'title'			=> $total['title'],
				'text'				=> $total['text'],
				'shipping_method_id' => $total['external_id'],
				'class'			=> $total['class'],
				'value'			=> $total['value'],
			);

			if ($total['class'] == 'ot_shipping') {
				if (strpos($total['title'], 'Federal') !== false) {
					$order_shipping_method = 'Federal';
				} elseif (strpos($total['title'], 'United') !== false) {
					$order_shipping_method = 'United';
				} else {
					$order_shipping_method = '';
				}
			}
		}

		$payment_method = prepared_query::fetch("SELECT label FROM payment_method WHERE `id` = :payment_method_id", cardinality::SINGLE, [':payment_method_id' => $order['payment_method_id']]);

		$this->info = array(
			'currency'			=> $order['currency'],
			'payment_method'		=> $payment_method,
			'date_purchased'		=> $order['date_purchased'],
			'payment_id'			=> $order['payment_id'],
			'orders_status'		=> $order['orders_status'],
			'orders_sub_status'	=> $order['orders_sub_status'],
			'customers_referer_url' => $order['customers_referer_url'],
			'purchase_order_number' => $order['purchase_order_number'],
			'dropship'			=> $order['dropship'],
			'net10_po'			=> $order['net10_po'],
			'net15_po'			=> $order['net15_po'],
			'net30_po'			=> $order['net30_po'],
			'net45_po'			=> $order['net45_po'],
			'amazon_order_number'			=> $order['amazon_order_number'],
			'packing_slip'		=> $order['packing_slip'],
			'last_modified'		=> $order['last_modified'],
			'order_notes'			=> !empty($order['order_notes'])?$order['order_notes']:NULL,
			'dsm'					=> $order['dsm'],
			'dpm'					=> $order['dpm'],
			'total_value'			=> !empty($this->totals['ot_total'])?round($this->totals['ot_total']['value'], 2):NULL,
			'order_shipping_method' => $order_shipping_method,
			'customers_ups'		=> $order['customers_ups'],
			'customers_fedex'		=> $order['customers_fedex'],
			'orders_weight'		=> $order['orders_weight'],
			'all_items_in_stock'	=> $order['all_items_in_stock'],
		);

		$customer_details_status = prepared_query::fetch('SELECT customers_fax, customers_notes, customers_notes_sales_rep, account_manager_id, aim_screenname, msn_screenname, company_account_contact_name, company_account_contact_email, company_account_contact_phone_number FROM customers WHERE customers_id = :customers_id', cardinality::ROW, [':customers_id' => $order['customers_id']]);

		$this->customer = array(
			'name'								=> $order['customers_name'],
			'extra_name'							=> $order['extra_name'],
			'id'									=> $order['customers_id'],
			'customer_extra_login_id'			=> $order['customers_extra_logins_id'],
			'extra_email_address'				=> $order['extra_email_address'],
			'copy_account'						=> $order['copy_account'],
			'company'							=> $order['customers_company'],
			'street_address'						=> $order['customers_street_address'],
			'suburb'								=> $order['customers_suburb'],
			'city'								=> $order['customers_city'],
			'postcode'							=> $order['customers_postcode'],
			'state'								=> $order['customers_state'],
			'country'							=> $order['customers_country'],
			'format_id'							=> $order['customers_address_format_id'],
			'telephone'							=> $order['customers_telephone'],
			'fax'								=> $customer_details_status['customers_fax'],
			'aim_screenname'						=> $customer_details_status['aim_screenname'],
			'msn_screenname'						=> $customer_details_status['msn_screenname'],
			'company_account_contact_name'		=> $customer_details_status['company_account_contact_name'],
			'company_account_contact_email'		=> $customer_details_status['company_account_contact_email'],
			'company_account_contact_phone_number' => $customer_details_status['company_account_contact_phone_number'],
			'account_manager_id'					=> $customer_details_status['account_manager_id'],
			'email_address'						=> $order['customers_email_address'],
			'notes'								=> $customer_details_status['customers_notes'],
			'notes_sales_rep'					=> $customer_details_status['customers_notes_sales_rep'],
		);

		$this->delivery = array(
			'name'			=> $order['delivery_name'],
			'company'		=> $order['delivery_company'],
			'street_address' => $order['delivery_street_address'],
			'suburb'		=> $order['delivery_suburb'],
			'city'			=> $order['delivery_city'],
			'postcode'		=> $order['delivery_postcode'],
			'state'		=> $order['delivery_state'],
			'country'		=> $order['delivery_country'],
			'telephone'	=> $order['delivery_telephone'],
			'format_id'	=> $order['delivery_address_format_id'],
		);

		$this->billing = array(
			'name'			=> $order['billing_name'],
			'company'		=> $order['billing_company'],
			'street_address' => $order['billing_street_address'],
			'suburb'		=> $order['billing_suburb'],
			'city'			=> $order['billing_city'],
			'postcode'		=> $order['billing_postcode'],
			'state'		=> $order['billing_state'],
			'country'		=> $order['billing_country'],
			'format_id'	=> $order['billing_address_format_id'],
		);

		$order_products = prepared_query::fetch('SELECT orders_products_id, products_name, products_model, products_tax, products_quantity, final_price, expected_ship_date, products_id, is_quote FROM orders_products WHERE orders_id = :orders_id', cardinality::SET, [':orders_id' => $orderId]);

		foreach ($order_products as $index => $order_product) {
			$this->products[$index] = array(
				'qty'				=> $order_product['products_quantity'],
				'name'				=> $order_product['products_name'],
				'model'			=> $order_product['products_model'],
				'tax'				=> $order_product['products_tax'],
				'price'			=> $order_product['final_price'],
				'final_price'		=> $order_product['final_price'],
				'id'				=> $order_product['products_id'],
				'orders_products_id' => $order_product['orders_products_id'],
				'expected_ship_date' => $order_product['expected_ship_date'],
				'is_quote'			=> $order_product['is_quote'],
			);
		}
	}
}
