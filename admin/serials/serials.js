// This function needs to go. Should be done with CSS.
function center_div(el) {
	let $el = jQuery('#'+el);
	if (typeof window.innerHeight != 'undefined') {
		$el.css('top', Math.round(document.viewport.getScrollOffsets().top + (window.innerHeight - $el.height())/2)+'px');
		$el.css('left', Math.round(document.viewport.getScrollOffsets().left + (window.innerWidth - $el.width())/2)+'px');
	}
	else {
		$el.css('top', Math.round(document.body.scrollTop + (jQuery('body')[0].clientHeight - $el.height())/2)+'px');
		$el.css('left', Math.round(document.body.scrollLeft + (jQuery('body')[0].clientWidth - $el.width())/2)+'px');
	}
}

//---------------------------------------
// order details
//---------------------------------------
function open_serials_release_dialog() {
	let orders_id = jQuery('#orders_id').val();
	let status_id = jQuery('#status').val();
	let substatus_id = jQuery('#sub_status').val();

	if ((status_id == '11' && substatus_id == '5') || (status_id != '2' && status_id != '3' && status_id != '6')) {
		jQuery.ajax({
			url: '/admin/serials_ajax.php',
			data: {
				action: 'check_order_for_serials',
				order_id: orders_id,
			},
			success: function(data) {
				if (data == 'true') {
					center_div('serials_release_dialog');
					jQuery('#serials_release_dialog').show();
				}
				else jQuery('#order_status').submit();
			},
		});
	}
	else jQuery('#order_status').submit();
}

function deallocate_serials(orderid) {
	jQuery.ajax({
		url: '/admin/serials_ajax.php',
		data: {
			action: 'deallocate_serials',
			order_id: orderid
		},
		success: function(data) {
			document.forms['order_status'].submit();
		}
	});
}

function open_serials_order_dialog(product_id, product_qty, ipn_id, order_id, orders_products_id) {
	center_div('serials_dialog');

	jQuery('#order_id').val(order_id);
	jQuery('#orders_products_id').val(orders_products_id);
	jQuery('#product_id').val(product_id);
	jQuery('#ipn_id').val(ipn_id);
	jQuery('#qty').val(product_qty);
	jQuery('#serials_needed').val(product_qty);

	jQuery('#serials_dialog').jqm().jqmShow();
	jQuery('#serials_dialog_content').hide();

	jQuery.ajax({
		url: '/admin/serials_ajax.php',
		data: {
			action: 'get_previously_entered_serials_orders',
			ipn_id: ipn_id,
			order_id: order_id,
			orders_products_id: orders_products_id,
		},
		success: function(data) {
			jQuery('#previously_entered_serials').html(data);
			check_order_qty();
			jQuery('#serial_autocomplete').focus();
			jQuery('#serials_dialog_content').show();
		}
	});

	jQuery('#serial_autocomplete').focus();
}

function check_order_qty() {
	jQuery('#serials_remaining').val(parseInt(jQuery('#serials_needed').val()) - parseInt(jQuery('#num_serials_entered').val()));

	if (parseInt(jQuery('#num_serials_entered').val()) >= parseInt(jQuery('#qty').val())) {
		jQuery('#serial_autocomplete').attr('disabled', true);
		jQuery('#add_serial_button').attr('disabled', true);
		jQuery('#table_row_'+jQuery('#orders_products_id').val()).removeClass('unallocated').addClass('allocated');

		if (allItemsInStock != 0) jQuery('#ship_fedex').attr('disabled', false);
	}
	else {
		jQuery('#serial_autocomplete').attr('disabled', false);
		jQuery('#add_serial_button').attr('disabled', false);
		jQuery('#table_row_'+jQuery('#orders_products_id').val()).removeClass('allocated').addClass('unallocated');
		jQuery('#ship_fedex').attr('disabled', true);
	}
}

function delete_serial_from_order(serial_id) {
	jQuery.ajax({
		url: '/admin/serials_ajax.php',
		data: {
			action: 'delete_serial_from_order',
			serial_id: serial_id,
			order_id: jQuery('#order_id').val(),
			ipn_id: jQuery('#ipn_id').val(),
			orders_products_id: jQuery('#orders_products_id').val(),
		},
		success: function(data) {
			jQuery('#previously_entered_serials').html(data);
			check_order_qty();
		}
	});
}

function delete_all_ipn_serials(stock_id) {
	jQuery.ajax({
		url: '/admin/serials_ajax.php',
		data: {
			action: 'delete_all_ipn_serials',
			stock_id: stock_id,
			order_id: jQuery('#order_id').val(),
			ipn_id: jQuery('#ipn_id').val(),
			orders_products_id: jQuery('#orders_products_id').val(),
		},
		success: function(data) {
			jQuery('#previously_entered_serials').html(data);
			check_order_qty();
		}
	});
}

function add_serial_to_order(serial_id, ipn_id, order_id, serial_autocomplete, orders_products_id) {
	if (serial_autocomplete.length == 0) {
		alert("A valid serial number is required.");
		return false;
	}

	if (serial_id.length == 0) {
		alert("A valid serial number is required.");
		return false;
	}

	jQuery.ajax({
		url: '/admin/serials_ajax.php',
		data: {
			action: 'add_serial_order',
			serial_id: serial_id,
			ipn_id: ipn_id,
			order_id: order_id,
			orders_products_id: orders_products_id
		},
		success: function(data) {
			jQuery('#previously_entered_serials').html(data);
			check_order_qty();
		}
	});

	jQuery('#serial_autocomplete').val('').focus();
}
