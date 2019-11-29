/*
To Do:
1) dynamic coloring options -
 a) alternate with more than one row, e.g. 2x2, 3x3
 b) alternate on arbitrary data groupings, e.g. 4 in a row, 2 in a row, 16 in a row
 c) more than 2 colors, e.g. 1-2-3-1-2-3
*/

ck = ck||{};

ck.table_manager = function(id, opts) {
	var self = this;

	this.id = id[0]=='#'?id:'#'+id;
	this.opts = opts||{};
	this.$table = null;
	this.$reload_ajax = null;
	this.$reload_opts = null;
	this.dataset = [];

	jQuery('document').ready(function() {
		self.$table = jQuery(self.id)
	});

	this.defaults = {
		reload_error: function(jqXHR, textStatus, errorThrown) {
			console.log('There was an error reloading table data: ['+textStatus+': '+errorThrown+']');
		},
		reload_success: function(data, textStatus, jqXHR) {
			self.reload(data);
		}
	};

	this.init();
};

ck.table_manager.prototype.init = function() {
	for (var key in this.defaults) {
		if (!this.defaults.hasOwnProperty(key)) continue;
		if (this.opts.hasOwnProperty(key)) continue;
		this.opts[key] = this.defaults[key];
	}

	this.$reload_opts = {
		url: this.opts.reload_uri,
		data: this.opts.reload_params,
		type: 'GET',
		method: 'GET',
		dataType: 'json',
		error: this.opts.reload_error,
		success: this.opts.reload_success
	};
};

ck.table_manager.prototype.reload = function(data) {
	if (data == undefined || data.table == undefined) {
		if (data.reload_url != undefined) this.$reload_opts.url = data.reload_url;
		if (this.$reload_opts.url == undefined) {
			console.log('You must specify a URI to reload data from.');
			return;
		}

		if (data.reload_params != undefined) this.$reload_opts.data = data.reload_params;

		if (data.reload_beforeSend != undefined) this.$reload_opts.beforeSend = data.reload_beforeSend;
		if (data.reload_complete != undefined) this.$reload_opts.complete = data.reload.complete;
		if (data.reload_error != undefined) this.$reload_opts.error = data.reload_error;
		if (data.reload_success != undefined) this.$reload_opts.success = data.reload_success;

		this.$reload_ajax = jQuery.ajax(this.$reload_opts);

		return;
	}


}

ck.table_manager.prototype.renumber = function() {
	var counter = 0;
	jQuery('.ck.table-manager .data-row').each(function() {
		jQuery(this).attr('data-row-counter', counter);
		jQuery(this).removeClass('row-0').removeClass('row-1');
		jQuery(this).addClass('row-'+(counter%2));
		counter++;
	});
};