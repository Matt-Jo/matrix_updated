<?php
require('includes/application_top.php');

//filter values
$psc_cats = prepared_query::fetch("select pscc.categories_id as id, concat(pscv.name, ' - ', pscc.name) as name from products_stock_control_categories pscc left join products_stock_control_verticals pscv on (pscc.vertical_id = pscv.id) where 1 order by pscv.name asc, pscc.name asc", cardinality::SET);

$purchaser_results = prepared_query::fetch('SELECT DISTINCT po.administrator_admin_id FROM purchase_orders po WHERE DATEDIFF(NOW(), po.creation_date) < 90', cardinality::COLUMN);
$purchasers = array();
foreach ($purchaser_results as $purchaser) {
	$purchasers[] = $purchaser;
}
$purchaser_list = prepared_query::fetch("select a.admin_id, concat(a.admin_firstname, ' ', a.admin_lastname) as name from admin a where a.admin_id in (". implode(',', $purchasers) .")", cardinality::SET);

$price_changer_results = prepared_query::fetch('select distinct pscch.change_user from products_stock_control_change_history pscch where datediff(now(), pscch.change_date) < 90 and pscch.type_id in (2)', cardinality::SET);
$price_changers = array();
foreach ($price_changer_results as $unused => $price_changer) {
	$price_changers[] = $price_changer['change_user'];
}
$price_changer_list = prepared_query::fetch("select distinct a.admin_email_address, concat(a.admin_firstname, ' ', a.admin_lastname) as name from admin a where a.admin_email_address in ('". implode("','", $price_changers) ."')", cardinality::SET);


//filter options
$filter = "";
if (!empty($_GET['pscc'])) {
	$filter .= " and psc.products_stock_control_category_id in ('".implode("','", $_GET['pscc'])."') ";
}
if (!empty($_GET['purchasers'])) {
	$filter .= " and po.administrator_admin_id in (". implode(",", $_GET['purchasers']) .") ";
}
if (!empty($_GET['price_changers'])) {
	$filter .= " and pscch2.change_user in ('". implode("','", $_GET['price_changers']) ."') ";
}
if (isset($_GET['ipn']) && trim($_GET['ipn'] != '')) {
	$filter .= " and psc.stock_name like '%".$_GET['ipn']."%' ";
}

$ipn_query = "select psc.stock_id,
	psc.stock_name,
	c.conditions_name,
	pscc.name as category_name,
	pscv.name as vertical_name,
	datediff(now(), (select max(pscch.change_date) from products_stock_control_change_history pscch where pscch.type_id in (2, 3) and pscch.stock_id = psc.stock_id)) as last_price_updated,
	(select sum(aii.invoice_item_qty * aii.invoice_item_price) from acc_invoice_items aii left join acc_invoices ai on ai.invoice_id = aii.invoice_id where aii.ipn_id = psc.stock_id and datediff(now(), ai.inv_date) between 0 and 30) as 0_30_sales,
	(select sum(aii.orders_product_cost_total) from acc_invoice_items aii left join acc_invoices ai on ai.invoice_id = aii.invoice_id where aii.ipn_id = psc.stock_id and datediff(now(), ai.inv_date) between 0 and 30) as 0_30_cost,
	(select sum(aii.invoice_item_qty * aii.invoice_item_price) from acc_invoice_items aii left join acc_invoices ai on ai.invoice_id = aii.invoice_id where aii.ipn_id = psc.stock_id and datediff(now(), ai.inv_date) between 31 and 60) as 31_60_sales,
	(select sum(aii.orders_product_cost_total) from acc_invoice_items aii left join acc_invoices ai on ai.invoice_id = aii.invoice_id where aii.ipn_id = psc.stock_id and datediff(now(), ai.inv_date) between 31 and 60) as 31_60_cost,
	(select sum(aii.invoice_item_qty * aii.invoice_item_price) from acc_invoice_items aii left join acc_invoices ai on ai.invoice_id = aii.invoice_id where aii.ipn_id = psc.stock_id and datediff(now(), ai.inv_date) between 61 and 90) as 61_90_sales,
	(select sum(aii.orders_product_cost_total) from acc_invoice_items aii left join acc_invoices ai on ai.invoice_id = aii.invoice_id where aii.ipn_id = psc.stock_id and datediff(now(), ai.inv_date) between 61 and 90) as 61_90_cost
from products_stock_control psc
	left join conditions c on (psc.conditions = c.conditions_id)
	left join products_stock_control_categories pscc on (psc.products_stock_control_category_id = pscc.categories_id)
	left join products_stock_control_verticals pscv on (pscc.vertical_id = pscv.id)
	left join purchase_order_products pop on (pop.ipn_id = psc.stock_id and pop.id = (select max(pop2.id) from purchase_order_products pop2 where pop2.ipn_id = psc.stock_id))
	left join purchase_orders po on (po.id = pop.purchase_order_id)
	left join products_stock_control_change_history pscch2 on (psc.stock_id = pscch2.stock_id and pscch2.type_id = 2 and pscch2.change_id = (select max(pscch3.change_id) from products_stock_control_change_history pscch3 where pscch3.stock_id = psc.stock_id and pscch3.type_id = 2))

where 1 $filter ";

if ($filter == "") {
	$ipn_query .= " limit 0, 20";
}
$ipns = prepared_query::fetch($ipn_query, cardinality::SET);

?><!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<style type="text/css">
		.dataTableContent { max-width: 250px; font-family: Arial, sans-serif; font-size: 10px; }
	</style>
	<script type="text/javascript">
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" style="background-color:#FFFFFF;">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script language="javascript" src="includes/general.js"></script>
	<!-- header_eof //-->
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td class="noPrint" width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top" style="font-family: arial; padding: 15px;">

		<script type="text/javascript">
				jQuery(document).ready(function($) {
					$("#ipn_table").tablesorter({
							headers: {
								10: { sorter: 'percent' }
							},
							widgets: ['zebra']
					});
				$('#ipn_search').autocomplete({
					minChars: 3,
					source: function(request, response) {
						$.ajax({
							minLength: 2,
							url:	'/admin/serials_ajax.php?'	+
								'action=ipn_autocomplete',
							dataType: 'json',
							data: {
								term: request.term,
								search_type: 'ipn'
							},
							success: function(data) {
								response($.map(data, function(item) {
									if (item.value == null) {
										item.value = item.label;
									}
													if (item.data_display == null) {
														item.data_display = item.label;
													}
									return {
										misc: item.value,
										label: item.data_display,
										value: item.label
									}
								}))
							}
						});
					}
				});
			});
		</script>
		<h3>Recent Product Sales</h3>
		<div style="font-size: 12px;">
		<form action="recent_product_sales.php" method="GET">
			Vertical/Category:
			<select name="pscc[]" multiple data-placeholder="Choose one or more..." class="jquery-chosen">
				<option value="0">All</option><?php
				foreach ($psc_cats as $unused => $row) {
				?><option value="<?= $row['id']; ?>" <?php if ( !empty($_GET['pscc']) && in_array($row['id'], $_GET['pscc'])) { ?> selected <?php } ?>><?= $row['name']; ?></option><?php
				} ?>
			</select><br/>
			Last Price Change By:
			<select name="price_changers[]" multiple data-placeholder="Choose one or more..." class="jquery-chosen">
				<?php foreach ($price_changer_list as $unused => $row) {
				?><option value="<?= $row['admin_email_address']; ?>" <?php if (!empty($_GET['price_changers']) && in_array($row['admin_email_address'], $_GET['price_changers'])) { ?> selected <?php } ?>><?= $row['name']; ?></option><?php
				} ?>
			</select><br/>
			Last Purchased By:
			<select name="purchasers[]" multiple data-placeholder="Choose one or more..." class="jquery-chosen">
				<?php foreach ($purchaser_list as $unused => $row) {
				?><option value="<?= $row['admin_id']; ?>" <?php if (!empty($_GET['purchasers']) && in_array($row['admin_id'], $_GET['purchasers'])) { ?> selected <?php } ?>><?= $row['name']; ?></option><?php
				} ?>
			</select><br/>
			IPN Filter:
					<input type="text" name="ipn" id="ipn_search" value="<?php if (!empty($_GET['ipn'])) { echo $_GET['ipn'];} ?>"><br/>
			<input type="submit" value="Update"/>
		</form>
		</div>
		<table id="ipn_table" class="tablesorter" border="0" cellpadding="5px" cellspacing="0" style="font-size: 12px;">
			<thead>
			<tr>
				<th class="header">IPN</th>
				<th class="header">Condition</th>
				<th class="header">Products</th>
				<th class="header">Discontinued</th>
				<th class="header">Dropship</th>
				<th class="header">Non-Stock</th>
				<th class="header">In Stock</th>
				<th class="header">Available</th>
				<th class="header">Stock Price</th>
				<th class="header">Dealer Price</th>
				<th class="header">Days Since Last Price Change</th>
				<th class="header">Month / Month Growth</th>
				<th class="header">0-30 Sales</th>
				<th class="header">0-30 Margin</th>
				<th class="header">31-60 Sales</th>
				<th class="header">31-60 Margin</th>
				<th class="header">61-90 Sales</th>
				<th class="header">61-90 Margin</th>
			</tr>
			</thead>
			<tbody>
			<?php
			$count = 0;
			foreach ($ipns as $unused => $row) {
				$ipn = new ck_ipn2($row['stock_id']);
				$products = $ipn->get_listings();
				$inv_data = $ipn->get_inventory();
			?><tr>
				<td><a href="ipn_editor.php?ipnId=<?= $ipn->get_header('ipn'); ?>"><?= $ipn->get_header('ipn'); ?></a></td>
				<td><?= $row['conditions_name']; ?></td>
				<td><?php
				$count = 0;
				foreach ($products as $product) {
					if ($count > 0) echo '<br/>';
					echo $product->get_header('products_model').'&nbsp;';
					if ($product->is('products_status')) {
						echo tep_image(DIR_WS_IMAGES.'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10);
					}
					else {
						echo tep_image(DIR_WS_IMAGES.'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);
					}
					$count++;

					$margin_0_30 = $row['0_30_sales'] - $row['0_30_cost'];
					$margin_31_60 = $row['31_60_sales'] - $row['31_60_cost'];
					$month_month_growth = 0;
					if ($margin_31_60 != 0) {
						$month_month_growth = 100 * ($margin_0_30 - $margin_31_60) / $margin_31_60;
					}
					$mmg_color = '#00A800';
					if ($month_month_growth < 0) {
						$mmg_color = '#a80000';
					}
				} ?></td>
				<td><?= $ipn->get_header('discontinued'); ?></td>
				<td><?= $ipn->get_header('drop_ship'); ?></td>
				<td><?= $ipn->get_header('non_stock'); ?></td>
				<td><?= $inv_data['on_hand']; ?></td>
				<td><?= $inv_data['available']; ?></td>
				<td>$<?= number_format($ipn->get_header('stock_price'), 2);?></td>
				<td>$<?= number_format($ipn->get_header('dealer_price'), 2);?></td>
				<td><?= $row['last_price_updated']; ?></td>
				<td style='color: <?= $mmg_color; ?>'>%<?= number_format($month_month_growth, 2);?></td>
				<td>$<?= number_format($row['0_30_sales'], 2);?></td>
				<td>$<?= number_format($row['0_30_sales'] - $row['0_30_cost'], 2);?></td>
				<td>$<?= number_format($row['31_60_sales'], 2);?></td>
				<td>$<?= number_format($row['31_60_sales'] - $row['31_60_cost'], 2);?></td>
				<td>$<?= number_format($row['61_90_sales'], 2);?></td>
				<td>$<?= number_format($row['61_90_sales'] - $row['61_90_cost'], 2);?></td>
			</tr><?php } ?>
			</tbody>
		</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
<!-- body_eof //-->
</body>
</html>
