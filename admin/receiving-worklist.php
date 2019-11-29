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

$terms_map = [
	0 => 0,
	1 => 0,
	2 => 1,
	3 => 5,
	4 => 10,
	5 => 15,
	6 => 20,
	7 => 30,
	8 => 45,
	9 => 30,
	10 => 0,
	11 => 0,
	12 => 0,
	13 => 0,
	14 => 7,
	15 => 0
];

$transit_time_default = 5;
$work_order_id = 11; // sub_status_id for Workorder orders

// later entries have a higher priority, this way it can work by default with array_search and anything unfound == FALSE == 0
$urgency_priority = [
	'[[[[NOT FOUND]]]]',
	'0 Available',
	'Allocated',
	'Work Order'
];

function default_worklist_sort($a, $b) {
	if (array_search($a['urgency'], $GLOBALS['urgency_priority']) > array_search($b['urgency'], $GLOBALS['urgency_priority'])) return -1;
	elseif (array_search($a['urgency'], $GLOBALS['urgency_priority']) < array_search($b['urgency'], $GLOBALS['urgency_priority'])) return 1;
	else {
		$d1 = new DateTime($a['terms_due']);
		$d2 = new DateTime($b['terms_due']);
		if ($d1 < $d2) return -1;
		elseif ($d1 > $d2) return 1;
		elseif ($a['open_qty'] > $b['open_qty']) return -1;
		elseif ($a['open_qty'] < $b['open_qty']) return 1;
		else return 0;
	}
}

$tracking_numbers = prepared_query::fetch('SELECT po.id as po_id, po.purchase_order_number as po_number, pot.tracking_number, pot.bin_number, v.vendors_company_name as vendor, po.creation_date as po_date, po.terms, pot.arrival_time as scanned, CONCAT(a.admin_firstname, \' \', a.admin_lastname) AS administrator FROM purchase_orders po JOIN purchase_order_tracking pot ON po.id = pot.po_id LEFT JOIN vendors v ON po.vendor = v.vendors_id LEFT JOIN admin a ON po.administrator_admin_id = a.admin_id WHERE po.status IN (1, 2) AND pot.STATUS = 1 AND pot.complete = 0', cardinality::SET); // AND po.purchase_order_number = 46009'); //

$ipns = [];
if ($ipns_raw = prepared_query::fetch('SELECT po.id as po_id, psc.stock_id, CASE WHEN psc.serialized = 1 THEN COUNT(s.id) ELSE psc.stock_quantity END as quantity, pop.quantity as po_qty, IFNULL(porp.received_qty, 0) as received_qty, ih.hold_qty FROM purchase_order_products pop LEFT JOIN (SELECT purchase_order_product_id, SUM(quantity_received) as received_qty FROM purchase_order_received_products GROUP BY purchase_order_product_id) porp ON pop.id = porp.purchase_order_product_id JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id LEFT JOIN serials s ON psc.stock_id = s.ipn AND status IN (2, 3, 6) LEFT JOIN (SELECT stock_id, SUM(quantity) as hold_qty FROM inventory_hold GROUP BY stock_id) ih ON psc.stock_id = ih.stock_id JOIN purchase_orders po ON pop.purchase_order_id = po.id JOIN purchase_order_tracking pot ON po.id = pot.po_id WHERE po.status IN (1, 2) AND pot.STATUS = 1 GROUP BY po.id, psc.stock_id, pop.quantity', cardinality::SET)) {
	foreach ($ipns_raw as $ipn) {
		if (empty($ipns[$ipn['po_id']])) $ipns[$ipn['po_id']] = [];
		$ipns[$ipn['po_id']][] = $ipn;
	}
}
unset($ipns_raw);

$allocations = [];
if ($allocations_raw = prepared_query::fetch('SELECT po.id as po_id, psc.stock_id, potoa.quantity as allocated_qty, o.orders_sub_status FROM purchase_order_products pop JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id JOIN purchase_order_to_order_allocations potoa ON pop.id = potoa.purchase_order_product_id JOIN orders_products op ON potoa.order_product_id = op.orders_products_id JOIN orders o ON op.orders_id = o.orders_id JOIN purchase_orders po ON pop.purchase_order_id = po.id JOIN purchase_order_tracking pot ON po.id = pot.po_id WHERE po.status IN (1, 2) AND pot.STATUS = 1', cardinality::SET)) {
	foreach ($allocations_raw as $allocation) {
		if (empty($allocations[$allocation['po_id']])) $allocations[$allocation['po_id']] = [];
		if (empty($allocations[$allocation['po_id']][$allocation['stock_id']])) $allocations[$allocation['po_id']][$allocation['stock_id']] = [];
		$allocations[$allocation['po_id']][$allocation['stock_id']][] = $allocation;
	}
}
unset($allocations_raw);

$date = new DateTime();
$date_interval = new DateInterval("P30D");
$date_interval->invert = 1;
$date->add($date_interval);

$top_fifty_products = prepared_query::fetch('SELECT psc.stock_id FROM products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id LEFT JOIN orders_products op ON p.products_id = op.products_id LEFT JOIN acc_invoices ai ON op.orders_id = ai.inv_order_id WHERE DATE_FORMAT(ai.inv_date, \'%Y-%m-%d\') > :date AND psc.serialized = 1 GROUP BY psc.stock_id ORDER BY SUM(op.products_quantity) DESC LIMIT 50', cardinality::SET, [':date' => $date->format('Y-m-d')]);

$ipn_zero_since = prepared_query::fetch('SELECT po.id AS po_id, DATE_FORMAT(cil.transaction_date, \'%Y-%m-%d\') AS date FROM purchase_orders po LEFT JOIN purchase_order_products pop ON po.id = pop.purchase_order_id LEFT JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id LEFT JOIN ck_inventory_ledgers cil ON pop.ipn_id = cil.stock_id WHERE (CASE WHEN psc.serialized = 0 THEN psc.stock_quantity WHEN psc.serialized = 1 THEN (SELECT COUNT(serial) FROM serials WHERE ipn = pop.ipn_id AND status = 2) END) = 0 AND po.status IN (1,2) AND (SELECT ending_qty FROM ck_inventory_ledgers WHERE stock_id = pop.ipn_id ORDER BY transaction_timestamp DESC LIMIT 1) = 0 ORDER BY DATE_FORMAT(cil.transaction_date, \'%Y-%m-%d\') ASC', cardinality::SET);

if (!empty($tracking_numbers)) {
	$content_map->tracking_numbers = [];
	$totals = ['work_order' => 0, 'allocated' => 0, 'zero_available' => 0, 'total_entries' => 0];
	foreach ($tracking_numbers as $tn) {
		$top_fifty_flag = NULL;
		$zero_since = NULL;
		$po_date = new DateTime($tn['po_date']);
		$scanned = new DateTime($tn['scanned']);
		$terms_due = new DateTime($tn['scanned']);

		$todays_date = new DateTime("now");

		foreach ($ipn_zero_since as $zero_stock) {
			if ($zero_stock['po_id'] == $tn['po_id']) {
				$total_days_since_zero = new DateTime($zero_stock['date']);
				$date_difference = $total_days_since_zero->diff($todays_date);
				$tn['zero_since'] = $date_difference->format('%a');
			}
		}

		// we allow 5 days for shipping
		$terms_days = $terms_map[$tn['terms']] - $transit_time_default;
		if ($terms_days > 0) $terms_due->add(new DateInterval('P' . $terms_days . 'D'));
		elseif ($terms_days < 0) $terms_due->sub(new DateInterval('P' . abs($terms_days) . 'D'));

		if ($terms_due < $po_date) $terms_due = $po_date;

		$tn['po_date'] = $po_date->format('m/d/Y H:i');
		$tn['scanned'] = $scanned->format('m/d/Y H:i');
		$tn['days_since_scanned'] = $todays_date->diff($scanned)->format('%a');
		$tn['days_until_terms_due'] = $todays_date->diff($terms_due)->format('%a');
		$tn['terms_due'] = $terms_due->format('m/d/Y H:i');

		$urgency = '';
		$po_qty = $open_qty = $received_qty = $available_qty = 0;

		$work_order = FALSE;

		if (!empty($ipns[$tn['po_id']])) {
			foreach ($ipns[$tn['po_id']] as $ipn) {
				$po_qty += $ipn['po_qty'];
				$received_qty += $ipn['received_qty'];

				$available_qty = $ipn['quantity'] - $ipn['hold_qty'];

				$allocated = 0;

				if (!empty($allocations[$tn['po_id']][$ipn['stock_id']])) {
					foreach ($allocations[$tn['po_id']][$ipn['stock_id']] as $allocation) {
						$allocated += $allocation['allocated_qty'];
						if ($allocation['orders_sub_status'] == $work_order_id) $work_order = TRUE;

						foreach ($top_fifty_products as $top_fifty) {
							if ($top_fifty['stock_id'] == $allocation['stock_id']) {
								$top_fifty_flag = ' class="top-fifty-flag"';
							}
						}
					}
				}

				if ($work_order) {
					$urgency = $urgency_priority[3];
					$totals['work_order'] ++;
				}
				elseif ($allocated > 0) {
					$urgency = $urgency_priority[2];
					$totals['allocated'] ++;
				}
				elseif ($available_qty - $allocated <= 0) {
					$urgency = $urgency_priority[1];
					$totals['zero_available'] ++;
				}
			}
			$open_qty += ($po_qty - $received_qty);
			$totals['total_entries'] ++;
		}

		$tn['po_qty'] = $po_qty;
		$tn['open_qty'] = $open_qty;
		$tn['received_qty'] = $received_qty;

		$tn['urgency'] = $urgency;
		$tn['top_fifty_flag'] = $top_fifty_flag;

		$content_map->tracking_numbers[] = $tn;
	}
}

$content_map->totals[] = $totals;

if (!empty($content_map->tracking_numbers)) usort($content_map->tracking_numbers, 'default_worklist_sort');

$cktpl->content('includes/templates/page-receiving-worklist.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
