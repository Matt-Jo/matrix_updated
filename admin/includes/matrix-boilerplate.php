<?php
$domain = $_SERVER['HTTP_HOST'];
$cdn = '//media.cablesandkits.com';
$static = $cdn.'/static';

$content_map->cdn = $cdn;
$content_map->static_files = $static;
$content_map->context = CONTEXT;

if (empty($meta_title)) $meta_title = $ck_keys->admin_page_title;
$content_map->head = ['title' => $meta_title, 'meta' => ['description' => '']];

$content_map->show_invoiced_stats = tep_admin_check_boxes('stats_invoices.php')?1:0;
if (!empty($_SESSION['greedy-search'])) $content_map->{'greedy-search?'} = 1;
if (tep_admin_check_boxes('po_list.php')) {
	$content_map->{'show_po_search?'} = 1;
	if (in_array($_SESSION['perms']['admin_groups_id'], [7, 29, 18, 20])) $content_map->{'default_search_po?'} = 1;
}

$content_map->system_messages = $messageStack->size>0?$messageStack->output():'';

ob_start();
if (tep_admin_check_boxes('orders.php')) require(DIR_WS_BOXES.'orders.php');
if (tep_admin_check_boxes('customers.php')) require(DIR_WS_BOXES.'customers.php');
if (tep_admin_check_boxes('purchasing.php')) require(DIR_WS_BOXES.'purchasing.php');
if (tep_admin_check_boxes('sales_rep.php')) require(DIR_WS_BOXES.'sales_rep.php');
if (tep_admin_check_boxes('warehouse.php')) require(DIR_WS_BOXES.'warehouse.php');
if (tep_admin_check_boxes('marketing.php')) require(DIR_WS_BOXES.'marketing.php');
if (tep_admin_check_boxes('inventory.php')) require(DIR_WS_BOXES.'inventory.php');
if (tep_admin_check_boxes('merchandising.php')) require(DIR_WS_BOXES.'merchandising.php');
if (tep_admin_check_boxes('accounting.php')) require(DIR_WS_BOXES.'accounting.php');
if (tep_admin_check_boxes('information.php')) require(DIR_WS_BOXES.'information.php');
if (tep_admin_check_boxes('administrator.php')) require(DIR_WS_BOXES.'administrator.php');
if (tep_admin_check_boxes('tools.php')) require(DIR_WS_BOXES.'tools.php');
if (tep_admin_check_boxes('data_manager.php')) require(DIR_WS_BOXES.'data_manager.php');
$content_map->leftnav = ob_get_clean();

$content_map->selected_box = is_string($selectedBox)?"'".$selectedBox."'":'false';
?>