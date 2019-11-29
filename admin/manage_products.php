<?php
require('includes/application_top.php');

function build_category_hierarchies() {
	$data = array();
	if ($categories = prepared_query::fetch("SELECT categories_id, parent_id FROM categories ORDER BY categories_id DESC", cardinality::SET)) {
		foreach ($categories as $category) {
			$hierarchy = array();
			if (!empty($category['parent_id'])) {
				build_hierarchy($category['parent_id'], $categories, $hierarchy);
				$children[$category['categories_id']] = build_children($category['categories_id'], $categories);
			}
			$data[$category['categories_id']] = implode('/', array_reverse($hierarchy));
		}
	}
	return array($data, $children);
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

/* This has been replaced by a global helper class
function check_flag($flag) {
	if ($flag === TRUE || in_array(strtolower(trim($flag)), array('y', 'yes', 'on', '1', 't', 'true'))) return TRUE;
	elseif ($flag === FALSE || in_array(strtolower(trim($flag)), array('n', 'no', 'off', '0', 'f', 'false'))) return FALSE;
	else return NULL;
}*/

// we define these here rather than using a closure since it's used in a loop
function reduce_prod_cols(&$val) {
	$val = preg_replace('/products_/', '', $val);
}
function reduce_to_params($val) {
	return '?';
}
function dup_key_upd($col) {
	return "$col=VALUES($col)";
}

set_time_limit(0);
ini_set('memory_limit', '2048M');

$import_run = FALSE;
$output = [];
$errors = [];
$warnings = [];

if (!defined('CREATE')) define('CREATE', 1);
if (!defined('UPDATE')) define('UPDATE', 2);
if (!defined('PERITEM')) define('PERITEM', 4);
//define('IMAGES', 8);
if (!defined('MODELNUM')) define('MODELNUM', 16);

function permission_mask($admin_id, $admin_groups_id) {
	// we're ignoring the group ID for now
	switch ($admin_id) {
		case 81:
			return UPDATE; // | IMAGES;
			break;
		case 117:
			return UPDATE;
			break;
		default:
			switch ($admin_groups_id) {
				default:
					return CREATE | UPDATE | PERITEM /*| IMAGES*/ | MODELNUM;
					break;
			}
	}
}

function checkperm($check) {
	return ($GLOBALS['permission_mask']&$check)==$check?$check:0;
}

// we probably don't need a separate function for this, since we're doing it once at the top of the file, but we might choose to use it differently in the future
$permission_mask = permission_mask($_SESSION['perms']['admin_id'], $_SESSION['perms']['admin_groups_id']);

/*$image_list = array(
	'a_sm' => 'image_a_sm',
	'a_med' => 'image_a_med',
	'a' => 'image_a',
	'b_sm' => 'image_b_sm',
	'b' => 'image_b',
	'c_sm' => 'image_c_sm',
	'c' => 'image_c',
	'd_sm' => 'image_d_sm',
	'd' => 'image_d',
	'e_sm' => 'image_e_sm',
	'e' => 'image_e',
	'f_sm' => 'image_f_sm',
	'f' => 'image_f',
	'g_sm' => 'image_g_sm',
	'g' => 'image_g'
);
$img_db_map = array(
	'image_a_sm' => 'products_image',
	'image_a_med' => 'products_image_med',
	'image_a' => 'products_image_lrg',
	'image_b_sm' => 'products_image_sm_1',
	'image_b' => 'products_image_xl_1',
	'image_c_sm' => 'products_image_sm_2',
	'image_c' => 'products_image_xl_2',
	'image_d_sm' => 'products_image_sm_3',
	'image_d' => 'products_image_xl_3',
	'image_e_sm' => 'products_image_sm_4',
	'image_e' => 'products_image_xl_4',
	'image_f_sm' => 'products_image_sm_5',
	'image_f' => 'products_image_xl_5',
	'image_g_sm' => 'products_image_sm_6',
	'image_g' => 'products_image_xl_6'
);*/

if (!empty($_FILES)) {
	if (!$_POST['skip_rows'] || !is_numeric($_POST['skip_rows'])) {
		$errors[] = 'For this import, you must include a header row we can match against.<br>';
	}
	elseif (!empty($_FILES['product_csvfile'])) {
		$import_run = TRUE;

		if ($_FILES['product_csvfile']['error'] || ($csv = fopen($_FILES['product_csvfile']['tmp_name'], 'r')) === FALSE) {
			$err = 'Sorry, there was a problem with that uploaded file:<br>';
			if ($_FILES['product_csvfile']['error']) $err .= "&nbsp;&nbsp;&nbsp;".$_FILES['product_csvfile']['error'].'<br>';

			$errors[] = $err;
		}
		else {
			$success = $failure = $warning = array('add' => 0, 'update' => 0);

			$create_allowed = $_POST['upload_action'] & checkperm(CREATE);
			$update_allowed = $_POST['upload_action'] & checkperm(UPDATE);
			$per_item_allowed = $_POST['upload_action'] & checkperm(PERITEM);
			//$images_allowed = checkperm(IMAGES);
			$modelnum_allowed = checkperm(MODELNUM);

			$aliases = array_flip($_POST['map']);
			/*$aliases = array(
				'action' => 'upload_action',
				'product_id' => 'products_id',
				'products_model' => 'model_number',
				'products_status' => 'status',
				'manu' => 'manufacturer'
			);*/

			$overwrite_data = CK\fn::check_flag(@$_POST['clear_all']);
			/*$img_ext = $_POST['img_ext'];
			$check_img_slot = CK\fn::check_flag($_POST['add_img_slotsize']);
			$add_img_path = CK\fn::check_flag($_POST['add_img_folder']);
			$img_path = preg_replace('/\./', '', $_POST['img_folder']); // disallow image wrangling that could take us up out of the images directory*/

			if ($_POST['product_context'] == TRUE) {
				$row_count = 0;
				$product_keys = [];
				while (($row = fgetcsv($csv, 0, "\t")) !== FALSE) {
					// skip or consume the column headers
					$row_count++;
					if ($_POST['skip_rows'] > $row_count) { $output[] = "Row $row_count skipped<br>"; continue; }
					if ($_POST['skip_rows'] == $row_count) {
						foreach ($row as $idx => $product_key) {
							if (!$product_key) $product_key = 'column_'.$idx; // we do this so that we don't lose data, but we don't actually have any mechanism to use these columns
							$product_keys[] = isset($aliases[$product_key])?$aliases[$product_key]:$product_key;
						}
						// uniqueness is handled at the assignment stage
						//$product_keys = array_unique($product_keys);
						continue;
					}
					if (!$row) continue;

					// we're in the data. build it with header references
					$data = (object) array();
					foreach ($row as $idx => $value) {
						// if it's not set, just set it directly
						if (!isset($data->{$product_keys[$idx]})) $data->{$product_keys[$idx]} = $value;
						// if it is set and not an array, array-ize it with both the previous and new values
						elseif (!is_array($data->{$product_keys[$idx]})) $data->{$product_keys[$idx]} = array($data->{$product_keys[$idx]}, $value);
						// it's already been set as an array, just add to it
						else $data->{$product_keys[$idx]}[] = $value;
					}

					// the data is built, use it

					// manage permissions for this item
					if (!empty($per_item_allowed)) {
						// set our permission flag per item
						$pi_create = preg_match('/create/i', @$data->upload_action)?1:0;
						$pi_update = preg_match('/update/i', @$data->upload_action)?1:0;
						if ($pi_create || $pi_update) { // if at least one of them is set, override the global settings
							$create_allowed = $pi_create;
							$update_allowed = $pi_update;
						}
						// if neither are set, and they're not set (or allowed) globally, then we have a problem
					}
					// if we've specified that the permissions should be set per item without a default, and we didn't set it for this item, error
					if (!$create_allowed && !$update_allowed) {
						$errors[] = '[LINE '.$row_count.']: Please specify whether this item is allowed to be created and/or updated.<br>';
						continue;
					}

					// check for or build all necessary identifiers for the IPN and product listing
					// IPN/Stock ID check happens first and is irrespective of update or create context, we need to know which IPN the listing is attached to
					if (empty($data->stock_id) && empty($data->ipn) && empty($data->products_id)) {
						$errors[] = '[LINE '.$row_count.']: You must specify either the stock_id or the IPN the listing is for.<br>';
						continue;
					}
					// if we don't have the stock_id or IPN, but we have the products ID, try to get the stock_id or error
					elseif (empty($data->stock_id) && empty($data->ipn) && !($data->stock_id = prepared_query::fetch('SELECT stock_id FROM products WHERE products_id LIKE :products_id', cardinality::SINGLE, [':products_id' => $data->products_id]))) {
						// we couldn't find the IPN
						$errors[] = '[LINE '.$row_count.']: The given products_id ['.$data->products_id.'] could not be matched in the system.<br>';
						continue;
					}
					// if we don't have the stock_id, then we have the IPN, try to get the stock_id or error
					elseif (empty($data->stock_id) && !($data->stock_id = prepared_query::fetch('SELECT stock_id FROM products_stock_control WHERE stock_name LIKE :ipn', cardinality::SINGLE, [':ipn' => $data->ipn]))) {
						// we couldn't find the IPN
						$errors[] = '[LINE '.$row_count.']: The given IPN ['.$data->ipn.'] could not be matched in the system.<br>';
						continue;
					}
					// if we don't have the IPN, then we have the stock_Id, try to get the IPN or error
					elseif (empty($data->ipn) && !($data->ipn = prepared_query::fetch('SELECT stock_name FROM products_stock_control WHERE stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $data->stock_id]))) {
						// we couldn't find the stock_id
						$errors[] = '[LINE '.$row_count.']: The given stock ID ['.$data->stock_id.'] could not be located in the system.<br>';
						continue;
					}
					// otherwise, check the supplied stock_id and ipn to make sure they match each other
					elseif (!($ipn = prepared_query::fetch('SELECT psc.stock_quantity, psc.stock_weight, psc.stock_price, psc.dealer_price, psci.* FROM products_stock_control psc LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id WHERE psc.stock_id = :stock_id AND psc.stock_name LIKE :stock_name', cardinality::ROW, [':stock_id' => $data->stock_id, ':stock_name' => $data->ipn]))) {
						$errors[] = '[LINE '.$row_count.']: The stock ID given ['.$data->stock_id.'] does not match the IPN given ['.$data->ipn.'] in the system.<br>';
						continue;
					}
					// we've got ID & # for both IPN and product listing

					$context = NULL;
					if (!empty($update_allowed)) {
						// we're allowed to update this item, so we need to check if it already exists
						if (!empty($data->products_id)) {
							// if we're passed a number for the product ID, then we assume that it should match in the database.
							if (is_numeric($data->products_id)) {
								// we're trying to update
								$context = UPDATE;

								// see if the products ID matches
								if (!($mn = prepared_query::fetch('SELECT products_model FROM products WHERE products_id = :products_id AND stock_id = :stock_id', cardinality::SINGLE, [':products_id' => $data->products_id, ':stock_id' => $data->stock_id]))) {
									$errors[] = '[LINE '.$row_count.']: The products ID given ['.$data->products_id.'] could not be matched for IPN ['.$data->ipn.'] in the system.<br>';
									continue;
								}
								// if the model number was set and is changed from what we expected, if we don't have the modelnum privilege, mark the error
								elseif (isset($data->model_number) && $data->model_number && $data->model_number != $mn && !$modelnum_allowed) {
									$errors[] = '[LINE '.$row_count.']: You\'ve changed the model number from ['.$mn.'] to ['.$data->model_number.'], but model number changing wasn\'t allowed.<br>';
									continue;
								}
								// we found the model number, if it wasn't set by the upload, set it now
								// we're not checking if they match because we can overwrite it if we got this far
								elseif (!isset($data->model_number) || !$data->model_number) $data->model_number = $mn;
							}
							// otherwise, we assume that we're being told to create the model number (this will be used even if the model
							// number already exists for this IPN, because it could legitimately be entered twice)
							else {
								if (!$data->model_number) {
									$errors[] = '[LINE '.$row_count.']: We don\'t have a valid products ID ['.$data->products_id.'] or a model number.<br>';
									continue;
								}
								elseif (empty($create_allowed)) {
									$errors[] = '[LINE '.$row_count.']: You\'ve specified that the model number should be entered as a new listing, but create permissions were not specified and/or allowed.<br>';
									continue;
								}
								else {
									$context = CREATE;
									$data->products_id = NULL;
								}
							}
						}
						elseif (empty($data->model_number)) {
							// with no products ID or model #, we're trying an update on all existing products for this IPN
							$context = UPDATE;

							if (!($products = prepared_query::fetch('SELECT products_id, products_model FROM products WHERE stock_id = :stock_id', cardinality::SET, [':stock_id' => $data->stock_id]))) {
								$errors[] = "[LINE $row_count]: IPN [$data->ipn] has no attached products to update, and you've provided no model # to create.<br>";
								continue;
							}
							elseif (count($products) == 1) {
								// if we've got only one result, set the products ID and model #, otherwise know that we've got a group to deal with below
								$data->products_id = $products[0]['products_id'];
								$data->model_number = $products[0]['products_model'];
							}
							// else products_id and model_number are empty and remain so, and $products is filled with an array we'll use instead
						}
						// we don't have products ID, we *do* have model number, if the model # exists it's an update, otherwise, if we're allowed, it's a create
						elseif (!($data->products_id = prepared_query::fetch('SELECT products_id FROM products WHERE products_model LIKE :products_model AND stock_id = :stock_id', cardinality::SINGLE, [':products_model' => $data->model_number, ':stock_id' => $data->stock_id])) && !$create_allowed) {
							$errors[] = '[LINE '.$row_count.']: The model # provided ['.$data->model_number.'] for IPN ['.$data->ipn.'] does not exist, but create permissions were not specified and/or allowed.<br>';
							continue;
						}
						// if we found the products ID, it's an update, otherwise it's a create (since if we didn't find
						// the products ID and create wasn't allowed, we would have failed above)
						elseif (!empty($data->products_id)) $context = UPDATE;
						else $context = CREATE;
					}
					else {
						// if we got this far, then create is allowed
						$context = CREATE;
						// this product *must not* already exist, so we can create it

						// if we have a product ID, that's an error
						if ($data->products_id) {
							$errors[] = '[LINE '.$row_count.']: A products ID was provided, but update permissions were not specified and/or allowed.<br>';
							continue;
						}
						// if we do not have a model number, use the IPN (we need to make sure this is clear to the user)
						if (!$data->model_number) $data->model_number = $data->ipn;
					}

					// if we've gotten this far, then we've set the appropriate context and filled in all of our identifiers

					// initialize any extra needed info for creating
					if ($context == CREATE) {
						// if we didn't grab the IPN row already, get it now, we're gonna need some of these details to fill out the product entry
						if (!$ipn) $ipn = prepared_query::fetch('SELECT psc.stock_quantity, psc.stock_weight, psc.stock_price, psc.dealer_price, psci.* FROM products_stock_control psc LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id WHERE psc.stock_id = :stock_id', cardinality::ROW, [':stock_id' => $data->stock_id]);
					}

					// we don't use the name until below, but go ahead and manage it here since it's required information for CREATEs
					if (isset($data->name) && $data->name) $data->name = preg_replace('/&nbsp;/', ' ', $data->name);
					elseif ($context == CREATE) {
						$errors[] = '[LINE '.$row_count.']: We\'re attempting to create product model ['.$data->model_number.'] for IPN ['.$data->ipn.'], please enter a name for this item.<br>';
						continue;
					}

					// if we've set the status (or we're creating a new listing, and thus want to ensure the status is turned off
					// unless it's specified on) run check_flag for all possible on/off values
					(isset($data->status)&&trim($data->status)!='')||$context==CREATE?($data->status = CK\fn::check_flag(@$data->status)?1:0):NULL;

					// if we've set the manufacturer, see if it's valid and, if necessary, translate to ID
					// if we're creating, this is required
					if (isset($data->manufacturer) && $data->manufacturer) {
						// if we've got an ID, try to match it
						if (is_numeric($data->manufacturer) && !prepared_query::fetch('SELECT manufacturers_id FROM manufacturers WHERE manufacturers_id = ?', cardinality::SINGLE, array($data->manufacturer))) {
							$errors[] = '[LINE '.$row_count.']: The specified manufacturer ID ['.$data->manufacturers.'] could not be found in the system.<br>';
							continue;
						}
						// if instead we've got a name, try to match it and pull out the ID (cache the manufacturers name first so we can refer to it in the error)
						elseif (!is_numeric($data->manufacturer) && ($manu = trim($data->manufacturer)) && !($data->manufacturer = prepared_query::fetch('SELECT manufacturers_id FROM manufacturers WHERE manufacturers_name LIKE ?', cardinality::SINGLE, array($manu)))) {
							$errors[] = '[LINE '.$row_count.']: The specified manufacturer name ['.$manu.'] could not be found in the system.<br>';
							continue;
						}
						// otherwise, we've got the matched ID
					}
					elseif ($context == CREATE) {
						$errors[] = '[LINE '.$row_count.']: We\'re attempting to create product model ['.$data->model_number.'] for IPN ['.$data->ipn.'], please enter a manufacturer for this item.<br>';
						continue;
					}

					// if we've turned on SEO URLs (or we're creating a new listing, and thus want to ensure the SEO URLs are turned off
					// unless it's specified on) run check_flag for all possible on/off values
					(isset($data->use_seo_urls)&&trim($data->use_seo_urls)!='')||$context==CREATE?($data->use_seo_urls = CK\fn::check_flag($data->use_seo_urls)?1:0):NULL;
					// if we've added SEO URL text, set it - optionally turn off use_seo_urls if this isn't provided
					if (!isset($data->seo_url_text) && $context == CREATE && $data->use_seo_urls == 1) {
						$data->seo_url_text = NULL;
						$data->use_seo_urls = 0;
					}

					// we don't use categories until below, but go ahead and manage it here since it's required information for CREATES
					if (isset($data->category_id) && $data->category_id) {
						// we're looking for numeric IDs
						if (!is_array($data->category_id)) $data->category_id = CK\fn::check_flag(@$_POST['use_category_names'])?preg_split('/\s*[|]+\s*/', $data->category_id):preg_split('/\D+/', $data->category_id);
						foreach ($data->category_id as $idx => $category_id) {
							// if we've got non-numeric data here, we'll have to warn that we're unable to confirm that we're selecting the right category
							if (!is_numeric($category_id)) {
								if ($cat_id = prepared_query::fetch('SELECT categories_id FROM categories_description WHERE categories_name LIKE ?', cardinality::SINGLE, array($category_id))) {
									$data->category_id[$idx] = $cat_id;
									$errors[] = '[LINE '.$row_count.']: You\'ve provided a category by name ['.$category_id.']; we cannot confirm that the matched category ID ['.$cat_id.'] is the one you\'re looking for, but we\'re using it anyway. <span style=\"background-color:#9f9;color:#c00;\">THIS LINE MAY STILL BE PROCESSED SUCCESSFULLY</span><br>';
								}
								else {
									$errors[] = '[LINE '.$row_count.']: You\'ve provided a category by name ['.$category_id.'] but we are unable to find it in the system.<br>';
									continue;
								}
							}
						}
					}
					elseif ($context == CREATE) {
						$errors[] = '[LINE '.$row_count.']: We\'re attempting to create product model ['.$data->model_number.'] for IPN ['.$data->ipn.'], please enter at least one category_id for this item.<br>';
						continue;
					}

					// update the database for the products table entry
					if ($context == CREATE) {
						// force copy images from parent IPN
						if (!($images = prepared_query::fetch('SELECT * FROM products_stock_control_images WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $data->stock_id]))) {
							prepared_query::execute("INSERT INTO products_stock_control_images (stock_id, image, image_med, image_lrg) VALUES (:stock_id, 'newproduct_sm.gif', 'newproduct_med.gif', 'newproduct.gif')", [':stock_id' => $data->stock_id]);
						}

						//only insert new item if $errors are empty
						if (empty($errors)) {
							$data->products_id = prepared_query::insert('INSERT INTO products (products_quantity, products_model, products_image, products_image_med, products_image_lrg, products_image_sm_1, products_image_xl_1, products_image_sm_2, products_image_xl_2, products_image_sm_3, products_image_xl_3, products_image_sm_4, products_image_xl_4, products_image_sm_5, products_image_xl_5, products_image_sm_6, products_image_xl_6, products_price, products_date_added, products_last_modified, products_price_modified, products_weight, products_status, manufacturers_id, stock_id, products_dealer_price, use_seo_urls, seo_url_text) SELECT :products_quantity, :model_number, psci.image, psci.image_med, psci.image_lrg, psci.image_sm_1, psci.image_xl_1, psci.image_sm_2, psci.image_xl_2, psci.image_sm_3, psci.image_xl_3, psci.image_sm_4, psci.image_xl_4, psci.image_sm_5, psci.image_xl_5, psci.image_sm_6, psci.image_xl_6, :products_price, NOW(), NOW(), NOW(), :stock_weight, :status, :manufacturer, :stock_id, :dealer_price, :use_seo_urls, :seo_url_text FROM products_stock_control_images psci WHERE psci.stock_id = :stock_id', [':products_quantity' => $ipn['stock_quantity'], ':model_number' => $data->model_number, ':products_price' => $ipn['stock_price'], ':stock_weight' => $ipn['stock_weight'],':status' => $data->status, ':manufacturer' => $data->manufacturer, ':stock_id' => $data->stock_id, ':dealer_price' => $ipn['dealer_price'], ':use_seo_urls' => $data->use_seo_urls, ':seo_url_text' => $data->seo_url_text, ':stock_id' => $data->stock_id]);
						}
						else {
							$output[] = '<b><span color="red">[LINE '.$row_count.']: You are trying to create products, but the system encountered errors in your upload. Please correct the errors in the upload and then try again.</span></b><br>';
							continue;
						}
					}
					else { // UPDATE
						if (empty($errors)) {
							// we might not have status or manufacturer ID
							if (!empty($data->manufacturer) && isset($data->status)) // also, we're not allowed to remove the manufacturer ID, so scorched earth doesn't matter
								prepared_query::execute('UPDATE products SET products_model = :products_model, products_status = :products_status, manufacturers_id = :manufacturers_id, products_last_modified = NOW() WHERE products_id = :products_id', [':products_model' => $data->model_number, ':products_status' => $data->status, ':manufacturers_id' => $data->manufacturer, ':products_id' => $data->products_id]);
							elseif (!empty($data->manufacturer))
								prepared_query::execute('UPDATE products SET products_model = :products_model, manufacturers_id = :manufacturers_id, products_last_modified = NOW() WHERE products_id = :products_id', [':products_model' => $data->model_number, ':manufacturers_id' => $data->manufacturer, ':products_id' => $data->products_id]);
							elseif (isset($data->status))
								prepared_query::execute('UPDATE products SET products_model = :products_model, products_status = :products_status, products_last_modified = NOW() WHERE products_id = :products_id', [':products_model' => $data->model_number, ':products_status' => $data->status, ':products_id' => $data->products_id]);
							else
								prepared_query::execute('UPDATE products SET products_model = :products_model, products_last_modified = NOW() WHERE products_id = :products_id', [':products_model' => $data->model_number, ':products_id' => $data->products_id]);

							if (isset($data->seo_url_text) && $data->seo_url_text) prepared_query::execute('UPDATE products SET seo_url_text = :seo_url_text WHERE products_id = :products_id', [':seo_url_text' => $data->seo_url_text, ':products_id' => $data->products_id]);
							if (isset($data->use_seo_urls) && $data->use_seo_urls) prepared_query::execute("UPDATE products SET use_seo_urls = CASE WHEN NULLIF(seo_url_text, '') IS NOT NULL THEN :use_seo_urls ELSE 0 END WHERE products_id = :products_id", [':use_seo_urls' => CK\fn::check_flag($data->use_seo_urls), ':products_id' => $data->products_id]);

							if (isset($data->google_name) && $data->google_name) prepared_query::execute('UPDATE products_description SET products_google_name = :products_google_name WHERE products_id = :products_id', [':products_google_name' => $data->google_name, ':products_id' => $data->products_id]);
						}
						else {
							$output[] = '<b><span color="red">You are trying to update products, but the system encountered errors in your upload. Please correct the errors in the upload and then try again.</span></b>';
							continue;
						}
					}

					// handle products description info
					// we've already confirmed the name for a CREATE above
					if ($context == CREATE) {
						$pd_fields = array('products_name' => $data->name);
					}
					else { // UPDATE
						// we may or may not wind up actually updating anything here, depending
						$pd_fields = array();
						if (empty($data->name)) $data->name = prepared_query::fetch('SELECT products_name FROM products_description WHERE products_id = :products_id', cardinality::SINGLE, [':products_id' => $data->products_id]);
					}

					if (isset($data->description) && $data->description) $pd_fields['products_description'] = $data->description;
					elseif ($context == CREATE) $pd_fields['products_description'] = $data->name;
					elseif ($overwrite_data && isset($data->description)) $pd_fields['products_description'] = NULL;

					if (isset($data->head_title) && $data->head_title) $pd_fields['products_head_title_tag'] = $data->head_title;
					elseif ($context == CREATE) $pd_fields['products_head_title_tag'] = $data->name;
					elseif ($overwrite_data && isset($data->head_title)) $pd_fields['products_head_title_tag'] = NULL;

					if (isset($data->short_desc) && $data->short_desc) $pd_fields['products_head_desc_tag'] = $data->short_desc;
					elseif ($context == CREATE) $pd_fields['products_head_desc_tag'] = $data->name;
					elseif ($overwrite_data && isset($data->short_desc)) $pd_fields['products_head_desc_tag'] = NULL;

					if (isset($data->google_name) && $data->google_name) $pd_fields['products_google_name'] = $data->google_name;
					elseif ($context == CREATE) $pd_fields['products_google_name'] = NULL;
					elseif ($overwrite_data && isset($data->google_name)) $pd_fields['products_google_name'] = NULL;

					if (!empty($pd_fields)) {
						// if this is an update and we didn't populate the products name to begin with, populate it now
						if (!isset($pd_fields['products_name'])) $pd_fields['products_name'] = $data->name;

						$pd_fields['products_id'] = $data->products_id;

						// accomplish an insert or update... context doesn't matter, if we've lost the products_description entry somehow we want to be able to re-create it
						prepared_query::execute('INSERT INTO products_description ('.implode(', ', array_keys($pd_fields)).') VALUES ('.implode(', ', array_map('reduce_to_params', $pd_fields)).') ON DUPLICATE KEY UPDATE '.implode(', ', array_map('dup_key_upd', array_keys($pd_fields))), array_values($pd_fields));
					}

					if (!empty($data->category_id)) {
						if ($overwrite_data) ServiceLocator::getDbService()->query('DELETE FROM products_to_categories WHERE products_id = :products_id', [':products_id' => $data->products_id]);
						foreach ($data->category_id as $category_id) {
							// this is a simple enough case to warrant INSERT IGNORE
							prepared_query::execute('INSERT IGNORE INTO products_to_categories (products_id, categories_id) VALUES (:products_id, :category_id)', [':products_id' => $data->products_id, ':category_id' => $category_id]);
						}

						// assign attribute keys
						try {
							if ($products = prepared_query::fetch('SELECT DISTINCT ptc.categories_id, agl.attribute_key_id, agl.attribute_key FROM products_to_categories ptc LEFT JOIN ck_attribute_group_categories agc ON ptc.categories_id = agc.category_id LEFT JOIN ck_attribute_group_lists agl ON agc.attribute_group_id = agl.attribute_group_id WHERE ptc.products_id = :products_id', cardinality::SET, [':products_id' => $data->products_id])) {
								list($hierarchies, $children) = build_category_hierarchies();
								if ($overwrite_data) prepared_query::execute('DELETE FROM ck_attribute_assignments WHERE products_id = :products_id', [':products_id' => $data->products_id]);
								foreach ($products as $product) {
									if (!empty($product['attribute_key_id'])) {
										$attribute_assignment_insert = prepared_query::execute('INSERT IGNORE INTO ck_attribute_assignments (attribute_assignment_level, products_id, model_number, attribute_key_id, attribute_key) VALUES (:asl, :pi, :mn, :aki, :ak)', [':asl' => 3, ':pi' => $data->products_id, ':mn' => $data->model_number, 'aki' => $product['attribute_key_id'], ':ak' => $product['attribute_key']]);
									}
									$parent_categories = $hierarchies[$product['categories_id']]?array_reverse(explode('/', $hierarchies[$product['categories_id']])):array();
									foreach ($parent_categories as $category_id) {
										if ($attributes = prepared_query::fetch('SELECT DISTINCT agc.category_id, agl.attribute_key_id, agl.attribute_key FROM ck_attribute_group_categories agc JOIN ck_attribute_group_lists agl ON agc.attribute_group_id = agl.attribute_group_id WHERE agc.trait = 1 AND agc.category_id = :category_id', cardinality::SET, [':category_id' => $category_id])) {
											foreach ($attributes as $attribute) {
												$attribute_assignment_insert = prepared_query::execute('INSERT IGNORE INTO ck_attribute_assignments (attribute_assignment_level, products_id, model_number, attribute_key_id, attribute_key) VALUES (:aal, :pi, :mn, :aki, :ak)', [':aal' => 4, ':pi' => $data->products_id, ':mn' => $data->model_number, ':aki' => $attribute['attribute_key_id'], ':ak' => $attribute['attribute_key']]);
											}
										}
									}
								}
							}
						}
						catch (Exception $e) {
							$errors[] = '[LINE '.$row_count.']: Failure to reassign attribute keys to this product ['.$e->getMessage().'].<br>';
							continue;
						}
					}
				}
				// done with the loop through our rows

				/*foreach ($success as $action => $cnt) {
					$action .= $action=='add'?'ed':'d';
					$output[] = "There were $cnt attribute values $action.<br>";
				}
				foreach ($warning as $action => $cnt) {
					$action .= $action=='add'?'ed':'d';
					$output[] = "There were $cnt attribute values that were not allowed to be $action.<br>";
				}
				foreach ($failure as $action => $cnt) {
					$action .= $action=='add'?'ed':'d';
					$output[] = "There were $cnt attribute values that failed to $action.<br>";
				}*/

			}
		}
	}
}
if (isset($_POST['action']) && $_POST['action'] == 'export') {
	$id_type = $_POST['id-type'];
	$vendor_selection = $_POST['vendor_selection'];
	$identifiers = trim($_POST['identifiers'])?preg_split('/\s+/', trim($_POST['identifiers'])):NULL;
	if (!empty($identifiers)) {
		$products = [];
		$categories = [];
		// loop through the identifiers, populating the appropriate ID type
		foreach ($identifiers as $$id_type) { // the variable variable may be a little too clever by half... we'll see...
			if ($id_type == 'ipn') {
				// since each IPN could conceivably have multiple products, merge the previous results with the current results
				$products = array_merge($products, prepared_query::fetch("SELECT psc.stock_id, psc.stock_name as ipn, p.products_id, p.products_model as model_number, pd.products_name as name, CASE WHEN p.products_status = 1 THEN 'ON' ELSE 'OFF' END as status, m.manufacturers_name as manufacturer, pd.products_description as description, pd.products_head_title_tag as head_title, pd.products_head_desc_tag as short_desc, pd.products_google_name, CASE WHEN p.use_seo_urls = 1 THEN 'ON' ELSE 'OFF' END as use_seo_urls, p.seo_url_text, '' as category_id, '' as category_names FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_description pd ON p.products_id = pd.products_id LEFT JOIN manufacturers m ON p.manufacturers_id = m.manufacturers_id WHERE psc.stock_name RLIKE :ipn", cardinality::SET, [':ipn' => '^[[:space:]]*'.$ipn.'[[:space:]]*$']));

				// grab the categories separately, since there's an undetermined number of them for the products attached to this category
				$categories = array_merge($categories, prepared_query::fetch('SELECT DISTINCT ptc.products_id, ptc.categories_id, cd.categories_name FROM products_to_categories ptc JOIN products_to_categories ptc2 ON ptc.products_id = ptc2.products_id JOIN categories_description cd ON ptc.categories_id = cd.categories_id JOIN products p ON ptc2.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE psc.stock_name RLIKE :ipn', cardinality::SET, [':ipn' => '^[[:space:]]*'.$ipn.'[[:space:]]*$']));
			}
			elseif ($id_type == 'model_number') {
				// since each IPN could conceivably have multiple products, merge the previous results with the current results
				$products = array_merge($products, prepared_query::fetch("SELECT psc.stock_id, psc.stock_name as ipn, p.products_id, p.products_model as model_number, pd.products_name as name, CASE WHEN p.products_status = 1 THEN 'ON' ELSE 'OFF' END as status, m.manufacturers_name as manufacturer, pd.products_description as description, pd.products_head_title_tag as head_title, pd.products_head_desc_tag as short_desc, pd.products_google_name, CASE WHEN p.use_seo_urls = 1 THEN 'ON' ELSE 'OFF' END as use_seo_urls, p.seo_url_text, '' as category_id, '' as category_names FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_description pd ON p.products_id = pd.products_id LEFT JOIN manufacturers m ON p.manufacturers_id = m.manufacturers_id WHERE p.products_model RLIKE :mn", cardinality::SET, [':mn' => '^[[:space:]]*'.$model_number.'[[:space:]]*$']));

				// grab the categories separately, since there's an undetermined number of them for the products attached to this category
				$categories = array_merge($categories, prepared_query::fetch('SELECT DISTINCT ptc.products_id, ptc.categories_id, cd.categories_name FROM products_to_categories ptc JOIN products_to_categories ptc2 ON ptc.products_id = ptc2.products_id JOIN categories_description cd ON ptc.categories_id = cd.categories_id JOIN products p ON ptc2.products_id = p.products_id WHERE p.products_model RLIKE ?', cardinality::SET, array('^[[:space:]]*'.$model_number.'[[:space:]]*$')));
			}
			else {
				// hard fail, we've been passed some unexpected input for the id_type, so we don't really know what global variable has been written or *over*-written, so kill it
				throw new Exception('Unexpected Data ['.$id_type.'], kill it with fire.');
				exit();
			}
		}
	}
	else {
		$category_id = $_POST['category_id'];
		$cascade = @CK\fn::check_flag($_POST['cascade']);
		$query = @CK\fn::check_flag($_POST['active-only'])?'p.products_status = 1 AND':'';

		if (!empty($category_id)) {
			// we're limiting the resulting list to only one category (and potentially it's decendants)
			$category_ids = array($category_id);
			$products = array();
			$categories = array();

			$lookup_category_ids = [$category_id];

			// if we're cascading down to sub-categories, then we'll need to dynamically build this list in the loop
			while (!empty($lookup_category_ids)) {
				$cat_id = array_pop($lookup_category_ids);
				// if we're cascading to decendant categories, grab those categories and add them to the list we're looping through (if they're not already there).
				if ($cascade && $cat = prepared_query::fetch('SELECT DISTINCT categories_id FROM categories WHERE parent_id = :category_id', cardinality::COLUMN, [':category_id' => $cat_id])) {
					foreach ($cat as $catid) {
						if (!in_array($catid, $category_ids)) {
							$category_ids[] = $catid;
							$lookup_category_ids[] = $catid;
						}
					}
				}
			}

			// if we're not, then it'll just run once anyway
			foreach ($category_ids as $category_id) {
				if ($vendor_selection != 'all') {
					$products = array_merge($products, prepared_query::fetch("SELECT psc.stock_id, psc.stock_name as ipn, p.products_id, p.products_model as model_number, pd.products_name as name, pd.products_google_name, CASE WHEN p.products_status = 1 THEN 'ON' ELSE 'OFF' END as status, m.manufacturers_name as manufacturer, pd.products_description as description, pd.products_head_title_tag as head_title, pd.products_head_desc_tag as short_desc, CASE WHEN p.use_seo_urls = 1 THEN 'ON' ELSE 'OFF' END as use_seo_urls, p.seo_url_text, '' as category_id, '' as category_names FROM products_to_categories ptc JOIN products p ON ptc.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_description pd ON p.products_id = pd.products_id LEFT JOIN manufacturers m ON p.manufacturers_id = m.manufacturers_id LEFT JOIN vendors_to_stock_item vtsi ON psc.vendor_to_stock_item_id = vtsi.id WHERE vtsi.vendors_id = :vendor_selection AND $query ptc.categories_id = :category_id", cardinality::SET, [':category_id' => $category_id, ':vendor_selection' => $vendor_selection]));
				}
				else {
					// if we've run this previously, merge the previous results with the current results
					$products = array_merge($products, prepared_query::fetch("SELECT psc.stock_id, psc.stock_name as ipn, p.products_id, p.products_model as model_number, pd.products_name as name, pd.products_google_name, CASE WHEN p.products_status = 1 THEN 'ON' ELSE 'OFF' END as status, m.manufacturers_name as manufacturer, pd.products_description as description, pd.products_head_title_tag as head_title, pd.products_head_desc_tag as short_desc, CASE WHEN p.use_seo_urls = 1 THEN 'ON' ELSE 'OFF' END as use_seo_urls, p.seo_url_text, '' as category_id, '' as category_names FROM products_to_categories ptc JOIN products p ON ptc.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_description pd ON p.products_id = pd.products_id LEFT JOIN manufacturers m ON p.manufacturers_id = m.manufacturers_id WHERE $query ptc.categories_id = :category_id", cardinality::SET, [':category_id' => $category_id]));
				}

				// grab the categories separately, since there's an undetermined number of them for the products attached to this category
				$categories = array_merge($categories, prepared_query::fetch('SELECT DISTINCT ptc.products_id, ptc.categories_id, cd.categories_name FROM products_to_categories ptc JOIN products_to_categories ptc2 ON ptc.products_id = ptc2.products_id JOIN categories_description cd ON ptc.categories_id = cd.categories_id WHERE ptc2.categories_id = :category_id', cardinality::SET, [':category_id' => $category_id]));
			}
		}
		else {
			if ($vendor_selection != 'all') {
				// grab ALL products filtered by vendor selection
				$products = prepared_query::fetch("SELECT psc.stock_id, psc.stock_name as ipn, p.products_id, p.products_model as model_number, pd.products_name as name, CASE WHEN p.products_status = 1 THEN 'ON' ELSE 'OFF' END as status, m.manufacturers_name as manufacturer, pd.products_description as description, pd.products_head_title_tag as head_title, pd.products_head_desc_tag as short_desc, pd.products_google_name, CASE WHEN p.use_seo_urls = 1 THEN 'ON' ELSE 'OFF' END as use_seo_urls, p.seo_url_text, '' as category_id, '' as category_names FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_description pd ON p.products_id = pd.products_id LEFT JOIN manufacturers m ON p.manufacturers_id = m.manufacturers_id LEFT JOIN vendors_to_stock_item vtsi ON psc.vendor_to_stock_item_id = vtsi.id WHERE vtsi.vendors_id = :vendor_selection AND $query true", cardinality::SET, [':vendor_selection' => $vendor_selection]);
			}
			else {
				// grab ALL products
				$products = prepared_query::fetch("SELECT psc.stock_id, psc.stock_name as ipn, p.products_id, p.products_model as model_number, pd.products_name as name, pd.products_google_name, CASE WHEN p.products_status = 1 THEN 'ON' ELSE 'OFF' END as status, m.manufacturers_name as manufacturer, pd.products_description as description, pd.products_head_title_tag as head_title, pd.products_head_desc_tag as short_desc, CASE WHEN p.use_seo_urls = 1 THEN 'ON' ELSE 'OFF' END as use_seo_urls, p.seo_url_text, '' as category_id, '' as category_names FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN products_description pd ON p.products_id = pd.products_id LEFT JOIN manufacturers m ON p.manufacturers_id = m.manufacturers_id WHERE $query true", cardinality::SET);
			}
			// grab ALL category relationships
			$categories = prepared_query::fetch('SELECT DISTINCT ptc.products_id, ptc.categories_id, cd.categories_name FROM products_to_categories ptc JOIN products_to_categories ptc2 ON ptc.products_id = ptc2.products_id JOIN categories_description cd ON ptc.categories_id = cd.categories_id', cardinality::SET);
		}
	}

	if (!empty($products)) {

		$fp = fopen(dirname(__FILE__)."/data_management/product_update_rpt_".$_SESSION['perms']['admin_id'].".csv", "w");

		//determine columns
		$column_names = ['stock_id', 'ipn', 'products_id', 'model_number', 'name'];
		if (!empty($_POST['google_name_selection']) && $_POST['google_name_selection'] == 'on') array_push($column_names, 'google_name');
		if (!empty($_POST['status_selection']) && $_POST['status_selection'] == 'on') array_push($column_names, 'status');
		if (!empty($_POST['manufacturer_selection']) && $_POST['manufacturer_selection'] == 'on') array_push($column_names, 'manufacturer');
		if (!empty($_POST['description_selection']) && $_POST['description_selection'] == 'on') array_push($column_names, 'description');
		if (!empty($_POST['head_title_selection']) && $_POST['head_title_selection'] == 'on') array_push($column_names, 'head_title');
		if (!empty($_POST['short_description_selection']) && $_POST['short_description_selection'] == 'on') array_push($column_names, 'short_description');
		if (!empty($_POST['use_seo_urls_selection']) && $_POST['use_seo_urls_selection'] == 'on') array_push($column_names, 'use_seo_urls');
		if (!empty($_POST['seo_url_text_selection']) && $_POST['seo_url_text_selection'] == 'on') array_push($column_names, 'seo_url_text');
		if (!empty($_POST['category_id_selection']) && $_POST['category_id_selection'] == 'on') array_push($column_names, 'category_id');
		if (!empty($_POST['category_names_selection']) && $_POST['category_names_selection'] == 'on') array_push($column_names, 'category_names');

		fputcsv($fp, $column_names);
		$used_products = [];

		$products_categories = [];

		foreach ($categories as $category) {
			if (empty($products_categories[$category['products_id']])) $products_categories[$category['products_id']] = [];
			$products_categories[$category['products_id']][] = $category;
		}
		unset($categories);

		foreach ($products as $product) {
			// if, through sub-category cascading, we've included the same product twice, skip it the second time
			if (in_array($product['products_id'], $used_products)) continue;

			// remove the columns that were not selected for the export
			if (empty($_POST['status_selection'])) unset($product['status']);
			if (empty($_POST['manufacturer_selection'])) unset($product['manufacturer']);
			if (empty($_POST['description_selection'])) unset($product['description']);
			if (empty($_POST['head_title_selection'])) unset($product['head_title']);
			if (empty($_POST['short_description_selection'])) unset($product['short_desc']);
			if (empty($_POST['use_seo_urls_selection'])) unset($product['use_seo_urls']);
			if (empty($_POST['seo_url_text_selection'])) unset($product['seo_url_text']);
			if (empty($_POST['category_id_selection'])) unset($product['category_id']);
			if (empty($_POST['category_names_selection'])) unset($product['category_names']);
			if (empty($_POST['google_name'])) unset($product['google_name']);

			$used_products[] = $product['products_id'];
			$cat_ids = [];
			$cat_names = [];

			if (!empty($product['description'])) $product['description'] = preg_replace('/\n/', "\r\n", $product['description']);
			if (!empty($product['short_desc'])) $product['short_desc'] = preg_replace('/\n/', "\r\n", $product['short_desc']);

			if ((isset($product['category_id']) || isset($product['category_names'])) && !empty($products_categories[$product['products_id']])) {
				foreach ($products_categories[$product['products_id']] as $category) {
					$cat_ids[] = $category['categories_id'];
					$cat_names[] = $category['categories_name'];
				}

				if (isset($product['category_id'])) $product['category_id'] = implode(', ', $cat_ids);
				if (isset($product['category_names'])) $product['category_names'] = implode(', ', $cat_names);
			}

			fputcsv($fp, array_values($product));
		}
		fclose($fp);
	}
	else {
		$errors[] = "There were no products found to export.<br>";
	}
}

//---------------header-------------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//-------------end header----------------

//-----------body-------------------
$content_map = new ck_content();

$content_map->export_link = [];
$content_map->categories = [];
$content_map->vendors = [];
$content_map->upload_permissions = [];
$content_map->product_export_options = [];

$vendor_selection = [];
$selections = [];
$top_level = [];

$cname = NULL;
$content_map->change_model_number = FALSE;

if (!empty($_POST['action']) && $_POST['action'] == 'export' && empty($errors)) $content_map->export_link[] = ['url_params' => $_SESSION['perms']['admin_id']];
if (!empty($output) && !empty($errors)) {
	$content_map->output_errors = ['errors' => $errors, 'output' => $output];
	//var_dump($content_map->output_errors);
}

if ($categories = prepared_query::fetch("SELECT c.categories_id, cd.categories_name, c.parent_id, COUNT(ptc.products_id) as pcount FROM categories_description cd JOIN categories c ON cd.categories_id = c.categories_id LEFT JOIN products_to_categories ptc ON c.categories_id = ptc.categories_id WHERE c.categories_id IN (SELECT DISTINCT parent_id as categories_id FROM categories UNION SELECT DISTINCT categories_id FROM products_to_categories) GROUP BY c.categories_id, cd.categories_name, c.parent_id ORDER BY cd.categories_name, c.categories_id", cardinality::SET)) {
	foreach ($categories as $category) {
		$cname = $category['pcount']?$category['categories_name'].'*':$category['categories_name'];
		if (empty($category['parent_id'])) {
			$top_level[] = ['id' => $category['categories_id'], 'name' => $cname];
		}
		else {
			if (empty($selections[$category['parent_id']])) $selections[$category['parent_id']] = [];
			$selections[$category['parent_id']][] = ['id' => $category['categories_id'], 'name' => $cname];
		}
	}
}

$content_map->top_level = $top_level;
$content_map->encoded_top_level = json_encode($top_level);
$content_map->encoded_selections = json_encode($selections);

$vendors = prepared_query::fetch('SELECT vendors_id, vendors_company_name FROM vendors', cardinality::SET);

foreach ($vendors as $vendor) $vendor_selection[] = ['vendor_id' => $vendor['vendors_id'], 'vendors_name' => $vendor['vendors_company_name']];

$content_map->vendors = $vendor_selection;

if (checkperm(CREATE | UPDATE | PERITEM)) $content_map->upload_permissions[] = ['value' => 7, 'title' =>  'Create/Update or Set Per Item'];
if (checkperm(PERITEM)) $content_map->upload_permissions[] = ['value' => 4, 'title' =>  'Set Per Item Only'];
if (checkperm(CREATE | UPDATE)) $content_map->upload_permissions[] = ['value' => 3, 'title' =>  'Create/Update'];
if (checkperm(CREATE)) $content_map->upload_permissions[] = ['value' => 1, 'title' =>  'Create Only'];
if (checkperm(UPDATE)) $content_map->upload_permissions[] = ['value' => 2, 'title' =>  'Update Only'];

if (checkperm(MODELNUM)) $content_map->change_model_number = TRUE;

//product export column options
$content_map->product_export_options[] = ['id' => 'category-names-selection', 'name' => 'category_names_selection', 'title' => 'Category Names'];
$content_map->product_export_options[] = ['id' => 'category-id-selection', 'name' => 'category_id_selection', 'title' => 'Category Id'];
$content_map->product_export_options[] = ['id' => 'seo-url-text-selection', 'name' => 'seo_url_text_selection', 'title' => 'Seo Url Text'];
$content_map->product_export_options[] = ['id' => 'use-seo-urls-selection', 'name' => 'use_seo_urls_selection', 'title' => 'Use Seo Urls'];
$content_map->product_export_options[] = ['id' => 'short-description-selection', 'name' => 'short_description_selection', 'title' => 'Short Description'];
$content_map->product_export_options[] = ['id' => 'status-selection', 'name' => 'status_selection', 'title' => 'Status'];
$content_map->product_export_options[] = ['id' => 'manufacturer-selection', 'name' => 'manufacturer_selection', 'title' => 'Manufacturer'];
$content_map->product_export_options[] = ['id' => 'description-selection', 'name' => 'description_selection', 'title' => 'Description'];
$content_map->product_export_options[] = ['id' => 'head-title-selection', 'name' => 'head_title_selection', 'title' => 'Head Title'];
$content_map->product_export_options[] = ['id' => 'google-name-selection', 'name' => 'google_name_selection', 'title' => 'Google Name'];

$cktpl->content('includes/templates/page-manage-products.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>