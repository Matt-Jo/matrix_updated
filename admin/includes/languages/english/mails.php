<?php
/*
 $Id: mail.php,v 1.8 2002/01/18 17:28:53 hpdl Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Send Email To Subscribers');

if (!defined('TEXT_SUBSCRIBER')) define('TEXT_SUBSCRIBER', 'Subscriber:');
if (!defined('TEXT_SUBJECT')) define('TEXT_SUBJECT', 'Subject:');
if (!defined('TEXT_FROM')) define('TEXT_FROM', 'From:');
if (!defined('TEXT_MESSAGE')) define('TEXT_MESSAGE', 'Message:');
if (!defined('TEXT_SELECT_SUBSCRIBER')) define('TEXT_SELECT_SUBSCRIBER', 'Select Subscriber');
if (!defined('TEXT_ALL_SUBSCRIBERS')) define('TEXT_ALL_SUBSCRIBERS', 'All Subscriber');
if (!defined('TEXT_NEWSLETTER_SUBSCRIBERS')) define('TEXT_NEWSLETTER_SUBSCRIBERS', 'To All Newsletter Subscribers');

if (!defined('NOTICE_EMAIL_SENT_TO')) define('NOTICE_EMAIL_SENT_TO', 'Notice: Email sent to: %s');

if (!defined('ERROR_NO_CUSTOMER_SELECTED')) define('ERROR_NO_CUSTOMER_SELECTED', 'Error: no customer was selected.');
// MaxiDVD Added Line For WYSIWYG HTML Area: BOF
if (!defined('TEXT_EMAIL_BUTTON_TEXT')) define('TEXT_EMAIL_BUTTON_TEXT', '<p><HR><b><font color="red">The Back Button has been DISABLE while HTML WYSIWG Editor is turned ON,</b></font> WHY? - Because if you click the back button to edit your HTML email, The PHP (php.ini - "Magic Quotes = On") will automatically add "\\\\\\\" backslashes everywhere Double Quotes " appear (HTML uses them in Links, Images and More) and this destorts the HTML and the pictures will dissapear once you submit the email again, If you turn OFF WYSIWYG Editor in Admin the HTML Ability of osCommerce is also turned OFF and the back button will re-appear. A fix for this HTML and PHP issue would be nice if someone knows a solution Iv\'e tried.<br><br><b>If you really need to Preview your emails before sending them, use the Preview Button located on the WYSIWYG Editor.<br><HR>');
if (!defined('TEXT_EMAIL_BUTTON_HTML')) define('TEXT_EMAIL_BUTTON_HTML', '<p><HR><b><font color="red">HTML is currently Disabled!</b></font><br><br>If you want to send HTML email, Enable WYSIWYG Editor for Email in: Admin-->Configuration-->WYSIWYG Editor-->Options<br>');
// MaxiDVD Added Line For WYSIWYG HTML Area: EOF
if (!defined('TEXT_EMAIL_BUTTON_HTML')) define('TEXT_EMAIL_BUTTON_HTML', '<p><HR><b><font color="red">The editor HTML is not at present validated!!</b></font><br><br>If you want to send a mail to HTML, to Validate the editor HTML: Admin-->Configuration-->Editeur HTML-->Options<br>');
if (!defined('NOTICE_EMAIL_SENT_TO')) define('NOTICE_EMAIL_SENT_TO', 'Information: email sent in: %s');
if (!defined('ERROR_NO_SUBSCRIBER_SELECTED')) define('ERROR_NO_SUBSCRIBER_SELECTED', 'Error: No Subscriber has been selected.');
?>
