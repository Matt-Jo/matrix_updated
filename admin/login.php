<?php
require('includes/application_top.php');

$login = NULL;

if (isset($_GET['action']) && $_GET['action'] == 'process') {
	$login_status = ck_admin::attempt_login(@$_POST['email_address'], @$_POST['password']);

	$access_key_status = ck_admin::validate_master_password(@$_POST['access_key']);

	if ($login_status['status'] == ck_admin::LOGIN_STATUS_FAIL || !$access_key_status) $login = 'fail';
	elseif ($login_status['status'] == ck_admin::LOGIN_STATUS_PASS) {
		$account = $login_status['account'];

		$admin = new ck_admin($account['account_id']);
		if (!empty($login_status['reencrypt'])) $admin->update_password($login_status['reencrypt']);

		prepared_query::execute('UPDATE admin SET admin_logdate = NOW(), admin_lognum = admin_lognum + 1 WHERE admin_id = :admin_id', [':admin_id' => $admin->id()]);

		$_SESSION['login_id'] = $admin->id();
		$_SESSION['login_groups_id'] = $admin->get('legacy_group_id');
		$_SESSION['login_firstname'] = $admin->get('first_name');;
		$_SESSION['login_email_address'] = $admin->get('email_address');
		// Provide login id to frontend session through cookie
		setcookie('admin_login_id', $admin->id(), 0, '/');

		// regenerate the session id to prevent fixation
        service_locator::get_session_service()->regenerate_id();

		if (!empty($_SESSION['login_target'])) {
			$login_target = $_SESSION['login_target'];
			$_SESSION['login_target'] = NULL;
			unset($_SESSION['login_target']);
			CK\fn::redirect_and_exit($login_target);
		}
		elseif ($_SESSION['login_groups_id'] == '16') CK\fn::redirect_and_exit('/admin/ipn_weight_update.php?selected_box=warehouse'); // Conditioning
		elseif ($_SESSION['login_groups_id'] == '15') CK\fn::redirect_and_exit('/admin/ipn_editor.php?selected_box=inventory'); // Inventory
		else CK\fn::redirect_and_exit('/admin/orders_new.php?selected_box=orders&status=2');
	}
}

require(DIR_WS_LANGUAGES.$_SESSION['language'].'/'.FILENAME_LOGIN); ?>

<!doctype html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title>Matrix</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<style>
		html, body, #login-main { height:100%; }
	</style>
</head>
<body>
	<div id="login-main" class="container">

		<?php if (!empty($login) && $login == 'fail') { ?>
		<div class="row d-flex justify-content-center align-items-center">
			<div class="alert alert-danger mt-4">Incorrect username, password, or access key</div>
		</div>
		<?php } ?>

		<div class="row d-flex justify-content-center align-items-center">
			<div class="card shadow col-4 mt-4">
			<div class="card-body">
				<div class="card-header bg-transparent border-0">
					<img src="//media.cablesandkits.com/pop-the-top4.png" class="mx-auto card-img-top">
				</div>
				<form name="login" action="?action=process" method="post">
					<div class="form-group">
						<label for="email-address">Email</label>
						<input type="text" id="email-address" name="email_address" class="form-control">
					</div>
					<div class="form-group">
						<label for="password">Password</label>
						<input type="password" id="password" name="password" class="form-control">
					</div>
					<div class="form-group">
						<label for="access-key">Access Key</label>
						<input type="password" id="access-key" name="access_key" class="form-control">
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary btn-block">Sign In</button>
					</div>
				</form>
			</div>
		</div>
		</div>
	</div>
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>
