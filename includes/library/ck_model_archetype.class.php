<?php
abstract class ck_model_archetype extends ck_master_archetype {
	private $active_model;
	private static $default_element = 'header';

	public static function init_with_model(ck_modeltype_archetype $model) {
		$self = new static();
		$self->set_active_model($model);

		return $self;
	}

	final public function __construct() {
		$this->init();
	}

	// if anything needs to be set up, we have a function to set it up
	abstract protected function init();

	protected function write() {
		if ($this->active_model->has_changes()) {
			$this->commit_changes($this->active_model->get_changes());
			$this->active_model->clear_changes();
		}
	}

	// run whatever specific actions we need to run to commit our found changes
	abstract protected function commit_changes($changes);

	public function get_active_model() {
		return $this->active_model;
	}

	public function set_active_model(ck_modeltype_archetype $model) {
		$this->active_model = $model;
		return $this;
	}

	public function has_active_model() {
		return !empty($this->active_model);
	}

	public function unset_active_model() {
		$this->active_model = NULL;
	}

	public function id() {
		return $this->active_model->id();
	}

	protected function set_id($id) {
		return $this->active_model->set_id($id);
	}

	public function __toString() {
		return $this->id();
	}

	public function __sleep() {
		return ['active_model'];
	}

	protected function create($set=FALSE) {
		return $this->active_model->create($set);
	}

	public function is($flag, $element=NULL) {
		$element = $element?:self::$default_element;
		return $this->active_model->is($element, $flag);
	}

	public function has($key, $element=NULL) {
		$element = $element?:self::$default_element;
		return $this->active_model->has($element, $key);
	}

	public function get($key, $element=NULL) {
		$element = $element?:self::$default_element;
		return $this->active_model->get($element, $key);
	}

	public function get_prop($key) {
		return $this->active_model->get_prop($key);
	}

	public function get_props() {
		return $this->active_model->get_props();
	}

	public function set_prop($key, $val) {
		return $this->active_model->set_prop($key, $val);
	}

	public function set_props(Array $props) {
		$this->active_model->set_props($props);
	}

	public function isset_prop($key) {
		return $this->active_model->isset_prop($key);
	}

	public function unset_prop($key) {
		$this->active_model->unset_prop($key);
	}

	protected function load($element, $data=NULL) {
		return $this->active_model->load($element, $data);
	}

	protected function reload($element, $data=NULL) {
		$this->unload($element);
		$this->load($element, $data);
	}

	protected function is_loaded($element) {
		return $this->active_model->is_loaded($element);
	}

	protected function unload($element=NULL) {
		return $this->active_model->unload($element);
	}

	protected function change($element, $data=NULL) {
		return $this->active_model->change($element, $data);
	}

	protected function check_change($element, Array $navigate=[]) {
		return $this->active_model->check_change($element, $navigate);
	}
}

class CKModelArchetypeException extends Exception {
}
?>
