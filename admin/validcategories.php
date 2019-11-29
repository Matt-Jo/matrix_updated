<?php
/*
 $Id: validcategories.php,v 0.01 2002/08/17 15:38:34 Richard Fielder

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
<td colspan="4">
<h4>Categories List</h4>
</td>
</tr>
<?
	echo "<tr><th>Category ID</th><th>Category Name</th></tr><tr>";
	$result = prepared_query::fetch("SELECT * FROM categories, categories_description WHERE categories.categories_id = categories_description.categories_id and categories_description.language_id = 1 ORDER BY categories.categories_id", cardinality::SET);
	foreach ($result as $row) {
		echo "<td>".$row["categories_id"]."</td>\n";
		echo "<td>".$row["categories_name"]."</td>\n";
		echo "</tr>\n";
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
