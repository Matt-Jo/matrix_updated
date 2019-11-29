<?php
require_once('includes/application_top.php');

$order = !empty($_GET['orders_id'])?new ck_sales_order($_GET['orders_id']):NULL;

$ca = new api_channel_advisor;
if ($ca::is_authorized()) $ca->export_shipped_orders($order);
?>
