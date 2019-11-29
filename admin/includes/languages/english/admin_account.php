<?php
/*
 $Id: admin_account.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Admin Account');

if (!defined('TABLE_HEADING_ACCOUNT')) define('TABLE_HEADING_ACCOUNT', 'My Account');

if (!defined('TEXT_INFO_FULLNAME')) define('TEXT_INFO_FULLNAME', '<b>Name: </b>');
if (!defined('TEXT_INFO_FIRSTNAME')) define('TEXT_INFO_FIRSTNAME', '<b>Firstname: </b>');
if (!defined('TEXT_INFO_LASTNAME')) define('TEXT_INFO_LASTNAME', '<b>Lastname: </b>');
if (!defined('TEXT_INFO_EMAIL')) define('TEXT_INFO_EMAIL', '<b>Email Address: </b>');
if (!defined('TEXT_INFO_PASSWORD')) define('TEXT_INFO_PASSWORD', '<b>Password: </b>');
if (!defined('TEXT_INFO_PASSWORD_HIDDEN')) define('TEXT_INFO_PASSWORD_HIDDEN', '-Hidden-');
if (!defined('TEXT_INFO_PASSWORD_CONFIRM')) define('TEXT_INFO_PASSWORD_CONFIRM', '<b>Confirm Password: </b>');
if (!defined('TEXT_INFO_CREATED')) define('TEXT_INFO_CREATED', '<b>Account Created: </b>');
if (!defined('TEXT_INFO_LOGDATE')) define('TEXT_INFO_LOGDATE', '<b>Last Access: </b>');
if (!defined('TEXT_INFO_LOGNUM')) define('TEXT_INFO_LOGNUM', '<b>Log Number: </b>');
if (!defined('TEXT_INFO_GROUP')) define('TEXT_INFO_GROUP', '<b>Group Level: </b>');
if (!defined('TEXT_INFO_ERROR')) define('TEXT_INFO_ERROR', '<font color="red">Email address has already been used! Please try again.</font>');
if (!defined('TEXT_INFO_MODIFIED')) define('TEXT_INFO_MODIFIED', 'Modified: ');

if (!defined('TEXT_INFO_HEADING_DEFAULT')) define('TEXT_INFO_HEADING_DEFAULT', 'Edit Account ');
if (!defined('TEXT_INFO_HEADING_CONFIRM_PASSWORD')) define('TEXT_INFO_HEADING_CONFIRM_PASSWORD', 'Password Confirmation ');
if (!defined('TEXT_INFO_INTRO_CONFIRM_PASSWORD')) define('TEXT_INFO_INTRO_CONFIRM_PASSWORD', 'Password:');
if (!defined('TEXT_INFO_INTRO_CONFIRM_PASSWORD_ERROR')) define('TEXT_INFO_INTRO_CONFIRM_PASSWORD_ERROR', '<font color="red"><b>ERROR:</b> wrong password!</font>');
if (!defined('TEXT_INFO_INTRO_DEFAULT')) define('TEXT_INFO_INTRO_DEFAULT', 'Click <b>edit button</b> below to change your account.');
if (!defined('TEXT_INFO_INTRO_DEFAULT_FIRST_TIME')) define('TEXT_INFO_INTRO_DEFAULT_FIRST_TIME', '<br><b>WARNING:</b><br>Hello <b>%s</b>, you just come here for the first time. We recommend you to change your password!');
if (!defined('TEXT_INFO_INTRO_DEFAULT_FIRST')) define('TEXT_INFO_INTRO_DEFAULT_FIRST', '<br><b>WARNING:</b><br>Hello <b>%s</b>, we recommend you to change your email (<font color="red">admin@localhost</font>) and password!');
if (!defined('TEXT_INFO_INTRO_EDIT_PROCESS')) define('TEXT_INFO_INTRO_EDIT_PROCESS', 'All fields are required. Click save to submit.');

if (!defined('JS_ALERT_FIRSTNAME')) define('JS_ALERT_FIRSTNAME',		'- Required: Firstname \n');
if (!defined('JS_ALERT_LASTNAME')) define('JS_ALERT_LASTNAME',		'- Required: Lastname \n');
if (!defined('JS_ALERT_EMAIL')) define('JS_ALERT_EMAIL',			'- Required: Email address \n');
if (!defined('JS_ALERT_PASSWORD')) define('JS_ALERT_PASSWORD',		'- Required: Password \n');
if (!defined('JS_ALERT_FIRSTNAME_LENGTH')) define('JS_ALERT_FIRSTNAME_LENGTH', '- Firstname length must be over ');
if (!defined('JS_ALERT_LASTNAME_LENGTH')) define('JS_ALERT_LASTNAME_LENGTH', '- Lastname length must be over ');
if (!defined('JS_ALERT_PASSWORD_LENGTH')) define('JS_ALERT_PASSWORD_LENGTH', '- Password length must be over ');
if (!defined('JS_ALERT_EMAIL_FORMAT')) define('JS_ALERT_EMAIL_FORMAT',	'- Email address format is invalid! \n');
if (!defined('JS_ALERT_EMAIL_USED')) define('JS_ALERT_EMAIL_USED',		'- Email address has already been used! \n');
if (!defined('JS_ALERT_PASSWORD_CONFIRM')) define('JS_ALERT_PASSWORD_CONFIRM', '- Miss typing in Password Confirmation field! \n');

if (!defined('ADMIN_EMAIL_SUBJECT')) define('ADMIN_EMAIL_SUBJECT', 'Personal Information Change');
if (!defined('ADMIN_EMAIL_TEXT')) define('ADMIN_EMAIL_TEXT', 'Hello %s,'."\n\n".'Your personal information, perhaps including your password, has been changed. If this was done without your knowledge or consent please contact the administrator immediatly!'."\n\n".'Website : %s'."\n".'Username: %s'."\n".'Password: %s'."\n\n".'Thanks!'."\n".'%s'."\n\n".'This is a system automated response, please do not reply, as it would be unread!');
?>
