<?php
require_once(__DIR__.'/../../includes/application_top.php');

$record_date = new DateTime('2004-09-01'); // the first order placed in the system was during this month - I haven't seen any records prior to this

$one_day = new DateInterval('P1D');

$last_date = prepared_query::fetch('SELECT MAX(calendar_date) FROM ck_reporting_calendar', cardinality::SINGLE);

if (!empty($last_date)) {
	$last_date = new DateTime($last_date);
	$record_date = $last_date->add($one_day);
}

$end_date = new DateTime('last day of december next year');

$savepoint_id = prepared_query::transaction_begin();

try {
	while ($record_date <= $end_date) {
		prepared_query::execute('INSERT INTO ck_reporting_calendar (calendar_date, weekend) VALUES (:record_date, :weekend)', [':record_date' => $record_date->format('Y-m-d'), ':weekend' => $record_date->format('N')>=6?1:0]);
		$record_date->add($one_day);
	}
}
catch (Exception $e) {
	prepared_query::fail_transaction();
	throw $e;
}
finally {
	prepared_query::transaction_end(NULL, $savepoint_id);
}
?>