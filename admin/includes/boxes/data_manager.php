<?php
$boxitems = [
	'id' => 'data_manager',
	'title' => 'Data Manager',
	'sort' => [0 => 'individual'],
	'individual' => [
		['file' => 'site-constants', 'text' => 'Site Constants'],
		['file' => 'lookup-manager', 'text' => 'Lookup Manager'],
		['file' => 'dynamic-lookup-manager', 'text' => 'Dynamic Lookup Manager'],
	],
];
?>
<h3 id="<?= $boxitems['id']; ?>"><a href="#"><?= $boxitems['title']; ?></a></h3>
<div>
	<?php foreach ($boxitems['sort'] as $boxgroup => $boxtype) {
		if ($boxtype == 'groups') { ?>
	<span class="subsection"><?= $boxgroup; ?></span>
		<?php } ?>
	<ul>
		<?php $boxset = $boxtype=='individual'?$boxitems['individual']:$boxitems['groups'][$boxgroup];
		foreach ($boxset as $boxlink) {
			if (tep_admin_check_boxes($boxlink['file'])) { ?>
		<li><a href="/admin/<?= $boxlink['file']; ?>?<?= @$boxlink['parameters']; ?>selected_box=<?= $boxitems['id']; ?>"><?= $boxlink['text']; ?></a></li>
			<?php }
		} ?>
	</ul>
	<?php } ?>
</div>
