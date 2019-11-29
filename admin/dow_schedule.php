<?php
require('includes/application_top.php');

if (!empty($_REQUEST['action'])) {
	if (is_array($_REQUEST['action'])) {
		foreach ($_REQUEST['action'] as $dow_schedule_id => $action) {
			// all we care about is that the $dow_schedule_id and $action variables get populated
		}
	}
	else {
		$action = $_REQUEST['action'];
	}

	$result = (object) array('status' => 1, 'message' => '');

	if ($action == 'ADD') {
		// $dow_schedule_id == 'new'
		$start_date = new DateTime($_REQUEST['start_date']['new']);
		//$model_number = $_REQUEST['model_number']['new'];
		$products_id = $_REQUEST['products_id']['new'];
		$specials_price = preg_replace('/[^0-9.]/', '', $_REQUEST['specials_price']['new'])?preg_replace('/[^0-9.]/', '', $_REQUEST['specials_price']['new']):NULL;
		//$active = $_REQUEST['active']['new'];

		/*if (!($products_id = prepared_query::fetch('SELECT products_id FROM products WHERE products_model LIKE ?', cardinality::SINGLE, array($model_number)))) {
			$result->status = 0;
			$result->message = "The model number [$model_number] could not be located in the database.";
		}
		else {*/
			prepared_query::execute('INSERT INTO ck_dow_schedule (products_id, start_date, specials_price, active) VALUES (?, ?, ?, 0)', array($products_id, $start_date->format('Y-m-d'), $specials_price));
		/*}*/
	}
	elseif ($action == 'UPDATE') {
		$active = $_REQUEST['active'][$dow_schedule_id];
		if ($active) $start_date = new DateTime();
		elseif (empty($_REQUEST['start_date'][$dow_schedule_id])) $start_date = NULL;
		else $start_date = new DateTime($_REQUEST['start_date'][$dow_schedule_id]);
		$specials_price = preg_replace('/[^0-9.]/', '', $_REQUEST['specials_price'][$dow_schedule_id])?preg_replace('/[^0-9.]/', '', $_REQUEST['specials_price'][$dow_schedule_id]):NULL;

		if (empty($start_date) && prepared_query::fetch('SELECT dow_schedule_id FROM ck_dow_schedule WHERE dow_schedule_id = ? AND active = 1', cardinality::SINGLE, array($dow_schedule_id))) {
			$result->status = 0;
			$result->message = 'You cannot delete a currently active DOW. You must set another DOW active first.';
		}
		else {
			if (empty($start_date)) prepared_query::execute('DELETE FROM ck_dow_schedule WHERE dow_schedule_id = ?', array($dow_schedule_id));
			else prepared_query::execute('UPDATE ck_dow_schedule SET start_date = ?, specials_price = ? WHERE dow_schedule_id = ?', array($start_date->format('Y-m-d'), $specials_price, $dow_schedule_id));

			if (!empty($active)) {
				//dow::reset_old_images();

				// if we find a new dow, set it and unset the old one, otherwise leave it
				$new = dow::get_new_dow();
				if (!empty($new)) {
					//dow::set_dow_images($new);

					dow::switch_active($new);
				}
			}
		}
	}
	elseif ($action == 'UPDATE ALL') {
		foreach ($_REQUEST['init'] as $idx => $dow_schedule_id) {
			$active = $_REQUEST['active'][$dow_schedule_id];
			if ($active) $start_date = new DateTime();
			elseif (empty($_REQUEST['start_date'][$dow_schedule_id])) $start_date = NULL;
			else $start_date = new DateTime($_REQUEST['start_date'][$dow_schedule_id]);
			$specials_price = preg_replace('/[^0-9.]/', '', $_REQUEST['specials_price'][$dow_schedule_id])?preg_replace('/[^0-9.]/', '', $_REQUEST['specials_price'][$dow_schedule_id]):NULL;

			if (empty($start_date) && prepared_query::fetch('SELECT dow_schedule_id FROM ck_dow_schedule WHERE dow_schedule_id = ? AND active = 1', cardinality::SINGLE, array($dow_schedule_id))) {
				$result->status = 0;
				$result->message = 'You cannot delete a currently active DOW. You must set another DOW active first.';
			}
			else {
				if (empty($start_date)) prepared_query::execute('DELETE FROM ck_dow_schedule WHERE dow_schedule_id = ?', array($dow_schedule_id));
				else prepared_query::execute('UPDATE ck_dow_schedule SET start_date = ?, specials_price = ? WHERE dow_schedule_id = ?', array($start_date->format('Y-m-d'), $specials_price, $dow_schedule_id));

				if (!empty($active)) {
					//dow::reset_old_images();

					// if we find a new dow, set it and unset the old one, otherwise leave it
					$new = dow::get_new_dow();
					if (!empty($new)) {
						//dow::set_dow_images($new);

						dow::switch_active($new);
					}
				}
			}
		}
	}
	elseif ($action == 'GO') {
		$dow_schedule_id = $_REQUEST['dow_schedule_id'];
		$active = $_REQUEST['active'];
		$stock_id = $_REQUEST['stock_id'];
		$products_id = $_REQUEST['products_id'];
		if ($active) $start_date = new DateTime();
		elseif (empty($_REQUEST['start_date'])) $start_date = NULL;
		else $start_date = new DateTime($_REQUEST['start_date']);

		$result->message = 'You must refresh the page to continue to manage the DOW for this product.';

		if (empty($dow_schedule_id)) {
			if (empty($start_date)) {
				$result->message = 'You must provide a start date to set up a new DOW';
				$result->status = 0;
			}
			elseif (prepared_query::fetch('SELECT dow_schedule_id FROM ck_dow_schedule WHERE start_date = ?', cardinality::SINGLE, array($start_date->format('Y-m-d')))) {
				$result->message = 'There is already a DOW scheduled for your chosen date. Please go to the management page to resolve.';
				$result->status = 2;
			}
			else prepared_query::execute('INSERT INTO ck_dow_schedule (products_id, start_date, active) VALUES (?, ?, 0)', array($products_id, $start_date->format('Y-m-d')));
		}
		else {
			if (empty($start_date) && prepared_query::fetch('SELECT dow_schedule_id FROM ck_dow_schedule WHERE dow_schedule_id = ? AND active = 1', cardinality::SINGLE, array($dow_schedule_id))) {
				$result->status = 2;
				$result->message = 'You cannot delete a currently active DOW. You must set another DOW active first.';
			}
			else {
				if (empty($start_date)) prepared_query::execute('DELETE FROM ck_dow_schedule WHERE dow_schedule_id = ?', array($dow_schedule_id));
				else prepared_query::execute('UPDATE ck_dow_schedule SET start_date = ? WHERE dow_schedule_id = ?', array($start_date->format('Y-m-d'), $dow_schedule_id));

				if (!empty($active)) {
					//dow::reset_old_images();

					// if we find a new dow, set it and unset the old one, otherwise leave it
					$new = dow::get_new_dow();
					if (!empty($new)) {
						//dow::set_dow_images($new);

						dow::switch_active($new);
					}
				}
			}
		}
	}

	echo json_encode($result);
	exit();
}

$dows = prepared_query::fetch('SELECT ds.dow_schedule_id, ds.products_id, ds.start_date, ds.specials_price, ds.active, p.products_model, psc.stock_name FROM ck_dow_schedule ds JOIN products p ON ds.products_id = p.products_id JOIN products_stock_control psc ON p.stock_id = psc.stock_id ORDER BY ds.start_date DESC', cardinality::SET);

?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
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
					<style>
						#dow-schedule td, #dow-schedule th { border-style:solid; border-color:#000; border-width:0px 1px 1px 0px; padding:5px 8px; }
						#dow-schedule tr:first-child td, #dow-schedule tr:first-child th { border-top-width:1px; }
						#dow-schedule td:first-child, #dow-schedule th:first-child { border-left-width:1px; }
						#dow-schedule .row-0 td { background-color:#dedede; }
						#dow-schedule .row-1 td { background-color:#ababab; }
						#dow-schedule .active-dow td { background-color:#cfc; }
					</style>
					<form style="display:inline;" id="dow_control" action="/admin/dow_schedule.php" method="post">
						<div class="manage">
							Dow Schedule Type:
							<select name="site_keys[dow.schedule_type]" size="1">
								<option value=""></option>
								<option value="weekdays" <?= $ck_keys->dow['schedule_type']=='weekdays'?'selected':''; ?>>Weekdays</option>
								<option value="weekly" <?= $ck_keys->dow['schedule_type']=='weekly'?'selected':''; ?>>Weekly</option>
							</select>
							<input type="submit" name="global_action" value="UPDATE CONFIG">
						</div>
						<p style="color:#c00;">
							If you override and set a new dow active manually, you must have the correct images prepared and uploaded to the staging directory. They must be the only images in that folder.
						</p>
						<input type="submit" name="action" value="UPDATE ALL">
						<table id="dow-schedule" cellpadding="0" cellspacing="0">
							<thead>
								<tr>
									<th>Date</th>
									<th>IPN</th>
									<th>Product Model</th>
									<th>DOW Price</th>
									<th>Active</th>
									<th>Control</th>
									<th>Link</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><input type="date" name="start_date[new]"></td>
									<td>
										<input id="ipn_lookup" type="text" name="ipn[new]" value="">
									</td>
									<td><input type="hidden" id="product_lookup" name="products_id[new]" value=""><span id="model_lookup"></span></td>
									<td>$<input type="text" name="specials_price[new]" style="width:65px;" value=""></td>
									<td>
										<!--select size="1" name="active[new]">
											<option value="0">inactive</option>
											<option value="1">ACTIVE</option>
										</select-->
									</td>
									<td><input type="submit" name="action[new]" value="ADD"></td>
									<td></td>
								</tr>
								<?php foreach ($dows as $idx => $dow) { ?>
								<tr class="row-<?php echo $idx%2; ?> <?php echo $dow['active']?'active-dow':''; ?>">
									<td><input type="hidden" name="init[]" value="<?= $dow['dow_schedule_id']; ?>" class="scheduled_dows"><input type="date" name="start_date[<?= $dow['dow_schedule_id']; ?>]" value="<?= $dow['start_date']; ?>"></td>
									<td><a href="/admin/ipn_editor.php?ipnId=<?= $dow['stock_name']; ?>" target="_blank"><?= $dow['stock_name']; ?></a></td>
									<td id="dow-model-<?= $dow['dow_schedule_id']; ?>"><?= $dow['products_model']; ?></td>
									<td id="price-<?= $dow['dow_schedule_id']; ?>">$<input type="text" name="specials_price[<?= $dow['dow_schedule_id']; ?>]" style="width:65px;" value="<?= $dow['specials_price']; ?>"></td>
									<td>
										<?php if (!empty($dow['active'])) { ?>
										ACTIVE
										<?php }
										else { ?>
										<select size="1" name="active[<?= $dow['dow_schedule_id']; ?>]">
											<option value="0" <?php echo !$dow['active']?'selected':''; ?>>inactive</option>
											<option value="1" <?php echo $dow['active']?'selected':''; ?>>ACTIVE</option>
										</select>
										<?php } ?>
									</td>
									<td id="dow_schedule"><input type="submit" name="action[<?= $dow['dow_schedule_id']; ?>]" value="UPDATE"></td>
									<td><a href="/dow/?edit-dow-id=<?= $dow['dow_schedule_id']; ?>" target="_blank">[PREVIEW]</a></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</form>
					<script>
						jQuery('input[type=submit]').click(function() {
							jQuery('input[type=submit]').removeAttr('clicked');
							jQuery(this).attr('clicked', 1);
						});
						jQuery('#dow_control').submit(function(e) {
							jQuery('#dow_schedule').css('background-color', '#ffc;');
							var qstring = jQuery(this).serialize();
							var submit_key = jQuery('input[type=submit][clicked=1]').attr('name'), submit_val = jQuery('input[type=submit][clicked=1]').val();
							qstring += '&'+encodeURIComponent(submit_key)+'='+encodeURIComponent(submit_val);
							var send = {
								url: jQuery(this).attr('action'),
								type: jQuery(this).attr('method').toUpperCase(),
								dataType: 'json',
								data: qstring,
								success: function(data, textStatus, jqXHR) {
									if (data == null) return;

									if (data.message) alert(data.message);

									if (data.status == 1) setTimeout(function() { location.reload(true); }, 1000);
								},
								error: function(jqXHR, textStatus, errorThrown) {
									jQuery('#dow_schedule').css('background-color', '#fcc;'); // error
								}
							};
							var halt = false;
							if (submit_val == 'UPDATE ALL') {
								// we're updating all currently existing dow entries
								jQuery('.scheduled_dows').each(function() {
									var dow_schedule_id = jQuery(this).val();
									if (jQuery('input[name="start_date['+dow_schedule_id+']"]').val() == '') {
										if (!confirm('Are you sure you want to remove the DOW for product model '+jQuery('#dow-model-'+dow_schedule_id).html()+'?')) {
											halt = true;
										}
									}
									if (jQuery('select[name="active['+dow_schedule_id+']"]').val() == 1) {
										if (!confirm('Please confirm that you want to make product model '+jQuery('#dow-model-'+dow_schedule_id).html()+' active. This will reset the scheduled date to today.'+"\n\n"+'PLEASE NOTE: If you have selected more than one line to make active, only the last one found will actually remain active.')) {
											halt = true;
										}
									}
								});
								if (!halt) jQuery.ajax(send);
							}
							else if (submit_val == 'UPDATE') {
								var dow_schedule_id = submit_key.replace(/^action\[(\d+)\]$/, "$1");
								if (jQuery('input[name="start_date['+dow_schedule_id+']"]').val() == '') {
									if (!confirm('Are you sure you want to remove the DOW for product model '+jQuery('#dow-model-'+dow_schedule_id).html()+'?')) {
										halt = true;
									}
								}
								if (jQuery('select[name="active['+dow_schedule_id+']"]').val() == 1) {
									if (!confirm('Please confirm that you want to make product model '+jQuery('#dow-model-'+dow_schedule_id).html()+' active. This will reset the scheduled date to today.')) {
										halt = true;
									}
								}
								if (!halt) jQuery.ajax(send);
							}
							else if (submit_val == 'UPDATE CONFIG') {
								// don't need to do anything
								return true;
							}
							else if (jQuery('input[name="start_date[new]"]').val() == '' || jQuery('input[name="products_id[new]"]').val() == '') {
								halt = true;
								alert('You have attempted to add a new DOW without a date and/or product listing. Both of those are required to add a new entry');
							}
							else if (jQuery('select[name="active[new]"]').val() == 1) {
								if (confirm('Please confirm that you intend to set your new DOW active immediately, overriding any currently active DOW')) {
									jQuery.ajax(send);
								}
							}
							else {
								// we're adding a new one and we've got all of the information we need
								jQuery.ajax(send);
							}
							e.preventDefault();
							return false;
						});
						jQuery('#ipn_lookup').autocomplete({
							minChars: 3,
							source: function(request, response) {
								jQuery.ajax({
									minLength: 2,
									url: '/admin/serials_ajax.php?action=ipn_autocomplete',
									dataType: 'json',
									data: {
										term: request.term,
										search_type: 'ipn',
										result_type: 'dow'
									},
									success: response
								});
							},
							select: function(event, ui) {
								if (ui.item.products_id) {
									jQuery('#product_lookup').val(ui.item.products_id);
									jQuery('#model_lookup').text(ui.item.products_model);
								}
								else {
									alert('The DOW requires an active product listing.');
								}
							}
						});
					</script>
				</td>
			</tr>
		</table>
	</body>
</html>
