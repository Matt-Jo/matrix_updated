<?php
require('includes/application_top.php');

ini_set('memory_limit', '512M');
set_time_limit(0);

//----------header--------------
$cktpl = new ck_template('includes/templates', ck_template::BACKEND);
$content_map = new ck_content();
require('includes/matrix-boilerplate.php');
$cktpl->open($content_map);
ck_bug_reporter::render();
//----------end header----------

//-------------body-------------
$content_map = new ck_content();

if (!empty($errors)) {
    $content_map->{'has_errors?'} = 1;
    $content_map->errors = $errors;
}

/*$products_id = isset($_GET['pID'])?$_GET['pID']:NULL;
$action = isset($_GET['action'])?$_GET['action']:NULL;

if (!empty($action)) {
	switch ($action) {
		case 'setflag':
			$flag = $_GET['flag']?1:0;
			tep_set_product_status($products_id, $flag);
			break;
	}
}*/

/*if (!empty($products_id)) {
	$ipns_info = prepared_query::fetch('SELECT psc.stock_id, psc.stock_name, p.products_id, p.products_model, p.products_status, pd.products_name FROM products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id AND p.archived = 0 LEFT JOIN products_description pd ON p.products_id = pd.products_id WHERE p.products_status IS NULL OR p.products_status = 0 OR p.products_id = :products_id ORDER BY psc.stock_quantity DESC, psc.stock_name ASC', cardinality::SET, [':products_id' => $products_id]);
}
elseif (empty($products_id)) {
	//$ipns_info = prepared_query::fetch('SELECT psc.stock_id FROM products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id AND p.archived = 0 LEFT JOIN products_description pd ON p.products_id = pd.products_id JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id WHERE (p.products_status IS NULL OR p.products_status = 0) AND psc.dlao_product = 0 AND pscv.id != 13 AND archived = 0 ORDER BY psc.stock_quantity DESC, psc.stock_name ASC', cardinality::SET);
}*/

$ipns_info = prepared_query::fetch('SELECT psc.stock_id FROM products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id AND p.archived = 0 LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id LEFT JOIN products_stock_control_verticals pscv ON pscc.vertical_id = pscv.id WHERE (p.products_status IS NULL OR p.products_status = 0) AND psc.dlao_product = 0 AND pscv.id != 13 ORDER BY psc.stock_quantity DESC', cardinality::SET);

$ipn_count = 0;

ck_archetype::cache(FALSE);
ck_ipn2::set_load_context(ck_ipn2::CONTEXT_LIST);
ck_ipn2::run_ipn_set();

foreach ($ipns_info as $ipn_info) {
    $ipn = new ck_ipn2($ipn_info['stock_id']);
    $number_of_products_on = 0;
    
	//$num_on = count($ipn->get_listings());
	
	//var_dump($ipn->get_header());
	//exit();

    /*if (!empty($products_id)) {
		$onprod = prepared_query::fetch('SELECT DISTINCT products_model as model FROM products WHERE products_id != :products_id AND stock_id = :stock_id AND products_status = 1 AND archived = 0', cardinality::SET, [':products_id' => $products_id, ':stock_id' => $ipn_info['stock_id']]);
	}
	else {
	    $onprod = prepared_query::fetch('SELECT DISTINCT products_model as model FROM products WHERE stock_id = :stock_id AND products_status = 1 AND archived = 0', cardinality::SET, [':stock_id' => $ipn_info['stock_id']]);
    }

	if (!empty($onprod)) {
        foreach ($onprod as $model) {
            if ($model['model'] == $ipn_info['products_model']) continue 2;
            $num_on++;
		}
	}*/
	
	//active product check
	//$products = prepared_query::fetch('SELECT products_id, products_status FROM products WHERE stock_id = :stock_id AND archived = 0', cardinality::SET, [':stock_id' => $ipn_info['stock_id']]);
	
	// has products in this context is active products only
	$has_products = 0;
	if ($ipn->has_listings()) {
		$products = $ipn->get_listings();
	
		foreach($products as $product) {
			if ($product->get_header('products_status') == 1) {
				$has_products = 1;
				$number_of_products_on ++;
			}
		}
	}
		
	/*$has_products = 0; // has products in this context means active - if there are no active products then we don't have any at all
	if (!empty($products)) {
		$has_products = 1; // yayy, we have some product
		// now lets check if the product(s) is active or not
		$no_active_products = 1;
		foreach ($products as $product) {
			if ($product['products_status'] == 1) {
				$no_active_products = 0; // we found an active one! :)
				continue; // one's enough for me!
			}
		}
		if ($no_active_products == 1) $has_products = 0;
	}
	*/
	$info = '';
	if (!$has_products) $info = '[NO PRODUCT]';
	

	/*if ($has_products == 1) {
        $control = '<img src="'.DIR_WS_IMAGES.'icon_status_green.gif" height="10" height="10">&nbsp;&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?action=setflag&flag=0&pID='.$ipn_info['products_id'].'"><img src="'.DIR_WS_IMAGES.'icon_status_red_light.gif" height="10" height="10"></a>';
    }
    elseif ($has_products == 0) {
        $control = '<a href="'.$_SERVER['PHP_SELF'].'?action=setflag&flag=1&pID='.$ipn_info['products_id'].'"><img src="'.DIR_WS_IMAGES.'icon_status_green_light.gif" height="10" width="10"></a>&nbsp;&nbsp;<img src="'.DIR_WS_IMAGES.'icon_status_red.gif" height="10" width="10">';
	}*/

	//$ipn_info['products_model']&&empty($ipn_info['products_name'])?$err='[Y]':$err=NULL;
		
	$ipn_content[] = [
		'has_inventory' => $ipn->get_inventory('on_hand')>0?1:0,
		'is_discontinued' => $ipn->is('discontinued')?1:0,
		'has_products' => $has_products,
		'ipn' => $ipn->get_header('ipn'),
		'stock_quantity' => $ipn->get_inventory('on_hand'),
		'estimated_total_cost' => '$'.$ipn->get_inventory('on_hand') * $ipn->get_header('average_cost'),
		//'products_model' => $ipn_info['products_model'],
		//'products_status' => $ipn_info['products_status'],
		//'products_name' => $ipn_info['products_name'],
		'onprod' => !empty($number_of_products_on)?'['.$number_of_products_on.']':NULL,
		//'err' => $err,
		'info' => $info
	];
	
	$ipn_count++;

	ck_ipn2::destroy_record($ipn->id());
}
$content_map->ipn_count = $ipn_count;
$content_map->ipns = $ipn_content;

$cktpl->content('includes/templates/page-ipns-with-products-off.mustache.html', $content_map);
//---------end body------------

//---------footer-------------
$cktpl->close($content_map);
//-------end footer--------------
?>