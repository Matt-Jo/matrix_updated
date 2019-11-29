<?php
/*
 $Id: ipn.lng.php,v 2.6a 2004/07/14 devosc Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 DevosC, Developing open source Code
 http://www.devosc.com

 Copyright (c) 2002 osCommerce
 Copyright (c) 2004 DevosC.com

 Released under the GNU General Public License
*/

 if (!defined('UNKNOWN_TXN_TYPE')) define('UNKNOWN_TXN_TYPE', 'Unknown Transaction Type');
 if (!defined('UNKNOWN_TXN_TYPE_MSG')) define('UNKNOWN_TXN_TYPE_MSG', 'An unknown transaction (%s) occurred from '.$_SERVER['REMOTE_ADDR']."\nAre you running any tests?\n\n");
 if (!defined('UNKNOWN_POST')) define('UNKNOWN_POST', 'Unknown Post');
 if (!defined('UNKNOWN_POST_MSG')) define('UNKNOWN_POST_MSG', "An unknown POST from %s was received.\nAre you running any tests?\n\n");
 if (!defined('EMAIL_SEPARATOR')) define('EMAIL_SEPARATOR', "------------------------------------------------------");
 if (!defined('RESPONSE_VERIFIED')) define('RESPONSE_VERIFIED', 'Verified');
 if (!defined('RESPONSE_MSG')) define('RESPONSE_MSG', "Connection Type\n".EMAIL_SEPARATOR."\ncurl= %s, socket= %s, domain= %s, port= %s \n\nPayPal Response\n".EMAIL_SEPARATOR."\n%s \n\n");
 if (!defined('RESPONSE_INVALID')) define('RESPONSE_INVALID', 'Invalid PayPal Response');
 if (!defined('RESPONSE_UNKNOWN')) define('RESPONSE_UNKNOWN', 'Unknown Verfication');
 if (!defined('EMAIL_RECEIVER')) define('EMAIL_RECEIVER', 'Email and Business ID config');
 if (!defined('EMAIL_RECEIVER_MSG')) define('EMAIL_RECEIVER_MSG', "Store Configuration Settings\nPrimary PayPal Email Address: %s\nBusiness ID: %s\n".EMAIL_SEPARATOR."\nPayPal Configuration Settings\nPrimary PayPal Email Address: %s\nBusiness ID: %s\n\n");
 if (!defined('EMAIL_RECEIVER_ERROR_MSG')) define('EMAIL_RECEIVER_ERROR_MSG', "Store Configuration Settings\nPrimary PayPal Email Address: %s\nBusiness ID: %s\n".EMAIL_SEPARATOR."\nPayPal Configuration Settings\nPrimary PayPal Email Address: %s\nBusiness ID: %s\n\nPayPal Transaction ID: %s\n\n");
 if (!defined('TXN_DUPLICATE')) define('TXN_DUPLICATE', 'Duplicate Transaction');
 if (!defined('TXN_DUPLICATE_MSG')) define('TXN_DUPLICATE_MSG', "A duplicate IPN transaction (%s) has been received.\nPlease check your PayPal Account\n\n");
 if (!defined('IPN_TXN_INSERT')) define('IPN_TXN_INSERT', "IPN INSERTED");
 if (!defined('IPN_TXN_INSERT_MSG')) define('IPN_TXN_INSERT_MSG', "IPN %s has been inserted\n\n");
 if (!defined('CHECK_CURRENCY')) define('CHECK_CURRENCY', 'Validate Currency');
 if (!defined('CHECK_CURRENCY_MSG')) define('CHECK_CURRENCY_MSG', "Incorrect Currency\nPayPal: %s\nosC: %s\n\n");
 if (!defined('CHECK_TXN_SIGNATURE')) define('CHECK_TXN_SIGNATURE', 'Validate PayPal_Shopping_Cart Transaction Signature');
 if (!defined('CHECK_TXN_SIGNATURE_MSG')) define('CHECK_TXN_SIGNATURE_MSG', "Incorrect Signature\nPayPal: %s\nosC: %s\n\n");
 if (!defined('CHECK_TOTAL')) define('CHECK_TOTAL', 'Validate Total Transaction Amount');
 if (!defined('CHECK_TOTAL_MSG')) define('CHECK_TOTAL_MSG', "Incorrect Total\nPayPal: %s\nSession: %s\n\n");
 if (!defined('DEBUG')) define('DEBUG', 'Debug');
 if (!defined('DEBUG_MSG')) define('DEBUG_MSG', "\nOriginal Post\n".EMAIL_SEPARATOR."\n%s\n\n\nReconstructed Post\n".EMAIL_SEPARATOR."\n%s\n\n");
 if (!defined('PAYMENT_SEND_MONEY_DESCRIPTION')) define('PAYMENT_SEND_MONEY_DESCRIPTION', 'Money Received');
 if (!defined('PAYMENT_SEND_MONEY_DESCRIPTION_MSG')) define('PAYMENT_SEND_MONEY_DESCRIPTION_MSG', "You have received a payment of %s %s \n".EMAIL_SEPARATOR."\nThis payment was sent by someone from the PayPal website, using the Send Money tab\n\n");
 if (!defined('TEST_INCOMPLETE')) define('TEST_INCOMPLETE', 'Invalid Test');
 if (!defined('TEST_INCOMPLETE_MSG')) define('TEST_INCOMPLETE_MSG', "An error has occured, mostly likely because the Custom field in the IPN Test Panel did not have a valid transaction id.\n\n\n");
 if (!defined('HTTP_ERROR')) define('HTTP_ERROR', 'HTTP Error');
 if (!defined('HTTP_ERROR_MSG')) define('HTTP_ERROR_MSG', "An HTTP Error occured during authentication\n".EMAIL_SEPARATOR."\ncurl= %s, socket= %s, domain= %s, port= %s\n\n");
?>
