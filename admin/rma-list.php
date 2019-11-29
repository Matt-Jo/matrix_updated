<?php
require_once('includes/application_top.php');

set_time_limit(300);
ini_set('memory_limit', '192M');

function display_pager($tab) { ?>
	<tr>
		<td colspan="12">
			<div id="pager-<?= $tab; ?>">
				<form>
					<img src="images/jquery_pager/first.png" class="first">
					<img src="images/jquery_pager/prev.png" class="prev">
					<input type="text" class="pagedisplay">
					<img src="images/jquery_pager/next.png" class="next">
					<img src="images/jquery_pager/last.png" class="last">
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
<?php }

function display_open_rmas($openRmas) { ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#open-rmas").tablesorter().tablesorterPager({container: $("#pager-2")});
		});
	</script>
	<table id="open-rmas" class="tablesorter">
		<thead>
			<tr>
				<th class="numeric">ID</th>
				<th>Status</th>
				<th>For</th>
				<th class="numeric">Order</th>
				<th>Customer</th>
				<th>Customer PO</th>
				<th>Followup Date</th>
				<th>Created By</th>
				<th>Days Old</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($openRmas as $rma) {
				$order = $rma->get_sales_order();
				$customer = $rma->get_customer();

				$days_old = $rma->get_header('created_on')->diff(ck_datetime::NOW()); ?>
			<tr>
				<td class="numeric"><a href="rma-detail.php?id=<?= $rma->id(); ?>"><?= $rma->id(); ?></a></td>
				<td><?= $rma->get_status(); ?></td>
				<td><?= $rma->get_header('disposition'); ?></td>
				<td class="numeric"><a href="orders_new.php?selected_box=orders&oID=<?= $order->id(); ?>&action=edit"><?= $order->id(); ?></a></td>
				<td><?= $customer->get_display_label(); ?></td>
				<td><?= $order->get_terms_po_number(); ?></td>
				<td style="<?= $rma->has('follow_up_date')&&$rma->get_header('follow_up_date')<=ck_datetime::NOW()?'color:red;':''; ?>"><?= $rma->has('follow_up_date')?$rma->get_header('follow_up_date')->short_date():''; ?></td>
				<td><?= $rma->get_admin()->get_name(); ?></td>
				<td><?= $days_old->format('%a days'); ?></td>
			</tr>
			<?php } ?>
		</tbody>
		<?php display_pager('2'); ?>
	</table>
<?php }

function display_my_rmas($myRmas) { ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#my-rmas").tablesorter().tablesorterPager({container: $("#pager-1")});
		});
	</script>
	<table id="my-rmas" class="tablesorter">
		<thead>
			<tr>
				<th class="numeric">ID</th>
				<th>Status</th>
				<th>For</th>
				<th class="numeric">Order</th>
				<th>Customer</th>
				<th>Customer PO</th>
				<th>Followup Date</th>
				<th>Created By</th>
				<th>Days Old</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($myRmas as $rma) {
				$order = $rma->get_sales_order();
				$customer = $rma->get_customer();

				$days_old = $rma->get_header('created_on')->diff(ck_datetime::NOW()); ?>
			<tr>
				<td class="numeric"><a href="rma-detail.php?id=<?= $rma->id(); ?>"><?= $rma->id(); ?></a></td>
				<td><?= $rma->get_status(); ?></td>
				<td><?= $rma->get_header('disposition'); ?></td>
				<td class="numeric"><a href="orders_new.php?selected_box=orders&oID=<?= $order->id(); ?>&action=edit"><?= $order->id(); ?></a></td>
				<td><?= $customer->get_display_label(); ?></td>
				<td><?= $order->get_terms_po_number(); ?></td>
				<td style="<?= $rma->has('follow_up_date')&&$rma->get_header('follow_up_date')<=ck_datetime::NOW()?'color:red;':''; ?>"><?= $rma->has('follow_up_date')?$rma->get_header('follow_up_date')->short_date():''; ?></td>
				<td><?= $rma->get_admin()->get_name(); ?></td>
				<td><?= $days_old->format('%a days'); ?></td>
			</tr>
			<?php } ?>
		</tbody>
		<?php display_pager('1'); ?>
	</table>
<?php }

function display_my_closed_rmas($myClosedRmas) { ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#my-closed-rmas").tablesorter().tablesorterPager({container: $("#pager-4")});
		});
	</script>
	<table id="my-closed-rmas" class="tablesorter">
		<thead>
			<tr>
				<th class="numeric">ID</th>
				<th>Status</th>
				<th>For</th>
				<th class="numeric">Order</th>
				<th>Customer</th>
				<th>Customer PO</th>
				<th>Followup Date</th>
				<th>Created By</th>
				<th>Days Old</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($myClosedRmas as $rma) {
				$order = $rma->get_sales_order();
				$customer = $rma->get_customer();

				$days_old = $rma->get_header('created_on')->diff(ck_datetime::NOW()); ?>
			<tr>
				<td class="numeric"><a href="rma-detail.php?id=<?= $rma->id(); ?>"><?= $rma->id(); ?></a></td>
				<td><?= $rma->get_status(); ?></td>
				<td><?= $rma->get_header('disposition'); ?></td>
				<td class="numeric"><a href="orders_new.php?selected_box=orders&oID=<?= $order->id(); ?>&action=edit"><?= $order->id(); ?></a></td>
				<td><?= $customer->get_display_label(); ?></td>
				<td><?= $order->get_terms_po_number(); ?></td>
				<td style="<?= $rma->has('follow_up_date')&&$rma->get_header('follow_up_date')<=ck_datetime::NOW()?'color:red;':''; ?>"><?= $rma->has('follow_up_date')?$rma->get_header('follow_up_date')->short_date():''; ?></td>
				<td><?= $rma->get_admin()->get_name(); ?></td>
				<td></td>
			</tr>
			<?php } ?>
		</tbody>
		<?php display_pager('4'); ?>
	</table>
<?php }

function display_all_rmas($allRmas) { ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#all-rmas").tablesorter().tablesorterPager({container: $("#pager-3")});
		});
	</script>
	<table id="all-rmas" class="tablesorter">
		<thead>
			<tr>
				<th class="numeric">ID</th>
				<th>Status</th>
				<th>For</th>
				<th class="numeric">Order</th>
				<th>Customer</th>
				<th>Customer PO</th>
				<th>Followup Date</th>
				<th>Created By</th>
				<th>Days Old</th>
			</tr>
		</thead>
		<tbody>
			<?php ck_sales_order::cache(FALSE);
			ck_customer2::cache(FALSE);
			foreach ($allRmas as $rma) {
				$order = $rma->get_sales_order();
				$customer = $rma->get_customer();

				$days_old = $rma->get_header('created_on')->diff(ck_datetime::NOW()); ?>
			<tr>
				<td class="numeric"><a href="rma-detail.php?id=<?= $rma->id(); ?>"><?= $rma->id(); ?></a></td>
				<td><?= $rma->get_status(); ?></td>
				<td><?= $rma->get_header('disposition'); ?></td>
				<td class="numeric"><a href="orders_new.php?selected_box=orders&oID=<?= $order->id(); ?>&action=edit"><?= $order->id(); ?></a></td>
				<td><?= $customer->get_display_label_direct(); ?></td>
				<td><?= $order->get_terms_po_number(); ?></td>
				<td style="<?= $rma->has('follow_up_date')&&$rma->get_header('follow_up_date')<=ck_datetime::NOW()?'color:red;':''; ?>"><?= $rma->has('follow_up_date')?$rma->get_header('follow_up_date')->short_date():''; ?></td>
				<td><?= $rma->get_admin()->get_name(); ?></td>
				<td><?= !in_array($rma->get_status(), [ck_rma2::STATUS_CLOSED, ck_rma2::STATUS_RECEIVED])?$days_old->format('%a days'):''; ?></td>
			</tr>
			<?php } ?>
		</tbody>
		<?php display_pager('3'); ?>
	</table>
<?php }

if (isset($_GET['tab'])) {
	$filter = " ";

	if (!empty($_GET['search_field']) && !empty($_GET['search_value']) && trim($_GET['search_field']) != '') {
		switch ($_GET['search_field']) {
			case 'id':
				$rma_ids = [$_GET['search_value']];
				break;
			case 'orders_id':
				$rma_ids = prepared_query::fetch('SELECT id FROM rma WHERE order_id = :orders_id ORDER BY created_on ASC', cardinality::COLUMN, [':orders_id' => $_GET['search_value']]);
				break;
			case 'customer_po':
				$rma_ids = prepared_query::fetch('SELECT DISTINCT r.id FROM rma r JOIN orders o ON r.order_id = o.orders_id WHERE o.net10_po LIKE :customer_po OR o.net15_po LIKE :customer_po OR o.net30_po LIKE :customer_po OR o.net45_po LIKE :customer_po ORDER BY r.created_on ASC', cardinality::COLUMN, [':customer_po' => '%'.$_GET['search_value'].'%']);
				break;
			case 'stock_name':
				$rma_ids = prepared_query::fetch('SELECT DISTINCT r.id FROM rma r JOIN rma_product rp ON r.id = rp.rma_id JOIN orders_products op ON rp.order_product_id = op.orders_products_id JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE psc.stock_name LIKE :ipn ORDER BY r.created_on ASC, psc.stock_name ASC', cardinality::COLUMN, [':ipn' => '%'.$_GET['search_value'].'%']);
				break;
		}

		$rmaList = array_reduce($rma_ids, function($rmaList, $rma_id) {
			$rmaList[] = new ck_rma2($rma_id);
			return $rmaList;
		}, []);

		$allRmas = [];
		$myRmas = [];
		$openRmas = [];
		$myClosedRmas = [];

		foreach ($rmaList as $rma) {
			$allRmas[] = $rma;

			if (in_array($rma->get_status(), [ck_rma2::STATUS_OPEN, ck_rma2::STATUS_PARTIALLY_RECEIVED])) {
				$openRmas[] = $rma;
				if ($rma->get_header('admin_id') == $_SESSION['login_id']) $myRmas[] = $rma;
			}
			elseif ($rma->get_header('admin_id') == $_SESSION['login_id']) $myClosedRmas[] = $rma;
		}

		switch ($_GET['tab']) {
			case '2':
				display_open_rmas($openRmas);
				break;
			case '1':
				display_my_rmas($myRmas);
				break;
			case '3':
				display_all_rmas($allRmas);
				break;
			case '4':
				display_my_closed_rmas($myClosedRmas);
				break;
		}
	}
	else {
		switch ($_GET['tab']) {
			case '2':
				$rma_ids = prepared_query::fetch('SELECT r.id, COUNT(rp.id) as line_items, COUNT(CASE WHEN rp.received_date IS NOT NULL THEN 1 END) as closed_line_items FROM rma r LEFT JOIN rma_product rp ON rp.rma_id = r.id WHERE r.closed = 0 GROUP BY r.id HAVING line_items > closed_line_items ORDER BY r.id DESC', cardinality::COLUMN);

				$rmaList = array_reduce($rma_ids, function($rmaList, $rma_id) {
					$rmaList[] = new ck_rma2($rma_id);
					return $rmaList;
				}, []);

				display_open_rmas($rmaList);
				break;
			case '1':
				$rma_ids = prepared_query::fetch('SELECT r.id, COUNT(rp.id) as line_items, COUNT(CASE WHEN rp.received_date IS NOT NULL THEN 1 END) as closed_line_items FROM rma r LEFT JOIN rma_product rp ON rp.rma_id = r.id WHERE r.closed = 0 AND r.created_by = :login_id GROUP BY r.id HAVING line_items > closed_line_items ORDER BY r.id DESC', cardinality::COLUMN, [':login_id' => $_SESSION['login_id']]);

				$rmaList = array_reduce($rma_ids, function($rmaList, $rma_id) {
					$rmaList[] = new ck_rma2($rma_id);
					return $rmaList;
				}, []);

				display_my_rmas($rmaList);
				break;
			case '3':
				$rma_ids = prepared_query::fetch('SELECT id FROM rma WHERE created_on >= CURDATE() - INTERVAL 13 MONTH ORDER BY created_on DESC', cardinality::COLUMN);

				$rmaList = array_reduce($rma_ids, function($rmaList, $rma_id) {
					$rmaList[] = new ck_rma2($rma_id);
					return $rmaList;
				}, []);

				display_all_rmas($rmaList);
				break;
			case '4':
				$rma_ids = prepared_query::fetch('SELECT r.id, r.closed, COUNT(rp.id) as line_items, COUNT(CASE WHEN rp.received_date IS NOT NULL THEN 1 END) as closed_line_items FROM rma r LEFT JOIN rma_product rp ON rp.rma_id = r.id WHERE r.created_by = :login_id GROUP BY r.id HAVING line_items = closed_line_items OR r.closed = 1 ORDER BY r.id DESC', cardinality::COLUMN, [':login_id' => $_SESSION['login_id']]);

				$rmaList = array_reduce($rma_ids, function($rmaList, $rma_id) {
					$rmaList[] = new ck_rma2($rma_id);
					return $rmaList;
				}, []);

				display_my_closed_rmas($rmaList);
				break;
		}
	}
	exit();
}

//search string
$search_string = '';
if (!empty($_GET['search_field']) && !empty($_GET['search_value']) && trim($_GET['search_field'])) $search_string = '&search_field='.$_GET['search_field'].'&search_value='.$_GET['search_value']; ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
		<title><?= TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body>
		<div id="modal" class="jqmWindow" style="width: 800px;">
			<a class="jqmClose" href="#" style="float: right; clear: both;">X</a>
			<div id="modal-content"></div>
		</div>
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<script language="javascript" src="includes/general.js"></script>
		<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
		<div id="main" style="width: 800px;">
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$("#please_wait").bind("ajaxSend", function() {
						$(this).show();
					}).bind("ajaxComplete", function() {
						$(this).hide();
					});
					$("#tabs").tabs();

					$('a#print').click(function() {
						window.print();
					});
				});
			</script>
			<style type="text/css">
				table.tablesorter tbody td { padding: 4px 3px 4px 3px; }
			</style>
			<h2>RMAs</h2>
			<div id="search">
				<form method="GET" action="rma-list.php">
					<span>Search</span>
					<select name="search_field">
						<option value="id" <?= !empty($_GET['search_field'])&&$_GET['search_field']=='id'?'selected':''; ?>>RMA ID</option>
						<option value="orders_id" <?= !empty($_GET['search_field'])&&$_GET['search_field']=='orders_id'?'selected':''; ?>>Order ID</option>
						<option value="customer_po" <?= !empty($_GET['search_field'])&&$_GET['search_field']=='customer_po'?'selected':''; ?>>Customer PO</option>
						<option value="stock_name" <?= !empty($_GET['search_field'])&&$_GET['search_field']=='stock_name'?'selected':''; ?>>IPN</option>
					</select>
					<input type="text" size="25" name="search_value" <?php if (!empty($_GET['search_value'])) { ?> value="<?= $_GET['search_value']; ?>"<?php } ?>>
					<input type="submit" value="Go">
				</form>
				<a href="rma-list.php">Clear Search</a>
			</div>
			<div style="font-size:8px; float: right; margin-right: 20px;">
				<a href="#" id="print">print</a>
			</div>
			<div id="please_wait" class="please_wait">
				<div class="please_wait_inner"><img src="images/ajax-loader.gif"></div>
			</div>

			<div id="tabs" style="clear: both;">
				<ul class="noPrint">
					<li id="tabs-1" table_id="open-rmas" class="ui-tabs-selected"><a href="rma-list.php?tab=1<?= $search_string; ?>">My RMAs</a></li>
					<li id="tabs-4" table_id="my-closed-rmas"><a href="rma-list.php?tab=4<?= $search_string; ?>">My Closed RMAs</a></li>
					<li id="tabs-2" table_id="my-rmas"><a href="rma-list.php?tab=2<?= $search_string; ?>">Open RMAs</a></li>
					<li id="tabs-3" table_id="all-rmas"><a href="rma-list.php?tab=3<?= $search_string; ?>">All RMAs</a></li>
				</ul>
			</div>
		</div>
		<div style="clear: both;"></div>
	</body>
<html>
