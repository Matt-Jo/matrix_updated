<?php
require('includes/application_top.php');
/*
Product a inventory report. This report should exclude an items with 0 on hand. It should be separated by IPN category and for each product in the category it should show: IPN name, description, manufacturer, quantity on hand, average cost, and $ total (qty * avg_cost). Then, it should summarize the number of products and total $ value for each category. Then, at the end of the report it should show the total number of product and the total $ value for the entire store. Also, the URL should accept a flag that will generate the report without the header and the sidebar so that we can produce a printable version of the report.
*/

$result1 = prepared_query::fetch("select pscc.name as cat_name, psc.stock_name, psc.stock_description, m.manufacturers_name, if (psc.serialized = 1,(select count(1) from serials s1 where s1.ipn = psc.stock_id and s1.status in (2, 3, 6)) , psc.stock_quantity) as stock_quantity, if (psc.serialized = 1, (select AVG(sh.cost) as serial_cost from serials s, serials_history sh where psc.stock_id = s.ipn AND sh.serial_id = s.id AND sh.entered_date = (select max(entered_date) from serials_history where serial_id = s.id) AND s.status in (2, 3, 6)),round(psc.average_cost,2)) as avg_cost from products_stock_control psc left join products_stock_control_categories pscc on psc.products_stock_control_category_id=pscc.categories_id left join products p on p.stock_id=psc.stock_id left join manufacturers m on p.manufacturers_id = m.manufacturers_id where psc.stock_quantity > 0 group by psc.stock_id order by pscc.name", cardinality::SET);

$result2 = prepared_query::fetch("select sum(psc.stock_quantity) as total_products, round( sum( if (psc.serialized = 1, (select sum(sh.cost) as serial_cost from serials s, serials_history sh where psc.stock_id = s.ipn AND sh.serial_id = s.id and sh.entered_date = (select max(entered_date) from serials_history where serial_id = s.id) and s.status in (2, 3, 6)), (psc.average_cost * psc.stock_quantity) ) ), 2 ) as total_value from products_stock_control psc where psc.stock_quantity > -1", cardinality::ROW);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/general.js"></script>
	<style>
		.dataTableContent { padding: 5px; max-width: 250px;}
	</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php if (@$printable != 'on') {
		require(DIR_WS_INCLUDES.'header.php');
	} ?>
	<!-- header_eof //-->

	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<?php if (@$printable != 'on') { ?>
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
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading">
										Inventory Report&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size:8px"><input type="checkbox" onClick="window.location='/admin/inventory_report.php?printable=<?php echo (@$printable=='on')? 'off' : 'on';?>'" <?php if (@$printable == 'on') echo 'checked';?>>printable?</span>
									</td>
									<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top">
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<tr class="dataTableHeadingRow">
												<?php //IPN name, description, manufacturer, quantity on hand, average cost, and $ total?>
												<td class="dataTableHeadingContent">IPN Name</td>
												<td class="dataTableHeadingContent">Description</td>
												<td class="dataTableHeadingContent">Manufacturer</td>
												<td class="dataTableHeadingContent">Stock Qty</td>
												<td class="dataTableHeadingContent" align="right">Avg Cost</td>
												<td class="dataTableHeadingContent" align="right">Total Cost</td>
											</tr>
											<?php $current_cat='asdfas';
											$cnt = 0;
											foreach ($result1 as $row) {
												if ($current_cat != $row['cat_name']) {
													$current_cat = $row['cat_name']; ?>
											<tr bgcolor="#ffffff"><td class="dataTableContent"> </td></tr>
											<tr class="dataTableHeadingRow">
												<td class="dataTableHeadingContent" colspan="6"><?= $current_cat; ?></td>
											</tr>
												<?php } ?>
											<tr bgcolor="<?php echo ((++$cnt)%2==0) ? '#e0e0e0' : '#ffffff' ?>">
												<td class="dataTableContent"><a href="ipn_editor.php?ipnId=<?= $row['stock_name']; ?>"><?= $row['stock_name']; ?></a></td>
												<td class="dataTableContent"><?= $row['stock_description']; ?></td>
												<td class="dataTableContent"><?= $row['manufacturers_name']; ?></td>
												<td class="dataTableContent"><?= $row['stock_quantity']; ?></td>
												<td class="dataTableContent" align="right"><?php echo money_format('%n', $row['avg_cost'])?></td>
												<td class="dataTableContent" align="right"><?php echo money_format('%n', $row['avg_cost'] * $row['stock_quantity'])?></td>
											</tr>
											<?php } ?>
											<?php $row = $result2; ?>
											<tr class="dataTableHeadingRow">
												<td colspan="3" class="dataTableHeadingContent" align="right">Total Products: <?= $row['total_products']; ?></td>
												<td colspan="3" class="dataTableHeadingContent" align="right">Total Value: <?= $row['total_value']; ?></td>
											</tr>
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
