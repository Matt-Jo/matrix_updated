<?php
class ck_customer_account_orders_view extends ck_view {

	protected $url = '/my-account/orders';
	protected $errors = [];

	public $secondary_partials = TRUE;

	public function page_title() {
		return 'Orders';
	}

	public function process_response() {
		if (!$_SESSION['cart']->is_logged_in()) CK\fn::redirect_and_exit('/login.php');
		$this->template_set = self::TPL_FULLWIDTH_FRONTEND_CUSTOMER_ACCOUNT;

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		$__FLAG = request_flags::instance();

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
		if (!empty($results)) echo json_encode($results);
		else echo json_encode(['success' => false]);
		exit();
	}

	private function http_response() {
		$data = $this->data();
		$customer = new ck_customer2($_SESSION['customer_id']);
		$data['total_orders'] = $customer->get_order_count();
		$page_size = 12;

		if ($customer->has_orders()) {
			$orders = ck_sales_order::get_orders_by_customer($customer->id());
			$page = !empty($_GET['page'])?$_GET['page']:1;
			if ($data['total_orders'] > $page_size) {
				$data['page_size'] = $page_size;
				$data['paginate'][] = 1;
				if ($page < ceil($data['total_orders']/$page_size)) $data['paginate']['next_page'] = $page + 1;
				if ($page > 1) $data['paginate']['previous_page'] = $page - 1;
				$data['pagination_status'] = 1 + (($page - 1) * $data['page_size']).' to '.$page * $data['page_size'].'(of '.$data['total_orders'];
				$data['paginate']['page_numbers'] = [];
				for ($i=1; $i<=ceil($data['total_orders']/$page_size); $i++) {
					$data['paginate']['page_numbers'][] = $i;
				}
			}

			for ($i = ($page - 1) * $page_size; $i < (($page - 1) * $page_size + $page_size) && $i < $data['total_orders']; $i++) {
				$page_orders[$i] = [
					'order_id' => $orders[$i]->id(),
					'date_purchased' => $orders[$i]->get_header('date_purchased')->format('Y-m-d'),
					'orders_status' => $orders[$i]->get_header('orders_status_name'),
					'order_total' => CK\text::monetize($orders[$i]->get_simple_totals('total')),
					'order_tax' => CK\text::monetize($orders[$i]->get_simple_totals('tax')),
					'shipping_total' => CK\text::monetize($orders[$i]->get_simple_totals('shipping')),
					'po_number' => $orders[$i]->get_header('purchase_order_number'),
					'shipped_to' => $orders[$i]->get_header('billing_name')
				];

				if (!empty($orders[$i]->get_header('customers_extra_logins_id'))) {
					$page_orders[$i]['ordered_by'] = $orders[$i]->get_header('extra_logins_firstname').' '.$orders[$i]->get_header('extra_logins_lastname');
				}
				else $page_orders[$i]['ordered_by'] = $orders[$i]->get_header('customers_name');

				foreach ($orders[$i]->get_products() as $product) {
					$page_orders[$i]['order_products'][] = [
						'product_image' => $product['listing']->get_image('products_image'),
						'url' => $product['listing']->get_url(),
						'model_number' => $product['model'],
						'quantity' => $product['quantity'],
						'price' => CK\text::monetize($product['final_price'])
					];
				}
			}
			$data['page_orders'] = array_values($page_orders);
		}

		$this->render('page-customer-account-orders.mustache.html', $data);
		$this->flush();
	}
}
?>