<?php
 require('includes/application_top.php');

 if (empty($_SESSION['customer_id'])) {
	$_SESSION['navigation']->set_snapshot();
	CK\fn::redirect_and_exit('/login.php');
 }

 require(DIR_WS_LANGUAGES.$_SESSION['language'].'/account_history.php');

 $breadcrumb->add('Customer Service', '/custserv.php');
 $breadcrumb->add('My Account', '/account.php');
 $breadcrumb->add('History', '/account_history.php');

 $content = 'account_history';
 $javascript = 'popup_window.js';
 require('templates/Pixame_v1/main_page.tpl.php');
?>
