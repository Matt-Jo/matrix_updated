<?php
require('includes/application_top.php');

$rfq_id = !empty($_REQUEST['id'])?$_REQUEST['id']:NULL;
$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;

$req_type = NULL;
if (!empty($rfq_id) && !is_numeric($rfq_id)) {
	$req_type = $rfq_id;
	$rfq_id = NULL;
}

// this page shouldn't see an inordinate amount of traffic, we'll just do a check for expired RFQs right here at the top of every load
prepared_query::execute('UPDATE ck_rfqs SET active = 0 WHERE expiration_date IS NOT NULL AND DATE(NOW()) > expiration_date');

if (!empty($rfq_id)) {
	$rfq = prepared_query::fetch('SELECT r.*, a.admin_firstname, a.admin_lastname, a.admin_email_address, a.rfq_signature FROM ck_rfqs r JOIN admin a ON r.admin_id = a.admin_id WHERE r.rfq_id = ? AND published_date IS NOT NULL', cardinality::ROW, $rfq_id);
	$ipns = prepared_query::fetch('SELECT rp.*, c.conditions_name, psc.stock_name FROM ck_rfqs r JOIN ck_rfq_products rp ON r.rfq_id = rp.rfq_id JOIN products_stock_control psc ON rp.stock_id = psc.stock_id LEFT JOIN conditions c ON rp.conditions_id = c.conditions_id WHERE rp.rfq_id = ? AND r.published_date IS NOT NULL', cardinality::SET, $rfq_id);
}
elseif (!empty($req_type) && strtolower($req_type) == 'lookup') {
	$content = 'vp_lookup';

	switch ($action) {
		case 'part-lookup':
			$results = ['results' => []];
			$part_number = trim($_GET['ipn_lookup']);
			if (empty($part_number)) exit();
			if ($products = prepared_query::fetch('SELECT p.products_id, psc.stock_name, p.products_model, IFNULL(COUNT(pa.product_addon_id), 0) as included_accessories, c.conditions_name as `condition` FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN conditions c ON psc.conditions = c.conditions_id JOIN product_addons pa ON p.products_id = pa.product_id AND pa.included = 1 WHERE p.products_model LIKE :part_number OR psc.stock_name LIKE :part_number GROUP BY p.products_id, p.products_model ORDER BY psc.stock_name', cardinality::SET, [':part_number' => '%'.$part_number.'%'])) {
				foreach ($products as $product) {
					$product['result_id'] = $product['products_id'];
					$product['field_value'] = $product['products_model'];
					$product['ipn'] = preg_replace('/('.$part_number.')/i', '<strong>$1</strong>', $product['stock_name']);
					$product['result_label'] = preg_replace('/('.$part_number.')/i', '<strong>$1</strong>', $product['products_model']);
					$results['results'][] = $product;
				}
			}

			echo json_encode($results);
			exit();
			break;
		case 'part-select':
			$products_id = $_GET['products_id'];
			$results = ['products' => []];

			$product = new ck_product_listing($products_id);

			$results['base_product_model'] = $product->get_header('products_model');
			$results['base_product_url'] = $product->get_url();
			$results['base_product_link'] = $product->is_viewable()?1:0;
			$results['base_product_condition'] = $product->get_header('conditions_name');

			if ($product->has_options('included')) {
				foreach ($product->get_options('included') as $option) {
					$results['products'][] = [
						'products_id' => $option['products_id'],
						'url' => $option['listing']->get_url(),
						'name' => $option['name'],
						'price' => CK\text::monetize($option['price']),
						'ipn' => $option['listing']->get_ipn()->get_header('ipn'),
						'model' => $option['listing']->get_header('products_model'),
						'link' => $option['listing']->is_viewable()?1:0,
						'short_desc' => $option['listing']->get_header('products_head_desc_tag')
					];
				}
			}

			echo json_encode($results);
			exit();
			break;
	}
}
elseif (!empty($req_type)) {
	$ipns = prepared_query::fetch('SELECT r.rfq_id, r.nickname, a.admin_firstname, a.admin_lastname, a.admin_email_address, r.request_details, r.published_date, r.expiration_date, rp.rfq_product_id, rp.stock_id, rp.model_alias, rp.conditions_id, rp.quantity, rp.qtyplus, c.conditions_name, psc.stock_name, rp.comment FROM ck_rfqs r JOIN ck_rfq_products rp ON r.rfq_id = rp.rfq_id JOIN admin a ON r.admin_id = a.admin_id JOIN products_stock_control psc ON rp.stock_id = psc.stock_id LEFT JOIN conditions c ON rp.conditions_id = c.conditions_id WHERE r.active = 1 AND published_date IS NOT NULL AND r.request_type LIKE ? ORDER BY rp.model_alias', cardinality::SET, $req_type);
}
else {
	$ipns = prepared_query::fetch('SELECT r.rfq_id, r.nickname, a.admin_firstname, a.admin_lastname, a.admin_email_address, r.request_details, r.published_date, r.expiration_date, rp.rfq_product_id, rp.stock_id, rp.model_alias, rp.conditions_id, rp.quantity, rp.qtyplus, c.conditions_name, psc.stock_name, rp.comment FROM ck_rfqs r JOIN ck_rfq_products rp ON r.rfq_id = rp.rfq_id JOIN admin a ON r.admin_id = a.admin_id JOIN products_stock_control psc ON rp.stock_id = psc.stock_id LEFT JOIN conditions c ON rp.conditions_id = c.conditions_id WHERE r.active = 1 AND published_date IS NOT NULL ORDER BY rp.model_alias', cardinality::SET);
}

$conditions = prepared_query::fetch('SELECT * FROM conditions ORDER BY conditions_name', cardinality::SET);

if (!empty($_SESSION['customer_extra_login_id'])) $customer = prepared_query::fetch('SELECT ce.customers_firstname, ce.customers_lastname, ce.customers_emailaddress as customers_email_address, c.customers_default_address_id FROM customers_extra_logins ce LEFT JOIN customers c ON ce.customers_id = c.customers_id WHERE ce.customers_extra_logins_id = ?', cardinality::ROW, $_SESSION['customer_extra_login_id']);
else $customer = prepared_query::fetch('SELECT * FROM customers WHERE customers_id = ?', cardinality::ROW, @$_SESSION['customer_id']);

switch ($action) {
	case 'save':
		$process_error = array();
		/*if (empty($_POST['name'])) $process_error[] = 'You must enter a name.';
		if (empty($_POST['email'])) $process_error[] = 'You must enter an email address.';
		if (empty($_POST['prior_relationship'])) $_POST['prior_relationship'] = 0;
		if (empty($_POST['zip_code'])) $process_error[] = 'You must enter a zip code.';*/
		if (!isset($_POST['shipping_included'])) $process_error[] = 'You must select whether this includes free standard shipping or not.';

		if (!empty($rfq_id)) {
			if (empty($rfq)) {
				$process_error[] = 'The request you selected does not exist.';
			}
			elseif ($rfq['active'] == 0) {
				$process_error[] = 'It appears the request has already been purchased or is expired. If you believe you\'ve received this message in error, please email '.$rfq['admin_email_address'];
			}
		}

		if (empty($process_error)) {
			try {
				//$vendors_id = prepared_query::fetch('SELECT vendors_id FROM vendors WHERE vendors_email_address LIKE ? ORDER BY nav_id DESC, vendors_id DESC', cardinality::SINGLE, $customer['customers_email_address']);
				$rfq_response_id = prepared_query::insert('INSERT INTO ck_rfq_responses (rfq_id, customers_id, customers_extra_login_id, notes, shipping_included, created_date, last_update) VALUES (?, ?, ?, ?, ?, NOW(), NOW())', array($rfq_id, $_SESSION['customer_id'], $_SESSION['customer_extra_login_id'], $_POST['gen_notes'], $_POST['shipping_included']));

				$response_ipns = array();

				foreach ($_POST['quantity'] as $rfq_product_id => $quantity) {
					foreach ($quantity as $repidx => $qty) {
						$qty = (int) $qty?(int) $qty:0;
						// we might at some point wish to enter in a row for every requested item rather than just those that are available
						// but for now we're not using it that way
						if (empty($qty)) continue;

						$price = preg_replace('/[^-.\d]/', '', $_POST['price'][$rfq_product_id][$repidx]);
						$price = is_numeric($price)?$price:0;

						// force to refurb if somehow it got missed - should *never* happen unless there's a browser error or the user is deliberately futzing with it
						$conditions_id = $_POST['condition'][$rfq_product_id][$repidx]; //?$_POST['condition'][$rfq_product_id][$repidx]:2;

						$notes = trim($_POST['notes'][$rfq_product_id][$repidx])?trim($_POST['notes'][$rfq_product_id][$repidx]):NULL;

						if (empty($price)) $process_error[] = 'You must enter a price of all items.';
						if (!is_numeric($conditions_id)) $process_error[] = 'You must select a condition for all items.';

						$details = prepared_query::fetch('SELECT rp.rfq_id, rp.stock_id, rp.model_alias, r.active, a.admin_email_address FROM ck_rfq_products rp JOIN ck_rfqs r ON rp.rfq_id = r.rfq_id LEFT JOIN admin a ON r.admin_id = a.admin_id WHERE rp.rfq_product_id = ?', cardinality::ROW, $rfq_product_id);

						if (empty($details)) {
							$process_error[] = 'One of the items you responded for could not be found.';
						}
						elseif ($details['active'] == 0) {
							$process_error[] = 'The response for model # '.$details['model_alias'].' is no longer active. If you believe you\'ve received this message in error, please email '.$details['admin_email_address'];
						}

						if (empty($process_error)) prepared_query::execute('INSERT INTO ck_rfq_response_products (rfq_response_id, rfq_id, stock_id, conditions_id, quantity, price, notes) VALUES (?, ?, ?, ?, ?, ?, ?)', array($rfq_response_id, $details['rfq_id'], $details['stock_id'], $conditions_id, $qty, $price, $notes));

						/*if (empty($rfq)) {
							$rfq = prepared_query::fetch('SELECT r.*, a.admin_firstname, a.admin_lastname, a.admin_email_address, a.rfq_signature FROM ck_rfqs r JOIN admin a ON r.admin_id = a.admin_id WHERE r.rfq_id = ?', cardinality::ROW, $details['rfq_id']);
						}*/
					}
				}

				if (empty($process_error)) {

					if (!empty($_POST['vendor_preference'])) {
						if (empty($_SESSION['customer_extra_login_id'])) {
							prepared_query::execute('UPDATE customers c SET c.rfq_free_ground_shipping = ? WHERE c.customers_id = ?', array($_POST['vendor_preference'], $_SESSION['customer_id']));
						}
						else {
							prepared_query::execute('UPDATE customers_extra_logins cel SET cel.rfq_free_ground_shipping = ? WHERE cel.customers_extra_logins_id = ?', array($_POST['vendor_preference'], $_SESSION['customer_extra_login_id']));
						}
					}

					$_SESSION['rfq_response_id'] = $rfq_response_id;

					$msg = 'Hi '.$customer['customers_firstname'].','."\n\n";
					$msg .= 'Here is a summary of the offer you recently submitted through our <a href="https://www.cablesandkits.com/VendorPortal/'.$rfq_id.'">Vendor Portal</a>.'."\n\n";

					$ipns = prepared_query::fetch('SELECT rrp.*, rp.model_alias, c.conditions_name FROM ck_rfq_response_products rrp JOIN ck_rfq_responses rr ON rrp.rfq_response_id = rr.rfq_response_id JOIN ck_rfq_products rp ON rrp.rfq_id = rp.rfq_id AND rrp.stock_id = rp.stock_id JOIN conditions c ON rrp.conditions_id = c.conditions_id WHERE rrp.rfq_response_id = ?', cardinality::SET, array($rfq_response_id));

					// loop through once to get the longest fields, init to header length
					/*$longest_qty = 3;
					$longest_part = 4;
					$longest_condition = 9;
					$longest_price = 5;
					foreach ($ipns as $ipn) {
						$longest_qty = max(strlen($ipn['quantity']), $longest_qty);
						$longest_part = max(strlen($ipn['model_alias']), $longest_part);
						$longest_condition = max(strlen($ipn['conditions_name']), $longest_condition);
						$longest_price = max(strlen('$'.number_format($ipn['price'], 2)), $longest_price);
					}

					// pad to 2 free spaces
					$longest_qty += 2;
					$longest_part += 2;
					$longest_condition += 2;
					$longest_price += 2;

					$qtypad = ceil($longest_qty/8);
					$partpad = ceil($longest_part/8);
					$conditionpad = ceil($longest_condition/8);
					$pricepad = ceil($longest_price/8);*/

					$msg .= '<style> #parts th, #parts td { padding-right:20px; text-align:left; } #parts th { border-bottom:1px solid #000; font-size:.9em; } </style>';

					$msg .= '<table border="0" cellpadding="0" cellspacing="0" id="parts">';
					$msg .= '<tr><th>QTY</th><th>CONDITION</th><th>PRICE</th><th>PART</th><th>NOTES</th></tr>';
					/*$header = 'QTY'.str_repeat("\t", $qtypad).'CONDITION'.str_repeat("\t", $conditionpad-1).'PRICE'.str_repeat("\t", $pricepad).'PART'.str_repeat("\t", $partpad).'NOTES'."\n";
					$msg .= $header;
					$msg .= str_repeat('-', strlen($header)*3)."\n";*/

					// since email uses a variable width font, a strict character count will often not line up correctly
					// this seems to make more of a difference with model numbers, so we lengthen it artificially
					//$model_fudge_factor = 1.25;

					foreach ($ipns as $ipn) {
						/*$qtyadj = floor(strlen($ipn['quantity'])/8);
						$partadj = floor(strlen($ipn['model_alias'])/8);
						$conditionadj = floor(strlen($ipn['conditions_name'])/8);*/
						$price = '$'.number_format($ipn['price'], 2);
						/*$priceadj = floor(strlen($price)/8);*/

						$msg .= '<tr><td>'.$ipn['quantity'].'</td><td>'.$ipn['conditions_name'].'</td><td>'.$price.'</td><td>'.$ipn['model_alias'].'</td><td>'.$ipn['notes'].'</td></tr>';

						/*$msg .= $ipn['quantity'].str_repeat("\t", $qtypad-$qtyadj);
						$msg .= $ipn['conditions_name'].str_repeat("\t", $conditionpad-$conditionadj);
						$msg .= $price.str_repeat("\t", $pricepad-$priceadj);
						$msg .= $ipn['model_alias'].str_repeat("\t", $partpad-$partadj);
						if (!empty($ipn['notes'])) $msg .= $ipn['notes'];

						$msg .= "\n";*/
					}
					$msg .= '</table>';
					$msg .= "\n";

					if ($_POST['shipping_included']) $msg .= '**Includes Free Shipping**'."\n\n";

					$msg .= 'Thank you for offering/quoting us the above products!'."\n\n";

					$msg .= $rfq['rfq_signature'];

					if (empty($rfq)) {
						$rfq = prepared_query::fetch("SELECT 'CK RE-source Submission' as subject_line, a.admin_firstname, a.admin_lastname, a.admin_email_address, a.rfq_signature FROM configuration c JOIN admin a ON c.configuration_value = a.admin_email_address WHERE c.configuration_key = 'DEFAULT_VP_CONTACT_EMAIL'", cardinality::ROW);
					}
					else {
						$msg .= "\n\n\n\n";
						$msg .= '-----Original Message-----'."\n";
						$msg .= '<strong>From:</strong> '.$rfq['admin_firstname'].' '.$rfq['admin_lastname'].' [<a href="mailto:'.$rfq['admin_email_address'].'">mailto:'.$rfq['admin_email_address'].'</a>]'."\n";
						$pubdate = new DateTime($rfq['published_date']);
						$msg .= '<strong>Sent:</strong> '.$pubdate->format('l, F d, Y g:i A')."\n";
						$msg .= '<strong>To:</strong> '.$customer['customers_firstname'].' '.$customer['customers_lastname']."\n";
						$msg .= '<strong>Subject:</strong> '.$rfq['subject_line']."\n\n\n";
						$msg .= $rfq['full_email_text'];

						//preg_replace('/^/m', '> ', $rfq['full_email_text'])."\n";
					}

                    $mailer = service_locator::get_mail_service();
                    $mail = $mailer->create_mail();
					$mail->set_body(nl2br($msg));
					$mail->set_from($rfq['admin_email_address'], $rfq['admin_firstname'].' '.$rfq['admin_lastname']);
					if (!empty($rfq['alert_creator'])) $mail->add_bcc($rfq['admin_email_address']);
					$mail->add_to($customer['customers_email_address']); // this is the one we want at go-live
					$mail->set_subject('RE: '.$rfq['subject_line']);
					$mailer->send($mail);

					header('Location: /VendorPortal/'.$rfq_id.'?action=saved');
					exit();
				}
				else {
					prepared_query::execute('DELETE FROM ck_rfq_response_products WHERE rfq_response_id = ?', array($rfq_response_id));
					prepared_query::execute('DELETE FROM ck_rfq_responses WHERE rfq_response_id = ?', array($rfq_response_id));
				}
			}
			catch (Exception $e) {
				echo '<pre>';
				print_r($e);
				echo '</pre>';
				exit();
			}
		}

		if (!empty($process_error)) $process_error = implode("<br>", $process_error);
		break;
	case 'saved':
		if (!empty($_SESSION['rfq_response_id'])) {
			$rfq_response_id = $_SESSION['rfq_response_id'];
			unset($_SESSION['rfq_response_id']);
		}
		else {
			$rfq_response_id = $_REQUEST['response_id'];
		}

		$response = prepared_query::fetch('SELECT rr.*, r.nickname FROM ck_rfq_responses rr JOIN ck_rfqs r ON rr.rfq_id = r.rfq_id WHERE rr.rfq_response_id = ?', cardinality::ROW, array($rfq_response_id));
		$response_ipns = prepared_query::fetch('SELECT rrp.*, rp.model_alias, c.conditions_name FROM ck_rfq_response_products rrp JOIN ck_rfq_products rp ON rrp.rfq_id = rp.rfq_id AND rrp.stock_id = rp.stock_id JOIN conditions c ON rrp.conditions_id = c.conditions_id WHERE rrp.rfq_response_id = ?', cardinality::SET, $rfq_response_id);

		break;
	default:
		break;
}

if (empty($content)) $content = 'wtb';

require('templates/Pixame_v1/main_page.tpl.php');
