<?php
require('includes/application_top.php');

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

if ($family_containers = ck_family_container::get_all_family_containers()) {
	$content_map->family_containers = [];
	foreach ($family_containers as $family) {
		$fam = $family->get_header();
		if ($family->is_active()) $fam['url'] = $family->get_url();
		$fam['family_name'] = $family->get_family_unit()->get_header('name');
		$fam['active'] = $fam['active']?'Y':'N';
		$fam['date_created'] = $fam['date_created']->format('Y-m-d');
		$content_map->family_containers[] = $fam;
	}
}

//$content_map->totals[] = $totals;

$cktpl->content('includes/templates/page-merchandising-family-containers.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
