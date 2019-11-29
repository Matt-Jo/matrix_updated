<?php $admin = (isset($_SESSION['admin'])&&$_SESSION['admin']=='true')||!empty($_SESSION['admin_login_id']);
$show_rev = $admin&&!empty($_GET['show_rev']);

if ($admin) $ck_admin = ck_admin::login_instance(); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html dir="LTR" lang="en">
<head>
	<title><?= STORE_NAME; ?> - Invoice for Order <?= $order->id(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<style type="text/css">
		#inv_main { width: 99%; margin: 2px; }
		#header { font-size: 22px; font-weight: bold; font-family: Arial, Verdana, Sans-serif; color: #787878; }
		.right{ float: right; }
		.left { float: left; }
		#body { clear: both; font-family: Arial, Verdana, Sans-serif; font-size: 12px; color: black; }
		.infoRow1 { padding-top: 20px; }
		.invTable { border-collapse: collapse; }
		.invTableHeaderRow { border: 3px solid #aaaaaa; background-color: #cdcdcd; font-weight: bold; text-align: left; padding: 2px; }
		.invTable td { vertical-align: top; }
		.invTableProductCell { border-top-width: 1px; border-left-width: 3px; border-bottom-width: 1px; border-style: solid; border-color: #aaaaaa; }
		.invTableProductCell.item-qty { text-align:center; }
		.unitprice { text-align: right; }
		td.totalprice { border-right-width: 3px; border-left-width: 3px; text-align: right; }
		td.totals { text-align: right; border-right-width: 3px; }
		input.totals { font-family: Arial,Verdana,Sans-serif; font-size: 12px; text-align: right; }
		#gtotal { border-width: 3px; text-align: right; }
		td.unitprice input { font-family: Arial, Verdana, Sans-serif; font-size: 12px; text-align: right; }
		#notice { float: left; margin-top: 5px; margin-left: 15px; display: none; color: red; }

		.included-option { font-size:10px; }
		.included-option td.invTableProductCell { border-width:1px 0px; padding-left:25px; }
		.included-option .invTableProductCell.item-qty { text-align:right; padding-right:15px; }

		<?php if ($admin) { ?>
		@media print {
			#admin-control { display:none; }
			.admin-col { display:none; }
			.admin-holder { display:none; }
		}
			<?php if ($show_rev) { ?>
		.admin-col { background-color:#fdd; text-align:right; }
			<?php }
		} ?>
	</style>
	<script type="text/javascript" src="/includes/javascript/jquery-1.4.2.min.js"></script>
	<?php if (!empty($editable)) {
		$old_order_totals = $order->get_simple_totals(); ?>
	<script>
		var data = '';
		$(document).ready(function() {
			$('input[type="submit"]').attr('disabled','disabled');

			$('td.unitprice input').bind('change keyup', function(event) {
				var id = this.id.split('_')[1];
				var totalprice_id = '#totalprice_'+id;

				var qty_id = '#unitqty_'+id;
				var qty = $(qty_id).html();

				var unitprice = doClean($(this).val());
				var price = unitprice * qty;
				var newPrice = '$' + price.toFixed(2);
				$(totalprice_id).html(newPrice);
				doTotals();
			});
			$('input.totals').bind('change keyup', function(event) {
				doTotals();
			});
			$('#close-button').click(function(event) {
				window.close();
			});
			$('#clear-tax-button').click(function(event) {
				jQuery('#tax').html('$' + (0.00));
				$('input').removeAttr('disabled');
				$('#gtotal').html('$' + (<?= $old_order_totals['total'] - @$old_order_totals['tax']; ?>).toFixed(2));
			});
			$('#apply-credit-button').click(function(event) {
				window.open('/admin/acc_customer_invoices.php?content=history&customer_id=<?= $customer->id(); ?>', '_blank');
			});
			$('#edit-button').click(function(event) {
				event.preventDefault();
				var button = this;
				data = 'edit=invoice_save&oID=<?= $order->id(); ?>&';
				$.each($('td.totals'), function() {
					doDataCollect($(this).html(), this);
				});
				$.each($('input.totals'), function() {
					doDataCollect($(this).val(), this);
				});
				$.each($('td.unitprice input'), function(index, value) {
					var id = this.id.split('_')[1];
					var unitprice = doClean($(this).val());
					data = data + 'product_' + id + '=' + unitprice + '&';
				});
				$.ajax({
					url: '/admin/invoice.php',
					data: data,
					success: function(msg) {
						if (msg == 'success') {
							$(button).attr('disabled', 'disabled');
							//window.close();
							$('#notice').show();
						}
						else {
							alert(msg);
						}
					}
				});
			});
		});

		function doDataCollect(input, myThis) {
			var totalText = doClean(input);
			var classList = jQuery(myThis).attr('class').split(/\s+/);
			jQuery.each(classList, function(index, item) {
				if (item.indexOf('ot_') != -1) {
					data = data+item+'='+totalText+'&';
				}
			});
		}

		function doTotals() {
			var subtotal = 0;
			var gtotal = 0;
			var coupon = 0;
			var ship = $('#shipping').val();

			//MMD - need to handle the case where there is no decimal since we are doing string concatentation of the arg
			if (ship.indexOf('.') > -1) {
				ship = doClean(ship + '0');
			}
			else {
				ship = doClean(ship + '.0');
			}

			jQuery.each(jQuery('td.totalprice span'), function(index, value) {
				subtotal = subtotal + doClean(jQuery(this).html());
			});
			jQuery('#subtotal').html('$' + subtotal.toFixed(2));

			jQuery.each(jQuery('td.totals'), function(index, value) {
				if (this.id == 'coupon') {
					coupon = coupon + doClean(jQuery(this).html());
				}
			});

			jQuery.get('/admin/invoice.php', { oID: '<?= $order->id(); ?>', edit: 'getCoupon' }, function(coupon_amount) {
				//
				//need to get coupon percentage to replace .1
				var coupon_total = 0;
				if (coupon_amount < 1) {
					coupon_total = (subtotal*coupon_amount);
				}
				else {
					coupon_total = coupon_amount;
				}
				jQuery('#coupon').html('-$' + (parseFloat(coupon_total)).toFixed(2));

				jQuery.get('/admin/invoice.php', { oID: '<?= $order->id(); ?>', edit: 'getTaxRate' }, function(tax_amount) {
					//
					//need to get the tax percentage to replace 8.25%
					jQuery('#tax').html('$' + ((subtotal - (coupon_total) + ship) * tax_amount).toFixed(2));

					jQuery.each(jQuery('td.totals'), function(index, value) {
						if (this.id != 'gtotal') {
							gtotal = gtotal + doClean(jQuery(this).html());
						}
					});
					jQuery.each(jQuery('input.totals'), function(index, value) {
						gtotal = gtotal + doClean(jQuery(this).val());
					});
					jQuery('#gtotal').html('$' + gtotal.toFixed(2));

					if (gtotal.toFixed(2) == '<?= $old_order_totals['total']; ?>') {
						$('input[type="submit"]').attr('disabled','disabled');
					}

					if (gtotal.toFixed(2) != '<?= $old_order_totals['total']; ?>') {
						$('input[type="submit"]').removeAttr('disabled');
					}
				});
			});
		}

		function doClean(val) {
			val = val.replace(',', '');
			if (val.substring(0,1) == '$') {
				val = val.substring(1, val.length);
			}
			else if (val.substring(0,1) == '-') {
				val = val.substring(2, val.length);
				val = (val * -1);
			}
			val = parseFloat(val);
			return val;
		}
	</script>
	<?php } ?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<?php if (isset($_SESSION['flash'])) { ?>
		<div style="position:absolute; right:0; top:0; width: 200px; background-color:green; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border-radius:.25rem; color:#fff; padding:.75em; margin:.25em;">
			<?= $_SESSION['flash']; ?>
		</div>
		<?php unset($_SESSION['flash']); ?>
	<?php } ?>
	<?php if ($admin && empty($GLOBALS['customer_invoice_attachment'])) { ?>
	<div id="admin-control">
		<?php if ($show_rev) {
			unset($_GET['show_rev']); ?>
		<a href="?<?= http_build_query($_GET); ?>"><button>Hide CK Revenue Column</button></a>
		<?php }
		else { ?>
		<a href="?<?= http_build_query($_GET); ?>&show_rev=1"><button>Show CK Revenue Column</button></a>
		<?php } ?>
	</div>
	<?php } ?>
	<div id="inv_main">
		<div id="header">
			<div class="left">
				<img src="https://media.cablesandkits.com/ckheader2_logo.gif" border="0">
			</div>
			<div class="right" style="">
				<?php if ((!empty($invoice) && $invoice->is('credit_memo')) || !empty($rma)) $invoice_title = 'Credit Memo';
				else $invoice_title = 'Invoice';

				if (!empty($invoice)) echo $invoice_title.' # '.$invoice->id();
				else echo $invoice_title.' # N/A'; ?>
			</div>
		</div>
		<div id="body">
			<div class="infoRow1">
				<div class="left" style="width: 50%;">
					<table width="100%">
						<tr>
							<td>CablesAndKits.com</td>
							<td><b>Questions?</b></td>
						</tr>
						<tr>
							<td>4555 Atwater Ct, Ste A</td>
							<td><?= $order->get_contact_phone(); ?></td>
						</tr>
						<tr>
							<td>Buford, GA 30518 USA</td>
							<td><a href="mailto:accounting@cablesandkits.com" style="font-size: 12px;">accounting@cablesandkits.com</a></td>
						</tr>
					</table>
				</div>

				<div class="right" style="width: 50%;">
					<table width="100%" class="invTable" style="margin-top: 30px;">
						<tr class="invTableHeaderRow">
							<th><?= $invoice_title; ?> Date</th>
							<th>Order #</th>
							<th>PO#/Ref#</th>
							<th>Terms</th>
						</tr>
						<tr>
							<td>
								<?php if (!empty($invoice)) echo $invoice->get_header('invoice_date')->format('m/d/Y');
								else echo 'N/A'; ?>
							</td>
							<td><?= $order->id(); ?></td>
							<td>
							<?php if ($admin && empty($GLOBALS['customer_invoice_attachment']) && empty($GLOBALS['skip_app_top'])) { ?>
										<form action="/admin/invoice.php?oID=<?= $order->id(); ?>" method="post" style="display:inline;">
											<input type="hidden" name="edit" value="update-term-po-number">
											<input type="text"
											       onchange="this.form.submit();"
											       name="terms_po_number" value="<?= $order->get_terms_po_number(); ?>"
											       style="padding:2px; border-radius:.25em; border:1px solid black; width:auto;">
										</form>
								<?php }
								if ($order->has_ref_po_number()) {
									if ($order->has_terms_po_number()) echo ' / ';
									echo $order->get_ref_po_number();
								} ?>
							</td>
							<td>
								<?= $order->get_header('payment_method_label'); ?>
							</td>
						</tr>
					</table>
				</div>
				<div style="clear: both;"></div>
			</div>

			<div class="infoRow1">
				<table width="100%" class="invTable">
					<tr class="invTableHeaderRow">
						<th width="50%">Bill To</th>
						<th width="30%">Ship To</th>
						<th width="20%">&nbsp;</th>
					</tr>
					<tr>
						<td valign="top">
							<?php $address = $order->get_bill_address();
							if ($address->has_name()) echo 'Attn: '.$address->get_name().'<br>';
							if ($address->has_company_name()) echo $address->get_company_name().'<br>';
							echo $address->get_header('address1').'<br>';
							if (!empty($address->get_header('address2'))) echo $address->get_header('address2').'<br>';

							echo $address->get_header('city').' '.$address->get_state().' '.$address->get_header('postcode').'<br>';
							echo $address->get_header('country'); ?>
						</td>
						<td valign="top">
							<?php $address = $order->get_ship_address();
							if ($address->has_name()) echo 'Attn: '.$address->get_name().'<br>';
							if ($address->has_company_name()) echo $address->get_company_name().'<br>';
							echo $address->get_header('address1').'<br>';
							if (!empty($address->get_header('address2'))) echo $address->get_header('address2').'<br>';

							echo $address->get_header('city').' '.$address->get_state().' '.$address->get_header('postcode').'<br>';
							echo $address->get_header('country'); ?>
						</td>
						<?php if ($order->is('dropship')) { ?>
						<td valign="top"><span style="color: red; font-weight: bold;">BLIND SHIPMENT</span></td>
						<?php } ?>
					</tr>
				</table>
			</div>

			<?php if (empty($rma)) { ?>
			<div class="infoRow1">
				<table width="100%" class="invTable">
					<tr class="invTableHeaderRow">
						<th width="16%">Ship Date</th>
						<th width="17%">Ship Via</th>
						<th width="17%">Shipping Account</th>
						<th width="50%">Tracking No</th>
					</tr>
					<tr>
						<td>
							<?php if (!empty($invoice)) echo $invoice->get_header('invoice_date')->format('m/d/Y');
							else echo 'N/A'; ?>
						</td>
						<td>
							<?= $order->get_shipping_method('carrier'); ?>
							<?= $order->get_shipping_method('method_name'); ?>
						</td>
						<td>
							<?php if ($order->is_shipping_on_account()) {
								if ($order->get_shipping_method('carrier') == 'FedEx') echo $order->get_header('customers_fedex');
								elseif ($order->get_shipping_method('carrier') == 'UPS') echo $order->get_header('customers_ups');
							}
							else echo 'Prepay'; ?>
						</td>
						<td>
							<?php if (!empty($invoice) && $order->get_shipping_method('shipping_code') != 47) {
								foreach ($order->get_packages() as $package) {
									if (CK\fn::check_flag($package['void'])) continue;

									if (empty($package['tracking_num'])) echo "N/A";
									else {
										$tracking_link = '';
										switch ($package['carrier']) {
											case 'FedEx':
												$tracking_link = 'http://www.fedex.com/Tracking/Detail?ascend_header=1&totalPieceNum=&clienttype=dotcom&cntry_code=us&tracknumber_list='.$package['tracking_num'].'&language=english&trackNum='.$package['tracking_num'].'&pieceNum';
												break;
											case 'UPS':
												$tracking_link = 'http://www.ups.com/WebTracking/processInputRequest?loc=en_US&Requester=NOT&tracknum='.$package['tracking_num'].'&AgreeToTermsAndConditions=yes';
												break;
											case 'USPS':
												$tracking_link = 'http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum='.$package['tracking_num'];
												break;
										}

										if (!empty($tracking_link)) { ?>
							<a href="<?= $tracking_link; ?>" target="_BLANK" style="font-size: 12px;"><?= $package['tracking_num']; ?></a>
										<?php }
										else echo $package['tracking_num'];
									}
								}
							}
							else echo 'N/A'; ?>
						</td>
					</tr>
				</table>
			</div>
			<?php } ?>

			<div class="infoRow1">
				<table width="100%" class="invTable" cellpadding="2px">
					<tr class="invTableHeaderRow">
						<th width="15%" style="text-align: center;">Quantity</th>
						<th width="15%" style="border-left:3px solid #aaaaaa;" >Item</th>
						<th width="40%" style="border-left:3px solid #aaaaaa;">Description</th>
						<th width="15%" style="border-left:3px solid #aaaaaa; text-align: center;">Unit Price</th>
						<th width="15%" style="border-left:3px solid #aaaaaa; text-align: center;">Total Price</th>
						<?php if ($show_rev) { ?>
						<th class="admin-col">CK Unit Revenue</th>
						<th class="admin-col">CK Line Revenue</th>
						<?php } ?>
					</tr>

					<?php
					$admin_subtotal = 0;
					if (!empty($invoice)) {
						foreach ($invoice->get_consolidated_products() as $orders_products_id => $product) {
							if ($product['ipn']->is_supplies()) continue;
							$admin_subtotal += $product['revenue'] * abs($product['quantity']);
							$unitprice = CK\text::monetize($product['invoice_unit_price']);
							$totalprice = CK\text::monetize(abs($product['quantity']) * $product['invoice_unit_price']); ?>
					<tr class="<?= empty($rma)&&$product['option_type']==3?'included-option':''; ?>">
						<td id="unitqty_<?= $orders_products_id; ?>" class="invTableProductCell item-qty"><?= abs($product['quantity']); ?></td>
						<td class="invTableProductCell item-model"><?= $product['model']; ?></td>
						<td class="invTableProductCell">
							<?= $product['name']; ?>
							<?php if (!empty($product['serials'])) { ?>
							<br>Serials:<br>
							<i>
								<?php foreach ($product['serials'] as $serial) {
									echo $serial->get_header('serial_number').' &nbsp;';
								} ?>
							</i>
							<?php } ?>
						</td>
						<td class="unitprice invTableProductCell">
							<?php if (!empty($editable) && $product['option_type'] != ck_cart::$option_types['INCLUDED']) { ?>
							<input id="unitprice_<?= $orders_products_id; ?>" name="<?= $orders_products_id; ?>" value="<?= $unitprice; ?>">
							<?php }
							else echo $unitprice; ?>
						</td>
						<td class="totalprice invTableProductCell">
							<span id="totalprice_<?= $orders_products_id; ?>"><?= $totalprice; ?></span>
						</td>
							<?php if ($show_rev) { ?>
						<td class="admin-col">
							<?= CK\text::monetize($product['revenue']); ?>
						</td>
						<td class="admin-col">
							<?= CK\text::monetize($product['revenue'] * abs($product['quantity'])); ?>
						</td>
							<?php } ?>
					</tr>
						<?php }
					}
					else {
						foreach ($order->get_products() as $product) {
							//the product is a shipping supply, do not show to customer
							if ($product['ipn']->is_supplies()) continue;
							$admin_subtotal += $product['revenue'] * abs($product['quantity']);
							$unitprice = CK\text::monetize($product['final_price']);
							$totalprice = CK\text::monetize($product['quantity'] * $product['final_price']); ?>
					<tr class="<?= empty($rma)&&$product['option_type']==3?'included-option':''; ?>">
						<td id="unitqty_<?= $product['orders_products_id']; ?>" class="invTableProductCell item-qty"><?= $product['quantity']; ?></td>
						<td class="invTableProductCell item-model"><?= $product['model']; ?></td>
						<td class="invTableProductCell">
							<?= $product['name']; ?>
							<?php if (!empty($product['serials'])) { ?>
							<br>Serials:<br>
							<i>
								<?php foreach ($product['serials'] as $serial) {
									echo $serial->get_header('serial_number').' &nbsp;';
								} ?>
							</i>
							<?php } ?>
						</td>
						<td class="unitprice invTableProductCell">
							<?php if (!empty($editable) && $product['option_type'] != ck_cart::$option_types['INCLUDED']) { ?>
							<input id="unitprice_<?= $product['orders_products_id']; ?>" name="<?= $product['orders_products_id']; ?>" value="<?= $unitprice; ?>">
							<?php }
							else echo $unitprice; ?>
						</td>
						<td class="totalprice invTableProductCell">
							<span id="totalprice_<?= $product['orders_products_id']; ?>"><?= $totalprice; ?></span>
						</td>
							<?php if ($show_rev) { ?>
						<td class="admin-col">
							<?= CK\text::monetize($product['revenue']); ?>
						</td>
						<td class="admin-col">
							<?= CK\text::monetize($product['revenue'] * abs($product['quantity'])); ?>
						</td>
							<?php } ?>
					</tr>
						<?php }
					}

					if (!empty($invoice)) {
						$totals = array_filter($invoice->get_simple_totals());
						if (empty($totals['shipping'])) $totals['shipping'] = 0;
						if (empty($totals['total'])) $totals['total'] = 0;
						$subtotal = $invoice->get_product_subtotal();

						if (!empty($rma)) {
							//$subtotal *= -1;
							if (empty($totals['shipping'])) unset($totals['shipping']);
						}
					}
					else {
						$totals = $order->get_simple_totals();
						$subtotal = $order->get_product_subtotal();
					} ?>
					<tr style="border-top: 3px solid #aaaaaa;">
						<td rowspan="<?= count($totals)+1; ?>" colspan="3" style="border-left: 3px solid #aaaaaa; border-bottom: 3px solid #aaaaaa;">
							<b>Order Comments:</b><br>
							<?php $order_status_history = $order->get_status_history();
							//only show the first comment
							if (!empty($order_status_history[0]) && !empty($order_status_history[0]['comments'])) echo nl2br($order_status_history[0]['comments']); ?>
						</td>
						<td class="invTableProductCell">Subtotal</td>
						<td class="invTableProductCell totals ot_subtotal" id="subtotal"><?= CK\text::monetize($subtotal); ?></td>
						<?php if ($show_rev) { ?>
						<td class="admin-holder"></td>
						<td class="admin-col">
							<?= CK\text::monetize($admin_subtotal); ?>
						</td>
						<?php } ?>
					</tr>
					<?php if (!empty($totals['coupon'])) { ?>
					<tr>
						<td class="invTableProductCell">Coupon</td>
						<td class="invTableProductCell totals ot_coupon" id="coupon"><?= CK\text::monetize($totals['coupon']); ?></td>
						<?php if ($show_rev) { ?>
						<td class="admin-holder" colspan="2"></td>
						<?php } ?>
					</tr>
					<?php }

					if (!empty($totals['tax'])) {?>
					<tr>
						<td class="invTableProductCell">Tax</td>
						<td class="invTableProductCell totals ot_tax" id="tax"><?= CK\text::monetize($totals['tax']); ?></td>
						<?php if ($show_rev) { ?>
						<td class="admin-holder" colspan="2"></td>
						<?php } ?>
					</tr>
					<?php }

					if (isset($totals['shipping'])) { ?>
					<tr>
						<td class="invTableProductCell">Shipping</td>
						<?php if (!empty($editable)) { ?>
						<td class="invTableProductCell"><input id="shipping" class="totals ot_shipping" value="<?= CK\text::monetize($totals['shipping']); ?>"></td>
						<?php }
						else { ?>
						<td class="invTableProductCell" style="text-align:right"><?= CK\text::monetize($totals['shipping']); ?></td>
						<?php }

						if ($show_rev) { ?>
						<td class="admin-holder" colspan="2"></td>
						<?php } ?>
					</tr>
					<?php } ?>

					<tr>
						<td style="border: 3px solid #aaaaaa;"><b><?= $invoice_title; ?> Total</b></td>
						<td class="invTableProductCell totals ot_total" id="gtotal"><?= CK\text::monetize(@$totals['total']); ?></td>
						<?php if ($show_rev) { ?>
						<td class="admin-holder" colspan="2"></td>
						<?php } ?>
					</tr>
				</table>

				<?php if (!empty($editable)) { ?>
				<div style="float: left;"><input type="submit" id="edit-button" value="Submit" style="background-color:green; color:white; font-size:15pt; height:50px; margin-right:10px; width:160px;"></div>
				<div style="float: left;"><input type="button" id="close-button" value="Close" style="background-color:green; color:white; font-size:15pt; height:50px; margin-right:10px; width:160px;"></div>
				<div style="float: left;"><input type="button" id="apply-credit-button" value="Apply Credit Memo" style="background-color:green; color:white; font-size:15pt; height:50px; margin-right:10px; width:200px;"></div>

					<?php if ($_SESSION['perms']['admin_groups_id'] == '11' || $_SESSION['perms']['admin_groups_id'] == '1') { ?>
				<div style="float: left;">
					<?php if (!empty($totals['tax']) && $totals['tax'] > 0.00) { ?>
					<input type="button" id="clear-tax-button" value="Remove Tax" style="background-color:green; color:white; font-size:15pt; height:50px; margin-right:10px; width:160px;">
					<?php }
					else { ?>
					<input type="button" id="clear-tax-button" value="Remove Tax" style="color:white; font-size:15pt; height:50px; margin-right:10px; width:160px;" disabled="disabled">
					<?php } ?>
				</div>
					<?php } ?>

				<div style="clear:both;"></div>
				<div style="float: left;" id="notice">Edit Invoice Complete!</div>
				<?php } ?>
			</div>

			<?php if (empty($rma)) { ?>
			<div class="infoRow1" style="text-align: center; font-weight: bold;">
				<p>If you wish to return or replace any items in your order, please reference the <a href="https://www.cablesandkits.com/info/returns-page">Returns Form</a>.</p>
				<p><i>Thank you for your business!</i></p>
			</div>
			<?php }

			if (empty($rma) && $order->get_shipping_method('shipping_code') == 47) { ?>
			<div style="margin-left: auto; margin-right: auto; margin-top: 50px; padding: 20px; width: 500px;">
				<div style="float: left;">Received By:</div>
				<div style="margin-left: 30px; float: left; width: 300px; border-bottom: 1px solid black;">&nbsp;</div>
			</div>
			<?php } ?>
		</div>
	</div>
	<div style="clear: both;"></div>
	<?php if (empty($GLOBALS['skip_app_top'])) { ?>
	<?php if ($admin && $ck_admin->is_top_admin() && !empty($invoice)) { ?>
	<form action="/admin/invoice.php?invId=<?= $invoice->id(); ?>" method="post">
		<input type="hidden" name="edit" value="override-incentive">

		<?php if ($invoice->get_incentive('overridden')) { ?>
		This incentive has been overridden<br>
		Incentive Percentage: <?= $invoice->get_incentive('final_incentive_percentage'); ?><br>
		Notes:<br>
		<?= $invoice->get_incentive('incentive_override_note'); ?>
		<?php }
		else { ?>
		Incentive Percentage: <input type="text" name="incentive_override_percentage" value="<?= $invoice->get_incentive('final_incentive_percentage'); ?>"><br>
		Notes:<br>
		<textarea name="incentive_override_note" cols="100" rows="5"></textarea>
		<button type="submit">Override Incentive</button>
		<?php } ?>
	</form>
	<?php } ?>
	<?php } ?>
</body>
</html>

