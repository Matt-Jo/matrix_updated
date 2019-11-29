<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/mustache.js/2.1.3/mustache.min.js"></script>
<script src="/images/static/js/ck-styleset.js"></script>
<script src="/images/static/js/ck-autocomplete.js"></script>
<style>
	#part-lookup { margin:20px; }
	#ipn_lookup { width:900px; height:20px; font-size:16px; padding-left:8px; }
	#lookup-results { margin-top:10px; }
	.included-accessories { border-collapse:collapse; }
	.included-accessories th, .included-accessories td { text-align:left; padding:3px 6px; border:1px solid #888; }
	.included-accessories th { border-bottom-width:3px; }
	.included-accessories .results-header { border-width:0px; }
	.included-accessories a { text-decoration:underline; color:#666; }
	.included-accessories .desc td { border-bottom-width:3px; }
	.included-accessories tr td.spacer { border-width:0px; }
</style>
<div id="part-lookup">
	<a href="/vendorportal" style="margin-bottom:20px;">< Go Back To Vendor Portal</a>
	<h2>Product Accessory Lookup Beta</h2>
	<form action="/VendorPortal/lookup" method="post" class="clearfix" id="lookup_form">
		<input type="hidden" name="action" value="save" id="action">
		<input type="hidden" name="ajax" value="1">
		<input type="text" name="ipn_lookup" id="ipn_lookup" placeholder="Part # Lookup">
	</form>
	<div id="lookup-results">
	</div>
</div>
<script>
	var part_lookup_ac = new ck.autocomplete('ipn_lookup', '/vendorportal/lookup', {
		preprocess: function() {
			jQuery('#lookup-results').html('Searching...');
		},
		//results_template: '<table{{#results}}<a href="#" class="entry" id="{{result_id}}">{{{result_label}}}</a>{{/results}}',
		results_template: '<table cellpadding="0" cellspacing="0" border="0" class="autocomplete-results-table"><tbody>{{#results}}<tr class="table-entry" id="{{result_id}}"><td>{{{ipn}}}<small>({{condition}})</small></td><td>{{{result_label}}}</td><td>{{included_accessories}} part(s) included</td></tr>{{/results}}</tbody></table>',
		autocomplete_action: 'part-lookup',
		autocomplete_field_name: 'ipn_lookup',
		select_result: lookup_result
	});

	ck.autocomplete.styles({
		'.autocomplete-results.table-results': 'border:0px;',
		'.autocomplete-results-table': 'border-collapse:collapse;',
		'.autocomplete-results-table .table-entry td': 'margin:0px; padding:4px 6px 4px 3px; font-size:15px; white-space:nowrap; border-bottom:1px solid #999; border-right:1px dotted #000; cursor:pointer;',
		'.autocomplete-results-table .table-entry:hover td': 'background:linear-gradient(#6ff, #7cf); color:#000;',
		'.autocomplete-results-table .table-entry small': 'font-style:italic;margin-left:3px;'
	});

	function lookup_result(data) {
		jQuery.ajax({
			url: '/vendorportal/lookup',
			dataType: 'json',
			data: {
				action: 'part-select',
				products_id: data.result_id
			},
			success: function(data) {
				var model = data.base_product_link==1?'<a href="'+data.base_product_url+'">'+data.base_product_model+'</a>':data.base_product_model;
				var $table = jQuery('<table cellpadding="0" cellspacing="0" border="0" class="included-accessories"></table>');
				$table.append('<thead><tr><th colspan="3" class="results-header">Included Accessories for '+model+' <small>('+data.base_product_condition+')</small></th></tr><tr><th>IPN</th><th>Model #</th><th>Name</th><th>Price</th></tr></thead>');
				var $body = jQuery('<tbody></tbody>');
				for (var i=0; i<data.products.length; i++) {
					var opt_model = data.products[i].link==1?'<a href="'+data.products[i].url+'">'+data.products[i].model+'</a>':'';
					$body.append('<tr><td>'+data.products[i].ipn+'</td><td>'+opt_model+'</td><td>'+data.products[i].name+'</td><td>'+data.products[i].price+'</td></tr>');
					$body.append('<tr class="desc"><td class="spacer"></td><td colspan="3">'+data.products[i].short_desc+'</td></tr>');
				}
				$table.append($body);
				jQuery('#lookup-results').html($table);
			}
		});
	}
</script>

<?php if (empty($_SESSION['customer_id'])) {
	$login_return_to = '/VendorPortal/lookup'; ?>
<div id="overlay">
	<style>
		#login { background-color:#fff; height:200px; width:325px; margin:10% auto 0px auto; padding:10px 30px; }
		#close-login { text-align:right; }
		#close-login a { display:block; padding:0px 3px; font-weight:bold; line-height:80%; float:right; font-size:1.2em; text-align:center; background-color:#fff; color:#e51937; }
		#close-login a:hover { text-decoration:none; }
	</style>
	<div id="login" class="active-area">
		<form action="/login.php?action=process" method="post">
			<input type="hidden" name="login_return_to" value="<?= $login_return_to; ?>">
			<div id="close-login">
				<a href="#" class="active-close">[X]</a>
			</div>
			<table width="254" border="0" cellspacing="0" cellpadding="0" style="font-size: 13px; text-align: left;">
				<tr>
					<td>Please Log In</td>
				</tr>
				<tr>
					<td>Email Address<br>
					<input type="text" id="loginput" name="email_address" value="<?= @$_REQUEST['email_address']; ?>"></td>
				</tr>
				<tr>
					<td>
						Enter Password<br>
						<input type="hidden" name="new_customer" value="N">
						<input type="password" name="password" id="loginput" class="lgi1"><br>
						<span class="logfp"><a href="/password_forgotten.php">Forget your password?</a></span>
					</td>
				</tr>
				<tr>
					<td><input type="image" src="/templates/Pixame_v1/images/login/signin.gif" class="logsignin"></td>
				</tr>
			</table>
			<div>
				<a href="/login.php?login_return_to=<?php echo urlencode($login_return_to); ?>">Create an account</a>
			</div>
		</form>
	</div>
</div>
<script>
	var $overlay = jQuery('#overlay');
	$overlay.remove();
	jQuery('body').append($overlay);
	$overlay.fadeIn();

	jQuery('.active-area').click(function(e) {
		e.stopPropagation();
	});

	jQuery('.active-close').click(function(e) {
		e.preventDefault();
		e.stopPropagation();
		overlay_close_action();
	});

	$overlay.click(function() {
		overlay_close_action();
	});

	function overlay_close_action() {
		window.location = '/';
	}
</script>
<?php } ?>
