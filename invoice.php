<?php
require('includes/application_top.php');

$customers_id = $_SESSION['customer_id'];
if (empty($customers_id)) die('Please log in to view your invoice.');

$orders_id = @$_GET['order_id'];
$invoice_id = @$_GET['invId'];

if (empty($orders_id) && empty($invoice_id)) die('You must provide either an order # or an invoice #.');

$invoice = $order = $rma = NULL;

if (!empty($invoice_id)) {
	$invoice = new ck_invoice($invoice_id);

	if ($invoice->get_header('customers_id') != $customers_id) die('The requested invoice does not belong to your account.');

	$customer = $invoice->get_customer();

	if ($invoice->has_rma()) {
		$rma = $invoice->get_rma();
		$order = $rma->get_sales_order();
	}
	elseif ($invoice->has_order()) $order = $invoice->get_order();

	if (!empty($orders_id) && $order->id() != $orders_id) die('Your selected invoice does not belong to your selected order.');
}
elseif (!empty($orders_id)) {
	$order = new ck_sales_order($orders_id);

	if ($order->get_header('customers_id') != $customers_id) die('The requested order does not belong to your account.');

	$customer = $order->get_customer();

	if ($order->has_invoices()) {
		$order_invoice = $order->get_latest_invoice();
		$invoice = new ck_invoice($order_invoice['invoice_id']);
	}
}

if (!empty($rma) && $rma->found()) $context = 'rma-invoice';
elseif (!empty($invoice) && $invoice->found()) $context = 'invoice';
elseif (!empty($order) && $order->found()) $context = 'order';
else die('Your selected order/invoice could not be found.');

require('invoice_inc.php');
?>
