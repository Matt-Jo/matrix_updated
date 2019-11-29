<?php
require('includes/application_top.php');

$serial_input = $_POST['serial'];
$ipn = $_POST['ipn'];

$serials = prepared_query::fetch('select s.serial from serials s, serials_history sh where s.serial like :serial and s.ipn = :ipn and sh.order_id=0 AND s.id = sh.serial_id AND sh.entered_date = (select max(entered_date) from serials_history where serial_id = s.id)', cardinality::COLUMN, [':serial' => $serial_input.'%', ':ipn' => $ipn]);

echo "<ul class='$serial_query'>";
foreach ($serials as $serial) {
	echo "<li>".$serial."</li>";
}
echo "</ul>";
?>
