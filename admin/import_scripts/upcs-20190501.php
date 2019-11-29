<?php
function upload_data($data) {
	foreach ($data as $idx => $item) {
		if ($idx === 0) continue; // skip the header row

		if (empty($item[0])) {
			echo 'Row ['.($idx+1).'] had no IPN'."\n";
			continue;
		}

		$ipn = ck_ipn2::get_ipn_by_ipn($item[0]);

		if (!$ipn->id()) continue;

		$ipn->create_upc(['upc' => NULL, 'provenance' => 'CK', 'uom_description' => 'EACH', 'unit_of_measure' => 1]);
		$ipn->create_upc(['upc' => NULL, 'provenance' => 'CK', 'uom_description' => 'PACK', 'unit_of_measure' => 10]);
		$ipn->create_upc(['upc' => NULL, 'provenance' => 'CK', 'uom_description' => 'CASE']);
	}

	return array('output' => ['Upload Complete'], 'errors' => []);
}
?>