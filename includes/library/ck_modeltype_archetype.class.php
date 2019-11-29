<?php
abstract class ck_modeltype_archetype {
	private $id;
	private $persistent;

	/* -------------------------------
	//protected static $format is defined per extending class - this could also be its own class, but that means we'd need some runtime processing to assign it
	// available options in $format:
	//  * cardinality
	//  * loose [flag] - accept data in what appears to be an unaccepted format, e.g. assign an array to SINGLE
	//  * data_type [validation]
	//  * range [validation]
	//     * possible valid range combos:
	//     * min/max
	//     * max/dec
	//     * max_length
	//  * coerce [flag] [validation] coerce/cast bool and datetime datatimes when validating
	//  * class [validation - for object data types]
	//  * columns - a list of column names to use in a row or row set - each potentially with their own column-specific format
	//     * each column could have the following format specified:
	//     * optional - if it's not included in the entered data, don't even create the empty column in the resulting data
	//     * default - if empty, use this default
	//     * not_null - if empty, complain
	//     * format - specifically, supports the top level format options for [validation]
	//  * keyed_set - the set of rows is not a numeric array indexed from 0, but indexed by a unique key relevant to the row data
	//  * key_column - the column data to use as the row key for a keyed_set - if not specified, it will use whatever key is passed in with the row
	//  * sets - a list of set names to use in a map of complex data - each potentially with their own format identical to this format list, recursively
	//  * set_cardinality - if we don't have a named list of sets for a map, but we do have a named list of columns, we can define the cardinality of the set to be a single row or set of rows
	------------------------------- */

	private $data = [];
	private $properties = [];

	private $change_cache = [];

	private $changes = [];
	private $create = FALSE;

	public static function instance($id=NULL, $persistent=TRUE) {
		$instance = NULL;

		if (is_null($id)) $instance = new static(NULL, $persistent);
		else {
			$registry = self::get_registry();
			if ($registry::record_exists(static::class, $id)) $instance = $registry::get_record(static::class, $id);
			else $instance = new static($id, $persistent);
		}

		return $instance;
	}

	public static function persistent_instance($id=NULL) {
		return self::instance($id, TRUE);
	}

	public static function temp_instance($id=NULL) {
		return self::instance($id, FALSE);
	}

	private function __construct($id=NULL, $persistent=TRUE) {
		$this->id = $id;
		$this->persistent = $persistent;

		$this->persist();

		$this->init();
	}

	public function __clone() {
		// if we're cloning this object, we *must* treat it as a new record
		$this->id = NULL;
		$this->changes = [];
	}

	// if anything needs to be set up, we have a function to set it up
	abstract protected function init();

	public function get_active_controller() {
		if (!self::active_controller_isset()) {
			if (!empty(static::$controller_class)) self::set_active_controller(new static::$controller_class);
			else throw new CKModeltypeArchetypeException('No active controller set for this class.');
		}
		return self::$active_controllers[static::class]->set_active_model($this);
	}

	private function persist() {
		if ($this->persistent && !empty($this->id())) {
			$registry = self::get_registry();
			$registry::register(static::class, $this->id(), $this);
		}
	}

	public function create($set=FALSE) {
		if (!$set) return $this->create;
		elseif (!empty($this->id())) throw new CKModeltypeArchetypeException('Cannot recreate model with an existing ID');
		else return $this->create = TRUE;
	}

	public function set_id($id) {
		if (!empty($this->id())) throw new CKModeltypeArchetypeException('Cannot change ID');
		$this->id = $id;

		$this->create = FALSE;

		$this->persist();
	}

	public function id() {
		return $this->id;
	}

	public function __toString() {
		return $this->id();
	}

	public function load($element, $data) {
		// if we've already loaded this data, we cannot load it again without first unloading it
		if ($this->is_loaded($element)) return FALSE;

		$loader = self::get_loader();

		if (is_null($this->data)) $this->data = [];

		if (isset(static::$format[$element])) $loader::load(static::$format[$element], $this->data, $element, $data);
		else throw new CKModeltypeArchetypeException('Element ['.$element.'] is not defined, cannot load data.');

		return $this->data[$element];
	}

	public function is_loaded($element=NULL) {
		if (empty($element)) return !empty($this->data);
		else return array_key_exists($element, $this->data);
	}

	public function unload($element=NULL) {
		if (empty($element)) unset($this->data);
		else unset($this->data[$element]);
	}

	public function get_prop($key) {
		return isset($this->properties[$key])?$this->properties[$key]:NULL;
	}

	public function get_props() {
		return $this->properties;
	}

	public function set_prop($key, $val) {
		return $this->properties[$key] = $val;
	}

	public function set_props(Array $props) {
		foreach ($props as $key => $val) {
			$this->set_prop($key, $val);
		}
	}

	public function isset_prop($key) {
		return isset($this->properties[$key]);
	}

	public function unset_prop($key) {
		unset($this->properties[$key]);
	}

	public function __sleep() {
		return ['data', 'properties'];
	}

	public function get($element, $key=NULL) {
		if (is_null($key)) return $this->data[$element];
		else return $this->data[$element][$key];
	}

	public function has($element, $key=NULL) {
		if (is_null($key)) return !empty($this->data[$element]);
		else return !empty($this->data[$element][$key]);
	}

	public function is($element, $key) {
		return CK\fn::check_flag($this->data[$element][$key]);
	}

	public function found($element=NULL, $key=NULL) {
		if (is_null($key)) return $this->is_loaded($element);
		else return array_key_exists($key, $this->data[$element]);
	}

	public function change($element, $data) {
		// we do more than just set the data, we need to know we intended to change it
		//if (!$this->is_loaded($element)) return $this->load($element, $data);

		$loader = self::get_loader();

		if (isset(static::$format[$element])) {
			// we may need to pass this into the data loader to make the resulting structure both more specific and more efficient
			// but we'll optimize when/if necessary
			$this->change_cache[$element] = @$this->data[$element]; // if we haven't loaded anything, this may not yet exist.

			$changes = $loader::change(static::$format[$element], $this->data, $element, $data);

			if (empty($this->changes[$element])) $this->changes[$element] = [];

			$this->changes[$element] = array_merge($this->changes[$element], $changes);
		}
		else throw new CKModeltypeArchetypeException('Element ['.$element.'] is not defined, cannot change data.');

		return $this->data[$element];
	}

	public function has_changes() {
		return !empty($this->changes);
	}

	public function get_changes() {
		return $this->changes;
	}

	public function check_change($element, Array $navigate=[]) {
		array_unshift($navigate, $element);
		$cached = $this->change_cache; // if we haven't changed this element, then NULL is a fine response

		foreach ($navigate as $key) {
			$cached = is_array($cached)&&array_key_exists($key, $cached)?$cached[$key]:NULL;
		}

		return $cached;
	}

	public function clear_changes() {
		$this->changes = [];
		$this->change_cache = [];
	}

	public static function remap($element, $data) {
		if (!empty(static::$format[$element]['db_map'])) {
			foreach (static::$format[$element]['db_map'] as $field => $map) {
				if (!isset($data[$field])) continue;
				$data[$map] = $data[$field];
				unset($data[$field]);
			}
		}

		return $data;
	}

	private static $loader;

	public static function set_loader($loader) {
		return self::$loader = $loader;
	}

	public static function get_loader($loader=NULL) {
		if (!empty($loader)) return $loader;
		if (!empty(self::$loader)) return self::$loader;
		return 'ck_data_loader';
	}

	private static $registry;

	public static function set_registry($registry) {
		return self::$registry = $registry;
	}

	public static function get_registry($registry=NULL) {
		if (!empty($registry)) return $registry;
		if (!empty(self::$registry)) return self::$registry;
		return 'ck_modeltype_registry';
	}

	private static $active_controllers = [];

	public static function set_active_controller(ck_model_archetype $controller) {
		self::$active_controllers[static::class] = $controller;
	}

	protected static function active_controller_isset() {
		return isset(self::$active_controllers[static::class]);
	}
}

class CKModeltypeArchetypeException extends Exception {
}
?>
