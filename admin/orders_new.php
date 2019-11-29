<?php
ini_set('memory_limit', '512M');
require('includes/application_top.php');
require_once('includes/modules/accounting_notes.php');

if (!isset($_SESSION['station'])) $_SESSION['station'] = 'pick';

$action = isset($_GET['action'])?$_GET['action']:'';

$oID = !empty($_REQUEST['oID'])?$_REQUEST['oID']:NULL;
$orders_id = !empty($_REQUEST['orders_id'])?$_REQUEST['orders_id']:$oID;

if (!empty($orders_id)) $sales_order = new ck_sales_order($orders_id);

//change order status to warehouse and to packing queue
if (!empty($_POST['move-to-pack-queue'])) {
	$so = new ck_sales_order($_POST['orders_id']);
	$so->update_order_status(['orders_status_id' => 7, 'orders_sub_status_id' => 24]);
	CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$so->id().'&action=edit');
}

if ($__FLAG['ajax']) {
	$response = [];

	$skip_response = FALSE;

	switch ($action) {
		case 'serial-reservation-lookup':
			$response['results'] = [];
			$serial = trim($_GET['serial_number']);
			$stock_id = $_GET['stock_id'];
			//if (empty($serial)) exit();
			if ($serials = ck_serial::get_reservable_serials_by_serial_match($serial, $stock_id)) {
				foreach ($serials as $serial) {
					$owner_name = NULL;
					if ($po = $serial->get_last_po()) {
						if ($owner = $po->get_header('owner')) {
							$owner_name = $owner->get_name();
						}
					}
					$response['results'][] = [
						'result_id' => $serial->id(),
						'field_value' => $serial->get_header('serial_number'),
						'result_label' => $serial->get_header('serial_number'),
						'serial_id' => $serial->id(),
						'serial_number' => $serial->get_header('serial_number'),
						'owner' => $owner_name,
						'status' => $serial->get_header('status'),
						'notes' => !empty($serial->get_current_history('short_notes'))?$serial->get_current_history('short_notes'):'No Notes',
						'orders_products_id' => $_GET['orders_products_id'],
					];
				}
			}
			break;
		case 'reserve-serial':
			$serial = new ck_serial($_GET['serial_id']);

			try {
				$admin = ck_admin::login_instance();

				$serial->reserve($_GET['orders_products_id'], $admin->id());
				$response['success'] = 1;
				$response['admin'] = $admin->get_name();
			}
			catch (Exception $e) {
				$response['message'] = $e->getMessage();
			}

			break;
		case 'unreserve-serial':
			$serial = new ck_serial($_GET['serial_id']);

			try {
				$serial->unreserve($_GET['orders_products_id']);
				$response['success'] = 1;
			}
			catch (Exception $e) {
				$response['message'] = $e->getMessage();
			}
			break;
		case 'serial-allocate-lookup':
			$serials = prepared_query::fetch('SELECT s.id as value, s.serial as label FROM serials s LEFT JOIN serials_assignments sa ON s.id = sa.serial_id AND sa.fulfilled = 0 AND sa.orders_products_id = :orders_products_id WHERE (s.status = :instock OR (s.status != :allocated AND sa.serials_assignment_id IS NOT NULL)) AND s.ipn = :stock_id AND s.serial LIKE :serial_number LIMIT 50', cardinality::SET, [':instock' => ck_serial::$statuses['INSTOCK'], ':allocated' => ck_serial::$statuses['ALLOCATED'], ':stock_id' => $_REQUEST['stock_id'], ':serial_number' => $_REQUEST['serial_number'].'%', ':orders_products_id' => $_REQUEST['orders_products_id']]);

			foreach ($serials as $serial) {
				$response[] = (object) $serial;
			}

			break;
		case 'add-recipient':
			if (empty($_REQUEST['recipient_email'])) return FALSE;
			$data = ['email' => $_REQUEST['recipient_email'], 'name' => $_REQUEST['recipient_name']];
			$sales_order->add_recipient($data);
			$response = TRUE;
			break;
		case 'delete-recipient':
			$sales_order->delete_recipient($_REQUEST['recipient_id']);
			$response = TRUE;
			break;
		default:
			$skip_response = TRUE;
			break;
	}

	if (!$skip_response) {
		echo json_encode($response);
		exit();
	}
}

if (!empty($action)) {
	switch ($action) {
		case 'auto-allocate':
			$order = new ck_sales_order($oID);
			if ($order->is_open() && in_array($_SESSION['login_groups_id'], [1, 8, 28, 20, 7, 29, 9, 18, 17, 5, 10])) {
				if ($order_products = prepared_query::fetch('SELECT p.stock_id, op.orders_products_id, GREATEST(0, op.products_quantity - IFNULL(SUM(potoa.quantity), 0)) as qty_needed_for_order FROM orders_products op JOIN products p ON op.products_id = p.products_id LEFT JOIN purchase_order_to_order_allocations potoa ON op.orders_products_id = potoa.order_product_id WHERE op.orders_id = :orders_id GROUP BY p.stock_id, op.orders_products_id, op.products_quantity', cardinality::SET, [':orders_id' => $_REQUEST['oID']])) {
					foreach ($order_products as &$order_product) {
						$ipn = new ck_ipn2($order_product['stock_id']);

						$order_product['qty_needed_for_order'] -= $ipn->get_inventory('salable');
						$order_product['qty_needed_for_order'] = max(0, $order_product['qty_needed_for_order']);

						if ($order_product['qty_needed_for_order'] <= 0) continue;

						// The original query that filtered POs that have an expected date of today or later -- 'SELECT pop.id as purchase_order_product_id, pop.quantity - IFNULL(porp.quantity_received, 0) - IFNULL(potoa.quantity, 0) as outstanding_quantity FROM purchase_order_products pop JOIN purchase_orders po ON pop.purchase_order_id = po.id AND po.status IN (1, 2) AND DATE(po.expected_date) >= DATE(NOW()) LEFT JOIN (SELECT porp.purchase_order_product_id, SUM(porp.quantity_received) as quantity_received FROM purchase_order_received_products porp JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id JOIN purchase_orders po ON pop.purchase_order_id = po.id AND po.status IN (1, 2) AND DATE(po.expected_date) >= DATE(NOW())) porp ON pop.id = porp.purchase_order_product_id LEFT JOIN (SELECT potoa.purchase_order_product_id, SUM(potoa.quantity) as quantity FROM purchase_order_to_order_allocations potoa JOIN purchase_order_products pop ON potoa.purchase_order_product_id = pop.id JOIN purchase_orders po ON pop.purchase_order_id = po.id AND po.status IN (1, 2) AND DATE(po.expected_date) >= DATE(NOW())) potoa ON pop.id = potoa.purchase_order_product_id WHERE pop.ipn_id = :stock_id AND pop.quantity - IFNULL(porp.quantity_received, 0) - IFNULL(potoa.quantity, 0) > 0 ORDER BY po.expected_date ASC, outstanding_quantity DESC'

						if ($pop_products = prepared_query::fetch('SELECT pop.id as purchase_order_product_id, pop.quantity - IFNULL(porp.quantity_received, 0) - IFNULL(potoa.quantity, 0) as outstanding_quantity FROM purchase_order_products pop JOIN purchase_orders po ON pop.purchase_order_id = po.id AND po.status IN (1, 2) LEFT JOIN (SELECT porp.purchase_order_product_id, SUM(porp.quantity_received) as quantity_received FROM purchase_order_received_products porp JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id JOIN purchase_orders po ON pop.purchase_order_id = po.id AND po.status IN (1, 2) GROUP BY porp.purchase_order_product_id) porp ON pop.id = porp.purchase_order_product_id LEFT JOIN (SELECT potoa.purchase_order_product_id, SUM(potoa.quantity) as quantity FROM purchase_order_to_order_allocations potoa JOIN purchase_order_products pop ON potoa.purchase_order_product_id = pop.id JOIN purchase_orders po ON pop.purchase_order_id = po.id AND po.status IN (1, 2) GROUP BY potoa.purchase_order_product_id) potoa ON pop.id = potoa.purchase_order_product_id WHERE pop.ipn_id = :stock_id AND pop.quantity - IFNULL(porp.quantity_received, 0) - IFNULL(potoa.quantity, 0) > 0 ORDER BY po.expected_date ASC, outstanding_quantity DESC', cardinality::SET, [':stock_id' => $order_product['stock_id']])) {
							foreach ($pop_products as $pop_product) {
								$allocate_qty = min($order_product['qty_needed_for_order'], $pop_product['outstanding_quantity']);
								prepared_query::execute('INSERT INTO purchase_order_to_order_allocations (purchase_order_product_id, order_product_id, quantity, modified, admin_id) VALUES (:purchase_order_product_id, :order_product_id, :quantity, NOW(), :admin_id)', [':purchase_order_product_id' => $pop_product['purchase_order_product_id'], ':order_product_id' => $order_product['orders_products_id'], ':quantity' => $allocate_qty, ':admin_id' => $_SESSION['login_id']]);

								$order_product['qty_needed_for_order'] -= $allocate_qty;

								if ($order_product['qty_needed_for_order'] <= 0) break;
							}
						}
					}
				}
			}
			if ($__FLAG['ajax']) exit();

			else CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$oID.'&action=edit');

			break;
		case 'de-allocate':
			if (in_array($_SESSION['login_groups_id'], [1, 8, 28, 20, 7, 29, 9, 18, 17, 5, 10])) {
				prepared_query::execute('DELETE FROM purchase_order_to_order_allocations WHERE id = :allocation_id', [':allocation_id' => $_REQUEST['allocation_id']]);
			}
			if ($__FLAG['ajax']) exit();

			else CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$oID.'&action=edit');

			break;
		case 'update_station':
			$_SESSION['station'] = $_GET['station'];
			exit();
			break;
		case 'void-invoice':
			$orders_id = $_GET['orders_id'];
			// only top administrators and select other users may move an order out of shipped
			if (!in_array($_SESSION['perms']['admin_groups_id'], [1])) die('You cannot move an order out of the shipped status');
			// kill the invoice and set the quantity back to the appropriate level
			$sales_order = new ck_sales_order($_GET['orders_id']);
			if ($sales_order->has_invoices()) {
				// only removing the first (latest) invoice, to copy the existing logic
				$invoice = new ck_invoice($sales_order->get_invoices()[0]['invoice_id']);
				$invoice->remove();
			}
			exit();
			break;
		case 'cancel-invoice':
			$invoice_id = $_POST['invoice_id'];

			try {
				$invoice = new ck_invoice($invoice_id);
				$credit_invoice = ck_invoice::create_credit_from_invoice($invoice);

				echo '1';
			}
			catch (Exception $e) {
				echo '0';
				print_r($e);
			}

			exit();
			break;
		case 'override-tax':
			$sales_order = new ck_sales_order($_REQUEST['orders_id']);

			if (is_numeric($_REQUEST['tax_status'])) {
				$sales_order->set_tax_exempt_status($__FLAG['tax_status']);
				$sales_order->refigure_totals();
				$sales_order->create_admin_note(['orders_note_text' => 'Taxable status updated by Admin: '.($__FLAG['tax_status']?'Turned On':'Turned Off'), 'orders_note_user' => $_SESSION['login_id']]);
			}
			else {
				$sales_order->unset_tax_exempt_status();
				$sales_order->refigure_totals();
				$sales_order->create_admin_note(['orders_note_text' => 'Taxable status updated by Admin: Default', 'orders_note_user' => $_SESSION['login_id']]);
			}

			CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$sales_order->id().'&action=edit');
			break;
		case 'fix-tax':
			$sales_order = new ck_sales_order($_REQUEST['orders_id']);
			$sales_order->refigure_totals();
			CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$sales_order->id().'&action=edit');
			break;
		case 'update_order':
			$context = 'update';

			$form_submit = !empty($_POST['form-submit'])?html_entity_decode($_POST['form-submit']):NULL;

			if (in_array($form_submit, ['Charge & Ship', 'Ship'])) $context = 'ship';
			elseif ($form_submit == 'Unship') $context = 'unship';
			elseif ($form_submit == 'Cancel') $context = 'cancel';
			elseif ($form_submit == 'Uncancel') $context = 'uncancel';
			elseif ($form_submit == 'Accounting Release') $context = 'accounting-release';

			$sales_order = new ck_sales_order($_GET['oID']);

			if (!$sales_order->is('released') && $context == 'ship') {
				$messageStack->add_session('You cannot ship an order on accounting hold.', 'error');
				CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$sales_order->id().'&action=edit');
			}

			switch ($context) {
				case 'update':
					try {
						$update = [];
						
						if (!empty($_POST['followup_date'])) {
							$followup_date = new DateTime($_POST['followup_date']);
							if ($followup_date != $sales_order->get_header('followup_date')) $update['followup_date'] = $followup_date->format('Y-m-d');
						}
						elseif (!empty($sales_order->get_header('followup_date'))) $update['followup_date'] = NULL;

						if (!empty($_POST['promised_ship_date'])) {
							$promised_ship_date = new DateTime($_POST['promised_ship_date']);
							if ($promised_ship_date != $sales_order->get_header('promised_ship_date')) $update['promised_ship_date'] = $promised_ship_date->format('Y-m-d');
						}
						elseif (!empty($sales_order->get_header('promised_ship_date'))) $update['promised_ship_date'] = NULL;

						if (!empty($update)) $sales_order->update($update);

						$status = [];

						if ($sales_order->is('released')) {
							if (!empty($_POST['status']) && $_POST['status'] != $sales_order->get_header('orders_status')) {
								if (in_array($sales_order->get_header('orders_status'), [3, 6])) {
									$messageStack->add_session('Warning: you cannot move orders to a new status from shipped or canceled except by certain approved processes.');
								}
								elseif (in_array($_POST['status'], [3, 6])) {
									$messageStack->add_session('Warning: you cannot move orders to shipped or canceled except by certain approved processes.');
								}
								else $status['orders_status_id'] = $_POST['status'];
							}

							if (!empty($_POST['sub_status']) && $_POST['sub_status'] != $sales_order->get_header('orders_sub_status')) {
								if (!in_array($sales_order->get_header('orders_status'), [3, 6]) && !in_array($_POST['status'], [3, 6])) $status['orders_sub_status_id'] = $_POST['sub_status'];
							}

							if (!empty($_POST['comments'])) $status['comments'] = $_POST['comments'];

							if (!empty($status)) {
								if (empty($status['orders_status_id'])) $status['orders_status_id'] = $sales_order->get_header('orders_status');
								if (empty($status['orders_sub_status_id'])) $status['orders_sub_status_id'] = $sales_order->get_header('orders_sub_status');

								$status['customer_notified'] = $__FLAG['notify'];

								$sales_order->update_order_status($status);
							}
						}

						if (empty($update) && empty($status) && empty($_POST['orders_note_text'])) $messageStack->add_session('Warning: Nothing to change. The order was not updated.', 'warning');
						else $messageStack->add_session('Success: Order has been successfully updated.', 'success');
					}
					catch (CKSalesOrderException $e) {
						$messageStack->add_session('Error updating order: '.$e->getMessage(), 'error');
					}
					catch (Exception $e) {
						$messageStack->add_session('Unknown error updating order: '.$e->getMessage(), 'error');
					}

					break;
				case 'ship':
					try {
						$messages = $sales_order->ship($__FLAG['notify'], @$_POST['comments']);

						foreach ($messages as $message) {
							$messageStack->add_session($message, 'success');
						}
					}
					catch (CKSalesOrderException $e) {
						$messageStack->add_session('Error shipping order: '.$e->getMessage(), 'error');
					}
					catch (Exception $e) {
						$messageStack->add_session('Unknown error shipping order: '.$e->getMessage(), 'error');
					}

					break;
				case 'unship':
					// only top administrators and select other users may move an order out of shipped
					if (!in_array($_SESSION['perms']['admin_groups_id'], [1]) && !in_array($_SESSION['perms']['admin_id'], [61, 81, 20, 72, 52, 48])) {
						$messageStack->add_session('You cannot move an order out of the shipped status', 'error');
						break;
					}

					try {
						$messages = $sales_order->unship($__FLAG['notify'], @$_POST['comments']);

						foreach ($messages as $message) {
							$messageStack->add_session($message, 'success');
						}
					}
					catch (CKSalesOrderException $e) {
						$messageStack->add_session('Error unshipping order: '.$e->getMessage(), 'error');
					}
					catch (Exception $e) {
						$messageStack->add_session('Unknown error unshipping order: '.$e->getMessage(), 'error');
					}
					break;
				case 'cancel':
					try {
						$messages = $sales_order->cancel($_POST['cancel_reason_id'], $__FLAG['notify'], @$_POST['comments']);

						foreach ($messages as $message) {
							$messageStack->add_session($message, 'success');
						}
					}
					catch (CKSalesOrderException $e) {
						$messageStack->add_session('Error cancelling order: '.$e->getMessage(), 'error');
					}
					catch (Exception $e) {
						$messageStack->add_session('Unknown error cancelling order: '.$e->getMessage(), 'error');
					}
					break;
				case 'uncancel':
					try {
						$sales_order->uncancel($__FLAG['notify'], @$_POST['comments']);

						$messageStack->add_session('Order successfully uncanceled by admin', 'success');
					}
					catch (CKSalesOrderException $e) {
						$messageStack->add_session('Error uncancelling order: '.$e->getMessage(), 'error');
					}
					catch (Exception $e) {
						$messageStack->add_session('Unknown error uncancelling order: '.$e->getMessage(), 'error');
					}
					break;
				case 'accounting-release':
					$sales_order->release();
					$messageStack->add_session('You have released this order from accounting hold.', 'success');
					break;
			}

			if (!empty($_POST['orders_note_text'])) {
				$admin_note = ['orders_note_text' => $_POST['orders_note_text'], 'orders_note_user' => $_SESSION['login_id']];
				if ($__FLAG['shipping_notice']) $admin_note['shipping_notice'] = 1;
				$sales_order->create_admin_note($admin_note);
			}

			CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$sales_order->id().'&action=edit');
			break;
		case 'assign-account-manager':
			$response = [];

			try {
				$sales_order->change_account_manager($_REQUEST['admin_id']);
				$response['account_manager'] = $sales_order->has_account_manager()?$sales_order->get_account_manager()->get_name():'None';
				$response['sales_team'] = $sales_order->has_sales_team()?$sales_order->get_sales_team()->get_header('label'):'None';
				$response['success'] = 1;
			}
			catch (CKSalesOrderException $e) {
				$response['error'] = $e->getMessage();
			}
			catch (Exception $e) {
				$response['error'] = 'Error assigning account manager: '.$e->getMessage();
			}

			echo json_encode($response);
			exit();
			break;
		case 'assign-sales-team':
			$response = [];

			try {
				$sales_order->change_sales_team($_REQUEST['sales_team_id']);
				$response['sales_team'] = $sales_order->has_sales_team()?$sales_order->get_sales_team()->get_header('label'):'None';
				$response['success'] = 1;
			}
			catch (CKSalesOrderException $e) {
				$response['error'] = $e->getMessage();
			}
			catch (Exception $e) {
				$response['error'] = 'Error assigning sales team: '.$e->getMessage();
			}

			echo json_encode($response);
			exit();
			break;
		case 'place-accounting-hold':
			$sales_order->hold();
			CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$sales_order->id().'&action=edit');
			break;
		case 'release-accounting-hold':
			$sales_order->release();
			CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$sales_order->id().'&action=edit');
			break;
		case 'email-invoice':
			(new ck_invoice($_POST['invoice_id']))->email_invoice_pdf();
			CK\fn::redirect_and_exit('/admin/orders_new.php?oID='.$sales_order->id().'&action=edit');
			break;
	}
}

if ($action == 'edit' && isset($_GET['oID'])) {
	$oID = (int) $_GET['oID'];

	try {
		$sales_order = new ck_sales_order($oID);
		$order_exists = $sales_order->found();

		if (!$order_exists) $messageStack->add(sprintf(ERROR_ORDER_DOES_NOT_EXIST, $oID), 'error');

		require_once(DIR_WS_CLASSES.'order_notes.php');

		$order_notes = new OrderNotes($sales_order->id()); // this does any necessary processing of the form - not ideal, but it'll work
	}
	catch (Exception $e) {
		$order_exists = FALSE;
		$messageStack->add('Fault pulling up order ID '.$oID.': '.$e->getMessage(), 'error');
	}
}

$admin_perms = prepared_query::fetch('SELECT * FROM admin WHERE admin_id = ?', cardinality::ROW, $_SESSION['login_id']);

/*---------------------------------
// begin page display
---------------------------------*/
if ($action == 'edit' && !empty($order_exists)) include_once('orders-detail.php');
else include_once('orders-list.php');
/*---------------------------------
// end page display
---------------------------------*/
?>
