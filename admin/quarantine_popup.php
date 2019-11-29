<?php
require('includes/application_top.php');
$ipn = $_GET['ipn'];

$quarantines = prepared_query::fetch("SELECT * FROM inventory_hold ih LEFT JOIN inventory_hold_reason ihr ON ih.reason_id = ihr.id where ih.stock_id = :stock_id", cardinality::SET, [':stock_id' => $ipn]);

foreach ($quarantines as $quarantine) {
	echo 'Reason: ';
	echo $quarantine['description'].'<br>';
	echo 'Notes: ';
	echo $quarantine['notes'].'<br><br>';
}
?>