<?php
// -------------------------------------------------------------------------------------------------------------------------------------------------------------
// Parse and secure the faqPath parameter values
function faqdesk_parse_category_path($faqPath) {
	// make sure the category IDs are integers
	$faqPath_array = array_map(function($id) { return (int)$id; }, explode('_', $faqPath));

	// make sure no duplicate category IDs exist which could lock the server in a loop
	$tmp_array = array();
	$n = sizeof($faqPath_array);
	for ($i=0; $i<$n; $i++) {
		if (!in_array($faqPath_array[$i], $tmp_array)) {
			$tmp_array[] = $faqPath_array[$i];
		}
	}

	return $tmp_array;
}

// -------------------------------------------------------------------------------------------------------------------------------------------------------------
// Construct a category path to the product
// TABLES: products_to_categories
function faqdesk_get_product_path($faqdesk_id) {
	$faqPath = '';

	$cat_count = prepared_query::fetch('SELECT COUNT(*) FROM faqdesk_to_categories WHERE faqdesk_id = :faqdesk_id', cardinality::SINGLE, [':faqdesk_id' => $faqdesk_id]);

	if ($cat_count == 1) {
		$categories = array();

		$cat_id = prepared_query::fetch('SELECT categories_id FROM faqdesk_to_categories WHERE faqdesk_id = :faqdesk_id', cardinality::SINGLE, [':faqdesk_id' => $faqdesk_id]);
		faqdesk_get_parent_categories($categories, $cat_id);

		$size = sizeof($categories)-1;
		for ($i = $size; $i >= 0; $i--) {
			if ($faqPath != '') $faqPath .= '_';
				$faqPath .= $categories[$i];
		}
		if ($faqPath != '') $faqPath .= '_';
		$faqPath .= $cat_id;
	}

	return $faqPath;
}

// -------------------------------------------------------------------------------------------------------------------------------------------------------------
// Recursively go through the categories and retreive all parent categories IDs
// TABLES: categories
function faqdesk_get_parent_categories(&$categories, $categories_id) {
	$parent_categories = prepared_query::fetch('SELECT parent_id FROM faqdesk_categories WHERE categories_id = :categories_id AND parent_id != 0', cardinality::COLUMN, [':categories_id' => $categories_id]);

	foreach ($parent_categories as $parent_id) {
		$categories[sizeof($categories)] = $parent_id;
		if ($parent_id != $categories_id) {
			faqdesk_get_parent_categories($categories, $parent_id);
		}
	}
}
?>
