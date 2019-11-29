<?php
class ck_customer2 extends ck_archetype implements ck_site_user_interface {
	use ck_site_user_trait;

	public static $validation = [
		'entry_firstname' => [
			'min_length' => 2,
			'max_length' => 32,
			'error' => 'Your First Name must contain between 2 - 32 characters.'
		],
		'entry_lastname' => [
			'min_length' => 2,
			'max_length' => 32,
			'error' => 'Your Last Name must contain between 2 - 32 characters.'
		],
		'customers_email_address' => [
			'min_length' => 6,
			'max_length' => 96,
			'error' => 'Your Email Address must contain between 6 - 96 characters.'
		],
		'entry_street_address' => [
			'min_length' => 5,
			'error' => 'Your street address must be at least contain 5 characters'
		],
		//'company_name' => ['min_length' => 2],
		'entry_postcode' => [
			'min_length' => 4,
			'error' => 'Your zip code must contain a minimum of 4 characters'
		],
		'entry_city' => [
			'min_length' => 3,
			'error' => 'Your city must contain a minimum of 3 characters'
		],
		'entry_state' => [
			'min_length' => 2,
			'error' => 'Your state must contain a minimum of 2 characters'
		],
		'entry_telephone' => [
			'min_length' => 3,
			'error' => 'Your Phone Number must contain a minimum of 3 characters.'
		],
		'password' => [
			'min_length' => 5,
			'error' => 'Your New Password must contain a minimum of 5 characters.'
		]
	];

	public static $customer_segment_map = [
		'IN' => 1,
		'EU' => 2,
		'RS' => 3,
		'BD' => 4,
		'MP' => 5,
		'ST' => 6
	];

	private static $password_reset_expiration_limit = 3600; // 1 hour

	protected static $skeleton_type = 'ck_customer_type';

	protected static $queries = [
		'customer_header' => [
			'qry' => 'SELECT c.customers_id, c.braintree_customer_id, c.customer_type, c.customers_firstname as first_name, c.customers_lastname as last_name, c.customers_email_address as email_address, c.email_domain, c.customers_default_address_id as default_address_id, CASE WHEN c.customer_type = 1 THEN 1 ELSE 0 END as dealer, c.customer_price_level_id, cpl.price_level, c.send_late_notice, c.credit_limit, c.customer_credit_status_id as credit_status_id, c.use_reclaimed_packaging, ccs.label as credit_status, ct.customer_terms_id as terms_id, ct.legacy_dealer_pay_module, ct.label as terms_label, ct.po_key as terms_po_key, ct.terms_days, ct.payment_method_id as terms_payment_method_id, c.dealer_shipping_module as own_shipping_account, c.customers_fedex as fedex_account_number, c.customers_ups as ups_account_number, c.aim_screenname, c.msn_screenname, c.company_account_contact_name, c.company_account_contact_email, c.company_account_contact_phone_number, c.customers_notes as notes, c.customers_notes_sales_rep as sales_rep_notes, c.account_manager_id, c.sales_team_id, ci.customers_info_date_account_created as date_account_created, ci.customers_info_date_account_last_modified as date_account_last_modified, ci.customers_info_date_of_last_logon as date_last_logon, ci.customers_info_number_of_logons as number_of_logons, CASE WHEN ci.customers_info_source_id = 9999 THEN so.sources_other_name ELSE s.sources_name END as referral_source, c.disable_standard_shipping, c.amazon_account, c.vendor_id, c.customers_telephone as telephone, c.customers_fax as fax, c.customers_newsletter as newsletter_subscribed, c.customer_segment_id, cs.segment, cs.segment_code, c.fraud, c.has_received_mousepad, c.vp_lookup FROM customers c LEFT JOIN customer_terms ct ON c.dealer_pay_module = ct.legacy_dealer_pay_module LEFT JOIN customer_credit_status ccs ON c.customer_credit_status_id = ccs.customer_credit_status_id LEFT JOIN customers_info ci ON c.customers_id = ci.customers_info_id LEFT JOIN sources s ON ci.customers_info_source_id = s.sources_id LEFT JOIN sources_other so ON c.customers_id = so.customers_id LEFT JOIN customer_segments cs ON c.customer_segment_id = cs.customer_segment_id LEFT JOIN customer_price_levels cpl ON c.customer_price_level_id = cpl.customer_price_level_id WHERE c.customers_id = :customers_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'password_reset' => [
			'qry' => 'SELECT NULL as customers_extra_logins_id, reset_code, reset_code_timestamp, CASE WHEN TIMESTAMPDIFF(SECOND, reset_code_timestamp, NOW()) < :expiration_limit THEN 1 ELSE 0 END as reset_code_active FROM customers WHERE customers_id = :customers_id AND reset_code IS NOT NULL UNION SELECT customers_extra_logins_id, reset_code, reset_code_timestamp, CASE WHEN TIMESTAMPDIFF(SECOND, reset_code_timestamp, NOW()) < :expiration_limit THEN 1 ELSE 0 END as reset_code_active FROM customers_extra_logins WHERE customers_id LIKE :customers_id AND reset_code IS NOT NULL',
			'cardinality' => cardinality::SET,
		],

		// this is only used by the ck_site_user trait
		'login_attempt' => [
			'qry' => 'SELECT * FROM (SELECT customers_id as account_id, customers_firstname, customers_lastname, customers_password as password, password_info, legacy_salt, customer_type, customers_email_address, customers_default_address_id, braintree_customer_id, 0 as extra_login, NULL as customers_extra_logins_id FROM customers WHERE customers_email_address LIKE :email UNION SELECT c.customers_id as account_id, ce.customers_firstname, ce.customers_lastname, ce.customers_password as password, ce.password_info, ce.legacy_salt, c.customer_type, ce.customers_emailaddress as customers_email_address, c.customers_default_address_id, c.braintree_customer_id, 1 as extra_login, ce.customers_extra_logins_id FROM customers_extra_logins ce LEFT JOIN customers c ON ce.customers_id = c.customers_id WHERE ce.customers_emailaddress LIKE :email AND ce.active = 1) c ORDER BY extra_login ASC',
			'cardinality' => cardinality::ROW
		],

		'update_password_customer' => [
			'qry' => 'UPDATE customers SET customers_password = :password, password_info = 0, legacy_salt = NULL, reset_code = NULL, reset_code_timestamp = NULL, account_setup = 1 WHERE customers_id = :customers_id',
			'cardinality' => cardinality::NONE
		],

		'update_password_extra_login' => [
			'qry' => 'UPDATE customers_extra_logins SET customers_password = :password, password_info = 0, legacy_salt = NULL, reset_code = NULL, reset_code_timestamp = NULL WHERE customers_id = :customers_id AND customers_extra_logins_id = :customers_extra_logins_id',
			'cardinality' => cardinality::NONE
		],

		'customer_header_count_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT COUNT(DISTINCT c.customers_id)',

				'from' => 'FROM customers c LEFT JOIN customers_extra_logins cel ON c.customers_id = cel.customers_id',

				//'where' => 'WHERE' // will *not* fail if we don't provide our own
			],
			'proto_opts' => [
				':customer_type' => 'c.customer_type = :customer_type',
				':email' => '(c.customers_email_address LIKE :email OR cel.customers_emailaddress LIKE :email)',
				':email_main' => 'c.customers_email_address LIKE :email_main',
				':first_name' => '(c.customers_firstname LIKE :first_name OR cel.customers_firstname LIKE :first_name)',
				':first_name_main' => 'c.customers_firstname LIKE :first_name_main',
				':last_name' => '(c.customers_lastname LIKE :last_name OR cel.customers_lastname LIKE :last_name)',
				':last_name_main' => 'c.customers_lastname LIKE :last_name_main',
			],
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'customer_header_list_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT DISTINCT',

				'from' => 'FROM customers c LEFT JOIN customer_terms ct ON c.dealer_pay_module = ct.legacy_dealer_pay_module LEFT JOIN customer_credit_status ccs ON c.customer_credit_status_id = ccs.customer_credit_status_id LEFT JOIN customers_info ci ON c.customers_id = ci.customers_info_id LEFT JOIN customers_extra_logins cel ON c.customers_id = cel.customers_id LEFT JOIN sources s ON ci.customers_info_source_id = s.sources_id LEFT JOIN sources_other so ON c.customers_id = so.customers_id LEFT JOIN customer_segments cs ON c.customer_segment_id = cs.customer_segment_id',

				//'where' => 'WHERE' // will *not* fail if we don't provide our own
			],
			'proto_opts' => [
				':customer_type' => 'c.customer_type = :customer_type',
				':email' => '(c.customers_email_address LIKE :email OR cel.customers_emailaddress LIKE :email)',
				':email_main' => 'c.customers_email_address LIKE :email',
				':first_name' => '(c.customers_firstname LIKE :first_name OR cel.customers_firstname LIKE :first_name)',
				':first_name_main' => 'c.customers_firstname LIKE :first_name',
				':last_name' => '(c.customers_lastname LIKE :last_name OR cel.customers_lastname LIKE :last_name)',
				':last_name_main' => 'c.customers_lastname LIKE :last_name',
			],
			'proto_defaults' => [
				'data_operation' => 'c.customers_id, c.customer_type, c.customers_firstname as first_name, c.customers_lastname as last_name, c.customers_email_address as email_address, c.customers_default_address_id as default_address_id, CASE WHEN c.customer_type = 1 THEN 1 ELSE 0 END as dealer, c.send_late_notice, c.credit_limit, c.customer_credit_status_id as credit_status_id, ccs.label as credit_status, ct.customer_terms_id as terms_id, ct.legacy_dealer_pay_module, ct.label as terms_label, ct.po_key as terms_po_key, ct.terms_days, ct.payment_method_id as terms_payment_method_id, c.dealer_shipping_module as own_shipping_account, c.customers_fedex as fedex_account_number, c.customers_ups as ups_account_number, c.aim_screenname, c.msn_screenname, c.company_account_contact_name, c.company_account_contact_email, c.company_account_contact_phone_number, c.customers_notes as notes, c.customers_notes_sales_rep as sales_rep_notes, c.account_manager_id, c.sales_team_id, ci.customers_info_date_account_created as date_account_created, ci.customers_info_date_account_last_modified as date_account_last_modified, ci.customers_info_date_of_last_logon as date_last_logon, ci.customers_info_number_of_logons as number_of_logons, CASE WHEN ci.customers_info_source_id = 9999 THEN so.sources_other_name ELSE s.sources_name END as referral_source, c.disable_standard_shipping, c.amazon_account, c.vendor_id, c.customers_telephone as telephone, c.customers_fax as fax, c.customers_newsletter as newsletter_subscribed, c.customer_segment_id, cs.segment, cs.segment_code, c.fraud'
			],
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'credit_status_history' => [
			'qry' => 'SELECT ccsh.customer_credit_status_history_id as credit_status_history_id, ccsh.customer_credit_status_id as credit_status_id, ccs.label as credit_status, ccsh.customer_terms_id as terms_id, ct.label as terms, ccsh.credit_limit, ccsh.comments, ccsh.admin_id, ccsh.status_date FROM customer_credit_status_history ccsh LEFT JOIN customer_credit_status ccs ON ccsh.customer_credit_status_id = ccs.customer_credit_status_id JOIN customer_terms ct ON ccsh.customer_terms_id = ct.customer_terms_id WHERE ccsh.customers_id = :customers_id ORDER BY ccsh.customer_credit_status_history_id DESC',
			'cardinality' => cardinality::SET
		],

		'contacts' => [
			'qry' => 'SELECT cc.id as contact_id, cc.email_address, cc.type_id as contact_type_id, cct.name as contact_type FROM customer_contacts cc JOIN customer_contact_types cct ON cc.type_id = cct.id WHERE cc.customer_id = :customers_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'extra_logins' => [
			'qry' => 'SELECT customers_extra_logins_id, customers_emailaddress as email_address, customers_firstname as first_name, customers_lastname as last_name, old_customers_id, active, rfq_free_ground_shipping, copy_account FROM customers_extra_logins WHERE customers_id = :customers_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'prices' => [
			'qry' => 'SELECT itc.stock_id, psc.stock_name as ipn, itc.price, itc.managed_category FROM ipn_to_customers itc JOIN products_stock_control psc ON itc.stock_id = psc.stock_id WHERE itc.customers_id = :customers_id ORDER BY itc.managed_category ASC /*AND itc.managed_category = 0*/',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'category_discounts' => [
			'qry' => 'SELECT id as discount_id, category_id as categories_id, discount as discount_pctg, status, start_date, end_date FROM ck_customer_category_discount WHERE customer_id = :customers_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'order_count' => [
			'qry' => 'SELECT DISTINCT o.orders_status as orders_status_id, o.orders_id FROM orders o JOIN orders_total ot ON o.orders_id = ot.orders_id JOIN orders_products op ON o.orders_id = op.orders_id WHERE o.customers_id = :customers_id',
			'cardinality' => cardinality::SET
		],

		'orders' => [
			'qry' => 'SELECT orders_id FROM orders WHERE customers_id = :customers_id ORDER BY date_purchased ASC',
			'cardinality' => cardinality::COLUMN
		],

		'invoices' => [
			'qry' => 'SELECT invoice_id FROM acc_invoices WHERE customer_id = :customers_id ORDER BY inv_date ASC',
			//'qry' => "SELECT ai.invoice_id, ai.inv_order_id as orders_id, ai.rma_id, ai.customers_extra_logins_id, ai.inv_date as invoice_date, ai.po_number, ai.paid_in_full, ai.invoice_rma, ai.credit_memo, ai.credit_payment_id, ai.original_invoice, ai.late_notice_date, ait.invoice_total_price as total FROM acc_invoices ai LEFT JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_total' WHERE ai.customer_id = :customers_id ORDER BY ai.inv_date ASC",
			'cardinality' => cardinality::COLUMN,
		],

		'payments' => [
			'qry' => "SELECT p.payment_id, p.payment_amount, p.payment_method_id, pm.label as payment_method_label, p.payment_ref, p.payment_date, p.customers_extra_logins_id FROM acc_payments p JOIN payment_method pm ON p.payment_method_id = pm.id WHERE p.customer_id = :customers_id GROUP BY p.payment_id ORDER BY p.payment_date ASC",
			'cardinality' => cardinality::SET,
		],

		'payment_applications' => [
			'qry' => 'SELECT pti.payment_to_invoice_id, pti.payment_id, pti.invoice_id, pti.credit_amount, pti.credit_date FROM acc_payments_to_invoices pti JOIN acc_payments p ON pti.payment_id = p.payment_id WHERE p.customer_id = :customers_id ORDER BY pti.credit_date ASC',
			'cardinality' => cardinality::SET,
		],

		'unapplied_payments' => [
			'qry' => "SELECT p.payment_id, p.payment_amount, p.payment_method_id, pm.label as payment_method_label, p.payment_ref, p.payment_date, p.customers_extra_logins_id, p.payment_amount - IFNULL(SUM(pti.credit_amount), 0) as unapplied_amount FROM acc_payments p JOIN payment_method pm ON p.payment_method_id = pm.id LEFT JOIN acc_payments_to_invoices pti ON p.payment_id = pti.payment_id WHERE p.customer_id = :customers_id GROUP BY p.payment_id HAVING p.payment_amount - IFNULL(SUM(pti.credit_amount), 0) > 0 ORDER BY p.payment_date ASC",
			'cardinality' => cardinality::SET
		],

		'notifications' => [
			'qry' => 'SELECT * FROM products_notifications WHERE customers_id = :customers_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'contact_types' => [
			'qry' => 'SELECT * FROM customer_contact_types',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'permissions' => [
			'qry' => 'SELECT cp.customer_permission_id, cp.permission_name, cp.description, ctp.status FROM ck_customer_access_customers_to_permissions ctp JOIN ck_customer_access_permissions cp ON ctp.customer_permission_id = cp.customer_permission_id AND cp.active = 1 WHERE ctp.customers_id = :customers_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	const NET0 = 0;
	const NET10 = 1;
	const NET15 = 2;
	const NET30 = 3;
	const NET45 = 4;

	public static $cpmi = [
		self::NET10 => 5,
		self::NET15 => 6,
		self::NET30 => 7,
		self::NET45 => 15
	];

	const CREDIT_OFF = 1;
	const CREDIT_ON = 2;
	const CREDIT_PREPAID = 3;
	const CREDIT_HOLDALL = 4;

	public static $credit_statuses = [
		1 => 'Off',
		2 => 'On',
		3 => 'Prepaid',
		4 => 'Hold All Orders'
	];

	public function __construct($customers_id, ck_customer_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($customers_id);

		if (!$this->skeleton->built('customers_id')) $this->skeleton->load('customers_id', $customers_id);

		self::register($customers_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('customers_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('customer_header', [$this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$header['date_account_created'] = !self::date_is_empty($header['date_account_created'])?self::DateTime($header['date_account_created']):NULL;
		$header['date_account_last_modified'] = !self::date_is_empty($header['date_account_last_modified'])?self::DateTime($header['date_account_last_modified']):NULL;
		$header['date_last_logon'] = !self::date_is_empty($header['date_last_logon'])?self::DateTime($header['date_last_logon']):NULL;
		$header['dealer'] = CK\fn::check_flag($header['dealer']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('customer_header', [':customers_id' => $this->id()]));
		$this->normalize_header();
	}

	private function build_password_reset() {
		$prs = self::fetch('password_reset', [':customers_id' => $this->id(), ':expiration_limit' => self::$password_reset_expiration_limit]);

		$prs = array_map(function($pr) {
			$pr['reset_code_active'] = CK\fn::check_flag($pr['reset_code_active']);

			if (empty($pr['customers_extra_logins_id'])) $pr['email'] = $this->get_email_address();
			else $pr['email'] = $this->get_email_address($pr['customers_extra_logins_id']);

			$pr['reset_code_link'] = PRODUCTION_FQDN.'/password_forgotten.php?action=confirm&email='.$pr['email'].'&confirmation_code='.$pr['reset_code'];

			return $pr;
		}, $prs);

		$this->skeleton->load('password_reset', $prs);
	}

	private function build_summary() {
		$summary = prepared_query::fetch('SELECT account_creation_date, first_order_id, first_order_booked_date, last_order_id, last_order_booked_date, lifetime_order_value, lifetime_order_count, pending_order_value, pending_order_count FROM ckv_customer_summary WHERE customers_id = :customers_id', cardinality::ROW, [':customers_id' => $this->id()]);

		if (!empty($summary)) {
			$date_fields = ['account_creation_date', 'first_order_booked_date', 'last_order_booked_date'];
			foreach ($date_fields as $field) $summary[$field] = ck_datetime::datify($summary[$field]);
		}

		$this->skeleton->load('summary', $summary);
	}

	private function build_tax_exemptions() {
		if ($tax_exemptions = prepared_query::keyed_set_fetch('SELECT cte.customer_tax_exemption_id, cte.countries_id, c.countries_iso_code_2 as country_code, c.countries_name as country_name, cte.zone_id, z.zone_code as state_code, z.zone_name as state_name, cte.tax_exempt, cte.exemption_document, cte.exemption_created FROM customer_tax_exemptions cte JOIN countries c ON cte.countries_id = c.countries_id JOIN zones z ON cte.zone_id = z.zone_id WHERE cte.customers_id = :customers_id', 'zone_id', [':customers_id' => $this->id()])) {
			foreach ($tax_exemptions as $zone_id => $te) {
				$tax_exemptions[$zone_id]['tax_exempt'] = CK\fn::check_flag($te['tax_exempt']);
				$tax_exemptions[$zone_id]['exemption_created'] = ck_datetime::datify($te['exemption_created']);
			}
		}

		$this->skeleton->load('tax_exemptions', $tax_exemptions);
	}

	private function build_credit() {
		$header = $this->get_header();

		if ($header['credit_status_id'] > self::CREDIT_OFF) {
			$credit = [
				'credit_status_id' => $header['credit_status_id'],
				'credit_status' => $header['credit_status'],
				'credit_limit' => $header['credit_limit'],
			];
		}
		else $credit = NULL;

		$this->skeleton->load('credit', $credit);
	}

	private function build_terms() {
		$header = $this->get_header();

		if ($header['terms_days'] > 0) {
			$terms = [
				'terms_id' => $header['terms_id'],
				'legacy_dealer_pay_module' => $header['legacy_dealer_pay_module'],
				'label' => $header['terms_label'],
				'po_key' => $header['terms_po_key'],
				'days' => $header['terms_days'],
				'payment_method_id' => $header['terms_payment_method_id'],
			];
		}
		else $terms = NULL;

		$this->skeleton->load('terms', $terms);
	}

	private function build_credit_status_history() {
		$cshs = self::fetch('credit_status_history', [':customers_id' => $this->id()]);

		foreach ($cshs as &$history) {
			$history['status_date'] = self::DateTime($history['status_date']);
			if (!empty($history['admin_id'])) $history['admin'] = new ck_admin($history['admin_id']);
		}

		$this->skeleton->load('credit_status_history', $cshs);
	}

	private function build_account_manager() {
		if (!empty($this->get_header('account_manager_id'))) {
			$this->skeleton->load('account_manager', new ck_admin($this->get_header('account_manager_id')));
		}
		else $this->skeleton->load('account_manager', NULL);
	}

	private function build_sales_team() {
		if (!empty($this->get_header('sales_team_id'))) {
			$this->skeleton->load('sales_team', new ck_team($this->get_header('sales_team_id')));
		}
		else $this->skeleton->load('sales_team', NULL);
	}

	private function build_accounting_contact() {
		$header = $this->skeleton->get('header');
		$this->skeleton->load('accounting_contact', ['name' => $header['company_account_contact_name'], 'email' => $header['company_account_contact_email'], 'phone' => $header['company_account_contact_phone_number']]);
	}

	private function build_contacts() {
		$this->skeleton->load('contacts', self::fetch('contacts', [':customers_id' => $this->id()]));
	}

	private function build_addresses() {
		$this->skeleton->load('addresses', ck_address2::get_addresses_by_customer($this->id()));
	}

	private function build_extra_logins() {
		$this->skeleton->load('extra_logins', self::fetch('extra_logins', [':customers_id' => $this->id()]));
	}

	private function build_prices() {
		//$prices = $this->skeleton->format('prices');
		$prices = self::fetch('prices', [':customers_id' => $this->id()]);

		$prices = array_map(function($price) {
			$price['managed_category'] = CK\fn::check_flag($price['managed_category']);
			return $price;
		}, $prices);

		$this->skeleton->load('prices', $prices);
	}

	private function build_category_discounts() {
		$discounts = self::fetch('category_discounts', [':customers_id' => $this->id()]);

		foreach ($discounts as &$discount) {
			$discount['start_date'] = new DateTime($discount['start_date']);
			$discount['end_date'] = new DateTime($discount['end_date']);
		}

		$this->skeleton->load('category_discounts', $discounts);
	}

	private function build_orders() {
		$order_ids = self::fetch('orders', [':customers_id' => $this->id()]);
		$orders = [];
		foreach ($order_ids as $order_id) $orders[] = new ck_sales_order($order_id);
		$this->skeleton->load('orders', $orders);
	}

	private function build_order_count() {
		$orders = self::fetch('order_count', [':customers_id' => $this->id()]);
		$this->skeleton->load('order_count', $orders);
	}

	private function build_invoices() {
		$invoice_ids = self::fetch('invoices', [':customers_id' => $this->id()]);

		$invoices = [];

		foreach ($invoice_ids as $invoice_id) {
			$invoices[] = new ck_invoice($invoice_id);
		}

		$this->skeleton->load('invoices', $invoices);
	}

	private function build_payments() {
		$payments = self::fetch('payments', [':customers_id' => $this->id()]);

		$apps = self::fetch('payment_applications', [':customers_id' => $this->id()]);
		$applications = [];
		foreach ($apps as $app) {
			if (empty($applications[$app['payment_id']])) $applications[$app['payment_id']] = [];
			$applications[$app['payment_id']][] = $app;
		}

		foreach ($payments as &$payment) {
			$payment['payment_date'] = self::DateTime($payment['payment_date']);

			if (empty($applications[$payment['payment_id']])) {
				$payment['applications'] = [];
				$payment['applied_amount'] = 0;
			}
			else {
				$payment['applications'] = $applications[$payment['payment_id']];
				$payment['applied_amount'] = array_reduce($payment['applications'], function($applied_amount, $application) {
					$applied_amount = bcadd($applied_amount, $application['credit_amount'], 2);
					return $applied_amount;
				}, 0);
			}

			$payment['unapplied_amount'] = bcsub($payment['payment_amount'], $payment['applied_amount'], 2);
		}

		$this->skeleton->load('payments', $payments);
	}

	private function build_unapplied_payments() {
		// for now, the main benefit of this over the build payments method is to omit the notes, which are a bear
		$unapplied_payments = self::fetch('unapplied_payments', [':customers_id' => $this->id()]);

		foreach ($unapplied_payments as &$payment) {
			$payment['payment_date'] = self::DateTime($payment['payment_date']);
		}

		$this->skeleton->load('unapplied_payments', $unapplied_payments);
	}

	private function build_notifications() {
		$notifications = self::fetch('notifications', [':customers_id' => $this->id()]);

		foreach ($notifications as &$notification) {
			$notification['date_added'] = new DateTime($notification['date_added']);
		}

		if (empty($notifications)) $notifications = [];

		$this->skeleton->load('notifications', $notifications);
	}

	private function build_permissions() {
		$permissions = self::fetch('permissions', [':customers_id' => $this->id()]);

		$cp = [];

		foreach ($permissions as $permission) {
			$status = NULL;
			if ($permission['status'] == 'GRANTED') $status = TRUE;
			elseif ($permission['status'] == 'REVOKED') $status = FALSE;
			$cp[$permission['permission_name']] = $status;
		}

		$this->skeleton->load('permissions', $cp);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function has_password_reset($key=NULL) {
		if (!$this->skeleton->built('password_reset')) $this->build_password_reset();
		if (is_null($key)) return $this->skeleton->has('password_reset');
		else {
			foreach ($this->skeleton->get('password_reset') as $pr) {
				// pass in '0' to match NULL in the dataset
				if ($key == $pr['customers_extra_logins_id']) return TRUE;
			}
			return FALSE;
		}
	}

	public function get_password_reset($key=NULL) {
		if (!$this->has_password_reset($key)) return [];
		elseif (is_null($key)) return $this->skeleton->get('password_reset');
		else {
			foreach ($this->skeleton->get('password_reset') as $pr) {
				// pass in '0' to match NULL in the dataset
				if ($key == $pr['customers_extra_logins_id']) return $pr;
			}
			return NULL;
		}
	}

	public function get_summary($key=NULL) {
		if (!$this->skeleton->built('summary')) $this->build_summary();
		if (empty($key)) return $this->skeleton->get('summary');
		else return $this->skeleton->get('summary', $key);
	}

	public function has_tax_exemptions($key=NULL) {
		if (!$this->skeleton->built('tax_exemptions')) $this->build_tax_exemptions();
		if (empty($key)) return $this->skeleton->has('tax_exemptions');
		else return !empty($this->skeleton->get('tax_exemptions')[$key]);
	}

	public function get_tax_exemptions($key=NULL) {
		if (!$this->has_tax_exemptions($key)) return [];
		return $this->skeleton->get('tax_exemptions', $key);
	}

	public function is_tax_exempt_in($state, $country) {
		if ($country = ck_address2::get_country($country)) {
			if ($state = ck_address2::get_zone($state, $country['countries_id'])) {
				return $this->has_tax_exemptions($state['zone_id']);
			}
		}

		return FALSE;
	}

	public function get_display_label($customers_extra_logins_id=NULL) {
		// this is a complete replacement for the old zend modle __toString() method
		$display_label = [];

		if ($cn = $this->get_company_name()) $display_label[] = $cn;
		$display_label[] = $this->get_name($customers_extra_logins_id);

		return implode(' - ', $display_label);
	}

	public function get_display_label_direct() {
		$names = prepared_query::fetch("SELECT CONCAT_WS(' ', c.customers_firstname, c.customers_lastname) as name, ab.entry_company as company FROM customers c JOIN address_book ab ON c.customers_default_address_id = ab.address_book_id WHERE c.customers_id = :customers_id", cardinality::ROW, [':customers_id' => $this->id()]);

		$display_label = [];
		if (!empty($names['company'])) $display_label[] = $names['company'];
		$display_label[] = $names['name'];

		return implode(' - ', $display_label);
	}

	public function get_highest_name($customers_extra_logins_id=NULL) {
		if ($cn = $this->get_company_name()) return $cn;
		else return $this->get_name($customers_extra_logins_id);
	}

	public function get_company_name() {
		if ($this->has_addresses()) {
			$address = $this->get_default_address();
			if ($address->has_company_name()) return $address->get_company_name();
		}

		return NULL;
	}

	public function get_name($customers_extra_logins_id=NULL) {
		if (!empty($customers_extra_logins_id) && $this->has_extra_logins() && ($cel = $this->get_extra_logins($customers_extra_logins_id))) {
			return $cel['first_name'].' '.$cel['last_name'];
		}
		else return $this->get_header('first_name').' '.$this->get_header('last_name');
	}

	public function get_email_address($customers_extra_logins_id=NULL) {
		if (!empty($customers_extra_logins_id) && $this->has_extra_logins() && ($cel = $this->get_extra_logins($customers_extra_logins_id))) {
			return $cel['email_address'];
		}
		else return $this->get_header('email_address');
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

	public function get_accounting_contact($key=NULL) {
		if (!$this->skeleton->built('accounting_contact')) $this->build_accounting_contact();
		if (empty($key)) return $this->skeleton->get('accounting_contact');
		else return $this->skeleton->get('accounting_contact', $key);
	}

	public function has_credit() {
		if (!$this->skeleton->built('credit')) $this->build_credit();
		return $this->skeleton->has('credit');
	}

	public function get_credit($key=NULL) {
		if (!$this->has_credit()) return NULL;
		if (empty($key)) return $this->skeleton->get('credit');
		else return $this->skeleton->get('credit', $key);
	}

	public function has_terms() {
		if (!$this->skeleton->built('terms')) $this->build_terms();
		return $this->skeleton->has('terms');
		//return $this->get_header('terms_days') > 0; // I misnamed this field, that should be fixed at some point, but for now we'll just wrap it
	}

	public function get_terms($key=NULL) {
		if (!$this->has_terms()) return NULL;
		if (empty($key)) return $this->skeleton->get('terms');
		else return $this->skeleton->get('terms', $key);

		/*if ($this->has_terms()) {
			return ['payment_method_id' => $this->get_header('terms_payment_method_id'), 
			$payment_terms = [];
			switch ($this->get_header('legacy_dealer_pay_module')) {
				case 1:
					$payment_terms = ['id' => 5, 'title' => 'Net 10', 'value' => 'net10'];
					break;
				case 2:
					$payment_terms = ['id' => 6, 'title' => 'Net 15', 'value' => 'net15'];
					break;
				case 3:
					$payment_terms = ['id' => 7, 'title' => 'Net 30', 'value' => 'net30'];
					break;
				case 4:
					$payment_terms = ['id' => 15, 'title' => 'Net 45', 'value' => 'net45'];
					break;
				default:
					break;
			}
			return $payment_terms;
		}
		return FALSE;*/
	}

	public function has_credit_status_history() {
		if (!$this->skeleton->built('credit_status_history')) $this->build_credit_status_history();
		return $this->skeleton->has('credit_status_history');
	}

	public function get_credit_status_history() {
		if (!$this->has_credit_status_history()) return [];
		return $this->skeleton->get('credit_status_history');
	}

	public function get_remaining_credit() {
		if (!$this->has_credit()) return 0;

		$credit_limit = $this->get_credit('credit_limit');

		$cutoff = new DateTime();
		$cutoff->sub(new DateInterval('P3Y'));

		$unshipped_orders = prepared_query::fetch('SELECT orders_id FROM orders WHERE customers_id = :customers_id AND orders_status NOT IN (:shipped, :canceled) AND DATE(date_purchased) >= :cutoff AND payment_method_id IN (:net10, :net15, :net30, :net45)', cardinality::COLUMN, [':customers_id' => $this->id(), ':shipped' => ck_sales_order::STATUS_SHIPPED, ':canceled' => ck_sales_order::STATUS_CANCELED, ':cutoff' => $cutoff->format('Y-m-d'), ':net10' => self::$cpmi[self::NET10], ':net15' => self::$cpmi[self::NET15], ':net30' => self::$cpmi[self::NET30], ':net45' => self::$cpmi[self::NET45]]);

		$unpaid_invoices = prepared_query::fetch('SELECT i.invoice_id FROM acc_invoices i JOIN orders o ON i.inv_order_id = o.orders_id WHERE o.customers_id = :customers_id AND i.paid_in_full = 0 AND DATE(i.inv_date) >= :cutoff AND o.payment_method_id IN (:net10, :net15, :net30, :net45)', cardinality::COLUMN, [':customers_id' => $this->id(), ':cutoff' => $cutoff->format('Y-m-d'), ':net10' => self::$cpmi[self::NET10], ':net15' => self::$cpmi[self::NET15], ':net30' => self::$cpmi[self::NET30], ':net45' => self::$cpmi[self::NET45]]);

		foreach ($unshipped_orders as $orders_id) {
			$order = new ck_sales_order($orders_id);
			$credit_limit -= $order->get_balance();
		}

		foreach ($unpaid_invoices as $invoice_id) {
			$invoice = new ck_invoice($invoice_id);
			$credit_limit -= $invoice->get_balance();
		}

		return $credit_limit;
	}

	public function can_place_credit_order($new_amount=0) {
		if (!$this->has_credit()) return FALSE; // credit is turned off for this customer

		$credit = $this->get_credit();

		if (!$this->has_terms()) return FALSE; // credit is turned on, but no terms are set

		if ($credit['credit_status_id'] != self::CREDIT_ON) return FALSE; // credit is temporarily suspended

		if ($this->get_remaining_credit() - $new_amount < 0) return FALSE; // we're over the credit limit.  If we want to know if it's this order that puts us over, just run it again without $new_amount

		return TRUE; // otherwise, we can place a credit order :)
	}

	public function cannot_place_any_order() {
		if ($this->get_credit('credit_status_id') == self::CREDIT_HOLDALL || $this->is('fraud')) return TRUE;
		return FALSE;
	}

	public function has_own_shipping_account() {
		return $this->is('own_shipping_account');
	}

	public function has_contacts() {
		if (!$this->skeleton->built('contacts')) $this->build_contacts();
		return $this->skeleton->has('contacts');
	}

	public function get_contacts() {
		if (!$this->has_contacts()) return NULL;
		else return $this->skeleton->get('contacts');
	}

	// we *probably* always have addresses, but we don't like to assume
	public function has_addresses() {
		if (!$this->skeleton->built('addresses')) $this->build_addresses();
		return $this->skeleton->has('addresses');
	}

	public function get_addresses($key=NULL) {
		if (!$this->has_addresses()) return NULL;
		if (empty($key)) return $this->skeleton->get('addresses');
		elseif ($key == 'default') return $this->get_default_address();
		else {
			$addresses = $this->skeleton->get('addresses');

			if (empty($addresses)) throw new CKCustomerException('Customer has no address records.');

			foreach ($addresses as $address) {
				if (is_numeric($key) && $address->get_header('address_book_id') == $key) return $address;
			}

			return NULL;
		}
	}

	public function get_default_address() {
		if (!$this->has_addresses()) return NULL;

		$addresses = $this->skeleton->get('addresses');
		foreach ($addresses as $address) {
			if ($address->is('default_address')) return $address;
		}

		// if we get here, just set the default as the first one in the list and return it
		$this->set_default_address($addresses[0]->id());
		return $address[0];
	}

	public function get_other_addresses($address_book_id=NULL) {
		if (!$this->has_addresses()) return [];

		$addresses = $this->skeleton->get('addresses');
		$addys = [];

		foreach ($addresses as $address) {
			if (empty($address_book_id) && $address->is('default_address')) continue;
			elseif (!empty($address_book_id) && $address_book_id == $address->id()) continue;
			$addys[] = $address;
		}

		return $addys;
	}

	public function has_extra_logins() {
		if (!$this->skeleton->built('extra_logins')) $this->build_extra_logins();
		return $this->skeleton->has('extra_logins');
	}

	public function get_extra_logins($key=NULL) {
		if (!$this->has_extra_logins()) return NULL;
		if (empty($key)) return $this->skeleton->get('extra_logins');
		else {
			$result_set = [];
			foreach ($this->skeleton->get('extra_logins') as $extra_login) {
				if ($key == 'active' && $extra_login['active'] == 1) $result_set[] = $extra_login;
				elseif ($key == 'inactive' && $extra_login['active'] == 0) $result_set[] = $extra_login;
				elseif (is_numeric($key) && $extra_login['customers_extra_logins_id'] == $key) return $extra_login;
				elseif (CK\text::email_match($key, $extra_login['email_address'])) return $extra_login;
			}
			return $result_set;
		}
	}

	public function has_prices() {
		if (!$this->skeleton->built('prices')) $this->build_prices();
		return $this->skeleton->has('prices');
	}

	public function get_prices($stock_id=NULL) {
		if (!$this->has_prices()) return NULL;
		if (empty($stock_id)) return $this->skeleton->get('prices');
		else {
			$prices = $this->skeleton->get('prices');
			foreach ($prices as $price) {
				if ($price['stock_id'] == $stock_id) return $price['price'];
			}
			// if we didn't find a price to match this specific stock_id, return NULL
			return NULL;
		}
	}

	public function has_category_discounts() {
		if (!$this->skeleton->built('category_discounts')) $this->build_category_discounts();
		return $this->skeleton->has('category_discounts');
	}

	public function get_category_discounts() {
		if (!$this->has_category_discounts()) return NULL;
		else return $this->skeleton->get('category_discounts');
	}

	public function has_orders() {
		if (!$this->skeleton->built('orders')) $this->build_orders();
		return $this->skeleton->has('orders');
	}

	public function get_orders($key=NULL) {
		if (!$this->has_orders()) return [];
		if (empty($key)) return $this->skeleton->get('orders');
		else {
			if ($key == 'open') {
				$orders = [];
				foreach ($this->skeleton->get('orders') as $order) {
					if (in_array($order->get_header('orders_status'), [ck_sales_order::STATUS_SHIPPED, ck_sales_order::STATUS_CANCELED, 9])) continue;
					$orders[] = $order;
				}
				return $orders;
			}
			// for our purposes, and because this is backwards compatible, if you're passing in a number
			// it's an order ID, not a status ID.
			elseif (is_numeric($key)) {
				foreach ($this->skeleton->get('orders') as $order) {
					if ($order->id() == $key) return $order;
				}
			}

			// if we don't recognize the key format or we just didn't find the specific order, return empty array
			return [];
		}
	}

	public function get_order_references($statuses, $exclude=FALSE) {
		if (!is_array($statuses)) $statuses = [$statuses];

		$params = [];
		foreach ($statuses as $status) {
			$params[] = '?';
		}

		$params = implode(', ', $params);

		if ($exclude) return prepared_query::fetch('SELECT orders_id FROM orders WHERE orders_status NOT IN ('.$params.')', cardinality::COLUMN, $statuses);
		else return prepared_query::fetch('SELECT orders_id FROM orders WHERE orders_status IN ('.$params.')', cardinality::COLUMN, $statuses);
	}

	public function get_order_count($orders_status_id=NULL) {
		if (!$this->skeleton->built('order_count')) $this->build_order_count();
		if (empty($orders_status_id)) return count($this->skeleton->get('order_count'));
		else return array_reduce($this->skeleton->get('order_count'), function($count, $order) use ($orders_status_id) {
			if ($order['orders_status_id'] == $orders_status_id) $count++;
			return $count;
		}, 0);
	}

	public function has_invoices() {
		if (!$this->skeleton->built('invoices')) $this->build_invoices();
		return $this->skeleton->has('invoices');
	}

	public function get_invoices() {
		if (!$this->has_invoices()) return [];
		return $this->skeleton->get('invoices');
	}

	public function get_incentive_base_total(DateTime $end_date=NULL) {
		if (empty($end_date)) $end_date = new DateTime('tomorrow midnight');
		$start_date = clone $end_date;
		$start_date->sub(new DateInterval('P1Y'));

		$total = prepared_query::fetch("SELECT SUM(IF(IFNULL(it_total.total, 0) - IFNULL(it_tax.total_tax, 0) - IFNULL(it_shipping.total_shipping, 0) - IFNULL(ii.total_cost, 0) <= 0 AND i.inv_order_id IS NOT NULL AND IFNULL(i.credit_memo, 0) != 1, 0, IFNULL(it_total.total, 0) - IFNULL(it_tax.total_tax, 0) - IFNULL(it_shipping.total_shipping, 0) - IFNULL(ii.total_cost, 0))) as product_margin_total FROM acc_invoices i LEFT JOIN (SELECT it.invoice_id, SUM(it.invoice_total_price) as total_shipping FROM acc_invoices i JOIN acc_invoice_totals it ON i.invoice_id = it.invoice_id AND it.invoice_total_line_type = 'ot_shipping' WHERE (i.inv_order_id IS NOT NULL OR i.rma_id IS NOT NULL) AND i.inv_date >= :start_date AND i.inv_date < :end_date AND i.customer_id = :customers_id GROUP BY it.invoice_id) it_shipping ON i.invoice_id = it_shipping.invoice_id LEFT JOIN (SELECT it.invoice_id, SUM(it.invoice_total_price) as total_tax FROM acc_invoices i JOIN acc_invoice_totals it ON i.invoice_id = it.invoice_id AND it.invoice_total_line_type = 'ot_tax' WHERE (i.inv_order_id IS NOT NULL OR i.rma_id IS NOT NULL) AND i.inv_date >= :start_date AND i.inv_date < :end_date AND i.customer_id = :customers_id GROUP BY it.invoice_id) it_tax ON i.invoice_id = it_tax.invoice_id LEFT JOIN (SELECT it.invoice_id, SUM(it.invoice_total_price) as total FROM acc_invoices i JOIN acc_invoice_totals it ON i.invoice_id = it.invoice_id AND it.invoice_total_line_type = 'ot_total' WHERE (i.inv_order_id IS NOT NULL OR i.rma_id IS NOT NULL) AND i.inv_date >= :start_date AND i.inv_date < :end_date AND i.customer_id = :customers_id GROUP BY it.invoice_id) it_total ON i.invoice_id = it_total.invoice_id LEFT JOIN (SELECT ii.invoice_id, SUM(ii.orders_product_cost_total) as total_cost FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE (i.inv_order_id IS NOT NULL OR i.rma_id IS NOT NULL) AND i.inv_date >= :start_date AND i.inv_date < :end_date AND i.customer_id = :customers_id GROUP BY ii.invoice_id) ii ON i.invoice_id = ii.invoice_id WHERE (i.inv_order_id IS NOT NULL OR i.rma_id IS NOT NULL) AND i.inv_date >= :start_date AND i.inv_date < :end_date AND i.customer_id = :customers_id", cardinality::SINGLE, [':start_date' => $start_date->format('Y-m-d'), ':end_date' => $end_date->format('Y-m-d H:i:s'), ':customers_id' => $this->id()]);

		return $total;
	}

	public function get_outstanding_invoices() {
		$invoices = $this->get_invoices();

		$outstanding_invoices = [];

		foreach ($invoices as $invoice) {
			if (!$invoice->is('paid_in_full')) $outstanding_invoices[] = $invoice;
		}

		return $outstanding_invoices;
	}

	public function get_outstanding_invoices_direct() {
		$invoices = array_map(function($invoice_id) {
			return new ck_invoice($invoice_id);
		}, prepared_query::fetch('SELECT invoice_id FROM acc_invoices WHERE customer_id = :customers_id AND IFNULL(paid_in_full, 0) = 0 ORDER BY inv_date ASC', cardinality::COLUMN, [':customers_id' => $this->id()]));

		return $invoices;
	}

	public function get_late_invoices_to_notify_direct() {
		$pms = ck_payment_method_lookup::instance()->get_list('code');

		$invoices = array_map(function($invoice_id) {
			return new ck_invoice($invoice_id);
		}, prepared_query::fetch('SELECT i.invoice_id FROM acc_invoices i JOIN customers c ON i.customer_id = c.customers_id LEFT JOIN customer_terms ct ON c.dealer_pay_module = ct.legacy_dealer_pay_module JOIN orders o ON i.inv_order_id = o.orders_id WHERE c.customers_id = :customers_id AND IFNULL(i.paid_in_full, 0) = 0 AND o.payment_method_id NOT IN (:net10, :amazon) AND DATEDIFF(CURDATE(), DATE(i.inv_date)) > IFNULL(ct.terms_days, 0) ORDER BY i.inv_date ASC', cardinality::COLUMN, [':customers_id' => $this->id(), ':net10' => $pms['net10']['payment_method_id'], ':amazon' => $pms['amazon']['payment_method_id']]));

		return $invoices;
	}

	public function get_first_late_notification_date() {
		$fnd = prepared_query::fetch('SELECT first_late_notice_date FROM customers WHERE customers_id = :customers_id', cardinality::SINGLE, [':customers_id' => $this->id()]);

		if (!empty($fnd)) {
			$fnd = ck_datetime::datify($fnd);
			$fnd->remove_time();
		}

		return $fnd;
	}

	public function get_customer_invoice_age() {
		return array_reduce($this->get_outstanding_invoices_direct(), function($age, $invoice) {
			return max($age, $invoice->get_age());
		}, 0);
	}

	public function get_customer_balance() {
		return array_reduce($this->get_outstanding_invoices_direct(), function($balance, $invoice) {
			$balance += $invoice->get_balance();
			return $balance;
		}, 0);
	}

	public function get_customer_net_balance() {
		return $this->get_customer_balance() - $this->get_unapplied_credit_total();
	}

	public function get_paid_invoices() {
		$invoices = $this->get_invoices();

		$paid_invoices = [];

		foreach ($invoices as $invoice) {
			if (!$invoice->is('paid_in_full')) continue;
			if ($invoice->is('invoice_rma')) continue;
			if (!$invoice->has('orders_id')) continue;
			$paid_invoices[] = $invoice;
		}

		return $paid_invoices;
	}

	public function get_unpaid_invoices() {
		$invoices = $this->get_invoices();

		$unpaid_invoices = [];

		foreach ($invoices as $invoice) {
			if ($invoice->is('paid_in_full')) continue;
			if ($invoice->is('invoice_rma')) continue;
			if (!$invoice->has('orders_id')) continue;
			$unpaid_invoices[] = $invoice;
		}

		return $unpaid_invoices;
	}

	public function get_rma_invoices() {
		$invoices = $this->get_invoices();

		$rma_invoices = [];

		foreach ($invoices as $invoice) {
			if (!$invoice->is('invoice_rma')) continue;
			$rma_invoices[] = $invoice;
		}

		return $rma_invoices;
	}

	public function get_credit_memo_invoices() {
		$invoices = $this->get_invoices();

		$cm_invoices = [];

		foreach ($invoices as $invoice) {
			if ($invoice->is('invoice_rma')) continue;
			if (!$invoice->has('orders_id')) continue;
			if (!$invoice->is('credit_memo')) continue;
			$cm_invoices[] = $invoice;
		}

		return $cm_invoices;
	}

	public function get_average_days_to_pay() {
		$avg = [];
		foreach ($this->get_paid_invoices() as $invoice) {
			if ($invoice_age = $invoice->get_age()) $avg[] = $invoice_age;
		}
		if (count($avg) == 0) return NULL;
		return round(array_sum($avg) / count($avg), 1);
	}

	public function has_payments() {
		if (!$this->skeleton->built('payments')) $this->build_payments();
		return $this->skeleton->has('payments');
	}

	public function get_payments() {
		if (!$this->has_payments()) return [];
		return $this->skeleton->get('payments');
	}

	public function get_limited_payments() {
		$date = new DateTime();
		$date->sub(new DateInterval('P1Y'));

		$payments = prepared_query::fetch('SELECT p.payment_id, p.payment_amount, p.payment_method_id, pm.label as payment_method_label, p.payment_ref, p.payment_date, p.customers_extra_logins_id FROM acc_payments p JOIN payment_method pm ON p.payment_method_id = pm.id WHERE p.customer_id = :customers_id AND p.payment_date >= :payment_date GROUP BY p.payment_id ORDER BY p.payment_date ASC', cardinality::SET, [':customers_id' => $this->id(), ':payment_date' => $date->format('Y-m-d')]);

		$apps = prepared_query::fetch('SELECT pti.payment_to_invoice_id, pti.payment_id, pti.invoice_id, pti.credit_amount, pti.credit_date FROM acc_payments_to_invoices pti JOIN acc_payments p ON pti.payment_id = p.payment_id WHERE p.customer_id = :customers_id AND pti.credit_date >= :credit_date ORDER BY pti.credit_date ASC', cardinality::SET, [':customers_id' => $this->id(), ':credit_date' => $date->format('Y-m-d')]);

		$applications = [];
		foreach ($apps as $app) {
			if (empty($applications[$app['payment_id']])) $applications[$app['payment_id']] = [];
			$applications[$app['payment_id']][] = $app;
		}

		foreach ($payments as &$payment) {
			$payment['payment_date'] = self::DateTime($payment['payment_date']);

			if (empty($applications[$payment['payment_id']])) {
				$payment['applications'] = [];
				$payment['applied_amount'] = 0;
			}
			else {
				$payment['applications'] = $applications[$payment['payment_id']];
				$payment['applied_amount'] = array_reduce($payment['applications'], function($applied_amount, $application) {
					$applied_amount = bcadd($applied_amount, $application['credit_amount'], 2);
					return $applied_amount;
				}, 0);
			}

			$payment['unapplied_amount'] = bcsub($payment['payment_amount'], $payment['applied_amount'], 2);
		}

		return $payments;
	}

	public function has_payment_notes($payment_id) {
		return !empty(prepared_query::fetch('SELECT * FROM acc_notes WHERE note_type = :payments AND note_type_id = :payment_id', cardinality::SINGLE, [':payments' => 'acc_payments', ':payment_id' => $payment_id]));
	}

	public function get_payment_notes($payment_id) {
		return prepared_query::fetch('SELECT GROUP_CONCAT(note_text) as note FROM acc_notes WHERE note_type = :payments AND note_type_id = :payment_id GROUP BY note_type_id', cardinality::SINGLE, [':payments' => 'acc_payments', ':payment_id' => $payment_id]);
	}

	public function has_unapplied_payments() {
		if (!$this->skeleton->built('unapplied_payments')) $this->build_unapplied_payments();
		return $this->skeleton->has('unapplied_payments');
	}

	public function get_unapplied_payments() {
		if (!$this->has_unapplied_payments()) return [];
		return $this->skeleton->get('unapplied_payments');
		/*$pmts = [];

		foreach ($this->get_payments() as $payment) {
			if ($payment['unapplied_amount'] > 0) $pmts[] = $payment;
		}

		return $pmts;*/
	}

	public function get_unapplied_credit_total() {
		return array_reduce($this->get_unapplied_payments(), function($credit, $pmt) {
			$credit += $pmt['unapplied_amount'];
			return $credit;
		}, 0);
	}

	public function get_unapplied_payments_by_payment_method_id($payment_method_id) {
		$pmts = [];

		foreach ($this->get_unapplied_payments() as $payment) {
			if ($payment['payment_method_id'] == $payment_method_id) $pmts[] = $payment;
		}

		return $pmts;
	}

	public function has_notifications() {
		if (!$this->skeleton->built('notifications')) $this->build_notifications();
		return $this->skeleton->has('notifications');
	}

	public function get_notifications($products_id=NULL) {
		if (!$this->has_notifications()) return NULL;
		if (empty($products_id)) return $this->skeleton->get('notifications');
		else {
			$notifications = $this->skeleton->get('notifications');
			foreach ($notifications as $notification) {
				if ($notification['products_id'] == $products_id) return $notification;
			}
			// if we didn't find a notification to match this specific products_id, return NULL
			return NULL;
		}
	}

	public function has_process_errors($type=NULL, $typekey=NULL) {
		if (empty($type)) return !empty($this->process_errors);

		elseif (!isset($this->process_errors[$type])) return FALSE;

		elseif (empty($typekey)) return !empty($this->process_errors[$type]);

		elseif (!isset($this->process_errors[$type][$typekey])) return FALSE;

		else return !empty($this->process_errors[$type][$typekey]);
	}

	public function get_process_errors($type=NULL, $typekey=NULL) {
		if (!$this->has_process_errors($type, $typekey)) return NULL;


		if (empty($type)) return $this->process_errors;

		elseif (!isset($this->process_errors[$type])) return [];

		elseif (empty($typekey)) return $this->process_errors[$type];

		elseif (!isset($this->process_errors[$type][$typekey])) return [];

		else return $this->process_errors[$type][$typekey];
	}

	public function owns_email_address($email) {
		if (CK\text::email_match($this->get_header('email_address'), $email)) return TRUE;
		elseif ($this->has_extra_logins() && !empty($this->get_extra_logins($email))) return TRUE;

		return FALSE;
	}

	public function get_email_address_id($email) {
		if (CK\text::email_match($this->get_header('email_address'), $email)) return $this->id();
		elseif ($this->has_extra_logins() && ($el = $this->get_extra_logins($email))) return $el['customers_extra_logins_id'];

		return NULL;
	}

	public function get_logged_in_email($customers_extra_logins_id=NULL) {
		if (empty($customers_extra_logins_id)) return $this->get_header('email_address');
		else return $this->get_extra_logins($customers_extra_logins_id)['email_address'];
	}

	public function get_permissions() {
		if (!$this->skeleton->built('permissions')) $this->build_permissions();
		return $this->skeleton->get('permissions');
	}

	public function is_allowed($permission) {
		$permissions = $this->get_permissions();
		return @$permissions[$permission]===TRUE;
	}

	public function is_denied($permission) {
		$permissions = $this->get_permissions();
		return $permissions[$permission]===FALSE;
	}

	public function get_crm_link() {
		$link = NULL;

		try {
			$hubspot = new api_hubspot;
			$link = $hubspot->get_hubspot_company_link($this);
		}
		catch (Exception $e) {
		}

		return $link;
	}

	public static function get_customer_by_email($email) {
		$clauses = self::$queries['customer_header_list_proto']['proto_opts'];

		$query_details = ['data_operation' => 'c.customers_id', 'where' => 'WHERE '.$clauses[':email'], 'cardinality' => cardinality::SINGLE];

		$qry = self::modify_query('customer_header_list_proto', $query_details);

		if ($customers_id = self::fetch($qry, [':email' => $email])) {
			return new self($customers_id);
		}
		else return NULL;
	}

	public static $matched_total = 0;

	public static function get_customers_by_match($fields, $page=1, $page_size=50, $order_by=[]) {
		$query_adds = ['where' => '', 'order_by' => [], 'limit' => ''];

		$clauses = self::$queries['customer_header_list_proto']['proto_opts'];

		if (isset($fields[':customer_type'])) $where_ctype = $clauses[':customer_type'];

		if (!empty($fields[':email']) && empty($fields[':main_only'])) $where_email = $clauses[':email'];
		elseif (!empty($fields[':email'])) $where_email = $clauses[':email_main'];

		if (!empty($fields[':last_name']) && empty($fields[':main_only'])) $where_lname = $clauses[':last_name'];
		elseif (!empty($fields[':last_name'])) $where_lname = $clauses[':last_name_main'];

		if (!empty($fields[':first_name']) && empty($fields[':main_only'])) $where_fname = $clauses[':first_name'];
		elseif (!empty($fields[':first_name'])) $where_fname = $clauses[':first_name_main'];

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

		$simple_header = FALSE;
		if (!empty($fields[':simple_result'])) $simple_header = TRUE;
		unset($fields[':simple_result']);

		$qry = self::modify_query('customer_header_count_proto', $query_adds);

		self::$matched_total = self::fetch($qry, $fields);

		if (!is_numeric($page)) $page = 1;
		if (!is_numeric($page_size)) $page_size = 50;
		$page--; // limits are zero based

		$query_adds['limit'] = 'LIMIT '.($page*$page_size).', '.$page_size;

		if ($simple_header) {
			$query_adds['data_operation'] = 'c.customers_id';
			$query_adds['cardinality'] = cardinality::COLUMN;
		}

		$qry = self::modify_query('customer_header_list_proto', $query_adds);

		// modify_query is necessary because we can't parameterize limits or ordering
		if ($headers = self::fetch($qry, $fields)) {
			$customers = [];
			foreach ($headers as $header) {
				if (!empty($simple_header)) {
					$skeleton = self::get_record($header); // if we've already instantiated it, well, oh well
					$customers[] = new self($header, $skeleton);
				}
				else {
					$skeleton = self::get_record($header['customers_id']); // if we've already instantiated it, well, oh well
					if (!$skeleton->built('header')) $skeleton->load('header', $header);

					$customers[] = new self($header['customers_id'], $skeleton);
				}
			}
			return $customers;
		}
		else return [];
	}

	public static function get_customers_by_name($name) {
		$criteria = [];

		$qry = "SELECT DISTINCT c.customers_id, CONCAT_WS(' ', c.customers_firstname, c.customers_lastname) as name, ab.entry_company as company FROM customers c LEFT JOIN address_book ab ON c.customers_default_address_id = ab.address_book_id WHERE ab.entry_company LIKE :name OR c.customers_firstname LIKE :name OR c.customers_lastname LIKE :name";
		$criteria[':name'] = $name.'%';

		$names = explode(' ', $name);

		if (count($names) == 2) {
			$criteria[':first_name'] = $names[0];
			$criteria[':last_name'] = $names[1].'%';
			$qry .= ' OR (c.customers_firstname = :first_name AND c.customers_lastname LIKE :last_name)';
		}

		$qry .= ' GROUP BY c.customers_id ORDER BY ab.entry_company, c.customers_firstname, c.customers_lastname';

		if ($customers_data = prepared_query::fetch($qry, cardinality::SET, $criteria)) {
			$customers = [];

			foreach ($customers_data as $cust) {
				$c = new self($cust['customers_id']);
				
				$display_label = [];
				if (!empty($cust['company'])) $display_label[] = $cust['company'];
				$display_label[] = $cust['name'];

				$c->set_prop('display_label', implode(' - ', $display_label));

				$customers[] = $c;
			}

			return $customers;
		}
		else return [];
	}

	public static function legacy_search_customers_past_due($search) {
		$qry = "SELECT c.customers_id, a.entry_company as company_name, CONCAT_WS(' ', c.customers_firstname, c.customers_lastname) as customer_name FROM customers c LEFT JOIN address_book a ON a.address_book_id = c.customers_default_address_id WHERE a.entry_company like '".$search."%' OR c.customers_firstname LIKE '".$search."%' OR c.customers_lastname LIKE '".$search."%'";

		$names = explode(' ', $search);
		if (count($names) == 2) {
			$qry .= " OR (customers_firstname = '".$names[0]."' AND customers_lastname LIKE '".$names[1]."%')";
		}

		$qry .= ' GROUP BY customers_id ORDER BY company_name, customer_name';

		if ($customers_data = prepared_query::fetch($qry, cardinality::SET, [])) {
			$customers = [];

			foreach ($customers_data as $cust) {
				$display_label = [];
				if (!empty($cust['company_name'])) $display_label[] = $cust['company_name'];
				$display_label[] = $cust['customer_name'];
				$display_label = implode(' - ', $display_label);

				$customers[] = ['customers_id' => $cust['customers_id'], 'name' => $display_label];
			}

			return $customers;
		}
		else return [];
	}

	public static function get_customer_by_payment_id($payment_id) {
		if ($customer_id = prepared_query::fetch('SELECT customer_id FROM acc_payments WHERE payment_id = :payment_id', cardinality::SINGLE, [':payment_id' => $payment_id])) {
			return new self($customer_id);
		}
		else return NULL;
	}

	public static function get_customer_order_count($customers_id) {
		return prepared_query::fetch('SELECT COUNT(DISTINCT o.orders_id) FROM orders o JOIN orders_total ot ON o.orders_id = ot.orders_id JOIN orders_products op ON o.orders_id = op.orders_id WHERE o.customers_id = :customers_id', cardinality::SINGLE, [':customers_id' => $customers_id]);
	}

	public static function email_exists($email, $customers_id=NULL, $customers_extra_logins_id=NULL) {
		if (empty($customers_id) && prepared_query::fetch('SELECT customers_id FROM customers WHERE TRIM(customers_email_address) LIKE :email', cardinality::SINGLE, [':email' => trim($email)])) return TRUE;
		elseif (!empty($customers_id) && prepared_query::fetch('SELECT customers_id FROM customers WHERE customers_id != :customers_id AND TRIM(customers_email_address) LIKE :email', cardinality::SINGLE, [':customers_id' => $customers_id, ':email' => trim($email)])) return TRUE;

		if (empty($customers_id) && empty($customers_extra_logins_id) && prepared_query::fetch('SELECT customers_id FROM customers_extra_logins WHERE TRIM(customers_emailaddress) LIKE :email', cardinality::SINGLE, [':email' => trim($email)])) return TRUE;
		elseif (!empty($customers_id) && empty($customers_extra_logins_id) && prepared_query::fetch('SELECT customers_id FROM customers_extra_logins WHERE customers_id != :customers_id AND TRIM(customers_emailaddress) LIKE :email', cardinality::SINGLE, [':customers_id' => $customers_id, ':email' => trim($email)])) return TRUE;
		elseif (!empty($customers_extra_logins_id) && prepared_query::fetch('SELECT customers_id FROM customers_extra_logins WHERE customers_extra_logins_id != :customers_extra_logins_id AND TRIM(customers_emailaddress) LIKE :email', cardinality::SINGLE, [':customers_extra_logins_id' => $customers_extra_logins_id, ':email' => trim($email)])) return TRUE;

		return FALSE;
	}

	/*public static function get_customers_with_outstanding_invoices() {
		if ($customers_ids = prepared_query::fetch('SELECT DISTINCT customer_id FROM acc_invoices WHERE paid_in_full = 0', cardinality::COLUMN)) {
			$customers = [];

			foreach ($customers_ids as $customers_id) {
				$customers[] = new self($customers_id);
			}

			return $customers;
		}
		else return [];
	}*/

	public static function get_customers_with_outstanding_invoices() {
		if ($customers_data = prepared_query::fetch("SELECT ia.customers_id, ia.customer_display_label, ia.terms_days, ia.terms_label, SUM(ia.invoice_balance) as balance, MAX(ia.invoice_age) as age, IFNULL(up.amount, 0) as unapplied_total FROM ckv_invoice_aging ia LEFT JOIN (SELECT customers_id, SUM(unapplied_amount) as amount FROM ckv_unapplied_payments GROUP BY customers_id) up ON ia.customers_id = up.customers_id GROUP BY ia.customers_id", cardinality::SET)) {
			$customers = [];

			foreach ($customers_data as $cust) {
				$c = new self($cust['customers_id']);
				$c->set_prop('display_label', $cust['customer_display_label']);
				$c->set_prop('terms', $cust['terms_days']);
				$c->set_prop('terms_label', $cust['terms_label']);
				$c->set_prop('customer_balance', $cust['balance']);
				$c->set_prop('customer_age', $cust['age']);
				$c->set_prop('unapplied_total', $cust['unapplied_total']);
				$customers[] = $c;
			}

			return $customers;
		}
		else return [];
	}

	public static function get_customers_with_late_invoices_to_notify() {
		prepared_query::execute('UPDATE customers c LEFT JOIN customer_terms ct ON c.dealer_pay_module = ct.legacy_dealer_pay_module LEFT JOIN acc_invoices i ON c.customers_id = i.customer_id AND i.paid_in_full = 0 AND i.inv_order_id IS NOT NULL AND DATEDIFF(CURDATE(), DATE(i.inv_date)) > IFNULL(ct.terms_days, 0) SET c.first_late_notice_date = NULL WHERE i.invoice_id IS NULL AND c.first_late_notice_date IS NOT NULL');

		$pms = ck_payment_method_lookup::instance()->get_list('code');

		if ($customers_ids = prepared_query::fetch('SELECT DISTINCT c.customers_id FROM acc_invoices i JOIN customers c ON i.customer_id = c.customers_id LEFT JOIN customer_terms ct ON c.dealer_pay_module = ct.legacy_dealer_pay_module JOIN orders o ON i.inv_order_id = o.orders_id WHERE i.paid_in_full = 0 AND c.send_late_notice = 1 AND o.payment_method_id NOT IN (:net10, :amazon) AND DATEDIFF(CURDATE(), DATE(i.inv_date)) > IFNULL(ct.terms_days, 0)', cardinality::COLUMN, [':net10' => $pms['net10']['payment_method_id'], ':amazon' => $pms['amazon']['payment_method_id']])) {
			$customers = [];

			foreach ($customers_ids as $customers_id) {
				$customers[] = new self($customers_id);
			}

			return $customers;
		}
		else return [];
	}

	public function account_needs_setup() {
		$account_setup = prepared_query::fetch('SELECT account_setup FROM customers WHERE customers_id = :customers_id', cardinality::SINGLE, [':customers_id' => $this->id()]);
		if ($account_setup == 0) return TRUE;
		return FALSE;
	}

	public function get_order_info_by_timeframe($timeframe) {
		if (!$this->has_orders()) return [];

		$date_check = NULL;

		if ($timeframe == 'lifetime') $date_check = new DateTime('today -50 year');
		elseif ($timeframe == 'year') $date_check = new DateTime('today -1 year');
		elseif ($timeframe == 'month') $date_check = new DateTime('today -1 month');
		elseif ($timeframe == 'week') $date_check = new DateTime('today -1 week');
		elseif ($timeframe == 'day') $date_check = new DateTime('today -1 day');

		$order_info = [
			'dollars' => 0.00,
			'orders' => 0
		];

		if (!empty($date_check)) {
			foreach ($this->get_orders() as $order) {
				if ($order->get_header('date_purchased') >= $date_check) {
					$order_info['dollars'] += floatval($order->get_simple_totals('total'));
					$order_info['orders'] ++;
				}
			}
		}
		return $order_info;
	}

	public function has_used_po_number_before($purchase_order_number) {
		$result = prepared_query::fetch('SELECT EXISTS( SELECT purchase_order_number FROM orders WHERE customers_id = :customers_id AND purchase_order_number = :purchase_order_number)', cardinality::SINGLE, [':customers_id' => $this->id(), ':purchase_order_number' => $purchase_order_number]);
		return CK\fn::check_flag($result);
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	// for the moment this is geared towards created customer accounts for ebay orders that come in - which means certain requirements are relaxed.
	public static function create(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (empty($data['header']['customer_price_level_id'])) $data['header']['customer_price_level_id'] = 1;
			$email_address = explode('@', $data['header']['customers_email_address']);
			$data['header']['email_domain'] = strtolower(trim(end($email_address)));
			$params = new ezparams($data['header']);
			$customers_id = prepared_query::insert('INSERT INTO customers ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', $params->query_vals(NULL, TRUE));

			prepared_query::execute('INSERT INTO customers_info (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created, customers_info_date_account_last_modified) VALUES (:customers_id, 0, NOW(), NOW())', [':customers_id' => $customers_id]);

			$customer = new self($customers_id);

			$address_book_id = $customer->create_address($data['address']);

			$customer->set_default_address($address_book_id);

			if (!$customer->has('sales_team_id') && ($quote = prepared_query::fetch('SELECT * FROM customer_quote WHERE NULLIF(customers_id, 0) IS NULL AND customer_email = :customer_email AND NULLIF(sales_team_id, 0) IS NOT NULL AND TO_DAYS(created) > TO_DAYS(NOW()) - 30 ORDER BY created DESC', cardinality::ROW, [':customer_email' => $data['header']['customers_email_address']]))) {
				if (!empty($quote['account_manager_id'])) $customer->change_account_manager($quote['account_manager_id']);
				elseif (!empty($quote['sales_team_id'])) $customer->change_sales_team($quote['sales_team_id']);
			}

			// assign a sales team if not assigned already
			if (!$customer->has('sales_team_id')) ck_team::auto_assign_sales_team($customer);

			try {
				$hubspot = new api_hubspot;
				$hubspot->update_company($customer);
			}
			catch (Exception $e) {
				// fail silently - we don't need to alert the customer that a hubspot update failed
			}

			prepared_query::transaction_commit($savepoint_id);
			return $customer;
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to create customer: '.$e->getMessage());
		}
	}

	public function update(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$blacklist = ['account_manager_id', 'sales_team_id', 'customers_password'];
			foreach ($blacklist as $field) {
				if (isset($data[$field])) {
					$data[$field] = NULL;
					unset($data[$field]);
				}
			}

			if (!empty($data['customers_email_address'])) {
				$email_address = explode('@', $data['customers_email_address']);
				$data['email_domain'] = strtolower(trim(end($email_address)));
			}

			$params = new ezparams($data);
			prepared_query::execute('UPDATE customers SET '.$params->update_cols(TRUE).' WHERE customers_id = :customers_id', $params->query_vals(['customers_id' => $this->id()], TRUE));
			prepared_query::execute('UPDATE customers_info SET customers_info_date_account_last_modified = NOW() WHERE customers_info_id = :customers_id', [':customers_id' => $this->id()]);

			$this->skeleton->rebuild();

			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Error updating customer: '.$e->getMessage());
		}
	}

	public function update_credit_status(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (empty($data['customer_credit_status_id'])) $data['customer_credit_status_id'] = $this->get_header('credit_status_id');
			if (!isset($data['dealer_pay_module'])) $data['dealer_pay_module'] = $this->get_header('legacy_dealer_pay_module');
			if (!isset($data['credit_limit'])) $data['credit_limit'] = $this->get_header('credit_limit');

			$header = [];
			if ($data['customer_credit_status_id'] != $this->get_header('credit_status_id')) $header['customer_credit_status_id'] = $data['customer_credit_status_id'];
			if ($data['dealer_pay_module'] != $this->get_header('legacy_dealer_pay_module')) $header['dealer_pay_module'] = $data['dealer_pay_module'];
			if ($data['credit_limit'] != $this->get_header('credit_limit')) $header['credit_limit'] = $data['credit_limit'];

			$history = [];
			$history['customer_credit_status_id'] = $data['customer_credit_status_id'];
			$history['customer_terms_id'] = prepared_query::fetch('SELECT customer_terms_id FROM customer_terms WHERE legacy_dealer_pay_module = :legacy_dealer_pay_module', cardinality::SINGLE, [':legacy_dealer_pay_module' => $data['dealer_pay_module']]);
			$history['credit_limit'] = $data['credit_limit'];
			if (!empty($data['comments'])) $history['comments'] = $data['comments'];
			if (!empty($data['admin_id'])) $history['admin_id'] = $data['admin_id'];

			if (!empty($header)) {
				$this->update($header);
				$this->create_credit_status_history($history);
			}
			elseif (!empty($history['comments'])) $this->create_credit_status_history($history);

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed updating customer credit status: '.$e->getMessage());
		}
	}

	private function create_credit_status_history(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['customers_id'] = $this->id();

			if (empty($data['customer_credit_status_id'])) throw new CKCustomerException('Cannot create status history record without the status.');

			if (empty($data['admin_id'])) $data['admin_id'] = !empty(ck_admin::login_instance())?ck_admin::login_instance()->id():ck_admin::$solutionsteam_id;

			$params = new ezparams($data);
			prepared_query::execute('INSERT INTO customer_credit_status_history ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', CK\fn::parameterize($data));

			$this->skeleton->rebuild('credit_status_history');

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed creating customer credit status history: '.$e->getMessage());
		}
	}

	public function rebuild_header() {
		$this->skeleton->rebuild('header');
	}

	public function create_address(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			foreach (self::$validation as $field => $details) {
				if (in_array($field, ['password', 'customers_email_address'])) continue;
				if ($field == 'entry_state' && !empty($data['entry_zone_id'])) continue;

				if (strlen(@$data[$field]) < $details['min_length']) {
					$field = strtoupper(preg_replace(['/entry_/', '/_/'], ['', ' '], $field));
					throw new CKCustomerException('Minimum length for '.$field.' is '.$details['min_length']);
				}
			}
			$data['customers_id'] = $this->id();
			$address = CK\fn::parameterize($data);
			$address_book_id = prepared_query::insert('INSERT INTO address_book (customers_id, entry_firstname, entry_lastname, entry_company, entry_street_address, entry_suburb, entry_postcode, entry_city, entry_country_id, entry_telephone, entry_zone_id, entry_state) VALUES (:customers_id, :entry_firstname, :entry_lastname, :entry_company, :entry_street_address, :entry_suburb, :entry_postcode, :entry_city, :entry_country_id, :entry_telephone, :entry_zone_id, :entry_state)', $address);

			$this->skeleton->rebuild('addresses');

			prepared_query::transaction_commit($savepoint_id);
			return $address_book_id;
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to create address on customer: '.$e->getMessage());
		}
	}

	public function edit_address($address_book_id, Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			foreach ($data as $field => $value) {
				if ($field == 'entry_state' && !empty($data['entry_zone_id'])) continue;
				if (empty(self::$validation[$field]['min_length'])) continue;

				if (strlen($value) < self::$validation[$field]['min_length']) {
					$field = strtoupper(preg_replace('/entry_/', '', $field));
					throw new CKCustomerException('Minimum length for '.$field.' is '.self::$validation[$field]['min_length']);
				}
			}
			$address = new prepared_fields($data, prepared_fields::UPDATE_QUERY);
			$id = new prepared_fields(['customers_id' => $this->id(), 'address_book_id' => $address_book_id]);

			prepared_query::execute('UPDATE address_book SET '.$address->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($address, $id));

			$this->skeleton->rebuild('addresses');

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to edit address on customer: '.$e->getMessage());
		}
	}

	public function set_default_address($address_book_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('UPDATE customers SET customers_default_address_id = :address_book_id WHERE customers_id = :customers_id', [':address_book_id' => $address_book_id, ':customers_id' => $this->id()]);

			$this->skeleton->rebuild('header');

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to set default address: '.$e->getMessage());
		}
	}

	public function delete_address($address_book_id) {
		$savepoint_id = prepared_query::transaction_begin();
		try {
			prepared_query::execute('DELETE FROM address_book WHERE address_book_id = :address_book_id AND customers_id = :customers_id', [':address_book_id' => $address_book_id, ':customers_id' => $this->id()]);
			$this->skeleton->rebuild('addresses');
			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to delete address');
		}
	}

	public function revalidate_login($password) {
		if (!empty($_SESSION['customer_extra_login_id'])) $email = prepared_query::fetch('SELECT customers_emailaddress FROM customers_extra_logins WHERE customers_extra_logins_id = :customers_extra_logins_id', cardinality::SINGLE, [':customers_extra_logins_id' => $_SESSION['customer_extra_login_id']]);
		elseif (!empty($_SESSION['customer_id'])) $email = $this->get_header('email_address');

		$login = self::attempt_login($email, $password);

		if ($login['status'] == self::LOGIN_STATUS_PASS) return TRUE;
		else return FALSE;
	}

	public function update_password($password, $account_id=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (empty($password)) throw new CKCustomerException('New password cannot be empty.');

			$pinfo = password_get_info($password);
			if ($pinfo['algo'] == 0) $password = self::encrypt_password($password);

			if (empty($account_id)) {
				self::execute('update_password_customer', [':password' => $password, ':customers_id' => $this->id()]);
				prepared_query::execute('UPDATE customers_info SET customers_info_date_account_last_modified = NOW() WHERE customers_info_id = :customers_id', [':customers_id' => $this->id()]);
			}
			else self::execute('update_password_extra_login', [':password' => $password, ':customers_id' => $this->id(), ':customers_extra_logins_id' => $account_id]);

			prepared_query::transaction_commit();
			return TRUE;
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to handle password: '.$e->getMessage());
		}
	}

	public function generate_forgot_password_code($customers_extra_logins_id=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$code = self::generate_code(['charset_weights' => [.4, .3, .3]]);

			if (empty($customers_extra_logins_id)) {
				prepared_query::execute('UPDATE customers SET reset_code = :reset_code, reset_code_timestamp = NOW() WHERE customers_id = :customers_id', [':reset_code' => $code, ':customers_id' => $this->id()]);
			}
			else {
				prepared_query::execute('UPDATE customers_extra_logins SET reset_code = :reset_code, reset_code_timestamp = NOW() WHERE customers_extra_logins_id = :customers_extra_logins_id', [':reset_code' => $code, ':customers_extra_logins_id' => $customers_extra_logins_id]);
			}

			prepared_query::transaction_commit();
			return $code;
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to handle password');
		}
	}

	public function add_contact($contact) {
		$ccid = prepared_query::insert('INSERT INTO customer_contacts (customer_id, type_id, email_address) VALUES (:customers_id, :contact_type_id, :email_address)', $contact);

		$this->skeleton->rebuild('contacts');

		return $ccid;
	}

	public function remove_contact($contact_id) {
		prepared_query::execute('DELETE FROM customer_contacts WHERE id = :contact_id', [':contact_id' => $contact_id]);

		$this->skeleton->rebuild('contacts');
	}

	public function add_extra_login($extra_login) {
		$this->skeleton->rebuild('extra_logins');

		// using ON DUPLICATE KEY UPDATE customers_extra_logins_id = LAST_INSERT_ID(customers_extra_logins_id) allows LAST_INSERT_ID() to return our updated row ID in the case of an update rather than an insert
		// per http://stackoverflow.com/questions/778534/mysql-on-duplicate-key-last-insert-id
		return prepared_query::insert('INSERT INTO customers_extra_logins (customers_emailaddress, customers_firstname, customers_lastname, customers_password, customers_id, old_customers_id, active, rfq_free_ground_shipping, copy_account) VALUES (:email_address, :first_name, :last_name, :password, :customers_id, :old_customers_id, :active, :rfq_free_ground_shipping, :copy_account) ON DUPLICATE KEY UPDATE customers_extra_logins_id = LAST_INSERT_ID(customers_extra_logins_id), customers_firstname = VALUES(customers_firstname), customers_lastname = VALUES(customers_lastname), customers_password = VALUES(customers_password), customers_id = VALUES(customers_id), old_customers_id = VALUES(old_customers_id), active = VALUES(active), rfq_free_ground_shipping = VALUES(rfq_free_ground_shipping), copy_account = VALUES(copy_account)', $extra_login);
	}

	public function add_price($price) {
		prepared_query::execute('INSERT INTO ipn_to_customers (stock_id, customers_id, price, managed_category) VALUES (:stock_id, :customers_id, :price, :managed_category) ON DUPLICATE KEY UPDATE price = VALUES(price), managed_category = VALUES(managed_category)', $price);
		// nothing relevant to return, since the primary key is stock_id/customers_id

		$this->skeleton->rebuild('prices');
	}

	public function remove_price($stock_id) {
		prepared_query::execute('DELETE FROM ipn_to_customers WHERE customers_id = :customers_id AND stock_id = :stock_id', [':customers_id' => $this->id(), ':stock_id' => $stock_id]);

		$this->skeleton->rebuild('prices');
	}

	public function remove_all_prices() {
		prepared_query::execute('DELETE FROM ipn_to_customers WHERE customers_id = :customers_id', [':customers_id' => $this->id()]);

		$this->skeleton->rebuild('prices');
	}

	public function add_category_discount($discount) {
		$ccd_id = prepared_query::insert('INSERT INTO ck_customer_category_discount (customer_id, category_id, discount, status, start_date, end_date) VALUES (:customers_id, :categories_id, :discount_pctg, :status, :start_date, :end_date) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), discount = VALUES(discount), status = VALUES(status), start_date = VALUES(start_date), end_date = VALUES(end_date)', $discount);

		$category = new ck_listing_category($discount[':categories_id']);
		if ($discount[':status'] == 1) {
			$category->add_category_discount(['discount' => $discount[':discount_pctg'], 'customers_id' => $discount[':customers_id']]);
		}
		else {
			$category->remove_category_discount($discount[':customers_id']);
		}


		$this->skeleton->rebuild('category_discounts');

		return $ccd_id;
	}

	public function remove_category_discount($discount_id) {
		foreach ($this->get_category_discounts() as $discount) {
			if ($discount['discount_id'] != $discount_id) continue;

			$category = new ck_listing_category($discount['categories_id']);
			$category->remove_category_discount($this->id());
		}

		prepared_query::execute('DELETE FROM ck_customer_category_discount WHERE id = :discount_id', [':discount_id' => $discount_id]);

		$this->skeleton->rebuild('category_discounts');
	}

	public function add_notification($products_id) {
		if (!$this->get_notifications($products_id)) {
			// for now we'll hit the DB directly rather than abstract this process away - haven't figured out final strategy here
			prepared_query::execute('INSERT INTO products_notifications (products_id, customers_id, date_added) values (:products_id, :customers_id, NOW())', [':products_id' => $products_id, ':customers_id' => $this->id()]);

			$this->skeleton->rebuild('notifications');
		}
	}

	public function remove_notification() {
		if ($this->get_notifications($products_id)) {
			// for now we'll hit the DB directly rather than abstract this process away - haven't figured out final strategy here
			prepared_query::execute('DELETE FROM products_notifications WHERE products_id = :products_id AND customers_id = :customers_id', [':products_id' => $products_id, ':customers_id' => $this->id()]);

			$this->skeleton->rebuild('notifications');
		}
	}

	public function remove_self() {
		$param = [':customers_id' => $this->id()];

		// non-"core" data
		prepared_query::execute('DELETE FROM customers_basket_attributes WHERE customers_id = :customers_id', $param);
		prepared_query::execute('DELETE FROM customers_basket WHERE customers_id = :customers_id', $param);
		prepared_query::execute('DELETE FROM whos_online WHERE customer_id = :customers_id', $param);

		// semi-"core" data
		prepared_query::execute('DELETE FROM sources_other WHERE customers_id = :customers_id', $param);

		// "core" data"
		prepared_query::execute('DELETE FROM customers_info WHERE customers_info_id = :customers_id', $param);
		prepared_query::execute('DELETE FROM address_book WHERE customers_id = :customers_id', $param);
		prepared_query::execute('DELETE FROM customers WHERE customers_id = :customers_id', $param);

		$this->skeleton->rebuild();
	}

	private $process_errors = [];
	private $process_errors_meta = [];

	private function reset_process_errors() {
		$this->process_errors = [];
		$this->process_errors_meta = [];
	}

	private function add_process_error($type, $typekey=NULL, $err) {
		if (!empty($this->process_errors_meta[$type])) {

			// check to see if we've previously assigned errors directly, but now we're trying to assign to children
			if ($this->process_errors_meta[$type] == 'direct' && !empty($typekey)) {
				// we've previously set at least one error directly to this $type, but now we're trying to set it to a $typekey below it

				// re-reference the direct entries below the $typekey "_direct"
				$this->process_errors[$type] = ['_direct' => $this->process_errors[$type]];

				// change the meta accordingly
				$this->process_errors_meta[$type] = 'children';
			}

			// check to see if we've previously assigned errors to children, but now we're trying to assign direclty
			elseif ($this->process_errors_meta[$type] == 'children' && empty($typekey)) {
				// we've previously set at least one error to a child, but now we're trying to set it directly to the $type

				// we can just assign our $typekey and it'll work
				$typekey = '_direct';
			}
		}

		if (empty($typekey)) {
			// this is the first error for this $type
			if (empty($this->process_errors[$type])) $this->process_errors[$type] = $err;
			// this is the third or later error for this $type
			elseif (is_array($this->process_errors[$type])) $this->process_errors[$type][] = $err;
			// this is the second error for this $type
			else $this->process_errors[$type] = [$this->process_errors[$type], $err];
		}
		else {
			// we haven't used this $type before
			if (empty($this->process_errors[$type])) $this->process_errors[$type] = [];

			// this is the first error for this $type->$typekey
			if (empty($this->process_errors[$type][$typekey])) $this->process_errors[$type][$typekey] = $err;
			// this is the third or later error for this $type->$typekey
			elseif (is_array($this->process_errors[$type][$typekey])) $this->process_errors[$type][$typekey][] = $err;
			// this is the second error for this $type->$typekey
			else $this->process_errors[$type][$typekey] = [$this->process_errors[$type][$typekey], $err];
		}
	}

	// this should be reworked into a controller
	public function process_account($action) {
		$this->reset_process_errors();

		switch ($action) {
			case 'update-account-manager':
				if ($this->get_header('account_manager_id') != $_POST['account_manager_id']) {
					$this->change_account_manager($_POST['account_manager_id']);

					try {
						$hubspot = new api_hubspot;
						$hubspot->update_company($this);
					}
					catch (Exception $e) {
						// fail silently
					}
				}
				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id().'#account-management');
				break;
			case 'update-sales-team':
				if ($this->get_header('sales_team_id') != $_POST['sales_team_id']) {
					$this->change_sales_team($_POST['sales_team_id']);

					try {
						$hubspot = new api_hubspot;
						$hubspot->update_company($this);
					}
					catch (Exception $e) {
						// fail silently
					}
				}
				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id().'#account-management');
				break;
			case 'update-customer-segment':
				if ($this->get_header('customer_segment_id') != $_POST['customer_segment_id']) {
					$this->update(['customer_segment_id' => $_POST['customer_segment_id']]);

					try {
						$hubspot = new api_hubspot;
						$hubspot->update_company($this);
					}
					catch (Exception $e) {
						// fail silently
					}
				}
				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id().'#account-management');
				break;
			case 'update-price-level':
				if ($this->get_header('customer_price_level_id') != $_POST['customer_price_level_id']) {
					$this->update([
						'customer_price_level_id' => $_POST['customer_price_level_id'],
						'customer_type' => $_POST['customer_price_level_id']==1?0:1,
					]);

					try {
						$hubspot = new api_hubspot;
						$hubspot->update_company($this);
					}
					catch (Exception $e) {
						// fail silently
					}
				}
				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id().'#account-management');
				break;
			case 'update-has-recieved-mousepad':
				$mousepad = CK\fn::check_flag($_POST['has_received_mousepad'])?1:0;
				if ($this->get_header('has_received_mousepad') != $mousepad) {
					$this->update(['has_received_mousepad' => $mousepad]);

					try {
						$hubspot = new api_hubspot;
						$hubspot->update_company($this);
					}
					catch (Exception $e) {
						// fail silently
					}
				}
				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id().'#account-management');
				break;
			case 'update_vendor':
				$vendor_id = $_GET['vID'];

				prepared_query::execute('UPDATE customers c SET c.vendor_id = :vendors_id WHERE c.customers_id = :customers_id', [':vendors_id' => $vendor_id, ':customers_id' => $this->id()]);
				echo prepared_query::fetch('SELECT vendors_company_name FROM vendors v WHERE v.vendors_id = :vendors_id', cardinality::SINGLE, [':vendors_id' => $vendor_id]);

				// nothing to rebuild, this is an ajax call
				exit();
				break;
			case 'update_crm':
				try {
					$hubspot = new api_hubspot;
					$hubspot->update_company($this);

					if ($this->has_extra_logins()) {
						foreach ($this->get_extra_logins('active') as $extra_login) {
							$hubspot->update_contact($this, $extra_login);
							api_hubspot::rate_limit();
						}
					}
				}
				catch (Exception $e) {
					// fail silently
				}

				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				break;
			case 'deleteconfirm':
				$this->remove_self();
				CK\fn::redirect_and_exit('/admin/customers_list.php?'.CK\fn::qs(['customers_id', 'action']));
				break;
			case 'update':
				if (strlen($_POST['first_name']) < self::$validation['entry_firstname']['min_length']) {
					$this->add_process_error('header', 'first_name', '&nbsp;<span class="errorText">min '.self::$validation['entry_firstname']['min_length'].' chars</span>');
				}

				if (strlen($_POST['last_name']) < self::$validation['entry_lastname']['min_length']) {
					$this->add_process_error('header', 'last_name', '&nbsp;<span class="errorText">min '.self::$validation['entry_lastname']['min_length'].' chars</span>');
				}

				if (strlen($_POST['customers_email_address']) < self::$validation['customers_email_address']['min_length']) {
					$this->add_process_error('header', 'customers_email_address', '&nbsp;<span class="errorText">min '.self::$validation['customers_email_address']['min_length'].' chars</span>');
				}

				if (!service_locator::get_mail_service()::validate_address($_POST['customers_email_address'])) {
					$this->add_process_error('header', 'customers_email_address', '&nbsp;<span class="errorText">The email address doesn\'t appear to be valid!</span>');
				}

				if (self::email_exists($_POST['customers_email_address'], $this->id())) {
					$this->add_process_error('header', 'customers_email_address', '&nbsp;<span class="errorText">This email address already exists!</span>');
				}

				if (strlen($_POST['address1']) < self::$validation['entry_street_address']['min_length']) {
					$this->add_process_error('header', 'address1', '&nbsp;<span class="errorText">min '.self::$validation['entry_street_address']['min_length'].' chars</span>');
				}

				if (strlen($_POST['postcode']) < self::$validation['entry_postcode']['min_length']) {
					$this->add_process_error('header', 'postcode', '&nbsp;<span class="errorText">min '.self::$validation['entry_postcode']['min_length'].' chars</span>');
				}

				if (strlen($_POST['city']) < self::$validation['entry_city']['min_length']) {
					$this->add_process_error('header', 'city', '&nbsp;<span class="errorText">min '.self::$validation['entry_city']['min_length'].' chars</span>');
				}

				if (empty($_POST['countries_id']) || !$_POST['countries_id']) {
					$this->add_process_error('header', 'countries_id', '&nbsp;<span class="errorText">please select a country from the dropdown.</span>');
					$this->add_process_error('header', 'state', '&nbsp;<span class="errorText">required</span>');
				}

				$zone_id = 0;
				if ((!empty($_POST['countries_id']) || $_POST['countries_id']) && ($regions = ck_address2::get_regions($_POST['countries_id']))) {
					foreach ($regions as $region) {
						if (in_array(strtoupper(trim($_POST['state'])), [strtoupper($region['zone_name']), strtoupper($region['zone_code'])])) {
							$zone_id = $region['zone_id'];
							break;
						}
					}

					if (empty($zone_id)) $this->add_process_error('header', 'state', '&nbsp;<span class="errorText">required</span>');
				}

				if (!$this->has_process_errors('header')) {
					$header = $this->get_header();

					$email_address = explode('@', $_POST['customers_email_address']);
					$email_domain = strtolower(trim(end($email_address)));

					$data = [
						'customers_firstname' => $_POST['first_name'],
						'customers_lastname' => $_POST['last_name'],
						'customers_email_address' => $_POST['customers_email_address'],
						'customers_telephone' => $_POST['telephone'],
						'customers_fax' => $_POST['fax'],
						'customers_notes' => $_POST['notes'],
						'customers_notes_sales_rep' => $_POST['sales_rep_notes'],
						'customers_newsletter' => $_POST['newsletter_subscribed'],
						'email_domain' => $email_domain,
						'mailchimp_synced' => 0,
						'templates_id' => 0,
						'customers_fedex' => $_POST['fedex_account_number'],
						'customers_ups' => $_POST['ups_account_number'],
						'send_late_notice' => $_POST['send_late_notice'],
						'dealer_shipping_module' => $_POST['own_shipping_account'],
						'aim_screenname' => $_POST['aim_screenname'],
						'msn_screenname' => $_POST['msn_screenname'],
						'company_account_contact_name' => $_POST['company_account_contact_name'],
						'company_account_contact_email' => $_POST['company_account_contact_email'],
						'company_account_contact_phone_number' => $_POST['company_account_contact_phone_number'],
						'disable_standard_shipping' => CK\fn::check_flag(@$_POST['disable_standard_shipping'])?1:0,
						'amazon_account' => CK\fn::check_flag(@$_POST['amazon_account'])?1:0,
						'use_reclaimed_packaging' => CK\fn::check_flag(@$_POST['use_reclaimed_packaging'])?1:0
					];

					$this->update($data);

					switch (TRUE) {
						case $header['credit_status_id'] != $_POST['credit_status_id']:
						case $header['credit_limit'] != CK\text::demonetize($_POST['credit_limit']):
						case $header['legacy_dealer_pay_module'] != $_POST['legacy_dealer_pay_module']:
						case !empty(trim($_POST['credit_status_comment'])):
							$credit = [
								'customer_credit_status_id' => $_POST['credit_status_id'],
								'comments' => trim($_POST['credit_status_comment']),
								'credit_limit' => CK\text::demonetize($_POST['credit_limit']),
								'dealer_pay_module' => $_POST['legacy_dealer_pay_module']
							];

							$this->update_credit_status($credit);
							break;
						default:
							break;
					}

					/*if (!empty($_POST['password']) && $_POST['password']) {
						$this->update_password($_POST['password']);
						//prepared_query::execute('UPDATE customers SET customers_password = :password WHERE customers_id = :customers_id', [':password' => self::encrypt_password($_POST['password']), ':customers_id' => $this->id()]);
					}*/

					$address_fields = [
						'first_name' => NULL,
						'last_name' => NULL,
						'address1' => NULL,
						'address2' => NULL,
						'postcode' => NULL,
						'city' => NULL,
						'state' => NULL,
						'zone_id' => $zone_id,
						'countries_id' => NULL,
						'company_name' => NULL,
						'website' => NULL,
						'customers_id' => $this->id(),
						'default_address_id' => NULL
					];

					$address = CK\fn::parameterize($address_fields, $_POST);

					if (!(prepared_query::fetch('SELECT address_book_id FROM address_book WHERE address_book_id = :address_book_id AND customers_id = :customers_id', cardinality::SINGLE, [':address_book_id' => $address[':default_address_id'], ':customers_id' => $address[':customers_id']]))) $address[':default_address_id'] = NULL;

					$address_book_id = prepared_query::insert('INSERT INTO address_book (address_book_id, customers_id, entry_firstname, entry_lastname, entry_street_address, entry_suburb, entry_postcode, entry_city, entry_state, entry_zone_id, entry_country_id, entry_company, entry_company_website) VALUES (:default_address_id, :customers_id, :first_name, :last_name, :address1, :address2, :postcode, :city, :state, :zone_id, :countries_id, :company_name, :website) ON DUPLICATE KEY UPDATE entry_firstname=VALUES(entry_firstname), entry_lastname=VALUES(entry_lastname), entry_street_address=VALUES(entry_street_address), entry_suburb=VALUES(entry_suburb), entry_postcode=VALUES(entry_postcode), entry_city=VALUES(entry_city), entry_state=VALUES(entry_state), entry_zone_id=VALUES(entry_zone_id), entry_country_id=VALUES(entry_country_id), entry_company=VALUES(entry_company), entry_company_website=VALUES(entry_company_website)', $address);

					if (empty($address[':default_address_id'])) prepared_query::execute('UPDATE customers SET customers_default_address_id = :address_book_id WHERE customers_id = :customers_id', [':address_book_id' => $address_book_id, ':customers_id' => $this->id()]);

					//prepared_query::execute('UPDATE address_book SET entry_firstname = :first_name, entry_lastname = :last_name, entry_street_address = :address1, entry_suburb = :address2, entry_postcode = :postcode, entry_city = :city, entry_state = :state, entry_zone_id = :zone_id, entry_country_id = :countries_id, entry_company = :company_name, entry_company_website = :website WHERE customers_id = :customers_id AND address_book_id = :default_address_id', $address);

					$this->skeleton->rebuild('addresses');
					$this->build_addresses();

					if ($GLOBALS['__FLAG']['vendor_portal_accessory_finder']) $this->grant_permission('vendor_portal.accessory_finder');
					else $this->revoke_permission('vendor_portal.accessory_finder');

					if (!empty($_POST['add_tax_exemption'])) {
						foreach ($_POST['add_tax_exemption'] as $zone_id => $status) {
							if (!CK\fn::check_flag($status)) continue;
							else prepared_query::execute('INSERT INTO customer_tax_exemptions (customers_id, countries_id, zone_id, tax_exempt) SELECT :customers_id, zone_country_id, :zone_id, 1 FROM zones WHERE zone_id = :zone_id', [':customers_id' => $this->id(), ':zone_id' => $zone_id]);
						}
					}

					if (!empty($_POST['tax_exemption'])) {
						foreach ($_POST['tax_exemption'] as $customer_tax_exemption_id => $status) {
							if (!CK\fn::check_flag($status)) prepared_query::execute('DELETE FROM customer_tax_exemptions WHERE customer_tax_exemption_id = :customer_tax_exemption_id', [':customer_tax_exemption_id' => $customer_tax_exemption_id]);
							else prepared_query::execute('UPDATE customer_tax_exemptions SET tax_exempt = :tax_exempt WHERE customer_tax_exemption_id = :customer_tax_exemption_id', [':tax_exempt' => CK\fn::check_flag($status)?1:0, ':customer_tax_exemption_id' => $customer_tax_exemption_id]);
						}
					}

					$this->skeleton->rebuild('tax_exemptions');

					try {
						$hubspot = new api_hubspot;
						$hubspot->update_company($this);
					}
					catch (Exception $e) {
						// fail silently
					}

					CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				}
				break;
			case 'pricing':
				if (CK\fn::check_flag(@$_POST['delete_pricing'])) {
					$this->remove_all_prices();
				}
				else {
					foreach ($_POST['p_price'] as $stock_id => $price) {
						$price = preg_replace('/[^0-9.]/', '', $price);
						if (empty($price) || CK\fn::check_flag(@$_POST['delete_special'][$stock_id])) {
							$this->remove_price($stock_id);
						}
						else {
							$ipn = new ck_ipn2($stock_id);

							$this->add_price([':stock_id' => $stock_id, ':customers_id' => $this->id(), ':price' => $price, ':managed_category' => 0]);
						}
					}
				}

				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				break;
			case 'ipn_search':
				// ajax response
				$ipns = ck_ipn2::get_ipns_by_match($_GET['search']); ?>
				<ul>
				<?php foreach ($ipns as $ipn) {
					// using the prices from get_header() just means we can skip addtl queries to fill in price
					switch ($this->get_header('customer_price_level_id')) {
						case 4: $price = $ipn->get_header('wholesale_low_price'); break;
						case 3: $price = $ipn->get_header('wholesale_high_price'); break;
						case 2: $price = $ipn->get_header('dealer_price'); break;
						case 1:
						default:
							$price = $ipn->get_header('stock_price');
							break;
					} ?>
					<li id="<?= $ipn->get_header('stock_id'); ?>:<?= $ipn->get_header('ipn'); ?>:<?= $ipn->get_header('average_cost'); ?>:<?= $price; ?>"><?= $ipn->get_header('ipn'); ?></li>
				<?php } ?>
				</ul>
				<?php exit();
				break;
			case 'ipn_add_pricing':
				$this->add_price([':stock_id' => $_POST['stock_id'], ':customers_id' => $this->id(), ':price' => $_POST['ipn_add_price'], ':managed_category' => 0]);
				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				break;
			case 'set-fraud-flag':
				$this->set_fraud_flag();
				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				break;
			case 'remove-fraud-flag':
				$this->remove_fraud_flag();
				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
		}
	}

	public function process_extra_login($action) {
		$this->reset_process_errors();

		switch ($action) {
			case 'extra_login_add':
				$err = '';

				$email_address = trim($_POST['customers_email_address']);

				if (empty($email_address)) $err .= 'Email address required.<br>';
				if (empty($_POST['customers_password']) || !$_POST['customers_password']) $err .= 'Password required.<br>';
				if (strlen($_POST['customers_password']) < 5) $err .= 'Password must be at least 5 chars.<br>';
				if (empty($_POST['first_name']) || !$_POST['first_name']) $err .= 'First name required.<br>';
				if (empty($_POST['last_name']) || !$_POST['last_name']) $err .= 'Last name required.<br>';

				if (empty($_POST['copy_account']) || !$_POST['copy_account']) $_POST['copy_account'] = 0;
				else $_POST['copy_account'] = 1;

				if (!CK\fn::check_flag(@$_POST['reactivate']) && self::email_exists($email_address)) {
					$err = 'The email address '.$email_address.' is already in use.<br>'.$err;
				}

				if (!empty($err)) $this->add_process_error('extra_logins', 'new', $err);
				else {
					if (CK\fn::check_flag(@$_POST['reactivate'])) {
						if ($customers_extra_logins_id = prepared_query::fetch('SELECT customers_extra_logins_id FROM customers_extra_logins WHERE TRIM(customers_emailaddress) LIKE :email AND customers_id = :customers_id', cardinality::SINGLE, [':email' => $email_address, ':customers_id' => $this->id()])) {
							prepared_query::execute('UPDATE customers_extra_logins SET customers_password = :password, password_info = 0, customers_firstname = :first_name, customers_lastname = :last_name, active = 1, copy_account = :copy_account WHERE customers_extra_logins_id = :customers_extra_logins_id', [':password' => self::encrypt_password($_POST['customers_password']), ':first_name' => $_POST['first_name'], ':last_name' => $_POST['last_name'], ':customers_extra_logins_id' => $customers_extra_logins_id, ':copy_account' => $_POST['copy_account']]);
						}
						else {
							$this->add_process_error('extra_logins', 'new', 'Could not find extra login for email '.$email_address);
						}
					}
					else {
						$customers_extra_logins_id = prepared_query::insert('INSERT INTO customers_extra_logins (customers_emailaddress, customers_password, password_info, customers_firstname, customers_lastname, customers_id, active, copy_account) VALUES (:email_address, :password, 0, :first_name, :last_name, :customers_id, 1, :copy_account)', [':email_address' => $email_address, ':password' => self::encrypt_password($_POST['customers_password']), ':first_name' => $_POST['first_name'], ':last_name' => $_POST['last_name'], ':customers_id' => $this->id(), 'copy_account' => $_POST['copy_account']]);
					}

					if (!empty($customers_extra_logins_id)) {
						try {
							$hubspot = new api_hubspot;
							$hubspot->update_contact($this, ['first_name' => $_POST['first_name'], 'last_name' => $_POST['last_name'], 'email_address' => $email_address]);
						}
						catch (Exception $e) {
							// fail silently
						}
					}

					CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				}
				break;
			case 'extra_login_update':
				$customers_extra_logins_id = $_POST['customers_extra_logins_id'];
				$email_address = trim($_POST['customers_email_address']);

				$err = '';

				$extra_login = $this->get_extra_logins($customers_extra_logins_id);

				if (empty($extra_login)) $err .= 'Could not find Extra Login record.<br>';

				if (empty($email_address)) $err .= 'Email address required.<br>';
				$reset_password = FALSE;
				if ($_POST['customers_password'] != '*****') {
					$reset_password = TRUE;
					if (empty($_POST['customers_password']) || !$_POST['customers_password']) $err .= 'Password required.<br>';
					if (strlen($_POST['customers_password']) < 5) $err .= 'Password must be at least 5 chars.<br>';
				}
				if (empty($_POST['first_name']) || !$_POST['first_name']) $err .= 'First name required.<br>';
				if (empty($_POST['last_name']) || !$_POST['last_name']) $err .= 'Last name required.<br>';
				if (empty($_POST['copy_account']) || !$_POST['copy_account']) $_POST['copy_account'] = 0;
				else $_POST['copy_account'] = 1;

				if (strtolower(trim($extra_login['email_address'])) != strtolower($email_address) && self::email_exists($email_address, NULL, $customers_extra_logins_id)) {
					$err .= 'That email address is already in use.<br>';
				}

				if (!empty($err)) $this->add_process_error('extra_logins', $customers_extra_logins_id, $err);
				else {
					if ($reset_password) {
						prepared_query::execute('UPDATE customers_extra_logins SET customers_emailaddress = :email_address, customers_firstname = :first_name, customers_lastname = :last_name, copy_account = :copy_account WHERE customers_extra_logins_id = :customers_extra_logins_id', [':email_address' => $email_address, ':first_name' => $_POST['first_name'], ':last_name' => $_POST['last_name'], ':customers_extra_logins_id' => $customers_extra_logins_id, ':copy_account' => $_POST['copy_account']]);
						$this->update_password($_POST['customers_password'], $customers_extra_logins_id);
					}
					else {
						prepared_query::execute('UPDATE customers_extra_logins SET customers_emailaddress = :email_address, customers_firstname = :first_name, customers_lastname = :last_name, copy_account = :copy_account WHERE customers_extra_logins_id = :customers_extra_logins_id', [':email_address' => $email_address, ':first_name' => $_POST['first_name'], ':last_name' => $_POST['last_name'], ':customers_extra_logins_id' => $customers_extra_logins_id, ':copy_account' => $_POST['copy_account']]);
					}

					try {
						$hubspot = new api_hubspot;
						$hubspot->update_contact($this, ['first_name' => $_POST['first_name'], 'last_name' => $_POST['last_name'], 'email_address' => $email_address]);
					}
					catch (Exception $e) {
						// fail silently
					}

					CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				}
				break;
			case 'extra_login_delete':
				prepared_query::execute('UPDATE customers_extra_logins SET active = 0 WHERE customers_extra_logins_id = :customers_extra_logins_id', [':customers_extra_logins_id' => $_GET['customers_extra_logins_id']]);
				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				break;
			case 'extra_login_search':
				// ajax method, we're just spitting out HTML to be used by the client, should be refactored to return JSON

				if (strpos($_GET['search'], '@')) $fields = [':email' => '%'.trim($_GET['search']).'%'];
				elseif (strpos($_GET['search'], ',')) {
					$names = preg_split('/\s*,\s*/', $_GET['search'], 2);
					$fields = [':last_name' => '%'.trim($names[0]).'%', ':first_name' => '%'.trim($names[1]).'%'];
				}
				elseif (preg_match('/\s+/', $_GET['search'])) {
					$names = preg_split('/\s+/', $_GET['search'], 2);
					$fields = [':first_name' => '%'.trim($names[0]).'%', ':last_name' => '%'.trim($names[1]).'%'];
				}
				else $fields = [':email' => '%'.trim($_GET['search']).'%', ':first_name' => '%'.trim($_GET['search']).'%', ':last_name' => '%'.trim($_GET['search']).'%'];

				$fields[':main_only'] = 1;

				$fields[':simple_result'] = 1;

				echo '<ul>';

				if ($customers = self::get_customers_by_match($fields, 1, 50, ['c.customers_firstname' => 'ASC', 'c.customers_lastname' => 'ASC', 'c.customers_email_address' => 'ASC'])) {
					foreach ($customers as $customer) {
						if ($customer->get_header('customers_id') == $this->id()) continue;
						echo '<li id="'.$customer->get_header('customers_id').':'.$customer->get_header('email_address').'">'.$customer->get_header('first_name').' '.$customer->get_header('last_name').' - '.$customer->get_header('email_address').'</li>';
					}
				}

				echo '</ul>';
				exit();
				break;
			case 'extra_login_compare':
				// ajax method, we're just spitting out HTML to be used by the client, should be refactored to return JSON

				if ($this->id() == $_GET['conversion_customers_id']) die('The two customers are the same!');

				$convert_customer = new ck_customer2($_GET['conversion_customers_id']);

				$prices = [];

				if ($this->has_prices() || $convert_customer->has_prices()) {
					foreach ($this->get_prices() as $price) {
						if (empty($prices[$price['stock_id']])) $prices[$price['stock_id']] = ['ipn' => $price['ipn'], 'master' => 'N/A', 'convert' => 'N/A'];
						$prices[$price['stock_id']]['master'] = '$'.number_format($price['price'], 2);
					}

					foreach ($convert_customer->get_prices() as $price) {
						if (empty($prices[$price['stock_id']])) $prices[$price['stock_id']] = ['ipn' => $price['ipn'], 'master' => 'N/A', 'convert' => 'N/A'];
						$prices[$price['stock_id']]['convert'] = '$'.number_format($price['price'], 2);
					}
				} ?>

				<div class="customer-data">
					<h3>Special Pricing Compare</h3>
					<div class="customer-data-box">
						<table cellspacing="2" cellpadding="2" border="0">
							<tr>
								<td class="main">Product Name</td>
								<td class="main">Master Account</td>
								<td class="main">Conversion Account</td>
							</tr>
							<?php foreach ($prices as $price) { ?>
							<tr>
								<td class="main"><?= $price['ipn']; ?></td>
								<td class="main" align="right"><?= $price['master']; ?></td>
								<td class="main" align="right"><?= $price['convert']; ?></td>
							</tr>
							<?php } ?>
							<tr>
								<td colspan="3" align="right"><input type="button" value="Cancel" onClick="$('conversion_compare_table').hide();"><input type="submit" value="Convert"></td>
							</tr>
						</table>
					</div>
				</div>
				<?php
				exit();
				break;
			case 'extra_login_convert':
				if ($this->id() == $_REQUEST['conversion_customers_id']) die('The two customers are the same!');

				$convert_customer = new ck_customer2($_REQUEST['conversion_customers_id']);

				$extra_login[':email_address'] = $convert_customer->get_header('email_address');
				$extra_login[':first_name'] = $convert_customer->get_header('first_name');
				$extra_login[':last_name'] = $convert_customer->get_header('last_name');
				$extra_login[':old_customers_id'] = $convert_customer->get_header('customers_id');
				$extra_login[':active'] = 1;
				$extra_login[':rfq_free_ground_shipping'] = 0;
				$extra_login[':copy_account'] = 1;

				$extra_login[':customers_id'] = $this->id();

				$extra_login[':password'] = prepared_query::fetch('SELECT customers_password FROM customers WHERE customers_id = ?', cardinality::SINGLE, $convert_customer->get_header('customers_id'));

				$new_el_id = $this->add_extra_login($extra_login);

				$parameters1 = [':customers_id' => $this->id(), ':customers_extra_logins_id' => $new_el_id, ':old_customers_id' => $convert_customer->get_header('customers_id')];
				$parameters2 = [':customers_id' => $this->id(), ':old_customers_id' => $convert_customer->get_header('customers_id')];

				// migrate orders
				prepared_query::execute('UPDATE orders SET customers_id = :customers_id, customers_extra_logins_id = :customers_extra_logins_id WHERE customers_id = :old_customers_id', $parameters1);

				//update rfq table
				prepared_query::execute('UPDATE ck_rfq_responses SET customers_id = :customers_id WHERE customers_id = :old_customers_id', $parameters2);

				// update accounting stuff
				prepared_query::execute('UPDATE acc_invoices SET customer_id = :customers_id, customers_extra_logins_id = :customers_extra_logins_id WHERE customer_id = :old_customers_id', $parameters1);

				// update accounting stuff
				prepared_query::execute('UPDATE acc_payments SET customer_id = :customers_id, customers_extra_logins_id = :customers_extra_logins_id WHERE customer_id = :old_customers_id', $parameters1);

				// update address_book to master customer id
				prepared_query::execute('UPDATE address_book SET customers_id = :customers_id WHERE customers_id = :old_customers_id', $parameters2);

				// update additional logins just in case
				prepared_query::execute('UPDATE customers_extra_logins SET customers_id = :customers_id WHERE customers_id = :old_customers_id', $parameters2);

				// update acc_transaction_history
				prepared_query::execute('UPDATE acc_transaction_history SET customer_id = :customers_id WHERE customer_id = :old_customers_id', $parameters2);

				// delete old customer
				prepared_query::execute('DELETE FROM customers WHERE customers_id = ?', $convert_customer->get_header('customers_id'));

				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				break;
			case 'extra_login_separate':
				// follow the same path in the same way as when creating a new customer

				$customers_extra_logins_id = $_GET['customers_extra_logins_id'];

				// if this particular extra login has an email that conflicts with an existing customer including the one it's currently attached to, don't do it (we should add an error notice)
				if (!prepared_query::fetch('SELECT c.customers_id FROM customers_extra_logins cel JOIN customers c ON LOWER(cel.customers_emailaddress) = LOWER(c.customers_email_address) WHERE cel.customers_extra_logins_id = ?', cardinality::SINGLE, $customers_extra_logins_id)) {
					$customers_id = prepared_query::insert('INSERT INTO customers (customers_firstname, customers_lastname, customers_email_address, customers_telephone, customers_fax, customers_newsletter, customers_password) SELECT cel.customers_firstname, cel.customers_lastname, cel.customers_emailaddress, c.customers_telephone, c.customers_fax, c.customers_newsletter, cel.customers_password FROM customers_extra_logins cel JOIN customers c ON cel.customers_id = c.customers_id WHERE cel.customers_extra_logins_id = ?', $customers_extra_logins_id);

					$address_book_id = prepared_query::insert('INSERT INTO address_book (customers_id, entry_gender, entry_company, entry_firstname, entry_lastname, entry_street_address, entry_suburb, entry_postcode, entry_city, entry_state, entry_country_id, entry_zone_id, entry_telephone, entry_company_website) SELECT :customers_id, ab.entry_gender, ab.entry_company, ab.entry_firstname, ab.entry_lastname, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_state, ab.entry_country_id, ab.entry_zone_id, ab.entry_telephone, ab.entry_company_website FROM customers c JOIN address_book ab ON c.customers_default_address_id = ab.address_book_id WHERE c.customers_id = :old_customers_id', [':customers_id' => $customers_id, ':old_customers_id' => $this->id()]);

					prepared_query::execute('UPDATE customers SET customers_default_address_id = :address_book_id WHERE customers_id = :customers_id', [':address_book_id' => $address_book_id, ':customers_id' => $customers_id]);

					prepared_query::execute('INSERT INTO customers_info (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created, customers_info_source_id) SELECT :customers_id, 0, NOW(), customers_info_source_id FROM customers_info WHERE customers_info_id = :old_customers_id', [':customers_id' => $customers_id, ':old_customers_id' => $this->id()]);

					$customer = new self($customers_id);
					try {
						$hubspot = new api_hubspot;
						$hubspot->update_company($customer, $force_new=TRUE);
					}
					catch (Exception $e) {
						// fail silently
					}

					if (9999 == prepared_query::fetch('SELECT customers_info_source_id FROM customers_info WHERE customers_info_id = ?', cardinality::SINGLE, $customers_id)) {
						prepared_query::execute('INSERT INTO sources_other (customers_id, sources_other_name) VALUES (:customers_id, :source)', [':customers_id' => $customers_id, ':source' => 'SPLIT FROM ACCOUNT # '.$this->id()]);
					}
				}

				prepared_query::execute('UPDATE customers_extra_logins SET active = 0, customers_emailaddress = concat(customers_emailaddress, "_DELETED") WHERE customers_extra_logins_id = ?', $customers_extra_logins_id);

				CK\fn::redirect_and_exit('/admin/customers_detail.php?customers_id='.$this->id());
				break;
			default:
				break;
		}
	}

	public function process_contact($action) {
		$this->reset_process_errors();

		switch ($action) {
			case 'ccm_init': ?>
				<h3>Customer Contacts</h3>
				<table cellspacing="5" cellpadding="5">
					<tr>
						<th>Type</th>
						<th>Email</th>
						<th>Action</th>
					</tr>
					<?php if ($this->has_contacts()) {
						foreach ($this->get_contacts() as $contact) { ?>
					<tr>
						<td><?= $contact['contact_type']; ?></td>
						<td><?= $contact['email_address']; ?></td>
						<td><a style="color:blue; cursor:pointer;" onclick="ccm_delete('<?= $contact['contact_id']; ?>');">Delete</a></td>
					</tr>
						<?php }
					} ?>
				</table>
				<?php $types = self::fetch('contact_types', NULL); ?>
				<hr>
				<strong>Type:</strong>
				<select id="ccm_type">
					<?php foreach ($types as $type) { ?>
					<option value="<?= $type['id']; ?>"><?= $type['name']; ?></option>
					<?php } ?>
				</select>
				<strong>Email:</strong>
				<input type="text" id="ccm_email_address" size="40">
				<input type="button" onclick="ccm_add_button();" value="Add Contact">
				<?php
				break;
			case 'ccm_add':
				$this->add_contact([':customers_id' => $this->id(), ':contact_type_id' => $_POST['contact_type_id'], ':email_address' => $_POST['email_address']]);
				// ajax, no response
				break;
			case 'ccm_delete':
				$this->remove_contact($_POST['contact_id']);
				// ajax, no response
				break;
			default:
				// ajax, some form of alert
				echo 'fell to default';
				break;
		}
		exit();
	}

	public function process_category_discount($action) {
		$this->reset_process_errors();

		switch ($action) {
			case 'cdm_init':
				$discounted_categories = [];

				if ($this->has_category_discounts()) { ?>
				<table style="font-size: 12px;" cellspacing="5px">
					<tr>
						<th>Category</th>
						<th>Discount</th>
						<th>Status</th>
						<th>Start Date</th>
						<th>Expires Date</th>
						<th>Action</th>
					</tr>
					<?php foreach ($this->get_category_discounts() as $discount) {
						$discounted_categories[$discount['categories_id']] = TRUE;
						$category = new ck_listing_category($discount['categories_id']); ?>
					<tr>
						<td><?= $category->get_name_path(); ?></td>
						<td>%<?= $discount['discount_pctg']; ?></td>
						<td><input type="checkbox" <?= !empty($discount['status'])?'checked':''; ?> id="status_<?= $discount['discount_id']; ?>" onclick="cdm_toggle_status('<?= $discount['discount_id']; ?>');"></td>
						<td><?= $discount['start_date']->format('m/d/Y'); ?></td>
						<td><?= $discount['end_date']->format('m/d/Y'); ?></td>
						<td><a href="javascript: void(0);" onclick="cdm_delete('<?= $discount['discount_id']; ?>');">Delete</td>
					</tr>
					<?php } ?>
				</table>
				<hr>
				<?php } ?>
				<p>NOTE: For the discount field please enter a number between 0 and 100. For example, to give the customer a 7.5% discount, enter 7.5.</p>

				Category:
				<script>
					var category_list = {
						selected_list: [],
						selections: {},
						top_level: []
					};
				</script>
				<?php $top_level = [];
				$selections = []; ?>
				<?php $categories_with_children_or_products = prepared_query::fetch('SELECT DISTINCT c.categories_id, c.parent_id, cd.categories_name FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id LEFT JOIN categories c0 ON c.categories_id = c0.parent_id LEFT JOIN products_to_categories ptc ON c.categories_id = ptc.categories_id WHERE c0.categories_id IS NOT NULL OR ptc.products_id IS NOT NULL', cardinality::SET); ?>
				<select id="category_selector" name="category_id" size="1">
					<option value="">ALL</option>
					<?php foreach ($categories_with_children_or_products as $cat) {
						$c = ck_listing_category::get_record($cat['categories_id']);
						if (!$c->built('header')) $c->load('header', $cat);
						$category = new ck_listing_category($cat['categories_id'], $c);
						// if this category doesn't have any products or subcategories, skip it
						//if (!$category->has_children() && !$category->is_direct_product_parent()) continue;
						// if we already have a discount on this category, skip it
						if (!empty($discounted_categories[$category->id()])) continue;
						// if we already have a discount on a parent of this category, skip it
						foreach ($discounted_categories as $categories_id => $true) {
							if ($category->category_is_ancestor($categories_id)) continue 2;
						}

						$cname = $category->get_header('categories_name');
						$cname .= $category->is_direct_product_parent()?'*':'';

						if (!$category->has_ancestors()) {
							$top_level[] = ['id' => $category->id(), 'name' => $cname]; ?>
					<option value="<?= $category->id(); ?>"><?= $cname; ?></option>
						<?php }
						elseif (!isset($selections[$category->get_header('parent_id')])) $selections[$category->get_header('parent_id')] = [];
						$selections[$category->get_header('parent_id')][] = ['id' => $category->id(), 'name' => $cname];
					} ?>
				</select>
				<?php if (!empty($top_level)) { ?>
				<script>
					category_list.top_level = <?= json_encode($top_level); ?>;
					category_list.selections = <?= json_encode($selections); ?>;
					cdm_category_selector_init();
				</script>
				<?php } ?>

				Discount: <input type="text" id="cdm_discount" value="">
				Expires: <input type="date" id="cdm_expires_date" value="<?= date('Y-m-d', time() + 365*24*60*60); ?>">
				<input type="button" value="Add" onclick="cdm_add_button();">
				<?php
				break;
			case 'cdm_add':
				$today = new DateTime();
				$end_date = new DateTime($_REQUEST['expires_date']);

				$discount = [':customers_id' => $this->id(), ':categories_id' => $_REQUEST['category_id'], ':discount_pctg' => $_REQUEST['discount'], ':status' => 1, ':start_date' => $today->format('Y-m-d'), ':end_date' => $end_date->format('Y-m-d')];

				$this->add_category_discount($discount);

				break;
			case 'cdm_delete':
				$this->remove_category_discount($_REQUEST['id']);
				break;
			case 'cdm_status':
				foreach ($this->get_category_discounts() as $discount) {
					if ($discount['discount_id'] != $_REQUEST['id']) continue;

					$discount['customers_id'] = $this->id();
					$discount['status'] = !empty($_REQUEST['status'])?1:0;
					$discount['start_date'] = $discount['start_date']->format('Y-m-d');
					$discount['end_date'] = $discount['end_date']->format('Y-m-d');
					$discount = CK\fn::parameterize(array_diff_key($discount, ['discount_id' => NULL]));

					$this->add_category_discount($discount);
				}
				break;
			default:
				echo "fell to default";
				break;
		}
		exit();
	}

	public function change_account_manager($admin_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (!empty($admin_id)) {
				$account_manager = new ck_admin($admin_id);

				if (!$account_manager->is('account_manager')) throw new CKCustomerException('Cannot set account manager to admin who is not set up to be an account manager.');

				prepared_query::execute('UPDATE customers SET account_manager_id = :account_manager_id, sales_team_id = :sales_team_id WHERE customers_id = :customers_id', [':account_manager_id' => $account_manager->id(), ':sales_team_id' => $account_manager->has_sales_team()?$account_manager->get_sales_team()['team']->id():NULL, ':customers_id' => $this->id()]);
			}
			else prepared_query::execute('UPDATE customers SET account_manager_id = NULL WHERE customers_id = :customers_id', [':customers_id' => $this->id()]);

			$this->skeleton->rebuild('header');

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to change account manager: '.$e->getMessage());
		}
	}

	public function change_sales_team($team_id) {
		$savepoint_id = prepared_query::transaction_begin();
		if (empty($team_id)) return FALSE;
		try {
			$sales_team = new ck_team($team_id);

			if (!$sales_team->is('sales_team')) throw new CKCustomerException('Cannot set sales team to team that is not set up to be a sales team.');

			if ($this->has_account_manager()) throw new CKCustomerException('Cannot set sales team separately from the account manager.');

			prepared_query::execute('UPDATE customers SET sales_team_id = :sales_team_id WHERE customers_id = :customers_id', [':sales_team_id' => $team_id, ':customers_id' => $this->id()]);

			$this->skeleton->rebuild('header');

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to change sales team: '.$e->getMessage());
		}
	}

	public function grant_permission($permission) {
		try {
			prepared_query::execute('INSERT INTO ck_customer_access_customers_to_permissions (customers_id, customer_permission_id, status) SELECT :customers_id, customer_permission_id, :status FROM ck_customer_access_permissions WHERE permission_name = :permission ON DUPLICATE KEY UPDATE status=VALUES(status)', [':customers_id' => $this->id(), ':status' => 'GRANTED', ':permission' => $permission]);
			$this->skeleton->rebuild('permissions');

			return TRUE;
		}
		catch (Exception $e) {
			return FALSE;
		}
	}

	public function revoke_permission($permission) {
		try {
			prepared_query::execute('INSERT INTO ck_customer_access_customers_to_permissions (customers_id, customer_permission_id, status) SELECT :customers_id, customer_permission_id, :status FROM ck_customer_access_permissions WHERE permission_name = :permission ON DUPLICATE KEY UPDATE status=VALUES(status)', [':customers_id' => $this->id(), ':status' => 'REVOKED', ':permission' => $permission]);
			$this->skeleton->rebuild('permissions');

			return TRUE;
		}
		catch (Exception $e) {
			return FALSE;
		}
	}

	public function unset_permission($permission) {
		try {
			prepared_query::execute('DELETE ctp FROM ck_customer_access_customers_to_permissions ctp JOIN ck_customer_access_permissions cp ON ctp.customer_permission_id = cp.customer_permission_id WHERE ctp.customers_id = :customers_id AND cp.permission_name = :permission', [':customers_id' => $this->id(), ':permission' => $permission]);
			$this->skeleton->rebuild('permissions');

			return TRUE;
		}
		catch (Exception $e) {
			return FALSE;
		}
	}

	public function set_account_setup() {
		$savepoint_id = prepared_query::transaction_begin();
		try {
			prepared_query::execute('UPDATE customers SET account_setup = 0 WHERE customers_id = :customers_id', [':customers_id' => $this->id()]);
			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to set account setup: '.$e->getMessage());
			return FALSE;
		}
	}

	public function set_fraud_flag() {
		$savepoint_id = prepared_query::transaction_begin();
		try {
			// place each order on accounting hold
			foreach($this->get_orders('open') as $order) $order->hold();
			
			$this->update(['fraud' => 1]);
			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to set fraud flag on customer: '.$e->getMessage());
			return FALSE;
		}
	}

	public function remove_fraud_flag() {
		$savepoint_id = prepared_query::transaction_begin();
		try {
			// we intintionally do not remove the orders from accounting hold automatically. This should be done on a case by case basis
			$this->update(['fraud' => 0]);
			prepared_query::transaction_commit($savepoint_id);
			return TRUE;
		}
		catch (CKCustomerException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKCustomerException('Failed to remove fraud flag from customer: '.$e->getMessage());
			return FALSE;
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/

	public function send_late_invoice_notification($fnd, $invoices) {
		$savepoint_id = prepared_query::transaction_begin();
		$mailer = service_locator::get_mail_service();

		try {
			$mail = $mailer->create_mail();
			$mail->set_from('accounting@cablesandkits.com', 'CablesAndKits Accounting')
				->add_bcc('accounting@cablesandkits.com');

			if (strlen($this->get_header('company_account_contact_email')) > 0) {
				$mail->add_to($this->get_header('company_account_contact_email'));
			}

			if ($additional_contacts = $this->get_contacts()) {
				foreach ($additional_contacts as $contact) {
					if ($contact['contact_type_id'] != 1) continue;
					$mail->add_to($contact['email_address']);
				}
			}

			if (empty($mail->get_to()) || empty($fnd)) {
				$mail->add_to($this->get_header('email_address'));
				if ($this->has_account_manager()) {
					$acct_manager = $this->get_account_manager();
					$mail->add_to($acct_manager->get_header('email_address'));
				}
			}

			$GLOBALS['customer_invoice_attachment'] = TRUE;

			foreach ($invoices as $invoice) {
				$pdf = ck_invoice::generate_pdf($invoice->get_header('orders_id'), $invoice->id());
				$mail->create_attachment($pdf, 'CablesAndKits.com_Invoice_'.$invoice->id().'.pdf');
			}

			$body = '';

			if (empty($fnd)) {
				// this is the first late notification they're receiving
				$mail->set_subject('Past Due Invoice - Reply Requested');

				$body .= 'Our records indicate the following invoice(s) are now past due.'."\r\n<br>";

				$body .= array_reduce($invoices, function($text, $invoice) {
					$text .= $invoice->id()."\r\n<br>";
					return $text;
				}, '');

				$body .= '<br>You are receiving this email because you are the accounting contact listed on this account. Please let us know when we can expect to receive payment. I have attached a copy of the invoice(s) for your convenience. Please let me know if you have any questions or need any additional information.'."\r\n<br><br>";

				prepared_query::execute('UPDATE customers SET first_late_notice_date = NOW() WHERE customers_id = :customers_id', [':customers_id' => $this->id()]);
			}
			else {
				// this is *not* the first late notification they're receiving
				$mail->set_subject('Your Account Remains Past Due - Reply Requested');

				//let's look for additional logins
				foreach ($invoices as $invoice) {
					$order = ck_sales_order::get_order_by_invoice_id($invoice);
					$cel_data = $order->get_extra_login();
					if (!empty($cel_data) && !empty($cel_data['extra_logins_email_address'])) {
						$mail->add_to($cel_data['extra_logins_email_address']);
					}
				}

				$body .= 'Our records indicate the following invoice(s) remain past due.'."\r\n<br>";

				$body .= array_reduce($invoices, function($text, $invoice) {
					$text .= $invoice->id()."\r\n<br>";
					return $text;
				}, '');

				$body .= '<br>The original past due notice was sent only to the accounting contact listed on your account. Unfortunately, we have still not received payment. To keep your account in good standing, please let me know when we can expect to receive payment or if there is anything you need from us in order to get this taken care of. I have attached a copy of the invoice(s) for your convenience.'."\r\n<br><br>";
			}

			$body .= 'Thanks,'."\r\n<br>";
			$body .= 'Accounting Department'."\r\n<br>";
			$body .= 'accounting@cablesandkits.com'."\r\n<br>";
			$body .= '678-597-5240';

			$mail->set_body($body);

			$invoice_ids = new prepared_fields(array_reduce($invoices, function($invoice_ids, $invoice) {
				$invoice_ids[] = $invoice->id();
				return $invoice_ids;
			}, []));
			prepared_query::execute('UPDATE acc_invoices set late_notice_date = NOW() where invoice_id in ('.$invoice_ids->select_values().')', $invoice_ids->update_parameters());

			$result = $mailer->send($mail);
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKCustomerException('Failed to send late invoice notification.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}
}

class CKCustomerException extends CKMasterArchetypeException {
}
?>
