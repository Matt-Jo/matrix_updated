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

$yesterday = new DateTime();
$yesterday->sub(new DateInterval('P1D'));

if ($ipns = prepared_query::fetch('SELECT products_stock_control.stock_name as ipn, rs.stock_id, rs.recorded_quantity, ps.day_end_qty as figured_quantity, rs.discrepancy_quantity, rs.recorded_total_cost, ps.day_end_qty * ps.day_end_unit_cost as figured_total_cost, rs.days_discrepancy, rs.record_date FROM products_stock_control JOIN ck_daily_recorded_inventory_snapshot rs ON rs.stock_id = products_stock_control.stock_id LEFT JOIN ck_daily_physical_inventory_snapshot ps ON rs.stock_id = ps.stock_id AND rs.record_date = ps.record_date WHERE rs.record_date = :yesterday AND rs.days_discrepancy > 0', cardinality::SET, [':yesterday' => $yesterday->format('Y-m-d')])) {
	foreach ($ipns as &$ipn) {
		$ipn['record_date'] = (new DateTime($ipn['record_date']))->format('m/d/Y');
		$ipn['absolute_qty_off'] = abs($ipn['discrepancy_quantity']);
		$ipn['absolute_dollar_off'] = CK\text::monetize(abs($ipn['figured_total_cost'] - $ipn['recorded_total_cost']));
		$ipn['figured_total_cost'] = CK\text::monetize($ipn['figured_total_cost']);
		$ipn['recorded_total_cost'] = CK\text::monetize($ipn['recorded_total_cost']);
	}
}

$content_map->ipns = $ipns;

$cktpl->content('includes/templates/page-ipn-count-discrepancies.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
