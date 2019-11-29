// initialize our ck namespace, if it hasn't been already
// we can do more than this, make it into a full framework a la jquery, see final 9/1/13 comment @ http://www.phpied.com/3-ways-to-define-a-javascript-class/
// I'll have to learn more about that, this is the extent to which I understand immediately
var ck = ck || {};
// properties prefixed with $ are meant to hold jquery objects

/**
	Creates a new rotator.
	@constructor
*/
ck.rotator = function() {
	// initialize the properties
	this.$target = null;
	this.target_width = null;
	this.static_time = null;
	this.transition_time = null;
	this.direction = null;
	this.control = { $next: null, $prev: null, $pause: null, $play: null, $pauseplay: null };

	this.defaults = {
		static_time: 6000,
		transition_time: 600,
		direction: 'right2left'
	};

	this.image_list = [];
	this.image_idx = 0;

	this.target_width = null;

	this.timer = null;
	this.action_time = null;
	this.timer_start = null;
	this.timer_left = null;
	this.timer_active = false;

	// this only takes one argument, a named list of settings
	if (arguments.length > 0) this.init(arguments[0]);
};

ck.rotator.prototype.init = function(settings) {
	var rotator = this; // alias this, so we can refer to it simply within jquery

	var start = true; // unless we tell it not to, get the thing going at the end of this

	// accept arguments to set parameters
	if (settings.target) this.$target = settings.target;
	if (settings.static_time) this.static_time = settings.static_time;
	if (settings.transition_time) this.transition_time = settings.transition_time;
	if (settings.direction) this.direction = settings.direction;
	if (settings.next) this.control.$next = $('#'+settings.next);
	if (settings.prev) this.control.$prev = $('#'+settings.prev);
	if (settings.pause) this.control.$pause = $('#'+settings.pause);
	if (settings.play) this.control.$play = $('#'+settings.play);
	if (settings.pauseplay) this.control.$pauseplay = $('#'+settings.pauseplay);
	if (settings.start) start = settings.start;

	// if we haven't set the timing or direction, use defaults
	if (this.static_time == null) this.static_time = this.defaults.static_time;
	if (this.transition_time == null) this.transition_time = this.defaults.transition_time;
	if (this.direction == null) this.direction = this.defaults.direction;

	// if control buttons have been indicated, set them up
	if (this.control.$next != null) this.control.$next.click(function (event) {
		event.preventDefault();
		rotator.next(true);
		return false;
	});
	if (this.control.$prev != null) this.control.$prev.click(function (event) {
		event.preventDefault();
		rotator.prev(true);
		return false;
	});
	if (this.control.$pause != null) this.control.$pause.click(function (event) {
		event.preventDefault();
		rotator.pause();
		return false;
	});
	if (this.control.$play != null) this.control.$play.click(function (event) {
		event.preventDefault();
		rotator.play();
		return false;
	});
	if (this.control.$pauseplay != null) this.control.$pauseplay.click(function (event) {
		event.preventDefault();
		rotator.pauseplay();
		return false;
	});

	if (this.$target == null) return;

	// we should move this out to a constructor option, allowing us to set this with an initialization variable
	this.$target.hover(function() {
		rotator.pauseplay();
	});

	// record the initial width, we'll need to know it later cuz it's gonna change
	this.target_width = this.$target.closest('.rotator-viewer').width();

	// get existing images in the target into our queue
	this.$target.find('a').each(function() {
		var obj = {};
		obj.target = $(this).attr('href');
		obj.source = $(this).find('img').attr('src');
		obj.$element = $(this);

		rotator.add_img(obj);
	});

	if (start) this.start();
};

// this could be modified to re-arrange the order of the image list, if we think we might want that, but for now we'll leave it as is.
// we might also consider removing images
ck.rotator.prototype.add_img = function(img) {
	if (typeof img.$element !== 'undefined' && img.$element instanceof $) {
		// this is an existing image element on the page
		// this should only be pre-set by init
		
		// if this is the first found image, we're good
		// if this is a subsequent image, we'll want to make sure it's hidden
		if (this.image_list.length >= 1) img.$element.css('visibility', 'hidden'); //hide();
	}
	else img.$element = null;

	for (var i=0; i<this.image_list.length; i++) {
		// if we find the same image in the list, we don't want to add it again.
		if (img.source == this.image_list[i].source) return;
		// we don't need to do any other handling
	}

	this.image_list.push(img);

	// if this is the first image in the list and it's either not loaded or not visible, load it and show it
	if (this.image_list.length == 1) {
		if (!this.image_list[0].$element || !this.image_list[0].$element.is(':visible')) this.load_img(0, true);
	}
};

ck.rotator.prototype.load_img = function(idx, show) {
	show = typeof show!=='undefined'?show:false;

	// if this one is already loaded, just return
	if (typeof this.image_list[idx].$element !== 'undefined' && this.image_list[idx].$element instanceof $) {
		if (show) this.image_list[idx].$element.show().css('visibility', 'visible');
		return;
	}

	if (!this.image_list[idx].target) this.image_list[idx].target = '#';

	this.image_list[idx].$element = $('<a></a>').attr('href', this.image_list[idx].target);
	if (this.image_list[idx].newpage == 1) this.image_list[idx].$element.attr('target', '_blank');
	var $img = $('<img>').attr('src', this.image_list[idx].source).attr('alt', this.image_list[idx].alt).attr('title', this.image_list[idx].title);
	// if this is not the first image, expand the box to accommodate the new image
	//console.log(this.$target.width()+' + '+this.target_width);
	//if (idx > 0) this.$target.width(this.$target.width() + this.target_width);
	//console.log(this.$target.width());
	$img.appendTo(this.image_list[idx].$element);

	//if (!show) this.image_list[idx].$element.css('visibility', 'hidden'); //hide();
	this.image_list[idx].$element.appendTo(this.$target);
	//console.log(this.$target.width());
};

ck.rotator.prototype.start = function() {
	var rotator = this; // alias this, so we can refer to it simply within jquery

	// if we're missing necessary info, like the target image holder or an actual list of images, we've got nothing to start
	if (this.$target == null || this.image_list.length == 0) return;

	// this will wait for the document to load completely, or just run if the document is already loaded
	$(document).ready(function() {
		// go ahead and get the timer going, then we can start the images loading
		rotator.play();
		for (var i=1; i<rotator.image_list.length; i++) {
			rotator.load_img(i);
		}
	});
};

ck.rotator.prototype.next = function() {
	var rotator = this; // alias for jquery

	this.pause(true);

	var next_idx = (this.image_idx + 1) % this.image_list.length;
	
	// perform the rotate
	this.image_list[next_idx].$element.show().css('visibility', 'visible');
	this.$target.animate({left: -1*this.target_width*next_idx}, this.transition_time, function() {
		rotator.image_idx = next_idx;
		rotator.play();
	});
};

ck.rotator.prototype.prev = function() {
	var rotator = this; // alias for jquery

	this.pause(true);

	if (this.image_idx > 0) var next_idx = (this.image_idx - 1) % this.image_list.length;
	else var next_idx = this.image_list.length - 1;

	// perform the rotate
	this.image_list[next_idx].$element.show().css('visibility', 'visible');
	this.$target.animate({left: -1*this.target_width*next_idx}, this.transition_time, function() {
		rotator.image_idx = next_idx;
		rotator.play();
	});
};

// we may need to adjust all of these functions if we want pause to keep track of the currently running time (but not for previous and next, obviously)
// currently when the action is controlled purposefully, it always resets the clock.
ck.rotator.prototype.pause = function(clear) {
	if (clear) {
		this.timer_start = null;
		this.timer_left = null;
	}
	else this.timer_left = this.static_time - ((new Date()) - this.timer_start);
	this.timer_active = false;
	clearTimeout(this.timer);
};

ck.rotator.prototype.play = function() {
	rotator = this;
	var delay = this.static_time;
	if (this.timer_left) {
		delay = this.timer_left;
	}
	this.timer_active = true;
	this.timer_start = new Date();
	this.timer = setTimeout(function(){rotator.next();}, delay);
};

ck.rotator.prototype.pauseplay = function() {
	if (this.timer_active) this.pause();
	else this.play();
};