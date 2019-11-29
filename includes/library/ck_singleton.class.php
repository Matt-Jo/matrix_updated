<?php
abstract class ck_singleton extends ck_master_archetype {

	protected $skeleton;

	protected static $instance = [];

	protected function __construct($parameters=[]) {
		$this->init($parameters);
	}

	abstract protected function init($parameters=[]);

	public static function instance($parameters=[]) {
		if (!empty(self::$instance[static::class])) return self::$instance[static::class];
		else return self::$instance[static::class] = new static($parameters);
	}

	// ->is() and ->has() will likely be implemented per class, unless everything fits this simple mode of a field in the header
	public function is($flag) {
		return CK\fn::check_flag($this->get_header($flag));
	}

	public function has($key) {
		return !empty($this->get_header($key));
	}

	public function __sleep() {
		return ['skeleton'];
	}
}
?>
