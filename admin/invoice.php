<?php
if (empty($GLOBALS['skip_app_top'])) require('includes/application_top.php');

$orders_id = @$_GET['oID'];
$invoice_id = @$_GET['invId'];

if (empty($orders_id) && empty($invoice_id)) die('You must provide either an order # or an invoice #.');

$invoice = $order = $rma = NULL;

if (!empty($invoice_id)) {
	$invoice = new ck_invoice($invoice_id);

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

$edit = !empty($_REQUEST['edit'])?$_REQUEST['edit']:NULL;

switch ($edit) {
	case 'invoice':
		$editable = TRUE;
		break;
	case 'invoice_save':
		if (!empty($order)) {

			if (!empty($invoice)) $credit_invoice = ck_invoice::create_credit_from_invoice($invoice);

			foreach ($_GET as $key => $value) {
				if (strpos($key, 'product_') !== false) {
					$item = explode('_', $key);
					$orders_products_id = $item[1];
					$order->update_product($orders_products_id, ['final_price' => $value]);
				}
			}

			// loop through twice, because we want to handle all totals *after* we've handled all products
			foreach ($_GET as $key => $value) {
				if (strpos($key, 'ot_') !== false) {
					if ($value == 0 && $key == 'ot_tax') {
						prepared_query::execute('UPDATE orders SET tax_exempt = 1 WHERE orders_id = :orders_id', [':orders_id' => $orders_id]);
						prepared_query::execute('DELETE FROM orders_total WHERE orders_id = :orders_id AND class = :class', [':orders_id' => $orders_id, ':class' => $key]);
					}
					else {
						$text = "<b>$".number_format($value, 2)."</b>";
						prepared_query::execute('UPDATE orders_total SET value = :value, text = :text WHERE orders_id = :orders_id AND class = :class', [':value' => $value, ':text' => $text, ':orders_id' => $orders_id, ':class' => $key]);
					}
				}
			}

			$new_invoice = ck_invoice::create_from_sales_order($order, $skip_inventory_management=TRUE);

			print 'success';
		}
		else print 'fail';
		exit();
		break;
	case 'getCoupon':
		$coupon_amount = 0;
		if ($coupons = $order->get_totals('coupon')) {
			foreach ($coupons as $coupon) {
				$coupon_id = explode(':', $coupon['title']);
				if (!empty($coupon_id[1]) && $cpn = prepared_query::fetch('SELECT coupon_amount, coupon_type FROM coupons WHERE coupon_code = ?', cardinality::ROW, $coupon_id[1])) {
					if ($cpn['coupon_type'] == 'P') $coupon_amount = number_format($cpn['coupon_amount'] / 100, 2);
					else $coupon_amount = number_format($cpn['coupon_amount'], 2); // coupon_type == F
				}
			}
		}
		print $coupon_amount;
		exit();
		break;
	case 'getTaxRate':
		echo $order->get_header('avatax_rate');
		exit();
		break;
	case 'override-incentive':
		if (!empty($_POST['incentive_override_percentage']) && is_numeric($_POST['incentive_override_percentage'])) {
			prepared_query::execute('UPDATE acc_invoices SET incentive_override_percentage = :pct, incentive_override_date = NOW(), incentive_override_note = :note, incentive_final_total = incentive_product_total * :pct WHERE invoice_id = :invoice_id', [':pct' => $_POST['incentive_override_percentage'], ':note' => $_POST['incentive_override_note'], ':invoice_id' => $invoice->id()]);
		}
		//exit();
		CK\fn::redirect_and_exit('/admin/invoice.php?invId='.$invoice->id());
		
		break;
	case 'update-term-po-number':
		if (!empty($order)) {
			$order->update_term_po_number($_REQUEST['terms_po_number']);
			$_SESSION['flash'] = 'Terms PO Updated!';
		}
		CK\fn::redirect_and_exit('/admin/invoice.php?oID='.$order->id());
		break;
}

require(DIR_FS_CATALOG.'invoice_inc.php');
?>
