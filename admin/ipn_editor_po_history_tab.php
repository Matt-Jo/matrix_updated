<?php
require_once('ipn_editor_top.php');

//allow po_per_page = 1000 and don't show the pager
$page = !empty($_GET['page'])?(int) $_GET['page']:1;
$po_per_page = 1000;
$show_pages = FALSE;
$show_sort = FALSE;
$limit_start = $po_per_page * ($page - 1);

// Fetch all fields on po_history tab
$pos = prepared_query::fetch('SELECT po.id, po.purchase_order_number, po.status, po.creation_date, po.expected_date, pos.text as status_text, v.vendors_company_name, a.admin_firstname, a.admin_lastname, pop.cost, IFNULL(SUM(porp.quantity_received), 0) as qty_received FROM purchase_order_products pop LEFT JOIN purchase_orders po ON po.id = pop.purchase_order_id LEFT JOIN purchase_order_status pos ON po.status = pos.id LEFT JOIN vendors v ON po.vendor = v.vendors_id LEFT JOIN admin a ON po.administrator_admin_id = a.admin_id LEFT JOIN purchase_order_received_products porp ON porp.purchase_order_product_id = pop.id WHERE pop.ipn_id = :stock_id GROUP BY po.id ORDER BY po.id DESC LIMIT '.$limit_start.', '.$po_per_page, cardinality::SET, [':stock_id' => $ipn->id()]);

// Find the aggregate sum of the quantity of products from all PO's
$po_product_quantity = prepared_query::fetch('SELECT pop.purchase_order_id, SUM(pop.quantity) AS quantity FROM purchase_order_products pop WHERE pop.ipn_id = :ipn_id GROUP BY pop.purchase_order_id', cardinality::SET, [':ipn_id' => $ipn->id()]);
// Loop through query and establish array of aggregated quantity values
$po_quantity = [];
foreach ($po_product_quantity as $quantity) {
	$po_quantity[$quantity['purchase_order_id']] = $quantity['quantity'];
}
?>

<style>
	.noshow-po { display:none; }
</style>

<div>Show All <input type="checkbox" name="show_all" onclick="show_all();" id="show_all_check" checked></div>

<table cellspacing="0" cellpadding="6px" style="border: 1px solid black" width="100%" id="showtr" name="showtr">
	<tr>
		<td class="main" nowrap><b>PO number</b></td>
		<td class="main" nowrap><b>Status</b></td>
		<td class="main" nowrap><b>Vendor</b></td>
		<td class="main" nowrap><b>Administrator</b></td>
		<td class="main" nowrap><b>Created</b></td>
		<td class="main" nowrap><b>Expected</b></td>
		<td class="main" nowrap><b>Cost</b></td>
		<td class="main" nowrap><b>Qty</b></td>
		<td class="main" nowrap><b>Received</b></td>
		<td class="main" nowrap><b>Remaining</b></td>
		<td class="main" nowrap><b>Action</b></td>
	</tr>
	<?php foreach ($pos as $po) {
		$po['creation_date'] = new DateTime($po['creation_date']);
		$po['expected_date'] = new DateTime($po['expected_date']);
		$po_quantity_remaining = $po_quantity[$po['id']] - $po['qty_received']; ?>

	<tr class="<?= !in_array($po['status'], [2, 1])||$po_quantity_remaining<=0?'no':''; ?>show-po">
		<td class="main"><a href="/admin/po_viewer.php?poId=<?= $po['id']; ?>"><?= $po['purchase_order_number']; ?></a></td>
		<td class="main"><?= $po['status_text']; ?></td>
		<td class="main"><?= $po['vendors_company_name']; ?></td>
		<td class="main"><?= $po['admin_firstname'].'&nbsp;'.$po['admin_lastname'];?></td>
		<td class="main"><?= $po['creation_date']->format('m/d/Y'); ?></td>
		<td class="main"><?= $po['expected_date']->format('m/d/Y'); ?></td>
		<td class="main"><?= CK\text::monetize($po['cost']); ?></td>
		<td class="main"><?= $po_quantity[$po['id']]; ?></td>
		<td class="main"><?= $po['qty_received']; ?></td>
		<td class="main"><?= $po_quantity_remaining; ?></td>
		<td class="main">
			<?php if (in_array($po['status'], [2, 1])) { ?>
			<input type="button" value="Receive" onClick="window.location='/admin/po_receiver.php?poId=<?= $po['id']; ?>';">
			&nbsp;
			<input type="button" value="Edit" onClick="window.location='/admin/po_editor.php?action=edit&poId=<?= $po['id']; ?>';">
			<?php } ?>
			&nbsp;
			<?php if ($po['status'] == 1) { ?>
			<input type="button" value="Void" onClick="if (confirm('Are you sure you want to void this purchase order?')) {window.location='po_list.php?poId=<?= $po['id']; ?>&page=<?= $page; ?>&show_all=<?= $__FLAG['show_all']?'no':'yes'; ?>&action=void';}">
			<?php } ?>
		</td>
	</tr>
	<?php } ?>
</table>
<script type="text/javascript">
	function show_all() {
		if (jQuery('#show_all_check').is(':checked')) jQuery('.noshow-po').show();
		else jQuery('.noshow-po').hide();
	}
	jQuery(document).ready(function($) {
		show_all();
	});
</script>
