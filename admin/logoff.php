<?php
 require('includes/application_top.php');

 require(DIR_WS_LANGUAGES.$_SESSION['language'].'/'.FILENAME_LOGOFF);

 unset($_SESSION['login_id']);
 unset($_SESSION['login_firstname']);
 unset($_SESSION['login_groups_id']);
 unset($_SESSION['customer_cards']);
 CK\fn::redirect_and_exit('/admin/login.php');
 exit;

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<style type="text/css"><!--
a { color:#080381; text-decoration:none; }
a:hover { color: #000000; color:#aabbdd; text-decoration:underline; }
a.text:link, a.text:visited { color: #000000; text-decoration: none; }
a:text:hover { color: #000000; text-decoration: underline; }
A.login_heading:link, A.login_heading:visited { underline; font-family: Verdana, Arial, sans-serif; font-size: 12px; color: #F0F0FF;}
A.login_heading: hover { color: #F0F0FF;}
.heading { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 20px; font-weight: bold; line-height: 1.5; color: #D3DBFF; }
.text { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; font-weight: bold; line-height: 1.5; color: #000000; }
.smallText { font-family: Verdana, Arial, sans-serif; font-size: 10px; }
.login_heading { font-family: Verdana, Arial, sans-serif; font-size: 12px; color: #ffffff;}
.login { font-family: Verdana, Arial, sans-serif; font-size: 12px; color: #000000;}
//--></style>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<table border="0" width="600" height="100%" cellspacing="0" cellpadding="0" align="center" valign="middle">
 <tr>
	<td><table border="0" width="600" cellspacing="0" cellpadding="1" align="center" valign="middle">
	<tr bgcolor="#000000">
		<td><table border="0" width="600" cellspacing="0" cellpadding="0">
			<tr> <td colspan="2" valign="top" height="100%">
 <table border="0" height="100%" cellspacing="0" cellpadding="0" bordercolor="#990000">
<tr><td colspan="3"> <table border=0 cellpadding=0 cellspacing=0 > <tr> <td> <?php echo '<a href="#">'.tep_image(DIR_WS_IMAGES.'logo-banner_02.gif', 'Get the latest Loaded6 version here').'</a>'; ?></td>

		<td background="images/logo-banner_03.gif" width="495" align="center" valign="middle">&nbsp;

			</td>
		</tr> </table></td></tr>
		<tr bgcolor="#000000">
			<td colspan="2" align="center" valign="middle">
							<table width="280" border="0" cellspacing="0" cellpadding="2">
							<tr>
								<td class="login_heading" valign="top"><b><?php echo HEADING_TITLE; ?></b></td>
							</tr>
							<tr>
								<td class="login_heading"><?php echo TEXT_MAIN; ?></td>
							</tr>
							<tr>

								<td class="login_heading" align="right"><?php echo '<a class="login_heading" href="/admin/login.php">'.tep_image_button('button_back.gif', IMAGE_BACK).'</a>'; ?></td>

							</tr>
							<tr>
								<td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '30'); ?></td>
							</tr>
							</table>
			</td>
		</tr>
		</table></td>
	</tr>
	<tr>
		<td></td>
	</tr>
	</table></td>
 </tr>
</table>

</body>

</html>
