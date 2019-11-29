(function($) {
	function perform_search(term, context, control_codes) {
	}

	$.fn.greedy_search = function(settings) {
		var opts = $.extend({}, $.fn.greedy_search.defaults, settings);

		if (!opts.$search_field) {
			opts.$search_field = $('<input type="text" id="greedy-search" name="greedy-search" style="visibility:hidden;">');
			$('body').append(opts.$search_field);
		}

		$('input, select, textarea, button, a, [contenteditable]')
			.bind('keydown', function(e) {
				e.stopPropagation();
			})
			.bind('keyup', function(e) {
				e.stopPropagation();
			});

		var ignore = false;

		this
			.bind('keydown', function(e) {
				var target = e.target || e.srcElement;
				if (target && target.tagName && (target.tagName.toLowerCase() == 'input' || target.tagName.toLowerCase() == 'select' || target.tagName.toLowerCase() == 'textarea' || target.tagName.toLowerCase() == 'button' || target.tagName.toLowerCase() == 'a' || jQuery(target).attr('contenteditable'))) return;
				if (opts.$greedy_flag && !opts.$greedy_flag.is(':checked')) return;
				var key = e.keyCode || e.which;
				// I believe the "ignore" handling is redundant, but I'll leave this here until I understand it better
				if (key === 0 || key === 13 || e.ctrlKey || e.metaKey || e.altKey) ignore = true;
				if (ignore > 0) return;
				opts.$search_field.focus();
			})
			.bind('keyup', function(e) {
				var key = e.keyCode || e.which;
				if (key === 0 || key === 13 || e.ctrlKey || e.metaKey || e.altKey) ignore = false;
			});
			/*.on('keyup', function(e) {
				var key = e.keyCode || e.which;
				if (key == 13) {
					var action = get_action(opts.$search_field.val(), opts.control_codes);
					switch (action) {
						case 'submit':
							break;
						case 'set_context':
							opts.context = opts.$search_field.val();
							opts.$search_field.val('');
							break;
					}
				}
			});*/
	};

	$.fn.greedy_search.defaults = {
		$greedy_flag: null,
		$context_field: null,
		$search_field: null,
		context: null,
		control_codes: {}
	};
}(jQuery));