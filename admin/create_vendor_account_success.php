<?php
require('includes/application_top.php');

require(DIR_WS_LANGUAGES.$_SESSION['language'].'/'.FILENAME_CREATE_VENDOR_ACCOUNT_SUCCESS);
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">

 <title><?php echo TITLE ?></title>

<base href="<?php echo (getenv('HTTPS') == 'on' ? HTTPS_SERVER : HTTP_SERVER).DIR_WS_ADMIN; ?>">
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0">
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
	<td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="0">
	<tr>
		<td><table border="0" width="100%" cellspacing="0" cellpadding="0">
		<tr>

			<td valign="top" class="main"><div align="center" class="pageHeading"><?php echo HEADING_TITLE; ?></div><br><?php echo TEXT_ACCOUNT_CREATED; ?></td>
		</tr>
		</table></td>
	</tr>
	<tr>
		<td align="right"><br><a href="/admin/vendors.php"><?php tep_image_button('button_back.gif', IMAGE_BUTTON_CONTINUE); ?></a></td>
	</tr>
	</table></td>
<!-- body_text_eof //-->

 </tr>
</table>
<!-- body_eof //-->
</body>
</html>
