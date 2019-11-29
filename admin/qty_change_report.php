<?php
require('includes/application_top.php');

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
else $start_date = date('Y-m-01');

if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];
else $end_date = date('Y-m-d', time() + (24*60*60));

function get_direction($column) {
	if (@$_GET['sort'] == $column) {
		if ($_GET['direction'] == 'ASC') return 'DESC';
		else return 'ASC';
	}
	else return 'ASC';
}

$sort = isset($_GET['sort'])?$_GET['sort']:'stock_id';
$direction = isset($_GET['direction'])?$_GET['direction']:'ASC';

// type_id 4 == 'Quantity Change'
$changes = prepared_query::fetch('SELECT pscch.*, psc.stock_name, pscc.name as category_name FROM products_stock_control_change_history pscch LEFT JOIN products_stock_control psc ON pscch.stock_id = psc.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id WHERE change_date < ? AND change_date > ? AND type_id = 4 ORDER BY '.$sort.' '.$direction, cardinality::SET, array($end_date, $start_date)); ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script language="javascript" src="/includes/javascript/prototype.js"></script>
	<script language="javascript" src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
	<script language="javascript"></script>
	<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
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
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="dataTableContent">Qty Changes</td>
									<td class="dataTableContent" align="right">
										<form method="get" action="/admin/qty_change_report.php" name="date_range">
											<input type='hidden' name='sort' value='<?= $sort; ?>'>
											<input type='hidden' name='direction' value='<?= $direction; ?>'>
											Start: <input type="text" value="<?= $start_date; ?>" name="start_date"/>
											End: <input type="text" value="<?= $end_date; ?>" name="end_date"/>
											<input type="submit" value="Filter"/>
										</form>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
								<tr>
									<td valign="top">
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<tr class="dataTableHeadingRow">
												<td class="dataTableHeadingContent">
													<a href="/admin/qty_change_report.php?start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>&sort=stock_id&direction=<?php echo get_direction('stock_id')?>">IPN</a>
												</td>
												<td class="dataTableHeadingContent">
													<a href="/admin/qty_change_report.php?start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>&sort=pscc.name&direction=<?php echo get_direction('pscc.name')?>">Category</a>
												</td>
												<td class="dataTableHeadingContent">
													<a href="/admin/qty_change_report.php?start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>&sort=change_date&direction=<?php echo get_direction('change_date')?>">Date</a>
												</td>
												<td class="dataTableHeadingContent">
													<a href="/admin/qty_change_report.php?start_date=<?= $start_date; ?>&end_date=<?= $end_date; ?>&sort=change_user&direction=<?php echo get_direction('change_user')?>">User</a>
												</td>
												<td class="dataTableHeadingContent">Previous Qty</td>
												<td class="dataTableHeadingContent">New Qty</td>
											</tr>
											<?php foreach ($changes as $row) { ?>
											<tr>
												<td class="dataTableContent"><?= $row['stock_name']; ?></td>
												<td class="dataTableContent"><?= $row['category_name']; ?></td>
												<td class="dataTableContent"><?php echo date("Y-m-d", strtotime($row['change_date'])); ?></td>
												<td class="dataTableContent"><?= $row['change_user']; ?></td>
												<td class="dataTableContent"><?= $row['old_value']; ?></td>
												<td class="dataTableContent"><?= $row['new_value']; ?></td>
											</tr>
											<?php } ?>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="3">
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
										</table>
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
