<?php
class ck_listing_category extends ck_archetype implements ck_merchandising_container_interface {

	public $languages_id;

	protected static $skeleton_type = 'ck_listing_category_type';

	private static $preloaded_data = [
		'primary_containers' => [],
	];

	protected static $queries = [
		'category_header' => [
			'qry' => 'SELECT c.categories_id, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.disabled, c.canonical_category_id, c.google_category_id, c.product_type, c.item_type, c.finder_category, c.product_finder_key, c.product_finder_title, c.topnav_redirect, c.promo_image, c.promo_link, c.promo_offsite, c.inactive, cd.categories_name, cd.categories_heading_title, cd.categories_description, cd.use_categories_description, cd.categories_description_product_ids, cd.categories_head_title_tag, cd.categories_head_desc_tag, cd.shopping_com_category, cd.categories_seo_url, cd.product_finder_description, cd.product_finder_image, cd.product_finder_hide, cd.use_categories_bottom_text, cd.categories_bottom_text, cd.categories_bottom_text_product_ids, c.use_seo_urls, c.seo_url_text, c.seo_url_parent_text, a.ebay_category1_id, a.ebay_shop_category1_id FROM categories_description cd JOIN categories c ON cd.categories_id = c.categories_id LEFT JOIN abx_category_info a ON cd.categories_id = a.categories_id AND a.categories_site = \'US\' WHERE c.categories_id = :categories_id AND cd.language_id = :languages_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'category_header_by_pfkey' => [
			'qry' => 'SELECT c.categories_id, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.disabled, c.canonical_category_id, c.google_category_id, c.product_type, c.item_type, c.finder_category, c.product_finder_key, c.product_finder_title, c.topnav_redirect, c.promo_image, c.promo_link, c.promo_offsite, c.inactive, cd.categories_name, cd.categories_heading_title, cd.categories_description, cd.categories_head_title_tag, cd.categories_head_desc_tag, cd.shopping_com_category, cd.categories_seo_url, cd.product_finder_description, cd.product_finder_image, cd.product_finder_hide, c.use_seo_urls, c.seo_url_text, c.seo_url_parent_text FROM categories_description cd JOIN categories c ON cd.categories_id = c.categories_id WHERE c.product_finder_key LIKE :product_finder_key AND cd.language_id = :languages_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'category_header_list' => [
			'qry' => 'SELECT c.categories_id, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.disabled, c.canonical_category_id, c.google_category_id, c.product_type, c.item_type, c.finder_category, c.product_finder_key, c.product_finder_title, c.topnav_redirect, c.promo_image, c.promo_link, c.promo_offsite, c.inactive, cd.categories_name, cd.categories_heading_title, cd.categories_description, cd.categories_head_title_tag, cd.categories_head_desc_tag, cd.shopping_com_category, cd.categories_seo_url, cd.product_finder_description, cd.product_finder_image, cd.product_finder_hide, c.use_seo_urls, c.seo_url_text, c.seo_url_parent_text FROM categories_description cd JOIN categories c ON cd.categories_id = c.categories_id WHERE cd.language_id = :languages_id ORDER BY cd.categories_name ASC, c.categories_id ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'topnav_category_header_list' => [
			'qry' => 'SELECT c.categories_id, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.disabled, c.canonical_category_id, c.google_category_id, c.product_type, c.item_type, c.finder_category, c.product_finder_key, c.product_finder_title, c.topnav_redirect, c.promo_image, c.promo_link, c.promo_offsite, c.inactive, cd.categories_name, cd.categories_heading_title, cd.categories_description, cd.categories_head_title_tag, cd.categories_head_desc_tag, cd.shopping_com_category, cd.categories_seo_url, cd.product_finder_description, cd.product_finder_image, cd.product_finder_hide, c.use_seo_urls, c.seo_url_text, c.seo_url_parent_text FROM categories_description cd JOIN categories c ON cd.categories_id = c.categories_id WHERE cd.language_id = :languages_id AND c.disabled = 0 and c.inactive = 0 ORDER BY c.parent_id ASC, c.sort_order ASC, cd.categories_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'children' => [
			// this should match the header query from above
			'qry' => 'SELECT c.categories_id, c.categories_image, c.parent_id, c.sort_order, c.date_added, c.last_modified, c.disabled, c.canonical_category_id, c.google_category_id, c.product_type, c.item_type, c.finder_category, c.topnav_redirect, c.promo_image, c.promo_link, c.promo_offsite, c.inactive, cd.categories_name, cd.categories_heading_title, cd.categories_description, cd.categories_head_title_tag, cd.categories_head_desc_tag, cd.shopping_com_category, cd.categories_seo_url, cd.product_finder_description, cd.product_finder_image, cd.product_finder_hide FROM categories_description cd JOIN categories c ON cd.categories_id = c.categories_id WHERE c.parent_id = :parent_id AND cd.language_id = :languages_id ORDER BY c.sort_order ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		// we're essentially relying on nextopia to provide us this on the front end, so this is mainly for back end purposes
		'listings' => [
			'qry' => 'SELECT DISTINCT products_id FROM products_to_categories WHERE categories_id = ?',
			'cardinality' => cardinality::COLUMN,
			'stmt' => NULL
		],

		'primary_category' => [
			'qry' => 'SELECT mpc.primary_category_id, mpc.container_type_id, mct.name as container_type, mct.table_name, mpc.container_id, mpc.canonical, mpc.redirect, mpc.date_created FROM ck_merchandising_primary_categories mpc JOIN ck_merchandising_container_types mct ON mpc.container_type_id = mct.container_type_id WHERE mpc.categories_id = :categories_id',
			'cardinality' => cardinality::ROW
		],

		'add_category_discount' => [
			'qry' => 'INSERT INTO ipn_to_customers (stock_id, customers_id, price, managed_category) SELECT psc.stock_id, :customers_id, CASE c.customer_price_level_id WHEN 1 THEN psc.stock_price WHEN 2 THEN psc.dealer_price WHEN 3 THEN IFNULL(psc.wholesale_high_price, psc.dealer_price) WHEN 4 THEN IFNULL(psc.wholesale_low_price, IFNULL(psc.wholesale_high_price, psc.dealer_price)) ELSE psc.stock_price END * :discount_pctg, 1 FROM products_to_categories ptc JOIN products p ON ptc.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id, customers c WHERE c.customers_id = :customers_id AND ptc.categories_id = :categories_id ON DUPLICATE KEY UPDATE price = CASE c.customer_price_level_id WHEN 1 THEN psc.stock_price WHEN 2 THEN psc.dealer_price WHEN 3 THEN psc.wholesale_high_price WHEN 4 THEN psc.wholesale_low_price ELSE psc.stock_price END * :discount_pctg, managed_category = 1',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		/*'remove_category_discount' => [
			'qry' => 'DELETE itc FROM ipn_to_customers itc JOIN products p ON itc.stock_id = p.stock_id JOIN products_to_categories ptc ON p.products_id = ptc.products_id WHERE itc.customers_id = :customers_id AND ptc.categories_id = :categories_id',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],*/

		'active_category_discounts' => [
			'qry' => 'SELECT * FROM ck_customer_category_discount WHERE status = 1',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'expired_category_discounts' => [
			'qry' => 'SELECT * FROM ck_customer_category_discount WHERE end_date < DATE(NOW())',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	public function __construct($categories_id, ck_listing_category_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($categories_id);

		$this->languages_id = $_SESSION['languages_id'];

		if (!$this->skeleton->built('categories_id')) $this->skeleton->load('categories_id', $categories_id);

		self::register($categories_id, $this->skeleton);
	}

	public function is_active() {
		// if this product is disabled or inactive, hide it
		return $this->found() && !($this->is('disabled') || $this->is('inactive'));
	}

	public function is_viewable() {
		return $this->is_active();
	}

	public function id() {
		return $this->skeleton->get('categories_id');
	}

	public static function preload(Array $elements, Array $categories_ids=[]) {
		foreach ($elements as $element) {
			switch ($element) {
				case 'primary_containers':
					$qry = 'SELECT mpc.primary_category_id, mpc.container_type_id, mpc.categories_id, mct.name as container_type, mct.table_name, mpc.container_id, mpc.canonical, mpc.redirect, mpc.date_created FROM ck_merchandising_primary_categories mpc JOIN ck_merchandising_container_types mct ON mpc.container_type_id = mct.container_type_id';
					$criteria = [];

					if (!empty($cateogires_ids)) {
						$sel = new prepared_fields($categories_ids, prepared_fields::SELECT_QUERY);
						$qry .= ' WHERE mpc.categories_id IN ('.$sel->select_values().')';
						$criteria = $sel->parameters();
					}

					$pcs = prepared_query::fetch($qry, cardinality::SET, $criteria);

					foreach ($pcs as $pc) {
						self::$preloaded_data['primary_containers'][$pc['categories_id']] = $pc;
					}

					break;
			}
		}
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('category_header', [':categories_id' => $this->skeleton->get('categories_id'), ':languages_id' => $this->languages_id]);
			if (empty($header)) return;
		}
		else {
			$header = $this->skeleton->get('header');
			if (empty($header)) return;
			$this->skeleton->rebuild('header');
		}

		if (!empty($header['date_added']) && !($header['date_added'] instanceof DateTime)) $header['date_added'] = new DateTime($header['date_added']);
		if (!empty($header['last_modified']) && !($header['last_modified'] instanceof DateTime)) $header['last_modified'] = new DateTime($header['last_modified']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$header = self::fetch('category_header', [':categories_id' => $this->skeleton->get('categories_id'), ':languages_id' => $this->languages_id]);
		if (empty($header)) $header = [];
		$this->skeleton->load('header', $header);
		$this->normalize_header();
	}

	private function build_base_url() {
		if ($this->has_primary_container()) {
			$primary_category = $this->get_primary_container();
			if ($primary_category['container_type'] != 'Category' || $primary_category['container_id'] != $this->id()) {
				if ($primary_category['canonical']) {
					$container = ck_merchandising_container_manager::instantiate($primary_category['container_type_id'], $primary_category['container_id']);

					if ($container->is_active()) $url = $container->get_url($this);
				}
			}
		}
		if (empty($url) && !empty($this->get_header('topnav_redirect'))) $url = $this->get_header('topnav_redirect');

		$original_url = '/';
		if ($this->is('use_seo_urls') && !empty($this->get_header('seo_url_text'))) {
			$parts = [];
			if ($ancestors = $this->get_ancestors()) {
				foreach ($ancestors as $cat) {
					if ($cat->is('use_seo_urls') && !empty($cat->get_header('seo_url_parent_text'))) $parts[] = CK\fn::simple_seo($cat->get_header('seo_url_parent_text'));
					elseif ($cat->is('use_seo_urls') && !empty($cat->get_header('seo_url_text'))) $parts[] = CK\fn::simple_seo($cat->get_header('seo_url_text'));
				}
			}
			$parts = array_reverse($parts);
			$parts[] = CK\fn::simple_seo($this->get_header('seo_url_text')).'/cat-'.$this->id().'/';
			$original_url .= implode('/', $parts);
		}
		else $original_url .= CK\fn::simple_seo($this->get_header('categories_head_title_tag'), '-c-'.$this->get_cpath().'.html');
		$this->skeleton->load('original_url', $original_url);

		if (empty($url)) $url = $original_url;

		$this->skeleton->load('base_url', $url);
	}

	private function build_ancestors() {
		$ancestors = [];

		$parent_id = $this->get_header('parent_id');

		while (!empty($parent_id)) {
			// we don't need to worry about instantiating tons of objects, only one of two situations is likely:
			// * we're only dealing with the 3 or 4 ancestors to this category, and we'll likely need to use them all
			// * we're dealing with the full set of categories, so they'll already be instantiated by the time we get here
			$ancestor = new self($parent_id);
			$parent_id = $ancestor->get_header('parent_id');
			$ancestors[] = $ancestor;
		}

		$this->skeleton->load('ancestors', $ancestors);
	}

	private function build_children() {
		$headers = self::fetch('children', [':parent_id' => $this->skeleton->get('categories_id'), ':languages_id' => $this->languages_id]);

		$children = [];

		foreach ($headers as $header) {
			$skeleton = self::get_record($header['categories_id']); // if we've already instantiated it, well, oh well
			if (!$skeleton->built('header')) $skeleton->load('header', $header);

			$children[] = new self($header['categories_id'], $skeleton);
		}

		$this->skeleton->load('children', $children);
	}

	// we're relying on nextopia for this on the front end, so this is only for back end purposes, we're only interested in products that are
	// directly attached, not products attached to children categories
	private function build_listings() {
		$products_ids = self::fetch('listings', [':categories_id' => $this->skeleton->fetch('categories_id')]);
		$listings = [];

		foreach ($products_ids as $products_id) {
			$listings[] = new ck_product_listing($products_id);
		}

		$this->skeleton->load('listings', $listings);
	}

	private function build_primary_container() {
		if (!empty(self::$preloaded_data['primary_containers'][$this->id()])) $primary_category = self::$preloaded_data['primary_containers'][$this->id()];
		else $primary_category = self::fetch('primary_category', [':categories_id' => $this->id()]);

		if (!empty($primary_category)) {
			$primary_category['canonical'] = CK\fn::check_flag($primary_category['canonical']);
			$primary_category['redirect'] = CK\fn::check_flag($primary_category['redirect']);
			$primary_category['date_created'] = self::DateTime($primary_category['date_created']);
		}
		else $primary_category = [];

		$this->skeleton->load('primary_category', $primary_category);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function get_template($key=NULL) {
		// not yet implemented, but required by interface
	}

	public function get_schema($key=NULL) {
		// not yet implemented, but required by interface
	}

	public function get_base_url() {
		if (!$this->skeleton->built('base_url')) $this->build_base_url();
		return $this->skeleton->get('base_url');
	}
	// this is really only valuable when the category is being redirected
	public function get_original_url() {
		//original url is built in the base url method
		if (!$this->skeleton->built('original_url')) $this->build_base_url();
		return $this->skeleton->get('original_url');
	}

	public function has_ancestors() {
		if (!$this->skeleton->built('ancestors')) $this->build_ancestors();
		return $this->skeleton->has('ancestors');
	}

	public function get_ancestors() {
		if (!$this->has_ancestors()) return [];
		return $this->skeleton->get('ancestors');
	}

	public function get_taxonomy() {
		$taxonomy = [];
		if ($ancestors = $this->get_ancestors()) {
			$ancestors = array_reverse($ancestors);
			foreach ($ancestors as $cat) {
				$taxonomy[] = $cat->get_header('categories_name');
			}
		}

		$taxonomy[] = $this->get_header('categories_name');

		return $taxonomy;
	}

	public function get_progenitor() {
		if ($ancestors = $this->get_ancestors()) return array_reverse($ancestors)[0];
		else return $this;
	}

	public function has_children() {
		if (!$this->skeleton->built('children')) $this->build_children();
		return $this->skeleton->has('children');
	}

	public function get_children() {
		if (!$this->has_children()) return [];
		return $this->skeleton->get('children');
	}

	public function get_finder_subcategories() {
		$children = $this->get_children();

		$subcategories = ['shop_by' => [], 'applications' => [], 'combined' => []];

		foreach ($children as $cat) {
			if ($cat->is('disabled') || $cat->is('inactive') || $cat->is('product_finder_hide')) continue;

			$subcategories['combined'][] = $cat;

			// objects pass-by-ref - we're not duplicating memory by storing it multiple times
			if ($cat->is('finder_category')) $subcategories['shop_by'][] = $cat;
			else $subcategories['applications'][] = $cat;
		}

		return $subcategories;
	}

	public function has_listings() {
		if (!$this->skeleton->built('listings')) $this->build_listings();
		return $this->skeleton->has('listings');
	}

	public function get_listings() {
		if (!$this->has_listings()) return [];
		return $this->skeleton->get('listings');
	}

	public function get_products_ids_direct() {
		return prepared_query::keyed_set_group_value_fetch('SELECT DISTINCT ptc.products_id, ptc0.categories_id FROM products_to_categories ptc JOIN products_to_categories ptc0 ON ptc.products_id = ptc0.products_id JOIN products p ON ptc.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id JOIN categories c ON ptc0.categories_id = c.categories_id WHERE ptc.categories_id = :categories_id AND p.products_status = 1 AND psc.discontinued = 0 AND c.inactive = 0', 'products_id', 'categories_id', [':categories_id' => $this->id()]);
	}

	public function has_primary_container() {
		if (!$this->skeleton->built('primary_category')) $this->build_primary_container();
		return $this->skeleton->has('primary_category');
	}

	public function get_primary_container() {
		if (!$this->has_primary_container()) return NULL;
		return $this->skeleton->get('primary_category');
	}

	public function get_cpath() {
		$cpath = [];

		// underscore delimited list of category IDs, from highest level ancestor down to current

		if ($ancestors = $this->get_ancestors()) {
			$ancestors = array_reverse($ancestors);
			foreach ($ancestors as $ancestor) {
				$cpath[] = $ancestor->get_header('categories_id');
			}
		}

		$cpath[] = $this->skeleton->get('categories_id');

		return implode('_', $cpath);
	}

	public function get_id_path() {
		$path = [];
		$path[] = $this->id();

		if ($ancestors = $this->get_ancestors()) {
			foreach ($ancestors as $ancestor) {
				$path[] = $ancestor->id();
			}
		}

		return $path;
	}

	public function get_name_path() {
		$name_path = [];
		if ($this->has_ancestors()) {
			foreach ($this->get_ancestors() as $ancestor) {
				$name_path[] = $ancestor->get_header('categories_name');
			}
			$name_path = array_reverse($name_path);
		}
		$name_path[] = $this->get_header('categories_name');

		return implode('/', $name_path);
	}

	public function get_url(ck_merchandising_container_interface $context=NULL) {
		return $this->get_base_url();
	}

	public function url_is_correct() {
		$compare = parse_url($_SERVER['REQUEST_URI']);

		if ($this->get_url() != $compare['path']) return FALSE;
		//if ()
		else return TRUE;
	}

	public function redirect_if_necessary() {
		if (!$this->url_is_correct()) CK\fn::permanent_redirect($this->get_url());

		if (!$this->is_viewable()) {
			if ($this->has_ancestors()) {
				foreach ($this->get_ancestors() as $ancestor) {
					if ($ancestor->is_viewable()) CK\fn::redirect_and_exit($ancestor->get_url());
				}
			}

			CK\fn::redirect_and_exit('/');
		}
	}

	public function url_is_canonical() {
		if ($this->get_url() == $this->get_canonical_url()) return TRUE;
		else return FALSE;
	}

	public function get_canonical_url() {
		$use_canonical = FALSE;
		// follow to the ultimate last referenced canonical category

		if ($this->has_primary_container()) {
			$primary_category = $this->get_primary_container();
			if ($primary_category['container_type'] != 'Category' || $primary_category['container_id'] != $this->id()) {
				if ($primary_category['canonical']) {
					$container = ck_merchandising_container_manager::instantiate($primary_category['container_type_id'], $primary_category['container_id']);

					if ($container->is_active()) return $container->get_canonical_url();
				}
			}
		}
		
		$category = $this;

		while (!empty($category->get_header('canonical_category_id'))) {
			$category = new ck_listing_category($category->get_header('canonical_category_id'));
			$use_canonical = TRUE;
			$canonical_url = $category->get_url();
		}

		if (!empty($use_canonical)) return $canonical_url;
		else return $this->get_url();
	}

	public function get_title() {}
	public function get_meta_description() {}
	public function activate() {}
	public function deactivate() {}
	public function set_as_primary_container($canonical, $redirect) {}
	public function remove_as_primary_container() {}

	public function set_primary_merchandising_container($container_type_id, $container_id, $canonical, $redirect) {
		$savepoint = self::transaction_begin();

		try {
			$this->remove_primary_merchandising_container();

			prepared_query::execute('INSERT INTO ck_merchandising_primary_categories (categories_id, container_type_id, container_id, canonical, redirect) VALUES (:categories_id, :container_type_id, :container_id, :canonical, :redirect)', [':categories_id' => $this->id(), ':container_type_id' => $container_type_id, ':container_id' => $container_id, ':canonical' => $canonical, ':redirect' => $redirect]);

			$this->update(['inactive' => 1]);
			self::rebuild_topnav_structure();

			self::transaction_commit($savepoint);
		}
		catch (CKListingCategoryException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKListingCategoryException('Error setting as primary container: '.$e->getMessage());
		}
	}
	public function remove_primary_merchandising_container() {
		$savepoint = self::transaction_begin();

		try {
			prepared_query::execute('DELETE FROM ck_merchandising_primary_categories WHERE categories_id = :categories_id', [':categories_id' => $this->id()]);

			$this->update(['inactive' => 0]);
			self::rebuild_topnav_structure();

			self::transaction_commit($savepoint);
		}
		catch (CKListingCategoryException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKListingCategoryException('Error deleting previous primary merchandising category: '.$e->getMessage());
		}
	}

	public function category_is_ancestor($category) {
		if (!is_numeric($category) && (!$category instanceof self)) return NULL;

		if (is_numeric($category)) $categories_id = $category;
		else $categories_id = $category->id();

		foreach ($this->get_ancestors() as $ancestor) {
			if ($ancestor->id() == $categories_id) return TRUE;
		}
		return FALSE;
	}

	public function is_direct_product_parent() {
		// if all we want to know if is if this category has any listings without building them, we'll need to use this
		if ($this->skeleton->built('listings')) return $this->has_listings();
		else {
			$products_ids = self::fetch('listings', [$this->skeleton->get('categories_id')]);
			return !empty($products_ids);
		}
	}

	public function is_redundant_direct() {
		$redundant = TRUE;

		if ($products = $this->get_products_ids_direct()) {
			foreach ($products as $products_id => $categories) {
				if (count($categories) == 1 && $categories[0] == $this->id()) $redundant = FALSE;
			}
		}

		if ($redundant) {
			$subcat_ids = prepared_query::fetch('SELECT DISTINCT categories_id FROM categories WHERE parent_id = :parent_id AND inactive = 0', cardinality::COLUMN, [':parent_id' => $this->id()]);

			foreach ($subcat_ids as $categories_id) {
				$cat = new self($categories_id);
				if (!$cat->is_redundant_direct()) {
					$redundant = FALSE;
					break;
				}
			}
		}

		return $redundant;
	}

	public static function url($name, $id) {
		return '/'.CK\fn::simple_seo($name, '-c-'.$id.'.html');
	}

	public static function get_flat_category_list() {
		if ($headers = self::fetch('category_header_list', [':languages_id' => $_SESSION['languages_id']])) {
			$categories = array();
			foreach ($headers as $header) {
				$skeleton = self::get_record($header['categories_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$categories[] = new self($header['categories_id'], $skeleton);
			}
			return $categories;
		}
		else return [];
	}

	public static function get_topnav_category_list() {
		if ($headers = self::fetch('topnav_category_header_list', [':languages_id' => $_SESSION['languages_id']])) {
			$categories = array();
			foreach ($headers as $header) {
				$skeleton = self::get_record($header['categories_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$categories[] = new self($header['categories_id'], $skeleton);
			}
			return $categories;
		}
		else return [];
	}

	public static function get_select_navigator_category_list() {
		$selections = [];
		$top_level = [];

		if ($categories = self::query_fetch('SELECT * FROM ckv_category_selector_summary', cardinality::SET, [])) {
			foreach ($categories as $category) {
				$cname = $category['pcount']?$category['categories_name'].'*':$category['categories_name'];
				if (empty($category['parent_id'])) {
					$top_level[] = ['id' => $category['categories_id'], 'name' => $cname];
				}
				else {
					if (empty($selections[$category['parent_id']])) $selections[$category['parent_id']] = [];
					$selections[$category['parent_id']][] = ['id' => $category['categories_id'], 'name' => $cname];
				}
			}
		}

		return ['top_level' => $top_level, 'selections' => $selections];
	}

	public static function get_category_by_product_finder_key($product_finder_key) {
		if ($product_finder_key && ($header = self::fetch('category_header_by_pfkey', [':product_finder_key' => $product_finder_key, ':languages_id' => $_SESSION['languages_id']]))) {
			$skeleton = self::get_record($header['categories_id']); // if we've already instantiated it, well, oh well
			if (!$skeleton->built('header')) $skeleton->load('header', $header);

			return new self($header['categories_id'], $skeleton);
		}
		else return NULL;
	}

	private static $cpath = [];
	private static $page_category_id = NULL;

	public static function set_cpath($cpath) {
		if (is_scalar($cpath)) return self::parse_cpath($cpath);
		else return self::$cpath = $cpath;
	}

	public static function parse_cpath($cpath=NULL) {
		// if we've already parsed it just use what we've already done, unless we're passing in a new value
		if (empty($cpath) && !empty(self::$cpath)) return self::$cpath;

		// if we haven't passed in the cpath, just grab it, it'll usually come from GET but no reason to limit
		$cpath = !empty($cpath)?$cpath:(isset($_REQUEST['cPath'])?$_REQUEST['cPath']:NULL);

		// split on underscore, and remove non-numeric entries
		$cpath1 = array_filter(explode('_', $cpath), 'is_numeric');

		// deduplicate, according to comments on http://php.net/manual/en/function.array-unique.php this method is faster than array_unique or array_flip methods
		$cpath2 = array();
		foreach ($cpath1 as $key => $val) {
			$cpath2[$val] = TRUE;
		}
		self::$cpath = array_keys($cpath2);

		return self::$cpath;
	}

	public static function page_category($cpath=NULL) {
		// if we've already figured the page category, just use what we've already done
		if (!empty(self::$page_category_id)) return self::$page_category_id;

		// get the last element in the cpath, which is our current category selection
		self::$page_category_id = self::parse_cpath()[count(self::$cpath)-1];

		return self::$page_category_id;
	}

	public static function get_all() {
		// maybe in the future this returns each object instead
		$categories = [];
		$category_ids = prepared_query::fetch('SELECT c.categories_id FROM categories c LEFT JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE c.inactive != 1 ORDER BY cd.categories_name');
		foreach ($category_ids as $categories_id) $categories[] = new ck_listing_category($categories_id['categories_id']);
		return $categories;
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public function update(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			self::query_execute('UPDATE categories SET '.$params->update_cols(TRUE).' WHERE categories_id = :categories_id', cardinality::NONE, $params->query_vals(['categories_id' => $this->id()], TRUE));

			$this->skeleton->rebuild();

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKListingCategoryException('Error updating category: '.$e->getMessage());
		}
	}

	public function add_category_discount($discount) {
		$discount_pctg = (1 - ($discount['discount'] / 100));
		self::execute('add_category_discount', [':customers_id' => $discount['customer_id'], ':discount_pctg' => $discount_pctg, ':categories_id' => $this->skeleton->get('categories_id')]);

		if ($this->has_children()) {
			foreach ($this->get_children() as $category) {
				$category->add_category_discount($discount);
			}
		}
	}

	public function remove_category_discount($customers_id) {
		prepared_query::execute('DELETE itc FROM ipn_to_customers itc JOIN products p ON itc.stock_id = p.stock_id JOIN products_to_categories ptc ON p.products_id = ptc.products_id WHERE itc.customers_id = :customers_id AND ptc.categories_id = :categories_id', [':customers_id' => $customers_id, ':categories_id' => $this->skeleton->get('categories_id')]);

		if ($this->has_children()) {
			foreach ($this->get_children() as $category) {
				$category->remove_category_discount($customers_id);
			}
		}
	}

	public static function rebuild_topnav_structure($preserve_headers=FALSE) {
		unset($GLOBALS['ck_keys']->template_cache);

		$topnav_structure = [];

		$parents = [];
		$tlc = [];
		$flc = [];

		$allparents = [];

		$categories = self::get_topnav_category_list();

		$headers = [];

		foreach ($categories as $category) {
			$header = $category->get_header();
			$parent_id = $header['parent_id'];

			if ($preserve_headers) $headers[] = $header;

			if (!$category->has_ancestors()) {
				// top level category
				$tlc[$category->id()] = count($topnav_structure);
				$topnav_structure[] = ['link' => $category->get_url(), 'title' => $header['categories_name']];
			}
			elseif (isset($tlc[$parent_id])) {
				// first level dropdown
				$parents[$category->id()] = $parent_id;

				if (!isset($topnav_structure[$tlc[$parent_id]]['cat_level_2'])) $topnav_structure[$tlc[$parent_id]]['cat_level_2'] = ['categories' => []];

				$flc[$category->id()] = count($topnav_structure[$tlc[$parent_id]]['cat_level_2']['categories']);

				$topnav_structure[$tlc[$parent_id]]['cat_level_2']['categories'][] = ['link' => $category->get_url(), 'title' => $header['categories_name']];
			}

			$allparents[$category->id()] = $parent_id;
		}

		// we have to do this as a separate step, because we can trust all of the second level categories to come after the top level categories (parent_id = 0), but
		// we can't trust all of the third level categories to come after the second level categories

		$slparents = [];

		foreach ($categories as $category) {
			$header = $category->get_header();
			$parent_id = $header['parent_id'];

			if (isset($parents[$parent_id])) {
				// second level popout

				if (!isset($topnav_structure[$tlc[$parents[$parent_id]]]['cat_level_2']['categories'][$flc[$parent_id]]['cat_level_3'])) $topnav_structure[$tlc[$parents[$parent_id]]]['cat_level_2']['categories'][$flc[$parent_id]]['cat_level_3'] = ['categories' => []];

				$topnav_structure[$tlc[$parents[$parent_id]]]['cat_level_2']['categories'][$flc[$parent_id]]['cat_level_3']['categories'][] = ['link' => $category->get_url(), 'title' => $header['categories_name']];
			}
			// else, skip it
		}

		$GLOBALS['ck_keys']->{'template_cache.topnav_categories'} = $topnav_structure;
		$GLOBALS['ck_keys']->{'template_cache.category_allparents'} = $allparents;

		return $headers;
	}

	public static function refresh_category_discounts() {
		if ($discounts = self::fetch('active_category_discounts', [])) {
			foreach ($discounts as $discount) {
				$category = new self($discount['category_id']);
				// remove the current discounts - if an item was removed from a category, this will catch it
				$category->remove_category_discount($discount['customer_id']);
				// add all discounts back with updated IPN pricing
				$category->add_category_discount($discount);
			}
		}
	}

	public static function expire_category_discounts() {
		if ($discounts = self::fetch('expired_category_discounts', [])) {
			foreach ($discounts as $discount) {
				// removing it at the customer level then removes all IPN references
				$customer = new ck_customer2($discount['customer_id']);
				$customer->remove_category_discount($discount['id']);
			}
		}
	}
}

class CKListingCategoryException extends CKMasterArchetypeException {
}
?>