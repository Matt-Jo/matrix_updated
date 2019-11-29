<?php
// I'm working the separate template builder into this view class - I may need to make some stuff public that isn't yet
abstract class ck_view extends ck_master_archetype {

	public $m; // mustache template instance
	private $auto_flush = TRUE;
	private $buffer = '';

	const CONTEXT_HTTP = 'http';
	const CONTEXT_AJAX = 'ajax';

	protected $response_context;

	const TPL_NONE = 0;
	const TPL_FRONTEND = 1;
	const TPL_BACKEND = 2;
	const TPL_SLIM = 3;
	const TPL_EMAIL = 4;
	const TPL_XML = 5;
	const TPL_FULLWIDTH_FRONTEND = 6;
	const TPL_FULLWIDTH_FRONTEND_CUSTOMER_ACCOUNT = 7;

	protected $tpl_opened = FALSE;
	protected $tpl_closed = FALSE;

	private $default_path = '../templates';
	private $managed_path = '../templates/managed';
	private $tpl_sets = [
		self::TPL_NONE => [
			'path' => '',
			'open' => [],
			'close' => []
		],
		self::TPL_FRONTEND => [
			'path' => '../templates',
			'open' => [
				'tpl-document.head.mustache.html',
				'tpl-page.head.mustache.html',
				'tpl-content.head.mustache.html',
				'tpl-content.leftblock.mustache.html',
			],
			'close' => [
				'tpl-content.foot.mustache.html',
				'tpl-page.foot.mustache.html',
				'tpl-document.foot.mustache.html',
			],
		],
		self::TPL_BACKEND => [
			'path' => '../../admin/includes/templates',
			'open' => [
				'tpl-admin-document.head.mustache.html',
				'tpl-admin-page.head.mustache.html',
				'tpl-admin-content.head.mustache.html',
				'tpl-admin-content.leftblock.mustache.html',
			],
			'close' => [
				'tpl-admin-content.foot.mustache.html',
				'tpl-admin-page.foot.mustache.html',
				'tpl-admin-document.foot.mustache.html',
			],
		],
		self::TPL_SLIM => [
			'path' => '../templates',
			'open' => [
				'tpl-slim-document.head.mustache.html',
				'tpl-slim-page.head.mustache.html',
				'tpl-slim-content.head.mustache.html',
				'tpl-slim-content.leftblock.mustache.html',
			],
			'close' => [
				'tpl-slim-content.foot.mustache.html',
				'tpl-slim-page.foot.mustache.html',
				'tpl-slim-document.foot.mustache.html',
			],
		],
		self::TPL_EMAIL => [
			'path' => '../templates',
			'open' => [
				'tpl-email-document.head.mustache.html',
				'tpl-email-page.head.mustache.html',
				'tpl-email-content.head.mustache.html',
				'tpl-email-content.leftblock.mustache.html',
			],
			'close' => [
				'tpl-email-content.foot.mustache.html',
				'tpl-email-page.foot.mustache.html',
				'tpl-email-document.foot.mustache.html',
			],
		],
		self::TPL_XML => [
			'path' => '',
			'open' => ['<?xml version="1.0" encoding="utf-8"?>'],
			'close' => []
		],
		self::TPL_FULLWIDTH_FRONTEND => [
			'path' => '../templates',
			'open' => [
				'tpl-document.head.mustache.html',
				'tpl-page.head.mustache.html',
				'tpl-content.head-fullwidth.mustache.html',
				'tpl-content.leftblock.mustache.html',
			],
			'close' => [
				'tpl-content.foot-fullwidth.mustache.html',
				'tpl-page.foot.mustache.html',
				'tpl-document.foot.mustache.html',
			]
		],
		self::TPL_FULLWIDTH_FRONTEND_CUSTOMER_ACCOUNT => [
			'path' => '../templates',
			'open' => [
				'tpl-document.head.mustache.html',
				'tpl-page.head.mustache.html',
				'tpl-content.head-fullwidth.mustache.html',
				'tpl-customer-account-additional-head.mustache.html',
				'tpl-content.leftblock.mustache.html',
			],
			'close' => [
				'tpl-content.foot-fullwidth.mustache.html',
				'tpl-customer-account-additional-foot.mustache.html',
				'tpl-page.foot.mustache.html',
				'tpl-document.foot.mustache.html',
			]
		]
	];

	private $dynamic_partials = [];

	protected $template_set = self::TPL_NONE;
	//protected $page_templates = [];

	public function __construct($context=NULL) {
		$this->set_response_context($context);
		if ($_SESSION['current_context'] === 'frontend') $this->template_set = self::TPL_FRONTEND;
	}

	// this is intended to be overridden by each extending view, but fall back to this; page tab title
	public function get_meta_title() { return NULL; }

	protected function set_response_context($context=NULL) {
		$__FLAG = request_flags::instance();

		if (!empty($context)) $this->response_context = $context;
		elseif ($__FLAG['ajax']) $this->response_context = self::CONTEXT_AJAX;
		else $this->response_context = self::CONTEXT_HTTP;
	}

	public function response_context() {
		return $this->response_context;
	}

	public function response_context_is($context) {
		return $this->response_context === $context;
	}

	public function __destruct() {
		//$this->close();
		$this->flush();
	}

	public function add_dynamic_partials($templates) {
		foreach ($templates as $name => $template) {
			$this->dynamic_partials[$name] = $template;
		}
	}

	protected function init($paths=[]) {
		if (!empty($this->tpl_sets[$this->template_set]['path'])) $paths[] = __DIR__.'/'.$this->tpl_sets[$this->template_set]['path'];
		if (empty($paths)) $paths[] = realpath(__DIR__.'/'.$this->default_path);
		$paths[] = realpath(__DIR__.'/'.$this->managed_path);
		$paths = array_unique($paths);

		// we may want to define other options, but for now we're cool with the default
		$opts = ['cache' => realpath(__DIR__.'/../templates/cache')];

		$loaders = [];
		if (!empty($this->dynamic_partials)) $partials_loaders = [];
		foreach ($paths as $path) {
			$loaders[] = new Mustache_Loader_FilesystemLoader($path, ['extension' => '']); // specify the extension manually
			if (!empty($this->dynamic_partials)) $partials_loaders[] = new mustache_filesystem_alias_loader($path, ['extension' => ''], $this->dynamic_partials);
		}
		if (!empty($loaders)) $opts['loader'] = new Mustache_Loader_CascadingLoader($loaders);
		if (!empty($partials_loaders)) $opts['partials_loader'] = new Mustache_Loader_CascadingLoader($partials_loaders);

		$this->m = new Mustache_Engine($opts);
	}

	abstract public function process_response();
	abstract public function respond();

	public function toggle_auto_flush($status=NULL) {
		if (is_null($status)) $this->auto_flush = !$this->auto_flush;
		elseif (empty($status)) $this->auto_flush = FALSE;
		else $this->auto_flush = TRUE;
	}

	public function set_open() {
		$this->tpl_opened = TRUE;
	}

	public function open($data=[]) {
		if ($this->tpl_opened) return;
		$this->tpl_opened = TRUE;
		if (!empty($this->tpl_sets[$this->template_set]['open'])) $this->render($this->tpl_sets[$this->template_set]['open'], $data);
	}

	public function close($data=[]) {
		if ($this->tpl_closed) return;
		$this->tpl_closed = TRUE;
		if (!empty($this->tpl_sets[$this->template_set]['close'])) $this->render($this->tpl_sets[$this->template_set]['close'], $data);
	}

	public function render($templates=[], $data=[]) {
		if (empty($this->m)) $this->init();

		if (!$this->tpl_opened) $this->open($data);

		if (is_scalar($templates)) $templates = [$templates];
		foreach ($templates as $template) {
			if (!preg_match('/\.mustache\./', $template)) {
				$loader = $this->m->getLoader();
				$this->m->setLoader(new Mustache_Loader_StringLoader);
				$tpl = $this->m->loadTemplate($template);
				$this->m->setLoader($loader);
			}
			else $tpl = $this->m->loadTemplate($template);
			$this->buffer .= $tpl->render($data);
		}
		if ($this->auto_flush) $this->flush();
	}

	public function flush() {
		echo $this->buffer;
		flush();
		$this->buffer = '';
	}

	protected function data() {
		$data = [];
		$data['domain'] = '//'.FQDN;
		$data['cdn'] = '//media.cablesandkits.com';
		$data['static_files'] = '//media.cablesandkits.com/static';

		return $data;
	}
}
?>
