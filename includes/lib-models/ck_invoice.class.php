<?php
class ck_invoice extends ck_archetype {

	const LATE_NOTICE_GRACE_PERIOD = 5;
	const LATE_NOTICE_GRACE_INTERVAL = 7;

	protected static $skeleton_type = 'ck_invoice_type';

	protected static $queries = [
		'invoice_header' => [
			'qry' => 'SELECT DISTINCT i.invoice_id, i.customer_id as customers_id, i.inv_order_id as orders_id, i.rma_id, i.inv_date as invoice_date, i.invoice_rma, i.paid_in_full, i.customers_extra_logins_id, cel.customers_emailaddress as extra_logins_email_address, cel.customers_firstname as extra_logins_firstname, cel.customers_lastname as extra_logins_lastname, cel.copy_account as extra_logins_copy_account, i.credit_payment_id, i.credit_memo, i.original_invoice as original_invoice_id, i.late_notice_date, CASE WHEN i0.invoice_id IS NULL THEN 1 ELSE 0 END as latest_version, pm.code as payment_method_code, pm.label as payment_method_label, o.purchase_order_number, o.net10_po, o.net15_po, o.net30_po, o.net45_po FROM acc_invoices i LEFT JOIN orders o LEFT JOIN payment_method pm ON o.payment_method_id = pm.id ON i.inv_order_id = o.orders_id LEFT JOIN customers_extra_logins cel ON i.customers_extra_logins_id = cel.customers_extra_logins_id LEFT JOIN acc_invoices i0 ON i.inv_order_id = i0.inv_order_id AND i.invoice_id < i0.invoice_id WHERE i.invoice_id = :invoice_id',
			// specifically ignore:
			// po_number
			'cardinality' => cardinality::ROW,
		],

		'incentive' => [
			'qry' => 'SELECT sales_incentive_tier_id, incentive_percentage, incentive_base_total, incentive_product_total, incentive_final_total, incentive_accrued, incentive_paid, incentive_override_percentage, incentive_override_date, incentive_override_note FROM acc_invoices WHERE invoice_id = :invoice_id',
			'cardinality' => cardinality::ROW,
		],

		'rma' => [
			'qry' => 'SELECT * FROM rma WHERE id = :rma_id',
			'cardinality' => cardinality::ROW,
		],

		'totals' => [
			// make the field names match the order totals table
			'qry' => 'SELECT invoice_total_id, invoice_total_line_type as class, invoice_total_description as title, invoice_total_price as value, invoice_total_date FROM acc_invoice_totals WHERE invoice_id = :invoice_id',
			'cardinality' => cardinality::SET,
		],

		'products' => [
			'qry' => 'SELECT ii.invoice_item_id, op.orders_products_id, ii.rma_product_id, ii.ipn_id as stock_id, op.products_id, op.parent_products_id, op.option_type, op.products_model as model, op.products_name as name, ii.invoice_item_price as invoice_unit_price, ii.revenue, ii.invoice_item_qty as quantity, ii.orders_product_cost as invoice_unit_cost, ii.orders_product_cost_total as invoice_line_cost, ii.serialized FROM acc_invoice_items ii LEFT JOIN orders_products op ON ii.orders_product_id = op.orders_products_id WHERE ii.invoice_id = :invoice_id ORDER BY CASE WHEN op.option_type = 0 THEN op.products_id ELSE op.parent_products_id END ASC, op.orders_products_id ASC',
			'cardinality' => cardinality::SET,
		],

		'allocated_serials' => [
			'qry' => 'SELECT DISTINCT serial_id FROM serials_history WHERE order_product_id = :orders_products_id',
			'cardinality' => cardinality::COLUMN,
		],

		'rma_serial' => [
			'qry' => 'SELECT DISTINCT serial_id FROM rma_product WHERE id = :rma_product_id',
			'cardinality' => cardinality::SINGLE,
		],

		'payments' => [
			'qry' => 'SELECT pti.payment_to_invoice_id, pti.credit_amount, pti.credit_date, ap.payment_id, ap.payment_amount, ap.payment_ref, ap.payment_date, ap.payment_method_id, pm.code as payment_method_code, pm.label as payment_method_label FROM acc_payments_to_invoices pti JOIN acc_payments ap ON pti.payment_id = ap.payment_id LEFT JOIN payment_method pm ON ap.payment_method_id = pm.id WHERE pti.invoice_id = :invoice_id',
			'cardinality' => cardinality::SET,
		],

		'notes' => [
			'qry' => "SELECT note_text FROM acc_notes WHERE note_type = 'acc_invoices' AND note_type_id = :invoice_id",
			'cardinality' => cardinality::COLUMN,
		]
	];

	const OUTSTANDING = 'OUTSTANDING';
	const PAID = 'PAID';
	const UNPAID = 'UNPAID';
	const RMA = 'RMA';
	const CREDIT_MEMO = 'CREDIT_MEMO';

	protected static $accounting_date;

	public function __construct($invoice_id, ck_invoice_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($invoice_id);

		if (!$this->skeleton->built('invoice_id')) $this->skeleton->load('invoice_id', $invoice_id);
		if ($this->skeleton->built('header')) $this->normalize_header();

		self::register($invoice_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('invoice_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('invoice_header', [$this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$date_fields = ['invoice_date', 'late_notice_date'];
		foreach ($date_fields as $field) {
			$header[$field] = ck_datetime::datify($header[$field]);
		}

		$bool_fields = ['invoice_rma', 'paid_in_full', 'credit_memo'];
		foreach ($bool_fields as $field) {
			$header[$field] = CK\fn::check_flag($header[$field]);
		}

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('invoice_header', [':invoice_id' => $this->id()]));
		$this->normalize_header();
	}

	private function build_incentive() {
		$incentive = self::fetch('incentive', [':invoice_id' => $this->id()]);
		$incentive['incentive_accrued'] = ck_datetime::datify($incentive['incentive_accrued']);
		$incentive['incentive_paid'] = ck_datetime::datify($incentive['incentive_paid']);

		if (empty($incentive['incentive_override_percentage'])) {
			$incentive['overridden'] = FALSE;
			$incentive['final_incentive_percentage'] = $incentive['incentive_percentage'];
		}
		else {
			$incentive['overridden'] = TRUE;
			$incentive['incentive_override_date'] = ck_datetime::datify($incentive['incentive_override_date']);
			$incentive['final_incentive_percentage'] = $incentive['incentive_override_percentage'];
		}

		$this->skeleton->load('incentive', $incentive);
	}

	private function build_customer() {
		$this->skeleton->load('customer', new ck_customer2($this->get_header('customers_id')));
	}

	private function build_extra_login() {
		// because load() ignores keys that don't fit the format of the target type, we can
		// load the whole header in since that's where the data is coming from
		$this->skeleton->load('extra_login', $this->skeleton->get('header'));
	}

	private function build_order() {
		if ($orders_id = $this->get_header('orders_id')) $this->skeleton->load('order', new ck_sales_order($orders_id));
		else $this->skeleton->load('order', NULL);
	}

	private function build_rma() {
		if ($rma_id = $this->get_header('rma_id')) $this->skeleton->load('rma', new ck_rma2($rma_id));
		else $this->skeleton->load('rma', NULL);
	}

	private function build_totals() {
		$totals = $this->skeleton->key_format('totals'); // the same template works for both totals and totals detail
		$simple_totals = $totals;
		$simple_totals = array_map(function($val) { return 0; }, $simple_totals);

		$invoice_totals = self::fetch('totals', [':invoice_id' => $this->id()]);

		foreach ($invoice_totals as $total) {
			$ttl = ['invoice_total_id' => $total['invoice_total_id'], 'title' => $total['title'], 'value' => $total['value'], 'invoice_total_date' => self::DateTime($total['invoice_total_date'])];
			switch ($total['class']) {
				case 'ot_subtotal':
					// these lines are obsolete
					continue 2;
					break;
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
					$totals['shipping'][] = $ttl;
					$totals['consolidated'][] = $ttl;
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

	private function build_products() {
		$invoice_products = self::fetch('products', [':invoice_id' => $this->id()]);

		$consolidated_products = [];

		foreach ($invoice_products as &$product) {
			$product['ipn'] = new ck_ipn2($product['stock_id']);
			$product['listing'] = new ck_product_listing($product['products_id']);
			$product['serialized'] = CK\fn::check_flag($product['serialized']);

			if ($product['serialized']) {
				$product['serials'] = [];
				if (!empty($product['rma_product_id'])) {
					$serial_id = self::fetch('rma_serial', [':rma_product_id' => $product['rma_product_id']]);
					$product['serials'][] = new ck_serial($serial_id);
				}
				else {
					$serial_ids = self::fetch('allocated_serials', [':orders_products_id' => $product['orders_products_id']]);
					foreach ($serial_ids as $serial_id) {
						$product['serials'][] = new ck_serial($serial_id);
					}
				}
			}

			if (empty($consolidated_products[$product['orders_products_id']])) {
				$consolidated_products[$product['orders_products_id']] = $product;
				$consolidated_products[$product['orders_products_id']]['invoice_item_ids'] = [];
				$consolidated_products[$product['orders_products_id']]['rma_product_ids'] = [];
				$consolidated_products[$product['orders_products_id']]['quantity'] = 0;
			}

			$consolidated_products[$product['orders_products_id']]['quantity'] += $product['quantity'];
			$consolidated_products[$product['orders_products_id']]['invoice_item_ids'][] = $product['invoice_item_id'];
			if (!empty($product['rma_product_id'])) $consolidated_products[$product['orders_products_id']]['rma_product_ids'][] = $product['rma_product_id'];
		}

		$this->skeleton->load('products', $invoice_products);
		$this->skeleton->load('consolidated_products', $consolidated_products);
	}

	private function build_payments() {
		$this->skeleton->load('payments', self::fetch('payments', [':invoice_id' => $this->id()]));
	}

	private function build_notes() {
		$this->skeleton->load('notes', self::fetch('notes', [':invoice_id' => $this->id()]));
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function get_incentive($key=NULL) {
		if (!$this->skeleton->built('incentive')) $this->build_incentive();
		if (empty($key)) return $this->skeleton->get('incentive');
		else return $this->skeleton->get('incentive', $key);
	}

	public function get_name() {
		return $this->get_customer()->get_name($this->get_header('customers_extra_logins_id'));
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

	public function has_order() {
		if (!$this->skeleton->get('order')) $this->build_order();
		return $this->skeleton->has('order');
	}

	public function get_order() {
		if (!$this->has_order()) return NULL;
		return $this->skeleton->get('order');
	}

	public function has_rma() {
		if (!$this->skeleton->get('rma')) $this->build_rma();
		return $this->skeleton->has('rma');
	}

	public function get_rma() {
		if (!$this->has_rma()) return NULL;
		return $this->skeleton->get('rma');
	}

	public function get_original_order() {
		if ($this->has_order()) return $this->get_order();
		elseif ($this->has_rma()) return $this->get_rma()->get_sales_order();
		else return NULL;
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

	public function get_product_subtotal() {
		return array_reduce($this->get_products(), function($st, $p) {
			return $st + ($p['invoice_unit_price'] * abs($p['quantity']));
		}, 0);
	}

	public function get_final_product_subtotal() {
		$totals = $this->get_simple_totals();
		return $totals['total'] - @$totals['tax'] - @$totals['shipping'];
	}

	public function get_product_cost() {
		return array_reduce($this->get_products(), function($c, $p) {
			return $c + $p['invoice_line_cost'];
		}, 0);
	}

	public function has_products() {
		if (!$this->skeleton->built('products')) $this->build_products();
		return $this->skeleton->has('products');
	}

	public function get_products() {
		if (!$this->has_products()) return [];
		return $this->skeleton->get('products');
	}

	public function get_consolidated_products() {
		if (!$this->has_products()) return [];
		return $this->skeleton->get('consolidated_products');
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
			$total += $payment['credit_amount'];
			return $total;
		}, 0);
	}

	public function has_notes() {
		if (!$this->skeleton->built('notes')) $this->build_notes();
		return $this->skeleton->has('notes');
	}

	public function get_notes() {
		if (!$this->has_notes()) return [];
		return $this->skeleton->get('notes');
	}

	public function has_terms_po_number() {
		return in_array($this->get_header('payment_method_code'), ['net10', 'net15', 'net30', 'net45']) && !empty($this->get_header($this->get_header('payment_method_code').'_po'));
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

	public function get_paid() {
		$paid = 0;

		foreach ($this->get_payments() as $payment) {
			$paid += $payment['credit_amount'];
		}

		return $paid;
	}

	public function get_balance() {
		$total = $this->get_simple_totals('total');

		$paid = $this->get_paid();

		return round($total - $paid, 6);
	}

	public function get_age() {
		$invoice_date = clone $this->get_header('invoice_date');
		$invoice_date->setTime(0, 0, 0);

		if (!$this->is('paid_in_full')) {
			$today = clone self::NOW();
			$today->setTime(0, 0, 0);

			return $today->diff($invoice_date, TRUE)->format('%a');
		}
		else {
			$end_date = clone $invoice_date;

			foreach ($this->get_payments() as $payment) {
				$end_date = max($end_date, self::DateTime($payment['credit_date']));
			}

			$end_date->setTime(0, 0, 0);

			return $end_date->diff($invoice_date, TRUE)->format('%a');
		}
	}

	public function get_days_past_due() {
		if ($this->is('paid_in_full')) return 0;

		$due_date = clone $this->get_header('invoice_date');
		$due_date->setTime(0, 0, 0);
		$due_date->add(new DateInterval('P'.$this->get_terms().'D'));

		$today = clone self::NOW();
		$today->setTime(0, 0, 0);

		if ($today <= $due_date) return 0;

		return $today->diff($due_date, TRUE)->format('%a');
	}

	public static function has_accounting_date() {
		return !empty(self::$accounting_date);
	}

	public static function get_accounting_date($force_business_day=FALSE) {
		if (!self::has_accounting_date()) self::set_accounting_date();

		$accounting_date = clone self::$accounting_date;

		if ($force_business_day) $accounting_date->set_to_next_business_day(FALSE, TRUE);

		return $accounting_date;
	}

	public static function get_invoice_by_payment_allocation_id($payment_to_invoice_id) {
		if ($invoice_id = self::query_fetch('SELECT invoice_id FROM acc_payments_to_invoices WHERE payment_to_invoice_id = :payment_to_invoice_id', cardinality::SINGLE, [':payment_to_invoice_id' => $payment_to_invoice_id])) {
			return new self($invoice_id);
		}
		else return NULL;
	}

	public static $list_context = 'general';

	public static function get_invoice_list_by_customer($customers_id, $status=NULL, Callable $sort=NULL, $page=NULL, $page_size=NULL) {
		$where = ['customer_id = :customers_id'];

		$params = [];

		if ($status == self::OUTSTANDING) $params['paid_in_full'] = FALSE;
		elseif ($status == self::PAID) {
			$params['paid_in_full'] = TRUE;
			$params['invoice_rma'] = FALSE;
			$params['orders_id'] = TRUE;
		}
		elseif ($status == self::UNPAID) {
			$params['paid_in_full'] = FALSE;
			$params['invoice_rma'] = FALSE;
			$params['orders_id'] = TRUE;
		}
		elseif ($status == self::RMA) $params['invoice_rma'] = TRUE;
		elseif ($status == self::CREDIT_MEMO) {
			$params['invoice_rma'] = FALSE;
			$params['orders_id'] = TRUE;
			$params['credit_memo'] = TRUE;
		}


		if (isset($params['paid_in_full'])) $where[] = 'paid_in_full = '.(CK\fn::check_flag($params['paid_in_full'])?1:0);
		if (isset($params['invoice_rma'])) $where[] = '(invoice_rma '.(CK\fn::check_flag($params['invoice_rma'])?'= 1)':'= 0 OR invoice_rma IS NULL)');
		if (isset($params['orders_id'])) $where[] = 'inv_order_id IS '.(CK\fn::check_flag($params['orders_id'])?'NOT NULL':'NULL');
		if (isset($params['credit_memo'])) $where[] = '(credit_memo '.(CK\fn::check_flag($params['credit_memo'])?'= 1)':'= 0 OR invoice_rma IS NULL)');

		$parameters = [':customers_id' => $customers_id];

		if (self::$list_context == 'marketplace') {
			$where[] = 'inv_date >= :invoice_date';
			$date = new DateTime();
			$date->sub(new DateInterval('P1Y'));
			$parameters[':invoice_date'] = $date->format('Y-m-d');
		}

		$where = implode(' AND ', $where);

		/*$limit = '';
		if (is_int($page) && is_int($page_size) && !empty($page) && !empty($page_size)) {
			$page--;
			$limit = 'LIMIT '.($page*$page_size).', '.$page_size;
		}*/

		$invoice_ids = self::query_fetch('SELECT invoice_id FROM acc_invoices WHERE '.$where.' ORDER BY inv_date DESC', cardinality::COLUMN, $parameters);

		$invoices = [];

		$result_count = count($invoice_ids);

		if (!empty($invoice_ids)) {
			if (!empty($page) && !empty($page_size)) {
				$page--; // indexes are zero based
				$invoice_ids = array_slice($invoice_ids, $page*$page_size, $page_size);
			}

			foreach ($invoice_ids as $invoice_id) {
				$invoices[] = new self($invoice_id);
			}

			if (!empty($sort)) usort($invoices, $sort);
		}

		return ['invoices' => $invoices, 'result_count' => $result_count];
	}

	public static function get_latest_invoice_by_orders_id($orders_id) {
		if ($invoice_id = prepared_query::fetch('SELECT invoice_id FROM ckv_latest_sales_invoice WHERE inv_order_id = :orders_id', cardinality::SINGLE, [':orders_id' => $orders_id])) {
			return new self($invoice_id);
		}
		else return NULL;
	}

	public static function get_total_ar_outstanding() {
		return prepared_query::fetch("SELECT SUM(balance) FROM (SELECT i.invoice_id, it.invoice_total_price - IFNULL(SUM(pti.credit_amount), 0) as balance FROM acc_invoices i JOIN acc_invoice_totals it ON i.invoice_id = it.invoice_id AND it.invoice_total_line_type IN ('ot_total', 'rma_total') LEFT JOIN acc_payments_to_invoices pti ON i.invoice_id = pti.invoice_id WHERE i.paid_in_full = 0 GROUP BY i.invoice_id) oi", cardinality::SINGLE);
	}

	/*-------------------------------
	// modify data
	-------------------------------*/

	public static function set_accounting_date(ck_datetime $accounting_date=NULL) {
		if (empty($accounting_date)) $accounting_date = ck_datetime::NOW();

		return self::$accounting_date = $accounting_date;
	}

	public static function create(Array $data) {
		$savepoint = self::transaction_begin();

		// get the admin copy of the avatax functions - used optionally for several points
		require_once(__DIR__.'/../../admin/includes/functions/avatax.php');

		try {
			$params = new ezparams($data['header']);
			self::query_execute('INSERT INTO acc_invoices ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));
			$invoice_id = self::fetch_insert_id();

			$invoice = new self($invoice_id);

			if (!empty($data['products'])) {
				$product_response = $invoice->create_products($data['products']);
			}

			$invoice_total = 0;
			if (!empty($data['totals'])) $invoice_total = $invoice->create_totals($data['totals']);

			if (!empty($data['note'])) $invoice->create_note($data['note']);

			// enter the transaction history record
			if (empty($data['transaction_history']['transaction_type'])) $data['transaction_history']['transaction_type'] = 'New Invoice';

			$invoice->create_transaction_history($data['transaction_history']);

			if (!empty($data['credits'])) {
				foreach ($data['credits'] as $credit) {
					$invoice->create_credit($credit);
				}
			}

			$payment_total = 0;
			if (!empty($data['payment_allocations'])) $payment_total = $invoice->create_payment_allocations($data['payment_allocations'], $invoice_total);

			if ($payment_total >= $invoice_total) $invoice->update(['paid_in_full' => 1]);

			if (!empty($data['post_tax']) && !empty($data['header']['inv_order_id'])) avatax_post_tax($invoice->id(), $data['header']['inv_order_id']);

			$response = [];

			if (!empty($data['rma'])) {
				$response['invoice'] = $invoice;
				$response['inventory_holds'] = !empty($product_response)?$product_response['inventory_holds']:[];
			}
			else $response = $invoice;

			self::transaction_commit($savepoint);
			return $response;
		}
		catch (CKInvoiceException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create invoice: '.$e->getMessage());
		}
	}

	public static function create_from_sales_order(ck_sales_order $sales_order, $skip_inventory_management=FALSE) {
		$savepoint = self::transaction_begin();

		$data = [
			'header' => [],
			'products' => [],
			'totals' => [],
			'payment_allocations' => [],
			'transaction_history' => [],
			'overall_total' => 0,
			'total_paid' => 0,
			'total_remaining' => 0,
			'post_tax' => TRUE,
		];

		$sales_order->force_rebuild();

		try {
			// build the invoice
			$data['header'] = [
				'customer_id' => $sales_order->get_header('customers_id'),
				'customers_extra_logins_id' => $sales_order->get_header('customers_extra_logins_id'),
				'inv_order_id' => $sales_order->id(),
				'paid_in_full' => 0,
				'credit_memo' => 0,
				'inv_date' => self::get_accounting_date(TRUE)->format('Y-m-d H:i:s'),
				'sales_incentive_tier_id' => NULL,
				'incentive_percentage' => 0,
				'incentive_base_total' => NULL,
				'incentive_product_total' => 0,
				'incentive_final_total' => 0,
			];

			$customer = $sales_order->get_customer();

			// build the products
			foreach ($sales_order->get_products() as $product) {
				// if the order has serialized items and they aren't fully allocated, don't invoice
				if ($product['ipn']->is('serialized') && count($product['allocated_serials']) != $product['quantity']) {
					throw new CKInvoiceException('Invoice cannot be created without correct # of serial allocations. [IPN: '.$product['ipn']->get_header('ipn').'] [Required: '.$product['quantity'].'] [Allocated: '.count($product['allocated_serials']).']');
				}

				$invoice_item = [
					'ipn_id' => $product['ipn']->id(),
					'orders_product_id' => $product['orders_products_id'],
					'invoice_item_price' => $product['final_price'],
					'revenue' => $product['revenue'],
					'invoice_item_qty' => $product['quantity'],
				];

				if ($product['ipn']->is('serialized')) {
					$total_cost = 0;
					foreach ($product['allocated_serials'] as $serial) {
						$total_cost += $serial->get_current_history()['cost'];
					}
					$invoice_item['orders_product_cost'] = 0; //$total_cost / $product['quantity'];
					$invoice_item['orders_product_cost_total'] = $total_cost;
					$invoice_item['serialized'] = 1;
				}
				else {
					$invoice_item['orders_product_cost'] = $product['ipn']->get_avg_cost();
					$invoice_item['orders_product_cost_total'] = round($invoice_item['orders_product_cost'] * $product['quantity'], 2);
					$invoice_item['serialized'] = 0;
				}

				$data['header']['incentive_product_total'] -= $invoice_item['orders_product_cost_total'];

				$invoice_item['additional']['serials'] = $product['allocated_serials'];

				if ($skip_inventory_management) $invoice_item['additional']['skip_inventory_management'] = TRUE;

				$data['products'][] = $invoice_item;
			}

			// build the totals
			$data['overall_total'] = 0;
			foreach ($sales_order->get_totals('consolidated') as $total) {
				$data['totals'][] = [
					'invoice_total_line_type' => 'ot_'.$total['class'],
					'invoice_total_description' => $total['title'],
					'invoice_total_price' => $total['value'],
				];

				if (in_array($total['class'], ['total'])) $data['header']['incentive_product_total'] += $total['value'];
				elseif (in_array($total['class'], ['tax', 'shipping'])) $data['header']['incentive_product_total'] -= $total['value'];

				if ($total['class'] == 'total') $data['overall_total'] = $total['value'];
			}

			$first_invoice_date = $sales_order->get_first_invoice_date();
			$base_total = !empty($first_invoice_date)?$customer->get_incentive_base_total($first_invoice_date):$customer->get_incentive_base_total();

			$data['header']['incentive_base_total'] = $base_total + $data['header']['incentive_product_total'];

			if ($customer->has_account_manager()) {
				$tiers = ck_sales_incentive_tier_lookup::instance()->get_list('active-basic', NULL, TRUE); // tiers are sorted by ascending incentive base

				foreach ($tiers as $tier) {
					if ($data['header']['incentive_base_total'] < $tier['incentive_base']) break;
					$data['header']['sales_incentive_tier_id'] = $tier['sales_incentive_tier_id'];
					$data['header']['incentive_percentage'] = $tier['incentive_percentage'];
				}
			}
			elseif ($sales_order->has_account_manager() && $data['header']['incentive_product_total'] >= 250) $data['header']['incentive_percentage'] = '0.15';

			$final_incentive = bcmul($data['header']['incentive_percentage'], $data['header']['incentive_product_total'], 4);
			if ($final_incentive > 0) $data['header']['incentive_final_total'] = $final_incentive;

			// build the payments
			$data['total_remaining'] = $data['overall_total'];
			$data['total_paid'] = 0;
			if ($sales_order->has_payments()) {
				foreach ($sales_order->get_payments() as $payment) {
					if ($data['total_remaining'] <= 0) break;

					$payment_application = [
						'payment_id' => $payment['payment_id'],
						'credit_amount' => min($data['total_remaining'], $payment['pmt_applied_amount']),
						'order_id' => $sales_order->id(),
						'customer_id' => $sales_order->get_header('customers_id')
					];

					$data['total_paid'] += $payment_application['credit_amount'];
					$data['total_remaining'] -= $payment_application['credit_amount'];

					$data['payment_allocations'][] = $payment_application;
				}
			}

			$data['transaction_history']['order_id'] = $sales_order->id();
			$data['transaction_history']['customer_id'] = $sales_order->get_header('customers_id');

			$invoice = self::create($data);

			$sales_order->rebuild_invoices();

			self::transaction_commit($savepoint);
			return $invoice;
		}
		catch (CKInvoiceException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create invoice from sales order: '.$e->getMessage());
		}
	}

	public static function create_credit_from_rma_receipt(ck_rma2 $rma, Array $received_products=[], $refund_shipping_amount=0) {
		$savepoint = self::transaction_begin();

		require_once(__DIR__.'/../../admin/includes/functions/avatax.php');

		$data = [
			'header' => [],
			'products' => [],
			'totals' => [],
			'transaction_history' => [],
			'subtotal' => 0,
			'tax' => 0,
			'refunded_shipping' => $refund_shipping_amount,
			'refunded_coupon' => 0,
			'restock_fee' => 0,
			'rma' => TRUE
		];

		try {
			if (empty($received_products) && $refund_shipping_amount == 0) throw new CKInvoiceException('No RMA products or shipping amount to invoice.');

			// if this order already has an invoice, don't create another one
			if ($rma->has_invoices() && empty($received_products) && $rma->is('refund_shipping')) throw new CKInvoiceException('This RMA will refund the total shipping amount, do not refund shipping separately.');

			$sales_order = $rma->get_sales_order();
			$customer = $rma->get_customer();

			$invrow = $sales_order->get_latest_invoice();
			$inv = new ck_invoice($invrow['invoice_id']);

			// build the invoice
			$data['header'] = [
				'customer_id' => $customer->id(),
				'customers_extra_logins_id' => $sales_order->get_header('customers_extra_logins_id'),
				'rma_id' => $rma->id(),
				'invoice_rma' => 1,
				'paid_in_full' => 1,
				'credit_memo' => 0,
				'inv_date' => self::get_accounting_date(TRUE)->format('Y-m-d H:i:s'),
				'sales_incentive_tier_id' => NULL,
				'incentive_percentage' => $inv->get_incentive('incentive_percentage'),
				'incentive_base_total' => NULL,
				'incentive_product_total' => 0,
				'incentive_final_total' => 0,
				'incentive_override_percentage' => $inv->get_incentive('incentive_override_percentage'),
				'incentive_override_date' => $inv->get_incentive('incentive_override_date'),
				'incentive_override_note' => $inv->get_incentive('incentive_override_note'),
			];

			$total_cost = 0;

			if (!empty($received_products)) {
				$invoice_products = $inv->get_consolidated_products();

				foreach ($received_products as $product) {
					if (!($invoice_product = $invoice_products[$product['orders_products_id']])) {
						throw new CKInvoiceException('We cannot create an RMA invoice for an item that has not been shipped/invoiced: '.$product['ipn']->get_header('ipn'));
					}

					$product['rma_id'] = $rma->id();
					$product['cost'] = $product['ipn']->is('serialized')?$product['serial']->get_current_history()['cost']:$invoice_product['invoice_unit_cost'];

					// cost and price are negative to reflect proper figures in the invoice stats report
					$invoice_item = [
						'ipn_id' => $product['ipn']->id(),
						'orders_product_id' => $product['orders_products_id'],
						'invoice_item_price' => $invoice_product['revenue'] * -1, // if it's not a bundle, this will equal the invoice item price, if it *is* a bundle, the bundle parent should be 0
						'revenue' => $invoice_product['revenue'] * -1,
						'invoice_item_qty' => $product['quantity'] * -1, // this will be "1" for serialized items
						'orders_product_cost' => $product['cost'] * -1,
						'rma_product_id' => $product['rma_product_id'],
						'additional' => [
							'rma_product' => $product
						]
					];

					if ($product['ipn']->is('serialized')) {
						$invoice_item['orders_product_cost_total'] = $product['serial']->get_invoiced_cost($product['orders_products_id']) * -1;
						$invoice_item['serialized'] = 1;
					}
					else {
						$invoice_item['orders_product_cost_total'] = round($invoice_item['orders_product_cost'] * $product['quantity'], 2);
						$invoice_item['serialized'] = 0;
					}

					$data['header']['incentive_product_total'] += abs($invoice_item['orders_product_cost_total']);

					$data['products'][] = $invoice_item;

					$data['subtotal'] += $invoice_item['invoice_item_price'] * $invoice_item['invoice_item_qty'];
				}
			}

			if ($rma->is('refund_shipping') && $rma->get_header('shipping_amount') > 0) {
				$data['refunded_shipping'] = $rma->get_header('shipping_amount');

				$data['totals'][] = [
					'invoice_total_line_type' => 'ot_shipping',
					'invoice_total_description' => 'rma # '.$rma->id(),
					'invoice_total_price' => $rma->get_header('shipping_amount') * -1,
				];
			}

			if ($rma->is('refund_coupon') && $rma->get_header('coupon_amount') > 0) {
				$data['refunded_coupon'] = $rma->get_header('coupon_amount');

				$data['totals'][] = [
					'invoice_total_line_type' => 'ot_coupon',
					'invoice_total_description' => 'rma # '.$rma->id(),
					'invoice_total_price' => $rma->get_header('coupon_amount'),
				];
			}

			if ($rma->has_restock_percentage()) {
				$data['restock_fee'] = $data['subtotal'] * $rma->get_restock_percentage();

				$data['totals'][] = [
					'invoice_total_line_type' => 'ot_custom',
					'invoice_total_description' => 'restock fee from rma # '.$rma->id(),
					'invoice_total_price' => $data['restock_fee'],
				];
			}

			$data['transaction_history']['transaction_type'] = 'RMA Invoice';
			$data['transaction_history']['order_id'] = $sales_order->id();
			$data['transaction_history']['customer_id'] = $customer->id();

			$data['header']['incentive_product_total'] -= (abs($data['subtotal']) - abs($data['refunded_coupon']) - abs($data['restock_fee']));

			$final_incentive = $inv->get_incentive('incentive_final_total')>0?bcmul($inv->get_incentive('final_incentive_percentage'), $data['header']['incentive_product_total'], 4):0;
			$data['header']['incentive_final_total'] = $final_incentive;

			$response = self::create($data);
			$invoice = $response['invoice'];

			$totals = [];

			if ($data['refunded_shipping'] != 0) {
				$totals[] = [
					'invoice_total_line_type' => 'ot_shipping',
					'invoice_total_description' => 'shipping credit from rma # '.$rma->id(),
					'invoice_total_price' => abs($data['refunded_shipping']) * -1
				];
			}

			// avatax returns tax as a negative number
			if (!empty($received_products)) {
				$tax = avatax_get_rma_tax($invoice->id(), $data['restock_fee']);
				if ($tax != 0) {
					$data['tax'] = $tax;

					$totals[] = [
						'invoice_total_line_type' => 'ot_tax',
						'invoice_total_description' => 'tax credit from rma # '.$rma->id(),
						'invoice_total_price' => $tax,
					];
				}
			}

			//total = +subtotal +tax +refunded_shipping -refunded_coupon -restock
			$data['total'] = abs($data['subtotal']) + abs($data['tax']) + abs($data['refunded_shipping']) - abs($data['refunded_coupon']) - abs($data['restock_fee']);

			$totals[] = [
				'invoice_total_line_type' => 'ot_total',
				'invoice_total_description' => 'rma # '.$rma->id(),
				'invoice_total_price' => $data['total'] * -1
			];

			$invoice->create_totals($totals);

			if (!empty($received_products) && $data['tax'] != 0) avatax_post_rma_tax($invoice->id(), $tax);

			$invoice->create_credit([
				'payment_amount' => abs($data['total']),
				'payment_method_id' => 8, // Account Credit
				'payment_ref' => 'Account Credit for RMA # '.$rma->id(),
				'unapplied' => TRUE
			]);

			self::transaction_commit($savepoint);
			return $response;
		}
		catch (CKInvoiceException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create invoice from rma: '.$e->getMessage());
		}
	}

	public static function create_credit_from_invoice(ck_invoice $invoice) {
		$savepoint = self::transaction_begin();

		$data = [
			'header' => [],
			'products' => [],
			'totals' => [],
			'transaction_history' => [],
			'overall_total' => 0,
			'total_paid' => 0,
			'total_remaining' => 0,
			'post_tax' => TRUE,
			'credit_tax' => TRUE
		];

		try {
			$data['header'] = [
				'customer_id' => $invoice->get_header('customers_id'),
				'customers_extra_logins_id' => $invoice->get_header('customers_extra_logins_id'),
				'inv_order_id' => $invoice->get_header('orders_id'),
				'paid_in_full' => 1,
				'credit_memo' => 1,
				'credit_payment_id' => $invoice->get_header('credit_payment_id'),
				'original_invoice' => $invoice->id(),
				'inv_date' => self::get_accounting_date(TRUE)->format('Y-m-d H:i:s'),
				'sales_incentive_tier_id' => NULL,
				'incentive_percentage' => $invoice->get_incentive('incentive_percentage'),
				'incentive_base_total' => NULL,
				'incentive_product_total' => -1 * $invoice->get_incentive('incentive_product_total'),
				'incentive_final_total' => -1 * $invoice->get_incentive('incentive_final_total'),
				'incentive_override_percentage' => $invoice->get_incentive('incentive_override_percentage'),
				'incentive_override_date' => $invoice->get_incentive('incentive_override_date'),
				'incentive_override_note' => $invoice->get_incentive('incentive_override_note'),
			];

			foreach ($invoice->get_products() as $product) {
				$prod = [];
				$prod['ipn_id'] = $product['stock_id'];
				$prod['orders_product_id'] = $product['orders_products_id'];
				$prod['invoice_item_price'] = $product['invoice_unit_price'] * -1;
				$prod['revenue'] = $product['revenue'] * -1;
				$prod['invoice_item_qty'] = $product['quantity'] * -1;
				$prod['orders_product_cost'] = $product['invoice_unit_cost'] * -1;
				$prod['orders_product_cost_total'] = $product['invoice_line_cost'] * -1;
				$prod['serialized'] = $product['serialized'];
				$prod['rma_product_id'] = $product['rma_product_id'];
				$prod['additional'] = ['skip_inventory_management' => TRUE];
				$data['products'][] = $prod;
			}

			foreach ($invoice->get_totals('consolidated') as $total) {
				$data['totals'][] = [
					'invoice_total_line_type' => 'ot_'.$total['class'],
					'invoice_total_description' => $total['title'],
					'invoice_total_price' => $total['value'] * -1,
				];
			}

			$data['transaction_history']['transaction_type'] = 'Credit Memo';
			$data['transaction_history']['order_id'] = $invoice->get_header('orders_id');
			$data['transaction_history']['customer_id'] = $invoice->get_header('customers_id');

			$new_invoice = self::create($data);

			// get the amount we're crediting
			$credit = $invoice->get_simple_totals('total');

			$invoice->create_credit([
				'payment_amount' => $credit,
				'payment_method_id' => 12, // Credit Memo
				'payment_ref' => 'Credit Memo Payment for '.$new_invoice->id()
			]);

			$invoice->update(['paid_in_full' => 1]);

			self::transaction_commit($savepoint);

			return $new_invoice;
		}
		catch (CKInvoiceException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create credit invoice for order: '.$e->getMessage());
		}
	}

	public function remove() {
		$savepoint = self::transaction_begin();

		try {
			// make the call to the tax service - this has to be done before we actually delete the records
			// get the admin copy of the avatax functions
			require_once(__DIR__.'/../../admin/includes/functions/avatax.php');
			avatax_cancel_tax($this->id());

			if ($this->has('orders_id')) self::query_execute('INSERT INTO acc_payments_to_orders (payment_id, order_id, amount) SELECT payment_id, :orders_id, credit_amount FROM acc_payments_to_invoices WHERE invoice_id = :invoice_id', cardinality::NONE, [':orders_id' => $this->get_header('orders_id'), ':invoice_id' => $this->id()]);

			self::query_execute('DELETE FROM acc_invoices WHERE invoice_id = :invoice_id', cardinality::NONE, [':invoice_id' => $this->id()]);
			self::query_execute('DELETE FROM acc_invoice_totals WHERE invoice_id = :invoice_id', cardinality::NONE, [':invoice_id' => $this->id()]);
			self::query_execute('DELETE FROM acc_payments_to_invoices WHERE invoice_id = :invoice_id', cardinality::NONE, [':invoice_id' => $this->id()]);

			foreach ($this->get_products() as $product) {
				$product['ipn']->uninvoice_qty($product);
			}

			self::query_execute('DELETE FROM acc_invoice_items WHERE invoice_id = :invoice_id', cardinality::NONE, [':invoice_id' => $this->id()]);

			//add a transaction statement
			$transaction_history = [
				'transaction_type' => 'Void Invoice',
				'customer_id' => $this->get_header('customers_id'),
			];

			if (!empty($this->get_header('orders_id'))) {
				$transaction_history['order_id'] = $this->get_header('orders_id');
				$sales_order = new ck_sales_order($this->get_header('orders_id'));
				$sales_order->rebuild_invoices();
			}

			$this->create_transaction_history($transaction_history);

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint);
		}
		catch (CKInvoiceException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to remove invoice for order: '.$e->getMessage());
		}
	}

	public function accrue_incentive() {
		$this->update(['incentive_accrued' => self::NOW()->format('Y-m-d H:i:s')]);
	}

	public function pay_incentive() {
		$this->update(['incentive_paid' => self::NOW()->format('Y-m-d H:i:s')]);
	}

	public function update(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			self::query_execute('UPDATE acc_invoices SET '.$params->update_cols(TRUE).' WHERE invoice_id = :invoice_id', cardinality::NONE, $params->query_vals(['invoice_id' => $this->id()], TRUE));

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Error updating invoice: '.$e->getMessage());
		}
	}

	public function create_products(Array $data) {
		$savepoint = self::transaction_begin();

		$response = [];

		try {
			foreach ($data as $prod) {
				// we need to work towards moving all "additional" actions out of the invoicing process and into the transaction creating the invoice
				if (!empty($prod['additional'])) {
					$additional = $prod['additional'];
					unset($prod['additional']);
				}

				$ipn = new ck_ipn2($prod['ipn_id']);

				$prod['invoice_id'] = $this->id();

				$params = new ezparams($prod);
				//var_dump('INSERT INTO acc_invoice_items ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));
				self::query_execute('INSERT INTO acc_invoice_items ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));

				if (!empty($prod['rma_product_id'])) {
					$resp = $ipn->receive_rma_qty($additional['rma_product']);

					if (!empty($resp['inventory_hold_id'])) {
						if (empty($response['inventory_holds'])) $response['inventory_holds'] = [];
						$response['inventory_holds'][$prod['rma_product_id']] = $resp['inventory_hold_id'];
					}
				}
				elseif (empty($additional['skip_inventory_management'])) {
					$ipn->invoice_qty($prod['invoice_item_qty'], $additional['serials']);
				}

				$ipn->rebuild_inventory_data();
			}

			$this->skeleton->rebuild('products');

			self::transaction_commit($savepoint);
			return $response;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create invoice products: '.$e->getMessage());
		}
	}

	public function create_totals(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$overall_total = 0;

			foreach ($data as $ttl) {
				$ttl['invoice_id'] = $this->id();

				if (empty($ttl['invoice_total_date'])) $ttl['invoice_total_date'] = self::NOW()->format('Y-m-d H:i:s');

				$params = new ezparams($ttl);
				self::query_execute('INSERT INTO acc_invoice_totals ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));

				if ($ttl['invoice_total_line_type'] == 'ot_total') $overall_total = $ttl['invoice_total_price'];
			}

			$this->skeleton->rebuild('totals');

			self::transaction_commit($savepoint);
			return $overall_total;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create invoice totals: '.$e->getMessage());
		}
	}

	public function create_note($note) {
		$savepoint = self::transaction_begin();

		try {
			$data = [
				'note_type' => 'acc_invoices',
				'note_type_id' => $this->id(),
				'note_text' => $note
			];

			$params = new ezparams($data);
			self::query_execute('INSERT INTO acc_notes ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create invoice note: '.$e->getMessage());
		}
	}

	public function create_payment_allocations(Array $data, $overall_total=0) {
		$savepoint = self::transaction_begin();

		try {
			$total_paid = 0;
			$total_remaining = $overall_total;
			foreach ($data as $payment) {
				if ($total_remaining <= 0) break;

				$payment['credit_amount'] = min($total_remaining, $payment['credit_amount']);
				$payment['invoice_id'] = $this->id();

				if (empty($payment['credit_date'])) $payment['credit_date'] = self::NOW()->format('Y-m-d H:i:s');

				$order_id = !empty($payment['order_id'])?$payment['order_id']:NULL;
				unset($payment['order_id']);
				$customer_id = !empty($payment['customer_id'])?$payment['customer_id']:NULL;
				unset($payment['customer_id']);

				$params = new ezparams($payment);
				self::query_execute('INSERT INTO acc_payments_to_invoices ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));
				$payments_to_invoices_id = self::fetch_insert_id();

				$total_paid += $payment['credit_amount'];
				$total_remaining -= $payment['credit_amount'];

				$transaction_history = [
					'transaction_type' => 'Applied Credit to Invoice',
					'payment_id' => $payment['payment_id'],
					'payment_to_invoice_id' => $payments_to_invoices_id,
					'order_id' => $order_id,
					'customer_id' => $customer_id,
				];

				$this->create_transaction_history($transaction_history);
			}

			$this->skeleton->rebuild('payments');

			self::transaction_commit($savepoint);

			return $total_paid;
		}
		catch (CKInvoiceException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create invoice payment allocations: '.$e->getMessage());
		}
	}

	public function remove_payment_allocations($payment_to_invoice_id=NULL) {
		$savepoint = self::transaction_begin();

		try {
			if (!empty($payment_to_invoice_id)) self::query_execute('DELETE FROM acc_payments_to_invoices WHERE invoice_id = :invoice_id AND payment_to_invoice_id = :payment_to_invoice_id', cardinality::NONE, [':invoice_id' => $this->id(), ':payment_to_invoice_id' => $payment_to_invoice_id]);
			else self::query_execute('DELETE FROM acc_payments_to_invoices WHERE invoice_id = :invoice_id', cardinality::NONE, [':invoice_id' => $this->id()]);

			$this->update(['paid_in_full' => 0]);

			$this->skeleton->rebuild('header');
			$this->skeleton->rebuild('payments');

			self::transaction_commit($savepoint);
		}
		catch (CKInvoiceException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to remove payment allocation: '.$e->getMessage());
		}
	}

	public function create_transaction_history(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$data['invoice_id'] = $this->id();

			if (empty($data['admin_id'])) $data['admin_id'] = !empty($_SESSION['login_id'])?$_SESSION['login_id']:ck_sales_order::$solutionsteam_id;
			if (empty($data['transaction_date'])) $data['transaction_date'] = self::NOW()->format('Y-m-d H:i:s');

			$params = new ezparams($data);
			self::query_execute('INSERT INTO acc_transaction_history ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create invoice transaction history: '.$e->getMessage());
		}
	}

	// mostly copied from legacy credit insert process
	public function create_credit(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$unapplied = !empty($data['unapplied']);
			unset($data['unapplied']);

			if (empty($data['customer_id'])) $data['customer_id'] = $this->get_header('customers_id');
			if (empty($data['payment_date'])) $data['payment_date'] = self::NOW()->format('Y-m-d H:i:s');

			$params = new ezparams($data);
			self::query_execute('INSERT INTO acc_payments ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));
			$payment_id = self::fetch_insert_id();

			$transaction_history = [
				'transaction_type' => $data['payment_amount']>0?'Insert Credit':'Insert Reverse Credit',
				'order_id' => $this->get_header('orders_id'),
				'payment_id' => $payment_id,
				'customer_id' => $data['customer_id'],
			];

			$this->create_transaction_history($transaction_history);

			if (!$unapplied) {
				// credit memo, delete other payment allocations and just allocate this one instead
				if ($data['payment_method_id'] == 12) $this->remove_payment_allocations();

				// apply the new payment and mark the original invoice paid in full
				$this->create_payment_allocations([['payment_id' => $payment_id, 'credit_amount' => $data['payment_amount'], 'customer_id' => $data['customer_id']]], $this->get_simple_totals('total'));
			}
			else {
				$this->update(['credit_payment_id' => $payment_id]);
			}

			self::transaction_commit($savepoint);

			return $payment_id;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to create invoice credit: '.$e->getMessage());
		}
	}

	public function figure_paid_status() {
		$this->skeleton->rebuild('simple_totals');
		$this->skeleton->rebuild('payments');

		if ($this->get_balance() <= 0) $this->set_paid_status(1);
		else $this->set_paid_status(0);
	}

	public function set_paid_status($status) {
		$this->update(['paid_in_full' => $status]);
	}

	public static function accrue_incentives(Array $invoice_ids, DateTime $timestamp=NULL) {
		$invoices = new prepared_fields($invoice_ids);

		if (empty($timestamp)) $timestamp = new DateTime;
		$accrual_date = new prepared_fields([$timestamp->format('Y-m-d H:i:s')]);

		prepared_query::execute('UPDATE acc_invoices SET incentive_accrued = ? WHERE invoice_id IN ('.$invoices->select_values().')', prepared_fields::consolidate_parameters($accrual_date, $invoices));
	}

	public static function pay_incentives(Array $invoice_ids, DateTime $timestamp=NULL) {
		$invoices = new prepared_fields($invoice_ids);

		if (empty($timestamp)) $timestamp = new DateTime;
		$payment_date = new prepared_fields([$timestamp->format('Y-m-d H:i:s')]);

		prepared_query::execute('UPDATE acc_invoices SET incentive_paid = ? WHERE invoice_id IN ('.$invoices->select_values().')', prepared_fields::consolidate_parameters($payment_date, $invoices));
	}

	public function delete_ghost() {
		// this is used @ /admin/ghost-invoices only. $this->remove() should be used in most contexts
		$savepoint = self::transaction_begin();

		try {

			self::query_execute('DELETE FROM acc_invoices WHERE invoice_id = :invoice_id', cardinality::NONE, [':invoice_id' => $this->id()]);
			self::query_execute('DELETE FROM acc_invoice_totals WHERE invoice_id = :invoice_id', cardinality::NONE, [':invoice_id' => $this->id()]);
			self::query_execute('DELETE FROM acc_payments_to_invoices WHERE invoice_id = :invoice_id', cardinality::NONE, [':invoice_id' => $this->id()]);

			self::query_execute('DELETE FROM acc_invoice_items WHERE invoice_id = :invoice_id', cardinality::NONE, [':invoice_id' => $this->id()]);

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint);
		}
		catch (CKInvoiceException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKInvoiceException('Failed to delete ghost invoice for order: '.$e->getMessage());
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/

	public static function generate_pdf($order_id=NULL, $invoice_id=NULL) {
		try {
			ob_start();
			$_GET['invId'] = $invoice_id;
			$_GET['oID'] = $order_id;
			$GLOBALS['skip_app_top'] = TRUE;

			require __DIR__ . '/../../admin/invoice.php';

			$content = ob_get_contents();

			ob_end_clean();

			$doc = new api_doc_raptor();
			return $doc->create_pdf($content, 'invoice.pdf');
		}
		catch(Exception $e) {
			// nothing
		}
	}

	public function email_invoice_pdf($recipients=[]) {
		if (!$this->has_order()) return FALSE;
		try {
			$order = $this->get_order();

			$mailer = service_locator::get_mail_service();
			$mail = $mailer->create_mail();

			$mail->set_from($order->get_contact_email(), 'CablesAndKits.com Sales Team');

			// if extra login email isn't empty, send email to it
			if (!empty($order->get_header('extra_logins_email_address'))) {
				$mail->add_to($order->get_header('extra_logins_email_address'), $order->get_header('extra_logins_firstname') . ' ' . $order->get_header('extra_logins_lastname'));
			}

			// if there is no extra login, or extra login is set to copy main login, add main login email to recipients
			if (empty($order->get_header('extra_logins_email_address')) || $order->is('extra_logins_copy_account')) {
				if (empty($order->get_header('customers_email_address'))) $email_address = $order->get_customer()->get_header('email_address');
				else $email_address = $order->get_header('customers_email_address');
				$mail->add_to($email_address, $order->get_header('customers_name'));
			}

			if ($order->has_recipients()) {
				foreach ($order->get_recipients() as $recipient) {
					$mail->add_to($recipient['email'], $recipient['name']);
				}
			}

			$mail->set_subject('CablesAndKits Invoice # ' . $this->id());

			$body = 'Hello! Please find the requested invoice attached to this email.'."\r\n<br><br>";

			$body .= 'Thanks!'."\r\n<br><br>";
			$body .= 'CablesAndKits Customer Solutions Team'."\r\n<br>";
			$body .= 'sales@cablesandkits.com'."\r\n<br>";
			$body .= '(888)-622-0223'."\r\n<br>";
			$body .= 'www.cablesandkits.com';

			$mail->set_body($body);


			$pdf = self::generate_pdf($this->get_header('orders_id'), $this->id());
			$mail->create_attachment($pdf, 'CablesAndKits.com_Invoice_'.$this->id().'.pdf');

			$mailer->send($mail);

			/*if (!$mail->send()) {
				$to = $mail->to();
				throw new CKInvoiceException('Failed to send notice to the following email addresses: '.implode(', ', array_keys($to)));
			}*/
		}
		catch (CKInvoiceException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKInvoiceException('Failed to send late invoice notification.', $e->getCode(), $e);
		}
	}

}

class CKInvoiceException extends CKMasterArchetypeException {
}
?>
