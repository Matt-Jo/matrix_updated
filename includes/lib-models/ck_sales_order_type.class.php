<?php
class ck_sales_order_type extends ck_types {

	public function __construct($orders_id=NULL) {
		$this->_init();
		if (!empty($orders_id)) $this->load('orders_id', $orders_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'orders_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'orders_id' => NULL,
				'released' => NULL,
				'customers_id' => NULL,
				'customers_name' => NULL,
				'customers_company' => NULL,
				'customers_street_address' => NULL,
				'customers_suburb' => NULL,
				'customers_city' => NULL,
				'customers_postcode' => NULL,
				'customers_state' => NULL,
				'customers_country' => NULL,
				'customers_telephone' => NULL,
				'customers_email_address' => NULL,
				'customers_address_format_id' => NULL,
				'delivery_name' => NULL,
				'delivery_company' => NULL,
				'delivery_street_address' => NULL,
				'delivery_suburb' => NULL,
				'delivery_city' => NULL,
				'delivery_postcode' => NULL,
				'delivery_state' => NULL,
				'delivery_country' => NULL,
				'delivery_address_format_id' => NULL,
				'delivery_nav_code' => NULL,
				'billing_name' => NULL,
				'billing_company' => NULL,
				'billing_street_address' => NULL,
				'billing_suburb' => NULL,
				'billing_city' => NULL,
				'billing_postcode' => NULL,
				'billing_state' => NULL,
				'billing_country' => NULL,
				'billing_address_format_id' => NULL,
				'tax_exempt' => NULL,
				'tax_status_reason' => NULL,
				'tax_exempt_raw' => NULL,
				'payment_method_id' => NULL,
				'payment_info' => NULL,
				'payment_id' => NULL,
				'cc_type' => NULL,
				'cc_owner' => NULL,
				'cc_number' => NULL,
				'cc_expires' => NULL,
				'last_modified' => NULL,
				'date_purchased' => NULL,
				'orders_status' => NULL,
				'orders_status_sort_order' => NULL,
				'orders_date_finished' => NULL,
				'currency' => NULL,
				'currency_value' => NULL,
				'account_name' => NULL,
				'account_number' => NULL,
				'purchased_without_account' => NULL,
				'paypal_ipn_id' => NULL,
				'customers_referer_url' => NULL,
				'fedex_tracking' => NULL,
				'order_notes' => NULL,
				'customers_fedex' => NULL,
				'customers_ups' => NULL,
				'dpm' => NULL,
				'dsm' => NULL,
				'net10_paid' => NULL,
				'net15_paid' => NULL,
				'net30_paid' => NULL,
				'dropship' => NULL,
				'purchase_order_number' => NULL,
				'packing_slip' => NULL,
				'net10_po' => NULL,
				'net15_po' => NULL,
				'net30_po' => NULL,
				'net45_po' => NULL,
				'orders_weight' => NULL,
				'actual_weight' => NULL,
				'all_items_in_stock' => NULL,
				'delivery_telephone' => NULL,
				'auth_cc_num' => NULL,
				'auth_cc_expire' => NULL,
				'auth_cc_ccv' => NULL,
				'paypal_issue' => NULL,
				'orders_sales_rep_id' => NULL,
				'sales_team_id' => NULL,
				'customers_extra_logins_id' => NULL,
				'orders_sub_status' => NULL,
				'followup_date' => NULL,
				'promised_ship_date' => NULL,
				'customer_type' => NULL,
				'customer_dept' => NULL,
				'orders_status_physical' => NULL,
				'picker_id' => NULL,
				'qa_id' => NULL,
				'packer_id' => NULL,
				'hold_requested' => NULL,
				'abort_requested' => NULL,
				'ups_account_number' => NULL,
				'fedex_signature_type' => NULL,
				'fedex_account_number' => NULL,
				'fedex_bill_type' => NULL,
				'avatax_rate' => NULL,
				'admin_id' => NULL,
				'channel' => NULL,
				'source' => NULL,
				'medium' => NULL,
				'campaign' => NULL,
				'keyword' => NULL,
				'content' => NULL,
				'utmz' => NULL,
				'parent_orders_id' => NULL,
				'amazon_order_number' => NULL,
				'orders_canceled_reason_id' => NULL,
				'ca_order_id' => NULL,
				'ebay_order_id' => NULL,
				'nav_sent' => NULL,
				'nav_id' => NULL,
				'split_order' => NULL,
				'gtm_data_sent' => NULL,
				'customer_order_count' => NULL,
				'legacy_order' => NULL,
				'paymentsvc_id' => NULL,
				'order_access_token' => NULL,
				'use_reclaimed_packaging' => NULL,
				'payment_method_code' => NULL,
				'payment_method_label' => NULL,
				'payment_method_osc_code' => NULL,
				'orders_status_name' => NULL,
				'orders_sub_status_name' => NULL,
				'extra_logins_email_address' => NULL,
				'extra_logins_firstname' => NULL,
				'extra_logins_lastname' => NULL,
				'extra_logins_copy_account' => NULL,
			]
		],
		'service_rep' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'account_manager' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'sales_team' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
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
				'extra_logins_lastname' => NULL
			]
		],
		'address' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'name' => NULL,
				'company' => NULL,
				'street_address_1' => NULL,
				'street_address_2' => NULL,
				'city' => NULL,
				'zip' => NULL,
				'state' => NULL,
				'country' => NULL,
				'phone' => NULL,
				'address_format_id' => NULL
			]
		],
		'billing_address' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'name' => NULL,
				'company' => NULL,
				'street_address_1' => NULL,
				'street_address_2' => NULL,
				'city' => NULL,
				'zip' => NULL,
				'state' => NULL,
				'country' => NULL,
				'address_format_id' => NULL
			]
		],
		'shipping_address' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'name' => NULL,
				'company' => NULL,
				'street_address_1' => NULL,
				'street_address_2' => NULL,
				'city' => NULL,
				'zip' => NULL,
				'state' => NULL,
				'country' => NULL,
				'phone' => NULL,
				'address_format_id' => NULL
			]
		],
		'addr' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL,
		],
		// bill/ship address are the address objects - rather than take over legacy billing/shipping address, just create new data for backwards compatibility - we'll switch over eventually
		'bill_address' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL,
		],
		'ship_address' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL,
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
				'orders_total_id' => NULL,
				'class' => NULL,
				'title' => NULL,
				'display_value' => NULL,
				'value' => NULL,
				'actual_shipping_cost' => NULL,
				'shipping_method_id' => NULL
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
		'estimated_costs' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'product_total' => NULL,
				'coupon_total' => NULL,
				'shipping_total' => NULL,
				'incalculable' => NULL,
				'incalculable_reason' => NULL
			]
		],
		'final_costs' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'product_total' => NULL,
				'coupon_total' => NULL,
				'shipping_total' => NULL,
				'incalculable' => NULL,
				'incalculable_reason' => NULL
			]
		],
		'shipping_method_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'shipping_method' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'shipping_code' => NULL,
				'method_name' => NULL,
				'carrier' => NULL,
				'description' => NULL,
				'original_code' => NULL,
				'abol_code' => NULL,
				'worldship_code' => NULL,
				'inactive' => NULL,
				'saturday_delivery' => NULL,
				'transit_days' => NULL
			]
		],
		'products' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'orders_products_id' => NULL,
				'products_id' => NULL,
				'listing' => NULL,
				'stock_id' => NULL,
				'ipn' => NULL,
				'allocated_serials' => NULL,
				'parent_products_id' => NULL,
				'option_type' => NULL,
				'model' => NULL,
				'name' => NULL,
				'revenue' => NULL,
				'final_price' => NULL,
				'display_price' => NULL,
				'price_reason' => NULL,
				'products_tax' => NULL,
				'quantity' => NULL,
				'cost' => NULL,
				'expected_ship_date' => NULL,
				'is_quote' => NULL,
				'exclude_forecast' => NULL,
				'vendor_stock_item' => NULL,
				'specials_excess' => NULL,
				'po_allocations' => NULL,
				'is_shippable' => NULL,
				'availability_status' => NULL,
			]
		],
		'po_allocations' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'allocation_id' => NULL,
				'orders_products_id' => NULL,
				'ipn' => NULL,
				'po_id' => NULL,
				'purchase_order_number' => NULL,
				'quantity' => NULL,
				'expected_date' => NULL
			]
		],
		'freight_lines' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'freight_shipment_id' => NULL,
				'list_total' => NULL,
				'residential' => NULL,
				'liftgate' => NULL,
				'inside' => NULL,
				'limitaccess' => NULL,
				'entered' => NULL,
				'products' => []
			]
		],
		'availability_status' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'is_stock_shippable' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'status_history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'orders_status_history_id' => NULL,
				'admin' => NULL,
				'orders_status_code' => NULL,
				'orders_status' => NULL,
				'orders_substatus_code' => NULL,
				'orders_substatus' => NULL,
				'status_date' => NULL,
				'customer_notified' => NULL,
				'comments' => NULL
			]
		],
		'notes' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'orders_note_id' => NULL,
				'admin_id' => NULL,
				'admin_first_name' => NULL,
				'admin_last_name' => NULL,
				'admin_email' => NULL,
				'note_text' => NULL,
				'note_date' => NULL,
				'modified_date' => NULL,
				'deleted' => NULL,
				'shipping_notice' => NULL
			]
		],
		'acc_order_notes' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'id' => NULL,
				'admin_firstname' => NULL,
				'admin_lastname' => NULL,
				'text' => NULL
			]
		],
		'packages' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'orders_packages_id' => NULL,
				'orders_products_id' => NULL,
				'package_type_id' => NULL,
				'package_name' => NULL,
				'logoed' => NULL,
				'package_length' => NULL,
				'package_width' => NULL,
				'package_height' => NULL,
				'scale_weight' => NULL,
				'void' => NULL,
				'date_time_created' => NULL,
				'date_time_voided' => NULL,
				'tracking_num' => NULL,
				'shipping_method_id' => NULL,
				'cost' => NULL,
				'tracking_void' => NULL,
				'tracking_date_created' => NULL,
				'tracking_date_voided' => NULL,
				'carrier' => NULL,
				'narvar_code' => NULL
			]
		],
		'fraud_score' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'score' => NULL,
				'proxy_score' => NULL,
				'spam_score' => NULL,
				'err' => NULL,
				'query' => NULL,
				'country_match' => NULL,
				'country_code' => NULL,
				'hi_risk' => NULL,
				'bin_match' => NULL,
				'bin_country' => NULL,
				'bin_name' => NULL,
				'ip_isp' => NULL,
				'ip_org' => NULL,
				'distance' => NULL,
				'anonymous_proxy' => NULL,
				'free_mail' => NULL,
				'cust_phone' => NULL,
				'ip_city' => NULL,
				'ip_region' => NULL,
				'ip_latitude' => NULL,
				'ip_longitude' => NULL
			]
		],
		'payments' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'pmt_applied_id' => NULL,
				'pmt_applied_amount' => NULL,
				'payment_id' => NULL,
				'payment_amount' => NULL,
				'payment_ref' => NULL,
				'payment_date' => NULL,
				'payment_method_id' => NULL,
				'payment_method_code' => NULL,
				'payment_method_label' => NULL,
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
		'rmas' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'rma' => NULL
			]
		],
		'child_orders' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'order' => NULL
			]
		],
		'recipients' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'orders_notification_recipient_id' => NULL,
				'email' => NULL,
				'name' => NULL,
			]
		]
	];
}
?>
