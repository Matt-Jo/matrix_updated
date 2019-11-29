<?php
function upload_data($data) {
	$output = array();
	if (!prepared_query::fetch("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'acquisition_source'", cardinality::SINGLE, [DB_DATABASE])) {
		prepared_query::execute('ALTER TABLE customers ADD acquisition_source VARCHAR(40) NULL DEFAULT NULL AFTER customer_group');
		$output[] = 'added acquisition_source';
	}

	$errors = array();

	foreach ($data as $idx => $customer) {
		if ($idx === 0) continue; // skip the header row
		if (!is_numeric($customer[0])) continue; // skip customer numbers that aren't formatted correctly
		$sources = array(
			'brokerbin',
			'marketplace',
			'other',
			'phone',
			'powersource',
			'predatesinfo',
			'uneda',
			'web-direct',
			'web-email',
			'web-other',
			'web-paid',
			'web-seo'
		);
		if (!in_array(preg_replace('/\s/', '', strtolower($customer[1])), $sources)) {
			$errors[] = $customer[1].' Source was not listed as an option';
			continue; // skip out of scope acquisition sources
		}

		prepared_query::execute('UPDATE customers SET acquisition_source = ? WHERE customers_id = ?', array($customer[1], $customer[0]));
	}

	$output[] = 'Upload Complete';

	return array('output' => $output, 'errors' => $errors);
}
?>