<?php
require(__DIR__.'/../../includes/application_top.php');

$customers = ck_customer2::get_customers_with_late_invoices_to_notify();

$report_invoices = [];

foreach ($customers as $customer) {
	$invoices = $customer->get_late_invoices_to_notify_direct();

	$notify_invoices = [];
	$beyond_grace_period = FALSE;

	foreach ($invoices as $invoice) {
		$notify_invoices[] = $invoice;
		$report_invoices[] = $invoice;

		if ($beyond_grace_period) continue;

		if ($invoice->get_days_past_due() >= ck_invoice::LATE_NOTICE_GRACE_PERIOD) $beyond_grace_period = TRUE;
	}

	if (!$beyond_grace_period) continue;

	$fnd = $customer->get_first_late_notification_date();
	if (!empty($fnd)) {
		$today = ck_datetime::TODAY();
		$int = $today->diff($fnd, TRUE)->format('%a');

		// once we've notified them that the grace period is passed, we notify them on the grace period interval:
		// e.g. 5, 12, 19, 26, etc. for a grace period of 5 and grace interval of 7
		if ($int % ck_invoice::LATE_NOTICE_GRACE_INTERVAL != ck_invoice::LATE_NOTICE_GRACE_PERIOD) continue;
	}

	$customer->send_late_invoice_notification($fnd, $notify_invoices);
}

$reportContent = "Invoice,Customer ID,Email,Name,Days Past Due,Last Late Email,Next Late Email\n";
$reportContent .= implode("\n", array_map(function($invoice) {
	$header = $invoice->get_header();
	$customer = $invoice->get_customer();

	$row = [];
	$row[] = $invoice->id();
	$row[] = $header['customers_id'];
	$row[] = addslashes($customer->get_header('email_address'));
	$row[] = addslashes(str_replace(',', '', $customer->get_name()));

	$days_past_due = $invoice->get_days_past_due();
	$row[] = $days_past_due;

	$cflnd = $customer->get_first_late_notification_date();
	$ilnd = $invoice->get_header('late_notice_date');

	if ($days_past_due >= ck_invoice::LATE_NOTICE_GRACE_PERIOD && (!empty($cflnd) || !empty($ilnd))) {
		$last_notice = clone max($cflnd, $ilnd);
		$next_notice = clone $last_notice;
		$next_notice->add(new DateInterval('P'.ck_invoice::LATE_NOTICE_GRACE_INTERVAL.'D'));
	}
	elseif ($days_past_due >= ck_invoice::LATE_NOTICE_GRACE_PERIOD) {
		$last_notice = NULL;
		$next_notice = ck_datetime::TODAY();
		$next_notice->add(new DateInterval('P7D'));
	}
	else {
		$last_notice = NULL;
		$next_notice = ck_datetime::TODAY();
		$next_notice->add(new DateInterval('P'.(ck_invoice::LATE_NOTICE_GRACE_PERIOD-$days_past_due).'D'));
	}
	$row[] = !empty($last_notice)?$last_notice->format('Y-m-d H:i:s'):NULL;
	$row[] = $next_notice->format('Y-m-d H:i:s');

	return implode(',', $row);
}, $report_invoices));

//Now we set up the email
$mailer = service_locator::get_mail_service();
$mail = $mailer->create_mail()
    ->set_from('accounting@cablesandkits.com', 'CablesAndKits Accounting')
    ->set_subject('All Past Due Invoice Emails Report')
    ->set_body('Find report attached.')
    ->create_attachment($reportContent, 'late_payment_email_report_'.date('m_d_Y').'.csv')
    ->add_to('accounting@cablesandkits.com');
$mailer->send($mail);

