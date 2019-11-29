<?php
require('includes/application_top.php');
switch ($_REQUEST['action']) {
	case 'ipn_search':
		$query = 'SELECT psc.*, c.market_state FROM products_stock_control psc LEFT JOIN conditions c ON psc.conditions = c.conditions_id WHERE psc.stock_name LIKE ?';
		if (!empty($_GET['output'])) $query .= ' AND serialized != 1';
		elseif (!empty($_GET['serial'])) $query .= ' AND serialized = 1';

		$rows = prepared_query::fetch($query, cardinality::SET, [$_GET['search_string'].'%']);
		print '<ul>';
		foreach ($rows as $row) {
			$name = $row['stock_name'];
			$id = $row['stock_id'];
			$market_state = $row['market_state'];
			if (!empty($_GET['avg_cost'])) {
				$id .= '_'.$row['average_cost'];
			}
			print '<li id="'.$id.'" data-market_state="'.$market_state.'">'.$name.'</li>';

		}
		print '</ul>';
		exit();
		break;
	case 'get_input_ipn':
		$ipn = new ck_ipn2($_GET['stock_id']);
		$stock_id = $ipn->id();
		$stock_name = $ipn->get_header('ipn');
		$average_cost = $ipn->get_header('average_cost');

		if ($ipn->is('serialized')) {
			$serials = prepared_query::fetch('SELECT s.serial, s.status, s.id as serial_id, sh.cost FROM serials s JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id WHERE s.ipn = :stock_id AND s.status IN (2, 3, 6)', cardinality::SET, [':stock_id' => $ipn->id()]); ?>
			<table class="serial_select">
				<tr>
					<td>Select</td>
					<td>Serial</td>
				</tr>
				<?php foreach ($serials as $serial) { ?>
				<tr>
					<td>
						<input type="checkbox" class="serial-select" id="serial_<?= $serial['serial']; ?>"
							data-serial="<?= $serial['serial']; ?>"
							data-serial_id="<?= $serial['serial_id']; ?>"
							data-stock_id="<?= $ipn->id(); ?>"
							data-ipn="<?= $stock_name; ?>"
							data-cost="<?= number_format($serial['cost'], 2, '.', ''); ?>"
							data-transfer_price="<?= $ipn->get_transfer_price(); ?>"
							data-market_state="<?= $ipn->get_header('market_state'); ?>"
						>
					</td>
					<td><?= $serial['serial']; ?></td>
					<td><?= $serial['status']==6?'(On HOLD)':''; ?></td>
				</tr>
				<?php } ?>
			</table>
			<br/>
			<input type="button" value="done" class="done-button">
		<?php }
		else { ?>
			Enter the quantity of <?= $stock_name; ?> to convert.
			<input id="ipn_conversion_qty" type="text">
			<input type="button" value="done" onClick=" if ($F('ipn_conversion_qty') > <?= $ipn->get_inventory('on_hand'); ?>) { alert('There are only <?= $ipn->get_inventory('on_hand'); ?> in stock.'); $('ipn_conversion_qty').value=<?= $ipn->get_inventory('on_hand'); ?>; } else { add_ipn_to_input( '<?= $ipn->id(); ?>', '<?= $stock_name; ?>', '<?= $average_cost; ?>', $F('ipn_conversion_qty'), 1, '<?= $ipn->get_header('market_state'); ?>'); $('popup_dialog').hide(); $('ipn_output_autocomplete').focus();}">
		<?php }
		break;
	case 'get_output_ipn':
		$ipn = prepared_query::fetch('SELECT psc.*, c.market_state FROM products_stock_control psc LEFT JOIN conditions c ON psc.conditions = c.conditions_id WHERE psc.stock_id = ?', cardinality::ROW, [$_GET['stock_id']]);
		$stock_id = $ipn['stock_id'];
		$stock_name = $ipn['stock_name'];
		$average_cost = $ipn['average_cost'];
		$market_state = $ipn['market_state']; ?>
		<tr id="output_ipn_tr_ipn_<?= $_GET['stock_id']; ?>" class="ipn_output_tr">
			<td class="main"><?= $stock_name; ?></td>
			<td>n/a</td>
			<td class="main" align="right"><input class="output_qty" type="text" size="2" id="output_ipn_qty_<?= $stock_id; ?>" name="output_ipn_qty_<?= $stock_id; ?>" value="1" onChange="update_costs();"></td>
			<td class="main" align="right">
				<input class="output_ipn_inputs" type="hidden" name="output_ipn_id[]" value="<?= $stock_id; ?>">
				<input class="output-market-state" type="hidden" name="output_market_state[]" value="<?= $market_state; ?>">
				<input type="hidden" id="original_cost_<?= $stock_id; ?>" value="<?= $average_cost; ?>">
				<input class="output_cost" onChange="update_costs();" type="text" size="5" id="output_cost_<?= $stock_id; ?>" name="output_cost_<?= $stock_id; ?>" value="<?= $average_cost; ?>">
			</td>
		</tr>
		<?php break;
	case 'get_confirmation':
	
		//print "<pre>";print_r($_POST);print "</pre>";
		?>
		<span class="main">Notes:</span><br/><br/>
		<textarea name="completion_notes" id="completion_notes" rows="8" cols="64"><?php
			echo "Input:\n";

			$total_cost = 0;
			foreach ($_POST['input_ipn_id'] as $i => $stock_id) {
				$ipn = new ck_ipn2($stock_id);
				echo $ipn->get_header('ipn').' quantity: '.$_POST['input_ipn_qty_'.$stock_id]."\n";
				if ($ipn->is('serialized')) {
					foreach ($_POST['input_ipn_serials'][$stock_id] as $serial_id) {
						$srl = new ck_serial($serial_id);
						$total_cost += $srl->get_current_history('cost');
					}
				}
				else {
					$total_cost = $total_cost + ($_POST['input_ipn_qty_'.$stock_id] * $ipn->get_avg_cost());
				}
			}

			echo 'Total cost of input ipns: $'.number_format($total_cost, 2)."\n\n\n";
			echo "Output:\n";
			
			$final_cost = 0;
			if (isset($_POST['output_ipn_id'])) {
				foreach ($_POST['output_ipn_id'] as $i => $stock_id) {
					$ipn = new ck_ipn2($stock_id);
					echo $ipn->get_header('ipn').' quantity: '.$_POST['output_ipn_qty_'.$stock_id].' cost: '.$_POST['output_cost_'.$stock_id]."\n";
					$final_cost = $final_cost + ($_POST['output_ipn_qty_'.$stock_id] * $_POST['output_cost_'.$stock_id]);
				}
			}

			if (isset($_POST['output_serial_id'])) {
				$merge = false;
				if ($_POST['conversion_type'] == 'manyone' && count($_POST['output_serial_id']) > 1) {
					$merge = true;
				}

				$found_merged_item = false;
				foreach ($_POST['output_serial_id'] as $i => $serial_id) {
					$srl = new ck_serial($serial_id);
					$ipn = new ck_ipn2($_POST['output_serial_'.$serial_id]);
					if (!$_POST['output_serial_'.$serial_id]) {
						echo 'SERIAL '.$srl->get_header('serial_number').' HAS NOT BEEN UPDATED IN THE OUPUT COLUMN!!!!';
						$error=true;
					}
					else {
						echo 'Serial '.$srl->get_header('serial_number').' moved to '.$ipn->get_header('ipn').' cost: $'.number_format($_POST['output_cost_serial_'.$serial_id],2)." each";
						$final_cost = $final_cost + $_POST['output_cost_serial_'.$serial_id];
					}

					if ($merge && $_POST['merged'] != $serial_id) {
						echo " - status set to 'Merged'.";
					}
					else {
						$found_merged_item = true;
					}
					echo "\n";
				}

				if ($merge && !$found_merged_item) {
					echo "ERROR - No radio button was selected to merge these serials. Please select one before completing this conversion.\n";
					$error = true;
				}
			}

			echo 'Total final cost of output ipns: $'.number_format($final_cost, 2);
		 ?></textarea>
		<input type="button" onClick="submit_form();" value="Continue" <?php if (!empty($error)) echo 'disabled'; ?>>
		<?php if (!empty($error)) echo '<br/><span class="main" style="color:#F55">Please make sure all fields have been entered correctly.</span>';
		break;
	case 'ipn_or_serial_search':

		$ipns = prepared_query::fetch('SELECT psc.stock_id, psc.serialized, (SELECT COUNT(s.id) FROM serials s WHERE s.status IN (:hold, :instock) AND s.ipn = psc.stock_id) AS serial_count FROM products_stock_control psc WHERE psc.stock_name LIKE :lookup', cardinality::SET, [':lookup' => $_GET['search_string'].'%', ':hold' => ck_serial::$statuses['HOLD'], ':instock' => ck_serial::$statuses['INSTOCK']]);

		$serials = prepared_query::fetch('SELECT s.serial, s.status, s.id as serial_id, sh.cost, s.ipn as stock_id FROM serials s JOIN ckv_latest_serials_history sh ON s.id = sh.serial_id WHERE s.serial LIKE :lookup AND s.status IN (2, 3, 6)', cardinality::SET, [':lookup' => $_GET['search_string'].'%']);

		print '<ul>';
		if (count($ipns) > 0) {
			foreach ($ipns as $row) {
				if ($row['serialized'] == 1 && $row['serial_count'] == 0) continue;
				$ipn = new ck_ipn2($row['stock_id']);
				$id = $ipn->id();
				if (!empty($_GET['avg_cost'])) $id .= '_' . $row['average_cost'];
				print '<li id="'.$id.'" data-serialized="'.$ipn->is('serialized').'" data-on_hand="'.$ipn->get_inventory('on_hand').'" data-lookup-type="ipn" data-avg_cost="'.$ipn->get_header('average_cost').'" data-ipn="'.$ipn->get_header('ipn').'" data-market_state="'.$ipn->get_header('market_state').'">'.$ipn->get_header('ipn').'</li>';
			}
		}
		if (count($serials) > 0) {
			foreach ($serials as $serial) {
				$ipn = new ck_ipn2($serial['stock_id']);
				print '<li id="'.$ipn->id().'" data-lookup-type="serial" data-serial_id="'.$serial['serial_id'].'" data-stock_id="'.$ipn->id().'" data-ipn="'.$ipn->get_header('ipn').'" data-serial="'.$serial['serial'].'" data-cost="'.number_format($serial['cost'], 2, '.', '').'" data-transfer_price="'.$ipn->get_transfer_price().'" data-market_state="'.$ipn->get_header('market_state').'">'.$serial['serial'].'</li>';
			}
		}
		print '</ul>';

		exit();
		break;
}
?>
