<?php
class ck_admin_ipn_creation_review_dashboard_view extends ck_view {

	protected $url = '/admin/ipn-creation-review-dashboard';

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
			case 'mark_creation_reviewed':
				$ipn = new ck_ipn2($_REQUEST['stock_id']);
				$ipn->mark_creation_reviewed();
				$response = TRUE;
				break;
			default:
				$response['err'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$stock_ids = prepared_query::fetch('SELECT stock_id FROM products_stock_control WHERE creation_reviewed = 0', cardinality::COLUMN, []);

		foreach ($stock_ids as $stock_id) {
			$ipn = new ck_ipn2($stock_id);

			$creator = $ipn->get_header('creator');
			if ($creator instanceof ck_admin) $creator = $creator->get_header('first_name').' '.$creator->get_header('last_name');

			$data['creation_reviews'][] = [
				'ipn' => $ipn->get_header('ipn'),
				'stock_id' => $ipn->id(),
				'creation_date' => $ipn->get_header('date_added')->format('y-m-d'),
				'creator' => $creator
			];
		}


		$this->render('page-ipn-creation-review-dashboard.mustache.html', $data);
		$this->flush();
	}
}
?>
