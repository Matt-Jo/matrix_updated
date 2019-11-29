<?php
require('includes/application_top.php');
set_time_limit(0);
@ini_set("memory_limit","512M");
//download spreadsheet of all IPNs
if (!empty($_POST['export'])) {

	$ipn_cats = [];
	$ipn_cats_rows = prepared_query::fetch('SELECT pscc.categories_id, pscc.name FROM products_stock_control_categories pscc WHERE 1', cardinality::SET);
	foreach ($ipn_cats_rows as $unused => $row) {
		$ipn_cats[$row['categories_id']] = $row['name'];
	}
	unset($ipn_cats_rows);

	$warranties = [];
	$warranty_rows = prepared_query::fetch('SELECT w.warranty_id, w.warranty_name FROM warranties w WHERE 1', cardinality::SET);
	foreach ($warranty_rows as $unused => $row) {
		$warranties[$row['warranty_id']] = $row['warranty_name'];
	}
	unset($warranty_rows);

	$dealer_warranties = [];
	$dw_rows = prepared_query::fetch('SELECT dw.dealer_warranty_id, dw.dealer_warranty_name FROM dealer_warranties dw WHERE 1', cardinality::SET);
	foreach ($dw_rows as $unused => $row) {
		$dealer_warranties[$row['dealer_warranty_id']] = $row['dealer_warranty_name'];
	}
	unset($dw_rows);

	$customize = ' ';

	//list all of the possible cell values
	$cells = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB'];

	//manage vendor selection
	if (!empty($_POST['vendor_selection']) && $_POST['vendor_selection'] != 'all') {
		$customize = ' AND v.vendors_id = '.$_POST['vendor_selection'].' ';
	}

	if (!empty($_POST['serialized_selection']) && $_POST['serialized_selection'] != 'all') {
		$customize .= ' AND psc.serialized = '.$_POST['serialized_selection'].' ';
	}

	if (!empty($_POST['category_selection']) && $_POST['category_selection'] != 'all') {
		$customize .= ' AND psc.products_stock_control_category_id = '.$_POST['category_selection'].' ';
	}

	$workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	$worksheet = $workbook->getSheet(0);
	$worksheet->setTitle('IPNs');

	$worksheet->getCell($cells[0].'1')->setValue('Stock Id');
	$worksheet->getCell($cells[1].'1')->setValue('Stock Name');

	$cell_array_index = 2;

	if (!empty($_POST['stock_description']) && $_POST['stock_description'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Stock Description');
		$cell_array_index ++;
	}

	if (!empty($_POST['ipn_weight']) && $_POST['ipn_weight'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Stock Weight');
		$cell_array_index ++;
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Last Weight Change');
		$cell_array_index ++;
	}

	if (!empty($_POST['stock_location']) && $_POST['stock_location'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Stock Location');
		$cell_array_index ++;
	}

	if (!empty($_POST['stock_location_2']) && $_POST['stock_location_2'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Stock Location 2');
		$cell_array_index ++;
	}

	if (!empty($_POST['condition']) && $_POST['condition'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Condition');
		$cell_array_index ++;
	}

	if (!empty($_POST['serialized']) && $_POST['serialized'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Serialized');
		$cell_array_index ++;
	}

	if (!empty($_POST['stock_price']) && $_POST['stock_price'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Stock Price');
		$cell_array_index ++;
	}

	if (!empty($_POST['dealer_price']) && $_POST['dealer_price'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Dealer Price');
		$cell_array_index ++;
	}

	if (!empty($_POST['wholesale_high_price']) && $_POST['wholesale_high_price'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Wholesale High Price');
		$cell_array_index ++;
	}

	if (!empty($_POST['wholesale_low_price']) && $_POST['wholesale_low_price'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Wholesale Low Price');
		$cell_array_index ++;
	}

	if (!empty($_POST['last_quantity_change']) && $_POST['last_quantity_change'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Last Quantity Change');
		$cell_array_index ++;
	}

	if (!empty($_POST['preferred_vendor']) && $_POST['preferred_vendor'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Preferred Vendor');
		$cell_array_index ++;
	}

	if (!empty($_POST['preferred_vendor_part_number']) && $_POST['preferred_vendor_part_number'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Preferred Vendor Part Number');
		$cell_array_index ++;
	}

	if (!empty($_POST['preferred_always_available']) && $_POST['preferred_always_available'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Preferred Always Available');
		$cell_array_index ++;
	}

	if (!empty($_POST['preferred_vendor_price']) && $_POST['preferred_vendor_price'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Preferred Vendor Price');
		$cell_array_index ++;
	}

	if (!empty($_POST['preferred_vendor_lead_time']) && $_POST['preferred_vendor_lead_time'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Preferred Vendor Lead Time');
		$cell_array_index ++;
	}

	if (!empty($_POST['preferred_vendor_notes']) && $_POST['preferred_vendor_notes'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Preferred Vendor Notes');
		$cell_array_index ++;
	}

	if (!empty($_POST['ipn_category']) && $_POST['ipn_category'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('IPN Category');
		$cell_array_index ++;
	}

	if (!empty($_POST['ipn_warranty']) && $_POST['ipn_warranty'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('IPN Warranty');
		$cell_array_index ++;
	}

	if (!empty($_POST['dealer_warranty']) && $_POST['dealer_warranty'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Dealer Warranty');
		$cell_array_index ++;
	}

	if (!empty($_POST['min_inventory_level']) && $_POST['min_inventory_level'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Min Inventory Level');
		$cell_array_index ++;
	}

	if (!empty($_POST['target_inventory_level']) && $_POST['target_inventory_level'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Target Inventory Level');
		$cell_array_index ++;
	}

	if (!empty($_POST['max_inventory_level']) && $_POST['max_inventory_level'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Max Inventory Level');
		$cell_array_index ++;
	}

	if (!empty($_POST['max_displayed_quantity']) && $_POST['max_displayed_quantity'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Max Displayed Quantity');
		$cell_array_index ++;
	}

	if (!empty($_POST['discontinued']) && $_POST['discontinued'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Discontinued');
		$cell_array_index ++;
	}

	if (!empty($_POST['drop_ship']) && $_POST['drop_ship'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Drop Ship');
		$cell_array_index ++;
	}

	if (!empty($_POST['non_stock']) && $_POST['non_stock'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Non Stock');
		$cell_array_index ++;
	}

	if (!empty($_POST['freight']) && $_POST['freight'] == 'on') {
		$worksheet->getCell($cells[$cell_array_index].'1')->setValue('Freight');
		$cell_array_index ++;
	}

	//record the amount of headers created so that we know how many columns to produce below
	$number_of_columns = $cell_array_index;

	$stock_ids = prepared_query::fetch("SELECT psc.stock_id, psc.stock_name, psc.stock_description, psc.last_quantity_change, psc.stock_weight, psc.last_weight_change, psc.serialized, psc.discontinued, psc.max_displayed_quantity, psc.drop_ship, psc.non_stock, psc.freight, psc.stock_price, psc.dealer_price, psc.wholesale_high_price, psc.wholesale_low_price, psc.products_stock_control_category_id, psc.dealer_warranty_id, psc.target_inventory_level, psc.max_inventory_level, psc.warranty_id, psc.min_inventory_level, psce.stock_location, psce.stock_location_2, vtsi.vendors_pn AS preferred_vendor_part_number, vtsi.vendors_price AS preferred_vendor_price, vtsi.always_avail AS preferred_always_available, vtsi.notes AS preferred_vendor_notes, vtsi.lead_time AS preferred_vendor_lead_time, v.vendors_company_name AS preferred_vendor, c.conditions_name FROM products_stock_control psc INNER JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id LEFT JOIN conditions c ON psc.conditions = c.conditions_id WHERE 1 AND psc.stock_id IS NOT NULL ".$customize, cardinality::SET);

	//now create the body of the spreadsheet
	$count = 2;
	foreach ($stock_ids as $unused => $row) {
		if (empty($row['stock_id'])) continue;

		$worksheet->getCell($cells[0].$count)->setValue($row['stock_id']);
		$worksheet->getCell($cells[1].$count)->setValue($row['stock_name']);

		$cell_array_index = 2;

		if (!empty($_POST['stock_description']) && $_POST['stock_description'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['stock_description']);
			$cell_array_index++;
		}

		if (!empty($_POST['ipn_weight']) && $_POST['ipn_weight'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['stock_weight']);
			$cell_array_index ++;
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['last_weight_change']);
			$cell_array_index ++;
		}

		if (!empty($_POST['stock_location']) && $_POST['stock_location'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['stock_location']);
			$cell_array_index ++;
		}

		if (!empty($_POST['stock_location_2']) && $_POST['stock_location_2'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['stock_location_2']);
			$cell_array_index ++;
		}

		if (!empty($_POST['condition']) && $_POST['condition'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['conditions_name']);
			$cell_array_index ++;
		}

		if (!empty($_POST['serialized']) && $_POST['serialized'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['serialized']);
			$cell_array_index ++;
		}

		if (!empty($_POST['stock_price']) && $_POST['stock_price'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['stock_price']);
			$cell_array_index ++;
		}

		if (!empty($_POST['dealer_price']) && $_POST['dealer_price'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['dealer_price']);
			$cell_array_index ++;
		}

		if (!empty($_POST['wholesale_high_price']) && $_POST['wholesale_high_price'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['wholesale_high_price']);
			$cell_array_index ++;
		}

		if (!empty($_POST['wholesale_low_price']) && $_POST['wholesale_low_price'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['wholesale_low_price']);
			$cell_array_index ++;
		}

		if (!empty($_POST['last_quantity_change']) && $_POST['last_quantity_change'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['last_quantity_change']);
			$cell_array_index ++;
		}

		if (!empty($_POST['preferred_vendor']) && $_POST['preferred_vendor'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['preferred_vendor']);
			$cell_array_index ++;
		}

		if (!empty($_POST['preferred_vendor_part_number']) && $_POST['preferred_vendor_part_number'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['preferred_vendor_part_number']);
			$cell_array_index ++;
		}

		if (!empty($_POST['preferred_always_available']) && $_POST['preferred_always_available'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['preferred_always_available']);
			$cell_array_index ++;
		}

		if (!empty($_POST['preferred_vendor_price']) && $_POST['preferred_vendor_price'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['preferred_vendor_price']);
			$cell_array_index ++;
		}

		if (!empty($_POST['preferred_vendor_lead_time']) && $_POST['preferred_vendor_lead_time'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['preferred_vendor_lead_time']);
			$cell_array_index ++;
		}

		if (!empty($_POST['preferred_vendor_notes']) && $_POST['preferred_vendor_notes'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['preferred_vendor_notes']);
			$cell_array_index ++;
		}

		if (!empty($_POST['ipn_category']) && $_POST['ipn_category'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($ipn_cats[$row['products_stock_control_category_id']]);
			$cell_array_index ++;
		}

		if (!empty($_POST['ipn_warranty']) && $_POST['ipn_warranty'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue(!empty($row['warranty_id'])?$warranties[$row['warranty_id']]:'');
			$cell_array_index ++;
		}

		if (!empty($_POST['dealer_warranty']) && $_POST['dealer_warranty'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue(!empty($row['dealer_warranty_id'])?$dealer_warranties[$row['dealer_warranty_id']]:NULL);
			$cell_array_index ++;
		}

		if (!empty($_POST['min_inventory_level']) && $_POST['min_inventory_level'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['min_inventory_level']);
			$cell_array_index ++;
		}

		if (!empty($_POST['target_inventory_level']) && $_POST['target_inventory_level'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['target_inventory_level']);
			$cell_array_index ++;
		}

		if (!empty($_POST['max_inventory_level']) && $_POST['max_inventory_level'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['max_inventory_level']);
			$cell_array_index ++;
		}

		if (!empty($_POST['max_displayed_quantity']) && $_POST['max_displayed_quantity'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['max_displayed_quantity']);
			$cell_array_index ++;
		}

		if (!empty($_POST['discontinued']) && $_POST['discontinued'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['discontinued']);
			$cell_array_index ++;
		}

		if (!empty($_POST['drop_ship']) && $_POST['drop_ship'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['drop_ship']);
			$cell_array_index ++;
		}

		if (!empty($_POST['non_stock']) && $_POST['non_stock'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['non_stock']);
			$cell_array_index ++;
		}

		if (!empty($_POST['freight']) && $_POST['freight'] == 'on') {
			$worksheet->getCell($cells[$cell_array_index].$count)->setValue($row['freight']);
			$cell_array_index ++;
		}

		$count ++;
	}

	//$worksheet->protectCells('B1:B'.(--$count), 'PHP');

	//save the spreadsheet
	$wb_file = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($workbook, 'Xlsx');
	$wb_file->save(__DIR__.'/../feeds/ipn-export-ipns.xlsx');

	header('Content-disposition: attachment; filename=ipn-export-ipns.xlsx');
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

	echo file_get_contents(__DIR__.'/../feeds/ipn-export-ipns.xlsx');
	exit();
}

//---------header-----------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//---------end header-------------

//---------body-------------------
$content_map = new ck_content();

if (!empty($errors)) {
	$content_map->{'has_errors?'} = 1;
	$content_map->errors = $errors;
}

//ipn export options for the view, decided to manage them here for ease of use, create the options here for more flexibility in the future
$ipn_export_options[] = ['id' => 'ipn-weight', 'name' => 'ipn_weight', 'title' => 'IPN Weight'];
$ipn_export_options[] = ['id' => 'stock-description', 'name' => 'stock_description', 'title' => 'Stock Description'];
$ipn_export_options[] = ['id' => 'condition', 'name' => 'condition', 'title' => 'Condition'];
$ipn_export_options[] = ['id' => 'serialized', 'name' => 'serialized', 'title' => 'Serialized'];
$ipn_export_options[] = ['id' => 'stock-price', 'name' => 'stock_price', 'title' => 'Stock Price'];
$ipn_export_options[] = ['id' => 'dealer-price', 'name' => 'dealer_price', 'title' => 'Dealer Price'];
$ipn_export_options[] = ['id' => 'wholesale-high-price', 'name' => 'wholesale_high_price', 'title' => 'Wholesale High Price'];
$ipn_export_options[] = ['id' => 'wholesale-low-price', 'name' => 'wholesale_low_price', 'title' => 'Wholesale Low Price'];
$ipn_export_options[] = ['id' => 'stock-weight', 'name' => 'stock_weight', 'title' => 'Stock Weight'];
$ipn_export_options[] = ['id' => 'stock-location', 'name' => 'stock_location', 'title' => 'Stock Location'];
$ipn_export_options[] = ['id' => 'stock-location2', 'name' => 'stock_location2', 'title' => 'Stock Location-2'];
$ipn_export_options[] = ['id' => 'preferred-vendor', 'name' => 'preferred_vendor', 'title' => 'Preferred Vendor'];
$ipn_export_options[] = ['id' => 'preferred-vendor-part-number', 'name' => 'preferred_vendor_part_number', 'title' => 'Preferred Vendor Part Number'];
$ipn_export_options[] = ['id' => 'preferred-vendor-price', 'name' => 'preferred_vendor_price', 'title' => 'Preferred Vendor Price'];
$ipn_export_options[] = ['id' => 'preferred-always-available', 'name' => 'preferred_always_available', 'title' => 'Preferred Always Available'];
$ipn_export_options[] = ['id' => 'preferred-vendor-lead-time', 'name' => 'preferred_vendor_lead_time', 'title' => 'Preferred Vendor Lead Time'];
$ipn_export_options[] = ['id' => 'preferred-vendor-notes', 'name' => 'preferred_vendor_notes', 'title' => 'Preferred Vendor Notes'];
$ipn_export_options[] = ['id' => 'ipn-category', 'name' => 'ipn_category', 'title' => 'IPN Category'];
$ipn_export_options[] = ['id' => 'ipn-warranty', 'name' => 'ipn_warranty', 'title' => 'IPN Warranty'];
$ipn_export_options[] = ['id' => 'dealer-warranty', 'name' => 'dealer_warranty', 'title' => 'Dealer Warranty'];
$ipn_export_options[] = ['id' => 'min-inventory-level', 'name' => 'min_inventory_level', 'title' => 'Min Inventory Level'];
$ipn_export_options[] = ['id' => 'target-inventory-level', 'name' => 'target_inventory_level', 'title' => 'Target Inventory Level'];
$ipn_export_options[] = ['id' => 'max-inventory-level', 'name' => 'max_inventory_level', 'title' => 'Max Inventory Level'];
$ipn_export_options[] = ['id' => 'max-displayed-quantity', 'name' => 'max_displayed_quantity', 'title' => 'Max Displayed Quantity'];
$ipn_export_options[] = ['id' => 'discontinued', 'name' => 'discontinued', 'title' => 'Discontinued'];
$ipn_export_options[] = ['id' => 'drop-ship', 'name' => 'drop_ship', 'title' => 'Drop Ship'];
$ipn_export_options[] = ['id' => 'non-stock', 'name' => 'non_stock', 'title' => 'Non-Stock'];
$ipn_export_options[] = ['id' => 'freight', 'name' => 'freight', 'title' => 'Freight'];
$ipn_export_options[] = ['id' => 'last_quantity_change', 'name' => 'last_quantity_change', 'title' => 'Last Quantity Change'];

//loop through the array and assign each iteration to the content map
foreach ($ipn_export_options as $ieo) {
	$content_map->ipn_export_options[] = $ieo;
}

//retrieve all the vendors for the vendor selection
$vendor_array = prepared_query::fetch('SELECT vendors_id, vendors_company_name, vendors_email_address, company_payment_terms FROM vendors ORDER BY vendors_company_name ASC', cardinality::SET);

//loop through the array and assign each iteration to the content map
foreach ($vendor_array as $vend) {
	$content_map->vendor_options[] = $vend;
}

//retrieve all category options
$category_array = prepared_query::fetch('SELECT categories_id, name AS category_name FROM products_stock_control_categories', cardinality::SET);

//loop through all categories and assing to content map
foreach ($category_array as $category) {
	$content_map->category_options[] = $category;
}

$cktpl->content('includes/templates/page-ipn-export.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>