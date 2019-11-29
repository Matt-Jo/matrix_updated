var ck = ck || {};

ck.colgroups = function($table) {
	var cidx_start = 0;
	var cidx_end = 0;
	var span = 0;
	$table.find('col').each(function() {
		$col = jQuery(this);
		span = parseInt($col.attr('span')?$col.attr('span'):1);
		cidx_end += span;

		if ($col.attr('class')) {
			$table.find('tr').each(function() {
				jQuery(this).find('th, td').each(function(tidx) {
					if (tidx >= cidx_start && tidx < cidx_end) {
						jQuery(this).addClass($col.attr('class'));
					}
				});
			});
		}

		cidx_start = cidx_end;
	});
};