<?php

require_once(dirname(__FILE__).'/../../../config/avatax.php');

//MMD - $negative argument is used for posting a negative invoice when
// editing an invoice.
function avatax_get_tax($order_id, $invoice_id = null, $negative = false) {
	$client = new TaxServiceSoap(AVATAX_CABLES_CONFIG);
	$request = new GetTaxRequest();

	$ck_order = new ck_sales_order($order_id);
	$customer = $ck_order->get_customer();

	$sign = 1;
	if ($negative) $sign = -1;

	if (!empty($ck_order->get_header('ca_order_id'))) return FALSE;
	if (!ck_sales_order::is_ck_tax_liable($ck_order->get_header('delivery_state'), $ck_order->get_header('delivery_country'))) return FALSE;

	$request->setOriginAddress(_avatax_get_origin_address());

	$destination = new Address();

	if ($ck_order->get_shipping_method('shipping_code') != '47') { // the case where the order is not for customer pickup
		$destination->setLine1($ck_order->get_header('delivery_street_address'));
		$destination->setLine2($ck_order->get_header('delivery_suburb'));
		$destination->setCity($ck_order->get_header('delivery_city'));
		$destination->setRegion($ck_order->get_header('delivery_state'));
		$destination->setPostalCode($ck_order->get_header('delivery_postcode'));
	}
	else $destination = _avatax_get_origin_address();

	$request->setDestinationAddress($destination);	//Address

	$request->setCompanyCode(AVATAX_COMPANY_CODE);

	//MMD - if we have an invoice ID, we want to create the document so we can post the tax
	if ($invoice_id) {
		$request->setDocType('SalesInvoice');
		$request->setDocCode($invoice_id); // invoice number
		$request->setDocDate(ck_datetime::NOW()->format('Y-m-d')); // date
	}
	else {
		$request->setDocType(DocumentType::$SalesOrder);
		$docCode = md5(ck_datetime::format_direct(ck_datetime::NOW(), 'dmyGis'));
		$request->setDocCode($docCode); // invoice number
		$request->setDocDate($ck_order->get_header('date_purchased')->format('Y-m-d')); //date
	}

	$request->setSalespersonCode('');			// string Optional
	$request->setCustomerCode($ck_order->get_header('customers_id'));		//string Required

	// Nov 1st 2019 Customer tax exemptions are managed in Avalara, so we don't need to send any usage type
	// if (!$ck_order->is('tax_exempt')) {
	// 	$request->setCustomerUsageType('');	//string	Entity Usage
	// }
	// else {
	// 	$request->setCustomerUsageType('G');
	// }

	$request->setDiscount(0.00); // decimal
	$request->setDetailLevel(DetailLevel::$Tax); // Summary or Document or Line or Tax or Diagnostic

	$tax_lines = [];
	$row_count = 1;
	foreach ($ck_order->get_products() as $product) {
		$line1 = new Line();
		$line1->setNo($row_count); //string // line Number of invoice
		$line1->setItemCode($product['model']); //string
		$line1->setDescription($product['name']); //string

		if ($product['ipn']->is('nontaxable')) $line1->setTaxCode('CKSERV'); //string
		else $line1->setTaxCode('PC070000'); //string

		$line1->setQty($product['quantity']); //decimal
		$line1->setAmount($product['quantity'] * $product['final_price'] * $sign); //decimal // TotalAmmount
		$line1->setDiscounted(false); //boolean

		$tax_lines[] = $line1;
		$row_count++;
	}

	if ($shipping = $ck_order->get_simple_totals('shipping')) {
		$line1 = new Line();
		$line1->setNo($row_count); //string // line Number of invoice
		$line1->setItemCode('CK_SHIPPING'); //string
		$line1->setDescription('Shipping'); //string
		$line1->setTaxCode('FR020100'); //string
		$line1->setQty(1); //decimal
		$line1->setAmount($shipping * $sign); //decimal // TotalAmmount
		$line1->setDiscounted(false); //boolean
		$tax_lines[] = $line1;
		$row_count++;
	}

	$request->setLines($tax_lines);

	try {
		$getTaxResult = $client->getTax($request);
		if ($getTaxResult->getResultCode() == SeverityLevel::$Success) {
			// every time we recalculate tax we store the tax rate on the order
			$tax_rate = 0;
			$lines = $getTaxResult->getTaxLines();
			foreach ($lines as $unused => $line) {
				if ($line->getRate() > 0) {
					$tax_rate = $line->getRate();
				}
			}
			prepared_query::execute("update orders o set o.avatax_rate = :tax_rate where o.orders_id = :orders_id", [':tax_rate' => $tax_rate, ':orders_id' => $order_id]);
			return $getTaxResult->getTotalTax();
		}
		else {//MMD - TODO - how to handle this
			foreach ($getTaxResult->getMessages() as $msg) {
				echo $msg->getName().": ".$msg->getSummary()."\n";
			}
		}

	}
	catch (SoapFault $exception) { //MMD - TODO - how to handle this
		$msg = "Exception: ";
		if ($exception)
			$msg .= $exception->faultstring;

		echo $msg."\n";
		echo $client->__getLastRequest()."\n";
		echo $client->__getLastResponse()."\n";
	}
}

//MMD - $negative argument is used for posting a negative invoice when
// editing an invoice.
function avatax_post_tax($invoiceId, $orderId, $negative = false, $invoice_date=NULL) {

	if (empty($invoice_date)) $invoice_date = date('Y-m-d');

	$invoice = new ck_invoice($invoiceId);
	$order = new ck_sales_order($orderId);

	if ($invoice->get_simple_totals('total') < 0) $negative = TRUE;

	if (!empty($order->get_header('ca_order_id'))) return FALSE;
	if (!ck_sales_order::is_ck_tax_liable($order->get_header('delivery_state'), $order->get_header('delivery_country'))) return FALSE;

	$client = new TaxServiceSoap(AVATAX_CABLES_CONFIG);
	$request= new PostTaxRequest();

	// Locate Document by Invoice Number
	$request->setDocCode($invoiceId);
	$request->setDocDate($invoice_date);
	$request->setDocType('SalesInvoice');

	$request->setCompanyCode(AVATAX_COMPANY_CODE);

	$tax = avatax_get_tax($orderId, $invoiceId, $negative);//this call is important because it creates the document
	$request->setTotalAmount($invoice->get_simple_totals('total') - $tax);
	$request->setTotalTax($tax);
	$request->setCommit(true);

	try {
		$result = $client->postTax($request);
		if ($result->getResultCode()!=SeverityLevel::$Success) {
			foreach ($result->getMessages() as $msg) {
				//MMD - TODO - do we want to log any of this?
				//echo $msg->getName().": ".$msg->getSummary()."\n";
			}
		}
	}
	catch (SoapFault $exception) {
		$msg = "Exception: ";
		if ($exception)
			$msg .= $exception->faultstring;

		echo $msg."\n";
		echo $client->__getLastRequest()."\n";
		echo $client->__getLastResponse()."\n";
	}
}

function avatax_cancel_tax($invoiceId) {

	$invoice = new ck_invoice($invoiceId);
	$order = $invoice->get_order();

	if (!empty($order->get_header('ca_order_id'))) return FALSE;
	if (!ck_sales_order::is_ck_tax_liable($order->get_header('delivery_state'), $order->get_header('delivery_country'))) return FALSE;

	$client = new TaxServiceSoap(AVATAX_CABLES_CONFIG);
	$request= new CancelTaxRequest();

	// Locate Document by Invoice Number (Document Code)
	$request->setDocCode($invoiceId);
	$request->setDocType('SalesInvoice');

	$request->setCompanyCode(AVATAX_COMPANY_CODE);

	$request->setCancelCode(CancelCode::$DocDeleted);

	try {
		$result = $client->cancelTax($request);

		if ($result->getResultCode() != "Success") {
			foreach ($result->getMessages() as $msg) {
				//MMD - TODO - log this?
				//echo $msg->getName().": ".$msg->getSummary()."\n";
			}
		}
	}

	catch (SoapFault $exception) {
		$msg = "Exception: ";
		if ($exception)
			$msg .= $exception->faultstring;

		echo $msg."\n";
		echo $client->__getLastRequest()."\n";
		echo $client->__getLastResponse()."\n";
	}
}

function avatax_get_rma_tax($rmaInvoiceId, $restock) {
	$invoice = new ck_invoice($rmaInvoiceId);
	$rma = $invoice->get_rma();
	$order = $rma->get_sales_order();
	$order_invoice = $order->get_latest_invoice('instance');
	$customer = $invoice->get_customer();

	$client = new TaxServiceSoap(AVATAX_CABLES_CONFIG);
	$request= new GetTaxRequest();

	//$tax_liable = ck_sales_order::is_ck_tax_liable($order->get_header('delivery_state'), $order->get_header('delivery_country')) && empty($order->get_header('ca_order_id'));
	//$tax_exempt = $order->is('tax_exempt');
	$tax_liable = empty($order->get_header('ca_order_id'));
	$taxed = $order->get_simple_totals('tax') > 0;

	if (!$taxed || !$tax_liable) return FALSE;

	$request->setOriginAddress(_avatax_get_origin_address());

	$destination = new Address();
	if ($order->get_shipping_method('shipping_code') != 47) { //the case where the order is not for customer pickup
		$destination->setLine1($order->get_header('delivery_street_address'));
		$destination->setLine2($order->get_header('delivery_suburb'));
		$destination->setCity($order->get_header('delivery_city'));
		$destination->setRegion($order->get_header('delivery_state'));
		$destination->setPostalCode($order->get_header('delivery_postcode'));
	}
	else {
		$destination = _avatax_get_origin_address();
	}

	$request->setDestinationAddress ($destination);	//Address

	$request->setCompanyCode(AVATAX_COMPANY_CODE);

	$request->setDocType(DocumentType::$ReturnInvoice);
	$request->setDocCode($order_invoice->id().'-'.$rmaInvoiceId);

	$request->setDocDate(date("Y-m-d"));			//date
	$request->setCustomerCode($order->get_header('customers_id'));		//string Required

	$request->setCustomerUsageType("");	//string	Entity Usage

	$request->setDiscount(0.00);			//decimal
	$request->setDetailLevel(DetailLevel::$Tax);		//Summary or Document or Line or Tax or Diagnostic

	$tax_lines = array();
	$row_count = '1';
	foreach ($invoice->get_products() as $line_item) {
		$line1 = new Line();
		$line1->setNo ($row_count);				//string // line Number of invoice
		$line1->setItemCode($line_item['listing']->get_header('products_model'));			//string
		$line1->setDescription($line_item['listing']->get_header('products_name'));		//string

		if ($line_item['ipn']->is('nontaxable')) $line1->setTaxCode("CKSERV");			//string
		else $line1->setTaxCode("PC070000");			//string

		$line1->setQty(abs($line_item['quantity']));				//decimal
		$line1->setAmount(abs($line_item['quantity']) * $line_item['invoice_unit_price']);			//decimal // TotalAmmount
		$line1->setDiscounted(false);		//boolean

		$tax_lines[] = $line1;
		$row_count++;
	}

	if ($restock > 0) {
		$line1 = new Line();
		$line1->setNo ($row_count);				//string // line Number of invoice
		$line1->setItemCode("CK_RESTOCK");			//string
		$line1->setDescription("Restock Fee");		//string
		$line1->setTaxCode("CKSERV");			//string
		$line1->setQty(1);				//decimal
		$line1->setAmount($restock * -1);			//decimal // TotalAmmount
		$line1->setDiscounted(false);		//boolean
		$tax_lines[] = $line1;
		$row_count++;
	}

	$request->setLines($tax_lines);

	try {
		$getTaxResult = $client->getTax($request);
		if ($getTaxResult->getResultCode() == SeverityLevel::$Success) {
			return $getTaxResult->getTotalTax();
		}
		else {//MMD - TODO - how to handle this
			foreach ($getTaxResult->getMessages() as $msg) {
				echo $msg->getName().": ".$msg->getSummary()."\n";
			}
		}

	}
	catch (SoapFault $exception) { //MMD - TODO - how to handle this
		$msg = "Exception: ";
		if ($exception)
			$msg .= $exception->faultstring;

		echo $msg."\n";
		echo $client->__getLastRequest()."\n";
		echo $client->__getLastResponse()."\n";
	}
}

function avatax_post_rma_tax($rmaInvoiceId, $tax) {

	$invoice = new ck_invoice($rmaInvoiceId);
	$rma = $invoice->get_rma();
	$order = $rma->get_sales_order();
	$order_invoice = $order->get_latest_invoice('instance');

	if (!empty($order->get_header('ca_order_id'))) return 0;
	if (!ck_sales_order::is_ck_tax_liable($order->get_header('delivery_state'), $order->get_header('delivery_country'))) return 0;

	$client = new TaxServiceSoap(AVATAX_CABLES_CONFIG);
	$request= new PostTaxRequest();

	// Locate Document by Invoice Number
	$request->setDocCode($order_invoice->id().'-'.$rmaInvoiceId);
	$request->setDocDate(date('Y-m-d'));
	$request->setDocType(DocumentType::$ReturnInvoice);

	$request->setCompanyCode(AVATAX_COMPANY_CODE);

	//MMD - calculate total amount using absolute values because sometimes the value is negative in the
	//accounting system and sometimes it's positive
	$total_amount = abs($invoice->get_simple_totals('total')) + abs($tax);

	$request->setTotalAmount($total_amount * -1);
	$request->setTotalTax($tax);
	$request->setCommit(true);

	try {
		$result = $client->postTax($request);
		if ($result->getResultCode()!=SeverityLevel::$Success) {
			foreach ($result->getMessages() as $msg) {
				//MMD - TODO - do we want to log any of this?
				//echo $msg->getName().": ".$msg->getSummary()."\n";
			}
		}
	}
	catch (SoapFault $exception) {
		$msg = "Exception: ";
		if ($exception)
			$msg .= $exception->faultstring;

		echo $msg."\n";
		echo $client->__getLastRequest()."\n";
		echo $client->__getLastResponse()."\n";
	}
}
?>
