<?php
final class data_types {
	// the intention of this set of constants is to be internally consistent - no one should use the numbers directly, so that they can be changed as needed

	// data types
	// mutually exclusive groups, that are similar within themselves
	const GROUP_BOOL = 1;			//               1
	const GROUP_NUM = 2;			//              10
	const GROUP_TEXT = 4;			//             100
	const GROUP_SET = 8;			//            1000
	const GROUP_TIME = 16;			//           10000
	const GROUP_OBJECT = 32;		//          100000

	private static $cutoff = 64;	//         1000000

	// mutually exclusive options, bitwise comparable to their groups above
	const BOOL_BOOL = 1;			//               1

	const NUM_TINYINT = 2;			//              10
	const NUM_SMALLINT = 34;		//          100010
	const NUM_MEDIUMINT = 65;		//         1000010
	const NUM_INTEGER = 130;		//        10000010
	const NUM_INT = 130;			//        10000010 - alias
	const NUM_BIGINT = 258;			//       100000010
	const NUM_FLOAT = 514;			//      1000000010
	const NUM_DOUBLE = 1026;		//     10000000010
	const NUM_DECIMAL = 2050;		//    100000000010
	const NUM_NUMERIC = 4098;		//   1000000000010

	const TEXT_TEXT = 4;			//             100
	const TEXT_CHAR = 36;			//          100100
	const TEXT_VARCHAR = 68;		//         1000100
	const TEXT_EMAIL = 132;			//        10000100

	const SET_ENUM = 8;				//            1000
	const SET_SET = 40;				//          101000

	const TIME_DATETIME = 16;		//           10000
	const TIME_DATE = 48;			//          110000
	const TIME_TIMESTAMP = 80;		//         1010000

	const OBJECT_OBJECT = 32;		//           10000

	public static function group_mask($type) {
		return $type % self::$cutoff;
	}

	public static function type_validate($data, $type) {
		$result = NULL;

		switch ($type) {
			case self::BOOL_BOOL:
				$result = !is_null(CK\fn::check_flag($data));
				break;
			case self::NUM_TINYINT:
			case self::NUM_SMALLINT:
			case self::NUM_MEDIUMINT:
			case self::NUM_INTEGER:
			case self::NUM_BIGINT:
				$result = $data == (int) $data;
				break;
			case self::NUM_FLOAT:
			case self::NUM_DOUBLE:
			case self::NUM_DECIMAL:
			case self::NUM_NUMERIC:
				$result = filter_var($data, FILTER_VALIDATE_FLOAT) !== FALSE;
				break;
			case self::TEXT_TEXT:
			case self::TEXT_CHAR:
			case self::TEXT_VARCHAR:
				$result = is_scalar($data);
				break;
			case self::TEXT_EMAIL:
				$result = filter_var($data, FILTER_VALIDATE_EMAIL);
				break;
			case self::SET_ENUM:
			case self::SET_SET:
				$result = is_array($data);
				break;
			case self::TIME_DATETIME:
			case self::TIME_DATE:
			case self::TIME_TIMESTAMP:
				try {
					if ($data instanceof DateTime) $result = TRUE;
					elseif (!self::isset_datetime($data)) $result = TRUE;
					else {
						$dt = new DateTime($data);
						$result = TRUE;
					}
				}
				catch (Exception $e) {
					$result = FALSE;
				}
				break;
			case self::OBJECT_OBJECT:
				$result = is_object($data);
				break;
			default:
				throw new CKDataTypesException('Urecognized data type');
				break;
		}

		return !empty($result);
	}

	public static function range_validate($data, $type, Array $range=[]) {
		$result = TRUE;

		if ($result) {
			switch ($type) {
				case self::NUM_TINYINT:
					if (!array_key_exists('min', $range)) $range['min'] = -128;
					if (!array_key_exists('max', $range)) $range['max'] = 127;
				case self::NUM_SMALLINT:
					if (!array_key_exists('min', $range)) $range['min'] = -32768;
					if (!array_key_exists('max', $range)) $range['max'] = 32767;
				case self::NUM_MEDIUMINT:
					if (!array_key_exists('min', $range)) $range['min'] = -8388608;
					if (!array_key_exists('max', $range)) $range['max'] = 8388607;
				case self::NUM_INTEGER:
					if (!array_key_exists('min', $range)) $range['min'] = -2147483648;
					if (!array_key_exists('max', $range)) $range['max'] = 2147483647;
				case self::NUM_BIGINT:
					if (!array_key_exists('min', $range)) $range['min'] = -9223372036854775808;
					if (!array_key_exists('max', $range)) $range['max'] = 9223372036854775807;

					$result = $result && self::within($data, $range['min'], $range['max']);
					break;
				case self::NUM_DECIMAL:
				case self::NUM_NUMERIC:
					$int = floor($data);
					$dec = $data - $int;
					if (array_key_exists('max', $range)) $result = $result && self::within(strlen($int) + strlen($dec), NULL, $range['max']);
					if (array_key_exists('dec', $range)) $result = $result && self::within(strlen($dec), NULL, $range['dec']);
					break;
				case self::TEXT_CHAR:
				case self::TEXT_VARCHAR:
					if (array_key_exists('max_length', $range)) $result = $result && self::within(strlen($data), NULL, $range['max_length']);
					if (array_key_exists('min_length', $range)) $result = $result && self::within(strlen($data), $range['min_length']);
					break;
			}
		}

		return $result;
	}

	private static function within($num, $min=NULL, $max=NULL) {
		$result = TRUE;

		if (!empty($min)) $result = $result && $num >= $min;
		if (!empty($max)) $result = $result && $num <= $max;

		return $result;
	}

	public static function coerce($data, $type) {
		switch (self::group_mask($type)) {
			case self::GROUP_BOOL:
				return CK\fn::check_flag($data);
				break;
			case self::GROUP_NUM:
				if (in_array($type, [self::NUM_TINYINT, self::NUM_SMALLINT, self::NUM_MEDIUMINT, self::NUM_INTEGER, self::NUM_BIGINT])) return (int) $data;
				else return (float) $data;
				break;
			case self::GROUP_TEXT:
				return (string) $data;
				break;
			case self::GROUP_SET:
				return is_array($data)?$data:[$data];
				break;
			case self::GROUP_TIME:
				if ($data instanceof DateTime) return $data;
				elseif (self::isset_datetime($data)) return new DateTime($data);
				else return NULL;
				break;
			case self::GROUP_OBJECT:
				$result = is_object($data)?$data:(object) $data;
				break;
			default:
				throw new CKDataTypesException('Urecognized data type');
				break;
		}
	}

	public static function isset_datetime($date_string) {
		$date_string = trim($date_string);
		return !empty($date_string)||!in_array($date_string, ['0000-00-00', '0000-00-00 00:00:00', '00:00:00']);
	}
}

class CKDataTypesException extends Exception {
}
?>
