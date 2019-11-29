<?php
require_once("includes/application_top.php");

$content = 'po_confirmation';

$key = !empty($_GET['key'])?$_GET['key']:NULL;

if (!empty($key)) {
	$lookup = prepared_query::fetch("select po.id, po.purchase_order_number from purchase_orders po where po.confirmation_hash = :key", cardinality::ROW, [':key' => $key]);

	if (!empty($lookup)) {
		$po_number = $lookup['purchase_order_number'];
		prepared_query::execute("update purchase_orders po set po.confirmation_status = '2' where po.id = :po_id", [':po_id' => $lookup['id']]);
	}
	else $error = "PO not found or confirmation key is invalid.";
}
else $error = "Confirmation key was not set in url.";

require('templates/Pixame_v1/main_page.tpl.php');
