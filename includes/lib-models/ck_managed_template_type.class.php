<?php
class ck_managed_template_type extends ck_types {

	public function __construct($managed_template_id=NULL) {
		$this->_init();
		if (!empty($managed_template_id)) $this->load('managed_template_id', $managed_template_id);
	}

	protected $structure = [
		'managed_template_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'managed_template_id' => NULL,
				'template_name' => NULL,
				'template_location' => NULL,
				'context' => NULL,
				'nav' => NULL,
				'date_created' => NULL
			]
		],
		'template' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		]
	];
}
?>
