<?php
/*
 $Id: login.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!empty($_GET['origin']) && defined('FILENAME_CHECKOUT_PAYMENT') && $_GET['origin'] == FILENAME_CHECKOUT_PAYMENT) {
 if (!defined('NAVBAR_TITLE')) define('NAVBAR_TITLE', 'Order');
 if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Ordering online is easy.');
 if (!defined('TEXT_STEP_BY_STEP')) define('TEXT_STEP_BY_STEP', 'We\'ll walk you through the process, step by step.');
} else {
 if (!defined('NAVBAR_TITLE')) define('NAVBAR_TITLE', 'Login');
 if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Welcome, Please Sign In');
 if (!defined('TEXT_STEP_BY_STEP')) define('TEXT_STEP_BY_STEP', ''); // should be empty
}

if (!defined('HEADING_RETURNING_ADMIN')) define('HEADING_RETURNING_ADMIN', 'Login Panel:');
if (!defined('HEADING_PASSWORD_FORGOTTEN')) define('HEADING_PASSWORD_FORGOTTEN', 'Password forgotten:');
if (!defined('TEXT_RETURNING_ADMIN')) define('TEXT_RETURNING_ADMIN', 'Staff only!');
if (!defined('ENTRY_EMAIL_ADDRESS')) define('ENTRY_EMAIL_ADDRESS', 'Login:');
if (!defined('ENTRY_PASSWORD')) define('ENTRY_PASSWORD', 'Password:');
if (!defined('ENTRY_FIRSTNAME')) define('ENTRY_FIRSTNAME', 'First Name:');
if (!defined('IMAGE_BUTTON_LOGIN')) define('IMAGE_BUTTON_LOGIN', 'Submit');

if (!defined('TEXT_PASSWORD_FORGOTTEN')) define('TEXT_PASSWORD_FORGOTTEN', 'Password forgotten?');

if (!defined('TEXT_LOGIN_ERROR')) define('TEXT_LOGIN_ERROR', '<font color="#ff0000"><b>ERROR:</b></font> Wrong username or password!');
if (!defined('TEXT_FORGOTTEN_ERROR')) define('TEXT_FORGOTTEN_ERROR', '<font color="#ff0000"><b>ERROR:</b></font> First name and password not match!');
if (!defined('TEXT_FORGOTTEN_FAIL')) define('TEXT_FORGOTTEN_FAIL', 'You have tried more than 3 times. For security reasons, please contact the Webmaster to get a new password.<br>&nbsp;<br>&nbsp;');
if (!defined('TEXT_FORGOTTEN_SUCCESS')) define('TEXT_FORGOTTEN_SUCCESS', 'The new password has been sent to your Email address. Please check your Email and click Back to login again.<br>&nbsp;<br>&nbsp;');

if (!defined('ADMIN_EMAIL_SUBJECT')) define('ADMIN_EMAIL_SUBJECT', 'New Password');
if (!defined('ADMIN_EMAIL_TEXT')) define('ADMIN_EMAIL_TEXT', 'Hi %s,'."\n\n".'You can access the admin panel with the following password. Once you accessed the admin, please change your password immediately!'."\n\n".'Website: %s'."\n".'Username: %s'."\n".'Password: %s'."\n\n".'Thanks!'."\n".'%s'."\n\n".'This is a system automated response, please do not reply, as your answer would be unread!');
?>
