<?php
class prepared_fields extends prepared_db {
	private $fields;
	private $query_type;
	private $named_fields;

	const INSERT_QUERY = 'INSERT_QUERY';
	const SELECT_QUERY = 'SELECT_QUERY';
	const UPDATE_QUERY = 'UPDATE_QUERY';
	const DELETE_QUERY = 'DELETE_QUERY';

	public function __construct($fields=NULL, $query_type=NULL) {
		if (empty($fields)) $fields = [];
		elseif (is_scalar($fields)) $fields = [$fields];

		foreach ($fields as $key => $val) {
			self::validate_data($val, TRUE);
			self::validate_identifier($key, TRUE);
		}

		if (!empty($fields)) $this->set_named_fields(empty(array_filter(array_keys($fields), 'is_int')));

		$this->fields = $fields;
		$this->set_query_type($query_type);
	}

	public function set_query_type($query_type) {
		switch ($query_type) {
			case self::INSERT_QUERY:
			case self::SELECT_QUERY:
			case self::UPDATE_QUERY:
			case self::DELETE_QUERY:
				$this->query_type = $query_type;
				break;
			case '':
				break;
			default:
				throw new PreparedFieldsException('['.$query_type.'] is not a valid query type.');
				break;
		}
	}

	public function get_query_type() {
		return $this->query_type;
	}

	public function is_query_type($query_type) {
		if (empty($this->query_type)) return TRUE;
		else return $this->query_type == $query_type;
	}

	public function set_named_fields($named_fields) {
		$this->named_fields = (bool) $named_fields;
	}

	public function named_fields_is_set() {
		return !is_null($this->named_fields);
	}

	public function is_named_fields() {
		return $this->named_fields!==FALSE;
	}

	/*-------------------------------
	// validate keys and values
	-------------------------------*/

	public function blacklist(Array $list) {
		$this->filter($list, TRUE);
	}

	public function whitelist(Array $list) {
		$this->filter($list, FALSE);
	}

	public function filter(Array $list, $blacklist=FALSE) {
		$this->fields = self::filter_fields($this->fields, $list, $blacklist);
	}

	// field name
	public static function validate_identifier($identifier, $halt=FALSE) {
		$status = TRUE;

		try {
			if (!is_scalar($identifier)) throw new PreparedFieldsException('The identifier must be a scalar value to be valid');

			$identifier = trim($identifier);

			if (is_null($identifier) || strlen($identifier) == 0) throw new PreparedFieldsException('An identifier cannot be zero length');

			$ids = explode('.', $identifier);
			foreach ($ids as $id) {
				if ($id[0] == '`' && strrev($id)[0] == '`') $id = trim($id, '`');
				if (trim($id) == '' || preg_match('/[^0-9a-zA-Z$_]/', $id)) throw new PreparedFieldsException('['.$identifier.'] is not a valid query identifier.');
			}
		}
		catch (PreparedFieldsException $e) {
			$status = FALSE;
			if ($halt) throw $e;
		}
		catch (Exception $e) {
			$status = FALSE;
			if ($halt) throw $e;
		}
		finally {
			return $status;
		}
	}

	public static function validate_data($data, $halt=FALSE) {
		if (!is_scalar($data) && !is_null($data) && !($data instanceof prepared_expression)) {
			if ($halt) throw new PreparedFieldsException('Prepared Fields only accepts scalar values.');
			else return FALSE;
		}
		return TRUE;
	}

	/*-------------------------------
	// change keys and values
	-------------------------------*/

	public static function filter_fields(Array $data, Array $list, $blacklist=FALSE) {
		if (defined('ARRAY_FILTER_USE_KEY')) {
			return array_filter($data, function($key) use ($list, $blacklist) {
				// whitelist/blacklist the fields we are allowed to use
				if ($blacklist) return !in_array($key, $list);
				else return in_array($key, $list);
			}, ARRAY_FILTER_USE_KEY);
		}
		else {
			$result = $blacklist?$data:[];
			foreach ($list as $key) {
				if ($blacklist) unset($result[$key]);
				elseif (isset($data[$key])) $result[$key] = $data[$key];
			}
			return $result;
		}
	}

	public static function clean_identifier($identifier) {
		return preg_replace('/[^0-9a-zA-Z_]/', '__', $identifier);
	}

	public static function parameterize_identifier($identifier, $named=TRUE) {
		return $named?':'.self::clean_identifier($identifier):'?';
	}

	public static function consolidate_parameters() {
		$args = func_get_args();
		/*if (is_bool($args[0])) $named = array_shift($args);
		elseif (is_bool(end($args))) $named = array_pop($args);
		else $named = TRUE;*/

		return call_user_func_array('array_merge', array_map(function (prepared_fields $fields) /*use ($named)*/ {
			//$fields->set_named_fields($named);
			return $fields->parameters();
		}, $args));
	}

	/* 5.6 or later
	public static function consolidate_parameters($named, prepared_fields ...$fields) {
	}*/

	/*-------------------------------
	// basic data handling
	-------------------------------*/

	public function append_data($val) {
		self::validate_data($val);
		$this->fields[] = $val;
	}

	public function __set($key, $val) {
		self::validate_identifier($key);
		self::validate_data($val);

		if (!$this->named_fields_is_set()) $this->set_named_fields($key == (int) $key);

		return $this->fields[$key] = $val;
	}

	public function __get($key) {
		if (isset($this->fields[$key])) return $this->fields[$key];
		elseif (method_exists($this, $key)) return $this->{$key}();
		else return NULL;
	}

	public function __isset($key) {
		return isset($this->fields[$key]);
	}

	public function __unset($key) {
		unset($this->fields[$key]);
	}

	/*-------------------------------
	// return fields and data formatted for queries
	-------------------------------*/

	// CREATE

	public function insert_fields() {
		if (!$this->is_query_type(self::INSERT_QUERY)) throw new PreparedFieldsException('Cannot get insert formatted data; fields are listed as '.$this->get_query_type());
		// a comma separated list of field names
		return implode(', ', array_keys($this->fields));
	}

	public function insert_values() {
		if (!$this->is_query_type(self::INSERT_QUERY)) throw new PreparedFieldsException('Cannot get insert formatted data; fields are listed as '.$this->get_query_type());
		return implode(', ', array_map(function($key) {
			if ($this->fields[$key] instanceof prepared_expression) return $this->fields[$key]->__toString();
			else return self::parameterize_identifier($key, $this->is_named_fields());
		}, array_keys($this->fields)));
	}

	public function insert_on_duplicate_key() {
		if (!$this->is_query_type(self::INSERT_QUERY)) throw new PreparedFieldsException('Cannot get insert formatted data; fields are listed as '.$this->get_query_type());
		return implode(', ', array_map(function($key) { return $key.'=VALUES('.$key.')'; }, array_keys($this->fields)));
	}

	public function insert_parameters() {
		if (!$this->is_query_type(self::INSERT_QUERY)) throw new PreparedFieldsException('Cannot get insert formatted data; fields are listed as '.$this->get_query_type());
		return $this->parameters();
	}

	// READ

	public function select_fields() {
		if (!$this->is_query_type(self::SELECT_QUERY)) throw new PreparedFieldsException('Cannot get select formatted data; fields are listed as '.$this->get_query_type());
		// a comma separated list of field names
		return implode(', ', array_keys($this->fields));
	}

	public function select_values() {
		if (!$this->is_query_type(self::SELECT_QUERY)) throw new PreparedFieldsException('Cannot get select formatted data; fields are listed as '.$this->get_query_type());
		return implode(', ', array_map(function($key) {
			if ($this->fields[$key] instanceof prepared_expression) return $this->fields[$key]->__toString();
			else return self::parameterize_identifier($key, $this->is_named_fields());
		}, array_keys($this->fields)));
	}

	public function select_parameters() {
		if (!$this->is_query_type(self::SELECT_QUERY)) throw new PreparedFieldsException('Cannot get select formatted data; fields are listed as '.$this->get_query_type());
		$original_fields = $this->fields;
		$this->fields = array_filter($this->fields);
		$parameters = $this->parameters();
		$this->fields = $original_fields;
		return $parameters;
	}

	// UPDATE

	public function update_sets() {
		if (!$this->is_query_type(self::UPDATE_QUERY)) throw new PreparedFieldsException('Cannot get update formatted data; fields are listed as '.$this->get_query_type());
		return implode(', ', array_map(function($key) {
			if ($this->fields[$key] instanceof prepared_expression) return $key.' = '.$this->fields[$key]->__toString();
			else return $key.' = '.self::parameterize_identifier($key, $this->is_named_fields());
		}, array_keys($this->fields)));
	}

	public function update_parameters() {
		if (!$this->is_query_type(self::UPDATE_QUERY)) throw new PreparedFieldsException('Cannot get update formatted data; fields are listed as '.$this->get_query_type());
		return $this->parameters();
	}

	// DELETE

	// GENERIC

	public function parameters() {
		$fields = array_filter($this->fields, function($field) {
			if ($field instanceof prepared_expression) return FALSE;
			else return TRUE;
		});

		if ($this->is_named_fields()) {
			$named_fields = [];
			foreach ($fields as $key => $val) {
				$named_fields[self::parameterize_identifier($key, $this->is_named_fields())] = $val;
			}
			return $named_fields;
		}
		else return array_values($fields);
	}

	public function where_clause() {
		// this is a very simple equality list - if we want anything more, we'll have to do it manually, for now
		return implode(' AND ', array_map(function ($key) {
			if (is_null($this->fields[$key])) $criterion = $key.' IS NULL';
			elseif ($this->fields[$key] instanceof prepared_expression) $criterion = $key.' = '.$this->fields[$key]->__toString();
			else $criterion = $key.' = '.self::parameterize_identifier($key, $this->is_named_fields());
			return $criterion;
		}, array_keys($this->fields)));
	}
}

class PreparedFieldsException extends Exception {
}
?>
