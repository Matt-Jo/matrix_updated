<?php

if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'FAQDesk ... Category and FAQ Management');
if (!defined('HEADING_TITLE_SEARCH')) define('HEADING_TITLE_SEARCH', 'Search:');
if (!defined('HEADING_TITLE_GOTO')) define('HEADING_TITLE_GOTO', 'Go To:');

if (!defined('TABLE_HEADING_ID')) define('TABLE_HEADING_ID', 'ID');
if (!defined('TABLE_HEADING_CATEGORIES_FAQDESK')) define('TABLE_HEADING_CATEGORIES_FAQDESK', 'Question');
if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');
if (!defined('TABLE_HEADING_STATUS')) define('TABLE_HEADING_STATUS', 'Status');

if (!defined('IMAGE_NEW_STORY')) define('IMAGE_NEW_STORY', 'New FAQ');

if (!defined('TEXT_CATEGORIES')) define('TEXT_CATEGORIES', 'Categories:');
if (!defined('TEXT_SUBCATEGORIES')) define('TEXT_SUBCATEGORIES', 'Subcategories:');
if (!defined('TEXT_FAQDESK')) define('TEXT_FAQDESK', 'FAQs:');
if (!defined('TEXT_NEW_FAQDESK')) define('TEXT_NEW_FAQDESK', 'FAQs in the category &quot;%s&quot;');

if (!defined('TABLE_HEADING_LATEST_NEWS_HEADLINE')) define('TABLE_HEADING_LATEST_NEWS_HEADLINE', 'Headline');
if (!defined('TEXT_NEWS_ITEMS')) define('TEXT_NEWS_ITEMS', 'FAQs:');
if (!defined('TEXT_INFO_HEADING_DELETE_ITEM')) define('TEXT_INFO_HEADING_DELETE_ITEM', 'Delete Item');
if (!defined('TEXT_DELETE_ITEM_INTRO')) define('TEXT_DELETE_ITEM_INTRO', 'Are you sure you want to permanently delete this item?');

if (!defined('TEXT_LATEST_NEWS_HEADLINE')) define('TEXT_LATEST_NEWS_HEADLINE', 'Question:');
if (!defined('TEXT_FAQDESK_ANSWER_LONG')) define('TEXT_FAQDESK_ANSWER_LONG', 'Long Answer:');

if (!defined('IMAGE_NEW_NEWS_ITEM')) define('IMAGE_NEW_NEWS_ITEM', 'New FAQ');

if (!defined('TEXT_FAQDESK_STATUS')) define('TEXT_FAQDESK_STATUS', 'FAQ Status:');
if (!defined('TEXT_FAQDESK_DATE_AVAILABLE')) define('TEXT_FAQDESK_DATE_AVAILABLE', 'Date Available:');
if (!defined('TEXT_FAQDESK_AVAILABLE')) define('TEXT_FAQDESK_AVAILABLE', 'In Print');
if (!defined('TEXT_FAQDESK_NOT_AVAILABLE')) define('TEXT_FAQDESK_NOT_AVAILABLE', 'Out of Print');

if (!defined('TEXT_FAQDESK_URL')) define('TEXT_FAQDESK_URL', 'Extra URL:');
if (!defined('TEXT_FAQDESK_URL_WITHOUT_HTTP')) define('TEXT_FAQDESK_URL_WITHOUT_HTTP', '<small>(without http://)</small>');

if (!defined('TEXT_FAQDESK_ANSWER_SHORT')) define('TEXT_FAQDESK_ANSWER_SHORT', 'Short Answer:');
if (!defined('TEXT_FAQDESK_ANSWER_LONG')) define('TEXT_FAQDESK_ANSWER_LONG', 'Long Answer:');
if (!defined('TEXT_FAQDESK_QUESTION')) define('TEXT_FAQDESK_QUESTION', 'Question:');

if (!defined('TEXT_FAQDESK_DATE_AVAILABLE')) define('TEXT_FAQDESK_DATE_AVAILABLE', 'Start Date:');
if (!defined('TEXT_FAQDESK_DATE_ADDED')) define('TEXT_FAQDESK_DATE_ADDED', 'This FAQ was submitted on:');

if (!defined('TEXT_FAQDESK_ADDED_LINK_HEADER')) define('TEXT_FAQDESK_ADDED_LINK_HEADER', "This is the link you've added:");
if (!defined('TEXT_FAQDESK_ADDED_LINK')) define('TEXT_FAQDESK_ADDED_LINK', '<a href="http://%s" target="blank"><u>Webseite</u></a>');

if (!defined('TEXT_FAQDESK_AVERAGE_RATING')) define('TEXT_FAQDESK_AVERAGE_RATING', 'Average Rating:');
if (!defined('TEXT_DATE_ADDED')) define('TEXT_DATE_ADDED', 'Date Added:');
if (!defined('TEXT_DATE_AVAILABLE')) define('TEXT_DATE_AVAILABLE', 'Date Available:');
if (!defined('TEXT_LAST_MODIFIED')) define('TEXT_LAST_MODIFIED', 'Last Modified:');
if (!defined('TEXT_IMAGE_NONEXISTENT')) define('TEXT_IMAGE_NONEXISTENT', 'IMAGE DOES NOT EXIST');
define('TEXT_NO_CHILD_CATEGORIES_OR_story', 'Please insert a new category or FAQ in<br>&nbsp;<br><b>%s</b>');

if (!defined('TEXT_EDIT_INTRO')) define('TEXT_EDIT_INTRO', 'Please make any necessary changes');
if (!defined('TEXT_EDIT_CATEGORIES_ID')) define('TEXT_EDIT_CATEGORIES_ID', 'Category ID:');
if (!defined('TEXT_EDIT_CATEGORIES_NAME')) define('TEXT_EDIT_CATEGORIES_NAME', 'Category Name:');
if (!defined('TEXT_EDIT_CATEGORIES_DESCRIPTION')) define('TEXT_EDIT_CATEGORIES_DESCRIPTION', 'Category Description:');
if (!defined('TEXT_EDIT_CATEGORIES_IMAGE')) define('TEXT_EDIT_CATEGORIES_IMAGE', 'Category Image:');
if (!defined('TEXT_EDIT_SORT_ORDER')) define('TEXT_EDIT_SORT_ORDER', 'Sort Order:');

if (!defined('TEXT_INFO_COPY_TO_INTRO')) define('TEXT_INFO_COPY_TO_INTRO', 'Please choose a new category you wish to copy this FAQ to');
if (!defined('TEXT_INFO_CURRENT_CATEGORIES')) define('TEXT_INFO_CURRENT_CATEGORIES', 'Current Categories:');

if (!defined('TEXT_INFO_HEADING_NEW_CATEGORY')) define('TEXT_INFO_HEADING_NEW_CATEGORY', 'New Category');
if (!defined('TEXT_INFO_HEADING_EDIT_CATEGORY')) define('TEXT_INFO_HEADING_EDIT_CATEGORY', 'Edit Category');
if (!defined('TEXT_INFO_HEADING_DELETE_CATEGORY')) define('TEXT_INFO_HEADING_DELETE_CATEGORY', 'Delete Category');
if (!defined('TEXT_INFO_HEADING_MOVE_CATEGORY')) define('TEXT_INFO_HEADING_MOVE_CATEGORY', 'Move Category');
if (!defined('TEXT_INFO_HEADING_DELETE_NEWS')) define('TEXT_INFO_HEADING_DELETE_NEWS', 'Delete FAQ');
if (!defined('TEXT_INFO_HEADING_MOVE_NEWS')) define('TEXT_INFO_HEADING_MOVE_NEWS', 'Move FAQ');
if (!defined('TEXT_INFO_HEADING_COPY_TO')) define('TEXT_INFO_HEADING_COPY_TO', 'Copy To');

if (!defined('TEXT_DELETE_CATEGORY_INTRO')) define('TEXT_DELETE_CATEGORY_INTRO', 'Are you sure you want to delete this category?');
if (!defined('TEXT_DELETE_PRODUCT_INTRO')) define('TEXT_DELETE_PRODUCT_INTRO', 'Are you sure you want to permanently delete this FAQ?');

if (!defined('TEXT_DELETE_WARNING_CHILDS')) define('TEXT_DELETE_WARNING_CHILDS', '<b>WARNING:</b> There are %s (child-)categories still linked to this category!');
if (!defined('TEXT_DELETE_WARNING_FAQDESK')) define('TEXT_DELETE_WARNING_FAQDESK', '<b>WARNING:</b> There are %s FAQS still linked to this category!');

if (!defined('TEXT_MOVE_FAQDESK_INTRO')) define('TEXT_MOVE_FAQDESK_INTRO', 'Please select which category you wish <b>%s</b> to reside in');
if (!defined('TEXT_MOVE_CATEGORIES_INTRO')) define('TEXT_MOVE_CATEGORIES_INTRO', 'Please select which category you wish <b>%s</b> to reside in');
if (!defined('TEXT_MOVE')) define('TEXT_MOVE', 'Move <b>%s</b> to:');

if (!defined('TEXT_NEW_CATEGORY_INTRO')) define('TEXT_NEW_CATEGORY_INTRO', 'Please fill out the following information for the new category');
if (!defined('TEXT_CATEGORIES_NAME')) define('TEXT_CATEGORIES_NAME', 'Category Name:');
if (!defined('TEXT_CATEGORIES_DESCRIPTION_NAME')) define('TEXT_CATEGORIES_DESCRIPTION_NAME', 'Category Description:');
if (!defined('TEXT_CATEGORIES_IMAGE')) define('TEXT_CATEGORIES_IMAGE', 'Category Image:');
if (!defined('TEXT_SORT_ORDER')) define('TEXT_SORT_ORDER', 'Sort Order:');

if (!defined('EMPTY_CATEGORY')) define('EMPTY_CATEGORY', 'Empty Category');

if (!defined('TEXT_HOW_TO_COPY')) define('TEXT_HOW_TO_COPY', 'Copy Method:');
if (!defined('TEXT_COPY_AS_LINK')) define('TEXT_COPY_AS_LINK', 'Link FAQ');
if (!defined('TEXT_COPY_AS_DUPLICATE')) define('TEXT_COPY_AS_DUPLICATE', 'Duplicate FAQ');

if (!defined('ERROR_CANNOT_LINK_TO_SAME_CATEGORY')) define('ERROR_CANNOT_LINK_TO_SAME_CATEGORY', 'Error: Can not link FAQs in the same category.');
if (!defined('ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE')) define('ERROR_CATALOG_IMAGE_DIRECTORY_NOT_WRITEABLE', 'Error: Catalog images directory is not writeable: '.DIR_FS_CATALOG_IMAGES);
if (!defined('ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST')) define('ERROR_CATALOG_IMAGE_DIRECTORY_DOES_NOT_EXIST', 'Error: Catalog images directory does not exist: '.DIR_FS_CATALOG_IMAGES);

if (!defined('TEXT_FAQDESK_START_DATE')) define('TEXT_FAQDESK_START_DATE', 'Start Date:');

if (!defined('TEXT_SHOW_STATUS')) define('TEXT_SHOW_STATUS', 'Status');

if (!defined('TEXT_DELETE_IMAGE')) define('TEXT_DELETE_IMAGE', 'Delete Image(s) ?');
if (!defined('TEXT_DELETE_IMAGE_INTRO')) define('TEXT_DELETE_IMAGE_INTRO', 'BEWARE: Deleting this/these image(s) will completely remove it/them. If you use this/these image(s) elsewhere, errors would occur!');

if (!defined('TEXT_FAQDESK_STICKY')) define('TEXT_FAQDESK_STICKY', 'Sticky Status');
if (!defined('TEXT_FAQDESK_STICKY_ON')) define('TEXT_FAQDESK_STICKY_ON', 'ON');
if (!defined('TEXT_FAQDESK_STICKY_OFF')) define('TEXT_FAQDESK_STICKY_OFF', 'OFF');
if (!defined('TABLE_HEADING_STICKY')) define('TABLE_HEADING_STICKY', 'Sticky');

if (!defined('TEXT_FAQDESK_IMAGE')) define('TEXT_FAQDESK_IMAGE', 'FAQ Image(s):');

if (!defined('TEXT_FAQDESK_IMAGE_ONE')) define('TEXT_FAQDESK_IMAGE_ONE', 'First Image:');
if (!defined('TEXT_FAQDESK_IMAGE_TWO')) define('TEXT_FAQDESK_IMAGE_TWO', 'Second Image:');
if (!defined('TEXT_FAQDESK_IMAGE_THREE')) define('TEXT_FAQDESK_IMAGE_THREE', 'Third Image:');

if (!defined('TEXT_FAQDESK_IMAGE_SUBTITLE')) define('TEXT_FAQDESK_IMAGE_SUBTITLE', 'Image title for First Image:');
if (!defined('TEXT_FAQDESK_IMAGE_SUBTITLE_TWO')) define('TEXT_FAQDESK_IMAGE_SUBTITLE_TWO', 'Image title for Second Image:');
if (!defined('TEXT_FAQDESK_IMAGE_SUBTITLE_THREE')) define('TEXT_FAQDESK_IMAGE_SUBTITLE_THREE', 'Image title for Third Image:');

if (!defined('TEXT_FAQDESK_IMAGE_PREVIEW_ONE')) define('TEXT_FAQDESK_IMAGE_PREVIEW_ONE', 'FAQ Image number 1:');
if (!defined('TEXT_FAQDESK_IMAGE_PREVIEW_TWO')) define('TEXT_FAQDESK_IMAGE_PREVIEW_TWO', 'FAQ Image number 2:');
if (!defined('TEXT_FAQDESK_IMAGE_PREVIEW_THREE')) define('TEXT_FAQDESK_IMAGE_PREVIEW_THREE', 'FAQ Image number 3:');

/*

	osCommerce, Open Source E-Commerce Solutions ---- http://www.oscommerce.com
	Copyright (c) 2002 osCommerce
	Released under the GNU General Public License

	IMPORTANT NOTE:

	This script is not part of the official osC distribution but an add-on contributed to the osC community.
	Please read the NOTE and INSTALL documents that are provided with this file for further information and installation notes.

	script name:	FaqDesk
	version:		1.2.5
	date:			2003-09-01
	author:			Carsten aka moyashi
	web site:		www..com

*/
?>
