<table class="dataTableRowSelected" width="100%" border="0" cellspacing="1" cellpadding="1" bgcolor="">
<tr>
<td valign="top" width="100%">
<a href="Javascript:setsmiley_1_s('<b>text</b>');"><img src="<?= EDITOR_IMAGE ?>/bold.gif" border="0" alt="Bold Font"></a>
<a href="Javascript:setsmiley_1_s('<i>text</i>');"><img src="<?= EDITOR_IMAGE ?>/italic.gif" border="0" alt="Italics font"></a>
<a href="Javascript:setsmiley_1_s('<u>text</u>');"><img src="<?= EDITOR_IMAGE ?>/underline.gif" border="0" alt="Underline"></a>
<a href="Javascript:setsmiley_1_s('<s>text</s>');"><img src="<?= EDITOR_IMAGE ?>/strike.gif" border="0" alt="Strike Out"></a>
<a href="Javascript:setsmiley_1_s('<sub>text</sub>');"><img src="<?= EDITOR_IMAGE ?>/sub.gif" border="0" alt="Subscript"></a>
<a href="Javascript:setsmiley_1_s('<sup>text</sup>');"><img src="<?= EDITOR_IMAGE ?>/sup.gif" border="0" alt="Superscript"></a>
<a href="Javascript:setsmiley_1_s('<span style=width=80%; filter:shadow(color=red,strength=3,left)>for this to work you must place a quote symbol between (= and width) also between () and >)</span>');"><img src="<?= EDITOR_IMAGE ?>/shadow.gif" border="0" alt="Shadow Text"></a>
<a href="Javascript:setsmiley_1_s('<span style=width=80%; filter:glow(color=red,strength=2)>for this to work you must place a quote symbol between (= and width) also between () and >)</span>');"><img src="<?= EDITOR_IMAGE ?>/glow.gif" border="0" alt="Glow Text"></a>
<a href="Javascript:setsmiley_1_s('<font color=red>text</font>');"><img src="<?= EDITOR_IMAGE ?>/color.gif" border="0" alt="Font color"></a>
<a href="Javascript:setsmiley_1_s('<font face=verdana>text</font>');"><img src="<?= EDITOR_IMAGE ?>/fontface.gif" border="0" alt="Font face"></a>
<a href="Javascript:setsmiley_1_s('<font size=2>text</font>');"><img src="<?= EDITOR_IMAGE ?>/fontsize.gif" border="0" alt="Font size"></a>
<a href="Javascript:setsmiley_1_s('<div align=center>text</div>');"><img src="<?= EDITOR_IMAGE ?>/fontleft.gif" border="0" alt="Font alignment"></a>
<a href="Javascript:setsmiley_1_s('<tt>text</tt>');"><img src="<?= EDITOR_IMAGE ?>/tele.gif" border="0" alt="Teletype"></a>
<a href="Javascript:setsmiley_1_s('<hr>');"><img src="<?= EDITOR_IMAGE ?>/hr.gif" border="0" alt="Horizontal Line"></a>
<a href="Javascript:setsmiley_1_s('<span><marquee direction=up>Text</marquee></span>');"><img src="<?= EDITOR_IMAGE ?>/move1.gif" border="0" alt="Scroll"></a>
<a href="Javascript:setsmiley_1_s('<table width=100% bgcolor=#f8f8f9 border=0><tr><td>quote</td></tr></table>');"><img src="<?= EDITOR_IMAGE ?>/quote2.gif" border="0" alt="Quote"></a>
<a href="Javascript:setsmiley_1_s('<img src=http://www.image.com/images/img.gif>');"><img src="<?= EDITOR_IMAGE ?>/img.gif" border="0" alt="Image"></a>
<a href="Javascript:setsmiley_1_s('<embed src=http://www.flash/images/image.swf quality=high pluginspage=http://www.macromedia.com/go/getflashplayer type=application/x-shockwave-flash width=200 height=200></embed>');"><img src="<?= EDITOR_IMAGE ?>/flash.gif" border="0" alt="Flash Image"></a>
<a href="Javascript:setsmiley_1_s('<a href=mailto:username@site.com>Mail Me!</a>');"><img src="<?= EDITOR_IMAGE ?>/email2.gif" border="0" alt="E-mail link"></a>
<a href="Javascript:setsmiley_1_s('<a href=http://www.link.com>address</a>');"><img src="<?= EDITOR_IMAGE ?>/url.gif" border="0" alt="Hyperlink"></a>
<a href="Javascript:setsmiley_1_s('<ul><li>text1</li><li>text3</li><li>text3</li></ul>');"><img src="<?= EDITOR_IMAGE ?>/list.gif" border="0" alt="List"></a>
</td>
</tr>

<tr>
<td width="100%" valign="top">

<?php
echo faqdesk_draw_textarea_field('faqdesk_answer_short_1', 'soft', '50', '3', stripbr((!empty($faqdesk_answer_short[1]) ? stripslashes($faqdesk_answer_short[1]) : faqdesk_get_faqdesk_answer_short($pInfo->faqdesk_id, 1))
));
?>

		</td>
	</tr>
</table>

<?php
/*

	osCommerce, Open Source E-Commerce Solutions ---- http://www.oscommerce.com
	Copyright (c) 2002 osCommerce
	Released under the GNU General Public License

	IMPORTANT NOTE:

	This script is not part of the official osC distribution but an add-on contributed to the osC community.
	Please read the NOTE and INSTALL documents that are provided with this file for further information and installation notes.	script name:		NewsDesk
	version:				1.48.2
	date:					06-05-2004 (dd/mm/yyyy)
	original author:		Carsten aka moyashi
	web site:				www..com
	modified code by:		Wolfen aka 241

*/
?>
