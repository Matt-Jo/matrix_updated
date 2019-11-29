<?php
require_once(__DIR__.'/../../includes/application_top.php');

error_reporting(E_ALL);

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

function build_children($category_id, $categories) {
	$children = array();
	foreach ($categories as $category) {
		if ($category['parent_id'] == $category_id) {
			$children[] = $category['categories_id'];
			$children = array_merge($children, build_children($category['categories_id'], $categories));
		}
	}
	return array_unique($children);
}

set_time_limit(0);

$targets = array(
	21 => array(144, 1030, 35, 85, 83, 84, 92, 86, 941, 82, 1031, 870),
	90 => array(44, 412, 936, 416, 193),
	180 => array(1058, 507),
	60 => array(68, 46, 53, 36, 22, 48, 422, 156, 115, 125, 196, 251, 27, 77, 462, 242, 1052, 241, 443, 973, 49, 65, 463, 253, 837, 974, 142, 1060, 1061, 70, 1062, 1085, 195)
);

foreach ($targets as $target => $categories) {
	$all_categories = prepared_query::fetch('SELECT categories_id, parent_id FROM categories ORDER BY categories_id DESC', cardinality::SET);
	$updates = array();
	foreach ($categories as $category_id) {
		$updates[] = $category_id;
		$updates = array_merge($updates, build_children($category_id, $all_categories));
	}
	$updates = array_unique($updates);

	try {
		prepared_query::execute('UPDATE products_stock_control psc JOIN products p ON psc.stock_id = p.stock_id JOIN products_to_categories ptc ON p.products_id = ptc.products_id SET psc.target_inventory_level = ? WHERE ptc.categories_id IN ('.implode(', ', $updates).')', array($target));
	}
	catch (Exception $e) {
		echo $e->getMessage();
	}
}
?>
