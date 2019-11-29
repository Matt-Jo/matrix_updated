<?php
class ck_vendor_type extends ck_types {

	public function __construct($vendors_id=NULL) {
		$this->_init();
		if (!empty($vendors_id)) $this->load('vendors_id', $vendors_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'vendors_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'vendors_id' => NULL,
				'company_name' => NULL,
				'last_name' => NULL,
				'email_address' => NULL,
				'default_address_id' => NULL,
				'telephone' => NULL,
				'fax' => NULL,
				'account_notes' => NULL,
				'vendor_type' => NULL,
				'fedex_account_number' => NULL,
				'ups_account_number' => NULL,
				'aim_screenname' => NULL,
				'msn_screenname' => NULL,
				'company_account_contact_name' => NULL,
				'company_account_contact_email' => NULL,
				'company_account_contact_phone_number' => NULL,
				'company_web_address' => NULL,
				'payment_terms' => NULL,
				'account_manager_id' => NULL,
				'account_manager_firstname' => NULL,
				'account_manager_lastname' => NULL,
				'account_manager_email' => NULL,
				'last_login_date' => NULL,
				'number_of_logins' => NULL,
				'date_account_created' => NULL,
				'last_modified' => NULL
			]
		],
		'addresses' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'address' => NULL
			]
		],
		'ipn_relationships' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'vendors_to_stock_item_id' => NULL,
				'stock_id' => NULL,
				'ipn' => NULL,
				'vendors_price' => NULL,
				'vendors_part_number' => NULL,
				'case_qty' => NULL,
				'always_available' => NULL,
				'lead_time' => NULL,
				'notes' => NULL,
				'primary' => NULL,
				'secondary' => NULL
			]
		]
	];
}
?>
