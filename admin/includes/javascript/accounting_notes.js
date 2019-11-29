function aon_init(customers_id, order_id){
	var current_url = window.location.href.split('?')[0];
	jQuery('#aon_customers_id').val(customers_id);
	jQuery('#aon_order_id').val(order_id);
                
	jQuery('#aon_modal').jqm().jqmHide();

	var ajax_url = current_url + '?aon_action=aon_init&customers_id=' + customers_id;
	if(order_id != ''){
		ajax_url = ajax_url + '&order_id=' + order_id;
	}
		
	jQuery('#aon_modal_content').html('Loading...');
	jQuery('#aon_modal').jqm({ajax: ajax_url, target: '#aon_modal_content', modal: true}).jqmShow();

}

function aon_add_button(){
	
	var current_url = window.location.href.split('?')[0];
		
	var order_id = jQuery('#aon_order_id').val();
	var text = jQuery('#aon_text').val();
	var customers_id = jQuery('#aon_customers_id').val();

	jQuery.post(
		current_url,
		{
			aon_action: 'aon_add',
			aon_order_id: order_id,
			aon_text: text,
			customers_id: customers_id
		},
		function (data, status_text, xhr){
			aon_init(customers_id, order_id);
		}
	);

}

function aon_delete(aon_id){
	
	var current_url = window.location.href.split('?')[0];
	
	jQuery.post(
		current_url,
		{
			aon_action: 'aon_delete',
			aon_id: aon_id
		},
		function (data, status_text, xhr){
			aon_init();
		}
			
	);
}
