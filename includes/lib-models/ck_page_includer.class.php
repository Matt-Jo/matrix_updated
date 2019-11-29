<?php
// needs to extend a page data archetype, which acts as a singleton since there's only one homepage
class ck_page_includer extends ck_archetype {

	protected static $skeleton_type = 'ck_page_includer_type';

	protected static $queries = [
		'get_all' => [
			'qry' => 'SELECT page_includer_id FROM ck_page_includers',
			'cardinality' => cardinality::COLUMN,
			'stmt' => NULL
		],

		'page_includer_header' => [
			'qry' => 'SELECT page_includer_id, label, target, page_height, date_created, date_updated FROM ck_page_includers WHERE page_includer_id = :page_includer_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'request_maps' => [
			'qry' => 'SELECT page_includer_request_map_id, request, date_created, date_updated FROM ck_page_includer_request_maps WHERE page_includer_id = :page_includer_id ORDER BY page_includer_request_map_id ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'target_by_request' => [
			'qry' => 'SELECT pi.target, pi.page_height FROM ck_page_includers pi JOIN ck_page_includer_request_maps pirm ON pi.page_includer_id = pirm.page_includer_id WHERE pirm.request LIKE :request',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		'create_page_includer' => [
			'qry' => 'INSERT INTO ck_page_includers (label, target, page_height, date_updated) VALUES (:label, :target, :page_height, NOW())',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'create_request_map' => [
			'qry' => 'INSERT INTO ck_page_includer_request_maps (page_includer_id, request, date_updated) VALUES (:page_includer_id, :request, NOW())',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'remove_request_map' => [
			'qry' => 'DELETE FROM ck_page_includer_request_maps WHERE page_includer_id = :page_includer_id AND (page_includer_request_map_id = :page_includer_request_map_id OR request LIKE :request)',
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		]
	];

	public function __construct($page_includer_id, ck_page_includer_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($page_includer_id);

		if (!$this->skeleton->built('page_includer_id')) $this->skeleton->load('page_includer_id', $page_includer_id);

		self::register($page_includer_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('page_includer_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('page_includer_header', [':page_includer_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$header['date_created'] = self::DateTime($header['date_created']);
		if (!empty($header['date_updated'])) $header['date_updated'] = self::DateTime($header['date_updated']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$header = self::fetch('page_includer_header', [':page_includer_id' => $this->id()]);
		if ($header) {
			$this->skeleton->load('header', $header);
			$this->normalize_header();
		}
	}

	private function build_request_maps() {
		$request_maps = self::fetch('request_maps', [':page_includer_id' => $this->id()]);

		foreach ($request_maps as &$map) {
			$map['date_created'] = self::DateTime($map['date_created']);
			if (!empty($map['date_updated'])) $map['date_updated'] = self::DateTime($map['date_updated']);
		}

		$this->skeleton->load('request_maps', $request_maps);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function get_request_maps($key=NULL) {
		if (!$this->skeleton->built('request_maps')) $this->build_request_maps();
		if (empty($key)) return $this->skeleton->get('request_maps');
		else {
			foreach ($this->skeleton->get('request_maps') as $map) {
				if (is_numeric($key) && $map['page_includer_request_map_id'] == $key) return $map;
				if (!is_numeric($key) && strtolower($map['request']) == strtolower($key)) return $map;
			}
			return NULL;
		}
	}

	public function get_url() {
		return '/pi/'.CK\fn::simple_seo($this->get_request_maps()[0], '/'.$this->id());
	}

	public static function get_target_by_request($request) {
		return self::fetch('target_by_request', [':request' => $request]);
	}

	public static function get_all() {
		$page_includers = [];
		if ($page_includer_ids = self::fetch('get_all', [])) {
			foreach ($page_includer_ids as $page_includer_id) {
				$page_includers[] = new self($page_includer_id);
			}
		}
		return $page_includers;
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public static function create($data) {
		self::transaction_begin();

		try {
			$header = CK\fn::parameterize($data['header']);
			self::execute('create_page_includer', $header);
			$page_includer_id = self::fetch_insert_id();

			$page_includer = new self($page_includer_id);

			foreach ($data['requests'] as $request) {
				$page_includer->add_map($request);
			}

			self::transaction_commit();
			return $page_includer;
		}
		catch (Exception $e) {
			self::transaction_rollback();
			throw $e;
		}
	}

	public function update($header) {
		$data = [];
		$qry = 'UPDATE ck_page_includers SET';
		$fields = [];
		if (isset($header['label'])) {
			$data[':label'] = $header['label'];
			$fields[] = 'label = :label';
		}
		if (isset($header['target'])) {
			$data[':target'] = $header['target'];
			$fields[] = 'target = :target';
		}
		if (isset($header['page_height'])) {
			$data[':page_height'] = $header['page_height'];
			$fields[] = 'page_height = :page_height';
		}
		if (!empty($data)) {
			$fields[] = 'date_updated = NOW()';

			$qry .= ' '.implode(', ', $fields).' WHERE page_includer_id = :page_includer_id';

			$data[':page_includer_id'] = $this->id();

			self::query_execute($qry, cardinality::NONE, $data);
		}
	}

	public function add_map($request) {
		$request = trim($request);
		if (empty($request)) return NULL;
		self::execute('create_request_map', [':page_includer_id' => $this->id(), ':request' => $request]);
		$this->skeleton->rebuild('request_maps');
		return self::fetch_insert_id();
	}

	public function remove_map($page_includer_request_map_id, $request) {
		self::execute('remove_request_map', [':page_includer_id' => $this->id(), ':page_includer_request_map_id' => $page_includer_request_map_id, ':request' => $request]);
		$this->skeleton->rebuild('request_maps');
	}
}

//CREATE TABLE ck_page_includers ( page_includer_id INT NOT NULL AUTO_INCREMENT , label VARCHAR(64) NOT NULL , target TEXT NOT NULL , date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , date_updated DATETIME NULL DEFAULT NULL , PRIMARY KEY (page_includer_id)) ENGINE = InnoDB;
//CREATE TABLE ck_page_includer_request_maps ( page_includer_request_map_id INT NOT NULL AUTO_INCREMENT , page_includer_id INT NOT NULL , request INT NOT NULL , date_created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , date_updated DATETIME NULL DEFAULT NULL , PRIMARY KEY (page_includer_request_map_id)) ENGINE = InnoDB;
?>
