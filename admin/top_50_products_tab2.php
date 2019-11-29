<h3>Top Quantity Invoiced Under Stocked IPNs</h3>
<p>This report shows the top 50 products by quantity invoiced in the past 90 days that are currently understocked. Understocked is defined as having fewer days supply than MIN + .25 * (TGT - MIN).</p>
<?php

	$from_date_time = time() - (90 * 24 * 60 * 60);
	$from_date = date('Y-m-d', $from_date_time);
	$tabresults = prepared_query::fetch("SELECT psc.stock_id,
	psc.max_inventory_level,
	(ai_data.total_revenue - ai_data.total_cost) as gross_margin,
	CASE WHEN IFNULL(psc.min_inventory_level, 0) > vtsi.lead_time THEN psc.min_inventory_level ELSE vtsi.lead_time END as lead_factor,
	hist.to180,
	hist.p3060,
	hist.to30,
	if (hist.to180 = null and hist.p3060 = null and hist.to30 = null, 0,
		if (hist.to180/180 <= hist.p3060/30 and hist.to180/180 <= hist.to30/30, if (hist.p3060/30 <= hist.to30/30, hist.p3060/30 , hist.to30/30 ),
			if (hist.p3060/30 <= hist.to180/180 and hist.p3060/30 <= hist.to30/30, if (hist.to180/180 <= hist.to30/30,hist.to180/180,hist.to30/30),
				if (hist.to180/180 <= hist.p3060/30,hist.to180/180,hist.p3060/30) ))) as daily_quantity,
	CASE WHEN psc.serialized = 0 THEN psc.stock_quantity ELSE s.serial_qty END as quantity,
	psc.stock_name as ipn,
	psc.on_order,
	psc.max_inventory_level,
	psc.min_inventory_level,
	psc.target_inventory_level,
	(if(psc.serialized = 0, psc.stock_quantity, s.serial_qty)) as on_hand_quantity,
	((if(psc.serialized = 0, psc.stock_quantity, s.serial_qty)) -
				(if(psc.serialized = 0,ifnull((SELECT SUM(ih.quantity) AS on_hold FROM inventory_hold ih WHERE ih.stock_id = psc.stock_id), 0) ,
				ifnull((select count(1) as on_hold from serials sih where sih.status = 6 and sih.ipn = psc.stock_id), 0)
				)) -
				ifnull((select sum(op3.products_quantity) from orders o3, orders_products op3, products p3 where o3.orders_id = op3.orders_id and (op3.products_id = p3.products_id or ((op3.products_id - p3.products_id) = 0)) and o3.orders_status in (1, 2, 5, 7, 8, 10, 11, 12) and p3.stock_id = psc.stock_id), 0) -
				psc.ca_allocated_quantity)
		as available_quantity,
	pscc.name as category,
	pscv.name as vertical,
	v.vendors_company_name,
	ai_data.total_sold as sold,
	ai_data.total_revenue,
	sd.total_days,
	lpctable.last_stock_price_change_date,
	(target_inventory_level - (target_inventory_level - min_inventory_level) * .75) as lower_supply_threshold

FROM products_stock_control psc
	LEFT JOIN (SELECT ipn as stock_id, COUNT(id) as serial_qty FROM serials WHERE status IN (2,3,6) GROUP BY stock_id) s ON s.stock_id = psc.stock_id
	LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id
	LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id
	left join (select max(change_date) as last_stock_price_change_date, stock_id from products_stock_control_change_history where type_id in (2, 37) group by stock_id) lpctable on lpctable.stock_id = psc.stock_id
	LEFT JOIN (select aii.ipn_id as stock_id, sum(aii.invoice_item_qty) as total_sold, sum(abs(aii.invoice_item_qty) * aii.invoice_item_price) as total_revenue, sum(aii.orders_product_cost_total) as total_cost from acc_invoice_items aii left join orders_products aid_op on (aii.orders_product_id = aid_op.orders_products_id) join acc_invoices ai on aii.invoice_id = ai.invoice_id where aid_op.exclude_forecast = 0 AND ai.inv_date >= '$from_date' group by aii.ipn_id) ai_data on ai_data.stock_id = psc.stock_id
	JOIN ck_cache_sales_history hist ON psc.stock_id = hist.stock_id
	LEFT JOIN (SELECT p.stock_id, COUNT(DISTINCT(DATE(o.date_purchased))) as total_days FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE o.orders_status NOT IN (6, 9) AND o.date_purchased >= '$from_date' GROUP BY p.stock_id) sd ON psc.stock_id = sd.stock_id
	JOIN vendors_to_stock_item vtsi ON psc.vendor_to_stock_item_id = vtsi.id
	JOIN vendors v ON vtsi.vendors_id = v.vendors_id
	having (if(daily_quantity = 0, 999, ceil(available_quantity / daily_quantity) )) < lower_supply_threshold
	ORDER BY sold DESC LIMIT 50", cardinality::SET);

?>
<table id="tab2table" cellspacing="0" cellpadding="0" border="0" class="fc tablesorter">
	<thead>
		<tr>
			<th class="header">Rank</th>
			<th class="header">IPN</th>
			<th class="header">IPN Vertical</th>
			<th class="header">IPN Category</th>
			<th class="header">Min</th>
			<th class="header">Tgt</th>
			<th class="header">Max</th>
			<th class="header">Daily Quantity</th>
			<th class="header">Days Supply</th>
			<th class="header">On Hand Qty</th>
			<th class="header">Available Qty</th>
			<th class="header">On Order</th>
			<th class="header">Last Price Review</th>
			<th class="header">Qty Sold</th>
			<th class="header">6mo Monthly Avg</th>
			<th class="header">31-60 Qty</th>
			<th class="header">0-30 Qty</th>
			<th class="header">Total Rev</th>
			<th class="header">Gross Margin</th>
			<th class="header">Vendor</th>
		</tr>
	</thead>
	<tbody>
	<?php if (!empty($tabresults)) {
		foreach ($tabresults as $idx => $ipn) {

			$ckipn = new ck_ipn2($ipn['stock_id']);

			$single_day = $ipn['daily_quantity'];
			$available_qty = $ckipn->get_inventory('available');
			$days_supply = !$available_qty?0:(!$single_day?'999-':ceil($available_qty/($single_day)));
			$days_indicator = $forecast->days_indicator_color_new($ipn, $days_supply);

			$style_string = "";
			if ($available_qty < 0) {
				$style_string .= "background-color: #FFEE22;";

			} ?>
			<tr style="<?= $style_string; ?>">
				<th><?php echo $idx+1; ?></th>
				<td><a href="/admin/ipn_editor.php?ipnId=<?= $ipn['ipn']; ?>" target="_blank"><?= $ipn['ipn']; ?></a></td>
				<td><?= $ipn['vertical']; ?></td>
				<td><?= $ipn['category']; ?></td>
				<td><?= $ipn['min_inventory_level']; ?></td>
				<td><?= $ipn['target_inventory_level']; ?></td>
				<td><?= $ipn['max_inventory_level']; ?></td>
				<td><?= $ipn['daily_quantity']; ?></td>
				<td style="background-color:#<?= $days_indicator; ?>"><?= $days_supply; ?></td>
				<td><?= $ckipn->get_inventory('on_hand'); ?> </td>
				<td><?= $available_qty; ?> </td>
				<td><?= $ipn['on_order']; ?> </td>
				<?php $last_price_review = new DateTime($ipn['last_stock_price_change_date']);
				$todays_date = new DateTime('now'); ?>
				<td title="<?= $ipn['last_stock_price_change_date']; ?>"><?= $todays_date->diff($last_price_review)->format('%a'); ?></td>
				<td><?= $ipn['sold']; ?></td>
				<td><?php echo round(($ipn['to180'] / 6 ), 2);?></td>
				<td><?= $ipn['p3060']; ?></td>
				<td><?= $ipn['to30']; ?></td>
				<td>$<?php echo number_format($ipn['total_revenue'], 2); ?></td>
				<td>$<?php echo number_format($ipn['gross_margin'], 2); ?></td>
				<td><?= $ipn['vendors_company_name']; ?></td>
			</tr>
		<?php }
	} ?>
	</tbody>
</table>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		jQuery("#tab2table").tablesorter({
			headers: {
				12: { sorter:'digit' }
			}
		});
	});
</script>
