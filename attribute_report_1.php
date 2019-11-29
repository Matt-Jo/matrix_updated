<?php
require('includes/application_top.php');

function build_data() {
	$data = array();
	if ($categories = prepared_query::fetch('SELECT c.categories_id, c.parent_id, cd.categories_name as category, cd.categories_description as description, ptc.product_count FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id LEFT JOIN (SELECT categories_id, COUNT(products_id) as product_count FROM products_to_categories GROUP BY categories_id) ptc ON c.categories_id = ptc.categories_id ORDER BY c.categories_id DESC', cardinality::SET)) {
		foreach ($categories as $category) {
			$hierarchy = array();
			$children = (object) array('children' => 0, 'children_products' => 0);
			if (!empty($category['parent_id'])) {
				build_hierarchy($category['parent_id'], $categories, $hierarchy);
			}
			build_children($category['categories_id'], $categories, $children);

			$data[] = array('categories_id' => $category['categories_id'], 'category_name' => $category['category'], 'parent_hierarchy' => implode(' > ', array_reverse($hierarchy)), 'num_children' => $children->children, 'num_products' => $category['product_count'], 'num_children_products' => $children->children_products);
		}
	}
	return $data;
}

 function build_hierarchy($parent_id, $categories, &$hierarchy) {
	foreach ($categories as $category) {
		if ($category['categories_id'] == $parent_id) {
			$hierarchy[] = $category['category'];
			if ($category['parent_id']) build_hierarchy($category['parent_id'], $categories, $hierarchy);
			// each category will have only one parent
			break;
		}
	}
	return $hierarchy;
}

function build_children($categories_id, $categories, &$children) {
	foreach ($categories as $category) {
		if ($category['parent_id'] == $categories_id) {
			$children->children++;
			$children->children_products += $category['product_count'];
			build_children($category['categories_id'], $categories, $children);
			// each category may have multiple children
			// break;
		}
	}
	return $children;
}

try {
	$data = build_data();
	$attribute_report_1 = fopen('attribute_report_1.csv', 'w');
	$intermediate = array();
	$intermediate[] = implode(',', array_keys($data[0]));
	foreach ($data as $row) {
		$intermediate[] = implode(',', $row);
	}
	fwrite($attribute_report_1, implode("\n", $intermediate));
	fclose($attribute_report_1);
	echo "SUCCESS!!!...?";
}
catch (Exception $e) {
	echo "<p>".$e->getMessage()."</p>";
}
?>