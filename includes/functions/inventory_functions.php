<?php
function add_ipn_change_history($type, $record_id, $admin_id, $status, $stock_id, $qty, $ipn_chn_id=null) {
	$query = "insert into `ipn_change_history` set `type`='$type', `record_id`= '$record_id', `admin_id`='$admin_id', `change_date`=NOW(), `status`='$status',`stock_id`='$stock_id',`qty`='$qty'";
	if ($ipn_chn_id) {
		$query.=", `ipn_change_history_notes_id`='$ipn_chn_id'";
	}
	prepared_query::execute($query);
	return true;
}

function insert_psc_change_history($stock_id, $type, $old_value, $new_value, $ipn_import_id = 0) {
	$change_type = null;
	$reference = '';
	if (strpos($type, 'Special Delete') !== FALSE) {
		$change_type = 'Special Delete';
		$reference = trim(substr($type, strlen($change_type)));
	}
	elseif (strpos($type, 'Special Create') !== FALSE) {
		$change_type = 'Special Update';
		$reference = trim(substr($type, strlen($change_type)));
	}
	elseif (strpos($type, 'Special Update') !== FALSE) {
		$change_type = 'Special Update';
		$reference = trim(substr($type, strlen($change_type)));
	}
	elseif (strpos($type, 'Changed preferred Vendor for product to') !== FALSE) {
		$change_type = 'Changed preferred Vendor for product';
		$pieces = explode(' to ', $type);
		$reference = trim($pieces[1]);
	}
	elseif (strpos($type, 'Changed preferred Vendor for product') !== FALSE) {
		$change_type = 'Changed preferred Vendor for product';
		$pieces = explode(':', $type);
		$reference = trim(@$pieces[1]);
	}
	elseif (strpos($type, 'Deleted vendor for product') !== FALSE) {
		$change_type = 'Deleted vendor for product';
		$pieces = explode(':', $type);
		$reference = trim($pieces[1]);
	}
	elseif (strpos($type, 'Saved vendor p/n') !== FALSE) {
		$change_type = 'Saved vendor p/n';
		$pieces = explode(':', $type);
		$reference = trim($pieces[1]);
	}
	elseif (strpos($type, 'Added vendor always available') !== FALSE) {
		$change_type = 'Added vendor always available';
		$pieces = explode(':', $type);
		$reference = trim($pieces[1]);
	}
	elseif (strpos($type, 'Added vendor lead time') !== FALSE) {
		$change_type = 'Added vendor lead time';
		$pieces = explode(':', $type);
		$reference = trim($pieces[1]);
	}
	elseif (strpos($type, 'Added vendor p/n') !== FALSE) {
		$change_type = 'Added vendor p/n';
		$pieces = explode(':', $type);
		$reference = trim($pieces[1]);
	}
	elseif (strpos($type, 'Saved vendor price') !== FALSE) {
		$change_type = 'Saved vendor price';
		$pieces = explode(':', $type);
		$reference = trim($pieces[1]);
	}
	elseif (strpos($type, 'Added vendor price') !== FALSE) {
		$change_type = 'Added vendor price';
		$pieces = explode(':', $type);
		$reference = trim($pieces[1]);
	}
	elseif (strpos($type, 'Saved vendor always available') !== FALSE) {
		$change_type = 'Saved vendor always available';
		$pieces = explode(':', $type);
	}
	elseif (strpos($type, 'Saved vendor lead time') !== FALSE) {
		$change_type = 'Saved vendor lead time';
		$pieces = explode(':', $type);
		$reference = trim($pieces[1]);
	}
	else $change_type = $type;

	$type_id = 0;
	$psccht_list = prepared_query::fetch("select psccht.id from products_stock_control_change_history_types psccht where psccht.name = '".addslashes($change_type)."'", cardinality::SET);

	if (count($psccht_list) == 0) {
		$type_id = prepared_query::insert('INSERT INTO products_stock_control_change_history_types (name) VALUES (:name)', [':name' => $change_type]);
	}
	else $type_id = $psccht_list[0]['id'];

	$user_email = !empty($_SESSION['login_id'])?prepared_query::fetch('SELECT admin_email_address FROM admin WHERE admin_id = ?', cardinality::SINGLE, $_SESSION['login_id']):'';

	$pscch_id = prepared_query::insert('INSERT INTO products_stock_control_change_history (stock_id, change_date, change_user, type_id, reference, old_value, new_value, ipn_import_id) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)', [$stock_id, $user_email, $type_id, $reference, $old_value, $new_value, $ipn_import_id]);

	return $pscch_id;
}

function insert_inventory_adjustment($stock_id, $type, $old_value, $new_value, $reason) {
	$average_cost = prepared_query::fetch('SELECT average_cost FROM products_stock_control WHERE stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $stock_id]);

	if ($type == '4') {
		$cost = '';
		$old_average_cost = $average_cost;
		$new_average_cost = $new_value==0?$average_cost:number_format((($average_cost * $old_value)/$new_value), 2, '.', '');

		prepared_query::execute('UPDATE products_stock_control SET average_cost = :new_average_cost WHERE stock_id = :stock_id', [':new_average_cost' => $new_average_cost, ':stock_id' => $stock_id]);
	}
	else {
		$cost = $average_cost;
		$old_average_cost = '';
		$new_average_cost = '';
	}

	$admin_id = $_SESSION['login_id'];

	prepared_query::execute('INSERT INTO inventory_adjustment (ipn_id, scrap_date, admin_id, inventory_adjustment_type_id, inventory_adjustment_reason_id, old_qty, new_qty, cost, old_avg_cost, new_avg_cost) VALUES (:stock_id, NOW(), :admin_id, :type, :reason, :old_value, :new_value, :cost, :old_average_cost, :new_average_cost)', [':stock_id' => $stock_id, ':admin_id' => $admin_id, ':type' => $type, ':reason' => $reason, ':old_value' => $old_value, ':new_value' => $new_value, ':cost' => $cost, ':old_average_cost' => $old_average_cost, ':new_average_cost' => $new_average_cost]);

	insert_psc_change_history($stock_id, 'Quantity Change', $old_value, $new_value);
}

function get_vendor_name_from_v2s($v2s) {
	if (empty($v2s)) return 'null';
	$vendors_company_name = prepared_query::fetch('SELECT vendors_company_name FROM vendors LEFT JOIN vendors_to_stock_item ON vendors.vendors_id = vendors_to_stock_item.vendors_id WHERE vendors_to_stock_item.id = :v2s', cardinality::SINGLE, [':v2s' => $v2s]);
	return $vendors_company_name;
}
?>
