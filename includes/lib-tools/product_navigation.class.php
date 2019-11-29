<?php
abstract class product_navigation {

	// this is the session "namespace" for navigation variables
	const navigation_session_key = 'navigation_state';

	public $context = '';

	// _query is the actual details of the query to be run
	public $_query = array();
	// page_key represents the final state of the query that is passed back to the client (e.g. if the last action was to remove a refinement, the final state is all of the currently in scope refinements)
	public $page_key = '';
	// results is the final product list resulting from the navigation state
	public $results = array();
	// if we're passed back information from the service provider that will need to be included in future queries, that info goes into extra_params
	public $extra_params = array();
	// nav_fields are the relevant fields being passed through that impact navigation (paging, refinements, etc). The child context (search or browse) will determine what's relevant
	public $nav_fields = array();


	// for the base navigation class, paging is 1 indexed to agree with what the customer sees
	// if the search provider is 0 indexed, that'll have to be managed in the child class
	public $paging = array(
		'first_page' => FALSE,
		'last_page' => FALSE,
		'current_page' => 0,
		'total_pages' => 0,
		'page_size' => 0,
		'total_results' => 0
	);
	public $paging_options = array(
		'firstrun' => TRUE,
		'page_size' => array(12, 20, 40)
	);

	public $refining = array(
		'attributes' => array(),
		'attribute_relevance' => array(),
		'attribute_variance_factor' => array(),
		'attribute_variance_count' => array(),
		'attribute_sort' => array(),
		'attribute_order' => array(),
		'count_control' => array()
	);
	public $refining_options = array(
		'sort_options' => array('length', 'portquantity', 'weightcapacity'),
		'childof' => array('Subcategory' => 'Category'),
		'filter_options' => array('Currentoffers' => array('NONE')),
		'relevancy' => array('Category' => 1.6, 'Currentoffers' => 2.0, 'Subcategory' => 3.0, 'Transceivertype' => 1.6),
		'refinement_order' => array(FALSE), //'Brand', 'Category', FALSE, 'Price'), // FALSE represents refinements that aren't explicitly ordered
		'first_class_count' => 5,
		'second_class_count' => 4
	);

	public $sorting_options = array(
		'search' => array(
			'relevancy' => 'Relevancy',
			'bestsellers__0__1' => 'Best Sellers',
			'price__1__1' => 'Price - Low',
			'price__0__1' => 'Price - High'
		),
		'browse' => array(
			'bestsellers__0__1' => 'Best Sellers',
			'price__1__1' => 'Price - Low',
			'price__0__1' => 'Price - High',
			//'debug__0__0' => 'None'
		)
	);


	// manage issues
	public $errs = array();
	public $warnings = array();


	// initialize properties that we want to only keep during this instance. If we don't initialize it, when we set it it will be saved in session
	public $page;
	public $price_low;
	public $price_high;


	// init
	public function __construct() {
		$this->paging = (object) $this->paging;
		$this->paging_options = (object) $this->paging_options;
		$this->refining = (object) $this->refining;
		$this->refining_options = (object) $this->refining_options;

		if ($this instanceof search) $this->context = 'search';
		elseif ($this instanceof browse) $this->context = 'browse';

		// if we're passed a key referring to a previous query, attempt to look up that query. Otherwise, use the info passed from the browser.
		$this->select_query();
	}

	// manage the query state.
	public function select_query() {
		$page_key = isset($_GET['page_key'])&&$_GET['page_key']?$_GET['page_key']:NULL;
		// if we're passed a valid page key that is currently in session, use it
		if (!empty($page_key)) {
			$this->page_key = $page_key;
			parse_str($page_key, $qry);
			$this->_query = $qry;
		}
		else {
			$this->_query = $_GET;
			$qry = http_build_query($this->_query);
			$this->page_key = $qry;
		}
	}

	// any supplementary/support fields will be processed here
	// we may process refinements here if we can do it in a way that seems global, or
	// we may do it in the specific search API child class if it looks like it's more specific to the API
	public function __call($method, $args) {
		switch ($method) {
			default:
				$this->warnings[] = "The $method method is not handled.";
				break;
		}
	}

	// any variables set or accessed outside of the defined structures are saved to session
	public function &__get($key) {
		// __get is returned by reference to allow directly accessing sub-arrays
		$val = NULL; // we're returning by ref, we need this to be able to return NULL
		if (!isset($_SESSION[self::navigation_session_key])) return $val;
		if (!isset($_SESSION[self::navigation_session_key][$key])) return $val;
		return $_SESSION[self::navigation_session_key][$key];
	}

	public function __set($key, $val) {
		if (!isset($_SESSION[self::navigation_session_key])) $_SESSION[self::navigation_session_key] = array();
		return $_SESSION[self::navigation_session_key][$key] = $val;
	}

	public function __isset($key) {
		// we use @ error suppression just in case the search session key doesn't exist and PHP doesn't like it for whatever reason
		return @isset($_SESSION[self::navigation_session_key][$key]);
	}

	public function __unset($key) {
		unset($_SESSION[self::navigation_session_key][$key]);
	}

	public function refinements($display=FALSE, $qstring_only=FALSE) {
		$imgdir = '/templates/Pixame_v1/images';
		$querystring = array();
		if ($this->context == 'search') {
			$querystring[] = search::search_query_key.'='.implode(' ', $this->terms);
		}
		elseif ($this->context == 'browse') {
			$querystring[] = $this->cat_key.'='.$this->category_id;
		}
		foreach ($this->extra_params as $param => $value) {
			$querystring[] = "$param=$value";
		}
		$clearstring = '';
		foreach ($_GET as $key => $val) {
			if (!preg_match('/^gae-/', $key)) continue;
			$querystring[] = $clearstring = "$key=$val";
		}
		$querystring = implode('&amp;', $querystring);
		if ($qstring_only) return $querystring;

		$context_uri = parse_url($_SERVER['REQUEST_URI']);
		$seo_link = !empty($context_uri['query'])?$context_uri['path'].'?'.http_build_query($_GET):$context_uri['path'].'?'.$querystring;
		$cat_qry = !empty($_GET[$this->cat_key])?preg_replace("/$this->cat_key=".$_GET[$this->cat_key]."/", '', !empty($context_uri['query'])?$context_uri['query']:''):(!empty($context_uri['query'])?$context_uri['query']:'');
		$cat_link = !empty($_GET[$this->cat_key])?preg_replace("/$this->cat_key=".$_GET[$this->cat_key]."/", '', $_SERVER['REQUEST_URI']):$_SERVER['REQUEST_URI'].'?'.$cat_qry;

		if (!$display) ob_start();
		?>
		<script type="text/javascript" src="/includes/javascript/custom-form-elements.js"></script>
		<script type="text/javascript" src="/includes/javascript/animatedcollapse.js"></script>
		<style>
			.leftbrowse { font-family:Helvetica, Arial, sans-serif; }
			.define-pricing { color: #DD003C; font-size: 13px; font-weight: bold; clear:both; padding-top:4px; }
			.define-pricing input.price { background-color: transparent; background-image: url("<?= $imgdir; ?>/pjgotobox.gif"); background-repeat: no-repeat; border: medium none; color: #ADACB1; font-family: Helvetica,Arial,sans-serif; font-size: 12px; height: 19px; padding: 0; /*position: relative; top: -6px;*/ width: 32px; text-align:center; outline:none; }
			.define-pricing input.pricego { position:relative; top:5px; }
			.defined { clear:both; background-image:url("<?= $imgdir; ?>/lb3l.gif"); background-repeat:repeat-x; background-position:center bottom; margin: 0px 0px 8px 0px; padding: 5px 0px 15px 0px; width:165px; }
			.defined .selection-title { background-color:#e3dddd; margin-top:5px; padding:3px 5px 0px 5px; float:none; color: #DD003C; font-size: 13px; text-transform:uppercase; font-weight: bold; }
			.defined .selection-title a { position:relative; top:4px; text-transform:none; }
			.defined .selection-options { color: #8B8B8B; font-size: 11px; font-weight: bold; margin: 8px 0 0; }
			.defined .selection-options .clear-attribute { color: #6B6A6A; font-size: 9px; }
			.defined .selection-options .clear-attribute:hover { color: #3c3c3c; }
			.defined .selection-option { text-align:left; }
			.defined .selection-option a.remove { color:#DD003C; }
			.defined .selection-additions { color: #8B8B8B; float: left; font-size: 10px; font-weight: bold; }
			.defined .selection-more { clear:both; text-align:center; }
			.defined .selection-more a { color:#84abb7; font-size:11px; font-weight:bold; }
			.defined .selection-more a:hover { color:#4d646b; }
			.define { color: #DD003C; float: left; font-size: 15px; font-weight: bold; clear:both; }
			.lbl1 { color: #6B6A6A; float: right; font-size: 9px; margin: 3px 0 0; }
			.lbl1 a { color: #6B6A6A; }
			.lbl1 a:hover { color: #3c3c3c; }
			.lbl { color: #6B6A6A; float: right; font-size: 9px; margin: -4px 0 0; }
			.lbl a { color: #6B6A6A; }
			.lbl a:hover { color: #3c3c3c; }
			.lbsection { background-image:url("<?= $imgdir; ?>/lb2l.gif"); background-repeat:repeat-x; background-position:center bottom; float: left; margin: 0 0 16px; padding: 0 0 16px; width: 154px; }
			.lbsection.third-class { display:none; }
			.lbsection.second-class .lbchecks { display:none; }
			.lbtitle a, .lbtitle a:hover { color: #DD003C; float: left; font-size: 15px; font-weight: bold; text-decoration:none; }
			.lbdd { margin: 0 0 0 3px; position: relative; top: 3px; }
			.lbchecks { color: #8B8B8B; float: left; font-size: 10px; font-weight: bold; margin: 8px 0 0; width: 154px; }
			.checkbox { width: 15px; height: 21px; padding: 0 5px 0 0; background: url("<?= $imgdir; ?>/lbcheck.gif") no-repeat; display: block; clear: left; float: left; }
			.lblabel { float: left; line-height: 1.4; margin: 0; padding: 0 0 6px 1px; position: relative; top: 5px; width: 125px; cursor:pointer; }
			.lblabel span { font-weight:normal; color:#aaa9aa; font-size:11px; }
			.selection-direct { color:inherit; }
			.lbviewmore { clear: left; float: left; margin: 4px 0 0 20px; }
			.lbviewmore a { color:#84abb7; font-size:11px; font-weight:bold; }
			.lbviewmore a:hover { color:#4d646b; }
		</style>
		<form action="<?= $_SERVER['REQUEST_URI']; ?>" class="pagerefine_form refresh_navigation" method="get">
			<?php if ($this->context == 'search') { ?>
			<input type="hidden" name="<?php echo search::search_query_key; ?>" value="<?php echo implode(' ', $this->terms); ?>"/>
			<?php }
			elseif ($this->context == 'browse') { ?>
			<input type="hidden" name="<?php echo $this->cat_key; ?>" value="<?php echo $this->category_id; ?>"/>
			<?php } ?>
			<?php foreach ($this->extra_params as $param => $value) { ?>
			<input type="hidden" name="<?= $param; ?>" value="<?= $value; ?>"/>
			<?php } ?>
			<?php foreach ($_GET as $key => $val) {
				if (!preg_match('/^gae-/', $key)) continue; ?>
			<input type="hidden" name="<?= $key; ?>" value="<?= $val; ?>"/>
			<?php } ?>
			<input type="hidden" name="refinement_data[]" value=""/>
			<div class="leftbrowse aholder aall" style="background-color:#f9f7f8; text-align: left;">
				<div class="defineby">
					<div class="define">Narrow your Results</div>
					<?php if ($this->context == 'search') { ?>
					<div class="lbl1"><a class="clear_refinements refresh_navigation" href="<?= $_SERVER['REQUEST_URI']; ?>?<?php echo search::search_query_key; ?>=<?php echo implode('+', $this->terms); ?>&amp;<?= $clearstring; ?>" rel="nofollow">Clear All Sections</a></div>
					<?php }
					elseif ($this->context == 'browse') { ?>
					<div class="lbl1"><a class="clear_refinements refresh_navigation" href="<?= $_SERVER['REQUEST_URI']; ?>?<?php echo $this->cat_key; ?>=<?php echo $this->category_id; ?>&amp;<?= $clearstring; ?>" rel="nofollow">Clear All Sections</a></div>
					<?php } ?>
				</div>
				<div class="define-pricing">
					Price:
					<input type="text" class="price low" name="refinement_data[Pricebox:1]" placeholder="$" value="<?php echo $this->price_low; ?>"/><span> - </span><input type="text" class="price high" name="refinement_data[Pricebox:2]" placeholder="$" value="<?php echo $this->price_high; ?>"/><input class="pricego" type="image" name="gopage" value="&#187;" src="<?= $imgdir; ?>/pjgoto.gif"/>
				</div>
				<div class="defined">
				</div>

				<?php foreach ($this->refining->attribute_order as $ridx => $refinement) {

					foreach ($this->refining->attributes as $aid => $details) {
						if ((!empty($refinement) && @$details->attribute_key == $refinement) || (empty($refinement) && !in_array(@$details->attribute_key, $this->refining->attribute_order))) {
							if ($ridx >= ($this->refining_options->first_class_count + $this->refining_options->second_class_count)) $display_class = 'third';
							elseif ($ridx >= $this->refining_options->first_class_count) $display_class = 'second';
							else $display_class = $details->display_class; ?>
				<div class="lbsection aholder a<?= $aid; ?> <?= $display_class; ?>-class" id="a<?= $aid; ?>-holder">
					<div class="lbtitle" data-title="<?php echo ucwords(strtolower($details->attribute)); ?>"><a class="toggle_attribute a<?= $aid; ?>" href="#" rel="nofollow"><?php echo ucwords(strtolower($details->attribute)); ?></a></div>
					<div class="lbl"><a class="toggle_attribute a<?= $aid; ?>" href="#" rel="nofollow"><img src="<?= $imgdir; ?>/lbdd.gif" border="0" class="lbdd"/></a></div>
					<div class="lbchecks" id="a<?= $aid; ?>">
						<?php $hidden_values = FALSE;
						foreach ($details->value_order as $vid => $vname) {
							$value = $details->values[$vid];
							$hidestyle = '';
							$hideclass = '';
							if (!in_array($vid, $details->value_show) && ($this->context == 'search' || $_SERVER['SCRIPT_NAME'] == '/outlet.php' || $refinement != 'Subcategory')) { $hidden_values = TRUE; $hidestyle = 'display:none;'; $hideclass = ' hide-onmin'; }
							if ($refinement == 'Subcategory') {
								// here we will make sure that the subcategory isn't INactive
								$subcategory_inactive = prepared_query::fetch('SELECT c.inactive FROM categories c LEFT JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE cd.categories_name LIKE ?', cardinality::SINGLE, [$vname]);
								if ($subcategory_inactive == 1) continue;
							} ?>
						<div class="lbsection-option<?php echo "$hideclass"; ?> ctx-<?php echo $this->context; ?>" id="v<?= $vid; ?>" style="<?= $hidestyle; ?>clear:both;">
							<?php if ($this->context == 'browse' && $_SERVER['SCRIPT_NAME'] != '/outlet.php' && $refinement == 'Subcategory') {
								$categories_id = prepared_query::fetch('SELECT c.categories_id FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE cd.categories_name LIKE :categories_name AND '.($this->cat_key?'c.parent_id = :parent_id':':parent_id != 999999999'), cardinality::SINGLE, [':categories_name' => $value['value'], ':parent_id' => $_GET[$this->cat_key]]);
								$ckcat = new ck_listing_category($categories_id); ?>
							<span class="lblabel v<?= $vid; ?>"><a href="<?= $ckcat->get_url(); ?><?php $cat_qry?"?$cat_qry":''; ?>" class="selection-direct go-anyway"><?= $value['value']; ?></a> <span class="count-control a<?= $aid; ?>-v<?= $vid; ?>-count">(<?= $value['count']; ?>)</span></span>
							<?php }
							else { ?>
							<input type="checkbox" class="styled" name="refinement_data[<?= $value['query']; ?>]" data-vid="v<?= $vid; ?>" value="<?= $value['query']; ?>"/> <span class="lblabel v<?= $vid; ?>"><a href="<?= $seo_link; ?>&amp;refinement_data[<?= $value['query']; ?>]=<?= $value['query']; ?>" class="selection-direct" rel="nofollow"><?= $value['value']; ?></a> <span class="count-control a<?= $aid; ?>-v<?= $vid; ?>-count">(<?= $value['count']; ?>)</span></span>
							<?php } ?>
						</div>
						<?php } ?>
						<?php $morestyle = '';
						if (empty($hidden_values)) { $morestyle = 'display:none;'; } ?>
						<div class="lbviewmore ctx-<?php echo $this->context; ?>" style="<?= $morestyle; ?>"><a class="togglerefinements a<?= $aid; ?>" href="#" rel="nofollow">View <?php echo $refinement=='Subcategory'?'Less':'More'; ?></a></div>
					</div>
				</div>
						<?php }
					}
				} ?>
				<script type="text/javascript">
				<?php foreach ($this->refining->attributes as $aid => $details) { ?>
				animatedcollapse.addDiv('a<?= $aid; ?>', 'fade=0,speed=400');
				animatedcollapse.addDiv('a<?= $aid; ?>-holder', 'fade=0,speed=400');
				<?php foreach ($details->value_order as $vid => $vname) { ?>
				animatedcollapse.addDiv('v<?= $vid; ?>', 'fade=0,speed=200');
				<?php }
				} ?>
				</script>
			</div>
		</form>
		<script type="text/javascript">
			animatedcollapse.init();
			Custom.init();
			jQuery('a.togglerefinements').click(function() {
				var valuelist = this.className.split(/\s+/);
				var action = jQuery(this).html();
				for (var i=0; i<valuelist.length; i++) {
					if (valuelist[i] == 'togglerefinements') continue;
					jQuery('#'+valuelist[i]+' .lbsection-option.hide-onmin').each(function() {
						if (jQuery(this).find('input').is(':checked')) return;
						if (jQuery(this).hasClass('zero-quant')) return;
						if (action == 'View More') animatedcollapse.show(jQuery(this).attr('id'));
						else if (action == 'View Less') animatedcollapse.hide(jQuery(this).attr('id'));
					});
					//jQuery('#'+valuelist[i]).css('background-color', '#ddffee').animate({backgroundColor: jQuery.Color({saturation:0})}, 1200);
				}
				if (action == 'View More') {
					jQuery(this).html('View Less');
				}
				else if (action == 'View Less') {
					jQuery(this).html('View More');
				}
				return false;
			});
			jQuery('a.toggle_attribute').live('click', function() {
				var attributelist = this.className.split(/\s+/);
				for (var i=0; i<attributelist.length; i++) {
					if (attributelist[i] == 'toggle_attribute') continue;
					if (jQuery('.aholder.'+attributelist[i]+' input:checked').length) {
						if (jQuery('#'+attributelist[i]).is(':hidden')) {
							jQuery('.aholder.'+attributelist[i]+' .lbtitle').html(jQuery('.aholder.'+attributelist[i]+' .lbtitle').attr('data-title'));
						}
						else {
							jQuery('.aholder.'+attributelist[i]+' .lbtitle').html(jQuery('.aholder.'+attributelist[i]+' .lbtitle').attr('data-title')+'+');
						}
					}
					animatedcollapse.toggle(attributelist[i]);
				}
				if (jQuery(this).html() == '[MORE]') {
					jQuery(this).html('[LESS]');
				}
				else if (jQuery(this).html() == '[LESS]') {
					jQuery(this).html('[MORE]');
				}
				return false;
			});
			jQuery('span.lblabel').live('click', function() {
				var valuelist = this.className.split(/\s+/);
				for (var i=0; i<valuelist.length; i++) {
					if (valuelist[i] == 'lblabel') continue;
					jQuery(this).siblings('input[data-vid='+valuelist[i]+']').click();
				}
				Custom.clear();
			});
			jQuery('a.selection-direct:not(.go-anyway)').live('click', function(event) {
				event.preventDefault();
				jQuery(this).closest('span.lblabel').click();
				return false;
			});
		</script>
		<?php
		if (!$display) $refinements = ob_get_clean();
		return $display?TRUE:$refinements;
	}

	public function paging_qstring() {
		$querystring = [];
		if ($this->context == 'search') $querystring[] = search::search_query_key.'='.implode(' ', $this->terms);
		elseif ($this->context == 'browse') $querystring[] = $this->cat_key.'='.$this->category_id;

		foreach ($this->extra_params as $param => $value) {
			$querystring[] = "$param=$value";
		}

		foreach ($_GET as $key => $val) {
			if (!preg_match('/^gae-/', $key)) continue;
			$querystring[] = "$key=$val";
		}

		$querystring[] = 'use_cached_refinements=1';
		$querystring = implode('&amp;', $querystring);

		return $querystring;
	}

	public function paginator($display=FALSE, $pager_only=FALSE) {
		// var_dump($_SERVER);
		$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$imgdir = '/templates/Pixame_v1/images';
		$querystring = $this->paging_qstring();
		if (!$display) ob_start();
		if (!empty($pager_only)) {
			if ($this->paging->total_results == 0) { ?>
			<div class="pjpagination">
			<img src="<?= $imgdir; ?>/arrowl.gif" alt="Arrow Left"/>
			<a href="<?= $url ?>?page=1&amp;<?= $querystring; ?>" class="current refresh_navigation">1</a>
			<img src="<?= $imgdir; ?>/arrowr.gif" alt="Arrow Right"/>
			</div>
			<?php }
			else {
				$start_page = $end_page = 0;
				$start_ellipses = $end_ellipses = TRUE;
				if ($this->paging->current_page - 5 <= 0) {
					$start_ellipses = FALSE;
					$start_page = max(2, $this->paging->current_page-2);
				}
				if ($this->paging->total_pages - $this->paging->current_page <= 4) {
					$end_ellipses = FALSE;
					$end_page = min($this->paging->current_page+2, $this->paging->total_pages-1);
				}
				if (!$start_page) $start_page = max(2, $this->paging->current_page-2);
				if (!$end_page) $end_page = min($this->paging->current_page+2, $this->paging->total_pages-1);
				if ($start_page == 3) $start_page--;
				if ($end_page == $this->paging->total_pages-2) $end_page++;
				//$start_page = $this->paging->current_page==4?2:max(2, $this->paging->current_page-1); // if we're currently on page 4, then start at page 2 and omit the ...
				//$end_page = $this->paging->current_page==($this->paging->total_pages-4)?($this->paging->total_pages-1):min($this->paging->current_page+2, ($this->paging->total_pages-1));
				?>
				<?php if ($this->paging->first_page) { ?>
				<img src="<?= $imgdir; ?>/arrowl.gif"/>
				<a href="<?= $url; ?>?page=1&amp;<?= $querystring; ?>" class="current refresh_navigation">1</a>
				<?php }
				else { ?>
					<?php //var_dump($_SERVER); ?>
				<a href="<?= $url; ?>?page=<?php echo ($this->paging->current_page - 1); ?>&amp;<?= $querystring; ?>" class="refresh_navigation"><img class="pagearrow" src="<?= $imgdir; ?>/arrowl.gif"/></a>
				<a href="<?= $url; ?>?page=1&amp;<?= $querystring; ?>" class="refresh_navigation">1</a>
				<?php } ?>
				<?php if (!empty($start_ellipses)) { ?>
				...
				<?php } ?>
				<?php for ($page=$start_page; $page<=$end_page; $page++) {
					if ($page == $this->paging->current_page) { ?>
				<a href="<?= $url; ?>?page=<?= $page; ?>&amp;<?= $querystring; ?>" class="current refresh_navigation"><?= $page; ?></a>
					<?php }
					else { ?>
				<a href="<?= $url; ?>?page=<?= $page; ?>&amp;<?= $querystring; ?>" class="refresh_navigation"><?= $page; ?></a>
					<?php }
				} ?>
				<?php if (!empty($end_ellipses)) { ?>
				...
				<?php } ?>
				<?php if ($this->paging->last_page) { ?>
				<?php if ($this->paging->total_pages != 1) { ?>
				<a href="<?= $url; ?>?page=<?php echo ($this->paging->total_pages); ?>&amp;<?= $querystring; ?>" class="current refresh_navigation"><?php echo ($this->paging->total_pages); ?></a>
				<?php } ?>
				<img src="<?= $imgdir; ?>/arrowr.gif"/>
				<?php }
				else { ?>
				<?php if ($this->paging->total_pages != 1) { ?>
				<a href="<?= $url; ?>?page=<?php echo ($this->paging->total_pages); ?>&amp;<?= $querystring; ?>" class="refresh_navigation"><?php echo ($this->paging->total_pages); ?></a>
				<?php } ?>
				<a href="<?= $url; ?>?page=<?php echo ($this->paging->current_page + 1); ?>&amp;<?= $querystring; ?>" class="refresh_navigation"><img class="pagearrow" src="<?= $imgdir; ?>/arrowr.gif"/></a>
				<?php }
			}
			$pager = ob_get_clean();

			ob_start();
			if ($this->paging->total_results == 0) { ?>
			<div class="pjshowing"><strong>Showing</strong> <span><strong>0-0</strong> of <strong>0</strong></span></div>
			<?php }
			else { ?>
			<strong>Showing</strong> <span><strong><?php echo ((($this->paging->current_page - 1) * $this->paging->page_size) + 1); ?>-<?php echo min($this->paging->total_results, ((($this->paging->current_page - 1) * $this->paging->page_size) + $this->paging->page_size)); ?></strong> of <strong><?php echo ($this->paging->total_results); ?></strong></span>
			<?php }
			$showing = ob_get_clean();
			return array('pager' => $pager, 'showing' => $showing, 'params' => $this->extra_params);
		}
		else { ?>
		<form action="<?= $_SERVER['REQUEST_URI']; ?>" class="pagejumper_form refresh_navigation" method="get">
			<input type="hidden" name="use_cached_refinements" value="1"/>
			<?php if ($this->context == 'search') { ?>
			<input type="hidden" name="<?php echo search::search_query_key; ?>" value="<?php echo implode(' ', $this->terms); ?>"/>
			<?php }
			elseif ($this->context == 'browse') { ?>
			<input type="hidden" name="<?php echo $this->cat_key; ?>" value="<?php echo $this->category_id; ?>"/>
			<?php } ?>
			<?php foreach ($this->extra_params as $param => $value) { ?>
			<input type="hidden" name="<?= $param; ?>" value="<?= $value; ?>"/>
			<?php } ?>
			<?php foreach ($_GET as $key => $val) {
				if (!preg_match('/^gae-/', $key)) continue; ?>
			<input type="hidden" name="<?= $key; ?>" value="<?= $val; ?>"/>
			<?php } ?>
			<?php if ($this->paging_options->firstrun) { ?>
			<style>
				.pagejumpers { font-family:Helvetica, Arial, sans-serif; line-height: 30px; }
				.pjshowing { color:#e51f37; font-size:12px; }
				.pjshowing span { color:#b7b7b7; font-size:12px; }
				.pjshowing span strong { color:#8b8b8b; }
				.pjpagination { color:#8b8b8b; font-size:14px; }
				.pjpagination a { color:#8b8b8b; text-decoration:none; padding:0 2px; }
				.pjpagination a:hover { text-decoration:underline; }
				.pjpagination .current { color:#e61f25; text-decoration:underline; font-weight:bold; }
				.pjsort { font-size:11px; color:#abaaaa; font-weight:bold; }
				.pjgo { font-size:11px; color:#abaaaa; font-weight:bold; }
				.pjgoto { background-color: transparent; background-image: url("<?= $imgdir; ?>/pjgotobox.gif"); background-repeat: no-repeat; border: medium none; color: #ADACB1; font-family: Helvetica,Arial,sans-serif; font-size: 12px; height: 19px; padding: 0; position: relative; top: -6px; width: 32px; text-align:center; outline:none; }
				.pagearrow { border:0px; }
			</style>
				<?php $this->paging_options->firstrun = FALSE;
			} ?>
			<!--table width="715" border="0" cellspacing="0" cellpadding="8">
				<tr>
					<td-->
						<div class="pagejumpers grid grid-pad">
							<div class="col-3-12 mobile-col-5-12 col-sm-12 col-sm-center">
								<?php if ($this->paging->total_results == 0) { ?>
								<div class="pjshowing"><strong>Showing</strong> <span><strong>0-0</strong> of <strong>0</strong></span></div>
								<?php }
								else { ?>
								<div class="pjshowing"><strong>Showing</strong> <span><strong><?php echo (($this->paging->current_page - 1) * $this->paging->page_size) + 1; ?>-<?php echo min($this->paging->total_results, ((($this->paging->current_page - 1) * $this->paging->page_size) + $this->paging->page_size)); ?></strong> of <strong><?php echo $this->paging->total_results; ?></strong></span></div>
								<?php } ?>
							</div>

							<div class="col-5-12 mobile-col-7-12 col-sm-12 col-sm-center">
								<?php if ($this->paging->total_results == 0) { ?>
								<div class="pjpagination">
								<img src="<?= $imgdir; ?>/arrowl.gif"/>
								<a href="<?= $url; ?>?page=1&amp;<?= $querystring; ?>" class="current refresh_navigation">1</a>
								<img src="<?= $imgdir; ?>/arrowr.gif"/>
								</div>
								<?php }
								else { ?>
								<div class="pjpagination">
									<?php
									$start_page = $end_page = 0;
									$start_ellipses = $end_ellipses = TRUE;
									if ($this->paging->current_page - 5 <= 0) {
										$start_ellipses = FALSE;
										$start_page = max(2, $this->paging->current_page-2);
									}
									if ($this->paging->total_pages - $this->paging->current_page <= 4) {
										$end_ellipses = FALSE;
										$end_page = min($this->paging->current_page+2, $this->paging->total_pages-1);
									}
									if (!$start_page) $start_page = max(2, $this->paging->current_page-2);
									if (!$end_page) $end_page = min($this->paging->current_page+2, $this->paging->total_pages-1);
									if ($start_page == 3) $start_page--;
									if ($end_page == $this->paging->total_pages-2) $end_page++;
									//$start_page = $this->paging->current_page==4?2:max(2, $this->paging->current_page-1); // if we're currently on page 4, then start at page 2 and omit the ...
									//$end_page = $this->paging->current_page==($this->paging->total_pages-4)?($this->paging->total_pages-1):min($this->paging->current_page+2, ($this->paging->total_pages-1));
									?>
									<?php if ($this->paging->first_page) { ?>
									<img src="<?= $imgdir; ?>/arrowl.gif"/>
									<a href="<?= $url; ?>?page=1&amp;<?= $querystring; ?>" class="current refresh_navigation">1</a>
									<?php }
									else { ?>
									<a href="<?= $url; ?>?page=<?php echo ($this->paging->current_page - 1); ?>&amp;<?= $querystring; ?>" class="refresh_navigation"><img class="pagearrow" src="<?= $imgdir; ?>/arrowl.gif"/></a>
									<a href="<?= $url; ?>?page=1&amp;<?= $querystring; ?>" class="refresh_navigation">1</a>
									<?php } ?>
									<?php if (!empty($start_ellipses)) { ?>
									...
									<?php } ?>
									<?php for ($page=$start_page; $page<=$end_page; $page++) {
										if ($page == $this->paging->current_page) { ?>
									<a href="<?= $url; ?>?page=<?= $page; ?>&amp;<?= $querystring; ?>" class="current refresh_navigation"><?= $page; ?></a>
										<?php }
										else { ?>
									<a href="<?= $url; ?>?page=<?= $page; ?>&amp;<?= $querystring; ?>" class="refresh_navigation"><?= $page; ?></a>
										<?php }
									} ?>
									<?php if (!empty($end_ellipses)) { ?>
									...
									<?php } ?>
									<?php if ($this->paging->last_page) { ?>
									<?php if ($this->paging->total_pages != 1) { ?>
									<a href="<?= $url; ?>?page=<?php echo ($this->paging->total_pages); ?>&amp;<?= $querystring; ?>" class="current refresh_navigation"><?php echo ($this->paging->total_pages); ?></a>
									<?php } ?>
									<img src="<?= $imgdir; ?>/arrowr.gif"/>
									<?php }
									else { ?>
									<?php if ($this->paging->total_pages != 1) { ?>
									<a href="<?= $url; ?>?page=<?php echo ($this->paging->total_pages); ?>&amp;<?= $querystring; ?>" class="refresh_navigation"><?php echo ($this->paging->total_pages); ?></a>
									<?php } ?>
									<a href="<?= $url; ?>?page=<?php echo ($this->paging->current_page + 1); ?>&amp;<?= $querystring; ?>" class="refresh_navigation"><img class="pagearrow" src="<?= $imgdir; ?>/arrowr.gif"/></a>
									<?php } ?>
								</div>
								<?php } ?>
							</div>


							<div class="col-2-12 mobile-col-5-12 col-sm-6 col-sm-center">
								<div class="pjshowtop">
									<div class="pjshowdd">
										<select name="results_per_page" size="1" id="showselect" class="refresh_navigation">
											<?php
											$page_size_found = FALSE;
											foreach ($this->paging_options->page_size as $page_size) { ?>
											<option value="<?= $page_size; ?>"<?php if ($page_size == $this->paging->page_size) { $page_size_found = TRUE; echo ' selected="selected"'; } ?>>Showing: <?= $page_size; ?></option>
											<?php }
											if (empty($page_size_found)) { ?>
											<option value="<?php echo $this->paging->page_size; ?>" selected="selected">Showing: <?php echo $this->paging->page_size; ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
							</div>


							<div class="col-2-12 mobile-col-7-12 col-sm-6 col-sm-center">
								<div class="pjsort">
									<select name="sort_by" size="1" id="sortby" class="refresh_navigation">
										<?php
										foreach ($this->sorting_options[$this->context] as $sort_key => $sort_value) { ?>
										<option value="<?= $sort_key; ?>"<?php if ($sort_key == $this->{$this->context.'_sort_key'}) { echo ' selected="selected"'; } ?>>Sort: <?= $sort_value; ?></option>
										<?php } ?>
									</select>
								</div>
								<?php if(1==0) { ?>
									<div class="pjgo">Go to Page<br/>
										<input type="text" name="page" class="pjgoto"/><input type="image" name="gopage" value="&#187;" src="<?= $imgdir; ?>/pjgoto.gif"/>
									</div>
								<?php } ?>
							</div>
						</div>
					<!--/td>
				</tr>
			</table-->
		</form>
		<?php }
		if (!$display) $paginator = ob_get_clean();
		return $display?TRUE:$paginator;

		/*
			<!--style>
				.pagination { background-color:#eedece; padding:4px; float:left; width:100%; }
				.pagination .block-1 { float:left; }
				.pagination .block-2 { float:right; }
				.pagination .block-3 { float:right; }
				.pagination .block-4 { text-align:center; clear:both; }
				.pagination .block-5 { float:left; }
				.pagination input[type=text] { width:30px; }
				.pagination a { font-weight:bold; }
				.pagination .nolink.highlight { display:inline-block; background-color:#e99; padding:2px; }
			</style>
			<div class="pagination">
				<div class="block-1">
					SHOWING: [
					<strong>
						<?php echo ((($this->paging->current_page - 1) * $this->paging->page_size) + 1); ?>
						-
						<?php echo ((($this->paging->current_page - 1) * $this->paging->page_size) + $this->paging->page_size); ?>
					</strong>
					of
					<strong><?php echo ($this->paging->total_results); ?></strong>
					]
				</div>

				<div class="block-2">
					SHOW: [
					<select name="results_per_page" size="1">
						<?php
						$page_size_found = FALSE;
						foreach ($this->paging_options->page_size as $page_size) { ?>
						<option value="<?= $page_size; ?>"<?php if ($page_size == $this->paging->page_size) { $page_size_found = TRUE; echo ' selected="selected"'; } ?>><?= $page_size; ?></option>
						<?php }
						if (empty($page_size_found)) { ?>
						<option value="<?php echo $this->paging->page_size; ?>" selected="selected"><?php echo $this->paging->page_size; ?></option>
						<?php } ?>
					</select>
					<input type="submit" name="gopagesize" value="&#187"/>
					]
				</div>

				<br/>

				<div class="block-3">
					GO DIRECTLY TO PAGE: [
					<input type="text" name="page"/>
					<input type="submit" name="gopage" value="&#187;"/>
					]
				</div>

				<div class="block-5">
					SORT: [
					<select name="sort_by" size="1">
						<?php
						foreach ($this->sorting_options[$this->context] as $sort_key => $sort_value) { ?>
						<option value="<?= $sort_key; ?>"<?php if ($sort_key == $this->{$this->context.'_sort_key'}) { echo ' selected="selected"'; } ?>><?= $sort_value; ?></option>
						<?php } ?>
					</select>
					<input type="submit" name="gopagesize" value="&#187"/>
					]
				</div>

				<div class="block-4">
					<?php if ($this->paging->first_page) { ?>
					<span class="nolink">&#171;</span>
					<span class="nolink">&#8249;</span>
					<?php }
					else { ?>
					<a href="<?= $_SERVER['REQUEST_URI']; ?>?page=1&amp;<?= $querystring; ?>" class="refresh_navigation">&#171;</a>
					<a href="<?= $_SERVER['REQUEST_URI']; ?>?page=<?php echo ($this->paging->current_page - 1); ?>&amp;<?= $querystring; ?>" class="refresh_navigation">&#8249;</a>
					<?php } ?>
					|
					<?php
					$start_page = max(1, $this->paging->current_page-4);
					$end_page = min($this->paging->current_page+4, $this->paging->total_pages);

					if ($start_page > 1) echo ' ... ';
					for ($page=$start_page; $page<=$end_page; $page++) {
						if ($page == $this->paging->current_page) { ?>
					<span class="nolink highlight"><?= $page; ?></span>
						<?php }
						else { ?>
					<a href="<?= $_SERVER['REQUEST_URI']; ?>?page=<?= $page; ?>&amp;<?= $querystring; ?>" class="refresh_navigation"><?= $page; ?></a>
						<?php }
					}
					if ($end_page < $this->paging->total_pages) echo ' ... ';
					?>
					|
					<?php if ($this->paging->last_page) { ?>
					<span class="nolink">&#8250;</span>
					<span class="nolink">&#187;</span>
					<?php }
					else { ?>
					<a href="<?= $_SERVER['REQUEST_URI']; ?>?page=<?php echo ($this->paging->current_page + 1); ?>&amp;<?= $querystring; ?>" class="refresh_navigation">&#8250;</a>
					<a href="<?= $_SERVER['REQUEST_URI']; ?>?page=<?php echo ($this->paging->total_pages); ?>&amp;<?= $querystring; ?>" class="refresh_navigation">&#187;</a>
					<?php } ?>
				</div>
			</div-->
		*/
	}

	public function prev_link() {
		if ($this->paging->first_page) return NULL;
		else return 'https://'.FQDN.$_SERVER['REQUEST_URI'].'?page='.($this->paging->current_page-1).'&amp;'.$this->paging_qstring();
	}

	public function next_link() {
		if ($this->paging->last_page) return NULL;
		else return 'https://'.FQDN.$_SERVER['REQUEST_URI'].'?page='.($this->paging->current_page+1).'&amp;'.$this->paging_qstring();
	}

	public function error() {
		if ($this->errs) return $this->errs;
		else return FALSE;
	}

	public function warning() {
		if ($this->warnings) return $this->warnings;
		else return FALSE;
	}

}
?>
