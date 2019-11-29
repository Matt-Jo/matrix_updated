jQuery(document).ready(function($) {
	cdm_init();
});

var current_url = window.location.href.split('?')[0];

function cdm_init() {
	var get_data = {
		cdm_action: 'cdm_init',
		customers_id: jQuery('#master_customers_id').val()
	};
				
	jQuery('#cdm_content').html('Loading...');
	jQuery.get(
		current_url,
		get_data,
		function(data, status_text, xhr) {
			jQuery('#cdm_content').html(data);
		}
	);
}

function cdm_add_button() {
	var category_id = jQuery('#category_selector').val();
	var customers_id = jQuery('#master_customers_id').val();
	var discount = jQuery('#cdm_discount').val();
	var expires_date = jQuery('#cdm_expires_date').val();

	var post_data = {
		cdm_action: 'cdm_add',
		category_id: category_id,
		customers_id: customers_id,
		discount: discount,
		expires_date: expires_date
	};

	jQuery.post(
		current_url,
		post_data,
		cdm_init
	);
}

function cdm_delete(cdm_id) {
	var post_data = {
		cdm_action: 'cdm_delete',
		id: cdm_id,
		customers_id: jQuery('#master_customers_id').val()
	};
	
	jQuery.post(
		current_url,
		post_data,
		cdm_init
	);
}

function cdm_toggle_status(cdm_id) {
	var checkbox_value = jQuery('#status_' + cdm_id).attr('checked');

	var status_value = '1';
	if (!checkbox_value) status_value = '0';

	var post_data = {
		cdm_action: 'cdm_status',
		id: cdm_id,
		status: status_value,
		customers_id: jQuery('#master_customers_id').val()
	};

	jQuery.post(
		current_url,
		post_data,
		cdm_init
	);
}

function cdm_category_selector_init() {
	// MMD - BEGIN CATEGORY SELECTOR - copied and adapted from manage_products.js				 
	jQuery('#category_selector').change(function() {
		var category_id = jQuery(this).val();

		jQuery(this).find('option').each(function() {
			if (jQuery(this).attr('value') == category_id) return;
			jQuery(this).remove();
		});

		if (category_id == -1) {
			// we're backing up
			category_list.selected_list.pop();
			if (category_list.selected_list.length) {
				// there's a previously selected category to back up to
				category_id = category_list.selected_list[category_list.selected_list.length - 1];

				if (category_list.selected_list.length > 1) {
					previous_category_id = category_list.selected_list[category_list.selected_list.length - 2];

					for (var j=0; j<category_list.selections[previous_category_id].length; j++) {
						if (category_list.selections[previous_category_id][j]['id'] == category_id) {
							jQuery(this).prepend('<option value="'+category_id+'">'+category_list.selections[previous_category_id][j]['name']+' ['+(category_list.selected_list.length-1)+']</option>');
						}
					}
				}
				else {
					for (var j=0; j<category_list.top_level.length; j++) {
						if (category_list.top_level[j]['id'] == category_id) {
							jQuery(this).prepend('<option value="'+category_id+'">'+category_list.top_level[j]['name']+'</option>');
						}
					}
				}

				jQuery(this).val(category_id);
				for (var i=0; i<category_list.selections[category_id].length; i++) {
					jQuery(this).append('<option value="'+category_list.selections[category_id][i]['id']+'">'+category_list.selections[category_id][i]['name']+' ['+category_list.selected_list.length+']</option>');
				}
			}
			else {
				// we're back at the top level
				jQuery(this).find('option').remove();
				jQuery(this).append('<option value="">All</option>');
				jQuery(this).val('');

				for (var i=0; i<category_list.top_level.length; i++) {
					jQuery(this).append('<option value="'+category_list.top_level[i]['id']+'">'+category_list.top_level[i]['name']+'</option>');
				}
			}
		}
		else {
			// we selected a category
			category_list.selected_list.push(category_id);
			jQuery(this).append('<option value="-1">Back One Level</option>');

			for (var i=0; i<category_list.selections[category_id].length; i++) {
				jQuery(this).append('<option value="'+category_list.selections[category_id][i]['id']+'">'+category_list.selections[category_id][i]['name']+' ['+category_list.selected_list.length+']</option>');
			}
		}
	});
	//MMD - END CATEGORY SELECTOR
}
