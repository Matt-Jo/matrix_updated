<?php
require_once(__DIR__.'/../../includes/application_top.php');

debug_tools::init_page();
debug_tools::enable_flag('print');
debug_tools::enable_flag('memory');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

$path = dirname(__FILE__);

ini_set('memory_limit', '4096M');
set_time_limit(0);

// start by filling in a bunch of missing IPN creation entries
prepared_query::execute("UPDATE products_stock_control_change_history pscch LEFT JOIN (SELECT stock_id, MIN(change_date) as change_date FROM products_stock_control_change_history WHERE change_date IS NOT NULL GROUP BY stock_id) pscch0 ON pscch.stock_id = pscch0.stock_id SET pscch.change_date = pscch0.change_date WHERE pscch.type_id = 26 AND pscch.change_date IS NULL");

// start conservative - look for "new IPN" changes
prepared_query::execute("UPDATE products_stock_control psc LEFT JOIN (SELECT stock_id, MIN(change_date) as change_date FROM products_stock_control_change_history WHERE type_id IN (26, 1022) AND change_date IS NOT NULL GROUP BY stock_id) pscch ON psc.stock_id = pscch.stock_id SET psc.date_added = pscch.change_date WHERE psc.date_added IS NULL OR psc.date_added IS NULL");

// ... then just set it to the earliest change for everything else, we're going way back here in all cases
prepared_query::execute("UPDATE products_stock_control psc LEFT JOIN (SELECT stock_id, MIN(change_date) as change_date FROM products_stock_control_change_history WHERE change_date IS NOT NULL GROUP BY stock_id) pscch ON psc.stock_id = pscch.stock_id SET psc.date_added = pscch.change_date WHERE psc.date_added IS NULL OR psc.date_added IS NULL");

exit();
?>
