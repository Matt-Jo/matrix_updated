<?php
class spreadsheet_import implements IteratorAggregate {

	private $iterator;

	private $file_path;
	private $file_type;

	private $opts = [];
	private $errors = [];

	private $spreadsheet;

	public function __construct($file, $opts=[]) {
		//ini_set('memory_limit', '512M');
		//ini_set('max_execution_time', 300);

		$file_details = pathinfo($file['name']);
		$this->file_path = $file['tmp_name'];
		$this->file_type = strtolower($file_details['extension']);

		if (!file_exists($this->file_path) || !is_readable($this->file_path)) { //if (($file = fopen($this->file_path, 'r')) === FALSE) {
			$this->errors[] = 'There was a problem opening the uploaded file for reading';
			return FALSE;
		}

		if (!empty($opts)) $this->options($opts);
	}

	public function has_errors() {
		return !empty($this->errors);
	}

	public function get_errors() {
		if (!$this->has_errors()) return [];
		else return $this->errors;
	}

	public function clear_errors() {
		$this->errors = [];
	}

	public function options($opts=[]) {
		if (!empty($opts)) $this->opts = $opts;
		return $this->opts;
	}

	public function getIterator() {
		if (!empty($this->iterator)) return $this->iterator;

		if ($this->file_type == 'csv') {
			$this->spreadsheet = new _si_csv($this->file_path, $this->opts);
			return $this->iterator = $this->spreadsheet;
		}
		else {
			$this->spreadsheet = Akeneo\Component\SpreadsheetParser\SpreadsheetParser::open($this->file_path, $this->file_type);

			$worksheet_index = !empty($this->opts['worksheet_index'])?$this->opts['worksheet_index']:0;
			if (!empty($this->opts['worksheet_name'])) $worksheet_index = $this->spreadsheet->getWorksheetIndex($this->opts['worksheet_name']);

			return $this->iterator = $this->spreadsheet->createRowIterator($worksheet_index);
		}
	}
}

class _si_csv implements Iterator {
	private $path;
	private $fh;

	private $current_row;
	private $current_data;
	private $current_valid;

	private $opts;
	private $defaults = [
		'length' => 0,
		'delimiter' => ',',
		'enclosure' => '"',
		'escape' => '\\'
	];

	public function __construct($path, $opts=[]) {
		$this->path = $path;
		$this->opts = array_merge($this->defaults, $opts);

		$this->fh = fopen($this->path, 'r');
	}

	public function current() {
		return $this->current_data;
	}

	public function key() {
		return $this->current_row;
	}

	public function next() {
		do {
			$this->current_data = fgetcsv($this->fh, $this->opts['length'], $this->opts['delimiter'], $this->opts['enclosure'], $this->opts['escape']);
			$this->current_row++;
			$this->current_valid = ($this->current_data !== FALSE);
			if ($this->current_valid) {
				$this->current_data = array_map('trim', $this->current_data);
				$empty_row = empty(array_filter($this->current_data));
			}
		}
		while ($this->current_valid && $empty_row);

		//echo '['.$this->current_row.']';
	}

	public function rewind() {
		rewind($this->fh);
		$this->current_row = 0;
		$this->next();
	}

	public function valid() {
		return $this->current_valid;
	}
}

class SpreadsheetImportException extends Exception {
}
?>
