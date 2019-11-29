<?php
require('includes/application_top.php');
require_once('includes/ExcelClass/reader.php');

/*-----------------------------*/
// These don't strictly need to be variables, but it just represents that this information could change in the future.
$search_provider = 'Nextopia';
/*-----------------------------*/

function trim_quotes($s) {
	$s=trim($s);
	return trim($s, "\"");
}

function build_category_hierarchies() {
	$data = array();
	if ($categories = prepared_query::fetch("SELECT categories_id, parent_id FROM categories ORDER BY categories_id DESC", cardinality::SET)) {
		foreach ($categories as $category) {
			$hierarchy = array();
			if (!empty($category['parent_id'])) {
				build_hierarchy($category['parent_id'], $categories, $hierarchy);
				$children[$category['categories_id']] = build_children($category['categories_id'], $categories);
			}
			$data[$category['categories_id']] = implode('/', array_reverse($hierarchy));
		}
	}
	return array($data, $children);
}
function build_hierarchy($parent_id, $categories, &$hierarchy) {
	foreach ($categories as $category) {
		if ($category['categories_id'] == $parent_id) {
			$hierarchy[] = $category['categories_id'];
			if ($category['parent_id']) build_hierarchy($category['parent_id'], $categories, $hierarchy);
			// each category will have only one parent
			break;
		}
	}
	return $hierarchy;
}
function build_children($category_id, $categories) {
	$children = array();
	foreach ($categories as $category) {
		if ($category['parent_id'] == $category_id) {
			$children[] = $category['categories_id'];
			$children = array_merge($children, build_children($category['categories_id'], $categories));
		}
	}
	return array_unique($children);
}

function convert($size) {
	$unit = array('b','kb','mb','gb','tb','pb');
	return @round($size / pow(1024, ($i=floor(log($size, 1024)))), 2).' '.$unit[$i];
}

set_time_limit(0);
ini_set('memory_limit', '256M');

$import_run = FALSE;
//$first_run = TRUE;
$imported_category;
$output = array();
$errors = array();

if (!empty($_FILES)) {
	if (!empty($_FILES['attribute_csvfile'])) {
		$import_run = TRUE;

		if ($_FILES['attribute_csvfile']['error'] || ($csv = fopen($_FILES['attribute_csvfile']['tmp_name'], 'r')) === FALSE) {
			//print "Sorry, there was a problem with that uploaded file.<br/>";
			//die();
			$errors[] = "Sorry, there was a problem with that uploaded file.<br/>";
		}
		else {
			$success = $failure = $warning = array('add' => 0, 'update' => 0);

			$create = in_array($_POST['upload_action'], array(1,3));
			$update = in_array($_POST['upload_action'], array(2,3));

			if ($_POST['attribute_context'] == 'keys') {
				list($hierarchies, $children) = build_category_hierarchies();
				$row_count = 0;
				while (($row = fgetcsv($csv, 0, "\t")) !== FALSE) {
					$row_count++;
					if ($_POST['skip_rows'] && $_POST['skip_rows'] >= $row_count) { $output[] = "Row $row_count skipped<br/>"; continue; }
					if (!$row) continue;

					$group_name = trim(strtolower(preg_replace('/\s+/', ' ', array_shift($row))));
					if (!$group_name) continue;

					$group_desc = trim(strtolower(preg_replace('/\s+/', ' ', array_shift($row))));
					$category_list = preg_split('/\W/', array_shift($row));
					if (empty($category_list)) {
						$errors[] = "You must include a category list for attribute group [".stripslashes($group_name)."]";
						continue;
					}
					$trait_list = preg_split('/\W/', array_shift($row));

					if ($_POST['clear_all'] == 'on') {
						foreach ($category_list as $category) {
							$category_id = is_numeric($category)?$category:prepared_query::fetch('SELECT categories_id FROM categories_description WHERE categories_name LIKE ?', cardinality::SINGLE, array($category));

							if ($products = prepared_query::fetch("SELECT DISTINCT products_id FROM products_to_categories WHERE categories_id = ?", cardinality::COLUMN, array($category_id))) {
								foreach ($products as $product) {
									prepared_query::execute("DELETE FROM ck_attribute_assignments WHERE products_id = ?", array($product));
								}
							}
							if (in_array($category, $trait_list)) {
								if ($children && isset($children[$category_id])) {
									foreach ($children[$category_id] as $child) {
										if ($products = prepared_query::fetch("SELECT DISTINCT products_id FROM products_to_categories WHERE categories_id = ?", cardinality::COLUMN, array($child))) {
											foreach ($products as $product) {
												prepared_query::execute("DELETE FROM ck_attribute_assignments WHERE products_id = ?", array($product));
											}
										}
									}
								}
							}

							prepared_query::execute('DELETE FROM ck_attribute_group_categories WHERE category_id = ?', array($category_id));
							if (in_array($category, $trait_list)) {
								if ($children && isset($children[$category_id])) {
									foreach ($children[$category_id] as $child) {
										prepared_query::execute('DELETE FROM ck_attribute_group_categories WHERE category_id = ?', array($child));
									}
								}
							}
							prepared_query::execute('DELETE FROM ck_attribute_groups WHERE group_name LIKE ? AND attribute_group_id NOT IN (SELECT DISTINCT attribute_group_id FROM ck_attribute_group_categories)', array($group_name));
							prepared_query::execute('DELETE FROM ck_attribute_group_lists WHERE attribute_group_id NOT IN (SELECT DISTINCT attribute_group_id FROM ck_attribute_groups)');
						}
					}

					try {
						$action = 'add';
						if (!($attribute_group_id = prepared_query::fetch("SELECT attribute_group_id FROM ck_attribute_groups WHERE group_name LIKE ?", cardinality::SINGLE, array($group_name)))) {
							if (!empty($create)) {
								$attribute_group_id = $group_insert = prepared_query::insert("INSERT INTO ck_attribute_groups (group_name, description) VALUES (?, ?)", array($group_name, $group_desc));
								$success[$action]++;
							}
							else {
								$warning[$action]++;
								$errors[] = "GROUP ENTRY DENIED: $group_name was not created, no attributes were assigned";
								continue;
							}
						}
						else {
							$action = 'update';
							if (!empty($update)) {
								if (!empty($group_desc)) {
									if ($group_desc == 'delete') $group_desc = NULL;
									prepared_query::execute("UPDATE ck_attribute_groups SET description = ? WHERE attribute_group_id = ?", array($group_desc, $attribute_group_id));
									$success[$action]++;
								}
							}
							else {
								$warning[$action]++;
								$errors[] = "GROUP UPDATE DENIED: $group_name was not updated. No further actions are allowed for this entry.";
								continue;
							}
						}
					}
					catch (Exception $e) {
						$failure[$action]++;
						$errors[] = "GROUP ".strtoupper($action)." FAILURE: ".$e->getMessage();
						continue;
					}

					try {
						foreach ($category_list as $category) {
							$category_id = is_numeric($category)?$category:prepared_query::fetch('SELECT categories_id FROM categories_description WHERE categories_name LIKE ?', cardinality::SINGLE, array($category));
							if (!$category_id) continue;
							if (!($category_name = prepared_query::fetch("SELECT categories_name FROM categories_description WHERE categories_id = ?", cardinality::SINGLE, array($category_id)))) {
								$errors[] = "Category ID [$category_id] could not be found in the database.";
								continue;
							}
							$trait = in_array($category, $trait_list)?1:0;
							$hierarchy = $hierarchies[$category_id];
							if (!($attribute_group_category_id = prepared_query::fetch("SELECT attribute_group_category_id FROM ck_attribute_group_categories WHERE attribute_group_id = ? AND category_id = ?", cardinality::SINGLE, array($attribute_group_id, $category_id)))) {
								$category_insert = prepared_query::execute("INSERT INTO ck_attribute_group_categories (category_id, categories_name, category_hierarchy, attribute_group_id, group_name, trait) VALUES (?, ?, ?, ?, ?, ?)", array($category_id, $category_name, $hierarchy, $attribute_group_id, $group_name, $trait));
							}
							else {
								prepared_query::execute("UPDATE ck_attribute_group_categories SET categories_name = ?, category_hierarchy = ?, trait = ? WHERE attribute_group_category_id = ?", array($category_name, $hierarchy, $trait, $attribute_group_category_id));
								$success[$action]++;
							}
						}
					}
					catch (Exception $e) {
						$failure[$action]++;
						$errors[] = "CATEGORY GROUP ATTACHMENT FAILURE: ".$e->getMessage();
						continue;
					}

					try {
						foreach ($row as $attribute_idx => $attribute) {
							$attribute_desc = trim(preg_replace('/\s+/', ' ', $attribute));
							$attribute = strtolower($attribute_desc);
							if (!$attribute) continue;
							if (in_array($attribute, array('price','brand','condition','warranty'))) continue;

							if (!($attribute_key_id = prepared_query::fetch("SELECT attribute_key_id FROM ck_attribute_keys WHERE attribute_key LIKE ?", cardinality::SINGLE, array($attribute)))) {
								$attribute_key_id = prepared_query::insert("INSERT INTO ck_attribute_keys (attribute_key, description) VALUES (?, ?)", array($attribute, $attribute_desc));
							}
							if (!($attribute_group_list_id = prepared_query::fetch("SELECT attribute_group_list_id FROM ck_attribute_group_lists WHERE attribute_group_id = ? AND attribute_key_id = ?", cardinality::SINGLE, array($attribute_group_id, $attribute_key_id)))) {
								$group_list_insert = prepared_query::execute("INSERT INTO ck_attribute_group_lists (attribute_group_id, group_name, attribute_key_id, attribute_key) VALUES (?, ?, ?, ?)", array($attribute_group_id, $group_name, $attribute_key_id, $attribute));
							}
						}
					}
					catch (Exception $e) {
						$failure[$action]++;
						$errors[] = "ATTRIBUTE ENTRY/ATTACHMENT FAILURE: ".$e->getMessage();
						continue;
					}
				}

				try {
					if ($products = prepared_query::fetch("SELECT DISTINCT p.products_id, p.products_model, ptc.categories_id, agl.attribute_key_id, agl.attribute_key FROM products p JOIN products_to_categories ptc ON p.products_id = ptc.products_id LEFT JOIN ck_attribute_group_categories agc ON ptc.categories_id = agc.category_id LEFT JOIN ck_attribute_group_lists agl ON agc.attribute_group_id = agl.attribute_group_id", cardinality::SET)) {
						foreach ($products as $product) {
							if (!empty($product['attribute_key_id'])) {
								$attribute_assignment_insert = prepared_query::execute("INSERT IGNORE INTO ck_attribute_assignments (attribute_assignment_level, products_id, model_number, attribute_key_id, attribute_key) VALUES (?, ?, ?, ?, ?)", array(3, $product['products_id'], $product['products_model'], $product['attribute_key_id'], $product['attribute_key']));
							}
							$parent_categories = $hierarchies[$product['categories_id']]?array_reverse(explode('/', $hierarchies[$product['categories_id']])):array();
							foreach ($parent_categories as $category_id) {
								if ($attributes = prepared_query::fetch("SELECT DISTINCT agc.category_id, agl.attribute_key_id, agl.attribute_key FROM ck_attribute_group_categories agc JOIN ck_attribute_group_lists agl ON agc.attribute_group_id = agl.attribute_group_id WHERE agc.trait = 1 AND agc.category_id = ?", cardinality::SET, array($category_id))) {
									foreach ($attributes as $attribute) {
										$attribute_assignment_insert = prepared_query::execute("INSERT IGNORE INTO ck_attribute_assignments (attribute_assignment_level, products_id, model_number, attribute_key_id, attribute_key) VALUES (?, ?, ?, ?, ?)", array(4, $product['products_id'], $product['products_model'], $attribute['attribute_key_id'], $attribute['attribute_key']));
									}
								}
							}
						}
					}
				}
				catch (Exception $e) {
					$errors[] = "ATTRIBUTE ASSIGNMENT FAILURE: ".$e->getMessage();
				}

				foreach ($success as $action => $cnt) {
					$action .= $action=='add'?'ed':'d';
					$output[] = "There were $cnt groups $action.<br/>";
				}
				foreach ($warning as $action => $cnt) {
					$action .= $action=='add'?'ed':'d';
					$output[] = "There were $cnt groups that were not allowed to be $action.<br/>";
				}
				foreach ($failure as $action => $cnt) {
					$action .= $action=='add'?'ed':'d';
					$output[] = "There were $cnt groups that failed to $action.<br/>";
				}
				$output[] = "<br/>";
			}
			elseif ($_POST['attribute_context'] == 'values') {
				$row_count = 0;
				$attribute_keys = array();
				$first_col_status = FALSE;
				while (($row = fgetcsv($csv, 0, "\t")) !== FALSE) {
					$row_count++;
					if ($_POST['skip_rows'] && $_POST['skip_rows'] > $row_count) { $output[] = "Row $row_count skipped<br/>"; continue; }
					if ($_POST['skip_rows'] && $_POST['skip_rows'] == $row_count) {
						if (preg_match('/status/i', $row[0])) $first_col_status = TRUE;
						foreach ($row as $idx => $attribute_key) {
							if ($idx <= 3 || ($idx == 4 && $first_col_status)) continue; // these are the product/ipn details
							if (!$attribute_keys && preg_match('/attribute/i', $attribute_key)) break;
							$attribute_keys[] = $attribute_key;
						}
						continue;
					}
					if (!$row) continue;

					if (!$_POST['skip_rows'] && !$first_col_status && preg_match('/on|off/i', $row[0])) $first_col_status = TRUE;
					if ($first_col_status) array_shift($row); // the first column just displays the on/off status of the product, we don't need it so discard it

					$product_id = (int) array_shift($row);
					if (!$product_id) continue;
					$stock_id = (int) array_shift($row);
					if (!$stock_id) continue;

					$model_number = $ipn = '';
					try {
						$model_number = prepared_query::fetch("SELECT products_model FROM products WHERE products_id = ?", cardinality::SINGLE, array($product_id));
						array_shift($row); // we got the right model number, just ditch the one from the import
						$ipn = prepared_query::fetch("SELECT stock_name FROM products_stock_control WHERE stock_id = ?", cardinality::SINGLE, array($stock_id));
						array_shift($row); // we got the right ipn, just ditch the one from the import
					}
					catch (Exception $e) {
						$errors[] = "Failure to get model # or ipn from database, using data from the import: ".$e->getMessage();
						!$model_number?$model_number = array_shift($row):NULL;
						!$ipn?$ipn = array_shift($row):NULL;
					}
					// we'll just remove these columns in pre-processing the list, since we'll need to pre-process the list anyway to combine attribute/value columns
					//array_shift($row); // ditch the product name
					//array_shift($row); // ditch the category hierarchy
					//array_shift($row); // ditch the first category
					foreach ($row as $idx => $attribute) {
						if (!$attribute_keys) list($attribute_key, $value) = preg_split('/\s*:\s*/', $attribute);
						else {
							$attribute_key = $attribute_keys[$idx];
							$value = $attribute;
						}
						$values = preg_split('/\s*,\s*/', trim(preg_replace('/\s+/', ' ', $value)));

						$attribute_desc = trim(preg_replace('/\s+/', ' ', $attribute_key));
						$attribute_key = strtolower($attribute_desc);
						if (!$attribute_key) continue;
						if (in_array($attribute_key, array('price','brand','condition','warranty','category','current offers'))) continue;

						if (!empty($_POST['clear_all']) && $_POST['clear_all'] == 'on') {
							prepared_query::execute('DELETE FROM ck_attributes WHERE products_id = ? AND attribute_key LIKE ?', array($product_id, $attribute_key));
						}

						try {
							// find or create the attribute_key_id
							if (!($attribute_key_id = prepared_query::fetch("SELECT attribute_key_id FROM ck_attribute_keys WHERE attribute_key LIKE ?", cardinality::SINGLE, array($attribute_key)))) {
								if (!empty($create)) {
									$attribute_key_id = prepared_query::insert("INSERT INTO ck_attribute_keys (attribute_key, description) VALUES (?, ?)", array($attribute_key, $attribute_desc));
								}
								else {
									$warning['add']++;
									$errors[] = "ATTRIBUTE ENTRY DENIED: $attribute_key was not created, no values were assigned";
									continue;
								}
							}

							foreach ($values as $value) {
								if (!$value) continue;
								$value_insert = prepared_query::execute("INSERT INTO ck_attributes (stock_id, ipn, products_id, model_number, attribute_key_id, attribute_key, value) VALUES (?, ?, ?, ?, ?, ?, ?)", array($stock_id, $ipn, $product_id, $model_number, $attribute_key_id, $attribute_key, $value));
								$success['add']++;
							}
						}
						catch (Exception $e) {
							$failure['add']++;
							//$errors[] = "ATTRIBUTE ADD FAILURE: ".$e->getMessage();
							continue;
						}
					}
				}

				foreach ($success as $action => $cnt) {
					$action .= $action=='add'?'ed':'d';
					$output[] = "There were $cnt attribute values $action.<br/>";
				}
				foreach ($warning as $action => $cnt) {
					$action .= $action=='add'?'ed':'d';
					$output[] = "There were $cnt attribute values that were not allowed to be $action.<br/>";
				}
				foreach ($failure as $action => $cnt) {
					$action .= $action=='add'?'ed':'d';
					$output[] = "There were $cnt attribute values that failed to $action.<br/>";
				}
				$output[] = "<br/>";
			}
		}
	}
}
if (isset($_GET['action']) && $_GET['action'] == 'export') {
	$category_id = $_GET['category_id'];
	$cascade = isset($_GET['cascade'])&&$_GET['cascade']=='on'?TRUE:FALSE;
	$useids = isset($_GET['useids'])&&$_GET['useids']=='on'?TRUE:FALSE;
	if (@$_GET['attribute_context'] == 'keys') {
		if (!empty($category_id)) {
			$category_ids = array($category_id);
			$groups = array();
			$categories = array();
			$attributes = array();
			foreach($category_ids as $category_id ) {
				$cat_id = $category_id;
				$groups = array_merge($groups, prepared_query::fetch('SELECT DISTINCT ag.attribute_group_id, ag.group_name, ag.description FROM ck_attribute_groups ag JOIN ck_attribute_group_categories agc ON ag.attribute_group_id = agc.attribute_group_id WHERE agc.category_id = ?', cardinality::SET, array($cat_id)));
				$categories = array_merge($categories, prepared_query::fetch('SELECT DISTINCT agc.attribute_group_id, agc.category_id, agc.categories_name, agc.trait FROM ck_attribute_group_categories agc JOIN ck_attribute_group_categories agc2 ON agc.attribute_group_id = agc2.attribute_group_id WHERE agc2.category_id = ?', cardinality::SET, array($cat_id)));
				$attributes = array_merge($attributes, prepared_query::fetch('SELECT DISTINCT agl.attribute_group_id, ak.attribute_key_id, ak.description as attribute_key FROM ck_attribute_group_lists agl JOIN ck_attribute_keys ak ON agl.attribute_key_id = ak.attribute_key_id JOIN ck_attribute_group_categories agc ON agl.attribute_group_id = agc.attribute_group_id WHERE agc.category_id = ?', cardinality::SET, array($cat_id)));

				// if we're cascading to parent categories, grab those categories and add them to the list we're looping through.
				while ($cascade && $cat = prepared_query::fetch('SELECT c.categories_id, agc.trait FROM categories c JOIN categories c2 ON c.categories_id = c2.parent_id LEFT JOIN ck_attribute_group_categories agc ON c.categories_id = agc.category_id WHERE c2.categories_id = ?', cardinality::ROW, array($cat_id))) {
					if ($cat['trait'] && !in_array($cat['categories_id'], $category_ids)) {
						$category_ids[] = $cat['categories_id'];
						break;
					}
					else {
						$cat_id = $cat['categories_id'];
					}
				}
			}
		}
		else {
			$groups = prepared_query::fetch('SELECT ag.attribute_group_id, ag.group_name, ag.description FROM ck_attribute_groups ag', cardinality::SET);
			$categories = prepared_query::fetch('SELECT agc.attribute_group_id, agc.category_id, agc.categories_name, agc.trait FROM ck_attribute_group_categories agc', cardinality::SET);
			$attributes = prepared_query::fetch('SELECT agl.attribute_group_id, ak.attribute_key_id, ak.description as attribute_key FROM ck_attribute_group_lists agl JOIN ck_attribute_keys ak ON agl.attribute_key_id = ak.attribute_key_id', cardinality::SET);
		}

		// we're just using a single file to export to. This will only support relatively infrequent usage by a small number of people. We'll have to come up with a more robust file management system if usage expands beyond that.
		$fp = fopen(dirname(__FILE__)."/attribute_update_rpt.txt", "w");
		$column_names = array('group name', 'group desc', 'category list', 'trait list');
		fwrite($fp, implode("\t", $column_names)."\n");
		foreach ($groups as $group) {
			$line = array($group['group_name'], $group['description']);
			$cats = array();
			$traits = array();
			foreach ($categories as $category) {
				if ($group['attribute_group_id'] != $category['attribute_group_id']) continue;
				$cats[] = $useids?$category['category_id']:$category['categories_name'];
				if ($category['trait']) $traits[] = $useids?$category['category_id']:$category['categories_name'];
			}
			$line[] = implode(', ', $cats);
			$line[] = implode(', ', $traits);
			foreach ($attributes as $attribute) {
				if ($group['attribute_group_id'] == $attribute['attribute_group_id']) $line[] = $attribute['attribute_key'];
			}
			fwrite($fp, implode("\t", $line)."\n");
		}
		fclose($fp);
	}
	elseif (@$_GET['attribute_context'] == 'values') {
		if (!$category_id) $errors[] = 'You must select a category to export when requesting product information.';
		else {
			$category_ids = array($category_id);
			$products = array();
			$attribute_keys = array();
			$ctr = 0;
			// creating a category ID loop allows us to add any children categories on the fly if we're cascading
			foreach($category_ids as $category_id) {
				$cat_id = $category_id;

				// if we're cascading to child categories, grab those categories and add them to the list we're looping through.
				if ($cascade && $categories = prepared_query::fetch('SELECT DISTINCT categories_id FROM categories WHERE parent_id = ?', cardinality::COLUMN, array($cat_id))) {
					foreach ($categories as $catid) {
						if (!in_array($catid, $category_ids)) $category_ids[] = $catid;
					}
				}

				$cattributes = prepared_query::fetch("SELECT DISTINCT * FROM (SELECT aa.products_id, ak.description as attribute_key, NULL as attribute_values FROM products_to_categories ptc JOIN ck_attribute_assignments aa ON ptc.products_id = aa.products_id AND ptc.categories_id = ? JOIN ck_attribute_keys ak ON aa.attribute_key_id = ak.attribute_key_id LEFT JOIN ck_attributes a ON aa.products_id = a.products_id AND aa.attribute_key_id = a.attribute_key_id WHERE a.attribute_id IS NULL AND ak.description NOT LIKE 'best sellers' UNION SELECT a.products_id, ak.description as attribute_key, GROUP_CONCAT(a.value) as attribute_values FROM products_to_categories ptc JOIN ck_attributes a ON ptc.products_id = a.products_id AND ptc.categories_id = ? JOIN ck_attribute_keys ak ON a.attribute_key_id = ak.attribute_key_id WHERE ak.description NOT LIKE 'best sellers' GROUP BY a.products_id, a.attribute_key) t ORDER BY products_id, attribute_key", cardinality::SET, array($cat_id, $cat_id));

				// manage the ordered list of attributes
				if (!empty($cattributes)) {
					foreach ($cattributes as $attribute) {
						if (!in_array($attribute['attribute_key'], $attribute_keys)) $attribute_keys[] = $attribute['attribute_key'];
					}
				}
			}

			// we're just using a single file to export to. This will only support relatively infrequent usage by a small number of people. We'll have to come up with a more robust file management system if usage expands beyond that.
			$fp = fopen(dirname(__FILE__)."/attribute_update_rpt.txt", "w");
			$column_names = array('status', 'products_id', 'stock_id', 'model_number', 'ipn');
			foreach ($attribute_keys as $attribute_key) {
				$column_names[] = $attribute_key;
			}
			fwrite($fp, implode("\t", $column_names)."\n");

			foreach ($category_ids as $category_id) {
				$cproducts = prepared_query::fetch("SELECT CASE WHEN p.products_status = 0 THEN 'OFF' ELSE 'ON' END as status, p.products_id, psc.stock_id, p.products_model, psc.stock_name FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id JOIN products_to_categories ptc ON p.products_id = ptc.products_id WHERE ptc.categories_id = ?", cardinality::SET, array($category_id));

				// manage the products, along with the attribute values that belong to those products in the order those attributes should show up
				if (!empty($cproducts)) {
					foreach ($cproducts as $product) {
						$cattributes = prepared_query::fetch("SELECT ak.description as attribute_key, GROUP_CONCAT(a.value) as attribute_values FROM products_to_categories ptc JOIN ck_attributes a ON ptc.products_id = a.products_id AND ptc.categories_id = ? JOIN ck_attribute_keys ak ON a.attribute_key_id = ak.attribute_key_id WHERE ptc.products_id = ? AND ak.description NOT LIKE 'best sellers' GROUP BY a.products_id, a.attribute_key", cardinality::SET, array($category_id, $product['products_id']));
						foreach ($attribute_keys as $attribute_key) {
							foreach ($cattributes as $attribute) {
								if ($attribute['attribute_key'] == $attribute_key) {
									$product[] = $attribute['attribute_values'];
									continue 2;
								}
							}
							$product[] = '';
						}
						for ($i=count($product); $i<5+count($attribute_keys); $i++) {
							$product[] = '';
						}
						//$products[] = $product;
						fwrite($fp, implode("\t", $product)."\n");
					}
				}
			}
			fclose($fp);
		}
	}
	else {
		$errors[] = 'You must select either Groups/Categories or Products/Values to export.';
	}
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
		<?php require(DIR_WS_INCLUDES.'header.php'); ?>
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<td width="<?php echo BOX_WIDTH; ?>" valign="top">
					<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td width="100%" valign="top">
					<table border="0" width="800" cellspacing="0" cellpadding="2">
						<tr>
							<td>
								<?php if ($output || $errors) {
									echo implode("\n", $output);
									if (!empty($errors)) {
										echo "<br/>ERRORS:<br/>";
										echo implode("<br/>", $errors);
									}
								} ?>
								<script src="https://use.edgefonts.net/piedra:n4:all.js"></script>
								<style>
									.scorched { text-transform:uppercase; font-weight:600; font-family: piedra; font-size:18px; color:#f62; background: -webkit-linear-gradient(#fa4, #f32); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
									.tall-break { line-height: 2em; }
									.tall-break small { line-height: 1em; }
									.tall-break em { line-height: 1em; font-family:Arial; color:#c00; }
									.ud { max-width:1000px; }
									.ud th, .ud td { border-style:solid; border-color:#000; border-width:0px 1px 1px 0px; padding:4px 8px; min-width:115px; }
									.ud tr:first-child th { border-top-width:1px; }
									.ud tr th:first-child, .ud tr td:first-child { border-left-width:1px; }
									.ud ol { padding-left:1.4em; }
									.ud strong { text-decoration:underline; cursor:pointer; color:#cb2026; }
									.ud a { color:#cb2026; }
								</style>
								<script>
								jQuery(document).ready(function() {
									jQuery('#category_selector').change(function() {
										var category_id = jQuery(this).val();
										jQuery(this).find('option').each(function() {
											if (jQuery(this).attr('value') == category_id) return;
											jQuery(this).remove();
										});
										if (category_id == -1) {
											// we're backing up
											category_list.selected_list.pop();
											if (category_list.selected_list.length) {
												// there's a previously selected category to back up to
												category_id = category_list.selected_list[category_list.selected_list.length - 1];

												if (category_list.selected_list.length > 1) {
													previous_category_id = category_list.selected_list[category_list.selected_list.length - 2];

													for (var j=0; j<category_list.selections[previous_category_id].length; j++) {
														if (category_list.selections[previous_category_id][j]['id'] == category_id) {
															jQuery(this).prepend('<option value="'+category_id+'">'+category_list.selections[previous_category_id][j]['name']+' ['+(category_list.selected_list.length-1)+']</option>');
														}
													}
												}
												else {
													for (var j=0; j<category_list.top_level.length; j++) {
														if (category_list.top_level[j]['id'] == category_id) {
															jQuery(this).prepend('<option value="'+category_id+'">'+category_list.top_level[j]['name']+'</option>');
														}
													}
												}

												jQuery(this).val(category_id);
												for (var i=0; i<category_list.selections[category_id].length; i++) {
													jQuery(this).append('<option value="'+category_list.selections[category_id][i]['id']+'">'+category_list.selections[category_id][i]['name']+' ['+category_list.selected_list.length+']</option>');
												}
											}
											else {
												// we're back at the top level
												jQuery(this).find('option').remove();
												jQuery(this).append('<option value="">All (only for Groups/Categories)</option>');
												jQuery(this).val('');
												for (var i=0; i<category_list.top_level.length; i++) {
													jQuery(this).append('<option value="'+category_list.top_level[i]['id']+'">'+category_list.top_level[i]['name']+'</option>');
												}
											}
										}
										else {
											// we selected a category
											category_list.selected_list.push(category_id);
											jQuery(this).append('<option value="-1">Back One Level</option>');
											for (var i=0; i<category_list.selections[category_id].length; i++) {
												jQuery(this).append('<option value="'+category_list.selections[category_id][i]['id']+'">'+category_list.selections[category_id][i]['name']+' ['+category_list.selected_list.length+']</option>');
											}
										}
									});

									jQuery('.ud strong').click(function() {
										jQuery('.ud ol').toggle();
									});
								});
								</script>
								<div style="padding:10px; border:1px solid #000; float:left; clear:both;">
									<?php if (isset($_GET['action']) && $_GET['action'] == 'export' && !$errors) { ?>
									<a href="/admin/attribute_update_rpt.txt">EXPORT</a> (right click, save as)<br/><br/>
									<?php } ?>
									<p><b>Export Attribute Report TXT file.</b></p>
									<form action="/admin/manage_attributes.php" method="get">
										<input type="hidden" name="action" value="export"/>
										<div class="tall-break">
											<small><strong>File will be tab delimited TXT. Save to your computer, right click, "Open With" and choose "Microsoft Excel". If that option isn't shown, select "Choose Default Program...", uncheck "Always use the selected program to open this type of file" and if necessary "Browse" to locate Excel.</strong></small><br/>
											[ <input type="radio" name="attribute_context" value="keys"/> Groups/Categories ]
											[ <input type="radio" name="attribute_context" value="values"/> Products/Values ]<br/>
											Category:
											<script>
												var category_list = {
													selected_list: [],
													selections: {},
													top_level: []
												};
											</script>
											<?php
											$top_level = array();
											$selections = array();
											?>
											<select id="category_selector" name="category_id" size="1">
												<option value="">ALL (only for Keys/Groups)</option>
												<?php if ($categories = prepared_query::fetch("SELECT c.categories_id, cd.categories_name, c.parent_id, COUNT(ptc.products_id) as pcount FROM categories_description cd JOIN categories c ON cd.categories_id = c.categories_id LEFT JOIN products_to_categories ptc ON c.categories_id = ptc.categories_id WHERE c.categories_id IN (SELECT DISTINCT parent_id as categories_id FROM categories UNION SELECT DISTINCT categories_id FROM products_to_categories) GROUP BY c.categories_id, cd.categories_name, c.parent_id ORDER BY cd.categories_name, c.categories_id", cardinality::SET)) {
													foreach ($categories as $category) {
														$cname = $category['pcount']?$category['categories_name'].'*':$category['categories_name'];
														if (empty($category['parent_id'])) {
															$top_level[] = array('id' => $category['categories_id'], 'name' => $cname); ?>
												<option value="<?= $category['categories_id']; ?>"><?= $cname; ?></option>
														<?php }
														else {
															if (!isset($selections[$category['parent_id']])) $selections[$category['parent_id']] = array();
															$selections[$category['parent_id']][] = array('id' => $category['categories_id'], 'name' => $cname);
														}
													}
												} ?>
											</select>
											<?php if (!empty($top_level)) { ?>
											<script>
												category_list.top_level = <?php echo json_encode($top_level); ?>;
												category_list.selections = <?php echo json_encode($selections); ?>;
											</script>
											<?php } ?>
											<small>(You can get to sub-categories by going back to the dropdown once you've made a selection.)</small><br/>
											<small>(Categories marked with a * have products attached directly to them)</small><br/>
											Cascade: <input type="checkbox" name="cascade" checked="checked"/> &darr;<br/>
											[Groups/Categories: Cascade selection to all parent categories that affect this category]<br/>
											[Products/Values: Cascade selection to products in all sub-categories]<br/>
											[Groups/Categories: Use Category IDs <input type="checkbox" name="useids" checked="checked"/> <small>(some categories have the same name, but different IDs)</small>]
										</div>
										<input type="submit" value="Export File"/>
									</form>
								</div>
								<div style="padding:10px; border:1px solid #000; float:left; clear:both;">
									<p><b>Upload Attribute TXT file.</b></p>
									<form enctype="multipart/form-data" action="/admin/manage_attributes.php" method="POST">
										<div class="tall-break">
											<small><strong>Please save file as tab delimited TXT</strong></small><br/>
											File: <input type="file" name="attribute_csvfile"/><br/>
											Header Row: <input type="text" style="width:30px;" name="skip_rows" value="1"/><br/>
											<small>(0 if there is no header in the spreadsheet, all rows before the header row will be skipped and data processing will start with the first row after the header row)</small><br/>
											<select name="upload_action" size="1">
												<option value="3">Create/Update</option>
												<option value="1">Create Only</option>
												<option value="2">Update Only</option>
											</select>
											[ <input type="radio" name="attribute_context" value="keys"/> Keys/Groups ]
											[ <input type="radio" name="attribute_context" value="values"/> Values ]<br/>
											<input type="checkbox" name="clear_all"/> <span class="scorched">Scorched Earth</span> (destroy and rebuild keys for categories or products referenced in upload)
											<!--select name="clear_context" size="1">
												<option value="category">Data for Categories Referenced in Upload</option>
												<option value="all">ALL DATA</option>
											</select--><br/>
											<input type="submit" value="Import File"/><br/>
											<em>If you have added a new attribute key, please be sure to activate it in <?= $search_provider; ?>'s admin interface or it will not show up on the front end.</em>
										</div>
										<hr/>
										<div class="ud">
											<strong>Instructions:</strong>
											<ol style="display:none;">
												<li>
													<em>Keys/Groups Uploading:</em>

													<p>This is used to assign a set of attributes to a set of products, the end result being that we know a product has a relevant attribute whether we've assigned a value for that attribute or not. We can use this to create a list of all products and their attributes that we can use to fill in the values for, and we can use it in search refinements to know that a particular attribute is "Unset" for a product if we've not yet filled in the values for it.</p>

													<p>The way this works is that we define an attribute group, choose which product categories that attribute group is relevant to, and which of those product categories should pass the group down to its children categories. Then we just list the attribute keys that belong in that group. The system looks up all items that are assigned to those categories and assigns each attribute key in the group to those products, if they have not already been assigned to that item previously or from another group.</p>

													<p>Attribute keys will be created if they have not yet already been created.</p>

													<p>In practice, we've more or less created one group per category, which is probably the most straight forward way to manage this.</p>

													<p>The spreadsheet format is as follows <small>(<a href="resources/attributes_upload-keys_groups.txt">D/L Blank Spreadsheet</a> - right click & Save As..., right click to open with Excel, and make sure to save as tab-delimited)</small>:</p>

													<table cellpadding="0" cellspacing="0">
														<thead>
															<tr>
																<th>Group Name (req)</th>
																<th>Group Description (opt)</th>
																<th>Category ID List (req)</th>
																<th>Trait cID List (opt)</th>
																<th>Attribute Key1 (req)</th>
																<th>Attribute Key2 (opt)</th>
																<th>... (opt)</th>
															</tr>
														</thead>
														<tbody>
															<tr>
																<td>The reference name of the attribute group</td>
																<td>Any descriptive text to go along with the group</td>
																<td>The list of category IDs to assign to this group (only category IDs should be used at this time, not actual category names)</td>
																<td>Any categories which have children for which this group should also be assigned (without having to include them directly). <em>If you do not include a category ID in this list, you must explicitly include child category IDs in the Category ID List if they should be assigned this group.</em></td>
																<td>The name of the first attribute to assign to the group</td>
																<td>The second attribute</td>
																<td>Include as many attributes as are applicable</td>
															</tr>
														</tbody>
													</table>
													<hr/>
												</li>
												<li>
													<em>Attribute Values Uploading:</em>

													<p>This is used to assign actual attribute values to each individual product. Each product will be on its own line, and any relevant attribute values will be listed at the end. <em>This will not overwrite existing values</em>, it will only add new values. So, if a rackmount kit has a "Series" attribute value of "Cisco 6000", you can use this to add "Cisco 6500" so the kit will now be set for both, but this does not provide any mechanism to remove "Cisco 6000" as a value for the "Series" attribute.</p>

													<p>Attribute keys will be created if they have not yet already been created. These attributes need not be defined as part of an attribute group to be assigned to an individual product.</p>

													<p>The spreadsheet format is as follows <small>(<a href="resources/attribute_upload-values.txt">D/L Blank Spreadsheet</a> - right click & Save As..., right click to open with Excel, and make sure to save as tab-delimited)</small>:</p>

													<table cellpadding="0" cellspacing="0">
														<thead>
															<tr>
																<th>products_id (req)</th>
																<th>stock_id (req)</th>
																<th>Model # (opt)</th>
																<th>IPN (opt)</th>
																<th>Attribute Key1:Value1 (,Value2,...) (req)</th>
																<th>Attribute Key2:Value1 (,Value2,...) (opt)</th>
																<th>... (opt)</th>
															</tr>
														</thead>
														<tbody>
															<tr>
																<td>The product ID that identifies this catalog product in the database</td>
																<td>The stock ID that identifies this IPN in the database</td>
																<td>The catalog product model # (it will attempt to pull this from the database first)</td>
																<td>The IPN (it will attempt to pull this from the database first)</td>
																<td>The attribute key, followed by one or more values that should be assigned to this attribute key</td>
																<td>The second attribute key with its values</td>
																<td>Include as many attributes with their values as applicable.</td>
															</tr>
														</tbody>
													</table>
												</li>
											</ol>
										</div>
									</form>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>
