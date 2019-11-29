<?php require_once('ipn_editor_top.php');

$this_tab = 'ipn-purchasing';

$subtabs = [
	'ipn-po_history' => ['name' => 'Purchase History', 'uri' => '/admin/ipn_editor_po_history_tab.php'],
	'ipn-receiving_history' => ['name' => 'Receiving History', 'uri' => '/admin/ipn_editor_receiving_history_tab.php'],
	'ipn-vendors' => ['name' => 'Vendors', 'uri' => '/admin/ipn_editor_vendors_tab.php'],
	'ipn-rfq_history' => ['name' => 'RFQ History', 'uri' => '/admin/ipn_editor_rfq_history_tab.php'],
	'ipn-usage' => ['name' => 'Usage History', 'uri' => '/admin/ipn_editor_usage_tab.php'],
];

$selectedTab = !empty($_GET['selectedTab'])?$_GET['selectedTab']:'ipn-manage';
$selectedSubTab = NULL;

if (is_numeric($selectedTab) && empty($_GET['selectedSubTab'])) {
	$selectedTab = (int) $selectedTab;
	if (!empty($ipn) && !$ipn->is('serialized') && $selectedTab >= 8) $selectedTab++;
	switch ($selectedTab) {
		case 9:
			$_GET['selectedSubTab'] = 'ipn-vendors';
			break;
	}
}

$selectedSubTab = !empty($_GET['selectedSubTab'])&&isset($subtabs[$_GET['selectedSubTab']])?$_GET['selectedSubTab']:'ipn-po_history'; ?>
<style>
</style>
<div class="<?= $ipn->is('serialized')?'serialized':''; ?>">
	<input type="hidden" id="selectedPurchasingTab" value="<?= $selectedSubTab; ?>">
	<ul id="ipn-editor-purchasing-tabs" class="noPrint">
		<?php foreach ($subtabs as $id => $subtab) { ?>
		<li id="<?= $id; ?>" class="tab"><?= $subtab['name']; ?></li>
		<?php } ?>
	</ul>
	<div id="ipn-editor-purchasing-tabs-body" style="padding: 10px;">
		<?php foreach ($subtabs as $id => $subtab) { ?>
		<div id="<?= $id; ?>-content" class="ck-tab-content" data-target="<?= $subtab['uri']; ?>" data-selectedTab="<?= $this_tab; ?>" data-loaded="0"></div>
		<?php } ?>
	</div>
	<script>
		jQuery('#ipn-editor-purchasing-tabs-body .ck-tab-content').on('tabs:open', load_tab);

		jQuery('#ipn-editor-purchasing-tabs .tab').on('dblclick', reload_tab);

		var ipn_editor_purchasing_tabs = new ck.tabs({
			tabs_id: 'ipn-editor-purchasing-tabs',
			tab_bodies_id: 'ipn-editor-purchasing-tabs-body',
			default_tab_index: jQuery('#selectedPurchasingTab').val(),
			content_suffix: '-content'
		});
	</script>
</div>