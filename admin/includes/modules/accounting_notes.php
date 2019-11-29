<?php
if (isset($_REQUEST['aon_action'])) {
	switch ($_REQUEST['aon_action']) {
		case 'aon_init':
		$order_ids = _aon_get_order_ids($_REQUEST['customers_id']);
		$curr_order_id = !empty($_REQUEST['order_id'])?$_REQUEST['order_id']:NULL;
		?>
		<h3>Accounting Notes</h3>
		<table cellspacing="5" cellpadding="5">
			<tr>
				<th>Order ID</th>
				<th>User</th>
				<th>Text</th>
				<th>Action</th>
			</tr><?php
			$notes = prepared_query::fetch('SELECT aon.id as note_id, aon.creator_id, aon.text, aon.order_id FROM acc_order_notes aon LEFT JOIN orders o ON aon.order_id = o.orders_id WHERE o.customers_id = :customers_id', [':customers_id' => $_REQUEST['customers_id']]);
			// foreach ($order_ids as $unused => $order_id) {
			// 	$notes = prepared_query::fetch('SELECT id as note_id, creator_id, text, order_id FROM acc_order_notes WHERE order_id = :orders_id', cardinality::SET, [':orders_id' => $order_id]);
				foreach ($notes as $unused => $note) {
					$admin = new ck_admin($note['creator_id']);
			?><tr>
				<td style="border-top: 1px solid black;"><?= $note['order_id']; ?></td>
				<td style="font-weight: normal; border-top: 1px solid black;"><?= $admin->get_name(); ?></td>
				<td style="font-weight: normal; border-top: 1px solid black;"><?= nl2br($note['text']); ?></td>
				<td style="border-top: 1px solid black;"><a style="color: blue; cursor: pointer;" onclick="aon_delete('<?= $note['note_id']; ?>');">Delete</a></td>
			</tr>
			<?php
				}
			// }
			?>
		</table>
		<hr/><strong>Order ID: </strong> <select id="aon_order_id"><?php
		foreach ($order_ids as $unused => $order_id) {
			?><option value="<?= $order_id; ?>" <?php if ($order_id == $curr_order_id) { ?> selected <?php } ?>><?= $order_id; ?></option><?php
		} ?></select>
		<strong>Note: </strong> <textarea id="aon_text" cols="65" rows="6"></textarea>
		<input type="button" onclick="aon_add_button();" value="Add Note"/> <?php
			break;
		case 'aon_add':
			prepared_query::insert('INSERT INTO acc_order_notes (order_id, creator_id, text) VALUES (:orders_id, :admin_id, :note)', [':orders_id' => $_REQUEST['aon_order_id'], ':admin_id' => $_SESSION['perms']['admin_id'], ':note' => addslashes($_REQUEST['aon_text'])]);

			break;
		case 'aon_delete':

			prepared_query::execute('delete from acc_order_notes where id = :id', [':id' => $_REQUEST['aon_id']]);

			break;
		default:
			echo 'fell to default';
			break;
	}
	exit();
}


function insert_accounting_notes_manager($customers_id, $order_id=NULL, $text='Accounting Notes') {
	$order_ids = _aon_get_order_ids($customers_id);
	if (count($order_ids) > 0) { ?>
	<a style="color: blue; cursor: pointer;" onclick="aon_init('<?= $customers_id; ?>', '<?= $order_id!=NULL?$order_id:''; ?>'); return false;"><?= $text; ?></a>
		<?php if (empty($_REQUEST['aon_modal_inserted'])) { ?>
		<script type="text/javascript" src="/admin/includes/javascript/accounting_notes.js"></script>
		<div id="aon_modal" class="jqmWindow" style="padding:20px; top:0; max-height:100%; scroll:auto; box-sizing:border-box;">
			<div style="text-align:right;">
				<a class="jqmClose" href="#" style="font-weight:bold;">X</a>
			</div>
			<div id="aon_modal_content" style="max-height: 600px; overflow: auto;"></div>
		</div>
		<input type="hidden" id="aon_customers_id" value="">
		<input type="hidden" id="aon_order_id" value="">
			<?php $_REQUEST['aon_modal_inserted'] = '1';
		}
	}
}

function _aon_get_order_ids($customers_id) {
	$iorders_ids = prepared_query::fetch('SELECT DISTINCT inv_order_id FROM acc_invoices WHERE customer_id = :customers_id AND inv_order_id IS NOT NULL AND paid_in_full = 0 ORDER BY inv_order_id ASC', cardinality::COLUMN, [':customers_id' => $customers_id]);

	$oorders_ids = prepared_query::fetch('SELECT orders_id FROM orders WHERE customers_id = :customers_id AND orders_status NOT IN (3, 6, 9) ORDER BY orders_id ASC', cardinality::COLUMN, [':customers_id' => $customers_id]);

	if (!empty($iorders_ids) && !empty($oorders_ids)) {
		$orders_ids = array_unique(array_merge($iorders_ids, $oorders_ids));
		asort($orders_ids);
		return $orders_ids;
	}
	elseif (!empty($iorders_ids)) return $iorders_ids;
	elseif (!empty($oorders_ids)) return $oorders_ids;
	else return [];
}
