<?php
// this is super limited right now, but we mainly need something to handle the "NOW()" calls in prepared queries in a known safe way
// it's entirely possible we'll need to handle more in the future, but add on an as-needed basis
class prepared_expression extends prepared_db {
	const NOW = 'now';

	private static $expression_map = [
		'now' => 'NOW()',
	];

	private $expression;

	public function __construct($expression) {
		if (!isset(self::$expression_map[$expression])) throw new PreparedExpressionException('Expression ['.$expression.'] not recognized.');
		$this->expression = $expression;
	}

	public function __toString() {
		return strval(self::$expression_map[$this->expression]);
	}

	public static function __callStatic($method, $args=NULL) {
		if (!defined('static::'.$method)) throw new PreparedExpressionException('Expression [static::'.$method.'] not recognized.');

		return new self(constant('static::'.$method));
	}
}

class PreparedExpressionException extends Exception {
}


