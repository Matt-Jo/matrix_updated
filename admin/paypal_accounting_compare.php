<?php
require('includes/application_top.php');

if (!empty($_POST['action']) && $_POST['action'] == 'compare') {
	$file = $_FILES['uploadedfile']['tmp_name'];
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" leftmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php
		require(DIR_WS_INCLUDES.'header.php');
	?>
	<!-- header_eof //-->
	<!-- body //-->
		<table border="0" width="100%" cellspacing="2" cellpadding="2">
			<tr>
				<td width="<?php echo BOX_WIDTH; ?>" valign="top">
					<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
						<!-- left_navigation //-->
						<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
						<!-- left_navigation_eof //-->
					</table>
				</td>
				<!-- body_text //-->
				<td width="100%" valign="top">
<!------------------------------------------------------------------- -->
<form enctype="multipart/form-data" action="<?php $_SERVER['PHP_SELF']; ?>" method="POST">
	<input type="hidden" name="action" value="compare" />
	Choose a file to upload: <input name="uploadedfile" type="file" /><br />
	<input type="submit" value="Upload File" />
</form>
<?php
if (isset($file)) {
	$columnHeaders = array();
	$orders = array();
	$orphans = array();
	$orphancounter = 0;
	$dates = array();
	$handle = fopen($file, 'r');
	$row = 0;

	while (($data = fgetcsv($handle)) !== false) {
		$row++;
		// read column headers
		if ($row == 1) {
			foreach ($data as $offset => $columnHeader) {
				if ($columnHeader != '') {
					$columnHeaders[strtolower($columnHeader)] = $offset;
				}
			}
		}

		// skip these transaction types that are meaningless to accounting
		if (in_array($data[$columnHeaders['type']], array('Authorization', 'Void'))) {
			continue;
		}

		$amount = str_replace(',', '', $data[$columnHeaders['gross']]);

		$transaction = prepared_query::fetch("SELECT ccl.order_id, ot.value, o.orders_status FROM credit_card_log ccl INNER JOIN orders_total ot ON ot.orders_id = ccl.order_id INNER JOIN orders o on o.orders_id = ccl.order_id WHERE ot.class = 'ot_total' AND ccl.transaction_id = ? AND result = 'A' LIMIT 1", cardinality::ROW, $data[$columnHeaders['transaction id']]);
		if (!empty($transaction)) {
            $dates[$data[$columnHeaders['date']]] += $amount;

            $orderValue = ($transaction['orders_status'] == 6) ? 0 : $transaction['value'];

            $transactionData = array(
                'date'		=> $data[$columnHeaders['date']],
                'name'		=> $data[$columnHeaders['name']],
                'type'		=> $data[$columnHeaders['balance impact']],
                'amount'		=> $amount,
                'order_value' => $orderValue,
            );

            $orders[$transaction['order_id']][$data[$columnHeaders['transaction id']]] = $transactionData;
		} else {
			$orphans[$data[$columnHeaders['type']]][$data[$columnHeaders['transaction id']]] = array(
				'date'	=> $data[$columnHeaders['date']],
				'name'	=> $data[$columnHeaders['name']],
				'type'	=> $data[$columnHeaders['balance impact']],
				'amount' => $amount,
			);
			$orphancounter++;
		}
	}
?>
	<h4>Totals</h4>
	<table cellpadding="3" cellspacing="3">
	<?php foreach ($dates as $date => $amount): ?>
		<tr>
			<td><?= $date; ?></td><td><?php echo money_format('%n', $amount); ?></td>
		</tr>
	<?php endforeach; ?>
	</table>
	<br>
	<h4>Stats</h4>
	<span class="main"><b>Matches</b>:&nbsp;<?php echo count($orders);?></span><br>
	<span class="main"><b>Orphans</b>:&nbsp;<?= $orphancounter; ?></span><br>
	<table cellpadding="6px" cellspacing="0" border="0">
		<thead>
			<tr>
				<th>Order Number</th>
				<th>Transaction ID</th>
				<th>Type</th>
				<th>Name</th>
				<th>Date</th>
				<th>Amount</th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ($orders as $orderId => $transactions) {
			$data = current($transactions);
			$transactionId = key($transactions);
		?>
		<tr<?php if (count($transactions) == 1 && $data['amount'] != $data['order_value']) echo ' style="background-color: #EEE854"';?>>
			<td><a href="orders_new.php?oID=<?php echo urlencode($orderId);?>&action=edit" target="_BLANK"><?= $orderId; ?></a></td>
			<td><?= $transactionId; ?></td>
			<td><?= $data['type']; ?></td>
			<td><?= $data['name']; ?></td>
			<td><?= $data['date']; ?></td>
			<td style="text-align:right;<?php if (count($transactions) == 1 && $data['amount'] != $data['order_value']) echo ' background-color: #E02817; text-align: right;';?>"><?php echo money_format('%n', $data['amount']); ?></td>
		</tr>
		<?php
			if (count($transactions) > 1) {
				$total = $data['amount'];
				array_shift($transactions);
				foreach ($transactions as $transactionId => $data) {
					$total += $data['amount'];
		?>
		<tr>
			<td>&nbsp;</td>
			<td><?= $transactionId; ?></td>
			<td><?= $data['type']; ?></td>
			<td><?= $data['name']; ?></td>
			<td><?= $data['date']; ?></td>
			<td style="text-align: right;"><?php echo money_format('%n', $data['amount']); ?></td>
		</tr>
		<?php
				}
		?>
		<tr<?php if ($total != $data['order_value']) echo ' style="background-color: #EEE854"';?>>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td style="border-top: 1px solid black; text-align: right;<?php if ($total != $data['order_value']) echo ' background-color: #E02817;';?>"><?php echo money_format('%n', $total); ?></td>
		</tr>
		<?php
			}
		}
		?>
		</tbody>
		<?php
		foreach ($orphans as $category => $transactions) {
		?>
		<thead>
			<tr>
				<th colspan="6"><?= $category; ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($transactions as $transactionId => $data) { ?>
		<tr>
			<td>&nbsp;</td>
			<td><?= $transactionId; ?></td>
			<td><?= $data['date']; ?></td>
			<td><?= $data['name']; ?></td>
			<td><?= $data['type']; ?></td>
			<td style="text-align: right;"><?php echo money_format('%n', $data['amount']); ?></td>
		</tr>
		<?php } ?>
		</tbody>
		<?php } ?>
	</table>
<?php
}
?>
<!------------------------------------------------------------------- -->
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
<html>
