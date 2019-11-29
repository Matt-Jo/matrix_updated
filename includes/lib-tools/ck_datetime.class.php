<?php
class ck_datetime extends DateTime {
	private $raw_timestamp;
	private $null_time = FALSE;

	const SHORT_DATE = 'm/d/Y';
	const LONG_DATE = 'l d F, Y';
	const FRIENDLY_TIMESTMAP_24H = 'm/d/Y H:i:s';
	const FRIENDLY_TIMESTAMP_12H = 'm/d/Y h:i:s a';
	const TIMESTAMP = 'Y-m-d H:i:s';
	const DATESTAMP = 'Y-m-d';
	const ISO = 'c';

	const STANDARD_TIME = 'STANDARD_TIME';
	const MILITARY_TIME = 'MILITARY_TIME';

	private static $one_day;
	private static $one_month;
	private static $one_year;

	public function __construct($timestamp=NULL) {
		$this->raw_timestamp = $timestamp;
		if (self::is_null_dt($this->raw_timestamp)) $this->null_time = TRUE;
		else parent::__construct($this->raw_timestamp);
	}

	public static function format_direct($date, $format) {
		$dt = self::datify($date);
		return !empty($dt)?$dt->format($format):NULL;
	}

	public static function datify($date, $or_now=FALSE) {
		if ($date instanceof self) return $date;
		elseif ($date instanceof DateTime) return new self($date->format(self::ISO));
		elseif (!empty($date) && !self::is_null_dt($date)) return new self($date);
		else return $or_now?self::NOW():NULL;
	}

	public static function one_day() {
		if (empty(self::$one_day)) self::$one_day = new DateInterval('P1D');
		return self::$one_day;
	}

	public static function NOW() {
		return new self();
	}

	public static function TODAY() {
		$today = self::NOW();
		$today->remove_time();
		return $today;
	}

	public static function is_null_dt($timestamp) {
		return in_array($timestamp, ['0000-00-00', '0000-00-00 00:00:00', '00:00:00']);
	}

	public function is_today() {
		$copy = clone $this;
		$copy->remove_time();

		return $copy == self::TODAY();
	}

	public function format($format) {
		if (!$this->has_timestamp()) return '';
		else return parent::format($format);
	}

	public function has_timestamp() {
		return !$this->null_time;
	}

	public function remove_time() {
		$this->setTime(0, 0, 0);
	}

	public function add_day() {
		$this->add(self::one_day());
		return $this;
	}

	public function next_day() {
		$next_day = clone $this;
		$next_day->add(self::one_day());

		return $next_day;
	}

	public function set_to_next_business_day($force_once=FALSE, $modify_time=FALSE) {
		if ($force_once) {
			$this->add_day();
			if ($modify_time) {
				$this->setTime(1, 0, 0);
				$modify_time = FALSE;
			}
		}

		while ($this->is_weekend()) {
			$this->add_day();
			if ($modify_time) {
				$this->setTime(1, 0, 0);
				$modify_time = FALSE;
			}
		}
	}

	public function previous_day() {
		$previous_day = clone $this;
		$previous_day->sub(self::one_day());

		return $previous_day;
	}

	public function is_weekend() {
		return $this->format('N') >= 6;
	}

	public function short_date() {
		return $this->format(self::SHORT_DATE);
	}

	public function long_date() {
		return $this->format(self::LONG_DATE);
	}

	public function iso() {
		return $this->format(self::ISO);
	}

	public function friendly($format=self::STANDARD_TIME) {
		switch ($format) {
			case self::MILITARY_TIME:
				return $this->format(self::FRIENDLY_TIMESTMAP_24H);
				break;
			case self::STANDARD_TIME:
			default:
				return $this->format(self::FRIENDLY_TIMESTMAP_12H);
				break;
		}
	}

	public function timestamp() {
		return $this->format(self::TIMESTAMP);
	}
}
?>
