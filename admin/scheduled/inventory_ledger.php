<?php
require_once(__DIR__.'/../../includes/application_top.php');

$job = 'inventory_ledger';

if (ck_job::is_running($job)) {
	debug_tools::note('inventory ledger is already running');
	exit();
}

ck_job::start($job);

$cli = PHP_SAPI==='cli'?TRUE:FALSE;
$path = dirname(__FILE__);

$cli_flag = [];
if ($cli && !empty($argv[1])) {
	for ($i=1; $i<count($argv); $i++) {
		$flag = explode('=', $argv[$i], 2);
		$cli_flag[$flag[0]] = !empty($flag[1])?$flag[1]:TRUE;
	}
}

$today = new DateTime(date('Y-m-d'));

if ($cli && !empty($cli_flag['--start'])) $startdate = new DateTime($cli_flag['--start']);
elseif (!$cli && !empty($_REQUEST['start'])) $startdate = new DateTime($_REQUEST['start']);
elseif ($startdate = prepared_query::fetch('SELECT MAX(transaction_date) FROM ck_inventory_ledgers', cardinality::SINGLE)) {
	$startdate = new DateTime($startdate);
	$startdate->add(new DateInterval('P1D'));
}
else $startdate = NULL;

if ($cli && !empty($cli_flag['--stock_id'])) $stock_id = $cli_flag['--stock_id'];
elseif (!$cli && !empty($_REQUEST['stock_id'])) $stock_id = $_REQUEST['stock_id'];
else $stock_id = NULL;

ck_ipn2::build_current_ipn_ranks($stock_id);
ck_ipn2::build_daily_ledger_history($start_date, $stock_id, FALSE);
ck_ipn2::build_physical_inventory_snapshot_history($stock_id, FALSE);

ck_job::stop($job);
?>
