<?php
$skip_check = TRUE;
require_once('includes/application_top.php');

$stock_name = $_GET['stock_name'];
$ipn = prepared_query::fetch("select psc.stock_id, psc.on_order, psc.stock_name, psc.stock_weight, psc.stock_description,v2s.vendors_price, v2s.vendors_pn, v2s.id as v2s_id from products_stock_control psc left join vendors_to_stock_item v2s on (v2s.stock_id=psc.stock_id and v2s.vendors_id=:vendors_id) where psc.stock_name = :ipn", cardinality::ROW, [':vendors_id' => $_GET['vendors_id'], ':ipn' => $stock_name]);

$stock_description = addslashes(trim($ipn['stock_description']));
$stock_description = str_replace("\r", " ", $stock_description);
$stock_description = str_replace("\n", " ", $stock_description);

$receiving_histories = prepared_query::fetch("select porp.quantity_received, pop.cost, pors.date, v.vendors_company_name, po.purchase_order_number from purchase_order_received_products porp, purchase_order_products pop, purchase_orders po, purchase_order_receiving_sessions pors, vendors v where porp.purchase_order_product_id = pop.id and porp.receiving_session_id = pors.id and pop.purchase_order_id = po.id and pop.ipn_id = :stock_id and po.vendor = v.vendors_id order by pors.date desc", cardinality::SET, [':stock_id' => $ipn['stock_id']]);
?>
({
	'stock_id': '<?= $ipn['stock_id']; ?>',
	'stock_name': '<?= $ipn['stock_name']; ?>',
	'stock_weight': '<?= round($ipn['stock_weight'], 2)?>',
	'on_order': '<?= $ipn['on_order']; ?>',
	'vendor_product': '<?= $ipn['vendors_pn']; ?>',
	v2s_id:'<?= $ipn['v2s_id']; ?>',
	'stock_description': '<?= $stock_description; ?>',
	'receiving_history': [
		<?php foreach ($receiving_histories as $idx => $receiving_history) {
			if ($idx > 0) echo ', '; ?>
		{
				'vendor': '<?= addslashes($receiving_history['vendors_company_name']); ?>',
				'quantity': '<?= $receiving_history['quantity_received']; ?>',
				'cost': '<?= $receiving_history['cost']; ?>',
				'date': '<?= date('m/d/y', strtotime($receiving_history['date'])); ?>',
				'purchase_order_number': '<?= $receiving_history['purchase_order_number']; ?>'
		}
		<?php } ?>
	],
	'most_recent_cost': '<?= $ipn['vendors_price']; ?>'
})
