<?php
require_once(__DIR__.'/../../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

@ini_set("memory_limit","2048M");
set_time_limit(0);

debug_tools::mark('START');

try {
	salesforce::push_all_customers(1000);
}
catch (Exception $e) {
	echo $e->getMessage();
}

debug_tools::clear_sub_timer_context();

debug_tools::mark('Done');
?>
