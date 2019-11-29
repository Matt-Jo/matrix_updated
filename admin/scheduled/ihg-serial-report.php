<?php
	require_once(__DIR__.'/../../includes/application_top.php');
	
	$ihg_report = fopen(__DIR__.'/../../customer-feeds/ihg/ihg-serial-report.csv', 'w');
	// prepare headers
	fputcsv($ihg_report, ['Product Model', 'Serials', 'Order Date']);

	$ihg_equipment = prepared_query::fetch('SELECT p.products_model, s.serial, o.date_purchased FROM serials s LEFT JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN orders o ON sh.order_id = o.orders_id LEFT JOIN products p ON s.ipn = p.stock_id WHERE o.customers_id = 136298 ORDER BY o.date_purchased DESC', cardinality::SET);
	
	//now create the body of the csv
	foreach ($ihg_equipment as $item) {
		fputcsv($ihg_report, [$item['products_model'], $item['serial'], $item['date_purchased']]);
	}
	
	fclose($ihg_report);
?>