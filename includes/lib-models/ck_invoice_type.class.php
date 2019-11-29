<?php
class ck_invoice_type extends ck_types {

	public function __construct($invoice_id=NULL) {
		$this->_init();
		if (!empty($invoice_id)) $this->load('invoice_id', $invoice_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'invoice_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'invoice_id' => NULL,
				'customers_id' => NULL,
				'customers_extra_logins_id' => NULL,
				'extra_logins_email_address' => NULL,
				'extra_logins_firstname' => NULL,
				'extra_logins_lastname' => NULL,
				'extra_logins_copy_account' => NULL,
				'orders_id' => NULL,
				'rma_id' => NULL,
				'invoice_date' => NULL,
				//'po_number' => NULL,
				'invoice_rma' => NULL,
				'paid_in_full' => NULL,
				'credit_payment_id' => NULL,
				'credit_memo' => NULL,
				'original_invoice_id' => NULL,
				'late_notice_date' => NULL,
				'latest_version' => NULL,
				'payment_method_code' => NULL,
				'payment_method_label' => NULL,
				'purchase_order_number' => NULL,
				'net10_po' => NULL,
				'net15_po' => NULL,
				'net30_po' => NULL,
				'net45_po' => NULL,
			]
		],
		'incentive' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'sales_incentive_tier_id' => NULL,
				'incentive_percentage' => NULL,
				'incentive_base_total' => NULL,
				'incentive_product_total' => NULL,
				'incentive_final_total' => NULL,
				'incentive_accrued' => NULL,
				'incentive_paid' => NULL,
				'overridden' => NULL,
				'incentive_override_percentage' => NULL,
				'incentive_override_date' => NULL,
				'incentive_override_note' => NULL,
				'final_incentive_percentage' => NULL,
			],
		],
		'customer' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'extra_login' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'customers_extra_logins_id' => NULL,
				'extra_logins_email_address' => NULL,
				'extra_logins_firstname' => NULL,
				'extra_logins_lastname' => NULL,
				'extra_logins_copy_account' => NULL
			]
		],
		'order' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'rma' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'simple_totals' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'coupon' => NULL,
				'custom' => NULL,
				'shipping' => NULL,
				'tax' => NULL,
				'total' => NULL,
				'other' => NULL,
			]
		],
		'totals' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'invoice_total_id' => NULL,
				'class' => NULL,
				'title' => NULL,
				'value' => NULL,
				'invoice_total_date' => NULL
			],
			'key_format' => [
				'coupon' => [],
				'custom' => [],
				'shipping' => [],
				'tax' => [],
				'total' => [],
				'other' => [],
				'consolidated' => []
			]
		],
		'products' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'invoice_item_id' => NULL,
				'orders_products_id' => NULL,
				'rma_product_id' => NULL,
				'stock_id' => NULL,
				'ipn' => NULL,
				'products_id' => NULL,
				'parent_products_id' => NULL,
				'listing' => NULL,
				'invoice_unit_price' => NULL,
				'revenue' => NULL,
				'quantity' => NULL,
				'invoice_unit_cost' => NULL,
				'invoice_line_cost' => NULL,
				'serialized' => NULL,
				'option_type' => NULL,
				'serials' => [],
				'name' => NULL,
				'model' => NULL
			]
		],
		'consolidated_products' => [
			'cardinality' => cardinality::MAP,
			// map is keyed by orders_products_id
			'format' => [
				'stock_id' => NULL,
				'ipn' => NULL,
				'products_id' => NULL,
				'parent_products_id' => NULL,
				'listing' => NULL,
				'invoice_unit_price' => NULL,
				'revenue' => NULL,
				'quantity' => NULL,
				'invoice_unit_cost' => NULL,
				'invoice_line_cost' => NULL,
				'serialized' => NULL,
				'option_type' => NULL,
				'serials' => [],
				'invoice_item_ids' => [],
				'rma_product_id' => [],
				'name' => NULL,
				'model' => NULL
			]
		],
		'payments' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'payment_to_invoice_id' => NULL,
				'credit_amount' => NULL,
				'credit_date' => NULL,
				'payment_id' => NULL,
				'payment_amount' => NULL,
				'payment_ref' => NULL,
				'payment_date' => NULL,
				'payment_method_id' => NULL,
				'payment_method_code' => NULL,
				'payment_method_label' => NULL,
			]
		],
		'notes' => [
			'cardinality' => cardinality::COLUMN,
			'format' => []
		]
	];
}
?>
