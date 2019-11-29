<?php
require('includes/application_top.php');

if (isset($_POST['pID'])) {
	prepared_query::execute('UPDATE acc_payments SET payment_amount = :payment_amount WHERE payment_id = :payment_id', [':payment_amount' => $_POST['payment_amount'], ':payment_id' => $_POST['pID']]);
	CK\fn::redirect_and_exit('customer_account_history.php?customer_id='.$_POST['customer_id']);
}
elseif (isset ($_POST['p2iID'])) {
}

if (!isset($_GET['pID']) && !isset($_GET['p2iID'])) die('Id was not specified.');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
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
				<td width="100%" valign="top">
				<!-- body_text //-->
				<?php if (!empty($_GET['pID'])) {
					$payment = prepared_query::fetch('SELECT * FROM acc_payments WHERE payment_id = :payment_id', cardinality::ROW, [':payment_id' => $_GET['pID']]); ?>
					<form method="POST" action="acc_record_editor.php">
						<input type="hidden" name="pID" value="<?= $payment['payment_id']; ?>">
						<input type="hidden" name="customer_id" value="<?= $payment['customer_id']; ?>">
						Payment ID: <?= $payment['payment_id']; ?><br>
						Payment Amount: <input type="text" name="payment_amount" value="<?= $payment['payment_amount']; ?>"><br>
						<input type="submit" value="Save">
						<input type="button" value="Cancel" onClick="window.location='customer_account_history.php?customer_id=<?= $payment['customer_id']; ?>';">
					</form>
				<?php }
				elseif (!empty($_GET['p2iID'])) {
					$invoice = ck_invoice::get_invoice_by_payment_allocation_id($_GET['p2iID']);
					foreach ($invoice->get_payments() as $payment) {
						if ($payment['payment_to_invoice_id'] != $_GET['p2iID']) continue; ?>
					<form method="POST" action="acc_record_editor.php">
						<input type="hidden" name="p2iID" value="<?= $payment['credit_amount']; ?>">
						<input type="hidden" name="customer_id" value="<?= $invoice->get_header('customers_id'); ?>">
						Payment To Invoice ID: <?= $payment['payment_to_invoice_id']; ?><br>
						Credit Amount: <input type="text" name="credit_amount" value="<?= $payment['credit_amount']; ?>"><br>
						<input type="submit" value="Save">
						<input type="button" value="Cancel" onClick="window.location='customer_account_history.php?customer_id=<?= $invoice->get_header('customers_id'); ?>';">
					</form>
					<?php }
				} ?>
				<!-- body_text_eof //-->
				</td>
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
<html>
