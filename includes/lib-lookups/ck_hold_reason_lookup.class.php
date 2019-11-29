<?php
class ck_hold_reason_lookup extends ck_lookup {
	protected $lookup_name = 'Hold Reason';

	protected $lookup_table = 'inventory_hold_reason';

	protected $table_key = 'id';

	protected $direct_key = 'hold_reason_id';
	protected $reverse_key = 'reason';

	protected static $queries = [
		'lookup' => [
			'qry' => 'SELECT id as hold_reason_id, description as reason, in_process, active FROM inventory_hold_reason ORDER BY description ASC',
			'cardinality' => cardinality::SET
		],
	];

	protected function _init($parameters=[]) {
		foreach ($this->basic_data as $idx => $hold_reason) {
			$this->basic_data[$idx]['active'] = CK\fn::check_flag($hold_reason['active']);
		}
	}

	protected function _get_list($by='all', $field=NULL, $match=NULL) {
		switch ($by) {
			case 'active':
				$list = array_filter($this->basic_data, function($reason) { return $reason['active']; });
				break;
			case 'inactive':
				$list = array_filter($this->basic_data, function($reason) { return !$reason['active']; });
			case 'all':
			default:
				$list = $this->basic_data;
				break;
		}

		return $list;
	}
}

class CKHoldReasonException extends CKMasterArchetypeException {
}
?>
