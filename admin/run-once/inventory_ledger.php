<?php
require_once(__DIR__.'/../../includes/application_top.php');

debug_tools::init_page();
debug_tools::enable_flag('print');
debug_tools::enable_flag('memory');

$job = 'inventory_ledger';

if (ck_job::is_running($job)) {
	debug_tools::note('inventory ledger is already running');
	exit();
}

ck_job::start($job);

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

$path = dirname(__FILE__);

ini_set('memory_limit', '4096M');
set_time_limit(0);

$cli_flag = [];
if ($cli && !empty($argv[1])) {
	for ($i=1; $i<count($argv); $i++) {
		$flag = explode('=', $argv[$i], 2);
		$cli_flag[$flag[0]] = !empty($flag[1])?$flag[1]:TRUE;
	}
}

if ($cli && !empty($cli_flag['--start'])) $start_date = new DateTime($cli_flag['--start']);
elseif (!$cli && !empty($_REQUEST['start'])) $start_date = new DateTime($_REQUEST['start']);
else $start_date = NULL;

if ($cli && !empty($cli_flag['--stock_id'])) $stock_id = $cli_flag['--stock_id'];
elseif (!$cli && !empty($_REQUEST['stock_id'])) $stock_id = $_REQUEST['stock_id'];
else $stock_id = NULL;

if ($cli && isset($cli_flag['--force-reset'])) $force_reset = TRUE;
elseif (!$cli && isset($_REQUEST['force-reset'])) $force_reset = TRUE;
else $force_reset = FALSE;

// fix missing date added data

// start by filling in a bunch of missing IPN creation entries
prepared_query::execute("UPDATE products_stock_control_change_history pscch LEFT JOIN (SELECT stock_id, MIN(change_date) as change_date FROM products_stock_control_change_history WHERE change_date IS NOT NULL GROUP BY stock_id) pscch0 ON pscch.stock_id = pscch0.stock_id SET pscch.change_date = pscch0.change_date WHERE pscch.type_id = 26 AND pscch.change_date IS NULL");

// start conservative - look for "new IPN" changes
prepared_query::execute("UPDATE products_stock_control psc LEFT JOIN (SELECT stock_id, MIN(change_date) as change_date FROM products_stock_control_change_history WHERE type_id IN (26, 1022) AND change_date IS NOT NULL GROUP BY stock_id) pscch ON psc.stock_id = pscch.stock_id SET psc.date_added = pscch.change_date WHERE psc.date_added IS NULL OR psc.date_added IS NULL");

// ... then just set it to the earliest change for everything else, we're going way back here in all cases
prepared_query::execute("UPDATE products_stock_control psc LEFT JOIN (SELECT stock_id, MIN(change_date) as change_date FROM products_stock_control_change_history WHERE change_date IS NOT NULL GROUP BY stock_id) pscch ON psc.stock_id = pscch.stock_id SET psc.date_added = pscch.change_date WHERE psc.date_added IS NULL OR psc.date_added IS NULL");

$run_date = new DateTime();

ck_ipn2::build_current_ipn_ranks($stock_id);
ck_ipn2::build_daily_ledger_history($start_date, $stock_id, TRUE, $force_reset);
ck_ipn2::build_physical_inventory_snapshot_history($stock_id, TRUE, $force_reset);

$now = new DateTime();

if ($now->format('Y-m-d') != $run_date->format('Y-m-d')) {
	debug_tools::mark('Go back and catch us back up if we took longer than a day');

	ck_ipn2::build_current_ipn_ranks($stock_id);
	ck_ipn2::build_daily_ledger_history($run_date, $stock_id, FALSE);
	ck_ipn2::build_physical_inventory_snapshot_history($stock_id, FALSE);
}

debug_tools::mark('Total Script Run Time');

ck_job::stop($job);
?>
