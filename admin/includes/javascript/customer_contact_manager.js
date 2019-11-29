jQuery(document).ready(function($) {

	//launch the modal when the manage addl contacts link is clicked
	$('#ccm_init').click(function(event) {
		ccm_init();
	});

});

var current_url = window.location.href.split('?')[0];

function ccm_init(){	
	var customers_id = jQuery('#master_customers_id').val();
                
	jQuery('#ccm_modal').jqm().jqmHide();
		
	jQuery('#ccm_modal_content').html('Loading...');
	jQuery('#ccm_modal').jqm({ajax: current_url + '?ccm_action=ccm_init&customers_id=' + customers_id, target: '#ccm_modal_content', modal: true}).jqmShow();

}

function ccm_add_button(){
	
	var type = jQuery('#ccm_type').val();
	var email_address = jQuery('#ccm_email_address').val();
	var customers_id = jQuery('#master_customers_id').val();

	var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
    
	if(!pattern.test(email_address)){
		alert('Please enter a valid email address.');
		return false;
	}

	jQuery.post(
		current_url,
		{
			ccm_action: 'ccm_add',
			contact_type_id: type,
			email_address: email_address,
			customers_id: customers_id
		},
		function (data, status_text, xhr){
			ccm_init();
		}
	);

}

function ccm_delete(ccm_id){
	
	jQuery.post(
		current_url,
		{
			ccm_action: 'ccm_delete',
			contact_id: ccm_id,
			customers_id: jQuery('#master_customers_id').val()
		},
		function (data, status_text, xhr){
			ccm_init();
		}
			
	);
}
