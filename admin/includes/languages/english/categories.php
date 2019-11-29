<?php
/*
 $Id: categories.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

// BOF MaxiDVD: Added For Ultimate-Images Pack!
if (!defined('TEXT_PRODUCTS_IMAGE_NOTE')) define('TEXT_PRODUCTS_IMAGE_NOTE','<b>Products Image:</b><small><br>Main Image used in <br><u>catalog & description</u> pages.<small>');
if (!defined('TEXT_PRODUCTS_IMAGE_MEDIUM')) define('TEXT_PRODUCTS_IMAGE_MEDIUM', '<b>Small Image:</b><br><small> Image on<br><u>products list</u> pages.</small>');
if (!defined('TEXT_PRODUCTS_IMAGE_LARGE')) define('TEXT_PRODUCTS_IMAGE_LARGE', '<b>Pop-up Image:</b><br><small> Large Image on<br><u>pop-up window</u> page.</small>');
if (!defined('TEXT_PRODUCTS_IMAGE_LINKED')) define('TEXT_PRODUCTS_IMAGE_LINKED', '<u>Store Product/s Sharing this Image =</u>');
if (!defined('TEXT_PRODUCTS_IMAGE_REMOVE')) define('TEXT_PRODUCTS_IMAGE_REMOVE', '<b>Remove</b> this Image from this Product?');
if (!defined('TEXT_PRODUCTS_IMAGE_DELETE')) define('TEXT_PRODUCTS_IMAGE_DELETE', '<b>Delete</b> this Image from the Server (Permanent!)?');
if (!defined('TEXT_PRODUCTS_IMAGE_REMOVE_SHORT')) define('TEXT_PRODUCTS_IMAGE_REMOVE_SHORT', 'Remove');
if (!defined('TEXT_PRODUCTS_IMAGE_DELETE_SHORT')) define('TEXT_PRODUCTS_IMAGE_DELETE_SHORT', 'Delete');
if (!defined('TEXT_PRODUCTS_IMAGE_TH_NOTICE')) define('TEXT_PRODUCTS_IMAGE_TH_NOTICE', '<b>SM = Small Images.</b> If a "SM" image is used<br>(Alone) NO Pop-up window link is created, the "SM"<br> will be placed directly under the products<br>description. If used in conjunction with an <br>"XL" image on the right, a Pop-up Window Link<br> is created and the "XL" image will be<br>shown in a Pop-up window.<br><br>');
if (!defined('TEXT_PRODUCTS_IMAGE_XL_NOTICE')) define('TEXT_PRODUCTS_IMAGE_XL_NOTICE', '<b>XL = Large Images.</b> Used for the Pop-up image<br><br><br>');
if (!defined('TEXT_PRODUCTS_IMAGE_ADDITIONAL')) define('TEXT_PRODUCTS_IMAGE_ADDITIONAL', 'Additional Images - These will appear below product description if used.');
define('TEXT_PRODUCTS_IMAGE_SM_1', 'SM Image 1:');
define('TEXT_PRODUCTS_IMAGE_XL_1', 'XL Image 1:');
define('TEXT_PRODUCTS_IMAGE_SM_2', 'SM Image 2:');
define('TEXT_PRODUCTS_IMAGE_XL_2', 'XL Image 2:');
define('TEXT_PRODUCTS_IMAGE_SM_3', 'SM Image 3:');
define('TEXT_PRODUCTS_IMAGE_XL_3', 'XL Image 3:');
define('TEXT_PRODUCTS_IMAGE_SM_4', 'SM Image 4:');
define('TEXT_PRODUCTS_IMAGE_XL_4', 'XL Image 4:');
define('TEXT_PRODUCTS_IMAGE_SM_5', 'SM Image 5:');
define('TEXT_PRODUCTS_IMAGE_XL_5', 'XL Image 5:');
define('TEXT_PRODUCTS_IMAGE_SM_6', 'SM Image 6:');
define('TEXT_PRODUCTS_IMAGE_XL_6', 'XL Image 6:');
// EOF MaxiDVD: Added For Ultimate-Images Pack!

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Categories / Products');
if (!defined('HEADING_TITLE_SEARCH')) define('HEADING_TITLE_SEARCH', 'Search:');
if (!defined('HEADING_TITLE_GOTO')) define('HEADING_TITLE_GOTO', 'Go To:');

if (!defined('TABLE_HEADING_ID')) define('TABLE_HEADING_ID', 'ID');
if (!defined('TABLE_HEADING_CATEGORIES_PRODUCTS')) define('TABLE_HEADING_CATEGORIES_PRODUCTS', 'Categories / Products');
if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');
if (!defined('TABLE_HEADING_STATUS')) define('TABLE_HEADING_STATUS', 'Status');

if (!defined('TEXT_NEW_PRODUCT')) define('TEXT_NEW_PRODUCT', 'New Product in &quot;%s&quot;');
if (!defined('TEXT_CATEGORIES')) define('TEXT_CATEGORIES', 'Categories:');
if (!defined('TEXT_SUBCATEGORIES')) define('TEXT_SUBCATEGORIES', 'Subcategories:');
if (!defined('TEXT_PRODUCTS')) define('TEXT_PRODUCTS', 'Products:');
if (!defined('TEXT_PRODUCTS_PRICE_INFO')) define('TEXT_PRODUCTS_PRICE_INFO', 'Price:');
if (!defined('TEXT_PRODUCTS_TAX_CLASS')) define('TEXT_PRODUCTS_TAX_CLASS', 'Tax Class:');
if (!defined('TEXT_PRODUCTS_AVERAGE_RATING')) define('TEXT_PRODUCTS_AVERAGE_RATING', 'Average Rating:');
if (!defined('TEXT_PRODUCTS_QUANTITY_INFO')) define('TEXT_PRODUCTS_QUANTITY_INFO', 'Quantity:');
if (!defined('TEXT_DATE_ADDED')) define('TEXT_DATE_ADDED', 'Date Added:');
if (!defined('TEXT_DELETE_IMAGE')) define('TEXT_DELETE_IMAGE', 'Delete Image');

if (!defined('TEXT_DATE_AVAILABLE')) define('TEXT_DATE_AVAILABLE', 'Date Available:');
if (!defined('TEXT_LAST_MODIFIED')) define('TEXT_LAST_MODIFIED', 'Last Modified:');
if (!defined('TEXT_IMAGE_NONEXISTENT')) define('TEXT_IMAGE_NONEXISTENT', 'IMAGE DOES NOT EXIST');
if (!defined('TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS')) define('TEXT_NO_CHILD_CATEGORIES_OR_PRODUCTS', 'Please insert a new category or product in this level.');
if (!defined('TEXT_PRODUCT_MORE_INFORMATION')) define('TEXT_PRODUCT_MORE_INFORMATION', 'For more information, please visit this products <a href="http://%s" target="blank"><u>webpage</u></a>.');
if (!defined('TEXT_PRODUCT_DATE_ADDED')) define('TEXT_PRODUCT_DATE_ADDED', 'This product was added to our catalog on %s.');
if (!defined('TEXT_PRODUCT_DATE_AVAILABLE')) define('TEXT_PRODUCT_DATE_AVAILABLE', 'This product will be in stock on %s.');

if (!defined('TEXT_EDIT_INTRO')) define('TEXT_EDIT_INTRO', 'Please make any necessary changes');
if (!defined('TEXT_EDIT_CATEGORIES_ID')) define('TEXT_EDIT_CATEGORIES_ID', 'Category ID:');
if (!defined('TEXT_EDIT_CATEGORIES_NAME')) define('TEXT_EDIT_CATEGORIES_NAME', 'Category Name:');
if (!defined('TEXT_EDIT_CATEGORIES_IMAGE')) define('TEXT_EDIT_CATEGORIES_IMAGE', 'Category Image:');
if (!defined('TEXT_EDIT_SORT_ORDER')) define('TEXT_EDIT_SORT_ORDER', 'Sort Order:');
if (!defined('TEXT_EDIT_CATEGORIES_HEADING_TITLE')) define('TEXT_EDIT_CATEGORIES_HEADING_TITLE', 'Category heading title:');
if (!defined('TEXT_EDIT_CATEGORIES_DESCRIPTION')) define('TEXT_EDIT_CATEGORIES_DESCRIPTION', 'Category heading Description:');

# 121 JFM 2009/08/12 #
if (!defined('TEXT_EDIT_EBAY_CATEGORIES')) define('TEXT_EDIT_EBAY_CATEGORIES', 'eBay Category Mapping:');
if (!defined('TEXT_EDIT_EBAY_SHOP_CATEGORIES')) define('TEXT_EDIT_EBAY_SHOP_CATEGORIES', 'eBay Shop Category Mapping:');
# 121 #

if (!defined('TEXT_EDIT_CATEGORIES_TITLE_TAG')) define('TEXT_EDIT_CATEGORIES_TITLE_TAG', 'Category Title Meta Tag :');
if (!defined('TEXT_EDIT_CATEGORIES_DESC_TAG')) define('TEXT_EDIT_CATEGORIES_DESC_TAG', 'Category Description Meta Tag :');
if (!defined('TEXT_EDIT_CATEGORIES_KEYWORDS_TAG')) define('TEXT_EDIT_CATEGORIES_KEYWORDS_TAG', 'Category Key Word Meta Tag:');



if (!defined('TEXT_INFO_COPY_TO_INTRO')) define('TEXT_INFO_COPY_TO_INTRO', 'Please choose a new category you wish to copy this product to');
if (!defined('TEXT_INFO_CURRENT_CATEGORIES')) define('TEXT_INFO_CURRENT_CATEGORIES', 'Current Categories:');

if (!defined('TEXT_INFO_HEADING_NEW_CATEGORY')) define('TEXT_INFO_HEADING_NEW_CATEGORY', 'New Category');
if (!defined('TEXT_INFO_HEADING_EDIT_CATEGORY')) define('TEXT_INFO_HEADING_EDIT_CATEGORY', 'Edit Category');
if (!defined('TEXT_INFO_HEADING_DELETE_CATEGORY')) define('TEXT_INFO_HEADING_DELETE_CATEGORY', 'Delete Category');
if (!defined('TEXT_INFO_HEADING_MOVE_CATEGORY')) define('TEXT_INFO_HEADING_MOVE_CATEGORY', 'Move Category');
if (!defined('TEXT_INFO_HEADING_DELETE_PRODUCT')) define('TEXT_INFO_HEADING_DELETE_PRODUCT', 'Delete Product');
if (!defined('TEXT_INFO_HEADING_MOVE_PRODUCT')) define('TEXT_INFO_HEADING_MOVE_PRODUCT', 'Move Product');
if (!defined('TEXT_INFO_HEADING_COPY_TO')) define('TEXT_INFO_HEADING_COPY_TO', 'Copy To');

if (!defined('TEXT_DELETE_CATEGORY_INTRO')) define('TEXT_DELETE_CATEGORY_INTRO', 'Are you sure you want to delete this category?');
if (!defined('TEXT_DELETE_PRODUCT_INTRO')) define('TEXT_DELETE_PRODUCT_INTRO', 'Are you sure you want to permanently delete this product?');

if (!defined('TEXT_DELETE_WARNING_CHILDS')) define('TEXT_DELETE_WARNING_CHILDS', '<b>WARNING:</b> There are %s (child-)categories still linked to this category!');
if (!defined('TEXT_DELETE_WARNING_PRODUCTS')) define('TEXT_DELETE_WARNING_PRODUCTS', '<b>WARNING:</b> There are %s products still linked to this category!');

if (!defined('TEXT_MOVE_PRODUCTS_INTRO')) define('TEXT_MOVE_PRODUCTS_INTRO', 'Please select which category you wish <b>%s</b> to reside in');
if (!defined('TEXT_MOVE_CATEGORIES_INTRO')) define('TEXT_MOVE_CATEGORIES_INTRO', 'Please select which category you wish <b>%s</b> to reside in');
if (!defined('TEXT_MOVE')) define('TEXT_MOVE', 'Move <b>%s</b> to:');

if (!defined('TEXT_NEW_CATEGORY_INTRO')) define('TEXT_NEW_CATEGORY_INTRO', 'Please fill out the following information for the new category');
if (!defined('TEXT_CATEGORIES_NAME')) define('TEXT_CATEGORIES_NAME', 'Category Name:');
if (!defined('TEXT_CATEGORIES_IMAGE')) define('TEXT_CATEGORIES_IMAGE', 'Category Image:');
if (!defined('TEXT_SORT_ORDER')) define('TEXT_SORT_ORDER', 'Sort Order:');

if (!defined('TEXT_PRODUCTS_STATUS')) define('TEXT_PRODUCTS_STATUS', 'Products Status:');
if (!defined('TEXT_PRODUCTS_DATE_AVAILABLE')) define('TEXT_PRODUCTS_DATE_AVAILABLE', 'Date Available:');
if (!defined('TEXT_PRODUCT_AVAILABLE')) define('TEXT_PRODUCT_AVAILABLE', 'In Stock');
if (!defined('TEXT_PRODUCT_NOT_AVAILABLE')) define('TEXT_PRODUCT_NOT_AVAILABLE', 'Out of Stock');
if (!defined('TEXT_PRODUCTS_MANUFACTURER')) define('TEXT_PRODUCTS_MANUFACTURER', 'Products Manufacturer:');
if (!defined('TEXT_PRODUCTS_NAME')) define('TEXT_PRODUCTS_NAME', 'Products Name:');
if (!defined('TEXT_PRODUCTS_DESCRIPTION')) define('TEXT_PRODUCTS_DESCRIPTION', 'Products Description:');
if (!defined('TEXT_PRODUCTS_QUANTITY')) define('TEXT_PRODUCTS_QUANTITY', 'Products Quantity:');
if (!defined('TEXT_PRODUCTS_MODEL')) define('TEXT_PRODUCTS_MODEL', 'Products Model:');
if (!defined('TEXT_PRODUCTS_IMAGE')) define('TEXT_PRODUCTS_IMAGE', 'Products Image:');
if (!defined('TEXT_PRODUCTS_URL')) define('TEXT_PRODUCTS_URL', 'Products URL:');
if (!defined('TEXT_PRODUCTS_URL_WITHOUT_HTTP')) define('TEXT_PRODUCTS_URL_WITHOUT_HTTP', '<small>(without http://)</small>');
if (!defined('TEXT_PRODUCTS_PRICE_NET')) define('TEXT_PRODUCTS_PRICE_NET', 'Products Price (Net):');
if (!defined('TEXT_PRODUCTS_PRICE_GROSS')) define('TEXT_PRODUCTS_PRICE_GROSS', 'Products Price (Gross):');
if (!defined('TEXT_PRODUCTS_WEIGHT')) define('TEXT_PRODUCTS_WEIGHT', 'Products Weight:');
if (!defined('TEXT_NONE')) define('TEXT_NONE', '--none--');

if (!defined('EMPTY_CATEGORY')) define('EMPTY_CATEGORY', 'Empty Category');

if (!defined('TEXT_HOW_TO_COPY')) define('TEXT_HOW_TO_COPY', 'Copy Method:');
if (!defined('TEXT_COPY_AS_LINK')) define('TEXT_COPY_AS_LINK', 'Link product');
if (!defined('TEXT_COPY_AS_DUPLICATE')) define('TEXT_COPY_AS_DUPLICATE', 'Duplicate product');

if (!defined('ERROR_CANNOT_LINK_TO_SAME_CATEGORY')) define('ERROR_CANNOT_LINK_TO_SAME_CATEGORY', 'Error: Can not link products in the same category.');
if (!defined('ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE')) define('ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE', 'Error: Catalog images directory is not writeable: '.DIR_FS_CATALOG_IMAGES);
if (!defined('ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST')) define('ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST', 'Error: Catalog images directory does not exist: '.DIR_FS_CATALOG_IMAGES);
if (!defined('ERROR_CANNOT_MOVE_CATEGORY_TO_PARENT')) define('ERROR_CANNOT_MOVE_CATEGORY_TO_PARENT', 'Error: Category cannot be moved into child category.');

//Header Tags Controller Admin
if (!defined('TEXT_PRODUCT_METTA_INFO')) define('TEXT_PRODUCT_METTA_INFO', '<b>Meta Tag Information</b>');
if (!defined('TEXT_PRODUCTS_PAGE_TITLE')) define('TEXT_PRODUCTS_PAGE_TITLE', 'Products Page Title:');
if (!defined('TEXT_PRODUCTS_HEADER_DESCRIPTION')) define('TEXT_PRODUCTS_HEADER_DESCRIPTION', 'Page Header Description:');
if (!defined('TEXT_PRODUCTS_KEYWORDS')) define('TEXT_PRODUCTS_KEYWORDS', 'Product Keywords:');
if (!defined('TEXT_PRODUCTS_STOCK')) define('TEXT_PRODUCTS_STOCK', 'Parent Stock Item:');

if (!defined('TEXT_PARENT_NOTICE')) define('TEXT_PARENT_NOTICE', ' <b>N.B. This field will be Ignored if this product has a parent.</b>');


?>
