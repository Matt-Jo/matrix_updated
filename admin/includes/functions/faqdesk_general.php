<?php
function faqdesk_draw_file_field($name, $parameters = '', $required = false) {
	$field = tep_draw_input_field($name, '', $parameters, $required, 'file');
	return $field;
}

function faqdesk_get_path($current_category_id = '') {
	global $cPath_array;

	if ($current_category_id == '') $cPath_new = implode('_', $cPath_array);
	else {
		if (sizeof($cPath_array) == 0) $cPath_new = $current_category_id;
		else {
			$cPath_new = '';
			$last_category = prepared_query::fetch("select parent_id from faqdesk_categories where categories_id = :categories_id", cardinality::SINGLE, [':categories_id' => $cPath_array[(sizeof($cPath_array)-1)]]);
			$current_category = prepared_query::fetch("select parent_id from faqdesk_categories where categories_id = :categories_id", cardinality::SINGLE, [':categories_id' => $current_category_id]);

			if ($last_category == $current_category) {
				for ($i = 0, $n = sizeof($cPath_array) - 1; $i < $n; $i++) {
					$cPath_new .= '_'.$cPath_array[$i];
				}
			}
			else {
				for ($i = 0, $n = sizeof($cPath_array); $i < $n; $i++) {
					$cPath_new .= '_'.$cPath_array[$i];
				}
			}

			$cPath_new .= '_'.$current_category_id;
			if (substr($cPath_new, 0, 1) == '_') {
				$cPath_new = substr($cPath_new, 1);
			}
		}
	}

	return 'cPath='.$cPath_new;
}

function faqdesk_output_generated_category_path($id, $from = 'category') {
	$calculated_category_path_string = '';
	$calculated_category_path = faqdesk_generate_category_path($id, $from);

	for ($i = 0, $n = sizeof($calculated_category_path); $i < $n; $i++) {
		for ($j = 0, $k = sizeof($calculated_category_path[$i]); $j < $k; $j++) {
			$calculated_category_path_string .= $calculated_category_path[$i][$j]['text'].'&nbsp;&gt;&nbsp;';
		}

		$calculated_category_path_string = substr($calculated_category_path_string, 0, -16).'<br>';
	}

	$calculated_category_path_string = substr($calculated_category_path_string, 0, -4);

	if (strlen($calculated_category_path_string) < 1) $calculated_category_path_string = TEXT_TOP;

	return $calculated_category_path_string;
}

function faqdesk_get_faqdesk_question($product_id, $language_id = 0) {
	$product = prepared_query::fetch("select faqdesk_question from faqdesk_description where faqdesk_id = :faqdesk_id and language_id = 1", cardinality::SINGLE, [':faqdesk_id' => $product_id]);
	return $product;
}

function faqdesk_get_category_tree($parent_id = '0', $spacing = '', $exclude = '', $category_tree_array = '', $include_itself = false) {
	if (!is_array($category_tree_array)) $category_tree_array = array();
	if ( (sizeof($category_tree_array) < 1) && ($exclude != '0') ) $category_tree_array[] = array('id' => '0', 'text' => TEXT_TOP);

	if (!empty($include_itself)) {
		$category = prepared_query::fetch("select cd.categories_name from faqdesk_categories_description cd where cd.language_id = 1 and cd.categories_id = :categories_id", cardinality::SINGLE, [':categories_id' => $parent_id]);
		$category_tree_array[] = array('id' => $parent_id, 'text' => $category);
	}

	$categories_set = prepared_query::fetch("select c.categories_id, cd.categories_name, c.parent_id from faqdesk_categories c, faqdesk_categories_description cd where c.categories_id = cd.categories_id and cd.language_id = 1 and c.parent_id = :parent_id order by c.sort_order, cd.categories_name", cardinality::SET, [':parent_id' => $parent_id]);

	foreach ($categories_set as $categories) {
		if ($exclude != $categories['categories_id']) $category_tree_array[] = array('id' => $categories['categories_id'], 'text' => $spacing.$categories['categories_name']);
		$category_tree_array = faqdesk_get_category_tree($categories['categories_id'], $spacing.'&nbsp;&nbsp;&nbsp;', $exclude, $category_tree_array);
	}

	return $category_tree_array;
}

function faqdesk_get_category_name($category_id, $language_id) {
	$category = prepared_query::fetch("select categories_name from faqdesk_categories_description where categories_id = :categories_id and language_id = 1", cardinality::SINGLE, [':categories_id' => $category_id]);
	return $category;
}

function faqdesk_get_category_description($category_id, $language_id) {
	$category = prepared_query::fetch("select categories_description from faqdesk_categories_description where categories_id = :categories_id and language_id = 1", cardinality::SINGLE, [':categories_id' => $category_id]);
	return $category;
}

function faqdesk_get_faqdesk_answer_long($product_id, $language_id) {
	$product = prepared_query::fetch("select faqdesk_answer_long from faqdesk_description where faqdesk_id = :faqdesk_id and language_id = 1", cardinality::SINGLE, [':faqdesk_id' => $product_id]);
	return $product;
}

function faqdesk_get_faqdesk_answer_short($product_id, $language_id) {
	$product = prepared_query::fetch("select faqdesk_answer_short from faqdesk_description where faqdesk_id = :faqdesk_id and language_id = 1", cardinality::SINGLE, [':faqdesk_id' => $product_id]);
	return $product;
}

// Count how many products exist in a category
// TABLES: products, products_to_categories, categories
function faqdesk_products_in_category_count($categories_id, $include_deactivated = false) {
	$products_count = 0;

	if (!empty($include_deactivated)) {
		$products = prepared_query::fetch("select count(*) as total from faqdesk p, faqdesk_to_categories p2c where p.faqdesk_id = p2c.faqdesk_id and p2c.categories_id = :categories_id", cardinality::SINGLE, [':categories_id' => $categories_id]);
	}
	else {
		$products = prepared_query::fetch("select count(*) as total from faqdesk p, faqdesk_to_categories p2c where p.faqdesk_id = p2c.faqdesk_id and p.faqdesk_status = '1' and p2c.categories_id = :categories_id", cardinality::SINGLE, [':categories_id' => $categories_id]);
	}

	$products_count += $products;

	if ($children = prepared_query::fetch("select categories_id from faqdesk_categories where parent_id = :parent_id", cardinality::COLUMN, [':parent_id' => $categories_id])) {
		foreach ($children as $childs) {
			$products_count += faqdesk_products_in_category_count($childs, $include_deactivated);
		}
	}

	return $products_count;
}

// Count how many subcategories exist in a category
// TABLES: categories
function faqdesk_childs_in_category_count($categories_id) {
	$categories_count = 0;

	$categories_set = prepared_query::fetch("select categories_id from faqdesk_categories where parent_id = :parent_id", cardinality::COLUMN, [':parent_id' => $categories_id]);
	foreach ($categories_set as $categories) {
		$categories_count++;
		$categories_count += faqdesk_childs_in_category_count($categories);
	}

	return $categories_count;
}

function faqdesk_generate_category_path($id, $from = 'category', $categories_array = '', $index = 0) {
	if (!is_array($categories_array)) $categories_array = array();

	if ($from == 'product') {
		$categories_set = prepared_query::fetch("select categories_id from faqdesk_to_categories where faqdesk_id = :faqdesk_id", cardinality::COLUMN, [':faqdesk_id' => $id]);
		foreach ($categories_set as $categories) {
			if ($categories == '0') $categories_array[$index][] = array('id' => '0', 'text' => TEXT_TOP);
			else {
				$category = prepared_query::fetch("select cd.categories_name, c.parent_id from faqdesk_categories c, faqdesk_categories_description cd where c.categories_id = :categories_id and c.categories_id = cd.categories_id and cd.language_id = 1", cardinality::ROW, [':categories_id' => $categories]);
				$categories_array[$index][] = array('id' => $categories, 'text' => $category['categories_name']);
				if ( (tep_not_null($category['parent_id'])) && ($category['parent_id'] != '0') ) $categories_array = faqdesk_generate_category_path($category['parent_id'], 'category', $categories_array, $index);
				$categories_array[$index] = array_reverse($categories_array[$index]);
			}
			$index++;
		}
	}
	elseif ($from == 'category') {
		$category = prepared_query::fetch("select cd.categories_name, c.parent_id from faqdesk_categories c, faqdesk_categories_description cd where c.categories_id = :categories_id and c.categories_id = cd.categories_id and cd.language_id = 1", cardinality::ROW, [':categories_id' => $id]);
		$categories_array[$index][] = array('id' => $id, 'text' => $category['categories_name']);
		if ( (tep_not_null($category['parent_id'])) && ($category['parent_id'] != '0') ) $categories_array = faqdesk_generate_category_path($category['parent_id'], 'category', $categories_array, $index);
	}

	return $categories_array;
}

function faqdesk_remove_category($category_id) {
	$category_image = prepared_query::fetch("select categories_image from faqdesk_categories where categories_id = :category_id", cardinality::SINGLE, [':category_id' => $category_id]);
	$duplicate_image = prepared_query::fetch("select count(*) as total from faqdesk_categories where categories_image = :category_image", cardinality::SINGLE, [':category_image' => $category_image]);

	if ($duplicate_image < 2) {
		if (file_exists(DIR_FS_CATALOG_IMAGES.$category_image)) {
			@unlink(DIR_FS_CATALOG_IMAGES.$category_image);
		}
	}

	prepared_query::execute("delete from faqdesk_categories where categories_id = :categories_id", [':categories_id' => $category_id]);
	prepared_query::execute("delete from faqdesk_categories_description where categories_id = :categories_id", [':categories_id' => $category_id]);
	prepared_query::execute("delete from faqdesk_to_categories where categories_id = :categories_id", [':categories_id' => $category_id]);
}

function faqdesk_remove_product($product_id) {
	$product_image = prepared_query::fetch("select faqdesk_image, faqdesk_image_two, faqdesk_image_three from faqdesk where faqdesk_id = :faqdesk_id", cardinality::ROW, [':faqdesk_id' => $product_id]);

	$duplicate_image = prepared_query::fetch("select count(*) as total from faqdesk where faqdesk_image = :faqdesk_image", cardinality::SINGLE, [':faqdesk_image' => $product_image['faqdesk_image']]);
	if ($duplicate_image < 2) {
		if (file_exists(DIR_FS_CATALOG_IMAGES.$product_image['faqdesk_image'])) {
			@unlink(DIR_FS_CATALOG_IMAGES.$product_image['faqdesk_image']);
		}
	}

	$duplicate_image_two = prepared_query::fetch("select count(*) as total from faqdesk where faqdesk_image_two = :faqdesk_image", cardinality::SINGLE, [':faqdesk_image' => $product_image['faqdesk_image_two']]);
	if ($duplicate_image_two < 2) {
		if (file_exists(DIR_FS_CATALOG_IMAGES.$product_image['faqdesk_image_two'])) {
			@unlink(DIR_FS_CATALOG_IMAGES.$product_image['faqdesk_image_two']);
		}
	}

	$duplicate_image_three = prepared_query::fetch("select count(*) as total from faqdesk where faqdesk_image_three = :faqdesk_image", cardinality::SINGLE, [':faqdesk_image' => $product_image['faqdesk_image_three']]);
	if ($duplicate_image_three < 2) {
		if (file_exists(DIR_FS_CATALOG_IMAGES.$product_image['faqdesk_image_three'])) {
			@unlink(DIR_FS_CATALOG_IMAGES.$product_image['faqdesk_image_three']);
		}
	}

	prepared_query::execute("delete from faqdesk where faqdesk_id = :faqdesk_id", [':faqdesk_id' => $product_id]);
	prepared_query::execute("delete from faqdesk_to_categories where faqdesk_id = :faqdesk_id", [':faqdesk_id' => $product_id]);
	prepared_query::execute("delete from faqdesk_description where faqdesk_id = :faqdesk_id", [':faqdesk_id' => $product_id]);
}

// Sets the status of a product
function faqdesk_set_product_status($faqdesk_id, $status) {
	if ($status == '1') return prepared_query::execute("update faqdesk set faqdesk_status = '1', faqdesk_last_modified = now() where faqdesk_id = :faqdesk_id", [':faqdesk_id' => $faqdesk_id]);
	elseif ($status == '0') return prepared_query::execute("update faqdesk set faqdesk_status = '0', faqdesk_last_modified = now() where faqdesk_id = :faqdesk_id", [':faqdesk_id' => $faqdesk_id]);
	else return -1;
}

function faqdesk_get_faqdesk_extra_url($product_id, $language_id) {
	$product = prepared_query::fetch("select faqdesk_extra_url from faqdesk_description where faqdesk_id = :faqdesk_id and language_id = 1", cardinality::SINGLE, [':faqdesk_id' => $product_id]);
	return $product;
}

function faqdesk_get_faqdesk_extra_url_name($product_id, $language_id) {
	$product = prepared_query::fetch("select faqdesk_extra_url_name from faqdesk_description where faqdesk_id = :faqdesk_id and language_id = 1", cardinality::SINGLE, [':faqdesk_id' => $product_id]);
	return $product;
}

function faqdesk_get_products_name($faqdesk_id, $language_id = 0) {
	$product = prepared_query::fetch("select faqdesk_question from faqdesk_description where faqdesk_id = :faqdesk_id and language_id = 1", cardinality::SINGLE, [':faqdesk_id' => $product_id]);
	return $product;
}

// Output a form textarea field
function faqdesk_draw_textarea_field($name, $wrap, $width, $height, $text = '', $parameters = '', $reinsert_value = true) {
	$field = '<textarea name="'.faqdesk_parse_input_field_data($name, array('"' => '&quot;')).'" wrap="'.faqdesk_parse_input_field_data($wrap, array('"' => '&quot;')).'" cols="'.faqdesk_parse_input_field_data($width, array('"' => '&quot;')).'" rows="'.faqdesk_parse_input_field_data($height, array('"' => '&quot;')).'"';

	if (faqdesk_not_null($parameters)) $field .= ' '.$parameters;

	//	$field .= 'class="post" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);"';
	$field .= 'ONSELECT="Javascript:storeCaret(this);" ONCLICK="Javascript:storeCaret(this);" ONKEYUP="Javascript:storeCaret(this);" ONCHANGE="Javascript:storeCaret(this);"';
	$field .= '>';

	if ( (isset($GLOBALS[$name])) && ($reinsert_value == true) ) $field .= $GLOBALS[$name];
	elseif (faqdesk_not_null($text)) $field .= $text;

	$field .= '</textarea>';

	return $field;
}

// Parse the data used in the html tags to ensure the tags will not break
function faqdesk_parse_input_field_data($data, $parse) {
	return strtr(trim($data), $parse);
}

function faqdesk_not_null($value) {
	if (is_array($value)) {
		if (sizeof($value) > 0) return true;
		else return false;
	}
	else {
		if (($value != '') && ($value != 'NULL') && (strlen(trim($value)) > 0)) return true;
		else return false;
	}
}

function faqdesk_set_categories_status($categories_id, $status) {
	if ($status == '1') return prepared_query::execute("update faqdesk_categories set catagory_status = '1' where categories_id = :categories_id", [':categories_id' => $categories_id]);
	elseif ($status == '0') return prepared_query::execute("update faqdesk_categories set catagory_status = '0' where categories_id = :categories_id", [':categories_id' => $categories_id]);
	else return -1;
}

function faqdesk_get_faqdesk_image_text($product_id, $language_id = 0) {
	$product = prepared_query::fetch("select faqdesk_image_text from faqdesk_description where faqdesk_id = :faqdesk_id and language_id = 1", cardinality::SINGLE, [':faqdesk_id' => $product_id]);
	return $product;
}

function faqdesk_get_faqdesk_image_text_two($product_id, $language_id = 0) {
	$product = prepared_query::fetch("select faqdesk_image_text_two from faqdesk_description where faqdesk_id = :faqdesk_id and language_id = 1", cardinality::SINGLE, [':faqdesk_id' => $product_id]);
	return $product;
}

function faqdesk_get_faqdesk_image_text_three($product_id, $language_id = 0) {
	$product = prepared_query::fetch("select faqdesk_image_text_three from faqdesk_description where faqdesk_id = :faqdesk_id and language_id = 1", cardinality::SINGLE, [':faqdesk_id' => $product_id]);
	return $product;
}

// -----------------------------------------------------------------------
// Sets the sticky of a product
// -----------------------------------------------------------------------
function faqdesk_set_product_sticky($faqdesk_id, $sticky) {
	if ($sticky == '1') return prepared_query::execute("update faqdesk set faqdesk_sticky = '1', faqdesk_last_modified = now() where faqdesk_id = :faqdesk_id", [':faqdesk_id' => $faqdesk_id]);
	elseif ($sticky == '0') return prepared_query::execute("update faqdesk set faqdesk_sticky = '0', faqdesk_last_modified = now() where faqdesk_id = :faqdesk_id", [':faqdesk_id' => $faqdesk_id]);
	else return -1;
}

// -----------------------------------------------------------------------
// nl2br >> br2nl ... stripbreaks code found on php.net forum
// -----------------------------------------------------------------------
function stripbr($str) {
	$str = preg_replace('#<BR\s*/?\s*>#',"",$str);
	return $str;
}

// -----------------------------------------------------------------------
// upload file function (taken from loaded 5)
// -----------------------------------------------------------------------
function tep_get_uploaded_file($filename) {
	if (isset($_FILES[$filename])) {
		$uploaded_file = array(
			'name' => $_FILES[$filename]['name'],
			'type' => $_FILES[$filename]['type'],
			'size' => $_FILES[$filename]['size'],
			'tmp_name' => $_FILES[$filename]['tmp_name']
		);
	}
	elseif (isset($GLOBALS['HTTP_POST_FILES'][$filename])) {
		global $HTTP_POST_FILES;
		$uploaded_file = array(
			'name' => $HTTP_POST_FILES[$filename]['name'],
			'type' => $HTTP_POST_FILES[$filename]['type'],
			'size' => $HTTP_POST_FILES[$filename]['size'],
			'tmp_name' => $HTTP_POST_FILES[$filename]['tmp_name']
		);
	}
	else {
		$uploaded_file = array(
			'name' => $GLOBALS[$filename.'_name'],
			'type' => $GLOBALS[$filename.'_type'],
			'size' => $GLOBALS[$filename.'_size'],
			'tmp_name' => $GLOBALS[$filename]
		);
	}

	return $uploaded_file;
}

// -----------------------------------------------------------------------
// return a local directory path (without trailing slash)
// -----------------------------------------------------------------------
function tep_get_local_path($path) {
	if (substr($path, -1) == '/') $path = substr($path, 0, -1);
	return $path;
}

// -----------------------------------------------------------------------
// the $filename parameter is an array with the following elements:
// name, type, size, tmp_name
// -----------------------------------------------------------------------
function tep_copy_uploaded_file($filename, $target) {
	if (substr($target, -1) != '/') $target .= '/';
	$target .= $filename['name'];
	move_uploaded_file($filename['tmp_name'], $target);
	chmod($target, 0777);
}
?>
