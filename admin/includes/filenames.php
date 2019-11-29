<?php
/*
$Id: filenames.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

osCommerce, Open Source E-Commerce Solutions
http://www.oscommerce.com

Copyright (c) 2003 osCommerce

Released under the GNU General Public License
*/

//Admin begin
if (!defined('FILENAME_ADMIN_ACCOUNT')) define('FILENAME_ADMIN_ACCOUNT', 'admin_account.php');
if (!defined('FILENAME_ADMIN_MEMBERS')) define('FILENAME_ADMIN_MEMBERS', 'admin_members.php');
if (!defined('FILENAME_FORBIDEN')) define('FILENAME_FORBIDEN', 'forbiden.php');
if (!defined('FILENAME_LOGIN')) define('FILENAME_LOGIN', 'login.php');
if (!defined('FILENAME_LOGOFF')) define('FILENAME_LOGOFF', 'logoff.php');
if (!defined('FILENAME_PASSWORD_FORGOTTEN')) define('FILENAME_PASSWORD_FORGOTTEN', 'password_forgotten.php');
//Admin end

// BOF: Lango Added for Order_edit MOD
if (!defined('FILENAME_CREATE_ORDER_PROCESS')) define('FILENAME_CREATE_ORDER_PROCESS', 'create_order_process.php');
if (!defined('FILENAME_CREATE_ORDER')) define('FILENAME_CREATE_ORDER', 'create_order.php');
if (!defined('FILENAME_EDIT_ORDERS')) define('FILENAME_EDIT_ORDERS', 'edit_orders.php');
// EOF: Lango Added for Order_edit MOD

// BOF: Lango Added for Sales Stats MOD
if (!defined('FILENAME_STATS_MONTHLY_SALES')) define('FILENAME_STATS_MONTHLY_SALES', 'stats_monthly_sales.php');
// EOF: Lango Added for Sales Stats MOD

// define the filenames used in the project
if (!defined('FILENAME_BACKUP')) define('FILENAME_BACKUP', 'backup.php');
if (!defined('FILENAME_CATALOG_ACCOUNT_HISTORY_INFO')) define('FILENAME_CATALOG_ACCOUNT_HISTORY_INFO', 'account_history_info.php');
if (!defined('FILENAME_CATEGORIES')) define('FILENAME_CATEGORIES', 'categories.php');
if (!defined('FILENAME_CONFIGURATION')) define('FILENAME_CONFIGURATION', 'configuration.php');
if (!defined('FILENAME_VENDORS')) define('FILENAME_VENDORS', 'vendors.php');
if (!defined('FILENAME_DEFINE_LANGUAGE')) define('FILENAME_DEFINE_LANGUAGE', 'define_language.php');
if (!defined('FILENAME_FILE_MANAGER')) define('FILENAME_FILE_MANAGER', 'file_manager.php');
if (!defined('FILENAME_MAIL')) define('FILENAME_MAIL', 'mail.php');
if (!defined('FILENAME_MANUFACTURERS')) define('FILENAME_MANUFACTURERS', 'manufacturers.php');
if (!defined('FILENAME_MODULES')) define('FILENAME_MODULES', 'modules.php');
if (!defined('FILENAME_ORDERS')) define('FILENAME_ORDERS', 'orders_new.php');
if (!defined('FILENAME_PRODUCTS_ATTRIBUTES')) define('FILENAME_PRODUCTS_ATTRIBUTES', 'products_attributes.php');
if (!defined('FILENAME_SPECIALS')) define('FILENAME_SPECIALS', 'specials.php');
if (!defined('FILENAME_WHOS_ONLINE')) define('FILENAME_WHOS_ONLINE', 'whos_online.php');

/*Tracking contribution begin*/
if (!defined('FILENAME_CATALOG_TRACKING_NUMBER')) define('FILENAME_CATALOG_TRACKING_NUMBER', 'tracking.php');
/*Tracking contribution end*/

if (!defined('FILENAME_EASYPOPULATE')) define('FILENAME_EASYPOPULATE', 'easypopulate.php');
if (!defined('FILENAME_EASYPOPULATE_BASIC')) define('FILENAME_EASYPOPULATE_BASIC', 'easypopulate_basic.php');
if (!defined('FILENAME_EDIT_ORDERS')) define('FILENAME_EDIT_ORDERS', 'edit_orders.php');

//DWD Modify: Information Page Unlimited 1.1f - PT
if (!defined('FILENAME_INFORMATION_MANAGER')) define('FILENAME_INFORMATION_MANAGER', 'information_manager.php');
//DWD Modify End

// product notifications
if (!defined('FILENAME_PRODUCT_NOTIFICATION')) define('FILENAME_PRODUCT_NOTIFICATION','product_notifications.php');

//added for Backup mySQL (provided Courtesy Zen-Cart Team) DMG
if (!defined('FILENAME_BACKUP_MYSQL')) define('FILENAME_BACKUP_MYSQL','backup_mysql.php');

//added for Recover Carts
if (!defined('FILENAME_CATALOG_LOGIN')) define('FILENAME_CATALOG_LOGIN', 'login.php');
if (!defined('FILENAME_CATALOG_PRODUCT_INFO')) define('FILENAME_CATALOG_PRODUCT_INFO', 'product_info.php');

if (!defined('FILENAME_STOCKS')) define('FILENAME_STOCKS','internal_parts.php');

if (!defined('FILENAME_ORDERS_EDIT')) define('FILENAME_ORDERS_EDIT', 'edit_orders.php');

if (!defined('FILENAME_VISITORS')) define('FILENAME_VISITORS', 'visitors.php');

if (!defined('FILENAME_POPUP_HELP')) define('FILENAME_POPUP_HELP', 'popup_help.php');
if (!defined('FILENAME_MAILS')) define('FILENAME_MAILS', 'mails.php');

if (!defined('FILENAME_CHANGE_PASSWORD')) define('FILENAME_CHANGE_PASSWORD', 'change_password.php');

if (!defined('FILENAME_OUTSTANDING_INVOICES')) define('FILENAME_OUTSTANDING_INVOICES', 'outstanding_invoices.php');

if (!defined('FILENAME_IPN_EDITOR')) define('FILENAME_IPN_EDITOR', 'ipn_editor.php');
if (!defined('FILENAME_WEIGHT_UPDATE')) define('FILENAME_WEIGHT_UPDATE', 'ipn_weight_update.php');

if (!defined('FILENAME_CREATE_VENDOR_ACCOUNT')) define('FILENAME_CREATE_VENDOR_ACCOUNT', 'create_vendor_account.php');
if (!defined('FILENAME_CREATE_VENDOR_ACCOUNT_PROCESS')) define('FILENAME_CREATE_VENDOR_ACCOUNT_PROCESS', 'create_vendor_account_process.php');
if (!defined('FILENAME_CREATE_VENDOR_ACCOUNT_SUCCESS')) define('FILENAME_CREATE_VENDOR_ACCOUNT_SUCCESS', 'create_vendor_account_success.php');

if (!defined('FILENAME_ADD_PO')) define('FILENAME_ADD_PO', 'add_po.php');
if (!defined('FILENAME_VIEW_PO')) define('FILENAME_VIEW_PO', 'view_po.php');
?>
