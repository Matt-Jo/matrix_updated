<?php
require('includes/application_top.php');

if (!empty($_POST['action'])) {
	switch ($_POST['action']) {
		case 'change_adjustment_reason':
			prepared_query::execute('UPDATE inventory_adjustment SET inventory_adjustment_reason_id = :inventory_adjustment_reason_id WHERE id = :inventory_adjustment_id', [':inventory_adjustment_reason_id' => $_POST['inventory_adjustment_reason_id'], ':inventory_adjustment_id' => $_POST['inventory_adjustment_id']]);

			unset($_POST['inventory_adjustment_reason_id']);
			unset($_POST['inventory_adjustment_id']);
			unset($_POST['action']);

			CK\fn::redirect_and_exit('/admin/inventory_adjustment_report.php?'.http_build_query($_POST));
			break;
	}
}

if (isset($_GET['start_date'])) $start_date = new DateTime($_GET['start_date']);
else $start_date = new DateTime('first day of this month'); //date('Y-m-01');

if (isset($_GET['end_date'])) {
	$end_date = new DateTime($_GET['end_date']);
	$end_date->add(new DateInterval('P1D'));
}
else $end_date = new DateTime('tomorrow'); //date('Y-m-d', time() + (24*60*60));

$reason = !empty($_GET['reason'])&&is_numeric($_GET['reason'])&&$_GET['reason']>0?$_GET['reason']:NULL;
$type = !empty($_GET['type'])&&is_numeric($_GET['type'])&&$_GET['type']>0?$_GET['type']:NULL;

$change_result = prepared_query::fetch('SELECT ia.id as inventory_adjustment_id, DATE(ia.scrap_date) as scrap_date, ia.notes, ia.old_qty, ia.new_qty, ia.old_avg_cost, ia.new_avg_cost, iat.name as adj_type, CONCAT(a.admin_firstname, \' \',a.admin_lastname) as admin, psc.stock_name, s.serial, iar.description as iar_description, ia.cost as cost, ia.inventory_adjustment_group_id, ia.inventory_adjustment_reason_id FROM inventory_adjustment ia JOIN admin a ON ia.admin_id = a.admin_id JOIN products_stock_control psc ON ia.ipn_id = psc.stock_id JOIN inventory_adjustment_reason iar ON ia.inventory_adjustment_reason_id = iar.id JOIN inventory_adjustment_type iat ON ia.inventory_adjustment_type_id = iat.id LEFT JOIN serials s ON s.id = ia.serial_id LEFT JOIN serials_history sh ON s.id = sh.serial_id WHERE ia.scrap_date > :start_date AND ia.scrap_date < :end_date AND (:inventory_adjustment_reason_id IS NULL OR ia.inventory_adjustment_reason_id = :inventory_adjustment_reason_id) AND (:inventory_adjustment_type_id IS NULL OR ia.inventory_adjustment_type_id = :inventory_adjustment_type_id) GROUP BY ia.id ORDER BY ia.scrap_date ASC, CASE WHEN ia.inventory_adjustment_type_id = 6 THEN 1 WHEN ia.inventory_adjustment_type_id = 5 THEN 2 WHEN ia.inventory_adjustment_type_id = 7 THEN 3 ELSE 0 END ASC, psc.stock_name ASC, ia.id', cardinality::SET, [':start_date' => $start_date->format('Y-m-d'), ':end_date' => $end_date->format('Y-m-d'), ':inventory_adjustment_reason_id' => $reason, ':inventory_adjustment_type_id' => $type]);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script language="javascript" src="/includes/javascript/prototype.js"></script>
	<script language="javascript"	src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
	<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
	<script type="text/javascript" src="/includes/javascript/jquery-1.4.2.min.js"></script>

	<script type="text/javascript" src="/includes/javascript/jquery-ui-1.8.custom.min.js"></script>
	<script type="text/javascript" src="/includes/javascript/jquery.tablesorter.min.js"></script>
	<script type="text/javascript" src="/includes/javascript/jquery.tablesorter.pager.js"></script>
	<script type="text/javascript" src="/includes/javascript/jquery.qtip-1.0.min.js"></script>
	<script type="text/javascript" src="/admin/includes/javascript/jqModal.js"></script>

	<script type="text/javascript">
			jQuery.noConflict();
		jQuery(document).ready(function($) {
				$('#start_date').datepicker({ dateFormat: 'yy-mm-dd' });
				$('#end_date').datepicker({ dateFormat: 'yy-mm-dd' });
			});
	</script>
</head>

<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
				</table>
			</td>

			<!-- body_text //-->
			<td width="100%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="dataTableContent">Inventory Adjustment Report</td>
									<td class="dataTableContent" align="right">
										<form method="get" action="/admin/inventory_adjustment_report.php" name="date_range">
											<input type="hidden" name="sort" value="<?= @$sort; ?>">
											<input type="hidden" name="direction" value="<?= @$direction; ?>">
											Adjustment Reason:
											<select name="reason">
												<option value ="">Please Select</option>
												<?php foreach (ck_adjustment_reason_lookup::instance()->get_list() as $reason) { ?>
												<option value="<?= $reason['adjustment_reason_id']; ?>" <?= !empty($_GET['reason'])&&$_GET['reason']==$reason['adjustment_reason_id']?'selected':''; ?>><?= $reason['system_only']?'[SYSTEM] ':''; ?><?= $reason['reason']; ?><?= !$reason['active']?' [INACTIVE]':''; ?></option>
												<?php } ?>
											</select>
											Adjustment Type:
											<select name="type">
												<option value ="">Please Select</option>
												<?php foreach (ck_adjustment_type_lookup::instance()->get_list() as $type) { ?>
												<option value="<?= $type['adjustment_type_id']; ?>" <?= !empty($_GET['type'])&&$_GET['type']==$type['adjustment_type_id']?'selected':''; ?>><?= $type['system_only']?'[SYSTEM] ':''; ?><?= $type['type']; ?><?= !$type['active']?' [INACTIVE]':''; ?></option>
												<?php } ?>
											</select>
											Start: <input type="text" value="<?= $start_date->format('Y-m-d'); ?>" name="start_date" id="start_date">
											End: <input type="text" value="<?= $end_date->sub(new DateInterval('P1D'))->format('Y-m-d'); ?>" name="end_date" id="end_date">
											<input type="submit" value="Filter">
										</form>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
								<tr>
									<td><b>Negative Adjustments</b></td>
								</tr>
								<tr>
									<td valign="top">
										<style>
											.adjustment-row td { white-space:nowrap; padding:3px 5px; }
											.adjustment-row td.longform { white-space:normal; }
											.adjustment-row.focused td { background-color:#cfc !important; }

											a.original-adjustment-reason:link { text-decoration:underline; }

											.original-adjustment-reason.hide { display:none; }
											.change-adjustment-reason.hide { display:none; }
										</style>
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<tr class="dataTableHeadingRow">
												<td class="dataTableHeadingContent">Date</td>
												<td class="dataTableHeadingContent">IPN</td>
												<td class="dataTableHeadingContent">Qty</td>
												<td class="dataTableHeadingContent">Serial#</td>
												<td class="dataTableHeadingContent">Cost</td>
												<td class="dataTableHeadingContent">Ext Cost</td>
												<td class="dataTableHeadingContent">User</td>
												<td class="dataTableHeadingContent">Adjustment Type</td>
												<td class="dataTableHeadingContent">Reason</td>
												<td class="dataTableHeadingContent">Notes</td>
											</tr>
											<?php
											$total_ext_cost = 0;
											$cnt = 0;

											foreach ($change_result as $row) {
												if (!in_array($row['adj_type'], ['Positive Inventory Adjustment', 'Positive Conversion Adjustment', 'Negative Conversion Adjustment', 'Rounding Conversion Adjustment'])) {
													$ext_cost = ($row['cost'] *( $row['old_qty'] - $row['new_qty'])); ?>
											<tr class="adjustment-row iar-row-<?= $row['inventory_adjustment_id']; ?>" bgcolor="<?php echo ((++$cnt)%2==0) ? '#e0e0e0' : '#ffffff' ?>">
												<td class="dataTableContent"><?= $row['scrap_date']; ?></td>
												<td class="dataTableContent"><?= $row['stock_name']; ?></td>
												<td class="dataTableContent"><?php echo ($row['old_qty'] - $row['new_qty']); ?></td>
												<td class="dataTableContent"><?= $row['serial']; ?></td>
												<td class="dataTableContent" align="right">
													<?php if ($row['cost'] > 0.01) echo '<font color="#CB2026">(';
													echo number_format($row['cost'], 2);
													if ($row['cost'] > 0.01) echo ')</font>'; ?>
												</td>
												<td class="dataTableContent" align="right" style="padding-right:20px;">
													<?php if ($ext_cost > 0.00) echo '<font color="#CB2026">(';
													echo number_format($ext_cost, 2);
													if ($ext_cost > 0.00) echo ')</font>'; ?>
												</td>
												<td class="dataTableContent"><?= $row['admin']; ?></td>
												<td class="dataTableContent"><?= $row['adj_type']; ?></td>
												<td class="dataTableContent">
													<?php $iar = ck_adjustment_reason_lookup::instance()->lookup_by_id($row['inventory_adjustment_reason_id']);
													if ($iar['system_only']) echo $row['iar_description'];
													else { ?>
													<a href="#" class="original-adjustment-reason" data-inventory-adjustment-id="<?= $row['inventory_adjustment_id']; ?>"><?= $row['iar_description']; ?></a>
													<div class="change-adjustment-reason hide change-iar-<?= $row['inventory_adjustment_id']; ?>">
														<form action="/admin/inventory_adjustment_report.php" method="post">
															<input type="hidden" name="inventory_adjustment_id" value="<?= $row['inventory_adjustment_id']; ?>">
															<input type="hidden" name="action" value="change_adjustment_reason">
															<?php foreach ($_GET as $key => $val) { ?>
															<input type="hidden" name="<?= $key; ?>" value="<?= $val; ?>">
															<?php } ?>
															<select name="inventory_adjustment_reason_id">
																<?php foreach (ck_adjustment_reason_lookup::instance()->get_list() as $reason) {
																	if ($reason['system_only'] || !$reason['active']) continue; ?>
																<option value="<?= $reason['adjustment_reason_id']; ?>" <?= $row['inventory_adjustment_reason_id']==$reason['adjustment_reason_id']?'selected':''; ?>><?= $reason['reason']; ?></option>
																<?php } ?>
															</select>
															<button type="submit">Change</button>
														</form>
													</div>
													<?php } ?>
												</td>
												<td class="dataTableContent longform"><?= $row['notes']; ?></td>
											</tr>
													<?php $total_ext_cost += $ext_cost;
												}
											} ?>
											<tr>
												<td class="main" align="right" colspan="6" style="padding-right:20px;">
													<b>Total Amount Adjusted:
													<?php if ($total_ext_cost > 0.00) echo '<font color="#CB2026">('.CK\text::monetize($total_ext_cost).')</font>';
													else echo CK\text::monetize($total_ext_cost); ?></b>
												</td>
												<td colspan="2"></td>
											</tr>
										</table>
										<script>
											jQuery('.original-adjustment-reason').click(function(e) {
												e.preventDefault();
												e.stopPropagation();

												let iar_id = jQuery(this).attr('data-inventory-adjustment-id');

												jQuery('.iar-row-'+iar_id).addClass('focused');

												let self = this;

												setTimeout(function() {
													if (confirm('Are you sure you want to change the inventory adjustment reason for this row?')) {
														jQuery('.change-iar-'+iar_id).removeClass('hide');
														jQuery(self).addClass('hide');
													}

													jQuery('.iar-row-'+iar_id).removeClass('focused');
												}, 20);
											});
											jQuery('.change-adjustment-reason').click(function(e) {
												e.stopPropagation();
											});
											jQuery('body').click(function(e) {
												jQuery('.change-adjustment-reason').addClass('hide');
												jQuery('.original-adjustment-reason').removeClass('hide');
											});
										</script>
									</td>
								</tr>
								<tr>
									<td>
										<b>Positive Adjustments</b>
									</td>
								</tr>
								<tr>
									<td>
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<tr class="dataTableHeadingRow">
												<td class="dataTableHeadingContent">Date</td>
												<td class="dataTableHeadingContent">IPN</td>
												<td class="dataTableHeadingContent">Old Qty</td>
												<td class="dataTableHeadingContent">New Qty</td>
												<td class="dataTableHeadingContent">Qty</td>
												<td class="dataTableHeadingContent">Old Avg Cost</td>
												<td class="dataTableHeadingContent">New Avg Cost</td>
												<td class="dataTableHeadingContent">Serial#</td>
												<td class="dataTableHeadingContent" align="right" width="70" style="padding-right:20px;">Adjustment</td>
												<td class="dataTableHeadingContent">User</td>
												<td class="dataTableHeadingContent">Adjustment Type</td>
												<td class="dataTableHeadingContent">Reason</td>
												<td class="dataTableHeadingContent">Notes</td>
											</tr>
											<?php $total_ext_cost = 0;

											foreach ($change_result as $row) {
												if ($row['adj_type'] == 'Positive Inventory Adjustment') {
													$ext_cost = ($row['new_avg_cost'] * $row['new_qty']) - ($row['old_avg_cost'] * $row['old_qty']); ?>
											<tr bgcolor="<?php echo ((++$cnt)%2==0) ? '#e0e0e0' : '#ffffff' ?>">
												<td class="dataTableContent"><?= $row['scrap_date']; ?></td>
												<td class="dataTableContent"><?= $row['stock_name']; ?></td>
												<td class="dataTableContent"><?= $row['old_qty']; ?></td>
												<td class="dataTableContent"><?= $row['new_qty']; ?></td>
												<td class="dataTableContent"><?php echo ($row['new_qty'] - $row['old_qty']); ?></td>
												<td class="dataTableContent"><?= $row['old_avg_cost']; ?></td>
												<td class="dataTableContent"><?= $row['new_avg_cost']; ?></td>
												<td class="dataTableContent"><?= $row['serial']; ?></td>
												<td class="dataTableContent" align="right" style="padding-right:20px;">
													<?php if ($ext_cost < 0.00) echo '<font color="#CB2026">('.number_format(($ext_cost * -1), 2).')</font>';
													else echo number_format($ext_cost, 2); ?>
												</td>
												<td class="dataTableContent"><?= $row['admin']; ?></td>
												<td class="dataTableContent"><?= $row['adj_type']; ?></td>
												<td class="dataTableContent"><?= $row['iar_description']; ?></td>
												<td class="dataTableContent"><?= $row['notes']; ?></td>
											</tr>
													<?php $total_ext_cost += ($ext_cost);
												}
											} ?>
											<tr>
												<td class="main" align="right" colspan="5" style="padding-right:20px;">
													<b>Total Amount Adjusted:
													<?php if ($total_ext_cost < 0.00) echo '<font color="#CB2026">('.CK\text::monetize($total_ext_cost * -1).')</font>';
													else echo CK\text::monetize($total_ext_cost); ?></b>
												</td>
												<td colspan="2"></td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td>
										<b>Conversion Adjustments</b>
									</td>
								</tr>
								<tr>
									<td>
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<tr class="dataTableHeadingRow">
												<td class="dataTableHeadingContent">Date</td>
												<td class="dataTableHeadingContent">IPN</td>
												<td class="dataTableHeadingContent">Serial#</td>
												<td class="dataTableHeadingContent">Old Qty</td>
												<td class="dataTableHeadingContent">New Qty</td>
												<td class="dataTableHeadingContent">Qty&plusmn;</td>
												<td class="dataTableHeadingContent">Unit Cost</td>
												<td class="dataTableHeadingContent" align="right" width="70" style="padding-right:20px;">Adjustment</td>
												<td class="dataTableHeadingContent">User</td>
												<td class="dataTableHeadingContent">Adjustment Type</td>
												<td class="dataTableHeadingContent">Reason</td>
												<td class="dataTableHeadingContent">Notes</td>
											</tr>
											<?php
											$conversions = [];
											$total_round_adjustment = 0;
											foreach ($change_result as $row) {
												if (in_array($row['adj_type'], array('Positive Conversion Adjustment', 'Negative Conversion Adjustment', 'Rounding Conversion Adjustment'))) {
													if (isset($conversions[$row['inventory_adjustment_group_id']])) {
														$conversions[$row['inventory_adjustment_group_id']][] = $row;
													}
													else {
														$conversions[$row['inventory_adjustment_group_id']] = array($row);
													}
													if ($row['adj_type'] == 'Rounding Conversion Adjustment') $total_round_adjustment += $row['cost'];
												}
											}
											$total_round_adjustment = $total_round_adjustment>=0?money_format('%n', $total_round_adjustment):'<span style="color:#cb2026;">('.money_format('%n', -1*$total_round_adjustment).')</span>';
											$row_group = 0;
											if (!empty($conversions)) {
												foreach ($conversions as $adjustment_group => $conversion) {
													foreach ($conversion as $idx => $row) {
														$bgcolor = $row_group%2==0?($idx%2==0?'#ffa':'#ff7'):($idx%2==0?'#ddf':'#aaf');
														$qtychng = $row['new_qty'] - $row['old_qty'];
														if ($row['adj_type'] == 'Rounding Conversion Adjustment') {
															$adjustment = $row['cost'];
														}
														else {
															$adjustment = $row['cost'] * $qtychng;
														}
														$adjustment = $adjustment>=0?number_format($adjustment, 2):'<span style="color:#cb2026;">('.number_format(-1*$adjustment, 2).')</span>';
														?>
											<tr bgcolor="<?= $bgcolor; ?>">
												<td class="dataTableContent"><?= $row['scrap_date']; ?></td>
												<td class="dataTableContent"><?= $row['stock_name']; ?></td>
												<td class="dataTableContent"><?= $row['serial']; ?></td>
												<td class="dataTableContent"><?= $row['old_qty']; ?></td>
												<td class="dataTableContent"><?= $row['new_qty']; ?></td>
												<td class="dataTableContent"><?php echo $qtychng>0?"+$qtychng":$qtychng; ?></td>
												<td class="dataTableContent"><?= $row['cost']; ?></td>
												<td class="dataTableContent" align="right" style="padding-right:20px;"><?= $adjustment; ?></td>
												<td class="dataTableContent"><?= $row['admin']; ?></td>
												<td class="dataTableContent"><?= $row['adj_type']; ?></td>
												<td class="dataTableContent"><?= $row['iar_description']; ?></td>
												<td class="dataTableContent"><?= $row['notes']; ?></td>
											</tr>
													<?php }
													$row_group++;
												}
											}
											else { ?>
												<tr>
													<td class="dataTableContent" style="text-align:center;font-weight:bold;" colspan="12">NO CONVERSION RECORDS FOUND</td>
												</tr>
											<?php } ?>
											<tr>
												<td class="main" align="right" colspan="5" style="padding-right:20px;">
													<strong>Total Rounding Adjustment: <?= $total_round_adjustment; ?></strong>
												</td>
												<td colspan="7"> </td>
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
