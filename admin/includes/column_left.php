<div id="menu" style="width: 125px; float: left;">
<?php
if (tep_admin_check_boxes('orders.php') == true) require(DIR_WS_BOXES.'orders.php');
if (tep_admin_check_boxes('customers.php') == true) require(DIR_WS_BOXES.'customers.php');
if (tep_admin_check_boxes('purchasing.php') == true) require(DIR_WS_BOXES.'purchasing.php');
if (tep_admin_check_boxes('sales_rep.php') == true) require(DIR_WS_BOXES.'sales_rep.php');
if (tep_admin_check_boxes('warehouse.php') == true) require(DIR_WS_BOXES.'warehouse.php');
if (tep_admin_check_boxes('marketing.php') == true) require(DIR_WS_BOXES.'marketing.php');
if (tep_admin_check_boxes('inventory.php') == true) require(DIR_WS_BOXES.'inventory.php');
if (tep_admin_check_boxes('merchandising.php') == true) require(DIR_WS_BOXES.'merchandising.php');
if (tep_admin_check_boxes('accounting.php') == true) require(DIR_WS_BOXES.'accounting.php');
if (tep_admin_check_boxes('information.php') == true) require(DIR_WS_BOXES.'information.php');
if (tep_admin_check_boxes('administrator.php') == true) require(DIR_WS_BOXES.'administrator.php');
if (tep_admin_check_boxes('tools.php') == true) require(DIR_WS_BOXES.'tools.php');
if (tep_admin_check_boxes('data_manager.php')) require(DIR_WS_BOXES.'data_manager.php');
?>
</div>
<script type="text/javascript">
	jQuery("#menu").accordion({
		collapsible: true,
		autoHeight: false,
		active: <?php if (is_string($selectedBox)) echo "'".$selectedBox."'"; else echo 'false'; ?>
	});
</script>
