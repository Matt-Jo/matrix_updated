<?php
require('includes/application_top.php');

if ($__FLAG['ajax']) {
	$result = [];

	switch ($_GET['action']) {
		case 'load-admin':
			$result = ['status' => NULL, 'details' => []];

			$admin = new ck_admin($_GET['admin_id']);

			$result['status'] = $admin->is('active')?1:0;

			$result['details']['Name'] = $admin->get_name();
			$result['details']['Email Address'] = $admin->get_header('email_address');
			$result['details']['Group Level'] = $admin->get_header('legacy_group');
			$result['details']['Account Created'] = $admin->get_header('date_created')->format('Y-m-d');
			$result['details']['Last Access'] = $admin->get_header('last_login_date')->format('Y-m-d');
			break;
		case 'reset-password':
			$admin = new ck_admin($_GET['admin_id']);
			$admin->legacy_reset_password();
			$result = ['status' => 1, 'message' => 'New Password Sent'];
			break;
		case 'begin-edit':
			$admin = new ck_admin($_GET['admin_id']);
			$header = $admin->get_header();
			$perms = $admin->get_legacy_permissions();

			$result = ['status' => 1, 'details' => [], 'perms' => []];

			$result['details']['admin_groups_id'] = $header['legacy_group_id'];
			$result['details']['admin_firstname'] = $header['first_name'];
			$result['details']['admin_lastname'] = $header['last_name'];
			$result['details']['admin_email_address'] = $header['email_address'];

			$result['details']['broker'] = $admin->is('broker')?1:0;
			$result['details']['account_manager'] = $admin->is('account_manager')?1:0;

			foreach ($perms as $perm => $status) {
				$result['perms'][$perm] = $status?1:0;
			}

			break;
		case 'deactivate-admin':
			$admin = new ck_admin($_GET['admin_id']);
			$admin->deactivate();
			$result = ['status' => 1, 'message' => 'Admin Deactivated'];
			break;
		case 'reactivate-admin':
			$admin = new ck_admin($_GET['admin_id']);
			$admin->reactivate();
			$result = ['status' => 1, 'message' => 'Admin Reactivated & New Password Sent'];
			break;
		case 'soft-disable':
			$admin = new ck_admin($_GET['admin_id']);
			$admin->soft_disable();
			$result = ['status' => 1, 'message' => 'Password Disabled'];
			break;
	}

	echo json_encode($result);

	exit();
}

if (!empty($_POST['action']) && $_POST['action'] == 'finish-edit') {
	if (!empty($_POST['admin_id'])) {
		// edit existing admin
		$admin = new ck_admin($_POST['admin_id']);

		if (prepared_query::fetch('SELECT admin_id FROM admin WHERE admin_email_address LIKE :admin_email_address AND admin_id != :admin_id', cardinality::SINGLE, [':admin_email_address' => $_POST['admin_email_address'], ':admin_id' => $admin->id()])) {
			$errors = ['Email Address Conflicts.'];
		}
		else {
			$update = new prepared_fields([
				'admin_firstname' => $_POST['admin_firstname'],
				'admin_lastname' => $_POST['admin_lastname'],
				'admin_email_address' => $_POST['admin_email_address'],
				'admin_groups_id' => $_POST['admin_groups_id'],
				'admin_modified' => prepared_expression::NOW(),
				'broker' => $__FLAG['broker']?1:0,
				'account_manager' => $__FLAG['account_manager']?1:0,
			], prepared_fields::UPDATE_QUERY);

			$id = new prepared_fields(['admin_id' => $admin->id()]);

			prepared_query::execute('UPDATE admin SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
		}
	}
	else {
		// create new admin

		if (prepared_query::fetch('SELECT admin_id FROM admin WHERE admin_email_address LIKE :admin_email_address', cardinality::SINGLE, [':admin_email_address' => $_POST['admin_email_address']])) {
			$errors = ['Email Address Conflicts.'];
		}
		else {
			$insert = new prepared_fields([
				'admin_firstname' => $_POST['admin_firstname'],
				'admin_lastname' => $_POST['admin_lastname'],
				'admin_email_address' => $_POST['admin_email_address'],
				'admin_groups_id' => $_POST['admin_groups_id'],
				'admin_created' => prepared_expression::NOW(),
				'admin_modified' => prepared_expression::NOW(),
				'broker' => $__FLAG['broker']?1:0,
				'account_manager' => $__FLAG['account_manager']?1:0,
				'status' => 1,
			], prepared_fields::INSERT_QUERY);

			$admin_id = prepared_query::insert('INSERT INTO admin ('.$insert->insert_fields().') VALUES ('.$insert->insert_values().')', $insert->parameters());

			$admin = new ck_admin($admin_id);

			$admin->legacy_reset_password(TRUE);
		}
	}

	if (empty($errors)) {
		if (!empty($_POST['copy_flags_from_admin_id'])) {
			prepared_query::execute('UPDATE admin a, admin a0 SET a.broker = a.broker | a0.broker, a.account_manager = a.account_manager | a0.account_manager WHERE a.admin_id = :admin_id AND a0.admin_id = :source_admin_id', [':admin_id' => $admin_id, ':source_admin_id' => $_POST['copy_flags_from_admin_id']]);

			$fields = implode(', ', array_map(function($f) { return 'a.'.$f.' = a.'.$f.' | a0.'.$f; }, array_keys($admin->get_legacy_permissions())));
			prepared_query::execute('UPDATE admin a, admin a0 SET '.$fields.' WHERE a.admin_id = :admin_id AND a0.admin_id = :source_admin_id', [':admin_id' => $admin_id, ':source_admin_id' => $_POST['copy_flags_from_admin_id']]);
		}
		else {
			foreach ($admin->get_legacy_permissions() as $perm => $val) {
				if ($__FLAG[$perm]) $admin->set_legacy_permission($perm, TRUE);
				else $admin->set_legacy_permission($perm, FALSE);
			}
		}

		if (!empty($_POST['copy_files_from_admin_id'])) {
			// add the group of the current user to any of the files permissioned on the group of the source user
			// yeah, it's a bit strange, and probably too greedy
			prepared_query::execute("UPDATE admin_files af JOIN admin a ON af.admin_groups_id RLIKE CONCAT('(^|,)', a.admin_groups_id, '(,|$)'), admin a1 SET af.admin_groups_id = CONCAT_WS(',', af.admin_groups_id, a1.admin_groups_id) WHERE a.admin_id = :source_admin_id AND a1.admin_id = :admin_id", [':admin_id' => $admin_id, ':source_admin_id' => $_POST['copy_files_from_admin_id']]);
		}

		CK\fn::redirect_and_exit('/admin/admin_members.php?selected_box=administrator');
	}
} ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<script src="/images/static/js/ck-styleset.js"></script>
	<script src="/images/static/js/ck-ajaxify.max.js"></script>
	<script src="/images/static/js/ck-button-links.max.js"></script>
	<?php require('includes/account_check.js.php'); ?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onLoad="SetFocus();">
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
				</table>
			</td>
			<!-- body_text //-->
			<td id="page-body">
				<style>
					h3 { margin:3px 0px 6px 0px; color:#727272; }
					h4 { margin:10px 0px 10px 0px; color:#727272; padding:3px 3px 3px 10px; border-top:1px solid #727272; border-bottom:1px solid #727272; background-color:#337272; color:#fff; }
					input[required], select[required], textarea[required] { border:1px solid #f55; }
					input::placeholder { color:#aaa; }
					#page-body *[title] { text-decoration:underline dashed; }
					#page-body *[title]::after { content:" [?]"; cursor:pointer; }
					button[disabled] { color:#c99; }

					.errors { background-color:#fcc; padding:8px; }

					#list-block { display:flex; flex-direction:row; flex-wrap:nowrap; justify-content:center; align-items:flex-start; align-content:flex-start; }

					#admins { border-collapse:collapse; font-size:12px; order:1; flex-grow:3; flex-basis:75%; }
					#admins th, #admins td { padding:4px 22px 4px 8px; }
					#admins th { background-color:#337272; font-size:16px; color:#fff; text-align:left; }
					#admins td { background-color:#dee4e8; border-bottom:1px solid #bbb; cursor:pointer; }
					#admins tr:hover td { background-color:#ffc; }
					#admins tr.current td { background-color:#99d8d8; }
					#admins tfoot td { background-color:#fff; text-align:right; border-bottom-width:0px; }
					#admins .indicator strong { display:none; }
					#admins .current .indicator strong { display:inline; }

					#admin-details { background-color:#dee4e8; border-width:0px 1px 1px 1px; border-style:solid; border-color:#bbb; box-sizing:border-box; order:2; flex-grow:1; flex-basis:25%; }
					#admin-details h4 { margin:0px 0px 8px 0px; }
					#admin-details .detail-control { text-align:center; display:none; }
					#admin-details .detail-interface { margin:10px; font-size:12px; }
					#admin-details .detail-update { margin:10px; font-size:12px; display:none; }
					#admin-details .detail-header { font-size:14px; border-bottom:1px solid #bbb; margin:5px 0px; }
					#admin-details .detail-field { margin-bottom:7px; }
					#admin-details .detail-field input[type=text], #admin-details .detail-field select { width:100%; box-sizing:border-box; font-size:12px; padding:3px 5px; }
				</style>

				<h3>Admin Members</h3>

				<?php if (!empty($errors)) { ?>
				<div class="errors">
					<?= implode('<br>', $errors); ?>
				</div>
				<?php } ?>
				
				<a href="/admin/admin_members.php?selected_box=administrator&all_admins=<?= $__FLAG['all_admins']?0:1; ?>"><?= $__FLAG['all_admins']?'Show Active Only':'Show All'; ?></a><br>
				<div id="list-block">
					<table border="0" cellspacing="0" cellpadding="0" id="admins">
						<thead>
							<tr>
								<th>ID</th>
								<th>Name</th>
								<th>Email</th>
								<th>Group</th>
								<th># Logins</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php $admins = ck_admin::get_all_admins(function($a, $b) { return strcmp($a->get_normalized_name(), $b->get_normalized_name()); });
							foreach ($admins as $admin) {
								if (!$__FLAG['all_admins'] && !$admin->is('active')) continue; ?>
							<tr class="admin" data-admin-id="<?= $admin->id(); ?>">
								<td><?= $admin->id(); ?></td>
								<td><?= $admin->get_normalized_name(); ?></td>
								<td><?= $admin->get_header('email_address'); ?></td>
								<td><?= $admin->get_header('legacy_group'); ?></td>
								<td><?= $admin->get_header('login_counter'); ?></td>
								<td class="indicator"><strong>&#10148;</strong></td>
							</tr>
							<?php } ?>
						</tbody>
						<tfoot>
							<tr>
								<td colspan="6">
									<a href="#" class="control-button" data-action="new-admin"><button type="button">New Member</button></a>
								</td>
							</tr>
						</tfoot>
					</table>

					<div id="admin-details">
						<h4>Admin Details</h4>

						<div class="detail-control view">
							<a href="#" class="control-button confirm" data-action="reset-password" data-admin-id=""><button type="button">Reset Pass</button></a>
							<a href="#" class="control-button" data-action="begin-edit" data-admin-id=""><button type="button">Edit</button></a>
							<a href="#" class="control-button only-active confirm" data-action="deactivate-admin" data-admin-id=""><button type="button">De-Activate</button></a>
							<a href="#" class="control-button only-inactive confirm" data-action="reactivate-admin" data-admin-id=""><button type="button">Re-Activate</button></a>
							<a href="#" class="control-button confirm" data-action="soft-disable" data-admin-id=""><button type="button">Soft Disable</button></a>
						</div>
						<form action="/admin/admin_members.php" method="post" id="admin-form">
							<input type="hidden" name="action" value="finish-edit">
							<input type="hidden" name="admin_id" id="admin_id" value="">
							<div class="detail-control edit">
								<button type="submit">Submit</button>
								<a href="#" class="control-button" data-action="discard" data-admin-id=""><button type="button">Discard</button></a>
							</div>
							<div class="detail-interface">
								Select Admin
							</div>
							<div class="detail-update">
								<div class="detail-field">
									<strong>First Name:</strong><br>
									<input type="text" name="admin_firstname" id="admin_firstname">
								</div>
								<div class="detail-field">
									<strong>Last Name:</strong><br>
									<input type="text" name="admin_lastname" id="admin_lastname">
								</div>
								<div class="detail-field">
									<strong>Email:</strong><br>
									<input type="text" name="admin_email_address" id="admin_email_address">
								</div>
								<div class="detail-field">
									<strong>Group:</strong><br>
									<select name="admin_groups_id" id="admin_groups_id">
										<option value="">Select</option>
										<?php foreach (ck_admin::get_legacy_groups() as $group) { ?>
										<option value="<?= $group['admin_groups_id']; ?>"><?= $group['admin_groups_name']; ?></option>
										<?php } ?>
									</select>
								</div>

								<div class="detail-field">
									<strong>Copy File Permissions From:</strong><br>
									<select name="copy_files_from_admin_id" id="copy_files_from_admin_id">
										<option value="">None</option>
										<?php foreach ($admins as $admin) { ?>
										<option value="<?= $admin->id(); ?>"><?= $admin->get_name(); ?></option>
										<?php } ?>
									</select>
								</div>

								<div class="detail-field">
									<strong>Copy Permissions &amp; Reporting Flags From:</strong><br>
									<select name="copy_flags_from_admin_id" id="copy_flags_from_admin_id">
										<option value="">Set Manually</option>
										<?php foreach ($admins as $admin) { ?>
										<option value="<?= $admin->id(); ?>"><?= $admin->get_name(); ?></option>
										<?php } ?>
									</select>
									<script>
										jQuery('#copy_flags_from_admin_id').change(function() {
											if (jQuery(this).val() == '') jQuery('#flags').show();
											else jQuery('#flags').hide();
										});
									</script>
								</div>

								<div id="flags">
									<div class="detail-header">Reporting</div>
									<div class="detail-field">
										<input type="checkbox" name="broker" id="broker"> Broker
									</div>
									<div class="detail-field">
										<input type="checkbox" name="account_manager" id="account_manager"> Account Manager
									</div>

									<div class="detail-header">Sales Permissions</div>
									<div class="detail-field">
										<input type="checkbox" name="use_master_password" id="use_master_password"> Use Master Password
									</div>

									<div class="detail-header">Accounting Permissions</div>
									<div class="detail-field">
										<input type="checkbox" name="update_net_terms" id="update_net_terms"> Update Net Terms
									</div>
									<div class="detail-field">
										<input type="checkbox" name="update_pay_tax" id="update_pay_tax"> Update Pay Tax
									</div>

									<div class="detail-header">IPN Permissions</div>
									<div class="detail-field">
										<input type="checkbox" name="ipn_reviewer" id="ipn_reviewer"> Review IPNs
									</div>
									<div class="detail-field">
										<input type="checkbox" name="update_ipn_quantity" id="update_ipn_quantity"> Update Qty
									</div>
									<div class="detail-field">
										<input type="checkbox" name="update_ipn_weight" id="update_ipn_weight"> Update Weight
									</div>
									<div class="detail-field">
										<input type="checkbox" name="rename_ipn" id="rename_ipn"> Rename IPN
									</div>
									<div class="detail-field">
										<input type="checkbox" name="update_ipn_average_cost" id="update_ipn_average_cost"> Update Avg Cost
									</div>
									<div class="detail-field">
										<input type="checkbox" name="upload_images" id="upload_images"> Upload Images
									</div>
									<div class="detail-field">
										<input type="checkbox" name="update_target_min_qty" id="update_target_min_qty"> Update Min Qty
									</div>
									<div class="detail-field">
										<input type="checkbox" name="update_target_max_qty" id="update_target_max_qty"> Update Max Qty
									</div>
									<div class="detail-field">
										<input type="checkbox" name="update_serial" id="update_serial"> Update Vendor Info
									</div>
									<div class="detail-field">
										<input type="checkbox" name="mark_as_reviewed" id="mark_as_reviewed"> Mark as Reviewed
									</div>
									<div class="detail-field">
										<input type="checkbox" name="change_ipn_category" id="change_ipn_category"> Change Category
									</div>
									<div class="detail-field">
										<input type="checkbox" name="change_warranties" id="change_warranties"> Change Warranty
									</div>
									<div class="detail-field">
										<input type="checkbox" name="change_dealer_warranties" id="change_dealer_warranties"> Change Dealer Warranty
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</td>
		</tr>
	</table>
</body>
<script>
	ck.button_links();

	let admin_groups = <?= json_encode(ck_admin::get_legacy_groups()); ?>;

	jQuery('.control-button').click(function(e) {
		e.preventDefault();

		if (jQuery(this).hasClass('confirm')) {
			if (!confirm('Confirm that you wish to '+jQuery(this).find('button').html()+' this account.')) return;
		}

		let admin_id = jQuery(this).attr('data-admin-id');
		let action = jQuery(this).attr('data-action');

		jQuery.ajax({
			url: '/admin/admin_members.php',
			data: { ajax: 1, action: action, admin_id: admin_id },
			dataType: 'json',
			success: function(data) {
				handlers[action](data);
			}
		});
	});

	let handlers = {
		'new-admin': function(data) {
			jQuery('.current').removeClass('current');

			jQuery('.detail-interface').hide();
			jQuery('#admin-form').trigger('reset');
			jQuery('.detail-update').show();
			jQuery('.detail-control.view').hide();
			jQuery('.detail-control.edit').show();

			jQuery('#admin_id').val('');
		},
		'reset-password': function(data) {
			alert(data.message);
		},
		'begin-edit': function(data) {
			jQuery('#admin-form').trigger('reset');

			jQuery('.detail-interface').hide();
			jQuery('.detail-update').show();
			jQuery('.detail-control.view').hide();
			jQuery('.detail-control.edit').show();

			jQuery('#admin_firstname').val(data.details.admin_firstname);
			jQuery('#admin_lastname').val(data.details.admin_lastname);
			jQuery('#admin_email_address').val(data.details.admin_email_address);
			jQuery('#admin_groups_id').val(data.details.admin_groups_id);

			if (data.details.broker == 1) jQuery('#broker').attr('checked', true);
			if (data.details.account_manager == 1) jQuery('#account_manager').attr('checked', true);

			for (let k in data.perms) {
				if (data.perms[k] == 1) jQuery('#'+k).attr('checked', true);
			}
		},
		'discard': function(data) {
			jQuery('.detail-interface').show();
			jQuery('.detail-update').hide();
			jQuery('.detail-control.view').show();
			jQuery('.detail-control.edit').hide();

			jQuery('#admin-form').trigger('reset');
		},
		'deactivate-admin': function(data) {
			jQuery('.detail-control .only-active').hide();
			jQuery('.detail-control .only-inactive').show();
			alert(data.message);
		},
		'reactivate-admin': function(data) {
			jQuery('.detail-control .only-active').show();
			jQuery('.detail-control .only-inactive').hide();
			alert(data.message);
		},
		'soft-disable': function(data) {
			alert(data.message);
		},
	};

	jQuery('.admin').click(function() {
		let admin_id = jQuery(this).attr('data-admin-id');

		jQuery('.current').removeClass('current');
		jQuery(this).addClass('current');

		jQuery.ajax({
			url: '/admin/admin_members.php',
			data: { ajax: 1, action: 'load-admin', admin_id: admin_id },
			dataType: 'json',
			success: function(data) {
				jQuery('.detail-interface').show().html('');
				jQuery('.detail-update').hide();
				for (let k in data.details) {
					jQuery('.detail-interface').append(jQuery('<div class="detail-field"><strong>'+k+'</strong><br>'+data.details[k]+'</div>'));
				}
				jQuery('#admin_id').val(admin_id);
				jQuery('.detail-control a').attr('data-admin-id', admin_id);
				if (data.status == 1) {
					jQuery('.detail-control .only-active').show();
					jQuery('.detail-control .only-inactive').hide();
				}
				else {
					jQuery('.detail-control .only-active').hide();
					jQuery('.detail-control .only-inactive').show();
				}
				jQuery('.detail-control.view').show();
				jQuery('.detail-control.edit').hide();

				document.getElementById('admin-details').scrollIntoView();
			},
		});
	});
</script>
</html>
