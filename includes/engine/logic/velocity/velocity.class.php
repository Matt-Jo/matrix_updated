<?php
class velocity extends ck_channel {

// certain properties may be defined with the methods where they are relevant

// attributes
private $actions = array();
private $units = array();

// traits... when we move to PHP 5.4 we can move these elements out to a trait and use it here
protected static $fields;
protected static $primary_identifier;

public function __construct($details, $actions=NULL, $units=NULL) {
	parent::__construct();
	self::init_fields();
	// set up the details array with its keys up front
	foreach (self::$fields as $field) $this->details[$field] = NULL;

	if (is_numeric($details)) $details = array('velocity_stage_id' => $details);
	elseif (is_string($details)) $details = array('identifier' => $details);
	if (is_object($details)) $details = (array) $details;

	if (is_array($details)) {
		foreach ($details as $key => $val) {
			$this->$key = $val;
		}
	}
	else {
		$this->construct_details = $details;
	}

	$this->set_context(TRUE); // if we're missing any fields, grab them from the database, if we're in an UPDATE context
	$this->original = $this->details; // record our original state so we know what's updated

	if ($actions === TRUE) {
		$this->get_actions();
	}
	elseif (is_array($actions)) $this->actions = $actions;

	if ($units === TRUE) {
		$this->get_units();
	}
	elseif (is_array($units)) $this->units = $units;
}

// figure out if we've currently got enough details to identify this stage
// we override the identified method because we might identify off of the unique "identifier" field, which isn't present in all classes
public function identified($exact=FALSE) {
	if (isset($this->velocity_stage_id)) return TRUE;
	// if the calling method shouldn't affect stored values (mainly magic methods), then exact will be set and we'll skip this section
	// otherwise, fill in the velocity stage id if we can
	elseif (!$exact && isset($this->identifier)) {
		// consider pulling more details than just the primary key here
		if ($this->velocity_stage_id = prepared_query::fetch('SELECT velocity_stage_id FROM ck_velocity_stages WHERE identifier = ?', cardinality::SINGLE, array($this->identifier))) return TRUE;
		else return FALSE;
	}
	else return FALSE;
}

// get actions defined for this stage
public function get_actions($db=NULL) {
	if (!$this->identified()) return FALSE;

	if ($actions = prepared_query::fetch('SELECT * FROM ck_velocity_actions WHERE velocity_stage_id = ?', cardinality::SET, array($this->velocity_stage_id))) return $this->actions = $actions;
	else return NULL;
}

// get units currently assigned to this stage
public function get_units($db=NULL) {
	if (!$this->identified()) return FALSE;

	if ($units = prepared_query::fetch('SELECT * FROM ck_velocity_units WHERE velocity_stage_id = ?', cardinality::SET, array($this->velocity_stage_id))) return $this->units = $units;
	else return NULL;
}

// static/helper functions
// get a list of stages that we can work with
public static function _list($criteria=NULL, $db=NULL) {
	// use dependancy injection, but fall back on the global value

	// if the criteria passed is an object or scalar, cast to array
	$criteria = self::format_details($criteria);

	// we're going to ignore criteria for now, as we don't yet know how or under what circumstances we'd want to use it
	if ($stages = prepared_query::fetch('SELECT * FROM ck_velocity_stages ORDER BY stock_days ASC, ordinal ASC, serialized ASC', cardinality::SET)) {
		foreach ($stages as $idx => &$stage) {
			$stage = new self($stage);
		}
		unset($stage); // just a precaution here
	}
	return $stages;
}

// interface details
public static function init_interface() {
	if (file_exists(__DIR__.'/'.__CLASS__.'.json')) {
		$json = file_get_contents(__DIR__.'/'.__CLASS__.'.json');
		if (is_null((self::$internal_interface = @json_decode($json, TRUE)))) {
			// not valid json
			return FALSE;
		}
	}
}
// we've created a json of these details, we init with init_interface(), called immediately when the file is included
protected static $internal_interface; /* = array(
	'primary_table' => 'ck_velocity_stages',
	'tables' => array(
		'ck_velocity_stages' => array(
			'fields' => array(
				'velocity_stage_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => 'AUTO_INCREMENT'),
				'identifier' => array('type' => 'VARCHAR(255)', 'required' => 'NOT NULL', 'default' => NULL),
				'description' => array('type' => 'TEXT', 'required' => 'NULL', 'default' => NULL),
				'stock_days' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => NULL),
				'use_aging' => array('type' => 'TINYINT', 'required' => 'NOT NULL', 'default' => 'DEFAULT 0'),
				'ordinal' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => 'DEFAULT 0'),
				'serialized' => array('type' => 'TINYINT', 'required' => 'NULL', 'default' => NULL),
				'active' => array('type' => 'TINYINT', 'required' => 'NOT NULL', 'default' => 'DEFAULT 1'),
				'entered' => array('type' => 'TIMESTAMP', 'required' => 'NOT NULL', 'default' => 'DEFAULT CURRENT_TIMESTAMP')
			),
			'indexes' => array(
				array(
					'index_type' => 'PRIMARY KEY',
					'fields' => array('velocity_stage_id')
				),
				array(
					'index_type' => 'UNIQUE INDEX',
					'fields' => array('identifier')
				)
			),
			'options' => array('engine' => 'ENGINE=INNODB', 'charset' => 'CHARACTER SET utf8', 'collation' => 'COLLATE utf8_bin')
		),
		'ck_velocity_actions' => array(
			'fields' => array(
				'velocity_action_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => 'AUTO_INCREMENT'),
				'velocity_stage_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => NULL),
				'ordinal' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => 'DEFAULT 0'),
				'action' => array('type' => 'VARCHAR(255)', 'required' => 'NOT NULL', 'default' => NULL),
				'details' => array('type' => 'TEXT', 'required' => 'NULL', 'default' => NULL),
				'auto_run' => array('type' => 'TINYINT', 'required' => 'NOT NULL', 'default' => 'DEFAULT 0'),
				'active' => array('type' => 'TINYINT', 'required' => 'NOT NULL', 'default' => 'DEFAULT 1'),
				'entered' => array('type' => 'TIMESTAMP', 'required' => 'NOT NULL', 'default' => 'DEFAULT CURRENT_TIMESTAMP')
			),
			'indexes' => array(
				array(
					'index_type' => 'PRIMARY KEY',
					'fields' => array('velocity_action_id')
				)
			),
			'options' => array('engine' => 'ENGINE=INNODB', 'charset' => 'CHARACTER SET utf8', 'collation' => 'COLLATE utf8_bin')
		),
		'ck_velocity_units' => array(
			'fields' => array(
				'velocity_unit_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => 'AUTO_INCREMENT'),
				'stock_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => NULL),
				'ipn' => array('type' => 'VARCHAR(255)', 'required' => 'NOT NULL', 'default' => NULL),
				'quantity' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => 1),
				'days_supply' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => NULL),
				'serial_id' => array('type' => 'INT', 'required' => 'NULL', 'default' => NULL),
				'serial_number' => array('type' => 'VARCHAR(255)', 'required' => 'NULL', 'default' => NULL),
				'receipt_date' => array('type' => 'DATE', 'required' => 'NULL', 'default' => NULL),
				'age' => array('type' => 'INT', 'required' => 'NULL', 'default' => NULL),
				'velocity_stage_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => NULL),
				'manual_date' => array('type' => 'DATE', 'required' => 'NULL', 'default' => NULL),
				'manual_fallback' => array('type' => 'TINYINT', 'required' => 'NULL', 'default' => NULL),
				'entered' => array('type' => 'TIMESTAMP', 'required' => 'NOT NULL', 'default' => 'DEFAULT CURRENT_TIMESTAMP')
			),
			'indexes' => array(
				array(
					'index_type' => 'PRIMARY KEY',
					'fields' => array('velocity_unit_id')
				)
			),
			'options' => array('engine' => 'ENGINE=INNODB', 'charset' => 'CHARACTER SET utf8', 'collation' => 'COLLATE utf8_bin')
		),
		'ck_velocity_action_queue' => array(
			'fields' => array(
				'velocity_action_queue_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => 'AUTO_INCREMENT'),
				'velocity_unit_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => NULL),
				'velocity_action_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => NULL),
				'queued_date' => array('type' => 'DATE', 'required' => 'NOT NULL', 'default' => NULL),
				'status' => array('type' => 'TINYINT', 'required' => 'NOT NULL', 'default' => 'DEFAULT 0'),
				'entered' => array('type' => 'TIMESTAMP', 'required' => 'NOT NULL', 'default' => 'DEFAULT CURRENT_TIMESTAMP')
			),
			'indexes' => array(
				array(
					'index_type' => 'PRIMARY KEY',
					'fields' => array('velocity_action_queue_id')
				)
			),
			'options' => array('engine' => 'ENGINE=INNODB', 'charset' => 'CHARACTER SET utf8', 'collation' => 'COLLATE utf8_bin')
		),
		'ck_velocity_action_log' => array(
			'fields' => array(
				'velocity_action_log_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => 'AUTO_INCREMENT'),
				'velocity_unit_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => NULL),
				'velocity_action_id' => array('type' => 'INT', 'required' => 'NOT NULL', 'default' => NULL),
				'action_date' => array('type' => 'DATE', 'required' => 'NOT NULL', 'default' => NULL),
				'details' => array('type' => 'TEXT', 'required' => 'NULL', 'default' => NULL),
				'removed' => array('type' => 'TINYINT', 'required' => 'NOT NULL', 'default' => 'DEFAULT 0'),
				'remove_date' => array('type' => 'DATE', 'required' => 'NULL', 'default' => NULL),
				'entered' => array('type' => 'TIMESTAMP', 'required' => 'NOT NULL', 'default' => 'DEFAULT CURRENT_TIMESTAMP')
			),
			'indexes' => array(
				array(
					'index_type' => 'PRIMARY KEY',
					'fields' => array('velocity_action_log_id')
				)
			),
			'options' => array('engine' => 'ENGINE=INNODB', 'charset' => 'CHARACTER SET utf8', 'collation' => 'COLLATE utf8_bin')
		)
	)
);*/

}
velocity::init_interface();
?>