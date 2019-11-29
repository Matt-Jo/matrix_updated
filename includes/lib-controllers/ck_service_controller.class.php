<?php
class ck_service_controller extends ck_controller {

	private $services = [];

	private $request = [
		'service' => NULL,
		'version' => NULL,
		'endpoint' => NULL
	];

	public function __construct($context=NULL) {
		$this->build_context($context);
	}

	private function build_context($context=NULL) {
		if (empty($context)) $context = $_GET['request'];

		$parts = explode('/', $context, 3);

		if (!empty($parts[0])) $this->request['service'] = $parts[0];

		if (!empty($parts[1])) {
			if (preg_match('/^v?(\d\.?)+$/', $parts[1])) $this->request['version'] = $parts[1];
			else $this->request['endpoint'] = $parts[1];
		}

		if (!empty($parts[2])) {
			if (is_null($this->request['endpoint'])) $this->request['endpoint'] = $parts[2];
			else $this->request['endpoint'] .= '/'.$parts[2];
		}
	}

	public function register_service($service, $handler) {
		$this->services[$service] = $handler;
	}

	public function run() {
		if (empty($this->services[$this->request['service']])) throw new CKServiceControllerException('Service ['.$this->request['service'].'] is not recognized');

		$service_handler = $this->services[$this->request['service']];

		if (class_exists($service_handler)) {
			$service = new $service_handler;
			$service->set_requested_version($this->request['version']);
			$service->set_requested_endpoint($this->request['endpoint']);
		}
		elseif (is_callable($service_handler)) {
			$service = $service_handler(['requested_version' => $this->request['version'], 'requested_endpoint' => $this->request['endpoint']]);
		}
		else throw new CKServiceControllerException('Service Handler for Service ['.$this->request['service'].'] could not be found');

		return $service;
	}
}

class CKServiceControllerException extends CKMasterArchetypeException {
}
?>
