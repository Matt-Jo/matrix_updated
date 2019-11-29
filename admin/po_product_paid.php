<?php
$skip_check = TRUE;
require('includes/application_top.php');

if (empty($_GET['product_id'])) {
	prepared_query::execute("update purchase_order_received_products set paid = :paid where receiving_session_id = :session_id", [':paid' => $_GET['paid'], ':session_id' => $_GET['session_id']]);
}
else {
	prepared_query::execute("update purchase_order_received_products set paid = :paid where purchase_order_product_id IN ('".$_GET['product_id']."') and receiving_session_id = :session_id", [':paid' => $_GET['paid'], ':session_id' => $_GET['session_id']]);
}
