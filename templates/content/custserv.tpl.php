<?php
require(DIR_WS_LANGUAGES.$_SESSION['language'].'/informationbox.php');
?>
<style>
	.boxText { font-family: Arial, Verdana, sans-serif; font-size: 11px; }
</style>
<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">Information</div>
	<div class="ck_blue_tab_right" ></div>
	<div style="clear: both;"></div>

	<div class="main rounded-corners">
		<div class="grid grid-pad">	
			<div class="col-1-1">
				<table border="0" width="100%" cellspacing="0" cellpadding="2" class="templateinfoBox">
					<tbody>
						<tr>
							<td>
								<table border="0" width="100%" cellspacing="0" cellpadding="2" class="infoBoxContents">
									<tbody>
										<tr>
											<td><img src="//media.cablesandkits.com/pixel_trans.gif" border="0" alt="Seperator Image" title="Seperator Image" width="100%" height="1"></td>
										</tr>
										<tr>
											<td align="center" class="boxText">
												<div class="grid grid-pad">
													<div class="col-1-3" align="left"><a href="/account_history.php">My Orders</a></div>
													<div class="col-1-3" align="left"><a href="/info/lifetime-warranty">Lifetime Warranty</a></div>
													<div class="col-1-3" align="left"><a href="/info/about-us">About Us</a></div>
													<div class="col-1-3" align="left"><a href="/account.php">My Account</a></div>
													<div class="col-1-3" align="left"><a href="/info/shipping-policy">Shipping Policies</a></div>
													<div class="col-1-3" align="left"><a href="/info/reviews">Testimonials</a></div>
													<div class="col-1-3" align="left"><a href="/info/conditions-of-use">Conditions of Use</a></div>
													<div class="col-1-3" align="left"><a href="/info/privacy">Your Privacy</a></div>
													<div class="col-1-3" align="left"><a href="/info/careers">Careers</a></div>
													<div class="col-1-3" align="left"><a href="/info/terms-and-conditions">Terms &amp; Conditions</a></div>
													<div class="col-1-3" align="left"><a href="/info/returns-exchanges">Returns &amp; Exchanges</a></div>
												</div>
											</td>
										</tr>
										<tr>
											<td><img src="//media.cablesandkits.com/pixel_trans.gif" border="0" alt="Seperator Image" title="Seperator Image" width="100%" height="1"></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<div class="ck_rounded_box">
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">Frequently Asked Questions</div>
	<div class="ck_blue_tab_right"></div>
	<div style="clear: both;"></div>

	<div class="main rounded-corners">
		<div class="grid grid-pad">	
			<div class="col-1-1">
				<table border="0" width="100%" cellspacing="0" cellpadding="2" class="templateinfoBox">
					<tbody>
						<tr>
							<td>
								<table border="0" width="100%" cellspacing="0" cellpadding="2" class="infoBoxContents">
									<tbody>
										<tr>
											<td><img src="//media.cablesandkits.com/pixel_trans.gif" border="0" alt="Seperator Image" title="Seperator Image" width="100%" height="1"></td>
										</tr>
										<tr>
											<td align="left" class="boxText">
												<?= $categories_faqdesk_string; ?>
											</td>
										</tr>
										<tr>
											<td><img src="//media.cablesandkits.com/pixel_trans.gif" border="0" alt="Seperator Image" title="Seperator Image" width="100%" height="1"></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>				   
</div>

<?php include 'templates/'.'content/contact_us.tpl.php'; ?>
