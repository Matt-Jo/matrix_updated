<?php
class ck_rma_type extends ck_types {

	public function __construct($rma_id=NULL) {
		$this->_init();
		if (!empty($rma_id)) $this->load('rma_id', $rma_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'rma_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'rma_id' => NULL,
				'orders_id' => NULL,
				'customers_id' => NULL,
				'closed' => NULL,
				'disposition' => NULL,
				'restock_fee_percent' => NULL,
				'tracking_number' => NULL,
				'follow_up_date' => NULL,
				'created_on' => NULL,
				'admin_id' => NULL,
				'refund_shipping' => NULL,
				'shipping_amount' => NULL,
				'refund_coupon' => NULL,
				'coupon_amount' => NULL
			]
		],
		'admin' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'customer' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'sales_order' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'products' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'rma_product_id' => NULL,
				'orders_products_id' => NULL,
				'products_id' => NULL,
				'revenue' => NULL,
				'final_price' => NULL,
				'listing' => NULL,
				'stock_id' => NULL,
				'ipn' => NULL,
				'serial_id' => NULL,
				'serial' => NULL,
				'quantity' => NULL,
				'original_order_qty' => NULL,
				'reason_id' => NULL,
				'reason' => NULL,
				'received' => NULL,
				'received_date' => NULL,
				'comments' => NULL,
				'admin_id' => NULL,
				'admin' => NULL,
				'not_defective' => NULL
			]
		],
		'consolidated_products' => [
			'cardinality' => cardinality::MAP,
			// map is keyed by orders_products_id
			'format' => [
				'products_id' => NULL,
				'revenue' => NULL,
				'final_price' => NULL,
				'listing' => NULL,
				'stock_id' => NULL,
				'ipn' => NULL,
				'quantity' => NULL,
				'serials' => [],
				'rma_product_ids' => []
			]
		],
		'notes' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'rma_note_id' => NULL,
				'created_on' => NULL,
				'admin_id' => NULL,
				'admin' => NULL,
				'note' => NULL
			]
		],
		'invoices' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'invoice_id' => NULL,
				'customers_id' => NULL,
				'customers_extra_logins_id' => NULL,
				'invoice_date' => NULL,
				'po_number' => NULL,
				'paid_in_full' => NULL,
				'credit_memo' => NULL,
				'original_invoice' => NULL,
				'late_notice_date' => NULL,
				'total' => NULL,
				'products' => [],
				'totals' => [],
				'allocated_payments' => [],
			]
		],
		'total' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		]
	];
}
?>
