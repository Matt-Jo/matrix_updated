<?php
require('includes/application_top.php');

if (PHP_SAPI === 'cli') define('CONTEXT', 'cli');
else define('CONTEXT', 'html'); // display relevant data out to an HTML context

$today = ck_datetime::NOW();
$start = NULL;
$end = NULL;

if (CONTEXT == 'cli') {
	$options = getopt('s:e:', ['start:', 'end:']);

	if (empty($start)) $start = !empty($options['s'])?$options['s']:NULL;
	if (empty($start)) $start = !empty($options['start'])?$options['start']:NULL;

	if (!empty($start)) $start = ck_datetime::datify($start);
	else $start = $today;

	if (empty($end)) $end = !empty($options['e'])?$options['e']:NULL;
	if (empty($end)) $end = !empty($options['end'])?$options['end']:NULL;

	if (!empty($end)) $end = ck_datetime::datify($end);
}
// we don't run this via http, so we haven't implemented those options - but we should

$yotpo = new api_yotpo;
$yotpo->send_order_feed($start, $end);
?>
