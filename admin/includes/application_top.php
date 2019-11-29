<?php
// set the include path for ZF
set_include_path(realpath('../library').PATH_SEPARATOR.get_include_path());

require_once(__DIR__.'/../../includes/library/ck.class.php');
$ck = new ck;

//include_once('includes/library/ck_toolset.class.php');
include_once('../includes/engine/vendor/autoload.php');
include_once('../includes/engine/framework/ck_content.class.php');
include_once('../includes/engine/framework/ck_template.class.php');

/* *** CONFIG *** */
// Include application configuration parameters
// Set the local configuration parameters - mainly for developers
if (file_exists('includes/local/configure.php')) include_once('includes/local/configure.php');

require_once('configure.php');

//require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
debug_tools::init_page();

$config = service_locator::get_config_service(service_locator::CONTEXT_ERP);

// set php_self in the local scope for compat
// deprecated - to be removed
$PHP_SELF = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);

//require_once(DIR_FS_CATALOG.'includes/engine/tools/imagesizer.class.php');
require_once('../includes/engine/tools/imagesizer.class.php');

// include the list of project filenames
require(DIR_WS_INCLUDES.'filenames.php');

// include the list of project database tables
require(DIR_WS_INCLUDES.'database_tables.php');

if (!defined('BOX_WIDTH')) define('BOX_WIDTH', 125); // how wide the boxes should be in pixels (default: 125)

// define our sticky menu item
$selectedBoxRequest = !empty($_GET['selected_box'])?$_GET['selected_box']:NULL;

if (!isset($selectedBoxRequest)) {
	$selectedBox = isset($_COOKIE['selected_box']) ? $_COOKIE['selected_box'] : null;
} else {
	$selectedBox = $selectedBoxRequest;
	setcookie('selected_box', $selectedBox);
}

if (isset($selectedBox)) {
	$selectedBox = "#{$selectedBox}";
}

// Define how do we update currency exchange rates
// Possible values are 'oanda' 'xe' or ''
if (!defined('CURRENCY_SERVER_PRIMARY')) define('CURRENCY_SERVER_PRIMARY', 'oanda');
if (!defined('CURRENCY_SERVER_BACKUP')) define('CURRENCY_SERVER_BACKUP', 'xe');

// used for money_format
setlocale(LC_MONETARY, 'en_US.utf8');

/* *** DATABASE *** */
// Zend db connection
try {
	$db = service_locator::get_db_service();
}
catch (Exception $e) {
	echo 'There was a database connection error; please try again in a few moments';
	exit();
}

// set application wide parameters
// Configuration Cache modification start
require_once('includes/configuration_cache_read.php');
// Configuration Cache modification end

// define our general functions used application-wide
require(DIR_WS_FUNCTIONS.'general.php');
require(DIR_WS_FUNCTIONS.'html_output.php');

service_locator::get_session_service()->start();

if (!empty($_REQUEST['action-level']) && $_REQUEST['action-level'] == 'global') {
	if ($_REQUEST['action'] == 'report-bug') {
		ck_bug_reporter::report();
	}
}

$_SESSION['current_context'] = 'backend';

if (!empty($_GET['ajax'])) {
	switch (@$_GET['action']) {
		case 'set-greedy-search':
			$_SESSION['greedy-search'] = $_GET['greedy-search'];
			exit();
			break;
		case 'set-quick-order-control':
			$_SESSION['quick-order-control'] = $_GET['quick-order-control'];
			exit();
			break;
	}
}

if (!isset($_SESSION['greedy-search'])) $_SESSION['greedy-search'] = 1;
if (!isset($_SESSION['quick-order-control'])) $_SESSION['quick-order-control'] = 0;

$ck_keys = new ck_keys;

if (empty($ck_keys->master_password)) $ck_keys->master_password = MASTER_PASS; //'Mahi@$24';
if (empty($ck_keys->admin_page_title)) $ck_keys->admin_page_title = 'Matrix';

if (!defined('CONTEXT')) define('CONTEXT', $config->get_env());

if (!empty($_SESSION['admin_login_id'])) {
	ini_set('display_errors', 1);
}

$__FLAG = request_flags::instance();

// set the language
if (empty($_SESSION['language']) || isset($_GET['language'])) {
	$_SESSION['language'] = 'english';
	$_SESSION['languages_id'] = 1;
}

// include the language translations
require(DIR_WS_LANGUAGES.$_SESSION['language'].'.php');
$current_page = basename($_SERVER['PHP_SELF']);
if (file_exists(DIR_WS_LANGUAGES.$_SESSION['language'].'/'.$current_page)) {
	include(DIR_WS_LANGUAGES.$_SESSION['language'].'/'.$current_page);
}

// setup our boxes
require(DIR_WS_CLASSES.'box.php');

// initialize the message stack for output messages
$messageStack = new messageStack(messageStack::CONTEXT_ADMIN);

// file uploading class
require(DIR_WS_CLASSES.'upload.php');

// calculate category path
if (isset($_GET['cPath'])) {
	$cPath = $_GET['cPath'];
}
else {
	$cPath = '';
}

$cPath_array = array();
if (tep_not_null($cPath)) {
	$cPath_array = tep_parse_category_path($cPath);
	$cPath = implode('_', $cPath_array);
	$current_category_id = $cPath_array[(sizeof($cPath_array)-1)];
}
else {
	$current_category_id = 0;
}

// check if a default currency is set
if (!defined('DEFAULT_CURRENCY')) {
	$messageStack->add(ERROR_NO_DEFAULT_CURRENCY_DEFINED, 'error');
}

// check if a default language is set
if (!defined('DEFAULT_LANGUAGE')) {
	$messageStack->add(ERROR_NO_DEFAULT_LANGUAGE_DEFINED, 'error');
}

if ((bool)ini_get('file_uploads') == false) {
	$messageStack->add(WARNING_FILE_UPLOADS_DISABLED, 'warning');
}

if (basename($_SERVER['SCRIPT_NAME']) != FILENAME_LOGIN && basename($_SERVER['SCRIPT_NAME']) != FILENAME_PASSWORD_FORGOTTEN) {
	tep_admin_check_login();
}

include('includes/application_top_faqdesk.php');

// extract permissions from db for the request
$perms = prepared_query::fetch('select * from admin where admin_id = ?', cardinality::ROW, @$_SESSION['login_id']);
$_SESSION['perms'] = $perms;

//inventory functions
require_once('../includes/functions/inventory_functions.php');

include_once(DIR_FS_CATALOG.'admin/includes/library/item_popup.class.php');

if (!empty($_SESSION['login_id'])) $_SESSION['admin_login_id'] = $_SESSION['login_id']; //setcookie($_COOKIE['osCAdminID'], $_SESSION['login_id'], NULL, '/');
unset($_SESSION['set_admin_as_user']);
