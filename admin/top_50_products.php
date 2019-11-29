<?php
require('includes/application_top.php');

$forecast = new forecast();

set_time_limit(0);
ini_set('memory_limit', '512M');

if (empty($_GET['admin_id'])) $_GET['admin_id'] = 'ALL';

if (!empty($_GET['t'])) {
	include('top_50_products_'. $_GET['t'] .'.php');
	die();
}

$action = !empty($_GET['action'])?$_GET['action']:NULL;

$rank_by = !empty($_GET['rank-by'])?$_GET['rank-by']:'total_qty';

if ($action == 'Run Report' || $action == 'Export to Excel') {
	switch ($rank_by) {
		case 'total_days':
			$rank = 'sd.total_days';
			break;
		case 'total_revenue':
			$rank = 'ai_data.total_revenue';
			break;
		case 'gross_margin':
			$rank = 'gross_margin';
			break;
		case 'total_qty':
		default:
			$rank = 'ai_data.total_sold';
			break;
	}

	$from_date_time = time() - ($_GET['days'] * 24 * 60 * 60);
	$from_date = new DateTime('@'.$from_date_time);

	$filter = '';
	if (!empty($_GET['pscc'])) $filter .= " and psc.products_stock_control_category_id in ('".implode("','", $_GET['pscc'])."') ";

	$hist_join = $__FLAG['unsold_products']?'LEFT':'';

	$qry = "SELECT psc.stock_id, psc.max_inventory_level, (ai_data.total_revenue - ai_data.total_cost) as gross_margin, CASE WHEN IFNULL(psc.min_inventory_level, 0) > vtsi.lead_time THEN psc.min_inventory_level ELSE vtsi.lead_time END as lead_factor, hist.to180, hist.p3060, hist.to30, IF (hist.to180 = NULL and hist.p3060 = NULL and hist.to30 = NULL, 0, IF (hist.to180/180 <= hist.p3060/30 and hist.to180/180 <= hist.to30/30, IF (hist.p3060/30 <= hist.to30/30, hist.p3060/30 , hist.to30/30 ), IF (hist.p3060/30 <= hist.to180/180 and hist.p3060/30 <= hist.to30/30, IF (hist.to180/180 <= hist.to30/30,hist.to180/180,hist.to30/30), IF (hist.to180/180 <= hist.p3060/30,hist.to180/180,hist.p3060/30) ))) as daily_quantity, CASE WHEN psc.serialized = 0 THEN psc.stock_quantity ELSE s.serial_qty END as quantity, psc.stock_name as ipn, psc.on_order, psc.max_inventory_level, psc.min_inventory_level, psc.target_inventory_level, (IF(psc.serialized = 0, psc.stock_quantity, s.serial_qty)) as on_hand_quantity, ((IF(psc.serialized = 0, psc.stock_quantity, s.serial_qty)) - (IF(psc.serialized = 0,IFNULL((SELECT SUM(ih.quantity) AS on_hold FROM inventory_hold ih WHERE ih.stock_id = psc.stock_id), 0), IFNULL((SELECT count(1) as on_hold FROM serials sih WHERE sih.status = 6 and sih.ipn = psc.stock_id), 0) )) - IFNULL((SELECT SUM(op3.products_quantity) FROM orders o3, orders_products op3, products p3 WHERE o3.orders_id = op3.orders_id and (op3.products_id = p3.products_id or ((op3.products_id - p3.products_id) = 0)) and o3.orders_status in (1, 2, 5, 7, 8, 10, 11, 12) and p3.stock_id = psc.stock_id), 0) - psc.ca_allocated_quantity) as available_quantity, (IF(psc.serialized = 0, psc.stock_quantity * psc.average_cost,(SELECT SUM(shb.cost) FROM serials sb, serials_history shb WHERE sb.ipn = psc.stock_id and sb.status in (2, 3, 6) and sb.id = shb.serial_id and shb.id = (SELECT max(shb2.id) FROM serials_history shb2 WHERE shb2.serial_id = sb.id)))) as inventory_value, pscc.name as category, pscv.name as vertical, ai_data.total_sold as sold, ai_data.total_revenue, sd.total_days, lpctable.last_stock_price_change_date FROM products_stock_control psc LEFT JOIN (SELECT ipn as stock_id, COUNT(id) as serial_qty FROM serials WHERE status IN (2,3,6) GROUP BY stock_id) s ON s.stock_id = psc.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id left join (SELECT max(change_date) as last_stock_price_change_date, stock_id FROM products_stock_control_change_history WHERE type_id in (2, 37) group by stock_id) lpctable on lpctable.stock_id = psc.stock_id LEFT JOIN (SELECT aii.ipn_id as stock_id, SUM(aii.invoice_item_qty) as total_sold, SUM(abs(aii.invoice_item_qty) * aii.invoice_item_price) as total_revenue, SUM(aii.orders_product_cost_total) as total_cost FROM acc_invoice_items aii left join orders_products aid_op on (aii.orders_product_id = aid_op.orders_products_id) join acc_invoices ai on aii.invoice_id = ai.invoice_id WHERE aid_op.exclude_forecast = 0 AND ai.inv_date >= :sales_start_date group by aii.ipn_id) ai_data on ai_data.stock_id = psc.stock_id ".$hist_join." JOIN ck_cache_sales_history hist ON psc.stock_id = hist.stock_id LEFT JOIN (SELECT p.stock_id, COUNT(DISTINCT(DATE(o.date_purchased))) as total_days FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE o.orders_status NOT IN (6, 9) AND o.date_purchased >= :sales_start_date GROUP BY p.stock_id) sd ON psc.stock_id = sd.stock_id LEFT JOIN vendors_to_stock_item vtsi ON psc.vendor_to_stock_item_id = vtsi.id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE true $filter ";

	$params = [':sales_start_date' => $from_date->format('Y-m-d')];

	if ($_GET['admin_id'] != 'ALL') {
		$qry .= ' and v.pm_to_admin_id = :admin_id ';
		$params[':admin_id'] = $_GET['admin_id'];
	}

	if ($_GET['serialized'] != 'BOTH') {
		$qry .= ' and psc.serialized = :serialized';
		$params[':serialized'] = $_GET['serialized']=='S'?1:0;
	}

	$limit_clause = '';
	if (!$__FLAG['all_products']) {
		$limit = (int) $_GET['limit'];
		$limit_clause = ' DESC LIMIT '.$limit;
	}
	$qry .= ' ORDER BY '.$rank.$limit_clause;

	$ipns = prepared_query::fetch($qry, cardinality::SET, $params);

	if ($action == "Export to Excel") {

		$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$worksheet = $workbook->getSheet(0);
		$worksheet->setTitle('Top 50 Products');

		$format_red = array('font' => array('color' => array('rgb' => 'FF3333')));
		$format_yellow = array('font' => array('color' => array('rgb' => 'EEEE00')));
		$format_green = array('font' => array('color' => array('rgb' => '00FF00')));
		$format_cyan = array('font' => array('color' => array('rgb' => '00FFFF')));
		$format_blue = array('font' => array('color' => array('rgb' => '3366FF')));

		//this is coupled to the colors in the forecast class
		$format_mappings = array(
			'f33' => $format_red,
			'ee0' => $format_yellow,
			'0f0' => $format_green,
			'00ffff' => $format_cyan,
			'3366ff' => $format_blue
		);

		$worksheet->getCellByColumnAndRow(1, 1)->setValue('Rank');
		$worksheet->getCellByColumnAndRow(2, 1)->setValue('IPN');
		$worksheet->getCellByColumnAndRow(3, 1)->setValue('IPN Category');
		$worksheet->getCellByColumnAndRow(4, 1)->setValue('IPN Vertical');
		$worksheet->getCellByColumnAndRow(5, 1)->setValue('Min');
		$worksheet->getCellByColumnAndRow(6, 1)->setValue('Tgt');
		$worksheet->getCellByColumnAndRow(7, 1)->setValue('Max');
		$worksheet->getCellByColumnAndRow(8, 1)->setValue('Daily Qty');
		$worksheet->getCellByColumnAndRow(9, 1)->setValue('Days Supply');
		$worksheet->getCellByColumnAndRow(10, 1)->setValue('On Hand Qty');
		$worksheet->getCellByColumnAndRow(11, 1)->setValue('Available Qty');
		$worksheet->getCellByColumnAndRow(12, 1)->setValue('On Order');
		$worksheet->getCellByColumnAndRow(13, 1)->setValue('Last Price Review');
		$worksheet->getCellByColumnAndRow(14, 1)->setValue('Qty Sold');
		$worksheet->getCellByColumnAndRow(15, 1)->setValue('6mo Monthly Avg');
		$worksheet->getCellByColumnAndRow(16, 1)->setValue('31-60 Qty');
		$worksheet->getCellByColumnAndRow(17, 1)->setValue('0-30 Qty');
		$worksheet->getCellByColumnAndRow(18, 1)->setValue('Total Rev');
		$worksheet->getCellByColumnAndRow(19, 1)->setValue('Gross Margin');
		$worksheet->getCellByColumnAndRow(20, 1)->setValue('Inventory Value');

		$count = 2;
		foreach ($ipns as $idx => $ipn) {

			$ckipn = new ck_ipn2($ipn['stock_id']);

			$single_day = $forecast->daily_qty($ipn);
			$available_qty = $ckipn->get_inventory('available'); //$ipn['available_quantity'];
			$days_supply = !$available_qty?0:(!$single_day?'999-':ceil($available_qty/($single_day)));
			$days_indicator = $forecast->days_indicator_color_new($ipn, $days_supply);

			$rank_format = NULL;
			if ($available_qty < 0) {
				$rank_format = $format_yellow;
			}

			$worksheet->getCellByColumnAndRow(1, $count)->setValue($idx+1);
			!empty($rank_format)?$worksheet->getStyle('A'.$count)->applyFromArray($rank_format):NULL;
			$worksheet->getCellByColumnAndRow(2, $count)->setValue($ipn['ipn']);
			$worksheet->getCellByColumnAndRow(3, $count)->setValue($ipn['category']);
			$worksheet->getCellByColumnAndRow(4, $count)->setValue($ipn['vertical']);
			$worksheet->getCellByColumnAndRow(5, $count)->setValue($ipn['min_inventory_level']);
			$worksheet->getCellByColumnAndRow(6, $count)->setValue($ipn['target_inventory_level']);
			$worksheet->getCellByColumnAndRow(7, $count)->setValue($ipn['max_inventory_level']);
			$worksheet->getCellByColumnAndRow(8, $count)->setValue($ipn['daily_quantity']);
			$worksheet->getCellByColumnAndRow(9, $count)->setValue($days_supply);
			$worksheet->getStyle('I'.$count)->applyFromArray($format_mappings[$days_indicator]);
			$worksheet->getCellByColumnAndRow(10, $count)->setValue($ckipn->get_inventory('on_hand'));
			$worksheet->getCellByColumnAndRow(11, $count)->setValue($available_qty);
			$worksheet->getCellByColumnAndRow(12, $count)->setValue($ipn['on_order']);
			$worksheet->getCellByColumnAndRow(13, $count)->setValue(date('M-d-Y', strtotime($ipn['last_stock_price_change_date'])));
			$worksheet->getCellByColumnAndRow(14, $count)->setValue($ipn['sold']);
			$worksheet->getCellByColumnAndRow(15, $count)->setValue(round(($ipn['to180'] / 6 ), 2));
			$worksheet->getCellByColumnAndRow(16, $count)->setValue($ipn['p3060']);
			$worksheet->getCellByColumnAndRow(17, $count)->setValue($ipn['to30']);
			$worksheet->getCellByColumnAndRow(18, $count)->setValue(round($ipn['total_revenue'], 2));
			$worksheet->getCellByColumnAndRow(19, $count)->setValue(round($ipn['gross_margin'], 2));
			$worksheet->getCellByColumnAndRow(20, $count)->setValue(round($ipn['inventory_value'], 2));

			$count++;
		}

		$wb_file = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($workbook, 'Xlsx');
		$wb_file->save('/tmp/top_50_products.xlsx');

		header('Content-Description: File Transfer');
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment;filename=top_50_products.xlsx");
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: '.filesize("/tmp/top_50_products.xlsx"));

		readfile('/tmp/top_50_products.xlsx');

		unlink('/tmp/top_50_products.xlsx');
		exit();
	}

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
				<td style="width:100%; font-family: arial; padding: 15px;" valign="top">
					<script>
						jQuery(function() {
							jQuery("#please_wait").bind("ajaxSend", function() {
								jQuery(this).show();
							}).bind("ajaxComplete", function() {
								jQuery(this).hide();
							});
							jQuery( "#tabs" ).tabs();
						});
					</script>
					<div id="please_wait" class="please_wait">
						<div class="please_wait_inner"><img src="images/ajax-loader.gif"></div>
					</div>
					<style>
						.input-block { margin-bottom:10px; }
						.input-field { margin-right:20px; display:inline-block; }
						.input-field.attn { font-weight:bold; }
						.input-field sub { font-weight:normal; }
					</style>
					<div id="tabs">
						<ul>
							<li><a href="top_50_products.php?t=tab1">Tab1</a></li>
							<li><a href="top_50_products.php?t=tab2">Tab2</a></li>
							<li><a href="#builder">Report Builder</a></li>
						</ul>
						<div id="builder">
							<form action="/admin/top_50_products.php#builder" method="get">
								<div class="input-block">
									Rank By:
									[ <input type="radio" name="rank-by" value="total_qty" <?= $rank_by=='total_qty'?'checked':''; ?>> Total Units ]
									[ <input type="radio" name="rank-by" value="total_days" <?= $rank_by=='total_days'?'checked':''; ?>> Sales Days ]
									[ <input type="radio" name="rank-by" value="total_revenue" <?= $rank_by=='total_revenue'?'checked':''; ?>> Revenue ]
									[ <input type="radio" name="rank-by" value="gross_margin" <?= $rank_by=='gross_margin'?'checked':''; ?>> Gross Margin ]
								</div>

								<div class="input-block">
									<div class="input-field attn">
										Top
										<sub>[<input type="checkbox" id="show-all-products" name="all_products" <?= $__FLAG['all_products']?'checked':''; ?>> all]</sub>
										<input type="text" id="report-limit" name="limit" value="<?= !empty($_GET['limit'])?$_GET['limit']:50; ?>" style="width:35px;text-align:center;" <?= $__FLAG['all_products']?'disabled':''; ?>>
										Products
										<script>
											jQuery('#show-all-products').click(function() {
												if (jQuery(this).is(':checked')) jQuery('#report-limit').attr('disabled', true);
												else jQuery('#report-limit').attr('disabled', false);
											});
										</script>
									</div>

									<div class="input-field">
										<select name="admin_id" size="1">
											<option value="ALL">All Managers</option>
											<?php $managers = prepared_query::fetch("SELECT DISTINCT a.admin_id, CONCAT(a.admin_firstname, ' ', a.admin_lastname) as admin_name FROM vendors v JOIN admin a ON v.pm_to_admin_id = a.admin_id ORDER BY admin_name", cardinality::SET);
											foreach ($managers as $manager) { ?>
											<option value="<?= $manager['admin_id']; ?>" <?= $_GET['admin_id']==$manager['admin_id']?'selected':''; ?>><?= $manager['admin_name']; ?></option>
											<?php } ?>
										</select>
									</div>

									<div class="input-field">
										<select name="serialized">
											<option value="BOTH">Serialized/Unserialized</option>
											<option value="U" <?= !empty($_GET['serialized'])&&$_GET['serialized']=='U'?'selected':''; ?>>Unserialized</option>
											<option value="S" <?= !empty($_GET['serialized'])&&$_GET['serialized']=='S'?'selected':''; ?>>Serialized</option>
										</select>
									</div>

									<div class="input-field">
										Category:
										<select name="pscc[]" multiple data-placeholder="Choose one or more..." class="jquery-chosen">
											<?php $psc_cats = prepared_query::fetch('select pscc.categories_id as id, pscc.name from products_stock_control_categories pscc where 1 order by pscc.name asc', cardinality::SET);
											foreach ($psc_cats as $unused => $row) { ?>
											<option value="<?= $row['id']; ?>" <?= !empty($_GET['pscc'])&&in_array($row['id'], $_GET['pscc'])?'selected':''; ?>><?= $row['name']; ?></option>
											<?php } ?>
										</select>
									</div>
								</div>

								<div class="input-block">
									<div class="input-field attn">
										<strong>Sales Range</strong> <input type="text" name="days" value="<?= !empty($_GET['days'])?$_GET['days']:90; ?>" style="width:35px;text-align:center;"> Days
										<sub>[<input type="checkbox" name="unsold_products" <?= $__FLAG['unsold_products']?'checked':''; ?>> include unsold products]</sub>
									</div>

									<div class="input-field">
										<input type="submit" name="action" value="Run Report">
										<input type="submit" name="action" value="Export to Excel">
									</div>
								</div>
							</form>
							<table cellspacing="0" id="buildertable" cellpadding="0" border="0" class="fc tablesorter">
								<thead>
									<tr>
										<th>Rank</th>
										<th>IPN</th>
										<th>IPN Category</th>
										<th>IPN Vertical</th>
										<th>Min</th>
										<th>Tgt</th>
										<th>Max</th>
										<th>Daily Qty</th>
										<th>Days Supply</th>
										<th>On Hand Qty</th>
										<th>Available Qty</th>
										<th>On Order</th>
										<th>Last Price Review</th>
										<th>Qty Sold</th>
										<th>6mo Monthly Avg</th>
										<th>31-60 Qty</th>
										<th>0-30 Qty</th>
										<th>Total Rev</th>
										<th>Gross Margin</th>
										<th>Inventory Value</th>
									</tr>
								</thead>
								<tbody>
								<?php if (!empty($ipns)) {
									foreach ($ipns as $idx => $ipn) {

										$ckipn = new ck_ipn2($ipn['stock_id']);

										$single_day = $forecast->daily_qty($ipn);
										$available_qty = $ckipn->get_inventory('available'); //$ipn['available_quantity'];
										$days_supply = !$available_qty?0:(!$single_day?'999-':ceil($available_qty/($single_day)));
										$days_indicator = $forecast->days_indicator_color_new($ipn, $days_supply);

										$style_string = "";
										//$available_qty = $ipn['quantity'];
										if ($available_qty < 0) {
											$style_string .= "background-color: #FFEE22;";
										} ?>
									<tr style="<?= $style_string; ?>">
										<th><?php echo $idx+1; ?></th>
										<td><a href="/admin/ipn_editor.php?ipnId=<?= $ipn['ipn']; ?>" target="_blank"><?= $ipn['ipn']; ?></a></td>
										<td><?= $ipn['category']; ?></td>
										<td><?= $ipn['vertical']; ?></td>
										<td><?= $ipn['min_inventory_level']; ?></td>
										<td><?= $ipn['target_inventory_level']; ?></td>
										<td><?= $ipn['max_inventory_level']; ?></td>
										<td><?= $ipn['daily_quantity']; ?></td>
										<td style="background-color:#<?= $days_indicator; ?>"><?= $days_supply; ?></td>
										<td><?= $ckipn->get_inventory('on_hand'); ?> </td>
										<td><?= $available_qty; ?> </td>
										<td><?= $ipn['on_order']; ?></td>
										<?php $last_price_review = new DateTime($ipn['last_stock_price_change_date']);
										$todays_date = new DateTime('now'); ?>
										<td title="<?= $ipn['last_stock_price_change_date']; ?>"><?= $todays_date->diff($last_price_review)->format('%a'); ?></td>
										<td><?= $ipn['sold']; ?></td>
										<td><?php echo round(($ipn['to180'] / 6 ), 2);?></td>
										<td><?= $ipn['p3060']; ?></td>
										<td><?= $ipn['to30']; ?></td>
										<td>$<?php echo number_format($ipn['total_revenue'], 2); ?></td>
										<td>$<?php echo number_format($ipn['gross_margin'], 2); ?></td>
										<td>$<?php echo number_format($ipn['inventory_value'], 2); ?></td>
									</tr>
									<?php }
								} ?>
								</tbody>
							</table>
							<script type="text/javascript">
								jQuery(document).ready(function($) {
									jQuery("#buildertable").tablesorter({
										headers: {
											12: { sorter:'digit'}
										}
									});
								});
							</script>
						</div>
					</div>
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
