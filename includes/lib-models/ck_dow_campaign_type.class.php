<?php
class ck_dow_campaign_type extends ck_types {

	protected static $queries = [
		'campaign_option_keys' => [
			'qry' => 'SELECT * FROM ck_dow_campaign_option_types',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],
	];

	public function __construct($dow_campaign_id=NULL) {
		$this->_init();
		$this->build_dynamic_maps();
		if (!empty($dow_campaign_id)) $this->load('dow_campaign_id', $dow_campaign_id);
	}

	private function build_dynamic_maps() {
		$options = self::fetch('campaign_option_keys', []);

		foreach ($options as $option) {
			// it will be accessible (as a reference) from either the ID or the named key
			$this->structure['options']['key_format'][$option['dow_campaign_option_type_id']] = [];
			$this->structure['options']['key_format'][$option['option_key']] = &$this->structure['options']['key_format'][$option['dow_campaign_option_type_id']];
		}
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'dow_campaign_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'dow_campaign_id' => NULL,
				'name' => NULL,
				'first_date' => NULL,
				'last_date' => NULL,
				'simultaneous_products' => NULL,
				'deal_length_days' => NULL,
				'deal_length_hours' => NULL,
				'deal_length_minutes' => NULL,
				'draft' => NULL,
				'active' => NULL,
				'created_date' => NULL
			]
		],
		'deals' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'dow_deal_id' => NULL,
				'products_id' => NULL,
				'deal_start' => NULL,
				'deal_end' => NULL,
				'custom_description' => NULL,
				'custom_legalese' => NULL,
				'create_specials_price' => NULL,
				'draft' => NULL,
				'active' => NULL,
				'created_date' => NULL,
				'listing' => NULL,
				'recommended_products' => NULL
			]
		],
		'options' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				// no named keys, just a numerical array
			],
			'key_format' => [
				// defined on init
			]
		]
    ];
}



