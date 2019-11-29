<?php
chdir('../..');
require_once('includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

@ini_set("memory_limit","2048M");
set_time_limit(0);

debug_tools::mark('START');

try {
	require_once('admin/includes/functions/avatax.php');

	$invoices = prepared_query::fetch('SELECT invoice_id, inv_order_id, inv_date, credit_memo FROM acc_invoices WHERE DATE(inv_date) >= :start_date AND DATE(inv_date) <= :end_date AND inv_order_id IS NOT NULL', cardinality::SET, [':start_date' => '2017-12-12', ':end_date' => '2018-01-19']);

	foreach ($invoices as $invoice) {
		$invoice_date = new DateTime($invoice['inv_date']);
		if (avatax_post_tax($invoice['invoice_id'], $invoice['inv_order_id'], FALSE, $invoice_date->format('Y-m-d')) !== FALSE) {
			var_dump($invoice);
		}
	}
}
catch (Exception $e) {
	echo $e->getMessage();
}

debug_tools::clear_sub_timer_context();

debug_tools::mark('Done');
?>
