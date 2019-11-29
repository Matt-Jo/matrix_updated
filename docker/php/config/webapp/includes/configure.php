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
define('PRIVATE_FQDN', getenv("APACHE_SUBDOMAIN").'.cablesandkits.com');
define('FQDN', getenv("APACHE_SUBDOMAIN").'.cablesandkits.com');
define('ADMIN_NOTICE_EMAIL', getenv("ADMIN_NOTICE_EMAIL"));
define('HTTP_SERVER', '//'.FQDN); // eg, http://localhost - should not be empty for productive servers
define('HTTPS_SERVER', '//'.FQDN); // eg, https://localhost - should not be empty for productive servers
define('ENABLE_SSL', true); // secure webserver for checkout procedure?
define('HTTP_COOKIE_DOMAIN', FQDN);
define('HTTPS_COOKIE_DOMAIN', FQDN);
define('HTTP_COOKIE_PATH', '/');
define('HTTPS_COOKIE_PATH', '/');
define('DIR_WS_HTTP_CATALOG', '/');
define('DIR_WS_HTTPS_CATALOG', '/');
define('DIR_WS_IMAGES', '//media.cablesandkits.com/');
define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
define('DIR_WS_INCLUDES', 'includes/');
define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');

//Added for BTS1.0
define('DIR_WS_TEMPLATES', 'templates/');
define('DIR_WS_CONTENT', DIR_WS_TEMPLATES . 'content/');
define('DIR_WS_JAVASCRIPT', DIR_WS_INCLUDES . 'javascript/');
//End BTS1.0
define('DIR_WS_DOWNLOAD_PUBLIC', 'pub/');
define('DIR_FS_CATALOG', getenv("PHP_APP_DIR")."/");
define('DIR_FS_DOWNLOAD', DIR_FS_CATALOG . 'download/');
define('DIR_FS_DOWNLOAD_PUBLIC', DIR_FS_CATALOG . 'pub/');

define('DIR_FS_ADMIN', getenv("PHP_APP_DIR").'/admin/');// absolute path required
define('EMAIL_INVOICE_DIR', 'html_emails/');
define('INVOICE_TEMPLATE_DIR', 'templates/');

// define our database connection
define('DB_SERVER', getenv("MYSQL_HOSTNAME")); //23.253.247.193'); // eg, localhost - should not be empty for productive servers
define('DB_SERVER_USERNAME', getenv("MYSQL_USER"));
define('DB_SERVER_PASSWORD', getenv("MYSQL_PASSWORD"));
define('DB_DATABASE', getenv("MYSQL_DATABASE"));
//define('DB_DATABASE', 'reports');
define('USE_PCONNECT', 'false'); // use persistent connections?
define('STORE_SESSIONS', 'mysql'); // leave empty '' for default handler or set to 'mysql'

// Payments internal API
define('PAYMENTS_API_IP', getenv("PAYMENTS_API_IP"));
define('PAYMENTS_API_PORT', getenv("PAYMENTS_API_PORT"));

//define('SUGAR_LOCATION',        'sugar.dfwtek.com');
//define('SUGAR_PASSWORD',        'qwerty');
//define('SUGAR_ADMIN',           'admin');

//define('SUGAR_DB',		'sugar.dfwtek.com');
//define('SUGAR_DB_USERNAME',	'dev');
//define('SUGAR_DB_PASSWORD',	'347Str0k3r');
//define('SUGAR_DATABASE',	'sugarcrm');

$service_environment = [
	'gtm' => [
		'account_id' => 'GTM-543XQD9'
	],

	'ups' => [
		'credentials' => [
			'access_license_number' => '4C7AA73596217470',
			'user_id' => 'cnkdeveloper',
			'password' => 'c@bl3snK1t',
		],
		/*'server' => [
			'name' => 'Production Environment',
			'endpoint' => '',
		],*/
		'server' => [
			'name' => 'Customer Integration Environment',
			'endpoint' => '',
		]
	],

	'fedex' => [
	],

	'salesforce' => [
	]
];
?>
