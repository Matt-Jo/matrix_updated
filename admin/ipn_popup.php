<?php
require('includes/application_top.php');
$ipn = $_GET['ipn'];

$result = prepared_query::fetch("SELECT YEAR(o.date_purchased) AS years, MONTHNAME(o.date_purchased) AS months, COUNT(o.orders_id) AS num_orders, SUM(op.products_quantity) AS units_sold, FORMAT(AVG(op.final_price), 2) AS avg_unit_price, DATE_FORMAT(products_date_added, '%c/%e/%Y') AS date_added FROM products p LEFT JOIN orders_products op ON op.products_id = p.products_id LEFT JOIN orders o ON o.orders_id = op.orders_id AND o.orders_status IN (1, 2, 3, 5, 7, 8, 10, 11, 12) WHERE p.stock_id = :stock_id AND UNIX_TIMESTAMP(o.date_purchased) - UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH)) > 0 GROUP BY p.stock_id, YEAR(o.date_purchased), MONTH(o.date_purchased)", cardinality::SET, [':stock_id' => $ipn]); ?>
<style>
	#popup-table { font-size: 10px; text-align: center; }
	#popup-table tr { margin: 0px; }
	#popup-table tr td p { width: 80px; margin-top: 0px; margin-bottom: 0px; }
</style>
<center><p>IPN Creation Date: <?=$row['date_added']?></p></center>
<table id="popup-table">
	<tr>
		<td><p>Year</p></td>
		<td><p>Month</p></td>
		<td><p>Total Orders</p></td>
		<td><p>Unit Sold</p></td>
		<td><p>Avg Per Unit Price</p></td>
	</tr>
	<?php foreach ($result as $i => $row) { ?>
	<tr <?= $i%2==1?'bgColor="#D8D8D8"'; ?>>
		<td><p><?= $row['years']; ?></p></td>
		<td><p><?= $row['months']; ?></p></td>
		<td><p><?= $row['num_orders']; ?></p></td>
		<td><p><?= $row['units_sold']; ?></p></td>
		<td><p>$<?= $row['avg_unit_price']; ?></p></td>
	</tr>
	<?php } ?>
</table>
<?php $result = prepared_query::fetch("SELECT psc.dealer_price, psc.stock_price, (SELECT DATE(MAX(pscch.change_date)) FROM products_stock_control_change_history pscch WHERE pscch.stock_id = psc.stock_id AND pscch.type_id = 3) as dealer_change, (SELECT DATE(MAX(pscch.change_date)) FROM products_stock_control_change_history pscch WHERE pscch.stock_id = psc.stock_id AND pscch.type_id = 2) as stock_change FROM products_stock_control psc WHERE psc.stock_id = :stock_id", cardinality::SET, [':stock_id' => $ipn]); ?>
<table id="popup-table" width="100%">
	<tr>
		<td><hr></td>
	</tr>
</table>
<table id="popup-table" width="100%">
	<tr>
		<td><p>Customer Type</p></td>
		<td><p>Price</p></td>
		<td><p>Last Date Change</p></td>
	</tr>
	<?php foreach ($result as $i => $row) { ?>
	<tr>
		<td><p>Stock</p></td>
		<td><p>$<?= $row['stock_price']; ?></p></td>
		<td><p><?= $row['stock_change']; ?></p></td>
	</tr>
	<tr>
		<td><p>Dealer</p></td>
		<td><p>$<?= $row['dealer_price']; ?></p></td>
		<td><p><?= $row['dealer_change']; ?></p></td>
	</tr>
	<?php } ?>
</table>
<?php $result = prepared_query::fetch("SELECT DATE(po.creation_date) as creation_date, pop.cost FROM purchase_orders po, purchase_order_products pop WHERE pop.ipn_id = :stock_id AND pop.purchase_order_id = po.id ORDER BY po.id DESC LIMIT 1", cardinality::SET, [':stock_id' => $ipn]);
$average_cost = prepared_query::fetch("SELECT SUM(pop.cost) as avg_cost, COUNT(po.id) as po_count FROM purchase_order_products pop, purchase_orders po WHERE pop.ipn_id = :stock_id AND pop.purchase_order_id = po.id AND UNIX_TIMESTAMP(po.creation_date) - UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH)) > 0", cardinality::ROW, [':stock_id' => $ipn]);

if ($average_cost['po_count'] == 0) $average_cost = 'N/A';
else $average_cost = CK\text::monetize($average_cost['avg_cost'] / $average_cost['po_count']); ?>
<table id="popup-table" width="100%">
	<tr>
		<td><hr></td>
	</tr>
</table>
<table id="popup-table" width="100%">
	<tr>
		<td><p>Last PO Date</p></td>
		<td><p>Last PO Price</p></td>
		<td><p>Avg PO Price</p></td>
	</tr>
	<?php foreach ($result as $i => $row) { ?>
	<tr>
		<td><p><?= $row['creation_date']; ?></p></td>
		<td><p><?= $row['cost']; ?></p></td>
		<td><p><?= $average_cost; ?></p></td>
	</tr>
	<?php } ?>
</table>
