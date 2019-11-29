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

if ($families = ck_family_unit::get_active_families()) {
	$content_map->families = [];
	foreach ($families as $family) {
		$fam = $family->get_header();
		$fam['type'] = $fam['homogeneous']?'Homogeneous':'Heterogeneous';
		$fam['active'] = $fam['active']?'Y':'N';
		$fam['date_created'] = $fam['date_created']->format('m/d/Y');
		$content_map->families[] = $fam;
	}
}

//$content_map->totals[] = $totals;

$cktpl->content('includes/templates/page-merchandising-unit-families.mustache.html', $content_map);
//---------end body---------------

//---------footer-----------------
$cktpl->close($content_map);
//---------end footer-------------
?>
