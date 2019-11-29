<?php
 require('includes/application_top.php');

 require(DIR_WS_LANGUAGES.$_SESSION['language'].'/'.DOWN_FOR_MAINTENANCE_FILENAME);

 $breadcrumb->add('Down for Maintenance', '/down_for_maintenance.php');



 $content = 'down_for_maintenance';


 require('templates/Pixame_v1/main_page.tpl.php');
?>
