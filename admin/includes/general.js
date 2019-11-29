function SetFocus() {
	if (document.forms.length > 0) {
		var field = document.forms[0];
		for (i=0; i<field.length; i++) {
			if (field.elements[i].type != "image" && field.elements[i].type != "hidden" && field.elements[i].type != "reset" && field.elements[i].type != "submit") {
				document.forms[0].elements[i].focus();

				if (field.elements[i].type == "text" || field.elements[i].type == "password") document.forms[0].elements[i].select();
				break;
			}
		}
	}
}

function rowOverEffect(object) {
	if (object.className == 'dataTableRow') object.className = 'dataTableRowOver';
}

function rowOutEffect(object) {
	if (object.className == 'dataTableRowOver') object.className = 'dataTableRow';
}

var header_search_tracker = 0;

function header_search_select(data) {
	header_search_tracker++;
	if (header_search_tracker == 1) {
		searchType = jQuery('#header_search_type').val();
		switch (searchType) {
			case 'serial':
				window.location = "ipn_editor.php?ipnId="+urlencode(data.value)+"&search_serial="+data.label+"&selectedTab=8";
				break;
			case 'ipn':
				window.location = "ipn_editor.php?ipnId="+urlencode(data.value);
				break;
			case 'order':
				if (data.label != null) data = data.label;
				window.location = "orders_new.php?oID="+data+"&action=edit";
				break;
			case 'invoice':
				if (data.order_id != null) data = data.order_id;
				window.location = "orders_new.php?oID="+data+"&action=edit";
				break;
			case 'po_number':
				if (data.label != null) data = data.label;
				window.location = "po_list.php?po_search="+data;
				break;
			case 'customer_email':
				if (data.value != null) data = data.value;
				window.location = 'customers_list.php?action=quicksearch&customers_id='+data;
				break;
			case 'track_number':
				//MMD - breaking value up - value is formatted as 'ABBBBB' where A is a type
				//(o, r, or p) and BBBBB is the id of the record
				var code = data.value.substring(0, 1);
				var id = data.value.substring(1);
				switch (code) {
					case 'o':
						window.location = "orders_new.php?action=edit&selected_box=orders&oID="+id;
						break;
					case 'r':
						window.location = "rma-detail.php?id="+id;
						break;
					case 'p':
						window.location = "po_list.php?tracking_search="+data.label;
						break;
				}
				break;
		}
	}
}

var ipn_editor_search_tracker = 0;

function ipn_editor_search_select(data) {
	ipn_editor_search_tracker++;
	if (ipn_editor_search_tracker == 1) {
		searchType = jQuery('#ipn_editor_search_type').val();
		switch (searchType) {
			case 'serial':
				window.location = "ipn_editor.php?ipnId="+urlencode(data.value)+"&search_serial="+data.label+"&selectedTab=8";
				break;
			case 'ipn':
			case 'stock':
				window.location = "ipn_editor.php?ipnId="+urlencode(data.value);
				break;
		}
	}
}

jQuery(document).ready(function() {
	//MMD - 092214 - this class will apply chosen to any select box
	jQuery('.jquery-chosen').chosen();

	//for global header search box
	jQuery('#header_search_type').change(function() {
		jQuery('#header_search_box').select();
	});

	//for ipn editor search box
	jQuery('#ipn_editor_search_type').change(function() {
		jQuery('#ipn_editor_search_box').select();
	});

	try {
		jQuery('#header_search_box')
			.autocomplete({
				minChars: 3,
				delay: 600,
				source: function (request, callback) {
					searchType = jQuery('#header_search_type').val();

					params = {
						action: 'generic_autocomplete',
						search_type: searchType,
						term: request.term
					}

					if (searchType == 'serial') {
						params.get_ipn = 1;
						params.search_all = 1;
					}

					jQuery.get('/admin/serials_ajax.php', params, function (data) {
						if (data == null) return false;

						callback(jQuery.map(data, function (item) {
							if (item.data_display != null) return {misc: item.value, label: item.data_display, value: item.label};
							else return item;
						}));
					}, "json");
				},
				select: function (e, ui) {
					if (ui != null) {
						e.preventDefault();
						jQuery('#header_search_box').val(ui.item.value);
						header_search_select(ui.item);
					}
				},
				focus: function (e, ui) {
					e.preventDefault();
				}
			})
			.keyup(function(e) {
				var key = e.keyCode || e.which;
				if (key != 13) return;

				//var $info = jQuery('.ui-menu-item');

				jQuery.ajax({
					url: '/admin/serials_ajax.php',
					dataType: 'json',
					data: {
						term: jQuery('#header_search_box').val(),
						search_type: jQuery('#header_search_type').val(),
						action: 'generic_autocomplete',
						limit: '1',
						get_ipn: 1,
						search_all: 1
					},
					success: function (data) {
						if (data != null) header_search_select(data[0]);
					}
				});
			});


		jQuery('#ipn_editor_search_box')
			.autocomplete({
				minChars: 1,
				delay: 600,
				source: function (request, callback) {
					searchType = jQuery('#ipn_editor_search_type').val();

					params = {
						action: 'generic_autocomplete',
						search_type: searchType,
						term: request.term
					}

					if (searchType == 'serial') {
						params.get_ipn = 1;
						params.search_all = 1;
					}

					jQuery.get('/admin/serials_ajax.php', params, function (data) {
						if (data == null) return false;

						callback(jQuery.map(data, function (item) {
							if (item.data_display != null) return {misc: item.value, label: item.data_display, value: item.label};
							else return item;
						}));
					}, "json");
				},
				select: function (e, ui) {
					if (ui != null) {
						e.preventDefault();
						jQuery('#ipn_editor_search_box').val(ui.item.value);
						ipn_editor_search_select(ui.item);
					}
				},
				focus: function (e, ui) {
					e.preventDefault();
				}
			})
			.keyup(function(e) {
				var key = e.keyCode || e.which;
				if (key != 13) return;

				//var $info = jQuery('.ui-menu-item');

				jQuery.ajax({
					url: '/admin/serials_ajax.php',
					dataType: 'json',
					data: {
						term: jQuery('#ipn_editor_search_box').val(),
						search_type: jQuery('#ipn_editor_search_type').val(),
						action: 'generic_autocomplete',
						limit: '1',
						get_ipn: 1,
						search_all: 1
					},
					success: function (data) {
						if (data != null) ipn_editor_search_select(data[0]);
					}
				});
			});
	}
	catch (err) {
		console.log(err);
	}
});
