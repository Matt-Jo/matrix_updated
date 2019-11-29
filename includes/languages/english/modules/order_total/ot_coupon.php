<?php
/*
 $Id: ot_coupon.php,v 1.3 2004/03/09 18:56:37 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

 if (!defined('MODULE_ORDER_TOTAL_COUPON_TITLE')) define('MODULE_ORDER_TOTAL_COUPON_TITLE', 'Discount Coupons');
 if (!defined('MODULE_ORDER_TOTAL_COUPON_HEADER')) define('MODULE_ORDER_TOTAL_COUPON_HEADER', 'Gift Vouchers/Discount Coupons');
 if (!defined('MODULE_ORDER_TOTAL_COUPON_DESCRIPTION')) define('MODULE_ORDER_TOTAL_COUPON_DESCRIPTION', 'Discount Coupon');
 if (!defined('SHIPPING_NOT_INCLUDED')) define('SHIPPING_NOT_INCLUDED', ' [Shipping not included]');
 if (!defined('TAX_NOT_INCLUDED')) define('TAX_NOT_INCLUDED', ' [Tax not included]');
 if (!defined('MODULE_ORDER_TOTAL_COUPON_USER_PROMPT')) define('MODULE_ORDER_TOTAL_COUPON_USER_PROMPT', '');
 if (!defined('ERROR_NO_INVALID_REDEEM_COUPON')) define('ERROR_NO_INVALID_REDEEM_COUPON', 'Invalid Coupon Code');
 if (!defined('ERROR_INVALID_STARTDATE_COUPON')) define('ERROR_INVALID_STARTDATE_COUPON', 'This coupon is not available yet');
 if (!defined('ERROR_INVALID_FINISDATE_COUPON')) define('ERROR_INVALID_FINISDATE_COUPON', 'This coupon has expired');
 if (!defined('ERROR_INVALID_USES_COUPON')) define('ERROR_INVALID_USES_COUPON', 'This coupon could only be used ');
 if (!defined('TIMES')) define('TIMES', ' times.');
 if (!defined('ERROR_INVALID_USES_USER_COUPON')) define('ERROR_INVALID_USES_USER_COUPON', 'You have used the coupon the maximum number of times allowed per customer.');
 if (!defined('REDEEMED_COUPON')) define('REDEEMED_COUPON', 'a coupon worth ');
 if (!defined('REDEEMED_MIN_ORDER')) define('REDEEMED_MIN_ORDER', 'on orders over ');
 if (!defined('REDEEMED_RESTRICTIONS')) define('REDEEMED_RESTRICTIONS', ' [Product-Category restrictions apply]');
 if (!defined('TEXT_ENTER_COUPON_CODE')) define('TEXT_ENTER_COUPON_CODE', 'Enter Coupon Code&nbsp;&nbsp;');
?>
