<?php
use Interop\Container\ContainerInterface;
use Zend\Session\Service\SessionManagerFactory;
use Zend\Session\Config\StandardConfig;

/**
 * Class responsible of building an instance that implements session_service_interface
 * @todo: replace factories with configuration files per environment
 */
class session_manager_factory extends SessionManagerFactory {

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $sessionManager = parent::__invoke($container, $requestedName, $options);
        $sessionConfiguration = new StandardConfig();
        // injecting session manager configuration
        $sessionConfiguration->setOptions($container->get('config')['session']);
        return $sessionManager;
    }

}

