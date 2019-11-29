<?php

require('includes/application_top.php');

if (isset($_SESSION['login_id'])) {
	echo $_SESSION['login_id'];
}
?>