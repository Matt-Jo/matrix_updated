<?php
require_once(__DIR__.'/../../includes/application_top.php');

$dnb_reminder_query = prepared_query::fetch('select psc.stock_id from products_stock_control psc where (datediff(now(), psc.donotbuy_date) % 14) = 0 and psc.donotbuy = 1 and (datediff(now(), psc.donotbuy_date)) > 0', cardinality::SET);
foreach ($dnb_reminder_query as $unused => $dnb_reminder) {

	$ipn = new ck_ipn2($dnb_reminder['stock_id']);
	$user = new ck_admin($ipn->get_header('donotbuy_user_id'));

	$body_text = $user->get_name().",\n\nYou set the ipn ".$ipn->get_header('ipn')." to \"Do Not Buy\" on ".$ipn->get_header('donotbuy_date')->format('Y-m-d').". If this setting is still required, no action is required. If it is not required, please return to the IPN Editor and uncheck the box.";

	$mailer = service_locator::get_mail_service();
    $mail = $mailer->create_mail();
	$mail->set_body(null,$body_text);
	$mail->set_body(nl2br($body_text));
	$mail->set_from('webmaster@cablesandkits.com', 'CK Webmaster');
	$mail->add_to($user->get_header('email_address'), $user->get_name());
	$mail->set_subject('IPN '.$ipn->get_header('ipn').' - Please Review "Do Not Buy" Status');
	$mailer->send($mail);
}
?>
