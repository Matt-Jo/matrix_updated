<?php
function upload_data($data) {
	foreach ($data as $idx => &$item) {
		if ($idx === 0) continue; // skip the header row

		if (empty($item[0])) {
			echo 'Row ['.($idx+1).'] had no IPN [UPC: '.$item[1].']'."\n";
			continue;
		}
		if (empty($item[1])) {
			echo 'Row ['.($idx+1).'] had no UPC [IPN: '.$item[0].']'."\n";
			continue;
		}
		if (empty($item[2])) $item[2] = 'EACH';
		if (empty($item[3])) $item[3] = 0;

		$import = [':ipn' => $item[0], ':upc' => $item[1], ':uom_description' => $item[2], ':unit_of_measure' => $item[3], ':provenance' => 'manufacturer'];

		prepared_query::execute('INSERT INTO ck_upc_assignments (stock_id, upc, unit_of_measure, uom_description, provenance) SELECT psc.stock_id, :upc, :unit_of_measure, :uom_description, :provenance FROM products_stock_control psc LEFT JOIN ck_upc_assignments ua ON psc.stock_id = ua.stock_id AND ua.upc = :upc WHERE psc.stock_name LIKE :ipn AND ua.upc_assignment_id IS NULL', $import);
	}

	//service_locator::getDbService()->query("INSERT INTO ck_upc_assignments (stock_id, upc, provenance) SELECT DISTINCT p.stock_id, p.upc, 'manufacturer' FROM products p LEFT JOIN ck_upc_assignments ua ON p.upc = ua.upc WHERE ua.upc_assignment_id IS NULL AND NULLIF(p.upc, '') IS NOT NULL;");
	//service_locator::getDbService()->query("INSERT INTO ck_upc_assignments (stock_id, upc, provenance, purpose) SELECT DISTINCT p.stock_id, p.amazon_upc, 'amazon', 'asin' FROM products p LEFT JOIN ck_upc_assignments ua ON p.amazon_upc = ua.upc WHERE ua.upc_assignment_id IS NULL AND NULLIF(p.amazon_upc, '') IS NOT NULL;");
	prepared_query::execute('UPDATE ck_owned_upcs ou JOIN ck_upc_assignments ua ON ou.upc = ua.upc SET ou.upc_assignment_id = ua.upc_assignment_id WHERE ou.upc_assignment_id IS NULL;');
	prepared_query::execute("UPDATE ck_upc_assignments ua JOIN ck_owned_upcs ou ON ua.upc_assignment_id = ou.upc_assignment_id SET ua.provenance = 'CK' WHERE ou.upc_id IS NOT NULL;");

	return array('output' => ['Upload Complete'], 'errors' => []);
}
?>