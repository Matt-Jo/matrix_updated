<?php
abstract class ck_io {

	protected $settings = [];
	protected $request_history = [];
	protected $response_history = [];

	protected function log_request($request) {
		$this->request_history[] = $request;
	}

	protected function log_response($response=NULL) {
		$this->response_history[] = $response;
	}
}
?>
