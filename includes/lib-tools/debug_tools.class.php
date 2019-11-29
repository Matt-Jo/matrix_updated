<?php
class debug_tools {
	const PAGE_FLAG = 'DEBUG_PAGE';
	const MEM_FLAG = 'DEBUG_MEMORY';
	const PERSIST_FLAG = 'DEBUG_PERSIST';
	const DESIST_FLAG = 'DEBUG_DESIST';

	private static $flags = [
		'print' => FALSE,
		'memory' => FALSE,
	];

	const TIME_SECONDS = 'TIME_SECONDS';
	const TIME_SHORT = 'TIME_SHORT';
	const TIME_LONG = 'TIME_LONG';

	private static $time_format = self::TIME_SECONDS;

	private static $formats = [
		self::TIME_SHORT => 'z H:i:s',
		self::TIME_LONG => 'z \d\a\y\s, H \h\o\u\r\s, i \m\i\n\u\t\e\s, s \s\e\c\o\n\d\s'
	];

	private static $counter = 0;
	private static $timer;

	private static $sub_timers = [];
	private static $sub_timer_context;
	private static $sub_times = [];

	private static $interleave_timers = [];

	private static $buffer = FALSE;

	private static function cli() {
		return PHP_SAPI==='cli';
	}

	public static function init_page() {
		self::start_timer();

		if (request_flags::instance()[self::DESIST_FLAG]) {
			unset($_SESSION[__CLASS__.'.persist.print']);
			unset($_SESSION[__CLASS__.'.persist.memory']);
		}

		if (request_flags::instance()[self::PAGE_FLAG]) self::enable_flag('print');
		elseif (!empty($_SESSION[__CLASS__.'.persist.print'])) self::enable_flag('print');

		if (request_flags::instance()[self::MEM_FLAG]) self::enable_flag('memory');
		elseif (!empty($_SESSION[__CLASS__.'.persist.memory'])) self::enable_flag('memory');

		if (request_flags::instance()[self::PERSIST_FLAG]) {
			$_SESSION[__CLASS__.'.persist.print'] = self::status_flag('print');
			$_SESSION[__CLASS__.'.persist.memory'] = self::status_flag('memory');
		}
	}

	public static function show_all() {
		self::enable_flag('print');
		self::enable_flag('memory');
	}

	public static function status_flag($flag) {
		return self::$flags[$flag];
	}

	public static function enable_flag($flag) {
		self::$flags[$flag] = TRUE;
	}

	public static function disable_flag($flag) {
		self::$flags[$flag] = FALSE;
	}

	public static function get_time() {
		return microtime(TRUE);
	}

	public static function set_time_format($format) {
		if (!in_array($format, [self::TIME_SECONDS, self::TIME_SHORT, self::TIME_LONG])) $format = self::TIME_SECONDS;
		self::$time_format = $format;
	}

	public static function get_formatted_time($time) {
		switch (self::$time_format) {
			case self::TIME_SECONDS:
				return round($time * 10) / 10;
				break;
			case self::TIME_SHORT:
			case self::TIME_LONG:
				return gmdate(self::$formats[self::$time_format], $time);
				break;
		}
	}

	public static function get_memory_usage() {
		$mu = memory_get_usage();

		$scale = [
			1000000000 => 'GB',
			1000000 => 'MB',
			1000 => 'KB',
			1 => 'B',
		];

		foreach ($scale as $break => $descriptor) {
			if ($mu < $break) continue;

			$mu = (round(($mu * 10) / $break) / 10).$descriptor;
			break; // once we get to a valid one, skip the rest
		}

		return $mu;
	}

	// interleave timer gives us a way to time a specific portion of a larger loop without timing everything in that loop
	// you can start and stop an interleave timer multiple times and it just counts time while its active
	public static function open_interleave_timer($label) {
		if (empty(self::$interleave_timers[$label])) self::$interleave_timers[$label] = ['running_time' => 0, 'running_count' => 0];
		self::$interleave_timers[$label]['current_start'] = self::get_time();
	}

	public static function close_interleave_timer($label) {
		self::$interleave_timers[$label]['running_time'] += self::get_time() - self::$interleave_timers[$label]['current_start'];
		self::$interleave_timers[$label]['running_count']++;
	}

	public static function show_interleave_times() {
		if (!self::status_flag('print')) return;

		foreach (self::$interleave_timers as $label => $times) {
			echo 'Debug ['.$label.' - Interleave]: Elapsed time - '.self::get_formatted_time($times['running_time']).' | Count '.$times['running_count'];
			if (self::status_flag('memory')) echo ' | Memory Usage: '.self::get_memory_usage();
			if (self::cli()) echo "\n-----------------------------\n";
			else echo '<br><hr><br>';
		}

		flush();
	}

	// mark gives us a way to send a message to the client while keeping track of the timing since we started tracking time.
	// if any sub-timers are active, keep track of that time separately.
	public static function mark($label=NULL, $silent=FALSE) {
		if (is_null($label)) $label = self::$counter++;

		self::start_timer();

		$time = self::get_time();

		if (!empty(self::$sub_timer_context)) {
			if (empty(self::$sub_timers[self::$sub_timer_context])) self::start_sub_timer(self::$sub_timer_context);
			$sub_time = self::get_time();
		}

		if (!self::status_flag('print')) return;

		if ($silent && !self::$buffer) {
			ob_start();
			self::$buffer = TRUE;
		}
		elseif (!$silent && self::$buffer) {
			echo ob_get_clean();
			self::$buffer = FALSE;
		}

		echo 'Debug ['.$label.']: Elapsed Time - '.self::get_formatted_time($time - self::$timer);

		if (!empty(self::$sub_timer_context)) {
			echo ' [[Sub Timer | '.self::$sub_timer_context.' | '.self::get_formatted_time($sub_time - self::$sub_timers[self::$sub_timer_context]).']]';
		}

		if (self::status_flag('memory')) echo ' | Memory Usage: '.self::get_memory_usage();

		if (self::cli()) echo "\n-----------------------------\n";
		else echo '<br><hr><br>';

		if (!self::$buffer) flush();
	}

	public static function start_timer() {
		if (empty(self::$timer)) self::$timer = self::get_time();
	}

	public static function start_sub_timer($label) {
		self::$sub_timers[$label] = self::get_time();
		self::$sub_timer_context = $label;
	}

	public static function set_sub_timer_context($label) {
		self::$sub_timer_context = $label;
	}

	public static function clear_sub_timer_context() {
		self::$sub_timer_context = NULL;
	}

	// note ignores all timing - just pop out a message to the screen
	public static function note($label=NULL, $silent=FALSE) {
		if (is_null($label)) $label = self::$counter++;

		if (!self::status_flag('print')) return;

		if ($silent && !self::$buffer) {
			ob_start();
			self::$buffer = TRUE;
		}
		elseif (!$silent && self::$buffer) {
			echo ob_get_clean();
			self::$buffer = FALSE;
		}

		echo 'Debug ['.$label.']';

		if (self::status_flag('memory')) echo ' | Memory Usage: '.self::get_memory_usage();

		if (self::cli()) echo "\n-----------------------------\n";
		else echo '<br><hr><br>';

		if (!self::$buffer) flush();
	}
}
?>
