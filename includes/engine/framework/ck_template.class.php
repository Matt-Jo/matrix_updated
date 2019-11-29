<?php
class ck_template {

	public $m; // mustache template instance
	public $buffer = FALSE;

	private $data = [];
	private $paths = NULL;

	// what stage of the page are we in:
	// 0: not started
	// 1: header (open)
	// 2: content
	// 3: footer (close)
	private $stage = 0;

	const NONE = 0;
	const FRONTEND = 1;
	const BACKEND = 2;
	const SLIM = 3;
	const EMAIL = 4;

	private $template_type;

	private $template_types = [
		0 => [],
		1 => [
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
		2 => [
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
		3 => [
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
		4 => [
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
	];

	public function __construct($paths, $template_type=self::FRONTEND) {
		$this->paths = is_array($paths)?$paths:[$paths];
		$this->template_type = $template_type;

		if (!empty($this->paths)) $this->init();
	}

	public function init() {
		$loaders = [];
		foreach ($this->paths as $path) {
			$loaders[] = new Mustache_Loader_FilesystemLoader($path, ['extension' => '']); // specify the extension manually
		}
		$loader = new Mustache_Loader_CascadingLoader($loaders);
		// we may want to define caching and other options, but for now we're cool with the default
		$this->m = new Mustache_Engine(['loader' => $loader, 'cache' => DIR_FS_CATALOG.'includes/templates/cache']);
	}

	public function __destruct() {
		if ($this->stage < 3) $this->close(); // if we haven't closed the page, close it
	}

	public function &__get($key) {
		return @$this->data[$key];
	}
	public function __set($key, $val) {
		return $this->data[$key] = $val;
	}
	public function __isset($key) {
		return isset($this->data[$key]);
	}
	public function __unset($key) {
		unset($this->data[$key]);
	}

	public function set_stage($stage) {
		// we're allowing an explicit setter for the stage var, we just have to know what we're doing and why if we want to set the stage
		if (!is_int($stage)) return FALSE;
		$this->stage = $stage;
		return TRUE;
	}

	// for all of the template rendering, we immediately
	public function open($content, $opening_templates=NULL) {
		if ($this->template_type == self::NONE) return;
		// the opening details of the page, only minor processing is required for these
		if ($this->stage) return; // we've already opened the page, we can only do this once
		$this->stage = 1;
		// we probably want to define this list in a more flexible spot (like a database table), but for now this is OK
		if (empty($opening_templates)) $opening_templates = $this->template_types[$this->template_type]['open'];
		if ($this->buffer) $output = '';
		foreach ($opening_templates as $template) {
			$tpl = $this->m->loadTemplate($template);
			// put it right out there. We'll probably want to do some error checking first, really, but for now just do it
			if ($this->buffer)
				$output .= $tpl->render($content);
			else {
				echo $tpl->render($content);
				flush();
			}
		}
		if ($this->buffer) return $output;
	}

	public function close($content=NULL, $closing_templates=NULL) {
		if ($this->template_type == self::NONE) return;
		// the close of the page, not likely anything is required for this
		if ($this->stage >= 3) return; // we've already closed the page, we can only do this once
		$this->stage = max($this->stage, 3);
		// we probably want to define this list in a more flexible spot (like a database table), but for now this is OK
		if (empty($closing_templates)) $closing_templates = $this->template_types[$this->template_type]['close'];
		if ($this->buffer) $output = '';
		foreach ($closing_templates as $template) {
			$tpl = $this->m->loadTemplate($template);
			// put it right out there. We'll probably want to do some error checking first, really, but for now just do it
			if ($this->buffer)
				$output .= $tpl->render($content);
			else {
				echo $tpl->render($content);
				flush();
			}
		}
		if ($this->buffer) return $output;
	}

	private function get_tpl($template) {
		preg_replace('#/?\.\./?#', '/', $template); // we want to trust that we're not being directed anywhere nefarious. This ensures that we stay within the templates directory.
		$pathinfo = pathinfo($template);
		if (empty($pathinfo['extension'])) $template .= isset($details['extension'])?'mustache.html':'.mustache.html';
		elseif ($pathinfo['extension'] == 'mustache') $template .= '.html';
		$template = ltrim($template, '/');

		foreach ($this->paths as $path) {
			if (is_file($path.'/'.$template)) return $this->m->loadTemplate($template);
		}

		$loader = $this->m->getLoader();
		$this->m->setLoader(new Mustache_Loader_FilesystemLoader($pathinfo['dirname'], ['extension' => '']));
		$tpl = $this->m->loadTemplate($pathinfo['basename']);
		$this->m->setLoader($loader);

		return $tpl;
	}

	public function simple_content($template, $content) {
		$loader = $this->m->getLoader();
		$this->m->setLoader(new Mustache_Loader_StringLoader);
		$tpl = $this->m->loadTemplate($template);
		// put it right out there. We'll probably want to do some error checking first, really, but for now just do it
		if ($this->buffer)
			return $tpl->render($content);
		else {
			echo $tpl->render($content);
			flush();
		}
		$this->m->setLoader($loader);
	}

	public function content($templates, $content) {
		if (!$this->stage) $this->open(NULL); // if the page hasn't been opened yet, open it (if we don't want this, set the stage manually)
		if ($this->stage >= 3) return; // we've already closed the page, no more content is allowed
		$this->stage = 2;
		if (empty($templates)) {
			$loader = $this->m->getLoader();
			$this->m->setLoader(new Mustache_Loader_StringLoader);
			$tpl = $this->m->loadTemplate('<pre>'.print_r($content, TRUE).'</pre>');
			// put it right out there. We'll probably want to do some error checking first, really, but for now just do it
			if ($this->buffer)
				return $tpl->render($content);
			else {
				echo $tpl->render($content);
				flush();
			}
			$this->m->setLoader($loader);
		}
		else {
			if (is_scalar($templates)) $templates = array($templates);
			if ($this->buffer) $output = '';
			foreach ($templates as $template) {
				$tpl = $this->get_tpl($template);

				// put it right out there. We'll probably want to do some error checking first, really, but for now just do it
				// we could potentially use the data array that backs the magic method getters and setters, but I don't know if that's what we want yet
				if ($this->buffer)
					$output .= $tpl->render($content);
				else {
					echo $tpl->render($content);
					flush();
				}
			}
			if ($this->buffer) return $output;
		}
	}
}
?>