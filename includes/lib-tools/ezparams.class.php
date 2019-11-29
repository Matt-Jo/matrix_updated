<?php
class ezparams {
	private $args;
	private $where;
	private $operators;

	public function __construct($args, $operators=NULL) {
		$this->args = $args;
		!empty($operators)?$this->operators = $operators:NULL;
	}

	public function __set($key, $val) {
		if (!is_scalar($val)) throw new Exception('ezparams only accepts scalar values.');
		return $this->args[$key] = $val;
	}

	public function __get($key) {
		if (method_exists($this, $key)) return $this->{$key}();
		else return $this->args[$key];
	}

	public function __isset($key) {
		return isset($this->args[$key]);
	}

	public function __unset($key) {
		unset($this->args[$key]);
	}

	public function reduce_cols($cols, $keep=TRUE) {
		// if we're keeping the columns passed in, we need to grab just the keys for the columns we're removing
		if ($keep) $cols = array_diff(array_keys($this->args), $cols);
		array_map([$this, '__unset'], $cols);
	}

	public function select_cols() {
		// this is identical to insert_cols() but we could conceivably treat them differently
		return implode(', ', array_keys($this->args));
	}

	public function select_list($named=FALSE) {
		// this is identical to insert_params() but we could conceivably treat them differently
		if ($named) return implode(', ', array_map([$this, 'reduce_to_named_params'], array_keys($this->args)));
		else return implode(', ', array_map([$this, 'reduce_to_params'], $this->args));
	}

	public function insert_cols() {
		return implode(', ', array_keys($this->args));
	}

	public function insert_params($named=FALSE) {
		if ($named) return implode(', ', array_map([$this, 'reduce_to_named_params'], array_keys($this->args)));
		else return implode(', ', array_map([$this, 'reduce_to_params'], $this->args));
	}

	public function insert_ondupe() {
		return implode(', ', array_map([$this, 'duplicate_key'], array_keys($this->args)));
	}

	public function query_vals($more_vals=NULL, $named=FALSE) {
		if (empty($more_vals)) $more_vals = [];
		elseif (!is_array($more_vals)) $more_vals = [$more_vals];

		if ($named) return CK\fn::parameterize(array_merge($this->args, $more_vals));
		else return array_merge(array_values($this->args), $more_vals);
	}

	public function update_cols($named=FALSE) {
		if ($named) return implode(', ', array_map(function($col) { return "$col = :$col"; }, array_keys($this->args)));
		else return implode(', ', array_map(function($col) { return "$col = ?"; }, array_keys($this->args)));
	}

	public function where_cols($sep='AND') {
		return implode(' = ? '.$sep.' ', array_keys($this->args)).' = ?';
	}

	/*public function where_cols($sep='AND') {
		if (!empty($this->operators)) {
			$set1 = array_diff(array_keys($this->args), array_keys($this->operators));
			$set2 = array_keys($this->operators);
		}
		else {
			$set1 = array_keys($this->args);
		}
		$clause = '';
		if (!empty($set1)) $clause .= implode(' = ? '.$sep.' ', array_keys($this->args)).' = ?';
		if (!empty($set2)) {
			foreach ($set2 as $key) {
				if (!empty($clause)) $clause .= ' '.$sep.' ';
				$clause .= $key.' '.$this->operators[$key].' ?';
			}
		}
		return $clause;
	}*/

	private function reduce_to_params($val) {
		return '?';
	}

	private function reduce_to_named_params($val) {
		return ":$val";
	}

	private function duplicate_key($col) {
		return "$col=VALUES($col)";
	}
}
?>