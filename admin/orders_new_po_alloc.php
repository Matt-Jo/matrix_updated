<?php
//MMD - assume we need to include application_top
require_once("includes/application_top.php");
require_once("includes/functions/po_alloc.php");

switch ($_GET['action']) {
	case '':
		break;
	case 'display':
		po_alloc_display();
		break;
	case 'save':
		$op_id = $_GET['op_id'];
		//save the data first
		po_alloc_save($op_id);
		//then throw back new markup
		//hardcode the markup - because we now if we can get here we can display the markup
		po_alloc_op_markup($op_id, '2');
		break;
	default:
		$op_id = null;
		if ($product) {
			$op_id = $product['orders_products_id'];
		}
		else if (isset($_GET['op_id'])) {
			$op_id = $_GET['op_id'];
		}
		else {
			echo "ERROR: op_id not set";
		}
		po_alloc_op_markup($op_id, $order->orders_status);
		break;
}

?>
