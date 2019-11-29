<?php
/*
 * Created on Jul 19, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

 class productFoldersTreeView{

	var $id, $onProductClickTemplate, $showProductCheckboxes, $onProductCheckTemplate, $onProductUncheckTemplate, $productCheckedList;

	function __construct($treeId) {
		$this->id = $treeId != null ? $treeId : "product_tree";
		$this->onProductClickTemplate = null;
		$this->showProductCheckboxes = false;
		$this->onProductCheckTemplate = null;
		$this->onProductUncheckTemplate = null;
		$this->productCheckedList = array();
	}

	function setOnProductClick($onClickTemplate) {
		$this->onProductClickTemplate = $onClickTemplate;
	}

	function setShowProductCheckboxes($showCheckboxes) {
		$this->showProductCheckboxes = $showCheckboxes;
	}

	function setOnProductCheck($onCheckTemplate) {
		$this->onProductCheckTemplate = $onCheckTemplate;
	}

	function setOnProductUncheck($onUncheckTemplate) {
		$this->onProductUncheckTemplate = $onUncheckTemplate;
	}

	function setProductCheckedList($productList) {
		$this->productCheckedList = $productList;
	}

	function isProductIdInCheckedList($productId) {
		for ($i = 0; $i < count($this->productCheckedList); $i++) {
			if ($this->productCheckedList[$i] == $productId) {
				return true;
			}
		}
		return false;
	}

	function insertProductIdIntoTemplate($template, $prodId) {
		return str_replace('%product_id%', $prodId, $template);
	}

	function render() {
		echo '<link rel=\'stylesheet\' type=\'text/css\' href=\''.DIR_WS_CATALOG.'yui/css/folders/tree.css\' />';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/yahoo/yahoo.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/event/event.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/treeview/treeview.js\' ></script>';
		echo '<div id=\''.$this->id.'\'></div>';
		echo '<script type=\'text/javascript\'>';
		echo ' function buildTree_'.$this->id.'() {';
		echo '	var tree_'.$this->id.' = new YAHOO.widget.TreeView(\''.$this->id.'\');';
		echo ' var rootNode = tree_'.$this->id.'.getRoot();';

		$category_query = prepared_query::fetch("select c.categories_id, cd.categories_name, c.sort_order from categories c, categories_description cd where c.parent_id = '0' and c.categories_id = cd.categories_id and cd.language_id = '1' order by c.sort_order, cd.categories_name", cardinality::SET);
		foreach ($category_query as $categories) {
			$this->insertCategoryNode($categories['categories_id'], $categories['categories_name'], 'rootNode');
		}

		echo ' tree_'.$this->id.'.draw();';
		echo ' }';
		echo 'YAHOO.util.Event.addListener(window, "load", buildTree_'.$this->id.');';
		echo '</script>';
	}

	function insertCategoryNode($categoryId, $categoryName, $parentNodeName) {
		$node_name = 'tree_'. $this->id.'_cat_'.$categoryId;
		echo 'var '.$node_name.' = new YAHOO.widget.TextNode(\''.addslashes($categoryName).'\', '.$parentNodeName.', false);';

		$category_query = prepared_query::fetch("select c.categories_id, cd.categories_name, c.sort_order from categories c, categories_description cd where c.parent_id = :parent_id and c.categories_id = cd.categories_id and cd.language_id = '1' order by c.sort_order, cd.categories_name", cardinality::SET, [':parent_id' => $categoryId]);
		foreach ($category_query as $categories) {
			$this->insertCategoryNode($categories['categories_id'], $categories['categories_name'], $node_name);
		}

		$product_query = prepared_query::fetch("select p.products_id, pd.products_name from products p, products_description pd, products_to_categories p2c where p.products_id = pd.products_id and pd.language_id = '1' and p.products_id = p2c.products_id and p2c.categories_id = :categories_id order by pd.products_name", cardinality::SET, [':categories_id' => $categoryId]);
		foreach ($product_query as $products) {
			$this->insertProductNode($products['products_id'], $products['products_name'], $node_name);
		}
	}

	function insertProductNode($productId, $productName, $parentNodeName) {
		$html_node_id = 'tree_'.$this->id.'_prod_'.$productId;
		$html_node_content = "<div id='".$html_node_id."' style='position:relative;'>";

		//check if we need to show check boxes
		if ($this->showProductCheckboxes) {
			$html_node_content .= '<input type="checkbox" ';

			if ($this->isProductIdInCheckedList($productId)) {
				$html_node_content .= 'checked ';
			}

			if ($this->onProductCheckTemplate != null || $this->onProductUncheckTemplate != null) {
				$html_node_content .= 'onclick="';

				if ($this->onProductCheckTemplate != null) {
					$html_node_content .= 'if (this.checked) {';
					$html_node_content .= $this->insertProductIdIntoTemplate($this->onProductCheckTemplate, $productId);
					$html_node_content .= '}';
				}
				if ($this->onProductUncheckTemplate != null) {
					$html_node_content .= 'if (!this.checked) {';
					$html_node_content .= $this->insertProductIdIntoTemplate($this->onProductUncheckTemplate, $productId);
					$html_node_content .= '}';
				}

				$html_node_content .= '" ';
			}

			$html_node_content .= '/>';

			if ($this->onProductClickTemplate != null) {
				$html_node_content .= '<a href="javascript:void();" onclick="';
				$html_node_content .= $this->insertProductIdIntoTemplate($this->onProductClickTemplate, $productId);
				$html_node_content .= ' return false;">';
				$html_node_content .= $productName;
				$html_node_content .= '</a>';
			}

			else {
				$html_node_content .= $productName;
			}
		}

		$html_node_content .= "</div>";

		echo "var ".$html_node_id."_html = '".addslashes($html_node_content)."';";
		echo "new YAHOO.widget.HTMLNode(".$html_node_id."_html, ".$parentNodeName.", false, false);";
		//now that we have construct our html, we write this content to a variable and then insert the node into the tree
	}
 }
?>
