<?php
class journal {

private $transaction;
private $page;
public $data = array();
public $db;

public function __construct($resource=NULL, $transaction=NULL) {
	$this->transaction = $transaction?$transaction:microtime();
	$this->page = $_SERVER['PHP_SELF'];
	if (!empty($resource)) {
		foreach ($resource as $key => $val) {
			$this->parse_structures($key, $val);
		}
	}
}

public function record($db=NULL) {
	if (!$db && !$this->db) return FALSE;
	if (!$db) $db = $this->db;
	$session_id = session_id();
	$qry = $db->prep('INSERT INTO order_journal (session_id, transaction_id, var_key, var_value, page) VALUES (?, ?, ?, ?, ?)');
	foreach ($this->data as $key => $val) {
		if (preg_match('/cc_number/i', $key)) $val = 'REMOVED';
		$db->query($qry, array($session_id, $this->transaction, $key, $val, $this->page));
	}
	$db->unprep($qry);
	$this->data = array();
}

public function parse_structures($key, $val) {
	if (empty($val)) $this->$key = 'NULL';
	elseif (is_scalar($val)) $this->$key = $val;
	elseif (is_array($val)) {
		foreach ($val as $skey => $sval) {
			$this->parse_structures($key.'__'.$skey, $sval);
		}
	}
	elseif (is_object($val)) {
		$this->parse_structures($key.'__PRETTYPRINT', print_r($val, TRUE));
		foreach ($val as $prop => $pval) {
			$this->parse_structures($key.'__'.$prop, $pval);
		}
	}
	else $this->$key = gettype($val);
}

public function __get($key) {
	return $this->data[$key];
}

public function __set($key, $val) {
	return $this->data[$key] = $val;
}

public function __destruct() {
	if (!empty($this->data) && $this->db) $this->record();
}

}
?>