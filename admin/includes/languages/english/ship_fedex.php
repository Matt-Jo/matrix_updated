<?php

	if (!defined('HEADING_TITLE')) define('HEADING_TITLE','Options for FedEx Shipment');
	if (!defined('IMAGE_SUBMIT')) define('IMAGE_SUBMIT','Submit');
	if (!defined('ORDER_HISTORY_DELIVERED')) define('ORDER_HISTORY_DELIVERED','Scheduled shipment, tracking number ');
	if (!defined('ORDER_HISTORY_CANCELLED')) define('ORDER_HISTORY_CANCELLED','Cancelled shipment');
	if (!defined('NO_ORDER_NUMBER_ERROR')) define('NO_ORDER_NUMBER_ERROR','No order number specified!');
	if (!defined('ERROR_FEDEX_QUOTES_NOT_INSTALLED')) define('ERROR_FEDEX_QUOTES_NOT_INSTALLED','Could not find a FedEx account number. Is FedEx RealTime Quotes installed and configured?');
	if (!defined('SHIPMENT_REQUEST_DATA')) define('SHIPMENT_REQUEST_DATA','Shipment request data, package number ');
	if (!defined('MANIFEST_DATA')) define('MANIFEST_DATA','Manifest data, package number ');
	if (!defined('RUNNING_IN_DEBUG')) define('RUNNING_IN_DEBUG','Running in debug mode, no ship request made');
	if (!defined('ERROR_NO_ORDER_SPECIFIED')) define('ERROR_NO_ORDER_SPECIFIED','ERROR: There is no order specified!');
	if (!defined('ORDER_NUMBER')) define('ORDER_NUMBER','Order number ');
	if (!defined('COULD_NOT_DELETE_ENTRIES')) define('COULD_NOT_DELETE_ENTRIES','Could not delete manifest entries.');
	if (!defined('ERROR')) define('ERROR','ERROR: ');
	if (!defined('ENTER_PACKAGE_WEIGHT')) define('ENTER_PACKAGE_WEIGHT','You must enter a package weight.');
	if (!defined('ENTER_NUMBER_PACKAGES')) define('ENTER_NUMBER_PACKAGES','You must enter the number of packages.');

	if (!defined('EMAIL_SEPARATOR')) define('EMAIL_SEPARATOR', '------------------------------------------------------');
	if (!defined('EMAIL_TEXT_SUBJECT')) define('EMAIL_TEXT_SUBJECT', 'Order Update');
	if (!defined('EMAIL_TEXT_ORDER_NUMBER')) define('EMAIL_TEXT_ORDER_NUMBER', 'Order Number:');
	if (!defined('EMAIL_TEXT_INVOICE_URL')) define('EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice:');
	if (!defined('EMAIL_TEXT_DATE_ORDERED')) define('EMAIL_TEXT_DATE_ORDERED', 'Date Ordered:');
	if (!defined('EMAIL_TEXT_STATUS_UPDATE')) define('EMAIL_TEXT_STATUS_UPDATE', 'Your order status is '.'%s'."\n\n".'Please reply to this email if you have any questions.'."\n");
	if (!defined('EMAIL_TEXT_COMMENTS_UPDATE')) define('EMAIL_TEXT_COMMENTS_UPDATE', 'Comments: '."%s\n");
	if (!defined('EMAIL_TEXT_TRACKING_NUMBER')) define('EMAIL_TEXT_TRACKING_NUMBER', 'You can track your packages by clicking the link below.');
	define('URL_TO_TRACK1', 'http://www.fedex.com/cgi-bin/tracking?action=track&tracknumbers=');

// form field titles
	if (!defined('NUMBER_OF_PACKAGES')) define('NUMBER_OF_PACKAGES','Number of Packages:');
	if (!defined('OVERSIZED')) define('OVERSIZED','Oversized?');
	if (!defined('PACKAGING_TYPE')) define('PACKAGING_TYPE','Packaging Type ("other" for ground shipments):');
	if (!defined('TYPE_OF_SERVICE')) define('TYPE_OF_SERVICE','Type of Service:');
	if (!defined('PAYMENT_TYPE')) define('PAYMENT_TYPE','Payment Type:');
	if (!defined('DROPOFF_TYPE')) define('DROPOFF_TYPE','Dropoff Type:');
	if (!defined('PICKUP_DATE')) define('PICKUP_DATE','Pickup date (yyyymmdd):');

	if (!defined('TOTAL_WEIGHT')) define('TOTAL_WEIGHT','Total weight for all packages:');
	if (!defined('PACKAGE_WEIGHT')) define('PACKAGE_WEIGHT','Package Weight:');

?>