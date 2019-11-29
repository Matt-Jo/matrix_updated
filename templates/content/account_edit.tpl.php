<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">My Account Information</div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear: both;"></div>

	<div class="main rounded-corners">
		<div class="grid grid-pad edit_form">
			<form name="account_edit" action="/account_edit.php" method="post" onsubmit="return check_form(account_edit);">
				<input type="hidden" name="action" value="process">
				<table border="0" width="100%" cellspacing="0" cellpadding="8">
					<?php if ($messageStack->size('account_edit') > 0) { ?>
					<tr>
						<td><?= $messageStack->output('account_edit'); ?></td>
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
												<td class="main"><b><?= MY_ACCOUNT_TITLE; ?></b></td>
												<td class="inputRequirement" align="right">* Required information</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td>
										<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
											<tr class="infoBoxContents">
												<td>
													<table border="0" cellspacing="2" cellpadding="2">
														<tr>
															<td class="main">First Name:</td>
															<td class="main"><input type="text" name="firstname" value="<?= $account['customers_firstname']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
														</tr>
														<tr>
															<td class="main">Last Name:</td>
															<td class="main"><input type="text" name="lastname" value="<?= $account['customers_lastname']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
														</tr>
														<tr>
															<td class="main">E-Mail Address:</td>
															<td class="main"><input type="text" name="email_address" value="<?= $account['customers_email_address']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
														</tr>
														<?php if (empty($_SESSION['customer_extra_login_id'])) { ?>
														<tr>
															<td class="main">Phone:</td>
															<td class="main"><input type="text" name="telephone" value="<?= $account['customers_telephone']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
														</tr>
														<tr>
															<td class="main">Fax:</td>
															<td class="main"><input type="text" name="fax" value="<?= $account['customers_fax']; ?>">&nbsp;</td>
														</tr>
															<?php if (empty($account['customer_segment']) || in_array($account['customer_segment'], ['IN', 'EU', 'RS'])) { ?>
														<tr>
															<td class="main" colspan="2">Have you changed how you use our products? <span class="inputRequirement">*</span></td>
														</tr>
														<tr>
															<td class="main" colspan="2">
																<style>
																	.option-list li { list-style-type:none; }
																</style>
																<ul class="option-list">
																	<li><input type="radio" name="customer_segment_id" value="<?= ck_customer2::$customer_segment_map['IN']; ?>" id="cs-in" <?= $account['customer_segment']=='IN'?'checked':''; ?> required> <label for="cs-in">for personal use</label></li>
																	<li><input type="radio" name="customer_segment_id" value="<?= ck_customer2::$customer_segment_map['EU']; ?>" id="cs-eu" <?= $account['customer_segment']=='EU'?'checked':''; ?> required> <label for="cs-eu">for installation in my business/where I work</label></li>
																	<li>
																		<input type="radio" name="customer_segment_id" value="<?= ck_customer2::$customer_segment_map['RS']; ?>" id="cs-rs" <?= $account['customer_segment']=='RS'?'checked':''; ?> required> <label for="cs-rs">for installation/management of a client or resale to a customer</label></li>
																	<li>
																		<input type="radio" name="customer_segment_id" value="<?= ck_customer2::$customer_segment_map['ST']; ?>" id="cs-st" <?= $account['customer_segment']=='ST'?'checked':''; ?> required>
																		<label for="cs-st">for furthering my IT education</label>
																	</li>
																</ul>
															</td>
														</tr>
															<?php }
														} ?>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td><?= tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
					</tr>
					<tr>
						<td>
							<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
								<tr class="infoBoxContents">
									<td>
										<table border="0" width="100%" cellspacing="0" cellpadding="2">
											<tr>
												<td width="10"><?= tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
												<td><a href="/account.php"><img src="/templates/Pixame_v1/images/buttons/english/button_back.gif" border="0" alt="Back" title="Back"></a></td>
												<td align="right"><input type="image" src="/templates/Pixame_v1/images/buttons/english/button_continue.gif" alt="Continue" title="Continue"></td>
												<td width="10"><?= tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td class="main"><?= tep_image(DIR_WS_IMAGES.'arrow_green.gif'); ?> <a href="/address_book.php"> Manage My Address Book</a></td>
					</tr>
					<tr>
						<td class="main"><?= tep_image(DIR_WS_IMAGES.'arrow_green.gif'); ?> <a href="/account_password.php">Change My Password</a></td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</div>
