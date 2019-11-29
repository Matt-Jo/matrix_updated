RMA#:&nbsp;<a href="https://www.cablesandkits.com/admin/rma-detail.php?id=<?= $this->rmaId; ?>"><?= $this->rmaId; ?></a><br><br>
Order#:&nbsp;<a href="https://www.cablesandkits.com/admin/orders_new.php?oID=<?= $this->orderId; ?>&action=edit"><?= $this->orderId; ?></a><br><br>
Customer:&nbsp;<?= $this->customer; ?><br><br>
For:&nbsp;<?= $this->disposition; ?><br><br><br><br>
Products Received:<br><br>
<?php foreach ($this->productsReceived as $rmaProduct) { ?>
IPN: <?= $rmaProduct['ipn']; ?>        Qty: <?= $rmaProduct['quantity']."\n"; ?><br>
<?php } ?>
