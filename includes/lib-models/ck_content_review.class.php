<?php
class ck_content_review extends ck_archetype {

	protected static $skeleton_type = 'ck_content_review_type';

	public static $status = [
		0 => 'No Issues Found',
		1 => 'Issue(s) found',
		2 => 'Fixed',
		3 => 'Will Not Fix'
	];

	protected static $queries = [
		'content_review_header' => [
			'qry' => 'SELECT cr.id as content_review_id, cr.product_id as products_id, cr.ipn_id as stock_id, cr.element_id, cre.name as element, cr.image_slot, cr.reason_id, crr.name as reason, cr.status, cr.notes, a1.admin_id as requester_admin_id, a1.admin_email_address as requester_email_address, a1.admin_firstname as requester_firstname, a1.admin_lastname as requester_lastname, a2.admin_id as responder_admin_id, a2.admin_email_address as responder_email_address, a2.admin_firstname as responder_firstname, a2.admin_lastname as responder_lastname, cr.notice_date, cr.response_date FROM content_reviews cr LEFT JOIN content_review_elements cre ON cr.element_id = cre.id LEFT JOIN content_review_reasons crr ON cr.reason_id = crr.id LEFT JOIN admin a1 ON cr.admin_id = a1.admin_id LEFT JOIN admin a2 ON cr.responder_id = a2.admin_id WHERE cr.id = :content_review_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL,
		],

		'content_review_list_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT DISTINCT',

				'from' => 'FROM content_reviews cr LEFT JOIN content_review_elements cre ON cr.element_id = cre.id LEFT JOIN content_review_reasons crr ON cr.reason_id = crr.id LEFT JOIN admin a1 ON cr.admin_id = a1.admin_id LEFT JOIN admin a2 ON cr.responder_id = a2.admin_id LEFT JOIN products_stock_control psc ON cr.ipn_id = psc.stock_id LEFT JOIN serials s ON psc.stock_id = s.ipn AND s.status IN (2, 3, 6)',

				//'where' => 'WHERE' // will *not* fail if we don't provide our own

				'order_by' => 'ORDER BY cr.notice_date ASC'
			],
			'proto_opts' => [
				':status' => 'cr.status = :status',
				':reason_id' => 'cr.reason_id = :reason_id',
				':categories_id' => 'psc.products_stock_control_category_id = :categories_id',
				':drop_ship' => 'psc.drop_ship = :drop_ship',
				':in_stock' => '(:in_stock AND (psc.serialized = 0 AND psc.stock_quantity > 0) OR (psc.serialized = 1 AND s.id IS NOT NULL))',
				':discontinued' => 'psc.discontinued != :discontinued',
				':ipn' => 'psc.stock_name LIKE :ipn'
			],
			'proto_defaults' => [
				'data_operation' => 'cr.id as content_review_id, cr.product_id as products_id, cr.ipn_id as stock_id, cr.element_id, cre.name as element, cr.image_slot, cr.reason_id, crr.name as reason, cr.status, cr.notes, a1.admin_id as requester_admin_id, a1.admin_email_address as requester_email_address, a1.admin_firstname as requester_firstname, a1.admin_lastname as requester_lastname, a2.admin_id as responder_admin_id, a2.admin_email_address as responder_email_address, a2.admin_firstname as responder_firstname, a2.admin_lastname as responder_lastname, cr.notice_date, cr.response_date'
			],
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],
	];

	public function __construct($content_review_id, ck_content_review_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($content_review_id);

		if (!$this->skeleton->built('content_review_id')) $this->skeleton->load('content_review_id', $content_review_id);
		if ($this->skeleton->built('header')) $this->normalize_header();

		self::register($content_review_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('content_review_id');
	}

	public function found() {
		return !empty($this->get_header());
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('content_review_header', [':content_review_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		if (!empty($header['notice_date'])) $header['notice_date'] = self::DateTime($header['notice_date']);
		if (!empty($header['response_date'])) $header['response_date'] = self::DateTime($header['response_date']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$header = self::fetch('content_review_header', [':content_review_id' => $this->id()]);
		if ($header) {
			$this->skeleton->load('header', $header);
			$this->normalize_header();
		}
	}

	private function build_listing() {
		if ($products_id = $this->get_header('products_id')) $this->skeleton->load('listing', new ck_product_listing($products_id));
	}

	private function build_ipn() {
		if ($stock_id = $this->get_header('stock_id')) $this->skeleton->load('ipn', new ck_ipn2($stock_id));
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header',$key);
	}

	public function has_listing() {
		if (!$this->skeleton->built('listing')) $this->build_listing();
		return $this->skeleton->has('listing');
	}

	public function get_listing() {
		if (!$this->has_listing()) return NULL;
		return $this->skeleton->get('listing');
	}

	public function has_ipn() {
		if (!$this->skeleton->built('ipn')) $this->build_ipn();
		return $this->skeleton->has('ipn');
	}

	public function get_ipn() {
		if (!$this->has_ipn()) return NULL;
		return $this->skeleton->get('ipn');
	}

	public static function get_content_reviews_by_field_lookup($fields) {
		$query_adds = ['where' => ''];

		$clauses = self::$queries['content_review_list_proto']['proto_opts'];

		$wheres = [];

		foreach ($fields as $field => $value) {
			if (!empty($clauses[$field])) $wheres[] = $clauses[$field];
		}

		if (!empty($wheres)) $query_adds['where'] = 'WHERE '.implode(' AND ', $wheres);

		$qry = self::modify_query('content_review_list_proto', $query_adds);

		//echo self::$queries[$qry]['qry'];
		//var_dump($fields);

		if ($headers = self::fetch($qry, $fields)) {
			$content_reviews = [];
			foreach ($headers as $header) {
				$skeleton = self::get_record($header['content_review_id']); // if we've already instantiated it, well, oh well
				if (!$skeleton->built('header')) $skeleton->load('header', $header);

				$content_reviews[] = new self($header['content_review_id'], $skeleton);
			}
			return $content_reviews;
		}
		else return [];
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public static function create($data) {
		self::transaction_begin();

		if ($data['status'] == 1) {
			if ($crid = self::query_fetch('SELECT id FROM content_reviews WHERE ipn_id = :stock_id AND status = 1 AND element_id = 1', cardinality::SINGLE, [':stock_id' => $data['stock_id']])) {
				$cr = new self($crid);
				return $cr;
			}
		}

		try {
			$header = CK\fn::parameterize($data);
			self::query_execute('INSERT INTO content_reviews (notice_date, admin_id, product_id, ipn_id, element_id, image_slot, reason_id, status, notes) VALUES (NOW(), :admin_id, :products_id, :stock_id, 1, :image_slot, :reason_id, :status, :notes)', cardinality::NONE, $header);
			$content_review_id = self::fetch_insert_id();

			$cr = new self($content_review_id);

			self::transaction_commit();
			return $cr;
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update_status($status) {
		if (empty($status) || empty(self::$status[$status])) throw new CKContentReviewException('Content Review status ID ['.$status.'] does not exist.');
		self::query_execute('UPDATE content_reviews SET status = :status, responder_id = :responder_admin_id, response_date = NOW() WHERE id = :content_review_id', cardinality::NONE, [':status' => $status, ':responder_admin_id' => $_SESSION['perms']['admin_id'], ':content_review_id' => $this->id()]);
		$this->skeleton->rebuild('header');
	}

	public function update_reason($reason_id) {
		if (empty($reason_id)) throw new CKContentReviewException('Content Review reason ID is empty');
		self::query_execute('UPDATE content_reviews SET reason_id = :reason_id WHERE id = :content_review_id', cardinality::NONE, [':reason_id' => $reason_id, ':content_review_id' => $this->id()]);
		$this->skeleton->rebuild('header');
	}
}

class CKContentReviewException extends Exception {
}
?>