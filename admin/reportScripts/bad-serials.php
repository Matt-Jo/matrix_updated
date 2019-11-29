<?php
require(__DIR__.'/../../includes/application_top.php');

debug_tools::show_all();

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

class wacky_serial {
	const ORDER_SHIPPED = 3;
	const ORDER_CANCELED = 6;

	const SERIAL_RECEIVING = 0;
	const SERIAL_INSTOCK = 2;
	const SERIAL_ALLOCATED = 3;
	const SERIAL_INVOICED = 4;
}

if ($serials = prepared_query::fetch('SELECT s.id as serial_id, s.serial, sh.id as serial_history_id, sh.entered_date, sh.po_number, o.orders_id, o.orders_status FROM serials s JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id LEFT JOIN orders o ON sh.order_id = o.orders_id WHERE s.status = :receiving AND NULLIF(sh.pors_id, 0) IS NULL AND NULLIF(sh.porp_id, 0) IS NULL', cardinality::SET, [':receiving' => wacky_serial::SERIAL_RECEIVING])) {
	foreach ($serials as $serial) {
		// if this serial is attached to a shipped order, mark serial invoiced
		if (!empty($serial['orders_id']) && $serial['orders_status'] == wacky_serial::ORDER_SHIPPED) {
			prepared_query::execute('UPDATE serials SET status = :invoiced WHERE id = :serial_id', [':invoiced' => wacky_serial::SERIAL_INVOICED, ':serial_id' => $serial['serial_id']]);
			debug_tools::mark('Set serial ['.$serial['serial'].'] invoiced');
		}

		// if this serial is attached to an order that is *not* canceled, mark serial allocated
		elseif (!empty($serial['orders_id']) && $serial['orders_status'] != wacky_serial::ORDER_CANCELED) {
			prepared_query::execute('UPDATE serials SET status = :allocated WHERE id = :serial_id', [':invoiced' => wacky_serial::SERIAL_ALLOCATED, ':serial_id' => $serial['serial_id']]);
			debug_tools::mark('Set serial ['.$serial['serial'].'] allocated');
		}

		// all other cases, delete the history record
		else {
			prepared_query::execute('DELETE FROM serials_history WHERE id = :serial_history_id', [':serial_history_id' => $serial['serial_history_id']]);
			debug_tools::mark('Remove broken serial history for serial ['.$serial['serial'].']');
		}
	}
}

if ($serials = prepared_query::fetch('SELECT s.id as serial_id, s.serial, sh.id as serial_history_id, o.orders_status FROM serials s JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id JOIN orders o ON sh.order_id = o.orders_id WHERE s.status IN (:receiving, :instock) AND NULLIF(sh.order_id, 0) IS NOT NULL', cardinality::SET, [':receiving' => wacky_serial::SERIAL_RECEIVING, ':instock' => wacky_serial::SERIAL_INSTOCK])) {
	foreach ($serials as $serial) {
		// if this serial is attached to a shipped order, mark serial invoiced
		if ($serial['orders_status'] == wacky_serial::ORDER_SHIPPED) {
			prepared_query::execute('UPDATE serials SET status = :invoiced WHERE id = :serial_id', [':invoiced' => wacky_serial::SERIAL_INVOICED, ':serial_id' => $serial['serial_id']]);
			debug_tools::mark('Set serial ['.$serial['serial'].'] invoiced');
		}

		// if this serial is attached to an order that is *not* canceled, mark serial allocated
		elseif ($serial['orders_status'] != wacky_serial::ORDER_CANCELED) {
			prepared_query::execute('UPDATE serials SET status = :allocated WHERE id = :serial_id', [':invoiced' => wacky_serial::SERIAL_ALLOCATED, ':serial_id' => $serial['serial_id']]);
			debug_tools::mark('Set serial ['.$serial['serial'].'] allocated');
		}

		// all other cases, remove order allocation
		else {
			prepared_query::execute('UPDATE serials_history SET order_id = NULL, order_product_id = NULL WHERE id = :serial_history_id', [':serial_history_id' => $serial['serial_history_id']]);
			debug_tools::mark('Remove broken allocation for serial ['.$serial['serial'].']');
		}
	}
}
?>
