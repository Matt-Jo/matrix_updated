<?php
class rest extends request {

	protected $header = []; // last header cached

	private $restopts = [
		CURLOPT_HEADER => TRUE
	];

	public function __construct($opts=[]) {
		foreach ($this->restopts as $opt => $val) {
			if (!isset($opts[$opt])) $opts[$opt] = $val;
		}
		parent::__construct($opts);
	}

	protected function clear() {
		$this->result = NULL;
		$this->header = [];
	}

	public function send($method, $options=[], $url=NULL, $data='', $https=NULL) {
		if (!empty($options)) {
			foreach ($options as $opt => $value) {
				curl_setopt($this->ch, $opt, $value);
			}
		}

		$this->clear();

		parent::_custom($method, $url, $data, $https);

		return $this->parse_response();
	}

	protected function parse_response() {
		$parts = explode("\r\n\r\n", $this->result, 2);

		$this->parse_headers($parts[0]);

		if (!empty($parts[1])) {
			$this->result = $parts[1];
			if (substr($parts[1], 0, 8) == 'HTTP/1.1') $this->parse_response();
		}
		else $this->result = NULL;

		return $this->result;
	}

	private function parse_headers($headers) {
		$headers = explode("\n", $headers);

		foreach ($headers as $header) {
			$keyval = explode(': ', $header, 2);
			if (count($keyval) == 1) $this->header[] = $keyval[0];
			else $this->header[$keyval[0]] = $keyval[1];
		}

		return $this->header;
	}

	public function header() {
		return $this->header;
	}

	public function statusOK() {
		return $this->status() >= 200 && $this->status() < 300;
	}
}