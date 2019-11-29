<?php
require('includes/application_top.php');

ini_set('memory_limit', '512M');
set_time_limit(0);

$poId = isset($_REQUEST['poId'])?$_REQUEST['poId']:NULL;
if (empty($_REQUEST['ajax'])) {
	if (is_null($poId)) throw new Exception('Purchase order ID was not set.');
}

$action = isset($_REQUEST['action'])?$_REQUEST['action']:NULL;
$save_action = isset($_REQUEST['save_action'])?$_REQUEST['save_action']:NULL;

if (!printfile::station_is_set()) printfile::set_station(printfile::$default_station_id);

switch ($action) {
	case 'manually-close':
		if (!empty($poId)) {
			$po = new ck_purchase_order($poId);
			$po->force_close();
		}
		CK\fn::redirect_and_exit('/admin/po_receiver.php?poId='.$poId);
		break;
	case 'add-unexpected-ipn':
		if (!empty($poId)) {
			$details = [
				':ipn_id' => $_REQUEST['stock_id'],
				':quantity' => $_REQUEST['qty'],
				//':cost' => CK\text::demonetize(@$_REQUEST['cost']),
				':unexpected_freebie' => $__FLAG['freebie']?1:NULL,
				':unexpected_notes' => !empty($_REQUEST['notes'])?$_REQUEST['notes']:NULL
			];
			//if (empty($details[':cost'])) $details[':cost'] = 0;
			$po = new ck_purchase_order($poId);
			$po->add_unexpected_ipn($details);
		}
		CK\fn::redirect_and_exit('/admin/po_receiver.php?poId='.$poId);
		break;
	case 'receive-product':
		$response = [];
		$po = new ck_purchase_order($poId);
		$po_review_id = $_POST['po_review_id'];

		ob_start();

		try {
			debug_tools::init_page();
			debug_tools::show_all();
			$review = $po->get_review_direct($po_review_id);
			if ($review['status'] == ck_purchase_order::$review_statuses['COMPLETED']) {
				$po_review_id = $po->create_review(['purchase_order_tracking_id' => $review['purchase_order_tracking_id']]);
				$review = $po->get_review_direct($po_review_id);
			}

			$data = [
				'pop_id' => $_POST['po_product_id'],
				'qty_received' => !empty($_POST['receiving-qty'])?$_POST['receiving-qty']:1,
				'hold_disposition_id' => !empty($_POST['disposition'])?$_POST['disposition']:NULL,
			];

			if (!empty($_POST['receiving-weight'])) $data['weight'] = $_POST['receiving-weight'];

			$po_product = $po->get_product_direct($data['pop_id']);

			$serial = NULL;
			if ($po_product['ipn']->is('serialized')) {
				if (!empty($_REQUEST['bulk-serial-entry'])) $serials = preg_split('/\s+/', trim($_REQUEST['bulk-serial-entry']));
				elseif (!empty($_REQUEST['serial_number'])) $serials = [trim($_REQUEST['serial_number'])];
				else $serials = [];

				$serial = [
					'serials' => $serials,
					'details' => [
						'conditions' => $_REQUEST['conditions'],
						'show_version' => !empty($_REQUEST['show_version'])?$_REQUEST['show_version']:NULL,
						'short_notes' => !empty($_REQUEST['short_notes'])?$_REQUEST['short_notes']:NULL,
						'bin_location' => !empty($_REQUEST['bin_location'])?$_REQUEST['bin_location']:'',
						'ios' => !empty($_REQUEST['ios'])?$_REQUEST['ios']:NULL,
						'version' => !empty($_REQUEST['version'])?$_REQUEST['version']:NULL,
						'dram' => !empty($_REQUEST['dram'])?$_REQUEST['dram']:NULL,
						'flash' => !empty($_REQUEST['flash'])?$_REQUEST['flash']:NULL,
						'mac_address' => !empty($_REQUEST['mac_address'])?$_REQUEST['mac_address']:NULL,
						'tester_admin_id' => !empty($_REQUEST['tester_admin_id'])?$_REQUEST['tester_admin_id']:NULL
					],
					'hold_notes' => !empty($_REQUEST['hold_notes'])?$_REQUEST['hold_notes']:NULL
				];
			}

			if (empty($serials) && !empty($_REQUEST['serial_history_id'])) {
				$ser = ck_serial::get_serial_by_history_id($_REQUEST['serial_history_id']);
				$serials = [$ser->get_header('serial_number')];

				$ser->update_history_record($_REQUEST['serial_history_id'], [
					'conditions' => $_REQUEST['conditions'],
					'show_version' => !empty($_REQUEST['show_version'])?$_REQUEST['show_version']:NULL,
					'short_notes' => !empty($_REQUEST['short_notes'])?$_REQUEST['short_notes']:NULL,
					'bin_location' => !empty($_REQUEST['bin_location'])?$_REQUEST['bin_location']:'',
					'ios' => !empty($_REQUEST['ios'])?$_REQUEST['ios']:NULL,
					'version' => !empty($_REQUEST['version'])?$_REQUEST['version']:NULL,
					'dram' => !empty($_REQUEST['dram'])?$_REQUEST['dram']:NULL,
					'flash' => !empty($_REQUEST['flash'])?$_REQUEST['flash']:NULL,
					'mac_address' => !empty($_REQUEST['mac_address'])?$_REQUEST['mac_address']:NULL,
					'tester_admin_id' => !empty($_REQUEST['tester_admin_id'])?$_REQUEST['tester_admin_id']:NULL
				]);
			}
			else {
				$po->adjust_review_qty($review['po_review_id'], $data, $serial);
			}

			// send data to print barcodes
			if ($po_product['ipn']->is('serialized')) {
				try {
					foreach ($serials as $srl) {
						$labels = new printfile('serial');
						if (count($po_product['ipn']->get_listings()) == 1) {
							$label_key = $po_product['ipn']->get_listings()[0]->get_header('products_model');
						}
						else {
							$label_parts = explode('-', $po_product['ipn']->get_header('ipn'));
							if (count($label_parts) >= 2) array_pop($label_parts);
							$label_key = implode('-', $label_parts);
						}
						$labels->write([[$label_key, $srl, 1, $serial['details']['mac_address']]]);
						$labels->send_print();

						/*$bins = new printfile('bin');
						$bins->write([[$po_product['ipn']->get_header('ipn'), $po_product['ipn']->get_header('bin1'), $po_product['ipn']->get_header('bin2'), '', 1]]);
						$bins->send_print();*/
					}
				}
				catch (Exception $e) {
					throw new Exception('Barcode Label could not be printed; Product was received successfully. ['.$e->getMessage().']');
				}
			}
			else {
				try {
					$labels = new printfile('nonserial');
					$labels->write([[$po_product['ipn']->get_header('ipn'), $data['qty_received']]]);
					$labels->send_print();

					$bins = new printfile('bin');
					$bins->write([[$po_product['ipn']->get_header('ipn'), $po_product['ipn']->get_header('bin1'), $po_product['ipn']->get_header('bin2'), '', $data['qty_received']]]);
					$bins->send_print();
				}
				catch (Exception $e) {
					throw new Exception('Barcode Label could not be printed; Product was received successfully. ['.$e->getMessage().']');
				}
			}
			/*
				$print = new api_print_node;

				if (!empty($pop['stock_location'])) {
					$lbl = $print->composite_label('bin', ['bin' => $pop['stock_location'], 'qty' => $_REQUEST['receiving-qty'], 'print-qty' => 1]);
					$response = $print->send_to_printer($lbl);
					/*echo '<div>';
					echo $lbl;
					var_dump($response);
					echo '</div>';* /
				}

				$lbl = $print->composite_label('nonserialized-ipn', ['ipn' => $pop['stock_name'], 'qty' => 1, 'print-qty' => $_REQUEST['receiving-qty']]);
				$response = $print->send_to_printer($lbl);
				/*echo '<div>';
				echo $lbl;
				var_dump($response);
				echo '</div>';* /
			*/

			// gotta reload it because the data has been changed
			$po_product = $po->get_product_direct($data['pop_id']);

			$response['po_tracking_id'] = $review['purchase_order_tracking_id'];
			$response['po_product_id'] = $data['pop_id'];
			$response['remaining'] = $po_product['qty_remaining'] - $po_product['qty_reviewing'];
			$response['debug_prod'] = $po_product;
			if ($po_product['ipn']->is('serialized')) {
				$response['serial_field'] = !empty($_REQUEST['bulk-serial-entry'])?'bulk-serial-entry':'serial_number';
				//$response['serials'] = array_map(function($serial) { return array_merge($serial->get_header(), $serial->get_current_history()); }, $po->get_open_reviewing_products($data['pop_id'])['serials']);
			}
		}
		catch (Exception $e) {
			$response['error'] = $e->getMessage();
		}

		$response['debug'] = ob_get_clean();

		echo json_encode($response);
		exit();
		break;
	case 'delete-receiving-session-ipn':
		$po = new ck_purchase_order($poId);

		$response = [];

		try {
			if (!($review = $po->get_open_reviews($_REQUEST['po_tracking_id']))) throw new Exception('Failure looking up open receiving session for this tracking number; ensure it has not already been received.');

			// if it's not there, we can ignore it because it's already removed
			if ($po->has_reviewing_products($review['po_review_id'], $_REQUEST['po_product_id'])) {
				$rp = $po->get_reviewing_products($review['po_review_id'], $_REQUEST['po_product_id']);

				$po->remove_review_product($rp);

				// now that we've removed it, we need to update remaining appropriately
				$po_product = $po->get_products($rp['po_product_id']);
				$response['po_product_id'] = $po_product['po_product_id'];
				$response['remaining'] = $po_product['qty_remaining'] - $po_product['qty_reviewing'];
				$response['debug'] = $po_product;
			}
		}
		catch (CKPurchaseOrderException $e) {
			$response['error'] = $e->getMessage();
		}
		catch (Exception $e) {
			$response['error'] = 'Could not delete product from receiving session: '.$e->getMessage();
		}

		echo json_encode($response);
		exit();
		break;
	case 'edit-serial-history-data':
		$serial = ck_serial::get_serial_by_history_id($_REQUEST['serial_history_id']);
		echo json_encode(array_merge($serial->get_header(), $serial->get_current_history(), $serial->get_hold()));
		exit();
		break;
	case 'delete-session-serial':
		$po = new ck_purchase_order($poId);
		$serial_history_id = $_POST['serial_history_id'];

		$response = [];

		try {
			$serial = ck_serial::get_serial_by_history_id($serial_history_id);
			$history = $serial->get_history($serial_history_id);

			$po_review = prepared_query::fetch('SELECT porp.pop_id, por.id as po_review_id, por.purchase_order_tracking_id FROM purchase_order_review_product porp JOIN purchase_order_review por ON porp.po_review_id = por.id WHERE porp.id = :purchase_order_review_product_id', cardinality::ROW, [':purchase_order_review_product_id' => $history['purchase_order_received_products_id']]);

			$po->delete_review_serial($serial_history_id);

			$response['success'] = 1;

			$response['po_tracking_id'] = $po_review['purchase_order_tracking_id'];
			$response['po_product_id'] = $history['purchase_order_products_id'];
			$response['remaining'] = 0;
			if (!empty($history['purchase_order_products_id']) && $po_product = $po->get_products($history['purchase_order_products_id'])) $response['remaining'] = $po_product['qty_remaining'] - $po_product['qty_reviewing'];
		}
		catch (CKPurchaseOrderException $e) {
			$response['success'] = 0;
			$response['error'] = $e->getMessage();
		}
		catch (Exception $e) {
			$response['success'] = 0;
			$response['error'] = 'Problem deleting session serial: '.$e->getMessage();
		}

		echo json_encode($response);
		exit();
		break;
	case 'update_receiving_station':
		echo printfile::set_station($_REQUEST['receiving_station']);
		exit();
		break;
	case 'add_note':
		$po_note_id = prepared_query::insert('INSERT INTO purchase_order_notes (purchase_order_id, purchase_order_note_user, purchase_order_note_created, purchase_order_note_text) VALUES (:purchase_order_id, :purchase_order_note_user, NOW(), :purchase_order_note_text)', [':purchase_order_id' => $_POST['poId'], ':purchase_order_note_user' => $_SESSION['login_id'], ':purchase_order_note_text' => $_POST['notes']]);

		// response data for display
		$admin_name = $_SESSION['perms']['admin_firstname'].' '.$_SESSION['perms']['admin_lastname'];
		$data = [
			'note' => nl2br($_POST['notes']),
			'created_on' => date('Y-m-d H:i:s'),
			'created_by' => $admin_name,
		];

		$po = new ck_purchase_order($_POST['poId']);
		// $po->send_new_po_note_notification($po_note_id);
		echo json_encode($data);
		exit();
		break;
	/*case 'save':
		$po = new ck_purchase_order($poId);

		switch ($save_action) {
			case 'complete-review-session':
				if ($po->complete_review_session($_POST['review_session_id'])) {
					CK\fn::redirect_and_exit('/admin/po_receiver.php?action=save&save_action=auto&poId='.$po->id());
				}
				else {
					$_SESSION['review_error'] = 'Unknown error completing the review session';
					CK\fn::redirect_and_exit('/admin/po_editor.php?action=edit&poId='.$po->id());
				}
				break;
			case 'auto':
				if ($po->complete_receiving_session()) {
					CK\fn::redirect_and_exit('/admin/po_list.php');
				}
				else {
					exit();
					CK\fn::redirect_and_exit('/admin/po_receiver.php?poId='.$poId);
				}
				break;
			default:
				throw new Exception('Something unexpected happened - contact Dev');
				break;
		}
		break;*/
	case 'complete-receiving-session':
		$po = new ck_purchase_order($poId);

		$response = [];

		if ($po->is_receiving_locked()) $response['error'] = 'Receiving is temporarily locked on this PO. If you happened to submit twice, this will halt the double receipt.';
		else {
			$po->lock_receiving();

			try {
				foreach ($_REQUEST['complete-tracking-number'] as $po_tracking_id => $on) {
					if (CK\fn::check_flag($on)) {
						if ($review = $po->get_open_reviews($po_tracking_id)) {
							$po->receive_review($review);
						}
					}
				}
			}
			catch (CKPurchaseOrderException $e) {
				$response['error'] = $e->getMessage();
			}
			catch (Exception $e) {
				$response['error'] = 'Failed completing receiving sessions: '.$e->getMessage();
			}

			$po->unlock_receiving();
		}

		if ($__FLAG['ajax']) {
			echo json_encode($response);
			exit();
		}
		elseif (!empty($response['error'])) {
			echo $response['error'];
		}
		else {
			CK\fn::redirect_and_exit('/admin/po_receiver.php?poId='.$po->id());
		}
		break;
	case 'complete-review-session':
		$po = new ck_purchase_order($poId);

		try {
			$qry = ['poId' => $po->id(), 'action' => 'complete-receiving-session'];

			foreach ($_REQUEST['reviews'] as $po_review_id => $products) {
				$review = $po->get_review_direct($po_review_id);
				$po->review_review($po_review_id, $products);
				$qry['complete-tracking-number['.$review['purchase_order_tracking_id'].']'] = 'on';
			}

			CK\fn::redirect_and_exit('/admin/po_receiver.php?'.http_build_query($qry));
		}
		catch (CKPurchaseOrderException $e) {
			$_SESSION['review_error'] = $e->getMessage();
			CK\fn::redirect_and_exit('/admin/po_editor.php?action=edit&poId='.$po->id());
		}
		catch (Exception $e) {
			$_SESSION['review_error'] = 'There was a problem completing your review: '.$e->getMessage();
			CK\fn::redirect_and_exit('/admin/po_editor.php?action=edit&poId='.$po->id());
		}

		break;
	case 'unlock-review':
		$po = new ck_purchase_order($poId);

		$po->unlock_review($_GET['po_review_id']);

		CK\fn::redirect_and_exit('/admin/po_receiver.php?poId='.$po->id());
		break;
	case 'retrieve-review-sessions':
		$response = [];
		$po = new ck_purchase_order($poId);

		$revs = [];

		if ($reviews = $po->get_open_reviews_direct()) {
			foreach ($reviews as $review) {
				$review['po_id'] = $po->id();

				if (empty($review['tracking_number'])) $review['tracking_number'] = 'NONE';

				if ($review['lock_for_review']) $review['under_review'] = 1;

				if ($p = $po->get_reviewing_products_direct($review['po_review_id'])) {
					$review['products'] = [];
					foreach ($p as $product) {
						$prod = [
							'po_review_product_id' => $product['po_review_product_id'],
							'po_product_id' => $product['po_product_id'],
							'stock_name' => $product['ipn']->get_header('ipn'),
							'qty_received' => $product['qty_received']
						];

						if (!empty($product['hold_disposition_id'])) $prod['on_hold'] = 1;
						if ($product['unexpected']) $prod['unexpected?'] = 1;

						$review['products'][] = $prod;
					}

					$revs[] = $review;
				}
			}
		}

		if (!empty($reviews)) {
			$response['reviews'] = $revs;
			$cktpl = new ck_template('includes/templates', ck_template::NONE);
			$cktpl->content('includes/templates/partial-review-sessions.mustache.html', $response);
		}
		exit();
		break;
	case 'start-receiving-product':
		$po = new ck_purchase_order($poId);

		$product = $po->get_product_direct($_REQUEST['po_product_id']);

		$response = ['po_tracking_id' => $_REQUEST['po_tracking_id'], 'po_product_id' => $_REQUEST['po_product_id'], 'ipn' => $product['ipn']->get_header('ipn'), 'receiving_weight' => $product['ipn']->get_header('stock_weight')];

		$reviews = $po->get_open_reviews_direct();
		$found = FALSE;

		if (!empty($reviews)) {
			foreach ($reviews as $review) {
				if ($_REQUEST['po_tracking_id'] == $review['purchase_order_tracking_id']) {
					$found = TRUE;
					break; // $review has the info we want
				}
			}
		}
		if ($found) {
			$response['po_review_id'] = $review['po_review_id'];
			$review_products = $po->get_reviewing_products_direct($review['po_review_id']);

			$found = FALSE;
			foreach ($review_products as $review_product) {
				if ($review_product['po_product_id'] == $product['po_product_id']) {
					$found = TRUE;
					break; // $review_product has the info we want
				}
			}

			if (!$found) unset($review_product);
		}
		else $response['po_review_id'] = $po->create_review(['purchase_order_tracking_id' => $_REQUEST['po_tracking_id']]);

		if ($product['ipn']->is('serialized')) {
			$response['serialized'] = 1;
			$all_open_reviews = $po->get_open_reviewing_products_direct($product['po_product_id']);

			$response['qty_remaining'] = $product['qty_remaining'] - array_reduce($all_open_reviews, function($qty, $prod) { return $qty += $prod['qty_received']; }, 0);

			/*$response['serials'] = [];
			foreach ($product['serials'] as $serial) {
				$response['serials'][] = array_merge($serial->get_current_history(), ['serial_number' => $serial->get_header('serial_number')]);
			}*/
		}
		else {
			if (!empty($product['ipn']->get_header('bin1'))) $response['bin1'] = $product['ipn']->get_header('bin1');
			if (!empty($product['ipn']->get_header('bin2'))) $response['bin2'] = $product['ipn']->get_header('bin2');

			if (!empty($review_product['qty_received'])) $response['receiving_qty'] = $review_product['qty_received'];
			if (!empty($review_product['weight'])) $response['receiving_weight'] = $review_product['weight'];
			if (!empty($review_product['hold_disposition_id'])) $response['receiving_disposition'] = $review_product['hold_disposition_id'];
		}

		echo json_encode($response);
		exit();
		break;
	case 'retrieve-review-serials':
		$po = new ck_purchase_order($poId);

		$serials = [];

		if ($reviews = $po->get_open_reviews_direct()) {
			$found = FALSE;
			foreach ($reviews as $review) {
				if ($_REQUEST['po_tracking_id'] == $review['purchase_order_tracking_id']) {
					$found = TRUE;
					break; // $review has the info we want
				}
			}

			if ($found) {
				if ($review_products = $po->get_reviewing_products_direct($review['po_review_id'])) {
					$found = FALSE;
					foreach ($review_products as $review_product) {
						if ($review_product['po_product_id'] == $_REQUEST['po_product_id']) {
							$found = TRUE;
							break; // $review_product has the info we want
						}
					}

					if ($found) {
						$serials['serials'] = array_map(function($serial) { return array_merge($serial->get_header(), $serial->get_current_history()); }, $review_product['serials']);
					}
				}
			}
		}

		$cktpl = new ck_template('includes/templates', ck_template::NONE);
		$cktpl->content('includes/templates/partial-review-session-serials.mustache.html', $serials);
		exit();
		break;
	case 'update-stock-weight':
		$ipn = new ck_ipn2($_REQUEST['stock_id']);
		if ($ipn->update_weight($_REQUEST['stock_weight'], true)) echo json_encode(['success' => true]);
		else echo json_encode(['success' => false]);
		exit();
		break;
	default:
		break;
}

if (!empty($_REQUEST['ajax'])) {
	echo json_encode(['error' => 'ajax response failed?', 'action' => $action]);
	exit();
}

//---------header-----------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
$meta_title = $poId.'-Receiving';
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
if (isset($msgs)) {
	$content_map->{'has_messages?'} = 1;
	$content_map->messages = $msgs;
}

$optimized = ck_purchase_order::receiving_optimized($poId);

if ($optimized) $content_map->optimized = 1;

$po = new ck_purchase_order($poId);
$header = $po->get_header();
$header['id'] = $header['purchase_order_id'];
$header['creation_date'] = $header['creation_date']->format('m/d/Y');
$header['expected_date'] = $header instanceof DateTime?$header['expected_date']->format('m/d/Y'):NULL;
$content_map->po = $header;

$content_map->po['administrator_name'] = $header['administrator']->get_name();

if ($po->has_tracking_numbers()) $content_map->tracking_numbers = $po->get_tracking_numbers();

$po_products = $po->get_products();
$content_map->po_products = [];
foreach ($po_products as $po_product) {
	// if the display is optimized, don't give the hover image
	if ($optimized) {
		$po_product['stock_name'] = $po_product['ipn']->get_header('ipn');
		$po_product['stock_name_safe'] = urlencode($po_product['stock_name']);
	}
	else {
		$stock_name_image = new item_popup($po_product['ipn']->get_header('ipn'), $db, ['stock_id' => $po_product['stock_id']]);
		$po_product['stock_name_images'] = $stock_name_image->__toString();
	}

	if ($po_product['ipn']->is('serialized')) $po_product['serialized?'] = 1;

	if ($po_product['unexpected']) $po_product['unexpected?'] = 1;

	$po_product['received'] = isset($po_product['qty_received'])?$po_product['qty_received']:0;

	$po_product['remaining_display'] = $po_product['qty_remaining'] - $po_product['qty_reviewing'];
	$po_product['session_received'] = $po_product['qty_reviewing'];

	$po_product['stock_weight'] = $po_product['ipn']->get_header('stock_weight');

	if ($po_product['remaining_display'] < 0) $po_product['remaining_display'] = 0;
	if ($po_product['remaining_display'] > 0) $po_product['remaining?'] = 1;

	// if the display is optimized, don't show fully received lines
	if ($optimized && $po_product['remaining_display'] == 0) continue;

	// if the display is optimized, don't show available stock
	if (!$optimized) {
		$inventory_data = $po_product['ipn']->get_inventory();

		$po_product['available'] = $inventory_data['available'];
		if ($inventory_data['available'] < 0) $po_product['unavailable?'] = 1;
	}

	// if the display is optimized, don't show requesting orders
	if (!$optimized) {
		$requesting_orders = prepared_query::fetch('SELECT o.orders_id, op.orders_products_id, op.products_quantity FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE o.orders_status NOT IN (3, 6, 9) AND p.stock_id = :stock_id', cardinality::SET, [':stock_id' => $po_product['stock_id']]);

		$po_product['orders'] = [];

		foreach ($requesting_orders as $requesting_order) {
			//check if it's specifically allocated to this PO
			$allocations = prepared_query::fetch('SELECT SUM(po2oa.quantity) FROM purchase_order_to_order_allocations po2oa WHERE po2oa.purchase_order_product_id = :purchase_order_product_id AND po2oa.order_product_id = :order_product_id', cardinality::SINGLE, [':purchase_order_product_id' => $po_product['po_product_id'], ':order_product_id' => $requesting_order['orders_products_id']]);

			$order = ['orders_id' => $requesting_order['orders_id']];
			if (!empty($allocations)) $order['allocated?'] = 1;
			if ($requesting_order['products_quantity'] != $allocations && $allocations > 0) $order['qty'] = $allocations.'/'.$requesting_order['products_quantity'];

			$po_product['orders'][] = $order;
		}
	}

	// if the display is optimized, don't show picture status
	if (!$optimized) {
		foreach ($po_product['ipn']->get_listings() as $listing) {
			$images = $listing->get_image();
			foreach ($images as $key => $image) {
				$image = str_replace('=', '%3D', trim($image));
				$file = pathinfo($image);
				if ($file['filename'] == 'newproduct') unset($images[$key]);
				else $images[$key] = $image; // trimmed and adjusted
			}

			if (empty($images['products_image_lrg'])) $po_product['need_photo?'] = 1;
			if ($content_reviews = $listing->get_content_reviews(1)) {
				foreach ($content_reviews as $review) {
					// missing, broken, wrong or watermarked images
					if ($review['element'] == 'image' && in_array($review['reason_id'], [2, 3, 5, 6])) {
						$po_product['need_photo?'] = 1;
					}
				}
			}
		}
	}

	$content_map->po_products[] = $po_product;
}

if ($po->can_be_closed()) $content_map->{'can_be_closed?'} = 1;

$upcs = prepared_query::fetch('SELECT psc.stock_id, ua.upc, psc.stock_name as ipn FROM purchase_order_products pop LEFT JOIN ck_upc_assignments ua ON ua.stock_id = pop.ipn_id JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id WHERE pop.purchase_order_id = :po_id', cardinality::SET, [':po_id' => $po->id()]);
$content_map->upcs = [];
$ipn_lookups = [];
foreach ($upcs as $upc) {
	$content_map->upcs[] = ['lookup' => $upc['upc'], 'stock_id' => $upc['stock_id']];
	if (empty($ipn_lookups[$upc['ipn']])) {
		$content_map->upcs[] = ['lookup' => $upc['ipn'], 'stock_id' => $upc['stock_id']];
		$ipn_lookups[$upc['ipn']] = TRUE;
	}
}

// if the display is optimized, don't show PO notes
if (!$optimized) {
	$content_map->po_notes = [];
	if ($po->has_notes()) {
		foreach ($po->get_notes() as $note) {
			$note['note_created'] = $note['note_created']->format("m/d/Y\nh:ia");
			$note['note_text'] = nl2br($note['note_text']);
			$content_map->po_notes[] = $note;
		}
	}
}

$content_map->receiving_stations = array_map(function($key, $val) {
	$station = ['value' => $key, 'label' => $val['name']];
	if (printfile::is_selected($key)) $station['selected?'] = 1;
	return $station;
}, array_keys(printfile::get_stations()), array_values(printfile::get_stations()));

$content_map->set_receiving_station = !printfile::station_is_set()?1:0;

$content_map->conditions = prepared_query::fetch("SELECT * FROM serials_configs WHERE type = 'condition' ORDER by text");
foreach ($content_map->conditions as &$condition) {
	if ($condition['text'] == 'Preowned') $condition['selected?'] = 1;
}

$testers = prepared_query::fetch('SELECT admin_id, admin_firstname, admin_lastname FROM admin WHERE admin_groups_id IN (6, 13, 22) AND status = 1', cardinality::SET, []);

foreach ($testers as $tester) {
	($_SESSION['perms']['admin_id'] == $tester['admin_id'])?$selected = 1:$selected = 0;
	$content_map->testers[] = ['admin_id' => $tester['admin_id'], 'admin_firstname' => $tester['admin_firstname'], 'admin_lastname' => $tester['admin_lastname'], 'selected?' => $selected];
}

$content_map->serial_dispositions = [['id' => 11, 'text' => 'Testing'], ['id' => 12, 'text' => 'Conditioning'], ['id' => 4, 'text' => 'Paint', 'selected?' => 1], ['id' => 14, 'text' => 'Phone Refurb'], ['id' => '19', 'text' => 'Phone']];
$content_map->nonserial_dispositions = [['id' => 12, 'text' => 'Conditioning']];

$cktpl->content('includes/templates/page-po-receiver.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
