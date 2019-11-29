<?php
class ck_custom_page_type extends ck_types {

	protected static $queries = [];

	public function __construct($page_id=NULL) {
		$this->_init();
		if (!empty($page_id)) $this->load('page_id', $page_id);
	}

	protected $structure = [
		'page_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'page_id' => NULL,
				'page_title' => NULL,
				'page_code' => NULL,
				'product_id_list' => NULL,
				'sitewide_header' => NULL,
				'full_width' => NULL,
				'url' => NULL,
				'meta_description' => NULL,
				'visibility' => NULL,
				'archived' => NULL,
				'url_identifier' => NULL
			]
		]
	];
}
?>
