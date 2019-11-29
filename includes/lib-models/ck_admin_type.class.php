<?php
class ck_admin_type extends ck_types {

	public function __construct($admin_id=NULL) {
		$this->_init();
		if (!empty($admin_id)) $this->load('admin_id', $admin_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'admin_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'admin_id' => NULL,
				'first_name' => NULL,
				'last_name' => NULL,
				'email_address' => NULL,
				'date_created' => NULL,
				'last_modified_date' => NULL,
				'last_login_date' => NULL,
				'login_counter' => NULL,
				'rfq_signature' => NULL,
				'rfq_greeting' => NULL,
				'legacy_group_id' => NULL,
				'legacy_group' => NULL,
				'active' => NULL,
				'account_manager' => NULL,
				'broker' => NULL,
				'phone_number' => NULL
			]
		],
		'teams' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'team_assignment_id' => NULL,
				'team_id' => NULL,
				'team' => NULL,
				'assignment_date' => NULL
			]
		],
		'roles' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'admin_role_id' => NULL,
				'role' => NULL,
				'status' => NULL
			]
		],
		'groups' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'permission_group_id' => NULL,
				'group' => NULL,
				'status' => NULL
			]
		],
		'legacy_permissions' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				// IPN
				'ipn_reviewer' => NULL,
				'update_ipn_quantity' => NULL,
				'update_ipn_weight' => NULL,
				'rename_ipn' => NULL,
				'update_ipn_average_cost' => NULL,
				'upload_images' => NULL,
				'update_target_min_qty' => NULL,
				'update_target_max_qty' => NULL,
				'update_serial' => NULL,
				'mark_as_reviewed' => NULL,
				'change_ipn_category' => NULL,
				'change_warranties' => NULL,
				'change_dealer_warranties' => NULL,
				// Sales
				'use_master_password' => NULL,
				// Accounting
				'update_pay_tax' => NULL,
				'update_net_terms' => NULL,
			],
		],
		'direct_permissions' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'permission_id' => NULL,
				'permission' => NULL,
				'status' => NULL
			]
		],
		'permissions' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'permission_id' => NULL,
				'permission' => NULL,
				'status' => NULL
			]
		],
	];
}