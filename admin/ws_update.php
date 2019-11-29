<?php
require_once(__DIR__.'/../includes/application_top.php');

error_reporting(E_ALL);

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

require_once(DIR_FS_CATALOG.'includes/library/nav/nav_simploader.php');

if (!empty($_REQUEST['action'])) {
	if (!empty($_POST['msg_id'])) {
		$msg = new export_message();
		$msg->confirm_message($_POST['msg_id']);
	}
	switch ($_REQUEST['action']) {
		case 'simple-success':
			echo json_encode(array('status' => 1)); // nothing else to do
			break;
		case 'set-nav-id':
			$customer = new nav_customer($_REQUEST['customers_id']);
			$customer->track_nav_id($_REQUEST['nav_id']);
			prepared_query::execute('UPDATE customers SET nav_id = ? WHERE customers_id = ?', array($_REQUEST['nav_id'], $_REQUEST['customers_id']));
			echo json_encode(array('status' => 1, 'message' => 'copacetic')); // either way, we properly handled
			break;
		case 'set-order-nav-id':
			if (!empty($_REQUEST['nav_id']) && !empty($_REQUEST['orders_id'])) {
				$order = new nav_order($_REQUEST['orders_id']);
				$order->track_nav_id($_REQUEST['nav_id']);
				prepared_query::execute('UPDATE orders SET nav_id = ? WHERE orders_id = ?', array($_REQUEST['nav_id'], $_REQUEST['orders_id']));
			}
			else {
				// log error
			}
			echo json_encode(array('status' => 1)); // either way, we properly handled
			break;
		case 'set-nav-id-group':
			nav_customer::prepare_import('key', $_REQUEST['list']);
			nav_customer::import();
			echo json_encode(array('status' => 1, 'message' => 'copacetic')); // either way, we properly handled
			break;
		case 'set-vendor-nav-id-group':
			ob_start();
			nav_vendor::prepare_import($_REQUEST['list']);
			nav_vendor::import();
			$errata = ob_get_clean();
			echo json_encode(array('status' => 1, 'message' => 'copacetic', 'errata' => $errata)); // either way, we properly handled
			break;
		case 'set-item-complete-group':
			nav_item::prepare_import($_REQUEST['list']);
			nav_item::import();
			echo json_encode(array('status' => 1, 'message' => 'copacetic')); // either way, we properly handled
			break;
		case 'update-qtys':
			foreach ($_REQUEST['list'] as $item) {
				// we hide errors for these cuz Variant_Code is missing on some training items
				//@prepared_query::execute('UPDATE products_stock_control SET stck_qty = ? WHERE nav_id = ? AND nav_variant = ?', array($item['Available_Inventory'], $item['Item_No'], $item['Variant_Code']));
				//@prepared_query::execute('UPDATE products p, products_stock_control psc SET p.prdcts_qty = ? WHERE p.stock_id = psc.stock_id AND psc.nav_id = ? and psc.nav_variant = ?', array($item['Available_Inventory'], $item['Item_No'], $item['Variant_Code']));

				// watch for errors and handle appropriately
			}
			echo json_encode(array('status' => 1));
			break;
		case 'NAV-UPDATE-Order':
			$order = new nav_order($_REQUEST['nav_obj']['Web_ID'], $_REQUEST['nav_obj']);
			$order->import_data(FALSE); // not a local order
			break;
		case 'NAV-UPDATE-Item':
			$item = new nav_item($_REQUEST['nav_obj']['Web_ID'], $_REQUEST['nav_obj']);
			$item->import_data(FALSE); // not a local item
			break;
		case 'NAV-UPDATE-Qty':
			$item = new nav_item($_REQUEST['nav_obj']['Web_ID'], $_REQUEST['nav_obj']);
			$item->import_data(FALSE);
			break;
		case 'NAV-UPDATE-Customer':
			$customer = new nav_customer($_REQUEST['nav_obj']['Web_ID'], $_REQUEST['nav_obj']);
			$customer->import_data(FALSE);
			break;
		/*case 'update-item-header':
			$data = $_REQUEST['data'];
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
				//$vertical = $vertical_map[$data['Item_Category_Code']]; // this is totally dependent on category
				$category = $category_map[$data['Product_Group_Code']];
				prepared_query::execute('UPDATE products_stock_control psc, products_stock_control_categories pscc SET psc.stock_name = ?, psc.average_cost = ?, psc.stock_weight = ?, psc.products_stock_control_category_id = pscc.categories_id WHERE psc.nav_id = ? AND pscc.name LIKE ?', array($data['No_2'], $data['Unit_Cost'], $data['Net_Weight'], $data['No'], $category));
				prepared_query::execute('UPDATE products p, products_stock_control psc SET products_weight = ? WHERE p.stock_id = psc.stock_id AND psc.nav_id = ?', array($data['Net_Weight'], $data['No']));
				//prepared_query::execute('UPDATE ', array());
			}
			elseif ($data['update_action'] == 'delete') {
			}
			break;
		case 'update-item-variant':
			$data = $_REQUEST['data'];
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
				prepared_query::execute('UPDATE products_stock_control SET stock_description = ? WHERE nav_id = ? AND nav_variant = ?', array($data['Description'], $data['Item_No'], $data['Code']));
			}
			elseif ($data['update_action'] == 'delete') {
			}
			elseif ($data['update_action'] == 'qty') {
				@prepared_query::execute('UPDATE products_stock_control SET stck_qty = ? WHERE nav_id = ? AND nav_variant = ?', array($data['Salable_Qty'], $data['Item_No'], $data['Code']));
				@prepared_query::execute('UPDATE products p, products_stock_control psc SET p.prdcts_qty = ? WHERE p.stock_id = psc.stock_id AND psc.nav_id = ? and psc.nav_variant = ?', array($item['Salable_Qty'], $item['Item_No'], $item['Code']));
			}
			break;
		case 'update-item-crossref':
			$data = $_REQUEST['data'];
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
				prepared_query::execute('UPDATE products_stock_control psc, products p, products_description pd SET p.products_status = ?, pd.products_name = ?, pd.products_head_title_tag = ? WHERE psc.nav_id = ? AND psc.nav_code = ? AND p.products_model = ?', array(($data['Export_To_Web']=='true'), $data['Web_Product_Name'], $data['Web_Product_Name'], $data['Item_No'], $data['Variant_Code'], $data['Cross_Reference_No']));
			}
			elseif ($data['update_action'] == 'delete') {
			}
			break;
		case 'update-item-price':
			$data = $_REQUEST['data'];
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
			}
			elseif ($data['update_action'] == 'delete') {
			}
			break;
		case 'update-item-assembly':
			$data = $_REQUEST['data'];
			// we'll see if we actually want to update this from nav or it makes more sense to set this up directly on the web
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
			}
			elseif ($data['update_action'] == 'delete') {
			}
			break;
		case 'update-customer-header':
			$data = $_REQUEST['data'];
			if ($data['update_action'] == 'insert') {
				// for all inserts, we'll need to check to make sure we're not entering a duplicate
				// for now, it makes more sense to allow the following traffic, because it's much easier to catch on this end:
				// web insert -> NAV insert -> web id update
				//				|
				//				--> web insert (catch and discard)
			}
			elseif ($data['update_action'] == 'update') {
			}
			elseif ($data['update_action'] == 'delete') {
			}
			break;
		case 'update-customer-contact':
			$data = $_REQUEST['data'];
			// match contact that has the Contact No and Customer No
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
			}
			elseif ($data['update_action'] == 'delete') {
			}
			break;
		case 'update-customer-address':
			$data = $_REQUEST['data'];
			$name = explode(' ', $data['Name'], 2);
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
				prepared_query::execute('UPDATE address_book ab, customer c, countries ctry, zones z SET entry_firstname = ?, entry_lastname = ?, entry_street_address = ?, entry_suburb = ?, entry_postcode = ?, entry_city = ?, entry_state = ?, entry_country_id = ctry.countries_id, entry_zone_id = z.zone_id, entry_telephone = ? WHERE ab.customers_id = c.customers_id AND c.nav_id = ? AND ab.nav_code = ? AND ctry.countries_iso_code_2 LIKE ? AND z.zone_code LIKE ?', array($name[0], $name[1], $data['Address'], @$data['Address_2'], $data['Post_Code'], $data['City'], $data['County'], @$data['Phone_No'], $data['Customer_No'], $data['Code'], $data['Country_Region_Code'], $data['County']));
			}
			elseif ($data['update_action'] == 'delete') {
			}

			// watch for errors and handle appropriately
			break;
		case 'update-order-header':
			$data = $_REQUEST['data'];
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
			}
			elseif ($data['update_action'] == 'delete') {
			}
			break;
		case 'update-order-lines':
			$data = $_REQUEST['data'];
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
			}
			elseif ($data['update_action'] == 'delete') {
			}
			break;
		case 'update-order-invoice':
			$data = $_REQUEST['data'];
			if ($data['update_action'] == 'insert') {
			}
			elseif ($data['update_action'] == 'update') {
			}
			elseif ($data['update_action'] == 'delete') {
			}
			break;*/
		case 'log-error':
			// log the error returned
			echo json_encode(array('status' => 1)); // nothing else to do
			break;
		case 'ignore':
			echo json_encode(array('status' => 1)); // nothing else to do
			break;
		default:
			// log unexpected request
			echo json_encode(array('status' => 0, 'message' => 'action: '.$_REQUEST['action'])); // we probably want to indicate to the client that we couldn't understand their request
			break;
	}
}
else {
	// we always always require an action to be passed in, so this is an error state of some sort
	if (isset($_REQUEST['status'])) {
		if ($_REQUEST['status'] == 1) ; // if they are sending us a success message, we need an action we can handle, even if it's just an ignore action
		else {
			// log the error that was returned from the client, if we have one
			// we should still be told explicitly that we want to just log the error, if that's all we want to do
		}
	}
	else {
		// we probably want to indicate to the client that we couldn't understand their request
	}
	echo json_encode(array('status' => 0, 'message' => print_r($_REQUEST, TRUE)));
}
?>