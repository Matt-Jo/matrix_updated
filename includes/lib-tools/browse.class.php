<?php
class browse extends product_navigation {

	public $Category = '';
	public $Subcategory = '';
	public $Currentcategory = array();

	public $cat_key = 'cPath';
	public $category_id;

	public $skip_extra_querying = array();
	public $hide_refinements = array();

	// initialize the query into usable structures for browsing
	public function __construct() {
		$this->cat_key = isset($_GET['cPath'])?'cPath':'cat_id';
		$this->category_id = isset($_GET[$this->cat_key])?$_GET[$this->cat_key]:NULL;
		parent::__construct();

		// we go ahead and populate the category and/or subcategory (or any other default fields that are relevant)
		$catid = $GLOBALS['current_category_id']?(int)$GLOBALS['current_category_id']:(int)$this->category_id;
		$this->resolve_categories($catid);

		$this->nav_fields = $this->_query;
	}

	public function resolve_categories($catid) {
		$cat = prepared_query::fetch('SELECT c.parent_id, cd.categories_name as category FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE c.categories_id = ?', cardinality::ROW, $catid);

		if(!empty($cat)) {
            $this->Currentcategory[] = $cat['category'];
            if (!empty($cat['parent_id'])) {
                $this->Subcategory = $cat['category'];
                $this->resolve_categories($cat['parent_id']);
            }
            else {
                $this->Category = $cat['category'];
            }
        }
	}

}
?>