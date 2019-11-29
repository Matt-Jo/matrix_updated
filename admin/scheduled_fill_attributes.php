<?php
require_once(__DIR__.'/../includes/application_top.php');

error_reporting(E_ALL);

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

function build_category_hierarchies() {
	$data = array();
	if ($categories = prepared_query::fetch("SELECT categories_id, parent_id FROM categories ORDER BY categories_id DESC", cardinality::SET)) {
		foreach ($categories as $category) {
			$hierarchy = array();
			if (!empty($category['parent_id'])) {
				build_hierarchy($category['parent_id'], $categories, $hierarchy);
			}
			$data[$category['categories_id']] = implode('/', array_reverse($hierarchy));
		}
	}
	return $data;
}
function build_hierarchy($parent_id, $categories, &$hierarchy) {
	foreach ($categories as $category) {
		if ($category['categories_id'] == $parent_id) {
			$hierarchy[] = $category['categories_id'];
			if ($category['parent_id']) build_hierarchy($category['parent_id'], $categories, $hierarchy);
			// each category will have only one parent
			break;
		}
	}
	return $hierarchy;
}

set_time_limit(0);

try {
	$sales = prepared_query::fetch('SELECT p.products_id, p.stock_id, SUM(op.products_quantity) as qty FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 180 GROUP BY p.products_id', cardinality::SET);

	if (!($attribute_key_id = prepared_query::fetch("SELECT attribute_key_id FROM ck_attribute_keys WHERE attribute_key LIKE 'best sellers'", cardinality::SINGLE))) {
		$attribute_key_id = prepared_query::insert("INSERT INTO ck_attribute_keys (attribute_key, description) VALUES ('best sellers', 'Best Sellers')");
	}
	// reset any existing best seller attributes
	prepared_query::execute('UPDATE ck_attributes SET value = 0 WHERE attribute_key_id = ?', array($attribute_key_id));
	// add any best seller attributes for products that are new and may be missing them
	prepared_query::execute('INSERT IGNORE INTO ck_attributes (stock_id, ipn, products_id, model_number, attribute_key_id, attribute_key, value, internal) SELECT DISTINCT stock_id, ipn, products_id, model_number, ?, ?, ?, ? FROM ck_attributes WHERE products_id NOT IN (SELECT DISTINCT products_id FROM ck_attributes WHERE attribute_key_id = ?)', array($attribute_key_id, 'best sellers', 0, 1, $attribute_key_id));

	foreach ($sales as $product) {
		$value_insert = prepared_query::execute("UPDATE ck_attributes SET value = ? WHERE products_id = ? AND attribute_key_id = ?", array(str_pad($product['qty'], 6, '0', STR_PAD_LEFT), $product['products_id'], $attribute_key_id));
	}

	if ($products = prepared_query::fetch('SELECT DISTINCT p.products_id, p.products_model, ptc.categories_id, agl.attribute_key_id, agl.attribute_key FROM products p JOIN products_to_categories ptc ON p.products_id = ptc.products_id LEFT JOIN ck_attribute_group_categories agc ON ptc.categories_id = agc.category_id LEFT JOIN ck_attribute_group_lists agl ON agc.attribute_group_id = agl.attribute_group_id', cardinality::SET)) {
		$hierarchies = build_category_hierarchies();
		foreach ($products as $product) {
			if (!empty($product['attribute_key_id'])) {
				$attribute_assignment_insert = prepared_query::execute("INSERT IGNORE INTO ck_attribute_assignments (attribute_assignment_level, products_id, model_number, attribute_key_id, attribute_key) VALUES (?, ?, ?, ?, ?)", array(3, $product['products_id'], $product['products_model'], $product['attribute_key_id'], $product['attribute_key']));
			}
			$parent_categories = !empty($hierarchies[$product['categories_id']])?array_reverse(explode('/', $hierarchies[$product['categories_id']])):array();
			foreach ($parent_categories as $category_id) {
				if ($attributes = prepared_query::fetch('SELECT DISTINCT agc.category_id, agl.attribute_key_id, agl.attribute_key FROM ck_attribute_group_categories agc JOIN ck_attribute_group_lists agl ON agc.attribute_group_id = agl.attribute_group_id WHERE agc.trait = 1 AND agc.category_id = ?', cardinality::SET, array($category_id))) {
					foreach ($attributes as $attribute) {
						$attribute_assignment_insert = prepared_query::execute("INSERT IGNORE INTO ck_attribute_assignments (attribute_assignment_level, products_id, model_number, attribute_key_id, attribute_key) VALUES (?, ?, ?, ?, ?)", array(4, $product['products_id'], $product['products_model'], $attribute['attribute_key_id'], $attribute['attribute_key']));
					}
				}
			}
		}
	}
}
catch (Exception $e) {
	echo $e->getMessage();
	// we should make some sort of notification to someone who cares here
}
?>
