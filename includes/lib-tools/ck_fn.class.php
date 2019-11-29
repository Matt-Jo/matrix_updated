<?php
namespace CK;

class fn {
	public static function check_flag($flag, $truevars=[], $falsevars=[]) {
		if ($flag === TRUE || (is_numeric($flag) && $flag > 0) || in_array(strtolower(trim($flag)), array_merge(['y', 'yes', 'on', 't', 'true', '+'], $truevars))) return TRUE;
		elseif ($flag === FALSE || (is_numeric($flag) && $flag <= 0) || in_array(strtolower(trim($flag)), array_merge(['n', 'no', 'off', 'f', 'false', '-'], $falsevars))) return FALSE;
		else return NULL;
	}

	// this is a brute force key existence check that properly handles empty/null values for existing keys
	public static function is_key_set($arr, $keys) {
		if (!is_array($keys)) $keys = [$keys];
		foreach ($keys as $key) {
			if (!isset($arr[$key]) || !array_key_exists($key, $arr)) return FALSE;
			$arr = $arr[$key];
		}
		return TRUE;
	}

	public static function get_caller($level=2) {
		// in most cases we're interested in 2 levels back
		$trace = debug_backtrace(FALSE);
		$caller = $trace[$level];
		return $caller['function'];
	}

	public static function expand_arg_list(array $args) {
		switch (count($args)) {
			case 0:
				throw new \Exception("function <strong>'".self::get_caller()."()'</strong> requires at least one parameter.");
				return FALSE;
				break;
			case 1:
				if (is_numeric($args[0])) return $args; // if we're passed an array of 1 integer, then we're good to go
				else $args = array_pop($args); // if we were passed in an array, dereference it, this is the meat of what we're after.
				// fallthrough
			default:
				if (!is_array($args)) {
					throw new \Exception("function <strong>'".self::get_caller()."()'</strong> requires a list of numbers or an array of numbers to operate on");
					return FALSE;
				}
				break;
		}
		return $args;
	}

	public static function simple_seo($name, $suffix=NULL) {
		return strtolower(preg_replace(['/[^A-Za-z0-9\s]+/', '/\s+/'], [' ', '-'], $name)).$suffix;
	}

	// copied/modified from https://www.binarytides.com/php-output-content-browser-realtime-buffering/
	public static function constant_flush() {
		if (headers_sent()) return FALSE;

		// Turn off output buffering
		ini_set('output_buffering', 'off');
		// Turn off PHP output compression
		ini_set('zlib.output_compression', false);
				 
		//Flush (send) the output buffer and turn off output buffering
		//ob_end_flush();
		while (@ob_end_flush());
				 
		// Implicitly flush the buffer(s)
		ini_set('implicit_flush', true);
		ob_implicit_flush(true);
		 
		//prevent apache from buffering it for deflate/gzip
		header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
		 
		for ($i=0; $i<1000; $i++) {
			echo ' ';
		}
				 
		flush();

		return TRUE;
	}

	public static function redirect_and_exit($url, $exact=FALSE) {
		// we'll use an exact link if we need to change details of the connection (domain, scheme, user/pass, port)
		// otherwise we'll use a relative path
		if (!$exact) {
			$parts = parse_url($url);
			$url = !empty($parts['path'])?$parts['path']:'';
			if (!empty($parts['query'])) $url .= '?'.$parts['query'];
			if (!empty($parts['fragment'])) $url .= '#'.$parts['fragment'];
		}
		header('Location: '.$url);
		session_write_close();
		exit();
	}

	public static function permanent_redirect($url, $exact=FALSE) {
		header("HTTP/1.1 301 Moved Permanently");
		self::redirect_and_exit($url, $exact);
	}

	public static function remote_img_exists($uri) {
		if (@get_headers($uri)[0] == 'HTTP/1.1 404 Not Found') return FALSE;
		else return TRUE;
	}

	// the main reasons this is a toolset method are
	// a) to replicate the functionality of removing certain default parameters
	// b) the diff and filter one-liner is ugly and we don't want to type it every time
	// the main difference between this and the legacy function is that it *does not* remove sub-arrays - they'll have to be mentioned explicitly to remove them
	public static function filter_request($source, $remove_params=[]) {
		$remove_params = array_flip($remove_params);

		$remove_params[session_name()] = TRUE;
		$remove_params['error'] = TRUE;
		$remove_params['x'] = TRUE;
		$remove_params['y'] = TRUE;

		// we may pass in an ArrayObject
		if (is_object($source)) $source = (array) $source;

		return array_filter(array_diff_key($source, $remove_params), function($var) { return !is_null($var)&&$var!==''&&$var!==FALSE; });
	}

	// this was part of legacy keymap, which was useful but we're refactoring off of
	public static function qs($remove_list=[], $source_array=NULL) {
		if (empty($source_array)) $source_array = $_GET;
		return http_build_query(self::filter_request($source_array, $remove_list));
	}

	// the following function takes an input array and turns its keys into parameters suitable for database usage, while performing the requested key and value modifications
	public static function parameterize($input, $values=[], $keymap=[]) {
		// to make a key suitable for our DB parameterization, it needs to be prepended with ':'
		$output = [];

		foreach ($input as $key => $val) {
			if (isset($keymap[$key])) $key = $keymap[$key];
			if (isset($values[$key])) $val = $values[$key];

			$output[':'.$key] = $val;
		}

		return $output;
	}

	// rules derived from https://en.wikipedia.org/wiki/English_plurals
	// not intended to be exhaustive
	private static $plural_rules = [
		// default is just append an 's'... don't need to set up a rule for it
		['regex' => '/(s|sh|ch)$/', 'action' => 'append', 'chars' => 'es'],
		// this gets its own rule even though it's the same as above, since sometimes it's wrong (for words of foreign origin) and we may wish to change or omit it
		['regex' => '/o$/', 'action' => 'append', 'chars' => 'es'],
		['regex' => '/([b-df-hj-np-tv-z])y$/', 'action' => 'replace', 'chars' => '$1ies'],

		// after this are the same rules duplicated for capitals or mixed case
		['regex' => '/(S|SH|CH)$/i', 'action' => 'append', 'chars' => 'ES'],
		['regex' => '/O$/', 'action' => 'append', 'chars' => 'ES'],
		['regex' => '/([B-DF-HJ-NP-TV-Z])Y$/i', 'action' => 'replace', 'chars' => '$1IES'],

		// after this point is the default list, nothing else should go after this point
		['regex' => '/[a-z0-9]$/', 'action' => 'append', 'chars' => 's'],
		['regex' => '/[A-Z]$/', 'action' => 'append', 'chars' => 'S']
	];

	private static $plural_repository = [];

	public static function pluralize($word, $qty=NULL, $plural_rule=[]) {
		// we need to pluralize unless qty is explicitly 1
		if ($qty === 1) return $word;
		// if we've already pluralized this word, pull it from our repository - for general uses, the memory/regex tradeoff probably favors
		// using memory, if we find we're pluralizing thousands of words we'll need to determine which is better and potentially refactor
		// to optionally skip this step
		// different capitalization will count as a different word
		elseif (!empty(self::$plural_repository[$word])) return self::$plural_repository[$word];
		else {
			// if we've passed in a new plural rule, follow it instead of using the general ruleset
			if (empty($plural_rule)) {
				foreach (self::$plural_rules as $idx => $plural_rule) {
					if (preg_match($plural_rule['regex'], $word)) break; // $plural_rule is set to what we want
				}
			}

			$plural = $word; // initialize the plural to our base word

			if ($plural_rule['action'] == 'replace') {
				$plural = preg_replace($plural_rule['regex'], $plural_rule['chars'], $plural);
			}
			else {
				// we're appending
				$plural .= $plural_rule['chars'];
			}

			return self::$plural_repository[$word] = $plural;
		}
	}
}
?>
