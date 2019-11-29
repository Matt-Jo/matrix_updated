<?php
require('includes/application_top.php');

$removed = false;

/*if (!printfile::station_is_set()) printfile::set_station(printfile::$default_station_id);

if (!empty($_GET['action']) && $_GET['action'] == 'update_receiving_station') {
	echo printfile::set_station($_REQUEST['receiving_station']);
	exit();
}*/

//do the save if necessary
if (isset($_GET['action']) && $_GET['action'] == 'update') {
	if (!$_GET['bin_location'] || trim($_GET['bin_location']) == '' || (empty($_GET['serial_id']) && empty($_GET['serial']))) {
		echo 'ERROR';
		exit();
	}
	if (empty($_GET['serial_id'])) {
		$serial_id = prepared_query::fetch('SELECT id FROM serials WHERE serial = :serial_number', cardinality::SINGLE, [':serial_number' => $_GET['serial']]);
		if (empty($serial_id)) {
			echo '<span style="font-size:18px; color:#c00;">'.$_GET['serial'].' IS NOT A VALID SERIAL NUMBER!!!</span>';
			exit();
		}
		$serial = new ck_serial($serial_id);
	}
	else {
		$serial_id = $_GET['serial_id'];
		$serial = new ck_serial($serial_id);
	}

	//We no longer want to restrict the ability to bin locate a serial while on hold.
	prepared_query::execute('DELETE FROM inventory_hold WHERE serial_id = :serial_id', [':serial_id' => $serial_id]);
	prepared_query::execute('UPDATE serials SET status = 2 WHERE id = :serial_id AND status = 6', [':serial_id' => $serial_id]);

	$serial_history = $serial->get_current_history();

	$serial->update_history_record($serial_history['serial_history_id'], ['bin_location' => $_GET['bin_location'], 'confirmation_date' => date('Y-m-d')]);

	echo $_GET['serial'].' - '.$_GET['bin_location'].($removed==true?' - removed from hold':'');

	$ipn = prepared_query::fetch('SELECT stock_name FROM products_stock_control WHERE stock_id = :stock_id', cardinality::SINGLE, [':stock_id' => $serial->get_header('stock_id')]);

	// send data to print barcodes
	/*try {
		$labels = new printfile('serial');
		$labels->write([[$ipn, $serial->serial, 1, $serial_history->mac_address]]);
		$labels->send_print();

		//$bins = new printfile('bin');
		//$bins->write([[$ipn, $_GET['bin_location'], '', '', 1]]);
		//$bins->send_print();
	}
	catch (Exception $e) {
		$ftp_success = FALSE;
		throw new Exception('Barcode Label could not be printed; Serial record was updated successfully. ['.$e->getMessage().']');
	}*/

	exit();
}
elseif (isset($_GET['action']) && $_GET['action'] == 'serial_lookup') {
	if ($serial_id = prepared_query::fetch('SELECT id FROM serials WHERE serial = :serial_number', cardinality::SINGLE, [':serial_number' => $_GET['serial']])) {
		$serial = new ck_serial($serial_id);

		$serial_history = $serial->get_current_history();

		echo json_encode(['serial_id' => $serial_id, 'bin_location' => $serial_history['bin_location']]);
	}
	else {
		echo json_encode(['serial_id' => NULL, 'bin_location' => 'SERIAL COULD NOT BE FOUND']);
	}
	exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script type="text/javascript" src="../includes/javascript/prototype.js"></script>
	<script type="text/javascript" src="../includes/javascript/scriptaculous/scriptaculous.js"></script>

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
			<td valign="top">
				<h4>Finish Serial Conditioning</h4>
				<h6>Using this page will automatically remove the serial from hold!</h6>
				<div>
					<?php /*Station: 
					<select id="receiving_station_select">
						<?php foreach (printfile::get_stations() as $id => $details) { ?>
						<option value="<?= $id; ?>" <?= printfile::is_selected($id)?'selected':''; ?>><?= $details['name']; ?></option>
						<?php } ?>
					</select>*/ ?>
					Serial: <input type="text" id="serial_autocomplete"><input type="hidden" id="serial_id">&nbsp;&nbsp;&nbsp;
					Current Bin Location: <input type="text" id="current_bin_location" disabled><br>
					Bin Location: <input type="text" id="bin_location">
					<input type="checkbox" name="bulk_putaway" id="bulk_putaway">
					<label for="bulk_putaway" style="font-size: 10px;">Bulk Putaway</label><br>
					<input type="button" id="save_button" value="Save">
				</div>
				<div>
					<h4>Last Updates</h4>
					<ul id="recent_list">
					</ul>
				</div>
				<script type="text/javascript">
					/*jQuery('#receiving_station_select').change(function() {
						jQuery.get('/admin/finish_serial_conditioning.php?action=update_receiving_station&receiving_station='+jQuery(this).val());
					});*/
					jQuery(document).ready(function($) {
						$('#serial_autocomplete').keypress(function(event) {
							if ((event.which && event.which == 13) || (event.keyCode && event.keyCode == 13)) {
								if (jQuery('#bulk_putaway').is(':checked')) jQuery('#save_button').click();
								else {
									jQuery.ajax({
										url: '/admin/finish_serial_conditioning.php',
										data: { action: 'serial_lookup', serial: jQuery(this).val() },
										dataType: 'json',
										success: function(data) {
											$('#serial_id').val(data.serial_id),
											$('#current_bin_location').val(data.bin_location);
											$('#bin_location').focus();
										}
									});
								}
							}
						}).focus();

						$('#bin_location').keypress(function(event) {
							if (
								(event.which	&& event.which == 13	) ||
								(event.keyCode && event.keyCode == 13 )
							) {
								new Ajax.Request('finish_serial_conditioning.php', {
									method:'get',
									parameters: {
										action: 'update',
										serial_id: $('#serial_id').val(),
										bin_location: $('#bin_location').val(),
										serial: $('#serial_autocomplete').val()
									},
									onComplete: function(transport) {
										$('#serial_autocomplete').val('');
										$('#serial_id').val('');
										if (!jQuery('#bulk_putaway').is(':checked')) $('#bin_location').val('');
										$('#current_bin_location').val('');
										$('#serial_autocomplete').focus();

										var newLi = new Element('li');
										newLi.update(transport.responseText);
										$('#recent_list').prepend(newLi)
									}
								});
							}
						});

						$('#save_button').click(function(event) {
							new Ajax.Request('finish_serial_conditioning.php', {
								method:'get',
								parameters: {
									action: 'update',
									serial_id: $('#serial_id').val(),
									bin_location: $('#bin_location').val(),
									serial: $('#serial_autocomplete').val()
								},
								onComplete: function(transport) {
									$('#serial_autocomplete').val('');
									$('#serial_id').val('');
									if (!jQuery('#bulk_putaway').is(':checked')) $('#bin_location').val('');
									$('#current_bin_location').val('');
									$('#serial_autocomplete').focus();

									var newLi = new Element('li');
									newLi.update(transport.responseText);
									$('#recent_list').prepend(newLi)

								}
							});
						});
					});

					function autocompleteHelper(term, callback) {
						if (jQuery('#serial_autocomplete').val() == '') {
							return 0;
						}
						params = {
							action: 'generic_autocomplete',
							search_type: 'serial_bins',
							ipn_id: 1,
							term: term,
							search_all: 1
						}
						jQuery.get('/admin/serials_ajax.php', params, function(data) {
							callback(data);
						}, "json");
					}
				</script>
			</td>
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
