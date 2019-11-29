<?php
chdir('..');
require('includes/application_top.php');

$limbo_orders = prepared_query::fetch("select o.orders_id from orders o where o.orders_status not in (select os.orders_status_id from orders_status os) OR (o.orders_status not in (3, 6, 9) AND (select count(*) from orders_total ot where ot.orders_id = o.orders_id and ot.class = 'ot_shipping') = 0) order by o.orders_id asc", cardinality::SET);

//process orders in limbo
$send_limbo_email = false;
$limbo_content = '';
foreach ($limbo_orders as $next_order) {
	$send_limbo_email = true;
	$limbo_content .= $next_order['orders_id']."<br/>";
}
if ($send_limbo_email) {
	$body = "The following orders either do not have a shipping method set or do not have a valid order status: <br/><br/>".$limbo_content;
    $mailer = service_locator::get_mail_service();
    $mail = $mailer->create_mail()
	    ->set_subject("EMERGENCY: Abandon ship immediately, but please address these orders in limbo first.")
	    ->set_from(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER)
	    ->set_body($body)
        ->add_to("sales@cablesandkits.com");
        
	$mailer->send($mail);
}
