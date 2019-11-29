<?php
@include('includes/application_top.php');
//ob_start();
debug_tools::start_timer();

ini_set('display_errors', 1);

if (PHP_SAPI === 'cli') define('CONTEXT', 'cli');
else define('CONTEXT', 'html'); // display relevant data out to an HTML context

if (CONTEXT == 'cli') {
	$options = getopt('s:', ['debug', 'test']);
	$service = $options['s'];
	if (!defined('DEBUG')) define('DEBUG', isset($options['debug'])&&$options['debug']);
	if (!defined('TEST')) define('TEST', isset($options['test'])&&$options['test']);
}
elseif (CONTEXT == 'html') {
	$service = isset($_REQUEST['service'])?strtolower($_REQUEST['service']):@strtolower($_REQUEST['s']);
	if (CK\fn::check_flag(@$_REQUEST['debug'])) define('DEBUG', TRUE);
	else define('DEBUG', FALSE);
	if (CK\fn::check_flag(@$_REQUEST['test'])) define('TEST', TRUE);
	else define('TEST', FALSE);
}

if (DEBUG || TEST) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}

$service = preg_replace('/[^a-z]/', '', $service);

$services = array(
	//'celebros',
	'nextopia',
	'brokerbin',
	//'godatafeed',
	'googleadservices',
	'bingadservices',
	'merchantadvantage',
	'amazoninventoryupdates',
	'ebayupdater',
	//'bazaarvoice',
	'cainventory',
	'adwords',
	'adwordstwo',
	//'bing',
	'windsor',
	//'ittrader',
	'sitemap',
	'criteo',
	'listrakorders',
	'listrakcustomers',
	'listrakorderitems',
	'listrakproducts',
	'adcustomizer',
	'feedonomics',
	'hydrianinventory',
	'hydriansales',
	'hydrianreceipts',
	'hydrianconversions',
	'hydrianvendors',
	'atlas',
	'roi',
	'salsify',
);

if (empty($service)) {
	throw new Exception("There was no service specified. You must specify a valid service.");
	exit();
}
elseif (!in_array($service, $services)) {
	throw new Exception("The [$service] service is not a valid, defined service able to consume our product feed.");
	exit();
}

$feed_class = "feed_$service";

// check class existence, throw an exception if we can't find stuff that should be included
if (!$db) {
	throw new Exception('DB init failed.');
	exit();
}
if (!class_exists('data_feed')) {
	throw new Exception('Parent class include failed.');
	exit();
}
if (!class_exists($feed_class)) {
	throw new Exception('File include failed [includes/lib-feeds/'.$feed_class.'.class.php].');
	exit();
}

if (DEBUG) $feed_class::$DEBUG = TRUE;
if (TEST) $feed_class::$TEST = TRUE;

prepared_query::execute('DELETE FROM ck_feed_failure_tracking WHERE failure_date < DATE(NOW()) - INTERVAL 2 WEEK');

$feed = new $feed_class();
$feed->build();

$feed->write();
//$output = ob_get_clean();
//if (DEBUG) echo $output;

if (!is_cli() && $__FLAG['then_close']) { ?>
<script>
	window.close();
</script>
<?php } ?>
