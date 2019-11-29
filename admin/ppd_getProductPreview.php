<?php
require_once('includes/application_top.php');

$product = new ck_product_listing($_GET['product_id']); ?>
({
	name: "<?= addslashes($product->get_header('products_name')); ?>",
	imageUrl: "<?= DIR_WS_CATALOG_IMAGES.$product->get_image('products_image'); ?>",
	price: "<?= $product->get_price('original'); ?>",
	description: "<?= addslashes($product->get_header('products_head_desc_tag')); ?>"
})
