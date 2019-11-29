<?php
class ck_config2 { // extends Zend_Config_Ini {

	const ENV_PRODUCTION = 'production';
	const ENV_STAGING	= 'staging';
	const ENV_DEVELOPMENT = 'development';

	protected $_environment;

	private $config_keys = [];

	/*public function __construct() {
		$this->_environment = self::ENV_DEVELOPMENT;
		$host = !empty($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:php_uname('n');

		// either prod or dev
		if (in_array($host, array('www.cablesandkits.com', 'cablesandkits.com', 'tmppvt.cablesandkits.com', 'matrix.atlantechinc.net'))) {
			// check for CLI
			$this->_environment = self::ENV_PRODUCTION;
		}

		$filename = dirname(__FILE__).'/../../config/config.ini';

		parent::__construct($filename, $this->_environment);
	}

	public function isProduction() {
		return $this->_environment == self::ENV_PRODUCTION;
	}

	public function isStaging() {
		return $this->_environment == self::ENV_STAGING;
	}

	public function isDevelopment() {
		return $this->_environment == self::ENV_DEVELOPMENT;
	}

	public function getEnv() {
		return $this->_environment;
	}

	public static function getInstance() {
		try {
			$config = Zend_Registry::get('config');
		}
		catch (Zend_Exception $e) {
			$config = new self();
			Zend_Registry::set('config', $config);
		}

		return $config;
	}*/

	public static function preload_legacy($type=NULL) {
		$table = '';

		switch ($type) {
			case 'faqdesk':
				$table = 'faqdesk_configuration';
				break;
			default:
				$table = 'configuration';
				break;
		}

		if ($config_values = prepared_query::fetch('SELECT configuration_key, configuration_value FROM '.$table)) {
			foreach ($config_values as $cfg) {
				if (!defined($cfg['configuration_key'])) define($cfg['configuration_key'], $cfg['configuration_value']);
			}
		}
	}
}
?>
