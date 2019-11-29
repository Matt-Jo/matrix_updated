<?php
// eventually, we'll kill application top and move all of those functions into the front controller or this file
require('includes/application_top.php');

debug_tools::init_page();

/*$accounting_date = ck_datetime::NOW();
if (!$accounting_date->is_weekend()) $accounting_date->modify('next saturday');
ck_invoice::set_accounting_date($accounting_date, FALSE);*/

$front_controller = new ck_front_controller;

$view = $front_controller->run();

// this'll be replaced
require(__DIR__.'/templates/Pixame_v1/main_page.tpl.php');
?>
