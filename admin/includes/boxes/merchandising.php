<?php
$boxitems = [
	'sort' => ['Merch Units' => 'groups', 'Merch Containers' => 'groups'],
	'groups' => [
		'Merch Units' => [
			//['file' => 'merchandising-product-list.php', 'text' => 'Products'],
			//['file' => 'merchandising-composite-list.php', 'text' => 'Composites'],
			['file' => 'merchandising-unit-family-list.php', 'text' => 'Families'],
		],
		'Merch Containers' => [
			//['file' => 'merchandising-listing-list.php', 'text' => 'Product Listings'],
			//['file' => 'merchandising-dow-list.php', 'text' => 'DOWs'],
			['file' => 'merchandising-family-container-list.php', 'text' => 'Families'],
			//['file' => 'merchandising-progression-list.php', 'text' => 'Progressions'],
			//['file' => 'merchandising-configuration-list.php', 'text' => 'Configurations'],
			//['file' => 'merchandising-browse-by-fit-list.php', 'text' => 'Browse By Fits'],
			//['file' => 'merchandising-category-list.php', 'text' => 'Categories']
		],
	],
]; ?>
<h3 id="merchandising"><a href="#">Merchandising</a></h3>
<div>
	<?php foreach ($boxitems['sort'] as $boxgroup => $boxtype) {
		if ($boxtype == 'groups') { ?>
	<span class="subsection"><?= $boxgroup; ?></span>
		<?php } ?>
	<ul>
		<?php $boxset = $boxtype=='individual'?$boxitems['individual']:$boxitems['groups'][$boxgroup];
		foreach ($boxset as $boxlink) {
			if (tep_admin_check_boxes($boxlink['file'])) { ?>
		<li><a href="/admin/<?= $boxlink['file']; ?>?<?= @$boxlink['parameters']; ?>selected_box=merchandising"><?= $boxlink['text']; ?></a></li>
			<?php }
		} ?>
	</ul>
	<?php } ?>
</div>
