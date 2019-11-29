<?php
require('includes/application_top.php');

$action = !empty($_REQUEST['action'])?$_REQUEST['action']:NULL;
$context = !empty($_REQUEST['context'])?$_REQUEST['context']:'create';

if (!empty($action)) {
	switch ($action) {
		case 'create':
			try {
				$data = [
					'header' => [
						'generic_model_number' => !empty($_POST['generic_model_number'])?$_POST['generic_model_number']:NULL,
						'name' => $_POST['name'],
						'description' => !empty($_POST['description'])?$_POST['description']:NULL,
						'homogeneous' => $__FLAG['homogeneous']?1:0
					]
				];

				if (empty($data['header']['name'])) throw new Exception('Family name cannot be empty.');

				$family = ck_family_unit::create($data);

				CK\fn::redirect_and_exit('/admin/merchandising-unit-family-detail.php?context=edit&family_unit_id='.$family->id());
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}

			break;
		case 'edit':
			try {
				$family = new ck_family_unit($_POST['family_unit_id']);

				$data = [
					'generic_model_number' => !empty($_POST['generic_model_number'])?$_POST['generic_model_number']:NULL,
					'name' => $_POST['name'],
					'description' => !empty($_POST['description'])?$_POST['description']:NULL,
					'homogeneous' => $__FLAG['homogeneous']?1:0,
					'active' => $__FLAG['active']?1:0
				];

				if (empty($data['name'])) throw new Exception('Family name cannot be empty.');

				$family->edit($data);

				CK\fn::redirect_and_exit('/admin/merchandising-unit-family-detail.php?context=edit&family_unit_id='.$family->id());
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}

			break;
		case 'variance-lookup':
			$response['results'] = [];
			$lookup = trim($_REQUEST['key']);

			//if (strlen($lookup) <= 1) exit();

			$family_unit_id = $_REQUEST['family_unit_id'];

			$family = new ck_family_unit($family_unit_id);

			if ($family->has_siblings()) {
				$attributes = prepared_query::fetch('SELECT DISTINCT a.attribute_key, a.attribute_key_id FROM ck_attributes a JOIN ck_merchandising_family_unit_siblings fs ON a.stock_id = fs.stock_id AND fs.family_unit_id = :family_unit_id LEFT JOIN ck_merchandising_family_unit_variances fv ON a.attribute_key_id = fv.attribute_id AND fv.family_unit_id = :family_unit_id WHERE fv.family_unit_variance_id IS NULL AND a.attribute_key LIKE :lookup ORDER BY a.attribute_key ASC', cardinality::SET, [':family_unit_id' => $family_unit_id, ':lookup' => $lookup.'%']);
			}
			else {
				$attributes = prepared_query::fetch('SELECT DISTINCT a.attribute_key, a.attribute_key_id FROM ck_attributes a LEFT JOIN ck_merchandising_family_unit_variances fv ON a.attribute_key_id = fv.attribute_id AND fv.family_unit_id = :family_unit_id WHERE fv.family_unit_variance_id IS NULL AND a.attribute_key LIKE :lookup ORDER BY a.attribute_key ASC', cardinality::SET, [':family_unit_id' => $family_unit_id, ':lookup' => $lookup.'%']);
			}

			foreach ($attributes as $attribute) {
				$label_attribute = preg_replace('/('.$lookup.')/i', '<strong>$1</strong>', $attribute['attribute_key']);
				$row = [
					'result_id' => $attribute['attribute_key_id'],
					'field_value' => $attribute['attribute_key'],
					'result_label' => $label_attribute.' [ATTR]',
					'variance_type' => 'attribute'
				];

				$response['results'][] = $row;
			}

			// we currently only have one field that we could reasonably want to vary on
			if (preg_match('/^'.$lookup.'/i', 'condition') && !prepared_query::fetch('SELECT fv.family_unit_variance_id FROM ck_merchandising_family_unit_variances fv WHERE fv.field_name = :field_name AND fv.family_unit_id = :family_unit_id', cardinality::SINGLE, [':field_name' => 'condition', ':family_unit_id' => $family_unit_id])) {
				$response['results'][] = [
					'result_id' => 'condition',
					'field_value' => 'condition',
					'result_label' => preg_replace('/('.$lookup.')/i', '<strong>$1</strong>', 'condition').' [FLD]',
					'variance_type' => 'field'
				];
			}

			echo json_encode($response);
			exit();

			break;
		case 'add-variance':
			$response = [];

			try {
				$family = new ck_family_unit($_POST['family_unit_id']);

				$data = [
					'family_unit_id' => $family->id(),
				];

				if ($_POST['variance_type'] == 'attribute') $data['attribute_id'] = $_POST['variance_key'];
				else $data['field_name'] = $_POST['variance_key'];

				$family->create_variance($data);

				$response['success'] = 1;
			}
			catch (Exception $e) {
				$response['success'] = 0;
				$response['error'] = $e->getMessage();
			}

			echo json_encode($response);
			exit();

			break;
		case 'edit-variance':
			try {
				$family = new ck_family_unit($_POST['family_unit_id']);

				$family_unit_variance_id = $_POST['family_unit_variance_id'];

				$data = [
					'name' => !empty($_POST['name'])?$_POST['name']:NULL,
					'descriptor' => !empty($_POST['descriptor'])?$_POST['descriptor']:NULL,
					'group_on' => $__FLAG['group_on']?1:0,
					'sort_order' => !empty($_POST['sort_order'])?$_POST['sort_order']:1,
					'active' => $__FLAG['active']?1:0
				];

				$family->edit_variance($family_unit_variance_id, $data);

				CK\fn::redirect_and_exit('/admin/merchandising-unit-family-detail.php?context=edit&family_unit_id='.$family->id());
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}

			break;
		case 'delete-variance':
			try {
				$family = new ck_family_unit($_POST['family_unit_id']);
				$family->remove_variance($_POST['family_unit_variance_id']);

				CK\fn::redirect_and_exit('/admin/merchandising-unit-family-detail.php?context=edit&family_unit_id='.$family->id());
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}

			break;
		case 'sibling-lookup':
			$response['results'] = [];
			$lookup = preg_replace('/\s/', '', $_REQUEST['ipn']);

			if (strlen($lookup) <= 1) exit();

			$family_unit_id = $_REQUEST['family_unit_id'];

			$ipns = prepared_query::fetch('SELECT DISTINCT psc.stock_id, psc.stock_name as ipn FROM products_stock_control psc LEFT JOIN ck_merchandising_family_unit_siblings fs ON psc.stock_id = fs.stock_id AND fs.family_unit_id = :family_unit_id WHERE fs.family_unit_sibling_id IS NULL AND psc.stock_name LIKE :lookup ORDER BY psc.stock_name ASC', cardinality::SET, [':family_unit_id' => $family_unit_id, ':lookup' => $lookup.'%']);

			foreach ($ipns as $ipn) {
				//$ckipn = new ck_ipn2($ipn['stock_id']);
				$label_ipn = preg_replace('/('.$lookup.')/i', '<strong>$1</strong>', $ipn['ipn']);
				$row = [
					'result_id' => $ipn['stock_id'],
					'field_value' => $ipn['ipn'],
					'result_label' => $label_ipn,
					'context' => 'single'
				];

				$response['results'][] = $row;
			}

			if (!empty($response['results'])) array_unshift($response['results'], ['result_id' => 'all', 'field_value' => $lookup, 'result_label' => 'Add All Listed IPNs', 'context' => 'all', 'lookup' => $lookup]);

			echo json_encode($response);
			exit();

			break;
		case 'add-sibling':
			$response = [];

			try {
				$family = new ck_family_unit($_POST['family_unit_id']);

				if ($context == 'single') {
					$data = [
						'family_unit_id' => $family->id(),
						'stock_id' => $_POST['stock_id'],
						'products_id' => isset($_POST['products_id'])?$_POST['products_id']:NULL
					];

					$family->create_sibling($data);
				}
				elseif ($context == 'all') {
					$ipns = prepared_query::fetch('SELECT DISTINCT psc.stock_id FROM products_stock_control psc LEFT JOIN ck_merchandising_family_unit_siblings fs ON psc.stock_id = fs.stock_id AND fs.family_unit_id = :family_unit_id WHERE fs.family_unit_sibling_id IS NULL AND psc.stock_name LIKE :lookup ORDER BY psc.stock_name ASC', cardinality::SET, [':family_unit_id' => $family->id(), ':lookup' => $_POST['lookup'].'%']);

					foreach ($ipns as $ipn) {
						$data = [
							'family_unit_id' => $family->id(),
							'stock_id' => $ipn['stock_id']
						];

						$family->create_sibling($data);
					}
				}

				$response['success'] = 1;
			}
			catch (Exception $e) {
				$response['success'] = 0;
				$response['error'] = $e->getMessage();
			}

			echo json_encode($response);
			exit();

			break;
		case 'edit-sibling':
			try {
				$family = new ck_family_unit($_POST['family_unit_id']);

				$family_unit_sibling_id = $_POST['family_unit_sibling_id'];

				$data = [
					'model_number' => !empty($_POST['model_number'])?$_POST['model_number']:NULL,
					'name' => !empty($_POST['name'])?$_POST['name']:NULL,
					'description' => !empty($_POST['description'])?$_POST['description']:NULL,
					'active' => $__FLAG['active']?1:0,
					'products_id' => !empty($_POST['products_id'])?$_POST['products_id']:NULL
				];

				$family->edit_sibling($family_unit_sibling_id, $data);

				CK\fn::redirect_and_exit('/admin/merchandising-unit-family-detail.php?context=edit&family_unit_id='.$family->id());
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}

			break;
		case 'delete-sibling':
			try {
				$family = new ck_family_unit($_POST['family_unit_id']);
				$family->remove_sibling($_POST['family_unit_sibling_id']);

				CK\fn::redirect_and_exit('/admin/merchandising-unit-family-detail.php?context=edit&family_unit_id='.$family->id());
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}

			break;
		case 'edit-variance-attribute-value':
			try {
				prepared_query::execute('INSERT INTO ck_merchandising_family_unit_variance_options (family_unit_variance_id, value, alias, sort_order) VALUES (:family_unit_variance_id, :value, :alias, :sort_order) ON DUPLICATE KEY UPDATE alias=VALUES(alias), sort_order=VALUES(sort_order)', [':family_unit_variance_id' => $_POST['family_unit_variance_id'], ':value' => $_POST['attribute_value'], ':alias' => !empty($_POST['alias'])?$_POST['alias']:NULL, ':sort_order' => !empty($_POST['sort_order'])?$_POST['sort_order']:NULL]);

				CK\fn::redirect_and_exit('/admin/merchandising-unit-family-detail.php?context=edit&family_unit_id='.$_POST['family_unit_id']);
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}
			break;
	}
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

$content_map->context = $context;

if ($context == 'edit' && !empty($_GET['family_unit_id'])) {
	$family = new ck_family_unit($_GET['family_unit_id']);

	$fam = $family->get_header();
	if ($fam['homogeneous']) $fam['homogeneous?'] = 1;
	if ($fam['active']) $fam['active?'] = 1;

	$content_map->family = $fam;

	$wanted_vars = [];

	if ($family->has_variances()) {
		$content_map->variances = [];
		foreach ($family->get_variances(NULL) as $var) {
			$wanted_vars[] = $var['key'];

			if ($var['group_on']) $var['var-group-on?'] = 1;
			$var['group_on'] = $var['group_on']?'Y':'N';
			if ($var['active']) $var['var-active?'] = 1;
			$var['active'] = $var['active']?'Y':'N';

			if ($family->has_grouped_variance()) {
				$var['variance-options'] = [];
				if ($var['target'] == 'field' && $var['key'] == 'condition') {
					$options = prepared_query::fetch('SELECT DISTINCT psc.conditions, fuvo.alias, fuvo.sort_order FROM products_stock_control psc JOIN ck_merchandising_family_unit_siblings fus ON psc.stock_id = fus.stock_id LEFT JOIN ck_merchandising_family_unit_variance_options fuvo ON psc.conditions = fuvo.value AND fuvo.family_unit_variance_id = :family_unit_variance_id WHERE fus.family_unit_id = :family_unit_id AND fus.active = 1 ORDER BY CASE WHEN fuvo.family_unit_variance_option_id IS NOT NULL THEN fuvo.sort_order ELSE psc.conditions END ASC', cardinality::SET, [':family_unit_id' => $family->id(), ':family_unit_variance_id' => $var['family_unit_variance_id']]);

					foreach ($options as &$option) {
						$option['attribute_value_db'] = $option['conditions'];
						$option['attribute_value'] = ck_ipn2::get_condition_name($option['conditions']);
						$option['attribute_value_safe'] = preg_replace('/\W+/', '', $option['attribute_value']);
					}

					$var['variance-options'] = $options;
				}
				elseif ($var['target'] == 'attribute') {
					$options = prepared_query::fetch('SELECT DISTINCT a.value as attribute_value, fuvo.alias, fuvo.sort_order FROM ck_merchandising_family_unit_siblings fus JOIN ck_attributes a ON fus.stock_id = a.stock_id AND a.internal = 0 JOIN ck_merchandising_family_unit_variances fv ON fus.family_unit_id = fv.family_unit_id AND a.attribute_key_id = fv.attribute_id LEFT JOIN ck_merchandising_family_unit_variance_options fuvo ON fv.family_unit_variance_id = fuvo.family_unit_variance_id AND a.value = fuvo.value WHERE fus.family_unit_id = :family_unit_id AND fus.active = 1 AND fv.family_unit_variance_id = :family_unit_variance_id ORDER BY CASE WHEN fuvo.family_unit_variance_option_id IS NOT NULL THEN fuvo.sort_order ELSE a.value END ASC', cardinality::SET, [':family_unit_id' => $family->id(), ':family_unit_variance_id' => $var['family_unit_variance_id']]);

					foreach ($options as &$option) {
						$option['attribute_value_db'] = $option['attribute_value'];
						$option['attribute_value_safe'] = preg_replace('/\W+/', '', $option['attribute_value']);
					}

					$var['variance-options'] = $options;
				}
			}

			$content_map->variances[] = $var;
		}
	}

	if ($family->has_siblings()) {
		$content_map->siblings = [];
		foreach ($family->get_siblings(NULL) as $sib) {
			$ipn = new ck_ipn2($sib['stock_id']);
			if ($ipn->has_primary_container()) {
				$primary_container = $ipn->get_primary_container();
				if ($primary_container['redirect']) {
					$container = ck_merchandising_container_manager::instantiate($primary_container['container_type_id'], $primary_container['container_id']);
					
					if (!empty($sib['products_id'])) $listing = new ck_product_listing($sib['products_id']);
					else $listing = $ipn->get_default_listing();
					
					if ($container->is_active()) $sib['primary_link'] = ['url' => $container->get_url($listing), 'description' => $primary_container['container_type'].': '.$container->get_title()];
				}
			}

			if (!empty($sib['products_id']) && $dl = new ck_product_listing($sib['products_id'])) $sib['default_model_number'] = $dl->get_header('products_model');
			$sib['ipn'] = $ipn->get_header('ipn');
			if ($sib['active']) $sib['sib-active?'] = 1;
			$sib['active'] = $sib['active']?'Y':'N';

			$found = TRUE;
			foreach ($wanted_vars as $wanted_var) {
				if ($attrs = $family->get_sibling_attributes($sib['stock_id'])) {
					foreach ($attrs as $attr) {
						if ($wanted_var == $attr['attribute_key']) continue 2;
					}
				}
				$found = FALSE;
			}

			if (!$found) $sib['missing-attributes'] = 1;

			if ($ipn->has_listings()) {
				$sib['has_products?'] = 1;
				$sib['products'][] = NULL;
				
				$listings = $ipn->get_listings();
				$listing_count = count($listings);

				if ($listing_count > 1) $sib['has_multiple_products?'] = 1;

				for ($i = 0; $i < $listing_count; $i ++) {
					$sib['products'][$i] = ['products_id' => $listings[$i]->id(), 'products_model' => $listings[$i]->get_header('products_model')];
					if ($listings[$i]->id() == $sib['products_id']) $sib['products'][$i]['selected?'] = 1;
				}
			}

			$content_map->siblings[] = $sib;
		}
	}
}

$cktpl->content('includes/templates/page-merchandising-unit-family-detail.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
