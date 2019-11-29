function popupWindow(url, width, height) {
	width = width || 800;
	height = height || 800;
	window.open(url, 'popupWindow', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width='+width+',height='+height+',screenX=150,screenY=150,top=150,left=150')
}

//MMD - this is now used in both the ZF shipping screens as well as orders_new.php
function dispEditPack (oid, pckg, packship) {
	new Ajax.Request ('/admin/packaging_dtl.php', {
		method: 'get',
		parameters: {action: 'package_details', oid: oid, pckgid: pckg, packship: packship},
		onSuccess: function (transport) {
			$('editpackDiv').update(transport.responseText);
			jQuery('#editpackDiv').dialog({ modal: true});
			jQuery('#editpackDiv').dialog('option', 'width', 400);
			jQuery('#editpackDiv').dialog('option', 'position', 'center');
			showPackageDim();
		}
	});

	return false;
}

function runPackage (oid, pckgid, ordtrackid, act, divid) {
	new Ajax.Request ('/admin/packaging_dtl.php', {
		method: 'get',
		parameters: {action: act, oid: oid, pckgid: pckgid, ordtrackid: ordtrackid},
		onSuccess: function (transport) {
			if (act == 'delete_packages' || act == 'void_package') {
				//location.reload();
			}
			$(divid).style.visibility = 'visible';
			$(divid).update(transport.responseText);
			if (act=='quick_add_package' && $('new_ship_fedex')!=null && !((shippingMethodId > 16 && shippingMethodId < 47) || shippingMethodId > 51)) {
				$('new_ship_fedex').disabled=0;
			}
		}
	});

	return false;
}

function runPackageTracking(oid) {
	jQuery.ajax({
		url: '/admin/packaging_dtl.php',
		type: 'GET',
		dataType: 'json',
		data: { action: 'update_ups_tracking', oid: oid, pckgid: '' },
		success: function(data) {
			if (data.shipping_type_error) {
				alert('You have tried to ship this order by a different shipping type than specified. Please change your shipping method or edit the order');
			}
			if (data.imported_address_change) {
				alert(data.imported_address_change);
			}
			jQuery('#editPackagesDiv').html(data.display);
			jQuery('#comments').val(data.note);
		},
		error: function() {
			alert('Your request failed; please refresh the page.');
		}
	});
}

function updatePackage(oid, pckgid, close) {
	if ($('update_package_id').value < 0) {
		alert('Please, select a package size.');
		$('update_package_id').focus();
	}
	else if ($('update_scale_weight')!= null && parseFloat($('update_scale_weight').value) < 0) {
		alert('Please, enter a valid weight.');
		$('update_scale_weight').focus();
	}
	else if ($('packship').value == 'ship' && $('update_tracking_num').value == '') {
		alert('Please, enter a tracking number.');
		$('update_tracking_num').focus();
	}
	else if ($('packship').value == 'ship' && $('update_cost') != null && parseFloat($('update_cost').value) < 0) {
		alert('Please, enter valid cost.');
		$('update_cost').focus();
	}
	else {
		var act = (pckgid > 0) ? 'update_package' : 'add_package';
		new Ajax.Request ('/admin/packaging_dtl.php', {
			method: 'get',
			parameters: {
				action: act,
				oid: oid,
				pckgid: pckgid,
				orders_tracking_id: $('update_orders_tracking_id').value,
				package_type_id: $('update_package_id').value,
				scale_weight: $('update_scale_weight').value,
				tracking_num: $('update_tracking_num').value,
				shipping_method_id: $('update_shipping_method_id').value,
				order_package_length: (($('update_length') != null) ? $('update_length').value : 0),
				order_package_width: (($('update_width') != null) ? $('update_width').value : 0),
				order_package_height: (($('update_height') != null) ? $('update_height').value : 0),
				cost: $('update_cost').value,
				num_packages: (($('num_packages') != null && parseInt($('num_packages').value, 10) > 0) ? parseInt($('num_packages').value, 10) : 0)
			},
			onSuccess: function (transport) {
				//location.reload();
				$('editPackagesDiv').style.visibility = 'visible';
				//$('editPackagesDiv').update(transport.responseText);
				runPackage(oid, '', '', 'package_list', 'editPackagesDiv');
			}
		});
		closeeditpackDiv(oid, !close);
	}

	return false;
}

function showPackageDim () {
	if (jQuery('#pckgDimDiv') != null && $('update_package_id') != null) {
		if ($('update_package_id').value >= 1 && $('update_package_id').value <= 2) {
			jQuery('#pckgDimDiv').show();
			jQuery('#update_length').focus();
		}
		else {
			jQuery('#pckgDimDiv').hide();
		}
	}

	return false;
}

function closeeditpackDiv (oid, reopen) {
	jQuery('#editpackDiv').dialog('close');
	jQuery('#pckgDimDiv').hide();

	//enable button
	jQuery("#new_ship_fedex").attr("disabled", false);

	if (reopen) dispEditPack(oid, 0, '');

	return false;
}

function catchEvent (e, o, p) {
	var key = (window.event) ? window.event.keyCode : ((e.which) ? e.which : e.charCode);
	if (key == 13) {
		updatePackage(o, p);
	}
	return false;
}

function dispNewFedexShip (oid, count) {
	count = (($('numpackages')!=null) && $('numpackages').value > count) ? $('numpackages').value : count;

	var h = 300 + (count * 35);
	new Ajax.Request ('new_ship_fedex.php', {
		method: 'get',
		parameters: {action: 'new_ship', oid: oid, addrerrs: (($('addrerrors')!=null) ? $('addrerrors').value : '')},
		onSuccess: function (transport) {
			$('editpackDiv').update(transport.responseText);
			jQuery('#editpackDiv').dialog({ modal: true});
			jQuery('#editpackDiv').dialog('option', 'width', 400);
			jQuery('#editpackDiv').dialog('option', 'position', 'center');
			showPackageDim();
		}
	});

	return false;
}

function sendNewFedexShip (oid) {
	new Ajax.Request ('new_ship_fedex.php', {
		method: 'get',
		parameters: {
			action: 'send_ship',
			oid: oid,
			bill_type: ($('bill_type')!=null) ? $('bill_type').value : '',
			payee_account_num: ($('payee_account_num')!=null) ? $('payee_account_num').value : '',
			signature_type: ($('signature_type')!=null) ? $('signature_type').value : '',
			print_label: ($('print_label')!=null && $('print_label').checked==true) ? $('print_label').value : ''
		},
		onSuccess: function (transport) {
			// Clean up error response
			if (transport.responseText.indexOf('%%error%%')>0 || transport.responseText.indexOf('ERROR')>=0) {
				$('editpackDiv').update(transport.responseText.replace('%%error%%', ''));
			}
			else {
				runPackage(oid, '', '', 'package_list', 'editPackagesDiv');
				if ($('comments')!=null) {
					$('comments').value = transport.responseText;
				}
				closeNewFedexShip();
			}
		}
	});

	return false;
}

function closeNewFedexShip () {
	jQuery('#editpackDiv').dialog('close');
	return false;
}

function dispPackLabel (oid, url, act) {
	new Ajax.Request ('new_ship_fedex.php', {
		method: 'get',
		parameters: {
			action: act,
			oid: oid,
			url: url
		},
		onSuccess: function (transport) {
			$('editpackDiv').update(transport.responseText);
			jQuery('#editpackDiv').dialog({ modal: true });
			jQuery('#editpackDiv').dialog('option', 'width', 450);
			jQuery('#editpackDiv').dialog('option', 'height', 450);
			jQuery('#editpackDiv').dialog('option', 'position', 'center');
		}
	});
	return false;
}

function printLabel(id) {
	var docContainer = document.getElementById(id);
	var winObj = window.open('', "lblWin", "width=740,height=325,top=200,left=250,toolbars=no,scrollbars=yes,status=no,resizable=no");
	winObj.document.writeln(docContainer.innerHTML);
	winObj.document.close();
	winObj.focus();
	winObj.print();
	winObj.close();
	return false;
}

function correctShipAdd (oid, act) {
	new Ajax.Request ('address_correct.php', {
		method: 'get',
		parameters: {
			action: act,
			oid: oid,
			company: (($('sa_company') !=null) ? $('sa_company').value : ''),
			name: (($('sa_name') !=null) ? $('sa_name').value : ''),
			street_address: (($('sa_street_address')!=null) ? $('sa_street_address').value : ''),
			suburb: (($('sa_suburb') !=null) ? $('sa_suburb').value : ''),
			city: (($('sa_city') !=null) ? $('sa_city').value : ''),
			postcode: (($('sa_postcode') !=null) ? $('sa_postcode').value : ''),
			state: (($('sa_state') !=null) ? $('sa_state').value : ''),
			state_name: (($('sa_state_name') !=null) ? $('sa_state_name').value : ''),
			country: (($('sa_country') !=null) ? $('sa_country').value : ''),
			telephone: (($('sa_telephone') !=null) ? $('sa_telephone').value : '')
		},
		onSuccess: function (transport) {
			$('shipaddrDiv').update(transport.responseText);
			if (act == 'correct_ship') {
				// Display
				$('shipaddrDiv').style.backgroundColor = '#ffb3b5';
				$('shipaddrDiv').style.height = '225px';
				$('shipaddrDiv').style.borderWidth = '1px';
				$('shipaddrDiv').style.borderColor = '#f00';
				$('shipaddrDiv').style.borderStyle = 'solid';
				jQuery('.shipaddrRow').css('height', '20px');
			}
			else {
				// Edit
				$('shipaddrDiv').style.backgroundColor = '#fff';
				$('shipaddrDiv').style.height = '190px';
				$('shipaddrDiv').style.borderStyle = 'none';
				$('addrbookDiv').style.visibility = 'hidden';
				jQuery('.shipaddrRow').css('height', '16px');
			}
		}
	});

	return false;
}

function getStateOptions(country, oid) {
	new Ajax.Request ('address_correct.php', {
		method: 'get',
		parameters: {
			action: 'reset_states',
			oid: oid,
			country: country
		},
		onSuccess: function (transport) {
			$('stateDiv').update(transport.responseText);
		}
	});

	return false;
}

function saCatchEvent (e, o, a) {
	var key = (window.event) ? window.event.keyCode : ((e.which) ? e.which : e.charCode);
	if (key == 13) {
		correctShipAdd (o, a);
	};

	return false;
}

function dispError(oid, fldid) {
	var errdivid = fldid+'Err';

	new Ajax.Request ('address_correct.php', {
		method: 'get',
		parameters: {
			action: 'display_error',
			oid: oid,
			fldid: fldid
		},
		onSuccess: function (transport) {
			$(errdivid).update(transport.responseText);
			$(errdivid).style.visibility = 'visible';
			$(errdivid).style.display = 'block';
			$(errdivid).style.backgroundColor = '#fff';
			$(errdivid).style.height = '50px';
			$(errdivid).style.width = '200px';
			$(errdivid).style.borderWidth = '1px';
			$(errdivid).style.borderColor = '#000';
			$(errdivid).style.borderStyle = 'double';
		}
	});
	setTimeout(function() {closeErrDiv(errdivid)}, 5000);
	return false;
}

function closeErrDiv(divid) {
	$(divid).style.visibility = 'hidden';
	$(divid).style.display = 'none';
	$('addrbookDiv').style.visibility = 'hidden';
	return false;
}

function closeAddrBook () {
	$('addrbookDiv').style.visibility = 'hidden';
	return false;
}

function writeMessage (id, a) {
	if ($('msgrowDiv')!=null) {
		if (a=='b') {
			$('msgrowDiv').style.visibility = 'hidden';
			$('msgrowDiv').innerHTML = '&nbsp;';
		}
		else if (a=='f') {
			var charnum = 0;
			var txt = '';

			switch (id) {
				case 'sa_company':
				case 'sa_name':
				case 'sa_street_address':
				case 'sa_suburb':
					charnum = 35 - $(id).value.length;
					break;
				case 'sa_city':
					charnum = 20 - $(id).value.length;
					break;
				case 'sa_telephone':
					charnum = 10 - $(id).value.length;
					break;
				default:
			}

			txt = (charnum > 0) ? charnum+' characters left' : '';
			$('msgrowDiv').style.visibility = 'visible';
			$('msgrowDiv').style.visibility = 'visible';
			$('msgrowDiv').style.width = '180px';
			$('msgrowDiv').style.height = '15px';
			$('msgrowDiv').style.padding = '5px';
			$('msgrowDiv').innerHTML = txt;
		}
	}

	return false;
}

function chooseCancelReason() {
	jQuery('#cancel_reason_dialog').dialog('open');
}

function cancelOrder() {
	if (confirm('Do you really want to cancel the order? This change is permanent and afterwards the order can no longer be edited.')) {
		var form = document.getElementById('order_status');
		var status;

		jQuery('#order_status').append('<input type="hidden" name="form-submit" value="Cancel">');

		//add the 'canceled' option to the drop down and select it
		jQuery('#status').append('<option value="6" selected="selected">Canceled</option>');

		document.getElementById('sub_status').disabled = true;

		//add code that unchecks the notify customer box
		if (confirm('Would you like the customer to be notified of this?')) form.elements['notify'].checked = true;
		else form.elements['notify'].checked = false;

		//if (form.onsubmit()) form.submit();
		jQuery('#order_status').submit();
	}
}

jQuery(document).ready(function($) {
	if($('#order_id_hidden').val() > 0) {
		runPackage($('#order_id_hidden').val(), '', '', 'package_list', 'editPackagesDiv');
	}

	var paymentSelectHandler = function(event) {
		var total = new Number();
		var amounts = $('.payment-amount');
		var appliedAmounts = $('.payment-amount-applied');
		var valid = true;

		$('.add-payment-id:checkbox').each(function(i) {
			var amtField = $(appliedAmounts.get(i));

			if ($(this).is(':checked')) {
				var amt = new Number($(amounts.get(i)).val());
				var val = amtField.val();
				var num = new Number(val);

				if (isNaN(num)) {
					alert(val + ' is not a valid dollar amount');
					amtField.val('');
					amtField.focus();
					valid = false;
				}
				else if (num == 0.00) {
					valid = false;
					amtField.attr('disabled', false);
					amtField.focus();
				}
				else if (num > amt) {
					alert(val + ' is greater than the payment amount of ' + amt);
					amtField.focus();
					valid = false;
				}
				else {
					total += num;
				}
			}
			else {
				amtField.attr('disabled', true);
			}
		});

		var enabledCount = $('.add-payment-id:checkbox:checked').size();
		if (enabledCount > 0 && valid === true) $('#add-payment-save').attr('disabled', false);
		else $('#add-payment-save').attr('disabled', true);

		$('#add-payment-total-allocated').html('$' + total.toFixed(2));
	};

	// binding done on ajax onLoad since live() does not support blur event
	// 1.4 supports this with focusout
	var onPaymentMethodLoad = function (hash) {
		$('.payment-amount-applied').blur(paymentSelectHandler);
	};

	$('#add-payment-method').click(function(event) {
		var paymentMethodId = $('#payment-method-id').val();
		var orderId = $('#order_id_hidden').val();
		$('#modal').jqm({ajax: 'add-payment-method.php?paymentMethodId=' + paymentMethodId + '&orderId=' + orderId, target: '#modal-content', modal: true, onLoad: onPaymentMethodLoad}).jqmShow();
	});

	$('.add-payment-id').live('click', paymentSelectHandler);

	$('#add-payment-save').live('click', function(event) {
		var data = $('form#add-payment-form').serialize();
		var orderId = $('#order_id_hidden').val();
		data += '&orderId=' + orderId;

		$.post('add-payment-method.php', data, function (data) {
			if (data.error) {
				alert(data.error);
			}
			else {
				$('#modal').jqm().jqmHide();
				window.location.reload();
			}
		}, 'json');
	});

	$('.remove-payment-allocation').click(function(event) {
		event.preventDefault();
		var response = confirm('Are you sure you want to remove this payment from the order?');

		if (response === true) {
			var paymentAllocationId = $(this).attr('id');
			$.post('add-payment-method.php', {action: 'remove_allocation', id: paymentAllocationId}, function(data) {
				window.location.reload();
			});
		}
	});

	// mark enabled product rma checkboxes with active class
	$('.rma-product-id:enabled').addClass('active');

	$('#create-rma').click(function(event) {
		var orderId = $('#order_id_hidden').val();
		var data = ['orderId='+orderId];
		$('input.rma-product-id:checked').each(function(i) {
			data.push('orderProductIds[]='+$(this).val());
		});
		$('#modal').jqm({ajax: 'create-rma-modal.php?' + data.join('&'), target: '#modal-content', modal: true, onLoad: function () {
			$('#follow-up-date').datepicker();
			$('#ui-datepicker-div').css('z-index', 3000);
		}}).jqmShow();
	});

	$('a.add-product-row').live('click', function(event) {
		event.preventDefault();
		var row = $(this).parents('tr');
		var newRow = row.clone();
		var count = newRow.children('.actions').children('.remove-product-row').length;
		newRow.children('.actions').children('.add-product-row').remove();
		if (count == 0) {
			newRow.children('.actions').append('&nbsp;<a href="#" class="remove-product-row">Remove</a>');
		}
		newRow.insertAfter(row);
	});

	$('a.remove-product-row').live('click', function(event) {
		event.preventDefault();
		var row = $(this).parents('tr');
		row.remove();
	});

	$('#quick-add-rma').live('click', function(event) {
		var data = $('form#rma-quick-add').serialize();
		var orderId = $('#order_id_hidden').val();
		data += '&orderId=' + orderId;

		$.post('create-rma-modal.php', data, function (data) {
			if (data.error) {
				alert(data.error);
				return;
			}
			else if (data.id) {
				$('#modal').jqm().jqmHide();
				window.location.href = 'rma-detail.php?id=' + data.id;
			}
		}, 'json');
	});

	$('.rma-product-id').click(function(event) {
		var enabledCount = $('.rma-product-id:checkbox:checked').size();
		if (enabledCount > 0) {
			$('#create-rma').attr('disabled', false);
		}
		else {
			$('#create-rma').attr('disabled', true);
		}
	});

	$('#linkAuth').live('click', function(event) {
		event.preventDefault();
		$('#authModal').jqm().jqmShow();
	});

	$('#linkCapture').live('click', function(event) {
		event.preventDefault();
		$('#captureModal').jqm().jqmShow();
	});

	auth_void_handler = function (data) {
		if (data.result == true) {
			$('#authModal').jqm().jqmHide();
			$('#indicator-authorized').attr('src', 'images/icons/cross.gif').attr('alt', 'No');
		}
		else {
			alert(data.message);
			$('#auth_void_process').attr('disabled', false);
		}
	}

	auth_amt_handler = function (data) {
		if (data.result == true) {
			$('#authModal').jqm().jqmHide();
			$('#indicator-authorized').attr('src', 'images/icons/tick.gif').attr('alt', 'Yes');
		}
		else {
			alert(data.message);
			$('#auth_amt_process').attr('disabled', false);
		}
	}

	refund_amt_handler = function (data) {
		if (data.result == true) {
			$('#captureModal').jqm().jqmHide();
			$('#indicator-captured').attr('src', 'images/icons/cross.gif').attr('alt', 'No');
			$('#linkCapture').show();
		}
		else {
			alert(data.message);
			$('#refund_amt_process').attr('disabled', false);
		}
	}

	capture_amt_handler = function (data) {
		if (data.result == true) {
			$('#captureModal').jqm().jqmHide();
			$('#indicator-captured').attr('src', 'images/icons/tick.gif').attr('alt', 'Yes');
			$('#linkAuth').hide();
		}
		else {
			alert(data.message);
			$('#capture_amt_process').attr('disabled', false);
		}
	}

	$('#auth_void_process').live('click', function() {
		$('#auth-throbber').show();
		$(this).attr('disabled', true);
		var orderId = $('#order_id_hidden').val();
		$.post("order_cc_transactions.php", {order_id: orderId, action: "void"}, auth_void_handler, "json");
	});

	$('#auth_amt_process').live('click', function() {
		$('#auth-throbber').show();
		$(this).attr('disabled', true);
		var orderId = $('#order_id_hidden').val();
		$.post("order_cc_transactions.php", {order_id: orderId, action: "auth", amount: $('#auth_amt').val()}, auth_amt_handler, "json");
	});

	$('#refund_amt_process').live('click', function() {
		$('#capture-throbber').show();
		$(this).attr('disabled', true);
		var orderId = $('#order_id_hidden').val();
		$.post("order_cc_transactions.php", {order_id: orderId, action: "refund", amount: $('#refund_amt').val()}, refund_amt_handler, "json");
	});

	$('#capture_amt_process').live('click', function() {
		$('#capture-throbber').show();
		$(this).hide();//attr('disabled', true);
		var orderId = $('#order_id_hidden').val();
		$.post("order_cc_transactions.php", {
			order_id: orderId,
			action: "capture",
			amount: $('#capture_amt').val()
		}, capture_amt_handler, "json");
	});

	$('#auth-throbber').live("ajaxComplete", function() {
		$(this).hide();
	});

	$('#capture-throbber').live("ajaxComplete", function() {
		$(this).hide();
	});

	$(document).keyup(function(event) {
		if (event.keyCode == 27) {
			// bind the escape key to close the modal
			$('.jqmWindow').jqm().jqmHide();
		}
	});

	var toggleDownHandler = function(event) {
		event.preventDefault();
		$('#cc-info-throbber').show();
		var orderId = $(this).attr('id');
		$('#cc-details').load('order_cc_details.php', 'orderId=' + orderId, function () {
			$('#cc-info-throbber').hide();
			$(this).slideDown();
			$('.toggle-cc-details').unbind().click(toggleUpHandler);
		});
	}

	var toggleUpHandler = function(event) {
		event.preventDefault();
		$('#cc-details').slideUp();
		$('.toggle-cc-details').unbind().click(toggleDownHandler);
	}

	$('.toggle-cc-details').click(toggleDownHandler);

	$('#change-cc').click(function(event) {
		var response = confirm('Does the current billing address on the order match the billing address of the card?');

		var orderId = $('#order_id_hidden').val();

		if (response == true) $('#modal').jqm({ajax: 'change-credit-card.php?order_id=' + orderId, target: '#modal-content', modal: true }).jqmShow();
		else window.location.href = 'edit_orders.php?selected_box=orders&oID=' + orderId;
	});

	$('#change-credit-card-button').live('click', function(event) {
		var data = $('form#credit-card-change').serialize();
		var orderId = $('#order_id_hidden').val();
		data += '&order_id=' + orderId;
		var button = $(this);
		button.attr('disabled', true);
		$('#change-cc-info-message').show();

		$.post('change-credit-card.php', data, function (data) {
			if (data.error) {
				alert(data.error);
				button.attr('disabled', false);
				$('#change-cc-info-message').hide();
				return;
			}
			else if (data.id) {
				$('#modal').jqm().jqmHide();
				window.location.reload();
			}
		}, 'json');
	});

	$('#existing-change-credit-card-button').live('click', function(event) {
		var data = $('form#existing-credit-card-change').serialize();
		var orderId = $('#order_id_hidden').val();
		data += '&order_id=' + orderId + '&action=save_existing';
		var button = $(this);
		button.attr('disabled', true);
		$('#change-cc-info-message').show();

		$.post('change-credit-card.php', data, function (data) {
			if (data.error) {
				alert(data.error);
				button.attr('disabled', false);
				$('#change-cc-info-message').hide();
				return;
			}
			else if (data.id) {
				$('#modal').jqm().jqmHide();
				window.location.reload();
			}
		}, 'json');
	});

	$('#maxMindLink').click(function(event) {
		event.preventDefault();
		$('#maxmind-info').slideToggle();
	});

	$('#followup_date').datepicker({ dateFormat: 'yy-mm-dd' });
	$('#promised_ship_date').datepicker({ dateFormat: 'yy-mm-dd' });

	$('.po-alloc-display').click(function(event) {
		var opId = this.getAttribute('opid');
		$('#modal').jqm({ajax: 'orders_new_po_alloc.php?op_id=' + opId + '&action=display', target: '#modal-content', modal:true, onLoad: initPOAlloc}).jqmShow();
	});

	//clear button
	$('#po2oa-clear').live('click', function(event) {
		$('.po2oa-checkbox').attr('checked', false);
		$('.po2oa-allocated-quantity').val('');
		$('#total-qty-allocated').html('0');
	});

	//cancel button
	$('#po2oa-cancel').live('click', function(event) {
		$('#modal').jqm().jqmHide();
	});

	//save button
	$('#po2oa-save').live('click', function(event) {
		var data = $('form#po2oa-edit').serialize();
		var opId = $('#po2oa-order-product-id').val();

		$.post('orders_new_po_alloc.php?action=save&op_id=' + opId, data, function (data) {
			if (data.error) {
				alert(data.error);
			}
			else {
				$('#po_order_alloc_op_' + opId).html(data);
				$('.po-alloc-display').click(function(event) {
					var opId = this.getAttribute('opid');
					$('#modal').jqm({ajax: 'orders_new_po_alloc.php?op_id=' + opId + '&action=display', target: '#modal-content', modal:true, onLoad: initPOAlloc}).jqmShow();
				});
				$('#modal').jqm().jqmHide();
			}
		});
	});

	jQuery('.exclude_forecast').change(function() {
		var $check = jQuery(this);
		jQuery.ajax({
			url: '/admin/ipn_editor.php?ajax=1&action=exclude_forecast',
			type: 'GET',
			dataType: 'json',
			data: encodeURI($check.attr('name'))+'='+($check.is(':checked')?1:0),
			success: function(data) {
				if (data.status == 1) $check.css('background-color', '#cfc');
				else {
					$check.css('background-color', '#fcc');
					alert(data.message);
				}
			},
			error: function() {
				alert('Your exclude action failed to save appropriately.');
			}
		});
	});
});

function initPOAlloc() {
	jQuery(document).ready(function($) {
		var allocUpdateHandler = function(event) {
			var qty_allocated = new Number();
			var allocated_fields = $(".po2oa-allocated-quantity");
			var available_fields = $(".po2oa-available-quantity");
			var ordered_field = $(".po2oa-ordered-quantity");
			var valid = true;

			$('.po2oa-checkbox:checkbox').each(function(i) {
				var qty_field = $(allocated_fields.get(i));
				if ($(this).is(':checked')) {
					var amt = new Number($(available_fields.get(i)).val());
					var val = qty_field.val();
					var num = new Number(val);

					if (isNaN(num)) {
						alert(val + ' is not a valid quantity');
						qty_field.val('');
						qty_field.focus();
						valid = false;
					}
					else if (num == 0) {
						valid = false;
						qty_field.attr('disabled', false);
						qty_field.focus();
					}
					else if (num > amt) {
						alert(val + " is greater than the available quantity of " + amt);
						qty_field.focus();
						valid = false;
					}
					else if (num < 0) {
						alert(val + " is negative. Please enter a positive amount.");
						qty_field.focus();
						valid = false;
					}
					else {
						qty_allocated += num;
						if (qty_allocated > ordered_field.val()) {
							alert("You have allocated more items than were ordered. Please readjust your allocations");
							valid = false;
						}
					}
				}
				else{
					qty_field.attr('disabled', true);
					qty_field.val('');
				}
			});

			if (valid === true) {
				$('#po2oa-save').attr('disabled', false);
			}
			else{
				$('#po2oa-save').attr('disabled', true);
			}

			$('#total-qty-allocated').html( qty_allocated.toFixed(0));
		}

		$('.po2oa-checkbox').live('click', allocUpdateHandler);
		$('.po2oa-allocated-quantity').blur(allocUpdateHandler);

	});
}
