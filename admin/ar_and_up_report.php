<?php
require_once(__DIR__.'/../includes/application_top.php');

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;

$outstanding_receivables_sql =<<<SQL
select it.invoice_total_price, sum(p2i.credit_amount) as applied, i.invoice_id, i.inv_order_id, c.customers_id,
concat(c.customers_lastname, ', ', c.customers_firstname) as customer_name, i.rma_id, i.inv_date
FROM acc_invoices i
INNER JOIN acc_invoice_totals it ON i.invoice_id = it.invoice_id
LEFT JOIN acc_payments_to_invoices p2i on i.invoice_id = p2i.invoice_id
LEFT JOIN customers c on i.customer_id = c.customers_id
WHERE i.paid_in_full = 0 AND (it.invoice_total_line_type ='ot_total' OR it.invoice_total_line_type='rma_total')
GROUP BY i.invoice_id
ORDER BY i.invoice_id asc;
SQL;

$unapplied_payments_sql =<<<SQL
SELECT		p.payment_amount AS amount,
		p.payment_amount - IF(SUM(p2i.credit_amount) IS NULL, 0, SUM(p2i.credit_amount)) AS remaining,
c.customers_id,
concat(c.customers_lastname, ', ', c.customers_firstname) as customer_name,
p.payment_id,
p.payment_ref,
p.payment_date,
pm.label as payment_method
FROM		acc_payments p
INNER JOIN	payment_method			AS pm	ON pm.id = p.payment_method_id
LEFT JOIN	acc_payments_to_invoices	AS p2i	ON p.payment_id=p2i.payment_id
LEFT JOIN	customers			AS c	ON c.customers_id = p.customer_id
WHERE p.payment_method_id not in (8, 9)
GROUP BY	p.payment_id
HAVING		remaining <> 0;
SQL;

$account_credits_sql =<<<SQL
SELECT		p.payment_amount AS amount,
		p.payment_amount - IF(SUM(p2i.credit_amount) IS NULL, 0, SUM(p2i.credit_amount)) AS remaining,
c.customers_id,
concat(c.customers_lastname, ', ', c.customers_firstname) as customer_name,
p.payment_id,
p.payment_ref,
p.payment_date,
pm.label as payment_method
FROM		acc_payments p
INNER JOIN	payment_method			AS pm	ON pm.id = p.payment_method_id
LEFT JOIN	acc_payments_to_invoices	AS p2i	ON p.payment_id=p2i.payment_id
LEFT JOIN	customers			AS c	ON c.customers_id = p.customer_id
WHERE p.payment_method_id in (8, 9)
GROUP BY	p.payment_id
HAVING		remaining <> 0;
SQL;

$unserialized_inv_sql =<<<SQL
select psc.stock_id, psc.stock_name, psc.stock_quantity, psc.average_cost
from products_stock_control psc
where psc.serialized = '0'
order by psc.stock_name asc;
SQL;

$serialized_inv_sql =<<<SQL
select psc.stock_id, psc.stock_name, s.id as serial_id, s.serial, sh.cost
from serials s
left join products_stock_control psc on s.ipn = psc.stock_id
left join serials_history sh on (sh.serial_id = s.id and sh.id = (select max(id) from serials_history where serial_id = s.id))
where s.status in (2, 3, 6);
SQL;

if (!defined('DIR_FS_CATALOG')) define('DIR_FS_CATALOG', realpath(dirname(dirname(__FILE__))).'/');
if (!defined('DIR_FS_ADMIN')) define('DIR_FS_ADMIN', DIR_FS_CATALOG.'admin/');

if ($action == 'email' || (!empty($argv) && $argv['1'] == 'email')) {
	$to_address = array('accounting@cablesandkits.com');
	//$to_address = array('jason.shinn@cablesandkits.com');
	if (!empty($_POST['to'])) {
		$to_address = array($_POST['to']);
	}

	$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$ar_worksheet = $workbook->getSheet(0);
	$ar_worksheet->setTitle('Outstanding Receivables');
	$up_worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($workbook, 'Unapplied Payments');
	$ac_worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($workbook, 'Account Credits');
	$ui_worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($workbook, 'Unserialized Inventory');
	$si_worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($workbook, 'Serialized Inventory');

	$ar_worksheet->getCellByColumnAndRow(1, 1)->setValue('Invoice Id');
	$ar_worksheet->getCellByColumnAndRow(2, 1)->setValue('Customer Id');
	$ar_worksheet->getCellByColumnAndRow(3, 1)->setValue('Customer Name');
	$ar_worksheet->getCellByColumnAndRow(4, 1)->setValue('Invoice Total');
	$ar_worksheet->getCellByColumnAndRow(5, 1)->setValue('Applied Payments');
	$ar_worksheet->getCellByColumnAndRow(6, 1)->setValue('Remaining');
	$ar_worksheet->getCellByColumnAndRow(7, 1)->setValue('Invoice Date');
	$ar_worksheet->getCellByColumnAndRow(8, 1)->setValue('Order ID');
	$ar_worksheet->getCellByColumnAndRow(9, 1)->setValue('RMA ID');

	$results = prepared_query::fetch($outstanding_receivables_sql, cardinality::SET);
	$count = 2;
	foreach ($results as $row ) {
		$ar_worksheet->getCellByColumnAndRow(1, $count)->setValue($row['invoice_id']);
		$ar_worksheet->getCellByColumnAndRow(2, $count)->setValue($row['customers_id']);
		$ar_worksheet->getCellByColumnAndRow(3, $count)->setValue($row['customer_name']);
		$ar_worksheet->getCellByColumnAndRow(4, $count)->setValue(round($row['invoice_total_price'], 2));
		$ar_worksheet->getCellByColumnAndRow(5, $count)->setValue(($row['applied'] == null ? '0' : round($row['applied'], 2)));
		$ar_worksheet->getCellByColumnAndRow(6, $count)->setValue(($row['applied'] == null ? round($row['invoice_total_price'], 2) : round($row['invoice_total_price'] - $row['applied'], 2)));
		$ar_worksheet->getCellByColumnAndRow(7, $count)->setValue(date('M-d-Y', strtotime($row['inv_date'])));
		$ar_worksheet->getCellByColumnAndRow(8, $count)->setValue(($row['inv_order_id'] == null ? '' : $row['inv_order_id']));
		$ar_worksheet->getCellByColumnAndRow(9, $count)->setValue(($row['rma_id'] == null ? '' : $row['rma_id']));
		$count++;
	}

	$up_worksheet->getCellByColumnAndRow(1, 1)->setValue('Payment Id');
	$up_worksheet->getCellByColumnAndRow(2, 1)->setValue('Customer Id');
	$up_worksheet->getCellByColumnAndRow(3, 1)->setValue('Customer Name');
	$up_worksheet->getCellByColumnAndRow(4, 1)->setValue('Payment Amount');
	$up_worksheet->getCellByColumnAndRow(5, 1)->setValue('Remaining');
	$up_worksheet->getCellByColumnAndRow(6, 1)->setValue('Payment Method');
	$up_worksheet->getCellByColumnAndRow(7, 1)->setValue('Payment Ref');
	$up_worksheet->getCellByColumnAndRow(8, 1)->setValue('Payment Date');

	$results = prepared_query::fetch($unapplied_payments_sql, cardinality::SET);
	$count = 2;
	foreach ($results as $row) {
		$up_worksheet->getCellByColumnAndRow(1, $count)->setValue($row['payment_id']);
		$up_worksheet->getCellByColumnAndRow(2, $count)->setValue($row['customers_id']);
		$up_worksheet->getCellByColumnAndRow(3, $count)->setValue($row['customer_name']);
		$up_worksheet->getCellByColumnAndRow(4, $count)->setValue(round($row['amount'], 2));
		$up_worksheet->getCellByColumnAndRow(5, $count)->setValue(round ($row['remaining'], 2));
		$up_worksheet->getCellByColumnAndRow(6, $count)->setValue($row['payment_method']);
		$up_worksheet->getCellByColumnAndRow(7, $count)->setValue($row['payment_ref']);
		$up_worksheet->getCellByColumnAndRow(8, $count)->setValue(date('M-d-Y', strtotime($row['payment_date'])));
		$count++;
	}

	$ac_worksheet->getCellByColumnAndRow(1, 1)->setValue('Payment Id');
	$ac_worksheet->getCellByColumnAndRow(2, 1)->setValue('Customer Id');
	$ac_worksheet->getCellByColumnAndRow(3, 1)->setValue('Customer Name');
	$ac_worksheet->getCellByColumnAndRow(4, 1)->setValue('Payment Amount');
	$ac_worksheet->getCellByColumnAndRow(5, 1)->setValue('Remaining');
	$ac_worksheet->getCellByColumnAndRow(6, 1)->setValue('Payment Method');
	$ac_worksheet->getCellByColumnAndRow(7, 1)->setValue('Payment Ref');
	$ac_worksheet->getCellByColumnAndRow(8, 1)->setValue('Payment Date');

	$results = prepared_query::fetch($account_credits_sql, cardinality::SET);
	$count = 2;
	foreach ($results as $row) {
		$ac_worksheet->getCellByColumnAndRow(1, $count)->setValue($row['payment_id']);
		$ac_worksheet->getCellByColumnAndRow(2, $count)->setValue($row['customers_id']);
		$ac_worksheet->getCellByColumnAndRow(3, $count)->setValue($row['customer_name']);
		$ac_worksheet->getCellByColumnAndRow(4, $count)->setValue(round($row['amount'], 2));
		$ac_worksheet->getCellByColumnAndRow(5, $count)->setValue(round ($row['remaining'], 2));
		$ac_worksheet->getCellByColumnAndRow(6, $count)->setValue($row['payment_method']);
		$ac_worksheet->getCellByColumnAndRow(7, $count)->setValue($row['payment_ref']);
		$ac_worksheet->getCellByColumnAndRow(8, $count)->setValue(date('M-d-Y', strtotime($row['payment_date'])));
		$count++;
	}

	$ui_worksheet->getCellByColumnAndRow(1, 1)->setValue('Stock Id');
	$ui_worksheet->getCellByColumnAndRow(2, 1)->setValue('Stock Name');
	$ui_worksheet->getCellByColumnAndRow(3, 1)->setValue('Quantity');
	$ui_worksheet->getCellByColumnAndRow(4, 1)->setValue('Average Cost');
	$ui_worksheet->getCellByColumnAndRow(5, 1)->setValue('Extended Cost');

	$results = prepared_query::fetch($unserialized_inv_sql, cardinality::SET);
	$count = 2;
	foreach ($results as $row) {
		$ui_worksheet->getCellByColumnAndRow(1, $count)->setValue($row['stock_id']);
		$ui_worksheet->getCellByColumnAndRow(2, $count)->setValue($row['stock_name']);
		$ui_worksheet->getCellByColumnAndRow(3, $count)->setValue($row['stock_quantity']);
		$ui_worksheet->getCellByColumnAndRow(4, $count)->setValue(round($row['average_cost'], 2));
		$ui_worksheet->getCellByColumnAndRow(5, $count)->setValue(round($row['average_cost'] * $row['stock_quantity'], 2));
		$count++;
	}

	$si_worksheet->getCellByColumnAndRow(1, 1)->setValue('Stock Id');
	$si_worksheet->getCellByColumnAndRow(2, 1)->setValue('Stock Name');
	$si_worksheet->getCellByColumnAndRow(3, 1)->setValue('Serial Id');
	$si_worksheet->getCellByColumnAndRow(4, 1)->setValue('Serial');
	$si_worksheet->getCellByColumnAndRow(5, 1)->setValue('Cost');

    $results = prepared_query::fetch($serialized_inv_sql, cardinality::SET);
    $count = 2;
    foreach ($results as $row) {
		$si_worksheet->getCellByColumnAndRow(1, $count)->setValue($row['stock_id']);
		$si_worksheet->getCellByColumnAndRow(2, $count)->setValue($row['stock_name']);
		$si_worksheet->getCellByColumnAndRow(3, $count)->setValue($row['serial_id']);
		$si_worksheet->getCellByColumnAndRow(4, $count)->setValue($row['serial']);
		$si_worksheet->getCellByColumnAndRow(5, $count)->setValue(round($row['cost'], 2));
		$count++;
	}

	$workbook->addSheet($up_worksheet, 1);
	$workbook->addSheet($ac_worksheet, 2);
	$workbook->addSheet($ui_worksheet, 3);
	$workbook->addSheet($si_worksheet, 4);

	$wb_file = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($workbook, 'Xlsx');
	$dir = DIR_FS_CATALOG.'feeds';
	$file = 'accounting.xlsx';
	//var_dump([$dir.'/'.$file, is_dir($dir), is_writeable($dir)]);
	$wb_file->save($dir.'/'.$file);

	//$workbook->disconnectWorksheets();
	//unset($workbook);
	//unset($ar_worksheet);
	//unset($up_worksheet);
	//unset($ui_worksheet);
	//unset($si_worksheet);

	if (is_file($dir.'/'.$file)) {
        $mailer = service_locator::get_mail_service();

        $mail = $mailer->create_mail()
		    ->set_body(null,'Accounting reconciliation spreadsheet attached.')
		    ->set_from('webmaster@cablesandkits.com')
            ->set_subject('Accounting Totals Spreadsheet')
            ->create_attachment(file_get_contents($dir.'/'.$file), 'accounting.xlsx');
        
        foreach ($to_address as $unused => $addr) {
			$mail->add_to($addr);
		}

		$mailer->send($mail);

		unlink($dir.'/'.$file);
	}
	else {
		echo 'FAILED TO WRITE FILE';
	}

	if (!empty($_POST['to'])) {
		header('Location: ar_and_up_report.php');
	}
	die();
}

$type = 'ar';
if (@$_GET['type'] == 'up') {
	$type = 'up';
}
else if (@$_GET['type'] == 'ac') {
	$type = 'ac';
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html> <head> <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<style type="text/css">
	.dataTableHeadingContent {width: 100px;}
</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php
	require(DIR_WS_INCLUDES.'header.php');
?>
<!-- header_eof //-->
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#results').tablesorter();

		$('a#print').click(function() {
			window.print();
		});
	});
</script>
<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
 <tr>
	<td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php
 require(DIR_WS_INCLUDES.'column_left.php'); ?>
<!-- left_navigation_eof //-->
		</table>
</td>
<!-- body_text //-->
	<td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td class="pageHeading">Outstanding Receivables and Unapplied Payments Report</td>
			<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
		</tr>
		</table></td>
	</tr>
	<tr>
		<td>
		<table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td valign="top">
				<div style="margin: 15px;">
				<form method="POST" action="ar_and_up_report.php">
					<input type="hidden" name="action" value="email"/>
					<input type="text" name="to" value="accounting@cablesandkits.com"/>
					<input type="submit" value="Email Spreadsheet"/>
				</form>
				</div>
				<div style="font-size:8px; float: right; margin-right: 20px;">
					<a href="#" id="print">print</a>
				</div>
<?php /* MMD - two possible different views to display - toggle based on 'type' param */
if ($type == 'ar') {
?>
				<div style="font-size: 12px; float: left; margin-left: 20px; font-family: arial;">
					Type:
					<b>Outstanding Receivables</b>
					<a href="<?= $_SERVER['PHP_SELF']; ?>?type=up">Unapplied Payments</a>
					<a href="<?= $_SERVER['PHP_SELF']; ?>?type=ac">Account Credits</a>
				</div>
				<div style="clear:both;">
					<table id="results" class="tablesorter">
						<thead><tr>
							<th>Invoice Id</th>
							<th>Customer Id</th>
							<th>Customer Name</th>
							<th>Invoice Total</th>
							<th>Applied Payments</th>
							<th>Remaining</th>
							<th>Invoice Date</th>
							<th>Order ID</th>
							<th>RMA ID</th>
						</tr></thead>
						<tbody>
							<?php $results = prepared_query::fetch($outstanding_receivables_sql, cardinality::SET); ?>
                            <?php foreach ($results as $row): ?>
							<tr>
								<td class="dataTableContent"><?= $row['invoice_id']; ?></td>
								<td class="dataTableContent"><?= $row['customers_id']; ?></td>
								<td class="dataTableContent"><?= $row['customer_name']; ?></td>
								<td class="dataTableContent"><?php echo money_format('%n', $row['invoice_total_price']);?></td>
								<td class="dataTableContent"><?php echo ($row['applied'] == null ? '0' : money_format('%n', $row['applied']));?></td>
								<td class="dataTableContent"><?php echo ($row['applied'] == null ? money_format('%n', $row['invoice_total_price']) : money_format('%n', $row['invoice_total_price'] - $row['applied']));?></td>
								<td class="dataTableContent"><?php echo date('M-d-Y', strtotime($row['inv_date']));?></td>
								<td class="dataTableContent"><?php echo ($row['inv_order_id'] == null ? '' : $row['inv_order_id']);?></td>
								<td class="dataTableContent"><?php echo ($row['rma_id'] == null ? '' : $row['rma_id']);?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
<?php } else if ($type == 'up') { ?>
				<div style="font-size: 12px; float: left; margin-left: 20px; font-family: arial;">
					Type:
					<a href="<?= $_SERVER['PHP_SELF']; ?>?type=ar">Outstanding Receivables</a>
					<b>Unapplied Payments</b>
					<a href="<?= $_SERVER['PHP_SELF']; ?>?type=ac">Account Credits</a>
				</div>
				<div style="clear:both;">
					<table id="results" class="tablesorter">
						<thead><tr>
							<th>Payment Id</th>
							<th>Customer Id</th>
							<th>Customer Name</th>
							<th>Payment Amount</th>
							<th>Remaining</th>
							<th>Payment Method</th>
							<th>Payment Ref</th>
							<th>Payment Date</th>
						</tr></thead>
						<tbody>
							<?php $results = prepared_query::fetch($unapplied_payments_sql, cardinality::SET); ?>
							<?php foreach ($results as $row): ?>
							<tr>
								<td class="dataTableContent"><?= $row['payment_id']; ?></td>
								<td class="dataTableContent"><?= $row['customers_id']; ?></td>
								<td class="dataTableContent"><?= $row['customer_name']; ?></td>
								<td class="dataTableContent"><?php echo money_format('%n', $row['amount']);?></td>
								<td class="dataTableContent"><?php echo money_format('%n', $row['remaining']);?></td>
								<td class="dataTableContent"><?= $row['payment_method']; ?></td>
								<td class="dataTableContent"><?= $row['payment_ref']; ?></td>
								<td class="dataTableContent"><?php echo date('M-d-Y', strtotime($row['payment_date']));?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
<?php } else if ($type == 'ac') { ?>
				<div style="font-size: 12px; float: left; margin-left: 20px; font-family: arial;">
					Type:
					<a href="<?= $_SERVER['PHP_SELF']; ?>?type=ar">Outstanding Receivables</a>
					<a href="<?= $_SERVER['PHP_SELF']; ?>?type=up">Unapplied Payments</a>
					<b>Account Credits</b>
				</div>
				<div style="clear:both;">
					<table id="results" class="tablesorter">
						<thead><tr>
							<th>Payment Id</th>
							<th>Customer Id</th>
							<th>Customer Name</th>
							<th>Payment Amount</th>
							<th>Remaining</th>
							<th>Payment Method</th>
							<th>Payment Ref</th>
							<th>Payment Date</th>
						</tr></thead>
						<tbody>
							<?php $results = prepared_query::fetch($account_credits_sql, cardinality::SET); ?>
                            <?php foreach ($results as $row): ?>
							<tr>
								<td class="dataTableContent"><?= $row['payment_id']; ?></td>
								<td class="dataTableContent"><?= $row['customers_id']; ?></td>
								<td class="dataTableContent"><?= $row['customer_name']; ?></td>
								<td class="dataTableContent"><?php echo money_format('%n', $row['amount']);?></td>
								<td class="dataTableContent"><?php echo money_format('%n', $row['remaining']);?></td>
								<td class="dataTableContent"><?= $row['payment_method']; ?></td>
								<td class="dataTableContent"><?= $row['payment_ref']; ?></td>
								<td class="dataTableContent"><?php echo date('M-d-Y', strtotime($row['payment_date']));?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
<?php } ?>

			</td>
			</tr>
		</table>
		</td>
	</tr>
	</td>
<!-- body_text_eof //-->
 </tr>
</table>
<!-- body_eof //-->

</body>
</html>
