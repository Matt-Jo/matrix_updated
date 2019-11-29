<?php
class prepared_db_engine extends prepared_db {
	private static $instances = [];
	private $db;

	private static $defaults = [
		'host' => 'localhost',
		'port' => 3306,
	];

	private function __construct($instance) {
		$this->db = $instance;
	}

	private static function instance_key(Array $params) {
		return array_reduce(function($key, $el) {
			return $key.$el;
		}, array_map(function($key, $val) {
			return $key.$val;
		}, array_keys($params), $params));
	}

	public static function instance(Array $params) {
		$instance_key = self::instance_key($params);

		foreach (self::$defaults as $key => $val) {
			if (empty($params[$key])) $params[$key] = $val;
		}

		if (empty(self::$instances[$instance_key])) {
			$instance = new mysqli($params['host'], $params['username'], $params['password'], $params['dbname'], $params['port']);
			self::$instances[$instance_key] = new self($instance);
		}

		return self::$instances[$instance_key];
	}

	public function 
}

class PreparedDbMysqlException extends Exception {
}
?>
