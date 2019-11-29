<?php
require_once('includes/configure.php');

if (!defined('CONTEXT')) define('CONTEXT', 'WWW');

require_once('includes/engine/vendor/autoload.php');
require_once('includes/engine/framework/ck_content.class.php');
require_once('includes/engine/framework/ck_template.class.php');
require_once('includes/engine/framework/canonical_page.class.php');
require_once('includes/engine/tools/imagesizer.class.php');
$cktpl = new ck_template('includes/templates');

mail('jason.shinn@cablesandkits.com', 'FATAL DB CONNECTION ERROR', 'Someone was redirected to the fatal page:'."\n".date('Y-m-d h:i:s a'));

$domain = $_SERVER['HTTP_HOST'];
$cdn = '//media.cablesandkits.com';
$static = $cdn.'/static';

$content_map = new ck_content();
$content_map->cdn = $cdn;
$content_map->static_files = $static;
$content_map->context = CONTEXT;

// ----------------------------------------------
// START HEAD

$content_map->head = array('verify-v1?' => array('Id5qSbm5TRrGS+Qa3DCFghfE1kqlqXwLIIMIvQVTbKo='), 'fb:admins?' => array('100006394206803'));

// set the context for the type of page that this is
$page_context = NULL;
$request_details = parse_url($_SERVER['REQUEST_URI']);
$script_details = pathinfo($_SERVER['SCRIPT_FILENAME']);
$page_context = 'homepage';

// set the page key for dynamic content on this page
$page_key = NULL;
$page_key = $_SERVER['REQUEST_URI'];

// need to use a global "page object" system whereby we aren't having to re-query the category or product details anywhere else in the page once we've grabbed them here at the top

// set head title, meta description and meta keywords
$content_map->head['title'] = 'Cisco Products, Business Networking Products';
$content_map->head['meta']['description'] = 'Get schooled in the latest Cisco products and solutions. Trust CablesAndKits.com to help you find the right business networking products today.';
//$content_map->head['meta']['keywords?'] = array('cisco products, network products, business networking products, cisco network products');

// analytics
$content_map->head['analytics']['key'] = 'UA-4362083-1';
$analytics_customer_type = 'Regular';
// short circuit evaluation allows us to pass through all checks progressively in a single condition
if (!empty($_SESSION['customer_id']) && $cust = new ck_customer2($_SESSION['customer_id']) && $cust->is('dealer')) $analytics_customer_type = "Dealer";
$content_map->head['analytics']['customer_type'] = $analytics_customer_type;
// if we're on the checkout success page, analytics scope is 2, otherwise it's 3
$content_map->head['analytics']['scope'] = 3;

// if this page has a canonical link that is not itself, use it
$canonical = new canonical_page($page_key, $_SERVER['REQUEST_URI'], $page_context);
if ($canonical->use_link()) $content_map->head['canonical?'] = array($canonical->link); // stick it in an array just to take advantage of simplified mustache structures

// HANDLE EXPERIMENTS HERE - for now we're skipping them, since we're not running any experiments currently

// ----------------------------------------------
// START BODY

$content_map->body = array(
	'class' => NULL,
	'homepage' => 'http://'.$domain
);

$content_map->login = array(
	'status' => 'anon',
	'anon' => 1
);

$content_map->cart = array('item_count' => 0);

$cktpl->open($content_map);

// ----------------------------------------------
// START MAIN CONTENT

$content_map = new ck_content();
$content_map->cdn = $cdn;
$content_map->static_files = $static;

$cktpl->content('includes/templates/page-outage.mustache.html', $content_map);

// ----------------------------------------------
// START FOOTER

$content_map = new ck_content();
$content_map->cdn = $cdn;
$content_map->static_files = $static;

if (CONTEXT != 'WWW' && !empty($errlog)) $content_map->errlog = print_r($errlog, TRUE);

$content_map->google_trusted_store = array('key' => '202120');
$content_map->google_trusted_store['subkey'] = '4090110';
if ($page_context == 'product') $content_map->google_trusted_store['products_id?'] = array($page_key);

$content_map->google_remarketing = array('key' => '1070544332');
$content_map->google_remarketing['pagetype'] = 'error';

$content_map->addshopper = array('key' => '512693afa387642e6d6e0843');

//$content_map->sitewide_notice['message?'] = array('Due to a local service outage, our phone & email system is temporarily down. In the meantime, please feel free to contact us via <a href="https://livechat.boldchat.com/aid/3213111608562807935/bc.chat?resize=true&amp;cwdid=3352884902902678067" target="_blank" onclick="window.open((window.pageViewer &amp;&amp; pageViewer.link || function(link) {return link;})(this.href + (this.href.indexOf(\'?\')>=0 ? \'&amp;\' : \'?\') + \'url=\' + escape(document.location.href)), \'Chat148782571958384204\', \'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=640,height=480\');return false;" style="color: blue; text-decoration: underline;">live chat</a> for further assistance. We apologize for any inconvenience.');

$cktpl->close($content_map);
?>
