var ck = ck || {};

ck.global_category_navigator_init = false;

ck.category_navigator = function($select) {

	if (!ck.global_category_navigator_init) {
		ck.category_navigator.styleset = new ck.styleset('category-navigator');
		ck.category_navigator.styleset.add_selector('.category-navigator-back').add_stylestring('background-color:#ccf;');
		ck.category_navigator.styleset.render();

		ck.global_category_navigator_init = true;
	}

	this.top_level = [];
	this.selections = [];

	this.$select = $select;

	this.$select.each(function() {
		this.selected_list = [];
		this.$default_list = jQuery(this).find('option');
	});

	var self = this;

	this.$select.change(function() {
		var category_id = jQuery(this).val();
		jQuery(this).find('option').each(function() {
			if (jQuery(this).attr('value') == category_id) return;
			jQuery(this).remove();
		});
		if (category_id == -1) {
			// we're backing up
			this.selected_list.pop();
			if (this.selected_list.length) {
				// there's a previously selected category to back up to
				category_id = this.selected_list[this.selected_list.length - 1];

				if (this.selected_list.length > 1) {
					var previous_category_id = this.selected_list[this.selected_list.length - 2];

					for (var j=0; j<self.selections[previous_category_id].length; j++) {
						if (self.selections[previous_category_id][j]['id'] == category_id) {
							jQuery(this).prepend('<option value="'+category_id+'">'+self.selections[previous_category_id][j]['name']+' ['+(this.selected_list.length-1)+']</option>');
						}
					}
				}
				else {
					for (var j=0; j<self.top_level.length; j++) {
						if (self.top_level[j]['id'] == category_id) {
							jQuery(this).prepend('<option value="'+category_id+'">'+self.top_level[j]['name']+'</option>');
						}
					}
				}

				jQuery(this).val(category_id);
				for (var i=0; i<self.selections[category_id].length; i++) {
					jQuery(this).append('<option value="'+self.selections[category_id][i]['id']+'">'+self.selections[category_id][i]['name']+' ['+this.selected_list.length+']</option>');
				}
			}
			else {
				// we're back at the top level
				jQuery(this).find('option').remove();
				jQuery(this).append(this.$default_list);
				jQuery(this).val('');
				for (var i=0; i<self.top_level.length; i++) {
					jQuery(this).append('<option value="'+self.top_level[i]['id']+'">'+self.top_level[i]['name']+'</option>');
				}
			}
		}
		else {
			// we selected a category
			this.selected_list.push(category_id);
			jQuery(this).append('<option value="-1" class="category-navigator-back">Back One Level</option>');
			if (self.selections[category_id]) {
				for (var i=0; i<self.selections[category_id].length; i++) {
					jQuery(this).append('<option value="'+self.selections[category_id][i]['id']+'">'+self.selections[category_id][i]['name']+' ['+this.selected_list.length+']</option>');
				}
			}
		}
	});
};

ck.category_navigator.styleset = null;

ck.category_navigator.prototype.load_top_level = function(top_level) {
	this.top_level = top_level;

	for (var i=0; i<top_level.length; i++) {
		this.$select.append('<option value="'+top_level[i].id+'">'+top_level[i].name+'</option>');
	}
};

ck.category_navigator.prototype.load_selections = function(selections) {
	this.selections = selections;
};

ck.category_navigator.prototype.select_path = function($element, categories) {
	for (let i=0; i<categories.length; i++) {
		$element.val(categories[i]).trigger('change');
	}
};
