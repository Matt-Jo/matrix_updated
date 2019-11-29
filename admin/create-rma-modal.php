<?php
require_once('includes/application_top.php');

$orderId = $_REQUEST['orderId'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// process submission
	$orderProductIds = $_POST['order_product_ids'];
	$quantities = $_POST['quantities'];
	$reasonIds = $_POST['reasons'];
	$serialIds = !empty($_POST['serials'])?$_POST['serials']:NULL;
	$comments = $_POST['comments'];

	$disposition = $_POST['disposition'];
	$notes = $_POST['notes'];

	$restockFee = $__FLAG['restock_fee'];
	$restockPercentage = $_POST['restock_percentage'];

	$followUpDate = $_POST['follow_up_date'];

	// start transaction
	$savepoint_id = prepared_query::transaction_begin();

	try {
		$rma = [
			'order_id' => $orderId,
			'disposition' => $disposition,
			'created_on' => prepared_expression::NOW(),
			'created_by' => ck_admin::login_instance()->id(),
		];

		if (!empty($followUpDate)) $rma['follow_up_date'] = ck_datetime::format_direct($followUpDate, 'Y-m-d');
		if (!empty($restockFee)) $rma['restock_fee_percent'] = $restockPercentage;

		//look for shipping and coupon values
		$order = new ck_sales_order($orderId);
		$orderTotals = $order->get_simple_totals();
		if (!empty($orderTotals['shipping'])) $rma['shipping_amount'] = $orderTotals['shipping'];
		if (!empty($orderTotals['coupon'])) $rma['coupon_amount'] = $orderTotals['coupon'];

		$insert = new prepared_fields($rma, prepared_fields::INSERT_QUERY);
		$rma_id = prepared_query::insert('INSERT INTO rma ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());

		$skip_future_serials = [];

		// create RMA products
		foreach ($orderProductIds as $index => $orderProductId) {
			if (!empty($skip_future_serials[$orderProductId])) continue;
			if (empty($reasonIds[$index])) throw new Exception("Must supply reason for row ".($index+1));

			$orderProduct = prepared_query::fetch('SELECT op.products_quantity - IFNULL(SUM(rp.quantity), 0) as open_order_qty, psc.serialized FROM orders_products op JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN rma_product rp ON op.orders_products_id = rp.order_product_id WHERE op.orders_products_id = :orders_products_id GROUP BY op.orders_products_id', cardinality::ROW, [':orders_products_id' => $orderProductId]);

			$rma_product = [
				'rma_id' => $rma_id,
				'order_product_id' => $orderProductId,
				'reason_id' => $reasonIds[$index],
				'comments' => $comments[$index],
			];

			if (!empty($orderProduct['serialized'])) {
				$rma_product['quantity'] = 1;

				if ($serialIds[$index] == 'ALL') {
					$skip_future_serials[$orderProductId] = TRUE;
					$serials = prepared_query::fetch('SELECT s.id as serial_id, s.serial FROM serials s JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN rma_product rp ON s.id = rp.serial_id AND sh.order_product_id = rp.order_product_id WHERE sh.order_product_id = :orders_products_id AND rp.id IS NULL', cardinality::SET, [':orders_products_id' => $orderProductId]);

					foreach ($serials as $serial) {						
						$rma_product['serial_id'] = $serial['serial_id'];
						$insert = new prepared_fields($rma_product, prepared_fields::INSERT_QUERY);
						$rma_product_id = prepared_query::insert('INSERT INTO rma_product ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
					}

					// we just short circuited the rest of the process
					continue;
				}
				else $rma_product['serial_id'] = $serialIds[$index];
			}
			else {
				if ((int)$quantities[$index] < 1) throw new Exception("Must supply quantity for row ".($index+1));

				$remaining = $orderProduct['open_order_qty'];

				if ((int)$quantities[$index] > $remaining) throw new Exception("Quantity to RMA ($quantities[$index]) must not exceed quantity available for RMA ($remaining) for row ".($index+1));

				$rma_product['quantity'] = $quantities[$index];
			}

			$insert = new prepared_fields($rma_product, prepared_fields::INSERT_QUERY);
			$rma_product_id = prepared_query::insert('INSERT INTO rma_product ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
		}

		// create RMA note
		if (strlen($notes) > 0) prepared_query::insert('INSERT INTO rma_note (rma_id, note_text, created_on, created_by) VALUES (:rma_id, :note_text, NOW(), :admin_id)', [':rma_id' => $rma_id, ':note_text' => $notes, ':admin_id' => ck_admin::login_instance()->id()]);

		/*try {
			$ckrma = new ck_rma2($rma->id);

			$customer = $ckrma->get_customer();
			$hubspot = new api_hubspot;
			$hubspot->update_company($customer);
			//$hubspot->update_transaction($ckrma);
		}
		catch (Exception $e) {
			echo json_encode(['id' => $rma->id, 'non-fatal-error' => $e->__toString()]);
			exit();
		}*/

		$response = ['id' => $rma_id];
	}
	catch (Exception $e) {
		prepared_query::fail_transaction();
		$response = ['error' => $e->getMessage()];
	}
	finally {
		prepared_query::transaction_end(NULL, $savepoint_id);
	}

	echo json_encode($response);
	exit();
}

$orderProductIds = $_GET['orderProductIds'];
$hasSerial = FALSE;

// present form
foreach ($orderProductIds as $orderProductId) {
	if ($srl = prepared_query::fetch('SELECT psc.serialized FROM orders_products op JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE op.orders_products_id = :orders_products_id', cardinality::SINGLE, [':orders_products_id' => $orderProductId])) {
		$hasSerial = TRUE;
		break;
	}
}

$rmaReasons = prepared_query::fetch('SELECT * FROM rma_reason ORDER BY description DESC', cardinality::SET); ?>
<h4 style="text-align: center;">RMA for Order <?= $orderId; ?></h4>
<form id="rma-quick-add">
	<table style="text-align: center;">
		<thead>
			<tr>
				<th>Quantity</th>
				<?php if ($hasSerial) { ?>
				<th>Serial</th>
				<?php } ?>
				<th>Product</th>
				<th>Reason</th>
				<th>Comments</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($orderProductIds as $orderProductId) {
				$orderProduct = prepared_query::fetch('SELECT op.products_name, op.products_quantity - IFNULL(SUM(rp.quantity), 0) as open_order_qty, psc.serialized FROM orders_products op JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN rma_product rp ON op.orders_products_id = rp.order_product_id WHERE op.orders_products_id = :orders_products_id GROUP BY op.orders_products_id', cardinality::ROW, [':orders_products_id' => $orderProductId]); ?>
			<tr>
				<td>
					<?php if (!empty($orderProduct['serialized'])) { ?>
					1 <input name="quantities[]" type="hidden" value="1">
					<?php }
					else { ?>
					<input name="quantities[]" type="text" style="width: 25px;"> of <?= $orderProduct['open_order_qty']; ?>
					<?php } ?>
				</td>
				<?php if ($hasSerial) { ?>
				<td>
					<?php if (!empty($orderProduct['serialized'])) {
						$serials = prepared_query::fetch('SELECT s.id as serial_id, s.serial FROM serials s JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN rma_product rp ON s.id = rp.serial_id AND sh.order_product_id = rp.order_product_id WHERE sh.order_product_id = :orders_products_id AND rp.id IS NULL', cardinality::SET, [':orders_products_id' => $orderProductId]); ?>
					<select name="serials[]" class="serial-select">
						<?php if (count($serials) > 1) { ?>
						<option value="0">--Please Select--</option>
						<option value="ALL">ADD ALL SERIALS</option>
						<?php }

						foreach ($serials as $serial) { ?>
						<option value="<?= $serial['serial_id']; ?>" class="serial-id-<?= $serial['serial_id']; ?>"><?= $serial['serial']; ?></option>
						<?php } ?>
					</select>
					<?php }
					else { ?>
					<input type="hidden" name="serials[]" value="">
					<?php } ?>
				</td>
				<?php } ?>
				<td style="width: 300px;"><?= $orderProduct['products_name']; ?></td>
				<td>
					<select name="reasons[]">
						<option value="0">--Please Select--</option>
						<?php foreach ($rmaReasons as $reason) { ?>
						<option value="<?= $reason['id']; ?>"><?= $reason['description']; ?></option>
						<?php } ?>
					</select>
				</td>
				<td><textarea name="comments[]"></textarea><input name="order_product_ids[]" type="hidden" value="<?= $orderProductId; ?>"></td>
				<td class="actions"><a href="#" class="add-product-row">Add</a></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<br>
	<label for="disposition">For:</label>
	<select id="disposition" name="disposition">
		<option value="advance replacement">Advance Replacement</option>
		<option value="replacement">Replacement</option>
		<option value="refund">Refund</option>
		<option value="credit">Credit</option>
	</select>
	<br>
	<label for="notes">Notes:</label>
	<textarea id="notes" name="notes"></textarea>
	<br>
	<label for="restock">Restock Fee?</label>
	<input id="restock" name="restock_fee" type="checkbox">
	<input name="restock_percentage" type="text" style="width: 25px;" value="25">%
	<br>
	<label for="follow-up-date">Follow Up:</label>
	<input id="follow-up-date" name="follow_up_date" type="text" style="width: 70px;" value="<?= date('m/d/Y', strtotime('+7 days')); ?>">
	<br>
	<input id="quick-add-rma" type="button" value="Create RMA" style="float: right;">
</form>
<script>
	jQuery('.serial-select').change(function() {
		if (jQuery(this).val() == 'ALL') return;

		if (jQuery(this).attr('data-previous-value') != undefined) {
			jQuery('.serial-id-'+jQuery(this).attr('data-previous-value')).show();
		}

		jQuery(this).attr('data-previous-value', jQuery(this).val());

		jQuery('.serial-id-'+jQuery(this).val()).hide();

		jQuery(this).find('.serial-id-'+jQuery(this).val()).show();
	});
</script>
