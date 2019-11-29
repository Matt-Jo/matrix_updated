<?php
$boxitems = [
	'id' => 'administrator',
	'title' => 'User/System',
	'sort' => ['Access Mgmt' => 'groups', 'System Admin' => 'groups'],
	'groups' => [
		'Access Mgmt' => [
			['file' => 'admin_members.php', 'text' => 'Member Groups'],
			['file' => 'master-password.php', 'text' => 'Manage Master Password'],
			['file' => 'team-list', 'text' => 'Teams'],
		],
		'System Admin' => [
			['file' => 'import_generic.php', 'text' => 'Generic File Import'],
			['file' => 'export_generic.php', 'text' => 'Generic Data Export'],
		],
	],
]; ?>
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
