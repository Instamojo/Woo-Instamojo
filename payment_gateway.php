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
        private $valid_refund_types = ['RFD', 'TNR', 'QFL', 'QNR', 'EWN', 'TAN', 'PTH'];
        
        private const DEFAULT_CURRENCY = 'INR'; 

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

                $api_data['buyer_name'] = $this->encode_string_data(trim($order->billing_first_name .' '.$order->billing_last_name, ENT_QUOTES), 20);
                $api_data['email'] = substr($order->billing_email, 0, 75);
                $api_data['phone'] = $this->encode_string_data($order->billing_phone, 20);
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
                $response = $api->create_payment_request($api_data);
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

        public function create_refund($payment_id, $trasnaction_id, $refund_amount, $refund_type, $refund_reason)
        {
            $this->log("Creating Refund for payment id: $payment_id");
            try{
                if (!in_array($refund_type, $this->valid_refund_types)) {
                    $this->handle_error('Invalid Refund Type :'.$refund_type);
                }

                $api = $this->get_instamojo_api();

                $api_data['transaction_id'] = $trasnaction_id;
                $api_data['refund_amount'] = $refund_amount;
                $api_data['type'] = $this->encode_string_data($refund_type, 3);
                $api_data['body'] = $this->encode_string_data($refund_reason, 100);
                $this->log("Data sent for creating refund ".print_r($response,true));
                $response = $api->create_refund($payment_id, $api_data);
                $this->log("Response from server on getting payment detail".print_r($response,true));
                if ($response->success == true) {
                    return array('result' => 'success', 'refund' => $response);
                }

                return array('result' => 'error', 'message' => $response);
            } catch(CurlException $e) {
                $this->handle_curl_exception($e);
            } catch(ValidationException $e) {
                $this->handle_validation_exception($e);
            } catch(Exception $e) {
                $this->handle_exception($e);
            }
        }

        public function get_payment_list($page = 1 ,$payment_id = '', $buyer_name = '', $seller_name = '', $payout = '', $product_slug = '', $order_id = '', $min_created_at = '', $max_created_at = '', $min_updated_at = '', $max_updated_at = '')
        {
            $this->log("Getting Payments list for payment_id : $payment_id, buyer_name : $buyer_name, seller_name : $seller_name, payout : $payout, product_slug : $product_slug, order_id : $order_id, min_created_at : $min_created_at, max_created_at : $max_created_at, min_updated_at : $min_updated_at, max_updated_at : $max_updated_at");
            try{
                $api = $this->get_instamojo_api();

                $query_string['page'] = $page;
                $query_string['id'] = $this->encode_string_data($payment_id, 20);
                $query_string['buyer'] = $this->encode_string_data($buyer_name, 100);
                $query_string['seller'] = $this->encode_string_data($seller_name, 100);
                $query_string['payout'] = $this->encode_string_data($payout, 20);
                $query_string['product'] = $this->encode_string_data($product_slug, 100);
                $query_string['order_id'] = $this->encode_string_data($order_id, 100);
                $query_string['min_created_at'] = $this->encode_string_data($min_created_at, 24);
                $query_string['max_created_at'] = $this->encode_string_data($max_created_at, 24);
                $query_string['min_updated_at'] = $this->encode_string_data($min_updated_at, 24);
                $query_string['max_updated_at'] = $this->encode_string_data($max_updated_at, 24);

                $this->log('Data sent for getting payments list');
                $response = $api->get_payment_list($this->remove_empty_elements_from_array($query_string));
                $this->log("Response from server on getting payment list".print_r($response,true));
                if (isset($response->id)) {
                    return array('result' => 'success', 'payment_list' => $response);
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
        
        public function get_gateway_order(string $id) {

            $this->log("Get Gateway Order for id: $id");
            
            try{
                $api = $this->get_instamojo_api();

                $this->log('Data sent for getting gateway order');
                $response = $api->get_gateway_order($id);
                $this->log("Response from server on getting getway order".print_r($response,true));
                if (isset($response->id)) {
                    return array('result' => 'success', 'payment_list' => $response);
                }

                return array('result' => 'error', 'message' => $response);
            } catch(CurlException $e) {
                $this->handle_curl_exception($e);
            } catch(ValidationException $e) {
                $this->handle_validation_exception($e);
            } catch(Exception $e) {
                $this->handle_exception($e);
            }
        }
        
        public function get_checkout_options_for_gateway_order(string $id) {

            $this->log("Get Checkout Options for Gateway Order for id: $id");
            
            try{
                $api = $this->get_instamojo_api();

                $this->log('Data sent for getting checkout options for gateway order');
                $response = $api->get_checkout_options_for_gateway_order($id);
                $this->log("Response from server on getting getway order".print_r($response,true));
                if (isset($response->id)) {
                    return array('result' => 'success', 'payment_list' => $response);
                }

                return array('result' => 'error', 'message' => $response);
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
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options' ));
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

        private function encode_string_data($string_data, $max_length = null)
        {
            $string_data = html_entity_decode($string_data, ENT_QUOTES, 'UTF-8');

            if ($max_length == null) {
                return $string_data;
            }

            return substr($string_data, 0, $max_length);
        }

        private function remove_empty_elements_from_array($data_array)
        {
            return array_filter($data_array, function($value) { return !is_null($value) && $value !== ''; });
        }
}