<?php
if (!defined('MESSAGE_STACK_CUSTOMER_ID')) define('MESSAGE_STACK_CUSTOMER_ID', 'Cart for Customer-ID ');
if (!defined('MESSAGE_STACK_DELETE_SUCCESS')) define('MESSAGE_STACK_DELETE_SUCCESS', ' deleted successfully');
if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Recover Cart Sales v2.11');
if (!defined('HEADING_EMAIL_SENT')) define('HEADING_EMAIL_SENT', 'E-mail Sent Report');
if (!defined('EMAIL_TEXT_LOGIN')) define('EMAIL_TEXT_LOGIN', 'Login to your account here:');
if (!defined('EMAIL_SEPARATOR')) define('EMAIL_SEPARATOR', '------------------------------------------------------');
if (!defined('EMAIL_TEXT_SUBJECT')) define('EMAIL_TEXT_SUBJECT', 'Inquiry from '. STORE_NAME );
if (!defined('EMAIL_TEXT_SALUTATION')) define('EMAIL_TEXT_SALUTATION', 'Dear ' );
if (!defined('EMAIL_TEXT_NEWCUST_INTRO')) define('EMAIL_TEXT_NEWCUST_INTRO', "\n\n".'Thank you for stopping by '.STORE_NAME .
									' and considering us for your purchase. ');
if (!defined('EMAIL_TEXT_CURCUST_INTRO')) define('EMAIL_TEXT_CURCUST_INTRO', "\n\n".'We would like to thank you for having shopped at ' .
									STORE_NAME.' in the past. ');
if (!defined('EMAIL_TEXT_BODY_HEADER')) define('EMAIL_TEXT_BODY_HEADER',
	'We noticed that during a visit to our store you placed ' .
	'the following item(s) in your shopping cart, but did not complete ' .
	'the transaction.'."\n\n" .
	'Shopping Cart Contents:'."\n\n"
	);

if (!defined('EMAIL_TEXT_BODY_FOOTER')) define('EMAIL_TEXT_BODY_FOOTER',
	'We are always interested in knowing what happened ' .
	'and if there was a reason that you decided not to purchase at ' .
	'this time. If you could be so kind as to let us ' .
	'know if you had any issues or concerns, we would appreciate it. ' .
	'We are asking for feedback from you and others as to how we can ' .
	'help make your experience at '. STORE_NAME.' better.'."\n\n".
	'PLEASE NOTE:'."\n".'If you believe you completed your purchase and are ' .
	'wondering why it was not delivered, this email is an indication that ' .
	'your order was NOT completed, and that you have NOT been charged! ' .
	'Please return to the store in order to complete your order.'."\n\n".
	'Our apologies if you already completed your purchase, ' .
	'we try not to send these messages in those cases, but sometimes it is ' .
	'hard for us to tell depending on individual circumstances.'."\n\n".
	'Again, thank you for your time and consideration in helping us ' .
	'improve the '.STORE_NAME." website.\n\nSincerely,\n\n"
	);

if (!defined('DAYS_FIELD_PREFIX')) define('DAYS_FIELD_PREFIX', 'Show for last ');
if (!defined('DAYS_FIELD_POSTFIX')) define('DAYS_FIELD_POSTFIX', ' days ');
if (!defined('DAYS_FIELD_BUTTON')) define('DAYS_FIELD_BUTTON', 'Go');
if (!defined('TABLE_HEADING_DATE')) define('TABLE_HEADING_DATE', 'DATE');
if (!defined('TABLE_HEADING_CONTACT')) define('TABLE_HEADING_CONTACT', 'CONTACTED');
if (!defined('TABLE_HEADING_CUSTOMER')) define('TABLE_HEADING_CUSTOMER', 'CUSTOMER NAME');
if (!defined('TABLE_HEADING_EMAIL')) define('TABLE_HEADING_EMAIL', 'E-MAIL');
if (!defined('TABLE_HEADING_PHONE')) define('TABLE_HEADING_PHONE', 'PHONE');
if (!defined('TABLE_HEADING_DESCRIPTION')) define('TABLE_HEADING_DESCRIPTION', 'DESCRIPTION');
if (!defined('TABLE_HEADING_QUANTY')) define('TABLE_HEADING_QUANTY', 'QTY');
if (!defined('TABLE_GRAND_TOTAL')) define('TABLE_GRAND_TOTAL', 'Grand Total: ');
if (!defined('TABLE_CART_TOTAL')) define('TABLE_CART_TOTAL', 'Cart Total: ');
if (!defined('TEXT_CURRENT_CUSTOMER')) define('TEXT_CURRENT_CUSTOMER', 'CUSTOMER');
if (!defined('TEXT_SEND_EMAIL')) define('TEXT_SEND_EMAIL', 'Send E-mail');
if (!defined('TEXT_RETURN')) define('TEXT_RETURN', '[Click Here To Return]');
if (!defined('TEXT_NOT_CONTACTED')) define('TEXT_NOT_CONTACTED', 'Uncontacted');
if (!defined('PSMSG')) define('PSMSG', 'Additional PS Message: ');
?>
