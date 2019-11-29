<?php
class ck_admin_ghost_invoices_view extends ck_view {

	protected $url = '/admin/ghost-invoices';

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
			case 'delete-invoice':
				$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);
				if (!$user->is_top_admin()) return FALSE;

				$invoice = new ck_invoice($_POST['invoice_id']);
				$invoice->delete_ghost();

				$page = '/admin/ghost-invoices';
				break;
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

		$data['invoices'] = prepared_query::fetch('SELECT ai.invoice_id, ai.inv_date AS date, CONCAT(c.customers_firstname, \' \', c.customers_lastname) AS name, c.customers_email_address AS email FROM acc_invoices ai LEFT JOIN customers c ON ai.customer_id = c.customers_id WHERE ai.inv_order_id IS NULL AND ai.rma_id IS NULL AND ai.paid_in_full = 0', cardinality::SET, []);

		$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);
		if ($user->is_top_admin()) $data['top_admin'] = 1;

		$this->render('page-ghost-invoices.mustache.html', $data);
		$this->flush();
	}
}
?>
