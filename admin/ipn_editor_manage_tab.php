<?php require_once('ipn_editor_top.php');

$this_tab = 'ipn-manage';

$subtabs = [
	'ipn-general' => ['name' => 'General', 'uri' => '/admin/ipn_editor_general_tab.php'],
	'ipn-serials' => ['name' => 'Serials', 'uri' => '/admin/ipn_editor_serials_tab.php'],
	'ipn-change_history' => ['name' => 'Change History', 'uri' => '/admin/ipn_editor_change_history_tab.php'],
	'ipn-ledger' => ['name' => 'Ledger', 'uri' => '/admin/ipn_editor_ledger_tab.php'],
];

$selectedTab = !empty($_GET['selectedTab'])?$_GET['selectedTab']:'ipn-manage';

if (is_numeric($selectedTab) && empty($_GET['selectedSubTab'])) {
	$selectedTab = (int) $selectedTab;
	if (!empty($ipn) && !$ipn->is('serialized') && $selectedTab >= 8) $selectedTab++;
	switch ($selectedTab) {
		case 0:
			$_GET['selectedSubTab'] = 'ipn-general';
			break;
		case 5:
			$_GET['selectedSubTab'] = 'ipn-ledger';
			break;
		case 8:
			$_GET['selectedSubTab'] = 'ipn-serials';
			break;
	}
}

$selectedSubTab = !empty($_GET['selectedSubTab'])&&isset($subtabs[$_GET['selectedSubTab']])?$_GET['selectedSubTab']:'ipn-general'; ?>
<style>
	#ipn-serials { display:none; }
	.serialized #ipn-serials { display:inline-block; }
</style>
<div class="<?= $ipn->is('serialized')?'serialized':''; ?>">
	<input type="hidden" id="selectedManageTab" value="<?= $selectedSubTab; ?>">
	<ul id="ipn-editor-manage-tabs" class="noPrint">
		<?php foreach ($subtabs as $id => $subtab) { ?>
		<li id="<?= $id; ?>" class="tab"><?= $subtab['name']; ?></li>
		<?php } ?>
	</ul>
	<div id="ipn-editor-manage-tabs-body" style="padding: 10px;">
		<?php foreach ($subtabs as $id => $subtab) { ?>
		<div id="<?= $id; ?>-content" class="ck-tab-content" data-target="<?= $subtab['uri']; ?>" data-selectedTab="<?= $this_tab; ?>" data-loaded="0"></div>
		<?php } ?>
	</div>
	<script>
		jQuery('#ipn-editor-manage-tabs-body .ck-tab-content').on('tabs:open', load_tab);

		jQuery('#ipn-editor-manage-tabs .tab').on('dblclick', reload_tab);

		var ipn_editor_manage_tabs = new ck.tabs({
			tabs_id: 'ipn-editor-manage-tabs',
			tab_bodies_id: 'ipn-editor-manage-tabs-body',
			default_tab_index: jQuery('#selectedManageTab').val(),
			content_suffix: '-content'
		});
	</script>
</div>