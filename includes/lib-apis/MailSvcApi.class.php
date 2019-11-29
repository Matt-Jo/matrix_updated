<?php
use GuzzleHttp\Client;

class MailSvcApi {
	private  $url = 'http://localhost:8100';
	private  $client;
	
	function __construct() {
		$this->client = new Client([ 'base_uri' => $this->url ]); 
	}
	

	function sendInvoice($order) {
		$response = $this->client->request('POST','/newinvoice/',
			['form_params' => ['order_data' => $order]]);
	        	
	    if ($response->getStatusCode() == '200') {
			return ($response->getBody()->getContents());
		}    	
	}
}