<?php
require('includes/application_top.php');

//---------header-----------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//---------end header-------------

// process report
$paid_in_full = 1;

$date_start = new DateTime('first day of last month');
$date_end = new DateTime('last day of last month');

$account_managers = ck_admin::get_account_managers(['ck_admin', 'sort_by_name']);
$allowed_reps = array_map(function($am) { return $am->id(); }, $account_managers);
$allowed_reps = array_filter($allowed_reps, function($ar) { return !empty($_GET['account_manager_id'])?$_GET['account_manager_id']==$ar:!in_array($ar, [1, 118]); });

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;
switch ($action) {
	case 'run-report':
		if (!empty($_GET['date-range'])) {
			if (preg_match('/ - /', $_GET['date-range'])) {
				list($date_start, $date_end) = explode(' - ', $_GET['date-range'], 2);
				$date_start = new DateTime($date_start);
				$date_end = new DateTime($date_end);
			}
			else $date_start = $date_end = new DateTime($_GET['date-range']);
		}

		//$paid_in_full = $_GET['paid_in_full'];

		// no break, we're falling through to the report runner
	default:
		//$table_columns = ['Account Mgr', 'Customer', 'Invoice #', 'Order/RMA', 'Paid Date', 'Balance', 'Product Margin', 'Incentive %', 'Incentive $'];

		$invoices = prepared_query::fetch('SELECT * FROM (SELECT CONCAT_WS(:sep, a.admin_firstname, a.admin_lastname) as account_manager, i.customer_id as customers_id, c.customers_email_address, i.invoice_id, i.inv_order_id as orders_id, NULL as rma_id, DATE(i.inv_date) as invoice_date, CASE WHEN i.credit_memo = 0 THEN DATE(pti.credit_date) ELSE DATE(i.inv_date) END as paid_date, CASE WHEN i.credit_memo = 0 THEN pti.balance ELSE 0 END as balance, i.incentive_product_total as product_margin, i.incentive_percentage, i.incentive_final_total, i.credit_memo FROM acc_invoices i JOIN orders o ON i.inv_order_id = o.orders_id AND o.orders_sales_rep_id IN ('.implode(',', $allowed_reps).') JOIN customers c ON o.customers_id = c.customers_id JOIN admin a ON o.orders_sales_rep_id = a.admin_id LEFT JOIN (SELECT pti.invoice_id, MAX(pti.credit_date) as credit_date, it.invoice_total_price - SUM(pti.credit_amount) as balance FROM acc_payments_to_invoices pti LEFT JOIN acc_invoice_totals it ON pti.invoice_id = it.invoice_id AND it.invoice_total_line_type = :ot_total GROUP BY pti.invoice_id HAVING MAX(pti.credit_date) >= DATE(:start_date) AND MAX(pti.credit_date) <= DATE(:end_date)) pti ON i.invoice_id = pti.invoice_id WHERE i.paid_in_full = :paid_in_full AND CASE WHEN i.credit_memo = 0 THEN pti.credit_date ELSE i.inv_date END >= DATE(:start_date) AND CASE WHEN i.credit_memo = 0 THEN pti.credit_date ELSE i.inv_date END <= DATE(:end_date) UNION SELECT CONCAT_WS(:sep, a.admin_firstname, a.admin_lastname) as account_manager, i.customer_id as customers_id, c.customers_email_address, i.invoice_id, NULL as orders_id, i.rma_id, DATE(i.inv_date) as invoice_date, DATE(i.inv_date) as paid_date, 0 as balance, i.incentive_product_total as product_margin, i.incentive_percentage, i.incentive_final_total, i.credit_memo FROM acc_invoices i JOIN rma ON i.rma_id = rma.id JOIN orders o ON rma.order_id = o.orders_id AND o.orders_sales_rep_id IN ('.implode(',', $allowed_reps).') JOIN customers c ON o.customers_id = c.customers_id JOIN admin a ON o.orders_sales_rep_id = a.admin_id WHERE i.inv_date >= DATE(:start_date) AND i.inv_date <= DATE(:end_date)) p ORDER BY account_manager ASC, paid_date ASC', cardinality::SET, [':sep' => ' ', ':ot_total' => 'ot_total', ':start_date' => $date_start->format('Y-m-d'), ':end_date' => $date_end->format('Y-m-d'), ':paid_in_full' => $paid_in_full]);

		/*$invoices = array_filter($invoices, function($invoice) use ($date_start) {
			if ($invoice['incentive_final_total'] > 0) return TRUE;

			$invoice_date = new DateTime($invoice['invoice_date']);

			if ($invoice_date < $date_start) return FALSE;
			else return TRUE;
		});*/

		$manager_totals = array_reduce($invoices, function($totals, $invoice) {
			if (empty($totals[$invoice['account_manager']])) $totals[$invoice['account_manager']] = ['account_manager' => $invoice['account_manager'], 'total' => 0];
			$totals[$invoice['account_manager']]['total'] += $invoice['incentive_final_total'];
			return $totals;
		}, []);

		$invoices = array_map(function($invoice) {
			if (!empty($invoice['rma_id'])) $invoice['transaction_id'] = '[RMA] '.$invoice['rma_id'];
			elseif (!empty($invoice['credit_memo'])) $invoice['transaction_id'] = '[CM] '.$invoice['invoice_id'];
			else {
				$invoice['transaction_id'] = $invoice['orders_id'];
				if ($invoice['product_margin'] < 0) $invoice['negative?'] = 1;
			}

			$invoice['paid_date'] = (new DateTime($invoice['paid_date']))->format('Y-m-d');
			$invoice['balance'] = CK\text::monetize($invoice['balance']);
			$invoice['product_margin'] = CK\text::monetize($invoice['product_margin']);
			$invoice['incentive_pctg'] = number_format($invoice['incentive_percentage'] * 100, 1).'%';
			$invoice['incentive_final'] = CK\text::monetize($invoice['incentive_final_total']);

			return $invoice;
		}, $invoices);

		break;
}

//---------body-------------------
$content_map = new ck_content();

if (!empty($errors)) {
	$content_map->{'has_errors?'} = 1;
	$content_map->errors = $errors;
}

$content_map->report_fields = [];

$content_map->report_fields['date_start'] = $date_start->format('Y/m/d');
$content_map->report_fields['date_end'] = $date_end->format('Y/m/d');
$content_map->report_fields['account_managers'] = array_map(function($am) {
	$r = ['account_manager_id' => $am->id(), 'name' => $am->get_name()];
	if (!empty($_GET['account_manager_id']) && $_GET['account_manager_id'] == $am->id()) $r['selected?'] = 1;
	return $r;
}, $account_managers);
if ($paid_in_full) $content_map->report_fields['paid_in_full?'] = 1;

$content_map->invoices = $invoices;

$content_map->manager_totals = array_map(function($ttl) {
	$ttl['total'] = CK\text::monetize($ttl['total']);
	return $ttl;
}, array_values($manager_totals));

$cktpl->content('includes/templates/page-sales-commission-report.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
