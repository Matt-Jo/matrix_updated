<?php
/*
 * Created on Jul 19, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

 class productCheckTreeView{

	var $id, $onProductClickTemplate, $onProductCheckTemplate, $onProductUncheckTemplate, $productCheckedList, $TreeType;

	function __construct($treeId) {
		$this->id = $treeId != null ? $treeId : "product_tree";
		$this->onProductClickTemplate = null;
		$this->onProductCheckTemplate = null;
		$this->onProductUncheckTemplate = null;
		$this->productCheckedList = array();
	}

	function setOnProductClick($onClickTemplate) {
		$this->onProductClickTemplate = $onClickTemplate;
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

	function setTreeType($type) {
		$this->TreeType = $type;
	}

	function getTreeType() {
		return $this->TreeType;
	}
	function isProductIdInCheckedList($productId) {
		for ($i = 0; $i < count($this->productCheckedList); $i++) {
			if ($this->productCheckedList[$i] == $productId) {
				return 'true';
			}
		}
		return 'false';
	}

	function insertProductIdIntoTemplate($template, $prodId) {
		return str_replace('%product_id%', $prodId, $template);
	}

	function render() {
		echo '<link rel=\'stylesheet\' type=\'text/css\' href=\''.DIR_WS_CATALOG.'yui/css/check/tree.css\' />';
		echo '<link rel=\'stylesheet\' type=\'text/css\' href=\''.DIR_WS_CATALOG.'yui/build/container/assets/container.css\' />';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/yahoo/yahoo.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/event/event.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/dom/dom.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/dragdrop/dragdrop.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/container/container.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/treeview/treeview.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/js/TaskNode.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_ADMIN.'includes/javascript/productPreviewDialog.js\' ></script>';
		echo '<div id=\''.$this->id.'\'></div>';
		echo '<script type=\'text/javascript\'>';
		echo ' var tree_'.$this->id.' = null;';
		echo ' function buildTree_'.$this->id.'() {';
		echo '	tree_'.$this->id.' = new YAHOO.widget.TreeView(\''.$this->id.'\');';
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

	function renderAjaxTree() {
		echo '<link rel=\'stylesheet\' type=\'text/css\' href=\''.DIR_WS_CATALOG.'yui/css/check/tree.css\' />';
		echo '<link rel=\'stylesheet\' type=\'text/css\' href=\''.DIR_WS_CATALOG.'yui/build/container/assets/container.css\' />';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/yahoo/yahoo.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/event/event.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/dom/dom.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/dragdrop/dragdrop.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/container/container.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/build/treeview/treeview.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_CATALOG.'yui/js/TaskNode.js\' ></script>';
		echo '<script type=\'text/javascript\' src=\''.DIR_WS_ADMIN.'includes/javascript/productPreviewDialog.js\' ></script>';
		echo '<div id=\''.$this->id.'\'></div>';
		echo '<script type=\'text/javascript\'>'."\n";
		echo ' var tree_'.$this->id.' = null;';
		echo ' function buildTree_'.$this->id.'() {';
		echo '	tree_'.$this->id.' = new YAHOO.widget.TreeView(\''.$this->id.'\');';
		echo '	tree_'.$this->id.'.setDynamicLoad('.$this->id.'_loadNodeData, 1);';
		echo ' var rootNode = tree_'.$this->id.'.getRoot();';

		$category_query = prepared_query::fetch("select c.categories_id, cd.categories_name, c.sort_order from categories c, categories_description cd where c.parent_id = '0' and c.categories_id = cd.categories_id and cd.language_id = '1' order by c.sort_order, cd.categories_name", cardinality::SET);
		foreach ($category_query as $categories) {
			$this->insertCategoryNodeAjax($categories['categories_id'], $categories['categories_name'], 'rootNode');
		}

		echo ' tree_'.$this->id.'.draw();';
		echo ' }';
		echo 'YAHOO.util.Event.addListener(window, "load", buildTree_'.$this->id.');';
	?>
	function <?php echo $this->id; ?>_loadNodeData(node, fnLoadComplete) {
		if ( ! node.data.category_id) {
			fnLoadComplete();
			return;
		}
		var nodeLabel = encodeURI(node.data.category_id);
		var sUrl = "categories_ajax.php?action=get_tree_node&parent_node=" + nodeLabel+"&check_type=<?php echo $this->getTreeType(); ?>&pID="+<?= $_GET['pID']; ?>;
		var callback = {

			//if our XHR call is successful, we want to make use
			//of the returned data and create child nodes.
			success: function(oResponse) {
				//YAHOO.log("XHR transaction was successful.", "info", "example");
				//console.log(oResponse.responseText);
				var oResults = eval("(" + oResponse.responseText + ")");
				if ((oResults.ResultSet.Result) && (oResults.ResultSet.Result.length)) {
					//Result is an array if more than one result, string otherwise
					if (YAHOO.lang.isArray(oResults.ResultSet.Result)) {
						for (var i=0, j=oResults.ResultSet.Result.length; i<j; i++) {

						if ( oResults.ResultSet.Result[i].type == 'category') {

							category_id=oResults.ResultSet.Result[i].category_id;

							node_name = oResults.ResultSet.Result[i].category_name;

							data = {
								label: '<span style="text-decoration:none; color: black;">'+node_name+'</span>',
								category_id: category_id,
								category_name: node_name}


								var tempNode = new YAHOO.widget.TaskNode(data, node, false, false, true);


						}
						else if ( oResults.ResultSet.Result[i].type=='product') {
							product_id=oResults.ResultSet.Result[i].product_id;

							node_name = oResults.ResultSet.Result[i].product_name;
							data = {
								label: '<span onmouseover="ppd_show( '+product_id+' , event);" onmouseout="ppd_hide();" style="text-decoration:none; color: black;">'+node_name+'</span>',
								product_id: product_id,
								product_name: node_name,
								isLeaf: true,
								leaf: true}

							var tempNode = new YAHOO.widget.TaskNode(data, node, false, false);
							tempNode.isLeaf = true;
							tempNode.onCheckClick = function() {
								if (this.checked) {
									<?php if ($this->TreeType=='parent') {?>
										createProductAddon(this.data.product_id, '<?= $_GET['pID']; ?>', 'pa_parent_table', this.data.product_id);
									<?php }else {?>
										createProductAddon( '<?= $_GET['pID']; ?>', this.data.product_id, 'pa_children_table', this.data.product_id);
									<?php } ?>
									addIdToParentsCheckedList(this.data.product_id);
								}
								else {
									<?php if ($this->TreeType=='parent') {?>
										removeProductAddon( this.data.product_id,'<?= $_GET['pID']; ?>', 'pa_parent_table', 'pa_'+this.data.product_id+'_<?= $_GET['pID']; ?>');
									<?php }else {?>
												removeProductAddon( '<?= $_GET['pID']; ?>',this.data.product_id, 'pa_children_table', 'pa_<?= $_GET['pID']; ?>_'+this.data.product_id);
									<?php } ?>
												removeIdFromParentsCheckedList(this.data.product_id);
								}

							}

						}
						if (oResults.ResultSet.Result[i].checked=="1") {
								tempNode.check();
						}

						}
					}
				}

				oResponse.argument.fnLoadComplete();
			},
			failure: function(oResponse) {
				YAHOO.log("Failed to process XHR transaction.", "info", "example");
				oResponse.argument.fnLoadComplete();
			},
			argument: {
				"node": node,
				"fnLoadComplete": fnLoadComplete
			},

			timeout: 7000
		};

		YAHOO.util.Connect.asyncRequest('GET', sUrl, callback);
	}
	<?php
	echo '</script>';
	}

	function insertCategoryNode($categoryId, $categoryName, $parentNodeName) {
		$node_name = 'tree_'. $this->id.'_cat_'.$categoryId;
		echo 'var '.$node_name.' = new YAHOO.widget.TaskNode(\''.addslashes('<span style="text-decoration:none; color: black;">'.$categoryName.'</span>').'\', '.$parentNodeName.', false, false, true);';

		$category_query = prepared_query::fetch("select c.categories_id, cd.categories_name, c.sort_order from categories c, categories_description cd where c.parent_id = :parent_id and c.categories_id = cd.categories_id and cd.language_id = '1' order by c.sort_order, cd.categories_name", cardinality::SET, [':parent_id' => $categoryId]);
		foreach ($category_query as $categories) {
			$this->insertCategoryNode($categories['categories_id'], $categories['categories_name'], $node_name);
		}

		$product_query = prepared_query::fetch("select p.products_id, pd.products_name from products p, products_description pd, products_to_categories p2c where p.products_id = pd.products_id and pd.language_id = '1' and p.products_id = p2c.products_id and p2c.categories_id = :categories_id order by pd.products_name", cardinality::SET, [':categories_id' => $categoryId]);
		foreach ($product_query as $products) {
			$this->insertProductNode($products['products_id'], $products['products_name'], $node_name);
		}
	}

	function insertCategoryNodeAjax($categoryId, $categoryName, $parentNodeName) {
		$node_name = 'tree_'. $this->id.'_cat_'.$categoryId;
	$data = "{ label: '<span style=\"text-decoration:none; color: black;\">$categoryName</span>', category_id: '$categoryId', category_name: '$categoryName'}";

				$checked=$this->isProductIdInCheckedList($categoryId);
		echo 'var '.$node_name.' = new YAHOO.widget.TaskNode( '. $data.', '.$parentNodeName.', false, '.$checked.', true);'."\n";

	}

	function insertProductNode($productId, $productName, $parentNodeName) {
		$node_id = 'tree_'.$this->id.'_prod_'.$productId;
		$node_text = '<span onmouseover="ppd_show('.$productId.', event);" onmouseout="ppd_hide();">'.$productName.'</span>';
		echo "var ".$node_id."_data = { label: '".addslashes($node_text)."', 'product_id': '".$productId."'};";
		echo "var ".$node_id." = new YAHOO.widget.TaskNode(".$node_id."_data, ".$parentNodeName.", false, false);";

		//check if this node should be checked
		if ($this->isProductIdInCheckedList($productId)) {
			echo $node_id.".check();";
		}

		if ($this->onProductCheckTemplate != null || $this->onProductUncheckTemplate != null) {
			echo $node_id.'_checkClickHandler = function() { ';

			if ($this->onProductCheckTemplate != null) {
				echo 'if ('.$node_id.'.checked) {';
				echo $this->insertProductIdIntoTemplate($this->onProductCheckTemplate, $productId);
				echo '}';
			}
			if ($this->onProductUncheckTemplate != null) {
				echo 'if (!'.$node_id.'.checked) {';
				echo $this->insertProductIdIntoTemplate($this->onProductUncheckTemplate, $productId);
				echo '}';
			}

			echo '}; ';
			echo $node_id.'.onCheckClick = '. $node_id.'_checkClickHandler;';
		}

		if ($this->onProductClickTemplate != null) {
			echo $node_id.'_labelClickHandler = function() {';

			echo $this->insertProductIdIntoTemplate($this->onProductClickTemplate, $productId);

			echo '}; ';
			echo $node_id.'.onLabelClick = '. $node_id.'_labelClickHandler;';
		}
	}
 }
?>
