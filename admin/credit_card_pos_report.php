<?php
require('includes/application_top.php');

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
else $start_date = date('Y-m-01');

if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];
else $end_date = date('Y-m-d');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script language="javascript" src="/includes/javascript/prototype.js"></script>
	<script language="javascript" src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
	<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
	<link rel="stylesheet" type="text/css" href="serials/serials.css">
	<script language="javascript" src="serials/serials.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#start_date').datepicker({ dateFormat: 'yy-mm-dd' });
			$('#end_date').datepicker({ dateFormat: 'yy-mm-dd' });
		});
	</script>
	<!-- header_eof //-->
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
				<!------------------------------------------------------------------- -->
				<div style="font-family: arial;">
					<h3>Credit Card POs Report</h3>
					<form method="get" action="credit_card_pos_report.php">
						Start Date <?= tep_draw_input_field('start_date', $start_date, 'id="start_date"'); ?>
						End Date <?= tep_draw_input_field('end_date', $end_date, 'id="end_date"'); ?>
						<input type="submit" value="Submit">
					</form>
					<?php
					$pos = prepared_query::fetch("SELECT po.id, po.creation_date, v.vendors_company_name, (select sum(pop.quantity * pop.cost) from purchase_order_products pop where pop.purchase_order_id = po.id) as total FROM purchase_orders po left join vendors v on po.vendor = v.vendors_id where po.terms = '1' and po.creation_date BETWEEN :start_date AND :end_date", cardinality::SET, [':start_date' => $start_date, ':end_date' => $end_date.' 23:59:59']); ?>
					<table cellspacing="4px" cellpadding="4px">
						<tr>
							<th>Date</th>
							<th>Vendor</th>
							<th>PO Number</th>
							<th>Total</th>
						</tr>
						<?php foreach ($pos as $po) { ?>
						<tr>
							<td><?= date('m/d/Y', strtotime($po['creation_date']));?></td>
							<td><?= $po['vendors_company_name']; ?></td>
							<td><a href="po_viewer.php?poId=<?= $po['id']; ?>"><?= $po['id']; ?></a></td>
							<td>$<?= number_format($po['total'], 2);?></td>
						</tr>
						<?php } ?>
					</table>
				</div>
				<!------------------------------------------------------------------- -->
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
<html>
