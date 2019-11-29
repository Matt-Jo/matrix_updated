<?php
require('includes/application_top.php');

function ipn_autocompleteorder() {
	$stock_name = '';
	$products_name = '';
	$name_length = [];
	$liArr = [];
	$invArr = [];
	$addonInvArr = [];

	$ipn_lookup = preg_replace('/([^a-zA-Z0-9.+])/', '$1?', $_REQUEST['value']);

	$ipns = prepared_query::fetch('SELECT psc.stock_id, psc.stock_name, p.products_id, pd.products_name, p.products_model, psc.stock_price, psc.dealer_price, psc.wholesale_high_price, psc.wholesale_low_price, psc.average_cost FROM products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id LEFT JOIN products_description pd ON pd.products_id = p.products_id WHERE psc.stock_name RLIKE :ipn_lookup AND p.products_id > 0 ORDER BY p.products_id LIMIT 30', cardinality::SET, [':ipn_lookup' => $ipn_lookup]);

	$name_length['stock_name'] = 0;
	$name_length['products_model'] = 0;

	foreach ($ipns as $ipn) {
		$included_addons = prepared_query::fetch('SELECT product_addon_id, custom_name, custom_desc, custom_price, use_custom_price, products.stock_id, products.products_model FROM product_addons LEFT JOIN products ON products.products_id = product_addon_id WHERE product_id = :products_id AND included = 1', cardinality::SET, [':products_id' => $ipn['products_id']]);

		$stock_name = $ipn['stock_name'];
		$name_length['stock_name'] = max(strlen($ipn['stock_name']), $name_length['stock_name']);
		$name_length['products_model'] = max(strlen($ipn['products_model']), $name_length['products_model']);
		$products_name = $ipn['products_name']?htmlentities($ipn['products_name'], ENT_QUOTES):'';
		$addonslist = '';
		$ckipn = new ck_ipn2($ipn['stock_id']);
		$invArr = $ckipn->get_inventory();
		$allocated = $invArr['allocated'];
		$onhand = $invArr['on_hand'];
		$available = $invArr['available'];

		foreach ($included_addons as $addonarr) {
			$ck_ipn = new ck_ipn2($addonarr['stock_id']);
			$addonInvArr = $ck_ipn->get_inventory();
			$custom_item_id = $ipn['products_id'].'+'.$addonarr['product_addon_id'];
			$custom_price = !empty($addonarr['use_custom_price'])?$addonarr['custom_price']:'0.00';
			$addonslist .= $addonarr['product_addon_id'].'~'.$addonarr['custom_name'].'~'.$addonarr['custom_desc'].'~'.$custom_price.'~'.$addonarr['products_model'].'~'.$addonInvArr['allocated'].'~'.$addonInvArr['on_hand'].'~'.$addonInvArr['available'].'~'.$custom_item_id.'|';
		}
		$addonslist = trim($addonslist, '| ');

		$ipn = ck_ipn2::normalize_pricing($ipn);

		$liArr[$ipn['products_id']] = [
			'stock_name' => $stock_name,
			'products_id' => $ipn['products_id'],
			'products_model' => $ipn['products_model'],
			'products_name' => $products_name,
			'stock_price' => $ipn['stock_price'],
			'dealer_price' => $ipn['dealer_price'],
			'wholesale_high_price' => CK\text::demonetize($ipn['wholesale_high_price']),
			'wholesale_low_price' => CK\text::demonetize($ipn['wholesale_low_price']),
			'average_cost' => $ipn['average_cost'],
			'allocated' => $allocated,
			'stock_quantity' => $onhand,
			'available' => $available,
			'addons' => htmlentities($addonslist, ENT_QUOTES)
		];
	}

	if (!empty($liArr)) {
		asort($liArr);
		$namestr = '';
		$substrlen = 72;
		echo '<ul>';

		foreach ($liArr as $pid => $tmpArr) {
			$namestr = strlen($tmpArr['stock_name'])<$name_length['stock_name']?str_pad($tmpArr['stock_name'], $name_length['stock_name']):$tmpArr['stock_name'];
			$tmpArr['products_model'] = strlen($tmpArr['products_model'])<$name_length['products_model']?str_pad($tmpArr['products_model'], $name_length['products_model']):$tmpArr['products_model'];
			$namestr .= ' ';
			$namestr .= $tmpArr['products_model'];
			$namestr .= ' ';
			$namestr .= $tmpArr['products_name'];
			$namestr = substr($namestr, 0, $substrlen).(strlen($namestr)>=$substrlen?' ...':'');
			$namestr = str_replace(' ', '&nbsp;', $namestr);
			echo '<li id="'.$pid.'">'.$namestr;
			echo '<input type="hidden" id="item_'.$pid.'" value="';
			echo $tmpArr['stock_price'].'^';			// 0
			echo $tmpArr['dealer_price'].'^';			// 1
			echo $tmpArr['wholesale_high_price'].'^';	// 2
			echo $tmpArr['wholesale_low_price'].'^';	// 3
			echo $tmpArr['average_cost'].'^';			// 4
			echo $tmpArr['allocated'].'^';				// 5
			echo $tmpArr['stock_quantity'].'^';			// 6
			echo $tmpArr['available'].'^';				// 7
			echo $tmpArr['stock_name'].'^';				// 8
			echo trim($tmpArr['products_model']).'^';	// 9
			echo $tmpArr['addons'].'">';				// 10
			echo '</li>';
		}
		echo '</ul>';
	}
}

ipn_autocompleteorder();
?>