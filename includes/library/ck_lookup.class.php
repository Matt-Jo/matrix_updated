<?php
abstract class ck_lookup extends ck_singleton {

	protected $basic_data = [];
	protected $lookup_map = [];
	protected $reverse_lookup_map = [];

	protected function init($parameters=[]) {
		$this->basic_data = self::fetch('lookup', []);

		foreach ($this->basic_data as $idx => $row) {
			$this->lookup_map[$row[$this->direct_key]] = &$this->basic_data[$idx];
			if (!empty($this->reverse_key)) $this->reverse_lookup_map[$row[$this->reverse_key]] = &$this->basic_data[$idx];
		}

		if (method_exists($this, '_init')) $this->_init();
	}

	public function get_list($by='basic', $field=NULL, $match=NULL) {
		switch ($by) {
			case 'by-id':
			case 'id':
				$list = $this->lookup_map;
				break;
			case 'by-name':
			case 'name':
			case 'by-code':
			case 'code':
				$list = $this->reverse_lookup_map;
				break;
			case 'basic':
			case '':
			case NULL:
				$list = $this->basic_data;
				break;
			default:
				if (method_exists($this, '_get_list')) $list = $this->_get_list($by, $field, $match);
				else $list = $this->basic_data;
				break;
		}

		if (!empty($field)) {
			$list = array_column($list, $field);
			//$list = array_map(function($dc) use ($field) { return $dc[$field]; }, $list);
		}

		return $list;
	}

	public function lookup_by_id($id, $field=NULL) {
		if (!isset($this->lookup_map[$id])) throw new CKLookupException($this->lookup_name.' ID ['.$id.'] does not exist');

		if (!empty($field) && !isset($this->lookup_map[$id][$field])) throw new CKLookupException('Field ['.$field.'] does not exist in the '.$this->lookup_name.' data');

		if (empty($field)) return $this->lookup_map[$id];
		else return $this->lookup_map[$id][$field];
	}

	// some lookups don't have a "code" per se, though they probably should, but it makes enough sense for this "by name" alias to exist
	public function lookup_by_name($name, $field=NULL) {
		return $this->lookup_by_code($name, $field);
	}

	public function lookup_by_code($code, $field=NULL) {
		if (!isset($this->reverse_lookup_map[$code])) throw new CKLookupException($this->lookup_name.' Code ['.$code.'] does not exist');

		if (!empty($field) && !isset($this->reverse_lookup_map[$code][$field])) throw new CKLookupException('Field ['.$field.'] does not exist in the '.$this->lookup_name.' data');

		if (empty($field)) return $this->reverse_lookup_map[$code];
		else return $this->reverse_lookup_map[$code][$field];
	}

	public function add_value(Array $data) {
		$savepoint = self::transaction_begin();

		prepared_fields::validate_identifier($this->lookup_table, TRUE); // if somehow the table name is not safe, halt

		try {
			$params = new prepared_fields($data, prepared_fields::INSERT_QUERY);
			self::query_execute('INSERT INTO '.$this->lookup_table.' ('.$params->insert_fields().') VALUES ('.$params->insert_values().')', cardinality::NONE, $params->insert_parameters());
			$id = self::fetch_insert_id();

			self::init();

			self::transaction_commit($savepoint);

			return $id;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKLookupException('Failed to add new '.$this->lookup_name.': '.$e->getMessage());
		}
	}

	public function remove_value($value_id) {
	}

	public function update_value($value_id, Array $data) {
		$savepoint = self::transaction_begin();

		prepared_fields::validate_identifier($this->lookup_table, TRUE); // if somehow the table name is not safe, halt

		try {
			$params = new prepared_fields($data, prepared_fields::UPDATE_QUERY);
			$ids = new prepared_fields([$this->table_key => $value_id]);
			self::query_execute('UPDATE '.$this->lookup_table.' SET '.$params->update_sets().' WHERE '.$ids->update_sets(), cardinality::NONE, prepared_fields::consolidate_parameters($params, $ids));

			self::init();

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKLookupException('Failed to update '.$this->lookup_name.': '.$e->getMessage());
		}
	}

	public function enable_value($value_id) {
	}

	public function disable_value($value_id) {
	}

	// these methods are defined in an incompatible way up the inheritance chain, we gotta override them
	public function is($flag) {
		return FALSE;
	}

	public function has($key) {
		return FALSE;
	}

	public function __sleep() {
		return ['lookup_map', 'reverse_lookup_map'];
	}
}

class CKLookupException extends CKMasterArchetypeException {
}
?>
