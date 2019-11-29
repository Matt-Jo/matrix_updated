<?php
//Admin begin
if (!defined('TABLE_ADMIN')) define('TABLE_ADMIN', 'admin');
if (!defined('TABLE_ADMIN_FILES')) define('TABLE_ADMIN_FILES', 'admin_files');
if (!defined('TABLE_ADMIN_GROUPS')) define('TABLE_ADMIN_GROUPS', 'admin_groups');
//Admin end

// define the database table names used in the project
if (!defined('TABLE_ADDRESS_BOOK')) define('TABLE_ADDRESS_BOOK', 'address_book');
if (!defined('TABLE_ADDRESS_FORMAT')) define('TABLE_ADDRESS_FORMAT', 'address_format');
if (!defined('TABLE_CATEGORIES')) define('TABLE_CATEGORIES', 'categories');
if (!defined('TABLE_CATEGORIES_DESCRIPTION')) define('TABLE_CATEGORIES_DESCRIPTION', 'categories_description');
if (!defined('TABLE_CONFIGURATION')) define('TABLE_CONFIGURATION', 'configuration');
if (!defined('TABLE_CONFIGURATION_GROUP')) define('TABLE_CONFIGURATION_GROUP', 'configuration_group');
if (!defined('TABLE_COUNTRIES')) define('TABLE_COUNTRIES', 'countries');
if (!defined('TABLE_CUSTOMERS')) define('TABLE_CUSTOMERS', 'customers');
if (!defined('TABLE_LANGUAGES')) define('TABLE_LANGUAGES', 'languages');
if (!defined('TABLE_MANUFACTURERS')) define('TABLE_MANUFACTURERS', 'manufacturers');
if (!defined('TABLE_MANUFACTURERS_INFO')) define('TABLE_MANUFACTURERS_INFO', 'manufacturers_info');
if (!defined('TABLE_NEWSLETTERS')) define('TABLE_NEWSLETTERS', 'newsletters');
if (!defined('TABLE_ORDERS')) define('TABLE_ORDERS', 'orders');
if (!defined('TABLE_ORDERS_SHIP_METHODS')) define('TABLE_ORDERS_SHIP_METHODS', 'orders_ship_methods');
if (!defined('TABLE_ORDERS_PRODUCTS')) define('TABLE_ORDERS_PRODUCTS', 'orders_products');
if (!defined('TABLE_ORDERS_PRODUCTS_ATTRIBUTES')) define('TABLE_ORDERS_PRODUCTS_ATTRIBUTES', 'orders_products_attributes');
if (!defined('TABLE_ORDERS_PRODUCTS_DOWNLOAD')) define('TABLE_ORDERS_PRODUCTS_DOWNLOAD', 'orders_products_download');
if (!defined('TABLE_ORDERS_STATUS')) define('TABLE_ORDERS_STATUS', 'orders_status');
if (!defined('TABLE_ORDERS_STATUS_HISTORY')) define('TABLE_ORDERS_STATUS_HISTORY', 'orders_status_history');
if (!defined('TABLE_ORDERS_TOTAL')) define('TABLE_ORDERS_TOTAL', 'orders_total');
if (!defined('TABLE_PRODUCTS')) define('TABLE_PRODUCTS', 'products');
if (!defined('TABLE_PRODUCTS_ATTRIBUTES')) define('TABLE_PRODUCTS_ATTRIBUTES', 'products_attributes');
if (!defined('TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD')) define('TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD', 'products_attributes_download');
if (!defined('TABLE_PRODUCTS_XSELL')) define('TABLE_PRODUCTS_XSELL', 'products_xsell');
if (!defined('TABLE_TAX_CLASS')) define('TABLE_TAX_CLASS', 'tax_class');
if (!defined('TABLE_GEO_ZONES')) define('TABLE_GEO_ZONES', 'geo_zones');
if (!defined('TABLE_ZONES_TO_GEO_ZONES')) define('TABLE_ZONES_TO_GEO_ZONES', 'zones_to_geo_zones');
if (!defined('TABLE_ZONES')) define('TABLE_ZONES', 'zones');

if (!defined('TABLE_PRODUCTS_STOCK_CONTROL')) define('TABLE_PRODUCTS_STOCK_CONTROL', 'products_stock_control');
if (!defined('TABLE_SERIALS')) define('TABLE_SERIALS', 'serials');
if (!defined('TABLE_SERIALS_STATUS')) define('TABLE_SERIALS_STATUS', 'serials_status');

if (!defined('TABLE_ADDRESS_BOOK_VENDORS')) define('TABLE_ADDRESS_BOOK_VENDORS', 'address_book_vendors');

if (!defined('TABLE_VENDORS')) define('TABLE_VENDORS', 'vendors');

if (!defined('TABLE_VENDORS_INFO')) define('TABLE_VENDORS_INFO', 'vendors_info');

if (!defined('TABLE_ORDERS_NOTE')) define('TABLE_ORDERS_NOTE','orders_notes');

?>
