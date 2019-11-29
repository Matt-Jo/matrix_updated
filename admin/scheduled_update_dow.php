<?php
require_once(__DIR__.'/../includes/application_top.php');

error_reporting(E_ALL);

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

try {
	//dow::reset_old_images();

	// if we find a new dow, set it and unset the old one, otherwise leave it
	$new = dow::get_new_dow();
	echo 'dow test';
	if (!empty($new)) {
		//dow::set_dow_images($new);

		dow::switch_active($new);
		echo 'new dow!';
	}
	else echo 'no new dow';
}
catch (Exception $e) {
	echo $e->getMessage();
	// we should make some sort of notification to someone who cares here
}

// we'll piggy back banner schedules on the DOW scheduler, down here so that if it fails for whatever reason it won't hold up the DOW change
// do any new banners go live today?

/*try {
	prepared_query::transaction_begin();
	if ($new_banners = prepared_query::fetch('SELECT * FROM ck_banners WHERE go_live = ? AND active = 0', cardinality::SET, date('Y-m-d'))) {
		prepared_query::execute('UPDATE ck_banners SET active = 0 WHERE active = 1');
		prepared_query::execute('UPDATE ck_banners SET active = 1 WHERE go_live = ?', date('Y-m-d'));
	}
	prepared_query::transaction_commit();
}
catch (Exception $e) {
	prepared_query::transaction_rollback();
}*/
?>
