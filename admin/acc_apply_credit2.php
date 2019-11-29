<?php
require('includes/application_top.php');

ini_set('max_execution_time', 500);

if (isset($_POST['payment_submit'])) {
	if ($_FILES) {
		if ($_REQUEST['customer_id'] == 96285 && $_FILES['payment_upload']['error'] != UPLOAD_ERR_NO_FILE) {
			$credit_amount = 0;
			$refund_amount = 0;
			$spreadsheet = new spreadsheet_import($_FILES['payment_upload']);
			$consolidated = [];
			$row_count = 0;
			foreach ($spreadsheet as $idx => $row) {
				$row_count ++;
				if ($row_count > 4) {
					//if ($row[3] == 'Refund') $refund_amount += $row[6];
					if ($row[4] != 'Amazon fees' && $row[3] != 'Refund') {
						$credit_amount += $row[6];
						if (empty($consolidated[$row[1]])) $consolidated[$row[1]] = ['amount' => 0];
						$consolidated[$row[1]]['amount'] += $row[6];
					}
				}
			}
		}
	}

	if (isset($_POST['payment_method_id'])) {
		$payment_id = ck_payment::legacy_insert_credit($_REQUEST['customer_id'], null, $_REQUEST['payment_method_id'], $_POST['payment_ref'], empty($_REQUEST['amount'])?$credit_amount:$_REQUEST['amount'], $_POST['date']);
		ck_payment::legacy_insert_note($_POST['reason'], 'acc_payments', $payment_id);
		$messageStack->add_session('Payment '.$payment_id.' inserted successfully.', 'success');

		// redirect back to dashboard if there are no outstanding invoices
		$customer = new ck_customer2($_REQUEST['customer_id']);
		$invoices = $customer->get_outstanding_invoices_direct();
		if (empty($invoices)) CK\fn::redirect_and_exit('/admin/acc_dashboard.php');
	}
}

if (!isset($_REQUEST['customer_id']) && !isset($_REQUEST['payment_id'])) throw new Exception("Missing customer_id");

if (empty($payment_id) && !empty($_REQUEST['payment_id'])) $payment_id = $_REQUEST['payment_id'];
if (!empty($payment_id)) $payment = ck_payment::get_with_instance($payment_id);

if (empty($payment)) $payment_remaining = 0;
else $payment_remaining = $payment->get_hard_unapplied_amount();

if (isset($_POST['submit_list'])) {
	if (isset($_POST['customer_id']) && isset($_POST['payment_id'])) {
		$payment_total = $payment_remaining;
		$num_processed = 0;
		$amount_applied = 0;
		$invoices = [];
		if (isset($_POST['invoices'])) $invoices = $_POST['invoices'];

		foreach ($invoices as $invoice_id) {
			$amount = $_POST['amount_'.$invoice_id];

			if ($amount < 0) continue;

			$invoice = new ck_invoice($invoice_id);

			$invoice_amount = $invoice->get_balance();

			if ($payment_total >= $amount) {
				ck_payment::legacy_apply_credit($payment->id(), $invoice_id, $amount);
				$num_processed++;
				$amount_applied += $amount;
				$invoice->figure_paid_status();

				$payment_total = $payment_total - $amount;
			}
			else die('Trying to apply a payment, but we ran out of money. We have already applied '.$amount_applied.' over '.$num_processed.' invoices this session.');
		}
		$messageStack->add_session('Payment '.$payment->id().' applied successfully.', 'success');
		CK\fn::redirect_and_exit('/admin/acc_dashboard.php');
	}
} ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script src="/includes/javascript/prototype.js" type="text/javascript"></script>
	<style>
		#apply_invoices, .amazon-payments-table { width:100%; text-align:center; font-size:12px; }
		.table-header { text-align:center; width:100%; font-size:20px; background-color:red; }
		#apply_invoices td { width:11%; }
		.amazon-payments-table td { width:11%; }
	</style>
	<script type="text/javascript">
		function stripCommas(numString) {
			var re = /,/g;
			return numString.replace(re,"");
		}

		function calc_payments(invoice_id) {
			//get invoice amount
			var invoice_amount = parseFloat($F('orig_amount_'+invoice_id).replace(',', ''));
			var applied_amount = parseFloat($F('amount_'+invoice_id).replace(',', ''));
			var remaining = parseFloat($F('amount_remaining').replace(',', ''));
			if ($('checkbox_'+invoice_id).checked) {
				var apply_amount = parseFloat(Math.min(invoice_amount, remaining));
				remaining = remaining - apply_amount;
				$('amount_remaining').value = remaining.toFixed(2);
				$('html_amount_remaining').update($F('amount_remaining'));
				$('amount_'+invoice_id).enable();
				$('amount_'+invoice_id).value = apply_amount.toFixed(2);
			}
			else {
				$('amount_'+invoice_id).disable();
				$('amount_'+invoice_id).value='';
				remaining = remaining + applied_amount;
				$('amount_remaining').value=remaining.toFixed(2);
			}
			$('html_amount_remaining').update($F('amount_remaining'));
		}

		function calc_remaining() {
			var total=0.0;
			$$('input.amount_applying').each(function(n) {
				if (!n.disabled) {
					total = total + parseFloat(n.value);
				}
			});
			remaining = parseFloat($F('original_payment_amount')) - total;
			$('amount_remaining').value=remaining.toFixed(2);
			$('html_amount_remaining').update($F('amount_remaining'));
			if (remaining < 0) {
				alert('You have allocated more money than is available from this payment.');
			}
		}
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<!-- body //-->
	<table border="0" width="1100px" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="1100px" valign="top">
				<div class="main_div" style="border:1px solid #333333;">
					<div class="acc_title">Apply To Invoices</div>
					<div class="acc_content_box">
						<?php // ********* if customer is amazon and file was submitted ************ //
						if ($_REQUEST['customer_id'] == 96285 && $_FILES['payment_upload']['error'] != UPLOAD_ERR_NO_FILE) {
							$customer = new ck_customer2($_REQUEST['customer_id']); ?>
						<form method="POST" action="acc_apply_credit2.php?customer_id=<?= $customer->id(); ?>payment_id=<?= $payment->id(); ?>">
							<input type="hidden" id="original_payment_amount" value="<?= $payment->get_header('amount'); ?>">
							<input type="hidden" name="customer_id" value="<?= $customer->id(); ?>">
							<input type="hidden" name="payment_id" value="<?= $payment->id(); ?>">
							<input type="hidden" name="amount_remaining" id="amount_remaining" value="<?= $payment_remaining; ?>">
							<div style="float:left; width:250px; height:30px;">
								<strong>Company: </strong><?= $customer->get_highest_name(); ?>
							</div>
							<div style="clear:left; width:400px; height:40px;">
								<p>Choose the invoices you would like to apply credit to and enter the amount of credit you would like to apply.</p>
								<p><span style="color:red; font-size:15px;">X</span> = uploaded payment amount does not equal invoice balance</p>
							</div>
							<div style="width:900px; height:30px;" align="right">
								<div style="float:right; width:760px; font-size:20px;"><strong>Credit amount: $</strong><?= $credit_amount; ?></div>
							</div>
							<table id="apply_invoices">
								<thead>
									<tr>
										<th style=""></th>
										<th>Customer</th>
										<th>Invoice #</th>
										<th>Order #</th>
										<th>PO #</th>
										<th>Age</th>
										<th>Total</th>
										<th>Balance</th>
										<th>Amount</th>
									</tr>
								</thead>
								<tbody>
									<?php $row_count = 0;
									$unmatched_invoices;
									foreach ($invoices as $invoice) {
										foreach ($consolidated as $amazon_order_id => $amount) {
											$order = new ck_sales_order($invoice['order_id']);
											if ($order->get_header('amazon_order_number') == $amazon_order_id && $invoice['balance'] > 0) {
												$matched_inputs[] = $amazon_order_id;
												$matched_invoices[] = $invoice['invoice_id'];
												$row_count ++; ?>
									<tr>
										<td style="margin:2.5px; padding:0; margin:0;">
											<?php if (number_format($invoice['balance'], 2) != number_format($amount['amount'], 2)) echo '<span style="color:red; font-size:15px;">X</span>'; ?>
											<?= $row_count; ?>
											<input class="invoice_ids" id="checkbox_<?= $invoice['invoice_id']; ?>" type="checkbox" name="invoices[]" value="<?= $invoice['invoice_id']; ?>" checked="checked">
										</td>
										<td><u><?= $invoice['customer_name']; ?></u></td>
										<td><?= $invoice['invoice_id']; ?></td>
										<td><u><a href="/admin/orders_new.php?oID=<?= $invoice['order_id']; ?>&action=edit"><u><?= $invoice['order_id']; ?></u></a></div>
										<td><?= $order->get_terms_po_number(); ?></td>
										<td><?= $invoice['days']; ?></td>
										<td><?= $invoice['total']; ?></td>
										<td><?= $invoice['balance']; ?></td>
										<td>
											<input class="amount_applying" type="text" size="8" name="amount_<?= $invoice['invoice_id']; ?>" id="amount_<?= $invoice['invoice_id']; ?>" value="<?= $amount['amount']; ?>">
											<input type="hidden" size="8" name="orig_amount_<?= $invoice['invoice_id']; ?>" id="orig_amount_<?= $invoice['invoice_id']; ?>" value="<?= $invoice['balance']; ?>">
										</td>
									</tr>
											<?php }
										}

										if (!empty($matched_invoices)) {
											if (!in_array($invoice['invoice_id'], $matched_invoices)) $unmatched_invoices[] = $invoice;
										}
									} ?>
								</tbody>
							</table>
							<div style="clear:both; float:left; height:30px; width:900px; padding-top:20px;" align="right">
								<div style="float:left; width:760px;"><strong>Remaining: $</strong></div>
								<div style="float:left; padding-left: 15px;" align="left"><span id="html_amount_remaining"><?= $payment_remaining; ?></span></div>
							</div>
							<div style="clear:both; padding-top:20px;">
								<input type="submit" name="submit_list" value="Apply Payment">
								<input type="button" value="Skip" onclick="javascript:document.location.href='/admin/acc_dashboard.php';">
							</div>
							<table class="amazon-payments-table" style="margin-top:10px;">
								<thead>
									<tr>
										<th class="table-header" colspan="2">Unmatched <u>Inputs</u></th>
									</tr>
									<tr>
										<th style="width:50%;">Amazon Order Id</th>
										<th style="width:50%;">Amount</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($consolidated as $amazon_order_id => $amount) {
										if (!in_array($amazon_order_id, $matched_inputs)) { ?>
									<tr>
										<td style="width:50%;"><?= $amazon_order_id ?></td>
										<td style="width:50%;"><?= $amount['amount']; ?></td>
									</tr>
										<?php }
									} ?>
								</tbody>
							</table>
							<table class="amazon-payments-table" style="margin-top:10px;">
								<thead>
									<tr>
										<th class="table-header" colspan="9">Unmatched <u>Invoices</u></th>
									</tr>
									<tr>
										<th></th>
										<th>Customer</th>
										<th>Invoice #</th>
										<th>Order #</th>
										<th>PO #</th>
										<th>Age</th>
										<th>Total</th>
										<th>Balance</th>
										<th>Amount</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($unmatched_invoices as $unmatched) {
										if (!in_array($unmatched['invoice_id'], $matched_invoices)) { ?>
									<tr>
										<td>
											<input class="invoice_ids" id="checkbox_<?= $unmatched['invoice_id']; ?>" type="checkbox" name="invoices[]" value="<?= $unmatched['invoice_id']; ?>" onClick="calc_payments(<?= $invoice['invoice_id']; ?>)">
										</td>
										<td><u><?= $unmatched['customer_name']; ?></u></td>
										<td><?= $unmatched['invoice_id']; ?></td>
										<td><a href="/admin/orders_new.php?oID=<?= $unmatched['order_id']; ?>&action=edit"><u><?= $unmatched['order_id']; ?></u></a></td>
										<td><?= $order->get_terms_po_number(); ?></td>
										<td><?= $unmatched['days']; ?></td>
										<td><?= $unmatched['total']; ?></td>
										<td><?= $unmatched['balance']; ?></td>
										<td>
											<input class="amount_applying" type="text" size="8" name="amount_<?= $unmatched['invoice_id']; ?>" id="amount_<?= $unmatched['invoice_id']; ?>" unmatched="<?= $amount['amount']; ?>" onkeyup="calc_remaining()">
											<input type="hidden" size="8" name="orig_amount_<?= $unmatched['invoice_id']; ?>" id="orig_amount_<?= $unmatched['invoice_id']; ?>" value="<?= $unmatched['balance']; ?>">
										</td>
									</tr>
										<?php }
									} ?>
								</tbody>
							</table>
						</form>
						<?php } // ********** end amazon *********** //
						else {
							$customer = new ck_customer2($_REQUEST['customer_id']); ?>
						<form method="POST" name="submit_list" action="acc_apply_credit2.php">
							<input type="hidden" id="original_payment_amount" value="<?= !empty($payment)?$payment->get_header('amount'):0; ?>">
							<input type="hidden" name="customer_id" value="<?= $customer->id(); ?>">
							<input type="hidden" name="payment_id" value="<?= !empty($payment)?$payment->id():NULL; ?>">
							<input type="hidden" name="amount_remaining" id="amount_remaining" value="<?= $payment_remaining; ?>">
							<div style="float:left; width:250px; height:30px;">
								<strong>Company: </strong><?= $customer->get_highest_name(); ?>
							</div>
							<div style="clear:left; width:400px; height:40px;">
								<p>Choose the invoices you would like to apply credit to and enter the amount of credit you would like to apply.</p>
							</div>
							<div style="width:900px; height:30px;" align="right">
								<div style="float:right; width:760px; font-size:20px;"><strong>Credit amount:</strong> <?= !empty($payment)?CK\text::monetize($payment->get_header('amount')):CK\text::monetize(0); ?></div>
							</div>
							<table class="amazon-payments-table">
								<thead>
									<tr>
										<th></th>
										<th>Customer</th>
										<th>Invoice #</th>
										<th>Order #</th>
										<th>PO #</th>
										<th>Age</th>
										<th>Total</th>
										<th>Balance</th>
										<th>Amount</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$invoices = $customer->get_outstanding_invoices_direct();
									foreach ($invoices as $invoice) {
										$order = $invoice->get_order();
										$balance = $invoice->get_balance(); ?>
									<tr>
										<td>
											<input class="invoice_ids" id="checkbox_<?= $invoice->id(); ?>" type="checkbox" name="invoices[]" value="<?= $invoice->id(); ?>" onClick="calc_payments(<?= $invoice->id(); ?>)">
										</td>
										<td><u><?= $invoice->get_name(); ?></u></td>
										<td><?= $invoice->id(); ?></td>
										<td><a href="/admin/orders_new.php?oID=<?= $invoice->get_header('orders_id'); ?>&action=edit"><u><?= $invoice->get_header('orders_id'); ?></u></a></td>
										<td><?= !empty($order)?$order->get_terms_po_number():''; ?></td>
										<td><?= $invoice->get_age(); ?></td>
										<td><?= $invoice->get_simple_totals('total'); ?></td>
										<td><?= $balance; ?></td>
										<td>
											<input class="amount_applying" disabled type="text" size="8" name="amount_<?= $invoice->id(); ?>" id="amount_<?= $invoice->id(); ?>" value="<?= $balance; ?>" onkeyup="calc_remaining()">
											<input type="hidden" size="8" name="orig_amount_<?= $invoice->id(); ?>" id="orig_amount_<?= $invoice->id(); ?>" value="<?= $balance; ?>">
										</td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
							<div style="clear:both; float:left; height:30px; width:900px; padding-top:20px;" align="right">
								<div style="float:left; width:760px;"><strong>Remaining: $</strong></div>
								<div style="float:left; padding-left: 15px;" align="left"><span id="html_amount_remaining"><?= $payment_remaining; ?></span></div>
							</div>
							<div style="clear:both; padding-top:20px;">
								<input type="submit" name="submit_list" value="Apply Payment">
								<input type="button" value="Skip" onclick="javascript:document.location.href='/admin/acc_dashboard.php';">
							</div>
						</form>
						<?php } ?>
					</div>
				</div>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<script>
		var $checked = jQuery(".invoice_ids:checkbox:checked");
		$checked.each(function() {
			calc_remaining();
		});
	</script>
	<!-- body_eof //-->
</body>
</html>
