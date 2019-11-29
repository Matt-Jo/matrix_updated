<?php
class ck_customer_account_view extends ck_view {

	protected $url = '/my-account';
	protected $errors = [];

	public $secondary_partials = TRUE;

	public function page_title() {
		return 'My Account';
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

		if ($customer->has_orders()) {
			$orders = ck_sales_order::get_orders_by_customer($customer->id());
			for ($i = 0; $i < 10 && $i < count($orders); $i++) {
				$data['orders'][$i] = [
					'order_id' => $orders[$i]->id(),
					'date_purchased' => $orders[$i]->get_header('date_purchased')->format('Y-m-d'),
					'orders_status' => $orders[$i]->get_header('orders_status_name'),
					'order_total' => CK\text::monetize($orders[$i]->get_simple_totals('total')),
					'order_tax' => CK\text::monetize($orders[$i]->get_simple_totals('tax')),
					'shipping_total' => CK\text::monetize($orders[$i]->get_simple_totals('shipping')),
					'po_number' => $orders[$i]->get_header('purchase_order_number')
				];
				foreach ($orders[$i]->get_products() as $product) {
					if ($product['ipn']->get_header('products_stock_control_category_id') == 90 || $product['ipn']->get_header('products_stock_control_vertical_id') == 13) continue;
					$data['orders'][$i]['order_products'][] = [
						'product_image' => $product['listing']->get_image('products_image'),
						'url' => $product['listing']->get_url(),
						'model_number' => $product['model'],
						'quantity' => $product['quantity'],
						'price' => CK\text::monetize($product['final_price'])
					];
				}
			}
		}

		$this->render('page-customer-account.mustache.html', $data);
		$this->flush();
	}
}
?>