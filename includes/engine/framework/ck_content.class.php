<?php
class ck_content {
	// nothing is private, we may have reason to extend this elsewhere in the system - it's preloaded with some globally available fields
	protected $data = [
		'domain' => NULL,
		'fqdn' => NULL, // alias
		'cdn' => '//media.cablesandkits.com',
		'media' => '//media.cablesandkits.com', // alias
		'static' => '//media.cablesandkits.com/static',
		'static_files' => '//media.cablesandkits.com/static', // alias
		'yotpo' => ['appkey' => 'RS5sKge5HAa1Oe1gbgC5XkKMu2IATDFERi5Cypav'],
	];
	// the stack is public, there should be very little reason to legitimately use the stack but we don't want to lose that info
	public $stack = array();

	// if we attempt to interact directly with our reserved words, handle it appropriately
	private static $reserved = array('data', 'stack');

	public function __construct($data=array()) {
		if (is_object($data)) $data = get_object_vars();
		foreach ($data as $key => $val) {
			if (in_array($key, self::$reserved)) $key .= '_reservedConflict';

			if (is_numeric($key)) $this->stack[] = $val;
			else $this->$key = $val;
		}

		$this->data['domain'] = '//'.FQDN;
		$this->data['fqdn'] = $this->data['domain'];
	}

	// getters and setters can be defined as callbacks, we'll use the less common "access" and "mutate" proper names because they'll be less likely to conflict with random actions

	public function &__get($key) {
		$getter = "access_$key";
		$callback = isset($this->$getter)?$this->$getter:NULL;
		if (method_exists($this, $getter)) return $this->$getter(); // this supports a natively implemented accessor - i.e. one we've hard coded into an extending class
		elseif ($callback && is_callable($callback)) return $callback(); // this supports an accessor implemented as a callback
		else return $this->data[$key];
	}
	public function __set($key, $val) {
		// if a getter is to be assigned, it'll have to be named accessor_{$key} to work with our magic methods
		// if a setter is to be assigned, it'll have to be named mutator_{$key} to work with our magic methods
		$setter = "mutate_$key";
		$callback = isset($this->$setter)?$this->$setter:NULL;
		if (method_exists($this, $setter)) return $this->$setter($val); // this supports a natively implemented mutator - i.e. one we've hard coded into an extending class
		elseif ($callback && is_callable($callback)) return $callback($val); // this supports a mutator implemented as a callback
		elseif (is_callable($val)) $this->$key = $val; // if we're defining a new callback, assign it to a property directly
		else return $this->data[$key] = $val;
	}
	public function __isset($key) {
		return isset($this->data[$key]);
	}
	public function __unset($key) {
		unset($this->data[$key]);
	}

	public function __call($method, $args) {
		// this will handle methods that aren't pre-defined, but set as callback-properties

		if (is_callable(array($this, $method))) {
			$callback = $this->$method;
			return call_user_func_array($callback, $args);
		}
		// we don't worry about throwing an exception, at least not for now, we may add that in the future
		else return NULL;
	}
}
?>