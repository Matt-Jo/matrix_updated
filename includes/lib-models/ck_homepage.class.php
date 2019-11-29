<?php
// needs to extend a page data archetype, which acts as a singleton since there's only one homepage
class ck_homepage extends ck_archetype {

	protected static $skeleton_type = 'ck_homepage_type';

	public static $static_url = '//media.cablesandkits.com/static';

	protected static $queries = [
		'homepage_elements' => [
			'qry' => 'SELECT * FROM ck_site_homepage WHERE active = 1 AND archived = 0 ORDER BY element, sort_order ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'all_homepage_elements' => [
			'qry' => 'SELECT * FROM ck_site_homepage WHERE archived = 0 ORDER BY created_date DESC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'update_element_proto' => [
			'proto_qry' => [
				'data_operation' => 'UPDATE ck_site_homepage',

				'set' => 'SET', // will fail if we don't provide our own

				'where' => 'WHERE', // will fail if we don't provide our own
			],
			'proto_opts' => [
				':element' => 'element = :element',
				':sort_order' => 'sort_order = :sort_order',
				':img_src' => 'img_src = :img_src',
				':alt_text' => 'alt_text = :alt_text',
				':link_target_type' => 'link_target_type = :link_target_type',
				':link_target' => 'link_target = :link_target',
				':active' => 'active = :active',
				':archived' => 'archived = :archived',
				':site_homepage_id' => 'site_homepage_id = :site_homepage_id',
				':html' => 'html = :html',
				':title' => 'title = :title',
				':product_ids' => 'product_ids = :product_ids'
			],
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],

		'create_element_proto' => [
			'proto_qry' => [
				'data_operation' => 'INSERT INTO ck_site_homepage',

				'values' => 'VALUES', // will fail if we don't provide our own
			],
			'proto_opts' => [
				':element' => 'element',
				':sort_order' => 'sort_order',
				':img_src' => 'img_src',
				':alt_text' => 'alt_text',
				':link_target_type' => 'link_target_type',
				':link_target' => 'link_target',
				':active' => 'active',
				':html' => 'html',
				':title' => 'title',
				':product_ids' => 'product_ids'
			],
			'cardinality' => cardinality::NONE,
			'stmt' => NULL
		],
	];

	public function __construct(ck_homepage_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record(1);

		self::register(1, $this->skeleton);
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_all_elements() {
		$elements_raw = self::fetch('all_homepage_elements', []);

		$elements = ['rotator' => [], 'kickers' => [], 'showcases' => []];

		foreach ($elements_raw as $element) {
			$element['created_date'] = new DateTime($element['created_date']);
			$ref = parse_url($element['img_src']);
			$element['absolute_img_ref'] = !empty($ref['host'])?$element['img_src']:self::$static_url.$element['img_src'];
			$elements[$element['element']][] = $element;
		}

		usort($elements['rotator'], [__CLASS__, 'sort_elements']);
		usort($elements['kickers'], [__CLASS__, 'sort_elements']);
		usort($elements['showcases'], [__CLASS__, 'sort_elements']);

		$this->skeleton->load('rotator', $elements['rotator']);
		$this->skeleton->load('kickers', $elements['kickers']);
		$this->skeleton->load('showcases', $elements['showcases']);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function has_rotator() {
		if (!$this->skeleton->built('rotator')) $this->build_all_elements();
		return $this->skeleton->has('rotator');
	}

	public function get_rotator() {
		if (!$this->has_rotator()) return [];
		return $this->skeleton->get('rotator');
	}

	public function has_kickers() {
		if (!$this->skeleton->built('kickers')) $this->build_all_elements();
		return $this->skeleton->has('kickers');
	}

	public function get_kickers() {
		if (!$this->has_kickers()) return [];
		return $this->skeleton->get('kickers');
	}

	public function has_showcases($key=null) {
		if (!$this->skeleton->built('showcases')) $this->build_all_elements();
		if (!empty($key)) {
			if ($key == 'active' && $this->skeleton->has('showcases')) {
				foreach ($this->get_showcases() as $showcase) {
					if ($showcase['active'] == 1) return TRUE;
				}
				return FALSE;
			}
		}
		return $this->skeleton->has('showcases');
	}

	public function get_showcases($key=NULL) {
		if (!empty($key)) {
			if ($key=='active') {
				$active = [];
				foreach ($this->get_showcases() as $showcase) {
					if ($showcase['active'] == 1) $active[] = $showcase;
				}
				return $active;
			}
		}
		if (!$this->has_showcases()) return [];
		return $this->skeleton->get('showcases');
	}

	public static function is_image_fully_qualified($element) {
		// this is a little kludgy, but making it static and passing the element in is nicer than looping through each element in turn
		// or, alternatively, reworking the entire class to be able to pinpoint a given element (which may come at some point but it's not necessary today 8/1/2016)
		return $element['img_src'] === $element['absolute_img_ref'];
	}

	public static function is_link_fully_qualified($element) {
		// this is a little kludgy, but making it static and passing the element in is nicer than looping through each element in turn
		// or, alternatively, reworking the entire class to be able to pinpoint a given element (which may come at some point but it's not necessary today 8/1/2016)
		$ref = parse_url($element['link_target']);
		return !empty($ref['host']);
	}

	public static function sort_elements($a, $b) {
		if ($a['sort_order'] < $b['sort_order']) return -1;
		elseif ($a['sort_order'] > $b['sort_order']) return 1;
		elseif ($a['created_date'] < $b['created_date']) return -1;
		elseif ($a['created_date'] > $b['created_date']) return 1;
		else return 0;
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public function update_element($site_homepage_id, $data) {
		$proto = self::$queries['update_element_proto'];

		$query_mods = ['set' => [], 'where' => $proto['proto_opts'][':site_homepage_id']];

		foreach ($data as $field => $value) {
			$query_mods['set'][] = $proto['proto_opts'][$field];
		}

		$query_mods['set'] = implode(', ', $query_mods['set']);

		$data[':site_homepage_id'] = $site_homepage_id;

		$qry = self::modify_query('update_element_proto', $query_mods);

		//echo self::$queries[$qry]['qry'];
		//var_dump($data);

		self::execute($qry, $data);

		$this->skeleton->rebuild('rotator');
		$this->skeleton->rebuild('kickers');
		$this->skeleton->rebuild('showcases');

		return TRUE;
	}

	public function create_element($data) {
		$proto = self::$queries['create_element_proto'];

		$query_mods = ['data_operation' => [], 'values' => ''];

		foreach ($data as $field => $value) {
			$query_mods['data_operation'][] = $proto['proto_opts'][$field];
		}

		$query_mods['data_operation'] = '('.implode(', ', $query_mods['data_operation']).')';
		$query_mods['values'] = '('.implode(', ', array_keys($data)).')';

		$qry = self::modify_query('create_element_proto', $query_mods);

		//echo self::$queries[$qry]['qry'];
		//var_dump($data);

		self::execute($qry, $data);
		$site_homepage_id = self::get_db()->last_insert_id();

		$this->skeleton->rebuild($data[':element']);

		return $site_homepage_id;
	}

	public static function manage_rotator($homepage) {
		foreach ($_POST['elements'] as $site_homepage_id => $element) {
			if ($site_homepage_id == 'newr') {
				if (empty($element['img_src'])) continue;
				$data = [
					':element' => $element['element'],
					':sort_order' => $element['sort_order'],
					':img_src' => $element['img_src'],
					':alt_text' => $element['alt_text'],
					':link_target_type' => $element['link_target_type'],
					':link_target' => $element['link_target_type']=='none'?NULL:$element['link_target'],
				];

				$homepage->create_element($data);
			}
			else {
				$data = [
					':element' => $element['element'],
					':sort_order' => $element['sort_order'],
					':img_src' => $element['img_src'],
					':alt_text' => $element['alt_text'],
					':link_target_type' => $element['link_target_type'],
					':link_target' => $element['link_target_type']=='none'?NULL:$element['link_target'],
					':active' => CK\fn::check_flag(@$element['active'])?1:0
				];

				if (CK\fn::check_flag(@$element['archived'])) $data[':archived'] = 1;

				$homepage->update_element($site_homepage_id, $data);
			}
		}
	}

	public static function manage_kickers($homepage) {
		foreach ($_POST['elements'] as $site_homepage_id => $element) {
			if ($site_homepage_id == 'newk') {
				if (empty($element['img_src'])) continue;
				$data = [
					':element' => $element['element'],
					':sort_order' => $element['sort_order'],
					':img_src' => $element['img_src'],
					':alt_text' => $element['alt_text'],
					':link_target_type' => $element['link_target_type'],
					':link_target' => $element['link_target_type']=='none'?NULL:$element['link_target'],
				];

				$homepage->create_element($data);
			}
			else {
				$data = [
					':element' => $element['element'],
					':sort_order' => $element['sort_order'],
					':img_src' => $element['img_src'],
					':alt_text' => $element['alt_text'],
					':link_target_type' => $element['link_target_type'],
					':link_target' => $element['link_target_type']=='none'?NULL:$element['link_target'],
					':active' => CK\fn::check_flag(@$element['active'])?1:0
				];

				if (CK\fn::check_flag(@$element['archived'])) $data[':archived'] = 1;

				$homepage->update_element($site_homepage_id, $data);
			}
		}
	}

	public static function manage_showcases($homepage) {
		foreach ($_POST['elements'] as $site_homepage_id => $element) {
			if ($site_homepage_id == 'news') {
				if (empty($element['html']) && empty($element['title'])) continue;
				$data = [
					':title' => $element['title'],
					':html' => $element['html'],
					':product_ids' => $element['product_ids'],
					':active' => 0,
					':element' => 'showcases',
				];

				$homepage->create_element($data);
			}
			else {
				$data = [
					':title' => $element['title'],
					':html' => $element['html'],
					':product_ids' => $element['product_ids'],
					':active' => CK\fn::check_flag($element['active'])?1:0
				];

				if (CK\fn::check_flag(@$element['archived'])) $data[':archived'] = 1;

				$homepage->update_element($site_homepage_id, $data);
			}
		}
	}

	public static function process_action($action) {
		switch ($action) {
			case 'category_lookup':
				$results = ['rows' => []];
				$field = $_GET['field'];
				// this should live in the ck_listing_categories class, but we'll deal with it for now
				if ($categories = prepared_query::fetch('SELECT cd.categories_id FROM categories_description cd WHERE cd.categories_name LIKE :field ORDER BY cd.categories_name', cardinality::SET, [':field' => '%'.$field.'%'])) {
					foreach ($categories as $categories_id) {
						$category = new ck_listing_category($categories_id['categories_id']);
						$results['rows'][] = ['value' => $categories_id['categories_id'], 'result' => $category->get_header('categories_name'), 'url' => $category->get_url(), 'label' => $category->get_header('categories_name')];
					}
				}
				else {
					$results['rows'][] = ['value' => '', 'result' => '', 'url' => '', 'label' => 'No Matching Options'];
				}
				echo json_encode($results);
				exit();
				break;
			case 'manage_elements':
				$element_type = $_POST['element-type'];

				$homepage = new self();

				if ($_POST['element-type'] == 'rotator') self::manage_rotator($homepage);
				elseif ($_POST['element-type'] == 'kickers') self::manage_kickers($homepage);
				elseif ($_POST['element-type'] == 'showcases') self::manage_showcases($homepage);

				CK\fn::redirect_and_exit('/admin/homepage_manager.php');
				break;
		}
	}
}
?>
