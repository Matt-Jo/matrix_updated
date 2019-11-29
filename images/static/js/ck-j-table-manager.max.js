// this is taking over for the incomplete images/static/js/ck-table-manager.max.js
(function($) {
	$.fn.table_manager = function(settings) {
		var opts = $.extend({}, $.fn.table_manager.defaults, settings);

		var $self = this;

		$self.addClass('ck-table-manager');

		//create_styleset();

		$self.find('thead th').each(function(idx) {
			$(this).data('col-idx', idx);
		});

		$self.find('thead th').on('hover', function(e) {
			if (e.type == 'mouseenter') {
				var lookup_idx = $(this).data('col-idx') + 1; // 1 based indexing for css vs 0 based for js
				if (opts.number_rows) lookup_idx += 1;

				$self.find('tbody td:nth-child('+lookup_idx+')').addClass('col-hover');
			}
			else {
				var lookup_idx = $(this).data('col-idx') + 1; // 1 based indexing for css vs 0 based for js
				if (opts.number_rows) lookup_idx += 1;

				$self.find('tbody td:nth-child('+lookup_idx+')').removeClass('col-hover');
			}
		});

		if (opts.styles != null) {
		}

		if (opts.color_rows) $self.addClass('color-rows');

		if (opts.sortable) {
			$self.addClass('sortable');

			$self.find('thead th:not(.no-sort)').data('sortdir', '').append('<span class="column-sorter asc">&#x25B2;</span><span class="column-sorter desc">&#x25BC;</span>');

			var $sortable_table = [];

			$self.find('tbody tr').each(function() {
				$sortable_table.push($(this));
			});

			var sort_add = false;
			var sort_column_order = [];

			$('body').on('keydown', function(e) {
				var key = e.keyCode || e.which;
				if (e.ctrlKey) sort_add = true;
			});

			$('body').on('keyup', function(e) {
				sort_add = false;
			});

			$self.find('thead th:not(.no-sort)').on('click', function() {
				var $col = $(this);
				var dir = $col.data('sortdir');

				if (!sort_add) {
					$self.find('.sorted').removeClass('sorted').removeClass('asc').removeClass('desc').data('sortdir', '');
					sort_column_order = [];
				}
				else {
					$col.removeClass(dir).data('sortdir', '');
				}

				var newdir = dir=='asc'?'desc':'asc';

				$col.addClass('sorted').addClass(newdir).data('sortdir', newdir);
				var column_idx = $col.data('col-idx');

				if (sort_column_order.indexOf(column_idx) === -1) sort_column_order.push(column_idx);

				$sortable_table.sort(function($a, $b) {
					var val1, val2;

					for (var i=0; i<sort_column_order.length; i++) {
						var method = opts.sort_methods[sort_column_order[i]]!=undefined?opts.sort_methods[sort_column_order[i]]:'text';

						var lookup_idx = opts.number_rows?sort_column_order[i]+1:sort_column_order[i];

						var coldir = $($self.find('thead th')[lookup_idx]).data('sortdir');

						var modifier = coldir=='asc'?1:-1;

						val1 = $.trim($($a.find('th, td')[lookup_idx]).text());
						val2 = $.trim($($b.find('th, td')[lookup_idx]).text());

						if (method == 'text') {
							val1 = val1.toUpperCase();
							val2 = val2.toUpperCase();
						}
						else if (method == 'integer') {
							val1 = parseInt(val1);
							val2 = parseInt(val2);
						}
						else if (method == 'numeric') {
							val1 = parseFloat(val1);
							val2 = parseFloat(val2);
						}
						else if (method == 'money') {
							val1 = parseFloat(val1.replace(/[^\d.-]/g, ''));
							val2 = parseFloat(val2.replace(/[^\d.-]/g, ''));
						}
						else if (method == 'date') {
							val1 = new Date(val1);
							val2 = new Date(val2);
						}
						else if (typeof method == 'function') {
							var result = method(val1, val2);
							if (result == 0) continue;
							else return result * modifier;
						}

						if (val1 < val2) return -1 * modifier;
						else if (val1 > val2) return 1 * modifier;
					}

					return 0;
				});

				$self.find('tbody').html('');
				for (var i=0; i<$sortable_table.length; i++) {
					$self.find('tbody').append($sortable_table[i]);
				}
				if (opts.number_rows) {
					$self.find('tbody tr td:first-child').each(function (idx) {
						$(this).html(idx+1);
					});
				}
			});
		}

		if (opts.number_rows) {
			$self.addClass('numbered');
			$self.find('thead tr').prepend('<th class="no-sort">#</th>');
			$self.find('tbody tr').each(function (idx) {
				$(this).prepend('<td class="row-idx">'+(idx+1)+'</td>');
			});
		}

		/*if (opts.lock_header) {
			
		}*/
	};

	$.fn.table_manager.defaults = {
		styles: null,
		color_rows: true,
		sortable: false,
		sort_methods: {},
		number_rows: false,
		lock_header: false,
		//ajax_reload_url: null,
	};

	var styleset = null;

	function create_styleset() {
		if (styleset != null) return;

		styleset = new ck.styleset('table-manager');
		styleset.add_selector('.ck-table-manager').add_stylestring('border-collapse:separate; font-size:12px;');

		styleset.add_selector('.ck-table-manager th, .ck-table-manager td').add_stylestring('padding:4px 8px; border-style:solid; border-color:#000; border-width:0px 1px 1px 0px;');
		styleset.add_selector('.ck-table-manager thead th').add_stylestring('background-color:#888; color:#fff; border-right-color:#fff;');
		styleset.add_selector('.ck-table-manager thead th:last-child').add_stylestring('border-right-color:#000;');
		styleset.add_selector('.ck-table-manager thead tr:first-child th').add_stylestring('border-top-width:1px;');
		styleset.add_selector('.ck-table-manager th:first-child, .ck-table-manager td:first-child').add_stylestring('border-left-width:1px;');

		styleset.add_selector('.ck-table-manager tbody tr:hover td').add_stylestring('background-color:#cff;');
		styleset.add_selector('.ck-table-manager tbody tr td.col-hover').add_stylestring('background-color:#cff;');
		
		styleset.add_selector('.ck-table-manager.color-rows tbody tr:nth-child(even) th, .ck-table-manager.color-rows tbody tr:nth-child(even) td:not(.col-hover)').add_stylestring('background-color:#eee;');
		styleset.add_selector('.ck-table-manager.color-rows tbody tr:nth-child(odd) th, .ck-table-manager.color-rows tbody tr:nth-child(odd) td:not(.col-hover)').add_stylestring('background-color:#fff;');
		styleset.add_selector('.ck-table-manager.color-rows tbody tr:nth-child(even):hover th, .ck-table-manager.color-rows tbody tr:nth-child(even):hover td:not(.col-hover)').add_stylestring('background-color:#cff;');
		styleset.add_selector('.ck-table-manager.color-rows tbody tr:nth-child(odd):hover th, .ck-table-manager.color-rows tbody tr:nth-child(odd):hover td:not(.col-hover)').add_stylestring('background-color:#cff;');

		styleset.add_selector('.ck-table-manager.sortable thead th:not(.no-sort)').add_stylestring('cursor:pointer; padding-right:24px;');
		styleset.add_selector('.ck-table-manager.sortable thead th:not(.no-sort):hover').add_stylestring('background-color:#ff9; color:#000; padding-right:8px;');
		styleset.add_selector('.ck-table-manager.sortable thead th.sorted').add_stylestring('background-color:#4ff; color:#000; padding-right:8px;');

		styleset.add_selector('.ck-table-manager.sortable thead th .column-sorter').add_stylestring('display:none; color:#888; width:16px; text-align:right;');
		styleset.add_selector('.ck-table-manager.sortable thead th:not(.sorted):hover .column-sorter.asc').add_stylestring('display:inline-block;');
		styleset.add_selector('.ck-table-manager.sortable thead th.sorted.asc .column-sorter.asc').add_stylestring('display:inline-block;');
		styleset.add_selector('.ck-table-manager.sortable thead th.sorted.desc .column-sorter.desc').add_stylestring('display:inline-block;');
		/*styleset.add_selector('.ck-table-manager.sortable thead th.sorted.asc:hover .column-sorter.asc').add_stylestring('display:none;');
		styleset.add_selector('.ck-table-manager.sortable thead th.sorted.asc:hover .column-sorter.desc').add_stylestring('display:inline-block;');
		styleset.add_selector('.ck-table-manager.sortable thead th.sorted.desc:hover .column-sorter.desc').add_stylestring('display:none;');
		styleset.add_selector('.ck-table-manager.sortable thead th.sorted.desc:hover .column-sorter.asc').add_stylestring('display:inline-block;');*/

		/*styleset.add_selector('.ck-table-manager.locked thead tr').add_stylestring('position:fixed; top:0px;');*/

		styleset.render();
	}

	create_styleset();
}(jQuery));