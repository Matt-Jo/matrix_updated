<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">My Password</div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear: both;"></div>

	<div class="main rounded-corners">
		<div class="grid grid-pad edit_form">
			<form name="account_password" action="/account_password.php" method="post" onsubmit="return check_form(account_password);">
				<input type="hidden" name="action" value="process">
				<table border="0" width="100%" cellspacing="0" cellpadding="8">
					<?php if ($messageStack->size('account_password') > 0) { ?>
					<tr>
						<td><?= $messageStack->output('account_password'); ?></td>
					</tr>
					<tr>
						<td><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
					</tr>
					<?php } ?>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
								<tr>
									<td>
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td class="main"><b>Change Password</b></td>
												<td class="inputRequirement" align="right">* Required Information</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td>
										<table border="0" width="100%" cellspacing="2" cellpadding="2" class="infoBox">
											<tr class="infoBoxContents">
												<td class="main">Current Password:</td>
												<td class="main">
													<input type="password" name="password_current">
													<span class="inputRequirement">*</span>
												</td>
											</tr>
											<tr class="infoBoxContents">
												<td colspan="2"><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
											</tr>
											<tr class="infoBoxContents">
												<td class="main">New Password:</td>
												<td class="main">
													<input type="password" name="password_new">
													<span class="inputRequirement">*</span>
												</td>
											</tr>
											<tr class="infoBoxContents">
												<td class="main">Confirm Password:</td>
												<td class="main">
													<input type="password" name="password_confirmation">
													<span class="inputRequirement">*</span>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
								<tr class="infoBoxContents">
									<td width="10"><?= tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
									<td><a href="/account.php"><img src="templates/Pixame_v1/images/buttons/english/button_back.gif" border="0" alt="Back" title="Back"></a></td>
									<td align="right"><input type="image" src="templates/Pixame_v1/images/buttons/english/button_continue.gif" alt="Continue" title=" Continue "></td>
									<td width="10"><?= tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</div>
