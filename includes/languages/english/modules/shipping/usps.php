<?php
/*
 $Id: usps.php,v 1.8 2003/02/14 12:54:37 dgw_ Exp $
 ++++ modified as USPS Methods 2.7 03/26/04 by Brad Waite and Fritz Clapp ++++

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('MODULE_SHIPPING_USPS_TEXT_TITLE')) define('MODULE_SHIPPING_USPS_TEXT_TITLE', 'United States Postal Service');
if (!defined('MODULE_SHIPPING_USPS_TEXT_DESCRIPTION')) define('MODULE_SHIPPING_USPS_TEXT_DESCRIPTION', 'United States Postal Service<br><br>You will need to have registered an account with USPS. <br><br>USPS expects you to use pounds as weight measure for your products.<br><br>Click <a href="javascript:(void)" onClick="window.open(\'instructions-shipping-usps.php\',\'Instructions\',\'resizable=1,statusbar=5,width=500,height=400,top=0,left=50,scrollbars=yes\')"><strong>HERE</strong></a> for registration details and instructions for proper usage.');
if (!defined('MODULE_SHIPPING_USPS_TEXT_OPT_PP')) define('MODULE_SHIPPING_USPS_TEXT_OPT_PP', 'Parcel Post');
if (!defined('MODULE_SHIPPING_USPS_TEXT_OPT_PM')) define('MODULE_SHIPPING_USPS_TEXT_OPT_PM', 'Priority Mail');
if (!defined('MODULE_SHIPPING_USPS_TEXT_OPT_EX')) define('MODULE_SHIPPING_USPS_TEXT_OPT_EX', 'Express Mail');
if (!defined('MODULE_SHIPPING_USPS_TEXT_OPT_MM')) define('MODULE_SHIPPING_USPS_TEXT_OPT_MM', 'Media Mail');
if (!defined('MODULE_SHIPPING_USPS_TEXT_OPT_LM')) define('MODULE_SHIPPING_USPS_TEXT_OPT_LM', 'Library Mail');
if (!defined('MODULE_SHIPPING_USPS_TEXT_OPT_BM')) define('MODULE_SHIPPING_USPS_TEXT_OPT_BM', 'Bound Printed');
if (!defined('MODULE_SHIPPING_USPS_TEXT_ERROR')) define('MODULE_SHIPPING_USPS_TEXT_ERROR', 'An error occured with the USPS shipping calculations.<br>If you prefer to use USPS as your shipping method, please contact the store owner.');
if (!defined('MODULE_SHIPPING_USPS_TEXT_DAY')) define('MODULE_SHIPPING_USPS_TEXT_DAY', 'Day');
if (!defined('MODULE_SHIPPING_USPS_TEXT_DAYS')) define('MODULE_SHIPPING_USPS_TEXT_DAYS', 'Days');
if (!defined('MODULE_SHIPPING_USPS_TEXT_WEEKS')) define('MODULE_SHIPPING_USPS_TEXT_WEEKS', 'Weeks');
?>