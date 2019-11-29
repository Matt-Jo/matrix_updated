<?php
require('includes/application_top.php');

class_exists('ck_site_user'); // just initialize the class, setting the master password

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'change':
			$old_password = $ck_keys->master_password;
			$new_password = substr(ck_site_user::random_bytes(10), 0, 8);
			$stub_mail = TRUE;
			break;
		case 'send-message':
			prepared_query::execute("UPDATE configuration SET configuration_value = ? WHERE configuration_key = 'MASTER_PASS'", $ck_keys->master_password);

            $mailer = service_locator::get_mail_service();
            
            $mail = $mailer->create_mail();

			$mail->set_body(null,$_REQUEST['message']);

			$mail->set_from(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER);
			$mail->set_subject('CK New Master Password');
			$mail->add_to($_REQUEST['email']);

            $mailer->send($mail);
            break;
		default:
			break;
	}
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<td width="<?php echo BOX_WIDTH; ?>" valign="top">
					<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td width="100%" valign="top">
					<table border="0" width="800" cellspacing="0" cellpadding="2">
						<tr>
							<td>
								<?php if (!empty($errors)) {
									if ($errors) {
										echo "<br>ERRORS:<br>";
										echo implode("<br>", $errors);
									}
								} ?>
								<style>
								</style>
								<div style="border:1px solid #000; overflow:auto;">
									<form action="/admin/master-password.php" method="post">
										<input type="hidden" name="action" value="change">
										Current: <?= $ck_keys->master_password; ?>
										<input type="submit" value="Change">
									</form>
									<?php if (!empty($stub_mail)) { ?>
									<br>
									<form action="/admin/master-password.php" method="get">
										<input type="hidden" name="global_action" value="updatekeys">
										<input type="hidden" name="action" value="send-message">
										<input type="hidden" name="site_keys[master_password]" value="<?= $new_password; ?>">
										To: <input type="text" name="email" value="<?= $_SESSION['login_email_address']; ?>"><br>
										<textarea name="message" cols="70" rows="12" wrap="hard"><?= "Hey Everybody,\n\nThe new master password is:\n\n".$new_password."\n\nPlease update any systems that use the ".$old_password." to use this new password.\n\nThanks,\nYour Intrepid CK Technical Support Collective"; ?></textarea><br>
										<input type="submit" value="Send">
									</form>
									<?php } ?>
								</div>
								<script>
								</script>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>
