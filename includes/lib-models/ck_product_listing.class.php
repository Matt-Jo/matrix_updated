<?php
class ck_product_listing extends ck_archetype implements ck_merchandising_container_interface {

	public $languages_id;

	protected static $skeleton_type = 'ck_product_listing_type';

	private $cross_sells = [];
	private $also_purchased = [];
	private $recommended = [];

	private static $content_review_statuses = [
		0 => 'No Issues Found',
		1 => 'Issue(s) Found',
		2 => 'Fixed',
		3 => 'Will Not Fix'
	];

	private static $preloaded_data = [
		'headers' => [],
		'images' => [],
		'primary_containers' => [],
		'attributes' => [],
	];

	protected static $queries = [
		'product_header' => [
			'qry' => 'SELECT p.products_id, p.stock_id, p.salsify_id, IFNULL(psc.image_reference, psc.stock_id) as image_reference_stock_id, pd.products_name, pd.products_head_title_tag, pd.products_head_desc_tag, pd.products_description, p.products_model, pd.products_url, pd.products_google_name, pd.products_ebay_name, psc.stock_price, psc.dealer_price, p.products_tax_class_id, p.products_date_added, p.products_date_available, p.manufacturers_id, psc.is_bundle, psc.bundle_price_flows_from_included_products, psc.bundle_price_modifier, psc.bundle_price_signum, vtsi.always_avail as always_available, vtsi.lead_time, psc.stock_quantity, psc.serialized, psc.ca_allocated_quantity, psc.max_displayed_quantity, psc.conditions, psc.stock_weight, c.conditions_name, psc.on_order, psc.warranty_id, w.warranty_name, psc.freight, psc.discontinued, psc.dlao_product, p.products_status, p.broker_status, p.level_1_product, p.canonical_type, p.canonical_id, p.use_seo_urls, p.seo_url_text FROM products_description pd JOIN products p ON pd.products_id = p.products_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN warranties w ON psc.warranty_id = w.warranty_id LEFT JOIN conditions c ON psc.conditions = c.conditions_id WHERE p.products_id = ? AND pd.language_id = ?',
			'cardinality' => cardinality::ROW,
		],

		'images' => [
			'qry' => 'SELECT image as products_image, image_med as products_image_med, image_lrg as products_image_lrg, image_sm_1 as products_image_sm_1, image_xl_1 as products_image_xl_1, image_sm_2 as products_image_sm_2, image_xl_2 as products_image_xl_2, image_sm_3 as products_image_sm_3, image_xl_3 as products_image_xl_3, image_sm_4 as products_image_sm_4, image_xl_4 as products_image_xl_4, image_sm_5 as products_image_sm_5, image_xl_5 as products_image_xl_5, image_sm_6 as products_image_sm_6, image_xl_6 as products_image_xl_6 FROM products_stock_control_images WHERE stock_id = :stock_id',
			'cardinality' => cardinality::ROW,
		],

		'specials_price' => [
			'qry' => 'SELECT * FROM specials WHERE products_id = ? AND status = 1 ORDER BY specials_id DESC LIMIT 1',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'all_specials' => [
			'qry' => 'SELECT * FROM specials WHERE products_id = :products_id ORDER BY status DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'categories' => [
			'qry' => 'SELECT categories_id FROM products_to_categories WHERE products_id = ? ORDER BY categories_id DESC',
			'cardinality' => cardinality::COLUMN,
			'stmt' => NULL
		],

		'parent_listings' => [
			'qry' => 'SELECT p.products_id, pa.recommended, pa.included, pa.use_custom_name, pa.custom_name, pa.use_custom_price, pa.custom_price, pa.bundle_quantity, pa.use_custom_desc, pa.custom_desc, pad.default_desc, pad.default_price, pa.allow_mult_opts FROM product_addons pa JOIN products p ON pa.product_id = p.products_id LEFT JOIN product_addon_data pad ON pa.product_addon_id = pad.product_id WHERE pa.product_addon_id = :products_id ORDER BY pa.included DESC, pa.recommended DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'options' => [
			'qry' => 'SELECT pa.product_addon_id as products_id, pa.recommended, pa.included, pa.use_custom_name, pa.custom_name, pa.use_custom_price, pa.custom_price, pa.bundle_quantity, pa.use_custom_desc, pa.custom_desc, pd.products_name, pad.default_desc, psc.stock_price, p.stock_id, psc.always_available, pad.default_price, psc.max_displayed_quantity, pa.allow_mult_opts FROM product_addons pa JOIN products p ON pa.product_addon_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id JOIN products_description pd ON pa.product_addon_id = pd.products_id LEFT JOIN product_addon_data pad ON pa.product_addon_id = pad.product_id WHERE pa.product_id = ? AND (pa.included = 1 OR psc.discontinued = 0) ORDER BY pa.included DESC, pa.recommended DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		// this query is currently focused around what's needed for the product info page, it'll likely need to be fleshed out for more generic usage
		'attributes' => [
			'qry' => 'SELECT DISTINCT ak.description as key_name, ak.attribute_key_id, a.attribute_key, a.value, ak.itemprop FROM ck_attributes a JOIN ck_attribute_keys ak ON a.attribute_key_id = ak.attribute_key_id WHERE a.internal = 0 AND a.products_id = ?',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'cross_sells' => [
			'qry' => 'SELECT DISTINCT px.xsell_id as products_id FROM products_xsell px JOIN products p ON px.xsell_id = p.products_id AND p.products_status = 1 WHERE px.products_id = ? LIMIT 3',
			'cardinality' => cardinality::COLUMN,
			'stmt' => NULL
		],

		'also_purchased' => [
			'qry' => 'SELECT DISTINCT p.products_id FROM orders_products op JOIN orders_products op1 ON op.orders_id = op1.orders_id AND op.products_id != op1.products_id JOIN orders o ON op.orders_id = o.orders_id JOIN products p ON op1.products_id = p.products_id AND p.products_status = 1 JOIN products_stock_control psc ON p.stock_id = psc.stock_id AND psc.dlao_product = 0 WHERE op.products_id = ? ORDER BY o.date_purchased DESC LIMIT 3',
			'cardinality' => cardinality::COLUMN,
			'stmt' => NULL
		],

		'notifications' => [
			'qry' => 'SELECT * FROM products_notifications WHERE products_id = ?',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'content_reviews' => [
			'qry' => 'SELECT DISTINCT cr.id as content_review_id, cr.notice_date, cr.admin_id, a.admin_firstname as reporter_first_name, a.admin_lastname as reporter_last_name, a.admin_email_address as reporter_email_address, cr.element_id, cre.name as element, cr.image_slot, cr.reason_id, crr.name as reason, cr.status, cr.notes, cr.responder_id, ar.admin_firstname as responder_first_name, ar.admin_lastname as responder_last_name, ar.admin_email_address as responder_email_address, cr.response_date FROM content_reviews cr JOIN content_review_elements cre ON cr.element_id = cre.id JOIN content_review_reasons crr ON cr.reason_id = crr.id LEFT JOIN admin a ON cr.admin_id = a.admin_id LEFT JOIN admin ar ON cr.responder_id = ar.admin_id WHERE cr.product_id = :products_id',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'manufacturer' => [
			'qry' => 'SELECT manufacturers_name FROM manufacturers WHERE manufacturers_id = ?',
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'upcs' => [
			'qry' => "SELECT upc_assignment_id, target_resource, target_resource_id, upc, unit_of_measure, uom_description, provenance, purpose, created_date, active FROM ck_upc_assignments WHERE (target_resource_id IS NULL AND stock_id = :stock_id) OR (target_resource = 'products' AND target_resource_id = :products_id) ORDER BY target_resource_id DESC, CASE WHEN unit_of_measure = 1 THEN 0 WHEN unit_of_measure = 0 THEN 9999 ELSE unit_of_measure END ASC",
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'primary_container' => [
			'qry' => 'SELECT mpc.primary_container_id, mpc.container_type_id, mpc.products_id, mct.name as container_type, mct.table_name, mpc.container_id, mpc.canonical, mpc.redirect, mpc.date_created FROM ck_merchandising_primary_containers mpc JOIN ck_merchandising_container_types mct ON mpc.container_type_id = mct.container_type_id WHERE mpc.products_id = :products_id',
			'cardinality' => cardinality::ROW
		],
	];

	const ACTIVE_CONTEXT_STANDARD = 'STANDARD';
	const ACTIVE_CONTEXT_BROKER = 'BROKER';

	private static $active_context = self::ACTIVE_CONTEXT_STANDARD;

	private static $active_context_keys = [
		self::ACTIVE_CONTEXT_STANDARD => 'products_status',
		self::ACTIVE_CONTEXT_BROKER => 'broker_status',
	];

	public static function set_active_context($context) {
		self::$active_context = $context;
	}

	public static function get_active_context() {
		return self::$active_context;
	}

	public static function active_context_is($context) {
		return self::$active_context===$context;
	}

	public static function get_active_context_key() {
		return self::$active_context_keys[self::get_active_context()];
	}

	public function __construct($products_id, ck_product_listing_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($products_id);

		$this->languages_id = $_SESSION['languages_id'];

		if (!$this->skeleton->built('products_id')) $this->skeleton->load('products_id', $products_id);

		self::register($products_id, $this->skeleton);
	}

	public function is_active($context=NULL) {
		if (!$this->found()) return FALSE;

		if (empty($context)) return $this->get_header(self::get_active_context_key())!=0;
		else return $this->get_header(self::$active_context_keys[$context]);
	}

	private $viewable_state;
	private $is_viewable;

	public function is_viewable() {
		if (is_bool($this->is_viewable)) return $this->is_viewable;
		/**/
		$viewable = $this->get_viewable_state();

		// globally viewable
		if ($viewable == 2) $this->is_viewable = TRUE;
		// admin only viewable
		elseif ($viewable == 1 && @$_SESSION['admin'] == 'true') $this->is_viewable = TRUE;
		// back end only viewable
		elseif ($viewable == 0 && $_SESSION['current_context'] == 'backend') $this->is_viewable = TRUE;
		else $this->is_viewable = FALSE;

		return $this->is_viewable;
		/**/
		/** /
		// if this item has no data, don't show it
		if (empty($this->get_header())) return FALSE;

		// if we're on the back-end, show it
		elseif ($_SESSION['current_context'] == 'backend') return TRUE;

		// if we're on the front end and the product is turned off, don't show it
		elseif (!$this->is_active()) return FALSE;

		// if we're on the front end, the product is turned on and we're an admin, show it
		elseif (@$_SESSION['admin'] == 'true') return TRUE;

		// if we're on the front end, the product is turned on, we're not an admin and the product is admin-only, don't show it
		elseif ($this->get_header('dlao_product') == 1) return FALSE;

		// if we're on the front end, the product is turned on, we're not an admin and the product is *not* admin-only, show it
		else return TRUE;
		/ **/
	}

	private $is_cartable;

	public function is_cartable() {
		if (is_bool($this->is_cartable)) return $this->is_cartable;
		$viewable = $this->get_viewable_state();

		// globally viewable
		if ($viewable == 2) $this->is_cartable = TRUE;
		// admin only viewable
		elseif (@$_SESSION['admin'] == 'true' || $_SESSION['current_context'] == 'backend') $this->is_cartable = TRUE;
		else $this->is_cartable = FALSE;

		return $this->is_cartable;
	}

	public function get_viewable_state() {
		if (!is_null($this->viewable_state)) return $this->viewable_state;
		return $this->viewable_state = prepared_query::fetch('SELECT viewable_state FROM ckv_product_viewable WHERE products_id = :products_id', cardinality::SINGLE, [':products_id' => $this->id()]);
	}

	public function id() {
		return $this->skeleton->get('products_id');
	}

	public function debug() {
		$this->skeleton->debug();
	}

	public static function preload(Array $elements, Array $products_ids=[]) {
		$qry_addendum = '';
		$criteria = [];

		if (!empty($products_ids)) {
			$sel = new prepared_fields($products_ids, prepared_fields::SELECT_QUERY);
			$qry_addendum = ' IN ('.$sel->select_values().')';
			$criteria = $sel->parameters();
		}

		foreach ($elements as $element) {
			switch ($element) {
				case 'headers':
					$qry = 'SELECT p.products_id, p.stock_id, IFNULL(psc.image_reference, psc.stock_id) as image_reference_stock_id, pd.products_name, pd.products_head_title_tag, pd.products_head_desc_tag, pd.products_description, p.products_model, pd.products_url, pd.products_google_name, psc.stock_price, psc.dealer_price, psc.wholesale_high_price, psc.wholesale_low_price, p.products_tax_class_id, p.products_date_added, p.products_date_available, p.manufacturers_id, psc.is_bundle, psc.bundle_price_flows_from_included_products, psc.bundle_price_modifier, psc.bundle_price_signum, vtsi.always_avail as always_available, vtsi.lead_time, psc.stock_quantity, psc.serialized, psc.ca_allocated_quantity, psc.max_displayed_quantity, psc.conditions, psc.stock_weight, c.conditions_name, psc.on_order, psc.warranty_id, w.warranty_name, psc.freight, psc.discontinued, psc.dlao_product, p.products_status, p.broker_status, p.level_1_product, p.canonical_type, p.canonical_id, p.use_seo_urls, p.seo_url_text FROM products_description pd JOIN products p ON pd.products_id = p.products_id LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN warranties w ON psc.warranty_id = w.warranty_id LEFT JOIN conditions c ON psc.conditions = c.conditions_id WHERE pd.language_id = ?';

					array_unshift($criteria, $_SESSION['languages_id']);

					$qry_bridge = !empty($qry_addendum)?' AND p.products_id':'';

					$hds = prepared_query::fetch($qry.$qry_bridge.$qry_addendum, cardinality::SET, $criteria);

					foreach ($hds as $hd) {
						self::$preloaded_data['headers'][$hd['products_id']] = $hd;
					}

					break;
				case 'images':
					$qry = 'SELECT DISTINCT p.products_id, psci.image as products_image, psci.image_med as products_image_med, psci.image_lrg as products_image_lrg, psci.image_sm_1 as products_image_sm_1, psci.image_xl_1 as products_image_xl_1, psci.image_sm_2 as products_image_sm_2, psci.image_xl_2 as products_image_xl_2, psci.image_sm_3 as products_image_sm_3, psci.image_xl_3 as products_image_xl_3, psci.image_sm_4 as products_image_sm_4, psci.image_xl_4 as products_image_xl_4, psci.image_sm_5 as products_image_sm_5, psci.image_xl_5 as products_image_xl_5, psci.image_sm_6 as products_image_sm_6, psci.image_xl_6 as products_image_xl_6 FROM products_stock_control_images psci JOIN products_stock_control psc ON (psci.stock_id = psc.stock_id AND psc.image_reference IS NULL) OR (psci.stock_id = psc.image_reference) JOIN products p ON psc.stock_id = p.stock_id';

					$qry_bridge = !empty($qry_addendum)?' AND p.products_id':'';

					$imgs = prepared_query::fetch($qry.$qry_bridge.$qry_addendum, cardinality::SET, $criteria);

					foreach ($imgs as $img) {
						self::$preloaded_data['images'][$img['products_id']] = $img;
					}

					break;
				case 'attributes':
					$qry = 'SELECT DISTINCT a.products_id, ak.description as key_name, ak.attribute_key_id, a.attribute_key, a.value, ak.itemprop FROM ck_attributes a JOIN ck_attribute_keys ak ON a.attribute_key_id = ak.attribute_key_id WHERE a.internal = 0';

					$qry_bridge = !empty($qry_addendum)?' AND a.products_id':'';

					$attrs = prepared_query::fetch($qry.$qry_bridge.$qry_addendum, cardinality::SET, $criteria);

					foreach ($attrs as $attr) {
						if (empty(self::$preloaded_data['attributes'][$attr['products_id']])) self::$preloaded_data['attributes'][$attr['products_id']] = [];
						self::$preloaded_data['attributes'][$attr['products_id']][] = $attr;
					}
					
					break;
				case 'primary_containers':
					$qry = 'SELECT mpc.primary_container_id, mpc.container_type_id, mpc.products_id, mct.name as container_type, mct.table_name, mpc.container_id, mpc.canonical, mpc.redirect, mpc.date_created FROM ck_merchandising_primary_containers mpc JOIN ck_merchandising_container_types mct ON mpc.container_type_id = mct.container_type_id';

					$qry_bridge = !empty($qry_addendum)?' WHERE mpc.products_id':'';

					$pcs = prepared_query::fetch($qry.$qry_bridge.$qry_addendum, cardinality::SET, $criteria);

					foreach ($pcs as $pc) {
						self::$preloaded_data['primary_containers'][$pc['products_id']] = $pc;
					}

					break;
			}
		}
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_header() {
		if (!empty(self::$preloaded_data['headers'][$this->id()])) {
			$header = self::$preloaded_data['headers'][$this->id()];
			unset(self::$preloaded_data['headers'][$this->id()]);
		}
		else $header = self::fetch('product_header', [$this->id(), $this->languages_id]);

		if ($header) $this->skeleton->load('header', $header);
	}

	private function build_base_url() {
		$url = NULL;

		if ($this->has_primary_container()) {
			$primary_container = $this->get_primary_container();

			if ($primary_container['container_type'] != 'Product Listing' || $primary_container['container_id'] != $this->id()) {
				if ($primary_container['redirect']) {
					$container = ck_merchandising_container_manager::instantiate($primary_container['container_type_id'], $primary_container['container_id']);
					if ($container->is_active()) $url = $container->get_url($this);
				}
			}
		}

		if (empty($url) && $this->get_ipn()->has_primary_container()) {
			$primary_container = $this->get_ipn()->get_primary_container();

			if ($primary_container['container_type'] != 'Product Listing' || $primary_container['container_id'] != $this->id()) {
				if ($primary_container['redirect']) {
					$container = ck_merchandising_container_manager::instantiate($primary_container['container_type_id'], $primary_container['container_id']);
					if ($container->is_active()) $url = $container->get_url($this);
				}
			}
		}

		if (empty($url)) {
			$url = '/';
			if ($this->is('use_seo_urls') && !empty($this->get_header('seo_url_text'))) {
				$parts = [];
				if ($cats = $this->get_categories()) {
					$cats = array_reverse($cats);
					$cat = $cats[0];

					if ($cat->is('use_seo_urls') && !empty($cat->get_header('seo_url_parent_text'))) $parts[] = CK\fn::simple_seo($cat->get_header('seo_url_parent_text'));
					elseif ($cat->is('use_seo_urls') && !empty($cat->get_header('seo_url_text'))) $parts[] = CK\fn::simple_seo($cat->get_header('seo_url_text'));

					if ($ancestors = $cat->get_ancestors()) {
						foreach ($ancestors as $cat) {
							if ($cat->is('use_seo_urls') && !empty($cat->get_header('seo_url_parent_text'))) $parts[] = CK\fn::simple_seo($cat->get_header('seo_url_parent_text'));
							elseif ($cat->is('use_seo_urls') && !empty($cat->get_header('seo_url_text'))) $parts[] = CK\fn::simple_seo($cat->get_header('seo_url_text'));
						}
					}
					$parts = array_reverse($parts);
				}
				$parts[] = CK\fn::simple_seo($this->get_header('seo_url_text'));
				$url .= implode('/', $parts).'/pro-'.$this->id().'/';
			}
			else {
				$url .= CK\fn::simple_seo($this->get_header('products_name'), '-p-'.$this->id().'.html');
			}
		}

		$this->skeleton->load('base_url', $url);
	}

	private function build_template() {
		$template = $this->get_thin_template();

		$template['schema'] = $this->get_schema();

		$images = $this->get_image();

		if (!empty($images['products_image_lrg'])) {
			$template['has_img?'] = 1;
			$template['img'] = $images['products_image_lrg'];
			$template['thumb'] = $images['products_image'];

			$template['img_thumbs'] = [];

			for ($i=1; $i<=6; $i++) {
				if (empty($images['products_image_sm_'.$i]) || empty($images['products_image_xl_'.$i])) continue;
				$template['has_carousel?'] = 1;

				$template['img_thumbs'][] = ['large_img' => $images['products_image_xl_'.$i], 'thumb_img' => $images['products_image_sm_'.$i]];
			}
		}

		if ($this->has_attributes()) $template['attributes'] = $this->get_attributes();

		if (!empty($template['options?'])) {
			$template['options?'] = 1;
			$template['has_options?'] = 1;
			if ($this->has_options('included')) {
				$template['included_options'] = ['options' => []];
				foreach ($this->get_included_options() as $option) {
					if (!$option['listing']->is_viewable()) continue;

					$option['listing_url'] = $option['listing']->get_url();
					$option['qty?'] = max($option['listing']->get_inventory('display_available'), 0);

					$template['included_options']['options'][] = $option;
				}
			}
			if ($this->has_options('extra')) {
				$template['extra_options'] = ['options' => []];
				foreach ($this->get_extra_options() as $option) {
					if (!$option['listing']->is_viewable()) continue;

					$option['listing_url'] = $option['listing']->get_url();
					$option['qty?'] = max($option['listing']->get_inventory('display_available'), 0);

					$template['extra_options']['options'][] = $option;
				}
			}
		}

		if ($this->has_cross_sells()) {
			$cross_sells = $this->get_cross_sells();
			$template['cross_sell_products'] = ['products' => []];
			foreach ($cross_sells as $xsell_product) {
				$prod = ['url' => $xsell_product->get_url(), 'img' => $xsell_product->get_image('products_image_med'), 'safe_name' => htmlspecialchars($xsell_product->get_header('products_name')), 'name' => $xsell_product->get_header('products_name'), 'display_price' => CK\text::monetize($xsell_product->get_price('display'))];

				if ($xsell_product->get_price('reason') == 'special') {
					$prod['original_price?'] = CK\text::monetize($xsell_product->get_price('original'));
				}

				$template['cross_sell_products']['products'][] = $prod;
			}
		}

		if ($this->has_also_purchased()) {
			$also_purchased = $this->get_also_purchased();
			$template['also_purchased_products'] = ['products' => []];
			foreach ($also_purchased as $ap_product) {
				$template['also_purchased_products']['products'][] = ['url' => $ap_product->get_url(), 'img' => $ap_product->get_image('products_image_med'), 'safe_name' => htmlspecialchars($ap_product->get_header('products_name')), 'name' => $ap_product->get_header('products_name')];
			}
		}

		$this->skeleton->load('template', $template);
	}

	private function build_thin_template() {
		if (!empty($_SESSION['cart'])) $cart_product = $_SESSION['cart']->get_universal_products($this->id());
		else $cart_product = NULL;

		$header = $this->get_header();
		$inventory = $this->get_inventory();
		$prices = $this->get_price();
		$images = $this->get_image();

		$template = [];

		$template['id'] = $this->id();
		$template['stock_id'] = $header['stock_id'];
		$template['name'] = $header['products_name'];
		$template['name_attr'] = htmlspecialchars($header['products_name']);
		$template['model_num'] = $header['products_model'];
		$template['weight'] = $this->get_total_weight();
		$template['reg_price'] = CK\text::monetize($prices['original']);
		$template['price_num'] = $prices['display'];
		$template['price'] = CK\text::monetize($prices['display']);
		$template['pct_off'] = round((1 - @($prices['display']/$prices['original'])) * 100);
		$template['meta_condition'] = $this->get_condition('meta');
		$template['condition'] = $this->get_condition();
		$template['header_condition'] = $header['conditions'];
		$template['display_available'] = max($inventory['display_available'], 0);
		$template['display_available_num'] = max($inventory['display_available_num'], 0);
		$template['cart_quantity'] = !empty($cart_product)?$cart_product['quantity']:0;
		$template['availability'] = $inventory['display_available_num']>0?'InStock':'OutOfStock';
		$template['description'] = stripslashes($header['products_description']);
		$template['safe_short_description'] = htmlspecialchars(strip_tags($header['products_head_desc_tag']));
		$template['short_description'] = $header['products_head_desc_tag'];
		$template['safe_breadcrumbs'] = htmlspecialchars(strip_tags($GLOBALS['breadcrumb']->trail()));
		$template['url'] = $this->get_url();
		$template['lead_time'] = $header['lead_time'];

		if ($this->is('discontinued')) {
			$template['is_discontinued?'] = 1;
			$template['discontinued'] = 1;
		}
		else $template['discontinued'] = 0;

		if ($header['always_available'] == 1) $template['always_available?'] = 1;

		if (!empty($images['products_image_lrg'])) {
			$template['has_img?'] = 1;
			$template['img'] = $images['products_image_lrg'];
			$template['thumb'] = $images['products_image'];

			$imgmeta = pathinfo($images['products_image_lrg']);
			$img = $imgmeta['dirname'].'/'.$imgmeta['filename'];
			$template['optimg'] = $img.'.webp?optimg';

			for ($i=1; $i<=6; $i++) {
				if (empty($images['products_image_sm_'.$i]) || empty($images['products_image_xl_'.$i])) continue;
				$template['has_carousel?'] = 1;
			}
		}

		if ($this->has_special()) {
			$template['special?'] = 1;
			$template['specials_price?'] = 1;
			if ($this->get_special()['active_criteria'] != 3) {
				$template['specials_notice?'] = 1;
			}
		}

		if ($inventory['display_available_num'] > 0) {
			$template['available?'] = 1;
			$template['instock?'] = 1;
			$template['info_availability'] = $inventory['display_available'].' in stock';
			$template['info_ships?'] = self::calc_ship_date();

			$template['availdetails'] = '<strong style="color:#090;">'.$inventory['display_available'].'</strong> in stock in Atlanta, GA - Ships '.ck_product_listing::calc_ship_date(0);

			if (self::calc_ship_date() == 'Today') $template['ships_today?'] = 1;
			else {
				$template['ships_ondate?'] = 1;
				$template['ship_date'] = 'next business day';
			}

			if ($header['on_order'] > 0) {
				$template['on_order'] = $header['on_order'];
				$template['on_order?'] = 1;
			}

			if ($header['always_available']) {
				$template['ships_addtl?'] = '<strong>Additional quantity available:</strong> Ships in '.$header['lead_time'].' day(s)';

				if ($header['lead_time'] >= 6) $template['addtl_ship_date'] = self::calc_ship_date($header['lead_time']);
				else $template['addtl_ship_date'] = 'in '.$header['lead_time'].'-'.($header['lead_time']+1).' business day(s)';
			}
		}
		elseif ($header['always_available'] == 1) {
			$template['stockavailable?'] = 1;
			$template['info_availability'] = 'Available';
			$template['info_ships?'] = self::calc_ship_date($header['lead_time']);

			$template['availdetails'] = 'Additional quantity available - Ships '.($header['lead_time']>=6?ck_product_listing::calc_ship_date($header['lead_time']):'in '.$header['lead_time'].'-'.($header['lead_time']+1).' business day(s)');

			if ($header['on_order'] > 0) {
				$template['on_order'] = $header['on_order'];
				$template['on_order?'] = 1;
				$template['availdetails'] .= '<br><strong style="color:#ff8500;">'.$header['on_order'].'</strong> on order - Please call to inquire about arrival date or additional quantity';
			}

			$template['ships_ondate?'] = 1;
			if ($header['lead_time'] >= 6) $template['ship_date'] = self::calc_ship_date($header['lead_time']);
			else $template['ship_date'] = 'in '.$header['lead_time'].'-'.($header['lead_time']+1).' business days';

			$template['ships_addtl?'] = '<strong>Additional quantity available:</strong> Ships in '.$header['lead_time'].' day(s)';
		}
		else {
			$template['outofstock?'] = 1;
			if ($header['on_order'] - $inventory['allocated'] > 0) {
				$template['on_order'] = $header['on_order'] - $inventory['allocated'];
				$template['on_order?'] = 1;

				$template['ships_addtl?'] = '<strong style="color:#ff8500;">'.($header['on_order'] - $inventory['allocated']).'</strong> on order - Please call to inquire about arrival date or additional quantity';
			}
			else {
				$template['ships_addtl?'] = 'If you need this item, we would love to help. Even though it\'s currently out of stock, we can get it for you! Please contact us to check on options available to meet your needs.';
			}
			$template['info_availability'] = 'Call to verify availability';
			$template['availdetails'] = 'If you need this item, we would love to help. Even though it\'s currently out of stock, we can get it for you! Please contact us to check on options available to meet your needs.';
		}

		if ($this->free_shipping() == 2) $template['free_shipping?'] = 1;
		elseif ($this->free_shipping() == 1) $template['qualifies_free_shipping?'] = 1;

		if (!in_array($header['warranty_id'], ['', '4'])) $template['warranty?'] = 1;

		if ($this->has_options()) {
			$template['options?'] = 1;
			$template['has_options?'] = 1;
		}
		$this->skeleton->load('thin_template', $template);
	}

	private function build_schema() {
		$header = $this->get_header();

		$schema = [];
		$schema['mpn'] = $header['products_model'];
		$schema['name'] = htmlspecialchars($header['products_name']);
		$schema['sku'] = $this->id();
		$schema['image'] = $this->get_image('products_image_lrg');
		$schema['brand'] = $this->get_manufacturer();
		$schema['price'] = $this->get_price('display');
		$schema['price_currency'] = 'USD';
		$schema['condition'] = $this->get_condition('meta');
		$schema['warranty'] = '100 years';

		$schema['url'] = $this->get_url();

		$schema['availability'] = ($this->get_inventory('display_available_num')>0?'InStock':'OutOfStock');
		$schema['inventory_level'] = $this->get_inventory('display_available_num');

		$schema['weight'] = $this->get_total_weight();

		$this->skeleton->load('schema', $schema);
	}

	private function build_ipn() {
		$this->skeleton->load('ipn', new ck_ipn2($this->get_header('stock_id')));
	}

	private function build_images() {
		// because load() ignores keys that don't fit the format of the target type, we can
		// load the whole header in since that's where the data is coming from
		if (!empty(self::$preloaded_data['images'][$this->id()])) {
			$images = self::$preloaded_data['images'][$this->id()];
			unset(self::$preloaded_data['images'][$this->id()]);
		}
		else {
			$images = self::fetch('images', [':stock_id' => $this->get_header('image_reference_stock_id')]);
		}
		/*if (!empty($this->get_ipn()->get_header('image_reference'))) { // if the ipn of this product uses an image reference then we'll use that instead
			$reference_ipn = new ck_ipn2($this->get_ipn()->id());
			$images = $reference_ipn->get_product_ready_images();
		}
		else $images = $this->get_header();*/
		$this->skeleton->load('images', !empty($images)?array_map('trim', $images):NULL);
	}

	private function build_inventory() {
		$inventory = $this->skeleton->format('inventory');

		// grab, and build if necessary, the inventory from the IPN object - most of our qty information will come from there
		$ipn_inventory = $this->get_ipn()->get_inventory();

		// this information could (should?) be coming from an accompanying IPN class, but for now we're doing it here
		$inventory['on_hand'] = $ipn_inventory['on_hand']; //empty($this->data['serialized'])?$this->data['stock_quantity']:$this->fetch('on_hand_serialized_inventory', array($this->stock_id));
		$inventory['allocated'] = $ipn_inventory['allocated']; //$this->data['ca_allocated_quantity'] + $this->fetch('allocated_inventory', array($this->stock_id));
		$inventory['on_hold'] = $ipn_inventory['on_hold']; //empty($this->data['serialized'])?$this->fetch('on_hold_nonserialized_inventory', array($this->stock_id)):$this->fetch('on_hold_serialized_inventory', array($this->stock_id));
		
		$options = $this->get_options('included'); // we use the getter to initialize it, if it's not yet initialized

		if (!$this->is('is_bundle')) $inventory['max_available_quantity_including_accessories'] = $inventory['on_hand'] - $inventory['allocated'] - $inventory['on_hold'];

		if (!empty($options)) {
			foreach ($options as $ctr => $option) {
				$bundle_available = floor($option['listing']->get_inventory('available') / $option['bundle_quantity']);
				if (!isset($inventory['max_available_quantity_including_accessories'])) $inventory['max_available_quantity_including_accessories'] = $bundle_available;
				$inventory['max_available_quantity_including_accessories'] = min($bundle_available, $inventory['max_available_quantity_including_accessories']);
			}
		}

		$inventory['max_available_quantity_including_accessories'] = max($inventory['max_available_quantity_including_accessories'], 0);
		
		if ($this->is('is_bundle')) {
			$inventory['on_hand'] = $inventory['max_available_quantity_including_accessories'];

			// since these are virtual products, we don't need to worry about flowing the full math through all of the potential options
			$inventory['allocated'] = 0;
			$inventory['on_hold'] = 0;
		}

		$inventory['available'] = $inventory['on_hand'] - $inventory['allocated'] - $inventory['on_hold'];

		if ($ipn_inventory['max_displayed_quantity'] > 0 && $ipn_inventory['max_displayed_quantity'] < $inventory['available']) {
			$inventory['display_available_num'] = $ipn_inventory['max_displayed_quantity'];
			$inventory['display_available'] = number_format($ipn_inventory['max_displayed_quantity']).'+';
		}
		else {
			$inventory['display_available_num'] = $inventory['available'];
			$inventory['display_available'] = number_format($inventory['available']);
		}

		$inventory['on_special'] = 0;
		if ($this->has_special()) {
			$special = $this->get_special();
			$inventory['on_special'] = $inventory['display_available_num'];
			if ($special['active_criteria'] == 1) {
				$inventory['on_special'] = min($inventory['display_available_num'], max($inventory['available'] - $special['specials_qty'], 0));
			}
		}

		$this->skeleton->load('inventory', $inventory);
	}

	private function build_price() {
		$prices = $this->skeleton->format('prices');

		$ipn_prices = $this->get_ipn()->get_price();

		$prices['original'] = $ipn_prices['list'];
		$prices['dealer'] = $ipn_prices['dealer'];
		$prices['wholesale_high'] = $ipn_prices['wholesale_high'];
		$prices['wholesale_low'] = $ipn_prices['wholesale_low'];
		// these two bundle prices are only used for reference for price management
		$prices['bundle_original'] = NULL;
		$prices['bundle_dealer'] = NULL;
		$prices['bundle_wholesale_high'] = NULL;
		$prices['bundle_wholesale_low'] = NULL;

		// since we're checking that it's status=1, and anything that will affect the specials status will turn it off, we can assume if we get anything that it's valid for this item
		if ($special = self::fetch('specials_price', [$this->id()])) {
			$prices['special'] = $special['specials_new_products_price'] ?? NULL;
			$this->skeleton->load('special', $special);
		}

		if (($customer = $this->get_customer()) && ($customer_price = $customer->get_prices($this->get_header('stock_id')))) {
			$prices['customer'] = $customer_price;
		}

		$prices['bundle_rollup'] = 0;

		// handle bundle pricing
		if ($this->is('is_bundle')) {
			if ($this->has_options('included')) {
				foreach ($this->get_included_options() as $included) {
					$prices['bundle_original'] += ($included['price'] * $included['bundle_quantity']); // bundle pricing uses the display price
					$prices['bundle_dealer'] += ($included['listing']->get_price('dealer') * $included['bundle_quantity']);
					$prices['bundle_wholesale_high'] += ($included['listing']->get_price('wholesale_high') * $included['bundle_quantity']);
					$prices['bundle_wholesale_low'] += ($included['listing']->get_price('wholesale_low') * $included['bundle_quantity']);
				}
				$header = $this->get_header();
				// only alter the prices if the 'module' is activated
				if ($header['bundle_price_flows_from_included_products'] > 0) {
					$prices['bundle_rollup'] = 1;
					$prices['original'] = self::modify_bundle_price($prices['bundle_original'], $header['bundle_price_flows_from_included_products'], $header['bundle_price_modifier'], $header['bundle_price_signum']);
					$prices['dealer'] = self::modify_bundle_price($prices['bundle_dealer'], $header['bundle_price_flows_from_included_products'], $header['bundle_price_modifier'], $header['bundle_price_signum']);
					$prices['wholesale_high'] = self::modify_bundle_price($prices['bundle_wholesale_high'], $header['bundle_price_flows_from_included_products'], $header['bundle_price_modifier'], $header['bundle_price_signum']);
					$prices['wholesale_low'] = self::modify_bundle_price($prices['bundle_wholesale_low'], $header['bundle_price_flows_from_included_products'], $header['bundle_price_modifier'], $header['bundle_price_signum']);
				}
			}
		}

		$this->select_price_direct($prices); // sets the values for 'display', 'prices_reason' and 'price_original_reason' by reference

		$this->skeleton->load('prices', $prices);
	}

	public static function modify_bundle_price($price, $method, $modifier, $sign) {
		$sign = empty($sign)?-1:1; // 0 - decrease; 1 - increase

		switch ($method) {
			case 1: // percentage change
				$change = bcmul($price, bcmul($modifier, .01, 4), 4);
				break;
			case 2: // dollar change
				$change = $modifier;
				break;
			case 0: // no change
			default:
				$change = 0;
				break;
		}

		$price = bcadd($price, bcmul($sign, $change, 4), 2);

		return $price;
	}

	private function build_all_specials() {
		if ($specials = self::fetch('all_specials', [':products_id' => $this->id()])) {
			foreach ($specials as &$special) {
				if (!self::date_is_empty($special['specials_date_added'])) $special['specials_date_added'] = self::DateTime($special['specials_date_added']);
				else $special['specials_date_added'] = NULL;
				if (!self::date_is_empty($special['specials_last_modified'])) $special['specials_last_modified'] = self::DateTime($special['specials_last_modified']);
				else $special['specials_last_modified'] = NULL;
				if (!self::date_is_empty($special['expires_date'])) $special['expires_date'] = self::DateTime($special['expires_date']);
				else $special['expires_date'] = NULL;
				if (!self::date_is_empty($special['date_status_change'])) $special['date_status_change'] = self::DateTime($special['date_status_change']);
				else $special['date_status_change'] = NULL;
			}
			$this->skeleton->load('all_specials', $specials);
		}
	}

	// this should be removed from here and accessed through the cart instead
	private function build_customer() {
		if (!empty($_SESSION['customer_id'])) $this->skeleton->load('customer', new ck_customer2($_SESSION['customer_id']));
		else $this->skeleton->load('customer', NULL);
	}

	private function build_categories() {
		$categories_ids = self::fetch('categories', [$this->id()]);

		$categories = [];

		if (!empty($categories_ids)) {
			foreach ($categories_ids as $categories_id) {
				$categories[] = new ck_listing_category($categories_id);
			}
		}

		$this->skeleton->load('categories', $categories);
	}

	private function build_parent_listings() {
		$parent_listings = self::fetch('parent_listings', [':products_id' => $this->id()]);

		$parents = ['included' => [], 'extra' => []];

		foreach ($parent_listings as $option) {
			$listing = new self($option['products_id']);
			$opt = ['products_id' => $option['products_id']];
			$opt['addon_name'] = $option['use_custom_name']?$option['custom_name']:$this->get_header('products_name');
			$opt['addon_desc'] = $option['use_custom_desc']?trim($option['custom_desc']):trim($option['default_desc']);

			$opt['bundle_quantity'] = $option['bundle_quantity'];

			$prices = [];
			$prices[] = $this->get_price('display');
			if ($option['use_custom_price']) $prices[] = $option['custom_price'];
			if (!empty($option['default_price'])) $prices[] = $option['default_price'];
			if (($customer_price = $listing->get_price('customer')) > 0) $prices[] = $customer_price;
			if (($special_price = $listing->get_price('special')) > 0) $prices[] = $special_price;

			$opt['addon_price'] = min($prices);

			$opt['addon_display_price'] = CK\text::monetize($opt['addon_price']);

			if ($option['recommended']) $opt['recommended?'] = 1;

			if (empty($opt['addon_desc'])) unset($opt['adoon_desc']);

			$opt['allow_mult_opts'] = $option['allow_mult_opts'];

			if ($option['included']) $parents['included'][] = $opt;
			else $parents['extra'][] = $opt;
		}

		if (!empty($parents['included'])) $parents['included'][count($parents['included'])-1]['last'] = 1;
		if (!empty($parents['extra'])) $parents['extra'][count($parents['extra'])-1]['last'] = 1;

		$this->skeleton->load('parent_listings', $parents);
	}

	private function build_options() {
		$options = self::fetch('options', [$this->id()]);

		$opts = ['included' => [], 'extra' => []];

		$bundle_total = 0;

		foreach ($options as $option) {
			$opt = ['products_id' => $option['products_id'], 'listing' => new self($option['products_id'])];
			$opt['name'] = $option['use_custom_name']?$option['custom_name']:$option['products_name'];
			$opt['desc'] = $option['use_custom_desc']?trim($option['custom_desc']):trim($option['default_desc']);

			$opt['bundle_quantity'] = $option['bundle_quantity'];

			if ($this->is('is_bundle')) $bundle_total += $opt['listing']->get_price('display');
			else $opt['bundle_revenue_pct'] = 0;

			$opt['price'] = $option['use_custom_price']?$option['custom_price']:(!empty($option['default_price'])?$option['default_price']:$option['stock_price']);

			if (($customer_price = $opt['listing']->get_price('customer')) > 0 && $customer_price < $opt['price']) $opt['price'] = $customer_price;
			if (($special_price = $opt['listing']->get_price('special')) > 0 && $special_price < $opt['price']) $opt['price'] = $special_price;

			$opt['display_price'] = CK\text::monetize($opt['price']);

			$opt['addon_id'] = $option['products_id']; //$this->get_child_addon_id($option['products_id']);

			if ($option['recommended']) $opt['recommended?'] = 1;

			if (empty($opt['desc'])) unset($opt['desc']);

			$opt['allow_mult_opts'] = $option['allow_mult_opts'];

			if ($option['included']) $opts['included'][] = $opt;
			else $opts['extra'][] = $opt;
		}

		if (!empty($opts['included'])) $opts['included'][count($opts['included'])-1]['last'] = 1;
		if (!empty($opts['extra'])) $opts['extra'][count($opts['extra'])-1]['last'] = 1;

		if ($this->is('is_bundle')) $opts['included'] = array_map(function($opt) use ($bundle_total) {
			$opt['bundle_revenue_pct'] = bcdiv($opt['listing']->get_price('display'), $bundle_total, 6);
			return $opt;
		}, $opts['included']);

		$this->skeleton->load('options', $opts);
	}

	private function build_attributes() {
		if (!empty(self::$preloaded_data['attributes'][$this->id()])) {
			$attributes = self::$preloaded_data['attributes'][$this->id()];
			unset(self::$preloaded_data['attributes'][$this->id()]);
		}
		else $attributes = self::fetch('attributes', [$this->id()]);

		$this->skeleton->load('attributes', $attributes);
	}

	private function build_cross_sells() {
		$xsells = self::fetch('cross_sells', array($this->id()));

		$cross_sells = [];

		if (!empty($xsells)) {
			foreach ($xsells as $products_id) {
				$cross_sells[] = new self($products_id);
			}
		}

		$this->skeleton->load('cross_sells', $cross_sells);
	}

	private function build_also_purchased() {
		$also_purchs = self::fetch('also_purchased', array($this->id()));

		$also_purchased = [];

		if (!empty($also_purchs)) {
			foreach ($also_purchs as $products_id) {
				$also_purchased[] = new self($products_id);
			}
		}

		$this->skeleton->load('also_purchased', $also_purchased);
	}

	private function build_notifications() {
		$notifications = self::fetch('notifications', array($this->id()));

		foreach ($notifications as &$notification) {
			$notification = ck_datetime::datify($notification['date_added']);
		}

		if (empty($notifications)) $notifications = [];

		$this->skeleton->load('notifications', $notifications);
	}

	private function build_content_reviews() {
		$reviews = self::fetch('content_reviews', [':products_id' => $this->id()]);

		$content_reviews = self::$content_review_statuses;

		foreach ($content_reviews as $status => $desc) {
			$content_reviews[$status] = [];
		}

		foreach ($reviews as &$review) {
			$review['notice_date'] = self::DateTime($review['notice_date']);
			if (!empty($review['response_date'])) $review['response_date'] = self::DateTime($review['response_date']);

			$content_reviews[$review['status']][] = $review;
		}

		$this->skeleton->load('content_reviews', $content_reviews);
	}

	private function build_manufacturer() {
		$this->skeleton->load('manufacturer', self::fetch('manufacturer', [$this->get_header('manufacturers_id')]));
	}

	private function build_upcs() {
		$upcs = self::fetch('upcs', [':stock_id' => $this->get_header('stock_id'), ':products_id' => $this->id()]);

		$relationship_map = [
			'' => 'IPN',
			'products' => 'Listing',
			'vendors_to_stock_item' => 'Vendor'
		];

		foreach ($upcs as &$upc) {
			$upc['relationship'] = $relationship_map[$upc['target_resource']];
			if ($upc['relationship'] == 'Listing') {
				$upc['related_object'] = $this->get_header('products_model');
			}
			elseif ($upc['relationship'] == 'Vendor') {
				$upc['related_object'] = self::query_fetch('SELECT v.vendors_company_name FROM vendors_to_stock_item vtsi JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE vtsi.id = :target_resource_id', cardinality::SINGLE, [':target_resource_id' => $upc['target_resource_id']]);
			}
			else $upc['related_object'] = '';
			$upc['created_date'] = self::DateTime($upc['created_date']);
			$upc['active'] = CK\fn::check_flag($upc['active']);
		}

		$this->skeleton->load('upcs', $upcs);
	}

	private function build_primary_container() {
		if (!empty(self::$preloaded_data['primary_containers'][$this->id()])) {
			$primary_container = self::$preloaded_data['primary_containers'][$this->id()];
			unset(self::$preloaded_data['primary_containers'][$this->id()]);
		}
		else $primary_container = self::fetch('primary_container', [':products_id' => $this->id()]);

		if (!empty($primary_container)) {
			$primary_container['canonical'] = CK\fn::check_flag($primary_container['canonical'] ?? NULL);
			$primary_container['redirect'] = CK\fn::check_flag($primary_container['redirect'] ?? NULL);
			$primary_container['date_created'] = ck_datetime::datify($primary_container['date_created']);
		}
		else $primary_container = [];

		$this->skeleton->load('primary_container', $primary_container);
	}

	/*-------------------------------
	// modify data
	-------------------------------*/

	private function select_price_direct(&$prices) {
		// I'm mimicking the historical logic used on the display page:
		//	base price will be overridden by any specific pricing, regardless of whether it's lower or not
		//	specific pricing has the following priority: lowest, tie goes to dealer then customer then special
		$price_reason = 'original';
		$price_original_reason = 'original';

		if ($prices['special'] > 0) $price_reason = 'special';

		if ($prices['customer'] > 0 && ($price_reason == 'original' || $prices['customer'] <= $prices[$price_reason])) $price_reason = 'customer';
		elseif ($prices['customer'] > 0) $price_original_reason = 'customer';

		if (($customer = $this->get_customer()) && $customer->is('dealer')) {
			switch ($customer->get_header('customer_price_level_id')) {
				case 3:
					$dpr = 'wholesale_high';
					break;
				case 4:
					$dpr = 'wholesale_low';
					break;
				case 2:
				default:
					$dpr = 'dealer';
					break;
			}

			$dp = $prices[$dpr];

			if ($dp > 0) {
				if ($price_reason == 'original' || $dp <= $prices[$price_reason]) $price_reason = $dpr;
				elseif ($price_original_reason == 'original' || $dp <= $prices['customer']) $price_original_reason = $dpr;
			}
		}

		$prices['reason'] = $price_reason;
		$prices['original_reason'] = $price_original_reason;

		return $prices['display'] = $prices[$prices['reason']];
	}

	public static function select_price(&$prices) {
		// I'm mimicking the historical logic used on the display page:
		//	base price will be overridden by any specific pricing, regardless of whether it's lower or not
		//	specific pricing has the following priority: lowest, tie goes to dealer then customer then special
		$price_reason = 'original';
		$price_original_reason = 'original';

		if ($prices['special'] > 0) $price_reason = 'special';

		if ($prices['customer'] > 0 && ($price_reason == 'original' || $prices['customer'] <= $prices[$price_reason])) $price_reason = 'customer';
		elseif ($prices['customer'] > 0) $price_original_reason = 'customer';

		if (!empty($_SESSION['cart']) && ($customer = $_SESSION['cart']->get_customer()) && $customer->is('dealer')) {
			switch ($customer->get_header('customer_price_level_id')) {
				case 3:
					$dpr = 'wholesale_high';
					break;
				case 4:
					$dpr = 'wholesale_low';
					break;
				case 2:
				default:
					$dpr = 'dealer';
					break;
			}

			$dp = $prices[$dpr];

			if ($dp > 0) {
				if ($price_reason == 'original' || $dp <= $prices[$price_reason]) $price_reason = $dpr;
				elseif ($price_original_reason == 'original' || $dp <= $prices['customer']) $price_original_reason = $dpr;
			}
		}

		$prices['reason'] = $price_reason;
		$prices['original_reason'] = $price_original_reason;

		return $prices['display'] = $prices[$prices['reason']];
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_flat_data(Array $elements=[]) {
		$response = [];

		foreach ($elements as $getter => $element) {
			$response[$element] = $this->$getter();
		}

		return $response;
	}

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) $response = $this->skeleton->get('header');
		else $response = $this->skeleton->get('header', $key);
		return $response;
	}

	public function get_base_url() {
		if (!$this->skeleton->built('base_url')) $this->build_base_url();
		return $this->skeleton->get('base_url');
	}

	public function get_template($key=NULL) {
		if (!$this->skeleton->built('template')) $this->build_template();
		if (empty($key)) return $this->skeleton->get('template');
		else return $this->skeleton->get('template', $key);
	}

	public function get_thin_template($key=NULL) {
		if (!$this->skeleton->built('thin_template')) $this->build_thin_template();
		if (empty($key)) return $this->skeleton->get('thin_template');
		else return $this->skeleton->get('thin_template', $key);
	}

	public function get_schema($key=NULL) {
		if (!$this->skeleton->built('schema')) $this->build_schema();
		if (empty($key)) return $this->skeleton->get('schema');
		else return $this->skeleton->get('schema', $key);
	}

	public function get_ipn() {
		if (!$this->skeleton->built('ipn')) $this->build_ipn();
		return $this->skeleton->get('ipn');
	}

	public function get_image($key=NULL) {
		if (!$this->skeleton->built('images')) $this->build_images();
		if (empty($key)) return $this->skeleton->get('images');
		else return $this->skeleton->get('images', $key);
	}

	// backwards incompatibility: default key used to be 'display_available'
	public function get_inventory($key=NULL) {
		if (!$this->skeleton->built('inventory')) $this->build_inventory();
		if (empty($key)) return $this->skeleton->get('inventory');
		else return $this->skeleton->get('inventory', $key);
	}

	// backwards incompatibility: default key used to be 'display'
	public function get_price($key=NULL) {
		if (!$this->skeleton->built('prices')) $this->build_price();
		if (empty($key)) return $this->skeleton->get('prices');
		else return $this->skeleton->get('prices', $key);
	}

	public function get_revenue() {
		if ($this->is('is_bundle')) return 0;
		else return $this->get_price('display');
	}

	public function has_special() {
		if (!$this->skeleton->built('special') && !$this->skeleton->built('prices')) $this->build_price();
		return $this->skeleton->has('special');
	}

	public function get_special() {
		if (!$this->has_special()) return NULL;
		else return $this->skeleton->get('special');
	}

	public function has_any_specials() {
		if (!$this->skeleton->built('all_specials')) $this->build_all_specials();
		return $this->skeleton->has('all_specials');
	}

	public function get_all_specials() {
		if (!$this->has_any_specials()) return NULL;
		else return $this->skeleton->get('all_specials');
	}

	public function has_customer() {
		if (!$this->skeleton->built('customer')) $this->build_customer();
		return $this->skeleton->has('customer');
	}

	public function get_customer() {
		if (!$this->has_customer()) return NULL;
		return $this->skeleton->get('customer');
	}

	public function has_categories() {
		if (!$this->skeleton->built('categories')) $this->build_categories();
		return $this->skeleton->has('categories');
	}

	public function get_categories() {
		if (!$this->has_categories()) return NULL;
		return $this->skeleton->get('categories');
	}

	public function has_parent_listings($key=NULL) {
		if (!$this->skeleton->built('parent_listings')) $this->build_parent_listings();
		if (empty($key)) return $this->skeleton->has('parent_listings');
		else return !empty($this->skeleton->get('parent_listings', $key));
	}

	public function get_parent_listings($key=NULL) {
		if (!$this->has_parent_listings($key)) return NULL;
		if (empty($key)) return $this->skeleton->get('parent_listings');
		else return $this->skeleton->get('parent_listings', $key);
	}

	public function has_options($key=NULL) {
		if (!$this->skeleton->built('options')) $this->build_options();
		if (empty($key)) return $this->skeleton->has('options');
		else return !empty($this->skeleton->get('options', $key));
	}

	public function get_options($key=NULL) {
		if (!$this->has_options($key)) return NULL;
		if (empty($key)) return $this->skeleton->get('options');
		else return $this->skeleton->get('options', $key);
	}

	// backwards compatibility method
	public function get_included_options() {
		return $this->get_options('included');
	}

	// backwards compatibility method
	public function get_extra_options() {
		return $this->get_options('extra');
	}

	public function has_attributes($key=NULL) {
		if (!$this->skeleton->built('attributes')) $this->build_attributes();
		if (empty($key)) return $this->skeleton->has('attributes');
		else {
			if (is_numeric($key)) {
				foreach ($this->skeleton->get('attributes') as $attr) {
					if ($attr['attribute_key_id'] == $key) return TRUE;
				}
				return FALSE;
			}
		}
	}

	public function get_attributes($key=NULL) {
		if (!$this->has_attributes($key)) return NULL;
		elseif (empty($key)) return $this->skeleton->get('attributes');
		else {
			if (is_numeric($key)) {
				foreach ($this->skeleton->get('attributes') as $attr) {
					if ($attr['attribute_key_id'] == $key) return $attr;
				}
				return NULL;
			}
		}
	}

	public function has_cross_sells() {
		if (!$this->skeleton->built('cross_sells')) $this->build_cross_sells();
		return $this->skeleton->has('cross_sells');
	}

	public function get_cross_sells() {
		if (!$this->has_cross_sells()) return NULL;
		return $this->skeleton->get('cross_sells');
	}

	public function has_also_purchased() {
		if (!$this->skeleton->built('also_purchased')) $this->build_also_purchased();
		return $this->skeleton->has('also_purchased');
	}

	public function get_also_purchased() {
		if (!$this->has_also_purchased()) return NULL;
		return $this->skeleton->get('also_purchased');
	}

	public function has_notifications() {
		if (!$this->skeleton->built('notifications')) $this->build_notifications();
		return $this->skeleton->has('notifications');
	}

	public function get_notifications($customers_id=NULL) {
		if (!$this->has_notifications()) return NULL;
		if (empty($customers_id)) return $this->skeleton->get('notifications');
		else {
			$notifications = $this->skeleton->get('notifications');
			foreach ($notifications as $notification) {
				if ($notification['customers_id'] == $customers_id) return $notification;
			}
			// if we didn't find a notification to match this specific customers_id, return NULL
			return NULL;
		}
	}

	public function has_content_reviews($key=NULL) {
		if (!$this->skeleton->built('content_reviews')) $this->build_content_reviews();
		if (empty($key)) return $this->skeleton->has('content_reviews');
		else return !empty($this->skeleton->get('content_reviews', $key));
	}

	public function get_content_reviews($key=NULL) {
		if (!$this->has_content_reviews($key)) return NULL;
		if (empty($key)) return $this->skeleton->get('content_reviews');
		else return $this->skeleton->get('content_reviews', $key);
	}

	public function get_manufacturer() {
		if (!$this->skeleton->built('manufacturer')) $this->build_manufacturer();
		return $this->skeleton->get('manufacturer');
	}

	public function has_upcs($key=NULL) {
		if (!$this->skeleton->built('upcs')) $this->build_upcs();

		if (empty($key)) return $this->skeleton->has('upcs');
		elseif (empty($this->skeleton->has('upcs'))) return FALSE;
		elseif (is_numeric($key)) {
			// first try to match the UPC
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc'] == $key) return TRUE;
			}
			// if this isn't an actual UPC assigned to this IPN, try and match the ID of the assignment record
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc_assignment_id'] == $key) return TRUE;
			}
			return FALSE;
		}
		else {
			$key = strtolower($key);
			// first try to match the UPC
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc'] == $key) return TRUE;
			}
			// if this isn't an actual UPC assigned to this IPN, try and match one of the types of UPC groups
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['target_resource'] == $key) return TRUE;
				elseif ($key == 'ipn' && empty($upc['target_resource'])) return TRUE;
				elseif ($upc['target_resource'] == 'products' && in_array($key, ['product', 'products', 'product_listings', 'products-listings', 'product listings'])) return TRUE;
				elseif ($upc['target_resource'] == 'vendors_to_stock_item' && in_array($key, ['vendor', 'vendors', 'vendors_to_stock_item', 'vendors-to-stock-item', 'vendors to stock item'])) return TRUE;
				elseif (strtolower($upc['purpose']) == $key) return TRUE;
			}
			return FALSE;
		}
	}

	public function get_upcs($key=NULL) {
		if (!$this->has_upcs($key)) return NULL;
		elseif (empty($key)) return $this->skeleton->get('upcs');
		elseif (is_numeric($key)) {
			// first try to match the UPC
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc'] == $key) return $upc;
			}
			// if this isn't an actual UPC assigned to this IPN, try and match the ID of the assignment record
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc_assignment_id'] == $key) return $upc;
			}
			return NULL;
		}
		else {
			$key = strtolower($key);
			// first try to match the UPC
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc'] == $key) return $upc;
			}
			// if this isn't an actual UPC assigned to this IPN, try and match one of the types of UPC groups
			$upcs = [];
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['target_resource'] == strtolower($key)) $upcs[] = $upc;
				elseif ($key == 'ipn' && empty($upc['target_resource'])) $upcs[] = $upc;
				elseif ($upc['target_resource'] == 'products' && in_array($key, ['product', 'products', 'product_listings', 'products-listings', 'product listings'])) $upcs[] = $upc;
				elseif ($upc['target_resource'] == 'vendors_to_stock_item' && in_array($key, ['vendor', 'vendors', 'vendors_to_stock_item', 'vendors-to-stock-item', 'vendors to stock item'])) $upcs[] = $upc;
				elseif (strtolower($upc['purpose']) == $key) $upcs[] = $upc;
			}
			return !empty($upcs)?$upcs:NULL;
		}
	}

	public function get_upc_number($type='') {
		// in all cases, upcs assigned to this listing come before the defaults for the IPN

		// first look for this purpose
		if (!empty($type)) {
			if ($this->has_upcs($type)) {
				foreach ($this->get_upcs($type) as $upc) {
					if ($upc['active']) return $upc['upc'];
				}
			}
		}

		// ... then just return the first one we get to not for some other purpose
		if ($this->has_upcs()) {
			foreach ($this->get_upcs() as $upc) {
				if (empty($upc['purpose']) && $upc['active']) return $upc['upc'];
			}
		}

		return NULL;
	}

	public function get_condition($type='friendly') {
		// perform all condition mapping
		if ($type == 'friendly') {
			switch ($this->get_header('conditions')) {
				case '1': return 'New'; break;
				case '6': return 'Open Box'; break;
				case '7': return 'Factory Sealed'; break;
				case '4': return 'Not Perfect, But Functional'; break;
				case '2':
				case '8':
				default:
					return 'Refurbished';
					break;
			}
		}
		elseif ($type == 'google') {
			switch ($this->get_header('conditions')) {
				case '1':
				case '6':
				case '7':
					return 'new';
					break;
				case '4':
					return 'used';
					break;
				case '2':
				case '8':
				default:
					return 'refurbished';
					break;
			}
		}
		elseif ($type == 'brokerbin') {
			switch ($this->get_header('conditions')) {
				case '1':
					return 'NEW';
					break;
				case '4':
					return 'ASIS';
					break;
				case '6':
					return 'NOB';
					break;
				case '7':
					return 'F/S';
					break;
				case '2':
				case '8':
				default:
					return 'REF';
					break;
			}
		}
		elseif ($type == 'meta') {
			return in_array($this->get_header('conditions'), array(2, 3, 4, 8))?'RefurbishedCondition':'NewCondition';
		}
		else {
			return $this->get_header('conditions_name');
		}
	}

	public function get_simple_weight() {
		return $this->get_header('stock_weight');
	}

	public function get_total_weight() {
		$weight = $this->get_header('stock_weight');
		if ($this->has_options('included')) {
			foreach ($this->get_options('included') as $opt) {
				$multiplier = !empty($opt['bundle_quantity'])?$opt['bundle_quantity']:1;
				$weight += $opt['listing']->get_total_weight() * $multiplier;
			}
		}
		return $weight;
	}

	public function free_shipping() {
		// 0 - not eligible for this product or customer
		// 1 - eligible for this product and customer, but the item doesn't trip the threshold by itself
		// 2 - eligible, and a single unit trips the threshold for free shipping

		// globally, we must have free shipping enabled, the item must not be marked as "freight"
		if ($GLOBALS['ck_keys']->product['freeship_enabled'] && !$this->is('freight')) {
			if ($this->get_price('display') > $GLOBALS['ck_keys']->product['freeship_threshold']) return 2;
			else return 1;
		}
		else return 0;
	}

	public function get_url(ck_merchandising_container_interface $context=NULL) {
		return $this->get_base_url();
	}

	public function url_is_correct() {
		$compare = parse_url($_SERVER['REQUEST_URI']);

		if ($this->get_url() != $compare['path']) return FALSE;
		else return TRUE;
	}

	public function redirect_if_necessary() {
		if (!$this->url_is_correct()) CK\fn::permanent_redirect($this->get_url());

		if (!$this->is_viewable()) {
			if ($this->has_categories() && $category = $this->get_categories()[0]) CK\fn::redirect_and_exit($category->get_url());
			else CK\fn::redirect_and_exit('/');
		}
	}

	public function url_is_canonical() {
		if ($this->get_url() == $this->get_canonical_url()) return TRUE;
		else return FALSE;
	}

	public function get_canonical_url() {
		$use_canonical = FALSE;
		// follow to the ultimate last referenced canonical product

		if ($this->has_primary_container()) {
			$primary_container = $this->get_primary_container();
			if ($primary_container['container_type'] != 'Product Listing' || $primary_container['container_id'] != $this->id()) {
				if ($primary_container['canonical']) {
					$container = ck_merchandising_container_manager::instantiate($primary_container['container_type_id'], $primary_container['container_id']);

					if ($container->is_active()) return $container->get_canonical_url($this);
				}
			}
		}

		if ($this->get_ipn()->has_primary_container()) {
			$primary_container = $this->get_ipn()->get_primary_container();
			if ($primary_container['container_type'] != 'Product Listing' || $primary_container['container_id'] != $this->id()) {
				if ($primary_container['canonical']) {
					$container = ck_merchandising_container_manager::instantiate($primary_container['container_type_id'], $primary_container['container_id']);

					if ($container->is_active()) return $container->get_canonical_url($this);
				}
			}
		}
		
		$product = $this;

		while ($product->get_header('canonical_type') == 1 && !empty($product->get_header('canonical_id'))) {
			$product = new self($product->get_header('canonical_id'));
			$use_canonical = TRUE;
			$canonical_url = $product->get_url();
		}

		// if it uses a category as a canonical, follow that path
		if ($product->get_header('canonical_type') == 2 && !empty($product->get_header('canonical_id'))) {
			$category = new ck_listing_category($product->get_header('canonical_id'));
			$use_canonical = TRUE;
			while (!empty($category->get_header('canonical_category_id'))) {
				$category = new ck_listing_category($category->get_header('canonical_category_id'));
			}
			$canonical_url = $category->get_url();
		}

		if (!empty($use_canonical)) return $canonical_url;
		else return $this->get_url();
	}

	public function get_category_cpath() {
		$categories = $this->get_categories();

		if (!empty($categories)) {
			$category = $categories[0];
			return $category->get_cpath();
		}
		else return '';
	}

	public function get_title() {
		$header = $this->get_header();
		if (!empty($header['products_head_title_tag'])) return $header['products_head_title_tag'];
		elseif (!empty($header['products_name'])) return $header['products_name'];
		else return $header['products_model'];
	}

	public function get_meta_description() {
		return $this->get_header('products_name').' - '.$this->get_header('products_model').' - '.CK\text::monetize($this->get_price('display')).' - In Stock!';
	}

	public static function url($name, $id) {
		return '/'.CK\fn::simple_seo($name, '-p-'.$id.'.html');
	}

	/*public function get_child_addon_id($child_products_id) {
		return 100000000 + ($this->id() * 10000) + $child_products_id;
	}

	public function get_parent_addon_id($parent_products_id) {
		return 100000000 + ($parent_products_id * 10000) + $this->id();
	}

	public static function get_full_addon_id($parent_products_id, $child_products_id) {
		return 100000000 + ($parent_products_id * 10000) + $child_products_id;
	}

	public static function get_parent_from_addon_id($addon_id) {
		return (($addon_id % 100000000) - ($addon_id % 10000)) / 10000;
	}

	public static function get_child_from_addon_id($addon_id) {
		return $addon_id % 10000;
	}

	public static function is_addon($products_id) {
		return $products_id >= 100000000;
	}*/

	// the following functions are copied with minimal restructuring from includes/functions/product_list.php
	// they should be moved to a shipping class/service when the time comes
	public static function calc_ship_date($lead_time=0, $format=FALSE) {
		$todays_shipping_cutoff = 19; // 7:00 PM

		$ship_date = clone ck_invoice::get_accounting_date();

		// if it's after the shipping cutoff, we're not shipping until tomorrow at earliest
		if ($ship_date->format('H') > $todays_shipping_cutoff) $ship_date->add_day();

		// advance to the nearest business day to start the lead time clock - if it's already a business day, this does nothing
		do {
			$ship_date->set_to_next_business_day(self::is_holiday($ship_date));
		}
		while (self::is_holiday($ship_date));

		// set forward date by lead time in business days
		for ($i=1; $i<=$lead_time; $i++) {
			$ship_date->add_day();
			do {
				$ship_date->set_to_next_business_day(self::is_holiday($ship_date));
			}
			while (self::is_holiday($ship_date));
		}

		if (!empty($format)) return $ship_date->format($format);
		elseif ($ship_date->is_today()) return 'Today';
		elseif ($lead_time <= 0) return $ship_date->format('l'); // day name
		else return $ship_date->format('M d'); // month & date
	}

	private static $holiday = [];

	//MMD when we incorporate holiday schedules into the store, we will need to update
	//this function to check against that schedule.
	public static function is_holiday($ts) {
		$timestamp = $ts->format(ck_datetime::DATESTAMP);
		if (empty(self::$holiday[$timestamp])) self::$holiday[$timestamp] = self::query_fetch('SELECT * FROM holidays WHERE holiday_date = :timestamp', cardinality::ROW, [':timestamp' => $timestamp]);
		if (!empty(self::$holiday[$timestamp])) return TRUE;
		return FALSE;
	}

	/*public function check_special() {
		if (empty($this->special)) return FALSE;

		if ($this->special['active_criteria'] == 3 || $this->special['specials_qty'] == 999999) {
			// this special is active until the expiration date
			// under any foreseeable circumstance, that means this special is active, as any that is expired should be marked inactive before we get here
			// but we check anyway

			$specdate = new DateTime($this->special['expires_date']);
			$nowdate = new DateTime();

			if ($specdate < $nowdate) {
				// go ahead and deactivated it, cuz why not?
				tep_set_specials_status($special['specials_id'], 0);
				return FALSE;
			}
			else return TRUE;
		}
		elseif ($this->special['active_criteria'] == 2 || $this->special['specials_qty'] == 0) {
			// this special is active until we run out of stock
			if ($this->inventory['available'] > 0) return TRUE;
			else return FALSE;
		}
		elseif ($special['active_criteria'] == 1 || !is_numeric($special['active_criteria'])) {
			// this special is active until it hits a set stock threshold - this is the default under the old scheme
			if ($this->inventory['available'] > $special['specials_qty']) return TRUE;
			else return FALSE;
		}

		return NULL; // if it somehow slips through the above, then something's wrong
	}*/

	public function has_primary_container() {
		if (!$this->skeleton->built('primary_container')) $this->build_primary_container();
		return $this->skeleton->has('primary_container');
	}

	public function get_primary_container() {
		if (!$this->has_primary_container()) return NULL;
		return $this->skeleton->get('primary_container');
	}

	public static function get_product_ids_for_product_feeds() {
		//exclude shipping and coniditioning supplies AND only grab active/non-archived products
		return prepared_query::fetch('SELECT p.products_id FROM products p LEFT JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE p.'.self::get_active_context_key().' = 1 AND archived = 0 AND psc.products_stock_control_category_id NOT IN (90, 97)', cardinality::COLUMN);
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public static function create(Array $data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if (empty($data['products_date_availbale'])) $data['products_date_available'] = prepared_expression::NOW();
			$data['products_date_added'] = prepared_expression::NOW();

			$prod = new prepared_fields($data, prepared_fields::INSERT_QUERY);
			$prod->whitelist(['products_model', 'products_date_available', 'manufacturers_id', 'stock_id', 'products_date_added']);

			$products_id = prepared_query::insert('INSERT INTO products ('.$prod->insert_fields().') VALUES ('.$prod->insert_values().')', $prod->insert_parameters());

			$product = new self($products_id);

			$data['products_id'] = $product->id();
			$data['language_id'] = 1;
			$data['products_head_desc_tag'] = '';
			$data['products_ebay_name'] = '';
			$data['products_ebay_subtitle'] = '';
			$data['products_google_name'] = '';
			$data['products_seo_url'] = '';

			$pd = new prepared_fields($data, prepared_fields::INSERT_QUERY);
			$pd->whitelist(['products_id', 'language_id', 'products_name', 'products_head_desc_tag', 'products_ebay_name', 'products_ebay_subtitle', 'products_google_name', 'products_seo_url']);

			prepared_query::execute('INSERT INTO products_description ('.$pd->insert_fields().') VALUES ('.$pd->insert_values().')', $pd->insert_parameters());
		}
		catch (CKProductListingException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKProductListingException('Failed to create product listing: '.$e->getMessage(), $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}

		return $product;
	}

	public function set_salsify_id($salsify_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('UPDATE products SET salsify_id = :salsify_id WHERE products_id = :products_id', [':salsify_id' => $salsify_id, ':products_id' => $this->id()]);
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKProductListingException('Failed to set salsify_id.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function activate() {
		if ($this->is_active()) return TRUE;
		else {
			self::query_execute('UPDATE products SET products_status = 1, products_last_modified = NOW() WHERE products_id = :products_id', cardinality::NONE, [':products_id' => $this->id()]);

			$this->get_ipn()->create_change_history_record('Product Status Change - '.$this->get_header('products_model'), 'Off', 'On');

			$this->skeleton->rebuild('header');
			return TRUE;
		}
	}

	public function deactivate() {
		if (!$this->is_active()) return TRUE;
		else {
			self::query_execute('UPDATE products SET products_status = 0, products_last_modified = NOW() WHERE products_id = :products_id', cardinality::NONE, [':products_id' => $this->id()]);

			$this->get_ipn()->create_change_history_record('Product Status Change - '.$this->get_header('products_model'), 'On', 'Off');

			$this->skeleton->rebuild('header');
			return TRUE;
		}
	}

	public function update_status($status) {
		$old_status = $this->is('products_status');

		self::query_execute('UPDATE products SET products_status = :status, products_last_modified = NOW() WHERE products_id = :products_id', cardinality::NONE, [':status' => ($status?1:0), ':products_id' => $this->id()]);
		$this->skeleton->rebuild('header');

		$this->get_ipn()->create_change_history_record('Product Status Change - '.$this->get_header('products_model'), ($old_status?'On':'Off'), ($status?'On':'Off'));
	}

	public function activate_broker() {
		if ($this->is_active()) return TRUE;
		else {
			self::query_execute('UPDATE products SET broker_status = 1, products_last_modified = NOW() WHERE products_id = :products_id', cardinality::NONE, [':products_id' => $this->id()]);

			$this->get_ipn()->create_change_history_record('Broker Status Change - '.$this->get_header('products_model'), 'Off', 'On');

			$this->skeleton->rebuild('header');
			return TRUE;
		}
	}

	public function deactivate_broker() {
		if (!$this->is_active()) return TRUE;
		else {
			self::query_execute('UPDATE products SET broker_status = 0, products_last_modified = NOW() WHERE products_id = :products_id', cardinality::NONE, [':products_id' => $this->id()]);

			$this->get_ipn()->create_change_history_record('Broker Status Change - '.$this->get_header('products_model'), 'On', 'Off');

			$this->skeleton->rebuild('header');
			return TRUE;
		}
	}

	public function update_broker_status($status) {
		$old_status = $this->is('products_status');

		self::query_execute('UPDATE products SET broker_status = :status, products_last_modified = NOW() WHERE products_id = :products_id', cardinality::NONE, [':status' => ($status?1:0), ':products_id' => $this->id()]);
		$this->skeleton->rebuild('header');

		$this->get_ipn()->create_change_history_record('Broker Status Change - '.$this->get_header('products_model'), ($old_status?'On':'Off'), ($status?'On':'Off'));
	}

	public function update_level_1_product($level_1_product) {
		$old_level_1_product = $this->is('level_1_product');

		self::query_execute('UPDATE products SET level_1_product = :level_1_product, products_last_modified = NOW() WHERE products_id = :products_id', cardinality::NONE, [':level_1_product' => ($level_1_product?1:0), ':products_id' => $this->id()]);
		$this->skeleton->rebuild('header');

		$this->get_ipn()->create_change_history_record('Product Level 1 Change - '.$this->get_header('products_model'), ($old_level_1_product?'On':'Off'), ($level_1_product?'On':'Off'));
	}

	public function set_as_primary_container($canonical, $redirect) {
		$savepoint = self::transaction_begin();

		try {
			$canonical = CK\fn::check_flag($canonical)?1:0;
			$redirect = CK\fn::check_flag($redirect)?1:0;
			$this->get_ipn()->set_primary_merchandising_container(2, $this->id(), $canonical, $redirect);
			
			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	public function remove_as_primary_container() {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('DELETE FROM ck_merchandising_primary_containers WHERE container_type_id = :container_type_id AND container_id = :container_id', cardinality::NONE, [':container_type_id' => 2, ':container_id' => $this->id()]);

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	public function set_primary_merchandising_container($container_type_id, $container_id, $canonical, $redirect) {
		$savepoint = self::transaction_begin();

		try {
			$this->remove_primary_merchandising_container();

			self::query_execute('INSERT INTO ck_merchandising_primary_containers (stock_id, products_id, container_type_id, container_id, canonical, redirect) VALUES (:stock_id, :products_id, :container_type_id, :container_id, :canonical, :redirect)', cardinality::NONE, [':stock_id' => $this->get_header('stock_id'), ':products_id' => $this->id(), ':container_type_id' => $container_type_id, ':container_id' => $container_id, ':canonical' => $canonical, ':redirect' => $redirect]);

			self::transaction_commit($savepoint);
		}
		catch (CKIpnException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKIpnException('Error updating local Channel Advisor allocated qty: '.$e->getMessage());
		}
	}

	public function remove_primary_merchandising_container() {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('DELETE FROM ck_merchandising_primary_containers WHERE products_id = :products_id', cardinality::NONE, [':products_id' => $this->id()]);

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKProductListingException('Error deleting previous primary merchandising container: '.$e->getMessage());
		}
	}

	public function add_included_product(Array $data) {
		// this is initially being created for the bundle product included product import
		// the data that is expected is included product id, bundle quantity, custom name/title
		try {
			$check = self::query_fetch('SELECT id FROM product_addons WHERE product_id = :product_id AND product_addon_id = :product_addon_id', cardinality::SINGLE, [':product_id' => $this->id(), 'product_addon_id' => $data['included_product_id']]);
			if (empty($check)) {
				self::query_execute('INSERT INTO product_addons (product_id, product_addon_id, recommended, custom_name, use_custom_name, included, bundle_quantity) VALUES (:product_id, :product_addon_id, :recommended, :custom_name, :use_custom_name, :included, :bundle_quantity)', cardinality::NONE, [':product_id' => $this->id(), ':product_addon_id' => $data['included_product_id'], ':recommended' => 1, ':custom_name' => $data['custom_title'], ':use_custom_name' => 1, ':included' => 1, ':bundle_quantity' => $data['bundle_quantity']]);
			}
			return TRUE;
		}
		catch (Exception $e) {
			throw $e;
		}
	}

	public function assign_category($categories_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('INSERT INTO products_to_categories (products_id, categories_id) VALUES (:products_id, :categories_id) ON DUPLICATE KEY UPDATE products_id=products_id', [':products_id' => $this->id(), ':categories_id' => $categories_id]);
		}
		catch (CKProductListingException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKProductListingException('Failed to assign category to product listing.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function remove_category($categories_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('DELETE FROM products_to_categories WHERE products_id = :products_id AND categories_id = :categories_id', [':products_id' => $this->id(), ':categories_id' => $categories_id]);
		}
		catch (CKProductListingException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKProductListingException('Failed to remove category from product listing.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function set_category_list(Array $categories_ids) {
		$categories_ids = array_filter($categories_ids, function($c) {
			return !empty($c) && $c != '0';
		});

		$old_categories_ids = array_map(function($c) {
			return $c->id();
		}, $this->has_categories()?$this->get_categories():[]);

		$add = array_diff($categories_ids, $old_categories_ids);
		$remove = array_diff($old_categories_ids, $categories_ids);

		foreach ($add as $categories_id) {
			$this->assign_category($categories_id);
		}

		foreach ($remove as $categories_id) {
			$this->remove_category($categories_id);
		}
	}

	public function set_special($special) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$ipn = $this->get_ipn();

			foreach ($ipn->get_listings() as $listing) {

				$change_record = [];
				$old_record = [];

				if ($listing->has_special()) {
					$specl = $listing->get_special();

					$special['specials_last_modified'] = prepared_expression::NOW();

					$spec = new prepared_fields($special, prepared_fields::UPDATE_QUERY);
					$spec->whitelist(['specials_new_products_price', 'specials_last_modified', 'expires_date', 'status', 'specials_qty', 'active_criteria']);

					$id = new prepared_fields(['specials_id' => $specl['specials_id']]);

					prepared_query::execute('UPDATE specials SET '.$spec->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($spec, $id));

					if ($specl['status'] != $special['status']) {
						$old_record[] = $specl['status']?'Status On':'Status Off';
						$change_record[] = $special['status']?'Status On':'Status Off';
					}

					if ($specl['specials_new_products_price'] != $special['specials_new_products_price']) {
						$old_record[] = 'Price: '.$specl['specials_new_products_price'];
						$change_record[] = 'Price: '.$special['specials_new_products_price'];
					}

					if ($specl['specials_qty'] != $special['specials_qty']) {
						$old_record[] = 'Qty: '.$specl['specials_qty'];
						$change_record[] = 'Qty: '.$special['specials_qty'];
					}

					$oed = ck_datetime::datify($specl['expires_date']);
					$ned = ck_datetime::datify($special['expires_date']);

					if ($oed != $ned) {
						$old_record[] = 'Expires: '.(!empty($oed)?$oed->format('m/d/Y H:i:s'):'');
						$change_record[] = 'Expires: '.(!empty($ned)?$ned->format('m/d/Y 23:59:59'):'');
					}
				}
				else {
					$special['products_id'] = $listing->id();
					$special['specials_date_added'] = prepared_expression::NOW();
					$special['specials_last_modified'] = prepared_expression::NOW();

					$spec = new prepared_fields($special, prepared_fields::INSERT_QUERY);
					$spec->whitelist(['products_id', 'specials_new_products_price', 'specials_date_added', 'specials_last_modified', 'expires_date', 'status', 'specials_qty', 'active_criteria']);

					prepared_query::execute('INSERT INTO specials ('.$spec->insert_fields().') VALUES ('.$spec->insert_values().')', $spec->insert_parameters());

					$change_record[] = 'Status '.(CK\fn::check_flag($special['status'])?'On':'Off');
					$change_record[] = 'Price: '.$special['specials_new_products_price'];
					$change_record[] = 'Qty: '.$special['specials_qty'];
					$change_record[] = 'Expires: '.(!empty($special['expires_date'])?$special['expires_date']:'NONE');
				}

				insert_psc_change_history($ipn->id(), 'Special Update ['.$listing->get_header('products_model').']', implode(' | ', $old_record), implode(' | ', $change_record));
			}
		}
		catch (CKProductListingException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKProductListingException('Failed to set special for product listing.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/

	public static function is_valid_product_id($product_id) {
		$result = FALSE;
		if (is_numeric($product_id)) $result = self::query_fetch('SELECT products_id FROM products WHERE products_id = :product_id', cardinality::SINGLE, [':product_id' => $product_id]);
		if ($result) return TRUE;
		return FALSE;
	}
}

class CKProductListingException extends Exception {
}
?>
