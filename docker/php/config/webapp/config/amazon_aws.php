<?php

	$autoloader = Zend_Loader_Autoloader::getInstance();
	$autoloader->registerNamespace('MarketplaceWebService_');
	$autoloader->registerNamespace('MarketplaceWebServiceOrders_');

	require(DIR_WS_FUNCTIONS . '/aws.php');
   define ('AWS_DATE_FORMAT', 'Y-m-d\TH:i:s\Z');

   /************************************************************************
    * REQUIRED
    *
    * * Access Key ID and Secret Acess Key ID, obtained from:
    * http://aws.amazon.com
    *
    * IMPORTANT: Your Secret Access Key is a secret, and should be known
    * only by you and AWS. You should never include your Secret Access Key
    * in your requests to AWS. You should never e-mail your Secret Access Key
    * to anyone. It is important to keep your Secret Access Key confidential
    * to protect your account.
    ***********************************************************************/
    define('AWS_ACCESS_KEY_ID', 'AKIAJMQUROSVOVRT5VNA');
    define('AWS_SECRET_ACCESS_KEY', 'b5FbIxE6j2EqFaBn+6Lslqek/2UcBZCHXoHF/AyA');

   /************************************************************************
    * REQUIRED
    * 
    * All MWS requests must contain a User-Agent header. The application
    * name and version defined below are used in creating this value.
    ***********************************************************************/
    define('AWS_APPLICATION_NAME', 'CablesAndKits.com - AWS PHP');
    define('AWS_APPLICATION_VERSION', '1.0');
    
   /************************************************************************
    * REQUIRED
    * 
    * All MWS requests must contain the seller's merchant ID and
    * marketplace ID.
    ***********************************************************************/
    define ('AWS_MERCHANT_ID', 'A220IKGHHX34J7');

	define("AWS_DEBUG", FALSE);

