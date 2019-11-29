var ck = ck || {};

ck.styleset = function(name, opts) {
	this.name = name;
	this.id = 'ck-'+name+'-styles';

	this.opts = opts || {};

	this.media = { none: {} };

	this.$styleset = jQuery('<style id="'+this.id+'"></style>');
	this.$styleset.appendTo('head');

	this.media_context = null;
	this.selector_context = null;

	this.auto_render = false;

	this.render();
};

ck.styleset.prototype.auto_render = function(status) {
	if (status) this.auto_render = true;
	else this.auto_render = false;

	return this;
};

ck.styleset.prototype.option = function(attr, val) {
	if (val == undefined) return this.opts[attr];
	else this.opts[attr] = val;

	if (this.auto_render) this.render();

	return this;
};

ck.styleset.prototype.add_media = function(media_query, no_context) {
	if (this.media[media_query] == undefined) this.media[media_query] = {};
	if (!no_context) this.set_media_context(media_query);
	if (this.auto_render) this.render();
	return this;
};

ck.styleset.prototype.remove_media = function(media_query) {
	delete this.media[media_query];
	this.clear_media_context();
	if (this.auto_render) this.render();
	return this;
};

ck.styleset.prototype.set_media_context = function(media_query) {
	this.media_context = media_query;
	return this;
};

ck.styleset.prototype.get_media_context = function() {
	return this.in_media_context()?this.media_context:'none';
};

ck.styleset.prototype.in_media_context = function() {
	return this.media_context != null;
};

ck.styleset.prototype.clear_media_context = function() {
	this.media_context = null;
	return this;
};

ck.styleset.prototype.add_selector = function(media_query, selector, no_context) {
	if (selector == undefined || typeof selector == 'boolean') no_context = selector, selector = media_query, media_query = this.get_media_context();
	if (this.media[media_query] == undefined) this.add_media(media_query, no_context);

	if (this.media[media_query][selector] == undefined) this.media[media_query][selector] = {};
	if (!no_context) this.set_context(selector);
	if (this.auto_render) this.render();
	return this;
};

ck.styleset.prototype.remove_selector = function(media_query, selector) {
	if (selector == undefined) selector = media_query, media_query = this.get_media_context();
	delete this.media[media_query][selector];
	this.clear_context();
	if (this.auto_render) this.render();
	return this;
};

ck.styleset.prototype.set_context = function(selector) {
	this.selector_context = selector;
	return this;
};

ck.styleset.prototype.in_context = function() {
	return this.selector_context != null;
};

ck.styleset.prototype.clear_context = function() {
	this.selector_context = null;
	return this;
};

ck.styleset.prototype.add_stylestring = function(selector, styles, media_query) {
	if (media_query == undefined) media_query = this.get_media_context();

	if (styles == undefined && this.in_context()) styles = selector, selector = this.selector_context;
	else this.add_selector(media_query, selector, true);
	

	styles = styles.split(/\s*;\s*/);

	for (var i=0; i<styles.length; i++) {
		style = styles[i].split(/\s*:\s*/, 2);

		if (style.length == 1) this.remove_style(selector, style[0], media_query);
		else this.add_style(selector, style[0], style[1], media_query);
	}

	if (this.auto_render) this.render();

	return this;
};

ck.styleset.prototype.add_style = function(selector, style, value, media_query) {
	if (media_query == undefined) media_query = this.get_media_context();

	if (value == undefined && this.in_context()) value = style, style = selector, selector = this.selector_context;
	else this.add_selector(media_query, selector, true); // won't do anything if it already exists

	this.media[media_query][selector][style] = value;

	if (this.auto_render) this.render();

	return this;
};

ck.styleset.prototype.remove_style = function(selector, style, media_query) {
	if (media_query == undefined) media_query = this.get_media_context();

	if (this.media[media_query][selector] != undefined) delete this.media[media_query][selector][style];

	if (this.auto_render) this.render();

	return this;
};

ck.styleset.prototype.render = function() {
	for (var i=0; i<this.$styleset[0].attributes.length; i++) {
		if (this.$styleset[0].attributes[i].nodeName == 'id') continue;
		if (this.opts[this.$styleset[0].attributes[i].nodeName] == undefined) this.$styleset.removeAttr(this.$styleset[0].attributes[i].nodeName);
	}
	for (var attr in this.opts) {
		this.$styleset.attr(attr, this.opts[attr]);
	}

	var sections = [];

	for (var media_query in this.media) {
		if (!this.media.hasOwnProperty(media_query)) continue;

		var selectors = [];

		for (var selector in this.media[media_query]) {
			if (!this.media[media_query].hasOwnProperty(selector)) continue;

			var styles = [];

			for (var style in this.media[media_query][selector]) {
				if (!this.media[media_query][selector].hasOwnProperty(style)) continue;

				styles.push(style+': '+this.media[media_query][selector][style]);
			}

			selectors.push(selector+' { '+styles.join('; ')+' }');
		}

		if (media_query == 'none') sections.push(selectors.join("\n"));
		else sections.push(media_query+" {\n\t"+selectors.join("\n\t")+"\n}");
	}

	this.$styleset.html(sections.join("\n\n"));

	this.clear_context();
	this.clear_media_context();

	return this;
};
