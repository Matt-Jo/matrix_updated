<?php
function make_cat($cat) {
		$query="select * from products_stock_control_categories where name='$cat'";
		$result=mysql_query($query);
		if (!@mysql_num_rows($result)) {
			$query="insert into products_stock_control_categories set name='$cat'";
			mysql_query($query);
			return mysql_insert_id();
		}
		else {
				$row=mysql_fetch_array($result);
				return $row['categories_id'];
		}
}

function link_cat($stock_id, $cat_id) {
		$query="update products_stock_control set products_stock_control_category_id=$cat_id where stock_id = $stock_id";
		mysql_query($query);
}

$db = mysql_connect('localhost', 'ckstore', 'k1ts.789');
mysql_select_db('ckstore');



$fh=fopen('ipn_cats.csv','r');

while (($row=fgetcsv($fh, 1000, ",")) !==FALSE) {
		$stock_id = $row[0];
		$new_category = $row[4];
		$cat_id=make_cat($new_category);
		link_cat($stock_id, $cat_id);
		print "linked $stock_id to $new_category\n";
}


?>
