<?php

use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\Proxy\LazyServiceFactory;
use Zend\ServiceManager\ServiceManager;

/**
 * Class service_locator knows howto build a ServiceManager
 *
 *
 * @todo: replace explicit services instances with configuration where services can be different per environment
 * @todo: hide vendors details whenever possible to reduce coupling. Expose our own interface instead. 
 *
 */
class service_locator {

	const CONTEXT_ECP = 'ECP';
	const CONTEXT_ERP = 'ERP';

    protected static $instance = null;

    /**
     * @see https://docs.zendframework.com/zend-servicemanager/quick-start/
     * @return ServiceManager
     */
    public static function instance($context=self::CONTEXT_ECP): ServiceManager {
        if(self::$instance === null) {
            $config = zf2_config_manager::instance();
			$session_config = $config['session'];
			if ($context == self::CONTEXT_ERP) $session_config = array_merge($session_config, $config['admin']['session']);

	        self::$instance = new ServiceManager([
                'factories' =>  [
                    db_service_interface::class         =>      zf2_db_service_factory::class,
                    cache_service_interface::class      =>      cache_service_interface::class,
                ],
                'aliases'   =>  [
                    'db'                                =>  db_service_interface::class,
                    'cache'                             =>  cache_service_interface::class,
                    'mail'                              =>  mail_service_interface::class
                ],
                'services'  =>  [
                    'config'                            =>  $config,
                    'session'                           =>  zf2_session_manager::instance($session_config),
                    'cache'                             =>  cache_manager::instance(),
                    'mail'                              =>  aws_ses_mail_service::instance([
                        'aws_access_key_id'    => $config['aws_access_key_id'] ?? null,
                        'aws_secret_access_key' => $config['aws_secret_access_key'] ?? null,
                    ])
                ],
                'lazy_services' => [
                    // Mapping services to their class names is required
                    // since the ServiceManager is not a declarative DIC.
                    'class_map' => [
                        'session'   =>  zf2_session_manager::class,
                        'db'        =>  db_service_interface::class,
                        'mail'      =>  mail_service_interface::class
                    ],
                ],
                'delegators' => [
                    'session' => [
                        LazyServiceFactory::class,
                    ],
                    'db'    =>  [
                        db_service_interface::class,
                    ],
                    'cache' => [
                        cache_manager::class,
                    ],
                ],
            ]);
        }
        return self::$instance;
    }


    public static function get_session_service($context=self::CONTEXT_ECP) : session_service_interface {
        return self::instance($context)->get('session');
    }

    public static function get_db_service() : db_service_interface {
        return self::instance()->get('db');
    }

    public static function get_config_service($context=self::CONTEXT_ECP) : config_service_interface {
        return self::instance($context)->get('config');
    }

    public static function get_cache_service() : cache_service_interface {
        return self::instance()->get('cache');
    }

    public static function get_mail_service() : mail_service_interface {
        return self::instance()->get('mail');
    }

    protected function __construct() {}


}

