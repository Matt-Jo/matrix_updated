<?php
use Zend\ServiceManager\Factory\FactoryInterface;

class zf1_db_service_factory implements FactoryInterface {

    /**
     * Create an object
     *
     * @param  \Interop\Container\ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException if unable to resolve the service.
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws \Interop\Container\Exception\ContainerException if any other error occurs
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $requestedName, array $options = null)
    {
        $cacheKey = 'ckstore';
        try {
            $db = \Zend_Registry::get($cacheKey);
        } catch (\Zend_Exception $e) {
            // needed until MySQL 5.1
            $params['driver_options'] = array(
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            );

            $config = \Zend_Registry::get('config');

            // set utf8 for all connections
            $params['charset'] = 'utf8';

            $params = array_merge($config->database->params->toArray(), $params);

            if (!isset($adapter)) {
                $adapter = $config->database->adapter;
            }

            $db = \Zend_Db::factory($adapter, $params);

            \Zend_Registry::set($cacheKey, $db);
        }

        return $db;
    }
}