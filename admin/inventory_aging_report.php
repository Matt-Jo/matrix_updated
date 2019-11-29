<?php
require('includes/application_top.php');
ini_set('memory_limit', '386M');

$action = !empty($_GET['action'])?$_GET['action']:NULL;

if ($action == 'run') {
	$date_range = !empty($_REQUEST['date_range'])?$_REQUEST['date_range']:NULL;
	if (empty($date_range)) {
		$date_end = new DateTime();
		$date_start = clone $date_end;
		$date_start->sub(new DateInterval('P1Y'));
	}
	else {
		if (preg_match('/-/', $date_range)) list($date_start, $date_end) = explode('-', $date_range, 2);
		else $date_start = $date_end = $date_range;

		$date_start = new DateTime($date_start);
		$date_end = new DateTime($date_end);
	}

	$start = time();
	switch ($_GET['report_type']) {
		case 'turns':
			$start_values = ck_ipn2::get_value_on_date($date_start);
			$end_values = ck_ipn2::get_value_on_date($date_end);
			$cogs = ck_ipn2::get_cogs_for_period($date_start, $date_end);
			break;
		case 'value':
			if ($date_start != $date_end) {
				$start_values = ck_ipn2::get_value_on_date($date_start);
				//$end_values = ck_ipn2::get_value_on_date($date_end);
			}
			else {
				$start_values = $end_values = ck_ipn2::get_value_on_date($date_start);
			}
			break;
		case 'ipn':
			$start_values = ck_ipn2::get_value_on_date($date_start, FALSE);
			$end_values = ck_ipn2::get_value_on_date($date_end, FALSE);
			$cogs = ck_ipn2::get_cogs_for_period($date_start, $date_end, FALSE);
			break;
		default:
			break;
	}
	$end = time();
}

$categories_list = prepared_query::fetch('SELECT pscv.name as vertical, pscc.name as category FROM products_stock_control_verticals pscv RIGHT JOIN products_stock_control_categories pscc ON pscv.id = pscc.vertical_id ORDER BY pscv.name ASC, pscc.name ASC', cardinality::SET);

if ($action == 'run' && $_GET['report_type'] == 'value') {
	$report_type = 'value';
	$columns = 3;
}
elseif ($action == 'run' && $_GET['report_type'] == 'ipn') {
	$report_type = 'ipn';
	$columns = 5;
}
else {
	$report_type = 'turns';
	$columns = 4;
} ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="/includes/javascript/prototype.js"></script>
	<style>
		h1 { color:#727272; font-size:20px; margin:0px; padding:0px; }
	</style>
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
				<h1>Inventory Aging Report</h1>
				<form action="/admin/inventory_aging_report.php" method="get">
					<input type="hidden" name="action" value="run">
					[<input type="radio" name="report_type" value="turns" <?= $report_type=='turns'?'checked':''; ?>> Inventory Turns/Aging]
					[<input type="radio" name="report_type" value="value"<?= $report_type=='value'?'checked':''; ?>> Value on Date]
					[<input type="radio" name="report_type" value="ipn"<?= $report_type=='ipn'?'checked':''; ?>> IPN Detail]<br>
					Show for range:
					<link href="/includes/javascript/daterangepicker.css" rel="stylesheet">
					<script src="/includes/javascript/moment.min.js"></script>
					<script src="/includes/javascript/jquery.daterangepicker.js"></script>
					<input id="date_range" name="date_range">
					<script>
						jQuery('#date_range').dateRangePicker({
							format: 'MM/DD/YYYY',
							separator: '-',
							batchMode: 'year-range',
							shortcuts: null,
							customShortcuts: [
								{
									name: 'Previous 12 months',
									dates: function() {
										var start = moment().subtract(1, 'year').toDate();
										var end = moment().subtract(1, 'day').toDate();
										return [start, end];
									}
								},
								{
									name: 'Previous Calendar Year',
									dates: function() {
										var start = moment().dayOfYear(1).subtract(1, 'year').toDate();
										var end = moment().dayOfYear(1).subtract(1, 'day').toDate();
										return [start, end];
									}
								}
							]
						});
						<?php if (!empty($date_start) && !empty($date_end)) { ?>
						jQuery('#date_range').data('dateRangePicker').setDateRange('<?= $date_start->format('m/d/Y'); ?>', '<?= $date_end->format('m/d/Y'); ?>');
						<?php } ?>
					</script>
					<input type="submit" value="Go">
				</form>
				<style>
					#aging-report { margin:10px; }
					#aging-report th, #aging-report td { border-style:solid; border-color:#000; border-width:1px 0px 0px 1px; padding:5px 8px; }
					#aging-report th:last-child, #aging-report td:last-child { border-right-width:1px; }
					#aging-report tr:last-child th, #aging-report tr:last-child td { border-bottom-width:1px; }
					.pop_open { display:none; background-color:#fff; border:1px solid #000; padding:5px 7px; position:absolute; /*width:150px;*/ font-size:10px; text-align:center; }
					#aging-report a { font-size:12px; text-decoration:underline; }
				</style>
				<table border="0" cellpadding="0" cellspacing="0" id="aging-report">
					<thead>
						<tr>
							<th colspan="<?= $columns; ?>">Period: <?= !empty($date_start)?$date_start->format('m/d/Y'):'[none]'; ?>-<?= !empty($date_end)?$date_end->format('m/d/Y'):'[none]'; ?></th>
						</tr>
						<tr>
							<?php if ($report_type == 'value') { ?>
							<th>Vertical</th>
							<th>Category</th>
							<th>Value</th>
							<?php }
							elseif ($report_type == 'ipn') { ?>
							<th>IPN</th>
							<th>Vertical</th>
							<th>Category</th>
							<th>Inventory Turns</th>
							<th>Inventory Aging</th>
							<?php }
							else { ?>
							<th>Vertical</th>
							<th>Category</th>
							<th>Inventory Turns</th>
							<th>Inventory Aging</th>
							<?php } ?>
						</tr>
					</thead>
					<?php if (!empty($action)) { ?>
					<tfoot>
						<tr>
							<th colspan="<?= $columns; ?>">Total Runtime: <?= $end-$start; ?></th>
						</tr>
					</tfoot>
					<?php } ?>
					<tbody>
						<?php if (empty($action)) { ?>
						<tr>
							<th colspan="<?= $columns; ?>">Run Report</th>
						</tr>
						<?php }
						else { ?>
						<tr>
							<?php if ($report_type == 'ipn') { ?>
							<th></th>
							<?php } ?>
							<th>
								<select name="vertical_filter" class="filter" size="1">
									<option value="">All Verticals</option>
									<?php $verts = array();
									foreach ($categories_list as $vertical) {
										if (in_array($vertical['vertical'], $verts)) continue;
										$verts[] = $vertical['vertical'];
										$vertkey = md5($vertical['vertical']); ?>
									<option value="<?= $vertkey; ?>"><?= $vertical['vertical']; ?></option>
									<?php } ?>
								</select>
							</th>
							<th>
								<select name="category_filter" class="filter" size="1">
									<option value="">All Categories</option>
									<?php $cats = array();
									foreach ($categories_list as $category) {
										if (in_array($category['category'], $cats)) continue;
										$cats[] = $category['category'];
										$vertkey = md5($category['vertical']);
										$catkey = md5($category['category']); ?>
									<option class="report-filter <?= $vertkey; ?>" value="<?= $catkey; ?>"><?= $category['category']; ?></option>
									<?php } ?>
								</select>
							</th>
							<?php if ($report_type == 'ipn') { ?>
							<td colspan="<?= $columns-3; ?>"></td>
							<?php }
							else { ?>
							<td colspan="<?= $columns-2; ?>"></td>
							<?php } ?>
						</tr>
							<?php if ($report_type == 'value') {
								foreach ($start_values as $vertical => $cat) {
									$vertkey = md5($vertical);
									foreach ($cat as $category => $value) {
										$catkey = md5($category); ?>
						<tr class="report-row <?= $vertkey; ?> <?= $catkey; ?>">
							<td><?= $vertical; ?></td>
							<td><?= $category; ?></td>
							<td>$<?= number_format($value, 2); ?></td>
						</tr>
									<?php }
								}
							}
							elseif ($report_type == 'ipn') {
								foreach ($cogs as $entry) {
									$row_key = md5($entry['vertical'].$entry['category'].$entry['ipn']);
									$vertkey = md5($entry['vertical']);
									$catkey = md5($entry['category']);
									// we don't have any stock listed for this item
									if (!isset($start_values[$entry['stock_id']]) && !isset($end_values[$entry['stock_id']])) {
										$turns = 'N/A';
										$aging = 'N/A';
									}
									else {
										// we only have a beginning or an ending value
										if (!isset($start_values[$entry['stock_id']]) xor !isset($end_values[$entry['stock_id']])) {
											$avg = isset($start_values[$entry['stock_id']])?$start_values[$entry['stock_id']]:$end_values[$entry['stock_id']];
										}
										// we have both a beginning and ending value
										else {
											$avg = (($start_values[$entry['stock_id']] + $end_values[$entry['stock_id']]) / 2);
										}
										// Formula: (COGS/period) / (AVG inv value over period)
										$turns = !empty($avg)?round($entry['cogs']/$avg, 2):0;
										// Formula: (1/Inventory Turns) * Period
										$days = $date_start->diff($date_end)->days;
										$aging = $entry['cogs']!=0?round(($avg/$entry['cogs'])*$days, 2):'N/A';
									} ?>
						<tr class="report-row <?= $vertkey; ?> <?= $catkey; ?>">
							<td><a href="/admin/ipn_editor.php?ipnId=<?= $entry['ipn']; ?>" target="_blank"><?= $entry['ipn']; ?></a></td>
							<td><?= $entry['vertical']; ?></td>
							<td><?= $entry['category']; ?></td>
							<td>
								<a href="#" class="detail-box" id="<?= $row_key; ?>-turns"><?= $turns; ?></a>
								<div class="pop_open" id="<?= $row_key; ?>-turns-details">
									$<?= number_format($entry['cogs'], 2); ?> (COGS)
									<hr>
									<?php if (!isset($start_values[$entry['stock_id']]) && !isset($end_values[$entry['stock_id']])) {
										echo 'N/A';
									}
									else {
										if (!isset($start_values[$entry['stock_id']]) xor !isset($end_values[$entry['stock_id']])) {
											echo isset($start_values[$entry['stock_id']])?'$'.number_format($start_values[$entry['stock_id']], 2).' (Start Value)':'N/A';
											echo ' + ';
											echo isset($end_values[$entry['stock_id']])?'$'.number_format($end_values[$entry['stock_id']], 2).' (End Value)':'N/A';
										}
										else {
											echo '($'.number_format($start_values[$entry['stock_id']], 2).' (Start Value) + $'.number_format($end_values[$entry['stock_id']], 2).' (End Value)) / 2 (Avg Value)';
										}
									} ?>
								</div>
							</td>
							<td>
								<a href="#" class="detail-box" id="<?= $row_key; ?>-aging"><?= $aging; ?></a>
								<div class="pop_open" id="<?= $row_key; ?>-aging-details">
									<?php if (!isset($start_values[$entry['stock_id']]) && !isset($end_values[$entry['stock_id']])) {
										echo 'N/A';
									}
									else {
										echo $days.' (Days) * (';
										if (!isset($start_values[$entry['stock_id']]) xor !isset($end_values[$entry['stock_id']])) {
											echo isset($start_values[$entry['stock_id']])?'$'.number_format($start_values[$entry['stock_id']], 2).' (Start Value)':'N/A';
											echo ' + ';
											echo isset($end_values[$entry['stock_id']])?'$'.number_format($end_values[$entry['stock_id']], 2).' (End Value)':'N/A';
										}
										else {
											echo '($'.number_format($start_values[$entry['stock_id']], 2).' (Start Value) + $'.number_format($end_values[$entry['stock_id']], 2).' (End Value)) / 2 (Avg Value)';
										}
										echo ')';
									} ?>
									<hr>
									$<?= number_format($entry['cogs'], 2); ?> (COGS)
								</div>
							</td>
						</tr>
								<?php }
							}
							else {
								foreach ($cogs as $entry) {
									$row_key = md5($entry['vertical'].$entry['category']);
									$vertkey = md5($entry['vertical']);
									$catkey = md5($entry['category']);
									// we don't have any stock listed for this item
									if (!isset($start_values[$entry['vertical']][$entry['category']]) && !isset($end_values[$entry['vertical']][$entry['category']])) {
										$turns = 'N/A';
										$aging = 'N/A';
									}
									else {
										// we only have a beginning or an ending value
										if (!isset($start_values[$entry['vertical']][$entry['category']]) xor !isset($end_values[$entry['vertical']][$entry['category']])) {
											$avg = isset($start_values[$entry['vertical']][$entry['category']])?$start_values[$entry['vertical']][$entry['category']]:$end_values[$entry['vertical']][$entry['category']];
										}
										// we have both a beginning and ending value
										else {
											$avg = (($start_values[$entry['vertical']][$entry['category']] + $end_values[$entry['vertical']][$entry['category']]) / 2);
										}
										// Formula: (COGS/period) / (AVG inv value over period)
										$turns = !empty($avg)?round($entry['cogs']/$avg, 2):0;
										// Formula: (1/Inventory Turns) * Period
										$days = $date_start->diff($date_end)->days;
										$aging = $entry['cogs']!=0?round(($avg/$entry['cogs'])*$days, 2):'N/A';
									} ?>
						<tr class="report-row <?= $vertkey; ?> <?= $catkey; ?>">
							<td><?= $entry['vertical']; ?></td>
							<td><?= $entry['category']; ?></td>
							<td>
								<a href="#" class="detail-box" id="<?= $row_key; ?>-turns"><?= $turns; ?></a>
								<div class="pop_open" id="<?= $row_key; ?>-turns-details">
									$<?= number_format($entry['cogs'], 2); ?> (COGS)
									<hr>
									<?php if (!isset($start_values[$entry['vertical']][$entry['category']]) && !isset($end_values[$entry['vertical']][$entry['category']])) {
										echo 'N/A';
									}
									else {
										if (!isset($start_values[$entry['vertical']][$entry['category']]) xor !isset($end_values[$entry['vertical']][$entry['category']])) {
											echo isset($start_values[$entry['vertical']][$entry['category']])?'$'.number_format($start_values[$entry['vertical']][$entry['category']], 2).' (Start Value)':'N/A';
											echo ' + ';
											echo isset($end_values[$entry['vertical']][$entry['category']])?'$'.number_format($end_values[$entry['vertical']][$entry['category']], 2).' (End Value)':'N/A';
										}
										else {
											echo '($'.number_format($start_values[$entry['vertical']][$entry['category']], 2).' (Start Value) + $'.number_format($end_values[$entry['vertical']][$entry['category']], 2).' (End Value)) / 2 (Avg Value)';
										}
									} ?>
								</div>
							</td>
							<td>
								<a href="#" class="detail-box" id="<?= $row_key; ?>-aging"><?= $aging; ?></a>
								<div class="pop_open" id="<?= $row_key; ?>-aging-details">
									<?php if (!isset($start_values[$entry['vertical']][$entry['category']]) && !isset($end_values[$entry['vertical']][$entry['category']])) {
										echo 'N/A';
									}
									else {
										echo $days.' (Days) * (';
										if (!isset($start_values[$entry['vertical']][$entry['category']]) xor !isset($end_values[$entry['vertical']][$entry['category']])) {
											echo isset($start_values[$entry['vertical']][$entry['category']])?'$'.number_format($start_values[$entry['vertical']][$entry['category']], 2).' (Start Value)':'N/A';
											echo ' + ';
											echo isset($end_values[$entry['vertical']][$entry['category']])?'$'.number_format($end_values[$entry['vertical']][$entry['category']], 2).' (End Value)':'N/A';
										}
										else {
											echo '($'.number_format($start_values[$entry['vertical']][$entry['category']], 2).' (Start Value) + $'.number_format($end_values[$entry['vertical']][$entry['category']], 2).' (End Value)) / 2 (Avg Value)';
										}
										echo ')';
									} ?>
									<hr>
									$<?= number_format($entry['cogs'], 2); ?> (COGS)
								</div>
							</td>
						</tr>
								<?php }
							}
						} ?>
					</tbody>
				</table>
				<script>
					jQuery('.filter').change(function() {
						if (jQuery(this).val() == '') {
							jQuery('.report-row').show();
							jQuery('.report-filter').show();
						}
						else {
							jQuery('.report-row').hide();
							jQuery('.report-filter').hide();
							jQuery('.'+jQuery(this).val()).show();
						}
					});

					jQuery('.pop_open').click(function(e) {
						e.stopPropagation();
					});
					var last_clicked_box;
					jQuery('.detail-box').click(function(e) {
						last_clicked_box = '#'+jQuery(this).attr('id')+'-details';

						jQuery(last_clicked_box).show();
						jQuery('body').addClass('viewing-details');

						e.preventDefault();
					});
					jQuery('.viewing-details').live('click', function() {
						jQuery('.pop_open.viewing').hide().removeClass('viewing');
						if (last_clicked_box != undefined) {
							jQuery(last_clicked_box).addClass('viewing');
							last_clicked_box = undefined;
						}
						else jQuery('body').removeClass('viewing-details');
					});
				</script>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
