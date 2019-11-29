<?php
abstract class ck_page_controller extends ck_master_archetype {

	protected $breadcrumbs;
	protected $url;
	protected $request;
	protected $templates = [];

	protected $page_details;

	protected $domain;
	protected $cdn = '//media.cablesandkits.com';
	protected $static_files = '//media.cablesandkits.com/static';

	protected $site_templates_folder;
	protected $page_templates_folder;
	protected $element_templates_folder;

	private $template_engine;

	const CONTEXT_HTTP = 'http';
	const CONTEXT_AJAX = 'ajax';

	protected $response_context;

	public function __construct() {

		$this->site_templates_folder = realpath(__DIR__.'/../templates');
		$this->page_templates_folder = realpath(__DIR__.'/../templates');
		$this->element_templates_folder = realpath(__DIR__.'/../templates');

		$this->domain = $_SERVER['HTTP_HOST'];
		$this->set_response_context();
	}

	protected function set_response_context() {
		if (CK\fn::check_flag(@$_REQUEST['ajax'])) $this->response_context = self::CONTEXT_AJAX;
		else $this->response_context = self::CONTEXT_HTTP;
	}

	protected function new_block() {
		$content_map = new ck_content();
		$content_map->cdn = $this->cdn;
		$content_map->static_files = $this->static_files;

		return $content_map;
	}

	abstract public function control(ck_page_details $page_details);
	abstract public function respond();
	abstract public function display($script_details);

	protected function buffer_start() {
		self::get_tpl()->buffer = TRUE;
	}

	protected function buffer_end() {
		self::get_tpl()->buffer = FALSE;
	}

	protected function run_templates($templates, $content_map) {
		return self::get_tpl()->content($templates, $content_map);
	}

	// wrangle the page template, if we want to set it explicitly for the call or the class (otherwise, fall back to the global instance)
	protected static $tpl = NULL;
	public static function set_tpl($tpl) {
		static::$tpl = $tpl;
	}
	// this allows us to use dependancy injection without requiring it
	protected static function get_tpl($tpl=NULL) {
		!$tpl?(!empty(self::$tpl)?$tpl=self::$tpl:$tpl=@$GLOBALS['cktpl']):NULL;
		return $tpl;
	}
}
?>
