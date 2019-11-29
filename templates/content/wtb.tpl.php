<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<!--<div class="ck_rounded_box">-->
<!--	<div class="ck_rounded_box_top"></div>-->
	<div style="background-color:#fff;">
		<div style="padding:15px;">
			<div id="vendor-portal-header" style="background-color:#fff; margin-bottom:15px;">
				<div id="ck-vendor-header" style="text-align:center;">
					Vendor Portal<sub id="vp-beta"><small>BETA</small></sub>
				</div>
				<div class="section-content">
					<div id="vp-nav">
						<?php $vp_lookup = FALSE;
							if (!empty($_SESSION['customer_id'])) {
							$customer = new ck_customer2($_SESSION['customer_id']);
							if ($customer->is_allowed('vendor_portal.accessory_finder')) $vp_lookup = TRUE;
						} ?>
						<a href="/VendorPortal">RE-source</a>
						<?= $vp_lookup?'| <a href="/VendorPortal/lookup">Accessory Finder</a>':''; ?>
					</div>
				</div>
			</div>
			<?php if (!empty($process_error)) { ?>
			<div style="font-size:1.1em; background-color:#fdd; color:#c00; font-weight:bold; padding:5px; margin-bottom:4px; border:1px solid #c00;">
				Your quote encountered the following issues:<br>
				<div><?= $process_error; ?></div>
			</div>
			<?php }
			if (!empty($rfq) && !$rfq['active']) { ?>
			<div style="font-size:1.1em; background-color:#fdd; color:#c00; font-weight:bold; padding:5px; margin-bottom:4px; border:1px solid #c00;">
				Please note that this quote request is no longer active.
			</div>
			<?php }
			if (empty($rfq) && empty($ipns)) { ?>
			<div style="font-size:1.1em; background-color:#fdd; color:#c00; font-weight:bold; padding:5px; margin-bottom:4px; border:1px solid #c00;">
				We could not find this request in our system.
			</div>
			<?php } ?>

			<style>
				#req-name { float:right; font-size:18px; color:#dd003c; font-weight:bold; }
				#request_details { white-space:pre; clear:both; margin:15px 0px; }
				#response-from { margin:10px 0px; font-size:15px; }
				.rfq_items { width:100%; margin:0px; }
				.rfq_items th, .rfq_items td { text-align:left; padding:3px 7px; }
				.rfq_items .tophead th { color:#386881; border-bottom:3px solid #000; }
				.row-0 td { background-color:#eee; }

				.rfq_items .req td { cursor:pointer; }

				.rfq_items .rep { display:none; }
				.rfq_items .rep td { padding:0px 1px 1px 0px; }

				.show-rep, .close-rep, .add-rep, .rem-rep { display:block; padding:0px 3px; font-weight:bold; line-height:80%; float:left; font-size:1.2em; text-align:center; }
				.show-rep:hover, .close-rep:hover, .add-rep:hover, .rem-rep:hover { text-decoration:none; }
				.show-rep, .close-rep, .add-rep { border:1px solid #000; background-color:#fff; }
				.show-rep, .add-rep { color:#e51937; }
				.close-rep { color:#386881; display:none; }
				.add-rep { /*border:1px solid #888; background-color:#aaa; color:#eee;*/ margin-right:10px; }
				.rem-rep { border:1px solid #000; background-color:#fff; color:#386881; }

				.rfq_response { border:1px solid #999; width:100%; }
				.rfq_response th { font-weight:normal; }
				.rfq_items .rep td .rfq_response td, .rfq_response td { padding:3px 7px; }
				.add-rep-container { color:#666; cursor:pointer; font-weight:bold; margin:0px; padding:1px 0px; }

				.format-currency { text-align:right; }
				#login { margin-top:200px !important; }
				#submit-vendor-portal { width:100%; }
				#submit-vendor-portal input { float:right; }

				@media all and (max-width:980px) {
					#submit-vendor-portal { margin-top:20px; text-align:center; }
					#submit-vendor-portal input { float:none; }
				}
			</style>
			<?php if ($action == 'saved') { ?>
			<div style="font-size:1.1em; background-color:#dfd; color:#00c; font-weight:bold; padding:5px; margin-bottom:4px; border:1px solid #0c0;">
				Thank you for submitting your response!
			</div>
			<br>
			<table border="0" cellspacing="0" cellpadding="0" class="rfq_response">
				<thead>
					<tr>
						<th width="13%">Qty</th>
						<th width="13%">Model #</th>
						<th width="13%">Condition</th>
						<th width="13%">Unit Price</th>
						<th width="48%">Notes</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($response_ipns as $idx => $ipn) { ?>
					<tr>
						<td nowrap><?= $ipn['quantity']; ?></td>
						<td nowrap><?= $ipn['model_alias']; ?></td>
						<td nowrap><?= $ipn['conditions_name']; ?></td>
						<td nowrap>$<?php echo number_format($ipn['price'], 2); ?></td>
						<td><?= $ipn['notes']; ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<br>
			<div style="padding-top:10px;">
				<?php echo $response['shipping_included']?'Free ground shipping included':''; ?>
			</div>
			<div style="margin:20px;font-weight:bold;">
				Please feel free to view and respond to our other open requests!
			</div>
			<?php }
			else { ?>
			<div id="req-name">
				<?php echo !empty($rfq_id)?$rfq['nickname']:(!empty($req_type)?'ALL OPEN '.strtoupper($req_type):'ALL OPEN WTB/RFQ'); ?>
			</div>
				<?php if (!empty($rfq_id)) { ?>
			<div id="request_details"><?= $rfq['request_details']; ?></div>
				<?php } ?>
			<form action="/VendorPortal/<?= $rfq_id; ?>" method="post" class="clearfix" id="wtb_form">
				<input type="hidden" name="action" value="save" id="action">
				<table border="0" cellspacing="0" cellpadding="0" class="rfq_items">
					<thead>
						<tr class="tophead">
							<th style="width:20px;"></th>
							<th style="width:275px;">MODEL #</th>
							<th style="width:40px;">QTY</th>
							<th style="width:80px;">CONDITION</th>
							<th>NOTES</th>
						</tr>
					</thead>
					<tbody>
						<?php if (!empty($ipns)) {
							foreach ($ipns as $idx => $ipn) { ?>
						<tr class="row-<?php echo $idx%2; ?> req closed" data-rpid="<?= $ipn['rfq_product_id']; ?>">
							<td><a href="#" class="show-rep rpid-<?= $ipn['rfq_product_id']; ?>" data-rpid="<?= $ipn['rfq_product_id']; ?>">+</a><a href="#" class="close-rep rpid-<?= $ipn['rfq_product_id']; ?>" data-rpid="<?= $ipn['rfq_product_id']; ?>">-</a></td>
							<td><?= $ipn['model_alias']; ?></td>
							<td><?php echo (empty($ipn['quantity'])?'ALL':$ipn['quantity']).($ipn['qtyplus']==1?'+':''); ?></td>
							<td><?php echo (empty($ipn['conditions_name'])?'ANY':$ipn['conditions_name']); ?></td>
							<td><?= $ipn['comment']; ?></td>
						</tr>
						<tr class="row-<?php echo $idx%2; ?> rep rpid-<?= $ipn['rfq_product_id']; ?>">
							<td></td>
							<td colspan="4">
								<table border="0" cellspacing="0" cellpadding="0" class="rfq_response">
									<thead>
										<tr>
											<th style="width:40px;">Qty</th>
											<th style="width:75px;">Condition</th>
											<th style="width:75px;">Unit Price</th>
											<th style="width:255px;">Notes</th>
											<th></th>
										</tr>
									</thead>
									<tbody model="<?= $ipn['model_alias']; ?>">
										<?php $idx = 0;
										if (isset($_POST['quantity'][$ipn['rfq_product_id']])) {
											foreach ($_POST['quantity'][$ipn['rfq_product_id']] as $idx => $qty) { ?>
										<tr class="reply-to-<?= $ipn['rfq_product_id']; ?> response">
											<td><input type="text" name="quantity[<?= $ipn['rfq_product_id']; ?>][<?= $idx; ?>]" value="<?= $qty; ?>" style="width:30px;" class="quantity"></td>
											<td class="condition_<?= $ipn['rfq_product_id']; ?>">
												<select size="1" name="condition[<?= $ipn['rfq_product_id']; ?>][<?= $idx; ?>]" class="condition">
													<option value=""></option>
													<?php foreach ($conditions as $condition) { ?>
													<option value="<?= $condition['conditions_id']; ?>" <?php echo $_POST['condition'][$ipn['rfq_product_id']][$idx]==$condition['conditions_id']?'selected':NULL; ?>><?= $condition['conditions_name']; ?></option>
													<?php } ?>
												</select>
											</td>
											<td>
												<input type="text" name="price[<?= $ipn['rfq_product_id']; ?>][<?= $idx; ?>]" value="<?php echo $_POST['price'][$ipn['rfq_product_id']][$idx]; ?>" style="width:50px;" placeholder="$" class="format-currency price">
											</td>
											<td>
												<textarea name="notes[<?= $ipn['rfq_product_id']; ?>][<?= $idx; ?>]" rows="2" cols="30" maxlength="255" style="width:initial;" class="notes"><?php echo $_POST['notes'][$ipn['rfq_product_id']][$idx]; ?></textarea>
											</td>
											<td>
												<a href="#" class="rem-rep" data-rpid="<?= $ipn['rfq_product_id']; ?>" data-repct="<?= $idx; ?>">x</td>
											</td>
										</tr>
											<?php }
										}
										else { ?>
										<tr class="reply-to-<?= $ipn['rfq_product_id']; ?> response">
											<td><input type="text" class="qty4<?= $ipn['rfq_product_id']; ?> quantity" name="quantity[<?= $ipn['rfq_product_id']; ?>][0]" value="" style="width:30px;"></td>
											<td class="condition_<?= $ipn['rfq_product_id']; ?>">
												<select size="1" name="condition[<?= $ipn['rfq_product_id']; ?>][0]" class="condition">
													<option value=""></option>
													<?php foreach ($conditions as $condition) { ?>
													<option value="<?= $condition['conditions_id']; ?>"><?= $condition['conditions_name']; ?></option>
													<?php } ?>
												</select>
											</td>
											<td>
												<input type="text" name="price[<?= $ipn['rfq_product_id']; ?>][0]" value="" style="width:50px;" placeholder="$" class="format-currency price">
											</td>
											<td>
												<textarea name="notes[<?= $ipn['rfq_product_id']; ?>][0]" rows="2" cols="30" maxlength="255" style="width:initial;" class="notes"></textarea>
											</td>
											<td>
												<a href="#" class="rem-rep" data-rpid="<?= $ipn['rfq_product_id']; ?>" data-repct="0">x</a>
											</td>
										</tr>
										<?php } ?>
										<tr>
											<td colspan="5" class="add-rep-container" data-rpid="<?= $ipn['rfq_product_id']; ?>" data-repct="<?= $idx; ?>">
												<a href="#" class="add-rep">+</a>
												Add other conditions and/or price breaks
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<?php if (!empty($_POST['quantity'][$ipn['rfq_product_id']][0])) { ?>
						<script>
							jQuery(document).ready(function() {
								jQuery('.show-rep.rpid-'+<?= $ipn['rfq_product_id']; ?>).click();
							});
						</script>
						<?php } ?>
							<?php }
						} ?>
					</tbody>
				</table>
				<br>
				<div style="font-weight:bold;">
					<?php
					$vendor_preference = null;
					if (empty($_SESSION['customer_extra_login_id']) && !empty($_SESSION['customer_id'])) {
						$vendor_preference = prepared_query::fetch('SELECT rfq_free_ground_shipping FROM customers c WHERE c.customers_id = ?', cardinality::SINGLE, $_SESSION['customer_id']);
					}
					elseif (!empty($_SESSION['customer_id'])) {
						$vendor_preference = prepared_query::fetch('SELECT rfq_free_ground_shipping FROM customers_extra_logins cel WHERE cel.customers_extra_logins_id = ?', cardinality::SINGLE, $_SESSION['customer_extra_login_id']);
					} ?>

					<div style="font-size:16px; text-align:center; width:100%;">Does this quote include free ground shipping?</div>
					<div style="text-align:center; width:100%;">[ <input type="radio" id="shipping_included_yes" name="shipping_included" value="1" <?= (isset($_POST['shipping_included'])&&$_POST['shipping_included']==1) || $vendor_preference == '1' ?'checked':''; ?>> Yes ] [ <input type="radio" id="shipping_included_no" name="shipping_included" value="0" <?= (isset($_POST['shipping_included'])&&$_POST['shipping_included']==0) || $vendor_preference != '1'?'checked':''; ?>> No ]</div>
					<input type="hidden" id="vendor_preference" value="<?= $vendor_preference; ?>" name="vendor_preference"/>
				</div>
				<div id="submit-vendor-portal">
					<input type="image" src="//media.cablesandkits.com/static/img/vp-submit.png" value="Review" id="review_button" align="middle">
				</div>
			</form>
			<div id="review_modal" style="display: none;"></div>
			<script>
				var sel = '<select size="1"><option value=""></option>';
				<?php foreach ($conditions as $condition) { ?>
					sel += '<option value="<?= $condition['conditions_id']; ?>"><?= $condition['conditions_name']; ?></option>';
				<?php } ?>
				sel += '</select>';
				var $select = jQuery(sel);

				function generatePreview() {
					var resultMarkup = '<table border="0" cellspacing="0" cellpadding="0" class="rfq_response"><thead><tr><th width="13%" align="left">Qty</th><th width="13%" align="left">Model #</th><th width="13%" align="left">Condition</th><th width="13%" align="left">Unit Price</th><th width="48%" align="left">Notes</th></tr></thead><tbody>';

					jQuery('.response').each(function() {
						quantity = jQuery(this).find('.quantity').val();
						if (quantity.trim() != '') {
							price = jQuery(this).find('.price').val();
							condition = jQuery(this).find('.condition option:selected').text();
							notes = jQuery(this).find('.notes').val();
							model = jQuery(this).parent().attr('model');
							resultMarkup = resultMarkup + '<tr><td nowrap>' + quantity + '</td><td nowrap>' + model + '</td><td nowrap>' + condition + '</td><td nowrap>' + price + '</td><td>' + notes + '</td></tr>';
						}
					});

					resultMarkup = resultMarkup + '</tbody></table>';
					if (jQuery('#shipping_included_yes').is(':checked')) {
						resultMarkup = resultMarkup + '<br/><div style="padding-top:10px;">Free ground shipping included</div>';
					} else if (jQuery('#shipping_included_no').is(':checked')) {
						resultMarkup = resultMarkup + '<br/><div style="padding-top:10px;">DOES NOT include free ground shipping</div>';
					} else {
						resultMarkup = resultMarkup + '<br/><div style="padding-top:10px; font-weight: bold; color: red;">Please indicate whether or not this quote includes free ground shipping</div>';
					}
					return resultMarkup;
				}

				jQuery('#review_button').click(function (e) {
					e.preventDefault();

					var formError = false;
					jQuery('.response').each(function() {
						quantity = jQuery(this).find('.quantity').val();
						price = jQuery(this).find('.price').val();
						notes = jQuery(this).find('.notes').val();
						if (quantity.trim() != '' || price.trim() != '' || notes.trim() != '') {
							model = jQuery(this).parent().attr('model');
							if (quantity.trim() == '') {
								alert('Please specify a quantity for ' + model + '. Take note that for any line item on this form if you enter a price, quantity, or notes you will be required to fill out the price and quantity for that item before your response will be accepted.');
								formError = true;
							}
							else if (price.trim() == '') {
								alert('Please specify a price for ' + model + '. Take note that for any line item on this form if you enter a price, quantity, or notes you will be required to fill out the price and quantity for that item before your response will be accepted.');
								formError = true;
							}
						}
					});
					if (formError) {
						return false;
					}

					var vendorPreference = jQuery('#vendor_preference').val();
					var message = null;
					if ((vendorPreference == 0 || vendorPreference == 2)
						&& jQuery('#shipping_included_yes').is(':checked')) {
						message = "Would you like to make 'Yes' your default setting for free ground shipping? Press OK to accept or Cancel to reject.";
					} else if (vendorPreference == 1 && jQuery('#shipping_included_no').is(':checked')) {
						message = "Would you like to change your default setting to 'No' for free ground shipping? Press OK to accept or cancel to reject.";
					}
					if (message != null) {
						result = confirm(message);
						if (result) {
							if (vendorPreference == 1) {
								jQuery('#vendor_preference').val('2');
							} else {
								jQuery('#vendor_preference').val('1');
							}
						}
					}

					jQuery('#review_modal').html(generatePreview());
					jQuery('#review_modal').dialog({
						modal: true,
						height: 500,
						width: 700,
						buttons: {
							"Submit" : function() {
								jQuery('#wtb_form').submit();
							},
							Cancel : function() {
								jQuery(this).dialog('close');
							}
						},
						title: 'Please review your response'
					});

				});

				jQuery('.show-rep, .close-rep, .add-rep').click(function(e) {
					e.preventDefault();
				});
				jQuery('.req').click(function() {
					var rpid = jQuery(this).attr('data-rpid');

					if (jQuery(this).hasClass('closed')) {
						jQuery('.rep.rpid-'+rpid).show();
						jQuery('.show-rep.rpid-'+rpid).hide();
						jQuery('.close-rep.rpid-'+rpid).show();
						jQuery(this).removeClass('closed');
						jQuery('.qty4'+rpid).select();
					}
					else {
						jQuery('.rep.rpid-'+rpid).hide();
						jQuery('.close-rep.rpid-'+rpid).hide();
						jQuery('.show-rep.rpid-'+rpid).show();
						jQuery(this).addClass('closed');
					}
				});
				jQuery('.add-rep-container').click(function() {
					var $row = jQuery(this).closest('tr');
					var repct = parseInt(jQuery(this).attr('data-repct'));
					repct++;
					var rpid = jQuery(this).attr('data-rpid');

					var $sel = $select.clone().attr('name', 'condition['+rpid+']['+repct+']');

					var $newrow = jQuery('<tr class="reply-to-'+rpid+' response"><td><input type="text" name="quantity['+rpid+']['+repct+']" value="" style="width:30px;" class="quantity"></td><td class="condition_'+rpid+'_'+repct+'"></td><td><input type="text" name="price['+rpid+']['+repct+']" value="" style="width:50px;" placeholder="$" class="format-currency price"></td><td><textarea name="notes['+rpid+']['+repct+']" rows="2" cols="30" maxlength="255" style="width:initial;" class="notes"></textarea></td><td><a href="#" class="rem-rep" data-rpid="'+rpid+'" data-repct="'+repct+'">x</a></td></tr>');

					$newrow.insertBefore($row);
					jQuery('.condition_'+rpid+'_'+repct).append($sel);

					jQuery(this).attr('data-repct', repct);
				});
				jQuery('.rem-rep').live('click', function(e) {
					e.preventDefault();

					var repct = parseInt(jQuery(this).attr('data-repct'));
					repct++;
					var rpid = jQuery(this).attr('data-rpid');

					var ct = jQuery('.reply-to-'+rpid).length;

					if (ct == 1) {
						jQuery(this).closest('tr').find('input, textarea, select').val('');
					}
					else {
						jQuery(this).closest('tr').remove();
					}
				});

				function format_currency(e) {
					// 48-57
					// 96-105
					e.preventDefault();

					var price = jQuery(this).val();

					var decimal = price.indexOf('.');
					//var selection = this.selectionStart;

					/*var movedit = 0;
					if (decimal != -1) {
						if (selection > decimal) {
							selection--;
							movedit = 1;
						}
					}*/

					var price = price.replace(/\D/, '');

					if (e.which >= 48 && e.which <= 57) price += ''+(e.which-48); //''+price.slice(0, selection)+(e.which-48)+price.slice(selection);
					////else if (e.which >= 96 && e.which <= 105) price += ''+(e.which-96);
					else if (e.which == 8) price = price.substring(0, price.length-1); //''+price.slice(0, selection-1, 1)+price.slice(selection);
					//else if (e.which == 46) price = ''+price.slice(0, selection)+price.slice(selection+1);
					else return false;

					price = parseFloat(price);
					if (isNaN(price)) price = 0;

					price /= 100;

					jQuery(this).val(price.toFixed(2));

					//if (price < 1) movedit++;
					//if (e.which == 8) this.selectionStart = this.selectionEnd = selection-1+movedit;
					//else if (e.which == 46) this.selectionStart = this.selectionEnd = selection+movedit;
					//else this.selectionStart = this.selectionEnd = selection+1+movedit;
				}

				/*jQuery('.format-currency').live('keypress', function(e) {
					format_currency.call(this, e);
				});
				jQuery('.format-currency').live('click', function(e) {
					var tmp = jQuery(this).val();
					jQuery(this).val('').val(tmp);
				});

				jQuery('.format-currency').live('keydown', function(e) {
					// catch & handle backspace & delete
					if (e.which == 8) format_currency.call(this, e); //|| e.which == 46)
				});*/

				jQuery('.format-currency').live('blur', function() {
					jQuery(this).val(parseFloat(jQuery(this).val()).toFixed(2));
				});
			</script>
			<?php } ?>
		</div>
	</div>
</div>
<?php if (empty($_SESSION['customer_id'])) {
	$login_return_to = '/VendorPortal/'.$rfq_id; ?>
	<div id="overlay">
		<style>
			#login { background-color:#fff; height:200px; width:325px; margin:10% auto 0px auto; padding:10px 30px; }
			#close-login { text-align:right; }
			#close-login a { display:block; padding:0px 3px; font-weight:bold; line-height:80%; float:right; font-size:1.2em; text-align:center; background-color:#fff; color:#e51937; }
			#close-login a:hover { text-decoration:none; }
		</style>
		<div id="login" class="active-area">
			<form action="/login.php?action=login" method="post">
				<input type="hidden" name="target_page" value="<?= $login_return_to; ?>">
				<div id="close-login">
					<a href="#" class="active-close">[X]</a>
				</div>
				<table width="254" border="0" cellspacing="0" cellpadding="0" style="font-size: 13px; text-align: left;">
					<tr>
						<td>Please Log In</td>
					</tr>
					<tr>
						<td>Email Address<br>
							<input type="text" id="loginput" name="email_address" value="<?= @$_REQUEST['email_address']; ?>"></td>
					</tr>
					<tr>
						<td>
							Enter Password<br>
							<input type="hidden" name="new_customer" value="N">
							<input type="password" name="password" id="loginput" class="lgi1"><br>
							<span class="logfp"><a href="/password_forgotten.php">Forget your password?</a></span>
						</td>
					</tr>
					<tr>
						<td><input type="image" src="/templates/Pixame_v1/images/login/signin.gif" class="logsignin"></td>
					</tr>
				</table>
				<div>
					<a href="/login.php?login_return_to=<?= urlencode($login_return_to); ?>">Create an account</a>
				</div>
			</form>
		</div>
	</div>
	<script>
		var $overlay = jQuery('#overlay');
		$overlay.remove();
		jQuery('body').append($overlay);
		$overlay.fadeIn();

		jQuery('.active-area').click(function(e) {
			e.stopPropagation();
		});

		jQuery('.active-close').click(function(e) {
			e.preventDefault();
			e.stopPropagation();
			overlay_close_action();
		});

		$overlay.click(function() {
			overlay_close_action();
		});

		function overlay_close_action() {
			window.location = '/';
		}
	</script>
<?php }

$open_wtbs = prepared_query::fetch('SELECT r.rfq_id, r.nickname, r.request_type, r.published_date FROM ck_rfqs r WHERE r.active = 1 AND r.published_date IS NOT NULL AND r.request_type = \'WTB\' ORDER BY r.request_type DESC, r.published_date DESC', cardinality::SET);
$open_rfqs = prepared_query::fetch('SELECT r.rfq_id, r.nickname, r.request_type, r.published_date FROM ck_rfqs r WHERE r.active = 1 AND r.published_date IS NOT NULL AND r.request_type = \'RFQ\' ORDER BY r.request_type DESC, r.published_date DESC', cardinality::SET);
if (!empty($open_wtbs) || !empty($open_rfqs)) {
$today = new DateTime(); ?>
<style>
	#vp-lb { width:175px; padding:8px 0px 8px 8px; margin:0px; }
	#vp-lb-header { color:#dd003c; font-size:24px; font-weight:bold; background-image:url(/templates/Pixame_v1/images/lb2l.gif); background-repeat:repeat-x; background-position:center bottom; margin:0px 0px 5px 0px; padding-bottom:15px; }

	#open-wtb, #open-rfq { background-image:url(/templates/Pixame_v1/images/lb2l.gif); background-repeat:repeat-x; background-position:center bottom; padding-bottom:15px; margin-bottom:5px; }

	#open-wtb table, #open-rfq table { width:100%; }
	.vp-lb-sub-header { text-align:left; color:#dd003c; font-size:18px; }
	.req-age { text-align:center; }
	td.req-age { color:#386881; }
	.newreq { color:#dd003c; }
	.req-name { padding:1px 4px 1px 10px; }
	.req-name a { color:#444; }
	.viewing td { background-color:#dfdedd; }
	.viewing .req-name a { color:#386881; }

	@media all and (max-width:980px) {
		#vp-lb { width:100%; }
		#vp-lb #open-wtb { width:100%; }
		#vp-lb #open-wtb table { margin:0 auto; width:400px; }
		#vp-lb-header { text-align:center; }
	}
</style>
<div id="vp-lb">
	<a href="/VendorPortal">
		<h3 id="vp-lb-header">Open Requests</h3>
	</a>
	<?php if (!empty($open_wtbs)) { ?>
		<div id="open-wtb">
			<table border="0" cellpadding="0" cellspacing="0">
				<thead>
				<tr>
					<th class="vp-lb-sub-header">Open WTBs</th>
					<th class="req-age">Age</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td class="req-name" colspan="2" style="font-weight:bold; padding: 0px"><a href="/VendorPortal/wtb">View All open WTBs</a></td>
				</tr>
				<?php foreach ($open_wtbs as $request) {
					$pubdate = new DateTime($request['published_date']);
					$diff = $pubdate->diff($today); ?>
					<tr class="<?php echo $request['rfq_id']==$rfq_id?'viewing':''; ?>">
						<td class="req-name"><a href="/VendorPortal/<?= $request['rfq_id']; ?>"><?= $request['nickname']; ?></a></td>
						<td class="req-age"><?php echo $diff->days==0?'<span class="newreq">New!</span>':$diff->days; ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
	<?php }
	if (!empty($open_rfqs)) { ?>
		<div id="open-rfq">
			<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
				<thead>
				<tr>
					<th class="vp-lb-sub-header">Open RFQs</th>
					<th class="req-age">Age</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td class="req-name" colspan="2" style="font-weight:bold;"><a href="/VendorPortal/rfq">View All open RFQs</a></td>
				</tr>
				<?php foreach ($open_rfqs as $request) {
					$pubdate = new DateTime($request['published_date']);
					$diff = $pubdate->diff($today); ?>
					<tr class="<?php echo $request['rfq_id']==$rfq_id?'viewing':''; ?>">
						<td class="req-name"><a href="/VendorPortal/<?= $request['rfq_id']; ?>"><?= $request['nickname']; ?></a></td>
						<td class="req-age"><?php echo $diff->format('d')==0?'<span class="newreq">New!</span>':$diff->format('d'); ?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
	<?php } ?>
</div>
<?php } ?>