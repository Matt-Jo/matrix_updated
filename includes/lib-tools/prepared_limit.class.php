<?php
class prepared_limit extends prepared_db {
	private $iteration = 0;
	private $start_point = 0;
	private $batch_size;

	public function __construct($start_point=NULL, $batch_size=NULL) {
		if (!empty($start_point)) $this->set_start_point($start_point);
		if (!empty($btach_size)) $this->set_batch_size($batch_size);
	}

	public function set_batch_size($batch_size) {
		if (!is_numeric($batch_size)) throw new PreparedLimitException('Limit does not support non-numeric batch sizes');
		$this->batch_size = $batch_size;
	}

	public function set_start_point($start_point) {
		if (!is_numeric($start_point)) throw new PreparedLimitException('Limit does not support non-numeric start points');
		$this->start_point = $start_point;
	}

	public function get_iteration_number() {
		return $this->iteration;
	}

	public function limit() {
		$limit = $this->start_point;

		if (!is_null($this->batch_size)) {
			$limit .= ', '.$this->batch_size;
			$this->start_point += $this->batch_size;
			$this->iteration++;
		}

		return $limit;
	}
}

class PreparedLimitException extends Exception {
}
?>
