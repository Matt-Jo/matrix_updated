<?php

class ck_custom_page extends ck_archetype {

	protected static $skeleton_type = 'ck_custom_page_type';

	protected static $queries = [
		'page_header' => [
			'qry' => 'SELECT page_id, page_title, page_code, product_id_list, sitewide_header, full_width, url, meta_description, visibility, archived, url_identifier FROM custom_pages WHERE page_id = :page_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		]
	];

	public function __construct($page_id, ck_custom_page_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($page_id);
		if (!$this->skeleton->built('page_id')) $this->skeleton->load('page_id', $page_id);
		self::register($page_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('page_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_header() {
		$this->skeleton->load('header', self::fetch('page_header', [':page_id' => $this->id()]));
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		return $this->skeleton->get('header',$key);
	}

	public static function get_all($key=NULL) {
		if (empty($key)) return self::query_fetch('SELECT page_id, page_title, page_code, product_id_list, sitewide_header, full_width, url, meta_description, visibility, url_identifier FROM custom_pages WHERE archived = 0', cardinality::SET, []);
		elseif ($key == 'active') return self::query_fetch('SELECT page_id, page_title, page_code, product_id_list, sitewide_header, full_width, url, meta_description, visibility, url_identifier FROM custom_pages WHERE archived = 0 AND visibility = 1', cardinality::SET, []);
	}

	public static function get_id_by_url($url, $url_identifier) {
		return self::query_fetch('SELECT page_id FROM custom_pages WHERE url = :url AND url_identifier = :url_identifier AND visibility = 1', cardinality::SINGLE, [':url' => $url, ':url_identifier' => $url_identifier]);
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public static function create(array $data) {
		$savepoint = prepared_query::transaction_begin();

		try {
			$params = new prepared_fields($data, prepared_fields::INSERT_QUERY);
			$params->whitelist(['page_title', 'page_code', 'product_id_list', 'sitewide_header', 'full_width', 'url', 'meta_description', 'visibility', 'archived', 'url_identifier']);
			prepared_query::insert('INSERT INTO custom_pages ('.$params->insert_fields().') VALUES ('.$params->insert_values().')', $params->insert_parameters());
		} catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKCustomPageException('Failed to create custom page '.$e->getMessage());
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint);
		}

		return TRUE;
	}

	public function archive() {
		$savepoint = self::transaction_begin();

		try {
			self::query_execute('UPDATE custom_pages SET archived = 1 WHERE page_id = :page_id', cardinality::NONE, [':page_id' => $this->id()]);
			self::transaction_commit($savepoint);
		} catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKCustomPageException('Failed to archive custom page '.$e->getMessage());
		}
	}

	public function update(array $data) {
		$savepoint = self::transaction_begin();

		try {
			$params = new ezparams($data);
			self::query_execute('UPDATE custom_pages SET '.$params->update_cols(TRUE).' WHERE page_id = :page_id', cardinality::NONE, $params->query_vals(['page_id' => $this->id()], TRUE));
			self::transaction_commit($savepoint);
			return TRUE;
		} catch (Exception $e) {
			self:: transaction_rollback($savepoint);
			throw new CKCustomPageException('Failed to update custom page '.$e->getMessage());
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/
}

class CKCustomPageException extends CKMasterArchetypeException { }
?>
