<?php
/*
 *
 * moving this to the vendor portal page
 *
 */
//$open_wtbs = $db->fetchAll('SELECT r.rfq_id, r.nickname, r.request_type, r.published_date FROM ck_rfqs r WHERE r.active = 1 AND r.published_date IS NOT NULL AND r.request_type = \'WTB\' ORDER BY r.request_type DESC, r.published_date DESC');
//$open_rfqs = $db->fetchAll('SELECT r.rfq_id, r.nickname, r.request_type, r.published_date FROM ck_rfqs r WHERE r.active = 1 AND r.published_date IS NOT NULL AND r.request_type = \'RFQ\' ORDER BY r.request_type DESC, r.published_date DESC');
//
//$today = new DateTime(); ?>
<!--<style>-->
<!--	#vp-lb { width:175px; padding:8px 0px 8px 8px; margin:0px; }-->
<!--	#vp-lb-header { color:#dd003c; font-size:24px; font-weight:bold; background-image:url(/templates/Pixame_v1/images/lb2l.gif); background-repeat:repeat-x; background-position:center bottom; margin:0px 0px 5px 0px; padding-bottom:15px; }-->
<!---->
<!--	#open-wtb, #open-rfq { background-image:url(/templates/Pixame_v1/images/lb2l.gif); background-repeat:repeat-x; background-position:center bottom; padding-bottom:15px; margin-bottom:5px; }-->
<!---->
<!--	#open-wtb table, #open-rfq table { width:100%; }-->
<!--	.vp-lb-sub-header { text-align:left; color:#dd003c; font-size:18px; }-->
<!--	.req-age { text-align:center; }-->
<!--	td.req-age { color:#386881; }-->
<!--	.newreq { color:#dd003c; }-->
<!--	.req-name { padding:1px 4px 1px 10px; }-->
<!--	.req-name a { color:#444; }-->
<!--	.viewing td { background-color:#dfdedd; }-->
<!--	.viewing .req-name a { color:#386881; }-->
<!--</style>-->
<!--<div id="vp-lb">-->
<!--	<a href="/VendorPortal"><h3 id="vp-lb-header">Open Requests</h3></a>-->
<!--	--><?php //if (!empty($open_wtbs)) { ?>
<!--	<div id="open-wtb">-->
<!--		<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">-->
<!--			<thead>-->
<!--				<tr>-->
<!--					<th class="vp-lb-sub-header">Open WTBs</th>-->
<!--					<th class="req-age">Age</th>-->
<!--				</tr>-->
<!--			</thead>-->
<!--			<tbody>-->
<!--				<tr>-->
<!--					<td class="req-name" colspan="2" style="font-weight:bold; padding: 0px"><a href="/VendorPortal/wtb">View All open WTBs</a></td>-->
<!--				</tr>-->
<!--				--><?php //foreach ($open_wtbs as $request) {
//					$pubdate = new DateTime($request['published_date']);
//					$diff = $pubdate->diff($today); ?>
<!--				<tr class="--><?php //echo $request['rfq_id']==$rfq_id?'viewing':''; ?><!--">-->
<!--					<td class="req-name"><a href="/VendorPortal/--><?//= $request['rfq_id']; ?><!--">--><?//= $request['nickname']; ?><!--</a></td>-->
<!--					<td class="req-age">--><?php //echo $diff->days==0?'<span class="newreq">New!</span>':$diff->days; ?><!--</td>-->
<!--				</tr>-->
<!--				--><?php //} ?>
<!--			</tbody>-->
<!--		</table>-->
<!--	</div>-->
<!--	--><?php //}
//	if (!empty($open_rfqs)) { ?>
<!--	<div id="open-rfq">-->
<!--		<table border="0" cellpadding="0" cellspacing="0" style="width:100%;">-->
<!--			<thead>-->
<!--				<tr>-->
<!--					<th class="vp-lb-sub-header">Open RFQs</th>-->
<!--					<th class="req-age">Age</th>-->
<!--				</tr>-->
<!--			</thead>-->
<!--			<tbody>-->
<!--				<tr>-->
<!--					<td class="req-name" colspan="2" style="font-weight:bold;"><a href="/VendorPortal/rfq">View All open RFQs</a></td>-->
<!--				</tr>-->
<!--				--><?php //foreach ($open_rfqs as $request) {
//					$pubdate = new DateTime($request['published_date']);
//					$diff = $pubdate->diff($today); ?>
<!--				<tr class="--><?php //echo $request['rfq_id']==$rfq_id?'viewing':''; ?><!--">-->
<!--					<td class="req-name"><a href="/VendorPortal/--><?//= $request['rfq_id']; ?><!--">--><?//= $request['nickname']; ?><!--</a></td>-->
<!--					<td class="req-age">--><?php //echo $diff->format('d')==0?'<span class="newreq">New!</span>':$diff->format('d'); ?><!--</td>-->
<!--				</tr>-->
<!--				--><?php //} ?>
<!--			</tbody>-->
<!--		</table>-->
<!--	</div>-->
<!--	--><?php //} ?>
<!--</div>-->
