<?php
namespace CK;

class standard_deviation extends math {

	private static $context = 'TOOLSET';

	private $set;
	private $sample;

	private $mean;
	private $variance;
	private $standard_deviation;

	private $max_std_dev;
	private $min_std_dev;

	public function __construct(array $set, $sample=FALSE) {
		self::$context = 'OBJECT';

		$this->set = $set;
		$this->sample = fn::check_flag($sample);

		$this->get_stddev(); // just go ahead and run it
	}

	private function check_context($args) {
		if (self::$context == 'OBJECT' && !empty($args)) {
			trigger_error('This object already has a set, please use this function statically');
			return FALSE;
		}
		return TRUE;
	}

	public function get_variance(array $set=NULL, $sample=FALSE) {
		if (!$this->check_context(func_num_args())) return FALSE;

		self::$context=='OBJECT'?$set = $this->set:NULL;
		self::$context=='OBJECT'?$sample = $this->sample:NULL;

		$n = count($set);
		if ($n === 0) {
			trigger_error('The array has zero elements', E_USER_WARNING);
			return FALSE;
		}
		if ($sample && $n === 1) {
			trigger_error('The array has only 1 element', E_USER_WARNING);
			return FALSE;
		}
		$mean = self::mean($set);
		$numerator = 0.0;
		foreach ($set as $value) {
			$d = ((double) $value) - $mean;
			$numerator += $d * $d;
		};

		if ($sample) $n--;

		$variance = $numerator / $n;

		if (self::$context == 'OBJECT') {
			$this->mean = $mean;
			$this->variance = $variance;
		}
		return $variance;
	}

	public function get_stddev(array $set=NULL, $sample=FALSE) {
		if (!$this->check_context(func_get_args())) return FALSE;

		if (self::$context == 'OBJECT') {
			$this->get_variance();
			$standard_deviation = $this->standard_deviation = sqrt($this->variance);

			$this->max_std_dev = $this->mean + $this->standard_deviation;
			$this->min_std_dev = $this->mean - $this->standard_deviation;
		}
		else {
			$variance = self::get_variance();
			$standard_deviation = sqrt($variance);
		}

		return $standard_deviation;
	}

	public function __get($key) {
		// we allow grabbing any of our properties, but their private because we don't allow setting them
		if (property_exists($this, $key)) return $this->$key;
		else trigger_error('The '.__CLASS__.' class does not have the '.$key.' property');
	}
}
?>
