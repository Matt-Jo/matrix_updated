<br/><br/><br/><br/>
<div style="text-align:center">
<?php if (isset($error)):?>
<p><strong>Error accessing your purchase order!</strong></p>
<p>Please reply to the email you received from CablesAndKits.com with this error message:</p>
<p style="color:#c00"><?= $error; ?></p>
<?php else:?>
<p><strong>Thank you for confirming that you have received purchase order #: <?= $po_number; ?></strong></p>
<?php endif;?>
</div>
