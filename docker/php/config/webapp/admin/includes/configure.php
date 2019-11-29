<?php
/*
osCommerce, Open Source E-Commerce Solutions
http://www.oscommerce.com

Copyright (c) 2003 osCommerce

Released under the GNU General Public License
*/

// Define the webserver and path parameters
// * DIR_FS_* = Filesystem directories (local/physical)
// * DIR_WS_* = Webserver directories (virtual/URL)
define('PRODUCTION_FQDN', getenv("APACHE_SUBDOMAIN").'.cablesandkits.com');
if (!defined('FQDN')) define('FQDN', getenv("APACHE_SUBDOMAIN").'.cablesandkits.com');
define('PRIVATE_FQDN', getenv("APACHE_SUBDOMAIN").'.cablesandkits.com');
if (!defined('HTTP_SERVER')) define('HTTP_SERVER', '//'.FQDN); // eg, http://localhost - should not be empty for productive servers
if (!defined('HTTPS_SERVER')) define('HTTPS_SERVER', '//'.FQDN); // eg, https://localhost - should not be empty for productive servers
if (!defined('HTTP_CATALOG_SERVER')) define('HTTP_CATALOG_SERVER', '//'.FQDN);
if (!defined('HTTPS_CATALOG_SERVER')) define('HTTPS_CATALOG_SERVER', '//'.FQDN);
if (!defined('ENABLE_SSL')) define('ENABLE_SSL', true); // secure webserver for checkout procedure?
if (!defined('ENABLE_SSL_CATALOG')) define('ENABLE_SSL_CATALOG', 'true'); // secure webserver for catalog module
if (!defined('DIR_FS_DOCUMENT_ROOT')) define('DIR_FS_DOCUMENT_ROOT', getenv("PHP_APP_DIR")."/"); // where the pages are located on the server
if (!defined('DIR_WS_ADMIN')) define('DIR_WS_ADMIN', '/admin/'); // absolute path required
if (!defined('DIR_FS_ADMIN')) define('DIR_FS_ADMIN', getenv("PHP_APP_DIR").'/admin/'); // absolute path required
if (!defined('DIR_WS_CATALOG')) define('DIR_WS_CATALOG', '/'); // absolute path required
if (!defined('DIR_FS_CATALOG')) define('DIR_FS_CATALOG', getenv("PHP_APP_DIR")."/"); // absolute path required
if (!defined('DIR_WS_IMAGES')) define('DIR_WS_IMAGES', 'images/');
if (!defined('DIR_WS_ICONS')) define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
if (!defined('DIR_WS_CATALOG_IMAGES')) define('DIR_WS_CATALOG_IMAGES', DIR_WS_CATALOG . 'images/');
if (!defined('DIR_WS_INCLUDES')) define('DIR_WS_INCLUDES', 'includes/');
if (!defined('DIR_WS_BOXES')) define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
if (!defined('DIR_WS_FUNCTIONS')) define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
if (!defined('DIR_WS_CLASSES')) define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
if (!defined('DIR_WS_MODULES')) define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
if (!defined('DIR_WS_LANGUAGES')) define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');
if (!defined('DIR_WS_CATALOG_LANGUAGES')) define('DIR_WS_CATALOG_LANGUAGES', DIR_WS_CATALOG . 'includes/languages/');
if (!defined('DIR_FS_CATALOG_LANGUAGES')) define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG . 'includes/languages/');
if (!defined('DIR_FS_CATALOG_IMAGES')) define('DIR_FS_CATALOG_IMAGES', DIR_FS_CATALOG . 'images/');
if (!defined('DIR_FS_CATALOG_MODULES')) define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG . 'includes/modules/');
if (!defined('DIR_FS_BACKUP')) define('DIR_FS_BACKUP', DIR_FS_ADMIN . 'backups/');

// Added for Templating
if (!defined('DIR_FS_CATALOG_MAINPAGE_MODULES')) define('DIR_FS_CATALOG_MAINPAGE_MODULES', DIR_FS_CATALOG_MODULES . 'mainpage_modules/');
if (!defined('DIR_WS_TEMPLATES')) define('DIR_WS_TEMPLATES', DIR_WS_CATALOG . 'templates/');
if (!defined('DIR_FS_TEMPLATES')) define('DIR_FS_TEMPLATES', DIR_FS_CATALOG . 'templates/');

if (!defined('EMAIL_INVOICE_DIR')) define('EMAIL_INVOICE_DIR', 'html_emails/');
if (!defined('INVOICE_TEMPLATE_DIR')) define('INVOICE_TEMPLATE_DIR', 'templates/');

// define our database connection
if (!defined('DB_SERVER')) define('DB_SERVER', getenv("MYSQL_HOSTNAME")); //23.253.247.193'); // eg, localhost - should not be empty for productive servers
if (!defined('DB_SERVER_USERNAME')) define('DB_SERVER_USERNAME', getenv("MYSQL_USER"));
if (!defined('DB_SERVER_PASSWORD')) define('DB_SERVER_PASSWORD', getenv("MYSQL_PASSWORD"));
if (!defined('DB_DATABASE')) define('DB_DATABASE', getenv("MYSQL_DATABASE"));
//if (!defined('DB_DATABASE')) define('DB_DATABASE', 'reports');
if (!defined('USE_PCONNECT')) define('USE_PCONNECT', 'false'); // use persisstent connections?
if (!defined('STORE_SESSIONS')) define('STORE_SESSIONS', 'mysql'); // leave empty '' for default handler or set to 'mysql'

// fedex
if (!defined('DIR_WS_FEDEX_LABELS')) define('DIR_WS_FEDEX_LABELS', DIR_WS_IMAGES . 'fedex/');
// fedex eof

define('SUGAR_LOCATION',        'sugar.dfwtek.com');
define('SUGAR_PASSWORD',        'qwerty');
define('SUGAR_ADMIN',           'admin');

define('SUGAR_DB',		'sugar.dfwtek.com');
define('SUGAR_DB_USERNAME',	'dev');
define('SUGAR_DB_PASSWORD',	'347Str0k3r');
define('SUGAR_DATABASE',	'sugarcrm');


