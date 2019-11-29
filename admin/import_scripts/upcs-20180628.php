<?php
function upload_data($data) {
	foreach ($data as $idx => $item) {
		if ($idx === 0) continue; // skip the header row

		if (empty($item[0])) {
			echo 'Row ['.($idx+1).'] had no Stock ID'."\n";
			continue;
		}

		//stock_id, upc, unit_of_measure, uom_description, provenance
		$imports = [
			[':stock_id' => $item[0], ':uom_description' => 'EACH', ':unit_of_measure' => 1, ':provenance' => 'CK'],
			[':stock_id' => $item[0], ':uom_description' => 'PACK', ':unit_of_measure' => 10, ':provenance' => 'CK']
		];

		foreach ($imports as $import) {
			$upc_assignment_id = prepared_query::insert('INSERT INTO ck_upc_assignments (stock_id, upc, uom_description, unit_of_measure, provenance) SELECT :stock_id, ou.upc, :uom_description, :unit_of_measure, :provenance FROM ck_owned_upcs ou WHERE ou.upc_assignment_id IS NULL LIMIT 1', $import);
			prepared_query::execute('UPDATE ck_owned_upcs ou JOIN ck_upc_assignments ua ON ou.upc = ua.upc AND ou.upc_assignment_id IS NULL SET ou.upc_assignment_id = ua.upc_assignment_id WHERE ua.upc_assignment_id = :upc_assignment_id', [':upc_assignment_id' => $upc_assignment_id]);
		}
	}

	return array('output' => ['Upload Complete'], 'errors' => []);
}
?>