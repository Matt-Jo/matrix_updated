<?php
class ck_cart_type extends ck_types {

	public function __construct($customers_id=NULL) {
		$this->_init();
		if (!empty($customers_id)) $this->load('customers_id', $customers_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'customers_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'customers_extra_logins_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'cart_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'active_cart_shipment_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'cart_id' => NULL,
				'cart_key' => NULL,
				'customers_id' => NULL,
				'customers_extra_logins_id' => NULL,
				'customer_comments' => NULL,
				'admin_comments' => NULL,
				'date_created' => NULL,
				'date_updated' => NULL
			]
		],
		'freight' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'totals' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'title' => NULL,
				'text' => NULL,
				'value' => NULL,
				'class' => NULL,
				'sort_order' => NULL,
				'actual_shipping_cost' => NULL,
				'external_id' => NULL
			]
		],
		'customer' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'shipments' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'cart_shipment_id' => NULL,
				'shipping_address_book_id' => NULL,
				'residential' => NULL,
				'blind' => NULL,
				'order_po_number' => NULL,
				'reclaimed_materials' => NULL,
				'shipping_method_id' => NULL,
				'freight_needs_liftgate' => NULL,
				'freight_needs_inside_delivery' => NULL,
				'freight_needs_limited_access' => NULL,
				'shipment_account_choice' => NULL,
				'ups_account_number' => NULL,
				'fedex_account_number' => NULL,
				'date_created' => NULL,
				'date_updated' => NULL
			]
		],
		'shipping_address' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL // with no key format, we're using our own runtime defined key format
		],
		'billing_address' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL // with no key format, we're using our own runtime defined key format
		],
		'payments' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				/*'cart_shipment_id' => NULL,
				'billing_address_book_id' => NULL,
				'payment_method_id' => NULL,
				'payment_po_number' => NULL,
				'payment_coupon_id' => NULL,
				'payment_coupon_code' => NULL,
				'date_created' => NULL,
				'date_updated' => NULL*/
			]
		],
		'quotes' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				/*'cart_quote_id' => NULL,
				'cart_shipment_id' => NULL,
				'quote_id' => NULL,
				'date_created' => NULL*/
			]
		],
		'products' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				/*'cart_product_id' => NULL,
				'cart_shipment_id' => NULL,
				'products_id' => NULL,
				'quantity' => NULL,
				'unit_price' => NULL,
				'price_options_snapshot' => NULL,
				'option_type' => NULL,
				'parent_products_id' => NULL,
				'date_created' => NULL,
				'date_updated' => NULL*/
			]
		],
		'universal_products' => [
			'cardinality' => cardinality::MAP,
			'format' => [
			]
		],
		'selected_ship_rate_quote' => [
			'cardinality' => cardinality::ROW,
			'format' => NULL
		]
	];
}
?>
