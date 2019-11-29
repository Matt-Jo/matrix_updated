<?php
require('includes/application_top.php');
require('includes/functions/faqdesk_general.php');
require(DIR_WS_LANGUAGES.$_SESSION['language'].'/faqdesk_info.php');

ck_config2::preload_legacy('faqdesk');

if (!empty($_GET['faqdeskPath'])) $newsPath = $_GET['faqdeskPath'];
elseif (@$_GET['faqdesk_id'] && empty($_GET['faqdeskPath'])) $newsPath = faqdesk_get_product_path(@$_GET['faqdesk_id']);
else $_GET['faqPath'] = '';

if (strlen($_GET['faqPath']) > 0) {
	$faqPath_array = faqdesk_parse_category_path($_GET['faqPath']	);
	$faqPath = implode('_', $faqPath_array);
	$current_category_id = $faqPath_array[(sizeof($faqPath_array)-1)];
}
else $current_category_id = 0;

$breadcrumb->add('Why CK', '/whyck.php');

$content = 'faqdesk_info';
require('templates/Pixame_v1/main_page.tpl.php');
?>
