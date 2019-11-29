var ck = ck || {};

ck.global_tabs_init = false;

ck.tabs = function(opts) {
	if (typeof opts == 'string') opts = { tabs_id: opts };

	if (opts.tabs_id == undefined || opts.tabs_id == '') throw new Error('Tabs missing identifier for element');

	this.defaults = {
		default_tab_index: 0,
		content_suffix: '-content'
	};
	this.opts = opts || {};

	this.tabs_id = this.opts.tabs_id;
	this.tab_bodies_id = this.opts.tab_bodies_id || this.opts.tabs_id+'-body';

	this.init();
};

ck.tabs.styleset = null;

ck.tabs.preinit = function() {
	if (!ck.global_tabs_init) {
		ck.tabs.styleset = new ck.styleset('tabs');
		ck.tabs.styleset.add_selector('.ck-tabs').add_stylestring('margin:0px; padding:0px;');
		ck.tabs.styleset.add_selector('.ck-tabs li').add_stylestring('display:inline-block; margin-right:8px; vertical-align:bottom; cursor:pointer; list-style-type:none; padding:3px; border:1px solid #000; background-color:#fff; color:#15489e; font-size:10px;');
		ck.tabs.styleset.add_selector('.ck-tabs li:hover').add_stylestring('background-color:#f1f1f1; text-decoration:underline; color:#000;');
		ck.tabs.styleset.add_selector('.ck-tabs li.active').add_stylestring('background-color:#f1f1f1; border-bottom-color:#f1f1f1;');
		ck.tabs.styleset.add_selector('.ck-tab-body').add_stylestring('padding:10px; margin-top:-1px; background-color:#f1f1f1; border:1px solid #000;');
		ck.tabs.styleset.add_selector('.ck-tab-content').add_stylestring('display:none;');
		ck.tabs.styleset.add_selector('.ck-tab-content.active').add_stylestring('display:block');
		ck.tabs.styleset.render();

		ck.global_tabs_init = true;
	}
};

ck.tabs.styles = function(styles) {
	for (var selector in styles) {
		if (!styles.hasOwnProperty(selector)) continue;

		// this also sets the selector context, so any subsequent actions will add to this selector
		ck.tabs.styleset.add_selector(selector);

		if (Array.isArray(styles[selector])) {
			for (var i=0; i<styles[selector].length; i++) {
				ck.tabs.styleset.add_stylestring(styles[selector][i]);
			}
		}
		else if (typeof styles[selector] == 'string') {
			ck.tabs.styleset.add_stylestring(styles[selector]);
		}
		else {
			for (var style in styles[selector]) {
				if (!styles[selector].hasOwnProperty(style)) continue;
				ck.tabs.styleset.add_style(style, styles[selector][style]);
			}
		}
	}
	this.styleset.render();
};

ck.tabs.prototype.init = function() {
	var self = this;

	for (var key in this.defaults) {
		if (!this.defaults.hasOwnProperty(key)) continue;
		if (this.opts[key] == undefined) this.opts[key] = this.defaults[key];
	}

	ck.tabs.preinit();

	jQuery('#'+this.tabs_id).addClass('ck-tabs');
	this.$tabs = jQuery('#'+this.tabs_id+'>li');
	jQuery('#'+this.tab_bodies_id).addClass('ck-tab-body');
	this.$tab_contents = jQuery('#'+this.tab_bodies_id+'>div').addClass('ck-tab-content');

	var indexes = [];

	this.tabs = [];

	this.$tabs.each(function(index) {
		self.tabs[index] = jQuery(this);

		if (jQuery(this)[0].hasAttribute('id')) return;

		indexes.push(index);
		jQuery(this).attr('id', 'ck-tab-'+index);
	});

	this.$tab_contents.each(function(index) {
		if (jQuery(this)[0].hasAttribute('id')) return;

		if (indexes.length > 0) jQuery(this).attr('id', 'ck-tab-'+indexes.shift()+self.opts.content_suffix);
	});

	this.$tabs.click(function() {
		self.$tabs.filter('.active').removeClass('active').trigger('tabs:close');
		self.$tab_contents.filter('.active').removeClass('active').trigger('tabs:close');

		jQuery(this).addClass('active').trigger('tabs:open');
		jQuery('#'+jQuery(this).attr('id')+self.opts.content_suffix).addClass('active').trigger('tabs:open');
	});

	if (Number.isInteger(this.opts.default_tab_index)) this.$tabs[this.opts.default_tab_index].click();
	else jQuery('#'+this.opts.default_tab_index).click();
};

ck.tabs.prototype.add_tab = function() {};
ck.tabs.prototype.remove_tab = function() {};