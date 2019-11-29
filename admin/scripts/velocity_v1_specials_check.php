<?php
/**************
 * Velocity V1 consists of two automated steps based on an email from Crhis - MMD 6/6/13
 * Step 1 - for all serials 60 days old and older we will create a special 20% off the stock price
 *	for the entire quantity of serials 60 days old and older. The details of how this works need
 *	to be discussed further, but for the time being it will work as so.
 *	1.	retrieve a list of all serialized IPNs and the number of serials greater than 60 days old
 *	2.	for each IPN in that list we will retrieve any existing special and apply the following algorithm
 *		a.	If no special exists, we will create a new one at 20% off the stock price and for the number
 *			serials older than 60 days.
 *		b.	Otherwise, if a special does exist, we update the special to reflect the quantity of
 *			serials older than 60 days and the lesser of the current special price or 20% off the
 *			stock price
 * Step 2 - for all serials 90 days old and older - Trigger an auction at 10% below cost rounded up (use the
 *	lowest of cost/current sale price). Use 40% off of stock price if cost is $0.00. This will be
 *	implemented in the [shall not be named] autolister.
 */

require_once(__DIR__.'/../../includes/application_top.php');
require_once(__DIR__.'/../../includes/functions/specials.php');

$email_output = "<h2>Following is a list of specials where the available quantity is less than 1 or the available quantity is less than the special cutoff quantity. These specials have been automatically canceled.</h2><table><tr><th>Product Model</th><th>IPN</th><th>Available Qty</th><th>Special Cutoff Qty</th></th>";

$ipn_query = prepared_query::fetch("select p.products_model, p.products_id,
	s.specials_qty,
	psc.stock_name,
	(if(psc.serialized = '0', psc.stock_quantity, (select count(1) from serials s1 where s1.ipn = psc.stock_id and s1.status in (2,3,6))) -
	ifnull((select sum(op.products_quantity) as total from orders o, orders_products op, products p2 where o.orders_id = op.orders_id and (op.products_id = p2.products_id or ((op.products_id - p2.products_id) = 0)) and o.orders_status in (1, 2, 5, 7, 8, 10, 11, 12) and p2.stock_id = psc.stock_id) ,0) -
	if (psc.serialized = '0', ifnull((SELECT SUM(ih.quantity) AS on_hold FROM inventory_hold ih WHERE ih.stock_id = psc.stock_id ),0),
	ifnull((select count(1) as on_hold from serials s2 where s2.status = 6 and s2.ipn = psc.stock_id),0))) as available_quantity

from specials s,
	products p,
	products_stock_control psc

where s.products_id = p.products_id and
	p.stock_id = psc.stock_id and
	s.status = 1 and
	(s.expires_date > now() or s.expires_date IS NULL) and
	s.specials_qty != '999999'

group by s.specials_id

having available_quantity < 1 or available_quantity <= s.specials_qty", cardinality::SET);
foreach ($ipn_query as $unused => $row) {
	tep_check_and_expire_specials($row['products_id'], $row['available_quantity']);
	$email_output .= "<tr><td>".$row['products_model']."</td><td>".$row['stock_name']."</td><td>".$row['available_quantity']."</td><td>".$row['specials_qty']."</td></tr>";
}

$email_output .= "</table>";

exit();
?>
