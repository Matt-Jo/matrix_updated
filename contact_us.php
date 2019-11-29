<?php
require('includes/application_top.php');
require(DIR_WS_LANGUAGES.$_SESSION['language'].'/contact_us.php');

//initialize the error array
$error_messages = [];
$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : NULL;
if ($action == 'send') {
	// Define and normalize the form input variables
	$fname = normalize_input($_POST['fname']);
	$lname = normalize_input($_POST['lname']);
	$email = normalize_input($_POST['email']);
	$enquiry = normalize_input($_POST['enquiry']);
	$order_no = normalize_input($_POST['order_no']);
	$phone = normalize_input($_POST['phone']);
	$selection = normalize_input($_POST['selection']);

	if (empty($fname)) {
		$error_messages['fname'] = 'First name is required.';
	}
	if (empty($lname)) {
		$error_messages['lname'] = 'Last name is required.';
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL) | (empty($email))) {
		$error_messages['email'] = 'Enter a valid email address.';
	}
	if (empty($phone)) {
		$error_messages['phone'] = 'Valid 10-digit phone number required.';
	}
	if (empty($enquiry)) {
		$error_messages['enquiry'] = 'Please supply a comment or question.';
	}
	if (empty($error_messages)) {
		$body = 'Name: '.$fname.' '.$lname."\r\n".'Email: '.$email."\r\n".'Phone: '.$phone."\r\n".'Category: '.$selection."\r\n".'Order ID: '.$order_no."\r\n".'Comment: '.$enquiry."\r\n";
		$mailer = service_locator::get_mail_service();
        $mail = $mailer->create_mail();
		$mail->set_body(null,$body);
		$mail->set_from($email, $fname . ' ' . $lname);
		$mail->add_to($_SESSION['cart']->get_contact_email(), 'CablesAndKits.com Sales Team');
		$mail->set_subject(EMAIL_SUBJECT);
        
        try{
            $mailer->send($mail);
            CK\fn::redirect_and_exit('/contact_us.php?action=success');
        } catch( mail_service_exception $e ) {
            CK\fn::redirect_and_exit('/contact_us.php?action=failed');
        }
	}
}

$breadcrumb->add('Contact Us', '/contact_us.php');
$content = 'contact_us';

require ('templates/Pixame_v1/main_page.tpl.php');

function normalize_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}
?>