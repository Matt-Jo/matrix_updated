<?php $product_info_values = prepared_query::fetch("select p.faqdesk_id, pd.faqdesk_question, pd.faqdesk_answer_long, pd.faqdesk_answer_short, p.faqdesk_image, p.faqdesk_image_two, p.faqdesk_image_three, pd.faqdesk_extra_url, pd.faqdesk_extra_viewed, p.faqdesk_date_added, p.faqdesk_date_available from faqdesk p, faqdesk_description pd where p.faqdesk_id = :faqdesk_id and pd.faqdesk_id = :faqdesk_id and pd.language_id = 1", cardinality::ROW, [':faqdesk_id' => @$_GET['faqdesk_id']]);

// product not found in database
if (empty($product_info_values)) { ?>
<table border="0" width="100%" cellspacing="3" cellpadding="3">
	<tr>
		<td class="main"><br><?= TEXT_NEWS_NOT_FOUND; ?></td>
	</tr>
	<tr>
		<td align="right">
			<br><a href="/"><?= tep_image_button('button_continue.gif', 'Continue'); ?></a>
		</td>
	</tr>
</table>
<?php }
else {
	prepared_query::execute("update faqdesk_description set faqdesk_extra_viewed = faqdesk_extra_viewed+1 where faqdesk_id = :faqdesk_id and language_id = 1", [':faqdesk_id' => $_GET['faqdesk_id']]); ?>
<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle"><?= TEXT_FAQDESK_HEADING;?></div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear: both;"></div>

	<div class="main rounded-corners">
        <div class="grid grid-pad">
			
			<div class="col-1-1">
				<table border="0" width="100%" cellspacing="0" cellpadding="8">
					<tr>
						<td>
							<table width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td class="tableHeading"><font color="#d22842"><?= $product_info_values['faqdesk_question']; ?></font></td>
								</tr>
							</table>

							<table border="0" width="100%" cellspacing="3" cellpadding="3">
								<tr>
									<td width="100%" class="main" valign="top">
										<?= stripslashes($product_info_values['faqdesk_answer_long']); ?>
									</td>
									<td width="" class="main" valign="top" align="center"></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div class="clearfix"></div>
		</div>
	</div>
</div>
<?php } ?>
