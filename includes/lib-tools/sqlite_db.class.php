<?php
// copyright Jason Shinn, 2012
// right granted to CK for use perpetually, and to distribute as a component of a larger system without restriction
class sqlite_db {

public $dbh = NULL;
public $db = NULL;

public $active = NULL;

private $transaction_context = array();
public $transaction_error = FALSE;

private $prepared_statements = array();

private $last_query = '';
private $last_params = array();
private $last_result = array();
private $last_rows = 0;
public $insert_id = NULL;

public function __construct($db) {
	if (!class_exists('SQLite3')) {
		$this->active = FALSE;
		//new err(array('code' => 10, 'msg' => "WHOA! Our system seems to have lost it's database. Please refresh and hope you don't see this error again."), TRUE);
		return FALSE;
	}
	$this->active = TRUE;

	if ($db) $this->connect($db);
}

public function connect($db) {
	$this->db = $db;

	// Try to establish the server database handle
	if (!($this->dbh = new SQLite3($this->db))) {
		return FALSE;
	}

	return (bool) $this->dbh;
}

public function disconnect() {
	return $this->close();
}

public function close() {
	return $this->dbh->close();
}

public function escape($str) {
	return $this->dbh->escapeString(stripslashes($str));
}
public function unescape($str) {
	return stripslashes($str);
}

private function log_error($msg) {
	if ($this->transaction_context) $this->transaction_error = TRUE;
	$this->last_error = TRUE;
	//new err(array('code' => 10, 'msg' => $msg));
	return FALSE;
}

public function prep($query) {
	$id = $this->_marker();
	$this->prepared_statements[$id] = array('query' => $query, 'stmt' => @$this->dbh->prepare($query));
	if ($this->dbh->lastErrorCode()) return $this->log_error("Query Prepare error: ".$this->dbh->lastErrorMsg());
	return $id;
}
public function unprep($id) {
	if (isset($this->prepared_statements[$id])) {
		$this->prepared_statements[$id]['stmt']->close();
		unset($this->prepared_statements[$id]);
		return TRUE;
	}
	else {
		return FALSE;
	}
}

public function query($query, $parameters=array()) {
	if ($this->transaction_error) return $this->log_error('This transaction has been aborted.');
	// prepare
	$query = trim($query);

	// log query for debugging purposes
	$this->last_query = $query;
	$this->last_params = $parameters;
	// reset result of query
	$this->last_rows = 0;
	$this->last_result = array();
	$this->last_error = FALSE;
	$this->insert_id = NULL;

	// execute query
	if (!isset($this->dbh) || !$this->dbh) return FALSE;

	$prepared = NULL;
	if (isset($this->prepared_statements[$query])) {
		$prepared = TRUE;
		$this->last_query = $this->prepared_statements[$query]['query'];
		$stmt = $this->prepared_statements[$query]['stmt'];
		$stmt->clear();
	}
	else {
		$prepared = FALSE;
		$stmt = $this->dbh->prepare($query);
		if (!$stmt) return $this->log_error("Query Prepare error: ".$this->dbh->lastErrorMsg());
	}

	if (!empty($parameters)) {
		$params = array_keys($parameters);
		// if the parameters passed in are a zero based numerical list, bump them by one, otherwise use them directly
		$bump = TRUE;
		foreach ($params as $idx => $param) {
			if ($idx != $param) {
				$bump = FALSE;
				break;
			}
		}
		foreach ($parameters as $idx => $val) {
			if (is_array($val)) list($val, $type) = $val;
			if (isset($type) && $type) {
				if ($bump) $stmt->bindValue(($idx+1), $val, $type);
				else $stmt->bindValue($idx, $val, $type);
			}
			else {
				if ($bump) $stmt->bindValue(($idx+1), $val);
				else $stmt->bindValue($idx, $val);
			}
		}
	}
	$result = @$stmt->execute();

	// note any errors and exit if found
	if ($this->dbh->lastErrorCode()) return $this->log_error("Query Execute error: ".$this->dbh->lastErrorMsg());

	// parse results and set appropriate meta data
	if (!$result->numColumns()) {
		// no columns in the result means it's not a select
		$this->last_rows = $this->dbh->changes();

		if (preg_match('/^insert/i', $query)) $this->insert_id = $this->dbh->lastInsertRowID();

		$result->finalize();
		!$prepared?$stmt->close():NULL;
		return (bool) $this->last_rows;
	}
	else {
		// handle select results
		// cache query results & log the total number of rows returned
		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
			$this->last_result[] = (object) $row;
		}

		$this->last_rows = count($this->last_result);

		$result->finalize();
		!$prepared?$stmt->close():NULL;
		return (bool) $this->last_rows;
	}
}
public function get_var($query=NULL, $parameters=array(), $col=0, $row=0) {
	if ($query) $this->query($query, $parameters);

	// if this wasn't a select that populates last_result, or the requested row isn't populated, return NULL
	if (!$this->last_result || !isset($this->last_result[$row])) return NULL;
	$values = array_values(get_object_vars($this->last_result[$row]));
	return isset($values[$col])?$values[$col]:NULL;
}
public function get_row($query=NULL, $parameters=array(), $row=0) {
	if ($query) $this->query($query, $parameters);

	// if this wasn't a select that populates last_result, or the requested row isn't populated, return NULL
	if (!$this->last_result || !isset($this->last_result[$row])) return NULL;
	return $this->last_result[$row];
}
public function get_col($query=NULL, $parameters=array(), $col=0) {
	if ($query) $this->query($query, $parameters);

	// if this wasn't a select that populates last_result, or the requested row isn't populated, return NULL
	if (!$this->last_result || !isset($this->last_result[$row])) return NULL;
	$return_array = array();
	$result_found = FALSE;
	for ($i=0; $i<$this->last_rows; $i++) {
		$var = $this->get_var(NULL, $col, $i);
		$new_array[] = $var;
		if (!is_null($var)) $result_found = TRUE;
	}
	return $result_found?$new_array:NULL;
}
public function get_results($query=NULL, $parameters=array()) {
	if ($query) $this->query($query, $parameters);

	// if this wasn't a select that populates last_result, or the requested row isn't populated, return NULL
	if (!$this->last_result) return NULL;
	return $this->last_result;
}
public function fetchOne($query=NULL, $parameters=array()) {
	return $this->get_var($query, $parameters);
}
public function fetchRow($query=NULL, $parameters=array()) {
	return $this->get_row($query, $parameters);
}
public function fetchAll($query=NULL, $parameters=array()) {
	return $this->get_results($query, $parameters);
}

/* MANAGE TRANSACTIONS */
public function begin() {
	return $this->savepoint();
}
public function begin_transaction() {
	return $this->savepoint();
}
public function savepoint() {
	$id = $this->_marker();
	$this->transation_context[] = $id;
	$stmt = $this->dbh->exec("SAVEPOINT $id");
	return $id;
}

public function release($savepoint=NULL) {
	if (empty($savepoint)) {
		$savepoint = end($this->transaction_context);
		reset($this->transaction_context);
	}
	return $this->commit($savepoint);
}
public function commit($savepoint=NULL) {
	if (empty($savepoint)) {
		$this->transaction_context = array();
		$this->dbh->exec('COMMIT');
	}
	elseif (is_int($savepoint)) {
		for ($i=0; $i<$savepoint; $i++) {
			$id = array_pop($this->transaction_context);
		}
		if ($id) $this->dbh->exec("RELEASE $id");
		else $this->dbh->exec('COMMIT');
	}
	elseif (in_array($savepoint, $this->transaction_context)) {
		while ($this->transaction_context) {
			$id = array_pop($this->transaction_context);
			if ($id == $savepoint) break;
		}
		$this->dbh->exec("RELEASE $id");
	}
	else {
		return FALSE;
	}
	$this->transaction_error = FALSE;
	return TRUE;
}
public function rollback($savepoint=NULL) {
	if (empty($savepoint)) {
		$this->transaction_context = array();
		$this->dbh->exec('ROLLBACK');
	}
	elseif (is_int($savepoint)) {
		for ($i=0; $i<$savepoint; $i++) {
			$id = array_pop($this->transaction_context);
		}
		if ($id) $this->dbh->exec("ROLLBACK TO $id");
		else $this->dbh->exec('ROLLBACK');
	}
	elseif (in_array($savepoint, $this->transaction_context)) {
		while ($this->transaction_context) {
			$id = array_pop($this->transaction_context);
			if ($id == $savepoint) break;
		}
		$this->dbh->exec("ROLLBACK TO $id");
	}
	else {
		return FALSE;
	}
	$this->transaction_error = FALSE;
	return TRUE;
}

public function end_transaction() {
	if (!$this->transaction_error) return $this->commit();
	else return $this->rollback();
}

public function close_savepoint($savepoint=NULL) {
	if (!$this->transaction_error) return $this->release($savepoint);
	else return $this->rollback($savepoint);
}

private function _marker() {
	return uniqid('db_');
}

}
?>