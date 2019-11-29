//jQuery('.productListing .productItemListing').children().css('visibility', 'hidden');

search_control = {
	cache: {},
	context: '',
	page: '',
	//count_control: {},
	last_data: null,
	last_request: null,
	ajax_call: null,
	interacted_element: null,

	refresh_results: function(refinement_opt, refinement_data, event) {
		//event.preventDefault();
		if (refinement_data.query) {
			refinement_data.query['ajax'] = 1;
			ajax_data = refinement_data.query;
		}
		else {
			ajax_data = refinement_data.url + '?ajax=1';
			if (refinement_data.hash) ajax_data += '#'+refinement_data.hash;
		}
		if (search_control.ajax_call) {
			search_control.ajax_call.abort();
		}
		jQuery('.productListing .product-result').each(function() { search_control.manage_loadstatus(this, 'ready'); });
		// we just log the last request here, and then we'll track it first thing once the response comes back (or fails) in order to keep from double logging in event of an abort
		search_control.last_request = ajax_data;
		search_control.ajax_call = jQuery.ajax({
			url: refinement_data.url,
			type: 'GET',
			dataType: 'json',
			data: ajax_data,
			context: refinement_opt,
			timeout: 10000,
			beforeSend: search_control.start_loading,
			success: search_control.load_data,
			complete: search_control.reset_ajax,
			error: search_control.ajax_error
		});
	},

	track_event: function() {
		var category = '--NAV--'+search_control.context+'--'+search_control.page;

		var action, label;
		// the functions below don't strictly need to be a closures, other than that they use intermediate variables that I don't need to be in scope otherwise.
		// really just trying this structure out to see what I can do with it
		if (jQuery('.lbchecks').has(search_control.interacted_element).length) {
			// this is a search refinement
			action = '--'+jQuery('.lbsection-option').has(search_control.interacted_element).find('input[type=checkbox]').val().replace(/^(.+):.+$/, '$1')+'--select';
			label = function() {
				var selection = jQuery('.lbsection-option').has(search_control.interacted_element).find('input[type=checkbox]');
				var section = selection.closest('.lbchecks').attr('id');
				var val = selection.val().replace(/^.+:(.+)$/, '$1');
				var ctx = 'r0';
				// group position, list position, value count, group more link visible, group expanded, value expanded
				var gp, lp, vc, gm, ge, ve;

				gp = jQuery('.lbsection.'+section).index('.lbsection');
				gp++; // index returns a 0 based index, let's make it a 1 based index for reporting purposes

				lp = selection.index('.lbsection.'+section+' .lbsection-option:visible:not(.zero-quant) input[type=checkbox]');
				lp++; // same as gp, make this a 1 based index rather than a 0 based index

				// get the total number of values that could show in the current context (we'll see if they're all showing below with the expansion checking)
				vc = jQuery('.lbsection.'+section+' .lbsection-option:not(.zero-quant)').length;

				// get whether the view more link is even in scope for this attribute
				gm = jQuery('.lbsection.'+section+' .lbchecks .lbviewmore').is(':visible')?1:0;

				// get whether the view more link has been used to expand the attribute
				ge = jQuery('.lbsection.'+section+' .lbchecks .lbviewmore').text()=='View More'?0:1;

				// get whether this particular element was originally hidden or not
				ve = jQuery('#'+selection.attr('data-vid')).hasClass('hide-onmin')?1:0;

				return '--val['+val+']--ctx['+ctx+']--gp['+gp+']--lp['+lp+']--vc['+vc+']--gm['+gm+']--ge['+ge+']--ve['+ve+']';
			}();
		}
		else if (jQuery('.selection-additions').has(search_control.interacted_element).length) {
			// this is an additional search refinement
			action = '--'+jQuery('.addition-option').has(search_control.interacted_element).find('input[type=checkbox]').val().replace(/^(.+):.+$/, '$1')+'--add';
			label = function() {
				var selection = jQuery('.addition-option').has(search_control.interacted_element).find('input[type=checkbox]');
				var section = selection.closest('.selection-additions').attr('id');
				var val = selection.val().replace(/^.+:(.+)$/, '$1');
				var ctx = 'r1';
				// group position, list position, value count
				var gp, lp, vc;

				gp = jQuery('.selection-additions#'+section).index('.selection-additions');
				gp++; // index returns a 0 based index, let's make it a 1 based index for reporting purposes

				lp = selection.index('.selection-additions#'+section+' .addition-option:not(.zero-quant, .hide-selected) input[type=checkbox]');
				lp++; // same as gp, make this a 1 based index rather than a 0 based index

				// get the total number of values that could show in the current context (we'll see if they're all showing below with the expansion checking)
				vc = jQuery('.selection-additions#'+section+' .addition-option:not(.zero-quant, .hide-selected)').length;

				return '--val['+val+']--ctx['+ctx+']--gp['+gp+']--lp['+lp+']--vc['+vc+']';
			}();
		}
		else if (jQuery(search_control.interacted_element).is('a')) {
			// this is a link, let's figure out its purpose
			if (jQuery(search_control.interacted_element).hasClass('clear_refinements')) {
				// we're clearing all selected refinements
				action = '--all_attributes--remove';
				label = null;
			}
			else {
				var query = search_control.parse_url(search_control.query_link(jQuery(search_control.interacted_element))).query;
				for (var field in query) {
					if (!query.hasOwnProperty(field)) continue;

					if (/remove_refinement/.test(field)) {
						// we're removing a single refinement
						action = '--'+query[field].replace(/^(.+):.+$/, '$1')+'--remove';
						label = '--val['+query[field].replace(/^.+:(.+)$/, '$1')+']';
						break;
					}
					else if (field == 'page') {
						// we're moving to a new page
						action = '--paging--page';
						label = '--val['+query[field]+']';
						break;
					}
				}
			}
		}
		else if (jQuery(search_control.interacted_element).is('select')) {
			// this is a select box, let's figure out its purpose
			if (jQuery(search_control.interacted_element).attr('name') == 'results_per_page') {
				// we're changing the number of results we show per page
				action = '--paging--shownum';
				label = '--val['+jQuery(search_control.interacted_element).val()+']';
			}
			else if (jQuery(search_control.interacted_element).attr('name') == 'sort_by') {
				// we're changing the sort field
				action = '--paging--sortby';
				label = '--val['+jQuery(search_control.interacted_element).find('option:selected').text()+']';
			}
		}
		else if (jQuery(search_control.interacted_element).is('form')) {
			// all else fails, this is a submitted form, and we'll have to figure out what caused it to submit
			// most likely the interaction with a text field that required a manual submit

			var query = search_control.parse_url(jQuery(search_control.interacted_element).attr('action'), { query: search_control.query_form(jQuery(search_control.interacted_element)) }).query;

			if (jQuery(search_control.interacted_element).hasClass('pagejumper_form')) {
				// we put in a specific page to go to
				action = '--paging--page';
				label = '--val['+query['page']+']';
			}
			else if (jQuery(search_control.interacted_element).hasClass('pagerefine_form')) {
				// we updated price, now let's figure out if we're selecting, adding or removing
				action = '--Price--change';
				var pricelow = parseFloat(jQuery('.price.low').val());
				var pricehigh = parseFloat(jQuery('.price.high').val());
				if (pricelow > pricehigh) {
					var prc = pricelow;
					pricelow = pricehigh;
					pricehigh = prc;
				}
				label = '--low['+pricelow+']--high['+pricehigh+']';
			}
		}

		ga('send', 'event', category, action, label);
		//_gaq.push(['_trackEvent', category, action, label]);
	},

	manage_loadstatus: function(cell, setval) {
		if (setval) jQuery(cell).attr('data-loadstatus', setval);
		else if (jQuery(cell).attr('data-loadstatus') == undefined) jQuery(cell).attr('data-loadstatus', '');
		return jQuery(cell).attr('data-loadstatus');
	},

	start_loading: function() {
		jQuery('.productListing .product-result').each(jQuery).wait(50, function() {
			if (search_control.manage_loadstatus(this) == 'loaded') {
				return;
			}
			search_control.manage_loadstatus(this, 'loading');
			jQuery(this).children().fadeOut(400, function() {
				jQuery(this).show().css('visibility', 'hidden');
			});
		}).addClass('loading');

		product_count = jQuery('.productListing .product-result').length;
		i = 0;
		//for (var i=0; i<product_count/10; i++) {
			jQuery().wait(1200*i, function() {
				jQuery('.productListing .product-result').repeat(1200).each(jQuery).wait(1000/product_count, function() {
					jQuery('.loader:hidden').remove();
					if (!jQuery(this).hasClass('loading')) {
						jQuery(this).unrepeat();
						return;
					}
					// I'm using offsetHeight and offsetWidth here because they should always be correct. IE 9, at least when the page defaults to IE 8 Document Mode and then is set to IE 9 Document Mode, does not create the jQuery width() and height() values correctly.
					jQuery('<div/>', {'class': 'loader', 'style': "background-image:url('/images/logos/ckloading.gif');background-position:center;background-repeat:no-repeat;position:absolute;-moz-border-radius:10px;-webkit-border-radius:10px;-khtml-border-radius:10px;border-radius:10px;"}).css('top', jQuery(this).offset().top).css('left', jQuery(this).offset().left).css('height', jQuery(this).get(0).offsetHeight).css('width', jQuery(this).get(0).offsetWidth).appendTo('body').fadeOut(800);
				});
			});
		//}

		/*jQuery().wait(2500, function() {
			jQuery('.productListing .productItemListing').repeat().each(jQuery).wait(100, function() {
				jQuery('.loader:hidden').remove();
				if (!jQuery(this).hasClass('loading')) {
					jQuery(this).unrepeat();
					return;
				}
				jQuery('<div/>', {class: 'loader', style: "background-image:url('/images/logos/ckloading.gif');background-position:center;background-repeat:no-repeat;position:absolute;-moz-border-radius:10px;-webkit-border-radius:10px;-khtml-border-radius:10px;border-radius:10px;"}).css('top', jQuery(this).offset().top).css('left', jQuery(this).offset().left).css('height', jQuery(this).height()).css('width', jQuery(this).width()).appendTo('body').fadeOut(800);
			});
		});*/

		if (jQuery(window).scrollTop() > 350) {
			jQuery('html, body').animate({ scrollTop: 125 }, 1000);
		};

		// this last is for testing only
		/*jQuery().wait(5000, function() {
			//jQuery('.productListing .productItemListing').unrepeat();
			jQuery('.productListing .productItemListing').each(jQuery).wait(50, function(index) {
				jQuery(this).children().hide().css('visibility', 'visible').fadeIn();
			}).removeClass('loading');
		});*/
	},

	load_data: function(data, textStatus, jqXHR) {
		if (data == null) return;

		if (data.listing && data.listing.length) {
			jQuery('.productListing .product-result').each(function() { search_control.manage_loadstatus(this, 'loaded'); });
			existing_items = jQuery('.productListing .product-result').length;

			for (var i=0; i<data.listing.length; i++) {
				if (i < existing_items) {
					jQuery('.productListing .product-result:nth-child('+(i+1)+')').replaceWith(data.listing[i]);
          jQuery('.productListing .product-result:nth-child('+(i+1)+')').next('hr').remove();
				}
				else {
					jQuery(data.listing[i]).appendTo('div.productListingHolder');
				}
			}
			if (i < existing_items-1) {
				var k=i+1;
				for (var j=k; j<=existing_items; j++) {
					jQuery('.productListing .product-result:nth-child('+(k)+')').unrepeat().removeClass('loading').remove();
				}
			}
			jQuery('.productListing .product-result').each(jQuery).wait(50, function(index) {
				jQuery(this).children().hide().css('visibility', 'visible').fadeIn();
			}).removeClass('loading');
		}
		else {
			existing_rows = jQuery('.productListing .product-result').length;
			deal_row = 1;// this represents the index of the rows we actually want to deal with.
			for (var i=0; i<existing_rows; i++) {
				if (i == 0) {
					jQuery('.productListing .product-result:nth-child('+(deal_row)+')').unrepeat().removeClass('loading').html('<p>Whoops! We\'ve gone too far! No products could be found to match your search. Please back up and try again.</p>');
					deal_row++;
				}
				else if (i == 1) {
					deal_row++;
					// this is the separator row after the first item
				}
				else {
					jQuery('.productListing .product-result:nth-child('+(deal_row)+')').unrepeat().removeClass('loading').remove();
				}
			}
		}

		if (data.hash && !(data.hash == location.hash || '#'+data.hash == location.hash)) {
			hash = data.hash;
			delete data['hash']; // remove the hash from the cache to keep from triggering this if we load from cache
			search_control.last_data = data; // set the last_data property to keep the hashchange from triggering a cache lookup
			location.hash = hash; // change the hash to enable back button usage
			// update all of the hard links on the page to use the current refinement scope.
			jQuery('.lblabel a.selection-direct').each(function() {
				if (/-c-/.test(jQuery(this).attr('href'))) {
					jQuery(this).attr('href', jQuery(this).attr('href')+'#'+hash.replace(/cPath=[0-9]+/, "cPath="+jQuery(this).attr('href').match(/^.+-c-([0-9_]+)\.html.*/)[1]));
				}
				else {
					jQuery(this).attr('href', jQuery(this).attr('href')+'#'+hash);
				}
			});
		}

		if (data.pager) {
			jQuery('.pagejumpers .pjshowing').fadeOut(400, function() { jQuery(this).html(data.pager.showing).fadeIn(); });
			jQuery('.pagejumpers .pjpagination').fadeOut(400, function() { jQuery(this).html(data.pager.pager).fadeIn(); });
			if (data.pager.params) {
				for (var param in data.pager.params) {
					jQuery('.pagejumper_form input[type=hidden], .pagerefine_form input[type=hidden]').each(function() {
						if (jQuery(this).attr('name') == param) {
							jQuery(this).val(data.pager.params[param]);
						}
					});
				}
			}
			jQuery('.pagejumpers .pjgoto').val('').blur();
		}

		if (data.refinements) {
			// if this wasn't an update that used cached refinements, update the display
			//if (data.refinements.hold_refine_display == 0) {
				// these interactions are complex enough to require a separate dedicated object
				refinement_control.init();
				refinement_control.update(data.refinements);
				refinement_control.finish();
			//}
		}

		jQuery('.add-to-cart-form').off('submit');

		add_product_to_cart();
	},

	ajax_error: function(jqXHR, textStatus, errorThrown) {
		if (textStatus == 'abort') return; // if we've aborted it, that means we're passing another call and there's no need to run any error code

		if (!this.nodeName) {
			hashval = location.hash?/^#?(.*)$/.exec(location.hash)[1]:'';
			window.location = location.pathname+'?page_key='+hashval;
			return;
		}
		jQuery(this).addClass('go-anyway');
		if (this.nodeName.toLowerCase() == 'a') jQuery(this).click();
		else if (this.nodeName.toLowerCase() == 'form') jQuery(this).unbind('submit').submit();

		jQuery('.productListing .product-result').unrepeat();
		jQuery('.productListing .product-result').each(jQuery).wait(100, function() {
			jQuery('.loader:hidden').remove();
			//jQuery('<div/>', {'class': 'loader', 'style': 'background-color:#daa;position:absolute;-moz-border-radius:10px;-webkit-border-radius:10px;-khtml-border-radius:10px;border-radius:10px;'}).css('top', jQuery(this).offset().top).css('left', jQuery(this).offset().left).css('height', jQuery(this).height()).css('width', jQuery(this).width()).appendTo('body');
		});
	},

	reset_ajax: function(jqXHR, textStatus) {
		search_control.interacted_element = null;
	},

	handle_cache: function() {
		hashval = location.hash?/^#?(.*)$/.exec(location.hash)[1]:'';
		/*if (hashval != '') {
			pageTracker._trackPageview(location.pathname+'?'+hashval);
		}
		else {
			pageTracker._trackPageview(location.pathname+location.search);
		}*/
		if (!search_control.last_data) {
			if (search_control.cache[hashval]) {
				search_control.start_loading();
				jQuery().wait(500, function () { search_control.load_data(search_control.cache[hashval]); });
			}
			else {
				console.log(hashval);
				request = search_control.parse_url(window.location+'', {query: search_control.query_string(hashval)});
				search_control.refresh_results(this, request);
			}
		}
		else {
			var cache_length = 0;
			var initial = null;
			for (k in search_control.cache) {
				if (search_control.cache.hasOwnProperty(k)) cache_length++;
				if (k == '') initial = search_control.cache[k];
			}
			if (cache_length >= 15) {
				delete search_control['cache'];
				search_control.cache = {};
				search_control.cache[''] = initial; // handle the special case of no hash
			}
			search_control.last_data.refinements.hold_refine_display = 0;
			search_control.cache[hashval] = search_control.last_data;
			search_control.last_data = null; // unset the last_data property to allow cache lookups on next action
		}
	},

	parse_url: function(target, extra) {
		var urlval = '', querystring = '', hashval = '';
		var queryvals = {}, params = [], keyval = [];
		var vals = [], remainder = '';

		if (/\?/.test(target)) {
			vals = target.split('?');
			urlval = vals[0];
			remainder = vals[1];
		}
		else {
			remainder = target;
		}

		if (/#/.test(remainder)) {
			vals = remainder.split('#');
			if (urlval == '') urlval = vals[0];
			else querystring = vals[0];
			hashval = vals[1];
		}
		else {
			if (urlval == '') urlval = remainder;
			else querystring = remainder;
		}

		if (querystring != '' && /&(amp;)?/.test(querystring)) {
			params = querystring.split(/&(amp;)?/);
			for (var i=0; i<params.length; i++) {
				// since we're using parenthesis to test for optional html entity, it potentially captures that part of the entity with the rest of the split, or undefined if it's not there.
				if (params[i] == 'amp;' || params[i] == undefined || params[i] == '') continue;
				keyval = params[i].replace(/%26/g, '&').split('=');
				queryvals[keyval[0]] = keyval[1];
			}
		}
		else if (querystring != '') {
			keyval = querystring.replace(/%26/g, '&').split('=');
			queryvals[keyval[0]] = keyval[1];
		}

		if (extra) {
			if (extra.url && extra.url != '' && urlval != '') urlval += '/'+extra.url;
			else if (extra.url && extra.url != '') urlval = extra.url;

			if (extra.query && typeof extra.query == 'string' && extra.query != '') {
				if (/&(amp;)?/.test(extra.query)) {
					params = extra.query.split(/&(amp;)?/);
					for (var i=0; i<params.length; i++) {
						if (params[i] == 'amp;' || params[i] == undefined || params[i] == '') continue;
						keyval = params[i].replace(/%26/g, '&').split('=');
						queryvals[keyval[0]] = keyval[1];
					}
				}
				else { // we already tested to make sure extra.query wasn't an empty string
					keyval = extra.query.replace(/%26/g, '&').split('=');
					queryvals[keyval[0]] = keyval[1];
				}
			}
			else if (extra.query && typeof extra.query == 'object') {
				for (var key in extra.query) {
					queryvals[key] = extra.query[key];
				}
			}

			if (extra.hash && extra.hash != '' && hashval != '') hashval += '-'.extra.hash;
			else if (extra.hash && extra.hash != '') hashval = extra.hash;
		}

		return { req: target, url: urlval, query: queryvals, hash: hashval };
	},

	query_string: function(str) {
		return decodeURIComponent(str.replace(/%26/g, '%2526').replace(/\+/g, ' '));
	},

	query_link: function(linkJQ) {
		return decodeURIComponent(linkJQ.attr('href').replace(/%26/g, '%2526').replace(/\+/g, '%2B')); //.replace(/%5B/g, '[').replace(/%5D/g, ']').replace(/%3A/g, ':')
	},

	query_form: function(formJQ) {
		return decodeURIComponent(formJQ.serialize().replace(/%26/g, '%2526').replace(/\+/g, ' ')); //.replace(/%5B/g, '[').replace(/%5D/g, ']').replace(/%3A/g, ':')
	}
};

refinement_control = {
	unmanage: 'subcategory',
	current_qstring: '',
	selected: [],
	values_to_show: 5, // this is the number of values to show before the rest are hidden behind "View More".  Used to figure out which values to show, and when a "View More" link is necessary.
	options_control: {},

	init: function() {
		// perform any initial actions that don't depend on the returned data
		// currently, there are none
	},

	update: function(refinements) {
		// the querystring could be used in several places and contexts.  set it for the current instance
		refinement_control.current_qstring = refinements.querystring;

		// update the refinement html structure from top to bottom
		jQuery('.price.low').val(refinements.price_low);
		jQuery('.price.high').val(refinements.price_high);

		// reset the options hide control, to keep track of how many more from each group we have to process
		refinement_control.options_control = {'selected': {'element_count': 0, 'process_count': 0}, 'unselected': {'element_count': 0, 'process_count': 0}, 'lbsection': {'element_count': 0, 'process_count': 0}, 'count_control': {'element_count': 0, 'process_count': 0}};

		// we test, just to guard against javascript errors in case the structure returned by the ajax call changes without having updated this.  It ought to always be there.
		if (refinements.selections) {
			refinement_control.selected_refinements(refinements.selections);
		}

		// manage the unselected attribute block display
		if (refinements.attribute_order) {
			refinement_control.unselected_refinements(refinements.attribute_order);
		}

		// manage the control and display of options.  We have to pass it off to a function that we can attempt to run periodically because we might not be ready for it if previous parts of the code aren't yet finished processing
		refinement_control.manage_options(refinements);

		// this is superseded by the unselected attribute section above
		// minimize the entire section from which an attribute option was selected
		/*jQuery('.lbsection').each(function() {
			var found = false;
			var count_found = false;
			for (var i=0; i<attribute_controller.length; i++) {
				if (jQuery(this).attr('id') == 'a'+attribute_controller[i]+'-holder') {
					found = true;
					break;
				}
			}
			for (var count_idx in data.refinements.counts) {
				if (jQuery(this).attr('id') == 'a'+data.refinements.counts[count_idx]['aid']+'-holder') {
					count_found = true;
					break;
				}
			}
			if (found || !count_found) {
				animatedcollapse.hide(jQuery(this).attr('id'));
			}
			else if (count_found) {
				animatedcollapse.show(jQuery(this).attr('id'));
			}
		});*/

		// this is the old code for when the checkboxes were going to be left in place, so we needed to dynamically check and uncheck them based on the returned data
		/*jQuery('.lbchecks input[type=checkbox]').each(function() {
			// updating the check directly, rather than "click()"ing it, won't cause the form to resubmit
			jQuery(this).removeAttr('checked');
			if (refinements.selections) {
				for (var j=0; j<refinements.selections.length; j++) {
					if (jQuery(this).val() == refinements.selections[j]) {
						jQuery(this).attr('checked', 'checked');
					}
				}
			}
			Custom.clear();
		});*/
	},

	finish: function() {
		// perform any final actions that don't depend on the returned data

		// anything that was selected will be showing elsewhere now, and the original selection will be hidden.  These checkboxes will only show again when they should be unchecked.
		jQuery('.selection-additions, .lbsection').find('input[type=checkbox]').removeAttr('checked');
		Custom.clear();
	},

	attribute_control: function(attribute, action, context) {
		if (action == 'remove') {
			// when we remove an attribute selection block, first collapse it, then remove it
			animatedcollapse.hide(jQuery(attribute).attr('id'));
			jQuery().wait(400, function(obj) { jQuery(obj).remove(); }(attribute));
		}
		else if (action == 'hide') {
			animatedcollapse.hide(jQuery(attribute).attr('id'));
		}
		else if (action == 'hidenow') {
			jQuery(attribute).css('display', 'none');
		}
		else if (action == 'shownow') {
			jQuery(attribute).css('display', '');
		}
		else if (action == 'show') {
			animatedcollapse.show(jQuery(attribute).attr('id'));
		}
		else if (action == 'add') {
			// we need to create it and it's option sub blocks, and append it to the defined block
			jQuery('<div id="a'+attribute['aid']+'-selection" class="attribute-selection" style="display:none;"><div class="selection-title">'+attribute['attribute']+'<div class="lbl"><a class="clear-attribute refresh_navigation" href="">Clear</a></div></div><div class="selection-options"></div><div class="selection-additions" id="a'+attribute['aid']+'-additions" style="display:none;"></div><div class="selection-more"><a class="toggle_attribute a'+attribute['aid']+'-additions" href="#">[MORE]</a></div>').appendTo('.defined');
			animatedcollapse.addDiv('a'+attribute['aid']+'-selection', 'fade=0,speed=400');
			animatedcollapse.divholders['a'+attribute['aid']+'-selection'].$divref = jQuery('#a'+attribute['aid']+'-selection');
			animatedcollapse.addDiv('a'+attribute['aid']+'-additions', 'fade=0,speed=400');
			animatedcollapse.divholders['a'+attribute['aid']+'-additions'].$divref = jQuery('#a'+attribute['aid']+'-additions');
		}
	},

	value_control: function(value, action, context, aid) {
		if (action == 'remove') {
			// when we remove an attribute selection block, first collapse it, then remove it
			animatedcollapse.hide(jQuery(value).attr('id'));
			jQuery().wait(400, function(obj) { jQuery(obj).remove(); }(value));
		}
		else if (action == 'hide') {
			animatedcollapse.hide(jQuery(value).attr('id'));
		}
		else if (action == 'hidenow') {
			jQuery(value).css('display', 'none');
		}
		else if (action == 'shownow') {
			jQuery(value).css('display', '');
		}
		else if (action == 'show') {
			animatedcollapse.show(jQuery(value).attr('id'));
		}
		else if (action == 'add') {
			if (context == 'selected') {
				// we need to create it and append it to the current selections block for the referenced attribute
				jQuery('<div class="selection-option" id="v'+value['vid']+'-option"><a class="remove refresh_navigation" href="'+window.location.pathname+'?remove_refinement['+value['query'].replace(/&/g, '%26').replace(/\"/, '%22')+']='+value['query'].replace(/&/g, '%26').replace(/\"/, '%22')+'&amp;'+refinement_control.current_qstring+'">[x]</a>&nbsp;'+value['value']+'<input type="hidden" name="refinement_data['+value['query']+']" value="'+value['query']+'"/></div>').appendTo('.defined #a'+aid+'-selection .selection-options');

				animatedcollapse.addDiv('v'+value['vid']+'-option', 'fade=0,speed=400');
				animatedcollapse.divholders['v'+value['vid']+'-option'].$divref = jQuery('#v'+value['vid']+'-option');
			}
			else if (context == 'additional') {
				jQuery('<div class="'+value['count_key']+'-holder addition-option" style="clear:both;"><input type="checkbox" class="styled" name="refinement_data['+value['query']+']" data-vid="v'+value['vid']+'" value="'+value['query']+'"/> <span class="lblabel v'+value['vid']+'">'+value['value']+' <span class="count-control '+value['count_key']+'-count">()</span></span></div>').appendTo('.defined #a'+aid+'-selection .selection-additions');
			}
		}
	},

	selected_refinements: function(selections) {
		if (selections.length == 0) {
			// no refinements have been selected, so just remove everything in the defined box and return
			jQuery('.defined .attribute-selection').each(function() {
				refinement_control.attribute_control(this, 'remove');
			});
			return;
		}

		// handle removing previously selected attributes that no longer have any options selected for them
		jQuery('.defined .attribute-selection').each(function() {
			// for each attribute that has a section defined, see if the current data shows a selection for it.
			for (var aid in selections) {
				// if we find that this object shows a selection in the current data, just return, we're good
				if (jQuery(this).attr('id') == 'a'+aid+'-selection') return;
			}
			// if we don't find this attribute among the current ata selections, remove it.
			refinement_control.attribute_control(this, 'remove');
		});

		for (var aid in selections) {
			if (!selections.hasOwnProperty(aid)) continue; // if this is an inherited property rather than a set property, skip it
			// update the race condition controller
			refinement_control.options_control.selected.element_count++;
		}

		for (var aid in selections) {
			if (!selections.hasOwnProperty(aid)) continue; // if this is an inherited property rather than a set property, skip it

			// we can probably change this section to add the attribute block if it's needed, add the additions to that block, then perform as if it's always been there.
			// for now we have a completely separate block for whether the attribute exists or not

			if (jQuery('.defined #a'+aid+'-selection').length) {
				// we've already made one selection in this attribute, add or remove options

				// first remove previously selected options that are no longer selected
				jQuery('.defined #a'+aid+'-selection .selection-option').each(function() {
					for (var i=0; i<selections[aid]['options'].length; i++) {
						// if we find this existing option under the current selections, we're good
						if (jQuery(this).attr('id') == 'v'+selections[aid]['options'][i]['vid']+'-option') return;
					}
					// if we don't find this existing option among the current selections, remove it
					jQuery('.a'+aid+'-'+jQuery(this).attr('id').replace('-option', '-holder')).removeClass('hide-selected');
					refinement_control.value_control(this, 'remove');
				});

				// then add newly selected options that were not previously selected
				var removeattr = [];
				for (var i=0; i<selections[aid]['options'].length; i++) {
					removeattr.push('remove_refinement['+selections[aid]['options'][i]['query'].replace(/&/g, '%26').replace(/\"/, '%22')+']='+selections[aid]['options'][i]['query'].replace(/&/g, '%26').replace(/\"/, '%22'));
					// if it's already there, skip it, we don't need to do anything
					if (jQuery('.defined #a'+aid+'-selection #v'+selections[aid]['options'][i]['vid']+'-option').length) continue;

					// add the selected attribute
					refinement_control.value_control(selections[aid]['options'][i], 'add', 'selected', aid);
				}
				// update the href for the clear link so that it will remove all options for this attribute
				jQuery('.defined #a'+aid+'-selection a.clear-attribute').attr('href', window.location.pathname+'?'+removeattr.join('&amp;')+'&amp;'+refinement_control.current_qstring);

				// hide the additional options block and reset the more-link
				animatedcollapse.hide('a'+aid+'-additions');
				if (jQuery('a.a'+aid+'-additions').html() == '[LESS]') {
					jQuery('a.a'+aid+'-additions').html('[MORE]');
				}

				// manage the displayed additional options, after the additional options block has finished hiding
				jQuery().wait(400, function(aid) {
					// loop through the total options list that originally loaded with the page
					for (var count_idx in search_control.cache['']['refinements']['counts']) {
						if (!search_control.cache['']['refinements']['counts'].hasOwnProperty(count_idx)) continue; // if this is an inherited property rather than a set property, skip it
						if (search_control.cache['']['refinements']['counts'][count_idx]['aid'] != aid) continue; // if this is an option for a different attribute, skip it

						// loop through the currently selected options
						for (var j=0; j<selections[aid]['options'].length; j++) {
							if (search_control.cache['']['refinements']['counts'][count_idx]['vid'] == selections[aid]['options'][j]['vid']) {
								// we found this option among the currently selected options, set the hide flag
								jQuery('.'+count_idx+'-holder').addClass('hide-selected');
								//refinement_control.options_control.selected.process_count++;
								//return; // since we're not actually making any functional changes anymore unless we actually find something here, we've no need to return to block further changes
							}
						}
						// we didn't find this option among the currently selected options, make sure it's shown when the additional options list is shown
						//refinement_control.value_control('.'+count_idx+'-holder', 'shownow', 'selected');
						// this is now handled below with options control
					}
					refinement_control.options_control.selected.process_count++;
				}(aid));
			}
			else {
				// this is the first time an option has been selected for this attribute
				// add it to the defined block
				refinement_control.attribute_control(selections[aid], 'add');

				// add newly selected options
				var removeattr = [];
				for (var i=0; i<selections[aid]['options'].length; i++) {
					removeattr.push('remove_refinement['+selections[aid]['options'][i]['query'].replace(/&/g, '%26').replace(/\"/, '%22')+']='+selections[aid]['options'][i]['query'].replace(/&/g, '%26').replace(/\"/, '%22'));

					// add the selected attribute
					refinement_control.value_control(selections[aid]['options'][i], 'add', 'selected', aid);
				}
				// update the href for the clear link so that it will remove all options for this attribute
				jQuery('.defined #a'+aid+'-selection a.clear-attribute').attr('href', window.location.pathname+'?'+removeattr.join('&amp;')+'&amp;'+refinement_control.current_qstring);

				// initialize the additional options block with all of the values that loaded with the page
				// loop through the total options list that originally loaded with the page
				for (var count_idx in search_control.cache['']['refinements']['counts']) {
					if (!search_control.cache['']['refinements']['counts'].hasOwnProperty(count_idx)) continue; // if this is an inherited property rather than a set property, skip it
					if (search_control.cache['']['refinements']['counts'][count_idx]['aid'] != aid) continue; // if this is an option for a different attribute, skip it

					// add the option
					refinement_control.value_control(search_control.cache['']['refinements']['counts'][count_idx], 'add', 'additional', aid);

					// if we find the option in the current selections, set a hide flag
					for (var j=0; j<selections[aid]['options'].length; j++) {
						if (search_control.cache['']['refinements']['counts'][count_idx]['vid'] == selections[aid]['options'][j]['vid']) {
							jQuery('.'+count_idx+'-holder').addClass('hide-selected');
							break;
						}
					}
				}

				// we've initialized the additional options, now we need to initialize the checkbox stylings
				jQuery('.defined #a'+aid+'-selection .selection-additions input[type=checkbox]').each(function() {
					Custom.init_field(this);
				});

				// the new attribute block has been completely created.  Show it.
				refinement_control.attribute_control('#a'+aid+'-selection', 'show');

				refinement_control.options_control.selected.process_count++;
			}
		}
	},

	unselected_refinements: function(attributes) {
		// check each originally loaded attribute to see about order and display status
		jQuery('.lbsection').each(function() {
			// start by hiding it, regardless
			refinement_control.attribute_control(this, 'hide');
			// go ahead and set all of them to third class, we'll just reset to the appropriate class if necessary below
			jQuery(this).removeClass('first-class second-class').addClass('third-class');
		});
		jQuery().wait(400, function() {
			// once all attributes are completely hidden, adjust the relevant attributes that have been passed through
			for (var i=0; i<attributes.length; i++) {
				//if (jQuery(obj).attr('id') == 'a'+attributes[i]['aid']+'-holder') {
				// put it in the correct order
				//selector_idx = i+1;
				var blockid = '#a'+attributes[i]['aid']+'-holder';
				var valblockid = '#a'+attributes[i]['aid'];
				if (jQuery(blockid).index('.lbsection') != i) jQuery(blockid).insertBefore(jQuery('.lbsection:eq('+i+')'));

				// moving the boxes around breaks the references in the animatedcollapse object.  Rebuild them
				for (divid in animatedcollapse.divholders) {
					if (!animatedcollapse.divholders.hasOwnProperty(divid)) continue; // if it's inherited, skip it
					if (animatedcollapse.divholders[divid].$divref) continue; // if the divref is maintained (I belive the top level block that is actually moved is left alone, but anything contained within that block is broken), we don't need to rebuild it
					animatedcollapse.divholders[divid].$divref = jQuery('#'+divid); // otherwise, re-grab the div reference
				}

				// then change to the appropriate class
				jQuery(blockid).removeClass('third-class').addClass(attributes[i]['display_class']+'-class');
				// control the display according to the class
				if (attributes[i]['display_class'] == 'first') {
					jQuery(valblockid).show();
					refinement_control.attribute_control(blockid, 'show');
				}
				else if (attributes[i]['display_class'] == 'second') {
					jQuery(valblockid).hide();
					refinement_control.attribute_control(blockid, 'show');
				}
				else {
					// the initial block above set the appropriate settings for this block
				}
			}
		});
		// there's nothing going on here that will affect selection hiding, so we'll just leave that alone for now.  We can add those counters later if necessary
	},

	manage_options: function(refinements) {
		// manage the race condition
		if (refinement_control.options_control.selected.element_count != refinement_control.options_control.selected.process_count || refinement_control.options_control.unselected.element_count != refinement_control.options_control.unselected.process_count) {
			// if we're not yet complete with processing selected and/or unselected options, then wait and try again
			setTimeout(function(){refinement_control.manage_options(refinements)}, 200);
			return;
		}

		refinement_control.options_control.lbsection.element_count = jQuery('.lbsection-option').length;
		refinement_control.options_control.count_control.element_count = jQuery('.count-control').length;

		// manage display flags and counts for all attribute options
		// for attribute values in the original attribute blocks, update the hide-onmin flag for whether it's shown by default or not
		jQuery('.lbsection-option').each(function() {
			// look through the show_options list and see if we find this option
			for (var aid in refinements.show_options) {
				if (!jQuery(this).closest('.lbsection').hasClass('a'+aid)) continue; // if this element of the show_options list doesn't belong to the current attribute group, skip it

				for (var i=0; i<refinements.show_options[aid].length; i++) {
					// if we find this value among the values to show, remove the hide-onmin flag (if it doesn't have it, then no effect), then return out of the function
					if (jQuery(this).attr('id') == 'v'+refinements.show_options[aid][i]) {
						jQuery(this).removeClass('hide-onmin');
						refinement_control.option_display('lbsection'); // control the display countdown and, when ready, manage the display
						return;
					}
				}
			}
			// if we got here, we didn't find the value to show, so add the hide-onmin flag (if it already has it, then no effect)
			if (!RegExp('^v'+refinement_control.unmanage+'-').test(jQuery(this).attr('id')) || jQuery(this).closest('.ctx-search').length || /outlet\.php/.test(window.location)) jQuery(this).addClass('hide-onmin');
			refinement_control.option_display('lbsection'); // control the display countdown and, when ready, manage the display
		});
		// for all attribute values that can be selected, either from the original attribute blocks or in the additional selected attribute blocks, update the quantity display, and
		// if it's zero quantity, add the zero-quant flag for whether it should be hidden or not
		jQuery('.count-control').each(function() {
			var count = 0;
			var new_display = null;
			for (var count_idx in refinements.counts) {
				// loop through the current count list to locate the current count display
				if (jQuery(this).hasClass(count_idx+'-count')) {
					count = refinements.counts[count_idx]['count'];
					if (refinements.counts[count_idx]['new_display']) {
						// we've actually got a new name for this particular value.
						jQuery('.defined .lblabel.v'+refinements.counts[count_idx]['vid']).fadeOut(400, function() { jQuery(this).html(refinements.counts[count_idx]['new_display']+' <span class="count-control '+count_idx+'-count">('+count+')</span>').fadeIn(); });
					}
					else {
						// no new name, just update the count
						jQuery(this).fadeOut(400, function() { jQuery(this).html('('+count+')').fadeIn(); });
					}

					// set or remove the zero-quant flag appropriately
					if (count == 0) {
						jQuery(this).closest('.addition-option, .lbsection-option').addClass('zero-quant');
					}
					else {
						jQuery(this).closest('.addition-option, .lbsection-option').removeClass('zero-quant');
					}

					refinement_control.option_display('count_control'); // control the display countdown and, when ready, manage the display

					// we've found it, we've processed it, we're done, return
					return;
				}
			}
			// we never found it, which means it should be set to zero
			jQuery(this).fadeOut(400, function() { jQuery(this).html('(0)').fadeIn(); });
			jQuery(this).closest('.addition-option, .lbsection-option').addClass('zero-quant');
			refinement_control.option_display('count_control'); // control the display countdown and, when ready, manage the display
		});
	},

	option_display: function(context) {
		refinement_control.options_control[context].process_count++;
		// if we're not yet all done with all groups that can control the options display, return
		if (refinement_control.options_control.lbsection.element_count != refinement_control.options_control.lbsection.process_count || refinement_control.options_control.count_control.element_count != refinement_control.options_control.count_control.process_count) {
			return;
		}
		// otherwise, control the option display

		// show all original options that don't have any hide flags set
		jQuery('.lbsection-option').not('.hide-onmin, .zero-quant').each(function() { refinement_control.value_control(this, 'show'); });
		// hide all original options that do have hide flags set
		jQuery('.lbsection-option.hide-onmin, .lbsection-option.zero-quant').each(function() { refinement_control.value_control(this, 'hide'); });
		// show all additional options that don't have any hide flags set
		jQuery('.addition-option').not('.hide-onmin, .zero-quant, .hide-selected').each(function() { refinement_control.value_control(this, 'shownow', 'option_display'); });
		// hide all additional options that do have hide flags set
		jQuery('.addition-option.hide-onmin, .addition-option.zero-quant, .addition-option.hide-selected').each(function() { refinement_control.value_control(this, 'hidenow'); });
		// control the view more link
		jQuery('.ctx-search a.togglerefinements, .ctx-browse a.togglerefinements:not(.a'+refinement_control.unmanage+')').html('View More');
		if (/outlet\.php/.test(window.location)) jQuery('.ctx-browse a.togglerefinements.a'+refinement_control.unmanage).html('View More');
		jQuery('.lbsection').each(function() {
			// if there are some that are hidden currently, but will be shown with a link, show the link
			if (jQuery(this).find('.lbsection-option.hide-onmin').not('.zero-quant').length) {
				jQuery(this).find('.lbviewmore').show();
			}
			// otherwise, hide the link
			else {
				jQuery(this).find('.lbviewmore').hide();
			}
		});
		jQuery('.attribute-selection').each(function() {
			if (jQuery(this).find('.addition-option').not('.zero-quant, .hide-selected').length) {
				jQuery(this).find('.selection-more').show();
			}
			else {
				jQuery(this).find('.selection-more').hide();
			}
		});
	}
};

jQuery(document).ready(function () {
	jQuery('a.refresh_navigation').live('click', function(event) {
		// using the click() event doesn't actually cause the browser to go to the link, so we have to do that manually
		if (jQuery(this).hasClass('go-anyway')) window.location = jQuery(this).attr('href');
		search_control.interacted_element = this;
		search_control.track_event();
		request = search_control.parse_url(search_control.query_link(jQuery(this)));
		search_control.refresh_results(this, request, event);
		return false;
	});

	jQuery('form.refresh_navigation').submit(function(event) {
		if (jQuery(this).hasClass('go-anyway')) return true;
		if (!search_control.interacted_element) search_control.interacted_element = this;
		search_control.track_event();
		request = search_control.parse_url(jQuery(this).attr('action'), { query: search_control.query_form(jQuery(this)) });
		search_control.refresh_results(this, request, event);
		return false;
	});

	jQuery('form.refresh_navigation select').live('change', function(event) {
		search_control.interacted_element = this;
		jQuery(this).closest('form').submit();
	});

	jQuery('.lbchecks span.checkbox, .lbchecks span.lblabel, .selection-additions span.checkbox, .selection-additions span.lblabel').live('click', function(event) {
		search_control.interacted_element = this;
		var frm = jQuery(this).closest('form');
		//frm.find('input.send_refinements').remove();
		//frm.find('input[type=checkbox]').each(function() {
		//	if (jQuery(this).is(':checked')) {
		//		jQuery('<input type="hidden" name="'+jQuery(this).attr('name')+'" value="'+jQuery(this).attr('value')+'" class="send_refinements"/>').appendTo(frm);
		//	}
		//});
		frm.submit();
	});

	jQuery(window).bind('hashchange', function (e) {
		search_control.handle_cache();
	});

	hashval = location.hash?/^#?(.*)$/.exec(location.hash)[1]:'';
	if (hashval != '') {
		// we only trigger the hashchange if there's a hash to worry about
		jQuery(window).trigger('hashchange');
	}
	else {
		jQuery('.productListing .product-result').each(jQuery).wait(50, function(index) {
			jQuery(this).children(':not(:visible)').hide().css('visibility', 'visible').fadeIn();
		});
	}
});
