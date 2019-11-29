<?php
require ('includes/application_top.php');

//data export
if (@$_GET['export'] == 'product_exceptions') {
	$product_exception_export_data = prepared_query::fetch('SELECT cfft.feed_failure_tracking_id, cfft.feed, cfft.reason, p.products_model, psc.stock_name, pscc.name AS category, cond.conditions_name FROM ck_feed_failure_tracking cfft LEFT JOIN products p ON cfft.products_id = p.products_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN conditions cond ON psc.conditions = cond.conditions_id WHERE DATE_FORMAT(cfft.failure_date, \'%Y-%m-%d\') = CURDATE() AND cfft.reason != \'Product Turned Off or Admin Only\' AND cfft.products_id IS NOT NULL AND psc.stock_quantity > 0 AND psc.discontinued != 1 AND p.products_status != 1', cardinality::SET);

	$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$worksheet = $workbook->getSheet(0);
	$worksheet->setTitle('Product Feed Exceptions');

	$worksheet->getCell('A1')->setValue('Id');
	$worksheet->getCell('B1')->setValue('Feed Name');
	$worksheet->getCell('C1')->setValue('Reason');
	$worksheet->getCell('D1')->setValue('Ipn');
	$worksheet->getCell('E1')->setValue('Condition');
	$worksheet->getCell('F1')->setValue('Product');
	$worksheet->getCell('G1')->setValue('Category');

	$count = 2;

	foreach ($product_exception_export_data as $prod_exec_export) {
		$worksheet->getCell('A'.$count)->setValue($prod_exec_export['feed_failure_tracking_id']);
		$worksheet->getCell('B'.$count)->setValue($prod_exec_export['feed']);
		$worksheet->getCell('C'.$count)->setValue($prod_exec_export['reason']);
		$worksheet->getCell('D'.$count)->setValue($prod_exec_export['stock_name']);
		$worksheet->getCell('E'.$count)->setValue($prod_exec_export['conditions_name']);
		$worksheet->getCell('F'.$count)->setValue($prod_exec_export['products_model']);
		$worksheet->getCell('G'.$count)->setValue($prod_exec_export['category']);

		$count ++;
	}

	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="product_feed_exceptions.xlsx"');
	header('Cache-Control: max-age=0');

	$wb_file = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($workbook, 'Xlsx');
	$wb_file->save('php://output');
	//must exit to avoid further output of application which voids header functionality
	exit();
}

//---------header-----------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//---------end header-------------

//---------body-------------------
$content_map = new ck_content();

if (!empty($errors)) {
	$content_map->{'has_errors?'} = 1;
	$content_map->errors = $errors;
}

//declare necessary variables
$product_exceptions = [];
$exception_ids = [];

//not the DRYest way to dynamically change the query, but definitely the easiest
if (!empty($_POST['feed_name_selection']) && $_POST['feed_name_selection'] != 'all' && !empty($_POST['feed_name_reason']) && $_POST['feed_name_reason'] != 'all') {
	$product_exception_data = prepared_query::fetch('SELECT cfft.feed_failure_tracking_id, cfft.feed, cfft.reason, p.products_model, psc.stock_name, pscc.name AS category, cond.conditions_name FROM ck_feed_failure_tracking cfft LEFT JOIN products p ON cfft.products_id = p.products_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN conditions cond ON psc.conditions = cond.conditions_id WHERE DATE_FORMAT(cfft.failure_date, \'%Y-%m-%d\') = CURDATE() AND cfft.reason != \'Product Turned Off or Admin Only\' AND cfft.products_id IS NOT NULL AND psc.stock_quantity > 0 AND psc.discontinued != 1 AND p.products_status != 1 AND cfft.feed = :feed AND cfft.reason = :reason', cardinality::SET, [':feed' => $_POST['feed_name_selection'], ':reason' => $_POST['feed_reason_selection']]);
}
else if (!empty($_POST['feed_name_selection']) && $_POST['feed_name_selection'] != 'all') {
	$product_exception_data = prepared_query::fetch('SELECT cfft.feed_failure_tracking_id, cfft.feed, cfft.reason, p.products_model, psc.stock_name, pscc.name AS category, cond.conditions_name FROM ck_feed_failure_tracking cfft LEFT JOIN products p ON cfft.products_id = p.products_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN conditions cond ON psc.conditions = cond.conditions_id WHERE DATE_FORMAT(cfft.failure_date, \'%Y-%m-%d\') = CURDATE() AND cfft.reason != \'Product Turned Off or Admin Only\' AND cfft.products_id IS NOT NULL AND psc.stock_quantity > 0 AND psc.discontinued != 1 AND p.products_status != 1 AND cfft.feed = :feed', cardinality::SET, [':feed' => $_POST['feed_name_selection']]);
}
else if (!empty($_POST['feed_reason_selection']) && $_POST['feed_reason_selection'] != 'all') {
	$product_exception_data = prepared_query::fetch('SELECT cfft.feed_failure_tracking_id, cfft.feed, cfft.reason, p.products_model, psc.stock_name, pscc.name AS category, cond.conditions_name FROM ck_feed_failure_tracking cfft LEFT JOIN products p ON cfft.products_id = p.products_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN conditions cond ON psc.conditions = cond.conditions_id WHERE DATE_FORMAT(cfft.failure_date, \'%Y-%m-%d\') = CURDATE() AND cfft.reason != \'Product Turned Off or Admin Only\' AND cfft.products_id IS NOT NULL AND psc.stock_quantity > 0 AND psc.discontinued != 1 AND p.products_status != 1 AND cfft.reason = :reason', cardinality::SET, [':reason' => $_POST['feed_reason_selection']]);
}
else {
	$product_exception_data = prepared_query::fetch('SELECT cfft.feed_failure_tracking_id, cfft.feed, cfft.reason, p.products_model, psc.stock_name, pscc.name AS category, cond.conditions_name FROM ck_feed_failure_tracking cfft LEFT JOIN products p ON cfft.products_id = p.products_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN conditions cond ON psc.conditions = cond.conditions_id WHERE DATE_FORMAT(cfft.failure_date, \'%Y-%m-%d\') = CURDATE() AND cfft.reason != \'Product Turned Off or Admin Only\' AND cfft.products_id IS NOT NULL AND psc.stock_quantity > 0 AND psc.discontinued != 1 AND p.products_status != 1', cardinality::SET);
}
//this is up here so that the export can use this data, as well
if (!empty($product_exception_data)) {
	foreach ($product_exception_data as $ped) {
		$product_exceptions[$ped['feed_failure_tracking_id']] = $ped;
		$exception_ids[] = $ped['feed_failure_tracking_id'];
	}
}

$feed_list = prepared_query::fetch('SELECT feed FROM ck_feed_failure_tracking WHERE DATE_FORMAT(failure_date, \'%Y-%m-%d\') = CURDATE() GROUP BY feed', cardinality::SET);
$feed_reason = prepared_query::fetch('SELECT reason FROM ck_feed_failure_tracking WHERE DATE_FORMAT(failure_date, \'%Y-%m-%d\') = CURDATE() AND feed_failure_tracking_id GROUP BY reason', cardinality::SET);

if (!empty($feed_list)) {
	$content_map->feed_list = [];
	foreach ($feed_list as $fl) {
		$content_map->feed_list[] = $fl;
	}
}

if (!empty($feed_reason)) {
	$content_map->feed_reason = [];
	foreach ($feed_reason as $fr) {
	    $content_map->feed_reason[] = $fr;
    }
}

if (!empty($product_exceptions)) {
	$content_map->product_exceptions = [];
	foreach ($product_exceptions as $prod_exc) {
		$content_map->product_exceptions[] = $prod_exc;
	}
}

unset($product_exception_data);

$cktpl->content('includes/templates/page-product-feed-exceptions.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>