<?php
class ck_admin_orders_view extends ck_view {

	protected $url = '/admin/orders';

	protected $page_templates = [];

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
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
			default:
				$response['err'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$tab = isset($_GET['tab']) ? $_GET['tab'] : 'ready-to-pick';
		
		if ($tab == 'ready-to-pick') {
			$data['rtp'] = 1;
			$order_ids = prepared_query::fetch('SELECT orders_id FROM orders WHERE orders_status IN (2)', cardinality::COLUMN, []);
			$data['total_orders'] = count($order_ids);
		}
		elseif ($tab == 'shipping') {
			$data['shipping'] = 1;
			$order_ids = prepared_query::fetch('SELECT orders_id FROM orders WHERE orders_status IN (7) AND orders_sub_status IN (24, 26)', cardinality::COLUMN, []);
		}
		elseif ($tab == 'warehouse') {
			$data['warehouse'] = 1;
			$order_ids = prepared_query::fetch('SELECT orders_id FROM orders WHERE orders_status IN (11) AND orders_sub_status IN (11, 15)', cardinality::COLUMN, []);
		}
		elseif ($tab == 'shipped') {
			$data['shipped'] = 1;
			$order_ids = prepared_query::fetch('SELECT DISTINCT o.orders_id FROM orders o JOIN orders_status_history osh ON o.orders_id = osh.orders_id AND o.orders_status = osh.orders_status_id WHERE o.orders_status IN (3) AND DATE(osh.date_added) = :date', cardinality::COLUMN, [':date' => (new DateTime('now'))->format('y-m-d h:m:s')]);
		}

        $keys = [];
		foreach ($order_ids as $order_id) {
			$order = new ck_sales_order($order_id);

			$shipping_method_id = $order->get_shipping_method('shipping_code');
			$order_status = $order->get_header('orders_status_name');
			$order_sub_status = $order->get_header('orders_sub_status_name');
			$shipping_method = $order->get_shipping_method_display('short');

			$order_details = [
				'order_id' => $order->id(),
				'customer_name' => $order->get_customer()->get_name(),
				'customer_company' => $order->get_customer()->get_company_name(),
				// 'order_total' => CK\text::monetize($order->get_simple_totals('total')),
				// 'estimated_margin' => $order->get_estimated_margin_dollars(),
				'po_number' => $order->get_ref_po_number(),
				'orders' => $order->get_customer()->get_order_count(),
				// 'account_rep' => $order->get_account_manager(),
				// 'sales_team' => $order->get_sales_team()->get_header('sales_team'),
				'ship_method' => $shipping_method,
				'weight' => $order->get_estimated_shipped_weight(),
				// 'payment_method' => $order->get_header('payment_method_label'),
				// 'date_purchased' => $order->get_header('date_purchased')->format('M d, Y')
			];

			$current_key = '';
			$order_details['special_handling'] = 0;

			if ($order_sub_status == 'Special Handling') {
				$current_key = $order_sub_status;
				$order_details['special_handling'] = 1;
			}
			elseif ($tab == 'ready-to-pick') $current_key = $shipping_method;
			else $current_key = $order_status . ' // ' . $order_sub_status;

			if (!isset($order_group[$current_key])) {
				$keys[] = $current_key;
				$order_group[$current_key] = [
					'title' => $current_key,
					'orders' => []
				];
			}
			$order_group[$current_key]['orders'][] = $order_details;
		}

		usort($keys, function ($a, $b) use ($position) {
			if ($a == 'Customer Pickup' || $b == 'Customer Pickup') return 1;
			else if ($a == 'Special Handling' || $b == 'Special Handling') return -1;
			else return 0;
		});

		foreach ($keys as $key) $data['order_group'][] = $order_group[$key];

		$this->render('page-orders.mustache.html', $data);
		$this->flush();
	}
}
?>
