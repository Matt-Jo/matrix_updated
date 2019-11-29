<?php
require_once('includes/application_top.php');

$id = $_REQUEST['id'];

$ck_rma = new ck_rma2($id);

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 're-open':
			if (!empty($id)) {
				try {
					prepared_query::execute('UPDATE rma SET closed = 0 WHERE id = ?', array($id));
				}
				catch (Exception $e) {
					// shouldn't ever get here
				}
			}
			header('Location: /admin/rma-detail.php?id='.$id);
			exit;

		case 'create_shipping_invoice':
			$ship_total = $ck_rma->get_sales_order()->get_simple_totals('shipping');
			$rma_ship_total = prepared_query::fetch("SELECT SUM(ABS(ait.invoice_total_price)) FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_shipping' WHERE ai.rma_id = ?", cardinality::SINGLE, array($id));
			$remaining_shipping = $ship_total - $rma_ship_total;

			$refund_shipping = CK\text::demonetize($_POST['refund_shipping']);

			if (!is_numeric($refund_shipping) || !($refund_shipping > 0)) {
				echo json_encode(['error' => 'You attempted to refund $'.number_format($refund_shipping, 2).', this is an invalid value. Please try again.']);
				break;
			}

			// since we need to be accurate down to the penny, let's make them whole numbers
			if ((int)($refund_shipping*100) <= (int)($remaining_shipping*100)) {
				try {
					ck_invoice::create_credit_from_rma_receipt($ck_rma, [], $refund_shipping);
					echo json_encode(['success' => 1]);
				}
				catch (Exception $e) {
					echo json_encode(['error' => $e->getMessage()]);
				}
			}
			else {
				echo json_encode(['error' => 'You attempted to refund $'.number_format($refund_shipping, 2).', please adjust the amount so that it does not exceed the remaining $'.number_format($remaining_shipping, 2).'.']);
			}
			break;

		case 'receive_form': ?>
			<div>
				<style>
					#receive-form { font-size:12px; }
					#receive-form  label, #receive-form input { cursor:pointer; }
					#rma-receiving-details { margin-bottom:25px; text-align:right; border-bottom:1px solid #000; padding-bottom:5px; }
					#rma-receiving-details textarea { display:block; width:100%; margin:10px 0; }
					#rma-receiving-details div { margin:10px; overflow:none; float:left; }
				</style>
				<form id="receive-form">
					<?php foreach ($ck_rma->get_products() as $index => $product) {
						if ($product['received']) continue;
						$op = prepared_query::fetch('SELECT * FROM orders_products WHERE orders_products_id = :orders_products_id', cardinality::ROW, [':orders_products_id' => $product['orders_products_id']]); ?>
					<div>
						<input id="<?= $index; ?>" name="rmaProductIds[]" type="checkbox" value="<?= $product['rma_product_id']; ?>">
						<label for="<?= $index; ?>"><?= $product['quantity']; ?> x <?= $op['products_name']; ?> (<?= $product['reason']; ?>) <?php if ($product['ipn']->is('serialized')) echo ' - '.$product['serial']->get_header('serial_number'); ?></label>
					</div>
					<div id="rma-receiving-details">
						<div>
							<input id="id_quarantine_<?= $index; ?>" class="quarantine-checkbox" name="quarantine[<?= $product['rma_product_id']; ?>]" data-quarantine-index="<?= $index; ?>" type="checkbox">
							<label for="id_quarantine_<?= $index; ?>">Quarantine</label>
						</div>
						<?php if ($product['reason_id'] == 3) { //if product reason is defective failure ?>
						<div>
							<input type="checkbox" id="not_defective" value='1' name="not_defective[<?= $product['rma_product_id']; ?>]">
							<label for="not_defective">Not Defective</label>
						</div>
						<?php } ?>
						<textarea id="hold-notes-<?= $index; ?>" class="text" name="quarantine_note[<?= $product['rma_product_id']; ?>]" placeholder="Hold Notes" disabled></textarea>
					</div>
					<?php } ?>
					<input id="receive-process-button" type="button" value="Receive Selected Items" style="float: right; margin-right: 10px;">
				</form>
				<script>
					jQuery('.quarantine-checkbox').click(function () {
						var index = jQuery(this).attr("data-quarantine-index");
						if (jQuery('.quarantine-checkbox').is(':checked')) jQuery('#hold-notes-'+index).removeAttr('disabled');
						else jQuery('#hold-notes-'+index).attr('disabled', 'disabled');
					});
				</script>
			</div>
			<?php break;

		case 'add_product_form': ?>
			<div>
				<form id="add-product-form">
					<table style="font-size: 11px;">
						<?php //prepare the reason drop down
						$reasons = prepared_query::fetch('SELECT * FROM rma_reason ORDER BY description DESC', cardinality::SET);
						$reason_options = "";
						foreach ($reasons as $reason) {
							$reason_options .= "<option value='".$reason['id']."'>".$reason['description']."</option>";
						}

						//prepare the list of products
						$products = $ck_rma->get_sales_order()->get_products();
						$rmaProducts = $ck_rma->get_products();
						$unserializedProducts = [];
						$serializedProducts = [];
						foreach ($products as $product) {
							if ($product['ipn']->is('serialized')) $serializedProducts[] = $product;
							else {
								$already_on_rma = FALSE;
								foreach ($rmaProducts as $rmaProduct) {
									if ($rmaProduct['orders_products_id'] == $product['orders_products_id']) $already_on_rma = TRUE;
								}

								if (empty($already_on_rma)) $unserializedProducts[] = $product;
							}
						}

						//DISPLAY UNSERIALIZED PRODUCTS
						echo '<tr><td colspan="6"><b>UNSERIALIZED PRODUCTS</b></td></tr><tr><th>Select</th><th>Product</th><th>IPN</th><th>Quantity</th><th>Reason</th><th>Comments</th></tr>';
						foreach ($unserializedProducts as $product) {
							$rma_qty = prepared_query::fetch('SELECT SUM(quantity) FROM rma_product WHERE order_product_id = :orders_products_id', cardinality::SINGLE, [':orders_products_id' => $product['orders_products_id']]);
							$maxQuantity = $product['quantity'] - $rma_qty; ?>
							<tr>
								<td><input type="checkbox" name="cb_u_<?= $product['orders_products_id']; ?>"></td>
								<td><?= $product['model']; ?></td>
								<td><?= $product['ipn']->get_header('ipn'); ?></td>
								<td>
									<select name="u<?= $product['orders_products_id']; ?>_quantity">
										<?php for ($i = 1; $i <= $maxQuantity; $i++) { ?>
										<option value="<?= $i; ?>"><?= $i; ?></option>
										<?php } ?>
									</select>
								</td>
								<td>
									<select name="u<?= $product['orders_products_id']; ?>_reason">
										<?= $reason_options; ?>
									</select>
								</td>
								<td><textarea name="u<?= $product['orders_products_id']; ?>_comment" rows="3" cols="25"></textarea></td>
							</tr>
						<?php }

						//DISPLAY SERIALIZED PRODUCTS
						echo '<tr><td colspan="6"><b>SERIALIZED PRODUCTS</b></td></tr>';
						foreach ($serializedProducts as $product) { ?>
							<tr><td colspan="6"><b>Model: <?= $product['model']; ?> IPN: <?= $product['ipn']->get_header('ipn'); ?></b></td></tr>
							<tr><th>Select</th><th colspan="3">Serial</th><th>Reason</th><th>Comments</th></tr>
							<?php foreach ($product['allocated_serials'] as $serial) {
								if (prepared_query::fetch('SELECT * FROM rma_product WHERE serial_id = :serial_id', cardinality::ROW, [':serial_id' => $serial->id()])) continue; ?>
								<tr>
									<td><input type="checkbox" name="cb_s_<?= $serial->id(); ?>"></td>
									<td colspan="3" align="center"><?= $serial->get_header('serial_number'); ?></td>
									<td><select name="s<?= $serial->id(); ?>_reason">
										<?= $reason_options; ?>
									</select></td>
									<td><textarea name="s<?= $serial->id(); ?>_comment" rows="3" cols="25"></textarea></td>
								</tr>
							<?php }
						} ?>
					</table>
					<input id="add-product-process-button" type="button" value="Add Selected Products" style="float: right; margin-right: 10px;">
				</form>
			</div>
			<?php break;

		case 'add_product':
			foreach ($_POST as $key => $value) {
				$rma_product = [
					'rma_id' => $ck_rma->id(),
				];

				if (strpos($key, 'cb_u_') == 0 && strpos($key, 'cb_u_') !== FALSE && $value == 'on') {
					$order_product_id = substr($key, 5);

					$rma_product['order_product_id'] = $order_product_id;
					$rma_product['quantity'] = $_POST['u'.$order_product_id.'_quantity'];
					$rma_product['reason_id'] = $_POST['u'.$order_product_id.'_reason'];
					$rma_product['comments'] = $_POST['u'.$order_product_id.'_comment'];
				}

				if (strpos($key, 'cb_s_') == 0 && strpos($key, 'cb_s_') !== FALSE && $value == 'on') {
					$serial_id = substr($key, 5);
					$serial = new ck_serial($serial_id);

					$rma_product['order_product_id'] = $serial->get_current_history('orders_products_id');
					$rma_product['serial_id'] = $serial->id();
					$rma_product['quantity'] = 1;
					$rma_product['reason_id'] = $_POST['s'.$serial_id.'_reason'];
					$rma_product['comments'] = $_POST['s'.$serial_id.'_comment'];
				}

				$insert = new prepared_fields($rma_product, prepared_fields::INSERT_QUERY);
				$rma_product_id = prepared_query::insert('INSERT INTO rma_product ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
			}
			break;

		case 'receive':
			$message = '';
			$message .= 'RMA#: <a href="'.FQDN.'/admin/rma-detail.php?id='.$ck_rma->id().'">'.$ck_rma->id().'</a><br><br>';
			$message .= 'Order#: <a href="'.FQDN.'/admin/orders_new.php?oID='.$ck_rma->get_header('orders_id').'&action=edit">'.$ck_rma->get_header('orders_id').'</a><br><br>';
			$message .= 'Customer: '.$ck_rma->get_customer()->get_display_label().'<br><br>';
			$message .= 'For: '.$ck_rma->get_header('disposition').'<br><br><br><br>';
			$message .= 'Products Received:<br><br>';

			try {
				// creating the invoice puts items back in stock
				$received_products = array_map(function($product) {
					$product['hold'] = CK\fn::check_flag(@$_POST['quarantine'][$product['rma_product_id']]);
					$product['hold_notes'] = !empty($_POST['quarantine_note'][$product['rma_product_id']])?$_POST['quarantine_note'][$product['rma_product_id']]:NULL;

					// if the return reason was indicated as defective, and we've received, tested, and indicate it's *not* defective, mark it as such
					if ($product['reason_id'] != 3) $product['not_defective'] = NULL;
					else $product['not_defective'] = CK\fn::check_flag(@$_POST['not_defective'][$product['rma_product_id']])?TRUE:FALSE;

					return $product;
				}, array_filter($ck_rma->get_products(), function($product) { return in_array($product['rma_product_id'], $_POST['rmaProductIds']); }));

				$response = ck_invoice::create_credit_from_rma_receipt($ck_rma, $received_products);

				//$invoice = $response['invoice'];
				//$inventory_holds = $response['inventory_holds'];

				foreach ($received_products as $product) {
					$ipn = new ck_ipn2($product['stock_id']);

					$message .= 'IPN: '.$ipn->get_header('ipn').'        Qty: '.$product['quantity'].'<br>';
				}

				// send email to accounting
                $mailer = service_locator::get_mail_service();
                $mail = $mailer->create_mail();
				$mail->set_from('webmaster@cablesandkits.com');
				$mail->add_to('accounting@cablesandkits.com');
				if ($ck_rma->get_sales_order()->get_header('payment_method_code') == 'amazon') {
					$mail->add_to('amazon@cablesandkits.com');
				}
				$mail->set_subject("RMA #".$ck_rma->id()." has been received");
				$mail->set_body($message);
				$mailer->send($mail);
			}
			catch (Exception $e) {
				throw $e;
			}

			break;

		case 'view_label':
			/*UPS*/
			$filename = file_writer::get_path('/admin/images/return-labels/'.$ck_rma->get_header('tracking_number').'.gif', TRUE);
			echo '<img src="'.$filename.'" style="width:5.1in;">';
			exit();

			break;
			/*END UPS*/

			/*FEDEX* /
			$filename = DIR_WS_FEDEX_LABELS.'rma/'.$ck_rma->id().'.png';
			header('Content-Type: image/png');
			echo file_get_contents($filename);

			break;
			/ *END FEDEX*/

		case 'generate_label':
			/*UPS*/
			$response = ['success' => 0];

			try {
				$to_address = api_ups::$local_origin;
				$to_address['first_name'] = 'Attn:';
				$to_address['last_name'] = 'RMA # '.$ck_rma->id();

				$address_type = new ck_address_type();
				$address_type->load('header', $to_address);
				$to = new ck_address2(NULL, $address_type);

				$from = $ck_rma->get_sales_order()->get_ship_address();

				$refnum = ['code' => \Ups\Entity\ReferenceNumber::CODE_RETURN_AUTHORIZATION_NUMBER, 'number' => $ck_rma->id()];

				$packages = [['weight' => $ck_rma->get_estimated_shipped_weight(), 'reference_number' => $refnum]];

				$labels = api_ups::get_label(['service' => \Ups\Entity\Service::S_GROUND], $packages, $to, $from);

				foreach ($labels['packages'] as $package) {
					$file = __DIR__.'/images/return-labels/'.$package['tracking_number'].'.'.strtolower($package['image_format']);
					file_writer::write(base64_decode($package['image']), $file, TRUE);
					$image = new Imagick(file_writer::get_path($file, TRUE));
					$image->rotateImage(new ImagickPixel('#ffffffff'), 90);
					$image->writeImage(file_writer::get_path($file, TRUE));
					$image->clear();
					$ck_rma->set_tracking_number($package['tracking_number']);
				}

				$response['success'] = 1;
			}
			catch (Exception $e) {
				$response['message'] = $e->getMessage();
			}

			echo json_encode($response);

			exit();
			break;
			/*END UPS*/

			/*FEDEX* /
			$response = ['success' => 0];

			try {
				$package_weight = max(floor($ck_rma->get_estimated_shipped_weight()), 1);

				$address_data = [
					'first_name' => 'Attn:',
					'last_name' => 'RMA # '.$ck_rma->id(),
					'company_name' => 'CablesAndKits.com',
					'address1' => '4555 Atwater Ct.',
					'address2' => 'Suite A.',
					'city' => 'Buford',
					'postcode' => '30518',
					'state' => 'GA',
					'country' => 'US',
					'telephone' => '8886220223',
					'country_address_format_id' => '1'
				];

				$address_type = new ck_address_type();
				$address_type->load('header', $address_data);
				$dest_address = new ck_address2(NULL, $address_type);

				// set the "from" address from the original order
				$origin_address = $ck_rma->get_order()->get_ship_address();

				//Now we build the payment info
				$payment_info = [];
				$payment_info['country'] = 'US';
				$payment_info['account_number'] = '285019516';
				$payment_info['type'] = 'RECIPIENT';

				$packageData = [];
				$packageData['weight'] = $package_weight;

				//MMD - what's the correct thing to do here?
				$packageData['dimensions'] = [];
				$packageData['dimensions']['height'] = 3;
				$packageData['dimensions']['width'] = 7;
				$packageData['dimensions']['length'] = 10;

				require_once(DIR_FS_CATALOG.DIR_WS_FUNCTIONS.'fedex_webservices.php');
				$trackingNumData = fws_generate_shipping_labels('FEDEX_GROUND', $origin_address, $dest_address, $packageData, $payment_info, NULL, 1, 1, FALSE, 'NO_SIGNATURE_REQUIRED', 'rma/');
				$trackingNum = $trackingNumData['tracking_number'];
				//MMD - rename the label on the file system to match how this worked before
				rename(DIR_WS_FEDEX_LABELS.'rma/'.$trackingNum.'.png', DIR_WS_FEDEX_LABELS.'rma/'.(string)$ck_rma->id().'.png');

				$ck_rma->set_tracking_number($trackingNum);

				$response['success'] = 1;
			}
			catch (Exception $e) {
				$response['message'] = $e->getMessage();
			}

			echo json_encode($response);

			exit();
			break;
			/ *END FEDEX*/

		case 'add_note':
			$noteText = $_POST['notes'];
			$rma_note_id = $ck_rma->create_note(['note_text' => $noteText]);
			$note = $ck_rma->get_notes($rma_note_id);

			// response data for display
			$data = [
				'note'		=> $noteText,
				'created_on' => $note['created_on']->format('Y-m-d'),
				'created_by' => $note['admin']->get_name(),
			];

			echo json_encode($data);
			break;

		case 'force_closed':
			foreach ($ck_rma->get_products() as $product) {
				if (!$product['received']) prepared_query::execute('DELETE FROM rma_product WHERE id = :rma_product_id', [':rma_product_id' => $product['rma_product_id']]);
			}

			$ck_rma->close();
			break;

		case 'cancel':
			$ck_rma->close();
			break;

		case 'update_rma':
			try {
				$update = [];

				if (isset($_POST['followup_date'])) {
					if ($_POST['followup_date'] == 'NULL') $update['follow_up_date'] = NULL;
					else {
						$fud = ck_datetime::datify($_POST['followup_date']);
						$update['follow_up_date'] = $fud->format('Y-m-d H:i:s');
					}
				}

				if (isset($_POST['restocking_fee'])) {
					if (!is_numeric($_POST['restocking_fee'])) throw new Exception($_POST['restocking_fee']. 'is not a valid value for a restock fee percentage.');
					$update['restock_fee_percent'] = $_POST['restocking_fee'];
				}

				if (isset($_POST['disposition'])) $update['disposition'] = $_POST['disposition'];
				if (isset($_POST['refund_shipping'])) $update['refund_shipping'] = $_POST['refund_shipping'];
				if (isset($_POST['refund_coupon'])) $update['refund_coupon'] = $_POST['refund_coupon'];
				if (isset($_POST['shipping_amount'])) $update['shipping_amount'] = $_POST['shipping_amount'];
				if (isset($_POST['coupon_amount'])) $update['coupon_amount'] = $_POST['coupon_amount'];

				$update = new prepared_fields($update, prepared_fields::UPDATE_QUERY);
				$id = new prepared_fields(['id' => $ck_rma->id()]);

				prepared_query::execute('UPDATE rma SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
				$cm_rma->rebuild();

				echo json_encode(['restock_fee' => $ck_rma->get_restock_fee()]);
			}
			catch (Exception $e) {
				echo json_encode(['error' => $e->getMessage()]);
			}
			break;

		case 'get_reasons':
			$reasons = prepared_query::fetch('SELECT id, description FROM rma_reason ORDER BY description DESC', cardinality::SET);;
			echo json_encode($reasons);
			break;

		case 'get_serials_for_product':
			$serials = prepared_query::fetch('SELECT s.id, s.serial FROM rma_product rp JOIN serials_history sh ON rp.order_product_id = sh.order_product_id JOIN serials s ON sh.serial_id = s.id WHERE rp.id = :rma_product_id ORDER BY sh.entered_date DESC', cardinality::SET, [':rma_product_id' => $_GET['rma_product_id']]);
			echo json_encode($serials);
			break;

		case 'update_rma_product':
			try {
				$p = ['comments' => $_POST['comments'], 'reason_id' => $_POST['reason']];

				if (isset($_POST['serial'])) {
					$p['serial_id'] = $_POST['serial'];
					$p['quantity'] = 1;
				}
				elseif (isset($_POST['quantity'])) $p['quantity'] = $_POST['quantity'];

				$p = new prepared_fields($p, prepared_fields::UPDATE_QUERY);
				$id = new prepared_fields(['id' => $_POST['rma_product_id']]);

				prepared_query::execute('UPDATE rma_product SET '.$p->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($p, $id));

				echo json_encode(array());
			} catch (Exception $e) {
				echo json_encode(array('error' => $e->getMessage()));
			}
			break;

		case 'delete_rma_product':
			try {
				prepared_query::execute('DELETE FROM rma_product WHERE id = :rma_product_id', [':rma_product_id' => $_POST['rma_product_id']]);
				echo json_encode(array());
			} catch (Exception $e) {
				echo json_encode(array('error' => $e->getMessage()));
			}
			break;
	}
	exit;
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body>
	<div id="modal" class="jqmWindow" style="width: 800px; height: 450px; overflow: scroll;">
		<a class="jqmClose" href="#" style="float: right; clear: both;">X</a>
		<div id="modal-content"></div>
	</div>
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<style type="text/css">
		.edit, .save { margin-left: 5px; }
		.edit img, .save img { border: none; vertical-align: middle; }
		#follow-up-date { width: 100px; }
		#restocking-fee { width: 50px; }
	</style>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#receive-button').click(function(event) {
				var rmaId = $('#rma-id-hidden').val();
				$('#modal').jqm({ajax: 'rma-detail.php?id=' + rmaId + '&action=receive_form', target: '#modal-content', modal: true}).jqmShow();
			});

			$('#add-product-button').click(function(event) {
				var rmaId = $('#rma-id-hidden').val();
				$('#modal').jqm({ajax: 'rma-detail.php?id=' + rmaId + '&action=add_product_form', target: '#modal-content', modal: true}).jqmShow();
			});

			$('#receive-process-button').live('click', function(event) {
				if ($('#modal input:checkbox:checked').length == 0) {
					alert('Please select at least one product to receive.');
					return;
				}

				var rmaId = $('#rma-id-hidden').val();
				var data = $('#receive-form').serialize();
				data += '&id=' + rmaId;
				data += '&action=receive';
				$.post('rma-detail.php', data, function (data) {
					if (data) {
						alert('There was a problem creating this receipt - contact dev, there may be unexpected errors related to this issue');
						return;
					}
					$('#modal').jqm().jqmHide();
					window.location.reload();
				}, 'text');
			});

			$('#add-product-process-button').live('click', function(event) {
				if ($('#modal input:checkbox:checked').length == 0) {
					alert('Please select at least one product to add to the RMA.');
					return;
				}

				var rmaId = $('#rma-id-hidden').val();
				var data = $('#add-product-form').serialize();
				data += '&id=' + rmaId;
				data += '&action=add_product';
				$.post('rma-detail.php', data, function (data) {
					$('#modal').jqm().jqmHide();
					window.location.reload();
				}, 'text');
			});

			$('#generate-label-button').live('click', function(event) {
				var rmaId = $('#rma-id-hidden').val();

				if ($('#tracking').length != 1 || confirm('Generating a new label will overwrite the old tracking number on the RMA. Are you sure you want to proceed?')) {
					$.ajax({
						url: 'rma-detail.php',
						type: 'post',
						dataType: 'json',
						data: { id: rmaId, action: 'generate_label' },
						success: function (data) {
							if (data.success == 1) {
								window.open('/admin/rma-detail.php?id=' + rmaId + '&action=view_label');
								window.location.reload();
							}
							else alert(data.message);
						}
					});
				}
			});

			$('#cancel-button').click(function(event) {
				var answer = confirm('Canceling the RMA will prevent further editing. This action cannot be undone. Are you sure you want to proceed?');
				if (answer === true) {
					var rmaId = $('#rma-id-hidden').val();
					$.post('rma-detail.php', {action: 'cancel', id: rmaId}, function(data) {
						window.location.reload();
					}, 'text');
				}
			});

			$('#force-closed-button').click(function(event) {
				var answer = confirm('Closing the RMA will delete unreceived products and prevent further editing. This action cannot be undone. Are you sure you want to proceed?');
				if (answer === true) {
					var rmaId = $('#rma-id-hidden').val();
					$.post('rma-detail.php', {action: 'force_closed', id: rmaId}, function(data) {
						window.location.reload();
					}, 'text');
				}
			});

			$('#add-note-button').click(function(event) {
				var rmaId = $('#rma-id-hidden').val();
				var data = $('#notes-form').serialize();
				data += '&action=add_note';
				data += '&id=' + rmaId;

				$.post('rma-detail.php', data, function(data) {
					$('#notes').val('');
					$('#notes-display').append('<tr><td>' + data.created_on + '</td><td>' + data.created_by + '</td><td>' + data.note + '</td></tr>');
				}, 'json');
			});

			$('#print-button').click(function(event) {
				window.print();
			});

			$('#edit_follow-up-date').click(function(event) {
				event.preventDefault();
				var buttonContainer = $(this).parent();
				buttonContainer.hide();
				var val = $('#follow-up-date-container').html();
				$('#follow-up-date-container').html('<input id="follow-up-date" type="text" value="">');
				$('#follow-up-date').val(val);
				$('#follow-up-date').datepicker({
					dateFormat: 'M dd, yy',
					minDate: +0,
					onSelect:	function(dateText, instance) {
						$.post('rma-detail.php', {
							action: 'update_rma',
							id: $('#rma-id-hidden').val(),
							followup_date: dateText
						}, function(data) {
							if (data.error) {
								alert(data.error);
								return;
							}

							$('#follow-up-date-container').html(dateText);
							buttonContainer.show();
						}, "json");
					}
				});
			});

			$('#remove_follow-up-date').click(function(event) {
				event.preventDefault();
				if (confirm("Are you sure you want to remove the follow up date?")) {
					$.post('rma-detail.php', {
						action: 'update_rma',
						id: $('#rma-id-hidden').val(),
						followup_date: "NULL"
						}, function(data) {
							if (data.error) {
								alert(data.error);
								return;
							}

							$('#follow-up-date-container').html("");
						}, "json");
				}
			});

			$('#edit_disposition').click(function(event) {
				event.preventDefault();
				var buttonContainer = $(this).parent();
				buttonContainer.hide();

				var currentVal = $.trim($('#disposition-container').html());
				var choices = ['advance replacement', 'replacement', 'refund', 'credit'];
				var html = '<select id="disposition">';
				for (i = 0; i < choices.length; i++) {
					if (currentVal === choices[i]) {
						html += '<option value="' + choices[i] + '" selected="selected">' + choices[i] + '</option>';
					} else {
						html += '<option value="' + choices[i] + '">' + choices[i] + '</option>';
					}
				}
				html += '</select>';
				$('#disposition-container').html(html);
				$('#disposition').change(function(event) {
					var newVal = $(this).val();
					$.post('rma-detail.php', {
						action: 'update_rma',
						id: $('#rma-id-hidden').val(),
						disposition: newVal
					}, function(data) {
						if (data.error) {
							alert(data.error);
							return;
						}

						$('#disposition-container').html(newVal);
						buttonContainer.show();
					});
				}, "json");
			});

			$('#edit_restocking-fee').click(function(event) {
				event.preventDefault();
				var buttonContainer = $(this).parent();
				buttonContainer.hide();

				var currentVal = $.trim($('#restocking-fee-container span').html());

				if (currentVal == 'None') {
					currentVal = '';
				}

				$('#restocking-fee-container').html('<input id="restocking-fee" type="text">');
				$('#restocking-fee').val(currentVal);
				$('#restocking-fee').focus();
				$('#restocking-fee').blur(function(event) {
					var newVal = $(this).val();
					$.post('rma-detail.php', {
						action: 'update_rma',
						id: $('#rma-id-hidden').val(),
						restocking_fee: newVal
					}, function(data) {
						if (data.error) {
							alert(data.error);
							return;
						}

						var fee = new Number(data.restock_fee);

						$('#restocking-fee-container').html('<span>' + newVal + '</span>%&nbsp;=&nbsp;$' + fee.toFixed(2));
						buttonContainer.show();
					}, "json");
				});
			});

			//refund shipping - start
			$('#refund_shipping').change(function(event) {
				var newVal = 0;
				if (this.checked) {
					$('#shipping-amount-container').show();
					$('#shipping-amount-button-container').show();
					newVal = 1;
				}
				else {
					$('#shipping-amount-container').hide();
					$('#shipping-amount-button-container').hide();
				}
						$.post('rma-detail.php', {
							action: 'update_rma',
							id: $('#rma-id-hidden').val(),
							refund_shipping: newVal
						}, function(data) {
							if (data.error) {
								alert(data.error);
								return;
							}
				});
			});
			$('#shipping-amount-edit-button').click(function(event) {
				event.preventDefault();
				this.hide();
				$('#shipping-amount-display').hide();
				$('#shipping-amount-save-button').show();
				$('#shipping-amount').show();
			});
			$('#shipping-amount-save-button').click(function(event) {
				event.preventDefault();
				var newVal = $('#shipping-amount').val();
				//MMD - leaving this if statement here because we are on a version of jQuery that
				//is way too old - I wanted to use 'isNumeric' here to check before posting to the server
				if ('number' != 'number') {
					alert('You must enter a valid number in order to change the shipping refund amount.');
				}
				else {
							$.post('rma-detail.php', {
										action: 'update_rma',
										id: $('#rma-id-hidden').val(),
										shipping_amount: newVal
								}, function(data) {
										if (data.error) {
										alert(data.error);
										return;
									}
					});
					$('#shipping-amount-display').html('$' + newVal);
					this.hide();
					$('#shipping-amount').hide();
					$('#shipping-amount-edit-button').show();
					$('#shipping-amount-display').show();
				}
			});
			//refund shipping - end
			//refund coupon - start
			$('#refund_coupon').change(function(event) {
				var newVal = 0;
				if (this.checked) {
					$('#coupon-amount-container').show();
					$('#coupon-amount-button-container').show();
					newVal = 1;
				}
				else {
					$('#coupon-amount-container').hide();
					$('#coupon-amount-button-container').hide();
				}
						$.post('rma-detail.php', {
							action: 'update_rma',
							id: $('#rma-id-hidden').val(),
							refund_coupon: newVal
						}, function(data) {
							if (data.error) {
								alert(data.error);
								return;
							}
				});
			});
			$('#coupon-amount-edit-button').click(function(event) {
				event.preventDefault();
				this.hide();
				$('#coupon-amount-display').hide();
				$('#coupon-amount-save-button').show();
				$('#coupon-amount').show();
			});
			$('#coupon-amount-save-button').click(function(event) {
				event.preventDefault();
				var newVal = $('#coupon-amount').val();
				//MMD - leaving this if statement here because we are on a version of jQuery that
				//is way too old - I wanted to use 'isNumeric' here to check before posting to the server
				if ('number' != 'number') {
					alert('You must enter a valid number in order to change the coupon refund amount.');
				}
				else {
							$.post('rma-detail.php', {
										action: 'update_rma',
										id: $('#rma-id-hidden').val(),
										coupon_amount: newVal
								}, function(data) {
										if (data.error) {
										alert(data.error);
										return;
									}
					});
					$('#coupon-amount-display').html('$' + newVal);
					this.hide();
					$('#coupon-amount').hide();
					$('#coupon-amount-edit-button').show();
					$('#coupon-amount-display').show();
				}
			});
			//refund coupon - end

			var editHandler = function(event) {
				event.preventDefault();
				$(this).unbind('click');
				var row = $(this).parent().parent();
				var rmaProductId = row.attr('id');
				var serialCell = row.children('.serial');
				var serialValue = serialCell.html();

				if (serialValue != 'N/A') {
					$.get('rma-detail.php', {
						id: $('#rma-id-hidden').val(),
						action: 'get_serials_for_product',
						rma_product_id: rmaProductId
					}, function (data) {
						// change to dropdown if there are other options
						if (data.length > 1) {
							var html = '<select id="serial">';
							for (i = 0; i < data.length; i++) {
								if (serialValue == data[i].serial) {
									html += '<option value="' + data[i].id + '" selected="selected">' + data[i].serial + '</option>';
								} else {
									html += '<option value="' + data[i].id + '">' + data[i].serial + '</option>';
								}
							}
							html += '</select>';
							serialCell.html(html);
						}
					}, "json");
				}

				var reasonCell = row.children('.reason');
				var reasonValue = $.trim(reasonCell.html());

				$.get('rma-detail.php', {
					id: $('#rma-id-hidden').val(),
					action: 'get_reasons'
				}, function (data) {
					// change to dropdown
					var html = '<select id="reason">';
					for (i = 0; i < data.length; i++) {
						if (reasonValue == data[i].description) {
							html += '<option value="' + data[i].id + '" selected="selected">' + data[i].description + '</option>';
						} else {
							html += '<option value="' + data[i].id + '">' + data[i].description + '</option>';
						}
					}
					html += '</select>';
					reasonCell.html(html);
				}, "json");

				var commentsCell = row.children('.comments');
				var commentsValue = commentsCell.html();

				// change to textarea
				commentsCell.html('<textarea id="comments">' + commentsValue + '</textarea>');

				if (serialValue == 'N/A') {
					var quantityCell = row.children('.quantity');
					var quantityValue = $.trim(quantityCell.html());
					var maxQuantity = quantityCell.attr('maxValue');
					var html = '<select id="quantity">';
					for (i = 1; i <= maxQuantity; i++) {
						html += '<option value="' + i + '" ';
						if (quantityValue == i) {
							html += 'selected="selected" ';
						}
						html += '>' + i + '</option>';
					}
					html += '</select>';
					quantityCell.html(html);
				}

				row.find('.save').bind('click', saveHandler);
			};

			$('.edit-product').click(editHandler);

			var saveHandler = function(event) {
				event.preventDefault();
				var row = $(this).parent().parent();
				var rmaProductId = row.attr('id');

				data = {
					id: $('#rma-id-hidden').val(),
					action: 'update_rma_product',
					rma_product_id: rmaProductId,
					reason: row.find('#reason').val(),
					comments: row.find('#comments').val()
				};

				if (row.find('#serial').length != 0) {
					data.serial = row.find('#serial').val();
				}
				else if (row.find('#quantity').val() > 0) {
					data.quantity = row.find('#quantity').val();
				}

				$.post('rma-detail.php', data, function(data) {
					var serialCell = row.children('.serial');
					var reasonCell = row.children('.reason');
					var commentsCell = row.children('.comments');
					var quantityCell = row.children('.quantity');

					if (row.find('#serial').length != 0) {
						serialCell.html(row.find('#serial').children(':selected').text());
					}

					reasonCell.html(row.find('#reason').children(':selected').text());
					quantityCell.html(row.find('#quantity').children(':selected').text());
					commentsCell.html(row.find('#comments').val())
				}, "json");

				$(this).unbind('click');
				row.find('.edit').bind('click', editHandler);
			};

			var deleteHandler = function (event) {
				if (confirm("Are you sure you want to delete this product from the RMA?")) {
					event.preventDefault();
					var row = $(this).parent().parent();
					var rmaProductId = row.attr('id');

					data = {
						id: $('#rma-id-hidden').val(),
						action: 'delete_rma_product',
						rma_product_id: rmaProductId
					}

					$.post('rma-detail.php', data, function(data) {
						row.remove();
					}, "json");
				}
			};

			$('.delete-product').click(deleteHandler);

			<?php if (!in_array($ck_rma->get_status(), [ck_rma2::STATUS_OPEN])) { ?>
			$('.save').hide();
			$('.edit').hide();
			$('.delete').hide();
			<?php } ?>
		});
	</script>
	<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
	<div id="main">
		<h2>RMA #<?= $ck_rma->id(); ?></h2>
		<table cellpadding="2" cellspacing="2" border="0">
			<tr>
				<?php $followup_date_style = '';
				if ($ck_rma->has('follow_up_date') && $ck_rma->get_header('follow_up_date') > ck_datetime::NOW()) $followup_date_style = "font-weight: bold;";
				else $followup_date_style = "font-weight: bold; color: red;"; ?>
				<td>Status:</td>
				<td>
					<?php echo $ck_rma->get_status();
					if ($ck_rma->get_status() == ck_rma2::STATUS_CLOSED && in_array($_SESSION['perms']['admin_groups_id'], [1, 5, 8, 10, 11, 13, 16, 20, 21, 22, 25, 28, 31])) { ?>
					<form action="/admin/rma-detail.php" method="post" style="display:inline;">
						<input type="hidden" name="id" value="<?= $id; ?>">
						<input type="hidden" name="action" value="re-open">
						<input type="submit" value="RE-OPEN">
					</form>
					<?php } ?>
				</td>
				<td>Follow Up Date:</td>
				<td id="follow-up-date-container" style="<?= $followup_date_style; ?>"><?= $ck_rma->has('follow_up_date')?$ck_rma->get_header('follow_up_date')->format('M d, Y'):'None'; ?></td>
				<td>
					<a id="edit_follow-up-date" class="edit" href="#"><img src="images/table_edit.png" alt="Edit"></a>
					<a id="remove_follow-up-date" class="edit" href="#"><img src="images/delete.png" alt="Remove"></a>
				</td>
			</tr>
			<tr>
				<td>Order:</td>
				<td><a href="orders_new.php?oID=<?= $ck_rma->get_sales_order()->id(); ?>&action=edit"><?= $ck_rma->get_sales_order()->id(); ?></a></td>
				<td>Created On:</td>
				<td><?= $ck_rma->get_header('created_on')->format('M d, Y h:i:s A'); ?></td>
				<td></td>
			</tr>
			<tr>
				<td>Customer:</td>
				<td><?= $ck_rma->get_customer()->get_display_label(); ?></td>
				<td>Created By:</td>
				<td><?= $ck_rma->get_admin()->get_name(); ?></td>
				<td></td>
			</tr>
			<tr>
				<td>Customer Email:</td>
				<td>
					<?php $order = $ck_rma->get_sales_order(); ?>
					<a href="mailto:<?= $order->get_prime_contact('email'); ?>"><?= $order->get_prime_contact('email'); ?></a>
				</td>
				<td colspan="3"></td>
			</tr>
			<tr>
				<td>Customer PO:</td>
				<td><?= $order->get_terms_po_number(); ?></td>
				<td colspan="3"></td>
			</tr>
			<tr>
				<td>Payment Method:</td>
				<td>
					<?= $order->get_header('payment_method_label'); ?>
					<?php if ($order->get_header('payment_method_code') == 'amazon') { ?>
					- <span style="color: #cc0000; font-weight: bold;">Refund must be processed on the Marketplace within 1 business day.</span>
					<?php } ?>
				</td>
				<td colspan="3"></td>
			</tr>
			<tr>
				<td colspan="5"></td>
			</tr>
			<tr>
				<td><input type="checkbox" class='edit' id="refund_shipping" <?= $ck_rma->is('refund_shipping')?'checked':''; ?>>&nbsp;Refund Shipping:</td>
				<td id="shipping-amount-container" <?php if (!$ck_rma->is('refund_shipping')) { ?> style="display: none;" <?php } ?>>
					<div id="shipping-amount-display"><?= CK\text::monetize($ck_rma->get_header('shipping_amount')); ?></div>
					<input type="text" id="shipping-amount" value="<?= $ck_rma->get_header('shipping_amount'); ?>" style="display: none;">
				</td>
				<td id="shipping-amount-button-container" <?php if ($ck_rma->get_header('refund_shipping') == 0) { ?> style="display: none;" <?php } ?>>
					<a href="#" class="edit" id="shipping-amount-edit-button"><img src="images/table_edit.png" alt="Edit Shipping Amount"></a>
					<a href="#" class="edit" id="shipping-amount-save-button" style="display: none;"><img src="images/save.png" alt="Save Shipping Amount"></a>
				</td>
				<td colspan="2"></td>
			</tr>
			<tr>
				<td><input type="checkbox" class='edit' id="refund_coupon" <?= $ck_rma->is('refund_coupon')?'checked':''; ?>>&nbsp;Refund Coupon:</td>
				<td id="coupon-amount-container" <?php if (!$ck_rma->is('refund_coupon')) { ?> style="display: none;" <?php } ?>>
					<div id="coupon-amount-display"><?= CK\text::monetize($ck_rma->get_header('coupon_amount')); ?></div>
					<input type="text" id="coupon-amount" value="<?= $ck_rma->get_header('coupon_amount'); ?>" style="display: none;">
				</td>
				<td id="coupon-amount-button-container" <?php if (!$ck_rma->is('refund_coupon')) { ?> style="display: none;" <?php } ?>>
					<a href="#" class="edit" id="coupon-amount-edit-button"><img src="images/table_edit.png" alt="Edit Coupon Amount"></a>
					<a href="#" class="edit" id="coupon-amount-save-button" style="display: none;"><img src="images/save.png" alt="Save Coupon Amount"></a>
				</td>
			</tr>
			<?php $ship_total = $order->get_simple_totals('shipping');
			$rma_ship_total = prepared_query::fetch("SELECT SUM(ABS(ait.invoice_total_price)) FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_shipping' WHERE ai.rma_id = ?", cardinality::SINGLE, array($id));
			$remaining_shipping = $ship_total - $rma_ship_total;

			if (!in_array($ck_rma->get_status(), [ck_rma2::STATUS_OPEN]) && $remaining_shipping > 0) { ?>
			<tr>
				<td colspan="5"></td>
			</tr>
			<tr>
				<td colspan="5">
					<form id="create-rma-shipping-invoice" action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
						<input type="hidden" name="id" value="<?= $id; ?>">
						<input type="hidden" name="action" value="create_shipping_invoice">
						$<?= number_format($remaining_shipping, 2); ?> Shipping Uninvoiced<br>
						<input type="text" style="width:60px;" name="refund_shipping" value="<?= number_format($remaining_shipping, 2); ?>">
						<input type="submit" value="Create Refund Invoice For Shipping">
					</form>
					<script>
						jQuery('#create-rma-shipping-invoice').submit(function (event) {
							event.preventDefault();
							jQuery.ajax({
								url: jQuery(this).attr('action'),
								type: jQuery(this).attr('method'),
								dataType: 'json',
								data: jQuery(this).serialize(),
								timeout: 10000,
								success: function(data) {
									if (data.error) { alert(data.error); return; }
									window.location.reload();
								},
								error: function() { alert('There was a problem processing this invoice'); }
							});
							return false;
						});
					</script>
				</td>
			</tr>
			<?php } ?>

			<tr>
				<td colspan="5"></td>
			</tr>
			<tr>
				<td>For:</td>
				<td id="disposition-container" style="text-transform: capitalize;"><?= $ck_rma->get_header('disposition'); ?></td>
				<td><a id="edit_disposition" class="edit" href="#"><img src="images/table_edit.png" alt="Edit"></a></td>
				<td colspan="2"></td>
			</tr>

			<?php // we don't actually store replacement orders anywhere
			if (in_array($ck_rma->get_header('disposition'), ['replacement', 'advance replacement']) && FALSE) { ?>
			<tr>
				<td>Replacement Order:</td>
				<td><a href="orders_new.php?oID=<?= $ck_rma->get_replacement_order()->id(); ?>&action=edit"><?= $ck_rma->get_replacement_order()->id(); ?></a></td>
				<td colspan="3"></td>
			</tr>
			<?php } ?>

			<tr>
				<td>Restocking Fee:</td>
				<td id="restocking-fee-container"><?= $ck_rma->has_restock_percentage()?'<span>'.$ck_rma->get_header('restock_fee_percent').'</span>%&nbsp;=&nbsp;'.CK\text::monetize($ck_rma->get_restock_fee()):'None'; ?></td>
				<td><a id="edit_restocking-fee" class="edit" href="#"><img src="images/table_edit.png" alt="Edit"></a></td>
				<td colspan="2"></td>
			</tr>

			<?php if (!empty($ck_rma->get_header('tracking_number'))) { ?>
			<tr>
				<td id="tracking">Tracking #:</td>
				<td>
					<!-- UPS -->
					<a target="_new" href="https://wwwapps.ups.com/tracking/tracking.cgi?tracknum=<?= $ck_rma->get_header('tracking_number'); ?>"><?= $ck_rma->get_header('tracking_number'); ?></a>
					<!-- END UPS -->
					<!-- FEDEX -- >
					<a target="_new" href="http://www.fedex.com/Tracking/Detail?mpCurrentPage=&cntry_code=us&tracknumber_list=<?= $ck_rma->get_header('tracking_number'); ?>&language=english&ascend_header=1&notifyResults=true&clienttype=dotcom&trackNum=<?= $ck_rma->get_header('tracking_number'); ?>"><?= $ck_rma->get_header('tracking_number'); ?></a>
					<!-- END FEDEX -->
					<a target="_new" href="/admin/rma-detail.php?id=<?= $ck_rma->id(); ?>&action=view_label">[view label]</a>
				</td>
				<td colspan="3"></td>
			</tr>
			<?php } ?>
		</table>
		<br>

		<h3>Products</h3>
		<table cellpadding="5" cellspacing="5" style="background-color: #ececec; width: 1000px; border: 2px solid #ababab;">
			<tr>
				<th>Quantity</th>
				<th>Product</th>
				<th>IPN</th>
				<th>Serial</th>
				<th>Qty On Hold</th>
				<th>Reason</th>
				<th>Comments</th>
				<th>Received?</th>
				<th>Received On</th>
				<th>Received By</th>
				<th>Actions</th>
				<th>Order Value</th>
			</tr>
			<?php foreach ($ck_rma->get_products() as $product) {
				$rma_qty = prepared_query::fetch('SELECT SUM(quantity) FROM rma_product WHERE order_product_id = :orders_products_id AND id != :rma_product_id', cardinality::SINGLE, [':orders_products_id' => $product['orders_products_id'], ':rma_product_id' => $product['rma_product_id']]);
				$max_can_receive = max($product['original_order_qty'] - $rma_qty, 0); ?>
			<tr id="<?= $product['rma_product_id']; ?>">
				<td class="quantity" maxValue="<?= $max_can_receive; ?>"><?= $product['quantity']; ?></td>
				<td nowrap><?= $product['listing']->get_header('products_model'); ?></td>
				<td nowrap><?= $product['ipn']->get_header('ipn'); ?></td>
				<td class="serial" nowrap><?= !empty($product['serial'])?$product['serial']->get_header('serial_number'):'N/A'; ?></td>
				<td><?= $product['ipn']->get_inventory('on_hold'); ?></td>
				<td class="reason"><?= $product['reason']; ?></td>
				<td class="comments"><?= $product['comments']; ?></td>
				<td><?= $product['received']?'Yes':'No'; ?></td>
				<td><?= $product['received']?$product['received_date']->format('M j, Y g:i:s A'):''; ?></td>
				<td>
					<?php if ($product['received']) {
						if (empty($product['admin'])) echo 'N/A';
						else echo $product['admin']->get_header('first_name').' '.$product['admin']->get_header('last_name');
					} ?>
				</td>
				<td nowrap>
					<a class="edit edit-product" href="#"><img src="images/table_edit.png" alt="Edit"></a>
					<a class="save" href="#"><img src="images/save.png" alt="Save"></a>
					<a class="delete delete-product" href="#"><img src="images/delete.png" alt="Delete" border="0"></a>
				</td>
				<td><?= CK\text::monetize($product['revenue']); ?></td>
			</tr>
			<?php } ?>
		</table>
		<br>

		<h3>Notes</h3>
		<table style="background-color: #ececec; width: 1000px; border: 2px solid #ababab;">
			<thead>
				<tr>
					<th>Created On</th>
					<th>Created By</th>
					<th>Note</th>
				</tr>
			</thead>
			<tbody id="notes-display">
				<?php foreach ($ck_rma->get_notes() as $note) { ?>
				<tr>
					<td><?= $note['created_on']->format('M d, Y h:i:s A'); ?></td>
					<td><?= $note['admin']->get_name(); ?></td>
					<td><?= $note['note']; ?></td>
				</tr>
				<?php } ?>
			</tbody>
			<tbody class="noPrint">
				<tr>
					<td></td>
					<td></td>
					<td>
						<form id="notes-form">
							<textarea id="notes" style="height: 75px; width: 300px;" name="notes"></textarea>
							<input id="add-note-button" style="vertical-align: top;" type="button" value="Add">
						</form>
					</td>
				</tr>
			</tbody>
		</table>
		<br><br>

		<div id="buttons" class="noPrint">
			<?php $status = $ck_rma->get_status();
			if (!in_array($status, [ck_rma2::STATUS_CLOSED, ck_rma2::STATUS_RECEIVED])) { ?>
			<input id="receive-button" type="button" value="Receive Items">
			<input id="generate-label-button" type="button" value="Create New Shipping Label">
			<input id="add-product-button" type="button" value="Add Product">
			<?php } ?>

			<input id="print-button" type="button" value="Print RMA">

			<?php if ($status == ck_rma2::STATUS_OPEN) { ?>
			<input id="cancel-button" type="button" value="Cancel RMA">
			<?php }
			elseif ($status == ck_rma2::STATUS_PARTIALLY_RECEIVED) { ?>
			<input id="force-closed-button" type="button" value="Force RMA Closed">
			<?php } ?>
		</div>

		<input id="rma-id-hidden" type="hidden" value="<?= $ck_rma->id(); ?>">
	</div>
	<div style="clear: both;"></div>
</body>
