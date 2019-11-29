<?php
require('includes/application_top.php');
require(DIR_WS_LANGUAGES.$_SESSION['language'].'/'.'information.php');

$adgrafics_information = !empty($_REQUEST['adgrafics_information'])?$_REQUEST['adgrafics_information']:NULL;
$information_id = !empty($_REQUEST['information_id'])?$_REQUEST['information_id']:NULL;

function browse_information() {
	return prepared_query::fetch('SELECT * FROM information WHERE languages_id = ? ORDER BY v_order', cardinality::SET, $_SESSION['languages_id']);
}

function read_data($information_id) {
	return prepared_query::fetch('SELECT * FROM information WHERE information_id = ?', cardinality::ROW, $information_id);
}

$warning = tep_image(DIR_WS_ICONS.'warning.gif', WARNING_INFORMATION);

function error_message($error) {
	global $warning;
	switch ($error) {
		case "20":
			return "<tr class=messageStackError><td>$warning .".ERROR_20_INFORMATION."</td></tr>";
			break;
		case "80":
			return "<tr class=messageStackError><td>$warning ".ERROR_80_INFORMATION."</td></tr>";
			break;
		default:
			return $error;
			break;
	}
} ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>

	<?php if (HTML_AREA_WYSIWYG_DISABLE == 'Enable' || HTML_AREA_WYSIWYG_DISABLE_JPSY == 'Enable') { ?>
	<script language="Javascript1.2">
		// load htmlarea
		//MaxiDVD Added WYSIWYG HTML Area Box + Admin Function v1.8 <head>
		_editor_url = "<?= HTTPS_SERVER.DIR_WS_ADMIN; ?>htmlarea/"; // URL to htmlarea files
		var win_ie_ver = parseFloat(navigator.appVersion.split("MSIE")[1]);
		if (navigator.userAgent.indexOf('Mac') >= 0) win_ie_ver = 0;
		if (navigator.userAgent.indexOf('Windows CE') >= 0) win_ie_ver = 0;
		if (navigator.userAgent.indexOf('Opera') >= 0) win_ie_ver = 0;

		<?php if (HTML_AREA_WYSIWYG_BASIC_PD == 'Basic') { ?>
		if (win_ie_ver >= 5.5) {
			document.write('<scr'+'ipt src="'+_editor_url+'editor_basic.js"');
			document.write(' language="Javascript1.2"></scr'+'ipt>');
		}
		else {
			document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>');
		}
		<?php }
		else { ?>
		if (win_ie_ver >= 5.5) {
			document.write('<scr'+'ipt src="'+_editor_url+'editor_advanced.js"');
			document.write(' language="Javascript1.2"></scr'+'ipt>');
		}
		else {
			document.write('<scr'+'ipt>function editor_generate() { return false; }</scr'+'ipt>');
		}
		<?php } ?>
	</script>
	<?php } ?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
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
				<table border=0 width="100%">
					<tr>
						<td align=right><?= $_SESSION['language']; ?></td>
					</tr>
					<?php 
					switch (@$adgrafics_information) {
						case "Added":
							$data = browse_information();
							$no = 1;
							if (sizeof($data) > 0) {
								$no += count($data);
							}
							$title = "".ADD_QUEUE_INFORMATION." #$no";
							echo tep_draw_form('', FILENAME_INFORMATION_MANAGER, 'adgrafics_information=AddSure');
							include('information_form.php');
							break;
						case "AddSure":
							function add_information($data) {
								$data = array_map('addslashes', $data);
								prepared_query::execute('INSERT INTO information VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)', array(!empty($data['visible'])?1:0, $data['v_order'], $data['info_title'], $data['description'], $data['product_ids'], $_SESSION['languages_id'], !empty($data['sitewide_header'])?1:0));
							}
							if (!empty($_REQUEST['v_order']) && !empty($_REQUEST['info_title']) && !empty($_REQUEST['description'])) {
								if ((INT)$_REQUEST['v_order']) {
									add_information($_POST);
									$data = browse_information();
									$title = "".tep_image(DIR_WS_ICONS.'confirm_red.gif', CONFIRM_INFORMATION).SUCCED_INFORMATION.ADD_QUEUE_INFORMATION." ".$_REQUEST['v_order']." ";
									include('information_list.php');
								}
								else {
									$error="20";
								}
							}
							else {
								$error="80";
							}
							break;
						case "Edit":
							if (!empty($information_id)) {
								$edit = read_data($information_id);

								$data = browse_information();
								
								$button = array("Update");
								$title = "".EDIT_ID_INFORMATION." $information_id";
								//echo form("$PHP_SELF?adgrafics_information=Update", $hidden);
								echo tep_draw_form('', FILENAME_INFORMATION_MANAGER, 'adgrafics_information=Update');
								echo tep_draw_hidden_field('information_id', $information_id);
								include('information_form.php');
							}
							else {
								$error = "80";
							}
							break;
						case "Update":
							function update_information($data) {
								$sitewide_header = '0';
								if (!empty($data['sitewide_header'])) {
									$sitewide_header = '1';
								}
								
								//$data = array_map('addslashes', $data);
								//die(var_dump($data));
								
								prepared_query::execute('UPDATE information SET info_title = ?, description = ?, product_ids = ?, visible = ?, v_order = ?, sitewide_header = ? WHERE information_id = ?', array($data['info_title'], $data['description'], $data['product_ids'], @$data['visible'], $data['v_order'], $sitewide_header, $data['information_id'])) or die ("update_information: ".mysql_error());
							}

							if (!empty($information_id) && !empty($_REQUEST['description']) && !empty($_REQUEST['v_order'])) {
								if ((INT)$_REQUEST['v_order']) {
									update_information($_POST);
									$data = browse_information();
									$title = @$_GET['confirm'].' '.UPDATE_ID_INFORMATION.' '.$information_id.' '.SUCCED_INFORMATION;
									include('information_list.php');
								}
								else {
									$error="20";
								}
							}
							else {
								$error="80";
							}
							break;
						case 'Visible':
								function tep_set_information_visible($information_id, $visible) {
									if ($visible == '1') {
										return prepared_query::execute('UPDATE information SET visible = \'0\' WHERE information_id = ?', $information_id);
									}
									else {
										return prepared_query::execute('UPDATE information SET visible = \'1\' WHERE information_id = ?', $information_id);
									}
								}

								tep_set_information_visible($information_id, @$_GET['visible']);

								$data = browse_information();
								if (@$_GET['visible'] == '1') {
									$vivod = DEACTIVATION_ID_INFORMATION;
								}
								else {
									$vivod = ACTIVATION_ID_INFORMATION;
								}
								$title = @$_GET['confirm'].' '.$vivod.' '.$information_id.' '.SUCCED_INFORMATION;
								include('information_list.php');
								break;
						case "Delete":
							if (!empty($information_id)) {
								$delete = read_data($information_id);
								$data = browse_information();
								$title = "".DELETE_CONFITMATION_ID_INFORMATION." $information_id";
								echo "<tr class=pageHeading><td>$title </td></tr>";
								echo "<tr><td>".TITLE_INFORMATION." $delete[info_title]</td></tr><tr><td align=right>";
								echo tep_draw_form('',FILENAME_INFORMATION_MANAGER, "adgrafics_information=DelSure&information_id=$val[information_id]");
								echo tep_draw_hidden_field('information_id', "$information_id");
								echo tep_image_submit('button_delete.gif', IMAGE_DELETE);

								echo '<a href="/admin/information_manager.php">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>';

								echo "</form></td></tr>";
							}
							else {
								$error="80";
							}
							break;
						case "DelSure":
							function delete_information ($information_id) {
								prepared_query::execute('DELETE FROM information WHERE information_id = ?', $information_id);
							}
							if (!empty($information_id)) {
								delete_information($information_id);
								$data = browse_information();
								$title = @$_GET['confirm'].' '.DELETED_ID_INFORMATION.' '.$information_id.' '.SUCCED_INFORMATION;
								include('information_list.php');
							}
							else {
								$error="80";
							}
							break;
						default:
							$data = browse_information();
							$title = "".MANAGER_INFORMATION."";
							include('information_list.php');
					}

					if (!empty($error)) {
						$content = error_message($error);
						echo $content;
						$data = browse_information();
						$no = 1;
						if (sizeof($data) > 0) {
                            $no += count($data);
						}
						$title = "".ADD_QUEUE_INFORMATION." $no";
						echo tep_draw_form('', FILENAME_INFORMATION_MANAGER, 'adgrafics_information=AddSure');
						include('information_form.php');
					} ?>
				</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
