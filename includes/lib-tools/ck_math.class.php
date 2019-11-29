<?php
namespace CK;

class math {
	public static function mean() {
		$args = fn::expand_arg_list(func_get_args());

		return array_sum($args) / count($args);
	}

	// this median() function very slightly modified from php.net:
	// http://php.net/manual/en/ref.math.php
	// author: crescentfreshpot at yahoo dot com 26-Jul-2005 12:50
	public static function median() {
		$args = fn::expand_arg_list(func_get_args());

		sort($args);

		$n = count($args);
		$h = intval($n / 2);

		if ($n%2 == 0) $median = self::mean($args[$h], $args[$h-1]);
		else $median = $args[$h];

		return $median;
	}

	public static function mode() {
		$args = fn::expand_arg_list(func_get_args());

		$set = array_count_values($args);

		arsort($set);

		$max;
		$modes = [];

		foreach ($set as $count => $element) {
			if (empty($max)) $max = $count;
			elseif ($count < $max) break;
			$modes[] = $mode;
		}

		if (count($modes) == 1) return $modes[0];
		else return $modes;
	}

	public static function round_to_nearest($val, $target, $mode=PHP_ROUND_HALF_UP) {
		return round($val / $target, 0, $mode) * $target;
	}
}
?>