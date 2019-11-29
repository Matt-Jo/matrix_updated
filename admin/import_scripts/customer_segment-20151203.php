<?php
function upload_data($data) {
	$group = array(0, 0, 0, 0);
	$errors = array();
	foreach ($data as $idx => $customer) {
		if ($idx === 0) continue; // skip the header row
		if (!is_numeric($customer[0])) continue; // skip customer numbers that aren't formatted correctly (\PhpOffice\PhpSpreadsheet\Spreadsheet makes them floats)
		if (!in_array($customer[1], array(0, 1, 2, 3))) continue; // skip out of scope customer groups

		$group[$customer[1]]++;

		prepared_query::execute('UPDATE customers SET customer_group = ? WHERE customers_id = ?', array($customer[1], $customer[0]));
	}

	$output = array('Group 0: '.$group[0], 'Group 1: '.$group[1], 'Group 2: '.$group[2], 'Group 3: '.$group[3], 'Upload Complete');

	return array('output' => $output, 'errors' => array());
}
?>