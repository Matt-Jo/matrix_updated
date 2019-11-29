<?php
/*
 $Id: manufacturers.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Manufacturers');

if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');

if (!defined('TEXT_HEADING_NEW_MANUFACTURER')) define('TEXT_HEADING_NEW_MANUFACTURER', 'New Manufacturer');
if (!defined('TEXT_HEADING_EDIT_MANUFACTURER')) define('TEXT_HEADING_EDIT_MANUFACTURER', 'Edit Manufacturer');
if (!defined('TEXT_HEADING_DELETE_MANUFACTURER')) define('TEXT_HEADING_DELETE_MANUFACTURER', 'Delete Manufacturer');

if (!defined('TEXT_MANUFACTURERS')) define('TEXT_MANUFACTURERS', 'Manufacturers:');
if (!defined('TEXT_DATE_ADDED')) define('TEXT_DATE_ADDED', 'Date Added:');
if (!defined('TEXT_LAST_MODIFIED')) define('TEXT_LAST_MODIFIED', 'Last Modified:');
if (!defined('TEXT_PRODUCTS')) define('TEXT_PRODUCTS', 'Products:');
if (!defined('TEXT_IMAGE_NONEXISTENT')) define('TEXT_IMAGE_NONEXISTENT', 'IMAGE DOES NOT EXIST');

if (!defined('TEXT_NEW_INTRO')) define('TEXT_NEW_INTRO', 'Please fill out the following information for the new manufacturer');
if (!defined('TEXT_EDIT_INTRO')) define('TEXT_EDIT_INTRO', 'Please make any necessary changes');

if (!defined('TEXT_MANUFACTURERS_NAME')) define('TEXT_MANUFACTURERS_NAME', 'Manufacturers Name:');
if (!defined('TEXT_MANUFACTURERS_IMAGE')) define('TEXT_MANUFACTURERS_IMAGE', 'Manufacturers Image:');
if (!defined('TEXT_MANUFACTURERS_URL')) define('TEXT_MANUFACTURERS_URL', 'Manufacturers URL:');

if (!defined('TEXT_DELETE_INTRO')) define('TEXT_DELETE_INTRO', 'Are you sure you want to delete this manufacturer?');
if (!defined('TEXT_DELETE_IMAGE')) define('TEXT_DELETE_IMAGE', 'Delete manufacturers image?');
if (!defined('TEXT_DELETE_PRODUCTS')) define('TEXT_DELETE_PRODUCTS', 'Delete products from this manufacturer? (including product reviews, products on special, upcoming products)');
if (!defined('TEXT_DELETE_WARNING_PRODUCTS')) define('TEXT_DELETE_WARNING_PRODUCTS', '<b>WARNING:</b> There are %s products still linked to this manufacturer!');

if (!defined('ERROR_DIRECTORY_NOT_WRITEABLE')) define('ERROR_DIRECTORY_NOT_WRITEABLE', 'Error: I can not write to this directory. Please set the right user permissions on: %s');
if (!defined('ERROR_DIRECTORY_DOES_NOT_EXIST')) define('ERROR_DIRECTORY_DOES_NOT_EXIST', 'Error: Directory does not exist: %s');
?>
