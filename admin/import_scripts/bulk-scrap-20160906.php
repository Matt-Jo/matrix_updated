<?php
function upload_data($data) {
	$admin_id = $_SESSION['perms']['admin_id'];
	$scrap_date = date('Y-m-d H:i:s');

	foreach ($data as $idx => $hold) {
		if ($idx === 0) continue; // skip the header row

		$inventory_hold_id = $hold[0];
		$qty = $hold[2];
		$serial = $hold[3];

		$hold_data = prepared_query::fetch('SELECT * FROM inventory_hold WHERE id = :id', cardinality::ROW, [':id' => $inventory_hold_id]);

		if (empty($hold_data)) continue;

		if (!empty($hold_data['serial_id'])) {
			$serial_id = $hold_data['serial_id'];
			$serial_data = prepared_query::fetch('SELECT po.id as po_id, sh.cost, s.ipn as stock_id FROM serials s JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id JOIN purchase_orders po ON sh.po_number = po.purchase_order_number WHERE sh0.id IS NULL AND sh.serial_id = :serial_id', cardinality::ROW, [':serial_id' => $hold_data['serial_id']]);;

			$ipn = new ck_ipn2($serial_data['stock_id']);

			$po_id = $serial_data['po_id'];
			$cost = $serial_data['cost'];
			$old_qty = 1;
			$new_qty = 0;
		}
		else {
			$ipn = new ck_ipn2($hold_data['stock_id']);

			if (empty($ipn->id())) var_dump($hold_data);

			$serial_id = NULL;
			$po_id = NULL;
			$cost = $ipn->get_header('average_cost');
			$old_qty = $ipn->get_inventory('on_hand');
			$new_qty = $old_qty-$qty;
		}

		if (!$ipn->found()) continue;

		$scrap_data = array(
			':ipn_id' => $ipn->id(),
			':po_id' => $po_id,
			':admin_id' => $admin_id,
			':cost' => $cost,
			':scrap_date' => $scrap_date,
			':old_qty' => $old_qty,
			':new_qty' => $new_qty,
			':serial_id' => $serial_id,
			':inventory_adjustment_reason_id' => 8,
			':inventory_adjustment_type_id' => 3,
			':notes' => 'Bulk Scrap from Spreadsheet',
		);


        prepared_query::execute('INSERT INTO inventory_adjustment (ipn_id, serial_id, po_id, admin_id, scrap_date, notes, cost, inventory_adjustment_type_id, inventory_adjustment_reason_id, old_qty, new_qty) VALUES (:ipn_id, :serial_id, :po_id, :admin_id, :scrap_date, :notes, :cost, :inventory_adjustment_type_id, :inventory_adjustment_reason_id, :old_qty, :new_qty)', $scrap_data);

		if ($hold_data['quantity'] == $qty) {
            prepared_query::execute('DELETE FROM inventory_hold WHERE id = :inventory_hold_id', [':inventory_hold_id' => $inventory_hold_id]);

			if (!empty($serial_id)) {
                prepared_query::execute('UPDATE serials SET status = 8 WHERE id = :serial_id', [':serial_id' => $serial_id]);
			}
			else {
				prepared_query::execute('UPDATE products_stock_control SET stock_quantity = stock_quantity - :hold_quantity WHERE stock_id = :stock_id', [':hold_quantity' => $qty, ':stock_id' => $ipn->id()]);
			}

		}
		else {
			prepared_query::execute('UPDATE inventory_hold SET quantity = quantity - :scrap_quantity WHERE id = :inventory_hold_id', [':scrap_quantity' => $qty, ':inventory_hold_id' => $inventory_hold_id]);

			prepared_query::execute('UPDATE products_stock_control SET stock_quantity = stock_quantity - :hold_quantity WHERE stock_id = :stock_id', [':hold_quantity' => $qty, ':stock_id' => $ipn->id()]);
		}
	}

	return array('output' => ['Upload Complete'], 'errors' => []);
}
?>