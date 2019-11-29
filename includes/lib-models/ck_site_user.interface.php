<?php
interface ck_site_user_interface {
	const LOGIN_STATUS_NONE = 'LOGIN_NONE'; // not passed yet
	const LOGIN_STATUS_PASS = 'LOGIN_PASS'; // passed
	const LOGIN_STATUS_FAIL = 'LOGIN_FAIL'; // failed

	const PASSWORD_VALIDATE_PASS = 'PASSWORD_PASS'; // passed
	const PASSWORD_VALIDATE_REENCRYPT = 'PASSWORD_REENCRYPT'; // passed, but needs reencryption
	const PASSWORD_VALIDATE_FAIL = 'PASSWORD_FAIL'; // failed

	const CONTEXT_CUSTOMER = 'CONTEXT_CUSTOMER'; // front end/customer
	const CONTEXT_ADMIN = 'CONTEXT_ADMIN'; // back end/admin

	public function update_password($password, $account_id=NULL);

	public function revalidate_login($password);
}
?>
