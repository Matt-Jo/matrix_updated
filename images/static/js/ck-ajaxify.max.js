var ck = ck || {};

ck.ajaxify = {};

ck.ajaxify.form = function($form, success, error, beforeSend, wrap) {
	var fn = function(e) {
		e.preventDefault();

		$form.addClass('ajax-sending');

		var opts = {};
		opts.url = jQuery(this).attr('action');
		opts.type = jQuery(this).attr('method');
		opts.dataType = jQuery(this).attr('data-type')?jQuery(this).attr('data-type'):'json';
		opts.data = jQuery(this).serialize();

		opts.data += '&ajax=1';

		if (jQuery(this).find('.clicked').length != 0 && jQuery(this).find('.clicked').attr('name')) opts.data += '&'+jQuery(this).find('.clicked').attr('name')+'='+jQuery(this).find('.clicked').val();
		jQuery(this).find('.clicked').removeClass('clicked');

		if (success) opts.success = success;
		if (error) opts.error = error;
		if (beforeSend) opts.beforeSend = beforeSend;

		opts.complete = function() {
			$form.removeClass('ajax-sending');
		};

		jQuery.ajax(opts);
	};

	$form.on('submit', function(e) {
		if (wrap) return wrap.call(this, e, fn);
		else return fn.call(this, e);
	});

	$form.find('input[type=submit]').click(function() {
		$form.find('.clicked').removeClass('clicked');
		jQuery(this).addClass('clicked');
	});
};

ck.ajaxify.link = function($link, success, error, beforeSend, wrap) {
	var fn = function(e) {
		e.preventDefault();

		var opts = {};
		opts.url = jQuery(this).attr('href');
		opts.type = jQuery(this).attr('data-method')?jQuery(this).attr('data-method'):'get';
		opts.dataType = jQuery(this).attr('data-type')?jQuery(this).attr('data-type'):'json';
		opts.data = 'ajax=1';

		if (success) opts.success = success;
		if (error) opts.error = error;
		if (beforeSend) opts.beforeSend = beforeSend;

		jQuery.ajax(opts);
	};

	$link.click(function(e) {
		if (wrap) return wrap.call(this, e, fn);
		else return fn.call(this, e);
	});
};