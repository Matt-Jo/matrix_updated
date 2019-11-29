<?php
/**
 * Common interface to every pluggable configuration handling service
 */
interface config_service_interface extends service_interface, ArrayAccess {
	const ENV_PRODUCTION = 'production';
	const ENV_STAGING	= 'staging';
	const ENV_DEVELOPMENT = 'development';
	
    function is_production(): bool;
	function is_staging(): bool;
	function is_development(): bool;
	function get_env(): string;
}

