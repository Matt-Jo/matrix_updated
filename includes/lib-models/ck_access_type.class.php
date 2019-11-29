<?php
class ck_access_type extends ck_types {

	public function __construct() {
		$this->_init();
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'permissions' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'permission_id' => NULL,
				'permission_name' => NULL,
				'description' => NULL,
				'screenshot' => NULL,
				'active' => NULL,
				'date_created' => NULL,
				'groups' => [],
				'roles' => [],
				'admins' => []
			]
		],
		'groups' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'permission_group_id' => NULL,
				'group_name' => NULL,
				'description' => NULL,
				'active' => NULL,
				'date_created' => NULL,
				'permissions' => [],
				'roles' => [],
				'admins' => []
			]
		],
		'roles' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'admin_role_id' => NULL,
				'role_name' => NULL,
				'description' => NULL,
				'active' => NULL,
				'date_created' => NULL,
				'permissions' => [],
				'groups' => [],
				'admins' => []
			]
		],
		'relationships' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'permissions' => [],
				'roles' => [],
				'groups' => [],
				'admins' => []
			]
		],
		'relationship_history' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'companion' => NULL,
				'action' => NULL,
				'action_date' => NULL
			],
			'key_format' => [
				'permissions' => [],
				'groups' => [],
				'roles' => [],
				'admins' => []
			]
		]
	];
}
?>
