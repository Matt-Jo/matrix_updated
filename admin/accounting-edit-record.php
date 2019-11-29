<?php
require('includes/application_top.php');

if ($__FLAG['ajax']) {
	$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;

	$response = [];

	switch ($action) {
		case 'record-lookup':
			$response['results'] = [];
			$record_id = trim($_GET['record_id']);

			if (empty($record_id)) break;

			if ($records = prepared_query::fetch("(SELECT invoice_id as record_id, 'Invoice' as record_type FROM acc_invoices WHERE CAST(invoice_id as CHAR) LIKE :record_id) UNION (SELECT orders_id as record_id, 'Order' as record_type FROM orders WHERE CAST(orders_id as CHAR) LIKE :record_id) UNION (SELECT id as record_id, 'RMA' as record_type FROM rma WHERE CAST(id as CHAR) LIKE :record_id) ORDER BY record_id ASC", cardinality::SET, [':record_id' => '%'.$record_id.'%'])) {
				foreach ($records as &$record) {
					$record['record_id'] = preg_replace('/('.$record_id.')/i', '<strong>$1</strong>', $record['record_id']);
					$response['results'][] = $record;
				}
			}

			break;
	}

	echo json_encode($response);
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

$cktpl->content('includes/templates/page-accounting-edit-record.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
