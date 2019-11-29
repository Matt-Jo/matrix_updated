<?php
abstract class array_singleton extends ArrayObject {

	private static $instance;
	private static $access = FALSE;

	// protected constructor(), init(), the only way to intstantiate is the instance method, which calls the constructor
	// which calls init, which then must call build_array to get the ArrayObject constructor to run appropriately

	public function __construct($input, $flags=0, $iterator_class='ArrayIterator') {
		if (!self::$access) throw new Exception('You cannot directly instantiate an array_singleton; you must use the ::instance() method.');
		self::$access = FALSE;
		$this->init($input, $flags, $iterator_class);
	}

	protected function build_array($input, $flags=0, $iterator_class='ArrayIterator') {
		return parent::__construct($input, $flags, $iterator_class);
	}

	abstract protected function init($input, $flags=0, $iterator_class='ArrayIterator');

	public static function instance($input=NULL, $flags=0, $iterator_class='ArrayIterator') {
		if (!empty(self::$instance)) return self::$instance;
		else {
			self::$access = TRUE;
			return self::$instance = new static($input, $flags, $iterator_class);
		}
	}
}
?>
