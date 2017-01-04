<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

Class WP_Gateway_Instamojo extends WC_Payment_Gateway{
	
	private $testmode;
	private $client_id;
	private $client_secret;
	
	
	public function __construct()
	{
		$this->id = "instamojo";
		$this->icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAeZJREFUOI19krtuE0EUhv9zdmd3LYSECEi20WJBBVTAM8RGioREFwrAtNBQ2E4DNU3WFNSQyJYtpUsaF7kg3gAQUPACdoQgEsQ49to7cyiIE+2uzUinmTPfN/9cKFfZvkfEzwgYYNYgUpGYyveg9HVmGwAuVXY3OHNmWSYhBJLgGaInQ2PM7f36nW9JAaPTetkNivfN8KgBywKMjpXoCcCcIeZP2ZXd62mB0BV02ofd+uJjGYVNUl46pzEgYpcFH5ISBmEMzz2LTvtH91WxLGHYmCkRA2L2khIGAGgNWNYFdFo//yUZNUm585J4LPiYq2xfOxWcSOyF0yTjBjkZgO14EYNtxyXmL/nazk07tsNJkvZBd2lxIV/d+0UkN4SgE6cBAbaAV+KC45jwvPN41yjzgXorF8e3mEgnlwmEyYgXFxAByga4/8BvXv0jOflMcIHE3wAIbCmYcPDcTsHOUbmwVhhE2WgL2gCShsl2oMN+tbdaqvPxHGDbgBo98t8UfuscNiHzYAUzCWu91VJ9+goEpQA1fFhY9/smjy0x+j/wuNYLisF0lkHkQA6f+muX+1FWNiHzYCcFT8PDf/J+Wc7xhuhoxoUBZCmYKKxOY8d6+erOXYBbINEEmBQNOEbkxX5Qej2jh79RaeQT2vwcPgAAAABJRU5ErkJggg==";
		$this->has_fields = false;
		$this->method_title = "Instamojo";
		$this->method_description = "Online Payment Gateway";
	
		$this->init_form_fields();
		$this->init_settings();
		
		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->testmode       = 'yes' === $this->get_option( 'testmode', 'no' );
		$this->client_id      = $this->get_option( 'client_id' );
		$this->client_secret  = $this->get_option( 'client_secret' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}
	
	public function init_form_fields()
	{
		$this->form_fields = include("instamojo-settings.php");		
	}
	
	public function process_payment($orderId)
	{
		include_once "lib/Instamojo.php";
		$this->log("Creating Instamojo Order for order id: $orderId");
		$this->log("Client ID: $this->client_id | Client Secret: $this->client_secret  | Testmode: $this->testmode ");
		
		$order = new WC_Order( $orderId );
		try{
			
			$api = new Instamojo($this->client_id, $this->client_secret, $this->testmode);
			
			$api_data['name'] = substr(trim((html_entity_decode( $order->billing_first_name ." ".$order->billing_last_name, ENT_QUOTES, 'UTF-8'))), 0, 20);
			$api_data['email'] 			= substr($order->billing_email, 0, 75);
			$api_data['phone'] 			= substr(html_entity_decode($order->billing_phone, ENT_QUOTES, 'UTF-8'), 0, 20);
			$api_data['amount'] 		= $this->get_order_total();
			$api_data['currency'] 		= "INR";
			$api_data['redirect_url'] 	= get_site_url();
			$api_data['transaction_id'] = time()."-". $orderId;
			$this->log("Data sent for creating order ".print_r($api_data,true));
			
			$response = $api->createOrderPayment($api_data);
			$this->log("Response from server on creating order".print_r($response,true));
			if(isset($response->order))
			{
				$url = $response->payment_options->payment_url;
				WC()->session->set( 'payment_request_id',  $response->order->id);	
				// die( json_encode(array("result"=>"success", "redirect"=>$url)));
				return array(
                    'result' => 'success', 
                    'redirect' => $url
                );
			}
		
		}catch(CurlException $e){
			$this->log("An error occurred on line " . $e->getLine() . " with message " .  $e->getMessage());
			$this->log("Traceback: " . (string)$e);
			$json = array(
				"result"=>"failure",
				"messages"=>"<ul class=\"woocommerce-error\">\n\t\t\t<li>" . $e->getMessage() . "</li>\n\t</ul>\n",
				"refresh"=>"false",
				"reload"=>"false"
				);
				
			die(json_encode($json));
		}catch(ValidationException $e){
			$this->log("Validation Exception Occured with response ".print_r($e->getResponse(), true));
			$errors_html = "<ul class=\"woocommerce-error\">\n\t\t\t";
			foreach( $e->getErrors() as $error)
			{
				$errors_html .="<li>".$error."</li>";
				
			}
			$errors_html .= "</ul>";
			$json = array(
				"result"=>"failure",
				"messages"=>$errors_html,
				"refresh"=>"false",
				"reload"=>"false"
				);
			die(json_encode($json));
		}
		catch(Exception $e){
			
			$this->log("An error occurred on line " . $e->getLine() . " with message " .  $e->getMessage());
			$this->log("Traceback: " . $e->getTraceAsString());
			$json = array(
				"result"=>"failure",
				"messages"=>"<ul class=\"woocommerce-error\">\n\t\t\t<li>".$e->getMessage()."</li>\n\t</ul>\n",
				"refresh"=>"false",
				"reload"=>"false"
				);
			die(json_encode($json));
			
		}
	}
	
	public static function log( $message ) 
	{
		insta_log($message);
	}
	
}