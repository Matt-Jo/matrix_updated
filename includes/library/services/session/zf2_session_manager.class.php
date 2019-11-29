<?php
use Zend\Session\Config\StandardConfig;
use Zend\Session\SessionManager;
use Zend\Session\Storage\SessionArrayStorage;
use Zend\Db\TableGateway\TableGateway;
use Zend\Session\SaveHandler\DbTableGateway;
use Zend\Session\SaveHandler\DbTableGatewayOptions;


/**
 * Session manager for Zend_Session:2.*
 */
class zf2_session_manager implements session_service_interface {

    private static $instance = null;
    private $vendor;

    public static function instance(array $config = []): zf2_session_manager {
        if(self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }


    private function __construct(array $options) {
        $config = new StandardConfig();
        $config->setOptions($options);
        $this->vendor = new SessionManager($config);
		// zend appears to ignore the config, maybe entirely? at least the name is ignored
		if (!empty($config->getName())) $this->vendor->setName($config->getName());
        $this->vendor->setStorage(new SessionArrayStorage());
    }

    function start() {
		/*
         * @todo: evaluate if should implement the following save handler in ZF2
         // injecting session manager configuration
        Zend_Session::setOptions($config);
        //set the save handler for Zend_Session
        Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable(array(
            'name'			=> 'sessions',
            'primary'		=> 'sesskey',
            'modifiedColumn' => 'modified',
            'dataColumn'	=> 'value',
            'lifetimeColumn' => 'expiry'
        )));
         *
         * @todo: ZF2 version of the above:
            // 
         *
         */

		// this needs to be here in start, rather than in construct, because we need all services (specifically the DB service) to be fully init-ed
		$tableGateway = new TableGateway('sessions', service_locator::get_db_service()->get_adapter());
		$saveHandler = new DbTableGateway($tableGateway, new DbTableGatewayOptions([
			'idColumn' => 'sesskey',
			'nameColumn' => 'name',
			'modifiedColumn' => 'modified',
			'dataColumn' => 'value',
			'lifetimeColumn' => 'expiry',
		]));
		$this->vendor->setSaveHandler($saveHandler);

		try {
	        $this->vendor->start();
			return;
		}
		catch (Exception $e) {
			session_unset();
		}
    }

    function destroy() {
        $this->vendor->destroy();
    }

    function regenerate_id() {
        $this->vendor->regenerateId();
    }

    function session_exists(): bool {
        return $this->vendor->sessionExists();
    }
}