<?php
class ck_maintenance_view extends ck_view {

	protected $url = '/maintenance';

	protected $page_templates = [];

	public function process_response() {
		if (!ck::in_maintenance_window()) CK\fn::redirect_and_exit('/');
		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_POST['action'])) $this->psuedo_controller();
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

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action']) {
			default:
				$response['errors'] = ['The requested action ['.$_REQUEST['action'].'] was not recognized'];
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$maintenance_window = ck::get_maintenance_window();

		$data['maintenance_start'] = $maintenance_window['start']->format('g:i A T, l M j');
		$data['maintenance_end'] = $maintenance_window['end']->format('g:i A T, l M j');

		$this->render('page-maintenance.mustache.html', $data);
		$this->flush();
	}
}
?>
