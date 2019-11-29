<?php
require('includes/application_top.php');

if (!empty($_REQUEST['action'])) {
	if (is_array($_REQUEST['action'])) {
		foreach ($_REQUEST['action'] as $pretty_url_id => $action) {
			// all we care about is that the $pretty_url_id and $action variables get populated
			break;
		}
	}
	else {
		$action = $_REQUEST['action'];
	}

	$result = (object) array('status' => 1, 'message' => '');

	if ($action == 'ADD') {
		// $pretty_url_id == 'new'
		$pretty_url = ltrim(parse_url($_REQUEST['pretty_url']['new'], PHP_URL_PATH), '/');
		$target_url_details = parse_url($_REQUEST['target_url']['new']);
		$target_url = ltrim($target_url_details['path'], '/');
		if (!empty($target_url_details['query'])) $target_url .= '?'.$target_url_details['query'];
		if (!empty($target_url_details['fragment'])) $target_url .= '#'.$target_url_details['fragment'];

		if (($pretty_url_id = prepared_query::fetch('SELECT pretty_url_id FROM ck_pretty_url WHERE pretty_url LIKE ?', cardinality::SINGLE, array($pretty_url)))) {
			$result->status = 0;
			$result->message = "The given URL [$pretty_url] has already been created. Please update the existing entry, or choose a new one.";
		}
		else {
			prepared_query::execute('INSERT INTO ck_pretty_url (pretty_url, target_url, touch_date) VALUES (?, ?, NOW())', array($pretty_url, $target_url));
		}
	}
	elseif ($action == 'UPDATE') {
		$active = $_REQUEST['active'][$pretty_url_id];

		$pretty_url = ltrim(parse_url($_REQUEST['pretty_url'][$pretty_url_id], PHP_URL_PATH), '/');
		$target_url_details = parse_url($_REQUEST['target_url'][$pretty_url_id]);
		$target_url = ltrim($target_url_details['path'], '/');
		if (!empty($target_url_details['query'])) $target_url .= '?'.$target_url_details['query'];
		if (!empty($target_url_details['fragment'])) $target_url .= '#'.$target_url_details['fragment'];

		if (($found = prepared_query::fetch('SELECT pretty_url_id FROM ck_pretty_url WHERE pretty_url LIKE ? AND pretty_url_id != ?', cardinality::SINGLE, array($pretty_url, $pretty_url_id)))) {
			$result->status = 0;
			$result->message = "The given URL [$pretty_url] has already been created. Please update the existing entry, or choose a new one.";
		}
		elseif ($active < 0 || empty($_REQUEST['pretty_url'][$pretty_url_id])) {
			// delete the URL
			prepared_query::execute('DELETE FROM ck_pretty_url WHERE pretty_url_id = ?', array($pretty_url_id));
		}
		else {
			// make whatever changes may have been done, and activate or deactivate
			prepared_query::execute('UPDATE ck_pretty_url SET pretty_url = ?, target_url = ?, touch_date = NOW(), active = ? WHERE pretty_url_id = ?', array($pretty_url, $target_url, $active, $pretty_url_id));
		}
	}
	elseif ($action == 'UPDATE ALL') {
		foreach ($_REQUEST['init'] as $idx => $pretty_url_id) {
			$active = $_REQUEST['active'][$pretty_url_id];

			$pretty_url = ltrim(parse_url($_REQUEST['pretty_url'][$pretty_url_id], PHP_URL_PATH), '/');
			$target_url_details = parse_url($_REQUEST['target_url'][$pretty_url_id]);
			$target_url = ltrim($target_url_details['path'], '/');
			if (!empty($target_url_details['query'])) $target_url .= '?'.$target_url_details['query'];
			if (!empty($target_url_details['fragment'])) $target_url .= '#'.$target_url_details['fragment'];

			if (($found = prepared_query::fetch('SELECT pretty_url_id FROM ck_pretty_url WHERE pretty_url LIKE ? AND pretty_url_id != ?', cardinality::SINGLE, array($pretty_url, $pretty_url_id)))) {
				$result->status = 0;
				$result->message = "The given URL [$pretty_url] has already been created. Please update the existing entry, or choose a new one.";
			}
			elseif ($active < 0 || empty($_REQUEST['pretty_url'][$pretty_url_id])) {
				// delete the URL
				prepared_query::execute('DELETE FROM ck_pretty_url WHERE pretty_url_id = ?', array($pretty_url_id));
			}
			else {
				// make whatever changes may have been done, and activate or deactivate
				prepared_query::execute('UPDATE ck_pretty_url SET pretty_url = ?, target_url = ?, touch_date = NOW(), active = ? WHERE pretty_url_id = ?', array($pretty_url, $target_url, $active, $pretty_url_id));
			}
		}
	}

	echo json_encode($result);
	exit();
}

$urls = prepared_query::fetch('SELECT * FROM ck_pretty_url ORDER BY target_url, pretty_url DESC', cardinality::SET);

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
						#url-manager td, #url-manager th { border-style:solid; border-color:#000; border-width:0px 1px 1px 0px; padding:5px 8px; }
						#url-manager tr:first-child td, #url-manager tr:first-child th { border-top-width:1px; }
						#url-manager td:first-child, #url-manager th:first-child { border-left-width:1px; }
						#url-manager .row-0 td { background-color:#dedede; }
						#url-manager .row-1 td { background-color:#ababab; }
						/*#url-manager .active-dow td { background-color:#cfc; }*/
					</style>
					<form style="display:inline;" id="url_control" action="/admin/pretty_url_manager.php" method="post">
						<p style="color:#c00;">
							You can copy/paste the entire URL into the "Target URL" field, including the domain, or just the unique key.
						</p>
						<input type="submit" name="action" value="UPDATE ALL">
						<table id="url-manager" cellpadding="0" cellspacing="0">
							<thead>
								<tr>
									<th>Pretty URL</th>
									<th>Target URL</th>
									<th>Date Managed</th>
									<th>Active</th>
									<th>Control</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><input type="text" name="pretty_url[new]"></td>
									<td><input type="text" name="target_url[new]" style="width:325px;"></td>
									<td></td>
									<td></td>
									<td class="manage_url"><input type="submit" name="action[new]" value="ADD"></td>
								</tr>
								<?php foreach ($urls as $idx => $url) { ?>
								<tr class="row-<?php echo $idx%2; ?>">
									<td><input type="hidden" name="init[]" value="<?= $url['pretty_url_id']; ?>" class="url_sets"><input type="text" name="pretty_url[<?= $url['pretty_url_id']; ?>]" value="<?= $url['pretty_url']; ?>"></td>
									<td><input type="text" name="target_url[<?= $url['pretty_url_id']; ?>]" value="<?= $url['target_url']; ?>" style="width:325px;"></td>
									<td><?php $dt = new DateTime($url['touch_date']); echo $dt->format('m/d/Y'); ?></td>
									<td>
										<select size="1" name="active[<?= $url['pretty_url_id']; ?>]">
											<option value="0" <?php echo !$url['active']?'selected':''; ?>>Inactive</option>
											<option value="1" <?php echo $url['active']?'selected':''; ?>>Active</option>
											<option value="-1">DELETE</option>
										</select>
									</td>
									<td class="manage_url"><input type="submit" name="action[<?= $url['pretty_url_id']; ?>]" value="UPDATE"></td>
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
						jQuery('#url_control').submit(function(e) {
							e.preventDefault();
							jQuery('.manage_url').css('background-color', '#ffc;');
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
									jQuery('.manage_url').css('background-color', '#fcc;'); // error
								}
							};
							var halt = false;
							if (submit_val == 'UPDATE ALL') {
								// we're updating all currently existing dow entries
								jQuery('.url_sets').each(function() {
									var pretty_url_id = jQuery(this).val();
									if (jQuery('input[name="pretty_url['+pretty_url_id+']"]').val() == '' || jQuery('select[name="active['+pretty_url_id+']"]').val() == -1) {
										if (!confirm('Are you sure you want to delete the URL Alias '+jQuery('input[name="pretty_url['+pretty_url_id+']"]').val()+'?')) {
											halt = true;
										}
									}
								});
								if (!halt) jQuery.ajax(send);
							}
							else if (submit_val == 'UPDATE') {
								var pretty_url_id = submit_key.replace(/^action\[(\d+)\]$/, "$1");
								if (jQuery('input[name="pretty_url['+pretty_url_id+']"]').val() == '' || jQuery('select[name="active['+pretty_url_id+']"]').val() == -1) {
									if (!confirm('Are you sure you want to delete the URL Alias '+jQuery('input[name="pretty_url['+pretty_url_id+']"]').val()+'?')) {
										halt = true;
									}
								}
								if (!halt) jQuery.ajax(send);
							}
							else if (jQuery('input[name="pretty_url[new]"]').val() == '' || jQuery('input[name="target_url[new]"]').val() == '') {
								halt = true;
								alert('You have attempted to add a new URL alias missing one or both of the Pretty URL or the Target URL. Please try again.');
							}
							else {
								// we're adding a new one and we've got all of the information we need
								jQuery.ajax(send);
							}
							return false;
						});
					</script>
				</td>
			</tr>
		</table>
	</body>
</html>
