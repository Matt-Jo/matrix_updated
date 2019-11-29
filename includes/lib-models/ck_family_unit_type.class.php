<?php
class ck_family_unit_type extends ck_types {

	public function __construct($family_unit_id=NULL) {
		$this->_init();
		if (!empty($family_unit_id)) $this->load('family_unit_id', $family_unit_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'family_unit_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'family_unit_id' => NULL,
				'generic_model_number' => NULL,
				'name' => NULL,
				'description' => NULL,
				'homogeneous' => NULL,
				'active' => NULL,
				'date_created' => NULL
			]
		],
		'variances' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'family_unit_variance_id' => NULL,
				'field_name' => NULL,
				'attribute_id' => NULL,
				'target' => NULL,
				'key' => NULL,
				'name' => NULL,
				'name_display' => NULL,
				'name_key' => NULL,
				'descriptor' => NULL,
				'group_on' => NULL,
				'sort_order' => NULL,
				'active' => NULL,
				'date_created' => NULL,
			]
		],
		'siblings' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'family_unit_sibling_id' => NULL,
				'stock_id' => NULL,
				'model_number' => NULL,
				'name' => NULL,
				'description' => NULL,
				'active' => NULL,
				'date_created' => NULL,
				'products_id' => NULL
			]
		],
		'sibling_attributes' => [
			'cardinality' => cardinality::MAP,
			'format' => [],
		],
		'listing_details' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'family_unit_sibling_id' => NULL,
				'stock_id' => NULL,
				'products_id' => NULL,
				'products_model' => NULL,
				'products_name' => NULL,
				'products_head_desc_tag' => NULL,
				'always_available' => NULL,
				'lead_time' => NULL,
				'conditions' => NULL,
				'conditions_name' => NULL,
				'discontinued' => NULL,
				'has_special' => NULL,
				'listing' => NULL,
				'attributes' => [],
				'schema' => [],
				'images' => [],
				'prices' => [],
				'inventory' => [],
			],
		],
		'listing_variant_options' => [
			'cardinality' => cardinality::MAP,
			'format' => [],
		],
		'containers' => [
			'cardinality' => cardinality::COLUMN,
			'format' => []
		],
	];
}
?>
