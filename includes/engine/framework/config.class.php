<?php
class config {

private $path = NULL;
private $files = array();
private $data = array();
private $data_namespace = NULL;

public function __construct($path) {
	$this->path = $path;
}

public function &__get($key) {
	if (!empty($this->data_namespace) && isset($this->data[$this->data_namespace]) && $key != $this->data_namespace) {
		// use the namespaced value if it exists, otherwise fall back to the main value
		if (is_object($this->data[$this->data_namespace]) && isset($this->data[$this->data_namespace]->$key)) return $this->data[$this->data_namespace]->$key;
		elseif (is_array($this->data[$this->data_namespace]) && isset($this->data[$this->data_namespace][$key])) return $this->data[$this->data_namespace][$key];
		else return @$this->data[$key];
	}
	else return @$this->data[$key];
}
public function __set($key, $val) {
	// if we're setting, we always have to specify the namespace in our reference if we want to use it
	return $this->data[$key] = $val;
}
public function __isset($key) {
	return isset($this->data[$key]);
}
public function __unset($key) {
	unset($this->data[$key]);
}

public function add_config($file=NULL) {
	if (!$file) $file = $this->path.'/config.json';
	else $file = realpath($file);
	$details = pathinfo($file);
	$newfile = $file;
	if (empty($details['extension'])) $newfile .= isset($details['extension'])?'json':'.json';
	if (empty($details['dirname'])) $newfile = $this->path.'/'.$newfile;
	if ($file != $newfile) {
		$file = $newfile;
		$details = pathinfo($file);
	}
	if (!is_file($file)) return FALSE;

	$config_key = $details['filename'];

	$json = file_get_contents($file);
	if (is_null(($config = @json_decode($json)))) {
		// not valid json
		return FALSE;
	}

	if (isset($this->$config_key)) {
		// this value has already been set, we probably don't want to overwrite it
		// think some more about this
		return FALSE;
	}
	$this->files[$config_key] = $file;

	if ($config_key == __CLASS__) {
		// this is the "base" or "default" config file
		foreach ($config as $key => $val) {
			$this->$key = $val;
		}
	}
	else {
		$this->$config_key = $config;
	}

	return TRUE;
}

public function set_namespace($namespace) {
	// the namespace is usually going to be the subdomain
	$this->data_namespace = $namespace;
}

public function define_file($file) {
	$file = (string) $file;
	if (!$file) return FALSE;

	$details = pathinfo($file);
	$newfile = $file;
	if (empty($details['extension'])) $newfile .= isset($details['extension'])?'json':'.json';
	if (empty($details['dirname'])) $newfile = $this->path.'/'.$newfile;
	if ($file != $newfile) {
		$file = $newfile;
		$details = pathinfo($file);
	}

	$config_key = $details['filename'];

	if (!isset($this->files[$config_key])) $this->files[$config_key] = $file;
	return TRUE;
}

public function write($file=NULL) {
	if (!$file) $file = $this->path.'/config.json';
	else $file = realpath($file);
	$details = pathinfo($file);
	$config_key = $details['filename'];

	if (!in_array($config_key, array_keys($this->files))) {
		// we never added this config
		return FALSE;
	}

	$file = $this->files[$config_key];

	if (!is_writable($file)) return FALSE;

	// only some of these encode options are available in PHP 5.3.x, but we should be able to use them without harm
	if ($config_key == __CLASS__) {
		$data = array();
		// grab all of the config data that doesn't belong to other files
		foreach ($this->data as $key => $val) {
			// we might optimize this to take the array_keys function out of the loop, but since the write method should only run infrequently and only related to a direct
			// admin action, we probably don't need to worry about it
			if (in_array($key, array_keys($this->files))) continue;
			$data[$key] = $val;
		}
		$json = json_encode($data, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_BIGINT_AS_STRING);
	}
	else {
		$json = json_encode($this->$config_key, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_BIGINT_AS_STRING);
	}

	return is_numeric(file_put_contents($file, $json));
}

}
?>