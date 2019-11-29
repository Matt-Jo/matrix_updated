<?php
require('includes/application_top.php');
require_once('includes/functions/po_alloc.php');

function insert_inventory_conversion_adjustment($inventory_adjustment_group_id, $stock_id, $old_value, $new_value, $cost, $serial_id=NULL) {
	$admin_id = $_SESSION['login_id'];
	if (!$serial_id) $serial_id = 'NULL';

	if ($old_value == $new_value) $type = 7; // rounding error
	elseif ($old_value > $new_value) $type = 6; // negative adjustment
	else $type = 5; // positive adjustment

	// all conversions are reason code 9: conversion
	prepared_query::execute('INSERT INTO inventory_adjustment (ipn_id, serial_id, scrap_date, admin_id, inventory_adjustment_type_id, inventory_adjustment_reason_id, old_qty, new_qty, cost, inventory_adjustment_group_id) VALUES (:stock_id, :serial_id, NOW(), :admin_id, :type, 9, :old_value, :new_value, :cost, :inventory_adjustment_group_id)', [':stock_id' => $stock_id, ':serial_id' => $serial_id, ':admin_id' => $admin_id, ':type' => $type, ':old_value' => $old_value, ':new_value' => $new_value, ':cost' => $cost, ':inventory_adjustment_group_id' => $inventory_adjustment_group_id]);
}

$errors = [];
// first things, first. We want to double check that there is no market state mismatch and that this conversion doesn't break any rules
if (isset($_POST['input_ipn_id'])) {
	$output_market_state = 2;
	$input_market_state = 0;

	foreach ($_POST['input_ipn_id'] as $i => $stock_id) {
		$market_state = prepared_query::fetch('SELECT c.market_state FROM products_stock_control psc LEFT JOIN conditions c ON c.conditions_id = psc.conditions WHERE psc.stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $stock_id]);
		if ($market_state == 2) {
			$input_market_state = $market_state;
			continue;
		}
	}

	if (!empty($_POST['output_ipn_id'])) {
		foreach ($_POST['output_ipn_id'] as $i => $stock_id) {
			$market_state = prepared_query::fetch('SELECT c.market_state FROM products_stock_control psc LEFT JOIN conditions c ON c.conditions_id = psc.conditions WHERE psc.stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $stock_id]);
			if ($market_state == 0) {
				$output_market_state = $market_state;
				continue;
			}
		}
	}

	if (!empty($_POST['output_serial_id'])) {
		foreach ($_POST['output_serial_id'] as $i => $serial_id) {
			$serial = new ck_serial($serial_id);
			$market_state = prepared_query::fetch('SELECT c.market_state FROM products_stock_control psc LEFT JOIN conditions c ON c.conditions_id = psc.conditions WHERE psc.stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $serial->get_header('stock_id')]);

			if ($market_state == 0) {
				$output_market_state = $market_state;
				continue;
			}
		}
	}
	// if the difference between the two are greater than one than we have a mismatch error. We can safely move up one market_state, but can' exceed that. So if this conditional still equates to true than we know we are more than 1 market state away
	if ($output_market_state == 0 && $input_market_state == 2) $errors[] = 'A market state of two can not go to 0';
}

if (empty($errors) && isset($_POST['input_ipn_id'])) {

	// get the conversion adjustment group id
	$inventory_adjustment_group_id = prepared_query::insert('INSERT INTO inventory_adjustment_groups (entered) VALUES (NOW())');
	$ipn_chn_id = prepared_query::insert('INSERT INTO ipn_change_history_notes (note) VALUES (:note)', [':note' => $_POST['completion_notes']]);
	//process all the non serialized ipns in the input column
	// JS: actually, this processes *all* IPNs in the input column, serialized or not. Not sure if that's desired or not.
	// The input serial id is passed through as well, and needs to be recorded as part of the inventory conversion adjustment
	foreach ($_POST['input_ipn_id'] as $i => $stock_id) {
		//get old values
		$old_values = prepared_query::fetch('SELECT average_cost, stock_quantity FROM products_stock_control WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $stock_id]);
		$old_average_cost = $old_values['average_cost']?$old_values['average_cost']:0;
		$old_quantity = $old_values['stock_quantity'];

		$quantity = $_POST['input_ipn_qty_'.$stock_id];
		$new_quantity = $old_quantity - $quantity;

		prepared_query::execute('UPDATE products_stock_control psc SET psc.stock_quantity = :qty WHERE psc.stock_id = :stock_id', [':qty' => $new_quantity, ':stock_id' => $stock_id]);
		prepared_query::execute('UPDATE products p, products_stock_control psc SET p.products_quantity = psc.stock_quantity WHERE p.stock_id = psc.stock_id AND psc.stock_id = :stock_id', [':stock_id' => $stock_id]);

		add_ipn_change_history('conv', $stock_id, $_SESSION['login_id'], 1, $stock_id, $quantity, $ipn_chn_id);
		insert_psc_change_history($stock_id, 'Inventory Conversion: Loss', $old_quantity, $new_quantity);

		// when I figured out this block processed all IPNs, not just non-serialized product, I added this to appropriately capture serialized adjustments
		// the qty change was tracked appropriately given the current database structure, which doesn't track specific serials in the product_stock_control_change_history
		if (isset($_POST['input_ipn_serials']) && isset($_POST['input_ipn_serials'][$stock_id])) {
			foreach ($_POST['input_ipn_serials'][$stock_id] as $serial_id) {
				$serial = new ck_serial($serial_id);
				insert_inventory_conversion_adjustment($inventory_adjustment_group_id, $stock_id, $old_quantity, $new_quantity, $serial->get_current_history('cost'), $serial_id);
			}
		}
		else {
			insert_inventory_conversion_adjustment($inventory_adjustment_group_id, $stock_id, $old_quantity, $new_quantity, $old_average_cost);
		}

		//MMD - remove warehouse allocations if needed
		po_alloc_check_and_remove_warehouse_by_ipn($stock_id);
	}

	//process all the non serialized ipns in the output column
	if (isset($_POST['output_ipn_id'])) {
		foreach ($_POST['output_ipn_id'] as $i => $stock_id) {
			$old_values = prepared_query::fetch('SELECT average_cost, stock_quantity FROM products_stock_control WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $stock_id]);
			$old_average_cost = $old_values['average_cost'];
			$old_quantity = $old_values['stock_quantity'];

			// figure the rounded cost and use that, since the database would round it anyway
			$quantity = $_POST['output_ipn_qty_'.$stock_id];
			$average_cost = $_POST['output_cost_'.$stock_id];
			$rounded_cost = round($average_cost, 2);
			$new_quantity = $quantity + $old_quantity;

			$new_average_cost = (($old_average_cost * $old_quantity) + ($quantity * $rounded_cost)) / $new_quantity;

			prepared_query::execute('UPDATE products_stock_control psc SET psc.average_cost = :avg_cost, psc.stock_quantity = :qty where psc.stock_id = :stock_id', [':avg_cost' => $new_average_cost, ':qty' => $new_quantity, ':stock_id' => $stock_id]);
			prepared_query::execute('UPDATE products p, products_stock_control psc SET p.products_quantity = psc.stock_quantity WHERE p.stock_id = psc.stock_id AND psc.stock_id = :stock_id', [':stock_id' => $stock_id]);

			add_ipn_change_history('conv', $stock_id, $_SESSION['login_id'], 1, $stock_id, $quantity, $ipn_chn_id);
			insert_psc_change_history($stock_id, 'Inventory Conversion: Gain', $old_quantity, $new_quantity);
			insert_inventory_conversion_adjustment($inventory_adjustment_group_id, $stock_id, $old_quantity, $new_quantity, $rounded_cost);

			// handle rounding errors:
			$total_output_cost = $average_cost * $quantity;
			$total_output_cost_rounded = $rounded_cost * $quantity;
			$rounding_error = $total_output_cost_rounded - $total_output_cost;
			if ($rounding_error != 0) {
				// if there's no rounding error, don't record it. Otherwise, positive or negative, we need the record
				insert_inventory_conversion_adjustment($inventory_adjustment_group_id, $stock_id, 0, 0, $rounding_error);
			}

			//MMD - remove warehouse allocations if needed
			po_alloc_check_and_remove_warehouse_by_ipn($stock_id);
		}
	}

	//process all the serialized items - move them from the old to the new ipns
	if (isset($_POST['output_serial_id'])) {
		//first we construct the list of serials we lost if there was a merge
		// we will use this to make a note the psc change history table
		$merged_serials = [];
		$new_serial_details = '';

		if (!empty($_POST['merged'])) {
			foreach ($_POST['output_serial_id'] as $i => $serial_id) {
				$serial = new ck_serial($serial_id);
				if ($_POST['merged'] != $serial_id) {
					$merged_serials[] = $serial->get_header('serial_number');
				}
				else {
					$new_serial_details = $serial->get_ipn()->get_header('ipn').'/'.$serial->get_header('serial_number');
				}
			}
		}

		foreach ($_POST['output_serial_id'] as $i => $serial_id) {
			$new_stock_id = $_POST['output_serial_'.$serial_id];

			//Now update the qty/cost of the new ipn
			$old_values = prepared_query::fetch('SELECT average_cost, stock_quantity FROM products_stock_control WHERE stock_id = :stock_id', cardinality::ROW, [':stock_id' => $new_stock_id]);
			$old_average_cost = $old_values['average_cost'];
			$old_quantity = $old_values['stock_quantity'];

			//special handling for merged serials
			if (isset($_POST['merged']) && $_POST['merged'] != $serial_id) {
				$serial = new ck_serial($serial_id);
				$old_cost = $serial->get_current_history('cost');
				prepared_query::execute('UPDATE serials s, serials_history sh SET s.ipn = :stock_id, s.status = 9, sh.cost = 0 WHERE s.id = :serial_id AND sh.serial_id = s.id', [':stock_id' => $new_stock_id, ':serial_id' => $serial_id]);

				add_ipn_change_history('merge', $new_stock_id, $_SESSION['login_id'], 1, $new_stock_id, 1, $ipn_chn_id);
				insert_psc_change_history($new_stock_id, 'Inventory Conversion: Serial Merged', implode(',', $merged_serials), $new_serial_details);
				insert_psc_change_history($new_stock_id, 'Inventory Conversion: Serial Cost', $serial->get_header('serial_number').': '.number_format($old_cost, 2), '0.00');
				insert_inventory_conversion_adjustment($inventory_adjustment_group_id, $new_stock_id, '0', '0', '0', $serial_id);

				continue;
			}

			// figure the rounded cost and use that, since the database would round it anyway
			// in almost all scenarios, this cost won't cause a rounding issue, but we don't mess around with accounting. Track the rounding issue whether it ever happens or not
			$quantity = 1;
			$serial_cost = $_POST['output_cost_serial_'.$serial_id];
			$transfer_price = is_numeric($_POST['transfer_price_serial_'.$serial_id])?$_POST['transfer_price_serial_'.$serial_id]:NULL;
			$rounded_cost = round($serial_cost, 2);
			$new_quantity = $quantity + $old_quantity;

			$serial = new ck_serial($serial_id);
			$old_cost = $serial->get_current_history('cost');
			$new_average_cost = (($old_average_cost * $old_quantity) + ($quantity * $rounded_cost)) / $new_quantity;

			prepared_query::execute('UPDATE serials s JOIN serials_history sh ON sh.serial_id = s.id SET s.ipn = :stock_id, sh.cost = :cost, sh.transfer_price = :transfer_price, sh.transfer_date = NOW() where s.id = :serial_id', [':stock_id' => $new_stock_id, ':cost' => $rounded_cost, ':serial_id' => $serial_id, ':transfer_price' => $transfer_price]);
			prepared_query::execute('UPDATE products_stock_control psc SET psc.stock_quantity = :qty, psc.average_cost = :cost WHERE psc.stock_id = :stock_id', [':qty' => $new_quantity, ':cost' => $new_average_cost, ':stock_id' => $new_stock_id]);
			prepared_query::execute('UPDATE products p, products_stock_control psc SET p.products_quantity = psc.stock_quantity WHERE p.stock_id = psc.stock_id AND psc.stock_id = :stock_id', [':stock_id' => $new_stock_id]);
			prepared_query::execute('UPDATE inventory_hold SET stock_id = :stock_id WHERE serial_id = :serial_id', [':stock_id' => $new_stock_id, 'serial_id' => $serial_id]);

			add_ipn_change_history('conv', $new_stock_id, $_SESSION['login_id'], 1, $new_stock_id, 1, $ipn_chn_id);

			// The loss/input side was tracked in the input processing block above
			if (!empty($_POST['merged']) && $_POST['merged'] == $serial_id) {
				insert_psc_change_history($new_stock_id, 'Inventory Conversion: Serial Merged', implode(',', $merged_serials), $new_serial_details);
				insert_psc_change_history($new_stock_id, 'Inventory Conversion: Serial Cost', $serial->get_header('serial_number').': '.number_format($old_cost, 2), number_format($rounded_cost, 2));
			}
			else {
				insert_psc_change_history($new_stock_id, 'Inventory Conversion: Gain', $old_quantity, $new_quantity);
			}
			insert_inventory_conversion_adjustment($inventory_adjustment_group_id, $new_stock_id, $old_quantity, $new_quantity, $rounded_cost, $serial_id);

			// handle rounding errors:
			// since the variables are set almost exactly the same way, we process this the same as figuring the rounding error in the output processing above
			// the main difference is that quantity is guaranteed to be 1 (for now), per line 130
			$total_output_cost = $serial_cost * $quantity;
			$total_output_cost_rounded = $rounded_cost * $quantity;
			$rounding_error = $total_output_cost_rounded - $total_output_cost;
			if ($rounding_error != 0) {
				// if there's no rounding error, don't record it. Otherwise, positive or negative, we need the record
				insert_inventory_conversion_adjustment($inventory_adjustment_group_id, $new_stock_id, 0, 0, $rounding_error, $serial_id);
			}

			//MMD - remove warehouse allocations if needed
			po_alloc_check_and_remove_warehouse_by_ipn($new_stock_id);
		}
	}

	prepared_query::execute('UPDATE inventory_hold ih JOIN serials s ON ih.serial_id = s.id SET ih.stock_id = s.ipn WHERE ih.stock_id != s.ipn');

	CK\fn::redirect_and_exit('/admin/inventory_conversion.php');
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<link rel="stylesheet" type="text/css" href="ipn_conversion/inventory_conversion.css">
	<script language="javascript" src="includes/menu.js"></script>
	<style>
		#ipn-or-serial-input-column { display:none; }
	</style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script language="javascript" src="includes/general.js"></script>
	<script language="javascript" src="includes/javascript/inventory_conversion.js?v=3"></script>
	<!-- header_eof //-->
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="2">
					<tr>
						<td>
							<form method="post" action="inventory_conversion.php" id="inventory_conversion_form">
								<!-- input market state and output market state default values are the extreme match. 0 can convert to 2. this will only be updated if an ipn is added to the column that is greater than input or less than output -->
								<input type="hidden" id="input-market-state" name="input_market_state" value="0">
								<input type="hidden" id="output-market-state" name="output_market_state" value="2">
								<?php include('ipn_conversion/input_dialog.php'); ?>
								<table border="0" width="100%" cellspacing="0" cellpadding="0">
									<tr>
										<td>
											<div style="width: 98%; border-bottom: 1px solid #000; padding-bottom: 10px;">
												<div class="pageHeading">Inventory Conversion</div>
												<br>
												<div class="main">
													Type:
													<select id="conversion_type" name="conversion_type">
														<option value="onemany">One to Many</option>
														<option value="manyone">Many to One</option>
														<option value="oneone">One to One</option>
														<option value="manymany">Many to Many</option>
														<option value="assembly">Assembly</option>
													</select>
												</div>
											</div>
											<?php if (!empty($errors)) { ?>
												<?php foreach ($errors as $error) { ?>
												<div style="background-color:red; color:#fff; border-radius:10px;">
													<?= $error; ?>
												</div>
												<?php } ?>
											<?php } ?>
											<div class="column_container">
												<!-- Input Column !-->
												<div class="conversion_column">
													<div class="column_header">Input IPN's</div>
													<div id="ipn-input-column" class="column_input">
														<div>Input IPN:</div>
														<div>
															<input id="ipn_input_autocomplete" type="text" size="20">
															<div id="ipn_input_choices" class="autocomplete ipn_auto" style="display:none"></div>
														</div>
													</div>
													<!-- this block is hidden by default and will be displayed in replace of the one
													before it when "assembly" is selected from the type dropdown -->
													<div id="ipn-or-serial-input-column" class="column_input">
														<div>Input lookup:</div>
														<div>
															<input id="ipn_or_serial_input_autocomplete" type="text" size="20">
															<div id="ipn_or_serial_input_choices" class="autocomplete ipn_auto" style="display:none"></div>
														</div>
													</div>
													<script type="text/javascript">
														new Ajax.Autocompleter(
															'ipn_input_autocomplete',
															"ipn_input_choices",
															"inventory_conversion_ajax.php",
															{
																method: 'get',
																minChars: 3,
																paramName: 'search_string',
																parameters: 'action=ipn_search',
																afterUpdateElement: function(input, li) {
																	open_popup_dialog('input', li.id);
																}
														});

														new Ajax.Autocompleter(
															'ipn_or_serial_input_autocomplete',
															"ipn_or_serial_input_choices",
															"inventory_conversion_ajax.php",
															{
																method: 'get',
																minChars: 3,
																paramName: 'search_string',
																parameters: 'action=ipn_or_serial_search',
																afterUpdateElement: function(input, li) {
																	if (li.dataset.lookupType == 'serial') {
																		add_serial_to_input(
																			li.dataset.serial,
																			li.dataset.serial_id,
																			li.dataset.stock_id,
																			li.dataset.ipn,
																			li.dataset.cost,
																			li.dataset.transfer_price,
																			li.dataset.market_state
																		);
																	}
																	else if (li.dataset.lookupType == 'ipn') {
																		if (li.dataset.serialized == 1) open_popup_dialog('input', li.id);
																		else {
																			in_context_ipn_qty = li.dataset.on_hand;
																			add_ipn_to_input(li.id, li.dataset.ipn, li.dataset.avg_cost, 1, li.dataset.market_state);
																		}
																	}
																	document.getElementById('ipn_or_serial_input_autocomplete').value = '';
																}
														});
													</script>
													<div class="column_data" id="input_result" style="display:none">
														<table id="input_result_table">
															<tr>
																<td class="main header">IPN</td>
																<td class="main header">Serial #</td>
																<td class="main header" align="right">Qty</td>
																<td class="main header" align="right">Cost</td>
																<td></td>
															</tr>
														</table>
														<br><br>
														<div class="costs">
															<hr>
															Total input Cost: $<span id="input_costs"></span>&nbsp;&nbsp;
														</div>
													</div>
												</div>
												<!-- Output Column !-->
												<div class="conversion_column">
													<div class="column_header">Output IPN's</div>
													<div id="column_output" style="display:none">
														<div>Output IPN:</div>
														<div>
															<input id="ipn_output_autocomplete" type="text" size="20">
															<div id="ipn_output_choices" class="autocomplete ipn_auto" style="display:none"></div>
															<script type="text/javascript">
																new Ajax.Autocompleter('ipn_output_autocomplete', "ipn_output_choices", "inventory_conversion_ajax.php",
																	{
																		method: 'get',
																		minChars: 3,
																		paramName: 'search_string',
																		parameters: 'action=ipn_search&output=1',
																		afterUpdateElement: function(input, li) {
																			add_ipn_to_output(li.id, li.dataset.market_state);
																		}
																	});
															</script>
														</div>
													</div>
													<style>
														.transfer_price { display:none; }
													</style>
													<div class="column_data" id="output_result" style="display:none;">
														<table id="output_result_table">
															<thead>
																<tr>
																	<td class="main header">IPN</td>
																	<td class="main header">Serial # / New IPN</td>
																	<td class="main header" align="right">Qty</td>
																	<td class="main header" align="right">Cost <span class="transfer_price">/ Transfer $</span></td>
																	<td class="main header merge_column" align="right">Merge</td>
																</tr>
															</thead>
															<tbody id="output_results_body">

															</tbody>
														</table>
														<br><br>
														<div class="costs">
															<hr>
															Total output Cost: $<span id="output_costs"></span>&nbsp;&nbsp;
														</div>
													</div>
												</div>
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<div style="width:100%; text-align:right">
												<input id="complete_button" type="button" value="Complete Conversion" onClick="open_popup_dialog('confirmation');" disabled='disabled'>
												<input type="button" value="Reset Form" onClick="window.location='inventory_conversion.php';">
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<div>
												Calculate Cost Conversion: $<input type="text" size="5" id="calc_input_cost" class="calculator" onchange="calculator();"> / <input type="text" size="2" id="calc_div_qty" class="calculator" onchange="calculator();"> = $<input type="text" size="5" id="calc_output_cost" class="calculator">
											</div>
											<script>
												// I did this with plain old DOM manipulation because I couldn't get jQuery to work... perhaps the $() function is registered to prototype on this page? Haven't explored enough to figure it out.
												function calculator() {
													var result = document.getElementById('calc_input_cost').value / document.getElementById('calc_div_qty').value;
													if (!isFinite(result)) {
														return;
													}
													document.getElementById('calc_output_cost').value = result;
												}
											</script>
										</td>
									</tr>
								</table>
							</form>
						</td>
					</tr>
				</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
<html>
