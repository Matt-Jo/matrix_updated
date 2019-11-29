<?php
require_once(__DIR__.'/../../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;
$path = dirname(__FILE__);

$cli_flag = [];
if ($cli && !empty($argv[1])) {
	for ($i=1; $i<count($argv); $i++) {
		$flag = explode('=', $argv[$i], 2);
		$cli_flag[$flag[0]] = !empty($flag[1])?$flag[1]:TRUE;
	}
}

if ($cli && !empty($cli_flag['--date'])) $date = new DateTime($cli_flag['--date']);
elseif (!$cli && !empty($_REQUEST['date'])) $date = new DateTime($_REQUEST['date']);
else $date = NULL;

ck_sales_order::day_end_batch_actions($date);
?>
