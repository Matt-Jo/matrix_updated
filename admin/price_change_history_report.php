<?php
require('includes/application_top.php');

//filter values
$psc_cats = prepared_query::fetch("SELECT pscc.categories_id as id, CONCAT(pscv.name, ' - ', pscc.name) as name FROM products_stock_control_categories pscc LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id ORDER BY pscv.name ASC, pscc.name ASC", cardinality::SET);

//filter options
$filter = '';
if (!empty($_GET['pscc'])) $filter .= " and psc.products_stock_control_category_id in ('".implode("','", $_GET['pscc'])."') ";
if (isset($_GET['lxd']) && trim($_GET['lxd'] != '')) $filter .= " and datediff(now(), pscch.change_date) < ".$_GET['lxd']." ";
if (isset($_GET['ipn']) && trim($_GET['ipn'] != '')) $filter .= " and psc.stock_name like '%".$_GET['ipn']."%' ";

$ipn_query = "select psc.stock_id, psc.stock_name, pscc.name as category_name, pscv.name as vertical_name, pscch.old_value as old_price, pscch.new_value as new_price, pscch.type_id, psccht.name as change_type, a.admin_id, pscch.change_date from products_stock_control_change_history pscch left join products_stock_control psc on psc.stock_id = pscch.stock_id left join products_stock_control_categories pscc on (psc.products_stock_control_category_id = pscc.categories_id) left join products_stock_control_verticals pscv on (pscc.vertical_id = pscv.id) left join admin a on a.admin_email_address = pscch.change_user left join products_stock_control_change_history_types psccht on pscch.type_id = psccht.id where pscch.type_id in (2, 43, 44) $filter order by pscch.change_id desc";

if ($filter == "") $ipn_query .= " limit 0, 100";
$ipns = prepared_query::fetch($ipn_query, cardinality::SET); ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<style type="text/css">
		.dataTableContent { max-width: 250px; font-family: Arial, sans-serif; font-size: 10px; }
	</style>
	<script>
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
				<script>
					jQuery(document).ready(function($) {
						$("#ipn_table").tablesorter({});
						$('#ipn_search').autocomplete({
							minChars: 3,
							source: function(request, response) {
								$.ajax({
									minLength: 2,
									url: '/admin/serials_ajax.php?action=ipn_autocomplete',
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
				<h3>Price Change History Report</h3>
				<div style="font-size: 12px;">
					<form action="price_change_history_report.php" method="GET">
						Days Filter:
						<select name="lxd" class="jquery-chosen">
							<option value="">Last 100 price changes</option>
							<option value="7" <?php if (@$_GET['lxd'] == '7') { ?> selected <?php } ?>>7 Days</option>
							<option value="14" <?php if (@$_GET['lxd'] == '14') { ?> selected <?php } ?>>14 Days</option>
							<option value="30" <?php if (@$_GET['lxd'] == '30') { ?> selected <?php } ?>>30 Days</option>
							<option value="60" <?php if (@$_GET['lxd'] == '60') { ?> selected <?php } ?>>60 Days</option>
							<option value="90" <?php if (@$_GET['lxd'] == '90') { ?> selected <?php } ?>>90 Days</option>
							<option value="180" <?php if (@$_GET['lxd'] == '180') { ?> selected <?php } ?>>180 Days</option>
							<option value="365" <?php if (@$_GET['lxd'] == '365') { ?> selected <?php } ?>>365 Days</option>
						</select><br/>
						Vertical/Category:
						<select name="pscc[]" multiple data-placeholder="Choose one or more..." class="jquery-chosen">
							<option value="0">All</option><?php
							foreach ($psc_cats as $unused => $row) {
							?><option value="<?= $row['id']; ?>" <?php if ( !empty($_GET['pscc']) && in_array($row['id'], $_GET['pscc'])) { ?> selected <?php } ?>><?= $row['name']; ?></option><?php
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
							<th class="header">Products (special information)</th>
							<th class="header">In Stock</th>
							<th class="header">Available</th>
							<th class="header">Old Price</th>
							<th class="header">New Price</th>
							<th class="header">Change User</th>
							<th class="header">Change Date</th>
							<th class="header">Change Type</th>
							<th class="header">Vertical/Category</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($ipns as $unused => $row) {
							$ipn = new ck_ipn2($row['stock_id']);
							$products = $ipn->get_listings();
							$inv_data = $ipn->get_inventory();
							$user = null;

							if (!empty($row['admin_id'])) $user = new ck_admin($row['admin_id']); ?>
						<tr>
							<td><a href="ipn_editor.php?ipnId=<?= $ipn->get_header('ipn'); ?>"><?= $ipn->get_header('ipn'); ?></a></td>
							<td>
								<?php foreach ($products as $idx => $product) {
									if ($idx > 0) echo '<br/>'; ?>
								<a href="/product_info.php?products_id=<?= $product->id(); ?>" target="_blank"><?= $product->get_header('products_model'); ?></a>&nbsp;
									<?php if ($product->is('products_status')) echo tep_image(DIR_WS_IMAGES.'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10);
									else echo tep_image(DIR_WS_IMAGES.'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10);

									if ($product->has_special()) {
										$special = $product->get_special(); ?>
								<span style="color: #a80000;"> ( <?= CK\text::monetize($special['specials_new_products_price']); ?> / <?= $special['specials_qty']; ?> / <?= date('m/d/y', strtotime($special['expires_date'])); ?> )</span>
									<?php }
								} ?>
							</td>
							<td><?= $inv_data['on_hand']; ?></td>
							<td><?= $inv_data['available']; ?></td>
							<td>$<?= @number_format(str_replace(',', '', str_replace('$', '', $row['old_price'])), 2);?></td>
							<td>$<?= @number_format(str_replace(',', '', str_replace('$', '', $row['new_price'])), 2);?></td>
							<td><?php if ($user) { echo $user->get_name(); } ?></td>
							<td><?= date('m/d/y h:i A', strtotime($row['change_date']));?></td>
							<td><?= $row['change_type']; ?></td>
							<td><?= $row['vertical_name'].'/'.$row['category_name'];?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
<!-- body_eof //-->
</body>
</html>
