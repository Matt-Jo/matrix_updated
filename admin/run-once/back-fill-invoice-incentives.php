<?php
require_once(__DIR__.'/../../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

@ini_set("memory_limit","2048M");
set_time_limit(0);

debug_tools::mark('START');

try {
	$start_date = new DateTime('2018-07-01');
	// grab all of the invoices we'll be affecting
	$invoices = prepared_query::fetch('SELECT DISTINCT invoice_id, inv_date, inv_order_id, customer_id FROM acc_invoices WHERE inv_order_id IS NOT NULL AND credit_memo = 0 AND inv_date >= :start_date AND incentive_final_total IS NULL', cardinality::SET, [':start_date' => $start_date->format('Y-m-d')]);

	debug_tools::mark('Done grabbing invoices - '.count($invoices));

	$tiers = ck_sales_incentive_tier_lookup::instance()->get_list('active-basic', NULL, TRUE); // tiers are sorted by ascending incentive base

	foreach ($invoices as $inv) {
		$invoice = new ck_invoice($inv['invoice_id']);
		$customer = new ck_customer2($inv['customer_id']);
		$sales_order = new ck_sales_order($inv['inv_order_id']);

		$first_invoice_date = $sales_order->get_first_invoice_date();

		if (!empty($first_invoice_date) && $first_invoice_date < $start_date) continue;

		$sales_incentive_tier_id = NULL;
		$incentive_percentage = NULL;
		$incentive_base_total = NULL;
		$incentive_product_total = $invoice->get_final_product_subtotal() - $invoice->get_product_cost();
		$incentive_final_total = NULL;

		if ($customer->has_account_manager()) {
			$base_total = !empty($first_invoice_date)?$customer->get_incentive_base_total($first_invoice_date):$customer->get_incentive_base_total($invoice->get_header('invoice_date'));

			$incentive_base_total = $base_total + $incentive_product_total;

			foreach ($tiers as $tier) {
				if ($incentive_base_total < $tier['incentive_base']) break;
				$sales_incentive_tier_id = $tier['sales_incentive_tier_id'];
				$incentive_percentage = $tier['incentive_percentage'];
			}
		}
		elseif ($sales_order->has_account_manager() && $incentive_product_total >= 250) $incentive_percentage = '0.15';

		$incentive_final_total = bcmul($incentive_percentage, $incentive_product_total, 4);
		if ($incentive_final_total <= 0) $incentive_final_total = NULL;

		prepared_query::execute('UPDATE acc_invoices SET sales_incentive_tier_id = :sales_incentive_tier_id, incentive_percentage = :incentive_percentage, incentive_base_total = :incentive_base_total, incentive_product_total = :incentive_product_total, incentive_final_total = :incentive_final_total WHERE invoice_id = :invoice_id', [':invoice_id' => $invoice->id(), ':sales_incentive_tier_id' => $sales_incentive_tier_id, ':incentive_percentage' => $incentive_percentage, ':incentive_base_total' => $incentive_base_total, ':incentive_product_total' => $incentive_product_total, ':incentive_final_total' => $incentive_final_total]);
	}

	debug_tools::mark('Done updating sales invoices');

	prepared_query::execute('UPDATE acc_invoices cm JOIN acc_invoices i ON cm.original_invoice = i.invoice_id SET cm.incentive_percentage = i.incentive_percentage, cm.incentive_product_total = -1 * i.incentive_product_total, cm.incentive_final_total = -1 * i.incentive_final_total WHERE cm.inv_order_id IS NOT NULL AND cm.credit_memo = 1 AND cm.inv_date >= :start_date AND cm.incentive_final_total IS NULL', [':start_date' => $start_date->format('Y-m-d')]);

	debug_tools::mark('Done updating credit memos');

	prepared_query::execute('UPDATE acc_invoices ri JOIN rma ON ri.rma_id = rma.id JOIN acc_invoices i ON rma.order_id = i.inv_order_id SET ri.incentive_percentage = i.incentive_percentage WHERE ri.rma_id IS NOT NULL AND ri.inv_date >= :start_date AND ri.incentive_final_total IS NULL', [':start_date' => $start_date->format('Y-m-d')]);

	debug_tools::mark('Done initial updating RMA invoices');

	$rmas = prepared_query::fetch('SELECT DISTINCT i.invoice_id, i.rma_id, i.incentive_percentage, SUM(ABS(ii.invoice_item_price * ii.invoice_item_qty)) as revenue, SUM(ABS(ii.orders_product_cost_total)) as cost FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE i.rma_id IS NOT NULL AND i.inv_date >= :start_date GROUP BY i.invoice_id', cardinality::SET, [':start_date' => $start_date->format('Y-m-d')]);

	debug_tools::mark('Done grabbing RMA invoices - '.count($rmas));

	foreach ($rmas as $rma) {
		$invoice = new ck_invoice($rma['invoice_id']);
		$ckrma = new ck_rma2($rma['rma_id']);

		$totals = $invoice->get_simple_totals();

		$incentive_product_total = $rma['cost'] - $rma['revenue'];
		if ($ckrma->is('refund_coupon') && $ckrma->get_header('coupon_amount') > 0) $incentive_product_total += abs($ckrma->get_header('coupon_amount'));
		if ($ckrma->has_restock_percentage()) $incentive_product_total += abs($rma['revenue'] * $ckrma->get_restock_percentage());

		$incentive_final_total = bcmul($rma['incentive_percentage'], $incentive_product_total, 4);

		prepared_query::execute('UPDATE acc_invoices SET incentive_product_total = :incentive_product_total, incentive_final_total = :incentive_final_total WHERE invoice_id = :invoice_id', [':invoice_id' => $invoice->id(), ':incentive_product_total' => $incentive_product_total, ':incentive_final_total' => $incentive_final_total]);
	}

	debug_tools::mark('Done updating RMA invoices');

	prepared_query::execute('UPDATE acc_invoices SET incentive_accrued = :date, incentive_paid = :date WHERE incentive_final_total IS NULL', [':date' => $start_date->format('Y-m-d')]);

	debug_tools::mark('Pay and Accrue unhandled invoices');
}
catch (Exception $e) {
	echo $e->getMessage();
}

debug_tools::mark('Done');
?>
