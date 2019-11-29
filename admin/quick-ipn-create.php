<?php
require('includes/application_top.php');

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;

switch ($action) {
	case 'create':
		$stock_name = trim($_POST['stock_name']);

		if (!empty(ck_ipn2::get_ipn_by_ipn($stock_name))) {
			$errors = ['An IPN with the same name already exists!'];
			break;
		}

		$ipn = [
			'header' => [
				'stock_name' => $stock_name,
				'stock_description' => $_POST['stock_description'],
				'stock_price' => CK\text::demonetize($_POST['stock_price']),
				'dealer_price' => CK\text::demonetize($_POST['dealer_price']),
				'wholesale_high_price' => CK\text::demonetize($_POST['wholesale_high_price']),
				'wholesale_low_price' => CK\text::demonetize($_POST['wholesale_low_price']),
				'conditions' => $_POST['conditions'],
				'serialized' => $__FLAG['serialized']?1:0,
				'stock_weight' => $_POST['stock_weight'] ?: NULL,
				'lead_time' => $_POST['preferred_vendor_lead_time'] ?: NULL,
				'always_available' => $__FLAG['preferred_vendor_always_avail']?1:0,
				'max_displayed_quantity' => $_POST['max_displayed_quantity'] ?: NULL,
				'min_inventory_level' => $_POST['min_inventory_level'] ?: NULL,
				'target_inventory_level' => $_POST['target_inventory_level'] ?: NULL,
				'max_inventory_level' => $_POST['max_inventory_level'] ?: NULL,
				'drop_ship' => $__FLAG['drop_ship']?1:0,
				'non_stock' => $__FLAG['non_stock']?1:0,
				'freight' => $__FLAG['freight']?1:0,
				'dlao_product' => $__FLAG['dlao_product']?1:0,
				'special_order_only' => $__FLAG['special_order_only']?1:0,
				'products_stock_control_category_id' => $_POST['products_stock_control_category_id'],
				'warranty_id' => $_POST['warranty_id'] ?: NULL,
				'dealer_warranty_id' => NULL,
				'is_bundle' => $__FLAG['is_bundle']?1:0,
				'bundle_price_flows_from_included_products' => NULL,
				'bundle_price_modifier' => NULL,
				'bundle_price_signum' => NULL,
				'image_reference' => NULL,
				'eccn_code' => $_POST['eccn_code'],
				'hts_code' => $_POST['hts_code'],
				'discontinued' => $__FLAG['discontinued']?1:0,
			],
			'extra' => [
				'stock_location' => $_POST['stock_location'],
				'stock_location_2' => $_POST['stock_location_2'],
				'preferred_vendor_id' => $_POST['preferred_vendor_id'] ?: NULL,
				'preferred_vendor_part_number' => $_POST['preferred_vendor_part_number'],
			],
			'vendor' => [
				'vendors_id' => $_POST['preferred_vendor_id'],
				'vendors_price' => CK\text::demonetize($_POST['preferred_vendor_price']),
				'vendors_pn' => $_POST['preferred_vendor_part_number'],
				'case_qty' => $_POST['preferred_vendor_case_qty'],
				'always_avail' => $__FLAG['preferred_vendor_always_avail']?1:0,
				'lead_time' => $_POST['preferred_vendor_lead_time'],
				'notes' => $_POST['preferred_vendor_notes'],
			]
		];

		if ($__FLAG['creation_reviewed']) $ipn['header']['creation_reviewed'] = 1;

		try {
			$ipn = ck_ipn2::create($ipn);

			if ($__FLAG['assign_upc']) $ipn->create_upc(['upc' => '', 'provenance' => 'CK']);

			$_POST['stock_id'] = $ipn->id();
			if (empty($_POST['products_model'])) $_POST['products_model'] = $ipn->get_header('ipn');

			$listing = ck_product_listing::create($_POST);

			if ($_POST['follow-up'] == 'view') CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.urlencode($ipn->get_header('ipn')));
			elseif ($_POST['follow-up'] == 'manage-product') CK\fn::redirect_and_exit('/admin/categories.php?action=new_product&pID='.$listing->id());
			elseif ($_POST['follow-up'] == 'add-to-cart') CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.urlencode($ipn->get_header('ipn')).'&selectedTab=ipn-sales&selectedSubTab=ipn-add-to-cart');
		}
		catch (Exception $e) {
			$errors = ['Error: '.$e->getMessage()];
		}

		break;
}

//---------header-----------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//---------end header-------------

//---------body-------------------
$content_map = new ck_content();

if (!empty($errors)) {
	$content_map->{'has_errors?'} = 1;
	$content_map->errors = $errors;
}

if (!empty($ipn) && $ipn instanceof ck_ipn2) $content_map->ipn = urlencode($ipn->get_header('ipn'));

$content_map->conditions = prepared_query::fetch('SELECT conditions_id, conditions_name as `condition` FROM conditions ORDER BY results_sort_order ASC', cardinality::SET);
$content_map->vendors = prepared_query::fetch('SELECT vendors_id, vendors_company_name as vendor FROM vendors ORDER BY vendors_company_name ASC', cardinality::SET);
$content_map->categories = prepared_query::fetch('SELECT categories_id, name as category FROM products_stock_control_categories ORDER BY name ASC', cardinality::SET);
$content_map->warranties = prepared_query::fetch('SELECT warranty_id, warranty_name as warranty FROM warranties', cardinality::SET);
$content_map->manufacturers = prepared_query::fetch('SELECT manufacturers_id, manufacturers_name as manufacturer FROM manufacturers ORDER BY manufacturers_name ASC', cardinality::SET);

$content_map->follow_ups = [
	['value' => 'view', 'description' => 'View Created IPN'],
	['value' => 'repeat', 'description' => 'Create another IPN'],
	['value' => 'manage-product', 'description' => 'Manage Product Listing'],
	['value' => 'add-to-cart', 'description' => 'Add To Cart'],
];

if (in_array($_SESSION['login_id'], [144, 163])) $content_map->reviewer = 1; // rebecca or gazaway

if ($action == 'create') {
	$content_map->follow_ups = array_map(function($fu) {
		if ($_POST['follow-up'] == $fu['value']) $fu['selected'] = 1;
		return $fu;
	}, $content_map->follow_ups);

	if ($_POST['follow-up'] != 'repeat') {
		$content_map->conditions = array_map(function($c) {
			if ($_POST['conditions'] == $c['conditions_id']) $c['selected'] = 1;
			return $c;
		}, $content_map->conditions);

		$content_map->vendors = array_map(function($v) {
			if ($_POST['preferred_vendor_id'] == $v['vendors_id']) $v['selected'] = 1;
			return $v;
		}, $content_map->vendors);

		$content_map->categories = array_map(function($c) {
			if ($_POST['products_stock_control_category_id'] == $c['categories_id']) $c['selected'] = 1;
			return $c;
		}, $content_map->categories);

		$content_map->warranties = array_map(function($w) {
			if ($_POST['warranty_id'] == $w['warranty_id']) $w['selected'] = 1;
			return $w;
		}, $content_map->warranties);

		$content_map->manufacturers = array_map(function($m) {
			if ($_POST['manufacturers_id'] == $m['manufacturers_id']) $m['selected'] = 1;
			return $m;
		}, $content_map->manufacturers);

		// if we get here, we know we had some sort of error trying to create this IPN
		$content_map->values = [];
		foreach ($_POST as $key => $val) {
			$content_map->values[$key] = $val;
		}
	}
	/*else {
		// we want to enter a new IPN - pick a couple defaults to carry over, skip everything else
		$content_map->values = [];
		foreach ($_POST as $key => $val) {
			if (in_array($key, ['serialized', 'drop_ship', 'non_stock', 'preferred_vendor_id'])) $content_map->values[$key] = $val;
		}
	}*/
}
elseif (!empty($_GET['copy_from_stock_id'])) {
	$ipn = new ck_ipn2($_GET['copy_from_stock_id']);
	$header = $ipn->get_header();
	$listing = $ipn->get_default_listing();

	$content_map->source_ipn = $header['ipn'];

	$content_map->values = [
		'stock_name' => $header['ipn'],
		'products_model' => !empty($listing)?$listing->get_header('products_model'):NULL,
		'products_name' => !empty($listing)?$listing->get_header('products_name'):NULL,
		'stock_price' => $header['stock_price'],
		'dealer_price' => $header['dealer_price'],
		'wholesale_high_price' => $header['wholesale_high_price'],
		'wholesale_low_price' => $header['wholesale_low_price'],
		'assign_upc' => 1,
		'stock_description' => $header['stock_description'],
		'conditioning_notes' => $header['conditioning_notes'],
		'stock_weight' => $header['stock_weight'],
		'eccn_code' => $header['eccn_code'],
		'hts_code' => $header['hts_code'],
		'min_inventory_level' => $header['min_inventory_level'],
		'target_inventory_level' => $header['target_inventory_level'],
		'max_inventory_level' => $header['max_inventory_level'],
	];

	$checkboxes = ['serialized', 'is_bundle', 'dlao_product', 'freight', 'discontinued', 'drop_ship', 'non_stock'];

	foreach ($checkboxes as $cb) {
		if ($ipn->is($cb)) $content_map->values[$cb] = 1;
	}

	// we don't ever want to copy condition, we always want to force the user to select it
	/* $content_map->conditions = array_map(function($c) use ($header) {
		if ($header['conditions'] == $c['conditions_id']) $c['selected'] = 1;
		return $c;
	}, $content_map->conditions);*/

	$content_map->categories = array_map(function($c) use ($header) {
		if ($header['products_stock_control_category_id'] == $c['categories_id']) $c['selected'] = 1;
		return $c;
	}, $content_map->categories);

	$content_map->warranties = array_map(function($w) use ($header) {
		if ($header['warranty_id'] == $w['warranty_id']) $w['selected'] = 1;
		return $w;
	}, $content_map->warranties);

	$content_map->manufacturers = array_map(function($m) use ($listing) {
		if (!empty($listing) && $listing->get_header('manufacturers_id') == $m['manufacturers_id']) $m['selected'] = 1;
		return $m;
	}, $content_map->manufacturers);
}

$cktpl->content('includes/templates/page-quick-ipn-create.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
