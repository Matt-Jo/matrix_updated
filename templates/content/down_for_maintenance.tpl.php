<style>
	.hdr { border-top:2px solid #000; border-bottom:2px solid #000; padding:5px; }
</style>
<h3 class="hdr">Down for Maintenance ...</h3>
<table border="0" width="100%" cellspacing="0" cellpadding="8">
	<tr>
		<td class="main"><?php echo DOWN_FOR_MAINTENANCE_TEXT_INFORMATION; ?></td>
	</tr>
	<?php if (DISPLAY_MAINTENANCE_TIME == 'true') { ?>
	<tr>
		<td class="main"><?php echo TEXT_MAINTENANCE_ON_AT_TIME.TEXT_DATE_TIME; ?></td>
	</tr>
	<?php }

	if (DISPLAY_MAINTENANCE_PERIOD == 'true') { ?>
	<tr>
		<td class="main"><?php echo TEXT_MAINTENANCE_PERIOD.TEXT_MAINTENANCE_PERIOD_TIME; ?></td>
	</tr>
	<?php } ?>
</table>
<table>
	<tr>
		<td align="right" class="main"><br><?= DOWN_FOR_MAINTENANCE_STATUS_TEXT; ?><br><br><a href="/"><img src="/templates/Pixame_v1/images/buttons/english/button_continue.gif" border="0" alt="Continue" title="Continue"></a></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
</table>
