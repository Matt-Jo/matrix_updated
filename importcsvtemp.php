<?php
$db = mysql_connect('localhost', 'ckstore', 'k1ts.789');
mysql_select_db('ckstore');


$fp = fopen("/var/www/vhosts/cablesandkits.com/subdomains/dev/httpdocs/conman.csv", "r");
$row = 1;
while (($data = fgetcsv($fp,200)) !== FALSE) {
	if ($row == 1) {

	}
	else {
		$stockname = $data[0];
		$manufacturer = $data[1];
		$condition = $data[2];
		$sql_sid = "SELECT stock_id FROM products_stock_control WHERE stock_name = '$stockname'";
		$rstock = mysql_query($sql_sid);
		$stock_id = mysql_fetch_array($rstock);
		$stockid = $stock_id['stock_id'];
		$sql_fco = "SELECT conditions_id FROM conditions WHERE conditions_name = '$condition'";
		$rfcon = mysql_query($sql_fco);
		$find_con = mysql_fetch_array($rfcon);
		$conid = $find_con['conditions_id'];
		$sql_con = "UPDATE products_stock_control SET condition = '$conid' WHERE stock_id = '$stockid'";
		mysql_query($sql_con);
		$sql_fma = "SELECT manufacturers_id FROM manufacturers WHERE manufacturers_name = '$manufacturer'";
		$rfmanuf = mysql_query($sql_fma);
		$find_man = mysql_fetch_array($rfmanuf);
		$manuf_id = $find_man['manufacturers_id'];
		$sql_uma = "UPDATE products SET manufacturers_id = '$manuf_id' WHERE stock_id = '$stockid'";
		mysql_query($sql_uma);
	}
	$row++;
}
echo $row.' rows updated';
fclose($fp);
mysql_close($db);
?>
