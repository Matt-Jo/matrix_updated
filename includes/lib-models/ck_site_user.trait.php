<?php
trait ck_site_user_trait {

	private static $login_instance;
	private static $account_id_key = [
		'ck_customer2' => [
			//'customers_id',
			'customer_id',
		],
		'ck_admin' => [
			'login_id',
			'admin_login_id',
		]
	];

	private static $context_to_class = [
		self::CONTEXT_CUSTOMER => 'ck_customer2',
		self::CONTEXT_ADMIN => 'ck_admin',
	];

	public static function login_instance($context=NULL) {
		if (!empty($context)) $context_class = self::$context_to_class[$context];
		else $context_class = !empty(self::$site_user_context_class)?self::$site_user_context_class:__CLASS__;

		if (!empty(self::$login_instance)) return self::$login_instance;
		if (is_array(self::$account_id_key[$context_class])) {
			foreach (self::$account_id_key[$context_class] as $aik) {
				if (!empty($_SESSION[$aik])) return self::$login_instance = new self($_SESSION[$aik]);
			}
			return NULL;
		}
		else {
			if (!empty($_SESSION[self::$account_id_key[$context_class]])) return self::$login_instance = new self($_SESSION[self::$account_id_key[$context_class]]);
			else return NULL;
		}
	}

	// for now this just holds static methods, because the actual login details are tied to the customer, or the admin
	// we've made it a trait that can be used by those object types

	//private static $master_pass = 'buV8q#gG';

	public static function attempt_login($email, $password_attempt, $allow_master_password_override=FALSE) {
		$result = ['failed' => 0, 'status' => self::LOGIN_STATUS_NONE, 'account' => NULL, 'reencrypt' => NULL];

		try {
			if (empty($email) || empty($password_attempt)) $result['failed']++;

			$account = self::fetch('login_attempt', [':email' => $email]);

			if (empty($account)) {
				$result['failed']++; // the email address couldn't be found
				$account = ['password' => 'EMPTY HASH', 'password_info' => 0];
			}

			if ($allow_master_password_override && in_array(self::validate_admin_override($password_attempt), [self::PASSWORD_VALIDATE_PASS, self::PASSWORD_VALIDATE_REENCRYPT])) {
				$result['status'] = self::LOGIN_STATUS_PASS;
				$result['account'] = $account;
			}
			else unset($_SESSION['admin_as_user']);

			if (in_array(($validate = self::validate($password_attempt, $account['password'], $account)), [self::PASSWORD_VALIDATE_PASS, self::PASSWORD_VALIDATE_REENCRYPT])) {
				$result['status'] = self::LOGIN_STATUS_PASS;
				$result['account'] = $account;

				if ($validate == self::PASSWORD_VALIDATE_REENCRYPT) $result['reencrypt'] = self::encrypt_password($password_attempt);
			}
		}
		catch (Exception $e) {
			// we may do something with this later, for now just fail gracefully
		}

		if ($result['status'] == self::LOGIN_STATUS_NONE) $result['failed']++;

		if ($result['failed'] > 0) {
			$result['status'] = self::LOGIN_STATUS_FAIL;
			$result['account'] = NULL;
		}

		if (!empty($result['account'])) {
			unset($result['account']['password']);
			unset($result['account']['password_info']);
			unset($result['account']['legacy_salt']);
		}

		return $result;
	}

	public static function validate_master_password($master_password_attempt) {
		return $master_password_attempt == $GLOBALS['ck_keys']->master_password;
	}

	private static function validate_admin_override($password_attempt) {
		try {
			$password_parts = explode('-', $password_attempt);

			$master_attempt = array_pop($password_parts);

			if (empty($password_parts)) return FALSE; // there was no dash, no attempt to use master password
			if (!self::validate_master_password($master_attempt)) return FALSE; // there was a dash, but the portion that would correspond to the master password doesn't match

			$password_attempt = implode('-', $password_parts);

			foreach(self::query_fetch('SELECT admin_id, admin_password as password, password_info, legacy_salt FROM admin WHERE use_master_password = 1', cardinality::SET, []) as $admin) {
				if (self::validate($password_attempt, $admin['password'], $admin) != self::PASSWORD_VALIDATE_FAIL) {
					$_SESSION['admin_as_user'] = TRUE;
					$_SESSION['admin_id'] = $admin['admin_id'];

					return TRUE;
				}
			}
		}
		catch (Exception $e) {
			// we may do something with this later, for now just fail gracefully
		}

		return FALSE;
	}

	private static function validate($password_attempt, $password_hash, $account) {
		// single hash with md5
		if ($account['password_info'] == 1 && self::legacy_validate($password_attempt, $password_hash)) return self::PASSWORD_VALIDATE_REENCRYPT;
		// single hash with bcrypt
		elseif ($account['password_info'] == 0 && password_verify($password_attempt, $password_hash)) return self::PASSWORD_VALIDATE_PASS;
		// didn't match & no salt attempt
		elseif ($account['password_info'] == 2 && !empty($account['legacy_salt'])) {
			// didn't match but there's a salt attempt
			$intermediate = self::legacy_encrypt_password($password_attempt, $account['legacy_salt']);
			// double hash md5/bcrypt
			if (password_verify($intermediate, $password_hash)) return self::PASSWORD_VALIDATE_REENCRYPT;
			// didn't match
			else return self::PASSWORD_VALIDATE_FAIL;
		}
		else return self::PASSWORD_VALIDATE_FAIL;
	}

	// this *should* be a constant-time check, but as it's legacy/deprecated and we're a low-priority target for the type of attack that's relevant for, we'll leave it
	private static function legacy_validate($password_attempt, $password_hash) {
		$pass_salt = explode(':', $password_hash, 2);
		if (count($pass_salt) != 2) return FALSE;

		// blech, md5, we'll figure out an upgrade path post-haste [4/14/16]
		if (md5($pass_salt[1].$password_attempt) != $pass_salt[0]) return FALSE;

		return TRUE;
	}

	public static function encrypt_password($plaintext) {
		return password_hash($plaintext, PASSWORD_DEFAULT);
		/*$salt = self::random_bytes(2);
		$password = md5($salt.$plaintext).':'.$salt;
		return $password;*/
	}

	private static function legacy_encrypt_password($plaintext, $salt) {
		$password = md5($salt.$plaintext).':'.$salt;
		return $password;
	}

	// copied from http://php.net/manual/en/function.openssl-random-pseudo-bytes.php
	// this is a re-implementation of openssl_random_pseudo_bytes, also from that page under our version of PHP (5.5.9)
	// the included implementation is *not* cryptographically strong, fixed in 5.5.28
	public static function random_bytes($length) {
		$length_n = (int) $length; // shell injection is no fun
		$handle = popen("/usr/bin/openssl rand -base64 $length_n", "r");
		$data = stream_get_contents($handle);
		pclose($handle);
		return $data;
	}

	// using this to generate a code for resetting a password
	public static function generate_code($opts=[]) {
		$password = '';

		$defaults = [
			'length' => 8,
			'charsets' => ['qwertyuiopasdfghjklzxcvbnm', 'QWERTYUIOPASDFGHJKLZXCVBNM', '1234567890', '!@#$%^&*()_+-=`~[]{}\\|;\':",./<>?'],
			'charset_weights' => [.3, .25, .3, .15],
			'common_denom' => 20 // common denom of weights 3/10, 1/4, 3/10, 3/20
		];

		foreach ($defaults as $key => $val) {
			if (empty($opts[$key])) $opts[$key] = $val;
		}

		$selections = [];

		if (function_exists('openssl_random_pseudo_bytes')) {
			for ($i=0; $i<$opts['length']+2; $i++) {
				// bindec wasn't working
				// the byte length requested just needs to give us enough fidelity to uniquely choose values out of our character sets
				// it'll be reduced because we're picking a real min/max randomly within this range
				// it's possible to randomly get a short range with 40 bits of randomness, but it's *highly* unlikely
				$selections[] = hexdec(bin2hex(openssl_random_pseudo_bytes(5)));
			}

			$stats = $selections;

			sort($stats);

			$min = array_shift($stats);
			$max = array_pop($stats);
			$range = $max - $min;

			$minfound = $maxfound = FALSE;

			foreach ($selections as $selection) {
				if (!$minfound && $selection == $min) { $minfound = TRUE; continue; }
				if (!$maxfound && $selection == $max) { $maxfound = TRUE; continue; }
				$num = $selection - $min;
				$charsetwt = $num / $range;

				$chosen_charset = 0;
				$weight = $opts['charset_weights'][$chosen_charset];

				while ($charsetwt > $weight) $weight += $opts['charset_weights'][++$chosen_charset]; // too clever?

				$internal_range = $range * $opts['charset_weights'][$chosen_charset];
				$internal_max = $range * $weight;
				$internal_min = $internal_max - $internal_range;

				$internal_num = $num - $internal_min;
				$charwt = $internal_num / $internal_range;

				$password .= $opts['charsets'][$chosen_charset][(int)round($charwt * (strlen($opts['charsets'][$chosen_charset])-1))];
			}
		}
		else {
			// this is not secure, but it's our best option - this isn't intended to create long term codes
			list($usec, $sec) = explode(' ', microtime());
			mt_srand($sec + $usec * 1000000);

			for ($i=0; $i<$opts['length']; $i++) {
				$charsetwt = mt_rand(0, $opts['common_denom']);

				$chosen_charset = 0;
				$weight = $opts['charset_weights'][$chosen_charset] * $opts['common_denom'];

				while ($charsetwt > $weight) $weight += ($opts['charset_weights'][++$chosen_charset] * $opts['common_denom']);

				$password .= $opts['charsets'][$chosen_charset][mt_rand(0, strlen($opts['charsets'][$chosen_charset])-1)];
			}
		}

		return $password;
	}
}
?>
