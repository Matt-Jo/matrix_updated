<?php
require('includes/application_top.php');
$order_id = $_POST['order_id'];

$orders = prepared_query::fetch("select orders_status_name, orders_sub_status, o.orders_status, pm.label as payment_method, s.orders_status_id from orders o inner join payment_method pm on o.payment_method_id = pm.id inner join orders_status s on orders_status = s.orders_status_id where orders_id = :orders_id", cardinality::ROW, [':orders_id' => $order_id]);

$orders_sub_status_array = prepared_query::keyed_set_value_fetch("select orders_sub_status_id, orders_sub_status_name from orders_sub_status WHERE orders_sub_status_name NOT LIKE 'PayPal Pending'", 'orders_sub_status_id', 'orders_sub_status_name');

# Order Comments
$addto = 0;
$noordcmmts = 0;
$ordcmt = "<tr><td class=\"main\" colspan=\"2\"><b>Order Comments</b>:</td></tr>".PHP_EOL;
$addto += 55;
$cmmtidx = 0;

$ordcmtqry = prepared_query::fetch("select comments, orders_status_history_id from orders_status_history where orders_id = :orders_id order by orders_status_history_id asc", cardinality::SET, [':orders_id' => $order_id]);

foreach ($ordcmtqry as $ordcmtArr) {
	if ($cmmtidx===0) {
		if (!empty($ordcmtArr["comments"])) {
			$ordcmt .= "<tr><td colspan=\"2\" class=\"smallText\" style=\"padding-left:20px;\">".$ordcmtArr["comments"]."</td></tr>".PHP_EOL;
			$addto += (int)((strlen($ordcmtArr["comments"]) / 250) + 1) * 15;
		}
		else {
			$ordcmt .= "<tr><td colspan=\"2\" class=\"smallText\" style=\"padding-left:20px;\">(No comments)</td></tr>".PHP_EOL;
		}
	}
	$cmmtidx++;
}

$ordcmt .= "<tr><td colspan=\"2\">&nbsp;</td></tr>".PHP_EOL;

# Admin Comments
$noadmcmmts = 0;
$admincmt = "<tr><td class=\"main\" colspan=\"2\"><b>Admin Comments</b>:</td></tr>".PHP_EOL;
$addto += 55;

$ordcmtAdmqry = prepared_query::fetch("select ons.orders_note_id, ons.orders_note_user, ons.orders_note_text, ons.orders_note_created, ons.orders_note_modified, ad.admin_firstname, ad.admin_lastname from orders_notes ons left join admin ad on ons.orders_note_user = ad.admin_id where orders_id = :orders_id and orders_note_deleted = 0 order by ons.orders_note_id desc", cardinality::SET, [':orders_id' => $order_id]);

foreach ($ordcmtAdmqry as $ordcmtAdmArr) {
	if ($noadmcmmts < 3) {
		if (!empty($ordcmtAdmArr["orders_note_modified"])) $orders_note_modified = new DateTime($ordcmtAdmArr["orders_note_modified"]);
		else $orders_note_modified = new DateTime($ordcmtAdmArr["orders_note_created"]);

		$admincmt .= "<tr><td colspan=\"2\" class=\"smallText\"><b>".$orders_note_modified->format('m/d/Y h:i:s a')."</b> ".$ordcmtAdmArr["admin_firstname"]."&nbsp;&nbsp;".$ordcmtAdmArr["admin_lastname"]."</td></tr>".PHP_EOL;
		$admincmt .= "<tr><td colspan=\"2\" class=\"smallText\" style=\"padding-left:20px;padding-top:3px;;padding-bottom:4px;\">".$ordcmtAdmArr["orders_note_text"]."</td></tr>".PHP_EOL;
		$addto += (int)((strlen($ordcmtAdmArr["orders_note_text"]) / 250) + 1) * 15;
	}
	$noadmcmmts++;
}
if (empty($noadmcmmts)) {
	$admincmt .= "<tr><td colspan=\"2\" class=\"smallText\" style=\"padding-left:20px;\">(No comments)</td></tr>".PHP_EOL;
}
elseif ($noadmcmmts >= 3) {
	$admincmt .= "<tr><td colspan=\"2\" class=\"smallText\" style=\"padding-left:20px;\">(More comments ...)</td></tr>".PHP_EOL;
}
$admincmt .= "<tr><td colspan=\"2\">&nbsp;</td></tr>".PHP_EOL;

# In Stock?
$instock = 1;
$stockqry = prepared_query::fetch("select op.orders_products_id, op.products_quantity, psc.stock_quantity from orders_products op left join products p on (op.products_id) = p.products_id left join products_stock_control psc on p.stock_id = psc.stock_id where op.orders_id = :orders_id", cardinality::SET, [':orders_id' => $order_id]);

foreach ($stockqry as $stockArr) {
	if ($stockArr['stock_quantity'] - $stockArr['products_quantity'] < 0) {
		$instock = 0;
	}
} ?>
<table border="0" cellpadding="1" cellspacing="0" width="100%">
	<tr>
		<td class="main" width="40%" nowrap="nowrap"><b>Order No. <?= $order_id; ?></b></td>
		<td class="main" width="60%" nowrap="nowrap"><b>In Stock?</b>: <?php echo $instock ? "Yes" : "No"; ?></td>
	</tr>
	<tr>
		<td class="main" nowrap="nowrap"><b>Payment Method</b>: <?= $orders['payment_method']; ?></td>
		<td class="main" nowrap="nowrap">
			<b>Status</b>:
			<?php echo $orders['orders_status_name'];

			# 291 - jfm 2009-08-05 - if order status is not CS, do not show sub-status
			echo ($orders['orders_sub_status'] && $orders['orders_status_id'] == 11) ? (" - ".$orders_sub_status_array[$orders['orders_sub_status']]) : ""; ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	<?php echo $ordcmt;
	echo $admincmt; ?>
</table>
