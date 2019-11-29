<?php
require_once(__DIR__.'/../../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

@ini_set("memory_limit","2048M");
set_time_limit(0);

$params = [':ebay' => 'ebay', ':ebay_customers_id' => 161001];

// FIRST, BACKUP acc_transaction_history, acc_payments, acc_invoices, orders, AND address_book

debug_tools::mark('START');

try {
	// grab all of the customers we'll be affecting
	$customers = prepared_query::fetch('SELECT DISTINCT customers_id FROM orders WHERE channel = :ebay AND customers_id != :ebay_customers_id ORDER BY customers_id ASC', cardinality::COLUMN, $params);

	debug_tools::mark('Done grabbing customers');

	debug_tools::start_sub_timer('Merge Records');

	// update all transaction history tied to ebay orders
	prepared_query::execute('UPDATE acc_transaction_history th JOIN orders o ON th.order_id = o.orders_id SET th.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);
	// ... and tied to invoices tied to ebay orders
	prepared_query::execute('UPDATE acc_transaction_history th JOIN acc_invoices i ON th.invoice_id = i.invoice_id JOIN orders o ON i.inv_order_id = o.orders_id SET th.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);
	// ... and tied to invoices tied to rmas tied to ebay orders
	prepared_query::execute('UPDATE acc_transaction_history th JOIN acc_invoices i ON th.invoice_id = i.invoice_id JOIN rma ON i.rma_id = rma.id JOIN orders o ON rma.order_id = o.orders_id SET th.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);
	// ... and tied to payments tied to ebay orders
	prepared_query::execute('UPDATE acc_transaction_history th JOIN acc_payments_to_orders pto ON th.payment_id = pto.payment_id JOIN orders o ON pto.order_id = o.orders_id SET th.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);
	// ... and tied to payments tied to invoices tied to ebay orders
	prepared_query::execute('UPDATE acc_transaction_history th JOIN acc_payments_to_invoices pti ON th.payment_id = pti.payment_id JOIN acc_invoices i ON pti.invoice_id = i.invoice_id JOIN orders o ON i.inv_order_id = o.orders_id SET th.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);
	// ... and tied to payments tied to invoices tied to rmas tied to ebay orders
	prepared_query::execute('UPDATE acc_transaction_history th JOIN acc_payments_to_invoices pti ON th.payment_id = pti.payment_id JOIN acc_invoices i ON pti.invoice_id = i.invoice_id JOIN rma ON i.rma_id = rma.id JOIN orders o ON rma.order_id = o.orders_id SET th.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);

	debug_tools::mark('Done with acc_transaction_history');

	// update all payments tied to ebay orders
	prepared_query::execute('UPDATE acc_payments p JOIN acc_payments_to_orders pto ON p.payment_id = pto.payment_id JOIN orders o ON pto.order_id = o.orders_id SET p.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);
	// ... and tied to invoices tied to ebay orders
	prepared_query::execute('UPDATE acc_payments p JOIN acc_payments_to_invoices pti ON p.payment_id = pti.payment_id JOIN acc_invoices i ON pti.invoice_id = i.invoice_id JOIN orders o ON i.inv_order_id = o.orders_id SET p.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);
	// ... and tied to invoices tied to rmas tied to ebay orders
	prepared_query::execute('UPDATE acc_payments p JOIN acc_payments_to_invoices pti ON p.payment_id = pti.payment_id JOIN acc_invoices i ON pti.invoice_id = i.invoice_id JOIN rma ON i.rma_id = rma.id JOIN orders o ON rma.order_id = o.orders_id SET p.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);

	debug_tools::mark('Done with acc_payments');

	// update all invoices tied to ebay orders
	prepared_query::execute('UPDATE acc_invoices i JOIN orders o ON i.inv_order_id = o.orders_id SET i.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);
	// ... and tied to rmas tied to ebay orders
	prepared_query::execute('UPDATE acc_invoices i JOIN rma ON i.rma_id = rma.id JOIN orders o ON rma.order_id = o.orders_id SET i.customer_id = :ebay_customers_id WHERE o.channel = :ebay AND o.customers_id != :ebay_customers_id', $params);

	debug_tools::mark('Done with invoices');

	// update all ebay orders
	prepared_query::execute('UPDATE orders SET customers_id = :ebay_customers_id WHERE channel = :ebay AND customers_id != :ebay_customers_id', $params);

	debug_tools::mark('Done with orders');

	debug_tools::clear_sub_timer_context();

	// capture the list of customers we've deleted
	$affected_customers = [];

	debug_tools::start_sub_timer('Delete Customers');

	// loop through all of the customers that used to have ebay orders, and see if we need to keep them
	foreach ($customers as $idx => $customers_id) {
		if (($idx+1)%2000 == 0) debug_tools::mark('Completed '.($idx+1).' Customer Records; '.count($affected_customers).' Deleted');

		$cparam = [':customers_id' => $customers_id];
		// if this customer still has any orders, we want to keep them
		if (prepared_query::fetch('SELECT 1 FROM orders WHERE customers_id = :customers_id', cardinality::SINGLE, $cparam)) continue;

		// if this customer still has any invoices, we want to keep them
		if (prepared_query::fetch('SELECT 1 FROM acc_invoices WHERE customer_id = :customers_id', cardinality::SINGLE, $cparam)) continue;

		// if this customer still has any payments, we want to keep them
		if (prepared_query::fetch('SELECT 1 FROM acc_payments WHERE customer_id = :customers_id', cardinality::SINGLE, $cparam)) continue;

		// if this customer has any rfq responses, we want to keep them
		if (prepared_query::fetch('SELECT 1 FROM ck_rfq_responses WHERE customers_id = :customers_id', cardinality::SINGLE, $cparam)) continue;

		// if this customer has any extra logins, we want to keep them
		if (prepared_query::fetch('SELECT 1 FROM customers_extra_logins WHERE customers_id = :customers_id', cardinality::SINGLE, $cparam)) continue;

		// if we get here, force any remaining transaction history records to the ebay customer, and delete the customer, with its address book
		prepared_query::execute('UPDATE acc_transaction_history SET customer_id = :ebay_customers_id WHERE customer_id = :customers_id', [':ebay_customers_id' => 161001, ':customers_id' => $customers_id]);
		prepared_query::execute('DELETE FROM address_book WHERE customers_id = :customers_id', $cparam);
		prepared_query::execute('DELETE FROM customers WHERE customers_id = :customers_id', $cparam);

		$affected_customers[] = $customers_id;
	}

	debug_tools::mark('Completed '.($idx+1).' Customer Records; '.count($affected_customers).' Deleted');

	debug_tools::mark('Done deleting customers');
}
catch (Exception $e) {
	echo $e->getMessage();
}

debug_tools::clear_sub_timer_context();

var_dump(['deleted customers' => count($affected_customers), 'remaining customers' => count(array_diff($customers, $affected_customers))]);

echo 'Customer ID: '.implode('<br>Customer ID: ', $affected_customers).'<br>';

debug_tools::mark('Done');
?>
