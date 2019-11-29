<?php
$boxitems = [
	'sort' => [0 => 'individual', 'Reports' => 'groups'],
	'individual' => [
		['file' => 'promos', 'text' => 'Promos'],
		['file' => 'custom-page-manager', 'text' => 'Custom Pages'],
		['file' => 'xsell_products.php', 'text' => 'Cross Sell Products'],
		['file' => 'coupon_admin.php', 'text' => 'Coupon Admin'],
		//['file' => 'products_expected.php', 'text' => 'Products Expected'], // old
		//['file' => 'referrals.php', 'text' => 'Referral Sources'], // old
		//['file' => 'salemaker.php', 'text' => 'Salemaker'], // old
		['file' => 'specials.php', 'text' => 'Specials'],
		['file' => 'specials_import_list.php', 'text' => 'Specials Importer'],
		['file' => 'dow_schedule.php', 'text' => 'DOW Schedule'],
		['file' => 'homepage_manager.php', 'text' => 'Homepage Manager'],
		['file' => 'ca-shipping-errors.php', 'text' => 'CA Shipping Errors'],
		//['file' => 'banner_schedule.php', 'text' => 'Banner Schedule'], // future, I think
		['file' => 'pretty_url_manager.php', 'text' => 'Pretty URL Manager'],
		['file' => 'page_includer.php', 'text' => 'Page Includer'],
	],
	'groups' => [
		'Reports' => [
			['file' => 'customer_creation_by_date.php', 'text' => 'Customer Creation Report'],
			['file' => 'image_issues_report.php', 'text' => 'Image Issues'],
			['file' => 'product-feed-exceptions.php', 'text' => 'Product Feed Exceptions'],
			['file' => 'ipn-creation-review-dashboard', 'text' => 'Ipn Creation Review Dashboard']
		],
	],
]; ?>
<h3 id="marketing"><a href="#">Marketing</a></h3>
<div>
	<?php foreach ($boxitems['sort'] as $boxgroup => $boxtype) {
		if ($boxtype == 'groups') { ?>
	<span class="subsection"><?= $boxgroup; ?></span>
		<?php } ?>
	<ul>
		<?php $boxset = $boxtype=='individual'?$boxitems['individual']:$boxitems['groups'][$boxgroup];
		foreach ($boxset as $boxlink) {
			if (tep_admin_check_boxes($boxlink['file'])) { ?>
		<li><a href="/admin/<?= $boxlink['file']; ?>?<?= @$boxlink['parameters']; ?>selected_box=marketing"><?= $boxlink['text']; ?></a></li>
			<?php }
		} ?>
	</ul>
	<?php } ?>
</div>
