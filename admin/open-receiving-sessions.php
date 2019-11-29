<?php
require('includes/application_top.php');

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;

switch ($action) {
	case 'delete_review_session':
		$po = new ck_purchase_order($_POST['po_id']);
		$po->remove_review($_POST['po_review_id']);
		CK\fn::redirect_and_exit('/admin/open-receiving-sessions.php');
		break;
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

$pos = ck_purchase_order::get_all_pos_with_open_reviews();

$content_map->receiving_sessions = [];

foreach ($pos as $po) {
	$open_reviews = $po->get_open_reviews();
	$open_review_products = $po->get_open_reviewing_products();

	foreach ($open_reviews as $review) {
		$open_lines = count($open_review_products[$review['po_review_id']]);
		$open_quantity = array_reduce($open_review_products[$review['po_review_id']], function($carry, $item) { $carry += $item['qty_received']; return $carry; }, 0);

		$rs = [
			'po_id' => $po->id(),
			'po_number' => $po->get_header('purchase_order_number'),
			'po_review_id' => $review['po_review_id'],
			'review_status' => array_search($review['status'], ck_purchase_order::$review_statuses),
			'created_by_admin' => $review['creator_email_address'],
			'open_lines' => $open_lines,
			'open_quantity' => $open_quantity,
			'created_on' => $review['created_on']->format('Y-m-d'),
			'modified_on' => $review['modified_on']->format('Y-m-d'),
		];

		if ($open_lines <= 0 && $open_quantity <= 0) $rs['close'] = 1;

		$content_map->receiving_sessions[] = $rs;
	}
}

$cktpl->content('includes/templates/page-open-receiving-sessions.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
