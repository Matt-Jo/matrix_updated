<?php
// many guidelines pulled from https://moz.com/blog/15-seo-best-practices-for-structuring-urls
class url_handler {

	private static $stop_words = ['and', 'or', 'but', 'of', 'the', 'a'];
	private static $whitelist = ['(', ')', 'a-z', 'A-Z', '0-9', '-'];

	private static $optimal_max_length = 60;

	private $original_path;
	private $parts = [
		'scheme' => NULL,
		'host' => NULL,
		'port' => NULL,
		'user' => NULL,
		'pass' => NULL,
		'path' => NULL,
		'query' => NULL,
		'fragment' => NULL
	];

	public function __construct($path) {
		$this->original_path = $path;
		$parsed = parse_url($path);

		foreach ($parsed as $part => $val) {
			$this->parts[$part] = $val;
		}
	}

	private function build_url() {
		$url = '';
		$userpass = [];
		$hostport = '';

		if (!empty($this->parts['user'])) $userpass[] = $this->parts['user'];
		if (!empty($this->parts['pass'])) $userpass[] = $this->parts['pass'];
		$userpass = implode(':', $userpass);

		if (!empty($this->parts['host'])) $hostport .= $this->parts['host'];
		if (!empty($this->parts['port'])) $hostport .= ':'.$this->parts['port'];

		if (!empty($this->parts['scheme'])) $url .= $this->parts['scheme'].'://';
		elseif (!empty($userpass) || !empty($hostport)) $url .= '//';

		if (!empty($userpass)) $url .= $userpass.'@';

		if (!empty($hostport)) $url .= $hostport;
		elseif (!empty($url)) throw new UrlHandlerException('If Scheme or Credentials are specified, host and/or port are required for URL.');

		if (!empty($this->parts['path'])) $url .= $this->parts['path'];
		if (!empty($this->parts['query'])) $url .= '?'.$this->parts['query'];
		if (!empty($this->parts['fragment'])) $url .= '#'.$this->parts['fragment'];

		return $url;
	}

	public function get_url() {
		return $this->build_url();
	}

	public function is_too_long() {
		return strlen($this->get_url()) > self::$optimal_max_length;
	}

	// we might also figure out how to automatically look for some repetition

	public function allow_underscores() {
		if (!in_array('_', self::$whitelist)) array_unshift(self::$whitelist, '_');

		return $this;
	}

	public function disallow_underscores() {
		if (in_array('_', self::$whitelist)) array_splice(self::$whitelist, array_search('_', self::$whitelist), 1);

		return $this;
	}

	public function simple_seo_transform() {
		if (empty($this->parts['path'])) return;

		$path_parts = pathinfo(strtolower($this->parts['path']));

		$dir = !empty($path_parts['dirname'])?preg_replace(['#[^a-zA-Z0-9\s/]+#', '/\s+/'], [' ', '-'], $path_parts['dirname']):'';
		$file = !empty($path_parts['filename'])?preg_replace(['/[^a-zA-Z0-9\s]+/', '/\s+/'], [' ', '-'], $path_parts['filename']):'';
		$ext = !empty($path_parts['extension'])?preg_replace('/[^a-zA-Z0-9]+/', '', $path_parts['extension']):'';

		$path = '';
		if (!empty($dir)) $path .= $dir.'/';
		if (!empty($file)) $path .= $file;
		if (!empty($ext)) $path .= '.'.$ext;

		$this->parts['path'] = $path;

		return $this; // chaining
	}

	public function full_seo_transform() {
		if (empty($this->parts['path'])) return;

		$path_parts = pathinfo(strtolower($this->parts['path']));

		$dir = '';
		$file = '';
		$ext = '';

		if (!empty($path_parts['dirname'])) {
			$dirs = explode('/', $path_parts['dirname']);

			foreach ($dirs as &$directory) {
				$directory = self::remove_stop_words($directory);
				$directory = self::apply_whitelist($directory);
				$directory = self::coerce_whitespace($directory);
			}

			$dir = implode('/', $dirs);
		}

		if (!empty($path_parts['filename'])) {
			$file = self::remove_stop_words($path_parts['filename']);
			$file = self::apply_whitelist($file);
			$file = self::coerce_whitespace($file);
		}

		if (!empty($path_parts['extension'])) {
			$ext = preg_replace('/[^a-zA-Z0-9]+/', '', $path_parts['extension']);
		}

		$path = '';
		if (!empty($dir)) $path .= $dir.'/';
		if (!empty($file)) $path .= $file;
		if (!empty($ext)) $path .= '.'.$ext;

		$this->parts['path'] = $path;

		return $this; // chaining
	}

	private static function remove_stop_words($section) {
		foreach (self::$stop_words as $word) {
			$section = preg_replace('/(\b)'.$word.'(\b)/', '\1\2', $section);
		}

		return $section;
	}

	private static function apply_whitelist($section) {
		// single space is a default whitelist character, since that's what we're replacing with anyway
		$section = preg_replace('/[^ '.implode('', self::$whitelist).']+/', ' ', $section);
		return $section;
	}

	private static function coerce_whitespace($section) {
		$section = preg_replace('/\s+/', '-', $section);
		return $section;
	}
}

class UrlHandlerException extends Exception {
}
?>
