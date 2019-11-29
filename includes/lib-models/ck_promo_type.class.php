<?php
class ck_promo_type extends ck_types {

	public function __construct($promo_id=NULL) {
		$this->_init();
		if (!empty($promos_id)) $this->load('promo_id', $promo_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'promo_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'promo_id' => NULL,
				'promo_title' => NULL,
				'quantity' => NULL,
				'measure' => NULL,
				'timeframe' => NULL,
				'created_at' => NULL,
				'updated_at' => NULL,
				'creator_id' => NULL,
				'rule_met' => NULL,
				'products_id' => NULL,
				'dev_rule' => NULL,
				'archive' => NULL,
				'active' => NULL
			]
		],
		'rules' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'promo_rule_id' => NULL,
				'rule_met' => NULL,
				'quantity' => NULL,
				'timeframe' => NULL,
				'measure' => NULL,
				'dev_rule' => NULL,
				'creator_id' => NULL,
				'created_at' => NULL,
				'updated_at' => NULL,
				'archive' => NULL
			]
		],
		'creator' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'product' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		]
	];
}
?>
