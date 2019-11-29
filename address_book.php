<?php
/*
 $Id: address_book.php,v 1.1.1.1 2004/03/04 23:37:53 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/
require('includes/application_top.php');

if (empty($_SESSION['customer_id'])) {
	$_SESSION['navigation']->set_snapshot();
	CK\fn::redirect_and_exit('/login.php');
}

require(DIR_WS_LANGUAGES.$_SESSION['language'].'/address_book.php');

$breadcrumb->add('Customer Service', '/custserv.php');
$breadcrumb->add('My Account', '/account.php');
$breadcrumb->add('Address Book', '/address_book.php');

$content = 'address_book';
$javascript = $content.'.js';

require('templates/Pixame_v1/main_page.tpl.php');
?>