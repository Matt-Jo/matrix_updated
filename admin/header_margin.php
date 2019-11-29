<?php require('includes/application_top.php');

$todays_margin = prepared_query::fetch("SELECT i.invoice_date, i.number_of_invoices, i.product_total, i.shipping_total, i.tax_total, i.invoice_total, i.product_cost_total, i.product_total - i.product_cost_total as product_margin_total, i.product_total + i.shipping_total - i.product_cost_total as margin_total, 'current' as status FROM (SELECT DATE(ai.inv_date) as invoice_date, IFNULL(COUNT(DISTINCT ai.invoice_id), 0) as number_of_invoices, IFNULL(SUM(ai_total.total), 0) - IFNULL(SUM(ai_tax.total_tax), 0) - IFNULL(SUM(ai_shipping.total_shipping), 0) as product_total, IFNULL(SUM(ai_shipping.total_shipping), 0) as shipping_total, IFNULL(SUM(ai_tax.total_tax), 0) as tax_total, IFNULL(SUM(ai_total.total), 0) as invoice_total, IFNULL(SUM(ii.total_cost), 0) as product_cost_total FROM acc_invoices ai LEFT JOIN (SELECT ait.invoice_id, SUM(ait.invoice_total_price) as total_shipping FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_shipping' WHERE ai.inv_date >= DATE(NOW()) GROUP BY ait.invoice_id) ai_shipping ON ai.invoice_id = ai_shipping.invoice_id LEFT JOIN (SELECT ait.invoice_id, SUM(ait.invoice_total_price) as total_tax FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_tax' WHERE ai.inv_date >= DATE(NOW()) GROUP BY ait.invoice_id) ai_tax ON ai.invoice_id = ai_tax.invoice_id LEFT JOIN (SELECT ait.invoice_id, SUM(ait.invoice_total_price) as total FROM acc_invoices ai JOIN acc_invoice_totals ait ON ai.invoice_id = ait.invoice_id AND ait.invoice_total_line_type = 'ot_total' WHERE ai.inv_date >= DATE(NOW()) GROUP BY ait.invoice_id) ai_total ON ai.invoice_id = ai_total.invoice_id LEFT JOIN (SELECT ii.invoice_id, SUM(ii.orders_product_cost_total) as total_cost FROM acc_invoices ai JOIN acc_invoice_items ii ON ai.invoice_id = ii.invoice_id WHERE ai.inv_date >= DATE(NOW()) GROUP BY ii.invoice_id) ii ON ai.invoice_id = ii.invoice_id WHERE ai.inv_date >= DATE(NOW()) AND (ai.inv_order_id IS NOT NULL OR ai.rma_id IS NOT NULL) GROUP BY DATE(ai.inv_date) ORDER BY ai.inv_date DESC) i UNION SELECT DATE(NOW()) as invoice_date, o.number_of_orders as number_of_invoices, o.product_total, o.shipping_total, o.tax_total, o.order_total as invoice_total, o.product_cost_total, o.product_total - o.product_cost_total as product_margin_total, o.product_total + o.shipping_total - o.product_cost_total as margin_total, 'expected' as status FROM (SELECT IFNULL(COUNT(DISTINCT o.orders_id), 0) as number_of_orders, IFNULL(SUM(op.product_total), 0) as pt, IFNULL(SUM(ot_total.total), 0) - IFNULL(SUM(ot_tax.total_tax), 0) - IFNULL(SUM(ot_shipping.total_shipping), 0) as product_total, IFNULL(SUM(ot_shipping.total_shipping), 0) as shipping_total, IFNULL(SUM(ot_tax.total_tax), 0) as tax_total, IFNULL(SUM(ot_total.total), 0) as order_total, IFNULL(SUM(op.total_cost), 0) as product_cost_total FROM orders o LEFT JOIN (SELECT ot.orders_id, SUM(ot.value) as total_shipping FROM orders o JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_shipping' WHERE o.orders_status IN (2, 7) GROUP BY ot.orders_id) ot_shipping ON o.orders_id = ot_shipping.orders_id LEFT JOIN (SELECT ot.orders_id, SUM(ot.value) as total_tax FROM orders o JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_tax' WHERE o.orders_status IN (2, 7) GROUP BY ot.orders_id) ot_tax ON o.orders_id = ot_tax.orders_id LEFT JOIN (SELECT ot.orders_id, SUM(ot.value) as total FROM orders o JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total' WHERE o.orders_status IN (2, 7) GROUP BY ot.orders_id) ot_total ON o.orders_id = ot_total.orders_id JOIN (SELECT op.orders_id, SUM(op.products_quantity * op.final_price) as product_total, SUM(op.products_quantity * IF(psc.serialized = 1, s.average_cost, psc.average_cost)) as total_cost FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN ckv_average_serial_cost s ON p.stock_id = s.stock_id WHERE o.orders_status IN (2, 7) AND op.products_quantity <= IF(psc.serialized = 1, s.on_hand, psc.stock_quantity) GROUP BY op.orders_id) op ON o.orders_id = op.orders_id WHERE o.orders_status IN (2, 7)) o", cardinality::SET);

$margins = [
	'current' => ['product_margin' => CK\text::monetize(0), 'total_margin' => CK\text::monetize(0), 'product_margin_percent' => '---', 'total_margin_percent' => '---'],
	'expected' => ['product_margin' => CK\text::monetize(0), 'total_margin' => CK\text::monetize(0), 'product_margin_percent' => '---', 'total_margin_percent' => '---'],
	'product_margin_total' => 0,
	'total_margin_total' => 0,
];
foreach ($todays_margin as $margin) {
	$margins[$margin['status']]['product_margin'] = CK\text::monetize($margin['product_margin_total']);
	if ($margin['product_total'] != 0) {
		$margins[$margin['status']]['product_margin_percent'] = number_format((($margin['product_margin_total'] / $margin['product_total']) * 100), 2).'%';
	}

	$margins[$margin['status']]['total_margin'] = CK\text::monetize($margin['margin_total']);
	if ($margin['invoice_total'] - $margin['tax_total'] != 0) {
		$margins[$margin['status']]['total_margin_percent'] = number_format((($margin['margin_total'] / ($margin['invoice_total'] - $margin['tax_total'])) * 100), 2).'%';
	}

	$margins['product_margin_total'] += $margin['product_margin_total'];
	$margins['total_margin_total'] += $margin['margin_total'];
}
?>
<table style="font-size: 12px;">
	<tr>
		<th align="left"><a href="/admin/stats_invoices.php?selected_box=accounting" style="font-size: 10px; text-decoration:underline; color: blue;">STATS</a></th>
		<th align="left" style="padding-left: 10px;">Product Margin</th>
		<th align="left" style="padding-left: 25px;">Total Margin</th>
	</tr>
	<tr>
		<td><b>Current</b></td>
		<td style="padding-left: 10px;"><?= $margins['current']['product_margin']; ?> (<?= $margins['current']['product_margin_percent']; ?>)</td>
		<td style="padding-left: 25px;"><?= $margins['current']['total_margin']; ?> (<?= $margins['current']['total_margin_percent']; ?>)</td>
	</tr>
	<tr>
		<td><b>Additional</b></td>
		<td style="padding-left: 10px;"><?= $margins['expected']['product_margin']; ?></td>
		<td style="padding-left: 25px;"><?= $margins['expected']['total_margin']; ?></td>
	</tr>
	<tr>
		<td></td>
		<td style="padding-left: 10px; border-top: 1px solid black;"><?= CK\text::monetize($margins['product_margin_total']); ?></td>
		<td style="padding-left: 25px; border-top: 1px solid black;"><?= CK\text::monetize($margins['total_margin_total']); ?></td>
	</tr>
</table>
