<?php
$skip_check = TRUE;
require('includes/application_top.php');

if (@$_REQUEST['dtp'] == 'dtp') {
	$ck_customer = new ck_customer2($_GET['customer_id']);
	if (in_array($ck_customer->id(), [96285, 127213, 130509, 152080, 161001])) echo '[N/A]';
	else {
		set_time_limit(0);
		ini_set('memory_limit', '1024M');
		$avg_pay = $ck_customer->get_average_days_to_pay();
		if (is_null($avg_pay)) echo '[N/A]';
		else echo $avg_pay;
	}
	exit();
}

$paidInvoices = ck_invoice::get_invoice_list_by_customer($_GET['customer_id'], ck_invoice::PAID, NULL, ($_REQUEST['start']/$_REQUEST['length'])+1, $_REQUEST['length']);

$response = array();

$response['draw'] = @$_REQUEST['draw'];
$response['recordsTotal'] = $paidInvoices['result_count'];
$response['recordsFiltered'] = $paidInvoices['result_count'];
$response['data'] = array();

if ($paidInvoices['result_count'] == 0) {
	echo json_encode($response);
	die();
}

foreach ($paidInvoices['invoices'] as $i => $invoice) {
	$row_array = array();
	$row_array[] = $invoice->id();
	$row_array[] = $invoice->get_header('invoice_date')->format('m/d/Y');
	$row_array[] = $invoice->get_age();
	$row_array[] = '<a href="/admin/orders_new.php?action=edit&oID='.$invoice->get_header('orders_id').'">'.$invoice->get_header('orders_id').'</a>';
	$row_array[] = $invoice->get_terms_po_number();
	$row_array[] = implode(', ', $invoice->get_notes());
	$row_array[] = CK\text::monetize($invoice->get_simple_totals('total'));
	$row_array[] = '<a href="?invoice_id='.$invoice->id().'&action=invoice_transactions" class="pop-transactions">'.CK\text::monetize($invoice->get_paid()).'</a>';
	$row_array[] = CK\text::monetize($invoice->get_balance());
	$response['data'][] = $row_array;
}

echo json_encode($response);
