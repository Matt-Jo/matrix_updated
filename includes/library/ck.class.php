<?php
class ck {
	private static $autoload_paths = [
		'includes/library', // framework
		'includes/lib-models',
		'includes/library/services',
		'includes/library/services/config',
		'includes/library/services/db',
		'includes/library/services/session',
		'includes/library/services/cache',
		'includes/library/services/mail',
		'includes/lib-models/session',
		'includes/lib-views',
		'includes/lib-controllers',
		'includes/lib-lookups',
		'includes/lib-tools',
		'includes/lib-apis',
		'includes/lib-feeds',
		'includes/lib-extensions',
		'includes/lib-services',
		'includes/engine/framework', // legacy
		'includes/engine/tools', // legacy
		'admin/includes/library' //legacy
	];

	private static $site_root;
	private static $subdomain;

	public function __construct() {
		self::$site_root = realpath(__DIR__.'/../..');
		self::$subdomain = basename(self::$site_root);
		require_once(self::$site_root.'/includes/lib-tools/ck_keys.class.php'); // this one is required for the autoloader to work
		spl_autoload_register([$this, 'autoload']);
	}

	public function __destruct() {
		if (prepared_query::in_transaction()) prepared_query::transaction_end();
	}

	private static $maintenance_url = '/maintenance';

	public static function on_maintenance_page() {
		$compare = parse_url($_SERVER['REQUEST_URI']);

		if (self::$maintenance_url != $compare['path']) return FALSE;
		else return TRUE;
	}

	public static function maintenance_redirect() {
		if (self::in_maintenance_window() && !self::on_maintenance_page()) {
			header('Location: '.self::$maintenance_url, TRUE, 307);
			exit();
		}
	}

	private static $maintenance_scheduled = FALSE;
	private static $maintenance_window = [
		'start' => NULL,
		'end' => NULL,
	];

	public static function set_maintenance_window(ck_datetime $start, ck_datetime $end) {
		self::$maintenance_scheduled = TRUE;
		self::$maintenance_window['start'] = $start;
		self::$maintenance_window['end'] = $end;
	}

	public static function in_maintenance_window() {
		if (self::$maintenance_scheduled) {
			$now = ck_datetime::NOW();

			if ($now >= self::$maintenance_window['start'] && $now <= self::$maintenance_window['end']) return TRUE;
		}

		return FALSE;
	}

	public static function get_maintenance_window() {
		return self::$maintenance_window;
	}

	private static $maintenance_areas = [];

	public static function set_maintenance_area($area) {
		self::$maintenance_areas[$area] = TRUE;
	}

	public static function area_in_maintenance($area) {
		if (self::$maintenance_scheduled && self::in_maintenance_window()) {
			return isset(self::$maintenance_areas[$area])&&self::$maintenance_areas[$area];
		}

		return FALSE;
	}

	private function get_keys() {
		if (empty($GLOBALS['ck_keys'])) $GLOBALS['ck_keys'] = new ck_keys; // this should be a singleton, we'll have to fix that
		return $GLOBALS['ck_keys'];
	}

	public function register_autoload_path($path, $class=NULL) {
		if (!empty($class)) {
			$keys = $this->get_keys();
			$paths = $keys->{'autoload.'.self::$subdomain.'_paths'};
			$paths[$class] = self::$site_root.'/'.$path;
			$keys->{'autoload.'.self::$subdomain.'_paths'} = $paths;
		}
		elseif (!in_array($path, self::$autoload_paths)) self::$autoload_paths[] = self::$site_root.'/'.$path;
	}

	public function autoload($class) {
		$class = preg_replace('#^CK\\\\#', 'ck_', $class);
		$keys = $this->get_keys();

		if (isset($keys->autoload) && is_array($keys->autoload) && !empty($keys->autoload[self::$subdomain.'_paths'])) {
			$paths = (array) $keys->autoload[self::$subdomain.'_paths'];
		}
		else $paths = [];

		if (!empty($paths[$class])) {
			if (file_exists($paths[$class])) {
				include($paths[$class]);
				return;
			}
			else unset($paths[$class]);
		}

		$type = 'class';
		$base_name = $class;


		if (preg_match('/_interface$/', $class)) {
			$type = 'interface';
			$base_name = preg_replace('/_interface$/', '', $base_name);
		}
		elseif (preg_match('/_trait$/', $class)) {
			$type = 'trait';
			$base_name = preg_replace('/_trait$/', '', $base_name);
		}
	
		$file_name = $base_name.'.'.$type.'.php';

		foreach (self::$autoload_paths as $folder) {
			if (file_exists(self::$site_root.'/'.$folder.'/'.$file_name)) {
				$paths[$class] = self::$site_root.'/'.$folder.'/'.$file_name;
				include($paths[$class]);
				$keys->{'autoload.'.self::$subdomain.'_paths'} = $paths;
				return;
			}
		}

		// not found by this autoloader
	}
}
?>
