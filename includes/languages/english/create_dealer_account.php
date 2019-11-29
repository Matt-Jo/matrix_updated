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

if (!defined('TEXT_ORIGIN_LOGIN')) define('TEXT_ORIGIN_LOGIN', '<font color="#FF0000"><small><b>NOTE:</b></font></small> If you already have an account, please <a href="%s"><u>login</u></a>.');

if (!defined('EMAIL_SUBJECT')) define('EMAIL_SUBJECT', 'Welcome to '.STORE_NAME);
if (!defined('EMAIL_GREET_MR')) define('EMAIL_GREET_MR', 'Dear Mr. %s,'."\n\n");
if (!defined('EMAIL_GREET_MS')) define('EMAIL_GREET_MS', 'Dear Ms. %s,'."\n\n");
if (!defined('EMAIL_GREET_NONE')) define('EMAIL_GREET_NONE', 'Dear %s'."\n\n");
if (!defined('EMAIL_TEXT')) define('EMAIL_TEXT', 'You can now take part in the <b>various services</b> we have to offer you. Some of these services include:'."\n\n".'<li><b>Permanent Cart</b> - Any products added to your online cart remain there until you remove them, or check them out.'."\n".'<li><b>Address Book</b> - We can now deliver your products to another address other than yours! This is perfect to send birthday gifts direct to the birthday-person themselves.'."\n".'<li><b>Order History</b> - View your history of purchases that you have made with us.'."\n".'<li><b>Products Reviews</b> - Share your opinions on products with our other customers.'."\n\n");
if (!defined('EMAIL_CONFIRMATION')) define('EMAIL_CONFIRMATION', 'Thank you for submitting your account information to our '.STORE_NAME."\n\n".'To finish your account setup please verify your e-mail address by clicking the link below: '."\n\n");
?>
