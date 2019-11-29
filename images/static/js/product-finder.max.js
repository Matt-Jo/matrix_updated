var ajax_call;


var pagination_tpl, product_result_tpl;

jQuery.ajax({
	url: '/includes/templates/partial-pagination.mustache.html?PageSpeed=off',
	dataType: 'text',
	success: function(tpl) {
		pagination_tpl = tpl;
		Mustache.parse(pagination_tpl);
	}
});
jQuery.ajax({
	url: '/includes/templates/partial-product-result.mustache.html?PageSpeed=off',
	dataType: 'text',
	success: function(tpl) {
		product_result_tpl = tpl;
		Mustache.parse(product_result_tpl);
	}
});

function loading() {
	var self = this;
	this.status = 0;

	this.start = function() {
		if (self.status == 0) self.run();
		else self.status = 1;
	};

	this.run = function() {
		self.status = 1;

		$set = jQuery('.loading-bar');
		$set.each(function(index, element) {
			setTimeout(function() {
				jQuery(element).css('visibility', 'visible').hide().css('opacity', '').fadeIn(300, function() {
					jQuery(element).fadeTo(600, 0);
				});
				if (index + 1 == $set.length) {
					if (self.status == 1) self.run();
					else if (self.status == 2) self.status = 0;
				}
			}, 150*index);
		});
	};

	this.stop = function() {
		self.status = 2;
	};
}

var loader = new loading();

var selected_page = null;
var pagination_active = false;
var see_all = false;

var last_results = null;

function reset_vars() {
	selected_page = null;
	last_results = null;
}

function add_selection() {
	var data = { refinement_data: {}, ajax: 1, focus: focus };
	jQuery('.find-val').each(function() {
		if (jQuery(this).val() == 'All') return;
		if (jQuery(this).hasClass('disabled')) return; // if other selections have removed this as a possibility, don't add it to the query

		if (this.nodeName.toLowerCase() == 'input' || this.nodeName.toLowerCase() == 'select' || this.nodeName.toLowerCase() == 'textarea') {
			if ((jQuery(this).attr('type') != 'radio' && jQuery(this).attr('type') != 'checkbox') || jQuery(this).is(':checked')) data.refinement_data[jQuery(this).attr('name')+':'+jQuery(this).val()] = jQuery(this).attr('name')+':'+jQuery(this).val();
		}
		else if (jQuery(this).hasClass('selected')) data.refinement_data[jQuery(this).attr('data-key')+':'+jQuery(this).attr('data-val')] = jQuery(this).attr('data-key')+':'+jQuery(this).attr('data-val');
	});

	if (pagination_active) {
		data.results_per_page = jQuery('#showselect').val();
		data.page = selected_page;
		data.sort_by = jQuery('#sortby').val();
	}

	if (see_all) {
		data['see-all'] = 1;
	}
	else see_all = 1; // once we get results, there's no reason to take them away again

	if (ajax_call) ajax_call.abort();
	else loader.start();

	ajax_call = jQuery.ajax({
		url: '/'+page+'.php',
		type: 'get',
		data: data,
		dataType: 'json',
		beforeSend: function() {
			jQuery('.add-to-cart-form').off('submit');
			jQuery('.pf-result-product .product-result').removeClass('product-result-visible');
			jQuery('#advanced-toggle .selections').fadeOut();
			jQuery('.pf-result-summary .results a').fadeOut();
			jQuery('.pagination').fadeOut(400, function() {
				jQuery('.pagination').html('');
			});
			jQuery('.pf-result-summary.bottom').slideUp();

			reset_vars();
		},
		success: function(data, textStatus, jqXHR) {
			ajax_call = undefined;
			loader.stop();
			if (data.products) {
				jQuery('.pf-result-product').css('visibility', 'visible');

				render_results(data.products);

				last_results = data.products;
			}
			else {
				jQuery('.pf-result-product').css('visibility', 'hidden');
			}

			if (data.pagination) {
				data.pagination.cdn = cdn;
				var $pagination = jQuery(Mustache.render(pagination_tpl, data.pagination));

				jQuery('.pagination').append($pagination);

				pagination_active = true;
			}
			else pagination_active = false;

			jQuery('#advanced-toggle .selections').text(data.selections).fadeIn();
			if (data.pagination) jQuery('.pagination').fadeIn();
			else if (data.products) jQuery('.pf-result-summary .results a').attr('href', data.result_lnk).html(data.result_cnt+' Results [ <span>see all</span> ]').fadeIn();
			else jQuery('.pf-result-summary .results a').attr('href', '#').html('0 Results').fadeIn();
			jQuery('.pf-result-summary.bottom').slideDown();

			open_all_options();

			if (data.products) manage_options(data.enabled_attributes);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			ajax_call = undefined;
			if (textStatus == 'abort') return;
			loader.stop();
			if (confirm('There was an error loading your results. Click OK to be redirected to our catalog with your current selections.')) {
				delete data.ajax;
				for (var i in data.refinement_data) {
					if (!data.refinement_data.hasOwnProperty(i)) continue;
					var prop = i.split(':');
					if (prop[0] == 'Category') delete data.refinement_data[i];
					if (prop[1] == '') delete data.refinement_data[i];
				}
				window.location = '/index.php?cPath='+cPath+'#'+jQuery.param(data, false);
			}
		}
	});
}

function render_results(results) {
	for (var i=0; i<results.length; i++) {
		results[i].page = page;
		results[i].cdn = cdn;

		// if this is first load, we're getting this from the server as the pagination isn't up yet
		if (jQuery('.resview.on').hasClass('grid')) results[i]['grid?'] = 1;
		else if (jQuery('.resview.on').hasClass('list')) results[i]['list?'] = 1;

		var $newprod = jQuery(Mustache.render(product_result_tpl, results[i]));

		jQuery('.pf-result-product').append($newprod);

		$newprod.addClass('product-result-visible');

		delete results[i]['grid?'];
		delete results[i]['list?'];
	}

	add_product_to_cart();
}

function open_all_options() {
	jQuery('.protected').removeClass('protected');
	jQuery('.disabled').each(function() {
		if (jQuery(this).hasClass('advanced-opt-label')) {
			jQuery('[data-key=\''+jQuery(this).attr('data-key')+'\'][value=\''+jQuery(this).attr('data-val')+'\']').attr('disabled', false);
		}
		else {
			jQuery(this).find('input').attr('disabled', false);
		}
		jQuery(this).removeClass('disabled');
	});
}

jQuery('.show-avail').live('click', function(e) {
	e.preventDefault();

	jQuery('.avail-details-'+jQuery(this).attr('data-pid')).toggle();
});

jQuery('#showselect').live('change', function() {

	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	action = '--paging--shownum';
	label = '--val['+jQuery(this).val()+']';

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);

	add_selection();
});

jQuery('.page-link').live('click', function(e) {
	e.preventDefault();
	if (jQuery(this).hasClass('current')) return;

	selected_page = jQuery(this).attr('data-page');

	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--paging--page';
	label = '--lnk['+selected_page+']';

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);

	add_selection();
});

jQuery('#sortby').live('change', function() {

	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	action = '--paging--sortby';
	label = '--val['+jQuery(this).find('option:selected').text()+']';

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);

	add_selection();
});

jQuery('#page-goto-button').live('click', function(e) {
	e.preventDefault();
	selected_page = jQuery('.pjgoto').val().replace(/\D/, '');

	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--paging--page';
	label = '--goto['+selected_page+']';

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);

	add_selection();
});

jQuery('.resview').live('click', function(e) {
	e.preventDefault();
	if (jQuery(this).hasClass('on')) return;

	jQuery('.resview.on').removeClass('on').addClass('off');
	jQuery(this).removeClass('off').addClass('on');

	var context = jQuery(this).hasClass('grid')?'grid':'list';

	jQuery('.add-to-cart-form').off('submit');

	jQuery('.pf-result-product .product-result').removeClass('product-result-visible', function () {
		jQuery(this).remove();
	});

	setTimeout(function() {
		render_results(last_results);
	}, 450);

	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--paging--resultsview';
	label = '--val['+context+']';

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);

	jQuery.ajax({
		url: '/'+page+'.php',
		type: 'post',
		data: { 'change-results-view': context },
	});
});

jQuery('.show-all-results').live('click', function(e) {
	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--link--seeall';
	var label = '';
	jQuery('.selected').each(function() {
		label += '--'+jQuery(this).attr('data-key')+'[';
		if (jQuery(this).attr('data-desc')) label += jQuery(this).attr('data-desc');
		else if (jQuery(this).attr('data-val')) label += jQuery(this).attr('data-val');
		else if (jQuery(this).val()) label += jQuery(this).val();
		label += ']';
	});

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);

	e.preventDefault();

	see_all = true;
	add_selection();
});

jQuery('.category-header, .application-header').click(function() {
	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--link--'+jQuery(this).attr('class');
	var label = '--cat['+jQuery(this).attr('data-name')+']';

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);
});
jQuery('.subcat-link, .application-link').click(function() {
	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--link--'+jQuery(this).attr('class');
	var label = '--cat['+jQuery(this).text()+']';

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);
});
/*jQuery('.pf-result-summary .results a').click(function() {
	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--link--seeall';
	var label = '';
	jQuery('.selected').each(function() {
		label += '--'+jQuery(this).attr('data-key')+'[';
		if (jQuery(this).attr('data-desc')) label += jQuery(this).attr('data-desc');
		else if (jQuery(this).attr('data-val')) label += jQuery(this).attr('data-val');
		else if (jQuery(this).val()) label += jQuery(this).val();
		label += ']';
	});

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);
});*/
jQuery('.lnk-to-prod').live('click', function() {
	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--link--product';
	var label = '--prod['+jQuery(this).attr('data-key')+']';

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);
});
jQuery('.add-to-cart').live('click', function() {
	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--add-to-cart';
	var label = '--prod['+jQuery(this).attr('data-key')+']';

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);
});

jQuery('#advanced-toggle').click(function(e) {
	e.preventDefault();

	var category = '--PFIND';
	category += '--'+major;
	if (minor) category += '+'+minor;

	var action = '--link--advanced-options';
	var label = '--'+(jQuery('.pf-options-hidden').is(':visible')?'hide':'show');

	ga('send', 'event', category, action, label);
	//_gaq.push(['_trackEvent', category, action, label]);

	jQuery('.pf-options').toggleClass('pf-options-hidden');
});

var product_adds = [];
var pa_counter = 0;

jQuery('.product-add').live('submit', function(e) {
	e.preventDefault();

	var $frm = jQuery(this);

	var data = $frm.serialize();
	data += '&ajax=1';

	product_adds[pa_counter] = jQuery.ajax({
		url: $frm.attr('action'),
		type: $frm.attr('method').toLowerCase(),
		data: data,
		dataType: 'json',
		timeout: 8000,
		beforeSend: function() {
			jQuery('#'+$frm.attr('id')+'-holder').css('background-color', '#fcfcac');
			jQuery('.add-notice').hide();
		},
		success: function(data, textStatus, jqXHR) {
			jQuery('#'+$frm.attr('id')+'-holder').animate({ backgroundColor: '#acfcac' }, {
				duration: 500,
				easing: 'swing',
				complete: function() {
					jQuery('#'+$frm.attr('id')+'-holder').animate({ backgroundColor: '#ffffff' }, 500, 'swing');
				}
			});

			jQuery('#cart-count').text(data.cart_qty);
			jQuery('#'+$frm.attr('id')+'-notice').css('color', '#090').html('You have successfully added<br>this item to your cart.').show(); //.fadeOut(5000);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if (textStatus == 'abort') {
				jQuery('#'+$frm.attr('id')+'-holder').animate({ backgroundColor: '#ffffff' }, 500, 'swing');
				return;
			}

			jQuery('#'+$frm.attr('id')+'-holder').animate({ backgroundColor: '#fcacac' }, {
				duration: 500,
				easing: 'swing',
				complete: function() {
					jQuery('#'+$frm.attr('id')+'-holder').animate({ backgroundColor: '#ffffff' }, 500, 'swing');
				}
			});

			jQuery('#'+$frm.attr('id')+'-notice').css('color', '#900').html('There was a problem adding<br>this item to your cart.').show();

			// make cart note a failure notice
		},
		complete: function(jqXHR, textStatus) {
			product_adds[pa_counter] = undefined;
		}
	});

	pa_counter++;
});