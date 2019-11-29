<?php
require('includes/application_top.php');

//---------header-----------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//---------end header-------------

//---------body-------------------
$content_map = new ck_content();

if (!empty($errors)) {
	$content_map->{'has_errors?'} = 1;
	$content_map->errors = $errors;
}

$urgency_priority = [
	'In Stock',
	'Zero Available',
	'Allocated to Order'
];

function default_worklist_sort($a, $b) {
	if (array_search($a['urgency'], $GLOBALS['urgency_priority']) > array_search($b['urgency'], $GLOBALS['urgency_priority'])) return -1;
	elseif (array_search($a['urgency'], $GLOBALS['urgency_priority']) < array_search($b['urgency'], $GLOBALS['urgency_priority'])) return 1;
	else return 0;
}

$testing_holds = [];
if ($testing_hold_raw = prepared_query::fetch('SELECT ih.id AS hold_id, ih.date AS hold_date, s.serial, psc.stock_id, psc.stock_name AS ipn, sh.bin_location FROM inventory_hold ih LEFT JOIN serials s ON ih.serial_id = s.id LEFT JOIN products_stock_control psc ON ih.stock_id = psc.stock_id LEFT JOIN serials_history sh ON ih.serial_id = sh.serial_id WHERE ih.reason_id = 11 AND s.status = 6 GROUP BY s.serial', cardinality::SET)) {
	foreach ($testing_hold_raw as $testing_hold) {
		$testing_holds[$testing_hold['hold_id']] = $testing_hold;
		
		$ipn = new ck_ipn2($testing_hold['stock_id']);

		$testing_hold_qty = prepared_query::fetch('SELECT SUM(quantity) FROM inventory_hold WHERE stock_id = :stock_id AND reason_id = 11', cardinality::SINGLE, [':stock_id' => $ipn->id()]);

		$testing_holds[$testing_hold['hold_id']]['available'] = !empty($ipn->get_inventory('available'))?$ipn->get_inventory('available'):0;
		$testing_holds[$testing_hold['hold_id']]['on_hand'] = !empty($ipn->get_inventory('on_hand'))?$ipn->get_inventory('on_hand'):0;
		$testing_holds[$testing_hold['hold_id']]['allocated'] = !empty($ipn->get_inventory('allocated'))?$ipn->get_inventory('allocated'):0;
		$testing_holds[$testing_hold['hold_id']]['testing_hold'] = !empty($testing_hold_qty)?$testing_hold_qty:0;
		$testing_holds[$testing_hold['hold_id']]['total_hold'] = !empty($ipn->get_inventory('on_hold'))?$ipn->get_inventory('on_hold'):0;
		$testing_holds[$testing_hold['hold_id']]['urgency'] = NULL;
		$testing_holds[$testing_hold['hold_id']]['orders'] = [];
	}
}

$testing_hold_orders = prepared_query::fetch('SELECT op.orders_id, psc.stock_name FROM orders_products op LEFT JOIN orders o ON op.orders_id = o.orders_id LEFT JOIN products p ON op.products_id = p.products_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE o.orders_status = 11 AND o.orders_sub_status = 15 AND psc.serialized = 1', cardinality::SET);

unset($testing_hold_raw);

if (!empty($testing_holds)) {
	$content_map->testing_holds = [];
	$content_map->totals = [];

	$totals['total_zero_available'] = 0;
	$totals['total_in_stock'] = 0;
	$totals['total_allocated_to_order'] = 0;
	$totals['total_entries'] = 0;

	foreach ($testing_holds as $ph) {
		foreach ($testing_hold_orders as $orders) {
			if ($ph['ipn'] == $orders['stock_name'] && !in_array($orders['orders_id'], $ph['orders'])) $ph['orders'][] = $orders['orders_id'];
		}

		$hold_date = new DateTime($ph['hold_date']);
		$todays_date = new DateTime();

		$ph['days_on_hold'] = $todays_date->diff($hold_date)->format('%a');

		$urgency = '';

		if ($ph['available'] < 0) {
			$urgency = $urgency_priority[2];
			$totals['total_allocated_to_order']++;
		}
		elseif ($ph['available'] == 0) {
			$urgency = $urgency_priority[1];
			$totals['total_zero_available']++;
		}
		elseif ($ph['available'] > 0) {
			$urgency = $urgency_priority[0];
			$totals['total_in_stock']++;
		}
		$totals['total_entries']++;
		$ph['urgency'] = $urgency;
		$content_map->testing_holds[] = $ph;
	}
	$content_map->totals[] = $totals;
}

if (!empty($content_map->testing_holds)) usort($content_map->testing_holds, 'default_worklist_sort');

$cktpl->content('includes/templates/page-testing-worklist.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
