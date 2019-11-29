<?php

use Zend\Db\Adapter\Driver;
use Zend\Db\ResultSet\AbstractResultSet;
use Zend\Db\ResultSet\ResultSet;

class prepared_query extends prepared_db {

	const TRANSACTION_CONTEXT_ON = 'TRANSACTION_CONTEXT_ON';
	const TRANSACTION_CONTEXT_OFF = 'TRANSACTION_CONTEXT_OFF';

	const TRANSACTION_STATUS_UNSET = 'TRANSACTION_STATUS_UNSET';
	const TRANSACTION_STATUS_SUCCESS = 'TRANSACTION_STATUS_SUCCESS';
	const TRANSACTION_STATUS_FAILURE = 'TRANSACTION_STATUS_FAILURE';

	protected static $last_query_key;

	protected static $sql_registry = [];
	protected static $qry_registry = [];
	protected static $in_transaction = self::TRANSACTION_CONTEXT_OFF;
	protected static $savepoints = [];

	protected static $transaction_status = self::TRANSACTION_STATUS_UNSET;
	protected static $soft_transaction_counter = 0;

	protected static $after_transaction_queries = [];

	protected static $debug = FALSE;
	protected static $debug_next_query = FALSE;
	protected static $debug_with_backtrace = FALSE;

	private static $log_queries = TRUE;
	private static $log_responses = FALSE;

	private static $log_idx = 0;

	private static $query_log = [];
	private static $response_log = [];

	private static $failed_query = [];

	public function __construct() {
		throw new PreparedQueryException('Cannot instantiate prepared_query.');
	}

	/*-------------------------------
	// logging
	-------------------------------*/

	public static function set_query_log($status) {
		if ($status === TRUE) self::$log_queries = TRUE;
		elseif ($status === FALSE) self::$log_queries = FALSE;
	}

	public static function set_response_log($status) {
		if ($status === TRUE) self::$log_responses = TRUE;
		elseif ($status === FALSE) self::$log_responses = FALSE;
	}

	public static function get_logs($idx=NULL) {
		if (is_numeric($idx)) return [self::get_query_log($idx) => self::get_response_log($idx)];
		else {
			$logs = [];
			foreach (self::get_query_log() as $idx => $query) {
				$logs[$query] = self::get_response_log($idx);
			}
			return $logs;
		}
	}

	public static function get_query_log($idx=NULL) {
		if (is_numeric($idx)) {
			if (!empty(self::$query_log[$idx])) return self::$query_log[$idx];
			else return NULL;
		}
		else return self::$query_log;
	}

	public static function get_response_log($idx=NULL) {
		if (is_numeric($idx)) {
			if (!empty(self::$response_log[$idx])) return self::$response_log[$idx];
			else return NULL;
		}
		else return self::$response_log;
	}

	public static function get_key($qry) {
		if (empty(self::$sql_registry[$qry])) return hash('sha256', $qry); // make a key out of the query, if we can't find it as an existing key
		else return $qry;
	}

	public static function get_failed_query() {
		return self::$failed_query;
	}

	/*-------------------------------
	// debugging
	-------------------------------*/

	public static function begin_debugging($backtrace=FALSE) {
		self::$debug = TRUE;
		self::$debug_with_backtrace = $backtrace;
	}

	public static function end_debugging() {
		self::$debug = FALSE;
		self::$debug_with_backtrace = FALSE;
	}

	public static function debugging() {
		return self::$debug || self::$debug_next_query;
	}

	public static function debug_next_query($backtrace=FALSE) {
		self::$debug_next_query = TRUE;
		self::$debug_with_backtrace = $backtrace;
	}

	public static function debug_query($qry, $cardinality, $criteria) {
		if (self::$debug_with_backtrace) {
			echo '<pre>';
			debug_print_backtrace();
			echo '</pre>';
		}
		var_dump(['query' => $qry, 'cardinality' => $cardinality, 'criteria' => $criteria]);
	}

	public static function debug_response($response) {
		var_dump(['response' => $response]);
	}

	/*-------------------------------
	// caching
	-------------------------------*/

	// this needs work
	public static function name_query($query_key, $query=NULL, $cardinality=NULL) {
		if (!cardinality::exists($cardinality)) {
			if (cardinality::exists($query)) {
				$cardinality = $query;
				$query = $query_key;
			}
			else $cardinality = cardinality::dflt();
		}

		if (empty($query)) $query = $query_key;

		$query_key = hash('sha256', $query_key);

		if (empty(self::$sql_registry[$query_key])) self::$sql_registry[$query_key] = ['qry' => $query, 'cardinality' => $cardinality];

		return $query_key; // return the hashed key
	}

	public static function lookup_query($query_key) {
		if (!empty(self::$sql_registry[$query_key])) return self::$sql_registry[$query_key];
		elseif (!empty(self::$sql_registry[hash('sha256', $query_key)])) return self::$sql_registry[hash('sha256', $query_key)];
		else return [];
	}

	public static function unname_query($query_key) {
		$query_key = hash('sha256', $query_key);

		self::$sql_registry[$query_key] = NULL;
		unset(self::$sql_registry[$query_key]);

		self::$qry_registry[$query_key] = NULL;
		unset(self::$qry_registry[$query_key]);
	}

	/*-------------------------------
	// manage clauses
	-------------------------------*/

	public static function paging($page=1, $page_size=NULL) {
		if (empty($page_size) || empty($page)) return '';
		elseif (!is_numeric($page) || !is_numeric($page_size)) throw new PreparedQueryException('['.$page.'] or ['.$page_size.'] are not valid values to limit upon.');
		else {
			$page--; // limits are zero-based
			return 'LIMIT '.($page*$page_size).', '.$page_size;
		}
	}

	/*-------------------------------
	// run queries
	-------------------------------*/

	public static function sim_fetch($qry, $cardinality=cardinality::SET, $criteria=[]) {
		if (empty(self::$sql_registry[$qry])) $query_key = hash('sha256', $qry); // make a key out of the query, if we can't find it as an existing key
		else $query_key = $qry;

		// allow defaulting to a set return and passing in criteria as the second parameter
		if (is_null($cardinality) || is_array($cardinality)) {
			$criteria = $cardinality;
			if (!empty(self::$sql_registry[$query_key])) $cardinality = self::$sql_registry[$query_key]['cardinality'];
			else $cardinality = cardinality::dflt();
		}

		if (!cardinality::exists($cardinality)) throw new PreparedQueryException('Result Cardinality ['.$cardinality.'] does not exist.');

		if (empty(self::$qry_registry[$query_key])) {
			if (!empty(self::$sql_registry[$query_key])) $qry = self::$sql_registry[$query_key]['qry'];
		}

		return ['query' => $qry, 'cardinality' => $cardinality, 'criteria' => $criteria];
	}


	/**
		* Given an sql query, this method will fetch results from db service
		*
		* @param $qry
		* @param int $cardinality
		* @param array $criteria
		* @return array|null
		* @throws PreparedQueryException
		*/
	public static function fetch(string $qry, $cardinality=cardinality::SET, $criteria=[]) {
		$db = self::get_db();

		if (self::debugging()) self::debug_query($qry, $cardinality, $criteria);

		$query_key = self::get_key($qry);

		// allow defaulting to a set return and passing in criteria as the second parameter
		if (is_null($cardinality) || is_array($cardinality)) {
			$criteria = $cardinality;
			if (!empty(self::$sql_registry[$query_key])) $cardinality = self::$sql_registry[$query_key]['cardinality'];
			else $cardinality = cardinality::dflt();
		}

		if (!is_array($criteria)) $criteria = [$criteria];

		if (!cardinality::exists($cardinality)) throw new PreparedQueryException('Result Cardinality ['.$cardinality.'] does not exist.');

		if (self::$log_queries) self::$query_log[self::$log_idx] = $qry;

		try {
			if (empty(self::$qry_registry[$query_key])) {
				if (!empty(self::$sql_registry[$query_key])) $qry = self::$sql_registry[$query_key]['qry'];
				self::$qry_registry[$query_key] = $db->create_statement($qry);
			}

			$result = self::$qry_registry[$query_key]->execute($criteria);
		}
		catch (Exception $e) {
			if (!empty(self::$sql_registry[$query_key])) $qry = self::$sql_registry[$query_key]['qry'];
			self::$failed_query['qry'] = $qry;
			self::$failed_query['cardinality'] = $cardinality;
			self::$failed_query['criteria'] = $criteria;

			throw $e;
		}

		$results = NULL;

		self::$last_query_key = $query_key;

		if ($result instanceof \Zend\Db\Adapter\Driver\ResultInterface) {
			$results = self::format_result($result, $cardinality);
		}

		if (self::$log_responses && $cardinality != cardinality::NONE) self::$response_log[self::$log_idx] = $results;
		self::$log_idx++;

		if (self::debugging()) self::debug_response($results);

		if (self::$debug_next_query) {
			self::$debug_next_query = FALSE;
			self::$debug_with_backtrace = FALSE;
		}

		return $results;
	}


	/**
		* $cardinality will modify the format of the result as follows:
		*
		*	  cardinality::SINGLE	 A single scalar value
		*	  cardinality::COLUMN	 A numerically indexed array of a single column - the column name is not returned
		*	  cardinality::ROW		An associative array of a single row, where each column is a named entry in the array
		*	  cardinality::SET		A numerically indexed array of ROW arrays - it's essentially the 2 dimensional table in full
		*	  cardinality::NONE	   When we don't expect any results (insert, update, delete), just return success
		*
		*
		* @param AbstractResultSet $results
		* @param int $cardinality
		* @return mixed
		*/
	public static function format_result(\Zend\Db\Adapter\Driver\ResultInterface $results, int $cardinality) {
		// if we don't expect any results (insert, update, delete), just return success
		if ($cardinality == cardinality::NONE) return NULL;

		/*
			* adapters
			*/

		$rs = new ResultSet();
		$rs->initialize($results);

		$toScalar = function($row) {
			$ret = current($row);
			while (!is_scalar($ret) && is_array($ret)) {
				$ret = current($ret);
			}
			return $ret;
		};

		$toColumn = function($row) {
			return array_values($row)[0];
		};

		switch ($cardinality) {
			case cardinality::SINGLE:
				// A single scalar value
				$results = $rs->current() !== null ? $rs->current()->getArrayCopy() : [];
				$results = current(array_map($toScalar, [$results])) ?? null;
				break;
			case cardinality::COLUMN:
				// A numerically indexed array of a single column - the column name is not returned
				$results = array_map($toColumn, $rs->toArray());
				break;
			case cardinality::ROW:
				// An associative array of a single row, where each column is a named entry in the array
				$results = $rs->current() !== null ? $rs->current()->getArrayCopy() : [];
				break;
			case cardinality::SET:
			default:
				// A numerically indexed array of ROW arrays - it's essentially the 2 dimensional table in full
				$results = $rs->toArray();
		}

		return $results;
	}


	public static function execute($qry, $criteria=[]) {
		return self::fetch($qry, cardinality::NONE, $criteria);
	}

	public static function exists($qry, $criteria=[]) {
		$limit = new prepared_limit(1);
		return !is_null(self::fetch($qry.' LIMIT '.$limit->limit(), cardinality::ROW, $criteria));
	}

	public static function execute_after_transaction($qry, $criteria=[], $savepoint_id=NULL, Callable $callback=NULL) {
		if (!self::in_transaction($savepoint_id)) {
			$result = self::execute($qry, $criteria);
			if (is_callable($callback)) $callback($result);
			else return $result;
		}
		elseif (!empty($savepoint_id)) {
			if (!isset(self::$after_transaction_queries[$savepoint_id])) self::$after_transaction_queries[$savepoint_id] = [];
			self::$after_transaction_queries[$savepoint_id][] = ['qry' => $qry, 'criteria' => $criteria, 'callback' => $callback];
		}
		else {
			if (!isset(self::$after_transaction_queries['all'])) self::$after_transaction_queries['all'] = [];
			self::$after_transaction_queries['all'][] = ['qry' => $qry, 'criteria' => $criteria, 'callback' => $callback];
		}
	}

	private static function transaction_end_execute($savepoint_id=NULL) {
		if (!self::in_transaction($savepoint_id)) return NULL;

		$working_savepoints = self::$savepoints;

		do {
			$savepoint_id_try = array_pop($working_savepoints);

			if (!empty(self::$after_transaction_queries[$savepoint_id_try])) {
				foreach (self::$after_transaction_queries[$savepoint_id_try] as $qry) {
					$result = self::execute($qry['qry'], $qry['criteria']);
					$cb = $qry['callback'];
					if (is_callable($cb)) $cb($result);
				}
				self::$after_transaction_queries[$savepoint_id_try] = NULL;
				unset(self::$after_transaction_queries[$savepoint_id_try]);
			}
		}
		while ($savepoint_id_try != $savepoint_id && !empty($working_savepoints));

		if (empty($savepoint_id)) $savepoint_id = 'all';

		// this is made to catch 'all' queries
		if (!empty(self::$after_transaction_queries[$savepoint_id])) {
			foreach (self::$after_transaction_queries[$savepoint_id] as $qry) {
				$result = self::execute($qry['qry'], $qry['criteria']);
				$cb = $qry['callback'];
				if (is_callable($cb)) $cb($result);
			}
			self::$after_transaction_queries[$savepoint_id] = NULL;
			unset(self::$after_transaction_queries[$savepoint_id]);
		}
	}

	public static function keyed_set_fetch($qry, $key, $criteria=[]) {
		$results = self::fetch($qry, cardinality::SET, $criteria);

		$new_results = [];

		foreach ($results as $result) {
			$new_results[$result[$key]] = $result;
		}

		return $new_results;
	}

	public static function keyed_set_group_fetch($qry, $key, $criteria=[]) {
		$results = self::fetch($qry, cardinality::SET, $criteria);

		$new_results = [];

		foreach ($results as $result) {
			if (empty($new_results[$result[$key]])) $new_results[$result[$key]] = [];
			$new_results[$result[$key]][] = $result;
		}

		return $new_results;
	}

	public static function keyed_set_value_fetch($qry, $key, $val, $criteria=[]) {
		$results = self::fetch($qry, cardinality::SET, $criteria);

		$new_results = [];

		foreach ($results as $result) {
			$new_results[$result[$key]] = $result[$val];
		}

		return $new_results;
	}

	public static function keyed_set_group_value_fetch($qry, $key, $val, $criteria=[]) {
		$results = self::fetch($qry, cardinality::SET, $criteria);

		$new_results = [];

		foreach ($results as $result) {
			if (empty($new_results[$result[$key]])) $new_results[$result[$key]] = [];
			$new_results[$result[$key]][] = $result[$val];
		}

		return $new_results;
	}

	public static function page_fetch($qry, $page=1, $page_size=NULL, $criteria=[]) {
		return self::fetch($qry.' '.self::paging($page, $page_size), cardinality::SET, $criteria);
	}

	public static function fetch_next($qry=NULL) {
		$query_key = !empty($qry)?self::get_key($qry):self::$last_query_key;
		$res = static::$qry_registry[$query_key]->fetch();
		return $res;
	}

	public static function insert($qry, $criteria=[]) {
		self::execute($qry, $criteria);
		return self::fetch_insert_id();
	}

	public static function fetch_insert_id() {
		$db = self::get_db();
		return $db->last_insert_id();
	}

	/*-------------------------------
	// transactions
	-------------------------------*/

	public static function get_safe_savepoint_id($savepoint_id=NULL) {
		// gets a valid, safe savepoint identifier
		if (!is_null($savepoint_id) && !prepared_fields::validate_identifier($savepoint_id)) throw new PreparedQueryException('['.$savepoint_id.'] is not a valid savepoint identifier.');
		return empty($savepoint_id)?preg_replace('/\./', '_', uniqid('sp_', TRUE)):$savepoint_id;
	}

	public static function add_savepoint($savepoint_id, $friendly=FALSE) {
		self::$savepoints[] = $savepoint_id;
	}

	public static function set_all_savepoints(Array $savepoints, $friendly=FALSE) {
		self::$savepoints = $savepoints;
	}

	public static function transaction_begin($savepoint_id=NULL) {
		if (self::in_transaction()) {
			$savepoint_id = self::get_safe_savepoint_id($savepoint_id);
			self::execute('SAVEPOINT '.$savepoint_id);
			self::add_savepoint($savepoint_id);
		}
		else {
			self::execute('START TRANSACTION');
			$savepoint_id = NULL;
		}

		self::set_transaction_context(self::TRANSACTION_CONTEXT_ON);

		return $savepoint_id;
	}

	public static function transaction_commit($savepoint_id=NULL) {
		self::transaction_end(TRUE, $savepoint_id);
	}

	public static function transaction_rollback($savepoint_id=NULL) {
		self::transaction_end(FALSE, $savepoint_id);
	}

	public static function transaction_end($commit=NULL, $savepoint_id=NULL) {
		if (!self::in_transaction($savepoint_id)) return;

		if ($commit !== TRUE && $commit !== FALSE) {
			if (self::transaction_status_isset()) $commit = self::get_transaction_status(TRUE);
			else $commit = TRUE;
		}

		self::clear_transaction_status();

		if (!empty($savepoint_id)) {
			$working_savepoints = self::$savepoints;

			do {
				$savepoint_id_try = array_pop($working_savepoints);

				self::transaction_end_execute($savepoint_id_try);

				if ($savepoint_id_try == $savepoint_id) {
					self::set_all_savepoints($working_savepoints);

					if ($commit) self::execute('RELEASE SAVEPOINT '.$savepoint_id);
					else self::execute('ROLLBACK TO '.$savepoint_id);
				}
			}
			while ($savepoint_id_try != $savepoint_id && !empty($working_savepoints));
		}
		else {
			if ($commit) self::execute('COMMIT');
			else self::execute('ROLLBACK');

			self::transaction_end_execute();

			self::set_all_savepoints([]);
			self::set_transaction_context(self::TRANSACTION_CONTEXT_OFF);
		}
	}

	public static function transaction_soft_begin() {
		self::$soft_transaction_counter++;
		if (self::in_transaction()) return TRUE;
		else return self::transaction_begin();
	}

	public static function transaction_soft_end($commit=NULL) {
		self::$soft_transaction_counter = max(0, self::$soft_transaction_counter-1);
		if (self::$soft_transaction_counter == 0) return self::transaction_end($commit);
	}

	public static function fail_transaction() {
		self::set_transaction_status(self::TRANSACTION_STATUS_FAILURE);
	}

	public static function set_transaction_status($status, $friendly=FALSE) {
		if (!in_array($status, [self::TRANSACTION_STATUS_SUCCESS, self::TRANSACTION_STATUS_FAILURE, self::TRANSACTION_STATUS_UNSET])) throw new PreparedQueryException('Transaction status ['.$status.'] is not valid.');
		self::$transaction_status = $status;
	}

	public static function get_transaction_status($success=FALSE) {
		if ($success) return self::$transaction_status == self::TRANSACTION_STATUS_SUCCESS;
		else return self::$transaction_status;
	}

	public static function transaction_status_isset() {
		return self::$transaction_status !== self::TRANSACTION_STATUS_UNSET;
	}

	public static function clear_transaction_status() {
		self::set_transaction_status(self::TRANSACTION_STATUS_UNSET);
	}

	public static function in_transaction($savepoint_id=NULL) {
		if (self::get_transaction_context() != self::TRANSACTION_CONTEXT_ON) return FALSE;
		elseif (!empty($savepoint_id)) return in_array($savepoint_id, self::$savepoints);
		else return TRUE;
	}

	public static function get_transaction_context() {
		return self::$in_transaction;
	}

	public static function set_transaction_context($in_transaction, $friendly=FALSE) {
		if (!in_array($in_transaction, [self::TRANSACTION_CONTEXT_ON, self::TRANSACTION_CONTEXT_OFF])) throw new PreparedQueryException('Transaction context ['.$in_transaction.'] is not valid.');
		self::$in_transaction = $in_transaction;
	}

	/*-------------------------------
	// db handle
	-------------------------------*/

	protected static $db = NULL;

	public static function set_db($db) {
		static::$db = $db;
	}

	protected static function get_db($db=NULL) {
		return $db ?? self::$db ?? service_locator::get_db_service() ?? NULL;
	}
}

class PreparedQueryException extends Exception {
}

