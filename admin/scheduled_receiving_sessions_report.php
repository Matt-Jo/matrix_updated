<?php
require_once(__DIR__.'/../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;
$path = dirname(__FILE__);

$cli_flag = [];
if ($cli && !empty($argv[1])) {
	for ($i=1; $i<count($argv); $i++) {
		$flag = explode('=', $argv[$i], 2);
		$cli_flag[$flag[0]] = !empty($flag[1])?$flag[1]:TRUE;
	}
}

$today = new DateTime(date('Y-m-d'));

if ($cli && !empty($cli_flag['--date'])) $date = new DateTime($cli_flag['--date']);
elseif (!$cli && !empty($_REQUEST['date'])) $date = new DateTime($_REQUEST['date']);
else  {
	$date = new DateTime();
	// we're running this with the intention of grabbing all of yesterday's details
	$date->sub(new DateInterval('P1D'));
}

$receiving_sessions = prepared_query::fetch('SELECT pors.`date`, po.purchase_order_number, v.vendors_company_name, SUM((pop.cost * porp.quantity_received)) as cost, pot.text as payment_method FROM purchase_order_receiving_sessions pors LEFT JOIN purchase_orders po ON pors.purchase_order_id = po.id LEFT JOIN vendors v ON po.vendor = v.vendors_id LEFT JOIN purchase_order_received_products porp ON pors.id = porp.receiving_session_id AND porp.paid = 0 LEFT JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id LEFT JOIN purchase_order_terms pot ON po.terms = pot.id WHERE DATE(pors.`date`) = :date GROUP BY pors.id ORDER BY po.purchase_order_number ASC', cardinality::SET, [':date' => $date->format('Y-m-d')]);

$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$worksheet = $workbook->getSheet(0);
$worksheet->setTitle('Product Feed Exceptions');

$worksheet->getCell('A1')->setValue('Vendor');
$worksheet->getCell('B1')->setValue('PO Number');
$worksheet->getCell('C1')->setValue('Date');
$worksheet->getCell('D1')->setValue('Price');
$worksheet->getCell('E1')->setValue('Payment Method');

$count = 2;

foreach ($receiving_sessions as $receiving_session) {
	$worksheet->getCell('A' . $count)->setValue($receiving_session['vendors_company_name']);
	$worksheet->getCell('B' . $count)->setValue($receiving_session['purchase_order_number']);
	$worksheet->getCell('C' . $count)->setValue($receiving_session['date']);
	$worksheet->getCell('D' . $count)->setValue(number_format($receiving_session['cost'], 4));
	$worksheet->getCell('E' . $count)->setValue($receiving_session['payment_method']);

	$count++;
}

$wb_file = new \PhpOffice\PhpSpreadsheet\Writer\Csv($workbook);
$wb_file->setDelimiter(',');
$wb_file->setLineEnding("\r\n");

$wb_file->save(__DIR__.'/../feeds/receiving_session_report.csv');

$csv = file_get_contents(__DIR__.'/../feeds/receiving_session_report.csv');

//Mail attachment to Accounting
$mailer = service_locator::get_mail_service();
$mail = $mailer->create_mail();
$mail->set_subject('Daily Receiving Sessions');
$mail->set_body(null,'Receiving Sessions Attached');
$mail->set_from('webmaster@cablesandkits.com', 'CK Webmaster');
$mail->add_to('accounting@cablesandkits.com', 'CK Accounting');

$attachment = $mail->create_attachment($csv, 'attachment.csv');

$attachment->filename = 'Receiving Sessions.csv';

$mailer->send($mail);
?>