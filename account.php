<?php
 require('includes/application_top.php');

 if (empty($_SESSION['customer_id'])) {
	$_SESSION['navigation']->set_snapshot();
	CK\fn::redirect_and_exit('/login.php');
 }

// if (isset($_SESSION['admin_as_user']) && $_SESSION['admin_as_user']) CK\fn::redirect_and_exit('/my-account');

 require(DIR_WS_LANGUAGES.$_SESSION['language'].'/account.php');

 $breadcrumb->add('Customer Service', '/custserv.php');
 $breadcrumb->add('My Account', '/account.php');

 $content = 'account';
 $javascript = $content.'.js';

 require('templates/Pixame_v1/main_page.tpl.php');
?>
