<?php
class ck_vendor_rfq_type extends ck_types {

	public function __construct($rfq_id=NULL) {
		$this->_init();
		if (!empty($rfq_id)) $this->load('rfq_id', $rfq_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'rfq_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'rfq_id' => NULL,
				'nickname' => NULL,
				'admin_id' => NULL,
				'admin_email' => NULL,
				'request_type' => NULL,
				'subject_line' => NULL,
				'request_details' => NULL,
				'published_date' => NULL,
				'expiration_date' => NULL,
				'active' => NULL,
				'created_date' => NULL,
				'full_email_text' => NULL
			]
		],
		'products' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'rfq_product_id' => NULL,
				'stock_id' => NULL,
				'model_alias' => NULL,
				'conditions_id' => NULL,
				'quantity' => NULL,
				'qtyplus' => NULL,
				'comment' => NULL
			]
		],
		'rfq_responses' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'rfq_response_id' => NULL,
				'customers_id' => NULL,
				'customers_extra_login_id' => NULL,
				'email' => NULL,
				'name' => NULL,
				'prior_relationship' => NULL,
				'vendors_id' => NULL,
				'notes' => NULL,
				'shipping_included' => NULL,
				'zip_code' => NULL,
				'created_date' => NULL,
				'last_update' => NULL
			]
		],
		'rfq_response_products' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'rfq_response_product_id' => NULL,
				'rfq_response_id' => NULL,
				'stock_id' => NULL,
				'conditions_id' => NULL,
				'quantity' => NULL,
				'price' => NULL,
				'notes' => NULL,
				'interested' => NULL
			]
		]
	];
}
?>
