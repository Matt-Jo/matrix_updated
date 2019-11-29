<?php
require('includes/application_top.php');

$orderid = $_POST['orderid'];
$ipn = $_POST['ipn'];
$serial = $_POST['serial'];

$check_serial = prepared_query::fetch("select s.id, sh.order_id from serials s, serials_history sh where serial = :serial and ipn = :ipn AND s.id = sh.serial_id AND sh.entered_date = (select max(entered_date) from serials_history where serial_id = s.id)", cardinality::ROW, [':serial' => $serial, ':ipn' => $ipn]);

if (empty($check_serial)) {
	echo "That serial is not in the database.";
}
elseif (!empty($check_serial['order_id'])) {
	if ($check_serial['order_id'] != $orderid) echo "The serial is already allocated to order ".$check_serial['order_id'];
	else echo strlen($serial) ? $serial : "click to edit";
}
else {
	prepared_query::execute("update serials_history sh, serials s set sh.order_id=:order_id where s.serial=:serial and s.ipn=:ipn AND s.id = sh.serial_id AND sh.entered_date = (select max(entered_date) from serials_history where serial_id = s.id)", [':order_id' => $orderid, ':serial' => $serial, ':ipn' => $ipn]);
	echo strlen($serial) ? $serial : "click to edit";
}
?>
