<?php
class feed_googleadservices extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$feed_namespace = 'googleadservices__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2); // remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';

		$this->ftp_server = "uploads.google.com";
		$this->ftp_user = "candk_base";
		$this->ftp_pass = "zoUUvsTtYwr3iBpSL";
		$this->ftp_path = "";
		$this->destination_filename = 'gdf_google_feed.txt';

		parent::__construct(self::OUTPUT_FTP, self::DELIM_TAB, self::FILE_CSV);

		$this->category_depth = 0;
		$this->category_hierarchy = TRUE;
		$this->needs_attributes = TRUE;
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	private static $failed_loop = FALSE;

	private static function track_failure($products_id, $reason) {
		$insert = [':feed' => 'googleadservices', ':products_id' => $products_id, ':reason' => $reason];

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
			'id', // products_id
			'title', // name
			'description', // description
			'google_product_category', // google_category_taxonomy
			'product_type', // ck_category_taxonomy
			'link', // url
			'image_link', // image_url
			'additional_image_link', // up to ten more image angles
			'condition', // condition
			'availability', // in stock - pretty much always, I think
			'price', // normal customer price
			'sale_price', // sale price - default to normal customer price if none
			'sale_price_effective_date',
			'brand', // manufacturer
			'gtin', // upc
			'mpn', // model_number
			'item_group_id',
			'color', // ck_attribute_color
			'material',
			'pattern',
			'size', // ck_attribute_length
			'gender',
			'age_group',
			'tax',
			'shipping',
			'shipping_weight', // products_weight
			'excluded_destination',
			'expiration_date',
			'adwords_labels',
			'adwords_redirect',
			'custom_label_0', // CK Category 1
			'custom_label_1', // CK Category 2
			'custom_label_2', // CK Category 3
			'custom_label_3', // margin segment
			'custom_label_4', // custom_label_4
			'promotion_id' // promo1 - free shipping
		];

		$margin_thresholds = [
			0 => 'PM0',
			10 => 'PM10',
			20 => 'PM20',
			30 => 'PM30',
			40 => 'PM40',
			50 => 'PM50',
			100 => 'PM100',
			250 => 'PM250',
			500 => 'PM500',
			1000 => 'PM1K',
			3000 => 'PM3K'
		];
		$margin_threshold_max = 'PM3K+';

		$brand_mapping = [
			'Generic' => 'CablesAndKits',
			'Third Party' => 'CablesAndKits'
		];

		debug_tools::mark('Product Count: '.count($products_ids));

		ck_product_listing::cache(FALSE);

		foreach ($products_ids as $index => $products_id) {
			if ($index%250 == 0) debug_tools::mark('Iteration '.$index);

			$product = new ck_product_listing($products_id);

			if (in_array($product->get_header('conditions'), [4, 6])) continue; //exclude S&D and NOB

			if ($product->has_categories()) {
				 //Exclude Audio & Video Cables AND DVI, VGA & USB Cables
				foreach ($product->get_categories() as $category) {
					if (in_array($category->id(), [498, 23])) continue 2;
					foreach ($category->get_ancestors() as $ancestor) if (in_array($ancestor->id(), [498, 23])) continue 3;
				}
			}

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

			$images = $product->get_image();
			foreach ($images as $key => &$image) {
				$image = str_replace('=', '%3D', trim($image));
				$file = pathinfo($image);
				if ($file['filename'] == 'newproduct') unset($images[$key]);
				else $images[$key] = $image; // trimmed and adjusted
			}

			if (!$product->is_viewable()) self::track_failure($products_id, 'Product Turned Off or Admin Only');
			if ($product->get_price('original') <= 0) self::track_failure($products_id, 'Product Price is $0');
			if ($product->get_upc_number('gtin') == '74632094368') self::track_failure($products_id, 'NetElixir Blacklisted UPC');
			if (empty($images['products_image_lrg'])) self::track_failure($products_id, 'No Main Product Image');
			if ($content_reviews = $product->get_content_reviews(1)) {
				foreach ($content_reviews as $review) {
					// missing, broken, wrong or watermarked images
					if ($review['element'] == 'image' && in_array($review['reason_id'], [2, 3, 5, 6])) {
						self::track_failure($products_id, 'Image Problem: '.$review['reason']);
					}
				}
			}
			if (FALSE) {
				// we don't currently have a flag for 'do not advertise, so this will never run
				self::track_failure($products_id, 'Do Not Advertise Flag is On');
			}
			if (self::loop_has_failed()) continue;

			if (!empty($product->get_header('products_google_name'))) $title = $product->get_header('products_google_name');
			else $title = $this->sanitize_name($product->get_header('products_name'));

			$description = substr($this->rip_tags($product->get_header('products_description')), 0, 4000);

			// format the image links appropriately
			$image_link = "http://media.cablesandkits.com/".str_replace('=', '%3D', $product->get_image('products_image_lrg'));
			$additional_image_link = '';
			for ($i = 1; $i <= 6; $i++) {
				$next_image = trim($product->get_image('products_image_xl_'.$i));
				if (!empty($next_image)) $additional_image_link .= ",http://media.cablesandkits.com/".str_replace('=', '%3D', $next_image);
				$next_image = null;
			}
			if (!empty($additional_image_link)) $additional_image_link = ltrim($additional_image_link, ',');

			$brand = !empty($brand_mapping[$product->get_manufacturer()])?$brand_mapping[$product->get_manufacturer()]:$product->get_manufacturer();

			$shipping_weight = $product->get_total_weight();
			$order_weight_tare = $shipping_weight + SHIPPING_BOX_WEIGHT;
			$order_weight_percent = $shipping_weight * (SHIPPING_BOX_PADDING / 100 + 1);
			$shipping_weight = $order_weight_percent<$order_weight_tare?$order_weight_tare:$order_weight_percent;
			$shipping_weight = round($shipping_weight, 1);

			$google_product_category = NULL;
			$product_type = NULL;
			$category_1 = NULL;
			$category_2 = NULL;
			$category_3 = NULL;
			$custom_label_4 = NULL;

			if ($product->has_categories()) {
				foreach ($product->get_categories() as $idx => $category) {
					// for our categorization, google only cares about the first one
					$ancestors = $category->get_ancestors();

					if (empty($product_type)) {
						$ck_category_taxonomy = [];
						$ck_category_list = array_reverse($ancestors);
						$ck_category_list[] = $category;

						foreach ($ck_category_list as $cat) {
							$ck_category_taxonomy[] = $cat->get_header('categories_name');

							if (empty($category_1)) $category_1 = $cat->get_header('categories_name');
							elseif (empty($category_2)) $category_2 = $cat->get_header('categories_name');
							elseif (empty($category_3)) $category_3 = $cat->get_header('categories_name');
						}
						$product_type = implode(' > ', $ck_category_taxonomy);
					}

					if (empty($google_product_category)) {
						$i = 0;
						do {
							$google_category_id = $category->get_header('google_category_id');
							$category = !empty($ancestors[$i])?$ancestors[$i]:NULL;
							$i++;
						}
						while (empty($google_category_id) && !empty($ancestors[$i]));

						if (!empty($google_category_id)) {

							$google_category_taxonomy = [];

							if ($google_category = prepared_query::fetch('SELECT * FROM google_categories WHERE google_category_id = :google_category_id', cardinality::ROW, [':google_category_id' => $google_category_id])) {
								for ($i=0; $i<8; $i++) {
									if (empty($google_category['category_'.$i])) continue;
									$google_category_taxonomy[] = $google_category['category_'.$i];
								}
							}
							$google_product_category = implode(' > ', $google_category_taxonomy);
						}
					}
				}
			}

			$color = NULL;
			$size = NULL;
			if ($attributes = $product->get_attributes()) {
				foreach ($attributes as $attribute) {
					if ($attribute['attribute_key'] == 'color') $color = $attribute['value'];
					elseif ($attribute['attribute_key'] == 'length') $size = $attribute['value'];
				}
			}

			$cost = $product->get_ipn()->get_avg_cost();
			$margin = $product->get_price('display') - $cost;
			$margin_segment = $margin_threshold_max;
			foreach ($margin_thresholds as $threshold => $key) {
				if ($margin < $threshold) {
					$margin_segment = $key;
					break;
				}
			}

			$dow = dow::get_active_dow();

			$promotion_id = $product->get_price('display')>=$GLOBALS['ck_keys']->product['freeship_threshold']&&!$product->is('freight')?'promo1':'';
			if ($dow['products_id'] == $products_id) $promotion_id = 'dow';
			if ($promotion_id == 'dow') $custom_label_4 = 'dow';
			elseif ($product->is('level_1_product')) $custom_label_4 = 'level 1 product';

			$ck_family_unit_model_number = NULL;
			if ($product->get_ipn()->has_family_units()) $ck_family_unit_model_number = $product->get_ipn()->get_family_units()[0]->get_header('generic_model_number');

			$row = [
				'id' => $products_id,
				'title' => $title,
				'description' => $description,
				'google_product_category' => $google_product_category,
				'product_type' => $product_type,
				'link' => 'https://'.FQDN.$product->get_url(),
				'image_link' => $image_link,
				'additional_image_link' => $additional_image_link,
				'condition' => $product->get_condition('google'),
				'availability' => 'in stock',
				'price' => $product->get_price('original'),
				'sale_price' => $product->get_price('display'),
				'sale_price_effective_date' => '',
				'brand' => $brand,
				'gtin' => $product->get_upc_number('gtin'),
				'mpn' => $product->get_header('products_model'),
				'item_group_id' => NULL,
				'color' => $color,
				'material' => NULL,
				'pattern' => NULL,
				'size' => $size,
				'gender' => NULL,
				'age_group' => NULL,
				'tax' => NULL,
				'shipping' => NULL,
				'shipping_weight' => $shipping_weight,
				'excluded_destination' => NULL,
				'expiration_date' => NULL,
				'adwords_labels' => NULL,
				'adwords_redirect' => NULL,
				'custom_label_0' => $category_1,
				'custom_label_1' => $category_2,
				'custom_label_2' => $category_3,
				'custom_label_3' => $ck_family_unit_model_number,
				'custom_label_4' => $custom_label_4,
				'promotion_id' => $promotion_id
			];

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
