<?php
require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');
$current_category = new ck_listing_category($current_category_id);
?>
<link rel="stylesheet" href="/images/static/css/product-finder.css">
<div class="productListing">
	<?php if (!empty($current_category->is('use_categories_description'))) { ?>
		<div class="rounded-corners" style="margin:5px; padding:10px 0; background-color:#fff; border-radius:10px; ">
			<div class="grid">
				<?php if (!empty($current_category->get_header('categories_image'))) { ?>
				<img src="<?= $cdn.'/'.$current_category->get_header('categories_image'); ?>" align="middle" style="float:right; margin:0px 0px 10px 10px; max-width:100%;">
				<?php } ?>
				<?php $cattpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
				$cattpl->buffer = TRUE;

				$content = new ck_content;

				$content->products = [];

				if (!empty($current_category->has('categories_description_product_ids'))) {
					$product_ids = preg_split('/\s*,\s*/', $current_category->get('categories_description_product_ids'));
					foreach ($product_ids as $product_id) {
						$product = new ck_product_listing($product_id);
						if (!$product->is_viewable()) continue;
						$template = $product->get_thin_template();
						$content->products[] = $template;
						$key = 'prod-'.$product->id();
						$content->$key = $template;
					}
				}

				$category_description = $cattpl->simple_content($current_category->get_header('categories_description'), $content);
				echo $category_description; ?>
			</div>
		</div>
	<?php }
	$categories = prepared_query::fetch('SELECT c.categories_id, cd.categories_name, c.categories_image, c.parent_id FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE c.parent_id = :parent_id AND cd.language_id = :languages_id AND c.inactive = 0 ORDER BY c.sort_order ASC, cd.categories_name ASC', cardinality::SET, [':parent_id' => $current_category_id, ':languages_id' => $_SESSION['languages_id']]);
	if (!empty($categories) || !empty($page_category['promo_image'])) { ?>
		<div align="center" class="subcat-block <?php echo $experiment->context_key; ?>">
			<div class="cat-group">
				<?php if (!empty($page_category['promo_image'])) {
					if (!empty($page_category['promo_link'])) { ?>
					<a class="category-promo" href="<?= $page_category['promo_link']; ?>" <?= $page_category['promo_offsite']?'target="_blank"':''; ?>><img src="<?= $cdn.'/'.$page_category['promo_image']; ?>"></a>
					<?php }
					else { ?>
					<div class="category-promo"><img src="<?= $cdn.'/'.$page_category['promo_image']; ?>"></div>
					<?php }
				}

				if (!empty($categories)) {
					foreach ($categories as $subcategory) {
						$listc = new ck_listing_category($subcategory['categories_id']); ?>
						<a class="cat-link" href="<?= $listc->get_url(); ?>"><?= $subcategory['categories_name']; ?></a>
					<?php }
				} ?>
			</div>
		</div>
	<?php } ?>
			<div style="background-color:#ffffff;">
				<div class="grid">
					<?php $browse->paginator(TRUE); ?>
					<div class="productListingHolder">
						<?php if ($browse->paging->total_results > 0) {
							ck_product_finder_page::build_product_results($browse->results);
						}
						else {
							if (empty($browse->results)) { ?>
								<p style="text-align:center; font-size:12px;">Whoops! We don't have any products here!</p>
							<?php }
						} ?>
					</div>
					<script src="/includes/javascript/jquery-timing.min.js" type="text/javascript"></script>
					<script src="/includes/javascript/jquery.ba-bbq.min.js?v=1" type="text/javascript"></script>
					<script src="/includes/javascript/advanced_search_control.max.js?v=2.0.19"></script>
					<?php $browse->finish_js(TRUE);
					$browse->paginator(TRUE); ?>
				</div>
			</div>

	<?php if (!empty($current_category->is('use_categories_bottom_text'))) { ?>

			<div style="background-color:#ffffff;">
				<div class="grid">
					<?php $cattpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
					$cattpl->buffer = TRUE;

					$content = new ck_content;

					$content->products = [];

					if (!empty($current_category->has('categories_bottom_text_product_ids'))) {
						$product_ids = preg_split('/\s*,\s*/', $current_category->get_header('categories_bottom_text_product_ids'));
						foreach ($product_ids as $product_id) {
							$product = new ck_product_listing($product_id);
							if (!$product->is_viewable()) continue;
							$template = $product->get_thin_template();
							$content->products[] = $template;
							$key = 'prod-'.$product->id();
							$content->$key = $template;
						}
					}

					$category_bottom_text = $cattpl->simple_content($current_category->get_header('categories_bottom_text'), $content);
					echo $category_bottom_text; ?>
				</div>
			</div>
	<?php } ?>
</div>
<script>
jQuery('.buy-now-button').click(function(e){
	var product_id = jQuery(this).attr('data-product-id');
	if(jQuery('#discontinued-'+product_id).val() == 1 && jQuery('#product_quantity-'+product_id).val() <= 0) {
		alert('This item is currently out of stock and has been marked discontinued. Please contact the CK Sales Team if you have any questions.');
		e.preventDefault();
	}
});
jQuery('.show-avail').live('click', function () {
	let product_id = jQuery(this).attr('data-pid');
	jQuery('.avail-details-'+product_id).slideToggle();
});
</script>
