<?php
use \Zend\Config\Config;
use \Zend\Config\Reader\Ini;


/**
 * Config manager for Zend_Session:1.*
 */
class zf2_config_manager implements config_service_interface {

    private static $instance = null;
    private $config = null;
    private $_environment;

    /**
     * Singleton accessor
     *
     * @return config_service_interface
     */
    public static function instance(): config_service_interface {
        if(self::$instance == null) {
            self::$instance = new zf2_config_manager();
        }
        return self::$instance;
    }
    
    /**
     * To make this class a singleton the constructor 
     * must be private or protected
     */
    private function __construct() {
        $this->_environment = self::ENV_DEVELOPMENT;
        $host = !empty($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:php_uname('n');

        // either prod or dev
        if (in_array($host, array('www.cablesandkits.com', 'cablesandkits.com', 'tmppvt.cablesandkits.com', 'matrix.atlantechinc.net'))) {
            // check for CLI
            $this->_environment = self::ENV_PRODUCTION;
        }

        $filename = dirname(__FILE__).'/../../../../config/config.ini';
        $reader = new Ini();
        $data   = $reader->fromFile($filename);

        // a function to merge recursively without duplicates
        $recursiveMerge = function( array &$array1, array &$array2 ) use(&$recursiveMerge): array {
            $merged = $array1;
            foreach ( $array2 as $key => &$value ) {
                if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) ) {
                    $merged [$key] = $recursiveMerge ( $merged [$key], $value );
                } else {
                    $merged [$key] = $value;
                }
            }
            return $merged;
        };

        $parsedConfig = [];
        
        foreach($data as $key => $settings) {
            $keyArr = explode(" : ", $key);
            if(count($keyArr) > 1) {
                $parsedConfig[$keyArr[0]] = $recursiveMerge($data[$keyArr[1]], $settings);
            } else {
                $parsedConfig[$key] = $settings;
            }
        }

        $this->config = $parsedConfig;
    }

	public function is_production(): bool {
		return $this->_environment == self::ENV_PRODUCTION;
	}

	public function is_staging(): bool {
		return $this->_environment == self::ENV_STAGING;
	}

	public function is_development(): bool {
		return $this->_environment == self::ENV_DEVELOPMENT;
	}

	public function get_env(): string {
		return $this->_environment;
    }
    

    /**
     * Magic function so that $obj->value will work.
     */
    public function __get(string $name) {
        return self::instance()->config[$this->_environment][$name];
    }

    /**
     * Whether a offset exists
     * ArrayAccess interface implementation
     */
    public function offsetExists($offset) {
        return self::instance()->config[$this->_environment][$offset];
    }

    /**
     * Offset to retrieve
     * ArrayAccess interface implementation
     */
    public function offsetGet($offset) {
        return self::instance()->config[$this->_environment][$offset];
    }

    /**
     * Offset to set
     * Overriding config settings is forbidden
     */
    public function offsetSet($offset, $value) {
        throw new BadMethodCallException("Array access of class " . get_class($this) . " is read-only!");
    }

    /**
     * Offset to unset
     * Deleting config settings is forbidden
     */
    public function offsetUnset($offset) {
        throw new BadMethodCallException("Array access of class " . get_class($this) . " is read-only!");
    }

}

