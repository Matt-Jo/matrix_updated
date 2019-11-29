var ck = ck || {};

var global_autocomplete_init = false;

/** @constructor */
ck.autocomplete = function(search_box_id, url, opts) {
	if (opts == undefined) opts = url, url = search_box_id;
	else if (opts.$fields == undefined) opts.$fields = [jQuery('#'+search_box_id)];
	else opts.$fields.push(jQuery('#'+search_box_id));

	this.$autocomplete_fields = [];
	this.url = url;

	this.defaults = {
		minimum_length: 3,
		halt_request: function() { return false; },
		select_result: function() { return; },
		results_template: '{{#results}}<a href="#" class="entry" id="{{result_id}}">{{{result_label}}}</a>{{/results}}',
		ajax_obj: {
			type: 'get',
			dataType: 'json',
			data: { ajax: 1 },
			success: this.show_results.bind(this),
			error: this.result_error.bind(this)
		},
		auto_select_single: true,
		force_results_return: false,
		display_handlers: {
			add_field: null,
			clear_results: null,
			show_results: null,
		},
	};
	this.opts = opts || {};

	this.input_timeout;

	this.init();
};

ck.autocomplete.styleset = null;

ck.autocomplete.styles = function(styles) {
	for (var selector in styles) {
		if (!styles.hasOwnProperty(selector)) continue;

		// this also sets the selector context, so any subsequent actions will add to this selector
		ck.autocomplete.styleset.add_selector(selector);

		if (Array.isArray(styles[selector])) {
			for (var i=0; i<styles[selector].length; i++) {
				ck.autocomplete.styleset.add_stylestring(styles[selector][i]);
			}
		}
		else if (typeof styles[selector] == 'string') {
			ck.autocomplete.styleset.add_stylestring(styles[selector]);
		}
		else {
			for (var style in styles[selector]) {
				if (!styles[selector].hasOwnProperty(style)) continue;
				ck.autocomplete.styleset.add_style(style, styles[selector][style]);
			}
		}
	}
	ck.autocomplete.styleset.render();
};

ck.autocomplete.prototype.init = function() {
	var self = this;

	for (var key in this.defaults) {
		if (!this.defaults.hasOwnProperty(key)) continue;
		if (this.opts[key] == undefined) this.opts[key] = this.defaults[key];
	}

	Mustache.parse(this.opts.results_template);

	if (!global_autocomplete_init) {
		ck.autocomplete.styleset = new ck.styleset('autocomplete');
		ck.autocomplete.styleset.add_selector('.autocomplete-group').add_style('display', 'inline-block');
		ck.autocomplete.styleset.add_selector('.autocomplete-results').add_stylestring('display:none; background-color:#fff; border:1px solid #5cc; position:absolute; max-height:700px; overflow-y:auto; overflow-x:hidden; z-index:999;');
		ck.autocomplete.styleset.add_selector('.autocomplete-results .entry').add_stylestring('margin:0px; padding:4px 6px; font-size:.5vw; white-space:nowrap; border-bottom:1px solid #999; display:block; z-index:999;');
		ck.autocomplete.styleset.add_selector('.autocomplete-results .entry:hover').add_stylestring('border-radius:3px; border-bottom-color:transparent; background:linear-gradient(#6ff, #7cf);');
		ck.autocomplete.styleset.render();

		jQuery('body').click(this.clear_results.bind(this, true));

		global_autocomplete_init = true;
	}

	if (this.opts.$fields) {
		for (var i=0; i<this.opts.$fields.length; i++) {
			this.add_field(this.opts.$fields[i]);
		}
	}
};

ck.autocomplete.prototype.add_field = function($fields) {
	if (this.opts.display_handlers.add_field) this.opts.display_handlers.add_field.call(this, $fields);
	else {
		var self = this;

		$fields.each(function() {
			jQuery(this).addClass('autocomplete-field');

			this.$autocomplete_group = jQuery('<div></div>').addClass('autocomplete-group');

			this.$autocomplete_results = jQuery('<div></div>').addClass('autocomplete-results');
			if (self.opts.autocomplete_results_class != undefined) {
				this.$autocomplete_results.addClass(self.opts.autocomplete_results_class);
			}

			this.$autocomplete_value = null;
			if (self.opts.hidden_value_field != undefined) {
				this.$autocomplete_value = jQuery('#'+self.opts.hidden_value_field).addClass('autocomplete-value');
			}
			else if (jQuery(this).attr('data-autocomplete-value-field')) {
				this.$autocomplete_value = jQuery('#'+jQuery(this).attr('data-autocomplete-value-field')).addClass('autocomplete-value');
			}

			this.$autocomplete_group.insertAfter(jQuery(this));
			jQuery(this).appendTo(this.$autocomplete_group);
			this.$autocomplete_group.append(this.$autocomplete_results);
			if (this.$autocomplete_value) this.$autocomplete_group.append(this.$autocomplete_value);

			jQuery(this).attr('autocomplete', 'off').keyup(function() {
				var element = this;
				if (self.input_timeout) clearTimeout(self.input_timeout);
				self.input_timeout = setTimeout(function() {
					self.request.call(self, element);
				}, 150);
			});
			if (self.opts.request_onclick) {
				jQuery(this).click(function(e) {
					e.stopPropagation();
					if (jQuery(this).val() != '') return;
					self.request.call(self, this);
				});
			}

			self.$autocomplete_fields.push(jQuery(this));
		});
	}
};

ck.autocomplete.prototype.clear_results = function(global) {
	if (this.opts.display_handlers.clear_results) this.opts.display_handlers.clear_results.call(this, global);
	else jQuery('.autocomplete-results').hide().html('');
};

ck.autocomplete.prototype.halt_request = function(value) {
	if (this.opts.halt_request(value)) return true;

	if (value.length < this.opts.minimum_length) return true;
	else return false;
};

ck.autocomplete.prototype.request = function(element) {
	if (this.opts.local_results) {
		this.local_request(element);
		return;
	}

	this.clear_results(false);

	this.autocomplete_request_element = element;

	var value = jQuery(element).val();
	var name = this.opts.autocomplete_field_name || jQuery(element).attr('name');
	if (this.halt_request(value)) return;

	if (this.opts.preprocess) this.opts.preprocess(value);

	var ajax_obj = jQuery.extend({}, this.opts.ajax_obj); // create a clone rather than a reference
	ajax_obj.url = this.url;
	ajax_obj.data.action = this.opts.autocomplete_action || 'autocomplete';
	ajax_obj.data[name] = value;

	if (this.opts.process_additional_fields) {
		ajax_obj.data = this.opts.process_additional_fields.call(element, ajax_obj.data);
	}

	if (this.opts.force_results_return) {
		if (this.$autocomplete_request == undefined) this.$autocomplete_request = {};
		if (this.$autocomplete_request[value] != undefined) return;
	}
	else if (this.$autocomplete_request) this.$autocomplete_request.abort();
	this.$autocomplete_request = jQuery.ajax(ajax_obj);
};

ck.autocomplete.prototype.local_request = function(element) {
	this.clear_results(false);

	this.autocomplete_request_element = element;

	var value = jQuery(element).val();
	var name = this.opts.autocomplete_field_name || jQuery(element).attr('name');
	if (this.halt_request(value)) return;

	if (this.opts.preprocess) this.opts.preprocess(value);

	var data = {};
	data.action = this.opts.autocomplete_action || 'autocomplete';
	data[name] = value;

	if (this.opts.process_additional_fields) {
		data = this.opts.process_additional_fields(data);
	}

	var results = this.opts.local_results(data);

	this.show_results.call(this, results);
};

ck.autocomplete.prototype.show_results = function(data, textStatus, jqXHR) {
	if (this.opts.display_handlers.show_results) this.opts.display_handlers.show_results.call(this, data, textStatus, jqXHR);
	else {
		if (data == null) return;
		this.clear_results(false);

		if (data.results.length == 0) return;
		else if (this.opts.auto_select_single && data.results.length == 1) return this.select_result(data.results[0]);

		this.autocomplete_request_element.$autocomplete_results.append(jQuery(Mustache.render(this.opts.results_template, data)));

		var self = this;

		setTimeout(function() {
			for (var i=0; i<data.results.length; i++) {
				jQuery('#'+data.results[i].result_id).click(self.select_result.bind(self, data.results[i]));
			}

			self.autocomplete_request_element.$autocomplete_results.show();
		}, 20);
	}
};

ck.autocomplete.prototype.select_result = function(result, e) {
	if (e) e.preventDefault();
	jQuery(this.autocomplete_request_element).val(result.field_value);
	if (this.autocomplete_request_element.$autocomplete_value) this.autocomplete_request_element.$autocomplete_value.val(result.value);

	this.opts.select_result.call(this, result);

	this.clear_results(false);
};

ck.autocomplete.prototype.result_error = function() {
};