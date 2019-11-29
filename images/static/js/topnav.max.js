// initialize our ck namespace, if it hasn't been already
// we can do more than this, make it into a full framework a la jquery, see final 9/1/13 comment @ http://www.phpied.com/3-ways-to-define-a-javascript-class/
// I'll have to learn more about that, this is the extent to which I understand immediately
var ck = ck || {};
// properties prefixed with $ are meant to hold jquery objects

/**
	Creates a new rotator.
	@constructor
*/
// in this particular case, a singleton might be more appropriate, but it's cool
ck.topnav = function(menulist, boundary) {
	var nav = this; // alias this, so we can refer to it simply within jquery
	// initialize the properties
	if (typeof boundary !== 'undefined') this.$boundary = $(boundary);
	else this.$boundary = null;
	this.menulist = menulist;
	this.close_delay = 20; // milliseconds
	this.close_animation = 120; // milliseconds

	this.right_boundary = this.$boundary.offset().left + this.$boundary.width();

	var context_class = '';

	$(document).ready(function() {
		for (var i=0; i<nav.menulist.length; i++) {
			$('.'+nav.menulist[i].menu).each(function(idx) {
				if ($(this).offset().left + $(this).width() > nav.right_boundary) $(this).addClass('boundary-shift');
			});
			$('.'+nav.menulist[i].link).hover(
				function() {
					$(this).addClass('in-context');
				},
				function() {
					var $lnk = $(this);
					$lnk.addClass('unselect');
					if ($('.ie').length) {
						// ie 9 and less does not support the css animations, and it doesn't look pretty when it doesn't use the animation, so skip the delay
						// all other browsers and version with enough traffic to concern ourselves with supports animations
						$lnk.removeClass('in-context').removeClass('unselect');
					}
					else {
						setTimeout(function() { $lnk.removeClass('in-context').removeClass('unselect'); }, nav.close_animation);
					}
				}
			);
		}
	});

	/*

	for (var i=0; i<this.menulist.length; i++) {
		$('.'+this.menulist[i].link).mouseover(function() {
			$(this).addClass('in-context');
			if (nav.menulist[i].hasOwnProperty('menu')) $('.'+nav.menulist[i].menu).addClass('in-context');
			for (var j=i; j>=0; j--) {
				var lock = Math.max(parseInt($('.'+nav.menulist[j].link).attr('data-lockctr')), 0);
				lock++;
				$('.'+nav.menulist[j].link).attr('data-lockctr', lock);
			}
		});

		$('.'+this.menulist[i].link+', .'+this.menulist[i].menu).mouseout(function() {
			for (var j=i-1; j>=0; j--) {
				var lock = parseInt($('.'+nav.menulist[j].link).attr('data-lockctr'));
				lock--;
				lock = Math.max(lock, 0);
				$('.'+nav.menulist[j].link).attr('data-lockctr', lock);
				if (lock == 0) {
					setTimeout(
						function(idx) {
							var lock = parseInt($('.'+nav.menulist[idx].link).attr('data-lockctr'));
							if (lock > 0) return;
							setTimeout(
								function(idx) {
									$('.'+nav.menulist[idx].link).removeClass('in-context');
									if (nav.menulist[idx].hasOwnProperty('menu')) $('.'+nav.menulist[idx].menu).removeClass('in-context');
								},
								nav.close_animation
							);
						},
						nav.close_delay
					);
				}
			}
		});
	}*/
};