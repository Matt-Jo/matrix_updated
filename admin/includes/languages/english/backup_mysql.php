<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce										|
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers							|
// |																	|
// | http://www.zen-cart.com/index.php									|
// |																	|
// | Portions Copyright (c) 2003 osCommerce								|
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,		|
// | that is bundled with this package in the file LICENSE, and is		|
// | available through the world-wide-web at the following url:			|
// | http://www.zen-cart.com/license/2_0.txt.							|
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to		|
// | license@zen-cart.com so we can mail you a copy immediately.		|
// +----------------------------------------------------------------------+
// $Id: backup_mysql.php,v 1.2.0.1 2004/08/03 00:00:00 DrByteZen Exp $
//

// define the locations of the mysql utilities. Typical location is in '/usr/bin/' ... but not on Windows servers.
// try 'c:/mysql/bin/mysql.exe' and 'c:/mysql/bin/mysqldump.exe' on Windows hosts ... change drive letter and path as needed
if (!defined('LOCAL_EXE_MYSQL')) define('LOCAL_EXE_MYSQL',	'/usr/local/bin/mysql'); // used for restores
if (!defined('LOCAL_EXE_MYSQLDUMP')) define('LOCAL_EXE_MYSQLDUMP', '/usr/local/bin/mysqldump'); // used for backups

// the following are the language definitions
if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Database Backup Manager - MySQL');
if (!defined('WARNING_NOT_SECURE_FOR_DOWNLOADS')) define('WARNING_NOT_SECURE_FOR_DOWNLOADS','<span class="errorText">NOTE: You do not have SSL enabled. Any downloads you do from this page will not be encrypted. Doing backups and restores will be fine, but download/upload of files from/to the server presents a security risk.');
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
if (!defined('TEXT_INFO_DOWNLOAD_ONLY')) define('TEXT_INFO_DOWNLOAD_ONLY', 'Download without storing on server');
if (!defined('TEXT_INFO_BEST_THROUGH_HTTPS')) define('TEXT_INFO_BEST_THROUGH_HTTPS', '(Safer via a secured HTTPS connection)');
if (!defined('TEXT_DELETE_INTRO')) define('TEXT_DELETE_INTRO', 'Are you sure you want to delete this backup?');
if (!defined('TEXT_NO_EXTENSION')) define('TEXT_NO_EXTENSION', 'None');
if (!defined('TEXT_BACKUP_DIRECTORY')) define('TEXT_BACKUP_DIRECTORY', 'Backup Directory:');
if (!defined('TEXT_LAST_RESTORATION')) define('TEXT_LAST_RESTORATION', 'Last Restoration:');
if (!defined('TEXT_FORGET')) define('TEXT_FORGET', '(forget)');

if (!defined('ERROR_BACKUP_DIRECTORY_DOES_NOT_EXIST')) define('ERROR_BACKUP_DIRECTORY_DOES_NOT_EXIST', 'Error: Backup directory does not exist. Please set this in configure.php.');
if (!defined('ERROR_BACKUP_DIRECTORY_NOT_WRITEABLE')) define('ERROR_BACKUP_DIRECTORY_NOT_WRITEABLE', 'Error: Backup directory is not writeable.');
if (!defined('ERROR_DOWNLOAD_LINK_NOT_ACCEPTABLE')) define('ERROR_DOWNLOAD_LINK_NOT_ACCEPTABLE', 'Error: Download link not acceptable.');
if (!defined('ERROR_CANT_BACKUP_IN_SAFE_MODE')) define('ERROR_CANT_BACKUP_IN_SAFE_MODE','ERROR: Cannot use backup script when safe_mode is enabled.');

if (!defined('SUCCESS_LAST_RESTORE_CLEARED')) define('SUCCESS_LAST_RESTORE_CLEARED', 'Success: The last restoration date has been cleared.');
if (!defined('SUCCESS_DATABASE_SAVED')) define('SUCCESS_DATABASE_SAVED', 'Success: The database has been saved.');
if (!defined('SUCCESS_DATABASE_RESTORED')) define('SUCCESS_DATABASE_RESTORED', 'Success: The database has been restored.');
if (!defined('SUCCESS_BACKUP_DELETED')) define('SUCCESS_BACKUP_DELETED', 'Success: The backup has been removed.');
if (!defined('FAILURE_DATABASE_NOT_SAVED')) define('FAILURE_DATABASE_NOT_SAVED', 'Failure: The database has NOT been saved.');
if (!defined('FAILURE_DATABASE_NOT_SAVED_UTIL_NOT_FOUND')) define('FAILURE_DATABASE_NOT_SAVED_UTIL_NOT_FOUND', 'ERROR: Could not locate the MYSQLDUMP backup utility. BACKUP FAILED.');
if (!defined('FAILURE_DATABASE_NOT_RESTORED')) define('FAILURE_DATABASE_NOT_RESTORED', 'Failure: The database may NOT have been restored properly. Please check it carefully.');
if (!defined('FAILURE_DATABASE_NOT_RESTORED_FILE_NOT_FOUND')) define('FAILURE_DATABASE_NOT_RESTORED_FILE_NOT_FOUND', 'Failure: The database was NOT restored. ERROR: FILE NOT FOUND: %s');
if (!defined('FAILURE_DATABASE_NOT_RESTORED_UTIL_NOT_FOUND')) define('FAILURE_DATABASE_NOT_RESTORED_UTIL_NOT_FOUND', 'ERROR: Could not locate the MYSQL restore utility. RESTORE FAILED.');
?>
