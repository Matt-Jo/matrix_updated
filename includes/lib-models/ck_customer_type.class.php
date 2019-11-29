<?php
class ck_customer_type extends ck_types {

	protected static $queries = [
		'order_statuses' => [
			'qry' => 'SELECT * FROM orders_status ORDER BY orders_status_id ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'order_substatuses' => [
			'qry' => 'SELECT * FROM orders_sub_status ORDER BY orders_sub_status_id ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'customer_permissions' => [
			'qry' => 'SELECT * FROM ck_customer_access_permissions WHERE active = 1 ORDER BY permission_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public function __construct($customers_id=NULL) {
		$this->_init();
		$this->build_dynamic_maps();
		if (!empty($customers_id)) $this->load('customers_id', $customers_id);
	}

	private function build_dynamic_maps() {
		$order_statuses = self::fetch('order_statuses', []);
		foreach ($order_statuses as $status) {
			$this->structure['orders']['key_format'][$status['orders_status_id']] = [];
		}

		$permissions = self::fetch('customer_permissions', []);
		foreach ($permissions as $permission) {
			$status = NULL;
			if ($permission['default_status'] == 'GRANTED') $status = TRUE;
			elseif ($permission['default_status'] == 'REVOKED') $status = FALSE;
			$this->structure['permissions']['format'][$permission['permission_name']] = $status;
		}
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'customers_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'customers_id' => NULL,
				'braintree_customer_id' => NULL,
				'customer_type' => NULL,
				'dealer' => NULL,
				'customer_price_level_id' => NULL,
				'customer_price_level' => NULL,
				'first_name' => NULL,
				'last_name' => NULL,
				'email_address' => NULL,
				'email_domain' => NULL,
				'default_address_id' => NULL,
				'send_late_notice' => NULL,
				'credit_limit' => NULL,
				'credit_status_id' => NULL,
				'credit_status' => NULL,
				'terms_id' => NULL,
				'legacy_dealer_pay_module' => NULL,
				'terms_label' => NULL,
				'terms_po_key' => NULL,
				'terms_days' => NULL,
				'terms_payment_method_id' => NULL,
				'own_shipping_account' => NULL,
				'fedex_account_number' => NULL,
				'ups_account_number' => NULL,
				'aim_screenname' => NULL,
				'msn_screenname' => NULL,
				'company_account_contact_name' => NULL,
				'company_account_contact_email' => NULL,
				'company_account_contact_phone_number' => NULL,
				'notes' => NULL,
				'sales_rep_notes' => NULL,
				'account_manager_id' => NULL,
				'sales_team_id' => NULL,
				'date_account_created' => NULL,
				'date_account_last_modified' => NULL,
				'date_last_logon' => NULL,
				'number_of_logons' => NULL,
				'referral_source' => NULL,
				'disable_standard_shipping' => NULL,
				'amazon_account' => NULL,
				'vendor_id' => NULL,
				'telephone' => NULL,
				'fax' => NULL,
				'newsletter_subscribed' => NULL,
				'customer_segment_id' => NULL,
				'segment' => NULL,
				'segment_code' => NULL,
				'fraud' => NULL,
				'has_received_mousepad' => NULL,
				'use_reclaimed_packaging' => NULL,
				'vp_lookup' => NULL
			]
		],
		'password_reset' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'customers_extra_logins_id' => NULL,
				'email' => NULL,
				'reset_code' => NULL,
				'reset_code_timestamp' => NULL,
				'reset_code_active' => NULL,
				'reset_code_link' => NULL,
			],
		],
		'summary' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'account_creation_date' => NULL,
				'first_order_id' => NULL,
				'first_order_booked_date' => NULL,
				'last_order_id' => NULL,
				'last_order_booked_date' => NULL,
				'lifetime_order_value' => NULL,
				'lifetime_order_count' => NULL,
				'pending_order_value' => NULL,
				'pending_order_count' => NULL,
			],
		],
		'tax_exemptions' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'customer_tax_exemption_id' => NULL,
				'countries_id' => NULL,
				'country_code' => NULL,
				'country_name' => NULL,
				'zone_id' => NULL,
				'state_code' => NULL,
				'state_name' => NULL,
				'tax_exempt' => NULL,
				'exemption_document' => NULL,
				'exemption_created' => NULL,
			],
		],
		'credit' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'credit_status_id' => NULL,
				'credit_status' => NULL,
				'credit_limit' => NULL,
			]
		],
		'terms' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'terms_id' => NULL,
				'legacy_dealer_pay_module' => NULL,
				'label' => NULL,
				'po_key' => NULL,
				'days' => NULL,
				'payment_method_id' => NULL,
			]
		],
		'credit_status_history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'credit_status_history_id' => NULL,
				'credit_status_id' => NULL,
				'credit_status' => NULL,
				'terms_id' => NULL,
				'terms' => NULL,
				'credit_limit' => NULL,
				'comments' => NULL,
				'admin_id' => NULL,
				'admin' => NULL,
				'status_date' => NULL,
			]
		],
		'account_manager' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'sales_team' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'accounting_contact' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'name' => NULL,
				'email' => NULL,
				'phone' => NULL
			]
		],
		'contacts' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'contact_id' => NULL,
				'email_address' => NULL,
				'contact_type_id' => NULL,
				'contact_type' => NULL
			]
		],
		'addresses' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'address' => NULL
			]
		],
		'extra_logins' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'customers_extra_logins_id' => NULL,
				'email_address' => NULL,
				'first_name' => NULL,
				'last_name' => NULL,
				'old_customers_id' => NULL,
				'active' => NULL,
				'rfq_free_ground_shipping' => NULL,
				'copy_account' => NULL
			]
		],
		'prices' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'stock_id' => NULL,
				'ipn' => NULL,
				'price' => NULL,
				'managed_category' => NULL
			]
		],
		'category_discounts' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'discount_id' => NULL,
				'categories_id' => NULL,
				'discount_pctg' => NULL,
				'status' => NULL,
				'start_date' => NULL,
				'end_date' => NULL
			]
		],
		'orders' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'order' => NULL,
			]
		],
		'order_count' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'orders_status_id' => NULL,
				'orders_id' => NULL
			]
		],
		'invoices' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'invoice' => NULL,
				/*'invoice_id' => NULL,
				'orders_id' => NULL,
				'rma_id' => NULL,
				'customers_extra_logins_id' => NULL,
				'invoice_date' => NULL,
				'po_number' => NULL,
				'paid_in_full' => NULL,
				'invoice_rma' => NULL,
				'credit_memo' => NULL,
				'credit_payment_id' => NULL,
				'original_invoice' => NULL,
				'late_notice_date' => NULL,
				'total' => NULL*/
			]
		],
		'payments' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'payment_id' => NULL,
				'payment_amount' => NULL,
				'payment_method_id' => NULL,
				'payment_method_label' => NULL,
				'payment_ref' => NULL,
				'payment_date' => NULL,
				'customers_extra_logins_id' => NULL,
				'applied_amount' => NULL,
				'unapplied_amount' => NULL,
				'note' => NULL,
				'applications' => []
			]
		],
		'unapplied_payments' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'payment_id' => NULL,
				'payment_amount' => NULL,
				'payment_method_id' => NULL,
				'payment_method_label' => NULL,
				'payment_ref' => NULL,
				'payment_date' => NULL,
				'customers_extra_logins_id' => NULL,
				'unapplied_amount' => NULL,
			]
		],
		'notifications' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'products_id' => NULL,
				'date_added' => NULL
			]
		],
		'permissions' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				// defined on init
			]
		]
	];
}
?>
