-------------------------------
-- √
-- ckv_ups_worldship {
	CREATE OR REPLACE ALGORITHM=MERGE SQL SECURITY DEFINER VIEW ckv_ups_worldship AS
	SELECT
		o.orders_id as orders_id,
		o.customers_id as customers_id,
		CASE WHEN o.dropship = 1 THEN o.customers_name ELSE 'Shipping Dept' END as ship_from_name,
		CASE WHEN o.dropship = 1 THEN (CASE o.customers_company WHEN NULL THEN '.' WHEN '' THEN '.' WHEN ' ' THEN '.' ELSE o.customers_company END) ELSE 'CablesAndKits' END as ship_from_company,
		CASE WHEN o.dropship = 1 THEN o.customers_street_address ELSE '4555 Atwater Ct' END as ship_from_address1,
		CASE WHEN o.dropship = 1 THEN o.customers_suburb ELSE 'Suite A' END as ship_from_address2,
		CASE WHEN o.dropship = 1 THEN o.customers_city ELSE 'Buford' END as ship_from_city,
		CASE WHEN o.dropship = 1 THEN o.customers_postcode ELSE '30518' END as ship_from_postcode,
		CASE WHEN o.dropship = 1 THEN o.customers_state ELSE 'GA' END as ship_from_state,
		CASE WHEN o.dropship = 1 THEN o.customers_country ELSE 'USA' END as ship_from_country,
		CASE WHEN o.dropship = 1 THEN o.customers_telephone ELSE '888-622-0223' END as ship_from_telephone,
		CASE WHEN o.dropship = 1 THEN o.customers_email_address ELSE 'sales@cablesandkits.com' END as ship_from_email_address,
		CASE WHEN ISNULL(o.delivery_company) OR o.delivery_company = '' THEN NULL ELSE o.delivery_name END as ship_to_name,
		CASE WHEN ISNULL(o.delivery_company) OR o.delivery_company = '' THEN o.delivery_name ELSE o.delivery_company END as ship_to_company,
		o.delivery_street_address as ship_to_address1,
		o.delivery_suburb as ship_to_address2,
		o.delivery_city as ship_to_city,
		o.delivery_postcode as ship_to_postcode,
		o.delivery_state as ship_to_state,
		o.delivery_country as ship_to_country,
		o.delivery_telephone as ship_to_telephone,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN 'TP' WHEN o.fedex_bill_type IN (2, 5) THEN 'REC' ELSE 'SHP' END as bill_to,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_ups ELSE NULL END as third_party_sender_ups_account_number,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_name ELSE NULL END as third_party_sender_name,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_company ELSE NULL END as third_party_sender_company,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_street_address ELSE NULL END as third_party_sender_address1,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_suburb ELSE NULL END as third_party_sender_address2,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_city ELSE NULL END as third_party_sender_city,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_postcode ELSE NULL END as third_party_sender_postcode,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_state ELSE NULL END as third_party_sender_state,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_country ELSE NULL END as third_party_sender_country,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_telephone ELSE NULL END as third_party_sender_telephone,
		CASE WHEN o.fedex_bill_type = 3 OR (o.fedex_bill_type IN (2, 5) AND o.dropship = 1 AND o.customers_ups = c.customers_ups) THEN o.customers_email_address ELSE NULL END as third_party_sender_email_address,
		CASE WHEN o.fedex_bill_type IN (2, 5) AND (o.dropship = 0 OR o.customers_ups != c.customers_ups) THEN o.customers_ups ELSE NULL END as receiver_ups_account_number,
		o.fedex_tracking as fedex_tracking,
		o.customers_fedex as customers_fedex,
		o.customers_ups as customers_ups,
		o.dropship as dropship,
		o.purchase_order_number as reference_number_1,
		CASE WHEN o.dropship = 1 THEN NULL ELSE o.orders_id END as reference_number_2,
		o.orders_weight as orders_weight,
		sm.worldship_code as service_type,
		CASE WHEN sm.saturday_delivery = 1 THEN 'Y' ELSE 'N' END as saturday_delivery
	FROM
		orders o
			 JOIN customers c ON o.customers_id = c.customers_id
			 JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_shipping'
			 JOIN shipping_methods sm ON ot.external_id = sm.shipping_code AND sm.worldship_code IS NOT NULL;
-- }

-------------------------------
-- √
-- ckv_ups_worldship_packages {
	CREATE OR REPLACE ALGORITHM=MERGE SQL SECURITY DEFINER VIEW ckv_ups_worldship_packages AS
	SELECT
		op.orders_id as orders_id,
		op.orders_packages_id as orders_packages_id,
		'CP' as package_type,
		op.scale_weight as weight,
		NULL as tracking_number,
		CASE WHEN op.package_type_id IN (1, 2, 1020) THEN op.order_package_length ELSE pt.package_length END as package_length,
		CASE WHEN op.package_type_id IN (1, 2, 1020) THEN op.order_package_width ELSE pt.package_width END as package_width,
		CASE WHEN op.package_type_id IN (1, 2, 1020) THEN op.order_package_height ELSE pt.package_height END as package_height,
		o.purchase_order_number as reference_number_1,
		CASE WHEN o.dropship = 1 THEN NULL ELSE o.orders_id END as reference_number_2,
		op.orders_packages_id as reference_number_3
	FROM
		orders_packages op
			 JOIN orders o ON op.orders_id = o.orders_id
			 LEFT JOIN package_type pt ON op.package_type_id = pt.package_type_id
	WHERE
		op.package_type_id != 999 AND
		op.void = 0;
-- }

-------------------------------
-- √
-- ckv_ups_worldship_returns {
	CREATE OR REPLACE ALGORITHM=MERGE SQL SECURITY DEFINER VIEW ckv_ups_worldship_returns AS
	SELECT
		r.id as rma_id,
		r.order_id as orders_id,
		r.closed as closed,
		r.disposition as disposition,
		r.replacement_order_id as replacement_order_id,
		r.fedex_tracking_number as fedex_tracking_number,
		r.fedex_account_number as fedex_account_number,
		1 as total_weight,
		CONCAT('Attn: RMA # ', r.id) as ship_to_name,
		'CablesAndKits.com' as ship_to_company,
		'4555 Atwater Ct' as ship_to_address1,
		'Suite A' as ship_to_address2,
		'Buford' as ship_to_city,
		'30518' as ship_to_postcode,
		'GA' as ship_to_state,
		'USA' as ship_to_country,
		'888-622-0223' as ship_to_telephone,
		'sales@cablesandkits.com' as ship_to_email_address,
		CASE WHEN ISNULL(o.delivery_company) OR o.delivery_company = '' THEN NULL ELSE o.delivery_name END as ship_from_name,
		CASE WHEN ISNULL(o.delivery_company) OR o.delivery_company = '' THEN o.delivery_name ELSE o.delivery_company END as ship_from_company,
		o.delivery_street_address as ship_from_address1,
		o.delivery_suburb as ship_from_address2,
		o.delivery_city as ship_from_city,
		o.delivery_postcode as ship_from_postcode,
		o.delivery_state as ship_from_state,
		o.delivery_country as ship_from_country,
		o.delivery_telephone as ship_from_telephone,
		'REC' as bill_to,
		'724TT9' as receiver_ups_account_number,
		r.id as reference_number_1
	FROM
		rma r
			 JOIN orders o ON r.order_id = o.orders_id;
-- }

-------------------------------



-------------------------------
-- √
-- ckv_group_connect {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_group_connect AS
	SELECT DISTINCT
		pscv.id as vertical_id,
		pscv.name as vertical,
		pscc.categories_id as categories_id,
		pscc.name as category,
		ps.product_series_id as product_series_id,
		IFNULL(ps.series_name, '') as series
	FROM
		products_stock_control_verticals pscv
			JOIN products_stock_control_categories pscc ON pscv.id = pscc.vertical_id
			LEFT JOIN products_stock_control psc ON pscc.categories_id = psc.products_stock_control_category_id
			LEFT JOIN ck_product_series ps ON psc.product_series_id = ps.product_series_id
	ORDER BY
		pscv.name,
		pscc.name,
		ps.series_name;
-- }

-------------------------------



-------------------------------
-- √
-- ckv_latest_serials_history {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_latest_serials_history as
	SELECT
		sh.id,
		sh.serial_id,
		sh.entered_date,
		sh.shipped_date,
		sh.conditions,
		sh.order_id,
		sh.order_product_id,
		sh.po_number,
		sh.pors_id,
		sh.pop_id,
		sh.porp_id,
		sh.dram,
		sh.flash,
		sh.mac_address,
		sh.image,
		sh.ios,
		sh.version,
		sh.cost,
		sh.transfer_price,
		sh.transfer_date,
		sh.show_version,
		sh.short_notes,
		sh.bin_location,
		sh.confirmation_date,
		sh.rma_id,
		sh.tester_admin_id
	FROM
		serials_history sh
			LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id
	WHERE
		sh0.id IS NULL;
-- }

-------------------------------
-- √
-- ckv_latest_sales_invoice {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_latest_sales_invoice as
	SELECT
		i.invoice_id,
		i.customer_id,
		i.inv_order_id,
		i.rma_id,
		i.inv_date,
		i.po_number,
		i.invoice_rma,
		i.paid_in_full,
		i.customers_extra_logins_id,
		i.credit_payment_id,
		i.credit_memo,
		i.original_invoice,
		i.late_notice_date,
		i.sent,
		i.sales_incentive_tier_id,
		i.incentive_percentage,
		i.incentive_base_total,
		i.incentive_product_total,
		i.incentive_final_total,
		i.incentive_accrued,
		i.incentive_paid,
		i.incentive_override_percentage,
		i.incentive_override_date,
		i.incentive_override_note
	FROM
		acc_invoices i
			LEFT JOIN acc_invoices i0 ON i.inv_order_id = i0.inv_order_id AND i.invoice_id < i0.invoice_id
	WHERE
		i.inv_order_id IS NOT NULL AND
		i0.invoice_id IS NULL;
-- }

-------------------------------
-- √
-- ckv_latest_ship_date {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_latest_ship_date as
	SELECT
		orders_id,
		MAX(date_added) as ship_date
	FROM
		orders_status_history USE INDEX (orders_status_date)
	WHERE
		orders_status_id = 3
	GROUP BY
		orders_id;
-- }

-------------------------------
-- √
-- ckv_marketplace_demand {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_marketplace_demand AS
	SELECT
		mop.stock_id as stock_id,
		SUM(mop.quantity) as marketplace_demand
	FROM
		marketplace_order_products mop
	GROUP BY
		mop.stock_id;
-- }

-------------------------------
-- √
-- ckv_ecom_demand {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_ecom_demand AS
	SELECT
		p.stock_id as stock_id,
		SUM(op.products_quantity) as ecom_demand
	FROM
		orders o
			JOIN orders_products op ON o.orders_id = op.orders_id
			JOIN products p ON op.products_id = p.products_id
	WHERE
		o.orders_status NOT IN (3, 6, 9)
	GROUP BY
		p.stock_id;
-- }

-------------------------------
-- √
-- ckv_alloc {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_alloc as
	SELECT
		opa.stock_id as stock_id,
		SUM(opau.quantity) as allocated,
		SUM(IF(opa.disposition_id IS NOT NULL, opau.quantity, 0)) as allocated_from_stock,
		SUM(IF(opa.purchase_order_product_id IS NOT NULL, opau.quantity, 0)) as allocated_from_expected
	FROM
		order_product_allocations opa
			JOIN order_product_allocation_units opau ON opa.order_product_allocation_id = opau.order_product_allocation_id
	WHERE
		opa.fulfilled = 0
	GROUP BY
		opa.stock_id;
-- }

-------------------------------
-- √
-- ckv_dispo {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_dispo as
	SELECT
		d.stock_id as stock_id,
		SUM(du.quantity) as on_hand,
		SUM(IF(d.hold = 1, du.quantity, 0)) as on_hold,
		SUM(IF(d.hold = 0, du.quantity, 0)) as salable,
		SUM(IF(ihr.in_process = 1, du.quantity, 0)) as in_conditioning
	FROM
		inventory_dispositions d
			JOIN inventory_disposition_units du ON d.disposition_id = du.disposition_id
			LEFT JOIN inventory_hold_reason ihr ON d.hold_reason_id = ihr.id
	GROUP BY
		d.stock_id;
-- }

-------------------------------
-- √
-- ckv_usage_snapshot {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_usage_snapshot as
	SELECT
		stock_id,
		to180 as units_last_180_days,
		p3060 as units_30_to_60_days,
		to30 as units_last_30_days,
		CASE
			WHEN IFNULL(to180, 0)/180 >= LEAST(IFNULL(p3060, 0)/30, IFNULL(to30, 0)/30) AND IFNULL(to180, 0)/180 <= GREATEST(IFNULL(to30, 0)/30, IFNULL(p3060, 0)/30) THEN IFNULL(to180, 0)/180
			WHEN IFNULL(p3060, 0)/30 >= LEAST(IFNULL(to180, 0)/180, IFNULL(to30, 0)/30) AND IFNULL(p3060, 0)/30 <= GREATEST(IFNULL(to30, 0)/30, IFNULL(to180, 0)/180) THEN IFNULL(p3060, 0)/30
			WHEN IFNULL(to30, 0)/30 >= LEAST(IFNULL(p3060, 0)/30, IFNULL(to180, 0)/180) AND IFNULL(to30, 0)/30 <= GREATEST(IFNULL(to180, 0)/180, IFNULL(p3060, 0)/30) THEN IFNULL(to30, 0)/30
			ELSE 0
		END as daily_runrate
	FROM
		ck_cache_sales_history;
-- }

-------------------------------
-- √
-- ckv_dispo_value {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_dispo_value as
	SELECT
		d.stock_id as stock_id,
		SUM(du.quantity) as on_hand,
		SUM(IF(d.hold = 1, du.quantity, 0)) as on_hold,
		SUM(IF(d.hold = 0, du.quantity, 0)) as salable,
		SUM(IF(ihr.in_process = 1, du.quantity, 0)) as in_conditioning,
		psc.serialized,
		IF(psc.serialized = 1, SUM(sh.cost), SUM(du.quantity) * psc.average_cost) as on_hand_value,
		IF(psc.serialized = 1, SUM(IF(d.hold = 1, sh.cost, 0)), SUM(IF(d.hold = 1, du.quantity, 0)) * psc.average_cost) as on_hold_value,
		IF(psc.serialized = 1, SUM(IF(d.hold = 0, sh.cost, 0)), SUM(IF(d.hold = 0, du.quantity, 0)) * psc.average_cost) as salable_value,
		IF(psc.serialized = 1, SUM(IF(ihr.in_process = 1, sh.cost, 0)), SUM(IF(ihr.in_process = 1, du.quantity, 0)) * psc.average_cost) as in_conditioning_value,
		IF(psc.serialized = 1, SUM(sh.cost) / SUM(du.quantity), psc.average_cost) as average_unit_cost
	FROM
		inventory_dispositions d
			JOIN inventory_disposition_units du ON d.disposition_id = du.disposition_id
			JOIN products_stock_control psc ON d.stock_id = psc.stock_id
			LEFT JOIN ckv_latest_serials_history sh ON du.serial_id = sh.serial_id
			LEFT JOIN inventory_hold_reason ihr ON d.hold_reason_id = ihr.id
	GROUP BY
		d.stock_id;
-- }

-------------------------------
-- √
-- ckv_open_order_lines {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_open_order_lines AS
	SELECT
		op.orders_id,
		o.orders_status,
		op.orders_products_id,
		p.stock_id,
		op.products_quantity as order_quantity,
		IFNULL(SUM(opau.quantity), 0) as allocated_quantity,
		IFNULL(SUM(IF(opa.disposition_id IS NOT NULL, opau.quantity, 0)), 0) as allocated_from_stock,
		IFNULL(SUM(IF(opa.purchase_order_product_id IS NOT NULL, opau.quantity, 0)), 0) as allocated_from_expected,
		op.products_quantity - IFNULL(SUM(opau.quantity), 0) as unallocated_quantity
	FROM
		orders o
			JOIN orders_products op ON o.orders_id = op.orders_id
			JOIN products p ON op.products_id = p.products_id
			LEFT JOIN order_product_allocations opa ON op.orders_products_id = opa.orders_products_id AND opa.fulfilled = 0
			LEFT JOIN order_product_allocation_units opau ON opa.order_product_allocation_id = opau.order_product_allocation_id
	WHERE
		o.orders_status NOT IN (3, 6, 9)
	GROUP BY
		op.orders_products_id;
-- }

-------------------------------
-- √
-- ckv_open_po_lines {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_open_po_lines AS
	SELECT
		pop.purchase_order_id,
		po.status,
		pop.id as purchase_order_product_id,
		pop.ipn_id as stock_id,
		pop.quantity as order_quantity,
		IFNULL(SUM(porp.quantity_received), 0) as received_quantity,
		pop.quantity - IFNULL(SUM(porp.quantity_received), 0) as unreceived_quantity
	FROM
		purchase_orders po
			JOIN purchase_order_products pop ON po.id = pop.purchase_order_id
			LEFT JOIN purchase_order_received_products porp ON pop.id = porp.purchase_order_product_id
	WHERE
		po.status in (1, 2)
	GROUP BY
		pop.id
	HAVING
		unreceived_quantity > 0;
-- }

-------------------------------
-- √
-- ckv_available_po_lines {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_available_po_lines AS
	SELECT
		opl.purchase_order_id,
		opl.status,
		opl.purchase_order_product_id,
		opl.stock_id,
		opl.order_quantity,
		opl.received_quantity,
		opl.unreceived_quantity,
		IFNULL(SUM(opau.quantity), 0) as allocated_quantity,
		GREATEST(opl.unreceived_quantity - IFNULL(SUM(opau.quantity), 0), 0) as available_quantity
	FROM
		ckv_open_po_lines opl
			LEFT JOIN order_product_allocations opa ON opl.purchase_order_product_id = opa.purchase_order_product_id AND opa.fulfilled = 0
			LEFT JOIN order_product_allocation_units opau ON opa.order_product_allocation_id = opau.order_product_allocation_id
	GROUP BY
		opl.purchase_order_product_id;
-- }

-------------------------------
-- √
-- ckv_on_purchase_orders {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_on_purchase_orders AS
	SELECT
		stock_id,
		SUM(unreceived_quantity) as on_order
	FROM
		ckv_open_po_lines
	GROUP BY
		stock_id;
-- }

-------------------------------
-- √
-- ckv_inventory {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_inventory AS
	SELECT
		psc.stock_id,
		IFNULL(ckv_dispo.on_hand, 0) as on_hand,
		IFNULL(ckv_dispo.on_hold, 0) as on_hold,
		IFNULL(ckv_dispo.salable, 0) as salable,
		IFNULL(ckv_dispo.in_conditioning, 0) as in_conditioning,
		IFNULL(ckv_alloc.allocated, 0) as allocated,
		IFNULL(ckv_alloc.allocated_from_stock, 0) as allocated_from_stock,
		IFNULL(ckv_alloc.allocated_from_expected, 0) as allocated_from_expected,
		IFNULL(mp.marketplace_demand, 0) as marketplace_demand,
		IFNULL(ec.ecom_demand, 0) as ecom_demand,
		IFNULL(mp.marketplace_demand, 0) + IFNULL(ec.ecom_demand, 0) as demand,
		IFNULL(po.on_order, 0) as on_order,
		IFNULL(po.on_order, 0) - IFNULL(ckv_alloc.allocated_from_expected, 0) as on_order_available,
		IFNULL(ckv_dispo.salable, 0) - IFNULL(ckv_alloc.allocated_from_stock, 0) as available,
		IFNULL(ec.ecom_demand, 0) + IFNULL(mp.marketplace_demand, 0) - IFNULL(ckv_alloc.allocated, 0) as unmet_demand,
		psc.max_displayed_quantity
	FROM
		products_stock_control psc
			LEFT JOIN ckv_dispo ON psc.stock_id = ckv_dispo.stock_id
			LEFT JOIN ckv_alloc ON psc.stock_id = ckv_alloc.stock_id
			LEFT JOIN ckv_marketplace_demand mp ON psc.stock_id = mp.stock_id
			LEFT JOIN ckv_ecom_demand ec ON psc.stock_id = ec.stock_id
			LEFT JOIN ckv_on_purchase_orders po ON psc.stock_id = po.stock_id;
-- }

-------------------------------
-- √
-- ckv_category_product_count {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_category_product_count as
	SELECT
		categories_id,
		COUNT(products_id) as pcount
	FROM
		products_to_categories
	GROUP BY
		categories_id
	ORDER BY
		categories_id
--}

-------------------------------
-- √
-- ckv_parent_categories {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_parent_categories as
	SELECT DISTINCT
		c.parent_id as categories_id
	FROM
		categories c
-- }

-------------------------------
-- √
-- ckv_category_selector_summary {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_category_selector_summary as
	SELECT
		c.categories_id,
		cd.categories_name,
		c.parent_id,
		cpc.pcount
	FROM
		categories_description cd
			JOIN categories c ON cd.categories_id = c.categories_id
			LEFT JOIN ckv_category_product_count cpc ON c.categories_id = cpc.categories_id
			LEFT JOIN ckv_parent_categories pc ON c.categories_id = pc.categories_id
	WHERE
		cpc.categories_id IS NOT NULL OR
		pc.categories_id IS NOT NULL
	ORDER BY
		cd.categories_name,
		c.categories_id
-- }

-------------------------------
-- √
-- ckv_product_viewable {
	-- viewable states:
	-- 0 - backend only
	-- 1 - admin only
	-- 2 - globally viewable
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_product_viewable as
	SELECT
		p.products_id,
		p.stock_id,
		CASE WHEN p.products_status = 0 THEN 0 WHEN psc.dlao_product = 1 THEN 1 ELSE 2 END as viewable_state
	FROM
		products p
			JOIN products_stock_control psc ON p.stock_id = psc.stock_id
	WHERE
		p.archived = 0
	ORDER BY
		p.products_model ASC
-- }

-------------------------------
-- √
-- ckv_legacy_available_po_lines {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_legacy_available_po_lines AS
	SELECT
		opl.purchase_order_id,
		opl.status,
		opl.purchase_order_product_id,
		opl.stock_id,
		opl.order_quantity,
		opl.received_quantity,
		opl.unreceived_quantity,
		SUM(IF(o.orders_id IS NOT NULL, potoa.quantity, 0)) as allocated_quantity,
		GREATEST(opl.unreceived_quantity - SUM(IF(o.orders_id IS NOT NULL, potoa.quantity, 0)), 0) as available_quantity
	FROM
		ckv_open_po_lines opl
			LEFT JOIN purchase_order_to_order_allocations potoa ON opl.purchase_order_product_id = potoa.purchase_order_product_id
			LEFT JOIN orders_products op ON potoa.order_product_id = op.orders_products_id
			LEFT JOIN orders o ON op.orders_id = o.orders_id AND o.orders_status NOT IN (3, 6, 9)
	GROUP BY
		opl.purchase_order_product_id;
-- }

-------------------------------
-- √
-- ckv_legacy_simple_inventory {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_legacy_simple_inventory as
	SELECT
		psc.stock_id,
		CASE WHEN psc.serialized = 1 THEN IFNULL(COUNT(s.id), 0) ELSE psc.stock_quantity END as on_hand,
		psc.ca_allocated_quantity as ca_allocated,
		psc.max_displayed_quantity,
		NULL as adjusted_available_quantity
	FROM
		products_stock_control psc
			LEFT JOIN serials s ON psc.stock_id = s.ipn AND s.status IN (2, 3, 6)
	GROUP BY
		psc.stock_id;
-- }

-------------------------------
-- √
-- ckv_legacy_order_allocations {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_legacy_order_allocations as
	SELECT
		p.stock_id,
		SUM(op.products_quantity) as allocated
	FROM
		orders o
			JOIN orders_products op ON o.orders_id = op.orders_id
			JOIN products p ON op.products_id = p.products_id
	WHERE
		o.orders_status NOT IN (3, 6, 9)
	GROUP BY
		p.stock_id;
-- }

-------------------------------
-- √
-- ckv_legacy_po_allocations {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_legacy_po_allocations AS
	SELECT
		stock_id,
		SUM(unreceived_quantity) as on_order,
		SUM(allocated_quantity) as allocated_quantity,
		SUM(available_quantity) as available_quantity
	FROM
		ckv_legacy_available_po_lines
	GROUP BY
		stock_id;
-- }

-------------------------------
-- √
-- ckv_legacy_hold_summary {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_legacy_hold_summary AS
	SELECT
		ih.stock_id,
		SUM(ih.quantity) as on_hold,
		SUM(IF(ih.reason_id IN (4, 8, 11, 12), ih.quantity, 0)) as in_conditioning
	FROM
		inventory_hold ih
			LEFT JOIN serials s ON ih.serial_id = s.id
	WHERE
		s.id IS NULL OR
		s.status = 6
	GROUP BY
		ih.stock_id;
-- }

-------------------------------
-- √
-- ckv_legacy_inventory {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_legacy_inventory as
	SELECT
		lsi.stock_id,
		lsi.on_hand,
		lsi.ca_allocated,
		lsi.max_displayed_quantity,
		lsi.adjusted_available_quantity,
		IFNULL(loa.allocated, 0) - IFNULL(lpa.allocated_quantity, 0) as local_allocated,
		IFNULL(loa.allocated, 0) - IFNULL(lpa.allocated_quantity, 0) + lsi.ca_allocated as allocated,
		IFNULL(lpa.allocated_quantity, 0) as po_allocated,
		IFNULL(lhs.on_hold, 0) as on_hold,
		lsi.on_hand - (IFNULL(loa.allocated, 0) - IFNULL(lpa.allocated_quantity, 0) + lsi.ca_allocated) - IFNULL(lhs.on_hold, 0) as available,
		lsi.on_hand - IFNULL(lhs.on_hold, 0) as salable,
		IFNULL(lhs.in_conditioning, 0) as in_conditioning,
		IFNULL(lpa.on_order, 0) as on_order,
		IFNULL(lpa.available_quantity, 0) as adjusted_on_order
	FROM
		ckv_legacy_simple_inventory lsi
			LEFT JOIN ckv_legacy_po_allocations lpa ON lsi.stock_id = lpa.stock_id
			LEFT JOIN ckv_legacy_hold_summary lhs ON lsi.stock_id = lhs.stock_id
			LEFT JOIN ckv_legacy_order_allocations loa ON lsi.stock_id = loa.stock_id;
-- }

-------------------------------
-- √
-- ckv_customer_summary {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_customer_summary as
	SELECT
		o.customers_id,
		ci.customers_info_date_account_created as account_creation_date,
		MIN(o.orders_id) as first_order_id,
		MIN(o.date_purchased) as first_order_booked_date,
		MAX(o.orders_id) as last_order_id,
		MAX(o.date_purchased) as last_order_booked_date,
		SUM(IF(o.orders_status = 3, ot.value, 0)) as lifetime_order_value,
		SUM(IF(o.orders_status = 3, 1, 0)) as lifetime_order_count,
		SUM(IF(o.orders_status = 3, 0, ot.value)) as pending_order_value,
		SUM(IF(o.orders_status = 3, 0, 1)) as pending_order_count
	FROM
		orders o USE INDEX (customer_status_date)
			JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total'
			LEFT JOIN customers_info ci ON o.customers_id = ci.customers_info_id
	WHERE
		o.orders_status NOT IN (6, 9)
	GROUP BY
		o.customers_id;
-- }

-------------------------------
-- √
-- ckv_customer_previous_twelve_months_summary {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_customer_previous_twelve_months_summary as
	SELECT
		o.customers_id,
		MIN(o.date_purchased) as first_order_booked_date,
		MAX(o.date_purchased) as last_order_booked_date,
		SUM(IF(o.orders_status = 3, ot.value, 0)) as period_order_value,
		SUM(IF(o.orders_status = 3, 1, 0)) as period_order_count,
		SUM(IF(o.orders_status = 3, 0, ot.value)) as pending_order_value,
		SUM(IF(o.orders_status = 3, 0, 1)) as pending_order_count
	FROM
		orders o USE INDEX (customer_status_date)
			JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total'
	WHERE
		TO_DAYS(o.date_purchased) >= TO_DAYS(NOW()) - 365 AND
		o.orders_status NOT IN (6, 9)
	GROUP BY
		o.customers_id
-- }

-------------------------------
-- √
-- ckv_customer_makeup {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_customer_makeup as
	SELECT
		COUNT(cp12.customers_id) as unique_customer_count,
		SUM(IF(cp12.first_order_booked_date > cs.first_order_booked_date, 1, 0)) as returning_customer_count,
		SUM(IF(cp12.first_order_booked_date > cs.first_order_booked_date, 0, 1)) as new_customer_count,
		SUM(IF(cp12.first_order_booked_date > cs.first_order_booked_date, IF(cp12.period_order_count > 1, 1, 0), 0)) as returning_customer_multiple_order_count,
		SUM(IF(cp12.first_order_booked_date > cs.first_order_booked_date, 0, IF(cp12.period_order_count > 1, 1, 0))) as new_customer_multiple_order_count
	FROM
		ckv_customer_previous_twelve_months_summary cp12
			JOIN ckv_customer_summary cs ON cp12.customers_id = cs.customers_id
-- }

-------------------------------
-- √
-- ckv_average_serial_cost {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_average_serial_cost as	SELECT
		s.ipn as stock_id,
		COUNT(s.id) as on_hand,
		AVG(sh.cost) as average_cost
	FROM
		serials s
			JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id
	WHERE
		s.status IN (2, 3, 6)
	GROUP BY
		s.ipn
-- }

-------------------------------
-- √
-- ckv_invoice_accounting {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_invoice_accounting as
	SELECT
		i.invoice_id,
		i.paid_in_full,
		i.inv_date as invoice_date,
		i.incentive_accrued,
		i.incentive_paid,
		MAX(pti.credit_date) as final_payment_date,
		it.invoice_total_price as invoice_total,
		IFNULL(SUM(pti.credit_amount), 0) as invoice_payment_total,
		(it.invoice_total_price - IFNULL(SUM(pti.credit_amount), 0)) as invoice_balance
	FROM
		acc_invoices i
			LEFT JOIN acc_payments_to_invoices pti ON i.invoice_id = pti.invoice_id
			LEFT JOIN acc_invoice_totals it ON i.invoice_id = it.invoice_id AND it.invoice_total_line_type = 'ot_total'
	WHERE
		i.inv_date >= CURDATE() - INTERVAL 1 YEAR - INTERVAL 1 DAY
	GROUP BY
		i.invoice_id;
-- }

-------------------------------
-- √
-- ckv_invoice_aging {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_invoice_aging as
	SELECT
		i.invoice_id,
		c.customers_id,
		CASE
			WHEN NULLIF(ab.entry_company, '') IS NOT NULL THEN CONCAT_WS(' - ', ab.entry_company, CONCAT_WS(' ', c.customers_firstname, c.customers_lastname))
			ELSE CONCAT_WS(' ', c.customers_firstname, c.customers_lastname)
		END as customer_display_label,
		i.inv_order_id as orders_id,
		ct.terms_days,
		ct.label as terms_label,
		IFNULL(it.invoice_total_price, 0) as invoice_total,
		DATE(NOW()) as today,
		DATE(i.inv_date) as invoice_date,
		DATE(i.inv_date) + INTERVAL ct.terms_days DAY as invoice_due_date,
		DATEDIFF(NOW(), i.inv_date) as invoice_age,
		IF(DATEDIFF(NOW(), i.inv_date + INTERVAL ct.terms_days DAY) > 0, DATEDIFF(NOW(), i.inv_date + INTERVAL ct.terms_days DAY), 0) as invoice_days_late,
		IFNULL(SUM(pti.credit_amount), 0) as total_paid,
		GREATEST(IFNULL(it.invoice_total_price, 0) - IFNULL(SUM(pti.credit_amount), 0), 0) as invoice_balance
	FROM
		acc_invoices i
			JOIN customers c ON i.customer_id = c.customers_id
			LEFT JOIN address_book ab ON c.customers_default_address_id = ab.address_book_id
			LEFT JOIN customer_terms ct ON c.dealer_pay_module = ct.legacy_dealer_pay_module
			LEFT JOIN acc_invoice_totals it ON i.invoice_id = it.invoice_id AND it.invoice_total_line_type = 'ot_total'
			LEFT JOIN acc_payments_to_invoices pti ON i.invoice_id = pti.invoice_id
	WHERE
		i.inv_order_id IS NOT NULL AND
		i.paid_in_full = 0
	GROUP BY
		i.invoice_id
	HAVING
		invoice_balance > 0
	ORDER BY
		invoice_due_date ASC;
-- }

-------------------------------
-- √
-- ckv_unapplied_payments {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_unapplied_payments as
 	SELECT DISTINCT
		p.payment_id,
		p.customer_id as customers_id,
		p.payment_amount,
		p.payment_date,
		p.payment_method_id,
		pm.label as pmt_method,
		p.payment_ref,
		IFNULL(SUM(pti.credit_amount), 0) as applied_amount,
		p.payment_amount - IFNULL(SUM(pti.credit_amount), 0) as unapplied_amount,
		MAX(pti.credit_date) as last_applied_date
	FROM
		acc_payments p
			JOIN payment_method pm ON p.payment_method_id = pm.id
			LEFT JOIN acc_payments_to_invoices pti ON p.payment_id = pti.payment_id
	GROUP BY
		p.payment_id
	HAVING
		unapplied_amount > 0
	ORDER BY
		unapplied_amount DESC;
-- }

-------------------------------
-- √
-- ckv_ipn_calendar {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_ipn_calendar as
 	SELECT
		psc.stock_id,
		rc.calendar_date
	FROM
		ck_reporting_calendar rc,
		products_stock_control psc USE INDEX (PRIMARY);
-- }

-------------------------------
-- √
-- ckv_ipn_last_demand_date {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_ipn_last_demand_date as
 	SELECT
		p.stock_id,
		MAX(o.date_purchased) as demand_date
	FROM
		orders o
			JOIN orders_products op ON o.orders_id = op.orders_id
			JOIN products p ON op.products_id = p.products_id
	WHERE
		o.orders_status NOT IN (6)
	GROUP BY
		p.stock_id;
-- }

-------------------------------
-- √
-- ckv_customer_name_lookup {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_customer_name_lookup as
	SELECT DISTINCT
		c.customers_id,
		c.customers_firstname,
		c.customers_lastname,
		CONCAT_WS(' ', c.customers_firstname, c.customers_lastname) as name,
		ab.entry_company as company
	FROM
		customers c
			JOIN acc_invoices i ON c.customers_id = i.customer_id
			LEFT JOIN address_book ab ON c.customers_id = ab.customers_id AND c.customers_default_address_id = ab.address_book_id;
-- }

-------------------------------
-- √
-- ckv_order_totals_summary {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_order_totals_summary as
	SELECT
		CAST(o.date_purchased as DATE) as order_date,
		COUNT(o.orders_id) as order_count,
		SUM(IF(ISNULL(cs.first_order_id), 1, 0)) as new_order_count,
		SUM(IF((cs.first_order_id IS NOT NULL), 1, 0)) as returning_order_count,
		SUM(IF((o.payment_method_id IN (5, 6, 7, 15)), 1, 0)) as terms_order_count,
		SUM(ot.value) as order_total,
		SUM(IF(ISNULL(cs.first_order_id), ot.value, 0)) as new_order_total,
		SUM(IF((cs.first_order_id IS NOT NULL), ot.value, 0)) as returning_order_total,
		SUM(IF((o.payment_method_id IN (5, 6, 7, 15)), ot.value, 0)) as terms_order_total
	FROM
		orders o
			LEFT JOIN ckv_customer_summary cs ON o.customers_id = cs.customers_id AND o.orders_id > cs.first_order_id
			LEFT JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_total'
	WHERE
		YEAR(o.date_purchased) >= YEAR(NOW() - INTERVAL 6 MONTH) AND
		o.orders_status != 6
	GROUP BY
		CAST(o.date_purchased as DATE)
	ORDER BY
		order_date DESC;
-- }

-------------------------------
-- √
-- ckv_inventory_accounting {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_inventory_accounting as
	SELECT
		psc.stock_id,
		IF(psc.serialized = 1, IFNULL(COUNT(s.id), 0), psc.stock_quantity) as on_hand_quantity,
		IF(psc.serialized = 1, IFNULL(SUM(sh.cost), 0), psc.stock_quantity * psc.average_cost) as on_hand_value
	FROM
		products_stock_control psc
			LEFT JOIN serials s ON psc.stock_id = s.ipn AND s.status IN (2, 3, 6)
			LEFT JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id
	GROUP BY
		psc.stock_id;
-- }

-------------------------------
-- √
-- ckv_serial_owner {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_serial_owner as
	SELECT
		s.id as serial_id,
		s.ipn as stock_id,
		s.serial as serial_number,
		s.status,
		sh.id as serial_history_id,
		sh.cost,
		pop.id as purchase_order_product_id,
		po.id as purchase_order_id,
		po.owner_admin_id
	FROM
		serials s
			JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id
			LEFT JOIN purchase_order_products pop ON sh.pop_id = pop.id
			LEFT JOIN purchase_orders po ON pop.purchase_order_id = po.id
	WHERE
		s.status IN (2, 3, 6);
-- }

------------------------------
-- √
-- ckv_order_metadata {
	CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ckv_order_metadata as
	SELECT
		o.customers_id,
		o.orders_id as first_orders_id,
		o.order_count as first_order_count,
		o0.orders_id as second_orders_id,
		o0.order_count as second_order_count,
		DATEDIFF(o0.date_purchased, o.date_purchased) as daycount,
		CASE o.order_count
			WHEN 1 THEN 'first-second'
			ELSE 'second-third'
		END as period
	FROM
		(SELECT
			o.orders_id,
			CASE
				WHEN o.customers_id = @customer THEN @row := @row + 1
				ELSE @row := 1
			END as order_count,
			@customer := o.customers_id as customers_id,
			o.date_purchased,
			o.parent_orders_id,
			o.orders_status as orders_status_id,
			o.orders_sub_status as orders_sub_status_id
		FROM
			(SELECT o.orders_id, o.customers_id, o.date_purchased, o.parent_orders_id, o.orders_status, o.orders_sub_status FROM orders o WHERE o.orders_status NOT IN (6, 9) AND o.parent_orders_id IS NULL ORDER BY customers_id ASC, date_purchased ASC) o,
			(SELECT @row := 0, @customer := 0) r) o

			JOIN (SELECT
				o.orders_id,
				CASE
					WHEN o.customers_id = @customer THEN @row := @row + 1
					ELSE @row := 1
				END as order_count,
				@customer := o.customers_id as customers_id,
				o.date_purchased,
				o.parent_orders_id,
				o.orders_status as orders_status_id,
				o.orders_sub_status as orders_sub_status_id
			FROM
				(SELECT o.orders_id, o.customers_id, o.date_purchased, o.parent_orders_id, o.orders_status, o.orders_sub_status FROM orders o WHERE o.orders_status NOT IN (6, 9) AND o.parent_orders_id IS NULL ORDER BY customers_id ASC, date_purchased ASC) o,
				(SELECT @row := 0, @customer := 0) r) o0 ON o.customers_id = o0.customers_id AND o.order_count = o0.order_count - 1
	WHERE
		o.order_count < 3 AND
		o0.order_count > 1 AND
		o0.order_count < 4
-- }

-------------------------------
