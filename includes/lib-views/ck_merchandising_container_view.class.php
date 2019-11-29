<?php
class ck_merchandising_container_view extends ck_view {

	private $container_type;
	private $container_id;
	private $container;

	private $prime_listing;

	public function __construct($context=NULL) {
		@ini_set('memory_limit', '512M');
		$this->init_container();

		$this->container->redirect_if_necessary();

		parent::__construct($context);
	}

	private function init_container() {
		if (!empty($this->container)) return;

		$container_id = $_GET['container_id'];

		list($this->container_type_key, $this->container_id) = explode('-', $container_id, 2);

		switch ($this->container_type_key) {
			case 'fam':
				$this->container = new ck_family_container($this->container_id);
				break;
			case 'fp':
				$this->container = new ck_product_listing($this->container_id);
				$path = explode('/', $_GET['url']);
				foreach ($path as $part) {
					if (!preg_match('/fam-\d+/', $part)) continue;
					list($fam, $fam_id) = explode('-', $part, 2);
					$this->container->set_prop('family_container', new ck_family_container($fam_id));
				}
				break;
		}
	}

	public function page_title() {
		return $this->container->get_title();
	}

	public function page_meta_description() {
		return $this->container->get_meta_description();
	}

	public function canonical_link() {
		return $this->container->get_canonical_url();
	}

	public function process_response() {
		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case '':
				break;
			default:
				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {

		switch ($_REQUEST['action']) {
			case 'load-selection':
				//$products_id = $_GET['products_id'];

				//$product = new ck_product_listing($_GET['products_id']);
				//$product->set_prop('family_container', $this->container);
				//$this->container = $product;

				$GLOBALS['breadcrumb'] = new breadcrumb;
				$GLOBALS['breadcrumb']->add('Home', HTTP_SERVER);

				$data = $this->build_family_container_product_data($this->data(), TRUE);

				require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
				require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

				$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
				$cktpl->buffer = TRUE;

				$data['schema'] = $cktpl->content(DIR_FS_CATALOG.'includes/templates/partial-product-schema.mustache.html', $data);

				echo json_encode(['product' => $data]);
				exit();

				break;
			default:
				break;
		}

		echo json_encode(['errors' => []]);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		if (isset($_SESSION['admin']) && $_SESSION['admin'] == 'true') $data['admin?'] = 1;
		$data['yotpo'] = ['appkey' => 'RS5sKge5HAa1Oe1gbgC5XkKMu2IATDFERi5Cypav'];

		$this->init_container();

		switch ($this->container_type_key) {
			case 'fam':
				$template = $this->container->get_templates('template');

				if ($offer_template = $this->container->get_templates('offer_template')) {
					$data['offer_template'] = function() use ($offer_template) { return '{{>'.$offer_template->get_header('template_location').'}}'; }; //1; //['_build_partial' => ]
					//$this->add_dynamic_partials(['dynamic_offer_partial' => $offer_template->get_header('template_location')]);
				}

				$data = $this->build_family_container_header_data($data);
				$this->render('partial-family-container-open.mustache.html', $data);
				$this->flush();

				if (!empty($offer_template)) $offer_template_location = $offer_template->get_header('template_location');
				else $offer_template_location = 'partial-family-container-offer-default.mustache.html';

				$data = $this->build_family_container_data($data);
				$this->render($offer_template_location, $data);
				$this->flush();

				$this->render('partial-family-container-close.mustache.html', $data);
				$this->flush();

				$data = $this->build_family_container_footer_data($data);
				$this->render($offer_template_location, $data);
				$this->flush();
				break;
			case 'fp':
				$family_container = $this->container->get_prop('family_container');
				$template = $family_container->get_templates('template');

				if ($offer_template = $family_container->get_templates('offer_template')) {
					$data['offer_template'] = function() use ($offer_template) { return '{{>'.$offer_template->get_header('template_location').'}}'; }; //1; //['_build_partial' => ]
					//$this->add_dynamic_partials(['dynamic_offer_partial' => $offer_template->get_header('template_location')]);
				}

				$data = $this->build_family_container_product_data($data);
				$this->render('page-family-container.mustache.html', $data);
				$this->flush();

				break;
		}
	}

	private function figure_product_prices($products_id, $data) {
		$product = new ck_product_listing($products_id);

		$prices = $product->get_price();

		$data['price_number'] = $prices['display'];
		$data['price'] = CK\text::monetize($prices['display']);

		if ($product->has_special()) {
			$data['regular_price_number'] = $prices['original'];
			$data['regular_price'] = CK\text::monetize($prices['original']);
		}
		elseif (!empty($prices['bundle_rollup'])) {
			$price_key = 'bundle_original';
			switch ($prices['reason']) {
				case 'dealer': $price_key = 'bundle_dealer'; break;
				case 'wholesale_high': $price_key = 'bundle_wholesale_high'; break;
				case 'wholesale_low': $price_key = 'bundle_wholesale_low'; break;
			}

			$data['regular_price_number'] = $prices[$price_key];
			$data['regular_price'] = CK\text::monetize($prices[$price_key]);
		}

		return $data;
	}

	private function build_family_container_product_data($data, $lite=FALSE) {
		$product = $this->container;
		$pl_template = $product->get_thin_template();
		$family_container = $product->get_prop('family_container');
		$fc_header = $family_container->get_header();

		if ($family_container->is('admin_only')) $data['admin-only'] = 1;

		$bcs = $family_container->get_breadcrumbs();

		foreach ($bcs as $category => $url) {
			$GLOBALS['breadcrumb']->add($category, $url);
		}

		$GLOBALS['breadcrumb']->add($pl_template['name'], $pl_template['url']);
		$data['breadcrumbs'] = $GLOBALS['breadcrumb']->trail();

		$data['base_url'] = $family_container->get_url();

		$data['model_number'] = $pl_template['model_num'];
		$data['page_title'] = $product->get_title();
		$data['title'] = $pl_template['name'];
		$data['products_id'] = $product->id();
		$data['attribute_safe'] = [
			'name' => $pl_template['name_attr'],
			'summary' => $pl_template['safe_short_description'],
			'breadcrumbs' => htmlspecialchars(strip_tags($GLOBALS['breadcrumb']->trail()))
		];
		$data['review_url'] = $family_container->get_url(); //$this->container->get_url_alternate($first_selected_product);

		$data['images'] = [];

		$images = $product->get_image();

		if (!empty($images['products_image_lrg'])) {
			$data['images'][] = [
				'image_large' => $images['products_image_lrg'],
				'image_thumb' => $images['products_image'],
			];

			for ($i=1; $i<=6; $i++) {
				if (empty($images['products_image_sm_'.$i]) || empty($images['products_image_xl_'.$i])) continue;
				$data['images'][] = [
					'image_large' => $images['products_image_xl_'.$i],
					'image_thumb' => $images['products_image_sm_'.$i],
				];
			}
		}

		$data['summary'] = !empty($pl_template['short_description'])?$pl_template['short_description']:$fc_header['summary'];

		$data['description'] = !empty($pl_template['description'])?$pl_template['description']:$fc_header['description'];
		$data['details'] = $fc_header['details'];

		if ($fc_header['show_lifetime_warranty']) $data['show_lifetime_warranty'] = 1;

		$data['first_selection'] = $product->id();

		$data = $this->figure_product_prices($product->id(), $data);

		if ($pl_template['display_available'] > 0) {
			$data['in_stock_number'] = $pl_template['display_available'];
			$data['in_stock_indicator'] = 'in Stock';

			if (ck_product_listing::calc_ship_date() == 'Today') $data['in_stock_ships'] = '- <span class="ships-date">Ships Today</span>';
			else $data['in_stock_ships'] = '- Ships next business day';

			if (!empty($pl_template['always_available?']) && $pl_template['lead_time'] < 30) $data['in_stock_additional'] = 'Additional quantities available - Ships in '.$pl_template['lead_time'].' business day(s)';
		}
		elseif (!empty($pl_template['always_available?'])) {
			$data['in_stock_number'] = '';
			$data['in_stock_indicator'] = 'Available';

			if ($pl_template['lead_time'] >= 6) $data['in_stock_ships'] = '- <span class="ships-date">Ships '.ck_product_listing::calc_ship_date($pl_template['lead_time']).'</span>';
			else $data['in_stock_ships'] = '- <span class="ships-date">Ships in '.$pl_template['lead_time'].'-'.($pl_template['lead_time']+1).' business days</span>';
		}
		else {
			$data['in_stock_number'] = '';
			$ipn = new ck_ipn2($pl_template['stock_id']);
			if ($ipn->get_inventory('adjusted_on_order') > 0) {
				$data['in_stock_indicator'] = $ipn->get_inventory('adjusted_on_order').' on order';
				$data['in_stock_ships'] = '- Please call to inquire about arrival date or additional quantity';
			}
			else {
				$data['in_stock_indicator'] = 'Currently out of stock. If you need this item, we can get it for you! Please contact us to check on options available to meet your needs.';
				$data['in_stock_ships'] = '';
			}
		}

		$data['schema'] = $product->get_schema();

		if (!$lite) {
			$bc = new breadcrumb;
			$bc->add('Home', HTTP_SERVER);
			foreach ($bcs as $category => $url) {
				$bc->add($category, $url);
			}
			$bc->add($family_container->get_header('name'), $family_container->get_url());

			$family_unit = $family_container->get_family_unit();

			$data['family_defaults'] = json_encode([
				'breadcrumbs' => $bc->trail(),
				'model_number' => $family_unit->get_header('generic_model_number'),
				'page_title' => $family_container->get_title(),
				'title' => $fc_header['name'],
				'summary' => $fc_header['summary'],
				'images' => [],
				'attribute_safe' => [
					'name' => htmlspecialchars($fc_header['name']),
					'summary' => htmlspecialchars(strip_tags($fc_header['summary'])),
					'breadcrumbs' => htmlspecialchars(strip_tags($bc->trail())),
				],
				'price' => 'Make a Selection',
				'price_number' => '',
				'regular_price' => '',
				'regular_price_number' => '',
				'in_stock_number' => '',
				'in_stock_indicator' => 'Make a selection to view stock quantities',
				'in_stock_ships' => '',
				'in_stock_additional' => '',
				'products_id' => '',
				'discontinued' => '',
				'quantity' => '',
				'creditkey_price' => '',
				'description' => $fc_header['description'],
				'schema' => '',
			]);

			$lookup_data = $family_container->get_lookup_data($product->id());

			$data['variant_count'] = count($lookup_data);

			$data['lookup_data'] = json_encode($lookup_data);

			if ($family_unit->has_grouped_variance()) {
				$v = array_values($lookup_data);

				// we make two assumptions: 1 - there are exactly 2 variances, 2 - the grouped on variance is first
				$v[0]['values'] = array_map(function($vdata) use ($v) {
					foreach ($v[1]['values'] as $val => $valdata) {
						foreach ($valdata['products_ids'] as $products_id) {
							if (in_array($products_id, $vdata['products_ids'])) {
								if (empty($vdata['subordinate'])) $vdata['subordinate'] = [];
								if (empty($vdata['selected'])) unset($valdata['selected']);
								$vdata['subordinate'][] = ['subvar' => $v[1], 'subval' => $valdata, 'products_id' => $products_id, 'pdata' => $this->figure_product_prices($products_id, [])];
							}
						}
					}

					return $vdata;
				}, array_values($v[0]['values']));

				$data['variants'] = $v[0];
			}
			else {
				$data['variants'] = array_map(function($data) {
					$data['values'] = array_values($data['values']);
					return $data;
				}, array_values($lookup_data));
			}
		}

		return $data;
	}

	private function build_family_container_header_data($data) {
		$header = $this->container->get_header();

		if ($this->container->is('admin_only')) $data['admin-only'] = 1;

		$this->container->preload_resources();

		foreach ($this->container->get_breadcrumbs() as $category => $url) {
			$GLOBALS['breadcrumb']->add($category, $url);
		}

		$GLOBALS['breadcrumb']->add($header['name'], $this->container->get_url());
		$data['breadcrumbs'] = $GLOBALS['breadcrumb']->trail();

		$data['base_url'] = $this->container->get_url();

		$first_selections = $this->container->get_first_selections($_GET['selections']);
		$first_selected_product = $this->container->get_first_selected_product($first_selections);

		$data['model_number'] = $first_selected_product['products_model'];
		$data['title'] = $header['name'];
		$data['products_id'] = $first_selected_product['products_id'];
		$data['attribute_safe'] = [
			'name' => htmlspecialchars($first_selected_product['products_name']),
			'summary' => htmlspecialchars(strip_tags($first_selected_product['products_head_desc_tag'])),
			'breadcrumbs' => htmlspecialchars(strip_tags($GLOBALS['breadcrumb']->trail()))
		];
		$data['review_url'] = $this->container->get_url_alternate($first_selected_product);

		$data['images'] = [];
		$images = $first_selected_product['images'];
		if (!empty($images['products_image_lrg'])) {
			$data['images'][] = [
				'image_large' => $images['products_image_lrg'],
				'image_thumb' => $images['products_image']
			];

			for ($i=1; $i<=6; $i++) {
				if (empty($images['products_image_sm_'.$i]) || empty($images['products_image_xl_'.$i])) continue;

				$data['images'][] = [
					'image_large' => $images['products_image_xl_'.$i],
					'image_thumb' => $images['products_image_sm_'.$i]
				];
			}
		}

		$data['summary'] = $header['summary'];

		$data['description'] = $header['description'];
		$data['details'] = $header['details'];

		if ($header['show_lifetime_warranty']) $data['show_lifetime_warranty'] = 1;

		$family_unit = $this->container->get_family_unit();

		if ($family_unit->has_grouped_variance()) {
			$data['price_number'] = $first_selected_product['prices']['display'];
			$data['price'] = CK\text::monetize($first_selected_product['prices']['display']);
			$data['first_selection'] = $first_selected_product['products_id'];
		}
		else {
			if ($first_selected_product['has_special']) {
				$data['regular_price_number'] = $first_selected_product['prices']['original'];
				$data['regular_price'] = CK\text::monetize($first_selected_product['prices']['original']);
			}
			elseif (!empty($first_selected_product['prices']['bundle_rollup'])) {
				if ($first_selected_product['prices']['reason'] == 'dealer') {
					$data['regular_price'] = CK\text::monetize($first_selected_product['prices']['bundle_dealer']);
					$data['regular_price_number'] = $first_selected_product['prices']['bundle_dealer'];
				}
				elseif ($first_selected_product['prices']['reason'] == 'wholesale_high') {
					$data['regular_price'] = CK\text::monetize($first_selected_product['prices']['bundle_wholesale_high']);
					$data['regular_price_number'] = $first_selected_product['prices']['bundle_wholesale_high'];
				}
				elseif ($first_selected_product['prices']['reason'] == 'wholesale_low') {
					$data['regular_price'] = CK\text::monetize($first_selected_product['prices']['bundle_wholesale_low']);
					$data['regular_price_number'] = $first_selected_product['prices']['bundle_wholesale_low'];
				}
				else {
					$data['regular_price'] = CK\text::monetize($first_selected_product['prices']['bundle_original']);
					$data['regular_price_number'] = $first_selected_product['prices']['bundle_original'];
				}
			}
			$data['price_number'] = $first_selected_product['prices']['display'];
			$data['price'] = CK\text::monetize($first_selected_product['prices']['display']);

			$data['first_selection'] = json_encode($first_selections);
		}

		return $data;
	}

	private function build_family_container_footer_data($data) {
		$family_unit = $this->container->get_family_unit();

		$listings = $family_unit->get_listing_details();

		$data['sibling_schemas'] = [];

		foreach ($listings as $lst) {
			$schema = $lst['schema'];
			$schema['url'] = $this->container->get_url_alternate($lst);

			$data['sibling_schemas'][] = ['schema' => $schema];
		}

		if ($family_unit->has_grouped_variance()) {
			$data['variant_lookup'] = json_encode($this->container->get_listing_lookup());
		}
		else {
			$data['variant_lookup'] = json_encode(array_values($this->container->get_listing_lookup()));
		}

		$data['long-info'] = 1;
		
		return $data;
	}

	private function build_family_container_data($data) {
		$family_unit = $this->container->get_family_unit();
		if ($family_unit->has_grouped_variance()) {
			$data['prime_options'] = $this->container->get_prime_options();
		}
		else {
			$data['variants'] = $this->container->get_variant_options();
		}

		return $data;
	}
}
?>
