<?php require_once('ipn_editor_top.php');

$this_tab = 'ipn-sales';

$subtabs = [
	'ipn-add-to-cart' => ['name' => 'Add To Cart', 'uri' => '/admin/ipn_editor_add_to_cart_tab.php'],
	'ipn-history' => ['name' => 'Sales History', 'uri' => '/admin/ipn_editor_history_tab.php'],
	'ipn-special_pricing' => ['name' => 'Special Pricing', 'uri' => '/admin/ipn_editor_special_pricing_tab.php'],
	'ipn-invoicing_history' => ['name' => 'Invoicing History', 'uri' => '/admin/ipn_editor_invoicing_history_tab.php'],
];

$selectedTab = !empty($_GET['selectedTab'])?$_GET['selectedTab']:'ipn-manage';

if (is_numeric($selectedTab) && empty($_GET['selectedSubTab'])) {
	$selectedTab = (int) $selectedTab;
	if (!empty($ipn) && !$ipn->is('serialized') && $selectedTab >= 8) $selectedTab++;
	switch ($selectedTab) {
		case 2:
			$_GET['selectedSubTab'] = 'ipn-history';
			break;
	}
}

$selectedSubTab = !empty($_GET['selectedSubTab'])&&isset($subtabs[$_GET['selectedSubTab']])?$_GET['selectedSubTab']:'ipn-history'; ?>
<style>
</style>
<div class="<?= $ipn->is('serialized')?'serialized':''; ?>">
	<input type="hidden" id="selectedSalesTab" value="<?= $selectedSubTab; ?>">
	<ul id="ipn-editor-sales-tabs" class="noPrint">
		<?php foreach ($subtabs as $id => $subtab) { ?>
		<li id="<?= $id; ?>" class="tab"><?= $subtab['name']; ?></li>
		<?php } ?>
	</ul>
	<div id="ipn-editor-sales-tabs-body" style="padding: 10px;">
		<?php foreach ($subtabs as $id => $subtab) { ?>
		<div id="<?= $id; ?>-content" class="ck-tab-content" data-target="<?= $subtab['uri']; ?>" data-selectedTab="<?= $this_tab; ?>" data-loaded="0"></div>
		<?php } ?>
	</div>
	<script>
		jQuery('#ipn-editor-sales-tabs-body .ck-tab-content').on('tabs:open', load_tab);

		jQuery('#ipn-editor-sales-tabs .tab').on('dblclick', reload_tab);

		var ipn_editor_sales_tabs = new ck.tabs({
			tabs_id: 'ipn-editor-sales-tabs',
			tab_bodies_id: 'ipn-editor-sales-tabs-body',
			default_tab_index: jQuery('#selectedSalesTab').val(),
			content_suffix: '-content'
		});
	</script>
</div>