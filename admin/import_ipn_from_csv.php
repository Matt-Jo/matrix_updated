<?php
require('includes/application_top.php');

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

$errors = [];
$message = NULL;
$output = NULL;
$preferred_vendor_id = NULL;
$secondary_vendor_id = NULL;

$conditions_array = [
	'New' => 1,
    'Refurb' => 2,
    'FacRefurb' => 3,
    'Clearance' => 4,
    'OEM' => 5,
    'NOB' => 6,
    'NIB' => 7,
	'Broker Refurb' => 8,
];

$ipn_cats = [];
$ipn_cats_rows = prepared_query::fetch('SELECT pscc.categories_id, pscc.name FROM products_stock_control_categories pscc', cardinality::SET);

foreach ($ipn_cats_rows as $row) {
	$ipn_cats[$row['categories_id']] = $row['name'];
}

$warranties = [];
$warranty_rows = prepared_query::fetch('SELECT w.warranty_id, w.warranty_name FROM warranties w', cardinality::SET);
foreach ($warranty_rows as $row) {
	$warranties[$row['warranty_id']] = $row['warranty_name'];
}

$dealer_warranties = [];
$dw_rows = prepared_query::fetch('SELECT dw.dealer_warranty_id, dw.dealer_warranty_name FROM dealer_warranties dw', cardinality::SET);
foreach ($dw_rows as $row) {
	$dealer_warranties[$row['dealer_warranty_id']] = $row['dealer_warranty_name'];
}

function make_cat($cat) {
	$categories_id = prepared_query::fetch('SELECT categories_id FROM products_stock_control_categories WHERE name = :category', cardinality::SINGLE, [':category' => $cat]);
	if (empty($categories_id)) throw new Exception('Cannot create new IPN category: '.$cat);
	else return $categories_id;
}

function make_warranty($warranty) {
	$warranty_id = prepared_query::fetch('SELECT warranty_id FROM warranties WHERE warranty_name = :warranty', cardinality::SINGLE, [':warranty' => $warranty]);
	if (empty($warranty_id)) throw new Exception('Cannot create new warranty: '.$warranty);
	else return $warranty_id;
}

function make_dealer_warranty($dealer_warranty) {
	$dealer_warranty_id = prepared_query::fetch('SELECT dealer_warranty_id FROM dealer_warranties WHERE dealer_warranty_name = :dealer_warranty', cardinality::SINGLE, [':dealer_warranty' => $dealer_warranty]);
	if (empty($dealer_warranty_id)) throw new Exception('Cannot create new dealer warranty: '.$dealer_warranty);
	else return $dealer_warranty_id;
}


if (isset($_POST['process_upload'])) {
	$columns = [];

	$number_added = $number_failed = 0;

	try {
		$ipn_import_id = prepared_query::insert('INSERT INTO ipn_import (label, description, admin_id, imported_date) VALUES (:label, :description, :admin_id, NOW())', [':label' => addslashes(@$_POST['label']), ':description' => addslashes(@$_POST['description']), ':admin_id' => $_SESSION['login_id']]);

		ck_ipn2::start_batch($ipn_import_id);

		$stock_id_list = [];
		$stock_name_list = [];

		$output = '<table cellpadding="5px" cellspacing="5px"><thead><tr><th>IPN</th><th>Change Type</th><th>Old Value</th><th>New Value</th></thead><tbody>';

		foreach ($_POST['spreadsheet_column'] as $column_idx => $field) {
			if ($field == '0') continue;
			$columns[$field] = $column_idx;
		}

		foreach ($_POST['spreadsheet_field'] as $row_idx => $row) {
			$stock_id = @$row[$columns['stock_id']];
			$stock_name = @$row[$columns['stock_name']];
			$stock_description = @$row[$columns['stock_description']];
			$condition = @$row[$columns['condition']];
			$serialized = @$row[$columns['serialized']];
			$stock_price = @$row[$columns['stock_price']];
			$dealer_price = @$row[$columns['dealer_price']];
			$wholesale_high_price = @$row[$columns['wholesale_high_price']];
			$wholesale_low_price = @$row[$columns['wholesale_low_price']];
			//$lqc = @$row[$columns['lqc']];
			$stock_weight = @$row[$columns['stock_weight']];
			$lwc = @$row[$columns['lwc']];
			$stock_location = @$row[$columns['stock_location']];
			$stock_location2 = @$row[$columns['stock_location2']];
			$preferred_vendor = @$row[$columns['preferred_vendor']];
			$preferred_vendor_part_number = @$row[$columns['preferred_vendor_part_number']];
			$preferred_vendor_price = @$row[$columns['preferred_vendor_price']];
			$preferred_always_available = @$row[$columns['preferred_always_available']];
			$preferred_lead_time = @$row[$columns['preferred_lead_time']];
			$preferred_case_quantity = @$row[$columns['preferred_case_quantity']];
			$preferred_vendor_notes = @$row[$columns['preferred_vendor_notes']];
			$secondary_vendor = @$row[$columns['secondary_vendor']];
			$secondary_vendor_part_number = @$row[$columns['secondary_vendor_part_number']];
			$secondary_vendor_price = @$row[$columns['secondary_vendor_price']];
			$secondary_always_available = @$row[$columns['secondary_always_available']];
			$secondary_lead_time = @$row[$columns['secondary_lead_time']];
			$secondary_case_quantity = @$row[$columns['secondary_case_quantity']];
			$secondary_vendor_notes = @$row[$columns['secondary_vendor_notes']];
			$ipn_cat = @$row[$columns['ipn_cat']];
			$warranty = @$row[$columns['warranty']];
			$dealer_warranty = @$row[$columns['dealer_warranty']];
			$min_inventory_level = @$row[$columns['min_inventory_level']];
			$target_inventory_level = @$row[$columns['target_inventory_level']];
			$max_inventory_level = @$row[$columns['max_inventory_level']];
			$max_displayed_quantity = @$row[$columns['max_displayed_quantity']];
			$discontinued = @$row[$columns['discontinued']];
			$drop_ship = @$row[$columns['drop_ship']];
			$non_stock = @$row[$columns['non_stock']];
			$freight = @$row[$columns['freight']];
			$is_bundle = @$row[$columns['is_bundle']];
			$bundle_price_flows_from_included_products = @$row[$columns['bundle_price_flows_from_included_products']];
			$bundle_price_modifier = @$row[$columns['bundle_price_modifier']];
			$bundle_price_signum = @$row[$columns['bundle_price_signum']];
			$image_reference = @$row[$columns['image_reference']];


			if (in_array($stock_id, $stock_id_list)) {
				$errors[] = 'The IPN id '.$stock_id.' was in the spreadsheet more than once.';
				continue;
			}
			elseif (!empty($stock_id)) $stock_id_list[] = $stock_id;

			$test_stock_name = !is_numeric($stock_name)?$stock_name:'str_'.$stock_name;

			if (in_array((string)$test_stock_name, $stock_name_list)) {
				$errors[] = 'The IPN '.$stock_name.' was in the spreadsheet more than once.';
				continue;
			}
			else $stock_name_list[] = $test_stock_name;

			//select vendor_id for preferred vendor
			if (!empty($preferred_vendor) && trim($preferred_vendor) != '') {
				$preferred_vendor_id = prepared_query::fetch('SELECT vendors_id FROM vendors WHERE vendors_company_name LIKE :preferred_vendor OR vendors_email_address LIKE :preferred_vendor OR vendors_id = :preferred_vendor', cardinality::SINGLE, [':preferred_vendor' => $preferred_vendor]);
				if (empty($preferred_vendor_id)) {
					$errors[] = 'The following vendor could not be found: '.$preferred_vendor.'. Please make sure it exists and try again.';
					continue;
				}
			}

			//select vendor_id for secondary_vendor
			if (!empty($secondary_vendor) && trim($secondary_vendor) != '') {
				$secondary_vendor_id = prepared_query::fetch('SELECT vendors_id FROM vendors WHERE vendors_company_name LIKE :secondary_vendor OR vendors_email_address LIKE :secondary_vendor OR vendors_id = :secondary_vendor', cardinality::SINGLE, [':secondary_vendor' => $secondary_vendor]);
				if (empty($secondary_vendor_id)) {
					$errors[] = 'The following vendor could not be found: '.$secondary_vendor.'. Please make sure it exists and try again.';
					continue;
				}
			}

			// create or retrieve IPN category ID
			if (!empty($ipn_cat)) $categories_id = make_cat($ipn_cat);
			else $categories_id = 0;

			// create or retrieve Warranty IDs
			if (!empty($warranty)) $warranty_id = make_warranty($warranty);
			else $warranty_id = NULL;
			if (!empty($dealer_warranty)) $dealer_warranty_id = make_dealer_warranty($dealer_warranty);
			else $dealer_warranty_id = NULL;

			$action = $_POST['inorup'];

			if ($action == 'update' || !empty($stock_id)) $check_result = prepared_query::fetch('SELECT COUNT(*) as quantity FROM products_stock_control WHERE stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $stock_id]);
			elseif ($action == 'insert') $check_result = prepared_query::fetch('SELECT COUNT(*) as quantity FROM products_stock_control WHERE stock_name LIKE :stock_name', cardinality::SINGLE, [':stock_name' => $stock_name]);
			else $check_result = 0;

			//if the IPN does *not* exist process the following code
			if (empty($check_result)) {
				if ($action == 'update') {
					if (trim($stock_id) == '' || $stock_id == '0') $errors[] = 'No stock_id provided for IPN name '.$stock_name.' and therefore it cannot be updated. If you intended to insert a new record, please choose update and insert';
					else $errors[] = $stock_name.' does not exist but the operation type selected was \'update\'.';
					continue;
				}
				else {
					$ipn = [
						'header' => [
							'stock_name' => $stock_name,
							'stock_description' => $stock_description,
							'stock_price' => CK\text::demonetize($stock_price),
							'dealer_price' => CK\text::demonetize($dealer_price),
							'wholesale_high_price' => CK\text::demonetize($wholesale_high_price),
							'wholesale_low_price' => CK\text::demonetize($wholesale_low_price),
							'conditions' => $conditions_array[$condition],
							'serialized' => CK\fn::check_flag($serialized)?1:0,
							'stock_weight' => $stock_weight,
							'lead_time' => $preferred_lead_time,
							'always_available' => CK\fn::check_flag($preferred_always_available)?1:0,
							'max_displayed_quantity' => $max_displayed_quantity,
							'min_inventory_level' => $min_inventory_level,
							'target_inventory_level' => $target_inventory_level,
							'max_inventory_level' => $max_inventory_level,
							'drop_ship' => CK\fn::check_flag($drop_ship)?1:0,
							'non_stock' => CK\fn::check_flag($non_stock)?1:0,
							'freight' => CK\fn::check_flag($freight)?1:0,
							'products_stock_control_category_id' => $categories_id,
							'warranty_id' => $warranty_id,
							'dealer_warranty_id' => $dealer_warranty_id,
							'is_bundle' => CK\fn::check_flag($is_bundle)?1:0,
							'bundle_price_flows_from_included_products' => $bundle_price_flows_from_included_products,
							'bundle_price_modifier' => $bundle_price_modifier,
							'bundle_price_signum' => $bundle_price_signum,
							'image_reference' => $image_reference,
						],
						'extra' => [
							'stock_location' => !empty($stock_location)?$stock_location:'',
							'stock_location_2' => !empty($stock_location2)?$stock_location2:'',
							'preferred_vendor_id' => !empty($preferred_vendor_id)?$preferred_vendor_id:0,
							'preferred_vendor_part_number' => !empty($preferred_vendor_part_number)?$preferred_vendor_part_number:''
						],
						'vendor' => [
							'vendors_id' => $preferred_vendor_id,
							'vendors_price' => CK\text::demonetize($preferred_vendor_price),
							'vendors_pn' => $preferred_vendor_part_number,
							'always_avail' => CK\fn::check_flag($preferred_always_available)?1:0,
							'lead_time' => $preferred_lead_time,
							'case_qty' => $preferred_case_quantity,
							'notes' => $preferred_vendor_notes
						]
					];

					if (in_array($_SESSION['login_id'], [144, 163])) $ipn['header']['creation_reviewed'] = 1; // rebecca or gazaway

					//if (CK\fn::check_flag($lqc)) $ipn['header']['last_quantity_change'] = new Zend_Db_Expr('NOW()');
					if (CK\fn::check_flag($lwc)) $ipn['header']['last_weight_change'] = new Zend_Db_Expr('NOW()');

					if (!empty($secondary_vendor_id)) {
						$secondary_vendor_data = [
							'vendors_id' => $secondary_vendor_id,
							'vendors_price' => CK\text::demonetize($secondary_vendor_price),
							'vendors_pn' => $secondary_vendor_part_number,
							'always_avail' => CK\fn::check_flag($secondary_always_available),
							'lead_time' => $secondary_lead_time,
							'case_qty' => $secondary_case_quantity,
							'notes' => $secondary_vendor_notes,
							'secondary' => 1
						];
					}

					try {
						$ipn = ck_ipn2::create($ipn);
						//set output value for New IPN
						$output .= '<tr><td>'.$stock_name.'</td><td>New IPN</td><td>-</td><td>-</td></tr>';
						$output .= '<tr><td>'.$stock_name.'</td><td>New Preferred Vendor</td><td>-</td><td>'.$preferred_vendor.'</td></tr>';

						if (!empty($secondary_vendor_data)) {
							$ipn->create_vendor_relationship($secondary_vendor_data);
							$output .= '<tr><td>'.$stock_name.'</td><td>New Secondary Vendor</td><td>-</td><td>'.$secondary_vendor.'</td></tr>';
						}
					}
					catch (Exception $e) {
						$errors[] = 'Exception caught inserting stock name '.$stock_name.':'.$e->getMessage();
					}
				}
			}
			//if the IPN *does* exist process the following code
			else {
				if ($action == 'insert') {
					$errors[] = $stock_name.' exists but the operation type selected was \'insert\'.';
					continue;
				}
				else {
					//pull IPN information
					$ipn = new ck_ipn2($stock_id);

					$header = $ipn->get_header();
					$header_update = [];
					$extra_update = [];

					if (!empty(CK\text::demonetize($stock_price)) && CK\text::demonetize($stock_price) != $header['stock_price']) {
						$header_update['stock_price'] = CK\text::demonetize($stock_price);
						$output .= '<tr><td>'.$stock_name.'</td><td>Stock Price Change</td><td>'.$header['stock_price'].'</td><td>'.$stock_price.'</td></tr>';
					}
					if (!empty(CK\text::demonetize($dealer_price)) && CK\text::demonetize($dealer_price) != $header['dealer_price']) {
						$header_update['dealer_price'] = CK\text::demonetize($dealer_price);
						$output .= '<tr><td>'.$stock_name.'</td><td>Dealer Price Change</td><td>'.$header['dealer_price'].'</td><td>'.$dealer_price.'</td></tr>';
					}
					if (!empty(CK\text::demonetize($wholesale_high_price)) && CK\text::demonetize($wholesale_high_price) != $header['wholesale_high_price']) {
						$header_update['wholesale_high_price'] = CK\text::demonetize($wholesale_high_price);
						$output .= '<tr><td>'.$stock_name.'</td><td>Wholesale High Price Change</td><td>'.$header['wholesale_high_price'].'</td><td>'.$wholesale_high_price.'</td></tr>';
					}
					if (!empty(CK\text::demonetize($wholesale_low_price)) && CK\text::demonetize($wholesale_low_price) != $header['wholesale_low_price']) {
						$header_update['wholesale_low_price'] = CK\text::demonetize($wholesale_low_price);
						$output .= '<tr><td>'.$stock_name.'</td><td>Wholesale Low Price Change</td><td>'.$header['wholesale_low_price'].'</td><td>'.$wholesale_low_price.'</td></tr>';
					}

					if (!empty($stock_description) && $stock_description != $header['stock_description']) {
						$header_update['stock_description'] = $stock_description;
						$output .= '<tr><td>'.$stock_name.'</td><td>Description Change</td><td>'.$header['stock_description'].'</td><td>'.$stock_description.'</td></tr>';
					}
					if (!empty($conditions_array[$condition]) && $conditions_array[$condition] != $header['conditions']) {
						$header_update['conditions'] = $conditions_array[$condition];
						$output .= '<tr><td>'.$stock_name.'</td><td>Condition Change</td><td>'.$conditions_array[$header['conditions']].'</td><td>'.$conditions_array[$condition].'</td></tr>';
					}
					if (!empty($columns['max_displayed_quantity']) && $max_displayed_quantity != $header['max_displayed_quantity']) {
						$header_update['max_displayed_quantity'] = $max_displayed_quantity;
						$output .= '<tr><td>'.$stock_name.'</td><td>Max Displayed Quantity Change</td><td>'.$header['max_displayed_quantity'].'</td><td>'.$max_displayed_quantity.'</td></tr>';
					}
					if (!empty($min_inventory_level) && $min_inventory_level != $header['min_inventory_level']) {
						$header_update['min_inventory_level'] = $min_inventory_level;
						$output .= '<tr><td>'.$stock_name.'</td><td>Min Inventory Level Change</td><td>'.$header['min_inventory_level'].'</td><td>'.$min_inventory_level.'</td></tr>';
					}
					if (!empty($target_inventory_level) && $target_inventory_level != $header['target_inventory_level']) {
						$header_update['target_inventory_level'] = $target_inventory_level;
						$output .= '<tr><td>'.$stock_name.'</td><td>Target Inventory Level Change</td><td>'.$header['target_inventory_level'].'</td><td>'.$target_inventory_level.'</td></tr>';
					}
					if (!empty($max_inventory_level) && $max_inventory_level != $header['max_inventory_level']) {
						$header_update['max_inventory_level'] = $max_inventory_level;
						$output .= '<tr><td>'.$stock_name.'</td><td>Max Inventory Level Change</td><td>'.$header['max_inventory_level'].'</td><td>'.$max_inventory_level.'</td></tr>';
					}
					if (!empty($stock_weight) && $stock_weight != $header['stock_weight']) {
						$header_update['stock_weight'] = $stock_weight;
						$output .= '<tr><td>'.$stock_name.'</td><td>Stock Weight Change</td><td>'.$header['stock_weight'].'</td><td>'.$stock_weight.'</td></tr>';
					}
					if (!empty($categories_id) && $categories_id != $header['products_stock_control_category_id']) {
						$header_update['products_stock_control_category_id'] = $categories_id;
						$output .= '<tr><td>'.$stock_name.'</td><td>IPN Category Change</td><td>'.$ipn_cats[$header['products_stock_control_category_id']].'</td><td>'.$ipn_cats[$categories_id].'</td></tr>';
					}
					if (!empty($warranty_id) && $warranty_id != $header['warranty_id']) {
						$header_update['warranty_id'] = $warranty_id;
						$output .= '<tr><td>'.$stock_name.'</td><td>Warranty Change</td><td>'.$warranties[$header['warranty_id']].'</td><td>'.$warranties[$warranty_id].'</td></tr>';
					}
					if (!empty($dealer_warranty_id) && $dealer_warranty_id != $header['dealer_warranty_id']) {
						$header_update['dealer_warranty_id'] = $dealer_warranty_id;
						$output .= '<tr><td>'.$stock_name.'</td><td>Dealer Warranty Change</td><td>'.$dealer_warranties[$header['dealer_warranty_id']].'</td><td>'.$dealer_warranties[$dealer_warranty_id].'</td></tr>';
					}

					if (!empty($columns['discontinued']) && CK\fn::check_flag($discontinued) != $ipn->is('discontinued')) {
						$header_update['discontinued'] = CK\fn::check_flag($discontinued)?1:0;
						$output .= "<tr><td>$stock_name</td><td>Discontinued Change</td><td>".$header['discontinued']."</td><td>$discontinued</td></tr>";
					}
					if (!empty($columns['drop_ship']) && CK\fn::check_flag($drop_ship) != $ipn->is('drop_ship')) {
						$header_update['drop_ship'] = CK\fn::check_flag($drop_ship)?1:0;
						$output .= '<tr><td>'.$stock_name.'</td><td>Drop Ship Change</td><td>'.$header['drop_ship'].'</td><td>'.$drop_ship.'</td></tr>';
					}
					if (!empty($columns['non_stock']) && CK\fn::check_flag($non_stock) != $ipn->is('non_stock')) {
						$header_update['non_stock'] = CK\fn::check_flag($non_stock)?1:0;
						$output .= '<tr><td>'.$stock_name.'</td><td>Non Stock Change</td><td>'.$header['non_stock'].'</td><td>'.$non_stock.'</td></tr>';
					}
					if (!empty($columns['freight']) && CK\fn::check_flag($freight) != $ipn->is('freight')) {
						$header_update['freight'] = CK\fn::check_flag($freight)?1:0;
						$output .= '<tr><td>'.$stock_name.'</td><td>Freight Change</td><td>'.$header['freight'].'</td><td>'.$freight.'</td></tr>';
					}
					if (CK\fn::check_flag($lwc)) {
						$header_update['last_weight_change'] = new Zend_Db_Expr('NOW()');
						$output .= '<tr><td>'.$stock_name.'</td><td>Weight Confirmation</td><td>'.$stock_weight.'</td><td>'.$stock_weight.'</td></tr>';
					}
					if (isset($columns['is_bundle']) && CK\fn::check_flag($is_bundle) != $ipn->is('is_bundle')) {
						$header_update['is_bundle'] = CK\fn::check_flag($is_bundle)?1:0;
						$output .= '<tr><td>'.$stock_name.'</td><td>Is Bundle Change</td><td>'.$header['is_bundle'].'</td><td>'.$is_bundle.'</td></tr>';
					}
					// the following is for bundle only
					if ($ipn->is('is_bundle')) {
						if (isset($columns['bundle_price_flows_from_included_products']) && $bundle_price_flows_from_included_products != $ipn->get_header('bundle_price_flows_from_included_products')) {
							if (in_array($bundle_price_flows_from_included_products, [0, 1, 2])) { // want to make sure that the value is a valid entry
								$header_update['bundle_price_flows_from_included_products'] = $bundle_price_flows_from_included_products;
								$output .= '<tr><td>' . $stock_name . '</td><td>Bundle Price Flows From Included Products</td><td>' . $header['bundle_price_flows_from_included_products'] . '</td><td>' . $bundle_price_flows_from_included_products . '</td></tr>';
							}
							else $errors[] = $bundle_price_flows_from_included_products . ' is not a valid value for bundle_price_flows_from_included_products';
						}
						if (isset($columns['bundle_price_modifier']) && $bundle_price_modifier != $ipn->get_header('bundle_price_modifier')) {
							if (is_numeric($bundle_price_modifier)) {
								$header_update['bundle_price_modifier'] = $bundle_price_modifier;
								$output .= '<tr><td>' . $stock_name . '</td><td>Bundle Price Modifier</td><td>' . $header['bundle_price_modifier'] . '</td><td>' . $bundle_price_modifier . '</td></tr>';
							}
							else $errors[] = $bundle_price_modifier . ' is not a valid value for bundle_price_modifier';
						}
						if (isset($columns['bundle_price_signum']) && $bundle_price_signum != $ipn->get_header('bundle_price_signum')) {
							if (in_array($bundle_price_signum, [0, 1])) {
								$header_update['bundle_price_signum'] = $bundle_price_signum;
								$output .= '<tr><td>' . $stock_name . '</td><td>Bundle Price Signum</td><td>' . $header['bundle_price_signum'] . '</td><td>' . $bundle_price_signum . '</td></tr>';
							}
							else $errors[] = $bundle_price_signum . ' is not a valid value for bundle_price_signum';
						}
					}
					if (isset($columns['image_reference'])) {
						$passed = FALSE;
						if (is_numeric($image_reference) && ck_ipn2::is_valid_stock_id($image_reference)) {
							if ($image_reference != $ipn->get_header('image_reference')) {
								$header_update['image_reference'] = $image_reference;
								$passed = TRUE;
							}
						}
						elseif (!is_numeric($image_reference) && ck_ipn2::is_valid_stock_name($image_reference)) {
							$stock_id = ck_ipn2::get_id_by_stock_name($image_reference);
							if ($stock_id != $ipn->get_header('image_reference')) {
								$header_update['image_reference'] = $stock_id;
								$passed = TRUE;
							}
						}
						else $errors[] = $image_reference.' is not a valid stock id and cannot be assigned as an image reference';
						if ($passed) $output .= '<tr><td>' . $stock_name . '</td><td>Image Reference</td><td>' . $header['image_reference'] . '</td><td>' . $iamge_reference . '</td></tr>';
					}

					if (!empty($stock_location) && $stock_location != $header['bin1']) {
						$extra_update['stock_location'] = $stock_location;
						$output .= '<tr><td>'.$stock_name.'</td><td>Stock Location Change</td><td>'.$header['bin1'].'</td><td>'.$stock_location.'</td></tr>';
					}
					if (!empty($stock_location2) && $stock_location2 != $header['bin2']) {
						$extra_update['stock_location_2'] = $stock_location2;
						$output .= '<tr><td>'.$stock_name.'</td><td>Stock Location 2 Change</td><td>'.$header['bin2'].'</td><td>'.$stock_location2.'</td></tr>';
					}

					try {
						//var_dump($header_update, $extra_update);
						$ipn->update($header_update, $extra_update);

						if (!empty($preferred_vendor_id)) {
							$vtsi_id = prepared_query::fetch('SELECT id FROM vendors_to_stock_item WHERE stock_id = :stock_id AND vendors_id = :vendors_id', cardinality::SINGLE, [':stock_id' => $stock_id, ':vendors_id' => $preferred_vendor_id]);
							$preferred_vendor_data = [
								'always_avail' => CK\fn::check_flag($preferred_always_available)?1:0,
								'preferred' => 1,
								'secondary' => 0
							];

							if (isset($columns['preferred_vendor_price']) && isset($row[$columns['preferred_vendor_price']])) $preferred_vendor_data['vendors_price'] = CK\text::demonetize($preferred_vendor_price);
							if (isset($columns['preferred_vendor_part_number']) && isset($row[$columns['preferred_vendor_part_number']])) $preferred_vendor_data['vendors_pn'] = $preferred_vendor_part_number;
							if (isset($columns['preferred_lead_time']) && isset($row[$columns['preferred_lead_time']])) $preferred_vendor_data['lead_time'] = $preferred_lead_time;
							if (isset($columns['preferred_case_quantity']) && isset($row[$columns['preferred_case_quantity']])) $preferred_vendor_data['case_qty'] = $preferred_case_quantity;
							if (isset($columns['preferred_vendor_notes']) && isset($row[$columns['preferred_vendor_notes']])) $preferred_vendor_data['notes'] = $preferred_vendor_notes;

							if (!empty($vtsi_id)) {
								$old_vendor = $ipn->get_vendors('preferred')['company_name'];
								$ipn->update_vendor_relationship($vtsi_id, $preferred_vendor_data);
							}
							else {
								$old_vendor = '-';
								$preferred_vendor_data['vendors_id'] = $preferred_vendor_id;
								$ipn->create_vendor_relationship($preferred_vendor_data);
							}

							$output .= '<tr><td>'.$stock_name.'</td><td>New Preferred Vendor</td><td>'.$old_vendor.'</td><td>'.$preferred_vendor.'</td></tr>';
						}

						if (!empty($secondary_vendor_id)) {
							$vtsi_id = prepared_query::fetch('SELECT id FROM vendors_to_stock_item WHERE stock_id = :stock_id AND vendors_id = :vendors_id', cardinality::SINGLE, [':stock_id' => $stock_id, ':vendors_id' => $secondary_vendor_id]);
							$secondary_vendor_data = [
								'always_avail' => CK\fn::check_flag($secondary_always_available)?1:0,
								'preferred' => 0,
								'secondary' => 1
							];

							if (isset($row[$columns['secondary_vendor_price']])) $secondary_vendor_data['vendors_price'] = CK\text::demonetize($secondary_vendor_price);
							if (isset($row[$columns['secondary_vendor_part_number']])) $secondary_vendor_data['vendors_pn'] = $secondary_vendor_part_number;
							if (isset($row[$columns['secondary_lead_time']])) $secondary_vendor_data['lead_time'] = $secondary_lead_time;
							if (isset($row[$columns['secondary_case_quantity']])) $secondary_vendor_data['case_qty'] = $secondary_case_quantity;
							if (isset($row[$columns['secondary_vendor_notes']])) $secondary_vendor_data['notes'] = $secondary_vendor_notes;

							$old_vendor = '';
							if (!empty($vtsi_id)) {
								if (!empty($ipn->get_vendors('secondary'))) {
									foreach ($ipn->get_vendors('secondary') as $secondary) {
										if ($secondary['vendors_id'] == $secondary_vendor_id) $old_vendor = $secondary['company_name'];
									}
								}
								$ipn->update_vendor_relationship($vtsi_id, $secondary_vendor_data);
							}
							else {
								$old_vendor = '-';
								$secondary_vendor_data['vendors_id'] = $secondary_vendor_id;
								$ipn->create_vendor_relationship($secondary_vendor_data);
							}

							$output .= '<tr><td>'.$stock_name.'</td><td>New Secondary Vendor</td><td>'.$old_vendor.'</td><td>'.$secondary_vendor.'</td></tr>';
						}
					}
					catch (Exception $e) {
						$errors[] = 'Exception caught updating stock name '.$stock_name.':'.$e->getMessage();
					}
				}
			}
		}

		prepared_query::execute('UPDATE products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id SET p.products_price = psc.stock_price, p.products_dealer_price = psc.dealer_price, p.products_weight = psc.stock_weight WHERE psc.stock_id = :stock_id', [':stock_id' => $stock_id]);

		if (count($errors) == 0) {
			ck_ipn2::end_batch();
			$message .= 'The import was processed successfully!<br/>';
			$output .= "</tbody></table>";
		}
		else {
			ck_ipn2::end_batch(TRUE);
			$message .= 'There were errors in the import. No changes were committed. Please see the report below for errors.<br>';
		}
	}
	catch (Exception $e) {
		ck_ipn2::end_batch(TRUE);
		$message .= 'There were errors in the import.  No changes were committed.  Please see the report below for errors.<br>';
	}
} ?>


<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<table border="0" width="800" cellspacing="0" cellpadding="2">
					<tr>
						<td>
							<p><b>Upload IPN XLSX file.</b><br>
							<!--<a href="import_ipn_from_csv.php?selected_box=inventory&product_spreadsheet=products" target="_BLANK">Spreadsheet With All Products</a></p>-->
							<form action="/admin/import_ipn_from_csv.php" method="POST" enctype="multipart/form-data">
								<input type="submit" value="Upload IPN list"> : <input type="file" name="ipn_upload" id="ipn_upload" accept=".xlsx">
							</form>
						</td>
					</tr>
					<tr>
						<?php
						if (!empty($message)) echo $message;
						if (count($errors) == 0) echo $output; ?>
						<td>
							<?php if (count($errors)>0) { echo "<pre>"; print_r($errors); echo "</pre>"; } ?>
						</td>
					</tr>
				</table>
				<?php
				$upload_error = NULL;
				if (!empty($_FILES['ipn_upload'])) { ?>
					<script src="/images/static/js/ck-j-spreadsheet-upload.max.js?v=0.12"></script>
					<style>
						#ipn_upload_results { border-collapse:collapse; font-size:12px; }
						.spreadsheet-upload-errors { color:#c00; font-weight:bold; }
						.error .spreadsheet-field { border-color:#f00; }
						.spreadsheet-field.error { background-color:#fcc; }
						.spreadsheet-field { padding:2px; }
						.spreadsheet-column-title, .skip-values { width:100px; }
						#upload_form div, textarea { display:block;}
					</style>
					<form id="upload_form" action="/admin/import_ipn_from_csv.php" method="POST" enctype="multipart/form-data">
						<input type="hidden" name="process_upload">
						<label for="inorup">Type:</label>
						<select name="inorup">
							<option value="update" selected>Update Only</option>
							<option value="both">Insert And Update</option>
							<option value="insert">Insert Only</option>
						</select>
						<div>
						<label for="label">Label:</label>
						<input type="text" name="label" id="label">
						</div>
						<label for="description">Description:</label>
						<textarea rows="4" cols="50" name="description" id="description"></textarea>
						<input type="submit" value="Submit">
						<table id="ipn_upload_results" border="0" cellpadding="0" cellspacing="0">
							<?php $upload_status_map = [
								UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
								UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
								UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
								UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
								UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
								UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
								UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
							];
							if ($_FILES['ipn_upload']['error'] !== UPLOAD_ERR_OK) { ?>
								<tr><td>There was a problem with receiving your product upload file: <?= $upload_status_map[$_FILES['ipn_upload']['error']]; ?></td></tr>
							<?php }
							else {
								$spreadsheet = new spreadsheet_import($_FILES['ipn_upload']); ?>
							<tbody>
								<?php $columns = 0;
								foreach ($spreadsheet as $idx => $row) {
									if (empty($columns)) $columns = count($row); ?>
								<tr>
									<?php for ($i=0; $i<$columns; $i++) { ?>
									<td><?= @$row[$i]; ?></td>
									<?php } ?>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</form>
					<script>
						jQuery('#ipn_upload_results').spreadsheet_upload({
							headers: [
								{ value:'stock_id', label:'Stock Id', required:true },
								{ value:'stock_name', label:'Stock Name', required:true },
								{ value:'stock_description', label:'Stock Description'},
								{ value:'condition', label:'Condition'},
								{ value:'serialized', label:'Serialized'},
								{ value:'stock_price', label:'Stock Price'},
								{ value:'dealer_price', label:'Dealer Price'},
								{ value:'wholesale_high_price', label:'Wholesale High Price'},
								{ value:'wholesale_low_price', label:'Wholesale Low Price'},
								//{ value:'lqc', label:'lqc'},
								{ value:'stock_weight', label:'Stock Weight'},
								{ value:'lwc', label:'lwc'},
								{ value:'stock_location', label:'Stock Location'},
								{ value:'stock_location2', label:'Stock Location 2'},
								{ value:'preferred_vendor', label:'Preferred Vendor'},
								{ value:'preferred_vendor_part_number', label:'Preferred Vendor PN'},
								{ value:'preferred_vendor_price', label: 'Preferred Vendor Price'},
								{ value:'preferred_always_available', label:'Preferred Always Available'},
								{ value:'preferred_lead_time', label:'Preferred Lead Time'},
								{ value: 'preferred_case_quantity', label:'Preferred Case Quantity'},
								{ value:'preferred_vendor_notes', label:'Preferred Vendor Notes'},
								{ value:'secondary_vendor', label:'Secondary Vendor'},
								{ value:'secondary_vendor_part_number', label:'Secondary Vendor PN'},
								{ value:'secondary_vendor_price', label:'Secondary Vendor Price'},
								{ value:'secondary_always_available', label:'Secondary Always Available'},
								{ value:'secondary_lead_time', label:'Secondary Lead Time'},
								{ value:'secondary_case_quantity', label:'Secondary Case Quantity'},
								{ value:'secondary_vendor_notes', label:'Secondary Vendor Notes'},
								{ value:'ipn_cat', label:'IPN Category'},
								{ value:'warranty', label:'Warranty'},
								{ value:'dealer_warranty', label:'Dealer Warranty'},
								{ value:'min_inventory_level', label:'Min Inventory Level'},
								{ value:'target_inventory_level', label:'Target Inventory Level'},
								{ value:'max_inventory_level', label:'Max Inventory Level'},
								{ value:'max_displayed_quantity', label:'Max Displayed Quantity'},
								{ value:'discontinued', label:'Discontinued'},
								{ value:'drop_ship', label:'Drop Ship'},
								{ value:'non_stock', label:'Non Stock'},
								{ value:'freight', label:'Freight'},
								{ value:'is_bundle', label:'Is Bundle'},
								{ value:'bundle_price_flows_from_included_products', label:'Bundle Price Flows From Included Products?'},
								{ value:'bundle_price_modifier', label:'Bundle Price Modifer'},
								{ value:'bundle_price_signum', label:'Bundle Price Signum'},
								{ value:'image_reference', label: 'Image Reference'}
							],
							validators: {
								/*stck_qty: function(col_idx, record_error, clear_error) {
									msg_recorded = false;
									jQuery(this).find('.col-'+col_idx).each(function(idx) {
										if (!jQuery(this).is(':disabled') && jQuery(this).val().replace(/[0-9]/g, '') != '') {
											if (!msg_recorded) record_error(jQuery(this), col_idx, 'There are invalid quantity values.');
											else record_error(jQuery(this), col_idx);
											msg_recorded = true;
										}
										else {
											clear_error(jQuery(this), col_idx);
										}
									});
								}*/
							}
						});
					</script>
				<?php } } ?>
			</td>
		</tr>
	</table>
</body>
</html>
