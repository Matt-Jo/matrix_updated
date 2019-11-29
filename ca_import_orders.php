<?php
require_once('includes/application_top.php');

$ca = new api_channel_advisor;
if ($ca::is_authorized()) $ca->import_orders();
?>
