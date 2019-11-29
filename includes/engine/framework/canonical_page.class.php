<?php
class canonical_page {

	public $link = NULL;

	public function __construct($key, $url, $type=NULL) {
		$this->page_key = $key;
		$parsed = parse_url($url);
		$this->page_url = $parsed['path'];

		// every page needs a canonical link
		$this->link = 'https://'.PRODUCTION_FQDN.$this->page_url;

		if ($type == 'catalog') {
			$this->canonical_catalog();
		}
		elseif ($type == 'product') {
			$this->canonical_product();
		}
		elseif (!empty($GLOBALS['view']) && $GLOBALS['view'] instanceof ck_view && method_exists($GLOBALS['view'], 'canonical_link')) {
			$this->link = 'https://'.FQDN.$GLOBALS['view']->canonical_link();
		}
		else { // type is a generic page
			$this->canonical_page($type);
		}
	}

	public function use_link() {
		return !empty($this->link);
	}

	private function canonical_catalog() {
		// eventually this will move to a catalog class, or we'll expand this to a more generic URL class
		try {
			$category = new ck_listing_category($this->page_key->category_id);
			$canonical_url = $category->get_canonical_url();
			if (!empty($_GET['page'])) $canonical_url .= '?'.$_SERVER['QUERY_STRING'];
			if ($this->check_current($canonical_url)) {
				$this->link = 'https://'.$GLOBALS['domain'].$canonical_url;
			}
		}
		catch (Exception $e) {
			// no category found... don't need to do anything
		}
	}

	private function canonical_product() {
		// eventually this will move to a product class, or we'll expand this to a more generic URL class
		try {
			$product = new ck_product_listing($this->page_key);
			$canonical_url = $product->get_canonical_url();
			if ($this->check_current($canonical_url)) {
				$this->link = 'https://'.$GLOBALS['domain'].$canonical_url;
			}
		}
		catch (Exception $e) {
			// no product found... don't need to do anything
		}
	}

	private function canonical_page($type) {
		/*if ($type == 'homepage') {
			$canonical_url = 'https://'.$GLOBALS['domain'].'/';
			if ($this->check_current($canonical_url)) {
				$this->link = $canonical_url;
			}
		}*/
	}

	private function check_current($canonical_url) {
		$canonical = parse_url($canonical_url);
		$current = parse_url($this->page_url);

		/** /
		echo '<pre>';
		print_r($canonical);
		print_r($current);
		echo '</pre>';
		/ **/

		// we assume that scheme and host will always be the same (especially since they might not even be referenced)
		// we assume that, for our purposes here, username and password won't be used
		// for path and query, they need to match. we assume that if they are empty in either or both places, that it would still work appropriately
		// for fragment, in most cases we could technically ignore this, but in cases where we use it for ajax history, it becomes important
		if (@$canonical['path'] != @$current['path'] || @$canonical['query'] != @$current['query'] || @$canonical['fragment'] != @$current['fragment']) {
			return TRUE;
		}
		return FALSE;
	}

}
?>