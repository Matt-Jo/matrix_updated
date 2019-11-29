<?php
$boxitems = [
	'id' => 'inventory',
	'title' => 'Inventory',
	'sort' => [0 => 'individual', 'Maintenance' => 'groups', 'Reports' => 'groups'],
	'individual' => [
		['file' => 'categories.php', 'text' => 'Categories/Products'],
		['file' => 'ipn_editor.php', 'text' => 'IPN Editor'],
	],
	'groups' => [
		'Maintenance' => [
			['file' => 'quick-ipn-create.php', 'text' => 'Create New IPN'],
			['file' => 'serials-swap.php', 'text' => 'Serials Swap'],
			['file' => 'inventory_adjustment_report.php', 'text' => 'Inventory Adjustment Report'],
			['file' => 'product_image_reviewer.php', 'text' => 'Review Product Images'],
			['file' => 'content_reviews.php', 'text' => 'Content Reviews'],
			['file' => 'import_ipn_from_csv.php', 'text' => 'IPN Import'],
			['file' => 'ipn-export.php', 'text' => 'IPN Export'],
			['file' => 'ipn_with_products_off.php', 'text' => 'IPN\'s With Products Off'],
			['file' => 'manufacturers.php', 'text' => 'Manufacturers'],
			['file' => 'manage_products.php', 'text' => 'Manage Products'],
			['file' => 'manage_attributes.php', 'text' => 'Manage Attributes'],
			['file' => 'stats_weight_compare.php', 'text' => 'Weight Compare'],
			['file' => 'inventory_conversion.php', 'text' => 'Inventory Conversion'],
			['file' => 'update_serial_bin.php', 'text' => 'Serial Bin Locations'],
			['file' => 'finish_serial_conditioning.php', 'text' => 'Finish Serial Conditioning'],
		],
		'Reports' => [
			['file' => 'endangered-ipns.php', 'text' => 'Endangered IPNs'],
			//['file' => 'no_sales_report.php', 'text' => 'No Sale Report'],
			['file' => 'qty_change_report.php', 'text' => 'Qty Change Report'],
			['file' => 'zero_weight_report.php', 'text' => 'Zero Weight Report'],
		],
	]
]; ?>
<h3 id="<?= $boxitems['id']; ?>"><a href="#"><?= $boxitems['title']; ?></a></h3>
<div>
	<?php foreach ($boxitems['sort'] as $boxgroup => $boxtype) {
		if ($boxtype == 'groups') { ?>
	<span class="subsection"><?= $boxgroup; ?></span>
		<?php } ?>
	<ul>
		<?php $boxset = $boxtype=='individual'?$boxitems['individual']:$boxitems['groups'][$boxgroup];
		foreach ($boxset as $boxlink) {
			if (tep_admin_check_boxes($boxlink['file'])) { ?>
		<li><a href="/admin/<?= $boxlink['file']; ?>?<?= @$boxlink['parameters']; ?>selected_box=<?= $boxitems['id']; ?>"><?= $boxlink['text']; ?></a></li>
			<?php }
		} ?>
	</ul>
	<?php } ?>
</div>
