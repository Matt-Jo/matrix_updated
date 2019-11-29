<?php
abstract class ck_archetype {

// fields & other data
protected $details;
protected $original; // the original copy of the data we're initializing with
protected $data = array();

// the context that we're in
const CREATE = 1;
const UPDATE = 2;
const REMOVE = 4;
protected $context = 0;

protected function __construct() {
	// empty, but we cascade up in our child classes in case we should want to invoke some functionality, so leave the stub here
}

// init the fields that represent the actual data underlying the object
protected static function init_fields() {
	if (empty(static::$fields)) {
		$primary_table = static::$internal_interface['primary_table'];
		static::$fields = array_keys(static::$internal_interface['tables'][$primary_table]['fields']);
		foreach (static::$internal_interface['tables'][$primary_table]['indexes'] as $index) {
			// we might later find a way to record compound primary keys, but we don't expect to use them
			if ($index['index_type'] == 'PRIMARY KEY') static::$primary_identifier = $index['fields'][0];
		}
	}
}

protected static function format_details($details) {
	if ($details && !is_array($details)) $details = (array) $details;
	return $details;
}

public function &__get($key) {
	if (in_array($key, static::$fields)) return $this->details[$key];
	else return @$this->data[$key];
}
public function __set($key, $val) {
	if (in_array($key, static::$fields)) return $this->details[$key] = $val;
	else return $this->data[$key] = $val;
}
public function __isset($key) {
	if (in_array($key, static::$fields)) return !empty($this->details[$key]);
	else return isset($this->data[$key]);
}
public function __unset($key) {
	if (in_array($key, static::$fields)) $this->details[$key] = NULL;
	else unset($this->data[$key]);
}

// we expect to override this method in most classes, because there may be more than one unique way to identify any given object that we're not aware of here
public function identified() {
	$key = static::$primary_identifier;
	if (isset($this->$key)) return TRUE;
	else return FALSE;
}

// this should only be run once, in the constructor, but it's conceivable that that's not a strict requirement
// we also use this to fill details, since this is the first place we actually check to see if we have an identifier
protected function set_context($fill_details=FALSE) {
	if ($this->identified()) {
		if (!empty($fill_details)) {
			// we trust the fields static property, so we can insert the necessary values directly into a query
			$query_columns = array();
			foreach (static::$fields as $field) {
				if (is_null($this->$field)) $query_columns[] = $field;
			}
			if ($query_columns) {
				$key = static::$primary_identifier;
				$data = prepared_query::fetch('SELECT '.implode(', ', $query_columns).' FROM '.static::$internal_interface['primary_table'].' WHERE '.$key.' = ?', cardinality::ROW, array($this->$key));
				foreach ($data as $field => $val) {
					$this->$field = $val;
				}
			}
		}
		$this->context = static::UPDATE;
	}
	else $this->context = static::CREATE;
}

public function write($context=NULL, $db=NULL) {
	// if we're not explicitly passed in our expected action, default to what we started with when we initialized the object, but don't destroy the passed value
	$perform = $context?$context:$this->context;

	$return_code = NULL;

	// if we've identified this stage with a stage ID, then we're updating an existing entry
	// we're not running an exact identifier because the identifier field needs to be unique, so if it matches an existing entry we can reliably match that to the stage ID
	if ($this->identified()) {
		// as expected, we're updating
		if ($perform & static::UPDATE) {
			$fields = $this->validate(static::$internal_interface['primary_table'], $perform);
			if ($this->validation_status()) {
				if (!empty($fields)) {
					$update_field_keys = array();
					$values = array();
					$key = static::$primary_identifier;
					foreach ($fields as $field => $value) {
						if ($field != $key) {
							$update_field_keys[] = $field;
							$values[] = $value;
						}
						else $$key = $value;
					}
					$values[] = $$key;
					prepared_query::execute('UPDATE '.static::$internal_interface['primary_table'].' SET '.implode(' = ?, ', $update_field_keys).' = ? WHERE '.$key.' = ?', $values);
					$return_code = 1;
				}
				else {
					// there was nothing to update, return a code that evaluates to true but indicates something different than a successful write
					$return_code = 2;
				}
			}
			else {
				$return_code = 0;
				// don't think we need to do anything else here, unless it's prepare the error for access
			}
		}
		// we're expected to create a new one, we'll need to figure out exactly how we want to handle that
		elseif ($perform & static::CREATE) {
			// figure it out, for now throw a "not handled" error
			throw new LogicException("You've attempted to CREATE a new ".get_class($this)." without clearing the ID of an existing one. We currently don't handle this scenario.");
		}
	}
	// if we haven't gotten the stage ID, we're inserting a new row
	else {
		// as expected, we're creating
		if ($perform & static::CREATE) {
			$fields = $this->validate('ck_velocity_stages', $perform);
			if ($this->validation_status()) {
				prepared_query::execute('INSERT INTO '.static::$internal_interface['primary_table'].' ('.implode(', ', array_keys($fields)).') VALUES ('.implode(', ', array_map(function($val) { return '?'; }, $fields)).')', array_values($fields));
				$return_code = 1;
			}
			else {
				$return_code = 0;
				// don't think we need to do anything else here, unless it's prepare the error for access
			}
		}
		// we're expected to update, we'll need to figure out exactly how we want to handle that
		elseif ($perform & static::UPDATE) {
			// figure it out, for now throw a "not handled" error
			throw new LogicException("You've attempted to CREATE a new ".get_class($this)." with the ID of an existing stage. We currently don't handle this scenario.");
		}
	}
	return $return_code;
}
// alias write() using the CREATE context
public function create($db=NULL) { return $this->write(static::CREATE, $db); }
// alias write() using the UPDATE context
public function update($db=NULL) { return $this->write(static::UPDATE, $db); }

protected $validation_errors = array();
protected $last_validation_code;
protected $last_validation_message;
protected $last_validation_error_code;
protected $last_validation_error_message;
protected static $validation_error_codes = array(
	0 => 'SUCCESS',
	1 => 'NULL ERROR - a value is required but was never provided',
	2 => 'INT ERROR - the value you entered is not a valid integer',
	4 => 'FLOAT ERROR - the value you entered is not a valid number',
	8 => 'CHAR ERROR - the value you entered is too long',
	16 => 'SET ERROR - the value you entered is not an allowed value',
	32 => 'DATETIME ERROR - the value you entered could not be validated as a date/time value'
);
protected static $validation_type_codes = array(
	'TINYINT' => 2,
	'SMALLINT' => 2,
	'MEDIUMINT' => 2,
	'INT' => 2,
	'INTEGER' => 2,
	'BIGINT' => 2,
	'FLOAT' => 4,
	'DOUBLE' => 4,
	'DECIMAL' => 4,
	'NUMERIC' => 4,
	'CHAR' => 8,
	'VARCHAR' => 8,
	'ENUM' => 16,
	'SET' => 16,
	'DATE' => 32,
	'DATETIME' => 32,
	'TIMESTAMP' => 32
	/* if the type isn't represented here, there are no meaningful validation restrictions that we
	intend to run into (we may want to add something to account for binary input into blobs) */
);
protected function validate($table, $context) {
	$field_map = array();
	$this->clear_validation_history();
	foreach (static::$internal_interface['tables'][$table]['fields'] as $field_name => $options) {
		// for CREATE, we need all values to be filled and correct
		// for UPDATE, we're only interested in the fields that have changed
		if ($context & static::CREATE || ($context & static::UPDATE && $this->details[$field_name] != $this->original[$field_name])) {
			// run the validation
			$this->last_validation_code = $this->_val($this->details[$field_name], $options);
			$this->last_validation_message = static::$validation_error_codes[$this->last_validation_code]; // this will only match a single error, if it's a NULL issue then it won't match here

			if (!$this->last_validation_code && !is_null($this->details[$field_name])) {
				// success, and it's not a null/default, let's log the field for use in our query
				$field_map[$field_name] = $this->details[$field_name];
			}
			else {
				// handle error
				$this->last_validation_error_code = $this->last_validation_code;
				foreach (static::$validation_error_codes as $code => $message) {
					// we only care about the first one here, the full details will be included in the error log
					if ($code & $this->last_validation_error_code) {
						$this->last_validation_error_message = $message;
						break;
					}
				}
				$this->log_validation_error($field_name, $this->details[$field_name], $options);
			}
		}
	}
	return $field_map;
}
protected function _val($value, $criteria, $silent=FALSE) { // silent allows us to massage data that doesn't otherwise fit... we're not using it yet
	$return_code = 0;
	if (is_null($value) && $criteria['required'] == 'NOT NULL' && is_null($criteria['default'])) $return_code += 1;

	preg_match('/^([A-Za-z]+)(\s*\((.*)\))?$/', trim($criteria['type']), $type_info);
	$type = strtoupper($type_info[1]); // the first paren match
	$details = $type_info[3]; // the inner details paren match

	// we're ignoring certain types since we're not using them and thus don't have a meaningful point of context with which to test them (or just don't care)
	// namely: BIT, BINARY & VARBINARY, BLOB, TIME, YEAR
	// we're ignoring TEXT because generally speaking it won't fail on any input we're working with

	if (static::$validation_type_codes[$type] == 2) { // this is an integer type
		// by default MySQL will round floating point numbers to the nearest integer for integer fields
		// we're fine with that, for now
		if (!is_numeric($value)) $return_code += 2;
		// we should really be worrying about bit length here, but we're not, for now
		// too large numbers will default to the largest acceptable number
	}
	elseif (static::$validation_type_codes[$type] == 4) { // this is a floating point type
		// we're generally making the same exceptions and accepting the same input here as we are with integers
		if (!is_numeric($value)) $return_code += 4;
	}
	elseif (static::$validation_type_codes[$type] == 8) { // this is a char type with a specified length requirement
		// even though MySQL will handle too long data OK, we'll mark an error for these since the result is less likely to be an approximation of what we're looking for
		if (strlen($value) > $details) $return_code += 8; // $details should hold the specified maximum length
	}
	elseif (static::$validation_type_codes[$type] == 16) { // this is a set or enum type
		$allowed_values = preg_split('/\s*,\s*/', $details);
		array_walk($allowed_values, function(&$val, $key) { $val = preg_replace("/'/", '', $val); });
		$input_values = preg_split('/\s*,\s*/', $values);
		// we could allow ENUM index values here, but that's not a recommended way to interact with enums
		if ($type == 'ENUM' && !in_array($value, $allowed_values)) $return_code += 16;
		elseif ($type == 'SET' && ($diff = array_diff($input_values, $allowed_values)) && !empty($diff)) $return_code += 16;
	}
	elseif (static::$validation_type_codes[$type] == 32) { // this is a type that will work with the PHP DateTime class
		try {
			// if we want to wrangle the input format to our desired format, we do that prior to validating, this is just a straight check, so no need to save the value anywhere
			new DateTime($value);
		}
		catch (Exception $e) {
			$return_code += 32;
		}
	}

	return $return_code;
}
protected function log_validation_error($field_name, $value, $options) {
	$err = (object) array('field_name' => $field_name, 'value' => $value, 'code' => $this->last_validation_error_code, 'messages' => array(), 'value' => $options);
	foreach (static::$validation_error_codes as $code => $message) {
		if ($code & $this->last_validation_error_code) {
			$err->messages[] = $message;
		}
	}
	$this->validation_errors[] = $err;
}
protected function clear_validation_history() {
	$this->validation_errors = array();
	$this->last_validation_code = NULL;
	$this->last_validation_message = NULL;
	$this->last_validation_error_code = NULL;
	$this->last_validation_error_message = NULL;
}
protected function validation_status() {
	return empty($this->validation_errors);
}
// need to code up an accessor method for validation errors, but I'm not quite sure yet how we'll use it

// wrangle the database, if we want to set it explicitly for the call or the class (otherwise, fall back to the global instance)
protected static $db = NULL;
public static function set_db($db) {
	static::$db = $db;
}
// this allows us to use dependancy injection without requiring it
protected static function get_db($db=NULL) {
	return $db ?? self::$db ?? service_locator::get_db_service() ?? NULL;
}

// set up the table structure as defined from the internal interface defined in the calling class
public static function _setup() {

	foreach (static::$internal_interface['tables'] as $table_name => $details) {
		// these are hard coded into the file, so we don't need to worry about cleaning the input, it's trusted
		$query = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (';
		$lines = array();
		foreach ($details['fields'] as $field_name => $options) {
			$lines[] = $field_name.' '.implode(' ', $options);
		}
		if (!empty($details['indexes'])) {
			foreach ($details['indexes'] as $index) {
				$line = $index['index_type'].' ('.implode(', ', $index['fields']).')';
				if (!empty($index['reference_definition'])) $line .= ' '.implode(' ', $index['reference_definition']);
				$lines[] = $line;
			}
		}
		$query .= implode(', ', $lines).')';
		$query .= ' '.implode(' ', $details['options']);

		prepared_query::execute($query);
	}
}

}
?>