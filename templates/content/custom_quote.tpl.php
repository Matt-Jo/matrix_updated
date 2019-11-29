<br><br><br><br>
<div style="text-align:center">
	<?php if (isset($error)) { ?>
	<p><strong>Error accessing your custom quote!</strong></p>
	<p>Please reply to the email you received from your sales rep with this error message:</p>
	<p style="color:#c00"><?= $error; ?></p>
	<?php }
	else { ?>
	<p><strong>Items added to Shopping Cart!</strong></p>
	<p>Check out your <a href="shopping_cart.php">shopping cart</a> to see the customized quote that has been generated for you.</p>
	<?php } ?>
</div>
