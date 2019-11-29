<?php
class feed_nextopia extends data_feed {

	public function __construct() {
		// first things first, let's clear out old files, so we don't get them backed up (no need to create a separate cron for this, just delete them where we create them)
		// we want to keep some old files, just in case there's a problem we need to research
		$feed_namespace = 'nextopia__';
		$this->remove_old_feeds($this->file_repository, $feed_namespace, 4); // keep the last 4 feeds

		$this->child_called = TRUE;
		$this->local_filename = $feed_namespace.date('Y-m-d__h-i-s-a').'.txt';
		//$this->destination_filename = 'products.txt';

		parent::__construct(self::OUTPUT_STD);

		$this->category_hierarchy = TRUE;
	}

	public function __destruct() {
		parent::__destruct(); // write the file, if we haven't already
	}

	public function build() {
		// populate
		$this->query_product_data();

		$this->header = array('sku', 'code', 'title', 'price', 'link', 'image_link', 'brand', 'description', 'head_description', 'warranty', 'condition', 'model_number', 'ipn', 'product_id', 'stock_id', 'price_dealer', 'price_wholesale_high', 'price_wholesale_low', 'current_offers', 'outlet_page');

		for ($i=1; $i<=$this->max_category_depth; $i++) {
			$this->header[] = "category $i";
			if ($i == 1) $this->header[] = 'category';
			elseif ($i == 2) $this->header[] = 'subcategory';
		}

		foreach ($this->attribute_keys as $attribute_key) {
			$this->header[] = $attribute_key;
		}

		// we need to get reserved qtys to help figure specials pricing
		$allocated = ck_ipn2::get_legacy_allocated_ipns();
		$on_hold = ck_ipn2::get_legacy_hold_ipns();

		$model_count = array();

		foreach ($this->results as $idx => &$product) {
			$row = array();
			$current_offers = ''; // init the current offers to blank, it may get filled in through processing, or it may be ignored

			$code = []; // code will handle multiple keywords

			// hash the product id for the SKU since it's not textually relevant but could collide with textually relevant data
			//$row[] = sha1($product->details['products_id']);
			// instead of the hash, let's just append the current count of the model number. This will make the sku textually relevant and avoid any collisions, which were happening even with the hash
			// If we have more than one of the same model number, then add an incremented count to the end of the model number to keep it unique
			$code[] = $model_number = preg_replace('/=$/', '', $product->details['model_number']);
			if (!isset($model_count[$model_number])) $model_count[$model_number] = 0;
			$model_count[$model_number]++;
			$model_number .= '-'.sprintf('%03d', $model_count[$model_number]);
			$row[] = $model_number;

			$code[] = $product->details['ipn'];
			$code[] = preg_replace('/-/', '', $code[0]);
			$code[] = preg_replace('/-/', '', $code[1]);

			$code = array_unique($code);

			// the "code" field is used by nextopia in sku matches, but is not required to be unique.
			// it's a space-separated set of keywords that allow partial matching - the only such field in the feed
			$row[] = implode(' ', $code);

			// reduce any whitespace to a single space character
			$row[] = preg_replace('/\s+/', ' ', strip_tags($product->details['name']));

			$ckp = new ck_product_listing($product->details['products_id']);

			// override base price with special price if appropriate
			// default to normal price, see if we should override it
			// go ahead and figure dealer price too, even though we only use it later
			// this is probably the only place that quantity is relevant in this feed, to figure out if the prices should be overridden by specials price
			$alloc = isset($allocated[$product->details['stock_id']])?$allocated[$product->details['stock_id']]:0;
			$hold = isset($on_hold[$product->details['stock_id']])?$on_hold[$product->details['stock_id']]:0;
			$available_qty = $product->details['stock_quantity'] - $alloc - $hold;
			$display_qty = $available_qty;
			if ($product->details['max_displayed_quantity'] > 0 && $product->details['max_displayed_quantity'] < $display_qty) {
				$display_qty = $product->details['max_displayed_quantity'];
			}
			$qty_on_special = min(max($available_qty-$product->details['specials_qty'], 0), $display_qty);
			// done with qty, now figure out correct price
			if (($qty_on_special || !empty($product->details['drop_ship'])) && $product->details['specials_price'] > 0) {
				$current_offers = 'On Special';
				$price = min($product->details['specials_price'], $ckp->get_price('original'));
				$dealer_price = min($product->details['specials_price'], $ckp->get_price('dealer'));
			}
			else {
				$price = $ckp->get_price('original');
				$dealer_price = $ckp->get_price('dealer');
			}
			$row[] = $price;

			// format the product link appropriately
			$link_name = preg_replace('/[^a-z0-9\s-]+/', '', strtolower(trim($product->details['name'])));
			$link_name = preg_replace('/\s+/', '-', $link_name);
			$row[] = "http://www.cablesandkits.com/$link_name-p-".$product->details['products_id'].".html";

			// format the image link appropriately
			$row[] = "http://media.cablesandkits.com/".$product->details['image_url'];

			// nothing needs to be done for the manufacturer
			$row[] = $product->details['manufacturer'];

			// reduce any whitespace to a single space character
			// also, remove any HTML tags
			$row[] = $this->rip_tags($product->details['description']);
			$row[] = $this->rip_tags($product->details['head_description']);

			// nothing needs to be done for the warranty
			$row[] = $product->details['warranty'];

			// map the internal condition to publicly consumable condition
			$condition = '';
			switch (strtolower($product->details['stock_condition'])) {
				case 'new':
				case 'oem':
					$condition = 'New';
					break;
				case 'clearance':
					$condition = 'As Is';
					break;
				case 'nob':
					$condition = 'Open Box';
					break;
				case 'nib':
					$condition = 'Factory Sealed';
					break;
				case 's&d':
					//$condition = 'Scratch & Dent';
					$condition = 'Not Perfect, But Functional';
					break;
				case 'refurb':
				case 'facrefurb':
				default:
					$condition = 'Refurbished';
					break;
			}
			$row[] = $condition;

			// nothing needs to be done for the model number, ipn, products id, stock id, dealer price has already been handled above
			$row[] = $product->details['model_number'];
			$row[] = $product->details['ipn'];
			$row[] = $product->details['products_id'];
			$row[] = $product->details['stock_id'];
			$row[] = $dealer_price;
			$row[] = $ckp->get_price('wholesale_high');
			$row[] = $ckp->get_price('wholesale_low');
			$row[] = $current_offers?$current_offers:'NONE'; // if current offers is blank, then fill it with NONE to make sure results will always be returned. We can filter out NONE on the backend
			$row[] = ($current_offers || strtolower($product->details['stock_condition']) == 's&d') ? 'Y' : 'N'; //if the product has specials or is "Scratch & Dent", we want to show it on the outlet page

			$cats = array();
			foreach ($product->categories as &$category_hierarchy) {
				$category_hierarchy = array_reverse($category_hierarchy);
				foreach ($category_hierarchy as $idx => $category) {
					if (!isset($cats[$idx])) $cats[$idx] = array();
					$cats[$idx][] = $category['category'];
				}
			}
			foreach ($cats as $idx => $categories) {
				$val = implode('||', array_unique($categories));
				$row[] = $val;
				if ($idx == 0 || $idx == 1) $row[] = $val;
			}
			if ($idx == 0) $row[] = '';
			for ($idx; $idx<$this->max_category_depth-1; $idx++) {
				$row[] = '';
			}
			unset($product->categories);

			// handle attributes appropriately, including them in the header as needed
			foreach ($this->attribute_keys as $attr_key) {
				$found = FALSE;
				foreach ($product->attributes as $attribute_key => $attribute_values) {
					if ($attribute_key == $attr_key) {
						$found = TRUE;
						$values = array();
						foreach ($attribute_values as $value) {
							if ($value['subheading']) $values[] = $value['subheading'].'~'.$value['value'];
							else $values[] = $value['value'];
						}
						$row[] = implode('||', $values);
						break;
					}
				}
				if (empty($found)) {
					$row[] = '';
				}
			}

			$this->data[] = $row;
			// manage memory
			unset($product);
			unset($this->results[$idx]);
			//if ($idx%800==0) gc_collect_cycles(); // force garbage collection
		}
	}

	// this is more of a general text wrangling function. we put it here in the absence of a better place to put it, and it's useful for getting the details of the description.
	public function rip_tags($string) {
		return preg_replace('/\s+/', ' ', preg_replace('/&nbsp;/', ' ', preg_replace('/<[^>]*>/', ' ', $string)));
	}

}
?>
