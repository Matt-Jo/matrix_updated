jQuery(document).ready(function($) {

	jQuery(document.body).append('<div id="chooseShippingDialog"><div id="chooseShippingContent"><center><img src="/admin/images/icons/throbber.gif" border="0"/></center></div></div>');

	jQuery( "#chooseShippingDialog" ).dialog({
      		autoOpen: false,
      		height: 500,
      		width: 750,
      		modal: true,
		title: 'Choose a Shipping Method',
      		buttons: {
			Select: function() {
				window[__chooseShippingCallbackFunction](jQuery('#__CS_shipping_method_id').val(),jQuery('#__CS_cost').val());
		          	jQuery( this ).dialog( "close" );
			},
		        Cancel: function() {
		          	jQuery( this ).dialog( "close" );
		        }
      		},
      		close: function() {
			jQuery('#chooseShippingContent').html('<center><img src="/admin/images/icons/throbber.gif" border="0"/></center>');
	      }
   	 });
});

var __chooseShippingCallbackFunction = '';

function chooseShippingDialog(original_order_id, customer_id, products, shipping_weight, freight, street_address, suburb, city, state, postcode, country, callback){

	jQuery( "#chooseShippingDialog" ).dialog( "open" );
	
	jQuery.post('/admin/choose_shipping.php',
		{
			original_order_id: original_order_id,
			customers_id: customer_id,
			products: products,
			shipping_weight: shipping_weight,
			freight: freight,
			street_address: street_address,
			suburb: suburb,
			city: city,
			state: state,
			postcode: postcode,
			country: country
		},
		function(data){
			jQuery('#chooseShippingContent').html(data);
		}
	);

	__chooseShippingCallbackFunction = callback;
}

function CsSelectShipping(shipping_method_id, cost){
	jQuery('input[name=__CS_shipping_method_id]').val(shipping_method_id);
	jQuery('input[name=__CS_cost]').val(cost);
}
