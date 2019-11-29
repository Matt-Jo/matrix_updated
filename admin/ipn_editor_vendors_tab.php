<?php
require_once('ipn_editor_top.php');
?>
<style>
	#vendor_edit { display:none; }
	#vendor_add { display:none; }
	.vend-even { background-color:#ccc; }
	.edit-field { display:none; }
</style>

<form id="ipn-vendor-form" action="/admin/ipn_editor.php" method="post">
	<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
	<input type="hidden" name="action" value="save_vendor">
	<input type="hidden" name="sub-action" id="ipn-vendor-sub-action" value="">
	<input type="hidden" name="vendor_relationship_id" id="ipn-vendor-relationship-id" value="">
	<p class="main" style="font-size:14px;font-weight:bold;">Vendors</p>
	<table cellpadding="4px" cellspacing="0" border="0" class="vendor_table">
		<thead>
			<tr>
				<th class="main" nowrap>Vendor Name</th>
				<th class="main" nowrap>Vendor Price</th>
				<th class="main" nowrap>Vendor P/N</th>
				<th class="main" nowrap>Case Qty</th>
				<th class="main" nowrap>Always Available</th>
				<th class="main" nowrap>Lead Time</th>
				<th class="main" nowrap>Notes</th>
				<th class="main" nowrap>Preferred</th>
				<th class="main" nowrap>Secondary</th>
				<th class="main">Actions</th>
			</tr>
		</thead>
		<tbody id="vendors_list">

			<?php if ($ipn->has_vendors()) {
				foreach ($ipn->get_vendors() as $idx => $vendor) { ?>
			<tr id="vendor_row_<?= $vendor['vendor_relationship_id']; ?>" class="vend-<?= $idx%2==0?'even':'odd'; ?>">
				<td class="main"><?= $vendor['company_name']; ?></td>
				<td class="main">
					<span class="no-edit-field"><?= $vendor['price']; ?></span>
					<span class="edit-field"><input type="text" name="vendors_price[<?= $vendor['vendor_relationship_id']; ?>]" value="<?= $vendor['price']; ?>" data-default="<?= $vendor['price']; ?>"></span>
				</td>
				<td class="main">
					<span class="no-edit-field"><?= $vendor['part_number']; ?></span>
					<span class="edit-field"><input type="text" name="vendors_pn[<?= $vendor['vendor_relationship_id']; ?>]" value="<?= $vendor['part_number']; ?>" data-default="<?= $vendor['part_number']; ?>"></span>
				</td>
				<td class="main">
					<span class="no-edit-field"><?= $vendor['case_qty']; ?></span>
					<span class="edit-field"><input type="text" name="case_qty[<?= $vendor['vendor_relationship_id']; ?>]" value="<?= $vendor['case_qty']; ?>" data-default="<?= $vendor['case_qty']; ?>"></span>
				</td>
				<td class="main">
					<span class="no-edit-field"><?= $vendor['always_available']?'Yes':'No'; ?></span>
					<span class="edit-field"><input type="checkbox" name="always_avail[<?= $vendor['vendor_relationship_id']; ?>]" <?= $vendor['always_available']?'checked':''; ?> data-default="<?= $vendor['always_available']?1:0; ?>"></span>
				</td>
				<td class="main">
					<span class="no-edit-field"><?= $vendor['lead_time']; ?></span>
					<span class="edit-field"><input type="text" name="lead_time[<?= $vendor['vendor_relationship_id']; ?>]" value="<?= $vendor['lead_time']; ?>" data-default="<?= $vendor['lead_time']; ?>"></span>
				</td>
				<td class="main">
					<span class="no-edit-field"><?= $vendor['notes']; ?></span>
					<span class="edit-field"><textarea name="notes[<?= $vendor['vendor_relationship_id']; ?>]"><?= $vendor['notes']; ?></textarea></span>
				</td>
				<td class="main <?= $vendor['preferred']?'preferred':''; ?>" <?= $vendor['preferred']?'id="current_pv_'.$vendor['vendors_id'].'"':''; ?>>
					<span class="no-edit-field"><b style="color:#ff0000"><?= $vendor['preferred']?'Preferred':''; ?></b></span>
					<span class="edit-field"><input type="checkbox" name="preferred[<?= $vendor['vendor_relationship_id']; ?>]" <?= $vendor['preferred']?'checked':''; ?> data-default="<?= $vendor['preferred']?1:0; ?>"></span>
				</td>
				<td class="main">
					<span class="no-edit-field"><?= $vendor['secondary']?'Secondary':''; ?></span>
					<span class="edit-field"><input type="checkbox" name="secondary[<?= $vendor['vendor_relationship_id']; ?>]" <?= $vendor['secondary']?'checked':''; ?> data-default="<?= $vendor['secondary']?1:0; ?>"></span>
				</td>
				<td class="main">
					<?php if (in_array($_SESSION['perms']['admin_groups_id'], [1, 13]) || $_SESSION['perms']['update_serial']) { ?>
					<span class="no-edit-field">
						<a href="#" class="edit-vendor" data-vendor-relationship-id="<?= $vendor['vendor_relationship_id']; ?>"><img src="images/edit.png" alt="Edit" title="Edit"></a>
						<a href="#" class="delete-vendor" data-vendor-relationship-id="<?= $vendor['vendor_relationship_id']; ?>" data-vendor-name="<?= $vendor['company_name']; ?>"><img src="images/delete.png" alt="Delete" title="Delete"></a>
					</span>
					<span class="edit-field">
						<a href="#" class="save-vendor" data-vendor-relationship-id="<?= $vendor['vendor_relationship_id']; ?>"><img src="images/save.png" alt="Save" title="Save"></a>
						<a href="#" class="cancel-vendor" data-vendor-relationship-id="<?= $vendor['vendor_relationship_id']; ?>"><img src="images/arrow_back.png" alt="Cancel" title="Cancel"></a>
					</span>
					<?php } ?>
				</td>
			</tr>
			<?php } 
			} ?>
		</tbody>
		<tbody>
			<tr id="vendor_add">
				<td class="main">
					<input type="text" name="vendors_company_name" id="add_vendors_company_name">
					<div id="vendor_choices" class="autocomplete"></div>
					<input type="hidden" name="vendors_id[new]" id="vendors_id_new" value="">
				</td>
				<td class="main"><input type="text" name="vendors_price[new]" value=""></td>
				<td class="main"><input type="text" name="vendors_pn[new]" value=""></td>
				<td class="main"><input type="text" name="case_qty[new]" value=""></td>
				<td class="main"><input type="checkbox" name="always_avail[new]"></td>
				<td class="main"><input type="text" name="lead_time[new]" value=""></td>
				<td class="main"><textarea name="notes[new]"></textarea></td>
				<td class="main"><input type="checkbox" name="preferred[new]"></td>
				<td class="main"><input type="checkbox" name="secondary[new]"></td>
				<td>
					<a href="#" class="save-vendor" data-vendor-relationship-id="new"><img src="images/save.png" alt="Save" title="Save"></a>
					<a href="#" class="cancel-vendor" data-vendor-relationship-id="new"><img src="images/arrow_back.png" alt="Cancel" title="Cancel"></a>
				</td>
			</tr>
		</tbody>
	</table>
</form>

<a href="#" id="add-vendor-entry" class="add-vendor">(Add Vendor Entry)</a>
<br><br><br>

<p class="main" style="font-size: 14px; font-weight: bold;">Also Purchased From</p>
<table class="vendor_table" cellspacing="0">
	<thead>
		<tr>
			<th class="main" nowrap>Vendor Name</th>
			<th class="main" nowrap>Vendor Email</th>
			<th class="main" nowrap>Vendor Phone Number</th>
			<th class="main" nowrap>Date of last order</th>
			<th class="main" nowrap>Last Purchase Price</th>
		</tr>
	</thead>
	<tbody>
		<?php $purchased_vendors = prepared_query::fetch("SELECT DISTINCT v.vendors_id, v.vendors_company_name, v.vendors_email_address, v.vendors_telephone, pop.po_id, DATE_FORMAT(DATE(po.last_date), '%m-%d-%Y') as last_date, pop.last_cost FROM vendors v JOIN (SELECT po.vendor as vendors_id, MAX(po.creation_date) as last_date FROM purchase_orders po JOIN purchase_order_products pop ON po.id = pop.purchase_order_id WHERE pop.ipn_id = :stock_id GROUP BY po.vendor) po ON v.vendors_id = po.vendors_id JOIN (SELECT po.vendor as vendors_id, po.creation_date, po.id as po_id, MIN(pop.cost) as last_cost FROM purchase_orders po JOIN purchase_order_products pop ON po.id = pop.purchase_order_id WHERE pop.ipn_id = :stock_id GROUP BY po.vendor, po.creation_date, po.id) pop ON v.vendors_id = pop.vendors_id AND po.last_date = pop.creation_date ORDER BY po.last_date DESC", cardinality::SET, [':stock_id' => $ipn->id()]);
		if (!empty($purchased_vendors)) {
			foreach ($purchased_vendors as $idx => $vendor) { ?>
		<tr <?= $idx%2?'':'class="hl"'; ?> id="vendor_row_<?= $vendor['vendors_id']; ?>">
			<td class="main"><?= $vendor['vendors_company_name']; ?></td>
			<td class="main"><?= $vendor['vendors_email_address']; ?></td>
			<td class="main"><?= $vendor['vendors_telephone']; ?></td>
			<td class="main"><?= $vendor['last_date']; ?></td>
			<td class="main" style="text-align:right;"><?= $vendor['last_cost']; ?></td>
		</tr>
		<?php }
		} ?>
	</tbody>
</table>

<script>
	jQuery('.edit-vendor').click(function(e) {
		e.preventDefault();
		var vendor_relationship_id = jQuery(this).attr('data-vendor-relationship-id');

		jQuery('#vendor_row_'+vendor_relationship_id+' .no-edit-field').hide();
		jQuery('#vendor_row_'+vendor_relationship_id+' .edit-field').show();
	});
	jQuery('.delete-vendor').click(function(e) {
		e.preventDefault();
		var vendor_relationship_id = jQuery(this).attr('data-vendor-relationship-id');
		var name = jQuery(this).attr('data-vendor-name');

		if (!confirm('Are you sure you want to remove the entry for '+name+' ?')) return;

		jQuery('#ipn-vendor-sub-action').val('delete');
		jQuery('#ipn-vendor-relationship-id').val(vendor_relationship_id);

		jQuery('#ipn-vendor-form').submit();
	});
	jQuery('.save-vendor').click(function(e) {
		e.preventDefault();
		var vendor_relationship_id = jQuery(this).attr('data-vendor-relationship-id');

		jQuery('#ipn-vendor-sub-action').val(vendor_relationship_id=='new'?'add':'update');
		jQuery('#ipn-vendor-relationship-id').val(vendor_relationship_id);

		jQuery('#ipn-vendor-form').submit();
	});
	jQuery('.cancel-vendor').click(function(e) {
		e.preventDefault();
		var vendor_relationship_id = jQuery(this).attr('data-vendor-relationship-id');

		if (vendor_relationship_id == 'new') {
			jQuery('#vendor_add').hide();
		}
		else {
			jQuery('#vendor_row_'+vendor_relationship_id+' .edit-field').hide();
			jQuery('#vendor_row_'+vendor_relationship_id+' .no-edit-field').show();
		}

		jQuery('#ipn-vendor-sub-action').val('');
		jQuery('#ipn-vendor-relationship-id').val('');
	});

	jQuery('.add-vendor').click(function(e) {
		e.preventDefault();

		jQuery('#vendor_add').show();
	});

	new Ajax.Autocompleter(
		'add_vendors_company_name',
		'vendor_choices',
		'ipn_editor.php',
		{
			method: 'get',
			minChars: 3,
			paramName: 'search_string',
			parameters: 'ajax=1&action=vendor_search',
			afterUpdateElement: function(input, li) {
				jQuery('#vendors_id_new').val(li.id);
			}
		}
	);
</script>
