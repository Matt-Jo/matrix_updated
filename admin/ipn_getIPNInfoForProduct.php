<?php require_once('../includes/application_top.php');

$product = new ck_product_listing($_GET['products_id']);
$ipn = $product->get_ipn();

if ($requiring_ipns = $ipn->get_requiring_ipns()) {
	$parent_qty = 0;
	foreach ($requiring_ipns as $requiring_ipn) {
		$parent_qty += max($requiring_ipn->get_inventory('available'), 0);
	}
}

$forecast = new forecast($ipn->id());
$ipn_forecast = $forecast->build_report('ALL')[0];

//set the conditioning buffer to 3 days
$conditioning_buffer = 3;

$broker_restriction = $ipn->get_inventory('available') - ceil(($ipn->get_header('lead_time') + $conditioning_buffer) * $forecast->daily_qty($ipn_forecast));
$broker_style = $broker_restriction<1?'color: red; font-weight: bold;':'color: green;'; ?>
<table cellspacing="0" cellpadding="3px" border="0" width="90%">
	<tr>
		<td class="itemDescription"><strong>IPN:</strong></td>
		<td class="itemDescription"><?= $ipn->get_header('ipn'); ?><a href="/admin/ipn_editor.php?ipnId=<?= urlencode($ipn->get_header('ipn')); ?>" style="font-weight:bold;color:#00f;" target="_blank">&#8599;</a></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>URL:</strong></td>
		<td class="itemDescription">
			<input id="url_selection" style="border-radius:6%; border:1px solid #000; display:inline; padding:0; margin:0;" placeholder="www.cablesandkits.com<?= $product->get_url(); ?>" value="www.cablesandkits.com<?= $product->get_url(); ?>">
		</td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Qty On Hand:</strong></td>
		<td class="itemDescription"><?= $ipn->get_inventory('on_hand'); ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Qty Allocated:</strong></td>
		<td class="itemDescription"><?= $ipn->get_inventory('allocated'); ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Qty On Hold:</strong></td>
		<td class="itemDescription"><?= $ipn->get_inventory('on_hold'); ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Qty Available:</strong></td>
		<td class="itemDescription"><?= $ipn->get_inventory('available'); ?></td>
	</tr>
	<?php if (!empty($requiring_ipns)) { ?>
	<tr>
		<td class="itemDescription"><strong>Parent Available Quantity:</strong></td>
		<td class="itemDescription" id="par_avl_qty"><?= $parent_qty; ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Adjusted Available Quantity:</strong></td>
		<td class="itemDescription" id="adj_avl_qty"><?= $ipn->get_inventory('available') - $parent_qty; ?></td>
	</tr>
	<?php } ?>
	<tr>
		<td class="itemDescription"><strong>Broker Sales Restriction:</strong></td>
		<td class="itemDescription" style="<?= $broker_style; ?>"><?= $broker_restriction; ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Qty On Order:</strong></td>
		<td class="itemDescription"><?= $ipn->get_header('on_order'); ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Cost:</strong></td>
		<td class="itemDescription">$<?= number_format($ipn->get_avg_cost(), 2); ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Bin Location:</strong></td>
		<td class="itemDescription"><?= $ipn->get_header('bin1'); ?></td>
	</tr>
	<?php $prices = $ipn->get_price(); ?>
	<tr>
		<td class="itemDescription"><strong>Retail Price:</strong></td>
		<td class="itemDescription"><?= CK\text::monetize($prices['list']); ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Reseller Price:</strong></td>
		<td class="itemDescription"><?= CK\text::monetize($prices['dealer']); ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Wholesale High Price:</strong></td>
		<td class="itemDescription"><?= CK\text::monetize($prices['wholesale_high']); ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Wholesale Low Price:</strong></td>
		<td class="itemDescription"><?= CK\text::monetize($prices['wholesale_low']); ?></td>
	</tr>
	<tr>
		<td class="itemDescription"><strong>Amazon SKU:</strong></td>
		<td class="itemDescription"><?= $product->id(); ?></td>
	</tr>
</table>