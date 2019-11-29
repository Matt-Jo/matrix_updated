<?php
class ck_view_page_includer extends ck_view {

	protected $url = '/page_includer.php';

	protected $page_templates = [
		'page-includer' => 'page-page_includer.mustache.html',
	];

	public function process_response() {
		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		// there's nothing to do here

		echo json_encode(['errors' => []]);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$page_request = explode('/', $_GET['request']);

		do {
			$attempt = array_pop($page_request);
			if (empty($attempt)) continue;

			if (is_numeric($attempt)) {
				$page_includer = new ck_page_includer($attempt);
				$target = $page_includer->get_header();
			}
			else {
				$target = ck_page_includer::get_target_by_request($attempt);
			}
		}
		while (empty($target) && !empty($page_request));

		$data['page'] = $target['target'];
		$data['height'] = $target['page_height'];
		$data['responsive-height'] = $target['page_height']+200;

		// this section will be replaced by a proper backend
		/*switch (strtolower($_GET['request'])) {
			case 'serverquote':
			case 'serverquotes':
				$data['page'] = 'https://www2.cablesandkits.com/serverquotes/';
				$data['height'] = 1825;
				break;
		}*/

		$this->render($this->page_templates['page-includer'], $data);
		$this->flush();
	}
}
?>
