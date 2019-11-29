var ck = ck || {};

ck.global_button_links_init = false;

ck.button_links = function() {
	if (!ck.global_button_links_init) {
		ck.button_links.styleset = new ck.styleset('button-links');
		ck.button_links.styleset.add_selector('.button-link').add_stylestring('display:inline-block; padding:1px 7px; border:1px solid #9c9c9c; border-radius:1.5px; background:linear-gradient(#f4f4f4, #dedede); text-align:center; white-space:pre;');
		ck.button_links.styleset.add_selector('.button-link:visited').add_stylestring('color:#000; font-size:11px;');
		ck.button_links.styleset.add_selector('.button-link:link').add_stylestring('color:#000; font-size:11px;');
		ck.button_links.styleset.add_selector('.button-link:hover').add_stylestring('border-color:#6c6c6c; text-decoration:none;');
		ck.button_links.styleset.render();

		ck.global_button_links_init = true;
	}

	jQuery('.button-link.new-tab').attr('target', '_blank');
	ck.ajaxify.link(jQuery('.button-link.ajax:not([data-button-link=processed])'), ck.button_links.ajax_link_success);

	jQuery('.button-link').attr('data-button-link', 'processed');
};

ck.button_links.styleset = null;

ck.button_links.styles = function(styles) {
	for (var selector in styles) {
		if (!styles.hasOwnProperty(selector)) continue;

		// this also sets the selector context, so any subsequent actions will add to this selector
		ck.button_links.styleset.add_selector(selector);

		if (Array.isArray(styles[selector])) {
			for (var i=0; i<styles[selector].length; i++) {
				ck.button_links.styleset.add_stylestring(styles[selector][i]);
			}
		}
		else if (typeof styles[selector] == 'string') {
			ck.button_links.styleset.add_stylestring(styles[selector]);
		}
		else {
			for (var style in styles[selector]) {
				if (!styles[selector].hasOwnProperty(style)) continue;
				ck.button_links.styleset.add_style(style, styles[selector][style]);
			}
		}
	}
	this.styleset.render();
};

ck.button_links.ajax_link_success = function(data) {
};