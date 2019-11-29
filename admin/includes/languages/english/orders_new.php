<?php
if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Orders');
if (!defined('HEADING_TITLE_SEARCH')) define('HEADING_TITLE_SEARCH', 'Order ID:');
if (!defined('HEADING_TITLE_STATUS')) define('HEADING_TITLE_STATUS', 'Status:');

if (!defined('TABLE_HEADING_CUSTOMERS')) define('TABLE_HEADING_CUSTOMERS', 'Customers');
if (!defined('TABLE_HEADING_ORDER_TOTAL')) define('TABLE_HEADING_ORDER_TOTAL', 'Order Total');
if (!defined('TABLE_HEADING_STATUS')) define('TABLE_HEADING_STATUS', 'Status');
if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');

if (!defined('TABLE_HEADING_CUSTOMER_NOTIFIED')) define('TABLE_HEADING_CUSTOMER_NOTIFIED', 'Customer Notified');

//begin PayPal_Shopping_Cart_IPN
if (!defined('TABLE_HEADING_PAYMENT_STATUS')) define('TABLE_HEADING_PAYMENT_STATUS', 'Payment Status');
//end PayPal_Shopping_Cart_IPN

if (!defined('ENTRY_CUSTOMER')) define('ENTRY_CUSTOMER', 'Customer:');
if (!defined('ENTRY_DELIVERY_TO')) define('ENTRY_DELIVERY_TO', 'Delivery To:');
if (!defined('ENTRY_BILLING_ADDRESS')) define('ENTRY_BILLING_ADDRESS', 'Billing Address:');
if (!defined('ENTRY_CREDIT_CARD_TYPE')) define('ENTRY_CREDIT_CARD_TYPE', 'Credit Card Type:');
if (!defined('ENTRY_CREDIT_CARD_OWNER')) define('ENTRY_CREDIT_CARD_OWNER', 'Credit Card Owner:');
if (!defined('ENTRY_CREDIT_CARD_NUMBER')) define('ENTRY_CREDIT_CARD_NUMBER', 'Credit Card Number:');
if (!defined('ENTRY_CREDIT_CARD_EXPIRES')) define('ENTRY_CREDIT_CARD_EXPIRES', 'Credit Card Expires:');
if (!defined('ENTRY_DATE_PURCHASED')) define('ENTRY_DATE_PURCHASED', 'Date Purchased:');
if (!defined('ENTRY_STATUS')) define('ENTRY_STATUS', 'Status:');
if (!defined('ENTRY_DATE_LAST_UPDATED')) define('ENTRY_DATE_LAST_UPDATED', 'Date Last Updated:');
if (!defined('ENTRY_NOTIFY_CUSTOMER')) define('ENTRY_NOTIFY_CUSTOMER', 'Notify Customer:');
if (!defined('ENTRY_NOTIFY_COMMENTS')) define('ENTRY_NOTIFY_COMMENTS', 'Append Comments:');
if (!defined('ENTRY_PRINTABLE')) define('ENTRY_PRINTABLE', 'Print Invoice');

if (!defined('TEXT_INFO_HEADING_DELETE_ORDER')) define('TEXT_INFO_HEADING_DELETE_ORDER', 'Delete Order');
if (!defined('TEXT_INFO_DELETE_INTRO')) define('TEXT_INFO_DELETE_INTRO', 'Are you sure you want to delete this order?');
if (!defined('TEXT_INFO_DELETE_DATA')) define('TEXT_INFO_DELETE_DATA', 'Customers Name ');
if (!defined('TEXT_INFO_DELETE_DATA_OID')) define('TEXT_INFO_DELETE_DATA_OID', 'Order Number ');
if (!defined('TEXT_INFO_RESTOCK_PRODUCT_QUANTITY')) define('TEXT_INFO_RESTOCK_PRODUCT_QUANTITY', 'Restock product quantity');
if (!defined('TEXT_DATE_ORDER_CREATED')) define('TEXT_DATE_ORDER_CREATED', 'Date Created:');
if (!defined('TEXT_DATE_ORDER_LAST_MODIFIED')) define('TEXT_DATE_ORDER_LAST_MODIFIED', 'Last Modified:');
if (!defined('TEXT_INFO_PAYMENT_METHOD')) define('TEXT_INFO_PAYMENT_METHOD', 'Payment Method:');

if (!defined('TEXT_ALL_ORDERS')) define('TEXT_ALL_ORDERS', 'All Orders');
if (!defined('TEXT_NO_ORDER_HISTORY')) define('TEXT_NO_ORDER_HISTORY', 'No Order History Available');

if (!defined('EMAIL_SEPARATOR')) define('EMAIL_SEPARATOR', '------------------------------------------------------');
if (!defined('EMAIL_TEXT_SUBJECT')) define('EMAIL_TEXT_SUBJECT', 'Order Update');
if (!defined('EMAIL_TEXT_ORDER_NUMBER')) define('EMAIL_TEXT_ORDER_NUMBER', 'Order Number:');
if (!defined('EMAIL_TEXT_INVOICE_URL')) define('EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice:');
if (!defined('EMAIL_TEXT_DATE_ORDERED')) define('EMAIL_TEXT_DATE_ORDERED', 'Date Ordered:');
if (!defined('EMAIL_TEXT_STATUS_UPDATE')) define('EMAIL_TEXT_STATUS_UPDATE', 'Your order has been updated to the following status.'."\n\n".'New status: %s'."\n\n".'Please reply to this email if you have any questions.'."\n");
if (!defined('EMAIL_TEXT_COMMENTS_UPDATE')) define('EMAIL_TEXT_COMMENTS_UPDATE', 'The comments for your order are'."\n\n%s\n\n");

/*Tracking contribution begin*/
if (!defined('EMAIL_TEXT_TRACKING_NUMBER')) define('EMAIL_TEXT_TRACKING_NUMBER', 'You can track your packages by clicking the link below.');
/*Tracking contribution end*/

if (!defined('ERROR_ORDER_DOES_NOT_EXIST')) define('ERROR_ORDER_DOES_NOT_EXIST', 'Error: Order does not exist.');
if (!defined('SUCCESS_ORDER_UPDATED')) define('SUCCESS_ORDER_UPDATED', 'Success: Order has been successfully updated.');
if (!defined('WARNING_ORDER_NOT_UPDATED')) define('WARNING_ORDER_NOT_UPDATED', 'Warning: Nothing to change. The order was not updated.');
if (!defined('TABLE_HEADING_ORDER_NOTES')) define('TABLE_HEADING_ORDER_NOTES', 'Order Notes:');

?>
