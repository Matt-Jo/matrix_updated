<?php require_once('includes/application_top.php');

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;

if (!empty($_GET['start_date'])) $start_date = new DateTime($_GET['start_date']);
else $start_date = new DateTime('first day of this month');
if (!empty($_GET['end_date'])) $end_date = new DateTime($_GET['end_date']);
else $end_date = new DateTime('last day of this month');

$date_group = !empty($_GET['date_group'])?$_GET['date_group']:'Day';
$segment = !empty($_GET['segment'])?$_GET['segment']:NULL;
$channel = !empty($_GET['channel'])?$_GET['channel']:NULL;
$sales_rep_id = !empty($_GET['sales_rep_id'])?$_GET['sales_rep_id']:NULL;
$sales_team_id = !empty($_GET['sales_team_id'])?$_GET['sales_team_id']:NULL;
$distribution_center_id = isset($_GET['distribution_center_id'])?$_GET['distribution_center_id']:'ALL';

$tab = !empty($_GET['tab'])?$_GET['tab']:NULL;

switch ($date_group) {
	case 'Week':
		$column_title = 'Weekly';
		$date_group_var = 'WEEKOFYEAR';
		break;
	case 'Month':
		$column_title = 'Monthly';
		$date_group_var = 'MONTH';
		break;
	case 'Day':
	default:
		$column_title = 'Daily';
		$date_group_var = 'DAYOFYEAR';
		break;
}

//$filter = [];
$parameters = [];

//$filter[] = 'DATE(ai.inv_date) >= :start_date';
$parameters[':start_date'] = $start_date->format('Y-m-d');
//$filter[] = 'DATE(ai.inv_date) <= :end_date';
$parameters[':end_date'] = $end_date->format('Y-m-d');

if (!empty($segment)) {
	//$filter[] = 'c.customer_segment_id = :customer_segment_id';
	$parameters[':customer_segment_id'] = $segment;
}

if (!empty($channel)) {
	//$filter[] = 'IFNULL(o.channel, o2.channel) = :channel';
	$parameters[':channel'] = $channel;
}

if (!empty($sales_rep_id)) {
	//if ($sales_rep_id == 'NONE') $filter[] = 'NULLIF(o.orders_sales_rep_id, 0) IS NULL AND NULLIF(o2.orders_sales_rep_id, 0) IS NULL';
	//else {
		//$filter[] = 'IFNULL(o.orders_sales_rep_id, o2.orders_sales_rep_id) = :sales_rep_id';
		$parameters[':sales_rep_id'] = $sales_rep_id;
	//}
}

if (!empty($sales_team_id)) {
	//if ($sales_team_id == 'NONE') $filter[] = 'NULLIF(o.sales_team_id, 0) IS NULL AND NULLIF(o2.sales_team_id, 0) IS NULL';
	//else {
		//$filter[] = 'IFNULL(o.sales_team_id, o2.sales_team_id) = :sales_team_id';
		$parameters[':sales_team_id'] = $sales_team_id;
	//}
}

if ($distribution_center_id != 'ALL') {
	//$filter[] = 'IFNULL(o.distribution_center_id, o2.distribution_center_id) = :distribution_center_id';
	$parameters[':distribution_center_id'] = $distribution_center_id;
}

/*$tab_filter = [
	'sales' => 'ai.inv_order_id IS NOT NULL AND ai.rma_id IS NULL AND IFNULL(ai.credit_memo, 0) = 0',
	'rma' => '((ai.inv_order_id IS NOT NULL AND ai.credit_memo = 1) OR ai.rma_id IS NOT NULL)',
	'term' => 'ai.inv_order_id IS NOT NULL AND NULLIF(ai.rma_id, 0) IS NULL AND o.payment_method_id IN (5, 6, 7, 15)',
	'all' => '(ai.inv_order_id IS NOT NULL OR ai.rma_id IS NOT NULL)'
];*/

// top admin, accounting, HR, purchasing mgr, purchasing, purchasing/accounting, sales/purchasing, going for broker, a team, money team, flying solo, sales/accounting, sales mgr
$allow_expand_invoices = in_array($_SESSION['perms']['admin_groups_id'], [1, 11, 12, 20, 7, 29, 17, 5, 10, 21, 30, 31, 24]);

if ($action == 'get_invoices' && $allow_expand_invoices) {
	/*switch ($tab) {
		case 'sales':
			$filter[] = $tab_filter['sales'];
			break;
		case 'rma':
			$filter[] = $tab_filter['rma'];
			break;
		case 'term':
			$filter[] = $tab_filter['term'];
			break;
		default:
			$filter[] = $tab_filter['all'];
			break;
	}*/

	//$filter[] = 'YEAR(ai.inv_date) = :year';
	$parameters[':year'] = $_GET['year'];
	//$filter[] = $date_group_var.'(ai.inv_date) = :range';
	$parameters[':range'] = $_GET['range'];

	//$filter = implode(' AND ', $filter);

	$criteria = [
		':start_date' => NULL,
		':end_date' => NULL,
		':customer_segment_id' => NULL,
		':channel' => NULL,
		':sales_rep_id' => NULL,
		':sales_team_id' => NULL,
		':distribution_center_id' => NULL,
		':tab' => $tab,
	];
	$criteria = array_merge($criteria, $parameters);

	$invoices = prepared_query::fetch("SELECT * FROM (SELECT DISTINCT ai.invoice_id, ai.inv_order_id, r.order_id as rma_order_id, ai.rma_id, ai.customer_id, CONCAT_WS(' ', a.admin_firstname, a.admin_lastname) as account_manager, DATE(ai.inv_date) as inv_date, IFNULL(ai_total.total, 0) - IFNULL(ai_tax.total_tax, 0) - IFNULL(ai_shipping.total_shipping, 0) as product_total, IFNULL(ai_shipping.total_shipping, 0) as shipping_total, IFNULL(ai_tax.total_tax, 0) as tax_total, IFNULL(ai_total.total, 0) as invoice_total, IFNULL(ii.total_cost, 0) as product_cost_total, IFNULL(ai_total.total, 0) - IFNULL(ai_tax.total_tax, 0) - IFNULL(ai_shipping.total_shipping, 0) - IFNULL(ii.total_cost, 0) as product_margin_total, IFNULL(ai_total.total, 0) - IFNULL(ai_tax.total_tax, 0) as revenue_total, IFNULL(ai_total.total, 0) - IFNULL(ai_tax.total_tax, 0) - IFNULL(ii.total_cost, 0) as revenue_margin_total, CASE WHEN NULLIF(ai.inv_order_id, 0) IS NOT NULL AND NULLIF(ai.rma_id, 0) IS NULL AND IFNULL(ai.credit_memo, 0) = 0 THEN 'sales' WHEN NULLIF(ai.rma_id, 0) IS NOT NULL OR ai.credit_memo = 1 THEN 'rma' ELSE 'all' END as invoice_type, IF(o.payment_method_id IN (5, 6, 7, 15), 'terms', 'no-terms') as payment_method FROM acc_invoices ai LEFT JOIN customers c ON ai.customer_id = c.customers_id LEFT JOIN admin a ON a.admin_id = c.account_manager_id LEFT JOIN orders o ON ai.inv_order_id = o.orders_id AND o.orders_status NOT IN (6, 9) LEFT JOIN rma r ON r.id = ai.rma_id LEFT JOIN orders o2 ON r.order_id = o2.orders_id  LEFT JOIN (SELECT ait.invoice_id, SUM(ait.invoice_total_price) as total_shipping FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_shipping' WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) GROUP BY ait.invoice_id) ai_shipping ON ai.invoice_id = ai_shipping.invoice_id  LEFT JOIN (SELECT ait.invoice_id, SUM(ait.invoice_total_price) as total_tax FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_tax' WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) GROUP BY ait.invoice_id) ai_tax ON ai.invoice_id = ai_tax.invoice_id  LEFT JOIN (SELECT ait.invoice_id, SUM(ait.invoice_total_price) as total FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_total' WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) GROUP BY ait.invoice_id) ai_total ON ai.invoice_id = ai_total.invoice_id LEFT JOIN (SELECT ii.invoice_id, SUM(ii.orders_product_cost_total) as total_cost FROM acc_invoices ai JOIN acc_invoice_items ii ON ai.invoice_id = ii.invoice_id JOIN products_stock_control psc ON ii.ipn_id = psc.stock_id WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) GROUP BY ii.invoice_id) ii ON ai.invoice_id = ii.invoice_id WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) AND (NULLIF(ai.inv_order_id, 0) IS NOT NULL OR NULLIF(ai.rma_id, 0) IS NOT NULL) AND (:customer_segment_id IS NULL OR c.customer_segment_id = :customer_segment_id) AND (:channel IS NULL OR IFNULL(o.channel, o2.channel) = :channel) AND (:sales_rep_id IS NULL OR (:sales_rep_id = 'NONE' AND NULLIF(o.orders_sales_rep_id, 0) IS NULL AND NULLIF(o2.orders_sales_rep_id, 0) IS NULL) OR IFNULL(o.orders_sales_rep_id, o2.orders_sales_rep_id) = :sales_rep_id) AND (:sales_team_id IS NULL OR (:sales_team_id = 'NONE' AND NULLIF(o.sales_team_id, 0) IS NULL AND NULLIF(o2.sales_team_id, 0) IS NULL) OR IFNULL(o.sales_team_id, o2.sales_team_id) = :sales_team_id) AND (:distribution_center_id IS NULL OR IFNULL(o.distribution_center_id, o2.distribution_center_id) = :distribution_center_id) ORDER BY ai.inv_date ASC) i WHERE YEAR(inv_date) = :year AND ".$date_group_var."(inv_date) = :range AND (:tab = 'all' OR (:tab = 'sales' AND invoice_type = 'sales') OR (:tab = 'rma' AND invoice_type = 'rma') OR (:tab = 'term' AND invoice_type = 'sales' AND payment_method = 'terms'))", cardinality::SET, $criteria); ?>
	<table border="0" cellspacing="0" id="invoice-stats-details" class="tablesorter">
		<thead>
			<tr>
				<th class="header">Date</th>
				<th class="header">Invoice Id</th>
				<th class="header">Order Id/<br>RMA Id</th>
				<th class="header">Company</th>
				<th class="header">Invoice Total</th>
				<th class="header">Product Total</th>
				<th class="header">Shipping Total</th>
				<th class="header">Tax</th>
				<th class="header">Cost</th>
				<th class="header">P. Gross Margin</th>
				<th class="header">P. Margin %</th>
				<th class="header">T. Margin</th>
				<th class="header">T. Margin %</th>
				<th class="header">Acct. Mgr.</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($invoices as $i => $row) {
				$customer = new ck_customer2($row['customer_id']); ?>
			<tr class="<?= $i%2==0?'':'odd'; ?>">
				<td><?= $row['inv_date']; ?></td>
				<td><a href="/admin/invoice.php?oID=<?= $row['rma_order_id']?$row['rma_order_id']:$row['inv_order_id']; ?>&invId=<?= $row['invoice_id']; ?>" target="_blank"><?= $row['invoice_id']; ?></a></td>
				<td><?= ($row['inv_order_id']>'0'?'<a href="/admin/orders_new.php?selected_box=orders&oID='.$row['inv_order_id'].'&action=edit" target="_blank">'.$row['inv_order_id'].'</a>':'<a href="/admin/rma-detail.php?id='.$row['rma_id'].'" target="_blank">RMA '.$row['rma_id'].'</a>'); ?></td>
				<td><?= $customer->get_display_label(); ?></td>
				<td class="numeric"><?= number_format($row['invoice_total'], 2); ?></td>
				<td class="numeric"><?= number_format($row['product_total'], 2); ?></td>
				<td class="numeric"><?= number_format($row['shipping_total'], 2); ?></td>
				<td class="numeric"><?= number_format($row['tax_total'], 2); ?></td>
				<td class="numeric"><?= number_format($row['product_cost_total'], 2); ?></td>
				<td class="numeric"><?= number_format($row['product_margin_total'], 2); ?></td>
				<td class="numeric">
					<?php if ($row['product_total'] == 0) echo '------';
					else echo @number_format(($row['product_margin_total'] / $row['product_total']) * 100, 2).'%'; ?>
				</td>
				<td class="numeric"><?= number_format($row['revenue_margin_total'], 2); ?></td>
				<td class="numeric">
					<?php if ($row['revenue_total'] == 0) echo '------';
					else echo @number_format(($row['revenue_margin_total'] / $row['revenue_total']) * 100, 2).'%'; ?>
				</td>
				<td style="padding-left:8px;"><?= $row['account_manager']; ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php
	exit();
}
else {
	$criteria = [
		':start_date' => NULL,
		':end_date' => NULL,
		':customer_segment_id' => NULL,
		':channel' => NULL,
		':sales_rep_id' => NULL,
		':sales_team_id' => NULL,
		'distribution_center_id' => NULL,
	];
	$criteria = array_merge($criteria, $parameters);
	$invoice_summaries = prepared_query::fetch("SELECT YEAR(i.inv_date) as `year`, ".$date_group_var."(i.inv_date) as `date`, SUM(i.number_of_invoices) as number_of_invoices, SUM(i.product_total) as product_total, SUM(i.shipping_total) as shipping_total, SUM(i.tax_total) as tax_total, SUM(i.invoice_total) as invoice_total, SUM(i.product_cost_total) as product_cost_total, SUM(i.product_total - i.product_cost_total) as product_margin_total, SUM(i.product_total + i.shipping_total - i.product_cost_total) as margin_total,  SUM(IF(i.invoice_type = 'sales', i.number_of_invoices, 0)) as number_of_sales, SUM(IF(i.invoice_type = 'sales', i.product_total, 0)) as sales_product_total, SUM(IF(i.invoice_type = 'sales', i.shipping_total, 0)) as sales_shipping_total, SUM(IF(i.invoice_type = 'sales', i.tax_total, 0)) as sales_tax_total, SUM(IF(i.invoice_type = 'sales', i.invoice_total, 0)) as sales_invoice_total, SUM(IF(i.invoice_type = 'sales', i.product_cost_total, 0)) as sales_product_cost_total, SUM(IF(i.invoice_type = 'sales', i.product_total - i.product_cost_total, 0)) as sales_product_margin_total, SUM(IF(i.invoice_type = 'sales', i.product_total + i.shipping_total - i.product_cost_total, 0)) as sales_margin_total,  SUM(IF(i.invoice_type = 'rma', i.number_of_invoices, 0)) as number_of_rmas, SUM(IF(i.invoice_type = 'rma', i.product_total, 0)) as rma_product_total, SUM(IF(i.invoice_type = 'rma', i.shipping_total, 0)) as rma_shipping_total, SUM(IF(i.invoice_type = 'rma', i.tax_total, 0)) as rma_tax_total, SUM(IF(i.invoice_type = 'rma', i.invoice_total, 0)) as rma_invoice_total, SUM(IF(i.invoice_type = 'rma', i.product_cost_total, 0)) as rma_product_cost_total, SUM(IF(i.invoice_type = 'rma', i.product_total - i.product_cost_total, 0)) as rma_product_margin_total, SUM(IF(i.invoice_type = 'rma', i.product_total + i.shipping_total - i.product_cost_total, 0)) as rma_margin_total,  SUM(IF(i.invoice_type = 'sales' AND i.payment_method = 'terms', i.number_of_invoices, 0)) as number_of_terms_sales, SUM(IF(i.invoice_type = 'sales' AND i.payment_method = 'terms', i.product_total, 0)) as terms_sales_product_total, SUM(IF(i.invoice_type = 'sales' AND i.payment_method = 'terms', i.shipping_total, 0)) as terms_sales_shipping_total, SUM(IF(i.invoice_type = 'sales' AND i.payment_method = 'terms', i.tax_total, 0)) as terms_sales_tax_total, SUM(IF(i.invoice_type = 'sales' AND i.payment_method = 'terms', i.invoice_total, 0)) as terms_sales_invoice_total, SUM(IF(i.invoice_type = 'sales' AND i.payment_method = 'terms', i.product_cost_total, 0)) as terms_sales_product_cost_total, SUM(IF(i.invoice_type = 'sales' AND i.payment_method = 'terms', i.product_total - i.product_cost_total, 0)) as terms_sales_product_margin_total, SUM(IF(i.invoice_type = 'sales' AND i.payment_method = 'terms', i.product_total + i.shipping_total - i.product_cost_total, 0)) as terms_sales_margin_total FROM (SELECT DATE(ai.inv_date) as inv_date, IFNULL(COUNT(DISTINCT ai.invoice_id), 0) as number_of_invoices, IFNULL(SUM(ai_total.total), 0) - IFNULL(SUM(ai_tax.total_tax), 0) - IFNULL(SUM(ai_shipping.total_shipping), 0) as product_total, IFNULL(SUM(ai_shipping.total_shipping), 0) as shipping_total, IFNULL(SUM(ai_tax.total_tax), 0) as tax_total, IFNULL(SUM(ai_total.total), 0) as invoice_total, IFNULL(SUM(ii.total_cost), 0) as product_cost_total, CASE WHEN NULLIF(ai.inv_order_id, 0) IS NOT NULL AND NULLIF(ai.rma_id, 0) IS NULL AND IFNULL(ai.credit_memo, 0) = 0 THEN 'sales' WHEN NULLIF(ai.rma_id, 0) IS NOT NULL OR ai.credit_memo = 1 THEN 'rma' ELSE 'all' END as invoice_type, IF(o.payment_method_id IN (5, 6, 7, 15), 'terms', 'no-terms') as payment_method FROM acc_invoices ai LEFT JOIN customers c ON ai.customer_id = c.customers_id LEFT JOIN orders o ON ai.inv_order_id = o.orders_id AND o.orders_status NOT IN (6, 9) LEFT JOIN rma r ON r.id = ai.rma_id LEFT JOIN orders o2 ON r.order_id = o2.orders_id  LEFT JOIN (SELECT ait.invoice_id, SUM(ait.invoice_total_price) as total_shipping FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_shipping' WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) GROUP BY ait.invoice_id) ai_shipping ON ai.invoice_id = ai_shipping.invoice_id  LEFT JOIN (SELECT ait.invoice_id, SUM(ait.invoice_total_price) as total_tax FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_tax' WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) GROUP BY ait.invoice_id) ai_tax ON ai.invoice_id = ai_tax.invoice_id  LEFT JOIN (SELECT ait.invoice_id, SUM(ait.invoice_total_price) as total FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_total' WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) GROUP BY ait.invoice_id) ai_total ON ai.invoice_id = ai_total.invoice_id LEFT JOIN (SELECT ii.invoice_id, SUM(ii.orders_product_cost_total) as total_cost FROM acc_invoices ai JOIN acc_invoice_items ii ON ai.invoice_id = ii.invoice_id JOIN products_stock_control psc ON ii.ipn_id = psc.stock_id WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) GROUP BY ii.invoice_id) ii ON ai.invoice_id = ii.invoice_id WHERE ai.inv_date < DATE(:end_date + INTERVAL 1 DAY) AND ai.inv_date >= DATE(:start_date) AND (NULLIF(ai.inv_order_id, 0) IS NOT NULL OR NULLIF(ai.rma_id, 0) IS NOT NULL) AND (:customer_segment_id IS NULL OR c.customer_segment_id = :customer_segment_id) AND (:channel IS NULL OR IFNULL(o.channel, o2.channel) = :channel) AND (:sales_rep_id IS NULL OR (:sales_rep_id = 'NONE' AND NULLIF(o.orders_sales_rep_id, 0) IS NULL AND NULLIF(o2.orders_sales_rep_id, 0) IS NULL) OR IFNULL(o.orders_sales_rep_id, o2.orders_sales_rep_id) = :sales_rep_id) AND (:sales_team_id IS NULL OR (:sales_team_id = 'NONE' AND NULLIF(o.sales_team_id, 0) IS NULL AND NULLIF(o2.sales_team_id, 0) IS NULL) OR IFNULL(o.sales_team_id, o2.sales_team_id) = :sales_team_id) AND (:distribution_center_id IS NULL OR IFNULL(o.distribution_center_id, o2.distribution_center_id) = :distribution_center_id) GROUP BY DATE(ai.inv_date), CASE WHEN NULLIF(ai.inv_order_id, 0) IS NOT NULL AND NULLIF(ai.rma_id, 0) IS NULL AND IFNULL(ai.credit_memo, 0) = 0 THEN 'sales' WHEN NULLIF(ai.rma_id, 0) IS NOT NULL OR ai.credit_memo = 1 THEN 'rma' ELSE 'all' END, IF(o.payment_method_id IN (5, 6, 7, 15), 'terms', 'no-terms') ORDER BY ai.inv_date ASC) i GROUP BY YEAR(i.inv_date), ".$date_group_var."(i.inv_date)", cardinality::SET, $criteria);
}

$account_managers = ck_admin::get_account_managers(['ck_admin', 'sort_by_name']);
$sales_teams = ck_team::get_sales_teams();
$customer_segments = prepared_query::fetch('SELECT * FROM customer_segments'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script src="/images/static/js/ck-styleset.js"></script>
	<script src="/images/static/js/ck-tabs.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?= BOX_WIDTH; ?>" valign="top">
				<!-- left_navigation //-->
				<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
				<!-- left_navigation_eof //-->
			</td>
			<style>
				#page-body { vertical-align:top; }
				#options_header { margin:.4em 0 .4em 0; }
				#options_header, #options_header ul { margin:inherit auto; padding:0; text-align:left; }
				.dataTableHeadingContent { width:100px; }
				#invoice-stats-details .header { padding-right:20px; }
				.sales_selection li { display:inline; list-style-type:none; border-right:1.75px solid #000; margin-right:.4em; }
				.sales_selection li:last-child { border:none; }
				.sales_selection label { font-size:.80em; }

				.invoice-headers td { padding:4px 3px; }

				.invoice-headers tbody tr td { background-color:#fff; }
				.invoice-headers>tbody>tr:nth-child(4n+1)>td { background-color:#e0e0e0; }
				.invoice-headers>tbody>tr:nth-child(even)>td { background-color:transparent; padding:0px; }
			</style>
			<td id="page-body">
				<div id="options_header">
					<form method="get" action="stats_invoices.php" id="filter_form">
						<input type="hidden" name="action" value="refresh">
						<ul class="sales_selection">
							<li>
								<select name="date_group">
									<option value="Day" <?= $date_group=='Day'?'selected':''; ?>>Daily</option>
									<option value="Week" <?= $date_group=='Week'?'selected':''; ?>>Weekly</option>
									<option value="Month" <?= $date_group=='Month'?'selected':''; ?>>Monthly</option>
								</select>
							</li>
							<li>
								<label for="start_date">Start Date</label>
								<input type="text" name="start_date" id="start_date" value="<?= $start_date->format('Y-m-d'); ?>">
							</li>
							<li>
								<label for="end_date">End Date</label>
								<input type="text" name="end_date" id="end_date" value="<?= $end_date->format('Y-m-d'); ?>">
							</li>
							<li>
								<label for="segment">Segment</label>
								<select name="segment" id="segment">
									<option value="">All</option>
									<?php foreach ($customer_segments as $sgmt) { ?>
									<option value="<?= $sgmt['customer_segment_id']; ?>" <?= $sgmt['customer_segment_id']==$segment?'selected':''; ?>><?= ucwords($sgmt['segment']); ?></option>
									<?php } ?>
								</select>
							</li>
							<li>
								<label for="channel">Channel</label>
								<select name="channel" id="channel">
									<option value="" <?= empty($channel)?'selected':''; ?>>All</option>
									<option value="ebay" <?= $channel=='ebay'?'selected':''; ?>>ebay</option>
									<option value="web" <?= $channel=='web'?'selected':''; ?>>web</option>
									<option value="phone" <?= $channel=='phone'?'selected':''; ?>>phone</option>
									<option value="amazon" <?= $channel=='amazon'?'selected':''; ?>>amazon</option>
									<option value="newegg" <?= $channel=='newegg'?'selected':''; ?>>newegg</option>
									<option value="newegg_business" <?= $channel=='newegg_business'?'selected':''; ?>>newegg business</option>
								</select>
							</li>
							<li>
								<label for="sales_rep_id">Sales Rep</label>
								<select name="sales_rep_id" id="sales_rep_id">
									<option value="">All</option>
									<option value="NONE" <?= $sales_rep_id=='NONE'?'selected':''; ?>>None</option>
									<?php foreach ($account_managers as $account_manager) { ?>
									<option value="<?= $account_manager->id(); ?>" <?= $sales_rep_id==$account_manager->id()?'selected':''; ?>><?= $account_manager->get_name(); ?></option>
									<?php } ?>
								</select>
							</li>
							<li>
								<label for="sales_rep_id">Sales Team</label>
								<select name="sales_team_id" id="sales_team_id">
									<option value="">All</option>
									<option value="NONE" <?= $sales_team_id=='NONE'?'selected':''; ?>>None</option>
									<?php foreach ($sales_teams as $sales_team) { ?>
									<option value="<?= $sales_team->id(); ?>" <?= $sales_team_id==$sales_team->id()?'selected':''; ?>><?= $sales_team->get_header('label'); ?></option>
									<?php } ?>
								</select>
							</li>
							<li>
								<label for="distribution_center_id">DC</label>
								<select name="distribution_center_id" id="distribution_center_id">
									<option value="ALL" <?= $distribution_center_id=='ALL'?'selected':''; ?>>All</option>
									<?php foreach (api_channel_advisor::$distribution_center_map as $dcid => $dc) { ?>
									<option value="<?= $dcid; ?>" <?= $distribution_center_id!='ALL'&&$distribution_center_id==$dcid?'selected':''; ?>><?= $dc; ?></option>
									<?php } ?>
								</select>
							</li>
							<li><input type="submit" value="Submit"></li>
						</ul>
					</form>
					<input type="hidden" class="page-var" name="date_group" value="<?= $date_group; ?>">
					<input type="hidden" class="page-var" name="start_date" value="<?= $start_date->format('Y-m-d'); ?>">
					<input type="hidden" class="page-var" name="end_date" value="<?= $end_date->format('Y-m-d'); ?>">
					<input type="hidden" class="page-var" name="segment" value="<?= $segment; ?>">
					<input type="hidden" class="page-var" name="channel" value="<?= $channel; ?>">
					<input type="hidden" class="page-var" name="sales_rep_id" value="<?= $sales_rep_id; ?>">
					<input type="hidden" class="page-var" name="sales_team_id" value="<?= $sales_team_id; ?>">
					<input type="hidden" class="page-var" name="distribution_center_id" value="<?= $distribution_center_id; ?>">
				</div>
				<ul id="invoice_stats_tabs">
					<li id="all_invoices">All Invoices</li>
					<li id="sales_invoices">Sales Invoices</li>
					<li id="rma_invoices">RMA/Credit Invoices</li>
					<li id="term_orders">Term Orders</li>
				</ul>
				<div id="invoice_stats_tabs-body">
					<div id="all_invoices-content">
						<table border="0" cellspacing="0" cellpadding="0" class="invoice-headers">
							<thead>
								<tr class="dataTableHeadingRow">
									<td class="dataTableHeadingContent">Date</td>
									<td class="dataTableHeadingContent">Number of Invoices</td>
									<td class="dataTableHeadingContent">Invoice Total</td>
									<td class="dataTableHeadingContent">Product Total</td>
									<td class="dataTableHeadingContent">Shipping Total</td>
									<td class="dataTableHeadingContent">Tax</td>
									<td class="dataTableHeadingContent">Cost</td>
									<td class="dataTableHeadingContent">Product Gross Margin</td>
									<td class="dataTableHeadingContent">Product Margin %</td>
									<td class="dataTableHeadingContent">Total Margin</td>
									<td class="dataTableHeadingContent">Total Margin %</td>
									<td class="dataTableHeadingContent">AOV</td>
									<td class="dataTableHeadingContent">Avg <?= $column_title; ?> Margin</td>
								</tr>
							</thead>
							<tbody>
								<?php $running_total = 0;
								$running_count = 0;
								$total_orders = $invoice_total = $total_product = $total_shipping = $total_tax = $total_cost = $total_margin = $total_product_margin = $margin_denom_total = 0;

								foreach ($invoice_summaries as $idx => $summary) {
									if ($summary['number_of_invoices'] <= 0) continue;
									if ($date_group == 'Day') $date = DateTime::createFromFormat('Y z', $summary['year'].' '.($summary['date']-1));
									elseif ($date_group == 'Week') {
										$date = new DateTime;
										$date->setISODate($summary['year'], $summary['date']);
									}
									else {
										$date = new DateTime;
										$date->setDate($summary['year'], $summary['date'], 1);
									}

									$margin_denom = $summary['invoice_total'] - $summary['tax_total'];

									$running_total += $summary['margin_total'];
									$running_count++; ?>
								<tr class="<?= $allow_expand_invoices?'allow-expand-invoices':''; ?> row-header" data-idx="all-<?= $idx; ?>" data-year="<?= $summary['year']; ?>" data-date="<?= $summary['date']; ?>" data-tab="all">
									<td class="dataTableContent"><?= $date->format('m/d/Y'); ?></td>
									<td class="dataTableContent"><?= $summary['number_of_invoices']; ?></td>
									<td class="dataTableContent"><?= number_format($summary['invoice_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['product_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['shipping_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['tax_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['product_cost_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['product_margin_total'], 2); ?></td>
									<td class="dataTableContent">
										<?php if ($summary['product_total'] == 0) echo '------';
										else echo @number_format(($summary['product_margin_total'] / $summary['product_total']) * 100, 2); ?>%
									</td>
									<td class="dataTableContent"><?= number_format($summary['margin_total'], 2); ?></td>
									<td class="dataTableContent">
										<?php if ($margin_denom == 0) echo '------';
										else echo @number_format(($summary['margin_total'] / $margin_denom) * 100, 2); ?>%
									</td>
									<td class="dataTableContent"><?= number_format($summary['invoice_total'] / $summary['number_of_invoices'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($running_total/$running_count, 2); ?></td>
								</tr>
								<tr>
									<td colspan="13"><div id="all-<?= $idx; ?>" style="display:none"></div></td>
								</tr>
									<?php $total_orders += $summary['number_of_invoices'];
									$invoice_total += $summary['invoice_total'];
									$margin_denom_total += $margin_denom;
									$total_product += $summary['product_total'];
									$total_shipping += $summary['shipping_total'];
									$total_tax += $summary['tax_total'];
									$total_cost += $summary['product_cost_total'];
									$total_margin += $summary['margin_total'];
									$total_product_margin += $summary['product_margin_total'];
								}
								flush(); ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="dataTableContent"><b>Totals:</b></td>
									<td class="dataTableContent"><?= $total_orders; ?></td>
									<td class="dataTableContent"><?= number_format($invoice_total, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_product, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_shipping, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_tax, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_cost, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_product_margin, 2); ?></td>
									<td class="dataTableContent"><?= $total_product!=0?number_format(($total_product_margin / $total_product) * 100, 2):0; ?>%</td>
									<td class="dataTableContent"><?= number_format($total_margin, 2); ?></td>
									<td class="dataTableContent"><?= $margin_denom_total!=0?number_format(($total_margin / $margin_denom_total) * 100, 2):0; ?>%</td>
									<td class="dataTableContent"><?= $total_orders!=0?number_format($invoice_total / $total_orders, 2):0; ?></td>
									<td class="dataTableContent"><?= $running_count!=0?number_format($running_total / $running_count, 2):0; ?></td>
								</tr>
							</tfoot>
						</table>
					</div>
					<div id="sales_invoices-content">
						<table border="0" cellspacing="0" cellpadding="2" class="invoice-headers">
							<thead>
								<tr class="dataTableHeadingRow">
									<td class="dataTableHeadingContent">Date</td>
									<td class="dataTableHeadingContent">Number of Invoices</td>
									<td class="dataTableHeadingContent">Invoice Total</td>
									<td class="dataTableHeadingContent">Product Total</td>
									<td class="dataTableHeadingContent">Shipping Total</td>
									<td class="dataTableHeadingContent">Tax</td>
									<td class="dataTableHeadingContent">Cost</td>
									<td class="dataTableHeadingContent">Product Gross Margin</td>
									<td class="dataTableHeadingContent">Product Margin %</td>
									<td class="dataTableHeadingContent">Total Margin</td>
									<td class="dataTableHeadingContent">Total Margin %</td>
									<td class="dataTableHeadingContent">AOV</td>
									<td class="dataTableHeadingContent">Avg <?= $column_title; ?> Margin</td>
								</tr>
							</thead>
							<tbody>
								<?php $running_total = 0;
								$running_count = 0;
								$total_orders = $invoice_total = $total_product = $total_shipping = $total_tax = $total_cost = $total_margin = $total_product_margin = $margin_denom_total = 0;

								foreach ($invoice_summaries as $idx => $summary) {
									if ($summary['number_of_sales'] <= 0) continue;
									if ($date_group == 'Day') $date = DateTime::createFromFormat('Y z', $summary['year'].' '.($summary['date']-1));
									elseif ($date_group == 'Week') {
										$date = new DateTime;
										$date->setISODate($summary['year'], $summary['date']);
									}
									else {
										$date = new DateTime;
										$date->setDate($summary['year'], $summary['date'], 1);
									}

									$margin_denom = $summary['sales_invoice_total'] - $summary['sales_tax_total'];

									$running_total += $summary['sales_margin_total'];
									$running_count++; ?>
								<tr class="<?= $allow_expand_invoices?'allow-expand-invoices':''; ?> row-header" data-idx="sales-<?= $idx; ?>" data-year="<?= $summary['year']; ?>" data-date="<?= $summary['date']; ?>" data-tab="sales">
									<td class="dataTableContent"><?= $date->format('m/d/Y'); ?></td>
									<td class="dataTableContent"><?= $summary['number_of_sales']; ?></td>
									<td class="dataTableContent"><?= number_format($summary['sales_invoice_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['sales_product_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['sales_shipping_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['sales_tax_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['sales_product_cost_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['sales_product_margin_total'], 2); ?></td>
									<td class="dataTableContent">
										<?php if ($summary['sales_product_total'] == 0) echo '------';
										else echo @number_format(($summary['sales_product_margin_total'] / $summary['sales_product_total']) * 100, 2); ?>%
									</td>
									<td class="dataTableContent"><?= number_format($summary['sales_margin_total'], 2); ?></td>
									<td class="dataTableContent">
										<?php if ($margin_denom == 0) echo '------';
										else echo @number_format(($summary['sales_margin_total'] / $margin_denom) * 100, 2); ?>%
									</td>
									<td class="dataTableContent"><?= number_format($summary['sales_invoice_total'] / $summary['number_of_sales'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($running_total/$running_count, 2); ?></td>
								</tr>
								<tr>
									<td colspan="13"><div id="sales-<?= $idx; ?>" style="display:none"></div></td>
								</tr>
									<?php $total_orders += $summary['number_of_sales'];
									$invoice_total += $summary['sales_invoice_total'];
									$margin_denom_total += $margin_denom;
									$total_product += $summary['sales_product_total'];
									$total_shipping += $summary['sales_shipping_total'];
									$total_tax += $summary['sales_tax_total'];
									$total_cost += $summary['sales_product_cost_total'];
									$total_margin += $summary['sales_margin_total'];
									$total_product_margin += $summary['sales_product_margin_total'];
								}
								flush(); ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="dataTableContent"><b>Totals:</b></td>
									<td class="dataTableContent"><?= $total_orders; ?></td>
									<td class="dataTableContent"><?= number_format($invoice_total, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_product, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_shipping, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_tax, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_cost, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_product_margin, 2); ?></td>
									<td class="dataTableContent"><?= $total_product!=0?number_format(($total_product_margin / $total_product) * 100, 2):0; ?>%</td>
									<td class="dataTableContent"><?= number_format($total_margin, 2); ?></td>
									<td class="dataTableContent"><?= $margin_denom_total!=0?number_format(($total_margin / $margin_denom_total) * 100, 2):0; ?>%</td>
									<td class="dataTableContent"><?= $total_orders!=0?number_format($invoice_total / $total_orders, 2):0; ?></td>
									<td class="dataTableContent"><?= $running_count!=0?number_format($running_total / $running_count, 2):0; ?></td>
								</tr>
							</tfoot>
						</table>
					</div>
					<div id="rma_invoices-content">
						<table border="0" cellspacing="0" cellpadding="2" class="invoice-headers">
							<thead>
								<tr class="dataTableHeadingRow">
									<td class="dataTableHeadingContent">Date</td>
									<td class="dataTableHeadingContent">Number of Invoices</td>
									<td class="dataTableHeadingContent">Invoice Total</td>
									<td class="dataTableHeadingContent">Product Total</td>
									<td class="dataTableHeadingContent">Shipping Total</td>
									<td class="dataTableHeadingContent">Tax</td>
									<td class="dataTableHeadingContent">Cost</td>
									<td class="dataTableHeadingContent">Product Gross Margin</td>
									<td class="dataTableHeadingContent">Product Margin %</td>
									<td class="dataTableHeadingContent">Total Margin</td>
									<td class="dataTableHeadingContent">Total Margin %</td>
									<td class="dataTableHeadingContent">AOV</td>
									<td class="dataTableHeadingContent">Avg <?= $column_title; ?> Margin</td>
								</tr>
							</thead>
							<tbody>
								<?php $running_total = 0;
								$running_count = 0;
								$total_orders = $invoice_total = $total_product = $total_shipping = $total_tax = $total_cost = $total_margin = $total_product_margin = $margin_denom_total = 0;

								foreach ($invoice_summaries as $idx => $summary) {
									if ($summary['number_of_rmas'] <= 0) continue;
									if ($date_group == 'Day') $date = DateTime::createFromFormat('Y z', $summary['year'].' '.($summary['date']-1));
									elseif ($date_group == 'Week') {
										$date = new DateTime;
										$date->setISODate($summary['year'], $summary['date']);
									}
									else {
										$date = new DateTime;
										$date->setDate($summary['year'], $summary['date'], 1);
									}

									$margin_denom = $summary['rma_invoice_total'] - $summary['rma_tax_total'];

									$running_total += $summary['rma_margin_total'];
									$running_count++; ?>
								<tr class="<?= $allow_expand_invoices?'allow-expand-invoices':''; ?> row-header" data-idx="rma-<?= $idx; ?>" data-year="<?= $summary['year']; ?>" data-date="<?= $summary['date']; ?>" data-tab="rma">
									<td class="dataTableContent"><?= $date->format('m/d/Y'); ?></td>
									<td class="dataTableContent"><?= $summary['number_of_rmas']; ?></td>
									<td class="dataTableContent"><?= number_format($summary['rma_invoice_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['rma_product_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['rma_shipping_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['rma_tax_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['rma_product_cost_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['rma_product_margin_total'], 2); ?></td>
									<td class="dataTableContent">
										<?php if ($summary['rma_product_total'] == 0) echo '------';
										else echo @number_format(($summary['rma_product_margin_total'] / $summary['rma_product_total']) * 100, 2); ?>%
									</td>
									<td class="dataTableContent"><?= number_format($summary['rma_margin_total'], 2); ?></td>
									<td class="dataTableContent">
										<?php if ($margin_denom == 0) echo '------';
										else echo @number_format(($summary['rma_margin_total'] / $margin_denom) * 100, 2); ?>%
									</td>
									<td class="dataTableContent"><?= number_format($summary['rma_invoice_total'] / $summary['number_of_rmas'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($running_total/$running_count, 2); ?></td>
								</tr>
								<tr>
									<td colspan="13"><div id="rma-<?= $idx; ?>" style="display:none"></div></td>
								</tr>
									<?php $total_orders += $summary['number_of_rmas'];
									$invoice_total += $summary['rma_invoice_total'];
									$margin_denom_total += $margin_denom;
									$total_product += $summary['rma_product_total'];
									$total_shipping += $summary['rma_shipping_total'];
									$total_tax += $summary['rma_tax_total'];
									$total_cost += $summary['rma_product_cost_total'];
									$total_margin += $summary['rma_margin_total'];
									$total_product_margin += $summary['rma_product_margin_total'];
								}
								flush(); ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="dataTableContent"><b>Totals:</b></td>
									<td class="dataTableContent"><?= $total_orders; ?></td>
									<td class="dataTableContent"><?= number_format($invoice_total, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_product, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_shipping, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_tax, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_cost, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_product_margin, 2); ?></td>
									<td class="dataTableContent"><?= $total_product!=0?number_format(($total_product_margin / $total_product) * 100, 2):0; ?>%</td>
									<td class="dataTableContent"><?= number_format($total_margin, 2); ?></td>
									<td class="dataTableContent"><?= $margin_denom_total!=0?number_format(($total_margin / $margin_denom_total) * 100, 2):0; ?>%</td>
									<td class="dataTableContent"><?= $total_orders!=0?number_format($invoice_total / $total_orders, 2):0; ?></td>
									<td class="dataTableContent"><?= $running_count!=0?number_format($running_total / $running_count, 2):0; ?></td>
								</tr>
							</tfoot>
						</table>
					</div>
					<div id="term_orders-content">
						<table border="0" cellspacing="0" cellpadding="2" class="invoice-headers">
							<thead>
								<tr class="dataTableHeadingRow">
									<td class="dataTableHeadingContent">Date</td>
									<td class="dataTableHeadingContent">Number of Invoices</td>
									<td class="dataTableHeadingContent">Invoice Total</td>
									<td class="dataTableHeadingContent">Product Total</td>
									<td class="dataTableHeadingContent">Shipping Total</td>
									<td class="dataTableHeadingContent">Tax</td>
									<td class="dataTableHeadingContent">Cost</td>
									<td class="dataTableHeadingContent">Product Gross Margin</td>
									<td class="dataTableHeadingContent">Product Margin %</td>
									<td class="dataTableHeadingContent">Total Margin</td>
									<td class="dataTableHeadingContent">Total Margin %</td>
									<td class="dataTableHeadingContent">AOV</td>
									<td class="dataTableHeadingContent">Avg <?= $column_title; ?> Margin</td>
								</tr>
							</thead>
							<tbody>
								<?php $running_total = 0;
								$running_count = 0;
								$total_orders = $invoice_total = $total_product = $total_shipping = $total_tax = $total_cost = $total_profit = 0;

								foreach ($invoice_summaries as $idx => $summary) {
									if ($summary['number_of_terms_sales'] <= 0) continue;
									if ($date_group == 'Day') $date = DateTime::createFromFormat('Y z', $summary['year'].' '.($summary['date']-1));
									elseif ($date_group == 'Week') {
										$date = new DateTime;
										$date->setISODate($summary['year'], $summary['date']);
									}
									else {
										$date = new DateTime;
										$date->setDate($summary['year'], $summary['date'], 1);
									}

									$margin_denom = $summary['terms_sales_invoice_total'] - $summary['terms_sales_tax_total'];

									$running_total += $summary['terms_sales_margin_total'];
									$running_count++; ?>
								<tr class="<?= $allow_expand_invoices?'allow-expand-invoices':''; ?> row-header" data-idx="term-<?= $idx; ?>" data-year="<?= $summary['year']; ?>" data-date="<?= $summary['date']; ?>" data-tab="term">
									<td class="dataTableContent"><?= $date->format('m/d/Y'); ?></td>
									<td class="dataTableContent"><?= $summary['number_of_terms_sales']; ?></td>
									<td class="dataTableContent"><?= number_format($summary['terms_sales_invoice_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['terms_sales_product_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['terms_sales_shipping_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['terms_sales_tax_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['terms_sales_product_cost_total'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($summary['terms_sales_product_margin_total'], 2); ?></td>
									<td class="dataTableContent">
										<?php if ($summary['terms_sales_product_total'] == 0) echo '------';
										else echo @number_format(($summary['terms_sales_product_margin_total'] / $summary['terms_sales_product_total']) * 100, 2); ?>%
									</td>
									<td class="dataTableContent"><?= number_format($summary['terms_sales_margin_total'], 2); ?></td>
									<td class="dataTableContent">
										<?php if ($margin_denom == 0) echo '------';
										else echo @number_format(($summary['terms_sales_margin_total'] / $margin_denom) * 100, 2); ?>%
									</td>
									<td class="dataTableContent"><?= number_format($summary['terms_sales_invoice_total'] / $summary['number_of_terms_sales'], 2); ?></td>
									<td class="dataTableContent"><?= number_format($running_total/$running_count, 2); ?></td>
								</tr>
								<tr>
									<td colspan="13"><div id="term-<?= $idx; ?>" style="display:none"></div></td>
								</tr>
									<?php $total_orders += $summary['number_of_terms_sales'];
									$invoice_total += $summary['terms_sales_invoice_total'];
									$margin_denom_total += $margin_denom;
									$total_product += $summary['terms_sales_product_total'];
									$total_shipping += $summary['terms_sales_shipping_total'];
									$total_tax += $summary['terms_sales_tax_total'];
									$total_cost += $summary['terms_sales_product_cost_total'];
									$total_margin += $summary['terms_sales_margin_total'];
									$total_product_margin += $summary['terms_sales_product_margin_total'];
								}
								flush(); ?>
							</tbody>
							<tfoot>
								<tr>
									<td class="dataTableContent"><b>Totals:</b></td>
									<td class="dataTableContent"><?= $total_orders; ?></td>
									<td class="dataTableContent"><?= number_format($invoice_total, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_product, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_shipping, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_tax, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_cost, 2); ?></td>
									<td class="dataTableContent"><?= number_format($total_product_margin, 2); ?></td>
									<td class="dataTableContent"><?= $total_product!=0?number_format(($total_product_margin / $total_product) * 100, 2):0; ?>%</td>
									<td class="dataTableContent"><?= number_format($total_margin, 2); ?></td>
									<td class="dataTableContent"><?= $margin_denom_total!=0?number_format(($total_margin / $margin_denom_total) * 100, 2):0; ?>%</td>
									<td class="dataTableContent"><?= $total_orders!=0?number_format($invoice_total / $total_orders, 2):0; ?></td>
									<td class="dataTableContent"><?= $running_count!=0?number_format($running_total / $running_count, 2):0; ?></td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
			</td>
		</tr>
	</table>
	<script>
		jQuery('#start_date').datepicker({ dateFormat:'yy-mm-dd' });
		jQuery('#end_date').datepicker({ dateFormat:'yy-mm-dd' });

		new ck.tabs({
			tabs_id: 'invoice_stats_tabs',
			tab_bodies_id: 'invoice_stats_tabs-body',
			default_tab_index: 0,
			content_suffix: '-content'
		});

		jQuery.tablesorter.addParser({
			// set a unique id
			id: 'thousands',
			is: function(s) {
				// return false so this parser is not auto detected
				return false;
			},
			format: function(s) {
				// format your data for normalization
				return s.replace('$','').replace(/,/g,'').replace('%','');
			},
			// set type, either numeric or text
			type: 'numeric'
		});

		jQuery('.allow-expand-invoices').click(function() {
			var $target = jQuery('#'+jQuery(this).attr('data-idx'));

			if ($target.is(':visible')) {
				$target.toggle();
				return;
			}

			var fields = {};

			fields.year = jQuery(this).attr('data-year');
			fields.range = jQuery(this).attr('data-date');
			fields.tab = jQuery(this).attr('data-tab');

			jQuery('.page-var').each(function() {
				fields[jQuery(this).attr('name')] = jQuery(this).val();
			});

			fields.ajax = 1;
			fields.action = 'get_invoices';

			jQuery.ajax({
				url: '/admin/stats_invoices.php',
				type: 'get',
				dataType: 'html',
				data: fields,
				success: function(data) {
					$target.html(data).toggle();
					setTimeout(function() {
						jQuery('.tablesorter').tablesorter({
							widgets: ['zebra'],
							headers: {
								4: { sorter: 'thousands' },
								5: { sorter: 'thousands' },
								6: { sorter: 'thousands' },
								7: { sorter: 'thousands' },
								8: { sorter: 'thousands' },
								9: { sorter: 'thousands' },
								10: { sorter: 'thousands' },
								11: { sorter: 'thousands' },
								12: { sorter: 'thousands' },
								13: { sorter: 'thousands' }
							}
						});
					}, 300);
				}
			});
		});
	</script>
</body>
</html>