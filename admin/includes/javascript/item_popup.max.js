jQuery('head').append('<link rel="stylesheet" type="text/css" href="css/item_popup.css">');

item_popup = {
	timer: 500, // milliseconds

	/*count_up: function(item) {
		var cu = setInterval(function() {
			if (!jQuery(item).hasClass('process')) {
				window.clearInterval(cu);
				return;
			}
			curr = jQuery(item).attr('data-popctr');
			curr++;
			if (curr >= 100) window.clearInterval(cu);
			if (curr > 100) return;
			jQuery(item).attr('data-popctr', curr);

			jQuery(item).find('.status').css('width', curr+'%').css('border-bottom-color', 'rgb('+(255-Math.ceil(2.55*curr))+', '+Math.ceil(2.55*curr)+', 0)');

			if (curr == 100) {
				item_popup.pop(item);
			}
		}, item_popup.count_up_time/100);
	},*/

	count_down: function(item) {
		if (jQuery(item).attr('data-inctx') == 1) return;
		jQuery(item).attr('data-inctx', 1);
		var cd = setInterval(function() {
			if (jQuery(item).attr('data-allhalt') == 1) return;
			var lock = jQuery(item).attr('data-lockctr');
			if (lock > 0) var counter = 1;
			else var counter = -1;
			var curr = parseInt(jQuery(item).attr('data-popctr'));
			curr += counter;

			if (curr <= 0 || curr >= 100) {
				jQuery(item).attr('data-inctx', 0);
				window.clearInterval(cd);
			}
			if (curr < 0 || curr > 100) return;

			jQuery(item).attr('data-popctr', curr);

			jQuery(item).find('.status').css('width', curr+'%').css('border-bottom-color', 'rgb('+(255-Math.ceil(2.55*curr))+', '+Math.ceil(2.55*curr)+', 0)');

			if (curr >= 100) {
				var ref = '.'+jQuery(item).attr('id');
				jQuery(ref).show().css('top', jQuery(item).position().top + jQuery(item).height()).css('left', jQuery(item).position().left);
			}
			else if (curr <= 1) {
				jQuery(item).removeClass('pop');
				var ref = '.'+jQuery(item).attr('id');
				jQuery(ref).hide().css('top', '').css('left', '').removeClass('locked');
			}
		}, item_popup.timer/100);
	}

	// we're just popping and hiding in place, but a function would give us a little more control over display
	/*pop: function(boxref) {
		if (jQuery('.'+boxref).is(':visible')) {
			jQuery('.'+boxref).hide().css('top', '').css('left', '').removeClass('locked');
			return;
		}
		var target_top = jQuery('#'+boxref).position().top + jQuery('#'+boxref).height();
		var target_left = jQuery('#'+boxref).position().left;

		// for now we're not doing any extra processing, but we could put the box in a different spot relative to the original text depending on where it shows up on the page

		jQuery('.'+boxref).show().css('top', target_top).css('left', target_left);
	}*/
};

jQuery(document).ready(function() {
	jQuery('.item_popup').live('mouseover', function(event) {
		jQuery(this).attr('data-allhalt', 0);
		jQuery(this).addClass('pop');
		var lock = jQuery(this).attr('data-lockctr');
		lock++;
		jQuery(this).attr('data-lockctr', Math.max(lock, 0));
		item_popup.count_down(this);
	}).live('click', function(event) {
		if (jQuery(this).attr('data-allhalt') == 1) return;
		jQuery(this).attr('data-popctr', 100);
		jQuery(this).find('.status').css('width', '100%').css('border-bottom-color', 'rgb(0, 255, 0)');
		var ref = '.'+jQuery(this).closest('.item_popup').attr('id');
		jQuery(ref).show().css('top', jQuery(this).position().top + jQuery(this).height()).css('left', jQuery(this).position().left);
		event.preventDefault();
		event.stopPropagation();
	});

	jQuery('.item_popup').live('mouseout', function(event) {
		var lock = jQuery(this).attr('data-lockctr');
		lock--;
		jQuery(this).attr('data-lockctr', Math.max(lock, 0));
		item_popup.count_down(this);
	});

	jQuery('.item_popup_details').live('mouseover', function(event) {
		var classes = jQuery(this).attr('class').split(/\s+/);
		var parent_id;
		for (var i=0; i<classes.length; i++) {
			if (classes[i] == 'item_popup_details') continue;
			if (/item_popup_\d+/.test(classes[i])) parent_id = classes[i];
		}
		var lock = jQuery('#'+parent_id).attr('data-lockctr');
		lock++;
		jQuery('#'+parent_id).attr('data-lockctr', Math.max(lock, 0));
		item_popup.count_down(jQuery('#'+parent_id).get(0));
	});

	jQuery('.item_popup_details').live('mouseout', function(event) {
		var classes = jQuery(this).attr('class').split(/\s+/);
		var parent_id;
		for (var i=0; i<classes.length; i++) {
			if (classes[i] == 'item_popup_details') continue;
			if (/item_popup_\d+/.test(classes[i])) parent_id = classes[i];
		}
		var lock = jQuery('#'+parent_id).attr('data-lockctr');
		lock--;
		jQuery('#'+parent_id).attr('data-lockctr', Math.max(lock, 0));
		item_popup.count_down(jQuery('#'+parent_id).get(0));
	});

	jQuery('.item_popup .ctrl .lock').live('click', function(event) {
		jQuery(this).attr('class', 'locked');
		jQuery(this).closest('.item_popup').addClass('locked');

		var ref = '.'+jQuery(this).closest('.item_popup').attr('id');
		jQuery(ref).addClass('locked');

		var lock = jQuery(this).closest('.item_popup').attr('data-lockctr');
		lock++;
		jQuery(this).closest('.item_popup').attr('data-lockctr', Math.max(lock, 0));
	});

	jQuery('.item_popup .ctrl .locked').live('click', function(event) {
		jQuery(this).attr('class', 'lock');
		jQuery(this).closest('.item_popup').removeClass('locked');

		var ref = '.'+jQuery(this).closest('.item_popup').attr('id');
		jQuery(ref).removeClass('locked');

		var lock = jQuery(this).closest('.item_popup').attr('data-lockctr');
		lock--;
		jQuery(this).closest('.item_popup').attr('data-lockctr', Math.max(lock, 0));
	});

	jQuery('.item_popup .ctrl .close').live('click', function(event) {
		event.preventDefault();
		event.stopPropagation();
		jQuery(this).closest('.item_popup').attr('data-allhalt', 1);
		jQuery(this).closest('.item_popup').removeClass('pop').removeClass('locked').attr('data-lockctr', 0).attr('data-popctr', 0);
		jQuery(this).siblings('.locked').attr('class', 'lock');
		jQuery(this).closest('.item_popup').find('.status').css('width', '0%').css('border-bottom-color', 'rgb(255, 0, 0)');
		var ref = '.'+jQuery(this).closest('.item_popup').attr('id');
		jQuery(ref).hide().css('top', '').css('left', '').removeClass('locked');
	});

	jQuery('.item_popup_imgs .carousel img:not(.in-context)').live('click', function(event) {
		jQuery(this).closest('.item_popup_imgs').find('.carousel .in-context').removeClass('in-context');
		jQuery(this).addClass('in-context');
		jQuery(this).closest('.item_popup_imgs').find('.context_image .in-context').removeClass('in-context');
		jQuery(this).closest('.item_popup_imgs').find('.context_image .'+jQuery(this).attr('data-target')).addClass('in-context');
	});
});