<?php
$address_tpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
?>
<style>
	#hiddenSubmit { visibility:hidden; }
	dialog { position:absolute; margin:auto; top:0; right:0; bottom:0; left:0; height:500px; width:280px; border:1px solid #dadada; font-family:sans-serif; padding:5px 10px 20px 20px; border-radius:3px; }
	.cardDialog h3 { background-color: #23ce2a; }
	#card-number { border:1px solid #333; -webkit-transition:border-color 160ms; transition:border-color 160ms; width:150px; height:15px; }
	#expiration-date { border:1px solid #333; -webkit-transition:border-color 160ms; transition:border-color 160ms; width:150px; height:15px; }
	#cvv { border:1px solid #333; -webkit-transition:border-color 160ms; transition:border-color 160ms; width:150px; height:15px; }
	#cardholder-nname { border:1px solid #333; -webkit-transition:border-color 160ms; transition:border-color 160ms; width:150px; height:15px; }
	#card-number.braintree-hosted-fields-focused { outline:none; border-color:#9ecaed; box-shadow:0 0 10px #9ecaed; }
	#card-number.braintree-hosted-fields-invalid { border-color:tomato; }
	#card-number.braintree-hosted-fields-valid { border-color:limegreen; }
	#expiration-date.braintree-hosted-fields-focused { outline:none; border-color:#9ecaed; box-shadow:0 0 10px #9ecaed; }
	#expiration-date.braintree-hosted-fields-invalid { border-color:tomato; }
	#expiration-date.braintree-hosted-fields-valid { border-color:limegreen; }
	#cvv.braintree-hosted-fields-focused { outline:none; border-color:#9ecaed; box-shadow:0 0 10px #9ecaed; }
	#cvv.braintree-hosted-fields-invalid { border-color:tomato; }
	#cvv.braintree-hosted-fields-valid { border-color:limegreen; }
	#cardholder-name.braintree-hosted-fields-focused { outline:none; border-color:#9ecaed; box-shadow:0 0 10px #9ecaed; }
	#cardholder-name.braintree-hosted-fields-invalid { border-color:tomato; }
	#cardholder-name.braintree-hosted-fields-valid { border-color:limegreen; }
	.cardDiv { padding:10px; margin:5px; }
	.tbl table { border-collapse:collapse !important; border-bottom-width:2px ; table-layout:fixed; margin-bottom:10px; }
	.tbl td, th { border:1px solid #ddd; padding:0.5rem; text-align:left; display:table-cell; }
	.tbl td { color:#23ce2a; }
	.tbl th { background-color:#69969c;  }
	.tbl tbody > tr:nth-of-type(odd) { background-color:#f9f9f9; }
	.addCard { background-color:#458a79; color:white; border-radius:5%; }
	.dlgButton button { color:blue; }
	#btnList { list-style-type:none !important; margin:0; padding:0; }
	#btnList > li { display:inline !important;  }
</style>

<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">Order Information</div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear:both;"></div>

	<div class="main rounded-corners">
        <div class="grid grid-pad">    
            <div class="col-1-1">
			<input type="hidden" id="brainTreeToken" value="<?= $_SESSION['braintree_client_token']; ?>">
			<input type="hidden" id="orderId" value="<?= $_SESSION['order_id']; ?>">
	
			<?php  if (isset($_SESSION['card_create_error'])) { ?>
			<input type="hidden" id="cardErrMsg" value="<?= $_SESSION['card_create_error']; ?>">
			<?php } ?>
	
			<table border="0" width="100%" cellspacing="0" cellpadding="8">
				<tr>
					<td>
						<table border="0" width="100%" cellspacing="0" cellpadding="2">
							<tr>
								<td class="main" colspan="2"><b>Order #<?= $ckorder->id(); ?> <small>(<?= $ckorder->get_header('orders_status_name'); ?>)</small></b></td>
							</tr>
							<tr>
								<td class="smallText">Order Date: <?= $ckorder->get_header('date_purchased')->long_date(); ?></td>
								<td class="smallText" align="right">Order Total: <?= CK\text::monetize($ckorder->get_simple_totals('total')); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<?php $customer = $ckorder->get_customer();
				if (!$ckorder->is('released')) {
					if ($customer->cannot_place_any_order()) { ?>
				<tr>
					<td style="color:#f00; font-weight:bold;">YOUR CREDIT TERMS HAVE BEEN SUSPENDED. ALL ORDERS WILL BE HELD UNTIL FURTHER NOTICE. PLEASE CONTACT OUR <a href="mailto:accounting@cablesandkits.com">ACCOUNTING DEPARTMENT</a> TO RESOLVE.</td>
				</tr>
					<?php }
					elseif ($customer->get_credit('credit_status_id') == ck_customer2::CREDIT_PREPAID) { ?>
				<tr>
					<td style="color:#f00; font-weight:bold;">Your credit terms have been TEMPORARILY SUSPENDED. You must prepay via credit card or paypal to release this order immediately. Please contact our <a href="mailto:accounting@cablesandkits.com">accounting department</a> to resolve any pending issues and have your terms reinstated.</td>
				</tr>
					<?php }
					elseif (!$customer->can_place_credit_order(0)) { ?>
				<tr>
					<td style="color:#f00; font-weight:bold;">This order places you over your credit limit.  You have <?= CK\text::monetize($customer->get_remaining_credit()); ?> available credit.  This order will be held until your previous invoices have been paid, or until you pre-pay for this order.</td>
				</tr>
					<?php }
					else { ?>
				<tr>
					<td style="color:#f00; font-weight:bold;">This transaction has been placed on credit hold.  Your account is still in good standing.  Please contact our <a href="mailto:accounting@cablesandkits.com">accounting department</a> to resolve the issue.</td>
				</tr>
					<?php }
				} ?>
				<tr>
					<td>
						<table border=0 cellspacing=0 cellpadding=0>
							<?php $invoices = prepared_query::fetch('SELECT acc.inv_order_id, acc.invoice_id FROM acc_invoices acc LEFT JOIN orders o ON o.orders_id = acc.inv_order_id WHERE o.customers_id = :customers_id AND o.orders_id = :orders_id GROUP BY acc.invoice_id', cardinality::SET, array(':customers_id' => $_SESSION['customer_id'], ':orders_id' => $_GET['order_id']));
	
							foreach ($invoices as $row) { ?>
							<tr>
								<td class="main">Invoice# <a target="_blank" href="/invoice.php?order_id=<?=$row['inv_order_id']?>&invId=<?=$row['invoice_id']?>"><?=$row['invoice_id']?></a><br></td>
							</tr>
							<?php } ?>
						</table>
					</td>
				</tr>
				<tr>
					<td><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
				</tr>
				<tr>
					<td width="100%" valign="top">
						<table border="0" width="100%" cellspacing="0" cellpadding="0">
							<tr>
								<td>
									<table border="0" width="100%" cellspacing="0" cellpadding="2">
										<tr>
											<td class="main" colspan="3"><b>Products</b></td>
										</tr>
										<tr>
											<td class="main" colspan="3">
												<table border="0" width="100%" cellspacing="0" cellpadding="0">
													<?php foreach ($ckorder->get_products() as $product) {
														if ($product['ipn']->is_supplies()) continue; ?>
													<tr>
														<td class="main" align="right" valign="top" width="30"><?= $product['quantity']; ?>&nbsp;x</td>
														<td class="main" valign="top"><?= $product['name']; ?></td>
														<td class="main" align="right" valign="top"><?= CK\text::monetize($product['final_price'] * $product['quantity']); ?></td>
													</tr>
													<?php } ?>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
							</tr>
							<tr>
								<td class="main"><b>Shipping Method</b></td>
							</tr>
							<tr>
								<td width="100%">
									<table width="100%">
										<tr>
											<td width="70%" class="main" align="left"><?= $ckorder->get_shipping_method_display('short'); ?></td>
											<td width="30%" class="main" align="right"><?= CK\text::monetize($ckorder->get_simple_totals('shipping')); ?></td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
							</tr>
							<tr>
								<td width="100%" valign="top">
									<table border="0" width="100%" cellspacing="0" cellpadding="2">
										<?php foreach ($ckorder->get_totals('consolidated') as $total) {
											if ($total['class'] == 'shipping') continue; ?>
										<tr>
											<td class="main" align="right" width="100%"><?= $total['title']; ?></td>
											<td class="main" align="right"><?= $total['display_value']; ?></td>
										</tr>
										<?php } ?>
									</table>
								</td>
							</tr>
							<tr>
								<td><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
							</tr>
							<tr>
								<td>
									<table width="100%">
										<tr>
											<td width="50%">
												<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
													<tr class="infoBoxContents">
														<td width="30%" valign="top">
															<table border="0" width="100%" cellspacing="0" cellpadding="2">
																<tr>
																	<td class="main"><b>Delivery Address</b></td>
																</tr>
																<tr>
																	<td class="main">
																		<?php $content = $ckorder->get_ship_address()->get_address_format_template(NULL, ',<span class="address-spacer">  <br></span>');
																		$address_tpl->content(DIR_FS_CATALOG.'includes/templates/partial-enhanced-address-format.mustache.html', $content);
																		//tep_address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br>'); ?>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
												</table>
											</td>
											<td width="50%">
												<table width="100%">
													<tr>
														<td width="100%">
															<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
																<tr class="infoBoxContents">
																	<td width="100%" valign="top">
																		<table border="0" width="100%" cellspacing="0" cellpadding="2">
																			<tr>
																				<td class="main"><b>Billing Address</b></td>
																			</tr>
																			<tr>
																				<td class="main">
																					<?php $content = $ckorder->get_bill_address()->get_address_format_template(NULL, ',<span class="address-spacer">  <br></span>');
																					$address_tpl->content(DIR_FS_CATALOG.'includes/templates/partial-enhanced-address-format.mustache.html', $content);
																					//tep_address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br>'); ?>
																				</td>
																			</tr>
																			<tr>
																				<td><strong><a href="checkout_payment_address.php">Edit address</a></strong></td>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
							</tr>
							<tr>
								<td class="main"><b>Ordered By</b></td>
							</tr>
							<tr>
								<td class="main"><?= $ckorder->get_prime_contact('fullname'); ?><br><br></td>
							</tr>
							<tr>
								<td class="main"><b>Payment Method</b></td>
							</tr>
							<tr>
								<td class="main"><strong><?= $ckorder->get_header('payment_method_label'); ?></strong></td>
							</tr>
							<tr>
								<td><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
							</tr>
							<tr>
								<td class="main"><b>Order History</b></td>
							</tr>
							<tr>
								<td width="100%">
									<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
										<tr class="infoBoxContents">
											<td valign="top" width="100%">
												<table border="0" width="100%" cellspacing="0" cellpadding="2">
													<?php $statuses = prepared_query::fetch('SELECT os.orders_status_name, osh.date_added, osh.comments FROM orders_status os JOIN orders_status_history osh ON os.orders_status_id = osh.orders_status_id WHERE osh.orders_id = :orders_id ORDER BY osh.date_added', cardinality::SET, [':orders_id' => $ckorder->id()]);
													foreach ($statuses as $status) {
														$date_added = new ck_datetime($status['date_added']); ?>
													<tr>
														<td class="main" valign="top" width="20%"><?= $date_added->short_date(); ?></td>
														<td class="main" valign="top" width="15%"><?= $status['orders_status_name']; ?></td>
														<td class="main" valign="top" width="65%"><?= empty($status['comments'])?'&nbsp;':nl2br(tep_output_string_protected($status['comments'])); ?></td>
													</tr>
													<?php } ?>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
				</tr>
				<tr>
					<td>
						<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
							<tr class="infoBoxContents">
								<td>
									<table border="0" width="100%" cellspacing="0" cellpadding="2">
										<tr>
											<td width="10"><?= tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
											<td align="right" class="main"><a href="javascript:popupWindow('/invoice.php?order_id=<?= $ckorder->id(); ?>')"><img src="/templates/Pixame_v1/images/buttons/english/button_printorder.gif" border="0" alt="Order Printable" title="Order Printable"></a></td>
											<td width="10"><?= tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>

<dialog id="window" style="border:1px solid #142f54;">
	<h3 style="background-color:#758AA8; color:white">Add Credit Card</h3>

	<p>Fields marked with (*) are required</p>

	<p id="err" style="background-color:yellow"></p>

	<form action="" id="add-creditcard-form" autocomplete="off">
		<input type="hidden" name="customer-id" value="<?= $_SESSION['braintree_customer_id']; ?>">

		<label for="customer-firstname">First Name (*)</label><br>
		<input id="firstName" type="text" name="customer-firstname"><br><br>

		<label for="customer-lastname">Last Name (*)</label><br>
		<input id="lastName" type="text" name="customer-lastname"><br><br>

		<!-- label for="customer-email">Email</label><br>
		<input id="Email" type="text" name="customer-email"><br><br -->

		<label for="card-number">Card Number (*)</label>
		<div id="card-number"></div><br>

		<label for="cvv">CVV (*)</label>
		<div id="cvv"></div><br>

		<label for="expiration-date">Expiration Date (*)</label>
		<div id="expiration-date"></div><!-- /
		<div id="expiration-year"></div-->
		<br>

		<input type="checkbox" id="privateCard" value="N">&nbsp;Hide card from others<br>
		<hr>
		<div style="padding-top:10px">
			<ul id="btnList">
				<li><button class="addCard" value="Pay">Add Card</button></li>
				<li><button class="addCard close-card">Close Dialog</button></li>
			</ul>
		</div>

		<input id="payment_method_nonce" hidden>
	</form>
</dialog>

<input type="hidden" id="amountDue" value="<?= $_SESSION['amountDue']; ?>">

<?php if (isset($_SESSION['braintree_customer_id'] )) { ?>
<input type="hidden" id="btCustId" value="<?= $_SESSION['braintree_customer_id'] ?>">
<?php }?>

<?php if (isset($_SESSION['order_id'] )) { ?>
<input type="hidden" id="orderId" value="<?= $_GET['order_id'] ?>">
<?php }?>

<form method="post" action="add_card_to_customer.php">
	<input type="hidden" id="newCard" name="newCard" value="true">
	<input type="hidden" id="custFname" name="custFname" value="">
	<input type="hidden" id="custLname" name="custLname" value="">
	<input type="hidden" id="custEmail" name="custEmail" value="">
	<input type="hidden" id="cardToken" name="cardToken" value="">
	<input type="hidden" id="cardIsPrivate" name="cardIsPrivate" value="">
	<input type="hidden" id="orderAmt" name="orderAmt" value="">
	<input type="hidden" id="braintreeCustId" name="braintreeCustId" value="">
	<input type="hidden" id="custOrderId" name="custOrderId" value="">
	<button id="hiddenSubmit" hidden>
</form>

<form method="post" action="pay_customer_order.php">
	<input type="hidden" id="customerId" name="customerId" value="">
	<input type="hidden" id="custOrderId2" name="custOrderId2" value="">
	<input type="hidden" id="orderAmt2" name="orderAmt2" value="">
	<input type="hidden" id="paymentToken" name="paymentToken" value="">
	<button id="newTransaction" hidden>
</form>

<script src="https://js.braintreegateway.com/web/3.6.3/js/client.min.js"></script>
<script src="https://js.braintreegateway.com/web/3.6.3/js/hosted-fields.min.js"></script>

<script type="text/javascript">
	var dialog = document.getElementById('window');
	$('#showCardError').hide();

	$(document).ready(function () {
		var custOrderId = $('#orderId').val();

		if (custOrderId != undefined) {
			$('#custOrderId').val(custOrderId);
			$('#custOrderId2').val(custOrderId);
		}

		var token = $('#brainTreeToken').val(),
			braintreeCustId = $('#braintreeCustId').val();

		msg = $('#cardErrMsg').val();

		if (msg != undefined && msg.length > 1) {
			alert(msg);
			$('#showCardError').show();
			//$("#showCardError").text(msg).fadeOut(4000);
		}

		$('#firstName').change(function(data) { $('#custFname').val(data.currentTarget.value); });
		$('#lastName').change(function(data) { $('#custLname').val(data.currentTarget.value); });
		$('#Email').change(function(data) { $('#custEmail').val(data.currentTarget.value); });

		$('#privateCard').change(function(data) {
			if (data.currentTarget.checked) $('#cardIsPrivate').val('T');
			else $('#cardIsPrivate').val('F');
		});

		var radios = $("input.cardRadioInput");

		firstObj = radios[0];
		$(firstObj).prop('checked', true); //check first object
		if (firstObj !== undefined) $('#paymentToken').val(firstObj.value);

		//braintree setup. Pass token we got from the hidden input
		braintree.client.create({ authorization: token }, function(err, clientInstance) {
			if (err) {
				console.log(err);
				return;
			}

			braintree.hostedFields.create({
				client: clientInstance,
				fields: {
					number: {
						selector: "#card-number"
					},
					cvv: {
						selector: "#cvv",
						type: 'password'
					},
					expirationDate: {
						selector: "#expiration-date",
						type: 'month'
						//placeholder: 'MM/YY'
					}
					/*expirationMonth: {
						selector: '#expiration-month',
						select: true
					},
					expirationYear: {
						selector: '#expiration-year',
						select: true
					}*/
				}
			},
			function(err, hostedFieldsInstance) {
				if (err) {
					$("#err").text("Error - Please enter all card details").fadeOut(3000);
				}
				else {
					//console.log(data);
					jQuery('#add-creditcard-form').submit(function(e) {
						e.preventDefault();
						hostedFieldsInstance.tokenize(function(err, payload) {
							if (err) {
								switch (err.code) {
									case 'HOSTED_FIELDS_FIELDS_EMPTY':
										$("#err").text('All fields are empty! Please fill out the form.').fadeOut(3000);
										break;
									case 'HOSTED_FIELDS_FIELDS_INVALID':
										$("#err").text('Some fields are invalid: '+err.details.invalidFieldKeys).fadeOut(3000);
										break;
									case 'HOSTED_FIELDS_FAILED_TOKENIZATION':
										$("#err").text('Tokenization failed server side. Is the card valid?').fadeOut(3000);
										break;
									case 'HOSTED_FIELDS_TOKENIZATION_NETWORK_ERROR':
										$("#err").text('Network error occurred when tokenizing.').fadeOut(3000);
										break;
									default:
										$("#err").text('Something bad happened! '+err).fadeOut(3000);
								}
							}
							else {
								$('#cardToken').val(payload.nonce);
								//$('#cardIsPrivate').val($('#privateCard').is(':checked')?'true':'false');

								if (braintreeCustId == undefined) {
									$('#newCard').val('true');
								}
								else {
									$('#newCard').val('false');
									$('#custBtId').val(braintreeCustId);
								}

								$('#add-svc-card').submit();
								dialog.close();
							}
						});
					});

					return;
					/*var nonce = result.nonce; //card is tokenized

					if (braintreeCustId == undefined) {
						$('#newCard').val('true');
					}
					else {
						$('#newCard').val('false');
						$('#custBtId').val(braintreeCustId);
					}

					$('#cardToken').val(nonce);
					$("#hiddenSubmit").click();
					dialog.close();*/
				}
			});
		});
	});

	$('#show').click(function () { dialog.show(); });

	//toggle main payment type selections too
	$(".cardRadioInput").click(function(obj) {
		var className = obj.currentTarget.className;
		var objId = obj.currentTarget.id;
		var tokens = objId.split('-');
		var radios = $("input."+className);
		radios.prop('checked', false); //uncheck other radios
		$('#'+objId).prop('checked', true);
		$('#paymentToken').val(tokens[1]) //set token in hidden field
		$('#payOrder').prop('disabled', false);				//enable button
	});

	$("#payOrder").click(function() {
		//we have a card selected, braintree id and amount...lets try and charge
		amtDue = $('#amountDue').val();
		brainTreeId = $('#btCustId').val();
		custOrderId = $('#custOrderId').val();

		$('#orderAmt2').val(amtDue);	//set amt due on post form
		$('#customerId').val(brainTreeId);	//set braintreeid in post form
		//$('#custOrderId').val(custOrderId);	//set orderId in post form

		$('#newTransaction').click();
	})

	$('#exit').click(function () {
		 dialog.close();
	});
</script>
