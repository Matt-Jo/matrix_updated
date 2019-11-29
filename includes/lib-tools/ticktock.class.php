<?php
class ticktock {
	private $tickers = [];
	private $times = [];
	private $cumulative = [];

	public function __construct() {
	}

	public function &__get($label) {
		return $this->times[$label];
	}

	public function __set($label, $time) {
		return $this->times[$label] = $time;
	}

	public function __unset($label) {
		unset($this->times[$label]);
	}

	public function __isset($label) {
		return isset($this->times[$label]);
	}

	public function __toString() {
		$output = '';
		foreach ($this->times as $label => $time) {
			$output .= $this->show($label);
		}
		foreach ($this->tickers as $label => $time) {
			$output .= $this->show($label);
		}
		return $output;
	}

	public function show($label) {
		if (!empty($this->tickers[$label])) $this->tock($label);
		return '['.$label.' '.number_format($this->$label, 4).']';
	}

	public function tick($label='no label', $cumulative=FALSE) {
		$this->tickers[$label] = microtime(TRUE);
		$this->cumulative[$label] = $cumulative;
	}

	public function tock($label='no label') {
		if ($this->cumulative[$label]) $this->$label += microtime(TRUE) - $this->tickers[$label];
		else $this->$label = microtime(TRUE) - $this->tickers[$label];

		unset($this->tickers[$label]);
	}
}
?>
