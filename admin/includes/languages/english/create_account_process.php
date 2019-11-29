<?php
/*
 $Id: create_account_process.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/
if (!defined('NAVBAR_TITLE')) define('NAVBAR_TITLE', 'Create an Account');
if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Account Information');
if (!defined('HEADING_NEW')) define('HEADING_NEW', 'Order Process');
if (!defined('NAVBAR_NEW_TITLE')) define('NAVBAR_NEW_TITLE', 'Order Process');

if (!defined('EMAIL_SUBJECT')) define('EMAIL_SUBJECT', 'Welcome to '.STORE_NAME);
if (!defined('EMAIL_GREET_MR')) define('EMAIL_GREET_MR', 'Dear Mr. '.stripslashes($_POST['lastname']).','."\n\n");
if (!defined('EMAIL_GREET_MS')) define('EMAIL_GREET_MS', 'Dear Ms. '.stripslashes($_POST['lastname']).','."\n\n");
if (!defined('EMAIL_GREET_NONE')) define('EMAIL_GREET_NONE', 'Dear '.stripslashes($_POST['firstname']).','."\n\n");
if (!defined('EMAIL_TEXT')) define('EMAIL_TEXT', 'You can now take part in the <b>various services</b> we have to offer you. Some of these services include:'."\n\n".'<li><b>Permanent Cart</b> - Any products added to your online cart remain there until you remove them, or check them out.'."\n".'<li><b>Address Book</b> - We can now deliver your products to another address other than yours! This is perfect to send birthday gifts direct to the birthday-person themselves.'."\n".'<li><b>Order History</b> - View your history of purchases that you have made with us.'."\n".'<li><b>Products Reviews</b> - Share your opinions on products with our other customers.'."\n\n");
define('EMAIL_PASS_1', 'Your password for this account is ');
define('EMAIL_PASS_2', ', keep it in a safe place. (Please note: Your password is case sensitive.)');


?>
