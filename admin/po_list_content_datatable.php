<?php
$skip_check = TRUE;
require('includes/application_top.php');
require_once('includes/functions/po_alloc.php');

$action = $_REQUEST['action'] ?? NULL;

if ($action=='get_pors') {
		$result=prepared_query::fetch('select date(date)as date from purchase_order_receiving_sessions where purchase_order_id=:po_id order by date desc', cardinality::COLUMN, [':po_id' => $po_id]);
		print '<div id="tr_pors_'.$po_id.'">';
		foreach ($result as $date) {
				print date('m/d/Y', strtotime($date))."<br/>";
		}
		print '</div>';
		return;
die;
}


$sort = 'po.id';
$dir = 'asc';

function customDatatableSort($a, $b) {
	$vala = strip_tags($a[$_REQUEST['order'][0]['column']]);
	$valb = strip_tags($b[$_REQUEST['order'][0]['column']]);

	//check if we are dealing with money
	if (strpos($vala, '$') == 0 && strpos($valb, '$') == 0) {
		$vala = str_replace('$', '', str_replace(',', '', $vala));
		$valb = str_replace('$', '', str_replace(',', '', $valb));
	}

	if ($vala == $valb) return 0;

	$result = ($vala<$valb)?-1:1;

	if ($_REQUEST['order'][0]['dir'] == 'desc') {
		$result = $result * -1;
	}

	return $result;

}

$allocated_subquery = "ifnull((select sum(op4.products_quantity) as total from orders o4, orders_products op4, products p4 where o4.orders_id = op4.orders_id and (op4.products_id = p4.products_id or ((op4.products_id - p4.products_id) = 0)) and o4.orders_status in (1, 2, 5, 7, 8, 10, 11, 12) and p4.stock_id = psc2.stock_id), 0)";

$received_subquery = "ifnull((select sum(porp5.quantity_received) from purchase_order_received_products porp5 where porp5.purchase_order_product_id = pop2.id ), 0)";

$urgent_po_subquery = "select count(*) from purchase_order_products pop2, products_stock_control psc2 where pop2.purchase_order_id = po.id and pop2.ipn_id = psc2.stock_id and (".$allocated_subquery." > psc2.stock_quantity) and (pop2.quantity - (". $received_subquery .") > 0)";

$po_query = "select po.id, po.purchase_order_number, po.status, po.creation_date, po.expected_date, po.followup_date, po.review, po.confirmation_status, pos.text as status_text, pot.text as terms,
v.vendors_company_name, a.admin_firstname, a.admin_lastname, rs.id as rs_id, max(date(por.date)) as por_date, count(por.id) as por_multiple, (". $urgent_po_subquery .") as urgent_count ";
$po_query .= "from purchase_orders po ";
$po_query .= "left join purchase_order_status pos on po.status = pos.id ";
$po_query .= "left join vendors v on po.vendor = v.vendors_id ";
$po_query .= "left join admin a on po.administrator_admin_id = a.admin_id ";
$po_query .= "left join purchase_order_terms pot on po.terms = pot.id ";
$po_query .= "left join purchase_order_review rs on ( rs.po_number=po.purchase_order_number and rs.status in (0,1)) left join purchase_order_receiving_sessions por on por.purchase_order_id=po.id ";
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
	$po_ids = prepared_query::fetch("select distinct pop.purchase_order_id from purchase_order_products pop, products_stock_control psc where pop.ipn_id = psc.stock_id and psc.stock_name like :ipn", cardinality::COLUMN, [':ipn' => '%'.$_GET['ipn_search'].'%']);

	$ipn_where_clause = " and po.id in (".implode(', ', $po_ids).', 0) ';
}

$vendor = '';

if (isset($_REQUEST['vendor_search'])) $vendor = "and po.vendor = '".$_REQUEST['vendor_search']."'";
// additions
if (isset($_REQUEST['my_pos'])) $vendor = "and po.administrator_admin_id = '".$_SESSION['login_id']."'";
if (isset($_REQUEST['tracking_search'])) $vendor = "and potr.tracking_number = '".$_REQUEST['tracking_search']."'";

if ($_REQUEST['action'] == 'tabs-1') {
	$po_query .= "where 1 ".$vendor. $ipn_where_clause ." and po.status in (1,2) group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'open_po';
	$tabs_num = 'tabs-1';
}
if ($_REQUEST['action'] == 'tabs-11') {
	$po_query .= "left join purchase_order_tracking potr on (potr.po_id = po.id) ";
	$po_query .= "where 1 ".$vendor. $ipn_where_clause. " and po.status in (1,2) and po.vendor != '35' and (potr.tracking_number IS NULL OR (potr.tracking_number IS NOT NULL AND potr.STATUS = '1')) ";
	$po_query .= " group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'follow_up';
	$tabs_num = 'tabs-11';
}
if ($_REQUEST['action'] == 'tabs-2') {
	$po_query .= "where 1 ".$vendor. $ipn_where_clause." and po.status in (1,2) and expected_date < NOW() group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'open_po_past_due';
	$tabs_num = 'tabs-2';
}
if ($_REQUEST['action'] == 'tabs-3') {
	$po_query .= "where 1 ".$vendor. $ipn_where_clause." and po.status in (2) group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'part_rec_po';
	$tabs_num = 'tabs-3';
}
if ($_REQUEST['action'] == 'tabs-4') {
	$po_query .= "where 1 ".$vendor. $ipn_where_clause." and po.status in (1) AND confirmation_status != '2' group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'pend_conf_po';
	$tabs_num = 'tabs-4';
}
if ($_REQUEST['action'] == 'tabs-5') {
	$dir = 'desc';
	$po_query .= "where 1 ".$vendor. $ipn_where_clause." group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.purchase_order_number desc ";
	$table_id = 'all_po';
	$tabs_num = 'tabs-5';
}
if ($_REQUEST['action'] == 'tabs-6') {
	$po_query .= "where 1 ".$vendor. $ipn_where_clause." and po.status in (1,2) ";
	$po_query .= "and po.administrator_admin_id = '".$_SESSION['login_id']."' group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'my_po';
	$tabs_num = 'tabs-6';
}
if ($_REQUEST['action'] == 'tabs-7') {
	$po_query .= "where 1 ".$vendor. $ipn_where_clause." and po.status in (1,2) ";
	$po_query .= "and (". $urgent_po_subquery .") > 0 group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'urgent_po';
	$tabs_num = 'tabs-7';
}
if ($_REQUEST['action'] == 'tabs-8') {
	$po_query .= "where (1 ".$vendor. $ipn_where_clause." and po.status in (1,2) and po.terms in (1, 10, 11, 12))";
		$po_query .= "OR (1 ".$vendor.$ipn_where_clause." AND po.creation_date > date_sub(now(),interval 30 day) and po.terms in (1, 10, 11, 12)) group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'prepaid_po';
	$tabs_num = 'tabs-8';
}
if ($_REQUEST['action'] == 'tabs-9') {
	$open_session_pos = prepared_query::fetch("select por.po_number from purchase_order_review por where por.status = '0' OR por.status = '1' ", cardinality::COLUMN);
	$po_list = !empty($open_session_pos)?implode(', ', $open_session_pos):"''"; //put this here so we always have at least one search criteria in the list

	$po_query .= "where 1 ".$vendor. $ipn_where_clause." and po.status in (1,2) and po.review = 0 and po.purchase_order_number in ($po_list) group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'open_sessions';
	$tabs_num = 'tabs-9';
}
if ($_REQUEST['action'] == 'tabs-10') {
	$po_query .= "where 1 ".$vendor. $ipn_where_clause." and po.status in (1,2) and po.review = 1 group by po.id ";
	$po_query .= "order by ";
	$po_query .= $sort." ".$dir.", ";
	$po_query .= "po.status asc, po.purchase_order_number desc ";
	$table_id = 'for_review';
	$tabs_num = 'tabs-10';
}

function get_total($poId) {
		$po_products_set = prepared_query::fetch("select pop.id, pop.quantity, pop.description, pop.cost, psc.stock_name, psc.on_order, sum(porp.quantity_received) as quantity_received from purchase_order_products pop left join products_stock_control psc on pop.ipn_id = psc.stock_id left join purchase_order_received_products porp on pop.id = porp.purchase_order_product_id where pop.purchase_order_id = :po_id group by pop.id", cardinality::SET, [':po_id' => $poId]);
		$po_products = array();
		$total = 0;
		$received = 0;
		$remaining = 0;
		foreach ($po_products_set as $po_products) {
			$recqty=0;
			$rec = isset($po_products['quantity_received']) ? (	($recqty=$po_products['quantity_received'])*$po_products['cost']) : 0;
			$rem = (($po_products['quantity'] - $recqty)*$po_products['cost']);
			$total += $po_products['quantity'] * $po_products['cost'];
			$received += $rec;
			$remaining +=$rem;
		}

		$result = array('total' => number_format($total, 2),
						'received' => number_format($received,2),
						'remaining' => number_format($remaining,2));

		return $result;
}

$open_session_pos = prepared_query::fetch("select por.po_number from purchase_order_review por where por.status = '0' OR por.status = '1' ", cardinality::COLUMN);
$po_query_rows = prepared_query::fetch($po_query, cardinality::SET);

$response = array();

$response['draw'] = $_REQUEST['draw'];
$response['recordsTotal'] = count($po_query_rows);
$response['recordsFiltered'] = count($po_query_rows);
$response['data'] = array();

$all_results = array();

foreach ($po_query_rows as $unused => $po) {

	if ($po == null) { //have we run out of invoices?
		break;
	}

	$row_array = array();

	$row_array[] = ($po['urgent_count'] > 0 ? 'Yes' : '&nbsp;');
		$row_array[] = '<a href="po_viewer.php?poId='.$po['id'].'">'.$po['purchase_order_number'].'</a>';
		$row_array[] = $po['status_text'];
		$row_array[] = $po['vendors_company_name'];
		$row_array[] = $po['admin_firstname'].'&nbsp;'.$po['admin_lastname'];
		$row_array[] = date('m/d/Y', strtotime($po['creation_date']));
		$row_array[] = ($po['expected_date'] != null ? date('m/d/Y', strtotime($po['expected_date'])) : '');

	$fd_style_string = '';
	$fd_content = '';
	if (!empty($po['followup_date'])) {
		$fd_content = date('m/d/Y', strtotime($po['followup_date']));
		if (strtotime($po['followup_date']) < time()) {
			$fd_style_string = 'style="color: red; font-weight: bold;"';
		}
	}
		$row_array[] = '<span '.$fd_style_string.'>'.$fd_content.'</span>';

	$por_string = ($po['por_multiple'] > 1)? '<span style="color:#0A0; cursor: pointer" onClick="show_sessions('.$po['id'].')">+</span>' : '';
		$por_string .= $po['por_date'] ? date('m/d/Y', strtotime($po['por_date'])) : '';
		$por_string .= '<div style="width:100px; height:300px; display:none;" id="tr_'.$po['id'].'"></div>';
	$row_array[] .= $por_string;

	$row_array[] = $po['terms'];

	$totals = get_total($po['id']);
		$row_array[] = '$'.$totals['received'];
		$row_array[] = '$'.$totals['remaining'];
	$row_array[] = (($po['status'] == 1 || $po['status'] == 2) && $po['review'] == 0 && in_array($po['id'], $open_session_pos)) ? 'Yes' : 'No';
	$row_array[] = (($po['status'] == 1 || $po['status'] == 2) && $po['review'] == 1) ? 'Yes' : 'No';

	$row_array[] = '$'.$totals['total'];

	$button_string = '';
	if ($po['status'] == 2 || $po['status'] == 1) {
		$button_string .= '<input type="button" value="Receive" onclick="window.location=\'po_receiver.php?poId='.$po['id'].'\';"/>&nbsp;';
		$button_string .= '<input type="button" value="Edit" onclick="window.location=\'po_editor.php?action=edit&poId='.$po['id'].'\';"/>';
	}
	$button_string .= '&nbsp;';
	if ($po['status'] == 1) {
		$button_string .= '<input type="button" value="Void" class="void" po_id="'.$po['id'].'"/>';
	}
	if (!empty($po['review'])) {
		$button_string .= '<input type="button" value="Review" onclick="window.location=\'po_editor.php?action=edit&poId='.$po['id'].'\'" />';
	}
	$row_array[] = $button_string;

	$all_results[] = $row_array;
}

if (!empty($_REQUEST['order'])) {
	usort($all_results, 'customDatatableSort');
}

for ($i = 0; $i < $_REQUEST['length']; $i++) {
	if (empty($all_results[(int)$_REQUEST['start'] + $i])) continue;
	$row_array = $all_results[(int)$_REQUEST['start'] + $i];
	$response['data'][] = $row_array;
}

echo json_encode($response);
