<?php
/*
 $Id: index.php,v 1.2 2004/03/05 00:36:42 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

if ( ($category_depth == 'products') || (isset($_GET['manufacturers_id'])) ) {
 if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Let\'s See What We Have Here');

 if (!defined('TEXT_NUMBER_OF_PRODUCTS')) define('TEXT_NUMBER_OF_PRODUCTS', 'Number of Products: ');
 if (!defined('TEXT_SHOW')) define('TEXT_SHOW', '<b>Show:</b>');
 if (!defined('TEXT_BUY')) define('TEXT_BUY', 'Buy 1 \'');
 if (!defined('TEXT_NOW')) define('TEXT_NOW', '\' now');
} elseif ($category_depth == 'top') {
 if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'What\'s New Here?');
} elseif ($category_depth == 'nested') {
 if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Categories');

 //MMD - added for category changes
 if (!defined('TEXT_NUMBER_OF_PRODUCTS')) define('TEXT_NUMBER_OF_PRODUCTS', 'Number of Products: ');
 if (!defined('TEXT_SHOW')) define('TEXT_SHOW', '<b>Show:</b>');
 if (!defined('TEXT_BUY')) define('TEXT_BUY', 'Buy 1 \'');
 if (!defined('TEXT_NOW')) define('TEXT_NOW', '\' now');
}
 if (!defined('HEADING_CUSTOMER_GREETING')) define('HEADING_CUSTOMER_GREETING', 'Our Customer Greeting');
 if (!defined('MAINPAGE_HEADING_TITLE')) define('MAINPAGE_HEADING_TITLE', 'Main Page Heading Title');
?>
