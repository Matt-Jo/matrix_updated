<?php
class ck_sales_incentive_tier_lookup extends ck_lookup {
	protected $lookup_name = 'Sales Incentive Tier';

	protected $lookup_table = 'ck_sales_incentive_tiers';

	protected $table_key = 'sales_incentive_tier_id';

	protected $direct_key = 'sales_incentive_tier_id';
	protected $reverse_key = NULL;

	protected static $queries = [
		'lookup' => [
			'qry' => 'SELECT sales_incentive_tier_id, incentive_base, incentive_percentage, active FROM ck_sales_incentive_tiers ORDER BY incentive_base ASC',
			'cardinality' => cardinality::SET
		]
	];

	protected function _init($parameters=[]) {
		foreach ($this->basic_data as $idx => $tier) {
			$this->basic_data[$idx]['active'] = CK\fn::check_flag($tier['active']);
		}
	}

	public function _get_list($by='basic', $field=NULL, $active=NULL) {
		switch ($by) {
			case 'active-lookup':
				$list = $this->lookup_map;
				break;
			case 'active-basic':
			default:
				$list = $this->basic_data;
				break;
		}

		if (!is_null($active)) $list = array_filter($list, function($tier) use ($active) { return $tier['active'] === $active; });

		return $list;
	}
}

class CKSalesIncentiveTierLookupException extends CKMasterArchetypeException {
}
?>
