<?php
require('includes/application_top.php');
require_once('includes/functions/po_alloc.php');

$ipn = isset($_GET['ipn'])?$_GET['ipn']:null;
$serial = isset($_GET['serial'])?$_GET['serial']:null;
$action = isset($_GET['action'])?$_GET['action']:null;

switch ($action) {
	case 'lookup':
		break;
	case 'save':
		if ($ipn_data = prepared_query::fetch("SELECT stock_id, serialized FROM products_stock_control WHERE stock_name = :ipn", cardinality::ROW, [':ipn' => $_POST['ipn']])) {
			$ipn = new ck_ipn2($ipn_data['stock_id']);

			if ($ipn->get_inventory('salable') < $_POST['quantity']) {
				$messageStack->add_session('Cannot hold more than what we currently have on hand, less what is already on hold', 'error');
			}
			else {
				if (isset($_POST['serial']) && $ipn_data['serialized'] == 1) {
					$serial_id = prepared_query::fetch("SELECT id FROM serials WHERE serial = :serial_number", cardinality::SINGLE, [':serial_number' => $_POST['serial']]);
				}

				if (($ipn_data['serialized'] == 1 && !empty($serial_id)) || $ipn_data['serialized'] == 0) {
					$data = array(
						'stock_id' => $ipn_data['stock_id'],
						'quantity' => $_POST['quantity'],
						'reason_id' => $_POST['reason'],
						'serial_id' => ($ipn_data['serialized'] == 1) ? $serial_id : 'null',
						'notes'	=> $_POST['notes'],
					);

					if (isset($_POST['holdId'])) {
						// update
						$result = prepared_query::execute('UPDATE inventory_hold SET stock_id = :stock_id, quantity = :quantity, reason_id = :reason_id, serial_id = :serial_id, notes = :notes WHERE id = :hold_id', [':stock_id' => $data['stock_id'], ':quantity' => $data['quantity'], ':reason_id' => $data['reason_id'], ':serial_id' => $data['serial_id'], ':notes' => $data['notes'], ':hold_id' => $_POST['holdId']]);
					}
					else {
						// insert
						$data['date'] = 'now()';
						$data['creator_id'] = $_SESSION['perms']['admin_id'];

						$result = prepared_query::execute('INSERT INTO inventory_hold (stock_id, quantity, reason_id, serial_id, notes, date, creator_id) VALUES (:stock_id, :quantity, :reason_id, :serial_id, :notes, NOW(), :creator_id)', [':stock_id' => $data['stock_id'], ':quantity' => $data['quantity'], ':reason_id' => $data['reason_id'], ':serial_id' => $data['serial_id'], ':notes' => $data['notes'], ':creator_id' => $data['creator_id']]);

						//we are decreasing quantity - tell channel advisor
						$ca = new api_channel_advisor;
						if ($ca::is_authorized()) $ca->update_quantity($ipn);
					}

					if (!empty($serial_id)) {
						$serial_status_id = prepared_query::fetch("SELECT id FROM serials_status WHERE name = 'On Hold'", cardinality::SINGLE);

						prepared_query::execute('UPDATE serials SET status = :on_hold WHERE id = :serial_id', [':on_hold' => $serial_status_id, ':serial_id' => $serial_id]);

						//MMD - remove warehouse allocations if needed
						po_alloc_check_and_remove_warehouse_by_ipn($ipn_data['stock_id']);
					}

					$messageStack->add_session('New inventory hold created.', 'success');
				}
				else {
					$messageStack->add_session("Invalid serial: {$_POST['serial']}", 'error');
				}
			}
		}
		else {
			$messageStack->add_session("Invalid ipn: {$_POST['ipn']}", 'error');
		}

		break;
	case 'save_bulk':
		$quantity = 0;
		$stock_id = prepared_query::fetch('SELECT stock_id FROM products_stock_control WHERE stock_name = :stock_name', cardinality::SINGLE, [':stock_name' => $_POST['ipn']]);
		$ipn = new ck_ipn2($stock_id);
		foreach ($_POST['serial_id'] as $serial_id) {
			if (!$hold_exists = prepared_query::fetch('SELECT id FROM inventory_hold WHERE serial_id = :serial_id', cardinality::SINGLE, [':serial_id' => $serial_id])) {
				$quantity ++;
				prepared_query::execute('UPDATE serials SET status = 6 WHERE id = :serial_id', [':serial_id' => $serial_id]);
				prepared_query::execute('INSERT INTO inventory_hold (stock_id, quantity, reason_id, serial_id, date, notes, creator_id) VALUES (:stock_id, :quantity, :reason_id, :serial_id, now(), :notes, :creator_id)', [':stock_id' => $stock_id, ':quantity' => 1, ':reason_id' => $_POST['reason'], ':serial_id' => $serial_id, ':notes' => $_POST['notes'], ':creator_id' => $_SESSION['perms']['admin_id']]);
			}
		}
		if ($quantity > 0) {
			//we are decreasing quantity - tell channel advisor
			$ca = new api_channel_advisor;
			if ($ca::is_authorized()) {
				$ca->update_quantity($ipn);
			}
		}
		CK\fn::redirect_and_exit('/admin/ipn_editor.php?ipnId='.$_POST['ipn']);
		break;
	case 'edit':
		$hold_record = prepared_query::fetch("SELECT ih.id, psc.stock_name, quantity, reason_id, notes, s.serial FROM inventory_hold ih INNER JOIN products_stock_control psc ON ih.stock_id = psc.stock_id LEFT JOIN serials s on s.id = ih.serial_id WHERE ih.id = :hold_id", cardinality::ROW, [':hold_id' => $_GET['holdId']]);
		$ipn = $hold_record['stock_name'];
		$serial = $hold_record['serial'];
		$holdId = $hold_record['id'];

		break;
	case 'delete_confirm':
		if ($serial_id = prepared_query::fetch("SELECT serial_id FROM inventory_hold WHERE id = :hold_id", cardinality::SINGLE, [':hold_id' => $_POST['holdId']])) {
			$in_stock = prepared_query::fetch("SELECT id FROM serials_status WHERE name = 'In Stock'", cardinality::SINGLE);

			$result = prepared_query::execute('UPDATE serials SET status = :in_stock WHERE id = :serial_id AND status = 6', [':in_stock' => $in_stock, ':serial_id' => $serial_id]);
		}

		$delete_query = prepared_query::execute("DELETE FROM inventory_hold WHERE id = :hold_id", [':hold_id' => $_POST['holdId']]);
	case 'delete': ?>
		<p>Are you sure you want to delete this inventory hold?</p>
		<input id="yes" name="yes" type="button" value="Yes" /><input id="no" class="jqmClose" name="no" type="button" value="No" />
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#yes').click(function() {
					$('#modal').jqmHide();
					$.post('inventory_hold.php?action=delete_confirm', {holdId: <?= $_GET['holdId']; ?>}, function() {
						window.location.reload();
					});
				});

				$('#no').click(function() {
					$('#modal').jqmHide();
				});
			});
		</script>
		<?php break;
	case 'bulk_scrap':
		if (empty($_GET['proc'])) {
			$reasons = ck_adjustment_reason_lookup::instance()->get_list('user');
			$types = ck_adjustment_type_lookup::instance()->get_list('user'); ?>
			<form id="scrap" method="post" action="inventory_hold.php?action=bulk_scrap&proc=1">
				<table>
					<tr>
						<td>Reason:</td>
						<td>
							<select id="reason" name="reason">
								<?php foreach ($reasons as $reason) {
									if ($reason['adjustment_direction'] > 0) continue; ?>
								<option value="<?= $reason['adjustment_reason_id']; ?>"><?= $reason['reason']; ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>Type:</td>
						<td>
							<select id="type" name="type">
								<?php foreach ($types as $type) {
									if ($type['adjustment_direction'] > 0) continue; ?>
								<option value="<?= $type['adjustment_type_id']; ?>"><?= $type['type']; ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>Notes:</td>
						<td><textarea id="notes" name="notes"></textarea></td>
					</tr>
				</table>
				<?php foreach ($_GET['hold_ids'] as $hold_id) { ?>
				<input type="hidden" name="hold_ids[]" value="<?= $hold_id; ?>">
				<?php } ?>
				<input id="post_scrap" type="button" value="submit">
			</form>
			<script>
				jQuery('#post_scrap').click(function() {
					jQuery('#modal').jqmHide();
					jQuery.post('inventory_hold.php?action=bulk_scrap&proc=1', jQuery('#scrap').serialize(), function() { window.location.reload(); });
				});
			</script>
		<?php }
		else {
			$reason_id = $_POST['reason'];
			$type_id = $_POST['type'];
			$notes = $_POST['notes'];

			$admin_id = $_SESSION['perms']['admin_id'];
			$scrap_date = date('Y-m-d H:i:s');

			$hold_ids = $_POST['hold_ids'];

			foreach ($hold_ids as $hold_id) {
				$hold = prepared_query::fetch('SELECT * FROM inventory_hold WHERE id = :hold_id', cardinality::ROW, [':hold_id' => $hold_id]);

				if (!empty($hold['serial_id'])) {
					$serial = prepared_query::fetch('SELECT po.id as po_id, sh.cost, s.ipn as stock_id FROM serials s JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id JOIN purchase_orders po ON sh.po_number = po.purchase_order_number WHERE sh0.id IS NULL AND sh.serial_id = :serial_id', cardinality::ROW, [':serial_id' => $hold['serial_id']]);
					$serial_id = $hold['serial_id'];
					$cost = $serial['cost'];
					$po_id = $serial['po_id'];
					$old_qty = 1;
					$new_qty = 0;
				}
				else {
					$serial_id = NULL;
					$po_id = NULL;
					$cost = prepared_query::fetch('SELECT average_cost from products_stock_control where stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $hold['stock_id']]);
					$old_qty = prepared_query::fetch('SELECT stock_quantity from products_stock_control where stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $hold['stock_id']]);
					$new_qty = $old_qty - $hold['quantity'];
				}

				$scrap_data = [
					':ipn_id' => $hold['stock_id'],
					':po_id' => $po_id,
					':admin_id' => $admin_id,
					':cost' => $cost,
					':scrap_date' => $scrap_date,
					':old_qty' => $old_qty,
					':new_qty' => $new_qty,
					':serial_id' => $serial_id,
					':inventory_adjustment_reason_id' => $reason_id,
					':inventory_adjustment_type_id' => $type_id,
					':notes' => $notes
				];

				prepared_query::execute('INSERT INTO inventory_adjustment (ipn_id, po_id, admin_id, cost, scrap_date, old_qty, new_qty, serial_id, inventory_adjustment_reason_id, inventory_adjustment_type_id, notes) VALUES (:ipn_id, :po_id, :admin_id, :cost, :scrap_date, :old_qty, :new_qty, :serial_id, :inventory_adjustment_reason_id, :inventory_adjustment_type_id, :notes)', $scrap_data);

				prepared_query::execute('DELETE FROM inventory_hold WHERE id = :id', [':id' => $hold_id]);

				if ($serial_id) {
					if ($scrap_data[':inventory_adjustment_reason_id'] == '1') $serial_status = '7';
					else $serial_status = '8';

					prepared_query::execute('UPDATE serials SET status = :status WHERE id = :id', [':status' => $serial_status, ':id' => $serial_id]);
				}
				else {
					prepared_query::execute("UPDATE products_stock_control SET stock_quantity = (stock_quantity - ".$hold['quantity'].") WHERE stock_id = ?", $scrap_data[':ipn_id']);
				}
			}
		}
		break;
	case 'scrap':
		$holdId = $_GET['holdId'];

		if (!isset($_GET['proc'])) {
			$reasons = ck_adjustment_reason_lookup::instance()->get_list('user');
			$types = ck_adjustment_type_lookup::instance()->get_list('user');

			$hold_data = prepared_query::fetch('SELECT * FROM inventory_hold WHERE id = ?', cardinality::ROW,$_GET['holdId']);
			$serial = prepared_query::fetch('SELECT serial from serials where id = ?', cardinality::SINGLE, $hold_data['serial_id']);
			if ($serial) $po_number = prepared_query::fetch('SELECT po_number from serials_history WHERE serial_id = '.$hold_data['serial_id'].' AND po_number IS NOT NULL ORDER BY id DESC LIMIT 1', cardinality::SINGLE);
			$po_id = prepared_query::fetch('SELECT id from purchase_orders WHERE purchase_order_number = ?', cardinality::SINGLE, @$po_number); ?>
			<script>
				jQuery(document).ready(function($) {
					$('#post_scrap').click(function(e) {
						if (jQuery('#reason').val() == return_to_vendor_reason && jQuery('#notes').val() == '') {
							e.preventDefault();
							alert('You must provide Vendor and original PO # for vendor returns');
							return;
						}
						$('#modal').jqmHide();
						$.post('inventory_hold.php?action=scrap&proc=1', $('#scrap').serialize(),
						function() {
							window.location.reload();
						});
					});
				});
			</script>

			<style>
				textarea[required] { border:1px solid #f00; }
			</style>
			<form id="scrap" method="POST" action="inventory_hold.php?action=scrap&proc=1">
				<table>
					<tr>
						<td>Qty:</td>
						<td><input id="qty" name="qty" size="3" value="<?= $hold_data['quantity']; ?>" readonly="true"></td>
					</tr>
					<?php if ($serial) { ?>
					<tr>
						<td>Serial:</td>
						<td><input id="serial" name="serial" value="<?= $serial; ?>" readonly="true"></td>
					</tr>
					<?php } ?>
					<tr>
						<td>PO#:</td>
						<td><input id="po" name="po" value="<?= @$po_number; ?>" <?php if ($serial) echo 'readonly="true"'; ?>></td>
					</tr>
					<tr>
						<td>Reason:</td>
						<td>
							<select id="reason" name="reason">
								<?php foreach ($reasons as $reason) {
									if ($reason['adjustment_direction'] > 0) continue; ?>
								<option value="<?= $reason['adjustment_reason_id']; ?>"><?= $reason['reason']; ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>Type:</td>
						<td>
							<select id="type" name="type">
								<?php foreach ($types as $type) {
									if ($type['adjustment_direction'] > 0) continue; ?>
								<option value="<?= $type['adjustment_type_id']; ?>"><?= $type['type']; ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>Notes:</td>
						<td>
							<textarea id="notes" name="notes" title="You must include Vendor and original PO # for 'Return to Vendor' reason"><?= $hold_data['notes']; ?></textarea>
						</td>
					</tr>
				</table>
				<input type="hidden" id="po_id" name="po_id" value="<?= $po_id; ?>">
				<input type="hidden" id="ipn_id" name="ipn_id" value="<?= $hold_data['stock_id']; ?>">
				<input type="hidden" id="serial_id" name="serial_id" value="<?= $hold_data['serial_id']; ?>">
				<input type="hidden" id="hold_id" name="hold_id" value="<?= $holdId; ?>">
				<input id="post_scrap" type="button" value="submit">
			</form>
			<script>
				var return_to_vendor_reason = 6;
				jQuery('#reason').change(function() {
					jQuery('#notes').attr('required', jQuery(this).val() == return_to_vendor_reason);
				});
			</script>
		<?php }
		else {
			$ipn_id = $_POST['ipn_id'];
			$po_id = $_POST['po_id'];
			$admin_id = $_SESSION['perms']['admin_id'];
			$scrap_date = date('Y-m-d H:i:s');
			$qty = $_POST['qty'];
			$serial_id = $_POST['serial_id'];
			$old_qty = prepared_query::fetch('SELECT stock_quantity from products_stock_control where stock_id = ?', cardinality::SINGLE,$ipn_id);
			if ($serial_id > '0') {
				$cost = prepared_query::fetch('SELECT cost from serials_history where serial_id = ? ORDER BY id DESC', cardinality::SINGLE, $serial_id);
				$old_qty = '1';
				$new_qty = '0';
			}
			else {
				$cost = prepared_query::fetch('SELECT average_cost from products_stock_control where stock_id = ?', cardinality::SINGLE, $ipn_id);
				$old_qty = $old_qty;
				$new_qty = $old_qty-$qty;
			}
			$reason_id = $_POST['reason'];
			$holdId = $_POST['hold_id'];
			$type = $_POST['type'];


			$scrap_data = array(
				':ipn_id' => $ipn_id,
				':po_id' => $po_id,
				':admin_id' => $admin_id,
				':cost' => $cost,
				':scrap_date' => $scrap_date,
				':old_qty' => $old_qty,
				':new_qty' => $new_qty,
				':serial_id' => $serial_id,
				':inventory_adjustment_reason_id' => $reason_id,
				':inventory_adjustment_type_id' => $type,
				':notes' => $_POST['notes'],
			);

			prepared_query::execute('INSERT INTO inventory_adjustment (ipn_id, po_id, admin_id, cost, scrap_date, old_qty, new_qty, serial_id, inventory_adjustment_reason_id, inventory_adjustment_type_id, notes) VALUES (:ipn_id, :po_id, :admin_id, :cost, :scrap_date, :old_qty, :new_qty, :serial_id, :inventory_adjustment_reason_id, :inventory_adjustment_type_id, :notes)', $scrap_data);

			$hold_data = prepared_query::fetch('SELECT * FROM inventory_hold WHERE id = ?', cardinality::ROW,$_POST['hold_id']);

			if ($hold_data['quantity'] == $qty) {
				prepared_query::execute('DELETE FROM inventory_hold WHERE id = ?', $_POST['hold_id']);

				if ($serial_id) {
					if ($scrap_data[':inventory_adjustment_reason_id'] == '1') $serial_status = '7';
					else $serial_status = '8';

					prepared_query::execute('UPDATE serials SET status = :status WHERE id = :id', [':status' => $serial_status, ':id' => $serial_id]);
				}
				else {
					prepared_query::execute("UPDATE products_stock_control SET stock_quantity = (stock_quantity - ".$hold_data['quantity'].") WHERE stock_id = ?", $scrap_data[':ipn_id']);
				}

			}
			else {
				prepared_query::execute("UPDATE inventory_hold SET quantity = (quantity - ".$qty.") WHERE id = ?", $holdId);

				prepared_query::execute("UPDATE products_stock_control SET stock_quantity = (stock_quantity - ".$hold_data['quantity'].") WHERE stock_id = ?", $scrap_data[':ipn_id']);
			}
		}
		break;
	case 'hold_selected_serials':
		$reason_result = prepared_query::fetch('SELECT * FROM inventory_hold_reason', cardinality::SET);
		$reasons = [];
		$count = 0;
		foreach($reason_result as $row) {
			$reasons[$row['id']] = $row['description'];
		} ?>
		<form id="holdForm" action="inventory_hold.php?action=save_bulk" method="POST">
			<fieldset style="width: 450px; margin:10px;">
				<legend>Inventory Hold</legend>
				<span>IPN: <b><?= $ipn; ?></b></span><br>
				<span>Serial(s): </span><br>
				<?php if (empty($_GET['put_serial_on_hold'])) { ?>
					<h3 style="color:red; text-align:center;">*ALERT* select the serials you would like to place on hold and try again</h3>
				<?php }
					else { ?>
					<ul>
					<?php foreach ($_GET['put_serial_on_hold'] as $serial_id => $serial) { ?>
							<li style="font-size:12px;"><?= $serial; ?><input type="hidden" value="<?= $serial_id; ?>" name="serial_id[<?= $count; ?>]"></li>
					<?php $count ++;
						} ?>
					</ul>
				<label for="reason">Reason: </label>
				<select id="reason" name="reason" style="margin-bottom:5px;">
					<?php foreach ($reasons as $id => $reason) { ?>
					<option value="<?= $id; ?>"><?= $reason; ?></option>
					<?php } ?>
				</select>
				<br>
				<label for="notes">Notes:</label>
				<br>
				<textarea id="notes" name="notes" style="width:100%; height:75px;"></textarea><br>
				<input id="createHold" type="submit" value="Create Hold" style="margin:5px;">
				<input name="ipn" type="hidden" value="<?= $ipn; ?>">
			</fieldset>
		</form>
		<?php }
		unset($_GET);
		break;
	default:
		if ($ipn_data = prepared_query::fetch("SELECT stock_id, serialized FROM products_stock_control WHERE stock_name = :ipn", cardinality::ROW, [':ipn' => @$_GET['ipn']])) {
			if (isset($_GET['serial']) && $ipn_data['serialized'] == 1) {
				$serial_id = prepared_query::fetch("SELECT id FROM serials WHERE serial = :serial_number", cardinality::SINGLE, [':serial_number' => $_GET['serial']]);

				if (prepared_query::fetch("SELECT id FROM inventory_hold WHERE serial_id = :serial_id", cardinality::SINGLE, [':serial_id' => $serial_id])) {
					echo "Serial {$_GET['serial']} already has a hold in place. Please edit that hold instead of creating a new one."; exit;
				}
			}

			if ($ipn_data['serialized'] == 1) {
				$serialform = true;
			}
		}
		break;
}

if ($action == 'edit' || is_null($action)) { ?>
	<div class="pageHeading" style="padding-bottom: 20px;"><?php echo ($action == 'edit') ? 'Edit' : 'New'; ?> Inventory Hold</div>
	<?php if (!isset($ipn)) { ?>
	<table>
		<tr>
			<td>
				<select name="autocomplete_type" id="autocomplete_type" onChange="$('autocomplete_search_box').clear().focus();">
					<option value="ipn">IPN Lookup</option>
					<option value="serial">Serial Lookup</option>
				</select>
			</td>
			<td>
				<input type="text" name="autocomplete_search_box" id="autocomplete_search_box" value="<?= $ipn; ?>">
				<script type="text/javascript">
					function make_ac_selection(event, ui) {
						var url = "inventory_hold.php?ipn=" + urlencode(ui.item.misc);
						if (jQuery('#autocomplete_type').val() == 'serial') url = url + '&serial=' + urlencode(ui.item.label);
						jQuery('#modal').jqmHide();
						jQuery('#modal').jqm({ajax: url});
						jQuery('#modal').jqmShow();
					}
					jQuery(document).ready(function ($) {
						$('#autocomplete_search_box').autocomplete({
							minLength: 3,
							source: function(request, response) {
								$.ajax({
									url: '/admin/serials_ajax.php?action=ipn_autocomplete',
									dataType: "json",
									data: {
										term: request.term,
										search_type: $('#autocomplete_type').val()
									},
									success: function(data) {
										if (data.length == 1) {
											make_ac_selection(null, { item: { misc: data[0].value, label: data[0].label, value: data[0].label } });
										}
										else {
											response($.map(data, function(item) {
												if (item.value == null) {
													item.value = item.label;
												}
												return {
													misc: item.value,
													label: item.label,
													value: item.label
												}
											}));
										}
									}
								});
							},
							select: make_ac_selection
						});

						<?php if (!empty($_GET['make_selection'])) { ?>
						setTimeout(function() { $('#autocomplete_type').val('<?= $_GET['make_selection']; ?>').change(); }, 90);
						<?php } ?>
					});

					function urlencode(str) {
						str = escape(str);
						str = str.replace('+', '%2B');
						str = str.replace('%20', '+');
						str = str.replace('*', '%2A');
						str = str.replace('/', '%2F');
						str = str.replace('@', '%40');
						return str;
					}
				</script>
			</td>
		</tr>
	</table>
	<?php }
	else {
		$ckipn = ck_ipn2::get_ipn_by_ipn($ipn);
		$reason_result = prepared_query::fetch("SELECT * FROM inventory_hold_reason", cardinality::SET);
		$reasons = array();
		foreach ($reason_result as $row) {
			$reasons[$row['id']] = $row['description'];
		} ?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#createHold-repeat').click(function() {
					var serialized = $('#serial').length > 0;
					$('#modal').jqmHide();
					$.post('inventory_hold.php?action=save', $('#holdForm').serialize(), function() {
						$('#modal').jqm({ajax: 'inventory_hold.php?make_selection='+(serialized?'serial':'ipn'), target: '#ajaxContainer', modal: true});
						$('#modal').jqmShow();
					});
				});
				$('#createHold').click(function() {
					$('#modal').jqmHide();
					$.post('inventory_hold.php?action=save', $('#holdForm').serialize(), function() {
						window.location.reload();
					});
				});
			});
		</script>
		<form id="holdForm" action="inventory_hold.php?action=save" method="POST">
			<fieldset style="width: 450px;">
				<legend><?php echo ($action == 'edit') ? 'Edit' : 'New'; ?> Inventory Hold</legend>
				<span>IPN: </span><span><?= $ipn; ?></span><br />
				<label for="quantity">Quantity: <input id="quantity" name="quantity" type="text" style="width: 30px;" value="<?= !empty($serial)?1:(isset($hold_record['quantity'])?$hold_record['quantity']:''); ?>" <?= !empty($serial)?'readonly':''; ?>> of <?= $ckipn->get_inventory('salable'); ?></label><br />
				<label for="reason">Reason: </label><select id="reason" name="reason">
				<?php foreach ($reasons as $id => $reason) { ?>
					<option value="<?= $id; ?>"<?php if (!empty($hold_record['reason_id']) && $hold_record['reason_id'] == $id): echo ' selected="selected"'; endif; ?>><?= $reason; ?></option>
				<?php } ?>
				</select><br />
				<?php if (!empty($serial) || !empty($serialform)) { ?>
				<label for="serial">Serial: </label><input id="serial" name="serial" type="text" value="<?= $serial; ?>" /><br />
				<?php } ?>
				<label for="notes">Notes: </label><textarea id="notes" name="notes"><?php echo !empty($hold_record['notes'])?htmlspecialchars($hold_record['notes']):''; ?></textarea><br />
				<input id="createHold-repeat" type="button" value="<?php echo ($action == 'edit') ? 'Edit' : 'Create'; ?> Inventory Hold &amp; Repeat" />
				<input id="createHold" type="button" value="<?php echo ($action == 'edit') ? 'Edit' : 'Create'; ?> Inventory Hold" style="margin-left:30px;" />
				<input name="ipn" type="hidden" value="<?= $ipn; ?>" />
				<?php if (!empty($holdId)) { ?>
				<input name="holdId" type="hidden" value="<?= $holdId; ?>" />
				<?php } ?>
			</fieldset>
		</form>
	<?php }
} ?>