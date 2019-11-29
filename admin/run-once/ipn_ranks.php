<?php
require_once(__DIR__.'/../../includes/application_top.php');
set_time_limit(0);

try {
	debug_tools::mark('Start');

	$dates = [
		'today' => new DateTime(),
		'0-30' => new DateTime(),
		'0-60' => new DateTime(),
		'0-90' => new DateTime(),
	];

	$dates['today']->sub(new DateInterval('P1D'));
	$dates['0-30']->sub(new DateInterval('P30D'));
	$dates['0-60']->sub(new DateInterval('P60D'));
	$dates['0-90']->sub(new DateInterval('P90D'));

	prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE DATE(i.inv_date) = :today GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date = :today GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_last_day = IFNULL(ii.gross_margin_dollars, 0), psc.usage_last_day = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0)', [':today' => $dates['today']->format('Y-m-d')]);

	prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE DATE(i.inv_date) >= :thirty GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :thirty GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_0_30 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_0_30= IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0)', [':thirty' => $dates['0-30']->format('Y-m-d')]);

	prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE DATE(i.inv_date) >= :sixty GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :sixty GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_0_60 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_0_60 = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0)', [':sixty' => $dates['0-60']->format('Y-m-d')]);

	prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE DATE(i.inv_date) >= :ninety GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :ninety GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_0_90 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_0_90 = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0)', [':ninety' => $dates['0-90']->format('Y-m-d')]);


	// honor excluded flag


	prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id JOIN orders_products op ON ii.orders_product_id = op.orders_products_id AND op.exclude_forecast = 0 WHERE DATE(i.inv_date) = :today GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date = :today GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_excluded_last_day = IFNULL(ii.gross_margin_dollars, 0), psc.usage_excluded_last_day = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0)', [':today' => $dates['today']->format('Y-m-d')]);

	prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id JOIN orders_products op ON ii.orders_product_id = op.orders_products_id AND op.exclude_forecast = 0 WHERE DATE(i.inv_date) >= :thirty GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :thirty GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_excluded_0_30 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_excluded_0_30= IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0)', [':thirty' => $dates['0-30']->format('Y-m-d')]);

	prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id JOIN orders_products op ON ii.orders_product_id = op.orders_products_id AND op.exclude_forecast = 0 WHERE DATE(i.inv_date) >= :sixty GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :sixty GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_excluded_0_60 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_excluded_0_60 = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0)', [':sixty' => $dates['0-60']->format('Y-m-d')]);

	prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id JOIN orders_products op ON ii.orders_product_id = op.orders_products_id AND op.exclude_forecast = 0 WHERE DATE(i.inv_date) >= :ninety GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :ninety GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_excluded_0_90 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_excluded_0_90 = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0)', [':ninety' => $dates['0-90']->format('Y-m-d')]);

	debug_tools::mark('Finished');
}
catch (Exception $e) {
	echo $e->getMessage();
}
?>