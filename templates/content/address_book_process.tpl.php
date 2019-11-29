<?php
if (isset($_GET['edit'])) $header_text = 'Update Address Book Entry';
elseif (isset($_GET['delete'])) $header_text = 'Delete Address Book Entry';
else $header_text = 'New Address Book Entry';
?>
<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle"><?= $header_text; ?></div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear: both;"></div>

	<div class="main rounded-corners">
		<div class="grid grid-pad edit_form">
			<?php if (!isset($_GET['delete'])) { ?>
			<form name="addressbook" action="/address_book_process.php<?= isset($_GET['edit'])?'?edit='.$_GET['edit']:''; ?>" method="post" onsubmit="return check_form(addressbook);">
			<?php } ?>
		
				<table border="0" width="100%" cellspacing="0" cellpadding="8">
		
		<?php if ($messageStack->size('addressbook') > 0) { ?>
			<tr>
				<td><?php echo $messageStack->output('addressbook'); ?></td>
			</tr>
			<tr>
				<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
			</tr>
		<?php }
		
		 if (isset($_GET['delete'])) { ?>
			<tr>
				<td class="main"><b>Delete Address</b></td>
			</tr>
			<tr>
				<td>
				<table>
				<tr>
					<td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
					<td class="main" width="100%" valign="top">Are you sure that you want to delete the following address?</td>
				</tr>
				</table>
				</td>
				</tr>
			<tr>
				<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
			</tr>
		
			<tr>
				<td>
					<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
					<tr class="infoBoxContents">
						<td>
		
										<table border="0" cellspacing="0" cellpadding="2">
										<tr>
											<td class="main" width="30%" align="center" valign="top"><b>Selected Address</b><br><?php echo tep_image(DIR_WS_IMAGES.'arrow_south_east.gif'); ?></td>
											<td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
											<td class="main" width="70%" valign="top">
												<?php $address = new ck_address2($_GET['delete']);
												$addr = $address->get_legacy_array();
												echo tep_address_format($addr['format_id'], $addr, TRUE, ' ', '<br>'); ?>
											</td>
											<td><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
										</tr>
										</table>
		
							</td>
						</tr>
					</table>
				</td>
			</tr>
		
			<tr>
				<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
			</tr>
			<tr>
				<td>
					<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
					<tr class="infoBoxContents">
						<td>
							<table border="0" width="100%" cellspacing="0" cellpadding="2">
							<tr>
								<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
								<td><a href="/address_book.php"><img src="/templates/Pixame_v1/images/buttons/english/button_back.gif" border="0" alt="Back" title="Back"></a></td>
								<td align="right"><a href="/address_book_process.php?delete=<?= $_GET['delete']; ?>&action=deleteconfirm"><img src="/templates/Pixame_v1/images/buttons/english/button_delete.gif" border="0" alt="Delete" title="Delete"></a></td>
								<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
								</tr>
							</table>
						</td>
						</tr>
					</table>
				</td>
			</tr>
		
		<?php
		 } else {
		?>
			<tr>
				<td>
					<?php if (!isset($process)) $process = false; ?>
					<table border="0" width="100%" cellspacing="0" cellpadding="2">
						<tr>
							<td>
								<table border="0" width="100%" cellspacing="0" cellpadding="2">
									<tr>
										<?php if (isset($_GET['edit']) && is_numeric($_GET['edit'])) { ?>
										<td class="main"><b>Edit Address Book Entry</b></td>
										<?php }
										else { ?>
										<td class="main"><b>Add New Address</b></td>
										<?php } ?>
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
													<td class="main"><input type="text" name="firstname" value="<?= @$entry['entry_firstname']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
												</tr>
												<tr>
													<td class="main">Last Name:</td>
													<td class="main"><input type="text" name="lastname" value="<?= @$entry['entry_lastname']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
												</tr>
												<tr>
													<td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
												</tr>
												<?php if (ACCOUNT_COMPANY == 'true') { ?>
												<tr>
													<td class="main">Company:</td>
													<td class="main"><input type="text" name="company" value="<?= @$entry['entry_company']; ?>">&nbsp;</td>
												</tr>
												<tr>
													<td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
												</tr>
												<?php } ?>
												<tr>
													<td class="main">Address:</td>
													<td class="main"><input type="text" name="street_address" value="<?= @$entry['entry_street_address']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
												</tr>
												<tr>
													<td class="main">Suite/Unit:</td>
													<td class="main"><input type="text" name="suburb" value="<?= @$entry['entry_suburb']; ?>">&nbsp;</td>
												</tr>
												<tr>
													<td class="main">City:</td>
													<td class="main"><input type="text" name="city" value="<?= @$entry['entry_city']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
												</tr>
												<tr>
													<td class="main">State:</td>
													<td class="main">
														<?php
														// +Country-State Selector
														$zones_array = array();
														$zones_list = prepared_query::fetch("select zone_name, zone_id from zones where zone_country_id = :country_id order by zone_name", cardinality::SET, [':country_id' => $entry['entry_country_id']]);
														foreach ($zones_list as $zones_values) {
															$zones_array[] = array('id' => $zones_values['zone_id'], 'text' => $zones_values['zone_name']);
														}
														if (count($zones_array) > 0) {
															echo tep_draw_pull_down_menu('zone_id', $zones_array, @$entry['entry_zone_id']);
															echo '<input type="hidden" name="state" value="">';
														}
														else {
															echo '<input type="text" name="state" value="'.@$entry['entry_state'].'">';
														}
														// -Country-State Selector

														echo '&nbsp;<span class="inputRequirement">*</span>'; ?>
													</td>
												</tr>
												<tr>
													<td class="main">Zip Code:</td>
													<td class="main"><input type="text" name="postcode" value="<?= @$entry['entry_postcode']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
												</tr>
												<tr>
													<td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
												</tr>
												<tr>
													<td class="main">Phone:</td>
													<td class="main"><input type="text" name="telephone" value="<?= @$entry['entry_telephone']; ?>">&nbsp;<span class="inputRequirement">*</span></td>
												</tr>
												<tr>
													<td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
												</tr>
												<tr>
													<td class="main">Country:</td>
													<?php // +Country-State Selector ?>
													<td class="main">
														<?php echo tep_get_country_list('country', @$entry['entry_country_id'], 'onChange="return refresh_form(addressbook);"').'&nbsp;<span class="inputRequirement">*</span>'; ?>
													</td>
													<?php // -Country-State Selector ?>
												</tr>
												<?php if ((isset($_GET['edit']) && (@$customer_default_address_id != $_GET['edit'])) || (isset($_GET['edit']) == false) ) { ?>
												<tr>
													<td colspan="2"><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
												</tr>
												<tr>
													<td colspan="2" class="main"><input type="checkbox" name="primary" value="on" id="primary"> Set as primary address.</td>
												</tr>
												<?php } ?>
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
				<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
			</tr>
		<?php
			if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
		?>
		
		 <tr>
				<td>
					<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
					<tr class="infoBoxContents">
						<td>
						<table border="0" width="100%" cellspacing="0" cellpadding="2">
							<tr>
								<td width="10"><?= tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
								<td><a href="/address_book.php"><img src="/templates/Pixame_v1/images/buttons/english/button_back.gif" border="0" alt="Back" title="Back"></a></td>
								<td align="right"><input type="hidden" name="action" value="update"><input type="hidden" name="edit" value="<?= $_GET['edit']; ?>"><input type="image" src="/templates/Pixame_v1/images/buttons/english/button_update.gif" alt="Update" title="Update"></td>
								<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
							</tr>
							</table>
						</td>
						</tr>
					</table>
				</td>
				</tr>
		
		<?php
			} else {
		?>
		
			<tr>
				<td>
					<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
					<tr class="infoBoxContents">
						<td>
						<table border="0" width="100%" cellspacing="0" cellpadding="2">
							<tr>
							<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
							<td><a href="/address_book.php"><img src="/templates/Pixame_v1/images/buttons/english/button_back.gif" border="0" alt="Back" title="Back"></a></td>
							<td align="right"><input type="hidden" name="action" value="process"><input type="hidden" name="edit" value="<?= @$_GET['edit']; ?>"><input type="image" src="/templates/Pixame_v1/images/buttons/english/button_continue.gif" alt="Continue" title="Continue"></td>
							<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
							</tr>
						</table>
						</td>
					</tr>
					</table>
				</td>
				</tr>
		
		
		<?php
			}
		 }
		?>
			<?php if (!isset($_GET['delete'])) echo '</form>'; ?>
			</table>

		</div>
	</div>
</div>
