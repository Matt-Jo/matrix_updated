<?php
$status = !empty($_GET['status'])?$_GET['status']:'';

$account_managers = ck_admin::get_account_managers(['ck_admin', 'sort_by_name']);
$sales_teams = ck_team::get_sales_teams();

$sort = NULL;

if (in_array($status, ['', ck_sales_order::STATUS_SHIPPED, ck_sales_order::STATUS_CANCELED])) {
	$page = !empty($_GET['page'])?$_GET['page']:1;
	$page_size = 75;
}
else $page = $page_size = NULL;

if (isset($_GET['customers_id'])) $customers_id = $_GET['customers_id'];
else $customers_id = NULL;

if (!empty($status)) {
	if ($status == ck_sales_order::STATUS_RTP) {
		$orders_status_ids = ck_sales_order::STATUS_RTP;
		// updated to show orders in ready to pick that are missing a shipping type
		$sort = function ($a, $b) {
			// order by field(CASE WHEN ot.external_id IS NULL THEN 9999 WHEN ss.orders_sub_status_name = 'Special Handling' THEN 999 ELSE ot.external_id END, 9999,999,17,18,19,20,21,22,23,25,26,27,28,29,9,1,2,3,4,5,7,8,49,9,12,13,14,15,48,31,32,33,34,38,41,43,52,47,50,51) ASC, o.orders_id DESC
			$a_shipping_method_id = $a->get_shipping_method('shipping_code');
			$b_shipping_method_id = $b->get_shipping_method('shipping_code');
			$a_substatus = $a->get_header('orders_sub_status');
			$b_substatus = $b->get_header('orders_sub_status');

			if (empty($a_shipping_method_id)) $acode = 9999;
			elseif ($a_substatus == ck_sales_order::$sub_status_map['RTP']['Special Handling']) $acode = 999;
			else $acode = $a_shipping_method_id;

			if (empty($b_shipping_method_id)) $bcode = 9999;
			elseif ($b_substatus == ck_sales_order::$sub_status_map['RTP']['Special Handling']) $bcode = 999;
			else $bcode = $b_shipping_method_id;

			$sort_order = [9999, 999, 47, 17, 18, 19, 20, 21, 22, 23, 25, 26, 27, 28, 29, 9, 1, 2, 3, 4, 5, 7, 8, 49, 9, 12, 13, 14, 15, 48, 31, 32, 33, 34, 38, 41, 43, 52, 50, 51];
			$idxa = array_search($acode, $sort_order);
			$idxb = array_search($bcode, $sort_order);

			if ($idxa === $idxb) {
				if ($a->id() > $b->id()) return -1;
				elseif ($a->id() < $b->id()) return 1;
				else return 0;
			}
			else {
				if ($idxa < $idxb) return -1;
				elseif ($idxa > $idxb) return 1;
				else return 0;
			}
		};
	}
	elseif ($status == ck_sales_order::STATUS_CST) {
		$orders_status_ids = ck_sales_order::STATUS_CST;

		$sort = function ($a, $b) {
			// order by field(COALESCE(ss.orders_sub_status_id, 9999), 1,21,2,16,17,19,18,11,15,4,5,9,20,6,3,8,13,14,12,7) ASC, o.orders_id DESC
			$a_substatus = $a->get_header('orders_sub_status');
			$b_substatus = $b->get_header('orders_sub_status');
			$sort_order = [NULL, 1, 21, 2, 16, 17, 19, 18, 11, 15, 4, 5, 9, 20, 6, 3, 8, 13, 14, 12, 7];
			$idxa = array_search($a_substatus, $sort_order);
			$idxb = array_search($b_substatus, $sort_order);

			if ($idxa === $idxb) {
				if ($a->id() > $b->id()) return -1;
				elseif ($a->id() < $b->id()) return 1;
				else return 0;
			}
			else {
				if ($idxa < $idxb) return -1;
				elseif ($idxa > $idxb) return 1;
				else return 0;
			}
		};
	}
	else {
		// specified status
		$orders_status_ids = $status;
		$sort = function ($a, $b) {
			// order by FIELD(CASE WHEN oss.orders_sub_status_id IS NULL THEN 0 ELSE oss.orders_sub_status_id END, 26, 24, 25, 0) ASC, o.orders_id DESC
			$a_substatus = $a->get_header('orders_sub_status');
			$b_substatus = $b->get_header('orders_sub_status');
			$sort_order = [26, 24, 25, NULL];
			$idxa = array_search($a_substatus, $sort_order);
			$idxb = array_search($b_substatus, $sort_order);

			if ($idxa === $idxb) {
				if ($a->id() > $b->id()) return -1;
				elseif ($a->id() < $b->id()) return 1;
				else return 0;
			}
			else {
				if ($idxa < $idxb) return -1;
				elseif ($idxa > $idxb) return 1;
				else return 0;
			}
		};
	}
}
else $orders_status_ids = NULL;

if (!empty($_GET['sales_team_id'])) $sales_team_id = $_GET['sales_team_id'];
else $sales_team_id = NULL;

//debug_tools::mark('Grab Orders', TRUE);
if (!empty($_GET['poquery'])) $order_list = ck_sales_order::get_orders_list_by_po($_GET['poquery'], $sort, $page, $page_size);
elseif (!empty($_GET['squery'])) $order_list = ck_sales_order::get_orders_list_by_customer($_GET['squery'], $sort, $page, $page_size);
else $order_list = ck_sales_order::get_orders_list($customers_id, $orders_status_ids, $sales_team_id, $sort, $page, $page_size);
//debug_tools::mark('Orders Grabbed', TRUE);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
 	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<link rel="stylesheet" type="text/css" href="serials/serials.css">
	<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
	<link rel="stylesheet" type="text/css" href="css/shipaddrtrack.css">
	<link rel="stylesheet" type="text/css" href="/admin/static/admin-styles.css">
	<style>
		#page-body { font-size:10px; }
		h3.page-heading { font-size:18px; display:inline-block; margin-right:150px; margin-bottom:3px; }

		.order-list-forms { font-weight:bold; font-size:11px; color:#727272; display:inline-block; }

		.status_tabs { margin:0px; padding:0px; font-size:small; font-weight:bold; color:#fff; height:26px; white-space:nowrap; width:100%; background-color: #0f4b96; border-style:solid; border-width:0px 0px 10px 0px; border-color:#9bbdca; }
		.status_tabs li { display:inline-block; padding:0px; }
		.status_tabs a { display:block; text-decoration:none; padding:7px; color:#fff; font-weight:bold; }
		.status_tabs a:link { color:#fff; font-weight:bold; }
		.status_tabs a:visited { color:#fff; font-weight:bold; }
		.status_tabs a:hover, .status_tabs li.selected a { background-color:#9bbdca; font-weight:bold; }

		.warehouse-tab { margin-left:40px; }

		#orddtl { visibility: hidden; border: 1px #000 solid; padding: 5px; font: 10px Verdana; }

		#print-all-button { font-size: 9px; color: #000; font-weight: normal; }
		#print-all-button:hover { font-size: 9px; color: #000; cursor: pointer;	font-weight: normal; }

		.orders-list { border:1px solid #000; border-collapse:collapse; width:100%; }
		/*.orders-list thead .list-statuses td { padding:0px; }*/
		.orders-list th, .orders-list td { white-space:nowrap; padding:3px 0px; }
		.orders-list .list-header th { background-color:#c9c9c9; color:#fff; text-align:center; }
		/*.orders-list .list-header th:first-child { padding-left:3px; }*/
		/*.orders-list tbody td:first-child { padding-left:3px; }*/
		.orders-list tbody td { background-color:#f0f1f1; }
		.orders-list tbody tr:hover td:not(.followup) { background-color:#fff; }
		.print-picklists input { display:none; }
		.status-2 .print-picklists input, .status-7 .print-picklists input { display:initial; }
		.followup.good { font-weight:bold; background-color:#0f0; }
		.followup.soon { background-color:#ff0; }
		.followup.due { background-color:#c00; color:#fff; }

		.order-list-context-header-row th { padding-bottom:0px; }
		h4.order-list-context-header {  border-style:solid; border-color:#9a9a9a; border-width:2px 0px 1px 0px; padding:7px 0px; margin:7px 0px 0px 0px; font-size:12px; font-weight:bold; }
		h4.order-list-context-header.error { background-color:#fcc; }

		.availability-status.ready { color:#00a000; }
		.availability-status.alloc-ready { color:#00f; }
		.availability-status.allocated { color:#000; }
		.availability-status.opportunity { color:#f90; }
		.availability-status.stock-issue { color:#f00; }
		.availability-status.error { color:#90c; }

		.order-expanded-details { visibility:hidden; display:none; }

		.orders-list .order-summary { /*cursor:pointer;*/ }
		.orders-list .order-summary th, .orders-list .order-summary td { text-align:center; }
		/*.orders-list .order-no { width:70px; }*/
		.orders-list .order-serialized { font-size:14px; font-weight:bold; color:#f00; }
		.orders-list .order-serialized.allocated { color:#0f0; }
		/*.orders-list .preview { padding-right:3px; }*/
		/*.orders-list .customer {}*/
		/*.orders-list .company { width:200px; white-space:normal; }*/
		/*.orders-list .total { text-align:center; }*/
		/*.orders-list .margin { padding-left:50px; }*/
		/*.orders-list .orders { text-align:center; }*/
		/*.orders-list .rep { padding-left:10px; }*/
		/*.orders-list .team { text-align:center; }*/
		/*.orders-list .ship-method { padding-left:20px; }*/
		/*.orders-list .weight { text-align:center; }*/
		/*.customer-po-column { text-align:center !important; }*/
		/*.orders-list .pay-method {}*/
		/*.orders-list .date-purchased { text-align:center; }*/
		/*.orders-list .followup { text-align:center; }*/

		/*.orders-list .sortable { cursor:pointer; padding-right:24px; }
		.orders-list .sortable:hover { background-color:#ff9; color:#000; padding-right:8px; }
		.orders-list .sortable.sorted { background-color:#4ff; color:#000; padding-right:8px; }
		.orders-list .sortable .column-sorter { display:none; color:#888; width:16px; text-align:right; }
		.orders-list .sortable:not(.sorted):hover .column-sorter.desc { display:inline-block; }
		.orders-list .sortable.sorted.asc .column-sorter.asc { display:inline-block; }
		.orders-list .sortable.sorted.desc .column-sorter.desc { display:inline-block; }*/
	</style>
	<script>
		function popupWindow(url, width, height) {
			width = width || 800;
			height = height || 800;
			window.open(url, 'popupWindow', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width='+width+',height='+height+',screenX=150,screenY=150,top=150,left=150')
		}

		function dispCmmt (id, evt) {
			evt = window.event ? window.event : evt;
			var x = evt.pageX ? evt.pageX : (document.getElementById(id).offsetLeft + evt.clientX);
			var y = evt.pageY ? evt.pageY : (document.getElementById(id).offsetTop + evt.clientY);

			var dtl = document.getElementById('orddtl');
			dtl.style.backgroundColor='#fff';
			dtl.style.zIndex="300";
			dtl.style.position='absolute';
			dtl.style.top=y+'px';
			dtl.style.scrollTop=y+'px';
			dtl.style.left=(x+25)+'px';
			dtl.style.width='700px';
			dtl.innerHTML = '';
			dtl.style.visibility = 'visible';
			jQuery('#orddtl').load('order_notes.php', {'order_id': id});
			return false;
		}

		function hideCmmt () {
			document.getElementById('orddtl').style.visibility='hidden';
		}
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<div id="orddtl"></div>
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top" id="page-body">
				<h3 class="page-heading">Orders</h3>
				<div class="order-list-forms">
					<form name="orders" action="/admin/orders_new.php" method="get">
						<input type="hidden" name="action" value="edit">
						<input type="hidden" name="sales_team_id" value="<?= @$_GET['sales_team_id']; ?>">
						[ Order ID:
						<input type="text" name="oID" size="12" id="search-order-id">
						<input type="submit" value="Go"> ]
					</form>
					<form name="orders_name_email" action="/admin/orders_new.php" method="get">
						<input type="hidden" name="action" value="edit">
						[ Name/Email:
						<input type="text" name="squery" size="12" value="<?= @$_GET['squery']; ?>" id="search-order-nameemail">
						<input type="submit" value="Go"> ]
					</form>
					<form name="orders_po" action="/admin/orders_new.php" method="get">
						<input type="hidden" name="action" value="edit">
						[ Customer PO#:
						<input type="text" name="poquery" size="12" value="<?= @$_GET['poquery']; ?>" id="search-order-po">
						<input type="submit" value="Go"> ]
					</form>
					<form method="get" action="/admin/orders_new.php" id="sales_team_filter">
						<input type="hidden" name="status" value="<?= $status; ?>">
						[ Filter Sales Team:
						<select name="sales_team_id" id="sales_team_select">
							<option value="">All</option>
							<option value="NONE" <?= @$_GET['sales_team_id']=='NONE'?'selected':''; ?>>None</option>
							<?php foreach ($sales_teams as $sales_team) { ?>
							<option value="<?= $sales_team->id(); ?>" <?= @$_GET['sales_team_id']==$sales_team->id()?'selected':''; ?>><?= $sales_team->get_header('label'); ?></option>
							<?php } ?>
						</select> ]
						<script>
							jQuery('#sales_team_select').change(function() {
								jQuery('#sales_team_filter').submit();
							});
						</script>
					</form>
				</div>
				<form action="/admin/pack_and_pick_list.php" id="print-bulk-pick-pack" data-status="<?= $status; ?>" method="get">
					<table class="orders-list main-list">
						<thead>
							<tr class="list-statuses">
								<?php $colspan = 15;
								if ($status != 3) $colspan++;
								if (in_array($status, [11, 5])) $colspan += 2; ?>
								<td colspan="<?= $colspan; ?>">
									<ul class="status_tabs">
										<li class="<?= $status==2?'selected':''; ?>"><a href="/admin/orders_new.php?status=2&sales_team_id=<?= @$_GET['sales_team_id']; ?>">Ready To Pick</a></li>
										<li class="<?= $status==11?'selected':''; ?>"><a href="/admin/orders_new.php?status=11&sales_team_id=<?= @$_GET['sales_team_id']; ?>">Customer service</a></li>
										<li class="<?= $status==5?'selected':''; ?>"><a href="/admin/orders_new.php?status=5&sales_team_id=<?= @$_GET['sales_team_id']; ?>">Backorder</a></li>
										<li class="<?= $status==6?'selected':''; ?>"><a href="/admin/orders_new.php?status=6&sales_team_id=<?= @$_GET['sales_team_id']; ?>">Canceled</a></li>
										<li class="<?= $status==3?'selected':''; ?>"><a href="/admin/orders_new.php?status=3&sales_team_id=<?= @$_GET['sales_team_id']; ?>">Shipped</a></li>
										<li class="warehouse-tab <?= $status==7?'selected':''; ?>"><a href="/admin/orders_new.php?status=7&sales_team_id=<?= @$_GET['sales_team_id']; ?>">Warehouse</a></li>
										<li class="<?= empty($status)?'selected':''; ?>"><a href="/admin/orders_new.php?sales_team_id=<?= @$_GET['sales_team_id']; ?>">View all</a></li>
									</ul>
								</td>
							</tr>
							<tr class="list-header status-<?= $status; ?>">
								<th class="order-no">Order No.</th>
								<th class="print-picklists" colspan="3"><input type="submit" value="PRINT"></th>
								<th class="customer">Customer</th>
								<th class="company">Company</th>
								<th class="total">Order Total</th>
								<?php if ($status != 3) { ?>
								<th class="margin">Estimated Margin</th>
								<?php } ?>
								<th class="customer-po-column">PO #</th>
								<th class="orders">Orders</th>
								<th class="rep">Account Rep</th>
								<th class="team">Sales Team</th>
								<th class="ship-method">Ship Method</th>
								<th class="weight">Weight</th>
								<th class="pay-method">Payment Method</th>
								<th class="date-purchased sortable">Date Purchased</th>
								<?php if (in_array($status, [11, 5])) { ?>
								<th class="followup sortable">Followup Date</th>
								<th class="followup sortable">Promised Ship Date</th>
								<?php } ?>
							</tr>
						</thead>
						<?php $astatus = [];
						$astatus[ck_sales_order::ASTATUS_READY] = 'ready';
						$astatus[ck_sales_order::ASTATUS_ALLOC_READY] = 'alloc-ready';
						$astatus[ck_sales_order::ASTATUS_ALLOCATED] = 'allocated';
						$astatus[ck_sales_order::ASTATUS_OPPORTUNITY] = 'opportunity';
						$astatus[ck_sales_order::ASTATUS_STOCK_ISSUE] = 'stock-issue';
						$astatus[ck_sales_order::ASTATUS_ERROR] = 'error';

						$context = NULL;
						$new_context = FALSE;
						$context_label = NULL;

						//debug_tools::mark('start loop', TRUE);
						foreach ($order_list['orders'] as $idx => $sales_order) {
							$order = $sales_order->get_header();
							$shipping_method = $sales_order->get_shipping_method();
							$serialized_status = $sales_order->get_serialized_status();

							$context_error = FALSE;
							if ($status == 2) {
								if (empty($shipping_method['shipping_code'])) $shipping_code = 'MISSING';
								elseif ($order['orders_sub_status'] == ck_sales_order::$sub_status_map['RTP']['Special Handling']) $shipping_code = 'SPECIAL';
								elseif (empty($shipping_method['carrier'])) $shipping_code = $shipping_method['method_name'];
								elseif (!in_array($shipping_method['carrier'], ['UPS', 'FedEx'])) $shipping_code = $shipping_method['carrier'];
								else $shipping_code = $shipping_method['shipping_code'];

								if ($shipping_code != $context) {
									$new_context = TRUE;
									$context = $shipping_code;
									switch ($shipping_code) {
										case 'MISSING':
											$context_error = TRUE;
											$context_label = 'MISSING SHIP TYPE (CONTACT CUSTOMER SERVICE)';
											break;
										case 'SPECIAL':
											$context_label = 'Special Handling Required';
											break;
										default:
											if (!in_array($shipping_method['carrier'], ['UPS', 'FedEx'])) $context_label = $shipping_code;
											elseif (in_array($shipping_code, [23])) $context_label = 'UPS Ground';
											elseif (in_array($shipping_code, [17, 18, 19, 20, 21, 22, 49])) $context_label = 'UPS Express';
											elseif (in_array($shipping_code, [25, 26, 27, 28, 29])) $context_label = 'UPS International';
											elseif (in_array($shipping_code, [9])) $context_label = 'FedEx Ground';
											elseif (in_array($shipping_code, [1, 2, 3, 4, 5, 7, 8])) $context_label = 'FedEx Express';
											elseif (in_array($shipping_code, [12, 13, 14, 15])) $context_label = 'FedEx International';
											else {
												$context_label = $shipping_method['carrier'].' Unknown';
												$context_error = TRUE;
											}
											break;
									}
								}
								else $new_context = FALSE;
							}
							elseif (empty($status)) {
								if ($order['orders_status'] != $context) {
									$new_context = TRUE;
									$context = $order['orders_status'];
									$context_label = $order['orders_status_name'];
								}
								else $new_context = FALSE;
							}
							elseif (in_array($status, [ck_sales_order::STATUS_CST, ck_sales_order::STATUS_WAREHOUSE])) {
								if ($order['orders_sub_status'] != $context) {
									$new_context = TRUE;
									$context = $order['orders_sub_status'];
									$context_label = $order['orders_sub_status_name'];
								}
								else $new_context = FALSE;
							}
							elseif ($idx == 0) { ?>
						<tbody>
							<?php }

							if ($new_context) {
								flush(); ?>
						</tbody>
						<tbody>
							<tr class="order-list-context-header-row">
								<th colspan="<?= $colspan; ?>"><h4 class="order-list-context-header <?= $context_error?'error':''; ?>"><?= $context_label; ?></h4></th>
							</tr>
							<?php }
							elseif ($idx%9 == 0) flush(); ?>
							<tr class="order-summary" data-target="/admin/orders_new.php?oID=<?= $sales_order->id(); ?>&action=edit">
								<td class="order-no availability-status <?= $astatus[$sales_order->get_availability_status()]; ?>" data-orders-id="<?= $sales_order->id(); ?>"><?= $sales_order->id(); ?></td>
								<td class="order-serialized <?= $serialized_status['allocated']?'allocated':''; ?>"><?= $serialized_status['serialized']?'S':''; ?></td>
								<td>
									<!--Display the print button only on the ready to pick tab (status 2) and change button color to red based on blind order value-->
									<?php if (in_array($status, [2, 7])) { ?>
									<input type="checkbox" class="print-control" name="orders_ids[]" value="<?= $sales_order->id(); ?>">
										<?php if ($order['dropship']) { ?>
									<span style="color:#f00;">[B]</span>
										<?php }
									} ?>
								</td>
								<td class="preview"><a href="/admin/orders_new.php?oID=<?= $sales_order->id(); ?>&action=edit&<?= http_build_query(CK\fn::filter_request($_GET, ['oID', 'action'])); ?>">&#10148;</a></td>
								<td class="customer"><?= $order['customers_name']; ?></td>
								<td class="company"><?= $order['customers_company']; ?></td>
								<td class="total"><?= CK\text::monetize($sales_order->get_simple_totals('total')); ?></td>
								<?php if ($status != ck_sales_order::STATUS_SHIPPED) { ?>
								<td class="margin">
									<?php $margin = $sales_order->get_estimated_margin_dollars('product', TRUE);
									echo $margin=='Incalculable'?$margin:($margin.' ('.$sales_order->get_estimated_margin_pct('product', TRUE).')'); ?>
								</td>
								<?php } ?>
								<td class="customer-po-column"><?= $sales_order->get_header('purchase_order_number'); ?></td>
								<td class="orders"><a href="/admin/orders_new.php?customers_id=<?= $order['customers_id']; ?>">[<?= ck_customer2::get_customer_order_count($order['customers_id']); ?>]</a></td>
								<td class="rep"><?= $sales_order->has_account_manager()?$sales_order->get_account_manager()->get_name():''; ?></td>
								<td class="team"><?= $sales_order->has_sales_team()?$sales_order->get_sales_team()->get_header('label'):''; ?></td>
								<td class="ship-method"><?= $sales_order->get_shipping_method_display('short'); ?></td>
								<td class="weight"><?= $sales_order->get_estimated_shipped_weight(); ?></td>
								<td class="pay-method"><?= $order['payment_method_label']; ?></td>
								<td class="date-purchased"><?= $order['date_purchased']->format('m/d/Y h:i a'); ?></td>
								<?php if (in_array($status, [ck_sales_order::STATUS_BACKORDER, ck_sales_order::STATUS_CST])) {
									$now = new DateTime();
									$now->setTime(0, 0, 0);
									$soon = new DateTime();
									$soon->setTime(0, 0, 0);
									$soon->add(new DateInterval('P2D'));

									while ($soon->format('N') >= 6) $soon->add(new DateInterval('P1D'));

									$followup_class = '';
									$promised_ship_class = '';

									if (!empty($order['followup_date'])) {
										if ($order['followup_date'] > $soon) $followup_class = 'good';
										elseif ($order['followup_date'] > $now) $followup_class = 'soon';
										else $followup_class = 'due';
									}
									if (!empty($order['promised_ship_date'])) {
										if ($order['promised_ship_date'] > $soon) $promised_ship_class = 'good';
										elseif ($order['promised_ship_date'] > $now) $promised_ship_class = 'soon';
										else $promised_ship_class = 'due';
									}
									?>
								<td class="followup <?= $followup_class; ?>" align="center"><?= !empty($order['followup_date'])?$order['followup_date']->format('m/d/Y'):''; ?></td>
								<td class="followup <?= $promised_ship_class; ?>" align="center"><?= !empty($order['promised_ship_date'])?$order['promised_ship_date']->format('m/d/Y'):''; ?></td>
								<?php } ?>
							</tr>
							<?php
						}
						//debug_tools::mark('end loop', TRUE); ?>
						</tbody>
						<tfoot>
							<?php if (in_array($status, [ck_sales_order::STATUS_RTP, ck_sales_order::STATUS_WAREHOUSE])) { ?>
							<tr>
								<th colspan="2" style="text-align:right;">All:</th>
								<td><input type="checkbox" id="print-all"></td>
								<td colspan="<?= $colspan-3; ?>"></td>
							</tr>
							<?php }
							elseif (in_array($status, ['', ck_sales_order::STATUS_SHIPPED, ck_sales_order::STATUS_CANCELED])) { ?>
							<tr>
								<td colspan="4">
									Displaying
									<strong><?= (($page - 1) * $page_size) + 1; ?></strong> to <strong><?= min($page * $page_size, $order_list['result_count']); ?></strong>
									(of <strong><?= $order_list['result_count']; ?></strong> orders)
								</td>
								<td colspan="<?= $colspan - 4; ?>" style="text-align:right;">
									<?php $page_count = ceil($order_list['result_count'] / $page_size);
									
									if ($page == 1) { ?>
									&lt;&lt;
									<?php }
									else { ?>
									<a href="/admin/orders_new.php?<?= http_build_query(CK\fn::filter_request($_GET, ['page'])); ?>&page=<?= $page-1; ?>">&lt;&lt;</a>
									<?php } ?>

									<select id="order-list-pagination" data-query="<?= http_build_query(CK\fn::filter_request($_GET, ['page'])); ?>">
										<?php for ($i=1; $i<=$page_count; $i++) { ?>
										<option value="<?= $i; ?>" <?= $i==$page?'selected':''; ?>><?= $i; ?></option>
										<?php } ?>
									</select>

									<?php if ($page == $page_count) { ?>
									&gt;&gt;
									<?php }
									else { ?>
									<a href="/admin/orders_new.php?<?= http_build_query(CK\fn::filter_request($_GET, ['page'])); ?>&page=<?= $page+1; ?>">&gt;&gt;</a>
									<?php } ?>
								</td>
							</tr>
							<?php } ?>
						</tfoot>
					</table>
				</form>
				<?php if ($status == 2) { ?>
				<h3 class="page-heading">Orders in other Statuses</h3>
				<table class="orders-list">
					<thead>
						<tr class="list-header status-<?= $status; ?>">
							<th class="order-no">Order No.</th>
							<th colspan="2"></th>
							<th class="customer">Customer</th>
							<th class="company">Company</th>
							<th class="total">Order Total</th>
							<th class="margin">Estimated Margin</th>
							<th class="orders">Orders</th>
							<th class="rep">Account Rep</th>
							<th class="team">Sales Team</th>
							<th class="ship-method">Ship Method</th>
							<th class="weight">Weight</th>
							<th class="pay-method">Payment Method</th>
							<th class="date-purchased">Date Purchased</th>
						</tr>
					</thead>
					<tbody>
						<?php $order_list = ck_sales_order::get_orders_list(NULL, ck_sales_order::STATUS_CST, $sales_team_id);
						//$orders = prepared_query::fetch('SELECT  FROM  WHERE  (1, 9) ORDER BY ss.orders_sub_status_id ASC, o.orders_id ASC', cardinality::SET);

						usort($order_list['orders'], function ($a, $b) {
							if ($a->get_header('orders_sub_status') == $b->get_header('orders_sub_status')) return 0;
							elseif ($a->get_header('orders_sub_status') < $b->get_header('orders_sub_status')) return -1;
							else return 1;
						});

						$context = NULL;
						$new_context = FALSE;
						$context_label = NULL;
						$context_error = FALSE;

						foreach ($order_list['orders'] as $sales_order) {
							$order = $sales_order->get_header();
							if (!in_array($order['orders_sub_status'], [1, 9])) continue;
							$shipping_method = $sales_order->get_shipping_method();
							$serialized_status = $sales_order->get_serialized_status();

							if ($order['orders_sub_status'] != $context) {
								$new_context = TRUE;
								$context = $order['orders_sub_status'];
								$context_label = $order['orders_sub_status_name'];
							}
							else $new_context = FALSE;

							if ($new_context) { ?>
						<tr class="order-list-context-header-row">
							<th colspan="14"><h4 class="order-list-context-header <?= $context_error?'error':''; ?>"><?= $context_label; ?></h4></th>
						</tr>
							<?php } ?>
						<tr class="order-summary" data-target="/admin/orders_new.php?oID=<?= $sales_order->id(); ?>&action=edit">
							<td class="order-no availability-status <?= $astatus[$sales_order->get_availability_status()]; ?>" data-orders-id="<?= $sales_order->id(); ?>"><?= $sales_order->id(); ?></td>
							<td class="order-serialized <?= $serialized_status['allocated']?'allocated':''; ?>"><?= $serialized_status['serialized']?'S':''; ?></td>
							<td class="preview"><a href="/admin/orders_new.php?oID=<?= $sales_order->id(); ?>&action=edit&<?= http_build_query(CK\fn::filter_request($_GET, ['oID', 'action'])); ?>">&#10148;</a><!--img src="/admin/images/icons/preview.gif" alt="Preview" title=" Preview "--></td>
							<td class="customer"><?= $order['customers_name']; ?></td>
							<td class="company"><?= $order['customers_company']; ?></td>
							<td class="total"><?= CK\text::monetize($sales_order->get_simple_totals('total')); ?></td>
							<td class="margin">
								<?php $margin = $sales_order->get_estimated_margin_dollars('product', TRUE);
								echo $margin=='Incalculable'?$margin:($margin.' ('.$sales_order->get_estimated_margin_pct('product', TRUE).')'); ?>
							</td>
							<td class="orders"><a href="/admin/orders_new.php?customers_id=<?= $order['customers_id']; ?>">[<?= ck_customer2::get_customer_order_count($order['customers_id']); ?>]</a></td>
							<td class="rep"><?= $sales_order->has_account_manager()?$sales_order->get_account_manager()->get_name():''; ?></td>
							<td class="team"><?= $sales_order->has_sales_team()?$sales_order->get_sales_team()->get_header('label'):''; ?></td>
							<td class="ship-method"><?= $sales_order->get_shipping_method_display('short'); ?></td>
							<td class="weight"><?= $sales_order->get_estimated_shipped_weight(); ?></td>
							<td class="pay-method"><?= $order['payment_method_label']; ?></td>
							<td class="date-purchased"><?= $order['date_purchased']->format('m/d/Y h:i a'); ?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
				<?php } ?>
				<script>
					var print_reload_timer;
					jQuery('#print-bulk-pick-pack').submit(function(e) {
						e.preventDefault();
						var orders = jQuery(this).serialize();
						var status = jQuery('#print-bulk-pick-pack').attr('data-status');
						console.log(orders);

						if (orders) {
							jQuery('.print-control:checked').remove();
							popupWindow('/admin/pack_and_pick_list.php?status='+status+'&'+orders);
						}
					});

					jQuery('#print-all').click(function(e) {
						jQuery('.print-control').attr('checked', jQuery(this).is(':checked'));
						clearTimeout(print_reload_timer);
					});

					jQuery('.print-control').click(function(e) {
						e.stopPropagation();
						clearTimeout(print_reload_timer);
					});

					jQuery('#order-list-pagination').change(function() {
						window.location = '/admin/orders_new.php?'+jQuery(this).attr('data-query')+'&page='+jQuery(this).val();
					});

					/*jQuery('.order-summary').click(function() {
						window.location = jQuery(this).attr('data-target');
					});*/

					jQuery('.order-summary .order-no').hover(
						function(e) { dispCmmt(jQuery(this).attr('data-orders-id'), e); },
						function(e) { hideCmmt(); }
					);

					// this is mostly copied from ck-table-manager sorting - we don't want the style changes and the functionality is a little different, so we're just plopping it down here
					// naked block for scoping
					/*{
						jQuery('.orders-list.main-list .list-header th').each(function(idx) {
							jQuery(this).attr('data-col-idx', idx);
						});

						jQuery('.orders-list.main-list .list-header th.sortable').attr('data-sortdir', '').append('<span class="column-sorter asc">&#x25B2;</span><span class="column-sorter desc">&#x25BC;</span>');

						let $sortable_groups = [];

						jQuery('.orders-list.main-list tbody').each(function() {
							let sortable_rows = { $body: jQuery(this), $header: jQuery(jQuery(this).find('tr.order-list-context-header-row')[0]), $list: [] };
							jQuery(this).find('tr.order-summary').each(function() {
								sortable_rows.$list.push(jQuery(this));
							});
							$sortable_groups.push(sortable_rows);
						});

						let sort_add = false;
						let sort_column_order = [];

						jQuery('body').keydown(function(e) {
							let key = e.keyCode || e.which;
							if (e.ctrlKey) sort_add = true;
						});

						jQuery('body').keyup(function(e) {
							sort_add = false;
						});

						jQuery('th.sortable').click(function() {
							let $col = jQuery(this);
							let dir = $col.attr('data-sortdir');

							if (!sort_add) {
								jQuery('.orders-list.main-list .sorted').removeClass('sorted').removeClass('asc').removeClass('desc').attr('data-sortdir', '');
								sort_column_order = [];
							}
							else {
								$col.removeClass(dir).attr('data-sortdir', '');
							}

							let newdir = dir=='asc'?'desc':'asc';

							$col.addClass('sorted').addClass(newdir).attr('data-sortdir', newdir);
							let column_idx = parseInt($col.attr('data-col-idx'))+2;

							if (sort_column_order.indexOf(column_idx) === -1) sort_column_order.push(column_idx);

							for (let i=0; i<$sortable_groups.length; i++) {
								$sortable_groups[i].$list.sort(function ($a, $b) {
									let val1, val2;

									for (let i=0; i<sort_column_order.length; i++) {
										let lookup_idx = sort_column_order[i];

										let coldir = jQuery(jQuery('.orders-list.main-list .list-header').find('th')[lookup_idx]).data('sortdir');

										let modifier = coldir=='asc'?1:-1;

										val1 = jQuery.trim(jQuery($a.find('th, td')[lookup_idx]).text());
										val2 = jQuery.trim(jQuery($b.find('th, td')[lookup_idx]).text());

										val1 = new Date(val1);
										val2 = new Date(val2);

										if (val1 < val2) return -1 * modifier;
										else if (val1 > val2) return 1 * modifier;
									}

									return 0;
								});

								console.log($sortable_groups[i].$list);

								$sortable_groups[i].$body.html('');
								if ($sortable_groups[i].$header) $sortable_groups[i].$body.append($sortable_groups[i].$header);
								for (let j=0; j<$sortable_groups[i].$list.length; j++) {
									$sortable_groups[i].$body.append($sortable_groups[i].$list[j]);
								}
							}
						});
					}*/
				</script>
				<?php /*debug_tools::mark('Stop');*/ ?>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
</body>
</html>
