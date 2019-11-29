<?php
class ck_product_finder {

	public $focus = NULL;
	public $major = NULL;
	public $minor = NULL;

	public $main_category;

	public $attributes = array();

	public $categories = array();
	public $applications = array();

	public $colors = array(
		'Blue' => array('hex' => '#0072bc', 'color' => 'Blue'),
		'Black' => array('hex' => '#000', 'color' => 'Black'),
		'Gray' => array('hex' => '#acacac', 'color' => 'Gray'),
		'Green' => array('hex' => '#049548', 'color' => 'Green'),
		'Red' => array('hex' => '#f30319', 'color' => 'Red'),
		'Yellow' => array('hex' => '#fee905', 'color' => 'Yellow', 'selhex' => 'black'),
		'White' => array('hex' => '#fff', 'color' => 'White', 'selhex' => 'black'),
		'Orange' => array('hex' => '#f26522', 'color' => 'Orange'),
		'Purple' => array('hex' => '#88026d', 'color' => 'Purple'),
		'Pink' => array('hex' => '#f26d7d', 'color' => 'Pink')
	);

	public function __construct($focus) {
		$this->focus = $focus;
		$this->get_context();
	}

	private function get_context() {
		$focus = explode('/', $this->focus, 2);
		array_filter($focus);

		$this->major = $focus[0];
		$this->minor = !empty($focus[1])?$focus[1]:NULL;
	}

	public function get_categories($parent_category_id, $db=NULL) {
		return $this->categories = prepared_query::fetch('SELECT cd.categories_id, cd.categories_name, cd.product_finder_description, cd.product_finder_image FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE c.parent_id = ? AND c.finder_category = 1 AND c.disabled = 0 AND c.inactive = 0 AND cd.product_finder_hide = 0 ORDER BY c.parent_id ASC, c.sort_order ASC, cd.categories_name ASC', cardinality::SET, array($parent_category_id));
	}

	public function get_applications($parent_category_id, $db=NULL) {
		return $this->applications = prepared_query::fetch('SELECT cd.categories_id, cd.categories_name, cd.product_finder_description, cd.product_finder_image FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE c.parent_id = ? AND c.finder_category = 0 AND c.disabled = 0 AND c.inactive = 0 AND cd.product_finder_hide = 0 ORDER BY c.parent_id ASC, c.sort_order ASC, cd.categories_name ASC', cardinality::SET, array($parent_category_id));
	}
}
?>