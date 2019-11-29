<?php
class ck_ipn_type extends ck_types {

	protected static $queries = [
		'serial_statuses' => [
			'qry' => 'SELECT * FROM serials_status ORDER BY id ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public function __construct($stock_id=NULL) {
		$this->_init();
		$this->build_dynamic_maps();
		if (!empty($stock_id)) $this->load('stock_id', $stock_id);
	}

	private static $dynamic_map_data = [];

	private function build_dynamic_maps() {
		if (empty(self::$dynamic_map_data['serial_statuses'])) self::$dynamic_map_data['serial_statuses'] = self::fetch('serial_statuses', []);

		foreach (self::$dynamic_map_data['serial_statuses'] as $status) {
			$this->structure['serials']['key_format'][$status['id']] = [];
		}
	}

	// public function add_to_structure(Array $data) {
	// 	$this->structure += $data;
	// 	$this->rebuild();
	// }

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	public $structure = [
		'stock_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'stock_id' => NULL,
				'ipn' => NULL,
				'creation_reviewed' => NULL,
				'creator' => NULL,
				'creation_reviewer' => NULL,
				'creation_reviewed_date' => NULL,
				'stock_quantity' => NULL,
				'stock_weight' => NULL,
				'conditioning_notes' => NULL,
				'stock_description' => NULL,
				'stock_price' => NULL,
				'dealer_price' => NULL,
				'wholesale_high_price' => NULL,
				'wholesale_low_price' => NULL,
				'last_quantity_change' => NULL,
				'last_weight_change' => NULL,
				'on_order' => NULL,
				'discontinued' => NULL,
				'average_cost' => NULL,
				'target_buy_price' => NULL,
				'target_min_qty' => NULL,
				'target_max_qty' => NULL,
				'serialized' => NULL,
				'conditions' => NULL,
				'conditions_name' => NULL,
				'market_state' => NULL,
				'products_stock_control_category_id' => NULL,
				'ipn_category' => NULL,
				'products_stock_control_vertical_id' => NULL,
				'ipn_vertical' => NULL,
				'products_stock_control_group_id' => NULL,
				'ipn_group' => NULL,
				'warranty_id' => NULL,
				'warranty_name' => NULL,
				'dealer_warranty_id' => NULL,
				'dealer_warranty_name' => NULL,
				'vendor_to_stock_item_id' => NULL,
				'date_added' => NULL,
				'max_displayed_quantity' => NULL,
				'max_inventory_level' => NULL,
				'target_inventory_level' => NULL,
				'min_inventory_level' => NULL,
				'freight' => NULL,
				'drop_ship' => NULL,
				'non_stock' => NULL,
				'last_stock_price_confirmation' => NULL,
				'last_dealer_price_confirmation' => NULL,
				'last_wholesale_high_price_confirmation' => NULL,
				'last_wholesale_low_price_confirmation' => NULL,
				'pricing_review' => NULL,
				'nontaxable' => NULL,
				'liquidate' => NULL,
				'liquidate_admin_note' => NULL,
				'liquidate_user_note' => NULL,
				'ca_allocated_quantity' => NULL,
				'navx_tracking_code' => NULL,
				'pic_audit' => NULL,
				'pic_problem' => NULL,
				'donotbuy' => NULL,
				'donotbuy_date' => NULL,
				'donotbuy_user_id' => NULL,
				'donotbuy_admin' => NULL,
				'is_bundle' => NULL,
				'dlao_product' => NULL,
				'special_order_only' => NULL,
				'eccn_code' => NULL,
				'hts_code' => NULL,
				'image' => NULL,
				'image_med' => NULL,
				'image_lrg' => NULL,
				'image_sm_1' => NULL,
				'image_xl_1' => NULL,
				'image_sm_2' => NULL,
				'image_xl_2' => NULL,
				'image_sm_3' => NULL,
				'image_xl_3' => NULL,
				'image_sm_4' => NULL,
				'image_xl_4' => NULL,
				'image_sm_5' => NULL,
				'image_xl_5' => NULL,
				'image_sm_6' => NULL,
				'image_xl_6' => NULL,
				'bin1' => NULL,
				'bin2' => NULL,
				'always_available' => NULL,
				'lead_time' => NULL,
				'vendors_id' => NULL,
				'vendors_company_name' => NULL,
				'vendors_pn' => NULL,
				'case_qty' => NULL,
				'vendors_price' => NULL,
				'current_daily_runrate' => NULL,
				'current_days_on_hand' => NULL,
				'bundle_price_flows_from_included_products' => NULL,
				'bundle_price_modifier' => NULL,
				'bundle_price_signum' => NULL,
				'image_reference' => NULL
			]
		],
		'images' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'image' => NULL,
				'image_med' => NULL,
				'image_lrg' => NULL,
				'image_sm_1' => NULL,
				'image_xl_1' => NULL,
				'image_sm_2' => NULL,
				'image_xl_2' => NULL,
				'image_sm_3' => NULL,
				'image_xl_3' => NULL,
				'image_sm_4' => NULL,
				'image_xl_4' => NULL,
				'image_sm_5' => NULL,
				'image_xl_5' => NULL,
				'image_sm_6' => NULL,
				'image_xl_6' => NULL
			]
		],
		'inventory' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'on_hand' => NULL,
				'allocated' => NULL,
				'local_allocated' => NULL,
				'ca_allocated' => NULL,
				'po_allocated' => NULL,
				'on_hold' => NULL,
				'in_conditioning' => NULL,
				'available' => NULL,
				'salable' => NULL,
				'max_displayed_quantity' => NULL,
				'adjusted_available_quantity' => NULL,
				'on_order' => NULL,
				'adjusted_on_order' => NULL
			]
		],
		'prices' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'list' => NULL,
				'dealer' => NULL,
				'wholesale_high' => NULL,
				'wholesale_low' => NULL,
				'target_buy' => NULL,
			]
		],
		'special_prices' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'specials_id' => NULL,
				'products_id' => NULL,
				'price' => NULL,
				'qty_limit' => NULL,
				'expiration_date' => NULL,
				'status' => NULL,
				'date_added' => NULL,
				'date_modified' => NULL,
				'date_status_change' => NULL,
				//'velocity' => NULL, // currently unused
				'active_criteria_type' => NULL
			]
		],
		'customer_prices' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'customer' => NULL,
				'price' => NULL
			]
		],
		'listings' => [
			'cardinality' => cardinality::COLUMN,
			'format' => []
		],
		'family_units' => [
			'cardinality' => cardinality::COLUMN,
			'format' => []
		],
		'primary_container' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'primary_container_id' => NULL,
				'container_type_id' => NULL,
				'container_type' => NULL,
				'table_name' => NULL,
				'container_id' => NULL,
				'canonical' => NULL,
				'redirect' => NULL,
				'date_created' => NULL
			]
		],
		'requiring_ipns' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'ipn' => NULL
			]
		],
		'serials' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				// no named keys, just a numerical array
			],
			'key_format' => [
				0 => [], // receiving
				// defined on init
				/*2 => [], // in stock
				3 => [], // allocated
				4 => [], // invoiced
				6 => [], // on hold
				7 => [], // scrapped
				8 => [], // returned to vendor
				9 => [], // merged
				10 => [] // deleted*/
			]
		],
		'sales_history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'orders_id' => NULL,
				'orders_status_id' => NULL,
				'orders_status_name' => NULL,
				'orders_sub_status_id' => NULL,
				'orders_sub_status_name' => NULL,
				'products_quantity' => NULL,
				'final_price' => NULL,
				'date_purchased' => NULL,
				'promised_ship_date' => NULL,
				'products_id' => NULL,
				'extended_products_id' => NULL,
				'products_model' => NULL,
				'products_name' => NULL,
				'exclude_forecast' => NULL
			]
		],
		'sales_history_range' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'orders_id' => NULL,
				'orders_status_id' => NULL,
				'orders_status_name' => NULL,
				'orders_sub_status_id' => NULL,
				'orders_sub_status_name' => NULL,
				'products_quantity' => NULL,
				'final_price' => NULL,
				'date_purchased' => NULL,
				'promised_ship_date' => NULL,
				'products_id' => NULL,
				'extended_products_id' => NULL,
				'products_model' => NULL,
				'products_name' => NULL,
				'exclude_forecast' => NULL
			]
		],
		'recent_sales_history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'orders_status_id' => NULL,
				'products_quantity' => NULL,
				'date_purchased' => NULL,
				'op.exclude_forecast' => NULL
			]
		],
		'rfq_history_range' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'quantity' => NULL,
				'price' => NULL,
				'created_date' => NULL
			]
		],

		'last_specials_date' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'purchase_history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'purchase_order_id' => NULL,
				'purchase_order_number' => NULL,
				'status_id' => NULL,
				'status' => NULL,
				'purchaser' => NULL,
				'vendor' => NULL,
				'creation_date' => NULL,
				'expected_date' => NULL,
				'shipping_method_id' => NULL,
				'shipping_method' => NULL,
				'pop_id' => NULL,
				'quantity' => NULL,
				'cost' => NULL,
				'description' => NULL,
				'quantity_received' => NULL,
				'allocated_quantity' => NULL
			]
		],
		'purchase_history_range' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'purchase_order_id' => NULL,
				'purchase_order_number' => NULL,
				'status_id' => NULL,
				'status' => NULL,
				'purchaser' => NULL,
				'vendor' => NULL,
				'creation_date' => NULL,
				'expected_date' => NULL,
				'shipping_method_id' => NULL,
				'shipping_method' => NULL,
				'pop_id' => NULL,
				'quantity' => NULL,
				'cost' => NULL,
				'description' => NULL,
				'quantity_received' => NULL,
				'allocated_quantity' => NULL
			]
		],
		'receiving_history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'purchase_order_id' => NULL,
				'purchase_order_number' => NULL,
				'status_id' => NULL,
				'status' => NULL,
				'purchaser' => NULL,
				'vendor' => NULL,
				'creation_date' => NULL,
				'expected_date' => NULL,
				'shipping_method_id' => NULL,
				'shipping_method' => NULL,
				'pop_id' => NULL,
				'quantity' => NULL,
				'cost' => NULL,
				'description' => NULL,
				'quantity_received' => NULL
			]
		],
		'po_reviews' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'id' => NULL,
				'po_review_id' => NULL,
				'pop_id' => NULL,
				'qty_received' => NULL,
				'created_on' => NULL,
				'modified_on' => NULL,
				'weight' => NULL,
				'status' => NULL
			]
		],
		'change_history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'change_id' => NULL,
				'change_date' => NULL,
				'change_type' => NULL,
				'change_user' => NULL,
				'change_code' => NULL,
				'reference' => NULL,
				'old_value' => NULL,
				'new_value' => NULL,
				'admin_name' => NULL
			]
		],
		'package' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'package_type_id' => NULL,
				'length' => NULL,
				'width' => NULL,
				'height' => NULL,
				'custom_dimension' => NULL,
				'package_name' => NULL,
				'logoed' => NULL,
				'promote' => NULL
			]
		],
		'ledger_history' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'action_code' => NULL,
				'ref_number' => NULL,
				'starting_qty' => NULL,
				'qty_change' => NULL,
				'ending_qty' => NULL,
				'orders_id' => NULL,
				'invoice_id' => NULL,
				'rma_id' => NULL,
				'purchase_order_id' => NULL,
				'transaction_timestamp' => NULL,
				'transaction_date' => NULL
			]
		],
		'sales' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'order' => NULL
			]
		],
		'purchases' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'po' => NULL
			]
		],
		'vendors' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'vendor_relationship_id' => NULL,
				'vendors_id' => NULL,
				'company_name' => NULL,
				'email_address' => NULL,
				'price' => NULL,
				'part_number' => NULL,
				'case_qty' => NULL,
				'always_available' => NULL,
				'lead_time' => NULL,
				'notes' => NULL,
				'preferred' => NULL,
				'secondary' => NULL
			]
		],
		'upcs' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'upc_assignment_id' => NULL,
				'target_resource' => NULL,
				'target_resource_id' => NULL,
				'relationship' => NULL,
				'related_object' => NULL,
				'upc' => NULL,
				'unit_of_measure' => NULL,
				'uom_description' => NULL,
				'provenance' => NULL,
				'purpose' => NULL,
				'created_date' => NULL,
				'active' => NULL
			]
		],
		'forecasting_metadata' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'usage_transactions' => NULL,
				'u30_date' => NULL,
				'u60_date' => NULL,
				'u180_date' => NULL,
				'usage_0-30' => NULL,
				'usage_30-60' => NULL,
				'usage_0-180' => NULL,
				'usage_0-180_normalized' => NULL,
				'runrate_0-30' => NULL,
				'runrate_30-60' => NULL,
				'runrate_0-180' => NULL,
				'daily_runrate' => NULL,
				'leadtime_days' => NULL,
				'minimum_days' => 0,
				'target_days' => 0,
				'days_supply' => NULL,
				'minimum_quantity' => 0,
				'raw_target_quantity' => 0,
				'target_quantity' => 0,
				'pre-lead_on_order' => 0,
				'post-lead_on_order' => 0,
				'pre-min_on_order' => 0,
				'post-min_on_order' => 0,
				'pre-target_on_order' => 0,
				'post-target_on_order' => 0,
				'earliest_eta' => NULL,
				'legacy-leadtime_days' => 0,
				'legacy-minimum_days' => 0,
				'legacy-minimum_quantity' => 0,
				'legacy-pre-lead_on_order' => 0,
				'legacy-post-lead_on_order' => 0,
				'legacy-pre-min_on_order' => 0,
				'legacy-post-min_on_order' => 0,
			]
		]
	];
}
?>
