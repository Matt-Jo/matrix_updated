<?php
use Zend_Session;
use Zend_Session_SaveHandler_DbTable;

require_once (realpath('library/Zend/Loader/Autoloader.php'));

/**
 * Session manager for Zend_Session:1.*
 */
class zf1_session_manager implements session_service_interface {

    private static $instance = null;

    static function instance(array $config = []): zf1_session_manager {
        if(self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }


    function __construct(array $config) {
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
    }

    function start() {
        Zend_Session::start();
    }

    function destroy() {
        Zend_Session::destroy();
    }

    function regenerate_id() {
        Zend_Session::regenerateId();
    }

    function session_exists(): bool {
        return Zend_Session::sessionExists();
    }
}

