<?php
require_once(__DIR__.'/../../includes/application_top.php');
ini_set('memory_limit', '768M');
set_time_limit(0);

try {
	echo 'Start';
	$forecast = new forecast();
	foreach ($forecast->build_report('all', TRUE) as $ipn) {
		$current_daily_runrate = $forecast->daily_qty($ipn);
		$current_days_on_hand = !$ipn['available_quantity']?0:(!$current_daily_runrate?999999:ceil($ipn['available_quantity']/($current_daily_runrate)));

		prepared_query::execute('UPDATE products_stock_control SET current_daily_runrate = :current_daily_runrate, current_days_on_hand = :current_days_on_hand WHERE stock_id = :stock_id', [':current_daily_runrate' => $current_daily_runrate, ':current_days_on_hand' => $current_days_on_hand, ':stock_id' => $ipn['stock_id']]);
	}
	echo 'Finished';
}
catch (Exception $e) {
	echo $e->getMessage();
}
?>