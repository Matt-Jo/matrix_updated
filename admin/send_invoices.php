<?php
require_once(__DIR__.'/../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;
$path = dirname(__FILE__);

$cli_flag = [];
if ($cli && !empty($argv[1])) {
	for ($i=1; $i<count($argv); $i++) {
		$flag = explode('=', $argv[$i], 2);
		$cli_flag[$flag[0]] = !empty($flag[1])?$flag[1]:TRUE;
	}
}

if ($cli && !empty($cli_flag['--start'])) $startdate = new DateTime($cli_flag['--start']);
elseif (!$cli && !empty($_REQUEST['start'])) $startdate = new DateTime($_REQUEST['start']);
else {
	$startdate = new DateTime();
	$startdate->sub(new DateInterval('P1D'));
}

if ($cli && !empty($cli_flag['--end'])) $enddate = new DateTime($cli_flag['--end']);
elseif (!$cli && !empty($_REQUEST['end'])) $enddate = new DateTime($_REQUEST['end']);
else {
	$enddate = new DateTime();
}

$resend = FALSE;
if ($cli && !empty($cli_flag['--resend'])) $resend = TRUE;
elseif (!$cli && !empty($_REQUEST['resend'])) $resend = TRUE;

if (($invoices = prepared_query::fetch('SELECT DISTINCT i.inv_order_id, i.customer_id, o.payment_method_id, i.paid_in_full FROM acc_invoices i JOIN orders o ON i.inv_order_id = o.orders_id WHERE IFNULL(i.inv_order_id, 0) > 0 AND  i.inv_date >= :start_date AND i.inv_date <= :end_date AND (:resend = 1 OR i.sent = 0) GROUP BY i.inv_order_id', cardinality::SET, [':start_date' => $startdate->format('Y-m-d H:i:s'), ':end_date' => $enddate->format('Y-m-d H:i:s'), ':resend' => $resend?1:0]))) {
	foreach ($invoices as $invoice) {
		$customer = new ck_customer2($invoice['customer_id']);
		$invoiceRecipients = $customer->has_contacts()?array_filter($customer->get_contacts(), function($c) { return $c['contact_type_id'] == 2; }):[];

		if (count($invoiceRecipients) == 0 && (!in_array($invoice['payment_method_id'], [6, 7, 15]) || $invoice['paid_in_full'] == 1)) {
			var_dump(['skipping' => [count($invoiceRecipients), $invoice['payment_method_id'], $invoice['paid_in_full']]]);
			continue;
		}

		send_invoice_email($invoice['inv_order_id'], $resend);
	}
}
?>
