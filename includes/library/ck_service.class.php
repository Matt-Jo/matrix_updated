<?php
abstract class ck_service extends ck_master_archetype {

	private $authenticated = FALSE; // we'll have to do *something* to open our service up if we want to use it

	const AUTH_DEFER = 'DEFER'; // if authentication is deferred to a later step, then we can proceed

	const BASIC = 'BASIC'; // non-soap and non-rest service
	const REST = 'REST'; // rest service
	const SOAP = 'SOAP'; // soap service

	const RESPONSE_JSON = 'JSON'; // respond with JSON
	const RESPONSE_XML = 'XML'; // respond with XML
	const RESPONSE_TEXT = 'TEXT'; // respond with non-structured text

	protected $service_type;

	protected $response_type;

	protected $requested_version;
	protected $requested_endpoint;

	protected $response = [];

	protected function set_service_type($service_type) {
		if (!in_array($service_type, [self::BASIC, self::REST, self::SOAP])) throw new CKServiceException('Service type ['.$service_type.'] not supported.');
		$this->service_type = $service_type;
	}

	public function get_service_type() {
		return $this->service_type;
	}

	public function set_requested_version($version) {
		$this->requested_version = $version;
	}

	public function set_requested_endpoint($endpoint) {
		$this->requested_endpoint = $endpoint;
	}

	public function is_authenticated() {
		return CK\fn::check_flag($this->authenticated) || $this->authenticated == self::AUTH_DEFER;
	}

	final public function authenticate() {
		$this->authenticated = $this->_authenticate();

		return $this->is_authenticated();
	}

	final public function process_request() {
		if (!$this->is_authenticated()) {}
		else return $this->_process_request();
	}

	final public function act() {
		if (!$this->is_authenticated()) {}
		else return $this->_act();
	}

	final public function respond() {
		if (!$this->is_authenticated()) {}
		else return $this->_respond();
	}

	abstract protected function _authenticate();
	abstract protected function _process_request();
	abstract protected function _act();
	abstract protected function _respond();

	protected function render($response=NULL) {
		if (empty($response)) $response = $this->response;
		switch ($this->response_type) {
			case self::RESPONSE_JSON:
				return json_encode($response);
				break;
			case self::RESPONSE_XML:
				return $response->asXML();
				break;
			case self::RESPONSE_TEXT:
			default:
				return $response;
				break;
		}
	}

	protected function init_response() {
		$this->response = [];
	}
}

class CKServiceException extends CKMasterArchetypeException {
}
?>
