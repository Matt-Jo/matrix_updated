<?php
class ck_global_search_type extends ck_types {

	public function __construct() {
		$this->_init();
		$this->build_dynamic_maps();
	}

	private function build_dynamic_maps() {
	}

	protected $structure = [
		'codes' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'control_code_id' => NULL,
				'control_code' => NULL,
				'control_value' => NULL,
				'control_key' => NULL,
				'active' => NULL,
				'date_created' => NULL
			]
		]
	];

}
?>
