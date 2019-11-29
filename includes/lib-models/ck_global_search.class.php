<?php
class ck_global_search extends ck_master_archetype {

	protected $skeleton;

	private $context;

	protected $queries = [
		'active_codes' => [
			'qry' => 'SELECT * FROM ck_control_codes WHERE active = 1',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	private $context_map = [
		'ipn' => ['ck_ipn2', 'get_ipns_by_match'],
		'po' => ['ck_purchase_order', 'get_pos_by_match'],
	];

	public function __construct() {
		$this->skeleton = new ck_global_search_type;
		$this->skeleton->load('codes', self::fetch('active_codes', []));

		if (!empty($_SESSION['global_search.context'])) $this->set_context($_SESSION['global_search.context']);
	}

	public function __destruct() {
		$_SESSION['global_search.context'] = $this->context;
	}

	public function get_context() {
		return $this->context;
	}

	public function set_context($context) {
		$this->context = $context;
	}

	public function clear_context() {
		$this->set_context(NULL);
	}

	public function search($term, $context=NULL) {
		$term = $this->parse_term($term);
		if (empty($context)) $context = $this->context;


	}

	public function parse_term($term) {
		if ($term[0] == '#' && $term[1] == '-' && $term[2] == '#') {
			$parts = explode('#-#', $term, 3);

			$this->context = $parts[1];

			$term = !empty($parts[2])?$parts[2]:'';
		}

		return $term;
	}
}
?>
