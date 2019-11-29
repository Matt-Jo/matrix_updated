<?php
require('includes/application_top.php');

if (!isset($_REQUEST['customer_id'])) throw new Exception('Missing customer_id');

$customer = new ck_customer2($_REQUEST['customer_id']);

if (isset($_POST['customer_id']) && isset($_POST['payment_id'])) {
	$applyType = $_POST['apply_type'];

	$unapplied_amount = 0;

	$payments = $customer->get_unapplied_payments();
	foreach ($payments as $pmt) {
		if ($pmt['payment_id'] != $_POST['payment_id']) continue;
		$unapplied_amount = $pmt['unapplied_amount'];
	}

	if ($applyType == 'other') $amount = min($unapplied_amount, $_POST['amount']);
	else $amount = $unapplied_amount;

	$data = [
		'header' => [
			'customer_id' => $customer->id(),
			'paid_in_full' => 1,
			'inv_date' => ck_invoice::get_accounting_date(TRUE)->format('Y-m-d H:i:s'),
		],
		'totals' => [[
			'invoice_total_line_type' => 'ot_total',
			'invoice_total_description' => 'Total:',
			'invoice_total_price' => $amount,
		]],
		'payment_allocations' => [[
			'payment_id' => $_POST['payment_id'],
			'credit_amount' => $amount,
			'customer_id' => $customer->id()
		]],
		'note' => @$_POST['notes']
	];

	try {
		$invoice = ck_invoice::create($data);

		$messageStack->add_session('Payment '.$_POST['payment_id'].' refunded for '.CK\text::monetize($amount), 'success');

		CK\fn::redirect_and_exit('/admin/acc_dashboard.php');
	}
	catch (Exception $e) {
		$messageStack->add_session($e->getMessage(), 'error');
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
		<script language="javascript" src="includes/menu.js"></script>
		<script language="javascript" src="includes/general.js"></script>
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php
		require(DIR_WS_INCLUDES.'header.php');
	?>
	<!-- header_eof //-->
	<script type="text/javascript">
		function MaxSelect() {
			document.getElementById("apply_type_max").checked=true;
		}

		jQuery(document).ready(function($) {
			$('form#refund').submit(function(event) {
				var paymentId = $('input:[name=payment_id]:radio:checked').val();

				if (paymentId == undefined) {
					alert('A credit must be selected for refund');
					return false;
				}
			});
		});
	</script>
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
<div style="width:800px; border:1px solid #333333; margin:20px;">
<div class="acc_title">Issue Refund</div>
<form id="refund" method="POST" action="">
<input type="hidden" name="customer_id" value="<?= $_REQUEST['customer_id']; ?>">
<div class="acc_content_box">
	<div style="float:left; width:300px; height:25px;"><strong>Company:</strong> <?= $customer->get_display_label(); ?></div>
	<div style="clear:left; height:20px;"><strong>Choose credit for refund:</strong></div>
	<div style="float:left; width:120px; padding-left:50px;"><strong>Type</strong></div>
	<div style="float:left; width:160px;"><strong>Date</strong></div>
	<div style="float:left; width:130px;"><strong>Amount</strong></div>
	<div style="float:left; width:130px;"><strong>Available</strong></div>

	<?php $credits = $customer->get_unapplied_payments();
	if (count($credits)) {
	foreach ($credits as $credit) {?>
	<div style="clear:left; float:left; width:30px; padding-left:10px;"><input type="radio" name="payment_id" onClick="MaxSelect();" value="<?= $credit['payment_id']; ?>"></div>
	<div style="float:left; width:120px; padding-top:5px; padding-left:10px; "><?= $credit['payment_method_label']; ?></div>
	<div style="float:left; width:160px; padding-top:5px;"><?= $credit['payment_date']->format('Y-m-d H:i:s'); ?></div>
	<div style="float:left; width:130px; padding-top:5px;"><?= money_format('%n', $credit['payment_amount']); ?></div>
	<div style="float:left; width:130px; padding-top:5px;"><?= money_format('%n', $credit['unapplied_amount']); ?></div>
		<?php }
		}
		else {?>
		<div style="clear:left; float:left; width:200px; padding-left:30px;">No credits found.</div>
		<?php } ?>

		<div style="clear:left; height:20px; padding-top:10px;"><strong>Amount to refund:</strong></div>
	<div style="clear:left; float:left; width:40px; padding-left:10px;"><input type="radio" id="apply_type_max" name="apply_type" value="max" checked="checked" /></div>
	<div style="float:left; padding-top:5px; width:300px;">Maximum amount possible</div><br>
	<div style="clear:both; float:left; width:40px; padding-left:10px; padding-top:5px;"><input type="radio" name="apply_type" value="other" /></div>
	<div style="float:left; padding-top:5px; width:300px;">Other Amount: <input type="text" size="10" name="amount" id="amount" /></div>
<div style="clear:both; padding-top:20px;">
<input type="submit" value="Refund Credit"> <input type="button" value="Cancel" name="Cancel" onclick="javascript:history.go(-1);">
</div>
</div>
</div>
</form>
<!------------------------------------------------------------------- -->
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
<html>
