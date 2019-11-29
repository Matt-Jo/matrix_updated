-- Following script will remove any row 
-- that is not strictly required for 
-- development and testing environments

DELETE FROM ck_feed_failure_tracking;
DELETE FROM ck_inventory_ledgers;
DELETE FROM ck_daily_physical_inventory_snapshot;
DELETE FROM ck_daily_recorded_inventory_snapshot;
DELETE FROM ck_daily_physical_inventory_snapshot_ranks;

DELETE rma_note
FROM orders
INNER JOIN rma ON orders.orders_id = rma.order_id
INNER JOIN rma_note ON rma_note.rma_id = rma.id
WHERE orders.last_modified < date_sub(DATE(now()), interval 12 month);

DELETE acc_invoices
FROM orders
INNER JOIN acc_invoices ON acc_invoices.rma_id = rma.id
WHERE orders.last_modified < date_sub(DATE(now()), interval 12 month);

DELETE rma_product
FROM orders
INNER JOIN rma ON orders.orders_id = rma.order_id
INNER JOIN rma_product ON rma.id = rma_product.rma_id
WHERE orders.last_modified < date_sub(DATE(now()), interval 12 month);

DELETE rma
FROM orders
INNER JOIN rma ON orders.orders_id = rma.order_id
WHERE orders.last_modified < date_sub(DATE(now()), interval 12 month);

DELETE serials_history
FROM orders
INNER JOIN serials_history ON orders.orders_id = serials_history.order_id
WHERE orders.last_modified < date_sub(DATE(now()), interval 12 month);

DELETE acc_payments_to_orders
FROM orders
INNER JOIN acc_payments_to_orders ON orders.orders_id = acc_payments_to_orders.order_id
WHERE orders.last_modified < date_sub(DATE(now()), interval 12 month);

DELETE orders
FROM orders
WHERE orders.last_modified < date_sub(DATE(now()), interval 12 month);

DELETE om
FROM orders_maxmind om
LEFT JOIN orders o ON om.order_id = o.orders_id
WHERE o.orders_id IS NULL;

DELETE ipn_calendar_temp
FROM ipn_calendar_temp
WHERE record_date < date_sub(DATE(now()), interval 12 month);

DELETE h
FROM orders_status_history h 
LEFT JOIN orders o ON h.orders_id = o.orders_id
WHERE o.orders_id IS NULL;

DELETE op
FROM orders_products op 
LEFT JOIN orders o ON op.orders_id = o.orders_id
WHERE o.orders_id IS NULL;

DELETE aii
FROM acc_invoice_items aii 
LEFT JOIN acc_invoices i ON aii.invoice_id = i.invoice_id
WHERE i.invoice_id IS NULL;

DELETE ccl
FROM credit_card_log ccl 
LEFT JOIN orders o ON ccl.order_id = o.orders_id
WHERE o.orders_id IS NULL;

DELETE ot
FROM orders_total ot 
LEFT JOIN orders o ON ot.orders_id = o.orders_id
WHERE o.orders_id IS NULL;

DELETE o2
FROM orders_notes o2 
LEFT JOIN orders o ON o2.orders_id = o.orders_id
WHERE o.orders_id IS NULL;

DELETE ait
FROM acc_invoice_totals ait 
LEFT JOIN acc_invoices i ON ait.invoice_id = i.invoice_id
WHERE i.invoice_id IS NULL;

DELETE ath
FROM acc_transaction_history ath
LEFT JOIN orders o ON ath.order_id = o.orders_id
WHERE o.orders_id IS NULL;

DELETE psh
FROM products_stock_control_change_history psh
WHERE psh.change_date < date_sub(DATE(now()), interval 12 month);

DELETE FROM ck_debug_labels;

DELETE o2
FROM orders_packages o2 
LEFT JOIN orders o ON o2.orders_id = o.orders_id
WHERE o.orders_id IS NULL;

DELETE o2
FROM serials_history o2 
LEFT JOIN orders o ON o2.order_id = o.orders_id
WHERE o.orders_id IS NULL;


DELETE FROM ck_error_log;

DELETE sq
FROM search_queries sq 
WHERE sq.date_searched < date_sub(DATE(now()), interval 12 month);

DELETE p, apto 
FROM acc_payments p
INNER JOIN acc_payments_to_orders apto ON p.payment_id = apto.payment_id
WHERE p.payment_date < date_sub(DATE(now()), interval 12 month);

DELETE p 
FROM acc_payments p
WHERE p.payment_date < date_sub(DATE(now()), interval 12 month);

DELETE ops 
FROM order_shipping_selections ops
INNER JOIN orders o ON o.orders_id = ops.orders_id
WHERE o.orders_id IS NULL;



OPTIMIZE TABLE ck_feed_failure_tracking; 
OPTIMIZE TABLE ck_inventory_ledgers;
OPTIMIZE TABLE ck_daily_physical_inventory_snapshot;
OPTIMIZE TABLE ck_daily_recorded_inventory_snapshot;
OPTIMIZE TABLE acc_invoices;
OPTIMIZE TABLE rma_note;
OPTIMIZE TABLE rma_product;
OPTIMIZE TABLE rma;
OPTIMIZE TABLE serials_history;
OPTIMIZE TABLE acc_payments_to_orders;
OPTIMIZE TABLE orders;
OPTIMIZE TABLE ck_daily_physical_inventory_snapshot_ranks;
OPTIMIZE TABLE orders_maxmind;
OPTIMIZE TABLE ipn_calendar_temp;
OPTIMIZE TABLE orders_status_history;
OPTIMIZE TABLE orders_products;
OPTIMIZE TABLE acc_invoice_items;
OPTIMIZE TABLE credit_card_log;
OPTIMIZE TABLE orders_total;
OPTIMIZE TABLE orders_notes;
OPTIMIZE TABLE acc_invoice_totals;
OPTIMIZE TABLE acc_transaction_history;
OPTIMIZE TABLE products_stock_control_change_history;
OPTIMIZE TABLE ck_debug_labels;
OPTIMIZE TABLE orders_packages;
OPTIMIZE TABLE ck_error_log;
OPTIMIZE TABLE search_queries;
OPTIMIZE TABLE serials_history;
OPTIMIZE TABLE acc_payments;
OPTIMIZE TABLE order_shipping_selections;


