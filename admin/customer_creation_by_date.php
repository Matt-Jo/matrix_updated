<?php
require('includes/application_top.php');

$customer_query = "SELECT count( DISTINCT c.customers_id ) AS count, date_format(date( ci.customers_info_date_account_created ),'%m-%d-%y') AS date, count( DISTINCT o.orders_id ) AS orders_count FROM customers c LEFT JOIN customers_info ci ON c.customers_id = ci.customers_info_id LEFT JOIN orders o ON (c.customers_id = o.customers_id AND datediff( ci.customers_info_date_account_created, o.date_purchased ) = 0) ";

if (@$_GET['action']=='search') {
	$customer_query.= " where datediff('".$_GET['syear']."-".$_GET['smonth']."-".$_GET['sday']."', date( ci.customers_info_date_account_created )) < 0 AND datediff('".$_GET['eyear']."-".$_GET['emonth']."-".$_GET['eday']."', date( ci.customers_info_date_account_created )) > 0 ";
}

$customer_query.="GROUP BY date( ci.customers_info_date_account_created ) ORDER BY date( ci.customers_info_date_account_created ) DESC";

$customers = prepared_query::fetch($customer_query, cardinality::SET); ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<style type="text/css">
		table#report_table {padding: 3px; border: 1px solid #000}
		table#report_table tr {padding: 3px}
		table#report_table tr td{background-color: #ccc; padding: 5px;}
	</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php
		require(DIR_WS_INCLUDES.'header.php');
	?>
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
				<td width="100%" valign="top" class="main">
			<form method="get" action="customer_creation_by_date.php">
				<input type="hidden" name="action" value="search">
				<table>
				<tr>
					<td>&nbsp;</td><td>MM DD YYYY</td>
				<tr>
					<td>Start Date:</td><td>
					<input type="text" name="smonth" size="2" value="<?= @$_GET['smonth']; ?>">
					<input size="2" type="text" name="sday" value="<?= @$_GET['sday']; ?>">
					<input size="4" type="text" name="syear" value="<?= @$_GET['syear']; ?>">
					</td>
				</tr>
				<tr>
					<td>End Date:</td><td>
					<input type="text" name="emonth" size="2" value="<?= @$_GET['emonth']; ?>">
					<input size="2" type="text" name="eday" value="<?= @$_GET['eday']; ?>">
					<input size="4" type="text" name="eyear" value="<?= @$_GET['eyear']; ?>">
					</td>
				</tr>
				<tr>
					<td colspan="3" align="right">
						<input type="submit" value="Search">
					</td>
				</table>
			</form>
			<hr/>
			<table id="report_table">
				<tr>
					<td>Date</td><td>New Customers</td><td align="center">New Customers with Orders</td>
			</tr>
			<?php foreach ($customers as $customer) { ?>
			<tr>
				<td><?= $customer['date']; ?></td><td align="center"><?= $customer['count']; ?></td><td><?= $customer['orders_count']; ?></td>
			</tr>
			<?php } ?>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
<html>
