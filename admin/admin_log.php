<?php
require('includes/application_top.php');

if (!empty($_POST['log_text'])) {
	$entry = [
		'text' => $_POST['log_text'],
		'event_date' => ck_datetime::datify($_POST['event_date'], TRUE)->format('Y-m-d'),
		'event_type_id' => $_POST['event_type_id'],
		'visibility' => $_POST['visibility'],
	];
	if (!empty($_POST['id'])) {
		$entry['last_updated'] = prepared_expression::NOW();
		$entry = new prepared_fields($entry, prepared_fields::UPDATE_QUERY);
		$id = new prepared_fields(['id' => $_POST['id']]);

		prepared_query::execute('UPDATE admin_log SET '.$entry->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($entry, $id));
	}
	else {
		$entry['admin_id'] = $_SESSION['login_id'];
		$entry['created'] = prepared_expression::NOW();

		$entry = new prepared_fields($entry, prepared_fields::INSERT_QUERY);

		prepared_query::execute('INSERT INTO admin_log ('.$entry->insert_fields().') VALUES ('.$entry->insert_values().')', $entry->insert_parameters());
	}

	CK\fn::redirect_and_exit('/admin/admin_log.php');
}

if (!empty($_GET['action']) && $_GET['action'] == 'delete') {
	prepared_query::execute('DELETE FROM admin_log WHERE id = :id', [':id' => $_GET['id']]);
	CK\fn::redirect_and_exit('/admin/admin_log.php');
}

$event_types = prepared_query::fetch('SELECT * FROM admin_log_event_type', cardinality::SET);
$visibility_types = array(
	'E' => 'Everyone',
	'A' => 'Admins',
	'M' => 'Only Me'
);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script type="text/javascript" src="../includes/javascript/prototype.js"></script>
	<script type="text/javascript" src="../includes/javascript/scriptaculous/scriptaculous.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script language="javascript" src="includes/general.js"></script>
	<!-- header_eof //-->

	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?= BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?= BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top">
				<table border="0" width="100%" cellspacing="0" cellpadding="0">
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="pageHeading">Captain's Log Viewer</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding-left: 10px; padding-top: 15px;">
							<input type="button" value="Create New Entry" onclick="jQuery('#createAdminLog').dialog({ modal: true }); jQuery('#createAdminLog').dialog('option', 'width', 550); return false;"/>
							<div style="display: none;" id="createAdminLog">
								<form method="POST" action="admin_log.php">
									Event Date:<br/>
									<input type="text" name="event_date" id="create_event_date"/><br/>
									Event Type:<br/>
									<select name="event_type_id" id="create_event_type">
										<?php foreach ($event_types as $event_type) { ?>
										<option value="<?= $event_type['id']; ?>"><?= $event_type['name']; ?></option>
										<?php } ?>
									</select><br/>
									Visibility:<br/>
									<select name="visibility" id="create_visibility">
										<?php foreach ($visibility_types as $id => $name) { ?>
										<option value="<?= $id; ?>"><?= $name; ?></option>
										<?php } ?>
									</select><br/>
									Text:<br/>
									<textarea rows="8" cols="50" name="log_text"></textarea><br/>
									<input type="submit" value="Submit" onclick="if (jQuery('#create_event_date').val().trim() == '') { alert('You must provide an event date.'); return false; }"/>
								</form>
							</div>
							<div style="display: none;" id="editAdminLog">
								<form method="POST" action="admin_log.php">
									Event Date:<br/>
									<input type="text" name="event_date" id="edit_event_date"/><br/>
									Event Type:<br/>
									<select name="event_type_id" id="edit_event_type">
										<?php foreach ($event_types as $event_type) { ?>
										<option value="<?= $event_type['id']; ?>"><?= $event_type['name']; ?></option>
										<?php } ?>
									</select><br/>
									Visibility:<br/>
									<select name="visibility" id="edit_visibility">
										<?php foreach ($visibility_types as $id => $name) { ?>
										<option value="<?= $id; ?>"><?= $name; ?></option>
										<?php } ?>
									</select><br/>
									Text:<br/>
									<textarea rows="8" cols="50" id="edit_log_text" name="log_text"></textarea><br/>
									<input type="hidden" name="id" id="edit_log_id" value=""/>
									<input type="submit" value="Update" onclick="if (jQuery('#edit_event_date').val().trim() == '') { alert('You must provide an event date.'); return false; }"/>
								</form>
							</div>
							<script type="text/javascript">
								jQuery(function() {
									jQuery('#create_event_date').datepicker();
									jQuery('#edit_event_date').datepicker();
								});
								function updateLogEntry(log_id, editor_name) {
									var d = new Date();
									var timeSeparator = ":";
									if (d.getMinutes() < 10) {
										timeSeparator = ":0";
									}
									var dateString = d.toLocaleDateString() + " " + d.getHours() + timeSeparator + d.getMinutes();
									var theText = jQuery('#log_text_' + log_id).html();
									if (jQuery('#last_updated_' + log_id).html().trim() == '') {
										theText = theText + "\n";
									}
									theText = theText + "\n[Edited by " + editor_name + " on " + dateString + "]";
									var regex = /<br\s*[\/]?>/gi;
									jQuery('#edit_log_text').html(theText.replace(regex,"\n")); //we used nl2br on the server side - time to switch back for editing purposes
									jQuery('#edit_log_id').val(log_id);
									jQuery('#edit_event_date').val(jQuery('#event_date_' + log_id).html());
									jQuery('#edit_event_type').val(jQuery('#event_type_' + log_id).attr('event_type_id'));
									jQuery('#edit_visibility').val(jQuery('#visibility_' + log_id).attr('visibility'));
									jQuery('#editAdminLog').dialog({ modal: true });
									jQuery('#editAdminLog').dialog('option', 'width', 550);
									return false;
								}
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<script type="text/javascript">
								jQuery(document).ready(function($) {
									jQuery("#adminLog").tablesorter({
										sortList: [[0,1]]
									});
								});
							</script>
							<style type="text/css">
								.tablesorter td{
									word-wrap: break-word;
								}
							</style>
							<table class="tablesorter" id="adminLog" style="table-layout: fixed;" cellpadding="10px">
								<thead>
									<tr>
										<th class="header" style="padding: 5px;">Event Date</th>
										<th class="header" style="padding: 5px;">Event Type</th>
										<th class="header" style="padding: 5px;">Visibility</th>
										<th class="header" style="padding: 5px;" colspan="3">Message</th>
										<th class="header" style="padding: 5px;">Created By</th>
										<th class="header" style="padding: 5px;">Created</th>
										<th class="header" style="padding: 5px;">Last Updated</th>
										<th class="header" style="padding: 5px;">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php $admin = ck_admin::login_instance();
									$entries = prepared_query::fetch('SELECT al.id, al.admin_id, al.text, al.created, al.last_updated, al.event_date, al.event_type_id, alet.name as event_type, al.visibility FROM admin_log al LEFT JOIN admin_log_event_type alet ON al.event_type_id = alet.id WHERE al.visibility = :everyone OR (al.visibility = :me AND al.admin_id = :admin_id) OR (al.visibility = :admin AND :admin_groups_id = 1)', cardinality::SET, [':everyone' => 'E', ':me' => 'M', ':admin' => 'A', ':admin_id' => $admin->id(), ':admin_groups_id' => $admin->get_header('legacy_group_id')]);

									foreach ($entries as $entry) {
										$entry['admin'] = new ck_admin($entry['admin_id']); ?>
									<tr>
										<td id="event_date_<?= $entry['id']; ?>" style="padding: 5px;"><?= ck_datetime::format_direct($entry['event_date'], 'm/d/Y'); ?></td>
										<td style="padding: 5px;" event_type_id="<?= $entry['event_type_id']; ?>" id="event_type_<?= $entry['id']; ?>"><?= $entry['event_type']; ?></td>
										<td style="padding: 5px;" visibility="<?= $entry['visibility']; ?>" id="visibility_<?= $entry['id']; ?>"><?= $visibility_types[$entry['visibility']]; ?></td>
										<td colspan="3" id="log_text_<?= $entry['id']; ?>" style="padding: 5px;"><?= str_replace(array(PHP_EOL, "\r\n", "\r", "\n"), '', nl2br($entry['text'])); ?></td>
										<td style="padding: 5px;"><?= $entry['admin']->get_name(); ?></td>
										<td style="padding: 5px;" nowrap><?= ck_datetime::format_direct($entry['created'], 'm/d/Y H:i'); ?></td>
										<td style="padding: 5px;" nowrap id="last_updated_<?= $entry['id']; ?>"><?= !empty($entry['last_updated'])?ck_datetime::format_direct($entry['last_updated'], 'm/d/Y H:i'):''; ?></td>
										<td style="padding: 5px;">
											<?php if ($admin->id() == $entry['admin_id'] || $admin->is_top_admin()) { ?>
											<a href="javascript:void(0);" onclick="updateLogEntry('<?= $entry['id']; ?>', '<?= $admin->get_name(); ?>');return false;">Update</a>
											|
											<a href="admin_log.php?action=delete&id=<?= $entry['id']; ?>" onclick="return confirm('Are you sure you want to delete this entry?');">Delete</a>
											<?php } ?>
										</td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</td>
			<!-- body_text_eof //-->
		</tr>
	</table>
	<!-- body_eof //-->
</body>
</html>
