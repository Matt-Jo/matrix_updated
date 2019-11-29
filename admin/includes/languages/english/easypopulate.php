<?php
/*
 $Id: easypopulate.php,v 1.4 2004/09/21 zip1 Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 20042 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Easy Populate Configuration');
if (!defined('EASY_VERSION_A')) define('EASY_VERSION_A', 'Easy Populate Advanced ');
if (!defined('EASY_VERSION_B')) define('EASY_VERSION_B', 'Easy Populate Basic ');
if (!defined('EASY_DEFAULT_LANGUAGE')) define('EASY_DEFAULT_LANGUAGE', ' - Default Language ');
if (!defined('EASY_UPLOAD_FILE')) define('EASY_UPLOAD_FILE', 'File uploaded. ');
if (!defined('EASY_UPLOAD_TEMP')) define('EASY_UPLOAD_TEMP', 'Temporary filename: ');
if (!defined('EASY_UPLOAD_USER_FILE')) define('EASY_UPLOAD_USER_FILE', 'User filename: ');
if (!defined('EASY_SIZE')) define('EASY_SIZE', 'Size: ');
if (!defined('EASY_FILENAME')) define('EASY_FILENAME', 'Filename: ');
if (!defined('EASY_SPLIT_DOWN')) define('EASY_SPLIT_DOWN', 'You can download your split files in the Tools/Files under /temp/');
if (!defined('EASY_UPLOAD_EP_FILE')) define('EASY_UPLOAD_EP_FILE', 'Upload EP File for Import');
if (!defined('EASY_SPLIT_EP_FILE')) define('EASY_SPLIT_EP_FILE', 'Upload and Split a EP File');

if (!defined('TEXT_IMPORT_TEMP')) define('TEXT_IMPORT_TEMP', 'Import Data from file in %s');
if (!defined('TEXT_INSERT_INTO_DB')) define('TEXT_INSERT_INTO_DB', 'Insert into DB');
if (!defined('TEXT_SELECT_ONE')) define('TEXT_SELECT_ONE', 'Select a EP File for Import');
if (!defined('TEXT_SPLIT_FILE')) define('TEXT_SPLIT_FILE', 'Select a EP File');
if (!defined('EASY_LABEL_CREATE')) define('EASY_LABEL_CREATE', 'Create an export file');
if (!defined('EASY_LABEL_CREATE_SELECT')) define('EASY_LABEL_CREATE_SELECT', 'Select method to save export file');
if (!defined('EASY_LABEL_CREATE_SAVE')) define('EASY_LABEL_CREATE_SAVE', 'Save to temp file on server');
if (!defined('EASY_LABEL_SELECT_DOWN')) define('EASY_LABEL_SELECT_DOWN', 'Select field set to download');
if (!defined('EASY_LABEL_SORT')) define('EASY_LABEL_SORT', 'Select field for sort order');
if (!defined('EASY_LABEL_PRODUCT_RANGE')) define('EASY_LABEL_PRODUCT_RANGE', 'Limit by Products_ID(s)');
if (!defined('EASY_LABEL_LIMIT_CAT')) define('EASY_LABEL_LIMIT_CAT', 'Limit By Category');
if (!defined('EASY_LABEL_LIMIT_MAN')) define('EASY_LABEL_LIMIT_MAN', 'Limit By Manufacturer');

if (!defined('EASY_LABEL_PRODUCT_AVAIL')) define('EASY_LABEL_PRODUCT_AVAIL', 'Range Available: ');
if (!defined('EASY_LABEL_PRODUCT_TO')) define('EASY_LABEL_PRODUCT_TO', ' to ');
if (!defined('EASY_LABEL_PRODUCT_RECORDS')) define('EASY_LABEL_PRODUCT_RECORDS', '	Total number of records: ');
if (!defined('EASY_LABEL_PRODUCT_BEGIN')) define('EASY_LABEL_PRODUCT_BEGIN', 'begin: ');
if (!defined('EASY_LABEL_PRODUCT_END')) define('EASY_LABEL_PRODUCT_END', 'end: ');
if (!defined('EASY_LABEL_PRODUCT_START')) define('EASY_LABEL_PRODUCT_START', 'Start File Creation ');

if (!defined('EASY_FILE_LOCATE')) define('EASY_FILE_LOCATE', 'You can get your file in the Tools/Files under ');
define('EASY_FILE_LOCATE_2', ' by clicking this Link and going to the file manager');
if (!defined('EASY_FILE_RETURN')) define('EASY_FILE_RETURN', ' You can return to EP by clicking this link.');
if (!defined('EASY_IMPORT_TEMP_DIR')) define('EASY_IMPORT_TEMP_DIR', 'Import from Temp Dir ');
if (!defined('EASY_LABEL_DOWNLOAD')) define('EASY_LABEL_DOWNLOAD', 'Download');
if (!defined('EASY_LABEL_COMPLETE')) define('EASY_LABEL_COMPLETE', 'Complete');
if (!defined('EASY_LABEL_TAB')) define('EASY_LABEL_TAB', 'tab-delimited .txt file to edit');
if (!defined('EASY_LABEL_MPQ')) define('EASY_LABEL_MPQ', 'Model/Price/Qty');
if (!defined('EASY_LABEL_EP_MC')) define('EASY_LABEL_EP_MC', 'Model/Category');
if (!defined('EASY_LABEL_EP_FROGGLE')) define('EASY_LABEL_EP_FROGGLE', 'Froogle');
if (!defined('EASY_LABEL_EP_ATTRIB')) define('EASY_LABEL_EP_ATTRIB', 'Attributes');
if (!defined('EASY_LABEL_NONE')) define('EASY_LABEL_NONE', 'None');
if (!defined('EASY_LABEL_CATEGORY')) define('EASY_LABEL_CATEGORY', '1st Category Name');
if (!defined('PULL_DOWN_MANUFACTURES')) define('PULL_DOWN_MANUFACTURES', 'Manufacturers');
if (!defined('EASY_LABEL_PRODUCT')) define('EASY_LABEL_PRODUCT', 'Product ID Number');
if (!defined('EASY_LABEL_MANUFACTURE')) define('EASY_LABEL_MANUFACTURE', 'Manufacturer ID Number');
if (!defined('EASY_LABEL_EP_FROGGLE_HEADER')) define('EASY_LABEL_EP_FROGGLE_HEADER', 'Download a EP or Froogle file');
if (!defined('EASY_LABEL_EP_MA')) define('EASY_LABEL_EP_MA', 'Model/Attributes');
if (!defined('EASY_LABEL_EP_FR_TITLE')) define('EASY_LABEL_EP_FR_TITLE', 'Create EP or Froogle Files in Temp Dir ');
if (!defined('EASY_LABEL_EP_DOWN_TAB')) define('EASY_LABEL_EP_DOWN_TAB', 'Create <b>Complete</b> tab-delimited .txt file in temp dir');
if (!defined('EASY_LABEL_EP_DOWN_MPQ')) define('EASY_LABEL_EP_DOWN_MPQ', 'Create <b>Model/Price/Qty</b> tab-delimited .txt file in temp dir');
if (!defined('EASY_LABEL_EP_DOWN_MC')) define('EASY_LABEL_EP_DOWN_MC', 'Create <b>Model/Category</b> tab-delimited .txt file in temp dir');
if (!defined('EASY_LABEL_EP_DOWN_MA')) define('EASY_LABEL_EP_DOWN_MA', 'Create <b>Model/Attributes</b> tab-delimited .txt file in temp dir');
if (!defined('EASY_LABEL_EP_DOWN_FROOGLE')) define('EASY_LABEL_EP_DOWN_FROOGLE', 'Create <b>Froogle</b> tab-delimited .txt file in temp dir');

if (!defined('EASY_LABEL_NEW_PRODUCT')) define('EASY_LABEL_NEW_PRODUCT', '!New Product!</font><br>');
if (!defined('EASY_LABEL_UPDATED')) define('EASY_LABEL_UPDATED', "<font color='black'> Updated</font><br>");
define('EASY_LABEL_DELETE_STATUS_1', "<font color='red'> !!Deleting product ");
define('EASY_LABEL_DELETE_STATUS_2', " from the database !!</font><br>");
define('EASY_LABEL_LINE_COUNT_1', 'Added ');
define('EASY_LABEL_LINE_COUNT_2', 'records and closing file... ');
define('EASY_LABEL_FILE_COUNT_1A', 'Creating file EPA_Split ');
define('EASY_LABEL_FILE_COUNT_1B', 'Creating file EPB_Split ');
define('EASY_LABEL_FILE_COUNT_2', '.txt ... ');
define('EASY_LABEL_FILE_CLOSE_1', 'Added ');
define('EASY_LABEL_FILE_CLOSE_2', ' records and closing file...');
//errormessages
define('EASY_ERROR_1', 'Strange but there is no default language to work... That may not happen, just in case... ');
define('EASY_ERROR_2', '... ERROR! - Too many characters in the model number.<br>
			25 is the maximum on a standard cre install.<br>
			Your maximum product_model length is set to ');
define('EASY_ERROR_2A', ' <br>You can either shorten your model numbers or increase the size of the field in the database.</font>');
define('EASY_ERROR_2B', "<font color='red'>");
define('EASY_ERROR_3', '<p class=smallText>No products_id field in record. This line was not imported <br><br>');
define('EASY_ERROR_4', '<font color=red>ERROR - v_customer_group_id and v_customer_price must occur in pairs</font>');
define('EASY_ERROR_5', '</b><font color=red>ERROR - You are trying to use a file created with EP Advanced, please try with Easy Populate Advanced </font>');
define('EASY_ERROR_5a', '<font color=red><b><u> Click here to return to Easy Populate Basic </u></b></font>');
define('EASY_ERROR_6', '</b><font color=red>ERROR - You are trying to use a file created with EP Basic, please try with Easy Populate Basic </font>');
define('EASY_ERROR_6a', '<font color=red><b><u> Click here to return to Easy Populate Advanced </u></b></font>');

?>
