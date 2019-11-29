<?php
require('includes/application_top.php');

@ini_set('memory_limit', '2048M');
set_time_limit(0);

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;

switch ($action) {
	case 'accrue':
		if (!empty($_POST['accrual_date'])) ck_invoice::accrue_incentives($_POST['invoice_ids'], ck_datetime::datify($_POST['accrual_date'], TRUE));
		else ck_invoice::accrue_incentives($_POST['invoice_ids']);

		CK\fn::redirect_and_exit('/admin/sales-commission-accounting.php');
		break;
	case 'pay-incentive':
		if (!empty($_POST['payment_date'])) ck_invoice::pay_incentives($_POST['invoice_ids'], new DateTime($_POST['payment_date']));
		else ck_invoice::pay_incentives($_POST['invoice_ids']);

		CK\fn::redirect_and_exit('/admin/sales-commission-accounting.php');
		break;
}

//---------header-----------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//---------end header-------------

// process report
$type = 'unaccrued';

$date_start = new DateTime('first day of last month');
$date_end = new DateTime('last day of last month');

$account_managers = ck_admin::get_account_managers(['ck_admin', 'sort_by_name']);
$allowed_reps = array_map(function($am) { return $am->id(); }, $account_managers);
$allowed_reps = array_filter($allowed_reps, function($ar) { return !empty($_GET['account_manager_id'])?$_GET['account_manager_id']==$ar:!in_array($ar, [1, 118]); });

$exceptions_exist = FALSE;

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

		$type = $_GET['type'];

		$incentive_accrued = NULL;
		if (in_array($type, ['unaccrued', 'accrued'])) $incentive_accrued = $type=='unaccrued'?0:1;
		$incentive_paid = NULL;
		if (in_array($type, ['unpaid', 'paid'])) $incentive_paid = $type=='unpaid'?0:1;

		$invoices = prepared_query::fetch('SELECT * FROM (SELECT CONCAT_WS(:sep, a.admin_firstname, a.admin_lastname) as account_manager, i.customer_id as customers_id, c.customers_email_address, i.invoice_id, i.inv_order_id as orders_id, NULL as rma_id, CASE WHEN :incentive_accrued = 1 THEN i.incentive_accrued WHEN :incentive_paid = 1 THEN i.incentive_paid WHEN :incentive_paid = 0 AND i.paid_in_full = 1 AND i.credit_memo = 0 THEN ia.final_payment_date ELSE i.inv_date END as transaction_date, CASE WHEN i.credit_memo = 0 THEN ia.invoice_balance ELSE 0 END as balance, i.incentive_product_total as product_margin, i.incentive_percentage, i.incentive_final_total, i.credit_memo FROM acc_invoices i JOIN orders o ON i.inv_order_id = o.orders_id AND o.orders_sales_rep_id IN ('.implode(',', $allowed_reps).') JOIN customers c ON o.customers_id = c.customers_id JOIN admin a ON o.orders_sales_rep_id = a.admin_id LEFT JOIN ckv_invoice_accounting ia ON i.invoice_id = ia.invoice_id WHERE i.incentive_final_total IS NOT NULL AND (:incentive_accrued = 0 AND i.incentive_accrued IS NULL AND i.inv_date <= DATE(:end_date)) OR (:incentive_accrued = 1 AND i.incentive_accrued >= DATE(:start_date) AND i.incentive_accrued <= DATE(:end_date)) OR (:incentive_paid = 0 AND i.incentive_accrued IS NOT NULL AND i.paid_in_full = 1 AND i.incentive_paid IS NULL AND ((i.credit_memo = 0 AND ia.final_payment_date <= DATE(:end_date)) OR i.inv_date <= DATE(:end_date))) OR (:incentive_paid = 1 AND i.incentive_paid >= DATE(:start_date) AND i.incentive_paid <= DATE(:end_date)) UNION SELECT CONCAT_WS(:sep, a.admin_firstname, a.admin_lastname) as account_manager, i.customer_id as customers_id, c.customers_email_address, i.invoice_id, NULL as orders_id, i.rma_id, CASE WHEN :incentive_accrued = 1 THEN i.incentive_accrued WHEN :incentive_paid = 1 THEN i.incentive_paid ELSE i.inv_date END as transaction_date, 0 as balance, i.incentive_product_total as product_margin, i.incentive_percentage, i.incentive_final_total, i.credit_memo FROM acc_invoices i JOIN rma ON i.rma_id = rma.id JOIN orders o ON rma.order_id = o.orders_id AND o.orders_sales_rep_id IN ('.implode(',', $allowed_reps).') JOIN customers c ON o.customers_id = c.customers_id JOIN admin a ON o.orders_sales_rep_id = a.admin_id WHERE i.incentive_final_total IS NOT NULL AND (:incentive_accrued = 0 AND i.incentive_accrued IS NULL AND i.inv_date <= DATE(:end_date)) OR (:incentive_accrued = 1 AND i.incentive_accrued >= DATE(:start_date) AND i.incentive_accrued <= DATE(:end_date)) OR (:incentive_paid = 0 AND i.incentive_paid IS NULL AND i.inv_date <= DATE(:end_date)) OR (:incentive_paid = 1 AND i.incentive_paid >= DATE(:start_date) AND i.incentive_paid <= DATE(:end_date))) p ORDER BY account_manager ASC, transaction_date ASC', cardinality::SET, [':sep' => ' ', ':start_date' => $date_start->format('Y-m-d'), ':end_date' => $date_end->format('Y-m-d'), ':incentive_accrued' => $incentive_accrued, ':incentive_paid' => $incentive_paid]);

		if ($__FLAG['omit-exceptions']) {
			$invoices = array_values(array_filter($invoices, function($invoice) use ($date_start) {
				if ((new DateTime($invoice['transaction_date'])) < $date_start) return FALSE;
				else return TRUE;
			}));
		}

		$manager_totals = array_reduce($invoices, function($totals, $invoice) use ($date_start, $__FLAG) {
			if (empty($totals[$invoice['account_manager']])) $totals[$invoice['account_manager']] = ['account_manager' => $invoice['account_manager'], 'total' => 0];
			if ($__FLAG['omit-exceptions'] && (new DateTime($invoice['transaction_date'])) < $date_start) return $totals;
			$totals[$invoice['account_manager']]['total'] += $invoice['incentive_final_total'];
			return $totals;
		}, []);

		$invoices = array_map(function($invoice) use ($date_start) {
			if (!empty($invoice['rma_id'])) $invoice['transaction_id'] = '[RMA] '.$invoice['rma_id'];
			elseif (!empty($invoice['credit_memo'])) $invoice['transaction_id'] = '[CM] '.$invoice['invoice_id'];
			else {
				$invoice['transaction_id'] = $invoice['orders_id'];
				if ($invoice['product_margin'] < 0) $invoice['negative?'] = 1;
			}

			if ((new DateTime($invoice['transaction_date'])) < $date_start) {
				$invoice['early?'] = 1;
				$GLOBALS['exceptions_exist'] = TRUE;
			}

			$invoice['transaction_date'] = (new DateTime($invoice['transaction_date']))->format('Y-m-d');
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
$content_map->report_fields[$type.'?'] = 1;

if ($exceptions_exist) $content_map->{'exceptions_exist?'} = 1;

if (!empty($invoices)) {
	$content_map->invoices = $invoices;

	$content_map->manager_totals = array_map(function($ttl) {
		$ttl['total'] = CK\text::monetize($ttl['total']);
		return $ttl;
	}, array_values($manager_totals));
}

$cktpl->content('includes/templates/page-sales-commission-accounting.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
