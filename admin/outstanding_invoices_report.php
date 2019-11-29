<?php
require('includes/application_top.php');
require_once('includes/modules/accounting_notes.php');

$action = isset($_GET['action'])?$_GET['action']:NULL;
$aging = isset($_GET['aging'])?$_GET['aging']:NULL;
$admin_id = isset($_GET['admin_id'])?$_GET['admin_id']:NULL;
$terms = isset($_GET['terms'])?$_GET['terms']:NULL;
$sales_team_id = isset($_GET['sales_team_id'])?$_GET['sales_team_id']:NULL;

// if we've got an invoice that's 27 years old that we're still interested in, odds are we'll note when it's missing
$default_end_date = 9999;

$account_managers = ck_admin::get_account_managers(['ck_admin', 'sort_by_name']);
$sales_teams = ck_team::get_sales_teams();

$customers = array();
if ($action == 'Build Report') {
	$customer_select = "SELECT DISTINCT c.customers_id, c.dealer_pay_module, CASE WHEN c.dealer_pay_module = 1 THEN 'NET 10' WHEN c.dealer_pay_module = 2 THEN 'NET 15' WHEN c.dealer_pay_module = 3 THEN 'NET 30' WHEN c.dealer_pay_module = 4 THEN 'NET 45' ELSE 'No Terms' END as terms, CONCAT_WS(' ', c.customers_firstname, c.customers_lastname) as customer_name, CASE WHEN c.company_account_contact_name IS NULL OR TRIM(c.company_account_contact_name) = '' THEN CONCAT_WS(' ', c.customers_firstname, c.customers_lastname) ELSE c.company_account_contact_name END as contact_name, CASE WHEN c.company_account_contact_email IS NULL OR TRIM(c.company_account_contact_email) = '' THEN c.customers_email_address ELSE c.company_account_contact_email END as contact_email, CASE WHEN c.company_account_contact_phone_number IS NULL OR TRIM(c.company_account_contact_phone_number) = '' THEN c.customers_telephone ELSE c.company_account_contact_phone_number END as contact_phone, CASE WHEN a.entry_company IS NULL OR TRIM(a.entry_company) = '' THEN CONCAT_WS(' ', c.customers_firstname, c.customers_lastname) ELSE a.entry_company END as company_name, c.account_manager_id, CONCAT_WS(' ', adm.admin_firstname, adm.admin_lastname) as manager";
	$customer_from = 'FROM customers c LEFT JOIN address_book a ON a.address_book_id = c.customers_default_address_id LEFT JOIN admin adm ON adm.admin_id = c.account_manager_id JOIN acc_invoices i ON c.customers_id = i.customer_id';
	$customer_where = 'WHERE i.paid_in_full = 0';
	$customer_order = 'ORDER BY company_name ASC, customer_name ASC';

	$invoice_select = "SELECT i.invoice_id, i.customer_id, i.inv_order_id, i.po_number, it.invoice_total_price as invoice_total, o.payment_method_id, i.inv_date, DATEDIFF(CURDATE(), DATE(i.inv_date)) as invoice_age, CASE WHEN DATEDIFF(CURDATE(), DATE(i.inv_date)) <= 30 THEN '0-30' WHEN DATEDIFF(CURDATE(), DATE(i.inv_date)) > 30 AND DATEDIFF(CURDATE(), DATE(i.inv_date)) <= 45 THEN '31-45' WHEN DATEDIFF(CURDATE(), DATE(i.inv_date)) > 45 AND DATEDIFF(CURDATE(), DATE(i.inv_date)) <= 60 THEN '46-60' WHEN DATEDIFF(CURDATE(), DATE(i.inv_date)) > 60 AND DATEDIFF(CURDATE(), DATE(i.inv_date)) <= 90 THEN '61-90' ELSE '91+' END as age_group, DATE(i.inv_date) as invoice_date, o.net10_po, o.net15_po, o.net30_po, o.net45_po, CASE WHEN o.net10_po IS NOT NULL AND TRIM(o.net10_po) != '' THEN o.net10_po WHEN o.net15_po IS NOT NULL AND TRIM(o.net15_po) != '' THEN o.net15_po WHEN o.net30_po IS NOT NULL AND TRIM(o.net30_po) != '' THEN o.net30_po WHEN o.net45_po IS NOT NULL AND TRIM(o.net45_po) != '' THEN o.net45_po ELSE NULL END as order_po";
	$invoice_from = "FROM acc_invoices i JOIN acc_invoice_totals it ON i.invoice_id = it.invoice_id AND it.invoice_total_line_type IN ('ot_total', 'rma_total') LEFT JOIN orders o ON i.inv_order_id = o.orders_id";
	$invoice_where = 'WHERE i.paid_in_full = 0';
	$invoice_order = 'ORDER BY invoice_age DESC';

	$inv_pmt_select = 'SELECT i.invoice_id, SUM(apti.credit_amount) as invoice_payment';
	$inv_pmt_from = 'FROM acc_payments_to_invoices apti JOIN acc_invoices i ON apti.invoice_id = i.invoice_id';
	$inv_pmt_where = 'WHERE i.paid_in_full = 0';
	$inv_pmt_group = 'GROUP BY i.invoice_id';
	$inv_pmt_order = ''; //'ORDER BY c.customers_id';

	$unapplied_pmt_select = "SELECT DISTINCT ap.payment_id, ap.customer_id, ap.payment_amount, ap.payment_date, pm.label as pmt_method, ap.payment_ref, SUM(apti.credit_amount) as applied_amount";
	$unapplied_pmt_from = 'FROM acc_payments ap JOIN payment_method pm ON ap.payment_method_id = pm.id LEFT JOIN acc_payments_to_invoices apti ON ap.payment_id = apti.payment_id';
	$unapplied_pmt_where = '';
	$unapplied_pmt_having = 'HAVING ap.payment_amount > COALESCE(SUM(apti.credit_amount), 0)';
	$unapplied_pmt_group = 'GROUP BY ap.payment_id, ap.payment_amount, ap.payment_date, pm.label, ap.payment_ref';
	$unapplied_pmt_order = 'ORDER BY ap.customer_id';

	$params = $invoice_params = $unapplied_params = array();
	if (!empty($aging)) {
		if ($aging == 'custom') {
			$params[] = $_GET['aging-start']?$_GET['aging-start']:0;
			$params[] = $_GET['aging-end']?$_GET['aging-end']:$default_end_date;
			$customer_where .= ' AND DATEDIFF(CURDATE(), DATE(i.inv_date)) >= ? AND DATEDIFF(CURDATE(), DATE(i.inv_date)) <= ?';
		}
		elseif ($aging != 'dates') {
			$params = array_merge($params, explode('-', $aging));
			$customer_where .= ' AND DATEDIFF(CURDATE(), DATE(i.inv_date)) >= ? AND DATEDIFF(CURDATE(), DATE(i.inv_date)) <= ?';
		}
		else {
			if (preg_match('/-/', $_GET['due_date'])) list($due_date_start, $due_date_end) = explode('-', $_GET['due_date'], 2);
			else $due_date_start = $due_date_end = $_GET['due_date'];

			$due_date_start = DateTime::createFromFormat('m/d/Y H:i:s', $due_date_start.' 00:00:00');
			$due_date_end = DateTime::createFromFormat('m/d/Y H:i:s', $due_date_end.' 00:00:00');

			$params[] = $due_date_start->format('Y-m-d');
			$params[] = $due_date_end->format('Y-m-d');
			// both dealer_pay_module = 3 and no dealer_pay_module get treated as 30 day terms
			$customer_where .= ' AND DATE(i.inv_date) + INTERVAL CASE WHEN c.dealer_pay_module = 1 THEN 10 WHEN c.dealer_pay_module = 2 THEN 15 WHEN c.dealer_pay_module = 4 THEN 45 ELSE 30 END DAY >= ? AND DATE(i.inv_date) + INTERVAL CASE WHEN c.dealer_pay_module = 1 THEN 10 WHEN c.dealer_pay_module = 2 THEN 15 WHEN c.dealer_pay_module = 3 THEN 30 WHEN c.dealer_pay_module = 4 THEN 45 ELSE 0 END DAY <= ?';

			//$invoice_params[] = $due_date_start->format('Y-m-d');
			//$invoice_params[] = $due_date_end->format('Y-m-d');
			$invoice_select .= ', DATE(i.inv_date) + INTERVAL CASE WHEN c.dealer_pay_module = 1 THEN 10 WHEN c.dealer_pay_module = 2 THEN 15 WHEN c.dealer_pay_module = 4 THEN 45 ELSE 30 END DAY as due_date';
			$invoice_from .= ' JOIN customers c ON i.customer_id = c.customers_id';
		}
	}

	if (!empty($admin_id)) {
		$customer_from .= ' left join orders o on i.inv_order_id = o.orders_id ';
		if ($aging != 'dates') $invoice_from .= ' JOIN customers c ON i.customer_id = c.customers_id';
		$inv_pmt_from .= ' JOIN customers c ON i.customer_id = c.customers_id left join orders o on i.inv_order_id = o.orders_id ';
		$unapplied_pmt_from .= ' JOIN customers c ON ap.customer_id = c.customers_id';

		if ($admin_id == 'NONE') {
			$customer_where .= ' AND (NULLIF(c.account_manager_id, 0) IS NULL AND NULLIF(o.orders_sales_rep_id, 0) IS NULL)';
			$invoice_where .= ' AND (NULLIF(c.account_manager_id, 0) IS NULL AND NULLIF(o.orders_sales_rep_id, 0) IS NULL)';
			$inv_pmt_where .= ' AND (NULLIF(c.account_manager_id, 0) IS NULL AND NULLIF(o.orders_sales_rep_id, 0) IS NULL)';
			$unapplied_pmt_where = 'WHERE NULLIF(c.account_manager_id, 0) IS NULL';
		}
		else {
			$params[] = $admin_id;
			$params[] = $admin_id;
			$customer_where .= ' AND (c.account_manager_id = ? OR o.orders_sales_rep_id = ?)';

			$invoice_params[] = $admin_id;
			$invoice_params[] = $admin_id;
			$invoice_where .= ' AND (c.account_manager_id = ? or o.orders_sales_rep_id = ?)';

			$inv_pmt_where .= ' AND (c.account_manager_id = ? or o.orders_sales_rep_id = ?)';

			$unapplied_params[] = $admin_id;
			$unapplied_pmt_where = 'WHERE c.account_manager_id = ?';
		}
	}

	if (!empty($sales_team_id)) {
		if (empty($admin_id)) {
			$customer_from .= ' left join orders o on i.inv_order_id = o.orders_id ';
			if ($aging != 'dates') $invoice_from .= ' JOIN customers c ON i.customer_id = c.customers_id';
			$inv_pmt_from .= ' JOIN customers c ON i.customer_id = c.customers_id left join orders o on i.inv_order_id = o.orders_id ';
			$unapplied_pmt_from .= ' JOIN customers c ON ap.customer_id = c.customers_id';
		}

		if ($sales_team_id == 'NONE') {
			$customer_where .= ' AND (c.sales_team_id IS NULL AND o.sales_team_id IS NULL)';
			$invoice_where .= ' AND (c.sales_team_id IS NULL AND o.sales_team_id IS NULL)';
			$inv_pmt_where .= ' AND (c.sales_team_id IS NULL AND o.sales_team_id IS NULL)';
			$unapplied_pmt_where = 'WHERE c.sales_team_id IS NULL';
		}
		else {
			$params[] = $sales_team_id;
			$params[] = $sales_team_id;
			$customer_where .= ' AND (c.sales_team_id = ? OR o.sales_team_id = ?)';

			$invoice_params[] = $sales_team_id;
			$invoice_params[] = $sales_team_id;
			$invoice_where .= ' AND (c.sales_team_id = ? or o.sales_team_id = ?)';

			$inv_pmt_where .= ' AND (c.sales_team_id = ? or o.sales_team_id = ?)';

			$unapplied_params[] = $sales_team_id;
			$unapplied_pmt_where = 'WHERE c.sales_team_id = ?';
		}
	}

	if (is_numeric($terms)) { // could be 0, to indicate no terms
		$params[] = $terms;
		$customer_where .= ' AND c.dealer_pay_module = ?';

		$invoice_params[] = $terms;
		if ($aging != 'dates' && empty($admin_id) && empty($sales_team_id)) $invoice_from .= ' JOIN customers c ON i.customer_id = c.customers_id';
		$invoice_where .= ' AND c.dealer_pay_module = ?';

		if (empty($admin_id) && empty($sales_team_id)) $inv_pmt_from .= ' JOIN customers c ON i.customer_id = c.customers_id';
		$inv_pmt_where .= ' AND c.dealer_pay_module = ?';

		$unapplied_params[] = $terms;
		if (empty($admin_id) && empty($sales_team_id)) $unapplied_pmt_from .= ' JOIN customers c ON ap.customer_id = c.customers_id';
		if (empty($unapplied_pmt_where)) $unapplied_pmt_where = 'WHERE true';
		$unapplied_pmt_where .= ' AND c.dealer_pay_module = ?';
	}

	//echo ("$customer_select $customer_from $customer_where $customer_order");
	// populate the customers array in the order we want it, alphabetical
	$customers_list = prepared_query::fetch("$customer_select $customer_from $customer_where $customer_order", cardinality::SET, $params);

	// ultimately, we're pretty much grabbing all invoices regardless of the date range, because we want to show all for a customer even if we're only interested in customers that have invoices in the selected date range
	$invoices = prepared_query::fetch("$invoice_select $invoice_from $invoice_where $invoice_order", cardinality::SET, $invoice_params);
	$invoice_payments = prepared_query::fetch("$inv_pmt_select $inv_pmt_from $inv_pmt_where $inv_pmt_group $inv_pmt_order", cardinality::SET, $invoice_params);

	$unapplied_pmts = prepared_query::fetch("$unapplied_pmt_select $unapplied_pmt_from $unapplied_pmt_where $unapplied_pmt_group $unapplied_pmt_having $unapplied_pmt_order", cardinality::SET, $unapplied_params);

	$total = 0;
	$payments = 0;
	$balance = 0;
	$total_unapplied = 0;
	$aging_results = array('0-30' => 0, '31-45' => 0, '46-60' => 0, '61-90' => 0, '91+' => 0);
	if (!empty($aging)) {
		$aging_total = 0;
		$aging_payments = 0;
		$aging_balance = 0;
		$aging_total_unapplied = 0;
		$aging_aging_results = array('0-30' => 0, '31-45' => 0, '46-60' => 0, '61-90' => 0, '91+' => 0);
	}

	foreach ($customers_list as $customer) {
		$customer = (object) $customer;

		$customer->invoices = array();
		$customer->unapplied_payments = array();

		$customer->total = 0;
		$customer->payments = 0;
		$customer->balance = 0;
		$customer->total_unapplied = 0;

		$customer->aging_results = array('0-30' => 0, '31-45' => 0, '46-60' => 0, '61-90' => 0, '91+' => 0);

		foreach ($invoices as $invoice) {
			if ($invoice['customer_id'] != $customer->customers_id) continue;
			$invoice = (object) $invoice;

			$invoice->payments = array();
			$invoice->total_paid = 0;

			$invoice->inv_date = new DateTime($invoice->inv_date);

			$customer->total += $invoice->invoice_total;
			$total += $invoice->invoice_total;

			foreach ($invoice_payments as $invoice_payment) {
				if ($invoice_payment['invoice_id'] != $invoice->invoice_id) continue;
				$invoice_payment = (object) $invoice_payment;

				$invoice->total_paid += $invoice_payment->invoice_payment;

				$invoice->payments[] = $invoice_payment;
			}

			$customer->payments += $invoice->total_paid;
			$payments += $invoice->total_paid;

			$invoice->balance = $invoice->invoice_total - $invoice->total_paid;

			$customer->balance += $invoice->balance;
			$balance += $invoice->balance;

			$customer->aging_results[$invoice->age_group] += $invoice->balance;
			$aging_results[$invoice->age_group] += $invoice->balance;

			if ($aging == 'dates') {
				$invoice->due_date = new DateTime($invoice->due_date);

				if ($invoice->due_date >= $due_date_start && $invoice->due_date <= $due_date_end) {
					$aging_total += $invoice->invoice_total;
					$aging_payments += $invoice->total_paid;
					$aging_balance += $invoice->balance;
					$aging_aging_results[$invoice->age_group] += $invoice->balance;
				}
			}
			elseif (!empty($aging)) {
				if ($aging == 'custom') {
					$aging_start = $_GET['aging-start']?$_GET['aging-start']:0;
					$aging_end = $_GET['aging-end']?$_GET['aging-end']:$default_end_date;
				}
				else {
					list($aging_start, $aging_end) = explode('-', $aging);
				}

				if ($invoice->invoice_age >= $aging_start && $invoice->invoice_age <= $aging_end) {
					$aging_total += $invoice->invoice_total;
					$aging_payments += $invoice->total_paid;
					$aging_balance += $invoice->balance;
					$aging_aging_results[$invoice->age_group] += $invoice->balance;
				}
			}

			$customer->invoices[] = $invoice;
		}

		if ($customer->invoices || !$aging) {
			foreach ($unapplied_pmts as $unapplied_pmt) {
				if ($unapplied_pmt['customer_id'] != $customer->customers_id) continue;
				$unapplied_payment = (object) $unapplied_pmt;

				$unapplied_payment->payment_date = new DateTime($unapplied_payment->payment_date);

				$customer->unapplied_payments[] = $unapplied_payment;

				$customer->total_unapplied += $unapplied_payment->payment_amount - $unapplied_payment->applied_amount;
				$total_unapplied += $unapplied_payment->payment_amount - $unapplied_payment->applied_amount;
			}
		}

		$customers[$customer->customers_id] = $customer;
	}
	unset($invoices);
	unset($invoice_payments);

	if (empty($aging)) {
		$customer_ids = array();
		foreach ($unapplied_pmts as $unapplied_pmt) {
			// if this customer has invoices, skip it
			if (isset($customers[$unapplied_pmt['customer_id']]) && $customers[$unapplied_pmt['customer_id']]->invoices) continue;

			// if this customer *is not* yet set up, set it up & log the customer ID so we can grab the full details at the end
			if (!isset($customers[$unapplied_pmt['customer_id']])) {
				$customer_ids[] = $unapplied_pmt['customer_id'];

				$customer = (object) array();

				$customer->invoices = array();
				$customer->unapplied_payments = array();

				$customer->total = 0;
				$customer->payments = 0;
				$customer->balance = 0;
				$customer->total_unapplied = 0;

				$customer->aging_results = array('0-30' => 0, '31-45' => 0, '46-60' => 0, '61-90' => 0, '91+' => 0);

				$customers[$unapplied_pmt['customer_id']] = $customer;
			}

			// add the unapplied payment
			$unapplied_payment = (object) $unapplied_pmt;

			$unapplied_payment->payment_date = new DateTime($unapplied_payment->payment_date);

			$customers[$unapplied_payment->customer_id]->unapplied_payments[] = $unapplied_payment;

			$customers[$unapplied_payment->customer_id]->total_unapplied += $unapplied_payment->payment_amount - $unapplied_payment->applied_amount;
			$total_unapplied += $unapplied_payment->payment_amount - $unapplied_payment->applied_amount;
		}

		// get the details for the new customers
		if (!empty($customer_ids)) {
			// remove the acc invoices JOIN, it was only there to ensure we only got customers with invoices, we didn't use any data, and now we're looking specifically for customers without invoices
			$customer_from = 'FROM customers c LEFT JOIN address_book a ON a.address_book_id = c.customers_default_address_id LEFT JOIN admin adm ON adm.admin_id = c.account_manager_id';

			$customer_where = 'WHERE c.customers_id IN (';
			$query_params = array_fill(0, count($customer_ids), '?');
			$customer_where .= implode(', ', $query_params);
			$customer_where .= ')';

			$customers_list = prepared_query::fetch("$customer_select $customer_from $customer_where $customer_order", cardinality::SET, $customer_ids);

			foreach ($customers_list as $customer) {
				foreach ($customer as $key => $val) {
					$customers[$customer['customers_id']]->$key = $val;
				}
			}
		}
	}
	unset($unapplied_pmts);

	usort($customers, function ($a, $b) { if (strtolower($a->company_name) == strtolower($b->company_name)) { return 0; } return strcmp(strtolower($a->company_name),strtolower($b->company_name)); });
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/general.js"></script>
	<style type="text/css">
		.dataTableContent { max-width: 250px; font-family: Arial, sans-serif; font-size: 10px; }
	</style>
	<script type="text/javascript">
		function popupWindow(url) {
			window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=800,height=800,screenX=150,screenY=150,top=150,left=150')
		}
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" style="background-color:#FFFFFF;">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td class="noPrint" width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="0">
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading">AR Report&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php if (!empty($companies)) { ?><span class="printable"><a href="#" onclick="window.print();">Print</a></span><?php } ?><span class="printable"> &nbsp;&nbsp;<a href="outstanding_invoices_report_export.php">Export Everything to excel</a></span></td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<form action="<?= $_SERVER['PHP_SELF']; ?>" method="get">
								<input type="submit" name="action" value="Build Report"><br>
								Aging:
								[<input type="radio" name="aging" value="" <?= !isset($_GET['aging'])||!$_GET['aging']?'checked':''; ?>> Everything]
								[<input type="radio" name="aging" value="0-30" <?= @$_GET['aging']=='0-30'?'checked':''; ?>> 0-30]
								[<input type="radio" name="aging" value="31-45" <?= @$_GET['aging']=='31-45'?'checked':''; ?>> 31-45]
								[<input type="radio" name="aging" value="46-60" <?= @$_GET['aging']=='46-60'?'checked':''; ?>> 46-60]
								[<input type="radio" name="aging" value="61-90" <?= @$_GET['aging']=='61-90'?'checked':''; ?>> 61-90]
								[<input type="radio" name="aging" value="91-<?= $default_end_date; ?>" <?= @$_GET['aging']=='91-'.$default_end_date?'checked':''; ?>> 91+]
								[<input class="custom_aging_radio" type="radio" name="aging" value="custom" <?= @$_GET['aging']=='custom'?'checked':''; ?>> Custom:
								<input class="custom_aging" type="text" name="aging-start" value="<?= @$_GET['aging-start']; ?>" style="width:40px;"> to
								<input class="custom_aging" type="text" name="aging-end" value="<?= @$_GET['aging-end']; ?>" style="width:40px;">]<br>

								<link href="/includes/javascript/daterangepicker.css" rel="stylesheet">
								<script src="/includes/javascript/moment.min.js"></script>
								<script src="/includes/javascript/jquery.daterangepicker.js"></script>
								[<input class="date_aging_radio" type="radio" name="aging" value="dates" <?= @$_GET['aging']=='dates'?'checked':''; ?>> Due Date in Range:
								<input id="due_date" class="date_aging" name="due_date">]
								<br>
								<script>
									jQuery('#due_date').dateRangePicker({
										format: 'MM/DD/YYYY',
										separator: '-'
									});
									<?php if ($aging == 'dates') { ?>
									jQuery('#due_date').data('dateRangePicker').setDateRange('<?= $due_date_start->format('m/d/Y'); ?>', '<?= $due_date_end->format('m/d/Y'); ?>');
									<?php } ?>

									jQuery('.custom_aging, .date_aging').focus(function() {
										jQuery('.'+jQuery(this).attr('class')+'_radio').click();
									});
								</script>

								Manager:
								<select name="admin_id" size="1">
									<option value="">All</option>
									<option value="NONE" <?= $admin_id=='NONE'?'selected':''; ?>>None</option>
									<?php foreach ($account_managers as $account_manager) { ?>
									<option value="<?= $account_manager->id(); ?>" <?= $admin_id==$account_manager->id()?'selected':''; ?>><?= $account_manager->get_normalized_name(); ?></option>
									<?php } ?>
								</select><br>
								Team:
								<select name="sales_team_id">
									<option value="">All</option>
									<option value="NONE" <?= $sales_team_id=='NONE'?'selected':''; ?>>None</option>
									<?php foreach ($sales_teams as $sales_team) { ?>
									<option value="<?= $sales_team->id(); ?>" <?= $sales_team_id==$sales_team->id()?'selected':''; ?>><?= $sales_team->get_header('label'); ?></option>
									<?php } ?>
								</select><br>
								Terms:
								<select name="terms" size="1">
									<option value="">All</option>
									<option value="0" <?= @$_GET['terms']==='0'?'selected':''; ?>>No Terms</option>
									<!--option value="1" <?= @$_GET['terms']==1?'selected':''; ?>>Net 10</option-->
									<option value="2" <?= @$_GET['terms']==2?'selected':''; ?>>Net 15</option>
									<option value="3" <?= @$_GET['terms']==3?'selected':''; ?>>Net 30</option>
									<option value="4" <?= @$_GET['terms']==4?'selected':''; ?>>Net 45</option>
								</select>
							</form>
							<?php if (!empty($customers)) { ?>
							<table border="0" width="1500" cellspacing="0" cellpadding="0">
								<tr>
									<td valign="top">
										<table border="0" width="100%" cellspacing="0" cellpadding="0">
										<?php foreach ($customers as $customer_id => $customer) {
											if (!$customer->invoices && !$customer->unapplied_payments) continue; ?>
											<tr style="background-color:#ffffff;">
												<td class="main" colspan="12">
													<strong><a href="customer_account_history.php?customer_id=<?= $customer->customers_id; ?>" target="_blank"><?= $customer->company_name; ?></a> - <?= $customer->terms; ?></strong>
													<?php insert_accounting_notes_manager($customer->customers_id, null, 'notes');?>
												</td>
												<td colspan="2">
													<a href="print_account_statement.php?customer_id=<?= $customer_id; ?>" target="_blank">Print Account Statement</a>
												</td>
											</tr>
											<tr class="dataTableHeadingRow">
												<td>&nbsp;</td>
												<td class="dataTableHeadingContent" width="300">Invoice</td>
												<td class="dataTableHeadingContent" width="100">Account Manager</td>
												<td class="dataTableHeadingContent" width="100">Date</td>
												<td class="dataTableHeadingContent" width="100">Days</td>
												<td class="dataTableHeadingContent" width="100">Customer PO</td>
												<td class="dataTableHeadingContent" width="100">CK Order</td>
												<td class="dataTableHeadingContent" width="100" align="right">Total</td>
												<td class="dataTableHeadingContent" width="100" align="right">Applied Payments</td>
												<td class="dataTableHeadingContent" width="100" align="right">0-30</td>
												<td class="dataTableHeadingContent" width="100" align="right">31-45</td>
												<td class="dataTableHeadingContent" width="100" align="right">46-60</td>
												<td class="dataTableHeadingContent" width="100" align="right">61-90</td>
												<td class="dataTableHeadingContent" width="100" align="right">91+</td>
											</tr>
											<tr style="background-color:#ffffff;">
												<td class="dataTableContent" colspan="2">
													<?= $customer->contact_name; ?> - <a href="mailto:<?= $customer->contact_email; ?>"><?= $customer->contact_email; ?></a> - <?= $customer->contact_phone; ?>
												</td>
												<td class="dataTableContent" colspan="12"><?= $customer->manager; ?></td>
											</tr>
											<tr>
												<td colspan="14" class="dataTableContent"><strong>Open Invoices</strong></td>
											</tr>
											<?php foreach ($customer->invoices as $idx => $invoice) { ?>
											<tr style="background-color:<?= ($idx%2==0)?'#ffffff':'#e0e0e0'; ?>;">
												<td style="width: 30px;">&nbsp;</td>
												<td class="dataTableContent" colspan="2">
													<a class="popwin" href="/admin/invoice.php?oID=<?= $invoice->inv_order_id; ?>&invId=<?= $invoice->invoice_id; ?>">
														<?= $invoice->invoice_id; ?>
														<?php if ($invoice->payment_method_id == 5) echo 'NET 10'; ?>
													</a>
												</td>
												<td class="dataTableContent"><?= $invoice->inv_date->format('m/d/Y'); ?></td>
												<td class="dataTableContent"><?= $invoice->invoice_age; ?></td>
												<td class="dataTableContent"><?= $invoice->order_po; ?></td>
												<td class="dataTableContent"><a href="orders_new.php?oID=<?= $invoice->inv_order_id; ?>&action=edit"><?= $invoice->inv_order_id; ?></a></td>
												<td class="dataTableContent" align="right"><?= money_format('%n', $invoice->invoice_total); ?></td>
												<td class="dataTableContent" align="right"><?= money_format('%n', $invoice->total_paid); ?></td>
												<td class="dataTableContent" align="right"><?= $invoice->age_group=='0-30'?money_format('%n', $invoice->balance):'&nbsp;'; ?></td>
												<td class="dataTableContent" align="right"><?= $invoice->age_group=='31-45'?money_format('%n', $invoice->balance):'&nbsp;'; ?></td>
												<td class="dataTableContent" align="right"><?= $invoice->age_group=='46-60'?money_format('%n', $invoice->balance):'&nbsp;'; ?></td>
												<td class="dataTableContent" align="right"><?= $invoice->age_group=='61-90'?money_format('%n', $invoice->balance):'&nbsp;'; ?></td>
												<td class="dataTableContent" align="right"><?= $invoice->age_group=='91+'?money_format('%n', $invoice->balance):'&nbsp;'; ?></td>
											</tr>
											<?php } ?>
											<tr>
												<td colspan="7" height="50"></td>
												<td class="dataTableContent" align="right"><strong><?= money_format('%n', $customer->total); ?></strong></td>
												<td class="dataTableContent" align="right"><strong><?= money_format('%n', $customer->payments); ?></strong></td>
												<td class="dataTableContent" style="border-top: 1px solid #000;" align="right"><strong><?= money_format('%n', $customer->aging_results['0-30']); ?></strong></td>
												<td class="dataTableContent" style="border-top: 1px solid #000;" align="right"><strong><?= money_format('%n', $customer->aging_results['31-45']); ?></strong></td>
												<td class="dataTableContent" style="border-top: 1px solid #000;" align="right"><strong><?= money_format('%n', $customer->aging_results['46-60']); ?></strong></td>
												<td class="dataTableContent" style="border-top: 1px solid #000;" align="right"><strong><?= money_format('%n', $customer->aging_results['61-90']); ?></strong></td>
												<td class="dataTableContent" style="border-top: 1px solid #000;" align="right"><strong><?= money_format('%n', $customer->aging_results['91+']); ?></strong></td>
											</tr>
											<tr>
												<td colspan="14" class="main" style="padding-bottom:30px; padding-top:15px;">
													<table cellpadding="0" cellspacing="0">
														<tr>
															<td colspan="5" class="dataTableContent"><strong>Unapplied Payments</strong></td>
														</tr>
														<tr>
															<td class="dataTableContent" width="100" height="15">Payment ID</td>
															<td class="dataTableContent" width="150">Date</td>
															<td class="dataTableContent" width="150">Payment Method</td>
															<td class="dataTableContent" width="100">Amount</td>
															<td class="dataTableContent" width="200">Payment Reference</td>
														</tr>
														<?php if ($customer->unapplied_payments) {
															foreach ($customer->unapplied_payments as $idx => $unapplied_payment) { ?>
														<tr style="background-color:<?= $idx%2==0?'#ffffff':'#e0e0e0'; ?>;">
															<td class="dataTableContent" height="20"><?= $unapplied_payment->payment_id; ?></td>
															<td class="dataTableContent"><?= $unapplied_payment->payment_date->format('m/d/Y'); ?></td>
															<td class="dataTableContent"><?= $unapplied_payment->pmt_method; ?></td>
															<td class="dataTableContent"><?= money_format('%n', $unapplied_payment->payment_amount-$unapplied_payment->applied_amount); ?></td>
															<td class="dataTableContent"><?= $unapplied_payment->payment_ref; ?></td>
														</tr>
															<?php }
														} ?>
														<tr>
															<td colspan="3" class="dataTableContent" style="padding-top:10px;"><strong>Total:</strong></td>
															<td class="dataTableContent" style="padding-top:10px;"><strong><?= money_format('%n', $customer->total_unapplied); ?></strong></td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td colspan="14"><hr /></td>
											</tr>
											<tr>
												<td height="30" colspan="14">&nbsp;</td>
											</tr>
										<?php } ?>
											<tr>
												<td colspan="3"></td>
												<td align="right"><strong>Totals</strong></td>
												<td align="right"><strong>Payments</strong></td>
												<td align="right" colspan="2"><strong>Net Balance</strong></td>
												<td align="right" colspan="2"><strong>Unapplied Pmts</strong></td>
												<td style="border-top: 1px solid #000;" align="right"><strong>0-30</strong></td>
												<td style="border-top: 1px solid #000;" align="right"><strong>31-45</strong></td>
												<td style="border-top: 1px solid #000;" align="right"><strong>46-60</strong></td>
												<td style="border-top: 1px solid #000;" align="right"><strong>61-90</strong></td>
												<td style="border-top: 1px solid #000;" align="right"><strong>91+</strong></td>
											</tr>
											<tr>
												<td colspan="3"></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $total); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $payments); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right" colspan="2"><?= money_format('%n', ($balance)); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right" colspan="2"><?= money_format('%n', ($total_unapplied)); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_results['0-30']); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_results['31-45']); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_results['46-60']); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_results['61-90']); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_results['91+']); ?></td>
											</tr>
											<?php if (!empty($aging)) { ?>
											<tr>
												<td colspan="3" style="align:center;vertical-align:middle">Totals limited to Aging Selection</td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_total); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_payments); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right" colspan="2"><?= money_format('%n', ($aging_balance)); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right" colspan="2"></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_aging_results['0-30']); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_aging_results['31-45']); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_aging_results['46-60']); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_aging_results['61-90']); ?></td>
												<td class="dataTableContent" style="border-top: 1px solid #000; font-weight: bold;" align="right"><?= money_format('%n', $aging_aging_results['91+']); ?></td>
											</tr>
											<?php } ?>
										</table>
									</td>
								</tr>
							</table>
							<?php } ?>
							<script>
								jQuery('.popwin').click(function(event) {
									event.preventDefault();
									popupWindow(jQuery(this).attr('href'));
									return false;
								});
							</script>
						</td>
					</tr>
				</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
<!-- body_eof //-->
</body>
</html>
