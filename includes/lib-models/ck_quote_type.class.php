<?php
class ck_quote_type extends ck_types {

	public function __construct($customer_quote_id=NULL) {
		$this->_init();
		if (!empty($customer_quote_id)) $this->load('customer_quote_id', $customer_quote_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'customer_quote_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'customer_quote_id' => NULL,
				'customers_id' => NULL,
				'customers_extra_logins_id' => NULL,
				'account_manager_id' => NULL,
				'sales_team_id' => NULL,
				'status' => NULL,
				'admin_id' => NULL,
				'admin_email_address' => NULL,
				'admin_name' => NULL,
				'admin_ext' => NULL,
				'notes' => NULL,
				'expiration_date' => NULL,
				'url_hash' => NULL,
				'customer_email' => NULL,
				'email_signature' => NULL,
				'send_from_admin' => NULL,
				'created' => NULL,
				'order_id' => NULL,
				'locked' => NULL,
				'released' => NULL,
				'active' => NULL,
				'prepared_by' => NULL
			]
		],
		'customer' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'account_manager' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'sales_team' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'products' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'customer_quote_product_id' => NULL,
				'products_id' => NULL,
				'parent_products_id' => NULL,
				'option_type' => NULL,
				'price' => NULL,
				'quantity' => NULL,
				'locked' => NULL,
				'listing' => NULL
			]
		],
		'history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'customer_quote_history_id' => NULL,
				'admin_id' => NULL,
				'action' => NULL,
				'action_date' => NULL
			]
		]
	];
}
?>
