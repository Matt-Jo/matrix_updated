<h3 id="orders"><a href="#">Orders</a></h3>
<div>
	<ul>
		<li><a href="/admin/orders_new.php?selected_box=orders&status=2">Orders List</a></li>
		<li><a href="/admin/rma-list.php">RMA List</a></li>
		<li><a href="/admin/cc_not_charged_report.php">CCs Not Charged</a></li>
	<?php if (tep_admin_check_boxes('ipn_editor.php')): ?>
		<li><a href="/admin/ga_order_data_import.php">GA Order Data Import</a></li>
	<?php endif; ?>
	</ul>
</div>
