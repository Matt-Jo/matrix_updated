<?php
require_once(__DIR__.'/../includes/application_top.php');

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

set_time_limit(0);

// default to top 20%, 21-50%, bottom 50%
function calc_tiers($item_count, $tiers) {
	$return = array();
	if (!(int) $item_count || !$tiers) return $return;
	foreach ($tiers as $range) {
		if (array_sum($return) >= $item_count) {
			// we've already accounted for the full amount
			$return[] = 0;
			continue;
		}
		if (!empty($range)) {
			$cnt = round($item_count*$range);
			// if the total count doesn't support our requested range and there is still some of the count left, just set this tier to 1 item
			if (!$cnt && ($item_count - array_sum($return)) > 0) $cnt = 1;
			$return[] = $cnt;
		}
		else {
			// we just want the rest
			$return[] = $item_count - array_sum($return);
		}
	}
	return $return;
}

function trydebug() {
	global $debug, $idx;
	//echo "DEBUG [".($debug++)."] - IDX [$idx]<br/>";
}

//try {
	$period = 90;

	// set up the table, or empty it if it exists
	if (!prepared_query::fetch("SHOW TABLES LIKE 'ck_ipn_tiers'", cardinality::ROW)) {
		prepared_query::execute('CREATE TABLE IF NOT EXISTS ck_ipn_tiers (stock_id int(11) NOT NULL, ipn varchar(255) NOT NULL, categories_id int(11) default NULL, ipn_category varchar(255) default NULL, period int(11) NOT NULL default ?, gross_margin decimal(15,4) default NULL, units_sold int(11) default NULL, num_orders int(11) default NULL, rank_gm int(11) default NULL, rank_us int(11) default NULL, rank_no int(11) default NULL, cat_rank_gm int(11) default NULL, cat_rank_us int(11) default NULL, cat_rank_no int(11) default NULL, run_date timestamp NOT NULL default CURRENT_TIMESTAMP, PRIMARY KEY (stock_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8;', array($period));
		prepared_query::execute('CREATE TABLE IF NOT EXISTS ck_ipn_tiers_invoices (last_invoice_id int(11) NOT NULL, KEY last_idx (last_invoice_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
	}
	else {
		prepared_query::execute('TRUNCATE TABLE ck_ipn_tiers');
		prepared_query::execute('TRUNCATE TABLE ck_ipn_tiers_invoices');
	}

	// update the invoices table with details directing us to correct data
	prepared_query::execute('INSERT INTO ck_ipn_tiers_invoices (last_invoice_id) SELECT MAX(ai1.invoice_id) FROM acc_invoices ai1 JOIN acc_invoices ai0 ON ai1.inv_order_id = ai0.inv_order_id AND ai1.credit_memo = ai0.credit_memo WHERE ai0.inv_order_id IS NOT NULL AND ai0.credit_memo = 0 AND TO_DAYS(ai0.inv_date) >= TO_DAYS(NOW()) - ? GROUP BY ai0.inv_order_id', array($period));

	// initialize the items from the products_stock_control table
	prepared_query::execute('INSERT INTO ck_ipn_tiers (stock_id, ipn, categories_id, ipn_category) SELECT psc.stock_id, psc.stock_name, pscc.categories_id, pscc.name FROM products_stock_control psc LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id');

	// for each IPN, grab invoiced sales history and populate it in the table
	$ipns = prepared_query::fetch('SELECT * FROM ck_ipn_tiers', cardinality::SET);
	foreach ($ipns as $idx => $ipn) {
		$debug = 0;
		$start = time();
		// gross margin for the period
		$gm = prepared_query::fetch('SELECT SUM((aii.invoice_item_price*ABS(aii.invoice_item_qty)) - (aii.orders_product_cost_total)) AS gm FROM acc_invoice_items aii JOIN acc_invoices ai ON ai.invoice_id = aii.invoice_id LEFT JOIN ck_ipn_tiers_invoices iti ON ai.invoice_id = iti.last_invoice_id WHERE aii.ipn_id = ? AND TO_DAYS(ai.inv_date) >= TO_DAYS(NOW()) - ? AND ai.credit_memo = 0 AND (ai.rma_id IS NOT NULL OR iti.last_invoice_id IS NOT NULL)', cardinality::SINGLE, array($ipn['stock_id'], $ipn['period']));
		!$gm?$gm=0:NULL;

		// unit sales for the period
		$us = prepared_query::fetch('SELECT SUM(aii.invoice_item_qty) AS us FROM acc_invoice_items aii JOIN acc_invoices ai ON ai.invoice_id = aii.invoice_id JOIN ck_ipn_tiers_invoices iti ON ai.invoice_id = iti.last_invoice_id WHERE aii.ipn_id = ? AND TO_DAYS(ai.inv_date) >= TO_DAYS(NOW()) - ? AND ai.credit_memo = 0', cardinality::SINGLE, array($ipn['stock_id'], $ipn['period']));
		!$us?$us=0:NULL;

		// total order count for the period
		$no = prepared_query::fetch('SELECT COUNT(DISTINCT ai.inv_order_id) as no FROM acc_invoices ai JOIN ck_ipn_tiers_invoices iti ON ai.invoice_id = iti.last_invoice_id JOIN acc_invoice_items aii ON ai.invoice_id = aii.invoice_id WHERE aii.ipn_id = ? AND TO_DAYS(ai.inv_date) >= TO_DAYS(NOW()) - ?', cardinality::SINGLE, array($ipn['stock_id'], $ipn['period']));
		!$no?$no=0:NULL;

		// update the record
		prepared_query::execute('UPDATE ck_ipn_tiers SET gross_margin = ?, units_sold = ?, num_orders = ? WHERE stock_id = ?', array($gm, $us, $no, $ipn['stock_id']));
		$end = time();
		//if ($end > $start) echo "IPN ".$ipn['ipn']." [".$ipn['stock_id']."] took ".($end-$start)." seconds<br/>";
	}

	// update ranks
	list($t1_count, $t2_count, $t3_count) = calc_tiers(count($ipns), array(.2, .3, 0));
	// we can interpolate the limits directly since we're directly calculating them from known integer outputs
	prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers ORDER BY gross_margin DESC LIMIT $t1_count) r ON it.stock_id = r.stock_id SET rank_gm = 1");
	prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers ORDER BY gross_margin DESC LIMIT $t1_count, $t2_count) r ON it.stock_id = r.stock_id SET rank_gm = 2");
	// we could use the t3_count, but why bother?
	prepared_query::execute('UPDATE ck_ipn_tiers SET rank_gm = 3 WHERE rank_gm IS NULL');

	prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers ORDER BY units_sold DESC LIMIT $t1_count) r ON it.stock_id = r.stock_id SET rank_us = 1");
	prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers ORDER BY units_sold DESC LIMIT $t1_count, $t2_count) r ON it.stock_id = r.stock_id SET rank_us = 2");
	prepared_query::execute('UPDATE ck_ipn_tiers SET rank_us = 3 WHERE rank_us IS NULL');

	prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers ORDER BY num_orders DESC LIMIT $t1_count) r ON it.stock_id = r.stock_id SET rank_no = 1");
	prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers ORDER BY num_orders DESC LIMIT $t1_count, $t2_count) r ON it.stock_id = r.stock_id SET rank_no = 2");
	prepared_query::execute('UPDATE ck_ipn_tiers SET rank_no = 3 WHERE rank_no IS NULL');

	// update each category
	$categories = prepared_query::fetch('SELECT categories_id, COUNT(stock_id) as cat_count FROM ck_ipn_tiers GROUP BY categories_id', cardinality::SET);
	foreach ($categories as $idx => $cat) {
		list($t1_count, $t2_count, $t3_count) = calc_tiers($cat['cat_count'], array(.2, .3, 0));
		prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers WHERE categories_id = ? ORDER BY gross_margin DESC LIMIT $t1_count) r ON it.stock_id = r.stock_id SET it.cat_rank_gm = 1 WHERE it.categories_id = ?", array($cat['categories_id'], $cat['categories_id']));
		prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers WHERE categories_id = ? ORDER BY gross_margin DESC LIMIT $t1_count, $t2_count) r ON it.stock_id = r.stock_id SET cat_rank_gm = 2 WHERE categories_id = ?", array($cat['categories_id'], $cat['categories_id']));
		prepared_query::execute('UPDATE ck_ipn_tiers SET cat_rank_gm = 3 WHERE categories_id = ? AND cat_rank_gm IS NULL', array($cat['categories_id']));

		prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers WHERE categories_id = ? ORDER BY units_sold DESC LIMIT $t1_count) r ON it.stock_id = r.stock_id SET cat_rank_us = 1 WHERE categories_id = ?", array($cat['categories_id'], $cat['categories_id']));
		prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers WHERE categories_id = ? ORDER BY units_sold DESC LIMIT $t1_count, $t2_count) r ON it.stock_id = r.stock_id SET cat_rank_us = 2 WHERE categories_id = ?", array($cat['categories_id'], $cat['categories_id']));
		prepared_query::execute('UPDATE ck_ipn_tiers SET cat_rank_us = 3 WHERE categories_id = ? AND cat_rank_us IS NULL', array($cat['categories_id']));

		prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers WHERE categories_id = ? ORDER BY num_orders DESC LIMIT $t1_count) r ON it.stock_id = r.stock_id SET cat_rank_no = 1 WHERE categories_id = ?", array($cat['categories_id'], $cat['categories_id']));
		prepared_query::execute("UPDATE ck_ipn_tiers it JOIN (SELECT DISTINCT stock_id FROM ck_ipn_tiers WHERE categories_id = ? ORDER BY num_orders DESC LIMIT $t1_count, $t2_count) r ON it.stock_id = r.stock_id SET cat_rank_no = 2 WHERE categories_id = ?", array($cat['categories_id'], $cat['categories_id']));
		prepared_query::execute('UPDATE ck_ipn_tiers SET cat_rank_no = 3 WHERE categories_id = ? AND cat_rank_no IS NULL', array($cat['categories_id']));
	}
/*}
catch (Exception $e) {
	echo $e->getMessage();
	// we should make some sort of notification to someone who cares here
}*/
?>
