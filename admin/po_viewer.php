<?php
require('includes/application_top.php');

$action = isset($_REQUEST['action'])?$_REQUEST['action']:NULL;
$poId = isset($_REQUEST['poId'])?$_REQUEST['poId']:NULL;

switch ($action) {
	case 'kill-allocation':
		$allocation_id = $_REQUEST['allocation_id'];
		if (!prepared_query::fetch('SELECT op.orders_id FROM orders_products op JOIN purchase_order_to_order_allocations potoa ON op.orders_products_id = potoa.order_product_id WHERE potoa.id = ?', cardinality::SINGLE, [$allocation_id])) prepared_query::execute('DELETE FROM purchase_order_to_order_allocations WHERE id = ?', [$allocation_id]);
		if ($__FLAG['ajax']) exit();
		else CK\redirect_and_exit('/admin/po_viewer.php?poId='.$poId);
		break;
	case 'auto-allocate':
		$po_status = prepared_query::fetch('SELECT po.status FROM purchase_orders po WHERE po.id = :po_id', cardinality::SINGLE, [':po_id' => $poId]);
		if (in_array($po_status, [1, 2]) && in_array($_SESSION['login_groups_id'], [1, 8, 28, 20, 7, 29, 9, 18, 17, 5, 10])) {
			if ($pop_products = prepared_query::fetch('SELECT pop.id as purchase_order_product_id, pop.ipn_id as stock_id, GREATEST(0, pop.quantity - IFNULL(porp.quantity_received, 0) - IFNULL(potoa.quantity, 0)) as outstanding_quantity FROM purchase_order_products pop JOIN purchase_orders po ON pop.purchase_order_id = po.id LEFT JOIN (SELECT porp.purchase_order_product_id, SUM(porp.quantity_received) as quantity_received FROM purchase_order_received_products porp JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id AND pop.purchase_order_id = :po_id) porp ON pop.id = porp.purchase_order_product_id LEFT JOIN (SELECT potoa.purchase_order_product_id, SUM(potoa.quantity) as quantity FROM purchase_order_to_order_allocations potoa JOIN purchase_order_products pop ON potoa.purchase_order_product_id = pop.id AND pop.purchase_order_id = :po_id) potoa ON pop.id = potoa.purchase_order_product_id WHERE pop.purchase_order_id = :po_id', cardinality::SET, [':po_id' => $poId])) {
				foreach ($pop_products as $pop_product) {
					$ipn = new ck_ipn2($pop_product['stock_id']);

					$salable = $ipn->get_inventory('salable');

					if ($pop_product['outstanding_quantity'] <= 0) continue;

					if ($order_products = prepared_query::fetch('SELECT op.orders_products_id, op.products_quantity - IFNULL(SUM(potoa.quantity), 0) as qty_needed_for_order FROM orders_products op JOIN orders o ON op.orders_id = o.orders_id AND o.orders_status NOT IN (3, 6, 9) JOIN products p ON op.products_id = p.products_id LEFT JOIN purchase_order_to_order_allocations potoa ON op.orders_products_id = potoa.order_product_id WHERE p.stock_id = :stock_id GROUP BY p.stock_id, op.orders_products_id, op.products_quantity HAVING op.products_quantity - IFNULL(SUM(potoa.quantity), 0) > 0 ORDER BY IFNULL(o.promised_ship_date, DATE_ADD(NOW(), INTERVAL 2 WEEK)) ASC, qty_needed_for_order DESC', cardinality::SET, [':stock_id' => $pop_product['stock_id']])) {
						foreach ($order_products as $order_product) {
							// if we have salable qtys, reduce our qty needed by the salable amount, up to our entire needed qty
							$usable_from_shelf = min($salable, $order_product['qty_needed_for_order']);
							$order_product['qty_needed_for_order'] -= $usable_from_shelf;
							// once we've used it, we can't use it again
							$salable -= $usable_from_shelf;
							$order_product['qty_needed_for_order'] = max(0, $order_product['qty_needed_for_order']);

							if ($order_product['qty_needed_for_order'] <= 0) continue;

							$allocate_qty = min($order_product['qty_needed_for_order'], $pop_product['outstanding_quantity']);
							prepared_query::execute('INSERT INTO purchase_order_to_order_allocations (purchase_order_product_id, order_product_id, quantity, modified, admin_id) VALUES (:purchase_order_product_id, :order_product_id, :quantity, NOW(), :admin_id)', [':purchase_order_product_id' => $pop_product['purchase_order_product_id'], ':order_product_id' => $order_product['orders_products_id'], ':quantity' => $allocate_qty, ':admin_id' => $_SESSION['login_id']]);

							$pop_product['outstanding_quantity'] -= $allocate_qty;

							if ($pop_product['outstanding_quantity'] <= 0) break;
						}
					}
				}
			}
		}
		if ($__FLAG['ajax']) exit();
		else CK\redirect_and_exit('/admin/po_viewer.php?poId='.$poId);
		break;
	case 'de-allocate':
		if (in_array($_SESSION['login_groups_id'], [1, 8, 28, 20, 7, 29, 9, 18, 17, 5, 10, 33])) {
			prepared_query::execute('DELETE FROM purchase_order_to_order_allocations WHERE id = :allocation_id', [':allocation_id' => $_REQUEST['allocation_id']]);
		}
		if ($__FLAG['ajax']) exit();
		else CK\redirect_and_exit('/admin/po_viewer.php?poId='.$poId);
		break;
	case 'add_note':
		//$noteText = addslashes($_POST['notes']);
		$po_note_id = prepared_query::insert('INSERT INTO purchase_order_notes (purchase_order_id, purchase_order_note_user, purchase_order_note_created, purchase_order_note_text) VALUES (:po_id, :admin_id, NOW(), :note)', [':po_id' => $poId, ':admin_id' => $_SESSION['login_id'], ':note' => $_POST['notes']]);

		// response data for display
		$admin_name = $_SESSION['perms']['admin_firstname'].' '.$_SESSION['perms']['admin_lastname'];
		$data = [
			'note' => nl2br($_POST['notes']),
			'created_on' => date('Y-m-d H:i:s'),
			'created_by' => $admin_name
		];

		$po = new ck_purchase_order($poId);
		// $po->send_new_po_note_notification($po_note_id);

		echo json_encode($data);
		exit();
		break;
	case 'packageTrackerFetch':
		$output = '';
		if ($trackings = prepared_query::fetch('SELECT pot.id, pot.tracking_number, pot.status, pot.tracking_method as tracking_method_id, pot.eta, sm.text as tracking_method, pot.bin_number, pot.complete FROM purchase_order_tracking pot LEFT JOIN purchase_order_shipping sm ON sm.id = pot.tracking_method WHERE pot.po_id = ?', cardinality::SET, array($poId))) {
			foreach ($trackings as $tracking) {
				if ($tracking['status'] == 1) {
					$status = 'Delivered';
				}
				else {
					$status = 'Not Delivered';
				}
				$output .= $tracking['tracking_number'].'/'.$tracking['tracking_method'].'/'.$tracking['tracking_method_id'].'/'.$tracking['eta'].'/'.$tracking['id'].'/'.$tracking['bin_number'].'/'.$status.'/'.$tracking['complete'].'__&__';
			}
		}
		echo $output;
		exit();
		break;
	case 'packageTrackerVoid':
		$tracking_id = $_GET['tracking_id'];
		prepared_query::execute('DELETE FROM purchase_order_tracking WHERE id = ?', array($tracking_id));
		print 'success';
		exit();
		break;
	case 'packageTrackerEdit':
		$tracking_id = $_GET['tracking_id'];
		$tracking_num = str_replace(' ', '', $_GET['tracking_number']);
		$tracking_method = $_GET['tracking_method'];
		$eta = $_GET['eta'];
		if ($_GET['tracking_status'] == 'on') {
			$status = 1;
		}
		else {
			$status = 0;
		}
		if ($_GET['tracking_complete'] == 'on') {
			$complete = 1;
		}
		else {
			$complete = 0;
		}
		$bin_number = !empty($_GET['bin_number'])?$_GET['bin_number']:NULL;
		prepared_query::execute('UPDATE purchase_order_tracking SET tracking_number = ?, tracking_method = ?, eta = ?, status = ?, bin_number = ?, complete = ?  WHERE id = ?', array($tracking_num, $tracking_method, $eta, $status, $bin_number, $complete, $tracking_id));
		print 'success';
		exit();
		break;
	case 'packageTrackerInsert':
		$tracking_num = str_replace(' ', '', $_GET['tracking_number']);
		$tracking_method = $_GET['tracking_method'];
		$eta = $_GET['eta'];
		if ($_GET['tracking_status'] == 'on') {
			$status = 1;
		}
		else {
			$status = 0;
		}
		if ($_GET['tracking_complete'] == 'on') {
			$complete = 1;
		}
		else {
			$complete = 0;
		}
		$bin_number = !empty($_GET['bin_number'])?$_GET['bin_number']:NULL;
		prepared_query::execute('INSERT INTO purchase_order_tracking (po_id, tracking_number, tracking_method, eta, status, bin_number, complete) VALUES (:po_id, :tracking_number, :tracking_method, :eta, :status, :bin_number, :complete) ON DUPLICATE KEY UPDATE tracking_method=:tracking_method, eta=:eta, status=:status, bin_number=:bin_number, complete=:complete', [':po_id' => $poId, ':tracking_number' => $tracking_num, ':tracking_method' => $tracking_method, ':eta' => $eta, ':status' => $status, ':bin_number' => $bin_number, ':complete' => $complete]);
		print 'success';
		exit();
		break;
	case 'complete_tracking':
		prepared_query::execute('UPDATE purchase_order_tracking SET complete = 1 WHERE id = ?', array($_POST['tracking_id']));
		CK\fn::redirect_and_exit('/admin/po_viewer.php?poId='.$poId);
		break;
	case 'update_submit_date':
		$submit_date = NULL;
		if ($_POST['submit_date'] != 'CLEAR') {
			$submit_date = date('Y-m-d', strtotime($_POST['submit_date']));
		}
		prepared_query::execute('UPDATE purchase_orders SET submit_date = ? WHERE id = ?', array($submit_date, $poId));
		exit();
		break;
	case 'update_expected_date':
		$expected_date = NULL;
		if ($_POST['expected_date'] != 'CLEAR') {
			$expected_date = date('Y-m-d', strtotime($_POST['expected_date']));
		}
		prepared_query::execute('UPDATE purchase_orders SET expected_date = ? WHERE id = ?', array($expected_date, $poId));
		exit();
		break;
	case 'update_followup_date':
		$followup_date = date('Y-m-d', strtotime($_POST['followup_date']));
		prepared_query::execute('UPDATE purchase_orders SET followup_date = ? WHERE id = ?', array($followup_date, $poId));
		exit();
		break;
	case 'adjust_review_qty':
		prepared_query::execute('UPDATE purchase_order_review_product SET qty_received = :qty WHERE id = :porp_id', [':qty' => $_REQUEST['new_qty'], ':porp_id' => $_REQUEST['porp_id']]);
		exit();
		break;
}

if ($__FLAG['ajax']) exit();

if (empty($poId)) {
	echo 'Purchase order ID was not set.';
	exit();
}

$po = new ck_purchase_order($poId);
$header = $po->get_header();
$admin_notes = $po->get_notes();
$po_products = $po->get_products();
$receiving_sessions = $po->get_receiving_sessions();
$mailer = service_locator::get_mail_service();

switch ($action) {
	case 'unreceive':
		$unreceive_porps = $_POST['unreceive_porps'];

		// first we look up the details of the receiving session
		$porsId = isset($_GET['porsId'])?$_GET['porsId']:NULL;

		// this used to be $pors, which didn't appear to be used anywhere, at all, for any reason, so I re-wrote it to be sane, and now I'm commenting it out
		//$receiving_session = prepared_query::fetch('SELECT * FROM purchase_order_receiving_sessions WHERE id = ?', cardinality::ROW, array($porsId));

		// this used to be $porps
		$received_products = prepared_query::fetch('SELECT porp.*, pop.ipn_id, pop.cost, psc.stock_name, psc.serialized FROM purchase_order_received_products porp JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id WHERE porp.receiving_session_id = ? AND porp.quantity_received > 0', cardinality::SET, array($porsId));

		// we used to look here to see if we're referencing all of the products to unreceive the whole session, or if we're just unreceiving a portion
		// now just assume it, unless in our loops through the products below we determine that there's still products attached to it
		$remove_session = TRUE;

		// remove products from $porps that are not getting unreceived
		foreach ($received_products as $i => &$product) {
			if (!empty($unreceive_porps[$product['id']])) {
				if (!empty($unreceive_porps[$product['id']]['noserial'])) {
					$product['unreceive'] = $unreceive_porps[$product['id']]['noserial'];
				}
				elseif (!empty($unreceive_porps[$product['id']]['missing-serials'])) {
					$product['unreceive'] = $unreceive_porps[$product['id']]['missing-serials'];
				}
				else {
					$product['unreceive'] = count($unreceive_porps[$product['id']]);
					$product['serials'] = $unreceive_porps[$product['id']];
				}
			}
			else {
				unset($received_products[$i]);
				$remove_session = FALSE;
			}
		}
		unset($product);

		$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);

		//remove or update porps in db
		foreach ($received_products as $key => $product) {
			$new_qty_rec = $product['quantity_received'] - $product['unreceive'];
			$new_qty_rem = $product['quantity_remaining'] + $product['unreceive'];

			//update porp
			if ($new_qty_rec > 0) {
				prepared_query::execute('INSERT INTO purchase_order_unreceive_products (stock_id, purchase_order_received_products_id, receiving_session_id, purchase_order_id, receive_date, receiver, unreceiver, notes, purchase_order_tracking_id, purchase_order_product_id, old_quantity_received, old_quantity_remaining, new_quantity_received, new_quantity_remaining, paid, entered) SELECT :stock_id, porp.id, porp.receiving_session_id, pors.purchase_order_id, pors.date, pors.receiver, :admin_id, pors.notes, pors.purchase_order_tracking_id, porp.id, porp.quantity_received, porp.quantity_remaining, :new_qty_rec, :new_qty_rem, porp.paid, porp.entered FROM purchase_order_received_products porp JOIN purchase_order_receiving_sessions pors ON porp.receiving_session_id = pors.id WHERE porp.id = :purchase_order_product_id', [':stock_id' => $product['ipn_id'], ':admin_id' => $user->id(), ':new_qty_rec' => $new_qty_rec, ':new_qty_rem' => $new_qty_rem, ':purchase_order_product_id' => $product['id']]);

				prepared_query::execute('UPDATE purchase_order_received_products SET quantity_received = ?, quantity_remaining = ? WHERE id = ?', array($new_qty_rec, $new_qty_rem, $product['id']));
				$remove_session = FALSE;
			}
			else {
				prepared_query::execute('INSERT INTO purchase_order_unreceive_products (stock_id, purchase_order_received_products_id, receiving_session_id, purchase_order_id, receive_date, receiver, unreceiver, notes, purchase_order_tracking_id, purchase_order_product_id, old_quantity_received, old_quantity_remaining, new_quantity_received, new_quantity_remaining, paid, entered) SELECT :stock_id, porp.id, porp.receiving_session_id, pors.purchase_order_id, pors.date, pors.receiver, :admin_id, pors.notes, pors.purchase_order_tracking_id, porp.id, porp.quantity_received, porp.quantity_remaining, 0, 0, porp.paid, porp.entered FROM purchase_order_received_products porp JOIN purchase_order_receiving_sessions pors ON porp.receiving_session_id = pors.id WHERE porp.id = :purchase_order_product_id', [':stock_id' => $product['ipn_id'], ':admin_id' => $user->id(), ':purchase_order_product_id' => $product['id']]);

				prepared_query::execute('DELETE FROM purchase_order_received_products WHERE id = ?', array($product['id']));
			}

			//remove serials
			if (!empty($product['serials'])) {
				foreach ($product['serials'] as $serial => $on) {
					$serial_id = prepared_query::fetch('SELECT id FROM serials WHERE serial = ?', cardinality::SINGLE, array($serial));
					prepared_query::execute('DELETE FROM serials_history WHERE serial_id = ? and pors_id = ? AND order_product_id IS NULL ORDER BY id DESC LIMIT 1', array($serial_id, $porsId));
					prepared_query::execute('DELETE FROM inventory_hold WHERE serial_id = :serial_id', [':serial_id' => $serial_id]);

					//we might need to add logic for this later for rma's and such
					prepared_query::execute('DELETE FROM serials WHERE id = ? AND id NOT IN (SELECT serial_id FROM serials_history WHERE serial_id = ?) ORDER BY id DESC LIMIT 1', array($serial_id, $serial_id));
					// if it's still there, set it to invoiced because there was a previous sale record before this current receipt
					prepared_query::execute('UPDATE serials SET status = 4 WHERE id = ?', array($serial_id));
				}
			}

			// manage on hand quantity, on order quantity, and average cost
			$ipn = prepared_query::fetch('SELECT stock_quantity, on_order, average_cost FROM products_stock_control WHERE stock_id = ?', cardinality::ROW, array($product['ipn_id']));

			$new_stock_quantity = $ipn['stock_quantity'] - $product['unreceive'];
			$new_on_order = $ipn['on_order'] + $product['unreceive'];
			$new_average_cost = 0;
			if ($new_stock_quantity > 0) {
				$new_average_cost = round((($ipn['average_cost']*$ipn['stock_quantity']) - ($product['cost']*$product['unreceive'])) / $new_stock_quantity, 2);
			}

			prepared_query::execute('UPDATE products_stock_control SET average_cost = ?, stock_quantity = ?, on_order = ? where stock_id = ?', array($new_average_cost, $new_stock_quantity, $new_on_order, $product['ipn_id']));
			prepared_query::execute('UPDATE products p, products_stock_control psc SET p.products_quantity = psc.stock_quantity WHERE p.stock_id = psc.stock_id and psc.stock_id = ?', array($product['ipn_id']));
			add_ipn_change_history('po', $product['purchase_order_product_id'], $_SESSION['login_id'], 0, $product['ipn_id'], $product['unreceive']);
			insert_psc_change_history($product['ipn_id'], 'PO Unreceive', $ipn['stock_quantity'], $new_stock_quantity);
			insert_psc_change_history($product['ipn_id'], 'Unreceive Cost Change', $ipn['average_cost'], $new_average_cost);
		}
		// check to see if there are any received products left, if not remove all of the receiving sessions
		if (!empty($remove_session)) {
			prepared_query::execute('DELETE FROM purchase_order_receiving_sessions WHERE id = ?', array($porsId));
			// the review status reset doesn't seem to be necessary, and I'm guessing it's not desired
			//prepared_query::execute('UPDATE purchase_order_review SET status = 0 WHERE po_number = ?', array($poId));
		}

		//now we insert a note into the purchase_order_notes table
		$receiver = $_SESSION['login_id'];
		if ($remove_session)
			$notes = "Unreceived a receiving session for the following items:\n";
		else
			$notes = "Modified a receiving session for the following items:\n";

		foreach ($received_products as $i => $product) {
			$notes .= '	'.$product['stock_name'].' - '.$product['quantity_received'].' at $'.number_format($product['cost'], 2)."\n";
		}
		prepared_query::execute('INSERT INTO purchase_order_notes(purchase_order_id, purchase_order_note_user, purchase_order_note_created, purchase_order_note_text) VALUES (?, ?, NOW(), ?)', array($poId, $receiver, $notes));

		//Now we need to send a email notifying about the unreceive
		$vendor = prepared_query::fetch('SELECT v.vendors_company_name FROM purchase_orders po LEFT JOIN vendors v ON po.vendor = v.vendors_id WHERE po.id = :po_id', cardinality::SINGLE, [':po_id' => $poId]);
        $mail = $mailer->create_mail()
            ->set_from('purchasing@cablesandkits.com', 'CablesAndKits.com');
		if (!empty($remove_session)) {
			$mail->set_subject('A receiving Session for '.$vendor.' Purchase Order: '.$poId.' has been Unreceived');
		} else {
			$mail->set_subject('A receiving Session for '.$vendor.' Purchase Order: '.$poId.' Has been Modified');
		}
		$mail->add_to('accounting@cablesandkits.com');
		$message = "Receiving Session $porsId has been Unreceived or Modified for the following Items:\n\n";
		$message .= $notes;

		//send the email
		$mail->set_body($message);
		$mailer->send($mail);

		// if we've removed some products ($received_products), but we've still received some as well ($still_received), we're now partially received
		if ($received_products && ($still_received = prepared_query::fetch('SELECT pop.id, pop.quantity, SUM(porp.quantity_received) as quantity_received FROM purchase_order_products pop JOIN purchase_order_received_products porp on pop.id = porp.purchase_order_product_id WHERE pop.purchase_order_id = ? GROUP BY pop.id', cardinality::SET, array($poId)))) {
			prepared_query::execute('UPDATE purchase_orders SET status = 2 WHERE id = ?', array($poId));
		}
		// else, if we've removed some products and there are no still received products, we're now completely open
		elseif (!empty($received_products)) {
			prepared_query::execute('UPDATE purchase_orders SET status = 1 WHERE id = ?', array($poId));
		}
		// otherwise, we haven't removed any products (which would be weird), so don't update anything

		CK\fn::redirect_and_exit('po_viewer.php?poId='.$poId);
		break;
	case 'email':
		$vendor_email = prepared_query::fetch('SELECT v.vendors_email_address FROM purchase_orders po LEFT JOIN vendors v on po.vendor = v.vendors_id WHERE po.id = :po_id', cardinality::SINGLE, [':po_id' => $poId]);

        ob_start();
		$_GET['confirmation'] = 'yes';
		include('po_printable.php');
		$message = ob_get_clean();

        $mail = $mailer->create_mail()
		    ->set_from('purchasing@cablesandkits.com', 'CablesAndKits.com')
		    ->set_subject('Purchase Order: '.$poId)
		    ->add_to($vendor_email)
            ->set_body($message)
            ->create_attachment($message, 'CablesAndKits.com_PO_'.$poId.'.html');

		$mailer->send($mail);

		prepared_query::execute('UPDATE purchase_orders SET submit_date = NOW() WHERE id = :purchase_order_id', [':purchase_order_id' => $poId]);

		CK\fn::redirect_and_exit('po_viewer.php?poId='.$poId);
		break;
	case 'add_additional_cost':
		$additional_cost_details = [
			'purchase_order_id' => $poId,
			'description' => $_POST['additional_cost_description'],
			'amount' => $_POST['additional_cost_amount']
		];

		$po->create_additional_cost($additional_cost_details);
		CK\fn::redirect_and_exit('po_viewer.php?poId='.$poId);
		break;
	case 'update_additional_cost':
		$additional_cost_details = [
			'purchase_order_additional_cost_id' => $_GET['purchase_order_additional_cost_id'],
			'purchase_order_id' => $poId,
			'description' => $_POST['additional_cost_description'],
			'amount' => $_POST['additional_cost_amount']
		];

		$po->update_additional_cost($additional_cost_details);
		CK\fn::redirect_and_exit('po_viewer.php?poId='.$poId);
		break;
	case 'spread_additional_cost':
		if (!empty($_GET['additional_cost_id'])) $po->spread_additional_cost($_GET['additional_cost_id']);
		CK\fn::redirect_and_exit('po_viewer.php?poId='.$poId);
		break;
	case 'unspread_additional_cost':
		$po->unspread_additional_cost($_GET['additional_cost_id']);
		CK\fn::redirect_and_exit('po_viewer.php?poId='.$poId);
		break;
	case 'remove_additional_cost':
		$po->remove_additional_cost($_GET['additional_cost_id']);
		CK\fn::redirect_and_exit('po_viewer.php?poId='.$poId);
		break;
	default:
		break;
}

$allocations = $po->get_allocations();
$pop_allocations = [];
foreach ($allocations as $allocation) {
	if (!isset($pop_allocations[$allocation['po_product_id']])) $pop_allocations[$allocation['po_product_id']] = [];
	$pop_allocations[$allocation['po_product_id']][] = $allocation;
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script type="text/javascript" src="/includes/javascript/jquery-1.4.2.min.js"></script>
	<script src="/images/static/js/ck-styleset.js"></script>
	<script src="/images/static/js/ck-ajaxify.max.js"></script>
	<script src="/images/static/js/ck-button-links.max.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php require(DIR_WS_INCLUDES.'header.php'); ?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#add-note-button').click(function(event) {
			var poId = $('#poId').val();
			var data = $('#notes-form').serialize();
			data += '&action=add_note';
			data += '&id=' + poId;

			$.post('po_viewer.php', data, function(data) {
				$('#notes').val('');
				$('#notes-display').append('<tr><td>' + data.created_on + '</td><td>' + data.created_by + '</td><td>' + data.note + '</td></tr>');
			}, 'json');
		});

		$('#packageTrackerInsertEta').datepicker({ dateFormat: 'yy-mm-dd' });

		$('#edit_submit-date').click(function(event) {
			event.preventDefault();
			var buttonContainer = $(this).parent();
			buttonContainer.hide();
			var val = $('#submit-date-container').html();
			$('#submit-date-container').html('<input id="submit-date" type="text" value="">');
			$('#submit-date').val(val);
			$('#submit-date').datepicker({
				dateFormat: 'mm/dd/yy',
				onSelect: function(dateText, instance) {
					$.post(
						'po_viewer.php',
						{ action: 'update_submit_date', poId: $('#po-id-hidden').val(), submit_date: dateText },
						function(data) {
							if (data.error) {
								alert(data.error);
								return;
							}
						},
						"json"
					);

					$('#submit-date-container').html(dateText);
					buttonContainer.show();
				}
			});
		});

		$('#clear_submit-date').click(function(event) {
			event.preventDefault();
			$.post(
				'po_viewer.php',
				{ action: 'update_submit_date', poId: $('#po-id-hidden').val(), submit_date: 'CLEAR' },
				function(data) {
					if (data.error) {
						alert(data.error);
						return;
					}
				},
				"json"
			);

			$('#submit-date-container').html('');
		});

		$('#edit_expected-date').click(function(event) {
			event.preventDefault();
			var buttonContainer = $(this).parent();
			buttonContainer.hide();
			var val = $('#expected-date-container').html();
			$('#expected-date-container').html('<input id="expected-date" type="text" value="">');
			$('#expected-date').val(val);
			$('#expected-date').datepicker({
				dateFormat: 'mm/dd/yy',
				minDate: +0,
				onSelect: function(dateText, instance) {
					$.post(
						'po_viewer.php',
						{ action: 'update_expected_date', poId: $('#po-id-hidden').val(), expected_date: dateText },
						function(data) {
							if (data.error) {
								alert(data.error);
								return;
							}
						},
						"json"
					);

					$('#expected-date-container').html(dateText);
					buttonContainer.show();
				}
			});
		});

		$('#clear_expected-date').click(function(event) {
			event.preventDefault();
			$.post(
				'po_viewer.php',
				{ action: 'update_expected_date', poId: $('#po-id-hidden').val(), expected_date: 'CLEAR' },
				function(data) {
					if (data.error) {
						alert(data.error);
						return;
					}
				},
				"json"
			);

			$('#expected-date-container').html('');
		});

		$('#edit_followup-date').click(function(event) {
			event.preventDefault();
			var buttonContainer = $(this).parent();
			buttonContainer.hide();
			var val = $('#followup-date-container').html();
			$('#followup-date-container').html('<input id="followup-date" type="text" value="">');
			$('#followup-date').val(val);
			$('#followup-date').datepicker({
				dateFormat: 'mm/dd/yy',
				minDate: +0,
				onSelect: function(dateText, instance) {
					$.post(
						'po_viewer.php',
						{ action: 'update_followup_date', poId: $('#po-id-hidden').val(), followup_date: dateText },
						function(data) {
							if (data.error) {
								alert(data.error);
								return;
							}
						},
						"json"
					);

					$('#followup-date-container').html(dateText);
					buttonContainer.show();
				}
			});
		});
	});
</script>
<!-- header_eof //-->
<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
	<tr>
		<td width="<?= BOX_WIDTH; ?>" valign="top">
			<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
				<!-- left_navigation //-->
				<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
				<!-- left_navigation_eof //-->
			</table>
		</td>
		<!-- body_text //-->
		<td width="100%">
			<input type="hidden" id="po-id" value="<?= $po->id(); ?>">
			<a href="/admin/po_list.php" class="button-link">&lt;&lt; Back to PO List</a>
			<?php if (in_array($header['po_status_id'], [1, 2])) { ?>
				<a href="/admin/po_editor.php?action=edit&poId=<?= $po->id(); ?>" class="button-link">Edit</a>
			<?php } ?>
			<a href="/admin/po_printable.php?poId=<?= $po->id(); ?>" class="button-link">View Printable PO</a>
			<a href="/admin/po_viewer.php?action=email&poId=<?= $po->id(); ?>" class="button-link email-to-vendor" data-ve="<?= $header['vendors_email_address']; ?>">Email to <?= $header['vendors_email_address']; ?></a>
			<script>
				jQuery('.email-to-vendor').click(function(e) {
					if (!confirm('Are you sure you want to email this PO to '+jQuery(this).attr('data-ve'))) {
						e.preventDefault();
						return false;
					}
				});
			</script>
			<a href="/admin/po_receiver.php?poId=<?= $po->id(); ?>" class="button-link" target="_blank">Receive</a>
			<br><br>

			<table cellspacing="3px" cellpadding="3px">
				<tr>
					<td class="main"><b>PO Number:</b>&nbsp;&nbsp;<?= $header['purchase_order_number']; ?></td>
				</tr>
				<tr>
					<td class="main"><b>Generated As:</b>&nbsp;&nbsp;<?= $header['entity_name']; ?></td>
				</tr>
				<tr>
					<td class="main"><b>Status:</b>&nbsp;&nbsp;<?= $header['po_status']; ?></td>
				</tr>
				<tr>
					<td class="main">&nbsp;</td>
				</tr>
				<tr>
					<td class="main"><b>Vendor:</b>&nbsp;&nbsp;<?= $header['vendor']; ?></td>
				</tr>
				<tr>
					<td class="main"><b>Confirmation:</b>&nbsp;&nbsp;
						<?php if ($header['confirmation_status_id'] == 0) echo 'Not requested';
						elseif ($header['confirmation_status_id'] == 1) echo 'Requested';
						elseif ($header['confirmation_status_id'] == 2) echo 'Received';
						else echo 'Bad data - please contact Dev with and provide the PO number'; ?>
					</td>
				</tr>
				<tr>
					<td class="main">&nbsp;</td>
				</tr>
				<tr>
					<td class="main"><strong>Created By:</strong> <?= $header['creator']->get_name(); ?></td>
				</tr>
				<tr>
					<td class="main"><strong>Administered By:</strong> <?= $header['administrator']->get_name(); ?></td>
				</tr>
				<tr>
					<td class="main"><strong>Owned By:</strong> <?= $header['owner']->get_name(); ?></td>
				</tr>
				<?php if (!empty($header['buyback_admin'])) { ?>
				<tr>
					<td class="main"><strong>Buyback Admin:</strong> <?= $header['buyback_admin']->get_name(); ?></td>
				</tr>
				<?php } ?>
				<tr>
					<td class="main"><b>Created:</b>&nbsp;&nbsp;<?= $header['creation_date']->format('m/d/Y'); ?></td>
				</tr>
				<tr>
					<td class="main">&nbsp;</td>
				</tr>
				<tr>
					<td class="main"><b>Dropship Fulfillment:</b>&nbsp;&nbsp;<?= $header['drop_ship']?'Yes':'No'; ?></td>
				</tr>
				<tr>
					<td class="main">&nbsp;</td>
				</tr>
				<tr>
					<td class="main"><b>Shipping method:</b>&nbsp;&nbsp;<?= $header['po_shipping_method']; ?></td>
					<input type="hidden" id="main_shipping_method_id" value="<?= $header['po_shipping_method_id']; ?>">
				</tr>
				<tr>
					<td class="main">
						<table>
							<tr>
								<td class="main"><b>Submitted:</b>&nbsp;&nbsp;</td>
								<td class="main" id="submit-date-container"><?= !empty($header['submit_date'])?$header['submit_date']->format('m/d/Y'):''; ?></td>
								<td class="main">
									<a id="edit_submit-date" class="edit" href="#"><img border="0" src="images/table_edit.png" alt="Edit"></a>
									<input type="hidden" name="po-id-hidden" id="po-id-hidden" value="<?= $po->id(); ?>">
									<a id="clear_submit-date" class="edit" href="#">clear</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="main">
						<table>
							<tr>
								<td class="main"><b>Expected:</b>&nbsp;&nbsp;</td>
								<td class="main" id="expected-date-container"><?= !empty($header['expected_date'])?$header['expected_date']->format('m/d/Y'):''; ?></td>
								<td class="main">
									<a id="edit_expected-date" class="edit" href="#"><img border="0" src="images/table_edit.png" alt="Edit"></a>
									<input type="hidden" name="po-id-hidden" id="po-id-hidden" value="<?= $po->id(); ?>">
									<a id="clear_expected-date" class="edit" href="#">clear</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="main">
						<table>
							<tr>
								<td class="main"><b>Followup:</b>&nbsp;&nbsp;</td>
								<td class="main" id="followup-date-container"><?= !empty($header['followup_date'])?$header['followup_date']->format('m/d/Y'):''; ?></td>
								<td class="main"><a id="edit_followup-date" class="edit" href="#"><img border="0" src="images/table_edit.png" alt="Edit"></a></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="main">&nbsp;</td>
				</tr>
				<tr>
					<td class="main"><b>Terms:</b>&nbsp;&nbsp;<?= $header['po_terms']; ?></td>
				</tr>
				<tr>
					<td class="main">&nbsp;</td>
				</tr>
				<!--tr>
					<td class="main">
						<style>
							#auto-allocate-results { position:absolute; display:none; }
						</style>
						<form action="/admin/index.php" method="post" id="auto-allocate" class="ajax">
							<input type="hidden" name="action" value="auto-allocate-from-po">
							<input type="hidden" name="model" value="ck_purchase_order">
							<input type="submit" value="Auto Allocate To Orders">
						</form>
						<div id="auto-allocate-results"></div>
						<script>
							jQuery('form.ajax').submit(function(e) {
								e.preventDefault();

								jQuery.ajax({
									url: jQuery(this).attr('action'),
									dataType: 'json',
									type: jQuery(this).attr('method').toUpperCase(),
									data: jQuery(this).serialize(),
									success: function(data, textStatus, jqXHR) {
										console.log(data);
									}
								});
							});
						</script>
					</td>
				</tr-->
			</table>
			<br><br>

			<style>
				#po_lines_table th, #po_lines_table td { padding:5px; }
				/*.po_line td { border-bottom:1px dotted #000; }*/
				.po_line:hover td { background-color:#ffc; }
				.po_line.unexpected td { background-color:#fcc; }
				.po_line.unexpected:hover td { color:#fff; background-color:#c00; }
				.po_line.unexpected:hover .item_popup:not(.pop) .spcr { color:#fff; }
			</style>
			<table cellspacing="0" cellpadding="0" width="90%" id="po_lines_table">
				<tr>
					<td class="main"><b>IPN</b></td>
					<td class="main"><b>Description</b></td>
					<?php if ($po->is('show_vendor_pn')) { ?>
						<td class="main"><b>Vendor P/N</b></td>
					<?php } ?>
					<td class="main" align="right" nowrap><b>Weight</b></td>
					<td class="main" align="right" nowrap><b>Ordered</b></td>
					<td class="main" align="right" nowrap><b>Received</b></td>
					<td class="main" align="right" nowrap><b>Remaining</b></td>
					<td class="main" align="right" nowrap><b>On Order</b></td>
					<td class="main" align="right" nowrap><b>Allocations</b></td>
					<td class="main" align="right" nowrap><b>Cost</b></td>
					<td class="main" align="right" nowrap><b>Ext Weight</b></td>
					<td class="main" align="right" nowrap><b>Total</b></td>
					<td class="main" align="right" nowrap><b>Amount<br>Received</b></td>
					<td class="main" align="right" nowrap><b>Amount<br>Remaining</b></td>
					<td class="main"><b>Additional Cost</b></td>
				</tr>
				<?php foreach ($po_products as $product) {
					$total_allocated = 0;
					if (!empty($pop_allocations[$product['po_product_id']])) {
						foreach ($pop_allocations[$product['po_product_id']] as $allocation) {
							$total_allocated += $allocation['quantity'];
						}
					} ?>
					<tr class="po_line <?= CK\fn::check_flag($product['unexpected'])?'unexpected':''; ?>">
						<td class="main"><?= (new item_popup($product['ipn']->get_header('ipn'), service_locator::get_db_service(), ['stock_id' => $product['stock_id']])); ?></td>
						<td class="main">
							<?= $product['description']; ?>
							<?php if (CK\fn::check_flag($product['unexpected'])) { ?>
								<br>UNEXPECTED
							<?php } ?>
						</td>
						<?php if ($po->is('show_vendor_pn')) { ?>
							<td class="main" align="left"><?= $product['vendors_pn']; ?></td>
						<?php } ?>
						<td class="main" align="right"><?= $product['ipn']->get_header('stock_weight'); ?></td>
						<td class="main" align="right"><?= $product['quantity']; ?></td>
						<td class="main" align="right"><?= $product['qty_received']; ?></td>
						<td class="main" align="right"><?= $product['qty_remaining']; ?></td>
						<td class="main" align="right"><?= $product['ipn']->get_header('on_order'); ?></td>
						<td class="main" align="right">
							<a href="#" id="allocated_<?= $product['po_product_id']; ?>" class="show_allocation"><?= $total_allocated; ?></a>
							<div id="allocated_<?= $product['po_product_id']; ?>_details" class="allocation_details" style="display:none; position:absolute; padding:4px 7px; background-color:#eee; border:1px solid #000; text-align:left;">
								<?php if (!empty($pop_allocations[$product['po_product_id']])) {
									foreach ($pop_allocations[$product['po_product_id']] as $allocation) { ?>
										<pre style="display:inline;">[<?= str_pad($allocation['quantity'], 3, '0', STR_PAD_LEFT); ?>]</pre>
										<?php if (!empty($allocation['orders_id'])) { ?>
											<a href="/admin/orders_new.php?oID=<?= $allocation['orders_id']; ?>&action=edit" target="_blank"><?= $allocation['orders_id']; ?></a><br>
										<?php }
										else { ?>
											<a href="#<?= $allocation['po_allocation_id']; ?>" class="kill-allocation">[ERROR-REM]</a><br>
										<?php } ?>
									<?php }
								} ?>
							</div>
						</td>
						<td class="main" align="right"><?= CK\text::monetize($product['cost'], 4); ?></td>
						<td class="main" align="right"><?= $product['ipn']->get_header('stock_weight') * $product['quantity']; ?></td>
						<td class="main" align="right"><?= CK\text::monetize($product['quantity'] * $product['cost'], 4); ?></td>
						<td class="main" align="right"><?= CK\text::monetize($product['qty_received'] * $product['cost'], 4); ?></td>
						<td class="main" align="right"><?= CK\text::monetize($product['qty_remaining'] * $product['cost'], 4); ?></td>
						<td class="main" align="center"><?php if ($product['additional_cost'] == 1) { ?>&#10004;<?php } ?></td>
				</tr>
				<?php } ?>
				<tr>
					<td class="main" colspan="<?= $po->is('show_vendor_pn')?10:9; ?>">&nbsp;</td>
					<td class="main" align="right" style="border-top: 1px solid black;"><b><?= number_format($po->get_total_weight(), 2); ?></b></td>
					<td class="main" align="right" style="border-top: 1px solid black;"><b><?= CK\text::monetize($po->get_total_product_cost()); ?></b></td>
					<td class="main" align="right" style="border-top: 1px solid black;"><b><?= CK\text::monetize($po->get_total_received_cost()); ?></b></td>
					<td class="main" align="right" style="border-top: 1px solid black;"><b><?= CK\text::monetize($po->get_total_remaining_cost()); ?></b></td>
				</tr>
			</table>
			<script>
				jQuery('.show_allocation').click(function(e) {
					e.preventDefault();
					jQuery('#'+jQuery(this).attr('id')+'_details').toggle();
					return false;
				});
				jQuery('.allocation_details').click(function() {
					jQuery(this).toggle();
				});
				jQuery('body').click(function() {
					jQuery('.allocation_details').hide();
				});
				jQuery('.kill-allocation').click(function(e) {
					e.preventDefault();

					jQuery.ajax({
						url: '/admin/po_viewer.php?ajax=1&action=kill-allocation',
						type: 'POST',
						dataType: 'json',
						data: { allocation_id: jQuery(this).attr('href').replace(/#/, '') },
						complete: function() { window.location.reload(true); }
					});

					return false;
				});
			</script>
			<?php $shipping_methods = prepared_query::fetch('SELECT id as shipping_code, text as name FROM purchase_order_shipping ORDER BY id', cardinality::SET); ?>
			<style>
				.rowhdr { background-color: white; border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-right: 1px solid #000000; font-family: calibri,arial,sans-serif; font-size: 11pt; padding: 5px; text-align: center; vertical-align: top; font-weight: bold; }
				.row1 { background-color: #D8D8D8; border-bottom: 1px solid #000000; border-right: 1px solid #000000; font-family: calibri,arial,sans-serif; font-size: 11pt; padding: 2px; text-align: center; vertical-align: top; }
				.row2 { background-color: white; border-bottom: 1px solid #000000; border-right: 1px solid #000000; font-family: calibri,arial,sans-serif; font-size: 11pt; padding: 2px; text-align: center; vertical-align: top; }
				#packageEditDiv { display: none; background-color: #FFFFFF; position: absolute; border: 1px double #000000; height: 250px; width: 400px; top: 700px; }
				#packageEditDiv table tr td { text-align: right; font-family: calibri,arial,sans-serif; font-size: 11pt; font-weight: bold; vertical-align: top; }
				#packageEditClose { text-align: right; color: #15489E; font-size: 10px; font-weight: normal; text-decoration: none; font-family: Verdana,Arial,sans-serif; }
				.packageLinks, .packageLinks:visited, .packageLinks:link { color: #0000FF; font-size: 11pt; text-decoration: underline; }
				.tool-column { margin: 50px 0; width:100%; }
				.tool { display:inline-block; height:100%; box-sizing:border-box; width:49%; margin:0; }
				/* additional cost table design*/
				.additional-costs-table { font-size:12px; width:100%; }
				.additional-costs-table th { font-weight:bold; }
				.additional-costs-table, .additional-costs-table tr, .additional-costs-table td, .additional-costs-table th { border-bottom: 1.5px solid #ccc; border-collapse:collapse; }
				.additional-costs-table thead tr:first-child { font-size: 20px; border:none; }
				.additional-costs-table tr:nth-child(odd) { background-color: aliceblue; }
				.additional-costs-table td, .table th { padding: 5px; text-align:center; }
				.additional-costs-table thead tr th { background-color:#fff; }
				#open-additional-cost-modal-button { width:30%; float:right; margin:10px auto; cursor:pointer; height:25px; }
			</style>
			<div class="tool-column">
				<div class="main tool tracking-number-table">
					<a class="packageLinks" id="packageHelperRowLinkInsert">Add Tracking Number</a>
					<div id="packageEditDiv">
						<div style="text-align: right">
							<a id="packageEditClose" onclick="jQuery('#packageEditDiv').toggle()">[close]</a>
						</div>
						<center>
							<table>
								<tr><td colspan="2"><br><br></td></tr>
								<tr>
									<td>Tracking #:</td>
									<td><input id="packageTrackerInsertNumber" type="text"></td>
									<input id="packageTrackerInsertId" type="hidden">
								</tr>
								<tr>
									<td>Method</td>
									<td>
										<select id="packageTrackerInsertMethod">
											<?php foreach ($shipping_methods as $shipping_method) { ?>
												<option value="<?= $shipping_method['shipping_code']; ?>"><?= $shipping_method['name']; ?></option>
											<?php } ?>
										</select>
									</td>
								</tr>
								<tr>
									<td>ETA</td>
									<td><input id="packageTrackerInsertEta" name="packageTrackerInsertEta" type="text"></td>
								</tr>
								<tr>
									<td>Delivered</td>
									<td><input id="packageTrackerInsertStatus" name="packageTrackerInsertStatus" type="checkbox"></td>
								</tr>
								<tr>
									<td>Bin #</td>
									<td><input id="packageTrackerInsertBin" name="packageTrackerInsertBin" type="text"></td>
								</tr>
								<tr>
									<td>Complete</td>
									<td><input id="packageTrackerInsertComplete" name="packageTrackerInsertComplete" type="checkbox"></td>
								</tr>
								<tr>
									<td colspan="2"><center><input id="packageTrackerInsertSubmit" type="submit" value="Save"></center></td>
								</tr>
							</table>
						</center>
					</div>
					<table border="0" cellspacing="0" cellpadding="0" id="packagePage">
						<tbody>
						<tr>
							<td class="rowhdr" style="border-left: 1px solid #000000">Tracking #</td>
							<td class="rowhdr">Method</td>
							<td class="rowhdr">ETA</td>
							<td class="rowhdr">Action</td>
							<td class="rowhdr">Status</td>
							<td class="rowhdr">Bin #</td>
							<td class="rowhdr">Complete</td>
						</tr>
						</tbody>
					</table>
					<script>
						function packageHelper() {
							jQuery.ajax({
								url: '/admin/po_viewer.php',
								data: 'action=packageTrackerFetch&poId='+jQuery('#po-id').val(),
								success: function(response) {
									packageHelper2(response);
								}
							});
						}
						function packageHelper2(response) {
							jQuery('.packageHelperRow').remove();

							var rows = response.split('__&__');
							var i = 0;
							while (true) {
								row = rows[i];
								if (row == null || row == '') break;

								var items = row.split('/');

								var oddClass = '';

								if (i%2 == 0) oddClass = 'row1';
								else oddClass = 'row2';

								var shippingTrackLink = '';
								var shippingCarrier = items[1].split(' ')[0];

								if (shippingCarrier == 'FedEx') shippingTrackLink = '<a onclick="window.open(this.href,\'nw\');return false;" href="http://www.fedex.com/Tracking/Detail?ascend_header=1&totalPieceNum=&clienttype=dotcom&cntry_code=us&tracknumber_list='+items[0]+'&language=english&trackNum='+items[0]+'&pieceNum" class="packageLinks">Track</a>';
								else if (shippingCarrier == 'UPS') shippingTrackLink = '<a onclick="window.open(this.href,\'nw\');return false;" href="http://www.ups.com/WebTracking/processInputRequest?loc=en_US&amp;Requester=NOT&amp;tracknum='+items[0]+'&amp;AgreeToTermsAndConditions=yes" class="packageLinks shiplnk">Track</a>';
								else if (shippingCarrier == 'USPS') shippingTrackLink = '<a onclick="window.open(this.href,\'nw\');return false;" href="http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum='+items[0]+'">Track</a>';

								//determine if the tracking number has been completed and set the variable accordingly
								var complete_button = '';
								var complete_tracking = items[7];

								if (complete_tracking == 0) complete_button = '<form action="/admin/po_viewer.php" method="post"><input type="hidden" name="poId" value="'+jQuery('#po-id').val()+'"><input type="hidden" name="action" value="complete_tracking"><input type="hidden" name="tracking_id" value="'+items[4]+'"><input type="submit" value="complete"></form>';
								else complete_button = 'Completed';

								jQuery('#packagePage tr:last').after(
									'<tr class="packageHelperRow">'+
									'<td class="'+oddClass+'" style="border-left: 1px solid #000000">'+items[0]+'&nbsp;&nbsp;'+shippingTrackLink+'</td>'+
									'<td class="'+oddClass+'">'+items[1]+'&nbsp;&nbsp;</td>'+
									'<td class="'+oddClass+'">'+items[3]+'&nbsp;&nbsp;</td>'+
									'<td class="'+oddClass+'"><a id="packageTracking_'+items[4]+'" class=" packageLinks packageHelperRowLinkVoid">Delete</a>&nbsp;<a id="packageTracking_'+items[4]+'" class=" packageLinks packageHelperRowLinkEdit">Edit</a></td>'+
									'<td class="'+oddClass+'">'+items[6]+'&nbsp;&nbsp;</td>'+
									'<td class="'+oddClass+'">'+items[5]+'</td>'+
									'<input type="hidden" id="packageTrackingId_'+items[4]+'" value="'+items[4]+'">'+
									'<input type="hidden" id="packageTrackingNumber_'+items[4]+'" value="'+items[0]+'">'+
									'<input type="hidden" id="packageTrackingMethod_'+items[4]+'" value="'+items[2]+'">'+
									'<input type="hidden" id="packageTrackingEta_'+items[4]+'" value="'+items[3]+'">'+
									'<input type="hidden" id="packageTrackingStatus_'+items[4]+'" value="'+items[6]+'">'+
									'<input type="hidden" id="packageTrackingComplete_'+items[4]+'" value="'+items[7]+'">'+
									'<input type="hidden" id="packageTrackingBin_'+items[4]+'" value="'+items[5]+'">'+
									'<td class="'+oddClass+'">'+complete_button+'</td>'+
									'</tr>'
								);
								i++;
							}
						}
						jQuery(document).ready(function($) {
							packageHelper();

							$('#packageHelperRowLinkInsert').click(function(event) {
								var position = $(this).position();
								$('#packageTrackerInsertMethod').val($('#main_shipping_method_id').val());
								$('#packageEditDiv').css('top', (position.top-75));
								$('#packageEditDiv').toggle();
								$('#packageTrackerInsertNumber').select();
							});

							$('.packageHelperRowLinkEdit').live('click', function(event) {
								event.preventDefault();
								packageEdit = true;
								var id = this.id.split('_')[1];
								$('#packageTrackerInsertId').val($('#packageTrackingId_'+id).val());
								$('#packageTrackerInsertNumber').val($('#packageTrackingNumber_'+id).val());
								$('#packageTrackerInsertMethod').val($('#packageTrackingMethod_'+id).val());
								$('#packageTrackerInsertEta').val($('#packageTrackingEta_'+id).val());

								if ($('#packageTrackingStatus_'+id).val() == 'Delivered') $('#packageTrackerInsertStatus').attr('checked','true');
								else $('#packageTrackerInsertStatus').removeAttr('checked');

								$('#packageTrackerInsertBin').val($('#packageTrackingBin_'+id).val());

								if ($('#packageTrackingComplete_'+id).val() == 1) $('#packageTrackerInsertComplete').attr('checked','true');
								else $('#packageTrackerInsertComplete').removeAttr('checked');

								$('#packageEditDiv').toggle();
								var position = $(this).position();
								$('#packageEditDiv').css('top', (position.top-75));
							});

							$('.packageHelperRowLinkVoid').live('click', function(event) {
								event.preventDefault();
								jQuery.ajax({
									url: '/admin/po_viewer.php',
									data: 'action=packageTrackerVoid&tracking_id='+this.id.split('_')[1],
									success: function(response) {
										packageHelper();
									}
								});
							});

							packageEdit = false;

							$('#packageTrackerInsertSubmit').click(function(event) {
								event.preventDefault();
								var action = "";

								if (packageEdit) action = 'packageTrackerEdit';
								else action = 'packageTrackerInsert';

								jQuery.ajax({
									url: '/admin/po_viewer.php',
									data: 'action='+action+'&poId='+jQuery('#po-id').val()+'&tracking_id='+$('#packageTrackerInsertId').val()+'&tracking_number='+$('#packageTrackerInsertNumber').val()+'&tracking_method='+$('#packageTrackerInsertMethod').val()+'&eta='+$('#packageTrackerInsertEta').val()+'&tracking_status='+$('#packageTrackerInsertStatus:checked').val()+'&bin_number='+$('#packageTrackerInsertBin').val()+'&tracking_complete='+$('#packageTrackerInsertComplete:checked').val(),
									success: function(response) {
										packageHelper();
										$('#packageTrackerInsertId').val('');
										$('#packageTrackerInsertNumber').val('');
										$('#packageTrackerInsertMethod').val('');
										$('#packageTrackerInsertEta').val('');
										$('#packageTrackerInsertStatus').removeAttr('checked');
										$('#packageTrackerInsertBin').val('');
										$('#packageTrackerInsertComplete').removeAttr('checked');
										$('#packageEditDiv').toggle();
										packageEdit = false;
									}
								});
							});
						});
					</script>
					<style>
					</style>

				</div>
				<?php $today = new DateTime(); ?>
				<div class="main tool allocations-table">
					<style>
						.allocation-details { border-collapse:collapse; margin-top:5px; }
						.allocation-details th, .allocation-details td { border:1px solid #000; padding:5px 8px; }
						.allocation-details th { background-color:#eee; }
						.allocation-details tfoot td { text-align:right; border-width:0px; }
						.allocation-details .late-alloc td { background-color:#fcc; }
						.allocation-details .late-recpt td { background-color:#ffd; }
						.allocation-details.non-manager .allocation-manage { display:none; }
						.allocation-details.po-locked .allocation-add { display:none; }
					</style>
					<strong>Allocations By Promised Dates</strong>
					<table border="0" cellpadding="0" cellspacing="0" class="allocation-details <?= !in_array($_SESSION['login_groups_id'], [1, 8, 28, 20, 7, 29, 9, 18, 17, 5, 10, 33])?'non-manager':''; ?> <?= in_array($header['po_status_id'], [3, 4])?'order-locked':''; ?>">
						<thead>
						<tr>
							<th>IPN</th>
							<th>Order</th>
							<th>Qty</th>
							<th>Promised Ship Date</th>
							<th class="allocation-manage">[X]</th>
						</tr>
						</thead>
						<tfoot class="allocation-manage allocation-add">
						<tr>
							<td colspan="5">
								<a href="/admin/po_viewer.php?poId=<?= $po->id(); ?>&action=auto-allocate" class="button-link auto-allocate" data-method="post">Auto-Allocate</a>
							</td>
						</tr>
						</tfoot>
						<tbody>
						<?php // we'll leave this allocations query separate from the one coming out of the po model because the logic is slightly different, specifically around sorting
						// to fit more in our model paradigm we'd make sure we had a version of this element with all of the necessary data and build a PHP sort for it
						if ($allocations = prepared_query::fetch('SELECT potoa.id as allocation_id, psc.stock_name as ipn, o.orders_id, potoa.quantity, o.promised_ship_date FROM purchase_order_products pop JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id JOIN purchase_order_to_order_allocations potoa ON pop.id = potoa.purchase_order_product_id JOIN orders_products op ON potoa.order_product_id = op.orders_products_id JOIN orders o ON op.orders_id = o.orders_id WHERE pop.purchase_order_id = :po_id ORDER BY IFNULL(o.promised_ship_date, DATE_ADD(NOW(), INTERVAL 2 WEEK)) ASC, psc.stock_name ASC, o.orders_id ASC', cardinality::SET, [':po_id' => $po->id()])) {
							foreach ($allocations as $allocation) {
								$promised_ship_date = !empty($allocation['promised_ship_date'])?new DateTime($allocation['promised_ship_date']):NULL;

								$expected_date_class = '';
								if (!empty($promised_ship_date)) {
									if (!empty($header['expected_date']) && $today > $header['expected_date']) $expected_date_class = 'late-recpt';
									if (empty($header['expected_date']) || $header['expected_date'] > $promised_ship_date) $expected_date_class = 'late-alloc';
								} ?>
								<tr class="<?= $expected_date_class; ?>">
									<td><a href="/admin/ipn_editor.php?ipnId=<?= $allocation['ipn']; ?>" target="_blank"><?= $allocation['ipn']; ?></a></td>
									<td><a href="/admin/orders_new.php?oID=<?= $allocation['orders_id']; ?>&action=edit" target="_blank"><?= $allocation['orders_id']; ?></a></td>
									<td><?= $allocation['quantity']; ?></td>
									<td><?= !empty($promised_ship_date)?$promised_ship_date->format('m/d/Y'):'NONE'; ?></td>
									<td class="allocation-manage"><a href="/admin/po_viewer.php?poId=<?= $po->id(); ?>&action=de-allocate&allocation_id=<?= $allocation['allocation_id']; ?>" class="button-link auto-allocate" data-method="post">Remove</a></td>
								</tr>
							<?php }
						}
						else { ?>
							<tr>
								<td colspan="5" class="cen">No Allocations to Orders</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<script>
						ck.ajaxify.link(jQuery('.auto-allocate'), function() {
							window.location.reload(true);
						});
					</script>
				</div>
			</div>
			<div class="tool-column">
				<div class="main tool">
					<table class="additional-costs-table">
						<thead>
						<tr>
							<th colspan="4">Additional Costs</th>
						</tr>
						<tr>
							<th>Description</th>
							<th>Amount</th>
							<th>Creation Date</th>
							<th>Actions</th>
						</tr>
						</thead>
						<tbody>
						<?php if ($po->has_additional_costs()) {
							foreach ($po->get_additional_costs() as $cost) { ?>
								<tr>
									<td><?= $cost['description']; ?></td>
									<td><?= CK\text::monetize($cost['amount']); ?></td>
									<td><?= $cost['date_created']; ?></td>
									<td>
										<?php if ($cost['cost_spread'] == 0) { ?>
											<a href="/admin/po_viewer.php?poId=<?= $po->id(); ?>&action=spread_additional_cost&additional_cost_id=<?= $cost['purchase_order_additional_cost_id']; ?>" class="additional-costs-actions button-link">Spread Cost</a>
											<a href="#" class="open-update-additional-cost-modal-button button-link" data-additional-amount="<?= $cost['amount']; ?>" data-additional-cost-description="<?= $cost['description']; ?>" data-additional-cost-id="<?= $cost['purchase_order_additional_cost_id']; ?>">Edit</a>
											<a href="/admin/po_viewer.php?poId=<?= $po->id(); ?>&action=remove_additional_cost&additional_cost_id=<?= $cost['purchase_order_additional_cost_id']; ?>" class="additional-costs-actions button-link">Remove</a>
										<?php }
										else { ?>
											<a href="/admin/po_viewer.php?poId=<?= $po->id(); ?>&action=unspread_additional_cost&additional_cost_id=<?= $cost['purchase_order_additional_cost_id']; ?>" class="additional-costs-actions button-link">Un-Spread Cost</a>
										<?php } ?>
									</td>
								</tr>
							<?php }
						}
						else { ?>
						<tr>
							<td colspan="4">No Additional Costs</td>
						</tr>
						<?php } ?>
						</tbody>
					</table>
					<button id="open-additional-cost-modal-button">Add Additional Cost</button>
				</div>
			</div>
			<style>
				.modal { display:none; position:fixed; z-index:1; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgb(0,0,0); background-color:rgba(0,0,0,0.4); }
				.modal-content {  background-color:#fefefe; margin:3% auto 15% auto; padding:20px; border:1px solid #888; width:35%; }
				.modal-header { width:100%; height:auto; display:block; clear:both; overflow:auto; border-bottom:1px solid #5f9ea0; margin-bottom: 10px; }
				.modal .close { color:#aaa; float:right; font-size:28px; font-weight:bold; cursor:pointer; }
				.modal-title { float:left; }
				.mocal .close:hover, .modal .close:focus { color:#000; text-decoration:none; cursor:pointer; }
				.modal-content form, .modal-content input, .modal-content input[type=submit], .modal-content form textarea, .modal-content select { width:100%; padding:0; }
				.modal-content input, .modal-content textarea, .modal-content input[type=submit], .modal-content select { height:25px; margin-bottom:10px; }
				.modal-content input[type=submit] { border:none; height:45px; }
				.modal-content input[type=submit]:hover { cursor:pointer; font-weight:bold; border:1px solid black; }

				.input-container { display:block; width:100%; height:auto; overflow:hidden; }
				.input-container input { width:85%; float:right; }
				.input-container label { float:left; }

				@media only screen and (max-width: 1300px) {
					.input-container label { display:block; width:100%; }
					.input-container input { display:block; width: 100%; }
				}
			</style>
			<div class="modal" id="additional-cost-modal">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Add Additional Cost</h4>
						<span id="close-additional-cost-modal" class="close">&times;</span>
					</div>
					<form id="additonal-cost-form" method="POST" action="/admin/po_viewer.php?poId=<?= $po->id(); ?>&action=add_additional_cost">
						<div class="input-container">
							<label for="additional-cost-amount">Amount</label>
							<input type="text" name="additional_cost_amount" id="additional-cost-amount" placeholder="Amount">
						</div>
						<div class="input-container">
							<label for="additional-cost-description">Description</label>
							<input type="text" name="additional_cost_description" id="additional-cost-description" placeholder="Description">
						</div>
						<input type="submit" id="add-additional-cost-form-submit" value="Add Additional Cost">
					</form>
				</div>
			</div>

			<div style="clear:both;"></div>

			<?php if (!empty($receiving_sessions)) { ?>
				<style>
					tr.receive_header td { font-size: 10px; font-family:verdana; font-weight: bold; }
					tr.receive_row td { font-size: 10px; font-family:verdana; }
					tr.receive_row:hover td { background-color:#ffc; }
					table.receiving-sessions td { padding:2px 8px; }
				</style>
				<h3 style="font-family: verdana;">Receiving sessions:</h3>
				<form id="unreceive-form" action="po_viewer_unreceive.php" method="get">
					<script>
						// we're just using the form for jquery serialize
						jQuery('#unreceive-form').submit(function(e) { e.preventDefault(); return false; });
					</script>
					<table class="receiving-sessions" cellspacing="0" cellpadding="0">
						<thead>
						<tr>
							<td class="main"><b>Session ID</b></td>
							<td class="main"><b>Tracking #s</b></td>
							<td class="main"><b>Receiver</b></td>
							<td class="main"><b>Date</b></td>
							<td class="main"><b>Notes</b></td>
							<td class="main" colspan="5"><b>Products<b></td>
							<td class="main"><b>Amount Received</b></td>
							<td class="main" colspan="2" style="text-align:center;"><b>Action</b></td>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($receiving_sessions as $idx => $session) {
							// this is more specific than the more general session products in the model, so leaving it for now
							$session_products = prepared_query::fetch('SELECT DISTINCT porp.quantity_received, porp.purchase_order_product_id, porp.quantity_remaining, porp.paid, porp.entered, psc.stock_name, psc.stock_id, psc.serialized, pop.cost, s.serial, s.id as serial_id, CASE WHEN psc.serialized = 1 AND sh.order_product_id IS NULL AND sh.id IS NOT NULL THEN 1 WHEN psc.serialized = 1 AND sh.order_product_id IS NOT NULL AND sh.id IS NOT NULL THEN 0 WHEN (psc.serialized = 0 OR sh.id IS NULL) AND psc.stock_quantity > porp.quantity_received THEN porp.quantity_received ELSE psc.stock_quantity END as qty_available FROM purchase_order_received_products porp LEFT JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id LEFT JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id LEFT JOIN serials_history sh ON psc.serialized = 1 AND sh.pop_id = pop.id AND sh.pors_id = porp.receiving_session_id LEFT JOIN serials s ON sh.serial_id = s.id WHERE porp.receiving_session_id = ?', cardinality::SET, [$session['po_receiving_session_id']]);

							$all_paid = FALSE;
							if (!prepared_query::fetch('SELECT 1 FROM purchase_order_received_products WHERE paid = 0 AND receiving_session_id = ?', cardinality::SINGLE, [$session['po_receiving_session_id']])) $all_paid = TRUE;

							$pors_received = 0; ?>
							<tr style="background-color:<?= $idx%2==0?'#fff':'#ccc'; ?>;">
								<td class="main" valign="top" style="border-top:2px solid #500;"><?= $session['po_receiving_session_id']; ?></td>
								<td class="main" valign="top" style="border-top:2px solid #500;">
									<?= !empty($session['tracking_number'])?$session['tracking_number']:'NONE'; ?>
								</td>
								<td class="main" valign="top" style="border-top:2px solid #500;"><?= $session['receiver_firstname'].'&nbsp;'.$session['receiver_lastname']; ?></td>
								<td class="main" valign="top" style="border-top:2px solid #500;"><?= $session['session_received_date']->format('m/d/Y h:ia'); ?></td>
								<td class="main" valign="top" style="border-top:2px solid #500;"><?= nl2br($session['notes']); ?>&nbsp;</td>
								<td class="main" valign="top" style="border-top:2px solid #500;">IPN</td>
								<td class="main" valign="top" style="border-top:2px solid #500;">Total Received</td>
								<td class="main" valign="top" style="border-top:2px solid #500;">Remaining</td>
								<td class="main" valign="top" style="border-top:2px solid #500;">Serials</td>
								<td class="main" valign="top" style="border-top:2px solid #500;">
									Paid
									<input type="checkbox" <?= $all_paid?'checked':''; ?> onclick="paid_product_all(<?= $session['po_receiving_session_id']; ?>);" id="paid_<?= $session['po_receiving_session_id']; ?>" name="paid_<?= $session['po_receiving_session_id']; ?>">
									<span style="font-size:9px; font-weight:normal;">check all</span>
								</td>
								<td class="main" valign="top" align="center" style="border-top:2px solid #500;">$<span id="session-received-<?= $session['po_receiving_session_id']; ?>"></span></td>
								<td class="main" valign="top" style="border-top:2px solid #500;text-align:center;border-left:1px solid #000;">On Hand</td>
								<td class="main" valign="top" style="border-top:2px solid #500;text-align:center;">
									<?php // only certain groups get to unreceive
									if (in_array($_SESSION['perms']['admin_groups_id'], [1, 7, 13, 17, 20, 22, 30])) { ?>
										<input type="button" value="Unreceive" onclick="open_unreceive_dialog(<?= $session['po_receiving_session_id']; ?>);" disabled id="session-<?= $session['po_receiving_session_id']; ?>" class="unreceive-session" data-itemcnt="0">
									<?php } ?>
								</td>
							</tr>
							<?php $seen_products = [];
							foreach ($session_products as $pidx => $product) {
								if (!isset($seen_products[$product['purchase_order_product_id']])) $seen_products[$product['purchase_order_product_id']] = 0;
								$seen_products[$product['purchase_order_product_id']]++;

								if ($seen_products[$product['purchase_order_product_id']] == 1) {
									$product_bgc = ($idx%2!=0&&count($seen_products)%2!=0)?'#c0c0c0':(($idx%2==0&&count($seen_products)%2!=0)?'#f0f0f0':'');
									$pors_received += $product['quantity_received'] * $product['cost'];
								} ?>

								<tr style="background-color:<?= $product_bgc; ?>;" class="receive_row">
									<td colspan="<?= $seen_products[$product['purchase_order_product_id']]>1?'8':'5'; ?>">
										<?php if ($pidx+1 == count($session_products)) { ?>
											<script>
												jQuery('#session-received-<?= $session['po_receiving_session_id']; ?>').html('<?= number_format($pors_received, 2); ?>');
											</script>
										<?php }
										else { ?>
											&nbsp;
										<?php } ?>
									</td>
									<?php if ($seen_products[$product['purchase_order_product_id']] == 1) { ?>
									<td valign="top"><a href="/admin/ipn_editor.php?ipnId=<?= $product['stock_name']; ?>" target="_blank"><?= $product['stock_name']; ?></a></td>
									<td valign="top"><?= $product['quantity_received']; ?></td>
									<td valign="top"><?= $product['quantity_remaining']; ?></td>
									<?php } ?>
									<td valign="top"><?= $product['serial']; ?>&nbsp;</td>
									<td valign="top">
										<?php if ($seen_products[$product['purchase_order_product_id']] == 1) { ?>
											<input class="item-paid" type="checkbox" <?= $product['paid']==1?'checked':''; ?> onclick="paid_product(<?= $product['purchase_order_product_id']; ?>, <?= $session['po_receiving_session_id']; ?>);" id="paid_<?= $product['purchase_order_product_id']; ?>" name="paid_<?= $product['purchase_order_product_id']; ?>">
										<?php }
										else { ?>
											&nbsp;
										<?php } ?>
									</td>
									<td>&nbsp;</td>
									<td valign="top" style="text-align:center;border-left:1px solid #000;"><?= $product['qty_available']; ?></td>
									<td valign="top" style="text-align:center;">
										<?php // only certain groups get to unreceive
										if (in_array($_SESSION['perms']['admin_groups_id'], [1, 7, 13, 17, 20, 22, 30])) {
											if (!empty($product['serial'])) { ?>
												<input type="checkbox" name="unreceive_prod[<?= $session['po_receiving_session_id']; ?>][<?= $product['purchase_order_product_id']; ?>][<?= $product['serial']; ?>]" value="1" class="unreceive-qty session-<?= $session['po_receiving_session_id']; ?>" data-counted="0" <?= $product['qty_available']||in_array($_SESSION['perms']['admin_groups_id'], [1])?'':'disabled'; ?>>
											<?php }
											elseif (!empty($product['serialized'])) { ?>
												<input type="text" style="width:30px;text-align:center;font-size:.9em;height:15px;" name="unreceive_prod[<?= $session['po_receiving_session_id']; ?>][<?= $product['purchase_order_product_id']; ?>][missing-serials]" value="0" class="unreceive-qty session-<?= $session['po_receiving_session_id']; ?>" data-counted="0" data-max="<?= in_array($_SESSION['perms']['admin_groups_id'], [1])?$product['quantity_received']:$product['qty_available']; ?>" <?= $product['qty_available']||in_array($_SESSION['perms']['admin_groups_id'], [1])?'':'disabled'; ?>>
											<?php }
											else { ?>
												<input type="text" style="width:30px;text-align:center;font-size:.9em;height:15px;" name="unreceive_prod[<?= $session['po_receiving_session_id']; ?>][<?= $product['purchase_order_product_id']; ?>][unserialized]" value="0" class="unreceive-qty session-<?= $session['po_receiving_session_id']; ?>" data-counted="0" data-max="<?= in_array($_SESSION['perms']['admin_groups_id'], [1])?$product['quantity_received']:$product['qty_available']; ?>" <?= $product['qty_available']||in_array($_SESSION['perms']['admin_groups_id'], [1])?'':'disabled'; ?>>
											<?php }
										} ?>
									</td>
								</tr>
							<?php }
						} ?>
						</tbody>
					</table>
				</form>
				<script>
					jQuery('.unreceive-qty').bind('click keyup change', function(event) {
						event.stopPropagation();
						if (jQuery(this).attr('type') == 'checkbox') {
							if (jQuery(this).is(':checked') && jQuery(this).attr('data-counted') == 0) {
								jQuery(this).attr('data-counted', 1);
								var classlist = jQuery(this).attr('class').split(/\s+/);
								for (var i=0; i<classlist.length; i++) {
									if (classlist[i] == 'unreceive-qty') continue;
									var sess = classlist[i];
								}
								var count = parseInt(jQuery('#'+sess).attr('data-itemcnt'));
								count++;
								jQuery('#'+sess).attr('data-itemcnt', count).removeAttr('disabled');
							}
							else if (!jQuery(this).is(':checked') && jQuery(this).attr('data-counted') == 1) {
								jQuery(this).attr('data-counted', 0);
								var classlist = jQuery(this).attr('class').split(/\s+/);
								for (var i=0; i<classlist.length; i++) {
									if (classlist[i] == 'unreceive-qty') continue;
									var sess = classlist[i];
								}
								var count = parseInt(jQuery('#'+sess).attr('data-itemcnt'));
								count--;
								jQuery('#'+sess).attr('data-itemcnt', count);
								if (count == 0) jQuery('#'+sess).attr('disabled', 'disabled');
							}
						}
						else if (jQuery(this).attr('type') == 'text') {
							if (parseInt(jQuery(this).val()) > 0) {
								var max = parseInt(jQuery(this).attr('data-max'));
								if (parseInt(jQuery(this).val()) > max) {
									jQuery(this).val(max);
								}
								if (jQuery(this).attr('data-counted') == 0) {
									jQuery(this).attr('data-counted', 1);
									var classlist = jQuery(this).attr('class').split(/\s+/);
									for (var i=0; i<classlist.length; i++) {
										if (classlist[i] == 'unreceive-qty') continue;
										var sess = classlist[i];
									}
									var count = parseInt(jQuery('#'+sess).attr('data-itemcnt'));
									count++;
									jQuery('#'+sess).attr('data-itemcnt', count).removeAttr('disabled');
								}
							}
							else if (parseInt(jQuery(this).val()) <= 0 && jQuery(this).attr('data-counted') == 1) {
								jQuery(this).attr('data-counted', 0);
								if (parseInt(jQuery(this).val()) < 0) {
									jQuery(this).val(0);
								}
								var classlist = jQuery(this).attr('class').split(/\s+/);
								for (var i=0; i<classlist.length; i++) {
									if (classlist[i] == 'unreceive-qty') continue;
									var sess = classlist[i];
								}
								var count = parseInt(jQuery('#'+sess).attr('data-itemcnt'));
								count--;
								jQuery('#'+sess).attr('data-itemcnt', count);
								if (count == 0) jQuery('#'+sess).attr('disabled', 'disabled');
							}
						}
					});

					jQuery('.receive_row').click(function(event) {
						if (event.target.nodeName.toLowerCase() == 'input') return;
						jQuery(this).find('.unreceive-qty').each(function() {
							if (jQuery(this).attr('type') == 'checkbox') {
								if (jQuery(this).is(':disabled')) return;
								if (jQuery(this).is(':checked')) jQuery(this).removeAttr('checked');
								else jQuery(this).attr('checked', 'checked');
								jQuery(this).change();
							}
							else jQuery(this).focus();
						});
					});

					// for now this is handled by the onclick attribute on the element itself
					/*jQuery('.unreceive-session').click(function() {
					 });*/
				</script>
			<?php }

			if ($reviews = $po->get_locked_reviews()) { ?>
				<style>
					table.products_table td { padding: 5px; }
					table.new_products { border: 1px solid #000; font-family: Verdana, Arial, sans-serif; font-size:12px; margin-bottom:10px; }
					table.new_products tr { background-color: #ebebeb; padding: 3px; }
					table.new_products tr td { padding: 5px; min-width: 50px; background-color:#FF9966; }
				</style>
				<br><br>
				<a href="/admin/po_editor.php?action=edit&poId=<?= $po->id(); ?>" target="_blank" class="button-link">Review</a>
				<?php foreach ($reviews as $review) { ?>
				<table class="new_products">
					<caption>Items Under Review [Tracking #: <?= !empty($review['tracking_number'])?$review['tracking_number']:'NONE'; ?>] [<?= $review['created_on']->format('m/d/Y'); ?>]:</caption>
					<thead>
						<tr>
							<th>IPN</th>
							<th>Review Qty</th>
							<th>Cost</th>
							<th>Serial</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($po->get_reviewing_products($review['po_review_id']) as $rp) {
							if ($rp['status'] != ck_purchase_order::$review_product_statuses['NEW_PRODUCT']) continue;
							$serialized = FALSE;
							$rowcount = 1;
							if ($rp['ipn']->is('serialized') && !empty($rp['serials'])) {
								$serialized = TRUE;
								$rowcount = count($rp['serials']);
							}
							$pop = $po->get_products($rp['po_product_id']); ?>
						<tr>
							<td rowspan="<?= $rowcount; ?>"><?= $rp['ipn']->get_header('ipn'); ?></td>
							<td rowspan="<?= $rowcount; ?>"><?= $rp['qty_received']; ?></td>
							<td rowspan="<?= $rowcount; ?>"><?= $pop['cost']; ?></td>
							<?php if ($serialized) {
								foreach ($rp['serials'] as $idx => $serial) { ?>
							<td><?= $serial->get_header('serial_number'); ?></td>
									<?php if ($idx + 1 < count($rp['serials'])) { ?>
						</tr>
						<tr>
									<?php }
								}
							}
							else { ?>
							<td>N/A</td>
							<?php } ?>
						</tr>
						<?php } ?>
					</tbody>
				</table>
				<?php }
			} ?>
			<br><br>

			<div class="main">
				<table>
					<tr>
						<td class="main"><h3>Vendor Only Notes:</h3></td>
					</tr>
					<tr>
						<td class="main">
							<?= nl2br($header['notes']); ?>
						</td>
					</tr>
				</table>
				<br><br>
				<style>
					#receiving-notes-block { background-color:#ececec; border:2px solid #ababab; padding:5px 10px; width:1000px; }
					#receiving-notes-list th, #receiving-notes-list td { padding:5px 20px 5px 0px; vertical-align:top; text-align:left; }
					#receiving-notes-list th { border-bottom:1px solid #999; }
					#receiving-notes-list td { border-bottom:1px dashed #999; }
					.note-date { white-space:pre; }
				</style>
				<div id="receiving-notes">
					<h3>Admin Notes</h3>
					<div id="receiving-notes-block">
						<table id="receiving-notes-list" class="noPrint">
							<thead>
							<tr>
								<th>Created On</th>
								<th>Created By</th>
								<th>Note</th>
							</tr>
							</thead>
							<tfoot>
							<tr>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td>
									<form id="notes-form">
										<input type="hidden" name="poId" value="<?= $po->id(); ?>" id="poId">
										<textarea id="notes" style="height: 75px; width: 300px;" name="notes"></textarea>
										<input id="add-note-button" style="vertical-align: top;" type="button" value="Add">
									</form>
								</td>
							</tr>
							</tfoot>
							<tbody id="notes-display">
							<?php foreach ($admin_notes as $note) { ?>
								<tr>
									<td class="note-date"><?= $note['note_created']->format("Y-m-d\nH:i:s"); ?></td>
									<td><?= $note['admin_firstname'].' '.$note['admin_lastname']; ?></td>
									<td><?= nl2br($note['note_text']); ?></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<!------------------------------------------------------------------- -->
		</td>
		<!-- body_text_eof //-->
	</tr>
</table>
<div id="unreceive_dialog_box" style="position: absolute; display:none; border: 1px solid black; background-color: #fff;"></div>
<script type="text/javascript">
	function center_div(el) {
		if (typeof window.innerHeight != 'undefined') {
			$(el).style.top = Math.round(document.viewport.getScrollOffsets().top + ((window.innerHeight - $(el).getHeight()))/2)+'px';
			$(el).style.left = Math.round(document.viewport.getScrollOffsets().left + ((window.innerWidth - $(el).getWidth()))/2)+'px';
		}
		else {
			$(el).style.top = Math.round(document.body.scrollTop + (($$('body')[0].clientHeight - $(el).getHeight()))/2)+'px';
			$(el).style.left = Math.round(document.body.scrollLeft + (($$('body')[0].clientWidth - $(el).getWidth()))/2)+'px';
		}
	}

	function open_unreceive_dialog(pors_id) {
		params = {
			pors_id: pors_id,
			poId: jQuery('#po-id').val(),
		};

		fields = jQuery('#unreceive-form').serializeArray();
		for (var i=0; i<fields.length; i++) {
			if (/unreceive_prod/.test(fields[i].name) && (!/unserialized/.test(fields[i].name) || fields[i].value > 0)) {
				params[fields[i].name] = fields[i].value;
			}
		}

		new Ajax.Updater('unreceive_dialog_box', 'po_viewer_unreceive.php', {
			method : 'get',
			evalJS : 'force',
			parameters : params,
			onSuccess : function(transport) {
				$('unreceive_dialog_box').show();
				center_div('unreceive_dialog_box');
			}
		});
	}

	function paid_product(product_id,session_id) {
		var paid = do_product('paid_', 'unpaid', product_id, session_id);
		new Ajax.Request('po_product_paid.php', {
			method : 'get',
			parameters : {
				product_id: product_id,
				paid: paid,
				session_id: session_id
			}
		});
	}

	function paid_product_all(session_id) {
		var paid = do_product_all('paid_', 'unpaid', session_id);
		new Ajax.Request('po_product_paid.php', {
			method : 'get',
			parameters : {
				paid: paid,
				session_id: session_id
			}
		});
	}

	function do_product_all(item, desc, session_id) {
		var paid = null;
		if ($(item+session_id) && $F(item+session_id)) {
			paid = 1;
			var answer = confirm('Are you sure you want to mark all items on this receiving session as paid?');
			if (!answer) {
				document.getElementById(item+session_id).checked = true;
				paid=0;
			}
			else {
				document.getElementById(item+session_id).checked = false;
				paid=1;
				setTimeout("location.reload(true);", '500');
			}
		}
		else {
			paid = 0;
			var answer = confirm('Are you sure you want to markall items on this receiving session as not paid?');
			if (!answer) {
				document.getElementById(item+session_id).checked = true;
				paid = 1;
			}
			else {
				document.getElementById(item+session_id).checked = false;
				paid = 0;
				setTimeout("location.reload(true);", '500');
			}
		}
		return paid;
	}

	function do_product(item, desc, product_id, session_id) {
		var paid = null;
		if ($(item+product_id) && $F(item+product_id)) {
			paid = 1;
		}
		else {
			paid = 0;
			var answer = confirm('Are you sure you want to mark this item as '+desc+'?');
			if (!answer) {
				document.getElementById(item+product_id).checked = true;
				paid = 1;
			}
			else {
				document.getElementById(item+product_id).checked = false;
				paid = 0;
			}
		}
		return paid;
	}

	jQuery('.open-update-additional-cost-modal-button').click(function () {
		jQuery('#additional-cost-modal').show();
		jQuery("#additional-cost-amount").val(jQuery(this).attr('data-additional-amount'));
		jQuery("#additional-cost-description").val(jQuery(this).attr('data-additional-cost-description'));
		//update html of modal
		jQuery("#add-additional-cost-form-submit").html('Update Additional Cost');
		jQuery(".modal-title").html('Update Additional Cost');
		//update form action depending on what we are doing
		jQuery("#additonal-cost-form").attr('action', '/admin/po_viewer.php?poId='+jQuery('#po-id').val()+'&action=update_additional_cost&purchase_order_additional_cost_id='+jQuery(this).attr('data-additional-cost-id'));
	});

	jQuery('#open-additional-cost-modal-button').click(function() {
		jQuery('#additional-cost-modal').show();
	});

	jQuery('#close-additional-cost-modal').click(function() {
		jQuery('#additional-cost-modal').hide();
	});
</script>
<!-- body_eof //-->
<script>
	ck.button_links();
</script>
</body>
</html>
