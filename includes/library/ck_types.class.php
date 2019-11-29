<?php
abstract class ck_types extends ck_master_archetype {

	protected function _init() {
		$data_stores = ['static' => ['lookup'], 'object' => ['structure']];
		foreach ($data_stores as $type => $stores) {
			foreach ($stores as $store) {
				if ($type == 'static') {
					if (!isset(static::$$store)) continue;
					$ds =& static::$$store;
				}
				else {
					if (!isset($this->$store)) continue;
					$ds =& $this->$store;
				}

				foreach ($ds as $key => $struct) {
					$ds[$key]['built'] = FALSE;
					if ($struct['cardinality'] == cardinality::SINGLE) {
						$ds[$key]['changes'] = NULL;
						$ds[$key]['data'] = NULL;
					}
					else {
						$ds[$key]['changes'] = [];
						$ds[$key]['data'] = [];
					}
				}

				unset($ds);
			}
		}
	}

	// for change tracking, we'll have to find some way to define whether this object is responsible for changes to a specific element or if there is a related object that controls that
	protected $changes = [];

	public function is_changed() {
		return !empty($this->changes);
	}

	public function found() {
		return !empty($this->structure['header']['data']);
	}

	// we'll need some way to access the changes - this might be a child type method

	public function rebuild($element=NULL) {
		if (!empty($element)) $this->structure[$element]['built'] = FALSE;
		else {
			foreach ($this->structure as $element => $details) {
				$this->structure[$element]['built'] = FALSE;
			}
		}
	}

	public function built($element) {
		return $this->structure[$element]['built'];
	}

	// this was in the work on the PO receiver - doesn't appear to be used yet.
	// in general it appears to be related to work on loading lookup table values
	protected function load_lookup($element, $data) {
		$this->load($element, $data, static::$lookup);
	}

	private function skip_optional_key($details, $arr, $keys) {
		return $details=='?'&&!isset($arr[$keys]);
	}

	public function load($element, $data, &$data_store=NULL) {
		if (empty($data_store)) $data_store =& $this->structure;
		if ($data_store[$element]['built']) return FALSE; // we can only load each element type once

		// reset to empty, so we can effectively rebuild the data
		// this may wholly supercede _init, I'll have to re-evaluate
		if ($data_store[$element]['cardinality'] == cardinality::SINGLE) $data_store[$element]['data'] = NULL;
		else $data_store[$element]['data'] = [];

		if (($data_store[$element]['cardinality'] == cardinality::SINGLE && is_null($data)) || ($data_store[$element]['cardinality'] != cardinality::SINGLE && empty($data))) {
			$data_store[$element]['built'] = TRUE;
			return TRUE;
		}

		$arr = is_array($data);
		switch ($data_store[$element]['cardinality']) {
			case cardinality::SINGLE:
				if ($arr) throw new CKTypeException('Array data is not valid for SINGLE data types: ['.$element.']');
				// we can assign directly
				// we should do extra work to validate data types
				$data_store[$element]['data'] = $data;
				break;
			case cardinality::COLUMN:
				if (!$arr) throw new CKTypeException('Array data is required for COLUMN data types: ['.$element.']');
				// we loop through the data rather than assigning directly to normalize the index to consecutive numerical indexing
				// we should do extra work to validate data types
				foreach ($data as $val) {
					$data_store[$element]['data'][] = $val;
				}
				break;
			case cardinality::ROW:
				if (!$arr) throw new CKTypeException('Array data is required for ROW data types: ['.$element.']');
				// we loop through the interface rather than assigning to discard any non-specified values
				// we should do extra work to validate required data is present and data types
				if (!empty($data_store[$element]['format'])) {
					foreach ($data_store[$element]['format'] as $key => $details) {
						if ($this->skip_optional_key($details, $data, $key)) continue;
						$data_store[$element]['data'][$key] = CK\fn::is_key_set($data, $key)?$data[$key]:$details;
					}
				}
				else {
					foreach ($data as $key => $value) {
						$row = [];
						$data_store[$element]['data'][$key] = $value;
					}
				}
				break;
			case cardinality::SET:
			default:
				if (!$arr) throw new CKTypeException('Array data is required for SET data types: ['.$element.']');
				// we loop through the data and the interface to normalize indexing and discard non-specified values
				// should do extra work to validate required data is present and data types
				foreach ($data as $line) {
					$row = [];
					foreach ($data_store[$element]['format'] as $key => $details) {
						if ($this->skip_optional_key($details, $line, $key)) continue;
						$row[$key] = CK\fn::is_key_set($line, $key)?$line[$key]:$details;
					}
					$data_store[$element]['data'][] = $row;
				}
				break;
			case cardinality::MAP:
				if (!$arr) throw new CKTypeException('Array data is required for MAP data types: ['.$element.']');
				// we loop through the data and the interface to discard non-specified keys or values
				// we should do extra work to validate required data is present and data types
				if (isset($data_store[$element]['key_format'])) {
					foreach ($data_store[$element]['key_format'] as $key => $details) {
						if (is_array($details)) {
							if (empty($data_store[$element]['format']) && CK\fn::is_key_set($data, $key) && is_array($data[$key])) {
								$data_store[$element]['data'][$key] = $data[$key];
							}
							elseif (CK\fn::is_key_set($data, $key)) {
								foreach ($data[$key] as $line) {
									$row = [];
									foreach ($data_store[$element]['format'] as $subkey => $details) {
										if ($this->skip_optional_key($details, $line, $subkey)) continue;
										$row[$subkey] = CK\fn::is_key_set($line, $subkey)?$line[$subkey]:$details;
									}
									if (!CK\fn::is_key_set($data_store[$element]['data'], $key) || !is_array($data_store[$element]['data'][$key])) $data_store[$element]['data'][$key] = [];
									$data_store[$element]['data'][$key][] = $row;
								}
							}
						}
						else {
							$row = [];
							foreach ($data_store[$element]['format'] as $subkey => $details) {
								if ($this->skip_optional_key($details, $data, [$key, $subkey])) continue;
								$row[$subkey] = CK\fn::is_key_set($data, [$key, $subkey])?$data[$key][$subkey]:$details;
							}
							$data_store[$element]['data'][$key] = $row;
						}
					}
				}
				else {
					if (empty($data_store[$element]['format'])) {
						// we don't have a key format *or* a row format, just accept the data as is
						// we do this when we don't want to waste time looping through an entire dataset - should probably do this more often
						$data_store[$element]['data'] = $data;
					}
					else {
						// if we don't have a key format, validate the format of the rows, not the keys
						foreach ($data as $key => $value) {
							$row = [];
							foreach ($data_store[$element]['format'] as $subkey => $details) {
								if ($this->skip_optional_key($details, $value, $subkey)) continue;
								$row[$subkey] = CK\fn::is_key_set($value, $subkey)?$value[$subkey]:$details;
							}
							$data_store[$element]['data'][$key] = $row;
						}
					}
				}
				break;
		}
		$data_store[$element]['built'] = TRUE;

		return TRUE;
	}

	public function format($element) {
		return $this->structure[$element]['format'];
	}

	public function key_format($element) {
		return $this->structure[$element]['key_format'];
	}

	public function get($element, $key=NULL) {
		if (is_null($key)) return $this->structure[$element]['data'];
		elseif (!isset($this->structure[$element]['data'][$key]) && !array_key_exists($key, $this->structure[$element]['data'])) return NULL;
		else return $this->structure[$element]['data'][$key];
	}

	public function lookup($element, $key=NULL) {
		if (is_null($key)) return static::$lookup[$element]['data'];
		elseif (!isset(static::$lookup[$element]['data'][$key]) && !array_key_exists($key, static::$lookup[$element]['data'])) return NULL;
		else return static::$lookup[$element]['data'][$key];
	}

	public function has($element) {
		return !empty($this->structure[$element]['data']);
	}

	// not sure if this is useful - in most cases it won't make sense to "remove" an element, rather than removing a relationship to another object type
	/*public function remove($element) {
		switch ($this->structure[$element]['cardinality']) {
			case cardinality::SINGLE:
				$this->structure[$element]['data'] = NULL;
				break;
			case cardinality::COLUMN:
			case cardinality::ROW:
			case cardinality::SET:
			default:
				$this->structure[$element]['data'] = [];
				break;
		}
	}*/

	// ideally we would add some sort of change tracking that actually confirms that the new value is different from the old value - but this is probably not necessary
	public function set($element, $data) { // $idx, $field - optional arguments, for one dimensional arrays either can come first, for two dimensional arrays, numeric index comes first
		// this function operates similarly, but not identical to, load() - here we're able to interact with individual array elements or fields, and we're tracking changes
		$arr = is_array($data);
		switch ($this->structure[$element]['cardinality']) {
			case cardinality::SINGLE:
				if ($arr) throw new CKTypeException('Array data is not valid for SINGLE data types');

				$this->structure[$element]['data'] = $data;
				$this->changes[] = $element;
				$this->structure[$element]['changes'] = TRUE;
				break;
			case cardinality::COLUMN:
				$args = func_get_args();
				if (!empty($args[2])) {
					if ($arr) throw new CKTypeException('Array data is not valid for a single entry in COLUMN data types');

					$this->structure[$element]['data'][$args[2]] = $data;
					$this->changes[] = $element;
					// checking for array will not overwrite a boolean TRUE - if it's boolean TRUE, we want to set the whole column
					if (is_array($this->structure[$element]['changes'])) $this->structure[$element]['changes'][$args[2]] = TRUE;
				}
				else {
					if (!$arr) throw new CKTypeException('Array data is required for COLUMN data types');

					$this->structure[$element]['data'] = [];
					foreach ($data as $val) {
						$this->structure[$element]['data'][] = $val;
					}
					$this->changes[] = $element;
					$this->structure[$element]['changes'] = TRUE;
				}
				break;
			case cardinality::ROW:
				$args = func_get_args();
				if (!empty($args[2])) {
					if ($arr) throw new CKTypeException('Array data is not valid for a single entry in ROW data types');
					if (!isset($this->structure[$element]['format'][$args[2]])) throw new CKTypeException('Field ['.$args[2].'] is not defined for element ['.$element.'] in type ['.get_class($this).']');

					$this->structure[$element]['data'][$args[2]] = $data;
					$this->changes[] = $element;
					// checking for array will not overwrite a boolean TRUE - if it's boolean TRUE, we want to set the whole row
					if (is_array($this->structure[$element]['changes'])) $this->structure[$elmeent]['changes'][$args[2]] = TRUE;
				}
				else {
					if (!$arr) throw new CKTypeException('Array data is required for ROW data types');

					$this->structure[$element]['data'] = [];
					foreach ($this->structure[$element]['format'] as $key => $details) {
						$this->structure[$element]['data'][$key] = !empty($data[$key])?$data[$key]:NULL;
					}
					$this->changes[] = $element;
					$this->structure[$element]['changes'] = TRUE;
				}
				break;
			case cardinality::SET:
			default:
				$args = func_get_args();
				if (!empty($args[2])) {
					if (!is_numeric($args[2])) throw new CKTypeException('Invalid argument type');
					else $row_idx = $args[2];

					if (!empty($args[3])) {
						// we're setting an individual value
						if ($arr) throw new CKTypeException('Array data is not valid for a single entry in SET data types');
						if (!isset($this->structure[$element]['format'][$args[3]])) throw new CKTypeException('Field ['.$args[3].'] is not defined for element ['.$element.'] in type ['.get_class($this).']');

						$this->structure[$element]['data'][$row_idx][$args[3]] = $data;
						$this->changes[] = $element;
						// checking for empty will cause it to not overwrite a boolean TRUE - if it's boolean TRUE, we want to set the whole row or set
						if (is_array($this->structure[$element]['changes']) && empty($this->structure[$element]['changes'][$row_idx])) $this->structure[$element]['changes'][$row_idx] = [];
						if (is_array($this->structure[$element]['changes']) && is_array($this->structure[$element]['changes'][$row_idx])) $this->structure[$element]['changes'][$row_idx][$args[3]] = TRUE;
					}
					else {
						// we're setting a whole row at once
						if (!$arr) throw new CKTypeException('Array data is required for a single row in SET data types');

						foreach ($this->structure[$element]['format'] as $key => $details) {
							$this->structure[$element]['data'][$row_idx][$key] = !empty($data[$key])?$data[$key]:NULL;
						}
						$this->changes[] = $element;
						// checking for array will not overwrite a boolean TRUE - if it's boolean TRUE, we want to set the whole set
						if (is_array($this->structure[$element]['changes'])) $this->structure[$element]['changes'][$row_idx] = TRUE;
					}
				}
				else {
					// we're setting the entire set at once
					if (!$arr) throw new CKTypeException('Array data is required for SET data types');

					$this->structure[$element]['data'] = [];
					foreach ($data as $line) {
						$row = [];
						foreach ($this->structure[$element]['format'] as $key => $details) {
							$row[$key] = $line[$key];
						}
						$this->structure[$element]['data'][] = $row;
					}
					$this->changes[] = $element;
					$this->structure[$element]['changes'] = TRUE;
				}
				break;
		}

		return $data;
	}

	protected function validate($element=NULL) {
		throw new CKTypeException('cktype->validate() is not yet implemented');
	}

	protected function reset_changes() {
		$this->changes = [];
		foreach ($this->structure as $element => $structure) {
			if ($structure['cardinality'] == cardinality::SINGLE) $this->structure[$element]['changes'] = NULL;
			else $this->structure[$element]['changes'] = [];
		}
	}

	public function __call($method, $args) {
		// the generic __call() method will cause a little extra processing than going straight to the public methods they point to, but could be a more "clean" choice in some circumstances
		$orig = $method;
		$parts = explode('_', $method, 2);
		$method = $parts[0];
		$element = !empty($parts[1])?$parts[1]:NULL;

		$methods = ['load', 'format', 'get', 'has', /*'remove',*/ 'set', 'validate'];

		if (empty($element) || !in_array($method, $methods)) throw new CKTypeException('Method ['.$orig.'] is not defined for type ['.get_class($this).']');
		if (!property_exists($this, $element)) throw new CKTypeException('Element ['.$element.'] does not exist for type ['.get_class($this).']');

		// for our purposes here, we'll just assume that $args is formatted properly
		switch ($method) {
			case 'load':
				return $this->load($element, $args[0]);
				break;
			case 'format':
				return $this->format($element);
				break;
			case 'get':
				return $this->get($element);
				break;
			case 'has':
				return $this->has($element);
				break;
			/*case 'remove':
				$this->remove($element);
				break;*/
			case 'set':
				if (empty($args[1])) return $this->set($element, $args[0]);
				elseif (empty($args[2])) return $this->set($element, $args[0], $args[1]);
				else return $this->set($element, $args[0], $args[1], $args[2]);
				break;
			case 'validate':
				return $this->validate($element);
				break;
			default:
				// best practice to show default in every switch; there's no way for us to reach it
				throw new CKTypeException('Unkonwn type error');
				break;
		}
	}

	public function __sleep() {
		return ['structure'];
	}
}

class CKTypeException extends CkMasterArchetypeException {
	public function __construct($message='', $code=0, $previous=NULL) {
		parent::__construct('['.$this->get_calling_class().'] '.$message, $code, $previous);
	}
}
?>
