<?php
$skip_check = TRUE;
require('includes/application_top.php');

ini_set('memory_limit', '256M');

// top admin and accounting
if ($_SESSION['login_groups_id'] == 1 || $_SESSION['login_groups_id'] == 11 || in_array($_SESSION['login_groups_id'], [29, 31])) {
	$hasUndoPermissions = true;
}

$ck_customer = new ck_customer2($_GET['customer_id']);

$payments = $ck_customer->get_payments();
$payments = array_map(function($payment) {
	$payment['type'] = 'Payment/Credit - '.$payment['payment_method_label'];
	return $payment;
}, $payments);

$response = [];

$response['draw'] = $_REQUEST['draw'];
$response['recordsTotal'] = count($payments);
$response['recordsFiltered'] = count($payments);
$response['data'] = [];

if (count($payments) == 0) {
	echo json_encode($response);
	die();
}

$payments = array_reverse($payments);

for ($i = 0; $i < $_REQUEST['length']; $i++) {
	$payment = @$payments[(int)$_REQUEST['start'] + $i];

	if ($payment == null) break; //have we run out of payments?

	$row_array = [];

	$row_array[] = $payment['payment_id'];
	$row_array[] = ck_datetime::datify($payment['payment_date'])->format('m/d/Y');
	$row_array[] = $payment['payment_method_label'];
	$row_array[] = $payment['payment_ref'];
	$row_array[] = CK\text::monetize($payment['payment_amount']);
	$row_array[] = '<a href="?payment_id='.$payment['payment_id'].'&action=invoices_for_payment" class="pop-transactions">'.CK\text::monetize($payment['applied_amount']).'</a>';
	$row_array[] = CK\text::monetize($payment['unapplied_amount']);
	if ($ck_customer->has_payment_notes($payment['payment_id']) && ($note = $ck_customer->get_payment_notes($payment['payment_id']))) {
		$note_display = '';
		$note_display .= strlen($note)>20?'<span class="notes" title="'.htmlspecialchars($note).'">':'';
		$note_display .= substr($note, 0, 20);
		$note_display .= strlen($note)>20?'...</span>':'';

		$row_array[] = $note_display;
	}
	else $row_array[] = '';

	$response['data'][] = $row_array;
}

echo json_encode($response);
