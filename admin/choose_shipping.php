<?php
require('includes/application_top.php');
setlocale(LC_MONETARY, 'en_US');

require('../includes/functions/shipping_rates.php');

function showShippingOptions($options, $heading, $show_prices=true) { ?>
	<h2 style="font-size:14px;"><?= $heading; ?></h2>
	<table border="0" cellspacing="2px" cellpadding="2px" style="font-size: 11px;">
		<?php foreach ($options as $unused => $option) {
			if ($option['shipping_method_id'] == null) {
				continue;
			}

			$shipping_method = prepared_query::fetch('SELECT name, carrier, description FROM shipping_methods WHERE shipping_code = :shipping_method_id', cardinality::ROW, [':shipping_method_id' => $option['shipping_method_id']]);
			
			if (is_array($option['cost'])) $cost = $option['cost']['list_rate'];
			else $cost = $option['cost']; ?>
		<tr>
			<td><input type="radio" name="shipping_method" onclick="CsSelectShipping('<?= $option['shipping_method_id']; ?>', '<?= $show_prices?$cost:'0'; ?>');"/></td>
			<td nowrap><?= !empty($shipping_method['carrier'])?$shipping_method['carrier'].' - '.$shipping_method['name']:$shipping_method['name']; ?></td>
			<td nowrap>(<?= $shipping_method['description']; ?>)</td>
			<td><?= $show_prices?CK\text::monetize($cost):''; ?></td>
		</tr>
		<?php } ?>
	</table>
<?php }

if (!empty($_POST['freight'])) {
	$total_weight = $_POST['shipping_weight'];
	$products = [];
	foreach ($_POST['products'] as $orders_products_id => $weight) {
		//as with the order, get two instances of the order products
		$products_id = prepared_query::fetch('SELECT products_id FROM orders_products WHERE orders_products_id = :orders_products_id', cardinality::SINGLE, [':orders_products_id' => $orders_products_id]);
		$products[] = ['id' => $products_id, 'weight' => $weight];
	}

	require_once(DIR_FS_CATALOG.'/includes/classes/shipping.php');
	require_once(DIR_FS_CATALOG.'/includes/classes/order.php');

	// fill global values expected by the freight module
	$order = new order($_POST['original_order_id']);

	$order->delivery['country'] = prepared_query::fetch('SELECT countries_id as id, countries_iso_code_2 as iso_code_2, countries_iso_code_3 as iso_code_3, countries_name as name FROM countries WHERE countries_name LIKE ?', cardinality::ROW, [$order->delivery['country']]);

	$shipping = new shipping;
	$quote = $shipping->quote(NULL, 'fedexfreight');
	$show_prices = TRUE; ?>
	<h2 style="font-size:14px;">Freight Option</h2>
	<table border="0" cellspacing="0" cellpadding="0">
		<?php foreach ($quote[0]['methods'] as $method) { ?>
		<tr>
			<td><input type="radio" name="shipping_method" checked></td>
			<script>CsSelectShipping(50, '<?= $show_prices?$method['cost']:0; ?>');</script>
			<td colspan="2">Oversize/Best Fit Shipping</td>
			<td>$<?= $show_prices?number_format($method['cost'], 2):''; ?></td>
		</tr>
		<?php } ?>
		<tr>
			<td colspan="4"><strong>Additional delivery services may be carried over from the original order</strong></td>
		</tr>
	</table>
<?php }
else {
	$weight = $_POST['shipping_weight'];

	$order_weight_tare = $weight + SHIPPING_BOX_WEIGHT;
	$order_weight_percent = ($weight * (SHIPPING_BOX_PADDING / 100 + 1));

	if ($order_weight_percent < $order_weight_tare) {
		$package_weight = $order_weight_tare;
	}
	else {
		$package_weight = $order_weight_percent;
	}

	$shipping_num_boxes = ceil($package_weight/50);
	$shipping_weight = round($package_weight/$shipping_num_boxes, 1);

	$address_data = [
		'address1' => $_POST['street_address'],
		'address2' => $_POST['suburb'],
		'postcode' => $_POST['postcode'],
		'city' => $_POST['city'],
		'state' => $_POST['state'],
		'country' => $_POST['country'],
		'country_address_format_id' => '1'
	];

	$address_type = new ck_address_type();
	$address_type->load('header', $address_data);
	$address = new ck_address2(NULL, $address_type);

	$country = ck_address2::get_country($_POST['country']);

	$customer = new ck_customer2($_POST['customers_id']);

	$shipping_carriers = [];

	//do fedex - the only circumstance where we don't show fedex is when the customer is shipping on their own account and
	// they do not have a fedex account number set up
	if (!($customer->get_header('own_shipping_account') != 0 && trim($customer->get_header('fedex_account_number')) == '' && trim($customer->get_header('ups_account_number')) != '')) {
		$shipping_carriers['FEDEX'] = @get_fedex_rates($address, $shipping_weight, $shipping_num_boxes);
	}

	//do UPS - we only show fedex when the customer ships on their own account and they have an account number set
	if ($customer->get_header('own_shipping_account') != 0 && trim($customer->get_header('ups_account_number')) != '') {
		$shipping_carriers['UPS'] = @get_ups_rates($address, $shipping_weight, $shipping_num_boxes);
	}

	//do USPS - we only show USPS rates when shipping INTL, to a US territory, or to Alaska or Hawaii
	if ($address->is_international() || in_array($address->get_state(), ['AK', 'HI'])) {
		$shipping_carriers['USPS'] = @get_usps_rates($address, $shipping_weight, $shipping_num_boxes);
	}

	//now we construct "other" shipping options
	$other_options = [];
	//store pickup
	$other_options[] = array('shipping_method_id' => '47', 'cost' => '0');
	if (!$customer->is('dealer')) {
		$other_options[] = array('shipping_method_id' => '48', 'cost' => '0');
	}
	$shipping_carriers['OTHER'] = $other_options;

	if (!empty($shipping_carriers['FEDEX'])) {
		$show_shipping_prices = true;
		if ($customer->get_header('own_shipping_account') != 0 && trim($customer->get_header('fedex_account_number')) != '') {
			$show_shipping_prices = false;
		}
		showShippingOptions($shipping_carriers['FEDEX'], 'FedEx Shipping Options', $show_shipping_prices);
	}

	if (!empty($shipping_carriers['UPS'])) {
		showShippingOptions($shipping_carriers['UPS'], 'UPS Shipping Options', false);
	}
	if (!empty($shipping_carriers['USPS'])) {
		showShippingOptions($shipping_carriers['USPS'], 'USPS Shipping Options');
	}
	if (!empty($shipping_carriers['OTHER'])) {
		showShippingOptions($shipping_carriers['OTHER'], 'Other Shipping Options');
	}
}
?>
<style>
	.infoText{
		margin-left: 25px;
		font-family: arial;
		font-size: 10px;
		color: #787878;
	}
</style>
<input type="hidden" name="__CS_shipping_method_id" id="__CS_shipping_method_id" value="0">
<input type="hidden" name="__CS_cost" id="__CS_cost" value="0">
<span class="infoText">NOTE: If you choose Standard (free) shipping, please confirm that the order should be eligible for free shipping.</span>
