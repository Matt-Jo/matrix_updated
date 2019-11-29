<?php
require('ipn_editor_top.php');
require_once('includes/functions/po_alloc.php');

ini_set('memory_limit', '1000M');

if (!empty($_GET['mode']) && $_GET['mode'] == 'new') CK\fn::redirect_and_exit('/admin/quick-ipn-create.php');

if (!empty($_REQUEST['stock_id'])) $ipn = new ck_ipn2($_REQUEST['stock_id']);
elseif (!empty($_REQUEST['ipnId'])) $ipn = ck_ipn2::get_ipn_by_ipn($_REQUEST['ipnId']);
else $ipn = NULL;

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;
$sub_action = !empty($_REQUEST['sub-action'])?$_REQUEST['sub-action']:NULL;

if ($__FLAG['ajax']) {
	$result = [];
	switch ($action) {
		case 'exclude_forecast':
			if (in_array($_SESSION['perms']['admin_groups_id'], [1, 5, 7, 10, 17, 20, 21, 27, 29, 30])) {
				try {
					foreach ($_REQUEST['exclude_op'] as $orders_products_id => $status) {
						prepared_query::execute('UPDATE orders_products SET exclude_forecast = ? WHERE orders_products_id = ?', [$status, $orders_products_id]);
					}
					$result['status'] = 1;
				}
				catch (Exception $e) {
					$result['status'] = 0;
					$result['message'] = $e->getMessage();
				}
			}
			else {
				$result['status'] = 0;
				$result['message'] = 'You do not have permissions to perform this action.';
			}
			break;
		case 'vendor_search':
			$vendor_response = prepared_query::fetch('SELECT v.vendors_id, v.vendors_company_name FROM vendors v WHERE v.vendors_company_name LIKE ?', cardinality::SET, $_REQUEST['search_string'].'%');
			echo '<ul>';
			foreach ($vendor_response as $row) {
				echo '<li id="'.$row['vendors_id'].'">'.$row['vendors_company_name'].'</li>';
			}
			echo '</ul>';
			exit();
			break;
		case 'get_serial_showver':
			$serial = new ck_serial($_GET['serial_id']);
			$result['show_version'] = nl2br($serial->get_current_history()['show_version']);
			break;
		case 'get_serial_history':
			$serial = new ck_serial($_GET['serial_id']);
			$result['records'] = [];
			foreach ($serial->get_history() as $record) {
				$result['records'][] = [
					'cost' => CK\text::monetize($record['cost']),
					'entered_date' => $record['entered_date']->format('M/d/Y'),
					'po_number' => $record['po_number'],
					'orders_id' => !empty($record['orders_id'])?$record['orders_id']:'',
					'shipped_date' => !empty($record['shipped_date'])?$record['shipped_date']->format('M/d/Y'):'',
					'condition' => $record['condition'],
					'dram' => !empty($record['dram'])?$record['dram']:'',
					'flash' => !empty($record['flash'])?$record['flash']:'',
					'ios' => !empty($record['ios'])?$record['ios']:'',
					'show_version' => !empty($record['show_version'])?nl2br($record['show_version']):'',
					'short_notes' => !empty($record['short_notes'])?nl2br($record['short_notes']):''
				];
			}
			break;
		case 'relationship-lookup':
			$results = ['results' => []];
			$stock_id = $_GET['stock_id'];
			$target_resource = strtolower(trim($_GET['relationship_type_field']));
			$target_name = trim($_GET['relationship_name_field']);
			switch ($target_resource) {
				case 'product listing':
					if ($products = prepared_query::fetch('SELECT products_id, products_model FROM products WHERE stock_id = :stock_id AND products_model LIKE :products_model ORDER BY products_model ASC, products_id ASC', cardinality::SET, [':stock_id' => $stock_id, ':products_model' => '%'.$target_name.'%'])) {
						foreach ($products as $product) {
							$product['result_id'] = $product['products_id'];
							$product['field_value'] = $product['products_model'];
							$product['result_label'] = !empty($target_name)?preg_replace('/('.$target_name.')/i', '<strong>$1</strong>', $product['products_model']):$product['products_model'];
							$product['result_label'] .= ' ['.$product['products_id'].']';
							$product['value'] = $product['products_id'];
							$results['results'][] = $product;
						}
					}
					break;
				case 'vendor':
					if ($vendors = prepared_query::fetch('SELECT v.vendors_id, v.vendors_company_name FROM vendors v JOIN vendors_to_stock_item vtsi ON v.vendors_id = vtsi.vendors_id WHERE vtsi.stock_id = :stock_id AND v.vendors_company_name LIKE :vendor_name ORDER BY v.vendors_company_name', cardinality::SET, [':stock_id' => $stock_id, ':vendor_name' => '%'.$target_name.'%'])) {
						foreach ($vendors as $vendor) {
							$vendor['result_id'] = $vendor['vendors_id'];
							$vendor['field_value'] = $vendor['vendors_company_name'];
							$vendor['result_label'] = !empty($target_name)?preg_replace('/('.$target_name.')/i', '<strong>$1</strong>', $vendor['vendors_company_name']):$vendor['vendors_company_name'];
							$vendor['value'] = $vendor['vendors_id'];
							$results['results'][] = $vendor;
						}
					}
					break;
			}

			echo json_encode($results);
			exit();
			break;
		case 'archive-product':
			if (prepared_query::execute('UPDATE products SET archived = 1 WHERE products_id = :products_id', [':products_id' => $_REQUEST['products_id']])) $result = ['success' => true];
			else $result = ['success' => false];
			break;
	}
	echo json_encode($result);
	exit();
}

switch ($action) {
	case 'add-to-cart':
		$cart = ck_cart::instance();
		$product = new ck_product_listing($_REQUEST['products_id']);
		$quote = NULL;
		if ($product->get_price('reason') != $_REQUEST['price_level']) {
			$quote['price'] = $product->get_price($_REQUEST['price_level']);
			$quote['reason'] = $_REQUEST['price_level'];
		}
		$cart->update_product($product, $_REQUEST['qty'], $is_total=FALSE, $parent_products_id=NULL, $option_type=NULL, $cart_shipment_id=NULL, $allow_discontinued=TRUE, $quote);
		CK\fn::redirect_and_exit('/shopping_cart.php');
		break;
	case 'save_general':
		switch ($sub_action) {
			case 'update':
				$header = $ipn->get_header();
				$header_update = [];
				$extra_update = [];
				$create_package = [];

				if (CK\text::demonetize($_POST['stock_price']) != $header['stock_price']) $header_update['stock_price'] = CK\text::demonetize($_POST['stock_price']);
				if (CK\text::demonetize($_POST['dealer_price']) != $header['dealer_price']) $header_update['dealer_price'] = CK\text::demonetize($_POST['dealer_price']);
				if (CK\text::demonetize($_POST['wholesale_high_price']) != $header['wholesale_high_price']) $header_update['wholesale_high_price'] = CK\text::demonetize($_POST['wholesale_high_price']);
				if (CK\text::demonetize($_POST['wholesale_low_price']) != $header['wholesale_low_price']) $header_update['wholesale_low_price'] = CK\text::demonetize($_POST['wholesale_low_price']);

				if ($_POST['stock_description'] != $header['stock_description']) $header_update['stock_description'] = $_POST['stock_description'];
				if ($_POST['conditioning_notes'] != $header['conditioning_notes']) $header_update['conditioning_notes'] = $_POST['conditioning_notes'];
				if ($_POST['conditions'] != $header['conditions']) $header_update['conditions'] = $_POST['conditions'];
				if ($_POST['max_displayed_quantity'] != $header['max_displayed_quantity']) $header_update['max_displayed_quantity'] = $_POST['max_displayed_quantity'];
				if ($_POST['min_inventory_level'] != $header['min_inventory_level']) $header_update['min_inventory_level'] = $_POST['min_inventory_level'];
				if ($_POST['target_inventory_level'] != $header['target_inventory_level']) $header_update['target_inventory_level'] = $_POST['target_inventory_level'];
				if ($_POST['max_inventory_level'] != $header['max_inventory_level']) $header_update['max_inventory_level'] = $_POST['max_inventory_level'];
				if ($_POST['eccn_code'] != $header['eccn_code']) $header_update['eccn_code'] = $_POST['eccn_code'];
				if ($_POST['hts_code'] != $header['hts_code']) $header_update['hts_code'] = $_POST['hts_code'];

				if ($__FLAG['discontinued'] != $ipn->is('discontinued')) $header_update['discontinued'] = $__FLAG['discontinued']?1:0;
				if ($__FLAG['is_bundle'] != $ipn->is('is_bundle')) $header_update['is_bundle'] = $__FLAG['is_bundle']?1:0;
				if ($__FLAG['serialized'] != $ipn->is('serialized')) $header_update['serialized'] = $__FLAG['serialized']?1:0;
				if ($__FLAG['drop_ship'] != $ipn->is('drop_ship')) $header_update['drop_ship'] = $__FLAG['drop_ship']?1:0;
				if ($__FLAG['non_stock'] != $ipn->is('non_stock')) $header_update['non_stock'] = $__FLAG['non_stock']?1:0;
				if ($__FLAG['freight'] != $ipn->is('freight')) $header_update['freight'] = $__FLAG['freight']?1:0;
				if ($__FLAG['dlao_product'] != $ipn->is('dlao_product')) $header_update['dlao_product'] = $__FLAG['dlao_product']?1:0;
				if ($__FLAG['special_order_only'] != $ipn->is('special_order_only')) $header_update['special_order_only'] = $__FLAG['special_order_only']?1:0;

				if ($__FLAG['donotbuy'] != $ipn->is('donotbuy')) {
					$header_update['donotbuy'] = $__FLAG['donotbuy']?1:0;
					$header_update['donotbuy_date'] = $__FLAG['donotbuy']?ck_ipn2::NOW()->format('Y-m-d'):NULL;
					$header_update['donotbuy_user_id'] = $__FLAG['donotbuy']?$_SESSION['admin_id']:NULL;
				}

				if ($__FLAG['confirm_stock_price']) $header_update['last_stock_price_confirmation'] = ck_ipn2::NOW()->format('Y-m-d');
				if ($__FLAG['confirm_dealer_price']) $header_update['last_dealer_price_confirmation'] = ck_ipn2::NOW()->format('Y-m-d');
				if ($__FLAG['confirm_wholesale_high_price']) $header_update['last_wholesale_high_price_confirmation'] = ck_ipn2::NOW()->format('Y-m-d');
				if ($__FLAG['confirm_wholesale_low_price']) $header_update['last_wholesale_low_price_confirmation'] = ck_ipn2::NOW()->format('Y-m-d');

				if ($__FLAG['update_pricing_review'] && !empty($_POST['pricing_review']) && $_POST['pricing_review'] != $header['pricing_review']) $header_update['pricing_review'] = $_POST['pricing_review'];

				if ($_POST['stock_location'] != $header['bin1']) $extra_update['stock_location'] = $_POST['stock_location'];
				if ($_POST['stock_location_2'] != $header['bin2']) $extra_update['stock_location_2'] = $_POST['stock_location_2'];

				if (isset($header_update['stock_price']) || isset($header_update['dealer_price'])) {
					if (!empty($ipn->get_price('customers'))) $messageStack->add('Please update Special Pricing for this IPN as necessary.', 'message');
					else $messageStack->add('Be sure to review the target buy price.', 'message');
				}

				if ($__FLAG['is_package']) {
					$create_package['package_name'] = $_POST['package_name'];
					$create_package['package_length'] = $_POST['package_length'];
					$create_package['package_width'] = $_POST['package_width'];
					$create_package['package_height'] = $_POST['package_height'];
					$create_package['package_logoed'] = $_POST['package_logoed'];
				}

				$ipn->update($header_update, $extra_update, $create_package);
				break;
			case 'quantity_update':
				if (!$ipn->is('serialized')) {
					$qty_delta = (int) $_POST['quantity'];

					if ($qty_delta < 0) {
						$error_message = 'The value of the quantity change must be a positive integer.';
						break 2;
					}

					$ipn->update_qty($qty_delta, @$_POST['update_direction'], $__FLAG['quantity_confirmed']);
				}
				else {
					$ipn->confirm_serialized_qty();
				}

				break;
			case 'weight_update':
				$new_weight = CK\text::numerify($_POST['weight']);

				if ($ipn->get_header('stock_weight') != $new_weight) {
					if ($new_weight < 0) {
						$error_message = 'The value of the weight change cannot be less than 0.';
						break 2;
					}

					$ipn->update_weight($new_weight, $__FLAG['weight_confirmed']);
				}
				break;
			case 'average_cost_update':
				$new_average_cost = CK\text::demonetize($_POST['average_cost'], NULL);

				if ($ipn->get_header('average_cost') != $new_average_cost) {
					if ($new_average_cost < 0) {
						$error_message = 'The value of the average cost cannot be less than 0.';
						break 2;
					}

					$ipn->update_average_cost($new_average_cost);
				}
				break;
			case 'target_buy_price_update':
				$new_target_buy_price = CK\text::demonetize($_POST['target_buy_price']);

				if ($ipn->get_header('target_buy_price') != $new_target_buy_price) {
					if ($new_target_buy_price < 0) {
						$error_message = 'The value of the target buy price cannot be less than 0.';
						break 2;
					}

					$ipn->update_target_buy_price($new_target_buy_price);
				}
				break;
			case 'name_update':
				$new_stock_name = trim($_POST['stock_name']);

				if ($ipn->get_header('ipn') != $new_stock_name) {
					if ($existing_ipn = ck_ipn2::get_ipn_by_ipn($new_stock_name)) {
						CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.urlencode($ipn->get_header('ipn')).'&error=same_name');
						break 2;
					}

					$ipn->update_stock_name($new_stock_name);
				}
				break;
			case 'target_min_qty_update':
				$new_target_min_qty = (int) $_POST['target_min_qty'];

				if ($ipn->get_header('target_min_qty') != $new_target_min_qty) {
					if ($new_target_min_qty < 0) {
						$error_message = 'The value of the target min qty cannot be less than 0.';
						break 2;
					}

					$ipn->update_target_min_qty($new_target_min_qty);
				}
				break;
			case 'target_max_qty_update':
				$new_target_max_qty = (int) $_POST['target_max_qty'];

				if ($ipn->get_header('target_max_qty') != $new_target_max_qty) {
					if ($new_target_max_qty < 0) {
						$error_message = 'The value of the target max qty cannot be less than 0.';
						break 2;
					}

					$ipn->update_target_max_qty($new_target_max_qty);
				}
				break;
			case 'mark_as_reviewed_update':
				$ipn->create_change_history_record('Marked As Reviewed', '---', '---');
				break;
			case 'category_update':
				$new_products_stock_control_category_id = $_POST['category_id'];

				if ($ipn->get_header('products_stock_control_category_id') != $new_products_stock_control_category_id) {
					$ipn->update_products_stock_control_category($new_products_stock_control_category_id);
				}
				break;
			case 'warranty_update':
				$new_warranty_id = $_POST['warranty'];

				if ($ipn->get_header('warranty_id') != $new_warranty_id) {
					$ipn->update_warranty($new_warranty_id);
				}
				break;
			case 'dealer_warranty_update':
				$new_dealer_warranty_id = $_POST['dealer_warranty'];

				if ($ipn->get_header('dealer_warranty_id') != $new_dealer_warranty_id) {
					$ipn->update_dealer_warranty($new_dealer_warranty_id);
				}
				break;
			case 'delete':
				$ipn->destroy();
				break;
			default:
				break;
		}

		CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.urlencode($ipn->get_header('ipn')).'&selectedTab=0');
		break;
	case 'setflag':
		switch ($sub_action) {
			case 'set_status':
				$product = new ck_product_listing($_REQUEST['products_id']);
				if ($product->is('products_status') != $__FLAG['flag']) {
					$product->update_status($__FLAG['flag']);
				}
				break;
			case 'set_broker_status':
				$product = new ck_product_listing($_REQUEST['products_id']);
				if ($product->is('broker_status') != $__FLAG['flag']) {
					$product->update_broker_status($__FLAG['flag']);
				}
				break;
			case 'set_container_status':
				$container = ck_merchandising_container_manager::instantiate($_GET['container_type_id'], $_GET['container_id']);
				$__FLAG['flag']?$container->activate():$container->deactivate();
				break;
			case 'set_level1product':
				$product = new ck_product_listing($_REQUEST['products_id']);
				if ($product->is('level_1_product') != $__FLAG['flag']) {
					$product->update_level_1_product($__FLAG['flag']);
				}
				break;
			case 'set_special_status':
				tep_set_specials_status($_REQUEST['specials_id'], ($__FLAG['flag']?1:0));
				break;
			default:
				break;
		}

		CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.urlencode($ipn->get_header('ipn')).'&selectedTab=1');
		break;
	case 'upload_images':
		$remove = !empty($_POST['remove'])?$_POST['remove']:[];

		$ipn_images = prepared_query::fetch('SELECT '.implode(', ', picture_audit::field_list()).' FROM products_stock_control_images WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $ipn->id()]);
		$prod_images = prepared_query::fetch('SELECT products_id, '.implode(', ', picture_audit::field_list('products')).' FROM products WHERE stock_id = :stock_id', cardinality::SET, [':stock_id' => $ipn->id()]);

		$slot_a = !empty($_FILES['slot_a']['name'])&&$_FILES['slot_a']['error']===0?$_FILES['slot_a']:NULL;
		$slot_b = !empty($_FILES['slot_b']['name'])&&$_FILES['slot_b']['error']===0?$_FILES['slot_b']:NULL;
		$slot_c = !empty($_FILES['slot_c']['name'])&&$_FILES['slot_c']['error']===0?$_FILES['slot_c']:NULL;
		$slot_d = !empty($_FILES['slot_d']['name'])&&$_FILES['slot_d']['error']===0?$_FILES['slot_d']:NULL;
		$slot_e = !empty($_FILES['slot_e']['name'])&&$_FILES['slot_e']['error']===0?$_FILES['slot_e']:NULL;
		$slot_f = !empty($_FILES['slot_f']['name'])&&$_FILES['slot_f']['error']===0?$_FILES['slot_f']:NULL;
		$slot_g = !empty($_FILES['slot_g']['name'])&&$_FILES['slot_g']['error']===0?$_FILES['slot_g']:NULL;

		// we're overriding the selected action to automatically remove old images
		if ($slot_a) $remove['a'] = 'up';
		if ($slot_b) $remove['b'] = 'up';
		if ($slot_c) $remove['c'] = 'up';
		if ($slot_d) $remove['d'] = 'up';
		if ($slot_e) $remove['e'] = 'up';
		if ($slot_f) $remove['f'] = 'up';
		if ($slot_g) $remove['g'] = 'up';

		$image_errors = [];

		$slots = ['a', 'b', 'c', 'd', 'e', 'f', 'g'];

		// check for all errros first
		$corrected_ipn = preg_replace('#/#', '$', $ipn->get_header('ipn'));
		foreach ($slots as $idx => $slot) {
			$slotnm = 'slot_'.$slot;
			if ($$slotnm) {
				if (${$slotnm}['name'] != $corrected_ipn.$slot.'.jpg')
					$image_errors[] = 'Slot '.strtoupper($slot).' Name is not correct: [Name - '.${$slotnm}['name'].'] [Expected - '.$corrected_ipn.$slot.'.jpg]';

				$dim = imagesizer::dim(${$slotnm}['tmp_name']);

				if ($dim['width'] != imagesizer::$map['archive']['width'] || $dim['height'] != imagesizer::$map['archive']['height'])
					$image_errors[] = 'Slot '.strtoupper($slot).' Dimensions are not correct: [Width - '.$dim['width'].'] [Height - '.$dim['height'].']';
			}
		}
		if (!empty($image_errors)) break;

		foreach ($remove as $slot => $on) { // all values are "on", because they're checkboxes
			$deprecate = [];
			$query_suffix = 'WHERE stock_id = ?';

			$anything = FALSE;
			$query = 'INSERT INTO products_stock_control_images (stock_id, ';
			$fields = [];
			$values = [];
			foreach (picture_audit::$image_slots[$slot]['ipn'] as $size => $field) {
				// if we uploaded a new image, we've already set the database accordingly
				if ($on != 'up') {
					$fields[$field] = $slot=='a'?"'".imagesizer::$newproduct[$size]."'":'NULL';
				}

				if (empty($ipn_images[$field])) continue; // there's no image reference to move
				if (preg_match('/newproduct/', $ipn_images[$field])) continue; // the existing image reference is to the newproducts.gif which needs to stay put
				if (preg_match('/deprecated/', $ipn_images[$field])) continue; // the existing image reference is to an image in the deprecated folder, which should stay put

				$other_ipns = prepared_query::fetch('SELECT * FROM products_stock_control_images WHERE stock_id != ? AND ('.implode(' = ? OR ', picture_audit::field_list()).' = ?)', cardinality::SET, [$ipn->id(), $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field]]);

				$other_products = prepared_query::fetch('SELECT * FROM products WHERE stock_id != ? AND ('.implode(' = ? OR ', picture_audit::field_list('products')).' = ?)', cardinality::SET, [$ipn->id(), $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field], $ipn_images[$field]]);

				if (empty($other_ipns) && empty($other_products)) $deprecate[] = ['size' => $size, 'ref' => $ipn_images[$field]];
			}

			// clear out the DB references for the IPN
			if (!empty($fields)) {
				$params = new ezparams($fields);
				prepared_query::execute('INSERT INTO products_stock_control_images (stock_id, '.$params->insert_cols.') VALUES (?, '.implode(', ', array_values($fields)).') ON DUPLICATE KEY UPDATE '.$params->insert_ondupe, [$ipn->id()]);
			}

			$anything = FALSE;
			$query = 'UPDATE products SET';
			foreach (picture_audit::$image_slots[$slot]['p'] as $size => $field) {
				if ($on != 'up') {
					if ($anything) $query .= ','; // if we've already got something, delimit
					$query .= ' '.$field.' = '.($slot=='a'?"'".imagesizer::$newproduct[$size]."'":'NULL');
					$anything = TRUE;
				}

				foreach ($prod_images as $product) {
					if (empty($product[$field])) continue; // there's no image reference to move
					if (preg_match('/newproduct/', $product[$field])) continue; // the existing image reference is to the newproducts.gif which needs to stay put
					if (preg_match('/deprecated/', $product[$field])) continue; // the existing image reference is to an image in the deprecated folder, which should stay put

					$other_ipns = prepared_query::fetch('SELECT * FROM products_stock_control_images WHERE stock_id != ? AND ('.implode(' = ? OR ', picture_audit::field_list()).' = ?)', cardinality::SET, [$ipn->id(), $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field]]);

					$other_products = prepared_query::fetch('SELECT * FROM products WHERE stock_id != ? AND ('.implode(' = ? OR ', picture_audit::field_list('products')).' = ?)', cardinality::SET, [$ipn->id(), $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field], $product[$field]]);

					if (empty($other_ipns) && empty($other_products)) $deprecate[] = ['size' => $size, 'ref' => $product[$field]];
				}
			}

			// clear out the DB references for the products
			if ($anything) prepared_query::execute($query.' WHERE stock_id = ?', [$ipn->id()]);

			foreach ($deprecate as $reference) {
				if ($reference['size'] == 'lrg') {
					//$sz300 = imagesizer::ref_300($reference['ref']);
					//if (file_exists(picture_audit::$imgfolder.'/'.$sz300)) @rename(picture_audit::$imgfolder.'/'.$sz300, picture_audit::$imgfolder.'/deprecated/'.$sz300);
					if (file_exists(picture_audit::$imgfolder.'/archive/'.$reference['ref'])) @rename(picture_audit::$imgfolder.'/archive/'.$reference['ref'], picture_audit::$imgfolder.'/deprecated/arch.'.$reference['ref']);
				}
				if (file_exists(picture_audit::$imgfolder.'/'.$reference['ref'])) @rename(picture_audit::$imgfolder.'/'.$reference['ref'], picture_audit::$imgfolder.'/deprecated/'.$reference['ref']);
			}
		}

		foreach ($slots as $idx => $slot) {
			$slotnm = 'slot_'.$slot;

			if (empty($$slotnm)) continue;

			$baseref = picture_audit::$imgfolder.'/archive/'.${$slotnm}['name'];
			@rename(${$slotnm}['tmp_name'], $baseref);

			imagesizer::resize($baseref, imagesizer::$map['lrg'], picture_audit::$imgfolder, 'p/'.${$slotnm}['name'], TRUE);
			//imagesizer::resize($baseref, imagesizer::$map['300'], picture_audit::$imgfolder, 'p/'.imagesizer::ref_300(${$slotnm}['name']), TRUE);
			if ($slot == 'a') imagesizer::resize($baseref, imagesizer::$map['med'], picture_audit::$imgfolder, 'p/'.imagesizer::ref_med(${$slotnm}['name']), TRUE);
			imagesizer::resize($baseref, imagesizer::$map['sm'], picture_audit::$imgfolder, 'p/'.imagesizer::ref_sm(${$slotnm}['name']), TRUE);

			if ($slot == 'a') {
				prepared_query::execute('INSERT INTO products_stock_control_images (stock_id, image, image_med, image_lrg) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE image=VALUES(image), image_med=VALUES(image_med), image_lrg=VALUES(image_lrg)', [$ipn->id(), 'p/'.imagesizer::ref_sm(${$slotnm}['name']), 'p/'.imagesizer::ref_med(${$slotnm}['name']), 'p/'.${$slotnm}['name']]);

				prepared_query::execute('UPDATE products SET products_image = ?, products_image_med = ?, products_image_lrg = ? WHERE stock_id = ?', ['p/'.imagesizer::ref_sm(${$slotnm}['name']), 'p/'.imagesizer::ref_med(${$slotnm}['name']), 'p/'.${$slotnm}['name'], $ipn->id()]);
			}
			else {
				prepared_query::execute('INSERT INTO products_stock_control_images (stock_id, image_sm_'.$idx.', image_xl_'.$idx.') VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE image_sm_'.$idx.'=VALUES(image_sm_'.$idx.'), image_xl_'.$idx.'=VALUES(image_xl_'.$idx.')', [$ipn->id(), 'p/'.imagesizer::ref_sm(${$slotnm}['name']), 'p/'.${$slotnm}['name']]);

				prepared_query::execute('UPDATE products SET products_image_sm_'.$idx.' = ?, products_image_xl_'.$idx.' = ? WHERE stock_id = ?', ['p/'.imagesizer::ref_sm(${$slotnm}['name']), 'p/'.${$slotnm}['name'], $ipn->id()]);
			}

			if (!service_locator::get_config_service()->is_production()) continue; //if we're not in production, don't clear the cache

			$edgecast_account_num = '7FCA';
			$edgecast_api_token = 'tok:df4a4a8a-3e20-44da-a412-8ab86341ecaf';

			$request = new request();
			$data = ['MediaPath' => 'https://media.cablesandkits.com/p/'.${$slotnm}['name'], 'MediaType' => 8];
			$url = 'https://api.edgecast.com/v2/mcc/customers/'.$edgecast_account_num.'/edge/purge';
			$request->opt(CURLOPT_HTTPHEADER, ['Authorization: '.$edgecast_api_token, 'Accept: Application/JSON', 'Content-Type: Application/JSON']);
			//$request->opt(CURLINFO_HEADER_OUT, TRUE);
			//$request->opt(CURLOPT_VERBOSE, TRUE);
			$purge_response = $request->put($url, json_encode($data));
			// we don't really care, but we might at some point check this response and alert if the purge didn't go through
			// we might also at some point check to make sure that we even need to purge in the first place
			if ($request->status() != 200 || (($purge_response = json_decode($purge_response)) && empty($purge_response->Id))) {
				$image_errors[] = 'Slot '.strtoupper($slot).' uploaded successfully, but CDN cache failed to purge automatically for the FULL size image';
			}
			/*$data['MediaPath'] = 'https://media.cablesandkits.com/p/'.imagesizer::ref_300(${$slotnm}['name']);
			$purge_response = $request->put($url, json_encode($data));
			if ($request->status() != 200 || (($purge_response = json_decode($purge_response)) && empty($purge_response->Id))) {
				$image_errors[] = 'Slot '.strtoupper($slot).' uploaded successfully, but CDN cache failed to purge automatically for the 300 size image';
			}*/
			if ($slot == 'a') {
				$data['MediaPath'] = 'https://media.cablesandkits.com/p/'.imagesizer::ref_med(${$slotnm}['name']);
				$purge_response = $request->put($url, json_encode($data));
				if ($request->status() != 200 || (($purge_response = json_decode($purge_response)) && empty($purge_response->Id))) {
					$image_errors[] = 'Slot '.strtoupper($slot).' uploaded successfully, but CDN cache failed to purge automatically for the MED size image';
				}
			}
			$data['MediaPath'] = 'https://media.cablesandkits.com/p/'.imagesizer::ref_sm(${$slotnm}['name']);
			$purge_response = $request->put($url, json_encode($data));
			if ($request->status() != 200 || (($purge_response = json_decode($purge_response)) && empty($purge_response->Id))) {
				$image_errors[] = 'Slot '.strtoupper($slot).' uploaded successfully, but CDN cache failed to purge automatically for the SM size image';
			}
		}

		CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.urlencode($ipn->get_header('ipn')).'&selectedTab=1');
		break;
	case 'save_vendor':
		switch ($sub_action) {
			case 'update':
				$vendor_relationship_id = $_POST['vendor_relationship_id'];

				$vendor = $ipn->get_vendors($vendor_relationship_id);

				$data = [];

				if ($vendor['price'] != CK\text::demonetize($_POST['vendors_price'][$vendor_relationship_id])) $data['vendors_price'] = $_POST['vendors_price'][$vendor_relationship_id];
				if ($vendor['part_number'] != $_POST['vendors_pn'][$vendor_relationship_id]) $data['vendors_pn'] = $_POST['vendors_pn'][$vendor_relationship_id];
				if ($vendor['case_qty'] != (int) $_POST['case_qty'][$vendor_relationship_id]) $data['case_qty'] = (int) $_POST['case_qty'][$vendor_relationship_id];
				if ($vendor['always_available'] != CK\fn::check_flag(@$_POST['always_avail'][$vendor_relationship_id])) $data['always_avail'] = CK\fn::check_flag(@$_POST['always_avail'][$vendor_relationship_id])?1:0;
				if ($vendor['lead_time'] != (int) $_POST['lead_time'][$vendor_relationship_id]) $data['lead_time'] = (int) $_POST['lead_time'][$vendor_relationship_id];
				if ($vendor['notes'] != $_POST['notes'][$vendor_relationship_id]) $data['notes'] = $_POST['notes'][$vendor_relationship_id];
				if ($vendor['preferred'] != CK\fn::check_flag(@$_POST['preferred'][$vendor_relationship_id])) $data['preferred'] = CK\fn::check_flag(@$_POST['preferred'][$vendor_relationship_id])?1:0;
				if ($vendor['secondary'] != CK\fn::check_flag(@$_POST['secondary'][$vendor_relationship_id])) $data['secondary'] = CK\fn::check_flag(@$_POST['secondary'][$vendor_relationship_id])?1:0;

				if (!empty($data)) $ipn->update_vendor_relationship($vendor_relationship_id, $data);

				break;
			case 'add':
				$data = [
					'vendors_id' => $_POST['vendors_id']['new'],
					'always_avail' => CK\fn::check_flag(@$_POST['always_avail']['new'])?1:0,
					'preferred' => CK\fn::check_flag(@$_POST['preferred']['new'])?1:0,
					'secondary' => CK\fn::check_flag(@$_POST['secondary']['new'])?1:0
				];
				if (CK\text::demonetize($_POST['vendors_price']['new'])) $data['vendors_price'] = CK\text::demonetize($_POST['vendors_price']['new']);
				if (!empty($_POST['vendors_pn']['new'])) $data['vendors_pn'] = $_POST['vendors_pn']['new'];
				if (!empty((int) $_POST['case_qty']['new'])) $data['case_qty'] = (int) $_POST['case_qty']['new'];
				if (!empty((int) $_POST['lead_time']['new'])) $data['lead_time'] = (int) $_POST['lead_time']['new'];
				if (!empty($_POST['notes']['new'])) $data['notes'] = $_POST['notes']['new'];

				$ipn->create_vendor_relationship($data);

				break;
			case 'delete':
				$vendor_relationship_id = $_REQUEST['vendor_relationship_id'];
				$ipn->delete_vendor_relationship($vendor_relationship_id);
				break;
			default:
				break;
		}

		CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.urlencode($ipn->get_header('ipn')).'&selectedTab='.($ipn->is('serialized')?9:8));
		break;
	case 'save_upc':
		$action_button = $_POST['action_button'];
		foreach ($action_button as $upc_assignment_id => $sub_action) {
			// this is literally just to grab the passed assignment ID and sub action
		}

		$target_resource_map = [
			'product listing' => 'products',
			'vendor' => 'vendors_to_stock_item'
		];

		switch ($sub_action) {
			case 'update':
				$upc_assignment = $ipn->get_upcs($upc_assignment_id);

				$data = [];

				if (!empty($target_resource_map[strtolower($_POST['target_resource'][$upc_assignment_id])])) $_POST['target_resource'][$upc_assignment_id] = $target_resource_map[strtolower($_POST['target_resource'][$upc_assignment_id])];

				if ($upc_assignment['uom_description'] != $_POST['uom_description'][$upc_assignment_id]) $data['uom_description'] = $_POST['uom_description'][$upc_assignment_id];
				if ($upc_assignment['unit_of_measure'] != $_POST['unit_of_measure'][$upc_assignment_id]) $data['unit_of_measure'] = $_POST['unit_of_measure'][$upc_assignment_id];
				if ($upc_assignment['target_resource'] != $_POST['target_resource'][$upc_assignment_id]) $data['target_resource'] = $_POST['target_resource'][$upc_assignment_id];
				if ($upc_assignment['target_resource_id'] != $_POST['target_resource_id'][$upc_assignment_id]) $data['target_resource_id'] = $_POST['target_resource_id'][$upc_assignment_id];
				if ($upc_assignment['provenance'] != $_POST['provenance'][$upc_assignment_id]) $data['provenance'] = $_POST['provenance'][$upc_assignment_id];
				if ($upc_assignment['purpose'] != $_POST['purpose'][$upc_assignment_id]) $data['purpose'] = $_POST['purpose'][$upc_assignment_id];

				if (strtolower($data['target_resource']) == 'ipn' && empty($data['target_resource_id'])) unset($data['target_resource']);

				if (!empty($data)) $ipn->update_upc($upc_assignment_id, $data);

				break;
			case 'remove':
				$ipn->remove_upc($upc_assignment_id);
				break;
			case 'create':
				$data = [
					'upc' => $_POST['upc']['new'],
					'provenance' => $_POST['provenance']['new'],
				];

				if (!empty($target_resource_map[strtolower($_POST['target_resource']['new'])])) $_POST['target_resource']['new'] = $target_resource_map[strtolower($_POST['target_resource']['new'])];

				if (!empty($_POST['uom_description']['new'])) $data['uom_description'] = $_POST['uom_description']['new'];
				if (!empty($_POST['unit_of_measure']['new'])) $data['unit_of_measure'] = $_POST['unit_of_measure']['new'];
				if (!empty($_POST['target_resource']['new'])) $data['target_resource'] = $_POST['target_resource']['new'];
				if (!empty($_POST['target_resource_id']['new'])) $data['target_resource_id'] = $_POST['target_resource_id']['new'];
				if (!empty($_POST['purpose']['new'])) $data['purpose'] = $_POST['purpose']['new'];

				if (strtolower($data['target_resource']) == 'ipn' && empty($data['target_resource_id'])) unset($data['target_resource']);

				$ipn->create_upc($data);

				break;
		}

		CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.urlencode($ipn->get_header('ipn')).'&selectedTab='.($ipn->is('serialized')?13:12));
		break;
	case 'set-primary-container':
		if (!empty($_POST['primary_container'])) {
			if ($_POST['primary_container'] == 'redirect') {
				$canonical = 1;
				$redirect = 1;
			}
			elseif ($_POST['primary_container'] == 'canonical') {
				$canonical = 1;
				$redirect = 0;
			}
			else {
				$canonical = 0;
				$redirect = 0;
			}
			$ipn->set_primary_merchandising_container($_POST['container_type_id'], $_POST['container_id'], $canonical, $redirect);
		}
		else $ipn->remove_primary_merchandising_container();

		CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.urlencode($ipn->get_header('ipn')).'&selectedTab=1');
		break;
	case 'set-bundle-pricing-impact':
		if (isset($_POST['pricing_flow']) && isset($_POST['signum'])) {
			$ipn->update(['bundle_price_flows_from_included_products' => $_POST['pricing_flow'], 'bundle_price_modifier' => $_POST['price_modifier'], 'bundle_price_signum' => $_POST['signum']]);
		}
		break;
	default:
		break;
}

$GLOBALS['use_jquery_1.8.3'] = TRUE;

$selectedTab = !empty($_GET['selectedTab'])?$_GET['selectedTab']:'ipn-manage';
$selectedSubTab = NULL;

if (is_numeric($selectedTab)) {
	$selectedTab = (int) $selectedTab;
	if (!empty($ipn) && !$ipn->is('serialized') && $selectedTab >= 8) $selectedTab++;
	switch ($selectedTab) {
		case 0:
			$selectedTab = 'ipn-manage';
			break;
		case 1:
			$selectedTab = 'ipn-marketing';
			break;
		case 2:
			$selectedTab = 'ipn-sales';
			break;
		case 5:
			$selectedTab = 'ipn-manage';
			break;
		case 8:
			$selectedTab = 'ipn-manage';
			break;
		case 9:
			$selectedTab = 'ipn-purchasing';
			break;
		case 13:
			$selectedTab = 'ipn-marketing';
			break;
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script src="/includes/javascript/jquery-1.4.2.min.js"></script>
	<script src="/includes/javascript/jquery-ui-1.8.custom.min.js"></script>
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="/includes/javascript/prototype.js"></script>
	<script language="javascript" src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
	<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
	<link rel="stylesheet" type="text/css" href="serials/serials.css">
	<style type="text/css">
		div#input_dialog_container { font-family:Verdana,Arial,sans-serif; font-size:12px; }
		div#popup_dialog { border-left: 1px solid #555; border-bottom: 1px solid #555; border-right: 1px solid #555; border-top: 1px solid #555; background-color: #fff; position: absolute; padding: 0px; padding-bottom: 10px; width: 500px; }
		div#input_dialog { width: 500px; }
		div#popup_content { padding: 5px; }
		#calendarmenu { position: absolute; }
		#calendarcontainer { padding:10px; }
	</style>
	<script language="javascript" src="serials/serials.js"></script>
	<script>
		function createpo(stockid) {
			var po_ipn = stockid;
			var po_ipns_add = [];
			po_ipns_add.push(po_ipn);
			window.location.href = 'po_editor.php?action=new&method=autofill&po_list='+po_ipns_add;
		}
	</script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/mustache.js/2.1.3/mustache.min.js"></script>
	<script src="/images/static/js/ck-styleset.js"></script>
	<script src="/images/static/js/ck-autocomplete.max.js?v=0.23"></script>
	<script src="/images/static/js/ck-tabs.max.js?v=7"></script>
	<script src="/images/static/js/ck-button-links.max.js"></script>
	<script src="/images/static/js/ck-ajaxify.max.js"></script>
	<script src="/images/static/js/ck-modal.max.js?v=5"></script>
	<script>
		ck.tabs.preinit();
		ck.tabs.styleset.add_style('.ck-tabs li', 'margin-right', '2px');
		ck.tabs.styleset.add_style('.ck-tabs li', 'padding', '.5em');
		ck.tabs.styleset.render();
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script language="javascript" src="includes/general.js"></script>
	<!-- header_eof //-->
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?= BOX_WIDTH; ?>" valign="top" class="noPrint">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<!------------------------------------------------------------------- -->
				<table class="noPrint">
					<tr>
						<td>
							<select id="ipn_editor_search_type">
								<option value="ipn">IPN Lookup</option>
								<option value="serial">Serial Lookup</option>
								<option value="stock">Consolidated Lookup</option>
							</select>
						</td>
						<td>
							<input type="text" id="ipn_editor_search_box" value="<?= !empty($ipn)?$ipn->get_header('ipn'):''; ?>">
							<input type="button" value="Create PO" onclick="createpo(<?= !empty($ipn)?$ipn->id():''; ?>)">
							<input type="button" value="New IPN" onClick="window.location='quick-ipn-create.php';">
							<?php if (!empty($ipn)) { ?>
							<a href="/admin/quick-ipn-create.php?copy_from_stock_id=<?= $ipn->id(); ?>" class="button-link">Copy IPN</a>
							<?php } ?>
						</td>
					</tr>
				</table>
				<br>
				<?php if ($ipn) { ?>
				<style>
					.ck-tab-content.loading { background-color:#ffc; }
				</style>
				<script src="/images/static/js/ck-j-table-manager.max.js?v=0.48"></script>
				<div class="<?= $ipn->is('serialized')?'serialized':''; ?>">
					<input type="hidden" id="stock_id" value="<?= $ipn->id(); ?>">
					<input type="hidden" id="selectedTab" value="<?= $selectedTab; ?>">
					<ul id="ipn-editor-tabs" class="noPrint">
						<li id="ipn-manage" class="tab">Manage</li>
						<li id="ipn-marketing" class="tab">Marketing</li>
						<li id="ipn-sales" class="tab">Sales</li>
						<li id="ipn-purchasing" class="tab">Purchasing</li>
					</ul>
					<div id="ipn-editor-tabs-body" style="padding: 10px;">
						<div id="ipn-manage-content" class="ck-tab-content" data-target="/admin/ipn_editor_manage_tab.php" data-loaded="0"></div>
						<div id="ipn-marketing-content" class="ck-tab-content" data-target="/admin/ipn_editor_marketing_tab.php" data-loaded="0"></div>
						<div id="ipn-sales-content" class="ck-tab-content" data-target="/admin/ipn_editor_sales_tab.php" data-loaded="0"></div>
						<div id="ipn-purchasing-content" class="ck-tab-content" data-target="/admin/ipn_editor_purchasing_tab.php" data-loaded="0"></div>
					</div>
					<script>
						function load_tab() {
							var $self = jQuery(this);

							if ($self.attr('data-loaded') > 0) return;

							var params = window.location.search.replace(/^\?/, '');

							$self.addClass('loading').html('<p style="text-align:center;">LOADING...</p>');

							$self.attr('data-loaded', 1);

							if ($self.attr('data-selectedTab')) params += '&selectedTab='+$self.attr('data-selectedTab');

							jQuery.ajax({
								url: $self.attr('data-target'),
								type: 'get',
								dataType: 'html',
								data: 'stock_id='+jQuery('#stock_id').val()+'&'+params,
								success: function(data) {
									$self.removeClass('loading');
									$self.html(data);
									$self.attr('data-loaded', 2);
								},
								error: function() {
									$self.removeClass('loading');
									$self.html('<p style="text-align:center;">ERROR</p>');
									$self.attr('data-loaded', 0);
								}
							});
						}

						function reload_tab() {
							var tab_id = jQuery(this).attr('id');
							if (jQuery('#'+tab_id+'-content').attr('data-loaded') == 2) {
								if (confirm('Do you want to reload the content of this tab from the server?')) {
									jQuery('#'+tab_id+'-content').attr('data-loaded', 0);
									jQuery('#'+tab_id+'-content').trigger('tabs:open');
								}
							}
						}

						jQuery('#ipn-editor-tabs-body .ck-tab-content').on('tabs:open', load_tab);

						jQuery('#ipn-editor-tabs .tab').on('dblclick', reload_tab);

						var ipn_editor_tabs = new ck.tabs({
							tabs_id: 'ipn-editor-tabs',
							tab_bodies_id: 'ipn-editor-tabs-body',
							default_tab_index: jQuery('#selectedTab').val(),
							content_suffix: '-content'
						});

						ck.button_links();
					</script>
				</div>
				<?php } ?>
				<!------------------------------------------------------------------- -->
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
