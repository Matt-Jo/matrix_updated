<?php
require_once('includes/application_top.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
	$action = !empty($_POST['action'])?$_POST['action']:NULL;

	if ($action == 'remove_allocation') {
		$id = $_POST['id'];

		prepared_query::execute('DELETE FROM acc_payments_to_orders WHERE id = :id', [':id' => $id]);
	}
	else {
		// process the submission
		$paymentIds = $_POST['add-payment-id'];
		$paymentAmounts = $_POST['payment-amount-applied'];
		$orderId = $_POST['orderId'];

		$order = new ck_sales_order($orderId);

		$payments = ck_payment::get_payments_by_applied_orders_id($order->id());
		$allocatedTotal = array_reduce($payments, function($total, $payment) use ($order) {
			foreach ($payment->get_active_controller()->get_order_applications($order->id()) as $app) {
				$total += $app['applied_amount'];
			}
			return $total;
		}, 0);

		$remaining = $order->get_simple_totals('total') - $allocatedTotal;

		$total = 0;
		foreach ($paymentAmounts as $payment) {
			$total += $payment;
		}

		if (round($total, 2) > round($remaining, 2)) {
			echo json_encode(array('error' => 'Total of applied payments ('.CK\text::monetize($total).') exceeds the amount remaining on the order ('.CK\text::monetize($remaining).')'));
			return;
		}

		foreach ($paymentIds as $index => $id) {
			$amount = $paymentAmounts[$index];

			$data = [
				':payment_id' => (int) $id,
				':order_id' => (int) $orderId,
				':amount' => (float) $amount,
			];

			prepared_query::execute('INSERT INTO acc_payments_to_orders (payment_id, order_id, amount) VALUES (:payment_id, :order_id, :amount)', $data);
		}

		echo json_encode(array());
	}
}
else {
	$paymentMethodId = $_GET['paymentMethodId'];
	$orderId = $_GET['orderId'];

	$order = new ck_sales_order($orderId);
	$paymentMethod = ck_payment_method_lookup::instance()->lookup_by_id($order->get_header('payment_method_id'), 'method_label');

	$customer = $order->get_customer();
	$payments = $customer->get_unapplied_payments_by_payment_method_id($paymentMethodId); ?>

<div style="width: 525px; margin: 15px;">
<?php if (count($payments) > 0): ?>
<h4 style="text-align: center;">Available <?= $paymentMethod; ?> Payments for <?= $customer->get_display_label(); ?></h4>
<form id="add-payment-form">
<table align="center" style="text-align: center; font-size: 14px;" cellpadding="6">
	<tr>
		<th>&nbsp;</th><th>Payment ID</th><th>Amount Available</th><th>Amount to Allocate</th>
	</tr>
	<?php foreach ($payments as $i => $payment) {
		$order_allocation = prepared_query::fetch('SELECT SUM(amount) FROM acc_payments_to_orders WHERE payment_id = :payment_id', cardinality::SINGLE, [':payment_id' => $payment['payment_id']]); ?>
	<tr>
		<td><input class="payment-amount" type="hidden" value="<?= $payment['unapplied_amount'] - $order_allocation; ?>"><input name="add-payment-id[]" class="add-payment-id" type="checkbox" value="<?= $payment['payment_id']; ?>" /></td><td><?= $payment['payment_id']; ?></td><td><?= CK\text::monetize($payment['unapplied_amount']); ?></td><td style="text-align: right;">$<input name="payment-amount-applied[]" class="payment-amount-applied" type="text" style="width: 145px;" disabled="disabled"></td>
	</tr>
	<?php } ?>
	<tr style="font-weight: bold;">
		<td>&nbsp;</td><td>&nbsp;</td><td>Order Total:</td><td><?= CK\text::monetize($order->get_simple_totals('total')); ?></td>
	</tr>
	<tr style="font-weight: bold;">
		<td>&nbsp;</td><td>&nbsp;</td><td style="border-bottom: 1px double black;">Total Allocated:</td><td id="add-payment-total-allocated" style="border-bottom: 1px double black;">$0.00</td>
	</tr>
</table>
</form>
<input id="add-payment-save" style="float: right; width: 150px; margin-right: 40px; padding: 10px; background-color: #7FA5EC; font-size: 12px; font-weight: bold; border: 2px solid #0A1959;" type="button" value="Save" disabled="disabled" />
<?php else: ?>
<h4 style="text-align: center;">No Available <?= $paymentMethod; ?> Payments found for <?= $customer->get_display_label(); ?></h4>
<?php endif; ?>
</div>
<?php } ?>
