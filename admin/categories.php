<?php
require('includes/application_top.php');
require_once('includes/functions/ebay_categories.php');

header('X-XSS-Protection:0');

if (!empty($_GET['ipn'])) {
	$ipn = ck_ipn2::get_ipn_by_ipn($_GET['ipn']);
	$product = $ipn->get_listings()[0];
	CK\fn::redirect_and_exit('/admin/categories.php?pID='.$product->get_header('products_id').'&action=new_product');
}

$ebayShopCatArr = geteBayShopArr();

$parent_cat_array = [];

// doing it this way allows us to treat $action and $_GET['action'] as separate variables after we initialize them, without having to check for $_GET['action']'s existence every time
// don't know if that's strictly needed, but without digging really deeply into the code it's impossible to tell if it's necessary or not
if (!isset($_GET['action'])) $_GET['action'] = '';
$action = $_GET['action'];

if (!empty($action)) {
	switch ($action) {
		case 'rebuild-topnav-cache':
			ck_listing_category::rebuild_topnav_structure();
			CK\fn::redirect_and_exit('/admin/categories.php');
			break;
		case 'google_category_lookup':
			$results = ['rows' => []];
			$field = ltrim($_GET['field'], '> ');
			if ($categories = prepared_query::fetch('SELECT * FROM google_categories WHERE category_1 LIKE :field OR category_2 LIKE :field OR category_3 LIKE :field OR category_4 LIKE :field OR category_5 LIKE :field OR category_6 LIKE :field OR category_7 LIKE :field OR category_8 LIKE :field ORDER BY category_1, category_2, category_3, category_4, category_5, category_6, category_7, category_8', cardinality::SET, [':field' => '%'.$field.'%'])) {
				foreach ($categories as $category) {
					$category_list = [];
					$last_category = NULL;
					for ($i=0; $i<8; $i++) {
						if (empty($category['category_'.$i])) continue;
						$category_list[] = $last_category = $category['category_'.$i];
						if ($i > 0) $last_category = '> '.$last_category;
					}
					$results['rows'][] = ['value' => $category['google_category_id'], 'result' => $last_category, 'label' => implode(' &gt; ', $category_list)];
				}
			}
			else {
				$results['rows'][] = ['value' => '', 'result' => '', 'label' => 'No Matching Options'];
			}
			echo json_encode($results);
			exit();
			break;
		case 'setflag':
			if ($_GET['flag'] == '0' || $_GET['flag'] == '1') {
				if (isset($_GET['pID'])) {
					tep_set_product_status($_GET['pID'], $_GET['flag']);

					$product = new ck_product_listing($_GET['pID']);

					insert_psc_change_history($product->get_header('stock_id'), 'Product Status Change - '.$product->get_header('products_model'), $__FLAG['flag']?'0':'1', $__FLAG['flag']?'1':'0');
				}
			}
			if (isset($_GET['editing'])) {
				CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$_GET['cPath'].'&pID='.$_GET['pID'].'&action=new_product');
			}
			else {
				CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$_GET['cPath'].'&pID='.$_GET['pID']);
			}
			break;
		case 'new_category':
		case 'edit_category':
			break;
		case 'insert_category':
		case 'update_category':
			$categories_id = isset($_POST['categories_id'])?$_POST['categories_id']:$_GET['categories_id'];

			$sort_order = $_POST['sort_order'];

			$sql_data_array = [
				'sort_order' => $sort_order,
				'canonical_category_id' => $_POST['canonical_category_id'],
				'google_category_id' => $_POST['google_category_id'],
				'inactive' => isset($_POST['inactive'])?1:0,
				'promo_image' => !empty($_POST['promo_image'])?$_POST['promo_image']:NULL,
				'promo_link' => !empty($_POST['promo_link'])?$_POST['promo_link']:NULL,
				'promo_offsite' => CK\fn::check_flag(@$_POST['promo_offsite'])?1:0,
				'use_seo_urls' => $__FLAG['use_seo_urls']?1:0,
				'seo_url_text' => !empty(trim($_POST['seo_url_text']))?trim($_POST['seo_url_text']):NULL,
				'seo_url_parent_text' => !empty(trim($_POST['seo_url_parent_text']))?trim($_POST['seo_url_parent_text']):NULL
			];

			if ($action == 'insert_category') {
				$insert_sql_data = [
					'parent_id' => $current_category_id,
					'date_added' => 'now()'
				];

				if (!empty($_POST['topnav_redirect'])) $insert_sql_data['topnav_redirect'] = $_POST['topnav_redirect'];

				$sql_data_array = array_merge($insert_sql_data, $sql_data_array);

				$params = new ezparams($sql_data_array);
				$categories_id = prepared_query::insert('INSERT INTO categories ('.$params->insert_cols.') VALUES ('.$params->insert_params.')', $params->query_vals);
			}
			elseif ($action == 'update_category') {
				$update_sql_data = ['last_modified' => 'now()'];

				if (empty($_POST['topnav_redirect'])) $update_sql_data['topnav_redirect'] = NULL;
				else $update_sql_data['topnav_redirect'] = $_POST['topnav_redirect'];

				$sql_data_array = array_merge($update_sql_data, $sql_data_array);

				$params = new ezparams($sql_data_array);
				prepared_query::execute('UPDATE categories SET '.$params->update_cols().' WHERE categories_id = ?', $params->query_vals($categories_id));
			}

			$additional_category_info = prepared_query::fetch("SELECT COUNT(categories_id) AS numcats FROM abx_category_info WHERE categories_id = :categories_id AND categories_site = 'US'", cardinality::SINGLE, [':categories_id' => $categories_id]);

			if (!empty($additional_category_info)) {
				prepared_query::execute("UPDATE abx_category_info SET ebay_category1_id = ?, ebay_shop_category1_id = ? WHERE categories_id = ? AND categories_site = 'US'", [$_POST['ebay_category1_id'], $_POST['ebay_shop_category1_id'], $categories_id]);
			}
			else {
				prepared_query::execute("INSERT INTO abx_category_info (categories_id, categories_site, ebay_category1_id, ebay_category2_id, ebay_shop_category1_id, ebay_shop_category2_id) VALUES (?, 'US', ?, '', ?, '0')", [$categories_id, $_POST['ebay_category1_id'], $_POST['ebay_shop_category1_id']]);
			}

			$sql_data_array = [
				'categories_name' => $_POST['categories_name'][1],
				'categories_heading_title' => $_POST['categories_heading_title'][1],
				'use_categories_description' => $__FLAG['use_categories_description']?1:0,
				'categories_description' => $_POST['categories_description'][1],
				'categories_description_product_ids' => $_POST['categories_description_product_ids'],
				'use_categories_bottom_text' => $__FLAG['use_categories_bottom_text']?1:0,
				'categories_bottom_text' => $_POST['categories_bottom_text'],
				'categories_bottom_text_product_ids' => $_POST['categories_bottom_text_product_ids'],
				'categories_head_title_tag' => $_POST['categories_head_title_tag'][1],
				'categories_head_desc_tag' => $_POST['categories_head_desc_tag'][1],
				'product_finder_description' => $_POST['product_finder_desc'],
				'product_finder_image' => $_POST['product_finder_image'],
				'product_finder_hide' => (!empty($_POST['product_finder_hide'])?1:0)
			];

			if ($action == 'insert_category') {
				$insert_sql_data = [
					'categories_id' => $categories_id,
					'language_id' => 1
				];

				$sql_data_array = array_merge($insert_sql_data, $sql_data_array);

				$params = new ezparams($sql_data_array);
				prepared_query::execute('INSERT INTO categories_description ('.$params->insert_cols.') VALUES ('.$params->insert_params.')', $params->query_vals);
			}
			elseif ($action == 'update_category') {
				$params = new ezparams($sql_data_array);
				prepared_query::execute('UPDATE categories_description SET '.$params->update_cols.' WHERE categories_id = ? AND language_id = ?', $params->query_vals(array($categories_id, 1)));
			}

			prepared_query::execute('UPDATE categories SET categories_image = ? WHERE categories_id = ?', array($_POST['categories_image'], $categories_id));
			$categories_image = '';

			if (!empty($_POST['redirect_container_id'])) {
				$category = new ck_listing_category($categories_id);
				// $container_type_id, $container_id, $canonical, $redirect
				$category->set_primary_merchandising_container(3, $_POST['redirect_container_id'], 1, 1);
			}
			else {
				$category = new ck_listing_category($categories_id);
				$category->remove_primary_merchandising_container();
			}

			unset($ck_keys->template_cache);
			CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$cPath.'&categories_id='.$categories_id);

			break;
		case 'delete_category_confirm':
			if (isset($_POST['categories_id'])) {
				$categories_id = $_POST['categories_id'];

				$categories = tep_get_category_tree($categories_id, '', '0', '', TRUE);
				$products = [];
				$products_delete = [];

				foreach ($categories as $i => $category) {
					$product_ids = prepared_query::fetch('SELECT products_id FROM products_to_categories WHERE categories_id = ?', cardinality::COLUMN, array($category['id']));
					foreach ($product_ids as $product_id) {
						if (empty($products[$product_id])) $products[$product_id] = [];
						$products[$product_id][] = $category['id'];
					}
				}

				foreach ($products as $products_id => $category_ids) {
					$other_category_count = prepared_query::fetch('SELECT COUNT(categories_id) FROM products_to_categories WHERE products_id = ? AND categories_id NOT IN ('.implode(', ', $category_ids).')', cardinality::SINGLE, array($products_id));
					if ($other_category_count < 1) $products_delete[] = $products_id;
				}

				// removing categories can be a lengthy process
				tep_set_time_limit(0);
				foreach ($categories as $i => $category) {
					tep_remove_category($category['id']);
				}

				foreach ($products_delete as $products_id) {
					tep_remove_product($products_id);
				}
			}

			CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$cPath);
			break;
		case 'delete_product_confirm':
			if (isset($_POST['products_id']) && isset($_POST['product_categories']) && is_array($_POST['product_categories'])) {
				$products_id = $_POST['products_id'];
				$product_categories = $_POST['product_categories'];

				foreach ($product_categories as $products_category) {
					prepared_query::execute('DELETE FROM products_to_categories WHERE products_id = :product_id AND categories_id = :product_category', [':product_id' => $products_id, ':product_category' => $products_category]);
				}

				$other_category_count = prepared_query::fetch('SELECT COUNT(categories_id) FROM products_to_categories WHERE products_id = ?', cardinality::SINGLE, array($products_id));

				echo $other_category_count;

				if ($other_category_count < 1) {
					tep_remove_product($products_id);
				}
			}

			CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$cPath);
			break;
		case 'move_category_confirm':
			if (isset($_POST['categories_id']) && ($_POST['categories_id'] != $_POST['move_to_category_id'])) {
				$categories_id = $_POST['categories_id'];
				$new_parent_id = $_POST['move_to_category_id'];

				$path = explode('_', tep_get_generated_category_path_ids($new_parent_id));

				if (in_array($categories_id, $path)) {
					$messageStack->add_session(ERROR_CANNOT_MOVE_CATEGORY_TO_PARENT, 'error');
					CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$cPath.'&categories_id='.$categories_id);
				}
				else {
					prepared_query::execute('UPDATE categories SET parent_id = ?, last_modified = NOW() WHERE categories_id = ?', array($new_parent_id, $categories_id));

					CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$new_parent_id.'&categories_id='.$categories_id);
				}
			}

			break;
		case 'move_product_confirm':
			$products_id = $_POST['products_id'];
			$new_parent_id = $_POST['move_to_category_id'];

			$dupes = prepared_query::fetch('SELECT COUNT(categories_id) FROM products_to_categories WHERE products_id = ? AND categories_id = ?', cardinality::SINGLE, array($products_id, $new_parent_id));

			if ($dupes < 1) prepared_query::execute('UPDATE products_to_categories SET categories_id = ? where products_id = ? and categories_id = ?', array($new_parent_id, $products_id, $current_category_id));

			CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$new_parent_id.'&pID='.$products_id);
			break;
		case 'insert_product':
		case 'update_product':
			// there's no way I'm going to wade through this morass to figure out the logical spot to put the "delete option" code, so here it goes at the beginning
			if (isset($_POST['delete_product_option'])) {
				foreach ($_POST['delete_product_option'] as $product_id => $child) {
					foreach ($child as $option_product_id => $on) {
						prepared_query::execute("DELETE FROM product_addons WHERE product_id = :products_id AND product_addon_id = :option_products_id", [':products_id' => $product_id, ':option_products_id' => $option_product_id]);
					}
				}
			}

			if (isset($_GET['pID'])) $products_id = $_GET['pID'];
			$products_date_available = !empty($_POST['products_date_available'])?$_POST['products_date_available']:NULL;

			$products_date_available = date('Y-m-d')<$products_date_available?$products_date_available:'null';

			if ($_POST['stock_id'] != 1) {
				$parent = prepared_query::fetch('SELECT stock_weight, stock_quantity, stock_price, dealer_price FROM products_stock_control WHERE stock_id = ?', cardinality::ROW, array($_POST['stock_id']));
				$p_stock = $parent['stock_quantity'];
				$p_weight = $parent['stock_weight'];
				$p_price = $parent['stock_price'];
				$d_price = $parent['dealer_price'];
			}
			else {
				$p_stock = $_POST['products_quantity'];
				$p_weight = $_POST['products_weight'];
				$p_price = $_POST['products_price'];
				$d_price = $_POST['products_dealer_price'];
			}

			// product
			$sql_data_array = [
				'products_quantity' => $p_stock,
				'products_model' => $_POST['products_model'],
				'products_price' => $p_price,
				'products_dealer_price' => $d_price,
				'products_date_available' => $products_date_available,
				'products_weight' => $p_weight,
				'products_status' => $_POST['products_status'],
				'products_tax_class_id' => $_POST['products_tax_class_id'],
				'manufacturers_id' => $_POST['manufacturers_id'],
				'stock_id' => $_POST['stock_id'],
				'allow_mult_opts' => $_POST['allow_mult_opts'],
				'use_seo_urls' => $__FLAG['use_seo_urls']?1:0,
				'seo_url_text' => !empty(trim($_POST['seo_url_text']))?trim($_POST['seo_url_text']):NULL
			];

			if ($action == 'insert_product') {
				$insert_sql_data = array(
					'products_date_added' => 'now()'
				);

				$sql_data_array = array_merge($insert_sql_data, $sql_data_array);

				if (!empty($_POST['stock_id'])) {
					if (!($images = prepared_query::fetch('SELECT * FROM products_stock_control_images WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $_POST['stock_id']]))) {
						prepared_query::execute("INSERT INTO products_stock_control_images (stock_id, image, image_med, image_lrg) VALUES (:stock_id, 'newproduct_sm.gif', 'newproduct_med.gif', 'newproduct.gif')", [':stock_id' => $_POST['stock_id']]);
						// technically this is just extra overhead, we could create the array directly, but this ensures we don't miss anything that might be enforced directly by the DB
						$images = prepared_query::fetch('SELECT * FROM products_stock_control_images WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $_POST['stock_id']]);
					}

					foreach ($images as $col => $val) {
						if ($col == 'stock_id' || empty($val)) continue;
						$sql_data_array['products_'.$col] = $val;
					}
				}
				else {
					// a new product should never be created without a stock_id, but since it's allowed in the interface we'll account for it.
					$sql_data_array['products_image'] = 'newproduct_sm.gif';
					$sql_data_array['products_image_med'] = 'newproduct_med.gif';
					$sql_data_array['products_image_lrg'] = 'newproduct.gif';
				}

				$params = new ezparams($sql_data_array);
				$products_id = prepared_query::insert('INSERT INTO products ('.$params->insert_cols.') VALUES ('.$params->insert_params.')', $params->query_vals);

				prepared_query::execute('INSERT INTO products_to_categories (products_id, categories_id) values (?, ?)', array($products_id, $current_category_id));
			}
			elseif ($action == 'update_product') {
				//check to see if new price is different than old price
				// sneak stock_id into here since we'll need to check it for image management
				$price = prepared_query::fetch('SELECT products_price, products_price_modified, stock_id FROM products WHERE products_id = ?', cardinality::ROW, array($products_id));
				if ($p_price != $price['products_price']) $sqlpriceval = 'now()';
				else $sqlpriceval = $price['products_price_modified'];

				$update_sql_data = array(
					'products_last_modified' => 'now()',
					'products_price_modified' => $sqlpriceval
				);

				$sql_data_array = array_merge($update_sql_data, $sql_data_array);

				if ($_POST['stock_id'] != $price['stock_id']) {
					// if we've changed the parent IPN, get the new images

					// pre-clear all images currently attached to the product listing
					prepared_query::execute('UPDATE products SET products_image = NULL, products_image_med = NULL, products_image_lrg = NULL, products_image_sm_1 = NULL, products_image_xl_1 = NULL, products_image_sm_2 = NULL, products_image_xl_2 = NULL, products_image_sm_3 = NULL, products_image_xl_3 = NULL, products_image_sm_4 = NULL, products_image_xl_4 = NULL, products_image_sm_5 = NULL, products_image_xl_5 = NULL, products_image_sm_6 = NULL, products_image_xl_6 = NULL WHERE products_id = :products_id', [':products_id' => $products_id]);

					if (!($images = prepared_query::fetch('SELECT * FROM products_stock_control_images WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $_POST['stock_id']]))) {
						prepared_query::execute("INSERT INTO products_stock_control_images (stock_id, image, image_med, image_lrg) VALUES (:stock_id, 'newproduct_sm.gif', 'newproduct_med.gif', 'newproduct.gif')", [':stock_id' => $_POST['stock_id']]);
						// technically this is just extra overhead, we could create the array directly, but this ensures we don't miss anything that might be enforced directly by the DB
						$images = prepared_query::fetch('SELECT * FROM products_stock_control_images WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $_POST['stock_id']]);
					}

					foreach ($images as $col => $val) {
						if ($col == 'stock_id' || empty($val)) continue;
						$sql_data_array['products_'.$col] = $val;
					}
				}

				$params = new ezparams($sql_data_array);
				prepared_query::execute('UPDATE products SET '.$params->update_cols().' WHERE products_id = ?', $params->query_vals($products_id));
			}

			// product description
			$sql_data_array = [
				'products_ebay_name' => $_POST['products_ebay_name'][1],
				'products_name' => $_POST['products_name'][1],
				'products_google_name' => $_POST['products_google_name'][1],
				'products_description' => $_POST['products_description'][1],
				'products_url' => $_POST['products_url'][1],
				'products_head_title_tag' => $_POST['products_head_title_tag'][1],
				'products_head_desc_tag' => $_POST['products_head_desc_tag'][1]
			];

			if ($action == 'insert_product') {
				$insert_sql_data = [
					'products_id' => $products_id,
					'language_id' => 1
				];

				$sql_data_array = array_merge($insert_sql_data, $sql_data_array);

				$params = new ezparams($sql_data_array);
				prepared_query::execute('INSERT INTO products_description ('.$params->insert_cols.') VALUES ('.$params->insert_params.')', $params->query_vals);
			}
			elseif ($action == 'update_product') {
				$params = new ezparams($sql_data_array);
				prepared_query::execute('UPDATE products_description SET '.$params->update_cols().' WHERE products_id = ? AND language_id = ?', $params->query_vals([$products_id, 1]));
			}

			// products to categories
			$ckp = new ck_product_listing($products_id);
			$ckp->set_category_list($_POST['add-category']);

			// MMD: updating default addon description and price
			if (isset($_POST['pad_default_price']) || isset($_POST['pad_default_desc'])) {
				$pad_default_price = $_POST['pad_default_price'];
				$pad_default_desc = $_POST['pad_default_desc'];
				if ($pad_default_price == '') $pad_default_price = NULL;

				//check to see if we've created this row in the table yet
				$check_pad = prepared_query::fetch('SELECT * FROM product_addon_data WHERE product_id = ?', cardinality::ROW, array($products_id));
				if (empty($check_pad)) {
					prepared_query::execute('INSERT INTO product_addon_data VALUES (?, ?, ?, NOW())', array($products_id, $pad_default_price, $pad_default_desc));
				}
				else {
					prepared_query::execute('UPDATE product_addon_data SET default_price = ?, default_desc = ?, last_updated = NOW() WHERE product_id = ?', array($pad_default_price, $pad_default_desc, $products_id));
				}
			}

			//MMD: updating addon specific prices, descriptions, recommendations
			$pa_update_prefixes = [];
			foreach ($_POST as $key => $value) {
				if (strpos($key, "pa_") === 0) {
					$prefix = strtok($key, '_');
					$parentId = strtok('_');
					$childId = strtok('_');
					$prefix = $prefix.'_'.$parentId.'_'.$childId;

					$isNewPrefix = TRUE;
					foreach ($pa_update_prefixes as $i => $pa_update_prefix) {
						//MMD: this if block is terrible, but i could not get strcmp to work on
						// my dev machine - wonder if it was Win Vista compatability problems?
						if (strpos($prefix." ", $pa_update_prefix) === 0) $isNewPrefix = FALSE;
					}

					if ($isNewPrefix) array_push($pa_update_prefixes, $prefix);
				}
			}

			foreach ($pa_update_prefixes as $i => $pa_update_prefix) {
				//first we split the prefix and pick out the parent and child ids
				$tok = strtok($pa_update_prefix, '_');
				$parentId = strtok('_');
				$childId = strtok('_');
				$included = isset($_POST[$pa_update_prefix.'_included'])?1:0;
				$bundle_quantity = isset($_POST[$pa_update_prefix.'_bundle_quantity'])?$_POST[$pa_update_prefix.'_bundle_quantity']:1;
				$recommended = isset($_POST[$pa_update_prefix.'_recommended'])?1:0;
				$allow_mult_opts = $_POST[$pa_update_prefix.'_allow_mult_opts'];
				//echo $pa_update_prefixes[$i].' = '.$_POST[$pa_update_prefixes[$i].'_allow_mult_opts'];
				$use_custom_price = isset($_POST[$pa_update_prefix.'_use_custom_price'])?1:0;
				$use_custom_name = isset($_POST[$pa_update_prefix.'_use_custom_name'])?1:0;
				$use_custom_desc = isset($_POST[$pa_update_prefix.'_use_custom_desc'])?1:0;
				$custom_price = $_POST[$pa_update_prefix.'_custom_price']!=''?$_POST[$pa_update_prefix.'_custom_price']:0;
				$custom_name = $_POST[$pa_update_prefix.'_custom_name'];
				$custom_desc = $_POST[$pa_update_prefix.'_custom_desc'];

				prepared_query::execute('UPDATE product_addons SET recommended = ?, allow_mult_opts = ?, bundle_quantity = ?, use_custom_price = ?, use_custom_name = ?, use_custom_desc = ?, included = ?, custom_price = ?, custom_name = ?, custom_desc = ? WHERE product_id = ? AND product_addon_id = ?', array($recommended, $allow_mult_opts, $bundle_quantity, $use_custom_price, $use_custom_name, $use_custom_desc, $included, $custom_price, $custom_name, $custom_desc, $parentId, $childId));
			}
			CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$cPath.'&pID='.$products_id);
			break;
		case 'copy_to_confirm':
			if (isset($_POST['products_id']) && isset($_POST['categories_id'])) {
				$products_id = $_POST['products_id'];
				$categories_id = $_POST['categories_id'];

				if ($_POST['copy_as'] == 'link') {
					if ($categories_id != $current_category_id) {
						$check = prepared_query::fetch('select count(*) as total from products_to_categories where products_id = ? and categories_id = ?', cardinality::SINGLE, array($products_id, $categories_id));
						if ($check < '1') {
							prepared_query::execute("insert into products_to_categories (products_id, categories_id) values (?, ?)", array($products_id, $categories_id));
						}
					}
					else $messageStack->add_session(ERROR_CANNOT_LINK_TO_SAME_CATEGORY, 'error');
				}
				elseif ($_POST['copy_as'] == 'duplicate') {
					$product = prepared_query::fetch("select stock_id, products_quantity, products_model, products_image, products_image_med, products_image_lrg, products_image_sm_1, products_image_xl_1, products_image_sm_2, products_image_xl_2, products_image_sm_3, products_image_xl_3, products_image_sm_4, products_image_xl_4, products_image_sm_5, products_image_xl_5, products_image_sm_6, products_image_xl_6, products_price, products_date_available, products_weight, products_tax_class_id, manufacturers_id from products where products_id = ?", cardinality::ROW, array($products_id));

					$dup_products_id = prepared_query::insert("insert into products (stock_id, products_quantity, products_model, products_image, products_image_med, products_image_lrg, products_image_sm_1, products_image_xl_1, products_image_sm_2, products_image_xl_2, products_image_sm_3, products_image_xl_3, products_image_sm_4, products_image_xl_4, products_image_sm_5, products_image_xl_5, products_image_sm_6, products_image_xl_6, products_price, products_date_added, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), ?, ?, 0, ?, ?)", [$product['stock_id'], $product['products_quantity'], $product['products_model'], $product['products_image'], $product['products_image_med'], $product['products_image_lrg'], $product['products_image_sm_1'], $product['products_image_xl_1'], $product['products_image_sm_2'], $product['products_image_xl_2'], $product['products_image_sm_3'], $product['products_image_xl_3'], $product['products_image_sm_4'], $product['products_image_xl_4'], $product['products_image_sm_5'], $product['products_image_xl_5'], $product['products_image_sm_6'], $product['products_image_xl_6'], $product['products_price'], $product['products_date_available'], $product['products_weight'], $product['products_tax_class_id'], $product['manufacturers_id']]);

					prepared_query::execute("INSERT INTO products_description (products_id, language_id, products_name, products_ebay_name, products_description, products_head_title_tag, products_head_desc_tag, products_url, products_viewed) SELECT ?, language_id, products_name, products_ebay_name, products_description, products_head_title_tag, products_head_desc_tag, products_url, products_viewed FROM products_description WHERE products_id = ?", array($dup_products_id, $products_id));

					if ($_POST['copy_options'] == 'on') {
						$option_data = prepared_query::fetch('select default_price, default_desc from product_addon_data where product_id = ?', cardinality::ROW, array($products_id));
						prepared_query::execute('insert into product_addon_data(product_id, default_price, default_desc, last_updated) values (?, ?, ?, now())', array($dup_products_id, ($option_data['default_price']>0?$option_data['default_price']:NULL), $option_data['default_desc']));

						$options = prepared_query::fetch('select product_addon_id, recommended, allow_mult_opts, custom_price, custom_name, custom_desc, use_custom_price, use_custom_name, use_custom_desc, included from product_addons where product_id = ?', cardinality::SET, array($products_id));
						foreach ($options as $option) {
							prepared_query::execute('insert into product_addons(product_id, product_addon_id, recommended, allow_mult_opts, custom_price, custom_name, custom_desc, use_custom_price, use_custom_name, use_custom_desc, last_updated, included) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now(), ?)', array($dup_products_id, $option['product_addon_id'], $option['recommended'], $option['allow_mult_opts'], $option['custom_price'], $option['custom_name'], $option['custom_desc'], $option['use_custom_price'], $option['use_custom_name'], $option['use_custom_desc'], $option['included']));
						}
					}

					prepared_query::execute("insert into products_to_categories (products_id, categories_id) values (?, ?)", array($dup_products_id, $categories_id));

					// BOF: WebMakers.com Added: Attributes Copy on non-linked
					$products_id = $dup_products_id;
				}
			}
			CK\fn::redirect_and_exit('/admin/categories.php?cPath='.$categories_id.'&pID='.$products_id);
			break;
		case 'new_product_preview':
			// previously, all that was managed here was the saving of uploaded images, so they could be displayed on the new product preview page
			// now we just need to associate the stock ID if we've got a name but no ID

			if (empty($_POST['stock_id'])) {
				if (!empty($_POST['stock_name'])) $_POST['stock_id'] = prepared_query::fetch('SELECT stock_id FROM products_stock_control WHERE stock_name LIKE ?', cardinality::SINGLE, array($_POST['stock_name']));
				elseif (!empty($_GET['pID'])) $_POST['stock_id'] = prepared_query::fetch('SELECT stock_id FROM products WHERE products_id = ?', cardinality::SINGLE, $_GET['pID']);
				else $_POST['stock_id'] = NULL;
			}

			break;
	}
}

// check if the catalog image directory exists
if (is_dir(DIR_FS_CATALOG_IMAGES)) {
	if (!is_writeable(DIR_FS_CATALOG_IMAGES)) $messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE, 'error');
}
else {
	$messageStack->add(ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST, 'error');
}

// WebMakers.com Added: Display Order
switch (CATEGORIES_SORT_ORDER) {
	case 'products_name':
		$order_it_by = 'pd.products_name';
		break;
	case 'products_name-desc':
		$order_it_by = 'pd.products_name DESC';
		break;
	case 'model':
		$order_it_by = 'p.products_model';
		break;
	case 'model-desc':
		$order_it_by = 'p.products_model DESC';
		break;
	default:
		$order_it_by = 'pd.products_name';
		break;
}

$go_back_to = @$REQUEST_URI;

function get_parent_cats($category_id, $reset=false) {
	global $parent_cat_array;
	if ($reset) $parent_cat_array=[];
	$parent_id = prepared_query::fetch("select parent_id from categories where categories_id = :categories_id", cardinality::SINGLE, [':categories_id' => $category_id]);
	if ($parent_id == 0) return;
	else {
		array_push($parent_cat_array, $parent_id);
		get_parent_cats($parent_id);
		return $parent_cat_array;
	}
}

/*---------------------------------
// begin page display
---------------------------------*/
if (in_array($action, ['new_product', 'new_product_preview'])) include('categories-product.php');
elseif (in_array($_GET['action'], ['new_category', 'edit_category', 'new_category_preview'])) include('categories-category.php');
else include('categories-hierarchy.php');
/*---------------------------------
// end page display
---------------------------------*/
?>
