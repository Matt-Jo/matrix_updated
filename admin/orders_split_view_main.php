<style>
	h3 { font-weight:bold; }
	.infoText { margin-left:25px; font-family:arial; font-size:10px; color:#787878; }
	#op_table { margin-left:25px; margin-top:10px; border:1px solid #555555; }
	#op_table .op_table_shade_row { background-color:#cdcdcd; }
	#op_table td, #op_table th { text-align:center; padding-left:10px; padding-right:10px; }
	#shipping_details { margin-left:25px; }
	#shipping_details input[type="text"]{ width:200px; }
	#shipping_details input[type="text"].shcost { width:60px; }
	#shipping_details input[disabled] { background-color:#fff; color:#555; }
	#order_notes{ margin-left:25px; }
	#split_order_submit { margin-left:25px; width:200px; height:50px; background-color:green; color:white; font-size: 15px; font-family:Verdana; }
	.freight_details { margin:10px 0px 0px 25px; }
	.freight_details th, .freight_details td { border-style:solid; border-color:#000; padding:3px 4px; }
	.freight_details thead th { border-width:1px 0px 0px 1px; }
	.freight_details thead tr:first-child th { background-color:#ccf; }
	.freight_details thead tr:last-child th { border-bottom-width:1px; }
	.freight_details tbody th { border-width:0px 0px 1px 1px; }
	.freight_details th:last-child { border-right-width:1px; }
	.freight_details tbody td { border-width:0px 1px 1px 1px; }
	.freight_details .select_vendor { cursor:pointer; }
	.freight_details .select_vendor:hover { background-color:#6f6; }
</style>
<script type="text/javascript" src="/admin/includes/javascript/choose_shipping.js?v=1.0.2"></script>
<script type="text/javascript">
	// jQuery(function() {
	jQuery(document).ready(function($) {
		jQuery('#chooseShipping').click(function() {

			//first we loop through the products being split and add up the weight
			var inputs = jQuery('#split_order_form :input');
			var values = {};
			var shipping_weight = 0;
			var freight = 0;
			var products = {};
			inputs.each(function() {
				values[this.name] = jQuery(this).val();
				if (jQuery(this).hasClass('split_qty')) {
					var order_product_id = this.name.replace('op[', '').replace(']', '');
					var quantity = jQuery(this).val();
					var weight = jQuery(this).attr('weight');
					shipping_weight = shipping_weight + (quantity * weight);
					if (quantity > 0) products[order_product_id] = weight;
					if (quantity) freight += parseInt(jQuery(this).attr('data-freight'));
				}
			});

			chooseShippingDialog(
				jQuery('input[name=original_order_id]').val(),
				values['customers_id'],
				products,
				shipping_weight,
				freight,
				values['delivery_street_address'],
				values['delivery_suburb'],
				values['delivery_city'],
				values['delivery_state'],
				values['delivery_postcode'],
				values['delivery_country'],
				'splitOrderShippingCallback'
			);
		});
	});
	function splitOrderShippingCallback(shipping_method_id, cost) {
		jQuery('#shipping_method').val(shipping_method_id);
		jQuery('#shipping_price').val(cost).keyup();
	}

	function ordersSplitVerifyForm() {
		if (jQuery('#shipping_method').val() == '0') {
			alert('Please select a shipping method.');
			return false;
		}
		return true;
	}
</script>
<form action="orders_split.php" method="POST" id="split_order_form">
	<input type="hidden" name="original_order_id" value="<?= $order->id(); ?>">
	<input type="hidden" name="customers_id" id="customers_id" value="<?= $order->get_header('customers_id'); ?>">
	<h2>Split Order #<?= $order->id(); ?> Onto a New Order</h2>
	<h3>1. Select Products</h3>
	<span class="infoText">For products you would like placed on the new order, change the quantity for that item from 0 to the quantity desired on the new order.</span>
	<div id="product_details">
		<table id="op_table" cellspacing='0' cellpadding='3px' border='0'>
			<thead>
				<tr>
					<th>Quantity To Split</th>
					<th>Product Model</th>
					<th>IPN</th>
					<th>Quantity Ordered</th>
					<th>Salable</th>
					<th>Needed</th>
					<th>Price Each</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($order->get_products() as $idx => $order_product) {
					$rowClass = 'op_table_shade_row';
					if ($idx%2 == '1') $rowClass = 'op_table_row';

					$inventory = $order_product['ipn']->get_inventory();
					$salable = $inventory['salable'];
					$needed = $order_product['quantity'] - $salable; ?>
				<tr class="<?= $rowClass; ?>">
					<td>
						<select name="op[<?= $order_product['orders_products_id']; ?>]" class="split_qty vendor-<?= prepared_query::fetch('SELECT CASE WHEN psc.drop_ship = 1 THEN psce.preferred_vendor_id ELSE 0 END as vendor_id FROM products_stock_control psc LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id WHERE psc.stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $order_product['stock_id']]); ?>" id='op_<?= $order_product['orders_products_id']; ?>' weight="<?= $order_product['ipn']->get_header('stock_weight'); ?>" data-freight="<?= $order_product['ipn']->get_header('freight'); ?>">
							<?php for ($i=0; $i<=$order_product['quantity']; $i++) { ?>
							<option value="<?= $i; ?>"><?= $i; ?></option>
							<?php } ?>
						</select>
					</td>
					<td><?= $order_product['model']; ?></td>
					<td><?= $order_product['ipn']->get_header('stock_name'); ?></td>
					<td><?= $order_product['quantity']; ?></td>
					<td><?= $salable; ?></td>
					<td><?= $needed>0?$needed:'0'; ?></td>
					<td><?= number_format($order_product['final_price'], 2); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<div style="margin-left:25px; margin-top:5px; ">
			<label for="split_accessories">*Split Included Accessories with Parent Product</label>
			<input type="checkbox" name="split_accessories" checked>
		</div>

		<?php if ($freights = prepared_query::fetch('SELECT fs.residential, fs.liftgate, fs.inside, fs.limitaccess, fsi.location_vendor_id, v.vendors_company_name, psc.stock_name as ipn FROM ck_freight_shipment fs JOIN ck_freight_shipment_items fsi ON fs.freight_shipment_id = fsi.freight_shipment_id JOIN orders_products op ON fs.orders_id = op.orders_id AND fsi.products_id = op.products_id LEFT JOIN vendors v ON fsi.location_vendor_id = v.vendors_id JOIN products p ON fsi.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE fs.orders_id = ? ORDER BY fsi.location_vendor_id ASC, ipn ASC', cardinality::SET, [$order->id()])) {
			$vendors = [];
			foreach ($freights as $freight) {
				$residential = $freight['residential'];
				$liftgate = $freight['liftgate'];
				$inside = $freight['inside'];
				$limitaccess = $freight['limitaccess'];
				if (!isset($vendors[$freight['location_vendor_id']])) $vendors[$freight['location_vendor_id']] = 0;
				$vendors[$freight['location_vendor_id']]++;
			} ?>
		<tr>
			<td class="main" colspan="5">
				<table class="freight_details" cellpadding="0" cellspacing="0">
					<thead>
						<tr>
							<th colspan="4">FREIGHT SHIPMENT DETAILS</th>
						</tr>
						<tr>
							<th style="background-color:<?= $residential?'#cfc':'#fcc'; ?>">Residential: <?= $residential?'Yes':'No'; ?></th>
							<th style="background-color:<?= $liftgate?'#cfc':'#fcc'; ?>">Liftgate: <?= $liftgate?($residential?'Included':'Yes'):'No'; ?></th>
							<th style="background-color:<?= $inside?'#cfc':'#fcc'; ?>">Inside Delivery: <?= $inside?'Yes':'No'; ?></th>
							<th style="background-color:<?= $limitaccess?'#cfc':'#fcc'; ?>">Limited Access: <?= $limitaccess?'Yes':'No'; ?></th>
						</tr>
						<tr>
							<th colspan="2">Location</th>
							<th colspan="2">IPN</th>
						</tr>
					</thead>
					<tbody>
						<?php $last_vendor_id = -1;
						foreach ($freights as $freight_item) { ?>
						<tr>
							<?php if ($freight_item['location_vendor_id'] != $last_vendor_id) {
								$last_vendor_id = $freight_item['location_vendor_id']; ?>
							<th class="select_vendor" data-vendorid="<?= $freight_item['location_vendor_id']; ?>" colspan="2" rowspan="<?= $vendors[$freight_item['location_vendor_id']]; ?>"><?= $freight_item['vendors_company_name']?$freight_item['vendors_company_name']:'CK'; ?></th>
							<?php } ?>
							<td colspan="2"><?= $freight_item['ipn']; ?></td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
				<script>
					jQuery('.select_vendor').click(function() {
						vendor_id = jQuery(this).attr('data-vendorid');
						jQuery('.vendor-'+vendor_id+' option').removeAttr('selected');
						jQuery('.vendor-'+vendor_id+' option:last-child').attr('selected', true);
						jQuery('.vendor-'+vendor_id).change();
					});
				</script>
			</td>
		</tr>
		<?php } ?>
	</div>

	<h3>2. Update Shipping Details</h3>
	<span class="infoText">Using the fields below, update the shipping method and shipping address for the new order.</span>

	<div id="shipping_details">
		<div style="float: left;">
			<h4>Shipping Address</h4>
			<table>
				<tbody>
					<tr>
						<td>Company:</td>
						<td><input type="text" name="delivery_company" value="<?= $order->get_header('delivery_company'); ?>"></td>
					</tr>
					<tr>
						<td>Name:</td>
						<td><input type="text" name="delivery_name" value="<?= $order->get_header('delivery_name'); ?>"></td>
					</tr>
					<tr>
						<td>Address:</td>
						<td><input type="text" name="delivery_street_address" value="<?= $order->get_header('delivery_street_address'); ?>"></td>
					</tr>
					<tr>
						<td>Suburb:</td>
						<td><input type="text" name="delivery_suburb" value="<?= $order->get_header('delivery_suburb'); ?>"></td>
					</tr>
					<tr>
						<td>City:</td>
						<td><input type="text" name="delivery_city" value="<?= $order->get_header('delivery_city'); ?>"></td>
					</tr>
					<tr>
						<td>State:</td>
						<td><input type="text" name="delivery_state" value="<?= $order->get_header('delivery_state'); ?>"></td>
					</tr>
					<tr>
						<td>Postcode:</td>
						<td><input type="text" name="delivery_postcode" value="<?= $order->get_header('delivery_postcode'); ?>"></td>
					</tr>
					<tr>
						<td>Country:</td>
						<td><input type="text" name="delivery_country" value="<?= $order->get_header('delivery_country'); ?>"></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div style="float: left; margin-left: 100px;">
			<h4>Shipping Method & Charge</h4>

			<table style="background-color:#eee;">
				<thead>
					<tr>
						<th colspan="2">Order <?= $order->id(); ?> Details</th>
					</tr>
				</thead>
				<tbody>
					<?php $totals = $order->get_totals('shipping');
					$shipttl = 0;
					$sm = $order->get_shipping_method();
					foreach ($totals as $total) {
						if ($total['shipping_method_id']) { // this is the real shipping quote
							$shipttl += $total['value']; ?>
					<tr>
						<td style="text-align:right;">Desc:</td>
						<td><input type="text" disabled value="<?= is_numeric($total['title'])?$sm['method_name']:$total['title']; ?>"></td>
					</tr>
					<tr>
						<td style="text-align:right;">Price:</td>
						<td><input type="text" class="shcost" disabled value="<?= $total['value']; ?>"></td>
					</tr>
						<?php }
						// these are the add on lines
						else { ?>
					<tr>
						<th style="text-align:right;"><?= $total['title']; ?></th>
						<td><?= CK\text::monetize($total['value']); ?></td>
					</tr>
						<?php }
					} ?>
				</tbody>
			</table>
			Shipping Total Remaining on Order <?= $order->id(); ?>:<br>
			<?php if (count($totals) > 1) { ?>
			<div style="margin:3px; padding:3px; background-color:#fcc; text-align:center;"><small>
				If additional freight services (Liftgate, etc) must be removed from<br>
				the original order you must manually do it from the order screen
			</small></div>
			<?php } ?>
			<input type="hidden" id="start_ship_total_value" value="<?= $shipttl; ?>">
			<input type="radio" name="ship_total_remaining" value="requoted"> Re-Quoted from Carrier: <span id="requoted_ship_total" style="background-color:#bfb;"><?= number_format($shipttl, 2, '.', ''); ?></span>
			<input type="hidden" id="requoted_ship_total_value" name="remaining_ship_total[requoted]" value="<?= $shipttl; ?>">
			<br>

			<input type="radio" checked name="ship_total_remaining" value="subtracted"> Straight Subtraction: <span id="subtracted_ship_total" style="background-color:#bfb;"><?= number_format($shipttl, 2, '.', ''); ?></span>
			<input type="hidden" id="subtracted_ship_total_value" name="remaining_ship_total[subtracted]" value="<?= $shipttl; ?>">
			<br>

			<input type="radio" name="ship_total_remaining" value="manual"> Manually Entered Value: <input type="text" class="shcost" name="remaining_ship_total[manual]" value="<?= number_format($shipttl, 2, '.', ''); ?>">
			<hr>

			<a href="javascript:void(0);" id="chooseShipping">Choose Shipping</a><br>
			<table>
				<tbody>
					<tr>
						<td>Method:</td>
						<td>
							<select name="shipping_method" id="shipping_method">
								<option value="0">Please select</option>
								<?php $shipping_methods = prepared_query::fetch('SELECT shipping_code as shipping_method_id, carrier, name FROM shipping_methods WHERE inactive = 0 ORDER BY carrier ASC, name ASC', cardinality::SET);
								foreach ($shipping_methods as $method) { ?>
								<option value="<?= $method['shipping_method_id']; ?>"><?= !empty($method['carrier'])?$method['carrier'].' - '.$method['name']:$method['name']; ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>Price:</td>
						<td>
							<input type="text" class="shcost" name="shipping_price" value="0.00" id="shipping_price">
						</td>
					</tr>
				</tbody>
			</table>
			<script>
				var split_counter = 0;
				var ajax_call = null;
				jQuery('.split_qty').change(function(event) {
					split_counter++;
					setTimeout(function() { re_quote_shipping(); }, 1500); // wait 1.5 seconds, see if we get another qty change
				});
				function re_quote_shipping() {
					split_counter--;
					if (split_counter > 0) return;

					ajax_call = jQuery.ajax({
						url: window.location+'&action=re-quote',
						type: 'GET',
						dataType: 'json',
						data: jQuery('#split_order_form').serialize(),
						timeout: 10000,
						success: function (data) {
							if (data == null) return;
							jQuery('#requoted_ship_total').html(data.ship_cost);
							jQuery('#requoted_ship_total_value').val(data.ship_cost);
						}
					});
				}
				jQuery('#shipping_price').keyup(function(event) {
					start = jQuery('#start_ship_total_value').val();
					subtracted = Math.max(start - jQuery(this).val(), 0);
					jQuery('#subtracted_ship_total').html(subtracted.toFixed(2));
					jQuery('#subtracted_ship_total_value').val(subtracted);
				});
			</script>
		</div>
	</div>
	<div style="clear: both;"></div>

	<h3>3. Update Payment</h3>
	<span class="infoText">Using the fields below, determine if CC Payment goes with the new order or stays with the old order.</span>

	<div id="payment">
		<?php if ($order->get_header('payment_method_id') != 1) { ?>
		N/A
		<input type="hidden" name="cc_payment_goes" value="">
		<?php }
		else { ?>
		<input type="radio" name="cc_payment_goes" value="parent" checked> CC Stays with Parent<br>
		<input type="radio" name="cc_payment_goes" value="child"> CC Goes to Child
		<?php } ?>
	</div>

	<h3>4. Record What You Did</h3>
	<span class="infoText">Using the fields below, add notes to both the original order and the new order with an important information.</span>

	<table id="order_notes">
		<tr>
			<td>Original Order Note:</td>
			<td>New Order Note:</td>
		</tr>
		<tr>
			<td><textarea name="original_order_notes" rows="10" cols="60"></textarea></td>
			<td><textarea name="new_order_notes" rows="10" cols="60"></textarea></td>
		</tr>
	</table>

	<h3>4. Split the Order</h3>
	<span class="infoText">Push the button below to complete the split order process. Shipping will be automatically recalulated for the original order. Tax will be automatically recalculated for both orders.</span><br><br>
	<input type="submit" value="Split Order" name="Split Order" id="split_order_submit" onClick="return ordersSplitVerifyForm();">
</form>
