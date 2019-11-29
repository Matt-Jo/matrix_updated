<?php

function po_alloc_op_markup($op_id, $order_status) {
	$alloc_query = prepared_query::fetch("select po2oa.quantity, po2oa.purchase_order_product_id, po.purchase_order_number, po.id from purchase_order_to_order_allocations po2oa left join purchase_order_products pop on po2oa.purchase_order_product_id = pop.id left join purchase_orders po on po.id = pop.purchase_order_id where po2oa.order_product_id = :orders_products_id order by po2oa.purchase_order_product_id desc", cardinality::SET, [':orders_products_id' => $op_id]);
	$output_string = "";
	foreach ($alloc_query as $alloc) {
		if (!empty($output_string)) {
			$output_string .= ", ";
		}
		if ($alloc['purchase_order_product_id'] == '0') {
			$output_string .= 'WAREHOUSE ('.$alloc['quantity'].')';
		}
		else {
			$output_string .= '<a href="po_viewer.php?poId='.$alloc['purchase_order_number'].'" target="_BLANK">'.$alloc['purchase_order_number'].'</a> ('.$alloc['quantity'].')';
		}
	}
	if (empty($output_string)) {
		$output_string = "NONE";
	}
	$perm_check = false;
	if (in_array($_SESSION['login_groups_id'], [1, 5, 7 , 8, 9, 10, 17, 18, 20, 22, 28, 29, 30, 31])) {
		$perm_check = true;
	}
	$status_check = true;
	if (in_array($order_status, [3, 6, 9])) {
		$status_check = false;
	}

	?>
	<div id="po_order_alloc_op_<?= $op_id; ?>">
	PO Allocations
	<?php if ($perm_check && $status_check) { ?>
		(<a href="javascript:void(0);" class="po-alloc-display" opid="<?= $op_id; ?>">edit</a>)
	<?php } ?>
	: <?= $output_string; ?>
	</div>
	<?php
}

function po_alloc_display() {
	$op_id = $_GET['op_id'];
	if (empty($op_id)) {
		die("op_id cannot be empty in po_alloc_display()");
	}
	//determine ipn_id
	$ipn_id = prepared_query::fetch("select p.stock_id from products p, orders_products op where op.orders_products_id = :orders_products_id and (op.products_id) = p.products_id", cardinality::SINGLE, [':orders_products_id' => $op_id]);

	//available POs
	$po_response = prepared_query::fetch('SELECT po.id, po.purchase_order_number, po.expected_date, pop.id as pop_id, (pop.quantity - IFNULL((SELECT SUM(quantity_received) FROM purchase_order_received_products WHERE purchase_order_product_id = pop.id), 0) - IFNULL((SELECT SUM(quantity) FROM purchase_order_to_order_allocations WHERE purchase_order_product_id = pop.id AND order_product_id != ?), 0)) as available_quantity, IFNULL((SELECT SUM(quantity) FROM purchase_order_to_order_allocations WHERE purchase_order_product_id = pop.id AND order_product_id = ?), 0) as allocated_quantity FROM purchase_orders po LEFT JOIN purchase_order_products pop ON po.id = pop.purchase_order_id WHERE pop.ipn_id = ? AND po.status != 4 GROUP BY pop.id ORDER BY pop.id ASC', cardinality::SET, array($op_id, $op_id, $ipn_id));
	$pos = array();
	foreach ($po_response as $po_row) {
		if ($po_row['available_quantity'] > 0) {
			$pos[] = $po_row;
		}
	}

	$quantity_ordered = prepared_query::fetch("select op.products_quantity from orders_products op where op.orders_products_id = :orders_products_id", cardinality::SINGLE, [':orders_products_id' => $op_id]);

	$total_quantity_allocated = prepared_query::fetch("select sum(quantity) as total from purchase_order_to_order_allocations where order_product_id = :orders_products_id", cardinality::SINGLE, [':orders_products_id' => $op_id]);

	$ipn = new ck_ipn2($ipn_id);

	$allocated = prepared_query::fetch("select SUM(po2oa.quantity) from purchase_order_to_order_allocations po2oa, orders_products op, products p where order_product_id != :orders_products_id and purchase_order_product_id = 0 and po2oa.order_product_id = op.orders_products_id and (op.products_id) = p.products_id and p.stock_id = :stock_id", cardinality::SINGLE, [':orders_products_id' => $op_id, ':stock_id' => $ipn_id]);

	$inventory_qty_saleable = $ipn->get_inventory('on_hand') - $ipn->get_inventory('on_hold') - $allocated;

	$inv_alloc_qty = prepared_query::fetch("select quantity from purchase_order_to_order_allocations where order_product_id = :orders_products_id and purchase_order_product_id = 0", cardinality::SINGLE, [':orders_products_id' => $op_id]);
	$inv_alloc_checked = $inv_alloc_qty > 0;
	?>
	<form id="po2oa-edit">
	<input type="hidden" id="po2oa-order-product-id" value="<?= $op_id; ?>"/>
	<center>Allocate From PO's</center>
	<div style="width: 100%; border-top: 1px solid black;">
		<?php if (empty($pos)) { ?>
		<center>No available POs for this product</center>
		<?php } else { ?>
		<table width="100%" cellpadding="5px">
			<tr>
				<th></th>
				<th>PO #</th>
				<th>Est. Date</th>
				<th>Available Qty</th>
				<th>Qty to Allocate</th>
			</tr>
			<?php foreach ($pos as $unused => $po) {
				$checked = false;
				if ($po['allocated_quantity'] > 0) {
					$checked = true;
				}
			?>
			<tr>
				<td align="center">
					<input type="checkbox"
						<?php if (!empty($checked)) { ?>checked="checked" <?php } ?>
						name="po2oa_<?= $po['pop_id']; ?>"
						id="po2oa_<?= $po['pop_id']; ?>"
						class="po2oa-checkbox"/>
				</td>
				<td align="center">
					<?= $po['id']; ?>
				</td>
				<td align="center">
					<?php echo date('m/d/Y', strtotime($po['expected_date'])); ?>
				</td>
				<td align="center">
					<?= $po['available_quantity']; ?>
				</td>
				<td align="center">
					<input type="text"
						<?php if (empty($checked)) { ?> disabled <?php } ?>
						name="po2oa_qty_<?= $po['pop_id']; ?>"
						id="po2oa_qty_<?= $po['pop_id']; ?>"
						class="po2oa-allocated-quantity"
						<?php if (!empty($checked)) { ?> value="<?= $po['allocated_quantity']; ?>" <?php } ?>/>
					<input type="hidden"
						class="po2oa-available-quantity"
						value="<?= $po['available_quantity']; ?>"/>
				</td>
			</tr>
			<?php } ?>
		</table>
		<?php } ?>
	</div>
	<center>Allocate From Available Inventory</center>
	<div style="width: 100%; border-top: 1px solid black;">
		<table width="100%" cellpadding="5px">
			<tr>
				<th></th>
				<th style="width: 100px;"></th>
				<th>Saleable Qty</th>
				<th>Qty to Allocate</th>
			</tr>
			<tr>
				<td align="center">
					<input type="checkbox"
						<?php if (!empty($inv_alloc_checked)) { ?>checked="checked" <?php } ?>
						name="po2oa_0"
						id="po2oa_0"
						<?php if ($inventory_qty_saleable <= 0) { ?> disabled="disabled" <?php } ?>
						class="po2oa-checkbox"/>
				</td>
				<td></td>
				<td align="center"><?php echo ($inventory_qty_saleable > 0) ? $inventory_qty_saleable : "0";?></td>
				<td align="center">
					<input type="text"
						<?php if (empty($inv_alloc_checked)) { ?> disabled <?php } ?>
						name="po2oa_qty_0"
						id="po2oa_qty_0"
						class="po2oa-allocated-quantity"
						<?php if (!empty($inv_alloc_checked)) { ?> value="<?= $inv_alloc_qty; ?>" <?php } ?>/>
					<input type="hidden"
						class="po2oa-available-quantity"
						value="<?= $inventory_qty_saleable; ?>"/>
				</td>
			</tr>
		</table>
	</div>
	<div style="width: 100%; border-top: 1px solid black; text-align: center;">
		<div style="width: 50%; margin-left: 40%; margin-right: 10%; text-align: right;">
			<table>
				<tr>
					<td>Quantity Ordered:</td>
					<td><?= $quantity_ordered; ?><input type="hidden" class="po2oa-ordered-quantity" value="<?= $quantity_ordered; ?>"/></td>
				</tr>
				<tr>
					<td>Total Quantity Allocated:</td>
					<td id="total-qty-allocated"><?= !empty($total_quantity_allocated)?$total_quantity_allocated:0; ?></td>
				</tr>
			</table>
		</div>
		<input type="button" value="Clear" id="po2oa-clear"/>
		<input type="button" value="Save" id="po2oa-save"/>
		<input type="button" value="Cancel" id="po2oa-cancel"/>
	</div>
	</form>
	<?php
}

function po_alloc_save($op_id) {

	po_alloc_remove($op_id);

	//now prepare the data
	$admin_id = $_SESSION['login_id'];
	foreach ($_POST as $key => $value) {
		//determine if we have a checkbox
		if (strpos($key, 'po2oa_') == 0 && strpos($key, 'po2oa_qty_') === FALSE && $value == 'on') {
			$pop_id = substr($key, 6);
			$quantity = $_POST['po2oa_qty_'.$pop_id];
			prepared_query::execute("insert into purchase_order_to_order_allocations (purchase_order_product_id, order_product_id, quantity, modified, admin_id) values (:pop_id, :orders_products_id, :qty, now(), :admin_id)", [':pop_id' => $pop_id, ':orders_products_id' => $op_id, ':qty' => $quantity, ':admin_id' => $admin_id]);
		}
	}
}

function po_alloc_remove($op_id) {
	prepared_query::execute("delete from purchase_order_to_order_allocations where order_product_id = :orders_products_id", [':orders_products_id' => $op_id]);
}

function po_alloc_remove_by_pop_id($pop_id) {
	prepared_query::execute("delete from purchase_order_to_order_allocations where purchase_order_product_id = :pop_id", [':pop_id' => $pop_id]);
}

function po_alloc_check_and_remove_warehouse_by_ipn($ipn_id) {
	//check if we need to proceed with removing the warehouse allocations
	$ipn = new ck_ipn2($ipn_id);
	if ($ipn->get_inventory('available') >= 0) {
		return true;
	}
	//lookup all the po2oa.id for this ipn that are from warehouse
	$warehouse_query = prepared_query::fetch("select po2oa.id from purchase_order_to_order_allocations po2oa, orders_products op, products p where po2oa.purchase_order_product_id = 0 and po2oa.order_product_id = op.orders_products_id and (op.products_id) = p.products_id and p.stock_id = :stock_id", [':stock_id' => $ipn_id]);
	foreach ($warehouse_query as $row) {
		prepared_query::execute("delete from purchase_order_to_order_allocations where id = :id", [':id' => $row['id']]);
	}
}
