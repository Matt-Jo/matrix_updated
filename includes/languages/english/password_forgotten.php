<?php
/*
 $Id: password_forgotten.php,v 1.2 2004/03/05 00:36:42 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'I\'ve Forgotten My Password!');

if (!defined('TEXT_NO_EMAIL_ADDRESS_FOUND')) define('TEXT_NO_EMAIL_ADDRESS_FOUND', 'Error: That E-Mail Address was not found in our records, please try again.');

if (!defined('EMAIL_PASSWORD_REMINDER_SUBJECT')) define('EMAIL_PASSWORD_REMINDER_SUBJECT', STORE_NAME.' - New Password');
if (!defined('EMAIL_PASSWORD_REMINDER_BODY')) define('EMAIL_PASSWORD_REMINDER_BODY', 'A new password was requested from '.$_SERVER['REMOTE_ADDR'].'.'."\n\n".'Your new password to \''.STORE_NAME.'\' is:'."\n\n".'	%s'."\n\n<a href=\"http://www.cablesandkits.com/account_password.php\">Login and change your password here</a>.");

if (!defined('SUCCESS_PASSWORD_SENT')) define('SUCCESS_PASSWORD_SENT', 'Success: A new password has been sent to your e-mail address.');
?>
