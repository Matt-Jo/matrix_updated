<?php require_once('ipn_editor_top.php');

$this_tab = 'ipn-marketing';

$subtabs = [
	'ipn-merchandising' => ['name' => 'Merchandising', 'uri' => '/admin/ipn_editor_merchandising_tab.php'],
	//'ipn-tiered_pricing' => ['name' => 'Tier Pricing', 'uri' => '/admin/ipn_editor_tier_pricing_tab.php'],
	'ipn-upc_management' => ['name' => 'UPCs', 'uri' => '/admin/ipn_editor_upc_management_tab.php'],
	'ipn-stats' => ['name' => 'Traffic', 'uri' => '/admin/ipn_editor_stats_tab.php'],
];

$selectedTab = !empty($_GET['selectedTab'])?$_GET['selectedTab']:'ipn-manage';

if (is_numeric($selectedTab) && empty($_GET['selectedSubTab'])) {
	$selectedTab = (int) $selectedTab;
	if (!empty($ipn) && !$ipn->is('serialized') && $selectedTab >= 8) $selectedTab++;
	switch ($selectedTab) {
		case 1:
			$_GET['selectedSubTab'] = 'ipn-merchandising';
			break;
		case 13:
			$_GET['selectedSubTab'] = 'ipn-upc_management';
			break;
	}
}

$selectedSubTab = !empty($_GET['selectedSubTab'])&&isset($subtabs[$_GET['selectedSubTab']])?$_GET['selectedSubTab']:'ipn-merchandising'; ?>
<style>
</style>
<div class="<?= $ipn->is('serialized')?'serialized':''; ?>">
	<input type="hidden" id="selectedMarketingTab" value="<?= $selectedSubTab; ?>">
	<ul id="ipn-editor-marketing-tabs" class="noPrint">
		<?php foreach ($subtabs as $id => $subtab) { ?>
		<li id="<?= $id; ?>" class="tab"><?= $subtab['name']; ?></li>
		<?php } ?>
	</ul>
	<div id="ipn-editor-marketing-tabs-body" style="padding: 10px;">
		<?php foreach ($subtabs as $id => $subtab) { ?>
		<div id="<?= $id; ?>-content" class="ck-tab-content" data-target="<?= $subtab['uri']; ?>" data-selectedTab="<?= $this_tab; ?>" data-loaded="0"></div>
		<?php } ?>
	</div>
	<script>
		jQuery('#ipn-editor-marketing-tabs-body .ck-tab-content').on('tabs:open', load_tab);

		jQuery('#ipn-editor-marketing-tabs .tab').on('dblclick', reload_tab);

		var ipn_editor_marketing_tabs = new ck.tabs({
			tabs_id: 'ipn-editor-marketing-tabs',
			tab_bodies_id: 'ipn-editor-marketing-tabs-body',
			default_tab_index: jQuery('#selectedMarketingTab').val(),
			content_suffix: '-content'
		});
	</script>
</div>