<?php
/*
 $Id: create_account.php,v 1.2 2004/03/05 00:36:42 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

if (!defined('NAVBAR_TITLE')) define('NAVBAR_TITLE', 'Create an Account');

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'My Account Information');

if (!defined('TEXT_ORIGIN_LOGIN')) define('TEXT_ORIGIN_LOGIN', '<font color="#e62345"><small><b>NOTE:</b></font></small> If you already have an account, please <a href="%s"><u>login</u></a>.');

if (!defined('EMAIL_SUBJECT')) define('EMAIL_SUBJECT', 'Welcome to '.STORE_NAME);
if (!defined('EMAIL_GREET_MR')) define('EMAIL_GREET_MR', 'Hi %s,'."!"."\n\n");
if (!defined('EMAIL_GREET_MS')) define('EMAIL_GREET_MS', 'Hi %s,'."!"."\n\n");
if (!defined('EMAIL_GREET_NONE')) define('EMAIL_GREET_NONE', 'Hi %s'."!"."\n\n");
if (!defined('EMAIL_TEXT')) define('EMAIL_TEXT', 'This is the login information you entered, hang onto it for future reference.'."\n\n".'Username: '.$email_address.' <br \>Password: *******	'."\n\n");
if (!defined('EMAIL_CONFIRMATION')) define('EMAIL_CONFIRMATION', 'Thank you for submitting your account information to our '.STORE_NAME."\n\n".'To finish your account setup please verify your e-mail address by clicking the link below: '."\n\n");

//---PayPal WPP Modification START ---//
if (!defined('EMAIL_EC_ACCOUNT_INFORMATION')) define('EMAIL_EC_ACCOUNT_INFORMATION', 'Thank you for using PayPal Express Checkout! To make your next visit with us even smoother, an account has been automatically created for you. Your new login information has been included below:'."\n\n");
//---PayPal WPP Modification END ---//
?>
