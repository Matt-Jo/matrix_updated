<?php
require('includes/application_top.php');

$ipns = prepared_query::fetch('SELECT DISTINCT psc.stock_id, psc.stock_name as ipn, v.vendors_company_name as vendor, MAX(old_po.id) as po_id FROM products_stock_control psc JOIN vendors_to_stock_item vtsi ON psc.vendor_to_stock_item_id = vtsi.id AND (vtsi.preferred IS NULL OR vtsi.preferred = 1) JOIN vendors v ON vtsi.vendors_id = v.vendors_id JOIN purchase_order_products pop ON psc.stock_id = pop.ipn_id JOIN purchase_orders old_po ON pop.purchase_order_id = old_po.id AND TO_DAYS(old_po.creation_date) <= TO_DAYS(NOW()) - 180 LEFT JOIN purchase_orders new_po ON pop.purchase_order_id = new_po.id AND TO_DAYS(new_po.creation_date) > TO_DAYS(NOW()) - 180 WHERE psc.serialized = 0 AND psc.stock_quantity > 0 AND new_po.purchase_order_number IS NULL GROUP BY psc.stock_id, psc.stock_name, v.vendors_company_name ORDER BY v.vendors_company_name, psc.stock_name', cardinality::SET);

$forecast = new forecast;
$frcst = $forecast->build_report('ALL');

$ipn_list = array();
foreach ($ipns as $idx => $ipn) {
	$ipn_list[$idx] = $ipn['stock_id'];
}

$forecasts = array();
foreach ($frcst as $idx => $ipn) {
	if (!in_array($ipn['stock_id'], $ipn_list)) continue; // if it's not relevant to our results, skip it
	$rowidx = array_search($ipn['stock_id'], $ipn_list);

	$ipns[$rowidx]['onhand'] = $ipn['quantity'];
	$ipns[$rowidx]['available'] = $ipn['available_quantity'];
	$ipns[$rowidx]['oh-overstock'] = $ipn['quantity'] - $ipn['target_max_qty'];
	$ipns[$rowidx]['a-overstock'] = $ipn['available_quantity'] - $ipn['target_max_qty'];

	if ($ipns[$rowidx]['onhand'] <= 0 || $ipns[$rowidx]['oh-overstock'] <= 0) unset($ipns[$rowidx]);
}

unset($frcst);

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
		</style>
	</head>
	<body marginstyle="width:0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
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
					<?php if (@$fp = fopen(dirname(__FILE__)."/vendor_return_report.csv", "w")) {
						$column_names = array(
							'ipn',
							'vendor',
							'po #',
							'po date',
							'po price',
							'po qty',
							'on hand',
							'o-stock on-hand',
							'available',
							'o-stock available'
						);
						fwrite($fp, implode(',', $column_names)."\n"); ?>
					<a href="vendor_return_report.csv">EXPORT</a> (right click - save as; please wait until the entire report has loaded, or you will lose some data)<br>
					<?php }
					else { ?>
					System failed to create export
					<?php } ?>
					<table cellspacing="0" cellpadding="0" border="0" class="fc">
						<thead>
							<tr>
								<th>IPN</th>
								<th>Pref. Vendor</th>
								<th>POs</th>
								<th>On Hand/+</th>
								<th>Available/+</th>
							</tr>
						</thead>
						<?php if (!empty($ipns)) {
							foreach ($ipns as $idx => $ipn) {
								if (empty($ipn['onhand'])) continue; ?>
						<tbody>
							<tr>
								<td><a href="ipn_editor.php?ipnId=<?= $ipn['ipn']; ?>" target="_blank"><?= $ipn['ipn']; ?></a></td>
								<td><?= $ipn['vendor']; ?></td>
								<td>
									<table cellspacing="0" cellpadding="0" border="0" class="fc">
										<thead>
											<tr>
												<th>PO #</th>
												<th>PO Date</th>
												<th>PO Price</th>
												<th>PO Qty</th>
											</tr>
										</thead>
										<tbody>
										<?php $po_stock = $ipn['oh-overstock'];
										$max_po = $ipn['po_id'];
										$po = 1;
										while ($po_stock > 0 && !empty($po)) {
											$po = prepared_query::fetch('SELECT po.id, po.purchase_order_number, DATE(po.creation_date) as po_date, pop.cost, pop.quantity FROM purchase_orders po JOIN purchase_order_products pop ON po.id = pop.purchase_order_id WHERE po.id <= ? AND pop.ipn_id = ? AND pop.quantity > 0 AND pop.cost > 0 ORDER BY po.creation_date DESC', cardinality::ROW, array($max_po, $ipn['stock_id']));
											$po_stock -= $po['quantity'];
											$max_po = $po['id'] - 1;
											$podt = new DateTime($po['po_date']);

											$export_line = array($ipn['ipn'], '"'.$ipn['vendor'].'"');
											$export_line[] = $po['purchase_order_number'];
											$export_line[] = $podt->format('m/d/Y');
											$export_line[] = $po['cost'];
											$export_line[] = $po['quantity'];
											$export_line[] = $ipn['onhand'];
											$export_line[] = $ipn['oh-overstock'];
											$export_line[] = $ipn['available'];
											$export_line[] = $ipn['a-overstock'];

											if ($fp) fwrite($fp, implode(',', $export_line)."\n"); ?>
											<tr>
												<td><?= $po['purchase_order_number']; ?></td>
												<td><?php echo $podt->format('m/d/Y'); ?></td>
												<td><?= $po['cost']; ?></td>
												<td><?= $po['quantity']; ?></td>
											</tr>
										<?php } ?>
										</tbody>
									</table>
								</td>
								<td><?= $ipn['onhand']; ?> / <span style="background-color:#cc0;"><?php echo $ipn['oh-overstock']; ?></span></td>
								<td><?= $ipn['available']; ?> / <span style="background-color:#cc0;"><?php echo $ipn['a-overstock']; ?></span></td>
							</tr>
						</tbody>
							<?php }
							if ($fp) fclose($fp);
						} ?>
					</table>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
