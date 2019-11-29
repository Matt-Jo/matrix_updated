<?php
require_once('includes/application_top.php');

$parentId = $_GET['parentId'];
$childId = $_GET['childId'];

$where = [];
$where[':product_id'] = $parentId;
$where[':product_addon_id'] = $childId;

prepared_query::execute('DELETE FROM product_addons WHERE product_id = :product_id AND product_addon_id = :product_addon_id', $where);
