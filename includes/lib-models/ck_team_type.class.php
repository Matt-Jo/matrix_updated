<?php
class ck_team_type extends ck_types {

	public function __construct($team_id=NULL) {
		$this->_init();
		if (!empty($team_id)) $this->load('team_id', $team_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'team_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'team_id' => NULL,
				'label' => NULL,
				'email_address' => NULL,
				'phone_number' => NULL,
				'local_phone_number' => NULL,
				'sales_team' => NULL,
				'salesforce_key' => NULL,
				'active' => NULL,
			]
		],
		'members' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'team_assignment_id' => NULL,
				'admin_id' => NULL,
				'member' => NULL,
				'assignment_date' => NULL,
			]
		],
	];
}