<?php
chdir('..');
require('includes/application_top.php');

//process customer service logistics orders first
$orders = prepared_query::fetch('SELECT osh.orders_id FROM orders_status_history osh WHERE osh.date_added > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND osh.orders_status_id = 11 AND osh.orders_sub_status_id = 7', cardinality::SET);
$mailer = service_locator::get_mail_service();

foreach ($orders as $next_order) {
	$body = '<a href="/admin/orders_new.php?oID='.$next_order['orders_id'].'&action=edit">Order # '.$next_order['orders_id'].'</a> is in Customer Service - Logistics';

    $mail = $mailer->create_mail();
	$mail->set_subject("Order #".$next_order['orders_id']." is in Customer Service - Logistics");
	$mail->set_from(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER);
	$mail->set_body($body);
	$mail->add_to("Daniel.Cupsa@cablesandkits.com");
	$mailer->send($mail);
}

//process customer service paint_hold orders first
$orders = prepared_query::fetch('SELECT osh.orders_id FROM orders_status_history osh WHERE osh.date_added > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND osh.orders_status_id = 11 AND osh.orders_sub_status_id = 15', cardinality::SET);
foreach ($orders as $next_order) {
	$body = '<a href="/admin/orders_new.php?oID='.$next_order['orders_id'].'&action=edit">Order # '.$next_order['orders_id'].'</a> is in Customer Service - Paint Hold';
    $mail = $mailer->create_mail();
	$mail->set_subject("Order #".$next_order['orders_id']." is in Customer Service - Paint Hold");
	$mail->set_from(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER);
	$mail->set_body($body);
	$mail->add_to("receiving@cablesandkits.com");
	$mailer->send($mail);
}

//process canceled orders
$orders = prepared_query::fetch('SELECT osh.orders_id FROM orders_status_history osh WHERE osh.date_added > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND osh.orders_status_id = 6', cardinality::SET);
foreach ($orders as $next_order) {
	$mail = $mailer->create_mail();

	$body = '<a href="/admin/orders_new.php?oID='.$next_order['orders_id'].'&action=edit">Order # '.$next_order['orders_id'].'</a> was Canceled';

	$mail->set_subject("Order #".$next_order['orders_id']." was Canceled");
	$mail->set_from(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER);
	$mail->set_body($body);
	//$mail->add_to("purchasing@cablesandkits.com");
	$mail->add_to("inventorydept@cablesandkits.com");
	$mail->add_to("shippingdept@cablesandkits.com");
	$mailer->send($mail);
}

$orders = prepared_query::fetch('SELECT osh.orders_id, c.customers_firstname, c.customers_lastname, ab.entry_company FROM orders_status_history osh LEFT JOIN orders o ON osh.orders_id = o.orders_id LEFT JOIN customers c ON o.customers_id = c.customers_id LEFT JOIN address_book ab ON c.customers_default_address_id = ab.address_book_id WHERE osh.date_added > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND osh.orders_status_id = 11 AND osh.orders_sub_status_id = 8', cardinality::SET);
foreach ($orders as $next_order) {
	$mail = $mailer->create_mail();

	$body = '<a href="/admin/orders_new.php?oID='.$next_order['orders_id'].'&action=edit">Order #'.$next_order['orders_id']."</a>\r\n<br/>
	Customer: ". $next_order['customers_firstname']." ".$next_order['customers_lastname']."\r\n<br/>
	Company: ". $next_order['entry_company']."";

	$mail->set_subject("Order #".$next_order['orders_id']." is in Customer Service - Dealers");
	$mail->set_from(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER);
	$mail->set_body($body);
	$mail->add_to("brokersales@cablesandkits.com");
	$mailer->send($mail);
}

