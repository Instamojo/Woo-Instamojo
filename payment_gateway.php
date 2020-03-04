<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
include_once "lib/ErrorHandler.php";

Class WP_Gateway_Instamojo extends WC_Payment_Gateway {
	
        use ErrorHandler;

        private $testmode;
	private $client_id;
	private $client_secret;
        private $localhost_list = array('127.0.0.1', '::1');
	private $instamojo_api = null;
        
	public function __construct()
	{
            $this->id = "instamojo";
            $this->icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAeZJREFUOI19krtuE0EUhv9zdmd3LYSECEi20WJBBVTAM8RGioREFwrAtNBQ2E4DNU3WFNSQyJYtpUsaF7kg3gAQUPACdoQgEsQ49to7cyiIE+2uzUinmTPfN/9cKFfZvkfEzwgYYNYgUpGYyveg9HVmGwAuVXY3OHNmWSYhBJLgGaInQ2PM7f36nW9JAaPTetkNivfN8KgBywKMjpXoCcCcIeZP2ZXd62mB0BV02ofd+uJjGYVNUl46pzEgYpcFH5ISBmEMzz2LTvtH91WxLGHYmCkRA2L2khIGAGgNWNYFdFo//yUZNUm585J4LPiYq2xfOxWcSOyF0yTjBjkZgO14EYNtxyXmL/nazk07tsNJkvZBd2lxIV/d+0UkN4SgE6cBAbaAV+KC45jwvPN41yjzgXorF8e3mEgnlwmEyYgXFxAByga4/8BvXv0jOflMcIHE3wAIbCmYcPDcTsHOUbmwVhhE2WgL2gCShsl2oMN+tbdaqvPxHGDbgBo98t8UfuscNiHzYAUzCWu91VJ9+goEpQA1fFhY9/smjy0x+j/wuNYLisF0lkHkQA6f+muX+1FWNiHzYCcFT8PDf/J+Wc7xhuhoxoUBZCmYKKxOY8d6+erOXYBbINEEmBQNOEbkxX5Qej2jh79RaeQT2vwcPgAAAABJRU5ErkJggg==";
            $this->has_fields = false;
            $this->method_title = "Instamojo";
            $this->method_description = "Online Payment Gateway";

            $this->init_form_fields();
            $this->init_settings();
            $this->add_to_payment_gateway_option();
	}

	public function process_payment($orderId)
	{
            $this->log("Creating Instamojo Order for order id: $orderId");
            $order = new WC_Order($orderId);
            try{
                $api = $this->get_instamojo_api();

                $api_data['buyer_name'] = substr(trim((html_entity_decode( $order->billing_first_name ." ".$order->billing_last_name, ENT_QUOTES, 'UTF-8'))), 0, 20);
                $api_data['email'] = substr($order->billing_email, 0, 75);
                $api_data['phone'] = substr(html_entity_decode($order->billing_phone, ENT_QUOTES, 'UTF-8'), 0, 20);
                $api_data['amount'] = $this->get_order_total();
                $api_data['currency'] = "INR";
                $api_data['redirect_url'] = get_site_url();
                $api_data['purpose'] = $orderId;
                $api_data['send_email'] = 'True';
                $api_data['send_sms'] = 'True';
                if (!$this->is_localhost()) {
                    $api_data['webhook'] = get_site_url();
                }
                $api_data['allow_repeated_payments'] = 'False';
                $this->log("Data sent for creating order ".print_r($api_data,true));
                $response = $api->createPaymentRequest($api_data);
                $this->log("Response from server on creating payment request".print_r($response,true));
                if (isset($response->id)) {
                    WC()->session->set( 'payment_request_id',  $response->id);
                    return array('result' => 'success', 'redirect' => $response->longurl);
                }
            } catch(CurlException $e) {
                $this->handle_curl_exception($e);
            } catch(ValidationException $e) {
                $this->handle_validation_exception($e);
            } catch(Exception $e) {
                $this->handle_exception($e);
            }
	}

        public function get_payment_details($payment_id)
        {
            $this->log("Getting Payment detail for payment id: $payment_id");
            try{
                $api = $this->get_instamojo_api();
                $this->log("Data sent for getting payment detail ".$payment_id);
                $response = $api->get_payment_detail($payment_id);
                $this->log("Response from server on getting payment detail".print_r($response,true));
                if (isset($response->id)) {
                    return array('result' => 'success', 'payment_detail' => $response);
                }

                return array('result' => 'error', 'message' => $response->message);
            } catch(CurlException $e) {
                $this->handle_curl_exception($e);
            } catch(ValidationException $e) {
                $this->handle_validation_exception($e);
            } catch(Exception $e) {
                $this->handle_exception($e);
            }
        }

	public static function log( $message ) 
	{
            insta_log($message);
	}

        public function init_settings()
        {
            parent::init_settings();
            $this->title          = $this->get_option( 'title' );
            $this->description    = $this->get_option( 'description' );
            $this->testmode       = 'yes' === $this->get_option( 'testmode', 'no' );
            $this->client_id      = $this->get_option( 'client_id' );
            $this->client_secret  = $this->get_option( 'client_secret' );
        }

        public function init_form_fields()
	{
            $this->form_fields = include("instamojo-settings.php");		
	}

        private function add_to_payment_gateway_option()
        {
            if (isset($this->client_id) && $this->client_secret) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
                return;
            }

            $this->handle_error('An error occurred, missing value for client_id and/or client_secret.');
        }

        private function get_instamojo_api()
        {
            if (null === $this->instamojo_api) {
                include_once "lib/Instamojo.php";
                $this->log("Client ID: $this->client_id | Client Secret: $this->client_secret  | Testmode: $this->testmode ");
                try{
                    $this->instamojo_api = new Instamojo($this->client_id, $this->client_secret, $this->testmode);
                } catch(Exception $e) {
                    $this->handle_exception($e);
                }
            }

            return $this->instamojo_api;
        }

        private function is_localhost()
        {
            return (in_array($_SERVER['REMOTE_ADDR'], $this->localhost_list)) ? true : false;
        }
}