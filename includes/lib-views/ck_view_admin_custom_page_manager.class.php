<?php
class ck_view_admin_custom_page_manager extends ck_view {

	protected $url = '/admin/custom-page-manager';
	protected $meta_title = 'Custom Page Manager';

	protected static $queries = [];

	public function get_meta_title() {
		return $this->meta_title;
	}

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
			case 'create':
				$data = $this->process_data($_POST);
				ck_custom_page::create($data);
				$page = '/admin/custom-page-manager';
				break;
			case 'archive':
				$data = $this->process_data($_POST);
				$page = new ck_custom_page($data['page_id']);
				$page->archive();
				$page = '/admin/custom-page-manager';
				break;
			case 'update':
				$data = $this->process_data($_POST);
				$page = new ck_custom_page($data['page_id']);
				$page->update($data);
				$page = '/admin/custom-page-manager';
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

		$__FLAG = $GLOBALS['__FLAG'];

		switch ($_REQUEST['action']) {
			case 'get-page-info':
				$response = self::query_fetch('SELECT page_id, page_title, page_code, product_id_list, sitewide_header, full_width, url, url_identifier, meta_description, visibility FROM custom_pages WHERE page_id = :page_id', cardinality::ROW, [':page_id' => $_REQUEST['page_id']]);
				break;
			default:
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$__FLAG = request_flags::instance();

		$data = $this->data();

		$custom_pages = self::query_fetch('SELECT page_id, page_title, page_code, product_id_list, sitewide_header, full_width, url, meta_description, visibility, url_identifier FROM custom_pages WHERE archived = 0', cardinality::SET, []);

		for ($i = 0; $i < count($custom_pages); $i ++) {
			$data['custom_pages'][$i] = [
				'page_id' => $custom_pages[$i]['page_id'],
				'title' => $custom_pages[$i]['page_title'],
				'code' => $custom_pages[$i]['page_code'],
				'product_id_list' => $custom_pages[$i]['product_id_list'],
				'visible?' => $custom_pages[$i]['visibility'],
				'sitewide_header?' => $custom_pages[$i]['sitewide_header'],
				'url' => $custom_pages[$i]['url'],
				'url_identifier' => $custom_pages[$i]['url_identifier'],
				'meta_description' => $custom_pages[$i]['meta_description'],
				'full_width?' => $custom_pages[$i]['full_width']
			];
		}

		$this->render('page-custom-page-manager.mustache.html', $data);
		$this->flush();
	}

	/*********
	 * Custom Methods
	 */

	private function process_data($post) {
		isset($_POST['sitewide_header'])?$sitewide_header=$_POST['sitewide_header']:$sitewide_header=FALSE;
		isset($_POST['visibility'])?$visibility=$_POST['visibility']:$visibility=FALSE;
		return $data = [
			'page_id' => $_POST['page_id'],
			'page_title' => $_POST['page_title'],
			'page_code' => $_POST['page_code'],
			'product_id_list' => $_POST['product_id_list'],
			'sitewide_header' => CK\fn::check_flag($sitewide_header)?1:0,
			'url' => $_POST['url'],
			'url_identifier' => $_POST['url_identifier'],
			'meta_description' => $_POST['meta_description'],
			'visibility' => CK\fn::check_flag($visibility)?1:0
		];
	}
}
?>
