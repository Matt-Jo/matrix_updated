<?php
require_once('ipn_editor_top.php');

$sortby = isset($_GET['sortby'])?$_GET['sortby']:NULL;
switch ($sortby) {
	case 'date_asc':
		$history_sort = 'ORDER BY pscch.change_date ASC, pscch.change_id ASC';
		break;
	case 'date_desc':
		$history_sort = 'ORDER BY pscch.change_date DESC, pscch.change_id DESC';
		break;
	case 'change_asc':
		$history_sort = 'ORDER BY psccht.name ASC, pscch.change_id ASC';
		break;
	case 'change_desc':
		$history_sort = 'ORDER BY psccht.name DESC, pscch.change_id DESC';
		break;
	default :
		$history_sort = 'ORDER BY pscch.change_date DESC, pscch.change_id DESC';
		break;
}

// grab the history.
/*
 we interpolate the ORDER BY clause directly because there's no danger as it's explicitly set directly above. If we change
 that so that it allows untrusted input, then we'll want to rethink that strategy.
*/
// leaving table alias lest we should add another table to this in the future
$history = prepared_query::fetch("SELECT pscch.*, psccht.*, psccht.name as change_type, pscch.change_user as admin_name, UNIX_TIMESTAMP(pscch.change_date) as tstamp FROM products_stock_control_change_history pscch join products_stock_control_change_history_types psccht on psccht.id = pscch.type_id WHERE pscch.stock_id = ? $history_sort", cardinality::SET, [$ipn->id()]);
?>
<div id="ipn-changes">
	<table id="ipn-change-history" cellpadding="0" cellspacing="0" border="0" class="ck-table-manager">
		<thead>
			<tr>
				<th>Change Date</th>
				<th>Change Type</th>
				<th class="no-sort">Reference</th>
				<th class="no-sort">Previous Value</th>
				<th class="no-sort">New Value</th>
				<th class="no-sort">Change User</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($history as $entry) { ?>
			<tr>
				<td><?= $entry['change_date']; ?></td>
				<td><?= $entry['change_type']; ?></td>
				<td><?= $entry['reference']; ?></td>
				<td><?= $entry['old_value']; ?></td>
				<td><?= $entry['new_value']; ?></td>
				<td><?= $entry['admin_name']; ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<script>
	jQuery('#ipn-change-history').table_manager({
		color_rows: true,
		sortable: true,
		sort_methods: {
			0: 'date',
		},
	});
</script>
