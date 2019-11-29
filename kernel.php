<?php

/**
 * This file was created with the intention to 
 * provide a centralized bootstrap logic, reusable 
 * across different types of environments: lamp, 
 * micro service, lambda function, automated testing suite, etc
 */

require_once(__DIR__.'/includes/library/ck.class.php');
$ck = new ck;

require_once(__DIR__.'/includes/engine/vendor/autoload.php');
// debug_tools::init_page();

require_once(__DIR__.'/includes/configure.php');

