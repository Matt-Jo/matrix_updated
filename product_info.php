<?php
@require('includes/application_top.php');

if (empty($_GET['products_id'])) CK\fn::redirect_and_exit('/');


$product = new ck_product_listing($_GET['products_id']);
if (!$product->is_viewable()) {
	if ($categories = $product->get_categories()) {
		foreach ($categories as $category) {
			CK\fn::redirect_and_exit($category->get_url());
			break;
		}
	}
}

$content = 'product_info';

require('templates/Pixame_v1/main_page.tpl.php');
?>
