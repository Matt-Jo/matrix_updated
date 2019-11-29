<?php
/*
 $Id: specials.php,v 1.2 2004/03/05 00:36:42 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Specials');

if (!defined('TABLE_HEADING_STATUS')) define('TABLE_HEADING_STATUS', 'Status');
if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');

if (!defined('TEXT_SPECIALS_PRODUCT')) define('TEXT_SPECIALS_PRODUCT', 'Product:');
if (!defined('TEXT_SPECIALS_SPECIAL_PRICE')) define('TEXT_SPECIALS_SPECIAL_PRICE', 'Special Price:');
if (!defined('TEXT_SPECIALS_EXPIRES_DATE')) define('TEXT_SPECIALS_EXPIRES_DATE', 'Expiry Date:');
if (!defined('TEXT_SPECIALS_PRICE_TIP')) define('TEXT_SPECIALS_PRICE_TIP', '<b>Specials Notes:</b><ul><li>You can enter a percentage to deduct in the Specials Price field, for example: <b>20%</b></li><li>If you enter a new price, the decimal separator must be a \'.\' (decimal-point), example: <b>49.99</b></li><li>Leave the expiry date empty for no expiration</li><li>IMPORTANT NOTE: Setting the special quantity to 999999 will cause the catalog to ignore the special quantity and leave it on regardless of the stock quantity.</li></ul>');

if (!defined('TEXT_INFO_DATE_ADDED')) define('TEXT_INFO_DATE_ADDED', 'Date Added:');
if (!defined('TEXT_INFO_LAST_MODIFIED')) define('TEXT_INFO_LAST_MODIFIED', 'Last Modified:');
if (!defined('TEXT_INFO_NEW_PRICE')) define('TEXT_INFO_NEW_PRICE', 'New Price:');
if (!defined('TEXT_INFO_ORIGINAL_PRICE')) define('TEXT_INFO_ORIGINAL_PRICE', 'Original Price:');
if (!defined('TEXT_INFO_PERCENTAGE')) define('TEXT_INFO_PERCENTAGE', 'Percentage:');
if (!defined('TEXT_INFO_EXPIRES_DATE')) define('TEXT_INFO_EXPIRES_DATE', 'Expires At:');
if (!defined('TEXT_INFO_STATUS_CHANGE')) define('TEXT_INFO_STATUS_CHANGE', 'Status Change:');

if (!defined('TEXT_INFO_HEADING_DELETE_SPECIALS')) define('TEXT_INFO_HEADING_DELETE_SPECIALS', 'Delete Special');
if (!defined('TEXT_INFO_DELETE_INTRO')) define('TEXT_INFO_DELETE_INTRO', 'Are you sure you want to delete the special products price?');
?>
