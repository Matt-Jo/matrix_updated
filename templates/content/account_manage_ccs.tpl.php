<style>
	.cardDialog h3 { background-color:#23ce2a; }

	#hiddenSubmit { visibility:hidden; }
	#card-number { border:1px solid #333; -webkit-transition:border-color 160ms; transition:border-color 160ms; width:150px; height:15px; }
	#expiration-date { border:1px solid #333; -webkit-transition:border-color 160ms; transition:border-color 160ms; width:150px; height:15px; }
	#cvv { border:1px solid #333; -webkit-transition:border-color 160ms; transition:border-color 160ms; width:150px; height:15px; }
	#cardholder-nname { border:1px solid #333; -webkit-transition:border-color 160ms; transition:border-color 160ms; width:150px; height:15px; }
	#card-number.braintree-hosted-fields-focused { /*border-color:#777;*/ outline:none; border-color:#9ecaed; box-shadow:0 0 10px #9ecaed; }
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
	.tbl td { color:#23ce2a; }
	.tbl th { background-color:#69969c; }
	.tbl tbody > tr:nth-of-type(odd) { background-color:#f9f9f9; }
	.addCard { background-color:#458a79; color:white; border-radius:5%; }
	.delete-card { background-color:#458a79; color:white; border-radius:5%; padding:2px 8px; border:1px solid #ccc; }
	.dlgButton button { color:blue; }
	#btnList { list-style-type:none !important; margin:0; padding:0; }
	#btnList > li { display:inline !important; }
	.errorMsg { border:1px solid red; background-color:yellow; color:black; padding:5px; margin:5px; }
</style>

<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">My Credit Cards</div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear:both;"></div>

	<div class="main rounded-corners">
		<div class="grid grid-pad edit_form">
		<input type="hidden" id="brainTreeToken" value="<?= $_SESSION['braintree_client_token']; ?>">

		<?php if (isset($_SESSION['card_create_error'])) { ?>
		<input type="hidden" id="cardErrMsg" value="<?= $_SESSION['card_create_error']; ?>">
		<?php } ?>

		<?php if (isset($_SESSION['matrix_cust_id'])) { ?>
		<input type="hidden" id="matrixCustId" value="<?= $_SESSION['matrix_cust_id']; ?>">
		<?php } ?>

		<?php if (isset($_SESSION['braintree_customer_id'])) { ?>
		<input type="hidden" id="braintreeCustId" value="<?= $_SESSION['braintree_customer_id']; ?>">
		<?php } ?>

		<div class="col-1-1">
			<?php //get stored cards from session
			if (isset($_SESSION['customer_cards']) && count($_SESSION['customer_cards']) > 0) {
				$ccs = $_SESSION['customer_cards'];	?>
			<div id="showCardError" style="border:1px solid red; background-color:yellow; color:black; padding:5px; margin:5px; display:none;"></div>
			<table class=" table-md" width="100%">
				<thead class="tbl">
					<th class="tbl">Card Type</th>
					<th class="tbl">Last 4</th>
					<th class="tbl">Expiration </th>
					<th class="tbl">Cardholder Name </th>
					<th class="tbl">Delete</th>
				</thead>
				<?php foreach ($ccs as $cc) { ?>
				<tr class="tbl">
					<td class="tbl" title="Card Type"><img width="50" height="30" src="<?= $cc['imageUrl']; ?>"></td>
					<td class="tbl" title="Last 4"><?= $cc['lastFour']; ?></td>
					<td class="tbl" title="Expiration"><?= $cc['expirationDate']; ?></td>
					<td class="tbl" title="Cardholder Name"><?= !empty($cc['cardholderName'])?$cc['cardholderName']:''; ?></td>
					<td class="tbl" title="Delete"><a class="delete-card" href="/account_manage_ccs.php?action=delete&amp;token=<?= $cc['token']; ?>">Delete Card</a></td>
				</tr>
				<?php } ?>
			</table>
			<?php }
			else { ?>
			<h3>You have no stored CCs on file.</h3>
			<?php } ?>

			<div style="padding-top:10px">
				<ul id="btnList">
					<li><a href="/account.php"><img src="/templates/Pixame_v1/images/buttons/english/button_back.gif" border="0" alt="Back" title="Back"></a></li>
					<!--li><button type="button" class="addCard add-card">Create New Card</button></li-->
				</ul>
			</div>
		</div>

		<?php $_SESSION['card_create_error'] = null; ?>
	</div>

	<div class="ck_rounded_box_bottom ck_rounded_box_wide_bottom_945" style="width:945px; background-image:url(./templates/Pixame_v1/images/login/featured_bottom.gif);"></div>
</div>

<style>
	div#create-credit-card { position:absolute; margin:0px auto; padding:0px; top:200px; right:0px; /*bottom:0;*/ left:0px; /*height:500px;*/ width:280px; border:1px solid #142f54; font-family:sans-serif; border-radius:3px; background-color:#fff; display:none; }
	#create-credit-card h3.dialog-title { background-color:#758AA8; color:white; margin:0px; padding:3px 5px; }
	#create-credit-card p { margin:10px 8px; }
	#create-credit-card #err { background-color:yellow; }
	#create-credit-card #add-creditcard-form { display:block; margin:10px; padding:0px; }
	#create-credit-card .req { color:#f66; }
</style>

<div id="create-credit-card">
	<h3 class="dialog-title">Add Credit Card</h3>

	<p>Fields marked with <span class="req">(*)</span> are required</p>

	<p id="err"></p>

	<form action="" id="add-creditcard-form" autocomplete="off">
		<input type="hidden" name="customer-id" value="<?= $_SESSION['braintree_customer_id']; ?>">

		<label for="customer-firstname" class="req">*First Name</label><br>
		<input id="firstName" type="text" name="customer-firstname"><br><br>

		<label for="customer-lastname" class="req">*Last Name</label><br>
		<input id="lastName" type="text" name="customer-lastname"><br><br>

		<label for="customer-email">Email</label><br>
		<input id="Email" type="text" name="customer-email" value="<?= $_SESSION['cart']->get_customer()->get_header('email_address'); ?>" style="width:250px;"><br><br>

		<label for="card-number" class="req">*Card Number</label>
		<div id="card-number"></div><br>

		<label for="cvv" class="req">*CVV</label>
		<div id="cvv"></div><br>

		<label for="expiration-date" class="req">*Expiration Date</label>
		<div id="expiration-date"></div><!-- /
		<div id="expiration-year"></div-->
		<br>

		<input type="checkbox" id="privateCard" value="N">&nbsp;Hide card from other users authorized on this account.<br>
		<hr>
		<div>
			<ul id="btnList">
				<li><button class="addCard" value="Pay">Add Card</button></li>
				<li><button class="addCard close-card">Close Dialog</button></li>
			</ul>
		</div>

		<input id="payment_method_nonce" hidden>
	</form>
</div>

<!-- hidden form to post to php -->
<form method="post" id="add-svc-card" action="add_card_to_customer.php">
	<input type="hidden" id="custFname" name="custFname" value="">
	<input type="hidden" id="custLname" name="custLname" value="">
	<input type="hidden" id="custEmail" name="custEmail" value="<?= $_SESSION['cart']->get_customer()->get_header('email_address'); ?>">
	<input type="hidden" id="cardToken" name="cardToken" value="">
	<input type="hidden" id="cardIsPrivate" name="cardIsPrivate" value="">
	<input type="hidden" id="newCard" name="newCard" value="">
	<input type="hidden" id="custBtId" name="custBtId" value="">
	<button id="hiddenSubmit">
</form>

<script src="https://js.braintreegateway.com/web/3.6.3/js/client.min.js"></script>
<script src="https://js.braintreegateway.com/web/3.6.3/js/hosted-fields.min.js"></script>

<script>
	$('#showCardError').hide();

	jQuery('.delete-card').click(function(e) {
		if (!confirm('Are you certain you want to delete this card?')) e.preventDefault();
	});

	$(document).ready(function () {
		//get token from hidden input
		var token = $('#brainTreeToken').val(),
			braintreeCustId = $('#braintreeCustId').val(),
			matrixCustId = $('#matrixCustId').val();

		msg = $('#cardErrMsg').val();

		if (msg != undefined && msg.length > 1) $('#showCardError').html(msg).show(); //$("#showCardError").text(msg).fadeOut(4000);

		$('#firstName').change(function(data) { $('#custFname').val(data.currentTarget.value); });
		$('#lastName').change(function(data) { $('#custLname').val(data.currentTarget.value); });
		$('#Email').change(function(data) { $('#custEmail').val(data.currentTarget.value); });
		$('#privateCard').change(function(data) {
			if (data.currentTarget.checked) $('#cardIsPrivate').val('T');
			else $('#cardIsPrivate').val('F');
		});
		$('#unhideCard').hover(function() { /*show a tool tip here*/ })

		//show modal
		$('.add-card').click(function() { jQuery('#create-credit-card').show(); });
		//hide modal
		$('.close-card').click(function() { jQuery('#create-credit-card').hide(); });

		$('#unhideCard').change(function(data) {
			var cardToken = data.currentTarget.name;

			if (jQuery('#create-credit-card').confirm("This action will allow other users in your group to view this card. Continue?")) {
				$.ajax({
					url: 'add_card_to_customer.php',
					type: 'POST',
					data: { AjaxCall:'true', token:cardToken },
					timeout: 10000,
					success: function(data) {
						//debugger;
						//location.reload();
					},
					error: function(obj) {
						alert('There was a communication error. Please wait at least 1 minute and reload the screen to see if it went through, and try again if necessary.');
					}
				});
			}
		});

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
						//type: 'month'
						placeholder: 'MM/YY'
					},
					/*firstName: {
						selector: '#firstName'
					},
					lastName: {
						selector: '#lastName'
					},
					email: {
						selector: '#Email'
					}*/
					/*expirationMonth: {
						selector: '#expiration-month',
						select: true
					},
					expirationYear: {
						selector: '#expiration-year',
						select: true
					}*/
				},
				
			},
			function(err, hostedFieldsInstance) {
				if (err) {
					//debugger;
					$("#err").text("Error - Please enter all card details").fadeOut(3000);
				}
				else {
					//console.log(data);
					//debugger;
					jQuery('#add-creditcard-form').submit(function(e) {
						e.preventDefault();
						hostedFieldsInstance.tokenize(/*{ vault: true },*/ function(err, payload) {
							//console.log(err);
							//console.log(payload);
							//return;
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
								jQuery('#create-credit-card').hide();
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
					jQuery('#create-credit-card').hide();*/
				}
			});
		});
	});
</script>
