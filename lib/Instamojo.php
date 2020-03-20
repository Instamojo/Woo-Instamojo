<?php
/**
 * Instamojo
 * used to manage Instamojo API calls
 * 
 */
include dirname(__FILE__) . DIRECTORY_SEPARATOR . "curl.php";
include dirname(__FILE__) . DIRECTORY_SEPARATOR . "ValidationException.php";
Class Instamojo
{
	private $api_endpoint;
	private $auth_endpoint;
	private $auth_headers;
	private $access_token;
	private $client_id;
	private $client_secret;
	
	 function __construct($client_id,$client_secret,$test_mode)
	{
		$this->curl = new Curl();
		$this->curl->setCacert(dirname(__FILE__) . DIRECTORY_SEPARATOR . "cacert.pem");
		$this->client_id 		= $client_id;
		$this->client_secret	= $client_secret;

		if($test_mode)
			$this->api_endpoint  = "https://test.instamojo.com/v2/";
		else
			$this->api_endpoint  = "https://www.instamojo.com/v2/";
		if($test_mode)
			$this->auth_endpoint = "https://test.instamojo.com/oauth2/token/";
		else
			$this->auth_endpoint = "https://www.instamojo.com/oauth2/token/"; 
		
		$this->get_access_token();
	}

	public function get_access_token()
	{
		$data = array();
		$data['client_id']		= $this->client_id;
		$data['client_secret'] 	= $this->client_secret;
		$data['scopes'] 		= "all";
		$data['grant_type'] 	= "client_credentials";

		$result = $this->curl->post($this->auth_endpoint,$data);
		if($result)
		{
			$result = json_decode($result);
			if(isset($result->error))
			{
				throw new ValidationException("The Authorization request failed with message '$result->error'", array("<a href='https://support.instamojo.com/hc/en-us/articles/214564625-Payment-Gateway-Authorization-Failed' target='_blank'>Payment Gateway Authorization Failed</a>"),$result);
			}else
				$this->access_token = $result->access_token;
		}
		
		$this->auth_headers[] = "Authorization:Bearer $this->access_token";
		
	}

        public function create_payment_request($data)
        {
            $endpoint = $this->api_endpoint .'payment_requests/';
            $result = $this->curl->post($endpoint, $data, array('headers' => $this->auth_headers));

            return json_decode($result);
        }

        public function get_payment_detail($payment_id)
        {
            $endpoint = $this->api_endpoint .'payments/'.$payment_id;
            $result = $this->curl->get($endpoint, array('headers' => $this->auth_headers));

            return json_decode($result);
        }

        public function create_refund($payment_id, $data)
        {
            $endpoint = $this->api_endpoint .'payments/'.$payment_id.'/refund/';
            $result = $this->curl->post($endpoint, $data, array('headers' => $this->auth_headers));

            return json_decode($result);
        }

        public function get_payment_list($query_string)
        {
            $endpoint = $this->api_endpoint .'payments?'. http_build_query($query_string);
            $result = $this->curl->get($endpoint, array('headers' => $this->auth_headers));

            return json_decode($result);
        }

        public function initiate_gateway_order($data)
        {
            $endpoint = $this->api_endpoint .'gateway/orders/';
            $result = $this->curl->post($endpoint, $data, array('headers' => $this->auth_headers));

            return json_decode($result);
        }

        public function get_gateway_order_detail($id)
        {
            $endpoint = $this->api_endpoint .'gateway/orders/id:'. $id;
            $result = $this->curl->get($endpoint, array('headers' => $this->auth_headers));

            return json_decode($result);
        }

        public function get_checkout_options_for_gateway_order($id)
        {
            $endpoint = $this->api_endpoint .'gateway/orders/'. $id.'/checkout-options/';
            $result = $this->curl->get($endpoint, array('headers' => $this->auth_headers));

            return json_decode($result);
        }

	public function getPaymentStatus($payment_id, $payments){
		foreach($payments as $payment){
		    if($payment->id == $payment_id){
			    return $payment->status;
		    }
		}
	}
	
}