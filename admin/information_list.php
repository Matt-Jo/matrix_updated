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
<tr class=pageHeading>
	<td><?= $title; ?></td>
</tr>
<tr>
	<td>
		<table border="0" width=100% cellpadding=2 cellspacing=1 bgcolor="#ffffff">
			<tr class="dataTableHeadingRow">
				<td align=center class="dataTableHeadingContent"><?php echo NO_INFORMATION;?></td>
				<td align=center class="dataTableHeadingContent"><?= tep_image(DIR_WS_ICONS.'sort.gif', SORT_BY); ?></td>
				<td align=center class="dataTableHeadingContent"><?php echo TITLE_INFORMATION;?></td>
				<td align=center class="dataTableHeadingContent"><?php echo ID_INFORMATION;?></td>
				<td align=center class="dataTableHeadingContent"><?php echo PUBLIC_INFORMATION;?></td>
				<td align=center class="dataTableHeadingContent" colspan=2><?php echo ACTION_INFORMATION;?></td>
			</tr>
			<?php
			 $no=1;
			 if (sizeof($data) > 0) {
			 foreach($data as $key => $val) {
			 $no % 2 ? $bgcolor="#DEE4E8" : $bgcolor="#F0F1F1";
			?>
			<tr bgcolor="<?= $bgcolor; ?>">
				<td align="right" class="dataTableContent"><?= $no; ?></td>
				<td align="center" class="dataTableContent"><?= $val['v_order'];?></td>
				<td class="dataTableContent"><?= $val['info_title'];?></td>
				<td align="center" class="dataTableContent"><?= $val['information_id'];?></td>
				<td nowrap class="dataTableContent">
					<?php
					if ($val['visible']==1) {
						echo tep_image(DIR_WS_ICONS.'icon_status_green.gif', IMAGE_ICON_STATUS_GREEN, 10, 10).'&nbsp;&nbsp;

						<a href="'.'/admin/information_manager.php?adgrafics_information=Visible&information_id='.$val['information_id'].'&visible='.$val['visible'].'">'.
							tep_image(DIR_WS_IMAGES.'icon_status_red_light.gif', DEACTIVATION_ID_INFORMATION." $val[information_id]", 10, 10).'</a>';
					}
					else {
						echo tep_image(DIR_WS_ICONS.'icon_status_red.gif', IMAGE_ICON_STATUS_RED, 10, 10).'&nbsp;&nbsp;

					<a href="/admin/information_manager.php?adgrafics_information=Visible&information_id='.$val['information_id'].'&visible='.$val['visible'].'">'.
						tep_image(DIR_WS_IMAGES.'icon_status_green_light.gif', ACTIVATION_ID_INFORMATION." $val[information_id]", 10, 10).'</a>';
					};
					?>
				</td>
				<td align=center class="dataTableContent">
					<?php echo '<a href="/admin/information_manager.php?adgrafics_information=Edit&information_id='.$val['information_id'].'">'.
					tep_image(DIR_WS_ICONS.'edit.gif', EDIT_ID_INFORMATION.$val['information_id']).'</a>'; ?>
				</td>
				<td align=center class="dataTableContent">
					<?php echo '<a href="/admin/information_manager.php?adgrafics_information=Delete&information_id='.$val['information_id'].'">'.
					tep_image(DIR_WS_ICONS.'delete.gif', DELETE_ID_INFORMATION.$val['information_id']).'</a>'; ?>
				</td>
			</tr>
			<?php
			$no++;
			 }
				} else { ?>
				<tr bgcolor="#DEE4E8">
					<td colspan=7><?php echo ALERT_INFORMATION;?></td>
				</tr>
			<?php } ?>
		</table>
	</td>
</tr>
<tr>
	<td align=right>
		<a href="/admin/information_manager.php?adgrafics_information=Added"><?= tep_image_button('button_new.gif', ADD_INFORMATION); ?></a>
		<a href="/admin/information_manager.php"><?php tep_image_button('button_cancel.gif', IMAGE_CANCEL); ?></a>
	</td>
</tr>