<?php
class ck_data_loader {
	public static function load($format, &$data_store, $element, $data) {
		switch ($format['cardinality']) {
			case cardinality::SINGLE:
				$data_store[$element] = self::load_single($data, $format);
				break;
			case cardinality::COLUMN:
				$data_store[$element] = self::load_column($data, $format);
				break;
			case cardinality::ROW:
				$data_store[$element] = self::load_row($data, $format);
				break;
			case cardinality::SET:
				$data_store[$element] = self::load_set($data, $format);
				break;
			case cardinality::MAP:
				$data_store[$element] = self::load_map($data, $format);
				break;
		}
	}

	private static function load_single($data, $format) {
		if (is_null($data)) return NULL;

		if (empty($format['loose']) && is_array($data)) throw new CKDataLoaderException('Array data is not valid for SINGLE data types.');

		if (!empty($format['data_type']) && !self::validate($data, $format)) throw new CKDataLoaderException('Data is not the specified data type.');

		return $data;
	}

	private static function load_column($data, $format) {
		if (is_null($data)) return [];

		if (empty($format['loose']) && !is_array($data)) throw new CKDataLoaderException('Array data is required for COLUMN data types.');

		if (!empty($format['data_type']) && !empty(array_filter($data, function($el) use ($format) { return !self::validate($el, $format); }))) throw new CKDataLoaderException('Data is not the specified data type.');

		return $data;
	}

	private static function load_row($data, $format) {
		if (is_null($data)) return [];

		if (empty($format['loose']) && !is_array($data)) throw new CKDataLoaderException('Array data is required for ROW data types.');

		return !empty($format['columns'])?self::normalize_row($data, $format['columns']):$data;
	}

	private static function load_set($data, $format) {
		if (is_null($data)) return [];

		if (empty($format['loose']) && !is_array($data)) throw new CKDataLoaderException('Array data is required for SET data types.');

		$return_data = [];

		if (!empty($format['columns'])) {
			foreach ($data as $row_key => $row) {
				if (!empty($format['keyed_set']) && !empty($format['key_column']) && !is_null($row[$format['key_column']])) $return_data[$row[$format['key_column']]] = self::normalize_row($row, $format['columns']);
				elseif (!empty($format['keyed_set']) && empty($format['key_column'])) $return_data[$row_key] = self::normalize_row($row, $format['columns']);
				else $return_data[] = self::normalize_row($row, $format['columns']);
			}
		}
		else $return_data = $data;

		return $return_data;
	}

	private static function load_map($data, $format) {
		if (is_null($data)) return [];

		if (empty($format['loose']) && !is_array($data)) throw new CKDataLoaderException('Array data is required for MAP data types.');

		$return_data = [];

		if (!empty($format['sets'])) {
			foreach ($format['sets'] as $set => $specs) {
				self::load($specs, $return_data, $set, array_key_exists($set, $data)?$data[$set]:NULL);
			}
		}
		elseif (!empty($format['columns'])) {
			foreach ($data as $set => $row_data) {
				if ($format['set_cardinality'] == cardinality::ROW) {
					$return_data[$set] = self::normalize_row($row_data, $format['columns']);
				}
				elseif ($format['set_cardinality'] == cardinality::SET) {
					$return_set = [];

					foreach ($row_data as $row_key => $row) {
						if (!empty($format['keyed_set']) && !empty($format['key_column']) && !is_null($row[$format['key_column']])) $return_set[$row[$format['key_column']]] = self::normalize_row($row, $format['columns']);
						elseif (!empty($format['keyed_set']) && empty($format['key_column'])) $return_set[$row_key] = self::normalize_row($row, $format['columns']);
						else $return_set[] = self::normalize_row($row, $format['columns']);
					}

					$return_data[$set] = $return_set;
				}
			}
		}
		else $return_data = $data;

		return $return_data;
	}

	public static function change($format, &$data_store, $element, $data) {
		$changes = [];

		switch ($format['cardinality']) {
			case cardinality::SINGLE:
				if (!isset($data_store[$element])) $data_store[$element] = NULL;
				$changes = self::change_single($data_store[$element], $data, $format);
				break;
			case cardinality::COLUMN:
				if (!isset($data_store[$element])) $data_store[$element] = [];
				$changes = self::change_column($data_store[$element], $data, $format);
				break;
			case cardinality::ROW:
				if (!isset($data_store[$element])) $data_store[$element] = [];
				$changes = self::change_row($data_store[$element], $data, $format);
				break;
			case cardinality::SET:
				if (!isset($data_store[$element])) $data_store[$element] = [];
				$changes = self::change_set($data_store[$element], $data, $format);
				break;
			case cardinality::MAP:
				if (!isset($data_store[$element])) $data_store[$element] = [];
				$changes = self::change_map($data_store[$element], $data, $format);
				break;
		}

		return $changes;
	}

	private static function change_single(&$element, $data, $format) {
		$changes = TRUE;

		if ($element === $data) return $changes = FALSE;

		if (is_null($data)) {
			$element = NULL;
			return $changes;
		}

		if (empty($format['loose']) && is_array($data)) throw new CKDataLoaderException('Array data is not valid for SINGLE data types.');

		if (!empty($format['data_type']) && !self::validate($data, $format)) throw new CKDataLoaderException('Data is not the specified data type.');

		$element = $data;
		return $changes;
	}

	private static function change_column(&$element, $data, $format) {
		$changes = TRUE;

		if ($element === $data) return $changes = FALSE;

		if (is_null($data)) {
			$element = [];
			return $changes;
		}

		if (empty($format['loose']) && !is_array($data)) throw new CKDataLoaderException('Array data is required for COLUMN data types.');

		if (!empty($format['data_type']) && !empty(array_filter($data, function($el) use ($format) { return !self::validate($el, $format); }))) throw new CKDataLoaderException('Data is not the specified data type.');

		$element = $data;
		return $changes;
	}

	private static function change_row(&$element, $data, $format) {
		$changes = [];

		if ($element === $data) return $changes;

		if (is_null($data)) {
			$changes = array_keys($element);
			$element = [];
			return $changes;
		}

		if (empty($format['loose']) && !is_array($data)) throw new CKDataLoaderException('Array data is required for ROW data types.');

		if (!empty($format['columns'])) {
			$check_keys = array_keys(array_intersect_key($element, $data)); // these are the keys that have potentially changed
			foreach ($check_keys as $key) {
				// remove all unchanged keys from $data
				if ($element[$key] === $data[$key]) unset($data[$key]);
			}
			$changes = array_keys($data); // the changes are what's left

			$data = array_merge($element, $data); // if we're missing any columns, fill them out with the previous data but keep the keys we're passing in
			$element = self::normalize_row($data, $format['columns']);
		}
		else {
			$changes = array_merge(array_keys($element), array_keys($data));
			$element = $data;
		}

		return $changes;
	}

	private static function change_set(&$element, $data, $format) {
		$changes = [];

		if ($element === $data) return $changes;

		if (is_null($data)) {
			$changes = array_keys($element);
			$element = [];
			return $changes;
		}

		if (empty($format['loose']) && !is_array($data)) throw new CKDataLoaderException('Array data is required for SET data types.');

		if (!empty($format['columns'])) {
			$check_rows = array_keys(array_intersect_key($element, $data)); // these are the keys that have potentially changed
			foreach ($check_rows as $row_key) {
				// remove all unchanged keys from $data
				if ($element[$row_key] === $data[$row_key]) unset($data[$row_key]);
				else {
					$changes[$row_key] = [];

					$check_columns = array_keys(array_intersect_key($element[$row_key], $data[$row_key]));
					foreach ($check_columns as $column) {
						if ($element[$row_key][$column] === $data[$row_key][$column]) unset($data[$row_key][$column]);
						else $changes[$row_key][] = $column;
					}
				}

				// what we're left with is just the elements of data that have changed, and we can merge in the unchanged from element
				$data[$row_key] = array_merge($element[$row_key], $data[$row_key]);
			}

			// we don't need missing rows, we can just manage the new or changed rows - if we deleted a row, it's key was specified with blank data
			//$data = array_merge($element, $data);

			foreach ($data as $row_key => $row) {
				if (!empty($format['keyed_set']) && !empty($format['key_column']) && isset($row[$format['key_column']]) && !is_null($row[$format['key_column']])) $element[$row[$format['key_column']]] = self::normalize_row($row, $format['columns']);
				elseif ((!empty($format['keyed_set']) && (empty($format['key_column']) || !isset($row[$format['key_column']]))) || array_key_exists($row_key, $element)) $element[$row_key] = self::normalize_row($row, $format['columns']);
				else $element[] = self::normalize_row($row, $format['columns']);

				if (empty($changes[$row_key])) $changes[$row_key] = array_keys($row);
			}
		}
		else {
			$changes = array_merge(array_keys($element), array_keys($data));
			$element = $data;
		}

		return $changes;
	}

	private static function change_map(&$element, $data, $format) {
		$changes = [];

		if ($element === $data) return $changes;

		if (is_null($data)) {
			$changes = array_keys($element);
			$element = [];
			return $changes;
		}

		if (empty($format['loose']) && !is_array($data)) throw new CKDataLoaderException('Array data is required for MAP data types.');

		if (!empty($format['sets'])) {
			foreach ($format['sets'] as $set => $specs) {
				if ($ch = self::change($specs, $element, $set, array_key_exists($set, $data)?$data[$set]:NULL)) $changes[$set] = $ch;
			}
		}
		elseif (!empty($format['columns'])) {
			$check_sets = array_keys(array_intersect_key($element, $data)); // these are the sets that have potentially changed
			foreach ($check_sets as $set) {
				// remove all unchanged keys from $data
				if ($element[$set] === $data[$set]) unset($data[$set]);
				else {
					$changes[$set] = [];

					$check_rows = array_keys(array_intersect_key($element[$set], $data[$set]));
					foreach ($check_rows as $row_key) {
						if ($element[$set][$row_key] === $data[$set][$row_key]) unset($data[$set][$row_key]);
						else {
							if ($format['set_cardinality'] == cardinality::ROW) $changes[$set][] = $row_key;
							elseif ($format['set_cardinality'] == cardinality::SET) {
								$changes[$set][$row_key] = [];

								$check_columns = array_keys(array_intersect_key($element[$set][$row_key], $data[$set][$row_key]));
								foreach ($check_columns as $column) {
									if ($element[$set][$row_key][$column] === $data[$set][$row_key][$column]) unset($data[$set][$row_key][$column]);
									else $changes[$set][$row_key][] = $column;
								}
							}
						}

						// what we're left with is just the elements of data that have changed, and we can merge in the unchanged from element
						$data[$set][$row_key] = array_merge($element[$set][$row_key], $data[$set][$row_key]);
					}
				}

				// what we're left with is just the sets of data that have changed, and we can merge in the unchanged from element
				$data[$set] = array_merge($element[$set], $data[$set]);
			}

			// we don't need missing sets, we can just manage the new or changed sets - if we deleted a set, it's key was specified with blank data
			//$data = array_merge($element, $data);

			foreach ($data as $set => $row_data) {
				if (empty($changes[$set])) $changes[$set] = array_keys($row_data);

				if ($format['set_cardinality'] == cardinality::ROW) {
					$element[$set] = self::normalize_row($row_data, $format['columns']);
				}
				elseif ($format['set_cardinality'] == cardinality::SET) {
					foreach ($row_data as $row_key => $row) {
						if (!empty($format['keyed_set']) && !empty($format['key_column']) && !is_null($row[$format['key_column']])) $element[$set][$row[$format['key_column']]] = self::normalize_row($row, $format['columns']);
						elseif ((!empty($format['keyed_set']) && empty($format['key_column'])) || array_key_exists($row_key, $element[$set])) $element[$set][$row_key] = self::normalize_row($row, $format['columns']);
						else $element[$set][] = self::normalize_row($row, $format['columns']);

						if (empty($changes[$set][$row_key])) $changes[$set][$row_key] = array_keys($row);
					}
				}
			}
		}
		else {
			$changes = array_merge(array_keys($element), array_keys($data));
			$element = $data;
		}

		return $changes;
	}

	private static function normalize_row($row, $column_format) {
		$return_row = [];

		foreach ($column_format as $column => $specs) {
			$exists = array_key_exists($column, $row);
			if (CK\fn::check_flag(@$specs['optional']) && !$exists) continue;

			if (!$exists) {
				if (!empty($specs['default'])) $return_row[$column] = $specs['default'];
				elseif (!empty($specs['not_null'])) throw new CKDataLoaderException('Value cannot be null.');
				else $return_row[$column] = NULL;
			}
			elseif (!empty($specs['format']) && !self::validate($row[$column], $specs['format'])) throw new CKDataLoaderException('Value does not match format: [column: '.$column.']');
			else $return_row[$column] = $row[$column];
		}

		return $return_row;
	}

	public static function validate(&$data, $format) {
		if (!data_types::type_validate($data, $format['data_type'])) {
			//echo 'loser1';
			return FALSE;
		}

		if (!empty($format['range']) && !data_types::range_validate($data, $format['data_type'], $format['range'])) {
			//echo 'loser2';
			return FALSE;
		}

		switch ($format['data_type']) {
			case data_types::OBJECT_OBJECT:
				if (!empty($format['class'])) {
					if (!($data instanceof $format['class'])) {
						//var_dump($data, $format['class']);
						//echo 'loser3';
						return FALSE;
					}
				}
				break;
		}

		if (!empty($format['coerce']) && in_array(data_types::group_mask($format['data_type']), [data_types::GROUP_BOOL, data_types::GROUP_TIME])) $data = data_types::coerce($data, $format['data_type']);

		return TRUE;
	}
}

class CKDataLoaderException extends Exception {
}
?>
