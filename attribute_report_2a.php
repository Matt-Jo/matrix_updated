<?php
require('includes/application_top.php');

function build_category_hierarchies() {
	$data = array();
	if ($categories = prepared_query::fetch('SELECT c.categories_id, c.parent_id, cd.categories_name FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id ORDER BY c.categories_id DESC', cardinality::SET)) {
		foreach ($categories as $category) {
			$hierarchy = array($category['categories_name']);
			if (!empty($category['parent_id'])) {
				build_hierarchy($category['parent_id'], $categories, $hierarchy);
			}
			$data[$category['categories_id']] = implode('>', array_reverse($hierarchy));
		}
	}
	return $data;
}
function build_hierarchy($parent_id, $categories, &$hierarchy) {
	foreach ($categories as $category) {
		if ($category['categories_id'] == $parent_id) {
			$hierarchy[] = $category['categories_name'];
			if ($category['parent_id']) build_hierarchy($category['parent_id'], $categories, $hierarchy);
			// each category will have only one parent
			break;
		}
	}
	return $hierarchy;
}
function build_data() {
	$data = array();
	$hierarchies = build_category_hierarchies();
	if ($products = prepared_query::fetch('SELECT psc.stock_id, psc.stock_name as ipn, p.products_id, p.products_model as model_number, pd.products_name FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id JOIN products_description pd ON p.products_id = pd.products_id WHERE p.products_status = 1', cardinality::SET)) {
		foreach ($products as $product) {
			$row = array('products_id' => $product['products_id'], 'stock_id' => $product['stock_id'], 'model_number' => $product['model_number'], 'ipn' => $product['ipn'], 'product_name' => '"'.preg_replace('/"/', '', $product['products_name']).'"', 'category_hierarchies' => '', 'first_category' => '');
			if ($attributes = prepared_query::fetch('SELECT DISTINCT attribute_key FROM ck_attribute_assignments WHERE products_id = ?', cardinality::SET, array($product['products_id']))) {
				foreach ($attributes as $idx => $attribute) {
					$row["attribute_key_$idx"] = $attribute['attribute_key'];
					$row["attribute_value_$idx"] = '';
				}
			}
			else {
				continue;
			}
			$category_field = array();
			$direct_categories = array();
			if ($categories = prepared_query::fetch('SELECT DISTINCT ptc.categories_id, cd.categories_name FROM products_to_categories ptc JOIN categories_description cd ON ptc.categories_id = cd.categories_id WHERE ptc.products_id = ?', cardinality::SET, array($product['products_id']))) {
				foreach ($categories as $category) {
					$category_field[] = $hierarchies[$category['categories_id']];
					$direct_categories[] = $category['categories_name'];
				}
			}
			$row['category_hierarchies'] = '"'.implode('; ', preg_replace('/"/', '', $category_field)).'"';
			$row['first_category'] = '"'.preg_replace('/"/', '', $direct_categories[0]).'"';
			$data[] = $row;
		}
	}
	return $data;
}

try {
	$data = build_data();
	$attribute_report_2 = fopen('feeds/attribute_report_2.csv', 'w');
	$intermediate = array();
	$header = array();
	foreach ($data as $row) {
		$rowhead = array_keys($row);
		if (count($rowhead) > count($header)) {
			for ($i=count($header); $i<count($rowhead); $i++) {
				$header[] = $rowhead[$i];
			}
		}
		$intermediate[] = implode(',', $row);
	}
	array_unshift($intermediate, implode(',', $header));
	fwrite($attribute_report_2, implode("\n", $intermediate));
	fclose($attribute_report_2);
	echo "SUCCESS!!!...?";
}
catch (Exception $e) {
	echo "<p>".$e->getMessage()."</p>";
}
?>