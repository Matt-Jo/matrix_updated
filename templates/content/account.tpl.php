<?php $customer = $_SESSION['cart']->get_customer(); ?>
<style>
	.accountLinks { font-size: 14px; }
</style>
<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">My Account Information</div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear: both;"></div>

	<div class="main rounded-corners">
		<div class="grid grid-pad">
			<table border="0" width="100%" cellspacing="0" cellpadding="8">
				<?php if ($messageStack->size('account') > 0) { ?>
				<tr>
					<td><?php echo $messageStack->output('account'); ?></td>
				</tr>
				<tr>
					<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
				</tr>
				<?php }

				if ($customer->get_order_count() > 0) { ?>
				<tr>
					<td>
						<table border="0" cellspacing="0" cellpadding="2">
							<tr>
								<td class="main"><b>Recent Orders</b></td>
								<td class="main"><a href="/account_history.php"><u>show all orders</u></a></td>
							</tr>
						</table>
					</td>
				</tr>
				<?php
				$any_held_orders = prepared_query::fetch('SELECT orders_id FROM orders WHERE customers_id = :customers_id AND released = 0 AND orders_status NOT IN (3, 6)', cardinality::SINGLE, [':customers_id' => $customer->id()]);

				if (!empty($any_held_orders)) {
					if ($customer->cannot_place_any_order()) { ?>
				<tr>
					<td style="color:#f00; font-weight:bold;">YOUR CREDIT TERMS HAVE BEEN SUSPENDED. ALL ORDERS WILL BE HELD UNTIL FURTHER NOTICE. PLEASE CONTACT OUR <a href="mailto:accounting@cablesandkits.com">ACCOUNTING DEPARTMENT</a> TO RESOLVE.</td>
				</tr>
					<?php }
					elseif ($customer->get_credit('credit_status_id') == ck_customer2::CREDIT_PREPAID) { ?>
				<tr>
					<td style="color:#f00; font-weight:bold;">Your credit terms have been TEMPORARILY SUSPENDED. You must prepay via credit card or paypal to release your orders immediately. Please contact our <a href="mailto:accounting@cablesandkits.com">accounting department</a> to resolve any pending issues and have your terms reinstated.</td>
				</tr>
					<?php }
					elseif (!$customer->can_place_credit_order(0)) { ?>
				<tr>
					<td style="color:#f00; font-weight:bold;">You are over your credit limit.  You have <?= CK\text::monetize($customer->get_remaining_credit()); ?> available credit.  New orders will be held until your previous invoices have been paid. You still have the option to place orders via Credit Card or Paypal.</td>
				</tr>
					<?php }
				} ?>
				<tr>
					<td>
						<?php $all_orders = prepared_query::fetch("SELECT o.orders_id, o.date_purchased, o.delivery_name, o.purchase_order_number, o.net10_po, o.net15_po, o.net30_po, o.net45_po, o.billing_name, CONCAT_WS(' ', cel.customers_firstname, cel.customers_lastname) as orderer, ot.text as order_total, s.orders_status_name FROM orders o JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total' JOIN orders_status s ON o.orders_status = s.orders_status_id LEFT JOIN customers_extra_logins cel ON o.customers_extra_logins_id = cel.customers_extra_logins_id WHERE o.customers_id = :customers_id ORDER BY o.orders_id DESC LIMIT 5", cardinality::SET, array(':customers_id' => $_SESSION['customer_id']));
						foreach ($all_orders as $orders) {
							if (tep_not_null($orders['delivery_name'])) {
								$order_name = $orders['delivery_name'];
								//$order_country = $orders['delivery_country'];
							}
							else {
								$order_name = $orders['billing_name'];
								$order_country = $orders['billing_country'];
							} ?>
						<div style="font-size:11px; padding:10px;">
							<b>Order Number:</b> <a href="account_history_info.php?order_id=<?= $orders['orders_id']; ?>"><span style="color:#0066cc;"><u><?= $orders['orders_id']; ?></u></span></a><br>
							<b>PO Number:</b>
							<?php if (!empty($orders['net30_po'])) { echo $orders['net30_po']; }
							elseif (!empty($orders['net45_po'])) { echo $orders['net45_po']; }
							elseif (!empty($orders['net15_po'])) { echo $orders['net15_po']; }
							elseif (!empty($orders['net10_po'])) { echo $orders['net10_po']; }
							else { echo 'NA'; } ?><br>
							<b>Reference Number:</b> <?= $orders['purchase_order_number']; ?> <br>
							<b>Order Status:</b> <?= $orders['orders_status_name']; ?><br>
							<b>Date:</b> <?= $orders['date_purchased']; ?><br>
							<b>Cost:</b> <?= $orders['order_total']; ?><br>
							<b>Ordered By:</b> <?= $orders['orderer']; ?><br>
							<b>Shipped To:</b> <?= $orders['delivery_name']; ?><br>
						</div>
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
				<tr>
					<td>
						<table border="0" width="100%" cellspacing="0" cellpadding="2">
							<tr>
								<td class="main"><b><?php echo MY_ACCOUNT_TITLE; ?></b></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
							<tr class="infoBoxContents accountLinks">
								<td>
									<p><?php echo tep_image(DIR_WS_IMAGES.'arrow_green.gif').' <a href="/account_edit.php">'.MY_ACCOUNT_INFORMATION.'</a>'; ?></p>
									<p><?php echo tep_image(DIR_WS_IMAGES.'arrow_green.gif').' <a href="/address_book.php">'.MY_ACCOUNT_ADDRESS_BOOK.'</a>'; ?></p>
									<p><?php echo tep_image(DIR_WS_IMAGES.'arrow_green.gif').' <a href="/account_password.php">'.MY_ACCOUNT_PASSWORD.'</a>'; ?></p>
									<p><?php echo tep_image(DIR_WS_IMAGES.'arrow_green.gif').' <a href="/account_manage_ccs.php">Manage my stored credit cards.</a>'; ?></p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
				</tr>
				<tr>
					<td>
						<table border="0" width="100%" cellspacing="0" cellpadding="2">
							<tr>
								<td class="main"><b><?php echo MY_ORDERS_TITLE; ?></b></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
							<tr class="infoBoxContents accountLinks">
								<td>
									<p><?php echo tep_image(DIR_WS_IMAGES.'arrow_green.gif').' <a href="/account_history.php">'.MY_ORDERS_VIEW.'</a>'; ?></p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<?php $balance = $customer->get_customer_balance();
				$unapplied_credit = $customer->get_unapplied_credit_total();
				if ($unapplied_credit > 0) { ?>
				<tr>
					<td>
						<table border="0" width="100%" cellspacing="0" cellpadding="2">
							<tr>
								<td class="main"><b>Available Account Credits Total</b></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<style>
							.credit { font-size:24px; }
							.credit.positive { color:#080; }
							.credit.negative { color:#ff6347; }
						</style>
						<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
							<tr>
								<?php $credit = $unapplied_credit - $balance; ?>
								<td class="credit <?= $credit>0?'positive':($credit<0?'negative':''); ?>">
									<?= CK\text::monetize($credit); ?><br>
									<?php if ($credit > 0) { ?>
									<small>You must contact us to apply this credit to outstanding invoices.</small>
									<?php } ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<?php } ?>
			</table>
		</div>
	</div>
</div>
