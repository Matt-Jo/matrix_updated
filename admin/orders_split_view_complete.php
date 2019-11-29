<h2>Split order process completed.</h2>
<h3>Original Order: <a href="orders_new.php?oID=<?= $_GET['original_order_id']; ?>&action=edit"><?= $_GET['original_order_id']; ?></a></h3>
<ul>
	<?php if (!empty($_GET['shipping_recalculated']) && $_GET['shipping_recalculated'] == 1) { ?>
	<li>Shipping charges were automatically recalculated for this order. Please verify they are correct.</li>
	<?php }
	else { ?>
	<li>Shipping charges were not recalculated for this order. Please verify they are correct.</li>
	<?php } ?>
	<li>Please make sure this order's status is set correctly.</li>
	<?php if ($_GET['cc_payment_goes'] == 'parent') { ?>
	<li>CC Payment stayed with parent - CC will need to be re-authorized for child order</li>
	<?php }
	elseif ($_GET['cc_payment_goes'] == 'child') { ?>
	<li>CC Payment went to child - CC will need to be re-authorized for parent order</li>
	<?php } ?>
</ul>
<h3>New Order: <a href="orders_new.php?oID=<?= $_GET['new_order_id']; ?>&action=edit"><?= $_GET['new_order_id']; ?></a></h3>
<ul>
	<li>Please make sure this order's status is set correctly.</li>
</ul>
