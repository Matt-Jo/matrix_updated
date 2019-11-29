<?php
$boxitems = array(
	'sort' => array(0 => 'individual', 'Reports' => 'groups'),
	'individual' => array(
		array('file' => 'customers_list.php', 'text' => 'All Customers'),
	),
	'groups' => array(
		'Reports' => array(
			array('file' => 'status_customer_history.php', 'text' => 'Customer Purchase History'),
		)
	)
); ?>
<h3 id="customers"><a href="#">Customers</a></h3>
<div>
	<?php foreach ($boxitems['sort'] as $boxgroup => $boxtype) {
		if ($boxtype == 'groups') { ?>
	<span class="subsection"><?= $boxgroup; ?></span>
		<?php } ?>
	<ul>
		<?php $boxset = $boxtype=='individual'?$boxitems['individual']:$boxitems['groups'][$boxgroup];
		foreach ($boxset as $boxlink) {
			if (tep_admin_check_boxes($boxlink['file'])) { ?>
		<li><a href="/admin/<?= $boxlink['file']; ?>?<?= @$boxlink['parameters']; ?>selected_box=customers"><?= $boxlink['text']; ?></a></li>
			<?php }
		} ?>
	</ul>
	<?php } ?>
</div>
