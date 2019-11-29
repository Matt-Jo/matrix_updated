<?php

 require('includes/application_top.php');

	$oID = $_GET['oID'];
	$active = $_GET['active'];


	// if there's no other indication, first label should be active label
	if (empty($active)) {
		$active = 1;
		}


		$tracking_num = $_GET['num'];


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
 <head>

	<style type="text/css">
	<!--
		table {
			width: 675;
			border-top: 1px dotted black;
			}
		td {
			font-family: Verdana, Arial, sans-serif;
			font-size: 12px;
			}
		-->
	</style>
	<script language="JavaScript1.1" type="text/javascript">
	var NS4 = (document.layers) ? true : false ;var resolution = 96;if (NS4 && navigator.javaEnabled()) {var toolkit = java.awt.Toolkit.getDefaultToolkit();resolution = toolkit.getScreenResolution();}
	</script>
	<script language="JavaScript" type="text/javascript">
	document.write('<img WIDTH=' + (675 * resolution )/100 + '<img HEIGHT=' + (467 * resolution )/100 + ' alt="ASTRA Barcode" src="images/fedex/<?= $tracking_num; ?>.png">');
	</script>
	<title></title>
 </head>
 <body>
	<table border="0" width="100%">
	<tr>
		<td colspan="3">
		<img src="images/pixel_trans.gif" border="0" alt=""
		width="1" height="100">
		</td>
	</tr>
	<tr>
		<td align="center">
		<a href="#" onclick=
		"window.print(); return false"><img src=
		"includes/languages/english/images/buttons/button_print.gif"
				border="0" alt="IMAGE_ORDERS_PRINT" title=
				" IMAGE_ORDERS_PRINT "></a>
		</td>

				<td align="center">

		</td>
				<td align="center">
		<a href="view_po.php?pID=<?= $pID; ?>&action=edit"><img src=
		"includes/languages/english/images/buttons/button_back.gif"
				border="0" alt="IMAGE_ORDERS_BACK" title=
				" IMAGE_ORDERS_BACK "></a>
		</td>
	</tr>
	</table>
 </body>
</html>