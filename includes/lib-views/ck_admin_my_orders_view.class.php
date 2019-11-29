<?php
class ck_admin_my_orders_view extends ck_view {

	protected $url = '/admin/my-orders';

	protected $page_templates = [];

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		if (!empty($_REQUEST['stage'])) $this->current_stage = $_REQUEST['stage'];

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

		$order_ids = prepared_query::fetch("SELECT o.orders_id FROM orders o WHERE (o.orders_sales_rep_id = :admin_id OR o.admin_id = :admin_id) AND o.orders_status NOT IN (3, 6, 9)", cardinality::COLUMN, [':admin_id' => $_SESSION['login_id']]);

		foreach ($order_ids as $id) {
			$sales_order = new ck_sales_order($id);

			$order = [
				'orders_id' => $sales_order->id(),
				'orders_status_name' => $sales_order->get_header('orders_status_name'),
				'customers_company' => $sales_order->get_header('customers_company'),
				'customers_name' => $sales_order->get_header('customers_name'),
				'payment_method' => $sales_order->get_header('payment_method_label'),
				'dropship' => $sales_order->get_header('dropship') == 1 ? 'Yes' : 'No',
				'total' => CK\text::monetize(floatval($sales_order->get_simple_totals('total'))),
				'shipping_method' => $sales_order->get_shipping_method('method_name'),
				'weight' => $sales_order->get_estimated_shipped_weight(),
				'customer_total_orders' => $sales_order->get_customer()->get_order_count(),
				'date_purchased' => $sales_order->get_header('date_purchased')->format('Y-m-d')
			];

			$order['estimated_margin_dollars'] = 'Incalculable';
			if (is_numeric($sales_order->get_estimated_margin_dollars('product', TRUE))) {
				$order['estimated_margin_dollars'] = CK\text::monetize($sales_order->get_estimated_margin_dollars('product', TRUE));
				$order['estimated_margin_percentage'] = $sales_order->get_estimated_margin_pct('product', TRUE);
			}

			$data['orders'][] = $order;
		}

		$this->render('page-my-orders.mustache.html', $data);
		$this->flush();
	}
}
?>
