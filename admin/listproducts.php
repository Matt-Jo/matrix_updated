<?php
/*
 $Id: validproducts.php,v 0.01 2002/08/17 15:38:34 Richard Fielder

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com



 Copyright (c) 2002 Richard Fielder

 Released under the GNU General Public License
*/

require('includes/application_top.php');


?>
<html>
<head>
<title>Valid Categories/Products List</title>
<style type="text/css">
<!--
h4 { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: x-small; text-align: center}
p { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: xx-small}
th { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: xx-small}
td { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: xx-small}
-->
</style>
<head>
<body>
<table width="550" border="1" cellspacing="1" bordercolor="gray">
<tr>
<td colspan="3">
<h4>Valid Products List</h4>
</td>
</tr>
<?
	$restrict_to_products = prepared_query::fetch("select restrict_to_products from coupons where coupon_id = :coupon_id", cardinality::SINGLE, [':coupon_id' => $_GET['cid']]);

	echo "<tr><th>Product ID</th><th>Product Name</th><th>Product Size</th></tr><tr>";
	$pr_ids = split("[,]", $restrict_to_products);
	for ($i = 0; $i < count($pr_ids); $i++) {
		$row = prepared_query::fetch("SELECT * FROM products, products_description WHERE products.products_id = products_description.products_id and products_description.language_id = :language_id and products.products_id = :products_id", cardinality::ROW, [':language_id' => $_SESSION['languages_id'], ':products_id' => $pr_ids[$i]]);
		if (!empty($row)) {
			echo "<td>".$row["products_id"]."</td>\n";
			echo "<td>".$row["products_name"]."</td>\n";
			echo "<td>".$row["products_model"]."</td>\n";
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
?>
<br>
<table width="550" border="0" cellspacing="1">
<tr>
<td align=middle><input type="button" value="Close Window" onClick="window.close()"></td>
</tr></table>
</body>
</html>
