<?php
/*
 $Id: english.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

//Admin begin

// images
if (!defined('IMAGE_FILE_PERMISSION')) define('IMAGE_FILE_PERMISSION', 'File Permission');
if (!defined('IMAGE_GROUPS')) define('IMAGE_GROUPS', 'Groups List');
if (!defined('IMAGE_INSERT_FILE')) define('IMAGE_INSERT_FILE', 'Insert File');
if (!defined('IMAGE_MEMBERS')) define('IMAGE_MEMBERS', 'Members List');
if (!defined('IMAGE_NEW_GROUP')) define('IMAGE_NEW_GROUP', 'New Group');
if (!defined('IMAGE_NEW_MEMBER')) define('IMAGE_NEW_MEMBER', 'New Member');
if (!defined('IMAGE_NEXT')) define('IMAGE_NEXT', 'Next');

// constants for use in tep_prev_next_display function
if (!defined('TEXT_DISPLAY_NUMBER_OF_MEMBERS')) define('TEXT_DISPLAY_NUMBER_OF_MEMBERS', 'Displaying <b>%d</b> to <b>%d</b> (of <b>%d</b> members)');
//Admin end

// look in your $PATH_LOCALE/locale directory for available locales..
// on RedHat6.0 I used 'en_US'
// on FreeBSD 4.0 I use 'en_US.ISO_8859-1'
// this may not work under win32 environments..
setlocale(LC_TIME, 'en_US.UTF-8');

// charset for web pages and emails
if (!defined('CHARSET')) define('CHARSET', 'utf-8');

// page title
if (!defined('TITLE')) define('TITLE', 'Matrix');

// header text in includes/header.php
if (!defined('HEADER_TITLE_SUPPORT_SITE')) define('HEADER_TITLE_SUPPORT_SITE', 'Support Site');
if (!defined('HEADER_TITLE_ONLINE_CATALOG')) define('HEADER_TITLE_ONLINE_CATALOG', 'Catalog');
if (!defined('HEADER_TITLE_ADMINISTRATION')) define('HEADER_TITLE_ADMINISTRATION', 'Admin');
if (!defined('HEADER_TITLE_CHAINREACTION')) define('HEADER_TITLE_CHAINREACTION', 'Chainreactionweb');
if (!defined('HEADER_TITLE_CRELOADED')) define('HEADER_TITLE_CRELOADED', 'CRE Loaded Project');
// MaxiDVD Added Line For WYSIWYG HTML Area: BOF
if (!defined('BOX_CATALOG_DEFINE_MAINPAGE')) define('BOX_CATALOG_DEFINE_MAINPAGE', 'Define MainPage');
// MaxiDVD Added Line For WYSIWYG HTML Area: EOF

// configuration box text in includes/boxes/configuration.php
if (!defined('BOX_HEADING_CONFIGURATION')) define('BOX_HEADING_CONFIGURATION', 'Configuration');
if (!defined('BOX_CONFIGURATION_MYSTORE')) define('BOX_CONFIGURATION_MYSTORE', 'My Store');
if (!defined('BOX_CONFIGURATION_LOGGING')) define('BOX_CONFIGURATION_LOGGING', 'Logging');
if (!defined('BOX_CONFIGURATION_CACHE')) define('BOX_CONFIGURATION_CACHE', 'Cache');

// added for super-friendly admin menu:
if (!defined('BOX_CONFIGURATION_MIN_VALUES')) define('BOX_CONFIGURATION_MIN_VALUES', 'Min Values');
if (!defined('BOX_CONFIGURATION_MAX_VALUES')) define('BOX_CONFIGURATION_MAX_VALUES', 'Max Values');
if (!defined('BOX_CONFIGURATION_IMAGES')) define('BOX_CONFIGURATION_IMAGES', 'Image Configuration');
if (!defined('BOX_CONFIGURATION_CUSTOMER_DETAILS')) define('BOX_CONFIGURATION_CUSTOMER_DETAILS', 'Customer Details');
if (!defined('BOX_CONFIGURATION_SHIPPING')) define('BOX_CONFIGURATION_SHIPPING', 'Default Shipping Settings');
if (!defined('BOX_CONFIGURATION_PRODUCT_LISTING')) define('BOX_CONFIGURATION_PRODUCT_LISTING', 'Product Listing');
if (!defined('BOX_CONFIGURATION_EMAIL')) define('BOX_CONFIGURATION_EMAIL', 'Email');
if (!defined('BOX_CONFIGURATION_DOWNLOAD')) define('BOX_CONFIGURATION_DOWNLOAD', 'Download Manager');
if (!defined('BOX_CONFIGURATION_GZIP')) define('BOX_CONFIGURATION_GZIP', 'GZip');
if (!defined('BOX_CONFIGURATION_SESSIONS')) define('BOX_CONFIGURATION_SESSIONS', 'Sessions');
if (!defined('BOX_CONFIGURATION_STOCK')) define('BOX_CONFIGURATION_STOCK', 'Stock Control');
if (!defined('BOX_CONFIGURATION_WYSIWYG')) define('BOX_CONFIGURATION_WYSIWYG', 'WYSIWYG Editor 1.7');
if (!defined('BOX_CONFIGURATION_MAINT')) define('BOX_CONFIGURATION_MAINT', 'Site Maintenance');
if (!defined('BOX_CONFIGURATION_ACCOUNTS')) define('BOX_CONFIGURATION_ACCOUNTS', 'Purchase Without Account');

// modules box text in includes/boxes/modules.php
if (!defined('BOX_HEADING_MODULES')) define('BOX_HEADING_MODULES', 'Modules');
if (!defined('BOX_MODULES_PAYMENT')) define('BOX_MODULES_PAYMENT', 'Payment');
if (!defined('BOX_MODULES_SHIPPING')) define('BOX_MODULES_SHIPPING', 'Shipping');
if (!defined('BOX_MODULES_ORDER_TOTAL')) define('BOX_MODULES_ORDER_TOTAL', 'Order Total');

// Google XML SiteMaps Admin
if (!defined('BOX_CATALOG_GOOGLESITEMAP')) define('BOX_CATALOG_GOOGLESITEMAP', 'Google XML Sitemap');

// categories box text in includes/boxes/catalog.php
if (!defined('BOX_HEADING_CATALOG')) define('BOX_HEADING_CATALOG', 'Catalog');
if (!defined('BOX_CATALOG_CATEGORIES_PRODUCTS')) define('BOX_CATALOG_CATEGORIES_PRODUCTS', 'Categories/Products');
if (!defined('BOX_CATALOG_CATEGORIES_PRODUCTS_ATTRIBUTES')) define('BOX_CATALOG_CATEGORIES_PRODUCTS_ATTRIBUTES', 'Products Attributes');
if (!defined('BOX_CATALOG_MANUFACTURERS')) define('BOX_CATALOG_MANUFACTURERS', 'Manufacturers');
if (!defined('BOX_CATALOG_REVIEWS')) define('BOX_CATALOG_REVIEWS', 'Reviews');
if (!defined('BOX_CATALOG_SPECIALS')) define('BOX_CATALOG_SPECIALS', 'Specials');
if (!defined('BOX_CATALOG_PRODUCTS_EXPECTED')) define('BOX_CATALOG_PRODUCTS_EXPECTED', 'Products Expected');
if (!defined('BOX_CATALOG_EASYPOPULATE')) define('BOX_CATALOG_EASYPOPULATE', 'Easy Populate');
if (!defined('BOX_CATALOG_EASYPOPULATE_BASIC')) define('BOX_CATALOG_EASYPOPULATE_BASIC', 'Easy Populate Basic');

if (!defined('BOX_CATALOG_SALEMAKER')) define('BOX_CATALOG_SALEMAKER', 'SaleMaker');

if (!defined('BOX_CATALOG_SHOP_BY_PRICE')) define('BOX_CATALOG_SHOP_BY_PRICE', 'Shop by Price');
//added for Super-Friendly Admin Menu:
if (!defined('BOX_CUSTOMERS_ORDERS')) define('BOX_CUSTOMERS_ORDERS', 'Orders');
if (!defined('BOX_CUSTOMERS_EDIT_ORDERS')) define('BOX_CUSTOMERS_EDIT_ORDERS', 'Edit Orders');
//begin PayPal_Shopping_Cart_IPN
if (!defined('BOX_CUSTOMERS_PAYPAL')) define('BOX_CUSTOMERS_PAYPAL', 'PayPal IPN');
//end PayPal_Shopping_Cart_IPN
if (!defined('BOX_CREATE_ACCOUNT')) define('BOX_CREATE_ACCOUNT', 'Create New Account');
if (!defined('BOX_CREATE_ORDER')) define('BOX_CREATE_ORDER', 'Create New Order');
if (!defined('BOX_CREATE_ORDERS_ADMIN')) define('BOX_CREATE_ORDERS_ADMIN', 'Create Orders Admin');
// taxes box text in includes/boxes/taxes.php
if (!defined('BOX_HEADING_LOCATION_AND_TAXES')) define('BOX_HEADING_LOCATION_AND_TAXES', 'Locations/Taxes');
if (!defined('BOX_TAXES_COUNTRIES')) define('BOX_TAXES_COUNTRIES', 'Countries');
if (!defined('BOX_TAXES_ZONES')) define('BOX_TAXES_ZONES', 'Zones');
if (!defined('BOX_TAXES_GEO_ZONES')) define('BOX_TAXES_GEO_ZONES', 'Tax Zones');
if (!defined('BOX_TAXES_TAX_CLASSES')) define('BOX_TAXES_TAX_CLASSES', 'Tax Classes');
if (!defined('BOX_TAXES_TAX_RATES')) define('BOX_TAXES_TAX_RATES', 'Tax Rates');

// reports box text in includes/boxes/reports.php
if (!defined('BOX_HEADING_REPORTS')) define('BOX_HEADING_REPORTS', 'Reports');
if (!defined('BOX_REPORTS_PRODUCTS_VIEWED')) define('BOX_REPORTS_PRODUCTS_VIEWED', 'Products Viewed');
if (!defined('BOX_REPORTS_PRODUCTS_PURCHASED')) define('BOX_REPORTS_PRODUCTS_PURCHASED', 'Products Purchased');
if (!defined('BOX_REPORTS_ORDERS_TOTAL')) define('BOX_REPORTS_ORDERS_TOTAL', 'Customer Orders-Total');

// added for super-friendly admin menu:
if (!defined('BOX_REPORTS_MONTHLY_SALES')) define('BOX_REPORTS_MONTHLY_SALES', 'Monthly Sales/Tax');
// tools text in includes/boxes/tools.php
if (!defined('BOX_TOOLS_KEYWORDS')) define('BOX_TOOLS_KEYWORDS', 'Keyword Manager');
if (!defined('BOX_HEADING_TOOLS')) define('BOX_HEADING_TOOLS', 'Tools');
if (!defined('BOX_TOOLS_BACKUP')) define('BOX_TOOLS_BACKUP', 'Database Backup');
if (!defined('BOX_TOOLS_BANNER_MANAGER')) define('BOX_TOOLS_BANNER_MANAGER', 'Banner Manager');
if (!defined('BOX_TOOLS_CACHE')) define('BOX_TOOLS_CACHE', 'Cache Control');
if (!defined('BOX_TOOLS_DEFINE_LANGUAGE')) define('BOX_TOOLS_DEFINE_LANGUAGE', 'Define Languages');
if (!defined('BOX_TOOLS_FILE_MANAGER')) define('BOX_TOOLS_FILE_MANAGER', 'File Manager');
if (!defined('BOX_TOOLS_MAIL')) define('BOX_TOOLS_MAIL', 'Send Email');
if (!defined('BOX_TOOLS_NEWSLETTER_MANAGER')) define('BOX_TOOLS_NEWSLETTER_MANAGER', 'Newsletter Manager');
if (!defined('BOX_TOOLS_SERVER_INFO')) define('BOX_TOOLS_SERVER_INFO', 'Server Info');
if (!defined('BOX_TOOLS_WHOS_ONLINE')) define('BOX_TOOLS_WHOS_ONLINE', 'Who\'s Online');

// localizaion box text in includes/boxes/localization.php
if (!defined('BOX_HEADING_LOCALIZATION')) define('BOX_HEADING_LOCALIZATION', 'Localization');
if (!defined('BOX_LOCALIZATION_CURRENCIES')) define('BOX_LOCALIZATION_CURRENCIES', 'Currencies');
if (!defined('BOX_LOCALIZATION_LANGUAGES')) define('BOX_LOCALIZATION_LANGUAGES', 'Languages');
if (!defined('BOX_LOCALIZATION_ORDERS_STATUS')) define('BOX_LOCALIZATION_ORDERS_STATUS', 'Orders Status');

// infobox box text in includes/boxes/info_boxes.php
if (!defined('BOX_HEADING_BOXES')) define('BOX_HEADING_BOXES', 'Infobox Admin');
if (!defined('BOX_HEADING_TEMPLATE_CONFIGURATION')) define('BOX_HEADING_TEMPLATE_CONFIGURATION', 'Template Admin');
if (!defined('BOX_HEADING_DESIGN_CONTROLS')) define('BOX_HEADING_DESIGN_CONTROLS', 'Design Controls');

// javascript messages
if (!defined('JS_ERROR')) define('JS_ERROR', 'Errors have occured during the process of your form!\nPlease make the following corrections:\n\n');

if (!defined('JS_OPTIONS_VALUE_PRICE')) define('JS_OPTIONS_VALUE_PRICE', '* The new product atribute needs a price value\n');
if (!defined('JS_OPTIONS_VALUE_PRICE_PREFIX')) define('JS_OPTIONS_VALUE_PRICE_PREFIX', '* The new product atribute needs a price prefix\n');

if (!defined('JS_PRODUCTS_NAME')) define('JS_PRODUCTS_NAME', '* The new product needs a name\n');
if (!defined('JS_PRODUCTS_DESCRIPTION')) define('JS_PRODUCTS_DESCRIPTION', '* The new product needs a description\n');
if (!defined('JS_PRODUCTS_PRICE')) define('JS_PRODUCTS_PRICE', '* The new product needs a price value\n');
if (!defined('JS_PRODUCTS_WEIGHT')) define('JS_PRODUCTS_WEIGHT', '* The new product needs a weight value\n');
if (!defined('JS_PRODUCTS_QUANTITY')) define('JS_PRODUCTS_QUANTITY', '* The new product needs a quantity value\n');
if (!defined('JS_PRODUCTS_MODEL')) define('JS_PRODUCTS_MODEL', '* The new product needs a model value\n');
if (!defined('JS_PRODUCTS_IMAGE')) define('JS_PRODUCTS_IMAGE', '* The new product needs an image value\n');

if (!defined('JS_SPECIALS_PRODUCTS_PRICE')) define('JS_SPECIALS_PRODUCTS_PRICE', '* A new price for this product needs to be set\n');

if (!defined('JS_GENDER')) define('JS_GENDER', '* The \'Gender\' value must be chosen.\n');
if (!defined('JS_FIRST_NAME')) define('JS_FIRST_NAME', '* The \'First Name\' entry must have at least '.ENTRY_FIRST_NAME_MIN_LENGTH.' characters.\n');
if (!defined('JS_LAST_NAME')) define('JS_LAST_NAME', '* The \'Last Name\' entry must have at least '.ENTRY_LAST_NAME_MIN_LENGTH.' characters.\n');
if (!defined('JS_DOB')) define('JS_DOB', '* The \'Date of Birth\' entry must be in the format: xx/xx/xxxx (month/date/year).\n');
if (!defined('JS_EMAIL_ADDRESS')) define('JS_EMAIL_ADDRESS', '* The \'E-Mail Address\' entry must have at least '.ENTRY_EMAIL_ADDRESS_MIN_LENGTH.' characters.\n');
if (!defined('JS_ADDRESS')) define('JS_ADDRESS', '* The \'Street Address\' entry must have at least '.ENTRY_STREET_ADDRESS_MIN_LENGTH.' characters.\n');
if (!defined('JS_POST_CODE')) define('JS_POST_CODE', '* The \'Post Code\' entry must have at least '.ENTRY_POSTCODE_MIN_LENGTH.' characters.\n');
if (!defined('JS_CITY')) define('JS_CITY', '* The \'City\' entry must have at least '.ENTRY_CITY_MIN_LENGTH.' characters.\n');
if (!defined('JS_STATE')) define('JS_STATE', '* The \'State\' entry is must be selected.\n');
if (!defined('JS_STATE_SELECT')) define('JS_STATE_SELECT', '-- Select Above --');
if (!defined('JS_ZONE')) define('JS_ZONE', '* The \'State\' entry must be selected from the list for this country.');
if (!defined('JS_COUNTRY')) define('JS_COUNTRY', '* The \'Country\' value must be chosen.\n');
if (!defined('JS_TELEPHONE')) define('JS_TELEPHONE', '* The \'Telephone Number\' entry must have at least '.ENTRY_TELEPHONE_MIN_LENGTH.' characters.\n');
if (!defined('JS_PASSWORD')) define('JS_PASSWORD', '* The \'Password\' amd \'Confirmation\' entries must match amd have at least '.ENTRY_PASSWORD_MIN_LENGTH.' characters.\n');

if (!defined('JS_ORDER_DOES_NOT_EXIST')) define('JS_ORDER_DOES_NOT_EXIST', 'Order Number %s does not exist!');
/* User Friendly Admin Menu */
if (!defined('CATALOG_CATEGORIES')) define('CATALOG_CATEGORIES', 'Categories');
if (!defined('CATALOG_ATTRIBUTES')) define('CATALOG_ATTRIBUTES', 'Product Attributes');
if (!defined('CATALOG_REVIEWS')) define('CATALOG_REVIEWS', 'Product Reveiws');
if (!defined('CATALOG_SPECIALS')) define('CATALOG_SPECIALS', 'Specials');
if (!defined('CATALOG_EXPECTED')) define('CATALOG_EXPECTED', 'Products Expected');
if (!defined('REPORTS_PRODUCTS_VIEWED')) define('REPORTS_PRODUCTS_VIEWED', 'Veiwed Products');
if (!defined('REPORTS_PRODUCTS_PURCHASED')) define('REPORTS_PRODUCTS_PURCHASED', 'Products Purchased');
if (!defined('TOOLS_FILE_MANAGER')) define('TOOLS_FILE_MANAGER', 'File Manager');
if (!defined('TOOLS_CACHE')) define('TOOLS_CACHE', 'Cache Control');
if (!defined('TOOLS_DEFINE_LANGUAGES')) define('TOOLS_DEFINE_LANGUAGES', 'Define Languages');
if (!defined('TOOLS_EMAIL')) define('TOOLS_EMAIL', 'Email Customers');
if (!defined('TOOLS_NEWSLETTER')) define('TOOLS_NEWSLETTER', 'Newsletters');
if (!defined('TOOLS_SERVER_INFO')) define('TOOLS_SERVER_INFO', 'Server Info');
if (!defined('TOOLS_WHOS_ONLINE')) define('TOOLS_WHOS_ONLINE', 'Who\'s Online');
if (!defined('BOX_HEADING_GV')) define('BOX_HEADING_GV', 'Coupon/Voucher');
if (!defined('GV_COUPON_ADMIN')) define('GV_COUPON_ADMIN', 'Discount Coupons');
if (!defined('GV_EMAIL')) define('GV_EMAIL', 'Send Gift Voucher');
if (!defined('GV_QUEUE')) define('GV_QUEUE', 'Gift Voucher Redeem');
if (!defined('GV_SENT')) define('GV_SENT', 'Gift Voucher\'s Sent');
/* User Friedly Admin Menu */

if (!defined('ENTRY_FIRST_NAME')) define('ENTRY_FIRST_NAME', 'First Name:');
if (!defined('ENTRY_FIRST_NAME_ERROR')) define('ENTRY_FIRST_NAME_ERROR', '&nbsp;<span class="errorText">min '.ENTRY_FIRST_NAME_MIN_LENGTH.' chars</span>');
if (!defined('ENTRY_EMAIL_ADDRESS')) define('ENTRY_EMAIL_ADDRESS', 'E-Mail Address:');
if (!defined('ENTRY_EMAIL_ADDRESS_ERROR')) define('ENTRY_EMAIL_ADDRESS_ERROR', '&nbsp;<span class="errorText">min '.ENTRY_EMAIL_ADDRESS_MIN_LENGTH.' chars</span>');
if (!defined('ENTRY_EMAIL_ADDRESS_CHECK_ERROR')) define('ENTRY_EMAIL_ADDRESS_CHECK_ERROR', '&nbsp;<span class="errorText">The email address doesn\'t appear to be valid!</span>');
if (!defined('ENTRY_EMAIL_ADDRESS_ERROR_EXISTS')) define('ENTRY_EMAIL_ADDRESS_ERROR_EXISTS', '&nbsp;<span class="errorText">This email address already exists!</span>');
if (!defined('ENTRY_STREET_ADDRESS')) define('ENTRY_STREET_ADDRESS', 'Street Address:');
if (!defined('ENTRY_STREET_ADDRESS_ERROR')) define('ENTRY_STREET_ADDRESS_ERROR', '&nbsp;<span class="errorText">min '.ENTRY_STREET_ADDRESS_MIN_LENGTH.' chars</span>');
if (!defined('ENTRY_SUBURB')) define('ENTRY_SUBURB', 'Suburb:');
if (!defined('ENTRY_SUBURB_ERROR')) define('ENTRY_SUBURB_ERROR', '');
if (!defined('ENTRY_POST_CODE')) define('ENTRY_POST_CODE', 'Post Code:');
if (!defined('ENTRY_POST_CODE_ERROR')) define('ENTRY_POST_CODE_ERROR', '&nbsp;<span class="errorText">min '.ENTRY_POSTCODE_MIN_LENGTH.' chars</span>');
if (!defined('ENTRY_CITY')) define('ENTRY_CITY', 'City:');
if (!defined('ENTRY_CITY_ERROR')) define('ENTRY_CITY_ERROR', '&nbsp;<span class="errorText">min '.ENTRY_CITY_MIN_LENGTH.' chars</span>');
if (!defined('ENTRY_STATE')) define('ENTRY_STATE', 'State:');
if (!defined('ENTRY_STATE_ERROR')) define('ENTRY_STATE_ERROR', '&nbsp;<span class="errorText">required</span>');
if (!defined('ENTRY_COUNTRY')) define('ENTRY_COUNTRY', 'Country:');
if (!defined('ENTRY_COUNTRY_ERROR')) define('ENTRY_COUNTRY_ERROR', '');
if (!defined('ENTRY_TELEPHONE_NUMBER')) define('ENTRY_TELEPHONE_NUMBER', 'Telephone Number:');
if (!defined('ENTRY_TELEPHONE_NUMBER_ERROR')) define('ENTRY_TELEPHONE_NUMBER_ERROR', '&nbsp;<span class="errorText">min '.ENTRY_TELEPHONE_MIN_LENGTH.' chars</span>');
if (!defined('ENTRY_FAX_NUMBER')) define('ENTRY_FAX_NUMBER', 'Fax Number:');
if (!defined('ENTRY_FAX_NUMBER_ERROR')) define('ENTRY_FAX_NUMBER_ERROR', '');
if (!defined('ENTRY_NEWSLETTER')) define('ENTRY_NEWSLETTER', 'Newsletter:');
if (!defined('ENTRY_NEWSLETTER_YES')) define('ENTRY_NEWSLETTER_YES', 'Subscribed');
if (!defined('ENTRY_NEWSLETTER_NO')) define('ENTRY_NEWSLETTER_NO', 'Unsubscribed');
if (!defined('ENTRY_NEWSLETTER_ERROR')) define('ENTRY_NEWSLETTER_ERROR', '');

// images
if (!defined('IMAGE_ANI_SEND_EMAIL')) define('IMAGE_ANI_SEND_EMAIL', 'Sending E-Mail');
if (!defined('IMAGE_BACK')) define('IMAGE_BACK', 'Back');
if (!defined('IMAGE_BACKUP')) define('IMAGE_BACKUP', 'Backup');
if (!defined('IMAGE_CANCEL')) define('IMAGE_CANCEL', 'Cancel');
if (!defined('IMAGE_CONFIRM')) define('IMAGE_CONFIRM', 'Confirm');
if (!defined('IMAGE_COPY')) define('IMAGE_COPY', 'Copy');
if (!defined('IMAGE_COPY_TO')) define('IMAGE_COPY_TO', 'Copy To');
if (!defined('IMAGE_DETAILS')) define('IMAGE_DETAILS', 'Details');
if (!defined('IMAGE_DELETE')) define('IMAGE_DELETE', 'Delete');
if (!defined('IMAGE_EDIT')) define('IMAGE_EDIT', 'Edit');
if (!defined('IMAGE_EMAIL')) define('IMAGE_EMAIL', 'Email');
if (!defined('IMAGE_FILE_MANAGER')) define('IMAGE_FILE_MANAGER', 'File Manager');
if (!defined('IMAGE_ICON_STATUS_GREEN')) define('IMAGE_ICON_STATUS_GREEN', 'Active');
if (!defined('IMAGE_ICON_STATUS_GREEN_LIGHT')) define('IMAGE_ICON_STATUS_GREEN_LIGHT', 'Set Active');
if (!defined('IMAGE_ICON_STATUS_RED')) define('IMAGE_ICON_STATUS_RED', 'Inactive');
if (!defined('IMAGE_ICON_STATUS_RED_LIGHT')) define('IMAGE_ICON_STATUS_RED_LIGHT', 'Set Inactive');
if (!defined('IMAGE_ICON_INFO')) define('IMAGE_ICON_INFO', 'Info');
if (!defined('IMAGE_INSERT')) define('IMAGE_INSERT', 'Insert');
if (!defined('IMAGE_LOCK')) define('IMAGE_LOCK', 'Lock');
if (!defined('IMAGE_MODULE_INSTALL')) define('IMAGE_MODULE_INSTALL', 'Install Module');
if (!defined('IMAGE_MODULE_REMOVE')) define('IMAGE_MODULE_REMOVE', 'Remove Module');
if (!defined('IMAGE_MOVE')) define('IMAGE_MOVE', 'Move');
if (!defined('IMAGE_NEW_BANNER')) define('IMAGE_NEW_BANNER', 'New Banner');
if (!defined('IMAGE_NEW_CATEGORY')) define('IMAGE_NEW_CATEGORY', 'New Category');
if (!defined('IMAGE_NEW_COUNTRY')) define('IMAGE_NEW_COUNTRY', 'New Country');
if (!defined('IMAGE_NEW_CURRENCY')) define('IMAGE_NEW_CURRENCY', 'New Currency');
if (!defined('IMAGE_NEW_FILE')) define('IMAGE_NEW_FILE', 'New File');
if (!defined('IMAGE_NEW_FOLDER')) define('IMAGE_NEW_FOLDER', 'New Folder');
if (!defined('IMAGE_NEW_LANGUAGE')) define('IMAGE_NEW_LANGUAGE', 'New Language');
if (!defined('IMAGE_NEW_NEWSLETTER')) define('IMAGE_NEW_NEWSLETTER', 'New Newsletter');
if (!defined('IMAGE_NEW_PRODUCT')) define('IMAGE_NEW_PRODUCT', 'New Product');
if (!defined('IMAGE_NEW_SALE')) define('IMAGE_NEW_SALE', 'New Sale');
if (!defined('IMAGE_NEW_TAX_CLASS')) define('IMAGE_NEW_TAX_CLASS', 'New Tax Class');
if (!defined('IMAGE_NEW_TAX_RATE')) define('IMAGE_NEW_TAX_RATE', 'New Tax Rate');
if (!defined('IMAGE_NEW_TAX_ZONE')) define('IMAGE_NEW_TAX_ZONE', 'New Tax Zone');
if (!defined('IMAGE_NEW_ZONE')) define('IMAGE_NEW_ZONE', 'New Zone');
if (!defined('IMAGE_ORDERS')) define('IMAGE_ORDERS', 'Orders');
if (!defined('IMAGE_ORDERS_INVOICE')) define('IMAGE_ORDERS_INVOICE', 'Invoice');
if (!defined('IMAGE_ORDERS_PACKINGSLIP')) define('IMAGE_ORDERS_PACKINGSLIP', 'Packing Slip');
if (!defined('IMAGE_PREVIEW')) define('IMAGE_PREVIEW', 'Preview');
if (!defined('IMAGE_RESTORE')) define('IMAGE_RESTORE', 'Restore');
if (!defined('IMAGE_RESET')) define('IMAGE_RESET', 'Reset');
if (!defined('IMAGE_SAVE')) define('IMAGE_SAVE', 'Save');
if (!defined('IMAGE_SEARCH')) define('IMAGE_SEARCH', 'Search');
if (!defined('IMAGE_SELECT')) define('IMAGE_SELECT', 'Select');
if (!defined('IMAGE_SEND')) define('IMAGE_SEND', 'Send');
if (!defined('IMAGE_SEND_EMAIL')) define('IMAGE_SEND_EMAIL', 'Send Email');
if (!defined('IMAGE_UNLOCK')) define('IMAGE_UNLOCK', 'Unlock');
if (!defined('IMAGE_UPDATE')) define('IMAGE_UPDATE', 'Update');
if (!defined('IMAGE_UPDATE_CURRENCIES')) define('IMAGE_UPDATE_CURRENCIES', 'Update Exchange Rate');
if (!defined('IMAGE_UPLOAD')) define('IMAGE_UPLOAD', 'Upload');

if (!defined('ICON_CROSS')) define('ICON_CROSS', 'False');
if (!defined('ICON_CURRENT_FOLDER')) define('ICON_CURRENT_FOLDER', 'Current Folder');
if (!defined('ICON_DELETE')) define('ICON_DELETE', 'Delete');
//added for quick product edit DMG
if (!defined('ICON_EDIT')) define('ICON_EDIT','Edit');
if (!defined('ICON_ERROR')) define('ICON_ERROR', 'Error');
if (!defined('ICON_FILE')) define('ICON_FILE', 'File');
if (!defined('ICON_FILE_DOWNLOAD')) define('ICON_FILE_DOWNLOAD', 'Download');
if (!defined('ICON_FOLDER')) define('ICON_FOLDER', 'Folder');
if (!defined('ICON_LOCKED')) define('ICON_LOCKED', 'Locked');
if (!defined('ICON_PREVIOUS_LEVEL')) define('ICON_PREVIOUS_LEVEL', 'Previous Level');
if (!defined('ICON_PREVIEW')) define('ICON_PREVIEW', 'Preview');
if (!defined('ICON_STATISTICS')) define('ICON_STATISTICS', 'Statistics');
if (!defined('ICON_SUCCESS')) define('ICON_SUCCESS', 'Success');
if (!defined('ICON_TICK')) define('ICON_TICK', 'True');
if (!defined('ICON_UNLOCKED')) define('ICON_UNLOCKED', 'Unlocked');
if (!defined('ICON_WARNING')) define('ICON_WARNING', 'Warning');

// constants for use in tep_prev_next_display function
if (!defined('TEXT_RESULT_PAGE')) define('TEXT_RESULT_PAGE', 'Page %s of %d');
if (!defined('TEXT_DISPLAY_NUMBER_OF_LANGUAGES')) define('TEXT_DISPLAY_NUMBER_OF_LANGUAGES', 'Displaying <b>%d</b> to <b>%d</b> (of <b>%d</b> languages)');
if (!defined('TEXT_DISPLAY_NUMBER_OF_MANUFACTURERS')) define('TEXT_DISPLAY_NUMBER_OF_MANUFACTURERS', 'Displaying <b>%d</b> to <b>%d</b> (of <b>%d</b> manufacturers)');
if (!defined('TEXT_DISPLAY_NUMBER_OF_SPECIALS')) define('TEXT_DISPLAY_NUMBER_OF_SPECIALS', 'Displaying <b>%d</b> to <b>%d</b> (of <b>%d</b> products on special)');
if (!defined('TEXT_DISPLAY_NUMBER_OF_ZONES')) define('TEXT_DISPLAY_NUMBER_OF_ZONES', 'Displaying <b>%d</b> to <b>%d</b> (of <b>%d</b> zones)');
if (!defined('TEXT_DISPLAY_NUMBER_OF_VENDORS')) define('TEXT_DISPLAY_NUMBER_OF_VENDORS', 'Displaying <b>%d</b> to <b>%d</b> (of <b>%d</b> vendors)');


if (!defined('PREVNEXT_BUTTON_PREV')) define('PREVNEXT_BUTTON_PREV', '&lt;&lt;');
if (!defined('PREVNEXT_BUTTON_NEXT')) define('PREVNEXT_BUTTON_NEXT', '&gt;&gt;');

if (!defined('TEXT_DEFAULT')) define('TEXT_DEFAULT', 'default');
if (!defined('TEXT_SET_DEFAULT')) define('TEXT_SET_DEFAULT', 'Set as default');
if (!defined('TEXT_FIELD_REQUIRED')) define('TEXT_FIELD_REQUIRED', '&nbsp;<span class="fieldRequired">* Required</span>');

if (!defined('ERROR_NO_DEFAULT_CURRENCY_DEFINED')) define('ERROR_NO_DEFAULT_CURRENCY_DEFINED', 'Error: There is currently no default currency set. Please set one at: Administration Tool->Localization->Currencies');

if (!defined('TEXT_CACHE_CATEGORIES')) define('TEXT_CACHE_CATEGORIES', 'Categories Box');
if (!defined('TEXT_CACHE_MANUFACTURERS')) define('TEXT_CACHE_MANUFACTURERS', 'Manufacturers Box');
if (!defined('TEXT_CACHE_ALSO_PURCHASED')) define('TEXT_CACHE_ALSO_PURCHASED', 'Also Purchased Module');

if (!defined('TEXT_NONE')) define('TEXT_NONE', '--none--');
if (!defined('TEXT_TOP')) define('TEXT_TOP', 'Top');

if (!defined('ERROR_DESTINATION_DOES_NOT_EXIST')) define('ERROR_DESTINATION_DOES_NOT_EXIST', 'Error: Destination does not exist.');
if (!defined('ERROR_DESTINATION_NOT_WRITEABLE')) define('ERROR_DESTINATION_NOT_WRITEABLE', 'Error: Destination not writeable.');
if (!defined('ERROR_FILE_NOT_SAVED')) define('ERROR_FILE_NOT_SAVED', 'Error: File upload not saved.');
if (!defined('ERROR_FILETYPE_NOT_ALLOWED')) define('ERROR_FILETYPE_NOT_ALLOWED', 'Error: File upload type not allowed.');
if (!defined('SUCCESS_FILE_SAVED_SUCCESSFULLY')) define('SUCCESS_FILE_SAVED_SUCCESSFULLY', 'Success: File upload saved successfully.');
if (!defined('WARNING_NO_FILE_UPLOADED')) define('WARNING_NO_FILE_UPLOADED', 'Warning: No file uploaded.');
if (!defined('WARNING_FILE_UPLOADS_DISABLED')) define('WARNING_FILE_UPLOADS_DISABLED', 'Warning: File uploads are disabled in the php.ini configuration file.');

if (!defined('BOX_HEADING_PAYPALIPN_ADMIN')) define('BOX_HEADING_PAYPALIPN_ADMIN', 'Paypal IPN'); // PAYPALIPN
if (!defined('BOX_PAYPALIPN_ADMIN_TRANSACTIONS')) define('BOX_PAYPALIPN_ADMIN_TRANSACTIONS', 'Transactions'); // PAYPALIPN
if (!defined('BOX_PAYPALIPN_ADMIN_TESTS')) define('BOX_PAYPALIPN_ADMIN_TESTS', 'Send Test IPN'); // PAYPALIPN
if (!defined('BOX_CATALOG_XSELL_PRODUCTS')) define('BOX_CATALOG_XSELL_PRODUCTS', 'Cross Sell Products'); // X-Sell

if (!defined('IMAGE_BUTTON_PRINT_ORDER')) define('IMAGE_BUTTON_PRINT_ORDER', 'Order Printable');

// BOF: Lango Added for print order MOD
if (!defined('IMAGE_BUTTON_PRINT')) define('IMAGE_BUTTON_PRINT', 'Print');
// EOF: Lango Added for print order MOD

// BOF: Lango Added for Featured product MOD
 if (!defined('BOX_CATALOG_FEATURED')) define('BOX_CATALOG_FEATURED', 'Featured Products');
// EOF: Lango Added for Featured product MOD

// BOF: Lango Added for Sales Stats MOD
if (!defined('BOX_REPORTS_MONTHLY_SALES')) define('BOX_REPORTS_MONTHLY_SALES', 'Monthly Sales/Tax');
// EOF: Lango Added for Sales Stats MOD

include('includes/languages/order_edit_english.php');
//included for CRE Loaded 6.1 Release edition

 if (!defined('BOX_TITLE_CRELOADED')) define('BOX_TITLE_CRELOADED', 'CRE Loaded Project');
 if (!defined('LINK_CRE_FORUMS')) define('LINK_CRE_FORUMS','CRE Loaded Forums');
 if (!defined('LINK_CRW_SUPPORT')) define('LINK_CRW_SUPPORT','Technical Support');
// General Release Edition
 if (!defined('LINK_SF_CRELOADED')) define('LINK_SF_CRELOADED','Source Forge Home');
 if (!defined('LINK_SF_BUGTRACKER')) define('LINK_SF_BUGTRACKER','Bug Tracker');
 if (!defined('LINK_CRE_FILES')) define('LINK_CRE_FILES','CRE Downloads');
 if (!defined('LINK_SF_SUPPORT')) define('LINK_SF_SUPPORT','Support Request');
 if (!defined('LINK_SF_TASK')) define('LINK_SF_TASK','Task Tracker');
 if (!defined('LINK_SF_CVS')) define('LINK_SF_CVS','Browse CVS');
 if (!defined('LINK_CRE_FILES')) define('LINK_CRE_FILES','CRE Downloads');
 if (!defined('LINK_SF_FEATURE')) define('LINK_SF_FEATURE','Feature Request');
//included for Backup mySQL (courtesy Zen-Cart Team) DMG
 if (!defined('BOX_TOOLS_MYSQL_BACKUP')) define('BOX_TOOLS_MYSQL_BACKUP','Backup mySQL');

if (!defined('BOX_REPORTS_RECOVER_CART_SALES')) define('BOX_REPORTS_RECOVER_CART_SALES', 'Recovered Sales Results');
if (!defined('BOX_TOOLS_RECOVER_CART')) define('BOX_TOOLS_RECOVER_CART', 'Recover Cart Sales');

//begin Inactive User Report
if (!defined('BOX_REPORTS_INACTIVE_USER')) define('BOX_REPORTS_INACTIVE_USER', 'Inactive Users');
//end Inactive User Report


// fedex
if (!defined('IMAGE_ORDERS_SHIP')) define('IMAGE_ORDERS_SHIP', 'Ship Package');
if (!defined('IMAGE_ORDERS_FEDEX_LABEL')) define('IMAGE_ORDERS_FEDEX_LABEL','View or Print FedEx Shipping Label');
if (!defined('IMAGE_ORDERS_TRACK')) define('IMAGE_ORDERS_TRACK','Track FedEx Shipment');
if (!defined('IMAGE_ORDERS_CANCEL_SHIPMENT')) define('IMAGE_ORDERS_CANCEL_SHIPMENT','Cancel FedEx Shipment');
// fedex eof

if (!defined('BOX_CATALOG_QUICK_UPDATES')) define('BOX_CATALOG_QUICK_UPDATES', 'Quick Updates');

// START - Admin Notes
if (!defined('BOX_TOOLS_ADMIN_NOTES')) define('BOX_TOOLS_ADMIN_NOTES', 'Admin Notes');
// END - Admin Notes

if (!defined('BOX_CATALOG_PARENT_STOCK')) define('BOX_CATALOG_PARENT_STOCK', 'Internal Numbers');

	if (!defined('BOX_CUSTOMERS_ORDERLIST')) define('BOX_CUSTOMERS_ORDERLIST', 'Generate orderlist');

	if (!defined('BOX_HEADING_FEEDS')) define('BOX_HEADING_FEEDS', 'Feeds');
	if (!defined('BOX_CATALOG_FEEDS')) define('BOX_CATALOG_FEEDS', 'Feed Management');

		if (!defined('BOX_VISITORS')) define('BOX_VISITORS', 'Visitors');

// tools text in includes/boxes/tools.php
if (!defined('BOX_TOOLS_TESTIMONIALS_MANAGER')) define('BOX_TOOLS_TESTIMONIALS_MANAGER', 'Testimonials Mgr');

// images
if (!defined('IMAGE_NEW_TESTIMONIAL')) define('IMAGE_NEW_TESTIMONIAL', 'New Testimonial');

if (!defined('BOX_HEADING_NEWSLETTER')) define('BOX_HEADING_NEWSLETTER', 'Newsletter');
if (!defined('BOX_NEWSLETTER_ADMIN')) define('BOX_NEWSLETTER_ADMIN', 'Newsletter Admin');
if (!defined('BOX_NEWSLETTER_EXTRA_INFOS')) define('BOX_NEWSLETTER_EXTRA_INFOS', 'Header/Footer Info');
if (!defined('BOX_NEWSLETTER_UPDATE')) define('BOX_NEWSLETTER_UPDATE', 'Update Table');
if (!defined('BOX_NEWSLETTER_SUBSCRIBERS_UTILITIES')) define('BOX_NEWSLETTER_SUBSCRIBERS_UTILITIES', 'Utilities');
if (!defined('BOX_NEWSLETTER_SUBSCRIBERS_VIEW')) define('BOX_NEWSLETTER_SUBSCRIBERS_VIEW', 'Subscribers Admin');
if (!defined('BOX_NEWSLETTER_EXTRA_DEFAULT')) define('BOX_NEWSLETTER_EXTRA_DEFAULT', 'Newsletter Default');
if (!defined('BOX_CUSTOMERS_NEWSLETTER_MANAGER')) define('BOX_CUSTOMERS_NEWSLETTER_MANAGER', 'Newsletter Admin');
if (!defined('TABLE_HEADING_EMAIL')) define('TABLE_HEADING_EMAIL','E Mails');
if (!defined('TEXT_UNSUBSCRIBE')) define('TEXT_UNSUBSCRIBE','Unsubscribe : ');



if (!defined('BOX_REPORTS_CUSTOMERS_PER_PRODUCT')) define('BOX_REPORTS_CUSTOMERS_PER_PRODUCT', 'Customers/Products');

if (!defined('BOX_CUSTOMERS_CHANGE_PASSWORD')) define('BOX_CUSTOMERS_CHANGE_PASSWORD', 'Change Password');

if (!defined('BOX_VENDORS_VENDORS')) define('BOX_VENDORS_VENDORS', 'Vendors');




if (!defined('BOX_CUSTOMERS_REFERRALS')) define('BOX_CUSTOMERS_REFERRALS', 'Referrals'); //rmh referrals
if (!defined('BOX_REPORTS_REFERRAL_SOURCES')) define('BOX_REPORTS_REFERRAL_SOURCES', 'Referral Sources'); //rmh referrals
// Maxmind Mod Noel Latsha
if (!defined('MAXMIND_DISTANCE')) define('MAXMIND_DISTANCE', 'Distance: ');
if (!defined('MAXMIND_COUNTRY')) define('MAXMIND_COUNTRY', 'Country Match: ');
if (!defined('MAXMIND_CODE')) define('MAXMIND_CODE', 'Country Code: ');
if (!defined('MAXMIND_FREE_EMAIL')) define('MAXMIND_FREE_EMAIL', 'Free Email: ');
if (!defined('MAXMIND_ANONYMOUS')) define('MAXMIND_ANONYMOUS', 'Anonymous Proxy: ');
if (!defined('MAXMIND_SCORE')) define('MAXMIND_SCORE', 'Score:');
if (!defined('MAXMIND_BIN_MATCH')) define('MAXMIND_BIN_MATCH', 'Bin Match: ');
if (!defined('MAXMIND_BIN_COUNTRY')) define('MAXMIND_BIN_COUNTRY', 'Bin Country: ');
if (!defined('MAXMIND_ERR')) define('MAXMIND_ERR', 'Error: ');
if (!defined('MAXMIND_PROXY_SCORE')) define('MAXMIND_PROXY_SCORE', 'Proxy Score: ');
if (!defined('MAXMIND_SPAM')) define('MAXMIND_SPAM', 'Spam Score: ');
if (!defined('MAXMIND_BIN_NAME')) define('MAXMIND_BIN_NAME', 'Bin Name: ');
if (!defined('MAXMIND_BIN_COUNTRY')) define('MAXMIND_BIN_COUNTRY', 'Bin Country: ');
if (!defined('MAXMIND_IP_ISP')) define('MAXMIND_IP_ISP', 'ISP: ');
if (!defined('MAXMIND_IP_ISP_ORG')) define('MAXMIND_IP_ISP_ORG', 'ISP Org: ');
if (!defined('MAXMIND_IP_CITY')) define('MAXMIND_IP_CITY', '<b>*</b>City: ');
if (!defined('MAXMIND_IP_REGION')) define('MAXMIND_IP_REGION', '<b>*</b>Region: ');
if (!defined('MAXMIND_IP_LATITUDE')) define('MAXMIND_IP_LATITUDE', '<b>*</b>Latitude: ');
if (!defined('MAXMIND_IP_LONGITUDE')) define('MAXMIND_IP_LONGITUDE', '<b>*</b>Longitude: ');
if (!defined('MAXMIND_MAXMIND')) define('MAXMIND_MAXMIND', '<b>*NOTE: You need to be subscribed to Premium Services at MaxMind.com for the following fields:</b>');
if (!defined('MAXMIND_HI_RISK')) define('MAXMIND_HI_RISK', 'High Risk Country: ');
if (!defined('MAXMIND_CUST_PHONE')) define('MAXMIND_CUST_PHONE', 'Phone Match: ');
if (!defined('MAXMIND_DETAILS')) define('MAXMIND_DETAILS', 'See <a href="http://www.maxmind.com/app/ccv?rId=duelpass" target="_blank"><u>MaxMind.com</u></a> for explanation of fields');
// End Maxmind Mod Noel Latsha
?>
