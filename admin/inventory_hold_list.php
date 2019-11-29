<?php
require('includes/application_top.php');

if (isset($_POST['bulk_action'])) {
	switch ($_POST['bulk_action']) {
		case 'Delete Checked Holds':
			foreach ($_POST['bulk'] as $hold_id) {
				if ($serial_id = prepared_query::fetch('SELECT serial_id FROM inventory_hold WHERE id = :hold_id', cardinality::SINGLE, [':hold_id' => $hold_id])) {
					$in_stock = prepared_query::fetch("SELECT id FROM serials_status WHERE name = 'In Stock'", cardinality::SINGLE);

					//MMD - D-38 - only update status if the current status is "hold"
					$result = prepared_query::execute('UPDATE serials SET status = :in_stock WHERE id = :serial_id AND status = 6', [':serial_id' => $serial_id, ':in_stock' => $in_stock]);
				}
				$delete_query = prepared_query::execute("DELETE FROM inventory_hold WHERE id = :hold_id", [':hold_id' => $hold_id]);
			}
			break;
		default:
			echo 'fell to default';
			break;
	}
	CK\fn::redirect_and_exit($_SERVER['REQUEST_URI']);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<style>
		.main {font-size:10px;}
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
			<td width="100%" valign="top" class="main">
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						$("#all-holds").tablesorter({
							headers: {
								0: { sorter: false },
								11: { sorter: "digit"},
								14: { sorter: false }

							}
						});

						$('#new').click(function() {
							$('#modal').jqm({ajax: 'inventory_hold.php', target: '#ajaxContainer', modal: true});
							$('#modal').jqmShow();
						});

						$('.edit').each(function(i) {
							$(this).bind('click', {index:i}, function(e) {
								$('#modal').jqm({ajax: 'inventory_hold.php?action=edit&holdId=' + $('#holdId-' + e.data.index).val(), target: '#ajaxContainer', modal: true});
								$('#modal').jqmShow();
							});
						});

						$('.delete').each(function(i) {
							$(this).bind('click', {index:i}, function(e) {
								$('#modal').jqm({ajax: 'inventory_hold.php?action=delete&holdId=' + $('#holdId-' + e.data.index).val(), target: '#ajaxContainer', modal: true});
								$('#modal').jqmShow();
							});
						});
						$('.scrap').each(function(i) {
							$(this).bind('click', {index:i}, function(e) {
								$('#modal').jqm({ajax: 'inventory_hold.php?action=scrap&holdId=' + $('#holdId-' + e.data.index).val(), target: '#ajaxContainer', modal: true});
								$('#modal').jqmShow();
							});
						});
						$('.return').each(function(i) {
							$(this).bind('click', {index:i}, function(e) {
								$('#modal').jqm({ajax: 'inventory_hold.php?action=return&holdId=' + $('#holdId-' + e.data.index).val(), target: '#ajaxContainer', modal: true});
								$('#modal').jqmShow();
							});
						});
						$('#cancel').click(function() {
							$('#modal').jqmHide();
						});
						$('#bulk_remove').click(function(e) {
							var holds = '';
							jQuery('.bulk-action-element').each(function() {
								if (!jQuery(this).is(':checked')) return;
								holds += '&hold_ids[]='+jQuery(this).val();
							});
							$('#modal').jqm({ ajax: 'inventory_hold.php?action=bulk_scrap&'+holds, target: '#ajaxContainer', modal: true });
							$('#modal').jqmShow();
						});
					});
				</script>
				<div style="width:90%; border:0px solid #333333; margin:5px; height:800px; padding:10px;">
					<div class="pageHeading" style="padding-bottom:20px;">Inventory Holds ("Quarantine")</div>
					<div>
						<table cellspacing="5px" cellpadding="5px" border="0">
							<tr>
								<td class="main" colspan="6">
									<input id="new" type="button" value="New Inventory Hold">
								</td>
							</tr>
							<tr>
								<td class="main" colspan="6">
									<form method="GET" action="inventory_hold_list.php?<?= tep_get_all_get_params(); ?>">
										Filter By PO#:
										<input type="text" name="po_number" size="10" value="<?php echo (isset($_REQUEST['po_number']) ? $_REQUEST['po_number'] : '');?>"/>
										<input type="submit" value="Filter" />
									</form>
								</td>
							</tr>
						</table>
					</div>
					<div class="jqmWindow" id="modal">
						<a href="#" style="float: right;" id="cancel">Close</a>
						<div id="ajaxContainer"></div>
					</div>
					<form method="post" action="inventory_hold_list.php?<?php echo tep_get_all_get_params();?>" id="hold-list">
						<div>
							<input id="bulk_delete" type="submit" name="bulk_action" value="Delete Checked Holds" />
							<input id="bulk_remove" type="button" name="bulk_action" value="Remove Checked Inventory" style="margin-left:80px;">
						</div>
						<div>
							<br>Filter Hold Reason:
							<select id="filter-reason">
								<option value="ALL">ALL</option>
								<?php $reasons = ck_hold_reason_lookup::instance()->get_list('active');
								foreach ($reasons as $reason) { ?>
								<option value="<?= $reason['hold_reason_id']; ?>"><?= $reason['reason']; ?></option>
								<?php } ?>
							</select>
							<script>
								jQuery('#filter-reason').change(function() {
									if (jQuery(this).val() == 'ALL') {
										jQuery('.hold-row').show();
									}
									else {
										jQuery('.hold-row').hide();
										jQuery('.reason-'+jQuery(this).val()).show();
									}
								});
							</script>
							<style>
								#all-holds thead tr th { padding-left:20px; padding-right:20px; }
								#all-holds { text-align:center; }

								table.tablesorter tbody tr td.serial-reserved { background-color:#777; }
								.serial-reserved a { color:#fff; font-weight:bold; }
							</style>
							<table id="all-holds" class="tablesorter">
								<thead>
									<tr>
										<th>
											<?php if (!empty($_REQUEST['po_number'])) { ?>
											<input type="checkbox" onclick="if(this.checked) { jQuery('.bulkCheckbox').attr('checked', 'checked'); }else { jQuery('.bulkCheckbox').attr('checked', ''); }"/>
											<?php } ?>
										</th>
										<th class="header">Hold Id</th>
										<th class="header">IPN</th>
										<th class="header">Qty</th>
										<th class="header">Qty Avail.</th>
										<th class="header">Serial</th>
										<th class="header">PO</th>
										<th class="header">PO Administrator</th>
										<th class="header">Bins</th>
										<th class="header">Hold Creator</th>
										<th class="header">Date</th>
										<th class="header">Days On Hold</th>
										<th class="header">Reason</th>
										<th class="header">Reserved</th>
										<th>Notes</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?php if (!empty($_REQUEST['po_number'])) {
										$holds = prepared_query::fetch("SELECT ih.*, ihr.description, s.id as serial_id, s.serial, CONCAT(a.admin_firstname, ' ', a.admin_lastname) as creator, sh.po_number, sh.bin_location AS serial_bin, CONCAT(aa.admin_firstname, ' ', aa.admin_lastname) AS po_administrator FROM inventory_hold ih JOIN inventory_hold_reason ihr ON ih.reason_id = ihr.id LEFT JOIN serials s ON s.id = ih.serial_id LEFT JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh2 ON sh.serial_id = sh2.serial_id AND sh.id < sh2.id LEFT JOIN purchase_orders po ON po.purchase_order_number = sh.po_number LEFT JOIN admin a ON ih.creator_id = a.admin_id LEFT JOIN admin aa ON aa.admin_id = po.administrator_admin_id WHERE sh2.id IS NULL AND s.id IN (SELECT DISTINCT sh.serial_id FROM serials_history sh WHERE sh.po_number = ?) ORDER BY ih.date DESC", cardinality::SET, $_REQUEST['po_number']);
									}
									else {
										$holds = prepared_query::fetch("SELECT ih.*, ihr.description, s.id as serial_id, s.serial, CONCAT(a.admin_firstname, ' ', a.admin_lastname) as creator, sh.po_number, sh.bin_location AS serial_bin, CONCAT(aa.admin_firstname, ' ', aa.admin_lastname) AS po_administrator FROM inventory_hold ih JOIN inventory_hold_reason ihr ON ih.reason_id = ihr.id LEFT JOIN serials s ON s.id = ih.serial_id LEFT JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id LEFT JOIN purchase_orders po ON po.purchase_order_number = sh.po_number LEFT JOIN admin a ON ih.creator_id = a.admin_id LEFT JOIN admin aa ON aa.admin_id = po.administrator_admin_id WHERE sh0.id IS NULL ORDER BY ih.date DESC", cardinality::SET);
									}

									foreach ($holds as $count => $hold) {
										$ipn = new ck_ipn2($hold['stock_id']); ?>
									<tr class="<?= $count%2!=0?'odd':''; ?> hold-row reason-<?= $hold['reason_id']; ?>" id="hold-id-<?= $hold['id']; ?>">
										<td><input type="checkbox" class="bulk-action-element" name="bulk[]" value="<?= $hold['id']; ?>" class="bulkCheckbox"></td>
										<td class="numeric" style="padding-right:5px;"><a href="inventory_hold.php?poId=<?= $hold['id']; ?>"><?= $hold['id']; ?></a></td>
										<td><a href="/admin/ipn_editor.php?ipnId=<?= $ipn->get_header('ipn'); ?>" target="_blank"><?= $ipn->get_header('ipn'); ?></a></td>
										<td><?= $hold['quantity']; ?></td>
										<td><?= !empty($ipn->get_inventory('available'))?$ipn->get_inventory('available'):0; ?></td>
										<td><?= $hold['serial']; ?></td>
										<td><?= $hold['po_number']; ?></td>
										<td><?= $hold['po_administrator']; ?></td>
										<td style="text-align:center;">
											<?php if (!empty($hold['serial_bin'])) echo $hold['serial_bin'] .'<hr> -';
											else echo (empty($ipn->get_header('bin1'))?'-':$ipn->get_header('bin1')).'<hr>'.(empty($ipn->get_header('bin2'))?'-':$ipn->get_header('bin2')); ?>
										</td>
										<td><?= $hold['creator']; ?></td>
										<td><?= date('m/d/Y g:i A', strtotime($hold['date'])); ?></td>
										<td>
											<?php
												$hold_date = new DateTime($hold['date']);
												$todays_date = new DateTime();

												echo $todays_date->diff($hold_date)->format('%a');
											?>
										</td>
										<td><?= $hold['description']; ?></td>
										<?php $serial_reservation = NULL;
										if (!empty($hold['serial_id']) && ($serial = new ck_serial($hold['serial_id'])) && $serial->has_reservation()) $serial_reservation = $serial->get_reservation(); ?>
										<td class="<?= !empty($serial_reservation)?'serial-reserved':''; ?>">
											<?php if (!empty($serial_reservation)) { ?>
											<a href="/orders_new.php?oID=<?= $serial_reservation['order']->id(); ?>&amp;action=edit&amp;selected_box=orders" target="_blank"><?= $serial_reservation['order']->id(); ?></a>
											<?php } ?>
										</td>
										<td><?= $hold['notes']; ?></td>
										<td width="300">
											<input class="edit" type="button" value="Edit">
											&nbsp;
											<input class="delete" type="button" value="Delete">
											<?php // top admin, eric & cliff
											if (in_array($_SESSION['perms']['admin_groups_id'], [1, 13, 17, 20, 22, 30, 31])) { ?>
											<input class="scrap" type="button" value="Remove Inventory">
											<?php } ?>
											<input id="holdId-<?= $count; ?>" name="holdId" type="hidden" value="<?= $hold['id']; ?>">
										</td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</form>
				</div>
				<!-- ----------------------------------------------------------------- -->
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
