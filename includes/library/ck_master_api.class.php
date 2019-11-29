<?php
abstract class ck_master_api extends ck_master_archetype {

	const RUNTIME_PRODUCTION = 'PRODUCTION';
	const RUNTIME_STAGING = 'STAGING'; // not used in today's environment
	const RUNTIME_DEVELOPMENT = 'DEVELOPMENT';

	protected static $runtime_context = self::RUNTIME_DEVELOPMENT; // default to safest setting

	protected static function set_runtime_context($context=NULL) {
		if (!is_null($context)) self::$runtime_context = $context;
		elseif (self::is_production()) self::$runtime_context = self::RUNTIME_PRODUCTION;
		else self::$runtime_context = self::RUNTIME_DEVELOPMENT;
	}

	protected static function get_runtime_context() {
		return self::$runtime_context;
	}

	protected static function runtime_context_is($context) {
		return self::$runtime_context === $context;
	}

	// this is typically needed in API responses when an element could be a single element or an array of like elements
	// we can use this to force them all to an array so we can use the same code block to process
	private static function toArray(&$element) {
		if (empty($element)) return FALSE;
		if (is_array($element)) return TRUE;

		// modify by ref
		$element = [$element];
		return TRUE;
	}

	protected static function is_production() {
		return service_locator::get_config_service()->is_production();
	}

	protected static function is_development() {
		return service_locator::get_config_service()->is_development();
	}

	/*------------------------------*/
	// Wrangle site-wide singletons
	// supports dependency injection, or setting per context, or accessing the global singleton
	/*------------------------------*/

	protected static $environment = NULL;
	public static function set_environment($environment) {
		static::$environment = $environment;
	}
	protected static function get_environment($environment=NULL) {
		return $environment ?? self::$environment ?? service_locator::get_config_service()->get_env();
	}
}

class CKApiException extends Exception {
}
?>
