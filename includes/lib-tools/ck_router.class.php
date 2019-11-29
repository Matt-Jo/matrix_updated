<?php
// need to set up the concept of a "destination" or "target" - and multiple sources or requests could wind up at the same target
class ck_router extends ck_singleton {

	private $instance_request = NULL;
	private $instance_script = NULL;
	private $request_path = NULL;
	// we don't actually care about any of the request parts other than request path
	// but we may decide to change that
	//private $request_query = NULL;
	//private $request_fragment = NULL; // unlikely to ever have info

	private $route_type = [];
	private $routes = [];

	const SIMPLE = 'SIMPLE';
	const FULL = 'FULL';

	protected function init($parameters=[]) {
		$this->route_type['/index.php'] = 'SIMPLE';
		$this->routes['/index.php'] = NULL;

		$this->route_type['/index.html'] = 'SIMPLE';
		$this->routes['/index.html'] = function() { CK\fn::redirect_and_exit('index.php'); };

		$this->instance_request = !empty($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'';
		$this->instance_script = !empty($_SERVER['SCRIPT_FILENAME'])?$_SERVER['SCRIPT_FILENAME']:'';
		$this->parse_request();
	}

	public function create_simple_route($requested_target, $final_target) {
		$this->route_type[$requested_target] = self::SIMPLE;
		$this->routes[$requested_target] = $final_target;
	}
	
	public function create_route($requested_target, array $final_targets, Closure $process_target) {
		$this->route_type[$requested_target] = self::FULL;
		$this->routes[$requested_target] = ['process' => $process_target, 'targets' => $final_targets];
	}

	public function destroy_route($requested_target) {
		unset($this->route_type[$requested_target]);
		unset($this->routes[$requested_target]);
	}

	private function parse_request() {
		$parts = pathinfo($this->instance_script);
		if (!empty($parts['basename'])) $this->request_path = '/'.$parts['basename'];
		//if (!empty($parts['query'])) $this->request_query = $parts['query'];
		//if (!empty($parts['fragment'])) $this->request_fragment = $parts['fragment'];
	}

	public function route($requested_target=NULL) {
		if (empty($requested_target)) $requested_target = $this->request_path;

		if (empty($this->routes[$requested_target])) return NULL;

		if ($this->route_type[$requested_target] == self::SIMPLE) return $this->routes[$requested_target];
		elseif ($this->route_type[$requested_target] == self::FULL) {
			$final_target = $this->routes[$requested_target]['process']();
			if (!empty($final_target)) return @$this->routes[$requested_target]['targets'][$final_target];
		}
	}
}
?>
