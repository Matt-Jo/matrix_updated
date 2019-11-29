var ck = ck || {};

ck.global_modal_init = false;
//ck.$modal_container = null;

ck.modal = function(opts) {
	if (typeof opts == 'string') opts = { modal_id: opts };

	if (opts.modal_id == undefined || opts.modal_id == '') throw new Error('Modal missing identifier for element');

	this.open = false;

	this.defaults = {
		close_image: '/images/static/img/ck-modal-close.png',
		fade: true,
		on_open: null,
		after_open: null,
		on_close: null,
		after_close: null,
		shadowbox: false,
		css: {},
		//resizable: true,
		//draggable: true
	};
	this.opts = opts || {};

	this.consume_opt_defaults();

	this.modal_id = this.opts.modal_id;

	this.init();
};

ck.modal.styleset = null;
ck.modal.open_context = false;

ck.modal.styles = function(styles) {
	for (var selector in styles) {
		if (!styles.hasOwnProperty(selector)) continue;

		// this also sets the selector context, so any subsequent actions will add to this selector
		ck.modal.styleset.add_selector(selector);

		if (Array.isArray(styles[selector])) {
			for (var i=0; i<styles[selector].length; i++) {
				ck.modal.styleset.add_stylestring(styles[selector][i]);
			}
		}
		else if (typeof styles[selector] == 'string') {
			ck.modal.styleset.add_stylestring(styles[selector]);
		}
		else {
			for (var style in styles[selector]) {
				if (!styles[selector].hasOwnProperty(style)) continue;
				ck.modal.styleset.add_style(style, styles[selector][style]);
			}
		}
	}
	this.styleset.render();
};

ck.modal.prototype.consume_opt_defaults = function() {
	for (var key in this.defaults) {
		if (!this.defaults.hasOwnProperty(key)) continue;
		if (this.opts[key] == undefined) this.opts[key] = this.defaults[key];
	}
}

ck.modal.prototype.init = function() {
	var self = this;

	if (!ck.global_modal_init) {
		ck.modal.styleset = new ck.styleset('ck-modal');
		//ck.modal.styleset.add_selector('#ck-modal-container').add_stylestring('position:fixed; top:0px; bottom:0px; left:0px; right:0px; display');
		//ck.modal.styleset.add
		ck.modal.styleset.add_selector('.ck-modal-shadowbox').add_stylestring('background-color:rgba(0, 0, 0, 0.5); position:fixed; margin:0px; top:0px; right:0px; bottom:0px; left:0px; display:none; z-index:100;');
		ck.modal.styleset.add_selector('.ck-modal-shadowbox.faded').add_stylestring('background-color:transparent;');
		ck.modal.styleset.add_selector('.ck-modal-link');
		ck.modal.styleset.add_selector('.ck-modal').add_stylestring('display:none; margin:auto; top:0px; right:0px; bottom:0px; left:0px; border:1px solid #000; background-color:#fff; overflow:auto; width:50%; height:50%; max-height:80%; z-index:999; font-size:12px;');
		ck.modal.styleset.add_selector('.ck-modal.faded').add_stylestring('opacity:.2;');
		ck.modal.styleset.add_selector('.ck-modal:not([draggable])').add_stylestring('position:fixed');
		ck.modal.styleset.add_selector('.ck-modal .modal-header').add_stylestring('margin:0px; padding:5px 5px 5px 10px; border-bottom:1px solid #000; font-weight:bold; background:linear-gradient(#fff, #ddd);');
		ck.modal.styleset.add_selector('.ck-modal .modal-close.icon').add_stylestring('float:right; cursor:pointer;');
		ck.modal.styleset.add_selector('.ck-modal .modal-fade.icon').add_stylestring('float:right; cursor:pointer; border:1px solid #c1c1c1; display:block; width:23px; height:13px; background:linear-gradient(#ddd, #fff); margin:1px 3px 0px 0px; border-radius:1px;');
		ck.modal.styleset.add_selector('.ck-modal .modal-body').add_stylestring('margin:0px; padding:10px 5px 5px 10px; overflow:auto;');/*display:inline-block;*/
		ck.modal.styleset.add_selector('.ck-modal.right').add_stylestring('left:auto; text-align:right;'); // must assign right distance
		ck.modal.styleset.add_selector('.ck-modal.left').add_stylestring('right:auto; text-align:left;'); // must assign left distance
		ck.modal.styleset.add_selector('.ck-modal.top').add_stylestring('bottom:auto;'); // must assign top distance
		//ck.modal.styleset.add_selector('.ck-modal.resizable').add_stylestring('resize:both;');
		ck.modal.styleset.add_selector('.ck-modal[draggable] .modal-header').add_stylestring('cursor:move;');
		ck.modal.styleset.render();

		ck.modal.$shadowbox = jQuery('<div class="ck-modal-shadowbox"></div>');

		jQuery('body').append(ck.modal.$shadowbox);

		//ck.$modal_container = jQuery('<div id="ck-modal-container"><div class="modal-shadowbox"></div></div>');
		//jQuery('body').append(ck.$modal_container);

		jQuery('body').click(this.close_all_modals.bind(this));

		ck.global_modal_init = true;
	}

	this.$modal_content = jQuery('#'+this.modal_id).length!=0?jQuery('#'+this.modal_id):jQuery('<div id="'+this.modal_id+'"></div>');
	this.$modal_content.addClass('ck-modal');

	if (this.opts.sticky) this.$modal_content.addClass('sticky');

	this.$modal_content.detach();

	if (this.opts.width) this.$modal_content.css('width', this.opts.width);
	if (this.opts.height) this.$modal_content.css('height', this.opts.height);
	if (this.opts.right) this.$modal_content.addClass('right').css('right', this.opts.right);
	if (this.opts.left) this.$modal_content.addClass('left').css('left', this.opts.left);
	if (this.opts.top) this.$modal_content.addClass('top').css('top', this.opts.top);
	if (this.opts.bottom) this.$modal_content.addClass('bottom').css('bottom', this.opts.bottom);
	//if (this.opts.resizable) this.$modal_content.resizable();
	/*if (this.opts.draggable) {
		this.$modal_content.attr('draggable', true).draggable({
			stop: function(event, ui) {
				var eTop = ui.helper.offset().top;
				var wTop = $(window).scrollTop();
				var top = eTop - wTop;
				ui.helper.css('position', 'fixed');
				ui.helper.css('top', top+'px');
			}
		});
	}*/

	for (const key in this.opts.css) {
		if (!this.opts.css.hasOwnProperty(key)) continue;
		this.$modal_content.css(key, this.opts.css[key]);
	}

	var $main = null;
	if (this.$modal_content.find('main').length != 0) $main = this.$modal_content.find('main');
	if (!$main) {
		$main = jQuery('<main></main>');
		$main.html(this.$modal_content.html());
		this.$modal_content.html('');
		this.$modal_content.append($main);
	}
	if (this.opts.content) $main.html(this.opts.content);
	$main.addClass('modal-body');

	var $header = null;
	if (this.$modal_content.find('header').length != 0) $header = this.$modal_content.find('header');
	if (this.opts.header) {
		if (!$header) {
			$header = jQuery('<header></header>');
			this.$modal_content.prepend($header);
		}
		$header.html(this.opts.header);
	}
	if ($header) $header.addClass('modal-header');

	var $close_icon = null;
	if (this.$modal_content.find('.modal-close.icon').length != 0) $close_icon = this.$modal_content.find('.modal-close.icon');
	if (!$close_icon) {
		$close_icon = jQuery('<a href="#" class="modal-close icon"><img src="'+this.opts.close_image+'"></a>');
		if ($header) $header.append($close_icon);
		else $main.prepend($close_icon);
	}

	if (this.opts.fade) {
		var $fade_icon = null;
		if (this.$modal_content.find('.modal-fade.icon').length != 0) $fade_icon = this.$modal_content.find('.modal-fade.icon');
		if (!$fade_icon) {
			$fade_icon = jQuery('<a href="#" class="modal-fade icon"></a>');
			if ($header) $header.append($fade_icon);
			else $main.prepend($fade_icon);
		}
	}

	if (this.opts.shadowbox) ck.modal.$shadowbox.append(this.$modal_content);
	else jQuery('body').append(this.$modal_content);

	//ck.$modal_container.append(this.$modal_content);

	// the close icon may not be the only way to close
	this.$modal_content.click(function(e) {
		if (e) e.stopPropagation();
	});
	this.$modal_content.find('.modal-close').click(this.close_modal.bind(this));
	this.$modal_content.find('.modal-fade').hover(function() {
		self.$modal_content.addClass('faded');
		ck.modal.$shadowbox.addClass('faded');
	},
	function() {
		self.$modal_content.removeClass('faded');
		ck.modal.$shadowbox.removeClass('faded');
	});
};

ck.modal.prototype.close_all_modals = function(e) {
	//if (e) e.preventDefault();
	if (!ck.modal.open_context) {
		jQuery('.ck-modal:not(.sticky)').hide().trigger('modal:close');
		ck.modal.$shadowbox.hide();
	}
	ck.modal.open_context = false;
};

ck.modal.prototype.open_modal = function(e) {
	if (e) e.preventDefault();
	ck.modal.open_context = true;
	this.$modal_content.trigger('modal:open');
	if (this.opts.on_open) this.opts.on_open.call(this);
	if (this.opts.shadowbox) ck.modal.$shadowbox.show();
	this.$modal_content.show();
	this.open = true;
	if (this.opts.after_open) this.opts.after_open.call(this);
};

ck.modal.prototype.close_modal = function(e) {
	if (e) e.preventDefault();
	if (this.opts.on_close) this.opts.on_close.call(this);
	ck.modal.$shadowbox.hide();
	this.$modal_content.hide().trigger('modal:close');
	this.open = false;
	if (this.opts.after_close) this.opts.after_close.call(this);
};

ck.modal.prototype.add_link = function($link) {
	var self = this;
	$link.on('click', function(e) {
		e.preventDefault();
		jQuery(this).trigger('modal:open');
		self.open_modal();
	});
};

ck.modal.prototype.on_open = function(fn) {
	this.opts.on_open = fn;
}

ck.modal.prototype.after_open = function(fn) {
	this.opts.after_open = fn;
}

ck.modal.prototype.on_close = function(fn) {
	this.opts.on_close = fn;
}

ck.modal.prototype.after_close = function(fn) {
	this.opts.after_close = fn;
}

ck.modal.prototype.is_open = function() {
	return this.open;
}

ck.modal.prototype.is_closed = function() {
	return !this.open;
}