<?php

/**
 * Session manager for automatic tests where session handling can be a mocked
 */
class mock_session_manager implements session_service_interface {

    private static $instance = null;

    public static function instance(array $config = []) {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    private function __construct(array $config) {}

    public function start() {
        session_start();
    }

    public function destroy() {
        session_destroy();
    }

    /**
     * Regenerates session ID
     * @return mixed
     */
    function regenerate_id()
    {
        // TODO: Implement regenerateId() method.
    }

    /**
     * @return bool
     */
    function session_exists(): bool
    {
        // TODO: Implement sessionExists() method.
    }
}