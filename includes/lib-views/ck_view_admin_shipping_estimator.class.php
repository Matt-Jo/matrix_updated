<?php
class ck_view_admin_shipping_estimator extends ck_view {

	protected $url = '/customer-quote.php';

	protected $page_templates = [
		'shipping_estimator' => 'partial-shipping_estimator.mustache.html',
	];

	private $orders_id;

	public function process_response() {
		$this->template_set = self::TPL_SLIM;

		$this->orders_id = $_GET['oid'];

		if (!$this->orders_id) die();
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		//elseif (!empty($_POST['action'])) $this->psuedo_controller();
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
	}

	private function http_response() {
		$data = $this->data();

		$order = $GLOBALS['se_order'] = new ck_sales_order($this->orders_id);

		$data['total_weight'] = shipit::$box_tare_weight;
		$data['total_count'] = 0;

		$products = $order->get_products();

		$product_weight = 0;

		foreach ($products as $product) {
			$data['total_count'] += $product['quantity'];
			$product_weight += $product['ipn']->get_total_weight() * $product['quantity'];
		}

		$data['total_weight'] += $product_weight;

		$shipping_address = $order->get_shipping_address();

		$country_info = ck_address2::get_country($shipping_address['country']);

		$address_data = [
			'address1' => $shipping_address['street_address_1'],
			'address2' => $shipping_address['street_address_2'],
			'postcode' => $shipping_address['zip'],
			'city' => $shipping_address['city'],
			'state' => $shipping_address['state'],
			'countries_id' => $country_info['countries_id'],
			'country' => $shipping_address['country'],
			'countries_iso_code_2' => $country_info['countries_iso_code_2'],
			'countries_iso_code_3' => $country_info['countries_iso_code_3'],
			'country_address_format_id' => $shipping_address['address_format_id'],
		];

		$data['estimator_postcode'] = $shipping_address['zip'];

		$address_type = new ck_address_type();
		$address_type->load('header', $address_data);

		$address = new ck_address2(NULL, $address_type);

		$addresses = $order->get_customer()->get_addresses();

		$data['address_list?'] = 1;
		$address_list = [];

		foreach ($addresses as $address_entry) {
			$option = $address_entry->get_address_line_template(['city', 'postcode', 'state', 'country'], ' ');
			$option['address_book_id'] = $address_entry->get_header('address_book_id');

			if ($address_entry->get_header('address_book_id') == $address->get_header('address_book_id')) $option['selected?'] = 1;

			$address_list[] = $option;
		}

		$data['addresses'] = $address_list;

		$__FLAG = request_flags::instance();

		$data['show_estimates?'] = 1;

		$data['formatted_address'] = $address->get_address_line_template(NULL, '<br>');
		if ($address->get_header('countries_iso_code_2') != 'US') $data['intl?'] = 1;

		$data['rate_groups'] = ck_sales_order::get_ship_rate_quotes($address, $product_weight);

		$data['admin?'] = 1;
		$data['admin?-cost-only'] = 1;

		// finish the page
		$this->render($this->page_templates['shipping_estimator'], $data);
		$this->flush();
	}
}
?>
