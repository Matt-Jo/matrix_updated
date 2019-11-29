<?php
require_once('includes/application_top.php');

$parentId = $_GET['parentId'];
$childId = $_GET['childId'];
$displayId = $_GET['displayId'];

//first we lookup the default price and description for the addon product
$default_values = prepared_query::fetch('SELECT pad.default_price, pad.default_desc, pd.products_name, p.products_price FROM products p JOIN products_description pd ON pd.products_id = p.products_id AND p.products_id = :child_id LEFT JOIN product_addon_data pad ON p.products_id = pad.product_id AND p.products_id = :child_id', cardinality::ROW, [':child_id' => $childId]);

$price = isset($default_values['default_price'])?$default_values['default_price']:$default_values['products_price'];
$name = $default_values['products_name'];
$desc = $default_values['default_desc'];

prepared_query::execute('INSERT INTO product_addons (product_id, product_addon_id) VALUES (:parent_id, :child_id)', [':parent_id' => $parentId, ':child_id' => $childId]);

$product_name_lookup = prepared_query::fetch('SELECT products_name FROM products_description WHERE products_id = :products_id', cardinality::SINGLE, [':products_id' => $displayId]);

$displayName = $product_name_lookup;
$tableId = $displayId==$parentId?'pa_parents_table':'pa_children_table';

$addon_uid = 'pa_'.$parentId.'_'.$childId;
?>
var parentTable = document.getElementById('<?= $tableId; ?>');
var newRow = parentTable.insertRow(parentTable.rows.length);
newRow.id = '<?= $addon_uid; ?>';

var newCell = newRow.insertCell(0);
newCell.style.fontSize = "10px";
var productLink = document.createElement("a");
productLink.href = 'categories.php?action=new_product&pID=<?= $displayId; ?>';
productLink.innerHTML = '<?= addslashes($displayName); ?>';
newCell.appendChild(productLink);

newCell = newRow.insertCell(1);
newCell.style.textAlign="center";
newCell.style.fontSize = "10px";
var checkbox = document.createElement("input");
checkbox.type="checkbox";
checkbox.name="<?= $addon_uid; ?>_included";
checkbox.onchange = function () {
	toggleIncluded(this.checked, '<?= $addon_uid; ?>');
}
newCell.appendChild(checkbox);

newCell = newRow.insertCell(2);
newCell.style.textAlign="center";
newCell.style.fontSize = "10px";
var inputField = document.createElement("input");
inputField.type="text";
inputField.name="<?= $addon_uid; ?>_bundle_quantity";
inputField.id="<?= $addon_uid; ?>_bundle_quantity";
inputField.size="4";
inputField.value="1";
inputField.style.display="none";
newCell.appendChild(inputField);

newCell = newRow.insertCell(3);
newCell.style.textAlign="center";
newCell.style.fontSize = "10px";
var checkbox = document.createElement("input");
checkbox.type="checkbox";
checkbox.name="<?= $addon_uid; ?>_recommended";
checkbox.id="<?= $addon_uid; ?>_recommended";
newCell.appendChild(checkbox);

newCell = newRow.insertCell(4);
newCell.style.fontSize = "10px";
var multOptsField = document.createElement("input");
multOptsField.name="<?= $addon_uid; ?>_allow_mult_opts";
multOptsField.id="<?= $addon_uid; ?>_allow_mult_opts";
multOptsField.value="0";
multOptsField.size="3";
newCell.appendChild(multOptsField);


newCell = newRow.insertCell(5);
newCell.style.textAlign="center";
newCell.style.fontSize = "10px";
var checkbox = document.createElement("input");
checkbox.type="checkbox";
checkbox.name="<?= $addon_uid; ?>_use_custom_price";
checkbox.id="<?= $addon_uid; ?>_use_custom_price";
checkbox.onchange = function () {
	toggleCustomPrice(this.checked, '<?= $addon_uid; ?>');
}
newCell.appendChild(checkbox);

newCell = newRow.insertCell(6);
newCell.style.fontSize = "10px";
var customPriceField = document.createElement("input");
customPriceField.name="<?= $addon_uid; ?>_custom_price";
customPriceField.id="<?= $addon_uid; ?>_custom_price";
customPriceField.value="<?= number_format($price, 2, '.', ','); ?>";
customPriceField.size="10";
customPriceField.style.display="none";
newCell.appendChild(customPriceField);
var defaultPriceSpan = document.createElement("span");
defaultPriceSpan.id = "<?= $addon_uid; ?>_default_price";
defaultPriceSpan.innerHTML = "<?= number_format($price, 2, '.', ','); ?>";
newCell.appendChild(defaultPriceSpan);

newCell = newRow.insertCell(7);
newCell.style.textAlign="center";
newCell.style.fontSize = "10px";
var checkbox = document.createElement("input");
checkbox.type="checkbox";
checkbox.name="<?= $addon_uid; ?>_use_custom_name";
checkbox.onchange = function () {
	toggleCustomName(this.checked, '<?= $addon_uid; ?>');
}
newCell.appendChild(checkbox);

newCell = newRow.insertCell(8);
newCell.style.fontSize = "10px";
var customNameTextArea = document.createElement("textarea");
customNameTextArea.row = 3;
customNameTextArea.cols = 40;
customNameTextArea.name = "<?= $addon_uid; ?>_custom_name";
customNameTextArea.id="<?= $addon_uid; ?>_custom_name";
customNameTextArea.innerHTML = "<?= addslashes($name); ?>";
customNameTextArea.style.display="none";
newCell.appendChild(customNameTextArea);
var defaultNameSpan = document.createElement("span");
defaultNameSpan.id = "<?= $addon_uid; ?>_default_name";
defaultNameSpan.innerHTML = "<?= addslashes($name); ?>";
newCell.appendChild(defaultNameSpan);

newCell = newRow.insertCell(9);
newCell.style.textAlign="center";
newCell.style.fontSize = "10px";
var checkbox = document.createElement("input");
checkbox.type="checkbox";
checkbox.name="<?= $addon_uid; ?>_use_custom_desc";
checkbox.onchange = function () {
	toggleCustomDesc(this.checked, '<?= $addon_uid; ?>');
}
newCell.appendChild(checkbox);

newCell = newRow.insertCell(10);
newCell.style.fontSize = "10px";
var customDescTextArea = document.createElement("textarea");
customDescTextArea.row = 3;
customDescTextArea.cols = 40;
customDescTextArea.name = "<?= $addon_uid; ?>_custom_desc";
customDescTextArea.id="<?= $addon_uid; ?>_custom_desc";
customDescTextArea.innerHTML = "<?= addslashes($desc); ?>";
customDescTextArea.style.display="none";
newCell.appendChild(customDescTextArea);
var defaultDescSpan = document.createElement("span");
defaultDescSpan.id = "<?= $addon_uid; ?>_default_desc";
defaultDescSpan.innerHTML = "<?= addslashes($desc); ?>";
newCell.appendChild(defaultDescSpan);
