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
						'name' => $_POST['name'],
						'url' => !empty($_POST['url'])?$_POST['url']:NULL,
						'url_with_categories' => $__FLAG['url_with_categories']?1:0,
						'meta_title' => !empty($_POST['meta_title'])?$_POST['meta_title']:NULL,
						'meta_description' => !empty($_POST['meta_description'])?$_POST['meta_description']:NULL,
						'meta_keywords' => !empty($_POST['meta_keywords'])?$_POST['meta_keywords']:NULL,
						'summary' => $_POST['summary'],
						'description' => $_POST['description'],
						'details' => $_POST['details'],
						'template_id' => $_POST['template_id'],
						'nav_template_id' => $_POST['nav_template_id'],
						'offer_template_id' => $_POST['offer_template_id'],
						'show_lifetime_warranty' => $__FLAG['show_lifetime_warranty']?1:0,
						'family_unit_id' => $_POST['family_unit_id'],
						'default_family_unit_sibling_id' => !empty($_POST['default_family_unit_sibling_id'])?$_POST['default_family_unit_sibling_id']:NULL,
						'admin_only' => $__FLAG['admin_only']?1:0,
						'active' => 0
					],
					'primary_container' => @$_POST['primary_container'],
					'primary_container_with_listing' => @$_POST['primary_container_with_listing']
				];

				if (!empty($_FILES['default_image'])) {
					$default_image = !empty($_FILES['default_image']['name'])&&$_FILES['default_image']['error']===0?$_FILES['default_image']:NULL;

					$dim = imagesizer::dim($default_image['tmp_name']);

					if ($dim['width'] != imagesizer::$map['archive']['width'] || $dim['height'] != imagesizer::$map['archive']['height']) {
						throw new Exception('Default image dimensions are not correct.');
					}

					$baseref = picture_audit::$imgfolder.'/archive/fam-'.$default_image['name'];
					@rename($default_image['tmp_name'], $baseref);

					imagesizer::resize($baseref, imagesizer::$map['lrg'], picture_audit::$imgfolder, 'p/fam-'.$default_image['name'], TRUE);
					imagesizer::resize($baseref, imagesizer::$map['med'], picture_audit::$imgfolder, 'p/fam-'.imagesizer::ref_med($default_image['name']), TRUE);
					imagesizer::resize($baseref, imagesizer::$map['sm'], picture_audit::$imgfolder, 'p/fam-'.imagesizer::ref_sm($default_image['name']), TRUE);

					$data['default_image'] = 'p/'.$default_image['name'];
					$data['default_image_medium'] = 'p/'.imagesizer::ref_med($default_image['name']);
					$data['default_image_small'] = 'p/'.imagesizer::ref_sm($default_image['name']);
				}

				if (empty($data['header']['name'])) throw new Exception('Family Containter Name cannot be empty.');
				if (empty($data['header']['summary'])) throw new Exception('Summary cannot be empty.');
				if (empty($data['header']['description'])) throw new Exception('Description cannot be empty.');
				if (empty($data['header']['details'])) throw new Exception('Details cannot be empty.');
				if (empty($data['header']['template_id'])) throw new Exception('Please select a Template.');
				if (empty($data['header']['nav_template_id'])) throw new Exception('Please select a Nav Template.');
				if (empty($data['header']['family_unit_id'])) throw new Exception('Please select a Family Unit.');

				$family_container = ck_family_container::create($data);

				CK\fn::redirect_and_exit('/admin/merchandising-family-container-detail.php?context=edit&family_container_id='.$family_container->id());
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}

			break;
		case 'edit':
			try {
				$family_container = new ck_family_container($_POST['family_container_id']);

				$data = [
					'name' => $_POST['name'],
					'url' => !empty($_POST['url'])?$_POST['url']:NULL,
					'url_with_categories' => $__FLAG['url_with_categories']?1:0,
					'meta_title' => !empty($_POST['meta_title'])?$_POST['meta_title']:NULL,
					'meta_description' => !empty($_POST['meta_description'])?$_POST['meta_description']:NULL,
					'meta_keywords' => !empty($_POST['meta_keywords'])?$_POST['meta_keywords']:NULL,
					'summary' => $_POST['summary'],
					'description' => $_POST['description'],
					'details' => $_POST['details'],
					'template_id' => $_POST['template_id'],
					'nav_template_id' => $_POST['nav_template_id'],
					'offer_template_id' => $_POST['offer_template_id'],
					'show_lifetime_warranty' => $__FLAG['show_lifetime_warranty']?1:0,
					'family_unit_id' => $_POST['family_unit_id'],
					'default_family_unit_sibling_id' => !empty($_POST['default_family_unit_sibling_id'])?$_POST['default_family_unit_sibling_id']:NULL,
					'admin_only' => $__FLAG['admin_only']?1:0,
					'active' => $__FLAG['active']?1:0
				];

				if (!empty($_FILES['default_image'])) {
					$default_image = !empty($_FILES['default_image']['name'])&&$_FILES['default_image']['error']===0?$_FILES['default_image']:NULL;

					$dim = imagesizer::dim($default_image['tmp_name']);

					if ($dim['width'] != imagesizer::$map['archive']['width'] || $dim['height'] != imagesizer::$map['archive']['height']) {
						throw new Exception('Default image dimensions are not correct.');
					}

					$baseref = picture_audit::$imgfolder.'/archive/fam-'.$default_image['name'];
					@rename($default_image['tmp_name'], $baseref);

					imagesizer::resize($baseref, imagesizer::$map['lrg'], picture_audit::$imgfolder, 'p/fam-'.$default_image['name'], TRUE);
					imagesizer::resize($baseref, imagesizer::$map['med'], picture_audit::$imgfolder, 'p/fam-'.imagesizer::ref_med($default_image['name']), TRUE);
					imagesizer::resize($baseref, imagesizer::$map['sm'], picture_audit::$imgfolder, 'p/fam-'.imagesizer::ref_sm($default_image['name']), TRUE);

					$data['default_image'] = 'p/'.$default_image['name'];
					$data['default_image_medium'] = 'p/'.imagesizer::ref_med($default_image['name']);
					$data['default_image_small'] = 'p/'.imagesizer::ref_sm($default_image['name']);
				}
				elseif ($__FLAG['remove_default_image']) {
					$data['default_image'] = NULL;
					$data['default_image_medium'] = NULL;
					$data['default_image_small'] = NULL;
				}

				if (empty($data['name'])) throw new Exception('Family Containter Name cannot be empty.');
				if (empty($data['summary'])) throw new Exception('Summary cannot be empty.');
				if (empty($data['description'])) throw new Exception('Description cannot be empty.');
				if (empty($data['details'])) throw new Exception('Details cannot be empty.');
				if (empty($data['template_id'])) throw new Exception('Please select a Template.');
				if (empty($data['nav_template_id'])) throw new Exception('Please select a Nav Template.');
				if (empty($data['family_unit_id'])) throw new Exception('Please select a Family Unit.');

				$family_container->update($data);

				if (!empty($_POST['primary_container_with_listing'])) {
					$family_container->set_as_primary_container_with_listing(in_array($_POST['primary_container_with_listing'], ['canonical', 'redirect']), $_POST['primary_container_with_listing']=='redirect');
				}
				
				if (!empty($_POST['primary_container'])) {
					$family_container->set_as_primary_container(in_array($_POST['primary_container'], ['canonical', 'redirect']), $_POST['primary_container']=='redirect');
				}
				else {
					//$family_container->remove_as_primary_container();
				}

				CK\fn::redirect_and_exit('/admin/merchandising-family-container-detail.php?context=edit&family_container_id='.$family_container->id());
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}

			break;
		case 'add-category':
			try {
				$family_container = new ck_family_container($_POST['family_container_id']);

				$categories_id = $_POST['add-category'];

				$family_container->add_category_relationship($categories_id, $__FLAG['set_default_relationship']);

				CK\fn::redirect_and_exit('/admin/merchandising-family-container-detail.php?context=edit&family_container_id='.$family_container->id());
			}
			catch (Exception $e) {
				$errors = [$e->getMessage()];
			}

			break;
		case 'update-categories':
			try {
				$family_container = new ck_family_container($_POST['family_container_id']);

				if (!empty($_POST['default_relationship'])) $family_container->update_category_relationship($_POST['default_relationship'], TRUE);
				else $family_container->unset_default_category_relationship();

				if (!empty($_POST['delete'])) {
					foreach ($_POST['delete'] as $categories_id => $flag) {
						if (CK\fn::check_flag($flag)) {
							$family_container->remove_category_relationship($categories_id);
						}
					}
				}

				CK\fn::redirect_and_exit('/admin/merchandising-family-container-detail.php?context=edit&family_container_id='.$family_container->id());
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

$content_map->templates = prepared_query::fetch('SELECT * FROM ck_managed_templates WHERE context = :context AND nav = 0', cardinality::SET, [':context' => 'merchandising-containers']);
$content_map->nav_templates = prepared_query::fetch('SELECT * FROM ck_managed_templates WHERE context = :context AND nav = 1', cardinality::SET, [':context' => 'merchandising-containers']);
$content_map->offer_templates = prepared_query::fetch('SELECT * FROM ck_managed_templates WHERE context = :context AND nav = 0', cardinality::SET, [':context' => 'merchandising-containers']);

$families = ck_family_unit::get_active_families();
$content_map->family_units = [];
foreach ($families as $fam) {
	$content_map->family_units[] = $fam->get_header();
}

if ($context == 'edit' && !empty($_GET['family_container_id'])) {
	$family_container = new ck_family_container($_GET['family_container_id']);

	$fam = $family_container->get_header();
	if (empty($fam['url'])) unset($fam['url']);
	$fam['full_url'] = $family_container->get_url();
	if (!$fam['url_with_categories']) unset($fam['url_with_categories']);
	if (empty($fam['meta_title'])) unset($fam['meta_title']);
	if (empty($fam['meta_description'])) unset($fam['meta_description']);
	if (empty($fam['default_image'])) unset($fam['default_image']);
	if (!$fam['show_lifetime_warranty']) unset($fam['show_lifetime_warranty']);
	if (!$fam['admin_only']) unset($fam['admin_only']);
	if (!$fam['active']) unset($fam['active']);

	$content_map->family = $fam;

	foreach ($content_map->templates as &$template) {
		if ($fam['template_id'] == $template['managed_template_id']) $template['selected_template'] = 1;
	}

	foreach ($content_map->nav_templates as &$template) {
		if ($fam['nav_template_id'] == $template['managed_template_id']) $template['selected_template'] = 1;
	}

	foreach ($content_map->offer_templates as &$template) {
		if ($fam['offer_template_id'] == $template['managed_template_id']) $template['selected_template'] = 1;
	}

	foreach ($content_map->family_units as &$unit) {
		if ($fam['family_unit_id'] == $unit['family_unit_id']) $unit['selected_family_unit'] = 1;
	}

	if ($family_container->get_family_unit()->has_siblings()) {
		$content_map->siblings = [];
		foreach ($family_container->get_family_unit()->get_siblings() as $sib) {
			$ipn = new ck_ipn2($sib['stock_id']);
			$sib['ipn'] = $ipn->get_header('ipn');
			if ($fam['default_family_unit_sibling_id'] == $sib['family_unit_sibling_id']) $sib['selected_sibling'] = 1;
			$content_map->siblings[] = $sib;
		}
	}

	$cats = ck_listing_category::get_select_navigator_category_list();

	$content_map->encoded_top_level = json_encode($cats['top_level']);
	$content_map->encoded_selections = json_encode($cats['selections']);

	if ($family_container->has_categories()) {
		$content_map->categories = [];
		foreach ($family_container->get_categories() as $category) {
			$cat = [];
			$cat['categories_id'] = $category['categories_id'];
			$cat['category'] = $category['category']->get_name_path();
			if ($category['default_relationship']) $cat['default_relationship'] = 1;

			$content_map->categories[] = $cat;
		}
	}
}

$cktpl->content('includes/templates/page-merchandising-family-container-detail.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
