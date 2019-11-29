<?php
$customer = $_SESSION['cart']->get_customer();
?>
<style>
	/* layout classes */
	.main-content{ float:left; width:600px; }
	.sidebar { float:right; width: 250px; margin-top:15px; }
	/* general classes */
	p { font-family:Arial, Verdana, sans-serif; font-size:11px; margin-top:0; }
	.link { text-decoration: underline; color: #d22842; }
	/* standard classes */
	.bold {font-weight:bold;}
	.section-heading { font-family:Arial, Verdana, sans-serif; color:#d22842; font-weight:bold; font-size:12px; }
	.section-title { font-weight:bold; margin-bottom:0; }
	.clear-both { clear:both; }
	.form-block { padding-left:12px; }
	.block { padding-left:0px; max-width:700px; width:100%; }
	.block p { margin-top:3px; }
	.top-block { padding-top:7px; }
	.textarea-message { max-width:450px; width:100%; }
	.inline { display:inline; }
	.form-controls-spacing { margin-bottom:12px; }
	.form-controls-left { float:left; }
	.form-block { font-family:Arial, Verdana, sans-serif; font-size:12px; }
	.ck_rounded_corners_with_dropshadow { background-color: white; overflow:auto; }
	.submit-button { text-align:right; padding-right:35px; }
	.error { color:red; }
	.success { background-color:#9d9; margin:0px 0px 10px 0px; padding:10px; text-align:center; font-weight:bold; border-radius:5px; border:2px solid #090; }
	.failed { background-color:#fbb; margin:0px 0px 10px 0px; padding:10px; text-align:center; font-weight:bold; border-radius:5px; border:2px solid #c00; }
	div.success p, div.failed p { font-size:13px; }
</style>
<div class="ck_rounded_box" >
	<div class="ck_blue_tab_left"></div>
	<div class="ck_blue_tab_middle">Contact Us</div>
	<div class="ck_blue_tab_right"></div>
	<div class="clear-both"></div>

	<div class="main rounded-corners">
        <div class="grid grid-pad">
			<div class="col-8-12">
				<div class="block top-block">
					<p>
						Need Assistance? We are here to help! Feel free to contact us via phone, email, chat, or US Mail to inquire about the products and services found on our
						Web site, or for questions related to your order.
					</p>
				</div><br/>
				<div class="block">
					<span class="section-heading">Contact Us by Phone</span>
					<p>Contact Sales or Customer Care at <span class="bold"><?= $_SESSION['cart']->get_contact_phone(); ?></span>.</p>
				</div>
				<!--div class="block">
					<span class="section-heading">Contact Us by Chat</span>
					<p>
						Contact one of our representatives by
						<a class="link" href="https://lc.chat/now/8165031/" onclick="return chat_popup(this.href)">Live Chat</a>
					</p>
				</div-->
				<div class="block">
					<span class="section-heading">Returns</span>
					<p>For returning items, please use our <a class="link" href="/info/returns-page">Returns</a> page.</p>
				</div>
				<div class="block">
					<span class="section-heading">Contact Us Online</span>
					<p>
						Please complete the fields below. More specific information from you allows us to provide a quick,
						accurate response.
					</p>
				</div>
				<div class="hsForm">
				</div>
			</div>
			<div class="col-4-12">
				<p class="section-heading">Hours of Operation</p>
				<p>Shop online 24 hours a day, 7 days a week!</p>
				<p class="section-title">Shipping Hours:</p>
				<p>Mon - Fri 9am - 6pm EST for ground shipments</p>
				<p>Mon - Fri 9am - 8pm EST for express shipments</p>
				<p class="section-title">Order Pickup:</p>
				<p>Mon - Fri 9am - 6pm EST</p>
				<p class="section-title">Customer Service:</p>
				<p>Mon - Fri 9am - 6pm EST</p>
				<p class="section-heading">Contact Information</p>
				<p class="section-title">Toll Free</p>
				<p><?= $_SESSION['cart']->get_contact_phone(); ?></p>
				<p class="section-title">Local</p>
				<p><?= $_SESSION['cart']->get_contact_local_phone(); ?></p>
				<p class="section-title">Warehouse Address</p>
				<p>
					CablesAndKits.com<br/>
					4555 Atwater Ct.<br/>
					Suite A<br/>
					Buford, GA 30518
				</p>
				<p class="section-title">Mailing Address</p>
				<p>
					CablesAndKits.com<br/>
					4555 Atwater Ct.<br/>
					Suite A<br/>
					Buford, GA 30518
				</p>
			</div>
		</div>
	</div>
</div>
<script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/shell.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.16.0/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/additional-methods.min.js"></script>
<script>
	$( "#contact_us" ).validate({
		rules: {
			phone: {
				required: true,
			}
		}
	});
	//hubspot code
	hbspt.forms.create({
		portalId: "3353942",
		formId: "d7efa57c-26d2-473c-a56b-7e4fee31d546", target: ".hsForm"
	});
</script>

