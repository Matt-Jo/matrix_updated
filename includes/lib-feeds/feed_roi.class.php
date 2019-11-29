<?php
class feed_roi extends data_feed {

	public function __construct() {
		mb_internal_encoding('UTF-8');
		$feed_namespace = 'roi__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 2); //remove any feed older than 2 days

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';

		$this->ftp_server = "roirevolution.brickftp.com";
		$this->ftp_user = "Jason.gazaway@cablesandkits.com";
		$this->ftp_pass = "HsmPAH&Y%81uQQ0EYdukHjAkRA1loRlOXBgJ2M$4ZBJwaT8&@tcQmaESn59lwfc#l*pGPR1UjV8VNqLURAUSYbmHvhy5i9qU*TDj";
		//$this->ftp_path = 'Client Services/PPC/Cables and Kits';
		$this->destination_filename = $feed_namespace.'cablesandkits.txt';

		parent::__construct(self::OUTPUT_FTP, self::DELIM_TAB, self::FILE_CSV);

		$this->category_depth = 0;
		$this->category_hierarchy = TRUE;
		$this->needs_attributes = TRUE;

	}

	public function __destruct() {
		parent::__destruct(); //write the files
	}

	private static $failed_loop = FALSE;

	private static function track_failure($products_id, $reason) {
		$insert = [':feed' => 'roi', ':products_id' => $products_id, ':reason' => $reason];

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
		//retreive product ids from product table
		//category_id 90 is the shipping supply category, we do not want to send this to listrak, so we are excluding it
		$products_ids = prepared_query::fetch('SELECT products_id FROM products p LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE psc.products_stock_control_category_id != 90 ORDER BY products_id ASC', cardinality::SET);

		$this->header = [
			'sku', //unique stock number of product
			'title', //product name
			'description', //description of product
			'price', // list price of product
			'sale_price', //sale price of product
			'sale_start_date', //start date time of sale
			'category', //category or department
			'link_url', //url of product
			'image_url', //url for product image
			'condition',
			'availability',
			'brand',
			'mpn',
			// tax
			// shipping cost
			'shipping_weight',
			'upc',
			'additional_images',
			'gender', //gender of product (if applicable)
			// age group
			'color', //color of product (e.g. green, paisley, etc.)
			'size', //size of product (e.g. small, M, 6 7/8")
			// material
			// pattern
			// seo keywords
			// bullet points (specs)
			// highlights
			// quantity
			'unit_cost', //price per indvidual item
			// product status (simple/configurable)
			// parent/child relationships
			// url status
			// 'master_sku',
		];

		$brand_mapping = [
			'Generic' => 'CablesAndKits',
			'Third Party' => 'CablesAndkits'
		];

		echo count($products_ids).'<br>';

		$start = time();

		foreach ($products_ids as $index => $products_id) {
			$product = new ck_product_listing($products_id['products_id']);

			if (!$product->found()) {
				self::track_failure($products_id['products_id'], 'Basic Completeness Fail - could not instantiate Listing');
				self::loop_has_failed(); // clear the fail flag
				continue; // can't do anything past this point
			}

			$products_id = $product->get_header('products_id');

			$images = $product->get_image();
			foreach ($images as $key => $image) {
				$image = str_replace('=', '%3D', trim($image));
				$file = pathinfo($image);
				if ($file['filename'] == 'newproduct') unset($images[$key]);
				else $images[$key] = $image; //trimmed and adjusted
			}

			if (!$product->is_viewable()) self::track_failure($products_id, 'Product Turned Off or Admin Only');
			if ($product->get_price('original') <= 0) self::track_failure($products_id, 'Product Price is $0');
			if (empty($images['products_image_lrg'])) self::track_failure($products_id, 'No Main Product Image');
			if ($content_reviews = $product->get_content_reviews(1)) {
				foreach ($content_reviews as $review) {
					//missing, broken, wrong or watermarked images
					if ($review['element'] == 'image' && in_array($review['reason_id'], [2, 3, 5, 6])) {
						self::track_failure($products_id, 'Image Problem: '.$review['reason']);
					}
				}
			}

			if (FALSE) {
				//we don't currently have a flag for 'do not advertisem, so this will never run
				self::track_failure($products_id, 'Do Not Advertise Flag is On');
			}
			if (self::loop_has_failed()) continue;

			$title = $this->sanitize_name($product->get_header('products_name'));
			$description = ltrim(trim(substr($this->rip_tags($product->get_header('products_description')), 0, 4000)));
			$link_url = 'www.cablesandkits.com'.$product->get_url().'?utm_source=google&utm_medium=cpc&utm_term='.$products_id.'&ne_ppc_id=2221&ne_kw='.$products_id;

			$image_url = "https://media.cablesandkits.com/".str_replace('=', '%3D', $product->get_image('products_image_lrg'));
			$additional_images = '';
			for ($i = 1; $i <= 6; $i++) {
				$next_image = trim($product->get_image('products_image_xl_'.$i));
				if (!empty($next_image)) $additional_images .= ",https://media.cablesandkits.com/".str_replace('=', '%3D', $next_image);
				$next_image = null;
			}
			if (!empty($additional_images)) $additional_images = ltrim($additional_images, ',');

			$brand = !empty($brand_mapping[$product->get_manufacturer()])?$brand_mapping[$product->get_manufacturer()]:$product->get_manufacturer();
			$category = NULL;
			$sub_category = NULL;

			if ($categories = $product->get_categories()) {
				foreach ($categories as $idx => $category1) {
					// for our categorization, google only cares about the first one
					$ancestors = $category1->get_ancestors();
					$ck_category_list = array_reverse($ancestors);

					foreach ($ck_category_list as $cat) {
						if (empty($category)) $category = $cat->get_header('categories_name');
						elseif (empty($sub_category)) $sub_category = $cat->get_header('categories_name');
					}
				}
			}

			$qoh = $product->get_header('stock_quantity');
			$in_stock = $qoh > 0 ? 1 : 0;

			$discontinued = $product->get_header('discontinued');

			$size = NULL;
			$color = NULL;

			//if color and length attribute exists set it to $color and $size
			if ($attributes = $product->get_attributes()) {
				foreach ($attributes as $attribute) {
					if ($attribute['attribute_key'] == 'color') $color = $attribute['value'];
					elseif ($attribute['attribute_key'] == 'length') $size = $attribute['value'];
				}
			}

			$unit_cost = $product->get_ipn()->get_avg_cost();
			$is_viewable = $product->is_viewable();

			$price = $product->get_price('original');
			$sale_price = $product->get_price('display');
			$on_sale = $sale_price < $price ? 1 : 0;
			$shipping_weight = $product->get_header('stock_weight');
			$condition = $product->get_header('conditions');

			$upc = NULL;
			if ($product->has_upcs()) $upc = $product->get_upc_number();


			$this->data[] = [
				'sku' => $products_id,
				'title' => $title,
				'description' => $description,
				'price' => $price,
				'sale_price' => $sale_price,
				'sale_start_date' => NULL,
				//'sale_start_date' => $sale_start_date,
				'category' => $category,
				'link_url' => $link_url,
				'image_url' => $image_url,
				'condition' => $condition,
				'availabilty' => $in_stock,
				'brand' => $brand,
				'mpn' => $product->get_header('products_model'),
				// tax
				// shipping cost
				'shipping_weight' => $shipping_weight,
				'upc' => $upc,
				'additional_images' => $additional_images,
				'gender' => NULL, //gender of product (if applicable)
				// age group
				'color' => $color, //color of product (e.g. green, paisley, etc.)
				'size' => $size, //size of product (e.g. small, M, 6 7/8")
				// material
				// pattern
				// seo keywords
				// bullet points (specs)
				// highlights
				// quantity
				'unit_cost' => $unit_cost, //price per indvidual item
				// product status (simple/configurable)
				// parent/child relationships
				// url status
				// master_sku unique stock number of the master product
			];

			if ($index % 1000 == 0) echo 'checkpoint ('.$index.')<br> Time elapsed: '.(time() - $start).' seconds<br>';
			flush();
		}

		echo '<br><span style=\'color:red\'>Completed in: '.(time() - $start).' seconds<br></span>';
	}

	// this is more of a general text wrangling function. we put it here in the absence of a better place to put it, and it's useful for getting the details of the description.
	public function rip_tags($string) {
		return preg_replace('/\s+/', ' ', preg_replace('/&nbsp;/', ' ', preg_replace('/<[^>]*>/', ' ', $string)));
	}

	public function sanitize_name($string) {
		return preg_replace('/\s+/', ' ', preg_replace('|[^a-zA-Z0-9 ,%#_()&+=./"\'-]|', '', preg_replace_callback('/&(([a-z]+)|(#[0-9]+));/', function($matches) { return html_entity_decode($matches[0]); }, strip_tags($string))));
	}
} ?>
