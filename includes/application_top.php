<?php
//sanitizing get vars for PCI compliance
function remove_tags($the_array) {
	foreach ($the_array as $key => $value) {
		if (!is_array($value)) {
			unset($the_array[$key]);
			$the_array[strip_tags(str_replace('alert(', '', $key))] = strip_tags(str_replace('alert(', '', $value));
		}
		else {
			unset($the_array[$key]);
			$the_array[strip_tags(str_replace('alert(', '', $key))] = remove_tags($value);
		}
	}
	return $the_array;
}
$_GET = remove_tags($_GET);

function is_cli() {
	return PHP_SAPI==='cli'?TRUE:FALSE;
}

chdir(__DIR__.'/..');

// set the include path for ZF
set_include_path(realpath('library').PATH_SEPARATOR.get_include_path());

require_once('library/ck.class.php');
$ck = new ck;

//ck::set_maintenance_window(ck_datetime::datify('2019-08-17 23:00:00'), ck_datetime::datify('2019-08-18 23:00:00'));
//ck::set_maintenance_area('db');
//ck::maintenance_redirect();

require_once('engine/vendor/autoload.php');
debug_tools::init_page();

require_once(__DIR__.'/configure.php');

// CORE CONFIG PROVIDER
$config = service_locator::get_config_service();

// temporarily turning this off until we can solve some of
// the problems
// if ($config->is_production()) {
// 	$honeybadger = Honeybadger\Honeybadger::new([
// 		'api_key' => '373b4335',
// 		'environment_name' => 'Production'
// 	]);
// }

$PHP_SELF = (isset($_SERVER['PHP_SELF'])?$_SERVER['PHP_SELF']:$_SERVER['SCRIPT_NAME']);

if (!defined('DIR_WS_CATALOG')) define('DIR_WS_CATALOG', DIR_WS_HTTPS_CATALOG);

require_once(DIR_FS_CATALOG.'includes/engine/tools/imagesizer.class.php');

// used for money_format
setlocale(LC_MONETARY, 'en_US.utf8');

// used for time format
setlocale(LC_TIME, 'en_US.UTF-8');

$__FLAG = request_flags::instance();

// define general functions used application-wide
require(DIR_WS_FUNCTIONS.'general.php');
require(DIR_WS_FUNCTIONS.'html_output.php');

// include navigation history class
require(DIR_WS_CLASSES.'navigation_history.php');

if (!ck::area_in_maintenance('db')) {
	// *** DATABASE ***
	// Zend db connection
	try {
		$db = service_locator::get_db_service();
	}
	catch (Exception $e) {
		header('Location: /outage.php');
		exit();
	}

	ck_config2::preload_legacy();

	$ck_keys = new ck_keys;

	//----------------------------------
	// site wide globals - this should really be a separate default config file
	if (empty($ck_keys->product['freeship_enabled'])) $ck_keys->{'product.freeship_enabled'} = TRUE;
	/*if (empty($ck_keys->product['freeship_threshold']))*/ $ck_keys->{'product.freeship_threshold'} = 99;
	if (empty($ck_keys->master_password)) $ck_keys->master_password = MASTER_PASS; //'Mahi@$24';
	if (empty($ck_keys->cart['default_country'])) $ck_keys->{'cart.default_country'} = 223;
	if (empty($ck_keys->cart['shipping_origin_postcode'])) $ck_keys->{'cart.shipping_origin_postcode'} = 30518;
	//----------------------------------

	// SESSION
	service_locator::get_session_service()->start();
}

$_SESSION['current_context'] = 'frontend';

if (CK\fn::check_flag($_GET['kiosk'] ?? null)) $_SESSION['kiosk'] = 1;
elseif (isset($_GET['kiosk'])) unset($_SESSION['kiosk']);

if (!empty($_SESSION['admin_login_id'])) {
	ini_set('display_errors', 1);
}

if (!empty($_REQUEST['action-level']) && $_REQUEST['action-level'] == 'global') {
	if ($_REQUEST['action'] == 'report-bug') {
		ck_bug_reporter::report();
	}
}

$router = ck_router::instance();
require(__DIR__.'/routes.php');
$page_handler = $router->route();

if (!isset($_SESSION['ref_url']) && isset($_SERVER['HTTP_REFERER'])) {
	$selfish = strpos($_SERVER['HTTP_REFERER'], 'cablesandkits');

	if ($selfish === false) $_SESSION['ref_url'] = $_SERVER['HTTP_REFERER'];
	else $_SESSION['ref_url'] = 'direct';
}
else $_SESSION['ref_url'] = 'direct';

if (!ck::area_in_maintenance('db')) {
	$cart = ck_cart::instance();

	// set the language
	// we only ever supported english anyway, so just remove all the other logic
	$_SESSION['language'] = 'english';
	$_SESSION['languages_id'] = 1;

	//MMD - remembering this value across the session
	if (!empty($_REQUEST['action']) && $_REQUEST['action'] == 'set-split-order') {
		if (!empty($_REQUEST['split_order'])) $_SESSION['split_order'] = "split";
		else $_SESSION['split_order'] = false;
		if ($__FLAG['ajax']) exit();
	}

	// redirect product or category pages, if necessary
	if (basename($_SERVER['SCRIPT_NAME']) == 'product_info.php') {
		$product_listing = new ck_product_listing($_GET['products_id']);
		$product_listing->redirect_if_necessary();
	}

	if (basename($_SERVER['SCRIPT_NAME']) == 'index.php' && isset($_GET['cPath']) && !isset($_GET['ajax'])) {
		$listing_category = new ck_listing_category(ck_listing_category::page_category($_GET['cPath']));
		$listing_category->redirect_if_necessary();

		$category_id = $listing_category->id(); // may or may not be required, but pulling from code we're replacing
	}

	// navigation history
	if (empty($_SESSION['navigation'])) $_SESSION['navigation'] = new navigationHistory;
	else $_SESSION['navigation']->path = [];
	//$_SESSION['navigation']->add_current_page();

	// down for maintenance except for admin ip
	if (EXCLUDE_ADMIN_IP_FOR_MAINTENANCE != getenv('REMOTE_ADDR')) {
		if (DOWN_FOR_MAINTENANCE == 'true' && !strstr($_SERVER['PHP_SELF'], DOWN_FOR_MAINTENANCE_FILENAME)) {
			CK\fn::redirect_and_exit('/down_for_maintenance.php');
		}
	}

	// do not let people get to down for maintenance page if not turned on
	if (DOWN_FOR_MAINTENANCE == 'false' && strstr($_SERVER['PHP_SELF'], DOWN_FOR_MAINTENANCE_FILENAME)) {
		CK\fn::redirect_and_exit('/index.php');
	}

	if (isset($_GET['action'])) $cart->process_page($_GET['action']);

	// auto expire special products
	require_once(DIR_WS_FUNCTIONS.'specials.php');
	tep_expire_specials();

	// calculate category path
	if (isset($_GET['cPath'])) $cPath = $_GET['cPath'];
	elseif (isset($_GET['products_id']) && !isset($_GET['manufacturers_id'])) {
		$product = new ck_product_listing($_GET['products_id']);
		$cPath = $product->get_category_cpath();
	}
	else $cPath = '';

	// include the breadcrumb class and start the breadcrumb trail
	require(DIR_WS_CLASSES.'breadcrumb.php');
	$breadcrumb = new breadcrumb;

	$breadcrumb->add('Home', HTTP_SERVER);

	// add category names or the manufacturer name to the breadcrumb trail
	if (!empty($cPath) && ($cpatharray = ck_listing_category::parse_cpath($cPath))) {
		$cPath = implode('_', $cpatharray);
		$current_category_id = $cpatharray[(sizeof($cpatharray)-1)];

		$cpatharray_length = count($cpatharray);
		$itteration_count = 0;
		if ($cpatharray_length == 1 && ($cat = new ck_listing_category($cpatharray[0])) && $cat->has_ancestors()) {
			$ancestors = array_reverse($cat->get_ancestors());
			foreach ($ancestors as $ancestor) {
				$breadcrumb->add($ancestor->get_header('categories_name'), $ancestor->get_url());
			}
			$header = empty($_GET['products_id']);
			$breadcrumb->add($cat->get_header('categories_name'), $cat->get_url(), $header);
		}
		else {
			foreach ($cpatharray as $cid) {
				$header = false;
				$itteration_count++;
				$listc = new ck_listing_category($cid);
				if ($itteration_count == $cpatharray_length) {
					if (empty($_GET['products_id'])) $header = true;
					$breadcrumb->add($listc->get_header('categories_name'), $listc->get_url(), $header);
				}
				else {
					$breadcrumb->add($listc->get_header('categories_name'), $listc->get_url());
				}
			}
		}
	}
	else $current_category_id = 0;

	// add the products model to the breadcrumb trail
	if (isset($_GET['products_id'])) {
		$plist = new ck_product_listing($_GET['products_id']);
		$breadcrumb->add($plist->get_header('products_model'), $plist->get_url());
	}

	if (!is_cli()) {
		// initialize the message stack for output messages
		$messageStack = new messageStack(messageStack::CONTEXT_PUBLIC);
	}

	if (!empty($_GET['keywords'])) {
		$_SESSION['search_term'] = $_GET['keywords'];
		$_SESSION['search_cat_id'] = !empty($_GET['categories_id'])?$_GET['categories_id']:NULL;
		$_SESSION['search_inc_sub_cat'] = !empty($_GET['inc_subcat'])?$_GET['inc_subcat']:NULL;
		$_SESSION['search_search_in_description'] = !empty($_GET['search_in_description'])?$_GET['search_in_description']:NULL;
	}

	unset($_SESSION['admin']);
	unset($_SESSION['admin_login_id']);
	if (isset($_COOKIE['osCAdminID2'])) {
		try {
			if ($admin_session_string = prepared_query::fetch('SELECT value FROM sessions WHERE sesskey = ? AND (modified + expiry) > ?', cardinality::SINGLE, [$_COOKIE['osCAdminID2'], time()])) {
				$admin_session = ck_session::decode_php_session_string($admin_session_string);
				if (!empty($admin_session['login_id'])) {
					$_SESSION['admin_login_id'] = $admin_session['login_id'];
					$_SESSION['admin'] = 'true';
					if (!empty($admin_session['set_admin_as_user'])) {
						ck_session::get_admin_user_login($admin_session_string);
					}
				}
			}
		}
		catch (Exception $e) {
			// don't need to do anything
		}
	}

	// set up content experiments
	if (class_exists('ga_experiment')) {
		$experiment_keys = ga_experiment::parse_keys($_GET);
		$experiment = ga_experiment::start($_SERVER['SCRIPT_NAME'], $_GET, $experiment_keys[0], $experiment_keys[1]);
	}
	else $experiment = FALSE;

	if (!empty($_POST['change-results-view']) && in_array($_POST['change-results-view'], array('list', 'grid'))) {
		$_SESSION['results-view'] = $_POST['change-results-view'];
		exit();
	}
}
?>
