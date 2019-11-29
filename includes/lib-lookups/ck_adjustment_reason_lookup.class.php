<?php
class ck_adjustment_reason_lookup extends ck_lookup {
	protected $lookup_name = 'Adjustment Reason';

	protected $lookup_table = 'inventory_adjustment_reason';

	protected $table_key = 'id';

	protected $direct_key = 'adjustment_reason_id';
	protected $reverse_key = 'reason';

	protected static $queries = [
		'lookup' => [
			'qry' => 'SELECT id as adjustment_reason_id, description as reason, sort_order, adjustment_direction, system_only, active FROM inventory_adjustment_reason ORDER BY description ASC',
			'cardinality' => cardinality::SET
		],
	];

	protected function _init() {
		foreach ($this->basic_data as $idx => $ar) {
			$this->basic_data[$idx]['system_only'] = CK\fn::check_flag($ar['system_only']);
			$this->basic_data[$idx]['active'] = CK\fn::check_flag($ar['active']);
		}
	}

	public function _get_list($by='basic', $field=NULL, $active=NULL) {
		switch ($by) {
			case 'lookup-active':
				$list = array_filter($this->lookup_map, function($ar) { return $ar['active']; });
				break;
			case 'active':
				$list = array_filter($this->basic_data, function($ar) { return $ar['active']; });
				break;
			case 'lookup-user':
				$list = array_filter($this->lookup_map, function($ar) { return $ar['active']&&!$ar['system_only']; });
				break;
			case 'user':
				$list = array_filter($this->basic_data, function($ar) { return $ar['active']&&!$ar['system_only']; });
				break;
			case 'lookup-system':
				$list = array_filter($this->lookup_map, function($ar) { return $ar['active']&&$ar['system_only']; });
				break;
			case 'system':
				$list = array_filter($this->basic_data, function($ar) { return $ar['active']&&$ar['system_only']; });
				break;
			default:
				$list = $this->basic_data;
				break;
		}

		if (!is_null($active)) $list = array_filter($list, function($ar) use ($active) { return $ar['active'] === $active; });

		return $list;
	}
}

class CKAdjustmentReasonException extends CKMasterArchetypeException {
}
?>
