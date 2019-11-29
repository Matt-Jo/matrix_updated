<?php
require('includes/application_top.php');

$amazon = $_REQUEST['customer_id']==96285?TRUE:FALSE;

$customer = new ck_customer2($_REQUEST['customer_id']);

/*if (isset($_POST['payment_method_id'])) {
	//checkmo, accountcredit, creditcard_pp, credit_memo, adjustment, cash,amazon, paypal, wire, write_off

	//we have amount, customer id ..create authorisation 
	$data = [
		'amount' => $_REQUEST['amount'],
		'customerId' => $customer->get_header('braintree_customer_id'),
		'token' => $_POST['payment_ref'],
		'authorization' => false,
		'orderId' => '0',
		'methodType' => $_REQUEST['payment_method_id']
	];
 				
 	$paymentSvcApi = new PaymentSvcApi();       

 	//call payment service
 	$result = json_decode($paymentSvcApi->authorizeMiscTransaction($data), true);
 	
	//payment_method_id - string - cash, checkmo,accountcredit, adjustment, 
	$payment_id = ck_payment::legacy_insert_credit($customer->id(), null, $_REQUEST['payment_method_id'], $_POST['payment_ref'], $_REQUEST['amount'], $_POST['date']);
					
	ck_payment::legacy_insert_note($_POST['reason'], 'acc_payments', $payment_id);
	
	$messageStack->add_session("Payment {$payment_id} inserted successfully.", 'success');

	// do not redirect to the next page if there are no outstanding invoices
	$invoices = $customer->get_outstanding_invoices_direct();
	if (count($invoices) > 0) CK\fn::redirect_and_exit('/admin/acc_apply_credit2.php?customer_id='.$customer->id().'&payment_id='.$payment_id);
	else CK\fn::redirect_and_exit('/admin/acc_dashboard.php');
}*/

$row = prepared_query::fetch('SELECT ab.entry_company FROM address_book ab, customers c WHERE c.customers_id= :customers_id AND c.customers_default_address_id = ab.address_book_id', cardinality::ROW, [':customers_id' => $customer->id()]);

if (!strlen($row['entry_company'])) {
	$row = prepared_query::fetch('SELECT customers_firstname, customers_lastname from customers where customers_id= :customers_id', cardinality::ROW, [':customers_id' => $customer->id()]);
	$name = $row['customers_firstname'].' '.$row['customers_lastname'];
}
else $name = $row['entry_company'];

$payment_methods = prepared_query::fetch('SELECT * FROM payment_method WHERE legacy = 0 ORDER BY label ASC', cardinality::SET); ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
		<title><?php echo TITLE; ?></title>
		<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
	</head>
	<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#date').datepicker({dateFormat: 'yy-mm-dd'});
		});
	</script>
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
					<!--------------------------------------------------------------------->
					<div style="width:800px; border:1px solid #333333; margin:20px;">
						<div class="acc_title">Enter Payment/Credit</div>
						<div class="acc_content_box">
							<form enctype="multipart/form-data" method="POST" action="/admin/acc_apply_credit2.php?customer_id=<?= $customer->id(); ?>">
								<?php if ($customer->id() == 96285) { //only show this option if 'Amazon Marketplace - CK Amazon' is selected ?>
								<div style="display:block; margin-bottom:15px;">
									<label for="payment_upload"><strong>Upload Amazon Payment Spreadsheet:</strong></label>
									<input type="file" id="payment_upload" name="payment_upload" accept=".xlsx">
								</div>
								<?php } ?>
								<input type="hidden" name="customer_id" value="<?= $customer->id(); ?>">
								<div style="float:left; width:300px; height:40px;"><strong>Company:</strong> <?= $name; ?></div>
								<div style="float:left; width:150px; height:40px;"><strong>Net Balance:</strong> <?= CK\text::monetize($customer->get_customer_net_balance()); ?></div>
								<div style="clear:left; height:20px;"><strong>Enter new payment/credit:</strong></div>
								<div style="float:left; width:165px; padding-left:50px;">
									<strong>Type</strong><br>
									<select name="payment_method_id" id="credit_type">
										<?php foreach ($payment_methods as $payment_method) { ?>
										<option value="<?= $payment_method['code']; ?>" <?php if ($customer->id() == 96285 && $payment_method['code'] == 'wire') echo ' selected'; ?>><?= $payment_method['label']; ?></option>
										<?php } ?>
									</select>
								</div>
								<div style="float:left;">
									<strong>Amount</strong><br>
									<input name="amount" type="text" id="amount" size="8">
								</div>
								<div style="float:left; padding-left:20px;">
									<strong>Ref #</strong><br>
									<input name="payment_ref" type="text" id="amount" size="20">
								</div>
								<div style="float:left; padding-left: 20px;">
										<strong>Date</strong><br>
										<input name="date" type="text" id="date" size="10" value="<?= date('Y-m-d'); ?>">
								</div>
								<div style="clear:left; padding:10px 10px 10px 50px;">
									<strong>Notes</strong><br>
									<textarea name="reason" id="reason" rows="6" cols="70"></textarea>
								</div>
								<div>
									<input type="submit" name="payment_submit" value="Enter Payment/Credit"><input type="reset" value="Cancel" name="Cancel" onclick="javascript:history.go(-1);">
								</div>
							</form>
						</div>
					</div>
					<!------------------------------------------------------------------- -->
				</td>
				<!-- body_text_eof //-->
			</tr>
		</table>
		<!-- body_eof //-->
	</body>
</html>
