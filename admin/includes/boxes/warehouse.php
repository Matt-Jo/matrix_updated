<?php
$boxitems = [
	'sort' => [0 => 'individual'],
	'individual' => [
		['file' => 'orders', 'text' => 'Orders'],
		['file' => 'priority-pos.php', 'text' => 'Priority PO\'s'],
		['file' => 'open-receiving-sessions.php', 'text' => 'Open Receiving Sessions'],
		['file' => 'orders_waiting_for_po2.php', 'text' => 'Hot PO\'s'],
		['file' => 'inventory_hold_list.php', 'text' => 'Inventory Hold'],
		['file' => 'inventory-disposition.php', 'text' => 'Inventory Disposition'],
		['file' => 'receiving-worklist.php', 'text' => 'Receiving Worklist'],
		['file' => 'testing-worklist.php', 'text' => 'Testing Worklist'],
		['file' => 'conditioning-worklist.php', 'text' => 'Conditioning Worklist'],
		['file' => 'paint-worklist.php', 'text' => 'Paint Worklist'],
		//['file' => 'ipn_inventory_form.php', 'text' => 'Inventory Form'],
		//['file' => 'warehouse/search_order.php', 'text' => 'Shipping Weight'],
		['file' => 'ipn_weight_update.php', 'text' => 'Weight Update'],
		['file' => 'cycle-count.php', 'text' => 'Cycle Count'],
		['file' => 'physical-count.php', 'text' => 'Physical Count'],
		['file' => 'put-away.php', 'text' => 'Put Away'],
		['file' => 'ipn-count-discrepancies.php', 'text' => 'IPN Count Discrepancies'],
		['file' => 'freebie_guide/', 'text' => 'Freebie Guide']
		/*['file' => 'zend/admin/viewpositions', 'text' => 'Shipping Positions'],
		['file' => 'zend/admin/vieworders', 'text' => 'Orders In Shipping'],
		['file' => 'zend/hold/holdlist', 'text' => 'Hold Orders'],
		['file' => 'zend/hold/abortlist', 'text' => 'Abort Orders'],
		['file' => 'zend/', 'text' => 'Shipping Index']*/
	],
]; ?>
<h3 id="warehouse"><a href="#">Warehouse</a></h3>
<div>
	<?php foreach ($boxitems['sort'] as $boxgroup => $boxtype) {
		if ($boxtype == 'groups') { ?>
	<span class="subsection"><?= $boxgroup; ?></span>
		<?php } ?>
	<ul>
		<?php $boxset = $boxtype=='individual'?$boxitems['individual']:$boxitems['groups'][$boxgroup];
		foreach ($boxset as $boxlink) {
			if (tep_admin_check_boxes($boxlink['file'])) { ?>
		<li><a href="/admin/<?= $boxlink['file']; ?>?<?= @$boxlink['parameters']; ?>selected_box=warehouse"><?= $boxlink['text']; ?></a></li>
			<?php }
		} ?>
	</ul>
	<?php } ?>
</div>
