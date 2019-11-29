<?php
require_once(dirname(__FILE__).'/../../config/avatax.php');

function avatax_get_tax($products=[], $shipping_cost=0) {
	$client = new TaxServiceSoap(AVATAX_CABLES_CONFIG);
	$request = new GetTaxRequest();
	$request->setOriginAddress(_avatax_get_origin_address());

	$cart = $_SESSION['cart'];
	$shipping_address = $cart->get_shipping_address();

	// if we're not liable for tax in this state, don't even send the API call to Avalara
	if (!ck_sales_order::is_ck_tax_liable($shipping_address->get_header('zone_id'), $shipping_address->get_header('countries_iso_code_2'))) return 0;

	$customer = $cart->get_customer();
	$shipment = $cart->get_shipments('active');

	$destination = new Address();

	if ($shipment['shipping_method_id'] != '47') {
		//set the address given that this order is not for customer pickup
		$destination->setLine1($shipping_address->get_header('address1'));
		$destination->setLine2($shipping_address->get_header('address2'));
		$destination->setCity($shipping_address->get_header('city'));
		$destination->setRegion($shipping_address->get_state());
		$destination->setPostalCode($shipping_address->get_header('postcode'));
	}
	else $destination = _avatax_get_origin_address();

	$request->setDestinationAddress($destination); //Address

	$request->setCompanyCode(AVATAX_COMPANY_CODE);
	$request->setDocType(DocumentType::$SalesOrder);

	//MMD - take into account customer's tax exempt status
	$dateTime = new DateTime();
	$docCode = md5(date_format($dateTime, 'dmyGis'));

	$request->setDocCode($docCode); // invoice number
	$request->setDocDate(date('Y-m-d')); //date
	$request->setSalespersonCode(''); // string Optional
	
	$request->setCustomerCode($customer->id());		//string Required

	// if (!$customer->is_tax_exempt_in($shipping_address->get_header('zone_id'), $shipping_address->get_header('countries_iso_code_2'))) $request->setCustomerUsageType(''); //string Entity Usage
	// else $request->setCustomerUsageType('G');

	$request->setDiscount(0.00);			//decimal
	$request->setPurchaseOrderNo("");	//string Optional
	$request->setExemptionNo("");		//string	if not using ECMS which keys on customer code
	$request->setDetailLevel(DetailLevel::$Tax);		//Summary or Document or Line or Tax or Diagnostic
	$request->setLocationCode("");

	$tax_lines = [];
	foreach ($products as $idx => $product) {
		$ipn = ck_ipn2::get_ipn_by_products_id($product['id']);

		$line1 = new Line();
		$line1->setNo($idx); //string // line Number of invoice
		$line1->setItemCode($product['model']); //string
		$line1->setDescription($product['name']); //string

		if ($ipn->is('nontaxable')) $line1->setTaxCode('CKSERV'); //string
		else $line1->setTaxCode('PC070000'); //string

		$line1->setQty($product['qty']); //decimal
		$line1->setAmount($product['qty'] * $product['final_price']); //decimal // TotalAmmount
		$line1->setDiscounted(FALSE); //boolean
		$line1->setRevAcct(''); //string
		$line1->setRef1(''); //string
		$line1->setRef2(''); //string
		$line1->setExemptionNo(''); //string
		$line1->setCustomerUsageType(''); //string

		$tax_lines[] = $line1;
	}

	if (!is_numeric($shipping_cost)) $shipping_cost = 0;

	$line1 = new Line();
	$line1->setNo($idx+1); //string // line Number of invoice
	$line1->setItemCode('CK_SHIPPING'); //string
	$line1->setDescription('Shipping'); //string
	$line1->setTaxCode('FR020100'); //string
	$line1->setQty(1); //decimal
	$line1->setAmount($shipping_cost); //decimal // TotalAmmount
	$line1->setDiscounted(FALSE); //boolean
	$line1->setRevAcct(''); //string
	$line1->setRef1(''); //string
	$line1->setRef2(''); //string
	$line1->setExemptionNo(''); //string
	$line1->setCustomerUsageType(''); //string
	$tax_lines[] = $line1;

	//MMD - TODO - add coupon logic here

	$request->setLines($tax_lines);

	try {
		$getTaxResult = $client->getTax($request);
		if ($getTaxResult->getResultCode() == SeverityLevel::$Success) return $getTaxResult->getTotalTax();
		else {
			//MMD - TODO - how to handle this
			foreach ($getTaxResult->getMessages() as $msg) {
				echo $msg->getName().': '.$msg->getSummary()."\n";
			}
		}
	}
	catch (SoapFault $exception) { //MMD - TODO - how to handle this
		$msg = 'Exception: ';
		if ($exception) $msg .= $exception->faultstring;

		echo $msg."\n";
		echo $client->__getLastRequest()."\n";
		echo $client->__getLastResponse()."\n";
	}
}
