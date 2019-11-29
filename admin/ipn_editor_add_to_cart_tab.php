<?php
require_once('ipn_editor_top.php');

$products = $ipn->get_listings();

if (!empty($_SESSION['customer_id'])) $cart = ck_cart::instance();

$price_locked = FALSE;
?>
<style>
	.add-to-cart { border-collapse:collapse; margin-top:5px; }
	.add-to-cart th, .add-to-cart td { border:1px solid #000; padding:4px 6px; font-size:14px; }
	.add-to-cart span { display:inline-block; padding-bottom:2px; border-bottom:1px dashed #000; }

	.no-product-listings { font-size:16px; }

	#add-to-cart-pid .inactive { background-color:#fcc; }
</style>
<div>
	Currently Logged In Cart: <?= !empty($cart)&&$cart->is_logged_in()?$cart->get_email_address():'NO CART - CANNOT ADD TO CART'; ?>
</div>
<?php if (!empty($products)) { ?>
<form action="/admin/ipn_editor.php" method="post">
	<input type="hidden" name="action" value="add-to-cart">
	<table cellspacing="0" cellpadding="0" border="0" class="add-to-cart">
		<thead>
			<tr>
				<th>IPN</th>
				<th>Availability</th>
				<th>Status</th>
				<th>Product Listing</th>
				<th>Price</th>
				<th>Qty</th>
				<th>[+]</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?= $ipn->get_header('ipn'); ?></td>
				<td><?= $ipn->get_inventory('available'); ?></td>
				<td>
					<?= $ipn->is('discontinued')?'<span title="Discontinued">[D]</span>':''; ?>
					<?= $ipn->is('is_bundle')?'<span title="Bundle">[B]</span>':''; ?>
					<?= $ipn->is('drop_ship')?'<span title="Drop Ship">[DS]</span>':''; ?>
					<?= $ipn->is('non_stock')?'<span title="Non-Stock">[NS]</span>':''; ?>
					<?= $ipn->is('freight')?'<span title="Freight">[F]</span>':''; ?>
				</td>
				<td>
					<select name="products_id" id="add-to-cart-pid" required>
						<?php foreach ($products as $product) {
							if ($product->get_viewable_state() > 0) $price_locked = TRUE; ?>
						<option value="<?= $product->id(); ?>" class="<?= $product->get_viewable_state()<=0?'inactive':''; ?>"><?= $product->get_viewable_state()<=0?'[INACTIVE] ':''; ?><?= $product->get_header('products_model'); ?> - <?= $product->get_header('products_name'); ?></option>
						<?php } ?>
					</select>
				</td>
				<td>
					<select name="price_level">
						<?php $prices = $product->get_price(); ?>
						<option value="original" <?= $prices['reason']=='original'?'selected':''; ?>>Retail: <?= $prices['original']; ?></option>
						<option value="dealer" <?= $prices['reason']=='dealer'?'selected':''; ?>>Reseller: <?= $prices['dealer']; ?></option>
						<?php if (!empty($prices['wholesale_high'])) { ?>
						<option value="wholesale_high" <?= $prices['reason']=='wholesale_high'?'selected':''; ?>>Wholesale High: <?= $prices['wholesale_high']; ?></option>
						<?php }
						if (!empty($prices['wholesale_low'])) { ?>
						<option value="wholesale_low" <?= $prices['reason']=='wholesale_low'?'selected':''; ?>>Wholesale Low: <?= $prices['wholesale_low']; ?></option>
						<?php }
						if (!empty($prices['special'])) { ?>
						<option value="special" <?= $prices['reason']=='special'?'selected':''; ?>>Special: <?= $prices['special']; ?></option>
						<?php }
						if  (!empty($prices['customer'])) { ?>
						<option value="customer" <?= $prices['reason']=='customer'?'selected':''; ?>>Customer: <?= $prices['customer']; ?></option>
						<?php } ?>
					</select>
					<?php if (!$price_locked) { ?>
					<span title="This price is not guaranteed to be accurate, please confirm with the product manager.">[<input type="checkbox" name="price_confirm" required> Confirm Price]</span>
					<?php } ?>
				</td>
				<td>
					<input type="text" name="qty" value="" style="width:40px;" required>
					<?php if ($ipn->is('discontinued')) { ?>
					<span title="This item is discontinued, please confirm we have or can procure enough units to fulfill this order.">[<input type="checkbox" name="qty_confirm" required> Confirm Qty]</span>
					<?php } ?>
				</td>
				<td>
					<?php if (!empty($cart) && $cart->is_logged_in()) { ?>
					<button type="submit">Add To Cart</button>
					<?php }
					else { ?>
					NO CART, CANNOT ADD
					<?php } ?>
				</td>
			</tr>
		</tbody>
	</table>
</form>
<?php }
else { ?>
<div class="no-product-listings">
	No Product Listings Exist
</div>
<?php } ?>
<script>
</script>
