<?php
require('includes/application_top.php');

$pscc_id = !empty($_GET['pscc_id']) ? $_GET['pscc_id'] : false;
$pscv_id = !empty($_GET['pscv_id']) ? $_GET['pscv_id'] : false;
$view_id = !empty($_GET['view_id']) ? $_GET['view_id'] : 0;
$ipn_state = !empty($_GET['ipn_state']) ? $_GET['ipn_state'] : 'all';
$ipn_stock_status = !empty($_GET['ipn_stock_status']) ? $_GET['ipn_stock_status'] : 'all';
$where_clause = "";

$views = ['0' => 'Due Today', '1' => 'Due Next 7 Days', '2' => 'Due Next 30 Days', '3' => 'All'];
$ipn_count = 0;

if (!empty($_POST['action']) && $_POST['action'] == 'submit') {
	$pscData = [];

	if ($_POST['new_stock_price'] != $_POST['old_stock_price']) {
		$pscData['stock_price'] = $_POST['new_stock_price'];
		$pscData['last_stock_price_confirmation'] = date('Y-m-d');
		insert_psc_change_history($_POST['stock_id'], 'Stock Price Change', $_POST['old_stock_price'], $_POST['new_stock_price']);
	}

	if (!empty($_POST['confirm_stock_price'])) {
		$pscData['last_stock_price_confirmation'] = date('Y-m-d');
		insert_psc_change_history($_POST['stock_id'], 'Stock Price Confirmation', $_POST['new_stock_price'], $_POST['new_stock_price']);
	}

	if ($_POST['new_dealer_price'] != $_POST['old_dealer_price']) {
		$pscData['dealer_price'] = $_POST['new_dealer_price'];
		$pscData['last_dealer_price_confirmation'] = date('Y-m-d');
		insert_psc_change_history($_POST['stock_id'], 'Dealer Price Change', $_POST['old_dealer_price'], $_POST['new_dealer_price']);
	}

	if (!empty($_POST['confirm_dealer_price'])) {
		$pscData['last_dealer_price_confirmation'] = date('Y-m-d');
		insert_psc_change_history($_POST['stock_id'], 'Dealer Price Confirmation', $_POST['new_dealer_price'], $_POST['new_dealer_price']);
	}

	$update = new prepared_fields($pscData, prepared_fields::UPDATE_QUERY);
	$id = new prepared_fields(['stock_id' => $_POST['stock_id']]);
	prepared_query::execute('UPDATE products_stock_control SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));

	die();
}

switch ($ipn_state) {
	case 'all':
		$where_clause = "psc.discontinued IN (0, 1) ";
		break;
	case 'active':
		$where_clause = " psc.discontinued = 0";
		break;
	case 'discontinued':
		$where_clause = " psc.discontinued = 1";
		break;
	default:
		break;
}

switch ($view_id) {
	case '0':
		$where_clause .= " AND (datediff( date_add(psc.last_stock_price_confirmation, interval if (psc.pricing_review > 0, psc.pricing_review, pscc.pricing_review) day), now()) <= 0 || datediff( date_add(psc.last_dealer_price_confirmation, interval if (psc.pricing_review > 0, psc.pricing_review, pscc.pricing_review) day), now()) <= 0)";
		break;
	case '1':
		$where_clause .= " AND (datediff( date_add(psc.last_stock_price_confirmation, interval if (psc.pricing_review > 0, psc.pricing_review, pscc.pricing_review) day), now()) <= 7 || datediff( date_add(psc.last_dealer_price_confirmation, interval if (psc.pricing_review > 0, psc.pricing_review, pscc.pricing_review) day), now()) <= 7)";
		break;
	case '2':
		$where_clause .= " AND (datediff( date_add(psc.last_stock_price_confirmation, interval if (psc.pricing_review > 0, psc.pricing_review, pscc.pricing_review) day), now()) <= 30 || datediff( date_add(psc.last_dealer_price_confirmation, interval if (psc.pricing_review > 0, psc.pricing_review, pscc.pricing_review) day), now()) <= 30)";
		break;
	case '3':
		break;
}

if ($pscc_id > 0 || $pscv_id > 0 || $view_id > 0) {

	if ($pscc_id == 'all-categories') {
		$rows = prepared_query::fetch("SELECT psc.stock_id, psc.stock_name, psc.last_stock_price_confirmation, psc.stock_price, psc.dealer_price, CASE WHEN ls.date_purchased IS NULL THEN 'Never Sold' ELSE ls.date_purchased END AS date_purchased, lp.creation_date, lp.cost FROM products_stock_control psc LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN (SELECT date_format(o.date_purchased, '%m/%d/%Y') AS date_purchased, p.stock_id FROM orders o LEFT JOIN orders_products op ON o.orders_id = op.orders_id LEFT JOIN products p ON op.products_id = p.products_id GROUP BY p.stock_id ORDER BY o.date_purchased DESC) ls ON psc.stock_id = ls.stock_id LEFT JOIN (SELECT pop.cost, date_format(po.creation_date, '%m/%d/%Y') AS creation_date, pop.ipn_id FROM purchase_order_products pop LEFT JOIN purchase_orders po ON pop.purchase_order_id = po.id WHERE po.status IN (1, 2, 3) ORDER BY po.id DESC) lp ON psc.stock_id = lp.ipn_id WHERE $where_clause AND pscc.vertical_id = :vertical_id GROUP BY psc.stock_id ORDER BY psc.stock_name ASC", cardinality::SET, [':vertical_id' => $pscv_id]);
	}
	else {
		$rows = prepared_query::fetch("SELECT psc.stock_id, psc.stock_name, psc.last_stock_price_confirmation, psc.stock_price, psc.dealer_price, CASE WHEN ls.date_purchased IS NULL THEN 'Never Sold' ELSE ls.date_purchased END AS date_purchased, lp.creation_date, lp.cost FROM products_stock_control psc LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN (SELECT date_format(o.date_purchased, '%m/%d/%Y') AS date_purchased, p.stock_id FROM orders o LEFT JOIN orders_products op ON o.orders_id = op.orders_id LEFT JOIN products p ON op.products_id = p.products_id GROUP BY p.stock_id ORDER BY o.date_purchased DESC) ls ON psc.stock_id = ls.stock_id LEFT JOIN (SELECT pop.cost, date_format(po.creation_date, '%m/%d/%Y') AS creation_date, pop.ipn_id FROM purchase_order_products pop LEFT JOIN purchase_orders po ON pop.purchase_order_id = po.id WHERE po.status IN (1, 2, 3) ORDER BY po.id DESC) lp ON psc.stock_id = lp.ipn_id WHERE $where_clause AND psc.products_stock_control_category_id = :products_stock_control_category_id AND pscc.vertical_id = :vertical_id GROUP BY psc.stock_id ORDER BY psc.stock_name ASC", cardinality::SET, [':products_stock_control_category_id' => $pscc_id, ':vertical_id' => $pscv_id]);
	}

	$ipn_count = count($rows);
} ?>

<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
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
					<style>
						.category-selector option { display:none; }
					</style>
					<h3>Price Management Report</h3>
					<form method="GET" action="/admin/price_management_report.php">
						<label for="vertical-selection">Vertical:</label>
						<select name="pscv_id" id="vertical-selection" class="vertical-selector">
							<option value="0">Not Selected</option>
							<?php $verticals = prepared_query::fetch('SELECT id, name FROM products_stock_control_verticals ORDER BY name ASC', cardinality::SET);
							foreach ($verticals as $vertical) { ?>
							<option value="<?= $vertical['id']; ?>" <?= $pscv_id == $vertical['id'] ? 'selected' : ''; ?>><?= $vertical['name']; ?></option>
							<?php } ?>
						</select>

						<label for="category-selector">Category:</label>
						<select name="pscc_id" class="category-selector" id="category-selector">
							<option value="0">Not Selected</option>
							<option value="all-categories" id="all-categories" <?= $pscc_id == 'all-categories' ? 'selected' : ''; ?>>All</option>
							<?php $categories = prepared_query::fetch('SELECT categories_id, name, vertical_id FROM products_stock_control_categories ORDER BY name ASC', cardinality::SET);
							foreach ($categories as $category) { ?>
							<option value="<?= $category['categories_id']; ?>" <?= $pscc_id == $category['categories_id'] ? 'selected' : ''; ?> class="vert-<?= $category['vertical_id']; ?>"><?= $category['name']; ?></option>
							<?php } ?>
						</select>

						<label for="view-selector">View:</label>
						<select name="view_id" id="view-selector">
							<?php foreach ($views as $id => $name) { ?>
							<option value="<?= $id; ?>" <?= $view_id == $id ? 'selected' : ''; ?>><?= $name; ?></option>
							<?php } ?>
						</select>

						<label for="ipn-stock-status">In Stock Status:</label>
						<select id="ipn-stock-status" name="ipn_stock_status">
							<option value="all" <?= $ipn_stock_status == 'all' ? 'selected' : ''; ?>>All</option>
							<option value="in_stock" <?= $ipn_stock_status == 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
							<option value="out_of_stock" <?= $ipn_stock_status == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
						</select>

						<label for="ipn-state">IPN State:</label>
						<select id="ipn-state" name="ipn_state">
							<option value="all" <?= $ipn_state == 'all' ? 'selected' : ''; ?>>All</option>
							<option value="active" <?= $ipn_state == 'active' ? 'selected' : ''; ?>>Active</option>
							<option value="discontinued" <?= $ipn_state == 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
						</select>

						<input type="submit" value="Submit">
						<input type="button" value="Reset" onClick="window.location='price_management_report.php';">
					</form>
					<p>IPN Count: <?= $ipn_count; ?></p>
					<?php if ($pscc_id || $pscv_id || $view_id > 0) { ?>
					<table id="price-management-sortable" class="tablesorter">
						<thead>
							<tr>
								<th>IPN</th>
								<th>Category</th>
								<th>Available Qty</th>
								<th>Last Sale Date</th>
								<th>Last PO Price</th>
								<th>Last PO Date</th>
								<th>Days Since Price Confirmation</th>
								<th>Stock Price</th>
								<th>Confirm</th>
								<th>Dealer Price</th>
								<th>Confirm</th>
								<th>Submit</th>
							</tr>
						</thead>
						<tbody>
						<?php $todays_date = new DateTime('now');
						foreach($rows as $row) {
							$ipn = new ck_ipn2($row['stock_id']);
							$inventory_data = $ipn->get_inventory();
							if ($ipn_stock_status == 'in_stock' && $inventory_data['available'] > 0 || $ipn_stock_status == 'out_of_stock' && $inventory_data['available'] <= 0 || $ipn_stock_status == 'all') { ?>
								<tr>
									<td><a href="ipn_editor.php?ipnId=<?= $ipn->get_header('ipn'); ?>" target="_blank"><?= $ipn->get_header('ipn'); ?></a></td>
									<td><?= $ipn->get_header('ipn_category'); ?></td>
									<td align="center"><?= $inventory_data['available']; ?></td>
									<td align="center"><?= $row['date_purchased']; ?></td>
									<td align="center"><?= CK\text::monetize($row['cost']); ?></td>
									<td align="center"><?= $row['creation_date']; ?></td>
									<?php $last_price_review = new DateTime($row['last_stock_price_confirmation']); ?>
									<td align="center" title="<?= $row['last_stock_price_confirmation']; ?>">
										<?= empty($row['last_stock_price_confirmation'])?'Never Confirmed':$todays_date->diff($last_price_review)->format('%a'); ?>
									</td>
									<td align="center">
										<input type="text" id="new_stock_price_<?= $row['stock_id']; ?>" value="<?= $row['stock_price']; ?>">
										<input type="hidden" id="old_stock_price_<?= $row['stock_id']; ?>" value="<?= $row['stock_price']; ?>">
									</td>
									<td align="center"><input type="checkbox" id="confirm_stock_price_<?= $row['stock_id']; ?>"></td>
									<td align="center">
										<input type="text" id="new_dealer_price_<?= $row['stock_id']; ?>" value="<?= $row['dealer_price']; ?>">
										<input type="hidden" id="old_dealer_price_<?= $row['stock_id']; ?>" value="<?= $row['dealer_price']; ?>">
									</td>
									<td align="center"><input type="checkbox" id="confirm_dealer_price_<?= $row['stock_id']; ?>"></td>
									<td align="center"><input type="button" value="Submit" onclick="submit_price_review(<?= $row['stock_id']; ?>);"></td>
								</tr>
								<?php }
								} ?>
						</tbody>
					</table>
					<?php } ?>
					<script type="text/javascript">
						function submit_price_review(stock_id) {
							var old_stock_price = jQuery('#old_stock_price_' + stock_id).val();
							var new_stock_price = jQuery('#new_stock_price_' + stock_id).val();
							var old_dealer_price = jQuery('#old_dealer_price_' + stock_id).val();
							var new_dealer_price = jQuery('#new_dealer_price_' + stock_id).val();
							var confirm_stock_price = 0;
							if (jQuery('#confirm_stock_price_' + stock_id).is(':checked')) {
								confirm_stock_price = 1;
							}
							var confirm_dealer_price = 0;
							if (jQuery('#confirm_dealer_price_' + stock_id).is(':checked')) {
								confirm_dealer_price = 1;
							}

							new Ajax.Request('price_management_report.php', {
									method : 'post',
									parameters : {
										action: 'submit',
										stock_id: stock_id,
										old_stock_price: old_stock_price,
										new_stock_price: new_stock_price,
										old_dealer_price: old_dealer_price,
										new_dealer_price: new_dealer_price,
										confirm_stock_price: confirm_stock_price,
										confirm_dealer_price: confirm_dealer_price
									}
							});

							if (old_stock_price != new_stock_price || old_dealer_price != new_dealer_price || confirm_stock_price == 1 || dealer_stock_price == 1) {
								jQuery('#table_row_' + stock_id).hide();
							}
						}

						jQuery.tablesorter.addParser({
							id: 'last_confirmation',
							is: function(s) { return false; },
							format: function(s) {
								return s.replace(/Never Confirmed/, 99999999);
							},
							type: 'numeric'
						});

						jQuery('#price-management-sortable').tablesorter({
							widgets: ['zebra'],
							headers: {
								2: { sorter:'digit' },
								3: { sorter:'shortDate' },
								5: { sorter:'shortDate' },
								6: { sorter:'last_confirmation' },
								7: { sorter:false },
								8: { sorter:false },
								9: { sorter:false },
								10: { sorter:false },
								11: { sorter:false }
							}
						});

						jQuery('.vert-'+jQuery('.vertical-selector').val()).show();
                        jQuery('#all-categories').show();
						jQuery('.vertical-selector').change(function() {
							jQuery('.category-selector option').hide();
							jQuery('.vert-'+jQuery(this).val()).show();
							jQuery('.category-selector').val(0);
                            jQuery('#all-categories').show();
						});
					</script>
				</td>
			</tr>
		</table>
	</body>
</html>