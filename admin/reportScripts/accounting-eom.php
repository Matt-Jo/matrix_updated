<?php
require(__DIR__.'/../../includes/application_top.php');

$inventory_values = prepared_query::fetch('SELECT SUM(ia.on_hand_value) as inventory_total, SUM(IF(pscc.vertical_id = 6, ia.on_hand_value, 0)) as equipment_value, SUM(IF(pscc.vertical_id != 6, ia.on_hand_value, 0)) as commodity_value, SUM(IF(psc.serialized = 0 AND pscc.vertical_id = 6, ia.on_hand_value, 0)) as unserialized_equipment_value, SUM(IF(psc.serialized = 0 AND pscc.vertical_id != 6, ia.on_hand_value, 0)) as unserialized_commodity_value, SUM(IF(psc.serialized = 1 AND pscc.vertical_id = 6, ia.on_hand_value, 0)) as serialized_equipment_value, SUM(IF(psc.serialized = 1 AND pscc.vertical_id != 6, ia.on_hand_value, 0)) as serialized_commodity_value FROM products_stock_control psc JOIN ckv_inventory_accounting ia ON psc.stock_id = ia.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id', cardinality::ROW);

$total_ar_outstanding = ck_invoice::get_total_ar_outstanding();

$total_ap_outstanding = prepared_query::fetch('SELECT SUM(opl.unreceived_quantity * pop.cost) FROM ckv_open_po_lines opl JOIN purchase_order_products pop ON opl.purchase_order_product_id = pop.id', cardinality::SINGLE);

// this one is no longer really relevant - I'm removing it from the email, but leaving it in the recorded snapshot just in case it becomes relevant again in the future
$total_ap_unpaid = prepared_query::fetch('SELECT SUM(pop.cost * porp.quantity_received) FROM purchase_orders po LEFT JOIN purchase_order_products pop ON po.id = pop.purchase_order_id LEFT JOIN purchase_order_received_products porp ON porp.purchase_order_product_id = pop.id WHERE porp.paid = 0', cardinality::SINGLE);

prepared_query::execute('INSERT INTO EOD_accounting (total_inventory, outstanding_rec, outstanding_pay, outstanding_unpaid_items) VALUES (:total_inventory, :outstanding_rec, :outstanding_pay, :outstanding_unpaid_items)', [':total_inventory' => $inventory_values['inventory_total'], ':outstanding_rec' => $total_ar_outstanding, ':outstanding_pay' => $total_ap_outstanding, ':outstanding_unpaid_items' => $total_ap_unpaid]);

$unapplied_payments = prepared_query::fetch('SELECT SUM(IF(payment_method_id IN (8, 9), unapplied_amount, 0)) as unapplied_account_credits, SUM(IF(payment_method_id NOT IN (8, 9), unapplied_amount, 0)) as unapplied_payments FROM ckv_unapplied_payments', cardinality::ROW);

$expected_revenue = prepared_query::fetch('SELECT SUM(ot.value) FROM orders_total ot JOIN orders o ON ot.orders_id = o.orders_id AND o.orders_status NOT IN (:shipped, :canceled, :other)', cardinality::SINGLE, [':shipped' => ck_sales_order::STATUS_SHIPPED, ':canceled' => ck_sales_order::STATUS_CANCELED, ':other' => 9]);

$body = '';
$body .= 'Total Inventory: <b>'.CK\text::monetize($inventory_values['inventory_total']).'</b><br>'."\n";
$body .= 'Outstanding Receivables: <b>'.CK\text::monetize($total_ar_outstanding).'</b><br>'."\n";
$body .= 'Outstanding PO Items: <b>'.CK\text::monetize($total_ap_outstanding).'</b><br>'."\n";
$body .= 'Total Unapplied Payments: <b>'.CK\text::monetize($unapplied_payments['unapplied_payments']).'</b><br>'."\n";
$body .= 'Total Account Credits: <b>'.CK\text::monetize($unapplied_payments['unapplied_account_credits']).'</b><br>'."\n";
$body .= 'Total Booked/Unbilled Revenue: <b>'.CK\text::monetize($expected_revenue).'</b><br>'."\n";

$body .= '<h2>Inventory Breakdown</h2>'."\n";
$body .= 'Equipment Total: <b>'.CK\text::monetize($inventory_values['equipment_value']).'</b><br>'."\n";
$body .= 'Unserialized Equipment: <b>'.CK\text::monetize($inventory_values['unserialized_equipment_value']).'</b><br>'."\n";
$body .= 'Serialized Equipment: <b>'.CK\text::monetize($inventory_values['serialized_equipment_value']).'</b><br><br>'."\n\n";
$body .= 'Commodity Total: <b>'.CK\text::monetize($inventory_values['commodity_value']).'</b><br>'."\n";
$body .= 'Unserialized Commodity: <b>'.CK\text::monetize($inventory_values['unserialized_commodity_value']).'</b><br>'."\n";
$body .= 'Serialized Commodity: <b>'.CK\text::monetize($inventory_values['serialized_commodity_value']).'</b><br>';

// write the report to the server in case delivery fails
$filename = realpath(__DIR__.'/../reports');
$filename .= '/EOD-'.ck_datetime::TODAY()->format('Y-m-d').'.html';
file_put_contents($filename, $body);

$mailer = service_locator::get_mail_service();
$mail = $mailer->create_mail()
    ->set_from('webmaster@cablesandkits.com', 'CK Webmaster')
    ->add_to('christin@cablesandkits.com', 'Christin Haynie')
    ->add_to('gary.epp@cablesandkits.com', 'Gary Epp')
    ->add_to('craig@cablesandkits.com', 'Craigory Haynie')
    ->add_to('accounting@cablesandkits.com', 'Accounting')
    ->add_to('ckeod@robot.zapier.com')
    ->set_subject('EOD Accounting Report')
    ->set_body($body);
$mailer->send($mail);
?>
