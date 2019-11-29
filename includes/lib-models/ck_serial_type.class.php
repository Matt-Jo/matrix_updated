<?php
class ck_serial_type extends ck_types {

	public function __construct($serial_id=NULL) {
		$this->_init();
		if (!empty($serial_id)) $this->load('serial_id', $serial_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'serial_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'serial_id' => NULL,
				'serial_number' => NULL,
				'status_code' => NULL,
				'status' => NULL,
				'status_sort_order' => NULL,
				'stock_id' => NULL,
			]
		],
		'ipn' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'reservation' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'serials_assignment_id' => NULL,
				'order' => NULL,
				'orders_products_id' => NULL,
				'admin' => NULL,
				'assignment_date' => NULL,
			],
		],
		'history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'serial_history_id' => NULL,
				'entered_date' => NULL,
				'shipped_date' => NULL,
				'condition_code' => NULL,
				'condition' => NULL,
				'orders_id' => NULL,
				'orders_products_id' => NULL,
				'po_number' => NULL,
				'purchase_order_receiving_sessions_id' => NULL,
				'purchase_order_products_id' => NULL,
				'purchase_order_received_products_id' => NULL,
				'purchase_order_id' => NULL,
				'dram' => NULL,
				'flash' => NULL,
				'image' => NULL,
				'ios' => NULL,
				'mac_address' => NULL,
				'version' => NULL,
				'cost' => NULL,
				'transfer_price' => NULL,
				'transfer_date' => NULL,
				'show_version' => NULL,
				'short_notes' => NULL,
				'bin_location' => NULL,
				'confirmation_date' => NULL,
				'rma_id' => NULL,
				'tester_admin_id' => NULL
			]
		],
		'hold' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'inventory_hold_id' => NULL,
				'quantity' => NULL, // for serials, this will always be 1
				'hold_reason_id' => NULL,
				'hold_reason' => NULL,
				'hold_created_date' => NULL,
				'notes' => NULL,
				'creator_id' => NULL,
				'creator' => NULL
			]
		],
		'last_po' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
	];
}
?>
