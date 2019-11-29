<?php
require_once('ipn_editor_top.php');

// grab the history.
/*
 we interpolate the ORDER BY clause directly because there's no danger as it's explicitly set directly above. If we change
 that so that it allows untrusted input, then we'll want to rethink that strategy.
*/
// leaving table alias lest we should add another table to this in the future
$history = prepared_query::fetch("SELECT pscch.*, psccht.*, psccht.name as change_type, pscch.change_user as admin_name, UNIX_TIMESTAMP(pscch.change_date) as tstamp FROM products_stock_control_change_history pscch join products_stock_control_change_history_types psccht on psccht.id = pscch.type_id WHERE pscch.stock_id = ? ORDER BY pscch.change_date DESC, pscch.change_id DESC", cardinality::SET, [$ipn->id()]);

$first_entry = 0;
$last_entry = time();
$last_type = '';
$start_qty = $figured_qty = 0;
$ledger = [];
$confirmations = [];
$tqoh = $ipn->get_inventory('on_hand');
foreach ($history as $entry) {
	if (!in_array($entry['change_type'], ['Quantity Confirmation', 'Quantity Change'])) continue;
	$confirmations[] = $entry['tstamp'];
	if (isset($_GET['confirmation_date'])) {
		if ($_GET['confirmation_date'] == $entry['tstamp']) {
			$first_entry = $entry['tstamp'];
			$start_qty = $figured_qty = $entry['new_value'];
			$ledger = [['timestamp' => $first_entry, 'action' => $entry['change_type'], 'related' => NULL, 'qty' => $start_qty]];
		}
		elseif ($entry['tstamp'] > $_GET['confirmation_date'] && $entry['tstamp'] <= $last_entry) {
			$last_entry = $entry['tstamp'];
			$last_type = $entry['change_type'];
			$tqoh = $entry['new_value'];
		}
	}
	elseif ($entry['tstamp'] > $first_entry) {
		$first_entry = $entry['tstamp'];
		$start_qty = $figured_qty = $entry['new_value'];
		$ledger = [['timestamp' => $first_entry, 'action' => $entry['change_type'], 'related' => NULL, 'qty' => $start_qty]];
	}
}
$confirmations = array_unique($confirmations);
rsort($confirmations);

// catch quantity changes accounted for only in the change history. currently there are none known.
// whether inventory is reduced or increased depends on the old value/new value difference
foreach ($history as $entry) {
	if (!in_array($entry['change_type'], ['New IPN', 'IPN Upload Update', 'Serial # Deleted', 'Move Serial [Loss]', 'Move Serial [Gain]'/*, 'PO Unreceive'*/])) continue;
	if ($entry['tstamp'] > $first_entry && $entry['tstamp'] < $last_entry) {
		$figured_qty += ($entry['new_value'] - $entry['old_value']);
		$ledger[] = [
			'timestamp' => $entry['tstamp'],
			'action' => $entry['change_type'],
			'related' => NULL,
			'qty' => ($entry['new_value'] - $entry['old_value'])
		];
	}
}

// inventory adjustments, conversions, scraps, etc.
if ($adjs = prepared_query::fetch('SELECT a.admin_email_address as admin, ia.serial_id, iat.name as type, ia.old_qty, ia.new_qty, ia.scrap_date, UNIX_TIMESTAMP(ia.scrap_date) as tstamp FROM inventory_adjustment ia JOIN admin a ON ia.admin_id = a.admin_id JOIN inventory_adjustment_type iat ON ia.inventory_adjustment_type_id = iat.id WHERE ia.ipn_id = ? AND ia.scrap_date BETWEEN ? AND ? AND ia.old_qty != ia.new_qty AND ia.scrap_date != ? ORDER BY ia.scrap_date ASC', cardinality::SET, [$ipn->id(), date('Y-m-d H:i:s', $first_entry), date('Y-m-d H:i:s', $last_entry), date('Y-m-d H:i:s', $first_entry)])) {
	foreach ($adjs as $adj) {
		if (!empty($adj['serial_id']) && $adj['new_qty'] > $adj['old_qty']) $qty_change = 1;
		elseif (!empty($adj['serial_id']) && $adj['new_qty'] < $adj['old_qty']) $qty_change = -1;
		else $qty_change = ($adj['new_qty'] - $adj['old_qty']);
		$figured_qty += $qty_change;
		$ledger[] = [
			'timestamp' => $adj['tstamp'],
			'action' => $adj['type'],
			'related' => $adj['admin'].' ['.$adj['old_qty'].' -> '.$adj['new_qty'].']',
			'qty' => $qty_change
		];
	}
}

// sales, which reduce inventory
if ($sales = prepared_query::fetch('SELECT ai.invoice_id, ai.inv_order_id, aii.invoice_item_qty, ai.inv_date, UNIX_TIMESTAMP(ai.inv_date) as tstamp FROM acc_invoices ai JOIN acc_invoice_items aii ON ai.invoice_id = aii.invoice_id WHERE ai.inv_order_id IS NOT NULL AND aii.ipn_id = ? AND ai.inv_date BETWEEN ? AND ? ORDER BY ai.inv_date ASC', cardinality::SET, [$ipn->id(), date('Y-m-d H:i:s', $first_entry), date('Y-m-d H:i:s', $last_entry)])) {
	foreach ($sales as $sale) {
		$figured_qty -= $sale['invoice_item_qty'];
		$ledger[] = [
			'timestamp' => $sale['tstamp'],
			'action' => 'Invoice/Ship [<a href="/admin/invoice.php?oID='.$sale['inv_order_id'].'&amp;invId='.$sale['invoice_id'].'" target="_blank">'.$sale['invoice_id'].'</a>]',
			'related' => 'Order # <a href="/admin/orders_new.php?selected_box=orders&amp;oID='.$sale['inv_order_id'].'&amp;action=edit" target="_blank">'.$sale['inv_order_id'].'</a>',
			'qty' => (-1)*$sale['invoice_item_qty']
		];
	}
}

// rmas, which increase inventory
if ($rmas = prepared_query::fetch('SELECT ai.invoice_id, ai.rma_id, rma.order_id, aii.invoice_item_qty, ai.inv_date, UNIX_TIMESTAMP(ai.inv_date) as tstamp FROM acc_invoices ai JOIN acc_invoice_items aii ON ai.invoice_id = aii.invoice_id JOIN rma ON ai.rma_id = rma.id WHERE ai.rma_id IS NOT NULL AND aii.ipn_id = ? AND ai.inv_date BETWEEN ? AND ? ORDER BY inv_date ASC', cardinality::SET, [$ipn->id(), date('Y-m-d H:i:s', $first_entry), date('Y-m-d H:i:s', $last_entry)])) {
	foreach ($rmas as $rma) {
		// rma invoice qtys are negative, but they should show up on this list as a positive
		$figured_qty -= $rma['invoice_item_qty'];
		$ledger[] = [
			'timestamp' => $rma['tstamp'],
			'action' => 'RMA Invoice [<a href="/admin/invoice.php?oID='.$rma['order_id'].'&amp;invId='.$rma['invoice_id'].'" target="_blank">'.$rma['invoice_id'].'</a>]',
			'related' => 'Order # <a href="/admin/orders_new.php?selected_box=orders&amp;oID='.$rma['order_id'].'&amp;action=edit" target="_blank">'.$rma['order_id'].'</a> | RMA # <a href="rma-detail.php?id='.$rma['rma_id'].'" target="_blank">'.$rma['rma_id'].'</a>',
			'qty' => (-1)*$rma['invoice_item_qty']
		];
	}
}

// other generic invoices that are not sales or rmas... these should not really occur, but we don't want to ignore them just in case
// these will reduce inventory
if ($invoices = prepared_query::fetch('SELECT ai.invoice_id, aii.invoice_item_qty, ai.inv_date, UNIX_TIMESTAMP(ai.inv_date) as tstamp FROM acc_invoices ai JOIN acc_invoice_items aii ON ai.invoice_id = aii.invoice_id WHERE ai.inv_order_id IS NULL AND ai.rma_id IS NULL AND aii.ipn_id = ? AND ai.inv_date BETWEEN ? AND ? ORDER BY ai.inv_date ASC', cardinality::SET, [$ipn->id(), date('Y-m-d H:i:s', $first_entry), date('Y-m-d H:i:s', $last_entry)])) {
	foreach ($invoices as $invoice) {
		$figured_qty -= $invoice['invoice_item_qty'];
		$ledger[] = [
			'timestamp' => $invoice['tstamp'],
			'action' => 'Invoice/Other [<a href="/admin/invoice.php?invId='.$invoice['invoice_id'].'" target="_blank">'.$invoice['invoice_id'].'</a>]',
			'related' => NULL,
			'qty' => (-1)*$invoice['invoice_item_qty']
		];
	}
}

// pos, which increase inventory
if ($pos = prepared_query::fetch('SELECT porp.quantity_received, pors.date, po.id, po.purchase_order_number, UNIX_TIMESTAMP(pors.date) as tstamp FROM purchase_order_received_products porp JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id JOIN purchase_orders po ON pop.purchase_order_id = po.id JOIN purchase_order_receiving_sessions pors ON porp.receiving_session_id = pors.id WHERE pop.ipn_id = ? AND pors.date BETWEEN ? AND ? ORDER BY pors.date ASC', cardinality::SET, [$ipn->id(), date('Y-m-d H:i:s', $first_entry), date('Y-m-d H:i:s', $last_entry)])) {
	foreach ($pos as $po) {
		$figured_qty += $po['quantity_received'];
		$ledger[] = [
			'timestamp' => $po['tstamp'],
			'action' => 'PO Receipt [<a href="/admin/po_viewer.php?selected_box=purchasing&amp;poId='.$po['id'].'" target="_blank">'.$po['purchase_order_number'].'</a>]',
			'related' => NULL,
			'qty' => $po['quantity_received']
		];
	}
}

usort($ledger, function ($a, $b) { if ($a['timestamp'] < $b['timestamp']) { return -1; } elseif ($a['timestamp'] > $b['timestamp']) { return 1; } elseif ($a['qty'] > $b['qty']) { return -1; } elseif ($a['qty'] < $b['qty']) { return 1; } else { return 0; }});
?>
<style>
	#ipn-changes { font-family: Verdana, Arial, sans-serif; font-size:12px; }
	#ipn_ledger form { display:inline; }

	.ipn-changes-table { width:100%; }
	.ipn-changes-table th, .ipn-changes-table td { padding:4px; text-align:left; }
	.ipn-changes-table th a.sort { font-weight:normal; }
	.ipn-changes-table .row-even td, .ipn-changes-table .col-odd { background-color:#ccc; }
	.ipn-changes-table .row-odd td, .ipn-changes-table .col-even { }
	.ipn-changes-table .date-even.row-even td { background-color:#dde; }
	.ipn-changes-table .date-even.row-odd td { background-color:#eef; }
	.ipn-changes-table .date-odd.row-even td { background-color:#aba; }
	.ipn-changes-table .date-odd.row-odd td { background-color:#bcb; }

	.ipn-changes-table.ledger { width:60%; }
	.ipn-changes-table.ledger .action .related { text-align:right; }
	.ipn-changes-table.ledger tfoot th, .ipn-changes-table.ledger tfoot td { text-align:right; background-color:#f1f1f1; }
	.ipn-changes-table.ledger tfoot th { border-top:1px solid #000; }
	.ipn-changes-table.ledger thead th, .ipn-changes-table.ledger thead td, .ipn-changes-table.ledger tbody th, .ipn-changes-table.ledger tbody td { padding:6px 10px; border-right:1px solid #000; }
	.ipn-changes-table.ledger .correct { background-color:#afa; }
	.ipn-changes-table.ledger .incorrect { background-color:#faa; }
</style>
<div id="ipn-changes">
	<div>
		Activity since confirmation on
		<form action="/admin/ipn_editor.php" method="get" id="confirmation-form">
			<input type="hidden" name="ipnId" value="<?= $ipn->get_header('ipn'); ?>">
			<input type="hidden" name="selectedTab" value="5">
			<input type="hidden" name="selectedSubTab" value="ipn-ledger">
			<select name="confirmation_date" size="1" id="confirmation-select">
				<?php foreach ($confirmations as $confirmation) { ?>
				<option value="<?= $confirmation; ?>" <?= $confirmation==$first_entry?'selected="selected"':''; ?>><?= date('m/d/Y H:i:s', $confirmation); ?></option>
				<?php } ?>
			</select>
		</form>
		<script>
			jQuery('#confirmation-select').change(function() {
				jQuery('#confirmation-form').submit();
			});
		</script>
	</div>
	<table cellpadding="0" cellspacing="0" border="0" class="ipn-changes-table ledger">
		<!--colgroup>
			<col class="col-even">
			<col class="col-odd">
			<col class="col-even">
			<col class="col-odd">
		</colgroup-->
		<thead>
			<tr>
				<th>Date</th>
				<th class="action">Action<div class="related"><small>Related To</small></div></th>
				<th>Qty Affected</th>
				<th>End Qty</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>To Date On Hand</th>
				<th>Figured Qty</th>
				<th>Difference</th>
				<th>Status</th>
			</tr>
			<tr>
				<td><?= $tqoh; ?></td>
				<td><?= $figured_qty; ?></td>
				<td><?= $tqoh - $figured_qty; ?></td>
				<td class="<?= $tqoh-$figured_qty==0?'correct':'incorrect'; ?>"></td>
			</tr>
		</tfoot>
		<tbody>
		<?php $dates = [];
		foreach ($ledger as $rowidx => $entry) {
			if ($rowidx == 0) $end_qty = $confirmations?$start_qty:$entry['qty'];
			else $end_qty += $entry['qty'];

			if (!in_array(date('Y-m-d', $entry['timestamp']), $dates)) $dates[] = date('Y-m-d', $entry['timestamp']); ?>
			<tr class="<?= count($dates)%2==0?'date-even':'date-odd'; ?> <?= $rowidx%2==0?'row-even':'row-odd'; ?>">
				<td><?= date('m/d/Y', $entry['timestamp']); ?></td>
				<td class="action"><?= $entry['action']; ?><?php if (!empty($entry['related'])) { echo '<div class="related"><small>'.$entry['related'].'</small></div>'; } ?></td>
				<td><?= $entry['qty']; ?></td>
				<td><?= $end_qty; ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</div>
<script>
</script>
