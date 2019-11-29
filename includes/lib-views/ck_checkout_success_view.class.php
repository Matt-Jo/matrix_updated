<?php
class ck_checkout_success_view extends ck_view {

	protected $url = 'checkout_success.php';

	protected $page_templates = [
	];

	protected $errors = [];

	public function process_response() {
		if (!$_SESSION['cart']->is_logged_in()) {
			$_SESSION['navigation']->set_snapshot(['page' => $this->url, 'get' => $_GET, 'post' => $_POST]);
			CK\fn::redirect_and_exit('/login.php');
		}

		if (empty($_GET['order_id'])) {
			$_GET['order_id'] = prepared_query::fetch('SELECT orders_id FROM orders WHERE customers_id = :customers_id ORDER BY date_purchased DESC LIMIT 1', cardinality::SINGLE, [':customers_id' => $_SESSION['customer_id']]);
		}

		if (!empty($_SESSION['log_out_as_user'])) {
			unset($_SESSION['log_out_as_user']);
			// copied without pretense from /logoff.php
			unset($_SESSION['previous_page']);
			unset($_SESSION['customer_id']);
			unset($_SESSION['customer_default_address_id']);
			unset($_SESSION['customer_first_name']);
			unset($_SESSION['customer_country_id']);
			unset($_SESSION['customer_zone_id']);
			unset($_SESSION['admin']);
			unset($_SESSION['comments']);
			unset($_SESSION['gv_id']);
			unset($_SESSION['cc_id']);
			$_SESSION['cart']->reset_cart();
			service_locator::get_session_service()->destroy();
		}

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	public function view_context() {
		return 'checkout_success';
	}

	private function psuedo_controller() {
		$page = NULL;

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action']) {
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
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$__FLAG = request_flags::instance();

		$data = $this->data();

		if (!empty($this->errors)) {
			$errors = $this->errors;
			$data['error'] = implode('<br>', $errors);
			$this->errors = [];
		}

		if (CK\fn::check_flag(@$_SESSION['admin_as_user'])) $data['admin?'] = 1;

		$GLOBALS['breadcrumb']->add('Checkout', '/shopping_cart.php');
		$GLOBALS['breadcrumb']->add('Success', '/checkout_success.php');

		$data['breadcrumbs'] = $GLOBALS['breadcrumb']->trail();

		$order = new ck_sales_order($_GET['order_id']);

		$data['contact_phone'] = $order->get_contact_phone();
		$data['contact_email'] = $order->get_contact_email();

		$data['orders_id'] = $order->id();

		foreach ($order->get_simple_totals() as $class => $total) {
			if (empty($total)) continue;
			if (!in_array($class, ['coupon', 'shipping', 'tax', 'total'])) continue;

			if (!isset($data[$class.'_total'])) $data[$class.'_total'] = 0;
			$data[$class.'_total'] += $total;
			$data[$class.'_total'] = number_format($data[$class.'_total'], 2, '.', '');
		}
		$data['cents_total'] = number_format($data['total_total'] * 100, 0, '.', '');

		$data['customer_email'] = $order->get_prime_contact('email');
		$data['customer_firstname'] = $order->get_prime_contact('firstname');
		$data['customer_lastname'] = $order->get_prime_contact('lastname');
		$data['country_code'] = ck_address2::get_country($order->get_shipping_address('country'))['countries_iso_code_2'];

		$expected_ship_time = new DateTime();
		//check if it's past 8 at night
		if ($expected_ship_time->format('G') >= 20) $expected_ship_time->add(new DateInterval('P1D'));

		$backorder = 'N';
		$lead_time = 0;
		//we are decreasing quantity - tell channel advisor
		$ca = new api_channel_advisor;
		foreach ($order->get_products() as $product) {
			if ($ca::is_authorized()) {
				$ca->update_quantity($product['ipn']);
			}

			if ($product['ipn']->get_inventory('available') >= 0) continue;

			$backorder = 'Y';
			$lead_time = max($lead_time, $product['ipn']->get_header('lead_time'));
		}

		// set expected ship time to latest lead time from vendor if we need to source it
		if ($lead_time > 0) $expected_ship_time->add(new DateInterval('P'.$lead_time.'D'));

		//now check to make sure it's not shipping on saturday
		if ($expected_ship_time->format('D') == 'Sat') $expected_ship_time->add(new DateInterval('P2D'));
		//now check to make sure it's not Sunday
		elseif ($expected_ship_time->format('D') == 'Sun') $expected_ship_time->add(new DateInterval('P1D'));

		//now we calculate the expected delivery date
		$expected_delivery_time = clone $expected_ship_time;
		$expected_delivery_time->add(new DateInterval('P'.($order->get_shipping_method('transit_days')+2).'D'));

		//now check to make sure it's not shipping on saturday
		if ($expected_delivery_time->format('D') == 'Sat') $expected_delivery_time->add(new DateInterval('P2D'));
		//now check to make sure it's not Sunday
		elseif ($expected_delivery_time->format('D') == 'Sun') $expected_delivery_time->add(new DateInterval('P1D'));

		$data['estimated_ship_date'] = $expected_ship_time->format('Y-m-d');

		$item_total = 0;
		$data['items'] = [];
		foreach ($order->get_products() as $product) {
			$item_total += $product['final_price'] * $product['quantity'];
			$data['items'][] = $product;
		}
		$data['item_total'] = number_format($item_total, 2, '.', '');

		if (!empty($_SESSION['view_order_in_admin'])) {
			$data['view_order_in_admin'] = 1;
			$data['private_domain'] = PRIVATE_FQDN;
			$_SESSION['view_order_in_admin'] = NULL;
			unset($_SESSION['view_order_in_admin']);
		}

		$this->render('page-checkout-success.mustache.html', $data);
		$this->flush();

		try {
			$customer = $order->get_customer();
			$hubspot = new api_hubspot;
			$hubspot->update_company($customer);
			//$hubspot->update_transaction($sales_order);
		}
		catch (Exception $e) {
			// fail silently
		}
	}
}
?>
