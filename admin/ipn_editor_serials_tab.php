<?php
require_once('ipn_editor_top.php');

set_time_limit(0);
@ini_set("memory_limit","512M");

debug_tools::show_all();

$days30 = new DateTime();
$days30->setTime(0, 0, 0);
$days30->sub(new DateInterval('P30D'));

$days60 = new DateTime();
$days60->setTime(0, 0, 0);
$days60->sub(new DateInterval('P60D'));

$vendors = prepared_query::fetch('SELECT DISTINCT pop.id as purchase_order_products_id, pop.purchase_order_id, v.vendors_company_name FROM serials s JOIN serials_history sh ON s.id = sh.serial_id JOIN purchase_order_products pop ON sh.pop_id = pop.id JOIN purchase_orders po ON pop.purchase_order_id = po.id JOIN vendors v ON po.vendor = v.vendors_id WHERE s.ipn = :stock_id', cardinality::SET, [':stock_id' => $ipn->id()]);
$pos = [];
foreach ($vendors as $vendor) {
	$pos[$vendor['purchase_order_products_id']] = $vendor;
}

$searched_serial = NULL;
if (!empty($_GET['search_serial'])) {
	$searched_serial = ck_serial::get_serial_by_serial($_GET['search_serial']);
} ?>

<style>
	.aged { /*font-weight:bold;*/ }
	.aged.green { background-color:#0f0; }
	.aged.yellow { background-color:#ee0; }
	.aged.red { background-color:#f33; color:#fff; }
	#serials_list thead tr th {  padding-left:20px; padding-right:20px; }
	#serials_list thead tr th, #serials_list tbody tr td { text-align:center; white-space:nowrap; font-size:.97em; }
	#serials_list tbody tr td .short-notes { max-width:300px; max-height:80px; overflow:auto; white-space:normal; }
	#selection_menu input[type=checkbox], #selection_menu label { cursor:pointer; }
	.serials-status { display:none; }
	.serials-status.in_stock, .serials-status.receiving { display:table-row; }

	#history-modal th, #history-modal td { padding:3px 5px; }
	#history-modal th { border-bottom:1px solid #000; }
	#history-modal .showver { border:1px solid #000; padding:2px; height:140px; width:300px; overflow:auto; }
	#place_hold_modal { position:absolute; }

	table.tablesorter tbody td.serial-reserved { background-color:#777; }
	.serial-reserved a { color:#fff; font-weight:bold; }
</style>

<input type="hidden" id="ipn-serial-ipn" value="<?= $ipn->get_header('ipn'); ?>">
<input type="hidden" id="ipn-serial-stock-id" value="<?= $ipn->id(); ?>">
<input type="hidden" id="ipn-serial-search-serial" value="<?= !empty($searched_serial)?$searched_serial->id():''; ?>">

<div style="clear: both;">
	<div style="font-size: 14px; font-weight: bold;">Serials for <?= $ipn->get_header('ipn'); ?></div>
	<div id="selection_menu" style=" font-size:15px; padding-left:475px;white-space:nowrap;">
		<input id="show_instock" class="status_checkbox" type="checkbox" checked value="in_stock">
		<label for="show_instock">In Stock</label>
		<input id="show_receiving" class="status_checkbox" type="checkbox" checked value="receiving">
		<label for="show_receiving">Receiving</label>
		<input id="show_allocated" class="status_checkbox" type="checkbox" value="allocated">
		<label for="show_allocated">Allocated</label>
		<input id="show_invoiced" class="status_checkbox" type="checkbox" value="invoiced">
		<label for="show_invoiced">Invoiced</label>
		<input id="show_hold" class="status_checkbox" type="checkbox" value="on_hold">
		<label for="show_hold">Hold</label>
		<input id="show_scrapped" class="status_checkbox" type="checkbox" value="scrapped">
		<label for="show_scrapped">Scrapped</label>
		<input id="show_rtv" class="status_checkbox" type="checkbox" value="returned_to_vendor">
		<label for="show_rtv">Returned to Vendor</label>
		<input id="show_merged" class="status_checkbox" type="checkbox" value="merged">
		<label for="show_merged">Merged</label>
	</div>
</div>
<div style="margin-top:20px; margin-bottom: 20px; display:inline; margin-right:20px;">
	<?php if (!empty($searched_serial)) { ?>
	<a href="/admin/ipn_editor.php?ipnId=<?= $ipn->get_header('ipn'); ?>&amp;selectedTab=8"><button type="button">Show All</button></a>
	<?php }
	else { ?>
	<input id="show-all-serials" type="button" value="Show All">
	<?php } ?>
</div>
<input type="button" id="hold_serials" value="Hold Serials">
<div id="serial_search_response" style="display:none"></div>

<div id="serials_table">
	<?php if (empty($searched_serial) && !$ipn->has_serials()) { ?>
	There are no serials for this IPN.
	<?php }
	else {
		if (empty($searched_serial)) $serials = $ipn->get_display_sorted_serials();
		else $serials = [$searched_serial]; ?>
	<table id="serials_list" class="tablesorter">
		<thead>
			<tr>
				<th>Status</th>
				<th>Hold</th>
				<th style="width:10%;">Serial</th>
				<th>Reserved</th>
				<th>Bin</th>
				<th>Confirmed</th>
				<th>Cost</th>
				<th>Condition</th>
				<th>DRAM</th>
				<th>Flash</th>
				<th>IOS</th>
				<th>Mac Addr</th>
				<th>Short Notes</th>
				<th>Sh Ver</th>
				<th>History</th>
				<th style="width:10%;">Received</th>
				<th>Vendor</th>
				<th>PO</th>
				<th>Order</th>
				<!--<?php if ($_SESSION['login_groups_id'] == 1) ?><th class="main noPrint">Actions</th>-->
			</tr>
		</thead>
		<tbody>
		<?php foreach ($serials as $serial) {
			$header = $serial->get_header();
			$history = $serial->get_current_history();
			$reservation = $serial->get_reservation();

			if ($history['entered_date'] > $days30) $serial_age = 'green'; // 30 days
			elseif ($history['entered_date'] > $days60) $serial_age = 'yellow'; // 60 days
			else $serial_age = 'red'; ?>
			<tr id="srl_<?= $serial->id(); ?>_container_row" class="serials-status <?= strtolower(str_replace(' ', '_', $header['status'])); ?>">
				<td style="width:200px;">
					<?= $header['status']; ?>
					<?php if ($header['status_code'] == '6') {
						try {
							$hold = prepared_query::fetch('SELECT ih.id, ihr.description as reason FROM inventory_hold ih JOIN inventory_hold_reason ihr ON ih.reason_id = ihr.id WHERE ih.serial_id = :serial_id', cardinality::ROW, [':serial_id' => $serial->id()]);
							echo '('.$hold['reason'].')';
							if (tep_admin_check_boxes('inventory_hold_list.php')) { ?>
								<input class="remove-hold" type="button" value="Remove Hold" data-hold-id="<?= $hold['id']; ?>"><a href="/admin/inventory_hold_list.php#hold-id-<?= $hold['id']; ?>" style="font-weight:bold;" target="_blank">&#8599;</a>
						<?php }
						}
						catch (Exception $e) {
							echo '(HOLD RECORD MISSING)';
						}
					} ?>
				</td>
				<td style="text-align:center; width:100px;">
					<input type="checkbox" id="put_serial_on_hold" class="serial_hold_checkbox" value="<?= $header['serial_number']; ?>" name="put_serial_on_hold[<?= $serial->id(); ?>]" <?= $header['status_code']!=2?' disabled':''; ?>>
				</td>
				<td class="aged <?= $serial_age; ?>"><?= $header['serial_number']; ?></td>
				<td class="<?= !empty($reservation)?'serial-reserved':''; ?>">
					<?php if (!empty($reservation)) { ?>
					<a href="/orders_new.php?oID=<?= $reservation['order']->id(); ?>&amp;action=edit&amp;selected_box=orders" target="_blank"><?= $reservation['order']->id(); ?></a>
					<?php } ?>
				</td>
				<td style="text-align:center;"><?= $history['bin_location']; ?></td>
				<td><?= !empty($history['confirmation_date'])?$history['confirmation_date']->format('n/d/Y'):'Unconfirmed'; ?></td>
				<td><?= $history['cost']; ?></td>
				<td><?= $history['condition']; ?></td>
				<td><?= $history['dram']; ?></td>
				<td><?= $history['flash']; ?></td>
				<td><?= $history['ios']; ?></td>
				<td><?= $history['mac_address']; ?></td>
				<td><div class="short-notes"><?= $history['short_notes']; ?></div></td>
				<td><a href="#" class="showver-link" data-serial-id="<?= $serial->id(); ?>">Sh Ver</a></td>
				<td><a href="#" class="history-link" data-serial-id="<?= $serial->id(); ?>">History</a></td>
				<td class="aged <?= $serial_age; ?>"><?= !empty($history['entered_date'])?$history['entered_date']->format('n/d/Y h:m A'):'Unknown'; ?></td>
				<td><?= !empty($pos[$history['purchase_order_products_id']])?$pos[$history['purchase_order_products_id']]['vendors_company_name']:''; ?></td>
				<td><?php if (!empty($pos[$history['purchase_order_products_id']])) { ?><a href="/admin/po_viewer.php?poId=<?= $pos[$history['purchase_order_products_id']]['purchase_order_id']; ?>" target="_blank"><?= $history['po_number']; ?></a><?php } ?></td>
				<td>
					<?php if (empty($history['orders_id'])) echo 'N/A';
					else { ?>
					<a href="/admin/orders_new.php?selected_box=orders&page=1&oID=<?= $history['orders_id']; ?>&action=edit" target="_blank"><?= $history['orders_id']; ?></a>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	<?php } ?>
</div>

<div class="jqmWindow" id="hold-modal">
	<a href="#" style="float: right;" class="jqmClose">[Close]</a>
	<div id="ajaxContainer"></div>
</div>

<div class="jqmWindow" id="place_hold_modal" style="width:500px;">
	<a href="#" style="float: right;" class="jqmClose">[Close]</a>
	<div id="ajaxContainer" style="margin:0 auto;"></div>
</div>

<script>
	jQuery(".tablesorter").tablesorter({
		theme: 'blue',
		headers: {
			0: { sorter: false },
			1: { sorter: false },
			10: { sorter: false },
			11: { sorter: false },
			12: { sorter: false }
		}
	});

	jQuery('#show-all-serials').click(function(e) {
		e.preventDefault();
		jQuery('#serial_search_response').hide();
		jQuery('.serials-status').show();
		jQuery('.status_checkbox').attr('checked', true);
	});

	jQuery('.status_checkbox').click(function(e) {
		var selector = jQuery(this).val();

		if (jQuery(this).is(':checked')) jQuery('.serials-status.'+selector).show();
		else jQuery('.serials-status.'+selector).hide();
	});

	jQuery('.remove-hold').click(function(e) {
		e.preventDefault();
		jQuery('#hold-modal').jqm({ ajax:'inventory_hold.php?action=delete&holdId='+jQuery(this).attr('data-hold-id'), target:'#ajaxContainer', modal:true });
		jQuery('#hold-modal').jqmShow();
	});

	jQuery('#hold_serials').click(function(e) {
		e.preventDefault();
		var serials = jQuery('.serial_hold_checkbox:checkbox:checked').serialize();
			jQuery('#place_hold_modal').jqm({ajax:'inventory_hold.php?action=hold_selected_serials&ipn='+jQuery('#ipn-serial-ipn').val()+'&'+serials, target: '#ajaxContainer', modal: true});
			jQuery('#place_hold_modal').jqmShow();
	});

	var showversion_modal = new ck.modal({
		modal_id: 'showversion-modal',
		header: 'Show Version',
		height: 'auto',
		width: '600px',
		top: '10px',
		sticky: true,
	});
	showversion_modal.add_link(jQuery('.showver-link'));
	jQuery('.showver-link').on('modal:open', function(e) {
		e.preventDefault();
		var serial_id = jQuery(this).attr('data-serial-id');

		jQuery.ajax({
			url: '/admin/ipn_editor.php',
			method: 'get',
			dataType: 'json',
			data: 'ajax=1&action=get_serial_showver&serial_id='+serial_id,
			success: function(data) {
				//console.log(data.show_version);
				//console.log(jQuery('#showversion-modal'));
				jQuery('#showversion-modal .modal-body').html(data.show_version);
			}
		});
	});
	var screen_limit = parseInt(window.screen.height*.8);
	ck.modal.styleset.add_style('#showversion-modal', 'max-height', screen_limit+'px');
	ck.modal.styleset.add_style('#showversion-modal .modal-body', 'max-height', (screen_limit-41)+'px');
	ck.modal.styleset.render();

	var history_modal = new ck.modal({
		modal_id: 'history-modal',
		header: 'Serial History',
		height: 'auto',
		width: '1000px',
		top: '10px',
		sticky: true,
	});
	history_modal.add_link(jQuery('.history-link'));
	jQuery('.history-link').on('modal:open', function(e) {
		e.preventDefault();
		var serial_id = jQuery(this).attr('data-serial-id');

		jQuery.ajax({
			url: '/admin/ipn_editor.php',
			method: 'get',
			dataType: 'json',
			data: 'ajax=1&action=get_serial_history&serial_id='+serial_id,
			success: function(data) {
				if (data.records) {
					$table = jQuery('<table cellpadding="0" cellspacing="0" border="0"><thead><tr><th>Cost</th><th>Received Date</th><th>PO Number</th><th>Order #</th><th>Invoice Date</th><th>Condition</th><th>DRAM</th><th>Flash</th><th>IOS</th><th>Show Version</th><th>Short Notes</th></thead></table>');

					$tbody = jQuery('<tbody></tbody>');

					for (var i=0; i<data.records.length; i++) {
						$tbody.append('<tr><td>'+data.records[i].cost+'</td><td>'+data.records[i].entered_date+'</td><td>'+data.records[i].po_number+'</td><td>'+data.records[i].orders_id+'</td><td>'+data.records[i].shipped_date+'</td><td>'+data.records[i].condition+'</td><td>'+data.records[i].dram+'</td><td>'+data.records[i].flash+'</td><td>'+data.records[i].ios+'</td><td><div class="showver">'+data.records[i].show_version+'</div></td><td>'+data.records[i].short_notes+'</td></tr>');
					}

					$table.append($tbody);

					jQuery('#history-modal .modal-body').html($table);
				}
			}
		});
	});
	ck.modal.styleset.add_style('#history-modal', 'max-height', screen_limit+'px');
	ck.modal.styleset.add_style('#history-modal .modal-body', 'max-height', (screen_limit-41)+'px');
	ck.modal.styleset.render();

	if (jQuery('#ipn-serial-search-serial').val() != '') {
		jQuery('.status_checkbox').attr('checked', false);
		jQuery('.serials-status').hide();
		jQuery('#srl_'+jQuery('#ipn-serial-search-serial').val()+'_container_row').show();
	}
</script>
