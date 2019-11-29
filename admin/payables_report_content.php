<?php
require('includes/application_top.php');

$start_date	= $_GET['start_date'];
$end_date	= $_GET['end_date'];

$and = "";

if (!empty($_GET['po_search'])) $and = " AND po.purchase_order_number = {$_GET['po_search']}";

if (!empty($_GET['ipn_search'])) {
	$pop_ipn_query_response = prepared_query::fetch("SELECT DISTINCT pop.purchase_order_id FROM purchase_order_products pop, products_stock_control psc WHERE pop.ipn_id = psc.stock_id AND psc.stock_name LIKE :ipn", cardinality::COLUMN, [':ipn' => '%'.$_GE['ipn_search'].'%']);
	$and .= " AND po.id in (".implode(', ', $pop_ipn_query_response).") ";
}

if (is_numeric($_GET['vendor_search'])) $and = " AND v.vendors_id = {$_GET['vendor_search']}";

if (!empty($start_date) && !empty($end_date)) $and .= "AND pors.date > '".$start_date."' AND pors.date < '".$end_date."23:59:59'";

switch ($_GET['action']) {
	case 'tabs-1':
		break;
	case 'tabs-2':
		// Received and Paid
		$and .= " AND porp.paid = 1";
		break;
	case 'tabs-3':
		// Received not Paid
		$total_unpaid = 0;
		$and .= " AND porp.paid = 0";
		break;
	case 'mark_paid':
		prepared_query::execute("update purchase_order_received_products porp set porp.paid = 1 where porp.receiving_session_id = :pors_id", [':pors_id' => $_GET['pors_id']]);
		echo $_GET['pors_id'];
		exit();
		break;
}
?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#tabs").tabs();
		jQuery("#open_po_<?=$_GET['action']?>")
			.tablesorter()
			.tablesorterPager({
				container: jQuery("#pager-<?=$_GET['action']?>"),
				size: 100
			});
	});
</script>
<table id="open_po_<?=$_GET['action']?>" class="tablesorter">
	<thead>
		<tr>
			<th class="header">Vendor Name</th>
			<th class="header">PO Number</th>
			<th class="header">Session ID</th>
			<th class="header">Received Date</th>
			<th class="header">Terms</th>
			<th class="header">Total</th>
			<?php if ($_GET['action'] == 'tabs-3') { ?>
			<th class="header">Action</th>
			<?php } ?>
		</tr>
	</thead>
	<tbody>
		<?php if (!($query_response = prepared_query::fetch("SELECT po.purchase_order_number, sum(porp.quantity_received * pop.cost) as cost, pot.text AS terms, pors.date, pors.id AS receiving_session, v.vendors_company_name, (SELECT count(*) from purchase_order_received_products where receiving_session_id = pors.id) as count FROM purchase_orders po LEFT JOIN purchase_order_terms AS pot ON po.terms = pot.id LEFT JOIN vendors AS v ON po.vendor = v.vendors_id LEFT JOIN purchase_order_products AS pop ON pop.purchase_order_id = po.id LEFT JOIN products_stock_control AS psc ON pop.ipn_id = psc.stock_id LEFT JOIN purchase_order_received_products AS porp ON porp.purchase_order_product_id = pop.id LEFT JOIN purchase_order_receiving_sessions AS pors ON pors.id = porp.receiving_session_id WHERE 1=1 ".$and." GROUP BY po.id, pors.id ORDER BY v.vendors_company_name asc, po.purchase_order_number desc, pors.id desc", cardinality::SET))) { ?>
		<tr>
			<td colspan="6">No Results To Display</td>
		</tr>
		<?php }

		foreach ($query_response as $cnt => $payable) { ?>
		<tr<?= (($cnt)%2==0) ? '' : ' class="odd"' ?> id="<?= $payable['receiving_session']; ?>">
			<td><?=$payable['vendors_company_name']; ?></td>
			<td class="numeric" style="padding-right:5px;"><a href="po_viewer.php?poId=<?=$payable['purchase_order_number']; ?>"><?=$payable['purchase_order_number']; ?></a></td>
			<td><?=$payable['receiving_session']; ?></td>
			<td><?=$payable['date']; ?></td>
			<td><?=$payable['terms']; ?></td>
			<td>$<?=number_format($payable['cost'], 2); ?></td>
			<?php if ($_GET['action'] == 'tabs-3') { ?>
			<td><a href="#" onclick="markPaid('<?= $payable['receiving_session']; ?>'); return false;">Mark Paid</a></td>
			<?php } ?>
		</tr>
			<?php if ($_GET['action'] == 'tabs-3') $total_unpaid += $payable['cost'];
		} ?>
	</tbody>
	<tbody>
		<tr>
			<td colspan="12">
				<div id="pager-<?=$_GET['action']?>">
					<form>
						<img src="images/jquery_pager/first.png" class="first"/>
						<img src="images/jquery_pager/prev.png" class="prev"/>
						<input type="text" class="pagedisplay"/>
						<img src="images/jquery_pager/next.png" class="next"/>
						<img src="images/jquery_pager/last.png" class="last"/>
						<select class="pagesize">
							<option selected="selected" value="100">100</option>
							<option value="500">500</option>
							<option value="1000">1000</option>
						</select>
					</form>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<?php if ($_GET['action'] == "tabs-3") { ?>
<div>
	Total Unpaid: <b>$<?php echo number_format($total_unpaid, 2, '.', ','); ?></b>
</div>
<script type="text/javascript">
	function markPaid(porsId) {
		jQuery.ajax({
			url: 'payables_report_content.php?action=mark_paid&pors_id=' + porsId,
			success: function(result) {
				jQuery('#' + result).hide();
				jQuery('#please_wait').hide();
			}
		});
	}
</script>
<?php } ?>
<!-- ----------------------------------------------------------------- -->
