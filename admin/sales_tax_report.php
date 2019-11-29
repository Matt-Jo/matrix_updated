<?php
require_once('includes/application_top.php');

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
else $start_date = date('Y-m-01', strtotime("-1 month"));

if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];
else $end_date = date("Y-m-d", strtotime("-1 day", strtotime(date("Y-m-01")))); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/general.js"></script>
	<style>
		.dataTableHeadingContent {width: 100px;}
	</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#start_date').datepicker({ dateFormat: 'yy-mm-dd' });
			$('#end_date').datepicker({ dateFormat: 'yy-mm-dd' });
		});
	</script>
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?= BOX_WIDTH; ?>" valign="top">
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
									<td class="pageHeading">Invoice Report</td>
									<td class="pageHeading" align="right"><?= tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table>
								<tr>
									<td></td>
									<td class="main">
										<form method="get" action="sales_tax_report.php">
											Start Date <?= tep_draw_input_field('start_date', $start_date, 'id="start_date"'); ?>
											End Date <?= tep_draw_input_field('end_date', $end_date, 'id="end_date"'). '&nbsp;'; ?>
											<input type="submit" value="Submit">
										</form>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<style>
								.invoice-details td { padding:4px 6px; font-size:12px; }
								.invoice-details tr:nth-child(even) td { background-color:#e0e0e0; }
							</style>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top">
										<table border="0" cellspacing="0" cellpadding="2">
											<thead>
												<tr class="dataTableHeadingRow">
													<td class="dataTableHeadingContent">Date</td>
													<td class="dataTableHeadingContent">Invoice ID</td>
													<td class="dataTableHeadingContent">Order ID</td>
													<td class="dataTableHeadingContent">RMA ID</td>
													<td class="dataTableHeadingContent">Company</td>
													<td class="dataTableHeadingContent">City</td>
													<td class="dataTableHeadingContent">Zip</td>
													<td class="dataTableHeadingContent">State</td>
													<td class="dataTableHeadingContent">Product Total</td>
													<td class="dataTableHeadingContent">Shipping Total</td>
													<td class="dataTableHeadingContent">Tax</td>
													<td class="dataTableHeadingContent">Cost</td>
												</tr>
											</thead>
											<tbody class="invoice-details">
												<?php
												$invoice_ids = prepared_query::fetch('SELECT DISTINCT i.invoice_id FROM acc_invoices i JOIN acc_invoice_totals it ON i.invoice_id = it.invoice_id AND it.invoice_total_line_type = :tax WHERE DATE(i.inv_date) >= :start_date AND DATE(i.inv_date) <= :end_date AND it.invoice_total_price != 0', cardinality::COLUMN, [':start_date' => $start_date, ':end_date' => $end_date, ':tax' => 'ot_tax']);

												$totals = [
													'product' => 0,
													'shipping' => 0,
													'tax' => 0,
													'cost' => 0,
												];

												foreach ($invoice_ids as $idx => $invoice_id) {
													$invoice = new ck_invoice($invoice_id);
													$order = $invoice->get_original_order();
													
													if (!empty($order->get_header('ca_order_id'))) continue; ?>
												<tr>
													<td class="dataTableContent"><?= $invoice->get_header('invoice_date')->format('m/d/y'); ?></td>
													<td class="dataTableContent"><?= $invoice->id(); ?></td>
													<td class="dataTableContent"><?= $order->id(); ?></td>
													<td class="dataTableContent"><?= $invoice->has_rma()?$invoice->get_rma()->id():NULL; ?></td>
													<td class="dataTableContent"><?= !empty($order->get_header('delivery_company'))?$order->get_header('delivery_company'):$order->get_header('delivery_name'); ?></td>
													<td class="dataTableContent"><?= $order->get_header('delivery_city'); ?></td>
													<td class="dataTableContent"><?= $order->get_header('delivery_postcode'); ?></td>
													<td class="dataTableContent"><?= $order->get_header('delivery_state'); ?></td>
													<td class="dataTableContent"><?= CK\text::monetize($invoice->get_product_subtotal()); ?></td>
													<td class="dataTableContent"><?= CK\text::monetize($invoice->get_simple_totals('shipping')); ?></td>
													<td class="dataTableContent"><?= CK\text::monetize($invoice->get_simple_totals('tax')); ?></td>
													<td class="dataTableContent"><?= CK\text::monetize($invoice->get_product_cost()); ?></td>
												</tr>
												<?php
												$totals['product'] += $invoice->get_product_subtotal();
												$totals['shipping'] += $invoice->get_simple_totals('shipping');
												$totals['tax'] += $invoice->get_simple_totals('tax');
												$totals['cost'] += $invoice->get_product_cost();
											} ?>
											</tbody>
											<tbody>
												<tr>
													<td colspan="12"><hr></td>
												</tr>
												<tr>
													<td class="dataTableContent"><b>Totals:</b></td>
													<td class="dataTableContent"></td>
													<td class="dataTableContent"></td>
													<td class="dataTableContent"></td>
													<td class="dataTableContent"></td>
													<td class="dataTableContent"></td>
													<td class="dataTableContent"></td>
													<td class="dataTableContent"></td>
													<td class="dataTableContent"><?= CK\text::monetize($totals['product']); ?></td>
													<td class="dataTableContent"><?= CK\text::monetize($totals['shipping']); ?></td>
													<td class="dataTableContent"><?= CK\text::monetize($totals['tax']); ?></td>
													<td class="dataTableContent"><?= CK\text::monetize($totals['cost']); ?></td>
												</tr>
											</tbody>
										</table>
									</td>
								</tr>
								<tr>
									<td></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
