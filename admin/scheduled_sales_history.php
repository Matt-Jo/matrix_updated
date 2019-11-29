<?php
require_once(__DIR__.'/../includes/application_top.php');

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

set_time_limit(0);

try {
	prepared_query::execute('TRUNCATE TABLE ck_cache_sales_history');
	$forecast = new forecast();
	$ipn_hist = $forecast->build_history();
	foreach ($ipn_hist as $stock_id => $history) {
		prepared_query::execute('INSERT INTO ck_cache_sales_history (stock_id, to180, p3060, to30, last_special) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))', array($stock_id, @$history['to180'], @$history['p3060'], @$history['to30'], @$history['last_special']));
	}
}
catch (Exception $e) {
	echo $e->getMessage();
	// we should make some sort of notification to someone who cares here
}
?>
