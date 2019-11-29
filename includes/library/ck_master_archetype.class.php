<?php

use Zend\Db\ResultSet\ResultSet;

abstract class ck_master_archetype {
	// this will be necessary for pretty much any model, controller, etc we create

	private static $debug = FALSE;
	protected static $debug_next_query = FALSE;

	public static function begin_debugging() {
		self::$debug = TRUE;
	}

	public static function end_debugging() {
		self::$debug = FALSE;
	}

	public static function debugging() {
		return self::$debug;
	}

	public function __destruct() {
	}

	// this is intended to be overridden by extending classes, but since we're just adding it now, by default everything is permitted
	public function is_permitted() {
		return TRUE;
	}

	// cardinalities: var (fetchColumn(0)), column (fetchColumn()), row (fetch()), set (fetchAll())
	protected static function fetch($type, $criteria=[], $db=NULL) {
		return self::query_fetch(static::$queries[$type]['qry'], static::$queries[$type]['cardinality'], $criteria, $db);
	}

	protected static function modify_query($type, $adds=[]) {
		if (empty(static::$queries[$type]['proto_qry'])) trigger_error('Query Prototype does not exist for '.get_called_class().'::'.$type.' - cannot modify query');

		$proto = static::$queries[$type]['proto_qry'];
		$defaults = !empty(static::$queries[$type]['proto_defaults'])?static::$queries[$type]['proto_defaults']:[];
		$query = '';

		// `isset() || array_key_exists()` is the most efficient key check - it's a little ugly, but this whole process is about providing flexibility without overhead

		// even though this one will never need the extra space, use it for consistency - extra spaces don't hurt
		if (isset($proto['data_operation']) || array_key_exists('data_operation', $proto)) $query .= ' '.$proto['data_operation'];
		if (isset($adds['data_operation']) || array_key_exists('data_operation', $adds)) $query .= ' '.$adds['data_operation'];
		elseif (!empty($defaults['data_operation'])) $query .= ' '.$defaults['data_operation'];

		if (isset($proto['set']) || array_key_exists('set', $proto)) $query .= ' '.$proto['set'];
		if (isset($adds['set']) || array_key_exists('set', $adds)) $query .= ' '.$adds['set'];
		elseif (!empty($defaults['set'])) $query .= ' '.$defaults['set'];

		if (isset($proto['values']) || array_key_exists('values', $proto)) $query .= ' '.$proto['values'];
		if (isset($adds['values']) || array_key_exists('values', $adds)) $query .= ' '.$adds['values'];
		elseif (!empty($defaults['values'])) $query .= ' '.$defaults['values'];

		if (isset($proto['from']) || array_key_exists('from', $proto)) $query .= ' '.$proto['from'];
		if (isset($adds['from']) || array_key_exists('from', $adds)) $query .= ' '.$adds['from'];
		elseif (!empty($defaults['from'])) $query .= ' '.$defaults['from'];

		if (isset($proto['where']) || array_key_exists('where', $proto)) $query .= ' '.$proto['where'];
		if (isset($adds['where']) || array_key_exists('where', $adds)) $query .= ' '.$adds['where'];
		elseif (!empty($defaults['where'])) $query .= ' '.$defaults['where'];

		if (isset($proto['order_by']) || array_key_exists('order_by', $proto)) $query .= ' '.$proto['order_by'];
		if (isset($adds['order_by']) || array_key_exists('order_by', $adds)) $query .= ' '.$adds['order_by'];
		elseif (!empty($defaults['order_by'])) $query .= ' '.$defaults['order_by'];

		if (isset($proto['limit']) || array_key_exists('limit', $proto)) $query .= ' '.$proto['limit'];
		if (isset($adds['limit']) || array_key_exists('limit', $adds)) $query .= ' '.$adds['limit'];
		elseif (!empty($defaults['limit'])) $query .= ' '.$defaults['limit'];

		$query_key = hash('sha256', $query);

		$cardinality = isset($adds['cardinality'])||array_key_exists('cardinality', $adds)?$adds['cardinality']:static::$queries[$type]['cardinality'];

		if (empty(static::$queries[$query_key])) static::$queries[$query_key] = array('qry' => $query, 'cardinality' => $cardinality, 'stmt' => NULL);

		return $query_key;
	}

	// this is a separate function to enforce a little extra safety - we know we're running a generic query rather than one we've rolled up to the class
	protected static function query_fetch($qry, $cardinality, $criteria, $db=NULL) {
		if (self::$debug_next_query) prepared_query::debug_next_query();

		return prepared_query::fetch($qry, $cardinality, $criteria);
	}


	// alias for fetch, so we can use whatever nomenclature makes the most sense
	// it probably makes more sense from a organizational standpoint to flip these
	// but fetch is likely to be called more often, so we'll remove one level of abstraction
	protected static function execute($type, $criteria=[], $db=NULL) {
		return self::fetch($type, $criteria, $db);
	}

	protected static function query_execute($qry, $cardinality=cardinality::NONE, $criteria=[], $db=NULL) {
		return prepared_query::execute($qry, $criteria);
	}

	protected static $out_of_transaction_queries = [];

	protected static function query_out_of_transaction_execute($qry, $criteria=[], $savepoint_id=NULL) {
		prepared_query::execute_after_transaction($qry, $criteria, $savepoint_id);
	}

	protected static function filter_fields(Array $data, Array $list, $blacklist=FALSE) {
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

	protected static function debug_query($qry, $criteria=NULL) {
		if (empty(static::$queries[$qry])) $qry_key = hash('sha256', $qry);
		else $qry_key = $qry;

		echo '[Query Key: '.$qry_key.']';

		if (empty(static::$queries[$qry_key])) echo '[Not Saved: '.$qry.']';
		else echo '[Saved: '.static::$queries[$qry_key]['qry'].']';

		if (!isset(static::$queries[$qry_key])) echo '[No Cardinality]';
		else echo '[Cardinality: '.cardinality::lookup(static::$queries[$qry_key]['cardinality']).']';

		if (empty(static::$queries[$qry_key]['stmt'])) echo '[Not Compiled]';
		else echo '[Compiled]';

		var_dump($criteria);
	}

	protected static function fetch_insert_id() {
		return prepared_query::fetch_insert_id();
	}

	protected static function validate_query_identifier($identifier) {
		return !preg_match('/[^0-9a-zA-Z$_]/', $identifier);
	}

	protected static function get_safe_savepoint_id($savepoint_id=NULL) {
		return prepared_query::get_safe_savepoint_id($savepoint_id);
	}

	public static function add_savepoint($savepoint_id, $friendly=FALSE) {
		return prepared_query::add_savepoint($savepoint_id, TRUE);
	}

	public static function set_all_savepoints(Array $savepoints, $friendly=FALSE) {
		return prepared_query::set_all_savepoints($savepoints, TRUE);
	}

	protected static function transaction_begin($savepoint_id=NULL) {
		return prepared_query::transaction_begin($savepoint_id);
	}

	protected static function transaction_commit($savepoint_id=NULL) {
		return prepared_query::transaction_commit($savepoint_id);
	}

	protected static function transaction_rollback($savepoint_id=NULL) {
		return prepared_query::transaction_rollback($savepoint_id);
	}

	protected static function transaction_end($commit=NULL, $savepoint_id=NULL) {
		return prepared_query::transaction_end($commit, $savepoint_id);
	}

	public static function fail_transaction() {
		return prepared_query::fail_transaction();
	}

	public static function set_transaction_status($status, $friendly=FALSE) {
		return prepared_query::set_transaction_status($status, TRUE);
	}

	public static function get_transaction_status($success=FALSE) {
		return prepared_query::get_transaction_status($success);
	}

	public static function transaction_status_isset() {
		return prepared_query::transaction_status_isset();
	}

	protected static function in_transaction($savepoint_id=NULL) {
		return prepared_query::in_transaction($savepoint_id);
	}

	public static function get_transaction_context() {
		return prepared_query::get_transaction_context();
	}

	public static function set_transaction_context($in_transaction, $friendly=FALSE) {
		return prepared_query::set_transaction_context($in_transaction, TRUE);
	}

	protected static $now;

	public static function NOW($reset=FALSE) {
		if (empty(self::$now) || $reset) self::$now = new DateTime;
		return self::$now;
	}

	public static function DateTime($datetime): DateTime {
		return $datetime instanceof DateTime?$datetime:new DateTime($datetime);
	}

	public static function date_is_empty($date_string) {
		return empty($date_string)||in_array($date_string, ['0000-00-00', '0000-00-00 00:00:00', '00:00:00']);
	}

	// wrangle the database, if we want to set it explicitly for the call or the class (otherwise, fall back to the global instance)
	protected static $db = NULL;
	public static function set_db($db) {
		static::$db = $db;
	}

	/**
	 * @deprecated
	 * @param null $db
	 * @return db_service_interface
	 */
	protected static function get_db($db=NULL): db_service_interface {
		return service_locator::get_db_service();
	}
}

class CKMasterArchetypeException extends Exception {
	public function __construct($message='', $code=0, $previous=NULL) {
		//parent::__construct('['.$this->get_calling_class().'] '.$message, $code, $previous);
		parent::__construct($message, $code, $previous);
	}

	public function get_calling_class() {
		$trace = debug_backtrace(FALSE);
		
		$idx = 3;
		while (empty($trace[$idx]) && $idx > 0) $idx--;

		$caller = @$trace[$idx];

		return $caller['class'];
	}
}
?>
