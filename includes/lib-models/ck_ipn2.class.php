<?php
class ck_ipn2 extends ck_archetype {

	protected static $skeleton_type = 'ck_ipn_type';

	const MARKET_STATE_RETAIL = 0;
	const MARKET_STATE_BROKER = 1;

	protected static $queries = [
		'ipns_for_purchase_management' => [
			'qry' => 'SELECT psc.stock_id, psc.stock_name as ipn, psc.creation_reviewed, psc.stock_quantity, psc.stock_weight, psc.conditioning_notes, psc.stock_description, psc.stock_price, psc.dealer_price, psc.wholesale_high_price, psc.wholesale_low_price, psc.last_quantity_change, psc.last_weight_change, psc.on_order, psc.discontinued, psc.average_cost, psc.target_buy_price, psc.target_min_qty, psc.target_max_qty, psc.serialized, psc.conditions, c.conditions_name, c.market_state, psc.products_stock_control_category_id, pscc.name as ipn_category, pscv.id as products_stock_control_vertical_id, pscv.name as ipn_vertical, pscg.products_stock_control_group_id, pscg.group_name as ipn_group, psc.warranty_id, w.warranty_name, psc.dealer_warranty_id, dw.dealer_warranty_name, psc.vendor_to_stock_item_id, psc.date_added, psc.max_displayed_quantity, psc.max_inventory_level, psc.target_inventory_level, psc.min_inventory_level, psc.freight, psc.drop_ship, psc.non_stock, psc.last_stock_price_confirmation, psc.last_dealer_price_confirmation, psc.last_wholesale_high_price_confirmation, psc.last_wholesale_low_price_confirmation, psc.pricing_review, psc.nontaxable, psc.liquidate, psc.liquidate_admin_note, psc.liquidate_user_note, psc.ca_allocated_quantity, psc.current_daily_runrate, psc.current_days_on_hand, psc.navx_tracking_code, psc.pic_audit, psc.pic_problem, psc.donotbuy, psc.donotbuy_date, psc.donotbuy_user_id, dnba.admin_email_address as donotbuy_admin, psc.is_bundle, psc.dlao_product, psc.special_order_only, psc.eccn_code, psc.hts_code, psci.image, psci.image_med, psci.image_lrg, psci.image_sm_1, psci.image_xl_1, psci.image_sm_2, psci.image_xl_2, psci.image_sm_3, psci.image_xl_3, psci.image_sm_4, psci.image_xl_4, psci.image_sm_5, psci.image_xl_5, psci.image_sm_6, psci.image_xl_6, psce.stock_location as bin1, psce.stock_location_2 as bin2, vtsi.always_avail as always_available, vtsi.lead_time, v.vendors_id, v.vendors_company_name, vtsi.vendors_pn, vtsi.case_qty, vtsi.vendors_price FROM products_stock_control psc LEFT JOIN conditions c ON psc.conditions = c.conditions_id LEFT JOIN warranties w ON psc.warranty_id = w.warranty_id LEFT JOIN dealer_warranties dw ON psc.dealer_warranty_id = dw.dealer_warranty_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id LEFT JOIN products_stock_control_groups pscg ON pscv.products_stock_control_group_id = pscg.products_stock_control_group_id LEFT JOIN admin dnba ON psc.donotbuy_user_id = dnba.admin_id LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE (:include_dropship = 1 OR psc.drop_ship != 1) AND psc.is_bundle != 1 ORDER BY v.vendors_company_name ASC, psc.stock_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'inventory_for_purchase_management' => [
			'qry' => 'SELECT psc.stock_id, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END as on_hand, psc.ca_allocated_quantity as ca_allocated, op.allocated - IFNULL(po.po_allocated, 0) as local_allocated, IFNULL(op.allocated, 0) + IFNULL(psc.ca_allocated_quantity, 0) - IFNULL(po.po_allocated, 0) as allocated, IFNULL(po.po_allocated, 0) as po_allocated, IFNULL(ih.on_hold, 0) as on_hold, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END - IFNULL(psc.ca_allocated_quantity, 0) - IFNULL(op.allocated, 0) + IFNULL(po.po_allocated, 0) - IFNULL(ih.on_hold, 0) as available, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END - IFNULL(ih.on_hold, 0) as salable, IFNULL(ic.in_conditioning, 0) as in_conditioning, psc.max_displayed_quantity, psc.on_order, psc.on_order - IFNULL(po.po_allocated, 0) as adjusted_on_order, NULL as adjusted_available_quantity FROM products_stock_control psc LEFT JOIN (SELECT ipn as stock_id, COUNT(id) as quantity FROM serials WHERE status IN (2, 3, 6) GROUP BY ipn) s ON psc.stock_id = s.stock_id LEFT JOIN (SELECT p.stock_id, SUM(op.products_quantity) allocated FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE o.orders_status IN (1, 2, 5, 7, 8, 10, 11, 12) GROUP BY p.stock_id) op ON psc.stock_id = op.stock_id LEFT JOIN (SELECT p.stock_id, SUM(potoa.quantity) as po_allocated FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id JOIN purchase_order_to_order_allocations potoa ON op.orders_products_id = potoa.order_product_id AND potoa.purchase_order_product_id > 0 WHERE o.orders_status IN (1, 2, 5, 7, 8, 10, 11, 12) GROUP BY p.stock_id) po ON psc.stock_id = po.stock_id LEFT JOIN (SELECT ih.stock_id, SUM(ih.quantity) as on_hold FROM inventory_hold ih LEFT JOIN serials s ON ih.serial_id = s.id WHERE s.id IS NULL OR s.status = 6 GROUP BY ih.stock_id) ih ON psc.stock_id = ih.stock_id LEFT JOIN (SELECT stock_id, SUM(quantity) as in_conditioning FROM inventory_hold WHERE reason_id IN (4, 8, 11, 12) GROUP BY stock_id) ic ON psc.stock_id = ic.stock_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE psc.drop_ship != 1 AND psc.is_bundle != 1 ORDER BY allocated DESC, v.vendors_company_name ASC, psc.stock_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'sales_history_for_purchase_management' => [
			// fancy date wrangling to bring the number of results down by an order of magnitude, from ~50,000 to ~5,000
			'qry' => 'SELECT psc.stock_id, 0 as orders_status_id, SUM(op.products_quantity) as products_quantity, CASE WHEN TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 30 THEN DATE_SUB(DATE(NOW()), INTERVAL 10 DAY) WHEN TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 60 THEN DATE_SUB(DATE(NOW()), INTERVAL 40 DAY) ELSE DATE_SUB(DATE(NOW()), INTERVAL 90 DAY) END as date_purchased, op.exclude_forecast FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id JOIN orders_products op ON p.products_id = op.products_id JOIN orders o ON op.orders_id = o.orders_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE psc.drop_ship != 1 AND psc.is_bundle != 1 AND op.exclude_forecast = 0 AND o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 180 GROUP BY psc.stock_id, CASE WHEN TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 30 THEN DATE_SUB(DATE(NOW()), INTERVAL 10 DAY) WHEN TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 60 THEN DATE_SUB(DATE(NOW()), INTERVAL 40 DAY) ELSE DATE_SUB(DATE(NOW()), INTERVAL 90 DAY) END, op.exclude_forecast ORDER BY v.vendors_company_name ASC, psc.stock_name ASC, o.date_purchased DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'purchase_history_for_purchase_management' => [
			'qry' => 'SELECT psc.stock_id, po.status as status_id, po.expected_date, pop.quantity, SUM(porp.quantity_received) as quantity_received, potoa.allocated_quantity FROM purchase_order_products pop JOIN products_stock_control psc ON pop.ipn_id = psc.stock_id JOIN purchase_orders po ON pop.purchase_order_id = po.id LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id LEFT JOIN (SELECT purchase_order_product_id, SUM(quantity) as allocated_quantity FROM purchase_order_to_order_allocations GROUP BY purchase_order_product_id) potoa ON pop.id = potoa.purchase_order_product_id WHERE psc.drop_ship != 1 AND psc.is_bundle != 1 AND po.status IN (1, 2) AND TO_DAYS(po.expected_date) <= TO_DAYS(NOW()) + IFNULL(NULLIF(psc.target_inventory_level, 0), psc.max_inventory_level) GROUP BY po.id, psc.stock_id, po.status, po.expected_date, pop.quantity, potoa.allocated_quantity HAVING pop.quantity - IFNULL(SUM(porp.quantity_received), 0) > 0 ORDER BY po.expected_date DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'change_history_for_purchase_management' => [
			'qry' => 'SELECT psc.stock_id, pscch.change_id, pscch.change_date, pscch.type_id as change_code, pscch.old_value, pscch.new_value FROM products_stock_control_change_history pscch JOIN products_stock_control psc ON pscch.stock_id = psc.stock_id JOIN products_stock_control_change_history_types psccht ON pscch.type_id = psccht.id WHERE psc.drop_ship != 1 AND psc.is_bundle != 1 AND pscch.type_id IN (41, 42) AND TO_DAYS(pscch.change_date) >= TO_DAYS(NOW()) - 180 ORDER BY pscch.change_date DESC, pscch.change_id DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'requiring_ipns_for_purchase_management' => [
			'qry' => 'SELECT DISTINCT psc.stock_id, rp.stock_id as requiring_stock_id FROM products_stock_control psc JOIN products p ON psc.stock_id = p.stock_id JOIN product_addons pa ON p.products_id = pa.product_addon_id AND pa.included = 1 JOIN products rp ON pa.product_id = rp.products_id JOIN products_stock_control rpsc ON rp.stock_id = rpsc.stock_id WHERE psc.drop_ship != 1 AND psc.is_bundle != 1 AND rp.products_status = 1 AND rpsc.drop_ship != 1 AND rpsc.is_bundle != 1',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'last_specials_dates_for_purchase_management' => [
			'qry' => 'SELECT psc.stock_id, MAX(o.date_purchased) as last_specials_date FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE psc.drop_ship != 1 AND psc.is_bundle != 1 AND op.exclude_forecast = 0 AND o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 180 AND op.price_reason = 2 GROUP BY psc.stock_id ORDER BY v.vendors_company_name ASC, psc.stock_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'ipns_for_inventory_report' => [
			'qry' => 'SELECT psc.stock_id, psc.stock_name as ipn, psc.creation_reviewed, psc.products_stock_control_category_id, psc.stock_price, psc.dealer_price, psc.wholesale_high_price, psc.wholesale_low_price, psc.average_cost, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END - IFNULL(ih.on_hold, 0) AS salable FROM products_stock_control psc LEFT JOIN (SELECT ipn as stock_id, COUNT(id) AS quantity FROM serials WHERE status IN (2, 3, 6) GROUP BY ipn) s ON psc.stock_id = s.stock_id LEFT JOIN (SELECT ih.stock_id, SUM(ih.quantity) AS on_hold FROM inventory_hold ih LEFT JOIN serials s ON ih.serial_id = s.id WHERE s.id IS NULL OR s.status = 6 GROUP BY ih.stock_id) ih ON psc.stock_id = ih.stock_id JOIN products p ON psc.stock_id = p.stock_id AND p.products_status = 1 WHERE psc.discontinued = 0 AND psc.dlao_product = 0 AND psc.is_bundle = 0 ORDER BY psc.stock_id DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'inventory_for_inventory_report' => [
			'qry' => 'SELECT psc.stock_id, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END - IFNULL(ih.on_hold, 0) AS salable FROM products_stock_control psc LEFT JOIN (SELECT ipn as stock_id, COUNT(id) as quantity FROM serials WHERE status IN (2, 3, 6) GROUP BY ipn) s ON psc.stock_id = s.stock_id LEFT JOIN (SELECT ih.stock_id, SUM(ih.quantity) as on_hold FROM inventory_hold ih LEFT JOIN serials s ON ih.serial_id = s.id WHERE s.id IS NULL OR s.status = 6 GROUP BY ih.stock_id) ih ON psc.stock_id = ih.stock_id JOIN products p ON psc.stock_id = p.stock_id AND p.products_status = 1 WHERE psc.is_bundle = 0 AND psc.dlao_product = 0 AND psc.discontinued = 0 ORDER BY psc.stock_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'ipn_header' => [
			'qry' => 'SELECT psc.stock_id, psc.stock_name as ipn, psc.creation_reviewed, psc.creator, psc.creation_reviewer, psc.creation_reviewed_date, psc.stock_quantity, psc.stock_weight, psc.conditioning_notes, psc.stock_description, psc.stock_price, psc.dealer_price, psc.wholesale_high_price, psc.wholesale_low_price, psc.last_quantity_change, psc.last_weight_change, psc.on_order, psc.discontinued, psc.average_cost, psc.target_buy_price, psc.target_min_qty, psc.target_max_qty, psc.serialized, psc.conditions, psc.current_daily_runrate, psc.current_days_on_hand, c.conditions_name, c.market_state, psc.products_stock_control_category_id, pscc.name as ipn_category, pscv.id as products_stock_control_vertical_id, pscv.name as ipn_vertical, pscg.products_stock_control_group_id, pscg.group_name as ipn_group, psc.warranty_id, w.warranty_name, psc.dealer_warranty_id, dw.dealer_warranty_name, psc.vendor_to_stock_item_id, psc.date_added, psc.max_displayed_quantity, psc.max_inventory_level, psc.target_inventory_level, psc.min_inventory_level, psc.freight, psc.drop_ship, psc.non_stock, psc.last_stock_price_confirmation, psc.last_dealer_price_confirmation, psc.last_wholesale_high_price_confirmation, psc.last_wholesale_low_price_confirmation, psc.pricing_review, psc.nontaxable, psc.liquidate, psc.liquidate_admin_note, psc.liquidate_user_note, psc.ca_allocated_quantity, psc.navx_tracking_code, psc.pic_audit, psc.pic_problem, psc.donotbuy, psc.donotbuy_date, psc.donotbuy_user_id, psc.current_days_on_hand, psc.image_reference, dnba.admin_email_address as donotbuy_admin, psc.is_bundle, psc.dlao_product, psc.special_order_only, psc.eccn_code, psc.hts_code, psc.bundle_price_flows_from_included_products, psc.bundle_price_modifier, psc.bundle_price_signum, psci.image, psci.image_med, psci.image_lrg, psci.image_sm_1, psci.image_xl_1, psci.image_sm_2, psci.image_xl_2, psci.image_sm_3, psci.image_xl_3, psci.image_sm_4, psci.image_xl_4, psci.image_sm_5, psci.image_xl_5, psci.image_sm_6, psci.image_xl_6, psce.stock_location as bin1, psce.stock_location_2 as bin2, vtsi.always_avail as always_available, vtsi.lead_time, v.vendors_id, v.vendors_company_name, vtsi.vendors_pn, vtsi.case_qty, vtsi.vendors_price FROM products_stock_control psc LEFT JOIN conditions c ON psc.conditions = c.conditions_id LEFT JOIN warranties w ON psc.warranty_id = w.warranty_id LEFT JOIN dealer_warranties dw ON psc.dealer_warranty_id = dw.dealer_warranty_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id LEFT JOIN products_stock_control_groups pscg ON pscv.products_stock_control_group_id = pscg.products_stock_control_group_id LEFT JOIN admin dnba ON psc.donotbuy_user_id = dnba.admin_id LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE (:context = \'stock_id\' AND psc.stock_id = :stock_id) OR (:context = \'stock_name\' AND psc.stock_name LIKE :stock_name)',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL,
		],

		'ipn_header_list' => [
			'qry' => 'SELECT psc.stock_id, psc.stock_name as ipn, psc.creation_reviewed, psc.stock_quantity, psc.stock_weight, psc.conditioning_notes, psc.stock_description, psc.stock_price, psc.dealer_price, psc.wholesale_high_price, psc.wholesale_low_price, psc.last_quantity_change, psc.last_weight_change, psc.on_order, psc.discontinued, psc.average_cost, psc.target_buy_price, psc.target_min_qty, psc.target_max_qty, psc.serialized, psc.conditions, c.conditions_name, c.market_state, psc.products_stock_control_category_id, pscc.name as ipn_category, pscv.id as products_stock_control_vertical_id, pscv.name as ipn_vertical, pscg.products_stock_control_group_id, pscg.group_name as ipn_group, psc.warranty_id, w.warranty_name, psc.dealer_warranty_id, dw.dealer_warranty_name, psc.vendor_to_stock_item_id, psc.date_added, psc.max_displayed_quantity, psc.max_inventory_level, psc.target_inventory_level, psc.min_inventory_level, psc.freight, psc.drop_ship, psc.non_stock, psc.last_stock_price_confirmation, psc.last_dealer_price_confirmation, psc.last_wholesale_high_price_confirmation, psc.last_wholesale_low_price_confirmation, psc.pricing_review, psc.nontaxable, psc.liquidate, psc.liquidate_admin_note, psc.liquidate_user_note, psc.ca_allocated_quantity, psc.navx_tracking_code, psc.pic_audit, psc.pic_problem, psc.donotbuy, psc.donotbuy_date, psc.donotbuy_user_id, dnba.admin_email_address as donotbuy_admin, psc.is_bundle, psc.dlao_product, psc.special_order_only, psc.eccn_code, psc.hts_code, psci.image, psci.image_med, psci.image_lrg, psci.image_sm_1, psci.image_xl_1, psci.image_sm_2, psci.image_xl_2, psci.image_sm_3, psci.image_xl_3, psci.image_sm_4, psci.image_xl_4, psci.image_sm_5, psci.image_xl_5, psci.image_sm_6, psci.image_xl_6, psce.stock_location as bin1, psce.stock_location_2 as bin2, vtsi.always_avail as always_available, vtsi.lead_time, v.vendors_id, v.vendors_company_name, vtsi.vendors_pn, vtsi.case_qty, vtsi.vendors_price FROM products_stock_control psc LEFT JOIN conditions c ON psc.conditions = c.conditions_id LEFT JOIN warranties w ON psc.warranty_id = w.warranty_id LEFT JOIN dealer_warranties dw ON psc.dealer_warranty_id = dw.dealer_warranty_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id LEFT JOIN products_stock_control_groups pscg ON pscv.products_stock_control_group_id = pscg.products_stock_control_group_id LEFT JOIN admin dnba ON psc.donotbuy_user_id = dnba.admin_id LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE psc.stock_name RLIKE :stock_name',
			'cardinality' => cardinality::SET,
			'stmt' => NULL,
		],

		'consolidated_inventory' => [
			'qry' => 'SELECT psc.stock_id, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END as on_hand, psc.ca_allocated_quantity as ca_allocated, op.allocated - IFNULL(po.po_allocated, 0) as local_allocated, IFNULL(op.allocated, 0) + IFNULL(psc.ca_allocated_quantity, 0) - IFNULL(po.po_allocated, 0) as allocated, IFNULL(po.po_allocated, 0) as po_allocated, IFNULL(ih.on_hold, 0) as on_hold, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END - (IFNULL(op.allocated, 0) + IFNULL(psc.ca_allocated_quantity, 0) - IFNULL(po.po_allocated, 0)) - IFNULL(ih.on_hold, 0) as available, CASE WHEN psc.serialized = 1 THEN IFNULL(s.quantity, 0) ELSE psc.stock_quantity END - IFNULL(ih.on_hold, 0) as salable, IFNULL(ic.in_conditioning, 0) as in_conditioning, psc.max_displayed_quantity, psc.on_order, psc.on_order - IFNULL(po.po_allocated, 0) as adjusted_on_order, NULL as adjusted_available_quantity FROM products_stock_control psc LEFT JOIN (SELECT ipn as stock_id, COUNT(id) as quantity FROM serials WHERE status IN (2, 3, 6) GROUP BY ipn) s ON psc.stock_id = s.stock_id LEFT JOIN (SELECT p.stock_id, SUM(op.products_quantity) as allocated FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE o.orders_status IN (1, 2, 5, 7, 8, 10, 11, 12) GROUP BY p.stock_id) op ON psc.stock_id = op.stock_id LEFT JOIN (SELECT p.stock_id, SUM(potoa.quantity) as po_allocated FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id JOIN purchase_order_to_order_allocations potoa ON op.orders_products_id = potoa.order_product_id AND potoa.purchase_order_product_id > 0 WHERE o.orders_status IN (1, 2, 5, 7, 8, 10, 11, 12) GROUP BY p.stock_id) po ON psc.stock_id = po.stock_id LEFT JOIN (SELECT ih.stock_id, SUM(ih.quantity) as on_hold FROM inventory_hold ih LEFT JOIN serials s ON ih.serial_id = s.id WHERE s.id IS NULL OR s.status = 6 GROUP BY ih.stock_id) ih ON psc.stock_id = ih.stock_id LEFT JOIN (SELECT stock_id, SUM(quantity) as in_conditioning FROM inventory_hold WHERE reason_id IN (4, 8, 11, 12) GROUP BY stock_id) ic ON psc.stock_id = ic.stock_id LEFT JOIN vendors_to_stock_item vtsi ON vtsi.id = psc.vendor_to_stock_item_id LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id',
			'cardinality' => cardinality::SET,
		],

		'on_hand_serialized_inventory' => [
			'qry' => 'SELECT COUNT(id) FROM serials WHERE ipn = ? AND status IN (2,3,6)',
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'allocated_inventory' => [
			//'qry' => 'SELECT SUM(soft_allocations) FROM (SELECT op.orders_products_id, op.products_quantity - IFNULL(SUM(potoa.quantity), 0) as soft_allocations FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id LEFT JOIN purchase_order_to_order_allocations potoa ON op.orders_products_id = potoa.order_product_id AND potoa.purchase_order_product_id > 0 WHERE o.orders_status IN (1, 2, 5, 7, 8, 10, 11, 12) AND p.stock_id = ? GROUP BY op.orders_products_id) sa',
			'qry' => 'SELECT SUM(op.products_quantity) as allocated FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE o.orders_status IN (1, 2, 5, 7, 8, 10, 11, 12) AND p.stock_id = ?',
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'po_allocated_inventory' => [
			'qry' => 'SELECT SUM(potoa.quantity) as po_allocated FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id JOIN purchase_order_to_order_allocations potoa ON op.orders_products_id = potoa.order_product_id AND potoa.purchase_order_product_id > 0 WHERE o.orders_status IN (1, 2, 5, 7, 8, 10, 11, 12) AND p.stock_id = :stock_id GROUP BY p.stock_id',
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'on_hold_inventory' => [
			'qry' => 'SELECT SUM(ih.quantity) as on_hold FROM inventory_hold ih LEFT JOIN serials s ON ih.serial_id = s.id WHERE (s.id IS NULL OR s.status = 6) AND ih.stock_id = :stock_id',
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'in_conditioning_inventory' => [
			'qry' => 'SELECT SUM(quantity) as in_conditioning FROM inventory_hold WHERE reason_id IN (4, 8, 11, 12) AND stock_id = :stock_id',
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		// this will eventually be replaced by a separate object type
		'specials_prices' => [
			'qry' => 'SELECT s.specials_id, s.products_id, s.specials_new_products_price as price, s.specials_qty as qty_limit, s.expires_date as expiration_date, s.status, s.specials_date_added as date_added, s.specials_last_modified as date_modified, s.date_status_change, s.active_criteria as active_criteria_type FROM specials s JOIN products p ON s.products_id = p.products_id WHERE p.stock_id = ? AND s.status = 1 AND p.archived = 0',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'customer_prices' => [
			'qry' => 'SELECT c.customers_id, c.customer_type, c.customer_price_level_id, itc.price FROM customers c LEFT JOIN ipn_to_customers itc ON c.customers_id = itc.customers_id WHERE itc.stock_id = ?',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'listings' => [
			'qry' => 'SELECT DISTINCT products_id FROM products WHERE stock_id = ? AND archived = 0 ORDER BY products_model ASC',
			'cardinality' => cardinality::COLUMN,
		],

		'family_units' => [
			'qry' => 'SELECT DISTINCT family_unit_id FROM ck_merchandising_family_unit_siblings WHERE stock_id = :stock_id',
			'cardinality' => cardinality::COLUMN,
		],

		'primary_container' => [
			'qry' => 'SELECT mpc.primary_container_id, mpc.container_type_id, mct.name as container_type, mct.table_name, mpc.container_id, mpc.canonical, mpc.redirect, mpc.date_created FROM ck_merchandising_primary_containers mpc JOIN ck_merchandising_container_types mct ON mpc.container_type_id = mct.container_type_id WHERE mpc.stock_id = :stock_id AND mpc.products_id IS NULL',
			'cardinality' => cardinality::ROW
		],

		'requiring_ipns' => [
			'qry' => 'SELECT DISTINCT rp.stock_id FROM products p JOIN product_addons pa ON p.products_id = pa.product_addon_id AND pa.included = 1 JOIN products rp ON pa.product_id = rp.products_id JOIN products_stock_control rpsc ON rp.stock_id = rpsc.stock_id WHERE p.stock_id = :stock_id AND rp.products_status = 1 AND rpsc.is_bundle != 1 AND rpsc.drop_ship != 1',
			'cardinality' => cardinality::COLUMN,
			'stmt' => NULL
		],

		/*'serials' => [
			'qry' => 'SELECT DISTINCT id, status FROM serials WHERE ipn = ?',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],*/

		'serialized_avg_cost' => [
			'qry' => 'SELECT AVG(IFNULL(sh.cost, 0)) FROM serials s JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id WHERE sh0.id IS NULL AND s.ipn = :stock_id AND s.status IN (2, 3, 6)',
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'sales_history' => [
			'qry' => 'SELECT o.orders_id, o.orders_status as orders_status_id, os.orders_status_name, o.orders_sub_status as orders_sub_status_id, oss.orders_sub_status_name, op.products_quantity, op.final_price, o.date_purchased, o.promised_ship_date, p.products_id, CASE WHEN op.products_id != p.products_id THEN op.products_id ELSE NULL END as extended_products_id, op.products_model, op.products_name, op.exclude_forecast FROM products p JOIN orders_products op ON p.products_id = op.products_id JOIN orders o ON op.orders_id = o.orders_id LEFT JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE p.stock_id = :stock_id ORDER BY o.date_purchased DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'sales_history_range' => [
			'qry' => 'SELECT o.orders_id, o.orders_status as orders_status_id, os.orders_status_name, o.orders_sub_status as orders_sub_status_id, oss.orders_sub_status_name, op.products_quantity, op.final_price, o.date_purchased, o.promised_ship_date, p.products_id, CASE WHEN op.products_id != p.products_id THEN op.products_id ELSE NULL END as extended_products_id, op.products_model, op.products_name, op.exclude_forecast FROM products p JOIN orders_products op ON p.products_id = op.products_id JOIN orders o ON op.orders_id = o.orders_id LEFT JOIN orders_status os ON o.orders_status = os.orders_status_id LEFT JOIN orders_sub_status oss ON o.orders_sub_status = oss.orders_sub_status_id WHERE p.stock_id = :stock_id AND DATE(o.date_purchased) >= :start_range AND DATE(o.date_purchased) <= :end_range ORDER BY o.date_purchased DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'recent_sales_history' => [
			'qry' => 'SELECT psc.stock_id, 0 as orders_status_id, SUM(op.products_quantity) as products_quantity, CASE WHEN TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 30 THEN DATE_SUB(DATE(NOW()), INTERVAL 10 DAY) WHEN TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 60 THEN DATE_SUB(DATE(NOW()), INTERVAL 40 DAY) ELSE DATE_SUB(DATE(NOW()), INTERVAL 90 DAY) END as date_purchased, op.exclude_forecast FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id JOIN orders_products op ON p.products_id = op.products_id JOIN orders o ON op.orders_id = o.orders_id WHERE psc.stock_id = :stock_id AND psc.drop_ship != 1 AND psc.is_bundle != 1 AND op.exclude_forecast = 0 AND o.orders_status NOT IN (6, 9) AND TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 180 GROUP BY psc.stock_id, CASE WHEN TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 30 THEN DATE_SUB(DATE(NOW()), INTERVAL 10 DAY) WHEN TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 60 THEN DATE_SUB(DATE(NOW()), INTERVAL 40 DAY) ELSE DATE_SUB(DATE(NOW()), INTERVAL 90 DAY) END, op.exclude_forecast ORDER BY o.date_purchased DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'rfq_history_range' => [
			'qry' => 'SELECT crrp.quantity, crrp.price, crr.created_date FROM ck_rfq_response_products crrp JOIN ck_rfq_responses crr ON crrp.rfq_response_id = crr.rfq_response_id WHERE crrp.stock_id = :stock_id AND DATE(crr.created_date) >= :start_range AND DATE(crr.created_date) <= :end_range ORDER BY crr.created_date DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'last_specials_date' => [
			'qry' => 'SELECT MAX(o.date_purchased) as last_special_date FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id WHERE op.price_reason = 2 AND o.orders_status NOT IN (6, 9) AND p.stock_id = :stock_id',
			'cardinality' => cardinality::SINGLE,
			'stmt' => NULL
		],

		'purchase_history' => [
			'qry' => 'SELECT po.id as purchase_order_id, po.purchase_order_number, po.status as status_id, pos.text as status, a.admin_email_address as purchaser, v.vendors_company_name as vendor, po.creation_date, po.expected_date, po.shipping_method as shipping_method_id, posh.text as shipping_method, pop.id as pop_id, pop.quantity, pop.cost, pop.description, SUM(porp.quantity_received) as quantity_received, potoa.allocated_quantity FROM purchase_order_products pop JOIN purchase_orders po ON pop.purchase_order_id = po.id LEFT JOIN purchase_order_status pos ON po.status = pos.id LEFT JOIN admin a ON po.administrator_admin_id = a.admin_id LEFT JOIN vendors v ON po.vendor = v.vendors_id LEFT JOIN purchase_order_shipping posh ON po.shipping_method = posh.id LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id LEFT JOIN (SELECT purchase_order_product_id, SUM(quantity) as allocated_quantity FROM purchase_order_to_order_allocations GROUP BY purchase_order_product_id) potoa ON pop.id = potoa.purchase_order_product_id WHERE pop.ipn_id = :stock_id GROUP BY po.id, po.purchase_order_number, po.status, pos.text, a.admin_email_address, v.vendors_company_name, po.creation_date, po.expected_date, po.shipping_method, posh.text, pop.id, pop.quantity, pop.cost, pop.description, potoa.allocated_quantity ORDER BY po.creation_date DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'purchase_history_range' => [
			'qry' => 'SELECT po.id as purchase_order_id, po.purchase_order_number, po.status as status_id, pos.text as status, a.admin_email_address as purchaser, v.vendors_company_name as vendor, po.creation_date, po.expected_date, po.shipping_method as shipping_method_id, posh.text as shipping_method, pop.id as pop_id, pop.quantity, pop.cost, pop.description, SUM(porp.quantity_received) as quantity_received, potoa.allocated_quantity FROM purchase_order_products pop JOIN purchase_orders po ON pop.purchase_order_id = po.id LEFT JOIN purchase_order_status pos ON po.status = pos.id LEFT JOIN admin a ON po.administrator_admin_id = a.admin_id LEFT JOIN vendors v ON po.vendor = v.vendors_id LEFT JOIN purchase_order_shipping posh ON po.shipping_method = posh.id LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id LEFT JOIN (SELECT purchase_order_product_id, SUM(quantity) as allocated_quantity FROM purchase_order_to_order_allocations GROUP BY purchase_order_product_id) potoa ON pop.id = potoa.purchase_order_product_id WHERE pop.ipn_id = :stock_id AND DATE(po.creation_date) >= :start_range AND DATE(po.creation_date) <= :end_range GROUP BY po.id, po.purchase_order_number, po.status, pos.text, a.admin_email_address, v.vendors_company_name, po.creation_date, po.expected_date, po.shipping_method, posh.text, pop.id, pop.quantity, pop.cost, pop.description, potoa.allocated_quantity ORDER BY po.creation_date DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'po_reviews' => [
			'qry' => 'SELECT porp.id, porp.po_review_id, porp.pop_id, porp.qty_received, porp.created_on, porp.modified_on, porp.weight, porp.status FROM purchase_order_review por JOIN purchase_order_review_product porp ON por.id = porp.po_review_id WHERE porp.psc_stock_id = :stock_id AND por.status != 2',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'change_history' => [
			'qry' => 'SELECT pscch.change_id, pscch.change_date, pscch.change_user as admin_name, pscch.type_id as change_code, psccht.name as change_type, pscch.reference, pscch.old_value, pscch.new_value, pscch.ipn_import_id FROM products_stock_control_change_history pscch JOIN products_stock_control_change_history_types psccht ON pscch.type_id = psccht.id WHERE pscch.stock_id = ? ORDER BY pscch.change_date DESC, pscch.change_id DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'vendors' => [
			'qry' => 'SELECT vtsi.id as vendor_relationship_id, v.vendors_id, v.vendors_company_name as company_name, v.vendors_email_address as email_address, vtsi.vendors_price as price, vtsi.vendors_pn as part_number, vtsi.case_qty, vtsi.always_avail as always_available, vtsi.lead_time, vtsi.notes, vtsi.preferred, vtsi.secondary FROM vendors_to_stock_item vtsi LEFT JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE vtsi.stock_id = :stock_id ORDER BY vtsi.preferred DESC, vtsi.secondary DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'upcs' => [
			'qry' => 'SELECT upc_assignment_id, target_resource, target_resource_id, upc, unit_of_measure, uom_description, provenance, purpose, created_date, active FROM ck_upc_assignments WHERE stock_id = :stock_id ORDER BY target_resource_id DESC, CASE WHEN unit_of_measure = 1 THEN 0 WHEN unit_of_measure = 0 THEN 9999 ELSE unit_of_measure END ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'package' => [
			'qry' => 'SELECT package_type_id, package_length as length, package_width as width, package_height as height, custom_dimension, package_name, logoed, promote FROM package_type WHERE ipn_id = :stock_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'ledger_history' => [
			'qry' => 'SELECT il.inventory_ledger_id, il.ledger_action_code_id, lac.action_code, il.ref_number, il.starting_qty, il.qty_change, il.ending_qty, il.orders_id, il.invoice_id, il.rma_id, il.purchase_order_id, il.transaction_timestamp, il.transaction_date FROM ck_inventory_ledgers il JOIN ck_ledger_action_codes lac ON il.ledger_action_code_id = lac.ledger_action_code_id WHERE il.stock_id = ? ORDER BY il.transaction_timestamp ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		// grab all of the ledger records that are active and will affect value for a chosen date
		'unserialized_value_on_date' => [
			// we're exploiting the fact that we reorganize this table to be sorted by date to know that the maximum ID per stock ID is the most recent
			'qry' => 'SELECT il.stock_id, psc.stock_name as ipn, pscc.name as category, pscv.name as vertical, MAX(il.inventory_ledger_id) as inventory_ledger_id, (SELECT ending_qty*avg_cost_on_date FROM ck_inventory_ledgers WHERE inventory_ledger_id = MAX(il.inventory_ledger_id)) as total FROM ck_inventory_ledgers il JOIN products_stock_control psc ON il.stock_id = psc.stock_id AND psc.serialized = 0 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id WHERE il.transaction_date <= :valuedate GROUP BY il.stock_id, psc.stock_name, pscc.name, pscv.name',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		// grab all of the serial history records that are active and will affect value for a chosen date
		'serialized_value_on_date_summary' => [
			'qry' => 'SELECT \'instock\' as type, pscc.name as category, pscv.name as vertical, SUM(sh.cost) as total FROM serials_history sh JOIN serials s ON sh.serial_id = s.id AND s.status IN (0, 2, 3, 6) JOIN products_stock_control psc ON s.ipn = psc.stock_id AND psc.serialized = 1 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id WHERE sh.entered_date <= :valuedate AND sh0.id IS NULL GROUP BY pscc.name, pscv.name UNION SELECT \'sold\' as type, pscc.name as category, pscv.name as vertical, SUM(sh.cost) as total FROM serials_history sh JOIN serials s ON sh.serial_id = s.id JOIN products_stock_control psc ON s.ipn = psc.stock_id AND psc.serialized = 1 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id JOIN orders o ON sh.order_id = o.orders_id AND o.orders_status = 3 JOIN acc_invoices ai ON sh.order_id = ai.inv_order_id LEFT JOIN acc_invoices ai0 ON ai.inv_order_id = ai0.inv_order_id AND ai.invoice_id > ai0.invoice_id WHERE sh.entered_date <= :valuedate AND ai.inv_date > :valuedate AND ai0.invoice_id IS NULL GROUP BY pscc.name, pscv.name UNION SELECT \'scrapped\' as type, pscc.name as category, pscv.name as vertical, SUM(sh.cost) as total FROM serials_history sh JOIN serials s ON sh.serial_id = s.id JOIN products_stock_control psc ON s.ipn = psc.stock_id AND psc.serialized = 1 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id JOIN purchase_orders po ON sh.po_number = po.purchase_order_number JOIN inventory_adjustment ia ON sh.serial_id = ia.serial_id AND po.id = ia.po_id AND sh.entered_date < ia.scrap_date AND ia.scrap_date > :valuedate LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id AND sh0.entered_date < ia.scrap_date WHERE sh.order_id IS NULL AND sh.entered_date <= :valuedate AND sh0.id IS NULL GROUP BY pscc.name, pscv.name UNION SELECT \'rma-defective\' as type, pscc.name as category, pscv.name as vertical, SUM(sh.cost) as total FROM serials_history sh JOIN serials s ON sh.serial_id = s.id JOIN products_stock_control psc ON s.ipn = psc.stock_id AND psc.serialized = 1 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id JOIN inventory_adjustment ia ON sh.serial_id = ia.serial_id AND IFNULL(ia.po_id, 0) = 0 AND sh.entered_date < ia.scrap_date AND ia.scrap_date > :valuedate LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id AND sh0.entered_date < ia.scrap_date WHERE sh.order_id IS NULL AND sh.po_number IS NULL AND sh.rma_id IS NOT NULL AND sh.entered_date <= :valuedate AND sh0.id IS NULL GROUP BY pscc.name, pscv.name',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'serialized_value_on_date_ipn' => [
			'qry' => 'SELECT \'instock\' as type, psc.stock_id, psc.stock_name as ipn, pscc.name as category, pscv.name as vertical, SUM(sh.cost) as total FROM serials_history sh JOIN serials s ON sh.serial_id = s.id AND s.status IN (0, 2, 3, 6) JOIN products_stock_control psc ON s.ipn = psc.stock_id AND psc.serialized = 1 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id WHERE sh.entered_date <= :valuedate AND sh0.id IS NULL GROUP BY pscc.name, pscv.name, psc.stock_id, psc.stock_name UNION SELECT \'sold\' as type, psc.stock_id, psc.stock_name as ipn, pscc.name as category, pscv.name as vertical, SUM(sh.cost) as total FROM serials_history sh JOIN serials s ON sh.serial_id = s.id JOIN products_stock_control psc ON s.ipn = psc.stock_id AND psc.serialized = 1 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id JOIN orders o ON sh.order_id = o.orders_id AND o.orders_status = 3 JOIN acc_invoices ai ON sh.order_id = ai.inv_order_id LEFT JOIN acc_invoices ai0 ON ai.inv_order_id = ai0.inv_order_id AND ai.invoice_id > ai0.invoice_id WHERE sh.entered_date <= :valuedate AND ai.inv_date > :valuedate AND ai0.invoice_id IS NULL GROUP BY pscc.name, pscv.name, psc.stock_id, psc.stock_name UNION SELECT \'scrapped\' as type, psc.stock_id, psc.stock_name as ipn, pscc.name as category, pscv.name as vertical, SUM(sh.cost) as total FROM serials_history sh JOIN serials s ON sh.serial_id = s.id JOIN products_stock_control psc ON s.ipn = psc.stock_id AND psc.serialized = 1 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id JOIN purchase_orders po ON sh.po_number = po.purchase_order_number JOIN inventory_adjustment ia ON sh.serial_id = ia.serial_id AND po.id = ia.po_id AND sh.entered_date < ia.scrap_date AND ia.scrap_date > :valuedate LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id AND sh0.entered_date < ia.scrap_date WHERE sh.order_id IS NULL AND sh.entered_date <= :valuedate AND sh0.id IS NULL GROUP BY pscc.name, pscv.name, psc.stock_id, psc.stock_name UNION SELECT \'rma-defective\' as type, psc.stock_id, psc.stock_name as ipn, pscc.name as category, pscv.name as vertical, SUM(sh.cost) as total FROM serials_history sh JOIN serials s ON sh.serial_id = s.id JOIN products_stock_control psc ON s.ipn = psc.stock_id AND psc.serialized = 1 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id JOIN inventory_adjustment ia ON sh.serial_id = ia.serial_id AND IFNULL(ia.po_id, 0) = 0 AND sh.entered_date < ia.scrap_date AND ia.scrap_date > :valuedate LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id AND sh0.entered_date < ia.scrap_date WHERE sh.order_id IS NULL AND sh.po_number IS NULL AND sh.rma_id IS NOT NULL AND sh.entered_date <= :valuedate AND sh0.id IS NULL GROUP BY pscc.name, pscv.name, psc.stock_id, psc.stock_name',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'COGS_for_period_summary' => [
			'qry' => 'SELECT pscc.name as category, pscv.name as vertical, SUM(aii.orders_product_cost_total) as cogs FROM acc_invoice_items aii JOIN acc_invoices ai ON aii.invoice_id = ai.invoice_id JOIN products_stock_control psc ON aii.ipn_id = psc.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id WHERE DATE(ai.inv_date) >= :start_date AND DATE(ai.inv_date) <= :end_date AND (ai.inv_order_id IS NOT NULL OR ai.rma_id IS NOT NULL) GROUP BY pscc.name, pscv.name ORDER BY pscv.name, pscc.name',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'COGS_for_period_ipn' => [
			'qry' => 'SELECT pscc.name as category, pscv.name as vertical, psc.stock_name as ipn, psc.stock_id, SUM(aii.orders_product_cost_total) as cogs FROM acc_invoice_items aii JOIN acc_invoices ai ON aii.invoice_id = ai.invoice_id JOIN products_stock_control psc ON aii.ipn_id = psc.stock_id LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id WHERE DATE(ai.inv_date) >= :start_date AND DATE(ai.inv_date) <= :end_date AND (ai.inv_order_id IS NOT NULL OR ai.rma_id IS NOT NULL) GROUP BY pscc.name, pscv.name, psc.stock_name, psc.stock_id ORDER BY pscv.name, pscc.name, psc.stock_name',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'populate_daily_ledger' => [
			'qry' => 'INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) VALUES (:stock_id, :ledger_action_code_id, :ref_number, :starting_qty, :qty_change, :ending_qty, :avg_cost_on_date, :transaction_cost, :purchase_order_id, :invoice_id, :rma_id, :inventory_adjustment_id, :transaction_date)',
			'cardinality' => 0, // this is an insert, not returning any results
			'stmt' => NULL
		],

		'populate_daily_ledger_init_runs' => [
			'qry' => 'SELECT ending_qty, avg_cost_on_date FROM ck_inventory_ledgers WHERE stock_id = :stock_id AND transaction_date < :transaction_date ORDER BY transaction_timestamp DESC LIMIT 1',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'populate_daily_ledger_running_values' => [
			'qry' => 'UPDATE ck_inventory_ledgers SET starting_qty = :starting_qty, qty_change = :qty_change, ending_qty = :ending_qty, avg_cost_on_date = :avg_cost_on_date WHERE inventory_ledger_id = :inventory_ledger_id',
			'cardinality' => 0,
			'stmt' => NULL
		]
	];

	// using the generic ck_type for type hinting allows for some limited use of duck typing
	public function __construct($stock_id, ck_ipn_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($stock_id);

		if (!$this->skeleton->built('stock_id')) $this->skeleton->load('stock_id', $stock_id);
		if ($this->skeleton->built('header')) $this->normalize_header();

		if (empty(self::$load_context)) self::set_load_context();

		self::register($stock_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('stock_id');
	}

	const CONTEXT_LIST = 'CONTEXT_LIST';
	const CONTEXT_SINGLE = 'CONTEXT_SINGLE';

	private static $load_context;

	public static function set_load_context($context=self::CONTEXT_SINGLE) {
		self::$load_context = $context;
	}

	private static $preset_stock_ids = [];

	public static function load_ipn_set(array $stock_ids) {
		self::$preset_stock_ids = array_map(function($val) { return (int) $val; }, $stock_ids);
	}

	public static function run_ipn_set() {
		self::prebuild_inventory();
	}

	public static function clear_prebuilt_inventory() {
		self::$prebuilt_inventory = [];
	}

	public static function preload_listing_inventory(array $products_ids) {
		self::set_load_context(self::CONTEXT_LIST);
		$lookup = new prepared_fields($products_ids);
		$stock_ids = prepared_query::fetch('SELECT DISTINCT stock_id FROM products WHERE products_id IN ('.$lookup->select_values().')', cardinality::COLUMN, $lookup->parameters());
		self::load_ipn_set($stock_ids);
		self::prebuild_inventory();
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('ipn_header', [':context' => 'stock_id', ':stock_id' => $this->id(), ':stock_name' => NULL]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$header['last_quantity_change'] = ck_datetime::datify($header['last_quantity_change']);
		$header['last_weight_change'] = ck_datetime::datify($header['last_weight_change']);
		if (!($header['date_added'] instanceof DateTime)) $header['date_added'] = new DateTime($header['date_added']);
		if (!empty($header['donotbuy_date']) && !($header['donotbuy_date'] instanceof DateTime)) $header['donotbuy_date'] = new DateTime($header['donotbuy_date']);
		if (!empty($header['creator']) && !($header['creator'] instanceof ck_admin)) $header['creator'] = new ck_admin($header['creator']);
		if (!empty($header['creation_reviewed_date']) && !($header['creation_reviewed_date'] instanceof DateTime)) $header['creation_reviewed_date'] = new DateTime($header['creation_reviewed_date']);
		if (!empty($header['creation_reviewer']) && !($header['creation_reviewer'] instanceof ck_admin)) $header['creation_reviewer'] = new ck_admin($header['creation_reviewer']);

		$header = self::normalize_pricing($header);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$header = self::fetch('ipn_header', [':context' => 'stock_id', ':stock_id' => $this->id(), ':stock_name' => NULL]);
		if ($header) {
			$this->skeleton->load('header', $header);
			$this->normalize_header();
		}
	}

	private function build_images() {
		// because load() ignores keys that don't fit the format of the target type, we can
		// load the whole header in since that's where the data is coming from
		if (!empty($this->get_header('image_reference'))) {
			$reference_ipn = new self($this->get_header('image_reference'));
			$header = $reference_ipn->get_header();
		}
		else $header = $this->get_header();
		$this->skeleton->load('images', array_map(function ($val) { if (is_string($val)) { return trim($val); } else { return ''; } }, $header));
	}

	private static $prebuilt_inventory = [];

	private static function prebuild_inventory() {
		if (!empty(self::$preset_stock_ids)) {
			$inventory = self::query_fetch(self::$queries['consolidated_inventory']['qry'].' WHERE psc.stock_id IN ('.implode(', ', self::$preset_stock_ids).')', cardinality::SET, []);
		}
		else {
			$inventory = self::fetch('consolidated_inventory', []);
		}

		foreach ($inventory as $inv) {
			self::$prebuilt_inventory[$inv['stock_id']] = $inv;
		}
	}

	private function build_inventory() {
		$inventory = $this->skeleton->format('inventory');

		if (self::$load_context == self::CONTEXT_LIST && empty(self::$prebuilt_inventory)) self::prebuild_inventory();

		if (self::$load_context == self::CONTEXT_LIST && isset(self::$prebuilt_inventory[$this->id()])) {
			$inventory = self::$prebuilt_inventory[$this->id()];
		}
		else {
			$header = $this->get_header();
			$inventory['ca_allocated'] = $header['ca_allocated_quantity'] ?? 0;
			$inventory['on_hand'] = !$this->is('serialized')?($header['stock_quantity'] ?? 0):self::fetch('on_hand_serialized_inventory', [$this->id()]);
			$inventory['po_allocated'] = self::fetch('po_allocated_inventory', [':stock_id' => $this->id()]);
			$inventory['local_allocated'] = self::fetch('allocated_inventory', [$this->id()]) - $inventory['po_allocated'];
			$inventory['allocated'] = ($inventory['local_allocated'] ?? 0) + ($inventory['ca_allocated'] ?? 0);
			$inventory['on_hold'] = self::fetch('on_hold_inventory', [':stock_id' => $this->id()]);
			$inventory['in_conditioning'] = self::fetch('in_conditioning_inventory', [':stock_id' => $this->id()]);

			$inventory['available'] = $inventory['on_hand'] - $inventory['allocated'] - $inventory['on_hold'];
			$inventory['salable'] = $inventory['on_hand'] - $inventory['on_hold'];

			$inventory['max_displayed_quantity'] = $header['max_displayed_quantity'] ?? NULL;

			$inventory['on_order'] = $header['on_order'] ?? 0;
			$inventory['adjusted_on_order'] = ($header['on_order'] ?? 0) - ($inventory['po_allocated'] ?? 0);

			// need to do some figuring here on adjusted available quantity
		}

		// there's a mess of integers, strings, nulls, etc, this normalizes them all to whole numbers
		$inventory = array_map(function($val) { return (int) $val; }, $inventory);

		$this->skeleton->load('inventory', $inventory);

		//var_dump($inventory);
	}

	public static function normalize_pricing($prices) {
		$price_sets = [
			['stock_price', 'dealer_price', 'wholesale_high_price', 'wholesale_low_price'],
			['retail_price', 'reseller_price', 'wholesale_high_price', 'wholesale_low_price'],
		];

		$matched_set = NULL;

		foreach ($price_sets as $idx => $price_set) {
			foreach ($price_set as $pidx => $price_field) {
				// the latter two fields are identical - we don't want to accidentally match here on the first go round.  If we get to the 2nd go round, then it doesn't matter
				if ($idx == 0 && $pidx >= 2) continue;

				if (array_key_exists($price_field, $prices)) $matched_set = $idx;
				else $matched_set = NULL;
			}

			if (!is_null($matched_set)) break;
		}

		if (!is_null($matched_set)) {
			$previous_field = NULL;

			foreach ($price_sets[$matched_set] as $price_field) {
				if (!is_null($previous_field)) {
					if (!is_numeric($prices[$price_field]) || $prices[$price_field] <= 0) $prices[$price_field] = $prices[$previous_field];
				}

				$previous_field = $price_field;
			}
		}

		return $prices;
	}

	private function build_price() {
		$prices = $this->skeleton->format('prices');

		$header = $this->get_header();

		$prices['list'] = $header['stock_price'];
		$prices['dealer'] = $header['dealer_price'];
		$prices['wholesale_high'] = $header['wholesale_high_price'];
		$prices['wholesale_low'] = $header['wholesale_low_price'];
		$prices['target_buy'] = $header['target_buy_price'];

		$this->skeleton->load('prices', $prices);

		$specials = self::fetch('specials_prices', [$this->id()]);

		if (!empty($specials)) {
			$specials_prices = [];
			foreach ($specials as $special) {
				$special['expiration_date'] = ck_datetime::datify($special['expiration_date']);
				$special['date_added'] = ck_datetime::datify($special['date_added']);
				$special['date_modified'] = ck_datetime::datify($special['date_modified']);
				$special['date_status_change'] = ck_datetime::datify($special['date_status_change']);

				$specials_prices[] = $special;
			}
			$this->skeleton->load('special_prices', $specials_prices);
		}

		/*
		// since we're checking that it's status=1, and anything that will affect the specials status will turn it off, we can assume if we get anything that it's valid for this item
		if (($this->special = self::fetch('specials_price', [$this->products_id]))) {
			$this->prices['special'] = $this->special['specials_new_products_price'];
		}*/

		$customers_data = self::fetch('customer_prices', [$this->id()]);

		if (!empty($customers_data)) {
			$customer_prices = [];
			foreach ($customers_data as $customer_price) {
				$customer_prices[] = ['customer' => ['customers_id' => $customer_price['customers_id'], 'customer_type' => $customer_price['customer_type'], 'customer_price_level_id' => $customer_price['customer_price_level_id']], 'price' => $customer_price['price']];
			}
			$this->skeleton->load('customer_prices', $customer_prices);
		}
	}

	private function build_listings() {
		$product_ids = self::fetch('listings', [$this->id()]);

		$listings = [];
		foreach ($product_ids as $products_id) {
			$listings[] = new ck_product_listing($products_id);
		}
		$this->skeleton->load('listings', $listings);
	}

	private function build_family_units() {
		$family_unit_ids = self::fetch('family_units', [':stock_id' => $this->id()]);

		$family_units = [];
		foreach ($family_unit_ids as $family_unit_id) {
			$family_units[] = new ck_family_unit($family_unit_id);
		}

		$this->skeleton->load('family_units', $family_units);
	}

	private function build_primary_container() {
		$primary_container = self::fetch('primary_container', [':stock_id' => $this->id()]);

		if (!empty($primary_container)) {
			$primary_container['canonical'] = CK\fn::check_flag($primary_container['canonical']);
			$primary_container['redirect'] = CK\fn::check_flag($primary_container['redirect']);
			$primary_container['date_created'] = self::DateTime($primary_container['date_created']);
		}
		else $primary_container = [];

		$this->skeleton->load('primary_container', $primary_container);
	}

	private function build_requiring_ipns() {
		$stock_ids = self::fetch('requiring_ipns', [':stock_id' => $this->id()]);

		$ipns = [];
		foreach ($stock_ids as $stock_id) {
			$ipns[] = new self($stock_id);
		}
		$this->skeleton->load('requiring_ipns', $ipns);
	}

	private function build_serials() {
		$ipn_serials = ck_serial::get_serials_by_stock_id($this->id());

		$serials = [];
		foreach ($ipn_serials as $serial) {
			if (empty($serials[$serial->get_header('status_code')])) $serials[$serial->get_header('status_code')] = [];
			$serials[$serial->get_header('status_code')][] = $serial;
		}
		$this->skeleton->load('serials', $serials);
	}

	private function build_sales_history() {
		$sales_history = self::fetch('sales_history', [':stock_id' => $this->id()]);

		foreach ($sales_history as &$record) {
			$record['date_purchased'] = self::DateTime($record['date_purchased']);
			if (!empty($record['promised_ship_date'])) $record['promised_ship_date'] = self::DateTime($record['promised_ship_date']);
		}

		$this->skeleton->load('sales_history', $sales_history);
	}

	private function build_sales_history_range(DateTime $start_range=NULL, DateTime $end_range=NULL) {
		if (empty($start_range)) $start_range = new DateTime('2000-01-01');
		if (empty($end_range)) $end_range = new DateTime();

		if ($start_range <= $end_range) $sales_history = self::fetch('sales_history_range', [':stock_id' => $this->id(), ':start_range' => $start_range->format('Y-m-d'), ':end_range' => $end_range->format('Y-m-d')]);
		else $sales_history = self::fetch('sales_history_range', [':stock_id' => $this->id(), ':start_range' => $end_range->format('Y-m-d'), ':end_range' => $start_range->format('Y-m-d')]);

		foreach ($sales_history as &$record) {
			$record['date_purchased'] = self::DateTime($record['date_purchased']);
			if (!empty($record['promised_ship_date'])) $record['promised_ship_date'] = self::DateTime($record['promised_ship_date']);
		}

		$this->skeleton->load('sales_history_range', $sales_history);
	}

	private function build_rfq_history_range(DateTime $start_range=NULL, DateTime $end_range=NULL) {
		if (empty($start_range)) $start_range = new DateTime('2000-01-01');
		if (empty($end_range)) $end_range = new DateTime();

		if ($start_range <= $end_range) $rfq_history = self::fetch('rfq_history_range', [':stock_id' => $this->id(), ':start_range' => $start_range->format('Y-m-d'), ':end_range' => $end_range->format('Y-m-d')]);
		else $rfq_history = self::fetch('rfq_history_range', [':stock_id' => $this->id(), ':start_range' => $end_range->format('Y-m-d'), ':end_range' => $start_range->format('Y-m-d')]);

		foreach ($rfq_history as $id => $record) $rfq_history[$id]['created_date'] = self::DateTime($record['created_date']);

		$this->skeleton->load('rfq_history_range', $rfq_history);
	}

	private function build_recent_sales_history() {
		$sales_history = self::fetch('recent_sales_history', [':stock_id' => $this->id()]);

		foreach ($sales_history as &$record) {
			$record['date_purchased'] = self::DateTime($record['date_purchased']);
		}

		$this->skeleton->load('recent_sales_history', $sales_history);
	}

	private function build_last_specials_date() {
		$last_specials_date = self::fetch('last_specials_date', [':stock_id' => $this->id()]);

		$last_specials_date = ck_datetime::datify($last_specials_date);
		$this->skeleton->load('last_specials_date', $last_specials_date);
	}

	private function build_purchase_history() {
		$purchase_history = self::fetch('purchase_history', [':stock_id' => $this->id()]);

		if ($purchase_history) {
			foreach ($purchase_history as &$record) {
				$record['creation_date'] = self::DateTime($record['creation_date']);
				if (!empty($record['expected_date'])) $record['expected_date'] = self::DateTime($record['expected_date']);
			}
		}

		$this->skeleton->load('purchase_history', $purchase_history);
	}

	private function build_purchase_history_range(DateTime $start_range=NULL, DateTime $end_range=NULL) {
		if (empty($start_range)) $start_range = new DateTime('2000-01-01');
		if (empty($end_range)) $end_range = new DateTime();

		if ($start_range <= $end_range) $purchase_history = self::fetch('purchase_history_range', [':stock_id' => $this->id(), ':start_range' => $start_range->format('Y-m-d'), ':end_range' => $end_range->format('Y-m-d')]);
		else $purchase_history = self::fetch('purchase_history_range', [':stock_id' => $this->id(), ':start_range' => $end_range->format('Y-m-d'), ':end_range' => $start_range->format('Y-m-d')]);

		foreach ($purchase_history as $id => $record) {
			$purchase_history[$id]['creation_date'] = self::DateTime($record['creation_date']);
			if (!empty($record['expected_date'])) $purchase_history[$id]['expected_date'] = self::DateTime($record['expected_date']);
		}

		$this->skeleton->load('purchase_history_range', $purchase_history);
	}

	private function build_receiving_history() {
		$purchase_history = $this->get_purchase_history();
		$receiving_history = [];
		if ($purchase_history) {
			foreach ($purchase_history as $record) {
				if ($record['quantity_received'] > 0) $receiving_history[] = $record;
			}
		}

		$this->skeleton->load('receiving_history', $receiving_history);
	}

	private function build_po_reviews() {
		$po_reviews = self::fetch('po_reviews', [':stock_id' => $this->id()]);

		foreach ($po_reviews as $idx => $record) {
			$po_reviews[$idx]['created_on'] = new DateTime($record['created_on']);
			$po_reviews[$idx]['modified_on'] = new DateTime($record['modified_on']);
		}

		$this->skeleton->load('po_reviews', $po_reviews);
	}

	private function build_change_history() {
		$change_history = self::fetch('change_history', [$this->id()]);

		foreach ($change_history as &$record) {
			$record['change_date'] = new DateTime($record['change_date']);
		}

		$this->skeleton->load('change_history', $change_history);
	}

	private function build_vendors() {
		$vendors = self::fetch('vendors', [':stock_id' => $this->id()]);

		foreach ($vendors as &$vendor) {
			$vendor['always_available'] = CK\fn::check_flag($vendor['always_available']);
			$vendor['preferred'] = CK\fn::check_flag($vendor['preferred']);
			$vendor['secondary'] = CK\fn::check_flag($vendor['secondary']);
		}

		$this->skeleton->load('vendors', $vendors);
	}

	private function build_upcs() {
		$upcs = self::fetch('upcs', [':stock_id' => $this->id()]);

		$relationship_map = [
			'' => 'IPN',
			'products' => 'Listing',
			'vendors_to_stock_item' => 'Vendor'
		];

		foreach ($upcs as &$upc) {
			$upc['relationship'] = $relationship_map[$upc['target_resource']];
			if ($upc['relationship'] == 'Listing') {
				$listing = new ck_product_listing($upc['target_resource_id']);
				$upc['related_object'] = $listing->get_header('products_model');
			}
			elseif ($upc['relationship'] == 'Vendor') {
				$upc['related_object'] = self::query_fetch('SELECT v.vendors_company_name FROM vendors_to_stock_item vtsi JOIN vendors v ON vtsi.vendors_id = v.vendors_id WHERE vtsi.id = :target_resource_id', cardinality::SINGLE, [':target_resource_id' => $upc['target_resource_id']]);
			}
			else $upc['related_object'] = '';
			$upc['created_date'] = self::DateTime($upc['created_date']);
			$upc['active'] = CK\fn::check_flag($upc['active']);
		}

		$this->skeleton->load('upcs', $upcs);
	}

	private static $today;
	private static $d30;
	private static $d60;
	private static $d180;

	private function build_forecasting_metadata() {
		$forecasting_metadata = [
			'usage_transactions' => [],

			'u30_date' => NULL,
			'u60_date' => NULL,
			'u180_date' => NULL,

			'usage_0-30' => 0,
			'usage_30-60' => 0,
			'usage_0-180' => 0,
			'usage_0-180_normalized' => 0,

			'runrate_0-30' => 0,
			'runrate_30-60' => 0,
			'runrate_0-180' => 0,
			'daily_runrate' => 0,

			'leadtime_days' => 0,
			'minimum_days' => 0,
			'target_days' => 0,
			'days_supply' => 0,

			'minimum_quantity' => 0,
			'raw_target_quantity' => 0, // this one is figured without estimated on-order receipt dates
			'target_quantity' => 0, // this one is figured with estimated on-order receipt dates

			// if you're on-time, you're late - on order qtys projected to be received on the lead time date are considered post-lead-time
			'pre-lead_on_order' => 0, // lead time is like the drop-dead minimum
			'post-lead_on_order' => 0,
			'pre-min_on_order' => 0, // minimum qty uses our min qty factor to add some safety stock
			'post-min_on_order' => 0,
			'pre-target_on_order' => 0, // target is what we want to purchase to
			'post-target_on_order' => 0,

			'earliest_eta' => NULL,

			'legacy-leadtime_days' => 0,
			'legacy-minimum_days' => 0,
			'legacy-minimum_quantity' => 0,
			'legacy-pre-lead_on_order' => 0, // lead time is like the drop-dead minimum
			'legacy-post-lead_on_order' => 0,
			'legacy-pre-min_on_order' => 0, // minimum qty uses our min qty factor to add some safety stock
			'legacy-post-min_on_order' => 0,

			/*'straddle-lead_on_order' => 0,
			'straddle-lead_on_order_date' => NULL,
			'straddle-target_on_order' => 0,
			'straddle-target_on_order_date' => NULL,*/
		];

		if (empty(self::$today)) {
			self::$today = new DateTime();

			self::$d30 = new DateTime();
			self::$d30->sub(new DateInterval('P30D'));

			self::$d60 = new DateTime();
			self::$d60->sub(new DateInterval('P60D'));

			self::$d180 = new DateTime();
			self::$d180->sub(new DateInterval('P180D'));
		}

		$forecasting_metadata['u30_date'] = self::$d30;
		$forecasting_metadata['u60_date'] = self::$d60;
		$forecasting_metadata['u180_date'] = self::$d180;

		// calculate usage, so we can figure our runrate
		if ($this->skeleton->built('sales_history')) $sales_history = $this->get_sales_history();
		else $sales_history = $this->get_sales_history_range(self::$d180, self::$today);

		if (!empty($sales_history)) {
			foreach ($sales_history as $transaction) {
				if (!empty($transaction['exclude_forecast'])) continue;
				if (in_array($transaction['orders_status_id'], [6, 9])) continue;
				if ($transaction['date_purchased'] < self::$d180) continue;

				$trn = ['date' => $transaction['date_purchased'], 'qty' => $transaction['products_quantity'], 'type' => 'Sale'];
				$forecasting_metadata['usage_transactions'][] = $trn;

				$forecasting_metadata['usage_0-180'] += $transaction['products_quantity'];

				if ($transaction['date_purchased'] < self::$d60) continue;

				if ($transaction['date_purchased'] < self::$d30) $forecasting_metadata['usage_30-60'] += $transaction['products_quantity'];
				else $forecasting_metadata['usage_0-30'] += $transaction['products_quantity'];
			}
		}

		if ($change_history = $this->get_change_history()) {
			$conversions = ['gains' => [], 'losses' => []];
			foreach ($change_history as $transaction) {
				if (!in_array($transaction['change_code'], [41, 42])) continue;
				if ($transaction['change_date']->format('Y-m-d') < self::$d180->format('Y-m-d')) continue;
				if ($transaction['change_code'] == 42) {
					if (!isset($conversions['gains'][$transaction['change_date']->format('c')])) {
						$conversions['gains'][$transaction['change_date']->format('c')] = 0;
					}
					$conversions['gains'][$transaction['change_date']->format('c')] += abs($transaction['new_value'] - $transaction['old_value']);
				}
				elseif ($transaction['change_code'] == 41) {
					if (!isset($conversions['losses'][$transaction['change_date']->format('c')])) {
						$conversions['losses'][$transaction['change_date']->format('c')] = 0;
					}
					$conversions['losses'][$transaction['change_date']->format('c')] += abs($transaction['new_value'] - $transaction['old_value']);
				}
			}

			foreach ($conversions['losses'] as $date => $losses) {
				if (isset($conversions['gains'][$date]) && $losses == $conversions['gains'][$date]) continue;

				$forecasting_metadata['usage_0-180'] += $losses;
				if ($date < self::$d60->format('c')) continue;

				if ($date < self::$d30->format('c')) $forecasting_metadata['usage_30-60'] += $losses;
				else $forecasting_metadata['usage_0-30'] += $losses;
			}
		}

		$forecasting_metadata['usage_0-180_normalized'] = $forecasting_metadata['usage_0-180'] / 6;

		// calculate our chosen runrate with our handy-dandy algorithm
		$forecasting_metadata['runrate_0-30'] = $forecasting_metadata['usage_0-30'] / 30;
		$forecasting_metadata['runrate_30-60'] = $forecasting_metadata['usage_30-60'] / 30;
		$forecasting_metadata['runrate_0-180'] = $forecasting_metadata['usage_0-180'] / 180;
		if (empty($forecasting_metadata['runrate_30-60']) && empty($forecasting_metadata['runrate_0-30'])) $forecasting_metadata['daily_runrate'] = $forecasting_metadata['runrate_0-180'];
		else $forecasting_metadata['daily_runrate'] = CK\math::median($forecasting_metadata['runrate_0-180'], $forecasting_metadata['runrate_30-60'], $forecasting_metadata['runrate_0-30']);

		// calculate our current days supply and lead time
		// used to be max(min inventory level, lead time), now min inventory level + lead time // JMS 2018-02-08
		$forecasting_metadata['leadtime_days'] = $this->get_header('min_inventory_level') + $this->get_header('lead_time');
		$forecasting_metadata['legacy-leadtime_days'] = max($this->get_header('min_inventory_level'), $this->get_header('lead_time'));
		$forecasting_metadata['minimum_days'] = ceil($forecasting_metadata['leadtime_days'] * forecast::min_qty_factor);
		$forecasting_metadata['legacy-minimum_days'] = ceil($forecasting_metadata['legacy-leadtime_days'] * forecast::min_qty_factor);
		$forecasting_metadata['target_days'] = !empty($this->get_header('target_inventory_level'))?$this->get_header('target_inventory_level'):$this->get_header('max_inventory_level');
		$forecasting_metadata['days_supply'] = empty($this->get_inventory('available'))?0:(empty($forecasting_metadata['daily_runrate'])?999999:ceil($this->get_inventory('available')/$forecasting_metadata['daily_runrate']));

		// calculate our minimum & target quantities
		$forecasting_metadata['minimum_quantity'] = max(0, ceil(($forecasting_metadata['daily_runrate'] * $forecasting_metadata['leadtime_days']) * forecast::min_qty_factor));
		$forecasting_metadata['legacy-minimum_quantity'] = max(0, ceil(($forecasting_metadata['daily_runrate'] * $forecasting_metadata['legacy-leadtime_days']) * forecast::min_qty_factor));
		$forecasting_metadata['raw_target_quantity'] = max(0, ceil(($forecasting_metadata['daily_runrate'] * $forecasting_metadata['target_days']) * forecast::target_qty_factor));

		// calculate on order quantities
		$dlead = new DateTime();
		$dlead->add(new DateInterval('P'.$forecasting_metadata['leadtime_days'].'D'));
		$dmin = new DateTime();
		$dmin->add(new DateInterval('P'.$forecasting_metadata['minimum_days'].'D'));

		$ldlead = new DateTime();
		$ldlead->add(new DateInterval('P'.$forecasting_metadata['legacy-leadtime_days'].'D'));
		$ldmin = new DateTime();
		$ldmin->add(new DateInterval('P'.$forecasting_metadata['legacy-minimum_days'].'D'));

		$dtarget = new DateTime();
		// there are a few products that "legitimately" do not have a target or max inventory level
		if (!empty($forecasting_metadata['target_days'])) $dtarget->add(new DateInterval('P'.$forecasting_metadata['target_days'].'D'));

		if ($purchase_history = $this->get_purchase_history()) {
			foreach ($purchase_history as $transaction) {
				if (!in_array($transaction['status_id'], [1, 2])) continue; // skip it if the PO isn't open or only partially received
				if ($transaction['quantity'] <= $transaction['quantity_received'] + $transaction['allocated_quantity']) continue; // skip it if we've already received or allocated the entire line
				//if ($transaction['expected_date'] > $dtarget) continue; // if our order date is further out than the date we're targeting to, then we don't count it

				if (empty($forecasting_metadata['earliest_eta'])) $forecasting_metadata['earliest_eta'] = $transaction['expected_date'];

				$outstanding_qty = $transaction['quantity'] - $transaction['quantity_received'] - $transaction['allocated_quantity'];

				if ($transaction['expected_date'] < $dlead) $forecasting_metadata['pre-lead_on_order'] += $outstanding_qty;
				else $forecasting_metadata['post-lead_on_order'] += $outstanding_qty;

				if ($transaction['expected_date'] < $ldlead) $forecasting_metadata['legacy-pre-lead_on_order'] += $outstanding_qty;
				else $forecasting_metadata['legacy-post-lead_on_order'] += $outstanding_qty;

				if ($transaction['expected_date'] < $dmin) $forecasting_metadata['pre-min_on_order'] += $outstanding_qty;
				else $forecasting_metadata['post-min_on_order'] += $outstanding_qty;

				if ($transaction['expected_date'] < $ldmin) $forecasting_metadata['legacy-pre-min_on_order'] += $outstanding_qty;
				else $forecasting_metadata['legacy-post-min_on_order'] += $outstanding_qty;

				if ($transaction['expected_date'] < $dtarget) $forecasting_metadata['pre-target_on_order'] += $outstanding_qty;
				else $forecasting_metadata['post-target_on_order'] += $outstanding_qty;
			}
		}

		usort($forecasting_metadata['usage_transactions'], function($a, $b) {
			if ($a['date'] > $b['date']) return -1;
			elseif ($a['date'] < $b['date']) return 1;
			else return 0;
		});

		$this->skeleton->load('forecasting_metadata', $forecasting_metadata);
	}

	private function build_package() {
		$package = self::fetch('package', [':stock_id' => $this->id()]);
		if (!empty($package)) $this->skeleton->load('package', $package);
	}

	private function build_ledger_history() {
		$this->skeleton->load('ledger_history', self::fetch('ledger_history', [$this->id()]));
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function get_image($key=NULL) {
		if (!$this->skeleton->built('images')) $this->build_images();
		if (empty($key)) return $this->skeleton->get('images');
		else return $this->skeleton->get('images', $key);
	}

	public function get_product_ready_images() {
		// all we do here is prepend 'products_' to the begining of the image
		$product_images = [];
		foreach ($this->get_image() as $idx => $image) {
			$product_images['products_'.$idx] = $image;
		}
		return $product_images;
	}

	public function get_inventory($key=NULL) {
		if (!$this->skeleton->built('inventory')) $this->build_inventory();
		if (empty($key)) return $this->skeleton->get('inventory');
		else return $this->skeleton->get('inventory', $key);
	}

	public static function get_inventory_direct($stock_id) {
		if (!empty(self::$prebuilt_inventory[$stock_id])) return self::$prebuilt_inventory[$stock_id];
		else {
			$ipn = new self($stock_id);
			return $ipn->get_inventory();
		}
	}

	public function get_price($key=NULL) {
		if (!$this->skeleton->built('prices')) $this->build_price();
		if (empty($key)) return $this->skeleton->get('prices');
		elseif ($key == 'specials') return $this->skeleton->get('special_prices');
		elseif ($key == 'customers') return $this->skeleton->get('customer_prices');
		else return $this->skeleton->get('prices', $key);
	}

	public function get_transfer_price() {
		if ($this->get_header('market_state') == self::MARKET_STATE_BROKER) return !empty($this->get_price('wholesale_low'))?$this->get_price('wholesale_low'):0;
		else return NULL;
	}

	public function has_special_prices() {
		if (!$this->skeleton->built('prices')) $this->build_price();
		return $this->skeleton->has('special_prices');
	}

	public function get_special_prices() {
		if (!$this->has_special_prices()) return NULL;
		return $this->skeleton->get('special_prices');
	}

	public function has_customer_prices() {
		if (!$this->skeleton->built('prices')) $this->build_price();
		return $this->skeleton->has('customer_prices');
	}

	public function get_customer_prices() {
		if (!$this->has_customer_prices()) return NULL;
		return $this->skeleton->get('customer_prices');
	}

	public function has_listings() {
		if (!$this->skeleton->built('listings')) $this->build_listings();
		return $this->skeleton->has('listings');
	}

	public function get_listings() {
		if (!$this->has_listings()) return [];
		return $this->skeleton->get('listings');
	}

	public function has_family_units() {
		if (!$this->skeleton->built('family_units')) $this->build_family_units();
		return $this->skeleton->has('family_units');
	}

	public function get_family_units() {
		if (!$this->has_family_units()) return [];
		return $this->skeleton->get('family_units');
	}

	public function get_default_listing() {
		foreach ($this->get_listings() as $listing) {
			if ($listing->is_viewable()) return $listing;
		}
	}

	public function has_active_listings() {
		return array_reduce($this->get_listings(), function($status, $listing) { return $status || $listing->is_viewable(); }, FALSE);
	}

	public function has_primary_container() {
		if (!$this->skeleton->built('primary_container')) $this->build_primary_container();
		return $this->skeleton->has('primary_container');
	}

	public function get_primary_container() {
		if (!$this->has_primary_container()) return NULL;
		return $this->skeleton->get('primary_container');
	}

	// any ipns that have listings that have a listing of this ipn included on them
	public function has_requiring_ipns() {
		if (!$this->skeleton->built('requiring_ipns')) $this->build_requiring_ipns();
		return $this->skeleton->has('requiring_ipns');
	}

	public function get_requiring_ipns() {
		if (!$this->has_requiring_ipns()) return [];
		return $this->skeleton->get('requiring_ipns');
	}

	public function has_serials() {
		if (!$this->skeleton->built('serials')) $this->build_serials();
		return $this->skeleton->has('serials');
	}

	public function get_serials($key=NULL) {
		if (!$this->has_serials()) return NULL;
		if (empty($key)) return $this->skeleton->get('serials');
		else {
			if (empty($this->skeleton->get('serials', $key))) return [];
			return $this->skeleton->get('serials', $key);
		}
	}

	// we have a default sort for unallocated serials, highest cost then oldest receipt of latest history record
	// perform that sort, by default on "in stock" status serials
	public function get_sorted_serials($key=2) {
		$serials = $this->get_serials($key);
		if (!empty($serials)) usort($serials, ['ck_serial', 'sort_serials']);
		return $serials;
	}

	public function get_display_sorted_serials() {
		$serials = $this->get_serials();
		$srls = [];
		foreach ($serials as $type => $list) {
			foreach ($list as $srl) {
				$srls[] = $srl;
			}
		}
		usort($srls, ['ck_serial', 'sort_display_serials']);
		return $srls;
	}

	public function has_sales_history() {
		if (!$this->skeleton->built('sales_history')) $this->build_sales_history();
		return $this->skeleton->has('sales_history');
	}

	public function get_sales_history() {
		if (!$this->has_sales_history()) return NULL;
		return $this->skeleton->get('sales_history');
	}

	public function has_sales_history_range(DateTime $start_range=NULL, DateTime $end_range=NULL) {
		if (!$this->skeleton->built('sales_history_range') || !empty($start_range) || !empty($end_range)) $this->build_sales_history_range($start_range, $end_range);
		return $this->skeleton->has('sales_history_range');
	}

	public function get_sales_history_range(DateTime $start_range=NULL, DateTime $end_range=NULL) {
		if (!$this->has_sales_history_range($start_range, $end_range)) return [];
		return $this->skeleton->get('sales_history_range');
	}

	public function has_recent_sales_history() {
		if (!$this->skeleton->built('recent_sales_history')) $this->build_recent_sales_history();
		return $this->skeleton->has('recent_sales_history');
	}

	public function get_recent_sales_history() {
		if (!$this->has_recent_sales_history()) return NULL;
		return $this->skeleton->get('recent_sales_history');
	}

	public function has_last_specials_date() {
		if (!$this->skeleton->built('last_specials_date')) $this->build_last_specials_date();
		return $this->skeleton->has('last_specials_date');
	}

	public function get_last_specials_date() {
		if (!$this->has_last_specials_date()) return NULL;
		else return $this->skeleton->get('last_specials_date');
	}

	public function has_purchase_history() {
		if (!$this->skeleton->built('purchase_history')) $this->build_purchase_history();
		return $this->skeleton->has('purchase_history');
	}

	public function get_purchase_history() {
		if (!$this->has_purchase_history()) return NULL;
		return $this->skeleton->get('purchase_history');
	}

	public function has_purchase_history_range(DateTime $start_range=NULL, DateTime $end_range=NULL) {
		if (!$this->skeleton->built('purchase_history_range') || !empty($start_range) || !empty($end_range)) $this->build_purchase_history_range($start_range, $end_range);
		return $this->skeleton->has('purchase_history_range');
	}

	public function get_purchase_history_range(DateTime $start_range=NULL, DateTime $end_range=NULL) {
		if (!$this->has_purchase_history_range($start_range, $end_range)) return [];
		return $this->skeleton->get('purchase_history_range');
	}

	public function has_receiving_history() {
		if (!$this->skeleton->built('receiving_history')) $this->build_receiving_history();
		return $this->skeleton->has('receiving_history');
	}

	public function get_receiving_history() {
		if (!$this->has_receiving_history()) return NULL;
		return $this->skeleton->get('receiving_history');
	}

	public function has_po_reviews() {
		if (!$this->skeleton->built('po_reviews')) $this->build_po_reviews();
		return $this->skeleton->has('po_reviews');
	}

	public function get_po_reviews() {
		if (!$this->has_po_reviews()) return NULL;
		return $this->skeleton->get('po_reviews');
	}

	public function get_change_history($key=NULL) {
		if (!$this->skeleton->built('change_history')) $this->build_change_history();
		if (empty($key)) return $this->skeleton->get('change_history');
		else {
			if (!is_array($key)) $key = [$key];
			$records = [];
			foreach ($this->skeleton->get('change_history') as $ch) {
				if (!empty(array_intersect([$ch['change_code'], $ch['change_type']], $key))) $records[] = $ch;
			}
			return $records;
		}
	}

	public function has_package() {
		if (!$this->skeleton->built('package')) $this->build_package();
		return $this->skeleton->has('package');
	}

	public function get_package() {
		if (!$this->has_package()) return NULL;
		else return $this->skeleton->get('package');
	}

	public function has_vendors() {
		if (!$this->skeleton->built('vendors')) $this->build_vendors();
		return $this->skeleton->has('vendors');
	}

	public function get_vendors($key=NULL) {
		if (!$this->has_vendors()) return NULL;
		elseif (empty($key)) return $this->skeleton->get('vendors');
		elseif ($key == 'preferred') {
			foreach ($this->skeleton->get('vendors') as $vendor) {
				if ($vendor['preferred']) return $vendor;
			}
			return NULL;
		}
		elseif ($key == 'secondary') {
			$vendors = [];
			foreach ($this->skeleton->get('vendors') as $vendor) {
				if ($vendor['secondary']) $vendors[] = $vendor;
			}
			return !empty($vendors)?$vendors:NULL;
		}
		else {
			foreach ($this->skeleton->get('vendors') as $vendor) {
				if ($vendor['vendor_relationship_id'] == $key) return $vendor;
			}
			return NULL;
		}
	}

	public function has_upcs($key=NULL) {
		if (!$this->skeleton->built('upcs')) $this->build_upcs();

		if (empty($key)) return $this->skeleton->has('upcs');
		elseif (empty($this->skeleton->has('upcs'))) return FALSE;
		elseif (is_numeric($key)) {
			// first try to match the UPC
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc'] == $key) return TRUE;
			}
			// if this isn't an actual UPC assigned to this IPN, try and match the ID of the assignment record
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc_assignment_id'] == $key) return TRUE;
			}
			return FALSE;
		}
		else {
			// first try to match the UPC
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc'] == $key) return TRUE;
			}
			// if this isn't an actual UPC assigned to this IPN, try and match one of the types of UPC groups
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['target_resource'] == $key) return TRUE;
				elseif ($key == 'ipn' && empty($upc['target_resource'])) return TRUE;
				elseif ($upc['target_resource'] == 'products' && in_array($key, ['product', 'products', 'product_listings', 'products-listings', 'product listings'])) return TRUE;
				elseif ($upc['target_resource'] == 'vendors_to_stock_item' && in_array($key, ['vendor', 'vendors', 'vendors_to_stock_item', 'vendors-to-stock-item', 'vendors to stock item'])) return TRUE;
				elseif ($upc['purpose'] == $key) return TRUE;
			}
			return FALSE;
		}
	}

	public function get_upcs($key=NULL) {
		if (!$this->has_upcs($key)) return NULL;
		elseif (empty($key)) return $this->skeleton->get('upcs');
		elseif (is_numeric($key)) {
			// first try to match the UPC
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc'] == $key) return $upc;
			}
			// if this isn't an actual UPC assigned to this IPN, try and match the ID of the assignment record
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc_assignment_id'] == $key) return $upc;
			}
			return NULL;
		}
		else {
			// first try to match the UPC
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['upc'] == $key) return $upc;
			}
			// if this isn't an actual UPC assigned to this IPN, try and match one of the types of UPC groups
			$upcs = [];
			foreach ($this->skeleton->get('upcs') as $upc) {
				if ($upc['target_resource'] == $key) $upcs[] = $upc;
				elseif ($key == 'ipn' && empty($upc['target_resource'])) $upcs[] = $upc;
				elseif ($upc['target_resource'] == 'products' && in_array($key, ['product', 'products', 'product_listings', 'products-listings', 'product listings'])) $upcs[] = $upc;
				elseif ($upc['target_resource'] == 'vendors_to_stock_item' && in_array($key, ['vendor', 'vendors', 'vendors_to_stock_item', 'vendors-to-stock-item', 'vendors to stock item'])) $upcs[] = $upc;
				elseif ($upc['purpose'] == $key) $upcs[] = $upc;
			}
			return !empty($upcs)?$upcs:NULL;
		}
	}

	public function get_forecasting_metadata($key=NULL) {
		if (!$this->skeleton->built('forecasting_metadata')) $this->build_forecasting_metadata();

		if (empty($key)) return $this->skeleton->get('forecasting_metadata');
		else return $this->skeleton->get('forecasting_metadata', $key);
	}

	public function get_ledger_history() {
		if (!$this->skeleton->built('ledger_history')) $this->build_ledger_history();
		return $this->skeleton->get('ledger_history');
	}

	public function get_condition($key='friendly') {
		if ($condition = self::get_condition_name($this->get_header('conditions'), $key)) return $condition;
		else return $this->get_header('conditions_name');
	}

	public function has_ever_been_recieved() {
		if ($this->get_inventory('on_hand') > 0) return TRUE;
		elseif ($purchase_history = $this->get_purchase_history()) {
			foreach ($purchase_history as $record) {
				if ($record['quantity_received'] > 0) return TRUE;
			}
		}
		elseif ($this->has_po_reviews()) return TRUE;

		return FALSE;
	}

	public function get_last_price_change($type='list') {
		$change_history = $this->get_change_history();

		if ($type == 'dealer') $change_types = ['Dealer Price Change', 'Dealer Price Confirmation'];
		elseif ($type == 'wholesale_high') $change_types = ['Wholesale High Price Change', 'Wholesale High Price Confirmation'];
		elseif ($type == 'wholesale_low') $change_types = ['Wholesale Low Price Change', 'Wholesale Low Price Confirmation'];
		else $change_types = ['Stock Price Change', 'Stock Price Confirmation'];

		foreach ($change_history as $record) {
			if (in_array($record['change_type'], $change_types)) return $record['change_date'];
		}

		return NULL;
	}

	public function get_avg_cost() {
		$average_cost = NULL;
		if ($this->is('serialized')) {
			$average_cost = self::fetch('serialized_avg_cost', [':stock_id' => $this->id()]);
		}

		if (empty($average_cost)) $average_cost = $this->get_header('average_cost');

		return $average_cost;
	}

	public function get_expected_cost() {
		if ($this->has('vendors_price') && is_numeric($this->get_header('vendors_price'))) return $this->get_header('vendors_price');
		else return $this->get_avg_cost();
	}

	public function is_supplies() {
		if ($this->get_header('products_stock_control_category_id') == 90) return TRUE;
		else return FALSE;
	}

	public function can_be_package() {
		if ($this->is_supplies()) return TRUE;
		else return FALSE;
	}

	public function get_total_weight() {
		$weight = $this->get_header('stock_weight');

		foreach ($this->get_listings() as $listing) {
			$weight = max($weight, $listing->get_total_weight());
		}

		return $weight;
	}

	public static function get_condition_name($condition, $key='friendly') {
		// if this is already a name, not an ID, then we don't know how to map it, just return it
		// this is specifically used in the family container
		if (!is_numeric($condition)) return $condition;

		// perform all condition mapping
		if ($key == 'friendly') {
			switch ($condition) {
				case '1': return 'New'; break;
				case '6': return 'Open Box'; break;
				case '7': return 'Factory Sealed'; break;
				case '4': return 'Not Perfect, But Functional'; break;
				case '2':
				case '8':
				default:
					return 'Refurbished';
					break;
			}
		}
		elseif ($key == 'google') {
			switch ($condition) {
				case '1':
				case '6':
				case '7':
					return 'new';
					break;
				case '4':
					return 'used';
					break;
				case '2':
				case '8':
				default:
					return 'refurbished';
					break;
			}
		}
		elseif ($key == 'meta') {
			return in_array($condition, [2, 3, 4, 8])?'RefurbishedCondition':'NewCondition';
		}
		else {
			return NULL;
		}
	}

	public static function get_value_on_date(DateTime $date, $summary=TRUE) {
		$unserialized = self::fetch('unserialized_value_on_date', [':valuedate' => $date->format('Y-m-d')]);
		if ($summary) $serialized = self::fetch('serialized_value_on_date_summary', [':valuedate' => $date->format('Y-m-d')]);
		else $serialized = self::fetch('serialized_value_on_date_ipn', [':valuedate' => $date->format('Y-m-d')]);

		$values = [];

		if ($summary) {
			foreach ($unserialized as $row) {
				if (empty($values[$row['vertical']])) $values[$row['vertical']] = [];
				if (empty($values[$row['vertical']][$row['category']])) $values[$row['vertical']][$row['category']] = 0;

				$values[$row['vertical']][$row['category']] += $row['total'];
			}
			unset($unserialized);

			foreach ($serialized as $row) {
				if (empty($values[$row['vertical']])) $values[$row['vertical']] = [];
				if (empty($values[$row['vertical']][$row['category']])) $values[$row['vertical']][$row['category']] = 0;

				$values[$row['vertical']][$row['category']] += $row['total'];
			}
			unset($serialized);
		}
		else {
			foreach ($unserialized as $row) {
				if (empty($values[$row['stock_id']])) $values[$row['stock_id']] = 0;

				$values[$row['stock_id']] += $row['total'];
			}
			unset($unserialized);

			foreach ($serialized as $row) {
				if (empty($values[$row['stock_id']])) $values[$row['stock_id']] = 0;

				$values[$row['stock_id']] += $row['total'];
			}
			unset($serialized);
		}

		return $values;
	}

	public static function get_cogs_for_period(DateTime $start_date, DateTime $end_date, $summary=TRUE) {
		if ($summary) return self::fetch('COGS_for_period_summary', [':start_date' => $start_date->format('Y-m-d'), ':end_date' => $end_date->format('Y-m-d')]);
		else return self::fetch('COGS_for_period_ipn', [':start_date' => $start_date->format('Y-m-d'), ':end_date' => $end_date->format('Y-m-d')]);
	}

	public static function get_id_by_stock_name($stock_name) {
		return self::query_fetch('SELECT stock_id FROM products_stock_control WHERE stock_name = :stock_name', cardinality::SINGLE, [':stock_name' => $stock_name]);
	}

	public static function get_ipn_by_ipn($stock_name) {
		if ($stock_name && ($header = self::fetch('ipn_header', [':context' => 'stock_name', ':stock_name' => $stock_name, ':stock_id' => NULL]))) {
			$skeleton = self::get_record($header['stock_id']); // if we've already instantiated it, well, oh well
			if (!$skeleton->built('header')) $skeleton->load('header', $header);

			return new self($header['stock_id'], $skeleton);
		}
		else return NULL;
	}

	public static function get_ipns_by_match($stock_name) {
		$stock_name = preg_replace('/([^a-zA-Z0-9.+])/', '$1?', $stock_name);
		if ($stock_name && ($headers = self::fetch('ipn_header_list', [':stock_name' => $stock_name.'.*']))) {
			$ipns = [];
			foreach ($headers as $header) {
				$skeleton = self::get_record($header['stock_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$ipns[] = new self($header['stock_id'], $skeleton);
			}

			return $ipns;
		}
		else return [];
	}

	public static function get_ipns_for_purchase_management($all=FALSE, $include_dropship=FALSE) {
		//debug_tools::mark('Begin');
		//debug_tools::mark('Headers');
		if ($headers = self::fetch('ipns_for_purchase_management', [':include_dropship' => CK\fn::check_flag($include_dropship)?0:1])) {
			$ipns = [];

			//debug_tools::mark('Inventory');
			$inventories_raw = self::fetch('inventory_for_purchase_management', []);
			$inventories = [];
			//debug_tools::mark('Inventory Loop');
			foreach ($inventories_raw as $inventory) {
				$inventories[$inventory['stock_id']] = $inventory;
			}
			$inventories_raw = NULL;
			unset($inventories_raw);

			//debug_tools::mark('Sales History');
			$sales_histories_raw = self::fetch('sales_history_for_purchase_management', []);
			$sales_histories = [];
			//debug_tools::mark('Sales History Loop');
			foreach ($sales_histories_raw as $history) {
				if (empty($sales_histories[$history['stock_id']])) $sales_histories[$history['stock_id']] = [];
				$history['date_purchased'] = self::DateTime($history['date_purchased']);
				$sales_histories[$history['stock_id']][] = $history;
			}
			$sales_histories_raw = NULL;
			unset($sales_histories_raw);

			//debug_tools::mark('Change History');
			$change_histories_raw = self::fetch('change_history_for_purchase_management', []);
			$change_histories = [];
			//debug_tools::mark('Change History Loop');
			foreach ($change_histories_raw as $history) {
				if (empty($change_histories[$history['stock_id']])) $change_histories[$history['stock_id']] = [];
				$history['change_date'] = self::DateTime($history['change_date']);
				$change_histories[$history['stock_id']][] = $history;
			}
			$change_histories_raw = NULL;
			unset($change_histories_raw);

			//debug_tools::mark('Purchase History');
			$purchase_histories_raw = self::fetch('purchase_history_for_purchase_management', []);
			//var_dump($purchase_histories_raw);
			$purchase_histories = [];
			//debug_tools::mark('Purchase History Loop');
			foreach ($purchase_histories_raw as $history) {
				if (empty($purchase_histories[$history['stock_id']])) $purchase_histories[$history['stock_id']] = [];
				$history['expected_date'] = self::DateTime($history['expected_date']);
				$purchase_histories[$history['stock_id']][] = $history;
			}
			$purchase_histories_raw = NULL;
			unset($purchase_histories_raw);

			//debug_tools::mark('Requiring IPNs');
			$requiring_ipns_raw = self::fetch('requiring_ipns_for_purchase_management', []);
			$requiring_ipns = [];
			//debug_tools::mark('Requiring IPNs Loop');
			foreach ($requiring_ipns_raw as $requires) {
				if (empty($requiring_ipns[$requires['stock_id']])) $requiring_ipns[$requires['stock_id']] = [];
				$requiring_ipns[$requires['stock_id']][] = new self($requires['requiring_stock_id']);
			}
			$requiring_ipns_raw = NULL;
			unset($requiring_ipns_raw);

			//debug_tools::mark('Last Specials Dates');
			$last_specials_dates_raw = self::fetch('last_specials_dates_for_purchase_management', []);
			$last_specials_dates = [];
			//debug_tools::mark('Last Specials Dates Loop');
			foreach ($last_specials_dates_raw as $special) {
				$last_specials_dates[$special['stock_id']] = self::DateTime($special['last_specials_date']);
			}
			$last_specials_dates_raw = NULL;
			unset($last_specials_dates_raw);

			//debug_tools::mark('Build Loop');
			foreach ($headers as $hidx => $header) {
				// we don't always instantiate the IPN, we only return the ones that have activity we're interested in
				if (empty($all) && empty($sales_histories[$header['stock_id']]) && empty($change_histories[$header['stock_id']])) continue;

				$skeleton = self::get_record($header['stock_id']);
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				if (!$skeleton->built('inventory')) {
					$skeleton->load('inventory', !empty($inventories[$header['stock_id']])?$inventories[$header['stock_id']]:[]);
					$inventories[$header['stock_id']] = NULL;
					unset($inventories[$header['stock_id']]);
				}

				if (!$skeleton->built('sales_history')) {
					$skeleton->load('sales_history', !empty($sales_histories[$header['stock_id']])?$sales_histories[$header['stock_id']]:[]);
					$sales_histories[$header['stock_id']] = NULL;
					unset($sales_histories[$header['stock_id']]);
				}

				if (!$skeleton->built('change_history')) {
					$skeleton->load('change_history', !empty($change_histories[$header['stock_id']])?$change_histories[$header['stock_id']]:[]);
					$change_histories[$header['stock_id']] = NULL;
					unset($change_histories[$header['stock_id']]);
				}

				if (!$skeleton->built('purchase_history')) {
					$skeleton->load('purchase_history', !empty($purchase_histories[$header['stock_id']])?$purchase_histories[$header['stock_id']]:[]);
					$purchase_histories[$header['stock_id']] = NULL;
					unset($purchase_histories[$header['stock_id']]);
				}

				if (!$skeleton->built('requiring_ipns')) {
					$skeleton->load('requiring_ipns', !empty($requiring_ipns[$header['stock_id']])?$requiring_ipns[$header['stock_id']]:[]);
					$requiring_ipns[$header['stock_id']] = NULL;
					unset($requiring_ipns[$header['stock_id']]);
				}

				if (!$skeleton->built('last_specials_date')) {
					$skeleton->load('last_specials_date', !empty($last_specials_dates[$header['stock_id']])?$last_specials_dates[$header['stock_id']]:NULL);
					$last_specials_dates[$header['stock_id']] = NULL;
					unset($last_specials_dates[$header['stock_id']]);
				}

				// we always instantiate the IPN because we may need it as a parent, but we only return the ones that have activity we're interested in
				$ipn = new self($header['stock_id'], $skeleton);
				/*if ($all || $skeleton->has('sales_history') || $skeleton->has('change_history'))*/
				$ipns[] = $ipn;//new self($header['stock_id'], $skeleton);

				//if (($hidx+1)%1000 == 0) debug_tools::mark('1000 IPN Time');
			}
			$headers = NULL;
			unset($headers);

			//debug_tools::mark('End');

			return $ipns;
		}
		else return [];
	}

	public static function get_ipns_for_inventory_report() {
		if ($ipns = self::fetch('ipns_for_inventory_report', [])) {
			//$ipns = [];

			/*$ipns = self::fetch('inventory_for_inventory_report', []);
			$inventories = [];
			foreach ($inventories_raw as $inventory) {
				$inventories[$inventory['stock_id']] = $inventory;
			}
			$inventories_raw = NULL;
			unset($inventories_raw);

			foreach ($headers as $hidx => $header) {
				$skeleton = self::get_record($header['stock_id']);
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				if (!$skeleton->built('inventory')) {
					$skeleton->load('inventory', !empty($inventories[$header['stock_id']])?$inventories[$header['stock_id']]:[]);
					$inventories[$header['stock_id']] = NULL;
					unset($inventories[$header['stock_id']]);
				}

				// we always instantiate the IPN because we may need it as a parent, but we only return the ones that have activity we're interested in
				$ipns[] = new self($header['stock_id'], $skeleton);
			}
			$headers = NULL;
			unset($headers);*/

			return $ipns;
		}
		else return [];
	}

	public static function get_legacy_allocated_ipns() {
		$allocations = self::query_fetch('SELECT stock_id, SUM(soft_allocations) as qty FROM (SELECT p.stock_id, SUM(op.products_quantity) - IFNULL(SUM(potoa.quantity), 0) as soft_allocations FROM orders o JOIN orders_products op ON o.orders_id = op.orders_id JOIN products p ON op.products_id = p.products_id LEFT JOIN (SELECT order_product_id, SUM(quantity) as quantity FROM purchase_order_to_order_allocations WHERE purchase_order_product_id > 0 GROUP BY order_product_id) potoa ON op.orders_products_id = potoa.order_product_id WHERE o.orders_status IN (1, 2, 5, 7, 8, 10, 11, 12) GROUP BY p.stock_id) sa GROUP BY stock_id', cardinality::SET, []);
		$allocated_array = [];
		foreach ($allocations as $allocation) {
			$allocated_array[$allocation['stock_id']] = $allocation['qty'];
		}
		return $allocated_array;
	}

	public static function get_legacy_hold_ipns() {
		$holds = self::query_fetch('SELECT ih.stock_id, SUM(ih.quantity) as qty FROM inventory_hold ih LEFT JOIN serials s ON ih.serial_id = s.id WHERE (s.id IS NULL OR s.status = 6) GROUP BY ih.stock_id', cardinality::SET, []);
		$on_hold_array = [];
		foreach ($holds as $hold) {
			$on_hold_array[$hold['stock_id']] = $hold['qty'];
		}
		return $on_hold_array;
	}

	public static function get_stock_id_by_product_id($products_id) {
		return self::query_fetch('SELECT stock_id FROM products WHERE products_id = :products_id', cardinality::SINGLE, [':products_id' => $products_id]);
	}

	public static function get_ipn_by_products_id($products_id) {
		if ($stock_id = self::query_fetch('SELECT stock_id FROM products WHERE products_id = :products_id', cardinality::SINGLE, [':products_id' => $products_id])) {
			return new self($stock_id);
		}
		else return NULL;
	}

	public function has_rfq_history_range(DateTime $start_range=NULL, DateTime $end_range=NULL) {
		if (!$this->skeleton->built('rfq_history_range') || !empty($start_range) || !empty($end_range)) $this->build_rfq_history_range($start_range, $end_range);
		return $this->skeleton->has('rfq_history_range');
	}

	public function get_rfq_history_range(DateTime $start_range=NULL, DateTime $end_range=NULL) {
		if (!$this->has_rfq_history_range($start_range, $end_range)) return [];
		return $this->skeleton->get('rfq_history_range');
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public static function build_current_ipn_ranks($stock_id=NULL) {
		try {
			debug_tools::mark('Start');

			$dates = [
				'today' => new DateTime(),
				'0-30' => new DateTime(),
				'0-60' => new DateTime(),
				'0-90' => new DateTime(),
			];

			$dates['today']->sub(new DateInterval('P1D'));
			$dates['0-30']->sub(new DateInterval('P30D'));
			$dates['0-60']->sub(new DateInterval('P60D'));
			$dates['0-90']->sub(new DateInterval('P90D'));

			prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE DATE(i.inv_date) = :today GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date = :today GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_last_day = IFNULL(ii.gross_margin_dollars, 0), psc.usage_last_day = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0) WHERE (:stock_id IS NULL OR psc.stock_id = :stock_id)', [':today' => $dates['today']->format('Y-m-d'), ':stock_id' => $stock_id]);

			prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE DATE(i.inv_date) >= :thirty GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :thirty GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_0_30 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_0_30= IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0) WHERE (:stock_id IS NULL OR psc.stock_id = :stock_id)', [':thirty' => $dates['0-30']->format('Y-m-d'), ':stock_id' => $stock_id]);

			prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE DATE(i.inv_date) >= :sixty GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :sixty GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_0_60 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_0_60 = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0) WHERE (:stock_id IS NULL OR psc.stock_id = :stock_id)', [':sixty' => $dates['0-60']->format('Y-m-d'), ':stock_id' => $stock_id]);

			prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE DATE(i.inv_date) >= :ninety GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :ninety GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_0_90 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_0_90 = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0) WHERE (:stock_id IS NULL OR psc.stock_id = :stock_id)', [':ninety' => $dates['0-90']->format('Y-m-d'), ':stock_id' => $stock_id]);


			// honor excluded flag


			prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id JOIN orders_products op ON ii.orders_product_id = op.orders_products_id AND op.exclude_forecast = 0 WHERE DATE(i.inv_date) = :today GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date = :today GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_excluded_last_day = IFNULL(ii.gross_margin_dollars, 0), psc.usage_excluded_last_day = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0) WHERE (:stock_id IS NULL OR psc.stock_id = :stock_id)', [':today' => $dates['today']->format('Y-m-d'), ':stock_id' => $stock_id]);

			prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id JOIN orders_products op ON ii.orders_product_id = op.orders_products_id AND op.exclude_forecast = 0 WHERE DATE(i.inv_date) >= :thirty GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :thirty GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_excluded_0_30 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_excluded_0_30= IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0) WHERE (:stock_id IS NULL OR psc.stock_id = :stock_id)', [':thirty' => $dates['0-30']->format('Y-m-d'), ':stock_id' => $stock_id]);

			prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id JOIN orders_products op ON ii.orders_product_id = op.orders_products_id AND op.exclude_forecast = 0 WHERE DATE(i.inv_date) >= :sixty GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :sixty GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_excluded_0_60 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_excluded_0_60 = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0) WHERE (:stock_id IS NULL OR psc.stock_id = :stock_id)', [':sixty' => $dates['0-60']->format('Y-m-d'), ':stock_id' => $stock_id]);

			prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN (SELECT ii.ipn_id as stock_id, SUM((ii.revenue * ABS(ii.invoice_item_qty)) - ii.orders_product_cost_total) as gross_margin_dollars, SUM(ii.invoice_item_qty) as sales FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id JOIN orders_products op ON ii.orders_product_id = op.orders_products_id AND op.exclude_forecast = 0 WHERE DATE(i.inv_date) >= :ninety GROUP BY ii.ipn_id) ii ON psc.stock_id = ii.stock_id LEFT JOIN (SELECT stock_id, SUM(ABS(new_value - old_value)) as conversions FROM products_stock_control_change_history WHERE type_id IN (41) AND change_date >= :ninety GROUP BY stock_id) c ON psc.stock_id = c.stock_id SET psc.gross_margin_dollars_excluded_0_90 = IFNULL(ii.gross_margin_dollars, 0), psc.usage_excluded_0_90 = IFNULL(ii.sales, 0) + IFNULL(c.conversions, 0) WHERE (:stock_id IS NULL OR psc.stock_id = :stock_id)', [':ninety' => $dates['0-90']->format('Y-m-d'), ':stock_id' => $stock_id]);

			debug_tools::mark('Finished');
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public static function build_daily_ledger_history(DateTime $start_date=NULL, $stock_id=NULL, $first_run=TRUE, $force_reset=FALSE) {
		debug_tools::start_sub_timer('Ledger');
		debug_tools::mark('Start Ledger');

		$one_day = new DateInterval('P1D');

		if (empty($start_date)) {
			if ($first_run) {
				$start_date = prepared_query::fetch('SELECT MIN(calendar_date) FROM ck_reporting_calendar', cardinality::SINGLE);
				$start_date = self::DateTime($start_date);
			}
			else {
				$start_date = prepared_query::fetch('SELECT MAX(transaction_date) FROM ck_inventory_ledgers', cardinality::SINGLE);
				$start_date = self::DateTime($start_date);
				$start_date->add($one_day);
			}
		}

		try {
			$lacs = prepared_query::fetch('SELECT * FROM ck_ledger_action_codes');
			$ledger_action_codes = [];
			foreach ($lacs as $lac) {
				$ledger_action_codes[$lac['action_code']] = $lac['ledger_action_code_id'];
			}

			$table_definition = '(inventory_ledger_id INT(11) NOT NULL AUTO_INCREMENT, stock_id INT(11) NOT NULL, ledger_action_code_id INT(11) NOT NULL, ref_number VARCHAR(128) NOT NULL, starting_qty INT(11) NOT NULL, qty_change INT(11) NOT NULL, ending_qty INT(11) NOT NULL, avg_cost_on_date DECIMAL(14,4) DEFAULT NULL, transaction_cost DECIMAL(14,4) DEFAULT NULL, purchase_order_id INT(11) DEFAULT NULL, invoice_id INT(11) DEFAULT NULL, rma_id INT(11) DEFAULT NULL, inventory_adjustment_id INT(11) DEFAULT NULL, transaction_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, transaction_date DATE NOT NULL, PRIMARY KEY (inventory_ledger_id), INDEX stock_transaction (stock_id, transaction_timestamp, transaction_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

			if ($first_run) {
				if ($force_reset) prepared_query::execute('DROP TABLE IF EXISTS ck_inventory_ledgers_backup');
				if (prepared_query::fetch("SHOW TABLES LIKE 'ck_inventory_ledgers'")) prepared_query::execute('RENAME TABLE ck_inventory_ledgers TO ck_inventory_ledgers_backup');
				prepared_query::execute('CREATE TABLE IF NOT EXISTS ck_inventory_ledgers '.$table_definition);
			}

			prepared_query::execute('DROP TABLE IF EXISTS ck_inventory_ledgers_temp');
			prepared_query::execute('CREATE TABLE IF NOT EXISTS ck_inventory_ledgers_temp '.$table_definition);

			//---------------------
			// Initial Data Fill
			//---------------------

			debug_tools::mark('Start Data Fill');

			// creation entries (zero change records)
			prepared_query::execute("INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT stock_id, :ledger_action_code_id, CONCAT_WS(' ', 'Create', stock_name), 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, date_added, date_added FROM products_stock_control WHERE (:stock_id IS NULL OR stock_id = :stock_id) AND date_added >= :start_date ORDER BY date_added ASC, stock_id ASC", [':ledger_action_code_id' => $ledger_action_codes['creation'], ':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);

			// inventory confirmations (qty keyframes)
			prepared_query::execute("INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT pscch.stock_id, :ledger_action_code_id, CONCAT_WS(' ', 'Confirm', pscch.change_id), CAST(pscch.old_value as SIGNED), CAST(pscch.new_value as SIGNED) - CAST(pscch.old_value as SIGNED), CAST(pscch.new_value as SIGNED), NULL, NULL, NULL, NULL, NULL, NULL, pscch.change_date, pscch.change_date FROM products_stock_control_change_history pscch LEFT JOIN inventory_adjustment ia ON pscch.stock_id = ia.ipn_id AND pscch.type_id IN (4, 5) AND ia.inventory_adjustment_type_id IN (3, 4) AND pscch.old_value = ia.old_qty AND pscch.new_value = ia.new_qty AND pscch.change_date = ia.scrap_date WHERE pscch.type_id IN (4, 5) AND ia.id IS NULL AND (:stock_id IS NULL OR pscch.stock_id = :stock_id) AND DATE(pscch.change_date) >= :start_date ORDER BY pscch.change_date ASC, pscch.change_id ASC", [':ledger_action_code_id' => $ledger_action_codes['confirmation'], ':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);
			// cost change confirmations (cost keyframes)
			prepared_query::execute("INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT pscch.stock_id, :ledger_action_code_id, CONCAT_WS(' ', 'Cost Change', pscch.change_id), 0, 0, 0, pscch.new_value, NULL, NULL, NULL, NULL, NULL, pscch.change_date, pscch.change_date FROM products_stock_control_change_history pscch WHERE pscch.type_id IN (6) AND (:stock_id IS NULL OR pscch.stock_id = :stock_id) AND DATE(pscch.change_date) >= :start_date ORDER BY pscch.change_date ASC, pscch.change_id ASC", [':ledger_action_code_id' => $ledger_action_codes['cost change'], ':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);

			debug_tools::mark('Change History Done');

			// pos, which increase inventory
			prepared_query::execute("INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT pop.ipn_id, :ledger_action_code_id, CONCAT_WS('', 'PO #', po.id), 0, porp.quantity_received, 0, NULL, pop.cost, po.id, NULL, NULL, NULL, pors.date, pors.date FROM purchase_order_received_products porp JOIN purchase_order_products pop ON porp.purchase_order_product_id = pop.id JOIN purchase_orders po ON pop.purchase_order_id = po.id JOIN purchase_order_receiving_sessions pors ON porp.receiving_session_id = pors.id AND (:stock_id IS NULL OR pop.ipn_id = :stock_id) AND DATE(pors.date) >= :start_date WHERE porp.quantity_received > 0 ORDER BY pors.date ASC, pors.id ASC", [':ledger_action_code_id' => $ledger_action_codes['purchase'], ':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);

			debug_tools::mark('PO Receipts Done');

			// po unreceives, which decrease inventory
			prepared_query::execute("INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT poup.stock_id, :ledger_action_code_id, CONCAT_WS(' ', 'Unreceive', poup.purchase_order_unreceive_product_id), 0, poup.new_quantity_received - poup.old_quantity_received, 0, NULL, NULL, poup.purchase_order_id, NULL, NULL, NULL, poup.unreceive_date, poup.unreceive_date FROM purchase_order_unreceive_products poup WHERE (:stock_id IS NULL OR poup.stock_id = :stock_id) AND DATE(poup.unreceive_date) >= :start_date ORDER BY poup.unreceive_date ASC, poup.purchase_order_unreceive_product_id ASC", [':ledger_action_code_id' => $ledger_action_codes['unreceive'], ':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);
			// po unreceives, which alter the cost
			prepared_query::execute("INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT pscch.stock_id, :ledger_action_code_id, CONCAT_WS(' ', 'Unreceive Cost Change', pscch.change_id), 0, 0, 0, pscch.new_value, NULL, NULL, NULL, NULL, NULL, pscch.change_date, pscch.change_date FROM products_stock_control_change_history pscch WHERE pscch.type_id IN (47) AND (:stock_id IS NULL OR pscch.stock_id = :stock_id) AND DATE(pscch.change_date) >= :start_date ORDER BY pscch.change_date ASC, pscch.change_id ASC", [':ledger_action_code_id' => $ledger_action_codes['unreceive cost change'], ':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);

			debug_tools::mark('PO Unreceipts Done');

			// sales, which reduce inventory
			prepared_query::execute("INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT ii.ipn_id, :ledger_action_code_id, CONCAT_WS('', 'Invoice #', i.invoice_id), 0, -1 * ii.invoice_item_qty, 0, ii.orders_product_cost, NULL, NULL, i.invoice_id, NULL, NULL, i.inv_date, i.inv_date FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE i.inv_order_id IS NOT NULL AND (:stock_id IS NULL OR ii.ipn_id = :stock_id) AND DATE(i.inv_date) >= :start_date ORDER BY i.inv_date ASC, i.invoice_id ASC", [':ledger_action_code_id' => $ledger_action_codes['invoice'], ':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);

			debug_tools::mark('Invoice Shipments Done');

			// rmas, which increase inventory
			prepared_query::execute("INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT ii.ipn_id, :ledger_action_code_id, CONCAT_WS('', 'RMA #', i.rma_id), 0, ABS(ii.invoice_item_qty), 0, NULL, ABS(ii.orders_product_cost), NULL, NULL, i.rma_id, NULL, i.inv_date, i.inv_date FROM acc_invoices i JOIN acc_invoice_items ii ON i.invoice_id = ii.invoice_id WHERE i.rma_id IS NOT NULL AND (:stock_id IS NULL OR ii.ipn_id = :stock_id) AND DATE(i.inv_date) >= :start_date ORDER BY i.inv_date ASC, i.invoice_id ASC", [':ledger_action_code_id' => $ledger_action_codes['rma'], ':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);

			debug_tools::mark('RMAs Done');

			// inventory adjustments, conversions, scraps, etc.
			prepared_query::execute("INSERT INTO ck_inventory_ledgers_temp (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT ia.ipn_id, CASE WHEN ia.inventory_adjustment_type_id IN (3, 4) THEN :adjustment_action WHEN ia.inventory_adjustment_type_id IN (5, 6, 7) THEN :conversion_action ELSE :scrap_action END, CONCAT_WS('', 'Adjustment [', iat.name, '] #', ia.id), ia.old_qty, CASE WHEN NULLIF(ia.serial_id, 0) IS NOT NULL AND ia.new_qty > ia.old_qty THEN 1 WHEN NULLIF(ia.serial_id, 0) IS NOT NULL AND ia.new_qty < ia.old_qty THEN -1 ELSE ia.new_qty - ia.old_qty END, ia.new_qty, CASE WHEN NULLIF(ia.cost, 0) IS NOT NULL THEN ia.cost WHEN NULLIF(ia.new_avg_cost, 0) IS NOT NULL THEN ia.new_avg_cost ELSE NULL END, NULL, NULL, NULL, NULL, ia.id, ia.scrap_date, ia.scrap_date FROM inventory_adjustment ia JOIN inventory_adjustment_type iat ON ia.inventory_adjustment_type_id = iat.id WHERE ia.old_qty != ia.new_qty AND (:stock_id IS NULL OR ia.ipn_id = :stock_id) AND DATE(ia.scrap_date) >= :start_date ORDER BY ia.scrap_date ASC, ia.id ASC", [':adjustment_action' => $ledger_action_codes['adjustment'], ':conversion_action' => $ledger_action_codes['conversion'], ':scrap_action' => $ledger_action_codes['scrap'], ':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);

			debug_tools::mark('Adjustments Done');

			prepared_query::execute('DELETE il FROM ck_inventory_ledgers_temp il JOIN products_stock_control psc ON il.stock_id = psc.stock_id WHERE psc.is_bundle = 1');

			debug_tools::mark('Removing Bundles Done');

			//---------------------
			// Re-sort on date
			//---------------------

			// resort out of our temp table
			prepared_query::execute('INSERT INTO ck_inventory_ledgers (stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date) SELECT stock_id, ledger_action_code_id, ref_number, starting_qty, qty_change, ending_qty, avg_cost_on_date, transaction_cost, purchase_order_id, invoice_id, rma_id, inventory_adjustment_id, transaction_timestamp, transaction_date FROM ck_inventory_ledgers_temp ORDER BY transaction_timestamp ASC, stock_id ASC, CASE WHEN purchase_order_id IS NULL AND invoice_id IS NULL AND rma_id IS NULL AND inventory_adjustment_id IS NULL THEN 1 ELSE 0 END ASC');
			// remove the temp table - we just used it for sorting
			prepared_query::execute('DROP TABLE ck_inventory_ledgers_temp');

			debug_tools::mark('Resorting Done');

			//---------------------
			// Fill in running qtys & running costs
			//---------------------

			// backfill our running counts, counting up from our keyframes
			$running_values = [];
			$counter = 0;
			$batch = 40000;
			// we run out of memory if we don't do this in batches
			// this can't be prepared because our version of MySQL won't allow parameterizing LIMITs
			while ($entries = prepared_query::fetch('SELECT * FROM ck_inventory_ledgers WHERE transaction_date >= :start_date ORDER BY transaction_timestamp ASC, stock_id ASC LIMIT '.($counter*$batch).', '.$batch, cardinality::SET, [':start_date' => $start_date->format('Y-m-d')])) {
				foreach ($entries as $idx => $entry) {
					if (!$first_run && empty($running_values[$entry['stock_id']])) $running_values[$entry['stock_id']] = prepared_query::fetch('populate_daily_ledger_init_runs', [':stock_id' => $entry['stock_id'], ':transaction_date' => $start_date->format('Y-m-d')]);
					elseif (empty($running_values[$entry['stock_id']])) $running_values[$entry['stock_id']] = ['ending_qty' => $entry['starting_qty'], 'avg_cost_on_date' => NULL];

					if (empty($running_values[$entry['stock_id']])) $running_values[$entry['stock_id']] = ['ending_qty' => 0, 'avg_cost_on_date' => NULL];

					if (($counter * $batch) + $idx % 5000 == 0) debug_tools::mark('Backfill Running Counts Iteration '.(($counter * $batch) + $idx));

					$starting_qty = $running_values[$entry['stock_id']]['ending_qty'];
					$ending_qty = $running_values[$entry['stock_id']]['ending_qty'] += $entry['qty_change'];
					$qty_change = $entry['qty_change'];

					// if this is a qty confirmation, forget the current running count and just set it to what we've got here
					if ($entry['ledger_action_code_id'] == 1) {
						$ending_qty = $running_values[$entry['stock_id']]['ending_qty'] = $entry['ending_qty'];
						$qty_change = $ending_qty - $starting_qty;
						$entry['starting_qty'] = $starting_qty;
					}

					if (empty($running_values[$entry['stock_id']]['avg_cost_on_date'])) {
						if (!empty($entry['avg_cost_on_date'])) $running_values[$entry['stock_id']]['avg_cost_on_date'] = $entry['avg_cost_on_date'];
						elseif (!empty($entry['purchase_order_id'])) $running_values[$entry['stock_id']]['avg_cost_on_date'] = $entry['transaction_cost'];
						elseif (!empty($entry['rma_id'])) $running_values[$entry['stock_id']]['avg_cost_on_date'] = $entry['transaction_cost'];
					}
					else {
						if (!empty($entry['avg_cost_on_date'])) $running_values[$entry['stock_id']]['avg_cost_on_date'] = $entry['avg_cost_on_date'];
						elseif (!empty($entry['purchase_order_id'])) {
							$running_values[$entry['stock_id']]['avg_cost_on_date'] = !empty($ending_qty)?(($starting_qty * $running_values[$entry['stock_id']]['avg_cost_on_date']) + ($qty_change * $entry['transaction_cost'])) / $ending_qty:0;
						}
						elseif (!empty($entry['rma_id'])) {
							$running_values[$entry['stock_id']]['avg_cost_on_date'] = !empty($ending_qty)?(($starting_qty * $running_values[$entry['stock_id']]['avg_cost_on_date']) + ($qty_change * $entry['transaction_cost'])) / $ending_qty:0;
						}
					}

					$avg_cost_on_date = isset($running_values[$entry['stock_id']]['avg_cost_on_date'])?$running_values[$entry['stock_id']]['avg_cost_on_date']:NULL;

					$fields = [
						':starting_qty' => $starting_qty,
						':qty_change' => $qty_change,
						':ending_qty' => $ending_qty,
						':avg_cost_on_date' => $avg_cost_on_date,
						':inventory_ledger_id' => $entry['inventory_ledger_id']
					];

					// this entry is already complete, we don't need to fix it
					// we make zero effort to change existing avg costs, so we only care if we've changed to or from null
					if ($starting_qty == $entry['starting_qty'] && $ending_qty == $entry['ending_qty'] && is_null($avg_cost_on_date) == is_null($entry['avg_cost_on_date'])) continue;

					prepared_query::execute('UPDATE ck_inventory_ledgers SET starting_qty = :starting_qty, qty_change = :qty_change, ending_qty = :ending_qty, avg_cost_on_date = :avg_cost_on_date WHERE inventory_ledger_id = :inventory_ledger_id', $fields);
				}
				$counter++;
			}
			unset($entries);

			debug_tools::mark('Running Inventory & Costs Done');

			// we filled cost forwards as best we can, now go backwards and fill in any missing costs from future values
			if ($nocosts = prepared_query::fetch('SELECT * FROM ck_inventory_ledgers WHERE avg_cost_on_date IS NULL')) {
				$ttl = count($nocosts);
				foreach ($nocosts as $idx => $entry) {

					if ($idx % 5000 == 0) debug_tools::mark('Backfill Empty Costs Iteration '.$idx.' of '.$ttl);

					$future_cost = prepared_query::fetch('SELECT avg_cost_on_date FROM ck_inventory_ledgers WHERE stock_id = :stock_id AND avg_cost_on_date IS NOT NULL AND transaction_timestamp > :transaction_timestamp ORDER BY transaction_timestamp ASC LIMIT 1', cardinality::SINGLE, [':stock_id' => $entry['stock_id'], ':transaction_timestamp' => $entry['transaction_timestamp']]);

					$update = [':avg_cost_on_date' => $future_cost, ':inventory_ledger_id' => $entry['inventory_ledger_id']];

					prepared_query::execute('UPDATE ck_inventory_ledgers SET avg_cost_on_date = :avg_cost_on_date WHERE inventory_ledger_id = :inventory_ledger_id', $update);
				}
			}
			unset($nocosts);

			debug_tools::mark('Backfill Empty Costs Done');
		}
		catch (Exception $e) {
			echo $e->__toString();
		}

		debug_tools::mark('Ledger Total Run Time');
		debug_tools::clear_sub_timer_context();
	}

	public static function build_physical_inventory_snapshot_history($stock_id=NULL, $first_run=TRUE, $force_reset=FALSE) {
		debug_tools::start_sub_timer('Physical');
		debug_tools::mark('Start Physical Inventory');

		$one_day = new DateInterval('P1D');

		if ($first_run) {
			$start_date = prepared_query::fetch('SELECT MIN(calendar_date) FROM ck_reporting_calendar', cardinality::SINGLE);
			$start_date = self::DateTime($start_date);
		}
		else {
			$start_date = prepared_query::fetch('SELECT MAX(record_date) FROM ck_daily_physical_inventory_snapshot', cardinality::SINGLE);
			$start_date = self::DateTime($start_date);
			$start_date->add($one_day);
		}

		$end_date = new DateTime;
		$end_date->sub(new DateInterval('PT22H')); // if we're after 10PM, we're getting todays data, if we're before, we're getting yesterdays data

		try {
			// this do/while block is literally to just simulate a naked block
			/**/
			do {
				$ledger_snapshot_definition = '(daily_physical_inventory_snapshot_id BIGINT(20) NOT NULL AUTO_INCREMENT, stock_id INT(11) NOT NULL, usage_qty INT(11) NOT NULL, increase_qty INT(11) NOT NULL, day_end_qty INT(11) NOT NULL, day_end_unit_cost DECIMAL(14,4) NOT NULL, daily_runrate DECIMAL(12,2) DEFAULT NULL, days_on_hand INT(11) DEFAULT NULL, day_end_available_qty INT(11) DEFAULT NULL, intra_day_zero_on_hand TINYINT(4) NOT NULL DEFAULT 0, consecutive_days_zero_on_hand INT(11) DEFAULT 0, record_date DATE NOT NULL, next_record_date DATE NOT NULL, finished TINYINT(4) NOT NULL DEFAULT 1, PRIMARY KEY (daily_physical_inventory_snapshot_id), INDEX consecutive_zero_on_hand (stock_id, consecutive_days_zero_on_hand, record_date, next_record_date), UNIQUE stock_transaction_date (stock_id, record_date, next_record_date, finished) USING BTREE) ENGINE=InnoDB DEFAULT CHARSET=utf8';
				
				// we always drop and re-create rather than truncate, this will ensure we get the most up-to-date table definition
				prepared_query::execute('DROP TABLE IF EXISTS ck_daily_physical_inventory_snapshot_temp');
				prepared_query::execute('CREATE TABLE IF NOT EXISTS ck_daily_physical_inventory_snapshot_temp '.$ledger_snapshot_definition);

				if ($first_run) {
					// on first run, we have to manually delete any previous backup
					if ($force_reset) prepared_query::execute('DROP TABLE IF EXISTS ck_daily_physical_inventory_snapshot_backup');
					if (prepared_query::fetch("SHOW TABLES LIKE 'ck_daily_physical_inventory_snapshot'")) prepared_query::execute('RENAME TABLE ck_daily_physical_inventory_snapshot TO ck_daily_physical_inventory_snapshot_backup');
				}
				else {
					prepared_query::execute('DROP TABLE IF EXISTS ck_daily_physical_inventory_snapshot_temp1');
					prepared_query::execute('RENAME TABLE ck_daily_physical_inventory_snapshot TO ck_daily_physical_inventory_snapshot_temp1');
				}
				
				prepared_query::execute('CREATE TABLE IF NOT EXISTS ck_daily_physical_inventory_snapshot '.$ledger_snapshot_definition);

				// ranks
				prepared_query::execute('CREATE TABLE IF NOT EXISTS ck_daily_physical_inventory_snapshot_ranks (daily_physical_inventory_snapshot_rank_id INT(11) NOT NULL AUTO_INCREMENT, daily_physical_inventory_snapshot_id INT(11) NOT NULL, gross_margin_dollars_0 DECIMAL(14,4) NOT NULL DEFAULT 0, gross_margin_dollars_0_30 DECIMAL(14,4) NOT NULL DEFAULT 0, gross_margin_dollars_0_60 DECIMAL(14,4) NOT NULL DEFAULT 0, gross_margin_dollars_0_90 DECIMAL(14,4) NOT NULL DEFAULT 0, usage_0 INT(11) NOT NULL DEFAULT 0, usage_0_30 INT(11) NOT NULL DEFAULT 0, usage_0_60 INT(11) NOT NULL DEFAULT 0, usage_0_90 INT(11) NOT NULL DEFAULT 0, gross_margin_dollars_excluded_0 DECIMAL(14,4) NOT NULL DEFAULT 0, gross_margin_dollars_excluded_0_30 DECIMAL(14,4) NOT NULL DEFAULT 0, gross_margin_dollars_excluded_0_60 DECIMAL(14,4) NOT NULL DEFAULT 0, gross_margin_dollars_excluded_0_90 DECIMAL(14,4) NOT NULL DEFAULT 0, usage_excluded_0 INT(11) NOT NULL DEFAULT 0, usage_excluded_0_30 INT(11) NOT NULL DEFAULT 0, usage_excluded_0_60 INT(11) NOT NULL DEFAULT 0, usage_excluded_0_90 INT(11) NOT NULL DEFAULT 0, PRIMARY KEY (daily_physical_inventory_snapshot_rank_id), UNIQUE daily_physical_inventory_snapshot_id (daily_physical_inventory_snapshot_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

				// the ongoing point-in-time snapshot; cannot be recreated
				$pit_snapshot_definition = '(daily_recorded_inventory_snapshot_id INT(11) NOT NULL AUTO_INCREMENT, stock_id INT(11) NOT NULL, recorded_quantity INT(11) NOT NULL, recorded_total_cost DECIMAL(14,4) NOT NULL, discrepancy_quantity INT(11) NOT NULL, days_discrepancy INT(11) NOT NULL DEFAULT 0, previous_record_id INT(11) NOT NULL, record_date DATE NOT NULL, PRIMARY KEY (daily_recorded_inventory_snapshot_id), UNIQUE stock_id (stock_id, record_date), INDEX discrepancy_quantity (discrepancy_quantity) USING BTREE) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

				prepared_query::execute('CREATE TABLE IF NOT EXISTS ck_daily_recorded_inventory_snapshot '.$pit_snapshot_definition);

				prepared_query::execute('DROP TABLE IF EXISTS ipn_calendar_temp');
				prepared_query::execute('CREATE TABLE ipn_calendar_temp (ipn_calendar_id INT(11) NOT NULL AUTO_INCREMENT, stock_id INT(11) NOT NULL, record_date DATE NOT NULL, relevant TINYINT(4) NOT NULL DEFAULT 0, PRIMARY KEY (ipn_calendar_id), UNIQUE ipn_date (stock_id, record_date), INDEX relevant (relevant)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
			}
			while (FALSE);

			// start filling in data
			prepared_query::execute('INSERT INTO ck_daily_physical_inventory_snapshot_temp (stock_id, usage_qty, increase_qty, intra_day_zero_on_hand, record_date, next_record_date) SELECT il.stock_id, SUM(IF(il.qty_change < 0, ABS(il.qty_change), 0)), SUM(IF(il.qty_change > 0, il.qty_change, 0)), IF(SUM(IF(il.ending_qty <= 0, 1, 0)) > 0, 1, 0), il.transaction_date, il.transaction_date + INTERVAL 1 DAY FROM ck_inventory_ledgers il WHERE (:stock_id IS NULL OR il.stock_id = :stock_id) AND il.transaction_date >= :start_date GROUP BY il.stock_id, il.transaction_date', [':stock_id' => $stock_id, ':start_date' => $start_date->format('Y-m-d')]);

			debug_tools::mark('Initial import done');

			prepared_query::execute('UPDATE ck_daily_physical_inventory_snapshot_temp dpis JOIN ck_inventory_ledgers il ON dpis.stock_id = il.stock_id AND dpis.record_date = il.transaction_date LEFT JOIN ck_inventory_ledgers il0 ON il.stock_id = il0.stock_id AND il.transaction_date = il0.transaction_date AND il.transaction_timestamp < il0.transaction_timestamp SET dpis.day_end_qty = il.ending_qty, dpis.day_end_unit_cost = il.avg_cost_on_date, dpis.consecutive_days_zero_on_hand = IF(il.ending_qty <= 0, 1, 0) WHERE il0.inventory_ledger_id IS NULL');

			debug_tools::mark('Day end values done');

			if ($first_run) {
				prepared_query::execute('INSERT INTO ipn_calendar_temp (stock_id, record_date) SELECT ic.stock_id, ic.calendar_date FROM ckv_ipn_calendar ic JOIN (SELECT stock_id, MIN(transaction_date) as record_date FROM ck_inventory_ledgers WHERE (:stock_id IS NULL OR stock_id = :stock_id) AND transaction_date >= :start_date GROUP BY stock_id) il ON ic.stock_id = il.stock_id WHERE (:stock_id IS NULL OR ic.stock_id = :stock_id) AND ic.calendar_date <= :end_date AND ic.calendar_date >= il.record_date', [':stock_id' => $stock_id, ':end_date' => $end_date->format('Y-m-d'), ':start_date' => $start_date->format('Y-m-d')]);
			}
			else {
				prepared_query::execute('INSERT INTO ipn_calendar_temp (stock_id, record_date) SELECT ic.stock_id, ic.calendar_date FROM ckv_ipn_calendar ic WHERE (:stock_id IS NULL OR ic.stock_id = :stock_id) AND ic.calendar_date <= :end_date AND ic.calendar_date >= :start_date', [':stock_id' => $stock_id, ':end_date' => $end_date->format('Y-m-d'), ':start_date' => $start_date->format('Y-m-d')]);
			}

			debug_tools::mark('Pre No-Transaction Data');
			/**/

			prepared_query::execute('DELETE ic FROM ipn_calendar_temp ic JOIN ck_daily_physical_inventory_snapshot_temp dpist ON ic.stock_id = dpist.stock_id AND ic.record_date = dpist.record_date');

			debug_tools::mark('Deleted calendar dates with data');

			$ctr = 0;

			while ($ctr%10 != 0 || $left = prepared_query::fetch('SELECT COUNT(ic.ipn_calendar_id) FROM ipn_calendar_temp ic', cardinality::SINGLE)) {
				if ($ctr%10 == 0) debug_tools::mark('Non-Transaction Rows: Iteration '.$ctr.'; '.(@$left).' left');

				prepared_query::execute('INSERT INTO ck_daily_physical_inventory_snapshot_temp (stock_id, usage_qty, increase_qty, day_end_qty, day_end_unit_cost, day_end_available_qty, intra_day_zero_on_hand, consecutive_days_zero_on_hand, record_date, next_record_date, finished) SELECT ic.stock_id, 0, 0, dpist.day_end_qty, dpist.day_end_unit_cost, dpist.day_end_available_qty, IF(dpist.day_end_qty = 0, 1, 0), IF(dpist.consecutive_days_zero_on_hand > 0, dpist.consecutive_days_zero_on_hand + 1, 0), ic.record_date, ic.record_date + INTERVAL 1 DAY, 1 FROM ipn_calendar_temp ic JOIN ck_daily_physical_inventory_snapshot_temp dpist ON ic.stock_id = dpist.stock_id AND ic.record_date = dpist.next_record_date');

				prepared_query::execute('DELETE ic FROM ipn_calendar_temp ic JOIN ck_daily_physical_inventory_snapshot_temp dpist ON ic.stock_id = dpist.stock_id AND ic.record_date = dpist.record_date');

				$ctr++;
			}

			debug_tools::mark('Fill in non-transaction days done');

			if ($first_run) {
				prepared_query::execute('INSERT INTO ck_daily_physical_inventory_snapshot (stock_id, usage_qty, increase_qty, day_end_qty, day_end_unit_cost, daily_runrate, days_on_hand, day_end_available_qty, intra_day_zero_on_hand, consecutive_days_zero_on_hand, record_date, next_record_date, finished) SELECT stock_id, usage_qty, increase_qty, day_end_qty, day_end_unit_cost, daily_runrate, days_on_hand, day_end_available_qty, intra_day_zero_on_hand, consecutive_days_zero_on_hand, record_date, next_record_date, finished FROM ck_daily_physical_inventory_snapshot_temp ORDER BY record_date ASC, stock_id ASC');
			}
			else {
				prepared_query::execute('INSERT INTO ck_daily_physical_inventory_snapshot (stock_id, usage_qty, increase_qty, day_end_qty, day_end_unit_cost, daily_runrate, days_on_hand, day_end_available_qty, intra_day_zero_on_hand, consecutive_days_zero_on_hand, record_date, next_record_date, finished) SELECT * FROM (SELECT stock_id, usage_qty, increase_qty, day_end_qty, day_end_unit_cost, daily_runrate, days_on_hand, day_end_available_qty, intra_day_zero_on_hand, consecutive_days_zero_on_hand, record_date, next_record_date, finished FROM ck_daily_physical_inventory_snapshot_temp ORDER BY record_date ASC, stock_id ASC UNION SELECT stock_id, usage_qty, increase_qty, day_end_qty, day_end_unit_cost, daily_runrate, days_on_hand, day_end_available_qty, intra_day_zero_on_hand, consecutive_days_zero_on_hand, record_date, next_record_date, finished FROM ck_daily_physical_inventory_snapshot_temp1 ORDER BY record_date ASC, stock_id ASC) r ORDER BY record_date ASC, stock_id ASC');
			}

			debug_tools::mark('Resorting Done');

			prepared_query::execute('UPDATE ck_daily_physical_inventory_snapshot dpis JOIN products_stock_control psc ON dpis.stock_id = psc.stock_id LEFT JOIN ckv_legacy_inventory li ON dpis.stock_id = li.stock_id SET dpis.daily_runrate = psc.current_daily_runrate, dpis.days_on_hand = psc.current_days_on_hand, dpis.day_end_available_qty = li.available WHERE dpis.record_date = :end_date', [':end_date' => $end_date->format('Y-m-d')]);

			debug_tools::mark('Daily Totals Done');

			while (prepared_query::fetch('SELECT dpis.daily_physical_inventory_snapshot_id FROM ck_daily_physical_inventory_snapshot dpis JOIN ck_daily_physical_inventory_snapshot dpis0 ON dpis.stock_id = dpis0.stock_id AND dpis.record_date = dpis0.next_record_date WHERE dpis.consecutive_days_zero_on_hand > 0 AND dpis0.consecutive_days_zero_on_hand >= dpis.consecutive_days_zero_on_hand')) {
				prepared_query::execute('UPDATE ck_daily_physical_inventory_snapshot dpis JOIN ck_daily_physical_inventory_snapshot dpis0 ON dpis.stock_id = dpis0.stock_id AND dpis.record_date = dpis0.next_record_date SET dpis.consecutive_days_zero_on_hand = 1 + dpis0.consecutive_days_zero_on_hand WHERE dpis.consecutive_days_zero_on_hand > 0 AND dpis0.consecutive_days_zero_on_hand >= dpis.consecutive_days_zero_on_hand');
			}

			debug_tools::mark('Consecutive Days Done');

			// remove the temp tables - we just used it for sorting
			prepared_query::execute('DROP TABLE ck_daily_physical_inventory_snapshot_temp');
			prepared_query::execute('DROP TABLE IF EXISTS ck_daily_physical_inventory_snapshot_temp1');
			prepared_query::execute('DROP TABLE ipn_calendar_temp');

			debug_tools::mark('Cleanup Done');

			// We only need to run this once, after the main table has been completely built
			prepared_query::execute('INSERT INTO ck_daily_physical_inventory_snapshot_ranks (daily_physical_inventory_snapshot_id, gross_margin_dollars_0, gross_margin_dollars_0_30, gross_margin_dollars_0_60, gross_margin_dollars_0_90, usage_0, usage_0_30, usage_0_60, usage_0_90, gross_margin_dollars_excluded_0, gross_margin_dollars_excluded_0_30, gross_margin_dollars_excluded_0_60, gross_margin_dollars_excluded_0_90, usage_excluded_0, usage_excluded_0_30, usage_excluded_0_60, usage_excluded_0_90) SELECT dpis.daily_physical_inventory_snapshot_id, psc.gross_margin_dollars_last_day, psc.gross_margin_dollars_0_30, psc.gross_margin_dollars_0_60, psc.gross_margin_dollars_0_90, psc.usage_last_day, psc.usage_0_30, psc.usage_0_60, psc.usage_0_90, psc.gross_margin_dollars_excluded_last_day, psc.gross_margin_dollars_excluded_0_30, psc.gross_margin_dollars_excluded_0_60, psc.gross_margin_dollars_excluded_0_90, psc.usage_excluded_last_day, psc.usage_excluded_0_30, psc.usage_excluded_0_60, psc.usage_excluded_0_90 FROM ck_daily_physical_inventory_snapshot dpis JOIN products_stock_control psc ON dpis.stock_id = psc.stock_id WHERE dpis.record_date = :end_date ON DUPLICATE KEY UPDATE gross_margin_dollars_0=psc.gross_margin_dollars_last_day, gross_margin_dollars_0_30=psc.gross_margin_dollars_0_30, gross_margin_dollars_0_60=psc.gross_margin_dollars_0_60, gross_margin_dollars_0_90=psc.gross_margin_dollars_0_90, usage_0=psc.usage_last_day, usage_0_30=psc.usage_0_30, usage_0_60=psc.usage_0_60, usage_0_90=psc.usage_0_90, gross_margin_dollars_excluded_0=psc.gross_margin_dollars_excluded_last_day, gross_margin_dollars_excluded_0_30=psc.gross_margin_dollars_excluded_0_30, gross_margin_dollars_excluded_0_60=psc.gross_margin_dollars_excluded_0_60, gross_margin_dollars_excluded_0_90=psc.gross_margin_dollars_excluded_0_90, usage_excluded_0=psc.usage_excluded_last_day, usage_excluded_0_30=psc.usage_excluded_0_30, usage_excluded_0_60=psc.usage_excluded_0_60, usage_excluded_0_90=psc.usage_excluded_0_90', [':end_date' => $end_date->format('Y-m-d')]);

			// build the recorded snapshot
			prepared_query::execute('INSERT IGNORE INTO ck_daily_recorded_inventory_snapshot (stock_id, recorded_quantity, recorded_total_cost, previous_record_id, record_date) SELECT psc.stock_id, CASE WHEN psc.serialized = 0 THEN psc.stock_quantity ELSE s.serial_quantity END, CASE WHEN psc.serialized = 0 THEN psc.average_cost * psc.stock_quantity ELSE s.total_cost END, dris.previous_record_id, :end_date FROM products_stock_control psc LEFT JOIN (SELECT s.ipn as stock_id, COUNT(s.id) as serial_quantity, SUM(sh.cost) as total_cost FROM serials s JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id WHERE sh0.id IS NULL AND s.status IN (2, 3, 6) GROUP BY s.ipn) s ON psc.stock_id = s.stock_id LEFT JOIN (SELECT stock_id, MAX(daily_recorded_inventory_snapshot_id) as previous_record_id FROM ck_daily_recorded_inventory_snapshot GROUP BY stock_id) dris ON psc.stock_id = dris.stock_id', [':end_date' => $end_date->format('Y-m-d')]);

			prepared_query::execute('UPDATE ck_daily_recorded_inventory_snapshot dris LEFT JOIN ck_daily_physical_inventory_snapshot dpis ON dris.stock_id = dpis.stock_id AND dris.record_date = dpis.record_date LEFT JOIN ck_daily_recorded_inventory_snapshot dris0 ON dris.previous_record_id = dris0.daily_recorded_inventory_snapshot_id SET dris.discrepancy_quantity =  dpis.day_end_qty - dris.recorded_quantity, dris.days_discrepancy = CASE WHEN dpis.day_end_qty - dris.recorded_quantity != 0 THEN 1 + IFNULL(dris0.days_discrepancy, 0) ELSE 0 END WHERE dpis.record_date = :end_date', [':end_date' => $end_date->format('Y-m-d')]);
		}
		catch (Exception $e) {
			echo $e->__toString();
		}

		debug_tools::mark('Physical Inventory Total Run Time');
		debug_tools::clear_sub_timer_context();
	}

	private static $batch_savepoint;
	private static $ipn_import_id = NULL;

	public static function start_batch($ipn_import_id) {
		self::$batch_savepoint = self::transaction_begin();
		self::$ipn_import_id = $ipn_import_id;
	}

	public static function end_batch($rollback=FALSE) {
		if ($rollback) self::transaction_rollback(self::$batch_savepoint);
		else self::transaction_commit(self::$batch_savepoint);

		self::$batch_savepoint = NULL;
		self::$ipn_import_id = NULL;
	}

	public static function create($data) {
		$savepoint = self::transaction_begin();

		try {
			if (empty($data['header']['creation_reviewed'])) $data['header']['creation_reviewed'] = 0;
			if (empty($data['header']['creator'])) {
				$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);
				$data['header']['creator'] = $user->id();
			}
			if (empty($data['header']['dealer_warranty_id'])) $data['header']['dealer_warranty_id'] = NULL;
			if (is_null($data['header']['bundle_price_flows_from_included_products'])) $data['header']['bundle_price_flows_from_included_products'] = 0;
			if (is_null($data['header']['bundle_price_modifier'])) $data['header']['bundle_price_modifier'] = 0;
			if (is_null($data['header']['bundle_price_signum'])) $data['header']['bundle_price_signum'] = 0;
			if (is_null($data['header']['dlao_product'])) $data['header']['dlao_product'] = 0;
			if (is_null($data['header']['special_order_only'])) $data['header']['special_order_only'] = 0;
			if (is_null($data['header']['discontinued'])) $data['header']['discontinued'] = 0;
			if (empty($data['header']['conditioning_notes'])) $data['header']['conditioning_notes'] = NULL;
			if (empty($data['header']['eccn_code'])) $data['header']['eccn_code'] = NULL;
			if (empty($data['header']['hts_code'])) $data['header']['hts_code'] = NULL;

			if (empty($data['header']['stock_price'])) $data['header']['stock_price'] = 0;
			if (empty($data['header']['dealer_price'])) $data['header']['dealer_price'] = 0;
			if (empty($data['header']['wholesale_high_price'])) $data['header']['wholesale_high_price'] = NULL;
			if (empty($data['header']['wholesale_low_price'])) $data['header']['wholesale_low_price'] = NULL;

			$header = CK\fn::parameterize($data['header']);
			self::query_execute('INSERT INTO products_stock_control (stock_name, creation_reviewed, creator, stock_description, conditioning_notes, stock_price, dealer_price, wholesale_high_price, wholesale_low_price, conditions, serialized, stock_weight, date_added, lead_time, always_available, max_displayed_quantity, min_inventory_level, target_inventory_level, max_inventory_level, drop_ship, non_stock, freight, dlao_product, special_order_only, products_stock_control_category_id, warranty_id, dealer_warranty_id, is_bundle, bundle_price_flows_from_included_products, bundle_price_modifier, bundle_price_signum, image_reference, eccn_code, hts_code, discontinued) VALUES (:stock_name, :creation_reviewed, :creator, :stock_description, :conditioning_notes, :stock_price, :dealer_price, :wholesale_high_price, :wholesale_low_price, :conditions, :serialized, :stock_weight, NOW(), :lead_time, :always_available, :max_displayed_quantity, :min_inventory_level, :target_inventory_level, :max_inventory_level, :drop_ship, :non_stock, :freight, :dlao_product, :special_order_only, :products_stock_control_category_id, :warranty_id, :dealer_warranty_id, :is_bundle, :bundle_price_flows_from_included_products, :bundle_price_modifier, :bundle_price_signum, :image_reference, :eccn_code, :hts_code, :discontinued)', cardinality::NONE, $header);
			$stock_id = self::fetch_insert_id();

			$ipn = new self($stock_id);

			require_once(__DIR__.'/../functions/inventory_functions.php');
			insert_psc_change_history($ipn->id(), 'New IPN', '---', $ipn->get_header('stock_quantity'), !empty(self::$ipn_import_id)?self::$ipn_import_id:0);

			$data['extra']['stock_id'] = $ipn->id();
			$extra = CK\fn::parameterize($data['extra']);
			self::query_execute('INSERT INTO products_stock_control_extra (stock_id, stock_location, stock_location_2, preferred_vendor_id, preferred_vendor_part_number) VALUES (:stock_id, :stock_location, :stock_location_2, :preferred_vendor_id, :preferred_vendor_part_number)', cardinality::NONE, $extra);

			if (!empty($data['vendor']['vendors_id'])) $ipn->create_vendor_relationship($data['vendor']);

			prepared_query::execute("INSERT INTO products_stock_control_images (stock_id, image, image_med, image_lrg) VALUES (:stock_id, 'newproduct_sm.gif', 'newproduct_med.gif', 'newproduct.gif')", [':stock_id' => $ipn->id()]);

			self::transaction_commit($savepoint);
			return $ipn;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	private static $change_history_field_map = [
		'stock_price' => 'Stock Price Change',
		'last_stock_price_confirmation' => 'Stock Price Confirmation',
		'dealer_price' => 'Dealer Price Change',
		'last_dealer_price_confirmation' => 'Dealer Price Confirmation',
		'wholesale_high_price' => 'Wholesale High Price Change',
		'last_wholesale_high_price_confirmation' => 'Wholesale High Price Confirmation',
		'wholesale_low_price' => 'Wholesale Low Price Change',
		'last_wholesale_low_price_confirmation' => 'Wholesale Low Price Confirmation',
		'pricing_review' => 'Price Review Frequency Change',
		'stock_description' => 'Description Change',
		'conditions' => 'Condition Change',
		'conditioning_notes' => 'Conditioning Note Change',
		'discontinued' => 'Discontinued Flag',
		'is_bundle' => 'Bundled Product Flag',
		'donotbuy' => 'Do Not Buy Flag',
		'drop_ship' => 'Dropship Flag',
		'non_stock' => 'Non-Stock Flag',
		'freight' => 'Freight Flag',
		'dlao_product' => 'Direct Link Admin Only Flag',
		'special_order_only' => 'Special Order Flag',
		'max_displayed_quantity' => 'Max Displayed Quantity Change',
		'min_inventory_level' => 'Min Inventory Level Change',
		'max_inventory_level' => 'Max Inventory Level Change',
		'target_inventory_level' => 'Target Inventory Level Change',
		'eccn_code' => 'ECCN Code Change',
		'hts_code' => 'HTS Code Change',
		'products_stock_control_category_id' => 'IPN Category',
		'warranty_id' => 'Warranty Change',
		'dealer_warranty_id' => 'Dealer Warranty Change',
		'stock_weight' => 'Weight Change',
		'serialized' => 'Serialization Change',
		'bundle_price_flows_from_included_products' => 'Bundle Price Settings Changed',
		'bundle_price_modifier' => 'Bundle Price Modifier Change',
		'bundle_price_signum' => 'Bundle Price Signum Change',
		'creation_reviewed' => 'Creation Reviewed',
		'creation_reviewer' => 'Creation Reviewer',
		'creation_reviewed_date' => 'Creation Reviewed Date'
	];

	private static $ch_field_header_map = [
		'last_stock_price_confirmation' => 'stock_price',
		'last_dealer_price_confirmation' => 'dealer_price',
		'last_wholesale_high_price_confirmation' => 'wholesale_high_price',
		'last_wholesale_low_price_confirmation' => 'wholesale_low_price',
	];

	private static $ch_field_ids = [
		'products_stock_control_category_id' => 'ipn_category',
		'warranty_id' => 'warranty_name',
		'dealer_warranty_id' => 'dealer_warranty_name',
	];

	private static $ch_field_bools = [
		'discontinued' => TRUE,
		'is_bundle' => TRUE,
		'donotbuy' => TRUE,
		'drop_ship' => TRUE,
		'non_stock' => TRUE,
		'freight' => TRUE,
		'dlao_product' => TRUE,
		'special_order_only' => TRUE,
	];

	public function update($header, $extra=NULL, $package=NULL) {
		self::transaction_begin();

		$old_header = $this->get_header();

		try {
			if (!empty($header)) {
				$params = new ezparams($header);
				$header['stock_id'] = $this->id();
				self::query_execute('UPDATE products_stock_control SET '.$params->update_cols(TRUE).' WHERE stock_id = :stock_id', cardinality::NONE, CK\fn::parameterize($header));
				$this->skeleton->rebuild('header');

				if (isset($header['stock_price']) || isset($header['dealer_price']) || isset($header['stock_weight'])) {
					self::query_execute('UPDATE products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id SET p.products_price = psc.stock_price, p.products_dealer_price = psc.dealer_price, p.products_price_modified = NOW(), p.products_weight = psc.stock_weight WHERE p.stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);
					$this->skeleton->rebuild('listings');
				}

				foreach ($header as $key => $val) {
					if ($key == 'stock_id') continue;
					if ($val == $old_header[$key]) continue; // if the value hasn't changed then no reason to run all this

					if (isset(self::$ch_field_header_map[$key])) $oldval = $old_header[self::$ch_field_header_map[$key]];
					else $oldval = $old_header[$key];

					if ($key == 'pricing_review') $val = $pricing_review==0?'DEFAULT':$pricing_review;

					if ($key == 'bundle_price_flows_from_included_products') {
						if ($old_header[$key] == 0) $oldval = 'Directly';
						else if ($old_header[$key] == 1) $oldval = 'Option Flow, % Modifier';
						else if ($old_header[$key] == 2) $oldval = 'Option Flow, $ Modifier';

						if ($val == 0) $val = 'Directly';
						else if ($val == 1) $val = 'Option Flow, % Modifier';
						else if ($val == 2) $val = 'Option Flow, $ Modifier';
					}

					if ($key == 'bundle_price_signum') {
						if ($old_header[$key] == 0) $oldval = 'Discount';
						elseif ($old_header[$key] == 1) $oldval = 'Upcharge';

						if ($val == 0) $val = 'Discount';
						elseif ($val == 1) $val = 'Upcharge';
					}

					if (isset(self::$ch_field_ids[$key])) {
						$val = $this->get_header(self::$ch_field_ids[$key]);
						$oldval = $old_header[self::$ch_field_ids[$key]];
					}

					if (isset(self::$ch_field_bools[$key])) {
						$val = $val==0?'No':'Yes';
						$oldval = $oldval==0?'No':'Yes';
					}

					if (is_null($oldval)) $oldval = '';
					if (is_null($val)) $val = '';

					$this->create_change_history_record(self::$change_history_field_map[$key], $oldval, $val);
				}
			}

			if (!empty($extra)) {
				$extra['stock_id'] = $this->id();
				$params = new ezparams($extra);
				self::query_execute('INSERT INTO products_stock_control_extra ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).') ON DUPLICATE KEY UPDATE '.$params->insert_ondupe(), cardinality::NONE, CK\fn::parameterize($extra));
				$this->skeleton->rebuild('header');

				if (isset($extra['stock_location'])) $this->create_change_history_record('Bin Location Change', $old_header['bin1'], $extra['stock_location']);
				if (isset($extra['stock_location_2'])) $this->create_change_history_record('Bin Location2 Change', $old_header['bin2'], $extra['stock_location_2']);
			}

			if (!empty($package)) {
				$package['ipn_id'] = $this->id();
				$package = CK\fn::parameterize($package);
				self::query_execute('INSERT INTO package_type (package_length, package_width, package_height, package_name, logoed, ipn_id) VALUES (:package_length, :package_width, :package_height, :package_name, :package_logoed, :ipn_id)', cardinality::NONE, $package);
				$this->skeleton->rebuild('package');
			}

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function destroy($soft=TRUE) {
		$admin = ck_admin::login_instance();
		if (!$admin->is_top_admin()) throw new CKIpnException('You do not have permission to delete an IPN.');

		self::transaction_begin();

		try {
			self::query_execute('DELETE FROM products_stock_control WHERE stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);

			// if it's not a soft delete, get rid of data from all other related tables as well
			if (!$soft) {
			}
			else $this->create_change_history_record('Deleted IPN', $this->id(), '---');

			$this->skeleton->rebuild();

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_qty($delta, $direction, $confirmed) {
		$inventory = $this->get_inventory();

		$savepoint = self::transaction_begin();

		try {
			$update = [':qty_delta' => $delta, ':stock_id' => $this->id()];

			if ($direction == 'decrease') {
				self::query_execute('UPDATE products_stock_control SET stock_quantity = stock_quantity - :qty_delta '.($confirmed?', last_quantity_change = NOW()':'').' WHERE stock_id = :stock_id', cardinality::NONE, $update);

				$this->create_ipn_history_record('manual', 0, $delta);
				$this->update_channel_advisor_qty(-1*$delta);
				$new_qty = $inventory['on_hand'] - $delta;
				$this->create_inventory_adjustment_record(3, 3, $inventory['on_hand'], $new_qty);
			}
			elseif ($direction == 'increase') {
				self::query_execute('UPDATE products_stock_control SET stock_quantity = stock_quantity + :qty_delta '.($confirmed?', last_quantity_change = NOW()':'').' WHERE stock_id = :stock_id', cardinality::NONE, $update);

				$this->create_ipn_history_record('manual', 1, $delta);
				$new_qty = $inventory['on_hand'] + $delta;
				$this->create_inventory_adjustment_record(4, 4, $inventory['on_hand'], $new_qty);
			}

			if (!isset($new_qty)) $new_qty = $inventory['on_hand'];

			if ($confirmed) $this->create_change_history_record('Quantity Confirmation', $new_qty, $new_qty);
			$this->check_and_remove_warehouse_allocations();

			$this->skeleton->rebuild('inventory');

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}

		if ($direction == 'increase') $this->send_in_stock_notification();
	}

	public function confirm_serialized_qty() {
		$inventory = $this->get_inventory();

		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE products_stock_control SET last_quantity_change = NOW() WHERE stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);

			$this->create_change_history_record('Quantity Confirmation', $inventory['on_hand'], $inventory['on_hand']);
			$this->check_and_remove_warehouse_allocations();

			$this->skeleton->rebuild('inventory');

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	public function receive_qty($receiving_session_id, $review_product, $cost) {
		$savepoint_id = self::transaction_begin();

		try {
			//get current qty and cost for calculation
			$on_hand = $this->get_inventory('on_hand');
			$average_cost = $this->get_header('average_cost');

			$new_stock_quantity = $on_hand + $review_product['qty_received'];

			if ($new_stock_quantity > 0) $new_average_cost = abs((($average_cost * $on_hand) + ($cost * $review_product['qty_received'])) / $new_stock_quantity);
			else $new_average_cost = $average_cost;

			//update cost and qty to new results
			self::query_execute('UPDATE products_stock_control psc SET psc.average_cost = :new_average_cost, psc.stock_quantity = psc.stock_quantity + :quantity, psc.on_order = GREATEST(psc.on_order - :quantity, 0) WHERE psc.stock_id = :stock_id', cardinality::NONE, [':new_average_cost' => $new_average_cost, ':quantity' => $review_product['qty_received'], ':stock_id' => $this->id()]);

			if ($this->is('serialized')) {
				foreach ($review_product['serials'] as $serial) {
					$serial->receive($receiving_session_id, $review_product);
				}
			}
			elseif (!empty($review_product['hold_disposition_id'])) {
				self::query_execute('INSERT INTO inventory_hold (stock_id, quantity, reason_id, date, notes, creator_id) VALUES (:stock_id, :quantity, :disposition, NOW(), NULL, :creator_id)', cardinality::NONE, [':stock_id' => $this->id(), ':quantity' => $review_product['qty_received'], ':disposition' => $review_product['hold_disposition_id'], ':creator_id' => $_SESSION['login_id']]);
			}

			$this->skeleton->rebuild('header'); // because for non-serialized stock, the on-hand qty comes from the header
			$this->skeleton->rebuild('inventory');

			$this->create_ipn_history_record('PORecS', 1, $review_product['qty_received'], $review_product['po_product_id']);

			try {
				if ($this->get_inventory('available') > 0) $this->send_in_stock_notification();
			}
			catch (Exception $e) {
				// discard mail errors
			}

			//now we update the weight for the IPN
			if (!empty($review_product['weight']) && $review_product['weight'] != 0) self::query_execute('UPDATE products_stock_control SET stock_weight = :stock_weight, last_weight_change = NOW() WHERE stock_id = :stock_id', cardinality::NONE, [':stock_weight' => $review_product['weight'], ':stock_id' => $this->id()]);

			//carry the IPN qty change (and weight change, if necessary) through to any attached products
			self::query_execute('UPDATE products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id SET p.products_quantity = psc.stock_quantity, p.products_weight = psc.stock_weight WHERE psc.stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);

			self::transaction_commit($savepoint_id);
		}
		catch (CKIpnException $e) {
			self::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKIpnException('Failed receiving qty to IPN: '.$e->getMessage());
		}
	}

	public function receive_rma_qty($rma_product) {
		$savepoint = self::transaction_begin();

		$response = ['inventory_hold' => NULL];

		try {
			// update average costing, even for serialized because it's simpler this way
			$inventory = $this->get_inventory();

			$old_total_cost = $this->get_header('average_cost') * $inventory['on_hand'];
			$received_total_cost = $rma_product['quantity'] * $rma_product['cost'];
			$new_average_cost = ($old_total_cost + $received_total_cost) / ($inventory['on_hand'] + $rma_product['quantity']);

			self::query_execute('UPDATE products_stock_control SET stock_quantity = stock_quantity + :rma_quantity, average_cost = :average_cost WHERE stock_id = :stock_id', cardinality::NONE, [':rma_quantity' => $rma_product['quantity'], ':average_cost' => $new_average_cost, ':stock_id' => $this->id()]);
			self::query_execute('UPDATE products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id SET p.products_quantity = psc.stock_quantity WHERE psc.stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);

			$this->create_change_history_record('Stock Quantity', $inventory['on_hand'], $inventory['on_hand'] + $rma_product['quantity']);
			if ($new_average_cost != $this->get_header('average_cost')) $this->create_change_history_record('Average Cost', $this->get_header('average_cost'), $new_average_cost);

			if (!empty($rma_product['serial'])) {
				$history = $rma_product['serial']->get_current_history();
				$rma_product['serial']->create_history_record([
					'entered_date' => date('Y-m-d H:i:s'),
					'conditions' => $history['condition_code'],
					'dram' => $history['dram'],
					'flash' => $history['flash'],
					'mac_address' => $history['mac_address'],
					'image' => $history['image'],
					'ios' => $history['ios'],
					'version' => $history['version'],
					'cost' => $history['cost'],
					'show_version' => $history['show_version'],
					'short_notes' => $history['short_notes'],
					'rma_id' => $rma_product['rma_id']
				]);
				$rma_product['serial']->update_serial_status($rma_product['hold']?ck_serial::$statuses['HOLD']:ck_serial::$statuses['INSTOCK']);
			}

			if ($rma_product['hold']) {
				$invhold = [
					'stock_id' => $this->id(),
					'quantity' => $rma_product['quantity'],
					'reason_id' => 2, //Purchasing Review
					'serial_id' => !empty($rma_product['serial'])?$rma_product['serial']->id():NULL,
					'date' => self::NOW()->format('Y-m-d H:i:s'),
					'notes' => 'From RMA # '.$rma_product['rma_id'].(!empty($rma_product['hold_notes'])?'<br>'.$rma_product['hold_notes']:''),
					'creator_id' => !empty($_SESSION['login_id'])?$_SESSION['login_id']:ck_sales_order::$solutionsteam_id,
				];

				$params = new ezparams($invhold);
				self::query_execute('INSERT INTO inventory_hold ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, $params->query_vals([], TRUE));
				$response['inventory_hold_id'] = self::fetch_insert_id();
			}

			$update = [
				'received_by' => !empty($_SESSION['login_id'])?$_SESSION['login_id']:ck_sales_order::$solutionsteam_id
			];

			if ($rma_product['not_defective'] === FALSE) $update['not_defective'] = 0;
			elseif ($rma_product['not_defective'] === TRUE) $update['not_defective'] = 1;

			$params = new ezparams($update);
			self::query_execute('UPDATE rma_product SET '.$params->update_cols(TRUE).', received_date = NOW() WHERE id = :id', cardinality::NONE, $params->query_vals(['id' => $rma_product['rma_product_id']], TRUE));

			$rma = new ck_rma2($rma_product['rma_id']);

			if ($rma_product['not_defective'] === TRUE) {
				$not_defective_note = $this->get_header('ipn').(!empty($rma_product['serial'])?' (Serial # '.$rma_product['serial']->get_header('serial_number').') ':'').' was returned as defective failure, but passed our tests.';
				$rma->create_note(['note_text' => $not_defective_note]);

				$mailer = service_locator::get_mail_service();
				$mail = $mailer->create_mail();
				$mail->set_from('webmaster@cablesandkits.com');
				$mail->add_to('sales@cablesandkits.com');
				$mail->set_subject('RMA Product Defectve Failure Alert');
				$mail->set_body(
					'Yo CST, <br><br> On RMA: <a href="'.FQDN."/admin/rma-detail.php?id=".$rma->getId().'">'.$rma->id().'</a> '.$not_defective_note.'<br><br> Just letting you know! <br><br>Sincerely, <br> Matrix',
					strip_tags($not_defective_note)
				);
				$mailer->send($mail);
			}

			$this->skeleton->rebuild('header');
			$this->skeleton->rebuild('inventory');
			$this->skeleton->rebuild('serials');

			self::transaction_commit($savepoint);
			return $response;
		}
		catch (CKIpnException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKIpnException('Failed to add RMA qty to stock: '.$e->getMessage());
		}
	}

	public function invoice_qty($qty, Array $serials=[]) {
		$savepoint = self::transaction_begin();

		if ($this->is('is_bundle')) return TRUE;

		try {
			if ($this->is('serialized') && $qty != count($serials)) throw new CKIpnException('Cannot invoice serialized IPN without specifying the serials that need to be marked as invoiced.');

			self::query_execute('UPDATE products_stock_control SET stock_quantity = stock_quantity - :invoice_quantity WHERE stock_id = :stock_id', cardinality::NONE, [':invoice_quantity' => $qty, ':stock_id' => $this->id()]);
			self::query_execute('UPDATE products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id SET p.products_quantity = psc.stock_quantity WHERE psc.stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);

			if (!empty($serials)) {
				foreach ($serials as $serial) {
					$serial->invoice();
				}
			}

			$this->skeleton->rebuild('header');
			$this->skeleton->rebuild('inventory');
			$this->skeleton->rebuild('serials');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (CKIpnException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKIpnException('Failed to invoice qty: '.$e->getMessage());
		}
	}

	public function uninvoice_qty($invoice_line) {
		$savepoint = self::transaction_begin();

		if ($this->is('is_bundle')) return TRUE;

		try {
			if ($this->is('serialized') && $invoice_line['quantity'] != count($invoice_line['serials'])) throw new CKIpnException('Cannot uninvoice serialized IPN without specifying the serials that need to be marked as uninvoiced.');

			// update average costing, even for serialized because it's simpler this way
			$old_total_cost = $this->get_header('average_cost') * $this->get_inventory('on_hand');
			$replaced_total_cost = $invoice_line['invoice_line_cost'];
			$new_average_cost = ($old_total_cost + $replaced_total_cost) / ($this->get_inventory('on_hand') + $invoice_line['quantity']);

			self::query_execute('UPDATE products_stock_control SET stock_quantity = stock_quantity + :invoice_quantity, average_cost = :average_cost WHERE stock_id = :stock_id', cardinality::NONE, [':invoice_quantity' => $invoice_line['quantity'], ':average_cost' => $new_average_cost, ':stock_id' => $this->id()]);
			self::query_execute('UPDATE products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id SET p.products_quantity = psc.stock_quantity WHERE psc.stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);

			if ($new_average_cost != $this->get_header('average_cost')) $this->create_change_history_record('Average Cost', $this->get_header('average_cost'), $new_average_cost);

			if (!empty($invoice_line['serials'])) {
				foreach ($invoice_line['serials'] as $serial) {
					$serial->uninvoice();
				}
			}

			$this->skeleton->rebuild('header');
			$this->skeleton->rebuild('inventory');
			$this->skeleton->rebuild('serials');

			self::transaction_commit($savepoint);
			return TRUE;
		}
		catch (CKIpnException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKIpnException('Failed to uninvoice qty: '.$e->getMessage());
		}
	}

	public function update_weight($weight, $confirmed) {
		$header = $this->get_header();

		if (empty($weight) || $weight == 0) return TRUE;

		self::transaction_begin();

		try {
			$update = [':stock_weight' => $weight, ':stock_id' => $this->id()];

			self::query_execute('UPDATE products_stock_control SET stock_weight = :stock_weight '.($confirmed?', last_weight_change = NOW()':'').' WHERE stock_id = :stock_id', cardinality::NONE, $update);
			$this->skeleton->rebuild('header');

			$this->create_change_history_record('Weight Change', $header['stock_weight'], $weight);
			if ($confirmed) $this->create_change_history_record('Weight Confirmation', $header['stock_weight'], $weight);

			self::query_execute('UPDATE products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id SET p.products_weight = psc.stock_weight WHERE p.stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);
			$this->skeleton->rebuild('listings');

			self::transaction_commit();
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_average_cost($average_cost) {
		$header = $this->get_header();

		self::transaction_begin();

		try {
			$update = [':average_cost' => $average_cost, ':stock_id' => $this->id()];

			self::query_execute('UPDATE products_stock_control SET average_cost = :average_cost WHERE stock_id = :stock_id', cardinality::NONE, $update);
			$this->skeleton->rebuild('header');

			$this->create_change_history_record('Average Cost', $header['average_cost'], $average_cost);

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_target_buy_price($target_buy_price) {
		$header = $this->get_header();

		self::transaction_begin();

		try {
			$update = [':target_buy_price' => $target_buy_price, ':stock_id' => $this->id()];

			self::query_execute('UPDATE products_stock_control SET target_buy_price = :target_buy_price WHERE stock_id = :stock_id', cardinality::NONE, $update);
			$this->skeleton->rebuild('header');

			$this->create_change_history_record('Target Buy Price', $header['target_buy_price'], $target_buy_price);

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_stock_name($stock_name) {
		$header = $this->get_header();

		self::transaction_begin();

		try {
			$update = [':stock_name' => $stock_name, ':stock_id' => $this->id()];

			self::query_execute('UPDATE products_stock_control SET stock_name = :stock_name WHERE stock_id = :stock_id', cardinality::NONE, $update);
			$this->skeleton->rebuild('header');

			$this->create_change_history_record('Stock Name', $header['ipn'], $stock_name);

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_target_min_qty($target_min_qty) {
		$header = $this->get_header();

		self::transaction_begin();

		try {
			$update = [':target_min_qty' => $target_min_qty, ':stock_id' => $this->id()];

			self::query_execute('UPDATE products_stock_control SET target_min_qty = :target_min_qty WHERE stock_id = :stock_id', cardinality::NONE, $update);
			$this->skeleton->rebuild('header');

			$this->create_change_history_record('Target Min Qty', $header['target_min_qty'], $target_min_qty);

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_target_max_qty($target_max_qty) {
		$header = $this->get_header();

		self::transaction_begin();

		try {
			$update = [':target_max_qty' => $target_max_qty, ':stock_id' => $this->id()];

			self::query_execute('UPDATE products_stock_control SET target_max_qty = :target_max_qty WHERE stock_id = :stock_id', cardinality::NONE, $update);
			$this->skeleton->rebuild('header');

			$this->create_change_history_record('Target Max Qty', !is_null($header['target_max_qty'])?$header['target_max_qty']:'', $target_max_qty);

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_products_stock_control_category($products_stock_control_category_id) {
		$header = $this->get_header();

		self::transaction_begin();

		try {
			$update = [':products_stock_control_category_id' => $products_stock_control_category_id, ':stock_id' => $this->id()];

			self::query_execute('UPDATE products_stock_control SET products_stock_control_category_id = :products_stock_control_category_id WHERE stock_id = :stock_id', cardinality::NONE, $update);
			$this->skeleton->rebuild('header');

			$this->create_change_history_record('IPN Category', $header['ipn_category'], $this->get_header('ipn_category'));

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_warranty($warranty_id) {
		$header = $this->get_header();

		self::transaction_begin();

		try {
			$update = [':warranty_id' => $warranty_id, ':stock_id' => $this->id()];

			self::query_execute('UPDATE products_stock_control SET warranty_id = :warranty_id WHERE stock_id = :stock_id', cardinality::NONE, $update);
			$this->skeleton->rebuild('header');

			$this->create_change_history_record('Warranty Change', $header['warranty_name'], $this->get_header('warranty_name'));

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_dealer_warranty($dealer_warranty_id) {
		$header = $this->get_header();

		self::transaction_begin();

		try {
			$update = [':dealer_warranty_id' => $dealer_warranty_id, ':stock_id' => $this->id()];

			self::query_execute('UPDATE products_stock_control SET dealer_warranty_id = :dealer_warranty_id WHERE stock_id = :stock_id', cardinality::NONE, $update);
			$this->skeleton->rebuild('header');

			$this->create_change_history_record('Dealer Warranty Change', !is_null($header['dealer_warranty_name'])?$header['dealer_warranty_name']:'', !is_null($this->get_header('dealer_warranty_name'))?$this->get_header('dealer_warranty_name'):'');

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function create_vendor_relationship($data) {
		$savepoint = self::transaction_begin();

		try {
			$data['stock_id'] = $this->id();
			$params = new ezparams($data);
			self::query_execute('INSERT INTO vendors_to_stock_item ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, CK\fn::parameterize($data));
			$vendor_relationship_id = self::fetch_insert_id();

			$this->skeleton->rebuild('vendors');
			$vendor = $this->get_vendors($vendor_relationship_id);

			if (!empty($data['preferred']) || count($this->get_vendors()) == 1) $this->set_preferred_vendor($vendor_relationship_id);
			elseif (!empty($data['secondary'])) $this->set_secondary_vendor($vendor_relationship_id);

			if (!empty($data['vendors_price'])) $this->create_change_history_record('Added vendor price: '.$vendor['company_name'], '', CK\text::monetize($data['vendors_price']));
			if (!empty($data['vendors_pn'])) $this->create_change_history_record('Added vendor p/n: '.$vendor['company_name'], '', $data['vendors_pn']);
			if (!empty($data['case_qty'])) $this->create_change_history_record('Added case qty: '.$vendor['company_name'], '', $data['case_qty']);
			if (!empty($data['lead_time'])) $this->create_change_history_record('Added vendor lead time: '.$vendor['company_name'], '', $data['lead_time']);
			if (!empty($data['always_avail'])) $this->create_change_history_record('Added vendor always available: '.$vendor['company_name'], '', $data['always_avail']==1?'On':'Off');
			//if (!empty($data['secondary'])) $this->create_change_history_record('Added secondary vendor: '.$vendor['company_name'], '', $data['secondary']==1?'On':'Off');

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	public function update_vendor_relationship($vendor_relationship_id, $data) {
		$vendor = $this->get_vendors($vendor_relationship_id);

		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			$data['vendor_relationship_id'] = $vendor_relationship_id;
			self::query_execute('UPDATE vendors_to_stock_item SET '.$params->update_cols(TRUE).' WHERE id = :vendor_relationship_id', cardinality::NONE, CK\fn::parameterize($data));
			$this->skeleton->rebuild('vendors');

			if ($data['preferred'] == 1) $this->set_preferred_vendor($vendor_relationship_id);
			elseif ($data['secondary'] == 1) $this->set_secondary_vendor($vendor_relationship_id);

			$this->skeleton->rebuild('vendors');

			if (isset($data['vendors_price'])) $this->create_change_history_record('Saved vendor price: '.$vendor['company_name'], CK\text::monetize($vendor['price']), CK\text::monetize($data['vendors_price']));
			if (isset($data['vendors_pn'])) $this->create_change_history_record('Saved vendor p/n: '.$vendor['company_name'], $vendor['part_number'], $data['vendors_pn']);
			if (isset($data['case_qty'])) $this->create_change_history_record('Saved case qty: '.$vendor['company_name'], $vendor['case_qty'], $data['case_qty']);
			if (isset($data['lead_time'])) $this->create_change_history_record('Saved vendor lead time: '.$vendor['company_name'], !empty($vendor['lead_time'])?$vendor['lead_time']:'', $data['lead_time']);
			if (isset($data['always_available'])) $this->create_change_history_record('Saved vendor always available: '.$vendor['company_name'], $vendor['always_available']?'On':'Off', $data['always_avail']==1?'On':'Off');
			//if (isset($data['secondary'])) $this->create_change_history_record('Saved secondary vendor: '.$vendor['company_name'], $vendor['secondary']?'On':'Off', $data['secondary']==1?'On':'Off');

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	private function set_preferred_vendor($vendor_relationship_id) {
		$old_vendor = $this->get_vendors('preferred');

		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE vendors_to_stock_item vtsi JOIN products_stock_control psc ON vtsi.stock_id = psc.stock_id SET vtsi.preferred = CASE WHEN :vendor_relationship_id = vtsi.id THEN 1 ELSE 0 END, vtsi.secondary = CASE WHEN :vendor_relationship_id = vtsi.id THEN 0 ELSE vtsi.secondary END, psc.vendor_to_stock_item_id = :vendor_relationship_id WHERE vtsi.stock_id = :stock_id', cardinality::NONE, [':vendor_relationship_id' => $vendor_relationship_id, ':stock_id' => $this->id()]);

			$this->skeleton->rebuild('vendors');

			$new_vendor = $this->get_vendors($vendor_relationship_id);

			$this->create_change_history_record('Changed preferred Vendor for product', !empty($old_vendor)?$old_vendor['company_name']:'', $new_vendor['company_name']);

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	private function set_secondary_vendor($vendor_relationship_id) {
		$old_vendor = $this->get_vendors('secondary');

		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE vendors_to_stock_item vtsi JOIN products_stock_control psc ON vtsi.stock_id = psc.stock_id SET vtsi.secondary = CASE WHEN :vendor_relationship_id = vtsi.id THEN 1 ELSE 0 END, vtsi.preferred = CASE WHEN :vendor_relationship_id = vtsi.id THEN 0 ELSE vtsi.preferred END WHERE vtsi.stock_id = :stock_id', cardinality::NONE, [':vendor_relationship_id' => $vendor_relationship_id, ':stock_id' => $this->id()]);

			$this->skeleton->rebuild('vendors');

			$new_vendor = $this->get_vendors($vendor_relationship_id);

			$this->create_change_history_record('Changed secondary Vendor for product', !empty($old_vendor)?$old_vendor['company_name']:'', $new_vendor['company_name']);

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	public function delete_vendor_relationship($vendor_relationship_id) {
		$vendor = $this->get_vendors($vendor_relationship_id);
		self::transaction_begin();

		try {
			self::query_execute('DELETE FROM vendors_to_stock_item WHERE id = :vendor_relationship_id AND stock_id = :stock_id', cardinality::NONE, [':vendor_relationship_id' => $vendor_relationship_id, ':stock_id' => $this->id()]);
			$this->create_ipn_history_record('Deleted Vendor Stock Item Row', NULL, NULL);
			$this->create_change_history_record('Deleted vendor for product', $vendor['company_name'], '');

			$this->skeleton->rebuild('vendors');

			self::transaction_commit();
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function create_upc($data) {
		$savepoint = self::transaction_begin();

		try {
			$data['stock_id'] = $this->id();
			if (empty($data['upc'])) {
				unset($data['upc']);
				$params = new ezparams($data);
				self::query_execute('INSERT INTO ck_upc_assignments (upc, '.$params->insert_cols().') SELECT ou.upc, '.$params->insert_params(TRUE).' FROM ck_owned_upcs ou WHERE upc_assignment_id IS NULL LIMIT 1', cardinality::NONE, CK\fn::parameterize($data));
				$upc_assignment_id = self::fetch_insert_id();
				self::query_execute('UPDATE ck_owned_upcs ou JOIN ck_upc_assignments ua ON ou.upc = ua.upc SET ou.upc_assignment_id = ua.upc_assignment_id WHERE ua.stock_id = :stock_id AND ua.upc_assignment_id = :upc_assignment_id', cardinality::NONE, [':stock_id' => $this->id(), ':upc_assignment_id' => $upc_assignment_id]);
			}
			else {
				$params = new ezparams($data);
				self::query_execute('INSERT INTO ck_upc_assignments ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', cardinality::NONE, CK\fn::parameterize($data));
				$upc_assignment_id = self::fetch_insert_id();
			}

			$this->skeleton->rebuild('upcs');
			$upc = $this->get_upcs($upc_assignment_id);

			$this->create_change_history_record('Add UPC Assignment', '', $upc['upc'].(!empty($upc['purpose'])?' ['.$upc['purpose'].']':''));

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	public function update_upc($upc_assignment_id, $data) {
		$old_upc = $this->get_upcs($upc_assignment_id);

		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			$data['upc_assignment_id'] = $upc_assignment_id;
			self::query_execute('UPDATE ck_upc_assignments SET '.$params->update_cols(TRUE).' WHERE upc_assignment_id = :upc_assignment_id', cardinality::NONE, CK\fn::parameterize($data));
			$this->skeleton->rebuild('upcs');

			$upc = $this->get_upcs($upc_assignment_id);

			$this->create_change_history_record('Update UPC Assignment', $old_upc['upc'].(!empty($old_upc['purpose'])?' ['.$old_upc['purpose'].']':''), $upc['upc'].(!empty($upc['purpose'])?' ['.$upc['purpose'].']':''));

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
	}

	public function remove_upc($upc_assignment_id) {
		$upc = $this->get_upcs($upc_assignment_id);
		$savepoint_id = self::transaction_begin();

		try {
			self::query_execute('UPDATE ck_upc_assignments SET active = 0 WHERE upc_assignment_id = :upc_assignment_id AND stock_id = :stock_id', cardinality::NONE, [':upc_assignment_id' => $upc_assignment_id, ':stock_id' => $this->id()]);
			$this->create_ipn_history_record('Removed UPC Assignment', NULL, NULL);
			$this->create_change_history_record('Removed UPC Assignment', $upc['upc'].(!empty($upc['purpose'])?' ['.$upc['purpose'].']':''), '');

			$this->skeleton->rebuild('upcs');

			self::transaction_commit($savepoint_id);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw $e;
		}
	}

	// wrapper methods - these will be implemented in full some day, or superceded, but for now just wrap them so internally we can call them correctly
	public function create_change_history_record($label, $old_value, $new_value) {
		require_once(__DIR__.'/../functions/inventory_functions.php');
		if (is_null($old_value)) $old_value = '';
		if (is_null($new_value)) $new_value = '';
		if (!empty(self::$ipn_import_id)) {
			$label = 'IPN Import - '.$label;
			insert_psc_change_history($this->id(), $label, $old_value, $new_value, self::$ipn_import_id);
		}
		else insert_psc_change_history($this->id(), $label, $old_value, $new_value);
	}

	public function create_ipn_history_record($label, $status, $quantity, $pop_id=NULL) {
		if (empty($pop_id)) $pop_id = $this->id();

		$data = [];
		$data['type'] = $label;
		$data['record_id'] = $pop_id;
		$data['admin_id'] = $_SESSION['login_id'];
		$data['change_date'] = prepared_expression::NOW();
		$data['status'] = !empty($status)?$status:'';
		$data['stock_id'] = $this->id();
		$data['qty'] = !empty($quantity)?$quantity:'';

		$insert = new prepared_fields($data, prepared_fields::INSERT_QUERY);

		prepared_query::execute('INSERT INTO ipn_change_history ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->insert_parameters());
	}

	private function create_inventory_adjustment_record($type, $reason, $old_qty, $new_qty) {
		require_once(__DIR__.'/../functions/inventory_functions.php');
		insert_inventory_adjustment($this->id(), $type, $old_qty, $new_qty, $reason);
	}

	private function check_and_remove_warehouse_allocations() {
		po_alloc_check_and_remove_warehouse_by_ipn($this->id());
	}

	private function update_channel_advisor_qty($delta=NULL) { // $delta is deprecated - will remove in future update
		//we are decreasing quantity - tell channel advisor
		$ca = new api_channel_advisor;
		if ($ca::is_authorized()) {
			$ca->update_quantity($this);
		}
	}

	public function update_local_channel_advisor_allocated_qty($qty_delta) {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE products_stock_control SET ca_allocated_quantity = ca_allocated_quantity + :qty_delta WHERE stock_id = :stock_id', cardinality::NONE, [':qty_delta' => $qty_delta, ':stock_id' => $this->id()]);

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKIpnException('Error updating local Channel Advisor allocated qty: '.$e->getMessage());
		}
	}

	public function set_primary_merchandising_container($container_type_id, $container_id, $canonical, $redirect) {
		$savepoint = self::transaction_begin();

		try {
			$this->remove_primary_merchandising_container();

			self::query_execute('INSERT INTO ck_merchandising_primary_containers (stock_id, container_type_id, container_id, canonical, redirect) VALUES (:stock_id, :container_type_id, :container_id, :canonical, :redirect)', cardinality::NONE, [':stock_id' => $this->id(), ':container_type_id' => $container_type_id, ':container_id' => $container_id, ':canonical' => $canonical, ':redirect' => $redirect]);

			self::transaction_commit($savepoint);
		}
		catch (CKIpnException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKIpnException('Error updating local Channel Advisor allocated qty: '.$e->getMessage());
		}
	}

	public function remove_primary_merchandising_container() {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('DELETE FROM ck_merchandising_primary_containers WHERE stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKIpnException('Error deleting previous primary merchandising container: '.$e->getMessage());
		}
	}

	public static function reset_local_channel_advisor_allocated_qty() {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE products_stock_control SET ca_allocated_quantity = 0');

			self::transaction_commit($savepoint);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKIpnException('Error resetting local Channel Advisor allocated qtys: '.$e->getMessage());
		}
	}

	public function rebuild_inventory_data() {
		$this->skeleton->rebuild('inventory');
	}

	public function turn_off_discontinued() {
		if ($this->is('discontinued')) {
			$savepoint = self::transaction_begin();
			try {
				self::query_execute('UPDATE products SET products_status = 0 WHERE stock_id = :stock_id', cardinality::NONE, [':stock_id' => $this->id()]);
				self::transaction_commit($savepoint);
			}
			catch (Exception $e) {
				self::transaction_rollback($savepoint);
				throw new CKIpnException('Error turning off the discontinued product: '.$e->getMessage());
			}
		}
	}

	public function mark_creation_reviewed() {
		$savepoint = self::transaction_begin();
		try {
			$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);
			self::query_execute('UPDATE products_stock_control SET creation_reviewed = 1, creation_reviewer = :creation_reviewer, creation_reviewed_date = NOW() WHERE stock_id = :stock_id', cardinality::NONE, [':creation_reviewer' => $user->id(), ':stock_id' => $this->id()]);
			self::transaction_commit($savepoint);
		}
		catch (CKIpnException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKIpnException('Error marking ipn creation as reviewed: '.$e->getMessage());
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/

	public function send_in_stock_notification() {
		$notifications = self::query_fetch('SELECT * FROM stock_notification sn JOIN products p ON sn.product_id = p.products_id JOIN products_description pd ON p.products_id = pd.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE p.products_quantity > 0 and psc.stock_id = :stock_id AND sn.end_date > DATE(NOW())', cardinality::SET, [':stock_id' => $this->id()]);

		foreach ($notifications as $notification) {
			$mailer = service_locator::get_mail_service();
			$mail = $mailer->create_mail();
			$mail->set_from('sales@cablesandkits.com');
			$mail->set_subject($notifications['products_name'].' is back in stock at CablesandKits.com!');
			$mail->add_to($notifications['email']);

			$msg = "Hi,\n";
			$msg .= "Our records indicate that you asked to be notified when the ".$notification['products_name']." (".$notifications['products_model'].") was back in stock.\n";
			$msg .= "We are pleased to let you know that this item is now in stock in our warehouse and available for purchase. The link below will take you directly to this item.\n";
			$msg .= "https://www.cablesandkits.com/product_info.php?products_id=".$notifications['products_id']."\n";
			$msg .= "As always, we would like to thank you for shopping at CablesAndKits.com and invite you to call or email us with any questions.\n";
			$msg .= "\n";
			$msg .= "Thanks!\n";
			$msg .= "CK\n";
			$msg .= "CablesAndKits.com\n";
			$msg .= "sales@cablesandkits.com\n";
			$msg .= "Ph. ".ck_admin::$toll_free_sales_phone."\n";
			$msg .= "Fx. ".ck_admin::$local_sales_phone."\n";

			$mail->set_body(NULL, $msg);

			$mailer->send($mail);

			self::query_execute('UPDATE stock_notification SET last_notified = NOW() WHERE notification_id = :notification_id', cardinality::NONE, [':notification_id' => $notifications['notification_id']]);
		}
	}

	public static function is_valid_stock_id($stock_id) {
		$result = FALSE;
		if (is_numeric($stock_id)) $result = self::query_fetch('SELECT stock_id FROM products_stock_control WHERE stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $stock_id]);
		if ($result) return TRUE;
		return FALSE;
	}

	public static function is_valid_stock_name($stock_name) {
		$result = self::query_fetch('SELECT stock_id FROM products_stock_control WHERE stock_name = :stock_name', cardinality::SINGLE, [':stock_name' => $stock_name]);
		if ($result) return TRUE;
		return FALSE;
	}
}

class CKIpnException extends Exception {
}
?>
