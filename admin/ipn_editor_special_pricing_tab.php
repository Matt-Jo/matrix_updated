<?php
require_once('ipn_editor_top.php');
?>
<table cellpadding="4px" cellspacing="0" border="0" width="100%">
	<thead>
		<tr>
			<td class="main" style="font-size:14px;" colspan="3"><b>Special Pricing</b></td>
		</tr>
		<tr>
			<td class="main"><b>Company</b></td>
			<td class="main"><b>Price</b></td>
			<td class="main"><b>Action</b></td>
		</tr>
	</thead>
	<tbody>
		<?php if ($ipn->has_customer_prices()) {
			foreach ($ipn->get_customer_prices() as $idx => $price) {
				$customer = new ck_customer2($price['customer']['customers_id']);
				$address = $customer->get_addresses('default'); ?>
		<tr style="<?= $idx%2==1?'background-color:#ccc;':''; ?>">
			<td class="main"><?= $customer->get_header('first_name').' '.$customer->get_header('last_name'); ?> - <?= $address->get_header('company_name'); ?></td>
			<td class="main"><?= $price['price']; ?></td>
			<td class="main"><a href="/admin/customers_detail.php?customers_id=<?= $customer->id(); ?>" target="_blank">edit</a></td>
		</tr>
			<?php }
		}
		else { ?>
		<tr>
			<td colspan="3">No special pricing</td>
		</tr>
		<?php } ?>
	</tbody>
</table>
