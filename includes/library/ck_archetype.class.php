<?php
abstract class ck_archetype extends ck_master_archetype {

	protected $skeleton;
	protected $ad_hoc_props = [];

	protected static $registry = [];
	protected static $cacheless = [];
	protected static $global_cacheless = FALSE;

	public static function cache($state=TRUE) {
		if (static::class != self::class) {
			if (!CK\fn::check_flag($state)) self::$cacheless[static::class] = TRUE;
			else self::$cacheless[static::class] = FALSE;
		}
		else {
			if (!CK\fn::check_flag($state)) self::$global_cacheless = TRUE;
			else self::$global_cacheless = FALSE;
		}
	}

	public static function record_exists($key) {
		return !empty(static::$registry[static::class][$key]);
	}

	public static function destroy_record($key) {
		static::$registry[static::class][$key] = NULL;
		unset(static::$registry[static::class][$key]);
	}

	public static function get_record($key) {
		// we're using this method of instantiating and accessing objects in order to facillitate circular references: two objects reference each other
		// they don't need to care whether or not the companion object is already instantiated, they just request it upon need, and if it has already been
		// instantiated, then use the existing object

		if (!empty(self::$registry[static::class][$key])) return self::$registry[static::class][$key];
		else return new static::$skeleton_type($key); // it's our constructors job to fill in the registry
	}

	public static function register($key, $record) {
		if (self::$global_cacheless || !empty(self::$cacheless[static::class])) return;
		static::$registry[static::class][$key] = $record;
	}

	public static function load_resource($key, $data_key, $data) {
		//if (!self::record_exists($key)) throw new CKArchetypeException('['.static::class.']: Cannot load data; resource not found');
		$record = self::get_record($key);
		$record->rebuild($key);
		$record->load($data_key, $data);
	}

	public static function strict_load($model_id) {
	    // @todo: the following line could potentially introduce bugs if this class
        // @todo:   does not provide a uniform __constructor signature for every child class
		$obj = new static($model_id);
		if (!$obj->found()) return FALSE;
		else return $obj;
	}

	public function get_prop($key) {
		return isset($this->ad_hoc_props[$key])?$this->ad_hoc_props[$key]:NULL;
	}

	public function get_props() {
		return $this->ad_hoc_props;
	}

	public function set_prop($key, $val) {
		return $this->ad_hoc_props[$key] = $val;
	}

	public function set_props(Array $props) {
		foreach ($props as $key => $val) {
			$this->set_prop($key, $val);
		}
	}

	public function isset_prop($key) {
		return isset($this->ad_hoc_props[$key]);
	}

	public function unset_prop($key) {
		unset($this->ad_hoc_props[$key]);
	}

	public function found() {
		$this->get_header();
		return $this->skeleton->found();
	}

	// ->is() and ->has() will likely be implemented per class, unless everything fits this simple mode of a field in the header
	public function is($flag) {
		return CK\fn::check_flag($this->get_header($flag));
	}

	public function has($key) {
		return !empty($this->get_header($key));
	}

	// this is just an alias for `get_header()`, but it fits the whole is/has paradigm
	public function get($key) {
		return $this->get_header($key);
	}

	public function failover($input, $key, $entity) {
		// isset() is faster, but array_key_exists() will catch keys with null values
		if (isset($input[$key]) || array_key_exists($key, $input)) return $input[$key];
		else {
			if (!$this->skeleton->built($entity)) {
				$builder = 'build_'.$entity;
				$this->$builder();
			}
			return @$this->skeleton->get($entity)[$key];
		}
	}

	public function force_rebuild() {
		$this->skeleton->rebuild();
	}

	public function __sleep() {
		return ['skeleton'];
	}

	public function __toString() {
		if (method_exists($this, 'id')) return $this->id();
		else throw new CKArchetypeException('To String not implemented for '.get_class($this));
	}
}

class CKArchetypeException extends CkMasterArchetypeException {
	public function __construct($message='', $code=0, $previous=NULL) {
		parent::__construct('['.$this->get_calling_class().'] '.$message, $code, $previous);
	}
}
?>