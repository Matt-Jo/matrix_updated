<?php
require_once(__DIR__.'/../includes/application_top.php');

error_reporting(E_ALL);

$cli = false;

if (isset($argv) && count($argv) > 0) {
	$cli = true;
}

$path = dirname(__FILE__);

exit();
/*? >

<script type="text/javascript">
 var _gaq = _gaq || [];
 _gaq.push(['_setAccount', 'UA-4362083-1']);
 _gaq.push(['_trackPageview']);

	_gaq.push(['_addTrans',
		'287282',			// order ID - required
		'Cables And Kits', // affiliation or store name
		'-244777364.00',		// total - required
		'-0.00',			// tax
		'-0.00',		// shipping
		'Santa Barbara',		// city
		'California',	// state or province
		'USA'			// country
	]);
	_gaq.push(['_addItem',
		'287282',			// order ID - necessary to associate item with transaction
		'C4900M-BKT-KIT=',			// SKU/code - required
		'Cisco Catalyst 4900M Rack Mount Kit, Complete',		// product name
		'Cisco Accessories>Cisco Rack Mount Kits>Cisco Switches',	// category or variation
		'61194341.00',		// unit price - required
		'-4'				// item quantity - required
	]);
	_gaq.push(['_trackTrans']);

 (function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
 })();

</script>
*/
?>