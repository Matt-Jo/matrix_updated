<?php
/*
	$Id: specials.php,v 1.1.1.1 2004/03/04 23:38:03 ccwjr Exp $

	osCommerce, Open Source E-Commerce Solutions
	http://www.oscommerce.com

	Copyright (c) 2003 osCommerce

	Released under the GNU General Public License
*/

require('includes/application_top.php');

function remote_img_exists($uri) {
	if (@get_headers($uri)[0] == 'HTTP/1.1 404 Not Found') return FALSE;
	else return TRUE;
}

//MMD - D-117 - want to also show scratch and dent on the outlet page so we added a new field to the feed
//$_GET['refinement_data']['Currentoffers:On Special'] = 'Currentoffers:On Special';
$_GET['refinement_data']['Outletpage:Y'] = 'Outletpage:Y';

$browse = new navigate_nextopia('browse');
$browse->query();
$browse->hide_refinements[] = 'Currentoffers';

if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
	require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
	require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
	require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');
	require_once(DIR_FS_CATALOG.'includes/engine/framework/canonical_page.class.php');
	require_once(DIR_FS_CATALOG.'includes/engine/tools/imagesizer.class.php');

	$cdn = '//media.cablesandkits.com';
	$static = $cdn.'/static';

	$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates');
	$cktpl->set_stage(1);
	// return ajax results
	echo $browse->build_json();
	$cktpl->set_stage(3);
	exit();
}

$breadcrumb->add('OUTLET', '/outlet.php');

$content = 'outlet';

require('templates/Pixame_v1/main_page.tpl.php');
?>
