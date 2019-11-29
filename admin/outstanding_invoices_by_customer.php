<?php
require('includes/application_top.php');

$customers_id = @$_REQUEST['customers_id'];
if (isset($customers_id)) {
	$customer = new ck_customer2($customers_id);
	$invoices = $customer->get_outstanding_invoices_direct();
}

$customers = ck_customer2::get_customers_with_outstanding_invoices();
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN"> <html> <head> <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>"> <title><?php echo TITLE; ?></title> <link rel="stylesheet" type="text/css" href="includes/stylesheet.css"> <script language="javascript" src="includes/general.js"></script>
<style>
		.dataTableContent { padding: 5px; max-width: 250px;}
</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php
 if (!$__FLAG['printable']) {
 require(DIR_WS_INCLUDES.'header.php');
 }; ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
 <tr>
<?php
	if (!$__FLAG['printable']) { ?>
	<td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php
 require(DIR_WS_INCLUDES.'column_left.php'); ?>
<!-- left_navigation_eof //-->
		</table>
<?php }; ?>
</td>
<!-- body_text //-->
	<td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td class="pageHeading">
			<?php if (!isset($invoices)) { ?>
				<td class="pageHeading">Outstanding Invoices Report&nbsp;&nbsp;&nbsp;&nbsp;&nbsp
					<?php if (count($customers)>0) { ?>
							<form method="get" action="outstanding_invoices_by_customer.php">
							<select name="customers_id">
							<?php foreach ( $customers as $customer) { ?>
								<option value="<?= $customer->id(); ?>"><?= $customer->get_highest_name(); ?></option>
							<?php } ?>
							</select> <input type="submit" value="View Invoices">
							</form>
					<?php }
					else { ?>
					There are no customers with outstanding invoices.
					<?php } ?>
				</td>
				<?php }
				else { ?>
			<td class="pageHeading">Outstanding Invoices Report&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<span style="font-size:8px">
				<input type="checkbox" onClick="window.location='/admin/outstanding_invoices_by_customer.php?customers_id=<?= $customers_id; ?>&printable=<?= $__FLAG['printable']?'off':'on'; ?>'" <?= $__FLAG['printable']?'checked':''; ?>>printable?</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<?php if (count($customers)>0) { ?>
							<form method="get" action="outstanding_invoices_by_customer.php">

							<select name="customers_id">
							<?php foreach ( $customers as $customer) { ?>
								<option value="<?= $customer->id(); ?>" <?php if ($customers_id==$customer->id()) echo 'selected';?>>
										<?= $customer->get_highest_name(); ?>
								</option>
							<?php } ?>
							</select> <input type="submit" value="View Invoices">
							</form>
					<?php } ?></td>
				<?php } ?>
			<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
		</tr>
		</table></td>
	</tr>

		<?php if (isset($invoices)):?>
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
						<tr class="dataTableHeadingRow">
								<?php //IPN name, description, manufacturer, quantity on hand, average cost, and $ total?>
								<td class="dataTableHeadingContent">Customers</td>
				<td class="dataTableHeadingContent">Order</td>
				<td class="dataTableHeadingContent">Age</td>
								<td class="dataTableHeadingContent">Amount</td>
								<td class="dataTableHeadingContent">Remaining</td>
				<td class="dataTableHeadingContent">Credits</td>
								<td class="dataTableHeadingContent" align="right">Terms</td>

						</tr>
						<?php foreach ($invoices as $idx => $invoice) {
							$customer = $invoice->get_customer(); ?>
						<tr bgcolor="<?= ((++$idx)%2==0) ? '#e0e0e0' : '#ffffff' ?>">
								<td class="dataTableContent"><?= $customer->get_display_label(); ?></td>
				<td class="dataTableContent"><?= $invoice->get_header('orders_id'); ?></td>
				<td class="dataTableContent"><?= $invoice->get_age(); ?></td>
								<td class="dataTableContent"><?= $invoice->get_simple_totals('total'); ?></td>
								<td class="dataTableContent"><?= $invoice->get_balance(); ?></td>
				<td class="dataTableContent"><?= CK\text::monetize($invoice->get_paid())?></td>
								<td class="dataTableContent" align="right"><?= $customer->get_terms('label'); ?></td>
						</tr>
						<?php } ?>
				</table>
			</td>
		</tr>
		<?php endif;?>
		<tr>
			<td colspan="3">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
				</table>
			</td>
		</tr>
		</table></td>
	</tr>
	</table></td>
<!-- body_text_eof //-->
 </tr>
</table>
<!-- body_eof //-->
</body>
</html>
