<?php
$skip_check = TRUE;
require_once('includes/application_top.php');

$po_number = $_GET['po_number'];

$po_number_count = prepared_query::fetch("select count(1) as total from purchase_orders where purchase_order_number = :po_number", cardinality::SINGLE, [':po_number' => $po_number]);

if ($po_number_count > 0) {
?>
({
	'result': true
})
<?php
}
else {
?>
({
	'result': false
})
<?php
}
?>
