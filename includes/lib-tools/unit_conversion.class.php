<?php
class unit_conversion {
	const DEFAULT_PRECISION = 2;

	public static function pounds_to_kilograms($pounds, $precision=self::DEFAULT_PRECISION) {
		return round($pounds * 0.453592, $precision);
	}

	public static function lbs2kg($lbs, $precision=self::DEFAULT_PRECISION) {
		return self::pounds_to_kilograms($lbs, $precision);
	}

	public static function kilograms_to_pounds($kilograms, $precision=self::DEFAULT_PRECISION) {
		return round($kilograms * 2.204622, $precision);
	}

	public static function kg2lbs($kg, $precision=self::DEFAULT_PRECISION) {
		return self::kilograms_to_pounds($kg, $precision);
	}

	public static function inches_to_centimeters($inches, $precision=self::DEFAULT_PRECISION) {
		return round($inches * 2.54, $precision);
	}

	public static function in2cm($in, $precision=self::DEFAULT_PRECISION) {
		return self::inches_to_centimeters($in, $precision);
	}

	public static function centimeters_to_inches($centimeters, $precision=self::DEFAULT_PRECISION) {
		return round($centimeters * 0.393701, $precision);
	}

	public static function cm2in($cm, $precision=self::DEFAULT_PRECISION) {
		return self::centimeters_to_inches($cm, $precision);
	}
}
?>
