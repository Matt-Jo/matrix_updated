<?php
require(__DIR__.'/../includes/application_top.php');

if ($ipns = prepared_query::fetch('SELECT psc.stock_id, psc.stock_name, psc.stock_quantity, psc.serialized, COUNT(s.id) as serial_quantity FROM products_stock_control psc JOIN serials s ON psc.stock_id = s.ipn AND s.status IN (2, 3, 6) WHERE psc.serialized = 1 GROUP BY psc.stock_id, psc.stock_name, psc.stock_quantity, psc.serialized HAVING psc.stock_quantity != COUNT(s.id) ORDER BY psc.stock_name ASC')) {
	foreach ($ipns as $ipn) {
		prepared_query::execute('UPDATE products_stock_control SET stock_quantity = :serial_quantity WHERE stock_id = :stock_id', [':serial_quantity' => $ipn['serial_quantity'], ':stock_id' => $ipn['stock_id']]);
		prepared_query::execute('UPDATE products SET products_quantity = :serial_quantity WHERE p.stock_id = :stock_id', [':serial_quantity' => $ipn['serial_quantity'], ':stock_id' => $ipn['stock_id']]);
	}
}
echo 'done';
?>
