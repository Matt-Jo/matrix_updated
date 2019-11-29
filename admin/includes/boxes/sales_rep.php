<?php
$boxitems = [
	'sort' => [0 => 'individual', 'Reports' => 'groups'],
	'individual' => [
		['file' => 'customer_quote_dashboard.php', 'text' => 'Customer Quotes'],
		['file' => 'customer-quote-dashboard.php', 'text' => 'Customer Quotes 2.0'],
		['file' => 'prospecting_report.php', 'text' => 'Prospecting Report'],
		//['file' => 'sales_rep_dashboard.php', 'text' => 'Dashboard'],
		//['file' => 'sales_rep_commission.php', 'text' => 'My Sales'],
		//['file' => 'product_list.php', 'text' => 'Product List'],
		//['file' => 'recover_cart_sales.php', 'text' => 'Recover Cart Sales'],
		['file' => 'product_notifications.php', 'text' => 'Stock Notifications'],
		['file' => 'my-orders', 'text' => 'My Orders'],
		//['file' => 'broker_orders.php', 'text' => 'Broker Orders'],
		//['file' => 'broker_invoice_stats.php', 'text' => 'Broker Invoicing'],
		//['file' => 'end_user_invoice_stats.php', 'text' => 'End User Invoicing']
	],
	'groups' => [
		'Reports' => [
			['file' => 'advanced_search.php', 'text' => 'Advanced Search'],
			//['file' => 'stats_recover_cart_sales.php', 'text' => 'Recovered Carts'],
			['file' => 'stock_liquidation_list.php', 'text' => 'Project Ex-Lax'],
			['file' => 'price_change_history_report.php', 'text' => 'Price Changes'],
			['file' => 'sales-commission-report.php', 'text' => 'Sales Performance Report']
		],
	]
]; ?>
<h3 id="sales"><a href="#">Sales</a></h3>
<div>
	<?php foreach ($boxitems['sort'] as $boxgroup => $boxtype) {
		if ($boxtype == 'groups') { ?>
	<span class="subsection"><?= $boxgroup; ?></span>
		<?php } ?>
	<ul>
		<?php $boxset = $boxtype=='individual'?$boxitems['individual']:$boxitems['groups'][$boxgroup];
		foreach ($boxset as $boxlink) {
			if (tep_admin_check_boxes($boxlink['file'])) { ?>
		<li><a href="/admin/<?= $boxlink['file']; ?>?<?= @$boxlink['parameters']; ?>selected_box=sales"><?= $boxlink['text']; ?></a></li>
			<?php }
		} ?>
	</ul>
	<?php } ?>
</div>
