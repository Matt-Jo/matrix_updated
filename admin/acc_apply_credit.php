<?php
require('includes/application_top.php');

if (!isset($_REQUEST['customer_id'])) die('Customer id was not specified.');

if (!isset($_REQUEST['order_id'])) $invoice = new ck_invoice($_REQUEST['invoice_id']);
else $invoice = ck_invoice::get_latest_invoice_by_orders_id($_REQUEST['order_id']);

if (isset($_POST['customer_id']) && isset($_POST['invoice_id'])) {
	$payment = ck_payment::get_with_instance($_REQUEST['payment_id']);
	$max_payment = $payment->get_hard_unapplied_amount();

	$balance = $invoice->get_balance();

	if ($max_payment > $balance) $max_payment = $balance;

	if ($_REQUEST['apply_type'] == 'max') $amount_to_apply = $max_payment;
	else $amount_to_apply = $_REQUEST['amount']<=$max_payment?$_REQUEST['amount']:$max_payment;

	ck_payment::legacy_apply_credit($_REQUEST['payment_id'], $invoice->id(), $amount_to_apply);

	$invoice->figure_paid_status();

	// redirect back to dashboard if there are no outstanding invoices
	$customer = $invoice->get_customer();
	$invoices = $customer->get_outstanding_invoices_direct();
	if (empty($invoices) || $customer->get_unapplied_credit_total() <= 0) CK\fn::redirect_and_exit('/admin/acc_dashboard.php');
	else CK\fn::redirect_and_exit('/admin/acc_customer_invoices.php?content=history&customer_id='.$customer->id());
}

$customer = new ck_customer2($_REQUEST['customer_id']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script language="javascript">
		function MaxSelect() {
			document.getElementById("apply_type_max").checked=true;
		}
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<!------------------------------------------------------------------- -->
				<div style="width:800px; border:1px solid #333333; margin:20px;">
					<div class="acc_title">Apply Payment/Credit</div>
					<form method="POST" action="acc_apply_credit.php">
						<input type="hidden" name="customer_id" value="<?= $customer->id(); ?>">
						<input type="hidden" name="invoice_id" value="<?= $invoice->id(); ?>">
						<div class="acc_content_box">
							<div style="float:left; width:200px; height:30px;"><strong>Company:</strong> <?= $customer->get_highest_name(); ?></div>
							<div style="float:left; width:120px; height:30px;"><strong>Order:</strong> <?= @$_REQUEST['order_id']; ?></div>
							<div style="float:left; width:150px; height:30px;"><strong>Amount:</strong> <?= CK\text::monetize($invoice->get_balance()); ?></div>
							<div style="clear:left; height:20px;"><strong>Choose from existing:</strong></div>
							<div style="float:left; width:180px; padding-left:20px;"><strong>Type</strong></div>
							<div style="float:left; width:130px;"><strong>Date</strong></div>
							<div style="float:left; width:130px;"><strong>Payment Ref</strong></div>
							<div style="float:left; width:130px;"><strong>Amount</strong></div>
							<div style="float:left; width:130px;"><strong>Available</strong></div>

							<?php if ($customer->has_unapplied_payments()) {
								$credits = $customer->get_unapplied_payments();
								foreach ($credits as $credit) { ?>
							<div style="clear:left; float:left; width:30px; padding-left:10px;"><input type="radio" name="payment_id" onClick="MaxSelect();" value="<?= $credit['payment_id']; ?>"></div>
							<div style="float:left; width:150px; padding-top:5px; padding-left:10px; "><?= $credit['payment_method_label']; ?></div>
							<div style="float:left; width:130px; padding-top:5px;"><?= $credit['payment_date']->format('Y-m-d'); ?></div>
							<div style="float:left; width:130px; padding-top:5px;"><?= $credit['payment_ref']; ?></div>
							<div style="float:left; width:130px; padding-top:5px;"><?= $credit['payment_amount']; ?></div>
							<div style="float:left; width:130px; padding-top:5px;"><?= $credit['unapplied_amount']; ?></div>
								<?php }
							}
							else { ?>
							<div style="clear:left; float:left; width:200px; padding-left:30px;">No credits found.</div>
							<?php } ?>

							<div style="clear:left; height:20px; padding-top:10px;"><strong>Amount to apply:</strong></div>
							<div style="clear:left; float:left; width:40px; padding-left:10px;"><input type="radio" id="apply_type_max" name="apply_type" value="max"></div>
							<div style="float:left; padding-top:5px; width:300px;">Maximum amount possible</div><br>
							<div style="clear:both; float:left; width:40px; padding-left:10px; padding-top:5px;"><input type="radio" name="apply_type" value="other" checked="checked"></div>
							<div style="float:left; padding-top:5px; width:300px; ">Other Amount: <input type="text" size="10" name="amount" id="amount"></div>
							<div style="clear:both; padding-top:20px;">
								<input type="submit" value="Apply Payment/Credit"> <input type="button" value="Skip" name="Cancel" onclick="window.location.href = 'acc_dashboard.php';">
							</div>
						</div>
					</form>
				</div>
				<!------------------------------------------------------------------- -->
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
<html>
