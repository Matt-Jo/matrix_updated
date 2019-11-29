<?php
/*
 $Id: whos_online.php,v 1.5 2002/03/30 15:48:55 harley_vb Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Who\'s Online');

if (!defined('TABLE_HEADING_ONLINE')) define('TABLE_HEADING_ONLINE', 'Online');
if (!defined('TABLE_HEADING_CUSTOMER_ID')) define('TABLE_HEADING_CUSTOMER_ID', 'ID');
if (!defined('TABLE_HEADING_FULL_NAME')) define('TABLE_HEADING_FULL_NAME', 'Name');
if (!defined('TABLE_HEADING_IP_ADDRESS')) define('TABLE_HEADING_IP_ADDRESS', 'IP Address');
if (!defined('TABLE_HEADING_ENTRY_TIME')) define('TABLE_HEADING_ENTRY_TIME', 'Entry');
if (!defined('TABLE_HEADING_LAST_CLICK')) define('TABLE_HEADING_LAST_CLICK', 'Last Click');
if (!defined('TABLE_HEADING_LAST_PAGE_URL')) define('TABLE_HEADING_LAST_PAGE_URL', 'Last URL');
if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');
if (!defined('TABLE_HEADING_SHOPPING_CART')) define('TABLE_HEADING_SHOPPING_CART', 'Shopping Cart');
if (!defined('TEXT_SHOPPING_CART_SUBTOTAL')) define('TEXT_SHOPPING_CART_SUBTOTAL', 'Subtotal');
if (!defined('TEXT_NUMBER_OF_CUSTOMERS')) define('TEXT_NUMBER_OF_CUSTOMERS', 'Currently there are %s visitors online');
if (!defined('TABLE_HEADING_HTTP_REFERER')) define('TABLE_HEADING_HTTP_REFERER', 'Refer?');
if (!defined('TEXT_HTTP_REFERER_URL')) define('TEXT_HTTP_REFERER_URL', 'HTTP Referer URL');
if (!defined('TEXT_HTTP_REFERER_FOUND')) define('TEXT_HTTP_REFERER_FOUND', 'Y');
if (!defined('TEXT_HTTP_REFERER_NOT_FOUND')) define('TEXT_HTTP_REFERER_NOT_FOUND', 'Not Found');
if (!defined('TEXT_STATUS_ACTIVE_CART')) define('TEXT_STATUS_ACTIVE_CART', 'Active/Cart');
if (!defined('TEXT_STATUS_ACTIVE_NOCART')) define('TEXT_STATUS_ACTIVE_NOCART', 'Active/NoCart');
if (!defined('TEXT_STATUS_INACTIVE_CART')) define('TEXT_STATUS_INACTIVE_CART', 'Inactive/Cart');
if (!defined('TEXT_STATUS_INACTIVE_NOCART')) define('TEXT_STATUS_INACTIVE_NOCART', 'Inactive/NoCart');
if (!defined('TEXT_STATUS_ACTIVE_BOT')) define('TEXT_STATUS_ACTIVE_BOT', 'Active/Bot');
if (!defined('TEXT_STATUS_INACTIVE_BOT')) define('TEXT_STATUS_INACTIVE_BOT', 'Inactive/Bot');
if (!defined('TABLE_HEADING_COUNTRY')) define('TABLE_HEADING_COUNTRY', 'Cntry');
if (!defined('TABLE_HEADING_USER_SESSION')) define('TABLE_HEADING_USER_SESSION', 'Session?');

if (!defined('TEXT_OSCID')) define('TEXT_OSCID', 'osCsid');
if (!defined('TEXT_PROFILE_DISPLAY')) define('TEXT_PROFILE_DISPLAY', 'Profile Display');
if (!defined('TEXT_USER_AGENT')) define('TEXT_USER_AGENT', 'User Agent');
if (!defined('TEXT_ERROR')) define('TEXT_ERROR', 'Error!');
if (!defined('TEXT_ADMIN')) define('TEXT_ADMIN', 'Admin');
if (!defined('TEXT_DUPLICATE_IP')) define('TEXT_DUPLICATE_IP', 'Duplicate IPs');
if (!defined('TEXT_BOTS')) define('TEXT_BOTS', 'Bots');
if (!defined('TEXT_ME')) define('TEXT_ME', 'Me!');
if (!defined('TEXT_ALL')) define('TEXT_ALL', 'All');
if (!defined('TEXT_REAL_CUSTOMERS')) define('TEXT_REAL_CUSTOMERS', 'Real Customers');
if (!defined('TEXT_YOUR_IP_ADDRESS')) define('TEXT_YOUR_IP_ADDRESS', 'Your IP Address');
if (!defined('TEXT_SET_REFRESH_RATE')) define('TEXT_SET_REFRESH_RATE', 'Set Refresh Rate');
if (!defined('TEXT_NONE_')) define('TEXT_NONE_', 'None');
?>