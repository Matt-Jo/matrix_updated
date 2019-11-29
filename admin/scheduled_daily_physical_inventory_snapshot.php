<?php
require_once(__DIR__.'/../includes/application_top.php');

try {
	echo 'Start';
	ck_ipn2::daily_physical_inventory_snapshot();
	echo 'Finished';
}
catch (Exception $e) {
	echo $e->getMessage();
}
?>