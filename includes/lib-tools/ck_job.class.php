<?php
class ck_job extends ck_master_archetype {
	const STATUS_STOPPED = 0;
	const STATUS_BLOCKING = 1;
	const STATUS_NON_BLOCKING = 2;

	public static function register($job_key) {
		prepared_query::execute('INSERT INTO ck_job_registry (job_key) VALUES (:job_key) ON DUPLICATE KEY UPDATE job_key=job_key', [':job_key' => $job_key]);
	}

	public static function start($job_key) {
		if (self::is_blocking($job_key)) throw new CKJobException($job_key.' is already running; cannot start.');

		self::register($job_key);

		prepared_query::execute('UPDATE ck_job_registry SET status = :blocking WHERE job_key = :job_key', [':blocking' => self::STATUS_BLOCKING, ':job_key' => $job_key]);
	}

	public static function stop($job_key) {
		prepared_query::execute('UPDATE ck_job_registry SET status = :stopped WHERE job_key = :job_key', [':stopped' => self::STATUS_STOPPED, ':job_key' => $job_key]);
	}

	public static function is_blocking($job_key) {
		return !empty(prepared_query::fetch('SELECT * FROM ck_job_registry WHERE job_key = :job_key AND status = :blocking', cardinality::SINGLE, [':job_key' => $job_key, ':blocking' => self::STATUS_BLOCKING]));
	}

	public static function is_running($job_key) {
		return !empty(prepared_query::fetch('SELECT * FROM ck_job_registry WHERE job_key = :job_key AND status != :stopped', cardinality::SINGLE, [':job_key' => $job_key, ':stopped' => self::STATUS_STOPPED]));
	}

	public static function get_job_status($job_key) {
		return prepared_query::fetch('SELECT status FROM ck_job_registry WHERE job_key = :job_key', cardinality::SINGLE, [':job_key' => $job_key]);
	}
}

class CKJobException extends Exception {
}
?>
