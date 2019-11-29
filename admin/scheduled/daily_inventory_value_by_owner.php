<?php
require_once(__DIR__.'/../../includes/application_top.php');

$daily_inventory_value_id = prepared_query::fetch('SELECT MAX(daily_inventory_value_id) FROM ck_daily_inventory_value', cardinality::SINGLE);

prepared_query::execute('INSERT INTO ck_daily_inventory_value_owners ( daily_inventory_value_id, admin_id, total_value, equipment_value, commodity_value, equipment_serialized_value, equipment_unserialized_value, commodity_serialized_value, commodity_unserialized_value, total_expected, equipment_expected, commodity_expected, equipment_serialized_expected, equipment_unserialized_expected, commodity_serialized_expected, commodity_unserialized_expected ) SELECT :daily_inventory_value_id, v.admin_id, IFNULL(v.total_value, 0), IFNULL(v.equipment_value, 0), IFNULL(v.commodity_value, 0), IFNULL(v.equipment_serialized_value, 0), IFNULL(v.equipment_unserialized_value, 0), IFNULL(v.commodity_serialized_value, 0), IFNULL(v.commodity_unserialized_value, 0), IFNULL(e.total_expected, 0), IFNULL(e.equipment_expected, 0), IFNULL(e.commodity_expected, 0), IFNULL(e.equipment_serialized_expected, 0), IFNULL(e.equipment_unserialized_expected, 0), IFNULL(e.commodity_serialized_expected, 0), IFNULL(e.commodity_unserialized_expected, 0) FROM (SELECT so.owner_admin_id as admin_id, SUM(so.cost) as total_value, SUM(IF(pscc.vertical_id = 6, so.cost, 0)) as equipment_value, SUM(IF(pscc.vertical_id != 6, so.cost, 0)) as commodity_value, SUM(IF(psc.serialized = 1 AND pscc.vertical_id = 6, so.cost, 0)) as equipment_serialized_value, 0 as equipment_unserialized_value, SUM(IF(psc.serialized = 1 AND pscc.vertical_id != 6, so.cost, 0)) as commodity_serialized_value, 0 as commodity_unserialized_value FROM products_stock_control psc JOIN ckv_serial_owner so ON psc.serialized = 1 AND psc.stock_id = so.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id GROUP BY so.owner_admin_id) v LEFT JOIN (SELECT po.owner_admin_id as admin_id, SUM(opl.unreceived_quantity * pop.cost) as total_expected, SUM(IF(pscc.vertical_id = 6, opl.unreceived_quantity * pop.cost, 0)) as equipment_expected, SUM(IF(pscc.vertical_id != 6, opl.unreceived_quantity * pop.cost, 0)) as commodity_expected, SUM(IF(psc.serialized = 1 AND pscc.vertical_id = 6, opl.unreceived_quantity * pop.cost, 0)) as equipment_serialized_expected, SUM(IF(psc.serialized = 0 AND pscc.vertical_id = 6, opl.unreceived_quantity * pop.cost, 0)) as equipment_unserialized_expected, SUM(IF(psc.serialized = 1 AND pscc.vertical_id != 6, opl.unreceived_quantity * pop.cost, 0)) as commodity_serialized_expected, SUM(IF(psc.serialized = 0 AND pscc.vertical_id != 6, opl.unreceived_quantity * pop.cost, 0)) as commodity_unserialized_expected FROM products_stock_control psc JOIN ckv_open_po_lines opl ON psc.stock_id = opl.stock_id LEFT JOIN purchase_order_products pop ON opl.purchase_order_product_id = pop.id LEFT JOIN purchase_orders po ON pop.purchase_order_id = po.id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id GROUP BY po.owner_admin_id) e ON v.admin_id = e.admin_id', [':daily_inventory_value_id' => $daily_inventory_value_id]);
?>
