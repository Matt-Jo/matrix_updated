<?php
/*
 $Id: tasks.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Tasks');

if (!defined('TABLE_HEADING_TASKS')) define('TABLE_HEADING_TASKS', 'Tasks');
if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');

if (!defined('TEXT_HEADING_NEW_TASK')) define('TEXT_HEADING_NEW_TASK', 'New Task');
if (!defined('TEXT_HEADING_EDIT_TASK')) define('TEXT_HEADING_EDIT_TASK', 'Edit Task');
if (!defined('TEXT_HEADING_DELETE_TASK')) define('TEXT_HEADING_DELETE_TASK', 'Delete Task');

if (!defined('TEXT_TASKS')) define('TEXT_TASKS', 'Tasks:');
if (!defined('TEXT_DATE_ADDED')) define('TEXT_DATE_ADDED', 'Date Added:');
if (!defined('TEXT_LAST_MODIFIED')) define('TEXT_LAST_MODIFIED', 'Last Modified:');
if (!defined('TEXT_PRODUCTS')) define('TEXT_PRODUCTS', 'Products:');
if (!defined('TEXT_IMAGE_NONEXISTENT')) define('TEXT_IMAGE_NONEXISTENT', 'IMAGE DOES NOT EXIST');

if (!defined('TEXT_NEW_INTRO')) define('TEXT_NEW_INTRO', 'Please fill out the following information for the new task');
if (!defined('TEXT_EDIT_INTRO')) define('TEXT_EDIT_INTRO', 'Please make any necessary changes');

if (!defined('TEXT_TASKS_NAME')) define('TEXT_TASKS_NAME', 'Tasks Name:');
if (!defined('TEXT_TASKS_IMAGE')) define('TEXT_TASKS_IMAGE', 'Tasks Image:');
if (!defined('TEXT_TASKS_URL')) define('TEXT_TASKS_URL', 'Tasks URL:');

if (!defined('TEXT_DELETE_INTRO')) define('TEXT_DELETE_INTRO', 'Are you sure you want to delete this task?');
if (!defined('TEXT_DELETE_IMAGE')) define('TEXT_DELETE_IMAGE', 'Delete tasks image?');
if (!defined('TEXT_DELETE_PRODUCTS')) define('TEXT_DELETE_PRODUCTS', 'Delete products from this task? (including product reviews, products on special, upcoming products)');
if (!defined('TEXT_DELETE_WARNING_PRODUCTS')) define('TEXT_DELETE_WARNING_PRODUCTS', '<b>WARNING:</b> There are %s products still linked to this task!');

if (!defined('ERROR_DIRECTORY_NOT_WRITEABLE')) define('ERROR_DIRECTORY_NOT_WRITEABLE', 'Error: I can not write to this directory. Please set the right user permissions on: %s');
if (!defined('ERROR_DIRECTORY_DOES_NOT_EXIST')) define('ERROR_DIRECTORY_DOES_NOT_EXIST', 'Error: Directory does not exist: %s');
?>
