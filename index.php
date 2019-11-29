<?php
require('includes/application_top.php');

// the following cPath references come from application_top.php
$category_depth = 'top';

function remote_img_exists($uri) {
	if (@get_headers($uri)[0] == 'HTTP/1.1 404 Not Found') return FALSE;
	else return TRUE;
}

if (!empty($cPath)) {
	$browse = new navigate_nextopia('browse');
	$browse->query();

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
}

require(DIR_WS_LANGUAGES.$_SESSION['language'].'/index.php');

if (empty($cPath)) {
	$content = 'index_default';
}
else {
	if (!empty($browse) && $browse->results) {
		// Get the category name and description
		$page_category = prepared_query::fetch('SELECT cd.categories_name, cd.categories_heading_title, cd.categories_description, c.categories_image, c.promo_image, c.promo_link, c.promo_offsite FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id AND cd.language_id = ? WHERE c.categories_id = ?', cardinality::ROW, [$_SESSION['languages_id'], $current_category_id]);

		// needed for the new products module shown below
		// need to confirm this is still needed
		$new_products_category_id = $current_category_id;
	}
	$content = 'index_nested';
}

require('templates/Pixame_v1/main_page.tpl.php');

