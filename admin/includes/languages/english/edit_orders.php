<?php
if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Edit Order');
if (!defined('HEADING_TITLE_NUMBER')) define('HEADING_TITLE_NUMBER', 'Nr.');
if (!defined('HEADING_TITLE_DATE')) define('HEADING_TITLE_DATE', 'of');
if (!defined('HEADING_SUBTITLE')) define('HEADING_SUBTITLE', 'Please edit all parts as desired and click on the "Update" button below.');
if (!defined('HEADING_TITLE_STATUS')) define('HEADING_TITLE_STATUS', 'Status');
if (!defined('ADDING_TITLE')) define('ADDING_TITLE', 'Add a product to this order');

if (!defined('HINT_UPDATE_TO_CC')) define('HINT_UPDATE_TO_CC', '<span style="color: red;">Hint: </span>Set payment to "Credit Card" to show some additional fields.');
if (!defined('HINT_DELETE_POSITION')) define('HINT_DELETE_POSITION', '<span style="color: red;">Hint: </span>If you edit the price associated with a product attribute, you have to calculate the new item cost manually.');
if (!defined('HINT_TOTALS')) define('HINT_TOTALS', '<span style="color: red;">Hint: </span>Fields with "0" values are deleted when updating the order (exception: shipping).');
if (!defined('HINT_PRESS_UPDATE')) define('HINT_PRESS_UPDATE', 'Please click on "Update" to save all changes.');

if (!defined('TABLE_HEADING_STATUS')) define('TABLE_HEADING_STATUS', 'New Status');
if (!defined('TABLE_HEADING_DELETE')) define('TABLE_HEADING_DELETE', 'Delete?');

if (!defined('TABLE_HEADING_CUSTOMER_NOTIFIED')) define('TABLE_HEADING_CUSTOMER_NOTIFIED', 'Customer notified');

if (!defined('ENTRY_CUSTOMER_NAME')) define('ENTRY_CUSTOMER_NAME', 'Name');
if (!defined('ENTRY_CUSTOMER_COMPANY')) define('ENTRY_CUSTOMER_COMPANY', 'Company');
if (!defined('ENTRY_CUSTOMER_ADDRESS')) define('ENTRY_CUSTOMER_ADDRESS', 'Customer Address');
if (!defined('ENTRY_CUSTOMER_SUBURB')) define('ENTRY_CUSTOMER_SUBURB', 'Suburb');
if (!defined('ENTRY_CUSTOMER_CITY')) define('ENTRY_CUSTOMER_CITY', 'City');
if (!defined('ENTRY_CUSTOMER_STATE')) define('ENTRY_CUSTOMER_STATE', 'State');
if (!defined('ENTRY_CUSTOMER_POSTCODE')) define('ENTRY_CUSTOMER_POSTCODE', 'Postcode');
if (!defined('ENTRY_CUSTOMER_COUNTRY')) define('ENTRY_CUSTOMER_COUNTRY', 'Country');
if (!defined('ENTRY_CUSTOMER_PHONE')) define('ENTRY_CUSTOMER_PHONE', 'Phone');
if (!defined('ENTRY_CUSTOMER_EMAIL')) define('ENTRY_CUSTOMER_EMAIL', 'E-Mail');
if (!defined('ENTRY_ADDRESS')) define('ENTRY_ADDRESS', 'Address');

if (!defined('ENTRY_BILLING_ADDRESS')) define('ENTRY_BILLING_ADDRESS', 'Billing Address');
if (!defined('ENTRY_CREDIT_CARD_TYPE')) define('ENTRY_CREDIT_CARD_TYPE', 'Card Type:');
if (!defined('ENTRY_CREDIT_CARD_OWNER')) define('ENTRY_CREDIT_CARD_OWNER', 'Card Owner:');
if (!defined('ENTRY_CREDIT_CARD_NUMBER')) define('ENTRY_CREDIT_CARD_NUMBER', 'Card Number:');
if (!defined('ENTRY_CREDIT_CARD_EXPIRES')) define('ENTRY_CREDIT_CARD_EXPIRES', 'Card Expires:');
if (!defined('ENTRY_STATUS')) define('ENTRY_STATUS', 'Order Status:');
if (!defined('ENTRY_NOTIFY_CUSTOMER')) define('ENTRY_NOTIFY_CUSTOMER', 'Notify customer:');
if (!defined('ENTRY_NOTIFY_COMMENTS')) define('ENTRY_NOTIFY_COMMENTS', 'Send comments:');

if (!defined('TEXT_NO_ORDER_HISTORY')) define('TEXT_NO_ORDER_HISTORY', 'No order found');

if (!defined('EMAIL_SEPARATOR')) define('EMAIL_SEPARATOR', '------------------------------------------------------');
if (!defined('EMAIL_TEXT_SUBJECT')) define('EMAIL_TEXT_SUBJECT', 'Your order has been updated');
if (!defined('EMAIL_TEXT_ORDER_NUMBER')) define('EMAIL_TEXT_ORDER_NUMBER', 'Order number:');
if (!defined('EMAIL_TEXT_INVOICE_URL')) define('EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice URL:');
if (!defined('EMAIL_TEXT_DATE_ORDERED')) define('EMAIL_TEXT_DATE_ORDERED', 'Order date:');
if (!defined('EMAIL_TEXT_STATUS_UPDATE')) define('EMAIL_TEXT_STATUS_UPDATE', 'Thank you so much for your order with us!'."\n\n".'The status of your order has been updated.'."\n\n".'New status: %s'."\n\n");
define('EMAIL_TEXT_STATUS_UPDATE2', 'If you have questions, please reply to this email.'."\n\n".'With warm regards from your friends at the '.STORE_NAME."\n");
if (!defined('EMAIL_TEXT_COMMENTS_UPDATE')) define('EMAIL_TEXT_COMMENTS_UPDATE', 'Here are the comments for your order:'."\n\n%s\n\n");

if (!defined('ERROR_ORDER_DOES_NOT_EXIST')) define('ERROR_ORDER_DOES_NOT_EXIST', 'Error: No such order.');
if (!defined('SUCCESS_ORDER_UPDATED')) define('SUCCESS_ORDER_UPDATED', 'Completed: Order has been successfully updated.');

if (!defined('ADDPRODUCT_TEXT_CATEGORY_CONFIRM')) define('ADDPRODUCT_TEXT_CATEGORY_CONFIRM', 'OK');
if (!defined('ADDPRODUCT_TEXT_SELECT_PRODUCT')) define('ADDPRODUCT_TEXT_SELECT_PRODUCT', 'Choose a product');
if (!defined('ADDPRODUCT_TEXT_PRODUCT_CONFIRM')) define('ADDPRODUCT_TEXT_PRODUCT_CONFIRM', 'OK');
if (!defined('ADDPRODUCT_TEXT_SELECT_OPTIONS')) define('ADDPRODUCT_TEXT_SELECT_OPTIONS', 'Choose an option');
if (!defined('ADDPRODUCT_TEXT_OPTIONS_CONFIRM')) define('ADDPRODUCT_TEXT_OPTIONS_CONFIRM', 'OK');
if (!defined('ADDPRODUCT_TEXT_OPTIONS_NOTEXIST')) define('ADDPRODUCT_TEXT_OPTIONS_NOTEXIST', 'Product has no options, so skipping...');
if (!defined('ADDPRODUCT_TEXT_CONFIRM_QUANTITY')) define('ADDPRODUCT_TEXT_CONFIRM_QUANTITY', 'pieces of this product');
if (!defined('ADDPRODUCT_TEXT_CONFIRM_ADDNOW')) define('ADDPRODUCT_TEXT_CONFIRM_ADDNOW', 'Add');
if (!defined('ADDPRODUCT_TEXT_STEP')) define('ADDPRODUCT_TEXT_STEP', 'Step');
define('ADDPRODUCT_TEXT_STEP1', ' &laquo; Choose a catalogue. ');
define('ADDPRODUCT_TEXT_STEP2', ' &laquo; Choose a product. ');
define('ADDPRODUCT_TEXT_STEP3', ' &laquo; Choose an option. ');

if (!defined('MENUE_TITLE_CUSTOMER')) define('MENUE_TITLE_CUSTOMER', '1. Customer Data');
if (!defined('MENUE_TITLE_PAYMENT')) define('MENUE_TITLE_PAYMENT', '2. Payment Method');
if (!defined('MENUE_TITLE_ORDER')) define('MENUE_TITLE_ORDER', '3. Ordered Products');
if (!defined('MENUE_TITLE_TOTAL')) define('MENUE_TITLE_TOTAL', '4. Discount, Shipping and Total');
if (!defined('MENUE_TITLE_STATUS')) define('MENUE_TITLE_STATUS', '5. Status and Notification');
if (!defined('MENUE_TITLE_UPDATE')) define('MENUE_TITLE_UPDATE', '6. Update Data');
?>