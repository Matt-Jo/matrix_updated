<?php
class feed_salsify extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$feed_namespace = 'salsify__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 1); // remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';

		parent::__construct(self::OUTPUT_NONE, self::DELIM_TAB, self::FILE_CSV);

		$this->category_depth = 0;
		$this->category_hierarchy = TRUE;
		$this->needs_attributes = TRUE;
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	private static $failed_loop = FALSE;

	private static function track_failure($products_id, $reason) {
		$insert = [':feed' => 'salsify', ':products_id' => $products_id, ':reason' => $reason];

		self::loop_has_failed(TRUE);

		prepared_query::execute('INSERT INTO ck_feed_failure_tracking (feed, products_id, reason) VALUES (:feed, :products_id, :reason)', $insert);
	}

	private static function loop_has_failed($status=NULL) {
		if (!empty($status)) return self::$failed_loop = TRUE;
		elseif (!empty(self::$failed_loop)) {
			self::$failed_loop = FALSE;
			return TRUE;
		}
		return FALSE;
	}

	public function build() {
		// populate
		// in advance of rewriting this class to use our new models, just grab the ID
		debug_tools::mark('Load product list');
		$products_ids = ck_product_listing::get_product_ids_for_product_feeds();

		$this->header = [
			'internal_item_id', // products_id
			'upc_gtin', // primary UPC
			'salsify_unique_id', // for now, uniqified model #
			'product_title', // self explanatory (SE)
			'msrp', // regular price
			'dealer_price', // dealer price
			'wholesale_high_price', // wholesale high price
			'wholesale_low_price', // wholesale low price
			'manufacturer', // manufacturer
			'short_description', // short description
			'long_description', // long description
			'country_of_origin', // US for everything, per convo with Salsify
			'category_taxonomies', // top level category
			'families', // family containers
			//'attributes', // structured list of attributes
			//'family', // what fam is it attached to
			//'included_products', // what products are included in this
			//'keywords', // keywords
			// not handled:
			// images
			// sub-categories
			// how we're creating the salsify unique id
			// need to look through the rest of our product info
			'ck_model_number', // SE
			'ck_google_product_title', // the google specific name, if any
			'ck_date_added', // date record created
			'ck_date_available', // date record made available
			//'ck_date_modified', // date record last modified
			'ck_unit_weight', // weight of individual unit
			'ck_product_status', // published or not
			'ck_broker_status', // published to brokers or not
			'ck_ipn', // what IPN it's connected to
			'ck_canonical_type', // is this using another product as canonical?
			'ck_url', // what is the URL we use for this product
			'ck_tab_title', // the page title tag info
			'ck_ebay_product_title', // ebay
			//'ck_ebay_product_subtitle', // ebay
			'ck_serialized_item', // is the item serialized
			'ck_lead_time', // what is our lead time on the item
			'ck_max_displayed_quantity', // what is the max qty we want to display
			'ck_freight_item', // do we ship by freight?
			'ck_drop_ship', // do we intend to drop ship this?
			'ck_non_stock', // this is a non-stock item
			'ck_bundle', // this is a bundle item
			'ck_special_order_only', // SE
		];

		$attrs = $this->query_attribute_data();

		foreach ($this->attribute_keys as $attribute_key) {
			$this->header[] = $attribute_key;
		}

		ck_product_listing::cache(FALSE);

		$salsify_unique_ids = [];

		foreach ($products_ids as $index => $products_id) {
			$product = new ck_product_listing($products_id);

			if (!$product->found()) {
				self::track_failure($products_id, 'Basic Completeness Fail - could not instantiate Listing');
				self::loop_has_failed(); // clear the fail flag
				continue; // can't do anything past this point
			}

			if (!$product->get_ipn()->found()) {
				self::track_failure($products_id, 'Basic Completeness Fail - could not instantiate IPN');
				self::loop_has_failed(); // clear the fail flag
				continue; // some stuff may or may not work, but we don't care that much
			}

			if (self::loop_has_failed()) continue;

			$header = $product->get_header();

			$ipn = $product->get_ipn();

			if (!empty($header['salsify_id'])) $salsify_unique_id = $header['salsify_id'];
			else {
				$base_salsify_unique_id = $ipn->get_header('ipn').'_'.$header['products_model'];
				$salsify_unique_id = $base_salsify_unique_id;
				$ctr = 0;

				while (in_array($salsify_unique_id, $salsify_unique_ids)) {
					$ctr++;
					$salsify_unique_id = $base_salsify_unique_id.'_'.$ctr;
				}

				$salsify_unique_ids[] = $salsify_unique_id;

				$product->set_salsify_id($salsify_unique_id);
				$header['salsify_id'] = $salsify_unique_id;
			}

			$prices = $product->get_price();

			$taxonomies = [];
			try {
				$cats = $product->get_categories();
				if (!empty($cats)) {
					foreach ($cats as $cat) {
						$taxonomies[] = '/'.implode('/', $cat->get_taxonomy());
					}
				}
			}
			catch (Exception $e) {
				// fail silently
			}

			$taxonomies = implode(',', $taxonomies);

			$families = [];
			try {
				foreach ($ipn->get_family_units() as $fam) {
					foreach ($fam->get_containers() as $ctnr) {
						$families[] = $ctnr->get_header('name');
					}
				}
			}
			catch (Exception $e) {
				// fail silently
			}

			$families = implode(',', $families);

			$row = [
				'internal_item_id' => $product->id(), // products_id
				'upc_gtin' => $product->get_upc_number(), // primary UPC
				'salsify_unique_id' => $salsify_unique_id, // for now, uniqified model #
				'product_title' => $header['products_name'], // self explanatory (SE)
				'msrp' => $product->is('is_bundle')?$prices['bundle_original']:$prices['original'], // regular price
				'dealer_price' => $product->is('is_bundle')?$prices['bundle_dealer']:$prices['dealer'], // dealer price
				'wholesale_high_price' => $product->is('is_bundle')?$prices['bundle_wholesale_high']:$prices['wholesale_high'], // wholesale high price
				'wholesale_low_price' => $product->is('is_bundle')?$prices['bundle_wholesale_low']:$prices['wholesale_low'], // wholesale low price
				'manufacturer' => $product->get_manufacturer(), // manufacturer
				'short_description' => preg_replace('/\s+/', ' ', $header['products_head_desc_tag']), // short description
				'long_description' => preg_replace('/\s+/', ' ', $header['products_description']), // long description
				'country_of_origin' => 'US', // US for everything, per convo with Salsify
				'category_taxonomies' => $taxonomies, // top level category
				'families' => $families, // family containers
				//'attributes', // structured list of attributes
				//'family', // what fam is it attached to
				//'included_products', // what products are included in this
				//'keywords', // keywords
				// not handled:
				// images
				// sub-categories
				// how we're creating the salsify unique id
				// need to look through the rest of our product info
				'ck_model_number' => $header['products_model'], // SE
				'ck_google_product_title' => $header['products_google_name'], // the google specific name, if any
				'ck_date_added' => !empty($header['products_date_added'])?ck_datetime::format_direct($header['products_date_added'], ck_datetime::TIMESTAMP):'', // date record created
				'ck_date_available' => !empty($header['products_date_available'])?ck_datetime::format_direct($header['products_date_available'], ck_datetime::TIMESTAMP):'', // date record made available
				//'ck_date_modified', // date record last modified
				'ck_unit_weight' => $header['stock_weight'], // weight of individual unit
				'ck_product_status' => $header['products_status'], // published or not
				'ck_broker_status' => $header['broker_status'], // published to brokers or not
				'ck_ipn' => $ipn->get_header('ipn'), // what IPN it's connected to
				'ck_canonical_url' => $product->get_canonical_url(), // is this using another product as canonical?
				'ck_url' => $product->get_url(), // what is the URL we use for this product
				'ck_tab_title' => preg_replace('/\s+/', ' ', $header['products_head_title_tag']), // the page title tag info
				'ck_ebay_product_title' => $header['products_ebay_name'], // ebay
				//'ck_ebay_product_subtitle', // ebay
				'ck_serialized_item' => $ipn->is('serialized')?1:0, // is the item serialized
				'ck_lead_time' => $header['lead_time'], // what is our lead time on the item
				'ck_max_displayed_quantity' => $header['max_displayed_quantity'], // what is the max qty we want to display
				'ck_freight_item' => $product->is('freight')?1:0, // do we ship by freight?
				'ck_drop_ship' => $ipn->is('drop_ship')?1:0, // do we intend to drop ship this?
				'ck_non_stock' => $ipn->is('non_stock')?1:0, // this is a non-stock item
				'ck_bundle' => $product->is('is_bundle')?1:0, // this is a bundle item
				'ck_special_order_only' => $ipn->is('special_order_only')?1:0, // SE
			];

			foreach ($this->attribute_keys as $attribute_key) {
				$found = FALSE;
				if (!empty($attrs[$product->id()])) {
					if (!empty($attrs[$product->id()][$attribute_key])) {
						$found = TRUE;
						$values = [];
						foreach ($attrs[$product->id()][$attribute_key] as $value) {
							if (!empty($value['subheading'])) $values[] = $value['subheading'].'~'.$value['value'];
							else $values[] = $value['value'];
						}
						$row[$attribute_key] = implode('||', $values);
						break;
					}
				}
				if (empty($found)) $row[$attribute_key] = '';
			}

			$this->data[] = $row;
			// manage memory
			unset($product);
		}

		debug_tools::mark('Finished building data.');
	}

	// this is more of a general text wrangling function. we put it here in the absence of a better place to put it, and it's useful for getting the details of the description.
	public function rip_tags($string) {
		return preg_replace('/\s+/', ' ', preg_replace('/&nbsp;/', ' ', preg_replace('/<[^>]*>/', ' ', $string)));
	}

	public function sanitize_name($string) {
		return preg_replace('/\s+/', ' ', preg_replace('|[^a-zA-Z0-9 ,%#_()&+=./"\'-]|', '', preg_replace_callback('/&(([a-z]+)|(#[0-9]+));/', function($matches) { return html_entity_decode($matches[0]); }, strip_tags($string))));
	}
}
?>
