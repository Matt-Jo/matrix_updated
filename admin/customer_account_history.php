<?php
require('includes/application_top.php');
require_once('includes/modules/accounting_notes.php');

$GLOBALS['use_jquery_1.8.3'] = true;

set_time_limit(0);
@ini_set("memory_limit","512M");

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'customer_search':
			$nameQuery = '';
			$names = explode(' ', $_REQUEST['search_string']);
			if (count($names) == 2) {
				$nameQuery = 'OR (customers_firstname = "'.$names[0].'" AND customers_lastname like "'.$names[1].'%")';
			}
			$customer_set = prepared_query::fetch('select a.entry_company as company_name, concat_ws(" ", c.customers_firstname, c.customers_lastname) as customer_name, c.customers_id from customers c left join address_book a on a.address_book_id=c.customers_default_address_id where entry_company like :search or customers_firstname like :search or customers_lastname like :search '.$nameQuery.' order by company_name, customer_name', cardinality::SET, [':search' => $_REQUEST['search_string'].'%']);

			$customers = [];
			print "<ul>";
			foreach ($customer_set as $row) {
				if (!empty($row['company_name'])) $name=$row['company_name'].' '.$row['customer_name'];
				elseif (!empty($row['customer_name'])) $name=$row['customer_name'];
				print '<li id="'.$row['customers_id'].'">'.$name.'</li>';
			}
			print "</ul>";
			exit;
			break;

		case 'invoice_transactions':
			$invoiceId = $_GET['invoice_id'];
			$payments = prepared_query::fetch("SELECT p.payment_id, p.payment_amount, p2inv.credit_amount, pm.label, p.payment_ref, p2inv.credit_date, n.note_text FROM acc_payments p INNER JOIN payment_method pm ON p.payment_method_id = pm.id INNER JOIN acc_payments_to_invoices p2inv ON p.payment_id = p2inv.payment_id LEFT JOIN acc_notes n on p.payment_id = n.note_type_id WHERE p2inv.invoice_id = :invoice_id", cardinality::SET, [':invoice_id' => $invoiceId]);

			if (!empty($payments)) {
				echo "<table cellpadding=\"5\" cellspacing=\"5\">";
				echo "<tr><th>Payment Id</th><th>Type</th><th>Reference</th><th>Payment Amount</th><th>Applied Amount</th><th>Date</th><th>Notes</th></tr>";
				foreach ($payments as $payment_results) {
					echo "<tr><td>{$payment_results['payment_id']}</td><td>{$payment_results['label']}</td><td>{$payment_results['payment_ref']}</td><td>".CK\text::monetize($payment_results['payment_amount'])."</td><td>".CK\text::monetize($payment_results['credit_amount'])."</td><td>".date("n-d-y H:i A", strtotime($payment_results['credit_date']))."</td><td>".$payment_results['note_text']."</td></tr>";
				}
				echo "</table>";
			}
			else echo "<p>No Payments Found</p>";

			exit;
			break;

		case 'invoices_for_payment':
			$paymentId = $_GET['payment_id'];
			$invoices = prepared_query::fetch("select ai.invoice_id, (select ait.invoice_total_price from acc_invoice_totals ait where ait.invoice_id = ai.invoice_id and (ait.invoice_total_line_type = 'ot_total' or ait.invoice_total_line_type = 'rma_total')) as invoice_total, ai.inv_order_id, ap2i.credit_amount, ap2i.credit_date from acc_invoices ai left join acc_payments_to_invoices ap2i on ai.invoice_id = ap2i.invoice_id where ap2i.payment_id = :payment_id", cardinality::SET, [':payment_id' => $paymentId]);
			if (!empty($invoices)) {
				echo "<table cellpadding=\"5\" cellspacing=\"5\">";
				echo "<tr><th>Invoice Id</th><th>Order Id</th><th>Invoice Amount</th><th>Applied Amount</th><th>Balance Amount</th><th>Applied Date</th></tr>";
				foreach ($invoices as $invoice_results) {
					echo "<tr><td>{$invoice_results['invoice_id']}</td><td>{$invoice_results['inv_order_id']}</td><td>".CK\text::monetize($invoice_results['invoice_total'])."</td><td>".CK\text::monetize($invoice_results['credit_amount'])."</td><td>".CK\text::monetize($invoice_results['invoice_total'] - $invoice_results['credit_amount'])."</td><td>".date("n-d-y H:i A", strtotime($invoice_results['credit_date']))."</td></tr>";
				}
				echo "</table>";
			}
			else echo "<p>No Invoices Found</p>";
			exit;
			break;

		case 'delete_payment':
			$response = [];

			$customer = ck_customer2::get_customer_by_payment_id($_POST['payment_id']);
			if ($payments = $customer->get_payments()) {
				foreach ($payments as $pmt) {
					if ($pmt['payment_id'] != $_POST['payment_id']) continue;

					foreach ($pmt['applications'] as $invoice_payment) {
						$response[] = $invoice_payment['payment_to_invoice_id'];

						$invoice = ck_invoice::get_invoice_by_payment_allocation_id($invoice_payment['payment_to_invoice_id']);
						if ($invoice) $invoice->remove_payment_allocations($invoice_payment['payment_to_invoice_id']);
					}

					prepared_query::execute('DELETE FROM acc_payments_to_invoices WHERE payment_id = :payment_id', [':payment_id' => $pmt['payment_id']]);
					prepared_query::execute('DELETE FROM acc_payments_to_orders WHERE payment_id = :payment_id', [':payment_id' => $pmt['payment_id']]);
					prepared_query::execute('DELETE FROM acc_payments WHERE payment_id = :payment_id', [':payment_id' => $pmt['payment_id']]);
				}
			}
			
			echo json_encode($response);
			exit;
			break;

		case 'delete_payment_to_invoice':
			$invoice = ck_invoice::get_invoice_by_payment_allocation_id($_POST['payment_to_invoice_id']);
			if ($invoice) $invoice->remove_payment_allocations($_POST['payment_to_invoice_id']);
			exit;
			break;

		default:
			break;
	}

}

if (isset($_GET['customer_id'])) {
	// top admin and accounting
	$hasUndoPermissions = false;
	if ($_SESSION['login_groups_id'] == 1 || $_SESSION['login_groups_id'] == 11 || in_array($_SESSION['login_groups_id'], array(29, 31))) {
		$hasUndoPermissions = true;
	}

	$ck_customer = new ck_customer2($_GET['customer_id']);

	$unpaidInvoices = ck_invoice::get_invoice_list_by_customer($_GET['customer_id'], ck_invoice::UNPAID)['invoices'];
	$rmaInvoices = ck_invoice::get_invoice_list_by_customer($_GET['customer_id'], ck_invoice::RMA)['invoices'];
	$creditMemos = ck_invoice::get_invoice_list_by_customer($_GET['customer_id'], ck_invoice::CREDIT_MEMO)['invoices'];
	$unappliedPayments = $ck_customer->get_unapplied_payments();

	$unpaidTotal = 0;
	$unpaidPayments = 0;
	$unpaidBalance = 0;
	foreach ($unpaidInvoices as $invoice) {
		$unpaidTotal += $invoice->get_simple_totals('total');
		$unpaidPayments += $invoice->get_paid();
		$unpaidBalance += $invoice->get_balance();
	}

	$rmaTotal = 0;
	$rmaPayments = 0;
	$rmaBalance = 0;
	foreach ($rmaInvoices as $invoice) {
		$rmaTotal += $invoice->get_simple_totals('total');
		$rmaPayments += $invoice->get_paid();
		$rmaBalance += $invoice->get_balance();
	}

	$cmTotal = 0;
	$cmPayments = 0;
	$cmBalance = 0;
	foreach ($creditMemos as $invoice) {
		$cmTotal += $invoice->get_simple_totals('total');
		$cmPayments += $invoice->get_paid();
		$cmBalance += $invoice->get_balance();
	}
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
		#paid-invoices tr:nth-child(even), #all-transactions tr:nth-child(even), #payments tr:nth-child(even) {background: #CCC}
		#paid-invoices tr:nth-child(odd), #all-transactions tr:nth-child(odd), #payments tr:nth-child(odd) {background: #fff}
	</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script type="text/javascript" language="javascript" src="/admin/includes/javascript/DataTables-1.10.5/media/js/jquery.dataTables.js"></script>
	<link rel="stylesheet" type="text/css" href="/admin/includes/javascript/DataTables-1.10.5/media/css/jquery.dataTables.css"/>
	<!-- header_eof //-->

	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td class="noPrint" width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading">
										Customer Account Statement
										<?php if (!isset($invoices)) { ?>
										<span class="noPrint">
											<span style="font-weight:normal; font-size:12px">Search</span>
											<input id="customer_autocomplete" type="text" size="40" />
											<div id="customer_choices" class="autocomplete" style="border: 1px solid rgb(0, 0, 0); display: none; background-color: rgb(255, 255, 255); z-index: 100;"> </div>
											<script type="text/javascript">
												jQuery(document).ready(function($) {
													$('#customer_autocomplete').focus();
												});

												new Ajax.Autocompleter('customer_autocomplete', "customer_choices", "customer_account_history.php",
												{
													method: 'get',
													minChars: 3,
													paramName: 'search_string',
													parameters: 'action=customer_search',
													afterUpdateElement: function(input, li) {
															window.location='customer_account_history.php?customer_id='+li.id;
													}
												});
											</script>
										</span>
										<?php }
										else {
											if (count($customers)>0) { ?>
										<form method="get" action="customer_account_history.php">
											<select name="customer_id">
												<?php foreach ( $customers as $customer) { ?>
												<option value="<?= $customer['customer_id']; ?>" <?php if ($_GET['customer_id']==$customer['customer_id']) echo 'selected';?>><?= $customer['company_name']; ?></option>
												<?php } ?>
											</select>
											<input type="submit" value="View Invoices" />
										</form>
											<?php }
										} ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php if (!empty($_GET['customer_id'])) { ?>
					<tr>
						<td class="dataTableContent" style="font-size:12px;font-weight: bold;" colspan="10">
							<?= $ck_customer->get_highest_name();
							insert_accounting_notes_manager($_GET['customer_id']); ?>
						</td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top">
										<script type="text/javascript">
											jQuery(document).ready(function($) {
												$("#tabs").tabs();

												$("#outstanding-invoices").tablesorter();
												$("#rma-invoices").tablesorter();
												$("#credit_memos").tablesorter();
												$("#unapplied_payments").tablesorter();

												$("#paid-invoices").dataTable({
													"processing": true,
													"serverSide": true,
													"ajax": "/admin/customer_account_history_paid_invoices.php?customer_id=<?= $_REQUEST['customer_id']; ?>",
													"ordering": false,
													"searching": false,
													"pageLength": 50,
													'lengthChange': true,
													'lengthMenu': [50, 100, 200, 500],
												});

												$("#all-transactions").dataTable({
													"processing": true,
													"serverSide": true,
													"ajax": "/admin/customer_account_history_all_trans.php?customer_id=<?= $_REQUEST['customer_id']; ?>",
													"ordering": false,
													"searching": false,
													"pageLength": 50,
													'lengthChange': true,
													'lengthMenu': [50, 100, 200, 500],
												});

												$("#payments").dataTable({
													"processing": true,
													"serverSide": true,
													"ajax": "/admin/customer_account_history_payments.php?customer_id=<?= $_REQUEST['customer_id']; ?>",
													"ordering": false,
													"searching": false,
													"pageLength": 50,
													'lengthChange': true,
													'lengthMenu': [50, 100, 200, 500],
												});

												$('a#print').click(function() {
													window.print();
												});

												$('#modal').jqm({ajax: '@href', trigger: 'a.pop-transactions', target: '#modal-contents'});

												$(document.body).on('click', 'a.undo-payment', function(event) {
													event.preventDefault();
													var response = confirm('Are you sure you want to remove this payment? Doing so will also remove the associated applications to invoices.');
													var row = $(this).parent().parent();
													var id = $(this).attr('id');

													if (response == true) {
														$.post('<?= $_SERVER['PHP_SELF']; ?>', {action: 'delete_payment', 'payment_id': id}, function(data) {
															$.each(data, function() {
																$('#' + this).parent().parent().remove();
															});
															row.remove();
														}, "json");
													}
												});

												$(document.body).on('click', 'a.undo-payment-allocation', function(event) {
													event.preventDefault();
													var response = confirm('Are you sure you want to remove this payment allocation?');
													var row = $(this).parent().parent();
													var id = $(this).attr('id');

													if (response == true) {
														$.post('<?= $_SERVER['PHP_SELF']; ?>', {action: 'delete_payment_to_invoice', 'payment_to_invoice_id': id}, function() {
															row.remove();
														});
													}
												});

												$('a.notes[title]').qtip({
													position: {
														corner: {
															tooltip: 'bottomRight',
															target: 'topLeft'
														}
													},
													style: { name: 'cream', tip: true }
												});

												jQuery('#dtp_content').html('Loading...');
												jQuery.get(
													'/admin/customer_account_history_paid_invoices.php',
													{
														dtp: 'dtp',
														customer_id: '<?= $_REQUEST['customer_id']; ?>'
													},
													function(data, status_text, xhr) {
														jQuery('#dtp_content').html(data);
													}
												);
											});
										</script>
										<div class="jqmWindow" id="modal">
											<div class="jqmAlertTitle" style="float: right;">
												<a href="#" class="jqmClose"><em>Close</em></a>
											</div>
											<div id="modal-contents" style="clear: both;">
												Please wait...
											</div>
										</div>
										<div class="noPrint" style="font-size:8px; float: right; margin-right: 20px;">
											<a href="#" id="print">Print Current View</a> | <a href="print_account_statement.php?customer_id=<?= $_GET['customer_id']; ?>" target="_blank">Print Account Statement</a>
										</div>

										<!-- Tabs -->
										<div id="tabs" style="clear: both;">
											<ul class="noPrint">
												<li><a href="#tabs-1">Outstanding Invoices</a></li>
												<li><a href="#tabs-2">Paid Invoices</a></li>
												<li><a href="#tabs-7">Unapplied Payments</a></li>
												<li><a href="#tabs-3">Payments</a></li>
												<li><a href="#tabs-4">RMA Invoices</a></li>
												<li><a href="#tabs-5">All Invoices/Transactions</a></li>
												<li><a href="#tabs-6">Credit Memos</a></li>
											</ul>

											<!-- Outstanding Invoices -->
											<div id="tabs-1">
												<?php if (count($unpaidInvoices)) { ?>
												<table id="outstanding-invoices" class="tablesorter">
													<thead>
														<tr>
															<th>Invoice</th>
															<th>Date</th>
															<th>Days</th>
															<th>CK Order #</th>
															<th>Customer PO</th>
															<th class="noPrint">Notes</th>
															<th class="numeric">Total</th>
															<th class="numeric">Payments</th>
															<th class="numeric">Balance</th>
														</tr>
													</thead>
													<tbody>
														<?php foreach ($unpaidInvoices as $index => $invoice) {
																$order = $invoice->get_order(); ?>
														<tr<?= ($index % 2 == 0) ? '' : ' class="odd"' ?>>
															<td><?= $invoice->id() ?></td>
															<td><?= $invoice->get_header('invoice_date')->format('m/d/Y'); ?></td>
															<td><?= $invoice->get_age(); ?></td>
															<td><a href="/admin/orders_new.php?action=edit&oID=<?= $invoice->get_header('orders_id'); ?>"><?= $invoice->get_header('orders_id'); ?></a></td>
															<td><?= $order->get_terms_po_number(); ?></td>
															<td class="noPrint" style="font-size:16px; height:20px; width:250px;">
																<style>
																	.clickable-expander { padding-top:0; margin:0; height:20px; width:250px; cursor:pointer; overflow:hidden; }
																	.clickable-expander:focus { background-color:inherit; user-select:text; opacity:1; position:absolute; height:auto; }
																</style>
																<?php if ($order->has_acc_order_notes()) { ?>
																<div class="clickable-expander" tabindex="1">
																<?php foreach ($order->get_acc_order_notes() as $note) { ?>
																	<p style="padding:0; margin:0; margin-bottom:.5em;">
																		<?= $note['text']; ?>
																		<span style="font-size:12px; margin-right:.3em;">
																			@<?= $note['admin_firstname'].' '.$note['admin_lastname']; ?>
																		</span>
																	</p>
																<?php } ?>
																</div>
																<?php } ?>
															</td>
															<td class="numeric"><?= CK\text::monetize($invoice->get_simple_totals('total')); ?></td>
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

											<!-- Paid Invoices -->
											<div id="tabs-2">
												Avg Days to Pay: <span id="dtp_content"></span>
												<table id="paid-invoices" style="font-size: 12px; text-align:right;">
													<thead>
														<tr>
															<th>Invoice</th>
															<th>Date</th>
															<th>Days</th>
															<th>CK Order #</th>
															<th>Customer PO</th>
															<th>Notes</th>
															<th>Total</th>
															<th>Payments</th>
															<th>Balance</th>
														</tr>
													</thead>
													<tfoot>
														<tr>
															<th>Invoice</th>
															<th>Date</th>
															<th>Days</th>
															<th>CK Order #</th>
															<th>Customer PO</th>
															<th>Notes</th>
															<th>Total</th>
															<th>Payments</th>
															<th>Balance</th>
														</tr>
													</tfoot>
												</table>
											</div>

											<!-- Payments -->
											<div id="tabs-3">
												<table id="payments" style="font-size: 12px; text-align:right;">
													<thead>
														<tr>
															<th>Payment</th>
															<th>Date</th>
															<th>Type</th>
															<th>Reference</th>
															<th>Amount</th>
															<th>Applied</th>
															<th>Balance</th>
															<th>Notes</th>
														</tr>
													</thead>
													<tfoot>
														<tr>
															<th>Payment</th>
															<th>Date</th>
															<th>Type</th>
															<th>Reference</th>
															<th>Amount</th>
															<th>Applied</th>
															<th>Balance</th>
															<th>Notes</th>
														</tr>
													</tfoot>
												</table>
											</div>

											<!-- RMA Invoices -->
											<div id="tabs-4">
												<?php if (count($rmaInvoices)) { ?>
												<table id="rma-invoices" class="tablesorter">
													<thead>
														<tr>
															<th>Invoice</th>
															<th>Date</th>
															<th>Days</th>
															<th>CK Order #</th>
															<th>Customer PO</th>
															<th class="noPrint">Notes</th>
															<th class="numeric">Total</th>
														</tr>
													</thead>
													<tbody>
														<?php $cnt = 0;
														foreach ($rmaInvoices as $invoice) {
															$rma = $invoice->get_rma(); ?>
														<tr<?= ((++$cnt)%2==0) ? '' : ' class="odd"' ?>>
															<td><a href="/admin/invoice.php?oID=<?= $invoice->get_header('orders_id'); ?>&invId=<?= $invoice->id(); ?>"><?= $invoice->id(); ?></a></td>
															<td><?= $invoice->get_header('invoice_date')->format('m/d/Y'); ?></td>
															<td><?= $invoice->get_age(); ?></td>
															<td><a target="_blank" href="/admin/orders_new.php?action=edit&oID=<?= $invoice->get_header('orders_id'); ?>"><?= $invoice->get_header('orders_id'); ?></a></td>
															<td><?= $invoice->get_terms_po_number(); ?></td>
															<td class="noPrint"><?= implode(', ', $invoice->get_notes()); ?></td>
															<td class="numeric"><?= CK\text::monetize($invoice->get_simple_totals('total')); ?></td>
														</tr>
														<?php } ?>
													</tbody>
													<tbody>
														<tr>
															<td style="font-weight: bold;"><?= count($rmaInvoices) ?> Total</td>
															<td colspan="5"></td>

															<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"><?= $rmaBalance; ?></td>
														</tr>
													</tbody>
												</table>
												<?php }
												else { ?>
												<p>No Invoices Found</p>
												<?php } ?>
											</div>

											<!-- All Transactions -->
											<div id="tabs-5">
												<table id="all-transactions" style="font-size: 12px; text-align:right;">
													<thead>
														<tr>
															<th>Order</th>
															<th>Date</th>
															<th>Amount</th>
															<th>Type</th>
															<th>Ref</th>
															<th>Notes</th>
															<?php if ($hasUndoPermissions) { ?>
															<th>Actions</th>
															<?php } ?>
														</tr>
													</thead>
													<tfoot>
														<tr>
															<th>Order</th>
															<th>Date</th>
															<th>Amount</th>
															<th>Type</th>
															<th>Ref</th>
															<th>Notes</th>
															<?php if ($hasUndoPermissions) { ?>
															<th>Actions</th>
															<?php } ?>
														</tr>
													</tfoot>
												</table>
											</div>

											<!-- Credit Memos -->
											<div id="tabs-6">
												<?php if (count($creditMemos)) { ?>
												<table id="credit_memos" class="tablesorter">
													<thead>
														<tr>
															<th>Invoice</th>
															<th>Date</th>
															<th>Days</th>
															<th>CK Order #</th>
															<th>Customer PO</th>
															<th class="noPrint">Notes</th>
															<th class="numeric">Total</th>
														</tr>
													</thead>
													<tbody>
														<?php $cnt = 0;
														foreach ($creditMemos as $invoice) { ?>
														<tr<?= ((++$cnt)%2==0) ? '' : ' class="odd"' ?>>
															<td><?= $invoice->id(); ?></td>
															<td><?= $invoice->get_header('invoice_date')->format('m/d/Y'); ?></td>
															<td><?= $invoice->get_age(); ?></td>
															<td><a href="/admin/orders_new.php?action=edit&oID=<?= $invoice->get_header('orders_id'); ?>"><?= $invoice->get_header('orders_id'); ?></a></td>
															<td><?= $invoice->get_terms_po_number(); ?></td>
															<td class="noPrint"><?= implode(', ', $invoice->get_notes()); ?></td>
															<td class="numeric"><?= CK\text::monetize($invoice->get_simple_totals('total')); ?></td>
														</tr>
														<?php } ?>
													</tbody>
													<tbody>
														<tr>
															<td style="font-weight: bold;"><?= count($creditMemos) ?> Total</td>
															<td colspan="5"></td>
															<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"><?= CK\text::monetize($cmTotal); ?></td>
														</tr>
													</tbody>
												</table>
												<?php }
												else { ?>
												<p>No Credit Memos Found</p>
												<?php } ?>
											</div>

											<!-- Unapplied Payments -->
											<div id="tabs-7">
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
															<th>Notes</th>
														</tr>
													</thead>
													<tbody>
														<?php $cnt = 0;
														$upPayments = 0;
														$upBalance = 0;
														foreach ($unappliedPayments as $payment) {
															$note_text = prepared_query::fetch('SELECT note_text FROM acc_notes WHERE note_type = :acc_payments AND note_type_id = :payment_id', cardinality::SINGLE, [':acc_payments' => 'acc_payments', ':payment_id' => $payment['payment_id']]);
															$paymentTotal = $payment['payment_amount'];
															$unappliedAmount = $payment['unapplied_amount'];
															$upPayments +=	$paymentTotal;
															$upBalance += $unappliedAmount; ?>
														<tr<?= ((++$cnt)%2==0) ? '' : ' class="odd"' ?>>
															<td><?= $payment['payment_id']; ?></td>
															<td><?= $payment['payment_date']->format('m/d/Y'); ?></td>
															<td><?= $payment['payment_method_label']; ?></td>
															<td><?= $payment['payment_ref']; ?></td>
															<td class='numeric'><?= CK\text::monetize($paymentTotal); ?></td>
															<td class="numeric"><a href="?payment_id=<?= $payment['payment_id']; ?>&action=invoices_for_payment" class="pop-transactions"><?= CK\text::monetize($paymentTotal - $unappliedAmount); ?></a></td>
															<td class='numeric'><?= CK\text::monetize($unappliedAmount); ?></td>
															<td><?php if (strlen($note_text) > 20) echo '<a href="#" class="notes" title="'.htmlspecialchars($note_text).'">'; echo substr($note_text, 0, 20); if (strlen($note_text) > 20) echo '...</a>'; ?></td>
														</tr>
														<?php } ?>
													</tbody>
													<tfoot>
														<tr>
															<td style="font-weight: bold;"><?= count($unappliedPayments) ?> Total</td>
															<td colspan="2"></td>
															<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"></td>
															<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"><?= CK\text::monetize($upPayments); ?></td>
															<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;">$0.00</td>
															<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"><?= CK\text::monetize($upBalance); ?></td>
															<td style="border-top: 1px solid #000; text-align: right; font-weight: bold;"></td>
														</tr>
													</tfoot>
												</table>
											</div>
										</div>
										<!-- End Tabs -->
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php } ?>
				</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
