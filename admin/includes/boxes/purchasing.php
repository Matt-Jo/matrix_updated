<?php
$boxitems = [
	'id' => 'purchasing',
	'title' => 'Purchasing',
	'sort' => [0 => 'individual', 'Reports' => 'groups', 'Vendor Portal' => 'groups'],
	'individual' => [
		['file' => 'create_vendor_account.php', 'text' => 'Create Vendor'],
		['file' => 'po_list.php', 'text' => 'Purchase Orders'],
		['file' => 'stock_reorder_report.php', 'text' => 'Stock Reorder List'],
		['file' => 'urgent_order_list.php', 'text' => 'Urgent Order List'],
		['file' => 'vendors.php', 'text' => 'Vendors'],
		['file' => 'merge_vendors.php', 'text' => 'Merge Vendors'],
		['file' => 'upload-suggested-buys', 'text' => 'Upload Suggested Buys'],
		//['file' => 'ipn_recommended_orders_by_vendor.php', 'text' => 'Vendor Stock Reorder Report'],
	],
	'groups' => [
		'Reports' => [
			['file' => 'top_50_products.php', 'text' => 'Top 50 Products'],
			['file' => 'inventory_aging_report.php', 'text' => 'Inventory Aging'],
			['file' => 'vendor_return_report.php', 'text' => 'Vendor Return Report'],
			//['file' => 'backorder_release_report.php', 'text' => 'Backorder Release Report'],
			['file' => 'ipn_without_preferred_vendor.php', 'text' => 'IPNs Without Preferred Vendors'],
			['file' => 'price_management_report.php', 'text' => 'Price Management Report'],
			['file' => 'recent_product_sales.php', 'text' => 'Recent Product Sales'],
			['file' => 'included_accessories_report.php', 'text' => 'Included Accessories Report'],
			['file' => 'wholesale-price-worksheet', 'text' => 'Wholesale Price Worksheet']
		],
		'Vendor Portal' => [
			['file' => 'rfq_list.php', 'text' => 'Request Manager'],
			['file' => 'rfq_detail.php', 'text' => 'Request Builder', 'parameters' => 'action=blank&'],
		]
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
