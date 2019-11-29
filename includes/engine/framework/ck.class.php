<?php
class ck {

// path to this file
private $app_path = NULL;
public $subdomain = NULL;

private $update_config = FALSE;

// public properties
public $config = NULL;
// private properties
private $data = array();
private $paths = array('FSPATH' => NULL, 'URLPATH' => NULL, 'ip' => NULL); // the fully qualified paths and include paths
// protected properties

// static properties
private static $force_www = TRUE; // if we don't have a subdomain, assume www

// constants

// constructor
public function __construct() {
	$this->app_path = __DIR__;

	// import main config file
	require_once($this->app_path.'/config.class.php');
	$this->config = new config(realpath($this->app_path.'/../config'));
	$this->config->add_config(/* The default config */);

	$this->register_subdomain();
	$this->config->set_namespace($this->subdomain);

	$this->register_paths();

	// manage include paths
	$ip = get_include_path();
	$this->ip = realpath($this->FSPATH.'/'.$this->config->paths->sub_folder.'/'.$this->config->paths->app_folder.'/framework').':'.realpath($this->FSPATH.'/'.$this->config->paths->sub_folder.'/'.$this->config->paths->app_folder.'/model').':'.realpath($this->FSPATH.'/'.$this->config->paths->sub_folder.'/'.$this->config->paths->app_folder.'/logic');
	set_include_path($ip.':'.$this->ip);

	// register the autoload function
	spl_autoload_register(array($this, 'load_resource'));
	require_once($this->FSPATH.'/'.$this->config->paths->sub_folder.'/'.$this->config->paths->app_folder.'/vendor/autoload.php');

	$this->set_globals();
}

public function __destruct() {
	if ($this->update_config) $this->config->write();
}

public function &__get($key) {
	if (in_array(strtoupper($key), array_keys($this->paths))) return $this->paths[strtoupper($key)];
	else return @$this->data[$key];
}
public function __set($key, $val) {
	// we're not allowed to overwrite the paths using this magic method
	if (in_array(strtoupper($key), array_keys($this->paths))) trigger_error('You are not allowed to re-define '.$key, E_USER_NOTICE);
	else return $this->data[$key] = $val;
}
public function __isset($key) {
	if (in_array(strtoupper($key), array_keys($this->paths))) return !empty($this->paths[strtoupper($key)]);
	else return isset($this->data[$key]);
}
public function __unset($key) {
	if (in_array(strtoupper($key), array_keys($this->paths))) trigger_error('You are not allowed to unset '.$key, E_USER_NOTICE);
	else unset($this->data[$key]);
}

public function load_resource($class) {
	// really we should be caching this info outside of the config file, but for now this will suffice
	if (!empty($this->config->cache->autoload->$class)) {
		if (file_exists($this->config->cache->autoload->$class)) {
			require_once($this->config->cache->autoload->$class);
			return TRUE;
		}
		else {
			unset($this->config->cache->autoload->$class);
			$this->update_config = TRUE;
		}
	}

	$paths = explode(':', $this->ip);
	foreach ($paths as $path) {
		if (file_exists($path.'/'.$class.'.class.php')) {
			$this->config->cache->autoload->$class = $path.'/'.$class.'.class.php';
			require_once($this->config->cache->autoload->$class);
			$this->update_config = TRUE;
			return TRUE;
		}
		// file_exists checks for directories too
		elseif (file_exists($path.'/'.$class) && file_exists($path.'/'.$class.'/'.$class.'.class.php')) {
			$this->config->cache->autoload->$class = $path.'/'.$class.'/'.$class.'.class.php';
			require_once($this->config->cache->autoload->$class);
			$this->update_config = TRUE;
			return TRUE;
		}
	}
	// we're gonna get an exception here, unless it's handled by another autoloader
	return FALSE;
}

// the registered subdomain is really to allow us to namespace our config file
private function register_subdomain() {
	$host = $_SERVER['HTTP_HOST'];
	if ($this->config->base_domain) $this->subdomain = preg_replace('/\.'.$this->config->base_domain.'$/i', '', $host);
	else {
		$parts = explode('.', $host);
		// we're going to assume a single level tld, like .com, rather than a multi level tld, like .co.uk
		array_pop($parts);
		array_pop($parts);
		$this->subdomain = implode('.', $parts);
	}
	if (self::$force_www && !$this->subdomain) $this->subdomain = 'www';
}

private function register_paths() {
	if ($this->config->paths->FSPATH) $this->paths['FSPATH'] = $this->config->paths->FSPATH;
	else {
		$folders = explode('/', $this->app_path);
		$install_path = $this->config->paths->sub_folder?$this->config->paths->sub_folder.'/'.$this->config->paths->app_folder:$this->config->paths->app_folder;
		$install_folders = array_reverse(explode('/', $install_path));

		// drop folders off the end of the installation directory for this file until we've matched the full installation path specified in the config file.
		// once we've matched the installation directory completely, we break out of the loop. otherwise, if we don't match it, we take the filesystem path
		// down to nothing and we break out of the loop that way (and have no FS Path to use, which is an error condition)
		while (!empty($install_folders) && !empty($folders)) {
			$folder = array_pop($folders);
			if ($install_folders[0] == $folder) array_shift($install_folders);
		}
		if (!empty($folders)) {
			$this->paths['FSPATH'] = implode('/', $folders);
		}
		else {
			throw new Exception('The File System PATH for our installation could not be determined.');
		}
	}

	if ($this->config->paths->URLPATH) $this->paths['URLPATH'] = $this->config->paths->URLPATH;
	else {
		if (isset($this->config->paths->sub_folder) && !is_null($this->config->paths->sub_folder)) $this->paths['URLPATH'] = $this->config->paths->sub_folder;
		else $this->paths['URLPATH'] = '';
	}
}

private function set_globals() {
	$GLOBALS['cktpl'] = new ck_template(realpath($this->app_path.'/../templates'));
}

// function list - for now, since we're not identifying a separate file for stand-alone functions, put them here in the framework class
// they're static since they have no bearing on state
public static function check_flag($flag) {
	if ($flag === TRUE || in_array(strtolower(trim($flag)), array('y', 'yes', 'on', '1', 't', 'true'))) return TRUE;
	elseif ($flag === FALSE || in_array(strtolower(trim($flag)), array('n', 'no', 'off', '0', 'f', 'false'))) return FALSE;
	else return NULL;
}

}
?>