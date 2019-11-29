<?php
require('includes/application_top.php');

$current_boxes = DIR_FS_ADMIN.DIR_WS_BOXES;

if (!empty($_GET['action'])) {
	switch ($_GET['action']) {
		case 'check_password':
			$admin = new ck_admin($_SESSION['login_id']);

			if (!$admin->revalidate_login($_POST['password_confirmation'])) CK\fn::redirect_and_exit('/admin/admin_account.php?action=check_account&error=password');
			else {
				$_SESSION['confirm_account'] = 'confirm_account';
				CK\fn::redirect_and_exit('/admin/admin_account.php?action=edit_process');
			}
			break;
		case 'save_account':
			$admin = new ck_admin($_SESSION['login_id']);

			$stored_email[] = 'NONE';
			$hiddenPassword = '-hidden-';

			$check_email = prepared_query::fetch('SELECT admin_email_address FROM admin WHERE admin_id != :admin_id', cardinality::COLUMN, [':admin_id' => $admin->id()]);
			$stored_email = array_merge($stored_email, $check_email);

			if (in_array($_POST['admin_email_address'], $stored_email)) {
				CK\fn::redirect_and_exit('/admin/admin_account.php?action=edit_process&error=email');
			} 
			else {
				prepared_query::execute('UPDATE admin SET admin_firstname = :fname, admin_lastname = :lname, admin_email_address = :email, admin_modified = NOW() WHERE admin_id = :admin_id', [':fname' => $_POST['admin_firstname'], ':lname' => $_POST['admin_lastname'], ':email' => $_POST['admin_email_address'], ':admin_id' => $admin->id()]);

				$admin->update_password($_POST['admin_password']);

                $mailer = service_locator::get_mail_service();
                $mail = $mailer->create_mail()
                    ->set_subject(ADMIN_EMAIL_SUBJECT)
                    ->set_from(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER)
                    ->add_to($_POST['admin_email_address'], $_POST['admin_firstname'].' '.$_POST['admin_lastname'])
                    ->set_body(null,sprintf(ADMIN_EMAIL_TEXT, $_POST['admin_firstname'], HTTP_SERVER.DIR_WS_ADMIN, $_POST['admin_email_address'], $hiddenPassword, STORE_OWNER))
                ;
                $mailer->send($mail);

				CK\fn::redirect_and_exit('/admin/admin_account.php?page='.$_GET['page'].'&mID='.$admin->id());
			}
			break;
	}
} ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET; ?>">
	<title><?= TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	<script language="javascript" src="includes/menu.js"></script>
	<script language="javascript" src="includes/general.js"></script>
	<?php require('includes/account_check.js.php'); ?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
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
			<?php 
			if (!empty($_GET['action'])) {
				if ($_GET['action'] == 'edit_process') echo tep_draw_form('account', FILENAME_ADMIN_ACCOUNT, 'action=save_account', 'post', 'enctype="multipart/form-data"');
				else if ($_GET['action'] == 'check_account') echo tep_draw_form('account', FILENAME_ADMIN_ACCOUNT, 'action=check_password', 'post', 'enctype="multipart/form-data"');
			}
			else echo tep_draw_form('account', FILENAME_ADMIN_ACCOUNT, 'action=check_account', 'post', 'enctype="multipart/form-data"'); ?>
			<table border="0" width="100%" cellspacing="0" cellpadding="2">
			<tr>
				<td width="100%">
					<table border="0" width="100%" cellspacing="0" cellpadding="0">
						<tr>
							<td class="pageHeading"><?= HEADING_TITLE; ?></td>
							<td class="pageHeading" align="right"><?= tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table border="0" width="100%" cellspacing="0" cellpadding="0" align="center">
				<tr>
					<td valign="top">
						<?php $myAccount = prepared_query::fetch('SELECT a.admin_id, a.admin_firstname, a.admin_lastname, a.admin_email_address, a.admin_created, a.admin_modified, a.admin_logdate, a.admin_lognum, ag.admin_groups_name FROM admin a, admin_groups ag WHERE a.admin_id= :login_id AND ag.admin_groups_id= :login_groups_id', cardinality::ROW, [':login_id' => $_SESSION['login_id'], ':login_groups_id' => $_SESSION['login_groups_id']]); ?>
						<table border="0" width="100%" cellspacing="0" cellpadding="2" align="center">
							<tr class="dataTableHeadingRow">
								<td class="dataTableHeadingContent"><?= TABLE_HEADING_ACCOUNT; ?></td>
							</tr>
							<tr class="dataTableRow">
								<td>
									<table border="0" cellspacing="0" cellpadding="3">
							<?php if (!empty($_GET['action'])) {
								if (($_GET['action'] == 'edit_process') && (!empty($_SESSION['confirm_account'])) ) { ?>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_FIRSTNAME; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= tep_draw_input_field('admin_firstname', $myAccount['admin_firstname']); ?></td>
								</tr>
								<tr>
								<td class="dataTableContent"><?= TEXT_INFO_LASTNAME; ?>&nbsp;&nbsp;&nbsp;</td>
								<td class="dataTableContent"><?= tep_draw_input_field('admin_lastname', $myAccount['admin_lastname']); ?></td>
								</tr>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_EMAIL; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent">
										<?php if (!empty($_GET['error'])) echo tep_draw_input_field('admin_email_address', $myAccount['admin_email_address']).' '.TEXT_INFO_ERROR.'';
										else echo tep_draw_input_field('admin_email_address', $myAccount['admin_email_address']); ?>
									</td>
								</tr>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_PASSWORD; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= tep_draw_password_field('admin_password'); ?></td>
								</tr>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_PASSWORD_CONFIRM; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= tep_draw_password_field('admin_password_confirm'); ?></td>
								</tr>
								<?php }
								}
								else {
									if (!empty($_SESSION['confirm_account'])) unset($_SESSION['confirm_account']); ?>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_FULLNAME; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= $myAccount['admin_firstname'].' '.$myAccount['admin_lastname']; ?></td>
								</tr>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_EMAIL; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= $myAccount['admin_email_address']; ?></td>
								</tr>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_PASSWORD; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= TEXT_INFO_PASSWORD_HIDDEN; ?></td>
								</tr>
								<tr class="dataTableRowSelected">
									<td class="dataTableContent"><?= TEXT_INFO_GROUP; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= $myAccount['admin_groups_name']; ?></td>
								</tr>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_CREATED; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= $myAccount['admin_created']; ?></td>
								</tr>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_LOGNUM; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= $myAccount['admin_lognum']; ?></td>
								</tr>
								<tr>
									<td class="dataTableContent"><?= TEXT_INFO_LOGDATE; ?>&nbsp;&nbsp;&nbsp;</td>
									<td class="dataTableContent"><?= $myAccount['admin_logdate']; ?></td>
								</tr>
							<?php } ?>
						</table>
					</td>
				</tr>
				<tr>
					<td>
						<table width="100%" border="0" cellspacing="0" cellpadding="3">
							<tr>
								<td class="smallText" valign="top"><?= TEXT_INFO_MODIFIED.$myAccount['admin_modified']; ?></td>
								<td align="right">
									<?php
									if (!empty($_GET['action'])) {
										if ($_GET['action'] == 'edit_process') { 
											echo '<a href="/admin/admin_account.php">'.tep_image_button('button_back.gif', IMAGE_BACK).'</a> '; 
											if (!empty($_SESSION['confirm_account'])) echo tep_image_submit('button_save.gif', IMAGE_SAVE, 'onClick="validateForm();return document.returnValue"');
										}
										else if ($_GET['action'] == 'check_account') echo '&nbsp;'; 
									}
									else echo tep_image_submit('button_edit.gif', IMAGE_EDIT); ?>
								</td>
							<tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<?php
		$heading = array();
		$contents = array();
		if (!empty($_GET['action'])) {
			switch ($_GET['action']) {
				case 'edit_process':
					$heading[] = array('text' => '<b>&nbsp;'.TEXT_INFO_HEADING_DEFAULT.'</b>');

					$contents[] = array('text' => TEXT_INFO_INTRO_EDIT_PROCESS.tep_draw_hidden_field('id_info', $myAccount['admin_id']));
					//$contents[] = array('align' => 'center', 'text' => '<a href="/admin_account.php">'.tep_image_button('button_back.gif', IMAGE_BACK).'</a> '.tep_image_submit('button_confirm.gif', IMAGE_CONFIRM, 'onClick="validateForm();return document.returnValue"').'<br>&nbsp');
					break;
				case 'check_account':
					$heading[] = array('text' => '<b>&nbsp;'.TEXT_INFO_HEADING_CONFIRM_PASSWORD.'</b>');

					$contents[] = array('text' => '&nbsp;'.TEXT_INFO_INTRO_CONFIRM_PASSWORD.tep_draw_hidden_field('id_info', $myAccount['admin_id']));
					if (!empty($_GET['error'])) {
						$contents[] = array('text' => '&nbsp;'.TEXT_INFO_INTRO_CONFIRM_PASSWORD_ERROR);
					}
					$contents[] = array('align' => 'center', 'text' => tep_draw_password_field('password_confirmation'));

					$contents[] = array('align' => 'center', 'text' => '<a href="/admin/admin_account.php">'.tep_image_button('button_back.gif', IMAGE_BACK).'</a> '.tep_image_submit('button_confirm.gif', IMAGE_CONFIRM).'<br>&nbsp');
					break;
				default:
					$heading[] = array('text' => '<b>&nbsp;'.TEXT_INFO_HEADING_DEFAULT.'</b>');

					$contents[] = array('text' => TEXT_INFO_INTRO_DEFAULT);
					//$contents[] = array('align' => 'center', 'text' => tep_image_submit('button_edit.gif', IMAGE_EDIT).'<br>&nbsp');
					if ($myAccount['admin_email_address'] == 'admin@localhost') $contents[] = array('text' => sprintf(TEXT_INFO_INTRO_DEFAULT_FIRST, $myAccount['admin_firstname']).'<br>&nbsp');
					else if (empty($myAccount['admin_modified']) || $myAccount['admin_logdate'] <= 1) $contents[] = array('text' => sprintf(TEXT_INFO_INTRO_DEFAULT_FIRST_TIME, $myAccount['admin_firstname']).'<br>&nbsp');
			}
		}

		if ((tep_not_null($heading)) && (tep_not_null($contents))) {
			echo '<td width="25%" valign="top">'."\n";
			$box = new box;
			echo $box->infoBox($heading, $contents);
			echo '</td>'."\n";
		 } ?>
		</tr>
	</table>
</td>
</tr>
</table>
</form>
</td>
<!-- body_text_eof //-->
</tr>
</table>
<!-- body_eof //-->
</body>
</html>
