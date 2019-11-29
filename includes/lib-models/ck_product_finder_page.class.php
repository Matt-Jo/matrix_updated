<?php
class ck_product_finder_page extends ck_page_controller {

	private $product_finder_key;
	private $category;
	public $major_category;
	public $specific_category;

	private $available_attributes = [];

	private $top_level;

	protected $templates = [
		'elements' => [
		'header' => ['partial-product_finder-top.mustache.html'],
		'footer' => ['partial-product_finder-bottom.mustache.html']

		]
	];

	private $browse;

	public function __construct($product_finder_key) {
		$this->product_finder_key = $product_finder_key;

		$this->category = ck_listing_category::get_category_by_product_finder_key($this->product_finder_key);

		$this->set_category_keys();

		parent::__construct();
	}

	private function qualify_templates() {
		foreach ($this->templates['elements'] as $section => &$templates) {
			array_walk($templates, [$this, 'build_qualified_template']);
		}

		if (!empty($this->page_details->templates)) {
			array_walk($this->page_details->templates, [$this, 'build_qualified_template']);
		}
	}

	private function build_qualified_template(&$template, $idx) {
		$path = pathinfo($template);
		if (empty($path['dirname']) || $path['dirname'] == '.') $template = $this->element_templates_folder.'/'.$template;
	}

	public function is_top_level() {
		return $this->top_level;
	}

	private function set_category_keys() {
		if (!empty($this->category->get_header('product_finder_title'))) $this->specific_category = strtolower($this->category->get_header('product_finder_title'));
		else $this->specific_category = strtolower($this->category->get_header('categories_name'));

		if (!empty($this->category->get_progenitor()->get_header('product_finder_title'))) $this->major_category = strtolower($this->category->get_progenitor()->get_header('product_finder_title'));
		else $this->major_category = strtolower($this->category->get_progenitor()->get_header('categories_name'));

		if ($this->category->has_ancestors()) $this->top_level = FALSE;
		else $this->top_level = TRUE;
	}

	public function control(ck_page_details $page_details) {
		$this->page_details = $page_details;

		// if refinement data is empty we want to at least initialize it
		if (empty($_GET['refinement_data'])) $_GET['refinement_data'] = [];
		
		if (empty($_GET['refinement_data']) && !empty($page_details->options_defaults)) {
			foreach ($page_details->options_defaults as $option) {
				$_GET['refinement_data'][$option] = $option;
			}
		}
		

		// set the cPath for nextopia to the current category
		$_GET['cPath'] = $this->category->get_header('categories_id'); //get_cpath();

		// if we've chosen a new category in our refinements, override with that one (and remove any refinements that have no chosen value)
		if (!empty($_GET['refinement_data'])) {
			foreach ($_GET['refinement_data'] as $key => $val) {
				list($key, $val) = explode(':', $key);
				if ($key == 'Category') $_GET['cPath'] = $val;

				if (empty($val)) unset($_GET['refinement_data'][$key.':'.$val]);
			}
		}

		if (!empty($page_details->build_query)) {
			$build_query = $page_details->build_query;
			$build_query($this->category);
		}

		// run the query against nextopia
		$this->browse = new navigate_nextopia('browse');

		// adjust the query - by default, we want to get attributes from the top level category
		$this->browse->adjust_query['_ALL'] = function(&$fields) { unset($fields['Category1']); unset($fields['Category2']); unset($fields['Subcategory']); };
		if (!empty($page_details->query_adjustments)) {
			foreach ($page_details->query_adjustments as $key => $func) {
				$this->browse->adjust_query[$key] = $func;
			}
		}

		// adjust the attribute results
		$this->browse->adjust_attribute['Category'] = 'Subcategory';
		if (!empty($page_details->attribute_adjustments)) {
			foreach ($page_details->attribute_adjustments as $key => $target) {
				$this->browse->adjust_attribute[$key] = $target;
			}
		}
		$this->browse->query();

		//$debug = [];

		foreach ($this->browse->refining->count_control as $option) {
			//if (!isset($debug[$option['aid']])) $debug[$option['aid']] = [];
			//$debug[$option['aid']][] = [$option['value'], $option['query']];
			if (isset($page_details->attributes[ucfirst($option['aid'])])) {
				if (!empty($page_details->attributes[ucfirst($option['aid'])])) {
					// if we've passed in a pre-set list of attribute values we want to deal with, use it
					// if we haven't set up this attribute before, set it up

					if (!isset($page_details->already_plural_attributes[ucfirst($option['aid'])])) $key = CK\fn::pluralize($option['aid']);
					else $key = $option['aid'];

					if (empty($this->available_attributes[$key])) $this->available_attributes[$key] = $page_details->attributes[ucfirst($option['aid'])];

					// if this is one of our preselected values, enable it
					if (!empty($this->available_attributes[$key][$option['value']])) $this->available_attributes[$key][$option['value']]['enabled?'] = 1;
				}
				else {
					// if we don't have a pre-set list of values, just grab them all into a list for this attribute
					// if we haven't set up this attribute before, set it up
					if (!isset($page_details->already_plural_attributes[ucfirst($option['aid'])])) $key = CK\fn::pluralize($option['aid']);
					else $key = $option['aid'];

					if (empty($this->available_attributes[$key])) $this->available_attributes[$key] = [];

					if (!in_array($option['value'], $this->available_attributes[$key])) $this->available_attributes[$key][] = $option['value'];
				}
			}
		}

		//var_dump($this->browse->refining->count_control);
		//var_dump($this->available_attributes);
		//var_dump($debug);
		//foreach ($this->browse->get_queries() as $qry) { echo $qry.'<br><br>'; }

		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
	}

	public function respond() {
		// return ajax results

		$json_response = ['errors' => []];

		/*$json_response['queries'] = $this->browse->get_queries();
		$json_response['count_control'] = $this->browse->refining->count_control;
		$json_response['available_attributes'] = $this->available_attributes;*/

		// we'll be using this for the nextopia interaction
		$sorts = [
			['sort_key' => 'bestsellers__0__1', 'sort_value' => 'Best Sellers'],
			['sort_key' => 'modelnumber__1__0', 'sort_value' => 'Model #'],
			['sort_key' => 'price__1__1', 'sort_value' => 'Price - Low'],
			['sort_key' => 'price__0__1', 'sort_value' => 'Price - High']
		];

		if (!empty($this->browse->results)) {
			// build the results
			$json_response['products'] = [];
			foreach ($this->browse->results as $products_id) {
				try {
					$product = new ck_product_listing($products_id);
					if (!$product->is_viewable()) continue;
					$json_response['products'][] = ['product' => $product->get_thin_template()];
				}
				catch (Exception $e) {
					$return_val['errs'][] = $e->getMessage();
				}
			}

			// build our pagination
			$json_response['pagination'] = ['results_start' => (($this->browse->paging->current_page - 1) * $this->browse->paging->page_size) + 1, 'results_end' => min($this->browse->paging->total_results, ((($this->browse->paging->current_page - 1) * $this->browse->paging->page_size) + $this->browse->paging->page_size)), 'results_cnt' => $this->browse->paging->total_results, 'page_sizes' => [], 'previous_page' => max($this->browse->paging->current_page - 1, 1), 'next_page' => min($this->browse->paging->current_page + 1, $this->browse->paging->total_pages), 'pages' => [], 'sorts' => []];

			// how do we display the results page?
			if (!empty($_SESSION['results-view']) && $_SESSION['results-view'] == 'grid') $json_response['pagination']['grid?'] = 1;
			else $json_response['pagination']['list?'] = 1;

			// how many results per page?
			$page_size_found = FALSE;
			foreach ($this->browse->paging_options->page_size as $size) {
				if ($size == $this->browse->paging->page_size) {
					$page_size_found = TRUE;
					$json_response['pagination']['page_sizes'][] = ['size' => $size, 'selected?' => 1];
				}
				else $json_response['pagination']['page_sizes'][] = ['size' => $size];
			}
			if (!$page_size_found) $json_response['pagination']['page_sizes'][] = ['size' => $this->browse->paging->page_size, 'selected?' => 1];

			$start_page = $end_page = 0;
			$start_ellipses = $end_ellipses = TRUE;

			// where are we in the paging?
			if ($this->browse->paging->current_page <= 5) {
				$start_ellipses = FALSE;
				$start_page = 2;
			}
			else $start_page = max(2, $this->browse->paging->current_page-2);

			if ($this->browse->paging->total_pages - $this->browse->paging->current_page <= 4) {
				$end_ellipses = FALSE;
				$end_page = $this->browse->paging->total_pages-1;
			}
			else $end_page = min($this->browse->paging->current_page+2, $this->browse->paging->total_pages-1);

			if ($this->browse->paging->current_page == 1) $json_response['pagination']['pages'][] = ['pagenum' => 1, 'current?' => 1];
			else $json_response['pagination']['pages'][] = ['pagenum' => 1];

			if ($start_ellipses) $json_response['pagination']['pages'][] = ['ellipses?' => 1];

			for ($i=$start_page; $i<=$end_page; $i++) {
				if ($this->browse->paging->current_page == $i) $json_response['pagination']['pages'][] = ['pagenum' => $i, 'current?' => 1];
				else $json_response['pagination']['pages'][] = ['pagenum' => $i];
			}

			if ($end_ellipses) $json_response['pagination']['pages'][] = ['ellipses?' => 1];

			// how many pages do we need to get through?
			if ($this->browse->paging->total_pages > 1) {
				if ($this->browse->paging->current_page == $this->browse->paging->total_pages) $json_response['pagination']['pages'][] = ['pagenum' => $this->browse->paging->total_pages, 'current?' => 1];
				else $json_response['pagination']['pages'][] = ['pagenum' => $this->browse->paging->total_pages];
			}

			// handle sorting
			foreach ($sorts as $sort) {
				if (!empty($this->browse->{$this->browse->context.'_sort_key'}) && $sort['sort_key'] == $this->browse->{$this->browse->context.'_sort_key'}) $sort['selected?'] = 1;

				$json_response['pagination']['sorts'][] = $sort;
			}
		}

		// build our selections
		$json_response['selections'] = implode(' - ', array_filter(array_map(function($v) {
			if (!empty($this->page_details->options_map[$v])) return $this->page_details->options_map[$v];
			else return NULL;
		}, array_values($_GET['refinement_data']))));

		// dereference our build_enabled_attributes closure so we can call it
		$build_enabled_attributes = $this->page_details->build_enabled_attributes;
		$json_response['enabled_attributes'] = $build_enabled_attributes($this->available_attributes);

		echo json_encode($json_response);
		exit();
	}

	public function display($script_details) {
		// build the full path to any templates that don't have their own path
		$this->qualify_templates();

		$content_map = $this->new_block();

		$tpl = self::get_tpl();

		$tpl->content($this->templates['elements']['header'], $content_map);

		// dereference our build_quick_order closure so we can call it
		$build_quick_order = $this->page_details->build_quick_order;
		$build_quick_order($this->category, $this->available_attributes, $content_map);

		/*ob_start();
		var_dump($this->browse->get_queries());
		var_dump($this->browse->refining->count_control);
		var_dump($this->available_attributes);
		var_dump($this->browse->base_refinement_options);

		$content_map->debug = ob_get_clean();*/

		$tpl->content($this->page_details->templates, $content_map);

		if ($this->is_top_level()) {
			$content_map->categories = ['cats' => []];
			// see if we should create the "shop by" section for this category
			// just reuse the same $cat variable from above, we're done with it
			$categories = $this->category->get_finder_subcategories()['shop_by'];
			foreach ($categories as $idx => $category) {
				$cat = [
					'link' => $category->get_url(),
					'name' => $category->get_header('categories_name'),
					'description' => $category->get_header('product_finder_description'),
					'img' => $category->get_header('product_finder_image'),
					'subcategories' => []
				];

				// we're on the top row
				if ($idx <= 2) $cat['toprow?'] = 1;
				// the first element of a new row
				if ($idx%3 == 0) $cat['newrow?'] = 1;
				// the last element of an old row
				if (($idx+1)%3 == 0) $cat['rowbreak?'] = 1;
				// the last element overall
				elseif ($idx + 1 == count($categories)) $cat['rowbreak?'] = 1;

				// if this category has subcategories that need to display as sub-links, build them out
				foreach ($category->get_finder_subcategories()['combined'] as $sbscategory) { // shop-by subcategory
					$subcat = ['link' => $sbscategory->get_url(), 'name' => $sbscategory->get_header('categories_name')];
					if ($idx%2 != 0) $subcat['rt'] = 'rt';
					$cat['subcategories'][] = $subcat;
				}

				$content_map->categories['cats'][] = $cat;
			}
		}

		if ($this->is_top_level() || !empty($this->page_details->show_additional)) {
			if (!$this->is_top_level()) $content_map->sbacat = ucwords($this->specific_category);

			$content_map->applications = ['apps' => []];
			$applications = $this->category->get_finder_subcategories()['applications'];
			foreach ($applications as $idx => $application) {
				$app = [
					'link' => $application->get_url(),
					'name' => $application->get_header('categories_name'),
					'img' => $application->get_header('product_finder_image')
				];
				// we're on the top row
				if ($idx <= 3) $app['toprow?'] = 1;
				// the first element of a new row
				if ($idx%4 == 0) $app['newrow?'] = 1;
				// the last element of an old row
				if (($idx+1)%4 == 0) $app['rowbreak?'] = 1;
				// the last element overall
				elseif ($idx + 1 == count($applications)) $app['rowbreak?'] = 1;

				foreach ($application->get_finder_subcategories()['combined'] as $apscategory) { // application subcategory
					if (!isset($app['subcategories'])) $app['subcategories'] = ['subcats' => []];
					$subcat = ['link' => $apscategory->get_url(), 'name' => $apscategory->get_header('categories_name')];
					$app['subcategories']['subcats'][] = $subcat;
				}

				$content_map->applications['apps'][] = $app;
			}
		}

		$content_map->cPath = $_GET['cPath'];
		$content_map->major = $this->major_category;
		$content_map->minor = $this->specific_category;

		$content_map->page = $script_details['filename'];

		$content_map->yotpo_page_id = $this->category->get_progenitor()->get_header('categories_id');
		$content_map->yotpo_page_url = 'https://www.cablesandkits.com/'.$this->product_finder_key;

		$content_map->finder['results_cnt'] = $this->browse->paging->total_results;

		$tpl->content($this->templates['elements']['footer'], $content_map);
	}

	/* this method will except all product data for the following pages: advanced_search_results, index_nested, and outlet */
	public static function build_product_results(Array $data) {
		foreach ($data as $product_id) {
			try {
				$product = new ck_product_listing($product_id);
				if (!$product->is_viewable()) continue;
				$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
				$content_map = new ck_content();
				$content_map->product = $product->get_thin_template();
				$cktpl->content(DIR_FS_CATALOG.'includes/templates/partial-product-result.mustache.html', $content_map);
			}
			catch (Exception $e) {
				// we do nothing.
			}
		}
	}
}
?>
