<?php require_once(__DIR__.'/../../includes/application_top.php'); ?>
<!DOCTYPE html>
<html>
<head>
<title>OC | Freebie Guide</title>
	<script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
	<script type="text/javascript" src="jquery-ui.min.js"></script>
	<link rel="stylesheet" href="stylesheet.css">
	<link href='https://fonts.googleapis.com/css?family=Roboto+Condensed' rel='stylesheet' type='text/css'>
</head>
<body>
	<main><!--logo here -->
		<img id="logo" src="https://media.cablesandkits.com/static/img/ck-site-logo.png">
		<div id="menuButton">
			<h3 id="menu">Menu</h3>
		</div>
		<!--UI tabs list-->
		<div id="tabs" class='group'>
			<nav>
				<div id="closeButton">
					<p id="close">X</p>
				</div>
				<ul>
					<?php $categories = prepared_query::fetch('SELECT categories_id AS id, name FROM products_stock_control_categories WHERE categories_id NOT IN (5, 6, 8, 10, 11, 12, 14, 20, 22, 23, 26, 27, 31, 32, 34, 35, 36, 37, 38, 40, 42, 46, 48, 49, 51, 57, 58, 59, 61, 62, 63, 64, 66, 81, 83, 85, 86, 88, 89, 90, 91, 92, 93, 94, 95) ORDER BY name ASC', cardinality::SET);
					foreach($categories as $category) { ?>
					<li><a href="#cat-<?= $category['id']; ?>" id="nav-cat-<?= $category['id']; ?>" class="nav-option"><?= $category['name']; ?></a></li>
					<?php } ?>
				</ul>
			</nav>
			<div id="main_section">
				<?php foreach($categories as $category) { ?>
				<div id="cat-<?= $category['id']; ?>" class="centerDiv">
					<h4 id="page-title"><?= $category['name']; ?></h4>
					<?php $ipns = prepared_query::fetch('SELECT psc.stock_name, psci.image_lrg AS image FROM products_stock_control psc LEFT JOIN products_stock_control_images psci ON psc.stock_id = psci.stock_id WHERE psc.products_stock_control_category_id = :category_id AND psc.discontinued = 0 AND psc.is_bundle = 0 AND psci.image_lrg != \'newproduct.gif\' AND psci.image_lrg != \'\'', cardinality::SET, [':category_id' => $category['id']]);
						foreach($ipns as $ipn) { ?>
						<!--the start of the IPN format-->
						<div class="img">
							<a href="/admin/ipn_editor.php?ipnId=<?= $ipn['stock_name']; ?>" target="_blank">
								<figure class="cap-top">
									<!-- continuing ipn creation-->
									<img src="https://media.cablesandkits.com/<?= $ipn['image']; ?>" width="110" height="90">
									<figcaption>
										<!--ScrewType:<br><?= $ipn['screw_ipn']; ?>-->
									</figcaption>
								</figure>
							</a>
							<div class="desc">
							  <?= $ipn['stock_name']; ?>
							</div>
						</div>
					<?php unset($ipn); } ?>
				</div>
				<?php } ?>
			</div>
		</div>
	</main>
</body>
<script>
	//for jQuery ui tabs
	jQuery("#tabs").tabs();

	//intial state of Nav
	jQuery("nav").hide();

	//Click functions for navigation and exiting navigation
	jQuery('#menuButton').click(function(){
		jQuery('nav').show('fast');
		jQuery('#menuButton').hide('fast');
		jQuery('nav').css('position:absolute').css('z-index:9999');
		jQuery('main').css('position: relative').css('z-index: -9999');
		jQuery('header').css('position:relative').css('z-index:-9999');
	});
	
	jQuery('#close').click(function(){
		jQuery('nav').hide('fast');
		jQuery('#menuButton').show('fast');
	});

	jQuery('.nav-option').click(function() {
		jQuery('nav').hide('fast');
		jQuery('#menuButton').show('fast');
	});
</script>
</html>
