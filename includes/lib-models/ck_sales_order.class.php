<?php
class ck_sales_order extends ck_archetype {

	protected static $skeleton_type = 'ck_sales_order_type';

	/*private static $statuses_built = FALSE;
	private static $statuses = [];
	private static $substatuses_built = FALSE;
	private static $substatuses = [];*/

	const STATUS_RTP = 2;
	const STATUS_SHIPPED = 3;
	const STATUS_BACKORDER = 5;
	const STATUS_CANCELED = 6;
	const STATUS_WAREHOUSE = 7;
	//const STATUS_HOLD = 8;
	const STATUS_CST = 11;

	const STATUS_UNSHIP = 'UNSHIP';
	const STATUS_UNCANCEL = 'UNCANCEL';

	const ASTATUS_READY = 0;
	const ASTATUS_ALLOC_READY = 5;
	const ASTATUS_ALLOCATED = 10;
	const ASTATUS_OPPORTUNITY = 20;
	const ASTATUS_STOCK_ISSUE = 30;
	const ASTATUS_ERROR = -1;

	public static $sub_status_map = [
		'RTP' => [
			'Uncat' => 22,
			'Special Handling' => 23
		],
		'Warehouse' => [
			'Uncat' => 25,
			'Packing' => 24,
			'Picking' => 26
		],
		'CST' => [
			'Uncat' => 1,
			'Sourcing' => 2,
			'Credit Hold' => 3,
			'Waiting for Customer Response' => 4,
			'Waiting for Customer Pickup' => 5,
			'Dealers' => 8,
			'Fraud Check' => 9,
			'Work Order' => 11,
			'Pictures' => 12,
			'End User' => 13,
			'Paint Hold' => 15,
			'Sourcing-Express' => 17,
			'Accounting' => 20,
			'Drop Ship' => 21,
			'3PL' => 27
		]
	];

	public static $solutionsteam_id = 137;
	public static $ckcorporate_id = 118;

	private $delay_refiguring = FALSE;

	public function delay_refiguring() {
		$this->delay_refiguring = TRUE;
	}

	private function refiguring_delayed() {
		return $this->delay_refiguring;
	}

	protected static $queries = [
		'order_header' => [
			'qry' => 'SELECT o.*, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name, cel.customers_emailaddress as extra_logins_email_address, cel.customers_firstname as extra_logins_firstname, cel.customers_lastname as extra_logins_lastname, cel.copy_account as extra_logins_copy_account FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id LEFT JOIN customers_extra_logins cel ON o.customers_extra_logins_id = cel.customers_extra_logins_id WHERE o.orders_id = ?',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'order_header_list' => [
			'qry' => 'SELECT o.*, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name, cel.customers_emailaddress as extra_logins_email_address, cel.customers_firstname as extra_logins_firstname, cel.customers_lastname as extra_logins_lastname, cel.copy_account as extra_logins_copy_account FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id LEFT JOIN customers_extra_logins cel ON o.customers_extra_logins_id = cel.customers_extra_logins_id WHERE o.customers_id = :customers_id ORDER BY o.date_purchased DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'order_header_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT',

				'from' => 'FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id LEFT JOIN customers_extra_logins cel ON o.customers_extra_logins_id = cel.customers_extra_logins_id',

				'where' => 'WHERE', // will fail if we don't provide our own

				'order_by' => 'ORDER BY' // we provide a default below, if it's not otherwise provided
			],
			'proto_additional_table_fields' => [
				'display_total' => [
					'column' => 'ot.text as order_total',
					'from' => "LEFT JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total'",
					'element' => 'totals'
				],
				'customer_type' => [
					'column' => 'c.customer_type',
					'from' => 'JOIN customers c ON o.customers_id = c.customers_id',
					'element' => 'customer'
				],
				'customer_account_manager' => [
					'column' => 'c.account_manager_id',
					'form' => 'JOIN customers c ON o.customers_id = c.customers_id',
					'element' => 'customer'
				]
			],
			'proto_opts' => [
				':orders_id' => 'o.orders_id = :orders_id',
				':customers_id' => 'o.customers_id = :customers_id', // we use the provided data so we don't have to explicitly remove it, it should always be 1
				':orders_status_id' => 'o.orders_status = :orders_status_id',
				':orders_sub_status_id' => 'o.orders_sub_status = :orders_sub_status_id',
				':sales_team_id' => 'a.admin_groups_id = :sales_team_id',
			],
			'proto_defaults' => [
				'data_operation' => 'o.*, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name, cel.customers_emailaddress as extra_logins_email_address, cel.customers_firstname as extra_logins_firstname, cel.customers_lastname as extra_logins_lastname, cel.copy_account as extra_logins_copy_account',
				'order_by' => 's.sort_order ASC'
			],
			'proto_count_clause' => 'COUNT(o.orders_id)',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'channel_advisor_order' => [
			'qry' => 'SELECT orders_id FROM orders WHERE ca_order_id = :ca_order_id',
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'shipped_channel_advisor_orders' => [
			'qry' => "SELECT DISTINCT o.orders_id FROM orders o JOIN acc_invoices i ON o.orders_id = i.inv_order_id JOIN orders_packages op ON o.orders_id = op.orders_id AND op.void = 0 JOIN orders_tracking ot ON op.orders_packages_id = ot.orders_packages_id AND ot.void = 0 AND ot.tracking_num != '' LEFT JOIN shipping_methods sm ON ot.shipping_method_id = sm.shipping_code JOIN orders_status_history osh ON o.orders_id = osh.orders_id AND osh.orders_status_id = 3 WHERE o.ca_order_id IS NOT NULL AND o.distribution_center_id = 0 AND IFNULL(o.ca_shipping_export_status, 0) = 0",
			//'qry' => "SELECT DISTINCT o.orders_id, o.ca_order_id, i.inv_date, ot.tracking_num, sm.shipping_code as shipping_method_id, CASE WHEN sm.carrier = '' THEN 'UPS' ELSE sm.carrier END as carrier, CASE WHEN sm.shipping_code = 48 THEN 'GROUND' WHEN sm.shipping_code = 3 THEN 'OVERNIGHT' WHEN sm.shipping_code = 43 THEN 'IPRIORITY' ELSE sm.name END as shipping_code FROM orders o JOIN acc_invoices i ON o.orders_id = i.inv_order_id JOIN orders_packages op ON o.orders_id = op.orders_id AND op.void = 0 JOIN orders_tracking ot ON op.orders_packages_id = ot.orders_packages_id AND ot.tracking_num != '' JOIN shipping_methods sm ON ot.shipping_method_id = sm.shipping_code WHERE o.ca_order_id IS NOT NULL AND IFNULL(o.ca_shipping_export_status, 0) = 0",
			'cardinality' => cardinality::COLUMN,
			'stmt' => NULL
		],

		'totals' => [
			'qry' => 'SELECT * FROM orders_total WHERE orders_id = ? ORDER BY sort_order ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'shipping_method' => [
			'qry' => 'SELECT shipping_code, name as method_name, carrier, description, original_code, abol_code, worldship_code, inactive, saturday_delivery, transit_days FROM shipping_methods WHERE shipping_code = ?',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'products' => [
			'qry' => 'SELECT op.orders_products_id, op.products_id, op.parent_products_id, op.option_type, op.products_model as model, op.products_name as name, op.products_price as revenue, op.final_price, op.display_price, op.price_reason, op.products_tax, op.products_quantity as quantity, op.cost, op.expected_ship_date, op.is_quote, op.exclude_forecast, op.vendor_stock_item, op.specials_excess, p.stock_id FROM orders_products op LEFT JOIN products p ON op.products_id = p.products_id WHERE op.orders_id = ? ORDER BY CASE WHEN op.option_type = 0 THEN op.products_id ELSE op.parent_products_id END ASC, op.orders_products_id ASC',
			'cardinality' => cardinality::SET,
		],

		'po_allocations' => [
			'qry' => 'SELECT potoa.id as allocation_id, op.orders_products_id, psc.stock_name as ipn, po.id as po_id, po.purchase_order_number, potoa.quantity, po.expected_date FROM orders_products op JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id JOIN purchase_order_to_order_allocations potoa ON op.orders_products_id = potoa.order_product_id JOIN purchase_order_products pop ON potoa.purchase_order_product_id = pop.id JOIN purchase_orders po ON pop.purchase_order_id = po.id WHERE op.orders_id = :orders_id ORDER BY po.expected_date DESC',
			'cardinality' => cardinality::SET
		],

		'freight_lines' => [
			'qry' => 'SELECT freight_shipment_id, list_total, residential, liftgate, inside, limitaccess, entered FROM ck_freight_shipment WHERE orders_id = :orders_id',
			'cardinality' => cardinality::ROW
		],

		'freight_items' => [
			'qry' => 'SELECT fsi.freight_shipment_item_id, fsi.location_vendor_id, fsi.products_id, fsi.entered, v.vendors_company_name, psc.stock_name as ipn FROM ck_freight_shipment_items fsi LEFT JOIN vendors v ON fsi.location_vendor_id = v.vendors_id JOIN products p ON fsi.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE fsi.freight_shipment_id = :freight_shipment_id ORDER BY fsi.location_vendor_id ASC, ipn ASC',
			'cardinality' => cardinality::SET
		],

		'status_history' => [
			'qry' => 'SELECT osh.orders_status_history_id, osh.admin_id, osh.orders_status_id as orders_status_code, os.orders_status_name as orders_status, osh.orders_sub_status_id as orders_substatus_code, oss.orders_sub_status_name as orders_substatus, osh.date_added as status_date, osh.customer_notified, osh.comments FROM orders_status_history osh LEFT JOIN orders_status os ON osh.orders_status_id = os.orders_status_id LEFT JOIN orders_sub_status oss ON osh.orders_sub_status_id = oss.orders_sub_status_id WHERE osh.orders_id = ? ORDER BY osh.date_added ASC',
			'cardinality' => cardinality::SET,
		],

		'notes' => [
			'qry' => 'SELECT n.orders_note_id, a.admin_id, a.admin_firstname as admin_first_name, a.admin_lastname as admin_last_name, a.admin_email_address as admin_email, n.orders_note_text as note_text, n.orders_note_created as note_date, n.orders_note_modified as modified_date, n.orders_note_deleted as deleted, n.shipping_notice FROM orders_notes n LEFT JOIN admin a ON n.orders_note_user = a.admin_id WHERE n.orders_id = ? AND n.orders_note_deleted = 0 ORDER BY n.orders_note_id ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'acc_order_notes' => [
			'qry' => 'SELECT aon.id, aon.text, a.admin_firstname, a.admin_lastname FROM acc_order_notes aon LEFT JOIN admin a ON aon.creator_id = a.admin_id WHERE order_id = ?',
			'cardinality' => cardinality::SET,
		],

		'packages' => [
			'qry' => 'SELECT op.orders_packages_id, op.order_product_id as orders_products_id, op.package_type_id, pt.package_name, pt.logoed, op.order_package_length as package_length, op.order_package_width as package_width, op.order_package_height as package_height, op.scale_weight, op.void, op.date_time_created, op.date_time_voided, ot.tracking_num, ot.shipping_method_id, ot.cost, ot.void as tracking_void, ot.date_time_created as tracking_date_created, ot.date_time_voided as tracking_date_voided, sm.carrier, sm.narvar_code FROM orders_packages op LEFT JOIN package_type pt ON op.package_type_id = pt.package_type_id LEFT JOIN orders_tracking ot ON op.orders_packages_id = ot.orders_packages_id AND ot.void = 0 LEFT JOIN shipping_methods sm ON ot.shipping_method_id = sm.shipping_code WHERE op.orders_id = ?',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'fraud_score' => [
			'qry' => 'SELECT score, proxy_score, spam_score, err, query, country_match, country_code, hi_risk, bin_match, bin_country, bin_name, ip_isp, ip_org, distance, anonymous_proxy, free_mail, cust_phone, ip_city, ip_region, ip_latitude, ip_longitude FROM orders_maxmind WHERE order_id = ?',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'payments' => [
			'qry' => 'SELECT apto.id as pmt_applied_id, apto.amount as pmt_applied_amount, ap.payment_id, ap.payment_amount, ap.payment_ref, ap.payment_date, ap.payment_method_id, pm.code as payment_method_code, pm.label as payment_method_label FROM acc_payments_to_orders apto JOIN acc_payments ap ON apto.payment_id = ap.payment_id LEFT JOIN payment_method pm ON ap.payment_method_id = pm.id WHERE apto.order_id = ?',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'invoices' => [
			'qry' => "SELECT ai.invoice_id, ai.customer_id, ai.customers_extra_logins_id, ai.inv_date as invoice_date, ai.po_number, ai.paid_in_full, ai.credit_memo, ai.original_invoice, ai.late_notice_date, ait.invoice_total_price as total FROM acc_invoices ai LEFT JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_total' WHERE inv_order_id = :orders_id ORDER BY ai.invoice_id DESC",
			'cardinality' => cardinality::SET,
		],

		'invoice_products' => [
			'qry' => 'SELECT invoice_item_id, ipn_id as stock_id, orders_product_id as orders_products_id, rma_product_id, invoice_item_price, invoice_item_qty, orders_product_cost, orders_product_cost_total, serialized FROM acc_invoice_items WHERE invoice_id = :invoice_id',
			'cardinality' => cardinality::SET,
		],

		'invoice_totals' => [
			'qry' => 'SELECT invoice_total_id, invoice_total_line_type, invoice_total_description, invoice_total_price, invoice_total_date FROM acc_invoice_totals WHERE invoice_id = :invoice_id',
			'cardinality' => cardinality::SET,
		],

		'invoice_allocated_payments' => [
			'qry' => 'SELECT payment_to_invoice_id, payment_id, credit_amount, credit_date FROM acc_payments_to_invoices WHERE invoice_id = :invoice_id',
			'cardinality' => cardinality::SET,
		],

		'rmas' => [
			'qry' => 'SELECT id FROM rma WHERE order_id = ?',
			'cardinality' => cardinality::COLUMN
		],

		'allocated_serials' => [
			'qry' => 'SELECT DISTINCT s.id as serial_id, s.serial as serial_number, s.status as status_code, ss.name as status, s.ipn as stock_id, sh.id as serial_history_id, sh.entered_date, sh.shipped_date, sh.conditions as condition_code, sc.text as `condition`, sh.order_id as orders_id, sh.order_product_id as orders_products_id, sh.po_number, sh.pors_id as purchase_order_receiving_sessions_id, sh.pop_id as purchase_order_products_id, sh.porp_id as purchase_order_received_products_id, sh.dram, sh.flash, sh.image, sh.ios, sh.cost, sh.transfer_price, sh.transfer_date, sh.show_version, sh.short_notes, sh.bin_location, sh.confirmation_date, sh.rma_id FROM serials s JOIN serials_status ss ON s.status = ss.id JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_configs sc ON sh.conditions = sc.id WHERE sh.order_id = ?',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'child_orders' => [
			'qry' => 'SELECT orders_id FROM orders WHERE parent_orders_id = :orders_id',
			'cardinality' => cardinality::COLUMN,
			'stmt' => NULL
		],

		'update_order_status' => [
			'qry' => 'UPDATE orders SET orders_status = :orders_status_id, orders_sub_status = :orders_sub_status_id WHERE orders_id = :orders_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'create_sales_order_proto_raw' => [
			'built' => FALSE,
			'proto_qry' => [
				'data_operation' => 'INSERT INTO orders',
				'values' => 'VALUES'
			],
			'proto_opts' => [
			],
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'recipients' => [
			'qry' => 'SELECT orders_notification_recipient_id, name, email FROM orders_notification_recipients WHERE orders_id = :orders_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public function __construct($orders_id, ck_sales_order_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($orders_id);

		if (!$this->skeleton->built('orders_id')) $this->skeleton->load('orders_id', $orders_id);
		if ($this->skeleton->built('header')) $this->normalize_header();

		self::register($orders_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('orders_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('order_header', [$this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		if (!empty($header)) {
			$date_fields = ['last_modified', 'date_purchased', 'orders_date_finished', 'followup_date', 'promised_ship_date'];
			foreach ($date_fields as $field) {
				$header[$field] = ck_datetime::datify($header[$field]);
			}

			$bool_fields = ['released', 'dropship', 'packing_slip', 'dsm', 'legacy_order'];
			foreach ($bool_fields as $field) {
				$header[$field] = CK\fn::check_flag($header[$field]);
			}

			// Tax managment resides in Avalara as of Nov 1 2019
			// if (!isset($header['tax_exempt_raw'])) $header['tax_exempt_raw'] = $header['tax_exempt'];
			//
			// if (is_numeric($header['tax_exempt_raw'])) {
			// 	if (CK\fn::check_flag($header['tax_exempt_raw'])) $header['tax_exempt'] = TRUE;
			// 	else $header['tax_exempt'] = FALSE;
			//
			// 	$header['tax_status_reason'] = 'order-override';
			// }
			// elseif (!self::is_ck_tax_liable($header['delivery_state'], $header['delivery_country'])) {
			// 	$header['tax_exempt'] = TRUE;
			// 	$header['tax_status_reason'] = 'non-liable';
			// }
			// else {
			// 	$customer = new ck_customer2($header['customers_id']);
			// 	if ($customer->is_tax_exempt_in($header['delivery_state'], $header['delivery_country'])) {
			// 		$header['tax_exempt'] = TRUE;
			// 		$header['tax_status_reason'] = 'customer-exempt';
			// 	}
			// 	else $header['tax_exempt'] = FALSE;
			// }
		}

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('order_header', [$this->id()]));
		$this->normalize_header();
	}

	private function build_service_rep() {
		if (!empty($this->get_header('admin_id'))) {
			$this->skeleton->load('service_rep', new ck_admin($this->get_header('admin_id')));
		}
		else $this->skeleton->load('service_rep', NULL);
	}

	private function build_account_manager() {
		if (!empty($this->get_header('orders_sales_rep_id'))) {
			$this->skeleton->load('account_manager', new ck_admin($this->get_header('orders_sales_rep_id')));
		}
		else $this->skeleton->load('account_manager', NULL);
	}

	private function build_sales_team() {
		if (!empty($this->get_header('sales_team_id'))) {
			$this->skeleton->load('sales_team', new ck_team($this->get_header('sales_team_id')));
		}
		else $this->skeleton->load('sales_team', NULL);
	}

	private function build_customer() {
		$this->skeleton->load('customer', new ck_customer2($this->get_header('customers_id')));
	}

	private function build_extra_login() {
		// because load() ignores keys that don't fit the format of the target type, we can
		// load the whole header in since that's where the data is coming from
		$this->skeleton->load('extra_login', $this->skeleton->get('header'));
	}

	private function build_address() {
		$header = $this->get_header();
		$this->skeleton->load('address', [
			'name' => $header['customers_name'],
			'company' => $header['customers_company'],
			'street_address_1' => $header['customers_street_address'],
			'street_address_2' => $header['customers_suburb'],
			'city' => $header['customers_city'],
			'zip' => $header['customers_postcode'],
			'state' => $header['customers_state'],
			'country' => $header['customers_country'],
			'phone' => $header['customers_telephone'],
			'address_format_id' => $header['customers_address_format_id']
		]);
	}

	private function build_billing_address() {
		$header = $this->get_header();
		$this->skeleton->load('billing_address', [
			'name' => $header['billing_name'],
			'company' => $header['billing_company'],
			'street_address_1' => $header['billing_street_address'],
			'street_address_2' => $header['billing_suburb'],
			'city' => $header['billing_city'],
			'zip' => $header['billing_postcode'],
			'state' => $header['billing_state'],
			'country' => $header['billing_country'],
			'address_format_id' => $header['billing_address_format_id']
		]);
	}

	private function build_shipping_address() {
		$header = $this->get_header();
		$this->skeleton->load('shipping_address', [
			'name' => $header['delivery_name'],
			'company' => $header['delivery_company'],
			'street_address_1' => $header['delivery_street_address'],
			'street_address_2' => $header['delivery_suburb'],
			'city' => $header['delivery_city'],
			'zip' => $header['delivery_postcode'],
			'state' => $header['delivery_state'],
			'country' => $header['delivery_country'],
			'phone' => $header['delivery_telephone'],
			'address_format_id' => $header['delivery_address_format_id']
		]);
	}

	private function build_addr() {
		$header = $this->get_header();

		$address_type = new ck_address_type();
		$address_type->load('header', [
			'customers_id' => $header['customers_id'],
			'first_name' => $header['customers_name'],
			'company_name' => $header['customers_company'],
			'address1' => $header['customers_street_address'],
			'address2' => $header['customers_suburb'],
			'city' => $header['customers_city'],
			'postcode' => $header['customers_postcode'],
			'state' => $header['customers_state'],
			'country' => $header['customers_country'],
			'telephone' => $header['customers_telephone'],
			'address_format_id' => $header['customers_address_format_id']
		]);
		$address = new ck_address2(NULL, $address_type);

		$this->skeleton->load('addr', $address);
	}

	// bill/ship address are the address objects - rather than take over legacy billing/shipping address, just create new data for backwards compatibility - we'll switch over eventually
	private function build_bill_address() {
		$header = $this->get_header();

		$country = ck_address2::get_country($header['billing_country']);
		$zone = ck_address2::get_zone($header['billing_state'], @$country['countries_id']);

		$address_type = new ck_address_type();
		$address_type->load('header', [
			'customers_id' => $header['customers_id'],
			'first_name' => $header['billing_name'],
			'company_name' => $header['billing_company'],
			'address1' => $header['billing_street_address'],
			'address2' => $header['billing_suburb'],
			'city' => $header['billing_city'],
			'postcode' => $header['billing_postcode'],
			'zone_id' => @$zone['zone_id'],
			'state_region_code' => @$zone['zone_code'],
			'state_region_name' => @$zone['zone_name'],
			'state' => $header['billing_state'],
			'countries_id' => @$country['countries_id'],
			'country' => $header['billing_country'],
			'countries_iso_code_2' => @$country['countries_iso_code_2'],
			'countries_iso_code_3' => @$country['countries_iso_code_3'],
			'country_address_format_id' => $header['billing_address_format_id']
		]);
		$address = new ck_address2(NULL, $address_type);

		$this->skeleton->load('bill_address', $address);
	}

	private function build_ship_address() {
		$header = $this->get_header();

		$country = ck_address2::get_country($header['delivery_country']);
		$zone = ck_address2::get_zone($header['delivery_state'], @$country['countries_id']);

		$address_type = new ck_address_type();
		$address_type->load('header', [
			'customers_id' => $header['customers_id'],
			'first_name' => $header['delivery_name'],
			'company_name' => $header['delivery_company'],
			'address1' => $header['delivery_street_address'],
			'address2' => $header['delivery_suburb'],
			'city' => $header['delivery_city'],
			'postcode' => $header['delivery_postcode'],
			'zone_id' => @$zone['zone_id'],
			'state_region_code' => @$zone['zone_code'],
			'state_region_name' => @$zone['zone_name'],
			'state' => $header['delivery_state'],
			'countries_id' => @$country['countries_id'],
			'country' => $header['delivery_country'],
			'countries_iso_code_2' => @$country['countries_iso_code_2'],
			'countries_iso_code_3' => @$country['countries_iso_code_3'],
			'country_address_format_id' => $header['delivery_address_format_id'],
			'telephone' => $header['delivery_telephone'],
		]);
		$address = new ck_address2(NULL, $address_type);

		$this->skeleton->load('ship_address', $address);
	}

	private function build_totals() {
		$totals = $this->skeleton->key_format('totals'); // the same template works for both totals and totals detail
		$simple_totals = $totals;
		$simple_totals = array_map(function($val) { return 0; }, $simple_totals);

		$order_totals = self::fetch('totals', [$this->id()]);

		foreach ($order_totals as $total) {
			$ttl = ['orders_total_id' => $total['orders_total_id'], 'title' => $total['title'], 'display_value' => $total['text'], 'value' => $total['value']];
			switch ($total['class']) {
				case 'ot_coupon':
					$class = $ttl['class'] = 'coupon';
					$totals['coupon'][] = $ttl;
					$totals['consolidated'][] = $ttl;
					break;
				case 'ot_custom':
					$class = $ttl['class'] = 'custom';
					$totals['custom'][] = $ttl;
					$totals['consolidated'][] = $ttl;
					break;
				case 'ot_shipping':
					$class = $ttl['class'] = 'shipping';
					$ttl['actual_shipping_cost'] = $total['actual_shipping_cost'];
					$ttl['shipping_method_id'] = $total['external_id'];
					$totals['shipping'][] = $ttl;
					$totals['consolidated'][] = $ttl;

					if ($total['external_id'] > 0) $this->skeleton->load('shipping_method_id', $total['external_id']);
					break;
				case 'ot_tax':
					$class = $ttl['class'] = 'tax';
					$totals['tax'][] = $ttl;
					$totals['consolidated'][] = $ttl;
					break;
				case 'ot_total':
					$class = $ttl['class'] = 'total';
					$totals['total'][] = $ttl;
					$totals['consolidated'][] = $ttl;
					break;
				default:
					$class = 'other';
					$ttl['class'] = $total['class'];
					$totals['other'][] = $ttl;
					$totals['consolidated'][] = $ttl;
					break;
			}

			if (empty($simple_totals[$class])) $simple_totals[$class] = 0;
			$simple_totals[$class] += $ttl['value'];
		}

		$this->skeleton->load('totals', $totals);
		$this->skeleton->load('simple_totals', $simple_totals);
	}

	private function build_estimated_costs() {
		$products = $this->get_products();

		$product_total = ['price' => 0, 'cost' => 0];
		$coupon_total = ['price' => 0, 'cost' => 0];
		$shipping_total = ['price' => 0, 'cost' => 0];
		$incalculable = FALSE;
		$incalculable_reason = [];

		foreach ($products as $product) {
			$product_total['price'] += $product['final_price'] * $product['quantity'];

			if ($product['ipn']->is('serialized')) {
				$qty = $product['quantity'];

				// count the cost that is already associated directly with this order
				foreach ($product['allocated_serials'] as $serial) {
					$qty--;
					$product_total['cost'] += $serial->get_current_history()['cost']; // when we build products, we only grab the history record associated with this order
				}

				// if there are serialized IPNs that have a qty that is as yet unallocated to this order, try and figure it from available stock
				if ($qty > 0) {
					// custom query it because for GLCs in particular it takes forever - the custom query brings this down from 9 seconds to <1 second.
					// we don't want allocated serials (status 3) because we've already counted serials allocated to this order, so those would be allocated to a different order
					$instock_serials = prepared_query::fetch('SELECT sh.serial_id, sh.cost, sh.entered_date FROM serials s JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id WHERE sh0.id IS NULL AND s.ipn = :stock_id AND s.status IN (2, 6) ORDER BY sh.cost DESC, sh.entered_date ASC', cardinality::SET, [':stock_id' => $product['stock_id']]);
					if (!empty($instock_serials)) {
						foreach ($instock_serials as $serial) {
							$product_total['cost'] += $serial['cost'];
							$qty--;
							if ($qty == 0) break;
						}
					}

					// we don't have enough unallocated serials in stock to cover the remaining required serials
					if (count($instock_serials) < $qty) {
						$incalculable = TRUE;
						$incalculable_reason[] = 'SOME UNKNOWN COSTS';
					}
				}

				// we've somehow allocated more serials than are required for this order
				if ($qty < 0) {
					$incalculable = TRUE;
					$incalculable_reason[] = 'SERIAL ALLOCATION ERROR';
				}
			}
			else {
				$product_total['cost'] += $product['ipn']->get_header('average_cost') * $product['quantity'];
			}

			// we don't have enough in the building to satisfy this order
			if ($product['ipn']->get_inventory('on_hand') < $product['quantity']) {
				$incalculable = TRUE;
				$incalculable_reason[] = 'SOME UNKNOWN COSTS';
			}
		}

		if ($coupons = $this->get_totals('coupon')) {
			foreach ($coupons as $coupon) {
				$coupon_total['price'] += abs($coupon['value']);
			}
		}

		if ($product_total['price'] - $coupon_total['price'] == 0) {
			$incalculable = TRUE;
			$incalculable_reason[] = 'ZERO PRODUCT TOTAL (PRODUCTS + COUPONS)';
		}

		$shipping_total['price'] += $this->get_simple_totals('shipping');

		$this->skeleton->load('estimated_costs', ['product_total' => $product_total, 'coupon_total' => $coupon_total, 'shipping_total' => $shipping_total, 'incalculable' => $incalculable, 'incalculable_reason' => $incalculable_reason]);
	}

	private function build_final_costs() {
		if ($this->has_invoices() && $invoice = $this->get_latest_invoice()) {
			$products = prepared_query::fetch('SELECT invoice_item_id, ipn_id, orders_product_id, invoice_item_price, invoice_item_qty, orders_product_cost, orders_product_cost_total FROM acc_invoice_items WHERE invoice_id = :invoice_id', cardinality::SET, [':invoice_id' => $invoice['invoice_id']]);

			$totals = prepared_query::fetch('SELECT invoice_total_id, invoice_total_line_type, invoice_total_price FROM acc_invoice_totals WHERE invoice_id = :invoice_id', cardinality::SET, [':invoice_id' => $invoice['invoice_id']]);

			$product_total = array_reduce($products, function($product_total, $product) {
				$product_total['price'] += $product['invoice_item_price'] * $product['invoice_item_qty'];
				$product_total['cost'] += $product['orders_product_cost_total'];
				return $product_total;
			}, ['price' => 0, 'cost' => 0]);

			$coupon_total = array_reduce($totals, function($coupon_total, $total) {
				if ($total['invoice_total_line_type'] == 'ot_coupon') $coupon_total['price'] += abs($total['invoice_total_price']);
				return $coupon_total;
			}, ['price' => 0, 'cost' => 0]);

			$shipping_total = array_reduce($totals, function($shipping_total, $total) {
				if ($total['invoice_total_line_type'] == 'ot_shipping') $shipping_total['price'] += $total['invoice_total_price'];
				return $shipping_total;
			}, ['price' => 0, 'cost' => 0]);

			if ($product_total['price'] - $coupon_total['price'] == 0) {
				$incalculable = TRUE;
				$incalculable_reason = ['ZERO PRODUCT TOTAL (PRODUCTS + COUPONS)'];
			}
			else {
				$incalculable = FALSE;
				$incalculable_reason = [];
			}

			$this->skeleton->load('final_costs', ['product_total' => $product_total, 'coupon_total' => $coupon_total, 'shipping_total' => $shipping_total, 'incalculable' => $incalculable, 'incalculable_reason' => $incalculable_reason]);
		}
		else $this->skeleton->load('final_costs', ['product_total' => ['price' => 0, 'cost' => 0], 'coupon_total' => ['price' => 0, 'cost' => 0], 'shipping_total' => ['price' => 0, 'cost' => 0], 'incalculable' => TRUE, 'incalculable_reason' => ['ORDER IS NOT YET INVOICED']]);
	}

	private function build_shipping_method() {
		// shipping method is attached through totals, so we need to grab those first if we haven't already
		if (!$this->skeleton->built('shipping_method_id') && !$this->skeleton->built('totals')) $this->build_totals();
		if (!$this->skeleton->built('shipping_method_id')) return $this->skeleton->load('shipping_method', []); //throw new Exception('Cannot build shipping method without shipping method ID');

		$this->skeleton->load('shipping_method', self::fetch('shipping_method', [$this->skeleton->get('shipping_method_id')]));
	}

	private function build_products() {
		$order_products = self::fetch('products', [$this->id()]);

		$allocated_serials = [];
		if ($serials = self::fetch('allocated_serials', [$this->id()])) {
			foreach ($serials as $ser) {
				// we build the type separately because we *only* want the history record that is associated with this order, not all history records
				$serial_data = new ck_serial_type($ser['serial_id']);
				$serial_data->load('header', $ser);
				$serial_data->load('history', [$ser]);

				$serial = new ck_serial($ser['serial_id'], $serial_data);
				$orders_products_id = $ser['orders_products_id'];

				if (empty($allocated_serials[$orders_products_id])) $allocated_serials[$orders_products_id] = [];
				$allocated_serials[$orders_products_id][] = $serial;
			}
		}

		$po_allocations = [];
		if ($this->has_po_allocations()) {
			foreach ($this->get_po_allocations() as $alloc) {
				if (empty($po_allocations[$alloc['orders_products_id']])) $po_allocations[$alloc['orders_products_id']] = [];
				$po_allocations[$alloc['orders_products_id']][] = $alloc;
			}
		}

		$is_stock_shippable = TRUE;
		$availability_status = self::ASTATUS_ERROR;

		ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);
		ck_ipn2::load_ipn_set(array_map(function($prod) { return $prod['stock_id']; }, $order_products));

		$in_context_demand = [];

		// orders products probably deserve their own class, but for now this will do
		foreach ($order_products as &$product) {
			$product['listing'] = new ck_product_listing($product['products_id']);
			$product['ipn'] = new ck_ipn2($product['stock_id']);

			$product['allocated_serials'] = !empty($allocated_serials[$product['orders_products_id']])?$allocated_serials[$product['orders_products_id']]:[];

			$product['quantity'] = (int) $product['quantity'];
			$product['expected_ship_date'] = !self::date_is_empty($product['expected_ship_date'])?self::DateTime($product['expected_ship_date']):NULL;
			$product['is_quote'] = CK\fn::check_flag($product['is_quote']);
			$product['exclude_forecast'] = CK\fn::check_flag($product['exclude_forecast']);
			$product['vendor_stock_item'] = CK\fn::check_flag($product['vendor_stock_item']);
			$product['specials_excess'] = (int) $product['specials_excess'];
			$product['is_shippable'] = TRUE;

			$product['po_allocations'] = !empty($po_allocations[$product['orders_products_id']])?$po_allocations[$product['orders_products_id']]:[];

			$total_allocated = array_reduce($product['po_allocations'], function ($total, $allocation) {
				$total += $allocation['quantity'];
				return $total;
			}, 0);

			$inventory = $product['ipn']->get_inventory();

			if (empty($in_context_demand[$product['ipn']->id()])) $in_context_demand[$product['ipn']->id()] = 0;
			$in_context_demand[$product['ipn']->id()] += $product['quantity'];

			if ($inventory['on_hand'] - $inventory['on_hold'] < $in_context_demand[$product['ipn']->id()] && !$this->is_shipped() && !$product['ipn']->is('is_bundle')) {
				$product['is_shippable'] = FALSE;
				$is_stock_shippable = FALSE;
			}

			if ($product['ipn']->is('is_bundle') || ($inventory['available'] >= 0 && $total_allocated <= 0)) {
				$product['availability_status'] = self::ASTATUS_READY;
				$availability_status = max($availability_status, self::ASTATUS_READY);
			}
			elseif ($inventory['available'] >= $in_context_demand[$product['ipn']->id()]) {
				$product['availability_status'] = self::ASTATUS_ALLOC_READY;
				$availability_status = max($availability_status, self::ASTATUS_ALLOC_READY);
			}
			elseif ($in_context_demand[$product['ipn']->id()] <= $total_allocated) {
				$product['availability_status'] = self::ASTATUS_ALLOCATED;
				$availability_status = max($availability_status, self::ASTATUS_ALLOCATED);
			}
			elseif ($inventory['salable'] >= $in_context_demand[$product['ipn']->id()]) {
				$product['availability_status'] = self::ASTATUS_OPPORTUNITY;
				$availability_status = max($availability_status, self::ASTATUS_OPPORTUNITY);
			}
			else {
				$product['availability_status'] = self::ASTATUS_STOCK_ISSUE;
				$availability_status = max($availability_status, self::ASTATUS_STOCK_ISSUE);
			}
		}

		usort($order_products, function($a, $b) {
			if ($a['ipn']->is_supplies() == $b['ipn']->is_supplies()) {
				$a_products_id = empty($a['parent_products_id'])?$a['products_id']:$a['parent_products_id'];
				$b_products_id = empty($b['parent_products_id'])?$b['products_id']:$b['parent_products_id'];

				if ($a_products_id == $b_products_id) {
					if ($a['option_type'] != $b['option_type']) {
						if ($a['option_type'] == ck_cart::$option_types['NONE']) return -1;
						elseif ($b['option_type'] == ck_cart::$option_types['NONE']) return 1;
						elseif ($a['option_type'] == ck_cart::$option_types['INCLUDED']) return -1;
						elseif ($b['option_type'] == ck_cart::$option_types['INCLUDED']) return 1;
						else return 0;
					}
					else {
						if ($a['orders_products_id'] == $b['orders_products_id']) return 0;
						else return $a['orders_products_id']<$b['orders_products_id']?-1:1;
					}
				}
				else return $a_products_id<$b_products_id?-1:1;
			}
			elseif ($a['ipn']->is_supplies()) return 1;
			elseif ($b['ipn']->is_supplies()) return -1;
		});

		$this->skeleton->load('products', $order_products);
		$this->skeleton->load('is_stock_shippable', $is_stock_shippable);
		$this->skeleton->load('availability_status', $availability_status);
	}

	private function build_po_allocations() {
		$po_allocations = self::fetch('po_allocations', [':orders_id' => $this->id()]);

		foreach ($po_allocations as &$allocation) {
			$allocation['expected_date'] = !self::date_is_empty($allocation['expected_date'])?self::DateTime($allocation['expected_date']):NULL;
		}

		$this->skeleton->load('po_allocations', $po_allocations);
	}

	private function build_freight_lines() {
		$freight_lines = self::fetch('freight_lines', [':orders_id' => $this->id()]); // row

		if ($freight_lines) {
			$freight_lines['residential'] = CK\fn::check_flag($freight_lines['residential']);
			$freight_lines['liftgate'] = CK\fn::check_flag($freight_lines['liftgate']);
			$freight_lines['inside'] = CK\fn::check_flag($freight_lines['inside']);
			$freight_lines['limitaccess'] = CK\fn::check_flag($freight_lines['limitaccess']);

			$freight_lines['entered'] = self::DateTime($freight_lines['entered']);

			$freight_items = self::fetch('freight_items', [':freight_shipment_id' => $freight_lines['freight_shipment_id']]); // set
			foreach ($freight_items as &$item) {
				$item['entered'] = self::DateTime($item['entered']);
			}

			$freight_lines['products'] = $freight_items;
		}

		$this->skeleton->load('freight_lines', $freight_lines);
	}

	private function build_status_history() {
		$histories = self::fetch('status_history', [$this->id()]);

		foreach ($histories as &$entry) {
			if (!empty($entry['admin_id'])) $entry['admin'] = new ck_admin($entry['admin_id']);
			$entry['status_date'] = self::DateTime($entry['status_date']);
			$entry['customer_notified'] = CK\fn::check_flag($entry['customer_notified']);
		}

		$this->skeleton->load('status_history', $histories);
	}

	private function build_acc_order_notes() {
		$notes = self::fetch('acc_order_notes', [$this->id()]);
		$this->skeleton->load('acc_order_notes', $notes);
	}

	private function build_notes() {
		$notes = self::fetch('notes', [$this->id()]);

		foreach ($notes as &$note) {
			$note['shipping_notice'] = CK\fn::check_flag($note['shipping_notice']);
			$note['note_date'] = self::DateTime($note['note_date']);
			$note['modified_date'] = self::DateTime($note['modified_date']);
		}

		$this->skeleton->load('notes', $notes);
	}

	private function build_packages() {
		$this->skeleton->load('packages', self::fetch('packages', [$this->id()]));
	}

	private function build_fraud_score() {
		$fraud_score = self::fetch('fraud_score', [$this->id()]);
		if (empty($fraud_score)) $fraud_score = [];
		$this->skeleton->load('fraud_score', $fraud_score);
	}

	private function build_payments() {
		$this->skeleton->load('payments', self::fetch('payments', [$this->id()]));
	}

	private function build_invoices() {
		// this is just a stub in place of a fuller invoice class
		$invoices = self::fetch('invoices', [':orders_id' => $this->id()]);

		foreach ($invoices as &$invoice) {
			$invoice['invoice_date'] = self::DateTime($invoice['invoice_date']);
			$invoice['paid_in_full'] = CK\fn::check_flag($invoice['paid_in_full']);
			$invoice['credit_memo'] = CK\fn::check_flag($invoice['credit_memo']);

			$invoice['products'] = self::fetch('invoice_products', [':invoice_id' => $invoice['invoice_id']]);
			$invoice['totals'] = self::fetch('invoice_totals', [':invoice_id' => $invoice['invoice_id']]);
			$invoice['allocated_payments'] = self::fetch('invoice_allocated_payments', [':invoice_id' => $invoice['invoice_id']]);

			foreach ($invoice['products'] as &$product) {
				$product['ipn'] = new ck_ipn2($product['stock_id']);
			}
		}

		$this->skeleton->load('invoices', $invoices);
	}

	private function build_rmas() {
		// this is just a stub in place of a fuller rma class
		$rma_ids = self::fetch('rmas', [$this->id()]);

		$rmas = [];

		if (!empty($rma_ids)) {
			$rmas = array_map(function($rma_id) { return new ck_rma2($rma_id); }, $rma_ids);
		}

		$this->skeleton->load('rmas', $rmas);
	}

	private function build_child_orders() {
		$child_orders = self::fetch('child_orders', [':orders_id' => $this->id()]);

		$orders = [];

		foreach ($child_orders as $orders_id) {
			$orders[] = new self($orders_id);
		}

		$this->skeleton->load('child_orders', $orders);
	}

	private function build_recipients() {
		$this->skeleton->load('recipients', self::fetch('recipients', [':orders_id' => $this->id()]));
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public static function is_ck_tax_liable($state, $country) {
		$country = ck_address2::get_country($country);

		if ($country['countries_id'] != ck_address2::COUNTRY_ID_USA) return FALSE;

		$state = ck_address2::get_zone($state, $country['countries_id']);

		$ck_tax_liability = prepared_query::fetch('SELECT sales_tax_liable FROM ck_sales_tax_liabilities WHERE countries_id = :countries_id AND zone_id = :zone_id', cardinality::SINGLE, [':countries_id' => $country['countries_id'], ':zone_id' => $state['zone_id']]);

		return CK\fn::check_flag($ck_tax_liability);
	}

	public function get_prime_contact($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();

		$contact = ['email' => NULL, 'firstname' => NULL, 'lastname' => NULL, 'fullname' => NULL];
		$header = $this->get_header();
		if (!empty($header['extra_logins_email_address'])) {
			$contact['email'] = $header['extra_logins_email_address'];
			$contact['firstname'] = $header['extra_logins_firstname'];
			$contact['lastname'] = $header['extra_logins_lastname'];
			$contact['fullname'] = $contact['firstname'].' '.$contact['lastname'];
		}
		else {
			$contact['email'] = $header['customers_email_address'];
			$names = preg_split('/\s+/', $header['customers_name'], 2);
			$contact['firstname'] = $names[0];
			$contact['lastname'] = $names[1];
			$contact['fullname'] = $header['customers_name'];
		}

		if (empty($key)) return $contact;
		else return $contact[$key];
	}

	public function get_dates($type=NULL) {
		$dates = [
			'book' => $this->get_header('date_purchased'),
			'promise' => $this->get_header('promised_ship_date'),
			'ship' => NULL,
			'cancel' => NULL,
		];

		if ($this->is_shipped()) {
			$status_history = array_reverse($this->get_status_history());

			foreach ($status_history as $status) {
				if ($status['orders_status_code'] == self::STATUS_SHIPPED) {
					$dates['ship'] = $status['status_date'];
					break;
				}
			}
		}
		elseif ($this->is_canceled()) {
			$status_history = array_reverse($this->get_status_history());

			foreach ($status_history as $status) {
				if ($status['orders_status_code'] == self::STATUS_CANCELED) {
					$dates['cancel'] = $status['status_date'];
					break;
				}
			}
		}

		$aliases = [
			'booked' => 'book',
			'promised' => 'promise',
			'shipped' => 'ship',
			'cancelled' => 'cancel',
			'canceled' => 'cancel',
		];

		if (!empty($aliases[$type])) $type = $aliases[$type];

		if (!empty($type)) return $dates[$type];
		else return $dates;
	}

	public function has_service_rep() {
		if (!$this->skeleton->built('service_rep')) $this->build_service_rep();
		return $this->skeleton->has('service_rep');
	}

	public function get_service_rep() {
		if (!$this->has_service_rep()) return NULL;
		else return $this->skeleton->get('service_rep');
	}

	public function has_account_manager() {
		if (!$this->skeleton->built('account_manager')) $this->build_account_manager();
		return $this->skeleton->has('account_manager');
	}

	public function get_account_manager() {
		if (!$this->has_account_manager()) return NULL;
		else return $this->skeleton->get('account_manager');
	}

	public function has_sales_team() {
		if (!$this->skeleton->built('sales_team')) $this->build_sales_team();
		return $this->skeleton->has('sales_team');
	}

	public function get_sales_team() {
		if (!$this->has_sales_team()) return NULL;
		else return $this->skeleton->get('sales_team');
	}

	public function get_contact_phone($separator='.') {
		$default_number = ['888', '622', '0223'];
		if (!$this->has_sales_team() || !$this->get_sales_team()->has('phone_number')) return implode($separator, $default_number);
		else {
			$team_number = array_filter(preg_split('/[^0-9]+/', $this->get_sales_team()->get_header('phone_number')));
			return implode($separator, $team_number);
		}
	}

	public function get_contact_local_phone($separator='.') {
		$default_number = ['678', '597', '5000'];
		if (!$this->has_sales_team() || !$this->get_sales_team()->has('local_phone_number')) return implode($separator, $default_number);
		else {
			$team_number = array_filter(preg_split('/[^0-9]+/', $this->get_sales_team()->get_header('local_phone_number')));
			return implode($separator, $team_number);
		}
	}

	public function get_contact_email() {
		$default_email = 'sales@cablesandkits.com';
		if (!$this->has_sales_team() || !$this->get_sales_team()->has('email_address')) return $default_email;
		else {
			$team_email = $this->get_sales_team()->get_header('email_address');
			return $team_email;
		}
	}

	public function get_customer() {
		if (!$this->skeleton->built('customer')) $this->build_customer();
		return $this->skeleton->get('customer');
	}

	public function has_extra_login() {
		if (!$this->skeleton->built('extra_login')) $this->build_extra_login();
		return $this->skeleton->has('extra_login');
	}

	public function get_extra_login() {
		if (!$this->has_extra_login()) return NULL;
		return $this->skeleton->get('extra_login');
	}

	public function get_address($key=NULL) {
		if (!$this->skeleton->built('address')) $this->build_address();
		if (empty($key)) return $this->skeleton->get('address');
		elseif ($key == 'legacy') {
			$address = $this->skeleton->get('address');
			$address['street_address'] = $address['street_address_1'];
			$address['suburb'] = $address['street_address_2'];
			$address['postcode'] = $address['zip'];
			$address['telephone'] = $address['phone'];
			$address['format_id'] = $address['address_format_id'];
			unset($address['street_address_1']);
			unset($address['street_address_2']);
			unset($address['zip']);
			unset($address['phone']);
			unset($address['address_format_id']);

			return $address;
		}
		else return $this->skeleton->get('address', $key);
	}

	public function get_billing_address($key=NULL) {
		if (!$this->skeleton->built('billing_address')) $this->build_billing_address();
		if (empty($key)) return $this->skeleton->get('billing_address');
		elseif ($key == 'legacy') {
			$address = $this->skeleton->get('billing_address');
			$address['street_address'] = $address['street_address_1'];
			$address['suburb'] = $address['street_address_2'];
			$address['postcode'] = $address['zip'];
			$address['format_id'] = $address['address_format_id'];
			unset($address['street_address_1']);
			unset($address['street_address_2']);
			unset($address['zip']);
			unset($address['address_format_id']);

			return $address;
		}
		else return $this->skeleton->get('billing_address', $key);
	}

	public function get_shipping_address($key=NULL) {
		if (!$this->skeleton->built('shipping_address')) $this->build_shipping_address();
		if (empty($key)) return $this->skeleton->get('shipping_address');
		elseif ($key == 'legacy') {
			$address = $this->skeleton->get('shipping_address');
			$address['street_address'] = $address['street_address_1'];
			$address['suburb'] = $address['street_address_2'];
			$address['postcode'] = $address['zip'];
			$address['telephone'] = $address['phone'];
			$address['format_id'] = $address['address_format_id'];
			unset($address['street_address_1']);
			unset($address['street_address_2']);
			unset($address['zip']);
			unset($address['phone']);
			unset($address['address_format_id']);

			return $address;
		}
		else return $this->skeleton->get('shipping_address', $key);
	}

	public function get_addr() {
		if (!$this->skeleton->built('addr')) $this->build_addr();
		return $this->skeleton->get('addr');
	}

	public function get_bill_address() {
		if (!$this->skeleton->built('bill_address')) $this->build_bill_address();
		return $this->skeleton->get('bill_address');
	}

	public function get_ship_address() {
		if (!$this->skeleton->built('ship_address')) $this->build_ship_address();
		return $this->skeleton->get('ship_address');
	}

	public function get_totals($key=NULL) {
		if (!$this->skeleton->built('totals')) $this->build_totals();
		if (empty($key)) return $this->skeleton->get('totals');
		else return $this->skeleton->get('totals', $key);
	}

	public function get_simple_totals($key=NULL) {
		if (!$this->skeleton->built('simple_totals')) $this->build_totals();
		if (empty($key)) return $this->skeleton->get('simple_totals');
		else return $this->skeleton->get('simple_totals', $key);
	}

	public function get_total() {
		return $this->get_simple_totals('total');
	}

	public function get_balance() {
		$balance = 0;

		if ($this->is_canceled()) $balance = 0;
		elseif ($this->is_shipped() && $this->has_invoices()) {
			$invoice = $this->get_latest_invoice('instance');
			$balance = $invoice->get_balance();
		}
		else {
			$balance = $this->get_simple_totals('total');

			if ($this->has_payments()) {
				foreach ($this->get_payments() as $pmt) {
					$balance -= $pmt['pmt_applied_amount'];
				}
			}
		}

		return $balance;
	}

	public function get_product_subtotal() {
		$subtotal = 0;
		foreach ($this->get_products() as $product) {
			$subtotal += ($product['quantity'] * $product['final_price']);
		}
		return $subtotal;
	}

	public function get_estimated_costs($key=NULL) {
		if (!$this->skeleton->built('estimated_costs')) $this->build_estimated_costs();
		if (empty($key)) return $this->skeleton->get('estimated_costs');
		else return $this->skeleton->get('estimated_costs', $key);
	}

	public function get_final_costs($key=NULL) {
		if (!$this->skeleton->built('final_costs')) $this->build_final_costs();
		if (empty($key)) return $this->skeleton->get('final_costs');
		else return $this->skeleton->get('final_costs', $key);
	}

	public function get_shipping_method($key=NULL) {
		if (!$this->skeleton->built('shipping_method')) $this->build_shipping_method();
		if (empty($key)) return $this->skeleton->get('shipping_method');
		else return $this->skeleton->get('shipping_method', $key);
	}

	public function is_shipping_on_account() {
		return $this->is('dsm');
	}

	public function get_shipping_method_display($type='full'): string {
		$sm = $this->get_shipping_method();
		if (empty($sm['shipping_code'])) return '';
		// @todo: fix undefined title. Before the return type of this function was explicit, this error was unnoticed
		elseif ($sm['shipping_code'] == 50) return $sm['title'] ?? '';

		switch ($type) {
			case 'short':
				return trim($sm['carrier'].' '.$sm['method_name']);
				break;
			case 'full':
			default:
				return trim($sm['carrier'].' '.$sm['method_name']).($sm['description']?' ('.$sm['description'].')':'');
				break;
		}
	}

	public function has_products() {
		if (!$this->skeleton->built('products')) $this->build_products();
		return $this->skeleton->has('products');
	}

	public function get_products($orders_products_id=NULL) {
		if (!$this->has_products()) return [];
		if (empty($orders_products_id)) return $this->skeleton->get('products');
		$products = $this->skeleton->get('products');
		foreach ($products as $product) {
			if ($product['orders_products_id'] == $orders_products_id) return $product;
		}
		return [];
	}

	public function get_out_of_stock_products() {
		$out_of_stock_products = [];
		foreach ($this->get_products() as $product) {
			if ($product['availability_status'] == self::ASTATUS_STOCK_ISSUE && !$product['ipn']->is('drop_ship')) {
				$out_of_stock_products[] = $product;
			}
		}
		return $out_of_stock_products;
	}

	public function get_products_by_parent($parent_product_id, $included_only=FALSE) {
		$product_results = [];
		foreach ($this->skeleton->get('products') as $product) {
			if ($included_only == TRUE) {
				if ($product['parent_products_id'] == $parent_product_id && $product['option_type'] == 3) $product_results[] = $product;
			}
			else {
				if ($product['parent_products_id'] == $parent_product_id) $product_results[] = $product;
			}
		}
		return $product_results;
	}

	public function has_freight_products() {
		foreach ($this->get_products() as $product) {
			if ($product['ipn']->is('freight')) return TRUE;
		}
		return FALSE;
	}

	public function has_po_allocations() {
		if (!$this->skeleton->built('po_allocations')) $this->build_po_allocations();
		return $this->skeleton->has('po_allocations');
	}

	public function get_po_allocations($orders_products_id=NULL) {
		if (!$this->has_po_allocations()) return [];
		if (empty($orders_products_id)) return $this->skeleton->get('po_allocations');
		else {
			$allocs = [];
			foreach ($this->skeleton->get('po_allocations') as $po_allocation) {
				if ($po_allocation['orders_products_id'] == $orders_products_id) $allocs[] = $po_allocation;
			}
			return $allocs;
		}
	}

	public function has_freight_lines() {
		if (!$this->skeleton->built('freight_lines')) $this->build_freight_lines();
		return $this->skeleton->has('freight_lines');
	}

	public function get_freight_lines($key=NULL) {
		if (!$this->has_freight_lines()) return NULL;
		if (empty($key)) return $this->skeleton->get('freight_lines');
		else return $this->skeleton->get('freight_lines', $key);
	}

	public function get_status_history() {
		if (!$this->skeleton->built('status_history')) $this->build_status_history();
		return $this->skeleton->get('status_history');
	}

	public function has_notes() {
		if (!$this->skeleton->built('notes')) $this->build_notes();
		return $this->skeleton->has('notes');
	}

	public function get_notes() {
		if (!$this->has_notes()) return [];
		return $this->skeleton->get('notes');
	}

	public function has_acc_order_notes() {
		if (!$this->skeleton->built('acc_order_notes')) $this->build_acc_order_notes();
		return $this->skeleton->has('acc_order_notes');
	}

	public function get_acc_order_notes() {
		if (!$this->has_acc_order_notes()) return [];
		return $this->skeleton->get('acc_order_notes');
	}

	public function has_packages() {
		if (!$this->skeleton->built('packages')) $this->build_packages();
		return $this->skeleton->has('packages');
	}

	public function get_packages() {
		if (!$this->has_packages()) return [];
		return $this->skeleton->get('packages');
	}

	public function has_fraud_score() {
		if (!$this->skeleton->built('fraud_score')) $this->build_fraud_score();
		return $this->skeleton->has('fraud_score');
	}

	public function get_fraud_score($key=NULL) {
		if (!$this->has_fraud_score()) return [];
		if (empty($key)) return $this->skeleton->get('fraud_score');
		elseif ($key == 'risk') return $this->get_fraud_risk();
		else return $this->skeleton->get('fraud_score', $key);
	}

	public function get_fraud_risk() {
		if (!$this->has_fraud_score() || empty($this->skeleton->get('fraud_score', 'score')) || !empty($this->skeleton->get('fraud_score', 'err'))) {
			return '(ERROR PROCESSING RISK SCORE)';
		}

		$risk = '(I can smell the fraud from here)';

		if (!empty($this->skeleton->get('fraud_score', 'query'))) {
			// newer maxmind results fit this format - since 2012
			$score = $this->skeleton->get('fraud_score', 'score');
			if ($score < 1) $risk = '(Extremely Low risk)';
			elseif ($score < 3) $risk = '(Very Low risk)';
			elseif ($score < 6) $risk = '(Low risk)';
			elseif ($score < 8) $risk = '(Low-Medium risk)';
			elseif ($score < 10) $risk = '(Medium risk)';
			elseif ($score < 15) $risk = '(Medium-High risk)';
			elseif ($score < 20) $risk = '(High risk)';
			elseif ($score < 25) $risk = '(Very High risk)';
			elseif ($score < 30) $risk = '(Extremely High risk)';
		}
		else {
			// older maxmind results fit this format - prior to the end of 2012
			// this should probably be ceil() rather than round(), but we're carrying forward the logic as is since it's only for old orders anyway
			$score = round($this->skeleton->get('fraud_score', 'score'));
			if ($score == 0) $risk = '(Extremely Low risk)';
			elseif ($score == 1) $risk = '(Very Low risk)';
			elseif ($score == 2 || $score == 3) $risk = '(Low risk)';
			elseif ($score == 4) $risk = '(Low-Medium risk)';
			elseif ($score == 5) $risk = '(Medium risk)';
			elseif ($score == 6) $risk = '(Medium-High risk)';
			elseif ($score == 7) $risk = '(High risk)';
			elseif ($score == 8) $risk = '(Very High risk)';
			elseif ($score == 9) $risk = '(Extremely High risk)';
		}

		return $risk;
	}

	public function has_payments() {
		if (!$this->skeleton->built('payments')) $this->build_payments();
		return $this->skeleton->has('payments');
	}

	public function get_payments() {
		if (!$this->has_payments()) return [];
		return $this->skeleton->get('payments');
	}

	public function get_allocated_total() {
		return array_reduce($this->get_payments(), function($total, $payment) {
			$total += $payment['pmt_applied_amount'];
			return $total;
		}, 0);
	}

	public function has_invoices() {
		if (!$this->skeleton->built('invoices')) $this->build_invoices();
		return $this->skeleton->has('invoices');
	}

	public function get_invoices($key=NULL) {
		if (!$this->has_invoices()) return [];
		if (empty($key)) return $this->skeleton->get('invoices');
		else {
			foreach ($this->skeleton->get('invoices') as $invoice) {
				if ($invoice['invoice_id'] == $key) return $invoice;
			}
		}
	}

	public function get_latest_invoice($key=NULL) {
		if (!$this->has_invoices()) return NULL;
		$invoice = $this->get_invoices()[0];
		if (empty($key)) return $invoice;
		elseif ($key == 'instance') return new ck_invoice($invoice['invoice_id']);
		else return $invoice[$key];
	}

	public function get_first_invoice_date() {
		$first_invoice_date = prepared_query::fetch('SELECT MIN(inv_date) FROM acc_invoices WHERE inv_order_id = :orders_id', cardinality::SINGLE, [':orders_id' => $this->id()]);
		if (!empty($first_invoice_date)) $first_invoice_date = self::DateTime($first_invoice_date);
		return $first_invoice_date;
	}

	public function has_rmas() {
		if (!$this->skeleton->built('rmas')) $this->build_rmas();
		return $this->skeleton->has('rmas');
	}

	public function get_rmas() {
		if (!$this->has_rmas()) return [];
		return $this->skeleton->get('rmas');
	}

	public function has_parent_orders() {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (!empty($this->get_header('parent_orders_id'))) return TRUE;
		else return FALSE;
	}

	public function get_parent_orders() {
		if (!$this->has_parent_orders()) return NULL;
		else {
			$orders = [];

			$ord = $this;
			do {
				$ord = new ck_sales_order($ord->get_header('parent_orders_id'));
				$orders[] = $ord;
			}
			while ($ord->has_parent_orders());

			return $orders;
		}
	}

	public function has_child_orders() {
		if (!$this->skeleton->built('child_orders')) $this->build_child_orders();
		return $this->skeleton->has('child_orders');
	}

	public function get_child_orders() {
		if (!$this->has_child_orders()) return [];
		return $this->skeleton->get('child_orders');
	}

	public function has_terms_po_number() {
		return in_array($this->get_header('payment_method_code'), ['net10', 'net15', 'net30', 'net45', 'net60']) && !empty($this->get_header($this->get_header('payment_method_code').'_po'));
	}

	public function get_terms_po_number() {
		if (!$this->has_terms_po_number()) return NULL;

		return $this->get_header($this->get_header('payment_method_code').'_po');

		/*switch ($this->get_header('payment_method_code')) {
			case 'net10': return $this->get_header('net10_po'); break;
			case 'net15': return $this->get_header('net15_po'); break;
			case 'net30': return $this->get_header('net30_po'); break;
			case 'net45': return $this->get_header('net45_po'); break;
			default: return NULL; break;
		}*/
	}

	public function get_terms() {
		if (!$this->has_terms_po_number()) return 0;
		else {
			switch ($this->get_header('payment_method_code')) {
				case 'net10': return 10; break;
				case 'net15': return 15; break;
				case 'net30': return 30; break;
				case 'net45': return 45; break;
				case 'net60': return 60; break;
				default: return 0; break;
			}
		}
	}

	public function has_ref_po_number() {
		return !empty($this->get_header('purchase_order_number'));
	}

	public function get_ref_po_number() {
		if (!$this->has_ref_po_number()) return NULL;

		return $this->get_header('purchase_order_number');
	}

	public function get_estimated_shipped_weight() {
		$shipped_weight = $this->get_estimated_product_weight();

		// we add the greater between the default tare weight or the tare factor applied to the product weight
		$shipped_weight += max(shipit::$box_tare_weight, $shipped_weight * shipit::$box_tare_factor);

		return round($shipped_weight, 1);
	}

	public function get_estimated_product_weight() {
		$product_weight = 0;

		foreach ($this->get_products() as $product) {
			$product_weight += $product['quantity'] * $product['listing']->get_simple_weight();
		}

		return round($product_weight, 1);
	}

	public function get_estimated_margin_dollars($type='product', $format=FALSE) {
		$estimated_costs = $this->get_estimated_costs();

		if ($estimated_costs['incalculable']) return 'Incalculable'; // incalculable reason is accessible through get_estimated_costs()

		$base = $estimated_costs['product_total']['price'] - $estimated_costs['product_total']['cost'] - $estimated_costs['coupon_total']['price'];

		switch ($type) {
			case 'product':
			default:
				// don't do anything
				break;
			case 'total':
				$base += $estimated_costs['shipping_total']['price'];
				break;
		}

		return $format?CK\text::monetize($base):$base;
	}

	public function get_estimated_margin_pct($type='product', $format=FALSE) {
		$estimated_costs = $this->get_estimated_costs();

		if ($estimated_costs['incalculable']) return 'Incalculable'; // incalculable reason is accessible through get_estimated_costs()

		$base_denominator = $estimated_costs['product_total']['price'] - $estimated_costs['coupon_total']['price'];
		$base_numerator = $base_denominator - $estimated_costs['product_total']['cost'];

		switch ($type) {
			case 'product':
			default:
				// don't do anything
				break;
			case 'total':
				$base_denominator += $estimated_costs['shipping_total']['price'];
				$base_numerator += $estimated_costs['shipping_total']['price'];
				break;
		}

		return $format?number_format(100*($base_numerator/$base_denominator), 2).'%':$base_numerator/$base_denominator;
	}

	public function get_final_margin_dollars($type='product', $format=FALSE) {
		$final_costs = $this->get_final_costs();

		if ($final_costs['incalculable']) return 'Incalculable'; // incalculable reason is accessible through get_final_costs()

		$base = $final_costs['product_total']['price'] - $final_costs['product_total']['cost'] - $final_costs['coupon_total']['price'];

		switch ($type) {
			case 'product':
			default:
				// don't do anything
				break;
			case 'total':
				$base += $final_costs['shipping_total']['price'];
				break;
		}

		return $format?CK\text::monetize($base):$base;
	}

	public function get_final_margin_pct($type='product', $format=FALSE) {
		$final_costs = $this->get_final_costs();

		if ($final_costs['incalculable']) return 'Incalculable'; // incalculable reason is accessible through get_final_costs()

		$base_denominator = $final_costs['product_total']['price'] - $final_costs['coupon_total']['price'];
		$base_numerator = $base_denominator - $final_costs['product_total']['cost'];

		switch ($type) {
			case 'product':
			default:
				// don't do anything
				break;
			case 'total':
				$base_denominator += $final_costs['shipping_total']['price'];
				$base_numerator += $final_costs['shipping_total']['price'];
				break;
		}

		return $format?number_format(100*($base_numerator/$base_denominator), 2).'%':$base_numerator/$base_denominator;
	}

	public function get_payment_service_id() {
		if ($this->has_parent_orders()) {
			$po = $this->get_parent_orders();
			$parent_order = array_pop($po); // get the top level parent
			$paymentsvc_id = $parent_order->get_header('paymentsvc_id');
		}
		else $paymentsvc_id = $this->get_header('paymentsvc_id');
		return $paymentsvc_id;
	}

	public function get_availability_status() {
		if (!$this->skeleton->built('availability_status')) $this->build_products();
		return $this->skeleton->get('availability_status');
	}

	public function get_serialized_status() {
		$result = ['serialized' => FALSE, 'allocated' => TRUE];
		foreach ($this->get_products() as $product) {
			if ($product['ipn']->is('serialized')) {
				$result['serialized'] = TRUE;
				$result['allocated'] = $result['allocated'] && ($product['quantity'] <= count($product['allocated_serials']));
			}

			if ($result['allocated'] == FALSE) break; // if at least one item is serialized and not allocated, we don't need to go any further than that
		}

		return $result;
	}

	public function is_terms() {
		return in_array($this->get_header('payment_method_id'), [ck_customer2::$cpmi[ck_customer2::NET10], ck_customer2::$cpmi[ck_customer2::NET15], ck_customer2::$cpmi[ck_customer2::NET30], ck_customer2::$cpmi[ck_customer2::NET45]]);
	}

	public function is_cc() {
		return $this->get_header('payment_method_code') == 'creditcard_pp';
	}

	public function is_pp() {
		return $this->get_header('payment_method_code') == 'paypal';
	}

	public function is_cc_capture_needed() {
		if (!$this->is_cc()) return FALSE;

		$paid = prepared_query::fetch('SELECT SUM(amount) FROM acc_payments_to_orders WHERE order_id = :orders_id', cardinality::SINGLE, [':orders_id' => $this->id()]);
		if (empty($paid) || $paid < $this->get_simple_totals('total')) return TRUE;

		return FALSE;
	}

	public function is_paid_shippable() {
		if (in_array($this->get_header('payment_method_code'), ['net10', 'net15', 'net30', 'net45', 'net60', 'amazon'])) return TRUE;

		$paid = prepared_query::fetch('SELECT SUM(amount) FROM acc_payments_to_orders WHERE order_id = :orders_id', cardinality::SINGLE, [':orders_id' => $this->id()]);
		if (empty($paid) || $paid < $this->get_simple_totals('total')) return FALSE;

		return TRUE;
	}

	public function is_authed_shippable() {
		if (in_array($this->get_header('payment_method_code'), ['creditcard_pp', 'creditcard_authnet', 'customer_service'])) return TRUE;
		else return FALSE;
	}

	public function is_stock_shippable() {
		if (!$this->skeleton->built('is_stock_shippable')) $this->build_products();
		return $this->skeleton->get('is_stock_shippable');
	}

	public function is_unshippable($admin=FALSE) {
		if (!$this->is_shipped()) return FALSE; // if it's not shipped, we can't unship it

		if (!$this->has_invoices() || (($invoices = $this->get_invoices()) && count($invoices) != 1)) return FALSE; // if there's not an invoice, *or* there are multiple invoices, we can't unship it

		if ($this->has_rmas()) return FALSE; // if this order has any RMAs, we can't unship it

		if ($invoices[0]['invoice_date']->format('Y-m-d') != self::NOW()->format('Y-m-d') && !$admin) return FALSE; // if the invoice date is *not* today, we can't unship it unless we're an admin
		elseif ($invoices[0]['invoice_date']->format('Y-m') != self::NOW()->format('Y-m') && $admin) return FALSE; // if the invoice date is *not* this month, we can't unship it even if we're an admin

		//if (count($invoices[0]['products']) != count($this->get_products())) return FALSE; // if, for whatever reason, this invoice products don't match our order, we can't unship - think this is legacy

		return TRUE; // if it was shipped/invoiced today and we haven't messed with it, we can unship
	}

	public function is_shipped() {
		return in_array($this->get_header('orders_status'), [self::STATUS_SHIPPED]);
	}

	public function is_canceled() {
		return in_array($this->get_header('orders_status'), [self::STATUS_CANCELED]);
	}

	public function is_open() {
		return !in_array($this->get_header('orders_status'), [self::STATUS_SHIPPED, self::STATUS_CANCELED]);
	}

	public function is_closed() {
		return !$this->is_open();
	}

	public function is_3pl_eligible() {
		$ups_3pl_states = ['CA', 'NV', 'AZ', 'UT'];
		$ship_to = $this->get_shipping_address();
		if (in_array($ship_to['state'], $ups_3pl_states)) {
			foreach ($this->get_products() as $product) {
				$product_quantity = prepared_query::fetch('SELECT SUM(quantity) FROM inventory_hold WHERE reason_id = :reason_id AND stock_id = :stock_id', cardinality::SINGLE, [':reason_id' => 16, ':stock_id' => $product['stock_id']]);
				if (!$product_quantity) return FALSE;
				if ($product['quantity'] > $product_quantity) return FALSE;
			}
			return TRUE;
		}
		return FALSE;
	}

	public function is_marketplace() {
		return !empty($this->get_header('ca_order_id'));
	}

	// these are mostly copied as-is from the Zend order model
	public function recv_po_is_product_complete(&$salable_quantities) {
		// if this order fails, skip it and don't assign any portion of salable quantities to it by using the local_salable_quantities in-method
		// if this order completes, update the salable_quantities array appropriately
		$local_salable_quantities = $salable_quantities;

		foreach ($this->get_products() as $product) {
			if (!array_key_exists($product['ipn']->id(), $local_salable_quantities)) $local_salable_quantities[$product['ipn']->id()] = $product['ipn']->get_inventory('salable');

			// remove the order qty from the $salable_qty, and assign that result to the salable _quantities array
			if ($product['quantity'] <= $local_salable_quantities[$product['ipn']->id()]) $local_salable_quantities[$product['ipn']->id()] -= $product['quantity'];
			// this order cannot be completed along with all of the other orders that this PO affects
			// either we haven't received all product yet, or other orders have been set completely ready to pick, and they take enough inventory to keep this one from completing
			else return FALSE;
		}
		// we passed salable_quantities by reference, so we just need to assign to it and be done with it
		$salable_quantities = $local_salable_quantities;
		return TRUE;
	}

	public function recv_po_is_ready_to_ship(&$salable_quantities) {
		$ready_to_pick = TRUE;
		$fail_reasons = [];

		if (!$this->is_paid_shippable() && !$this->is_authed_shippable()) {
			$ready_to_pick = FALSE;
			$fail_reasons[] = 'pmt';
		}

		if (!$this->recv_po_is_product_complete($salable_quantities)) {
			$ready_to_pick = FALSE;
			$fail_reasons[] = 'prod';
		}

		return [$ready_to_pick, $fail_reasons];
	}

	public static function get_ship_rate_quotes(ck_address2 $address, $weight, $method=NULL, $module=NULL) {
		$header = $address->get_header();
		$GLOBALS['order'] = (object) [
			'delivery' => [
				'postcode' => $header['postcode'],
				'country' => [
					'id' => $header['countries_id'],
					'title' => $header['country'],
					'iso_code_2' => $header['countries_iso_code_2'],
					'iso_code_3' => $header['countries_iso_code_3']
				],
				'country_id' => $header['countries_id'],
				'format_id' => $header['country_address_format_id']
			]
		]; // needed to interact with the oldschool shipping modules

		$GLOBALS['order']->delivery['street_address'] = $header['address1'];
		$GLOBALS['order']->delivery['suburb'] = $header['address2'];
		$GLOBALS['order']->delivery['city'] = $header['city'];
		$GLOBALS['order']->delivery['telephone'] = $header['telephone'];
		$GLOBALS['order']->delivery['state'] = $address->get_state();
		$GLOBALS['order']->delivery['zone_id'] = $header['zone_id'];

		$GLOBALS['total_weight'] = $weight;

		require_once(__DIR__.'/../classes/shipping.php');
		$shipping_modules = new shipping;
		$quote_list = $shipping_modules->quote($method, $module);

		$rate_groups = [];

		foreach ($quote_list as $module_idx => $module_raw) {
			$rate_group = ['module' => $module_raw['module']];
			if (!empty($module_raw['icon'])) {
				$rate_group['group_img?'] = $module_raw['icon'];
			}

			if (!empty($module_raw['error'])) {
				$rate_group['error_raw'] = $module_raw['error'];
				if (empty($rate_group['rate_quotes'])) $rate_group['rate_quotes'] = [];
				$rate_group['rate_quotes'][] = ['name' => $module_raw['module'], 'error?' => '('.$module_raw['error'].')'];
			}
			else {
				if (empty($module_raw['methods'])) continue;
				foreach ($module_raw['methods'] as $method_idx => $method_raw) {
					$quote = [];
					$quote['shipping_method_id'] = $method_raw['shipping_method_id'];
					$quote['price'] = CK\text::monetize($method_raw['cost']);
					$quote['price_raw'] = $method_raw['cost'];
					$quote['title_raw'] = $method_raw['title'];
					$quote['negotiated_rate'] = !empty($method_raw['negotiated_rate'])?CK\text::monetize($method_raw['negotiated_rate']):NULL;

					if ($method_raw['shipping_method_id'] == 50) {
						// freight quote
						$quote['name'] = 'Oversize/Best Fit Shipping';

						if (!empty($module_raw['residential'])) {
							if (empty($module_raw['verified_address'])) {
								$quote['possible_residential?'] = 'The cost shown is for residential delivery, if you are shipping to a business location, please log in to verify your address.';
							}
							else {
								$quote['possible_residential?'] = 'Your address is a residential address. A residential delivery surcharge is included in your shipping cost.';
							}

							$quote['quote_residential?'] = 1;
						}
						elseif (empty($quote['confirmed'])) {
							$quote['possible_residential?'] = 'We cannot confirm that your location is a business. If the carrier determines that your address is a residential address, then an additional residential delivery surcharge will be included.';
						}

						$rate_group['freight_quote'] = $quote;
					}
					else {
						if (empty($rate_group['rate_quotes'])) $rate_group['rate_quotes'] = [];

						require_once(__DIR__.'/../classes/shipping_methods.php');
						$shipmethod = new shipping_methods;
						$quote['name'] = $shipmethod->sm_short_description($method_raw['shipping_method_id']);

						if (!empty($_SESSION['sm_cmnt'][$method_raw['shipping_method_id']])) $quote['estimated_delivery'] = $_SESSION['sm_cmmt'][$method_raw['shipping_method_id']];
						else $quote['estimated_delivery'] = $shipmethod->sm_description($method_raw['shipping_method_id']);

						$rate_group['rate_quotes'][] = $quote;
					}
				}
			}
			$rate_groups[] = $rate_group;
		}

		return $rate_groups;
	}

	public static function get_orders_by_customer($customers_id, Callable $sort=NULL) {
		if ($customers_id && ($headers = self::fetch('order_header_list', [':customers_id' => $customers_id]))) {
			$orders = [];
			foreach ($headers as $header) {
				$skeleton = self::get_record($header['orders_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$orders[] = new self($header['orders_id'], $skeleton);
			}

			if (!empty($sort)) usort($orders, $sort);

			return $orders;
		}
		else return [];
	}

	public static function get_order_ids_by_customer($customers_id) {
		if ($customers_id && ($order_ids = prepared_query::fetch('SELECT DISTINCT orders_id FROM orders WHERE customers_id = :customers_id', cardinality::COLUMN, [':customers_id' => $customers_id]))) {
			return $order_ids;
		}
		else return [];
	}

	public static $matched_total = 0;

	public static function get_orders_by_match($criteria, $fields=[], $page=1, $page_size=50, $order_by=[]) {
		$query_mods = ['data_operation' => '', 'where' => '', 'order_by' => '', 'limit' => ''];

		$clauses = self::$queries['customer_header_list_proto']['proto_opts'];

		if (isset($fields[':customer_type'])) $where_ctype = $clauses[':customer_type'];

		if (!empty($fields[':email']) && empty($fields[':main_only'])) $where_email = $clauses[':email'];
		elseif (!empty($fields[':email'])) $where_email = $clauses[':email_main'];

		if (!empty($fields[':last_name']) && empty($fields[':main_only'])) $where_lname = $clauses[':last_name'];
		elseif (!empty($fields[':last_name'])) $where_lname = $clauses[':last_name_main'];

		if (!empty($fields[':first_name']) && empty($fields[':main_only'])) $where_fname = $clauses[':first_name'];
		elseif (!empty($fields[':first_name'])) $where_fname = $clauses[':first_name_main'];

		if (!empty($fields[':use_pricing'])) $where_price = $clauses[':use_pricing'];

		if (!empty($where_fname) && !empty($where_lname)) $where_name = '('.$where_fname.(empty($fields[':main_only'])?' AND ':' OR ').$where_lname.')';
		elseif (!empty($where_fname)) $where_name = $where_fname;
		elseif (!empty($where_lname)) $where_name = $where_lname;

		if (!empty($where_email) && !empty($where_name)) $where_person = '('.$where_email.' OR '.$where_name.')';
		elseif (!empty($where_email)) $where_person = $where_email;
		elseif (!empty($where_name)) $where_person = $where_name;

		$wheres = [];
		if (!empty($where_ctype)) $wheres[] = $where_ctype;
		if (!empty($where_person)) $wheres[] = $where_person;
		if (!empty($where_price)) $wheres[] = $where_price;

		if (!empty($wheres)) $query_adds['where'] = 'WHERE '.implode(' AND ', $wheres);

		unset($fields[':main_only']);
		unset($fields[':use_pricing']);

		if (!empty($order_by)) {
			$sort_whitelist = ['c.customers_lastname' => 'DESC', 'c.customers_firstname' => 'DESC', 'a.admin_firstname' => 'DESC', 'ci.customers_info_date_account_created' => 'DESC', 'c.customers_email_address' => 'DESC'];
			foreach ($order_by as $field => $direction) {
				if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) continue;
				if (empty($sort_whitelist[$field])) continue;
				$query_adds['order_by'][] = $field.' '.$direction;
			}
		}

		if (!empty($query_adds['order_by'])) $query_adds['order_by'] = 'ORDER BY '.implode(', ', $query_adds['order_by']);
		else $query_adds['order_by'] = 'ORDER BY c.customers_id DESC';

		$qry = self::modify_query('customer_header_count_proto', $query_adds);

		self::$matched_total = self::fetch($qry, $fields);

		if (!is_numeric($page)) $page = 1;
		if (!is_numeric($page_size)) $page_size = 50;
		$page--; // limits are zero based

		$query_adds['limit'] = 'LIMIT '.($page*$page_size).', '.$page_size;

		// modify_query is necessary because we can't parameterize limits or ordering
		if ($headers = self::fetch(self::modify_query('customer_header_list_proto', $query_adds), $fields)) {
			$customers = [];
			foreach ($headers as $header) {
				$skeleton = self::get_record($header['customers_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$customers[] = new self($header['customers_id'], $skeleton);
			}
			return $customers;
		}
		else return [];
	}

	public static function get_order_page_limit() {
		return prepared_query::fetch('SELECT COUNT(*) as total FROM orders WHERE orders_status IN (5, 1, 2, 7, 8, 10)', cardinality::SINGLE, []) + 9;
	}

	public static function get_order_by_ca_order_id($ca_order_id) {
		if ($orders_id = self::fetch('channel_advisor_order', [':ca_order_id' => $ca_order_id])) {
			return new self($orders_id);
		}
		else return NULL;
	}

	public static function get_order_by_invoice_id($invoice_id) {
		if ($orders_id = prepared_query::fetch('SELECT inv_order_id FROM acc_invoices WHERE invoice_id = :invoice_id', cardinality::SINGLE, [':invoice_id' => $invoice_id])) {
			return new self($orders_id);
		}
		else return NULL;
	}

	public static function get_shipped_ca_orders() {
		if ($orders_ids = self::fetch('shipped_channel_advisor_orders', [])) {
			$orders = [];
			foreach ($orders_ids as $orders_id) {
				$orders[] = new self($orders_id);
			}
			return $orders;
		}
		else return [];
	}

	public static function get_orders_list($customers_id=NULL, $orders_status_id=NULL, $sales_team_id=NULL, Callable $sort=NULL, $page=NULL, $page_size=0) {
		if (!empty($customers_id) && !empty($orders_status_id) && !empty($sales_team_id)) {
			$os_fields = new prepared_fields($orders_status_id);
			if ($sales_team_id == 'NONE') {
				$st_fields = new prepared_fields([$customers_id]);
				$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE os.orders_status_id IN ('.$os_fields->select_values().') AND o.customers_id = ? AND o.sales_team_id IS NULL ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, prepared_fields::consolidate_parameters($os_fields, $st_fields));
			}
			else {
				$st_fields = new prepared_fields([$customers_id, $sales_team_id]);
				$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE os.orders_status_id IN ('.$os_fields->select_values().') AND o.customers_id = ? AND o.sales_team_id = ? ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, prepared_fields::consolidate_parameters($os_fields, $st_fields));
			}
		}
		elseif (!empty($customers_id) && !empty($orders_status_id)) {
			$os_fields = new prepared_fields($orders_status_id);
			$st_fields = new prepared_fields($customers_id);
			$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE os.orders_status_id IN ('.$os_fields->select_values().') AND o.customers_id = '.$st_fields->select_values().' ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, prepared_fields::consolidate_parameters($os_fields, $st_fields));
		}
		elseif (!empty($customers_id) && !empty($sales_team_id)) {
			if ($sales_team_id == 'NONE') {
				$st_fields = new prepared_fields(['o.customers_id' => $customers_id]);
				$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE '.$st_fields->where_clause().' AND o.sales_team_id IS NULL ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, $st_fields->parameters());
			}
			else {
				$st_fields = new prepared_fields(['o.customers_id' => $customers_id, 'o.sales_team_id' => $sales_team_id]);
				$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE '.$st_fields->where_clause().' ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, $st_fields->parameters());
			}
		}
		elseif (!empty($orders_status_id) && !empty($sales_team_id)) {
			$os_fields = new prepared_fields($orders_status_id);
			if ($sales_team_id == 'NONE') {
				$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE os.orders_status_id IN ('.$os_fields->select_values().') AND o.sales_team_id IS NULL ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, $os_fields->parameters());
			}
			else {
				$st_fields = new prepared_fields($sales_team_id);
				$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE os.orders_status_id IN ('.$os_fields->select_values().') AND o.sales_team_id = '.$st_fields->select_values().' ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, prepared_fields::consolidate_parameters($os_fields, $st_fields));
			}
		}
		elseif (!empty($customers_id)) {
			$st_fields = new prepared_fields(['o.customers_id' => $customers_id]);
			$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE '.$st_fields->where_clause().' ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, $st_fields->parameters());
		}
		elseif (!empty($orders_status_id)) {
			$os_fields = new prepared_fields($orders_status_id);
			$orders_headers = prepared_query::fetch('SELECT o.orders_id FROM orders o JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE os.orders_status_id IN ('.$os_fields->select_values().') ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, $os_fields->parameters());
		}
		elseif (!empty($sales_team_id)) {
			if ($sales_team_id == 'NONE') {
				$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE o.sales_team_id IS NULL ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET);
			}
			else {
				$st_fields = new prepared_fields(['o.sales_team_id' => $sales_team_id]);
				$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE '.$st_fields->where_clause().' ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, $st_fields->parameters());
			}
		}
		else {
			$orders_headers = prepared_query::fetch('SELECT o.orders_id FROM orders o JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, []);
		}

		$orders = [];

		$result_count = count($orders_headers);

		if (!empty($orders_headers)) {
			if (!empty($page) && !empty($page_size)) {
				$page--; // indexes are zero based
				$orders_headers = array_slice($orders_headers, $page*$page_size, $page_size);
			}

			ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);
			$of = new prepared_fields(array_map(function($ord) { return $ord['orders_id']; }, $orders_headers));
			ck_ipn2::load_ipn_set(prepared_query::fetch('SELECT DISTINCT p.stock_id FROM orders_products op JOIN products p ON op.products_id = p.products_id WHERE op.orders_id IN ('.$of->select_values().')', cardinality::COLUMN, $of->parameters()));
			ck_ipn2::run_ipn_set();

			foreach ($orders_headers as $orders_header) {
				if (!empty($orders_header['orders_status'])) self::load_resource($orders_header['orders_id'], 'header', $orders_header);
				$orders[] = new self($orders_header['orders_id']);
			}

			if (!empty($sort)) usort($orders, $sort);
		}

		return ['orders' => $orders, 'result_count' => $result_count];
	}

	public static function get_orders_list_by_po($po_number, Callable $sort=NULL, $page=NULL, $page_size=0) {
		$orders_headers = prepared_query::fetch('SELECT o.orders_id, o.orders_status, o.orders_sub_status, o.dropship, o.customers_company, o.customers_name, o.customers_id, o.date_purchased, o.followup_date, o.promised_ship_date, pm.code as payment_method_code, pm.label as payment_method_label, pm.osc_string as payment_method_osc_code, os.orders_status_name, os.sort_order as orders_status_sort_order, oss.orders_sub_status_name FROM orders o JOIN payment_method pm ON o.payment_method_id = pm.id JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE o.purchase_order_number LIKE :po_number OR net10_po LIKE :po_number OR net15_po LIKE :po_number OR net30_po LIKE :po_number OR net45_po LIKE :po_number OR net60_po LIKE :po_number ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, [':po_number' => '%'.$po_number.'%']);

		$orders = [];

		$result_count = count($orders_headers);

		if (!empty($orders_headers)) {
			if (!empty($page) && !empty($page_size)) {
				$page--; // indexes are zero based
				$orders_headers = array_slice($orders_headers, $page*$page_size, $page_size);
			}

			ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);
			$of = new prepared_fields(array_map(function($ord) { return $ord['orders_id']; }, $orders_headers));
			ck_ipn2::load_ipn_set(prepared_query::fetch('SELECT DISTINCT p.stock_id FROM orders_products op JOIN products p ON op.products_id = p.products_id WHERE op.orders_id IN ('.$of->select_values().')', cardinality::COLUMN, $of->parameters()));
			ck_ipn2::run_ipn_set();

			foreach ($orders_headers as $orders_header) {
				self::load_resource($orders_header['orders_id'], 'header', $orders_header);
				$orders[] = new self($orders_header['orders_id']);
			}

			if (!empty($sort)) usort($orders, $sort);
		}

		return ['orders' => $orders, 'result_count' => $result_count];
	}

	public static function get_orders_list_by_customer($customer, Callable $sort=NULL, $page=NULL, $page_size=0) {
		$orders_headers = prepared_query::fetch('SELECT o.orders_id FROM orders o JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE o.customers_name LIKE :customer OR o.customers_email_address LIKE :customer ORDER BY os.sort_order ASC, o.orders_id DESC', cardinality::SET, [':customer' => '%'.$customer.'%']);

		$orders = [];

		$result_count = count($orders_headers);

		if (!empty($orders_headers)) {
			if (!empty($page) && !empty($page_size)) {
				$page--; // indexes are zero based
				$orders_headers = array_slice($orders_headers, $page*$page_size, $page_size);
			}

			ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);
			$of = new prepared_fields(array_map(function($ord) { return $ord['orders_id']; }, $orders_headers));
			ck_ipn2::load_ipn_set(prepared_query::fetch('SELECT DISTINCT p.stock_id FROM orders_products op JOIN products p ON op.products_id = p.products_id WHERE op.orders_id IN ('.$of->select_values().')', cardinality::COLUMN, $of->parameters()));
			ck_ipn2::run_ipn_set();

			foreach ($orders_headers as $orders_header) {
				if (!empty($orders_header['orders_status'])) self::load_resource($orders_header['orders_id'], 'header', $orders_header);
				$orders[] = new self($orders_header['orders_id']);
			}

			if (!empty($sort)) usort($orders, $sort);
		}

		return ['orders' => $orders, 'result_count' => $result_count];
	}

	public static function get_order_by_orders_products_id($orders_products_id) {
		$order = NULL;

		if ($orders_id = prepared_query::fetch('SELECT orders_id FROM orders_products WHERE orders_products_id = :orders_products_id', cardinality::SINGLE, [':orders_products_id' => $orders_products_id])) {
			$order = new self($orders_id);
		}

		return $order;
	}

	public function has_recipients() {
		if (!$this->skeleton->built('recipients')) $this->build_recipients();
		return $this->skeleton->has('recipients');
	}

	public function get_recipients($key=NULL) {
		if (!$this->has_recipients()) return [];
		if (empty($key)) return $this->skeleton->get('recipients');
		elseif (!empty($key)) {
			foreach ($this->skeleton->get('recipients') as $recipient) {
				if ($recipient['email'] == $key) return $recipient;
			}
		}
		return [];
	}

	/*public static function get_status($status) {
		if (!self::$statuses_built) self::build_statuses();

		if (!is_numeric($status)) $status = preg_replace('/[ _-]/', '', strtoupper($status));

		return self::$statuses[$status];
	}

	public static function get_substatus($substatus_id) {
	}*/

	/*-------------------------------
	// sorting list results
	-------------------------------*/

	/*-------------------------------
	// modify data
	-------------------------------*/

	public static function create(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$params = new ezparams($data['header']);
			$orders_id = prepared_query::insert('INSERT INTO orders ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', CK\fn::parameterize($data['header']));

			$sales_order = new self($orders_id);

			$customer = $sales_order->get_customer();
			if (!$sales_order->has('orders_sales_rep_id') && $customer->has('account_manager_id')) $sales_order->change_account_manager($customer->get_header('account_manager_id'));
			elseif (!$sales_order->has('sales_team_id') && $customer->has('sales_team_id')) $sales_order->change_sales_team($customer->get_header('sales_team_id'));
			// assign team if not assigned already
			elseif (!$customer->has('sales_team_id')) {
				ck_team::auto_assign_sales_team($customer);
				$sales_order->change_sales_team($customer->get_header('sales_team_id'));
			}

			// turning this off for now. Needs some fixes
			// ck_promo::run_promos($sales_order);

			// deal with payments, and fail if payment fails
			// also deal with credit record, if relevant
			$sales_order->create_payment($data['payment'], $data['customer_notes']);

			// deal with fraud check - if needed, not for marketplace orders, maybe not for phone orders?
			if ($data['check_fraud']) {
				// nothing fancy here.  We'll redo the maxmind API at some point, maybe
				try {
					$insert_id = $sales_order->id();
					require(DIR_WS_MODULES.'maxmind/maxmind.php');
				}
				catch (Exception $e) {
					$data['admin_notes'][] = ['orders_note_text' => 'Maxmind Fraudcheck Failed: '.$e->getMessage()];
					if (!empty($data['customer_notes'])) $data['customer_notes'] .= ' | Automated Note - MaxMind Error, see admin notes.';
					else $data['customer_notes'] = 'Automated Note - MaxMind Error, see admin notes.';
				}
			}

			// this encompasses all products, either from the basic cart or from a quote
			/* we need to update this to account for total weight */
			$sales_order->create_products($data['products']);
			if (!$sales_order->is_stock_shippable()) $sales_order->update(['all_items_in_stock' => 0]);
			// deal with specials excess note

			// set promised ship date based on product expected ship date if the promised ship date hasn't already been set
			if (!$sales_order->has('promised_ship_date')) {
				$expected_ship_date = '';
				foreach ($sales_order->get_products() as $product) {
					if (!empty($product['expected_ship_date'])) {
						if ($product['expected_ship_date']->format('Y-m-d') == '2099-01-01') {
							$expected_ship_date = '';
							break;
						}
						if (empty($expected_ship_date) || $product['expected_ship_date'] > $expected_ship_date) {
							$expected_ship_date = $product['expected_ship_date'];
						}
					}
				}
				if (!empty($expected_ship_date)) $sales_order->update(['promised_ship_date' => $expected_ship_date->format('Y-m-d')]);
			}

			// create total records
			$sales_order->create_totals($data['totals']);

			// create freight records
			if (!empty($data['freight'])) $sales_order->create_freight($data['freight']);

			// crm
			if (!empty($data['send_to_crm'])) {
				try {
					$customer = $sales_order->get_customer();
					$hubspot = new api_hubspot;
					$hubspot->update_company($customer);
					//$hubspot->update_transaction($sales_order);
				}
				catch (Exception $e) {
					// fail silently
				}
			}

			// admin notes
			if (!empty($data['admin_notes'])) {
				foreach ($data['admin_notes'] as $admin_note) {
					$sales_order->create_admin_note($admin_note);
				}
			}

			// handle status figuring - this sends the order created notification, if desired
			$sales_order->create_initial_order_status($data['customer_notes'], $data['notify']);

			// check to see if any products are out of stock and notify the customer
			if (!$sales_order->is_stock_shippable() && CK\fn::check_flag($data['notify'])) $sales_order->send_out_of_stock_notification();

			prepared_query::transaction_commit($savepoint_id);
			return $sales_order;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to create sales order: '.$e->getMessage());
		}
	}

	public static function create_from_cart(ck_cart $cart) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (!$cart->has_customer()) throw new CKSalesOrderException('Failed to retrieve customer for order creation.');

			$customer = $cart->get_customer();

			// pull all details out and build the structure
			$default = $customer->get_addresses('default')->get_header();
			$shipping = $cart->get_shipping_address()->get_header();
			$billing = $cart->get_billing_address()->get_header();

			$default_state = !empty($default['state_region_code'])?$default['state_region_code']:$default['state'];
			$shipping_state = !empty($shipping['state_region_code'])?$shipping['state_region_code']:$shipping['state'];
			$billing_state = !empty($billing['state_region_code'])?$billing['state_region_code']:$billing['state'];

			$__FLAG = request_flags::instance();

			$shipment = $cart->get_shipments('active');
			$payments = $cart->get_payments($shipment['cart_shipment_id']);
			$payment = $payments[0];

			$dpm = $customer->has_credit()&&$customer->has_terms()?$customer->get_header('legacy_dealer_pay_module'):0;
			// they have an account, they've chosen to bill to it, and not free shipping
			$dsm = $customer->has('own_shipping_account')&&!is_null($shipment['shipment_account_choice'])&&$shipment['shipment_account_choice']!=4&&$shipment['shipping_method_id']!=48?1:0;
			if ($shipment['shipment_account_choice'] == 0 && $shipment['shipping_method_id'] != 48) $dsm = 1; // if the customer has choosen to ship on alternate account, we'll merge paths here

			$order = [
				'header' => [
					'customers_id' => $customer->id(),
					'customers_extra_logins_id' => $cart->get_header('customers_extra_logins_id'),

					'customers_name' => $default['first_name'].' '.$default['last_name'],
					'customers_company' => $default['company_name'],
					'customers_street_address' => $default['address1'],
					'customers_suburb' => $default['address2'],
					'customers_city' => $default['city'],
					'customers_postcode' => $default['postcode'],
					'customers_state' => $default_state,
					'customers_country' => $default['country'],
					'customers_telephone' => $default['telephone'],
					'customers_email_address' => $customer->get_header('email_address'),
					'customers_address_format_id' => $default['country_address_format_id'],
					'customer_type' => $customer->get_header('customer_type'),
					'customer_dept' => 0,

					'delivery_name' => $shipping['first_name'].' '.$shipping['last_name'],
					'delivery_company' => $shipping['company_name'],
					'delivery_street_address' => $shipping['address1'],
					'delivery_suburb' => $shipping['address2'],
					'delivery_city' => $shipping['city'],
					'delivery_postcode' => $shipping['postcode'],
					'delivery_state' => $shipping_state,
					'delivery_country' => $shipping['country'],
					'delivery_telephone' => $shipping['telephone'],
					'delivery_address_format_id' => $shipping['country_address_format_id'],

					'billing_name' => $billing['first_name'].' '.$billing['last_name'],
					'billing_company' => $billing['company_name'],
					'billing_street_address' => $billing['address1'],
					'billing_suburb' => $billing['address2'],
					'billing_city' => $billing['city'],
					'billing_postcode' => $billing['postcode'],
					'billing_state' => $billing_state,
					'billing_country' => $billing['country'],
					'billing_address_format_id' => $billing['country_address_format_id'],

					'channel' => !empty($cart->user_admin())?'phone':'web',
					'orders_sales_rep_id' => $customer->get_header('account_manager_id'),
					'sales_team_id' => $customer->get_header('sales_team_id'),
					'admin_id' => $cart->user_admin(),
					'date_purchased' => date('Y-m-d H:i:s'),
					'last_modified' => date('Y-m-d H:i:s'),
					'currency' => 'USD',
					'currency_value' => 1,
					'customers_referer_url' => @$_SESSION['ref_url'],
					'orders_weight' => $cart->get_estimated_shipped_weight(),

					'orders_status' => self::STATUS_CST,
					'orders_sub_status' => self::$sub_status_map['CST']['Uncat'],

					'split_order' => $cart->get_split_requested(),
					'dropship' => $shipment['blind']?1:0,
					'use_reclaimed_packaging' => $shipment['reclaimed_materials']?1:0,
					'packing_slip' => 0,
					'legacy_order' => FALSE,

					'payment_method_id' => $payment['payment_method_id'],
					'purchase_order_number' => !empty($shipment['order_po_number'])?$shipment['order_po_number']:'',

					'customers_fedex' => $cart->get_fedex_account_number(TRUE),
					'customers_ups' => $cart->get_ups_account_number(TRUE),
					'dpm' => $dpm,
					'dsm' => $dsm,
					'ups_account_number' => $cart->get_ups_account_number(),
					'fedex_account_number' => $cart->get_fedex_account_number(),
					'fedex_bill_type' => $cart->get_shipping_bill_type(),
				],
				'payment' => [
					'braintree_customer_id' => $customer->get_header('braintree_customer_id'),
					'payment_method_id' => $payment['payment_method_id'],
					'payment_card_id' => $payment['payment_card_id'],
					'pp_nonce' => $payment['pp_nonce'],
					'payment_transaction_id' => NULL,
					'amount' => NULL,
					'marketplace' => FALSE,
					'admin_id' => $cart->user_admin(),
					'cart' => $cart,
				],
				'freight' => [],
				'products' => [],
				'totals' => [],
				'customer_notes' => $cart->get_header('customer_comments'),
				'admin_notes' => [],
				'check_fraud' => TRUE,
				'send_to_crm' => FALSE, // moved this to the success page
				'notify' => isset($_SESSION['admin_as_user'])&&!$__FLAG['send_confirmation_email']?FALSE:TRUE,
			];

			switch (ck_payment_method_lookup::instance()->lookup_by_id($payment['payment_method_id'], 'method_code')) {
				case 'net10':
					$order['header']['net10_po'] = $payment['payment_po_number'];
					break;
				case 'net15':
					$order['header']['net15_po'] = $payment['payment_po_number'];
					break;
				case 'net30':
					$order['header']['net30_po'] = $payment['payment_po_number'];
					break;
				case 'net45':
					$order['header']['net45_po'] = $payment['payment_po_number'];
					break;
				case 'net60':
					$order['header']['net60_po'] = $payment['payment_po_number'];
					break;
			}

			if ($cart->has_products()) {
				foreach ($cart->get_products() as $product) {
					if ($product['option_type'] == ck_cart::$option_types['INCLUDED']) continue; // we'll grab these separately

					$display_prices = json_decode($product['price_options_snapshot']);
					if (json_last_error() == JSON_ERROR_NONE) {
						$display_price = $display_prices->display;
						$price_reason = $product['option_type']==ck_cart::$option_types['NONE']?$display_prices->reason:'option';
						$price_reason_code = !empty(ck_cart::$price_reasons_key[$price_reason])?ck_cart::$price_reasons_key[$price_reason]:-1;
					}
					else {
						$display_price = $product['unit_price'];
						$price_reason = 'unknown';
						$price_reason_code = -1;
					}

					$unit_price = $product['unit_price'];
					$is_quote = 0;
					if ($product['quoted_price']) {
						$unit_price = $product['quoted_price'];
						$is_quote = 1;
						$price_reason_code = !empty(ck_cart::$price_reasons_key[$product['quoted_reason']])?ck_cart::$price_reasons_key[$product['quoted_reason']]:ck_cart::$price_reasons_key['quote'];
					}

					$order['products'][] = [
						'products_id' => $product['products_id'],
						'parent_products_id' => $product['parent_products_id'],
						'products_model' => $product['listing']->get_header('products_model'),
						'products_name' => $product['listing']->get_header('products_name'), // theoretically, for options this should be using the custom name, but it's always used the default
						'products_quantity' => $product['quantity'],
						'products_price' => $unit_price,
						'final_price' => $unit_price,
						'display_price' => $display_price,
						'price_reason' => $price_reason_code,
						'option_type' => $product['option_type'],
						'is_quote' => 0,
						'exclude_forecast' => $price_reason=='special'?1:0
					];
				}
			}

			if ($cart->has_quotes()) {
				foreach ($cart->get_quotes() as $quote) {
					if ($quote['quote']->has_direct_account_manager()) $order['header']['orders_sales_rep_id'] = $quote['quote']->get_account_manager()->id();

					foreach ($quote['quote']->get_products() as $product) {
						if ($product['option_type'] == ck_cart::$option_types['INCLUDED']) continue; // we'll grab these separately

						$display_price = $product['price'];
						$price_reason = $product['option_type']==ck_cart::$option_types['NONE']?'quote':'option';
						$price_reason_code = !empty(ck_cart::$price_reasons_key[$price_reason])?ck_cart::$price_reasons_key[$price_reason]:-1;

						$order['products'][] = [
							'products_id' => $product['products_id'],
							'parent_products_id' => $product['parent_products_id'],
							'products_model' => $product['listing']->get_header('products_model'),
							'products_name' => $product['listing']->get_header('products_name'), // theoretically, for options this should be using the custom name, but it's always used the default
							'products_quantity' => $product['quantity'],
							'products_price' => $product['price'],
							'final_price' => $product['price'],
							'display_price' => $display_price,
							'price_reason' => $price_reason_code,
							'option_type' => $product['option_type'],
							'is_quote' => 1,
							'exclude_forecast' => 0
						];
					}
				}
			}

			foreach ($cart->get_totals() as $order_total) {
				$order['totals'][] = $order_total;

				if ($order_total['class'] == 'ot_shipping' && $cart->is_freight() && !empty($order_total['external_id'])) $order['freight']['list_total'] = $order_total['value'];
				if ($order_total['class'] == 'ot_total') $order['payment']['amount'] = round($order_total['value'], 2);
			}

			// the freight flag would have been set during the order totals processing, specifically in the ot_shipping.php module
			if ($cart->is_freight()) {
				$order['freight']['residential'] = $shipment['residential']?1:0;
				$order['freight']['liftgate'] = $shipment['freight_needs_liftgate']?1:0;
				$order['freight']['inside'] = $shipment['freight_needs_inside_delivery']?1:0;
				$order['freight']['limitaccess'] = $shipment['freight_needs_limited_access']?1:0;
			}

			if (!empty($cart->user_admin()) && $cart->has('admin_comments')) {
				$order['admin_notes'][] = [
					'orders_note_user' => $cart->user_admin(),
					'orders_note_text' => $cart->get_header('admin_comments')
				];
			}

			$sales_order = self::create($order);

			// coupon tracking - replaces legacy method for "applying credit", which was misnamed anyway
			if (!empty($payment['payment_coupon_code'])) {
				foreach ($cart->get_totals() as $order_total) {
					// we just want to doublecheck that we've got an actual coupon total entered
					if ($order_total['class'] == 'ot_coupon') {
						prepared_query::execute('INSERT INTO coupon_redeem_track (coupon_id, customer_id, redeem_date, redeem_ip, order_id) VALUES (:coupon_id, :customers_id, NOW(), :ip_address, :orders_id)', [':coupon_id' => $payment['payment_coupon_id'], ':customers_id' => $customer->id(), ':ip_address' => $_SERVER['REMOTE_ADDR'], ':orders_id' => $sales_order->id()]);
						break;
					}
				}
			}

			// close quotes
			if ($cart->has_quotes()) {
				foreach ($cart->get_quotes() as $quote) {
					$quote['quote']->close_won($sales_order->id());
				}
			}

			// clear session variables
			$cart->reset_cart(TRUE);

			// unregister session variables used during checkout
			unset($_SESSION['sendto']);
			unset($_SESSION['billto']);
			unset($_SESSION['shipping']);
			unset($_SESSION['comments']);
			unset($_SESSION['admin_comments']);
			unset($_SESSION['dropship']);
			unset($_SESSION['use_reclaimed_packaging']);
			unset($_SESSION['purchase_order_number']);
			unset($_SESSION['net10_po']);
			unset($_SESSION['net15_po']);
			unset($_SESSION['net30_po']);
			unset($_SESSION['net45_po']);
			unset($_SESSION['net60_po']);
			unset($_SESSION['packing_slip']);
			unset($_SESSION['po_marker']);
			unset($_SESSION['customers_fedex']);
			unset($_SESSION['customers_ups']);
			unset($_SESSION['shipping_account_choice']);
			unset($_SESSION['PayPal_osC']);
			unset($_SESSION['split_order']);
			unset($_SESSION['any_out_of_stock']);
			unset($_SESSION['freight_opts_liftgate']);
			unset($_SESSION['freight_opts_inside']);
			unset($_SESSION['freight_opts_limitaccess']);
			unset($_SESSION['paypalNonce']);
			$cart->reset_cart(TRUE);
			unset($_SESSION['cc_id']);
			unset($_SESSION['cot_shipping']);
			unset($_SESSION['cot_coupon']);
			unset($_SESSION['cot_tax']);
			unset($_SESSION['cot_total']);

			prepared_query::transaction_commit($savepoint_id);

			return $sales_order;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (CKCartException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			$msg = 'There was a cart retrieval error completing your order, please try again or contact <a href="mailto:'.$this->get_contact_email().'">'.$this->get_contact_email().'</a>.';
			if (isset($_SESSION['admin_as_user'])) $msg .= ' Admin Details: '.$e->getMessage();
			if (ck_master_archetype::debugging()) {
				throw $e;
			}
			else {
				throw new CKSalesOrderException($msg);
			}
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			if (!empty($sales_order)) $msg = 'There was an error completing your order, please try again or contact <a href="mailto:'.$sales_order->get_contact_email().'">'.$sales_order->get_contact_email().'</a>.';
			else $msg = 'There was an error completing your order, please try again or contact <a href="mailto:'.$cart->get_contact_email().'">'.$cart->get_contact_email().'</a>.';
			if (isset($_SESSION['admin_as_user'])) $msg .= ' Admin Details: '.$e->getMessage();
			if (ck_master_archetype::debugging()) {
				throw $e;
			}
			else {
				throw new CKSalesOrderException($msg);
			}
		}
	}

	public function create_payment(Array $data, &$customer_notes) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (!$data['marketplace']) {
				$token = 'AAA'; // unless payment type is cc or paypal, we dont need token

				$pm = ck_payment_method_lookup::instance()->lookup_by_id($data['payment_method_id']);

				if ($pm['method_code'] === 'creditcard_pp') $token = @$data['payment_card_id'];
				elseif ($pm['method_code'] === 'paypal') $token = @$data['pp_nonce'];

				$transaction = [
					'amount' => $data['amount'],
					'customerId' => @$data['braintree_customer_id'],
					'token' => $token,
					'authorization' => FALSE,
					'orderId' => $this->id()
				];

				$billing = $this->get_billing_address();
				$bname = explode(' ', $billing['name'], 2);

				if (!empty($billing['company'])) $transaction['company'] = $billing['company'];
				if (!empty($bname[0])) $transaction['firstName'] = $bname[0];
				if (!empty($bname[1])) $transaction['lastName'] = $bname[1];
				if (!empty($billing['street_address_1'])) $transaction['street1'] = $billing['street_address_1'];
				if (!empty($billing['street_address_2'])) $transaction['street2'] = $billing['street_address_2'];
				if (!empty($billing['city'])) $transaction['city'] = $billing['city'];
				if (!empty($billing['state'])) $transaction['state'] = $billing['state'];
				if (!empty($billing['zip'])) $transaction['postalCode'] = $billing['zip'];
				if (!empty($billing['country']) && $country_code = ck_address2::get_country($billing['country'])) $transaction['countryCode'] = $country_code['countries_iso_code_2'];

				$paymentSvcApi = new PaymentSvcApi();

				switch ($pm['method_code']) {
					case 'creditcard_pp':
						if (empty($transaction['token'])) {
							throw new CKSalesOrderException('There was an error authorizing your credit card payment - please try again or contact your <a href="mailto:'.$this->get_contact_email().'">CK Sales Team</a>.');
						}

						$result = json_decode($paymentSvcApi->authorizeCCTransaction($transaction), TRUE);

						if ($result['result']['status'] != 'authorized') {
							throw new CKSalesOrderException('There was an error authorizing your credit card - here\'s the message from our processor: '.$result['result']['message']);
						}

						if (!empty($result['result']['transactionId'])) {
							try {
								prepared_query::execute('UPDATE orders SET paymentsvc_id = :paymentsvc_id WHERE orders_id = :orders_id', [':paymentsvc_id' => $result['result']['transactionId'], ':orders_id' => $this->id()]);

								$this->skeleton->rebuild('header');
							}
							catch (Exception $e) {
								$admin_note = ['orders_note_text' => 'CC Record Non-Fatal Error: this order failed when recording successful CC transaction ID: '.$result['result']['transactionId'].'. Error Message: '.$e->getMessage()];
								if (!empty($data['admin_id'])) $admin_note['orders_note_user'] = $data['admin_id'];
								$this->create_admin_note($admin_note);

								if (empty($customer_notes)) $customer_notes = 'Automated Note - Payment Error, see admin notes.';
								else $customer_notes .= ' | Automated Note - Payment Error, see admin notes.';
							}
						}
						else {
							ob_start();
							var_dump($result);
							$msg = ob_get_clean();

							$admin_note = ['orders_note_text' => 'CC Authorize Non-Fatal Error: this order failed when authorizing the CC - it appears the CC has authorized successfully, but we can\'t be certain without research. Details: '.$msg];
							if (!empty($data['admin_id'])) $admin_note['orders_note_user'] = $data['admin_id'];
							$this->create_admin_note($admin_note);

							if (empty($customer_notes)) $customer_notes = 'Automated Note - Payment Error, see admin notes.';
							else $customer_notes .= ' | Automated Note - Payment Error, see admin notes.';
						}
						break;
					case 'paypal':
						if (empty($transaction['token'])) {
							throw new CKSalesOrderException('There was an error capturing your paypal payment - please try again or contact your <a href="mailto:'.$this->get_contact_email().'">CK Sales Team</a>.');
						}

						$transaction['authorization'] = TRUE;
						$result = json_decode($paymentSvcApi->authorizePaypalTransaction($transaction), TRUE);

						if ($result['result']['status'] != 'settling') {
							prepared_query::execute_after_transaction('UPDATE ck_cart_payments SET pp_nonce = NULL WHERE pp_nonce = :pp_nonce', [':pp_nonce' => $transaction['token']]);
							throw new CKSalesOrderException('There was an error capturing your paypal payment - here\'s the message from our processor: '.$result['result']['message']);
						}

						if (!empty($result['result']['transactionId'])) {
							try {
								prepared_query::execute('UPDATE orders SET paymentsvc_id = :paymentsvc_id WHERE orders_id = :orders_id', [':paymentsvc_id' => $result['result']['transactionId'], ':orders_id' => $this->id()]);

								$this->skeleton->rebuild('header');

								$data['payment_transaction_id'] = $result['result']['transactionId'];
								$this->create_credit($data);

								//$this->add_checkout_credit('paypal', $result['result']['transactionId'], $charge_amount);
							}
							catch (CKSalesOrderException $e) {
								$admin_note = ['orders_note_text' => 'PP Account Credit Non-Fatal Error: this order failed to create an account credit for a successful PP transaction. Error Message: ['.$e->getMessage().']'];
								if (!empty($data['admin_id'])) $admin_note['orders_note_user'] = $data['admin_id'];
								$this->create_admin_note($admin_note);

								if (empty($customer_notes)) $customer_notes = 'Automated Note - Payment Error, see admin notes.';
								else $customer_notes .= ' | Automated Note - Payment Error, see admin notes.';
							}
							catch (Exception $e) {
								$admin_note = ['orders_note_text' => 'PP Record Non-Fatal Error: this order failed when recording successful PP transaction ID: '.$result['result']['transactionId'].'. Error Message: ['.$e->getMessage().']'];
								if (!empty($data['admin_id'])) $admin_note['orders_note_user'] = $data['admin_id'];
								$this->create_admin_note($admin_note);

								if (empty($customer_notes)) $customer_notes = 'Automated Note - Payment Error, see admin notes.';
								else $customer_notes .= ' | Automated Note - Payment Error, see admin notes.';
							}
						}
						else {
							ob_start();
							var_dump($result);
							$msg = ob_get_clean();

							$admin_note = ['orders_note_text' => 'PP Authorize Non-Fatal Error: this order failed when authorizing PP account - it appears the payment has authorized successfully, but we can\'t be certain without research. Details:
							'.$msg];
							if (!empty($data['admin_id'])) $admin_note['orders_note_user'] = $data['admin_id'];
							$this->create_admin_note($admin_note);

							if (empty($customer_notes)) $customer_notes = 'Automated Note - Payment Error, see admin notes.';
							else $customer_notes .= ' | Automated Note - Payment Error, see admin notes.';
						}
						break;
					case 'accountcredit':
						$result = json_decode($paymentSvcApi->authorizeAccCreditTransaction($transaction), TRUE);
						break;
					case 'checkmo':
					default:
						$result = json_decode($paymentSvcApi->authorizeMoTransaction($transaction), TRUE);
						break;
					case 'net10':
						break;
					case 'net15':
						break;
					case 'net30':
						break;
					case 'net45':
						break;
					case 'net60':
						break;
					/*case 'customer_service':
						break;*/
				}
			}
			elseif (in_array($data['payment_method_id'], [2])) $this->create_credit($data);

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to add payment to order.');
		}
	}

	// basic logic is from legacy functions, rather than a new evaluation of what and how we should be recording this information
	public function create_credit(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$payment = [
				'customer_id' => $this->get_header('customers_id'),
				'payment_amount' => $data['amount'],
				'payment_method_id' => $data['payment_method_id'],
				'payment_ref' => $data['payment_transaction_id']
			];

			$payment_id = prepared_query::insert('INSERT INTO acc_payments (customer_id, payment_amount, payment_method_id, payment_ref, payment_date) VALUES (:customer_id, :payment_amount, :payment_method_id, :payment_ref, NOW())', CK\fn::parameterize($payment));

			// credit memo, delete payment references, don't know if that's
			if ($data['payment_method_id'] == 12) prepared_query::execute('DELETE FROM acc_payments_to_orders WHERE order_id = :order_id', [':order_id' => $this->id()]);
			else prepared_query::execute('INSERT INTO acc_payments_to_orders (payment_id, order_id, amount) VALUES (:payment_id, :order_id, :amount)', [':payment_id' => $payment_id, ':order_id' => $this->id(), ':amount' => $data['amount']]);

			$admin_id = !empty($data['admin_id'])?$data['admin_id']:self::$solutionsteam_id;

			$historyData = [
				'transaction_type' => $data['amount']>0?'Insert Credit':'Insert Reverse Credit',
				'admin_id' => $admin_id,
				'order_id' => $this->id(),
				'payment_id' => $payment_id,
				'customer_id' => $this->get_header('customers_id'),
			];

			prepared_query::execute('INSERT INTO acc_transaction_history (transaction_type, transaction_date, admin_id, order_id, payment_id, customer_id) VALUES (:transaction_type, NOW(), :admin_id, :order_id, :payment_id, :customer_id)', CK\fn::parameterize($historyData));

			prepared_query::transaction_commit($savepoint_id);

			return $payment_id;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			$msg = 'There was an error entering a credit on this order.  Payment has been rendered.';
			if (isset($_SESSION['admin_as_user'])) $msg .= ' Admin Details: '.$e->getMessage();
			if (ck_master_archetype::debugging()) {
				echo $msg;
				throw $e;
			}
			else {
				throw new CKSalesOrderException($msg);
			}
		}
	}

	private $in_context_demand = [];

	public function create_products(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		$sufficient = TRUE;
		$specials_excess_note = '';

		try {
			foreach ($data as $prod) {
				$prod['orders_id'] = $this->id();

				$product = new ck_product_listing($prod['products_id']);

				if (empty($this->in_context_demand[$product->get_ipn()->id()])) $this->in_context_demand[$product->get_ipn()->id()] = 0;
				$this->in_context_demand[$product->get_ipn()->id()] += $prod['products_quantity'];

				if (!isset($prod['price_reason'])) {
					if (!isset($prod['final_price'])) $prod['price_reason'] = ck_cart::$price_reasons_key['original'];
					else {
						$prod['price_reason'] = ck_cart::$price_reasons_key['quote'];
						$prices = $product->get_price();
						foreach ($prices as $price_reason => $price) {
							if ($price_reason == 'display') continue;
							if (bccomp($prod['final_price'], $price, 2) == 0) {
								$prod['price_reason'] = ck_cart::$price_reasons_key[$price_reason];
								break;
							}
						}
					}
				}

				$sufficient = $sufficient && TRUE;
				$prod['expected_ship_date'] = ck_product_listing::calc_ship_date(0, 'Y-m-d');

				$remaining_stock = $product->get_inventory('available') - $this->in_context_demand[$product->get_ipn()->id()];

				$prod['qty_available_when_booked'] = $product->get_inventory('available');

				if ($remaining_stock < 0) {
					$sufficient = FALSE;
					$prod['expected_ship_date'] = $product->is('always_available')?ck_product_listing::calc_ship_date($product->get_header('lead_time'), 'Y-m-d'):'2099-01-01';
				}

				if ($remaining_stock <= 0) $product->get_ipn()->turn_off_discontinued();

				if ($prod['option_type'] == ck_cart::$option_types['INCLUDED']) tep_check_and_expire_specials($product->id(), $remaining_stock);
				else {
					if (!isset($prod['specials_excess'])) $prod['specials_excess'] = tep_check_and_expire_specials($product->id(), $remaining_stock);

					if (!empty($prod['specials_excess'])) {
						if (empty($specials_excess_note)) $specials_excess_note = "The customer purchased more than the qty on sale of the following items:\n";
						$specials_excess_note .= $product->get_header('products_model').': '.abs($prod['specials_excess'])." units\n";
					}
				}

				// handling included options price appropriately is all the responsibility of this method and the update products method
				if (!isset($prod['final_price'])) $prod['final_price'] = $product->get_price('display');
				if (!isset($prod['products_price'])) $prod['products_price'] = $prod['final_price'];

				if (!$product->is('is_bundle')) $bundle_revenue = 0;
				else {
					$bundle_revenue = $prod['products_price'];
					$prod['products_price'] = 0;
				}

				if (empty($prod['products_model'])) $prod['products_model'] = $product->get_header('products_model');
				if (empty($prod['products_name'])) $prod['products_name'] = $product->get_header('products_name');
				if (!isset($prod['display_price'])) $prod['display_price'] = $prod['final_price'];
				if (!isset($prod['exclude_forecast']) && $prod['price_reason'] == ck_cart::$price_reasons_key['special']) $prod['exclude_forecast'] = 1;

				$params = new ezparams($prod);
				$orders_products_id = prepared_query::insert('INSERT INTO orders_products ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', CK\fn::parameterize($prod));

				if ($opts = $product->get_options('included')) {
					$options = [];

					$running_total = 0;

					foreach ($opts as $opt) {
						// we need to deal with a potentially different qty if this is a bundle
						$option_qty = $prod['products_quantity'];
						$option_revenue = 0;
						if ($product->is('is_bundle')) {
							$option_qty *= $opt['bundle_quantity'];
							$option_line_revenue = bcmul($bundle_revenue, $opt['bundle_revenue_pct'], 6);
							$option_revenue = bcdiv($option_line_revenue, $opt['bundle_quantity'], 6);

							$running_total = bcadd($running_total, $option_line_revenue, 6);
						}

						$options[] = [
							//'orders_id'
							'products_id' => $opt['products_id'],
							'parent_products_id' => !empty($prod['parent_products_id'])?$prod['parent_products_id']:$product->id(),
							'products_model' => $opt['listing']->get_header('products_model'),
							'products_name' => $opt['name'],
							'products_quantity' => $option_qty,
							'products_price' => $option_revenue,
							'final_price' => 0,
							'display_price' => 0,
							'is_quote' => !empty($prod['is_quote'])?$prod['is_quote']:0,
							'price_reason' => ck_cart::$price_reasons_key['option'],
							'option_type' => ck_cart::$option_types['INCLUDED'],
							'specials_excess' => NULL,
							//'expected_ship_date'
							'exclude_forecast' => !empty($prod['exclude_forecast'])?$prod['exclude_forecast']:0
						];
					}

					$sufficient = $this->create_products($options) && $sufficient;

					if ($product->is('is_bundle') && empty($prod['parent_products_id']) && bccomp($running_total, $prod['final_price'], 4) != 0) {
						$diff = bcsub($prod['final_price'], $running_total, 4);
						prepared_query::execute('UPDATE orders_products SET products_price = :diff WHERE orders_products_id = :orders_products_id', [':diff' => $diff, ':orders_products_id' => $orders_products_id]);
					}
				}

				$product->get_ipn()->rebuild_inventory_data();
			}

			$this->skeleton->rebuild('products');
			ck_ipn2::clear_prebuilt_inventory();
			$this->update(['orders_weight' => $this->get_estimated_shipped_weight()]);

			if (!empty($specials_excess_note)) $this->create_admin_note(['orders_note_text' => $specials_excess_note]);

			prepared_query::transaction_commit($savepoint_id);
			return $sufficient;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to add product to order: '.$e->getMessage());
		}
	}

	public function delete_product($orders_products_id, $delete_children=TRUE) {
		$savepoint_id = prepared_query::transaction_begin();
		try {
			$product = $this->get_products($orders_products_id);

			//delete main product
			prepared_query::execute('DELETE FROM orders_products WHERE orders_products_id = :orders_products_id', [':orders_products_id' => $orders_products_id]);

			//delete any po allocations
			prepared_query::execute('DELETE FROM purchase_order_to_order_allocations WHERE order_product_id = :order_product_id', [':order_product_id' => $orders_products_id]);

			//check for allocated serials and unallocate
			if ($product['ipn']->is('serialized') && !empty($product['allocated_serials'])) {
				foreach($product['allocated_serials'] as $serial) {
					$serial->unallocate();
				}
			}

			//check for included products
			if ($delete_children && !empty($included_options_on_order = $this->get_products_by_parent($product['products_id'], TRUE))) {
				foreach ($included_options_on_order as $included) {
					$this->delete_product($included['orders_products_id'], FALSE);

					if ($included['ipn']->is('serialized') && !empty($included['allocated_serials'])) {
						foreach ($product['allocated_serials'] as $serial) {
							$serial->unallocate();
						}
					}
				}
			}

			$product['ipn']->rebuild_inventory_data();
			$this->skeleton->rebuild('products');
			$this->update(['orders_weight' => $this->get_estimated_shipped_weight()]);
			if (!$this->refiguring_delayed()) $this->refigure_totals();

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CkSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CkSalesOrderException('Failed to remove product from order: '.$e->getMessage());
		}
	}

	public function update_product($orders_products_id, Array $data) {
		//if the quantity equals zero we'll just delete it
		if (isset($data['products_quantity']) && $data['products_quantity'] <= 0) {
			$this->delete_product($orders_products_id);
			return;
		}

		$savepoint_id = prepared_query::transaction_begin();
		try {
			$product = $this->get_products($orders_products_id);

			if (!$product['ipn']->is('is_bundle')) {
				$bundle_revenue = 0;
				if (isset($data['final_price'])) $data['products_price'] = $data['final_price'];
			}
			else {
				$data['products_price'] = 0;
				if (isset($data['final_price'])) $bundle_revenue = $data['final_price'];
				else $bundle_revenue = $product['final_price'];
			}

			$params = new ezparams($data);
			prepared_query::execute('UPDATE orders_products SET '.$params->update_cols(TRUE).' WHERE orders_products_id = :orders_products_id', $params->query_vals(['orders_products_id' => $orders_products_id], TRUE));

			if (isset($data['products_quantity']) && $data['products_quantity'] != $product['quantity']) {
				$options = [];

				$children_products = $this->get_products_by_parent($product['products_id'], TRUE);
				$allocated_serials = [];
				$order_po_allocations = [];
				$increase_quantity = FALSE;

				$ca = new api_channel_advisor;
				if ($ca::is_authorized()) $ca->update_quantity($product['listing']->get_ipn());

				if ($data['products_quantity'] < $product['quantity']) prepared_query::execute('UPDATE serials s JOIN serials_history sh ON s.id = sh.serial_id SET s.status = 2, sh.order_id = NULL, sh.order_product_id = NULL WHERE sh.order_product_id = :orders_products_id', [':orders_products_id' => $orders_products_id]);
				elseif ($data['products_quantity'] > $product['quantity']) $increase_quantity = TRUE;

				if ($product['listing']->get_inventory('allocated') <= $product['listing']->get_inventory('on_hand')) {
					prepared_query::execute('UPDATE orders_products SET po_waiting = NULL WHERE orders_products_id = :orders_products_id', [':orders_products_id' => $orders_products_id]);
				}

				if (!empty($children_products)) {
					foreach ($children_products as $child) {
						if ($child['ipn']->is('serialized')) {
							if (!isset($allocated_serials[$child['products_id']])) $allocated_serials[$child['products_id']] = [];
							$allocated_serials[$child['products_id']] = array_merge($allocated_serials[$child['products_id']], $child['allocated_serials']);
						}

						if (!isset($order_po_allocations[$child['products_id']])) $order_po_allocations[$child['products_id']] = [];
						$order_po_allocations[$child['products_id']] = array_merge($order_po_allocations[$child['products_id']], prepared_query::fetch('SELECT purchase_order_product_id, quantity FROM purchase_order_to_order_allocations WHERE order_product_id = :order_product_id', cardinality::SET, [':order_product_id' => $child['orders_products_id']]));

						$this->delete_product($child['orders_products_id'], FALSE);
					}

					$running_total = 0;

					foreach ($product['listing']->get_options('included') as $included_option) {
						// we need to deal with a potentially different qty if this is a bundle
						$option_qty = $data['products_quantity'];
						$option_revenue = 0;

						if ($product['ipn']->is('is_bundle')) {
							$option_qty *= $included_option['bundle_quantity'];
							$option_line_revenue = bcmul($bundle_revenue, $included_option['bundle_revenue_pct'], 6);
							$option_revenue = bcdiv($option_line_revenue, $included_option['bundle_quantity'], 6);

							$running_total = bcadd($running_total, $option_line_revenue, 6);
						}

						$options[] = [
							'orders_id' => $this->id(),
							'products_id' => $included_option['products_id'],
							'parent_products_id' => $product['products_id'],
							'products_model' => $included_option['listing']->get_header('products_model'),
							'products_name' => $included_option['name'],
							'products_quantity' => $option_qty,
							'products_price' => $option_revenue,
							'final_price' => 0,
							'display_price' => 0,
							'is_quote' => !empty($product['is_quote'])?$product['is_quote']:0,
							'price_reason' => ck_cart::$price_reasons_key['option'],
							'option_type' => ck_cart::$option_types['INCLUDED'],
							'specials_excess' => NULL,
							//'expected_ship_date'
							'exclude_forecast' => !empty($product['exclude_forecast'])?$product['exclude_forecast']:0
						];
					}

					$this->create_products($options);

					if ($increase_quantity) {
						foreach ($this->get_products_by_parent($product['products_id'], TRUE) as $new_child) {

							//recreate po allocations
							if (!empty($order_po_allocations[$new_child['products_id']])) {
								foreach ($order_po_allocations[$new_child['products_id']] as $allocation) {

									$allocate_quantity = min($new_child['quantity'], $allocation['quantity']);

									prepared_query::execute('INSERT INTO purchase_order_to_order_allocations (purchase_order_product_id, order_product_id, quantity, modified) VALUES (:purchase_order_product_id, :order_product_id, :quantity, NOW())', [':purchase_order_product_id' => $allocation['purchase_order_product_id'], ':order_product_id' => $new_child['orders_products_id'], ':quantity' => $allocate_quantity]);

									$new_child['quantity'] -= $allocate_quantity;
									if ($new_child['quantity'] <= 0) break;
								}
							}

							//recreate serial allocations
							if (!empty($allocated_serials[$new_child['products_id']])) {
								for ($i=0; $i<$new_child['quantity']; $i++) {
									if (empty($allocated_serials[$new_child['products_id']])) break;
									$serial = array_shift($allocated_serials[$new_child['products_id']]);
									$serial->allocate($this->id(), $new_child['orders_products_id']);
								}
							}

						}
					}

					if ($product['ipn']->is('is_bundle') && isset($data['final_price']) && bccomp($running_total, $data['final_price'], 4) != 0) {
						$diff = bcsub($data['final_price'], $running_total, 4);
						prepared_query::execute('UPDATE orders_products SET products_price = :diff WHERE orders_products_id = :orders_products_id', [':diff' => $diff, ':orders_products_id' => $orders_products_id]);
					}
				}
			}
			elseif (isset($data['final_price']) && $data['final_price'] != $product['final_price'] && $product['ipn']->is('is_bundle')) {
				$ratio = bcdiv($data['final_price'], $product['final_price'], 6);
				$running_total = 0;

				if ($children_products = $this->get_products_by_parent($product['products_id'], TRUE)) {
					foreach ($children_products as $child) {
						$products_price = bcmul($child['revenue'], $ratio, 6);
						// by definition, if we're in this section, qty hasn't changed, and by definition the child qty is a multiple of the bundle qty
						$bundle_multiple = $child['quantity'] / $product['quantity'];
						$running_total = bcadd($running_total, bcmul($products_price, $bundle_multiple, 6), 6);
						prepared_query::execute('UPDATE orders_products SET products_price = :products_price WHERE orders_products_id = :orders_products_id', [':products_price' => $products_price, ':orders_products_id' => $child['orders_products_id']]);
					}

					// any rounding errors get added to the bundle parent
					if (bccomp($running_total, $data['final_price'], 4) != 0) {
						$diff = bcsub($data['final_price'], $running_total, 4);
						prepared_query::execute('UPDATE orders_products SET products_price = :diff WHERE orders_products_id = :orders_products_id', [':diff' => $diff, ':orders_products_id' => $orders_products_id]);
					}
				}
			}

			$product['ipn']->rebuild_inventory_data();
			$this->skeleton->rebuild('products');
			$this->update(['orders_weight' => $this->get_estimated_shipped_weight()]);
			if (!$this->refiguring_delayed()) $this->refigure_totals();

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CkSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CkSalesOrderException('Failed to update product on order: '.$e->getMessage());
		}
	}

	public function move_product_to_other_order($source_orders_products_id, ck_sales_order $new_order, $new_qty) {
		$savepoint = self::transaction_begin();
		try {
			$product = $this->get_products($source_orders_products_id);

			$new_orders_products_id = prepared_query::insert('INSERT INTO orders_products (orders_id, products_id, parent_products_id, option_type, products_model, products_name, products_price, final_price, display_price, price_reason, products_quantity, cost, qty_available_when_booked, expected_ship_date, is_quote, exclude_forecast, vendor_stock_item, specials_excess) SELECT :new_orders_id, products_id, parent_products_id, option_type, products_model, products_name, products_price, final_price, display_price, price_reason, :new_qty, cost, qty_available_when_booked, expected_ship_date, is_quote, exclude_forecast, vendor_stock_item, specials_excess FROM orders_products WHERE orders_products_id = :orders_products_id AND orders_id = :orders_id', [':new_orders_id' => $new_order->id(), ':new_qty' => $new_qty, ':orders_products_id' => $source_orders_products_id, ':orders_id' => $this->id()]);

			prepared_query::execute('INSERT INTO purchase_order_to_order_allocations (purchase_order_product_id, order_product_id, quantity, modified, admin_id) SELECT purchase_order_product_id, :new_orders_products_id, LEAST(quantity, :new_qty), NOW(), admin_id FROM purchase_order_to_order_allocations WHERE order_product_id = :source_orders_products_id', [':new_orders_products_id' => $new_orders_products_id, ':new_qty' => $new_qty, ':source_orders_products_id' => $source_orders_products_id]);
			prepared_query::execute('UPDATE purchase_order_to_order_allocations SET quantity = GREATEST(quantity - :new_qty, 0) WHERE order_product_id = :source_orders_products_id', [':new_qty' => $new_qty, ':source_orders_products_id' => $source_orders_products_id]);
			prepared_query::execute('DELETE FROM purchase_order_to_order_allocations WHERE quantity <= 0 AND order_product_id = :source_orders_products_id', [':source_orders_products_id' => $source_orders_products_id]);

			$old_qty = $product['quantity'] - $new_qty;
			if ($old_qty <= 0) prepared_query::execute('DELETE FROM orders_products WHERE orders_products_id = :orders_products_id', [':orders_products_id' => $source_orders_products_id]);
			else prepared_query::execute('UPDATE orders_products SET products_quantity = :old_qty WHERE orders_products_id = :orders_products_id', [':old_qty' => $old_qty, ':orders_products_id' => $source_orders_products_id]);

			$product['ipn']->rebuild_inventory_data();
			$this->skeleton->rebuild('products');
			$this->update(['orders_weight' => $this->get_estimated_shipped_weight()]);
			$this->refigure_totals();

			self::transaction_commit($savepoint);
		}
		catch (CkSalesOrderException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CkSalesOrderException('Failed to split or move product on order: '.$e->getMessage());
		}
	}

	public function create_totals(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		$map = [
			'ot_custom' => [
				'title' => 'Custom:',
				'sort_order' => 2,
			],
			'ot_shipping' => [
				'title' => 'Shipping:',
				'sort_order' => 200,
			],
			'ot_coupon' => [
				'title' => 'Coupon:',
				'sort_order' => 720,
			],
			'ot_tax' => [
				'title' => 'Tax:',
				'sort_order' => 780,
			],
			'ot_total' => [
				'title' => 'Total:',
				'sort_order' => 800,
			],
		];

		try {
			foreach ($data as $ttl) {
				$ttl['orders_id'] = $this->id();

				if (empty($ttl['title'])) $ttl['title'] = $map[$ttl['class']]['title'];
				if (empty($ttl['text'])) $ttl['text'] = $ttl['class']=='ot_total'?'<strong>'.CK\text::monetize($ttl['value']).'</strong>':CK\text::monetize($ttl['value']);
				if (empty($ttl['sort_order'])) $ttl['sort_order'] = $map[$ttl['class']]['sort_order'];
				if (empty($ttl['actual_shipping_cost'])) $ttl['actual_shipping_cost'] = $ttl['value'];
				if (!isset($ttl['external_id']) && $ttl['class'] == 'ot_shipping') {
					$ttl['external_id'] = is_numeric($ttl['title'])?$ttl['title']:0;
				}

				$params = new ezparams($ttl);
				$orders_total_id = prepared_query::insert('INSERT INTO orders_total ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', CK\fn::parameterize($ttl));
			}

			$this->skeleton->rebuild('totals');
			$this->skeleton->rebuild('simple_totals');
			$this->skeleton->rebuild('estimated_costs');

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to add total to order.');
		}
	}

	public function remove_total($orders_total_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('DELETE FROM orders_total WHERE orders_total_id = :orders_total_id AND orders_id = :orders_id', [':orders_total_id' => $orders_total_id, ':orders_id' => $this->id()]);

			$this->skeleton->rebuild('totals');
			$this->skeleton->rebuild('simple_totals');
			$this->skeleton->rebuild('estimated_costs');

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to remove total');
		}
	}

	public function update_total($orders_total_id, Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		$map = [
			'ot_custom' => [
				'title' => 'Custom:',
				'sort_order' => 2,
			],
			'ot_shipping' => [
				'title' => 'Shipping:',
				'sort_order' => 200,
			],
			'ot_coupon' => [
				'title' => 'Coupon:',
				'sort_order' => 720,
			],
			'ot_tax' => [
				'title' => 'Tax:',
				'sort_order' => 780,
			],
			'ot_total' => [
				'title' => 'Total:',
				'sort_order' => 800,
			],
		];

		try {
			if (empty($data['text']) && isset($data['value'])) $data['text'] = !empty($data['class'])&&$data['class']=='ot_total'?'<strong>'.CK\text::monetize($data['value']).'</strong>':CK\text::monetize($data['value']);
			if (empty($data['actual_shipping_cost']) && isset($data['value'])) $data['actual_shipping_cost'] = $data['value'];
			if (!isset($data['external_id']) && !empty($data['class'])) $data['external_id'] = $data['class']=='ot_shipping'?$data['title']:0;

			$params = new ezparams($data);

			prepared_query::execute('UPDATE orders_total SET '.$params->update_cols(TRUE).' WHERE orders_total_id = :orders_total_id', $params->query_vals(['orders_total_id' => $orders_total_id], TRUE));

			$this->skeleton->rebuild('totals');
			$this->skeleton->rebuild('simple_totals');
			$this->skeleton->rebuild('estimated_costs');

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to remove total');
		}
	}

	public function update_term_po_number($new_term_po_number) {
		$savepoint_id = prepared_query::transaction_begin();
		try {
			prepared_query::execute('UPDATE orders SET ' . $this->get_header('payment_method_code') . '_po = :new_term_po_number WHERE orders_id = :orders_id', [':new_term_po_number' => $new_term_po_number, ':orders_id' => $this->id()]);
			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to update net terms po number');
		}
	}

	// managing taxes in avalara now
	// public function set_tax_exempt_status($status) {
	// 	$this->update(['tax_exempt' => CK\fn::check_flag($status)?1:0]);
	// }
	//
	// public function unset_tax_exempt_status($status=NULL) {
	// 	$this->update(['tax_exempt' => NULL]);
	// }

	public function refigure_totals() {
		$this->delay_refiguring = FALSE;

		$savepoint_id = prepared_query::transaction_begin();

		try {
			$tax_already_exists = FALSE;
			$ot_total_id = NULL;

			// no longer managing tax emption in matrix. we'll request tax no matter what
			// if (!$this->is('tax_exempt')) {
			require_once(__DIR__.'/../../admin/includes/functions/avatax.php');
			$tax = avatax_get_tax($this->id());

			// }
			// else $tax = NULL;

			$subtotal = $this->get_product_subtotal();

			foreach ($this->get_totals('consolidated') as $total) {

				if ($total['class'] != 'tax' && $total['class'] != 'total') $subtotal += $total['value'];

				// while we are touching it let's get the ot_total id for the update below
				if ($total['class'] == 'total') $ot_total_id = $total['orders_total_id'];

				// manage tax
				if ($total['class'] == 'tax') {
					$tax_already_exists = TRUE;
					if (!$tax) $this->remove_total($total['orders_total_id']);
					else {
						$this->update_total($total['orders_total_id'], ['value' => $tax]);
						// since there is tax we'll add it to the subtotal
						$subtotal += $tax;
					}
				}
			}

			// if tax does exist, but we didn't have it in our totals then we'll create it
			if ($tax && !$tax_already_exists) {
				$this->create_totals([['class' => 'ot_tax', 'value' => $tax]]);
				$subtotal += $tax;
			}

			// update the ot_total
			$this->update_total($ot_total_id, ['value' => $subtotal]);

			$this->skeleton->rebuild('totals');
			$this->skeleton->rebuild('simple_totals');
			$this->skeleton->rebuild('estimated_costs');

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to refigure totals');
		}
	}

	public function create_freight(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['orders_id'] = $this->id();
			$params = new ezparams($data);
			$freight_shipment_id = prepared_query::insert('INSERT INTO ck_freight_shipment ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', $params->query_vals(NULL, TRUE));

			foreach ($this->get_products() as $prod) {
				$vendors_id = $prod['ipn']->is('drop_ship')?$prod['ipn']->get_header('vendors_id'):0;
				prepared_query::execute('INSERT INTO ck_freight_shipment_items (freight_shipment_id, location_vendor_id, products_id) VALUES (:freight_shipment_id, :vendors_id, :products_id)', [':freight_shipment_id' => $freight_shipment_id, ':vendors_id' => $vendors_id, ':products_id' => $prod['products_id']]);
			}

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to add product to order: '.$e->getMessage());
		}
	}

	public function create_admin_note(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['orders_id'] = $this->id();
			if (empty($data['orders_note_user'])) $data['orders_note_user'] = !empty($_SESSION['login_id'])?$_SESSION['login_id']:self::$solutionsteam_id;

			$params = new ezparams($data);
			prepared_query::execute('INSERT INTO orders_notes ('.$params->insert_cols().', orders_note_created) VALUES ('.$params->insert_params(TRUE).', NOW())', CK\fn::parameterize($data));

			$this->skeleton->rebuild('notes');

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			$msg = 'There was an error adding an admin note to this order.';
			if (isset($_SESSION['admin_as_user'])) $msg .= ' Admin Details: '.$e->getMessage();
			if (ck_master_archetype::debugging()) {
				echo $msg;
				throw $e;
			}
			else {
				throw new CKSalesOrderException($msg);
			}
		}
	}

	private function create_orders_status_history(Array $data, $type='updated') {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['orders_id'] = $this->id();
			if (empty($data['admin_id'])) {
				if ($admin = ck_admin::login_instance()) $data['admin_id'] = $admin->id();
				else $data['admin_id'] = self::$solutionsteam_id;
			}
			if (empty($data['date_added'])) $data['date_added'] = self::NOW()->format('Y-m-d H:i:s');

			$params = new ezparams($data);
			$orders_status_history_id = prepared_query::insert('INSERT INTO orders_status_history ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', CK\fn::parameterize($data));

			$this->skeleton->rebuild('status_history');

			prepared_query::transaction_commit($savepoint_id);

			// send notification if necessary
			if ($data['customer_notified'] == 1) $this->send_order_update_notification($type);

			return TRUE;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			$msg = 'There was an error creating status history record for this order.';
			if (isset($_SESSION['admin_as_user']) || $_SESSION['current_context'] == 'backend') $msg .= ' Admin Details: '.$e->getMessage();
			if (ck_master_archetype::debugging()) {
				echo $msg;
				throw $e;
			}
			else {
				throw new CKSalesOrderException($msg);
			}
		}
	}

	public function create_initial_order_status($customer_notes, $notify) {
		$savepoint_id = prepared_query::transaction_begin();

		// this has now been superseded by class constants - and at some point we'll pull this out of the DB directly
		$orders_status_map = [
			'RTP' => 2,
			'Shipped' => 3,
			'Backorder' => 5,
			'Canceled' => 6,
			'Warehouse' => 7,
			'On Hold' => 8,
			'CST' => 11
		];

		try {
			if (!empty($this->get_header('ca_order_id'))) {
				// channel advisor orders are always imported *after* they are created at the marketplace, so this status record indicates when it was created, the next indicates when it was imported
				$orders_status_history = [
					'orders_status_id' => $orders_status_map['CST'],
					'date_added' => $this->get_header('date_purchased')->format('Y-m-d H:i:s'),
					'customer_notified' => 0,
					'comments' => 'Order Created in marketplace, waiting for payment to clear',
					'orders_sub_status_id' => self::$sub_status_map['CST']['Accounting']
				];

				$this->create_orders_status_history($orders_status_history);
			}

			$customer = $this->get_customer();

			$freight = CK\fn::check_flag(prepared_query::fetch('SELECT psc.freight FROM orders_products op JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE op.orders_id = :orders_id AND psc.freight = 1', cardinality::SINGLE, [':orders_id' => $this->id()]));

			$shipped_order_count = prepared_query::fetch('SELECT COUNT(orders_id) FROM orders WHERE customers_id = :customers_id AND orders_status = :shipped', cardinality::SINGLE, [':customers_id' => $customer->id(), ':shipped' => self::STATUS_SHIPPED]);

			$order_total = $this->get_simple_totals('total');

			// default to Customer Service / Uncat
			$orders_status_history = [
				'orders_status_id' => $this->get_header('orders_status'),
				'date_added' => self::NOW()->format('Y-m-d H:i:s'),
				'customer_notified' => $notify?1:0,
				'comments' => $customer_notes,
				'orders_sub_status_id' => $this->get_header('orders_sub_status')
			];

			// if the order is 3pl eligible we'll start off with an admin note
			$is_3pl_eligible = FALSE;
			if ($is_3pl_eligible = $this->is_3pl_eligible()) $this->create_admin_note(['orders_note_text' => 'This order can be shipped by UPS-3PL', 'orders_note_user' => self::$solutionsteam_id]);

			// send the order to the correct status
			$halt_order = FALSE;

			if (!$halt_order && $customer->is('fraud')) {
				$halt_order = TRUE;
				$orders_status_history['orders_sub_status_id'] = self::$sub_status_map['CST']['Accounting'];
				$this->create_admin_note(['orders_note_text' => 'Customer placed this order while marked as fraudulent. Accounting will need to release the customer before this order can be released']);
				$this->hold('only_hold_order');
			}

			// any credit hold status supercedes even fraud checks - if they are marked hold all orders or have terms, presumably they've already passed fraudcheck
			if (!$halt_order && ($customer->cannot_place_any_order())) {
				$this->hold('only_hold_order');
				// if this customer is marked to hold all orders, send to accounting
				$orders_status_history['orders_sub_status_id'] = self::$sub_status_map['CST']['Accounting'];
				$this->create_admin_note(['orders_note_text' => 'Customer placed order while marked as "Hold All Orders".']);
				$halt_order = TRUE;
			}

			if (!$halt_order && ($this->is_terms() && !$customer->can_place_credit_order())) {
				$this->hold('only_hold_order');
				// if this order exceeds the credit limit, send to accounting and make a note on the customer
				if ($customer->get_credit('credit_status_id') == ck_customer2::CREDIT_PREPAID) {
					$this->create_admin_note(['orders_note_text' => 'Customer placed terms order while marked "Prepaid Only"']);
				}
				elseif ($customer->get_remaining_credit() < 0) {
					$customer->update_credit_status(['comments' => 'Exceeded Credit Limit']);
					$this->create_admin_note(['orders_note_text' => 'Customer has exceeded credit limit with this order.']);
				}
				else {
					$this->create_admin_note(['orders_note_text' => 'System disallowed credit terms order - unknown why, contact dev.']);
				}
				$orders_status_history['orders_sub_status_id'] = self::$sub_status_map['CST']['Accounting'];
				$halt_order = TRUE;
			}

			if (!$halt_order && ($this->get_header('payment_method_id') == 1 && $shipped_order_count < 3 && $order_total >= 100)) {
				$paymentSvcApi = new PaymentSvcApi();
				$transactionData = json_decode($paymentSvcApi->getTransactionDetails($this->get_header('paymentsvc_id')), TRUE);

				if (!in_array($transactionData['result']['avs']['street_address_response_code'], ['M', 'A'])) {
					$orders_status_history['orders_sub_status_id'] = self::$sub_status_map['CST']['Fraud Check'];
					$halt_order = TRUE;
				}
			}

			if (!$halt_order && ($order_total > 5000)) {
				// if the order is over $5000, send to Customer Service - Accounting
				$orders_status_history['orders_sub_status_id'] = self::$sub_status_map['CST']['Accounting'];
				$halt_order = TRUE;
			}

			if (!$halt_order && ($shipped_order_count < 10 && in_array($this->get_header('payment_method_id'), [1, 2, 11]))) {
				// if the customer has placed less than 10 orders 7 it's a CC or Paypal payment, we do some extra checking to see if the order should go to fraud check:
				// - the fraud score is over 6.0
				// - the order is over $1000
				// - the order is over $500 and the shipping street is different from the billing street
				if (($this->has_fraud_score() && $this->get_fraud_score('score') > 6.0) || $order_total > 2000 || $order_total > 1000 && $this->get_shipping_address('street_address_1') != $this->get_billing_address('street_address_1')) {
					$orders_status_history['orders_sub_status_id'] = self::$sub_status_map['CST']['Fraud Check'];
					$halt_order = TRUE;
				}
				// if it doesn't meet that criteria, we're not halting
			}

			if (!$halt_order && ($this->get_estimated_shipped_weight() >= 250 || $freight)) {
				// if the order has a freight item, or it's otherwise heavy enough to go freight, it stays in CST Uncat, which is the default
				$halt_order = TRUE;
			}

			if (!$halt_order && ($this->get_header('payment_method_id') == 3 || !empty($customer_notes) || !$this->is('all_items_in_stock'))) {
				// if it's paid by check/money order, it has a customer comment, or not all items are in stock, it stays in CST Uncat, which is the default
				$halt_order = TRUE;
			}

			// if the order is 3pl eligible then we'll drop it in CST 3PL status
			if (!$halt_order && ($is_3pl_eligible)) {
				$halt_order = TRUE;
				$orders_status_history['orders_sub_status_id'] = self::$sub_status_map['CST']['3PL'];
			}

			if (!$halt_order) {
				// if we haven't run into a halting condition, send through to RTP
				$orders_status_history['orders_status_id'] = $orders_status_map['RTP'];
				$orders_status_history['orders_sub_status_id'] = self::$sub_status_map['RTP']['Uncat'];
			}

			$this->create_orders_status_history($orders_status_history, 'created');
			if ($orders_status_history['orders_status_id'] != $this->get_header('orders_status') || $orders_status_history['orders_sub_status_id'] != $this->get_header('orders_sub_status')) {
				prepared_query::execute('UPDATE orders SET orders_status = :orders_status_id, orders_sub_status = :orders_sub_status_id WHERE orders_id = :orders_id', [':orders_status_id' => $orders_status_history['orders_status_id'], ':orders_sub_status_id' => $orders_status_history['orders_sub_status_id'], ':orders_id' => $this->id()]);
				$this->skeleton->rebuild('header');
			}

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to successfully set initial order status: '.$e->getMessage());
		}
	}

	public function hold($key=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$this->update(['released' => 0]);
			if (empty($key)) {
				$this->update_order_status(['orders_status_id' => 11, 'orders_sub_status_id' => 20]);
				$this->create_admin_note(['orders_note_text' => 'Order placed on Accounting Hold', 'orders_note_user' => $_SESSION['login_id']]);
			}
			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to hold order: '.$e->getMessage());
		}
	}

	public function release() {
		$customer = $this->get_customer();
		// we don't want to release the orders if the customer is set to fraud
		if ($customer->is('fraud')) return FALSE;

		$savepoint_id = prepared_query::transaction_begin();
		try {
			$this->update(['released' => 1]);
			$this->update_order_status(['orders_status_id' => 11, 'orders_sub_status_id' => 1]);
			$this->create_admin_note(['orders_note_text' => 'Order removed from Accounting Hold', 'orders_note_user' => $_SESSION['login_id']]);
			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to hold order: '.$e->getMessage());
		}
	}

	public function create_package(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['package']['orders_id'] = $this->id();
			if (empty($data['package']['date_time_created'])) $data['package']['date_time_created'] = self::NOW()->format('Y-m-d H:i:s');

			$params = new ezparams($data['package']);
			$orders_packages_id = prepared_query::insert('INSERT INTO orders_packages ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', CK\fn::parameterize($data['package']));

			if (!empty($data['tracking'])) {
				$data['tracking']['orders_packages_id'] = $orders_packages_id;
				if (empty($data['tracking']['date_time_created'])) $data['tracking']['date_time_created'] = self::NOW()->format('Y-m-d H:i:s');

				$params = new ezparams($data['tracking']);
				prepared_query::execute('INSERT INTO orders_tracking ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', CK\fn::parameterize($data['tracking']));
			}

			$this->skeleton->rebuild('packages');

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed adding package to order: '.$e->getMessage());
		}
	}

	public function rebuild_invoices() {
		$this->skeleton->rebuild('invoices');
	}

	public function mark_channel_advisor_shipment_exported($status) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('UPDATE orders SET ca_shipping_export_status = :status WHERE orders_id = :orders_id', [':status' => $status?1:0, ':orders_id' => $this->id()]);

			$this->skeleton->rebuild('header');

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Could not mark sales order as exported to Channel Advisor');
		}
	}

	public function ship($notify=FALSE, $note=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		$messages = [];

		try {
			if ($this->has_invoices()) throw new CKSalesOrderException('Order cannot be shipped if it already has an invoice');

			// handle labels/tracking
			if (!$this->has_packages()) throw new CKSalesOrderException('Order cannot be shipped without attaching packages first');

			$any_have_tracking = FALSE;
			$any_missing_tracking = FALSE;

			$carrier = $this->get_shipping_method('carrier');

			foreach ($this->get_packages() as $package) {
				if (!empty($package['void'])) continue;

				if (empty(trim($package['tracking_num']))) $any_missing_tracking = TRUE;
				else $any_have_tracking = TRUE;
			}

			if (!$any_have_tracking && $carrier == 'Fedex') {
				// if all of the packages are missing a tracking number and this is a fedex shipment, we can automatically generate the labels
				require_once(__DIR__.'/../../admin/new_ship_fedex.php');

				$result = new_ship_fedex_send_ship($this->id(), $this->get_header('fedex_account_number'), $this->get_header('fedex_bill_type'), $this->get_header('fedex_signature_type'), TRUE, FALSE);

				if ($result === FALSE) throw new CKSalesOrderException('There was a problem generating Fedex shipping labels. Please print them manually and try again.');
				else $messages[] = 'Fedex labels were automatically created and printed for this order.';

				$this->skeleton->rebuild('packages');
			}
			elseif ($any_missing_tracking) throw new CKSalesOrderException('Order cannot be shipped unless each package has a tracking number.');

			$tracking_infos = [];

			foreach ($this->get_packages() as $package) {
				if (!empty($package['void'])) continue;

				// if packages were created and have tracking numbers then we'll send it to after ship for tracking
				if (empty(trim($package['tracking_num']))) continue;
				if (empty($package['carrier'])) continue;
				if (in_array($this->get_customer()->get_header('customer_segment_id'), [5])) continue; // we do not send tracking info on market place orders
				
				try {
					$tracking_info = [
						'slug' => $package['carrier'],
						'tracking_number' => $package['tracking_num'],
						'customer_name' => $this->get_header('customers_name'),
						'emails' => $this->get_header('customers_email_address'),
						'order_id' => $this->id()
					];
					api_after_ship::create_tracking($tracking_info);

					$tracking_infos[] = $tracking_info;
				}
				catch (Exception $e) {
					// if we run into an aftership API error, don't hold up the entire order.
				}
			}

			foreach ($this->get_products() as $product) {
				if ($product['listing']->is('serialized') && count($product['allocated_serials']) < $product['quantity']) {
					$_SESSION['need_serials'] = TRUE;
					throw new CKSalesOrderException('Order cannot be shipped unless all serialized items have serials allocated');
				}
			}
			$_SESSION['need_serials'] = NULL;
			unset($_SESSION['need_serials']);

			$this->refigure_totals();

			// handle payment
			$paymentSvcApi = new PaymentSvcApi();

			if ($this->is_cc_capture_needed()) {
				// attempt to capture payment
				/*if ($this->has_parent_orders()) {
					$parent_order = array_pop($this->get_parent_orders());
					$paymentsvc_id = $parent_order->get_header('paymentsvc_id');
				}
				else*/ $paymentsvc_id = $this->get_header('paymentsvc_id');

				if (empty($paymentsvc_id)) throw new CKSalesOrderException('No CC Payment Found - could not capture.');

				$transaction = ['transactionId' => $paymentsvc_id, 'amount' => floatval($this->get_simple_totals('total')), 'orderId' => $this->id()];

				if ($transaction['amount'] <= 0) throw new CKSalesOrderException('Order cannot be shipped with $0 total when paid by credit card - either change the payment type or fix the total.');

				/*if ($this->has_parent_orders() || $this->has_child_orders()) {
					$res = $paymentSvcApi->partialSettlement($transaction);
					$settlement_result = json_decode($res, TRUE);
				}
				else {*/
					$res = $paymentSvcApi->settleTransaction($transaction);
					$settlement_result = json_decode($res, TRUE);
				/*}*/

				$credit = ['payment_method_id' => 1, 'payment_transaction_id' => $this->get_header('paymentsvc_id'), 'amount' => $transaction['amount']];

				if (!empty($settlement_result['authorization_expired']) || !in_array($settlement_result['result']['status'], ['submitted_for_settlement', 'settlement_pending'])) {
					ob_start();
					var_dump($settlement_result);
					$msg = ob_get_clean();
					throw new CKSalesOrderException('Card Charge was unable to be captured: ['.$msg.']');
					/*
					// if capture/settlement failed, try to charge from the beginning
					$cardToken = json_decode($paymentSvcApi->findToken($this->get_header('paymentsvc_id')), TRUE);

					if ($cardToken['result']['status'] === 'success') {
						//success ...now create a new authorization
						$transaction = [
							'amount' => $transaction['amount'],
							'customerId' => $this->get_customer()->get_header('braintree_customer_id'),
							'token' => $cardToken['result']['cardType'],
							'authorization' => TRUE,
							'orderId' => $this->id()
						];

						$auth_result = json_decode($paymentSvcApi->authorizeCCTransaction($transaction), TRUE);

						if ($auth_result['result']['status'] === 'submitted_for_settlement') {
							if ($settlement_result['result']['status'] == 'authorized') $paymentSvcApi->voidTransaction($this->get_header('paymentsvc_id'));
							if (!empty($auth_result['result']['transactionId'])) $this->update_payment_svc_id($auth_result['result']['transactionId']);

							$this->create_credit($credit);
							$messages[] = 'Card charged successfully.';
						}
						else throw new CKSalesOrderException('Card Charge was unable to be captured: '.(!empty($auth_result['result']['message'])?$auth_result['result']['message']:$auth_result['notfound']));
					}
					else throw new CKSalesOrderException('Card Charge was unable to be captured: '.$cardToken['result']['status']);
					*/
				}
				else {
					$this->create_credit($credit);
					$messages[] = 'Card charged successfully.';
				}
			}

			if (!$this->is_paid_shippable()) throw new CKSalesOrderException('Order cannot be processed due to missing payment.');

			// handle status
			$status = ['orders_status_id' => self::STATUS_SHIPPED, 'customer_notified' => $notify];
			if (!empty($note)) $status['comments'] = $note;
			$this->update_order_status($status);

			try {
				// handle invoice
				ck_invoice::create_from_sales_order($this);

				// delete payment allocations
				prepared_query::execute('DELETE FROM acc_payments_to_orders WHERE order_id = :orders_id', [':orders_id' => $this->id()]);

				if (!empty($_SESSION['login_id'])) $this->create_admin_note(['orders_note_text' => 'Order shipped by Admin', 'orders_note_user' => $_SESSION['login_id']]);

				// handle allocations
				foreach ($this->get_products() as $product) {
					// remove po allocations for the invoiced order - as we are currently, there shouldn't be any allocations at this point, but this forces it
					prepared_query::execute('DELETE FROM purchase_order_to_order_allocations WHERE order_product_id = :orders_products_id', [':orders_products_id' => $product['orders_products_id']]);
				}

				prepared_query::execute('UPDATE serials_assignments sa JOIN orders_products op ON sa.orders_products_id = op.orders_products_id SET sa.fulfilled = 1 WHERE op.orders_id = :orders_id', [':orders_id' => $this->id()]);

				if (CK\fn::check_flag($this->get_customer()->get_header('has_received_mousepad') == 0)) prepared_query::execute('UPDATE customers SET has_received_mousepad = 1 WHERE customers_id = :customers_id', [':customers_id' => $this->get_customer()->id()]);

				$messages[] = 'Order successfully shipped.';
			}
			catch (Exception $e) {
				$messages[] = 'Order successfully shipped, with errors: ['.$e->getMessage().']';
			}

			prepared_query::transaction_commit($savepoint_id);

			return $messages;
		}
		catch (CKSalesOrderException $e) {
			if (!empty($tracking_infos)) {
				foreach ($tracking_infos as $ti) {
					$results = api_after_ship::delete_tracking(['tracking_number' => $ti['tracking_number'], 'slug' => $ti['slug']]);
				}
			}

			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			foreach ($tracking_infos as $ti) {
				$results = api_after_ship::delete_tracking(['tracking_number' => $ti['tracking_number'], 'slug' => $ti['slug']]);
			}

			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to ship order: '.$e->getMessage());
		}
	}

	public function unship($notify=FALSE, $note=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		$messages = [];

		try {
			if ($this->has_invoices()) {
				// only removing the first (latest) invoice, to copy the existing logic
				$invoice = new ck_invoice($this->get_latest_invoice('invoice_id'));
				$invoice->remove();
			}

			$this->create_admin_note(['orders_note_text' => 'Order unshipped by Admin', 'orders_note_user' => $_SESSION['login_id']]);

			// update the status
			$status = ['orders_status_id' => self::STATUS_UNSHIP, 'customer_notified' => $notify];
			if (!empty($note)) $status['comments'] = $note;
			$this->update_order_status($status);

			$messages[] = 'Order has been successfully unshipped';

			// if this order has packages we want to delete the tracking that was assigned
			if ($this->has_packages()) {
				foreach ($this->get_packages() as $packages) {
					if (empty($packages['tracking_num'])) continue;
					if (empty($packages['carrier'])) continue;
					if (in_array($this->get_customer()->get_header('customer_segment_id'), [5])) continue; // we do not send tracking info for market place orders therefore there isn't anything to delete

					try {
						$tracking_info = ['tracking_number' => $packages['tracking_num'], 'slug' => $packages['carrier']];
						$results = api_after_ship::delete_tracking($tracking_info);
					}
					catch (Exception $e) {
						// if we run into an aftership API error, don't hold up the entire order.
					}
				}
			}

			prepared_query::transaction_commit($savepoint_id);

			return $messages;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to unship order: '.$e->getMessage());
		}
	}

	public function cancel($orders_canceled_reason_id, $notify=FALSE, $note=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		$messages = [];

		try {
			// if this is a cc order void the authorization - if it's captured, leave it alone
			if ($this->is_cc_capture_needed()) {
				if (!empty($this->get_header('paymentsvc_id'))) {
					$paymentSvcApi = new PaymentSvcApi();

					$result = json_decode($paymentSvcApi->voidTransaction($this->get_header('paymentsvc_id')), TRUE);

					if ($result['result']['status'] != 'authorization_voided') $messages[] = 'Auth was not voided: '.$result['result']['status'];
				}
			}
			elseif ($this->is_cc()) {
				$messages[] = 'Card is no longer refunded automatically. Please make sure this is done manually.';
			}

			// delete PO allocations, and de-allocate any serials
			foreach ($this->get_products() as $product) {
				prepared_query::execute('DELETE FROM purchase_order_to_order_allocations WHERE order_product_id = :orders_products_id', [':orders_products_id' => $product['orders_products_id']]);

				foreach ($product['allocated_serials'] as $serial) {
					$serial->unallocate();
				}
			}

			// delete payment allocations
			prepared_query::execute('DELETE FROM acc_payments_to_orders WHERE order_id = :orders_id', [':orders_id' => $this->id()]);

			// update the status
			$status = ['orders_status_id' => self::STATUS_CANCELED, 'customer_notified' => $notify, 'orders_canceled_reason_id' => $orders_canceled_reason_id];
			if (!empty($note)) $status['comments'] = $note;
			$this->update_order_status($status);
			$this->create_admin_note(['orders_note_text' => 'Order canceled by Admin', 'orders_note_user' => $_SESSION['login_id']]);

			$messages[] = 'Order has been successfully canceled';

			prepared_query::transaction_commit($savepoint_id);

			return $messages;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to cancel order: '.$e->getMessage());
		}
	}

	public function uncancel($notify=FALSE, $note=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$status = ['orders_status_id' => self::STATUS_UNCANCEL, 'customer_notified' => $notify];
			if (!empty($note)) $status['comments'] = $note;
			$this->update_order_status($status);
			$this->create_admin_note(['orders_note_text' => 'Order uncanceled by Admin', 'orders_note_user' => $_SESSION['login_id']]);
			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to uncancel order: '.$e->getMessage());
		}
	}

	public function update(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (empty($data['last_modified'])) $data['last_modified'] = self::NOW()->format('Y-m-d H:i:s');
			$params = new ezparams($data);
			prepared_query::execute('UPDATE orders SET '.$params->update_cols(TRUE).' WHERE orders_id = :orders_id', $params->query_vals(['orders_id' => $this->id()], TRUE));

			$this->skeleton->rebuild();

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Error updating order: '.$e->getMessage());
		}
	}

	public function change_account_manager($admin_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (!empty($admin_id)) {
				$account_manager = new ck_admin($admin_id);

				if (!$account_manager->is('account_manager')) throw new CKSalesOrderException('Cannot set account manager to admin who is not set up to be an account manager.');

				$this->update(['orders_sales_rep_id' => $account_manager->id(), 'sales_team_id' => $account_manager->has_sales_team()?$account_manager->get_sales_team()['team']->id():NULL]);
			}
			else $this->update(['orders_sales_rep_id' => NULL]);

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to change account manager: '.$e->getMessage());
		}
	}

	public function change_sales_team($team_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (!empty($team_id)) {
				$sales_team = new ck_team($team_id);

				if (!$sales_team->is('sales_team')) throw new CKSalesOrderException('Cannot set sales team to team that is not set up to be a sales team.');

				if ($this->has_account_manager()) throw new CKSalesOrderException('Cannot set sales team separately from the account manager.');
			}

			$this->update(['sales_team_id' => $team_id]);

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to change sales team: '.$e->getMessage());
		}
	}

	public function update_order_status(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		$UNSHIP = FALSE;
		$UNCANCEL = FALSE;

		try {
			// normalize the data
			if (empty($data['orders_status_id'])) $data['orders_status_id'] = $this->get_header('orders_status');

			if (!isset($data['orders_sub_status_id'])) $data['orders_sub_status_id'] = $this->get_header('orders_sub_status');
			elseif (empty($data['orders_sub_status_id'])) $data['orders_sub_status_id'] = NULL;

			$data['customer_notified'] = CK\fn::check_flag(@$data['customer_notified'])?1:0;

			// check for disallowed status changes
			if ($this->get_header('orders_status') == self::STATUS_SHIPPED && $data['orders_status_id'] != self::STATUS_SHIPPED) {
				if ($data['orders_status_id'] != self::STATUS_UNSHIP) throw new CKSalesOrderException('Order status cannot be changed once the order is shipped.');

				$UNSHIP = TRUE;

				$data['orders_status_id'] = self::STATUS_CST;
				$data['orders_sub_status_id'] = self::$sub_status_map['CST']['Uncat'];
			}
			elseif ($this->get_header('orders_status') == self::STATUS_CANCELED && $data['orders_status_id'] != self::STATUS_CANCELED) {
				if ($data['orders_status_id'] != self::STATUS_UNCANCEL) throw new CKSalesOrderException('Order status cannot be changed once the order is canceled.');

				$UNCANCEL = TRUE;

				$data['orders_status_id'] = self::STATUS_CST;
				$data['orders_sub_status_id'] = self::$sub_status_map['CST']['Uncat'];
			}

			// check for substatuses that don't belong to the parent status - no fatal errors here, just massage if necessary
			if (!empty($data['orders_sub_status_id'])) {
				if (!in_array($data['orders_status_id'], [self::STATUS_CST, self::STATUS_RTP, self::STATUS_WAREHOUSE])) $data['orders_sub_status_id'] = NULL;

				if ($data['orders_status_id'] == self::STATUS_CST) {
					$found = FALSE;
					foreach (self::$sub_status_map['CST'] as $sub_status_id) {
						if ($data['orders_sub_status_id'] == $sub_status_id) {
							$found = TRUE;
							break;
						}
					}
					if (!$found) $data['orders_sub_status_id'] = self::$sub_status_map['CST']['Uncat'];
				}

				if ($data['orders_status_id'] == self::STATUS_RTP) {
					$found = FALSE;
					foreach (self::$sub_status_map['RTP'] as $sub_status_id) {
						if ($data['orders_sub_status_id'] == $sub_status_id) {
							$found = TRUE;
							break;
						}
					}
					if (!$found) $data['orders_sub_status_id'] = self::$sub_status_map['RTP']['Uncat'];
				}

				if ($data['orders_status_id'] == self::STATUS_WAREHOUSE) {
					$found = FALSE;
					foreach (self::$sub_status_map['Warehouse'] as $sub_status_id) {
						if ($data['orders_sub_status_id'] == $sub_status_id) {
							$found = TRUE;
							break;
						}
					}
					if (!$found) $data['orders_sub_status_id'] = self::$sub_status_map['Warehouse']['Uncat'];
				}

                $mailer = service_locator::get_mail_service();

				// send internal notes
				if ($data['orders_sub_status_id'] == self::$sub_status_map['CST']['Work Order'] && $data['orders_sub_status_id'] != $this->get_header('orders_sub_status')) {
                    $mailer->create_mail()
                    ->set_subject('Order# '.$this->id().' has been placed in Work Order Status')
                    ->add_to('receiving@cablesandkits.com')
                    ->set_from('CablesAndKits.com','accounting@cablesandkits.com')
                    ->set_body(
                        'Order# <a href="//www.cablesandkits.com/admin/orders_new.php?oID='.
                        $this->id().'&action=edit">'.$this->id().
                        '</a> has been placed in Work Order Status'
                    );

				}
				elseif ($data['orders_sub_status_id'] == self::$sub_status_map['CST']['Dealers'] && $data['orders_sub_status_id'] != $this->get_header('orders_sub_status')) {
                    $mailer->create_mail()
                        ->set_subject('Order# '.$this->id().' has been placed in Customer Service Dealers')
                        ->add_to($this->get_contact_email())
                        ->set_from('CablesAndKits.com','accounting@cablesandkits.com')
                        ->set_body(
                            'Order# <a href="//www.cablesandkits.com/admin/orders_new.php?oID='
                            .$this->id().'&action=edit">'.$this->id()
                            .'</a> has been placed in Customer Service Dealers'
                        );
				}
			}

			$order = [];

			if ($data['orders_status_id'] == self::STATUS_CANCELED) {
				$order['orders_canceled_reason_id'] = $data['orders_canceled_reason_id'];
				unset($data['orders_canceled_reason_id']);
			}

			$notification_type = 'updated';
			if ($data['customer_notified']) {
				if ($data['orders_status_id'] == self::STATUS_SHIPPED) {
					if ($data['orders_status_id'] != $this->get_header('orders_status')) $notification_type = 'first-shipped';
					else $notification_type = 'shipped';
				}
			}

			if ($data['orders_status_id'] != $this->get_header('orders_status')) $order['orders_status'] = $data['orders_status_id'];
			if ($data['orders_sub_status_id'] != $this->get_header('orders_sub_status')) $order['orders_sub_status'] = $data['orders_sub_status_id'];

			if ($UNCANCEL) $order['orders_canceled_reason_id'] = NULL;

			if (!empty($order)) $this->update($order);

			$this->create_orders_status_history($data, $notification_type);

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKSalesOrderException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKSalesOrderException('Failed to update status: '.$e->getMessage());
		}
	}

	public function gtm_sent() {
		prepared_query::execute('UPDATE orders SET gtm_data_sent = 1 WHERE orders_id = :orders_id', [':orders_id' => $this->id()]);
		// rebuild the header without a full query
		$header = $this->get_header();
		$this->skeleton->rebuild('header');
		$header['gtm_data_sent'] = 1;
		$this->skeleton->load('header', $header);
	}

	public function update_payment_svc_id($paymentsvc_id) {
		prepared_query::execute('UPDATE orders SET paymentsvc_id = :paymentsvc_id WHERE orders_id = :orders_id', [':paymentsvc_id' => $paymentsvc_id, ':orders_id' => $this->id()]);
		$header = $this->get_header();
		$this->skeleton->rebuild('header');
		$header['paymentsvc_id'] = $paymentsvc_id;
		$this->skeleton->load('header', $header);
	}

	public static function move_rtp_to_warehouse($orders_ids=[]) {
		foreach ($orders_ids as $orders_id) {
			prepared_query::execute('INSERT INTO orders_status_history (orders_id, orders_status_id, orders_sub_status_id, date_added, customer_notified) SELECT :orders_id, 7, 26, NOW(), 0 FROM orders WHERE orders_id = :orders_id AND orders_status = 2', [':orders_id' => $orders_id]);
			prepared_query::execute("INSERT INTO orders_notes (orders_id, orders_note_user, orders_note_text, orders_note_created, orders_note_modified, shipping_notice) SELECT :orders_id, :admin_id, 'Pick List Printed - This order may already be picked', NOW(), NOW(), 1 FROM orders WHERE orders_id = :orders_id AND orders_status = 2", [':orders_id' => $orders_id, ':admin_id' => $_SESSION['perms']['admin_id']]);
			prepared_query::execute('UPDATE orders SET orders_status = 7, orders_sub_status = 26 WHERE orders_id = :orders_id AND orders_status = 2', [':orders_id' => $orders_id]);
		}
	}

	public static function day_end_batch_actions(DateTime $date=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (empty($date)) {
				$date = new DateTime;
				$date->sub(new DateInterval('PT21H')); // if we're after 9PM, we're getting todays orders, if we're before, we're getting yesterdays orders
			}

			if ($shipped_orders = prepared_query::fetch('SELECT DISTINCT o.orders_id FROM orders o JOIN orders_status_history osh ON o.orders_id = osh.orders_id AND o.orders_status = osh.orders_status_id WHERE o.orders_status = :shipped AND DATE(osh.date_added) = :date', cardinality::COLUMN, [':shipped' => self::STATUS_SHIPPED, ':date' => $date->format('Y-m-d')])) {
				/*
				 * foreach ($shipped_orders as $orders_id) {
					try {
						// nothing here for now
					}
					catch (Exception $e) {
						// fail gracefully
					}
				}*/
			}
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to perform day-end batch order actions.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function add_recipient(Array $data) {
		if (!empty($this->get_recipients($data['email']))) return FALSE;

		$savepoint = self::transaction_begin();
		$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);
		try {
			self::query_execute('INSERT INTO orders_notification_recipients (orders_id, email, name, admin_id) VALUES (:orders_id, :email, :name, :admin_id)', cardinality::NONE, [':orders_id' => $this->id(), ':email' => $data['email'], ':name' => $data['name'], ':admin_id' => $user->id()]);

			$this->skeleton->rebuild('recipients');

			self::transaction_commit($savepoint);
		}
		catch (CKSalesOrderException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKSalesOrderException('Failed to add recipient to order: '.$e->getMessage());
		}
	}

	public function delete_recipient($orders_notification_recipient_id) {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('DELETE FROM orders_notification_recipients WHERE orders_notification_recipient_id = :orders_notification_recipient_id AND orders_id = :orders_id', cardinality::NONE, [':orders_notification_recipient_id' => $orders_notification_recipient_id, ':orders_id' => $this->id()]);

			$this->skeleton->rebuild('recipients');

			self::transaction_commit($savepoint);
		}
		catch (CKSalesOrderException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKSalesOrderException('Failed to add recipient to order: '.$e->getMessage());
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/

	public function send_payment_request() {
		// this might be better conceived as setting and returning the access token rather than building and sending the email,
		// but since we don't have a better way to do that at the moment, it all goes in here
        $mailer = service_locator::get_mail_service();
		$token = hash('sha256', ck_site_user::random_bytes(32));

		prepared_query::execute('UPDATE orders SET order_access_token = :order_access_token WHERE orders_id = :orders_id', [':order_access_token' => $token, ':orders_id' => $this->id()]);

		$link = 'https:'.HTTPS_SERVER.'/account_direct_order.php?token='.$token;

		$body = "Hey ".$this->get_header('customers_name').",<br><br>\n\n";
		$body .= "We are super excited for the order you've placed. In order to maximize your security and privacy, we ask you to log into your account and add your credit card information under your profile for this order.<br>\n";
		$body .= "Once your credit card information is attached to your account, we will be able to process your order.<br><br>\n\n";
		$body .= '<a href="'.$link.'">Log in to add your Credit Card</a><br>'."\n\n";
		$body .= "If you don't know your login information, just use the \"Forget your password?\" link on the login page to reset it.<br><br>\n\n";
		$body .= "If you are unable to use the link above, copy and paste the following URL into your browser to add a card to your account:<br>\n";
		$body .= '<a href="'.$link.'">'.$link.'</a>'."<br><br>\n\n";
		$body .= "Thank you,<br><br>\n\n";
		$body .= 'CK Sales Team';

        $mail = $mailer->create_mail();
		$mail->set_subject('Action Needed to Complete your Order from CablesAndKits.Com - Order Number '.$this->id());
		$mail->set_body($body);
		$mail->set_from($this->get_contact_email(), 'CablesAndKits.com Sales Team');
		$mail->add_to($this->get_header('customers_email_address'), $this->get_header('customers_name'));

		if ($this->has_recipients()) {
			foreach($this->get_recipients() as $recipient) {
				$mail->add_to($recipient['email'], $recipient['name']);
			}
		}

        $mailer->send($mail);
    }

	public function send_order_update_notification($type='updated') {
        $mailer = service_locator::get_mail_service();
		try {
			$mail = $mailer->create_mail();

			$mail->set_from($this->get_contact_email(), 'CablesAndKits.com Sales Team');

			// if extra login email isn't empty, send email to it
			if (!empty($this->get_header('extra_logins_email_address'))) {
				$mail->add_to($this->get_header('extra_logins_email_address'), $this->get_header('extra_logins_firstname').' '.$this->get_header('extra_logins_lastname'));
			}

			// if there is no extra login, or extra login is set to copy main login, add main login email to recipients
			if (empty($this->get_header('extra_logins_email_address')) || $this->is('extra_logins_copy_account')) {
				if (empty($this->get_header('customers_email_address'))) $email_address = $this->get_customer()->get_header('email_address');
				else $email_address = $this->get_header('customers_email_address');
				$mail->add_to($email_address, $this->get_header('customers_name'));
			}

			if ($this->has_recipients()) {
				foreach($this->get_recipients() as $recipient) {
					$mail->add_to($recipient['email'], $recipient['name']);
				}
			}

			require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
			require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
			require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

			$content = new ck_content;

			$content->fqdn = 'https://'.PRODUCTION_FQDN;
			$content->media = 'https://media.cablesandkits.com';

			$content->order_number = $this->id();
			$content->date = date('M d, Y');
			$content->store_address = nl2br(STORE_NAME_ADDRESS);
			$content->contact_phone = $this->get_contact_phone();
			$content->contact_email = $this->get_contact_email();
			$content->date_purchased = $this->get_header('date_purchased')->format('m/d/Y');
			$content->date_purchased_long = $this->get_header('date_purchased')->format('l d F, Y');
			$content->payment_method = $this->get_header('payment_method_label');

			$content->customer_name = !empty($this->get_header('extra_logins_email_address'))?$this->get_header('extra_logins_firstname').' '.$this->get_header('extra_logins_lastname'):$this->get_header('customers_name');

			$histories = $this->get_status_history();
			$history = array_pop($histories);
			if (!empty($history['comments'])) $content->order_comments = $history['comments'];

			if (!$this->is('released')) {
				$customer = $this->get_customer();

				if ($customer->has_credit() && $customer->cannot_place_any_order()) $content->cannot_place_any_order = 1;
				elseif ($customer->get_credit('credit_status_id') == ck_customer2::CREDIT_PREPAID) $content->prepaid_only = 1;
				elseif (!$customer->can_place_credit_order($this->get_simple_totals('total'))) {
					$content->over_limit = 1;
					$content->remaining_credit = CK\text::monetize($customer->get_remaining_credit());
				}
				else $content->unreleased = 1;
			}

			if ($this->has_terms_po_number()) $content->{'ponum?'} = $this->get_terms_po_number();
			if ($this->has_ref_po_number()) $content->{'purchase_order_number?'} = $this->get_ref_po_number();

			$sold_to = $this->get_address();
			$ship_to = $this->get_shipping_address();
			$bill_to = $this->get_billing_address();
			$content->customer = [
				'format'.$this->get_header('customers_address_format_id') => 1,
				'name' => $sold_to['name'],
				'address1' => $sold_to['street_address_1'],
				'city' => $sold_to['city'],
				'state' => $sold_to['state'],
				'postcode' => $sold_to['zip'],
				'country' => $sold_to['country'],
				'sold_to_telephone' => $sold_to['phone'],
				'sold_to_email' => !empty($this->get_header('extra_logins_email_address'))?$this->get_header('extra_logins_email_address'):$this->get_header('customers_email_address')
			];
			if (!empty($sold_to['company'])) $content->customer['company_name'] = $sold_to['company'];
			if (!empty($sold_to['street_address_2'])) $content->customer['address2'] = $sold_to['street_address_2'];

			$content->delivery = [
				'format'.$this->get_header('delivery_address_format_id') => 1,
				'name' => $ship_to['name'],
				'address1' => $ship_to['street_address_1'],
				'city' => $ship_to['city'],
				'state' => $ship_to['state'],
				'postcode' => $ship_to['zip'],
				'country' => $ship_to['country']
			];
			if (!empty($ship_to['company'])) $content->delivery['company_name'] = $ship_to['company'];
			if (!empty($ship_to['street_address_2'])) $content->delivery['address2'] = $ship_to['street_address_2'];

			$content->billing = [
				'format'.$this->get_header('billing_address_format_id') => 1,
				'name' => $bill_to['name'],
				'address1' => $bill_to['street_address_1'],
				'city' => $bill_to['city'],
				'state' => $bill_to['state'],
				'postcode' => $bill_to['zip'],
				'country' => $bill_to['country']
			];
			if (!empty($bill_to['company'])) $content->billing['company_name'] = $bill_to['company'];
			if (!empty($bill_to['street_address_2'])) $content->billing['address2'] = $bill_to['street_address_2'];

			if ($this->has_products()) {
				$content->products = [];
				foreach ($this->get_products() as $product) {
					if ($product['option_type'] == ck_cart::$option_types['INCLUDED']) continue;
					if ($product['ipn']->get_header('products_stock_control_category_id') == 90) continue;

					$prod = [
						'qty' => $product['quantity'],
						'name' => $product['name'],
						'model' => $product['model'],
						'price' => CK\text::monetize($product['final_price']),
						'total' => CK\text::monetize($product['quantity'] * $product['final_price'])
					];

					$content->products[] = $prod;
				}
			}

			$content->totals = [];
			foreach ($this->get_totals('consolidated') as $total) {
				$ttl = [
					'description' => $total['title'],
					'value' => $total['display_value']
				];

				if ($total['class'] == 'shipping' && is_numeric($ttl['description'])) $ttl['description'] = $this->get_shipping_method_display('full');

				$content->totals[] = $ttl;
			}

			$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
			$cktpl->buffer = TRUE;

			switch ($type) {
				case 'created':
					$mail->set_subject('Order Confirmation from CablesAndKits.Com - Order Number '.$this->id());

					$content->{'first?'} = 1;

					break;
				case 'updated':
					$mail->set_subject('Order Update');

					$content->{'update_status?'} = $this->get_header('orders_status_name');

					break;
				case 'first-shipped':
					// if this is the order tracking email and the customer has additional tracking recipients, add them
					if ($this->get_customer()->has_contacts()) {
						foreach ($this->get_customer()->get_contacts() as $contact) {
							if ($contact['contact_type_id'] == 3) $mail->add_to($contact['email_address']);
						}
					}
					// fall through to the regular shipped logic
				case 'shipped':
					$mail->set_subject('Order Shipped');

					$content->{'update_status?'} = $this->get_header('orders_status_name');

					if ($this->has_packages()) {
						$packages = $this->get_packages();
						//prepared_query::fetch('SELECT ot.tracking_num, sm.carrier, sm.narvar_code FROM orders_tracking ot JOIN orders_packages op ON ot.orders_packages_id = op.orders_packages_id JOIN shipping_methods sm ON ot.shipping_method_id = sm.shipping_code WHERE op.orders_id = :orders_id AND ot.void = 0 AND op.void = 0 AND ot.tracking_num != '' ORDER BY sm.carrier, sm.narvar_code', cardinality::SET, [':orders_id' => $oID])) {
						$content->tracking = [];
						if (count($packages) > 1) $content->{'tracking_multiple?'} = 1;
						foreach ($packages as $package) {
							if (CK\fn::check_flag($package['void']) || CK\fn::check_flag($package['tracking_void'])) continue;
							if (empty($package['tracking_num'])) continue;
							if (!in_array($package['carrier'], ['UPS', 'FedEx', 'USPS', 'DHL'])) continue;
							$content->tracking[] = ['tracking_num' => $package['tracking_num']];
						}
					}

					break;
			}

			$mail->set_body($cktpl->content(DIR_FS_CATALOG.'includes/templates/email-order.mustache.html', $content), $cktpl->content(DIR_FS_CATALOG.'includes/templates/email-order-text.mustache.html', $content));

			try {
				$mailer->send($mail);
			}
			catch (Exception $e) {
				$to = $mail->get_to();
				throw new CKSalesOrderException('Failed to send notice to the following email addresses: '.implode(', ', array_keys($to)));
			}
		}
		catch (CKSalesOrderException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKSalesOrderException('Failed to send notice to customer: '.$e->getMessage());
		}
	}

	public function send_out_of_stock_notification(Array $out_of_stock_items = NULL) {
		try {
			if (empty($out_of_stock_items)) $out_of_stock_items = $this->get_out_of_stock_products();

            $mailer = service_locator::get_mail_service();
            $mail = $mailer->create_mail();

			$mail->set_from($this->get_contact_email(), 'CablesAndKits.com Sales Team');

			// if extra login email isn't empty, send email to it
			if (!empty($this->get_header('extra_logins_email_address'))) {
				$mail->add_to($this->get_header('extra_logins_email_address'), $this->get_header('extra_logins_firstname').' '.$this->get_header('extra_logins_lastname'));
			}

			// if there is no extra login, or extra login is set to copy main login, add main login email to recipients
			if (empty($this->get_header('extra_logins_email_address')) || $this->is('extra_logins_copy_account')) {
				$mail->add_to($this->get_header('customers_email_address'), $this->get_header('customers_name'));
			}

			if ($this->has_recipients()) {
				foreach($this->get_recipients() as $recipient) {
					$mail->to($recipient['email'], $recipient['name']);
				}
			}

			require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
			require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
			require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

			$content = new ck_content;

			$content->fqdn = 'https://'.PRODUCTION_FQDN;
			$content->media = 'https://media.cablesandkits.com';

			$content->contact_phone = $this->get_contact_phone();
			$content->contact_email = $this->get_contact_email();

			$content->order_number = $this->id();
			$content->out_of_stock_items = [];

			foreach ($out_of_stock_items as $item) {
				if ($item['availability_status'] != self::ASTATUS_STOCK_ISSUE || $item['ipn']->is('drop_ship')) continue;

				$expected_ship_date = $item['expected_ship_date']->format('M d');
				if ($item['expected_ship_date'] == self::DateTime('2099-01-01')) $expected_ship_date = 'will be sent within 48 hours. Please contact us if you have any concerns.';

				$available_quantity = max($item['quantity'] - abs($item['ipn']->get_inventory('available')), 0);

				$oosi = [
					'available_quantity' => $available_quantity,
					'product_image' => $item['listing']->get_image('products_image_med'),
					'item_listing' => $item['listing']->get_url(),
					'products_name' => $item['listing']->get_header('products_name'),
					'item_quantity' => $item['quantity'],
					'products_model' => $item['listing']->get_header('products_model'),
					'expected_ship_date' => $expected_ship_date,
					'on_order_quantity' => min($item['ipn']->get_inventory('on_order'), $item['quantity'] - $available_quantity)
				];

				if ($oosi['on_order_quantity'] > 0) $oosi['show_est_ship_date'] = 1;

				$content->out_of_stock_items[] = $oosi;
			}

			if (!empty($content->out_of_stock_items)) {
				$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
				$cktpl->buffer = TRUE;
	
				$mail->set_subject('Stock Notification - Order Number '.$this->id());
	
				$mail->set_body($cktpl->content(DIR_FS_CATALOG.'includes/templates/email-stock_notification.mustache.html', $content));
    
                try {
                    $mailer->send($mail);
                }catch(Exception $e) {
                    throw new CKSalesOrderException('Failed to send notice to the following email addresses: '.implode(', ', array_keys($mail->get_to())));
                }
				
				$this->create_admin_note(['orders_note_user' => self::$solutionsteam_id,'orders_note_text' => 'The customer received an automatic stock notifcation']);
			}
		}
		catch (Exception $e) {
			$this->create_admin_note(['orders_note_user' => self::$solutionsteam_id, 'orders_note_text' => 'Failed to send out of stock notice to customer '.$e->getMessage()]);
		}
	}
}

class CKSalesOrderException extends CKMasterArchetypeException {
}
?>
