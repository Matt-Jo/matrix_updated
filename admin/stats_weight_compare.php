<?php
require('includes/application_top.php');
function tep_trim_shipping($str) {
	$str = strip_tags(str_replace('&nbsp;','',$str));
	$str = trim($str,':');
	//echo $str;
	if (strpos($str, 'edEx Express (Int')) {
		$str = 'FedEx Intl';
	} elseif (strpos($str, 'edEx Express') && $str != 'Fedex Intl') {
		$str = 'FedEx Express';
	} elseif (strpos($str, 'International Economy:')) {
		$str = 'International Economy';
	} elseif (strpos($str, 'International Priority:')) {
		$str = 'International Priority';
	} elseif (strpos($str, 'PS Ground') || strpos($str, 'PSGround')) {
		$str = 'Domestic UPS';
	}	elseif (strpos($str, 'ustomer Pickup')) {
		$str = 'Customer Pickup';
	}	elseif (strpos($str, 'edEx Ground')) {
		$str = 'FedEx Ground';
	}	elseif (strpos($str, 'nited States Postal Service')) {
		$str = 'USPS';
	}	elseif (strpos($str, 'nternational UPS')) {
		$str = 'Intl UPS';
	}	elseif (strpos($str, 'Shipping Special')) {
		$str = '$4.95 Shipping';
	}	elseif (strpos($str, 'omestic UPS')) {
		$str = 'Domestic UPS';
	}	elseif (strpos($str, 'ree Shipping')) {
		$str = 'Free Shipping';
	}	elseif (strpos($str, 'hippingMethodStandard')) {
		$str = 'Free Shipping';
	}
	return $str;
} ?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- header //-->
<?php require(DIR_WS_INCLUDES.'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
 <tr>
	<td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
<!-- left_navigation_eof //-->
		</table></td>
<!-- body_text //-->
	<td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
			<td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
		</tr>
		</table></td>
	</tr>
		<tr>
			<td class="main">
<?php

$orders_ids = prepared_query::fetch("SELECT orders_id FROM orders as o where TO_DAYS(o.date_purchased) > TO_DAYS(NOW()) - 365", cardinality::COLUMN);
echo '<table border="1" cellpadding="3">';
echo '<tr style="font-weight:bold; font-size:12px; text-align:center;"><td>Order #</td>&nbsp;<td>Shipping Method</td><td>Original Order Weight</td>&nbsp;<td>Order Weight</td>&nbsp;<td>Tar Weight</td><td>Actual Weight</td>
<td>Difference</td></tr>';
foreach ($orders_ids as $orders_id) {
	$order_weight = 0;
	$actual_weight = 0;

	/***** SHIPPING STUFF *****/
	$products_weight = prepared_query::fetch("SELECT SUM(p.products_weight * op.products_quantity) as total_weight, o.orders_weight, o.actual_weight, ot.title FROM orders o LEFT JOIN orders_total ot ON o.orders_id = ot.orders_id AND ot.class = 'ot_shipping' LEFT JOIN orders_products op ON o.orders_id = op.orders_id LEFT JOIN products p ON op.products_id = p.products_id WHERE o.orders_id = :orders_id GROUP BY ot.actual_shipping_cost", cardinality::ROW, [':orders_id' => $orders_id]);
	$order_weight = $products_weight['total_weight'];
	$actual_weight = $products_weight['actual_weight'];
	$orig_order_weight = $products_weight['orders_weight'];
	$order_weight_tar = $order_weight + SHIPPING_BOX_WEIGHT;

	$order_weight_percent = ($order_weight * (SHIPPING_BOX_PADDING / 100 + 1));

	if ($order_weight_percent < $order_weight_tar) {
		$package_weight = $order_weight_tar;
	} else {
		$package_weight = $order_weight_percent;
	}

	$package_weight=round($package_weight,1);
	$difference=($actual_weight - $orig_order_weight);
	if ($difference > 0) {
	$difference = '<b><font size="2" color="ff0000">'.$difference.'</font></b>';
}
	/***** SHIPPING STUFF *****/
	$products_weight['title'] = tep_trim_shipping($products_weight['title']);
	echo '<tr style="text-align:left; font-size:10px;"><td>'.$orders_id.'</td>&nbsp;<td>'.$products_weight['title'].'</td><td>'.$orig_order_weight.'</td>&nbsp;<td>'.$order_weight.'</td>&nbsp;<td>'.$package_weight.'</td><td>'. $actual_weight .'</td><td>'.$difference.'</td></tr>';
}
echo '</table>';
?>
			</td>
		</tr>
	</table></td>
 </tr>
</table></td>
<!-- body_text_eof //-->
 </tr>
</table>
<!-- body_eof //-->
</body>
</html>
