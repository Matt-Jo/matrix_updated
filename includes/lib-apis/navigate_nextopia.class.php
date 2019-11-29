<?php
class navigate_nextopia implements nav_service_interface {

	// valid values: search, browse
	private $context;
	// the instantiated navigation object
	private $nav;

	// contextual keys
	private $context_sort_by;
	private $context_sort_direction;
	private $context_sort_key;

	// service validation and access
	private $client_id = 'd3b7a28d3d8100f6f88d3ff055404c3e';
	private $search_urls = array(
		'http://ecommerce-search.nextopiasoftware.com/return-results.php',
		'http://ecommerce-search.nextopiasoftware.net/return-results.php',
		'http://ecommerce-search-dyn.nextopia.net/return-results.php'
	);

	// locally relevant properties
	// this is a map to translate the value received from nextopia to the customer-formatted attribute/facet name
	private $attribute_displays = array();
	// refinement control
	public $refinements = array();
	private $refinement_attributes;
	private $this_refinement_options = array(); // the refinement options returned by the current query. this'll help us figure out correct option quantities if only one option is still relevant.
	// the built queries to be sent to the service
	private $queries = array();
	// build the current search state to pass back in the hash
	private $page_key_state = array();
	/* for now, we're managing the refinements on the nextopia admin. This section is only relevant if we want to manage them on our end

	// since we're requesting the available refinements dynamically, we need to define the few up front that are sort of "base attributes" that are stored outside of the system.
	// by omitting Price here, we won't have to ignore it as an attribute later, it'll just never be provided, and we can manage it separately rather than use the provided options.
	// Also by omitting price, we won't have the quantities that belong in each price range.
	private $requested_refines = array(
		'Brand',
		'Category',
		'Condition',
		'Warranty'
	);
	*/

	public $adjust_query = array();
	public $adjust_attribute = array();

	// if we're not passed relevant info here, use these
	private $defaults = array(
		'pagesize' => 12,
		'sortby' => array('search' => 'relevancy', 'browse' => 'bestsellers__0__1')
	);

	// init
	public function __construct($context) {
		$this->context = $context;
		//require_once("$context.class.php");
		$this->nav = new $context();

		$this->context_sort_by = $context.'_sort_by';
		$this->context_sort_direction = $context.'_sort_direction';
		$this->context_sort_key = $context.'_sort_key';

		// this is just a nextopia formatted key -> customer friendly display map, we should only init it once from the db and store it for the query
		// we don't store it in session (we've defined the property above), though we could, but this makes sure we're always up to date with any new entries that happen mid session
		if (!$this->attribute_displays) { //!isset($this->attribute_displays)) {
			$attribute_descriptions = prepared_query::fetch('SELECT * FROM ck_attribute_keys', cardinality::SET);
			$this->attribute_displays = array('Currentoffers' => 'Current Offers'); // this is an attribute that won't be included in the query but should be corrected
			foreach ($attribute_descriptions as $attribute_description) {
				$this->attribute_displays[ucfirst(strtolower(preg_replace('/\s|_/', '', $attribute_description['attribute_key'])))] = $attribute_description['description'];
			}
		}

		foreach ($this->nav_fields as $field => $value) {
			$this->$field($value);
		}
	}

	public function get_queries() {
		return $this->queries;
	}

	// initialize the page state for javascript history navigation
	public function finish_js($display=FALSE) {
		ob_start(); ?>
		<script type="text/javascript">
			search_control.cache[''] = <?= $this->build_json(); ?>;
			search_control.context = '<?= get_class($this->nav); ?>';
			search_control.page = '<?= $_SERVER["SCRIPT_NAME"]; ?>';
		</script>
		<?php
		$js = ob_get_clean();
		if ($display) echo $js;
		else return $js;
	}

	// build & return ajax values
	public function build_json() {
		$return_val = array('hash' => $this->page_key, 'queries' => $this->queries, 'request' => $this->_query, 'listing' => array(), 'raw_listing' => [], 'pager' => array('pager' => '', 'showing' => '', 'params' => array()), 'refinements' => array('querystring' => '', 'selections' => array(), 'counts' => array(), 'attribute_order' => array(), 'show_options' => array(), 'price_low' => $this->price_low, 'price_high' => $this->price_high), 'results' => $this->results, 'errs' => array(), 'session' => $_SESSION);

		$buf = $GLOBALS['cktpl']->buffer;
		$GLOBALS['cktpl']->buffer = TRUE;

		foreach ($this->results as $product_id) {
			try {
				$product = new ck_product_listing($product_id);
				if (!$product->is_viewable()) continue;
				$content_map = new ck_content();
				$content_map->json = 1;
				$content_map->product = $product->get_thin_template();
				$content_map->cpath = $GLOBALS['cPath'];
				$return_val['raw_listing'][] = $content_map->product;
				$return_val['listing'][] = $GLOBALS['cktpl']->content(DIR_FS_CATALOG.'includes/templates/partial-product-result.mustache.html', $content_map);
			}
			catch (Exception $e) {
				$return_val['errs'][] = $e->getMessage();
				//MMD - not sure what to do here, we have to fail gracefully when we have removed a duplicate
				//product ID from the list to be displayed - so in some cases we will display 28 or 29 results instead of 30
			}
		}

		$GLOBALS['cktpl']->buffer = $buf;

		$return_val['pager'] = $this->nav->paginator(FALSE, TRUE);
		$return_val['refinements']['querystring'] = $this->nav->refinements(FALSE, TRUE);
		if (!empty($this->refinements)) {
			foreach ($this->refinements as $refinement) {
				list($attribute, $value) = explode(':', $refinement);
				if ($attribute == 'Pricebox' || in_array($attribute, $this->hide_refinements)) continue;
				$aid = strtolower(preg_replace('/\W/', '-', $attribute));
				$vid = strtolower(preg_replace('/\W/', '-', $value));
				$vid = "$aid-$vid";
				$attribute_display = isset($this->attribute_displays[$attribute])?$this->attribute_displays[$attribute]:$attribute;
				if (!isset($return_val['refinements']['selections'][$aid])) $return_val['refinements']['selections'][$aid] = array('attribute' => $attribute_display, 'attribute_key' => $attribute, 'aid' => $aid, 'options' => array());
				if (isset($this->refining->count_control["a$aid-v$vid"]) && isset($this->refining->count_control["a$aid-v$vid"]['new_display'])) $value = $this->refining->count_control["a$aid-v$vid"]['new_display'];
				$return_val['refinements']['selections'][$aid]['options'][] = array('vid' => $vid, 'value' => $value, 'query' => $refinement);
			}
		}
		foreach ($this->refining->attributes as $aid => $vals) {
			$return_val['refinements']['show_options'][$aid] = $vals->value_show;
		}
		foreach ($this->refining->attribute_order as $ridx => $refinement) {
			foreach ($this->refining->attributes as $aid => $details) {
				if (empty($details->attribute_key)) continue; // it's been too long, don't know why this would be unset but it's throwing notices
				if (($refinement && $details->attribute_key == $refinement) || (!$refinement && !in_array($details->attribute_key, $this->refining->attribute_order))) {
					if ($ridx >= ($this->refining_options->first_class_count + $this->refining_options->second_class_count)) $details->display_class = 'third';
					elseif ($ridx >= $this->refining_options->first_class_count) $details->display_class = 'second';
					//else $display_class = $details->display_class;
					$details->aid = $aid;
					// we don't set attribute_sort_details except in some commented out debugging code
					//$details->score = $this->refining->attribute_sort_details[$refinement];
					unset($details->value);
					unset($details->value_order);
					unset($details->value_show);
					$return_val['refinements']['attribute_order'][$ridx] = $details;
				}
			}
		}
		$return_val['refinements']['counts'] = $this->refining->count_control;

		return json_encode($return_val);
	}

	// parse any unique price range strings to pull out the range that we should be sending to the service provider
	public function figure_price_range($selection, $store_range=TRUE) {
		if (preg_match('/^\D*(\d+\D+\d+)\D*$/', $selection, $results)) {
			list($price_low, $price_high) = preg_split('/\D+/', $results[1]);
		}
		elseif (preg_match('/^.*less\D*(\d+)\D*/', strtolower($selection), $results)) {
			// we're doing a less than search
			$price_low = 0;
			$store_range?$this->price_low = 'min':NULL;
			$price_high = $results[1];
		}
		elseif (preg_match('/^.*greater\D*(\d+)\D*/', strtolower($selection), $results)) {
			// we're doing a greater than search
			$price_low = $results[1];
			$store_range?$this->price_high = 'max':NULL;
			$price_high = 999999;
		}
		if ($store_range && $this->price_low != 'min' && is_numeric($price_low) && (!is_numeric($this->price_low) || $price_low < $this->price_low)) $this->price_low = $price_low;
		if ($store_range && $this->price_high != 'max' && is_numeric($price_high) && (!is_numeric($this->price_high) || $price_high > $this->price_high)) $this->price_high = $price_high;

		$set_min = $this->price_low=='min'?0:$this->price_low;
		$set_max = $this->price_high=='max'?999999:$this->price_high;
		$range = min($set_min, $price_low)==$set_min?(max($set_max, $price_high)!=$set_max?'higher':'even'):'lower';
		return array(min($set_min, $price_low), max($price_high, $set_max), $range);
	}

	// run the actual query to get the desired results
	public function query() {
		//$log = [];
		//$log['url'] = $_SERVER['SCRIPT_URI'];
		//$log['qstring'] = json_encode($_GET);
		//$log['post'] = json_encode($_POST);
		//$log['session'] = json_encode($_SESSION);

		$dealer = FALSE;
		if (!empty($_SESSION['customer_id']) && is_numeric($_SESSION['customer_id'])) {
			$cust = prepared_query::fetch('SELECT customer_type FROM customers WHERE customers_id = ?', cardinality::ROW, $_SESSION['customer_id']);
			$dealer = $cust['customer_type']==1;
		}


		$fields = array(
			'client_id' => $this->client_id,
			'page' => 1,
			'requested_fields' => 'Productid',
			'xml' => 1,
			'searchtype' => 1,
			'force_sku_search' => 1,
			'ip' => $_SERVER['REMOTE_ADDR'],
		);
		
		if ($this->context == 'search') {
			$fields['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$fields['keywords'] = implode(' ', $this->terms);
			$this->page_key_state['keywords'] = implode(' ', $this->terms);

			$this->base_refinement_key = $this->page_key_state['keywords'];
		}
		
		if ($this->context == 'browse') {
			//die('I am in context : browse.');
			$fields['nav_search'] = 1;
			$this->page_key_state[$this->cat_key] = $this->category_id;
			$this->base_refinement_key = $this->page_key_state[$this->cat_key];
			$fields['refine'] = 'y';
			if ($this->Currentcategory) {
				//print_r($this->Currentcategory);
				foreach ($this->Currentcategory as $lev => $category) {
					$fields['Category'.(count($this->Currentcategory)-$lev)] = $category;
				}
				$fields['Category'] = $fields['Category1'];
				$fields['Subcategory'] = !empty($fields['Category2'])?$fields['Category2']:NULL;
			}
		
			//sri
			//in browse mode. This is where breadcrumb nav is  built....
	
		}
		
		//$extra_fields = $fields;
		$extra_queries = array();
		if ($this->refinements) {
			$fields['refine'] = 'y';
			$refinements = $this->refinements;
			$this->refinement_attributes = array();
			// nextopia requires the refinement options to be alphabetized to multi-select appropriately, so we sort them here.
			// on closer inspection, the delimiter we had been told to use was a range delimiter, not an or delimiter. They should be acceptable in any order, but there's no reason to remove the sort
			sort($refinements);
			foreach ($refinements as $refinement) {
				list($attribute, $value) = explode(':', $refinement);
				if ($attribute == 'Pricebox') {
					list($position, $price) = explode('|||', $value);
					if ($position == 1) $this->price_low = $price;
					elseif ($position == 2) $this->price_high = $price;
					continue;
				}
				$this->page_key_state['refinement_data['.$refinement.']'] = $refinement;
				if (!in_array($attribute, $this->refinement_attributes)) $this->refinement_attributes[] = $attribute;
				if (!isset($this->skip_extra_querying) || !in_array($attribute, $this->skip_extra_querying)) {
					if (!in_array($attribute, array_keys($extra_queries))) $extra_queries[$attribute] = $fields;
					foreach ($extra_queries as $attribute_qtys => $fields_qtys) {
						if ($attribute_qtys == $attribute) continue;
						$extra_queries[$attribute_qtys]['refine'] = 'y';
						if (isset($extra_queries[$attribute_qtys][$attribute])) {
							$extra_queries[$attribute_qtys][$attribute] .= '^'.$value;
						}
						else {
							$extra_queries[$attribute_qtys][$attribute] = $value;
						}
					}
				}
				if (isset($fields[$attribute])) {
					$fields[$attribute] .= '^'.$value;
				}
				else {
					$fields[$attribute] = $value;
				}
			}
		}
		else {
			$this->__get('base_refinement_options')[$this->base_refinement_key] = array();
		}
		$this->cached_refinements = $this->refinements;

		if (is_numeric($this->price_low) && is_numeric($this->price_high)) {
			if ($this->price_high < $this->price_low) {
				$pr = $this->price_high;
				$this->price_high = $this->price_low;
				$this->price_low = $pr;
			}
			$this->page_key_state['refinement_data[Pricebox:1]'] = $this->price_low;
			$this->page_key_state['refinement_data[Pricebox:2]'] = $this->price_high;
			$value = $this->price_low.'||'.$this->price_high;
			$fields['Price'] = $value;
			foreach ($extra_queries as $attribute_qtys => $fields_qtys) {
				if ($attribute_qtys == 'Price') continue;
				$extra_queries[$attribute_qtys]['refine'] = 'y';
				$extra_queries[$attribute_qtys]['Price'] = $value;
			}
		}
		elseif (is_numeric($this->price_low)) {
			$this->page_key_state['refinement_data[Pricebox:1]'] = $this->price_low;
			$value = $this->price_low.'||999999';
			$fields['Price'] = $value;
			foreach ($extra_queries as $attribute_qtys => $fields_qtys) {
				if ($attribute_qtys == 'Price') continue;
				$extra_queries[$attribute_qtys]['refine'] = 'y';
				$extra_queries[$attribute_qtys]['Price'] = $value;
			}
		}
		elseif (is_numeric($this->price_high)) {
			$this->page_key_state['refinement_data[Pricebox:2]'] = $this->price_high;
			$value = '0||'.$this->price_high;
			$fields['Price'] = $value;
			foreach ($extra_queries as $attribute_qtys => $fields_qtys) {
				if ($attribute_qtys == 'Price') continue;
				$extra_queries[$attribute_qtys]['refine'] = 'y';
				$extra_queries[$attribute_qtys]['Price'] = $value;
			}
		}

		// if we're doing a price refinement and the customer is confirmed as a dealer, override the price field with the dealer price
		if ($dealer && isset($fields['Price'])) {
			$fields['Pricedealer'] = $fields['Price'];
			unset($fields['Price']);
		}

		if (!empty($this->base_refinement_options[$this->base_refinement_key])) $fields['requested_fields'] .= ','.implode(',', $this->base_refinement_options[$this->base_refinement_key]);

		if ($this->page) {
			$fields['page'] = $this->page;
			$this->page_key_state['page'] = $this->page;
		}

		if (!empty($this->results_per_page)) {
			$fields['res_per_page'] = $this->results_per_page;
			$this->page_key_state['results_per_page'] = $this->results_per_page;
		}
		else {
			$fields['res_per_page'] = $this->defaults['pagesize'];
			$this->page_key_state['results_per_page'] = $this->defaults['pagesize'];
		}

		if ($this->{$this->context_sort_by} != 'debug') {
			if ($this->{$this->context_sort_by}) {
				if ($this->{$this->context_sort_by} != 'relevancy')
					$fields['sort_by_field'] = ucfirst(strtolower($this->{$this->context_sort_by})).':'.$this->{$this->context_sort_direction};
				$this->page_key_state['sort_by'] = $this->{$this->context_sort_key};
			}
			elseif ($this->context == 'browse' && $this->defaults['sortby'][$this->context]) {
				// default sort for browsing is random, so if we want a different default sort then we'll need to set it explicitly
				// we could probably do this regardless of context and the only effect would be to have an explicit relevancy sort for search rather than an implicit relevancy sort
				$this->{$this->context_sort_key} = $this->defaults['sortby'][$this->context];
				if (preg_match('/__/', $this->{$this->context_sort_key})) {
					$sort = explode('__', $this->{$this->context_sort_key});
					$this->{$this->context_sort_by} = $sort[0];
					$this->{$this->context_sort_direction} = $sort[1]?'ASC':'DESC';
				}
				else {
					$this->{$this->context_sort_by} = $args[0];
					$this->{$this->context_sort_direction} = 'ASC';
				}
				$fields['sort_by_field'] = ucfirst(strtolower($this->{$this->context_sort_by})).':'.$this->{$this->context_sort_direction};
				$this->page_key_state['sort_by'] = $this->{$this->context_sort_key};
			}
		}
		
		//die(var_dump($fields));

		//$qstring = http_build_query($fields);
		$this->page_key();

		//$log['queries'] = [];
		//$log['results'] = [];

		foreach ($this->search_urls as $url) {
			$req = new request(array(CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_TIMEOUT => 10, CURLINFO_HEADER_OUT => TRUE));
			$results = $req->get($url, $fields);
			/*$query = "$url?$qstring";

			$ch = curl_init($query);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$results = curl_exec($ch);
			curl_close($ch);*/

			//$log['queries'][] = $req->request();
			//$log['results'][] = $results;

			if (!(strpos($results, "<xml_feed_done>1</xml_feed_done>") == FALSE)) {
				$this->queries[] = $req->request();
				$results = new SimpleXMLElement($results);
				$this->parse_results($results);
				break;
			}
		}

		foreach ($extra_queries as $attribute => $fields) {
			$fields['res_per_page'] = 1;
			if (!empty($this->adjust_query[$attribute])) $this->adjust_query[$attribute]($fields);
			if (!empty($this->adjust_query['_ALL'])) $this->adjust_query['_ALL']($fields);
			$qstring = http_build_query($fields);
			
			foreach ($this->search_urls as $url) {
				$query = "$url?$qstring";

				$ch = curl_init($query);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				$results = curl_exec($ch);
				curl_close($ch);

				//$log['queries'][] = $query;
				//$log['results'][] = $results;

				if (!(strpos($results, "<xml_feed_done>1</xml_feed_done>") == FALSE)) {
					$this->queries[] = $query;
					$results = new SimpleXMLElement($results);
					$this->parse_refinements($attribute, $results);
					break;
				}
			}
		}

		//if (preg_match('/Googlebot/', $_SERVER['HTTP_USER_AGENT'])) prepared_query::execute('INSERT INTO temp_googlebot_req_log (url, qstring, post, session, queries, results) VALUES (:url, :qstring, :post, :session, :queries, :results)', [':url' => $log['url'], ':qstring' => $log['qstring'], ':post' => $log['post'], ':session' => $log['session'], ':queries' => json_encode($log['queries']), ':results' => json_encode($log['results'])]);
	}

	public function parse_results($results) {
		// paging
		$this->paging->current_page = (int) $results->pagination[0]->current_page;
		$this->paging->total_pages = (int) $results->pagination[0]->total_pages;
		$this->paging->page_size = $this->results_per_page?$this->results_per_page:$this->defaults['pagesize'];
		$this->paging->total_results = (int) $results->pagination[0]->total_products;
		if ($this->paging->current_page == 1) $this->paging->first_page = TRUE;
		if ($this->paging->current_page == $this->paging->total_pages) $this->paging->last_page = TRUE;

		// results
		if ($results->results[0]->result) {
			
			foreach ($results->results[0]->result as $product) {
				$this->results[] = (int) $product->Productid; //(int) $product->product_id;
			}
		}

		if ($results->refinables[0]->refinable) {
			$fill_refinements = empty($this->base_refinement_options[$this->base_refinement_key]); // booleanize base_refinement_options... if it's got data in it, we won't be filling it, but if it's empty, let's fill it.
			foreach ($results->refinables[0]->refinable as $attribute) {
				$a = trim((string) $attribute->name);
				$aid = strtolower(preg_replace('/\W/', '-', $a));

				if ($aid == 'subcategory' && isset($_GET['cPath']) && preg_match('/\d_\d/', $_GET['cPath'])) continue;

				if ($fill_refinements) {
					// the pass/assign by ref gymnastics we need to do to handle sub-arrays chokes on two levels of references and two levels in the array.
					// calling __get() explicitly solves that
					if (empty($this->base_refinement_options) || empty($this->__get('base_refinement_options')[$this->base_refinement_key])) $this->__get('base_refinement_options')[$this->base_refinement_key] = array();
					$this->__get('base_refinement_options')[$this->base_refinement_key][$aid] = $a;
				}
				$this->this_refinement_options[$aid] = $a;

				$attribute_display = isset($this->attribute_displays[$a])?$this->attribute_displays[$a]:$a;
				$this->refining->attributes[$aid] = (object) array('attribute' => $attribute_display, 'display_class' => 'first', 'attribute_key' => $a, 'values' => array(), 'value_order' => array(), 'value_show' => array());
				foreach ($attribute->values[0]->value as $value) {
					$v = trim((string) preg_replace('/"/', '&quot;', $value->name));
					if (isset($this->refining_options->filter_options[$a]) && in_array($v, $this->refining_options->filter_options[$a])) continue;
					$vid = strtolower(preg_replace('/\W/', '-', $v));
					$vid = "$aid-$vid";
					//$vquery = preg_replace('/&/', '%26', $v);
					$vcnt = trim((string) $value->num);

					if (!isset($this->refining->attribute_relevance[$a])) $this->refining->attribute_relevance[$a] = 0;
					$this->refining->attribute_relevance[$a] += $vcnt;
					if (!isset($this->refining->attribute_variance_factor[$a])) $this->refining->attribute_variance_factor[$a] = array();
					$this->refining->attribute_variance_factor[$a][] = $vcnt;
					if (!isset($this->refining->attribute_variance_count[$a])) $this->refining->attribute_variance_count[$a] = 0;
					$this->refining->attribute_variance_count[$a]++;

					$this->refining->attributes[$aid]->values[$vid] = array('query' => "$a:$v", 'value' => $v, 'count' => $vcnt);
					$this->refining->attributes[$aid]->value_order[$vid] = $v;
					// if this is an "unset" value, then de-prioritize it so it'll only show up if there aren't enough values to push it to the minimized group
					if ($v == 'Unset') $this->refining->attributes[$aid]->value_show[$vid] = 0;
					else $this->refining->attributes[$aid]->value_show[$vid] = $vcnt;
				}
				// sort the values by # of results
				arsort($this->refining->attributes[$aid]->value_show, SORT_NUMERIC);
				// only interested in the keys, and the top 5 at that
				$this->refining->attributes[$aid]->value_show = array_keys(array_slice($this->refining->attributes[$aid]->value_show, 0, 5, TRUE));
				// sort the relevant values by name
				if (in_array(strtolower($a), $this->refining_options->sort_options)) {
					natsort($this->refining->attributes[$aid]->value_order);
				}

				foreach ($this->refining->attributes[$aid]->value_order as $vid => $v) {
					$control_key = "a$aid-v$vid";
					$vcnt = $this->refining->attributes[$aid]->values[$vid]['count'];
					$query = $this->refining->attributes[$aid]->values[$vid]['query'];
					$this->refining->count_control[$control_key] = array('count_key' => $control_key, 'aid' => $aid, 'vid' => $vid, 'value' => $v, 'count' => $vcnt, 'query' => $query);
				}
			}

			if (!isset($this->refinement_attributes)) $this->refinement_attributes = array();

			foreach ($this->refining->attribute_relevance as $a => $relevance) {
				if (isset($this->refining_options->childof[$a])) {
					// if we've got a child relationship defined and the parent has not yet been selected, hide the child at the bottom of the list. It might still show up if there aren't enough attribute options to cause it to hide
					if (!in_array($this->refining_options->childof[$a], $this->refinement_attributes) && !@$this->{$this->refining_options->childof[$a]}) {
						$this->refining->attribute_sort[$a] = -10;
						continue;
					}
					// if the parent *has* been selected, put the relevance to the max
					else {
						if (in_array($a, array_keys($this->refining_options->relevancy))) {
							$this->refining->attribute_sort[$a] = ceil(45 * $this->refining_options->relevancy[$a]);
						}
						else {
							$this->refining->attribute_sort[$a] = 45;
						}
						continue;
					}
				}

				if (in_array($a, array_keys($this->refining_options->relevancy))) {
					$relevance_score = ceil(40 * $this->refining_options->relevancy[$a]);
				}
				else {
					// we get a global relevance score between 1-30, 30 being the most relevant
					$relevance_score = ceil(40 * CK\math::round_to_nearest($relevance / $this->paging->total_results, .2));
				}

				// we get a variance score between 1-30, 30 being the least variant (most concentrated into a single option), 1 being the most relevant
				/*$perfect_variance = ceil($this->paging->total_results / max($this->refining->attribute_variance_count[$a], 3)); // divide by a minimum of 3
				sort($this->refining->attribute_variance_factor[$a]);
				// pick a representative group from the middle
				if ($this->refining->attribute_variance_count[$a] == 2) {
					$select = 1;
					$variance = $this->refining->attribute_variance_factor[$a][0];
				}
				elseif ($this->refining->attribute_variance_count[$a] == 3) {
					$select = 1;
					$variance = $this->refining->attribute_variance_factor[$a][2];
				}
				else {
					$select = ceil($this->refining->attribute_variance_count[$a]/3);
					$variance = array_sum(array_slice($this->refining->attribute_variance_factor[$a], $select, $select))/$select;
				}

				// allow a little wiggle room in the variance factor if there's a wide array of options
				$perfect_variances = array(abs($variance - $perfect_variance) => $perfect_variance, abs($variance - ($perfect_variance + $select)) => ($perfect_variance + $select), abs($variance - ($perfect_variance - $select)) => ($perfect_variance - $select));
				$perfect_variance = $perfect_variances[min(abs($variance - $perfect_variance), abs($variance - ($perfect_variance + $select)), abs($variance - ($perfect_variance - $select)))];

				$variance_score = 31 - ceil(30 * min($variance, $perfect_variance) / max($variance, $perfect_variance));*/

				// let's try a weighted variance... how many values does it take to fill 1/2 of the total for this group?
				// first let's start from the bottom... how many small values are there?
				sort($this->refining->attribute_variance_factor[$a]);
				$running_count = 0;
				foreach ($this->refining->attribute_variance_factor[$a] as $factidx => $count) {
					$running_count += $count;
					if ($running_count > ($relevance / 2)) break;
				}
				// less is better
				$lowfactor = (($factidx + 1) / $this->refining->attribute_variance_count[$a]);

				// then let's go from the top, how many large values are there?
				rsort($this->refining->attribute_variance_factor[$a]);
				$running_count = 0;
				foreach ($this->refining->attribute_variance_factor[$a] as $factidx => $count) {
					$running_count += $count;
					if ($running_count > ($relevance / 2)) break;
				}
				// more is better
				$highfactor = ($this->refining->attribute_variance_count[$a] / ($factidx + 1));
				$factor = $lowfactor * $highfactor;

				$variance_score = ceil(30 * CK\math::round_to_nearest($factor / $this->refining->attribute_variance_count[$a], .33));

				$this->refining->attribute_sort[$a] = $relevance_score - $variance_score;

				// this is just for debugging purposes
				//$this->refining->attribute_sort_details[$a] = array('total_score' => $relevance_score - $variance_score, 'relevance_score' => $relevance_score, 'variance_score' => $variance_score, 'lowfactor' => $lowfactor, 'highfactor' => $highfactor, 'factor' => $factor, 'attribute count' => $this->refining->attribute_variance_count[$a]);
			}
			// higher score gets sorted first
			arsort($this->refining->attribute_sort, SORT_NUMERIC);

			foreach ($this->refining_options->refinement_order as $refinement) {
				foreach ($this->refining->attribute_sort as $a => $score) {
					if (($refinement && $a == $refinement) || (!$refinement && !in_array($a, $this->refining_options->refinement_order))) {
						$this->refining->attribute_order[] = $a;
					}
				}
			}
		}

		if (!isset($this->refinement_attributes)) $this->refinement_attributes = array();

		if (is_array($this->base_refinement_options[$this->base_refinement_key])) {
			foreach ($this->base_refinement_options[$this->base_refinement_key] as $aid => $refinement) {
				if (in_array($refinement, $this->refinement_attributes)) continue;
				foreach ($this->this_refinement_options as $caid => $curr_refinement) {
					if ($aid == $caid) continue 2;
				}
				$v = trim((string) @$results->results[0]->result[0]->{$refinement}[0]);
				if (!$v) continue;
				$vid = strtolower(preg_replace('/\W/', '-', $v));
				$vid = "$aid-$vid";
				$vcnt = $this->paging->total_results;
				$control_key = "a$aid-v$vid";
				$this->refining->count_control[$control_key] = array('count_key' => $control_key, 'aid' => $aid, 'vid' => $vid, 'value' => $v, 'count' => $vcnt, 'query' => "$refinement:$v");
				if (!isset($this->refining->attributes[$aid])) $this->refining->attributes[$aid] = (object) array();
				$this->refining->attributes[$aid]->value_show = array($vid);
			}
		}
	
		
		//sri
		// This is where we get the attributes for the side nav
		//die(var_dump($this->refining->attributes));
	}

	public function parse_refinements($attribute_qty, $results) {
		$price_counts = array('lower' => array(), 'higher' => array());
		if ($results->refinables[0]->refinable) {
			foreach ($results->refinables[0]->refinable as $attribute) {
				$a = trim((string) $attribute->name);
				if (empty($this->adjust_attribute[$attribute_qty]) && $attribute_qty != $a) continue;
				elseif (!empty($this->adjust_attribute[$attribute_qty]) && $this->adjust_attribute[$attribute_qty] != $a) continue;
				$aid = strtolower(preg_replace('/\W/', '-', $a));

				$this->this_refinement_options[$aid] = $a;

				foreach ($attribute->values[0]->value as $value) {
					$v = trim((string) $value->name);
					$vid = strtolower(preg_replace('/\W/', '-', $v));
					$vid = "$aid-$vid";
					$vcnt = trim((string) $value->num);

					$control_key = "a$aid-v$vid";

					$this->refining->count_control[$control_key] = array('count_key' => $control_key, 'aid' => $aid, 'vid' => $vid, 'value' => $v, 'count' => $vcnt, 'query' => "$a:$v");

					if ($a == 'Price') {
						$details = $this->figure_price_range($v, FALSE);
						$diff = $details[1]-$details[0];
						$price_counts[$details[2]][$diff] = $vcnt;

						$this->refining->count_control[$control_key]['diff'] = $diff;
						$this->refining->count_control[$control_key]['range'] = $details[2];
						if ($details[1] >= 999999) {
							$this->refining->count_control[$control_key]['new_display'] = 'Greater than $'.$details[0];
						}
						elseif ($details[0] == 0) {
							$this->refining->count_control[$control_key]['new_display'] = 'Less than $'.$details[1];
						}
						else {
							$this->refining->count_control[$control_key]['new_display'] = '$'.$details[0].' - $'.$details[1];
						}
					}
				}
				if ($a == 'Price') {
					foreach ($this->refining->count_control as $control_key => $details) {
						foreach ($price_counts[$details['range']] as $count_diff => $cnt) {
							if ($count_diff < $details['diff']) $this->refining->count_control[$control_key]['count'] += $cnt;
						}
					}
				}
			}
		}
		//die('i am in parse_refinements');
	}

	public function page_key() {
		$pstate = array();
		foreach ($this->page_key_state as $key => $val) {
			// we need to pre-process ampersands to account for Firefox, which is a little too helpful with copied & pasted URLs
			// Firefox pre-decodes any encoded values in the URL, so unless we pre-encode, it will decode it to a basic ampersand which obviously already has a meaning in the URL
			// we may need to do this with equals signs as well, we'll see
			$pstate[preg_replace('/&/', '%26', $key)] = preg_replace('/&/', '%26', $val);
		}
		$this->page_key = http_build_query($pstate);
		//die('i am in pagekey');

	}

	public function __call($method, $args) {
		switch ($method) {
			case 'refinements':
			case 'paginator':
				// we're passing this along to the nav object
				if (isset($args[1])) {
					return $this->nav->{$method}($args[0], $args[1]);
				}
				elseif (isset($args[0])) {
					return $this->nav->{$method}($args[0]);
				}
				else {
					return $this->nav->{$method}();
				}
				break;
			case 'refinement_data':
				if ($this->refinements) return; // we've already set the refinements, through the remove_refinements interface
				$refs = array();
				if (is_array($args[0])) {
					foreach ($args[0] as $key => $val) {
						if ($key !== 0 && $val !== '') {
							if ($key != $val) $val = $key.'|||'.$val; // if the key doesn't match the value, it's because the value is dynamically entered by the customer, like in a text field
							$refs[] = $val;
						}
					}
				}
				$this->refinements = $refs;
				break;
			case 'remove_refinement':
				$this->refinements = $this->cached_refinements;
				if (is_array($args[0])) {
					foreach ($args[0] as $key => $val) {
						if (in_array($val, $this->refinements)) unset($this->refinements[array_search($val, $this->refinements)]);
					}
				}
				break;
			case 'use_cached_refinements':
				$this->refinements = $this->cached_refinements;
				break;
			case 'page':
				if (!is_numeric($args[0])) break;
				$this->page = $args[0];
				break;
			case 'results_per_page':
				if (!is_numeric($args[0])) break;
				// results per page is stored in session
				if (($this->results_per_page && $this->results_per_page != $args[0]) || (!$this->results_per_page && $this->defaults['pagesize'] != $args[0])) {
					$this->results_per_page = $args[0];
				}
				break;
			case 'sort_by':
				// sort by is stored in session
				if (($this->{$this->context_sort_by} && $this->{$this->context_sort_by} != $args[0]) || (!$this->{$this->context_sort_by} && $this->defaults['sortby'][$this->context] != $args[0])) {
					$this->{$this->context_sort_key} = $args[0];
					if (preg_match('/__/', $this->{$this->context_sort_key})) {
						$sort = explode('__', $this->{$this->context_sort_key});
						$this->{$this->context_sort_by} = $sort[0];
						$this->{$this->context_sort_direction} = $sort[1]?'ASC':'DESC';
					}
					else {
						$this->{$this->context_sort_by} = $args[0];
						$this->{$this->context_sort_direction} = 'ASC';
					}
				}
				else {
					$this->{$this->context_sort_key} = $this->defaults['sortby'][$this->context];
				}
				break;
			default:
				return call_user_func_array([$this->nav, $method], $args);
				break;
		}
	}

	public function &__get($key) {
		// __get is returned by reference to allow directly accessing sub-arrays
		if (isset($this->nav->$key)) $val =& $this->nav->$key;
		else $val = NULL;
		return $val;
	}

	public function __set($key, $val) {
		return $this->nav->$key = $val;
	}

	public function __isset($key) {
		return isset($this->nav->$key);
	}

	public function __unset($key) {
		unset($this->nav->$key);
	}
}
?>
