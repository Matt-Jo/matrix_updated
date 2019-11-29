<?php
/*
	$Id: specials.php,v 1.1.1.1 2004/03/04 23:40:51 ccwjr Exp $

	osCommerce, Open Source E-Commerce Solutions
	http://www.oscommerce.com

	Copyright (c) 2003 osCommerce

	Released under the GNU General Public License
*/

if (!defined('DIR_FS_CATALOG_MODULES')) {
	if (!defined('DIR_FS_CATALOG_MODULES')) define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG.'includes/modules/');
}

////
// Sets the status of a special product
function tep_set_specials_status($specials_id, $status) {
	if (!function_exists('insert_psc_change_history')) {
		require_once(DIR_WS_FUNCTIONS.'inventory_functions.php');
	}

	$prod_details = prepared_query::fetch('SELECT p.stock_id, p.products_model, s.status FROM products p JOIN specials s ON p.products_id = s.products_id WHERE s.specials_id = ?', cardinality::ROW, array($specials_id));
	$old = $prod_details['status']?'Status On':'Status Off';
	$new = $status?'Status On':'Status Off';
	insert_psc_change_history($prod_details['stock_id'], 'Special Update ['.$prod_details['products_model'].']', $old, $new);

	prepared_query::execute("UPDATE specials SET status = :status, date_status_change = NOW() WHERE specials_id = :specials_id", [':status' => $status, ':specials_id' => $specials_id]);
}

////
// Auto expire products on special
function tep_expire_specials() {
	if (($specials = prepared_query::fetch('SELECT specials_id FROM specials WHERE status = 1 AND NOW() >= expires_date AND expires_date > 0', cardinality::SET))) {
		foreach ($specials as $special) {
			tep_set_specials_status($special['specials_id'], 0);
		}
	}
}

function tep_check_and_expire_specials($product_id, $stock_remaining) {
	if (($specials = prepared_query::fetch('SELECT * FROM specials WHERE products_id = ? AND status = 1', cardinality::SET, array($product_id)))) {
		foreach ($specials as $special) {
			// active through the date, no matter what - the specials qty 999999 indicates this under the old scheme
			if ($special['active_criteria'] == 3 || $special['specials_qty'] == '999999') {
				// this isn't really necessary, as all specials are expired per their date by cron
				// also, this is an overriding expiration condition, so to make this complete we should be checking the date anyway
				// but for our purposes, we're just fleshing out the expires options, so this is fine as-is, or we can delete it and note why it's missing
				$specdate = new DateTime($special['expires_date']);
				$nowdate = new DateTime();

				if ($specdate < $nowdate) tep_set_specials_status($special['specials_id'], 0);
				return NULL;
			}
			// active through the stock quantity - the specials qty 0 indicates this under the old scheme
			elseif (($special['active_criteria'] == 2 || $special['specials_qty'] == 0) && $stock_remaining <= 0) {
				tep_set_specials_status($special['specials_id'], 0);
				return $stock_remaining;
			}
			// active until the stock threshold is hit - this represents any other scenario, and is the default for the old scheme
			elseif (($special['active_criteria'] == 1 || !is_numeric($special['active_criteria'])) && $stock_remaining <= $special['specials_qty']) {
				tep_set_specials_status($special['specials_id'], 0);
				return $stock_remaining - $special['specials_qty'];
			}
		}
	}
	return NULL;
}
?>
