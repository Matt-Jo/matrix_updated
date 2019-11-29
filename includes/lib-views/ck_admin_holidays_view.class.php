<?php
class ck_admin_holidays_view extends ck_view {

	protected $url = '/admin/holidays';

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

		$today = new DateTime('now');

		$holidays = prepared_query::fetch('SELECT date_string, title FROM ck_holidays');

		foreach ($holidays as $holiday) {
			$holiday_date = new DateTime($holiday['date_string']);
			$data['holidays'][] = ['title' => $holiday['title'], 'date' => $holiday_date->format('M D, Y')];
		}

		$this->render('page-holidays.mustache.html', $data);
		$this->flush();
	}
}
?>
