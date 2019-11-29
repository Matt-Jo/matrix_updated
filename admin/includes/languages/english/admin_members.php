<?php
/*
 $Id: admin_members.php,v 1.2 2004/03/05 00:36:41 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2002 osCommerce

 Released under the GNU General Public License
*/

if (!empty($_GET['gID'])) {
 if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Admin Groups');
} elseif (!empty($_GET['gPath'])) {
 if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Define Groups');
} else {
 if (!defined('HEADING_TITLE')) define('HEADING_TITLE', 'Admin Members');
}

if (!defined('TEXT_COUNT_GROUPS')) define('TEXT_COUNT_GROUPS', 'Groups: ');

if (!defined('TABLE_HEADING_NAME')) define('TABLE_HEADING_NAME', 'Name');
if (!defined('TABLE_HEADING_EMAIL')) define('TABLE_HEADING_EMAIL', 'Email Address');
if (!defined('TABLE_HEADING_PASSWORD')) define('TABLE_HEADING_PASSWORD', 'Password');
if (!defined('TABLE_HEADING_CONFIRM')) define('TABLE_HEADING_CONFIRM', 'Confirm Password');
if (!defined('TABLE_HEADING_GROUPS')) define('TABLE_HEADING_GROUPS', 'Groups Level');
if (!defined('TABLE_HEADING_CREATED')) define('TABLE_HEADING_CREATED', 'Account Created');
if (!defined('TABLE_HEADING_MODIFIED')) define('TABLE_HEADING_MODIFIED', 'Account Modified');
if (!defined('TABLE_HEADING_LOGDATE')) define('TABLE_HEADING_LOGDATE', 'Last Access');
if (!defined('TABLE_HEADING_LOGNUM')) define('TABLE_HEADING_LOGNUM', 'LogNum');
if (!defined('TABLE_HEADING_LOG_NUM')) define('TABLE_HEADING_LOG_NUM', 'Log Number');
if (!defined('TABLE_HEADING_ACTION')) define('TABLE_HEADING_ACTION', 'Action');

if (!defined('TABLE_HEADING_GROUPS_NAME')) define('TABLE_HEADING_GROUPS_NAME', 'Groups Name');
if (!defined('TABLE_HEADING_GROUPS_DEFINE')) define('TABLE_HEADING_GROUPS_DEFINE', 'Boxes and Files Selection');
if (!defined('TABLE_HEADING_GROUPS_GROUP')) define('TABLE_HEADING_GROUPS_GROUP', 'Level');
if (!defined('TABLE_HEADING_GROUPS_CATEGORIES')) define('TABLE_HEADING_GROUPS_CATEGORIES', 'Categories Permission');


if (!defined('TEXT_INFO_HEADING_DEFAULT')) define('TEXT_INFO_HEADING_DEFAULT', 'Admin Member ');
if (!defined('TEXT_INFO_HEADING_DELETE')) define('TEXT_INFO_HEADING_DELETE', 'Delete Permission ');
if (!defined('TEXT_INFO_HEADING_EDIT')) define('TEXT_INFO_HEADING_EDIT', 'Edit Category / ');
if (!defined('TEXT_INFO_HEADING_NEW')) define('TEXT_INFO_HEADING_NEW', 'New Admin Member ');

if (!defined('TEXT_INFO_DEFAULT_INTRO')) define('TEXT_INFO_DEFAULT_INTRO', 'Member Group');
if (!defined('TEXT_INFO_DELETE_INTRO')) define('TEXT_INFO_DELETE_INTRO', 'Remove <nobr><b>%s</b></nobr> from <nobr>Admin Members?</nobr>');
if (!defined('TEXT_INFO_DELETE_INTRO_NOT')) define('TEXT_INFO_DELETE_INTRO_NOT', 'You can not delete <nobr>%s group!</nobr>');
if (!defined('TEXT_INFO_EDIT_INTRO')) define('TEXT_INFO_EDIT_INTRO', 'Set permission level here: ');

if (!defined('TEXT_INFO_FULLNAME')) define('TEXT_INFO_FULLNAME', 'Name: ');
if (!defined('TEXT_INFO_FIRSTNAME')) define('TEXT_INFO_FIRSTNAME', 'Firstname: ');
if (!defined('TEXT_INFO_LASTNAME')) define('TEXT_INFO_LASTNAME', 'Lastname: ');
if (!defined('TEXT_INFO_EMAIL')) define('TEXT_INFO_EMAIL', 'Email Address: ');
if (!defined('TEXT_INFO_PASSWORD')) define('TEXT_INFO_PASSWORD', 'Password: ');
if (!defined('TEXT_INFO_CONFIRM')) define('TEXT_INFO_CONFIRM', 'Confirm Password: ');
if (!defined('TEXT_INFO_CREATED')) define('TEXT_INFO_CREATED', 'Account Created: ');
if (!defined('TEXT_INFO_MODIFIED')) define('TEXT_INFO_MODIFIED', 'Account Modified: ');
if (!defined('TEXT_INFO_LOGDATE')) define('TEXT_INFO_LOGDATE', 'Last Access: ');
if (!defined('TEXT_INFO_LOGNUM')) define('TEXT_INFO_LOGNUM', 'Log Number: ');
if (!defined('TEXT_INFO_GROUP')) define('TEXT_INFO_GROUP', 'Group Level: ');
if (!defined('TEXT_INFO_ERROR')) define('TEXT_INFO_ERROR', '<font color="red">Email address has already been used! Please try again.</font>');

if (!defined('JS_ALERT_FIRSTNAME')) define('JS_ALERT_FIRSTNAME', '- Required: Firstname \n');
if (!defined('JS_ALERT_LASTNAME')) define('JS_ALERT_LASTNAME', '- Required: Lastname \n');
if (!defined('JS_ALERT_EMAIL')) define('JS_ALERT_EMAIL', '- Required: Email address \n');
if (!defined('JS_ALERT_EMAIL_FORMAT')) define('JS_ALERT_EMAIL_FORMAT', '- Email address format is invalid! \n');
if (!defined('JS_ALERT_EMAIL_USED')) define('JS_ALERT_EMAIL_USED', '- Email address has already been used! \n');
if (!defined('JS_ALERT_LEVEL')) define('JS_ALERT_LEVEL', '- Required: Group Member \n');

if (!defined('ADMIN_EMAIL_SUBJECT')) define('ADMIN_EMAIL_SUBJECT', 'New Admin Member');
if (!defined('ADMIN_EMAIL_TEXT')) define('ADMIN_EMAIL_TEXT', 'Hi %s,'."\n\n".'You can access the admin panel with the following password. Once you access the admin, please change your password!'."\n\n".'Website : %s'."\n".'Username: %s'."\n".'Password: %s'."\n\n".'Thanks!'."\n".'%s'."\n\n".'This is a system automated response, please do not reply, as it would be unread!');
if (!defined('ADMIN_EMAIL_EDIT_SUBJECT')) define('ADMIN_EMAIL_EDIT_SUBJECT', 'Admin Member Profile Edit');
if (!defined('ADMIN_EMAIL_EDIT_TEXT')) define('ADMIN_EMAIL_EDIT_TEXT', 'Hi %s,'."\n\n".'Your personal information has been updated by an administrator.'."\n\n".'Website : %s'."\n".'Username: %s'."\n".'Password: %s'."\n\n".'Thanks!'."\n".'%s'."\n\n".'This is a system automated response, please do not reply, as it would be unread!');

if (!defined('TEXT_INFO_HEADING_DEFAULT_GROUPS')) define('TEXT_INFO_HEADING_DEFAULT_GROUPS', 'Admin Group ');
if (!defined('TEXT_INFO_HEADING_DELETE_GROUPS')) define('TEXT_INFO_HEADING_DELETE_GROUPS', 'Delete Group ');

if (!defined('TEXT_INFO_DEFAULT_GROUPS_INTRO')) define('TEXT_INFO_DEFAULT_GROUPS_INTRO', '<b>NOTE:</b><li><b>edit:</b> edit group name.</li><li><b>delete:</b> delete group.</li><li><b>define:</b> define and change group access.</li>');
if (!defined('TEXT_INFO_DELETE_GROUPS_INTRO')) define('TEXT_INFO_DELETE_GROUPS_INTRO', 'It will also delete members of this group. Are you sure want to delete <nobr><b>%s</b> group?</nobr>');
if (!defined('TEXT_INFO_DELETE_GROUPS_INTRO_NOT')) define('TEXT_INFO_DELETE_GROUPS_INTRO_NOT', 'You can not delete this groups!');
if (!defined('TEXT_INFO_GROUPS_INTRO')) define('TEXT_INFO_GROUPS_INTRO', 'Give an unique group name. Click next to submit.');

if (!defined('TEXT_INFO_HEADING_GROUPS')) define('TEXT_INFO_HEADING_GROUPS', 'New Group');
if (!defined('TEXT_INFO_GROUPS_NAME')) define('TEXT_INFO_GROUPS_NAME', ' <b>Group Name:</b><br>Give an unique group name. Then, click next to submit.<br>');
if (!defined('TEXT_INFO_GROUPS_NAME_FALSE')) define('TEXT_INFO_GROUPS_NAME_FALSE', '<font color="red"><b>ERROR:</b> The group name must have at least 5 characters!</font>');
if (!defined('TEXT_INFO_GROUPS_NAME_USED')) define('TEXT_INFO_GROUPS_NAME_USED', '<font color="red"><b>ERROR:</b> Group name has already been used!</font>');
if (!defined('TEXT_INFO_GROUPS_LEVEL')) define('TEXT_INFO_GROUPS_LEVEL', 'Group Level: ');
if (!defined('TEXT_INFO_GROUPS_BOXES')) define('TEXT_INFO_GROUPS_BOXES', '<b>Boxes Permission:</b><br>Give access to selected boxes.');
if (!defined('TEXT_INFO_GROUPS_BOXES_INCLUDE')) define('TEXT_INFO_GROUPS_BOXES_INCLUDE', 'Include files stored in: ');

if (!defined('TEXT_INFO_EDIT_GROUP_INTRO')) define('TEXT_INFO_EDIT_GROUP_INTRO', 'Edit Group Name: ');

if (!defined('TEXT_INFO_HEADING_DEFINE')) define('TEXT_INFO_HEADING_DEFINE', 'Define Group');
if (@$_GET['gPath'] == 1) {
 if (!defined('TEXT_INFO_DEFINE_INTRO')) define('TEXT_INFO_DEFINE_INTRO', '<b>%s :</b><br>You can not change file permission for this group.<br><br>');
} else {
 if (!defined('TEXT_INFO_DEFINE_INTRO')) define('TEXT_INFO_DEFINE_INTRO', '<b>%s :</b><br>Change permission for this group by selecting or unselecting boxes and files provided. Click <b>save</b> to save the changes.<br><br>');
}
?>
