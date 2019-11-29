<?php
class ck_page_includer_type extends ck_types {

	public function __construct($page_includer_id=NULL) {
		$this->_init();
		if (!empty($page_includer_id)) $this->load('page_includer_id', $page_includer_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'page_includer_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'page_includer_id' => NULL,
				'label' => NULL,
				'target' => NULL,
				'page_height' => NULL,
				'date_created' => NULL,
				'date_updated' => NULL
			]
		],
		'request_maps' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'page_includer_request_map_id' => NULL,
				'request' => NULL,
				'date_created' => NULL,
				'date_updated' => NULL
			]
		]
	];
}
?>
