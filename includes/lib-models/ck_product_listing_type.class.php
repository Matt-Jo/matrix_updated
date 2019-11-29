<?php
class ck_product_listing_type extends ck_types {

	protected static $queries = [
		'content_statuses' => [
			'qry' => 'SELECT DISTINCT status FROM content_reviews ORDER BY status ASC',
			'cardinality' => cardinality::COLUMN,
			'stmt' => NULL
		]
	];

	private static $content_statuses;

	public function __construct($products_id=NULL) {
		$this->_init();
		$this->build_dynamic_maps();
		if (!empty($products_id)) $this->load('products_id', $products_id);
	}

	private function build_dynamic_maps() {
		if (empty(self::$content_statuses)) self::$content_statuses = self::fetch('content_statuses', []);

		foreach (self::$content_statuses as $status) {
			$this->structure['content_reviews']['key_format'][$status] = [];
		}
	}

	public function debug() {
		foreach ($this->structure as $key => $s) {
			var_dump([$key => $s['data']]);
		}
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'products_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'products_id' => NULL,
				'stock_id' => NULL,
				'salsify_id' => NULL,
				'image_reference_stock_id' => NULL,
				'products_name' => NULL,
				'products_head_title_tag' => NULL,
				'products_head_desc_tag' => NULL,
				'products_description' => NULL,
				'products_model' => NULL,
				'products_url' => NULL,
				'products_tax_class_id' => NULL,
				'products_date_added' => NULL,
				'products_date_available' => NULL,
				'manufacturers_id' => NULL,
				'is_bundle' => NULL,
				'bundle_price_flows_from_included_products' => NULL,
				'bundle_price_modifier' => NULL,
				'bundle_price_signum' => NULL,
				'always_available' => NULL,
				'lead_time' => NULL,
				'stock_quantity' => NULL,
				'serialized' => NULL,
				'ca_allocated_quantity' => NULL,
				'max_displayed_quantity' => NULL,
				'conditions' => NULL,
				'stock_weight' => NULL,
				'conditions_name' => NULL,
				'on_order' => NULL,
				'warranty_id' => NULL,
				'warranty_name' => NULL,
				'freight' => NULL,
				'discontinued' => NULL,
				'dlao_product' => NULL,
				'products_status' => NULL,
				'broker_status' => NULL,
				'level_1_product' => NULL,
				'on_special' => NULL,
				'canonical_type' => NULL,
				'canonical_id' => NULL,
				'use_seo_urls' => NULL,
				'seo_url_text' => NULL,
				'products_google_name' => NULL,
				'products_ebay_name' => NULL,
			]
		],
		'base_url' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL,
		],
		'template' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'id' => NULL,
				'stock_id' => NULL,
				'name' => NULL,
				'name_attr' => NULL,
				'model_num' => NULL,
				'weight' => NULL,
				'reg_price' => NULL,
				'price_num' => NULL,
				'price' => NULL,
				'pct_off' => NULL,
				'meta_condition' => NULL,
				'condition' => NULL,
				'header_condition' => NULL,
				'display_available' => NULL,
				'display_available_num' => NULL,
				'cart_quantity' => NULL,
				'availability' => NULL,
				'description' => NULL,
				'safe_short_description' => NULL,
				'short_description' => NULL,
				'safe_breadcrumbs' => NULL,
				'url' => NULL,
				'discontinued' => NULL,
				'img' => NULL,
				'thumb' => NULL,
				'optimg' => NULL,
				'info_availability' => NULL,
				'ship_date' => NULL,
				'on_order' => NULL,
				'addtl_ship_date' => NULL,
				'lead_time' => NULL,
				'is_discontinued?' => '?',
				'always_available?' => '?',
				'has_img?' => '?',
				'has_carousel?' => '?',
				'special?' => '?',
				'specials_price?' => '?',
				'specials_notice?' => '?',
				'available?' => '?',
				'instock?' => '?',
				'info_ships?' => '?',
				'ships_today?' => '?',
				'ships_ondate?' => '?',
				'ships_addtl?' => '?',
				'stockavailable?' => '?',
				'outofstock?' => '?', 
				'on_order?' => '?',
				'free_shipping?' => '?',
				'qualifies_free_shipping?' => '?',
				'warranty?' => '?',
				'options?' => '?',
				'has_options?' => '?',
				'img_thumbs' => [],
				'attributes' => [],
				'included_options' => [],
				'extra_options' => [],
				'cross_sell_products' => [],
				'also_purchased_products' => [],
				'schema' => [],
			],
		],
		'thin_template' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'id' => NULL,
				'stock_id' => NULL,
				'name' => NULL,
				'name_attr' => NULL,
				'model_num' => NULL,
				'weight' => NULL,
				'reg_price' => NULL,
				'price_num' => NULL,
				'price' => NULL,
				'pct_off' => NULL,
				'meta_condition' => NULL,
				'condition' => NULL,
				'header_condition' => NULL,
				'display_available' => NULL,
				'display_available_num' => NULL,
				'cart_quantity' => NULL,
				'availability' => NULL,
				'description' => NULL,
				'safe_short_description' => NULL,
				'short_description' => NULL,
				'safe_breadcrumbs' => NULL,
				'url' => NULL,
				'discontinued' => NULL,
				'img' => NULL,
				'thumb' => NULL,
				'optimg' => NULL,
				'info_availability' => NULL,
				'ship_date' => NULL,
				'on_order' => NULL,
				'addtl_ship_date' => NULL,
				'lead_time' => NULL,
				'is_discontinued?' => '?',
				'always_available?' => '?',
				'has_img?' => '?',
				'has_carousel?' => '?',
				'special?' => '?',
				'specials_price?' => '?',
				'specials_notice?' => '?',
				'available?' => '?',
				'instock?' => '?',
				'info_ships?' => '?',
				'ships_today?' => '?',
				'ships_ondate?' => '?',
				'ships_addtl?' => '?',
				'stockavailable?' => '?',
				'outofstock?' => '?', 
				'on_order?' => '?',
				'free_shipping?' => '?',
				'qualifies_free_shipping?' => '?',
				'warranty?' => '?',
				'options?' => '?',
				'has_options?' => '?',
				'availdetails' => '?'
			],
		],
		'schema' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'mpn' => NULL, // model #
				'name' => NULL,
				'sku' => NULL, // product ID
				'description' => NULL,
				'url' => NULL,
				'image' => NULL,
				'brand' => NULL,
				'price' => NULL,
				'price_currency' => NULL,
				'availability' => NULL,
				'condition' => NULL,
				'inventory_level' => NULL,
				'warranty' => NULL,
				'weight' => NULL,
				'attribute_properties' => [], // not currently used
			],
		],
		'ipn' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'images' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'products_image' => NULL,
				'products_image_med' => NULL,
				'products_image_lrg' => NULL,
				'products_image_sm_1' => NULL,
				'products_image_xl_1' => NULL,
				'products_image_sm_2' => NULL,
				'products_image_xl_2' => NULL,
				'products_image_sm_3' => NULL,
				'products_image_xl_3' => NULL,
				'products_image_sm_4' => NULL,
				'products_image_xl_4' => NULL,
				'products_image_sm_5' => NULL,
				'products_image_xl_5' => NULL,
				'products_image_sm_6' => NULL,
				'products_image_xl_6' => NULL
			]
		],
		'inventory' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'on_hand' => NULL,
				'allocated' => NULL,
				'on_hold' => NULL,
				'available' => NULL,
				'on_special' => NULL,
				'display_available_num' => NULL,
				'display_available' => NULL,
				'max_available_quantity_including_accessories' => NULL
			]
		],
		'prices' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'display' => NULL,
				'original' => NULL,
				'dealer' => NULL,
				'wholesale_high' => NULL,
				'wholesale_low' => NULL,
				'special' => NULL,
				'customer' => NULL,
				'bundle_original' => NULL,
				'bundle_dealer' => NULL,
				'bundle_wholesale_high' => NULL,
				'bundle_wholesale_low' => NULL,
				'original_reason' => NULL,
				'reason' => NULL,
				'bundle_rollup' => NULL,
			]
		],
		// specials should be its own object, but for now we'll just do it like this
		'special' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'specials_id' => NULL,
				'specials_new_products_price' => NULL,
				'specials_date_added' => NULL,
				'specials_last_modified' => NULL,
				'expires_date' => NULL,
				'date_status_change' => NULL,
				'status' => NULL,
				'specials_qty' => NULL,
				'velocity' => NULL,
				'active_criteria' => NULL
			]
		],
		'all_specials' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'specials_id' => NULL,
				'specials_new_products_price' => NULL,
				'specials_date_added' => NULL,
				'specials_last_modified' => NULL,
				'expires_date' => NULL,
				'date_status_change' => NULL,
				'status' => NULL,
				'specials_qty' => NULL,
				'velocity' => NULL,
				'active_criteria' => NULL
			]
		],
		'customer' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'categories' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'category' => NULL
			]
		],
		'parent_listings' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'products_id' => NULL,
				'listing' => NULL,
				'addon_name' => NULL,
				'addon_desc' => NULL,
				'bundle_quantity' => NULL,
				'addon_price' => NULL,
				'addon_display_price' => NULL,
				// this is a v0.1 kind of data format here, to indicate that if this element is missing from the data load, don't populate it at all
				// this data format *will change* - don't rely on it long term
				'recommended?' => '?',
				'allow_mult_opts' => NULL
			],
			'key_format' => [
				'included' => [],
				'extra' => []
			]
		],
		'options' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'products_id' => NULL,
				'listing' => NULL,
				'name' => NULL,
				'desc' => NULL,
				'bundle_quantity' => NULL,
				'bundle_revenue_pct' => NULL,
				'price' => NULL,
				'display_price' => NULL,
				'addon_id' => NULL,
				// this is a v0.1 kind of data format here, to indicate that if this element is missing from the data load, don't populate it at all
				// this data format *will change* - don't rely on it long term
				'recommended?' => '?',
				'last' => NULL,
				'allow_mult_opts' => NULL
			],
			'key_format' => [
				'included' => [],
				'extra' => []
			]
		],
		'attributes' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'key_name' => NULL,
				'attribute_key_id' => NULL,
				'attribute_key' => NULL,
				'value' => NULL,
				'itemprop' => NULL
			]
		],
		'cross_sells' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'product' => NULL
			]
		],
		'also_purchased' => [
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'product' => NULL
			]
		],
		'notifications' => [
			'cardinality' => cardinality::SET,
			'format' => [
				'customers_id' => NULL,
				'date_added' => NULL
			]
		],
		'content_reviews' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'content_review_id' => NULL,
				'notice_date' => NULL,
				'admin_id' => NULL,
				'reporter_first_name' => NULL,
				'reporter_last_name' => NULL,
				'reporter_email_address' => NULL,
				'element_id' => NULL,
				'element' => NULL,
				'image_slot' => NULL,
				'reason_id' => NULL,
				'reason' => NULL,
				'status' => NULL,
				'notes' => NULL,
				'responder_id' => NULL,
				'responder_first_name' => NULL,
				'responder_last_name' => NULL,
				'responder_email_address' => NULL,
				'response_date' => NULL
			],
			'key_format' => [
				// defined on init
			]
		],
		'manufacturer' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
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
				'date_created' => NULL,
				'products_id' => NULL
			]
		]
	];
}
?>
