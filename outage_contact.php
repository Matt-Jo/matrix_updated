<?php
date_default_timezone_set('America/New_York');

ini_set("display_errors", 0);

// set the include path for ZF
set_include_path(realpath('library').PATH_SEPARATOR.get_include_path());

// Set the local configuration parameters - mainly for developers
if (file_exists('includes/local/configure.php')) {
	require_once('includes/local/configure.php');
}

require_once('includes/configure.php');

if (!defined('DIR_WS_CATALOG')) define('DIR_WS_CATALOG', DIR_WS_HTTPS_CATALOG);

// used for money_format
setlocale(LC_MONETARY, 'en_US.utf8');

$fname = $_POST['fname'];
$lname = $_POST['lname'];
$email_address = $_POST['email'];
$enquiry = $_POST['enquiry'];
$order_no = $_POST['order_no'];
$phone = $_POST['phone'];
$category = $_POST['selection'];

$body = "Name: $fname $lname\r\nEmail: $email_address\r\nPhone: $phone\r\nCategory: $category\r\nOrder Id: $order_no\r\nComment: $enquiry\r\n";

$mailer = service_locator::get_mail_service();
$mail = $mailer->create_mail();
$mail->set_body(null,$body);
$mail->set_from($email_address, $name);
$mail->add_to('sales@cablesandkits.com', 'CablesAndKits.com');
$mail->set_subject('Inquiry From CablesAndKits.com');
$mailer->send($mail);

header('Location: /');
exit();
?>

