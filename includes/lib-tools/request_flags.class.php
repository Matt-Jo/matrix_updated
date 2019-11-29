<?php
class request_flags extends array_singleton {

	private static function cli() {
		return PHP_SAPI==='cli';
	}

	// override the instance method to not accept any parameters we're going to automatically consume them from _GET, _POST, etc
    public static function instance($input=NULL, $flags=0, $iterator_class='ArrayIterator') {
		return parent::instance($input, $flags, $iterator_class);
	}

	// we have to match the abstract signature, but we won't be using the parameters
	protected function init($input, $flags=0, $iterator_class='ArrayIterator') {
		// don't worry about overwriting, just use our php.ini preference for precedence and move on - something passed as a flag is unlikely to have a conflict
		if (self::cli()) $this->build_array(array_slice($GLOBALS['argv'], 1));
		else $this->build_array($_REQUEST);
	}

	public function offsetGet($key) {
		if (parent::offsetExists($key)) return CK\fn::check_flag(parent::offsetGet($key));
		else return NULL;
	}
}

