<?php
$skip_check = TRUE;
require('includes/application_top.php');

if (!empty($_POST)) {
	$response = ['status' => ck_admin::LOGIN_STATUS_NONE, 'user' => [], 'group' => []];

	$login = ck_admin::attempt_login(@$_POST['username'], @$_POST['password']);

	$access_key_status = ck_admin::validate_master_password(@$_POST['access_key']);

	if ($login['status'] == ck_admin::LOGIN_STATUS_FAIL) $response['status'] = ck_admin::LOGIN_STATUS_FAIL;
	elseif ($login['status'] == ck_admin::LOGIN_STATUS_PASS) {
		$account = $login['account'];

		$admin = new ck_admin($account['account_id']);
		if (!empty($login['reencrypt'])) $admin->update_password($login['reencrypt']);

		prepared_query::execute('UPDATE admin SET admin_logdate = NOW(), admin_lognum = admin_lognum + 1 WHERE admin_id = :admin_id', [':admin_id' => $admin->id()]);

		$response['status'] = ck_admin::LOGIN_STATUS_PASS;
		$response['user']['user_id'] = $admin->id();
		$response['user']['name'] = $admin->get_name();
		$response['user']['email'] = $admin->get('email_address');
		$response['group']['legacy_group_id'] = $admin->get('legacy_group_id');
		$response['group']['legacy_group'] = $admin->get('legacy_group');
	}

	echo json_encode($response);
	exit();
}
?>
<form action="/admin/service-login.php" method="post">
	<input type="text" name="username">
	<input type="password" name="password">
	<input type="password" name="access_key">
	<button type="submit">Submit</button>
</form>
