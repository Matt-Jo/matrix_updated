<?php
/*
	Module: Information Pages Unlimited
	File date: 2003/03/02
	Based on the FAQ script of adgrafics
	Adjusted by Joeri Stegeman (joeri210 at yahoo.com), The Netherlands

	osCommerce, Open Source E-Commerce Solutions
	http://www.oscommerce.com

	Released under the GNU General Public License
*/
?>
<tr class="pageHeading">
	<td><?= $title; ?></td>
</tr>
<tr class="dataTableRow">
	<td>
		<font color="red">
			Queue List:
			<?php $data = browse_information();
			$no = 1;
			if (sizeof($data) > 0) {
				foreach($data as $key => $val) {
					echo $val['v_order'].', ';
					$no++;
				}
			} ?>
		</font>
	</td>
</tr>
<tr>
	<td>
		<table border="0" cellpadding="0" cellspacing="2">
			<tr>
				<td>Queue:</td>
				<td>
					<?php if (!empty($edit['v_order'])) $no = $edit['v_order'];
					echo tep_draw_input_field('v_order', "$no", 'size=3 maxlength=4'); ?>

					Visible
					<?php if (!empty($edit['visible']) && $edit['visible'] == 1) echo tep_image(DIR_WS_ICONS.'icon_status_green.gif', INFORMATION_ID_ACTIVE);
					else echo tep_image(DIR_WS_ICONS.'icon_status_red.gif', INFORMATION_ID_DEACTIVE);

					$checked = '';
					if (!empty($edit['visible'])) $checked= "checked";
					echo tep_draw_checkbox_field('visible', '1', "$checked"); ?>
					( To Do visible )
				</td>
			</tr>
			<tr>
				<td>Sitewide Header:</td>
				<td>
					<?php $checked = '';
					if (!empty($edit['sitewide_header'])) $checked = "checked";
					echo tep_draw_checkbox_field('sitewide_header', '1', "$checked"); ?>
				</td>
			</tr>
			<tr>
				<td>Title:</td>
				<td><?= tep_draw_input_field('info_title', @$edit['info_title'], 'maxlength=255'); ?></td>
			</tr>
			<tr>
				<td>Description:</td>
				<td>
					<?= tep_draw_textarea_field('description', '', '60', '10', @$edit['description']); ?>
				</td>
				<?php if (HTML_AREA_WYSIWYG_DISABLE != 'Disable') { ?>
				<script language="JavaScript1.2" defer>
					// MaxiDVD Added WYSIWYG HTML Area Box + Admin Function v1.7 - 2.2 MS2 HTML Email HTML - <body>
					var config = new Object(); // create new config object
					config.width = "<?= EMAIL_AREA_WYSIWYG_WIDTH; ?>px";
					config.height = "<?= EMAIL_AREA_WYSIWYG_HEIGHT; ?>px";
					config.bodyStyle = 'background-color: <?= HTML_AREA_WYSIWYG_BG_COLOUR; ?>; font-family: "<?= HTML_AREA_WYSIWYG_FONT_TYPE; ?>"; color: <?= HTML_AREA_WYSIWYG_FONT_COLOUR; ?>; font-size: <?= HTML_AREA_WYSIWYG_FONT_SIZE; ?>pt;';
					config.debug = <?= HTML_AREA_WYSIWYG_DEBUG; ?>;
					editor_generate('description',config);
					// MaxiDVD Added WYSIWYG HTML Area Box + Admin Function v1.7 - 2.2 MS2 HTML Email HTML - <body>
				</script>
				<?php } ?>
			</tr>
			<tr>
				<td>Product ID List:</td>
				<td><input type="text" name="product_ids" value="<?= @$edit['product_ids']; ?>"></td>
			</tr>
			<tr>
				<td></td>
				<td align="right">
					<?php echo tep_image_submit('button_insert.gif', IMAGE_INSERT);

					echo '<a href="/admin/information_mangaer.php">'.tep_image_button('button_cancel.gif', IMAGE_CANCEL).'</a>'; ?>
				</td>
			</tr>
		</table>
		</form>
	</td>
</tr>
