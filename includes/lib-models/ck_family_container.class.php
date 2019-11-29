<?php
class ck_family_container extends ck_archetype implements ck_merchandising_container_interface {
	protected static $skeleton_type = 'ck_family_container_type';

	protected static $queries = [
		'family_header' => [
			'qry' => 'SELECT family_container_id, name, url, url_with_categories, meta_title, meta_description, meta_keywords, summary, description, details, default_image, default_image_medium, default_image_small, template_id, nav_template_id, offer_template_id, show_lifetime_warranty, family_unit_id, default_family_unit_sibling_id, admin_only, active, date_created FROM ck_merchandising_family_containers WHERE family_container_id = :family_container_id',
			'cardinality' => cardinality::ROW,
		],

		'categories' => [
			'qry' => 'SELECT categories_id, default_relationship, date_created FROM ck_merchandising_container_category_relationships WHERE container_id = :family_container_id AND container_type_id = :container_type_id',
			'cardinality' => cardinality::SET,
		],

		'all_families' => [
			'qry' => 'SELECT family_container_id FROM ck_merchandising_family_containers ORDER BY name ASC',
			'cardinality' => cardinality::COLUMN,
		],

		'active_families' => [
			'qry' => 'SELECT family_container_id FROM ck_merchandising_family_containers WHERE active = 1 ORDER BY name ASC',
			'cardinality' => cardinality::COLUMN,
		]
	];

	public function __construct($family_container_id, ck_family_container_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($family_container_id);

		if (!$this->skeleton->built('family_container_id')) $this->skeleton->load('family_container_id', $family_container_id);

		self::register($family_container_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('family_container_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('family_header', [':family_container_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$header['url_with_categories'] = CK\fn::check_flag($header['url_with_categories']);
		$header['show_lifetime_warranty'] = CK\fn::check_flag($header['show_lifetime_warranty']);
		$header['admin_only'] = CK\fn::check_flag($header['admin_only']);
		$header['active'] = CK\fn::check_flag($header['active']);
		$header['date_created'] = self::DateTime($header['date_created']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('family_header', [':family_container_id' => $this->id()]));
		$this->normalize_header();
	}

	private function build_base_url() {
		$header = $this->get_header();

		$url_parts = ['mc'];

		if ($this->is('url_with_categories') && $this->has_categories() && $cat = $this->get_categories('default')) {
			$cat = $cat['category'];

			$cats = [];

			if ($cat->is('use_seo_urls')) {
				if (!empty($cat->get_header('seo_url_parent_text'))) $cats[] = $cat->get_header('seo_url_parent_text');
				elseif (!empty($cat->get_header('seo_url_text'))) $cats[] = $cat->get_header('seo_url_text');
				else $cats[] = $cat->get_header('categories_name');
			}
			else $cats[] = $cat->get_header('categories_name');

			if ($cat->has_ancestors()) {
				foreach ($cat->get_ancestors() as $anc) {
					if ($anc->is('use_seo_urls')) {
						if (!empty($anc->get_header('seo_url_parent_text'))) $cats[] = $anc->get_header('seo_url_parent_text');
						elseif (!empty($anc->get_header('seo_url_text'))) $cats[] = $anc->get_header('seo_url_text');
						else $cats[] = $anc->get_header('categories_name');
					}
					else $cats[] = $anc->get_header('categories_name');
				}
			}

			$cats = array_reverse($cats);

			foreach ($cats as $cat) $url_parts[] = $cat;
		}

		if (!empty($header['url'])) $url_parts[] = $header['url'];
		else $url_parts[] = $header['name'];

		$url_parts[] = 'fam-'.$this->id();

		$url = new url_handler(implode('/', $url_parts));

		$url = '/'.$url->full_seo_transform()->get_url().'/';

		$this->skeleton->load('base_url', $url);
	}

	private function build_templates() {
		$templates = ['template' => NULL, 'nav_template' => NULL, 'offer_template' => NULL];

		$templates['template'] = new ck_managed_template($this->get_header('template_id'));
		$templates['nav_template'] = new ck_managed_template($this->get_header('nav_template_id'));
		if (!empty($this->get_header('offer_template_id'))) {
			$templates['offer_template'] = new ck_managed_template($this->get_header('offer_template_id'));
		}

		$this->skeleton->load('templates', $templates);
	}

	private function build_family_unit() {
		$this->skeleton->load('family_unit', new ck_family_unit($this->get_header('family_unit_id')));
	}

	private function build_categories() {
		$categories = self::fetch('categories', [':family_container_id' => $this->id(), ':container_type_id' => $this->skeleton->lookup('container_type', 'container_type_id')]);

		foreach ($categories as &$category) {
			$category['category'] = new ck_listing_category($category['categories_id']);
			$category['default_relationship'] = CK\fn::check_flag($category['default_relationship']);
			$category['date_created'] = self::DateTime($category['date_created']);
		}

		$this->skeleton->load('categories', $categories);
	}

	private function build_first_selected_product($selections=NULL) {
		$listing = NULL;

		$family_unit = $this->get_family_unit();

		$possible_siblings = array_filter(array_map(function($sib) { return !empty($sib['products_id'])?$sib['family_unit_sibling_id']:NULL; }, $family_unit->get_siblings()));

		if (!empty($selections)) {
			$variances = $family_unit->get_variances();
			$listing_variant_options = $family_unit->get_listing_variant_options();

			foreach ($variances as $variance) {
				$opt_var_type = htmlspecialchars($variance['name_key']);
				if (empty($selections[$opt_var_type])) continue;
				if (empty($listing_variant_options[$variance['family_unit_variance_id']])) continue;

				foreach ($listing_variant_options[$variance['family_unit_variance_id']] as $value => $products) {
					if ($variance['field_name'] == 'condition') $value = ck_ipn2::get_condition_name($value);
					$value = htmlspecialchars(preg_replace('/\s+/', '', $value));

					if ($value != $selections[$opt_var_type]) continue;

					$possible_siblings = array_intersect($possible_siblings, array_map(function($prod) {
						return $prod['family_unit_sibling_id'];
					}, $products));
				}
			}

			if (!empty($possible_siblings)) {
				$selected_sibling = $family_unit->get_siblings(current($possible_siblings));
				$listing = $this->normalize_listing($selected_sibling);
			}
		}
		else {
			$header = $this->get_header();

			if (!empty($header['default_family_unit_sibling_id'])) {
				$selected_sibling = $family_unit->get_siblings($header['default_family_unit_sibling_id']);
				$listing = $this->normalize_listing($selected_sibling);
			}

			if (empty($listing)) { // it wasn't viewable
				foreach ($family_unit->get_siblings() as $sibling) {
					$listing = $this->normalize_listing($sibling);
					if (!empty($listing)) break; // we only want the first one
				}
			}
		}

		$this->skeleton->load('first_selected_product', $listing);
	}

	private function normalize_listing($sibling) {
		$product = new ck_product_listing($sibling['products_id']);
		if (!$product->is_viewable()) return NULL;

		$detail = $product->get_header();
		$images = $product->get_image();

		$listing = [
			'family_unit_sibling_id' => $sibling['family_unit_sibling_id'],
			'stock_id' => $sibling['stock_id'],
			'products_id' => $sibling['products_id'],
			'products_model' => $detail['products_model'],
			'products_name' => $detail['products_name'],
			'products_head_desc_tag' => $detail['products_head_desc_tag'],
			'always_available' => $detail['always_available'],
			'lead_time' => $detail['lead_time'],
			'conditions' => $detail['conditions'],
			'conditions_name' => $detail['conditions_name'],
			'discontinued' => $detail['discontinued'],
			'images' => [
				'products_image' => $images['products_image'],
				'products_image_med' => $images['products_image_med'],
				'products_image_lrg' => $images['products_image_lrg'],
				'products_image_sm_1' => $images['products_image_sm_1'],
				'products_image_xl_1' => $images['products_image_xl_1'],
				'products_image_sm_2' => $images['products_image_sm_2'],
				'products_image_xl_2' => $images['products_image_xl_2'],
				'products_image_sm_3' => $images['products_image_sm_3'],
				'products_image_xl_3' => $images['products_image_xl_3'],
				'products_image_sm_4' => $images['products_image_sm_4'],
				'products_image_xl_4' => $images['products_image_xl_4'],
				'products_image_sm_5' => $images['products_image_sm_5'],
				'products_image_xl_5' => $images['products_image_xl_5'],
				'products_image_sm_6' => $images['products_image_sm_6'],
				'products_image_xl_6' => $images['products_image_xl_6'],
			],
			'prices' => $product->get_price(),
			'inventory' => $product->get_inventory(),
			'attributes' => [],
			'has_special' => FALSE,
		];

		foreach ($product->get_attributes() as $attribute) {
			$listing['attributes'][$attribute['attribute_key_id']] = $attribute;
		}

		if ($product->has_special()) $listing['has_special'] = TRUE;

		$listing['schema'] = [
			'mpn' => $listing['products_model'], // model #
			'name' => htmlspecialchars($listing['products_name']),
			'sku' => $listing['products_id'], // product ID
			'url' => $this->get_url($product),
			'image' => $listing['images']['products_image_lrg'],
			'brand' => $product->get_manufacturer(),
			'price' => $listing['prices']['display'],
			'price_currency' => 'USD',
			'availability' => $listing['inventory']['display_available_num']>0?'InStock':'OutOfStock',
			'condition' => ck_ipn2::get_condition_name($listing['conditions'], 'meta'),
			'inventory_level' => $listing['inventory']['display_available_num'],
			'description' => '',
			'warranty' => '100 years',
		];

		return $listing;
	}

	private function build_first_selections($raw_selections=NULL) {
		$selection = [];

		if (!empty($raw_selections)) {
			$selections = explode('/', $raw_selections);
			foreach ($selections as $sel) {
				if (empty($sel)) continue;
				$parts = explode('=', $sel);

				$selection[$parts[0]] = $parts[1];
			}
		}
		else {
			$listing = $this->get_first_selected_product();

			$variances = $this->get_family_unit()->get_variances();

			foreach ($listing['attributes'] as $attr) {
				$opt_var_type = htmlspecialchars($attr['attribute_key']);
				$opt_var_val = htmlspecialchars(preg_replace('/\s+/', '', $attr['value']));

				$selection[$opt_var_type] = $opt_var_val;
			}

			foreach ($variances as $variance) {
				if ($variance['target'] == 'attribute') continue;
				if ($variance['key'] != 'condition') continue;

				$condition = ck_ipn2::get_condition_name($listing['conditions']);

				$opt_var_type = htmlspecialchars($variance['name_key']);
				$opt_var_val = htmlspecialchars(preg_replace('/\s+/', '', $condition));

				$selection[$opt_var_type] = $opt_var_val;
			}
		}

		$this->skeleton->load('first_selections', $selection);
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

	public function get_family_unit() {
		if (!$this->skeleton->built('family_unit')) $this->build_family_unit();
		return $this->skeleton->get('family_unit');
	}

	public function get_templates($key=NULL) {
		if (!$this->skeleton->built('templates')) $this->build_templates();
		if (empty($key)) return $this->skeleton->get('templates');
		else return $this->skeleton->get('templates', $key);
	}

	public function has_categories() {
		if (!$this->skeleton->built('categories')) $this->build_categories();
		return $this->skeleton->has('categories');
	}

	public function get_categories($key=NULL) {
		if (!$this->has_categories()) return [];
		if (empty($key)) return $this->skeleton->get('categories');
		elseif ($key == 'default') {
			foreach ($this->skeleton->get('categories') as $cat) {
				if ($cat['default_relationship']) return $cat;
			}

			return NULL;
		}
	}

	public function is_active() {
		return $this->found() && $this->is('active') && $this->get_family_unit()->is_active();
	}

	public function is_viewable() {
		// if we couldn't even find it, there's nothing to view
		if (empty($this->get_header())) return FALSE;

		// if we're in the admin, we can view it regardless of any other details
		if ($_SESSION['current_context'] == 'backend') return TRUE;

		// we're on the front end - if it's not active, we can't view it
		if (!$this->is_active()) return FALSE;

		// if we're an admin on the front end, we can see it regardless of any other details
		if (@$_SESSION['admin'] == 'true') return TRUE;

		// we're not an admin and this is an admin-only product, we can't view it
		if ($this->is('admin_only')) return FALSE;

		// nothing else factors into it, we can view it
		return TRUE;
	}

	public function get_url_alternate($listing) {
		$url = $this->get_base_url();

		// product listing was passed in
		$url .= 'fp-'.$listing->id().'/';
		/*$family_unit = $this->get_family_unit();
		$selection_parts = [];
		if ($family_unit->has_variances()) {
			foreach ($family_unit->get_variances() as $variance) {
				if ($variance['target'] == 'field' && $variance['key'] == 'condition') {
					$opt = htmlspecialchars(preg_replace('/\s+/', '', ck_ipn2::get_condition_name($listing['conditions'])));
				}
				elseif ($variance['target'] == 'attribute') {
					$attribute = $listing['attributes'][$variance['attribute_id']];

					$opt = htmlspecialchars(preg_replace('/\s+/', '', $attribute['value']));
				}

				$key = !empty($variance['name'])?$variance['name']:$variance['key'];

				$selection_parts[htmlspecialchars(preg_replace('/\s+/', '', $key))] = $opt;
			}

			$selection = [];
			foreach ($selection_parts as $key => $val) {
				$selection[] = $key.'='.$val;
			}

			$url .= implode('/', $selection).'/';
		}*/

		return $url;
	}

	public function get_url(ck_merchandising_container_interface $context=NULL) {
		$url = $this->get_base_url();

		if ($context) {
			// product listing was passed in
			$url .= 'fp-'.$context->id().'/';
			/*$family_unit = $this->get_family_unit();
			$selection_parts = [];
			if ($family_unit->has_variances()) {
				foreach ($family_unit->get_variances() as $variance) {
					if ($variance['target'] == 'field' && $variance['key'] == 'condition') {
						$opt = htmlspecialchars(preg_replace('/\s+/', '', $context->get_ipn()->get_condition()));
					}
					elseif ($variance['target'] == 'attribute') {
						$attribute = $context->get_attributes($variance['attribute_id']);

						$opt = htmlspecialchars(preg_replace('/\s+/', '', $attribute['value']));
					}

					$key = !empty($variance['name'])?$variance['name']:$variance['key'];

					$selection_parts[htmlspecialchars(preg_replace('/\s+/', '', $key))] = $opt;
				}

				$selection = [];
				foreach ($selection_parts as $key => $val) {
					$selection[] = $key.'='.$val;
				}

				$url .= implode('/', $selection).'/';
			}*/
		}

		return $url;
	}

	public function url_is_correct($request_uri=NULL) {
		if (empty($request_uri)) $request_uri = $_SERVER['REQUEST_URI'];

		$compare = parse_url($request_uri);

		$compare['path'] = preg_replace('#'.preg_quote(@$_GET['selections']).'#', '', $compare['path']);

		return $this->get_url() == $compare['path'];
	}

	public function redirect_if_necessary() {
		if (!$this->url_is_correct()) CK\fn::permanent_redirect($this->get_url());

		if (!$this->is_viewable()) {
			if ($this->has_categories() && $category = $this->get_categories('default')) CK\fn::redirect_and_exit($category['category']->get_url());
			else CK\fn::redirect_and_exit('/');
		}

		// old-style URLs - we gotta redirect to the correct product
		//if (!empty($_GET['selections'])) {
			$first_selections = $this->get_first_selections(@$_GET['selections']);
			//var_dump($first_selections);
			$first_selected_product = $this->get_first_selected_product($first_selections);
			//var_dump($first_selected_product);
			//exit();

			$l = new ck_product_listing($first_selected_product['products_id']);
			$l->redirect_if_necessary();
		//}
	}

	public function get_canonical_url(ck_merchandising_container_interface $context=NULL) {
		return $this->get_url($context); // for now, this container is always the canonical URL for itself
	}

	public function get_title() {
		$header = $this->get_header();

		if (!empty($header['meta_title'])) return $header['meta_title'];
		else return $header['name'];
	}

	public function get_meta_description() {
		$header = $this->get_header();

		if (!empty($header['meta_description'])) return $header['meta_description'];
		else return $header['name'];
	}

	public function get_breadcrumbs() {
		$breadcrumbs = [];

		if ($this->has_categories() && $category = $this->get_categories('default')) {
			$category = $category['category'];
			if ($category->has_ancestors()) {
				$ancestors = array_reverse($category->get_ancestors());
				foreach ($ancestors as $ancestor) {
					$breadcrumbs[$ancestor->get_header('categories_name')] = $ancestor->get_url();
				}
				$breadcrumbs[$category->get_header('categories_name')] = $category->get_url();
			}
		}

		return $breadcrumbs;
	}

	public function get_first_selected_product($selections=NULL) {
		if (!$this->skeleton->built('first_selected_product')) $this->build_first_selected_product($selections);
		return $this->skeleton->get('first_selected_product');
	}

	public function get_first_selections($raw_selections=NULL) {
		if (!$this->skeleton->built('first_selections')) $this->build_first_selections($raw_selections);
		return $this->skeleton->get('first_selections');
	}

	public function get_listing_lookup() {
		$family_unit = $this->get_family_unit();

		$listings = $family_unit->get_listing_details();
		$variances = $family_unit->get_variances();

		$listing_lookup = [];

		foreach ($listings as $lst) {
			$images = $lst['images'];

			$listing_lookup[$lst['products_id']] = [
				'variances' => [],
				'model_number' => $lst['products_model'],
				'hero_image_src' => $images['products_image_lrg'],
				'image_title' => htmlspecialchars($lst['products_name']),
				'carousel_image' => [],
				'item_price' => CK\text::monetize($lst['prices']['display']),
				'item_price_number' => $lst['prices']['display'],
				'products_id' => $lst['products_id'],
				'discontinued' => !empty($lst['discontinued'])?1:0,
				'quantity' => $lst['inventory']['display_available_num'],
				'included_options' => [],
			];

			if ($family_unit->has_grouped_variance()) {
				$lstobj = new ck_product_listing($lst['products_id']);
				if ($lstobj->has_options('included')) {
					foreach ($lstobj->get_options('included') as $option) {
						$option['listing_url'] = $option['listing']->get_url();
						if ($option['listing']->is_viewable()) $listing_lookup[$lst['products_id']]['included_options'][] = $option;
					}
				}
			}

			$listing_lookup[$lst['products_id']]['carousel_image'][] = [
				'large_src' => $images['products_image_lrg'],
				'thumb_src' => $images['products_image']
			];

			for ($i=1; $i<=6; $i++) {
				if (empty($images['products_image_sm_'.$i]) || empty($images['products_image_xl_'.$i])) continue;

				$listing_lookup[$lst['products_id']]['carousel_image'][] = [
					'large_src' => $images['products_image_xl_'.$i],
					'thumb_src' => $images['products_image_sm_'.$i]
				];
			}

			if ($lst['prices']['reason'] == 'special') {
				$listing_lookup[$lst['products_id']]['regular_price'] = CK\text::monetize($lst['prices']['original']);
				$listing_lookup[$lst['products_id']]['regular_price_number'] = $lst['prices']['original'];
			}
			elseif (!empty($lst['prices']['bundle_rollup'])) {
				if ($lst['prices']['reason'] == 'dealer') {
					$listing_lookup[$lst['products_id']]['regular_price'] = CK\text::monetize($lst['prices']['bundle_dealer']);
					$listing_lookup[$lst['products_id']]['regular_price_number'] = $lst['prices']['bundle_dealer'];
				}
				elseif ($lst['prices']['reason'] == 'wholesale_high') {
					$listing_lookup[$lst['products_id']]['regular_price'] = CK\text::monetize($lst['prices']['bundle_wholesale_high']);
					$listing_lookup[$lst['products_id']]['regular_price_number'] = $lst['prices']['bundle_wholesale_high'];
				}
				elseif ($lst['prices']['reason'] == 'wholesale_low') {
					$listing_lookup[$lst['products_id']]['regular_price'] = CK\text::monetize($lst['prices']['bundle_wholesale_low']);
					$listing_lookup[$lst['products_id']]['regular_price_number'] = $lst['prices']['bundle_wholesale_low'];
				}
				else {
					$listing_lookup[$lst['products_id']]['regular_price'] = CK\text::monetize($lst['prices']['bundle_original']);
					$listing_lookup[$lst['products_id']]['regular_price_number'] = $lst['prices']['bundle_original'];
				}
			}

			if ($lst['inventory']['display_available'] > 0) {
				$listing_lookup[$lst['products_id']]['in_stock_number'] = $lst['inventory']['display_available'];
				$listing_lookup[$lst['products_id']]['in_stock_indicator'] = 'in Stock';

				if (ck_product_listing::calc_ship_date() == 'Today') $listing_lookup[$lst['products_id']]['in_stock_ships'] = '- <span class="ships-date">Ships Today</span>';
				else $listing_lookup[$lst['products_id']]['in_stock_ships'] = '- Ships next business day';

				if ($lst['always_available'] && $lst['lead_time'] < 30) $listing_lookup[$lst['products_id']]['in_stock_additional'] = 'Additional quantities available - Ships in '.$lst['lead_time'].' business day(s)';
			}
			elseif ($lst['always_available']) {
				$listing_lookup[$lst['products_id']]['in_stock_number'] = '';
				$listing_lookup[$lst['products_id']]['in_stock_indicator'] = 'Available';

				if ($lst['lead_time'] >= 6) $listing_lookup[$lst['products_id']]['in_stock_ships'] = '- <span class="ships-date">Ships '.ck_product_listing::calc_ship_date($lst['lead_time']).'</span>';
				else $listing_lookup[$lst['products_id']]['in_stock_ships'] = '- <span class="ships-date">Ships in '.$lst['lead_time'].'-'.($lst['lead_time']+1).' business days</span>';
			}
			else {
				$listing_lookup[$lst['products_id']]['in_stock_number'] = '';
				if ($lst['inventory']['adjusted_on_order'] > 0) {
					$listing_lookup[$lst['products_id']]['in_stock_indicator'] = ($lst['inventory']['adjusted_on_order']).' on order';
					$listing_lookup[$lst['products_id']]['in_stock_ships'] = '- Please call to inquire about arrival date or additional quantity';
				}
				else {
					$listing_lookup[$lst['products_id']]['in_stock_indicator'] = 'Currently out of stock. If you need this item, we can get it for you! Please contact us to check on options available to meet your needs.';
					$listing_lookup[$lst['products_id']]['in_stock_ships'] = '';
				}
			}

			$attrs = [];
			foreach ($lst['attributes'] as $attr) {
				$attrs[$attr['family_unit_variance_id']] = $attr;
			}

			foreach ($variances as $variance) {
				$opt_var_type = htmlspecialchars($variance['name_key']);

				if ($variance['target'] == 'attribute') {
					$opt_var_val = htmlspecialchars(preg_replace('/\s+/', '', $attrs[$variance['family_unit_variance_id']]['value']));
				}
				elseif ($variance['key'] == 'condition') {
					$condition = ck_ipn2::get_condition_name($lst['conditions']);
					$opt_var_val = htmlspecialchars(preg_replace('/\s+/', '', $condition));
				}

				$listing_lookup[$lst['products_id']]['variances'][$opt_var_type] = $opt_var_val;
			}
		}

		return $listing_lookup;
	}

	public function get_prime_options() {
		$family_unit = $this->get_family_unit();
		$siblings = $family_unit->get_siblings();
		$listings = $family_unit->get_keyed_listing_details();
		$variances = $family_unit->get_variances();
		// we make two assumptions: 1 - there are exactly 2 variances, 2 - the grouped on variance is first

		$variance_group = $variances[0];
		$variance_set = $variances[1];

		$prime_options = [];

		$products = [];

		$variance_group['name'] = preg_replace('/\s+/', '', $variance_group['key']);
		$variance_set['name'] = preg_replace('/\s+/', '', $variance_set['key']);

		$group_option_lookup = [];
		$set_option_lookup = [];

		if ($variance_group['target'] == 'field' && $variance_group['key'] == 'condition') {
			$group_options = prepared_query::fetch('SELECT DISTINCT psc.conditions, fuvo.alias, fuvo.sort_order FROM products_stock_control psc JOIN ck_merchandising_family_unit_siblings fus ON psc.stock_id = fus.stock_id LEFT JOIN ck_merchandising_family_unit_variance_options fuvo ON psc.conditions = fuvo.value AND fuvo.family_unit_variance_id = :family_unit_variance_id WHERE fus.family_unit_id = :family_unit_id AND fus.active = 1 ORDER BY CASE WHEN fuvo.family_unit_variance_option_id IS NOT NULL THEN fuvo.sort_order ELSE psc.conditions END ASC', cardinality::SET, [':family_unit_id' => $family_unit->id(), ':family_unit_variance_id' => $variance_group['family_unit_variance_id']]);

			foreach ($group_options as $option) {
				$option['attribute_value'] = ck_ipn2::get_condition_name($option['conditions']);

				$group_option_lookup[$option['conditions']] = $option;
			}
		}
		elseif ($variance_group['target'] == 'attribute') {
			$group_options = prepared_query::fetch('SELECT DISTINCT a.value as attribute_value, fuvo.alias, fuvo.sort_order FROM ck_merchandising_family_unit_siblings fus JOIN ck_attributes a ON fus.stock_id = a.stock_id AND a.internal = 0 JOIN products p ON a.products_id = p.products_id AND p.products_status = 1 JOIN ck_merchandising_family_unit_variances fv ON fus.family_unit_id = fv.family_unit_id AND a.attribute_key_id = fv.attribute_id LEFT JOIN ck_merchandising_family_unit_variance_options fuvo ON fv.family_unit_variance_id = fuvo.family_unit_variance_id AND a.value = fuvo.value WHERE fus.family_unit_id = :family_unit_id AND fus.active = 1 AND fv.family_unit_variance_id = :family_unit_variance_id ORDER BY CASE WHEN fuvo.family_unit_variance_option_id IS NOT NULL THEN fuvo.sort_order ELSE a.value END ASC', cardinality::SET, [':family_unit_id' => $family_unit->id(), ':family_unit_variance_id' => $variance_group['family_unit_variance_id']]);

			foreach ($group_options as $option) {
				$group_option_lookup[$option['attribute_value']] = $option;
			}
		}

		if ($variance_set['target'] == 'field' && $variance_set['key'] == 'condition') {
			$set_options = prepared_query::fetch('SELECT DISTINCT psc.conditions, fuvo.alias, fuvo.sort_order FROM products_stock_control psc JOIN ck_merchandising_family_unit_siblings fus ON psc.stock_id = fus.stock_id LEFT JOIN ck_merchandising_family_unit_variance_options fuvo ON psc.conditions = fuvo.value AND fuvo.family_unit_variance_id = :family_unit_variance_id WHERE fus.family_unit_id = :family_unit_id AND fus.active = 1 ORDER BY CASE WHEN fuvo.family_unit_variance_option_id IS NOT NULL THEN fuvo.sort_order ELSE psc.conditions END ASC', cardinality::SET, [':family_unit_id' => $family_unit->id(), ':family_unit_variance_id' => $variance_set['family_unit_variance_id']]);

			foreach ($set_options as $option) {
				$option['attribute_value'] = ck_ipn2::get_condition_name($option['conditions']);

				$set_option_lookup[$option['conditions']] = $option;
			}
		}
		elseif ($variance_set['target'] == 'attribute') {
			$set_options = prepared_query::fetch('SELECT DISTINCT a.value as attribute_value, fuvo.alias, fuvo.sort_order FROM ck_merchandising_family_unit_siblings fus JOIN ck_attributes a ON fus.stock_id = a.stock_id AND a.internal = 0 JOIN products p ON a.products_id = p.products_id AND p.products_status = 1 JOIN ck_merchandising_family_unit_variances fv ON fus.family_unit_id = fv.family_unit_id AND a.attribute_key_id = fv.attribute_id LEFT JOIN ck_merchandising_family_unit_variance_options fuvo ON fv.family_unit_variance_id = fuvo.family_unit_variance_id AND a.value = fuvo.value WHERE fus.family_unit_id = :family_unit_id AND fus.active = 1 AND fv.family_unit_variance_id = :family_unit_variance_id ORDER BY CASE WHEN fuvo.family_unit_variance_option_id IS NOT NULL THEN fuvo.sort_order ELSE a.value END ASC', cardinality::SET, [':family_unit_id' => $family_unit->id(), ':family_unit_variance_id' => $variance_set['family_unit_variance_id']]);

			foreach ($set_options as $option) {
				$set_option_lookup[$option['attribute_value']] = $option;
			}
		}

		foreach ($siblings as $sibling) {
			if (empty($listings[$sibling['family_unit_sibling_id']])) continue;
			$lst = $listings[$sibling['family_unit_sibling_id']];

			if ($variance_group['target'] == 'field' && $variance_group['key'] == 'condition') {
				$condition = ck_ipn2::get_condition_name($lst['conditions']);
				$group_key = htmlspecialchars(preg_replace('/\s+/', '', $condition));

				if (empty($prime_options[$group_key])) $prime_options[$group_key] = ['prime_variance_type' => htmlspecialchars($variance_group['name']), 'prime_variance_option' => $group_key, 'option_display' => $condition, 'value-lookup' => $lst['conditions'], 'secondary_options' => []];
			}
			elseif ($variance_group['target'] == 'attribute') {
				$attribute = $lst['attributes'][$variance_group['attribute_id']];

				$group_key = htmlspecialchars(preg_replace('/\s+/', '', $attribute['value']));

				if (empty($prime_options[$group_key])) $prime_options[$group_key] = ['prime_variance_type' => htmlspecialchars($variance_group['name']), 'prime_variance_option' => $group_key, 'option_display' => ucwords($attribute['value']), 'value-lookup' => $attribute['value'], 'secondary_options' => []];
			}

			if (!empty($group_option_lookup[$prime_options[$group_key]['value-lookup']]['alias'])) {
				$prime_options[$group_key]['option_display'] = ucwords($group_option_lookup[$prime_options[$group_key]['value-lookup']]['alias']);
			}

			if (!empty($prime_options)) {
				if ($variance_set['target'] == 'field' && $variance_set['key'] == 'condition') {
					$condition = ck_ipn2::get_condition_name($lst['conditions']);
					$set_key = htmlspecialchars(preg_replace('/\s+/', '', $condition));

					if (empty($prime_options[$group_key]['secondary_options'][$set_key])) $prime_options[$group_key]['secondary_options'][$set_key] = ['variance_type' => htmlspecialchars($variance_set['name']), 'option_value' => $set_key, 'option_display' => $condition, 'value-lookup' => $lst['conditions']];
				}
				elseif ($variance_set['target'] == 'attribute') {
					$attribute = $lst['attributes'][$variance_set['attribute_id']];

					$set_key = htmlspecialchars(preg_replace('/\s+/', '', $attribute['value']));

					if (empty($prime_options[$group_key]['secondary_options'][$set_key])) $prime_options[$group_key]['secondary_options'][$set_key] = ['variance_type' => htmlspecialchars($variance_set['name']), 'option_value' => $set_key, 'option_display' => ucwords($attribute['value']), 'value-lookup' => $attribute['value']];
				}

				if (!empty($set_option_lookup[$prime_options[$group_key]['secondary_options'][$set_key]['value-lookup']]['alias'])) {
					$prime_options[$group_key]['secondary_options'][$set_key]['option_display'] = ucwords($set_option_lookup[$prime_options[$group_key]['secondary_options'][$set_key]['value-lookup']]['alias']);
				}

				$prime_options[$group_key]['secondary_options'][$set_key]['price'] = CK\text::monetize($lst['prices']['display']);
				$prime_options[$group_key]['secondary_options'][$set_key]['price_number'] = $lst['prices']['display'];
				if ($lst['prices']['reason'] == 'special') {
					$prime_options[$group_key]['secondary_options'][$set_key]['regular_price'] = CK\text::monetize($lst['prices']['original']);
					$prime_options[$group_key]['secondary_options'][$set_key]['regular_price_number'] = $lst['prices']['original'];
				}
				elseif (!empty($lst['prices']['bundle_rollup'])) {
					if ($lst['prices']['reason'] == 'dealer') {
						$prime_options[$group_key]['secondary_options'][$set_key]['regular_price'] = CK\text::monetize($lst['prices']['bundle_dealer']);
						$prime_options[$group_key]['secondary_options'][$set_key]['regular_price_number'] = $lst['prices']['bundle_dealer'];
					}
					elseif ($lst['prices']['reason'] == 'wholesale_high') {
						$prime_options[$group_key]['secondary_options'][$set_key]['regular_price'] = CK\text::monetize($lst['prices']['bundle_wholesale_high']);
						$prime_options[$group_key]['secondary_options'][$set_key]['regular_price_number'] = $lst['prices']['bundle_wholesale_high'];
					}
					elseif ($lst['prices']['reason'] == 'wholesale_low') {
						$prime_options[$group_key]['secondary_options'][$set_key]['regular_price'] = CK\text::monetize($lst['prices']['bundle_wholesale_low']);
						$prime_options[$group_key]['secondary_options'][$set_key]['regular_price_number'] = $lst['prices']['bundle_wholesale_low'];
					}
					else {
						$prime_options[$group_key]['secondary_options'][$set_key]['regular_price'] = CK\text::monetize($lst['prices']['bundle_original']);
						$prime_options[$group_key]['secondary_options'][$set_key]['regular_price_number'] = $lst['prices']['bundle_original'];
					}
				}

				$prime_options[$group_key]['secondary_options'][$set_key]['products_id'] = $lst['products_id'];
			}
		}

		usort($prime_options, function($a, $b) use ($group_option_lookup) {
			if (empty($group_option_lookup[$a['value-lookup']]) && empty($group_option_lookup[$b['value-lookup']])) {
				return strcmp($a['option_display'], $b['option_display']);
			}
			elseif (empty($group_option_lookup[$b['value-lookup']])) return -1; // if only the first one is sorted, it comes first
			elseif (empty($group_option_lookup[$a['value-lookup']])) return 1; // if only the second on is sorted, it comes first
			else {
				if ($group_option_lookup[$a['value-lookup']]['sort_order'] < $group_option_lookup[$b['value-lookup']]['sort_order']) return -1;
				elseif ($group_option_lookup[$a['value-lookup']]['sort_order'] > $group_option_lookup[$b['value-lookup']]['sort_order']) return 1;
				else return 0;;
			}
		});

		// dereference named arrays into numerical arrays where we'll need to loop through them in mustache
		$prime_options = array_map(function($option) use ($set_option_lookup) {
			usort($option['secondary_options'], function($a, $b) use ($set_option_lookup) {
				if (empty($set_option_lookup[$a['value-lookup']]) && empty($set_option_lookup[$b['value-lookup']])) {
					return strcmp($a['option_display'], $b['option_display']);
				}
				elseif (empty($set_option_lookup[$b['value-lookup']])) return -1; // if only the first one is sorted, it comes first
				elseif (empty($set_option_lookup[$a['value-lookup']])) return 1; // if only the second on is sorted, it comes first
				else {
					if ($set_option_lookup[$a['value-lookup']]['sort_order'] < $set_option_lookup[$b['value-lookup']]['sort_order']) return -1;
					elseif ($set_option_lookup[$a['value-lookup']]['sort_order'] > $set_option_lookup[$b['value-lookup']]['sort_order']) return 1;
					else return 0;
				}
			});
			$option['secondary_options'] = array_values($option['secondary_options']);
			return $option;
		}, array_values($prime_options));

		return $prime_options;
	}

	public function get_variant_options() {
		$family_unit = $this->get_family_unit();
		$listing_variant_options = $family_unit->get_listing_variant_options();
		$variances = $family_unit->get_variances();

		$variants = [];

		foreach ($variances as $variance) {
			$opts = [];

			if (empty($variance['descriptor'])) unset($variance['descriptor']);

			$variance['variance_size'] = 0;

			foreach ($listing_variant_options[$variance['family_unit_variance_id']] as $value => $products) {
				$opt = NULL;

				if ($variance['target'] == 'field' && $variance['key'] == 'condition') {
					$condition = ck_ipn2::get_condition_name($value);
					$opt = ['variance_type' => htmlspecialchars($variance['name_key']), 'option_value' => htmlspecialchars(preg_replace('/\s+/', '', $condition)), 'option_display' => $condition];
				}
				elseif ($variance['target'] == 'attribute') {
					$opt = ['variance_type' => htmlspecialchars($variance['name_key']), 'option_value' => htmlspecialchars(preg_replace('/\s+/', '', $value)), 'option_display' => ucwords($value)];
				}

				if (!empty($opt)) $opts[$opt['option_value']] = $opt;
			}

			if ($variance['key'] == 'length') {
				ksort($opts, SORT_NUMERIC);
				foreach ($opts as $optkey => $opt) {
					$opts[$optkey]['option_display'] = preg_replace('/[^0-9.]/', '', $opt['option_display']);
				}
			}
			elseif (!($arr = array_filter($opts, function($var) { return !empty($var['option_value'])&&!is_numeric($var['option_value']); }))) {
				ksort($opts, SORT_NUMERIC);
			}
			else {
				ksort($opts, SORT_NATURAL);
			}

			foreach ($opts as $opt) {
				$variance['variance_size'] = max($variance['variance_size'], strlen($opt['option_display']));
			}

			$variance['variance_size'] = max(1, round($variance['variance_size'] * .54, 1));

			$variance['options'] = array_values($opts);

			$variants[] = $variance;
		}

		return $variants;
	}

	public function get_lookup_data($products_id) {
		$family_unit = $this->get_family_unit();

		$options = [];

		$lvos = prepared_query::fetch('SELECT * FROM (SELECT fv.family_unit_variance_id, IFNULL(fv.name, ak.attribute_key) as name_key, fv.descriptor, IFNULL(pv.products_id, MIN(ipv.products_id)) as sibling_products_id, IFNULL(fvo.alias, a.value) as value, a.products_id as attribute_products_id, fv.sort_order, fv.group_on, fvo.sort_order as vsort_order FROM ck_merchandising_family_unit_variances fv JOIN ck_attribute_keys ak ON fv.attribute_id = ak.attribute_key_id JOIN ck_attributes a ON ak.attribute_key_id = a.attribute_key_id JOIN ck_merchandising_family_unit_siblings fs ON fv.family_unit_id = fs.family_unit_id AND a.stock_id = fs.stock_id LEFT JOIN ck_merchandising_family_unit_variance_options fvo ON fv.family_unit_variance_id = fvo.family_unit_variance_id AND a.value = fvo.value LEFT JOIN ckv_product_viewable pv ON fs.products_id = pv.products_id AND pv.viewable_state = 2 LEFT JOIN ckv_product_viewable ipv ON fs.stock_id = ipv.stock_id AND ipv.viewable_state = 2 WHERE fv.family_unit_id = :family_unit_id AND fv.active = 1 GROUP BY fs.family_unit_sibling_id, fv.family_unit_variance_id HAVING sibling_products_id = attribute_products_id UNION SELECT fv.family_unit_variance_id, IFNULL(fv.name, fv.field_name) as name_key, fv.descriptor, IFNULL(pv.products_id, MIN(ipv.products_id)) as sibling_products_id, IFNULL(fvo.alias, psc.conditions) as value, NULL as attribute_products_id, fv.sort_order, fv.group_on, fvo.sort_order as vsort_order FROM ck_merchandising_family_unit_variances fv JOIN ck_merchandising_family_unit_siblings fs ON fv.family_unit_id = fs.family_unit_id JOIN products_stock_control psc ON fs.stock_id = psc.stock_id LEFT JOIN ck_merchandising_family_unit_variance_options fvo ON fv.family_unit_variance_id = fvo.family_unit_variance_id AND psc.conditions = fvo.value LEFT JOIN ckv_product_viewable pv ON fs.products_id = pv.products_id AND pv.viewable_state = 2 LEFT JOIN ckv_product_viewable ipv ON fs.stock_id = ipv.stock_id AND ipv.viewable_state = 2 WHERE fv.family_unit_id = :family_unit_id AND fv.attribute_id IS NULL AND fv.active = 1 GROUP BY fs.family_unit_sibling_id, fv.family_unit_variance_id HAVING sibling_products_id IS NOT NULL) lvos ORDER BY group_on DESC, sort_order ASC, vsort_order ASC', cardinality::SET, [':family_unit_id' => $family_unit->id()]);

		foreach ($lvos as $lvo) {
			$lvo['name_key'] = trim($lvo['name_key']);
			$lvo['value'] = trim($lvo['value']);

			$lvo['name_display'] = ucwords($lvo['name_key']);
			$lvo['name_key'] = htmlspecialchars(preg_replace('/\s+/', '', $lvo['name_key']));

			if ($lvo['name_key'] == 'length') $lvo['value'] = preg_replace('/[^0-9.]/', '', $lvo['value']);
			elseif ($lvo['name_key'] == 'condition') $lvo['value'] = ck_ipn2::get_condition_name($lvo['value']);

			$lvo['value_display'] = ucwords($lvo['value']);
			$lvo['value_key'] = htmlspecialchars(preg_replace('/\s+/', '', $lvo['value']));

			if (empty($options[$lvo['name_key']])) $options[$lvo['name_key']] = ['variance_id' => $lvo['family_unit_variance_id'], 'variance_display' => $lvo['name_display'], 'variance_key' => $lvo['name_key'], 'variance_size' => 0, 'values' => []];
			if (empty($options[$lvo['name_key']]['values'][$lvo['value_key']])) $options[$lvo['name_key']]['values'][$lvo['value_key']] = ['value_display' => $lvo['value_display'], 'value_key' => $lvo['value_key'], 'products_ids' => []];

			if (!empty($lvo['descriptor'])) $options[$lvo['name_key']]['descriptor'] = $lvo['descriptor'];

			$options[$lvo['name_key']]['variance_size'] = max($options[$lvo['name_key']]['variance_size'], strlen($lvo['value_display']));

			$options[$lvo['name_key']]['values'][$lvo['value_key']]['products_ids'][] = $lvo['sibling_products_id'];

			if ($lvo['sibling_products_id'] == $products_id) $options[$lvo['name_key']]['values'][$lvo['value_key']]['selected'] = 1;
		}

		if ($family_unit->has_grouped_variance()) {
		}
		else {
			foreach ($options as $variance => $data) {
				// if this is length, or all values are strictly numeric, sort as numbers
				if ($variance == 'length' || (!($arr = array_filter(array_keys($data['values']), function($var) { return !empty($var)&&!is_numeric($var); })))) {
					uasort($data['values'], function($a, $b) {
						if ($a['value_display'] == $b['value_display']) return 0;
						else return $a['value_display']<$b['value_display']?-1:1;
					});
				}
				// otherwise, sort natural
				else uasort($data['values'], function($a, $b) { return strnatcmp($a['value_display'], $b['value_display']); });

				$data['variance_size'] = max(1, round($data['variance_size'] * .54, 1));

				$options[$variance] = $data;
			}
		}

		return $options;
	}

	public static function get_all_family_containers() {
		if ($family_container_ids = self::fetch('all_families')) {
			$families = [];
			foreach ($family_container_ids as $family_container_id) {
				$families[] = new self($family_container_id);
			}
			return $families;
		}
		else return NULL;
	}

	public static function get_active_family_containers() {
		if ($family_container_ids = self::fetch('active_families')) {
			$families = [];
			foreach ($family_container_ids as $family_container_id) {
				$families[] = new self($family_container_id);
			}
			return $families;
		}
		else return NULL;
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public static function create(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data['header']);
			self::query_execute('INSERT INTO ck_merchandising_family_containers ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals(NULL, TRUE));
			$family_container_id = self::fetch_insert_id();

			$family_container = new self($family_container_id);

			if (!empty($data['category_relationships'])) {
				foreach ($data['category_relationships'] as $category) {
					$family_container->add_category_relationship($category);
				}
			}

			if (!empty($data['primary_container'])) {
				$family_container->set_as_primary_container(in_array($data['primary_container'], ['canonical', 'redirect']), $data['primary_container']=='redirect');
			}

			self::transaction_commit($savepoint);
			return $family_container;
		}
		catch (CKFamilyContainerException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyContainerException('Failed to create family container: '.$e->getMessage());
		}
	}

	public function update(Array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			self::query_execute('UPDATE ck_merchandising_family_containers SET '.$params->update_cols(TRUE).' WHERE family_container_id = :family_container_id', cardinality::NONE, $params->query_vals(['family_container_id' => $this->id()], TRUE));

			$this->skeleton->rebuild('header');
			$this->skeleton->rebuild('templates');
			$this->skeleton->rebuild('family_unit');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyContainerException('Failed to edit family container: '.$e->getMessage());
		}
	}

	public function activate() {
		if ($this->is_active()) return TRUE;
		else return $this->update(['active' => 1]);
	}

	public function deactivate() {
		if (!$this->is_active()) return TRUE;
		else return $this->update(['active' => 0]);
	}

	public function set_as_primary_container($canonical, $redirect) {
		$savepoint = self::transaction_begin();

		try {
			$family_unit = $this->get_family_unit();
			if ($family_unit->has_siblings()) {
				foreach ($family_unit->get_siblings() as $sib) {
					$canonical = CK\fn::check_flag($canonical)?1:0;
					$redirect = CK\fn::check_flag($redirect)?1:0;
					$ipn = new ck_ipn2($sib['stock_id']);
					$ipn->set_primary_merchandising_container($this->skeleton->lookup('container_type', 'container_type_id'), $this->id(), $canonical, $redirect);
				}
			}

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyContainerException('Failed to create set family container as the primary container: '.$e->getMessage());
		}
	}

	public function remove_as_primary_container() {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('DELETE FROM ck_merchandising_primary_containers WHERE container_type_id = :container_type_id AND container_id = :container_id', cardinality::NONE, [':container_type_id' => $this->skeleton->lookup('container_type', 'container_type_id'), ':container_id' => $this->id()]);

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyContainerException('Failed to create family container: '.$e->getMessage());
		}
	}

	public function set_as_primary_container_with_listing($canonical, $redirect) {
		$savepoint = self::transaction_begin();

		try {
			$family_unit = $this->get_family_unit();
			if ($family_unit->has_siblings()) {
				foreach ($family_unit->get_siblings() as $sib) {
					if (!empty($sib['products_id'])) {
						$listing = new ck_product_listing($sib['products_id']);
						$canonical = CK\fn::check_flag($canonical)?1:0;
						$redirect = CK\fn::check_flag($redirect)?1:0;
						$listing->set_primary_merchandising_container($this->skeleton->lookup('container_type', 'container_type_id'), $this->id(), $canonical, $redirect);
					}
				}
			}

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyContainerException('Failed to create set family container as the primary container: '.$e->getMessage());
		}
	}

	public function add_category_relationship($categories_id, $default=FALSE) {
		$savepoint = self::transaction_begin();

		try {
			if (CK\fn::check_flag($default)) self::query_execute('UPDATE ck_merchandising_container_category_relationships SET default_relationship = 0 WHERE container_id = :container_id AND categories_id != :categories_id AND container_type_id = :container_type_id', cardinality::NONE, [':container_id' => $this->id(), ':categories_id' => $categories_id, ':container_type_id' => $this->skeleton->lookup('container_type', 'container_type_id')]);

			self::query_execute('INSERT INTO ck_merchandising_container_category_relationships (container_id, categories_id, container_type_id, default_relationship) VALUES (:container_id, :categories_id, :container_type_id, :default_relationship)', cardinality::NONE, [':container_id' => $this->id(), ':categories_id' => $categories_id, ':container_type_id' => $this->skeleton->lookup('container_type', 'container_type_id'), ':default_relationship' => CK\fn::check_flag($default)?1:0]);

			$this->skeleton->rebuild('categories');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyContainerException('Failed to add cateogry to family container: '.$e->getMessage());
		}
	}

	public function update_category_relationship($categories_id, $default) {
		$savepoint = self::transaction_begin();

		try {
			// if we're turning default on, turn it off for everything else
			if (CK\fn::check_flag($default)) self::query_execute('UPDATE ck_merchandising_container_category_relationships SET default_relationship = 0 WHERE container_id = :container_id AND categories_id != :categories_id AND container_type_id = :container_type_id', cardinality::NONE, [':container_id' => $this->id(), ':categories_id' => $categories_id, ':container_type_id' => $this->skeleton->lookup('container_type', 'container_type_id')]);

			self::query_execute('UPDATE ck_merchandising_container_category_relationships SET default_relationship = :default_relationship WHERE container_id = :container_id AND categories_id = :categories_id AND container_type_id = :container_type_id', cardinality::NONE, [':container_id' => $this->id(), ':categories_id' => $categories_id, ':container_type_id' => $this->skeleton->lookup('container_type', 'container_type_id'), ':default_relationship' => CK\fn::check_flag($default)?1:0]);

			$this->skeleton->rebuild('categories');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyContainerException('Failed to set default status on category for family container: '.$e->getMessage());
		}
	}

	public function unset_default_category_relationship() {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE ck_merchandising_container_category_relationships SET default_relationship = 0 WHERE container_id = :container_id AND container_type_id = :container_type_id', cardinality::NONE, [':container_id' => $this->id(), ':container_type_id' => $this->skeleton->lookup('container_type', 'container_type_id')]);

			$this->skeleton->rebuild('categories');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyContainerException('Failed to set default status on category for family container: '.$e->getMessage());
		}
	}

	public function remove_category_relationship($categories_id) {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('DELETE FROM ck_merchandising_container_category_relationships WHERE container_id = :container_id AND categories_id = :categories_id AND container_type_id = :container_type_id', cardinality::NONE, [':container_id' => $this->id(), ':categories_id' => $categories_id, ':container_type_id' => $this->skeleton->lookup('container_type', 'container_type_id')]);

			$this->skeleton->rebuild('categories');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKFamilyContainerException('Failed to remove cateogry from family container: '.$e->getMessage());
		}
	}

	public function preload_resources() {
		$siblings = $this->get_family_unit()->get_siblings();

		ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);
		ck_ipn2::load_ipn_set(array_map(function($sib) { return $sib['stock_id']; }, $siblings));
	}
}

class CKFamilyContainerException extends CKMasterArchetypeException {
}
?>
