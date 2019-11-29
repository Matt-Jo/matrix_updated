<?php
require('includes/application_top.php');
$companies = ck_customer2::get_customers_with_outstanding_invoices();

$agingsub30 = 0;
$agingsub60 = 0;
$agingsub90 = 0;
$aging91plus = 0;

$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$worksheet = $workbook->getSheet(0);
$worksheet->setTitle('ARexport');

$worksheet->getCellByColumnAndRow(1, 1)->setValue('Company');
$worksheet->getCellByColumnAndRow(2, 1)->setValue('Terms');
$worksheet->getCellByColumnAndRow(3, 1)->setValue('Account Contact');
$worksheet->getCellByColumnAndRow(4, 1)->setValue('Account Manager');
$worksheet->getCellByColumnAndRow(5, 1)->setValue('Invoice');
$worksheet->getCellByColumnAndRow(6, 1)->setValue('Date');
$worksheet->getCellByColumnAndRow(7, 1)->setValue('Days');
$worksheet->getCellByColumnAndRow(8, 1)->setValue('Customer PO');
$worksheet->getCellByColumnAndRow(9, 1)->setValue('CK Order');
$worksheet->getCellByColumnAndRow(10, 1)->setValue('Total');
$worksheet->getCellByColumnAndRow(11, 1)->setValue('Applied Payments');
$worksheet->getCellByColumnAndRow(12, 1)->setValue('0-40');
$worksheet->getCellByColumnAndRow(13, 1)->setValue('41-60');
$worksheet->getCellByColumnAndRow(14, 1)->setValue('61-90');
$worksheet->getCellByColumnAndRow(15, 1)->setValue('91+');

$i = 2;

foreach ($companies as $customer) {
	foreach ($customer->get_outstanding_invoices_direct() as $invoice) {
		$invoice_age = $invoice->get_age();

		if ($invoice_age <= 40) {
			$agingCategory = 30;
		}
		elseif ($invoice_age > 40 && $invoice_age <= 60) {
			$agingCategory = 60;
		}
		elseif ($invoice_age > 61 && $invoice_age <= 90) {
			$agingCategory = 90;
		}
		else {
			$agingCategory = 91;
		}

		$po_number = $invoice->get_terms_po_number();

		$account_manager = $customer->get_account_manager();

		$worksheet->getCellByColumnAndRow(1, $i)->setValue($customer->get_highest_name());
		$worksheet->getCellByColumnAndRow(2, $i)->setValue($customer->getTerms());
		$worksheet->getCellByColumnAndRow(3, $i)->setValue($customer->getAccountContactName().' - '.$customer->getAccountContactEmail().' - '.$customer->getAccountContactPhone());
		$worksheet->getCellByColumnAndRow(4, $i)->setValue(!empty($account_manager)?$account_manager->get_name():NULL);
		$worksheet->getCellByColumnAndRow(5, $i)->setValue($invoice->id());
		$worksheet->getCellByColumnAndRow(6, $i)->setValue($invoice->get_header('invoice_date')->format('m/d/Y'));
		$worksheet->getCellByColumnAndRow(7, $i)->setValue($invoice_age);
		$worksheet->getCellByColumnAndRow(8, $i)->setValue($po_number);
		$worksheet->getCellByColumnAndRow(9, $i)->setValue($invoice->get_header('orders_id'));
		$worksheet->getCellByColumnAndRow(10, $i)->setValue(money_format('%n', $invoice->get_simple_totals('total')));
		$worksheet->getCellByColumnAndRow(11, $i)->setValue(money_format('%n', $invoice->get_paid()));
		$worksheet->getCellByColumnAndRow(12, $i)->setValue($agingCategory==30?money_format('%n', $invoice->get_balance()):' ');
		$worksheet->getCellByColumnAndRow(13, $i)->setValue($agingCategory==60?money_format('%n', $invoice->get_balance()):' ');
		$worksheet->getCellByColumnAndRow(14, $i)->setValue($agingCategory==90?money_format('%n', $invoice->get_balance()):' ');
		$worksheet->getCellByColumnAndRow(15, $i)->setValue($agingCategory==120?money_format('%n', $invoice->get_balance()):' ');
		$i++;
	}
}

$wb_file = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($workbook, 'Xlsx');
$wb_file->save('/tmp/arreport.xlsx');

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");;
header("Content-Disposition: attachment; filename=arreport.xlsx");
header("Content-Transfer-Encoding: binary ");
header("Content-Length: ".filesize('/tmp/arreport.xlsx'));
readfile('/tmp/arreport.xlsx');
?>
