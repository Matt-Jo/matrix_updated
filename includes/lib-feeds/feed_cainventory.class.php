<?php
class feed_cainventory extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$feed_namespace = 'cainventory__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 20); // remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';

		$this->ftp_server = "ftp.channeladvisor.com";
		$this->ftp_user = "candk:webmaster@cablesandkits.com";
		$this->ftp_pass = "1SweetGSXR";
		$this->ftp_path = "Inventory/Transform";
		$this->destination_filename = 'cainventory.CK_INV_01.txt';
	
		//parent::$TEST = true;

		parent::__construct(self::OUTPUT_FTP, self::DELIM_TAB, self::FILE_TXT);
		//parent::__construct(self::OUTPUT_FILE, self::DELIM_TAB, self::FILE_TXT);

		$this->category_depth = 0;
		$this->category_hierarchy = TRUE;
		$this->needs_attributes = TRUE;

		//$this->debug = TRUE;
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	private static $failed_loop = FALSE;

	private static function track_failure($products_id, $reason) {
		self::loop_has_failed(TRUE);
		$insert = [':feed' => 'cainventory', ':products_id' => $products_id, ':reason' => $reason];
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
		//$this->query_product_data('0');
		debug_tools::mark('Load Categories and Attributes');
		$this->query_category_data();
		$this->query_attribute_data();

		debug_tools::mark('Load inventory');
		ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);
		ck_ipn2::run_ipn_set();

		debug_tools::mark('Load product list');
		$products_ids = prepared_query::fetch('SELECT p.products_id FROM products p LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE psc.products_stock_control_category_id != 90 AND p.archived = 0 ORDER BY products_id ASC', cardinality::SET);

		$this->header = [
			'ck_unique_model_number',
			'ck_upc',
			'ck_amazon_upc',
			'ck_walmart_upc',
			'ck_products_id',
			'ck_products_status',
			'ck_stock_id',
			'ck_model_number',
			'ck_stock_name',
			'ck_warranty',
			'ck_product_name',
			'ck_product_description',
			'ck_product_short_description',
			'ck_condition_note',
			'ck_price',
			'ck_special_price',
			'ck_cost',
			'ck_gross_margin',
			'ck_url',
			'ck_image_urls',
			'ck_manufacturer',
			'ck_manufacturer_part_number',
			'ck_brand',
			'ck_free_shipping_allowed',
			'ck_condition',
			'ck_ipn_category',
			'ck_quantity_available',
			'ck_special_quantity',
			'ck_weight',
			'ck_included_products',
			'ck_content_review_reason',
			'ck_item_type',
			'ck_product_type',
			'ck_ebay_category',
			'ck_ebay_shop_category',
			'ck_days_overstock',
			'ck_advertising_indicator',
			'ck_dealer_price',
			'ck_wholesale_high_price',
			'ck_wholesale_low_price',
			'ck_family_unit_model_number',
			'ck_min_inventory_level',
			'ck_days_supply',
			'ck_drop_ship',
			'ck_non_stock',
		];

		debug_tools::mark('Product Count: '.count($products_ids));

		for ($i=1; $i<=$this->max_category_depth; $i++) {
			$this->header[] = 'ck_category_'.$i;
		}

		foreach ($this->attribute_keys as $attribute_key) {
			$this->header[] = 'ck_attribute_'.$attribute_key;
		}

		$model_count = [];

		ck_product_listing::cache(FALSE);

		foreach ($products_ids as $index => $products) {
			//if ($index < 8250) continue;
			if ($index%250 == 0) debug_tools::mark('Iteration '.$index);
			$product = new ck_product_listing($products['products_id']);

			if (!$product->found()) {
				self::track_failure($products['products_id'], 'Basic Completeness Fail - could not instantiate Listing');
				self::loop_has_failed();
				continue;
			}

			$products_id = $product->id();

			if (!$product->get_ipn()->found()) {
				self::track_failure($products_id, 'Basic Completeness Fail - could not instantiate IPN');
				self::loop_has_failed();
				continue;
			}

			$manufacturer = $product->get_manufacturer();
			// CA would like us to map 'Generic' and 'Third Party' to CablesAndKits
			if ($product->get_manufacturer() == 'Generic' || $product->get_manufacturer() == 'Third Party') $manufacturer = 'CablesAndKits';

			$model_number = $product->get_header('products_model');

			if (empty($model_count[$model_number])) $model_count[$model_number] = 0;
			$model_count[$model_number]++;

			if ($model_count[$model_number] > 1) $model_number .= '-'.sprintf('%03d', $model_count[$model_number]);

			$amazon_upc = empty(trim($product->get_upc_number('asin')))?'_DELETE_' : trim($product->get_upc_number('asin'));
			$description = str_replace("\t", " ", str_replace([PHP_EOL, "\r\n", "\r", "\n"], '', self::remove_nocontent_elements($product->get_header('products_description'))));
			$short_description = str_replace("\t", ' ', str_replace([PHP_EOL, "\r\n", "\r", "\n"], '', $product->get_header('products_head_desc_tag')));

			$condition_note = '';
			if ($product->get_header('warranty_id') > 0 && $product->get_header('warranty_id') != '4') {
				$condition_note = 'Lifetime Advance Replacement Warranty! Risk Free Purchase & Hassle Free Returns';
			}

			// format the image links appropriately
			$image_urls = "http://media.cablesandkits.com/" . str_replace('=', '%3D', $product->get_image('products_image_lrg'));
			for ($i = 1; $i <= 6; $i++) {
				$next_image = trim($product->get_image('products_image_xl_' . $i));
				if (!empty($next_image)) $image_urls .= ",http://media.cablesandkits.com/" . str_replace('=', '%3D', $next_image);
				$next_image = null;
			}

			$shipping_weight = $product->get_total_weight();
			$order_weight_tare = $shipping_weight + SHIPPING_BOX_WEIGHT;
			$order_weight_percent = $shipping_weight * (SHIPPING_BOX_PADDING / 100 + 1);
			$shipping_weight = $order_weight_percent<$order_weight_tare?$order_weight_tare:$order_weight_percent;
			$shipping_weight = round($shipping_weight, 1);

			$included_products_list = '';
			if ($product->has_options('included')) {
				$included_products_list .= "<ul>";

				foreach ($product->get_included_options() as $unused => $ip) {
					$included_products_list .= "<li><strong>".$ip['name']."</strong> - ".$ip['desc']."</li>";
				}

				$included_products_list .= "</ul>";
			}

			$content_review = '';
			if (!empty($product->get_content_reviews(1))) $content_review = 'image: '.$product->get_content_reviews(1)[0]['reason'];

			$item_type = '';
			$product_type = '';
			$ebay_shop_category = '';
			$ebay_category = '';
			$categories = [];

			$dow = dow::get_active_dow();
			$ck_advertising_indicator = 'normal';
			if ($dow['products_id'] == $products_id) $ck_advertising_indicator = 'dow';
			elseif ($product->is('level_1_product')) $ck_advertising_indicator = 'level 1 product';

			if ($product->has_categories()) {
				foreach ($product->get_categories() as $category) {
					$category_header = $category->get_header();

					$categories = array_reverse($category->get_ancestors());
					$categories[] = $category;

					if (!empty($category_header['item_type'])) $item_type = $category_header['item_type'];

					if (!empty($category_header['product_type'])) $product_type = $category_header['product_type'];

					if (!empty($category_header['ebay_category1_id'])) $ebay_category = $category_header['ebay_category1_id'];

					if (!empty($category_header['ebay_shop_category1_id'])) $ebay_shop_category = $category_header['ebay_shop_category1_id'];
				}
			}
			
			$ck_family_unit_model_number = '';
			if ($product->get_ipn()->has_family_units()) $ck_family_unit_model_number = $product->get_ipn()->get_family_units()[0]->get_header('generic_model_number');

			$row = [
				'ck_unique_model_number' => $model_number,
				'ck_upc' => $product->get_upc_number(),
				'ck_amazon_upc' => $amazon_upc,
				'ck_walmart_upc' => $product->get_upc_number('walmart'),
				'ck_products_id' => $product->id(),
				'ck_products_status' => $product->get_header('products_status'),
				'ck_stock_id' => $product->get_header('stock_id'),
				'ck_model_number' => $product->get_header('products_model'),
				'ck_stock_name' => $product->get_ipn()->get_header('ipn'),
				'ck_warranty' => $product->get_header('warranty_name'),
				'ck_product_name' => $this->sanitize_name($product->get_header('products_name')),
				'ck_product_description' => $description,
				'ck_product_short_description' => $short_description,
				'ck_condition_note' => $condition_note,
				'ck_price' => $product->get_price('original'),
				'ck_special_price' => $product->get_price('display'),
				'ck_cost' => $product->get_ipn()->get_avg_cost(),
				'ck_gross_margin' => $product->get_price('display') - $product->get_ipn()->get_avg_cost(),
				'ck_url' => 'https://'.FQDN.$product->get_url(),
				'ck_image_urls' => $image_urls,
				'ck_manufacturer' => $manufacturer,
				'ck_manufacturer_part_number' => $product->get_header('products_model'),
				'ck_brand' => $manufacturer,
				'ck_free_shipping_allowed' => $product->get_header('freight')==0?'Yes':'No',
				'ck_condition' => $product->get_header('conditions_name'),
				'ck_ipn_category' => $product->get_ipn()->get_header('ipn_category'),
				'ck_quantity_available' => $product->get_inventory('max_available_quantity_including_accessories'),
				'ck_special_quantity' => $product->get_inventory('on_special'),
				'ck_weight' => $shipping_weight,
				'ck_included_products' => $included_products_list,
				'ck_content_review_reason' => $content_review,
				'ck_item_type' => $item_type,
				'ck_product_type' => $product_type,
				'ck_ebay_category' => $ebay_category,
				'ck_ebay_shop_category' => $ebay_shop_category,
				'ck_days_overstock' => max($product->get_ipn()->get_header('current_days_on_hand') - $product->get_ipn()->get_header('max_inventory_level'), 0),
				'ck_advertising_indicator' => $ck_advertising_indicator,
				'ck_dealer_price' => $product->get_price('dealer'),
				'ck_wholesale_high_price' => $product->get_price('wholesale_high'),
				'ck_wholesale_low_price' => $product->get_price('wholesale_low'),
				'ck_family_unit_model_number' => $ck_family_unit_model_number,
				'ck_min_inventory_level' => $product->get_ipn()->get_header('min_inventory_level'),
				'ck_days_supply' => $product->get_ipn()->get_header('current_days_on_hand'),
				'ck_drop_ship' => $product->get_ipn()->is('drop_ship')?1:0,
				'ck_non_stock' => $product->get_ipn()->is('non_stock')?1:0,
			];

			$category_index = 1;
			if (!empty($categories)) {
				foreach ($categories as $category) {
					$row['ck_category_'.$category_index] = $category->get_header('categories_name');
					$category_index++;
				}
			}

			for ($y = $category_index; $y <= $this->max_category_depth; $y++) {
				$row['ck_category_'.$y] = '';
			}

			foreach ($this->attribute_keys as $attribute_key) {
				$found = FALSE;
				if ($product->has_attributes()) {
					foreach ($product->get_attributes() as $attribute) {
						if ($attribute_key == $attribute['attribute_key']) {
							$found = TRUE;
							$values = [];
							if (!empty($value['subheading'])) $values[] = $value['subheading'].'~'.$value['value'];
							else $values[] = $attribute['value'];
							$row['ck_attribute_'.$attribute_key] = implode('||', $values);
							break;
						}
					}
				}
				if (empty($found)) $row['ck_attribute_'.$attribute_key] = '';
			}

			$this->data[] = $row;
		}

		debug_tools::mark('Finished building data.');
	}

	public function rip_tags($string) {
		return preg_replace('/\s+/', ' ', preg_replace('/&nbsp;/', ' ', preg_replace('/<[^>]*>/', ' ', $string)));
	}

	public function sanitize_name($string) {
		return preg_replace('/\s+/', ' ', preg_replace('|[^a-zA-Z0-9 ,%#_()&+=./"\'-]|', '', preg_replace_callback('/&(([a-z]+)|(#[0-9]+));/', function($matches) { return html_entity_decode($matches[0]); }, strip_tags($string))));
	}

	public static function remove_nocontent_elements($html) {
		if (empty(trim($html))) return trim($html);

		$doc = new DOMDocument();
		@$doc->loadHTML('<div>'.$html.'</div>');

		$xpath = new DOMXPath($doc);
		foreach ($xpath->query('//comment()') as $comment) {
			$comment->parentNode->removeChild($comment); // remove comments
		}

		if (!empty($doc->doctype)) $doc->removeChild($doc->doctype); // remove the added doctype
		if (!empty($doc->getElementsByTagName('html'))) {
			/*if (empty($doc->firstChild->firstChild->firstChild)) {
				echo $html."\n\n";
				echo $doc->saveHtml()."\n\n";
				echo $doc->saveHtml($doc->firstChild)."\n\n";
			}*/
			$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild); // remove the added <html><body></body></html>
		}

		$nocontent_elements = ['script', 'style', 'link'];

		foreach ($nocontent_elements as $element) {
			$nodes = $doc->getElementsByTagName($element);

			for ($i=0; $i < $nodes->length; $i++) {
				$node = $nodes->item($i);
				$node->parentNode->removeChild($node);
			}
		}

		$new_html = preg_replace('/[ \t]+/', ' ', $doc->saveHtml());

		return $new_html;
	}
} ?>
