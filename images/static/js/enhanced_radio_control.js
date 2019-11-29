function enhanced_radio_control(name) {
	this.name = name;

	this.init(); // if we're ready, we can go ahead and set up our event
};
enhanced_radio_control.prototype.init = function() {
	var self = this;
	this.$peers = jQuery('input[name='+this.name+']');

	this.$peers.each(function() {
		if (jQuery(this).is(':checked')) jQuery(this).attr('data-previous-value', 1);
		else jQuery(this).attr('data-previous-value', 0);
	});

	if (this.$peers.length >= 1) {
		this.$peers.click(function() {
			self.toggle(jQuery(this));
		});
	}
};
enhanced_radio_control.prototype.check = function($radio) {
	var self = this;
	this.$peers.each(function() {
		if (jQuery(this) == $radio) return;
		self.uncheck(jQuery(this));
	});

	$radio.attr('checked', true).attr('data-previous-value', 1);
};
enhanced_radio_control.prototype.uncheck = function($radio) {
	$radio.attr('checked', false).attr('data-previous-value', 0);
};
enhanced_radio_control.prototype.toggle = function($radio) {
	if (this.is_checked($radio)) this.uncheck($radio);
	else this.check($radio);
};
enhanced_radio_control.prototype.is_checked = function($radio) {
	return $radio.attr('data-previous-value')==1;
};