<link rel="stylesheet" href="/images/static/css/product-finder.css">
<style>
	.availablity-label { display:block; }
</style>
<div style="background-color:#fff;">
	<div class="grid">
		<div class="productListing">
			<?php $search->paginator(TRUE); ?>
			<div class="productListingHolder">
				<?php if ($search->paging->total_results > 0) {
					ck_product_finder_page::build_product_results($search->results);
				}
				else { ?>
					<div class="main"><br><span style="font-size:11px;">No results were found for: <b><?= stripslashes($_GET['keywords']); ?></b></span><br></div>
					<div class="main">
						<p>Our site search needs help and we know it! Please bear with us as we work to improve our search to better help you find what you are looking for.</p>
						<p>In the meantime please try the following tips:</p>
						<ul>
							<li>Search for fewer words (Search for "2600 rack" or "2600" instead of "2600 series rack mount kit")</li>
							<li>Use singular words like "cable" instead of "cables"</li>
							<li>Search using the series instead of the model number (Search for "2600 rack" instead of "2610 rack" if looking for a 2610 rack mount kit)</li>
							<li>Try browsing the categories on the left to find what you are looking for</li>
						</ul>
						<p>If you have any trouble finding what you are looking for please give us a call, message us on <a style="font-weight: bold; color:#0000ff;" onclick="openLiveHelp(); return false" target="_blank" href="#">Live Chat</a>, or send us an <a style="font-weight: bold; color:#0000ff;" href="custserv.php">email</a>. We will be more than happy to help you find what you need. Also, keep in mind that just because you do not see it doesn't mean we cannot get it for you. We have access to tens of thousands of items not currently listed on our store!</p>
					</div>
				<?php } ?>
			</div>
			<script src="/includes/javascript/jquery-timing.min.js" type="text/javascript"></script>
			<script src="/includes/javascript/jquery.ba-bbq.min.js?v=1" type="text/javascript"></script>
			<script type="text/javascript" src="/includes/javascript/advanced_search_control.max.js?v=2.0.19"></script>
			<?php $search->finish_js(TRUE);
			$search->paginator(TRUE); ?>
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
