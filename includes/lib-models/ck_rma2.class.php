<?php
class ck_rma2 extends ck_archetype {

	protected static $skeleton_type = 'ck_rma_type';

	protected static $queries = [
		'rma_header' => [
			'qry' => 'SELECT r.id as rma_id, r.order_id as orders_id, o.customers_id, r.closed, r.disposition, r.restock_fee_percent, r.tracking_number as tracking_number, r.follow_up_date, r.created_on, r.created_by as admin_id, r.refund_shipping, r.shipping_amount, r.refund_coupon, r.coupon_amount FROM rma r LEFT JOIN orders o ON r.order_id = o.orders_id WHERE r.id = :rma_id',
			'cardinality' => cardinality::ROW
		],

		'products' => [
			'qry' => 'SELECT rp.id as rma_product_id, rp.order_product_id as orders_products_id, op.products_id, op.products_price as revenue, op.final_price, op.products_quantity as original_order_qty, p.stock_id, rp.serial_id, rp.quantity, rp.reason_id, rr.description as reason, rp.comments, rp.received_date, rp.received_by as admin_id, rp.not_defective FROM rma_product rp LEFT JOIN orders_products op ON rp.order_product_id = op.orders_products_id LEFT JOIN products p ON op.products_id = p.products_id LEFT JOIN rma_reason rr ON rp.reason_id = rr.id WHERE rp.rma_id = :rma_id',
			'cardinality' => cardinality::SET
		],

		'notes' => [
			'qry' => 'SELECT rn.id as rma_note_id, rn.created_on, rn.created_by as admin_id, rn.note_text as note FROM rma_note rn WHERE rn.rma_id = :rma_id',
			'cardinality' => cardinality::SET
		],

		'invoices' => [
			'qry' => "SELECT ai.invoice_id, ai.customer_id, ai.customers_extra_logins_id, ai.inv_date as invoice_date, ai.po_number, ai.paid_in_full, ai.credit_memo, ai.original_invoice, ai.late_notice_date, ait.invoice_total_price as total FROM acc_invoices ai LEFT JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_total' WHERE rma_id = :rma_id ORDER BY ai.invoice_id DESC",
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
	];

	public function __construct($rma_id, ck_sales_order_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($rma_id);

		if (!$this->skeleton->built('rma_id')) $this->skeleton->load('rma_id', $rma_id);
		if ($this->skeleton->built('header')) $this->normalize_header();

		self::register($rma_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('rma_id');
	}

	const STATUS_OPEN = 'OPEN';
	const STATUS_PARTIALLY_RECEIVED = 'PARTIALLY RECEIVED';
	const STATUS_RECEIVED = 'RECEIVED';
	const STATUS_CLOSED = 'CLOSED';

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('rma_header', [':rma_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$date_fields = ['follow_up_date', 'created_on'];
		foreach ($date_fields as $field) {
			$header[$field] = ck_datetime::datify($header[$field]);
		}

		$bool_fields = ['closed', 'refund_shipping', 'refund_coupon'];
		foreach ($bool_fields as $field) {
			$header[$field] = CK\fn::check_flag($header[$field]);
		}

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('rma_header', [':rma_id' => $this->id()]));
		$this->normalize_header();
	}

	private function build_admin() {
		$this->skeleton->load('admin', new ck_admin($this->get_header('admin_id')));
	}

	private function build_sales_order() {
		$this->skeleton->load('sales_order', new ck_sales_order($this->get_header('orders_id')));
	}

	private function build_customer() {
		$this->skeleton->load('customer', new ck_customer2($this->get_header('customers_id')));
	}

	private function build_products() {
		$products = self::fetch('products', [':rma_id' => $this->id()]);

		$consolidated_products = [];

		foreach ($products as &$product) {
			$product['listing'] = new ck_product_listing($product['products_id']);
			$product['ipn'] = new ck_ipn2($product['stock_id']);
			if (!empty($product['serial_id'])) $product['serial'] = new ck_serial($product['serial_id']);

			$product['received'] = FALSE;

			if (!self::date_is_empty($product['received_date'])) {
				$product['received_date'] = self::DateTime($product['received_date']);
				$product['received'] = TRUE;
			}

			if (!empty($product['admin_id'])) {
				$product['admin'] = new ck_admin($product['admin_id']);
				$product['received'] = TRUE;
			}

			$product['not_defective'] = CK\fn::check_flag($product['not_defective']);

			if (empty($consolidated_products[$product['orders_products_id']])) {
				$consolidated_products[$product['orders_products_id']] = $product;
				$consolidated_products[$product['orders_products_id']]['serials'] = [];
				$consolidated_products[$product['orders_products_id']]['rma_product_ids'] = [];
				$consolidated_products[$product['orders_products_id']]['quantity'] = 0;
			}

			$consolidated_products[$product['orders_products_id']]['quantity'] += $product['quantity'];
			if (!empty($product['serial'])) $consolidated_products[$product['orders_products_id']]['serials'][] = $product['serial'];
			$consolidated_products[$product['orders_products_id']]['rma_product_ids'][] = $product['rma_product_id'];
		}

		$this->skeleton->load('products', $products);
		$this->skeleton->load('consolidated_products', $consolidated_products);
	}

	private function build_notes() {
		$notes = self::fetch('notes', [':rma_id' => $this->id()]);

		foreach ($notes as &$note) {
			$note['created_on'] = self::DateTime($note['created_on']);
			$note['admin'] = new ck_admin($note['admin_id']);
		}

		$this->skeleton->load('notes', $notes);
	}

	private function build_invoices() {
		// this is just a stub in place of a fuller invoice class
		$invoices = self::fetch('invoices', [':rma_id' => $this->id()]);

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

	private function build_total() {
		$sales_order = $this->get_sales_order();

		$invrow = $sales_order->get_latest_invoice();
		$inv = new ck_invoice($invrow['invoice_id']);

		$invoice_products = $inv->get_consolidated_products();

		$subtotal = 0;
		$ship_total = 0;
		$coupon_total = 0;
		$restock_total = 0;
		$tax = 0;
		$total = 0;

		foreach ($this->get_products() as $product) {
			if (!($invoice_product = $invoice_products[$product['orders_products_id']])) continue;
			$subtotal += abs($invoice_product['revenue'] * $product['quantity']);
		}

		if ($this->is('refund_shipping') && $this->get_header('shipping_amount') > 0) {
			$ship_total = abs($this->get_header('shipping_amount'));
		}

		if ($this->is('refund_coupon') && $this->get_header('coupon_amount') > 0) {
			$coupon_total = abs($this->get_header('coupon_amount'));
		}

		if ($this->has_restock_percentage()) {
			$restock_total = abs($subtotal * $this->get_restock_percentage());
		}

		// tax figuring for RMAs works off of invoicing - so we'll skip it for the moment.  See create_invoice_from_rma_receipt to pull it back in

		//total = +subtotal +tax +refunded_shipping -refunded_coupon -restock
		$total = ($subtotal + $tax + $ship_total) - ($coupon_total + $restock_total);

		$this->skeleton->load('total', $total);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function get_admin() {
		if (!$this->skeleton->built('admin')) $this->build_admin();
		return $this->skeleton->get('admin');
	}

	public function get_status() {
		if ($this->is('closed')) return self::STATUS_CLOSED;
		else {
			$product_status = array_reduce($this->get_products(), function($result, $product) {
				if ($product['received']) $result['has_received'] = TRUE;
				else $result['has_unreceived'] = TRUE;

				return $result;
			}, ['has_received' => FALSE, 'has_unreceived' => FALSE]);

			if (!$product_status['has_received']) return self::STATUS_OPEN;
			elseif (!$product_status['has_unreceived']) return self::STATUS_RECEIVED;
			else return self::STATUS_PARTIALLY_RECEIVED;
		}
	}

	public function get_sales_order() {
		if (!$this->skeleton->built('sales_order')) $this->build_sales_order();
		return $this->skeleton->get('sales_order');
	}

	public function get_customer() {
		if (!$this->skeleton->built('customer')) $this->build_customer();
		return $this->skeleton->get('customer');
	}

	public function has_products() {
		if (!$this->skeleton->built('products')) $this->build_products();
		return $this->skeleton->has('products');
	}

	public function get_products($key=NULL) {
		if (!$this->has_products()) return [];
		if (empty($key)) return $this->skeleton->get('products');
		else {
			foreach ($this->skeleton->get('products') as $product) {
				if ($product['rma_product_id'] == $key) return $product;
			}
			return NULL;
		}
	}

	public function get_consolidated_products() {
		if (!$this->has_products()) return [];
		return $this->skeleton->get('consolidated_products');
	}

	public function has_notes() {
		if (!$this->skeleton->built('notes')) $this->build_notes();
		return $this->skeleton->has('notes');
	}

	public function get_notes($key=NULL) {
		if (!$this->has_notes()) return [];
		if (empty($key)) return $this->skeleton->get('notes');
		else {
			foreach ($this->skeleton->get('notes') as $note) {
				if ($note['rma_note_id'] == $key) return $note;
			}
			return NULL;
		}
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

	public function get_rma_total() {
		if (!$this->skeleton->built('total')) $this->build_total();
		return $this->skeleton->get('total');
	}

	public function get_latest_invoice($key=NULL) {
		if (!$this->has_invoices()) return NULL;
		$invoice = $this->get_invoices()[0];
		if (empty($key)) return $invoice;
		else return $invoice[$key];
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

	public function has_restock_percentage() {
		return $this->get_header('restock_fee_percent') > 0;
	}

	public function get_restock_percentage() {
		return $this->get_header('restock_fee_percent') / 100;
	}

	public function get_restock_fee($products=NULL) {
		$fee = 0;

		if ($this->has_restock_percentage()) {
			$pct = $this->get_restock_percentage();
			if (empty($products)) $products = $this->get_products();
			foreach ($products as $product) {
				if (!$product['received']) continue;
				$fee += $product['quantity'] * $product['revenue'] * $pct;
			}	
		}

		return $fee;
	}

	/*-------------------------------
	// modify data
	-------------------------------*/

	public function force_rebuild() {
		$this->skeleton->rebuild();
	}

	public function close() {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('UPDATE rma SET closed = 1 WHERE id = :rma_id', [':rma_id' => $this->id()]);

			$this->skeleton->rebuild();
		}
		catch (CKRmaException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKRmaException('Failed to close RMA.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function create_note(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$data['rma_id'] = $this->id();

			if (empty($data['created_on'])) $data['created_on'] = self::NOW()->format('Y-m-d H:i:s');
			if (empty($data['created_by'])) $data['created_by'] = !empty($_SESSION['login_id'])?$_SESSION['login_id']:ck_sales_order::$solutionsteam_id;

			$params = new ezparams($data);
			self::query_execute('INSERT INTO rma_note ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));
			$rma_note_id = self::fetch_insert_id();

			$this->skeleton->rebuild('notes');

			self::transaction_commit($savepoint);
			return $rma_note_id;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKRmaException('Failed adding RMA note: '.$e->getMessage());
		}
	}

	public function set_tracking_number($tracking_number) {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE rma SET tracking_number = :tracking_number WHERE id = :rma_id', cardinality::NONE, [':tracking_number' => $tracking_number, ':rma_id' => $this->id()]);

			$this->skeleton->rebuild('header');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKRmaException('Failed setting tracking number: '.$e->getMessage());
		}
	}

	public function receive_product($rma_product_id) {
		$savepoint = self::transaction_begin();

		try {
			

			$this->skeleton->rebuild('products');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKRmaException('Failed receiving RMA product: '.$e->getMessage());
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/
}

class CKRmaException extends CKMasterArchetypeException {
}
?>
