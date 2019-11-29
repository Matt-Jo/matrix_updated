<?php
$_GET['keywords'] = preg_replace('/>/', '&gt;', $_GET['keywords']);
$_GET['keywords'] = preg_replace('/</', '&lt;', $_GET['keywords']);

require('includes/application_top.php');

function remote_img_exists($uri) {
	if (@get_headers($uri)[0] == 'HTTP/1.1 404 Not Found') return FALSE;
	else return TRUE;
}

$error = false;

$search = new navigate_nextopia('search');

if (!isset($_GET['ajax']) || $_GET['ajax'] != 1) {
	unset($search->search_sort_by);
	unset($search->search_sort_direction);
	unset($search->search_sort_key);
}

$search->query();

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
	echo $search->build_json();
	$cktpl->set_stage(3);
	exit();
}

$breadcrumb->add('Search Results', '/advanced_search_result.php?'.CK\fn::qs([], $_GET));

$content = 'advanced_search_result';

require('templates/Pixame_v1/main_page.tpl.php');
?>
