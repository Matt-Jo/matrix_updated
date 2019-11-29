<?php
require('includes/application_top.php');

// process
$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;
$rfq_id = !empty($_REQUEST['rfq_id'])?$_REQUEST['rfq_id']:NULL;

// this page shouldn't see an inordinate amount of traffic, we'll just do a check for expired RFQs right here at the top of every load
prepared_query::execute('UPDATE ck_rfqs SET active = 0 WHERE expiration_date IS NOT NULL AND DATE(NOW()) > expiration_date');

// possible actions:
// update - take the entered details and update the table (POST)
// mark-interested - mark a particular line as one we're interested in
// blank or [NONE] - we just want to display a blank form to create a new RFQ - not really an action (GET)
// edit or [NONE] - we want to display a form populated with the the current details to edit - not really an action yet (GET)
// results - display the results of the RFQ - not really an action (GET)

//$default_to = $_SESSION['login_email_address'];
$default_to = 'network-equipment@lists.uneda.com';

if (empty($_SESSION['perms']['rfq_signature']))
	$signature = "Thanks!\n\n".$_SESSION['perms']['admin_firstname'].' '.$_SESSION['perms']['admin_lastname']."\nCablesAndKits.com\nd) [DIRECT LINE] | t) [TOLL FREE] | f) [FAX LINE]\nIM: ".strtoupper($_SESSION['perms']['admin_firstname'])."atCK";
else
	$signature = $_SESSION['perms']['rfq_signature'];

if (empty($_SESSION['perms']['rfq_greeting']))
	$greeting = "Hi There!\n\nPlease provide pricing and availability for the following.";
else
	$greeting = $_SESSION['perms']['rfq_greeting'];

switch ($action) {
	case 'update_vendor':
		prepared_query::execute('update customers c set c.vendor_id = '.$_GET['vID'].' where c.customers_id = '.$_GET['customers_id']);
		header('Location: /admin/rfq_detail.php?rfq_id='.$_GET['rfq_id'].'&action=results');
		break;
	case 'delete_response':
		prepared_query::execute('delete from ck_rfq_response_products where rfq_response_product_id = "'. $_GET['delete_rrp_id'] .'"');
		header('Location: /admin/rfq_detail.php?rfq_id='.$_GET['rfq_id'].'&action=results');
		break;
	case 'edit_response':
		prepared_query::execute('update ck_rfq_response_products rrp set rrp.conditions_id = "'.$_POST['edit_condition'].'", rrp.quantity = "'. $_POST['edit_quantity'] .'", rrp.price = "'.$_POST['edit_price'].'", rrp.notes = "'.addslashes($_POST['edit_notes']).'" where rrp.rfq_response_product_id = "'. $_POST['edit_response_product_id'].'"');

		header('Location: /admin/rfq_detail.php?rfq_id='.$_POST['rfq_id'].'&action=results');
		break;
	case 'template':
		$new_rfq_id = prepared_query::insert('INSERT INTO ck_rfqs (nickname, admin_id, request_type, subject_line, request_details, created_date) SELECT nickname, ?, request_type, subject_line, request_details, NOW() FROM ck_rfqs WHERE rfq_id = ?', array($_SESSION['login_id'], $rfq_id));
		prepared_query::execute('INSERT INTO ck_rfq_products (rfq_id, stock_id, model_alias, conditions_id) SELECT ?, stock_id, model_alias, conditions_id FROM ck_rfq_products WHERE rfq_id = ?', array($new_rfq_id, $rfq_id));

		header('Location: /admin/rfq_detail.php?rfq_id='.$new_rfq_id);
		exit();
		break;
	case 'create-from-srl':
		$ipns = $_REQUEST['stock_id'];
		$nickname = "";
		if (is_array($ipns) && count($ipns) == 1) {
			$ipn = new ck_ipn2($ipns[0]);
			$products = $ipn->get_listings();
			$nickname = $products[0]->get_header('products_model');
		}
		// we're entering a new RFQ
		$rfq_id = prepared_query::insert('INSERT INTO ck_rfqs (admin_id, request_type, subject_line, nickname, created_date) VALUES (?, \'WTB\', \'WTB: \', ?, NOW())', array($_SESSION['login_id'], $nickname));

		if (is_array($ipns)) {
			foreach ($ipns as $idx => $stock_id) {
				$additional = prepared_query::fetch('SELECT psc.conditions as condition_id, IFNULL(p.products_model, psc.stock_name) as model_number FROM products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id AND p.products_status = 1 LEFT JOIN products p2 ON p.stock_id = p2.stock_id AND p2.products_status = 1 AND p.products_id > p2.products_id WHERE p2.products_id IS NULL AND psc.stock_id = ?', cardinality::ROW, array($stock_id));
				$qty = empty($_REQUEST['quantity'][$idx])?NULL:$_REQUEST['quantity'][$idx];
				prepared_query::execute('INSERT INTO ck_rfq_products (rfq_id, stock_id, model_alias, conditions_id, quantity) VALUES (?, ?, ?, ?, ?)', array($rfq_id, $stock_id, $additional['model_number'], $additional['condition_id'], $qty));
			}
		}

		header('Location: /admin/rfq_detail.php?rfq_id='.$rfq_id);
		exit();
		break;
	case 'update':
		if (empty($_REQUEST['skip_process'])) {
			if ($_REQUEST['greeting'] != $greeting) {
				if ($_REQUEST['greeting'] === '') $_REQUEST['greeting'] = NULL;
				prepared_query::execute('UPDATE admin SET rfq_greeting = ? WHERE admin_id = ?', array($_REQUEST['greeting'], $_SESSION['login_id']));
			}

			if ($_REQUEST['signature'] != $signature) {
				if ($_REQUEST['signature'] === '') $_REQUEST['signature'] = NULL;
				prepared_query::execute('UPDATE admin SET rfq_signature = ? WHERE admin_id = ?', array($_REQUEST['signature'], $_SESSION['login_id']));
			}

			//$_REQUEST['subject_line'] = preg_replace('/^(WTB|RFQ): ?/', '', $_REQUEST['subject_line']);

			$expiration_date = empty($_REQUEST['expiration_date'])?NULL:$_REQUEST['expiration_date'];
			if (empty($rfq_id)) {
				// we're entering a new RFQ
				$rfq_id = prepared_query::insert('INSERT INTO ck_rfqs (nickname, admin_id, request_type, subject_line, request_details, expiration_date, alert_creator, created_date) VALUES (:nickname, :admin_id, :request_type, :subject_line, :request_details, :expiration_date, :alert_creator, NOW())', [':nickname' => $_REQUEST['nickname'], ':admin_id' => $_SESSION['login_id'], ':request_type' => $_REQUEST['request_type'], ':subject_line' => $_REQUEST['subject_line'], ':request_details' => $_REQUEST['request_details'], ':expiration_date' => $expiration_date, ':alert_creator' => $__FLAG['alert_creator']?1:0]);

				$ipns = $_REQUEST['stock_id'];
				if (is_array($ipns)) {
					foreach ($ipns as $idx => $stock_id) {
						if (!empty($_REQUEST['remove'][$idx])) continue; // we "deleted" this one before even entering it
						$condition = $_REQUEST['condition'][$idx];
						$qty = empty($_REQUEST['quantity'][$idx])?NULL:$_REQUEST['quantity'][$idx];
						$comment = empty($_REQUEST['comment'][$idx])?NULL:$_REQUEST['comment'][$idx];
						$qtyplus = empty($_REQUEST['qtyplus'][$idx])?0:1;
						prepared_query::execute('INSERT INTO ck_rfq_products (rfq_id, stock_id, model_alias, conditions_id, quantity, qtyplus, comment) VALUES (?, ?, ?, ?, ?, ?, ?)', array($rfq_id, $stock_id, $_REQUEST['alias'][$idx], $condition, $qty, $qtyplus, $comment));
					}
				}
			}
			else {
				// we're updating an existing RFQ
				prepared_query::execute('UPDATE ck_rfqs SET nickname = :nickname, admin_id = :admin_id, request_type = :request_type, subject_line = :subject_line, request_details = :request_details, expiration_date = :expiration_date, alert_creator = :alert_creator WHERE rfq_id = :rfq_id', [':nickname' => $_REQUEST['nickname'], ':admin_id' => $_SESSION['login_id'], ':request_type' => $_REQUEST['request_type'], ':subject_line' => $_REQUEST['subject_line'], ':request_details' => $_REQUEST['request_details'], ':expiration_date' => $expiration_date, ':alert_creator' => $__FLAG['alert_creator']?1:0, ':rfq_id' => $rfq_id]);

				$lines = $_REQUEST['canonical_id'];
				if (is_array($lines)) {
					foreach ($lines as $idx => $rfq_product_id) {
						if (empty($rfq_product_id) && !empty($_REQUEST['remove'][$idx])) continue; // we "deleted" this one before even entering it
						elseif (!empty($_REQUEST['remove'][$idx])) prepared_query::execute('DELETE FROM ck_rfq_products WHERE rfq_product_id = ?', $rfq_product_id);
						else {
							$condition = $_REQUEST['condition'][$idx];
							$qty = empty($_REQUEST['quantity'][$idx])?NULL:$_REQUEST['quantity'][$idx];
							$comment = empty($_REQUEST['comment'][$idx])?NULL:$_REQUEST['comment'][$idx];
							$qtyplus = empty($_REQUEST['qtyplus'][$idx])?0:1;

							if (empty($rfq_product_id)) prepared_query::execute('INSERT INTO ck_rfq_products (rfq_id, stock_id, model_alias, conditions_id, quantity, qtyplus, comment) VALUES (?, ?, ?, ?, ?, ?, ?)', array($rfq_id, $_REQUEST['stock_id'][$idx], $_REQUEST['alias'][$idx], $condition, $qty, $qtyplus, $comment));
							else prepared_query::execute('UPDATE ck_rfq_products SET model_alias = ?, conditions_id = ?, quantity = ?, qtyplus = ?, comment = ? WHERE rfq_product_id = ?', array($_REQUEST['alias'][$idx], $condition, $qty, $qtyplus, $comment, $rfq_product_id));
						}
					}
				}
			}
		}

		if ($_REQUEST['submit'] == 'Force Expire') {
			prepared_query::execute('UPDATE ck_rfqs SET active = 0 WHERE rfq_id = ?', $rfq_id);
			header('Location: /admin/rfq_list.php');
		}
		elseif ($_REQUEST['submit'] == 'Save & Publish') {
			$rfq = prepared_query::fetch('SELECT r.*, a.admin_firstname, a.admin_lastname, a.admin_email_address FROM ck_rfqs r JOIN admin a ON r.admin_id = a.admin_id WHERE r.rfq_id = ?', cardinality::ROW, $rfq_id);
			$ipns = prepared_query::fetch('SELECT rp.*, c.conditions_name, psc.stock_name FROM ck_rfq_products rp JOIN products_stock_control psc ON rp.stock_id = psc.stock_id LEFT JOIN conditions c ON rp.conditions_id = c.conditions_id WHERE rp.rfq_id = ?', cardinality::SET, $rfq_id);

			//$msg = "Please let me know what you have on hand. Enter your response at https://www.cablesandkits.com/wtb.php?id=".$rfq_id."\n\n";
			$msg = $_REQUEST['greeting']."\n\n";

			if (!empty($rfq['request_details'])) $msg .= $rfq['request_details']."\n\n";

			// loop through once to get the longest fields, init to header length
			$longest_qty = 3;
			$longest_part = 4;
			$longest_condition = 9;
			foreach ($ipns as $ipn) {
				$qty = (empty($ipn['quantity'])?'ALL':$ipn['quantity']).($ipn['qtyplus']==1?'+':'');
				$cond = empty($ipn['conditions_name'])?'ANY':$ipn['conditions_name'];
				$longest_qty = max(strlen($qty), $longest_qty);
				$longest_part = max(strlen($ipn['model_alias']), $longest_part);
				$longest_condition = max(strlen($cond), $longest_condition);
			}

			// pad to 2 free spaces
			$longest_qty += 2;
			$longest_part += 2;
			$longest_condition += 2;

			$qtypad = ceil($longest_qty/8);
			$partpad = ceil($longest_part/8);
			$conditionpad = ceil($longest_condition/8);

			$header = 'QTY'.str_repeat("\t", $qtypad).'CONDITION'.str_repeat("\t", $conditionpad-1).'PART'.str_repeat("\t", $partpad).'NOTES'."\n";
			$msg .= $header;
			$msg .= str_repeat('-', strlen($header)*3)."\n";

			foreach ($ipns as $ipn) {
				$qty = (empty($ipn['quantity'])?'ALL':$ipn['quantity']).($ipn['qtyplus']==1?'+':'');
				$cond = empty($ipn['conditions_name'])?'ANY':$ipn['conditions_name'];

				$qtyadj = floor(strlen($qty)/8);
				$partadj = floor(strlen($ipn['model_alias'])/8);
				$conditionadj = floor(strlen($cond)/8);

				$msg .= $qty.str_repeat("\t", $qtypad-$qtyadj);
				$msg .= $cond.str_repeat("\t", $conditionpad-$conditionadj);
				$msg .= $ipn['model_alias'].str_repeat("\t", $partpad-$partadj);
				if (!empty($ipn['comment'])) $msg .= $ipn['comment'];
				$msg .= "\n";
			}

			$msg .= "\nPlease respond using the following link:\nhttps://www.cablesandkits.com/VendorPortal/".$rfq_id."\n\n";

			// for whatever reason, it's not showing the newlines that are sent from the client when they come after the linkified email address
			$msg .= "\n".$_REQUEST['signature']."\n";

			if (empty($_REQUEST['send_to'])) $send_to = $default_to;
			else $send_to = $_REQUEST['send_to'];

            $mailer = service_locator::get_mail_service();
            
            $mail = $mailer->create_mail();
			$mail->set_body(null,$msg);
			$mail->set_from($rfq['admin_email_address'], $rfq['admin_firstname'].' '.$rfq['admin_lastname']);
			$mail->add_to($send_to);
			$mail->set_subject($rfq['subject_line']);
			$mailer->send($mail);

			prepared_query::execute('UPDATE ck_rfqs SET published_date = NOW(), full_email_text = ? WHERE rfq_id = ?', array($msg, $rfq_id));
		}
		else {
			// we're just saving it, which we've done above, so nothing left to do
		}
		if (!empty($_REQUEST['source']) && $_REQUEST['source'] == 'ipn_editor' && !empty($_REQUEST['ipn'])) {
			header('Location: /admin/ipn_editor.php?ipnId='.$_REQUEST['ipn']);
		}
		else {
			header('Location: /admin/rfq_detail.php?rfq_id='.$rfq_id);
		}
		exit();
		break;
	/*case 'mark-interested':
		$interested = $_POST['interested'];
		$result = array('status' => 0);
		try {
			foreach ($interested as $rfq_response_product_id => $status) {
				prepared_query::execute('UPDATE ck_rfq_response_products SET interested = ? WHERE rfq_response_product_id = ?', array($status, $rfq_response_product_id));
			}
			$result['status'] = 1;
			$result['rfq_response_product_id'] = $rfq_response_product_id;
			$result['interested_status'] = $status;
		}
		catch (Exception $e) {
			// don't need to do anything
		}

		echo json_encode($result);
		exit();
		break;*/
	case 'blank':
	case 'edit':
	case 'results':
		// these are all display actions, we don't need to do anything with them up here, just catch the case
		break;
	default:
		// catch the empty case
		if (!empty($rfq_id)) $action = 'edit';
		else $action = 'blank';
		break;
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

$content_map->rfq_id = $rfq_id;

$conditions = prepared_query::fetch('SELECT * FROM conditions ORDER BY conditions_name', cardinality::SET);
if (!empty($rfq_id)) {
	$rfq = prepared_query::fetch('SELECT r.*, a.admin_firstname, a.admin_lastname, a.admin_email_address FROM ck_rfqs r JOIN admin a ON r.admin_id = a.admin_id WHERE r.rfq_id = ?', cardinality::ROW, $rfq_id);
	$ipns = prepared_query::fetch('SELECT rp.*, psc.stock_name FROM ck_rfq_products rp JOIN products_stock_control psc ON rp.stock_id = psc.stock_id WHERE rp.rfq_id = ?', cardinality::SET, $rfq_id);
}
else {
	$rfq = prepared_query::fetch('SELECT admin_firstname, admin_lastname, admin_email_address FROM admin WHERE admin_id = ?', cardinality::ROW, $_SESSION['login_id']);
	$ipns = [];
}

$content_map->conditions = $conditions;

$content_map->default_to = $default_to;

if (in_array($action, ['blank', 'edit'])) {
	$content_map->manage = 1;

	if (!empty($rfq['rfq_id'])) {
		if ($rfq['request_type'] == 'RFQ') $rfq['rt_rfq'] = 1;
		elseif ($rfq['request_type'] == 'WTB') $rfq['rt_wtb'] = 1;

		if (!empty($rfq['active'])) $rfq['active?'] = 1;

		if (empty($rfq['alert_creator'])) unset($rfq['alert_creator']);

		if (!empty($rfq['published_date'])) {
			$published_date = new DateTime($rfq['published_date']);
			$rfq['published_date'] = $published_date->format('m/d/Y');
		}
	}

	$content_map->rfq = $rfq;

	foreach ($ipns as $idx => &$ipn) {
		$ipn['idx'] = $idx;
		$ipn['conditions'] = $conditions;

		foreach ($ipn['conditions'] as &$condition) {
			if ($ipn['conditions_id'] == $condition['conditions_id']) $condition['selected?'] = 1;
		}

		if (!empty($ipn['qtyplus'])) $ipn['qtyplus?'] = 1;
	}

	$content_map->ipns = $ipns;

	$content_map->ipn_count = count($ipns);
}
elseif ($action == 'results') {
	$content_map->results = 1;

	$content_map->rfq = $rfq;

	$content_map->responses = prepared_query::fetch("SELECT rrp.rfq_response_product_id, rrp.conditions_id, rr.rfq_response_id, c.vendor_id, v.vendors_company_name as vendor_name, c.customers_id, c.customers_email_address as account_email_address, IFNULL(cel.customers_emailaddress, c.customers_email_address) as customers_email_address, IFNULL(cel.customers_firstname, c.customers_firstname) as customers_firstname, IFNULL(cel.customers_lastname, c.customers_lastname) as customers_lastname, CASE WHEN NULLIF(rr.notes, '') IS NOT NULL THEN 1 ELSE 0 END as has_notes, rr.notes, rrp.notes as ipn_notes, rr.shipping_included, rr.created_date, rrp.stock_id, psc.stock_name, rc.conditions_name as requested_condition, rrc.conditions_name as response_condition, rp.quantity as requested_quantity, rrp.quantity, rrp.price, rrp.interested FROM ck_rfq_responses rr JOIN customers c ON rr.customers_id = c.customers_id LEFT JOIN vendors v ON c.vendor_id = v.vendors_id JOIN ck_rfq_response_products rrp ON rr.rfq_response_id = rrp.rfq_response_id JOIN conditions rrc ON rrp.conditions_id = rrc.conditions_id JOIN ck_rfq_products rp ON rrp.rfq_id = rp.rfq_id AND rrp.stock_id = rp.stock_id LEFT JOIN conditions rc ON rp.conditions_id = rc.conditions_id JOIN products_stock_control psc ON rp.stock_id = psc.stock_id LEFT JOIN customers_extra_logins cel on rr.customers_extra_login_id = cel.customers_extra_logins_id WHERE rrp.rfq_id = :rfq_id ORDER BY psc.stock_name ASC", cardinality::SET, [':rfq_id' => $rfq_id]);

	foreach ($content_map->responses as &$response) {
		$ipn = new ck_ipn2($response['stock_id']);
		if (!empty($response['has_notes'])) $response['has_notes?'] = 1;
		if (!empty($response['vendor_name'])) $response['has_vendor?'] = 1;

		$response['formatted_price'] = CK\text::monetize($response['price']);

		if (!empty($response['shipping_included'])) $response['shipping_included?'] = 1;

		$created_date = new DateTime($response['created_date']);
		$response['created_date'] = $created_date->format('m/d/Y H:i:s');

		$response['handle_qty'] = !empty($response['requested_quantity'])?min($response['requested_quantity'], $response['quantity']):$response['quantity'];

		$to_address = api_ups::$local_origin;
		$address_type = new ck_address_type();
		$address_type->load('header', $to_address);
		$to = new ck_address2(NULL, $address_type);

		$customer = new ck_customer2($response['customers_id']);
		$from = $customer->get_default_address();

		/*$vendor = new ck_vendor($response['vendor_id']);
		$from = $vendor->get_default_address();*/

		$response['entry_postcode'] = $from->get_header('postcode');

		$response['vendor_country'] = $from->get_header('countries_iso_code_2');

		$estimated_product_weight = $response['handle_qty'] * $ipn->get_header('stock_weight');
		$estimated_shipped_weight = $estimated_product_weight + max(shipit::$box_tare_weight, $estimated_product_weight * shipit::$box_tare_factor);

		$max_weight = 50;
		$package_count = ceil($estimated_shipped_weight / $max_weight);
		$estimated_package_weight = $estimated_shipped_weight / $package_count;

		$packages = [];
		for ($i=0; $i<$package_count; $i++) {
			$packages[] = ['weight' => $estimated_package_weight];
		}

		$quotes = api_ups::quote_rates($packages, $to, $from);

		$est_shipping = NULL;

		if ($from->get_header('countries_id') == $to->get_header('countries_id')) {
			$selected_service_code = \Ups\Entity\Service::S_GROUND;
			$est_shipping = $quotes[$selected_service_code]['negotiated'];
			$response['service'] = $quotes[$selected_service_code]['service'];
		}
		else {
			foreach ($quotes as $service_code => $quote) {
				if (!isset($est_shipping) || $quote['negotiated'] < $est_shipping) {
					$selected_service_code = $service_code;
					$est_shipping = $quote['negotiated'];
					$response['service'] = $quote['service'];
				}
			}
		}

		$times = api_ups::transit_time(['weight' => $estimated_shipped_weight, 'package_count' => count($packages), 'total_value' => $est_shipping + ($response['price'] * $response['handle_qty'])], $to, $from);
		$response['lead_time'] = $times[$selected_service_code]['transit_days'];
		$response['lead_time_date'] = $times[$selected_service_code]['arrival_weekday'].', '.$times[$selected_service_code]['arrival_date'];

		$response['freight'] = $est_shipping;
		$response['formatted_freight'] = CK\text::monetize($est_shipping);
		$response['freight_weight'] = $estimated_shipped_weight;

		$response['landed_cost'] = $response['price'] + ($est_shipping / $response['handle_qty']);
		$response['formatted_landed_cost'] = CK\text::monetize($response['landed_cost']);
		$response['stock_price'] = CK\text::monetize($ipn->get_price('list'));
		$response['stock_gross_margin_percentage'] = number_format(($ipn->get_price('list') - $response['landed_cost']) / $ipn->get_price('list') * 100, 2).'%';
		$response['dealer_price'] = CK\text::monetize($ipn->get_price('dealer'));
		$response['wholesale_high_price'] = CK\text::monetize($ipn->get_price('wholesale_high'));
		$response['wholesale_low_price'] = CK\text::monetize($ipn->get_price('wholesale_low'));

		/*$response['lead_time'] = $ipn->get_header('lead_time');
		$response['lead_time_date'] = (new DateTime('+'.$response['lead_time'].' days'))->format('m/d/Y');
		if ($ipn->get_header('vendors_id') != $response['vendor_id']) {
			$response['lead_time_vendor'] = $ipn->get_header('vendors_company_name');
		}*/
	}
}

if (!empty($_SESSION['perms']['rfq_signature'])) $content_map->custom_sig = 1;
$content_map->signature = $signature;

if (!empty($_SESSION['perms']['rfq_greeting'])) $content_map->custom_greet = 1;
$content_map->greeting = $greeting;

$cktpl->content('includes/templates/page-rfq-detail.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
