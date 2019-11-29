<?php
function sbs_get_zone_name($country_id, $zone_id) {
	$zone = prepared_query::fetch('SELECT zone_name FROM zones WHERE zone_country_id = ? AND zone_id = ?', cardinality::ROW, array($country_id, $zone_id));
	if (!empty($zone)) {
		return $zone['zone_name'];
	}
}

// Returns an array with countries
// TABLES: countries
function sbs_get_countries($countries_id = '', $with_iso_codes = false) {
	$countries_array = array();
	if (!empty($countries_id)) {
		if (!empty($with_iso_codes)) {
			$countries_array = prepared_query::fetch("select countries_name, countries_iso_code_2, countries_iso_code_3 from countries where countries_id = :countries_id order by countries_name", cardinality::ROW, [':countries_id' => $countries_id]);
		}
		else {
			$countries_array = prepared_query::fetch("select countries_name from countries where countries_id = :countries_id", cardinality::ROW, [':countries_id' => $countries_id]);
		}
	}
	else {
		$countries_array = prepared_query::fetch("select countries_id, countries_name from countries order by countries_name", cardinality::SET);
	}

	return $countries_array;
}

function sbs_get_country_list($name, $selected = '', $parameters = '') {
	$countries_array = array(array('id' => '', 'text' => 'Please Select'));
	$countries = sbs_get_countries();
	$size = sizeof($countries);
	for ($i=0; $i<$size; $i++) {
		$countries_array[] = array('id' => $countries[$i]['countries_id'], 'text' => $countries[$i]['countries_name']);
	}

	return tep_draw_pull_down_menu($name, $countries_array, $selected, $parameters);
}
?>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
	<tr>
		<td class="main">
			<table border="0" width="100%" cellspacing="0" cellpadding="2" class="formArea">
				<tr>
					<td class="main">
						<table border="0" cellspacing="0" cellpadding="2">
							<tr>
								<td class="formAreaTitle">&nbsp;</td>
							</tr>
							<tr>
								<td class="formAreaTitle">Company Information</td>
							</tr>
							<tr>
								<td class="formAreaTitle">&nbsp;</td>
							</tr>
							<tr>
								<td class="main">&nbsp;Company Name</td>
								<td class="main">
									&nbsp;
									<?php if (!empty($error)) {
										if (!empty($entry_firstname_error)) {
											echo tep_draw_input_field('vendors_company_name').'&nbsp;'.ENTRY_FIRST_NAME_ERROR;
										}
										else {
											echo $_REQUEST['vendors_company_name'].tep_draw_hidden_field('vendors_company_name');
										}
									}
									else {
										echo tep_draw_input_field('vendors_company_name', @$account['vendors_company_name']).'&nbsp;'.ENTRY_FIRST_NAME_TEXT;
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main">&nbsp;<?php echo ENTRY_STREET_ADDRESS; ?></td>
								<td class="main">
									&nbsp;
									<?php if (!empty($error)) {
										if (!empty($entry_street_address_error)) {
											echo tep_draw_input_field('street_address').'&nbsp;'.ENTRY_STREET_ADDRESS_ERROR;
										}
										else {
											echo $street_address.tep_draw_hidden_field('street_address');
										}
									}
									else {
										echo tep_draw_input_field('street_address', @$account['entry_street_address']).'&nbsp;'.ENTRY_STREET_ADDRESS_TEXT;
									} ?>
								</td>
							</tr>
							<?php if (ACCOUNT_SUBURB == 'true') { ?>
							<tr>
								<td class="main">&nbsp;<?php echo ENTRY_SUBURB; ?></td>
								<td class="main">
									&nbsp;
									<?php if (!empty($error)) {
										if (!empty($entry_suburb_error)) {
											echo tep_draw_input_field('suburb').'&nbsp;'.ENTRY_SUBURB_ERROR;
										}
										else {
											echo $suburb.tep_draw_hidden_field('suburb');
										}
									}
									else {
										echo tep_draw_input_field('suburb', @$account['entry_suburb']).'&nbsp;'.ENTRY_SUBURB_TEXT;
									} ?>
								</td>
							</tr>
							<?php } ?>
							<tr>
								<td class="main">&nbsp;<?php echo ENTRY_CITY; ?></td>
								<td class="main">
									&nbsp;
									<?php if (!empty($error)) {
										if (!empty($entry_city_error)) {
											echo tep_draw_input_field('city').'&nbsp;'.ENTRY_CITY_ERROR;
										}
										else {
											echo $city.tep_draw_hidden_field('city');
										}
									}
									else {
										echo tep_draw_input_field('city', @$account['entry_city']).'&nbsp;'.ENTRY_CITY_TEXT;
									} ?>
								</td>
							</tr>
							<?php if (ACCOUNT_STATE == 'true') { ?>
							<tr>
								<td class="main">&nbsp;<?php echo ENTRY_STATE; ?></td>
								<td class="main">
									&nbsp;
									<?php $state = sbs_get_zone_name(@$country, @$zone_id);
									if (!empty($error)) {
										if (!empty($entry_state_error)) {
											if (!empty($entry_state_has_zones)) {
												$zones_array = array();
												$zones_query = prepared_query::fetch("select zone_name from zones where zone_country_id = :countries_id order by zone_name", cardinality::COLUMN, [':countries_id' => $country]);
												foreach ($zones_query as $zones_values) {
													$zones_array[] = array('id' => $zones_values, 'text' => $zones_values);
												}
												echo tep_draw_pull_down_menu('state', $zones_array).'&nbsp;'.ENTRY_STATE_ERROR;
											}
											else {
												echo tep_draw_input_field('state').'&nbsp;'.ENTRY_STATE_ERROR;
											}
										}
										else {
											echo $state.tep_draw_hidden_field('zone_id').tep_draw_hidden_field('state');
										}
									}
									else {
										echo tep_draw_input_field('state', sbs_get_zone_name(@$account['entry_country_id'], @$account['entry_zone_id'])).'&nbsp;'.ENTRY_STATE_TEXT;
									} ?>
								</td>
							</tr>
							<?php } ?>
							<tr>
								<td class="main">&nbsp;<?php echo ENTRY_POST_CODE; ?></td>
								<td class="main">
									&nbsp;
									<?php if (!empty($error)) {
										if (!empty($entry_post_code_error)) {
											echo tep_draw_input_field('postcode').'&nbsp;'.ENTRY_POST_CODE_ERROR;
										}
										else {
											echo $postcode.tep_draw_hidden_field('postcode');
										}
									}
									else {
										echo tep_draw_input_field('postcode', @$account['entry_postcode']).'&nbsp;'.ENTRY_POST_CODE_TEXT;
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main">&nbsp;<?php echo ENTRY_COUNTRY; ?></td>
								<td class="main">
									&nbsp;
									<?php $account['entry_country_id'] = STORE_COUNTRY;
									if (!empty($error)) {
										if (!empty($entry_country_error)) {
											echo sbs_get_country_list('country').'&nbsp;'.ENTRY_COUNTRY_ERROR;
										}
										else {
											echo ck_address2::legacy_get_country_field($country, 'countries_name').tep_draw_hidden_field('country');
										}
									}
									else {
										echo sbs_get_country_list('country', $account['entry_country_id']).'&nbsp;'.ENTRY_COUNTRY_TEXT;
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main">&nbsp;<?php echo ENTRY_TELEPHONE_NUMBER; ?></td>
								<td class="main">
									&nbsp;
									<?php if (!empty($error)) {
										if (!empty($entry_telephone_error)) {
											echo tep_draw_input_field('telephone').'&nbsp;'.ENTRY_TELEPHONE_NUMBER_ERROR;
										}
										else {
											echo $telephone.tep_draw_hidden_field('telephone');
										}
									}
									else {
										echo tep_draw_input_field('telephone', @$account['vendors_telephone']).'&nbsp;'.ENTRY_TELEPHONE_NUMBER_TEXT;
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main">&nbsp;<?php echo ENTRY_FAX_NUMBER; ?></td>
								<td class="main">
									&nbsp;
									<?php if (!empty($processed)) {
										echo $fax.tep_draw_hidden_field('fax');
									}
									else {
										echo tep_draw_input_field('fax', @$account['vendors_fax']).'&nbsp;'.ENTRY_FAX_NUMBER_TEXT;
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main"><?php echo '&nbsp;Web Address:'; ?></td>
								<td class="main">
									&nbsp;&nbsp;
									<?php if (!empty($processed) && $processed == true) {
										echo @$cInfo->company_web_address.tep_draw_hidden_field('company_web_address');
									}
									else {
										echo tep_draw_input_field('company_web_address', @$cInfo->company_web_address, 'maxlength="96"');
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main">&nbsp;Email PO's to:</td>
								<td class="main">
									&nbsp;
									<?php if (!empty($error)) {
										if (!empty($entry_email_address_error)) { ?>
									<input type="text" name="email_address" autocomplete="off" value="<?= @$email_address; ?>">&nbsp;<?= ENTRY_EMAIL_ADDRESS_ERROR; ?>
										<?php }
										elseif (!empty($entry_email_address_check_error)) { ?>
									<input type="text" name="email_address" autocomplete="off" value="<?= @$email_address; ?>">&nbsp;<?= ENTRY_EMAIL_ADDRESS_CHECK_ERROR; ?>
										<?php }
										elseif (!empty($entry_email_address_exists)) { ?>
									<input type="text" name="email_address" autocomplete="off" value="<?= @$email_address; ?>">&nbsp;<?= ENTRY_EMAIL_ADDRESS_ERROR_EXISTS; ?>
										<?php }
										else {
											echo $email_address.tep_draw_hidden_field('email_address');
										}
									}
									else { ?>
										<input type="text" name="email_address" autocomplete="off" value="<?= @$email_address; ?>">&nbsp;<?= ENTRY_EMAIL_ADDRESS_TEXT; ?>
									<?php } ?>
								</td>
							</tr>
							<tr>
								<td class="main"><?php echo '&nbsp;Payment Terms:'; ?></td>
								<td class="main">
									&nbsp;&nbsp;
									<?php if (!empty($processed) && $processed == true) {
										echo @$cInfo->company_payment_terms.tep_draw_hidden_field('company_payment_terms');
									}
									else {
										echo tep_draw_pull_down_menu('company_payment_terms', tep_get_po_terms(), @$cInfo->company_payment_terms);
									} ?>
								</td>
							</tr>
							<tr>
								<td valign="top" class="main">&nbsp;Vendor Notes:</td>
								<td class="main">
									&nbsp;&nbsp;
									<?php if (!empty($processed) && $processed == true) {
										echo @$cInfo->vendors_notes.tep_draw_hidden_field('vendors_notes');
									}
									else {
										echo tep_draw_textarea_field('vendors_notes', 'soft', '75', '5', (@$cInfo->vendors_notes));
									} ?>
								</td>
							</tr>
							<tr>
								<td class="formAreaTitle"><?php echo '&nbsp;'; ?></td>
							</tr>
							<tr>
								<td class="formAreaTitle"><?php echo 'Contact Information'; ?></td>
							</tr>
							<tr>
								<td class="formAreaTitle"><?php echo '&nbsp;'; ?></td>
							</tr>
							<tr>
								<td class="main"><?php echo '&nbsp;Contact Name:'; ?></td>
								<td class="main">
									&nbsp;&nbsp;
									<?php if (!empty($processed) && $processed == true) {
										echo @$cInfo->company_account_contact_name.tep_draw_hidden_field('company_account_contact_name');
									}
									else {
										echo tep_draw_input_field('company_account_contact_name', @$cInfo->company_account_contact_name, 'maxlength="32"');
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main"><?php echo '&nbsp;Contact Phone Number:'; ?></td>
								<td class="main">
									&nbsp;&nbsp;
									<?php if (!empty($processed) && $processed == true) {
										echo @$cInfo->company_account_contact_phone_number.tep_draw_hidden_field('company_account_contact_phone_number');
									}
									else {
										echo tep_draw_input_field('company_account_contact_phone_number', @$cInfo->company_account_contact_phone_number, 'maxlength="32"');
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main"><?php echo '&nbsp;Contact Email:'; ?></td>
								<td class="main">
									&nbsp;&nbsp;
									<?php if (!empty($processed) && $processed == true) {
										echo @$cInfo->company_account_contact_email.tep_draw_hidden_field('company_account_contact_email');
									}
									else {
										echo tep_draw_input_field('company_account_contact_email', @$cInfo->company_account_contact_email, 'maxlength="32"');
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main"><?php echo '&nbsp;AIM Screen Name:'; ?></td>
								<td class="main">
									&nbsp;&nbsp;
									<?php if (!empty($processed) && $processed == true) {
										echo @$cInfo->aim_screenname.tep_draw_hidden_field('aim_screenname');
									}
									else {
										echo tep_draw_input_field('aim_screenname', @$cInfo->aim_screenname, 'maxlength="32"');
									} ?>
								</td>
							</tr>
							<tr>
								<td class="main"><?php echo '&nbsp;MSN Screen Name:'; ?></td>
								<td class="main">
									&nbsp;&nbsp;
									<?php if (!empty($processed) && $processed == true) {
										echo @$cInfo->msn_screenname.tep_draw_hidden_field('msn_screenname');
									}
									else {
										echo tep_draw_input_field('msn_screenname', @$cInfo->msn_screenname, 'maxlength="32"');
									} ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
