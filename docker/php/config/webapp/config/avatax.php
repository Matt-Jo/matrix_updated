<?php 

require_once(dirname(__FILE__) . '/../includes/avatax/AvaTax.php');

define('AVATAX_CABLES_CONFIG', 'cablesandkits');

$config = service_locator::get_config_service();
if($config->get_env() != Ck_Config::ENV_PRODUCTION){ //DEVELOPMENT
    define('AVATAX_COMPANY_CODE', 'CKDEV');
    new ATConfig(AVATAX_CABLES_CONFIG, array(
        'url'       => 'https://avatax.avalara.net/',
        'account'   => '1100075882',
        'license'   => '8C1A03E512960877',
        'trace'     => true) // change to false for production
    );
}
else{ //PRODUCTION
    define('AVATAX_COMPANY_CODE', 'CKSTORE');
    new ATConfig(AVATAX_CABLES_CONFIG, array(
        'url'       => 'https://avatax.avalara.net/',
        'account'   => '1100075882',
        'license'   => '8C1A03E512960877',
        'trace'     => false) // change to false for production
    );
}

/*******
 * Functions below here are only utility functions for the
 * functions above
 *******/
function _avatax_get_origin_address(){
    //Add Origin Address
    $origin = new Address();
    $origin->setLine1("4555 Atwater Ct");
    $origin->setLine2("Suite A");
    $origin->setCity("Buford");
    $origin->setRegion("GA");
    $origin->setPostalCode("30518");
    return $origin;
}
