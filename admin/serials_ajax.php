<?php
require('includes/application_top.php');

function get_previously_entered_serials_orders() {
	if (!isset($_REQUEST['order_id']) && !isset($_REQUEST['ipn_id']) && !isset($_REQUEST['orders_products_id'])) throw new Exception('order_id, ipn_id, or orders_products_id not set');

	$order = new ck_sales_order($_REQUEST['order_id']);

	$serials = prepared_query::fetch('SELECT s.serial, s.id, s.ipn as stock_id, sh.cost FROM serials s JOIN serials_history sh ON s.id = sh.serial_id WHERE sh.order_id = :orders_id AND s.ipn = :stock_id AND sh.order_product_id = :orders_products_id', cardinality::SET, [':orders_id' => $order->id(), ':stock_id' => $_REQUEST['ipn_id'], ':orders_products_id' => $_REQUEST['orders_products_id']]);

	echo '<input type="hidden" id="num_serials_entered" name="num_serials_entered" value="'.count($serials).'">';

	if (!empty($serials)) {
		echo '<div style="text-align:left;"><a href="#" onclick="delete_all_ipn_serials('.$_REQUEST['ipn_id'].');">(remove all serials allocated for this IPN)</a></div><br>';
		foreach ($serials as $serial) {
			echo $serial['serial'].' ('.CK\text::monetize($serial['cost']).')';
			if (!$order->is_shipped()) echo ' <a href="javascript:void(null)" onClick="delete_serial_from_order('.$serial['id'].')">(delete)</a>';
			echo '<br>';
		}
	}
	else echo 'No Serials entered yet.';
}

function delete_serial_from_order() {
	if (! isset($_REQUEST['serial_id']) || ! isset($_REQUEST['order_id']) || ! isset($_REQUEST['ipn_id'])) {
		throw new Exception('ipn, po, and serial must be entered.');
	}

	$serial = new ck_serial($_REQUEST['serial_id']);
	$serial->unallocate();

	get_previously_entered_serials_orders();
}

function delete_all_ipn_serials() {
	if (! isset($_REQUEST['stock_id']) || ! isset($_REQUEST['order_id']) || ! isset($_REQUEST['ipn_id'])) {
		throw new Exception('ipn must be entered.');
	}

	$serials = ck_serial::get_allocated_serials_by_orders_id($_REQUEST['order_id'], $_REQUEST['stock_id']);
	foreach ($serials as $serial) {
		$serial->unallocate();
	}

	get_previously_entered_serials_orders();
}

function add_serial_to_order() {
	try {
		if (!isset($_REQUEST['serial_id']) || !isset($_REQUEST['order_id']) || !isset($_REQUEST['ipn_id']) && !isset($_REQUEST['orders_products_id'])) {
			throw new Exception('order, ipn, and serial must be entered.');
		}

		if (!prepared_query::fetch('SELECT op.orders_products_id FROM orders_products op JOIN products p ON op.products_id = p.products_id JOIN serials s ON p.stock_id = s.ipn WHERE op.orders_products_id = :orders_products_id AND s.id = :serial_id', cardinality::SINGLE, [':orders_products_id' => $_REQUEST['orders_products_id'], ':serial_id' => $_REQUEST['serial_id']])) {
			throw new Exception('There was a mismatch between the serial # and the IPN, please refresh the page and try again.');
		}

		$total_qty = prepared_query::fetch('SELECT products_quantity FROM orders_products WHERE orders_id = :orders_id AND orders_products_id = :orders_products_id', cardinality::SINGLE, [':orders_id' => $_REQUEST['order_id'], ':orders_products_id' => $_REQUEST['orders_products_id']]);
		$allocated_qty = prepared_query::fetch('SELECT COUNT(sh.id) FROM serials_history sh JOIN serials s ON sh.serial_id = s.id WHERE sh.order_id = :orders_id AND sh.order_product_id = :orders_products_id', cardinality::SINGLE, [':orders_id' => $_REQUEST['order_id'], ':orders_products_id' => $_REQUEST['orders_products_id']]);
		$reserved_qty = prepared_query::fetch('SELECT COUNT(s.id) FROM serials_assignments sa JOIN serials s ON sa.serial_id = s.id WHERE s.status != :allocated AND sa.orders_products_id = :orders_products_id AND sa.fulfilled = 0', cardinality::SINGLE, [':allocated' => ck_serial::$statuses['ALLOCATED'], ':orders_products_id' => $_REQUEST['orders_products_id']]);

		$unallocated_qty = $total_qty - $allocated_qty;

		$serial = new ck_serial($_REQUEST['serial_id']);

		if ($unallocated_qty <= $reserved_qty && !$serial->is_reserved_to($_REQUEST['orders_products_id'])) {
			throw new Exception('You are trying to allocate a serial that is not one of the required reserved serials for this order line. Please get the correct serial.');
		}
		
		$serial->allocate($_REQUEST['order_id'], $_REQUEST['orders_products_id']);

		get_previously_entered_serials_orders();
	}
	catch (Exception $e) {
		echo '<div class="add-serial-error"><strong>There was a problem allocating this serial:</strong><br>'.$e->getMessage().'</div>';
		echo '<input type="hidden" id="num_serials_entered" name="num_serials_entered" value="'.$allocated_qty.'">';
	}
}

function deallocate_serials() {
	if (!isset($_REQUEST['order_id'])) {
		throw new Exception('order_id not set!');
	}

	$serials = ck_serial::get_allocated_serials_by_orders_id($_REQUEST['order_id']);
	foreach ($serials as $serial) {
		$serial->unallocate();
	}
}

function check_order_for_serials() {
	if (!isset($_REQUEST['order_id'])) {
		throw new Exception('order_id is required');
	}

	if ($serials = ck_serial::get_allocated_serials_by_orders_id($_REQUEST['order_id'])) echo "true";
	else echo "false";
}

function serials_autocomplete() {
	if (!isset($_REQUEST['ipn_id']) && !isset($_REQUEST['search_all'])) throw new Exception('ipn not set and search_all not specified');

	$value = trim($_REQUEST['term']);
	if (empty($value)) $value = trim($_REQUEST['value']);

	if (isset($_REQUEST['search_all'])) {
		$serials = prepared_query::fetch('SELECT s.id, s.serial, psc.stock_name FROM serials s LEFT JOIN products_stock_control psc ON psc.stock_id = s.ipn WHERE serial LIKE :serial_number LIMIT 50', cardinality::SET, [':serial_number' => $value.'%']);
	}
	elseif (isset($_REQUEST['search_ipn'])) {
		$serials = prepared_query::fetch('SELECT s.id, s.serial FROM serials s JOIN products_stock_control psc ON s.ipn = psc.stock_id WHERE psc.stock_name = :ipn AND serial LIKE :serial_number LIMIT 50', cardinality::SET, [':ipn' => $_REQUEST['search_ipn'], ':serial_number' => $value.'%']);
	}
	else {
		$serials = prepared_query::fetch('SELECT id, serial FROM serials WHERE status = :instock AND ipn = :stock_id AND serial LIKE :serial_number LIMIT 50', [':instock' => ck_serial::$statuses['INSTOCK'], ':stock_id' => $_REQUEST['ipn_id'], ':serial_number' => $value.'%']);
	}

	if (isset($_REQUEST['get_ipn'])) $set_id = 'stock_name';
	else $set_id = 'id';

	$response = [];
	foreach ($serials as $serial) {
		$obj = new stdClass();
		$obj->value = $serial[$set_id];
		$obj->label = $serial['serial'];
		$response[] = $obj;
	}

	echo json_encode($response);
}

function serial_bins_autocomplete() {
	if (! isset($_REQUEST['ipn_id']) && ! isset($_REQUEST['search_all'])) {
		throw new Exception('ipn not set and search_all not specified');
	}

	$value = trim($_REQUEST['term']);
	if ($value == null) {
		$value = trim($_REQUEST['value']);
	}

	if (isset($_REQUEST['search_all'])) {
		$serials = ck_serial::get_serials_by_serial_match($value);
		// don't know why we exclude invoiced here, but it was in the original query, so I'm pulling it forward
		$serials = array_filter($serials, function($srl) { return $srl->get_header('status_code') != ck_serial::$statuses['INVOICED']; });
	}
	elseif (isset($_REQUEST['search_ipn'])) {
		$ipn = ck_ipn2::get_ipn_by_ipn($_REQUEST['search_ipn']);
		$serials = ck_serial::get_serials_by_serial_match($value, $ipn->id());
	}
	else {
		$serials = ck_serial::get_serials_by_serial_match($value, $_REQUEST['ipn_id']);
		$serials = array_filter($serials, function($srl) { return $srl->get_header('status_code') == ck_serial::$statuses['INSTOCK']; });
	}
	$serials = array_slice($serials, 0, 50);

	$response = array();
	foreach ($serials as $serial) {
		$obj = new stdClass();
		$obj->value = isset($_REQUEST['get_ipn'])?$serial->get_ipn()->get_header('ipn'):$serial->id();
		$obj->label = $serial->get_header('serial_number');
		$obj->curr_bin = $serial->get_current_history('bin_location');
		$response[] = $obj;
	}

	echo json_encode($response);
}

function ipn_autocomplete() {
	$value = trim($_REQUEST['term']);

	$search_type = !empty($_REQUEST['search_type'])?$_REQUEST['search_type']:NULL;
	$result_type = !empty($_REQUEST['result_type'])?$_REQUEST['result_type']:NULL;
	$special = !empty($_REQUEST['special'])?$_REQUEST['special']:NULL;
	$ipn_only = !empty($_REQUEST['ipn_only'])?$_REQUEST['ipn_only']:NULL;

	$results = array();
	if ($search_type == 'serial') {
		$serials = prepared_query::fetch('SELECT psc.stock_name, s.serial FROM serials s JOIN products_stock_control psc ON s.ipn = psc.stock_id WHERE s.serial LIKE :serial ORDER BY s.serial ASC LIMIT 50', cardinality::SET, [':serial' => $value.'%']);
		foreach ($serials as $serial) {
			$obj = new stdClass();
			$obj->value = $serial['stock_name'];
			$obj->label = $serial['serial'];
			$results[] = $obj;
		}
	}
	elseif ($result_type == 'rfq') {
		$results = prepared_query::fetch('SELECT psc.stock_id, psc.conditions as condition_id, psc.stock_name, psc.stock_name as label, \'\' as value, IFNULL(p.products_model, psc.stock_name) as model_number FROM products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id AND p.products_status = 1 LEFT JOIN products p2 ON p.stock_id = p2.stock_id AND p2.products_status = 1 AND p.products_id > p2.products_id WHERE p2.products_id IS NULL AND psc.stock_name RLIKE :match ORDER BY psc.stock_name ASC', cardinality::SET, [':match' => preg_replace('/([^a-zA-Z0-9.+])/', '$1?', $value)]);
	}
	elseif ($result_type == 'dow') {
		$ipns = prepared_query::fetch('SELECT psc.stock_id, psc.stock_name, p.products_id, p.products_model, pd.products_name FROM products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id AND p.products_status = 1 LEFT JOIN products_description pd ON p.products_id = pd.products_id WHERE psc.stock_name RLIKE :match ORDER BY psc.stock_name ASC', cardinality::SET, [':match' => preg_replace('/([^a-zA-Z0-9.+])/', '$1?', $value)]);

		foreach ($ipns as $ipn) {
			$label = $ipn['stock_name'];
			if (!empty($ipn['products_model'])) $label .= ' ['.$ipn['products_model'].' - '.$ipn['products_name'].']';
			else $label .= ' [NO ACTIVE LISTING]';
			$results[] = array(
				'stock_id' => $ipn['stock_id'],
				'label' => $label,
				'value' => $ipn['stock_name'],
				'products_id' => $ipn['products_id'],
				'products_model' => $ipn['products_model']
			);
		}
	}
	else {
		if ($special == 1) {
			$stockResults = prepared_query::fetch('SELECT psc.stock_id, psc.stock_price, psc.dealer_price, psc.stock_name, psc.serialized, psc.products_stock_control_category_id, CASE WHEN psc.serialized = 1 THEN (SELECT count(*) AS count FROM serials WHERE ipn = psc.stock_id AND status in (2, 3, 6)) ELSE psc.stock_quantity END as qty_on_hand, psc.max_inventory_level, CASE WHEN IFNULL(psc.min_inventory_level, 0) > vtsi.lead_time THEN psc.min_inventory_level ELSE vtsi.lead_time END as lead_factor, hist.to180, hist.p3060, hist.to30, CASE WHEN s.stock_id IS NOT NULL THEN 1 ELSE NULL END as has_special FROM products_stock_control psc LEFT JOIN (SELECT DISTINCT p.stock_id FROM products p JOIN specials s ON p.products_id = s.products_id WHERE p.archived = 0) s ON psc.stock_id = s.stock_id LEFT JOIN vendors_to_stock_item vtsi ON psc.vendor_to_stock_item_id = vtsi.id LEFT JOIN ck_cache_sales_history hist ON psc.stock_id = hist.stock_id WHERE psc.stock_name RLIKE :match ORDER BY psc.stock_name ASC', cardinality::SET, [':match' => preg_replace('/([^a-zA-Z0-9.+])/', '$1?', $value)]);
		}
		else {
			$stockResults = prepared_query::fetch('SELECT psc.stock_id, psc.stock_name, psc.serialized, psc.products_stock_control_category_id, CASE WHEN psc.serialized = 1 THEN (SELECT count(*) AS count FROM serials WHERE ipn = psc.stock_id AND status in (2, 3, 6)) ELSE psc.stock_quantity END as qty_on_hand, psc.max_inventory_level, CASE WHEN IFNULL(psc.min_inventory_level, 0) > vtsi.lead_time THEN psc.min_inventory_level ELSE vtsi.lead_time END as lead_factor, hist.to180, hist.p3060, hist.to30 FROM products_stock_control psc LEFT JOIN vendors_to_stock_item vtsi ON psc.vendor_to_stock_item_id = vtsi.id LEFT JOIN ck_cache_sales_history hist ON psc.stock_id = hist.stock_id LEFT JOIN conditions c ON psc.conditions = c.conditions_id WHERE psc.stock_name RLIKE :next_best ORDER BY psc.stock_name = :exact_match DESC, psc.stock_name ASC', cardinality::SET, [':exact_match' => $value, ':next_best' => preg_replace('/([^a-zA-Z0-9.+])/', '$1?', $value)]);
		}

		$display_full = (empty($ipn_only)||$ipn_only!=1)&&(empty($special)||$special!=1);

		if (!empty($display_full)) {
			$allocated = ck_ipn2::get_legacy_allocated_ipns();
			$on_hold = ck_ipn2::get_legacy_hold_ipns();

			$forecast = new forecast();
		}

		$ipn_width = $avail_width = $price_width = 0;
		foreach ($stockResults as $ipn) {
			$ipn_width = max(strlen($ipn['stock_name']), $ipn_width);
			if ($display_full) $avail_width = max(strlen($ipn['qty_on_hand']-@$on_hold[$ipn['stock_id']]-@$allocated[$ipn['stock_id']]), $avail_width);
			if ($special == 1) $price_width = max(strlen($ipn['stock_price']), $price_width);
		}
		foreach ($stockResults as $ipn) {
			if (!empty($ipn['has_special']) && count($stockResults) > 1) continue;

			$obj = new stdClass();
			$obj->serialized = $ipn['serialized'];
			$obj->stock_id = $ipn['stock_id'];
			$obj->label = $ipn['stock_name'];
			$obj->value = $ipn['stock_name'];

			if (!empty($display_full)) {
				$single_day = $forecast->daily_qty($ipn);
				$available_qty = $ipn['qty_on_hand']-@$on_hold[$ipn['stock_id']]-@$allocated[$ipn['stock_id']];
				$days_supply = !$available_qty?0:(!$single_day?'999-':ceil($available_qty/($single_day)));
				$days_indicator = '0f0';

				if ($available_qty < $forecast->min_qty_formula($ipn)) $days_indicator = 'ee0';
				elseif ($available_qty > $forecast->max_qty_formula($ipn)) $days_indicator = 'f33';

				$obj->data_display = '<table class="ajax_response_data" cellpadding="0" cellspacing="0" border="0"><tbody><tr><td style="width:'.ceil($ipn_width*.65).'em;">'.$ipn['stock_name'].'</td><td style="width:'.$avail_width.'em;">'.$available_qty.'</td><td style="background-color:#'.$days_indicator.';width:2.5em;">'.$days_supply.'</td></tr></tbody></table>';
			}
			elseif ($special == 1) {
				if (!empty($ipn['has_special'])) $obj->stock_id = -1; // we don't want to be able to actually select this
				$obj->label .= ' ($'.number_format($ipn['stock_price'], 2).')';
				$obj->data_display = '<table class="ajax_response_data" cellpadding="0" cellspacing="0" border="0"><tbody><tr><td style="width:'.ceil($ipn_width*.65).'em;'.(!empty($ipn['has_special'])?'background-color:#000;color:#fff;':'').'">'.$ipn['stock_name'].'</td><td style="width:'.$price_width.'em;'.(!empty($ipn['has_special'])?'background-color:#000;color:#fff;':'').'">$'.number_format($ipn['stock_price'], 2).'</td></tr></tbody></table>';
			}
			$results[] = $obj;
		}
	}

	echo json_encode($results);
}

function stock_autocomplete() {
	$exact_match = trim($_REQUEST['term']);
	$approximate_match = preg_replace('/([^a-zA-Z0-9.+])/', '$1?', $exact_match);

	ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);

	$results = [];

	if ($stock_ids = prepared_query::fetch('SELECT DISTINCT psc.stock_id FROM products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id LEFT JOIN serials s ON psc.stock_id = s.ipn WHERE psc.stock_name RLIKE :approximate_match OR p.products_model RLIKE :approximate_match OR s.serial RLIKE :approximate_match ORDER BY psc.stock_name = :exact_match DESC, p.products_model = :exact_match DESC, s.serial = :exact_match DESC, psc.stock_name ASC', cardinality::COLUMN, [':approximate_match' => $approximate_match, ':exact_match' => $exact_match])) {
		$ipn_width = $avail_width = $price_width = 0;
		foreach ($stock_ids as $stock_id) {
			$ipn = new ck_ipn2($stock_id);
			$ipn_width = max(strlen($ipn->get_header('ipn')), $ipn_width);
		}
		foreach ($stock_ids as $stock_id) {
			$ipn = new ck_ipn2($stock_id);

			$data = [
				'ipn' => $ipn->get_header('ipn'),
				'qty_available' => $ipn->get_inventory('available'),
				'matching_field' => 'ipn',
				'listing' => '',
				'serial' => '',
			];

			if (!preg_match('/'.$approximate_match.'/', $data['ipn'])) {
				foreach ($ipn->get_listings() as $listing) {
					if (preg_match('/'.$approximate_match.'/', $listing->get_header('products_model'))) {
						$data['matching_field'] = 'listing';
						$data['listing'] = $listing->get_header('products_model');
						break;
					}
				}

				if ($data['matching_field'] == 'ipn') {
					foreach ($ipn->get_serials() as $serial) {
						if (preg_match('/'.$approximate_match.'/', $serial->get_header('serial_number'))) {
							$data['matching_field'] = 'serial';
							$data['serial'] = $serial->get_header('serial_number');
							break;
						}
					}
				}
			}

			$result = [
				'stock_id' => $ipn->id(),
				'label' => $data['ipn'],
				'value' => $data['ipn'],
				//'data_display' => '<table class="ajax_response_data" cellpadding="0" cellspacing="0" border="0"><tbody><tr><td style="width:'.ceil($ipn_width*.65).'em;">'.$data['ipn'].'</td></tr></table>',
			];

			$results[] = $result;
		}
	}

	echo json_encode($results);
}

function model_autocomplete() {
	$value = trim($_REQUEST['term']);
	$results = array();
	$stockResults = prepared_query::fetch("SELECT p.products_id, p.products_model, CONCAT(p.products_model, ' ', psc.stock_name) AS label FROM products AS p LEFT JOIN products_stock_control AS psc ON p.stock_id = psc.stock_id WHERE p.products_model LIKE :match LIMIT 100", cardinality::SET, [':match' => $value.'%']);
	foreach ($stockResults as $row) {
		$obj = new stdClass();
		$obj->label = $row['label'];
		$obj->value = $row['products_model'];
		$obj->id = $row['products_id'];
		$results[] = $obj;
	}
	echo json_encode($results);
}

function customer_autocomplete() {
	$value = trim($_REQUEST['term']);
	$results = array();
	$stockResults = prepared_query::fetch("SELECT DISTINCT customers_name AS label FROM	orders WHERE customers_name LIKE :match LIMIT 100", cardinality::SET, [':match' => $value.'%']);
	foreach ($stockResults as $row) {
		$obj = new stdClass();
		$obj->label = $row['label'];
		$results[] = $obj;
	}
	echo json_encode($results);
}

function vendor_autocomplete() {
	$value = trim($_REQUEST['term']);
	$results = array();
	$stockResults = prepared_query::fetch("SELECT DISTINCT vendors_id, vendors_company_name AS label FROM	vendors WHERE	vendors_company_name LIKE :match LIMIT 100", cardinality::SET, [':match' => $value.'%']);
	foreach ($stockResults as $row) {
		$obj = new stdClass();
		$obj->label = $row['label'];
		$obj->value = $row['label'];
		$obj->vendor_id = $row['vendors_id'];
		$results[] = $obj;
	}
	echo json_encode($results);
}

function company_autocomplete() {
	$value = trim($_REQUEST['term']);
	$results = array();
	$stockResults = prepared_query::fetch("SELECT customers_company AS label FROM orders WHERE customers_company LIKE :match GROUP BY customers_company LIMIT 100", cardinality::SET, [':match' => $value.'%']);
	foreach ($stockResults as $row) {
		$obj = new stdClass();
		$obj->label = $row['label'];
		$results[] = $obj;
	}
	echo json_encode($results);
}

function order_autocomplete() {
	$value = trim($_REQUEST['term']);

	if (strlen($value) < 3) {
		return false;
	}

	$orders = prepared_query::fetch('select orders_id from orders where orders_id like :orders_id order by orders_id asc', cardinality::COLUMN, [':orders_id' => $value.'%']);

	echo json_encode($orders);
}

function po_number_autocomplete() {
	$value = trim($_REQUEST['term']);

	if (strlen($value) < 2) {
		return false;
	}

	$pos = prepared_query::fetch('select purchase_order_number from purchase_orders where purchase_order_number like :match order by purchase_order_number asc', cardinality::COLUMN, [':match' => $value.'%']);

	echo json_encode($pos);
}

//used in the tracking number auto complete
//this is designed to sort this field in descending order
function cdate_cmp($a, $b) {
	$first = strtotime($a['cdate']);
	$second = strtotime($b['cdate']);
	if ($first == $second) {
		return 0;
	}
	else if ($first > $second) {
		return -1;
	}
	else {
		return 1;
	}
}

function track_number_autocomplete() {
	$value = str_replace(' ', '', trim($_REQUEST['term']));

	if (strlen($value) < 2) {
		return false;
	}

	//MMD - we have to retrieve tracking numbers from three places
	$otrackResults= prepared_query::fetch("select ot.tracking_num as track, 'o' as type, o.orders_id as track_id, o.date_purchased as cdate from orders o left join orders_packages op	on o.orders_id = op.orders_id left join orders_tracking ot	on op.orders_packages_id = ot.orders_packages_id where ot.tracking_num like :match order by o.orders_id desc limit 40", cardinality::SET, [':match' => $value.'%']);

	$rtrackResults= prepared_query::fetch("select r.fedex_tracking_number as track, 'r' as type, r.id as track_id, r.created_on as cdate from rma r where r.fedex_tracking_number like :match order by r.id desc limit 40", cardinality::SET, [':match' => $value.'%']);

	$ptrackResults= prepared_query::fetch("select distinct pot.tracking_number as track, 'p' as type, po.id as track_id, po.creation_date as cdate from purchase_order_tracking pot left join purchase_orders po on (po.id = pot.po_id) where pot.tracking_number like :match order by po.id desc limit 40", cardinality::SET, [':match' => $value.'%']);

	$trackResults = array_merge($otrackResults, $rtrackResults, $ptrackResults);

	usort($trackResults, 'cdate_cmp');

	$count = 0;
	$results = array();
	foreach ($trackResults as $row) {
		if ($count == 40) {
			break;
		}
		$obj = new stdClass();
		$obj->label = $row['track'];
		$obj->value = $row['type'].$row['track_id'];
		$results[] = $obj;
		$count++;
	}
	echo json_encode($results);
}

function invoice_number_autocomplete() {
	$value = str_replace(' ', '', trim($_REQUEST['term']));

	if (strlen($value) < 2) {
		return false;
	}

	$iResults= prepared_query::fetch("select distinct ai.invoice_id, ai.inv_order_id from acc_invoices ai where ai.invoice_id like :match and ai.inv_order_id is not null order by ai.invoice_id desc limit 40", cardinality::SET, [':match' => $value.'%']);

	$results = array();
	foreach ($iResults as $row) {
		$obj = new stdClass();
		$obj->label = $row['invoice_id'];
		$obj->value = $row['inv_order_id'];
		$obj->order_id = $row['inv_order_id'];
		$results[] = $obj;
	}
	echo json_encode($results);
}

function customer_email_autocomplete() {
	$email = preg_replace('/\s/', '', $_REQUEST['term']);

	if (strlen($email) <= 2) return FALSE;

	$emails = prepared_query::fetch('(SELECT customers_id, customers_email_address FROM customers WHERE customers_email_address LIKE :email) UNION (SELECT customers_id, customers_emailaddress as customers_email_address FROM customers_extra_logins WHERE customers_emailaddress LIKE :email) ORDER BY customers_email_address ASC', cardinality::SET, [':email' => '%'.$email.'%']);

	$results = [];
	foreach ($emails as &$address) {
		$address['customers_email_address'] = preg_replace('/('.$email.')/i', '<strong>$1</strong>', $address['customers_email_address']);
		$results[] = ['value' => $address['customers_id'], 'label' => $address['customers_email_address']];
	}

	echo json_encode($results);
}

function order_recipients_autocomplete() {
	$email = preg_replace('/\s/', '', $_REQUEST['term']);

	if (strlen($email) <= 2) return FALSE;

	$sales_order = new ck_sales_order($_REQUEST['orders_id']);

	$emails = prepared_query::fetch('(SELECT customers_id, customers_email_address, customers_firstname, customers_lastname FROM customers WHERE customers_email_address LIKE :email) UNION (SELECT customers_id, customers_emailaddress as customers_email_address, customers_firstname, customers_lastname FROM customers_extra_logins WHERE customers_emailaddress LIKE :email) ORDER BY customers_email_address ASC', cardinality::SET, [':email' => '%'.$email.'%']);

	$results = [];
	foreach ($emails as $address) {
		if (!$sales_order->get_recipients($address['customers_email_address'])) {
			$og = $address['customers_email_address'];
			$address['customers_email_address'] = preg_replace('/(' . $email . ')/i', '<strong>$1</strong>', $address['customers_email_address']);
			$results[] = ['value' => ['email' => $og, 'name' => $address['customers_firstname'] . ' ' . $address['customers_lastname']], 'label' => $address['customers_email_address']];
		}
	}

	echo json_encode($results);
}

function generic_autocomplete() {
	if (!isset($_REQUEST['search_type'])) {
		throw new Exception('search type not set!');
	}

	switch ($_REQUEST['search_type']) {
		case 'serial':
			serials_autocomplete();
			break;
		case 'ipn':
			ipn_autocomplete();
			break;
		case 'stock':
			stock_autocomplete();
			break;
		case 'order':
			order_autocomplete();
			break;
		case 'po_number':
			po_number_autocomplete();
			break;
		case 'track_number':
			track_number_autocomplete();
			break;
		case 'po_track_number':
			po_track_number_autocomplete();
			break;
		case 'rma_track_number':
			rma_track_number_autocomplete();
			break;
		case 'invoice':
			invoice_number_autocomplete();
			break;
		case 'serial_bins':
			serial_bins_autocomplete();
			break;
		case 'customer_email':
			customer_email_autocomplete();
			break;
		case 'order_recipients':
			order_recipients_autocomplete();
			break;
		default:
			throw new Exception("invalid search type {$_REQUEST['search_type']}");
			break;
	}
}

switch ($_REQUEST['action']) {
	case 'get_previously_entered_serials_orders':
		get_previously_entered_serials_orders();
		break;
	case 'add_serial_order':
		add_serial_to_order();
		break;
	case 'delete_serial_from_order':
		delete_serial_from_order();
		break;
	case 'delete_all_ipn_serials':
		delete_all_ipn_serials();
		break;
	case 'serial_autocomplete':
		serials_autocomplete();
		break;
	case 'deallocate_serials':
		deallocate_serials();
		break;
	case 'check_order_for_serials':
		check_order_for_serials();
		break;
	case 'ipn_autocomplete':
		ipn_autocomplete();
		break;
	case 'stock_autocomplete':
		stock_autocomplete();
		break;
	case 'model_autocomplete':
		model_autocomplete();
		break;
	case 'customer_autocomplete':
		customer_autocomplete();
		break;
	case 'vendor_autocomplete':
		vendor_autocomplete();
		break;
	case 'company_autocomplete':
		company_autocomplete();
		break;
	case 'generic_autocomplete':
		generic_autocomplete();
		break;

	default:
		echo 'Action is not defined';
		break;
}

