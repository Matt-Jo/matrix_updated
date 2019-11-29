<?php
require_once('ipn_editor_top.php');

$uom_descriptions = prepared_query::fetch("SELECT DISTINCT uom_description FROM ck_upc_assignments WHERE uom_description != '' ORDER BY uom_description", cardinality::SET);
$provenances = prepared_query::fetch("SELECT DISTINCT provenance FROM (SELECT DISTINCT provenance FROM ck_upc_assignments WHERE provenance != '' UNION SELECT 'CK' as provenance UNION SELECT 'Manufacturer' as provenance UNION SELECT 'Vendor' as provenance) p ORDER BY provenance", cardinality::SET);
$purposes = prepared_query::fetch("SELECT DISTINCT purpose FROM (SELECT DISTINCT purpose FROM ck_upc_assignments WHERE purpose != '' UNION SELECT 'ASIN' as purpose UNION SELECT 'WalMart' as purpose UNION SELECT 'GTIN' as purpose) p ORDER BY purpose", cardinality::SET);
?>
<style>
	.upc_table { border-collapse:collapse; }
	.upc_table th, .upc_table td { font-size:14px; border:1px solid #000; }
	.upc-even { background-color:#ccc; }
	.uom { text-align:right; }

	#upc_add, .edit-upc-fields { display:none; }
	#upc_add td, .edit-upc-fields td { text-align:center; }
</style>

<form id="ipn-upc-form" action="/admin/ipn_editor.php" method="post">
	<input type="hidden" name="stock_id" value="<?= $ipn->id(); ?>">
	<input type="hidden" name="action" value="save_upc">
	<input type="hidden" name="sub-action" id="ipn-upc-sub-action" value="">
	<p class="main" style="font-size:14px;font-weight:bold;">UPCs</p>
	<table cellpadding="4px" cellspacing="0" border="0" class="upc_table">
		<thead>
			<tr>
				<th>UPC</th>
				<th class="uom">UOM [#]</th>
				<th>Relationship</th>
				<th>Source</th>
				<th>Purpose</th>
				<th>Action</th>
			</tr>
		</thead>
		<tbody id="upc_list">
			<?php if ($ipn->has_upcs()) {
				foreach ($ipn->get_upcs() as $idx => $upc) {
					if (empty($upc['active'])) continue; ?>
			<tr id="upc_row_<?= $upc['upc_assignment_id']; ?>" class="upc-<?= $idx%2==0?'even':'odd'; ?>" data-upc-id="<?= $upc['upc_assignment_id']; ?>">
				<td id="upc-upc-<?= $upc['upc_assignment_id']; ?>"><?= $upc['upc']; ?></td>
				<td class="uom">
					<?= $upc['uom_description']; ?>
					[<?= $upc['unit_of_measure']; ?>]
				</td>
				<td>
					<?= $upc['relationship']; ?>
					<?= !empty($upc['related_object'])?'['.$upc['related_object'].']':''; ?>
				</td>
				<td><?= $upc['provenance']; ?></td>
				<td><?= $upc['purpose']; ?></td>
				<td>
					<button class="edit-upc" data-upc-id="<?= $upc['upc_assignment_id']; ?>">Edit</button>
					<button class="remove-upc" type="submit" name="action_button[<?= $upc['upc_assignment_id']; ?>]" value="remove" data-upc-id="<?= $upc['upc_assignment_id']; ?>">Remove</button>
				</td>
			</tr>
			<tr id="edit-upc_row_<?= $upc['upc_assignment_id']; ?>" class="edit-upc-fields upc-<?= $idx%2==0?'even':'odd'; ?>" data-upc-id="<?= $upc['upc_assignment_id']; ?>">
				<td><?= $upc['upc']; ?></td>
				<td class="uom">
					<input type="text" id="uom_ac-<?= $upc['upc_assignment_id']; ?>" class="uom_ac" data-qty-field="uom_qty-<?= $upc['upc_assignment_id']; ?>" name="uom_description[<?= $upc['upc_assignment_id']; ?>]" placeholder="Description" style="width:125px;" value="<?= $upc['uom_description']; ?>" data-default="<?= $upc['uom_description']; ?>">
					[<input type="text" id="uom_qty-<?= $upc['upc_assignment_id']; ?>" name="unit_of_measure[<?= $upc['upc_assignment_id']; ?>]" placeholder="QTY" style="width:50px;" value="<?= $upc['unit_of_measure']; ?>" data-default="<?= $upc['unit_of_measure']; ?>">]
				</td>
				<td>
					<input type="text" id="relationship_ac-<?= $upc['upc_assignment_id']; ?>" class="relationship_ac" name="target_resource[<?= $upc['upc_assignment_id']; ?>]" placeholder="Relationship" style="width:125px;" value="<?= $upc['relationship']; ?>" data-default="<?= $upc['relationship']; ?>"><br>
					[<input type="text" id="relationship_name_ac-<?= $upc['upc_assignment_id']; ?>" class="relationship_name_ac" data-reltype-field="relationship_ac-<?= $upc['upc_assignment_id']; ?>" data-autocomplete-value-field="relationship_id_ac-<?= $upc['upc_assignment_id']; ?>" name="target_resource_name[<?= $upc['upc_assignment_id']; ?>]" placeholder="Which?" value="<?= $upc['related_object']; ?>" data-default="<?= $upc['related_object']; ?>">]
					<input type="hidden" id="relationship_id_ac-<?= $upc['upc_assignment_id']; ?>" class="relationship_id_ac" name="target_resource_id[<?= $upc['upc_assignment_id']; ?>]" value="<?= $upc['target_resource_id']; ?>" data-default="<?= $upc['target_resource_id']; ?>">
				</td>
				<td><input type="text" id="provenance_ac-<?= $upc['upc_assignment_id']; ?>" class="provenance_ac" name="provenance[<?= $upc['upc_assignment_id']; ?>]" placeholder="Source" style="width:125px;" value="<?= $upc['provenance']; ?>" data-default="<?= $upc['provenance']; ?>"></td>
				<td><input type="text" id="purpose_ac-<?= $upc['upc_assignment_id']; ?>" class="purpose_ac" name="purpose[<?= $upc['upc_assignment_id']; ?>]" placeholder="Purpose" style="width:125px;" value="<?= $upc['purpose']; ?>" data-default="<?= $upc['purpose']; ?>"></td>
				<td>
					<button type="submit" name="action_button[<?= $upc['upc_assignment_id']; ?>]" value="update">Update</button>
					<button class="cancel-edit-upc" data-upc-id="<?= $upc['upc_assignment_id']; ?>">Cancel</button>
				</td>
			</tr>
				<?php }
			} ?>
		</tbody>
		<tbody>
			<tr id="upc_add">
				<td><input type="text" id="new_upc" name="upc[new]" placeholder="UPC - blank to autofill"></td>
				<td>
					<input type="text" id="uom_ac-new" class="uom_ac" data-qty-field="uom_qty-new" name="uom_description[new]" placeholder="Description" style="width:125px;">
					[<input type="text" id="uom_qty-new" name="unit_of_measure[new]" placeholder="QTY" style="width:50px;">]
				</td>
				<td>
					<input type="text" id="relationship_ac-new" class="relationship_ac" name="target_resource[new]" placeholder="Relationship" style="width:125px;"><br>
					[<input type="text" id="relationship_name_ac-new" class="relationship_name_ac" data-reltype-field="relationship_ac-new" data-autocomplete-value-field="relationship_id_ac-new" name="target_resource_name[new]" placeholder="Which?">]
					<input type="hidden" id="relationship_id_ac-new" class="relationship_id_ac" name="target_resource_id[new]">
				</td>
				<td><input type="text" id="provenance_ac-new" class="provenance_ac" name="provenance[new]" placeholder="Source" value="CK" style="width:125px;"></td>
				<td><input type="text" id="purpose_ac-new" class="purpose_ac" name="purpose[new]" placeholder="Purpose" style="width:125px;"></td>
				<td><button type="submit" name="action_button[new]" value="create">Create</button></td>
			</tr>
		</tbody>
	</table>
	<a href="#" class="button-link" id="add-upc">ADD UPC</a>
</form>

<?php foreach ($uom_descriptions as $uom_description) { ?>
<input type="hidden" class="uom_description-value" value="<?= $uom_description['uom_description']; ?>">
<?php } ?>
<?php foreach ($provenances as $provenance) { ?>
<input type="hidden" class="provenance-value" value="<?= $provenance['provenance']; ?>">
<?php } ?>
<?php foreach ($purposes as $purpose) { ?>
<input type="hidden" class="purpose-value" value="<?= $purpose['purpose']; ?>">
<?php } ?>

<script>
	jQuery('.edit-upc').on('click', function(e) {
		e.preventDefault();
		var upc_assignment_id = jQuery(this).attr('data-upc-id');
		jQuery('#upc_row_'+upc_assignment_id).hide();
		jQuery('#edit-upc_row_'+upc_assignment_id).show();
	});

	jQuery('.cancel-edit-upc').on('click', function(e) {
		e.preventDefault();
		var upc_assignment_id = jQuery(this).attr('data-upc-id');
		jQuery('#edit-upc_row_'+upc_assignment_id).hide();
		jQuery('#upc_row_'+upc_assignment_id).show();
		jQuery('#edit-upc_row_'+upc_assignment_id).find('input').each(function() {
			jQuery(this).val(jQuery(this).attr('data-default'));
		});
	});

	jQuery('.remove-upc').on('click', function(e) {
		if (!confirm('Are you sure you want to remove UPC ['+jQuery('#upc-upc-'+jQuery(this).attr('data-upc-id')).html()+'] from this IPN?')) e.preventDefault();
	});

	jQuery('#add-upc').on('click', function(e) {
		e.preventDefault();
		jQuery('#upc_add').toggle();
	});

	jQuery('#new_upc').keyup(function() {
		if (jQuery(this).val() == '' && jQuery('#provenance_ac').val() == '') jQuery('#provenance_ac').val('CK');
		else if (jQuery(this).val() != '' && jQuery('#provenance_ac').val() == 'CK') jQuery('#provenance_ac').val('');
	});

	var uom_descriptions = [];
	jQuery('.uom_description-value').each(function() {
		uom_descriptions.push({ result_id: jQuery(this).val().replace(/[^a-zA-Z0-9]/, ''), result_label: jQuery(this).val(), field_value: jQuery(this).val() });
	});

	var uom_ac;
	uom_ac = new ck.autocomplete(null, {
		$fields: [jQuery('.uom_ac')],
		minimum_length: 0,
		request_onclick: true,
		autocomplete_field_name: 'uom_field',
		auto_select_single: false,
		local_results: function(data) {
			var regex = new RegExp('^'+data.uom_field, 'i');
			var response = { results: uom_descriptions.filter(function(value) { return value.result_id.match(regex) }) };
			return response;
		},
		select_result: function(result) {
			var num = parseInt(result.field_value);
			if (!isNaN(num)) jQuery('#'+jQuery(uom_ac.autocomplete_request_element).attr('data-qty-field')).val(num);
		}
	});

	var relationship_descriptions = [
		{ result_id: 'ipn', result_label: 'IPN', field_value: 'IPN' },
		{ result_id: 'listing', result_label: 'Product Listing', field_value: 'Product Listing' },
		{ result_id: 'vendor', result_label: 'Vendor', field_value: 'Vendor' }
	];

	var relationship_ac = new ck.autocomplete(null, {
		$fields: [jQuery('.relationship_ac')],
		minimum_length: 0,
		request_onclick: true,
		autocomplete_field_name: 'relationship_field',
		auto_select_single: false,
		local_results: function(data) {
			var regex = new RegExp('^'+data.relationship_field, 'i');
			var response = { results: relationship_descriptions.filter(function(value) { return value.result_id.match(regex) }) };
			return response;
		},
		select_result: function(result) {
			jQuery('.relationship_name_ac').val('');
			jQuery('.relationship_id_ac').val('');
		}
	});

	var relationship_name_ac;
	relationship_name_ac = new ck.autocomplete('/admin/ipn_editor.php?stock_id='+jQuery('#stock_id').val(), {
		$fields: [jQuery('.relationship_name_ac')],
		minimum_length: 0,
		request_onclick: true,
		autocomplete_field_name: 'relationship_name_field',
		auto_select_single: false,
		autocomplete_action: 'relationship-lookup',
		process_additional_fields: function(data) {
			data.stock_id = jQuery('#stock_id').val();
			data.relationship_type_field = jQuery('#'+jQuery(relationship_name_ac.autocomplete_request_element).attr('data-reltype-field')).val();
			return data;
		},
		//hidden_value_field: 'relationship_id_ac'
	});

	var provenance_descriptions = [];
	jQuery('.provenance-value').each(function() {
		provenance_descriptions.push({ result_id: jQuery(this).val().replace(/[^a-zA-Z0-9]/, ''), result_label: jQuery(this).val(), field_value: jQuery(this).val() });
	});

	var provenance_ac = new ck.autocomplete(null, {
		$fields: [jQuery('.provenance_ac')],
		minimum_length: 0,
		request_onclick: true,
		autocomplete_field_name: 'provenance_field',
		auto_select_single: false,
		local_results: function(data) {
			var regex = new RegExp('^'+data.provenance_field, 'i');
			var response = { results: provenance_descriptions.filter(function(value) { return value.result_id.match(regex) }) };
			return response;
		}
	});

	var purpose_descriptions = [];
	jQuery('.purpose-value').each(function() {
		purpose_descriptions.push({ result_id: jQuery(this).val().replace(/[^a-zA-Z0-9]/, ''), result_label: jQuery(this).val(), field_value: jQuery(this).val() });
	});

	var purpose_ac = new ck.autocomplete(null, {
		$fields: [jQuery('.purpose_ac')],
		minimum_length: 0,
		request_onclick: true,
		autocomplete_field_name: 'purpose_field',
		auto_select_single: false,
		local_results: function(data) {
			var regex = new RegExp('^'+data.purpose_field, 'i');
			var response = { results: purpose_descriptions.filter(function(value) { return value.result_id.match(regex) }) };
			return response;
		}
	});

	ck.button_links();
</script>
