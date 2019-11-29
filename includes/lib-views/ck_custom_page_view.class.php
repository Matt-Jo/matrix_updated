<?php
class ck_custom_page_view extends ck_view {

	private $page;
	protected $errors = [];

	public function __construct($context=NULL) {
		$this->init_page();
		parent::__construct($context);
	}

	public function page_title() {
		return $this->page->get_header('page_title');
	}

	public function page_meta_description () {
		return $this->page->get_header('meta_description');
	}

	private function init_page() {
		if (!empty($this->page)) return;

		$uri = parse_url($_SERVER['REQUEST_URI']);

		preg_match('/(\/.*?\/)/', $uri['path'], $url_identifier);
		preg_match('/\/(.*)/', ltrim($uri['path'], '/'), $url);

		$url_identifier = $url_identifier[0];
		$url = ltrim($url[0], '/');

		$page_id = ck_custom_page::get_id_by_url($url, $url_identifier);

		if ($page_id) $this->page = new ck_custom_page($page_id);
		else CK\fn::redirect_and_exit('/');
	}

	public function process_response() {
		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
		$this->template_set = self::TPL_FULLWIDTH_FRONTEND;

	}

	private function psuedo_controller() {
		$page = NULL;

		$__FLAG = request_flags::instance();

		switch ($_REQUEST['action']) { }

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		$__FLAG = request_flags::instance();

		$response = [];

		switch ($_REQUEST['action']) {}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();
		$data['page_code'] = $this->page->get_header('page_code');

		$product_template = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
		$product_template->buffer = TRUE;
		$content = new ck_content;

		if (!empty($this->page->get_header('product_id_list'))) {
			$product_ids = preg_split('/\s*,\s*/', $this->page->get_header('product_id_list'));
			foreach ($product_ids as $product_id) {
				$product = new ck_product_listing($product_id);
				if (!$product->is_viewable()) continue;
				$template = $product->get_thin_template();
				$content->products[] = $template;
				$key = 'prod-'.$product->id();
				$content->$key = $template;
			}
			$data['page_code'] = $product_template->simple_content($this->page->get_header('page_code'), $content);
		}

		$data['head']['title'] = $this->page->get_header('page_title');
		$data['meta_description'] = $this->page->get_header('meta_description');
		$data['page_title'] = $this->page->get_header('page_title');

		$this->render('page-custom-page.mustache.html', $data);
		$this->flush();
	}
}
?>
