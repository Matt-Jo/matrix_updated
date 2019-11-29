<?php
require('includes/application_top.php');

if (isset($_POST['action'])) $action = strtolower(trim($_POST['action']));
else $action = NULL;

if ($action == 'publish') {
	foreach ($_POST['liquidate'] as $stock_id => $status) {
		$offset = $_POST['liquidate_qty'][$stock_id];
		$admin_note = @$_POST['liquidate_admin_note'][$stock_id];
		$user_note = @$_POST['liquidate_user_note'][$stock_id];

		prepared_query::execute('UPDATE products_stock_control SET liquidate = ?, liquidate_qty = ?, liquidate_admin_note = ?, liquidate_user_note = ? WHERE stock_id = ?', array($status, $offset, $admin_note, $user_note, $stock_id));
	}
}

$ipns = array();
$total_overstock_value = 0;
$total_overstock_qty = 0;
$total_value = 0;
$total_qty = 0;
if (TRUE) { //!empty($perms['admin_sll'])) {
	// type_id 15 == 'Marked As Reviewed'
	$ipn_details = prepared_query::fetch("SELECT psc.stock_id, psc.serialized, psc.stock_quantity, psc.average_cost, pscc.name as ipn_category, psc.stock_name as ipn, c.conditions_name as cond, psc.liquidate_qty, psc.liquidate, psc.liquidate_admin_note, psc.liquidate_user_note, psc.max_inventory_level, CASE WHEN psc.drop_ship = 1 THEN 'DS' ELSE '' END as dsns, CASE WHEN psc.discontinued = 1 THEN 'DISC' ELSE '' END as disc, hist.last_review, po.last_receipt, psc.date_added as ipn_age FROM products_stock_control psc JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN conditions c ON psc.conditions = c.conditions_id LEFT JOIN (SELECT stock_id, MAX(change_date) as last_review FROM products_stock_control_change_history WHERE type_id = 15 GROUP BY stock_id) hist ON psc.stock_id = hist.stock_id LEFT JOIN (SELECT popy.ipn_id as stock_id, MAX(porsy.date) as last_receipt FROM purchase_order_products popy JOIN purchase_order_received_products porpy ON popy.id = porpy.purchase_order_product_id JOIN purchase_order_receiving_sessions porsy ON porsy.id = porpy.receiving_session_id GROUP BY popy.ipn_id) po ON psc.stock_id = po.stock_id LEFT JOIN (SELECT DISTINCT s.ipn FROM serials s JOIN (SELECT sh.serial_id, MAX(sh.entered_date) as entered_date FROM serials_history sh GROUP BY sh.serial_id) sh ON s.id = sh.serial_id /*AND TO_DAYS(sh.entered_date) < TO_DAYS(NOW()) - 30*/ WHERE s.status IN (2)) s ON psc.stock_id = s.ipn ORDER BY ipn", cardinality::SET);

	$max_age_history = prepared_query::fetch('SELECT p.stock_id, SUM(op.products_quantity) as qty FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - COALESCE(NULLIF(psc.max_inventory_level, 0), 30) GROUP BY p.stock_id', cardinality::SET);

	$forecast = new forecast;
	$ipn_history = $forecast->build_history(); //NULL, ' AND p.stock_id IN (SELECT DISTINCT s.ipn FROM serials s JOIN (SELECT sh.serial_id, MAX(sh.entered_date) as entered_date FROM serials_history sh GROUP BY sh.serial_id) sh ON s.id = sh.serial_id WHERE s.status IN (2) AND TO_DAYS(sh.entered_date) < TO_DAYS(NOW()) - 30) ');

	$qtys = prepared_query::fetch("SELECT s.ipn as stock_id, COUNT(s.id) as serial_qty, SUM(sh.cost) as value, CASE WHEN TO_DAYS(sh.entered_date) >= TO_DAYS(NOW()) - 30 THEN 'current' WHEN TO_DAYS(sh.entered_date) >= TO_DAYS(NOW()) - 60 THEN 'aged30-60' ELSE 'aged60-plus' END as serial_age FROM serials s JOIN serials_history sh ON s.id = sh.serial_id AND sh.entered_date = (SELECT MAX(entered_date) FROM serials_history WHERE s.id = serial_id) WHERE s.status IN (2) GROUP BY stock_id, CASE WHEN TO_DAYS(sh.entered_date) >= TO_DAYS(NOW()) - 30 THEN 'current' WHEN TO_DAYS(sh.entered_date) >= TO_DAYS(NOW()) - 60 THEN 'aged30-60' ELSE 'aged60-plus' END", cardinality::SET);

	foreach ($ipn_details as $detail) {
		$ipn = $detail;
		$daily = $forecast->daily_qty(@$ipn_history[$ipn['stock_id']]);
		$period = $ipn['max_inventory_level']?$ipn['max_inventory_level']:30;
		$ipn['period_sales'] = $daily * $period;
		$ipn['period'] = $period;

		$ipn['total_value'] = 0;
		$ipn['current_value'] = 0;
		$ipn['aged30-60_value'] = 0;
		$ipn['aged60-plus_value'] = 0;
		$ipn['aged_value'] = 0;

		$ipn['total_qty'] = 0;
		$ipn['current_qty'] = 0;
		$ipn['aged30-60_qty'] = 0;
		$ipn['aged60-plus_qty'] = 0;
		$ipn['aged_qty'] = 0;

		if (!empty($ipn['serialized'])) {
			foreach ($qtys as $qty) {
				if ($qty['stock_id'] != $ipn['stock_id']) continue;
				$ipn['total_value'] += $qty['value'];
				$ipn['total_qty'] += $qty['serial_qty'];

				$ipn[$qty['serial_age'].'_value'] += $qty['value'];
				$ipn[$qty['serial_age'].'_qty'] += $qty['serial_qty'];

				if ($qty['serial_age'] != 'current') {
					$ipn['aged_value'] += $qty['value'];
					$ipn['aged_qty'] += $qty['serial_qty'];
				}

				$ipn['average_cost'] = $ipn['total_value'] / $ipn['total_qty'];
			}
		}
		else {
			$ipn['total_value'] = $ipn['stock_quantity'] * $ipn['average_cost'];
			$ipn['total_qty'] = $ipn['stock_quantity'];
		}

		$ipn['days_supply'] = !$ipn['total_qty']?0:(!$daily?999999:($ipn['total_qty']/($daily)));

		$ipn['inv_value'] = $ipn['total_value'];
		if ($ipn['liquidate'] == 1) {
			$total_value += $ipn['total_value'];
			$total_qty += $ipn['total_qty'];
		}
		$ipn['overstock_qty'] = $ipn['total_qty'] - $ipn['period_sales'] - $ipn['liquidate_qty'];

		if ($ipn['overstock_qty'] <= 0) continue;
		$total_overstock_value += $ipn['overstock_qty'] * $ipn['average_cost'];
		$total_overstock_qty += $ipn['overstock_qty'];

		$ipns[] = $ipn;
	}
}
else {
	$ipn_details = prepared_query::fetch('SELECT psc.stock_id, pscc.name as ipn_category, psc.stock_name as ipn, c.conditions_name as cond, psc.liquidate_qty, psc.liquidate, psc.liquidate_user_note, psc.max_inventory_level FROM products_stock_control psc JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN conditions c ON psc.conditions = c.conditions_id WHERE psc.liquidate = 1 ORDER BY ipn', cardinality::SET);

	$max_age_history = prepared_query::fetch('SELECT p.stock_id, SUM(op.products_quantity) as qty FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - COALESCE(NULLIF(psc.max_inventory_level, 0), 30) GROUP BY p.stock_id', cardinality::SET);

	$forecast = new forecast;
	$ipn_history = $forecast->build_history(); //NULL, ' AND p.stock_id IN (SELECT DISTINCT stock_id FROM products_stock_control WHERE liquidate = 1) ');

	$qtys = prepared_query::fetch("SELECT s.ipn as stock_id, COUNT(s.id) as serial_qty, SUM(sh.cost) as value, CASE WHEN TO_DAYS(sh.entered_date) >= TO_DAYS(NOW()) - 30 THEN 'current' WHEN TO_DAYS(sh.entered_date) >= TO_DAYS(NOW()) - 60 THEN 'aged30-60' ELSE 'aged60-plus' END as serial_age FROM serials s JOIN serials_history sh ON s.id = sh.serial_id AND sh.entered_date = (SELECT MAX(entered_date) FROM serials_history WHERE s.id = serial_id) JOIN products_stock_control psc ON s.ipn = psc.stock_id AND psc.liquidate = 1 WHERE s.status IN (2) GROUP BY stock_id, CASE WHEN TO_DAYS(sh.entered_date) >= TO_DAYS(NOW()) - 30 THEN 'current' WHEN TO_DAYS(sh.entered_date) >= TO_DAYS(NOW()) - 60 THEN 'aged30-60' ELSE 'aged60-plus' END", cardinality::SET);

	foreach ($ipn_details as $detail) {
		$ipn = $detail;
		$daily = $forecast->daily_qty(@$ipn_history[$ipn['stock_id']]);
		$period = $ipn['max_inventory_level']?$ipn['max_inventory_level']:30;
		$ipn['period_sales'] = $daily * $period;

		$ipn['total_value'] = 0;
		$ipn['current_value'] = 0;
		$ipn['aged30-60_value'] = 0;
		$ipn['aged60-plus_value'] = 0;
		$ipn['aged_value'] = 0;

		$ipn['total_qty'] = 0;
		$ipn['current_qty'] = 0;
		$ipn['aged30-60_qty'] = 0;
		$ipn['aged60-plus_qty'] = 0;
		$ipn['aged_qty'] = 0;

		foreach ($qtys as $qty) {
			if ($qty['stock_id'] != $ipn['stock_id']) continue;
			$ipn['total_value'] += $qty['value'];
			$ipn['total_qty'] += $qty['serial_qty'];

			$ipn[$qty['serial_age'].'_value'] += $qty['value'];
			$ipn[$qty['serial_age'].'_qty'] += $qty['serial_qty'];

			if ($qty['serial_age'] != 'current') {
				$ipn['aged_value'] += $qty['value'];
				$ipn['aged_qty'] += $qty['serial_qty'];
			}
		}

		$ipn['days_supply'] = !$ipn['total_qty']?0:(!$daily?999999:($ipn['total_qty']/($daily)));

		$ipn['inv_value'] = $ipn['total_value'];
		$total_value += $ipn['total_value'];
		$total_qty += $ipn['total_qty'];
		$ipn['overstock_qty'] = $ipn['total_qty'] - $ipn['period_sales'] - $ipn['liquidate_qty'];

		if ($ipn['overstock_qty'] <= 0) continue;

		$ipns['ipn_'.str_pad(ceil($ipn['overstock_qty']), 20, '0', STR_PAD_LEFT).'_'.$ipn['stock_id']] = $ipn;
	}

	//krsort($ipns);
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="includes/general.js"></script>
		<script language="javascript" src="../includes/javascript/prototype.js"></script>
		<?php $font = 'frijole'; // bangers sonsie-one ?>
		<script src="https://use.edgefonts.net/<?= $font; ?>:n4:all.js"></script>
		<style>
			.ex-lax { font-family:<?= $font; ?>; font-size:3em; color:#26f; background: -webkit-linear-gradient(#4af, #4af, #4af, #8b4513, #8b4513); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
			.report_options { text-align:right; }
			.report_submit { text-align:left; }
			.sll_tbl .dataTableHeadingContent input:not([type=submit]) { width:20px; }
			.sll_tbl th, .sll_tbl td { width:75px; }
			.sll_tbl th.headerHead { width:auto; }
			.sll_tbl th.wide, .sll_tbl td.wide { width:130px; }
			.sll_tbl th.related, .sll_tbl td.related { width:40px; text-align:center; }
			.sll_tbl .hideRow { display:none; }
			.sll_tbl .empty { color:#f00; }
			.sll_tbl .empty a { color:#f00; }
			.sll_tbl .filtered-off { display:none; }
			.sll_tbl thead.frozen { position:fixed; top:0px; }
			.sll_tbl thead.frozen tr.hider th { border-bottom:3px solid #c00; }
			.sll_tbl .row-even td { background-color:#dee4e8; }
			.sll_tbl .row-odd td { background-color:#f0f1f1; }
			.sll_tbl .row-even.liquidate td { background-color:#9ee; }
			.sll_tbl .row-odd.liquidate td { background-color:#bee; }
			.sll_tbl .row-even.hide td { background-color:#e99; }
			.sll_tbl .row-odd.hide td { background-color:#ebb; }
			.sll_tbl thead { z-index:10; }
			/*.sll_tbl .cat-even.row-even td { background-color:#9ee; }
			.sll_tbl .cat-even.row-odd td { background-color:#bee; }*/

			.curr-qty { background-color:#0f0; }
			.aged30-60-qty { background-color:#ff0; }
			.aged60-plus-qty { background-color:#f00; color:#fff; }

			.utabs { margin:0px; padding:0px; }
			.utabs li { float:left; padding:4px 8px; list-style-type:none; margin-left:8px; border-width:1px 1px 0px 1px; border-style:solid; border-color:#000; background-color:#cb2026; color:#fff; font-weight:bold; cursor:pointer; }
			.utabs li.selected { cursor:default; background-color:#fff; position:relative; top:1px; z-index:5; color:#000; }
			.tabbox { padding:10px 10px 200px 10px; border:1px solid #000; float:left; clear:both; display:none; }
			.tabbox.selected { display:block; }
		</style>
	</head>
	<body marginstyle="width:0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<script type="text/javascript">
			var clicked_button;
			current_livefilter_options = {
			};
			jQuery(document).ready(function($) {
				/* freeze the table header at the top of the page */
				/* this will need to be generalized if we alter the tables on this page so more than one has a thead */
				thead_pos = jQuery('thead').offset().top;
				jQuery(window).scroll(function () {
					if (jQuery('.sll_tbl thead').offset().top - jQuery(window).scrollTop() <= 1) {
						jQuery('.sll_tbl thead').addClass('frozen');
						if (jQuery('.sll_tbl .column-selection').hasClass('hideRow')) {
							jQuery('.sll_tbl thead tr.mainheader').addClass('hider');
						}
						else {
							jQuery('.sll_tbl thead tr.column-selection').addClass('hider');
						}
					}
					if (jQuery(window).scrollTop() <= thead_pos) {
						jQuery('.sll_tbl thead').removeClass('frozen');
						jQuery('.sll_tbl thead tr').removeClass('hider');
					}
					if (jQuery(window).scrollTop() >= thead_pos + jQuery('table.sll_tbl').height()) {
						jQuery('.sll_tbl thead').removeClass('frozen');
						jQuery('.sll_tbl thead tr').removeClass('hider');
					}
				});
				jQuery('#rebuild_columns').click(function () {
					if (jQuery(this).is(':checked')) {
						jQuery('.column-selection').removeClass('hideRow');
						if (jQuery('.sll_tbl .mainheader').hasClass('hider')) {
							jQuery('.sll_tbl .mainheader').removeClass('hider');
							jQuery('.column-selection').addClass('hider')
						}
					}
					else {
						jQuery('.column-selection.hidePossible').addClass('hideRow');
						if (jQuery('.column-selection').hasClass('hider')) {
							jQuery('.sll_tbl .mainheader').addClass('hider');
							jQuery('.column-selection').removeClass('hider')
						}
					}
				});
			});
			function recolor_sll() {
				jQuery('.sll_tbl tbody tr:even').removeClass('row-odd').addClass('row-even');
				jQuery('.sll_tbl tbody tr:odd').removeClass('row-even').addClass('row-odd');
			}
		</script>
		<!-- header_eof //-->
		<!-- body //-->
		<table border="0" style="width:100%" cellspacing="2" cellpadding="2">
			<tr>
				<td style="width:<?php echo BOX_WIDTH; ?>" valign="top">
					<table border="0" style="width:<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td style="width:100%" valign="top">
					<span class="ex-lax">Project Ex-Lax</span>
					<?php if (TRUE) { //!empty($perms['admin_sll'])) { ?>
					<form action="<?= $_SERVER['PHP_SELF']; ?>" id="sll" method="post">
						<input type="hidden" name="selected_box" value="sales"/>
						<input type="hidden" name="action" value="publish"/>
						<ul class="utabs">
							<li class="selected" data-tabref="view_sll">View List</li>
							<li data-tabref="admin_sll">Admin List</li>
						</ul>
						<div id="admin_sll" class="tabbox">
							<input type="submit" value="Update"/>
							<table cellspacing="0" cellpadding="2px" border="0" class="sll_tbl">
								<thead>
									<tr class="dataTableHeadingRow mainheader">
										<th colspan="13"><!-- --></th>
										<th class="dataTableHeadingContent" colspan="3" style="text-align:right;font-size:12px;color:#c00;border-bottom:1px solid #c00;">Total Overstock Value: <?php echo '$'.number_format($total_overstock_value, 2); ?><br/>Total Overstock Qty: <?= $total_overstock_qty; ?></th>
									</tr>
									<tr class="dataTableHeadingRow mainheader">
										<th class="dataTableHeadingContent">
											IPN Category
										</th>
										<th class="dataTableHeadingContent">
											IPN
										</th>
										<th class="dataTableHeadingContent">
											Cond.
										</th>
										<th class="dataTableHeadingContent">
											DS
										</th>
										<th class="dataTableHeadingContent">
											Disc.
										</th>
										<th class="dataTableHeadingContent">
											Qty
										</th>
										<th class="dataTableHeadingContent">
											Qty Sold
										</th>
										<th class="dataTableHeadingContent wide">
											Last Review
										</th>
										<th class="dataTableHeadingContent wide">
											Last Rec'd
										</th>
										<th class="dataTableHeadingContent">
											IPN Age
										</th>
										<th class="dataTableHeadingContent">
											Inv. Value
										</th>
										<th class="dataTableHeadingContent">
											Days Supply
										</th>
										<th class="dataTableHeadingContent">
											Liq. Qty
										</th>
										<th class="dataTableHeadingContent wide">
											Liquidate <input type="checkbox" id="liquidate_all" name="liquidate_all"/>
										</th>
										<th class="dataTableHeadingContent wide">
											Admin Note
										</th>
										<th class="dataTableHeadingContent wide">
											User Note
										</th>
									</tr>
								</thead>
								<tbody>
									<?php $categories = array();
									foreach ($ipns as $rowidx => $row) {
										// go ahead and dereference the stock ID so we can interpolate it directly
										$stock_id = $row['stock_id'];
										if (!in_array($row['ipn_category'], $categories)) $categories[] = $row['ipn_category'];
										?>
									<tr class="data-row-handle <?php echo count($categories)%2==0?'cat-even':'cat-odd'; ?> <?php echo $rowidx%2==0?'row-even':'row-odd'; ?> <?php echo $row['liquidate']?($row['liquidate']=='1'?'liquidate':'hide'):''; ?>">
										<td class="main">
											<?= $row['ipn_category']; ?>
										</td>
										<td class="main wide">
											<a href="ipn_editor.php?ipnId=<?php echo urlencode($row['ipn']); ?>" target="_blank"><?= $row['ipn']; ?></a>
										</td>
										<td class="main">
											<?= $row['cond']; ?>
										</td>
										<td class="main">
											<?= $row['dsns']; ?>
										</td>
										<td class="main">
											<?= $row['disc']; ?>
										</td>
										<td class="main wide">
											<?= $row['total_qty']; ?> -
											<?php if (!empty($row['current_qty'])) { ?><span class="curr-qty">[<?= $row['current_qty']; ?>]</span><?php } ?>
											<?php if ($row['aged30-60_qty']) { ?><span class="aged30-60-qty">[<?php echo $row['aged30-60_qty']; ?>]</span><?php } ?>
											<?php if ($row['aged60-plus_qty']) { ?><span class="aged60-plus-qty">[<?php echo $row['aged60-plus_qty']; ?>]</span><?php } ?>
										</td>
										<td class="main">
											<?php echo ceil($row['period_sales']); ?>
										</td>
										<td class="main wide">
											<?php if (!empty($row['last_review'])) { ?>
											<small><?php echo date('m/d/Y', strtotime($row['last_review'])); ?><br/>[<?php echo ceil((strtotime('today') - strtotime($row['last_review']))/60/60/24); ?> Days]</small>
											<?php } ?>
										</td>
										<td class="main wide">
											<?php if (!empty($row['last_receipt'])) { ?>
											<small><?php echo date('m/d/Y', strtotime($row['last_receipt'])); ?><br/>[<?php echo ceil((strtotime('today') - strtotime($row['last_receipt']))/60/60/24); ?> Days]</small>
											<?php } ?>
										</td>
										<td class="main">
											<?php if (!empty($row['ipn_age'])) { ?>
											<small><?php echo date('m/d/Y', strtotime($row['ipn_age'])); ?></small>
											<?php } ?>
										</td>
										<td class="main" style="text-align:right;">
											<?php echo '$'.number_format($row['inv_value'], 2); ?>
										</td>
										<td class="main" style="text-align:right;">
											<?php echo number_format($row['days_supply'], 1, '.', ''); ?>
										</td>
										<td class="main wide" style="text-align:center;">
											-<input type="text" size="3" id="rec_<?= $stock_id; ?>" name="liquidate_qty[<?= $stock_id; ?>]" value="<?= $row['liquidate_qty']; ?>"/>
											<span id="liquidate_rec_<?= $stock_id; ?>"><?php echo floor($row['overstock_qty']); ?></span>
										</td>
										<td class="main wide">
											<select class="liquidate_ipn<?php if (!empty($row['liquidate'])) { echo ' preset'; } ?>" name="liquidate[<?= $stock_id; ?>]" size="1">
												<option value="0">Unhandled</option>
												<option value="1"<?php if ($row['liquidate']==1) { echo ' selected'; } ?>>Liquidate</option>
												<option value="2"<?php if ($row['liquidate']==2) { echo ' selected'; } ?>>Hide</option>
											</select>
										</td>
										<td class="main wide">
											<textarea name="liquidate_admin_note[<?= $stock_id; ?>]" wrap="soft" rows="3" cols="15"><?= $row['liquidate_admin_note']; ?></textarea>
										</td>
										<td class="main wide">
											<textarea name="liquidate_user_note[<?= $stock_id; ?>]" wrap="soft" rows="3" cols="15"><?= $row['liquidate_user_note']; ?></textarea>
										</td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</form>
					<script>
						jQuery('#admin_sll thead th').each(function() {
							var idx = jQuery(this).index('#admin_sll thead th');
							idx++;
							jQuery(this).css('width', jQuery('#admin_sll tbody tr:first-child td:nth_child('+idx+')').width());
						});
						jQuery('#liquidate_all').click(function() {
							if (jQuery(this).is(':checked')) {
								jQuery('.liquidate_ipn').each(function() {
									if (jQuery(this).val() == 0) {
										jQuery(this).val(1);
									}
								});
							}
							else {
								jQuery('.liquidate_ipn').each(function() {
									if (!jQuery(this).hasClass('preset') && jQuery(this).val() == 1) {
										jQuery(this).val(0);
									}
								});
							}
						});
					</script>
					<?php } ?>
					<div id="view_sll" class="tabbox selected">
						<table cellspacing="0" cellpadding="2px" border="0" class="sll_tbl">
							<thead>
								<tr class="dataTableHeadingRow mainheader">
									<th colspan="3"><!-- --></th>
									<th class="dataTableHeadingContent" colspan="3" style="text-align:right;font-size:12px;color:#c00;border-bottom:1px solid #c00;">Total Listed Value: <?php echo '$'.number_format($total_value, 2); ?><br/>Total Listed Qty: <?= $total_qty; ?></th>
								</tr>
								<tr class="dataTableHeadingRow mainheader">
									<th class="dataTableHeadingContent wide">
										IPN Category
									</th>
									<th class="dataTableHeadingContent wide">
										IPN
									</th>
									<th class="dataTableHeadingContent">
										Cond.
									</th>
									<th class="dataTableHeadingContent">
										Inv. Value
									</th>
									<th class="dataTableHeadingContent">
										Overstock Qty
									</th>
									<th class="dataTableHeadingContent wide">
										Note
									</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!empty($ipns)) {
									$categories = array($ipns[0]['ipn_category']);
									$rowidx = 0;
									foreach ($ipns as $row) {
										if ($row['liquidate'] != 1) continue;
										// go ahead and dereference the stock ID so we can interpolate it directly
										$stock_id = $row['stock_id'];
										if (!in_array($row['ipn_category'], $categories)) $categories[] = $row['ipn_category'];
										?>
								<tr class="data-row-handle <?php echo count($categories)%2==0?'cat-even':'cat-odd'; ?> <?php echo $rowidx++%2==0?'row-even':'row-odd'; ?>">
									<td class="main wide">
										<?= $row['ipn_category']; ?>
									</td>
									<td class="main wide">
										<a href="ipn_editor.php?ipnId=<?php echo urlencode($row['ipn']); ?>" target="_blank"><?= $row['ipn']; ?></a>
									</td>
									<td class="main">
										<?= $row['cond']; ?>
									</td>
									<td class="main" style="text-align:right;">
										<?php echo '$'.number_format($row['inv_value'], 2); ?>
									</td>
									<td class="main" style="text-align:center;">
										<?php echo floor($row['overstock_qty']); ?>
									</td>
									<td class="main wide">
										<?= $row['liquidate_user_note']; ?>
									</td>
								</tr>
									<?php }
								}
								else { ?>
								<tr class="dataTableHeadingRow">
									<th colspan="17" class="dataTableHeadingContent">
										<input type="submit" name="action" value="Build Report"/>
									</th>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<script>
						jQuery('.utabs li').click(function() {
							if (jQuery(this).hasClass('selected')) return;
							jQuery('.utabs li.selected').removeClass('selected');
							jQuery(this).addClass('selected');
							jQuery('.tabbox.selected').removeClass('selected');
							jQuery('#'+jQuery(this).attr('data-tabref')).addClass('selected');
						});
					</script>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
