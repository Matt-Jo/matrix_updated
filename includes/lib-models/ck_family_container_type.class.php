<?php
class ck_family_container_type extends ck_types {

	protected static $queries = [
		'container_type' => [
			'qry' => 'SELECT container_type_id, name, table_name, active FROM ck_merchandising_container_types WHERE name = :container_type_name',
			'cardinality' => cardinality::ROW
		]
	];

	public function __construct($family_container_id=NULL) {
		$this->_init();
		$this->build_dynamic_maps();
		if (!empty($family_container_id)) $this->load('family_container_id', $family_container_id);
	}

	private function build_dynamic_maps() {
		if (!self::$lookup['container_type']['built']) {
			$this->load_lookup('container_type', self::fetch('container_type', [':container_type_name' => 'Family']));
		}
	}

	protected static $lookup = [
		'container_type' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'container_type_id' => NULL,
				'name' => NULL,
				'table_name' => NULL,
				'active' => NULL
			]
		]
	];

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'family_container_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'family_container_id' => NULL,
				'name' => NULL,
				'url' => NULL,
				'url_with_categories' => NULL,
				'meta_title' => NULL,
				'meta_description' => NULL,
				'meta_keywords' => NULL,
				'summary' => NULL,
				'description' => NULL,
				'details' => NULL,
				'default_image' => NULL,
				'default_image_medium' => NULL,
				'default_image_small' => NULL,
				'template_id' => NULL,
				'nav_template_id' => NULL,
				'offer_template_id' => NULL,
				'show_lifetime_warranty' => NULL,
				'family_unit_id' => NULL,
				'default_family_unit_sibling_id' => NULL,
				'admin_only' => NULL,
				'active' => NULL,
				'date_created' => NULL
			]
		],
		'base_url' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL,
		],
		'templates' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'template' => NULL,
				'nav_template' => NULL,
				'offer_template' => NULL,
			]
		],
		'family_unit' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'categories' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'categories_id' => NULL,
				'category' => NULL,
				'default_relationship' => NULL,
				'date_created' => NULL
			]
		],
		'first_selected_product' => [
			'cardinality' => cardinality::ROW,
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
		'first_selections' => [
			'cardinality' => cardinality::MAP,
			'format' => [],
		]
	];
}
?>
