<?php require('includes/application_top.php'); ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="includes/general.js"></script>
		<script language="javascript" src="../includes/javascript/prototype.js"></script>
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
		<?php if (empty($_GET['ipn_no']) || $_GET['ipn_no'] != 'yes') { ?>
		<?php
			require(DIR_WS_INCLUDES.'header.php');
		?>
		<?php } ?>
		<!-- header_eof //-->
		<!-- body //-->
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<?php if (empty($_GET['ipn_no']) || $_GET['ipn_no'] != 'yes') { ?>
				<td width="<?php echo BOX_WIDTH; ?>" valign="top">
					<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<?php } ?>
				<!-- body_text //-->
				<td width="100%" valign="top">
				<?php $ccs = prepared_query::fetch("SELECT o.orders_id, o.date_purchased, os.orders_status_name FROM orders o left join orders_status os on (o.orders_status = os.orders_status_id) WHERE payment_method_id = '1' AND orders_id NOT IN (SELECT order_id FROM credit_card_log WHERE action = 'C' AND result = 'A') AND orders_status IN('5', '11') ORDER BY o.orders_status asc, o.orders_id asc", cardinality::SET); ?>
				<div style="margin: 6px; border: 1px solid black; padding-left: 10px;">
					<h2>Orders With CCs Not Charged</h2>
				<?php
				if (empty($ccs)) echo "No results";
				else {?>
					<table cellspacing="4px">
						<tr>
							<th style="font-size: 11px; text-align: left;">Order ID</th>
							<th style="font-size: 11px; text-align: left;">Date Purchased</th>
							<th style="font-size: 11px; text-align: left;">Status</th>
						</tr>
						<?php foreach ($ccs as $row) { ?>
						<tr>
							<td style="font-size: 11px;"><a href="orders_new.php?oID=<?= $row['orders_id'];?>&action=edit"><?= $row['orders_id'];?></a></td>
							<td style="font-size: 11px;"><?= date('m/d/Y', strtotime($row['date_purchased']));?></td>
							<td style="font-size: 11px;"><?= $row['orders_status_name'];?></td>
						</tr>
						<?php } ?>
					</table>
				<?php } ?>
				</div>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
	<!-- body_eof //-->
	</body>
<html>
