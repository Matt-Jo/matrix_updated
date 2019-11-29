<?php
// eventually, this file will become the index, and receive all traffic. Some of the functionality herein will be moved to support files, which
// will replace includes/application_top.php & etc. This functionality my remain commented out in here as a placeholder in the meantime

if (!defined('CONTEXT')) define('CONTEXT', $config->get_env());
/** /
// handle errors and exceptions so that we don't
set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) { if (!($errno & error_reporting())) { return; } if (empty($GLOBALS['errlog'])) { $GLOBALS['errlog'] = array(); } $GLOBALS['errlog'][] = array('errno' => $errno, 'errstr' => $errstr, 'errfile' => $errfile, 'errline' => $errline); });
// handle exceptions by logging them to a variable, never printing info out directly
set_exception_handler(function (Exception $e) { if (empty($GLOBALS['errlog'])) { $GLOBALS['errlog'] = array(); } $GLOBALS['errlog'][] = $e; if (CONTEXT == 'WWW') { echo 'A FATAL ERROR OCCURRED, PLEASE CONTACT SUPPORT'; } else { echo '<pre>'; print_r($GLOBALS['errlog']); echo '</pre>'; } });
// handle fatal errors
register_shutdown_function(function () { $error = error_get_last(); if (!empty($error) && $error['type'] == E_ERROR) { if (empty($GLOBALS['errlog'])) { $GLOBALS['errlog'] = array(); } $GLOBALS['errlog'][] = array('errno' => $error['type'], 'errstr' => $error['message'], 'errfile' => $error['file'], 'errline' => $error['line']); if (CONTEXT == 'WWW') { echo 'A FATAL ERROR OCCURRED, PLEASE CONTACT SUPPORT'; } else { echo '<pre>'; print_r($GLOBALS['errlog']); echo '</pre>'; } } });

if (CONTEXT == 'WWW') {
	// this should be the default, so we'll leave it alone
	//error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
	ini_set('display_errors', 0);
}

else {
	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', 1);
}
/ **/

require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');
require_once(DIR_FS_CATALOG.'includes/engine/framework/canonical_page.class.php');
require_once(DIR_FS_CATALOG.'includes/engine/tools/imagesizer.class.php');

// if a view exists then there is no reason to create a new template, everything we need will be done through the view
$cktpl = NULL;
if (empty($view) || !($view instanceof ck_view)) $cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates');

/*----------------------------------------*/
$domain = $_SERVER['HTTP_HOST'].'/'; // /matrix
$cdn = '//media.cablesandkits.com';
$static = $cdn.'/static';

$content_map = new ck_content();
$content_map->cdn = $cdn;
$content_map->static_files = $static;
$content_map->context = CONTEXT;

if (!ck::area_in_maintenance('db')) {
	$content_map->contact_phone = $_SESSION['cart']->get_contact_phone();
	$content_map->contact_local_phone = $_SESSION['cart']->get_contact_local_phone();
	$content_map->contact_email = $_SESSION['cart']->get_contact_email();
}

// ----------------------------------------------
// START HEAD

$content_map->head = ['verify-v1?' => ['Id5qSbm5TRrGS+Qa3DCFghfE1kqlqXwLIIMIvQVTbKo='], 'fb:admins?' => ['100006394206803']];

//MMD D-175 - make optimizely URL a config value
$config = service_locator::get_config_service();
$content_map->head['optimizely_url_code'] = !empty($config->optimizely)?$config->optimizely->url_code:NULL;

if (!ck::area_in_maintenance('db')) {
	//sitewide header
	$sitewide_headers = prepared_query::fetch('SELECT cp.* FROM custom_pages cp WHERE cp.sitewide_header = 1 AND cp.visibility = 1', cardinality::SET);
	foreach ($sitewide_headers as $unused => $sitewide_header) {
		if (!isset($content_map->head['sitewide_header'])) $content_map->head['sitewide_header'] = '';
		$content_map->head['sitewide_header'] .= stripslashes($sitewide_header['page_code']);
	}
}

// set the context for the type of page that this is
$page_context = $version = NULL;
$request_details = parse_url($_SERVER['REQUEST_URI']); 
$script_details = pathinfo($_SERVER['SCRIPT_FILENAME']);
switch ($script_details['basename']) {
	case 'index.php':
		if (!empty($_GET['cPath']) && empty($browse_failed)) $page_context = 'catalog';
		else $page_context = 'homepage';
		break;
	case 'advanced_search_result.php':
		$page_context = 'search';
		break;
	case 'product_info.php':
		$page_context = 'product';
		break;
	case 'outlet.php':
		$page_context = 'outlet';
		break;
	case 'custserv.php':
		$page_context = 'customer_service';
		break;
	case 'contact_us.php':
		$page_context = 'contact_us';
		break;
	case 'login.php':
		$page_context = 'login';
		break;
	case 'logoff.php':
		$page_context = 'logoff';
		break;
	case 'checkout_shipping.php':
		$page_context = 'checkout_shipping';
		break;
	case 'checkout_payment.php':
		$page_context = 'checkout_payment';
		break;
	case 'checkout_confirmation.php':
		$page_context = 'checkout_confirmation';
		break;
	case 'checkout_success.php':
		$page_context = 'checkout';
		break;
	case 'wtb.php':
		$page_context = 'vendor_portal';
		break;
	case 'buyback.php':
		$page_context = 'buyback';
		break;
	case 'faqdesk_info.php':
		$page_context = 'faqdesk_info';
		break;
	case 'dow.php':
		$page_context = 'dow';
		break;
	case 'product_finderv2.php':
		//$version = 2;
	case 'product_finder.php':
		$page_context = 'product_finder';
		break;
	case 'page_includer.php':
		$page_context = 'page_includer';
		break;
	case '404.php':
		$page_context = '404';
		break;
	default:
		$page_context = 'page';
		break;
}

// GTM data layer
// tag manager is borking some of our javascript when the user is marked as a CK employee, so turn it off for now (round-about April/May 2016)
// 9/27/16 - turned it back on, there's reason to believe that whatever borked it before may be fixed
$gtm_data_layer = [];
$gtm_dl_events = [];
if (isset($_SESSION['admin']) && $_SESSION['admin'] == 'true') $gtm_data_layer['isCkEmployee'] = 1;

if (!ck::area_in_maintenance('db')) {
	// data for criteo
	$gtm_data_layer['email'] = $_SESSION['cart']->has_customer()?md5($_SESSION['cart']->get_customer()->get_logged_in_email($_SESSION['customer_extra_login_id'])):'';
	$gtm_data_layer['site_type'] = 'd'; // we only have a desktop site, not mobile or tablet
}

if ($page_context == 'homepage') $gtm_data_layer['event'] = 'HomePage';
elseif ($page_context == 'catalog') {
	$gtm_data_layer['event'] = 'ListingPage';
	$gtm_data_layer['products'] = [];
	if ($browse->paging->total_results > 0) {
		foreach ($browse->results as $product_id) {
			$gtm_data_layer['products'][] = $product_id;
		}
	}
}
elseif ($page_context == 'product') {
	$gtm_data_layer['event'] = 'ProductPage';
	$gtm_data_layer['product_id'] = $product->id();
}
elseif ($script_details['basename'] == 'shopping_cart.php') {
	$gtm_data_layer['event'] = 'BasketPage';
	$gtm_data_layer['products_info'] = [];
	if ($_SESSION['cart']->has_products()) {
		foreach ($_SESSION['cart']->get_products() as $product) {
			$gtm_data_layer['products_info'][] = ['id' => $product['products_id'], 'price' => $product['unit_price'], 'quantity' => $product['quantity']];
		}
	}
	if ($_SESSION['cart']->has_quotes()) {
		foreach ($_SESSION['cart']->get_quotes() as $quote) {
			if ($quote['quote']->has_products()) {
				foreach ($quote['quote']->get_products() as $product) {
					$gtm_data_layer['products_info'][] = ['id' => $product['products_id'], 'price' => $product['price'], 'quantity' => $product['quantity']];
				}
			}
		}
	}
}
elseif (!empty($view) && $view instanceof ck_view && method_exists($view, 'view_context')) {
	if ($view->view_context() == 'checkout_success') {
		$order = new ck_sales_order($_GET['order_id']);
		if (!$order->is('gtm_data_sent')) {

			//criteo
			$gtm_data_layer['transaction_id'] = $order->id();
			$gtm_data_layer['products_info'] = [];
			foreach ($order->get_products() as $product) {
				$gtm_data_layer['products_info'][] = [
					'id' => $product['products_id'],
					'price' => number_format($product['final_price'], 2, '.', ''),
					'quantity' => $product['quantity']
				];
			}

			//listrak
			$content_map->{'listrak_conversion?'} = 1;

			$gtm_data_layer['transactionId'] = $order->id();
			$gtm_data_layer['transactionAffiliation'] = '';
			$prime_contact = $order->get_prime_contact();
			$gtm_data_layer['order'] = ['customer_email' => $prime_contact['email'], 'customer_firstname' => $prime_contact['firstname'], 'customer_lastname' => $prime_contact['lastname']];

			$totals = $order->get_simple_totals();
			$gtm_data_layer['transactionTotal'] = number_format($totals['total'], 2, '.', '');
			$gtm_data_layer['transactionShipping'] = !empty($totals['shipping'])?number_format($totals['shipping'], 2, '.', ''):'0.00';
			if (!empty($totals['tax'])) $gtm_data_layer['transactionTax'] = number_format($totals['tax'], 2, '.', '');

			$subtotal = 0;

			$gtm_data_layer['transactionProducts'] = [];
			foreach ($order->get_products() as $product) {
				$gtm_data_layer['transactionProducts'][] = [
					'name' => $product['name'],
					'model' => $product['model'],
					'sku' => $product['products_id'],
					'category' => $product['ipn']->get_header('ipn_category'),
					'price' => number_format($product['final_price'], 2, '.', ''),
					'quantity' => $product['quantity']
				];

				$subtotal += $product['final_price'] * $product['quantity'];
			}

			$gtm_data_layer['transactionItemTotal'] = number_format($subtotal, 2, '.', '');

			$gtm_dl_events[] = 'transactionTracked';
			$gtm_dl_events[] = 'TransactionPage';

			$order->gtm_sent();
		}
	}
}

if ($__FLAG['skipgtm']) $content_map->head['skip_gtm?'] = 1;

$content_map->head['gtm_id'] = $service_environment['gtm']['account_id']; //'GTM-5HRCVX'; //
$content_map->head['gtm_data_layer'] = json_encode($gtm_data_layer);
$content_map->head['gtm_dl_events'] = $gtm_dl_events;

// set the page key for dynamic content on this page
$page_key = NULL;
switch ($page_context) {
	case 'catalog':
	case 'product_finder':
		$page_key = (object) ['cPath' => $_GET['cPath']];
		$page_key->category_path = explode('_', $page_key->cPath);
		$page_key->category_id = end($page_key->category_path);
		reset($page_key->category_path);
		break;
	case 'product':
		$page_key = $_GET['products_id'];
		break;
	case 'vendor_portal':
		$page_key = $content;
		break;
	case 'homepage':
	case 'page':
	case 'search':
	case 'outlet':
	case 'checkout':
	case 'logoff':
	default:
		$page_key = $_SERVER['REQUEST_URI'];
		break;
}
// hacking the max width for now as we transition to a full width site
if (in_array($page_context, ['outlet', 'search', 'catalog', 'product_finder', 'homepage'])) $content_map->page['max_width'] = '1200px';

// need to use a global "page object" system whereby we aren't having to re-query the category or product details anywhere else in the page once we've grabbed them here at the top

// set head title, meta description
if ($page_context == 'homepage') {
	$content_map->head['title'] = 'CablesAndKits: The Network Hardware & Cabling Experts';
	$content_map->head['meta']['description'] = 'Trust your Data Center to CablesAndKits, the industry leader in Cisco Hardware, Ethernet cables, Fiber and more. Lifetime Warranty, Same-day Shipping & Expert Advice.';
}
elseif ($page_context == 'dow') {
	$content_map->head['title'] = 'Deal Of The Week | Switches, Routers, Phones, Modules, & More';
	$content_map->head['meta']['description'] = 'CablesAndKits.com: One stop source for Cisco Accessories and more!';
}
elseif (in_array($page_context, ['customer_service', 'contact_us'])) {
	$content_map->head['title'] = 'CablesAndKits: Contact our Experts for Help';
	$content_map->head['meta']['description'] = 'Our Customer Service experts are here to help. Give us a call, chat or email and one of our amazing customer service specialists will assist you with your needs.';
}
elseif (in_array($page_context, ['catalog', 'product_finder'])) {
	$cd = prepared_query::fetch('SELECT categories_name, categories_head_desc_tag, categories_head_title_tag FROM categories_description WHERE categories_id = ?', cardinality::ROW, [$page_key->category_id]);
	$content_map->head['title'] = !empty($cd['categories_head_title_tag'])?$cd['categories_head_title_tag']:(!empty($cd['categories_name'])?$cd['categories_name']:'CablesAndKits.com'); // Should we have a default?
	$content_map->head['meta']['description'] = !empty($cd['categories_head_desc_tag'])?$cd['categories_head_desc_tag']:(!empty($cd['categories_name'])?$cd['categories_name']:'CablesAndKits.com'); // Should we have a default?
	if (!empty($_GET['refinement_data'])) $content_map->head['meta']['robots'] = 'nofollow';
}
elseif (in_array($page_context, ['search'])) {
	$content_map->head['title'] = 'CablesAndKits.com';
	$content_map->head['meta']['description'] = 'CablesAndKits.com: One stop source for Cisco Accessories and more!';
	$content_map->head['meta']['robots'] = 'noindex,nofollow';
}
elseif ($page_context == 'product') {
	// questions: Tax Rate? In Stock? Skip head_description_tag?
	$content_map->head['title'] = !empty($product->get_header('products_head_title_tag'))?$product->get_header('products_head_title_tag'):(!empty($product->get_header('products_name'))?$product->get_header('products_name'):$product->get_header('products_model'));
	$content_map->head['meta']['description'] = $product->get_header('products_name').' - '.$product->get_header('products_model').' - $'.number_format($product->get_price('display'), 2).' - In Stock!';
	if (!empty($_GET['yoReviewsPage'])) $content_map->head['meta']['robots'] = 'noindex,nofollow';
}
elseif (!empty($view) && $view instanceof ck_view) {
	if (method_exists($view, 'page_title')) $content_map->head['title'] = $view->page_title();
	else $content_map->head['title'] = 'CablesAndKits.com';

	if (method_exists($view, 'page_meta_description')) $content_map->head['meta']['description'] = $view->page_meta_description();
	else $content_map->head['meta']['description'] = 'CablesAndKits.com: One stop source for Cisco Accessories and more!';

	if (!empty($_GET['yoReviewsPage'])) $content_map->head['meta']['robots'] = 'noindex,nofollow';
}
else {
	$content_map->head['title'] = 'CablesAndKits.com';
	$content_map->head['meta']['description'] = 'CablesAndKits.com: One stop source for Cisco Accessories and more!';
}

// if this page has a canonical link that is not itself, use it
$canonical = new canonical_page($page_key, $_SERVER['REQUEST_URI'], $page_context);
if ($canonical->use_link()) $content_map->head['canonical?'] = [$canonical->link]; // stick it in an array just to take advantage of simplified mustache structures

if (in_array($page_context, ['catalog', 'search', 'outlet'])) {
	$content_map->head['links'] = [];
	if (!empty($browse)) {
		$prev = $browse->prev_link();
		$next = $browse->next_link();
	}
	elseif (!empty($search)) {
		$prev = $search->prev_link();
		$next = $search->next_link();
	}

	if (!empty($prev)) $content_map->head['links'][] = ['rel' => 'prev', 'href' => $prev];
	if (!empty($next)) $content_map->head['links'][] = ['rel' => 'next', 'href' => $next];
}

// HANDLE EXPERIMENTS HERE - for now we're skipping them, since we're not running any experiments currently

// if this is the logoff page, set a header meta refresh to get us back to a non-ssl page
if ($page_context == 'logoff') {
	$content_map->head['meta']['refresh?'] = ["2;URL=https://$domain"]; //s chnaged
}

// ----------------------------------------------
// START BODY

$content_map->body = [
	'class' => NULL,
	'homepage' => 'http://'.$domain //here there is https so when  logo is clicked it directs to https:// to make http:// I removed s 
];


if ($page_context == 'vendor_portal') $content_map->body['class'] = 'vendor-portal';

if (!empty($javascript) && is_file(DIR_WS_JAVASCRIPT.$javascript)) {
	if (preg_match('/\.php$/', $javascript)) {
		ob_start();
		include_once(DIR_WS_JAVASCRIPT.$javascript);
		$js = ob_get_clean();
	}
	else $js = file_get_contents(DIR_WS_JAVASCRIPT.$javascript);
	$content_map->body['javascript'] = $js;
}

if (!empty($_GET['error_message'])) {
	$content_map->body['error_message?'] = [strip_tags(urldecode($_GET['error_message']))]; // we're stripping tags, otherwise we'll have to do some significant checking to make sure we're not opening up a vulnerability
	// we'll still need to change this to storing errors in the user session rather than passing them through the querystring, we don't want someone else to be able to pop up an error on our site
}

if (empty($_SESSION['customer_id'])) {
	$content_map->login = [
		'status' => 'anon',
		'anon' => 1
	];
}
else {
	$content_map->login = ['status' => 'user'];
	$content_map->login['user_id'] = $_SESSION['customer_id'];
	$content_map->login['name'] = '';
}

if (!ck::area_in_maintenance('db')) {
	$content_map->cart = ['has_products' => $_SESSION['cart']->has_products()];

	if (empty($ck_keys->template_cache['topnav_categories']) || empty($ck_keys->template_cache['category_allparents'])) {
		$categories = ck_listing_category::rebuild_topnav_structure($page_context=='product_finder');
	}
}

$content_map->topnav = ['categories' => $ck_keys->template_cache['topnav_categories']];

if ($page_context != 'vendor_portal') $content_map->head['show_full?'] = 1;
else {
	$content_map->head['show_vp'] = 1;
	if (!empty($_SESSION['customer_id'])) {
		$customer = new ck_customer2($_SESSION['customer_id']);
		if ($customer->is_allowed('vendor_portal.accessory_finder')) {
			$content_map->head['vp_lookup?'] = 1;

			if ($page_key == 'wtb') $content_map->head['lookup_link?'] = 1;
			elseif ($page_key == 'vp_lookup') $content_map->head['wtb_link?'] = 1;
		}
		elseif ($page_key == 'vp_lookup') {
			CK\fn::redirect_and_exit('/VendorPortal');
		}
	}
}
// this is where we will load all the data for the customer account partials
if (!empty($view) && $view instanceof ck_view && property_exists($view, 'secondary_partials') && $view->secondary_partials && !empty($_SESSION['customer_id'])) {
	$customer = new ck_customer2($_SESSION['customer_id']);
	// data for partial -- side nav and header
	$content_map->page_title = $view->page_title();
	$content_map->total_orders = $customer->get_order_count();
	$content_map->customer_name = $customer->get_display_label();
	$content_map->sales_contact_phone = $customer->get_contact_phone();
	$content_map->sales_contact_clickable_phone = str_replace('.', '', $customer->get_contact_phone());
	$content_map->sales_contact_local_phone = $customer->get_contact_local_phone();
	$content_map->sales_contact_local_clickable_phone = str_replace('.', '', $customer->get_contact_local_phone());
	$content_map->sales_contact_email = $customer->get_contact_email();

	if ($view->page_title() == 'orders') $content_map->order_page = 'active';
	elseif ($view->page_title() == 'Account Payment') $content_map->payment_page = 'active';
	elseif ($view->page_title() == 'account info') $content_map->info_page = 'active';
	elseif ($view->page_title() == 'addresses') $content_map->address_page = 'active';

	$content_map->outstanding_invoices = count($customer->get_outstanding_invoices());

	if ($customer->is_allowed('vendor_portal.accessory_finder')) $content_map->vendor_portal_access = 1;

	if ($customer->has_sales_team()) {
		$content_map->sales_team = $customer->get_sales_team()->get_header('label');
		if ($customer->get_sales_team()->has_members()) {
			foreach ($customer->get_sales_team()->get_members() as $member) {
				if (empty($member['admin_id'])) continue;
				$content_map->team_members[] = $member['member']->get_header('first_name') . ' ' . $member['member']->get_header('last_name');
			}
		}
	}
	// end partial data
}

if (!empty($view) && $view instanceof ck_view) $view->open($content_map);
elseif (!empty($cktpl)) $cktpl->open($content_map);

if (isset($_SESSION['admin']) && $_SESSION['admin'] == 'true') ck_bug_reporter::render();

// ----------------------------------------------
// START MAIN CONTENT

if ($page_context == 'homepage') {
	// rotator
	$content_map = new ck_content();
	$content_map->cdn = $cdn;
	$content_map->static_files = $static;

	$content_map->contact_phone = $_SESSION['cart']->get_contact_phone();
	$content_map->contact_local_phone = $_SESSION['cart']->get_contact_local_phone();
	$content_map->contact_email = $_SESSION['cart']->get_contact_email();

	if (empty($ck_keys->dow['schedule_type'])) $ck_keys->__set('dow.schedule_type', 'weekly');

	$schedtype = 'sched_'.$ck_keys->dow['schedule_type'].'?';
	$content_map->$schedtype = 1;

	$content_map->rotate_banners = [];

	$homepage = new ck_homepage();

	$content_map->rotate_banners = [];
	$rotator = $homepage->get_rotator();

	$exts = [
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'webp' => 'image/webp',
	];

	foreach ($rotator as $element) {
		if (empty($element['active'])) continue;

		$imgmeta = parse_url($element['absolute_img_ref']);
		$imgmeta2 = pathinfo($imgmeta['path']);

		$img = '//'.$imgmeta['host'].$imgmeta2['dirname'].'/'.$imgmeta2['filename'];
		$ext = $imgmeta2['extension'];

		$banner = ['img' => $img.'.'.$ext, 'mime' => $exts[$ext], 'optimg' => $img.'.webp?optimg', 'alt' => $element['alt_text']];

		if ($element['link_target_type'] == 'category_id') {
			$bannercat = new ck_listing_category($element['link_target']);
			$banner['link'] = $bannercat->get_url();
		}
		else {
			$banner['link'] = $element['link_target'];
			$ref = parse_url($element['link_target']);
			if (ck_homepage::is_link_fully_qualified($element) && $ref['host'] != $_SERVER['HTTP_HOST']) {
				$banner['newpage?'] = 1;
			}
		}

		$content_map->rotate_banners[] = $banner;
	}

	// showcases
	if ($homepage->has_showcases('active')) {
		$content_map->has_showcases = TRUE;

		foreach ($homepage->get_showcases('active') as $showcase) {
			$showcase_template = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
			$showcase_template->buffer = TRUE;

			$showcase_content = new ck_content;
			$showcase_content->product_list = [];

			if (!empty($showcase['product_ids'])) {
				$products_ids = preg_split('/\s*,\s*/', $showcase['product_ids']);
				ck_ipn2::preload_listing_inventory($products_ids);
				foreach ($products_ids as $products_id) {
					$product = new ck_product_listing($products_id);
					if (!$product->is_viewable()) continue;
					$key = 'prod-'.$product->id();
					$template = $product->get_thin_template();
					$showcase_content->$key = $template;
					$showcase_content->product_list[] = $template;
				}
			}

			$content_map->showcases[] = ['html' => $showcase_template->simple_content($showcase['html'], $showcase_content)];
		}
	}

	/* // working on a banner scheduler - it's incomplete, has work done in:
	// /admin/scheduled_update_dow.php
	// /admin/banner_schedule.php
	// /admin/includes/boxes/marketing.php
	if ($banners = prepared_query::fetch('SELECT * FROM ck_banners WHERE active = 1 ORDER BY sort_order ASC, banner_id DESC', cardinality::SET)) {
		foreach ($banners as $banner) {
			$content_map->rotate_banners[] = (object) array('img' => $static.$banner['img'], 'link' => $banner['link'], 'alt' => $banner['alt']);
		}
	}
	else {
		$content_map->rotate_banners[] = (object) array('img' => $static.'/img/Why-Ck-Banner2.png', 'link' => '/whyck', 'alt' => 'Why Cables & Kits?');
	}*/
	// kickers
	$content_map->kickers = ['group1' => [], 'group2' => []];
	$kickers = $homepage->get_kickers();
	foreach ($kickers as $idx => $element) {
		if (empty($element['active'])) continue;

		$imgmeta = parse_url($element['absolute_img_ref']);
		$imgmeta2 = pathinfo($imgmeta['path']);

		$img = '//'.$imgmeta['host'].$imgmeta2['dirname'].'/'.$imgmeta2['filename'];
		$ext = $imgmeta2['extension'];

		$kicker = ['img' => $img.'.'.$ext, 'mime' => $exts[$ext], 'optimg' => $img.'.webp?optimg', 'alt' => $element['alt_text']];

		if ($element['link_target_type'] == 'category_id') {
			$kickercat = new ck_listing_category($element['link_target']);
			$kicker['link'] = $kickercat->get_url();
		}
		else {
			$kicker['link'] = $element['link_target'];
			$ref = parse_url($element['link_target']);
			if (ck_homepage::is_link_fully_qualified($element) && $ref['host'] != $_SERVER['HTTP_HOST']) {
				$kicker['newpage?'] = 1;
			}
		}

		if (count($content_map->kickers['group1']) <= 1) $content_map->kickers['group1'][] = $kicker;
		else $content_map->kickers['group2'][] = $kicker;
	}

	$dow = dow::get_active_dow();

	//$dow = prepared_query::fetch('SELECT p.products_image_lrg as img, pd.products_name as title, p.products_price as reg_price, COALESCE(s.specials_new_products_price, p.products_price) as price FROM ck_dow_schedule ds JOIN products p ON ds.products_id = p.products_id JOIN products_description pd ON p.products_id = pd.products_id LEFT JOIN specials s ON p.products_id = s.products_id WHERE ds.active = 1 ORDER BY ds.start_date DESC LIMIT 1', cardinality::ROW);
	// use the old, tagless image name, so if one is created for that image then it'll use it rather than override it with the current DOW image, otherwise it'll create with the DOTW-less name
	$imgpath = pathinfo($dow['products_image_lrg']);
	preg_match('/^(.+)(_('.implode('|', array_keys(imagesizer::$map)).'))?$/', $imgpath['filename'], $filename);
	$img = imagesizer::resize(DIR_FS_CATALOG.'images/'.$dow['products_image_lrg'], imagesizer::$map['dow'], DIR_FS_CATALOG.'images', 'product/'.$filename[1].'_dow.'.$imgpath['extension']);
	if (!$img) $img = 'product/'.$filename[1].'_dow.'.$imgpath['extension'];
	$content_map->dow = [
		'link' => '/dow',
		'img' => $img,
		'alt' => $dow['title'],
		'title' => $dow['title'],
		'reg_price' => number_format($dow['reg_price'], 2),
		'price' => number_format($dow['price'], 2)
	];
	if ($dow['reg_price'] != $dow['price']) {
		$content_map->dow['on_special'] = '1';
	}

	$cktpl->content(DIR_FS_CATALOG.'includes/templates/page-homepage.mustache.html', $content_map);
}
elseif ($page_context == 'dow') {
	$content_map = new ck_content();

	$content_map->contact_phone = $_SESSION['cart']->get_contact_phone();
	$content_map->contact_local_phone = $_SESSION['cart']->get_contact_local_phone();
	$content_map->contact_email = $_SESSION['cart']->get_contact_email();

	if (empty($ck_keys->dow['schedule_type'])) $ck_keys->__set('dow.schedule_type', 'weekly');

	$schedtype = 'sched_'.$ck_keys->dow['schedule_type'].'?';
	$content_map->$schedtype = 1;

	if (isset($_SESSION['admin']) && $_SESSION['admin'] == 'true' && !empty($_GET['edit-dow-id'])) $content_map->edit_dow_id = $_GET['edit-dow-id'];

	if (empty($dow['expires_date'])) $dow['expires_date'] = prepared_query::fetch('SELECT expires_date FROM specials WHERE products_id = ? AND expires_date >= NOW()', cardinality::SINGLE, $product->get_header('products_id'));
	if (empty($dow['expires_date'])) $dow['expires_date'] = date('Y-m-d 23:59:59');

	$content_map->product = $product->get_template();

	$content_map->product['display_available'] = ($product->get_inventory('display_available_num')>0?$product->get_inventory('display_available_num').' in stock':' ');
	if (!empty($dow['custom_description'])) $content_map->product['description'] = $dow['custom_description'];
	$content_map->product['end_date'] = $dow['expires_date'];

	if (!empty($dow['legalese'])) $content_map->product['legalese'] = $dow['legalese'];

	if (($recommendations = dow::get_dow_recommended($dow['dow_schedule_id']))) {
		$content_map->recommendations = ['products' => []];
		foreach ($recommendations as $idx => $rec) {
			if ($idx+1 == count($recommendations)) $content_map->recommendations['products'][] = ['idx' => $idx, 'id' => $rec['products_id'], 'name' => $rec['products_name'], 'img' => $rec['img'], 'ordinal' => $rec['ordinal'], 'last' => 1];
			else $content_map->recommendations['products'][] = ['idx' => $idx, 'id' => $rec['products_id'], 'name' => $rec['products_name'], 'img' => $rec['img'], 'ordinal' => $rec['ordinal']];
		}
	}

	$cktpl->content(DIR_FS_CATALOG.'includes/templates/page-dow.mustache.html', $content_map);
}
elseif (!empty($view) && $view instanceof ck_view) $view->respond();
elseif (!empty($page_controller)) {
	$page_controller->display($script_details);
}
elseif (function_exists('build_page_template')) {
	$content_map = new ck_content();
	$content_map->cdn = $cdn;
	$content_map->static_files = $static;

	$content_map->contact_phone = $_SESSION['cart']->get_contact_phone();
	$content_map->contact_local_phone = $_SESSION['cart']->get_contact_local_phone();
	$content_map->contact_email = $_SESSION['cart']->get_contact_email();

	if ($breadcrumb->size() > 1 && !in_array($page_context, array('outlet', 'login')) && $_SERVER['PHP_SELF'] != '/values.php' && $_SERVER['PHP_SELF'] != '/whyck.php') {
		$content_map->{'breadcrumbs?'} = $breadcrumb->trail();
	}

	// we have the page template spit out the template file, just to not worry about scope
	if ($template_file = build_page_template($content_map)) { // content_map gets modified by reference
		// if we've actually got a template, display it, otherwise it was handled within the function
		$cktpl->content($template_file, $content_map);
	}
}
else {
	if ($page_context == 'product' && $product->is_viewable()) {
		$content_map = new ck_content();

		$content_map->contact_phone = $_SESSION['cart']->get_contact_phone();
		$content_map->contact_local_phone = $_SESSION['cart']->get_contact_local_phone();
		$content_map->contact_email = $_SESSION['cart']->get_contact_email();

		if (isset($_SESSION['admin']) && $_SESSION['admin'] == 'true') $content_map->{'admin?'} = 1;

		$content_map->product = $product->get_template();

		$ck_template = DIR_FS_CATALOG.'includes/templates/page-product_info.mustache.html';
	}
	elseif ($page_context == 'product') {
		$content_map = new ck_content();

		$content_map->contact_phone = $_SESSION['cart']->get_contact_phone();
		$content_map->contact_local_phone = $_SESSION['cart']->get_contact_local_phone();
		$content_map->contact_email = $_SESSION['cart']->get_contact_email();

		if (isset($_SESSION['admin']) && $_SESSION['admin'] == 'true') $content_map->{'admin?'} = 1;

		$content_map->{'noproduct?'} = 1;

		$ck_template = DIR_FS_CATALOG.'includes/templates/page-product_info.mustache.html';
	} ?>

	<div class="centertable">
		<?php if (in_array($page_context, ['catalog', 'search'])) { ?>
		<div class="leftTd">
			<div class="mob_only cat_select"><span>Filter</span></div>
			<table cellspacing="0" cellpadding="8px" border="0" class="leftFilter">
				<?php if (isset($browse) && is_object($browse) && $browse instanceof nav_service_interface) { ?>
				<tr><td><?php $browse->refinements(TRUE); ?></td></tr>
				<?php }
				elseif (isset($search) && is_object($search) && $search instanceof nav_service_interface) { ?>
				<tr><td><?php $search->refinements(TRUE); ?></td></tr>
				<?php } ?>
			</table>
		</div>
		<?php }
		/* moving this to the vendor portal page
		 * elseif ($page_context == 'vendor_portal' && $page_key == 'wtb') { ?>
		<div style="background-color:#f9f7f8;text-align:left;border-style:solid; border-color:#cecece; border-width:0px 0px 2px 1px; vertical-align:top;">
			<?php require(DIR_WS_INCLUDES.'vp_column_left.php'); ?>
		</div>
		<?php } */?>

		<div valign="top" style="border-style:solid; border-color:#cecece; border-width:0px 1px 2px 1px;">
			<?php if ($breadcrumb->size() > 1 && !in_array($page_context, ['outlet', 'login']) && $_SERVER['PHP_SELF'] != '/values.php' && $_SERVER['PHP_SELF'] != '/whyck.php') { ?>
			<style>
				.lt-ie9 div.tools { width:100%; }
			</style>
			<div class="tools">
				<?php echo $breadcrumb->trail(); ?>
			</div>
			<?php }

			if (!empty($ck_template)) $cktpl->content($ck_template, $content_map);
			elseif (isset($content_template)) require(DIR_WS_CONTENT.$content_template);
			elseif (!empty($content)) require(DIR_WS_CONTENT.$content.'.tpl.php'); ?>
		</div>
	</div>
<?php }

// ----------------------------------------------
// START FOOTER

$content_map = new ck_content();
$content_map->cdn = $cdn;
$content_map->static_files = $static;

if (!ck::area_in_maintenance('db')) {
	$content_map->contact_phone = $_SESSION['cart']->get_contact_phone();
	$content_map->clickable_contact_phone = str_replace('.', '', $_SESSION['cart']->get_contact_phone());
	$content_map->contact_local_phone = $_SESSION['cart']->get_contact_local_phone();
	$content_map->clickable_local_phone = str_replace('.', '', $_SESSION['cart']->get_contact_local_phone());
	$content_map->contact_email = $_SESSION['cart']->get_contact_email();
}

$content_map->copydate = date('Y');

if (CONTEXT != 'WWW' && !empty($errlog)) $content_map->errlog = print_r($errlog, TRUE);

$content_map->google_trusted_store = ['key' => '202120'];
$content_map->google_trusted_store['subkey'] = '4090110';
if ($page_context == 'product') $content_map->google_trusted_store['products_id?'] = [$page_key];

$content_map->google_remarketing = ['key' => '1070544332'];
if ($page_context == 'homepage') $content_map->google_remarketing['pagetype'] = 'home';
elseif ($page_context == 'catalog') $content_map->google_remarketing['pagetype'] = 'category';
elseif ($page_context == 'product') {
	$content_map->google_remarketing['pagetype'] = 'product';
	$content_map->google_remarketing['prodid'] = $page_key;
	$content_map->google_remarketing['totalvalue'] = $product->get_price('display');
}
elseif ($page_context == 'search') $content_map->google_remarketing['pagetype'] = 'searchresults';
elseif ($page_context == 'cart') {
	$content_map->google_remarketing['pagetype'] = 'cart';
	if (!empty($_SESSION['cart'])) $content_map->google_remarketing['totalvalue'] = $_SESSION['cart']->get_total();
}
elseif (!empty($view) && $view instanceof ck_view && method_exists($view, 'view_context')) {
	if (in_array($view->view_context(), ['checkout_shipping', 'checkout_payment', 'checkout_address', 'checkout_confirmation'])) {
		$content_map->google_remarketing['pagetype'] = 'purchase';
		if (!empty($_SESSION['cart'])) $content_map->google_remarketing['totalvalue'] = $_SESSION['cart']->get_total();
	}
	else $content_map->google_remarketing['pagetype'] = 'other';
}
else $content_map->google_remarketing['pagetype'] = 'other';

if (empty($view) || !($view instanceof ck_view) || !method_exists($view, 'view_context') || $view->view_context() != 'checkout_success') {
	$content_map->google_remarketing_revisited = ['key' => '1070544332'];
}

$content_map->addshopper = ['key' => '512693afa387642e6d6e0843'];

if ($page_context != 'vendor_portal') $content_map->foot['show_full?'] = 1;

if (!ck::area_in_maintenance('db')) {
	//category links for footer nav
	$content_map->category_url = [];

	$ethernet_url = new ck_listing_category(121);
	$content_map->category_url['ethernet_url'] = $ethernet_url->get_url();

	$fiber_url = new ck_listing_category(282);
	$content_map->category_url['fiber_url'] = $fiber_url->get_url();

	$cisco_equipment = new ck_listing_category(50);
	$content_map->category_url['cisco_equipment_url'] = $cisco_equipment->get_url();

	$cisco_switches = new ck_listing_category(82);
	$content_map->category_url['cisco_switches_url'] = $cisco_switches->get_url();

	$fiber_om3 = new ck_listing_category(320);
	$content_map->category_url['fiber_om3_url'] = $fiber_om3->get_url();

	$cisco_accessories = new ck_listing_category(232);
	$content_map->category_url['cisco_accessories_url'] = $cisco_accessories->get_url();

	$racks_and_cabinets = new ck_listing_category(240);
	$content_map->category_url['racks_and_cabinets_url'] = $racks_and_cabinets->get_url();

	$power_products = new ck_listing_category(241);
	$content_map->category_url['power_products_url'] = $power_products->get_url();

	$packs_and_bundles = new ck_listing_category(1232);
	$content_map->category_url['packs_and_bundles_url'] = $packs_and_bundles->get_url();

	$cisco_transceivers = new ck_listing_category(85);
	$content_map->category_url['cisco_transceivers_url'] = $cisco_transceivers->get_url();

	$cisco_console_cables = new ck_listing_category(41);
	$content_map->category_url['cisco_console_cables_url'] = $cisco_console_cables->get_url();

	$ethernet_cat6 = new ck_listing_category(1057);
	$content_map->category_url['ethernet_cat6_url'] = $ethernet_cat6->get_url();

	$cable_management = new ck_listing_category(33);
	$content_map->category_url['cable_management_url'] = $cable_management->get_url();

	$servers_url = new ck_listing_category(1265);
	$content_map->category_url['servers_url'] = $servers_url->get_url();
}

if (!empty($view) && $view instanceof ck_view) $view->close($content_map);
elseif (!empty($cktpl)) $cktpl->close($content_map);
?>