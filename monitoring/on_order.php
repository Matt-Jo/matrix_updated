<?php
/*
	$Id: account_edit.php,v 1.1.1.1 2004/03/04 23:37:53 ccwjr Exp $

	osCommerce, Open Source E-Commerce Solutions
	http://www.oscommerce.com

	Copyright (c) 2003 osCommerce

	Released under the GNU General Public License
*/

chdir('..');
require('includes/application_top.php');

$audits = prepared_query::fetch('SELECT psc.stock_id, psc.stock_name, psc.on_order, IFNULL(pop.qty_ordered, 0) as qty_ordered, IFNULL(porp.qty_received, 0) as qty_received, GREATEST(IFNULL(pop.qty_ordered, 0) - IFNULL(porp.qty_received, 0), 0) as qty_outstanding, ABS(GREATEST(IFNULL(pop.qty_ordered, 0) - IFNULL(porp.qty_received, 0), 0) - psc.on_order) as difference FROM products_stock_control psc LEFT JOIN (SELECT pop.ipn_id as stock_id, SUM(pop.quantity) as qty_ordered FROM purchase_orders po JOIN purchase_order_products pop ON po.id = pop.purchase_order_id WHERE po.status IN (1, 2) GROUP BY pop.ipn_id) pop ON psc.stock_id = pop.stock_id LEFT JOIN (SELECT pop.ipn_id as stock_id, SUM(porp.quantity_received) as qty_received FROM purchase_orders po JOIN purchase_order_products pop ON po.id = pop.purchase_order_id JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id WHERE po.status IN (1, 2) GROUP BY pop.ipn_id) porp ON psc.stock_id = porp.stock_id WHERE ABS(GREATEST(IFNULL(pop.qty_ordered, 0) - IFNULL(porp.qty_received, 0), 0) - psc.on_order) != 0 GROUP BY psc.stock_id ORDER BY difference', cardinality::SET);

var_dump($audits);

foreach ($audits as $audit) {
	// we're not going to worry about tracking down the issue, and we trust this number to be correct, so just fix it instead of alerting us and requiring a manual fix
	prepared_query::execute('UPDATE products_stock_control SET on_order = ? WHERE stock_id = ?', array($audit['qty_outstanding'], $audit['stock_id']));
}

echo 'done';
?>
