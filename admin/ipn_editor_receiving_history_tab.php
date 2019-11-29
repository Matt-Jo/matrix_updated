<?php
require_once('ipn_editor_top.php');

$receipts = prepared_query::fetch('SELECT porp.quantity_received, pop.cost, pors.date, v.vendors_company_name, po.id, po.purchase_order_number FROM purchase_order_received_products porp JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id JOIN purchase_orders po ON pop.purchase_order_id = po.id LEFT JOIN purchase_order_receiving_sessions pors ON porp.receiving_session_id = pors.id LEFT JOIN vendors v ON po.vendor = v.vendors_id WHERE pop.ipn_id = :stock_id ORDER BY pors.date DESC', cardinality::SET, [':stock_id' => $ipn->id()]); ?>
<table cellspacing="0" cellpadding="6px" style="border: 1px solid black" width="100%" id="showtr" name="showtr">
	<tr>
		<td class="main" nowrap><b>Vendor</b></td>
		<td class="main" nowrap><b>Date</b></td>
		<td class="main" nowrap><b>PO number</b></td>
		<td class="main" nowrap><b>Qty</b></td>
		<td class="main" nowrap><b>Cost</b></td>
	</tr>
	<?php foreach ($receipts as $receiving_session) {
		$receiving_session['date'] = new DateTime($receiving_session['date']); ?>
	<tr class="show">
		<td class="main"><?= $receiving_session['vendors_company_name']; ?></td>
		<td class="main"><?= $receiving_session['date']->format('m/d/Y'); ?></td>
		<td class="main"><a href="/admin/po_viewer.php?poId=<?= $receiving_session['id']; ?>"><?= $receiving_session['purchase_order_number']; ?></a></td>
		<td class="main"><?= $receiving_session['quantity_received']; ?></td>
		<td class="main"><?= CK\text::monetize($receiving_session['cost']); ?></td>
	</tr>
	<?php } ?>
</table>
