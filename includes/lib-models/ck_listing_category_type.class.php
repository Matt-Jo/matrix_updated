<?php
class ck_listing_category_type extends ck_types {

	public function __construct($categories_id=NULL) {
		$this->_init();
		if (!empty($categories_id)) $this->load('categories_id', $categories_id);
	}

	// so far we've defined the relationship and cardinality (size) of the data
	// we also need to define the data types and limitations for each of these fields
	protected $structure = [
		'categories_id' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL
		],
		'header' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'categories_id' => NULL,
				'categories_image' => NULL,
				'parent_id' => NULL,
				'sort_order' => NULL,
				'date_added' => NULL,
				'last_modified' => NULL,
				'disabled' => NULL,
				'canonical_category_id' => NULL,
				'google_category_id' => NULL,
				'product_type' => NULL,
				'item_type' => NULL,
				'finder_category' => NULL,
				'product_finder_key' => NULL,
				'product_finder_title' => NULL,
				'topnav_redirect' => NULL,
				'promo_image' => NULL,
				'promo_link' => NULL,
				'promo_offsite' => NULL,
				'inactive' => NULL,
				'categories_name' => NULL,
				'categories_heading_title' => NULL,
				'categories_description' => NULL,
				'categories_description_product_ids' => NULL,
				'use_categories_description' => NULL,
				'categories_head_title_tag' => NULL,
				'use_categories_bottom_text' => NULL,
				'categories_bottom_text' => NULL,
				'categories_bottom_text_product_ids' => NULL,
				'categories_head_desc_tag' => NULL,
				'shopping_com_category' => NULL,
				'categories_seo_url' => NULL,
				'product_finder_description' => NULL,
				'product_finder_image' => NULL,
				'product_finder_hide' => NULL,
				'use_seo_urls' => NULL,
				'seo_url_text' => NULL,
				'seo_url_parent_text' => NULL,
				'ebay_category1_id' => NULL,
				'ebay_shop_category1_id' => NULL
			]
		],
		'base_url' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL,
		],
		'original_url' => [
			'cardinality' => cardinality::SINGLE,
			'format' => NULL,
		],
		'ancestors' => [
			// vertical parent list up to top
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'category' => NULL
			]
		],
		'children' => [
			// horizontal children list one level down
			'cardinality' => cardinality::COLUMN,
			'format' => [
				'category' => NULL
			]
		],
		'listings' => [
			'cardinality' => cardinality::MAP,
			'format' => [
				'listing' => NULL
			],
			'key_format' => [
				'direct' => [], // products that belong directly to this category
				'inherited' => [] // products that belong to children of this category
			]
		],
		'primary_category' => [
			'cardinality' => cardinality::ROW,
			'format' => [
				'primary_category_id' => NULL,
				'container_type_id' => NULL,
				'container_type' => NULL,
				'table_name' => NULL,
				'container_id' => NULL,
				'canonical' => NULL,
				'redirect' => NULL,
				'date_created' => NULL
			]
		],
		'images' => [
			'cardinality' => cardinality::ROW,
			'format' => [
			]
		]
	];
}
?>
