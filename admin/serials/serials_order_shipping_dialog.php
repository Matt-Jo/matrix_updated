<div id="serials_dialog_container">
	<div id="serials_dialog" style="display:none;" class="jqmWindow">

		<div id="serials_diaglog_titlebar">
		<span style="float:left; margin: 3px 0 0 10px;">Previously Entered Serials</span>
		<img src="/admin/images/serials/title-bar-close.png" id="serials_menu_close" style="float: right; margin-right: 3px;" onClick="$('serials_dialog').hide();refreshSerialsList($('orders_products_id').value);"/>
		</div>

		<div id="previously_entered_serials"></div>

		<div id="serials_dialog_content" style="display: none;">

	<p class="serials_dialog_title">Enter Serials</p>
	<input type="hidden" name="order_id" id="order_id">
	<input type="hidden" name="order_products_id" id="orders_products_id">
	<input type="hidden" name="product_id" id="product_id">
	<input type="hidden" name="ipn_id" id="ipn_id">
	<input type="hidden" name="qty" id="qty">
	<table>
		<tr>
			<td>Enter Serial for product: </td>
			<td id="product_name"></td>
			<td>
				<input type="text" name="serial_autocomplete" id="serial_autocomplete">
				<input type="hidden" name="serial_id" id="serial_id">
				<input type="button" name="submit" value="ok" id="add_serial_button" />
				<input type="button" name="done" value="done" onClick="$('serials_dialog').hide();refreshSerialsList($('orders_products_id').value);">
			</td>
		</tr>
		<tr><td></td></tr>
		<tr>
			<td colspan="2">Serials Needed: <input type="text" name="serials_needed" id="serials_needed" disabled size="3" style="background-color:#ffffff; color:#000000; border:1px solid #ffffff;" ></td>
			<td>Serials Remaining: <input type="text" name="serials_remaining" id="serials_remaining" disabled size="3" style="background-color:#ffffff; color:#000000; border:1px solid #ffffff;" ></td>
		</tr>
	</table>

	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#serial_autocomplete').autocomplete({
			delay: 600,
			source: function(request, callback) {
				autocompleteHelper(request.term, callback);
			},
			select: function(event, ui) {
				event.preventDefault();
				setTimeout(function() {
					$('#add_serial_button').focus();
				}, 100);
				$('#serial_autocomplete').val(ui.item.label);
				$('#serial_id').val(ui.item.value);
			},
			focus: function(event, ui) {
				event.preventDefault();
			}
		}).keypress(function(event) {
			if (
				(event.which	&& event.which == 13	) ||
				(event.keyCode && event.keyCode == 13 )
			) {
				//$('#add_serial_button').focus();
				autocompleteHelper($('#serial_autocomplete').val(), function(data) {
					$('#serial_autocomplete').val(data[0].label);
					$('#serial_id').val(data[0].value),
					$('#add_serial_button').focus();
					$('#serial_autocomplete').autocomplete('close');
				});
			}
		});

		$('#add_serial_button').click(function(event) {
			add_serial_to_order(
				$('#serial_id').val(),
				$('#ipn_id').val(),
				$('#order_id').val(),
				$('#serial_autocomplete').val(),
				$('#orders_products_id').val()
			);
		});
	});
	function autocompleteHelper(term, callback) {
		if (jQuery('#serial_autocomplete').val() == '') {
			return 0;
		}
		params = {
			action: 'generic_autocomplete',
			search_type: 'serial',
			ipn_id: jQuery('#ipn_id').val(),
			term: term
		}
		jQuery.get('/admin/serials_ajax.php', params, function(data) {
			callback(data);
		}, "json");
	}
	</script>
	</div>
	</div>
</div>
<div id="serials_release_dialog_container">
	<div id="serials_release_dialog" style="display: none;">
	Would you like to release the allocation of serialized items on this order?<br/><br/>
	<input type="button" value="Yes" onClick="deallocate_serials(<?= $oID?>);">
	<input type="button" value="No" onClick="document.forms['order_status'].submit();">
	</div>
</div>
<script type="text/javascript">
	function refreshSerialsList(orders_products_id) {
		new Ajax.Updater('serials_display_' + orders_products_id, ordersProductsSerialsUrls[orders_products_id], { method: 'get' });
	}
</script>
