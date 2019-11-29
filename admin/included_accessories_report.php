<?php
require('includes/application_top.php');

ini_set('memory_limit', '786M');
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);
ck_ipn2::run_ipn_set();

ck_ipn2::cache(FALSE);

$accessories = prepared_query::fetch('SELECT DISTINCT psc.stock_id, psc.stock_name as ipn, pscc.name as category, psc.stock_quantity, op.usage_qty FROM products_stock_control psc JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id JOIN products p ON psc.stock_id = p.stock_id JOIN (SELECT DISTINCT product_addon_id AS products_id FROM product_addons pa WHERE pa.included = 1) pa ON p.products_id = pa.products_id LEFT JOIN (SELECT p.products_id, SUM(op.products_quantity) as usage_qty FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 90 GROUP BY p.products_id) op ON p.products_id = op.products_id', cardinality::SET);

$categories = [];
foreach ($accessories as $accessory) {
	if (!in_array($accessory['category'], $categories)) $categories[] = $accessory['category'];
}

$pos = prepared_query::fetch('SELECT po.purchase_order_number, po.id as po_id, pop.ipn_id as stock_id, SUM(pop.quantity) - IFNULL(SUM(porp.quantity_received), 0) as outstanding_qty FROM purchase_orders po JOIN purchase_order_products pop ON po.id = pop.purchase_order_id LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id JOIN (SELECT DISTINCT pa.stock_id as stock_id FROM product_addons pad JOIN products pa ON pad.product_addon_id = pa.products_id WHERE pad.included = 1) pa ON pop.ipn_id = pa.stock_id WHERE po.status IN (1, 2) GROUP BY po.purchase_order_number, pop.ipn_id HAVING outstanding_qty > 0', cardinality::SET);

$po_lookup = [];
foreach ($pos as $po) {
	if (empty($po_lookup[$po['stock_id']])) $po_lookup[$po['stock_id']] = ['list' => [], 'total' => 0];
	$po_lookup[$po['stock_id']]['list'][] = $po;
	$po_lookup[$po['stock_id']]['total'] += $po['outstanding_qty'];
}

$parents = prepared_query::fetch('SELECT DISTINCT psc.stock_id, psc.stock_name as ipn, pa.accessory_stock_id, psc.stock_quantity FROM products_stock_control psc JOIN (SELECT DISTINCT p.stock_id, pa.stock_id as accessory_stock_id FROM product_addons pad JOIN products p ON pad.product_id = p.products_id JOIN products pa ON pad.product_addon_id = pa.products_id WHERE pad.included = 1) pa ON psc.stock_id = pa.stock_id', cardinality::SET);

$parent_lookup = [];
foreach ($parents as &$parent) {
	$ipn = new ck_ipn2($parent['stock_id']);
	$parent['stock_quantity'] = $ipn->get_inventory('on_hand');
	if (empty($parent_lookup[$parent['accessory_stock_id']])) $parent_lookup[$parent['accessory_stock_id']] = ['list' => [], 'total' => 0];
	$parent_lookup[$parent['accessory_stock_id']]['list'][] = $parent;
	$parent_lookup[$parent['accessory_stock_id']]['total'] += $parent['stock_quantity'];
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="../includes/javascript/prototype.js"></script>
		<style>
			.fc th, .fc td { border-style:solid; border-color:#000; border-width:0px 1px 1px 0px; padding:4px 6px; }
			.fc tr:first-child th, .fc tr:first-child td { border-top-width:1px; }
			.fc th:first-child, .fc td:first-child { border-left-width:1px; }
			.grouper { position:relative; overflow:hidden; margin:10px; border:1px solid #000; }
			.ipnlink { display:block; width:300px; text-align:center; float:left; border-right:1px solid #000; padding:2px 0px; }
			.ipnlink:hover { color:#c00; }
			.ipnlink span { color:#000; }
			#ipn-selector { cursor:pointer; }
			.ipn-selector { display:<?= !empty($_GET['stock_id'])?'none':'block'; ?>; }
			td.qty-display span { display:block; float:left; width:35px; padding:2px; text-align:center; margin:2px; font-weight:bold; }
			.onhand { background-color:#bbb; }
			.allocated { background-color:#bbf; }
			.held { background-color:#fbb; }
			.available { background-color:#bfb; }
			.pop_open { display:none; background-color:#fff; border:1px solid #000; padding:5px 7px; position:absolute; }
			table.tablesorter tbody tr.odd.warn td, table.tablesorter tbody tr.even.warn td, .warn th { background-color:#fcc; }
		</style>
	</head>
	<body marginstyle="width:0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<script language="javascript" src="includes/general.js"></script>
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
					<form action="<?= $_SERVER['PHP_SELF']; ?>" id="srl" method="get">
						<input type="hidden" name="selected_box" value="purchasing">
						<div style="border: 1px solid black; padding: 10px 10px 200px 10px;">
							<select name="category_filter" id="category_filter" size="1">
								<option value="">All Categories</option>
								<?php foreach ($categories as $category) { ?>
								<option value="<?php echo strtolower(preg_replace('/[^a-zA-Z]/', '-', $category)); ?>"><?= $category; ?></option>
								<?php } ?>
							</select><br><br>
							<a href="/admin/included_accessories_report.csv">Export</a> (right click, save as)
							<table cellspacing="0" id="buildertable" cellpadding="0" border="0" class="fc tablesorter">
								<thead>
									<tr>
										<th>IPN</th>
										<th>Category</th>
										<th class="qty-display">Qty <span class="onhand">On Hand</span>, <span class="allocated">Allocated</span>, <span class="held">Held</span> & <span class="available">Available</span></th>
										<th>Qty on PO</th>
										<th>Total Parent Qty</th>
										<th>Coverage</th>
										<th>90 Days Usage</th>
									</tr>
								</thead>
								<tbody>
								<?php if (!empty($accessories)) {
									if ($fp = @fopen(dirname(__FILE__)."/included_accessories_report.csv", "w")) {
										fputcsv($fp, ['IPN', 'Category', 'Qty on Hand', 'Qty Allocated', 'Qty Held', 'Qty Available', 'Qty on PO', 'Total Parent Qty', 'Coverage', '90 Days Usage']);
									}

									foreach ($accessories as $idx => $accessory) {
										$ipn = new ck_ipn2($accessory['stock_id']);
										$inventory = $ipn->get_inventory();
										$available = $inventory['available'];

										$coverage = $available>=@$parent_lookup[$accessory['stock_id']]['total']?'100%':($available<=0?'0%':((int)(@($available/@$parent_lookup[$accessory['stock_id']]['total'])*100)).'%');

										if ($fp) fputcsv($fp, [$accessory['ipn'], $accessory['category'], $accessory['stock_quantity'], $inventory['allocated'], $inventory['on_hold'], $available, $po_lookup[$accessory['stock_id']], $parent_lookup[$accessory['stock_id']], $coverage, $accessory['usage_qty']]); ?>
									<tr class="accessory-row category-<?php echo strtolower(preg_replace('/[^a-zA-Z]/', '-', $accessory['category'])); ?> <?php echo @$parent_lookup[$accessory['stock_id']]['total']>$available?'warn':''; ?>">
										<th><a href="/admin/ipn_editor.php?ipnId=<?= $accessory['ipn']; ?>"><?= $accessory['ipn']; ?></a></th>
										<td><?= $accessory['category']; ?></td>
										<td class="qty-display"><span class="onhand"><?= $accessory['stock_quantity']; ?></span> <span class="allocated"><?php echo max(0, $inventory['allocated']); ?></span> <span class="held"><?= max(0, $inventory['on_hold']); ?></span> <span class="available"><?= $available; ?></span></td>
										<td>
											<a href="#" class="pos-details" id="pos_<?= $accessory['stock_id']; ?>"><?= @$po_lookup[$accessory['stock_id']]['total']; ?></a>
											<div class="pop_open" id="pos_<?= $accessory['stock_id']; ?>-details">
												<?php foreach (@$po_lookup[$accessory['stock_id']]['list'] as $po) { ?>
												<a href="/admin/po_viewer.php?poId=<?= $po['po_id']; ?>">[PO # <?= $po['purchase_order_number']; ?>]</a>] - <?= $po['outstanding_qty']; ?><br>
												<?php } ?>
											</div>
										</td>
										<td>
											<a href="#" class="parent-details" id="parent_<?= $accessory['stock_id']; ?>"><?= @$parent_lookup[$accessory['stock_id']]['total']; ?></a>
											<div class="pop_open" id="parent_<?= $accessory['stock_id']; ?>-details">
												<?php foreach (@$parent_lookup[$accessory['stock_id']]['list'] as $parent) { ?>
												<a href="/admin/ipn_editor.php?ipnId=<?= $parent['ipn']; ?>"><?= $parent['ipn']; ?></a> - <?= $parent['stock_quantity']; ?><br>
												<?php } ?>
											</div>
										</td>
										<td><?= $coverage; ?></td>
										<td><?= $accessory['usage_qty']; ?></td>
									</tr>
									<?php }
									if ($fp) fclose($fp);
								} ?>
								</tbody>
							</table>
							<script>
								jQuery(document).ready(function($) {
									jQuery('.tablesorter').tablesorter({
										widgets: ['zebra'],
										theme: 'blue',
										textExtraction: function(node) {
											return $(node).text();
										},
										headers: {
											2: { sorter: false },
											3: { sorter: 'digit' },
											4: { sorter: 'digit' }
										},
										sortList: [[5,0], [0,0]]
									});

									jQuery('#category_filter').change(function() {
										if (jQuery(this).val() == '') jQuery('.accessory-row').show();
										else {
											jQuery('.accessory-row').hide();
											jQuery('.category-'+jQuery(this).val()).show();
										}
									});

									jQuery('.pop_open').click(function(e) {
										e.stopPropagation();
									});

									var last_clicked_po;
									jQuery('.pos-details, .parent-details').click(function(e) {
										last_clicked_po = '#'+jQuery(this).attr('id')+'-details';

										jQuery(last_clicked_po).show();
										jQuery('body').addClass('viewing-details');

										e.preventDefault();
									});

									jQuery('.viewing-details').live('click', function() {
										jQuery('.pop_open.viewing').hide().removeClass('viewing');
										if (last_clicked_po != undefined) {
											jQuery(last_clicked_po).addClass('viewing');
											last_clicked_po = undefined;
										}
										else jQuery('body').removeClass('viewing-details');
									});
								});
							</script>
						</div>
					</form>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
