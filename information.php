<?php
require('includes/application_top.php');

$info_id = !empty($_GET['info_id'])?$_GET['info_id']:NULL;

if (!$info_id || !($info_page = prepared_query::fetch('SELECT information_id, visible, v_order, info_title, description as content, product_ids, languages_id, sitewide_header FROM information WHERE visible = 1 AND information_id = :information_id', cardinality::ROW, array(':information_id' => $info_id)))) $content = '404';
else {
	$info_page['info_title'] = stripslashes($info_page['info_title']);
	$info_page['content'] = stripslashes($info_page['content']);

	// Only replace cariage return by <BR> if NO HTML found in text
	// Added as noticed by infopages module
	if ($info_page['content'] === strip_tags($info_page['content'])) {
		$info_page['content'] = str_replace("\r?\n", "<br>", $info_page['content']);
	}

	require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
	require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
	require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

	$cktpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
	$cktpl->buffer = TRUE;

	$content = new ck_content;

	$content->products = [];

	if (!empty($info_page['product_ids'])) {
		$content->products = [];
		$product_ids = preg_split('/\s*,\s*/', $info_page['product_ids']);

		foreach ($product_ids as $product_id) {
			$product = new ck_product_listing($product_id);
			if (!$product->is_viewable()) continue;
			$template = $product->get_thin_template();
			$content->products[] = $template;
			$key = 'prod-'.$product->id();
			$content->$key = $template;
		}
	}
	$info_page['full_width'] = 1;
	$info_page['content'] = $cktpl->simple_content($info_page['content'], $content);

	$info_page['link'] = '/'.CK\fn::simple_seo($info_page['info_title'], '-i-'.$info_page['information_id'].'.html');

	$compare = parse_url($_SERVER['REQUEST_URI']);

	if ($info_page['link'] != $compare['path']) CK\fn::redirect_and_exit($info_page['link']);

	$content = 'information';
}

require('templates/Pixame_v1/main_page.tpl.php');
?>
