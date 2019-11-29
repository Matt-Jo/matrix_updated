<?php
require('includes/application_top.php');

//this code was copied over from the ipn_editor_actions and modified, thats why some of it is dumb - matt.
if (isset($_GET['action']) && $_GET['action']=='ipn_validate') {
	if ($row = prepared_query::fetch('select stock_id, stock_name, stock_weight from products_stock_control where stock_name = :ipn', cardinality::ROW, [':ipn' => $_GET['ipn_stock_name']])) {
		$ipn_stock_id=$row['stock_id'];
		$ipn_stock_name=$row['stock_name'];
		$ipn_stock_weight=$row['stock_weight'];
		print "$ipn_stock_name <span id=\"ipn_stock_weight_disp\" style=\"visibility:hidden\">$ipn_stock_weight</span>";
		exit();
	}
	else {
		print "error";
		exit();
	}
}

if (isset($_POST['ipn_stock_name']) && isset($_POST['ipn_stock_weight'])) {
	$ipn_stock_name=$_POST['ipn_stock_name'];
	$new_weight = $_POST['ipn_stock_weight'];
	$change_user = $_SESSION['login_email_address'];
	$edit_date = date('Y-m-d');

	$row = prepared_query::fetch('select stock_id, stock_weight from products_stock_control where stock_name= :ipn', cardinality::ROW, [':ipn' => $ipn_stock_name]);
	$ipn_stock_id=$row['stock_id'];
	$old_weight=$row['stock_weight'];

	prepared_query::execute('UPDATE products_stock_control psc LEFT JOIN products p ON psc.stock_id = p.stock_id SET psc.stock_weight = :new_weight, psc.last_weight_change = NOW(), p.products_weight = :new_weight WHERE psc.stock_name = :ipn', [':new_weight' => $new_weight, ':ipn' => $ipn_stock_name]);

	insert_psc_change_history($ipn_stock_id, 'Weight Change', $old_weight, $new_weight);
	insert_psc_change_history($ipn_stock_id, 'Weight Confirmation', '---', '---');

	CK\fn::redirect_and_exit('/admin/ipn_weight_update.php');
} ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
	<title><?php echo TITLE; ?></title>
	<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
	<!-- header //-->
	<?php require(DIR_WS_INCLUDES.'header.php'); ?>
	<!-- header_eof //-->
	<!-- body //-->
	<table border="0" width="100%" cellspacing="2" cellpadding="2">
		<tr>
			<td width="<?php echo BOX_WIDTH; ?>" valign="top">
				<table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
					<!-- left_navigation //-->
					<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
					<!-- left_navigation_eof //-->
				</table>
			</td>
			<!-- body_text //-->
			<td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
				<script language="javascript" src="/includes/javascript/prototype.js"></script>
				<script language="javascript" src="/includes/javascript/scriptaculous/scriptaculous.js"></script>
				<link rel="stylesheet" type="text/css" href="/includes/javascript/scriptaculous/scriptaculous.css">
				<style type="text/css">
					#stockNameDiv { position: absolute; top: 190px; left:135px; width: 290px; }
					#curWeightDiv { position: absolute; top: 190px; left:425px; width: 310px; }
					#stockWeightDiv { position: absolute; top: 190px; left:700px; width: 400px; }
				</style>
				<div id="ipn_name_div" style=" font-weight:bold; font-size: 18px; text-align: center;"></div>
				<br/>
				<div style=" font-weight:bold; font-size: 18px;">
					<form method="post" action="ipn_weight_update.php" id="form_ipn_weight">
						<div id="stockNameDiv">
							<label for="ipn_stock_name">Stock Name:</label>
							<input name="ipn_stock_name" id="ipn_stock_name" type="text" value="">
						</div>
						<div id="curWeightDiv">
							<label for="ipn_current_stock_weight">Current Stock Weight:</label>
							<span id="ipn_current_stock_weight"></span>
						</div>
						<div id="stockWeightDiv">
							<label for="ipn_stock_weight">Stock Weight:</label>
							<input name="ipn_stock_weight" id="ipn_stock_weight" type="text" value="" />
							<input type="submit" value="Submit">
						</div>
					</form>
				</div>
				<script type="text/javascript">
					Event.observe('ipn_stock_name', 'keydown',
						function(e) {
							if (e.keyCode==13) {
								new Ajax.Request('ipn_weight_update.php',{
									method: 'get',
									parameters: {action: 'ipn_validate', ipn_stock_name: $F('ipn_stock_name')},
									onSuccess: function (transport) {
										if (transport.responseText.match(/error/)) {
											alert('IPN: '+$F('ipn_stock_name')+' is not a valid stock name.');
											$('ipn_stock_name').clear();
											$('ipn_stock_name').focus();
											$('ipn_name_div').update('');
										}
										else {
											$('ipn_name_div').update(transport.responseText);
											$('ipn_current_stock_weight').innerHTML = $('ipn_stock_weight_disp').innerHTML;
											$('ipn_stock_weight').focus();
										}
									}
								});
								e.stop();
							}
						});
				</script>
			</td>
		</tr>
	</table>
</body>
</html>
