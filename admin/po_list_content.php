<?php
require('includes/application_top.php');
require_once('includes/functions/po_alloc.php');

$action = !empty($_GET['action'])?$_GET['action']:NULL;
$po_id = !empty($_REQUEST['po_id'])?$_REQUEST['po_id']:NULL;

if ($action=='get_pors') {
	$results = prepared_query::fetch('SELECT DATE(`date`) as date FROM purchase_order_receiving_sessions WHERE purchase_order_id = ? ORDER BY `date` DESC', cardinality::SET, $po_id);
	echo '<div id="tr_pors_'.$po_id.'">';
	foreach ($results as $row) {
		echo date('m/d/Y', strtotime($row['date']))."<br/>";
	}
	echo '</div>';
	return;
	die();
}

$sort = 'po.id';
$dir = 'asc';

$allocated_subquery = "ifnull((select sum(op4.products_quantity) as total from orders o4, orders_products op4, products p4 where o4.orders_id = op4.orders_id and (op4.products_id = p4.products_id or ((op4.products_id - p4.products_id) = 0)) and o4.orders_status in (1, 2, 5, 7, 8, 10, 11, 12) and p4.stock_id = psc2.stock_id), 0)";
$received_subquery = "ifnull((select sum(porp5.quantity_received) from purchase_order_received_products porp5 where porp5.purchase_order_product_id = pop2.id ), 0)";
$urgent_po_subquery = "select count(*) from purchase_order_products pop2, products_stock_control psc2 where pop2.purchase_order_id = po.id and pop2.ipn_id = psc2.stock_id and (".$allocated_subquery." > psc2.stock_quantity) and (pop2.quantity - (". $received_subquery .") > 0)";

$po_query = "select po.id, po.purchase_order_number, po.status, po.creation_date, po.expected_date, po.followup_date, po.review, po.confirmation_status, pos.text as status_text, pot.text as terms,
v.vendors_company_name, a.admin_firstname, a.admin_lastname, rs.id as rs_id, max(date(por.date)) as por_date, count(por.id) as por_multiple, (". $urgent_po_subquery .") as urgent_count from purchase_orders po left join purchase_order_status pos on po.status = pos.id left join vendors v on po.vendor = v.vendors_id left join admin a on po.administrator_admin_id = a.admin_id left join purchase_order_terms pot on po.terms = pot.id left join purchase_order_review rs on ( rs.po_number=po.purchase_order_number and rs.status in (0,1)) left join purchase_order_receiving_sessions por on por.purchase_order_id=po.id ";
if (isset($_GET['tracking_search'])) {
	$po_query .= "left join purchase_order_tracking potr on (potr.po_id = po.id) ";
}

if (isset($_GET['void'])) {
	$poId = $_GET['void'];

	prepared_query::execute("update purchase_orders set status = 4 where id = :po_id", [':po_id' => $poId]);

	//now we get all the pops on the po and decrease the on_order qtys appropriately
	$pop_qtys = prepared_query::fetch('select pop.id, pop.ipn_id, pop.quantity from purchase_order_products pop where pop.purchase_order_id = :po_id', cardinality::SET, [':po_id' => $poId]);
	foreach ($pop_qtys as $pop_qty) {
		prepared_query::execute('update products_stock_control psc set psc.on_order = (psc.on_order - :qty) where psc.stock_id = :stock_id', [':qty' => $pop_qty['quantity'], ':stock_id' => $pop_qty['ipn_id']]);
		prepared_query::execute("update orders_products set po_waiting = NULL where po_waiting = :po_id", [':po_id' => $poId]);
		//MMD - don't forget to remove the po/order allocations
		po_alloc_remove_by_pop_id($pop_qty['id']);
	}
}

if (isset($_REQUEST['po_search'])) {
	$table_id = 'open_po';
	$tabs_num = 'tabs-5';
	$po_query .= "where po.purchase_order_number = '".$_REQUEST['po_search']."' group by po.id ";
	$po_query .= "order by po.id desc ";
}

$ipn_where_clause = "";
if (isset($_GET['ipn_search'])) {
	//first we look up all the POPs with this IPN
	if ($pop_ipn_query_response = prepared_query::fetch("select distinct pop.purchase_order_id from purchase_order_products pop, products_stock_control psc where pop.ipn_id = psc.stock_id and psc.stock_name like :ipn", cardinality::COLUMN, [':ipn' => '%'.$_GET['ipn_search'].'%'])) {
		$ipn_where_clause = " and po.id in (".implode(', ', $pop_ipn_query_response).") ";
	}
}

if (isset($_REQUEST['vendor_search'])) $vendor = "and po.vendor = '".$_REQUEST['vendor_search']."'";
else $vendor;

// additions
if (isset($_REQUEST['my_pos'])) $vendor = "and po.administrator_admin_id = '".$_SESSION['login_id']."'";
else $vendor;

if ($__FLAG['only_open_pos']) @$vendor .= ' AND po.status IN (1, 2)';

if (isset($_REQUEST['tracking_search'])) $vendor = "and potr.tracking_number = '".$_REQUEST['tracking_search']."'";
else $vendor;

if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-1') {
	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause ." and po.status in (1,2) group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'open_po';
	$tabs_num = 'tabs-1';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-11') {
	$po_query .= "left join purchase_order_tracking potr on (potr.po_id = po.id) ";
	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause. " and po.status in (1,2) and po.vendor != '35' and (potr.tracking_number IS NULL OR (potr.tracking_number IS NOT NULL AND potr.STATUS = '1')) ";
	$po_query .= " group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'follow_up';
	$tabs_num = 'tabs-11';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-2') {
	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause." and po.status in (1,2) and expected_date < NOW() group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'open_po_past_due';
	$tabs_num = 'tabs-2';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-3') {
	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause." and po.status in (2) group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'part_rec_po';
	$tabs_num = 'tabs-3';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-4') {
	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause." and po.status in (1) AND confirmation_status != '2' group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'pend_conf_po';
	$tabs_num = 'tabs-4';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-5') {
	$dir = 'desc';
	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause." group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.purchase_order_number desc ";
	$table_id = 'all_po';
	$tabs_num = 'tabs-5';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-6') {
	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause." and po.status in (1,2) ";
	$po_query .= "and po.administrator_admin_id = '".$_SESSION['login_id']."' group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'my_po';
	$tabs_num = 'tabs-6';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-7') {
	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause." and po.status in (1,2) ";
	$po_query .= "and (". $urgent_po_subquery .") > 0 group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'urgent_po';
	$tabs_num = 'tabs-7';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-8') {
	$po_query .= "where (1 ".(@$vendor).$ipn_where_clause." and po.status in (1,2) and po.terms in (1, 10, 11, 12))";
	$po_query .= "OR (1 ".(@$vendor).$ipn_where_clause." AND po.creation_date > date_sub(now(),interval 30 day) and po.terms in (1, 10, 11, 12)) group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'prepaid_po';
	$tabs_num = 'tabs-8';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-9') {
	$open_session_pos = prepared_query::fetch("select por.po_number from purchase_order_review por where por.status = '0' OR por.status = '1'", cardinality::COLUMN);
	$po_list = !empty($open_session_pos)?implode(', ', $open_session_pos):"''"; //put this here so we always have at least one search criteria in the list

	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause." and po.status in (1,2) and po.review = 0 and po.purchase_order_number in ($po_list) group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'open_sessions';
	$tabs_num = 'tabs-9';
}
if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'tabs-10') {
	$po_query .= "where 1 ".(@$vendor). $ipn_where_clause." and po.status in (1,2) and po.review = 1 group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'for_review';
	$tabs_num = 'tabs-10';
}

function get_total($poId) {
	$po_products_set = prepared_query::fetch("select pop.id, pop.quantity, pop.description, pop.cost, psc.stock_name, psc.on_order, sum(porp.quantity_received) as quantity_received from purchase_order_products pop left join products_stock_control psc on pop.ipn_id = psc.stock_id left join purchase_order_received_products porp on pop.id = porp.purchase_order_product_id where pop.purchase_order_id = :po_id group by pop.id", cardinality::SET, [':po_id' => $poId]);
	$total = $received = $remaining = 0;
	foreach ($po_products_set as $po_products) {
		$recqty=0;
		$rec = isset($po_products['quantity_received']) ? (	($recqty=$po_products['quantity_received'])*$po_products['cost']) : 0;
		$rem = (($po_products['quantity'] - $recqty)*$po_products['cost']);
		$total += $po_products['quantity'] * $po_products['cost'];
		$received += $rec;
		$remaining +=$rem;
	}

	$result = array('total' => number_format($total, 2), 'received' => number_format($received,2), 'remaining' => number_format($remaining,2));
	return $result;
}

$open_session_pos = prepared_query::fetch("select por.po_number from purchase_order_review por where por.status = '0' OR por.status = '1' ", cardinality::COLUMN); ?>
<!-- ----------------------------------------------------------------- -->
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$("#tabs").tabs();
		$("#open_po").tablesorter().tablesorterPager({container: $("#pager-tabs-1")});
		$("#follow_up").tablesorter().tablesorterPager({container: $("#pager-tabs-11")});
		$("#open_po_past_due").tablesorter().tablesorterPager({container: $("#pager-tabs-2")});
		$("#part_rec_po").tablesorter().tablesorterPager({container: $("#pager-tabs-3")});
		$("#pend_conf_po").tablesorter().tablesorterPager({container: $("#pager-tabs-4")});
		//$("#all_po").tablesorter().tablesorterPager({container: $("#pager-tabs-5")});
		$("#my_pos_tab").tablesorter().tablesorterPager({container: $("#pager-tabs-6")});
		$("#urgent_po").tablesorter().tablesorterPager({container: $("#pager-tabs-7")});
		//$("#prepaid_po").tablesorter().tablesorterPager({container: $("#pager-tabs-8")});
		$("#open_sessions").tablesorter().tablesorterPager({container: $("#pager-tabs-9")});
		$("#for_review").tablesorter().tablesorterPager({container: $("#pager-tabs-10")});

		$('.void').click(function(event) {
			var tabs = $("#tabs .ui-state-active").attr("id");
			var table_id = $("#tabs .ui-state-active").attr("table_id");
			var po_id = $(this).attr("po_id");
			if (confirm('Are you sure that you want to void PO'+po_id+'?'))
			$.ajax({
				type: "GET",
				url: "po_list_content.php",
				data: "action="+tabs+"&void="+po_id,
				success: function(html) {
					$("#"+table_id).replaceWith(html);
					$("#open_po").tablesorter();
					$("#follow_up").tablesorter();
					$("#open_po_past_due").tablesorter();
					$("#part_rec_po").tablesorter();
					$("#pend_conf_po").tablesorter();
					$("#all_po").tablesorter();
				}
			});
		});
	});
	function show_sessions(po_id) {
		if ($('tr_pors_'+po_id) != undefined ) {
			$('tr_pors_'+po_id).toggle();
		}
		else {
			new Ajax.Updater('tr_'+po_id, '/admin/po_list_content.php', {
				parameters: {action: 'get_pors', po_id: po_id},
				insertion: 'after',
				onComplete: function(s) {
					var bg_color = $('tr_'+po_id).getStyle('background-color');
					$('tr_pors_'+po_id).setStyle({backgroundColor: bg_color});
				}
			});
		}
	}
</script>
<?php if ($table_id != 'prepaid_po') { ?>
<table id="<?php echo isset($table_id)?$table_id : "open_po"; ?>" class="tablesorter">
	<thead>
		<tr>
			<th class="header">Urgent</th>
			<th class="header">PO number</th>
			<th class="header">Status</th>
			<th class="header">Vendor</th>
			<th class="header">Administrator</th>
			<th class="header">Created</th>
			<th class="header">Expected</th>
			<th class="header">Follow Up</th>
			<th class="header">Received Date</th>
			<?php /* <th class="header">Confirmation</th> */ ?>
			<th class="header">Terms</th>
			<th class="header">Received</th>
			<th class="header">Remaining</th>
			<th class="header">Open Sessions</th>
			<th class="header">For Review</th>
			<th class="header">Total</th>
			<th class="header">Action</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$po_query_response = prepared_query::fetch($po_query, cardinality::SET);
		$count = $cnt = 0;
		foreach ($po_query_response as $po) { ?>
		<tr<?php echo ((++$cnt)%2==0) ? '' : ' class="odd"' ?>>
			<td><?= $po['urgent_count']>0?'Yes':'&nbsp;'; ?></td>
			<td class="numeric" style="padding-right:5px;"><a href="po_viewer.php?poId=<?= $po['id']; ?>"><?= $po['purchase_order_number']; ?></a></td>
			<td><?= $po['status_text']; ?></td>
			<td><?= $po['vendors_company_name']; ?></td>
			<td><?php echo $po['admin_firstname'].'&nbsp;'.$po['admin_lastname'];?></td>
			<td><?php echo date('m/d/Y', strtotime($po['creation_date']));?></td>
			<td><?php echo ($po['expected_date'] != null ? date('m/d/Y', strtotime($po['expected_date'])) : '');?></td>
			<?php
			$fd_style_string = '';
			$fd_content = '';
			if (!empty($po['followup_date'])) {
				$fd_content = date('m/d/Y', strtotime($po['followup_date']));
				if (strtotime($po['followup_date']) < time()) {
					$fd_style_string = 'style="color: red; font-weight: bold;"';
				}
			} ?>
			<td <?= $fd_style_string; ?>><?= $fd_content; ?></td>
			<td align="right">
				<?php echo ($po['por_multiple'] > 1)? '<span style="color:#0A0; cursor: pointer" onClick="show_sessions('.$po['id'].')">+</span>' : ''?>
				<?php echo $po['por_date'] ? date('m/d/Y', strtotime($po['por_date'])) : '';?>
				<div style="width:100px; height:300px; display:none;" id="tr_<?= $po['id']; ?>"></div>
			</td>
			<?php /* <td>
			<?php
			if ($po['confirmation_status'] == 1) {
				echo 'Requested';
			}
			else if ($po['confirmation_status'] == 2) {
				echo 'Received';
			}
			else {
				echo 'Not Requested';
			}
			?>
			</td> */ ?>
			<td><?= $po['terms']; ?></td>
			<?php $totals = get_total($po['id']); ?>
			<td class="numeric">$<?= $totals['received']; ?></td>
			<td class="numeric">$<?= $totals['remaining']; ?></td>
			<td><?= ($po['status']==1||$po['status']==2)&&$po['review']==0&&in_array($po['id'], $open_session_pos)?'Yes':'No'; ?></td>
			<td><?= ($po['status']==1||$po['status']==2)&&$po['review']==1?'Yes':'No'; ?></td>
			<td class="numeric">$<?= $totals['total']; ?></td>
			<td nowrap>
				<?php if ($po['status'] == 2 || $po['status'] == 1) { ?>
				<input type="button" value="Receive" onclick="window.location='po_receiver.php?poId=<?= $po['id']; ?>';"/>&nbsp;
				<input type="button" value="Edit" onclick="window.location='po_editor.php?action=edit&poId=<?= $po['id']; ?>';"/>
				<?php } ?>
				&nbsp;
				<?php if ($po['status'] == 1) {?>
				<input type="button" value="Void" class="void" po_id="<?= $po['id']; ?>"/>
				<?php }
				if (!empty($po['review'])) { ?>
				<input type="button" value="Review" onclick="window.location='po_editor.php?action=edit&poId=<?= $po['id']; ?>'" >
				<?php } ?>
			</td>
		</tr>
		<?php } ?>
	</tbody>
	<tr>
		<td colspan="12">
			<div id="pager-<?= @$_GET['action']; ?>">
				<form>
					<img src="images/jquery_pager/first.png" class="first"/>
					<img src="images/jquery_pager/prev.png" class="prev"/>
					<input type="text" class="pagedisplay"/>
					<img src="images/jquery_pager/next.png" class="next"/>
					<img src="images/jquery_pager/last.png" class="last"/>
					<select class="pagesize">
						<option selected="selected" value="10">10</option>
						<option value="20">20</option>
						<option value="30">30</option>
						<option value="40">40</option>
					</select>
				</form>
			</div>
		</td>
	</tr>
</table>
<?php }
else { ?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$("#prepaid_po").dataTable({
			"processing": true,
			"serverSide": true,
			"ajax": "/admin/po_list_content_datatable.php?<?= $_SERVER['QUERY_STRING']; ?>",
			"ordering": true,
			"searching": false,
			"pageLength": 10,
			"autoWidth": false
		});
	});
</script>
<style>
	#prepaid_po tr th, #prepaid_po tr td { padding: 2px 2px 2px 2px; max-width: 1163px; text-align: right; font-size: 10px; }
	#prepaid_po tr th { text-align: left; }
	#prepaid_po tr:nth-child(even), #all-transactions tr:nth-child(even), #payments tr:nth-child(even) {background: #CCC}
	#prepaid_po tr:nth-child(odd), #all-transactions tr:nth-child(odd), #payments tr:nth-child(odd) {background: #fff}
</style>
<table id="prepaid_po" data-order='[[ 1, "desc" ]]' border="1px solid #bbbbbb;">
	<thead>
		<tr>
			<th>Urgent</th>
			<th>PO number</th>
			<th>Status</th>
			<th>Vendor</th>
			<th>Administrator</th>
			<th>Created</th>
			<th>Expected</th>
			<th>Follow Up</th>
			<th>Received Date</th>
			<th>Terms</th>
			<th>Received</th>
			<th>Remaining</th>
			<th>Open Sessions</th>
			<th>For Review</th>
			<th>Total</th>
			<th>Action</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th>Urgent</th>
			<th>PO number</th>
			<th>Status</th>
			<th>Vendor</th>
			<th>Administrator</th>
			<th>Created</th>
			<th>Expected</th>
			<th>Follow Up</th>
			<th>Received Date</th>
			<th>Terms</th>
			<th>Received</th>
			<th>Remaining</th>
			<th>Open Sessions</th>
			<th>For Review</th>
			<th>Total</th>
			<th>Action</th>
		</tr>
	</tfoot>
</table>
<?php } ?>
<!-- ----------------------------------------------------------------- -->
