<?php
require_once(__DIR__.'/request.class.php');
class export_message {

	private $service_uri;

	public function __construct($service_uri=NULL) {
		$this->service_uri = $service_uri;
		if ($this->service_uri) $this->request = new request();
	}

	public function send($msg, $objects=array()) {
		$result = $this->request->post($this->service_uri, $msg);
		if ($this->context(self::DEBUG)) $this->request->debug(TRUE);
		$status = 0;
		if (!empty($result)) {
			$result = json_decode($result);
			if ($result->status == 0 && $this->context(self::DEV)) {
				// this is likely a data issue of some sort, but maybe a programming error, either way we'll need to handle this gracefully
				$this->debug('failed status');
				echo '<pre>';
				print_r($result);
				echo '</pre>';
			}
			else {
				if ($this->context(self::DEBUG)) {
					$this->debug('success!');
					echo '<pre>';
					print_r($result);
					print_r($objects);
					echo '</pre>';
				}
				if (!empty($objects)) $status = $this->record_message($result->msg_id, $objects);
			}
		}
		else {
			if ($this->context(self::DEV)) {
				// this is an issue with our requester class, or the service isn't available
				$this->debug('failed post');
			}
		}

		return $status;
	}

	private function record_message($message_id, $objects) {
		try {
			foreach ($objects as $object) {
				prepared_query::execute('INSERT INTO ck_export_message_log (object, object_id, message_id) VALUES (?, ?, ?)', array($object['object'], $object['object_id'], $message_id));
			}
		}
		catch (Exception $e) {
			return 0;
		}
		return 1;
	}

	public function confirm_message($message_id) {
		try {
			prepared_query::execute('UPDATE ck_export_message_log SET confirmed_at = NOW() WHERE message_id = ?', array($message_id));
		}
		catch (Exception $e) {
		}
	}

	//--------------------------------------------------------

	// this is more appropriate in a general debug/info class, but we'll put it here for now
	private $debug_ctr = 0;
	private function debug($txt=NULL) {
		echo "<br>\n--------------------<br>\n".'DEBUG ['.$txt.']: '.($this->debug_ctr++)."<br>\n--------------------<br>\n";
	}

	//--------------------------------------------------------

	// this sort of thing would be better as a parent class or mix in, but we'll put it here for now
	const CONTEXT = 14;
	const PRODUCTION = 1;
	const DEV = 2;
	const DEBUG = 4;
	//const VERBOSE = 8; // this currently has no meaning/is not used

	// we might also switch this up to allow matching on higher numbers even if the specific number isn't a component
	public function context($level) {
		return $level&self::CONTEXT?TRUE:FALSE;
	}

	//--------------------------------------------------------

	private $data = array();
	public function &__get($key) {
		if (isset($this->data[$key])) return $this->data[$key];
		else return NULL;
	}
	public function __set($key, $val) {
		return $this->data[$key] = $val;
	}
	public function __isset($key) {
		return isset($this->data[$key]);
	}
	public function __unset($key) {
		unset($this->data[$key]);
	}

	private static $db = NULL;
	public static function set_db($db) {
		self::$db = $db;
	}
	// this allows us to use dependancy injection without requiring it
	private static function get_db($db=NULL) {
        return $db ?? self::$db ?? service_locator::get_db_service() ?? NULL;
	}
}
?>