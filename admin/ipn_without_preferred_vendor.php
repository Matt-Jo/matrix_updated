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
				<div style="margin: 6px; border: 1px solid black; padding-left: 10px;">
					<h2>IPN's Without Preferred Vendors</h2>
					<?php if (!($ipns = prepared_query::fetch("SELECT psc.stock_name, vtsi.id, v.vendors_id from products_stock_control psc left join vendors_to_stock_item vtsi on psc.vendor_to_stock_item_id = vtsi.id left join vendors v on vtsi.vendors_id = v.vendors_id WHERE ((psc.vendor_to_stock_item_id IS NULL or psc.vendor_to_stock_item_id < 1) or vtsi.id is null or v.vendors_id is null) AND psc.discontinued = 0", cardinality::COLUMN))) {
						echo "No results";
					}
					else { ?>
					<table cellspacing="4px">
						<tr>
							<th style="font-size: 11px; text-align: left;">IPN</th>
						</tr>
						<?php foreach ($ipns as $ipn) { ?>
						<tr>
							<td style="font-size: 11px;"><a href="ipn_editor.php?ipnId=<?= $ipn; ?>"><?= $ipn; ?></a></td>
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
