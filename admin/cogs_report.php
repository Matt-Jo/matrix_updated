<?php
require_once('includes/application_top.php');

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
else $start_date = date('Y-m-01');

if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];
else $end_date = date('Y-m-d');

// handle SQL between for same date properly
if ($start_date == $end_date) {
	$start_date .= ' 00:00:00';
	$end_date .= ' 23:59:59';
}

$results = prepared_query::fetch("select ai.invoice_id, ai.inv_order_id, ai.inv_date, ab.entry_company, pm.code as payment_code, CONCAT_WS(' ', c.customers_firstname, c.customers_lastname) as name, MAX(p2i.credit_date) as credit_date, ait.invoice_total_price as total, (SELECT SUM(invi.orders_product_cost_total) from acc_invoice_items invi inner join products_stock_control psc on invi.ipn_id = psc.stock_id where invi.invoice_id = ai.invoice_id) as total_cost, DATEDIFF(MAX(p2i.credit_date), ai.inv_date) as days_to_pay from acc_invoices ai join customers c on ai.customer_id = c.customers_id left join address_book ab on c.customers_default_address_id = ab.address_book_id join orders o on ai.inv_order_id = o.orders_id join acc_invoice_totals ait on (ait.invoice_id=ai.invoice_id and ait.invoice_total_line_type='ot_total') join acc_payments_to_invoices p2i on p2i.invoice_id = ai.invoice_id left join payment_method pm on o.payment_method_id = pm.id where ai.paid_in_full = 1 group by ai.invoice_id having MAX(p2i.credit_date) between ? and ?", cardinality::SET, array($start_date, $end_date));

$all_cogs = array();
$terms_cogs = array();
$all_cost = 0;
$all_total = 0;
$all_days = 0;
$all_count = 0;
$terms_cost = 0;
$terms_total = 0;
$terms_days = 0;
$terms_count = 0;

foreach ($results as $cog) {
	$all_cogs[] = $cog;
	$all_cost += $cog['total_cost'];
	$all_total += $cog['total'];
	$all_days += $cog['days_to_pay'];
	$all_count++;
	if ($cog['payment_code'] == 'net10' || $cog['payment_code'] == 'net15' || $cog['payment_code'] == 'net30' || $cog['payment_code'] == 'net45' || $cog['payment_code'] == 'wire') {
		$terms_cogs[] = $cog;
		$terms_cost += $cog['total_cost'];
		$terms_total += $cog['total'];
		$terms_days += $cog['days_to_pay'];
		$terms_count++;
	}
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<style type="text/css">
		.dataTableHeadingContent {width: 100px;}
	</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#tabs").tabs();

			$('#start_date').datepicker({ dateFormat: 'yy-mm-dd' });
			$('#end_date').datepicker({ dateFormat: 'yy-mm-dd' });
			$('#all-cogs').tablesorter();
			$('#terms-cogs').tablesorter();

			$('a#print').click(function() {
				window.print();
			});
		});
	</script>
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
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading">COGS Report</td>
									<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table>
								<tr>
									<form method="get" action="<?= $_SERVER['PHP_SELF']; ?>">
										<td class="main">
											<?php
											echo 'Start Date '.tep_draw_input_field('start_date', $start_date, 'id="start_date"');
											echo 'End Date '.tep_draw_input_field('end_date', $end_date, 'id="end_date"'). '&nbsp;';
											echo '<input type="submit" value="Submit">';
											?>
										</td>
									</form>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top">
										<div style="font-size:8px; float: right; margin-right: 20px;">
											<a href="#" id="print">print</a>
										</div>
										<div id="tabs" style="clear: both;">
											<ul class="noPrint">
												<li><a href="#tabs-1">All COGS</a></li>
												<li><a href="#tabs-2">Terms COGS</a></li>
											</ul>
											<div id="tabs-1">
												<?php if ($all_count > 0) { ?>
												<table id="all-cogs" class="tablesorter">
													<thead>
														<tr>
															<th>Invoice #</th>
															<th>Order #</th>
															<th>Invoice Date</th>
															<th>Date of Payment</th>
															<th>Company</th>
															<th>Contact</th>
															<th>Amount</th>
															<th>COGS</th>
															<th>Days to Pay</th>
															<th>% Margin</th>
														</tr>
													</thead>
													<tbody>
														<?php foreach ($all_cogs as $row) { ?>
														<tr>
															<td class="dataTableContent"><?= $row['invoice_id']; ?></td>
															<td class="dataTableContent"><?= $row['inv_order_id']; ?></td>
															<td class="dataTableContent"><?php echo date('M-d-Y', strtotime($row['inv_date'])); ?></td>
															<td class="dataTableContent"><?php echo date('M-d-Y', strtotime($row['credit_date'])); ?></td>
															<td class="dataTableContent"><?= $row['entry_company']; ?></td>
															<td class="dataTableContent"><?= $row['name']; ?></td>
															<td class="dataTableContent" style="text-align: right;"><?php echo money_format('%n', $row['total'])?></td>
															<td class="dataTableContent" style="text-align: right;"><?php echo money_format('%n', $row['total_cost'])?></td>
															<td class="dataTableContent" style="text-align: right;"><?= $row['days_to_pay']; ?></td>
															<td class="dataTableContent">
																<?php if ($row['total'] == 0) echo '------';
																else echo @number_format((($row['total'] - $row['total_cost']) / $row['total']) * 100, 2); ?>%
															</td>
														</tr>
														<?php } ?>
													</tbody>
													<tbody>
														<tr style="font-weight: bold;">
															<td colspan="6"><?= $all_count; ?> Total Invoices</td>
															<td style="text-align: right"><?php echo money_format('%n', $all_total); ?></td>
															<td style="text-align: right"><?php echo money_format('%n', $all_cost); ?></td>
															<td style="text-align: right"><?php echo @number_format($all_days / $all_count, 0) ?></td>
															<td><?php echo @number_format((($all_total - $all_cost) / $all_total) * 100, 2); ?>%</td>
														</tr>
													</tbody>
												</table>
												<?php }
												else { ?>
												<p>No Results To Display</p>
												<?php } ?>
											</div>
											<div id="tabs-2">
												<?php if ($terms_count > 0) { ?>
												<table id="terms-cogs" class="tablesorter">
													<thead>
														<tr>
															<th>Invoice #</th>
															<th>Order #</th>
															<th>Invoice Date</th>
															<th>Date of Payment</th>
															<th>Company</th>
															<th>Contact</th>
															<th>Amount</th>
															<th>COGS</th>
															<th>Days to Pay</th>
															<th>% Margin</th>
														</tr>
													</thead>
													<tbody>
														<?php foreach ($terms_cogs as $row) { ?>
														<tr>
															<td class="dataTableContent"><?= $row['invoice_id']; ?></td>
															<td class="dataTableContent"><?= $row['inv_order_id']; ?></td>
															<td class="dataTableContent"><?php echo date('M-d-Y', strtotime($row['inv_date'])); ?></td>
															<td class="dataTableContent"><?php echo date('M-d-Y', strtotime($row['credit_date'])); ?></td>
															<td class="dataTableContent"><?= $row['entry_company']; ?></td>
															<td class="dataTableContent"><?= $row['name']; ?></td>
															<td class="dataTableContent" style="text-align: right;"><?php echo money_format('%n', $row['total'])?></td>
															<td class="dataTableContent" style="text-align: right;"><?php echo money_format('%n', $row['total_cost'])?></td>
															<td class="dataTableContent" style="text-align: right;"><?= $row['days_to_pay']; ?></td>
															<td class="dataTableContent">
																<?php if ($row['total'] == 0) echo '------';
																else echo @number_format((($row['total'] - $row['total_cost']) / $row['total']) * 100, 2); ?>%
															</td>
														</tr>
														<?php } ?>
													</tbody>
													<tbody>
														<tr style="font-weight: bold;">
															<td colspan="6"><?= $terms_count; ?> Total Invoices</td>
															<td style="text-align: right"><?php echo money_format('%n', $terms_total); ?></td>
															<td style="text-align: right"><?php echo money_format('%n', $terms_cost); ?></td>
															<td style="text-align: right"><?php echo @number_format($terms_days / $terms_count, 0) ?></td>
															<td><?php echo @number_format((($terms_total - $terms_cost) / $terms_total) * 100, 2); ?>%</td>
														</tr>
													</tbody>
												</table>
												<?php }
												else { ?>
												<p>No Results To Display</p>
												<?php } ?>
											</div>
										</div>
									</td>
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
