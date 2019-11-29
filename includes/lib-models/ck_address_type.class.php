<?php
class ck_address_type extends ck_types {

	public function __construct($address_book_id=NULL) {
		$this->_init();
		if (!empty($address_book_id)) $this->load('address_book_id', $address_book_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'address_book_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'address_book_id' => NULL,
				'customers_id' => NULL,
				'gender' => NULL,
				'company_name' => NULL,
				'first_name' => NULL,
				'last_name' => NULL,
				'address1' => NULL,
				'address2' => NULL,
				'postcode' => NULL,
				'city' => NULL,
				'state' => NULL,
				'countries_id' => NULL,
				'country' => NULL,
				'countries_iso_code_2' => NULL,
				'countries_iso_code_3' => NULL,
				'country_address_format_id' => NULL,
				'country_default_postcode' => NULL,
				'zone_id' => NULL,
				'state_region_code' => NULL,
				'state_region_name' => NULL,
				'region_country_match' => NULL,
				'telephone' => NULL,
				'website' => NULL,
				'default_address' => NULL
			]
		],
		'customer' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		]
	];
}
?>
