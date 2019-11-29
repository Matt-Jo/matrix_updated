<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">My Order History</div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear: both;"></div>

	<div class="main rounded-corners">
		<div class="grid grid-pad edit_form">
			<table border="0" width="100%" cellspacing="0" cellpadding="8">
				<tr>
					<td>
						<?php $customer = new ck_customer2($_SESSION['customer_id']);
						$order_ids = ck_sales_order::get_order_ids_by_customer($_SESSION['customer_id']);
						$page_size = 12;
						$page = !empty($_GET['page'])?$_GET['page']:1; ?>
						<style>
							.order-history { width:100%; margin-bottom:10px; }
							.order-history th, .order-history td { padding:3px 6px; }
							.order-history th { border:2px solid #999; }
							.order-history td { border-width:0px 0px 1px 0px; border-style:solid; border-color:#bbb; }
							.order-history tfoot td { padding-top:10px; border-width:0px; color:#e62345; font-weight:bold; font-family:Arial, Verdana, sans-serif; font-size:12px; }
							.order-history tfoot td a { color:#00f; }
							.order-history tfoot td a:hover { color:#00f;  background-color:#ff3; }
						</style>
						<table border="0" cellpadding="0" cellspacing="0" class="order-history table-md" id="order-history">
							<thead>
								<tr>
									<th>Order #</th>
									<th>Order Date</th>
									<?php if ($customer->has_credit() && $customer->has_terms()) { ?>
									<th>PO #</th>
									<?php } ?>
									<th>Ref #</th>
									<th>Status</th>
									<th>Total</th>
									<th>Ordered By</th>
									<th>Shipped To</th>
								</tr>
							</thead>
							<?php if (count($order_ids) > $page_size) { ?>
							<tfoot>
								<tr>
									<td colspan="<?= $customer->has_credit()&&$customer->has_terms()?8:7; ?>">
										<div style="float:left;">
											Displaying <?= 1 + (($page - 1) * $page_size); ?> to <?= $page * $page_size; ?> (of <?= count($order_ids); ?> orders)
										</div>
										<div style="float:right;">
											Results Pages:
											<?php if ($page > 1) { ?>
											<a href="?page=<?= $page - 1; ?>">[&lt;&lt; Prev]</a>
											<?php }
	
											for ($i=1; $i<=ceil(count($order_ids)/$page_size); $i++) {
												if ($i == $page) echo $i;
												else { ?>
											<a href="?page=<?= $i; ?>"><?= $i; ?></a>
												<?php }
											}
	
											if ($page < ceil(count($order_ids)/$page_size)) { ?>
											<a href="?page=<?= $page + 1; ?>">[Next &gt;&gt;]</a>
											<?php } ?>
										</div>
									</td>
								</tr>
							</tfoot>
							<?php } ?>
							<tbody>
								<?php if (!empty($order_ids)) {
									$page_orders = array_slice($order_ids, ($page - 1) * $page_size, $page_size);
									foreach ($page_orders as $order_id) {
										$order = new ck_sales_order($order_id); ?>
								<tr>
									<td title="Order #"><a href="/account_history_info.php?order_id=<?= $order->id(); ?>" style="color:#06c;"><?= $order->id(); ?></a></td>
									<td title="Order Date"><?= $order->get_header('date_purchased')->format('Y-m-d H:i:s'); ?></td>
									<?php if ($customer->has_credit() && $customer->has_terms()) { ?>
									<td title="Payment Type">
										<?php if (!empty($order->get_header('net10_po'))) echo $order->get_header('net10_po');
										elseif (!empty($order->get_header('net15_po'))) echo $order->get_header('net15_po');
										elseif (!empty($order->get_header('net30_po'))) echo $order->get_header('net30_po');
										elseif (!empty($order->get_header('net45_po'))) echo $order->get_header('net45_po');
										else echo 'NA'; ?>
									</td>
									<?php } ?>
									<td title="Ref #"><?= $order->get_header('purchase_order_number'); ?></td>
									<td title="Status"><?= $order->get_header('orders_status_name'); ?></td>
									<td title="Total"><?= CK\text::monetize($order->get_simple_totals('total')); ?></td>
									<td title="Ordered By">
										<?php if (!empty($order->get_header('customers_extra_logins_id'))) {
											echo $order->get_header('extra_logins_firstname').' '.$order->get_header('extra_logins_lastname');
										}
										else {
											echo $order->get_header('customers_name');
										} ?>
									</td>
									<td title="Shipped To"><?= $order->get_header('billing_name'); ?></td>
								</tr>
									<?php }
								}
								else { ?>
								<tr>
									<td colspan="<?= $customer->has_credit()&&$customer->has_terms()?8:7; ?>">You don't have any orders yet!</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
						<a href="/account.php"><img src="templates/Pixame_v1/images/buttons/english/button_back.gif" border="0" alt="Back" title="Back"></a>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
