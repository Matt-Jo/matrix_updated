<?php
require('includes/application_top.php');

require(DIR_WS_LANGUAGES.$_SESSION['language'].'/'.FILENAME_CREATE_VENDOR_ACCOUNT);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<?php require('includes/form_check.js.php'); ?>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onLoad="SetFocus();">
<!-- header //-->
<?php require(DIR_WS_INCLUDES.'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->


<table border="0" width="100%" cellspacing="2" cellpadding="2">
 <tr>
	<td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES.'column_left.php'); ?>
<!-- left_navigation_eof //-->
	</table></td>
<!-- body_text //-->
	<td width="100%" valign="top"><form name="account_edit" method="post" action="/admin/create_vendor_account_process.php" onSubmit="return check_form();"><input type="hidden" name="action" value="process"><table border="0" width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>
			<td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
		</tr>
		</table></td>
	</tr>
	<tr>
		<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
	</tr>
	<tr>
		<td>
			<?php require(DIR_WS_MODULES.'account_details_vendors.php'); ?>
		</td>
	</tr>
	<tr>
		<td align="right" class="main"><br><?php echo tep_image_submit('button_confirm.gif', @IMAGE_BUTTON_CONTINUE); ?></td>
	</tr>
	</table></form></td>
<!-- body_text_eof //-->
	<td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="0" cellpadding="2">
	</table></td>
 </tr>
</table>
<!-- body_eof //-->

</body>
</html>
