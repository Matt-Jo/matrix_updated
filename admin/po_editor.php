<?php
require('includes/application_top.php');
require_once('includes/functions/po_alloc.php');

function po_send_email($poId, $poEmailToAddress, $poEmailFromAddress = 'purchasing@cablesandkits.com', $entity) {
    $mailer = service_locator::get_mail_service();
    $mail = $mailer->create_mail();
	$mail->set_from($poEmailFromAddress);
	$mail->set_subject('Purchase Order: '. $poId);

	ob_start();
	include('po_printable.php');
	$message = ob_get_clean();

	$mail->set_body($message);

	if ($entity == 0) $mail->create_attachment($message, 'CablesAndKits_PO_'.$poId.'.html');
    elseif ($entity == 1) $mail->create_attachment($message, 'Atlantech_Resellers_Inc_PO_'.$poId.'.html');

	$mail->add_to($poEmailToAddress);
	$mailer->send($mail);

	prepared_query::execute('UPDATE purchase_orders SET submit_date = NOW() WHERE id = :purchase_order_id', [':purchase_order_id' => $poId]);
}

$poId = !empty($_REQUEST['poId'])?$_REQUEST['poId']:NULL;

if (!empty($_POST['action']) && $_POST['action'] == 'add_note') {
	$po_note_id = prepared_query::insert('INSERT INTO purchase_order_notes (purchase_order_id, purchase_order_note_user, purchase_order_note_created, purchase_order_note_text) VALUES (:po_id, :admin_id, NOW(), :note)', [':po_id' => $_POST['poID'], ':admin_id' => $_SESSION['login_id'], ':note' => $_POST['notes']]);

	// response data for display
	$admin_name = $_SESSION['perms']['admin_firstname'].' '.$_SESSION['perms']['admin_lastname'];
	$data = [
		'note' => stripslashes($noteText),
		'created_on' => date('Y-m-d H:i:s'),
		'created_by' => $admin_name,
	];

	$po = new ck_purchase_order($_POST['poID']);
	// $po->send_new_po_note_notification($po_note_id);

	echo json_encode($data);
	die;
}
elseif (!empty($_POST['action']) && $_POST['action'] == 'vendor_data') {
	$vendor_id = $_POST['vendor_id'];
	$ipn_ids = !empty($_POST['ipn_ids'])?$_POST['ipn_ids']:NULL;

	$result_data = [];
	if (is_array($ipn_ids)) {
		$vtsi_query = "select vtsi.* from vendors_to_stock_item vtsi where stock_id in (". implode(',', $ipn_ids).") and vendors_id = '$vendor_id'";
		$results = prepared_query::fetch($vtsi_query, cardinality::SET);
		foreach ($results as $unused => $result) {
			if ($result_data[$result['stock_id']]) {
				continue;
			}
			$result_data[$result['stock_id']] = array(
				'pn' => $result['vendors_pn'],
				'cost' => $result['vendors_price']
			);
		}
	}

	$abv_query = "select abv.entry_street_address, abv.entry_suburb, abv.entry_postcode, abv.entry_state, abv.entry_city, abv.entry_country_id, abv.entry_zone_id, c.countries_name from address_book_vendors abv left join countries c on (abv.entry_country_id = c.countries_id) where abv.vendors_id = '$vendor_id'";
	$results = prepared_query::fetch($abv_query, cardinality::SET);
	$state_string = ck_address2::legacy_get_zone_field($results[0]['entry_country_id'], $results[0]['entry_zone_id'], $results[0]['entry_state'], 'zone_name');
	$result_data['vendor_address'] = $results[0]['entry_street_address']."<br>";
	if (trim($results[0]['entry_suburb']) != '') $result_data['vendor_address'] .= $results[0]['entry_suburb']."<br>";
	$result_data['vendor_address'] .= $results[0]['entry_city'].", ".$state_string." ".$results[0]['entry_postcode']."<br>".$results[0]['countries_name'];
	echo json_encode($result_data);
	die();
}

if ($_GET['action'] == 'generate_po_number') {
	$id = prepared_query::fetch("SELECT id from purchase_orders ORDER BY id DESC LIMIT 1", cardinality::SINGLE);
	echo $id + 1;
	die;
}

if (!empty($poId)) $po = new ck_purchase_order($poId);

$action = !empty($_GET['action'])?$_GET['action']:NULL;
$method = !empty($_GET['method'])?$_GET['method']:NULL;
$polist = !empty($_GET['po_list'])?$_GET['po_list']:NULL;

if (empty($action)) die('action not set');

switch ($action) {
	case 'new':
		break;
	case 'edit':
		break;
	case 'create':
		$creator_admin_id = $_SESSION['login_id'];
		$administrator_admin_id = !empty($_POST['administrator_admin_id'])?$_POST['administrator_admin_id']:$creator_admin_id;
		$owner_admin_id = !empty($_POST['owner_admin_id'])?$_POST['owner_admin_id']:$creator_admin_id;

		$buyback_admin_id = !empty($_POST['buyback_admin_id'])?$_POST['buyback_admin_id']:NULL;

		$status = 1; //set this to 'open';
		$drop_ship = $__FLAG['drop_ship']?1:0;
		$submit_date = isset($_POST['submit_date'])?new DateTime($_POST['submit_date']):NULL;
		$expected_date = isset($_POST['expected_date'])? $_POST['expected_date'] : null;
		$followup_date = isset($_POST['followup_date'])? $_POST['followup_date'] : null;
		$vendor = $_POST['vendor'];
		$purchase_order_number = $_POST['purchase_order_number'];
		$shipping_method = $_POST['shipping_method'];
		$terms = $_POST['terms'];
		$notes = $_POST['vendor_notes'];
		$entity = $_POST['entity'];

		$show_vendor_pn = (isset($_POST['show_vendor_product']) && $_POST['show_vendor_product']=='on') ? true : false;

		$po_id = prepared_query::insert('INSERT INTO purchase_orders (purchase_order_number, status, creator, administrator_admin_id, owner_admin_id, buyback_admin_id, vendor, creation_date, submit_date, expected_date, drop_ship, followup_date, shipping_method, notes, terms, show_vendor_pn, entity_id) VALUES (:purchase_order_number, :status, :creator_admin_id, :administrator_admin_id, :owner_admin_id, :buyback_admin_id, :vendor, NOW(), :submit_date, :expected_date, :drop_ship, :followup_date, :shipping_method, :notes, :terms, :show_vendor_pn, :entity_id)', [':purchase_order_number' => $purchase_order_number, ':status' => $status, ':creator_admin_id' => $creator_admin_id, ':administrator_admin_id' => $administrator_admin_id, ':owner_admin_id' => $owner_admin_id, ':buyback_admin_id' => $buyback_admin_id, ':vendor' => $vendor, ':expected_date' => date('Y-m-d', strtotime($expected_date)), ':submit_date' => !empty($submit_date)?$submit_date->format('Y-m-d'):NULL, ':drop_ship' => $drop_ship, ':followup_date' => date('Y-m-d', strtotime($followup_date)), ':shipping_method' => $shipping_method, ':notes' => $notes, ':terms' => $terms, ':show_vendor_pn' => ($show_vendor_pn ? $show_vendor_pn : 0), ':entity_id' => $entity]);

		if (!empty($_POST['ipn'])) {
			foreach ($_POST['ipn'] as $key => $ipn_id) {
				$description = $_POST['description'][$key];
				$quantity = $_POST['quantity'][$key];
				$cost = $_POST['cost'][$key];

				prepared_query::execute("insert into purchase_order_products (purchase_order_id, ipn_id, quantity, cost, description) values (:po_id, :ipn_id, :quantity, :cost, :description)", [':po_id' => $po_id, ':ipn_id' => $ipn_id, ':quantity' => $quantity, ':cost' => $cost, ':description' => $description]);

				prepared_query::execute("update products_stock_control psc set psc.on_order = (psc.on_order + :quantity) where psc.stock_id = :stock_id", [':quantity' => $quantity, ':stock_id' => $ipn_id]);
			}
		}

		//add a note to the PO
		$noteText = addslashes($_POST['notes']);
		prepared_query::execute('insert into purchase_order_notes (purchase_order_id, purchase_order_note_user, purchase_order_note_text, purchase_order_note_created) values (:po_id, :admin_id, :note, NOW())', [':po_id' => $po_id, ':admin_id' => $creator_admin_id, ':note' => $noteText]);

		if (!empty($_POST['confirmation_requested'])) {
			$_GET['confirmation'] = 'yes';//do this for po_printable.php
			prepared_query::execute("update purchase_orders po set po.confirmation_status = '1', po.confirmation_hash=:hash where po.id = :po_id", [':hash' => md5(uniqid(rand(), true)), ':po_id' => $po_id]);
		}

		//MMD - logic for sending email
		if (!empty($_POST['email_check'])) {
			//hack this - J2EE wouldn't allow it :-)
			$_GET['poId'] = $po_id;
			po_send_email($po_id, $_POST['email_to_address'], $_POST['email_from_address'], $_POST['entity']);
		}

		//do vendor product update
		if ($_POST['show_vendor_product'] == 'on') {
			$new_v2s = !empty($_POST['vendors_pn']['new'])?$_POST['vendors_pn']['new']:[];
			if (count($new_v2s)) {
				foreach ($new_v2s as $ipn => $vendors_pn) {
					prepared_query::execute("insert into vendors_to_stock_item set vendors_id=:vendors_id, stock_id=:stock_id, vendors_pn=:vendors_pn", [':vendors_id' => $_POST['vendor'], ':stock_id' => $ipn, ':vendors_pn' => $vendors_pn]);
				}
			}
			unset($_POST['vendors_pn']['new']);
			if (!empty($_POST['vendors_pn'])) {
				foreach ($_POST['vendors_pn'] as $v2s_id => $vendors_pn) {
					prepared_query::execute("update vendors_to_stock_item set vendors_pn=:vendors_pn where id=:id", [':vendors_pn' => $vendors_pn, ':id' => $v2s_id]);
				}
			}

			$vendor_id = prepared_query::fetch('select vendor from purchase_orders po where id=:po_id', cardinality::SINGLE, [':po_id' => $po_id]);
			$po_products_query_response = prepared_query::fetch("select pop.id, pop.quantity, pop.description, pop.cost, pop.ipn_id, psc.stock_name, sum(porp.quantity_received) as quantity_received, v2s.vendors_pn, v2s.vendors_price, v2s.id as v2s_id, v2s.vendors_id, v2s.stock_id as v2s_ipn from purchase_order_products pop left join products_stock_control psc on pop.ipn_id = psc.stock_id left join purchase_order_received_products porp on pop.id = porp.purchase_order_product_id left join purchase_orders po on po.id=:po_id left join vendors_to_stock_item v2s on (pop.ipn_id=v2s.stock_id and v2s.vendors_id=po.vendor) where pop.purchase_order_id = :po_id group by pop.id", cardinality::SET, [':po_id' => $po_id]);
			$v2s=@$_POST['v2s'];
			$vendor_update=[];
			$vendor_update_price=[];
			$vendor_update_pn=[];
			$po_products=[];
			foreach ($po_products_query_response as $result) {
				foreach ($v2s as &$v) {
					if ($v['ipn_id']==$result['ipn_id']) {
						$v['v2s_id']=$result['v2s_id'];
						$v['stock_name']=$result['stock_name'];
						$v['old_pn']=$result['vendors_pn'];
						$v['vendors_price']=$result['vendors_price'];
						$v['cost']=$result['cost'];
						if (!isset($v['v2s_id'])) {
							$vendor_table_update_form=true;
							$vendor_update[]=$v;
						}
						else {
							if ($v['vendors_pn']!=$v['old_pn']) {
								$vendor_table_update_form=true;
								$vendor_update_pn[]=$v;
							}
							if ($v['cost'] != $v['vendors_price']) {
								$vendor_table_update_form=true;
								$vendor_update_price[]=$v;
							}
						}
						continue;
					}
				}
			}
		}

		if (empty($vendor_table_update_form) && empty($_FILES)) CK\fn::redirect_and_exit('/admin/po_list.php');
		break;
	case 'save-upload':
		$columns = [];
		foreach ($_POST['spreadsheet_column'] as $column_idx => $field) {
			if ($field == '0') continue;
			$columns[$field] = $column_idx;
		}

		foreach ($_POST['spreadsheet_field'] as $row_idx => $row) {
			$ipn = $row[$columns['ipn']];
			$qty = preg_replace('/[^0-9]/', '', $row[$columns['qty']]);
			$cost = preg_replace('/[^0-9.]/', '', $row[$columns['cost']]);

			$desc = !empty($row[@$columns['desc']])?$row[$columns['desc']]:NULL;
			//$vpn = !empty($row[$columns['vpn']])?$row[$columns['vpn']]:NULL;

			prepared_query::execute('INSERT INTO purchase_order_products (purchase_order_id, ipn_id, quantity, cost, description) SELECT :po_id, psc.stock_id, :qty, :cost, IFNULL(:desc, psc.stock_description) FROM products_stock_control psc WHERE psc.stock_name LIKE :ipn', [':po_id' => $poId, ':qty' => $qty, ':cost' => $cost, ':desc' => $desc, ':ipn' => $ipn]);

			prepared_query::execute('UPDATE products_stock_control SET on_order = on_order + :qty WHERE stock_name LIKE :ipn', [':qty' => $qty, ':ipn' => $ipn]);
		}

		CK\fn::redirect_and_exit('/admin/po_editor.php?action=edit&poId='.$poId);
		break;
	case 'save':
		$administrator_admin_id = $_POST['administrator_admin_id'];
		$owner_admin_id = $_POST['owner_admin_id'];
		$buyback_admin_id = !empty($_POST['buyback_admin_id'])?$_POST['buyback_admin_id']:NULL;
		$drop_ship = $__FLAG['drop_ship']?1:0;
		$submit_date = isset($_POST['submit_date'])?new DateTime($_POST['submit_date']):NULL;
		$expected_date = isset($_POST['expected_date'])? $_POST['expected_date'] : null;
		$followup_date = isset($_POST['followup_date'])? $_POST['followup_date'] : null;
		$purchase_order_number = $_POST['purchase_order_number'];
		$shipping_method = $_POST['shipping_method'];
		$terms = $_POST['terms'];
		$notes = $_POST['vendor_notes'];
		$show_vendor_pn= (isset($_POST['show_vendor_product']) && $_POST['show_vendor_product']=='on') ? true : false;

		prepared_query::execute('UPDATE purchase_orders SET purchase_order_number = :purchase_order_number, shipping_method = :shipping_method, drop_ship = :drop_ship, submit_date = :submit_date, expected_date = :expected_date, followup_date = :followup_date, terms = :terms, notes = :notes, show_vendor_pn = :show_vendor_pn, administrator_admin_id = :administrator_admin_id, owner_admin_id = :owner_admin_id, buyback_admin_id = :buyback_admin_id WHERE id = :purchase_order_id', [':purchase_order_number' => $purchase_order_number, ':shipping_method' => $shipping_method, ':drop_ship' => $drop_ship, ':submit_date' => !empty($submit_date)?$submit_date->format('Y-m-d'):NULL, ':expected_date' => date('Y-m-d', strtotime($expected_date)), ':followup_date' => date('Y-m-d', strtotime($followup_date)), ':terms' => $terms, ':notes' => $notes, ':show_vendor_pn' => $show_vendor_pn, ':administrator_admin_id' => $administrator_admin_id, ':owner_admin_id' => $owner_admin_id, ':buyback_admin_id' => $buyback_admin_id, ':purchase_order_id' => $poId]);

		//now we have to do special logic for updating the products. we will have to either:
		// 1. insert any new products on the order
		// 2. update any products previously on the order that are still on the order
		// 3. remove any products previously on the order that are no longer on the order.
		//we will do this by creating a list of products currently on the order and list of
		//products previously on the order and comparing them
		$current_keys = [];
		$previous_keys = [];
		if (!empty($_POST['ipn'])) {
			foreach ($_POST['ipn'] as $key => $ipn_id) {
				$current_keys[] = "".$key;
			}
		}
		$previous_ipns = prepared_query::fetch("select id from purchase_order_products where purchase_order_id = :po_id", cardinality::COLUMN, [':po_id' => $poId]);
		foreach ($previous_ipns as $previous_ipn) {
			$previous_keys[] = $previous_ipn;
		}

		//now we calculate the ipns to delete from this PO and delete them
		for ($i = 0, $n = count($previous_keys); $i < $n; $i++) {
			if (!in_array($previous_keys[$i], $current_keys)) {
				//first we have to look up the old qty for this pop so we know how much to decrement the on_order field by
				$qty_lookup = prepared_query::fetch('select pop.quantity, pop.ipn_id from purchase_order_products pop where pop.purchase_order_id = :po_id and id = :pop_id', cardinality::ROW, [':po_id' => $poId, ':pop_id' => $previous_keys[$i]]);

				//now we delete the pop from the po
				prepared_query::execute('delete from purchase_order_products where purchase_order_id = :po_id and id = :pop_id', [':po_id' => $poId, ':pop_id' => $previous_keys[$i]]);

				//no we decrease the on_order quantity
				prepared_query::execute('update products_stock_control psc set psc.on_order = (psc.on_order - :quantity) where psc.stock_id = :stock_id', [':quantity' => $qty_lookup['quantity'], ':stock_id' => $qty_lookup['ipn_id']]);
				//MMD - don't forget to remove order allocations
				po_alloc_remove_by_pop_id($previous_keys[$i]);
			}
		}

		//now we calculate the ipns to add to this PO
		for ($i = 0, $n = count($current_keys); $i < $n; $i++) {
			if (!in_array($current_keys[$i], $previous_keys)) {
				$ipn_id = $_POST['ipn'][$current_keys[$i]];
				$description = $_POST['description'][$current_keys[$i]];
				$quantity = $_POST['quantity'][$current_keys[$i]];
				$cost = $_POST['cost'][$current_keys[$i]];

				if (empty($ipn_id) || $ipn_id == '0' || empty($quantity) || $quantity == '0') continue;

				prepared_query::execute('INSERT INTO purchase_order_products (purchase_order_id, ipn_id, quantity, cost, description) VALUES (:purchase_order_id, :stock_id, :qty, :cost, :description)', [':purchase_order_id' => $poId, ':stock_id' => $ipn_id, ':qty' => $quantity, ':cost' => $cost, ':description' => $description]);

				//now we increase the on_order quantity
				prepared_query::execute('UPDATE products_stock_control psc SET psc.on_order = (psc.on_order + :qty) WHERE psc.stock_id = :stock_id', [':qty' => $quantity, ':stock_id' => $ipn_id]);
			}
		}

		//now we calculate the ipns to update on this PO
		$update_keys = array_intersect($previous_keys, $current_keys);
		$temp_array = $update_keys;
		$update_keys = [];
		foreach ($temp_array as $unused => $value) {
			$update_keys[] = $value;
		}
		for ($i = 0, $n = count($update_keys); $i < $n; $i++) {
			$description = $_POST['description'][$update_keys[$i]];
			$quantity = $_POST['quantity'][$update_keys[$i]];
			$cost = $_POST['cost'][$update_keys[$i]];

			//first we have to look up the old qty for this pop so we know how much to decrement the on_order field by
			$qty_lookup = prepared_query::fetch('select pop.ipn_id, pop.quantity from purchase_order_products pop where pop.purchase_order_id = :po_id and id = :pop_id', cardinality::ROW, [':po_id' => $poId, ':pop_id' => $update_keys[$i]]);

			//now we calculate the delta in the qtys
			$dQuantity = $quantity - $qty_lookup['quantity'];

			//now we update the pop
			prepared_query::execute("update purchase_order_products set quantity = :qty, cost = :cost, description = :desc where purchase_order_id = :po_id and id = :pop_id", [':qty' => $quantity, ':cost' => $cost, ':desc' => $description, ':po_id' => $poId, 'pop_id' => $update_keys[$i]]);

			//now we update the cost for any serials on open reviews
			$review_serials = prepared_query::fetch("select sh.serial_id from serials_history sh where sh.pors_id = '' and sh.pop_id = :pop_id", cardinality::SET, [':pop_id' => $update_keys[$i]]);
			foreach ($review_serials as $review_serial) {
				$mySerial = new ck_serial($review_serial['serial_id']);
				$mySerialHistory = $mySerial->get_current_history();
				$mySerial->update_history_record($mySerialHistory['serial_history_id'], ['cost' => $cost]);
			}

			//now we update the porp if any exist
			prepared_query::execute("update purchase_order_received_products set quantity_remaining = (:qty - quantity_received) where purchase_order_product_id = :pop_id", [':qty' => $quantity, ':pop_id' => $update_keys[$i]]);

			//now we update the on_order quantity
			prepared_query::execute('update products_stock_control psc set psc.on_order = (psc.on_order + :qty) where psc.stock_id = :stock_id', [':qty' => $dQuantity, ':stock_id' => $qty_lookup['ipn_id']]);
			if ($dQuantity != '0') {
				po_alloc_remove_by_pop_id($update_keys[$i]);
			}
		}

		//now we check if the PO should be flagged 'received' because of the edit process
		$check_po_query_response = prepared_query::fetch("select pop.id, pop.quantity, sum(porp.quantity_received) as quantity_received from purchase_order_products pop left join purchase_order_received_products porp on pop.id = porp.purchase_order_product_id where pop.purchase_order_id = :po_id group by pop.id", cardinality::SET, [':po_id' => $poId]);

		$po_filled = true;
		foreach ($check_po_query_response as $check_po) {
			if ($check_po['quantity'] > $check_po['quantity_received']) {
				$po_filled = false;
				break;
			}
		}

		if ($po_filled) {
			//set the order status to 'received'
			prepared_query::execute("update purchase_orders set status = 3 where id = :po_id", [':po_id' => $poId]);
		}

		//add a note to the PO
		$creator_admin_id = $_SESSION['login_id'];
		prepared_query::execute('INSERT INTO purchase_order_notes (purchase_order_id, purchase_order_note_user, purchase_order_note_text, purchase_order_note_created) VALUES (:po_id, :creator_admin_id, :note, NOW())', [':po_id' => $poId, ':creator_admin_id' => $creator_admin_id, ':note' => $_POST['notes']]);

		if (!empty($_POST['confirmation_requested'])) {
			$_GET['confirmation'] = 'yes';//do this for po_printable.php
			prepared_query::execute("update purchase_orders po set po.confirmation_status = '1', po.confirmation_hash=:hash where po.id = :po_id", [':hash' => md5(uniqid(rand(), true)), ':po_id' => $poId]);
		}

		if (!empty($_POST['email_check'])) {
			po_send_email($poId, $_POST['email_to_address'], $_POST['email_from_address'], $_POST['entity']);
		}

		//do vendor product update
		if ($_POST['show_vendor_product']=='on') {
			$vendor_id = prepared_query::fetch('select vendor from purchase_orders po where id=:po_id', cardinality::SINGLE, [':po_id' => $poId]);
			
			$po_products_query_response = prepared_query::fetch("select pop.id, pop.quantity, pop.description, pop.cost, pop.ipn_id, psc.stock_name, sum(porp.quantity_received) as quantity_received, v2s.vendors_pn, v2s.vendors_price, v2s.id as v2s_id, v2s.vendors_id, v2s.stock_id as v2s_ipn from purchase_order_products pop left join products_stock_control psc on pop.ipn_id = psc.stock_id left join purchase_order_received_products porp on pop.id = porp.purchase_order_product_id left join purchase_orders po on po.id=:po_id left join vendors_to_stock_item v2s on (pop.ipn_id=v2s.stock_id and v2s.vendors_id=po.vendor) where pop.purchase_order_id = :po_id group by pop.id", cardinality::SET, [':po_id' => $poId]);
			$v2s=@$_POST['v2s'];
			$vendor_update=[];
			$vendor_update_price=[];
			$vendor_update_pn=[];
			$po_products=[];
			foreach ($po_products_query_response as $result) {
				foreach ($v2s as &$v) {
					if ($v['ipn_id']==$result['ipn_id']) {
						$v['v2s_id']=$result['v2s_id'];
						$v['stock_name']=$result['stock_name'];
						$v['old_pn']=$result['vendors_pn'];
						$v['vendors_price']=$result['vendors_price'];
						$v['cost']=$result['cost'];

						if (!isset($v['v2s_id'])) {
							$vendor_table_update_form=true;
							$vendor_update[]=$v;
						}
						else {
							if ($v['vendors_pn']!=$v['old_pn']) {
								$vendor_table_update_form=true;
								$vendor_update_pn[]=$v;
							}
							if ($v['cost'] != $v['vendors_price']) {
								$vendor_table_update_form=true;
								$vendor_update_price[]=$v;
							}
						}
						continue;
					}
				}
			}
		}
		if (empty($vendor_table_update_form) && empty($_FILES)) CK\fn::redirect_and_exit('/admin/po_list.php');
		break;
		/*
		For bug 256 & 258: update vendors_to_stock_item

		*/
	case 'update_vendors':
		$vendor_id = $_POST['vendor_id'];
		$vendor = $_POST['vendor'];
		if (isset($vendor['new'])) {
			foreach ($vendor['new'] as $stock_id) {
				$data = $vendor['ipn'][$stock_id];

				$ipn = new ck_ipn2($stock_id);
				$ipn->create_vendor_relationship(['vendors_id' => $vendor_id, 'vendors_pn' => $data['vendors_pn'], 'vendors_price' => $data['price'], 'preferred' => CK\fn::check_flag(@$data['preferred'])?1:0]);
			}
		}
		if (isset($vendor['update'])) {
			foreach ($vendor['update'] as $vtsi_id) {
				$data = $vendor['v2s'][$vtsi_id];

				$vtsi = prepared_query::fetch('SELECT stock_id, vendors_pn, vendors_price FROM vendors_to_stock_item WHERE id = :vtsi_id', cardinality::ROW, [':vtsi_id' => $vtsi_id]);

				$ipn = new ck_ipn2($vtsi['stock_id']);

				unset($vtsi['stock_id']);

				if (!empty($data['pn'])) $vtsi['vendors_pn'] = $data['pn'];
				else unset($vtsi['vendors_pn']);

				if (!empty($data['price'])) $vtsi['vendors_price'] = $data['price'];
				else unset($vtsi['vendors_price']);

				if (CK\fn::check_flag(@$data['preferred'])) $vtsi['preferred'] = 1;

				$ipn->update_vendor_relationship($vtsi_id, $vtsi);
			}
		}
		CK\fn::redirect_and_exit('/admin/po_list.php');
		break;
}

$vendor_array = prepared_query::fetch('SELECT vendors_id, vendors_company_name, vendors_email_address, company_payment_terms FROM vendors ORDER BY vendors_company_name ASC', cardinality::SET);
$po_shipping_array = prepared_query::fetch('SELECT id as shipping_id, text as shipping_type FROM purchase_order_shipping ORDER BY sort_order ASC', cardinality::SET);
$po_terms_array = prepared_query::fetch('SELECT id as terms_id, text as terms_type FROM purchase_order_terms ORDER BY text ASC', cardinality::SET);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
		<title><?= TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<link rel="stylesheet" type="text/css" href="<?= DIR_WS_CATALOG; ?>yui/build/tabview/assets/border_tabs.css">
		<script type="text/javascript" src="<?= DIR_WS_CATALOG; ?>yui/build/yahoo/yahoo.js"></script>
		<script type="text/javascript" src="<?= DIR_WS_CATALOG; ?>yui/build/event/event.js"></script>
		<script type="text/javascript" src="<?= DIR_WS_CATALOG; ?>yui/build/connection/connection.js"></script>
		<script type="text/javascript" src="<?= DIR_WS_CATALOG; ?>yui/build/dom/dom.js"></script>
		<script type="text/javascript" src="<?= DIR_WS_CATALOG; ?>yui/build/element/element-beta.js"></script>
		<script type="text/javascript" src="<?= DIR_WS_CATALOG; ?>yui/build/autocomplete/autocomplete.js"></script>
		<style type="text/css">
			table.vendor_table { width: 650px; border: 1px solid #000; background-color:}
			table.vendor_table tr { #999; padding: 5px;}
			table.vendor_table tr td { min-width: 130px; background-color: #ccc; text-align: center}
		</style>
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<!-- header //-->
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<script language="javascript" src="includes/general.js"></script>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#edit_submit-date').click(function(event) {
					event.preventDefault();
					var buttonContainer = $(this).parent();
					buttonContainer.hide();
					var val = $('#submit-date-container').html();
					$('#submit-date-container').html('<input id="submit-date-js" type="text" value="" >');
					$('#submit-date-js').val(val);
					$('#submit-date-js').datepicker({
						dateFormat: 'mm/dd/yy',
						onSelect:	function(dateText, instance) {
							$.post('po_viewer.php', {
								action: 'update_submit_date',
								id: $('#po-id-hidden').val(),
								submit_date: dateText
							}, function(data) {
								if (data.error) {
									alert(data.error);
									return;
								}
							}, "json");

							$('#submit-date-container').html(dateText);
							$('#submit-date').val(dateText);
							buttonContainer.show();
						}
					});
				});
				$('#clear_submit-date').click(function(event) {
					event.preventDefault();
					$.post('po_viewer.php', {
						action: 'update_submit_date',
						id: $('#po-id-hidden').val(),
						submit_date: 'CLEAR'
					},
					function(data) {
						if (data.error) {
							alert(data.error);
							return;
						}
					}, "json");

					$('#submit-date-container').html('');
				});

				$('#edit_expected-date').click(function(event) {
					event.preventDefault();
					var buttonContainer = $(this).parent();
					buttonContainer.hide();
					var val = $('#expected-date-container').html();
					$('#expected-date-container').html('<input id="expected-date-js" type="text" value="" >');
					$('#expected-date-js').val(val);
					$('#expected-date-js').datepicker({
						dateFormat: 'mm/dd/yy',
						minDate: +0,
						onSelect:	function(dateText, instance) {
							$.post('po_viewer.php', {
								action: 'update_expected_date',
								id: $('#po-id-hidden').val(),
								expected_date: dateText
							}, function(data) {
								if (data.error) {
									alert(data.error);
									return;
								}
							}, "json");

							$('#expected-date-container').html(dateText);
							$('#expected-date').val(dateText);
							buttonContainer.show();
						}
					});
				});
				$('#clear_expected-date').click(function(event) {
					event.preventDefault();
					$.post('po_viewer.php', {
						action: 'update_expected_date',
						id: $('#po-id-hidden').val(),
						expected_date: 'CLEAR'
					},
					function(data) {
						if (data.error) {
							alert(data.error);
							return;
						}
					}, "json");

					$('#expected-date-container').html('');
				});

				$('#edit_followup-date').click(function(event) {
					event.preventDefault();
					var buttonContainer = $(this).parent();
					buttonContainer.hide();
					var val = $('#followup-date-container').html();
					$('#followup-date-container').html('<input id="followup-date-js" type="text" value="" >');
					$('#followup-date-js').val(val);
					$('#followup-date-js').datepicker({
						dateFormat: 'mm/dd/yy',
						minDate: +0,
						onSelect:	function(dateText, instance) {
							$.post('po_viewer.php', {
								action: 'update_followup_date',
								id: $('#po-id-hidden').val(),
								followup_date: dateText
							}, function(data) {
								if (data.error) {
									alert(data.error);
									return;
								}
							}, "json");

							$('#followup-date-container').html(dateText);
							$('#followup-date').val(dateText);
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
				<td width="100%" valign="top">
					<!------------------------------------------------------------------- -->
					<?php if (isset($vendor_table_update_form)) { ?>
					<form method="post" action="/admin/po_editor.php?action=update_vendors">
						<input type="hidden" name="vendor_id" value="<?= $vendor_id; ?>">
						<?php if (count($vendor_update)) { ?>
						<p class="main">The following IPNs are not associated with this vendor. Would you like to add these associations?</p>
						<table class="vendor_table">
							<tr>
								<th class="main">Add</th>
								<th class="main">Preferred</th>
								<th class="main">IPN</th>
								<th class="main">Price</th>
								<th class="main">PN</th>
							</tr>
							<?php foreach ($vendor_update as $v) { ?>
							<tr>
								<td class="main"><input type="checkbox" name="vendor[new][]" value="<?= $v['ipn_id']; ?>"></td>
								<td class="main"><input type="checkbox" name="vendor[ipn][<?= $v['ipn_id']; ?>][preferred]" ></td>
								<td class="main"><?= $v['stock_name']; ?></td>
								<td class="main"><?= $v['cost']; ?><input type="hidden" name="vendor[ipn][<?= $v['ipn_id']; ?>][price]" value="<?= $v['cost']; ?>"></td>
								<td class="main"><?= $v['vendors_pn']; ?><input type="hidden" name="vendor[ipn][<?= $v['ipn_id']; ?>][vendors_pn]" value="<?= $v['vendors_pn']; ?>"></td>
							</tr>
							<?php } ?>
						</table>
						<?php }
				
						if (count($vendor_update_price)) { ?>
						<p class="main">The prices on this PO for the following IPNs do not match this vendor's pricing. Would you like to update the vendor pricing?</p>
						<table class="vendor_table">
							<tr>
								<th class="main">Update Price</th>
								<th class="main">Preferred</th>
								<th class="main">IPN</th>
								<th class="main">Vendor Price</th>
								<th class="main">PO Price</th>
							</tr>
							<?php foreach ($vendor_update_price as $v) { ?>
							<tr>
								<td class="main"><input type="checkbox" name="vendor[update][]" value="<?= $v['v2s_id']; ?>"></td>
								<td class="main"><input type="checkbox" name="vendor[v2s][<?= $v['v2s_id']; ?>][preferred]" ></td>
								<td class="main"><?= $v['stock_name']; ?></td>
								<td class="main"><?= $v['vendors_price'] ? $v['vendors_price'] :'Not Set '?></td>
								<td class="main"><?= $v['cost']; ?><input type="hidden" name="vendor[v2s][<?= $v['v2s_id']; ?>][price]" value="<?= $v['cost']; ?>"></td>
							</tr>
							<?php } ?>
						</table>
						<?php }

						if (count($vendor_update_pn)) { ?>
						<p class="main">The PN on this PO for the following IPNs do not match this vendor's PN. Would you like to update the vendor PN?</p>
						<table class="vendor_table">
							<tr>
								<th class="main">Update P/N</th>
								<th class="main">Preferred</th>
								<th class="main">IPN</th>
								<th class="main">Vendor PN</th>
								<th class="main">PO PN</th>
							</tr>
							<?php foreach ($vendor_update_pn as $v) { ?>
							<tr>
								<td class="main"><input type="checkbox" name="vendor[update][]" value="<?= $v['v2s_id']; ?>"></td>
								<td class="main"><input type="checkbox" name="vendor[v2s][<?= $v['v2s_id']; ?>][preferred]" ></td>
								<td class="main"><?= $v['stock_name']; ?></td>
								<td class="main"><?= $v['old_pn'] ? $v['old_pn'] : 'Not Set'?><input type="hidden" name="vendor[v2s][<?= $v['v2s_id']; ?>][pn]" value="<?= $v['vendors_pn']; ?>"></td>
								<td class="main"><?= $v['vendors_pn']; ?></td>
							</tr>
							<?php } ?>
						</table>
						<?php } ?>
						<input type="submit" value="Save">
						<input type="button" onclick="window.location='po_editor.php?action=new';" value="New Purchase Order">
					</form>
					<?php }
					else { ?>
					<style>
						table.products_table td{ padding: 5px;}
						table.new_products {border: 1px solid #000;}
						table.new_products tr {background-color: #ebebeb; padding: 3px;}
						table.new_products tr td {padding: 5px; min-width: 50px;}
					</style>

					<?php if (!empty($po) && $review_sessions = $po->get_locked_reviews()) { ?>
					<form method="post" action="/admin/po_receiver.php">
						<input type="hidden" name="poId" value="<?= $po->id(); ?>">
						<input type="hidden" name="action" value="complete-review-session">
						<?php if (!empty($_SESSION['review_error'])) { ?>
						<p style="color:#c00; font-weight:bold;"><?= $_SESSION['review_error']; ?></p>
							<?php unset($_SESSION['review_error']);
						}

						foreach ($review_sessions as $review_session) { ?>
						<div style="background-color:#f96; padding:8px; margin-bottom:8px;">
							<h3 style="font-family: verdana; margin:0px 0px 3px 0px;">Items Under Review [<?= $review_session['created_on']->format('m/d/Y'); ?>]:</h3>
							<table class="new_products">
								<tr>
									<td>PO Number</td>
									<td>Tracking #</td>
									<td>Created On</td>
									<td>Created By</td>
									<td>Notes</td>
								</tr>
								<tr>
									<td><?= $po->get_header('purchase_order_number'); ?></td>
									<td><?= !empty($review_session['tracking_number'])?$review_session['tracking_number']:'NONE'; ?></td>
									<td><?= $review_session['created_on']->format('m/d/Y'); ?></td>
									<td><?= $review_session['creator_firstname'].' '.$review_session['creator_lastname']; ?></td>
									<td><?= nl2br($review_session['notes']); ?></td>
								</tr>
							</table>
							<br>

							<table class="new_products">
								<tr>
									<td>&nbsp;</td>
									<td>Stock Name</td>
									<td>Qty</td>
									<td>Cost</td>
									<td>Freebie</td>
									<td>Serial</td>
								</tr>
								<?php foreach ($po->get_reviewing_products($review_session['po_review_id']) as $rp) {
									if ($rp['status'] != ck_purchase_order::$review_product_statuses['NEW_PRODUCT']) continue;
									$pop = $po->get_products($rp['po_product_id']); ?>
								<tr style="<?= empty($pop['cost'])?'background-color:#f96;':''; ?>">
									<td><input type="checkbox" name="reviews[<?= $review_session['po_review_id']; ?>][<?= $rp['po_product_id']; ?>][approve]" class="approve_checkbox"></td>
									<td><?= $rp['ipn']->get_header('ipn'); ?></td>
									<td><?= $rp['qty_received']?$rp['qty_received']:1; ?></td>
									<td><input type="text" size="4" name="reviews[<?= $review_session['po_review_id']; ?>][<?= $rp['po_product_id']; ?>][cost]" value="<?= !empty($pop['cost'])?$pop['cost']:0; ?>"></td>
									<td><input type="checkbox" name="reviews[<?= $review_session['po_review_id']; ?>][<?= $rp['po_product_id']; ?>][freebie]" <?= CK\fn::check_flag($rp['unexpected'])?'checked':''; ?>></td>
									<td>
										<?php if ($rp['ipn']->is('serialized') && !empty($rp['serials'])) {
											foreach ($rp['serials'] as $serial) {
												echo $serial->get_header('serial_number').'<br>';
											}
										}
										else echo 'N/A'; ?>
									</td>
								</tr>
								<?php } ?>
								<tr>
									<td colspan="3"><input type="button" value="Select All" onClick="$$('input.approve_checkbox').each(function(e) { e.checked = 1 });"></td>
									<td colspan="3" align="right"><input type="submit" value="Submit"></td>
								</tr>
							</table>
						</div>
						<?php } ?>
					</form>
					<?php } ?>

					<?php if ($action == 'edit') { ?>
					<form action="po_editor.php?action=edit&poId=<?= $poId; ?>" method="post" enctype="multipart/form-data">
						<div style="margin-bottom:8px;">
							<input type="submit" value="Upload Product List">: <input type="file" name="po_product_upload" id="po_product_upload">
						</div>
					</form>
					<?php } ?>

					<form action="po_editor.php?action=<?= $action=='edit'?(empty($_FILES['po_product_upload'])?'save':'save-upload'):'create'; ?>&poId=<?= $poId; ?>" method="post" name="po_editor" onSubmit="return validateSubmit();">
						<span class="main"><b>Vendor:</b></span>
						<?php $company_payment_term = NULL;
						if ($action == 'new') {
							$p_vendor = isset($_GET['p_vendor'])?$_GET['p_vendor']:NULL;
							$autofill_class = '';
							if (!empty($_GET['method']) && $_GET['method'] == 'autofill') {
								$ipn_id = $_GET['po_list'];
								if (strpos($_GET['po_list'], ',')) $ipn_id = substr($_GET['po_list'], 0, strpos($_GET['po_list'], ','));

								$ipn_vendor_query = "";
								if (!empty($p_vendor)) {
									$ipn_vendor = prepared_query::fetch('SELECT v.vendors_id, v.company_payment_terms FROM vendors v WHERE v.vendors_id = :vendors_id', cardinality::ROW, [':vendors_id' => $p_vendor]);
								}
								else {
									$ipn_vendor = prepared_query::fetch('SELECT v.vendors_id, v.company_payment_terms FROM products_stock_control psc LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE psc.stock_id = :stock_id', cardinality::ROW, [':stock_id' => $ipn_id]);
								}
								// MMD - fixing on 071715 - this should come from the request during the autofill, right? $p_vendor = $ipn_vendor['vendors_id'];
								if (empty($p_vendor)) $p_vendor = $ipn_vendor['vendors_id'];
								$company_payment_term = $ipn_vendor['company_payment_terms'];

								$autofill_class = 'auto-fill';
							} ?>
						<select id="vendor" name="vendor" class="jquery-chosen <?= $autofill_class; ?>">
							<?php foreach ($vendor_array as $vend) { ?>
							<option value="<?= $vend['vendors_id']; ?>" id="vendor-selector-<?= $vend['vendors_id']; ?>" data-email="<?= $vend['vendors_email_address']; ?>" data-terms="<?= $vend['company_payment_terms']; ?>" <?= $vend['vendors_id']==$p_vendor?'selected':''; ?>><?= $vend['vendors_company_name']; ?></option>
							<?php } ?>
						</select>
						<script>
							jQuery('#vendor').change(function() {
								var $opt = jQuery('#vendor-selector-'+jQuery(this).val());

								if (jQuery(this).hasClass('auto-fill')) {
									jQuery('#email_to_address').val($opt.attr('data-email'));
									jQuery('#terms').val($opt.attr('data-terms'));
									updateVendorSpecificData(this);
								}
								else {
									jQuery('#po_content').show();
									jQuery('#email_field').show();
									jQuery('#email_to_address').val($opt.attr('data-email'));
									jQuery('#terms').val($opt.attr('data-terms'));
									updateVendorSpecificData(this);
								}
							});
						</script>

						<div style="float: right; margin-right: 250px; font-family: verdana;" id="vendor_address"></div>

						<input type="hidden" id="previous_vendor" name="previous_vendor" value="">
						<div id="vendor_update_please_wait" style="display:none;">Please wait while vendor specific data is updated.</div>
						<div id="vendor_update_prompt" style="display:none;">Changing the vendor will cause all of the part numbers and prices on the products below to be reset. Do you want to change the vendor?</div>
						<script type="text/javascript">
							function updateVendorSpecificData(selectBox) {
								var dialog = jQuery("#vendor_update_prompt").dialog({
									modal: true,
									buttons: {
										"Yes": function() {
											var vendor_id = jQuery('#vendor').val();
											jQuery('#previous_value').val(vendor_id);
											jQuery(this).dialog('close');
											jQuery('#vendor_update_please_wait').dialog({ modal: true });
											var ipn_list = new Array();
											pn_boxes = jQuery('.vendor_product');
											for (var i = 0; i < pn_boxes.length; i++) {
												var ipn_id = jQuery(pn_boxes[i]).attr('ipn_id');
												ipn_list[ipn_list.length] = ipn_id;
											}
											/*if(ipn_list.length == 0) { //MMD - we no longer need this check since we are adding the address now
												jQuery('#vendor_update_please_wait').dialog('close');
												return;
											}*/
											jQuery.post('po_editor.php', {
												'action': 'vendor_data',
												'vendor_id': vendor_id,
												'ipn_ids': ipn_list
											},
											function(data) {
												data = jQuery.parseJSON(data);
												pn_boxes = jQuery('.vendor_product');
												for (var i = 0; i < pn_boxes.length; i++) {
													var ipn_id = jQuery(pn_boxes[i]).attr('ipn_id');
													if (data[ipn_id]) {
														var new_pn = data[ipn_id]['pn'];
														jQuery(pn_boxes[i]).val(new_pn);
													}
													else {
														jQuery(pn_boxes[i]).val('');
													}
												}
												cost_boxes = jQuery('.vendor_cost');
												for (var i = 0; i < cost_boxes.length; i++) {
													var ipn_id = jQuery(cost_boxes[i]).attr('ipn_id');
													if (data[ipn_id]) {
														var new_cost = data[ipn_id]['cost'];
														jQuery(cost_boxes[i]).val(new_cost);
													}
													else {
														jQuery(cost_boxes[i]).val('');
													}
												}
												jQuery('#vendor_address').html(data['vendor_address']);
												jQuery('#vendor_update_please_wait').dialog('close');
											});
										},
										"No": function() {
											jQuery("#vendor").val(jQuery('#previous_vendor').val());
											jQuery(this).dialog("close");
										}
									}
								});
								if (jQuery("#previous_vendor").val() == "") {
									jQuery("#previous_vendor").val(selectBox.value);
									jQuery('.ui-button:contains("Yes")').click()
								}
							}
						</script>
						<?php }
						else {
							$vendor = prepared_query::fetch('SELECT v.vendors_company_name, p.vendor FROM purchase_orders p LEFT JOIN vendors v ON p.vendor = v.vendors_id WHERE p.id = :po_id', cardinality::ROW, [':po_id' => $poId]); ?>
						<span class="main"><?= $vendor['vendors_company_name']; ?></span>
						<input type="hidden" id="vendor" name="vendor" value="<?= $vendor['vendor']; ?>">
						<?php } ?>
						<br><br>
						
						<div id="po_content" style="<?= $action=='new'&&(empty($_GET['method'])||$_GET['method']!='autofill')?'display:none;':''; ?>">
							<?php if ($action == 'edit') {
								$po = prepared_query::fetch('SELECT po.* FROM purchase_orders po WHERE po.id = :po_id', cardinality::ROW, [':po_id' => $poId]);
								if (!empty($po['expected_date'])) $po['expected_date'] = new DateTime($po['expected_date']);
								if (!empty($po['submit_date'])) $po['submit_date'] = new DateTime($po['submit_date']);
								if (!empty($po['followup_date'])) $po['followup_date'] = new DateTime($po['followup_date']);
								$po['creator_admin_id'] = $po['creator'];
								$po['creator'] = new ck_admin($po['creator_admin_id']);
								$po['administrator'] = new ck_admin($po['administrator_admin_id']);
								$po['owner'] = new ck_admin($po['owner_admin_id']);
								if (!empty($po['buyback_admin_id'])) $po['buyback_admin'] = new ck_admin($po['buyback_admin_id']);
								$po['drop_ship'] = CK\fn::check_flag($po['drop_ship']);
							}
							else {
								$nextWeek = new DateTime();
								$nextWeek->add(new DateInterval('P1W'));
								//set defaults for new POs
								$po = [
									'expected_date' => $nextWeek,
									'submit_date' => NULL,
									'followup_date' => $nextWeek,
									'shipping_method' => 1, //make UPS Ground default shipping method
									'terms' => $company_payment_term,
									'drop_ship' => NULL,
								];
							} ?>
							<table cellspacing="3px" cellpadding="3px">
								<tr>
									<td class="main" nowrap><b>Generate As:</b></td>
									<td class="main" nowrap>
										<select name="entity">
											<?php $entities = prepared_query::fetch('SELECT entity_id, entity_name FROM ck_entities', cardinality::SET);
											foreach ($entities as $entity) { ?>
											<option value="<?= $entity['entity_id']; ?>"><?= $entity['entity_name']; ?></option>
											<?php } ?>
										</select>
									</td>
								</tr>
								<tr>
									<td class="main" nowrap><strong>Creator:</strong></td>
									<td class="main" nowrap><?= !empty($po['creator'])?$po['creator']->get_name():''; ?></td>
								</tr>
								<?php $buyers = prepared_query::fetch('SELECT admin_id, admin_firstname, admin_lastname, admin_email_address FROM admin WHERE (admin_groups_id IN (7, 17, 20, 29, 31) AND status = 1) OR admin_id = :admin_id', cardinality::SET, [':admin_id' => $_SESSION['login_id']]); ?>
								<tr>
									<td class="main" nowrap><strong>Administrator:</strong></td>
									<td class="main" nowrap>
										<?php if (!in_array($_SESSION['perms']['admin_groups_id'], [1, 7, 17, 20, 29, 31])) { ?>
										<?= !empty($po['administrator'])?$po['administrator']->get_name():''; ?>
										<input type="hidden" name="administrator_admin_id" value="<?= !empty($po['administrator'])?$po['administrator']->id():$_SESSION['login_id']; ?>">
										<?php }
										else { ?>
										<select name="administrator_admin_id" id="administrator">
											<option value="0"></option>
											<?php foreach ($buyers as $buyer) { ?>
											<option value="<?= $buyer['admin_id']; ?>" data-email="<?= $buyer['admin_email_address']; ?>" <?= !empty($po['administrator'])&&$po['administrator']->id()==$buyer['admin_id']?'selected':''; ?>><?= $buyer['admin_firstname'].' '.$buyer['admin_lastname']; ?></option>
											<?php } ?>
										</select>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<td class="main" nowrap><strong>Owner:</strong></td>
									<td class="main" nowrap>
										<?php if (!in_array($_SESSION['perms']['admin_groups_id'], [1, 7, 17, 20, 29, 31])) { ?>
										<?= !empty($po['owner'])?$po['owner']->get_name():''; ?>
										<input type="hidden" name="owner_admin_id" value="<?= !empty($po['owner'])?$po['owner']->id():$_SESSION['login_id']; ?>">
										<?php }
										else { ?>
										<select name="owner_admin_id" id="owner">
											<option value="0"></option>
											<?php foreach ($buyers as $buyer) { ?>
											<option value="<?= $buyer['admin_id']; ?>" data-email="<?= $buyer['admin_email_address']; ?>" <?= !empty($po['owner'])&&$po['owner']->id()==$buyer['admin_id']?'selected':''; ?>><?= $buyer['admin_firstname'].' '.$buyer['admin_lastname']; ?></option>
											<?php } ?>
										</select>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<td class="main" nowrap><strong>Buyback Admin:</strong></td>
									<td class="main" nowrap>
										<?php if (!in_array($_SESSION['perms']['admin_groups_id'], [1, 7, 17, 20, 29, 31])) { ?>
										<?= !empty($po['buyback_admin'])?$po['buyback_admin']->get_name():''; ?>
										<input type="hidden" name="buyback_admin_id" value="<?= !empty($po['buyback_admin'])?$po['buyback_admin']->id():NULL; ?>">
										<?php }
										else { ?>
										<select name="buyback_admin_id" id="buyback_admin">
											<option value="0"></option>
											<?php $account_managers = ck_admin::get_account_managers(['ck_admin', 'sort_by_name']);
											foreach ($account_managers as $am) { ?>
											<option value="<?= $am->id(); ?>" <?= !empty($po['buyback_admin'])&&$po['buyback_admin']->id()==$am->id()?'selected':''; ?>><?= $am->get_name(); ?></option>
											<?php } ?>
										</select>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<td class="main" nowrap><b>PO Number:</b></td>
									<td class="main" nowrap>
										<input type="text" readonly name="purchase_order_number" id="purchase_order_number" value="<?= @$po['purchase_order_number']; ?>">
										<?php if ($action != 'edit') { ?>
										<a href="javascript: void(0);" onClick="generate_po_number();">Autogenerate</a>
										<?php } ?>
									</td>
								</tr>
								<tr>
									<td class="main" colspan="2"></td>
								</tr>
								<tr>
									<td class="main" nowrap><b>Ship via:</b></td>
									<td class="main" nowrap>
										<select name="shipping_method" id="shipping_method" size="1">
											<?php foreach ($po_shipping_array as $shipping_method) { ?>
											<option value="<?= $shipping_method['shipping_id']; ?>" <?= $shipping_method['shipping_id']==$po['shipping_method']?'selected':''; ?>><?= $shipping_method['shipping_type']; ?></option>
											<?php } ?>
										</select>
									</td>
								</tr>
								<tr>
									<td class="main"><strong>Dropship Fulfillment</strong></td>
									<td class="main"><input type="checkbox" name="drop_ship" <?= CK\fn::check_flag($po['drop_ship'])?'checked':''; ?>></td>
								</tr>
								<tr>
									<td class="main"><b>Submitted:</b>&nbsp;&nbsp;</td>
									<td class="main" id="submit-date-container"><?= !empty($po['submit_date'])?$po['submit_date']->format('m/d/Y'):''; ?></td>
									<td class="main">
										<a id="edit_submit-date" class="edit" href="#"><img border="0" src="images/table_edit.png" alt="Edit"></a>
										<input type="hidden" name="po-id-hidden" id="po-id-hidden" value="<?= @$po['purchase_order_number']; ?>">
										<input id="submit-date" type="hidden" name="submit_date" value="<?= !empty($po['submit_date'])?$po['submit_date']->format('m/d/Y'):''; ?>">
										<a id="clear_submit-date" class="edit" href="#">clear</a>
									</td>
								</tr>
								<tr>
									<td class="main"><b>Expected:</b>&nbsp;&nbsp;</td>
									<td class="main" id="expected-date-container"><?= !empty($po['expected_date'])?$po['expected_date']->format('m/d/Y'):''; ?></td>
									<td class="main">
										<a id="edit_expected-date" class="edit" href="#"><img border="0" src="images/table_edit.png" alt="Edit"></a>
										<input type="hidden" name="po-id-hidden" id="po-id-hidden" value="<?= @$po['purchase_order_number']; ?>">
										<input id="expected-date" type="hidden" name="expected_date" value="<?= !empty($po['expected_date'])?$po['expected_date']->format('m/d/Y'):''; ?>">
										<a id="clear_expected-date" class="edit" href="#">clear</a>
									</td>
								</tr>
								<tr>
									<td class="main"><b>Followup:</b>&nbsp;&nbsp;</td>
									<td class="main" id="followup-date-container"><?= !empty($po['followup_date'])?$po['followup_date']->format('m/d/Y'):''; ?></td>
									<td class="main">
										<a id="edit_followup-date" class="edit" href="#"><img border="0" src="images/table_edit.png" alt="Edit" ></a>
										<input id="followup-date" type="hidden" name="followup_date" value="<?= !empty($po['followup_date'])?$po['followup_date']->format('m/d/Y'):''; ?>">
									</td>
								</tr>
								<tr>
									<td class="main" colspan="2"></td>
								</tr>
								<tr>
									<td class="main" nowrap><b>Payment Terms:</b></td>
									<td class="main" nowrap>
										<select name="terms" id="terms" size="1">
											<?php foreach ($po_terms_array as $terms) { ?>
											<option value="<?= $terms['terms_id']; ?>" <?= $terms['terms_id']==$po['terms']?'selected':''; ?>><?= $terms['terms_type']; ?></option>
											<?php } ?>
										</select>
									</td>
								</tr>
							</table>
							<br><br>

							<?php $upload_error = NULL;
							if (!empty($_FILES['po_product_upload'])) { ?>
							<script src="/images/static/js/ck-j-spreadsheet-upload.max.js?v=0.19"></script>
							<style>
								#po_product_upload_results { border-collapse:collapse; font-size:12px; }
								.spreadsheet-upload-errors { color:#c00; font-weight:bold; }
								.error .spreadsheet-field { border-color:#f00; }
								.spreadsheet-field.error { background-color:#fcc; }
								.spreadsheet-field { padding:2px; }
								/*#po_product_upload_results th, #po_product_upload_results td { border:1px solid #000; }*/
							</style>
							<input type="submit" value="Submit">
							<input type="hidden" name="process-product-upload" id="process-product-upload" value="1">
							<table id="po_product_upload_results" border="0" cellpadding="0" cellspacing="0">
								<?php $upload_status_map = [
									UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
									UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
									UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
									UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
									UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
									UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
									UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
								];
								if ($_FILES['po_product_upload']['error'] !== UPLOAD_ERR_OK) { ?>
									<tr><td>There was a problem with receiving your product upload file: <?= $upload_status_map[$_FILES['po_product_upload']['error']]; ?></td></tr>
								<?php }
								else {
									$spreadsheet = new spreadsheet_import($_FILES['po_product_upload']); ?>
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
								<?php } ?>
							</table>
							<script>
								jQuery('#po_product_upload_results').spreadsheet_upload({
									headers: [
										{ value:'ipn', label:'IPN', required:true },
										{ value:'qty', label:'Qty', required:true },
										{ value:'cost', label:'Cost', required:true },
										{ value:'desc', label:'Description' }
									],
									validators: {
										qty: function(col_idx, record_error, clear_error) {
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
										},
										cost: function(col_idx, record_error, clear_error) {
											msg_recorded = false;
											jQuery(this).find('.col-'+col_idx).each(function(idx) {
												if (!jQuery(this).is(':disabled') && jQuery(this).val().replace(/[$0-9.]/g, '') != '') {
													//console.log([jQuery(this).val(), jQuery(this).is(':disabled'), jQuery(this).attr('disabled')]);
													if (!msg_recorded) record_error(jQuery(this), col_idx, 'There are invalid cost values.');
													else record_error(jQuery(this), col_idx);
													msg_recorded = true;
												}
												else {
													clear_error(jQuery(this), col_idx);
												}
											});
										},
										ipn: function(col_idx, record_error, clear_error) {
											msg_recorded = false;
										}
									}
								});
							</script>
							<?php } ?>

							<div style="text-align:right; margin-bottom:2px;">
								<a href="/admin/quick-ipn-create.php" target="_blank"><button type="button" style="cursor:pointer;">Create New IPN</button></a>
							</div>
							<table style="border: 1px solid black" id="add_product_table" width="100%">
								<tr>
									<td colspan="5">
										<div id="add_product_button">
											<input type="button" value="Add product" id="add_product_start_button" onClick="document.getElementById('ipn_search').style.visibility='visible';document.getElementById('add_product_button').style.display='none'; document.getElementById('ipn_search_input').focus();">
										</div>
										<div id="add_product_form">
											<div id="ipn_search" style="visibility:hidden;">
												<style type="text/css">
													#ipn_search_input { width:15em; }
													#ipn_search_container { position:absolute; z-index:9050; }
													#ipn_search_container .yui-ac-content { position:absolute; left:0; top:0; width:15em; border:1px solid #404040; background:#fff; overflow:hidden; text-align:left; z-index:9050; }
													#ipn_search_container .yui-ac-shadow { position:absolute; left:0; top:0; margin:.3em; background:#a0a0a0; z-index:9049; }
													#ipn_search_container ul { padding:5px 0; width:100%; }
													#ipn_search_container li { padding:0 5px; cursor:default; white-space:nowrap; }
													#ipn_search_container li.yui-ac-highlight { background:#ff0; }
												</style>
												<label class="main">IPN Lookup:</label>
												<input id="ipn_search_input" name="ipn_search_input">
												<div id="ipn_search_container"></div>
												<div id="ipn_product_container"></div>
											</div>
											<div id="product_form" style="display:none;">
												<table cellspacing="3px" cellpadding="3px">
													<tr>
														<td class="main" colspan="4"><b>Receiving History</b></td>
													</tr>
													<tr>
														<td class="main" valign="top" colspan="4"><div style="width: 100%; height: 100px; overflow: auto;"><table id="add_product_history" cellpadding="2px" style="padding: 4px; background-color: #CCCCCC;"></table></div></td>
													</tr>
													<tr>
														<td class="main"><b>Product</b></td>
														<td class="main"><b>Quantity</b></td>
														<td class="main"><b>Price</b></td>
														<td class="main"><b>Description</b></td>
													</tr>
													<tr>
														<td class="main" disabled valign="top">
															<input type="text" id="add_product_ipn_name">
															<input type="hidden" id="add_product_ipn_id">
															<input type="hidden" id="add_product_vendor_pn">
															<input type="hidden" id="add_product_v2s_id">
															<input type="hidden" id="add_product_weight" value="2" size="5">
														</td>
														<td class="main" valign="top"><input type="text" id="add_product_quantity" value="0" size="5" onkeypress="return captureEnterKey(event);"></td>
														<td class="main" valign="top"><input type="text" id="add_product_cost" value="0" size="5" onkeypress="return captureEnterKey(event);"></td>
														<td class="main" valign="top"><textarea rows="2" cols="80" id="add_product_description" onkeypress="return captureEnterKey(event);"></textarea></td>
													</tr>
												</table>
											</div>
											<br>
											<input type="button" value="Ok" onClick="addProductToForm(); document.getElementById('add_product_start_button').focus();">
											<input type="button" value="Cancel" onClick="resetAddProductForm(); document.getElementById('add_product_start_button').focus();">
										</div>
									</td>
								</tr>
								<?php //now we do the table header
								if (!empty($poId)) $show_vpn = prepared_query::fetch('SELECT show_vendor_pn FROM purchase_orders po WHERE id = :po_id', cardinality::SINGLE, [':po_id' => $poId]);
								else $show_vpn = 1; ?>
								<tr>
									<td class="main"><b>IPN</b></td>
									<td class="main">
										<input type="checkbox" id="show_vendor_product" name="show_vendor_product" <?= $show_vpn?'checked="checked"':''?> onClick="if($('show_vendor_product').checked) { $$('input.vendor_product').invoke('show'); } else { $$('input.vendor_product').invoke('hide'); }">
										<b>Vendor P/N</b>
									</td>
									<td class="main"><b>Description</b></td>
									<td class="main"><b>Weight</b></td>
									<td class="main"><b>Quantity</b></td>
									<td class="main"><b>Cost</b></td>
									<td class="main"><b>Ext Weight</b></td>
									<td class="main"><b>Action</b></td>
									<td class="main"><b>Total</b></td>
								</tr>
								<?php $total_weight = 0;
								//now, if we are in 'edit' mode, we look up any stored products
								if ($action == 'edit') {
									$po_products = prepared_query::fetch('SELECT pop.id, pop.quantity, pop.description, pop.cost, pop.ipn_id, psc.stock_name, psc.stock_weight, SUM(porp.quantity_received) as quantity_received, v2s.vendors_pn, v2s.v2s_id, v2s.vendors_id FROM purchase_order_products pop LEFT JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id LEFT JOIN purchase_orders po ON po.id = :po_id LEFT JOIN (SELECT MAX(id) as v2s_id, stock_id, vendors_id, MAX(vendors_pn) as vendors_pn FROM vendors_to_stock_item GROUP BY stock_id, vendors_id) v2s ON v2s.stock_id = psc.stock_id AND v2s.vendors_id = po.vendor WHERE pop.purchase_order_id = :po_id GROUP BY pop.id', cardinality::SET, [':po_id' => $poId]);
									foreach ($po_products as $i => $po_product) {
										$reviewCount = prepared_query::fetch('SELECT SUM(porp.qty_received) as total FROM purchase_order_review_product porp JOIN purchase_order_review por ON porp.po_review_id = por.id AND por.status = 0 WHERE porp.pop_id = :pop_id', cardinality::SINGLE, [':pop_id' => $po_product['id']]); ?>
								<tr id='ipn_<?= $po_product['id']; ?>'>
									<td class="main" valign="top"><input type="hidden" value="<?= $po_product['ipn_id'];?>" name="ipn[<?= $po_product['id'];?>]"><?= $po_product['stock_name'];?></td>
									<td class="main" valign="top">
										<input class="vendor_product" style="<?= $show_vpn?'':'display:none'; ?>" type="text" name="v2s[<?= $po_product['v2s_id']?$po_product['v2s_id']:'new'; ?>][vendors_pn]" value="<?= $po_product['vendors_pn']; ?>">
										<input type="hidden" name="v2s[<?= $po_product['v2s_id']? $po_product['v2s_id'] : 'new' ?>][ipn_id]" value="<?= $po_product['ipn_id'];?>">
									</td>
									<td class="main" valign="top"><textarea rows="2" cols="80" name="description[<?= $po_product['id'];?>]"><?= $po_product['description'];?></textarea></td>
									<td class="main" valign="top"><input type="hidden" id="weight-<?= $po_product['id']; ?>" name="weight[<?= $po_product['id'];?>]" value="<?= $po_product['stock_weight'];?>" ><?= $po_product['stock_weight'];?></td>
									<td class="main" valign="top"><input type="text" size="6" minimumValue="<?= isset($po_product['quantity_received'])?$po_product['quantity_received']:0; ?>" reviewCount="<?= $reviewCount?$reviewCount:0; ?>" value="<?= $po_product['quantity']; ?>" name="quantity[<?= $po_product['id']; ?>]" class="pop_qty pop-<?= $po_product['ipn_id']; ?>-<?= $po_product['id']; ?>" data-stock-id="<?= $po_product['ipn_id']; ?>-<?= $po_product['id']; ?>"></td>
									<td class="main" valign="top"><input type="text" size="10" <?php if ($po_product['quantity_received'] > 0) { ?> readonly style="background-color: #ababab;" <?php } ?> value="<?= $po_product['cost']; ?>" name="cost[<?= $po_product['id']; ?>]" class="pop_cost pop-<?= $po_product['ipn_id']; ?>-<?= $po_product['id']; ?>" data-stock-id="<?= $po_product['ipn_id']; ?>-<?= $po_product['id']; ?>"></td>
									<td class="main" valign="top" id="total_weight_<?= $po_product['id'];?>"><?= $po_product['stock_weight']*$po_product['quantity']; ?></td>
									<td class="main" valign="top"><a href="javascript: void(0);" onClick="deleteProduct('<?= $po_product['id']; ?>', '<?= isset($po_product['quantity_received'])?$po_product['quantity_received']:0; ?>');">Delete</a></td>
									<td class="main" valign="top" id="total_<?= $po_product['ipn_id']; ?>-<?= $po_product['id']; ?>">&nbsp;</td>
								</tr>
										<?php $total_weight += $po_product['stock_weight'] * $po_product['quantity'];
									}
								}

								//if info was sent from stock list, check the action and use values from array
								if ($action == 'new' && !empty($_GET['method']) && $_GET['method'] == 'autofill') {
									$po_list = explode(',', $_GET['po_list']);
									$po_qty = explode(',', @$_GET['qty']);
									$po_price_array = !empty($_GET['prices'])?explode(',', $_GET['prices']):[];
									foreach ($po_list as $i => $stock_id) {
										$ipn = prepared_query::fetch('SELECT psc.*, v2s.* FROM products_stock_control psc LEFT JOIN vendors_to_stock_item v2s ON v2s.id = psc.vendor_to_stock_item_id WHERE psc.stock_id = :stock_id', cardinality::ROW, [':stock_id' => $stock_id]);
										if (!empty($po_price_array)) $vendor_price = $po_price_array[$i];
										else $vendor_price = $ipn['vendors_price']?$ipn['vendors_price']:0; ?>
								<tr id='ipn_<?= $stock_id; ?>'>
									<td class="main"><input type="hidden" value="<?= $stock_id; ?>" name="ipn[<?= $stock_id; ?>]"><?= $ipn['stock_name']; ?></td>
									<td class="main" valign="top">
										<input class="vendor_product" style="<?= $show_vpn?'':'display:none'; ?>" type="text" name="v2s[<?= $ipn['id']?$ipn['id']:'new'; ?>][vendors_pn]" value="<?= $ipn['vendors_pn']; ?>">
										<input type="hidden" name="v2s[<?= $ipn['id']?$ipn['id']:'new'; ?>][ipn_id]" value="<?= @$ipn['ipn_id']; ?>">
									</td>
									<td class="main"><textarea rows="2" cols="80" name="description[<?= $stock_id;?>]"><?= $ipn['stock_description']; ?></textarea></td>
									<td class="main"><input type="hidden" name="weight[<?= $stock_id;?>]" value="<?= $ipn['stock_weight']; ?>" ><?= $ipn['stock_weight']; ?></td>
									<td class="main"><input type="text" size="6" class="pop_qty pop-<?= $stock_id; ?>" data-stock-id="<?= $stock_id; ?>" name="quantity[<?= $stock_id;?>]" value="<?= $po_qty[$i]; ?>" minimumValue="0" reviewCount="0"></td>
									<td class="main"><input type="text" size="10" class="pop_cost pop-<?= $stock_id; ?>" data-stock-id="<?= $stock_id; ?>" value="<?= $vendor_price; ?>" name="cost[<?= $stock_id; ?>]"></td>
									<td class="main" id="total_weight_<?= $stock_id;?>"><?= @($ipn['stock_weight'] * $po_qty[$i]); ?></td>
									<td class="main"><a href="javascript: void(0);" onClick="deleteProduct('<?= $stock_id; ?>');">Delete</a></td>
									<td class="main" id="total_<?= $stock_id;?>">&nbsp;</td>
								</tr>
									<?php }
								} ?>
								<tr>
									<td class="main" colspan="6">&nbsp;</td>
									<td class="main" style="border-top: 1px solid black;" id="ext_weight"><?= $total_weight; ?></td>
									<td class="main">&nbsp;</td>
									<td id="po_total" class="main" style="border-top: 1px solid black;">&nbsp;</td>
								</tr>
							</table>
							<script type="text/javascript">
								var po_product_ids = [];
								<?php
								if ($action == 'new' && !empty($_GET['method']) && $_GET['method'] == 'autofill') {
									$polist = explode(',', $_GET['po_list']);
									for ($i = 0, $n=count($polist); $i < $n; $i++) { ?>
										po_product_ids[po_product_ids.length] = '<?= $stock_id;?>';
										document.getElementsByName('quantity[' + <?= $polist[$i]; ?> + ']')[0].focus();
									<?php }
								}
								else {
									for ($i = 0, $n=@count($po_products); $i < $n; $i++) { ?>
										po_product_ids[po_product_ids.length] = '<?= $po_product['id'];?>';
									<?php }
								} ?>
								setInterval('calculatePOTotals();', 1000);
								setInterval('calculateExtWeight();', 1000);
							</script>
							<br><br>
							<?php $po = [];
							$purchase_order_note = '';
							if ($action == 'edit') {
								$po = prepared_query::fetch('select * from purchase_orders where id = :po_id', cardinality::ROW, [':po_id' => $poId]);
								$po['creator_admin_id'] = $po['creator'];
								$po['creator'] = new ck_admin($po['creator_admin_id']);
								$po['administrator'] = new ck_admin($po['administrator_admin_id']);
								$po['owner'] = new ck_admin($po['owner_admin_id']);
								if (!empty($po['buyback_admin_id'])) $po['buyback_admin'] = new ck_admin($po['buyback_admin_id']);
								$purchase_order_note = 'Updated';
							}
							else {
								$nextWeek = time() + (7 * 24 * 60 * 60);
								$po['expected_date'] = date('m/d/y', $nextWeek);
								$purchase_order_note = "Created";
							}
							?>
							<table>
								<tr>
									<td valign="top"><span class="main"><b>Vendor Only Notes:</b></span>&nbsp;&nbsp;</td>
									<td><textarea name="vendor_notes" rows='6' cols='70'><?= @$po['notes']; ?></textarea></td>
								</tr>
							</table>
							<br><br>
							<script type="text/javascript">
								jQuery(document).ready(function($) {
									$('#add-note-button').click(function(event) {
										var poId = $('#poId').val();
										var data = $('#notes-form').serialize();
										data += '&action=add_note';
										data += '&poID=' + poId;
										data += '&notes=' + $('#notes').val();
										$.post('po_editor.php', data, function(data) {
											$('#notes').val();
											$('#notes-display').append('<tr><td>' + data.created_on + '</td><td>' + data.created_by + '</td><td>' + data.note + '</td></tr>');
										}, 'json');
										location.reload();
									});
								});
							</script>

							<?php $po_notes = prepared_query::fetch('SELECT pon.*, CONCAT(a.admin_firstname, \' \', a.admin_lastname) as name FROM purchase_order_notes pon JOIN admin a ON pon.purchase_order_note_user = a.admin_id WHERE purchase_order_id = :po_id', cardinality::SET, [':po_id' => $poId]); ?>

							<div class="main">
								<h3>Admin Notes</h3>
								<table style="background-color: #ececec; width: 1000px; border: 2px solid #ababab;" class="noPrint">
									<tr>
										<td class="main" valign="top"><br><b>Admin Notes:</b><br><br></td>
									</tr>
									<tr>
										<td class="main" valign="top">
											<!-- start orders notes -->
											<?php require_once(DIR_WS_CLASSES.'purchaseorder_notes.php');
											$orders_notes= new PurchaseOrderNotes($poId);
											$orders_notes->displayAll(); ?>
										</td>
									</tr>
									<tr>
										<td colspan="4" align="left">
											<form id="notes-form">
												<input type="hidden" name="testing" value="<?= $poId; ?>" id="testing">
												<input type="hidden" name="poID" value="<?= $poId; ?>" id="poId">
												<textarea id="notes" style="height: 75px; width: 300px;" name="notes"></textarea>
												<?php if ($_GET['action'] != 'new') { ?>
												<input id="add-note-button" style="vertical-align: top;" type="button" value="Add">
												<?php } ?>
											</form>
										</td>
									</tr>
								</table>
							</div>
						</div>
						<br><br>
						<?php $email_div_style = '';
						$email_address = '';
						if ($action == 'new') {
							$email_div_style = 'style="display:none;"';
							if (!empty($_GET['method']) && $_GET['method'] == 'autofill') {
								if (!empty($_GET['p_email'])) {
									$email_address = $_GET['p_email'];
									$email_div_style = "";
								}
								else {
									$ipn_id = $_GET['po_list'];
									if (strpos($_GET['po_list'], ',')) $ipn_id = substr($_GET['po_list'], 0, strpos($_GET['po_list'], ','));

									$p_vendor = prepared_query::fetch('SELECT v.vendors_id FROM products_stock_control psc LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE psc.stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $ipn_id]);

									$email_address = prepared_query::fetch('SELECT v.vendors_email_address FROM vendors v WHERE v.vendors_id = :vendors_id', cardinality::SINGLE, [':vendors_id' => $p_vendor]);
									$email_div_style = '';
								}
							}
						}
						else {
							$email_address = prepared_query::fetch('SELECT v.vendors_email_address FROM purchase_orders po JOIN vendors v ON po.vendor = v.vendors_id WHERE po.id = :po_id', cardinality::SINGLE, [':po_id' => $poId]);
						} ?>

						<span id="email_field" <?= $email_div_style; ?>>
							<input type="checkbox" name="email_check" id="email-check" checked>
							<label for="email-check"><span class="main"><b>From:</b></span>&nbsp;</label>
							<select name="email_from_address" id="email-from-address">
								<?php if (!empty($po['administrator'])) { ?>
								<option value="<?= $po['administrator']->get_header('email_address'); ?>"> Administrator - <?= $po['administrator']->get_header('email_address'); ?></option>
								<?php } ?>
								<?php if (!empty($po['owner'])) { ?>
								<option value="<?= $po['owner']->get_header('email_address'); ?>"> Owner - <?= $po['owner']->get_header('email_address'); ?></option>
								<?php } ?>
								<?php if (!empty($po['creator'])) { ?>
								<option value="<?= $po['creator']->get_header('email_address'); ?>"> Creator - <?= $po['creator']->get_header('email_address'); ?></option>
								<?php } ?>
								<?php if (!empty($po['buyback_admin'])) { ?>
								<option value="<?= $po['buyback_admin']->get_header('email_address'); ?>"> Buyback Admin - <?= $po['buyback_admin']->get_header('email_address'); ?></option>
								<?php } ?>
								<option value="<?= $_SESSION['perms']['admin_email_address']; ?>">You - <?= $_SESSION['perms']['admin_email_address']; ?></option>
								<option value="purchasing@cablesandkits.com">Purchasing - purchasing@cablesandkits.com</option>
							</select>
							&nbsp;<span class="main"><b>Email PO to:</b></span>&nbsp;
							<input type="text" size="30" name="email_to_address" id="email_to_address" value="<?= $email_address; ?>">
							&nbsp;&nbsp;&nbsp;
							<input type="checkbox" name="confirmation_requested" checked >&nbsp;<span class="main"><b>Request confirmation</b></span>
						</span>

						<?php if ($action == 'new') { ?>
						<input type="button" value="Cancel" onclick="window.location='po_list.php';">
						<input type="submit" value="Create PO" id="po_editor_submit_button">
						<?php }
						elseif ($action == 'edit') { ?>
						<input type="button" value="Cancel" onclick="window.location='po_viewer.php?poId=<?= $poId; ?>';">
						<input type="submit" value="Save" id="po_editor_submit_button">
						<?php } ?>
					</form>
					<?php } ?>
					<!------------------------------------------------------------------- -->
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<script type="text/javascript">
			ipnSearchInit = function() {
				var ist_xhr_ds = new YAHOO.widget.DS_XHR("ipn_autoCompleteQuery.php", ["results","name"]);
				ist_xhr_ds.queryMatchContains = true;

				// Instantiate AutoComplete
				var ist_auto_complete = new YAHOO.widget.AutoComplete("ipn_search_input","ipn_search_container", ist_xhr_ds);
				ist_auto_complete.useShadow = true;
				ist_auto_complete.typeAhead = false;
				ist_auto_complete.forceSelection = true;
				ist_auto_complete.formatResult = function(oResultItem, sQuery) {
					return oResultItem[1].name;
				};

				ist_auto_complete.doBeforeExpandContainer = function(oTextbox, oContainer, sQuery, aResults) {
					var pos = YAHOO.util.Dom.getXY(oTextbox);
					pos[1] += YAHOO.util.Dom.get(oTextbox).offsetHeight;
					YAHOO.util.Dom.setXY(oContainer,pos);
					return true;
				};

				ist_auto_complete.itemSelectEvent.subscribe(function(type, args) {
					var callback = {
						success: function(o) {
							var result = eval(o.responseText);

							//first we reset the IPN selector
							document.getElementById('ipn_search_input').value = '';

							//now we hide that div
							document.getElementById('ipn_search').style.visibility='hidden';

							//now we set the values on the product form
							document.getElementById('add_product_ipn_name').value = result.stock_name;
							document.getElementById('add_product_ipn_id').value = result.stock_id;
							document.getElementById('add_product_description').value = result.stock_description;
							document.getElementById('add_product_quantity').value = '1';
							document.getElementById('add_product_weight').value = result.stock_weight;
							document.getElementById('add_product_cost').value = result.most_recent_cost;
							document.getElementById('add_product_vendor_pn').value = result.vendor_product;
							document.getElementById('add_product_v2s_id').value = result.v2s_id;

							//now we populate the receiving history
							var tableElem = document.getElementById('add_product_history');
							var tableSize = tableElem.rows.length;
							for (var i = tableSize - 1; i >= 0; i--) {
								tableElem.deleteRow(i);
							}
							var headerRowElem = tableElem.insertRow(tableElem.rows.length);

							var headerTdElem = headerRowElem.insertCell(0);
							headerTdElem.className = 'main';
							headerTdElem.innerHTML='<b>Vendor</b>';

							headerTdElem = headerRowElem.insertCell(1);
							headerTdElem.className = 'main';
							headerTdElem.innerHTML='<b>Date</b>';

							headerTdElem = headerRowElem.insertCell(2);
							headerTdElem.className = 'main';
							headerTdElem.innerHTML='<b>PO</b>';

							headerTdElem = headerRowElem.insertCell(3);
							headerTdElem.className = 'main';
							headerTdElem.innerHTML='<b>Quantity</b>';

							headerTdElem = headerRowElem.insertCell(4);
							headerTdElem.className = 'main';
							headerTdElem.innerHTML='<b>Cost</b>';

							for (var i = 0; i < result.receiving_history.length; i++) {

								var rowElem = tableElem.insertRow(tableElem.rows.length);

								var tdElem = rowElem.insertCell(0);
								tdElem.className = 'main';
								tdElem.innerHTML = result.receiving_history[i]['vendor'];

								tdElem = rowElem.insertCell(1);
								tdElem.className = 'main';
								tdElem.innerHTML = result.receiving_history[i]['date'];

								tdElem = rowElem.insertCell(2);
								tdElem.className = 'main';
								tdElem.innerHTML = result.receiving_history[i]['purchase_order_number'];

								tdElem = rowElem.insertCell(3);
								tdElem.className = 'main';
								tdElem.innerHTML = result.receiving_history[i]['quantity'];

								tdElem = rowElem.insertCell(4);
								tdElem.className = 'main';
								tdElem.innerHTML = result.receiving_history[i]['cost'];
							}

							//now we show the add_product_form div
							document.getElementById('product_form').style.display = 'block';
							document.getElementById('add_product_quantity').focus();
							document.getElementById('add_product_quantity').select();
						},
						failure: function(o) {
							if (o.responseText !== undefined) {
								alert("Get PO info for IPN failed: " + o.responseText);
							}
							else {
								alert("Get PO info for IPN failed: no error message available");
							}
						},
						argument: new Array()
					};
					var urlstring = args[2][0];

					var urlvariable = encodeURIComponent(urlstring);

					var url = "ipn_getPOInfoForIPN.php?stock_name=" + urlvariable + "&vendors_id="+ $F('vendor');

					YAHOO.util.Connect.asyncRequest('GET', url, callback);
				});
			}
			ipnSearchInit();

			function addProductToForm() {

				var ipn_name = document.getElementById('add_product_ipn_name').value;
				var ipn_id = document.getElementById('add_product_ipn_id').value;
				var description = document.getElementById('add_product_description').value;
				var quantity = document.getElementById('add_product_quantity').value;
				var weight = document.getElementById('add_product_weight').value;
				var ext_weight = (quantity * weight).toFixed(2);
				var cost = document.getElementById('add_product_cost').value;
				var vendors_pn = document.getElementById('add_product_vendor_pn').value;
				var v2s_id = document.getElementById('add_product_v2s_id').value;
				writeProductToForm(ipn_id, ipn_name, description, quantity, weight, ext_weight, cost, vendors_pn, v2s_id);

				resetAddProductForm();

				calculatePOTotals();
				calculateExtWeight();
			}

			function resetAddProductForm() {

				document.getElementById('add_product_quantity').value = 0;
				document.getElementById('add_product_cost').value = 0;

				//document.getElementById('ipn_search').style.visibility='visible';
				document.getElementById('product_form').style.display = 'none';
				document.getElementById('add_product_button').style.display='block';
				//document.getElementById('add_product_form').style.display='none';
			}

			var newProductCounter = 0;

			function writeProductToForm(ipn_id, ipn_name, description, quantity, weight, ext_weight, cost, vendors_pn, v2s_id) {
				newProductCounter++;

				if (!v2s_id) v2s_id="new"+ newProductCounter;

				addProductTable = document.getElementById('add_product_table');
				var trElem = addProductTable.insertRow(addProductTable.rows.length - 1);

				//var trElem = document.createElement('tr');
				trElem.id = 'ipn_new_' + newProductCounter;

				//create the ipn td
				var tdElem = document.createElement('td');
				tdElem.className = 'main';
				tdElem.innerHTML = '<input type="hidden" value="' + ipn_id + '" name="ipn[new_' + newProductCounter + ']">' + ipn_name;
				trElem.appendChild(tdElem);

				//create the vendor pn td
				tdElem = document.createElement('td');
				tdElem.className = 'main';
				tdElem.innerHTML = '<input '+($('show_vendor_product').checked ? '' : 'style="display:none"')+'class="vendor_product" ipn_id="'+ipn_id+'" type="text" name="v2s['+v2s_id+'][vendors_pn]" value="'+vendors_pn+'"><input type="hidden" name="v2s['+v2s_id+'][ipn_id]" value="' + ipn_id + '">';
				trElem.appendChild(tdElem);

				//create the description td
				tdElem = document.createElement('td');
				tdElem.className = 'main';
				tdElem.innerHTML = '<textarea rows="2" cols="80" name="description[new_' + newProductCounter + ']">' + description + '</textarea>';
				trElem.appendChild(tdElem);

				//create the weight td
				tdElem = document.createElement('td');
				tdElem.className = 'main';
				tdElem.innerHTML = '<input size="6" type="hidden" minimumValue="0" value="' + weight + '" name="weight[new_' + newProductCounter + ']">' + weight;
				trElem.appendChild(tdElem);

				//create the quantity td
				tdElem = document.createElement('td');
				tdElem.className = 'main';
				tdElem.innerHTML = '<input size="6" type="text" minimumValue="0" reviewCount="0" value="' + quantity + '" name="quantity[new_' + newProductCounter + ']" class="pop_qty pop-new_'+newProductCounter+'" data-stock-id="new_'+newProductCounter+'">'
				trElem.appendChild(tdElem);

				//create the cost td
				tdElem = document.createElement('td');
				tdElem.className = 'main';
				tdElem.innerHTML = '<input size="10" type="text" ipn_id="'+ipn_id+'" value="' + cost + '" name="cost[new_' + newProductCounter + ']" class="vendor_cost pop_cost pop-new_'+newProductCounter+'" data-stock-id="new_'+newProductCounter+'">'
				trElem.appendChild(tdElem);

				//create the ext_weight td
				tdElem = document.createElement('td');
				tdElem.className = 'main';
				tdElem.id = 'total_weight_new_' + newProductCounter;
				tdElem.innerHTML = ext_weight;
				trElem.appendChild(tdElem);

				//create the action td
				tdElem = document.createElement('td');
				tdElem.className = 'main';
				tdElem.innerHTML = '<a href="javascript: void(0);" onClick="deleteProduct(\'new_' + newProductCounter + '\');">Delete</a>';
				trElem.appendChild(tdElem);

				tdElem = document.createElement('td');
				tdElem.className = 'main';
				tdElem.id = 'total_new_' + newProductCounter;
				tdElem.innerHTML = '&nbsp;';
				trElem.appendChild(tdElem);

				po_product_ids[po_product_ids.length] = 'new_' + newProductCounter;
			}

			function deleteProduct(ipn_id, quantity_received) {
				if (quantity_received > 0) {
					alert("You cannot delete a product from a purchase order after you have received some or all of that product.");
					return;
				}

				var doIt = confirm('Are you sure you want to remove this product from the purchase order?');

				if (doIt) {
					var row = document.getElementById('ipn_' + ipn_id);

					//remove from the list of po_product_ids
					var po_product_id = row.cells[row.cells.length - 1].id.substring(6);
					var tempArray = Array();
					for (var i = 0; i < po_product_ids.length; i++) {
						if (po_product_ids[i] != po_product_id) {
							tempArray[tempArray.length] = po_product_ids[i];
						}
					}
					po_product_ids = tempArray;

					var row_parent = row.parentNode;
					row_parent.removeChild(row);
				}
			}

			function validateSubmit() {
				jQuery('#po_editor_submit_button').attr('disabled', 'disabled');

				if (document.getElementsByName('vendor')[0].value == '0' || document.getElementsByName('vendor')[0].value.length == 0) {
					alert('You must select a vendor in order to create a purchase order.');
					jQuery('#po_editor_submit_button').removeAttr('disabled');
					return false;
				}

				if (document.getElementsByName('purchase_order_number')[0].value.length == 0) {
					alert('You must supply a purchase order number in order to create or edit a purchase order.');
					jQuery('#po_editor_submit_button').removeAttr('disabled');
					return false;
				}

				var inputArray = document.getElementsByTagName('input');

				var emptyPoError = true;
				var incorrectQtyPoError = false;

				for (var i = 0; i < inputArray.length; i++) {
					var minimumValue = inputArray[i].getAttribute('minimumValue');
					var reviewCount = inputArray[i].getAttribute('reviewCount');
					var value = inputArray[i].value;
					if (minimumValue) {
						emptyPoError = false;
						if (parseInt(value) < (parseInt(minimumValue) + parseInt(reviewCount))) {
							incorrectQtyPoError = true;
						}
					}
				}

				if (emptyPoError) {
					if (jQuery('#po_product_upload').val() != '') emptyPoError = false;
				}

				if (emptyPoError) {
					if (jQuery('#process-product-upload').length) emptyPoError = false;
				}

				if (emptyPoError) {
					alert('You must have at least one product on a purchase order.');
					jQuery('#po_editor_submit_button').removeAttr('disabled');
					return false;
				}
				else if (incorrectQtyPoError) {
					alert('You cannot change the quantity for an item on this purchase order to be less than the sum of the quantity you have already received and the quantity in open reviews.')
					jQuery('#po_editor_submit_button').removeAttr('disabled');
					return false;
				}

				<?php if ($action != 'edit') { ?>
				var args = [];
				var callback = {
					success: handleValidateSuccess,
					failure: handleValidateFailure,
					argument: args
				};
				var url = "po_orderNumberExists.php?po_number=" + document.getElementsByName('purchase_order_number')[0].value;

				YAHOO.util.Connect.asyncRequest('GET', url, callback);

				return false;
				<?php }
				else { ?>
				return true;
				<?php } ?>
			}

			var handleValidateFailure = function (o) {
				if (o.responseText !== undefined) {
					alert("Validate PO number failed: " + o.responseText);
					jQuery('#po_editor_submit_button').removeAttr('disabled');
				}
				else {
					alert("Validate PO number failed: no error message available");
					jQuery('#po_editor_submit_button').removeAttr('disabled');
				}
			}

			var handleValidateSuccess = function (o) {
				var response = eval(o.responseText);
				if (response.result) {
					alert ("This PO number already exists. Please choose another.");
					jQuery('#po_editor_submit_button').removeAttr('disabled');
				}
				else {
					document.forms['po_editor'].submit();
				}
			}

			function captureEnterKey(e) {
				var keynum;
				if (window.event) { // IE
					keynum = e.keyCode;
				}
				else if (e.which) { // Netscape/Firefox/Opera
					keynum = e.which;
				}

				if (keynum == 13) {
					addProductToForm();
					document.getElementById('add_product_start_button').focus();
					return false;
				}
				else {
					return true;
				}
			}
			function calculatePOTotals() {
				var po_total = 0;
				jQuery('.pop_cost').each(function(idx) {
					var stock_id = jQuery(this).attr('data-stock-id');
					var cost = parseFloat(jQuery(this).val());
					var qty = parseInt(jQuery('.pop_qty.pop-'+stock_id).val());

					var line_cost = cost * qty;

					po_total += line_cost;
					jQuery('#total_'+stock_id).html('$'+line_cost.toFixed(2));
				});
				/*for (var i = 0; i < document.po_product_ids.length; i++) {
					var qty = document.getElementsByName('quantity[' + document.po_product_ids[i] + ']')[0].value;
					var cost = document.getElementsByName('cost[' + document.po_product_ids[i] + ']')[0].value;
					var line_cost = qty * cost;
					po_total += line_cost;
					document.getElementById('total_' + document.po_product_ids[i]).innerHTML = '$' + line_cost.toFixed(2);
				}*/

				document.getElementById('po_total').innerHTML = '$' + po_total.toFixed(2);
			}

			function calculateExtWeight() {
				var ext_weight = 0;
				po_product_ids.forEach(function (id) {
					var weight = jQuery("#weight-"+id);
					var quantity = jQuery('#product-quantity-'+id);
					var total_weight = weight * quantity;
					ext_weight += total_weight;
					jQuery('#total_weight_'+id).html = total_weight.toFixed(2);
				});
				jQuery('#ext_weight').html = ext_weight;
			}

			function generate_po_number() {
				new Ajax.Request('po_editor.php',{
					method: 'get',
					parameters: {
						action: 'generate_po_number'
					},
					onComplete: function(transport) {
						$('purchase_order_number').value = transport.responseText;
					}
				});
			}

			jQuery('#administrator, #owner, #buyback_admin').change(function () {
				let email = jQuery(this).find(':selected').attr('data-email');
				jQuery('#email-from-address').append('<option value="'+email+'">'+email+'</option>');
			});
		</script>
		<!-- body_eof //-->
	</body>
<html>
