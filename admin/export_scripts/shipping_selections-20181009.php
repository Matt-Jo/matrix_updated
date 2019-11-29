<?php
function export_data($data=NULL) {
	$batch_size = 50;
	$limit = new prepared_limit();
	$limit->set_batch_size($batch_size);

	while ($shipping_selections = prepared_query::fetch("SELECT orders.orders_id, orders.dsm as customer_account, IF(orders.dsm=1, s.shipping_method_carrier_id, NULL) as customer_account_carrier_id, IF(orders.dsm=1, CASE WHEN s.carrier_code = 'fedex' THEN orders.fedex_account_number WHEN s.carrier_code = 'ups' THEN orders.customers_ups ELSE NULL END, NULL) as customer_account_number, s.shipping_code as shipping_method_id, 1 as shipping_method_affirmative, 1 as expedited_processing, fs.liftgate as ltl_liftgate, fs.inside as ltl_inside_delivery, fs.limitaccess as ltl_limited_access, orders.date_purchased as selection_date FROM orders LEFT JOIN ck_freight_shipment fs ON orders.orders_id = fs.orders_id LEFT JOIN (SELECT ot.orders_id, sm.shipping_code, smc.shipping_method_carrier_id, smc.carrier_code FROM orders_total ot JOIN shipping_methods sm ON ot.external_id = sm.shipping_code LEFT JOIN shipping_method_carriers smc ON sm.carrier = smc.carrier_name WHERE ot.class = 'ot_shipping') s ON orders.orders_id = s.orders_id LIMIT ".$limit->limit())) {

		if ($limit->get_iteration_number() == 1) echo implode(',', array_keys($shipping_selections[0]))."\n";

		foreach ($shipping_selections as $selection) {
			echo implode(',', $selection)."\n";
		}

		flush();
	}
}
?>