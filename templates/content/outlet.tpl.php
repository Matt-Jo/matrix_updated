<link rel="stylesheet" href="/images/static/css/product-finder.css">
	<div class="centertable">
		<div class="leftTd">
			<div class="mob_only cat_select"><span>Filter</span></div>
			<table cellspacing="0" cellpadding="8px" border="0" class="leftFiler">
			<?php if (isset($browse) && is_object($browse) && $browse instanceof nav_service_interface) { ?>
				<tr><td><?php $browse->refinements(TRUE); ?></td></tr>
			<?php } ?>
			</table>
		</div>
		<div style="margin-top:10px; border-style:solid; border-color:#cecece; border-width:0px 0px 2px 1px;">
			<div class="tools">
				<?= $breadcrumb->trail(); ?>
			</div>
			<div class="productListing">
				<div style="background-color:#ffffff;">
					<div class="grid">
						<?php $browse->paginator(TRUE); ?>
						<div class="productListingHolder">
							<?php if ($browse->paging->total_results > 0) {
								ck_product_finder_page::build_product_results($browse->results);
							}
							else { ?>
								<?php if (empty($browse->results)) { ?>
									<p style="text-align:center; font-size:12px;">Whoops! We don't have any products here!</p>
								<?php }
							} ?>
						</div>
						<script src="/includes/javascript/jquery-timing.min.js" type="text/javascript"></script>
						<script src="/includes/javascript/jquery.ba-bbq.min.js?v=1" type="text/javascript"></script>
						<script type="text/javascript" src="includes/javascript/advanced_search_control.max.js?v=2.0.19"></script>
						<?php $browse->finish_js(TRUE);
						$browse->paginator(TRUE); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<script>
jQuery('.buy-now-button').click(function(e){
	var product_id = jQuery(this).attr('data-product-id');
	if(jQuery('#discontinued-'+product_id).val() == 1 && jQuery('#product_quantity-'+product_id).val() <= 0) {
		alert('This item is currently out of stock and has been marked discontinued. Please contact the CK Sales Team if you have any questions.');
		e.preventDefault();
	}
});
jQuery('.show-avail').on('click', function (e) {
	e.preventDefault();
	let product_id = jQuery(this).attr('data-pid');
	jQuery('.avail-details-'+product_id).slideToggle();
});
</script>
