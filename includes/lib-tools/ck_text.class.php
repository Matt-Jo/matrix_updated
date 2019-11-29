<?php
namespace CK;

class text {
	public static function email_match($email1, $email2) {
		return trim(strtolower($email1)) === trim(strtolower($email2));
	}

	public static function numerify($number, $decimals=NULL, $decimal_point='.', $thousands_separator='') {
		$number = self::demonetize($number, $decimals, $decimal_point, $thousands_separator);
		return !empty($number)?$number:0;
	}

	// take in text that may have characters keeping it from being treated like a number and safely make it numeric - specifically used for money inputs, to remove the leading '$'
	public static function demonetize($amount, $decimals=2, $decimal_point='.', $thousands_separator=',', $currency_identifier='$') {
		$amount = (string) $amount;

		// remove all spaces
		$amount = preg_replace('/\s+/', '', $amount);

		// remove a leading money identifier
		$amount = preg_replace('/^\\'.$currency_identifier.'/', '', $amount);

		// remove existing thousands separator
		$amount = preg_replace('/'.$thousands_separator.'/', '', $amount);

		// get rid of anything after a non-numeric character
		$amount = preg_replace('/^([0-9'.$decimal_point.'-]+)[^0-9'.$decimal_point.'-].*$/', '$1', $amount);

		// dashes are only allowed at the beginning to denote negative numbers
		$amount = preg_replace('/^(.+)-.*$/', '$1', $amount);

		// only one decimal is allowed, get rid of any subsequent ones
		$amount = preg_replace('/^([^.]*[.][^.]*)[.].*$/', '$1', $amount);

		// format as money, to the specified number of places
		if (!empty($amount) && !is_null($decimals)) $amount = number_format($amount, $decimals, $decimal_point, '');

		return $amount;
	}

	// the reverse, take a number and format it for text output as money
	public static function monetize($amount, $decimals=2, $decimal_point='.', $thousands_separator=',', $currency_identifier='$') {
		$amount = self::demonetize($amount, $decimals, $decimal_point, $thousands_separator, $currency_identifier);
		if ($amount < 0) return '-'.$currency_identifier.number_format(abs($amount), $decimals, $decimal_point, $thousands_separator);
		elseif (empty($amount)) return $currency_identifier.number_format(0, $decimals, $decimal_point, $thousands_separator);
		else return $currency_identifier.number_format($amount, $decimals, $decimal_point, $thousands_separator);
	}
}
?>
