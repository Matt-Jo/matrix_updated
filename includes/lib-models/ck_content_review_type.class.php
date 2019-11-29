<?php
class ck_content_review_type extends ck_types {

	public function __construct($content_review_id=NULL) {
		$this->_init();
		if (!empty($content_review_id)) $this->load('content_review_id', $content_review_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'content_review_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'content_review_id' => NULL,
				'products_id' => NULL,
				'stock_id' => NULL,
				'element_id' => NULL,
				'element' => NULL,
				'image_slot' => NULL,
				'reason_id' => NULL,
				'reason' => NULL,
				'status' => NULL,
				'notes' => NULL,
				'requester_admin_id' => NULL,
				'requester_email_address' => NULL,
				'requester_firstname' => NULL,
				'requester_lastname' => NULL,
				'responder_admin_id' => NULL,
				'responder_email_address' => NULL,
				'responder_firstname' => NULL,
				'responder_lastname' => NULL,
				'notice_date' => NULL,
				'response_date' => NULL
			]
		],
		'listing' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'ipn' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		]
	];
}
?>
