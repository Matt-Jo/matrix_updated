<?php

/**
 * Common interface to every pluggable session handling service
 */
interface session_service_interface extends service_interface {

    static function instance(array $config = []);

    /**
     * Creates a new session
     * @return mixed
     */
    function start();
    /**
     * Destroys an existing session
     * @return mixed
     */
    function destroy();
    /**
     * Regenerates session ID
     * @return mixed
     */
    function regenerate_id();
    function session_exists(): bool ;
}