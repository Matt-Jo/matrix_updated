<?php
 require('includes/application_top.php');

function get_parent_checklist($product_id) {
	$pa_parent_addons_query = prepared_query::fetch("SELECT pa.product_id, p2c.categories_id from product_addons pa left join products_to_categories p2c on pa.product_id=p2c.products_id where pa.product_addon_id = :products_id", cardinality::SET, [':products_id' => $product_id]);

	$parent_checked_list = [];
	$parent_checked_list['products'] = [];
	$parent_checked_list['categories'] = [];

	foreach ($pa_parent_addons_query as $pa_parent_addon_values) {
		$parentId = $pa_parent_addon_values['product_id'];
		array_push($parent_checked_list['products'], $parentId);

		$catId = $pa_parent_addon_values['categories_id'];
		array_push($parent_checked_list['categories'], $catId);
	}

	return $parent_checked_list;
}

function get_child_checklist($product_id) {
	$pa_child_addons_query = prepared_query::fetch("SELECT pa.product_addon_id, p2c.categories_id from product_addons pa left join products_to_categories p2c on pa.product_addon_id=p2c.products_id where pa.product_id = :products_id", cardinality::SET, [':products_id' => $product_id]);

	$child_checked_list = [];
	$child_checked_list['products'] = [];
	$child_checked_list['categories'] = [];

	foreach ($pa_child_addons_query as $pa_child_addon_values) {
		$catId = $pa_child_addon_values['categories_id'];
		array_push($child_checked_list['categories'], $catId);

		$childId = $pa_child_addon_values['product_addon_id'];
		array_push($child_checked_list['products'], $childId);
	}

	return $child_checked_list;
}

function get_tree_node() {
	if ($_GET['check_type']=='parent') $check_list=get_parent_checklist($_GET['pID']);
	else $check_list=get_child_checklist($_GET['pID']);

	echo '{"ResultSet":{"Result":[';

	$category_query = prepared_query::fetch("select c.categories_id, cd.categories_name, c.sort_order from categories c, categories_description cd where c.parent_id = :parent_id and c.categories_id = cd.categories_id and cd.language_id = '1' order by c.sort_order, cd.categories_name", cardinality::SET, [':parent_id' => $_GET['parent_node']]);

	$first=true;

	foreach ($category_query as $categories) {
		if (in_array($categories['categories_id'], $check_list['categories'])) $checked=1;
		else $checked=0;

		if (!$first) echo ",";
		else $first=false;

		echo "{
			type:\"category\",
			category_id:\"".addslashes($categories['categories_id'])."\",
			category_name:\"".addslashes($categories['categories_name'])."\",
			checked: \"".$checked."\"
		}";
	}

	$product_query = prepared_query::fetch("select p.products_id, pd.products_name from products p, products_description pd, products_to_categories p2c where p.products_id = pd.products_id and pd.language_id = '1' and p.products_id = p2c.products_id and p2c.categories_id = :categories_id order by pd.products_name", cardinality::SET, [':categories_id' => $_GET['parent_node']]);

	foreach ($product_query as $products) {
		if (in_array($products['products_id'], $check_list['products'])) $checked=1;
		else $checked=0;

		if (!$first) echo ",";
		else $first=false;

		echo "{
			type:\"product\",
			product_name: \"".addslashes($products['products_name'])."\",
			product_id:\"".addslashes($products['products_id'])."\",
			checked: \"".$checked."\",
			Click: \"\",
			Unclick: \"\"
		}";
	}

	echo ']}}';
}

$action=$_GET['action'];

switch ($action) {
	case 'get_tree_node':
		get_tree_node();
		break;
	default:
		break;
}
