<style>
	.moduleRow { }
	.moduleRowOver { background-color: #D7E9F7; cursor: pointer;}
	.moduleRowSelected { background-color: #d3d3d3; }
</style>
<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">My Personal Address Book</div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear: both;"></div>

	<div class="main rounded-corners">
		<div class="grid grid-pad edit_form">
			<table border="0" width="100%" cellspacing="0" cellpadding="8">
				<?php if ($messageStack->size('addressbook') > 0) { ?>
				<tr>
					<td><?php echo $messageStack->output('addressbook'); ?></td>
				</tr>
				<tr>
					<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
				</tr>
				<?php } ?>
	
				<tr>
					<td><?php echo tep_draw_separator('pixel_trans.gif', '100%', '10'); ?></td>
				</tr>
				<tr>
					<td class="main"><b><?php echo ADDRESS_BOOK_TITLE; ?></b></td>
				</tr>
				<tr>
					<td>
						<?php $addresses_list = prepared_query::fetch("select address_book_id, entry_firstname as firstname, entry_lastname as lastname, entry_company as company, entry_street_address as street_address, entry_suburb as suburb, entry_city as city, entry_postcode as postcode, entry_state as state, entry_zone_id as zone_id, entry_telephone as telephone, entry_country_id as country_id from address_book where customers_id = :customers_id order by firstname, lastname", cardinality::SET, [':customers_id' => $_SESSION['customer_id']]);

						foreach ($addresses_list as $addresses) {
							$format_id = prepared_query::fetch('SELECT address_format_id FROM countries WHERE countries_id = :countries_id', cardinality::SINGLE, [':countries_id' => $addresses['country_id']]);
							if (empty($format_id)) $format_id = 1; ?>
						<div class="row">
							<?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?>
							<div class="grid">
								
									<div class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onClick="document.location.href='<?php echo '/address_book_process.php?edit='.$addresses['address_book_id']; ?>'">
										<div class="col-7-12 alignLeftMd"><b><?php echo tep_output_string_protected($addresses['firstname'].' '.$addresses['lastname']); ?></b><?php if ($addresses['address_book_id'] == @$customer_default_address_id) echo '&nbsp;<small><i>'.PRIMARY_ADDRESS.'</i></small>'; ?></div>
										<div class="main alignRightMd col-5-12"><a href="/address_book_process.php?edit=<?= $addresses['address_book_id']; ?>"><img src="/templates/Pixame_v1/images/buttons/english/small_edit.gif" border="0" alt="Edit" title="Edit"></a> <a href="/address_book_process.php?delete=<?= $addresses['address_book_id']; ?>"><img src="/templates/Pixame_v1/images/buttons/english/button_delete.gif" border="0" alt="Delete" title="Delete"></a></div>
									</div>
									
									<div class="alignLeftMd"><?php echo tep_address_format($format_id, $addresses, true, ' ', '<br>'); ?></div>
							</div>
							<?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?>
						</div>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<td>
						<table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBox">
							<tr class="infoBoxContents">
								<td>
									<table border="0" width="100%" cellspacing="0" cellpadding="2">
										<tr>
											<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
											<td class="smallText"><a href="/account.php"><img src="/templates/Pixame_v1/images/buttons/english/button_back.gif" border="0" alt="Back" title="Back"></a></td>
											<td class="smallText" align="right"><a href="/address_book_process.php"><img src="/templates/Pixame_v1/images/buttons/english/button_add_address.gif" border="0" alt="Add Address" title="Add Address"></a></td>
											<td width="10"><?php echo tep_draw_separator('pixel_trans.gif', '10', '1'); ?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="smallText"><?php echo PRIMARY_ADDRESS_DESCRIPTION; ?></td>
				</tr>
			</table>
		</div>
	</div>
</div>
