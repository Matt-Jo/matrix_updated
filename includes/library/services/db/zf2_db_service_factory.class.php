<?php
use Interop\Container\ContainerInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterServiceFactory;
use Zend\ServiceManager\Factory\FactoryInterface;

class zf2_db_service_factory extends AdapterServiceFactory implements FactoryInterface {

    /**
     * @todo remove this method (use parent´s instead) once Zend_Config gets replaced by newer version
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object|Adapter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $options = $container->get('config')['db']['params'];
        $options["driver_options"] = [
            \PDO::MYSQL_ATTR_INIT_COMMAND   =>  "SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));"
        ];
        return new zf2_db_service($options);
    }

}