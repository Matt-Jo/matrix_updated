(function($) {

	var errors = [];

	var errcheck_timeout = null;

	function record_error($field, col_idx, msg) {
		var $row = $('.row-'+$field.attr('data-idx'));
		var colerr = parseInt($row.attr('data-colerr'));

		$field.addClass('error');

		colerr = colerr | Math.pow(2, col_idx);
		$row.attr('data-colerr', colerr);
		$row.addClass('error');

		if (msg != undefined) errors.push(msg);
	}

	function clear_error($field, col_idx) {
		var $row = $('.row-'+$field.attr('data-idx'));
		var colerr = parseInt($row.attr('data-colerr'));

		$field.removeClass('error');

		colerr = colerr & ~Math.pow(2, col_idx);
		$row.attr('data-colerr', colerr);
		if (colerr == 0) $row.removeClass('error');
	}

	function check_sheet_status() {
		// we need to bind `this` for this function to work appropriately

		errors = [];

		var $self = this;

		if (errcheck_timeout) clearTimeout(errcheck_timeout);

		errcheck_timeout = setTimeout(function() {
			for (var i=0; i<$self.opts.headers.length; i++) {
				if ($self.opts.headers[i].required && $self.find('.spreadsheet-column-title[value=\''+$self.opts.headers[i].value+'\']').length <= 0) {
					errors.push('Column Header '+$self.opts.headers[i].label+' must be included.');
				}
			}

			$('.spreadsheet-column-title').each(function(col_idx) {
				if ($self.opts.validators[$(this).val()] != undefined) {
					$self.opts.validators[$(this).val()].call($self, col_idx, record_error, clear_error);
				}
				else {
					$self.find('.col-'+col_idx+'.error').each(function() {
						clear_error($(this), col_idx);
					});
				}
			});

			$self.find('.spreadsheet-upload-errors').html(errors.join('<br>'));
		}, 700);
	}

	function skip_to($self) {
		var checked = $(this).is(':checked');
		var counter = parseInt($(this).attr('data-idx'));
		$('.upload_row').each(function(idx) {
			if (checked && parseInt($(this).attr('data-idx')) <= counter) {
				$(this).addClass('skipped');
				$(this).find('.spreadsheet-field').attr('disabled', true);
				$(this).find('.spreadsheet-skip-to').attr('checked', true);
			}
			else if (!checked && parseInt($(this).attr('data-idx')) >= counter) {
				$(this).removeClass('skippped');
				$(this).find('.spreadsheet-skip-to').attr('checked', false);
				if ($(this).find('.spreadsheet-skip-row').is(':checked')) return;
				$(this).find('.spreadsheet-field:not(.skipped)').attr('disabled', false);
			}
		});

		check_sheet_status.call($self);
	}

	$.fn.spreadsheet_upload = function(settings) {
		this.opts = $.extend({}, $.fn.spreadsheet_upload.defaults, settings);

		var skippers = [];

		var max_col = 0;

		var $self = this;

		this.find('tbody tr').each(function(row_idx) {
			$row = $(this);
			$row.addClass('upload_row').addClass('row-'+row_idx).attr('data-idx', row_idx).attr('data-colskip', 0).attr('data-colerr', 0);
			$row.find('td').each(function(col_idx) {
				var val = $(this).html();
				if ($self.opts.add_hide_header) {
					if (skippers[col_idx] == undefined) skippers[col_idx] = {};
					if (skippers[col_idx][val] == undefined) skippers[col_idx][val] = 0;
					skippers[col_idx][val]++;
				}
				var $inp = $('<input type="text" class="spreadsheet-field col-'+col_idx+' excol-'+Math.pow(2, col_idx)+'" name="spreadsheet_field['+row_idx+']['+col_idx+']" data-idx="'+row_idx+'">');
				$inp.attr('value', val);
				$(this).html($inp);

				max_col = Math.max(max_col, col_idx);
			});
			$row.append('<td><input type="checkbox" class="spreadsheet-skip-row" name="spreadsheet_skip_row['+row_idx+']" data-idx="'+row_idx+'"></td>');
			$row.prepend('<td><input type="checkbox" class="spreadsheet-skip-to" name="spreadsheet_skip_row['+row_idx+']" data-idx="'+row_idx+'"></td>');
			if ($self.opts.add_row_id) $row.prepend('<td>'+(row_idx+1)+'</td>');
		});

		$('.spreadsheet-skip-to').click(function() {
			skip_to.call(this, $self);
		});

		$('.spreadsheet-skip-row').click(function() {
			var checked = $(this).is(':checked');
			var idx = parseInt($(this).attr('data-idx'));
			if ($('.upload_row.row-'+idx).find('.spreadsheet-skip-to').is(':checked')) return;
			$('.row-'+idx).find('.spreadsheet-field:not(.skipped)').attr('disabled', checked);
			$('.row-'+idx).toggleClass('skipped', checked);

			check_sheet_status.call($self);
		});

		// if we haven't opted to add the skip header, skippers will be empty anyway
		for (var i=0; i<skippers.length; i++) {
			for (var k in skippers[i]) {
				if (skippers[i][k] <= 1) delete skippers[i][k];
			}
			skippers[i] = Object.keys(skippers[i]);
		}

		var $header = $('<thead></thead>');

		var $errors = $('<tr><td class="spreadsheet-upload-errors" colspan="'+max_col+'"></td></tr>');
		$header.append($errors);

		var header_matches = {};

		var $titles = $('<tr></tr>');
		if (this.opts.add_row_id) $titles.append('<th>#</th>');
		$titles.append('<th>Skip &darr;</th>');
		for (var i=0; i<=max_col; i++) {
			$title_picker = $('<select class="spreadsheet-column-title" name="spreadsheet_column['+i+']" size="1" data-idx="'+i+'" data-autoset="0"></select>');
			$title_picker.append('<option value="0">SKIP</option>');
			for (var j=0; j<this.opts.headers.length; j++) {
				$title_picker.append('<option value="'+this.opts.headers[j].value+'" '+(i==j?'selected':'')+'>'+this.opts.headers[j].label+'</option>');
				header_matches[this.opts.headers[j].value.toLowerCase().replace(/[^a-z0-9]/, '')] = this.opts.headers[j].value;
			}
			$titles.append($('<th></th>').append($title_picker));
		}
		$titles.append('<th>Skip &larr;</th>');
		$header.append($titles);

		if (this.opts.add_hide_header) {
			var $hiders = $('<tr></tr>');
			if (this.opts.add_row_id) $hiders.append('<th></th>');
			$hiders.append('<th>Hide:</th>');
			for (var i=0; i<=max_col; i++) {
				$hider_picker = $('<select class="skip-values" name="skip_values['+i+']" size="1" data-idx="'+Math.pow(2, i)+'"></select>');
				$hider_picker.append('<option value="NONE">NONE</option>');
				for (var j=0; j<skippers[i].length; j++) {
					$hider_picker.append('<option value="'+skippers[i][j]+'">'+skippers[i][j].substring(0, 20)+'</option>');
				}
				$hiders.append($('<th></th>').append($hider_picker));
			}
			$hiders.append('<th></th>');
			$header.append($hiders);
		}

		this.prepend($header);

		$('.spreadsheet-column-title').change(function() {
			if ($(this).val() == 0) $('.col-'+$(this).attr('data-idx')).addClass('skipped').attr('disabled', true);
			else $('.upload_row:not(.skipped) .col-'+$(this).attr('data-idx')).removeClass('skipped').attr('disabled', false);

			check_sheet_status.call($self);
		});

		if (this.opts.add_hide_header) {
			$('.skip-values').change(function() {
				var col = parseInt($(this).attr('data-idx'));
				if ($(this).val() == 'NONE') {
					$('.upload_row').each(function(idx) {
						var colskip = parseInt($(this).attr('data-colskip'));
						colskip = colskip & ~col;
						$(this).attr('data-colskip', colskip);
						if (colskip > 0) return;
						$(this).show();
						if ($(this).hasClass('skipped')) return;
						$(this).find('.spreadsheet-field:not(.skipped)').attr('disabled', false);
					});
				}
				else {
					var skip_value = $(this).val();
					$('.excol-'+col).each(function() {
						if ($(this).val() != skip_value) return;
						var $row = $('.row-'+$(this).attr('data-idx'));
						var colskip = parseInt($row.attr('data-colskip'));
						if (colskip == 0) $row.hide();
						colskip = colskip | col;
						$row.attr('data-colskip', colskip);
						$row.find('.spreadsheet-field').attr('disabled', true);
					});
				}

				check_sheet_status.call($self);
			});

			// try and text-match the headers
			this.find('tbody tr:first-child').each(function(row_idx) {
				if (row_idx > 0) return;
				$row = $(this);

				var header_found = false;

				$row.find('td input').each(function(col_idx) {
					var header_attempt = $(this).val().toLowerCase().replace(/[^a-z0-9]/, '');
					if (header_matches[header_attempt] != undefined) {
						$('.spreadsheet-column-title[data-idx='+(col_idx-1)+']').val(header_matches[header_attempt]).attr('data-autoset', 1);
						header_found = true;
					}
				});

				if (header_found) {
					$('.spreadsheet-column-title[data-autoset=0]').val(0);
					//$('.spreadsheet-skip-row[data-idx=0]').click();
					skip_to.call($('.spreadsheet-skip-to[data-idx=0]').attr('checked', true).get(0), $self);
				}
			});
		}

		check_sheet_status.call(this);
	};

	$.fn.spreadsheet_upload.defaults = {
		add_row_id: true,
		add_hide_header: true,
		headers: [],
		validators: {}
	};
}(jQuery));