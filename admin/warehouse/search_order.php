<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>

<style type="text/css">
.logo {
padding-bottom:20px;
}

.numpad {
width:90px;
height:70px;
background-color="#bccff1;
border:1px solid #1958cb;
}
h1 {
font-size:18px;
color:#666666;
}
.main {
width:600px;
}
.go {
width:165px;
height:70px;
}
</style>
<script language="javascript">

function FillOrder($var) {
	$order = document.getElementById("order").value

	document.getElementById("order").value = $order + $var;
 }

function Clearform() {
	document.getElementById("order").value = "";
}
</script>

<?php
	$host = 'localhost';
	$user = 'ckstore';
	$pass = 'k1ts.789';
	$dbname = 'ckstore';
	mysql_connect($host, $user, $pass) or die('failed to connect');
	
	mysql_select_db($dbname) or die('wrong db');
?>


<div class="main">
<div class="logo"><img src="/images/pop-the-top4.png"></div>
<div> <h1>order input system</h1> </div>

<?php
if (!empty($_POST)) {
echo '<div class="main">';
$orders = $_POST['order'];
$sql = "select * from orders where orders_id = '$orders'";
$query = mysql_query($sql);
$result = mysql_fetch_array($query);

if (empty($result)) {
echo 'no order found<br />';
echo '<a href="javascript:history.go(-1);">Go Back</a>';
}
else
{
echo 'Order#'.$result['orders_id'].'<br />';

echo '</div>';
}} else {

?>

<form name="search_orders" action="order_weight.php" method="post">
<div style="float:left; padding-right:30px;">
<div>
<img type="image" src="images/num_pad_01.jpg" value="1" name="1" class="numpad" onclick="FillOrder(1)">
<img type="image" src="images/num_pad_02.jpg" value="2" name="2" class="numpad" onclick="FillOrder(2)">
<img type="image" src="images/num_pad_03.jpg" value="3" name="3" class="numpad" onclick="FillOrder(3)"></div>
<div><img type="image" src="images/num_pad_04.jpg" value="4" name="4"class="numpad" onclick="FillOrder(4)">
<img type="image" src="images/num_pad_05.jpg" value="5" name="5" class="numpad" onclick="FillOrder(5)">
<img type="image" src="images/num_pad_06.jpg" value="6" name="6" class="numpad" onclick="FillOrder(6)"></div>
<div><img type="image" src="images/num_pad_07.jpg" value="7" name="7" class="numpad" onclick="FillOrder(7)">
<img type="image" src="images/num_pad_08.jpg" value="8" name="8" class="numpad" onclick="FillOrder(8)">
<img type="image" src="images/num_pad_09.jpg" value="9" name="9" class="numpad" onclick="FillOrder(9)"></div>
<div><img type="image" src="images/num_pad_00.jpg" value="0" name="0" class="numpad" onclick="FillOrder(0)">
<img type="image" src="images/num_pad_dot.jpg" value="." name="." class="numpad" onclick="FillOrder('.')">
<img type="image" src="images/num_pad_c.jpg" value="Clear" name="Clear" class="numpad" onclick="Clearform()"></div>
</div>
<div>
<input type="text" name="order" id="order"><br><br>
<input class="go" type="image" src="images/Search.jpg" value="Submit" name="Search for Order">
</div>
</form>
</div>

<?php } ?>

