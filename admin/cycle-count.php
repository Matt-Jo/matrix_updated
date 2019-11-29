<?php
require('includes/application_top.php');

$confirm = @$_GET['confirm'];
$confirmQty = @$_GET['confirmQty'];
$confirmIPN = @$_GET['confirmIPN'];
$serial_confirm = @$_GET['serial_confirm'];

if (is_numeric($confirm) && is_numeric($confirmQty)) {
	prepared_query::execute('UPDATE products_stock_control SET last_quantity_change = NOW() WHERE stock_name = ?', array($confirmIPN));

	insert_psc_change_history($confirm, 'Quantity Confirmation', $confirmQty, $confirmQty);

	die('success');
}
elseif (is_numeric($serial_confirm)) {
	prepared_query::execute('UPDATE serials_history sh SET sh.confirmation_date = NOW() WHERE sh.id = ?', array($serial_confirm));

	die('success');
}
elseif (!empty($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'mass-confirm':
			$context = $_REQUEST['context'];

			$counts = array();
			$problems = array();
			$shids = array();

			if (in_array($_REQUEST['action-type'], array('Confirm:All', 'Confirm:List'))) {
				$list = $_POST[$context.'s-list'];
				$list = preg_split('/\s+/', trim($list));

				foreach ($list as $element) {
					if ($context == 'serial') {
						$serial = prepared_query::fetch('SELECT s.id, s.status, psc.stock_id, psc.stock_name as ipn, sh.id as serials_history_id FROM serials s JOIN products_stock_control psc ON s.ipn = psc.stock_id JOIN serials_history sh ON s.id = sh.serial_id LEFT JOIN serials_history sh0 ON sh.serial_id = sh0.serial_id AND sh.id < sh0.id WHERE s.serial LIKE ? AND sh0.id IS NULL', cardinality::ROW, $element);

						if (empty($serial)) {
							$problems[] = 'Serial # '.$element.' could not be found in the system.';
							continue;
						}

						$shids[] = $serial['serials_history_id'];

						if (empty($counts[$serial['ipn']])) {
							$counts[$serial['ipn']] = array(
								'listed' => prepared_query::fetch('SELECT COUNT(id) FROM serials WHERE ipn = ? AND status IN (2, 3, 6)', cardinality::SINGLE, $serial['stock_id']),
								'counted' => 0
							);
						}
						$counts[$serial['ipn']]['counted']++;

						prepared_query::execute('UPDATE serials_history SET confirmation_date = NOW() WHERE id = ?', $serial['serials_history_id']);

						if (!in_array($serial['status'], array(2, 3, 6))) $problems[] = 'Serial # '.$element.' is not marked as on-hand.';
					}
					else {
						// there's nothing to do here, as we don't accept IPNs as a list
					}
				}
			}
			if (in_array($_REQUEST['action-type'], array('Confirm:All', 'Confirm:Selections'))) {
				if ($context == 'serial') {
					foreach ($_POST['serial_history_id'] as $serial_history_id => $ignore) {
						if (in_array($serial_history_id, $shids)) continue; // we already got this one in the list

						$serial = prepared_query::fetch('SELECT s.id, s.status, psc.stock_id, psc.stock_name as ipn FROM serials s JOIN products_stock_control psc ON s.ipn = psc.stock_id JOIN serials_history sh ON s.id = sh.serial_id WHERE sh.id = ?', cardinality::ROW, $serial_history_id);

						if (empty($counts[$serial['ipn']])) {
							$counts[$serial['ipn']] = array(
								'listed' => prepared_query::fetch('SELECT COUNT(id) FROM serials WHERE ipn = ? AND status IN (2, 3, 6)', cardinality::SINGLE, $serial['stock_id']),
								'counted' => 0
							);
						}
						$counts[$serial['ipn']]['counted']++;

						prepared_query::execute('UPDATE serials_history SET confirmation_date = NOW() WHERE id = ?', $serial_history_id);
					}
				}
				else {
					foreach ($_POST['ipn'] as $stock_id => $qty) {
						prepared_query::execute('UPDATE products_stock_control SET last_quantity_change = NOW() WHERE stock_id = ?', array($stock_id));
						insert_psc_change_history($stock_id, 'Quantity Confirmation', $qty, $qty);
					}
				}
			}

			foreach ($counts as $ipn => $cnt) {
				if ($cnt['counted'] < $cnt['listed']) $problems[] = 'IPN '.$ipn.' lists '.$cnt['listed'].' on hand; only a count of '.$cnt['counted'].' has been uplaoded';
			}

			echo json_encode(array('status' => count($problems), 'errors' => $problems));
			exit();
			break;
		default:
			break;
	}
}

$allocated_array = ck_ipn2::get_legacy_allocated_ipns();

if (!empty($_GET['formDD'])) {
	$filter = $_GET['formDD'];
	if ($filter == '10000') {
		$filter = 0;
	}
}
else {
	$now = time();
	$then = strtotime(@$_GET['formDate']);
	$diff = $now - $then;
	$filter = (ceil($diff / (60 * 60 * 24))) -1 ;
}

$date_filter = " AND 1 ";
$serialized_flag = '0';

if ($filter != '10000') {
	if (isset($_GET['formSerialFilter']) && $_GET['formSerialFilter'] == '1') {
		$serialized_flag = '1';
		$date_filter = " AND (sh.confirmation_date IS NULL OR UNIX_TIMESTAMP(sh.confirmation_date) < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL $filter DAY))) ";
	}
	else {
		$date_filter = " AND UNIX_TIMESTAMP(psc.last_quantity_change) < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL $filter DAY)) ";
	}
}

$velocity_filter = '';

$product_counts = prepared_query::fetch('SELECT COUNT(it.stock_id) as total, COUNT(it1.stock_id) as unsold FROM ck_ipn_tiers it LEFT JOIN ck_ipn_tiers it1 ON it.stock_id = it1.stock_id AND it1.units_sold = 0', cardinality::ROW);

switch (@$_GET['product_velocity']) {
	case 'sold':

		$velocity_filter .= 'JOIN ck_ipn_tiers it ON psc.stock_id = it.stock_id AND it.units_sold > 0';
		break;
	case 'unsold':
		$velocity_filter .= 'JOIN ck_ipn_tiers it ON psc.stock_id = it.stock_id AND it.units_sold = 0';
		break;
	case '5':
		$limit = round($product_counts['total'] * .05);
		$velocity_filter .= 'JOIN (SELECT * FROM ck_ipn_tiers ORDER BY units_sold DESC LIMIT '.$limit.') it ON psc.stock_id = it.stock_id';
		break;
	case '10':
		$limit = round($product_counts['total'] * .10);
		$velocity_filter .= 'JOIN (SELECT * FROM ck_ipn_tiers ORDER BY units_sold DESC LIMIT '.$limit.') it ON psc.stock_id = it.stock_id';
		break;
	case '20':
		$limit = round($product_counts['total'] * .20);
		$velocity_filter .= 'JOIN (SELECT * FROM ck_ipn_tiers ORDER BY units_sold DESC LIMIT '.$limit.') it ON psc.stock_id = it.stock_id';
		break;
	case 'all': // no filters

	default:
		break;
}

//MMD - 083011 - adding in clause to display unserialized items with 0 in stock that have a
//bin location set
$ipn_query_string =<<<SQL
	SELECT
		pscc.name as category_name,
		psc.stock_id,
		psc.stock_name,
		s5.id as serial_id,
		sh.id as serial_history_id,
		s5.serial,
		s5.status,
		ss.name as serial_status,
		sh.bin_location as serial_bin_location,
		sh.confirmation_date,
		IF(
			psc.serialized = '0',
			psc.stock_quantity,
			(
				SELECT count(s.id)
				FROM serials s
				WHERE s.ipn = psc.stock_id
				AND s.status IN(2, 3, 6)
			)
		) as stock_quantity,
		psc.last_quantity_change,
		psce.stock_location,
		psce.stock_location_2,
		(
			SELECT SUM(ih.quantity)
			FROM inventory_hold ih
			WHERE ih.stock_id = psc.stock_id
		) as onHold
	FROM
		products_stock_control psc
			LEFT JOIN products_stock_control_extra psce ON psc.stock_id = psce.stock_id
			LEFT JOIN products_stock_control_categories pscc ON psc.products_stock_control_category_id = pscc.categories_id
			LEFT JOIN serials s5 ON s5.ipn = psc.stock_id AND s5.status in (2, 3, 6)
			LEFT JOIN serials_history sh ON s5.id = sh.serial_id AND sh.id = (SELECT MAX(sh2.id) FROM serials_history sh2 WHERE sh2.serial_id = s5.id)
			LEFT JOIN serials_status ss ON s5.status = ss.id
			$velocity_filter
	WHERE
		(IF(
			psc.serialized = '0',
			psc.stock_quantity,
			(
				SELECT count(s.id)
				FROM serials s
				WHERE s.ipn = psc.stock_id
				AND s.status IN(2, 3, 6)
			)
		) > 0 OR
		(psc.serialized = '0' AND
		psc.stock_quantity <= '0' AND
		trim(psce.stock_location) != '')) {$date_filter} AND
		psc.serialized = $serialized_flag
	ORDER BY
		sh.bin_location ASC,
		psce.stock_location ASC,
		psc.stock_name ASC
SQL;

$ipns = prepared_query::fetch($ipn_query_string, cardinality::SET);

$cc_file =  realpath(__DIR__.'/data_management').'/cycle-count.csv';
$cc_web_file = '/admin/data_management/cycle-count.csv';

if (!empty($_GET['excel'])) {
	header('Expires: 0');
	header('Cache-control: private');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Content-Description: File Transfer');
	header('Content-Type: text/csv');
	header('Content-disposition: attachment;filename=inventory_exports_'.time().'.xls');
   	readfile($cc_file);

    exit();
} ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<script>
		jQuery(document).ready(function() {
			jQuery('.confirmButton').click(function(event) {
				event.preventDefault();
				var id = jQuery(this).attr('id').split('_')[0];
				var cqty = jQuery('#'+id+'_confirmQty').val();
				var cipn = jQuery('#'+id+'_confirmIPN').val();
				jQuery.ajax({
					url: '/admin/cycle-count.php',
					data: 'confirm='+id+'&confirmQty='+cqty+'&confirmIPN='+cipn,
					success: function(msg) {
						if (msg == 'success') {
							alert("IPN "+cipn+" has been confirmed!");
							window.location.reload();
						}
					}
				});
			});

			jQuery('.serialConfirmButton').click(function(event) {
				event.preventDefault();
				var id = jQuery(this).attr('id');
				jQuery.ajax({
					url: '/admin/cycle-count.php',
					data: 'serial_confirm='+id,
					success: function(msg) {
						if (msg == 'success') {
							alert("The serial has been confirmed!");
							//window.location.reload();
						}
					}
				});
			});
		});
	</script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td class="noPrint" width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<div style="padding: 0 8px 0 8px;">
					<h3 class="pagetitle">IPN Inventory Form</h3>
					<form action="" method="get">
						<div>
							<select id="formSerialFilter" name="formSerialFilter">
								<option value="0" <?= (@$_GET['formSerialFilter'] == 0 ? 'selected' : '');?>>Unserialized IPNs</option>
								<option value="1" <?= (@$_GET['formSerialFilter'] == 1 ? 'selected' : '');?>>Serialized IPNs</option>
							</select>
							<select id="formDD" name="formDD">
								<option value="10" <?= @$_GET['formDD']==10?'selected':''; ?>>10 Days</option>
								<option value="30" <?= @$_GET['formDD']==30||empty($_GET['formDD'])?'selected':''; ?>>30 Days</option>
								<option value="45" <?= @$_GET['formDD']==45?'selected':''; ?>>45 Days</option>
								<option value="60" <?= @$_GET['formDD']==60?'selected':''; ?>>60 Days</option>
								<option value="80" <?= @$_GET['formDD']==80?'selected':''; ?>>80 Days</option>
								<option value="10000" <?= @$_GET['formDD']==10000?'selected':''; ?>>All</option>
								<option value="custom" <?= @$_GET['formDD']=='custom'?'selected':''; ?>>Custom</option>
							</select>
							<input type="text" id="formDate" name="formDate">
							<select id="product_velocity" name="product_velocity" size="1">
								<option value="all">ALL IPNS (<?= $product_counts['total']; ?>)</option>
								<option value="sold" <?= @$_GET['product_velocity']=='sold'?'selected':''; ?>>IPNS W/MOVEMENT (<?= $product_counts['total']-$product_counts['unsold']; ?>)</option>
								<option value="5" <?= @$_GET['product_velocity']=='5'?'selected':''; ?>>TOP 5% MOVERS (<?= round($product_counts['total']*.05); ?>)</option>
								<option value="10" <?= @$_GET['product_velocity']=='10'?'selected':''; ?>>TOP 10% MOVERS (<?= round($product_counts['total']*.10); ?>)</option>
								<option value="20" <?= @$_GET['product_velocity']=='20'?'selected':''; ?>>TOP 20% MOVERS (<?= round($product_counts['total']*.20); ?>)</option>
								<option value="unsold" <?= @$_GET['product_velocity']=='unsold'?'selected':''; ?>>IPNS W/NO SALES (<?= $product_counts['unsold']; ?>)</option>
							</select>
							<input type="submit" value="Filter">
							<a href="<?= $cc_web_file; ?>">Export to Excel</a>
						</div>
					</form>
					<script>
						jQuery(document).ready(function($) {
							$('#formDate').datepicker();

							$('#formDD').change(function(event) {
								var formDD = $(this).val();
								if (formDD == '10000') formDD = '0';
								$('#formDate').datepicker('setDate', -formDD);
							});

							var formDD = $('#formDD').val();

							if (formDD == '10000') formDD = '0';
							if (formDD != '' && formDD != 'custom') $('#formDate').datepicker('setDate', -formDD);
						});
					</script>
					<div id="overlay" style="display: none;">Please wait...</div>
					<?php if (!$serialized_flag) { ?>
					<form action="/cycle-count.php" method="post" id="mass-confirm">
						<input type="hidden" name="action" value="mass-confirm">
						<input type="hidden" name="context" value="ipn">
						<input type="submit" name="action-type" value="Confirm:Selections">
					</form>
					<script>
						jQuery('#mass-confirm').submit(function(e) {
							e.preventDefault();

							var $form = jQuery(this);
							var $submit = jQuery(document.activeElement);

							var data = $form.serialize();

							var actype = 'Confirm:All';
							if ($submit && $submit.attr('name') == 'action-type') actype = $submit.val();
							data += '&action-type='+actype;

							// if we know, for certain, that we only want the textbox, just stop here
							if (actype == 'Confirm:List') {}
							else {
								jQuery('#grid').find('input[type=checkbox]:checked').each(function() {
									data += '&'+jQuery(this).attr('name')+'='+jQuery(this).val();
								});
							}

							jQuery.ajax({
								url: '/admin/cycle-count.php',
								type: 'POST',
								data: data,
								dataType: 'json',
								success: function(result) {
									if (result.status == 0) {
										alert("The ipn(s) have been confirmed!");
										//window.location.reload();
									}
									else {
										alert("The following ipn(s) have experienced problems:\n"+result.errors);
									}
								}
							});
						});
					</script>
					<table id="grid" class="tablesorter" style="border: 4px solid #e0e0e0;">
						<thead>
							<tr>
								<th>Bin #1</th>
								<th>IPN</th>
								<th>Category</th>
								<th>Bin #2</th>
								<th>On Hand</th>
								<th>Hold Qty</th>
								<th>Allocated</th>
								<th>Available</th>
								<th>Updated</th>
								<th>New Qty</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php if (!empty($ipns)) {
								$fh = fopen($cc_file, 'w');

								$col_headers = ['Bin #1', 'IPN', 'Category', 'Bin #2', 'On Hand', 'Hold Qty', 'Allocated', 'Available', 'Updated', 'New Qty', 'Action'];

								if (is_resource($fh)) fputcsv($fh, $col_headers);  //cold headers

								foreach ($ipns as $ipn) {
									$onHand = $ipn['stock_quantity'];
									$onHold = empty($ipn['onHold'])?0:$ipn['onHold'];
									$allocated = empty($allocated_array[$ipn['stock_id']])?0:$allocated_array[$ipn['stock_id']];
									$avail = $onHand - $onHold - $allocated;
									$stock_location1 = $ipn['stock_location'];
									$stock_name = $ipn['stock_name'];
									$category_name = $ipn['category_name'];
									$stock_location2 = $ipn['stock_location_2'];

									if (isset($ipn['last_quantity_change'])) $updated = date('m/d/y', strtotime($ipn['last_quantity_change']));
									else $updated = '';

									$data = [$stock_location1, $stock_name, $category_name, $stock_location2, $onHand, $onHold, $allocated, $avail, $updated];

									if (is_resource($fh)) fputcsv($fh, $data); ?>
							<tr>
								<td><?= $stock_location1; ?></td>
								<td><?= $stock_name; ?></td>
								<td><?= $category_name; ?></td>
								<td><?= $stock_location2; ?></td>
								<td><?= $onHand; ?></td>
								<td><?= $onHold; ?></td>
								<td><?= $allocated; ?></td>
								<td><?= $avail; ?></td>
								<td><?= date('m/d/y', strtotime($ipn['last_quantity_change'])); ?></td>
								<td style="border-bottom: 1px solid black;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
								<td>
									<form action="" method="GET">
										<input type="hidden" id="<?= $ipn['stock_id']; ?>_confirmQty" value="<?= $ipn['stock_quantity']; ?>">
										<input type="hidden" id="<?= $ipn['stock_id']; ?>_confirmIPN" value="<?= $ipn['stock_name']; ?>">
										<input type="submit" id="<?= $ipn['stock_id']; ?>_button" class="confirmButton" value="Confirm">
									</form>
									<input type="checkbox" name="ipn[<?= $ipn['stock_id']; ?>]" value="<?= $ipn['stock_quantity']; ?>">
								</td>
							</tr>
								<?php }

								if (is_resource($fh)) fclose($fh);
							} ?>
						</tbody>
					</table>
					<script type="text/javascript">
						jQuery('#grid').tablesorter({
							headers: {
								1: { sorter: "text" },
								4: { sorter: "digit" },
								5: { sorter: "digit" },
								6: { sorter: "digit" },
								8: { sorter: false },
								9: { sorter: false },
								10: { sorter: false }
							},
							sortList: [[0,0]]
						});

						jQuery("#grid")
							.bind("sortStart", function() { jQuery("#overlay").show(); })
							.bind("sortEnd", function() { jQuery("#overlay").hide(); });
					</script>
					<?php }
					else { ?>
					<form action="/cycle-count.php" method="post" id="mass-confirm">
						<input type="hidden" name="action" value="mass-confirm">
						<input type="hidden" name="context" value="serial">
						<textarea name="serials-list" cols="60" rows="4"></textarea><br>
						<input type="submit" name="action-type" value="Confirm:ALL">
						<input type="submit" name="action-type" value="Confirm:List">
						<input type="submit" name="action-type" value="Confirm:Selections">
					</form>
					<script>
						jQuery('#mass-confirm').submit(function(e) {
							e.preventDefault();

							var $form = jQuery(this);
							var $submit = jQuery(document.activeElement);

							var data = $form.serialize();

							var actype = 'Confirm:All';
							if ($submit && $submit.attr('name') == 'action-type') actype = $submit.val();
							data += '&action-type='+actype;

							// if we know, for certain, that we only want the textbox, just stop here
							if (actype == 'Confirm:List') {}
							else {
								jQuery('#grid').find('input[type=checkbox]:checked').each(function() {
									data += '&'+jQuery(this).attr('name')+'=1';
								});
							}

							console.log(data);

							jQuery.ajax({
								url: '/admin/cycle-count.php',
								type: 'POST',
								data: data,
								dataType: 'json',
								success: function(result) {
									console.log(result);
									if (result.status == 0) {
										alert("The serial(s) have been confirmed!");
										window.location.reload();
									}
									else {
										alert("The following serial(s) have experienced problems:\n"+result.errors);
									}
								}
							});
						});
					</script>
					<table id="grid" class="tablesorter" style="border: 4px solid #e0e0e0;">
						<thead>
							<tr>
								<th>Serial Bin</th>
								<th>IPN</th>
								<th>Category</th>
								<th>IPN Bin #1</th>
								<th>IPN Bin #2</th>
								<th>Serial</th>
								<th>Status</th>
								<th>Confirmed</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php if (!empty($ipns)) {
								foreach ($ipns as $ipn) { ?>
							<tr>
								<td><?= $ipn['serial_bin_location']; ?></td>
								<td><?= $ipn['stock_name']; ?></td>
								<td><?= $ipn['category_name']; ?></td>
								<td><?= $ipn['stock_location']; ?></td>
								<td><?= $ipn['stock_location_2']; ?></td>
								<td><?= $ipn['serial']; ?></td>
								<td>
									<?= $ipn['serial_status'];
									if ($ipn['status'] == '6') {
										try {
											$hold_reason = prepared_query::fetch('SELECT ihr.description FROM inventory_hold ih JOIN inventory_hold_reason ihr ON ih.reason_id = ihr.id WHERE ih.serial_id = :serial_id', cardinality::SINGLE, [':serial_id' => $ipn['serial_id']]);
											echo '('.$hold_reason.')';
										}
										catch (Exception $e) {
											echo '(HOLD RECORD MISSING)';
										}
									} ?>
								</td>
								<td><?= $ipn['confirmation_date']?date('m/d/y', strtotime($ipn['confirmation_date'])):'Unconfirmed' ;?></td>
								<td>
									<form action="" method="get">
										<input type="submit" id="<?= $ipn['serial_history_id']; ?>" class="serialConfirmButton" value="Confirm">
									</form>
									<input type="checkbox" name="serial_history_id[<?= $ipn['serial_history_id']; ?>]">
								</td>
							</tr>
								<?php }
							} ?>
						</tbody>
					</table>
					<script type="text/javascript">
						jQuery('#grid').tablesorter({
							headers: {
								1: {sorter: "text"},
								4: {sorter: false},
								5: {sorter: false},
								6: {sorter: false},
								7: {sorter: false}
							},
							sortList: [[0,0]]
						});

						jQuery("#grid")
							.bind("sortStart", function() { jQuery("#overlay").show(); })
							.bind("sortEnd", function() { jQuery("#overlay").hide(); });
					</script>
					<?php } ?>
				</div>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
<html>
