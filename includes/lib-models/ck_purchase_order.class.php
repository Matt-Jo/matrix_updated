<?php
class ck_purchase_order extends ck_archetype {

	protected static $skeleton_type = 'ck_purchase_order_type';

	protected static $queries = [
		'purchase_order_header_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT po.id as purchase_order_id, po.purchase_order_number, po.status as po_status_id, po.receiving_lock, po.creator as creator_admin_id, po.administrator_admin_id, po.owner_admin_id, po.buyback_admin_id, po.vendor as vendor_id, v.vendors_company_name as vendor, v.vendors_email_address, po.creation_date, po.submit_date, po.expected_date, po.drop_ship, po.shipping_method as po_shipping_method_id, po.notes, po.terms as po_terms_id, po.confirmation_status as confirmation_status_id, po.confirmation_hash, po.show_vendor_pn, po.followup_date, ce.entity_name',
				'from' => 'FROM purchase_orders po JOIN vendors v ON po.vendor = v.vendors_id LEFT JOIN ck_entities ce ON po.entity_id = ce.entity_id',
				'where' => 'WHERE' // will fail if we don't provide our own
			],
			'proto_opts' => [
				':purchase_order_id' => 'po.id = :purchase_order_id',
				':vendor_id' => 'po.vendor = :vendor_id',
				':admin_id' => 'po.administrator_admin_id = :admin_id',
				':po_status_id' => 'po.status = :po_status_id',
				':purchase_order_number' => 'po.purchase_order_number = :purchase_order_number'
			],
			'proto_defaults' => [
				'where' => 'po.id = :purchase_order_id'
			],
			'proto_count_clause' => 'COUNT(po.id)',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'notes' => [
			'qry' => 'SELECT pon.id as po_note_id, pon.purchase_order_note_user as note_admin_id, a.admin_firstname, a.admin_lastname, a.admin_email_address, pon.purchase_order_note_created as note_created, pon.purchase_order_note_text as note_text, pon.purchase_order_note_deleted as note_deleted, pon.purchase_order_note_modified as note_modified FROM purchase_order_notes pon LEFT JOIN admin a ON pon.purchase_order_note_user = a.admin_id WHERE pon.purchase_order_id = :purchase_order_id ORDER BY pon.purchase_order_note_created ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'products' => [
			'qry' => 'SELECT pop.id as po_product_id, pop.ipn_id as stock_id, pop.quantity, pop.cost, pop.description, IFNULL(SUM(porp.quantity_received), 0) as qty_received, pop.unexpected, pop.unexpected_freebie, pop.unexpected_notes, pop.additional_cost, vtsi.vendors_pn FROM purchase_order_products pop LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id JOIN purchase_orders po ON pop.purchase_order_id = po.id LEFT JOIN (SELECT stock_id, vendors_id, MAX(vendors_pn) as vendors_pn FROM vendors_to_stock_item GROUP BY stock_id, vendors_id) vtsi ON vtsi.stock_id = pop.ipn_id AND vtsi.vendors_id = po.vendor WHERE pop.purchase_order_id = :purchase_order_id AND (:po_product_id IS NULL OR pop.id = :po_product_id) GROUP BY pop.id ORDER BY pop.id',
			'cardinality' => cardinality::SET,
		],

		'receiving_sessions' => [
			'qry' => 'SELECT pors.id as po_receiving_session_id, pors.`date` as session_received_date, pors.receiver as receiver_admin_id, a.admin_firstname as receiver_firstname, a.admin_lastname as receiver_lastname, a.admin_email_address as receiver_email_address, pors.notes, pors.purchase_order_tracking_id, pot.tracking_number FROM purchase_order_receiving_sessions pors LEFT JOIN admin a ON pors.receiver = a.admin_id LEFT JOIN purchase_order_tracking pot ON pors.purchase_order_tracking_id = pot.id WHERE pors.purchase_order_id = :purchase_order_id ORDER BY pors.`date` ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'received_products' => [
			'qry' => 'SELECT porp.id as po_received_product_id, porp.receiving_session_id as po_receiving_session_id, porp.purchase_order_product_id, porp.quantity_received, porp.quantity_remaining, porp.paid, porp.entered, pop.ipn_id as stock_id FROM purchase_order_received_products porp JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id WHERE pop.purchase_order_id = :purchase_order_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'reviews' => [
			'qry' => 'SELECT por.id as po_review_id, por.status, por.creator as creator_admin_id, ac.admin_firstname as creator_firstname, ac.admin_lastname as creator_lastname, ac.admin_email_address as creator_email_address, por.reviewer as reviewer_admin_id, ar.admin_firstname as reviewer_firstname, ar.admin_lastname as reviewer_lastname, ar.admin_email_address as reviewer_email_address, por.notes, por.created_on, por.modified_on, IFNULL(por.purchase_order_tracking_id, 0) as purchase_order_tracking_id, pot.tracking_number, por.lock_for_review FROM purchase_order_review por LEFT JOIN admin ac ON por.creator = ac.admin_id LEFT JOIN admin ar ON por.reviewer = ar.admin_id LEFT JOIN purchase_order_tracking pot ON por.purchase_order_tracking_id = pot.id WHERE por.po_number = :purchase_order_number AND (:po_review_id IS NULL OR por.id = :po_review_id) ORDER BY por.created_on DESC',
			'cardinality' => cardinality::SET,
		],

		'reviewing_products' => [
			'qry' => 'SELECT porevp.id as po_review_product_id, porevp.po_review_id, porevp.psc_stock_id as stock_id, porevp.pop_id as po_product_id, porevp.qty_received, porevp.notes, porevp.hold_disposition_id, porevp.created_on, porevp.modified_on, porevp.weight, porevp.status, pop.unexpected FROM purchase_order_review_product porevp JOIN purchase_order_products pop ON porevp.pop_id = pop.id WHERE pop.purchase_order_id = :purchase_order_id',
			'cardinality' => cardinality::SET,
		],

		'reviewing_serials' => [
			'qry' => 'SELECT serial_id FROM serials_history WHERE porp_id = :po_review_product_id',
			'cardinality' => cardinality::COLUMN,
		],

		'allocations' => [
			'qry' => 'SELECT potoa.id as po_allocation_id, potoa.purchase_order_product_id as po_product_id, pop.ipn_id as stock_id, op.orders_id, potoa.order_product_id as orders_products_id, potoa.quantity, potoa.modified, potoa.admin_id as allocator_admin_id, a.admin_firstname as allocator_firstname, a.admin_lastname as allocator_lastname, a.admin_email_address as allocator_email_address FROM purchase_order_to_order_allocations potoa JOIN purchase_order_products pop ON potoa.purchase_order_product_id = pop.id JOIN admin a ON potoa.admin_id = a.admin_id LEFT JOIN orders_products op ON potoa.order_product_id = op.orders_products_id WHERE pop.purchase_order_id = :purchase_order_id ORDER BY potoa.order_product_id ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'tracking_numbers' => [
			'qry' => 'SELECT id as po_tracking_id, tracking_number, tracking_method as po_shipping_method_id, eta, STATUS as arrived, bin_number, arrival_time, complete FROM purchase_order_tracking WHERE po_id = :purchase_order_id',
			'cardinality' => cardinality::SET,
		],

		'add_ipn' => [
			'qry' => 'INSERT INTO purchase_order_products (purchase_order_id, ipn_id, quantity, cost, description, unexpected, unexpected_freebie, unexpected_notes) VALUES (:purchase_order_id, :ipn_id, :quantity, :cost, :description, :unexpected, :unexpected_freebie, :unexpected_notes)',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'update_on_order' => [
			'qry' => 'UPDATE products_stock_control SET on_order = on_order + :on_order WHERE stock_id = :stock_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'update_received_session_serials' => [
			'qry' => 'UPDATE purchase_order_review_product porp LEFT JOIN (SELECT porp_id, COUNT(id) as qty_received FROM serials_history WHERE porp_id = :porp_id GROUP BY porp_id) sh ON porp.id = sh.porp_id SET porp.qty_received = IFNULL(sh.qty_received, 0) WHERE porp.id = :porp_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'delete_orphaned_review_product' => [
			'qry' => 'DELETE FROM purchase_order_review_product WHERE id = :porp_id AND qty_received <= 0',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'delete_session_nonserial_hold' => [
			'qry' => '',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'receiving_session_qtys' => [
			'qry' => 'SELECT id as porp_id, receiving_session_id, purchase_order_product_id, quantity_received FROM purchase_order_received_products WHERE receiving_session_id = :receiving_session_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'receiving_session_allocations' => [
			'qry' => 'SELECT potoa.id as potoa_id, potoa.quantity, op.orders_products_id FROM purchase_order_to_order_allocations potoa JOIN orders_products op ON potoa.order_product_id = op.orders_products_id JOIN orders o ON op.orders_id = o.orders_id WHERE potoa.purchase_order_product_id = :purchase_order_product_id ORDER BY CASE WHEN o.orders_status = 5 THEN 0 ELSE 1 END ASC, potoa.order_product_id ASC, potoa.id ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'receiving_session_reduce' => [
			'qry' => 'UPDATE purchase_order_to_order_allocations SET quantity = quantity - :quantity WHERE id = :potoa_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'receiving_session_remove' => [
			'qry' => 'DELETE FROM purchase_order_to_order_allocations WHERE id = :potoa_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'additional_costs' => [
			'qry' => 'SELECT purchase_order_additional_cost_id, purchase_order_id, description, amount, cost_spread, date_created FROM purchase_order_additional_costs WHERE purchase_order_id = :purchase_order_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'create_additional_cost' => [
			'qry' => 'INSERT INTO purchase_order_additional_costs (purchase_order_id, description, amount) VALUES (:purchase_order_id, :description, :amount)',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'update_additional_cost' => [
			'qry' => 'UPDATE purchase_order_additional_costs SET description = :description, amount = :amount WHERE purchase_order_additional_cost_id = :purchase_order_additional_cost_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'delete_additional_cost' => [
			'qry' => 'DELETE FROM purchase_order_additional_costs WHERE purchase_order_additional_cost_id = :purchase_order_additional_cost_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'pos_with_open_reviews' => [
			'qry' => 'SELECT DISTINCT po.id FROM purchase_order_review por JOIN purchase_orders po ON por.po_number = po.purchase_order_number WHERE por.status != :completed AND po.status NOT IN (:received, :voided)',
			'cardinality' => cardinality::COLUMN
		]
	];

	public static $po_statuses = [
		'OPEN' => 1,
		'PARTIALLY_RECEIVED' => 2,
		'RECEIVED' => 3,
		'VOIDED' => 4
	];

	public static $review_statuses = [
		'NEEDS_REVIEW' => 0,
		'READY' => 1,
		'COMPLETED' => 2
	];

	public static $review_product_statuses = [
		'NORMAL' => 0,
		//'ADDITIONAL_QTY' => 1,
		'NEW_PRODUCT' => 2
	];

	public function __construct($purchase_orders_id, ck_purchase_order_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($purchase_orders_id);

		if (!$this->skeleton->built('purchase_orders_id')) $this->skeleton->load('purchase_orders_id', $purchase_orders_id);

		self::register($purchase_orders_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('purchase_orders_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$qry = self::modify_query('purchase_order_header_proto', ['cardinality' => cardinality::ROW]);
			$header = self::fetch($qry, [':purchase_order_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$header['po_status'] = $this->skeleton->lookup('po_statuses', $header['po_status_id']);
		$header['po_shipping_method'] = $this->skeleton->lookup('po_shipping_methods', $header['po_shipping_method_id']);
		$header['po_terms'] = $this->skeleton->lookup('po_terms', $header['po_terms_id']);
		$header['confirmation_status'] = $this->skeleton->lookup('po_confirmation_statuses', $header['confirmation_status_id']);

		$date_fields = ['creation_date', 'submit_date', 'expected_date', 'followup_date'];
		foreach ($date_fields as $field) {
			$header[$field] = !self::date_is_empty($header[$field])?self::DateTime($header[$field]):NULL;
		}

		$bool_fields = ['receiving_lock', 'drop_ship'];
		foreach ($bool_fields as $field) {
			$header[$field] = CK\fn::check_flag($header[$field]);
		}

		$admin_fields = ['creator_admin_id' => 'creator', 'administrator_admin_id' => 'administrator', 'owner_admin_id' => 'owner', 'buyback_admin_id' => 'buyback_admin'];
		foreach ($admin_fields as $admin_id => $field) {
			if (!empty($header[$admin_id])) $header[$field] = new ck_admin($header[$admin_id]);
		}

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$qry = self::modify_query('purchase_order_header_proto', ['cardinality' => cardinality::ROW]);
		$header = self::fetch($qry, [':purchase_order_id' => $this->id()]);
		$this->skeleton->load('header', $header);
		$this->normalize_header();
	}

	private function build_notes() {
		$notes = self::fetch('notes', [':purchase_order_id' => $this->id()]);

		foreach ($notes as &$note) {
			$note['note_created'] = self::DateTime($note['note_created']);
			$note['note_modified'] = self::DateTime($note['note_modified']);
		}

		$this->skeleton->load('notes', $notes);
	}

	private function build_products() {
		$products = self::fetch('products', [':purchase_order_id' => $this->id(), ':po_product_id' => NULL]);

		$reviewing_products = [];
		foreach ($this->get_open_reviewing_products() as $rsid => $rps) {
			foreach ($rps as $rp) {
				if (empty($reviewing_products[$rp['po_product_id']])) $reviewing_products[$rp['po_product_id']] = 0;
				$reviewing_products[$rp['po_product_id']] += $rp['qty_received'];
			}
		}

		$products = self::normalize_products($products, $reviewing_products);

		$this->skeleton->load('products', $products);
	}

	private static function normalize_products($products, $reviewing_products) {
		return array_map(function($p) use ($reviewing_products) {
			$p['ipn'] = new ck_ipn2($p['stock_id']);
			$p['unexpected'] = CK\fn::check_flag($p['unexpected']);
			$p['qty_remaining'] = $p['quantity'] - $p['qty_received'];
			$p['qty_reviewing'] = !empty($reviewing_products[$p['po_product_id']])?$reviewing_products[$p['po_product_id']]:0;
			return $p;
		}, $products);
	}

	private function build_receiving_sessions() {
		$receiving_sessions = self::fetch('receiving_sessions', [':purchase_order_id' => $this->id()]);

		foreach ($receiving_sessions as &$receiving_session) {
			$receiving_session['session_received_date'] = self::DateTime($receiving_session['session_received_date']);
		}

		$this->skeleton->load('receiving_sessions', $receiving_sessions);
	}

	private function build_received_products() {
		$received_products = self::fetch('received_products', [':purchase_order_id' => $this->id()]);

		foreach ($received_products as &$product) {
			$product['ipn'] = new ck_ipn2($product['stock_id']);
		}

		$this->skeleton->load('received_products', $received_products);
	}

	private function build_reviews() {
		$reviews = self::fetch('reviews', [':purchase_order_number' => $this->get_header('purchase_order_number'), ':po_review_id' => NULL]);

		$reviews = self::normalize_reviews($reviews);

		$this->skeleton->load('reviews', $reviews);
	}

	private static function normalize_reviews($reviews) {
		return array_map(function($r) {
			$r['created_on'] = self::DateTime($r['created_on']);
			$r['modified_on'] = self::DateTime($r['modified_on']);
			$r['lock_for_review'] = CK\fn::check_flag($r['lock_for_review']);
			return $r;
		}, $reviews);
	}

	private function build_reviewing_products() {
		$products = self::fetch('reviewing_products', [':purchase_order_id' => $this->id()]);

		$products = self::normalize_reviewing_products($products);

		$this->skeleton->load('reviewing_products', $products);
	}

	private static function normalize_reviewing_products($products) {
		return array_map(function($p) {
			$p['ipn'] = new ck_ipn2($p['stock_id']);
			$p['created_on'] = self::DateTime($p['created_on']);
			$p['modified_on'] = self::DateTime($p['modified_on']);
			$p['unexpected'] = CK\fn::check_flag($p['unexpected']);

			if ($p['ipn']->is('serialized')) {
				$p['serials'] = [];
				foreach (self::fetch('reviewing_serials', [':po_review_product_id' => $p['po_review_product_id']]) as $serial_id) {
					$p['serials'][] = new ck_serial($serial_id);
				}
			}

			return $p;
		}, $products);
	}

	private function build_allocations() {
		$allocations = self::fetch('allocations', [':purchase_order_id' => $this->id()]);

		foreach ($allocations as &$allocation) {
			$allocation['modified'] = self::DateTime($allocation['modified']);
		}

		$this->skeleton->load('allocations', $allocations);
	}

	private function build_tracking_numbers() {
		$tracking_numbers = self::fetch('tracking_numbers', [':purchase_order_id' => $this->id()]);

		foreach ($tracking_numbers as &$tracking_number) {
			$tracking_number['eta'] = self::DateTime($tracking_number['eta']);
			$tracking_number['arrival_time'] = self::DateTime($tracking_number['arrival_time']);
		}

		$this->skeleton->load('tracking_numbers', $tracking_numbers);
	}

	private function build_additional_costs() {
		$additional_costs = self::fetch('additional_costs', [':purchase_order_id' => $this->id()]);
		$this->skeleton->load('additional_costs', $additional_costs);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header')[$key];
	}

	public function is_receiving_locked() {
		return $this->is('receiving_lock');
	}

	public function is_prepaid() {
		return in_array($this->get_header('po_terms'), ['Credit Card', 'PayPal', 'Prepay', 'Wire', 'CC - Capital One', 'CC - Amex']);
	}

	public function has_notes() {
		if (!$this->skeleton->built('notes')) $this->build_notes();
		return $this->skeleton->has('notes');
	}

	public function get_notes($po_note_id=NULL) {
		if (!$this->has_notes()) return [];
		if (empty($po_note_id)) return $this->skeleton->get('notes');
		else {
			foreach ($this->skeleton->get('notes') as $note) {
				if ($note['po_note_id'] == $po_note_id) return $note;
			}
			return NULL;
		}
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
				if ($key == $product['po_product_id']) return $product;
			}
			return NULL;
		}
	}

	public function get_product_direct($po_product_id) {
		$products = self::fetch('products', [':purchase_order_id' => $this->id(), ':po_product_id' => $po_product_id]);

		if (!empty($products)) {
			$reviewing_products = [];
			foreach ($this->get_open_reviewing_products_direct($po_product_id) as $rsid => $rp) {
				if (empty($reviewing_products[$rp['po_product_id']])) $reviewing_products[$rp['po_product_id']] = 0;
				$reviewing_products[$rp['po_product_id']] += $rp['qty_received'];
			}

			$products = self::normalize_products($products, $reviewing_products);

			return $products[0];
		}
		else return NULL;
	}

	public function get_product_by_review_product($po_review_product_id) {
		foreach ($this->get_reviewing_products() as $rp) {
			if ($rp['po_review_product_id'] == $po_review_product_id) return $this->get_products($rp['po_product_id']);
		}
		return NULL;
	}

	public function has_receiving_sessions() {
		if (!$this->skeleton->built('receiving_sessions')) $this->build_receiving_sessions();
		return $this->skeleton->has('receiving_sessions');
	}

	public function get_receiving_sessions($key=NULL) {
		if (!$this->has_receiving_sessions()) return [];
		if (empty($key)) return $this->skeleton->get('receiving_sessions');
		else {
			foreach ($this->skeleton->get('receiving_sessions') as $session) {
				if ($key == $session['po_receiving_session_id']) return $session;
			}
			return NULL;
		}
	}

	public function has_received_products() {
		if (!$this->skeleton->built('received_products')) $this->build_received_products();
		return $this->skeleton->has('received_products');
	}

	public function get_received_products($key=NULL) {
		if (!$this->has_received_products()) return [];
		if (empty($key)) return $this->skeleton->get('received_products');
		else {
			$received_products = [];
			foreach ($this->skeleton->get('received_products') as $rp) {
				if ($rp['po_receiving_session_id'] == $key) $received_products[] = $rp;
			}

			return $received_products;
		}
	}

	public function has_reviews() {
		if (!$this->skeleton->built('reviews')) $this->build_reviews();
		return $this->skeleton->has('reviews');
	}

	public function get_reviews($key=NULL) {
		if (!$this->has_reviews()) return [];
		if (empty($key)) return $this->skeleton->get('reviews');
		else {
			foreach ($this->skeleton->get('reviews') as $review) {
				if ($key == $review['po_review_id']) return $review;
			}
			return NULL;
		}
	}

	public function get_review_direct($po_review_id) {
		$reviews = self::fetch('reviews', [':purchase_order_number' => $this->get_header('purchase_order_number'), ':po_review_id' => $po_review_id]);

		if (!empty($reviews)) {
			$reviews = self::normalize_reviews($reviews);
			return $reviews[0];
		}
		else return NULL;
	}

	public function get_open_reviews_direct() {
		$reviews = prepared_query::fetch('SELECT por.id as po_review_id, por.status, por.creator as creator_admin_id, ac.admin_firstname as creator_firstname, ac.admin_lastname as creator_lastname, ac.admin_email_address as creator_email_address, por.reviewer as reviewer_admin_id, ar.admin_firstname as reviewer_firstname, ar.admin_lastname as reviewer_lastname, ar.admin_email_address as reviewer_email_address, por.notes, por.created_on, por.modified_on, IFNULL(por.purchase_order_tracking_id, 0) as purchase_order_tracking_id, pot.tracking_number, por.lock_for_review FROM purchase_order_review por LEFT JOIN admin ac ON por.creator = ac.admin_id LEFT JOIN admin ar ON por.reviewer = ar.admin_id LEFT JOIN purchase_order_tracking pot ON por.purchase_order_tracking_id = pot.id WHERE por.po_number = :purchase_order_number AND por.status IN (:needs_review, :ready) ORDER BY por.created_on DESC', [':purchase_order_number' => $this->get_header('purchase_order_number'), ':needs_review' => self::$review_statuses['NEEDS_REVIEW'], ':ready' => self::$review_statuses['READY']]);

		if (!empty($reviews)) {
			$reviews = self::normalize_reviews($reviews);
			return $reviews;
		}
		else return NULL;
	}

	public function get_open_reviews($po_tracking_id=NULL) {
		$reviews = [];

		foreach ($this->get_reviews() as $review) {
			// we're looking for open reviews
			if (!in_array($review['status'], [self::$review_statuses['NEEDS_REVIEW'], self::$review_statuses['READY']])) continue;

			// if we're looking ony for this tracking number, then either return it or skip it
			if (!is_null($po_tracking_id)) { // gotta check for null, because "0" means "NONE" and is a legit option
				if ($po_tracking_id == $review['purchase_order_tracking_id']) return $review;
				continue;
			}

			// we're not looking for a specific tracking number, return all open ones
			$reviews[] = $review;
		}

		return $reviews;
	}

	public function get_locked_reviews() {
		$reviews = [];

		foreach ($this->get_reviews() as $review) {
			if ($review['lock_for_review']) $reviews[] = $review;
		}

		return $reviews;
	}

	public function has_reviewing_products($po_review_id=NULL, $po_product_id=NULL) {
		if (!$this->skeleton->built('reviewing_products')) $this->build_reviewing_products();
		if (empty($po_review_id) && empty($po_product_id)) return $this->skeleton->has('reviewing_products');
		elseif (empty($po_product_id)) {
			foreach ($this->skeleton->get('reviewing_products') as $rp) {
				if ($rp['po_review_id'] == $po_review_id) return TRUE;
			}
			return FALSE;
		}
		elseif (empty($po_review_id)) {
			foreach ($this->skeleton->get('reviewing_products') as $rp) {
				if ($rp['po_product_id'] == $po_product_id) return TRUE;
			}
			return FALSE;
		}
		else {
			foreach ($this->skeleton->get('reviewing_products') as $rp) {
				if ($rp['po_review_id'] == $po_review_id && $rp['po_product_id'] == $po_product_id) return TRUE;
			}
			return FALSE;
		}
	}

	public function get_reviewing_products($po_review_id=NULL, $po_product_id=NULL) {
		if (!$this->has_reviewing_products()) return [];
		if (empty($po_review_id) && empty($po_product_id)) return $this->skeleton->get('reviewing_products');
		elseif (empty($po_product_id)) {
			$reviewing_products = [];
			foreach ($this->skeleton->get('reviewing_products') as $rp) {
				if ($rp['po_review_id'] == $po_review_id) $reviewing_products[] = $rp;
			}

			return $reviewing_products;
		}
		elseif (empty($po_review_id)) {
			$reviewing_products = [];
			foreach ($this->skeleton->get('reviewing_products') as $rp) {
				if ($rp['po_product_id'] == $po_product_id) $reviewing_products[] = $rp;
			}

			return $reviewing_products;
		}
		else {
			foreach ($this->skeleton->get('reviewing_products') as $rp) {
				if ($rp['po_review_id'] == $po_review_id && $rp['po_product_id'] == $po_product_id) return $rp;
			}
		}
	}

	public function get_reviewing_products_direct($po_review_id) {
		$reviewing_products = prepared_query::fetch('SELECT porevp.id as po_review_product_id, porevp.po_review_id, porevp.psc_stock_id as stock_id, porevp.pop_id as po_product_id, porevp.qty_received, porevp.notes, porevp.hold_disposition_id, porevp.created_on, porevp.modified_on, porevp.weight, porevp.status, pop.unexpected FROM purchase_order_review_product porevp JOIN purchase_order_products pop ON porevp.pop_id = pop.id WHERE pop.purchase_order_id = :purchase_order_id AND porevp.po_review_id = :po_review_id', cardinality::SET, [':purchase_order_id' => $this->id(), ':po_review_id' => $po_review_id]);

		$reviewing_products = self::normalize_reviewing_products($reviewing_products);

		return $reviewing_products;
	}

	public function get_open_reviewing_products($po_product_id=NULL) {
		$reviewing_products = [];

		if ($open_reviews = $this->get_open_reviews()) {
			foreach ($open_reviews as $review) {
				if ($this->has_reviewing_products($review['po_review_id'])) $reviewing_products[$review['po_review_id']] = $this->get_reviewing_products($review['po_review_id'], $po_product_id);
			}
		}

		return $reviewing_products;
	}

	public function get_open_reviewing_products_direct($po_product_id) {
		$reviewing_products = prepared_query::fetch('SELECT porevp.id as po_review_product_id, porevp.po_review_id, porevp.psc_stock_id as stock_id, porevp.pop_id as po_product_id, porevp.qty_received, porevp.notes, porevp.hold_disposition_id, porevp.created_on, porevp.modified_on, porevp.weight, porevp.status, pop.unexpected FROM purchase_order_review_product porevp JOIN purchase_order_review por ON porevp.po_review_id = por.id AND por.status IN (:needs_review, :ready) JOIN purchase_order_products pop ON porevp.pop_id = pop.id WHERE pop.purchase_order_id = :purchase_order_id AND porevp.pop_id = :po_product_id', cardinality::SET, [':purchase_order_id' => $this->id(), ':needs_review' => self::$review_statuses['NEEDS_REVIEW'], ':ready' => self::$review_statuses['READY'], ':po_product_id' => $po_product_id]);

		$reviewing_products = self::normalize_reviewing_products($reviewing_products);

		$reviews = [];

		foreach ($reviewing_products as $rp) {
			$reviews[$rp['po_review_id']] = $rp;
		}

		return $reviews;
	}

	public function has_allocations() {
		if (!$this->skeleton->built('allocations')) $this->build_allocations();
		return $this->skeleton->has('allocations');
	}

	public function get_allocations() {
		if (!$this->has_allocations()) return [];
		return $this->skeleton->get('allocations');
	}

	public function has_tracking_numbers() {
		if (!$this->skeleton->built('tracking_numbers')) $this->build_tracking_numbers();
		return $this->skeleton->has('tracking_numbers');
	}

	public function get_tracking_numbers($key=NULL) {
		if (!$this->has_tracking_numbers()) return [];
		if (empty($key)) return $this->skeleton->get('tracking_numbers');
		else {
			foreach ($this->skeleton->get('tracking_numbers') as $tracking) {
				if ($key == $tracking['po_tracking_id']) return $tracking;
			}
			return NULL;
		}
	}

	public function get_total_units() {
		$total_units = 0;
		foreach ($this->get_products() as $product) {
			$total_units += $product['quantity'];
		}
		return $total_units;
	}

	public function get_total_product_cost() {
		$total_product_cost = 0;
		foreach ($this->get_products() as $product) {
			$total_product_cost += $product['cost'] * $product['quantity'];
		}
		return $total_product_cost;
	}

	public function get_total_received_cost() {
		$total_received_cost = 0;
		foreach ($this->get_products() as $product) {
			$total_received_cost += $product['cost'] * $product['qty_received'];
		}
		return $total_received_cost;
	}

	public function get_total_remaining_cost() {
		$total_remaining_cost = 0;
		foreach ($this->get_products() as $product) {
			$total_remaining_cost += $product['cost'] * $product['qty_remaining'];
		}
		return $total_remaining_cost;
	}

	public function get_total_weight() {
		$total_weight = 0;
		foreach ($this->get_products() as $product) {
			$total_weight += $product['ipn']->get_header('stock_weight') * $product['quantity'];
		}
		return $total_weight;
	}

	public function has_additional_costs($key=NULL) {
		if (!$this->skeleton->built('additional_costs')) $this->build_additional_costs();
		if (empty($key)) return $this->skeleton->has('additional_costs');
		else {
			foreach ($this->skeleton->get('additional_costs') as $cost) {
				if ($key == $cost['purchase_order_additional_cost_id']) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	public function get_additional_costs($key=NULL) {
		if (!$this->has_additional_costs()) return [];
		if (empty($key)) return $this->skeleton->get('additional_costs');
		else {
			foreach ($this->skeleton->get('additional_costs') as $cost) {
				if ($key == $cost['purchase_order_additional_cost_id']) {
					return $cost;
				}
			}
			unset($cost);
			return NULL;
		}
	}

	public function is_open() {
		return (int) $this->get_header('po_status') < 3;
	}

	public function is_closed() {
		return !$this->is_open();
	}

	public function can_be_closed() {
		if ($this->is_closed()) return FALSE;

		foreach ($this->get_products() as $product) {
			if ($product['qty_remaining'] > 0) return FALSE;
		}

		return TRUE;
	}

	public static function get_all_pos_with_open_reviews() {
		$pos = [];

		if ($po_ids = self::fetch('pos_with_open_reviews', [':completed' => self::$review_statuses['COMPLETED'], ':received' => self::$po_statuses['RECEIVED'], ':voided' => self::$po_statuses['VOIDED']])) {
			foreach ($po_ids as $po_id) {
				$pos[] = new self($po_id);
			}
		}

		return $pos;
	}

	/*-------------------------------
	// modify data
	-------------------------------*/

	public static function create(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			// this is mostly just stubbed out, building it in advance of using it, so there may be more detail needed here

			$params = new ezparams($data['header']);
			$purchase_order_id = prepared_query::insert('INSERT INTO purchase_orders ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', $params->query_vals(NULL, TRUE));

			$purchase_order = new self($purchase_order_id);

			if (!empty($data['products'])) $purchase_order->create_products($data['products']);
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKSPurchaseOrderException('Failed to create purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return $purchase_order;
	}

	public function lock_receiving() {
		$this->update(['receiving_lock' => 1]);
	}

	public function unlock_receiving() {
		$this->update(['receiving_lock' => 0]);
	}

	public function update(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$params = new ezparams($data);
			prepared_query::execute('UPDATE purchase_orders SET '.$params->update_cols(TRUE).' WHERE id = :purchase_order_id', $params->query_vals(['purchase_order_id' => $this->id()], TRUE));

			$this->skeleton->rebuild();
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error updating purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function remove() {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			throw new CKPurchaseOrderException('Currently we do not allow deleting an existing purchase order.');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error removing purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function force_close() {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (!$this->can_be_closed()) throw new CKPurchaseOrderException('This PO cannot be force closed.  Please ensure it\'s open and there are no remaining items to be received.');

			$this->update(['status' => 3]);

			$this->skeleton->rebuild('header');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error forcing purchase order closed: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function create_products(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			foreach ($data as $prod) {
				$prod['purchase_order_id'] = $this->id();

				$params = new ezparams($prod);
				$purchase_order_products_id = prepared_query::insert('INSERT INTO purchase_order_products ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', $params->query_vals(NULL, TRUE));
			}

			$this->skeleton->rebuild('products');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to add product to purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function update_product($purchase_order_product_id, Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['purchase_order_id'] = $this->id();

			$params = new ezparams($data);
			prepared_query::execute('UPDATE purchase_order_products SET '.$params->update_cols(TRUE).' WHERE id = :purchase_order_product_id', $params->query_vals(['purchase_order_product_id' => $purchase_order_product_id], TRUE));

			$this->skeleton->rebuild('products');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error updating product on purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function remove_product($purchase_order_product_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('DELETE FROM purchase_order_products WHERE id = :purchase_order_product_id', [':purchase_order_product_id' => $purchase_order_product_id]);

			$this->skeleton->rebuild('products');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error removing product from purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function create_note(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['purchase_order_id'] = $this->id();
			if (empty($data['purchase_order_note_user'])) $data['purchase_order_note_user'] = !empty($_SESSION['login_id'])?$_SESSION['login_id']:ck_sales_order::$solutionsteam_id;

			$params = new ezparams($data);
			$po_note_id = prepared_query::insert('INSERT INTO purchase_order_notes ('.$params->insert_cols().', purchase_order_note_created) VALUES ('.$params->insert_params(TRUE).', NOW())', $params->query_vals(NULL, TRUE));

			// $this->send_new_po_note_notification($po_note_id);

			$this->skeleton->rebuild('notes');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('There was an error adding a note to this purchase order.');
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	/*public function update_note($purchase_order_note_id, Array $data) {
	}

	public function remove_note($purchase_order_note_id) {
	}*/

	public function create_additional_cost(array $additional_cost_details) {
		try {
			self::execute('create_additional_cost', [':purchase_order_id' => $this->id(), ':description' => $additional_cost_details['description'], ':amount' => $additional_cost_details['amount']]);

			$this->skeleton->rebuild('additional_costs');

			return TRUE;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function update_additional_cost(array $additional_cost_details) {
		try {
			self::execute('update_additional_cost', [
				':purchase_order_additional_cost_id' => $additional_cost_details['purchase_order_additional_cost_id'],
				':description' => $additional_cost_details['description'],
				':amount' => $additional_cost_details['amount']
			]);

			$this->skeleton->rebuild('additional_costs');

			return TRUE;
		}
		catch(Exception $e) {
			throw $e;
		}
	}

	public function remove_additional_cost($purchaes_order_additional_cost_id) {
		try {
			self::execute('delete_additional_cost', [':purchase_order_additional_cost_id' => $purchaes_order_additional_cost_id]);

			$this->skeleton->rebuild('additional_costs');
			return TRUE;
		}
		catch(Exception $e) {
			throw $e;
		}
	}

	public function spread_additional_cost($additional_cost_id) {
		try {
			$savepoint_id = prepared_query::transaction_begin();

			if ($this->has_products() && $this->has_additional_costs()) {
				//sum up the total quantity of purchase order products
				$total_item_quantity = 0;
				foreach ($this->get_products() as $product) {
					$total_item_quantity += $product['quantity'];
				}

				//calculate the portioned amount by dividing the additional cost by the total quantity of items in the purchase order
				$portioned_amount = $this->get_additional_costs($additional_cost_id)['amount']/$total_item_quantity;

				foreach ($this->get_products() as $product) {

					prepared_query::execute('UPDATE purchase_order_additional_costs SET cost_spread = 1 WHERE purchase_order_additional_cost_id = :purchase_order_additional_cost_id', [':purchase_order_additional_cost_id' => $additional_cost_id]);

					prepared_query::execute('INSERT INTO purchase_order_additional_costs_to_purchase_order_products (purchase_order_product_id, purchase_order_additional_cost_id, additional_cost_portion) VALUES (:purchase_order_product_id, :purchase_order_additional_cost_id, :additional_cost_portion)', [':purchase_order_product_id' => $product['po_product_id'], ':purchase_order_additional_cost_id' => $additional_cost_id, ':additional_cost_portion' => $portioned_amount]);

					prepared_query::execute('UPDATE purchase_order_products SET cost = (cost + :new_cost), additional_cost = 1 WHERE id = :purchase_order_product_id', [':purchase_order_product_id' => $product['po_product_id'], ':new_cost' => $portioned_amount]);
				}
			}

			$this->skeleton->rebuild('additional_costs');
			$this->skeleton->rebuild('products');
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function unspread_additional_cost($additional_cost_id) {
		try {
			$savepoint_id = prepared_query::transaction_begin();

			if ($this->has_products() && $this->has_additional_costs()) {
				$additional_cost = $this->get_additional_costs($additional_cost_id);

				$cost_portions = prepared_query::fetch('SELECT additional_cost_portion, purchase_order_product_id FROM purchase_order_additional_costs_to_purchase_order_products WHERE purchase_order_additional_cost_id = :purchase_order_additional_cost_id', cardinality::SET, [':purchase_order_additional_cost_id' => $additional_cost_id]);

				prepared_query::execute('DELETE FROM purchase_order_additional_costs_to_purchase_order_products WHERE purchase_order_additional_cost_id = :purchase_order_additional_cost_id', [':purchase_order_additional_cost_id' => $additional_cost_id]);

				prepared_query::execute('UPDATE purchase_order_additional_costs SET cost_spread = 0 WHERE purchase_order_additional_cost_id = :purchase_order_additional_cost_id', [':purchase_order_additional_cost_id' => $additional_cost_id]);


				foreach ($cost_portions as $cost_portion) {
					prepared_query::execute('UPDATE purchase_order_products pop LEFT JOIN purchase_order_additional_costs_to_purchase_order_products poactpop ON pop.id = poactpop.purchase_order_product_id SET pop.cost = (pop.cost - :cost_portion), pop.additional_cost = (CASE WHEN poactpop.purchase_order_additional_costs_to_purchase_order_products_id THEN 1 ELSE 0 END) WHERE pop.id = :purchase_order_product_id', [':cost_portion' => $cost_portion['additional_cost_portion'], ':purchase_order_product_id' => $cost_portion['purchase_order_product_id']]);
				}
			}

			$this->skeleton->rebuild('additional_costs');
			$this->skeleton->rebuild('products');
		}
		catch(Exception $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function create_tracking_number(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['po_id'] = $this->id();

			if (!empty($data['status'])) { // weird capitalization
				$data['STATUS'] = $data['status'];
				unset($data['status']);
			}

			$params = new ezparams($data);
			$tracking_id = prepared_query::insert('INSERT INTO purchase_order_tracking ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', $params->query_vals(NULL, TRUE));

			$this->skeleton->rebuild('tracking_numbers');

		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to create new tracking number.');
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return $tracking_id;
	}

	public function update_tracking_number($purchase_order_tracking_id, Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['purchase_order_id'] = $this->id();

			if (!empty($data['status'])) { // weird capitalization
				$data['STATUS'] = $data['status'];
				unset($data['status']);
			}

			$params = new ezparams($data);
			prepared_query::execute('UPDATE purchase_order_tracking SET '.$params->update_cols(TRUE).' WHERE id = :purchase_order_tracking_id', $params->query_vals(['purchase_order_tracking_id' => $purchase_order_tracking_id], TRUE));

			$this->skeleton->rebuild('tracking_numbers');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error updating tracking number on purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function remove_tracking_number($purchase_order_tracking_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('DELETE FROM purchase_order_tracking WHERE id = :purchase_order_tracking_id', [':purchase_order_tracking_id' => $purchase_order_tracking_id]);

			$this->skeleton->rebuild('tracking_numbers');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error removing tracking number from purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function create_review(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['po_number'] = $this->get_header('purchase_order_number');

			if (empty($data['status'])) $data['status'] = self::$review_statuses['NEEDS_REVIEW'];
			if (empty($data['creator'])) $data['creator'] = !empty($_SESSION['login_id'])?$_SESSION['login_id']:ck_sales_order::$solutionsteam_id;

			if (!empty($data['tracking_number'])) {
				foreach ($this->get_tracking_numbers() as $tn) {
					if ($data['tracking_number'] == $tn['tracking_number']) {
						$data['purchase_order_tracking_id'] = $tn['po_tracking_id'];
						break;
					}
				}

				if (empty($data['purchase_order_tracking_id'])) throw new CKPurchaseOrderException('Tracking Number '.$data['tracking_number'].' could not be found on this PO.');

				unset($data['tracking_number']);
			}

			if (isset($data['purchase_order_tracking_id'])) {
				if ($reviews = $this->get_open_reviews_direct()) {
					foreach ($reviews as $review) {
						if ($data['purchase_order_tracking_id'] == $review['purchase_order_tracking_id']) throw new CKPurchaseOrderException('An open review already exists for this tracking number; please use that one.');
					}
				}
			}
			elseif (!isset($data['purchase_order_tracking_id']) || $data['purchase_order_tracking_id'] === '') throw new CKPurchaseOrderException('You must select a tracking number to create this receiving session for.');

			$params = new ezparams($data);
			$review_id = prepared_query::insert('INSERT INTO purchase_order_review ('.$params->insert_cols().', created_on, modified_on) VALUES ('.$params->insert_params(TRUE).', NOW(), NOW())', $params->query_vals(NULL, TRUE));

			$this->skeleton->rebuild('reviews');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to create new receiving session.');
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return $review_id;
	}

	public function update_review($purchase_order_review_id, Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data['po_number'] = $this->get_header('purchase_order_number');

			if (!empty($data['tracking_number'])) {
				foreach ($this->get_tracking_numbers() as $tn) {
					if ($data['tracking_number'] == $tn['tracking_number']) {
						$data['purchase_order_tracking_id'] = $tn['po_tracking_id'];
						break;
					}
				}

				if (empty($data['purchase_order_tracking_id'])) throw new CKPurchaseOrderException('Tracking Number '.$data['tracking_number'].' could not be found on this PO.');

				unset($data['tracking_number']);
			}

			if (!empty($data['purchase_order_tracking_id'])) {
				foreach ($this->get_open_reviews() as $review) {
					if ($data['purchase_order_tracking_id'] == $review['purchase_order_tracking_id']) throw new CKPurchaseOrderException('An open review already exists for this tracking number; please use that one.');
				}
			}

			$params = new ezparams($data);
			prepared_query::execute('UPDATE purchase_order_review SET '.$params->update_cols(TRUE).', modified_on = NOW() WHERE id = :purchase_order_review_id', $params->query_vals(['purchase_order_review_id' => $purchase_order_review_id], TRUE));

			$this->skeleton->rebuild('reviews');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error updating receiving session on purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function remove_review($purchase_order_review_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('DELETE FROM purchase_order_review_product WHERE po_review_id = :purchase_order_review_id', [':purchase_order_review_id' => $purchase_order_review_id]);
			prepared_query::execute('DELETE FROM purchase_order_review WHERE po_number = :purchase_order_number AND id = :purchase_order_review_id', [':purchase_order_number' => $this->get_header('purchase_order_number'), ':purchase_order_review_id' => $purchase_order_review_id]);
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error removing receiving session on purchase order: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function lock_review($purchase_order_review_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$this->update_review($purchase_order_review_id, ['lock_for_review' => 1, 'status' => self::$review_statuses['NEEDS_REVIEW']]);
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error locking receiving session from the review process: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function review_review($purchase_order_review_id, $products) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			foreach ($products as $po_product_id => $details) {
				$review_product = $this->get_reviewing_products($purchase_order_review_id, $po_product_id);

				if (!CK\fn::check_flag(@$details['approve'])) continue;
				if ($review_product['status'] != self::$review_product_statuses['NEW_PRODUCT']) continue;

				$cost = CK\text::demonetize($details['cost']);
				$freebie = CK\fn::check_flag(@$details['freebie'])?1:0;

				if (!($freebie xor $cost > 0)) throw new CKPurchaseOrderException('Non Freebie items must have a cost; Freebie items must *not* have a cost.');

				$this->update_product($po_product_id, ['unexpected' => 0, 'cost' => $cost, 'unexpected_freebie' => $freebie]);
				$this->update_review_product($review_product['po_review_product_id'], ['pop_id' => $po_product_id, 'status' => self::$review_product_statuses['NORMAL']]);

				if ($review_product['ipn']->is('serialized') && !empty($review_product['serials'])) {
					foreach ($review_product['serials'] as $srl) {
						$current_history = $srl->get_current_history();
						if (!empty($current_history) && $po_product_id == $current_history['purchase_order_products_id']) $srl->update_history_record($current_history['serial_history_id'], ['cost' => $cost]);
					}
				}
			}

			$this->update_review($purcahse_order_review_id, ['reviewer' => $_SESSION['login_id']]);

			$this->unlock_review($purchase_order_review_id);

			$this->skeleton->rebuild(); // there's enough that needs to be rebuilt with this, let's just start over from scratch
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to finish review.');
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function unlock_review($purchase_order_review_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$this->update_review($purchase_order_review_id, ['lock_for_review' => 0, 'status' => self::$review_statuses['READY']]);
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error unlocking receiving session from the review process: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function receive_review($review) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$has_expected_products = $has_unexpected_products = FALSE;
			$review_products = $this->get_reviewing_products_direct($review['po_review_id']);
			foreach ($review_products as $review_product) {
				if ($review_product['unexpected']) $has_unexpected_products = TRUE;
				else $has_expected_products = TRUE;
			}

			if ($has_unexpected_products && !$has_expected_products) {
				// if we only have unexpected products, we can just lock this for review and then finish
				$this->lock_review($review['po_review_id']);
			}
			// if we don't *only* have unexpected products, we should have at minimum *some* expected products, but test anyway just to be explicit
			elseif ($has_expected_products) {
				if ($has_unexpected_products) $unexpected_products = [];

				$po_receiving_session_id = $this->create_receiving_session_from_review($review);

				foreach ($review_products as $review_product) {
					if ($review_product['unexpected']) {
						$unexpected_products[] = $review_product;
						continue;
					}

					$this->create_receiving_session_product($po_receiving_session_id, $review_product);
				}

				$this->update_review($review['po_review_id'], ['status' => self::$review_statuses['COMPLETED']]);

				// move any unexpected products off of this review session so they can be handled separately
				if (!empty($unexpected_products)) {
					$po_review_id = $this->create_review(['purchase_order_tracking_id' => $review['purchase_order_tracking_id']]);
					foreach ($unexpected_products as $unexpected_product) {
						prepared_query::execute('UPDATE purchase_order_review_product SET po_review_id = :po_review_id WHERE id = :po_review_product_id', [':po_review_id' => $po_review_id, ':po_review_product_id' => $unexpected_product['po_review_product_id']]);
					}
					$this->lock_review($po_review_id);
				}

				// we only get this far if we've received *something*, so we're either fully received or partially received

				// figure out the status of the order, based on the items attached to it
				$po_status = self::$po_statuses['RECEIVED'];

				if (!empty($this->get_open_reviewing_products())) $po_status = self::$po_statuses['PARTIALLY_RECEIVED'];
				else {
					foreach ($this->get_products() as $product) {
						// if we've got any left to receive of this item, we're only partially received
						if ($product['qty_remaining'] > 0) $po_status = self::$po_statuses['PARTIALLY_RECEIVED'];
					}
				}

				//throw new CKPurchaseOrderException('just kill it 4');

				$this->update(['status' => $po_status]);

				$this->send_receipt_notification($this->get_reviews($review['po_review_id']), $this->get_receiving_sessions($po_receiving_session_id));

				// now we do the lookup against the PO2OA table for orders that can be moved to 'ready to pick' and move them - for each of these orders we send an email to purchasing
				$salable_quantities = []; // this is passed around from order to order by reference, so we can build up a total
				/* maybe rewrite this loop to just grab any affected orders, since we're looking up all of the items on these orders, not just the ones that we just received ? */

				// Grab all orders that have items from this PO receipt allocated to them and have an order status of "Backorder"
				// Order by oldest order allocation first
				$orders = [];
				if ($this->has_allocations()) {
					foreach ($this->get_allocations() as $alloc) {
						if (!in_array($alloc['orders_id'], $orders)) {
							$orders[] = $alloc['orders_id'];

							$order = new ck_sales_order($alloc['orders_id']);
							if ($order->get_header('orders_status') != ck_sales_order::STATUS_BACKORDER) continue;

							list($ready_to_pick, $fail_reasons) = $order->recv_po_is_ready_to_ship($salable_quantities); // pass $salable_quantities by ref

							if (!empty($ready_to_pick)) {
								$order->update_order_status(['orders_status_id' => ck_sales_order::STATUS_RTP]);
								$order->create_admin_note(['orders_note_text' => 'Order status changed to Ready To Pick automatically by purchasing system.']);
							}
							else {
								$notes = [];
								foreach ($fail_reasons as $reason) {
									switch ($reason) {
										case 'pmt':
											$notes[] = 'still missing payment';
											break;
										case 'prod':
											$notes[] = 'some product is either still missing or overallocated';
											break;
										default:
											$notes[] = 'unhandled incomplete reason ['.$reason.']';
											break;
									}
								}
								$notes = implode('; ', $notes);

								$order->update_order_status(['orders_status_id' => ck_sales_order::STATUS_CST, 'orders_sub_status_id' => ck_sales_order::$sub_status_map['CST']['Uncat']]);
								$order->create_admin_note(['orders_note_text' => 'Order status changed to Uncategorized automatically by purchasing system ('.$notes.')']);
							}
						}
					}
				}

				$this->remove_received_allocations($po_receiving_session_id);

				$this->skeleton->rebuild(); // there's enough that needs to be rebuilt with this, let's just start over from scratch
			}
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error receiving the selected session: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return TRUE;
	}

	public function adjust_review_qty($purchase_order_review_id, Array $data, Array $serial=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if ($purchase_order_review_id === '') throw new CKPurchaseOrderException('Receiving: Receiving Session not set.');
			if (empty($data['pop_id'])) throw new CKPurchaseOrderException('Receiving: PO Product ID not set.');

			if (empty($data['qty_received'])) throw new CKPurchaseOrderException('Receiving: You must enter a qty to receive.');

			$po_product = $this->get_product_direct($data['pop_id']);

			// I tried to allow updating a serials history record below, but that's not set up correctly - so, if it exists on a current receiving session, I'm going to short circuit that here
			if ($po_product['ipn']->is('serialized') && !empty($serial)) {
				foreach ($serial['serials'] as $serial_number) {
					$serial_number = str_replace("'", '', trim($serial_number));

					if ($srl = ck_serial::get_serial_by_serial($serial_number)) {
						$current_history = $srl->get_current_history();
						if (!empty($current_history) && $data['pop_id'] == $current_history['purchase_order_products_id']) {
							throw new CKPurchaseOrderException('Receiving: the submitted serial # ['.$serial_number.'] has already been received for this PO.');
						}
					}
				}
			}

			// unfortunately we can't just insert and deal with duplicate indexes because there are unique relationships that are duplicated in the way back history keeping us from creating the unique index
			$rps = $this->get_reviewing_products_direct($purchase_order_review_id);
			$found = FALSE;
			foreach ($rps as $rp) {
				if ($data['pop_id'] == $rp['po_product_id']) {
					$found = TRUE;
					break; // $rp has the info we want
				}
			}
			if ($found) {
				if ($po_product['qty_remaining'] - $po_product['qty_reviewing'] + $rp['qty_received'] - $data['qty_received'] < 0) throw new CKPurchaseOrderException('You may not receive more of this item than is listed on the PO. Please edit the PO or change the amount you are receiving.');

				if ($po_product['ipn']->is('serialized')) $data['qty_received'] += $rp['qty_received'];
				$this->update_review_product($rp['po_review_product_id'], $data, $serial);
			}
			else {
				if ($po_product['qty_remaining'] - $po_product['qty_reviewing'] - $data['qty_received'] < 0) throw new CKPurchaseOrderException('You may not receive more of this item than is listed on the PO. Please edit the PO or change the amount you are receiving.');

				$this->create_review_product($purchase_order_review_id, $data, $serial);
			}
		}
		catch (CKMasterArchetypeException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to adjust qty of IPN on receiving session: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function delete_review_serial($serial_history_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$serial = ck_serial::get_serial_by_history_id($serial_history_id);

			$history = $serial->get_history($serial_history_id);

			$serial->remove_history_record($serial_history_id);
			if (!$serial->has_history()) $serial->remove();

			self::execute('update_received_session_serials', [':porp_id' => $history['purchase_order_received_products_id']]);
			self::execute('delete_orphaned_review_product', [':porp_id' => $history['purchase_order_received_products_id']]);

			$this->skeleton->rebuild(); // there's enough that needs to be rebuilt with this, let's just start over from scratch
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to remove serial from receiving session.');
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	private function create_review_product($purchase_order_review_id, Array $data, Array $serial=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$po_product = $this->get_product_direct($data['pop_id']);

			if ($po_product['ipn']->is('serialized') && empty($serial)) throw new CKPurchaseOrderException('No serial data provided with serialized IPN receipt.');

			$data['po_review_id'] = $purchase_order_review_id;

			$data['psc_stock_id'] = $po_product['ipn']->id();
			$data['notes'] = '';
			$data['status'] = $po_product['unexpected']?self::$review_product_statuses['NEW_PRODUCT']:self::$review_product_statuses['NORMAL'];

			$params = new ezparams($data);
			$po_review_product_id = prepared_query::insert('INSERT INTO purchase_order_review_product ('.$params->insert_cols().', created_on, modified_on) VALUES ('.$params->insert_params(TRUE).', NOW(), NOW())', $params->query_vals(NULL, TRUE));

			if ($po_product['ipn']->is('serialized')) {
				$serial['details']['po_number'] = $this->get_header('purchase_order_number');
				$serial['details']['pop_id'] = $data['pop_id'];
				$serial['details']['porp_id'] = $po_review_product_id;
				$serial['details']['cost'] = $po_product['cost'];

				// even though we're looping through this, we're only really sending one at a time
				foreach ($serial['serials'] as $serial_number) {
					$serial_number = str_replace("'", '', trim($serial_number));

					// this will automatically detect and use an existing serial # if it matches, so long as the IPN matches
					$srl = ck_serial::create([
						'header' => [
							'serial' => $serial_number,
							'ipn' => $po_product['ipn']->id(),
							'status' => ck_serial::$statuses['RECEIVING']
						],
						'history' => $serial['details']
					]);

					if (!empty($data['hold_disposition_id'])) prepared_query::execute('INSERT INTO inventory_hold (stock_id, quantity, reason_id, serial_id, date, notes) VALUES (:stock_id, 1, :hold_disposition_id, :serial_id, NOW(), :hold_notes)', [':stock_id' => $po_product['ipn']->id(), ':hold_disposition_id' => $data['hold_disposition_id'], ':serial_id' => $srl->id(), ':hold_notes' => $serial['hold_notes']]);
				}
			}

			$this->skeleton->rebuild('products');
			$this->skeleton->rebuild('reviewing_products');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to create new receiving session product: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return $po_review_product_id;
	}

	private function update_review_product($purchase_order_review_product_id, Array $data, Array $serial=NULL) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			// since this is a private method, we can guarantee pop_id will be filled even though it's not used in the actual update query
			$po_product = $this->get_product_direct($data['pop_id']);

			if ($po_product['ipn']->is('serialized') && !empty($serial)) {
				$serial['details']['po_number'] = $this->get_header('purchase_order_number');
				$serial['details']['pop_id'] = $po_product['po_product_id'];
				$serial['details']['porp_id'] = $purchase_order_review_product_id;
				$serial['details']['cost'] = $po_product['cost'];

				foreach ($serial['serials'] as $serial_number) {
					$serial_number = str_replace("'", '', trim($serial_number));

					if ($srl = ck_serial::get_serial_by_serial($serial_number)) {
						$srl->update_serial_status(ck_serial::$statuses['RECEIVING']);
						$current_history = $srl->get_current_history();
						if (!empty($current_history) && $serial['details']['pop_id'] == $current_history['purchase_order_products_id']) $srl->update_history_record($current_history['serial_history_id'], $serial['details']);
						else $srl->create_history_record($serial['details']);
					}
					else {
						$srl = ck_serial::create([
							'header' => [
								'serial' => $serial_number,
								'ipn' => $po_product['ipn']->id(),
								'status' => ck_serial::$statuses['RECEIVING']
							],
							'history' => $serial['details']
						]);
					}

					if (!empty($data['hold_disposition_id'])) {
						if (prepared_query::fetch('SELECT id FROM inventory_hold WHERE serial_id = :serial_id', cardinality::SINGLE, [':serial_id' => $srl->id()])) prepared_query::execute('UPDATE inventory_hold SET reason_id = :hold_disposition_id, notes = :hold_notes WHERE serial_id = :serial_id', [':hold_disposition_id' => $data['hold_disposition_id'], ':serial_id' => $srl->id(), ':hold_notes' => $serial['hold_notes']]);
						else prepared_query::execute('INSERT INTO inventory_hold (stock_id, quantity, reason_id, serial_id, date, notes) VALUES (:stock_id, 1, :hold_disposition_id, :serial_id, NOW(), :hold_notes)', [':stock_id' => $po_product['ipn']->id(), ':hold_disposition_id' => $data['hold_disposition_id'], ':serial_id' => $srl->id(), ':hold_notes' => $serial['hold_notes']]);
					}
					else prepared_query::execute('DELETE FROM inventory_hold WHERE serial_id = :serial_id', [':serial_id' => $srl->id()]);
				}
			}

			// whitelist fields
			$data = self::filter_fields($data, ['qty_received', 'notes', 'hold_disposition_id', 'weight', 'status']);
			$params = new ezparams($data);
			prepared_query::execute('UPDATE purchase_order_review_product SET '.$params->update_cols(TRUE).', modified_on = NOW() WHERE id = :purchase_order_review_product_id', $params->query_vals(['purchase_order_review_product_id' => $purchase_order_review_product_id], TRUE));

			self::execute('delete_orphaned_review_product', [':porp_id' => $purchase_order_review_product_id]);

			$this->skeleton->rebuild('products');
			$this->skeleton->rebuild('reviewing_products');
		}
		catch (CKMasterArchetypeException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to update receiving session product.');
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function remove_review_product($po_review_product) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('DELETE FROM purchase_order_review_product WHERE id = :po_review_product_id', [':po_review_product_id' => $po_review_product['po_review_product_id']]);

			if ($po_review_product['ipn']->is('serialized')) {
				foreach ($po_review_product['serials'] as $srl) {
					if ($srl->has_history()) {
						foreach ($srl->get_history() as $sh) {
							if ($sh['purchase_order_received_products_id'] == $po_review_product['po_review_product_id']) $this->delete_review_serial($sh['serial_history_id']);
						}
					}
				}
			}

			$this->skeleton->rebuild('products');
			$this->skeleton->rebuild('reviewing_products');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to remove receiving session product.');
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	private function create_receiving_session_from_review($review) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$data = ['purchase_order_id' => $this->id(), 'purchase_order_tracking_id' => $review['purchase_order_tracking_id']];
			$data['receiver'] = !empty($_SESSION['login_id'])?$_SESSION['login_id']:ck_admin::$solutionsteam_id;

			$params = new ezparams($data);
			$receiving_session_id = prepared_query::insert('INSERT INTO purchase_order_receiving_sessions ('.$params->insert_cols().', `date`) VALUES ('.$params->insert_params(TRUE).', NOW())', $params->query_vals(NULL, TRUE));

			$this->skeleton->rebuild('receiving_sessions');
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Failed to receive receiving session: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return $receiving_session_id;
	}

	/*public function update_receiving_session($purchase_order_receiving_session_id, Array $data) {
	}

	public function remove_receiving_session($purchase_order_receiving_session_id) {
	}*/

	private function create_receiving_session_product($purchase_order_receiving_session_id, Array $review_product) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$po_product = $this->get_products($review_product['po_product_id']);

			$data = [];
			$data['receiving_session_id'] = $purchase_order_receiving_session_id;
			$data['purchase_order_product_id'] = $review_product['po_product_id'];
			$data['quantity_received'] = $review_product['qty_received'];
			$data['quantity_remaining'] = $po_product['qty_remaining'] - $review_product['qty_received'];
			$data['paid'] = $this->is_prepaid()?1:0;

			$params = new ezparams($data);
			prepared_query::execute('INSERT INTO purchase_order_received_products ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', $params->query_vals(NULL, TRUE));

			$review_product['ipn']->receive_qty($purchase_order_receiving_session_id, $review_product, $po_product['cost']);

			$this->skeleton->rebuild('products');
			$this->skeleton->rebuild('received_products');

			// this may be obsolete - it's almost certainly poorly used
			if ($data['quantity_remaining'] <= 0) {
				prepared_query::execute('UPDATE orders_products op LEFT JOIN products p ON op.products_id = p.products_id SET op.po_waiting = NULL WHERE op.po_waiting = :po_id AND p.stock_id = :stock_id', [':po_id' => $this->id(), ':stock_id' => $review_product['stock_id']]);
			}
		}
		catch (CKPurchaseOrderException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPurchaseOrderException('Error receiving product: '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	/*public function update_receiving_session_product($purchase_order_receiving_session_product_id, Array $data) {
	}

	public function remove_receiving_session_product($purchase_order_receiving_session_product_id) {
	}*/

	public function add_unexpected_ipn($details) {
		$details[':purchase_order_id'] = $this->id();
		$details[':unexpected'] = $details[':unexpected_freebie']==1?0:1;
		$details[':cost'] = 0;
		$ipn = new ck_ipn2($details[':ipn_id']);
		$details[':description'] = $ipn->get_header('stock_description');
		self::execute('add_ipn', $details);
		if ($details[':quantity'] > 0) self::execute('update_on_order', [':stock_id' => $details[':ipn_id'], 'on_order' => $details[':quantity']]);
		$this->skeleton->rebuild('products');
	}

	public function remove_received_allocations($receiving_session_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if ($receiving_items = self::fetch('receiving_session_qtys', [':receiving_session_id' => $receiving_session_id])) {
				foreach ($receiving_items as $item) {
					if (!($allocations = self::fetch('receiving_session_allocations', [':purchase_order_product_id' => $item['purchase_order_product_id']]))) continue;

					$remaining_received_qty = $item['quantity_received'];

					$serial_ids = prepared_query::fetch('SELECT serial_id FROM serials_history WHERE pors_id = :receiving_session_id AND pop_id = :po_product_id', cardinality::COLUMN, [':receiving_session_id' => $item['receiving_session_id'], ':po_product_id' => $item['purchase_order_product_id']]);

					foreach ($allocations as $allocation) {
						$adjust_qty = max(min($remaining_received_qty, $allocation['quantity']), 0);
						if ($adjust_qty == 0) break;

						$remaining_received_qty -= $adjust_qty;
						if ($adjust_qty >= $allocation['quantity']) {
							self::execute('receiving_session_remove', [':potoa_id' => $allocation['potoa_id']]);
							$reserve_qty = $allocation['quantity'];
						}
						else {
							self::execute('receiving_session_reduce', [':quantity' => $adjust_qty, ':potoa_id' => $allocation['potoa_id']]);
							$reserve_qty = $adjust_qty;
						}

						foreach ($serial_ids as $serial_id) {
							if ($reserve_qty <= 0) break;

							$serial = new ck_serial($serial_id);
							if ($serial->has_reservation()) continue;

							$serial->reserve($allocation['orders_products_id']);
							$reserve_qty--;
						}
					}
				}
			}
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public static function update_tracking_number_cost(Array $package_detail, Array $shipment_detail) {
		$savepoint_id = prepared_query::transaction_begin();
		try {

			$ck_address = api_ups::$local_origin;
			$address_type = new ck_address_type();
			$address_type->load('header', $ck_address);
			$ck_address = new ck_address2(NULL, $address_type);

			$origin_address = [
				'company_name' => '',
				'address1' => '',
				'address2' => '',
				'postcode' => $shipment_detail['origin_zip_code'],
				'city' => '',
				'state' => $shipment_detail['origin_state'],
				'zone_id' => '',
				'countries_id' => 223,
				'country' => 'United States',
				'countries_iso_code_2' => 'US',
				'countries_iso_code_3' => 'USA',
				'country_address_format_id' => 2,
				'telephone' => '',
			];

			$address_type = new ck_address_type();
			$address_type->load('header', $origin_address);
			$origin_address = new ck_address2(NULL, $address_type);

			$shipping_costs = api_ups::quote_rates($package_detail, $ck_address, $origin_address);
			$package_shipping_cost = NULL;
			foreach ($shipping_costs as $shipping_cost) {
				if ($shipping_cost['code'] == $shipment_detail['shipping_method']) $package_shipping_cost = $shipping_cost['negotiated'];
			}

			prepared_query::execute('UPDATE purchase_order_tracking SET shipping_cost = :shipping_cost WHERE id = :tracking_id', [':shipping_cost' => $package_shipping_cost, ':tracking_id' => $shipment_detail['tracking_id']]);

			return TRUE;
		}
		catch(Exception $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/

	public function send_new_po_note_notification($po_note_id) {
		try {
			$note = $this->get_notes($po_note_id);
			$administrator = $this->get_header('administrator');

			//send email about note
			$mailer = service_locator::get_mail_service();
			$mail = $mailer->create_mail();

			$mail->add_to($administrator->get_header('email_address'));
			$mail->add_to('sales@cablesandkits.com');

			$note_users = prepared_query::fetch('SELECT a.admin_email_address FROM purchase_order_notes pon JOIN admin a ON pon.purchase_order_note_user = a.admin_id WHERE pon.purchase_order_id = :po_id', cardinality::COLUMN, [':po_id' => $this->id()]);

			foreach ($note_users as $email) {
				$mail->add_to($email);
			}

			$mail->set_from('webmaster@cablesandkits.com');
			$mail->set_subject('A note was added to purchase order '.$this->id());
			$mail->set_body(nl2br('A note was added to purchase order '.$this->id().":\n\n".$note['note_text']));

			$mailer->send($mail);
		}
		catch (CKPurchaseOrderException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKPurchaseOrderException('Failed to send note notification: '.$e->getMessage());
		}
	}

	public function send_receipt_notification($review_session, $receiving_session) {
		try {
			// send the email notification to accounting and receivers
			$mailer = service_locator::get_mail_service();
			$email = $mailer->create_mail();
			$email->add_to('accounting@cablesandkits.com');

			$po_number = $this->get_header('purchase_order_number');
			$session_cost = 0;

			$hold_list = [];
			foreach ($this->get_reviewing_products($review_session['po_review_id']) as $rp) {
				$ipn = $rp['ipn']->get_header('ipn');

				if ($rp['ipn']->is('serialized')) {
					foreach ($rp['serials'] as $srl) {
						if ($srl->has_hold()) {
							if (empty($hold_list[$ipn])) $hold_list[$ipn] = [];
							$hold_list[$ipn][] = $srl;
						}
					}
				}
				else {
					if (!empty($rp['hold_disposition_id'])) {
						if (empty($hold_list[$ipn])) $hold_list[$ipn] = 0;
						$hold_list[$ipn] += $rp['qty_received'];
					}
				}
			}

			$received_list = [];
			foreach ($this->get_received_products($receiving_session['po_receiving_session_id']) as $rp) {
				// grabbing the cost of this received line for the subject
				$session_cost += $rp['quantity_received'] * $this->get_products($rp['purchase_order_product_id'])['cost'];

				$ipn = $rp['ipn']->get_header('ipn');

				$hold_qty = 0;
				if (!empty($hold_list[$ipn])) {
					if ($rp['ipn']->is('serialized')) $hold_qty = count($hold_list[$ipn]);
					else $hold_qty = $hold_list[$ipn];
				}

				if ($rp['quantity_received'] > $hold_qty) $received_list[$ipn] = $rp['quantity_received'] - $hold_qty;
			}

			require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
			require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
			require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

			$content = new ck_content;

			$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
			$cktpl->buffer = TRUE;

			$content->fqdn = 'https://'.FQDN;
			$content->media = 'https://media.cablesandkits.com';

			$content->po_id = $this->id();
			$content->po_number = $this->get_header('purchase_order_number');

			$content->receiving_session_id = $receiving_session['po_receiving_session_id'];

			$content->administrator = $this->get_header('administrator')->get_name();
			$content->owner = $this->get_header('owner')->get_name();
			$content->vendor = $this->get_header('vendor');
			$content->po_status = $this->get_header('po_status');

			if (!empty($received_list)) {
				$content->received_products = [];
				foreach ($received_list as $ipn => $quantity) {
					$content->received_products[] = ['ipn' => $ipn, 'qty' => $quantity];
				}
			}

			if (!empty($hold_list)) {
				$content->held_products = [];
				foreach ($hold_list as $ipn => $quantity) {
					if (is_array($quantity)) {
						$content->held_products[] = ['ipn' => $ipn, 'qty' => count($quantity), 'serials' => '('.array_reduce($quantity, function($list, $srl) {
							if (!empty($list)) $list .= ', ';
							$list .= $srl->get_header('serial_number');
							return $list;
						}, '').')'];
					}
					else $content->held_products[] = ['ipn' => $ipn, 'qty' => $quantity, 'serials' => NULL];
				}
			}

			$subject = 'PO '.$po_number.' has been received with a cost of $'.$session_cost;

			// now we look up the review products
			$needs_review = FALSE;
			if ($or = $this->get_open_reviews($review_session['purchase_order_tracking_id'])) {
				if ($or['lock_for_review']) {
					$needs_review = TRUE;
					$subject .= ' - PO Review Needed';
					$content->review_products = [];
					foreach ($this->get_reviewing_products($or['po_review_id']) as $rp) {
						$content->review_products[] = ['ipn' => $rp['ipn']->get_header('ipn'), 'qty' => $rp['qty_received']];
					}
				}
			}

			//now we do the purchase order notes
			if ($this->has_notes()) {
				$content->notes = [];
				foreach ($this->get_notes() as $note) {
					$content->notes[] = ['created_date' => $note['note_created']->format('m/d/Y H:i'), 'firstname' => $note['admin_firstname'], 'lastname' => $note['admin_lastname'], 'note' => $note['note_text']];
				}
			}

			$email->set_body(
				$cktpl->content(DIR_FS_CATALOG.'includes/templates/email-po-receipt.mustache.html', $content)
			);

			$subject .= ' - Payment Method: '.$this->get_header('po_terms');

			if ($this->get_header('po_status_id') != self::$po_statuses['RECEIVED'] || $this->has_notes() || $needs_review) $email->add_to($this->get_header('administrator')->get_header('email_address'));

			$email->set_from('webmaster@cablesandkits.com');
			$email->set_subject($subject);

			if (!$mailer->send($email)) {
				$to = $email->get_to();
				throw new CKPurchaseOrderException('Failed to send notice to the following email addresses: [see dev]');
			}
		}
		catch (CKPurchaseOrderException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKPurchaseOrderException('Failed to send receipt notice: '.$e->getMessage());
		}
	}

	public static function receiving_optimized($po_id) {
		if (!isset($_SESSION['receiving-optimized'])) $_SESSION['receiving-optimized'] = [];

		$__FLAG = request_flags::instance();

		if ($__FLAG['optimize-receiving']) $_SESSION['receiving-optimized'][$po_id] = TRUE;
		elseif ($__FLAG['deoptimize-receiving']) $_SESSION['receiving-optimized'][$po_id] = FALSE;

		return !empty($_SESSION['receiving-optimized'][$po_id]);
	}
}

class CKPurchaseOrderException extends CKMasterArchetypeException {
}
?>
