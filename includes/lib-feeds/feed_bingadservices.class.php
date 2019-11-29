<?php
class feed_bingadservices extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$feed_namespace = 'bingadservices__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2); // remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';

		$this->ftp_server = "feeds.adcenter.microsoft.com";
		$this->ftp_user = "cablesandkits";
		$this->ftp_pass = "n3t3lixir!";
		$this->ftp_path = "";
		$this->destination_filename = 'bingshoppinggdf';

		parent::__construct(self::OUTPUT_FTPS, self::DELIM_TAB, self::FILE_CSV);

		$this->category_depth = 0;
		$this->category_hierarchy = TRUE;
		$this->needs_attributes = TRUE;
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	private static $failed_loop = FALSE;

	private static function track_failure($products_id, $reason) {
		$insert = [':feed' => 'bingadservices', ':products_id' => $products_id, ':reason' => $reason];

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
		$products_ids = prepared_query::fetch('SELECT products_id FROM products ORDER BY products_id ASC', cardinality::SET);
		//$this->query_product_data('0');

		$this->header = [
			'id', // products_id
			'title', // name
			'description', // description
			'product_category', // google_category_taxonomy
			'product_type', // ck_category_taxonomy
			'link', // url
			'image_link', // image_url
			//'additional_image_link', // up to ten more image angles
			'condition', // condition
			'availability', // in stock - pretty much always, I think
			'price', // normal customer price
			'sale_price', // sale price - default to normal customer price if none
			'mpn', // model_number
			'gtin', // upc
			'color', // ck_attribute_color
			'size', // ck_attribute_length
			'brand', // manufacturer
			'shipping_weight', // products_weight
			'customlabel0', // CK Category 1
			'customlabel1', // CK Category 2
			'customlabel2', // CK Category 3
			'customlabel3', // margin segment
			//'promotion_id', // promo1 - free shipping
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

		ck_product_listing::cache(FALSE);

		foreach ($products_ids as &$products_id) {
			$product = new ck_product_listing($products_id['products_id']);

			if (!$product->found()) {
				self::track_failure($products_id['products_id'], 'Basic Completeness Fail - could not instantiate Listing');
				self::loop_has_failed(); // clear the fail flag
				continue; // can't do anything past this point
			}

			$products_id = $product->get_header('products_id');

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

			$title = $this->sanitize_name($product->get_header('products_name'));
			$description = substr($this->rip_tags($product->get_header('products_description')), 0, 4000);
			$link = 'https://www.cablesandkits.com'.$product->get_url().'?utm_source=bing&utm_medium=cpc&utm_term='.$products_id.'&ne_ppc_id=2221&ne_kw='.$products_id;

			$image_link = 'http://media.cablesandkits.com/'.$images['products_image_lrg'];
			$additional_image_links = [];
			for ($i=1; $i<=6; $i++) {
				if (empty($images['products_image_xl_'.$i])) continue;
				$additional_image_links[] = 'http://media.cablesandkits.com/'.$images['products_image_xl_'.$i];
			}
			$additional_image_link = implode(',', $additional_image_links);

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
			if ($categories = $product->get_categories()) {
				foreach ($categories as $idx => &$category) {
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

			$promotion_id = $product->get_price('display')>=$GLOBALS['ck_keys']->product['freeship_threshold']&&!$product->is('freight')?'promo1':'';
			$row = [
				'id' => $products_id,
				'title' => $title,
				'description' => $description,
				'google_product_category' => $google_product_category,
				'product_type' => $product_type,
				'link' => $link,
				'image_link' => $image_link,
				//'additional_image_link' => $additional_image_link,
				'condition' => $product->get_condition('google'),
				'availability' => 'in stock',
				'price' => $product->get_price('original'),
				'sale_price' => $product->get_price('display'),
				'mpn' => $product->get_header('products_model'),
				'gtin' => $product->get_upc_number('gtin'),
				'color' => $color,
				'size' => $size,
				'brand' => $brand,
				'shipping_weight' => $shipping_weight,
				'custom_label_0' => $category_1,
				'custom_label_1' => $category_2,
				'custom_label_2' => $category_3,
				'custom_label_3' => $margin_segment,
				//'promotion_id' => $promotion_id
			];

			$this->data[] = $row;
			// manage memory
			unset($product);
		}
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
