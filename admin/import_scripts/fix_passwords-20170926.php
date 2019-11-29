<?php
function upload_data() {
	ini_set('max_execution_time', 0);

	$start = time();

	$time_limit = 600;

	$customer_counter = 0;

	$output = [];

	while (time() - $start <= $time_limit && $accounts = prepared_query::fetch("SELECT customers_id, customers_password FROM customers WHERE customers_password RLIKE ':' AND password_info = 1 LIMIT 300")) {
		foreach ($accounts as $account) {
			if (in_array($account['customers_password'], ['', 'NONE'])) continue;
			$pass_salt = explode(':', $account['customers_password'], 2);
			if (empty($pass_salt[1])) continue;
			prepared_query::execute('UPDATE customers SET customers_password = :password, password_info = 2, legacy_salt = :salt WHERE customers_id = :customers_id', [':password' => ck_customer2::encrypt_password($account['customers_password']), ':salt' => $pass_salt[1], ':customers_id' => $account['customers_id']]);

			$customer_counter++;
		}
	}

	$el_counter = 0;

	while (time() - $start <= $time_limit && $accounts = prepared_query::fetch("SELECT customers_extra_logins_id, customers_password FROM customers_extra_logins WHERE customers_password RLIKE ':' AND password_info = 1 LIMIT 300")) {
		foreach ($accounts as $account) {
			if (in_array($account['customers_password'], ['', 'NONE'])) continue;
			$pass_salt = explode(':', $account['customers_password'], 2);
			if (empty($pass_salt[1])) continue;
			prepared_query::execute('UPDATE customers_extra_logins SET customers_password = :password, password_info = 2, legacy_salt = :salt WHERE customers_extra_logins_id = :customers_extra_logins_id', [':password' => ck_customer2::encrypt_password($account['customers_password']), ':salt' => $pass_salt[1], ':customers_extra_logins_id' => $account['customers_extra_logins_id']]);

			$el_counter++;
		}
	}

	$cleft = prepared_query::fetch('SELECT COUNT(customers_id) FROM customers WHERE password_info = 1', cardinality::SINGLE);
	$elleft = prepared_query::fetch('SELECT COUNT(customers_extra_logins_id) FROM customers_extra_logins WHERE password_info = 1', cardinality::SINGLE);

	$output[] = 'Customers Updated: '.$customer_counter;
	$output[] = 'Customers Left: '.$cleft;
	$output[] = 'Extra Logins Updated: '.$el_counter;
	$output[] = 'Extra Logins Left: '.$elleft;
	$output[] = 'Total Time: '.(time()-$start);

	return ['output' => $output, 'errors' => []];
}
?>