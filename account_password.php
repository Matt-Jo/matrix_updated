<?php
require('includes/application_top.php');

if (empty($_SESSION['customer_id'])) {
	$_SESSION['navigation']->set_snapshot();
	CK\fn::redirect_and_exit('/login.php');
}

// needs to be included earlier to set the success message in the messageStack
require(DIR_WS_LANGUAGES.$_SESSION['language'].'/account_password.php');

if (isset($_POST['action']) && ($_POST['action'] == 'process')) {
	$password_current = $_POST['password_current'];
	$password_new = $_POST['password_new'];
	$password_confirmation = $_POST['password_confirmation'];

	$error = FALSE;

	if (strlen($password_new) < ck_customer2::$validation['password']['min_length']) {
		$error = TRUE;
		$messageStack->add('account_password', 'Your New Password must contain a minimum of '.ck_customer2::$validation['password']['min_length'].' characters.');
	} 
	elseif ($password_new != $password_confirmation) {
		$error = TRUE;
		$messageStack->add('account_password', 'The Password Confirmation must match your New Password.');
	}

	$customer = $_SESSION['cart']->get_customer();

	if (!$customer->revalidate_login($password_current)) {
		$error = TRUE;
		$messageStack->add('account_password', 'Your Current Password did not match the password in our records. Please try again.');
	}

	if (empty($error)) {
		$customer->update_password($password_new, $_SESSION['customer_extra_login_id']);

		$messageStack->add_session('account', 'Your password has been successfully updated.', 'success');
		CK\fn::redirect_and_exit('/account.php');
	}
}

$breadcrumb->add('Customer Service', '/custserv.php');
$breadcrumb->add('My Account', '/account.php');
$breadcrumb->add('Change Password', '/account_password.php');

$content = 'account_password';
$javascript = 'form_check.js.php';

require('templates/Pixame_v1/main_page.tpl.php');
?>