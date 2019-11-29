<?php
require('includes/application_top.php');

if (empty($_REQUEST['pors_id'])) {
	echo "pors_id not set!";
	exit();
}
$pors_id = $_REQUEST['pors_id'];
$poId = $_REQUEST['poId'];

$products = prepared_query::fetch('SELECT DISTINCT psc.stock_id, psc.stock_name, DATE(pors.date) as date, pors.date as date_time, porp.id as porp_id, pop.quantity, porp.quantity_received, s.serial, psc.serialized, psc.stock_quantity, porp.purchase_order_product_id FROM purchase_order_products pop LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id LEFT JOIN purchase_order_receiving_sessions pors ON pors.id = porp.receiving_session_id LEFT JOIN products_stock_control psc ON psc.stock_id = pop.ipn_id LEFT JOIN serials_history sh ON sh.pop_id = pop.id AND sh.pors_id = pors.id LEFT JOIN serials s ON sh.serial_id = s.id WHERE pors.id = ? AND quantity_received > 0 ORDER BY pop.id, s.id', cardinality::SET, array($pors_id));
?>
<form method="post" action="po_viewer.php?action=unreceive&poId=<?= $poId; ?>&porsId=<?= $pors_id; ?>">
	<table border="0" cellspacing="3" cellpadding="3" id="unreceive_table">
		<tr>
			<td colspan="4" align="center"><b>Choose which products to unreceive</b></td>
		</tr>
		<tr style="background-color:#888;">
			<td>&#10004;</td>
			<td>Qty Unreceive</td>
			<td>Name</td>
			<td>Quantity<br/>Received</td>
			<td>Serial</td>
		</tr>
		<?php
		foreach ($products as $product) {
			// serialized products won't even be sent if they're not checked
			// unserialized products won't be sent if they don't have a positive value entered
			if (!isset($_GET['unreceive_prod'][$pors_id][$product['purchase_order_product_id']])) continue;
			$unreceipt_request = $_GET['unreceive_prod'][$pors_id][$product['purchase_order_product_id']];
			// if the specific serial isn't sent, then it wasn't selected
			if ($product['serialized'] && !isset($unreceipt_request[$product['serial']]) && !isset($unreceipt_request['missing-serials'])) continue;
			
			if ($product['serialized'] && isset($unreceipt_request[$product['serial']])) {
				$unrec_key = $product['serial'];
				$unrec_qty = 1;
				$unrec_qty_received = 1;
				$serial = $product['serial'];
			}
			elseif ($product['serialized'] && isset($unreceipt_request['missing-serials'])) {
				$unrec_key = 'missing-serials';
				$unrec_qty = $unreceipt_request['missing-serials'];
				$unrec_qty_received = $product['quantity_received'];
				$serial = 'missing';
			}
			elseif (!$product['serialized']) {
				$unrec_key = 'noserial';
				$unrec_qty = $unreceipt_request['unserialized'];
				$unrec_qty_received = $product['quantity_received'];
				$serial = '';
			} ?>
		<tr>
			<td>
				<input class="unreceive_check_boxes" type="checkbox" name="unreceive_porps[<?= $product['porp_id']; ?>][<?= $unrec_key; ?>]" value="<?= $unrec_qty; ?>" checked>
			</td>
			<td><?= $unrec_qty; ?></td>
			<td><?= $product['stock_name']; ?></td>
			<td align="center"><?= $unrec_qty_received; ?></td>
			<td><?= $serial; ?></td>
		</tr>
		<?php } ?>
		<tr style="background-color:#888;">
			<td colspan="2">
				<input type="button" value="Check All" onclick="$$('input.unreceive_check_boxes').each(function(e) { e.checked = 1; });">
				<input type="button" value="Uncheck All" onclick="$$('input.unreceive_check_boxes').each(function(e) { e.checked = 0; });">
			</td>
			<td align="right" colspan="3"><input type="button" value="Close" onClick="$('unreceive_dialog_box').hide();">
				<input type="submit" value="Unreceive">
			</td>
		</tr>
	</table>
</form>
