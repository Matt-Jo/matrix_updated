<?php
/*
 $Id: backup.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Database Backup Manager');

if (!defined('TABLE_HEADING_TITLE')) define('TABLE_HEADING_TITLE', 'Title');
if (!defined('TABLE_HEADING_FILE_DATE')) define('TABLE_HEADING_FILE_DATE', 'Date');
if (!defined('TABLE_HEADING_FILE_SIZE')) define('TABLE_HEADING_FILE_SIZE', 'Size');
if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');

if (!defined('TEXT_INFO_HEADING_NEW_BACKUP')) define('TEXT_INFO_HEADING_NEW_BACKUP', 'New Backup');
if (!defined('TEXT_INFO_HEADING_RESTORE_LOCAL')) define('TEXT_INFO_HEADING_RESTORE_LOCAL', 'Restore Local');
if (!defined('TEXT_INFO_NEW_BACKUP')) define('TEXT_INFO_NEW_BACKUP', 'Do not interrupt the backup process which might take a couple of minutes.');
if (!defined('TEXT_INFO_UNPACK')) define('TEXT_INFO_UNPACK', '<br><br>(after unpacking the file from the archive)');
if (!defined('TEXT_INFO_RESTORE')) define('TEXT_INFO_RESTORE', 'Do not interrupt the restoration process.<br><br>The larger the backup, the longer this process takes!<br><br>If possible, use the mysql client.<br><br>For example:<br><br><b>mysql -h'.DB_SERVER.' -u'.DB_SERVER_USERNAME.' -p '.DB_DATABASE.' < %s </b> %s');
if (!defined('TEXT_INFO_RESTORE_LOCAL')) define('TEXT_INFO_RESTORE_LOCAL', 'Do not interrupt the restoration process.<br><br>The larger the backup, the longer this process takes!');
if (!defined('TEXT_INFO_RESTORE_LOCAL_RAW_FILE')) define('TEXT_INFO_RESTORE_LOCAL_RAW_FILE', 'The file uploaded must be a raw sql (text) file.');
if (!defined('TEXT_INFO_DATE')) define('TEXT_INFO_DATE', 'Date:');
if (!defined('TEXT_INFO_SIZE')) define('TEXT_INFO_SIZE', 'Size:');
if (!defined('TEXT_INFO_COMPRESSION')) define('TEXT_INFO_COMPRESSION', 'Compression:');
if (!defined('TEXT_INFO_USE_GZIP')) define('TEXT_INFO_USE_GZIP', 'Use GZIP');
if (!defined('TEXT_INFO_USE_ZIP')) define('TEXT_INFO_USE_ZIP', 'Use ZIP');
if (!defined('TEXT_INFO_USE_NO_COMPRESSION')) define('TEXT_INFO_USE_NO_COMPRESSION', 'No Compression (Pure SQL)');
if (!defined('TEXT_INFO_DOWNLOAD_ONLY')) define('TEXT_INFO_DOWNLOAD_ONLY', 'Download only (do not store server side)');
if (!defined('TEXT_INFO_BEST_THROUGH_HTTPS')) define('TEXT_INFO_BEST_THROUGH_HTTPS', 'Best through a HTTPS connection');
if (!defined('TEXT_DELETE_INTRO')) define('TEXT_DELETE_INTRO', 'Are you sure you want to delete this backup?');
if (!defined('TEXT_NO_EXTENSION')) define('TEXT_NO_EXTENSION', 'None');
if (!defined('TEXT_BACKUP_DIRECTORY')) define('TEXT_BACKUP_DIRECTORY', 'Backup Directory:');
if (!defined('TEXT_LAST_RESTORATION')) define('TEXT_LAST_RESTORATION', 'Last Restoration:');
if (!defined('TEXT_FORGET')) define('TEXT_FORGET', '(<u>forget</u>)');

if (!defined('ERROR_BACKUP_DIRECTORY_DOES_NOT_EXIST')) define('ERROR_BACKUP_DIRECTORY_DOES_NOT_EXIST', 'Error: Backup directory does not exist. Please set this in configure.php.');
if (!defined('ERROR_BACKUP_DIRECTORY_NOT_WRITEABLE')) define('ERROR_BACKUP_DIRECTORY_NOT_WRITEABLE', 'Error: Backup directory is not writeable.');
if (!defined('ERROR_DOWNLOAD_LINK_NOT_ACCEPTABLE')) define('ERROR_DOWNLOAD_LINK_NOT_ACCEPTABLE', 'Error: Download link not acceptable.');

if (!defined('SUCCESS_LAST_RESTORE_CLEARED')) define('SUCCESS_LAST_RESTORE_CLEARED', 'Success: The last restoration date has been cleared.');
if (!defined('SUCCESS_DATABASE_SAVED')) define('SUCCESS_DATABASE_SAVED', 'Success: The database has been saved.');
if (!defined('SUCCESS_DATABASE_RESTORED')) define('SUCCESS_DATABASE_RESTORED', 'Success: The database has been restored.');
if (!defined('SUCCESS_BACKUP_DELETED')) define('SUCCESS_BACKUP_DELETED', 'Success: The backup has been removed.');
?>
