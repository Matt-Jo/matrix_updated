<?php
require('includes/application_top.php');

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'customer_search':
			$nameQuery = '';
			$names = explode(' ', $_REQUEST['search_string']);
			if (count($names) == 2) {
				$nameQuery = ' OR (customers_firstname = "'.$names[0].'" AND customers_lastname like "'.$names[1].'%")';
			}
			$result=prepared_query::fetch('select a.entry_company as company_name, concat_ws(" ", c.customers_firstname, c.customers_lastname) as customer_name, c.customers_id from customers c left join address_book a on a.address_book_id=c.customers_default_address_id where entry_company like :search or customers_firstname like :search or customers_lastname like :search '.$nameQuery.' order by company_name, customer_name', cardinality::SET, [':search' => $_REQUEST['search_string'].'%']);
			$customers=array();
			print "<ul>";
			foreach ($result as $row) {
				if (!empty($row['company_name'])) {
					$name=$row['company_name'].' '.$row['customer_name'];
				} elseif (!empty($row['customer_name'])) {
					$name=$row['customer_name'];
				}
				print '<li id="'.$row['customers_id'].'">'.$name.'</li>';
			}
			print "</ul>";
			exit;
			break;

		case 'invoice_transactions':
			$invoiceId = $_GET['invoice_id'];
			if ($payments = prepared_query::fetch("SELECT p.payment_id, p.payment_amount, p2inv.credit_amount, pm.label, p.payment_ref, p2inv.credit_date, n.note_text FROM acc_payments p INNER JOIN payment_method pm ON p.payment_method_id = pm.id INNER JOIN acc_payments_to_invoices p2inv ON p.payment_id = p2inv.payment_id LEFT JOIN acc_notes n on p.payment_id = n.note_type_id WHERE p2inv.invoice_id = :invoice_id", cardinality::SET, [':invoice_id' => $invoiceId])) {
				echo "<table cellpadding=\"5\" cellspacing=\"5\">";
				echo "<tr><th>Payment Id</th><th>Type</th><th>Reference</th><th>Payment Amount</th><th>Applied Amount</th><th>Date</th><th>Notes</th></tr>";
				foreach ($payments as $payment_results) {
					echo "<tr><td>{$payment_results['payment_id']}</td><td>{$payment_results['label']}</td><td>{$payment_results['payment_ref']}</td><td>".CK\text::monetize($payment_results['payment_amount'])."</td><td>".CK\text::monetize($payment_results['credit_amount'])."</td><td>".date("n-d-y H:i A", strtotime($payment_results['credit_date']))."</td><td>".$payment_results['note_text']."</td></tr>";
				}
				echo "</table>";
			} else {
				echo "<p>No Payments Found</p>";
			}

			exit;
			break;

		case 'invoices_for_payment':
			$paymentId = $_GET['payment_id'];
			if ($invoices = prepared_query::fetch("select ai.invoice_id, (select ait.invoice_total_price from acc_invoice_totals ait where ait.invoice_id = ai.invoice_id and (ait.invoice_total_line_type = 'ot_total' or ait.invoice_total_line_type = 'rma_total')) as invoice_total, ai.inv_order_id, ap2i.credit_amount, ap2i.credit_date from acc_invoices ai left join acc_payments_to_invoices ap2i on ai.invoice_id = ap2i.invoice_id where ap2i.payment_id = :payment_id", cardinality::SET, [':payment_id' => $paymentId])) {
				echo "<table cellpadding=\"5\" cellspacing=\"5\">";
				echo "<tr><th>Invoice Id</th><th>Order Id</th><th>Invoice Amount</th><th>Applied Amount</th><th>Balance Amount</th><th>Applied Date</th></tr>";
				foreach ($invoices as $invoice_results) {
					echo "<tr><td>{$invoice_results['invoice_id']}</td><td>{$invoice_results['inv_order_id']}</td><td>".CK\text::monetize($invoice_results['invoice_total'])."</td><td>".CK\text::monetize($invoice_results['credit_amount'])."</td><td>".CK\text::monetize($invoice_results['invoice_total'] - $invoice_results['credit_amount'])."</td><td>".date("n-d-y H:i A", strtotime($invoice_results['credit_date']))."</td></tr>";
				}
				echo "</table>";
			}
			else {
				echo "<p>No Invoices Found</p>";
			}
			exit;
			break;


 }

}

if (isset($_REQUEST['customer_id'])) {
	// top admin and accounting
	if ($_SESSION['login_groups_id'] == 1 || $_SESSION['login_groups_id'] == 11 || in_array($_SESSION['login_groups_id'], array(29, 31)) ) {
		$hasUndoPermissions = true;
	}

	$ck_customer = new ck_customer2($_REQUEST['customer_id']);

	$unpaidInvoices = $ck_customer->get_unpaid_invoices();

	$unpaidTotal = 0;
	$unpaidPayments = 0;
	$unpaidBalance = 0;
	foreach ($unpaidInvoices as $invoice) {
		$unpaidTotal += $invoice->get_simple_totals('total');
		$unpaidPayments += $invoice->get_paid();
		$unpaidBalance += $invoice->get_balance();
	}

	$upPayments = 0;
	$upBalance = 0;

	$payments = $ck_customer->get_payments();
	$payments = array_map(function($payment) {
		$payment['type'] = 'Payment/Credit - '.$payment['payment_method_label'];
		return $payment;
	}, $payments);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=7" />
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<link rel="stylesheet" type="text/css" href="/admin/includes/stylesheet.css" />
	<title><?= TITLE; ?></title>
	<style type="text/css">
		.dataTableContent { padding: 5px; max-width: 250px;}
		#modal-contents{overflow: auto; height: 400px}
		#modal-contents table{font-size: 8pt;}
		#modal-contents table tr th{white-space: nowrap; background-color: #dcdcdc;}
	</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="javascript:window.print();">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->

	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td class="noPrint" width="<?= BOX_WIDTH; ?>" valign="top"></td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<?php if (!empty($_REQUEST['customer_id'])) { ?>
					<tr>
						<td class="dataTableContent" style="font-size:12px;font-weight: bold;" colspan="10"><?= $ck_customer->get_highest_name(); ?></td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top">
										<!-- Tabs -->
										<!-- Outstanding Invoices -->
										<div id="tabs-1">
											<?php if (count($unpaidInvoices)) { ?>
											<h2>Outstanding Invoices</h2>
											<table id="outstanding-invoices" class="tablesorter">
												<thead>
													<tr>
														<th>Invoice</th>
														<th>Date</th>
														<th>Days</th>
														<th>CK Order #</th>
														<th colspan="2">Customer PO</th>
														<th class="numeric">Total</th>
														<th class="numeric">Payments</th>
														<th class="numeric">Balance</th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ($unpaidInvoices as $index => $invoice) {
														$order = $invoice->get_order(); ?>
													<tr <?= ($index % 2 == 0)?'':'class="odd"'; ?>>
														<td><?= $invoice->id() ?></td>
														<td><?= $invoice->get_header('invoice_date')->format('m/d/Y'); ?></td>
														<td><?= $invoice->get_age(); ?></td>
														<td><a href="/admin/orders_new.php?action=edit&oID=<?= $order->id(); ?>"><?= $order->id(); ?></a></td>
														<td colspan="2"><?= $order->get_terms_po_number(); ?></td>
														<td class="numeric"><?= CK\text::monetize($invoice->get_simple_totals('total')) ?></td>
														<td class="numeric"><a href="?invoice_id=<?= $invoice->id(); ?>&action=invoice_transactions" class="pop-transactions"><?= CK\text::monetize($invoice->get_paid()); ?></a></td>
														<td class="numeric"><?= CK\text::monetize($invoice->get_balance()); ?></td>
													</tr>
													<?php } ?>
												</tbody>
												<tbody>
													<tr>
														<td style="font-weight: bold;"><?= count($unpaidInvoices) ?> Total</td>
														<td colspan="4"></td>
														<td class="noPrint">&nbsp;</td>
														<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"><?= CK\text::monetize($unpaidTotal); ?></td>
														<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"><?= CK\text::monetize($unpaidPayments); ?></td>
														<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"><?= CK\text::monetize($unpaidBalance); ?></td>
													</tr>
												</tbody>
											</table>
											<?php }
											else { ?>
											<p>No Invoices Found</p>
											<?php } ?>
										</div>
										<!-- End Outstanding Invoices -->
										<!-- Paid Invoices -->
										<div id="tabs-7">
											<?php if (count($payments)) { ?>
											<table id="unapplied_payments" class="tablesorter">
												<thead>
													<tr>
														<th>Payment</th>
														<th>Date</th>
														<th>Type</th>
														<th>Reference</th>
														<th>Amount</th>
														<th>Applied</th>
														<th>Balance</th>
													</tr>
												</thead>
												<tbody>
													<?php $cnt = 0;
													foreach ($payments as $payment) {
														if ($payment['unapplied_amount'] > 0) {
															$upPayments += $payment['payment_amount'];
															$upBalance += $payment['unapplied_amount']; ?>
													<tr <?= ((++$cnt)%2==0) ? '' : 'class="odd"'; ?>>
														<td><?= $payment['payment_id']; ?></td>
														<td><?= $payment['payment_date']->format('m/d/Y'); ?></td>
														<td><?= $payment['payment_method_label']; ?></td>
														<td><?= $payment['payment_ref']; ?></td>
														<td class='numeric'><?= CK\text::monetize($payment['payment_amount']); ?></td>
														<td class="numeric"><a href="?payment_id=<?= $payment['payment_id']; ?>&action=invoices_for_payment" class="pop-transactions"><?= CK\text::monetize($payment['applied_amount']); ?></a></td>
														<td class='numeric'><?= CK\text::monetize($payment['unapplied_amount']); ?></td>
													</tr>
														<?php }
													} ?>
												</tbody>
												<tbody>
													<tr>
														<td style="font-weight: bold;"><?= count($payments) ?> Total</td>
														<td colspan="2"></td>
														<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"></td>
														<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"><?= CK\text::monetize($upPayments); ?></td>
														<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;">$0.00</td>
														<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"><?= CK\text::monetize($upBalance); ?></td>
														<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"></td>
													</tr>
												</tbody>
											</table>
												<?php $true_balance = ($unpaidBalance - $upBalance); ?>
											<div class="main" align="right"><b>Net Balance <?= CK\text::monetize($true_balance); ?></b></div>
											<?php }
											else { ?>
											<p>No Unapplied Payments Found</p>
											<?php } ?>
										</div>
									</td>
									<!-- End Tabs -->
								</tr>
							</table>
						</td>
					</tr>
					<?php } ?>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
