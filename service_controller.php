<?php
require('includes/application_top.php');

$service_controller = new ck_service_controller;

$service_controller->register_service('salesforce', 'ck_salesforce_service');

$service = $service_controller->run();

$service->authenticate();
$service->process_request();
$service->act();
$service->respond();
?>
