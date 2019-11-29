<?php
require_once(__DIR__.'/../../includes/application_top.php');

ck_listing_category::expire_category_discounts();
ck_listing_category::refresh_category_discounts();
?>
