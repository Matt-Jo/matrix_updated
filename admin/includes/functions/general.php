<?php
require_once(dirname(__FILE__).'/../../../includes/functions/specials.php');

function tep_admin_check_login() {
	global $PHP_SELF;

	if (!empty($GLOBALS['skip_check'])) return;

	if (empty($_SESSION['login_id'])) {
		$_SESSION['login_target'] = $_SERVER['REQUEST_URI'];
		CK\fn::redirect_and_exit('/admin/login.php');
	}
	else {
		if ($_SESSION['login_groups_id'] == 1) return;

		$filename = basename($PHP_SELF);

		$allowed_files = [
			'index.php',
			'index.html',
			'forbiden.php',
			'logoff.php',
			'admin_account.php',
			'packingslip.php',
			'invoice.php',
			'front_controller.php',
			'dev-update-db.dev.php',
			'dev-perform-update.dev.php',
		];

		if (!in_array($filename, $allowed_files)) {
			$db_file = prepared_query::fetch('SELECT admin_files_name FROM admin_files WHERE (FIND_IN_SET(:admin_groups_id, admin_groups_id) OR admin_id = :admin_id) AND admin_files_name = :filename', cardinality::SINGLE, [':admin_groups_id' => $_SESSION['login_groups_id'], ':admin_id' => $_SESSION['login_id'], ':filename' => $filename]);
			if (empty($db_file)) CK\fn::redirect_and_exit('/admin/forbiden.php?attempted_access=['.$filename.']');
		}
	}
}

//Return 'true' or 'false' value to display boxes and files in index.php and column_left.php
function tep_admin_check_boxes($filename, $boxes='') {
	// If we're dealing with a top admin, grant access to everything
	if ($_SESSION['login_groups_id'] == 1) return TRUE;

	$db_file = prepared_query::fetch('SELECT admin_files_id FROM admin_files WHERE (FIND_IN_SET(:admin_groups_id, admin_groups_id) OR admin_id = :admin_id) and admin_files_name = :filename', cardinality::SINGLE, [':admin_groups_id' => $_SESSION['login_groups_id'], ':admin_id' => $_SESSION['login_id'], ':filename' => $filename]);

	$return_value = FALSE;
	if (!empty($db_file)) $return_value = TRUE;
	return $return_value;
}

function tep_output_string($string, $translate = false, $protected = false) {
	if ($protected == true) return htmlspecialchars($string);
	else {
		if ($translate == false) return strtr(trim($string), array('"' => '&quot;'));
		else return strtr(trim($string), $translate);
	}
}

function tep_output_string_protected($string) {
	return tep_output_string($string, false, true);
}

function tep_get_all_get_params($exclude_array = '') {

	if ($exclude_array == '') $exclude_array = array();

	$get_url = '';

	foreach ($_GET as $key => $value) {
		if (($key != session_name()) && ($key != 'error') && (!in_array($key, $exclude_array))) $get_url .= $key.'='.$value.'&';
	}

	return $get_url;
}

function tep_get_category_tree($parent_id = '0', $spacing = '', $exclude = '', $category_tree_array = '', $include_itself = false) {
	if (!is_array($category_tree_array)) $category_tree_array = array();
	if ( (sizeof($category_tree_array) < 1) && ($exclude != '0') ) $category_tree_array[] = array('id' => '0', 'text' => TEXT_TOP);

	if (!empty($include_itself)) {
		$category = prepared_query::fetch('SELECT cd.categories_name FROM categories_description cd WHERE cd.language_id = 1 AND cd.categories_id = :parent_id', cardinality::SINGLE, [':parent_id' => $parent_id]);
		$category_tree_array[] = array('id' => $parent_id, 'text' => $category);
	}

	// yes, categorieses - just trying not to have to rename every inappropriately named variable in the foreach loop
	$categorieses = prepared_query::fetch('SELECT c.categories_id, cd.categories_name, c.parent_id FROM categories c, categories_description cd WHERE c.categories_id = cd.categories_id AND cd.language_id = 1 AND c.parent_id = :parent_id ORDER BY c.sort_order, cd.categories_name', cardinality::SET, [':parent_id' => $parent_id]);
	foreach ($categorieses as $categories) {
		if ($exclude != $categories['categories_id']) $category_tree_array[] = array('id' => $categories['categories_id'], 'text' => $spacing.$categories['categories_name']);
		$category_tree_array = tep_get_category_tree($categories['categories_id'], $spacing.'&nbsp;&nbsp;&nbsp;', $exclude, $category_tree_array);
	}

	return $category_tree_array;
}

function tep_info_image($image, $alt, $width = '', $height = '') {
	if (tep_not_null($image) && (file_exists(DIR_FS_CATALOG_IMAGES.$image)) ) $image = tep_image(DIR_WS_CATALOG_IMAGES.$image, $alt, $width, $height);
	else $image = @TEXT_IMAGE_NONEXISTENT;

	return $image;
}

function tep_not_null($value) {
	if (is_array($value)) {
		if (sizeof($value) > 0) return true;
		else return false;
	}
	else {
		if ( (is_string($value) || is_int($value)) && ($value != '') && ($value != 'NULL') && (strlen(trim($value)) > 0)) return true;
		else return false;
	}
}

function tep_address_format($address_format_id, $address, $html, $boln, $eoln) {
	$address_format = prepared_query::fetch("select address_format as format from address_format where address_format_id = :address_format_id", cardinality::SINGLE, [':address_format_id' => $address_format_id]);

	$company = tep_output_string_protected($address['company']);
	if (isset($address['firstname']) && tep_not_null($address['firstname'])) {
		$firstname = tep_output_string_protected($address['firstname']);
		$lastname = tep_output_string_protected($address['lastname']);
	}
	elseif (isset($address['name']) && tep_not_null($address['name'])) {
		$firstname = tep_output_string_protected($address['name']);
		$lastname = '';
	}
	else {
		$firstname = '';
		$lastname = '';
	}
	$street = tep_output_string_protected($address['street_address']);
	$suburb = tep_output_string_protected($address['suburb']);
	$city = tep_output_string_protected($address['city']);
	$state = tep_output_string_protected($address['state']);
	if (isset($address['country_id']) && tep_not_null($address['country_id'])) {
		$country = ck_address2::legacy_get_country_field($address['country_id'], 'countries_name');

		if (isset($address['zone_id']) && tep_not_null($address['zone_id'])) {
			$state = ck_address2::legacy_get_zone_field($address['country_id'], $address['zone_id'], $state, 'zone_code');
		}
	}
	elseif (isset($address['country']) && tep_not_null($address['country'])) {
		$country = tep_output_string_protected($address['country']);
	}
	else {
		$country = '';
	}
	$postcode = tep_output_string_protected($address['postcode']);
	$zip = $postcode;

	if (!empty($html)) {
		// HTML Mode
		$HR = '<hr>';
		$hr = '<hr>';
		if ( ($boln == '') && ($eoln == "\n") ) { // Values not specified, use rational defaults
			$CR = '<br>';
			$cr = '<br>';
			$eoln = $cr;
		}
		else { // Use values supplied
			$CR = $eoln.$boln;
			$cr = $CR;
		}
	}
	else {
		// Text Mode
		$CR = $eoln;
		$cr = $CR;
		$HR = '----------------------------------------';
		$hr = '----------------------------------------';
	}

	$statecomma = '';
	$streets = $street;
	if ($suburb != '') $streets = $street.$cr.$suburb;
	if ($country == '') $country = tep_output_string_protected($address['country']);
	if ($state != '') $statecomma = $state.', ';

	$fmt = $address_format;
	@eval("\$address = \"$fmt\";");

	if ( (ACCOUNT_COMPANY == 'true') && (tep_not_null($company)) ) {
		$address = $company.$cr.$address;
	}

	return $address;
}

function tep_products_in_category_count($categories_id, $include_deactivated = false) {
	$products_count = 0;

	if (!empty($include_deactivated)) $products_count += prepared_query::fetch("select count(*) as total from products p, products_to_categories p2c where p.products_id = p2c.products_id and p2c.categories_id = :categories_id", cardinality::SINGLE, [':categories_id' => $categories_id]);
	else $products_count += prepared_query::fetch("select count(*) as total from products p, products_to_categories p2c where p.products_id = p2c.products_id and p.products_status = '1' and p2c.categories_id = :categories_id", cardinality::SINGLE, [':categories_id' => $categories_id]);

	if ($children = prepared_query::fetch("select categories_id from categories where parent_id = :parent_id", cardinality::COLUMN, [':parent_id' => $categories_id])) {
		foreach ($children as $childs) {
			$products_count += tep_products_in_category_count($childs, $include_deactivated);
		}
	}

	return $products_count;
}

function tep_childs_in_category_count($categories_id) {
	$categories_count = 0;

	$categories_set = prepared_query::fetch("select categories_id from categories where parent_id = :parent_id", cardinality::COLUMN, [':parent_id' => $categories_id]);
	foreach ($categories_set as $categories) {
		$categories_count++;
		$categories_count += tep_childs_in_category_count($categories);
	}

	return $categories_count;
}

function tep_get_countries($default = '') {
	$countries_array = [];
	if (!empty($default)) $countries_array[] = ['id' => '', 'text' => $default];
	$countries_set = prepared_query::fetch("select countries_id as id, countries_name as text from countries order by countries_name", cardinality::SET);
	foreach ($countries_set as $countries) {
		$countries_array[] = $countries;
	}

	return $countries_array;
}

function tep_get_po_terms($default = '') {
	$po_terms_array = array();
	if (!empty($default)) {
		$po_terms_array[] = array('id' => '', 'text' => $default);
	}
	$po_terms_query = prepared_query::fetch("select id, text from purchase_order_terms order by ordinal", cardinality::SET);
	foreach ($po_terms_query as $po_terms) {
		$po_terms_array[] = array('id' => $po_terms['id'], 'text' => $po_terms['text']);
	}

	return $po_terms_array;
}

function tep_set_product_status($products_id, $status) {
	if ($status == '1') return prepared_query::execute("update products set products_status = '1', products_last_modified = now() where products_id = :products_id", [':products_id' => $products_id]);
	elseif ($status == '0') return prepared_query::execute("update products set products_status = '0', products_last_modified = now() where products_id = :products_id", [':products_id' => $products_id]);
	else return -1;
}

function tep_set_time_limit($limit) {
	if (!get_cfg_var('safe_mode')) set_time_limit($limit);
}

function tep_generate_category_path($id, $from = 'category', $categories_array = '', $index = 0) {

	if (!is_array($categories_array)) $categories_array = [];

	$category = prepared_query::fetch('SELECT cd.categories_name, c.parent_id FROM categories c, categories_description cd WHERE c.categories_id = :categories_id AND c.categories_id = cd.categories_id AND cd.language_id = 1', cardinality::ROW, [':categories_id' => $id]);
	$categories_array[$index][] = ['id' => $id, 'text' => $category['categories_name']];
	if ( (tep_not_null($category['parent_id'])) && ($category['parent_id'] != '0') ) $categories_array = tep_generate_category_path($category['parent_id'], 'category', $categories_array, $index);

	return $categories_array;
}

function tep_output_generated_category_path($id, $from = 'category') {
	$calculated_category_path_string = '';
	$calculated_category_path = tep_generate_category_path($id, $from);
	for ($i=0, $n=sizeof($calculated_category_path); $i<$n; $i++) {
		for ($j=0, $k=sizeof($calculated_category_path[$i]); $j<$k; $j++) {
			$calculated_category_path_string .= $calculated_category_path[$i][$j]['text'].'&nbsp;&gt;&nbsp;';
		}
		$calculated_category_path_string = substr($calculated_category_path_string, 0, -16).'<br>';
	}
	$calculated_category_path_string = substr($calculated_category_path_string, 0, -4);

	if (strlen($calculated_category_path_string) < 1) $calculated_category_path_string = TEXT_TOP;

	return $calculated_category_path_string;
}

function tep_get_generated_category_path_ids($id, $from = 'category') {
	$calculated_category_path_string = '';
	$calculated_category_path = tep_generate_category_path($id, $from);
	for ($i=0, $n=sizeof($calculated_category_path); $i<$n; $i++) {
		for ($j=0, $k=sizeof($calculated_category_path[$i]); $j<$k; $j++) {
			$calculated_category_path_string .= $calculated_category_path[$i][$j]['id'].'_';
		}
		$calculated_category_path_string = substr($calculated_category_path_string, 0, -1).'<br>';
	}
	$calculated_category_path_string = substr($calculated_category_path_string, 0, -4);

	if (strlen($calculated_category_path_string) < 1) $calculated_category_path_string = TEXT_TOP;

	return $calculated_category_path_string;
}

function tep_remove_category($category_id) {
	$category_image = prepared_query::fetch("select categories_image from categories where categories_id = :categories_id", cardinality::SINGLE, [':categories_id' => $category_id]);

	$duplicate_image = prepared_query::fetch("select count(*) as total from categories where categories_image = :category_image", cardinality::SINGLE, [':category_image' => $category_image]);

	if ($duplicate_image < 2) {
		if (file_exists(DIR_FS_CATALOG_IMAGES.$category_image)) {
			@unlink(DIR_FS_CATALOG_IMAGES.$category_image);
		}
	}

	prepared_query::execute("delete from categories where categories_id = :categories_id", [':categories_id' => $category_id]);
	prepared_query::execute("delete from categories_description where categories_id = :categories_id", [':categories_id' => $category_id]);
	prepared_query::execute("delete from products_to_categories where categories_id = :categories_id", [':categories_id' => $category_id]);
}

function tep_remove_product($product_id) {
	$product_image = prepared_query::fetch('SELECT products_image FROM products WHERE products_id = :products_id', cardinality::SINGLE, [':products_id' => $product_id]);

	$duplicate_image = prepared_query::fetch('SELECT COUNT(*) as total FROM products WHERE products_image = :products_image AND products_id != :products_id', cardinality::SINGLE, [':products_image' => $product_image, ':products_id' => $product_id]);

	if (empty($duplicate_image)) {
		if (file_exists(DIR_FS_CATALOG_IMAGES.$product_image)) {
			@unlink(DIR_FS_CATALOG_IMAGES.$product_image);
		}
	}

	prepared_query::execute('DELETE FROM specials WHERE products_id = :products_id', [':products_id' => $product_id]);
	prepared_query::execute('DELETE FROM products WHERE products_id = :products_id', [':products_id' => $product_id]);
	prepared_query::execute('DELETE FROM products_to_categories WHERE products_id = :products_id', [':products_id' => $product_id]);
	prepared_query::execute('DELETE FROM products_description WHERE products_id = :products_id', [':products_id' => $product_id]);
	prepared_query::execute('DELETE rd, r FROM reviews r JOIN reviews_description rd ON r.reviews_id = rd.reviews_id WHERE r.products_id = :products_id', [':products_id' => $product_id]);
}

function tep_parse_category_path($cPath) {
	// make sure the category IDs are integers
	$cPath_array = array_map(function($id) { return (int)$id; }, explode('_', $cPath));

	// make sure no duplicate category IDs exist which could lock the server in a loop
	$tmp_array = array();
	$n = sizeof($cPath_array);
	for ($i=0; $i<$n; $i++) {
		if (!in_array($cPath_array[$i], $tmp_array)) {
			$tmp_array[] = $cPath_array[$i];
		}
	}

	return $tmp_array;
} ?>
