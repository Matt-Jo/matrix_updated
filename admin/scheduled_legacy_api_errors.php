<?php
require_once(__DIR__.'/../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;
$path = dirname(__FILE__);

$cli_flag = [];
if ($cli && !empty($argv[1])) {
	for ($i=1; $i<count($argv); $i++) {
		$flag = explode('=', $argv[$i], 2);
		$cli_flag[$flag[0]] = !empty($flag[1])?$flag[1]:TRUE;
	}
}

/*if ($cli && !empty($cli_flag['--verbose'])) $verbose = TRUE;
elseif (!$cli && $__FLAG['verbose']) $verbose = TRUE;
else $verbose = FALSE;*/

/*$today = new DateTime(date('Y-m-d'));

if ($cli && !empty($cli_flag['--start'])) $startdate = new DateTime($cli_flag['--start']);
elseif (!$cli && !empty($_REQUEST['start'])) $startdate = new DateTime($_REQUEST['start']);
elseif ($startdate = prepared_query::fetch('SELECT MAX(transaction_date) FROM ck_inventory_ledgers', cardinality::SINGLE)) {
	$startdate = new DateTime($startdate);
	$startdate->add(new DateInterval('P1D'));
}
else $startdate = NULL;

$start = time();*/

ck_legacy_api::send_errors();

/*$end = time();

if ($verbose) echo 'Total Run Time: '.($end-$start).' Seconds'."\n";*/

exit();
?>
