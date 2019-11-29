<?php
class ck_payment_method_lookup extends ck_lookup {
	protected $lookup_name = 'Payment Method';

	protected $lookup_table = 'payment_method';

	protected $table_key = 'id';

	protected $direct_key = 'payment_method_id';
	protected $reverse_key = 'method_code';

	protected static $queries = [
		'lookup' => [
			'qry' => 'SELECT id as payment_method_id, code as method_code, label as method_label, orders as net_terms, legacy as is_legacy FROM payment_method ORDER BY id ASC',
			'cardinality' => cardinality::SET
		]
	];

	protected function _init($parameters=[]) {
		foreach ($this->basic_data as $idx => $payment_method) {
			$this->basic_data[$idx]['is_legacy'] = CK\fn::check_flag($payment_method['is_legacy']);
			$this->basic_data[$idx]['net_terms'] = CK\fn::check_flag($payment_method['net_terms']);
		}
	}

	protected function _get_list($by='basic', $field=NULL, $match=NULL) {
		switch ($by) {
			case 'terms':
				$list = array_filter($this->basic_data, function($pm) { return $pm['net_terms']; });
				break;
			case 'active':
				$list = array_filter($this->basic_data, function($pm) { return !$pm['is_legacy']; });
				break;
			default:
				$list = $this->basic_data;
				break;
		}

		return $list;
	}
}

class CKPaymentLookupException extends CKMasterArchetypeException {
}
?>
