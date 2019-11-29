<?php
class ck_homepage_type extends ck_types {

	public function __construct($nothing=NULL) {
		$this->_init();
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'rotator' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'site_homepage_id' => NULL,
				'sort_order' => NULL,
				'img_src' => NULL,
				'absolute_img_ref' => NULL,
				'alt_text' => NULL,
				'link_target_type' => NULL,
				'link_target' => NULL,
				'active' => NULL,
				'created_date' => NULL
			]
		],
		'kickers' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'site_homepage_id' => NULL,
				'sort_order' => NULL,
				'img_src' => NULL,
				'absolute_img_ref' => NULL,
				'alt_text' => NULL,
				'link_target_type' => NULL,
				'link_target' => NULL,
				'active' => NULL,
				'created_date' => NULL
			]
		],
		'showcases' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'site_homepage_id' => NULL,
				'title' => NULL,
				'active' => NULL,
				'html' => NULL,
				'product_ids' => NULL
			]
		]
	];
}
?>
