<style type="text/css">
.logo {
padding-bottom:20px;
}

.numpad {
width:90px;
height:70px;
background-color="#bccff1;
bweight:1px solid #1958cb;
}
h1 {
font-size:18px;
color:#666666;
}
h2 {
font-size:14px;
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

function FillWeight($var) {
	$weight = document.getElementById("weight").value

	document.getElementById("weight").value = $weight + $var;
 }

function Clearform() {
	document.getElementById("weight").value = "";
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
<div> <h1>weight input system</h1> </div>

<?php
if (empty($_POST['order'])) {

echo 'Please go back and enter an order number<br /><br />';
echo '<a href="search_order.php"><img width="165" border="0" src="images/Back.jpg"></a>';
} else {
$orders = $_POST['order'];

$sql = "select * from orders where orders_id = '$orders'";
$query = mysql_query($sql);
$result = mysql_fetch_array($query);

if (!$result) {
echo 'there are no orders that match #'.$orders ;
echo '<br /><br /><a href="search_order.php"><img width="165" border="0" src="images/Back.jpg"></a>';
} else {

if (@$_POST['weight']) {
$order_id = $_POST['order'];
$weight = $_POST['weight'];
$sql = "update orders set actual_weight='$weight' where orders_id = '$order_id'";
mysql_query($sql);

echo 'This orders weight has been updated<br /><br>';
echo '<a href="search_order.php"><img width="165" border="0" src="images/Back.jpg"></a>';
}
else {

echo '<div class="main">';
$order = $_POST['order'];
echo '<h2>Order# '.$order.'</h2>';
?>

<form name="search_weights" action="order_weight.php" method="post">
<div style="float:left; padding-right:30px;">
<div>
<img type="image" src="images/num_pad_01.jpg" value="1" name="1" class="numpad" onclick="FillWeight(1)">
<img type="image" src="images/num_pad_02.jpg" value="2" name="2" class="numpad" onclick="FillWeight(2)">
<img type="image" src="images/num_pad_03.jpg" value="3" name="3" class="numpad" onclick="FillWeight(3)"></div>
<div><img type="image" src="images/num_pad_04.jpg" value="4" name="4"class="numpad" onclick="FillWeight(4)">
<img type="image" src="images/num_pad_05.jpg" value="5" name="5" class="numpad" onclick="FillWeight(5)">
<img type="image" src="images/num_pad_06.jpg" value="6" name="6" class="numpad" onclick="FillWeight(6)"></div>
<div><img type="image" src="images/num_pad_07.jpg" value="7" name="7" class="numpad" onclick="FillWeight(7)">
<img type="image" src="images/num_pad_08.jpg" value="8" name="8" class="numpad" onclick="FillWeight(8)">
<img type="image" src="images/num_pad_09.jpg" value="9" name="9" class="numpad" onclick="FillWeight(9)"></div>
<div><img type="image" src="images/num_pad_00.jpg" value="0" name="0" class="numpad" onclick="FillWeight(0)">
<img type="image" src="images/num_pad_dot.jpg" value="." name="." class="numpad" onclick="FillWeight('.')">
<img type="image" src="images/num_pad_c.jpg" value="Clear" name="Clear" class="numpad" onclick="Clearform()"></div>
</div>
<div>
<h2>Weight:</h2><input type="text" name="weight" id="weight"><br><br>
<input class="go" type="image" src="images/Submit.jpg" value="Submit" name="Submit Weight">
<br /><br />
<?php echo '<a href="search_order.php"><img width="165" border="0" src="images/Cancel.jpg"></a>'; ?>
</div>
<input type="hidden" value="<?= $order; ?>" name="order">
</form>

<?php

echo '</div>';
}
?>
</div>

<?php }} ?>

