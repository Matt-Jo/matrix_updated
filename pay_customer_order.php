<?php
require('includes/application_top.php');

$paymentSvcApi = new PaymentSvcApi();

$orderId 	= $_POST['custOrderId2'];
$amount 	= floatval($_POST['orderAmt2']);
$customerId = $_POST['customerId'];
$token 		= $_POST['paymentToken'];

$custData =[ "orderId" 			=> $orderId, 
			 "amount" 			=> $amount,
			 "customerId" 		=> $customerId, 
			 "token" 			=> $token,
			 "authorization" 	=> true ];
			 
			 
//die(var_dump($custData));			 
				     
$result = json_decode($paymentSvcApi->authorizeCCTransaction($custData), true);
$status = $result['result']['status'];

//if ( in_array($result, ['submitted_for_settlement','authorized'])) {
if ($result['result']['status'] == 'submitted_for_settlement') {
	//we have a brain treeid now update customer table with brain_tree_customer_id
	$transactionId = $result["result"]["transactionId"];
		
	$payment_id = prepared_query::insert('INSERT INTO acc_payments (customer_id, payment_amount, payment_method_id, payment_ref, payment_date) VALUES (?, ?, 1 ,?, NOW())', array($customerId, $amount, $transactionId));

	//insert into acc_payments_to_orders
	prepared_query::execute('insert into acc_payments_to_orders(payment_id, order_id, amount) values(?,?,?)', array ($payment_id,  $orderId ,$amount));
				
	//update transaction id only if it is a child order.
	prepared_query::execute("UPDATE orders SET paymentsvc_id = ?  WHERE orders_id = ?", array($transactionId,$orderId));
		
}
else {
	//there was an issue creating customer and adding card
	//can we set a flash message or something similar to indicate failure
	//or even show  a popup?
}	

header('location: account_history_info.php');	
exit();