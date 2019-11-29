<?php
class ck_purchase_order_type extends ck_types {

	protected static $queries = [
		'po_statuses' => [
			'qry' => 'SELECT id as po_status_id, text as po_status FROM purchase_order_status ORDER BY id ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],
		'po_terms' => [
			'qry' => 'SELECT id as po_terms_id, text as po_terms, ordinal FROM purchase_order_terms ORDER BY ordinal ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],
		'po_shipping_methods' => [
			'qry' => 'SELECT id as po_shipping_method_id, text as po_shipping_method, sort_order, notes FROM purchase_order_shipping ORDER BY sort_order ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public function __construct($purchase_orders_id=NULL) {
		$this->_init();
		$this->build_dynamic_maps();
		if (!empty($purchase_orders_id)) $this->load('purchase_orders_id', $purchase_orders_id);
	}

	private function build_dynamic_maps() {
		if (!self::$lookup['po_statuses']['built']) {
			$po_statuses = self::fetch('po_statuses', []);
			$statuses = [];
			$status_ids = [];
			foreach ($po_statuses as $status) {
				$statuses[$status['po_status_id']] = $status['po_status'];
				$status_ids[$status['po_status']] = $status['po_status_id'];
			}
			$this->load_lookup('po_statuses', $statuses);
			$this->load_lookup('po_status_ids', $status_ids);
		}

		if (!self::$lookup['po_terms']['built']) {
			$po_terms = self::fetch('po_terms', []);
			$terms = [];
			foreach ($po_terms as $term) {
				$terms[$term['po_terms_id']] = $term['po_terms'];
			}
			$this->load_lookup('po_terms', $terms);
		}

		if (!self::$lookup['po_shipping_methods']['built']) {
			$po_shipping_methods = self::fetch('po_shipping_methods', []);
			$shipping_methods = [];
			foreach ($po_shipping_methods as $shipping_method) {
				$shipping_methods[$shipping_method['po_shipping_method_id']] = $shipping_method['po_shipping_method'];
			}
			$this->load_lookup('po_shipping_methods', $shipping_methods);
		}

		if (!self::$lookup['po_confirmation_statuses']['built']) {
			$this->load_lookup('po_confirmation_statuses', [0 => 'no confirmation requested', 1 => 'confirmation requested', 2 => 'confirmation received']);
		}
	}

	protected static $lookup = [
		'po_statuses' => [
			'cardinality' => cardinality::ROW,
			'format' => []
		],
		'po_status_ids' => [
			'cardinality' => cardinality::ROW,
			'format' => []
		],
		'po_terms' => [
			'cardinality' => cardinality::ROW,
			'format' => []
		],
		'po_shipping_methods' => [
			'cardinality' => cardinality::ROW,
			'format' => []
		],
		'po_confirmation_statuses' => [
			'cardinality' => cardinality::ROW,
			'format' => []
		],
	];

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'purchase_orders_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'purchase_order_id' => NULL,
				'purchase_order_number' => NULL,
				'po_status_id' => NULL,
				'po_status' => NULL,
				'receiving_lock' => NULL,
				'creator_admin_id' => NULL,
				'creator' => NULL,
				'administrator_admin_id' => NULL,
				'administrator' => NULL,
				'owner_admin_id' => NULL,
				'owner' => NULL,
				'buyback_admin_id' => NULL,
				'buyback_admin' => NULL,
				'vendor_id' => NULL,
				'vendor' => NULL,
				'vendors_email_address' => NULL,
				'creation_date' => NULL,
				'submit_date' => NULL,
				'expected_date' => NULL,
				'followup_date' => NULL,
				'drop_ship' => NULL,
				'po_shipping_method_id' => NULL,
				'po_shipping_method' => NULL,
				'notes' => NULL,
				'po_terms_id' => NULL,
				'po_terms' => NULL,
				//'review' => NULL,
				'confirmation_status_id' => NULL,
				'confirmation_status' => NULL,
				'confirmation_hash' => NULL,
				'show_vendor_pn' => NULL,
				'entity_name' => NULL,
			]
		],
		'notes' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'po_note_id' => NULL,
				'note_admin_id' => NULL,
				'admin_firstname' => NULL,
				'admin_lastname' => NULL,
				'admin_email_address' => NULL,
				'note_created' => NULL,
				'note_text' => NULL,
				'note_deleted' => NULL,
				'note_modified' => NULL
			]
		],
		'products' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'po_product_id' => NULL,
				'stock_id' => NULL,
				'ipn' => NULL,
				'vendors_pn' => NULL,
				'quantity' => NULL,
				'cost' => NULL,
				'description' => NULL,
				'qty_received' => NULL,
				'qty_remaining' => NULL,
				'qty_reviewing' => NULL,
				'unexpected' => NULL,
				'unexpected_freebie' => NULL,
				'unexpected_notes' => NULL,
				'additional_cost' => NULL
			]
		],
		'receiving_sessions' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'po_receiving_session_id' => NULL,
				'session_received_date' => NULL,
				'receiver_admin_id' => NULL,
				'receiver_firstname' => NULL,
				'receiver_lastname' => NULL,
				'receiver_email_address' => NULL,
				'notes' => NULL,
				'purchase_order_tracking_id' => NULL,
				'tracking_number' => NULL
			]
		],
		'received_products' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'po_received_product_id' => NULL,
				'po_receiving_session_id' => NULL,
				'purchase_order_product_id' => NULL,
				'stock_id' => NULL,
				'ipn' => NULL,
				'quantity_received' => NULL,
				'quantity_remaining' => NULL, // this is a stored field in the database, but it should be a calculated number
				'paid' => NULL,
				'entered' => NULL
			]
		],
		'reviews' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'po_review_id' => NULL,
				'status' => NULL,
				'creator_admin_id' => NULL,
				'creator_firstname' => NULL,
				'creator_lastname' => NULL,
				'creator_email_address' => NULL,
				'reviewer_admin_id' => NULL,
				'reviewer_firstname' => NULL,
				'reviewer_lastname' => NULL,
				'reviewer_email_address' => NULL,
				'notes' => NULL,
				'lock_for_review' => NULL,
				'created_on' => NULL,
				'modified_on' => NULL,
				'purchase_order_tracking_id' => NULL,
				'tracking_number' => NULL
			]
		],
		'reviewing_products' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'po_review_product_id' => NULL,
				'po_review_id' => NULL,
				'stock_id' => NULL,
				'ipn' => NULL,
				'po_product_id' => NULL,
				'qty_received' => NULL,
				'notes' => NULL,
				'hold_disposition_id' => NULL,
				'created_on' => NULL,
				'modified_on' => NULL,
				'weight' => NULL,
				'status' => NULL,
				'unexpected' => NULL,
				'serials' => []
			]
		],
		'allocations' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'po_allocation_id' => NULL,
				'po_product_id' => NULL,
				'stock_id' => NULL,
				'orders_id' => NULL,
				'orders_products_id' => NULL,
				'quantity' => NULL,
				'modified' => NULL,
				'allocator_admin_id' => NULL,
				'allocator_firstname' => NULL,
				'allocator_lastname' => NULL,
				'allocator_email_address' => NULL
			]
		],
		'tracking_numbers' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'po_tracking_id' => NULL,
				'tracking_number' => NULL,
				'po_shipping_method_id' => NULL,
				'eta' => NULL,
				'arrived' => NULL,
				'bin_number' => NULL,
				'arrival_time' => NULL,
				'complete' => NULL,
			]
		],
		'additional_costs' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'purchase_order_additional_cost_id' => NULL,
				'purchase_order_id' => NULL,
				'description' => NULL,
				'amount' => NULL,
				'cost_spread' => NULL,
				'date_created' => NULL
			]
		]
	];
}
?>
