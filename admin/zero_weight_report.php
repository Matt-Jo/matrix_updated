<?php
require('includes/application_top.php');

$stock_ids = prepared_query::fetch('SELECT psc.stock_id FROM products_stock_control psc LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id WHERE stock_weight = 0.00 AND psc.is_bundle = 0', cardinality::SET);

foreach ($stock_ids as $idx => $stock_id) {
	$ipn = new ck_ipn2($stock_id['stock_id']);
	$data[$idx]['on_hand'] = $ipn->get_inventory('on_hand');
	$data[$idx]['category'] = $ipn->get_header('ipn_category');
	$data[$idx]['bin1'] = $ipn->get_header('bin1');
	$data[$idx]['bin2'] = $ipn->get_header('bin2');
	$data[$idx]['ipn'] = $ipn->get_header('ipn');
	$data[$idx]['has_listing'] = $ipn->has_listings()==true?'yes':'no';
}

if (!empty($_GET['action'])) {
	if ($_GET['action'] == 'export') {
		$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$worksheet = $workbook->getSheet(0);
		$worksheet->setTitle('Zero Weight IPNs');

		$worksheet->getCell('A1')->setValue('IPN');
		$worksheet->getCell('B1')->setValue('Cateogory');
		$worksheet->getCell('C1')->setValue('On Hand Qty');
		$worksheet->getCell('D1')->setValue('Bin1');
		$worksheet->getCell('E1')->setValue('Bin2');
		$worksheet->getCell('F1')->setValue('Listing');

		$count = 2;
		foreach ($data as $stock_data) {
			$worksheet->getCell('A'.$count)->setValue($stock_data['ipn']);
			$worksheet->getCell('B'.$count)->setValue($stock_data['category']);
			$worksheet->getCell('C'.$count)->setValue($stock_data['on_hand']);
			$worksheet->getCell('D'.$count)->setValue($stock_data['bin1']);
			$worksheet->getCell('E'.$count)->setValue($stock_data['bin2']);
			$worksheet->getCell('F'.$count)->setValue($stock_data['has_listing']);
			$count++;
		}

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="zero-weight-report.xlsx"');
		header('Cache-Control: max-age=0');

		$wb_file = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($workbook, 'Xlsx');
		$wb_file->save('php://output');
		exit();

	}
}


//-------------header--------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//-------------end header-------------

//-------body-----------
$content_map = new ck_content();

if (!empty($errors)) {
	$content_map->{'has_errors?'} = 1;
	$content_map->errors = $errors;
}

$content_map->count = count($stock_ids);
$content_map->data = $data;


$cktpl->content('includes/templates/page-zero-weight-report.mustache.html', $content_map);
//---------end body---------

//----------footer----------
$cktpl->close($content_map);
//---------end footer-----------
?>