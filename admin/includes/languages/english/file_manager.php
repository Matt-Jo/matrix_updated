<?php
/*
 $Id: file_manager.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'File Manager');

if (!defined('TABLE_HEADING_FILENAME')) define('TABLE_HEADING_FILENAME', 'Name');
if (!defined('TABLE_HEADING_SIZE')) define('TABLE_HEADING_SIZE', 'Size');
if (!defined('TABLE_HEADING_PERMISSIONS')) define('TABLE_HEADING_PERMISSIONS', 'Permissions');
if (!defined('TABLE_HEADING_USER')) define('TABLE_HEADING_USER', 'User');
if (!defined('TABLE_HEADING_GROUP')) define('TABLE_HEADING_GROUP', 'Group');
if (!defined('TABLE_HEADING_LAST_MODIFIED')) define('TABLE_HEADING_LAST_MODIFIED', 'Last Modified');
if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');

if (!defined('TEXT_INFO_HEADING_UPLOAD')) define('TEXT_INFO_HEADING_UPLOAD', 'Upload');
if (!defined('TEXT_FILE_NAME')) define('TEXT_FILE_NAME', 'Filename:');
if (!defined('TEXT_FILE_SIZE')) define('TEXT_FILE_SIZE', 'Size:');
if (!defined('TEXT_FILE_CONTENTS')) define('TEXT_FILE_CONTENTS', 'Contents:');
if (!defined('TEXT_LAST_MODIFIED')) define('TEXT_LAST_MODIFIED', 'Last Modified:');
if (!defined('TEXT_NEW_FOLDER')) define('TEXT_NEW_FOLDER', 'New Folder');
if (!defined('TEXT_NEW_FOLDER_INTRO')) define('TEXT_NEW_FOLDER_INTRO', 'Enter the name for the new folder:');
if (!defined('TEXT_DELETE_INTRO')) define('TEXT_DELETE_INTRO', 'Are you sure you want to delete this file?');
if (!defined('TEXT_UPLOAD_INTRO')) define('TEXT_UPLOAD_INTRO', 'Please select the files to upload.');

if (!defined('ERROR_DIRECTORY_NOT_WRITEABLE')) define('ERROR_DIRECTORY_NOT_WRITEABLE', 'Error: I can not write to this directory. Please set the right user permissions on: %s');
if (!defined('ERROR_FILE_NOT_WRITEABLE')) define('ERROR_FILE_NOT_WRITEABLE', 'Error: I can not write to this file. Please set the right user permissions on: %s');
if (!defined('ERROR_DIRECTORY_NOT_REMOVEABLE')) define('ERROR_DIRECTORY_NOT_REMOVEABLE', 'Error: I can not remove this directory. Please set the right user permissions on: %s');
if (!defined('ERROR_FILE_NOT_REMOVEABLE')) define('ERROR_FILE_NOT_REMOVEABLE', 'Error: I can not remove this file. Please set the right user permissions on: %s');
if (!defined('ERROR_DIRECTORY_DOES_NOT_EXIST')) define('ERROR_DIRECTORY_DOES_NOT_EXIST', 'Error: Directory does not exist: %s');
?>
