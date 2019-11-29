<?php
final class cardinality {
	// data cardinality - what is the "size" of the data element
	// mutually exclusive values
	const NONE = 0; // no results - create/update/delete
	const SINGLE = 1; // single value (for our purposes, an object qualifies)
	const COLUMN = 2; // single dimension array of homogeneous values
	const ROW = 3; // single dimension array of heterogeneous values
	const SET = 4; // two dimensional array of indexed rows
	const MAP = 5; // two dimensional array of keyed rows

	private static $map = [
		0 => 'NONE',
		1 => 'SINGLE',
		2 => 'COLUMN',
		3 => 'ROW',
		4 => 'SET',
		5 => 'MAP'
	];

	public static function lookup($cardinality) {
		if (!empty(self::$map[$cardinality])) return self::$map[$cardinality];
		else return 'UNKNOWN';
	}

	public static function exists($cardinality) {
		return self::lookup($cardinality) != 'UNKNOWN';
	}

	public static function dflt() {
		return self::SET;
	}
}
?>
