<?php
class ck_vendor_rfq extends ck_archetype {

	protected static $skeleton_type = 'ck_vendor_rfq_type';

	protected static $queries = [
		'rfq_header' => [
			'qry' => 'SELECT r.rfq_id, r.nickname, r.admin_id, a.admin_email_address as admin_email, r.request_type, r.subject_line, r.request_details, r.published_date, r.expiration_date, r.active, r.created_date, r.full_email_text FROM ck_rfqs r JOIN admin a ON r.admin_id = a.admin_id WHERE rfq_id = :rfq_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

	];

	public function __construct($rfq_id, ck_vendor_rfq_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($rfq_id);

		if (!$this->skeleton->built('rfq_id')) $this->skeleton->load('rfq_id', $rfq_id);

		self::register($rfq_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('rfq_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('rfq_header', [':rfq_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$date_fields = ['published_date', 'expiration_date', 'created_date'];
		foreach ($date_fields as $field) {
			$header[$field] = self::DateTime($header[$field]);
		}

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('rfq_header', [':rfq_id' => $this->id()]));
		$this->normalize_header();
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	/*-------------------------------
	// modify data
	-------------------------------*/
}
?>