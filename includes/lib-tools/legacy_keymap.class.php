<?php
class legacy_keymap extends ArrayObject {

	private $keymap;

	public function __construct($input, $keymap, $flags=0, $iterator_class='ArrayIterator') {
		$this->keymap = $keymap;
		parent::__construct($input, $flags, $iterator_class);
	}

	/*public function &load_keymap($keymap) {
		$this->keymap = $keymap;
		return $this;
	}*/

	public function &offsetGet($key) { 
		if (parent::offsetExists($key)) {
			$val =& parent::offsetGet($key);
			return $val;
		}
		elseif (isset($this->keymap[$key])) {
			if (is_array($this->keymap[$key])) {
				foreach ($this->keymap[$key] as $keyopt) {
					if (parent::offsetExists($keyopt)) {
						$val =& parent::offsetGet($keyopt);
						return $val;
					}
				}
			}
			elseif (parent::offsetExists($this->keymap[$key])) {
				$val =& parent::offsetGet($this->keymap[$key]);
				return $val;
			}
		}

		trigger_error('Index ['.$key.'] could not be located or mapped in legacy_keymap array', E_USER_NOTICE);

		$return = NULL;
		return $return;
		// the below just caused an infinite loop - obv. not wanted
		//return $this[$key]; // guaranteed to throw notices
	}

	public function offsetExists($key) { 
		if (parent::offsetExists($key)) return TRUE;
		elseif (isset($this->keymap[$key])) {
			if (is_array($this->keymap[$key])) {
				foreach ($this->keymap[$key] as $keyopt) {
					if (parent::offsetExists($keyopt)) return TRUE;
				}
			}
			else return parent::offsetExists($this->keymap[$key]);
		}

		return FALSE;
	}

	// this doesn't strictly belong to the "legacy" mapping context, but it's super useful, so we're gonna do it
	public function qs($remove_list=array()) {
		return http_build_query(CK\fn::filter_request($this, $remove_list));
	}
}
?>
