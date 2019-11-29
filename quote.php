<?php
require_once('includes/application_top.php');

if (empty($_SESSION['admin'])) CK\fn::redirect_and_exit('/');

$customer_quote_id = $_GET['customer_quote_id'];

$quote = new ck_quote($customer_quote_id);

if (!defined('CONTEXT')) define('CONTEXT', 'WWW');

require_once(DIR_FS_CATALOG.'includes/engine/vendor/autoload.php');
require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_content.class.php');
require_once(DIR_FS_CATALOG.'includes/engine/framework/ck_template.class.php');

if ($__FLAG['email']) {
	$cktpl = new ck_template('includes/templates', ck_template::EMAIL);
	$cktpl->buffer = TRUE;
	$mailer = service_locator::get_mail_service();
	$email = $mailer->create_mail();
}
else $cktpl = new ck_template('includes/templates', ck_template::SLIM);

$domain = $_SERVER['HTTP_HOST'];
$cdn = '//media.cablesandkits.com';
$static = $cdn.'/static';

$content_map = new ck_content();
$content_map->cdn = $cdn;
$content_map->static_files = $static;
$content_map->context = CONTEXT;

$content_map->head = ['title' => 'CablesAndKits Quote'];

$from = ck_admin::$sales_email;

$email_body = $cktpl->open($content_map);

$header = $quote->get_header();
$products = $quote->get_products();

$content_map->sales_phone = $content_map->prepared_by_phone = ck_admin::$toll_free_sales_phone;
$content_map->prepared_by_name = ck_admin::$sales_name;

if (!empty($quote->get_header('prepared_by'))) {
	$admin = new ck_admin($quote->get_header('prepared_by'));
	$content_map->prepared_by_name = $admin->get_header('first_name').' '.$admin->get_header('last_name');
	if (!empty($admin->get_header('phone_number'))) $content_map->prepared_by_phone = $admin->get_header('phone_number');
	$from = $admin->get_header('email_address');
}

$content_map->prepared_by_email = $from;

if ($__FLAG['email']) $content_map->email = 1;

$content_map->customer_quote_id = $header['customer_quote_id'];
if (!empty($header['customers_id'])) {
	$customer = new ck_customer2($header['customers_id']);

	if (!empty($header['customers_extra_logins_id'])) {
		$el = $customer->get_extra_logins($header['customers_extra_logins_id']);
		$content_map->contact_name = $el['first_name'].' '.$el['last_name'];
	}
	else $content_map->contact_name = $customer->get_header('first_name').' '.$customer->get_header('last_name');

	$address = $customer->get_addresses('default');
	if (!empty($address->get_header('company_name'))) $content_map->company_name = $address->get_header('company_name');
}

$content_map->created_date = $header['created']->format('m/d/Y');
$content_map->expiration_date = $header['expiration_date']->format('m/d/Y');
$content_map->admin_name = $header['admin_name'];
$content_map->admin_email_address = $header['admin_email_address'];
$content_map->customer_email = $header['customer_email'];
$content_map->url_hash = $header['url_hash'];

$content_map->subtotal = 0;
$content_map->products = [];

foreach ($products as $product) {
	if ($product['option_type'] == ck_cart::$option_types['INCLUDED']) continue; // we don't show addon products on quote display
	$content_map->subtotal += $product['price'] * $product['quantity'];
	$prod = [
		'quote_product_id' => $product['customer_quote_product_id'],
		'quantity' => $product['quantity'],
		'model_number' => $product['listing']->get_header('products_model'),
		'description' => $product['listing']->get_header('products_name'),
		'lead_time' => NULL,
		'quote_price' => '$'.number_format($product['price'], 2),
		'line_total' => '$'.number_format($product['price']*$product['quantity'], 2)
	];

	if ($product['listing']->get_inventory('available') >= $product['quantity']) {
		$prod['set_lead_time'] = $prod['lead_time'] = 'In Stock';
	}
	else {
		$prod['set_lead_time'] = $prod['lead_time'] = ($product['listing']->get_header('lead_time')+1).' days';
	}

	if (!empty($_REQUEST['lead_time'][$product['customer_quote_product_id']])) $prod['set_lead_time'] = $_REQUEST['lead_time'][$product['customer_quote_product_id']];

	$content_map->products[] = $prod;
}

$content_map->display_total = '$'.number_format($content_map->subtotal, 2);

if (!empty($_REQUEST['contact_name'])) $content_map->contact_name = $_REQUEST['contact_name'];
if (!empty($_REQUEST['preparer'])) $content_map->preparer = $_REQUEST['preparer'];
if (!empty($_REQUEST['company_name'])) $content_map->company_name = $_REQUEST['company_name'];
if (!empty($_REQUEST['customer_email'])) $content_map->customer_email = $_REQUEST['customer_email'];
if (!empty($_REQUEST['shipping_cost'])) {
	$content_map->shipping_cost = number_format($_REQUEST['shipping_cost'], 2);
	$content_map->display_total = '$'.number_format($content_map->subtotal + $_REQUEST['shipping_cost'], 2);
}
if (!empty($_REQUEST['additional_notes'])) $content_map->additional_notes = $_REQUEST['additional_notes'];

$email_body .= $cktpl->content('includes/templates/page-quote.mustache.html', $content_map);
$email_body .= $cktpl->close($content_map);

if ($__FLAG['email']) {
	$email
		->set_subject('Re: CablesAndKits.com Quote Number: '.$header['customer_quote_id'])
		->set_from($from)
		->add_to($_REQUEST['send_email_to'])
		->set_body($email_body);
	$mailer->send($email);
	$quote->update_quote([':status' => 1]);
	$quote->add_history_record('email sent');
	CK\fn::redirect_and_exit('/admin/customer-quote.php?customer_quote_id='.$header['customer_quote_id'].'&email-sent=1');
}


