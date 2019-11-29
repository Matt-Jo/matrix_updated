<?php
require('includes/application_top.php');

ini_set('memory_limit', '768M');
set_time_limit(0);

//---------header-----------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//---------end header-------------

// process report

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;
switch ($action) {
	case 'run-report':
		// no break, we're falling through to the report runner
	default:
		$all_ipns = ck_ipn2::get_ipns_for_purchase_management();
		$ipns = [];

		foreach ($all_ipns as $ipn) {
			$forecasting_metadata = $ipn->get_forecasting_metadata();

			if ($ipn->is('discontinued') && $ipn->get_inventory('on_hand') <= 0) continue;

			if ($ipn->get_inventory('available') <= 0) $ipns[] = $ipn;
			elseif ($forecasting_metadata['days_supply'] < $forecasting_metadata['minimum_days']) $ipns[] = $ipn;
		}

		break;
}

//---------body-------------------
$content_map = new ck_content();

if (!empty($errors)) {
	$content_map->{'has_errors?'} = 1;
	$content_map->errors = $errors;
}

$severity_map = [
	'Out Of Stock' => 0,
	'All Stock Claimed' => 1,
	'Can\'t Cover Leadtime' => 2,
	'Can\'t Cover Minimum' => 3
];

if (!empty($ipns)) {
	$content_map->ipns = [];
	foreach ($ipns as $ipn) {
		$row = [];

		$forecasting_metadata = $ipn->get_forecasting_metadata();

		$row['ipn'] = $ipn->get_header('ipn');
		$row['vendor'] = $ipn->get_header('vendors_company_name');
		$row['lead_time'] = $ipn->get_header('lead_time');
		$row['on_order'] = $forecasting_metadata['pre-lead_on_order'];
		$row['on_hand'] = $ipn->get_inventory('on_hand');
		$row['available'] = $ipn->get_inventory('available');
		$row['minimum_qty'] = $forecasting_metadata['minimum_quantity'];
		$row['days_supply'] = $forecasting_metadata['days_supply'];
		$row['minimum_days'] = $forecasting_metadata['minimum_days'];

		if ($ipn->get_inventory('on_hand') <= 0) $row['severity'] = 'Out Of Stock';
		elseif ($ipn->get_inventory('available') <= 0) $row['severity'] = 'All Stock Claimed';
		else {
			$forecasting_metadata = $ipn->get_forecasting_metadata();

			if ($forecasting_metadata['days_supply'] < $forecasting_metadata['leadtime_days']) $row['severity'] = 'Can\'t Cover Leadtime';
			elseif ($forecasting_metadata['days_supply'] < $forecasting_metadata['minimum_days']) $row['severity'] = 'Can\'t Cover Minimum';
		}

		$content_map->ipns[] = $row;
	}

	usort($content_map->ipns, function($a, $b) use ($severity_map) {
		if ($severity_map[$a['severity']] == $severity_map[$b['severity']]) return 0;
		else return $severity_map[$a['severity']]<$severity_map[$b['severity']]?-1:1;
	});
}

$content_map->severity_map = [];
foreach ($severity_map as $severity => $severity_sort) {
	$content_map->severity_map[] = ['severity' => $severity, 'severity_sort' => $severity_sort];
}

$cktpl->content('includes/templates/page-endangered-ipns.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
