<?php
class ck_adjustment_type_lookup extends ck_lookup {
	protected $lookup_name = 'Adjustment Type';

	protected $lookup_table = 'inventory_adjustment_type';

	protected $table_key = 'id';

	protected $direct_key = 'adjustment_type_id';
	protected $reverse_key = 'type';

	protected static $queries = [
		'lookup' => [
			'qry' => 'SELECT id as adjustment_type_id, name as type, sort_order, adjustment_direction, system_only, active FROM inventory_adjustment_type ORDER BY sort_order ASC, name ASC',
			'cardinality' => cardinality::SET
		],
	];

	protected function _init() {
		foreach ($this->basic_data as $idx => $at) {
			$this->basic_data[$idx]['system_only'] = CK\fn::check_flag($at['system_only']);
			$this->basic_data[$idx]['active'] = CK\fn::check_flag($at['active']);
		}
	}

	public function _get_list($by='basic', $field=NULL, $active=NULL) {
		switch ($by) {
			case 'lookup-active':
				$list = array_filter($this->lookup_map, function($at) { return $at['active']; });
				break;
			case 'active':
				$list = array_filter($this->basic_data, function($at) { return $at['active']; });
				break;
			case 'lookup-user':
				$list = array_filter($this->lookup_map, function($at) { return $at['active']&&!$at['system_only']; });
				break;
			case 'user':
				$list = array_filter($this->basic_data, function($at) { return $at['active']&&!$at['system_only']; });
				break;
			case 'lookup-system':
				$list = array_filter($this->lookup_map, function($at) { return $at['active']&&$at['system_only']; });
				break;
			case 'system':
				$list = array_filter($this->basic_data, function($at) { return $at['active']&&$at['system_only']; });
				break;
			default:
				$list = $this->basic_data;
				break;
		}

		if (!is_null($active)) $list = array_filter($list, function($at) use ($active) { return $at['active'] === $active; });

		return $list;
	}
}

class CKAdjustmentTypeException extends CKMasterArchetypeException {
}
?>
