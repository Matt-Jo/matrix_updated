<?php
/*
 $Id: mail.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Send Email To Customers');

if (!defined('TEXT_CUSTOMER')) define('TEXT_CUSTOMER', 'Customer:');
if (!defined('TEXT_SUBJECT')) define('TEXT_SUBJECT', 'Subject:');
if (!defined('TEXT_FROM')) define('TEXT_FROM', 'From:');
if (!defined('TEXT_MESSAGE')) define('TEXT_MESSAGE', 'Message:');
if (!defined('TEXT_SELECT_CUSTOMER')) define('TEXT_SELECT_CUSTOMER', 'Select Customer');
if (!defined('TEXT_ALL_CUSTOMERS')) define('TEXT_ALL_CUSTOMERS', 'All Customers');
if (!defined('TEXT_NEWSLETTER_CUSTOMERS')) define('TEXT_NEWSLETTER_CUSTOMERS', 'To All Newsletter Subscribers');

if (!defined('NOTICE_EMAIL_SENT_TO')) define('NOTICE_EMAIL_SENT_TO', 'Notice: Email sent to: %s');
if (!defined('ERROR_NO_CUSTOMER_SELECTED')) define('ERROR_NO_CUSTOMER_SELECTED', 'Error: No customer has been selected.');
// MaxiDVD Added Line For WYSIWYG HTML Area: BOF
if (!defined('TEXT_EMAIL_BUTTON_TEXT')) define('TEXT_EMAIL_BUTTON_TEXT', '<p><HR><b><font color="red">The Back Button has been DISABLED while HTML WYSIWG Editor is turned ON.</b></font> WHY? - Because if you click the back button to edit your HTML email, the PHP (php.ini - "Magic Quotes = On") will automatically add "\\\\\\\" backslashes everywhere Double Quotes " appear (HTML uses them in Links, Images and More) and this distorts the HTML, the pictures will dissapear once you submit the email again. If you turn OFF WYSIWYG Editor in Admin, the HTML Ability of osCommerce is also turned OFF and the back button will re-appear. A fix for this HTML and PHP issue would be nice if someone knows a solution.<br><br><b>If you really need to Preview your emails before sending them, use the Preview Button located on the WYSIWYG Editor.<br><HR>');
if (!defined('TEXT_EMAIL_BUTTON_HTML')) define('TEXT_EMAIL_BUTTON_HTML', '<p><HR><b><font color="red">HTML is currently Disabled!</b></font><br><br>If you want to send HTML email, Enable WYSIWYG Editor for Email in: Admin-->Configuration-->WYSIWYG Editor-->Options<br>');
// MaxiDVD Added Line For WYSIWYG HTML Area: EOF
?>
