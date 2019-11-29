let assembly = false;
let in_context_ipn_qty = null;

function center_div(el) {
	if (typeof window.innerHeight != 'undefined') {
		$(el).style.left = Math.round(document.viewport.getScrollOffsets().left + ((window.innerWidth - $(el).getWidth()))/2)+'px';
	}
	else {
		$(el).style.left = Math.round(document.body.scrollLeft + (($$('body')[0].clientWidth - $(el).getWidth()))/2)+'px';
	}
}

function open_popup_dialog(action, ipn_id) {
	switch (action) {
		case 'input':
			method = 'get';
			parameters = { action:'get_input_ipn', stock_id:ipn_id };
			break;
		case 'output':
			method = 'get';
			parameters = { action:'get_output_ipn', stock_id:ipn_id };
			break;
		case 'confirmation':
			method = 'post';
			parameters = new Hash({ action:'get_confirmation' });
			parameters = parameters.merge($('inventory_conversion_form').serialize(true));
			parameters.set("conversion_type", get_conversion_type()); // this isn't set in the serialized form because it's disabled.
			var fields = $$("input[name^='output_cost_serial_']");
			for (var i=0; i<fields.length; i++) {
				if (fields[i].disabled) {
					parameters.set(fields[i].name, fields[i].value);
				}
			}
	}

	new Ajax.Updater('popup_content', 'inventory_conversion_ajax.php', {
		method: method,
		parameters: parameters,
		onComplete: function() {
			center_div('popup_dialog');
			$('popup_dialog').show();
		}
	});
}

function add_ipn_to_input(stock_id, stock_name, average_cost, qty, market_state) {
	update_market_state('add', 'input', market_state);
	//if ipn has already been added, then replace it
	if ($('input_result_table_row_'+stock_id)) {
		// if assembly type is selected then we want to added the previous quantity to the new quantity
		if (assembly) qty += parseInt(jQuery('#input_result_td_qty_'+stock_id).text());
		$('input_result_table_row_'+stock_id).remove();
	}

	if (in_context_ipn_qty !== null && qty > in_context_ipn_qty) {
		alert('There are only '+in_context_ipn_qty+' in stock.');
		in_context_ipn_qty = null;
		return;
	}
	in_context_ipn_qty = null;

	if (!qty) qty=0;

	$('input_result_table').insert('<tr id="input_result_table_row_'+stock_id+'" class="ipn_input_tr"><td class="main ipn_input_name">'+stock_name+'<input type="hidden" name="input_ipn_id[]" value="'+stock_id+'"><input type="hidden" name="input_ipn_qty_'+stock_id+'" value="'+qty+'"><input type="hidden" name="input_ipn_avg_cost['+stock_id+']" value="'+average_cost+'"><input type="hidden" name="input_market_state[]" class="input-market-state" value="'+market_state+'"></td><td class="main">n/a</td><td class="main ipn_input_qty" id="input_result_td_qty_'+stock_id+'" align="right">'+qty+'</td><td class="main input_cost" align="right">'+average_cost+'</td></tr>');

	$('input_result').show();
	$('ipn_input_autocomplete').clear();
	if ($F('conversion_type')=='oneone' || $F('conversion_type')=='onemany') {
		$('ipn_input_autocomplete').disable();
	}

	if (!assembly || jQuery('#output_results_body tr').length <= 0) $('column_output').show();
	else $('column_output').hide();

	$('conversion_type').disable();
	update_costs();
}

function remove_ipn_from_input(stock_id, market_state) {
	update_market_state('remove', 'input', market_state);
	if ($('input_result_table_row_'+stock_id)) {
		$('input_result_table_row_'+stock_id).remove();
		$('input_result_div_'+stock_id).remove();
	}
}

function add_serial_to_input(serial, serial_id, stock_id, stock_name, cost, transfer_price, market_state) {
	update_market_state('add', 'input', market_state);
	if ($('input_result_table_row_'+stock_id+'_'+serial_id)) return;

	if ($('input_ipn_id_'+stock_id)) {
		$('input_result_table').insert('<tr id="input_result_table_row_'+stock_id+'_'+serial_id+'" class="ipn_input_tr"><td class="main ipn_input_name">'+stock_name+'<input type="hidden" name="input_ipn_serials['+stock_id+'][]" value="'+serial_id+'"><input type="hidden" name="input_serial_cost['+serial_id+']" value="'+cost+'"><input type="hidden" name="input_market_state[]" class="input-market-state" value="'+market_state+'"></td><td class="main" align="right">'+serial+'</td><td class="main ipn_input_qty" align="right">1</td><td class="main input_cost" align="right">'+cost+'</td></tr>');
		$('input_ipn_qty_'+stock_id).value = parseInt($F('input_ipn_qty_'+stock_id)) + 1;
	}
	else {
		$('input_result_table').insert('<tr id="input_result_table_row_'+stock_id+'_'+serial_id+'" class="ipn_input_tr"><td class="main ipn_input_name">'+stock_name+'<input id="input_ipn_id_'+stock_id+'" type="hidden" name="input_ipn_id[]" value="'+stock_id+'"><input id="input_ipn_qty_'+stock_id+'" type="hidden" name="input_ipn_qty_'+stock_id+'" value="1"><input type="hidden" name="input_serial_cost['+serial_id+']" value="'+cost+'"><input type="hidden" name="input_market_state[]" class="input-market-state" value="'+market_state+'"><input type="hidden" name="input_ipn_serials['+stock_id+'][]" value="'+serial_id+'"></td><td class="main" align="right">'+serial+'</td><td class="main ipn_input_qty" align="right">1</td><td class="main input_cost" align="right">'+cost+'</td></tr>');
	}

	$('input_result').show();
	$('ipn_input_autocomplete').clear();

	if ($F('conversion_type') == 'oneone' || $F('conversion_type') == 'onemany') {
		$('ipn_input_autocomplete').disable();
	}

	// intentionally not including the market state param here. The serial is being added to the output, but a output ipn hasn't been chosen yet
	add_serial_to_output(stock_name, serial_id, serial, stock_id, cost, transfer_price);

	if (!assembly || jQuery('#output_results_body tr').length <= 0) $('column_output').show();
	else $('column_output').hide();

	$('conversion_type').disable();
	update_costs();
}

function remove_serial_from_input(serial_id, stock_id, market_state) {
	update_market_state('remove', 'input', market_state);
	if ($('input_result_table_row_'+stock_id+'_'+serial_id)) {
		$('input_result_table_row_'+stock_id+'_'+serial_id).remove()
	}

	jQuery('#input_ipn_qty_'+stock_id).val(jQuery('#input_ipn_qty_'+stock_id).val() - 1);
	remove_serial_from_output(serial_id, market_state);
	update_costs();
}

function add_serial_to_output(stock_name, serial_id, serial, stock_id, cost, transfer_price, market_state=null) {
	// there is one case where we don't want to change the market state and that's when a serial is added to the output, but is not the output ipn
	if (market_state) update_market_state('add', 'output', market_state);
	var merged_field = "";
	if (get_conversion_type() == 'manyone') {
		merged_field = "<td class='main merge_column'><input type='radio' id='merged' name='merged' value='"+serial_id+"' onchange='update_costs();'/></td>";
	}

	var output_ipn = "";
	var output_ipn_id = "";
	if (get_conversion_type() == 'manymany') {
		output_ipn = stock_name;
		output_ipn_id = stock_id;
	}

	$('output_results_body').insert('<tr id="output_serial_row_'+serial_id+'" class="ipn_output_tr serial-output-tr" data-serial="'+serial_id+'"><td class="main">'+stock_name+'</td><td class="main">'+serial+': <input type="text" id="serial_autocomplete_'+serial_id+'" class="new-serial-ipn" value="'+output_ipn+'"><span id="clear-ipn-'+serial_id+'" class="clear-ipn" data-serial-id="'+serial_id+'" data-market-state="'+market_state+'" style="display:none; padding:2px; cursor:pointer;">X</span><div id="output_serial_'+serial_id+'_choices" class="autocomplete" style="border: 1px solid #000; display:none; background-color: #fff; z-index: 100;"></div><input type="hidden" name="output_serial_id[]" value="'+serial_id+'"><input class="output_serial_ipn" type="hidden" id="output_serial_ipn_'+serial_id+'" name="output_serial_'+serial_id+'" value="'+output_ipn_id+'"><input type="hidden" class="output-market-state" name="output_market_state[]" value="'+market_state+'"></td><td class="main" align="right">1<input type="hidden" class="output_qty" name="output_serial_qty_'+serial_id+'" value="1"></td><td class="main output_cost" align="right"><input type="text" class="output_cost" size="5" id="output_cost_serial_'+serial_id+'" name="output_cost_serial_'+serial_id+'" value="'+cost+'" onchange="update_costs()"> <span class="transfer_price">/ <input type="text" class="transfer_price" size="5" id="transfer_price_serial+'+serial_id+'" name="transfer_price_serial_'+serial_id+'" value="'+transfer_price+'"></span></td>'+merged_field+'</tr>');


	if (transfer_price != '') jQuery('.transfer_price').show();

	new Ajax.Autocompleter('serial_autocomplete_'+serial_id, 'output_serial_'+serial_id+'_choices', "inventory_conversion_ajax.php", {
		method: 'get',
		minChars: 3,
		paramName: 'search_string',
		parameters: 'action=ipn_search&serial=1&avg_cost=1',
		afterUpdateElement: function(input, li) {
			id = li.id.split('_')[0];
			orig_cost = li.id.split('_')[1];
			$('output_serial_ipn_'+serial_id).value = id;
			jQuery('#serial_autocomplete_'+serial_id).attr('disabled', true);
			jQuery('#clear-ipn-'+serial_id).show();
			update_market_state('add', 'output', li.dataset.market_state);
		}
	});

	if (assembly && jQuery('input:radio[name=merged]').filter(':checked').length == 0) {
		// if nothing is selected then we want to default select the first one. otherwise we'll ride with the user selection
		jQuery('input:radio[name=merged]').first().attr('checked', true);
	}

	$('output_result').show();
}

function remove_serial_from_output(serial_id, market_state) {
	update_market_state('remove', 'output', market_state);
	jQuery('#output_serial_row_'+serial_id).remove();
}

function add_ipn_to_output(stock_id, serial_id, market_state) {
	update_market_state('add', 'output', market_state);
	if ($('output_ipn_tr_'+serial_id)) {
		$('output_ipn_tr_'+serial_id).remove();
	}

	new Ajax.Updater('output_result_table','inventory_conversion_ajax.php', {
		method: 'get',
		parameters: { action: 'get_output_ipn', stock_id: stock_id, serial_id: serial_id },
		insertion: 'bottom',
		onComplete: function() {
			$('output_result').show();
			$('output_result_table').show();
			$('ipn_output_autocomplete').clear();
			update_costs();
		}
	});

	if ($F('conversion_type') == 'oneone' || $F('conversion_type') == 'manyone' || $F('conversion_type') == 'assembly') {
		$('ipn_output_autocomplete').disable();
	}

	$('conversion_type').disable();
}

function update_costs() {
	var input_cost = $$('tr.ipn_input_tr').inject(0, function(a, el) {
		return parseFloat(el.down('td.input_cost').innerHTML) * parseInt(el.down('td.ipn_input_qty').innerHTML) +a;
	});

	$('input_costs').update(input_cost.toFixed(2));

	if ($$('input[type=radio][name=merged])').length < 2) {
		$$('.merge_column').each(function(item) {
			item.hide();
		});
	}
	else {
		$$('.merge_column').each(function(item) {
			item.show();
		});
	}

	if ($$('input:checked[type=radio][name=merged])').length == 1) {
		var merged_serial_id = $$('input:checked[type=radio][name=merged]')[0].value;
		var fields = $$("input[name^='output_cost_serial_']");

		for (var i = 0; i < fields.length; i++) {
			fields[i].value = '0';
			fields[i].disabled = 'disabled';
		}

		$("output_cost_serial_" + merged_serial_id).value = input_cost.toFixed(2);
		$("output_cost_serial_" + merged_serial_id).disabled = '';
	}

	var output_cost = $$('tr.ipn_output_tr').inject(0, function(a, el) {
		return parseFloat(el.down('input.output_cost').value) *  parseInt(el.down('input.output_qty').value) + a;
	});

	$('output_costs').update(output_cost.toFixed(2));

	if (input_cost.toFixed(2) == output_cost.toFixed(2)) {
		$('complete_button').enable();
	}
	else{
		$('complete_button').disable();
	}
}

function submit_form() {
	if (jQuery('#input-market-state').val() == 2 && jQuery('#output-market-state').val() == 0) {
		alert ('You can NOT convert a Grade B IPN to anything besides a Grade A IPN -- check your form and try again');
		return false;
	}
	$('inventory_conversion_form').submit();
}

function get_conversion_type() {
	var box = $('conversion_type');
	// conversion type assembly operates the same was as manyone - the only difference is the ability to lookup ipns or serials
	if (box.options[box.selectedIndex].value == 'assembly') return 'manyone'
	return box.selectedIndex>=0?box.options[box.selectedIndex].value:undefined;
}
let active_market_states_input = [];
let active_market_states_output = [];
function update_market_state(add_or_remove, column, market_state) {
	if (add_or_remove == 'add') {
		let current_input_market_state = jQuery('#input-market-state').val();
		let current_output_market_state = jQuery('#output-market-state').val();

		if (column == 'input') active_market_states_input.push(market_state);
		else if (column == 'output') active_market_states_output.push(market_state);
	}
	else if (add_or_remove == 'remove') {
		if (column == 'output') active_market_states_output.splice(active_market_states_output.indexOf(market_state), 1);
		else if (column == 'input') active_market_states_input.splice(active_market_states_output.indexOf(market_state), 1);
	}
	// apply the highest value to the output
	jQuery('#output-market-state').val(Math.max.apply(null, active_market_states_output));
	// apply the lowest value to the input
	jQuery('#input-market-state').val(Math.min.apply(null, active_market_states_input));
}

jQuery('.serial-select').live('click', function(e) {
	let serial_id = jQuery(this).attr('data-serial_id');
	let serial = jQuery(this).attr('data-serial');
	let stock_id = jQuery(this).attr('data-stock_id');
	let ipn = jQuery(this).attr('data-ipn');
	let cost = jQuery(this).attr('data-cost');
	let transfer_price = jQuery(this).attr('data-transfer_price');
	let market_state = jQuery(this).attr('data-market_state');

	if (jQuery(this).is(':checked')) add_serial_to_input(serial, serial_id, stock_id, ipn, cost, transfer_price, market_state);
	else remove_serial_from_input(serial_id, stock_id, market_state);
});

jQuery('.done-button').live('click', function(e) {
	jQuery('#popup_dialog').hide();
	jQuery('#ipn_output_autocomplete').focus();
});

jQuery('#conversion_type').live('change', function () {
	if (jQuery(this).val() == 'assembly') {
		assembly = true;
		jQuery('#ipn-or-serial-input-column').show();
		jQuery('#ipn-input-column').hide();
	}
	else {
		jQuery('#ipn-or-serial-input-column').hide();
		jQuery('#ipn-input-column').show();
	}
});

jQuery('.clear-ipn').live('click', function () {
	let serial_id = jQuery(this).attr('data-serial-id');
	let market_state = jQuery(this).attr('data-market-state');
	jQuery('#serial_autocomplete_'+serial_id).attr('disabled', false);
	jQuery('#serial_autocomplete_'+serial_id).val('');
	update_market_state('remove', 'output', market_state);
});