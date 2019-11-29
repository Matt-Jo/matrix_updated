<?php
trait ck_legacy_api {

	private $legacy_properties = [];

	private static $legacy_api_map = [
		'method' => [],
		'method_lookup' => [],
		'property' => []
	];

	private function map_legacy_method($legacy, $replacement, $process=NULL) {
		if (!method_exists(static::class, $replacement)) throw new CKLegacyException('Method ['.$replacement.'] could not be found in '.static::class.' to replace ['.$legacy.']');

		if (empty($process)) $process = function($arguments) { return $arguments; };

		self::$legacy_api_map['method_lookup'][$legacy] = $replacement;
		self::$legacy_api_map['method'][$legacy] = function($context, $arguments) use ($replacement, $process) {
			if ($context == 'object') return call_user_func_array([$this, $replacement], $process($arguments));
			else return call_user_func_array([static::class, $replacement], $process($arguments));
		};
	}

	private function map_legacy_property($legacy, $replacement) {
		if (!property_exists(static::class, $replacement)) throw new CKLegacyException('Property ['.$replacement.'] could not be found in '.static::class.' to replace ['.$legacy.']');

		self::$legacy_api_map['property'][$legacy] = $replacement;
	}

	// handle legacy properties and method calls with __get, __set and __call
	public function &__get($key) {
		$params = [':called_class' => static::class, ':call_key' => $key];
		if (!empty(self::$legacy_api_map['property'][$key])) {
			$err = 'Class ['.static::class.'] property ['.$key.'] is deprecated; please use ['.self::$legacy_api_map['property'][$key].'] instead.';
			$errtype = E_USER_NOTICE;
			$params[':mapped'] = 1;

			$val =& $this->{self::$legacy_api_map['property'][$key]};
		}
		else {
			$err = 'Class ['.static::class.'] property ['.$key.'] is no longer supported.';
			$errtype = E_USER_WARNING;
			$params[':mapped'] = 0;

			if (!empty($this->legacy_properties[$key])) $val =& $this->legacy_properties[$key];
			else $val = NULL;
		}

		trigger_error($err, $errtype);

		prepared_query::execute('INSERT INTO ck_legacy_notice_queue (called_class, call_type, call_key, mapped) VALUES (:called_class, \'getprop\', :call_key, :mapped)', $params);

		return $val;
	}

	public function __set($key, $val) {
		$params = [':called_class' => static::class, ':call_key' => $key, ':call_args' => @((string) $val)];
		if (!empty(self::$legacy_api_map['property'][$key])) {
			$err = 'Class ['.static::class.'] property ['.$key.'] is deprecated; please use ['.self::$legacy_api_map['property'][$key].'] instead.';
			$errtype = E_USER_NOTICE;
			$params[':mapped'] = 1;

			$this->{self::$legacy_api_map['property'][$key]} = $val;
		}
		else {
			$err = 'Class ['.static::class.'] property ['.$key.'] is no longer supported.';
			$errtype = E_USER_WARNING;
			$params[':mapped'] = 0;

			$this->legacy_properties[$key] = $val;
		}

		trigger_error($err, $errtype);

		prepared_query::execute('INSERT INTO ck_legacy_notice_queue (called_class, call_type, call_key, call_args, mapped) VALUES (:called_class, \'setprop\', :call_key, :call_cargs, :mapped)', $params);

		return $val;
	}

	private static function compress_args($args) {
		if (empty($args)) return NULL;
		$keys = array_keys($args);
		$result = [];
		foreach ($keys as $key) {
			$result[] = $key.': '.@((string) $args[$key]);
		}
		return implode('; ', $result);
	}

	public function __call($method, $args) {
		$params = [':called_class' => static::class, ':call_key' => $method, ':call_args' => self::compress_args($args)];
		if (!empty(self::$legacy_api_map['method'][$method])) {
			$err = 'Class ['.static::class.'] method ['.$method.'] is deprecated; please use ['.self::$legacy_api_map['method_lookup'][$method].'] instead.';
			$errtype = E_USER_NOTICE;
			$params[':mapped'] = 1;

			$mthd = self::$legacy_api_map['method'][$method];

			$result = $mthd('object', $args);
		}
		else {
			$err = 'Class ['.static::class.'] method ['.$method.'] is no longer supported.';
			$errtype = E_USER_WARNING;
			$params[':mapped'] = 0;

			$result = NULL;
		}

		trigger_error($err, $errtype);

		// need to include the calling file

		self::query_execute("INSERT INTO ck_legacy_notice_queue (called_class, call_type, call_key, call_args, mapped) VALUES (:called_class, 'objmethod', :call_key, :call_args, :mapped)", cardinality::NONE, $params);

		return $result;
	}

	public static function __callStatic($method, $args) {
		$params = [':called_class' => static::class, ':call_key' => $method, ':call_args' => self::compress_args($args)];
		if (!empty(self::$legacy_api_map['method'][$method])) {
			$err = 'Class ['.static::class.'] method ['.$method.'] is deprecated; please use ['.self::$legacy_api_map['method_lookup'][$method].'] instead.';
			$errtype = E_USER_NOTICE;
			$params[':mapped'] = 1;

			$mthd = self::$legacy_api_map['method'][$method];

			$result = $mthd('static', $args);
		}
		else {
			$err = 'Class ['.static::class.'] method ['.$method.'] is no longer supported.';
			$errtype = E_USER_WARNING;
			$params[':mapped'] = 0;

			$result = NULL;
		}

		trigger_error($err, $errtype);

		prepared_query::execute('INSERT INTO ck_legacy_notice_queue (called_class, call_type, call_key, call_args, mapped) VALUES (:called_class, \'statmethod\', :call_key, :call_cargs, :mapped)', $params);

		return $result;
	}

	public static function send_errors() {
		prepared_query::execute('DELETE FROM ck_legacy_notice_queue WHERE exported = 1');

		if ($queue = prepared_query::fetch('SELECT * FROM ck_legacy_notice_queue WHERE exported = 0', cardinality::SET)) {
			$mail->set_from(ADMIN_NOTICE_EMAIL);
			$mail->set_subject($subject);
			$mail->add_to(ADMIN_NOTICE_EMAIL);

			$body = [];
			$body[] = 'The following legacy notices were registered:';
			$body[] = '---------------------------------------------';

			foreach ($queue as $notice) {
				$timestamp = new DateTime($notice['call_timestamp']);
				$body[] = 'Called Class: '.$notice['called_class'].' | Call Type: '.$notice['call_type'].' | Call Args: '.$notice['call_args'].' | Mapped: '.$notice['mapped'].' | Timestamp: '.$timestamp->format('Y-m-d H:i:s');

				prepared_query::execute('UPDATE ck_legacy_notice_queue SET exported = 1 WHERE legacy_notice_queue_id = :id', [':id' => $notice['legacy_notice_queue_id']]);
			}

			$mail->set_body(implode('<br>', $body));

            $mail->send();
        }
	}
}

class CKLegacyException extends CkMasterArchetypeException {
	public function __construct($message='', $code=0, $previous=NULL) {
		parent::__construct('['.$this->get_calling_class().'] '.$message, $code, $previous);
	}
}
?>
