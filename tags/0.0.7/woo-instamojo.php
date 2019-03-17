<?php
/*
Plugin Name: WooCommerce - Instamojo
Plugin URI: http://www.instamojo.com
Description: Instamojo Payment Gateway for WooCommerce. Instamojo lets you collect payments instantly.
Version: 0.0.7
Author: Instamojo
Email: support@instamojo.com
Author URI: http://www.instamojo.com/
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


add_action('plugins_loaded', 'woocommerce_instamojo_init', 0);
define('instamojo_imgdir', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/img/');

function url_handler($url){
    if(strpos($url, '?') === FALSE){
        return $url . "?";
    }
    else{
        return $url . '&';
    }
}

# add the settings link at plugin actions
add_filter('plugin_action_links_'.plugin_basename(__FILE__),'instamojo_settings_links');
function instamojo_settings_links($links)
{   
    $mylinks = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_instamojo') . '">Settings</a>'
        );
    return array_merge($links, $mylinks);
}

add_filter('plugin_row_meta', 'plugin_row_meta', 10, 2 );
function plugin_row_meta( $links, $file ) {

    if (strpos( $file, 'woo-instamojo.php' ) !== false ) {
        $new_links = array('<a href="mailto:support@instamojo.com">Support</a>');
        $links = array_merge( $links, $new_links );
    }
    
    return $links;
}

# activate session
function session_initialize() {
    if (!session_id()) 
    {
        session_initialize();
    }
}

add_action( 'init', 'session_start' );

function woocommerce_instamojo_init(){
    if(!class_exists('WC_Payment_Gateway')) return;

    if( isset($_GET['msg']) && !empty($_GET['msg']) ){
        add_action('the_content', 'instamojo_showMessage');
    }
    function instamojo_showMessage($content){
        $message_format = '<div class="%s">%s</div>';
        return sprintf($message_format, htmlentities($_GET['class'], ENT_QUOTES), htmlentities(urldecode($_GET['msg'])));
    }
    /**
     * Gateway class
     */
    class WC_Instamojo extends WC_Payment_Gateway{
        public function __construct(){
            $this->id                   = 'instamojo';
            $this->method_title         = __('Instamojo ', 'instamojo');
            $this->method_description   = "Instamojo lets you collect payments instantly.";
            $this->has_fields           = false;
            $this->icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAeZJREFUOI19krtuE0EUhv9zdmd3LYSECEi20WJBBVTAM8RGioREFwrAtNBQ2E4DNU3WFNSQyJYtpUsaF7kg3gAQUPACdoQgEsQ49to7cyiIE+2uzUinmTPfN/9cKFfZvkfEzwgYYNYgUpGYyveg9HVmGwAuVXY3OHNmWSYhBJLgGaInQ2PM7f36nW9JAaPTetkNivfN8KgBywKMjpXoCcCcIeZP2ZXd62mB0BV02ofd+uJjGYVNUl46pzEgYpcFH5ISBmEMzz2LTvtH91WxLGHYmCkRA2L2khIGAGgNWNYFdFo//yUZNUm585J4LPiYq2xfOxWcSOyF0yTjBjkZgO14EYNtxyXmL/nazk07tsNJkvZBd2lxIV/d+0UkN4SgE6cBAbaAV+KC45jwvPN41yjzgXorF8e3mEgnlwmEyYgXFxAByga4/8BvXv0jOflMcIHE3wAIbCmYcPDcTsHOUbmwVhhE2WgL2gCShsl2oMN+tbdaqvPxHGDbgBo98t8UfuscNiHzYAUzCWu91VJ9+goEpQA1fFhY9/smjy0x+j/wuNYLisF0lkHkQA6f+muX+1FWNiHzYCcFT8PDf/J+Wc7xhuhoxoUBZCmYKKxOY8d6+erOXYBbINEEmBQNOEbkxX5Qej2jh79RaeQT2vwcPgAAAABJRU5ErkJggg==';
            $this->order_button_text  = __('Proceed to Pay', 'instamojo');
            $this->init_form_fields();
            $this->init_settings();           
            $this->title            = $this->settings['title'];
            $this->api_key          = $this->settings['api_key'];
            $this->auth_token       = $this->settings['auth_token'];
            $this->private_salt     = $this->settings['private_salt'];
            $this->custom_field     = $this->settings['custom_field'];
            $this->payment_link     = $this->settings['payment_link'];
            $this->redirect_url     = $this->settings['thank_you_url'];
            $this->thank_you_msg    = $this->settings['thank_you_msg'];
            //$this->description      = $this->settings['description'];

            $this->msg['message']   = "";
            $this->msg['class']     = "";
                    
            add_action('woocommerce_api_' . strtolower(get_class($this)),
                       array(&$this, 'check_instamojo_response'));
            
            if (version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' )){
                /* 2.0.0 */
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
            }else{
                /* 1.6.6 */
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }

        }
    
        public function init_form_fields(){

            $checkout_url = site_url();
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __( 'Enable Instamojo Payment', 'woocommerce' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __( 'Title*', 'woocommerce' ),
                    'type' => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default' => __( 'Instamojo', 'woocommerce' ),
                    'desc_tip'      => true,
                ),
                'payment_link' =>array(
                    'title' => __('Instamojo Payment Link*','instamojo' ),
                    'type' => 'text',
                    'default' => '',
                    'placeholder' => _x('https://www.instamojo.com/<username>/<slug>/', 'placeholder', 'woocommerce')
                ),
                'api_key' =>array(
                    'title' => __('Private Api Key*','instamojo'),
                    'type' => 'text',
                    'default' => '',
                    'description' => __('Log in to your Instamojo account and get this from https://www.instamojo.com/developers/.', 'Instamojo')
                ),
                'auth_token' =>array(
                    'title' => __('Private Auth Token*','instamojo'),
                    'type' => 'text',
                    'default' => '',
                    'description' => __('Log in to your Instamojo account and get this from https://www.instamojo.com/developers/.', 'Instamojo')
                ),
                'private_salt' =>array(
                    'title' => __('Private Salt*','instamojo'),
                    'type' => 'text',
                    'default' => '',
                    'description' => __('Log in to your Instamojo account and get this from https://www.instamojo.com/developers/.', 'Instamojo')
                ),
                'custom_field' =>array(
                    'title' => __('Custom Field*', 'instamojo'),
                    'type' => 'text',
                    'desc_tip'=> true,
                    'placeholder' => _x('Field_<number>', 'placeholder', 'woocommerce'),
                    'description' => __('On your payment link, click on "More Options" at the top of the page and then click on "Custom Fields". Now create a custom field called "Order ID" and mark it as "required". In the custom field creation page, hover over the field you just created. You\'ll see a field with the format "Field_<number>". Note down the full name (including the "Field_" bit. Note that this is case sensitive!). Enter this name in the "Custom field" field of the Instamojo settings page in WooCommerce.', 'Instamojo')
                ),
                'thank_you_url' =>array(
                    'title' => __('Thank You Url','instamojo'),
                    'type' => 'text',
                    'default' => $checkout_url,
                    'desc_tip'=> true,
                    'description' => __('Thank you page\'s url ', 'Instamojo'),
                    'placeholder' => _x('This will be your thank you url.', 'placeholder', 'woocommerce')
                ),
                'thank_you_msg' =>array(
                    'title' => __('Thank You Message','instamojo'),
                    'type' => 'text',
                    'default' => 'Payment received, your item(s) are now being processed.',
                    'desc_tip'=> "Message displayed to user after successful payment.",
                    'placeholder' => _x('Payment received, your item(s) are now being processed.', 'placeholder', 'woocommerce')
                ),
                
            );
        }
        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         **/
        public function admin_options(){
            echo '<h3>'.__('Instamojo', 'instamojo').'</h3>';
            echo '<p>'.__('We\'re on a mission to make mobile/desktop payments simple, accessible & fun— "Think Payments, Think Instamojo".').'</p>';
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            echo '</table>';
        }
        /**
        * Process the payment and return the result
        **/
        function process_payment($order_id){
            global $woocommerce;
            $order = new WC_Order($order_id);

            $amount = $woocommerce->cart->total;

            $billing_email =  substr($order->billing_email, 0, 75);
            $delivery_name = substr(trim($order->billing_first_name ." ".$order->billing_last_name), 0, 20);
            $billing_tel = substr($order->billing_phone , 0, 20);

            $data_arr = Array();
            $data_arr["data_amount"] = $amount;
            $data_arr["data_name"] = $delivery_name;
            $data_arr["data_phone"] = $billing_tel;
            $data_arr["data_email"] = $billing_email;

            $custom_field = "data_". $this->custom_field;
            $custom_field1= strtolower($custom_field);
            $data_arr[$custom_field1] = $order_id;

            $ver = explode('.', phpversion());
            $major = (int) $ver[0];
            $minor = (int) $ver[1];

            if($major >= 5 and $minor >= 4){
                 ksort($data_arr, SORT_STRING | SORT_FLAG_CASE);
            }
            else{
                 uksort($data_arr, 'strcasecmp');
            }

            $str = hash_hmac("sha1", implode("|", $data_arr), $this->private_salt);
            $encoded_number = urlencode($billing_tel);
            $link= url_handler($this->payment_link) . "intent=buy&";
            $link.="data_readonly=data_email&data_readonly=data_amount&data_readonly=data_phone&data_readonly=data_name&data_readonly={$custom_field}&data_hidden={$custom_field}";
            $link.="&data_amount=$amount&data_name=$delivery_name&data_email=$billing_email&data_phone=$encoded_number&{$custom_field}=$order_id&data_sign=$str";

            $_SESSION["order_id"] = $order_id;            return array(
                'result' => 'success', 
                'redirect' => $link
            );
        }
        /**
        * Check for valid Instamojo server callback
        **/

        function check_instamojo_response(){

            global $woocommerce;
            $msg = Array();

            if(isset($_REQUEST['status']) && isset($_REQUEST['payment_id'])){
                $payment_id = $_REQUEST['payment_id'];
                $data = check_instamojo_payment_status($this->api_key, $this->auth_token, $payment_id);

                if(!empty($data)){
                    try{
                        try{
                            if($data['payment']['status'] == "Credit" || $data['payment']['status'] == "Failed" || $data['payment']['status'] == "Initiated"){
                                $order_id = $data['payment']['custom_fields'][$this->custom_field]['value'];
                                $order = new WC_Order($order_id);
                                if($data['payment']['status'] == "Credit"){
                                    $order->add_order_note('Payment was successfull.<br />Instamojo Payment ID: '. $payment_id);
                                    $order->payment_complete();
                                    if(empty($this->thank_you_msg)){
                                        $msg['msg'] = "Payment received, your item(s) are now being processed.";
                                    }
                                    else{
                                        $msg['msg'] = $this->thank_you_msg;
                                    }
                                    $msg['class'] = 'woocommerce-message';
                                    if(empty($this->redirect_url)){
                                        $this->redirect_url = site_url() . "/checkout/order-received/";
                                        $redirect_url = $this->redirect_url . $order_id . '/?key=' . $order->order_key;
                                        wp_redirect($redirect_url);
                                        exit;
                                    } 
                                }
                                else if($data['payment']['status'] == "Failed"){
                                    $order -> update_status('failed');
                                    $order -> add_order_note('Payment failed. <br />Instamojo Payment ID: '. $payment_id);
                                    $msg['class'] = 'woocommerce-error';
                                    $msg['msg'] = "Payment failed.";

                                }
                                else if($data['payment']['status'] == "Initiated"){
                                    $order -> update_status('failed');
                                    $order -> add_order_note('Payment was initiated but never completed for Instamojo Payment ID: '. $payment_id);
                                    $msg['class'] = 'woocommerce-error';
                                    $msg['msg'] = "Payment failed.";
                                }
                                $woocommerce->cart->empty_cart();

                            }
                            else{
                                throw new Exception($data['payment']['status']);
                            }
                        }
                        catch (Exception $e){
                            $msg['class'] = 'woocommerce-error';
                            $msg['msg'] = "Failed to get the payment details: ". $e->getMessage();
                        }
                    }catch(Exception $e){
                        $msg['class'] = 'woocommerce-error';
                        $msg['msg'] = "Oops! something went wrong while processing the request.";
                    }
                }
                else{
                    $msg['class'] = 'woocommerce-error';
                    $msg['msg'] = "Invalid order.";
                }
            }
            else{
                $msg['class'] = 'woocommerce-error';
                $msg['msg'] = "Invalid or missing parameters.";
            }
            if(empty($this->redirect_url)){
                $this->redirect_url = site_url();
            }
            if(substr($this->redirect_url, -1) != '/'){
                $this->redirect_url .= '/' ;
            }
            $redirect_url = add_query_arg(array('msg' => urlencode($msg['msg']), 'class' => urlencode($msg['class'])), $this->redirect_url);

            wp_redirect($redirect_url);
            exit;
        }
        
        }
        /**
        * Add the Gateway to WooCommerce
        **/
        function woocommerce_add_instamojo_gateway($methods) {
            $methods[] = 'WC_Instamojo';
            return $methods;
        }

        add_filter('woocommerce_payment_gateways', 'woocommerce_add_instamojo_gateway' );
    }


function check_instamojo_payment_status($api_key, $auth_token, $payment_id){

    $cUrl = 'https://www.instamojo.com/api/1.1/payments/' . $payment_id . '/';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $cUrl);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Api-Key:$api_key",
                                               "X-Auth-Token:$auth_token"));
    $response = curl_exec($ch);
    $error_number = curl_errno($ch);
    $error_message = curl_error($ch);
    curl_close($ch);
    $response_obj = json_decode($response, true);
    if($response_obj['success'] == false) {
        $message = json_encode($response_obj['message']);
        return Array('payment' => Array('status' => $message));

    }
    if(empty($response_obj) || is_null($response_obj)){
        return Array('payment' => Array('status' => 'No response from the server.'));
    }
    else{
        return $response_obj;
    }
}
