<?php
class ck_session extends ck_singleton {

	// nothing much to do, at least not yet when we're not taking over the whole session functionality
	protected function init($parameters=[]) {
	}

	private static $switch_session_holder;

	public static function switch_session($session_string=NULL) {
		if (!empty($session_string)) {
			self::$switch_session_holder = session_encode();

			foreach ($_SESSION as $key => $value) unset($_SESSION[$key]);

			session_decode($session_string);
		}
		else session_decode(self::$switch_session_holder);
	}

	// based on function from http://php.net/manual/en/function.session-decode.php
	public static function decode_php_session_string($session_string) {
		$current_session = session_encode();

		foreach ($_SESSION as $key => $value) unset($_SESSION[$key]);

		session_decode($session_string);
		$restored_session = $_SESSION;

		foreach ($_SESSION as $key => $value) unset($_SESSION[$key]);

		session_decode($current_session);

		return $restored_session;
	}

	public static function get_admin_user_login($admin_session) {
		self::switch_session($admin_session);

		$customer_id = $_SESSION['customer_id'];
		$customer_default_address_id = $_SESSION['customer_default_address_id'];
		$customer_first_name = $_SESSION['customer_first_name'];
		$customer_last_name = $_SESSION['customer_last_name'];
		$customer_country_id = $_SESSION['customer_country_id'];
		$customer_zone_id = $_SESSION['customer_zone_id'];
		$customer_is_dealer = $_SESSION['customer_is_dealer'];
		$customer_extra_login_id = $_SESSION['customer_extra_login_id'];
		$admin_as_user = $_SESSION['admin_as_user'];
		$admin_id = $_SESSION['admin_id'];

		unset($_SESSION['set_admin_as_user']);

		try {
			self::query_execute('UPDATE sessions SET value = :value WHERE sesskey = :sesskey', cardinality::NONE, [':value' => session_encode(), ':sesskey' => $_COOKIE['osCAdminID2']]);
		}
		catch (Exception $e) {
			// don't need to do anything, just need to double make sure we don't fail so we can switch back to the correct session
		}

		self::switch_session();

		$_SESSION['customer_id'] = $customer_id;
		$_SESSION['customer_default_address_id'] = $customer_default_address_id;
		$_SESSION['customer_first_name'] = $customer_first_name;
		$_SESSION['customer_last_name'] = $customer_last_name;
		$_SESSION['customer_country_id'] = $customer_country_id;
		$_SESSION['customer_zone_id'] = $customer_zone_id;
		$_SESSION['customer_is_dealer'] = $customer_is_dealer;
		$_SESSION['customer_extra_login_id'] = $customer_extra_login_id;
		$_SESSION['admin_as_user'] = $admin_as_user;
		$_SESSION['admin_id'] = $admin_id;
	}

	public static function assign_admin_user_login($admin_session) {
		if (!empty($_SESSION['customer_id'])) {
			$customer_id = $_SESSION['customer_id'];
			$customer_extra_login_id = $_SESSION['customer_extra_login_id'];
		}

		self::switch_session($admin_session);

		if (!empty($customer_id)) {
			$_SESSION['customer_id'] = $customer_id;
			$_SESSION['customer_extra_login_id'] = $customer_extra_login_id;
		}
		else {
			unset($_SESSION['customer_id']);
			unset($_SESSION['customer_extra_login_id']);
			unset($_SESSION['cart']);
		}

		try {
			self::query_execute('UPDATE sessions SET value = :value WHERE sesskey = :sesskey', cardinality::NONE, [':value' => session_encode(), ':sesskey' => $_COOKIE['osCAdminID2']]);
		}
		catch (Exception $e) {
			// don't need to do anything, just need to double make sure we don't fail so we can switch back to the correct session
		}

		self::switch_session();
	}
}
?>
