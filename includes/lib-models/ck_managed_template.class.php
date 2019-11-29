<?php
class ck_managed_template extends ck_archetype {
	protected static $skeleton_type = 'ck_managed_template_type';

	protected static $queries = [
		'template_header' => [
			'qry' => 'SELECT managed_template_id, template_name, template as template_location, context, nav, date_created FROM ck_managed_templates WHERE managed_template_id = :managed_template_id',
			'cardinality' => cardinality::ROW
		]
	];

	protected static $template_folder = 'includes/templates/managed';

	public function __construct($managed_template_id, ck_managed_template_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($managed_template_id);

		if (!$this->skeleton->built('managed_template_id')) $this->skeleton->load('managed_template_id', $managed_template_id);

		self::register($managed_template_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('managed_template_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('template_header', [':managed_template_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$header['nav'] = CK\fn::check_flag($header['nav']);
		$header['date_created'] = self::DateTime($header['date_created']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('template_header', [':managed_template_id' => $this->id()]));
		$this->normalize_header();
	}

	private function build_template() {
		$location = realpath(__DIR__.'/../../'.self::$template_folder.'/'.realpath($header['template_location']));
		if (is_file($location)) $this->skeleton->load('template', file_get_contents($location));
		else $this->skeleton->load('template', NULL);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function get_template() {
		if (!$this->skeleton->built('template')) $this->build_template();
		return $this->skeleton->get('template');
	}

	/*-------------------------------
	// change data
	-------------------------------*/
}

class CKManagedTemplateException extends CKMasterArchetypeException {
}
?>
