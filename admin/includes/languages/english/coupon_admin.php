<?php
/*
 $Id: coupon_admin.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com
 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('TOP_BAR_TITLE')) define('TOP_BAR_TITLE', 'Statistics');
if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Discount Coupons');
if (!defined('HEADING_TITLE_STATUS')) define('HEADING_TITLE_STATUS', 'Status : ');
if (!defined('TEXT_CUSTOMER')) define('TEXT_CUSTOMER', 'Customer:');
if (!defined('TEXT_COUPON')) define('TEXT_COUPON', 'Coupon Name');
if (!defined('TEXT_COUPON_ALL')) define('TEXT_COUPON_ALL', 'All Coupons');
if (!defined('TEXT_COUPON_ACTIVE')) define('TEXT_COUPON_ACTIVE', 'Active Coupons');
if (!defined('TEXT_COUPON_INACTIVE')) define('TEXT_COUPON_INACTIVE', 'Inactive Coupons');
if (!defined('TEXT_SUBJECT')) define('TEXT_SUBJECT', 'Subject:');
if (!defined('TEXT_FROM')) define('TEXT_FROM', 'From:');
if (!defined('TEXT_FREE_SHIPPING')) define('TEXT_FREE_SHIPPING', 'Free Shipping');
if (!defined('TEXT_MESSAGE')) define('TEXT_MESSAGE', 'Message:');
if (!defined('TEXT_SELECT_CUSTOMER')) define('TEXT_SELECT_CUSTOMER', 'Select Customer');
if (!defined('TEXT_ALL_CUSTOMERS')) define('TEXT_ALL_CUSTOMERS', 'All Customers');
if (!defined('TEXT_NEWSLETTER_CUSTOMERS')) define('TEXT_NEWSLETTER_CUSTOMERS', 'To All Newsletter Subscribers');
if (!defined('TEXT_CONFIRM_DELETE')) define('TEXT_CONFIRM_DELETE', 'Are you sure you want to delete this Coupon?');

if (!defined('TEXT_TO_REDEEM')) define('TEXT_TO_REDEEM', 'To redeem this coupon just enter the following code during checkout.');
if (!defined('TEXT_IN_CASE')) define('TEXT_IN_CASE', ' in case you have any problems. ');
if (!defined('TEXT_VOUCHER_IS')) define('TEXT_VOUCHER_IS', 'Coupon code: ');
if (!defined('TEXT_REMEMBER')) define('TEXT_REMEMBER', 'Be sure to keep this email or write down your code!');
if (!defined('TEXT_VISIT')) define('TEXT_VISIT', 'when you visit '.HTTP_SERVER.DIR_WS_CATALOG);
if (!defined('TEXT_ENTER_CODE')) define('TEXT_ENTER_CODE', ' and enter the code ');

if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');

if (!defined('CUSTOMER_ID')) define('CUSTOMER_ID', 'Customer id');
if (!defined('CUSTOMER_NAME')) define('CUSTOMER_NAME', 'Customer Name');
if (!defined('REDEEM_DATE')) define('REDEEM_DATE', 'Date Redeemed');
if (!defined('IP_ADDRESS')) define('IP_ADDRESS', 'IP Address');

if (!defined('TEXT_REDEMPTIONS')) define('TEXT_REDEMPTIONS', 'Redemptions');
if (!defined('TEXT_REDEMPTIONS_TOTAL')) define('TEXT_REDEMPTIONS_TOTAL', 'In Total');
if (!defined('TEXT_REDEMPTIONS_CUSTOMER')) define('TEXT_REDEMPTIONS_CUSTOMER', 'For this Customer');
if (!defined('TEXT_NO_FREE_SHIPPING')) define('TEXT_NO_FREE_SHIPPING', 'No Free Shipping');

if (!defined('NOTICE_EMAIL_SENT_TO')) define('NOTICE_EMAIL_SENT_TO', 'Notice: Email sent to: %s');
if (!defined('ERROR_NO_CUSTOMER_SELECTED')) define('ERROR_NO_CUSTOMER_SELECTED', 'Error: No customer has been selected.');
if (!defined('COUPON_NAME')) define('COUPON_NAME', 'Coupon Name');
//define('COUPON_VALUE', 'Coupon Value');
if (!defined('COUPON_AMOUNT')) define('COUPON_AMOUNT', 'Coupon Amount');
if (!defined('COUPON_CODE')) define('COUPON_CODE', 'Coupon Code');
if (!defined('COUPON_STARTDATE')) define('COUPON_STARTDATE', 'Start Date');
if (!defined('COUPON_FINISHDATE')) define('COUPON_FINISHDATE', 'End Date');
if (!defined('COUPON_FREE_SHIP')) define('COUPON_FREE_SHIP', 'Free Shipping');
if (!defined('COUPON_DESC')) define('COUPON_DESC', 'Coupon Description');
if (!defined('COUPON_MIN_ORDER')) define('COUPON_MIN_ORDER', 'Coupon Minimum Order');
if (!defined('COUPON_USES_COUPON')) define('COUPON_USES_COUPON', 'Uses per Coupon');
if (!defined('COUPON_USES_USER')) define('COUPON_USES_USER', 'Uses per Customer');
if (!defined('COUPON_PRODUCTS')) define('COUPON_PRODUCTS', 'Valid Product List');
if (!defined('COUPON_CATEGORIES')) define('COUPON_CATEGORIES', 'Valid Categories List');
if (!defined('VOUCHER_NUMBER_USED')) define('VOUCHER_NUMBER_USED', 'Number Used');
if (!defined('DATE_CREATED')) define('DATE_CREATED', 'Date Created');
if (!defined('DATE_MODIFIED')) define('DATE_MODIFIED', 'Date Modified');
if (!defined('TEXT_HEADING_NEW_COUPON')) define('TEXT_HEADING_NEW_COUPON', 'Create New Coupon');
if (!defined('TEXT_NEW_INTRO')) define('TEXT_NEW_INTRO', 'Please fill out the following information for the new coupon.<br>');


if (!defined('COUPON_NAME_HELP')) define('COUPON_NAME_HELP', 'A short name for the coupon');
if (!defined('COUPON_AMOUNT_HELP')) define('COUPON_AMOUNT_HELP', 'The value of the discount for the coupon, either absolute or in % for a discount from the order total.');
if (!defined('COUPON_CODE_HELP')) define('COUPON_CODE_HELP', 'You can enter your own code here, or leave blank for an auto generated one.');
if (!defined('COUPON_STARTDATE_HELP')) define('COUPON_STARTDATE_HELP', 'The date the coupon will be valid from');
if (!defined('COUPON_FINISHDATE_HELP')) define('COUPON_FINISHDATE_HELP', 'The date the coupon expires');
if (!defined('COUPON_FREE_SHIP_HELP')) define('COUPON_FREE_SHIP_HELP', 'The coupon gives free shipping on an order. Note: This overrides the coupon_amount figure but respects the minimum order value');
if (!defined('COUPON_DESC_HELP')) define('COUPON_DESC_HELP', 'A description of the coupon for the customer');
if (!defined('COUPON_MIN_ORDER_HELP')) define('COUPON_MIN_ORDER_HELP', 'The minimum order value before the coupon is valid');
if (!defined('COUPON_USES_COUPON_HELP')) define('COUPON_USES_COUPON_HELP', 'The maximum number of times the coupon can be used; leave blank if you want no limit.');
if (!defined('COUPON_USES_USER_HELP')) define('COUPON_USES_USER_HELP', 'Number of times a user can use the coupon, leave blank for no limit.');
if (!defined('COUPON_PRODUCTS_HELP')) define('COUPON_PRODUCTS_HELP', 'A comma separated list of product_ids that this coupon can be used with. Leave blank for no restrictions.');
if (!defined('COUPON_CATEGORIES_HELP')) define('COUPON_CATEGORIES_HELP', 'A comma separated list of cpaths that this coupon can be used with, leave blank for no restrictions.');
?>
