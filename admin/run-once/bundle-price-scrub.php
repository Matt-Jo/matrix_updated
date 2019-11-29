<?php
chdir('../..');
require_once('includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

@ini_set("memory_limit","2048M");
set_time_limit(0);

debug_tools::mark('START');

try {
	$bundles = prepared_query::fetch('SELECT op.orders_products_id, op.orders_id, op.products_id, op.parent_products_id, op.option_type, op.products_quantity, op.products_price, op.final_price, psc.stock_id, psc.stock_price FROM orders_products op JOIN orders o ON op.orders_id = o.orders_id JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id AND psc.is_bundle = 1 WHERE op.option_type = 0 AND op.products_price > 0.1 AND op.final_price > 0.1 AND o.date_purchased >= :bundle_start_date ORDER BY o.date_purchased ASC', cardinality::SET, [':bundle_start_date' => '2015-05-29']);

	debug_tools::mark('Done grabbing bundle items; Count '.count($bundles));

	debug_tools::start_sub_timer('Spread pricing');

	foreach ($bundles as $bidx => $bundle) {
		$bundle_revenue = $bundle['final_price'];

		$children = prepared_query::fetch('SELECT op.*, psc.stock_price FROM orders_products op JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id AND psc.is_bundle = 0 WHERE op.parent_products_id = :parent_products_id AND op.orders_id = :orders_id AND op.option_type = 3', cardinality::SET, [':parent_products_id' => $bundle['products_id'], ':orders_id' => $bundle['orders_id']]);

		$running_total = 0;

		$bundle_raw_total = array_reduce($children, function($total, $child) use ($bundle) {
			$multiple = $child['products_quantity'] / $bundle['products_quantity'];
			$total = bcadd($total, bcmul($child['stock_price'], $multiple, 6), 6);
			return $total;
		}, 0);

		foreach ($children as $cidx => $child) {
			$bundle_multiple = $child['products_quantity'] / $bundle['products_quantity'];

			// using stock price - not using any interim bundles to re-orient pricing ratio
			$item_ratio = bcdiv(bcmul($child['stock_price'], $bundle_multiple, 6), $bundle_raw_total, 6);

			$child_revenue = bcmul($bundle_revenue, $item_ratio, 6);

			$child_products_price = bcdiv($child_revenue, $bundle_multiple, 6);

			$running_total = bcadd($running_total, $child_revenue, 6);

			prepared_query::execute('UPDATE orders_products SET products_price = :products_price WHERE orders_products_id = :orders_products_id', [':products_price' => $child_products_price, ':orders_products_id' => $child['orders_products_id']]);

			prepared_query::execute('UPDATE acc_invoice_items ii JOIN acc_invoices i ON ii.invoice_id = i.invoice_id SET ii.revenue = :products_price WHERE ii.orders_product_id = :orders_products_id AND i.inv_order_id = :orders_id', [':products_price' => $child_products_price, ':orders_products_id' => $child['orders_products_id'], ':orders_id' => $bundle['orders_id']]);
		}

		$bundle_products_price = bcsub($bundle['final_price'], $running_total, 6);

		prepared_query::execute('UPDATE orders_products SET products_price = :products_price WHERE orders_products_id = :orders_products_id', [':products_price' => $bundle_products_price, ':orders_products_id' => $bundle['orders_products_id']]);

		prepared_query::execute('UPDATE acc_invoice_items ii JOIN acc_invoices i ON ii.invoice_id = i.invoice_id SET ii.revenue = :products_price WHERE ii.orders_product_id = :orders_products_id AND i.inv_order_id = :orders_id', [':products_price' => $bundle_products_price, ':orders_products_id' => $bundle['orders_products_id'], ':orders_id' => $bundle['orders_id']]);

		if (($bidx+1)%500 == 0) debug_tools::mark('Completed '.($bidx+1).' Bundles');
	}

	debug_tools::mark('Completed '.($bidx+1).' Bundles');
}
catch (Exception $e) {
	echo $e->getMessage();
}

debug_tools::clear_sub_timer_context();

debug_tools::mark('Done');
?>
