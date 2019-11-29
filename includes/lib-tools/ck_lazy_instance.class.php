<?php
class ck_lazy_instance {
	private $lazy_class;
	private $lazy_id;
	private $lazy_instance;

	public function __construct($class, $id) {
		$this->lazy_class = $class;
		$this->lazy_id = $id;
	}

	public function instantiate($deets) {
		if (empty($this->lazy_instance)) {
			$class = $this->lazy_class;
			$this->lazy_instance = new $class($this->lazy_id);
		}
	}

	public function __get($key) {
		$this->instantiate($key);

		return $this->lazy_instance->$key;
	}

	public function __call($method, $args) {
		$this->instantiate($method);

		return call_user_func_array([$this->lazy_instance, $method], $args);
	}
}
?>
