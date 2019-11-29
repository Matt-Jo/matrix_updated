<?php
class ck_admin_category_redirects_view extends ck_view {

	protected $url = '/admin/category-redirects';

	protected $page_templates = [
	];

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_POST['action'])) $this->psuedo_controller();
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
		$response = [];

		switch ($_REQUEST['action']) {
			default:
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();
		$category_finalist = [];
		foreach (ck_listing_category::get_all() as $category) {
			if ($category->has_primary_container() && $category->get_primary_container()['container_id'] != $category->id()) {
				$category_finalist[] = $category;
			}
		}

		$data['redirected_categories'] = $category_finalist;

		$this->render('page-category-redirects.mustache.html', $data);
		$this->flush();
	}
}
?>
