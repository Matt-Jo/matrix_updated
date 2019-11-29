<?php
include('../includes/application_top.php');

// if there's a library that's hard to test, like a feed or API call going against a live service, add that file as an include in this file, it will allow you to load it and at minimum check the syntax.  We need a better way to test the actual running of the functions, but that can be at least partially done through code reviews

$ca = new api_channel_advisor;
$ca->debug_errors();

$customer = new ck_customer2('none');
?>
OK
