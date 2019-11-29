<?php
$skip_check = TRUE;
require('includes/application_top.php');

set_time_limit(0);
ini_set('memory_limit', '1024M');

// top admin and accounting
if ($_SESSION['login_groups_id'] == 1 || $_SESSION['login_groups_id'] == 11 || in_array($_SESSION['login_groups_id'], [29, 31])) {
	$hasUndoPermissions = true;
}

$ck_customer = new ck_customer2($_GET['customer_id']);

if (in_array($ck_customer->id(), [96285, 127213, 130509, 152080, 161001])) {
	ck_invoice::$list_context = 'marketplace';
	$all_payments = $ck_customer->get_limited_payments();
}
else {
	$all_payments = $ck_customer->get_payments();
}

$all_transaction_count = 0;

$paidInvoices = ck_invoice::get_invoice_list_by_customer($_GET['customer_id'], ck_invoice::PAID);
$all_transaction_count += $paidInvoices['result_count'];
$paidInvoices = array_map(function($invoice) {
	$inv = $invoice->get_header();
	$inv['invoice'] = $invoice;
	$inv['type'] = 'Invoice';
	return $inv;
}, $paidInvoices['invoices']);

$unpaidInvoices = ck_invoice::get_invoice_list_by_customer($_GET['customer_id'], ck_invoice::UNPAID);
$all_transaction_count += $unpaidInvoices['result_count'];
$unpaidInvoices = array_map(function($invoice) {
	$inv = $invoice->get_header();
	$inv['invoice'] = $invoice;
	$inv['type'] = 'Invoice';
	return $inv;
}, $unpaidInvoices['invoices']);

$rmaInvoices = ck_invoice::get_invoice_list_by_customer($_GET['customer_id'], ck_invoice::RMA);
$all_transaction_count += $rmaInvoices['result_count'];
$rmaInvoices = array_map(function($invoice) {
	$inv = $invoice->get_header();
	$inv['invoice'] = $invoice;
	$inv['type'] = 'Refund Invoice';
	return $inv;
}, $rmaInvoices['invoices']);

$all_transaction_count += count($all_payments);
$all_payments = array_map(function($payment) {
	$payment['type'] = 'Payment/Credit - '.$payment['payment_method_label'];
	return $payment;
}, $all_payments);
$all_applications = array_reduce($all_payments, function($applications, $payment) {
	$applications = array_merge($applications, $payment['applications']);
	return $applications;
}, []);
$all_transaction_count += count($all_applications);

$all_transactions = array_merge($paidInvoices, $unpaidInvoices, $rmaInvoices, $all_payments, $all_applications);
$all_transactions = array_filter($all_transactions); // just copying the logic from what it was before

$dates = [];
foreach ($all_transactions as $key => $transaction) {
	if (!empty($transaction['credit_date'])) $dates[$key] = ck_datetime::datify($transaction['credit_date']);
	elseif (!empty($transaction['payment_date'])) $dates[$key] = ck_datetime::datify($transaction['payment_date']);
	elseif (!empty($transaction['invoice_date'])) $dates[$key] = ck_datetime::datify($transaction['invoice_date']);
}

array_multisort($dates, $all_transactions);

$response = [];

$response['draw'] = $_REQUEST['draw'];
$response['recordsTotal'] = $all_transaction_count;
$response['recordsFiltered'] = $all_transaction_count;
$response['data'] = [];

if (count($all_transactions) == 0) {
	echo json_encode($response);
	die();
}

$all_transactions = array_slice(array_reverse($all_transactions), $_REQUEST['start'], $_REQUEST['length']);

foreach ($all_transactions as $i => $transaction) {
	//have we run out of transactions?
	if ($transaction == null) break;

	$row_array = [];

	if (!empty($transaction['payment_to_invoice_id'])) {
		$row_array[] = '&nbsp;';
		$row_array[] = ck_datetime::datify($transaction['credit_date'])->format('m/d/Y');
		$row_array[] = CK\text::monetize($transaction['credit_amount']);
		$row_array[] = 'Payment Allocation';
		$row_array[] = 'Applied '.$transaction['credit_amount'].' to Invoice # '.$transaction['invoice_id'];
		$row_array[] = '&nbsp;';

		if ($hasUndoPermissions) {
			$row_array[] = '<a href="#" id="'.$transaction['payment_to_invoice_id'].'" class="undo-payment-allocation">Remove Payment-Invoice Allocation</a>'; //MMD - TODO - prolly broke
		}
	}
	elseif (!empty($transaction['payment_id'])) {
		$row_array[] = '&nbsp;';
		$row_array[] = ck_datetime::datify($transaction['payment_date'])->format('m/d/Y');
		$row_array[] = CK\text::monetize($transaction['payment_amount']);
		$row_array[] = $transaction['type'];
		$row_array[] = $transaction['payment_ref'];
		if ($ck_customer->has_payment_notes($transaction['payment_id']) && ($note = $ck_customer->get_payment_notes($transaction['payment_id']))) {
			$note_display = '';
			$note_display .= strlen($note)>20?'<span class="notes" title="'.htmlspecialchars($note).'">':'';
			$note_display .= substr($note, 0, 20);
			$note_display .= strlen($note)>20?'...</span>':'';

			$row_array[] = $note_display;
		}
		else $row_array[] = '';

		if ($hasUndoPermissions) {
			$row_array[] = '<a href="acc_record_editor.php?pID='.$transaction['payment_id'].'">Edit Payment</a> <a href="#" id="'.$transaction['payment_id'].'" class="undo-payment">Remove Payment</a>'; //MMD - TODO - prolly broke
		}
	}
	elseif (!empty($transaction['invoice_id'])) {
		$row_array[] = $transaction['invoice']->has('orders_id')?'<a href="/admin/orders_new.php?action=edit&oID='.$transaction['invoice']->get_header('orders_id').'">'.$transaction['invoice']->get_header('orders_id').'</a>':'&nbsp;';
		$row_array[] = $transaction['invoice']->get_header('invoice_date')->format('m/d/Y');
		$row_array[] = CK\text::monetize($transaction['invoice']->get_simple_totals('total'));
		$row_array[] = $transaction['type'];
		$row_array[] = '&nbsp;';
		$row_array[] = (strlen(implode(', ', $transaction['invoice']->get_notes()))>20?'<span class="notes" title="'.htmlspecialchars(implode(', ', $transaction['invoice']->get_notes())).'">':'').substr(implode(', ', $transaction['invoice']->get_notes()), 0, 20).(strlen(implode(', ', $transaction['invoice']->get_notes()))>20?'...</span>':''); //MMD - TODO - prolly broke

		if ($hasUndoPermissions) {
			$row_array[] = '';
		}
	}

	$response['data'][] = $row_array;
}

echo json_encode($response);
